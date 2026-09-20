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
            'analyticsUrl' => base_url('admin/analytics'),
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

    /**
     * @return array{rows: list<array<string, mixed>>, totals: array<string, float>, filters: array<string, mixed>}
     */
    private function paymentsData(int $limit): array
    {
        $date = static fn (?string $v): string => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) === 1 ? (string) $v : '';
        $from = $date($this->request->getGet('from'));
        $to   = $date($this->request->getGet('to'));
        $eventId = max(0, (int) $this->request->getGet('event_id'));

        $db      = db_connect();
        $builder = $db->table($db->prefixTable('payments') . ' p')
            ->select('p.id, p.created_at, p.amount, p.currency, p.payment_status, p.paypal_transaction_id, t.ticket_code, e.title AS event_title, u.first_name, u.last_name, u.email')
            ->join($db->prefixTable('tickets') . ' t', 't.id = p.ticket_id')
            ->join($db->prefixTable('events') . ' e', 'e.id = t.event_id')
            ->join($db->prefixTable('users') . ' u', 'u.id = t.user_id', 'left');

        if ($from !== '') {
            $builder->where('p.created_at >=', $from . ' 00:00:00');
        }
        if ($to !== '') {
            $builder->where('p.created_at <=', $to . ' 23:59:59');
        }
        if ($eventId > 0) {
            $builder->where('e.id', $eventId);
        }

        $rows   = $builder->orderBy('p.created_at', 'DESC')->limit($limit)->get()->getResultArray();
        $totals = ['completed' => 0.0, 'refunded' => 0.0];

        foreach ($rows as $row) {
            $key = (string) $row['payment_status'] === 'refunded' ? 'refunded' : 'completed';
            $totals[$key] += (float) $row['amount'];
        }

        return ['rows' => $rows, 'totals' => $totals, 'filters' => ['from' => $from, 'to' => $to, 'event_id' => $eventId]];
    }

    public function payments(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        return view('admin/payments', $this->paymentsData(500) + [
            'events'    => $this->eventModel->orderBy('start_date', 'DESC')->findAll(200),
            'pageTitle' => lang('App.paymentsTitle'),
        ]);
    }

    public function paymentsExport(): \CodeIgniter\HTTP\ResponseInterface|\CodeIgniter\HTTP\RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $data = $this->paymentsData(50000);
        $csv  = [['Date', 'Event', 'Ticket', 'Customer', 'Email', 'Amount', 'Currency', 'Status', 'PayPal transaction']];

        foreach ($data['rows'] as $r) {
            $csv[] = [
                (string) $r['created_at'],
                (string) $r['event_title'],
                (string) $r['ticket_code'],
                trim((string) $r['first_name'] . ' ' . (string) $r['last_name']),
                (string) $r['email'],
                number_format((float) $r['amount'], 2, '.', ''),
                (string) $r['currency'],
                (string) $r['payment_status'],
                (string) $r['paypal_transaction_id'],
            ];
        }

        $out = '';
        foreach ($csv as $line) {
            $out .= implode(',', array_map(static fn ($c) => '"' . str_replace('"', '""', csv_safe($c)) . '"', $line)) . "\r\n";
        }

        $this->logAdminAction('payments_export', 'system', ['rows' => count($data['rows'])]);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="payments-' . date('Ymd') . '.csv"')
            ->setBody("\xEF\xBB\xBF" . $out);
    }

    public function analytics(): string
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $db     = db_connect();
        $tTable = $db->prefixTable('tickets');
        $eTable = $db->prefixTable('events');

        $since = date('Y-m-d', strtotime('-29 days'));
        $dailyRows = $db->query(
            "SELECT DATE(created_at) AS day, COUNT(*) AS cnt FROM {$tTable}
             WHERE status = 'valid' AND DATE(created_at) >= ? GROUP BY day ORDER BY day ASC",
            [$since]
        )->getResultArray();

        $dailyMap = [];
        foreach ($dailyRows as $r) {
            $dailyMap[$r['day']] = (int) $r['cnt'];
        }
        $days = [];
        $dayCounts = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $days[]      = date('d/m', strtotime($d));
            $dayCounts[] = $dailyMap[$d] ?? 0;
        }

        $revenueRows = $db->query(
            "SELECT e.title, SUM(t.donation_amount) AS total
             FROM {$tTable} t JOIN {$eTable} e ON e.id = t.event_id
             WHERE t.status = 'valid' AND t.donation_amount > 0
             GROUP BY t.event_id ORDER BY total DESC LIMIT 8"
        )->getResultArray();

        $checkInRows = $db->query(
            "SELECT e.title,
                COUNT(CASE WHEN t.status='valid' THEN t.id END) AS issued,
                SUM(CASE WHEN t.status='valid' AND t.checked_in_at IS NOT NULL THEN 1 ELSE 0 END) AS checked_in
             FROM {$eTable} e LEFT JOIN {$tTable} t ON t.event_id = e.id
             WHERE e.deleted_at IS NULL AND e.status IN ('active','inactive')
             GROUP BY e.id HAVING issued > 0 ORDER BY e.start_date DESC LIMIT 10"
        )->getResultArray();

        return view('admin/analytics', [
            'pageTitle'   => lang('App.analyticsPageTitle'),
            'days'        => $days,
            'dayCounts'   => $dayCounts,
            'revenueRows' => $revenueRows,
            'checkInRows' => $checkInRows,
        ]);
    }
}
