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

    public function show(string $slug): string
    {
        $event = $this->eventModel->where('slug', $slug)->first();

        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        $event['remaining_seats'] = $this->getRemainingSeats($event);

        return view('events/show', [
            'event' => $event,
            'pageTitle' => $event['title'] . ' | Ticketing System',
            'paypalClientId' => $this->getPayPalClientId(),
        ]);
    }
}
