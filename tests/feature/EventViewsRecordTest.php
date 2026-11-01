<?php

use App\Libraries\EventViews;
use App\Models\EventModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Event page visits are counted once per visitor and day, admins and bots are left out.
 * Runs on the MySQL test database (ON DUPLICATE KEY), skipped otherwise.
 *
 * @internal
 */
final class EventViewsRecordTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace   = 'App';

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->db->DBDriver !== 'MySQLi') {
            $this->markTestSkipped('Event view tests need a MySQL test database (database.tests.* env).');
        }

        $this->db->table('event_views')->truncate();
    }

    private function makeEvent(): array
    {
        $id = (int) (new EventModel())->insert([
            'title'      => 'Views test ' . bin2hex(random_bytes(3)),
            'slug'       => 'views-test-' . bin2hex(random_bytes(3)),
            'location'   => 'Athens',
            'start_date' => date('Y-m-d H:i:s', strtotime('+10 days')),
            'end_date'   => date('Y-m-d H:i:s', strtotime('+11 days')),
            'capacity'   => 10,
            'event_type' => 'free',
            'status'     => 'active',
        ], true);

        return (new EventModel())->find($id);
    }

    private function visit(array $event, string $agent = self::BROWSER, array $session = [], string $query = ''): void
    {
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $this->withSession($session)->get('events/' . $event['slug'] . $query)->assertStatus(200);
    }

    public function testRepeatVisitsSameDayCountOneVisitor(): void
    {
        $event = $this->makeEvent();

        $this->visit($event, self::BROWSER, [], '?utm_source=FB');
        $this->visit($event);

        $rows = $this->db->table('event_views')->where('event_id', (int) $event['id'])->get()->getResultArray();
        $this->assertCount(1, $rows);
        $this->assertSame(2, (int) $rows[0]['hits']);
        $this->assertSame('facebook', $rows[0]['source']);

        $stats = EventViews::perEvent();
        $this->assertSame(1, $stats[(int) $event['id']]['visitors']);
        $this->assertSame(2, $stats[(int) $event['id']]['views']);
    }

    public function testBotsAndAdminsAreNotCounted(): void
    {
        $event = $this->makeEvent();

        $this->visit($event, 'Mozilla/5.0 (compatible; Googlebot/2.1)');
        $this->visit($event, self::BROWSER, ['is_logged_in' => true, 'user_id' => 1, 'user_role' => 'admin']);

        $this->assertSame(0, $this->db->table('event_views')->where('event_id', (int) $event['id'])->countAllResults());
    }
}
