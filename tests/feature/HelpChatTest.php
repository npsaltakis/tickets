<?php

use App\Models\EventModel;
use App\Models\TicketModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Help chat answers: guests get no booking data, signed-in users only ever see their own.
 * Runs on the MySQL test database, skipped otherwise.
 *
 * @internal
 */
final class HelpChatTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->db->DBDriver !== 'MySQLi') {
            $this->markTestSkipped('Help chat tests need a MySQL test database (database.tests.* env).');
        }
    }

    private function makeUser(): int
    {
        return (int) (new UserModel())->insert([
            'first_name' => 'Help',
            'last_name'  => 'Test',
            'email'      => 'help-' . bin2hex(random_bytes(4)) . '@example.test',
            'password'   => password_hash('secret-pass', PASSWORD_DEFAULT),
            'role'       => 'client',
            'status'     => 'active',
        ], true);
    }

    private function bookEvent(int $userId, string $title): void
    {
        $eventId = (int) (new EventModel())->insert([
            'title'      => $title,
            'slug'       => 'help-' . bin2hex(random_bytes(4)),
            'location'   => 'Athens',
            'start_date' => date('Y-m-d H:i:s', strtotime('+5 days')),
            'end_date'   => date('Y-m-d H:i:s', strtotime('+6 days')),
            'capacity'   => 10,
            'event_type' => 'free',
            'status'     => 'active',
        ], true);

        (new TicketModel())->insert([
            'event_id'       => $eventId,
            'user_id'        => $userId,
            'ticket_code'    => strtoupper(bin2hex(random_bytes(6))),
            'status'         => 'valid',
            'payment_status' => 'free',
        ]);
    }

    private function session(int $userId): array
    {
        return ['is_logged_in' => true, 'user_id' => $userId, 'user_role' => 'client'];
    }

    public function testGuestsGetNoBookingData(): void
    {
        $menu = json_decode((string) $this->get('help/menu')->response()->getBody(), true);
        $this->assertFalse($menu['signedIn']);
        $this->assertNotContains('my_tickets', array_column($menu['questions'], 'id'));

        $answer = json_decode((string) $this->get('help/answer?q=my_tickets')->response()->getBody(), true);
        $this->assertSame([], $answer['items']);
        $this->assertSame(base_url('login'), $answer['links'][0]['url']);
    }

    public function testUsersSeeOnlyTheirOwnBookings(): void
    {
        $alice = $this->makeUser();
        $bob   = $this->makeUser();
        $this->bookEvent($alice, 'Alice Concert');
        $this->bookEvent($bob, 'Bob Theatre');

        $result = $this->withSession($this->session($alice))->get('help/answer?q=my_tickets');
        $result->assertStatus(200);
        $answer = json_decode((string) $result->response()->getBody(), true);

        $titles = array_column($answer['items'], 'title');
        $this->assertContains('Alice Concert', $titles);
        $this->assertNotContains('Bob Theatre', $titles);
    }

    public function testUnknownQuestionIsNotFound(): void
    {
        $this->get('help/answer?q=drop-table')->assertStatus(404);
    }
}
