<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class ReportController extends EventBaseController
{
    public function report(): RedirectResponse|string
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.reportUnauthorized'));
        }

        $db = db_connect();
        $eventsTable = $db->prefixTable('events');
        $ticketsTable = $db->prefixTable('tickets');
        $usersTable = $db->prefixTable('users');

        $reportRows = $db->table($eventsTable . ' events')
            ->select("events.id, events.slug, events.title, events.location, events.start_date, events.end_date, events.capacity, events.event_type, events.status, COUNT(CASE WHEN tickets.status = 'valid' THEN tickets.id END) AS issued_tickets, SUM(CASE WHEN tickets.status = 'valid' AND tickets.payment_status = 'free' THEN 1 ELSE 0 END) AS free_tickets, SUM(CASE WHEN tickets.status = 'valid' AND tickets.payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_tickets, SUM(CASE WHEN tickets.status = 'valid' THEN tickets.donation_amount ELSE 0 END) AS donation_total", false)
            ->join($ticketsTable . ' tickets', 'tickets.event_id = events.id', 'left', false)
            ->groupBy('events.id')
            ->orderBy('events.start_date', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($reportRows as &$row) {
            $row['issued_tickets'] = (int) ($row['issued_tickets'] ?? 0);
            $row['free_tickets'] = (int) ($row['free_tickets'] ?? 0);
            $row['paid_tickets'] = (int) ($row['paid_tickets'] ?? 0);
            $row['capacity'] = (int) ($row['capacity'] ?? 0);
            $row['remaining_seats'] = max($row['capacity'] - $row['issued_tickets'], 0);
            $row['donation_total'] = number_format((float) ($row['donation_total'] ?? 0), 2, '.', '');
        }

        unset($row);

        $selectedEventId = (int) ($this->request->getGet('event_id') ?? 0);
        $selectedEvent = null;
        $ticketRows = [];

        foreach ($reportRows as $row) {
            if ((int) ($row['id'] ?? 0) === $selectedEventId) {
                $selectedEvent = $row;
                break;
            }
        }

        if ($selectedEvent !== null) {
            $ticketRows = $db->table($ticketsTable . ' tickets')
                ->select([
                    'tickets.ticket_code',
                    'tickets.payment_status',
                    'tickets.donation_amount',
                    'tickets.created_at AS booked_at',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                ])
                ->join($usersTable . ' users', 'users.id = tickets.user_id', 'left', false)
                ->where('tickets.event_id', $selectedEventId)
                ->where('tickets.status', 'valid')
                ->orderBy('tickets.created_at', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($ticketRows as &$ticketRow) {
                $ticketRow['customer_name'] = trim(((string) ($ticketRow['first_name'] ?? '')) . ' ' . ((string) ($ticketRow['last_name'] ?? '')));
                $ticketRow['donation_amount'] = number_format((float) ($ticketRow['donation_amount'] ?? 0), 2, '.', '');
            }

            unset($ticketRow);
        }

        return view('events/report', [
            'reportRows' => $reportRows,
            'selectedEventId' => $selectedEventId,
            'selectedEvent' => $selectedEvent,
            'ticketRows' => $ticketRows,
            'pageTitle' => lang('App.reportPageTitle'),
        ]);
    }

    public function myEvents(): RedirectResponse|string
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
        }

        $userId = (int) session()->get('user_id');
        $rows = $this->ticketModel
            ->select([
                'tickets.id AS ticket_id',
                'tickets.ticket_code',
                'tickets.donation_amount',
                'tickets.payment_status',
                'tickets.created_at AS booked_at',
                'events.id AS event_id',
                'events.slug',
                'events.title',
                'events.image',
                'events.location',
                'events.address',
                'events.start_date',
                'events.end_date',
                'events.event_type',
                'events.status',
            ])
            ->join('events', 'events.id = tickets.event_id')
            ->where('tickets.user_id', $userId)
            ->where('tickets.status', 'valid')
            ->orderBy('events.start_date', 'ASC')
            ->orderBy('tickets.created_at', 'DESC')
            ->findAll();

        $events = [];

        foreach ($rows as $row) {
            $eventId = (int) ($row['event_id'] ?? 0);
            if ($eventId < 1) {
                continue;
            }

            if (! isset($events[$eventId])) {
                $events[$eventId] = [
                    'event_id' => $eventId,
                    'slug' => (string) ($row['slug'] ?? ''),
                    'title' => (string) ($row['title'] ?? ''),
                    'image' => (string) ($row['image'] ?? ''),
                    'location' => (string) ($row['location'] ?? ''),
                    'address' => (string) ($row['address'] ?? ''),
                    'start_date' => $row['start_date'] ?? null,
                    'end_date' => $row['end_date'] ?? null,
                    'event_type' => (string) ($row['event_type'] ?? 'free'),
                    'status' => (string) ($row['status'] ?? 'inactive'),
                    'tickets_count' => 0,
                    'donation_total' => 0.0,
                    'booked_at' => $row['booked_at'] ?? null,
                    'tickets' => [],
                    'payment_statuses' => [],
                ];
            }

            $events[$eventId]['tickets_count']++;
            $events[$eventId]['donation_total'] += (float) ($row['donation_amount'] ?? 0);
            $events[$eventId]['tickets'][] = [
                'code'           => (string) ($row['ticket_code'] ?? ''),
                'payment_status' => (string) ($row['payment_status'] ?? 'free'),
            ];
            $events[$eventId]['payment_statuses'][(string) ($row['payment_status'] ?? '')] = true;

            if (! empty($row['booked_at']) && (empty($events[$eventId]['booked_at']) || strtotime((string) $row['booked_at']) > strtotime((string) $events[$eventId]['booked_at']))) {
                $events[$eventId]['booked_at'] = $row['booked_at'];
            }
        }

        foreach ($events as &$event) {
            $event['donation_total'] = number_format((float) $event['donation_total'], 2, '.', '');
            $event['payment_summary'] = isset($event['payment_statuses']['paid']) ? 'paid' : 'free';
        }

        unset($event);

        return view('events/my_events', [
            'events' => array_values($events),
            'pageTitle' => lang('App.myEventsPageTitle'),
        ]);
    }
}
