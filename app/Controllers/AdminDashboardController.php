<?php

namespace App\Controllers;

class AdminDashboardController extends EventBaseController
{
    public function index(): string
    {
        $db = db_connect();
        $eventsTable = $db->prefixTable('events');
        $ticketsTable = $db->prefixTable('tickets');

        $totals = $db->table($ticketsTable . ' tickets')
            ->select("COUNT(CASE WHEN tickets.status = 'valid' THEN tickets.id END) AS issued_tickets, SUM(CASE WHEN tickets.status = 'valid' AND tickets.checked_in_at IS NOT NULL THEN 1 ELSE 0 END) AS checked_in_tickets, SUM(CASE WHEN tickets.status = 'valid' THEN tickets.donation_amount ELSE 0 END) AS donation_total", false)
            ->get()
            ->getRowArray() ?? [];

        $activeEvents = $db->table($eventsTable)
            ->where('status', 'active')
            ->where('deleted_at', null)
            ->countAllResults();

        $upcomingEvents = $db->table($eventsTable)
            ->where('deleted_at', null)
            ->whereIn('status', ['active', 'inactive'])
            ->where('start_date >=', date('Y-m-d H:i:s'))
            ->orderBy('start_date', 'ASC')
            ->limit(6)
            ->get()
            ->getResultArray();

        $attentionRows = $db->table($eventsTable . ' events')
            ->select("events.id, events.slug, events.title, events.capacity, events.start_date, events.status, events.bookings_enabled, COUNT(CASE WHEN tickets.status = 'valid' THEN tickets.id END) AS issued_tickets", false)
            ->join($ticketsTable . ' tickets', 'tickets.event_id = events.id', 'left', false)
            ->where('events.deleted_at', null)
            ->groupBy('events.id')
            ->orderBy('events.start_date', 'ASC')
            ->get()
            ->getResultArray();
        $attentionEvents = [];

        foreach ($attentionRows as $row) {
            $issuedTickets = (int) ($row['issued_tickets'] ?? 0);
            $capacity = (int) ($row['capacity'] ?? 0);
            $bookingsEnabled = (int) ($row['bookings_enabled'] ?? 1) === 1;

            if (! $bookingsEnabled || ($capacity > 0 && $issuedTickets >= $capacity)) {
                $attentionEvents[] = $row;
            }

            if (count($attentionEvents) >= 8) {
                break;
            }
        }

        return view('admin/dashboard', [
            'pageTitle' => lang('App.adminDashboardPageTitle'),
            'stats' => [
                'active_events' => $activeEvents,
                'issued_tickets' => (int) ($totals['issued_tickets'] ?? 0),
                'checked_in_tickets' => (int) ($totals['checked_in_tickets'] ?? 0),
                'donation_total' => number_format((float) ($totals['donation_total'] ?? 0), 2, '.', ''),
            ],
            'upcomingEvents' => $upcomingEvents,
            'attentionEvents' => $attentionEvents,
        ]);
    }
}
