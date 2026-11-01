<?php

namespace App\Libraries;

use App\Models\FaqModel;
use App\Models\WaitlistModel;
use Throwable;

/**
 * The help chat: a fixed menu of questions. Questions about the visitor's own bookings are answered from the
 * database for the signed-in user only; general questions come from the faqs table (Admin → Help chat).
 * No free text and no AI, so nothing leaves the server.
 */
class HelpAssistant
{
    /**
     * Built-in questions answered from the visitor's own data.
     */
    private const PERSONAL = ['my_tickets', 'next_event', 'my_qr', 'my_waitlist', 'my_donations'];

    /**
     * Longest list shown in one answer; the rest is a link away ("My Tickets").
     */
    private const MAX_ITEMS = 5;

    /**
     * Questions to offer this visitor.
     *
     * @return list<array{id: string, label: string}>
     */
    public static function menu(?int $userId, string $locale): array
    {
        $menu = [];

        if ($userId !== null) {
            foreach (self::PERSONAL as $key) {
                if ($key === 'my_waitlist' && ! WaitlistModel::isEnabled()) {
                    continue;
                }
                $menu[] = ['id' => $key, 'label' => lang('App.helpQ_' . $key)];
            }
        } else {
            $menu[] = ['id' => 'sign_in', 'label' => lang('App.helpQ_sign_in')];
        }

        foreach (self::faqs($locale) as $faq) {
            $menu[] = ['id' => 'faq:' . (int) $faq['id'], 'label' => (string) $faq['question']];
        }

        return $menu;
    }

    /**
     * The answer to one menu question: text, optional list items and links (rendered as text by the page, never as HTML).
     *
     * @return array{question: string, text: string, items: list<array{title: string, meta: string, url: string}>, links: list<array{label: string, url: string}>}|null
     */
    public static function answer(string $id, ?int $userId, string $locale): ?array
    {
        if (str_starts_with($id, 'faq:')) {
            return self::faqAnswer((int) substr($id, 4), $locale);
        }

        if ($id === 'sign_in' || ($userId === null && in_array($id, self::PERSONAL, true))) {
            return self::reply(lang('App.helpQ_sign_in'), lang('App.helpA_sign_in'), [], [['label' => lang('App.helpLinkLogin'), 'url' => base_url('login')]]);
        }

        return match ($id) {
            'my_tickets'   => self::myTickets((int) $userId),
            'next_event'   => self::nextEvent((int) $userId),
            'my_qr'        => self::reply(lang('App.helpQ_my_qr'), lang('App.helpA_my_qr'), [], [['label' => lang('App.helpLinkMyTickets'), 'url' => base_url('my-events')]]),
            'my_waitlist'  => WaitlistModel::isEnabled() ? self::myWaitlist((int) $userId) : null,
            'my_donations' => self::myDonations((int) $userId),
            default        => null,
        };
    }

    /**
     * Valid tickets of the user, one row per event, soonest first.
     *
     * @return list<array<string, mixed>>
     */
    private static function bookings(int $userId, bool $upcomingOnly = false): array
    {
        $db      = db_connect();
        $builder = $db->table($db->prefixTable('tickets') . ' t')
            ->select('e.title, e.slug, e.location, e.start_date, e.end_date, COUNT(t.id) AS seats')
            ->join($db->prefixTable('events') . ' e', 'e.id = t.event_id')
            ->where('t.user_id', $userId)
            ->where('t.status', 'valid')
            ->where('e.deleted_at', null)
            ->groupBy('e.id')
            ->orderBy('e.start_date', 'ASC');

        if ($upcomingOnly) {
            $builder->groupStart()->where('e.end_date >=', date('Y-m-d H:i:s'))->orWhere('e.end_date', null)->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    private static function myTickets(int $userId): array
    {
        $rows = self::bookings($userId);
        if ($rows === []) {
            return self::reply(lang('App.helpQ_my_tickets'), lang('App.helpA_no_tickets'), [], [['label' => lang('App.helpLinkEvents'), 'url' => base_url('/')]]);
        }

        $now      = time();
        $upcoming = array_filter($rows, static fn (array $r): bool => empty($r['end_date']) || strtotime((string) $r['end_date']) >= $now);
        $items    = array_map(static fn (array $r): array => self::eventItem($r), array_slice(array_reverse($rows), 0, self::MAX_ITEMS));
        $text     = $upcoming === []
            ? lang('App.helpA_my_tickets_past', [count($rows)])
            : lang('App.helpA_my_tickets', [count($rows), count($upcoming)]);

        return self::reply(lang('App.helpQ_my_tickets'), $text, $items, [['label' => lang('App.helpLinkMyTickets'), 'url' => base_url('my-events')]]);
    }

    private static function nextEvent(int $userId): array
    {
        $rows = self::bookings($userId, true);
        if ($rows === []) {
            return self::reply(lang('App.helpQ_next_event'), lang('App.helpA_no_upcoming'), [], [['label' => lang('App.helpLinkEvents'), 'url' => base_url('/')]]);
        }

        $next = $rows[0];

        return self::reply(
            lang('App.helpQ_next_event'),
            lang('App.helpA_next_event', [(string) $next['title']]),
            [self::eventItem($next)],
            [['label' => lang('App.helpLinkMyTickets'), 'url' => base_url('my-events')]]
        );
    }

    private static function myWaitlist(int $userId): array
    {
        $db   = db_connect();
        $rows = $db->table($db->prefixTable('waitlist') . ' w')
            ->select('e.title, e.slug, e.location, e.start_date, e.end_date, w.notified_at')
            ->join($db->prefixTable('events') . ' e', 'e.id = w.event_id')
            ->where('w.user_id', $userId)
            ->where('e.deleted_at', null)
            ->groupStart()->where('e.end_date >=', date('Y-m-d H:i:s'))->orWhere('e.end_date', null)->groupEnd()
            ->orderBy('e.start_date', 'ASC')
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return self::reply(lang('App.helpQ_my_waitlist'), lang('App.helpA_no_waitlist'));
        }

        return self::reply(lang('App.helpQ_my_waitlist'), lang('App.helpA_my_waitlist', [count($rows)]), array_map(static fn (array $r): array => self::eventItem($r), $rows));
    }

    private static function myDonations(int $userId): array
    {
        $db   = db_connect();
        $rows = $db->table($db->prefixTable('tickets') . ' t')
            ->select('t.ticket_code, t.donation_amount, e.title')
            ->join($db->prefixTable('events') . ' e', 'e.id = t.event_id')
            ->where('t.user_id', $userId)
            ->where('t.status', 'valid')
            ->where('t.payment_status', 'paid')
            ->where('t.donation_amount >', 0)
            ->orderBy('t.created_at', 'DESC')
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return self::reply(lang('App.helpQ_my_donations'), lang('App.helpA_no_donations'));
        }

        $total = array_sum(array_map(static fn (array $r): float => (float) $r['donation_amount'], $rows));
        $items = array_map(static fn (array $r): array => [
            'title' => (string) $r['title'],
            'meta'  => SiteSettings::money((float) $r['donation_amount']) . ' · ' . lang('App.helpReceipt'),
            'url'   => base_url('my-events/tickets/' . rawurlencode((string) $r['ticket_code']) . '/receipt'),
        ], array_slice($rows, 0, self::MAX_ITEMS));

        return self::reply(
            lang('App.helpQ_my_donations'),
            lang('App.helpA_my_donations', [SiteSettings::money($total), count($rows)]),
            $items,
            [['label' => lang('App.helpLinkMyTickets'), 'url' => base_url('my-events')]]
        );
    }

    private static function faqAnswer(int $faqId, string $locale): ?array
    {
        foreach (self::faqs($locale) as $faq) {
            if ((int) $faq['id'] !== $faqId) {
                continue;
            }

            $links = [];
            $url   = self::link((string) ($faq['link_url'] ?? ''));
            if ($url !== '') {
                $links[] = ['label' => trim((string) ($faq['link_label'] ?? '')) ?: lang('App.helpLinkMore'), 'url' => $url];
            }

            return self::reply((string) $faq['question'], (string) $faq['answer'], [], $links);
        }

        return null;
    }

    /**
     * Active general questions in the visitor's language (falls back to Greek when none exist for it).
     *
     * @return list<array<string, mixed>>
     */
    private static function faqs(string $locale): array
    {
        try {
            if (! db_connect()->tableExists('faqs')) {
                return [];
            }

            $model = new FaqModel();
            $rows  = $model->where('locale', $locale)->where('is_active', 1)->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();
            if ($rows === [] && $locale !== 'el') {
                $rows = $model->where('locale', 'el')->where('is_active', 1)->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();
            }

            return $rows;
        } catch (Throwable) {
            return [];
        }
    }

    private static function eventItem(array $row): array
    {
        $meta = array_filter([
            ! empty($row['start_date']) ? date('d/m/Y H:i', strtotime((string) $row['start_date'])) : '',
            (string) ($row['location'] ?? ''),
            isset($row['seats']) ? lang('App.helpSeats', [(int) $row['seats']]) : '',
        ]);

        return [
            'title' => (string) $row['title'],
            'meta'  => implode(' · ', $meta),
            'url'   => base_url('events/' . $row['slug']),
        ];
    }

    /**
     * A link from the admin: site paths (/contact) become full URLs; anything else must be http(s).
     */
    private static function link(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '/')) {
            return base_url(ltrim($url, '/'));
        }

        return preg_match('#^https?://#i', $url) === 1 ? $url : '';
    }

    private static function reply(string $question, string $text, array $items = [], array $links = []): array
    {
        return ['question' => $question, 'text' => $text, 'items' => array_values($items), 'links' => $links];
    }
}
