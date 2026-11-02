<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Who created each event, shown to admins in the events list and on the edit form.
 * Older events are filled in from the admin log (event_create / event_duplicate) where it still has them.
 */
class AddCreatedByToEventsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('events', [
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
        ]);

        if (! $this->db->tableExists('admin_logs')) {
            return;
        }

        $logs = $this->db->table('admin_logs')
            ->select('admin_id, context')
            ->whereIn('action', ['event_create', 'event_duplicate'])
            ->where('admin_id IS NOT NULL')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($logs as $log) {
            $context = json_decode((string) $log['context'], true);
            $eventId = (int) ($context['target_event_id'] ?? 0);
            if ($eventId > 0) {
                $this->db->table('events')
                    ->where('id', $eventId)
                    ->where('created_by IS NULL')
                    ->update(['created_by' => (int) $log['admin_id']]);
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropColumn('events', 'created_by');
    }
}
