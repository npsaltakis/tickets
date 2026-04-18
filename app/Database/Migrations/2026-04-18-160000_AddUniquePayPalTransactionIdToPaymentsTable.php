<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniquePayPalTransactionIdToPaymentsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'paypal_transaction_id' => [
                'type' => 'VARCHAR',
                'constraint' => 191,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('paypal_transaction_id', 'paypal_captures_paypal_transaction_id_unique');
        $this->forge->createTable('paypal_captures');

        $paymentsTable = $this->db->prefixTable('payments');
        $capturesTable = $this->db->prefixTable('paypal_captures');

        $this->db->query(
            "INSERT IGNORE INTO {$capturesTable} (paypal_transaction_id, created_at, updated_at)
            SELECT DISTINCT paypal_transaction_id, NOW(), NOW()
            FROM {$paymentsTable}
            WHERE paypal_transaction_id IS NOT NULL AND paypal_transaction_id <> ''"
        );
    }

    public function down()
    {
        $this->forge->dropTable('paypal_captures');
    }
}
