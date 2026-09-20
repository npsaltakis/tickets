<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailChangesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'new_email'  => ['type' => 'VARCHAR', 'constraint' => 191],
            'selector'   => ['type' => 'VARCHAR', 'constraint' => 32],
            'token_hash' => ['type' => 'VARCHAR', 'constraint' => 64],
            'expires_at' => ['type' => 'DATETIME'],
            'used_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addUniqueKey('selector');
        $this->forge->createTable('email_changes');
    }

    public function down(): void
    {
        $this->forge->dropTable('email_changes', true);
    }
}