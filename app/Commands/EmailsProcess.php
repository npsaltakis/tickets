<?php

namespace App\Commands;

use App\Libraries\EmailQueue;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cron-friendly queue worker. Run it every few minutes, e.g. `php spark emails:process 100`.
 */
class EmailsProcess extends BaseCommand
{
    protected $group       = 'Tickets';
    protected $name        = 'emails:process';
    protected $description = 'Delivers pending queued emails (mass emails, event change notices, alerts).';
    protected $usage       = 'emails:process [limit]';
    protected $arguments   = ['limit' => 'Maximum number of emails to send in this run (default 100)'];

    public function run(array $params)
    {
        $queue = new EmailQueue();
        $sent  = $queue->flush(max(1, (int) ($params[0] ?? 100)));

        CLI::write(sprintf('Sent: %d, still pending: %d', $sent, $queue->pendingCount()), 'green');
    }
}
