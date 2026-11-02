<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Notifications for each user (bookings, event changes and cancellations, waiting-list seats, received tickets,
 * reminders), shown under the bell in the site header and on /notifications. See App\Libraries\UserNotifications.
 */
class CreateUserNotificationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'data'       => ['type' => 'TEXT', 'null' => true],
            'link'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'read_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'read_at']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('user_notifications');
    }

    public function down(): void
    {
        $this->forge->dropTable('user_notifications');
    }
}
