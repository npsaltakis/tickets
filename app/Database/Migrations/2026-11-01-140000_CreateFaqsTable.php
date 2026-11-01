<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * General questions of the help chat, per language, editable in Admin → Help chat.
 * Questions about the visitor's own tickets are built in (App\Libraries\HelpAssistant) and answered from the database.
 */
class CreateFaqsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'el'],
            'question'   => ['type' => 'VARCHAR', 'constraint' => 191],
            'answer'     => ['type' => 'TEXT'],
            'link_url'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'link_label' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['locale', 'is_active', 'sort_order']);
        $this->forge->createTable('faqs');

        $now  = date('Y-m-d H:i:s');
        $rows = [
            ['el', 'Πώς κλείνω θέση;', 'Άνοιξε την εκδήλωση που σε ενδιαφέρει, διάλεξε πόσες θέσεις θέλεις και πάτα «Κράτηση». Για να κλείσεις θέση πρέπει να έχεις συνδεθεί. Το εισιτήριο με τον κωδικό QR έρχεται στο email σου και φαίνεται και στα «Εισιτήριά μου».', '/', 'Δες τις εκδηλώσεις'],
            ['el', 'Πώς ακυρώνω μια κράτηση;', 'Γράψε μας από τη φόρμα επικοινωνίας με τον κωδικό του εισιτηρίου και θα την ακυρώσουμε. Αν έχεις κάνει δωρεά, θα σου πούμε και για την επιστροφή.', '/contact', 'Επικοινωνία'],
            ['el', 'Χρειάζεται να εκτυπώσω το εισιτήριο;', 'Όχι. Στην είσοδο δείχνεις τον κωδικό QR από το κινητό, από το email ή από τα «Εισιτήριά μου», που ανοίγουν και χωρίς internet αν έχεις εγκαταστήσει την εφαρμογή.', '/my-events', 'Τα εισιτήριά μου'],
            ['el', 'Πώς εγκαθιστώ την εφαρμογή;', 'Το site εγκαθίσταται σαν εφαρμογή στο κινητό ή στον υπολογιστή. Δες τις οδηγίες για Android, iPhone και υπολογιστή.', '/install-app', 'Οδηγίες εγκατάστασης'],
            ['el', 'Πώς επικοινωνώ μαζί σας;', 'Στείλε μας μήνυμα από τη φόρμα επικοινωνίας και θα σου απαντήσουμε το συντομότερο.', '/contact', 'Επικοινωνία'],
            ['en', 'How do I book a seat?', 'Open the event you like, choose how many seats you want and press "Book". You need to be signed in to book. The ticket with its QR code is emailed to you and also shown in "My Tickets".', '/', 'See the events'],
            ['en', 'How do I cancel a booking?', 'Write to us through the contact form with your ticket code and we will cancel it. If you made a donation, we will tell you about the refund too.', '/contact', 'Contact'],
            ['en', 'Do I need to print my ticket?', 'No. At the entrance you show the QR code on your phone, from the email or from "My Tickets", which also opens offline once the app is installed.', '/my-events', 'My Tickets'],
            ['en', 'How do I install the app?', 'The site installs as an app on your phone or computer. See the steps for Android, iPhone and computers.', '/install-app', 'Install instructions'],
            ['en', 'How can I contact you?', 'Send us a message through the contact form and we will get back to you as soon as possible.', '/contact', 'Contact'],
        ];

        $order = ['el' => 0, 'en' => 0];
        foreach ($rows as [$locale, $question, $answer, $link, $label]) {
            $this->db->table('faqs')->insert([
                'locale'     => $locale,
                'question'   => $question,
                'answer'     => $answer,
                'link_url'   => $link,
                'link_label' => $label,
                'sort_order' => ++$order[$locale],
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('faqs', true);
    }
}
