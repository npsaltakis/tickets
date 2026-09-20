<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class EventTextTest extends CIUnitTestCase
{
    private array $event = ['title' => 'Τίτλος', 'title_en' => 'Title', 'description' => 'Περιγραφή', 'description_en' => ''];

    protected function tearDown(): void
    {
        service('request')->setLocale('el');
        parent::tearDown();
    }

    public function testGreekVisitorsSeeDefaultText(): void
    {
        service('request')->setLocale('el');

        $this->assertSame('Τίτλος', event_text($this->event, 'title'));
    }

    public function testEnglishVisitorsSeeTranslationWithFallback(): void
    {
        service('request')->setLocale('en');

        $this->assertSame('Title', event_text($this->event, 'title'));
        $this->assertSame('Περιγραφή', event_text($this->event, 'description'));
    }
}