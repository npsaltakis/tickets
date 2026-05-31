<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Home extends EventBaseController
{
    public function index(): string
    {
        $batchSize = 12;
        [$events, $hasMore] = $this->fetchEventBatch('', 0, $batchSize);

        return view('events/index', [
            'events' => $events,
            'batchSize' => $batchSize,
            'hasMore' => $hasMore,
            'pageTitle' => 'All Events | Ticketing System',
            'metaDescription' => lang('App.eventsPageSubtitle'),
            'canonicalUrl' => base_url('/'),
        ]);
    }

    public function eventsFeed()
    {
        $query = trim((string) $this->request->getGet('q'));
        if ($query !== '' && mb_strlen($query, 'UTF-8') < 3) {
            $query = '';
        }

        $offset = max(0, (int) $this->request->getGet('offset'));
        $limit = max(1, min(24, (int) ($this->request->getGet('limit') ?? 12)));
        [$events, $hasMore] = $this->fetchEventBatch($query, $offset, $limit);

        return $this->response->setJSON([
            'html' => view('events/_event_cards', ['events' => $events]),
            'count' => count($events),
            'hasMore' => $hasMore,
            'nextOffset' => $offset + count($events),
        ]);
    }

    public function calendarFeed()
    {
        $month = max(1, min(12, (int) ($this->request->getGet('month') ?? (int) date('n'))));
        $year  = max(2020, min(2099, (int) ($this->request->getGet('year') ?? (int) date('Y'))));

        $from = sprintf('%04d-%02d-01 00:00:00', $year, $month);
        $to   = date('Y-m-t 23:59:59', mktime(0, 0, 0, $month, 1, $year));

        $builder = $this->eventModel->builder();
        $builder->where('deleted_at', null);

        if (! $this->isAdmin()) {
            $builder->where('status', 'active');
        }

        $builder
            ->groupStart()
                ->where('start_date <=', $to)
                ->where('end_date >=', $from)
                ->orGroupStart()
                    ->where('start_date >=', $from)
                    ->where('start_date <=', $to)
                ->groupEnd()
            ->groupEnd();

        $events = $builder->orderBy('start_date', 'ASC')->get()->getResultArray();

        $result = array_map(static fn ($e) => [
            'id'         => (int) ($e['id'] ?? 0),
            'title'      => (string) ($e['title'] ?? ''),
            'slug'       => (string) ($e['slug'] ?? ''),
            'status'     => (string) ($e['status'] ?? 'inactive'),
            'start_date' => (string) ($e['start_date'] ?? ''),
            'end_date'   => (string) ($e['end_date'] ?? ''),
            'event_type' => (string) ($e['event_type'] ?? 'free'),
        ], $events);

        return $this->response->setJSON([
            'events' => $result,
            'month'  => $month,
            'year'   => $year,
        ]);
    }

    public function show(string $slug): string
    {
        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        $isLoggedIn = session()->get('is_logged_in') === true;
        $isAdmin = $isLoggedIn && (string) session()->get('user_role') === 'admin';

        if ((string) ($event['status'] ?? '') !== 'active' && ! $isAdmin) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        $event['remaining_seats'] = $this->getRemainingSeats($event);
        $hasOnlineAccess = false;
        $userTicketCodes = [];

        if ($isAdmin) {
            $hasOnlineAccess = true;
        } elseif ($isLoggedIn) {
            $userTickets = $this->ticketModel
                ->select('ticket_code')
                ->where('event_id', (int) $event['id'])
                ->where('user_id', (int) session()->get('user_id'))
                ->where('status', 'valid')
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $userTicketCodes = array_values(array_filter(array_map(
                static fn(array $ticket): string => (string) ($ticket['ticket_code'] ?? ''),
                $userTickets
            )));

            $hasOnlineAccess = $userTicketCodes !== [];
        }

        $canonicalUrl = base_url('events/' . (string) $event['slug']);
        $metaDescription = $this->buildEventMetaDescription($event);
        $metaImage = $this->normalizeEventImageUrl((string) ($event['image'] ?? ''));

        return view('events/show', [
            'event' => $event,
            'pageTitle' => $this->buildEventSeoTitle($event),
            'metaDescription' => $metaDescription,
            'canonicalUrl' => $canonicalUrl,
            'metaImage' => $metaImage,
            'metaType' => 'event',
            'structuredData' => [
                $this->buildEventStructuredData($event, $canonicalUrl, $metaImage),
                $this->buildEventBreadcrumbStructuredData($event, $canonicalUrl),
            ],
            'paypalClientId' => $this->getPayPalClientId(),
            'hasOnlineAccess' => $hasOnlineAccess,
            'userTicketCodes' => $userTicketCodes,
        ]);
    }

    private function buildEventMetaDescription(array $event): string
    {
        $description = trim(strip_tags((string) ($event['description'] ?? '')));
        if ($description !== '') {
            return mb_substr(preg_replace('/\s+/', ' ', $description) ?? $description, 0, 155, 'UTF-8');
        }

        $parts = array_filter([
            (string) ($event['title'] ?? ''),
            ! empty($event['start_date']) ? date('d/m/Y H:i', strtotime((string) $event['start_date'])) : '',
            (string) ($event['location'] ?? ''),
        ]);

        return implode(' - ', $parts);
    }

    private function buildEventSeoTitle(array $event): string
    {
        $parts = array_filter([
            (string) ($event['title'] ?? ''),
            (string) ($event['location'] ?? ''),
            ! empty($event['start_date']) ? date('d/m/Y', strtotime((string) $event['start_date'])) : '',
        ]);

        return implode(' | ', $parts) . ' | ' . lang('App.siteTitle');
    }

    private function normalizeEventImageUrl(string $image): string
    {
        $image = trim($image);
        if ($image === '') {
            return '';
        }

        return preg_match('#^https?://#i', $image) ? $image : base_url(ltrim($image, '/'));
    }

    private function buildEventStructuredData(array $event, string $canonicalUrl, string $metaImage): array
    {
        $startDate = ! empty($event['start_date']) ? date(DATE_ATOM, strtotime((string) $event['start_date'])) : null;
        $endDate = ! empty($event['end_date']) ? date(DATE_ATOM, strtotime((string) $event['end_date'])) : null;
        $status = (string) ($event['status'] ?? 'active');
        $eventStatus = $status === 'cancelled'
            ? 'https://schema.org/EventCancelled'
            : 'https://schema.org/EventScheduled';
        $eventFormat = (string) ($event['event_format'] ?? 'physical');
        $attendanceMode = match ($eventFormat) {
            'online' => 'https://schema.org/OnlineEventAttendanceMode',
            'hybrid' => 'https://schema.org/MixedEventAttendanceMode',
            default => 'https://schema.org/OfflineEventAttendanceMode',
        };

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => (string) ($event['title'] ?? ''),
            'description' => $this->buildEventMetaDescription($event),
            'url' => $canonicalUrl,
            'eventStatus' => $eventStatus,
            'eventAttendanceMode' => $attendanceMode,
            'organizer' => [
                '@type' => 'Organization',
                'name' => lang('App.siteTitle'),
                'url' => base_url('/'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonicalUrl,
                'availability' => ((int) ($event['remaining_seats'] ?? 0) > 0 && $status === 'active')
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/SoldOut',
                'priceCurrency' => 'EUR',
                'price' => (string) (($event['event_type'] ?? 'free') === 'donation' ? (float) ($event['min_donation'] ?? 0) : 0),
            ],
        ];

        if ($startDate !== null) {
            $data['startDate'] = $startDate;
        }

        if ($endDate !== null) {
            $data['endDate'] = $endDate;
        }

        if ($metaImage !== '') {
            $data['image'] = [$metaImage];
        }

        if ($eventFormat === 'online') {
            $data['location'] = [
                '@type' => 'VirtualLocation',
                'url' => trim((string) ($event['online_url'] ?? $canonicalUrl)) ?: $canonicalUrl,
            ];
        } else {
            $data['location'] = [
                '@type' => 'Place',
                'name' => (string) ($event['location'] ?? ''),
                'address' => trim((string) ($event['address'] ?? ($event['location'] ?? ''))),
            ];
        }

        return $data;
    }

    private function buildEventBreadcrumbStructuredData(array $event, string $canonicalUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => lang('App.eventsPageTitle'),
                    'item' => base_url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => (string) ($event['title'] ?? ''),
                    'item' => $canonicalUrl,
                ],
            ],
        ];
    }
}
