<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAddressToEventsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('events', [
            'address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'location',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('events', 'address');
    }
}
