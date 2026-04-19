<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;

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
        $paymentsTable = $db->prefixTable('payments');
        $usersTable = $db->prefixTable('users');

        $reportRows = $db->table($eventsTable . ' events')
            ->select("events.id, events.slug, events.title, events.location, events.start_date, events.end_date, events.capacity, events.event_type, events.status, COUNT(CASE WHEN tickets.status = 'valid' THEN tickets.id END) AS issued_tickets, SUM(CASE WHEN tickets.status = 'valid' AND tickets.payment_status = 'free' THEN 1 ELSE 0 END) AS free_tickets, SUM(CASE WHEN tickets.status = 'valid' AND tickets.payment_status = 'paid' THEN 1 ELSE 0 END) AS paid_tickets, SUM(CASE WHEN tickets.status = 'valid' AND tickets.checked_in_at IS NOT NULL THEN 1 ELSE 0 END) AS checked_in_tickets, SUM(CASE WHEN tickets.status = 'valid' THEN tickets.donation_amount ELSE 0 END) AS donation_total", false)
            ->join($ticketsTable . ' tickets', 'tickets.event_id = events.id', 'left', false)
            ->where('events.deleted_at', null)
            ->groupBy('events.id')
            ->orderBy('events.start_date', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($reportRows as &$row) {
            $row['issued_tickets'] = (int) ($row['issued_tickets'] ?? 0);
            $row['free_tickets'] = (int) ($row['free_tickets'] ?? 0);
            $row['paid_tickets'] = (int) ($row['paid_tickets'] ?? 0);
            $row['checked_in_tickets'] = (int) ($row['checked_in_tickets'] ?? 0);
            $row['capacity'] = (int) ($row['capacity'] ?? 0);
            $row['remaining_seats'] = max($row['capacity'] - $row['issued_tickets'], 0);
            $row['not_checked_in_tickets'] = max($row['issued_tickets'] - $row['checked_in_tickets'], 0);
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
                    'tickets.checked_in_at',
                    'payments.paypal_transaction_id',
                    'payments.payment_status AS gateway_payment_status',
                    'checkin_admin.first_name AS checked_in_by_first_name',
                    'checkin_admin.last_name AS checked_in_by_last_name',
                    'checkin_admin.email AS checked_in_by_email',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                ])
                ->join($paymentsTable . ' payments', 'payments.ticket_id = tickets.id', 'left', false)
                ->join($usersTable . ' users', 'users.id = tickets.user_id', 'left', false)
                ->join($usersTable . ' checkin_admin', 'checkin_admin.id = tickets.checked_in_by', 'left', false)
                ->where('tickets.event_id', $selectedEventId)
                ->where('tickets.status', 'valid')
                ->orderBy('tickets.created_at', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($ticketRows as &$ticketRow) {
                $ticketRow['customer_name'] = trim(((string) ($ticketRow['first_name'] ?? '')) . ' ' . ((string) ($ticketRow['last_name'] ?? '')));
                $checkedInByName = trim(((string) ($ticketRow['checked_in_by_first_name'] ?? '')) . ' ' . ((string) ($ticketRow['checked_in_by_last_name'] ?? '')));
                $ticketRow['checked_in_label'] = ! empty($ticketRow['checked_in_at']) ? lang('App.reportCheckedInYes') : lang('App.reportCheckedInNo');
                $ticketRow['checked_in_at_formatted'] = ! empty($ticketRow['checked_in_at']) ? date('d/m/Y H:i', strtotime((string) $ticketRow['checked_in_at'])) : '-';
                $ticketRow['checked_in_by_name'] = $checkedInByName !== '' ? $checkedInByName : (string) ($ticketRow['checked_in_by_email'] ?? '-');
                $ticketRow['donation_amount'] = number_format((float) ($ticketRow['donation_amount'] ?? 0), 2, '.', '');
                $ticketRow['paypal_transaction_id'] = (string) ($ticketRow['paypal_transaction_id'] ?? '-');
                $ticketRow['gateway_payment_status'] = (string) ($ticketRow['gateway_payment_status'] ?? '-');
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
            ->where('events.deleted_at', null)
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

    public function checkIn(): RedirectResponse|string
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.checkInUnauthorized'));
        }

        [$checkInEvents, $checkInTotals] = $this->getCheckInDashboardData();

        return view('events/check_in', [
            'pageTitle' => lang('App.checkInPageTitle'),
            'result' => session()->getFlashdata('check_in_result'),
            'enteredCode' => (string) session()->getFlashdata('check_in_code'),
            'checkInEvents' => $checkInEvents,
            'checkInTotals' => $checkInTotals,
        ]);
    }

    public function processCheckIn(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.checkInUnauthorized'));
        }

        $ticketCode = strtoupper(preg_replace('/\s+/', '', trim((string) $this->request->getPost('ticket_code'))));

        if ($ticketCode === '') {
            return redirect()->to(base_url('check-in'))
                ->with('check_in_code', '')
                ->with('check_in_result', [
                    'type' => 'error',
                    'message' => lang('App.checkInCodeRequired'),
                ]);
        }

        $ticket = $this->ticketModel
            ->select([
                'tickets.id',
                'tickets.ticket_code',
                'tickets.payment_status',
                'tickets.donation_amount',
                'tickets.status',
                'tickets.created_at',
                'tickets.checked_in_at',
                'tickets.checked_in_by',
                'events.title AS event_title',
                'events.location AS event_location',
                'events.start_date AS event_start_date',
                'users.first_name',
                'users.last_name',
                'users.email',
            ])
            ->join('events', 'events.id = tickets.event_id')
            ->join('users', 'users.id = tickets.user_id', 'left')
            ->where('tickets.ticket_code', $ticketCode)
            ->first();

        if ($ticket === null) {
            return redirect()->to(base_url('check-in'))
                ->with('check_in_code', $ticketCode)
                ->with('check_in_result', [
                    'type' => 'error',
                    'message' => lang('App.checkInNotFound'),
                ]);
        }

        $customerName = trim(((string) ($ticket['first_name'] ?? '')) . ' ' . ((string) ($ticket['last_name'] ?? '')));
        $details = [
            'ticket_code' => (string) ($ticket['ticket_code'] ?? ''),
            'event_title' => (string) ($ticket['event_title'] ?? '-'),
            'event_location' => (string) ($ticket['event_location'] ?? '-'),
            'event_start_date' => ! empty($ticket['event_start_date']) ? date('d/m/Y H:i', strtotime((string) $ticket['event_start_date'])) : '-',
            'customer_name' => $customerName !== '' ? $customerName : '-',
            'customer_email' => (string) ($ticket['email'] ?? '-'),
            'payment_status_label' => lang('App.paymentStatus' . ucfirst((string) ($ticket['payment_status'] ?? 'free'))),
            'donation_amount' => 'EUR ' . number_format((float) ($ticket['donation_amount'] ?? 0), 2),
            'booked_at' => ! empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime((string) $ticket['created_at'])) : '-',
            'checked_in_at' => ! empty($ticket['checked_in_at']) ? date('d/m/Y H:i', strtotime((string) $ticket['checked_in_at'])) : '',
        ];

        if ((string) ($ticket['status'] ?? '') !== 'valid') {
            return redirect()->to(base_url('check-in'))
                ->with('check_in_code', $ticketCode)
                ->with('check_in_result', [
                    'type' => 'error',
                    'message' => lang('App.checkInInvalidStatus'),
                    'details' => $details,
                ]);
        }

        if (! empty($ticket['checked_in_at'])) {
            return redirect()->to(base_url('check-in'))
                ->with('check_in_code', $ticketCode)
                ->with('check_in_result', [
                    'type' => 'warning',
                    'message' => lang('App.checkInAlreadyUsed'),
                    'details' => $details,
                ]);
        }

        $this->ticketModel->update((int) $ticket['id'], [
            'checked_in_at' => Time::now()->toDateTimeString(),
            'checked_in_by' => (int) session()->get('user_id'),
        ]);

        $this->logAdminAction('ticket_check_in', 'ticket', [
            'target_ticket_id' => (int) $ticket['id'],
            'ticket_code' => $ticketCode,
            'event_title' => (string) ($ticket['event_title'] ?? ''),
        ]);

        $details['checked_in_at'] = date('d/m/Y H:i');

        return redirect()->to(base_url('check-in'))
            ->with('check_in_code', $ticketCode)
            ->with('check_in_result', [
                'type' => 'success',
                'message' => lang('App.checkInSuccess'),
                'details' => $details,
            ]);
    }

    public function exportCheckIn(): RedirectResponse|ResponseInterface
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.checkInUnauthorized'));
        }

        $db = db_connect();
        $rows = $db->table($db->prefixTable('tickets') . ' tickets')
            ->select([
                'tickets.ticket_code',
                'tickets.payment_status',
                'tickets.donation_amount',
                'tickets.created_at AS booked_at',
                'tickets.checked_in_at',
                'events.title AS event_title',
                'events.start_date AS event_start_date',
                'users.first_name',
                'users.last_name',
                'users.email',
            ])
            ->join($db->prefixTable('events') . ' events', 'events.id = tickets.event_id')
            ->join($db->prefixTable('users') . ' users', 'users.id = tickets.user_id', 'left')
            ->where('tickets.status', 'valid')
            ->where('events.deleted_at', null)
            ->orderBy('events.start_date', 'DESC')
            ->orderBy('tickets.checked_in_at', 'DESC')
            ->get()
            ->getResultArray();

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return $this->response->setStatusCode(500)->setBody('Unable to export check-in list.');
        }

        fputcsv($handle, [
            lang('App.reportEvent'),
            lang('App.startDate'),
            lang('App.reportTicketCode'),
            lang('App.reportCustomer'),
            lang('App.reportCustomerEmail'),
            lang('App.reportPaymentStatus'),
            lang('App.reportDonationAmount'),
            lang('App.reportBookedAt'),
            lang('App.reportCheckInStatus'),
            lang('App.checkInCheckedInAt'),
        ]);

        foreach ($rows as $row) {
            $customerName = trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? '')));
            $checkedInAt = (string) ($row['checked_in_at'] ?? '');

            fputcsv($handle, [
                (string) ($row['event_title'] ?? ''),
                ! empty($row['event_start_date']) ? date('d/m/Y H:i', strtotime((string) $row['event_start_date'])) : '',
                (string) ($row['ticket_code'] ?? ''),
                $customerName,
                (string) ($row['email'] ?? ''),
                (string) ($row['payment_status'] ?? ''),
                number_format((float) ($row['donation_amount'] ?? 0), 2, '.', ''),
                ! empty($row['booked_at']) ? date('d/m/Y H:i', strtotime((string) $row['booked_at'])) : '',
                $checkedInAt !== '' ? lang('App.reportCheckedInYes') : lang('App.reportCheckedInNo'),
                $checkedInAt !== '' ? date('d/m/Y H:i', strtotime($checkedInAt)) : '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="check-in-list-' . date('Ymd-His') . '.csv"')
            ->setBody("\xEF\xBB\xBF" . (string) $csv);
    }

    private function getCheckInDashboardData(): array
    {
        $db = db_connect();
        $eventsTable = $db->prefixTable('events');
        $ticketsTable = $db->prefixTable('tickets');

        $rows = $db->table($eventsTable . ' events')
            ->select("events.id, events.slug, events.title, events.start_date, events.location, events.capacity, COUNT(CASE WHEN tickets.status = 'valid' THEN tickets.id END) AS issued_tickets, SUM(CASE WHEN tickets.status = 'valid' AND tickets.checked_in_at IS NOT NULL THEN 1 ELSE 0 END) AS checked_in_tickets", false)
            ->join($ticketsTable . ' tickets', 'tickets.event_id = events.id', 'left', false)
            ->whereIn('events.status', ['active', 'inactive'])
            ->where('events.deleted_at', null)
            ->groupBy('events.id')
            ->orderBy('events.start_date', 'DESC')
            ->limit(12)
            ->get()
            ->getResultArray();

        $totals = [
            'issued' => 0,
            'checked_in' => 0,
            'pending' => 0,
            'rate' => 0,
        ];

        foreach ($rows as &$row) {
            $issued = (int) ($row['issued_tickets'] ?? 0);
            $checkedIn = (int) ($row['checked_in_tickets'] ?? 0);
            $pending = max($issued - $checkedIn, 0);
            $row['issued_tickets'] = $issued;
            $row['checked_in_tickets'] = $checkedIn;
            $row['pending_tickets'] = $pending;
            $row['check_in_rate'] = $issued > 0 ? (int) round(($checkedIn / $issued) * 100) : 0;
            $row['start_date_label'] = ! empty($row['start_date']) ? date('d/m/Y H:i', strtotime((string) $row['start_date'])) : '-';

            $totals['issued'] += $issued;
            $totals['checked_in'] += $checkedIn;
            $totals['pending'] += $pending;
        }
        unset($row);

        $totals['rate'] = $totals['issued'] > 0 ? (int) round(($totals['checked_in'] / $totals['issued']) * 100) : 0;

        return [$rows, $totals];
    }
}
