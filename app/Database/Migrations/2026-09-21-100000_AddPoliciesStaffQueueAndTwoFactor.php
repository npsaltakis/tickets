<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPoliciesStaffQueueAndTwoFactor extends Migration
{
    public function up(): void
    {
        $users = $this->db->prefixTable('users');

        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query("ALTER TABLE `{$users}` MODIFY `role` ENUM('admin','staff','client') NOT NULL DEFAULT 'client'");
        }

        $this->forge->addColumn('users', [
            'totp_secret'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            'totp_enabled' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'to_email'   => ['type' => 'VARCHAR', 'constraint' => 191],
            'subject'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'body'       => ['type' => 'MEDIUMTEXT'],
            'attempts'   => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'last_error' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'sent_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['sent_at', 'attempts']);
        $this->forge->createTable('email_queue');

        if ($this->db->DBDriver === 'MySQLi') {
            // Only used when `session.driver = database` is enabled in .env.
            $sessions = $this->db->prefixTable('ci_sessions');
            $this->db->query("CREATE TABLE IF NOT EXISTS `{$sessions}` (
                `id` VARCHAR(128) NOT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                `data` BLOB NOT NULL,
                PRIMARY KEY (`id`),
                KEY `ci_sessions_timestamp` (`timestamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function down(): void
    {
        $users = $this->db->prefixTable('users');

        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query('DROP TABLE IF EXISTS `' . $this->db->prefixTable('ci_sessions') . '`');
            $this->db->query("UPDATE `{$users}` SET `role` = 'client' WHERE `role` = 'staff'");
            $this->db->query("ALTER TABLE `{$users}` MODIFY `role` ENUM('admin','client') NOT NULL DEFAULT 'client'");
        }

        $this->forge->dropTable('email_queue', true);
        $this->forge->dropColumn('users', ['totp_secret', 'totp_enabled']);
    }
}
