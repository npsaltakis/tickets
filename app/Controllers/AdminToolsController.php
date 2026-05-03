<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class AdminToolsController extends BaseController
{
    private const DEMO_CLEANUP_CONFIRMATION = 'DELETE_DEMO_DATA';

    public function sendTestEmail(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.eventCreateUnauthorized'));
        }

        $recipient = trim((string) session()->get('user_email'));
        if ($recipient === '') {
            return redirect()->back()->with('event_error', lang('App.adminTestEmailMissingRecipient'));
        }

        try {
            $emailService = service('email');
            $emailService->setTo($recipient);
            $emailService->setSubject($this->bilingualSubject('App.adminTestEmailSubject'));
            $emailService->setMailType('html');
            $emailService->setMessage($this->buildBilingualActionEmailHtml(
                [
                    $this->localizedLine('App.adminTestEmailGreeting', [], 'el'),
                    $this->localizedLine('App.adminTestEmailIntro', [], 'el'),
                    $this->localizedLine('App.adminTestEmailFooter', [], 'el'),
                ],
                [
                    $this->localizedLine('App.adminTestEmailGreeting', [], 'en'),
                    $this->localizedLine('App.adminTestEmailIntro', [], 'en'),
                    $this->localizedLine('App.adminTestEmailFooter', [], 'en'),
                ],
                base_url('/'),
                $this->localizedLine('App.adminTestEmailButton', [], 'el'),
                $this->localizedLine('App.adminTestEmailButton', [], 'en'),
                $this->bilingualSubject('App.adminTestEmailSubject')
            ));

            if (! $emailService->send()) {
                log_message('error', 'Admin test email failed. debug={debug}', [
                    'debug' => $emailService->printDebugger(['headers', 'subject']),
                ]);

                return redirect()->back()->with('event_error', lang('App.adminTestEmailFailed'));
            }
        } catch (Throwable $exception) {
            log_message('error', 'Admin test email exception: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->back()->with('event_error', lang('App.adminTestEmailFailed'));
        }

        $this->logAdminAction('admin_test_email', 'system', [
            'recipient' => $recipient,
        ]);

        return redirect()->back()->with('event_info', lang('App.adminTestEmailSuccess'));
    }

    public function cleanupDemoDataPreview(): ResponseInterface
    {
        if (! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => lang('App.adminAccessRequired'),
            ]);
        }

        return $this->response->setJSON([
            'status' => 'preview',
            'confirmation_required' => self::DEMO_CLEANUP_CONFIRMATION,
            'matched' => $this->getDemoCleanupPlan(),
        ]);
    }

    public function cleanupDemoData(): ResponseInterface
    {
        if (! $this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => lang('App.adminAccessRequired'),
            ]);
        }

        if ((string) $this->request->getPost('confirm') !== self::DEMO_CLEANUP_CONFIRMATION) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'message' => 'Send confirm=' . self::DEMO_CLEANUP_CONFIRMATION . ' to run cleanup.',
                'matched' => $this->getDemoCleanupPlan(),
            ]);
        }

        $db = db_connect();
        $plan = $this->getDemoCleanupPlan();
        $eventsTable = $db->prefixTable('events');
        $ticketsTable = $db->prefixTable('tickets');
        $paymentsTable = $db->prefixTable('payments');
        $usersTable = $db->prefixTable('users');
        $capturesTable = $db->prefixTable('paypal_captures');
        $deleted = [
            'events' => 0,
            'users' => 0,
            'tickets' => 0,
            'payments' => 0,
            'paypal_captures' => 0,
        ];

        $eventIds = $plan['event_ids'];
        $userIds = $plan['user_ids'];
        $ticketIds = $plan['ticket_ids'];
        $captureIds = $plan['paypal_transaction_ids'];

        $db->transBegin();

        try {
            if ($captureIds !== [] && $db->tableExists('paypal_captures')) {
                $db->table($capturesTable)
                    ->whereIn('paypal_transaction_id', $captureIds)
                    ->delete();
                $deleted['paypal_captures'] = $db->affectedRows();
            }

            if ($ticketIds !== []) {
                $db->table($paymentsTable)
                    ->whereIn('ticket_id', $ticketIds)
                    ->delete();
                $deleted['payments'] = $db->affectedRows();

                $db->table($ticketsTable)
                    ->whereIn('id', $ticketIds)
                    ->delete();
                $deleted['tickets'] = $db->affectedRows();
            }

            if ($eventIds !== []) {
                $db->table($eventsTable)
                    ->whereIn('id', $eventIds)
                    ->delete();
                $deleted['events'] = $db->affectedRows();
            }

            if ($userIds !== []) {
                $db->table($usersTable)
                    ->whereIn('id', $userIds)
                    ->where('role !=', 'admin')
                    ->delete();
                $deleted['users'] = $db->affectedRows();
            }

            if ($db->transStatus() === false) {
                $db->transRollback();

                return $this->response->setStatusCode(500)->setJSON([
                    'status' => 'error',
                    'message' => 'Demo cleanup failed.',
                ]);
            }

            $db->transCommit();
        } catch (Throwable $exception) {
            $db->transRollback();

            log_message('error', 'Demo cleanup failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => 'Demo cleanup failed.',
            ]);
        }

        $this->logAdminAction('demo_cleanup', 'system', [
            'deleted' => $deleted,
            'matched_before_cleanup' => $plan['counts'],
        ]);

        return $this->response->setJSON([
            'status' => 'ok',
            'deleted' => $deleted,
        ]);
    }

    private function getDemoCleanupPlan(): array
    {
        $db = db_connect();
        $eventsTable = $db->prefixTable('events');
        $ticketsTable = $db->prefixTable('tickets');
        $paymentsTable = $db->prefixTable('payments');
        $usersTable = $db->prefixTable('users');

        $eventRows = $db->table($eventsTable)
            ->select('id, title, slug')
            ->groupStart()
                ->like('title', 'demo')
                ->orLike('title', 'test')
                ->orLike('title', 'sample')
                ->orLike('title', 'example')
                ->orLike('title', 'δοκιμ')
                ->orLike('slug', 'demo')
                ->orLike('slug', 'test')
                ->orLike('slug', 'sample')
                ->orLike('slug', 'example')
                ->orLike('description', 'demo')
                ->orLike('description', 'test')
                ->orLike('description', 'sample')
                ->orLike('description', 'example')
            ->groupEnd()
            ->get()
            ->getResultArray();

        $userRows = $db->table($usersTable)
            ->select('id, email')
            ->where('role !=', 'admin')
            ->groupStart()
                ->like('email', 'demo')
                ->orLike('email', 'test')
                ->orLike('email', 'sample')
                ->orLike('email', 'example')
                ->orLike('first_name', 'demo')
                ->orLike('first_name', 'test')
                ->orLike('last_name', 'demo')
                ->orLike('last_name', 'test')
            ->groupEnd()
            ->get()
            ->getResultArray();

        $eventIds = array_values(array_unique(array_map(static fn(array $row): int => (int) $row['id'], $eventRows)));
        $userIds = array_values(array_unique(array_map(static fn(array $row): int => (int) $row['id'], $userRows)));
        $ticketBuilder = $db->table($ticketsTable)->select('id');

        if ($eventIds !== [] || $userIds !== []) {
            $ticketBuilder->groupStart();

            if ($eventIds !== []) {
                $ticketBuilder->whereIn('event_id', $eventIds);
            }

            if ($userIds !== []) {
                if ($eventIds !== []) {
                    $ticketBuilder->orWhereIn('user_id', $userIds);
                } else {
                    $ticketBuilder->whereIn('user_id', $userIds);
                }
            }

            $ticketBuilder->groupEnd();
            $ticketRows = $ticketBuilder->get()->getResultArray();
        } else {
            $ticketRows = [];
        }

        $ticketIds = array_values(array_unique(array_map(static fn(array $row): int => (int) $row['id'], $ticketRows)));
        $paymentRows = $ticketIds !== []
            ? $db->table($paymentsTable)
                ->select('id, paypal_transaction_id')
                ->whereIn('ticket_id', $ticketIds)
                ->get()
                ->getResultArray()
            : [];
        $paypalTransactionIds = array_values(array_filter(array_unique(array_map(
            static fn(array $row): string => (string) ($row['paypal_transaction_id'] ?? ''),
            $paymentRows
        ))));

        return [
            'counts' => [
                'events' => count($eventIds),
                'users' => count($userIds),
                'tickets' => count($ticketIds),
                'payments' => count($paymentRows),
                'paypal_captures' => count($paypalTransactionIds),
            ],
            'events' => array_map(static fn(array $row): array => [
                'id' => (int) $row['id'],
                'title' => (string) ($row['title'] ?? ''),
                'slug' => (string) ($row['slug'] ?? ''),
            ], $eventRows),
            'users' => array_map(static fn(array $row): array => [
                'id' => (int) $row['id'],
                'email' => (string) ($row['email'] ?? ''),
            ], $userRows),
            'event_ids' => $eventIds,
            'user_ids' => $userIds,
            'ticket_ids' => $ticketIds,
            'paypal_transaction_ids' => $paypalTransactionIds,
        ];
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
