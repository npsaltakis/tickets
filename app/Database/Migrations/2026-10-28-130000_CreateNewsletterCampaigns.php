<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One row per newsletter send, so the admin can follow sent / pending / failed per recipient.
 */
class CreateNewsletterCampaigns extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'subject'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'event_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'recipients' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('newsletter_campaigns');

        $this->forge->addColumn('email_queue', [
            'campaign_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'id'],
        ]);
        $this->forge->addKey('campaign_id', false, false, 'email_queue_campaign_id');
        $this->forge->processIndexes('email_queue');
    }

    public function down(): void
    {
        $this->forge->dropKey('email_queue', 'email_queue_campaign_id', false);
        $this->forge->dropColumn('email_queue', 'campaign_id');
        $this->forge->dropTable('newsletter_campaigns', true);
    }
}
