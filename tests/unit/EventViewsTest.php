<?php

use App\Libraries\EventViews;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Where an event page visitor came from, and who is not counted.
 *
 * @internal
 */
final class EventViewsTest extends CIUnitTestCase
{
    public function testReferrersAreGroupedBySource(): void
    {
        $cases = [
            ''                                       => 'direct',
            'https://example.org/events/x'           => 'site',
            'https://www.example.org/'               => 'site',
            'https://l.facebook.com/l.php?u=x'       => 'facebook',
            'https://m.facebook.com/'                => 'facebook',
            'https://l.instagram.com/?u=x'           => 'instagram',
            'https://www.google.gr/'                 => 'google',
            'https://www.google.co.uk/'              => 'google',
            'https://mail.google.com/mail/u/0/'      => 'email',
            'https://t.co/abc'                       => 'x',
            'https://duckduckgo.com/'                => 'search',
            'android-app://com.google.android.gm/'   => 'google',
            'android-app://com.instagram.android/'   => 'instagram',
            'https://some-blog.example.net/post'     => 'other',
        ];

        foreach ($cases as $referrer => $expected) {
            $this->assertSame($expected, EventViews::sourceFromReferrer($referrer, 'example.org'), "Referrer: {$referrer}");
        }
    }

    public function testUtmSourceIsCleanedAndAliased(): void
    {
        $this->assertSame('facebook', EventViews::normalizeUtm('FB'));
        $this->assertSame('email', EventViews::normalizeUtm('newsletter'));
        // Only a-z, 0-9, ".", "_" and "-" are kept.
        $this->assertSame('afisa-2026', EventViews::normalizeUtm(' Afisa 2026 '));
        $this->assertSame('ab', EventViews::normalizeUtm('<a>"b'));
        $this->assertSame('', EventViews::normalizeUtm(''));
        $this->assertSame(32, strlen(EventViews::normalizeUtm(str_repeat('a', 50))));
    }

    public function testBotsAreNotCounted(): void
    {
        $this->assertTrue(EventViews::isBot(''));
        $this->assertTrue(EventViews::isBot('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'));
        $this->assertTrue(EventViews::isBot('facebookexternalhit/1.1'));
        $this->assertTrue(EventViews::isBot('curl/8.4.0'));
        $this->assertFalse(EventViews::isBot('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1'));
    }
}
