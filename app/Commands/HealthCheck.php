<?php

namespace App\Commands;

use App\Libraries\EmailQueue;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Cron-friendly self check that emails the administrator when something is wrong (throttled to one alert per 6 hours).
 * Example (every 15 minutes): `php spark health:check`
 */
class HealthCheck extends BaseCommand
{
    protected $group       = 'Tickets';
    protected $name        = 'health:check';
    protected $description = 'Checks database, email queue, disk space and backups; alerts ADMIN_NOTIFY_EMAIL on problems.';
    protected $usage       = 'health:check';

    public function run(array $params)
    {
        $problems = [];

        try {
            $db = db_connect();
            $db->query('SELECT 1');

            $table  = $db->prefixTable('email_queue');
            $failed = $db->tableExists('email_queue')
                ? $db->table($table)->where('sent_at', null)->where('attempts >=', EmailQueue::MAX_ATTEMPTS)->countAllResults()
                : 0;
            $backlog = $db->tableExists('email_queue')
                ? $db->table($table)->where('sent_at', null)->where('attempts <', EmailQueue::MAX_ATTEMPTS)->where('created_at <', date('Y-m-d H:i:s', strtotime('-1 hour')))->countAllResults()
                : 0;

            if ($failed > 0) {
                $problems[] = "{$failed} email(s) failed permanently (see Admin > Email queue).";
            }
            if ($backlog > 0) {
                $problems[] = "{$backlog} email(s) waiting for more than an hour - is `php spark emails:process` scheduled?";
            }
        } catch (Throwable $exception) {
            $problems[] = 'Database unreachable: ' . $exception->getMessage();
        }

        if (! is_writable(WRITEPATH)) {
            $problems[] = 'writable/ is not writable.';
        }

        $free = @disk_free_space(WRITEPATH);
        if ($free !== false && $free < 200 * 1024 * 1024) {
            $problems[] = 'Low disk space: ' . number_format($free / 1048576) . ' MB free.';
        }

        $backups = glob(WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . 'backup-*.sql.gz') ?: [];
        if ($backups === []) {
            $problems[] = 'No database backup found (schedule `php spark db:backup`).';
        } else {
            $newest = max(array_map('filemtime', $backups));
            if ($newest < time() - 36 * 3600) {
                $problems[] = 'Newest database backup is older than 36 hours.';
            }
        }

        if ($problems === []) {
            CLI::write('OK - all checks passed.', 'green');

            return EXIT_SUCCESS;
        }

        foreach ($problems as $problem) {
            CLI::error('- ' . $problem);
        }

        $cache = cache();
        $to    = trim((string) getenv('ADMIN_NOTIFY_EMAIL'));

        if ($to !== '' && ! $cache->get('health_alert_sent')) {
            try {
                $mail = service('email');
                $mail->clear();
                $mail->setTo($to);
                $mail->setSubject('[Tickets] Health check failed');
                $mail->setMailType('html');
                $mail->setMessage('<p>' . implode('</p><p>', array_map('esc', $problems)) . '</p><p>' . esc(base_url('/')) . '</p>');

                if ($mail->send(false)) {
                    $cache->save('health_alert_sent', true, 6 * 3600);
                    CLI::write('Alert emailed to ' . $to, 'yellow');
                }
            } catch (Throwable $exception) {
                CLI::error('Could not send alert: ' . $exception->getMessage());
            }
        }

        return EXIT_ERROR;
    }
}
