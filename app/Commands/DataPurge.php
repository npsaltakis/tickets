<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Data retention: removes expired tokens, old queue/audit rows and abandoned unverified accounts.
 * Run weekly from cron: `php spark data:purge`. Use `--dry-run` to only count.
 */
class DataPurge extends BaseCommand
{
    protected $group       = 'Tickets';
    protected $name        = 'data:purge';
    protected $description = 'Applies the data retention rules (see PRODUCTION_CHECKLIST.md).';
    protected $usage       = 'data:purge [--dry-run]';
    protected $options     = ['--dry-run' => 'Only report what would be deleted'];

    public function run(array $params)
    {
        $dry = array_key_exists('dry-run', $params) || CLI::getOption('dry-run') !== null;
        $db  = db_connect();
        $now = date('Y-m-d H:i:s');

        $auditDays   = max(30, (int) env('retention.adminLogsDays', 365));
        $pendingDays = max(7, (int) env('retention.unverifiedAccountDays', 30));

        $rules = [
            'Expired password reset tokens'    => ['password_resets', static fn ($b) => $b->where('expires_at <', date('Y-m-d H:i:s', strtotime('-7 days')))],
            'Expired verification tokens'      => ['email_verifications', static fn ($b) => $b->where('expires_at <', date('Y-m-d H:i:s', strtotime('-7 days')))],
            'Expired email change requests'    => ['email_changes', static fn ($b) => $b->where('expires_at <', date('Y-m-d H:i:s', strtotime('-7 days')))],
            'Sent queued emails (>30 days)'    => ['email_queue', static fn ($b) => $b->where('sent_at <', date('Y-m-d H:i:s', strtotime('-30 days')))],
            'Failed queued emails (>90 days)'  => ['email_queue', static fn ($b) => $b->where('sent_at', null)->where('created_at <', date('Y-m-d H:i:s', strtotime('-90 days')))],
            "Audit logs (>{$auditDays} days)"  => ['admin_logs', static fn ($b) => $b->where('created_at <', date('Y-m-d H:i:s', strtotime("-{$auditDays} days")))],
            'Waiting list of finished events'  => ['waitlist', static fn ($b) => $b->where('event_id IN (SELECT id FROM ' . db_connect()->prefixTable('events') . " WHERE end_date < '{$now}')", null, false)],
            "Unverified accounts (>{$pendingDays} days, no tickets)" => ['users', static fn ($b) => $b
                ->where('status', 'inactive')
                ->where('role', 'client')
                ->where('created_at <', date('Y-m-d H:i:s', strtotime("-{$pendingDays} days")))
                ->where('id NOT IN (SELECT user_id FROM ' . db_connect()->prefixTable('tickets') . ')', null, false)],
        ];

        foreach ($rules as $label => [$table, $apply]) {
            if (! $db->tableExists($table)) {
                continue;
            }

            $count = $apply($db->table($db->prefixTable($table)))->countAllResults();

            if (! $dry && $count > 0) {
                $apply($db->table($db->prefixTable($table)))->delete();
            }

            CLI::write(sprintf('%-52s %d%s', $label, $count, $dry ? ' (dry run)' : ''), $count > 0 ? 'yellow' : 'green');
        }

        return EXIT_SUCCESS;
    }
}
