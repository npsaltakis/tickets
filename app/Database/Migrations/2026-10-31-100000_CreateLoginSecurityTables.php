<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-IP login attempt log, blocked IPs (automatic after repeated failures, or manual) and
 * whitelisted IPs that are never blocked. Managed from Admin → Security.
 */
class CreateLoginSecurityTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ip_address'         => ['type' => 'VARCHAR', 'constraint' => 45],
            'username_attempted' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'success'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'attempted_at'       => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['ip_address', 'attempted_at']);
        $this->forge->createTable('login_attempts', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'reason'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'blocked_by' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'system'],
            'blocked_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('ip_address');
        $this->forge->createTable('blocked_ips', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45],
            'note'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'added_by'   => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'admin'],
            'added_at'   => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('ip_address');
        $this->forge->createTable('whitelisted_ips', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('login_attempts', true);
        $this->forge->dropTable('blocked_ips', true);
        $this->forge->dropTable('whitelisted_ips', true);
    }
}
