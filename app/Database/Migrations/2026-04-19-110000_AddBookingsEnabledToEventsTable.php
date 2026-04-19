<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBookingsEnabledToEventsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('events', [
            'bookings_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'after' => 'status',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('events', 'bookings_enabled');
    }
}
