<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Pure-PHP logical backup (no mysqldump binary needed): writes writable/backups/backup-<timestamp>.sql.gz
 * and removes backups older than the retention period. Run it from cron, e.g. daily: `php spark db:backup`.
 */
class DbBackup extends BaseCommand
{
    protected $group       = 'Tickets';
    protected $name        = 'db:backup';
    protected $description = 'Creates a gzip-compressed SQL backup of the application database.';
    protected $usage       = 'db:backup [keep_days]';
    protected $arguments   = ['keep_days' => 'Delete backups older than this many days (default 14, or backup.keepDays in .env)'];

    public function run(array $params)
    {
        $keepDays = max(1, (int) ($params[0] ?? env('backup.keepDays', 14)));
        $dir      = WRITEPATH . 'backups';

        if (! is_dir($dir) && ! mkdir($dir, 0750, true) && ! is_dir($dir)) {
            CLI::error('Cannot create ' . $dir);

            return EXIT_ERROR;
        }

        $file   = $dir . DIRECTORY_SEPARATOR . 'backup-' . date('Ymd-His') . '.sql.gz';
        $handle = gzopen($file, 'wb9');

        if ($handle === false) {
            CLI::error('Cannot write ' . $file);

            return EXIT_ERROR;
        }

        $db = db_connect();
        gzwrite($handle, "-- Backup of {$db->getDatabase()} at " . date('c') . "\nSET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\n\n");

        $tables = array_map(static fn (array $row): string => (string) reset($row), $db->query('SHOW TABLES')->getResultArray());
        $rows   = 0;

        foreach ($tables as $table) {
            $create = $db->query('SHOW CREATE TABLE ' . $db->escapeIdentifiers($table))->getRowArray();
            gzwrite($handle, 'DROP TABLE IF EXISTS ' . $db->escapeIdentifiers($table) . ";\n" . (string) ($create['Create Table'] ?? '') . ";\n\n");

            $offset = 0;
            do {
                $chunk = $db->query('SELECT * FROM ' . $db->escapeIdentifiers($table) . ' LIMIT 500 OFFSET ' . $offset)->getResultArray();

                foreach ($chunk as $row) {
                    $values = array_map(static fn ($v): string => $v === null ? 'NULL' : $db->escape((string) $v), array_values($row));
                    gzwrite($handle, 'INSERT INTO ' . $db->escapeIdentifiers($table) . ' VALUES (' . implode(',', $values) . ");\n");
                    $rows++;
                }

                $offset += 500;
            } while (count($chunk) === 500);

            gzwrite($handle, "\n");
        }

        gzwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($handle);

        $removed = 0;
        foreach (glob($dir . DIRECTORY_SEPARATOR . 'backup-*.sql.gz') ?: [] as $old) {
            if (filemtime($old) < time() - ($keepDays * 86400) && unlink($old)) {
                $removed++;
            }
        }

        CLI::write(sprintf('Backup written: %s (%d tables, %d rows, %s KB). Old backups removed: %d', basename($file), count($tables), $rows, number_format(filesize($file) / 1024, 1), $removed), 'green');

        return EXIT_SUCCESS;
    }
}
