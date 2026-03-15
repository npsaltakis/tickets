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
        $isLoggedIn = session()->get('is_logged_in') === true;
        $isAdmin = $isLoggedIn && (string) session()->get('user_role') === 'admin';
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

        return view('events/show', [
            'event' => $event,
            'pageTitle' => $event['title'] . ' | Ticketing System',
            'paypalClientId' => $this->getPayPalClientId(),
            'hasOnlineAccess' => $hasOnlineAccess,
            'userTicketCodes' => $userTicketCodes,
        ]);
    }
}
