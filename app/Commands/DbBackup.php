<?php

namespace App\Commands;

use App\Libraries\Backups;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Pure-PHP logical backup (no mysqldump binary needed): writes writable/backups/backup-<timestamp>.sql.gz
 * and removes backups older than the retention period. Run it from cron, e.g. daily: `php spark db:backup`.
 * Pass "full" to include the uploaded images and Wallet keys (backup-full-<timestamp>.zip).
 */
class DbBackup extends BaseCommand
{
    protected $group       = 'Tickets';
    protected $name        = 'db:backup';
    protected $description = 'Creates a backup of the database (or, with "full", database + uploaded files).';
    protected $usage       = 'db:backup [keep_days] [full]';
    protected $arguments   = [
        'keep_days' => 'Delete backups older than this many days (default: Admin → Backups, backup.keepDays in .env, or 14)',
        'full'      => 'Also back up uploaded images and Wallet keys',
    ];

    public function run(array $params)
    {
        $full     = in_array('full', $params, true);
        $numbers  = array_values(array_filter($params, 'ctype_digit'));
        $keepDays = max(1, (int) ($numbers[0] ?? Backups::keepDays()));
        $backups  = new Backups();

        try {
            $name = $full ? $backups->createFull() : $backups->createDatabase();
        } catch (Throwable $exception) {
            \App\Libraries\AdminNotifications::add('backup_failed', ['error' => $exception->getMessage()], 'admin/backups');
            CLI::error('Backup failed: ' . $exception->getMessage());

            return EXIT_ERROR;
        }

        $removed = $backups->prune($keepDays);
        $path    = $backups->path($name);

        CLI::write(sprintf('Backup written: %s (%s KB). Old backups removed: %d', $name, number_format(((int) filesize((string) $path)) / 1024, 1), $removed), 'green');

        return EXIT_SUCCESS;
    }
}
