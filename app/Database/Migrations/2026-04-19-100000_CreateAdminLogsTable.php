<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminLogsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'target_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'admin_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'admin_email' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
            ],
            'context' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('action');
        $this->forge->addKey('target_type');
        $this->forge->addKey('admin_id');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('admin_id', 'users', 'id', 'CASCADE', 'SET NULL');
        $this->forge->createTable('admin_logs');
    }

    public function down()
    {
        $this->forge->dropTable('admin_logs', true);
    }
}
