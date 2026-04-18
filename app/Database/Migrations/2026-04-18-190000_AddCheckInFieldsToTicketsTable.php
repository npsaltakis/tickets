<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCheckInFieldsToTicketsTable extends Migration
{
    public function up()
    {
        $ticketsTable = $this->db->prefixTable('tickets');
        $usersTable = $this->db->prefixTable('users');

        $this->db->query("
            ALTER TABLE {$ticketsTable}
            ADD COLUMN checked_in_at DATETIME NULL AFTER status,
            ADD COLUMN checked_in_by INT(11) UNSIGNED NULL AFTER checked_in_at,
            ADD KEY tickets_checked_in_by_index (checked_in_by),
            ADD CONSTRAINT tickets_checked_in_by_foreign
                FOREIGN KEY (checked_in_by) REFERENCES {$usersTable}(id)
                ON DELETE SET NULL
                ON UPDATE CASCADE
        ");
    }

    public function down()
    {
        $ticketsTable = $this->db->prefixTable('tickets');

        $this->db->query("
            ALTER TABLE {$ticketsTable}
            DROP FOREIGN KEY tickets_checked_in_by_foreign,
            DROP INDEX tickets_checked_in_by_index,
            DROP COLUMN checked_in_by,
            DROP COLUMN checked_in_at
        ");
    }
}
