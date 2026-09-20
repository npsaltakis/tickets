<?php

namespace App\Commands;

use App\Controllers\AdminToolsController;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cron-friendly reminder sender. Example (hourly):
 *   0 * * * * php /path/to/spark reminders:send 48
 */
class RemindersSend extends BaseCommand
{
    protected $group       = 'Tickets';
    protected $name        = 'reminders:send';
    protected $description = 'Emails ticket holders of events starting soon (each ticket is reminded once).';
    protected $usage       = 'reminders:send [hours_ahead]';
    protected $arguments   = ['hours_ahead' => 'Look-ahead window in hours (default 48)'];

    public function run(array $params)
    {
        $hours = max(1, (int) ($params[0] ?? 48));

        $controller = new AdminToolsController();
        $controller->initController(service('request'), service('response'), service('logger'));

        $result = $controller->runReminders($hours);

        CLI::write(sprintf('Reminders sent: %d of %d', (int) ($result['sent'] ?? 0), (int) ($result['total'] ?? 0)), 'green');
    }
}
