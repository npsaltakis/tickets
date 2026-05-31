<?php

namespace App\Controllers;

use App\Models\EventModel;
use App\Models\TicketModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class TicketAdminController extends BaseController
{
    private EventModel  $eventModel;
    private TicketModel $ticketModel;
    private UserModel   $userModel;

    public function __construct()
    {
        $this->eventModel  = new EventModel();
        $this->ticketModel = new TicketModel();
        $this->userModel   = new UserModel();
    }

    public function index(string $slug): string|RedirectResponse
    {
        if (! $this->ensureAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $event = $this->eventModel->where('slug', $slug)->first();
        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        $db           = db_connect();
        $ticketsTable = $db->prefixTable('tickets');
        $usersTable   = $db->prefixTable('users');

        $tickets = $db->table($ticketsTable . ' t')
            ->select('t.id, t.ticket_code, t.status, t.payment_status, t.donation_amount, t.created_at, t.checked_in_at, u.first_name, u.last_name, u.email')
            ->join($usersTable . ' u', 'u.id = t.user_id', 'left')
            ->where('t.event_id', (int) $event['id'])
            ->orderBy('t.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $users = $this->userModel->where('status', 'active')->orderBy('first_name', 'ASC')->findAll();

        return view('events/admin_tickets', [
            'event'     => $event,
            'tickets'   => $tickets,
            'users'     => $users,
            'pageTitle' => lang('App.adminTicketsPageTitle') . ' — ' . ($event['title'] ?? ''),
        ]);
    }

    public function store(string $slug): RedirectResponse
    {
        if (! $this->ensureAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $event = $this->eventModel->where('slug', $slug)->first();
        if (empty($event)) {
            throw PageNotFoundException::forPageNotFound('Event not found');
        }

        $userId = (int) $this->request->getPost('user_id');
        $seats  = max(1, (int) $this->request->getPost('seats'));

        if ($userId < 1) {
            return redirect()->back()->with('ticket_error', lang('App.adminTicketsUserRequired'));
        }

        $user = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->back()->with('ticket_error', lang('App.adminTicketsUserNotFound'));
        }

        $capacity    = (int) ($event['capacity'] ?? 0);
        $issued      = $this->ticketModel
            ->where('event_id', (int) $event['id'])
            ->where('status', 'valid')
            ->whereNotIn('payment_status', ['failed'])
            ->countAllResults();
        $remaining = max($capacity - $issued, 0);

        if ($seats > $remaining) {
            return redirect()->back()->with('ticket_error', strtr(lang('App.seatsLimitError'), ['{max}' => $remaining]));
        }

        $ticketCodes = [];
        for ($i = 0; $i < $seats; $i++) {
            $code = $this->generateCode();
            $ticketCodes[] = $code;
            $this->ticketModel->insert([
                'event_id'       => (int) $event['id'],
                'user_id'        => $userId,
                'ticket_code'    => $code,
                'donation_amount' => 0.00,
                'payment_status' => 'free',
                'status'         => 'valid',
            ]);
        }

        $this->logAdminAction('ticket_manual_create', 'ticket', [
            'event_id' => (int) $event['id'],
            'user_id'  => $userId,
            'seats'    => $seats,
            'codes'    => implode(', ', $ticketCodes),
        ]);

        return redirect()->to(base_url('admin/events/' . $slug . '/tickets'))
            ->with('ticket_info', lang('App.adminTicketsCreated'));
    }

    public function qrCode(string $ticketCode): ResponseInterface
    {
        if (session()->get('is_logged_in') !== true || (string) session()->get('user_role') !== 'admin') {
            return $this->response->setStatusCode(403)->setBody('Unauthorized');
        }

        $ticketCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $ticketCode));
        if ($ticketCode === '') {
            return $this->response->setStatusCode(400)->setBody('Invalid code');
        }

        try {
            $qrCode = QrCode::create($ticketCode)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
                ->setSize(300)
                ->setMargin(10)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);

            $writer = new PngWriter();
            $result = $writer->write($qrCode);

            return $this->response
                ->setHeader('Content-Type', $result->getMimeType())
                ->setHeader('Cache-Control', 'public, max-age=3600')
                ->setBody($result->getString());
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setBody('QR generation failed');
        }
    }

    public function export(string $slug): ResponseInterface
    {
        if (! $this->ensureAdmin()) {
            return $this->response->setStatusCode(403)->setBody('Unauthorized');
        }

        $event = $this->eventModel->where('slug', $slug)->first();
        if (empty($event)) {
            return $this->response->setStatusCode(404)->setBody('Event not found');
        }

        $db           = db_connect();
        $ticketsTable = $db->prefixTable('tickets');
        $usersTable   = $db->prefixTable('users');

        $rows = $db->table($ticketsTable . ' t')
            ->select('t.ticket_code, t.status, t.payment_status, t.donation_amount, t.created_at, t.checked_in_at, u.first_name, u.last_name, u.email')
            ->join($usersTable . ' u', 'u.id = t.user_id', 'left')
            ->where('t.event_id', (int) $event['id'])
            ->orderBy('t.created_at', 'ASC')
            ->get()
            ->getResultArray();

        $csvRows = [['Ticket Code', 'First Name', 'Last Name', 'Email', 'Status', 'Payment', 'Amount', 'Booked At', 'Checked In At']];

        foreach ($rows as $row) {
            $csvRows[] = [
                $row['ticket_code'] ?? '',
                $row['first_name'] ?? '',
                $row['last_name'] ?? '',
                $row['email'] ?? '',
                $row['status'] ?? '',
                $row['payment_status'] ?? '',
                $row['donation_amount'] ?? 0,
                $row['created_at'] ?? '',
                $row['checked_in_at'] ?? '',
            ];
        }

        $csv = '';
        foreach ($csvRows as $row) {
            $csv .= implode(',', array_map(
                static fn ($c) => '"' . str_replace('"', '""', (string) $c) . '"',
                $row
            )) . "\r\n";
        }

        $filename = 'attendees-' . $slug . '-' . date('Y-m-d') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody("\xEF\xBB\xBF" . $csv);
    }

    private function ensureAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }

    private function generateCode(): string
    {
        do {
            $code = strtoupper(bin2hex(random_bytes(6)));
        } while ($this->ticketModel->where('ticket_code', $code)->first() !== null);

        return $code;
    }

    private function logAdminAction(string $action, string $targetType, array $context = []): void
    {
        try {
            $adminLogModel = new \App\Models\AdminLogModel();
            $adminLogModel->insert([
                'admin_id'    => (int) session()->get('user_id'),
                'admin_email' => (string) session()->get('user_email'),
                'action'      => $action,
                'target_type' => $targetType,
                'context'     => json_encode($context),
                'ip_address'  => service('request')->getIPAddress(),
            ]);
        } catch (\Throwable) {
        }
    }
}
