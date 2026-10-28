<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Edited content of the About / GDPR / Terms / Privacy pages, per language.
 * A page without a row keeps showing its built-in text.
 */
class CreatePagesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 32],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 8],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 191],
            'subtitle'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'body'       => ['type' => 'MEDIUMTEXT', 'null' => true],
            'updated_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['slug', 'locale']);
        $this->forge->createTable('pages');
    }

    public function down(): void
    {
        $this->forge->dropTable('pages', true);
    }
}
