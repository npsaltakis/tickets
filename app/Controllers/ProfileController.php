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

    public function requestEmailChange(): RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'));
        }

        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('/'));
        }

        $newEmail = strtolower(trim((string) $this->request->getPost('new_email')));

        if (! password_verify((string) $this->request->getPost('email_password'), (string) ($user['password'] ?? ''))) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.profileCurrentPasswordWrong'));
        }

        if (filter_var($newEmail, FILTER_VALIDATE_EMAIL) === false || mb_strlen($newEmail) > 191) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.usersInvalidEmail'));
        }

        if (strtolower((string) $user['email']) === $newEmail || $this->userModel->where('email', $newEmail)->first() !== null) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.profileEmailTaken'));
        }

        $cache = cache();
        $key   = 'email_change_rate_' . $userId;
        $count = (int) ($cache->get($key) ?? 0);
        if ($count >= 3) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.bookingRateLimited'));
        }
        $cache->save($key, $count + 1, 3600);

        $selector = bin2hex(random_bytes(8));
        $token    = bin2hex(random_bytes(32));
        $db       = db_connect();

        $db->table($db->prefixTable('email_changes'))->where('user_id', $userId)->where('used_at', null)->delete();
        $db->table($db->prefixTable('email_changes'))->insert([
            'user_id'    => $userId,
            'new_email'  => $newEmail,
            'selector'   => $selector,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $url     = base_url('profile/confirm-email') . '?' . http_build_query(['selector' => $selector, 'token' => $token]);
        $subject = $this->bilingualSubject('App.emailChangeSubject');

        try {
            $mail = service('email');
            $mail->clear();
            $mail->setTo($newEmail);
            $mail->setSubject($subject);
            $mail->setMailType('html');
            $mail->setMessage($this->buildBilingualActionEmailHtml(
                [$this->localizedLine('App.emailChangeBody', [], 'el')],
                [$this->localizedLine('App.emailChangeBody', [], 'en')],
                $url,
                $this->localizedLine('App.emailChangeButton', [], 'el'),
                $this->localizedLine('App.emailChangeButton', [], 'en'),
                $subject
            ));
            $sent = $mail->send(false);
        } catch (\Throwable) {
            $sent = false;
        }

        return redirect()->to(base_url('profile'))->with($sent ? 'profile_info' : 'profile_error', lang($sent ? 'App.emailChangeSent' : 'App.emailChangeFailed'));
    }

    public function confirmEmailChange(): RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
        }

        $db  = db_connect();
        $row = $db->table($db->prefixTable('email_changes'))
            ->where('selector', trim((string) $this->request->getGet('selector')))
            ->get()
            ->getRowArray();

        $token = (string) $this->request->getGet('token');

        if (
            empty($row)
            || $row['used_at'] !== null
            || strtotime((string) $row['expires_at']) < time()
            || ! hash_equals((string) $row['token_hash'], hash('sha256', $token))
            || (int) $row['user_id'] !== (int) session()->get('user_id')
        ) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.invalidOrExpiredVerificationToken'));
        }

        if ($this->userModel->where('email', (string) $row['new_email'])->first() !== null) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.profileEmailTaken'));
        }

        $this->userModel->update((int) $row['user_id'], ['email' => (string) $row['new_email']]);
        $db->table($db->prefixTable('email_changes'))->where('id', (int) $row['id'])->update(['used_at' => date('Y-m-d H:i:s')]);
        session()->set('user_email', (string) $row['new_email']);

        return redirect()->to(base_url('profile'))->with('profile_info', lang('App.emailChangeDone'));
    }

    public function twoFactorSetup(): RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'));
        }

        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);

        if ($user !== null && (int) ($user['totp_enabled'] ?? 0) !== 1) {
            $this->userModel->update($userId, ['totp_secret' => \App\Libraries\Totp::generateSecret(), 'totp_enabled' => 0]);
        }

        return redirect()->to(base_url('profile'));
    }

    public function twoFactorQr(): ResponseInterface
    {
        $user = session()->get('is_logged_in') === true ? $this->userModel->find((int) session()->get('user_id')) : null;

        if (empty($user) || (int) ($user['totp_enabled'] ?? 0) === 1 || (string) ($user['totp_secret'] ?? '') === '') {
            return $this->response->setStatusCode(404);
        }

        $uri = \App\Libraries\Totp::provisioningUri((string) $user['totp_secret'], (string) $user['email'], lang('App.siteTitle'));

        return $this->response
            ->setHeader('Content-Type', 'image/png')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($this->buildTicketQrImageContent($uri));
    }

    public function twoFactorEnable(): RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'));
        }

        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);

        if (empty($user) || (string) ($user['totp_secret'] ?? '') === '') {
            return redirect()->to(base_url('profile'));
        }

        if (! \App\Libraries\Totp::verify((string) $user['totp_secret'], (string) $this->request->getPost('code'))) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.twoFactorInvalid'));
        }

        $this->userModel->update($userId, ['totp_enabled' => 1]);

        return redirect()->to(base_url('profile'))->with('profile_info', lang('App.twoFactorEnabled'));
    }

    public function twoFactorDisable(): RedirectResponse
    {
        if (session()->get('is_logged_in') !== true) {
            return redirect()->to(base_url('login'));
        }

        $userId = (int) session()->get('user_id');
        $user   = $this->userModel->find($userId);

        if (
            empty($user)
            || ! password_verify((string) $this->request->getPost('disable_password'), (string) ($user['password'] ?? ''))
            || ! \App\Libraries\Totp::verify((string) ($user['totp_secret'] ?? ''), (string) $this->request->getPost('code'))
        ) {
            return redirect()->to(base_url('profile'))->with('profile_error', lang('App.twoFactorInvalid'));
        }

        $this->userModel->update($userId, ['totp_enabled' => 0, 'totp_secret' => null]);

        return redirect()->to(base_url('profile'))->with('profile_info', lang('App.twoFactorDisabled'));
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
