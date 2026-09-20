<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRefundsWaitlistAndReminders extends Migration
{
    public function up(): void
    {
        $tickets  = $this->db->prefixTable('tickets');
        $payments = $this->db->prefixTable('payments');

        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query("ALTER TABLE `{$tickets}` MODIFY `payment_status` ENUM('pending','paid','free','failed','refunded') NOT NULL DEFAULT 'pending'");
        }

        $this->forge->addColumn('tickets', [
            'reminder_sent_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'cancelled_at'     => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);

        $this->forge->addColumn('payments', [
            'paypal_refund_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            'refunded_at'      => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'event_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'notified_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['event_id', 'user_id'], 'waitlist_event_user_unique');
        $this->forge->addForeignKey('event_id', 'events', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('waitlist');
    }

    public function down(): void
    {
        $tickets = $this->db->prefixTable('tickets');

        $this->forge->dropTable('waitlist', true);
        $this->forge->dropColumn('payments', ['paypal_refund_id', 'refunded_at']);
        $this->forge->dropColumn('tickets', ['reminder_sent_at', 'cancelled_at']);
        if ($this->db->DBDriver === 'MySQLi') {
            $this->db->query("UPDATE `{$tickets}` SET `payment_status` = 'failed' WHERE `payment_status` = 'refunded'");
            $this->db->query("ALTER TABLE `{$tickets}` MODIFY `payment_status` ENUM('pending','paid','free','failed') NOT NULL DEFAULT 'pending'");
        }
    }
}
