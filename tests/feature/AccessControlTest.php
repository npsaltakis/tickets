<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Route level access rules. Runs on the MySQL test database (see BookingFlowTest), skipped otherwise.
 *
 * @internal
 */
final class AccessControlTest extends CIUnitTestCase
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
            $this->markTestSkipped('Access control tests need a MySQL test database (database.tests.* env).');
        }

        // CSRF is covered by the framework; drop it so POST routes can be exercised here.
        $filters = new \Config\Filters();
        unset($filters->globals['before']['csrf']);
        $filters->globals['before'] = array_values(array_filter(
            $filters->globals['before'],
            static fn ($v) => $v !== 'csrf'
        ));
        \CodeIgniter\Config\Factories::injectMock('config', 'Filters', $filters);
    }

    private function session(string $role): array
    {
        return ['is_logged_in' => true, 'user_id' => 1, 'user_role' => $role, 'user_name' => 'T', 'user_email' => 't@example.test'];
    }

    private function assertBouncedHome(string $role, string $path): void
    {
        $result = $this->withSession($this->session($role))->get($path);

        $result->assertRedirect();
        $this->assertSame(base_url('/'), $result->getRedirectUrl(), "{$role} should be bounced from {$path}");
    }

    public function testPublicPagesRender(): void
    {
        foreach (['contact', 'about', 'gdpr', 'login', 'events/feed'] as $path) {
            $this->get($path)->assertStatus(200);
        }
    }

    public function testPrivateAreasRedirectGuests(): void
    {
        foreach (['my-events', 'profile', 'admin/dashboard', 'users', 'check-in', 'report'] as $path) {
            $this->get($path)->assertRedirect();
        }
    }

    public function testClientCannotOpenAdminOrCheckIn(): void
    {
        foreach (['admin/dashboard', 'users', 'check-in', 'admin/discount-codes'] as $path) {
            $this->assertBouncedHome('client', $path);
        }
    }

    public function testStaffCanCheckInButNotAdminAreas(): void
    {
        $this->withSession($this->session('staff'))->get('check-in')->assertStatus(200);
        $this->assertBouncedHome('staff', 'users');
        $this->assertBouncedHome('staff', 'admin/analytics');
        $this->assertBouncedHome('staff', 'check-in/export');
    }

    private function makeTwoFactorUser(): array
    {
        $secret = \App\Libraries\Totp::generateSecret();
        $model  = new \App\Models\UserModel();
        $id     = (int) $model->insert([
            'first_name'   => 'Two',
            'last_name'    => 'Factor',
            'email'        => 'twofa-' . bin2hex(random_bytes(4)) . '@example.test',
            'password'     => password_hash('secret-pass', PASSWORD_DEFAULT),
            'role'         => 'admin',
            'status'       => 'active',
            'totp_secret'  => $secret,
            'totp_enabled' => 1,
        ], true);

        return [$id, $secret];
    }

    public function testTwoFactorStepRequiresPendingLogin(): void
    {
        $result = $this->get('login/2fa');

        $result->assertRedirect();
        $this->assertSame(base_url('login'), $result->getRedirectUrl());
    }

    public function testTwoFactorAcceptsValidCodeAndCompletesLogin(): void
    {
        [$id, $secret] = $this->makeTwoFactorUser();

        $result = $this->withSession(['pending_2fa_user' => $id, 'pending_2fa_until' => time() + 300, 'pending_2fa_attempts' => 0])
            ->post('login/2fa', ['code' => \App\Libraries\Totp::code($secret)]);

        $result->assertRedirect();
        $this->assertSame(base_url('/'), $result->getRedirectUrl());
        $result->assertSessionHas('is_logged_in', true);
        $result->assertSessionHas('user_role', 'admin');
        $result->assertSessionMissing('pending_2fa_user');
    }

    public function testTwoFactorRejectsWrongCodeAndLocksAfterFiveTries(): void
    {
        [$id] = $this->makeTwoFactorUser();
        $session = ['pending_2fa_user' => $id, 'pending_2fa_until' => time() + 300, 'pending_2fa_attempts' => 0];

        $wrong = $this->withSession($session)->post('login/2fa', ['code' => '000000']);
        $wrong->assertSessionMissing('is_logged_in');

        $locked = $this->withSession(['pending_2fa_attempts' => 5] + $session)->post('login/2fa', ['code' => '000000']);
        $locked->assertRedirect();
        $this->assertSame(base_url('login'), $locked->getRedirectUrl());
        $locked->assertSessionMissing('pending_2fa_user');
    }

    private function makePrivateEvent(): array
    {
        $model = new \App\Models\EventModel();
        $id    = (int) $model->insert([
            'title'          => 'Secret Gala',
            'title_en'       => 'Secret Gala EN',
            'slug'           => 'secret-gala-' . bin2hex(random_bytes(3)),
            'location'       => 'Athens',
            'start_date'     => date('Y-m-d H:i:s', strtotime('+10 days')),
            'end_date'       => date('Y-m-d H:i:s', strtotime('+11 days')),
            'capacity'       => 20,
            'event_type'     => 'free',
            'status'         => 'active',
            'is_private'     => 1,
            'access_code'    => 'OPENSESAME',
        ], true);

        return $model->find($id);
    }

    public function testPrivateEventIsHiddenUntilCodeIsGiven(): void
    {
        $event = $this->makePrivateEvent();

        $gate = $this->get('events/' . $event['slug']);
        $gate->assertStatus(200);
        $this->assertStringNotContainsString('Secret Gala', $gate->getBody());

        $wrong = $this->get('events/' . $event['slug'], ['code' => 'nope']);
        $wrong->assertStatus(200);
        $this->assertStringNotContainsString('Secret Gala', $wrong->getBody());

        $viaLink = $this->get('events/' . $event['slug'], ['code' => 'OPENSESAME']);
        $viaLink->assertRedirect();

        $open = $this->withSession(['private_access_' . $event['id'] => true])->get('events/' . $event['slug']);
        $open->assertStatus(200);
        $this->assertStringContainsString('Secret Gala', $open->getBody());

    }

    public function testPrivateEventNeverAppearsInListingsOrSitemap(): void
    {
        $this->makePrivateEvent();

        $this->assertStringNotContainsString('Secret Gala', (string) $this->get('events/feed')->getBody());
        $this->assertStringNotContainsString('secret-gala', (string) $this->get('sitemap.xml')->getBody());
    }

    public function testCheckInSearchIsForStaffOnly(): void
    {
        $this->assertBouncedHome('client', 'check-in/search');

        $staff = $this->withSession($this->session('staff'))->get('check-in/search', ['q' => 'abc']);
        $staff->assertStatus(200);
        $staff->assertJSONFragment(['results' => []]);
    }

    public function testWebhookRejectsUnsignedRequests(): void
    {
        $this->withBody('{"event_type":"PAYMENT.CAPTURE.COMPLETED"}', 'json')
            ->post('paypal/webhook')
            ->assertStatus(401);
    }
}
