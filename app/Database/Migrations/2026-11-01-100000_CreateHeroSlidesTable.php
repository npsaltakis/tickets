<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Slides of the home page hero slider: an uploaded image with its own text and link, or an event.
 * With no active slides the slider shows the next events automatically.
 */
class CreateHeroSlidesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'type'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'image'],
            'event_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'image'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'subtitle'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'link_url'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'button_text' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['is_active', 'sort_order']);
        $this->forge->createTable('hero_slides');
    }

    public function down(): void
    {
        $this->forge->dropTable('hero_slides', true);
    }
}
