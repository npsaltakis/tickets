<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInfoFieldsToEventsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('events', [
            'info_phone' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'address',
            ],
            'info_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'info_phone',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('events', 'info_url');
        $this->forge->dropColumn('events', 'info_phone');
    }
}
