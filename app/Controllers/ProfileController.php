<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class ProfileController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index(): string|RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
        }

        $user = $this->userModel->find((int) session()->get('user_id'));
        if ($user === null) {
            return redirect()->to(base_url('/'));
        }

        return view('auth/profile', [
            'user'      => $user,
            'pageTitle' => lang('App.profilePageTitle'),
        ]);
    }

    /**
     * GDPR: lets users download everything the system stores about them.
     */
    public function exportData(): ResponseInterface|RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
        }

        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('/'));
        }

        unset($user['password']);

        $db      = db_connect();
        $tickets = $db->table($db->prefixTable('tickets') . ' t')
            ->select('t.ticket_code, t.status, t.payment_status, t.donation_amount, t.created_at, t.checked_in_at, e.title AS event_title, e.start_date AS event_start_date')
            ->join($db->prefixTable('events') . ' e', 'e.id = t.event_id', 'left')
            ->where('t.user_id', $userId)
            ->orderBy('t.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $waitlist = $db->table($db->prefixTable('waitlist'))->where('user_id', $userId)->get()->getResultArray();

        $payload = [
            'exported_at' => date('c'),
            'account'     => $user,
            'tickets'     => $tickets,
            'waitlist'    => $waitlist,
        ];

        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="my-data-' . date('Ymd') . '.json"')
            ->setBody(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * GDPR: anonymises the account. Ticket/payment rows stay for accounting but lose their personal link.
     */
    public function deleteAccount(): RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'));
        }

        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('/'));
        }

        if (! password_verify((string) $this->request->getPost('delete_password'), (string) ($user['password'] ?? ''))) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.profileCurrentPasswordWrong'));
        }

        if ((string) ($user['role'] ?? '') === 'admin') {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.profileDeleteAdminBlocked'));
        }

        $db       = db_connect();
        $upcoming = $db->table($db->prefixTable('tickets') . ' t')
            ->join($db->prefixTable('events') . ' e', 'e.id = t.event_id')
            ->where('t.user_id', $userId)
            ->where('t.status', 'valid')
            ->groupStart()->where('e.end_date', null)->orWhere('e.end_date >=', date('Y-m-d H:i:s'))->groupEnd()
            ->countAllResults();

        if ($upcoming > 0) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.profileDeleteHasTickets'));
        }

        $db->table($db->prefixTable('waitlist'))->where('user_id', $userId)->delete();

        $this->userModel->update($userId, [
            'first_name' => 'Deleted',
            'last_name'  => 'User',
            'email'      => 'deleted-' . $userId . '-' . bin2hex(random_bytes(4)) . '@deleted.invalid',
            'password'   => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'status'     => 'banned',
        ]);

        session()->destroy();

        return redirect()->to(base_url('/'))->with('login_info', lang('App.profileDeleted'));
    }

    public function update(): RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'));
        }

        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('/'));
        }

        $firstName   = trim((string) $this->request->getPost('first_name'));
        $lastName    = trim((string) $this->request->getPost('last_name'));
        $currentPass = (string) $this->request->getPost('current_password');
        $newPass     = (string) $this->request->getPost('new_password');
        $confirmPass = (string) $this->request->getPost('confirm_password');

        if ($firstName === '' || $lastName === '') {
            return redirect()->back()->withInput()->with('profile_error', lang('App.profileNameRequired'));
        }

        $payload = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ];

        if ($newPass !== '') {
            if ($currentPass === '' || ! password_verify($currentPass, (string) ($user['password'] ?? ''))) {
                return redirect()->back()->withInput()->with('profile_error', lang('App.profileCurrentPasswordWrong'));
            }
            if (strlen($newPass) < 8) {
                return redirect()->back()->withInput()->with('profile_error', lang('App.passwordTooShort'));
            }
            if ($newPass !== $confirmPass) {
                return redirect()->back()->withInput()->with('profile_error', lang('App.profilePasswordMismatch'));
            }
            $payload['password'] = password_hash($newPass, PASSWORD_DEFAULT);
        }

        $this->userModel->update($userId, $payload);

        session()->set([
            'user_name' => trim($firstName . ' ' . $lastName),
        ]);

        return redirect()->to(base_url('profile'))->with('profile_info', lang('App.profileUpdated'));
    }
}
