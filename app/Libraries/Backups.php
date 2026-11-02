<?php

namespace App\Libraries;

use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Backups in writable/backups (Admin → Backups and `php spark db:backup`).
 *
 * - backup-<time>.sql.gz       database only (also what the daily cron writes)
 * - backup-full-<time>.zip     database.sql.gz + files/: uploaded images (events, slider, logo) and keys/ (Wallet)
 * - pre-restore-<time>.sql.gz  the database as it was right before a restore, to undo it
 * - uploaded-<time>.<ext>      a backup uploaded from another server to restore here
 * - code-*.zip                 written by the Updater before an update; listed for download only
 *
 * Code is not part of a backup: it comes from releases, and restoring an old backup must not bring old code back.
 * The dump is plain SQL with one INSERT per line (string values have their newlines escaped), read back one
 * statement at a time, so neither side holds the whole database in memory.
 */
class Backups
{
    /**
     * Files of a full backup, relative to the site root: uploaded images and the Wallet keys.
     */
    private const FILE_PATTERN = '#^(public/assets/images/(\d+|hero|site)/|keys/)[^\0]+$#';

    private const NAME_PATTERN = '/^(backup|backup-full|pre-restore|uploaded|code)-[A-Za-z0-9._-]+\.(sql\.gz|sql|zip)$/';

    private string $dir;

    public function __construct()
    {
        $this->dir = WRITEPATH . 'backups';
    }

    /**
     * How long backups are kept: Admin → Backups, else backup.keepDays in .env, else 14 days.
     */
    public static function keepDays(): int
    {
        $days = (int) SiteSettings::get('backup_keep_days');

        return max(1, $days > 0 ? $days : (int) env('backup.keepDays', 14));
    }

    /**
     * Writes <prefix>-<time>.sql.gz and returns its file name.
     */
    /**
     * @param (callable(int, string): void)|null $progress Called with a percentage and a short message as work advances.
     */
    public function createDatabase(string $prefix = 'backup', ?callable $progress = null): string
    {
        $progress ??= static function (int $percent, string $message): void {};

        $this->prepareDir();
        $name = $prefix . '-' . date('Ymd-His') . '.sql.gz';
        $this->dumpDatabase($this->dir . DIRECTORY_SEPARATOR . $name, $progress, 0, 99);
        $progress(100, $name);

        return $name;
    }

    /**
     * Writes backup-full-<time>.zip (database + files) and returns its file name.
     *
     * @param (callable(int, string): void)|null $progress See createDatabase().
     */
    public function createFull(?callable $progress = null): string
    {
        $progress ??= static function (int $percent, string $message): void {};

        $this->prepareDir();
        $name    = 'backup-full-' . date('Ymd-His') . '.zip';
        $path    = $this->dir . DIRECTORY_SEPARATOR . $name;
        $sqlPath = $this->dir . DIRECTORY_SEPARATOR . 'tmp-' . bin2hex(random_bytes(6)) . '.sql.gz';

        try {
            $this->dumpDatabase($sqlPath, $progress, 0, 40);

            $zip = new ZipArchive();
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Cannot create ' . $name);
            }
            $zip->addFile($sqlPath, 'database.sql.gz');

            $files = $this->backedUpFiles();
            $total = count($files);
            $done  = 0;
            foreach ($files as $relative => $absolute) {
                $zip->addFile($absolute, 'files/' . $relative);
                $done++;
                if ($done === $total || $done % max(1, intdiv($total, 50)) === 0) {
                    $progress(40 + intdiv(45 * $done, max(1, $total)), lang('App.backupProgressFiles', [$done, $total]));
                }
            }

            // Compressing happens when the archive is closed, which takes most of the time for many images.
            $progress(88, lang('App.backupProgressZip'));
            if (! $zip->close()) {
                throw new RuntimeException('Cannot write ' . $name);
            }
        } catch (Throwable $exception) {
            @unlink($path);

            throw $exception;
        } finally {
            @unlink($sqlPath);
        }

        $progress(100, $name);

        return $name;
    }

    /**
     * Newest first.
     *
     * @return list<array{name: string, type: string, size: int, time: int}>
     */
    public function all(): array
    {
        $list = [];
        foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            $name = basename($path);
            if (is_file($path) && preg_match(self::NAME_PATTERN, $name) === 1) {
                $list[] = ['name' => $name, 'type' => $this->type($name), 'size' => (int) filesize($path), 'time' => (int) filemtime($path)];
            }
        }
        usort($list, static fn (array $a, array $b): int => $b['time'] <=> $a['time']);

        return $list;
    }

    /**
     * Full path of a backup by name, or null for anything that is not one of ours.
     */
    public function path(string $name): ?string
    {
        if (preg_match(self::NAME_PATTERN, $name) !== 1) {
            return null;
        }
        $path = $this->dir . DIRECTORY_SEPARATOR . $name;

        return is_file($path) ? $path : null;
    }

    public function delete(string $name): bool
    {
        $path = $this->path($name);

        return $path !== null && @unlink($path);
    }

    /**
     * Removes database and full backups older than $keepDays (code-*.zip are pruned by the Updater).
     */
    public function prune(int $keepDays): int
    {
        $removed = 0;
        foreach ($this->all() as $backup) {
            if (in_array($backup['type'], ['db', 'full', 'pre-restore', 'uploaded'], true) && $backup['time'] < time() - $keepDays * 86400) {
                $removed += $this->delete($backup['name']) ? 1 : 0;
            }
        }

        return $removed;
    }

    /**
     * Keeps an uploaded backup under our own name so it can be restored (and is listed like the others).
     */
    public function storeUpload(string $tempPath, string $clientName): string
    {
        $extension = preg_match('/\.(sql\.gz|sql|zip)$/i', $clientName, $match) === 1 ? strtolower($match[1]) : '';
        if ($extension === '') {
            throw new RuntimeException(lang('App.backupBadFile'));
        }

        $this->prepareDir();
        $name = 'uploaded-' . date('Ymd-His') . '.' . $extension;
        if (! @move_uploaded_file($tempPath, $this->dir . DIRECTORY_SEPARATOR . $name) && ! @rename($tempPath, $this->dir . DIRECTORY_SEPARATOR . $name)) {
            throw new RuntimeException(lang('App.backupBadFile'));
        }

        return $name;
    }

    /**
     * Replaces the database (and, for a full backup, the backed-up files) with the backup's contents.
     * The current database is saved first as pre-restore-<time>.sql.gz; its name is returned.
     */
    public function restore(string $name): string
    {
        $path = $this->path($name);
        if ($path === null || $this->type($name) === 'code') {
            throw new RuntimeException(lang('App.backupNotFound'));
        }

        $zip = null;
        if (str_ends_with($name, '.zip')) {
            $zip = new ZipArchive();
            if ($zip->open($path) !== true || $zip->locateName('database.sql.gz') === false || ! $this->hasSafeEntries($zip)) {
                throw new RuntimeException(lang('App.backupBadFile'));
            }
        }

        $safety = $this->createDatabase('pre-restore');

        if ($zip === null) {
            $this->importSql($path);
        } else {
            $sqlPath = $this->dir . DIRECTORY_SEPARATOR . 'tmp-' . bin2hex(random_bytes(6)) . '.sql.gz';
            try {
                file_put_contents($sqlPath, $zip->getStream('database.sql.gz'));
                $this->importSql($sqlPath);
            } finally {
                @unlink($sqlPath);
            }
            $this->restoreFiles($zip);
            $zip->close();
        }

        // An older backup restored into a newer version: bring its tables up to date.
        service('migrations')->latest();
        cache()->clean();

        return $safety;
    }

    private function type(string $name): string
    {
        return match (true) {
            str_starts_with($name, 'backup-full-') => 'full',
            str_starts_with($name, 'backup-')      => 'db',
            str_starts_with($name, 'pre-restore-') => 'pre-restore',
            str_starts_with($name, 'uploaded-')    => 'uploaded',
            default                                => 'code',
        };
    }

    private function prepareDir(): void
    {
        if (! is_dir($this->dir) && ! @mkdir($this->dir, 0750, true) && ! is_dir($this->dir)) {
            throw new RuntimeException('Cannot create ' . $this->dir);
        }
        if (! is_file($this->dir . DIRECTORY_SEPARATOR . '.htaccess')) {
            @file_put_contents($this->dir . DIRECTORY_SEPARATOR . '.htaccess', "<IfModule authz_core_module>\n    Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n    Deny from all\n</IfModule>\n");
        }
        if (! is_file($this->dir . DIRECTORY_SEPARATOR . 'index.html')) {
            @file_put_contents($this->dir . DIRECTORY_SEPARATOR . 'index.html', '');
        }
    }

    /**
     * Tables of this site only: with a table prefix set, other applications sharing the database are left out.
     *
     * @return list<string>
     */
    private function tables(): array
    {
        $db     = db_connect();
        $prefix = (string) $db->getPrefix();
        $tables = array_map(static fn (array $row): string => (string) reset($row), $db->query('SHOW TABLES')->getResultArray());

        return array_values(array_filter($tables, static fn (string $table): bool => $prefix === '' || str_starts_with($table, $prefix)));
    }

    /**
     * @param (callable(int, string): void)|null $progress Reported from $from to $to percent, one step per table.
     */
    private function dumpDatabase(string $file, ?callable $progress = null, int $from = 0, int $to = 100): void
    {
        $handle = gzopen($file, 'wb6');
        if ($handle === false) {
            throw new RuntimeException('Cannot write ' . $file);
        }

        try {
            $db     = db_connect();
            $tables = $this->tables();
            gzwrite($handle, "-- Backup of {$db->getDatabase()} at " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");

            foreach ($tables as $index => $table) {
                if ($progress !== null) {
                    $progress($from + intdiv(($to - $from) * $index, max(1, count($tables))), lang('App.backupProgressTable', [$table, $index + 1, count($tables)]));
                }
                $quoted = $db->escapeIdentifiers($table);
                $create = $db->query('SHOW CREATE TABLE ' . $quoted)->getRowArray();
                gzwrite($handle, 'DROP TABLE IF EXISTS ' . $quoted . ";\n" . (string) ($create['Create Table'] ?? '') . ";\n\n");

                $offset = 0;
                do {
                    $chunk = $db->query('SELECT * FROM ' . $quoted . ' LIMIT 500 OFFSET ' . $offset)->getResultArray();
                    foreach ($chunk as $row) {
                        $values = array_map(static fn ($v): string => $v === null ? 'NULL' : $db->escape((string) $v), array_values($row));
                        gzwrite($handle, 'INSERT INTO ' . $quoted . ' VALUES (' . implode(',', $values) . ");\n");
                    }
                    $offset += 500;
                } while (count($chunk) === 500);

                gzwrite($handle, "\n");
            }

            gzwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } catch (Throwable $exception) {
            gzclose($handle);
            @unlink($file);

            throw $exception;
        }

        gzclose($handle);
    }

    /**
     * Runs a .sql or .sql.gz file one statement at a time (gzopen also reads plain files).
     * A statement ends with a line ending in ";"; our dumps keep every INSERT on one line.
     */
    private function importSql(string $file): void
    {
        $handle = gzopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException(lang('App.backupBadFile'));
        }

        $db        = db_connect();
        $statement = '';
        $db->query('SET FOREIGN_KEY_CHECKS=0');

        try {
            while (($line = gzgets($handle)) !== false) {
                $trimmed = trim($line);
                if ($statement === '' && ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*'))) {
                    continue;
                }

                $statement .= $line;
                if (str_ends_with($trimmed, ';')) {
                    if ($db->query($statement) === false) {
                        $error = $db->error();

                        throw new RuntimeException('SQL error ' . ($error['code'] ?? '') . ': ' . ($error['message'] ?? ''));
                    }
                    $statement = '';
                }
            }
        } finally {
            gzclose($handle);
            $db->query('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * @return array<string, string> Path relative to the site root (with /) => absolute path
     */
    private function backedUpFiles(): array
    {
        $files = [];

        // public/ is FCPATH, which hosts may place outside ROOTPATH: each folder maps from its own base.
        $bases = [
            'public/assets/images/' => FCPATH . 'assets' . DIRECTORY_SEPARATOR . 'images',
            'keys/'                 => ROOTPATH . 'keys',
        ];

        foreach ($bases as $prefix => $base) {
            if (! is_dir($base)) {
                continue;
            }
            $baseLength = strlen(rtrim(str_replace('\\', '/', $base), '/') . '/');
            $iterator   = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                $relative = $prefix . substr(str_replace('\\', '/', $file->getPathname()), $baseLength);
                if ($file->isFile() && preg_match(self::FILE_PATTERN, $relative) === 1) {
                    $files[$relative] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Where a backed-up file (path relative to the site root, as in backedUpFiles()) lives on this server.
     */
    private function absolutePath(string $relative): string
    {
        $path = str_starts_with($relative, 'public/') ? FCPATH . substr($relative, 7) : ROOTPATH . $relative;

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    private function hasSafeEntries(ZipArchive $zip): bool
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if ($entry === 'database.sql.gz' || str_ends_with($entry, '/')) {
                continue;
            }
            if (! str_starts_with($entry, 'files/') || preg_match('#(^|/)\.\.(/|$)#', $entry) === 1
                || preg_match(self::FILE_PATTERN, substr($entry, 6)) !== 1) {
                return false;
            }
        }

        return true;
    }

    private function restoreFiles(ZipArchive $zip): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if (! str_starts_with($entry, 'files/') || str_ends_with($entry, '/')) {
                continue;
            }

            $target = $this->absolutePath(substr($entry, 6));
            if (! is_dir(dirname($target))) {
                @mkdir(dirname($target), 0755, true);
            }
            if (file_put_contents($target, $zip->getStream($entry)) === false) {
                throw new RuntimeException('Cannot write ' . substr($entry, 6));
            }
        }
    }
}
