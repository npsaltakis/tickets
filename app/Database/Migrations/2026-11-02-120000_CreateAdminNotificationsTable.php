<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Notifications for admins (new users, events created/cancelled/deleted, bookings, full events, contact messages),
 * shown under the bell in the admin topbar and in Admin → Notifications. See App\Libraries\AdminNotifications.
 * Read state is per admin: users.notifications_seen_at; anything newer is unread.
 */
class CreateAdminNotificationsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'data'       => ['type' => 'TEXT', 'null' => true],
            'link'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'actor_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at');
        $this->forge->createTable('admin_notifications');

        $this->forge->addColumn('users', [
            'notifications_seen_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'notifications_seen_at');
        $this->forge->dropTable('admin_notifications');
    }
}
