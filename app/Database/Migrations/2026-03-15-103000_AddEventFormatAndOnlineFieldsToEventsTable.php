<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventFormatAndOnlineFieldsToEventsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('events', [
            'event_format' => [
                'type' => 'ENUM',
                'constraint' => ['physical', 'online', 'hybrid'],
                'default' => 'physical',
                'after' => 'event_type',
            ],
            'online_url' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'after' => 'event_format',
            ],
            'online_access_notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'online_url',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('events', ['online_access_notes', 'online_url', 'event_format']);
    }
}
