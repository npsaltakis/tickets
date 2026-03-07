<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStartEndDateToEventsTable extends Migration
{
    public function up()
    {
        $table = $this->db->prefixTable('events');

        $this->forge->addColumn('events', [
            'start_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'location',
            ],
            'end_date' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'start_date',
            ],
        ]);

        $this->db->query("UPDATE {$table} SET start_date = event_date WHERE start_date IS NULL");
        $this->db->query("UPDATE {$table} SET end_date = event_date WHERE end_date IS NULL");
    }

    public function down()
    {
        $this->forge->dropColumn('events', ['start_date', 'end_date']);
    }
}
