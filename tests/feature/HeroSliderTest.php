<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Home page hero slider and its admin page. Runs on the MySQL test database, skipped otherwise.
 *
 * @internal
 */
final class HeroSliderTest extends CIUnitTestCase
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
            $this->markTestSkipped('Hero slider tests need a MySQL test database (database.tests.* env).');
        }

        $filters = new \Config\Filters();
        $filters->globals['before'] = array_filter($filters->globals['before'], static fn ($v) => $v !== 'csrf');
        \CodeIgniter\Config\Factories::injectMock('config', 'Filters', $filters);

        $this->db->table('hero_slides')->truncate();
    }

    private function admin(): array
    {
        return ['is_logged_in' => true, 'user_id' => 1, 'user_role' => 'admin', 'user_name' => 'T', 'user_email' => 't@example.test'];
    }

    public function testAdminPageRendersForAdminOnly(): void
    {
        $this->withSession($this->admin())->get('admin/hero-slides')->assertStatus(200);

        $result = $this->withSession(['is_logged_in' => true, 'user_id' => 2, 'user_role' => 'user'])->get('admin/hero-slides');
        $result->assertRedirect();
    }

    public function testImageSlideNeedsAnImage(): void
    {
        $this->withSession($this->admin())->post('admin/hero-slides', ['type' => 'image', 'title' => 'X', 'is_active' => '1']);

        $this->assertSame(0, $this->db->table('hero_slides')->countAllResults());
    }

    public function testEventSlideNeedsAnEvent(): void
    {
        $this->withSession($this->admin())->post('admin/hero-slides', ['type' => 'event', 'event_id' => '999999', 'is_active' => '1']);

        $this->assertSame(0, $this->db->table('hero_slides')->countAllResults());
    }

    public function testActiveImageSlidesShowOnHome(): void
    {
        $this->db->table('hero_slides')->insertBatch([
            ['type' => 'image', 'image' => 'assets/images/hero/a.jpg', 'title' => 'Summer Festival', 'subtitle' => 'Three nights', 'link_url' => '/about', 'button_text' => 'Info', 'sort_order' => 1, 'is_active' => 1],
            ['type' => 'image', 'image' => 'assets/images/hero/b.jpg', 'title' => 'Hidden Slide', 'subtitle' => '', 'link_url' => '', 'button_text' => '', 'sort_order' => 2, 'is_active' => 0],
        ]);

        $result = $this->get('/');
        $result->assertStatus(200);
        $result->assertSee('Summer Festival');
        $result->assertSee('Three nights');
        $result->assertSee(base_url('about'));
        $result->assertDontSee('Hidden Slide');
    }
}
