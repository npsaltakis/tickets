<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniquePayPalTransactionIdToPaymentsTable extends Migration
{
    public function up()
    {
        $this->forge->addUniqueKey('paypal_transaction_id', 'payments_paypal_transaction_id_unique');
        $this->forge->processIndexes('payments');
    }

    public function down()
    {
        $this->forge->dropKey('payments', 'payments_paypal_transaction_id_unique', true);
    }
}
