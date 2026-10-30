<?php

namespace App\Libraries;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * Updates the installation from GitHub Releases. Publishing a release runs .github/workflows/release.yml,
 * which attaches tickets-<tag>.zip (code + vendor/) and its SHA-256; this downloads and verifies it,
 * backs up the files it replaces and copies it over the site. Per-site files are never touched.
 */
class Updater
{
    private const DEFAULT_REPO = 'npsaltakis/tickets';
    private const CACHE_KEY    = 'updater_latest_release';
    private const KEEP_BACKUPS = 3;

    /**
     * Paths (relative to the site root) the package never overwrites.
     */
    private const PROTECTED = ['.env', 'writable/', 'keys/', 'public/assets/images/site/'];

    /**
     * Files every valid package contains.
     */
    private const REQUIRED = ['VERSION', 'spark', 'app/Config/App.php', 'public/index.php', 'vendor/autoload.php'];

    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = rtrim($root ?? ROOTPATH, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Installed release tag, or '' when the site was not installed from a release (Git/FTP).
     */
    public function currentVersion(): string
    {
        $file = $this->root . 'VERSION';

        return is_file($file) ? trim((string) file_get_contents($file)) : '';
    }

    public static function isNewer(string $latest, string $current): bool
    {
        if ($latest === '') {
            return false;
        }

        return $current === '' || version_compare(ltrim($latest, 'vV'), ltrim($current, 'vV'), '>');
    }

    /**
     * A Git checkout (development) is updated with git, not with packages.
     */
    public function isGitCheckout(): bool
    {
        return is_dir($this->root . '.git');
    }

    /**
     * Latest published release, cached for 10 minutes; null when GitHub cannot be reached.
     *
     * @return array{tag: string, name: string, notes: string, published_at: string, url: string, zip: string, sha256: string}|null
     */
    public function latest(bool $refresh = false): ?array
    {
        $cached = cache(self::CACHE_KEY);
        if (! $refresh && is_array($cached)) {
            return $cached;
        }

        $body = $this->fetch('https://api.github.com/repos/' . $this->repo() . '/releases/latest');
        $data = $body === null ? null : json_decode($body, true);
        if (! is_array($data) || empty($data['tag_name'])) {
            return null;
        }

        $tag     = (string) $data['tag_name'];
        $release = [
            'tag'          => $tag,
            'name'         => (string) ($data['name'] ?? $tag),
            'notes'        => (string) ($data['body'] ?? ''),
            'published_at' => (string) ($data['published_at'] ?? ''),
            'url'          => (string) ($data['html_url'] ?? ''),
            'zip'          => '',
            'sha256'       => '',
        ];

        foreach ($data['assets'] ?? [] as $asset) {
            $name = (string) ($asset['name'] ?? '');
            if ($name === 'tickets-' . $tag . '.zip') {
                $release['zip'] = (string) $asset['browser_download_url'];
            } elseif ($name === 'tickets-' . $tag . '.zip.sha256') {
                $release['sha256'] = (string) $asset['browser_download_url'];
            }
        }

        cache()->save(self::CACHE_KEY, $release, 600);

        return $release;
    }

    /**
     * Downloads, verifies and installs a release. Returns the backup zip of the replaced files.
     *
     * @param array{tag: string, zip: string, sha256: string} $release
     */
    public function install(array $release): string
    {
        if ($this->isGitCheckout()) {
            throw new RuntimeException(lang('App.updaterGitCheckout'));
        }
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException(lang('App.updaterNoZip'));
        }
        if ($release['zip'] === '' || $release['sha256'] === '') {
            throw new RuntimeException(lang('App.updaterNotReady'));
        }

        @set_time_limit(600);

        $work = $this->root . 'writable' . DIRECTORY_SEPARATOR . 'updates';
        if (! is_dir($work) && ! mkdir($work, 0755, true) && ! is_dir($work)) {
            throw new RuntimeException('Cannot create ' . $work);
        }

        $lock = fopen($work . DIRECTORY_SEPARATOR . 'update.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException(lang('App.updaterBusy'));
        }

        $zipFile = $work . DIRECTORY_SEPARATOR . 'package.zip';
        $package = $work . DIRECTORY_SEPARATOR . 'package';

        try {
            $this->download($release['zip'], $zipFile);

            $expected = strtolower(substr(trim((string) $this->fetch($release['sha256'])), 0, 64));
            if (strlen($expected) !== 64 || ! hash_equals($expected, (string) hash_file('sha256', $zipFile))) {
                throw new RuntimeException(lang('App.updaterChecksum'));
            }

            $this->removeDir($package);
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true || ! $zip->extractTo($package)) {
                throw new RuntimeException(lang('App.updaterBadPackage'));
            }
            $zip->close();

            foreach (self::REQUIRED as $file) {
                if (! is_file($package . DIRECTORY_SEPARATOR . $file)) {
                    throw new RuntimeException(lang('App.updaterBadPackage') . ' (' . $file . ')');
                }
            }
            if (trim((string) file_get_contents($package . DIRECTORY_SEPARATOR . 'VERSION')) !== $release['tag']) {
                throw new RuntimeException(lang('App.updaterBadPackage') . ' (VERSION)');
            }

            $files  = $this->packageFiles($package);
            $backup = $this->backup($files, $release['tag']);
            $this->copyFiles($package, $files);
        } finally {
            @unlink($zipFile);
            $this->removeDir($package);
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        cache()->clean();
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        return $backup;
    }

    /**
     * Relative paths of the package files that may be installed (protected paths left out).
     *
     * @return list<string>
     */
    private function packageFiles(string $package): array
    {
        $files    = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($package, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($package) + 1));
            if (! self::isProtected($relative)) {
                $files[] = $relative;
            }
        }

        sort($files);

        return $files;
    }

    public static function isProtected(string $relative): bool
    {
        foreach (self::PROTECTED as $path) {
            if ($relative === rtrim($path, '/') || str_starts_with($relative, $path)) {
                return true;
            }
        }

        // Uploaded event images live in public/assets/images/<event id>/.
        return preg_match('#^public/assets/images/\d+/#', $relative) === 1;
    }

    /**
     * Zips the current copies of the files about to be replaced into writable/backups.
     *
     * @param list<string> $files
     */
    private function backup(array $files, string $tag): string
    {
        $dir = $this->root . 'writable' . DIRECTORY_SEPARATOR . 'backups';
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException('Cannot create ' . $dir);
        }

        $from = preg_replace('/[^A-Za-z0-9._-]/', '', $this->currentVersion()) ?: 'unversioned';
        $path = $dir . DIRECTORY_SEPARATOR . 'code-' . $from . '-before-' . preg_replace('/[^A-Za-z0-9._-]/', '', $tag) . '-' . date('Ymd-His') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create ' . $path);
        }
        foreach ($files as $relative) {
            if (is_file($this->root . $relative)) {
                $zip->addFile($this->root . $relative, $relative);
            }
        }
        if (! $zip->close()) {
            throw new RuntimeException('Cannot write ' . $path);
        }

        $old = glob($dir . DIRECTORY_SEPARATOR . 'code-*.zip') ?: [];
        rsort($old);
        foreach (array_slice($old, self::KEEP_BACKUPS) as $stale) {
            @unlink($stale);
        }

        return $path;
    }

    /**
     * @param list<string> $files
     */
    private function copyFiles(string $package, array $files): void
    {
        // VERSION last: if anything fails before it, the site still reports the old version and can retry.
        $files = array_values(array_diff($files, ['VERSION']));
        $files[] = 'VERSION';

        foreach ($files as $relative) {
            $target = $this->root . $relative;
            $dir    = dirname($target);
            if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
                throw new RuntimeException(lang('App.updaterCopyFailed') . ' ' . $relative);
            }

            // Copy next to the target, then rename, so no request ever reads a half-written file.
            $temp = $target . '.update-tmp';
            if (! copy($package . DIRECTORY_SEPARATOR . $relative, $temp) || ! rename($temp, $target)) {
                @unlink($temp);

                throw new RuntimeException(lang('App.updaterCopyFailed') . ' ' . $relative);
            }
        }
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    private function repo(): string
    {
        $repo = trim((string) env('updater.repo', self::DEFAULT_REPO));

        return preg_match('#^[\w.-]+/[\w.-]+$#', $repo) === 1 ? $repo : self::DEFAULT_REPO;
    }

    /**
     * GET over HTTPS; null on failure.
     */
    protected function fetch(string $url): ?string
    {
        $curl = curl_init($url);
        $headers = str_starts_with($url, 'https://api.github.com/') ? ['Accept: application/vnd.github+json'] : [];
        curl_setopt_array($curl, $this->curlOptions() + [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => $headers]);
        $body = curl_exec($curl);
        $ok   = $body !== false && curl_getinfo($curl, CURLINFO_RESPONSE_CODE) === 200;
        $info = curl_error($curl) ?: 'HTTP ' . curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        if (! $ok) {
            log_message('error', 'Updater request failed: {url} ({info})', ['url' => $url, 'info' => $info]);
        }

        return $ok ? (string) $body : null;
    }

    protected function download(string $url, string $target): void
    {
        $handle = fopen($target, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Cannot write ' . $target);
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $this->curlOptions() + [CURLOPT_FILE => $handle, CURLOPT_TIMEOUT => 300]);
        $ok    = curl_exec($curl) !== false && curl_getinfo($curl, CURLINFO_RESPONSE_CODE) === 200;
        $error = curl_error($curl);
        curl_close($curl);
        fclose($handle);

        if (! $ok) {
            @unlink($target);

            throw new RuntimeException(lang('App.updaterDownloadFailed') . ($error !== '' ? ' ' . $error : ''));
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function curlOptions(): array
    {
        return [
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_USERAGENT       => 'tickets-updater',
        ];
    }
}
