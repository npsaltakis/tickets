<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Key/value site settings edited from Admin → Site settings (name, logo, colour, contact, company details).
 */
class CreateSettingsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'key'        => ['type' => 'VARCHAR', 'constraint' => 64],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('key', true);
        $this->forge->createTable('settings');
    }

    public function down(): void
    {
        $this->forge->dropTable('settings', true);
    }
}
