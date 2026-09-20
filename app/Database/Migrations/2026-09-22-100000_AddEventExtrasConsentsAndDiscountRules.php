<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventExtrasConsentsAndDiscountRules extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('events', [
            'is_private'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'access_code'   => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'default' => null],
            'title_en'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'description_en' => ['type' => 'TEXT', 'null' => true],
            'donation_goal' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true, 'default' => null],
        ]);

        $this->forge->addColumn('discount_codes', [
            'max_uses_per_user' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            'starts_at'         => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'capture_id' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['code_id', 'user_id']);
        $this->forge->createTable('discount_redemptions');

        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'event_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'terms_version' => ['type' => 'VARCHAR', 'constraint' => 20],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true, 'default' => null],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('event_id');
        $this->forge->createTable('consents');
    }

    public function down(): void
    {
        $this->forge->dropTable('consents', true);
        $this->forge->dropTable('discount_redemptions', true);
        $this->forge->dropColumn('discount_codes', ['max_uses_per_user', 'starts_at']);
        $this->forge->dropColumn('events', ['is_private', 'access_code', 'title_en', 'description_en', 'donation_goal']);
    }
}
