<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Visits to event pages: one row per visitor, event and day (repeat visits that day add to `hits`).
 * No cookies and no IP addresses: the visitor is a hash with a salt that changes every day,
 * so the same person cannot be followed from one day to the next.
 */
class CreateEventViewsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'event_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'view_date'    => ['type' => 'DATE'],
            'visitor_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'source'       => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'direct'],
            'hits'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['event_id', 'view_date', 'visitor_hash']);
        $this->forge->addKey('view_date');
        $this->forge->createTable('event_views');
    }

    public function down(): void
    {
        $this->forge->dropTable('event_views', true);
    }
}
