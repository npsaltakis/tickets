<?php

use App\Libraries\BookingService;
use App\Models\DiscountCodeModel;
use App\Models\EventModel;
use App\Models\PaymentModel;
use App\Models\TicketModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Booking rules against a real MySQL database (row locking is MySQL specific).
 * Point phpunit at a throw-away database with database.tests.* env vars, otherwise these tests are skipped.
 *
 * @internal
 */
final class BookingFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->db->DBDriver !== 'MySQLi') {
            $this->markTestSkipped('Booking flow tests need a MySQL test database (database.tests.* env).');
        }

        $this->service = new BookingService();
    }

    private function makeUser(string $email): int
    {
        return (int) (new UserModel())->insert([
            'first_name' => 'Test',
            'last_name'  => 'User',
            'email'      => $email,
            'password'   => password_hash('secret-pass', PASSWORD_DEFAULT),
            'role'       => 'client',
            'status'     => 'active',
        ], true);
    }

    private function makeEvent(int $capacity, string $type = 'free'): array
    {
        $id = (int) (new EventModel())->insert([
            'title'        => 'Test event ' . bin2hex(random_bytes(3)),
            'slug'         => 'test-event-' . bin2hex(random_bytes(3)),
            'location'     => 'Athens',
            'start_date'   => date('Y-m-d H:i:s', strtotime('+10 days')),
            'end_date'     => date('Y-m-d H:i:s', strtotime('+11 days')),
            'capacity'     => $capacity,
            'event_type'   => $type,
            'min_donation' => $type === 'donation' ? 10 : 0,
            'status'       => 'active',
        ], true);

        return (new EventModel())->find($id);
    }

    public function testFreeBookingNeverExceedsCapacity(): void
    {
        $event = $this->makeEvent(3);
        $a     = $this->makeUser('a@example.test');
        $b     = $this->makeUser('b@example.test');

        $this->assertSame('created', $this->service->bookFree($event, $a, 2)['status']);
        $this->assertSame('full', $this->service->bookFree($event, $b, 2)['status']);
        $this->assertSame('created', $this->service->bookFree($event, $b, 1)['status']);
        $this->assertSame(0, $this->service->remainingSeats($event));
    }

    public function testPerUserSeatLimit(): void
    {
        $event = $this->makeEvent(100);
        $user  = $this->makeUser('limit@example.test');
        $max   = BookingService::maxSeatsPerUser();

        $this->assertSame('limit', $this->service->bookFree($event, $user, $max + 1)['status']);
        $this->assertSame('created', $this->service->bookFree($event, $user, $max)['status']);
        $this->assertSame('limit', $this->service->bookFree($event, $user, 1)['status']);
    }

    public function testCancelFreesTheSeat(): void
    {
        $event  = $this->makeEvent(1);
        $user   = $this->makeUser('cancel@example.test');
        $result = $this->service->bookFree($event, $user, 1);
        $ticket = (new TicketModel())->where('ticket_code', $result['codes'][0])->first();

        $this->assertSame(0, $this->service->remainingSeats($event));

        $cancel = $this->service->cancelTicket((int) $ticket['id']);

        $this->assertTrue($cancel['ok']);
        $this->assertSame(1, $this->service->remainingSeats($event));
        $this->assertSame('not_valid', $this->service->cancelTicket((int) $ticket['id'])['error']);
    }

    public function testCheckedInTicketCannotBeCancelled(): void
    {
        $event  = $this->makeEvent(2);
        $user   = $this->makeUser('checked@example.test');
        $result = $this->service->bookFree($event, $user, 1);
        $ticket = (new TicketModel())->where('ticket_code', $result['codes'][0])->first();

        (new TicketModel())->update((int) $ticket['id'], ['checked_in_at' => date('Y-m-d H:i:s')]);

        $this->assertSame('checked_in', $this->service->cancelTicket((int) $ticket['id'])['error']);
    }

    public function testPaidCaptureIsIdempotentAndAppliesDiscount(): void
    {
        $event = $this->makeEvent(10, 'donation');
        $user  = $this->makeUser('paid@example.test');

        $discounts = new DiscountCodeModel();
        $discounts->insert(['code' => 'HALF', 'type' => 'percent', 'value' => 50, 'is_active' => 1, 'max_uses' => 1]);

        $capture = ['id' => 'CAPTURE-1', 'amount' => ['value' => '10.00', 'currency_code' => 'EUR']];
        $booking = ['event_id' => (int) $event['id'], 'user_id' => $user, 'seats' => 1, 'donation' => 20.0, 'code' => 'HALF'];

        $first = $this->service->fulfillCapture($capture, $booking, $user, (int) $event['id']);
        $this->assertSame('created', $first['status']);
        $this->assertCount(1, $first['codes']);

        $again = $this->service->fulfillCapture($capture, $booking, $user, (int) $event['id']);
        $this->assertSame('duplicate', $again['status']);

        $this->assertSame(1, (new PaymentModel())->where('paypal_transaction_id', 'CAPTURE-1')->countAllResults());
        $this->assertSame(1, (int) $discounts->where('code', 'HALF')->first()['used_count']);
    }

    public function testCaptureWithWrongAmountIsRejected(): void
    {
        $event = $this->makeEvent(10, 'donation');
        $user  = $this->makeUser('tamper@example.test');

        $capture = ['id' => 'CAPTURE-2', 'amount' => ['value' => '1.00', 'currency_code' => 'EUR']];
        $booking = ['event_id' => (int) $event['id'], 'user_id' => $user, 'seats' => 1, 'donation' => 20.0];

        $this->assertSame('invalid', $this->service->fulfillCapture($capture, $booking, $user)['status']);
        $this->assertSame(10, $this->service->remainingSeats($event));
    }

    public function testCaptureForAnotherUserIsRejected(): void
    {
        $event = $this->makeEvent(10, 'donation');
        $owner = $this->makeUser('owner@example.test');
        $other = $this->makeUser('other@example.test');

        $capture = ['id' => 'CAPTURE-3', 'amount' => ['value' => '20.00', 'currency_code' => 'EUR']];
        $booking = ['event_id' => (int) $event['id'], 'user_id' => $owner, 'seats' => 1, 'donation' => 20.0];

        $this->assertSame('invalid', $this->service->fulfillCapture($capture, $booking, $other)['status']);
    }
}
