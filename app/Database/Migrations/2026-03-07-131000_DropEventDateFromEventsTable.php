<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropEventDateFromEventsTable extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('events', ['event_date']);
    }

    public function down()
    {
        $this->forge->addColumn('events', [
            'event_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'location',
            ],
        ]);

        $table = $this->db->prefixTable('events');
        $this->db->query("UPDATE {$table} SET event_date = start_date WHERE event_date IS NULL");
    }
}
