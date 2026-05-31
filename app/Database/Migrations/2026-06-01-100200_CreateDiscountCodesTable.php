<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDiscountCodesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'        => ['type' => 'VARCHAR', 'constraint' => 32],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'type'        => ['type' => 'ENUM', 'constraint' => ['percent', 'fixed'], 'default' => 'percent'],
            'value'       => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'max_uses'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            'used_count'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'event_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            'expires_at'  => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('event_id');
        $this->forge->createTable('discount_codes');
    }

    public function down(): void
    {
        $this->forge->dropTable('discount_codes');
    }
}
