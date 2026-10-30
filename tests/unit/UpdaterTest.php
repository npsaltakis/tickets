<?php

use App\Libraries\Updater;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UpdaterTest extends CIUnitTestCase
{
    private string $root;
    private string $zip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/updater-test-' . bin2hex(random_bytes(4));
        $this->zip  = $this->root . '-package.zip';

        // An installed site with per-site files that must survive the update.
        $this->write('.env', 'SECRET=1');
        $this->write('writable/logs/log.txt', 'old log');
        $this->write('keys/wallet.json', '{}');
        $this->write('public/assets/images/site/logo.png', 'client logo');
        $this->write('public/assets/images/7/poster.jpg', 'event poster');
        $this->write('app/Config/App.php', 'old app');
        $this->write('VERSION', 'v1.0.0');
    }

    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
        @unlink($this->zip);

        parent::tearDown();
    }

    public function testVersionComparison(): void
    {
        $this->assertTrue(Updater::isNewer('v1.2.0', 'v1.1.9'));
        $this->assertTrue(Updater::isNewer('v1.10.0', 'v1.9.0'));
        $this->assertTrue(Updater::isNewer('v1.0.0', ''));
        $this->assertFalse(Updater::isNewer('v1.2.0', 'v1.2.0'));
        $this->assertFalse(Updater::isNewer('', 'v1.0.0'));
    }

    public function testProtectedPaths(): void
    {
        $this->assertTrue(Updater::isProtected('.env'));
        $this->assertTrue(Updater::isProtected('writable/cache/index.html'));
        $this->assertTrue(Updater::isProtected('keys/wallet.json'));
        $this->assertTrue(Updater::isProtected('public/assets/images/site/logo.png'));
        $this->assertTrue(Updater::isProtected('public/assets/images/12/a.jpg'));
        $this->assertFalse(Updater::isProtected('public/assets/images/logo.png'));
        $this->assertFalse(Updater::isProtected('app/Config/App.php'));
    }

    public function testInstallReplacesCodeAndKeepsSiteFiles(): void
    {
        $this->makePackage('v1.1.0', [
            '.env'                              => 'SECRET=overwritten',
            'writable/logs/log.txt'             => 'new log',
            'public/assets/images/site/logo.png' => 'package logo',
            'app/Config/App.php'                => 'new app',
            'app/Libraries/New.php'             => 'new file',
            'public/index.php'                  => 'index',
            'vendor/autoload.php'               => 'autoload',
            'spark'                             => 'spark',
        ]);

        $backup = $this->updater(hash_file('sha256', $this->zip))->install($this->release());

        $this->assertSame('v1.1.0', trim($this->read('VERSION')));
        $this->assertSame('new app', $this->read('app/Config/App.php'));
        $this->assertSame('new file', $this->read('app/Libraries/New.php'));
        $this->assertSame('SECRET=1', $this->read('.env'));
        $this->assertSame('old log', $this->read('writable/logs/log.txt'));
        $this->assertSame('client logo', $this->read('public/assets/images/site/logo.png'));
        $this->assertSame('event poster', $this->read('public/assets/images/7/poster.jpg'));
        $this->assertFileDoesNotExist($this->root . '/writable/updates/package');

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($backup));
        $this->assertSame('old app', $zip->getFromName('app/Config/App.php'));
        $this->assertFalse($zip->getFromName('.env'));
        $zip->close();
    }

    public function testChecksumMismatchChangesNothing(): void
    {
        $this->makePackage('v1.1.0', ['app/Config/App.php' => 'new app', 'public/index.php' => 'i', 'vendor/autoload.php' => 'a', 'spark' => 's']);

        try {
            $this->updater(str_repeat('0', 64))->install($this->release());
            $this->fail('Expected a checksum failure');
        } catch (RuntimeException $exception) {
            $this->assertSame(lang('App.updaterChecksum'), $exception->getMessage());
        }

        $this->assertSame('old app', $this->read('app/Config/App.php'));
        $this->assertSame('v1.0.0', $this->read('VERSION'));
    }

    /**
     * @return array{tag: string, zip: string, sha256: string}
     */
    private function release(): array
    {
        return ['tag' => 'v1.1.0', 'zip' => 'https://example.test/tickets-v1.1.0.zip', 'sha256' => 'https://example.test/tickets-v1.1.0.zip.sha256'];
    }

    private function updater(string $sha256): Updater
    {
        return new class ($this->root, $this->zip, $sha256) extends Updater {
            public function __construct(string $root, private string $zip, private string $sha256)
            {
                parent::__construct($root);
            }

            protected function fetch(string $url): ?string
            {
                return $this->sha256 . '  tickets-v1.1.0.zip';
            }

            protected function download(string $url, string $target): void
            {
                copy($this->zip, $target);
            }
        };
    }

    /**
     * @param array<string, string> $files
     */
    private function makePackage(string $tag, array $files): void
    {
        $zip = new ZipArchive();
        $zip->open($this->zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('VERSION', $tag . "\n");
        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }
        $zip->close();
    }

    private function write(string $path, string $content): void
    {
        $file = $this->root . '/' . $path;
        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, $content);
    }

    private function read(string $path): string
    {
        return (string) file_get_contents($this->root . '/' . $path);
    }
}
