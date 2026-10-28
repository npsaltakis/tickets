<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Newsletter consent per user (off by default) and a token for the one-click unsubscribe link.
 */
class AddNewsletterToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'newsletter_opt_in'    => ['type' => 'TINYINT', 'constraint' => 1, 'unsigned' => true, 'default' => 0, 'after' => 'status'],
            'newsletter_opt_in_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'newsletter_opt_in'],
            'newsletter_token'     => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'newsletter_opt_in_at'],
        ]);
        $this->forge->addKey('newsletter_token', false, true, 'users_newsletter_token');
        $this->forge->processIndexes('users');
    }

    public function down(): void
    {
        $this->forge->dropKey('users', 'users_newsletter_token', false);
        $this->forge->dropColumn('users', ['newsletter_opt_in', 'newsletter_opt_in_at', 'newsletter_token']);
    }
}
