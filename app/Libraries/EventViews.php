<?php

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use Throwable;

/**
 * Counts visits to event pages for the admin reports, without cookies and without storing IP addresses.
 *
 * A visitor is sha256(daily salt + IP + user agent). The salt is random and replaced every day, so a hash
 * says "the same browser came back today" but cannot be linked to a person or to another day.
 */
class EventViews
{
    /**
     * Referrer hosts (and their subdomains) grouped into the sources shown in the reports.
     */
    private const REFERRERS = [
        'facebook'  => ['facebook.com', 'fb.com', 'fb.me', 'messenger.com'],
        'instagram' => ['instagram.com'],
        'google'    => ['google.com', 'google.gr'],
        'search'    => ['bing.com', 'duckduckgo.com', 'yahoo.com', 'ecosia.org', 'search.brave.com', 'yandex.com'],
        'x'         => ['t.co', 'twitter.com', 'x.com'],
        'tiktok'    => ['tiktok.com'],
        'youtube'   => ['youtube.com', 'youtu.be'],
        'linkedin'  => ['linkedin.com', 'lnkd.in'],
        'whatsapp'  => ['whatsapp.com', 'wa.me'],
        'viber'     => ['viber.com'],
        'email'     => ['mail.google.com', 'outlook.live.com', 'outlook.office.com', 'mail.yahoo.com'],
    ];

    /**
     * Short utm_source values people use, mapped to the same names.
     */
    private const UTM_ALIASES = ['fb' => 'facebook', 'ig' => 'instagram', 'twitter' => 'x', 'newsletter' => 'email', 'mail' => 'email'];

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|embedly|headless|lighthouse|monitor|curl|wget|python|httpclient|okhttp|go-http/i';

    /**
     * Records one visit to an event page; never throws, the page must open whatever happens here.
     */
    public static function record(int $eventId, IncomingRequest $request): void
    {
        try {
            if ($eventId < 1 || ! self::isCountable($request)) {
                return;
            }

            $db    = db_connect();
            $table = $db->prefixTable('event_views');
            $today = date('Y-m-d');
            $hash  = hash('sha256', self::dailySalt($today) . '|' . $request->getIPAddress() . '|' . self::userAgent($request));

            // The first visit of the day keeps its source; later ones only count as extra hits.
            $db->query(
                "INSERT INTO {$table} (event_id, view_date, visitor_hash, source, hits, created_at) VALUES (?, ?, ?, ?, 1, ?)
                 ON DUPLICATE KEY UPDATE hits = hits + 1",
                [$eventId, $today, $hash, self::source($request), date('Y-m-d H:i:s')]
            );
        } catch (Throwable $e) {
            log_message('error', 'Event view not recorded: ' . $e->getMessage());
        }
    }

    /**
     * Where the visitor came from: ?utm_source=..., else the referrer's site, else "direct".
     */
    public static function source(IncomingRequest $request): string
    {
        $utm = self::normalizeUtm((string) $request->getGet('utm_source'));
        if ($utm !== '') {
            return $utm;
        }

        return self::sourceFromReferrer($request->getHeaderLine('Referer'), (string) parse_url(base_url('/'), PHP_URL_HOST));
    }

    public static function normalizeUtm(string $value): string
    {
        $value = (string) preg_replace('/\s+/', '-', strtolower(trim($value)));
        $value = substr((string) preg_replace('/[^a-z0-9._-]/', '', $value), 0, 32);

        return self::UTM_ALIASES[$value] ?? $value;
    }

    public static function sourceFromReferrer(string $referrer, string $siteHost): string
    {
        $referrer = trim($referrer);
        if ($referrer === '') {
            return 'direct';
        }

        if (str_starts_with($referrer, 'android-app://')) {
            $app = strtolower((string) parse_url($referrer, PHP_URL_HOST));

            return match (true) {
                str_contains($app, 'facebook') => 'facebook',
                str_contains($app, 'instagram') => 'instagram',
                str_contains($app, 'viber') => 'viber',
                str_contains($app, 'whatsapp') => 'whatsapp',
                str_contains($app, 'google') => 'google',
                default => 'other',
            };
        }

        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        if ($host === '') {
            return 'direct';
        }
        if ($siteHost !== '' && ($host === strtolower($siteHost) || $host === 'www.' . strtolower($siteHost))) {
            return 'site';
        }

        // Webmail first: mail.google.com would otherwise count as Google.
        foreach (['email', ...array_keys(self::REFERRERS)] as $source) {
            foreach (self::REFERRERS[$source] as $domain) {
                if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                    return $source;
                }
            }
        }

        // Any other Google country domain (google.de, google.co.uk, ...).
        if (preg_match('/(^|\.)google\.[a-z.]+$/', $host) === 1) {
            return 'google';
        }

        return 'other';
    }

    /**
     * Per event: page views, visitors (unique per day), people who booked since visits were first counted,
     * conversion (bookers / visitors) and the source most visitors came from.
     *
     * @return array<int, array{views: int, visitors: int, bookers: int, conversion: ?float, top_source: string}>
     */
    public static function perEvent(?string $since = null): array
    {
        $db = db_connect();
        if (! $db->tableExists('event_views')) {
            return [];
        }

        $views   = $db->prefixTable('event_views');
        $tickets = $db->prefixTable('tickets');
        $where   = $since !== null ? 'WHERE view_date >= ' . $db->escape($since) : '';

        $stats = [];
        foreach ($db->query("SELECT event_id, SUM(hits) AS views, COUNT(*) AS visitors FROM {$views} {$where} GROUP BY event_id")->getResultArray() as $row) {
            $stats[(int) $row['event_id']] = ['views' => (int) $row['views'], 'visitors' => (int) $row['visitors'], 'bookers' => 0, 'conversion' => null, 'top_source' => ''];
        }
        if ($stats === []) {
            return [];
        }

        // Only bookings made since the event's first counted visit, so older bookings do not inflate the rate.
        $bookers = $db->query(
            "SELECT t.event_id, COUNT(DISTINCT t.user_id) AS bookers
             FROM {$tickets} t
             JOIN (SELECT event_id, MIN(view_date) AS first_day FROM {$views} {$where} GROUP BY event_id) v ON v.event_id = t.event_id
             WHERE t.status = 'valid' AND t.created_at >= v.first_day
             GROUP BY t.event_id"
        )->getResultArray();
        foreach ($bookers as $row) {
            if (isset($stats[(int) $row['event_id']])) {
                $stats[(int) $row['event_id']]['bookers'] = (int) $row['bookers'];
            }
        }

        $best = [];
        foreach ($db->query("SELECT event_id, source, COUNT(*) AS visitors FROM {$views} {$where} GROUP BY event_id, source")->getResultArray() as $row) {
            $id = (int) $row['event_id'];
            if (! isset($best[$id]) || (int) $row['visitors'] > $best[$id]) {
                $best[$id]                  = (int) $row['visitors'];
                $stats[$id]['top_source'] = (string) $row['source'];
            }
        }

        foreach ($stats as $id => $row) {
            $stats[$id]['conversion'] = $row['visitors'] > 0 ? round(min(100, $row['bookers'] / $row['visitors'] * 100), 1) : null;
        }

        return $stats;
    }

    /**
     * Visitors and views per day for the last $days days (oldest first, days without visits as 0).
     *
     * @return array{labels: list<string>, visitors: list<int>, views: list<int>}
     */
    public static function daily(int $days = 30): array
    {
        $db    = db_connect();
        $since = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $map   = [];

        if ($db->tableExists('event_views')) {
            $rows = $db->query('SELECT view_date, COUNT(*) AS visitors, SUM(hits) AS views FROM ' . $db->prefixTable('event_views') . ' WHERE view_date >= ? GROUP BY view_date', [$since])->getResultArray();
            foreach ($rows as $row) {
                $map[(string) $row['view_date']] = [(int) $row['visitors'], (int) $row['views']];
            }
        }

        $out = ['labels' => [], 'visitors' => [], 'views' => []];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day               = date('Y-m-d', strtotime("-{$i} days"));
            $out['labels'][]   = date('d/m', strtotime($day));
            $out['visitors'][] = $map[$day][0] ?? 0;
            $out['views'][]    = $map[$day][1] ?? 0;
        }

        return $out;
    }

    /**
     * Visitors per source since a date, most first.
     *
     * @return array<string, int>
     */
    public static function sources(string $since): array
    {
        $db = db_connect();
        if (! $db->tableExists('event_views')) {
            return [];
        }

        $rows = $db->query('SELECT source, COUNT(*) AS visitors FROM ' . $db->prefixTable('event_views') . ' WHERE view_date >= ? GROUP BY source ORDER BY visitors DESC', [$since])->getResultArray();

        return array_combine(array_column($rows, 'source'), array_map('intval', array_column($rows, 'visitors'))) ?: [];
    }

    /**
     * Name of a source for the reports; utm_source values we do not know are shown as typed.
     */
    public static function label(string $source): string
    {
        $key  = 'App.viewSource' . str_replace(['.', '-', '_'], '', ucwords($source, '.-_'));
        $text = lang($key);

        return $text !== $key ? $text : $source;
    }

    public static function isBot(string $userAgent): bool
    {
        return trim($userAgent) === '' || preg_match(self::BOT_PATTERN, $userAgent) === 1;
    }

    private static function isCountable(IncomingRequest $request): bool
    {
        // The team's own visits are left out; so are bots and link previews / prefetches.
        $role = (string) session()->get('user_role');
        if (session()->get('is_logged_in') === true && in_array($role, ['admin', 'staff'], true)) {
            return false;
        }

        $purpose = strtolower($request->getHeaderLine('Sec-Purpose') . ' ' . $request->getHeaderLine('Purpose') . ' ' . $request->getHeaderLine('X-Moz'));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        return ! self::isBot(self::userAgent($request));
    }

    private static function userAgent(IncomingRequest $request): string
    {
        $agent = $request->getHeaderLine('User-Agent');

        return $agent !== '' ? $agent : $request->getUserAgent()->getAgentString();
    }

    private static function dailySalt(string $day): string
    {
        $cache = cache();
        $key   = 'event_views_salt_' . str_replace('-', '', $day);
        $salt  = $cache->get($key);

        if (! is_string($salt) || $salt === '') {
            $salt = bin2hex(random_bytes(32));
            $cache->save($key, $salt, 2 * DAY);
        }

        return $salt;
    }
}
