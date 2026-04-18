<?php

namespace App\Controllers;

use App\Models\EmailVerificationModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;

class UserAdminController extends BaseController
{
    private UserModel $userModel;
    private EmailVerificationModel $emailVerificationModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->emailVerificationModel = new EmailVerificationModel();
    }

    public function index(): RedirectResponse|string
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $users = $this->userModel
            ->orderBy('role', 'DESC')
            ->orderBy('status', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        foreach ($users as &$user) {
            $user['login_locked_until'] = $this->getLoginLockedUntil((string) ($user['email'] ?? ''));
        }

        unset($user);

        return view('users/index', [
            'users'     => $users,
            'pageTitle' => lang('App.usersPageTitle'),
        ]);
    }

    public function create(): RedirectResponse|string
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        return $this->renderUserForm();
    }

    public function store(): RedirectResponse
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        [$payload, $error] = $this->buildUserPayload(null, true);
        if ($error !== null) {
            return redirect()->back()->withInput()->with('users_error', $error);
        }

        $payload['status'] = 'active';
        $userId = $this->userModel->insert($payload, true);

        $this->logAdminAction('user_create', 'user', [
            'target_user_id' => is_numeric($userId) ? (int) $userId : 0,
            'email' => $payload['email'],
            'role' => $payload['role'],
            'status' => $payload['status'],
        ]);

        return redirect()->to(base_url('users'))->with('users_info', lang('App.usersCreateSuccess'));
    }

    public function edit(int $userId): RedirectResponse|string
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $user = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersNotFound'));
        }

        return $this->renderUserForm($user);
    }

    public function update(int $userId): RedirectResponse
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $user = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersNotFound'));
        }

        [$payload, $error] = $this->buildUserPayload($user, false);
        if ($error !== null) {
            return redirect()->back()->withInput()->with('users_error', $error);
        }

        $newRole = (string) ($payload['role'] ?? $user['role']);
        if ((string) ($user['role'] ?? 'client') === 'admin' && $newRole !== 'admin' && $this->isLastActiveAdmin($user)) {
            return redirect()->back()->withInput()->with('users_error', lang('App.usersLastAdminError'));
        }

        $this->userModel->update($userId, $payload);
        $this->logAdminAction('user_update', 'user', [
            'target_user_id' => $userId,
            'email' => $payload['email'],
            'role' => $payload['role'],
            'password_changed' => array_key_exists('password', $payload),
        ]);
        $updatedUser = $this->userModel->find($userId);
        if ($updatedUser !== null) {
            $this->syncSessionUser($updatedUser);
        }

        return redirect()->to(base_url('users'))->with('users_info', lang('App.usersUpdateSuccess'));
    }

    public function block(int $userId): RedirectResponse
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $user = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersNotFound'));
        }

        if ((int) session()->get('user_id') === $userId) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersSelfBlockError'));
        }

        if (! $this->withAtomicAdminGuard(fn() => $this->userModel->update($userId, ['status' => 'banned']))) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersLastAdminError'));
        }

        $this->logAdminAction('user_block', 'user', [
            'target_user_id' => $userId,
            'email' => (string) ($user['email'] ?? ''),
        ]);

        return redirect()->to(base_url('users'))->with('users_info', lang('App.usersBlockSuccess'));
    }

    public function unblock(int $userId): RedirectResponse
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $user = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersNotFound'));
        }

        $this->userModel->update($userId, ['status' => 'active']);
        $this->logAdminAction('user_unblock', 'user', [
            'target_user_id' => $userId,
            'email' => (string) ($user['email'] ?? ''),
        ]);
        $updatedUser = $this->userModel->find($userId);
        if ($updatedUser !== null) {
            $this->syncSessionUser($updatedUser);
        }

        return redirect()->to(base_url('users'))->with('users_info', lang('App.usersUnblockSuccess'));
    }

    public function delete(int $userId): RedirectResponse
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $user = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersNotFound'));
        }

        if ((int) session()->get('user_id') === $userId) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersSelfDeleteError'));
        }

        if (! $this->withAtomicAdminGuard(fn() => $this->userModel->delete($userId))) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersLastAdminError'));
        }

        $this->logAdminAction('user_delete', 'user', [
            'target_user_id' => $userId,
            'email' => (string) ($user['email'] ?? ''),
            'role' => (string) ($user['role'] ?? ''),
            'status' => (string) ($user['status'] ?? ''),
        ]);

        return redirect()->to(base_url('users'))->with('users_info', lang('App.usersDeleteSuccess'));
    }


    public function resendVerification(int $userId): RedirectResponse
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $user = $this->userModel->find($userId);
        if ($user === null) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersNotFound'));
        }

        if ((string) ($user['status'] ?? '') !== 'inactive') {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersVerificationResendInvalidStatus'));
        }

        $selector = bin2hex(random_bytes(8));
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = Time::now()->addMinutes(10)->toDateTimeString();

        $this->emailVerificationModel->where('user_id', $userId)->delete();
        $this->emailVerificationModel->insert([
            'user_id' => $userId,
            'selector' => $selector,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at' => null,
        ]);

        if (! $this->sendVerificationEmail($userId, (string) ($user['email'] ?? ''), $selector, $token)) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersVerificationResendFailed'));
        }

        $this->logAdminAction('user_resend_verification', 'user', [
            'target_user_id' => $userId,
            'email' => (string) ($user['email'] ?? ''),
        ]);

        return redirect()->to(base_url('users'))->with('users_info', lang('App.usersVerificationResendSuccess'));
    }

    private function getLoginLockedUntil(string $email): ?int
    {
        $email = trim(strtolower($email));
        if ($email === '') {
            return null;
        }

        $cache = cache();
        $key = 'login_user_lock_' . sha1($email);
        $lockedUntil = (int) ($cache->get($key) ?? 0);

        if ($lockedUntil <= time()) {
            if ($lockedUntil > 0) {
                $cache->delete($key);
            }

            return null;
        }

        return $lockedUntil;
    }

    private function renderUserForm(?array $user = null): string
    {
        $isEditMode = $user !== null;

        return view('users/form', [
            'user' => $user,
            'isEditMode' => $isEditMode,
            'pageTitle' => $isEditMode ? lang('App.usersEditPageTitle') : lang('App.usersCreatePageTitle'),
        ]);
    }

    private function ensureAdmin(): ?RedirectResponse
    {
        if (session()->get('is_logged_in') !== true || (string) session()->get('user_role') !== 'admin') {
            return redirect()->to(base_url('/'))->with('login_error', lang('App.usersUnauthorized'));
        }

        return null;
    }

    private function buildUserPayload(?array $existingUser, bool $isCreate): array
    {
        $firstName = trim((string) $this->request->getPost('first_name'));
        $lastName = trim((string) $this->request->getPost('last_name'));
        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        $role = trim((string) $this->request->getPost('role'));

        if ($firstName === '' || $lastName === '' || $email === '' || $role === '') {
            return [null, lang('App.usersRequiredFields')];
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [null, lang('App.usersInvalidEmail')];
        }

        if (! in_array($role, ['admin', 'client'], true)) {
            return [null, lang('App.usersInvalidRole')];
        }

        $emailOwner = $this->userModel->where('email', $email)->first();
        if ($emailOwner !== null && (int) ($emailOwner['id'] ?? 0) !== (int) ($existingUser['id'] ?? 0)) {
            return [null, lang('App.usersEmailExists')];
        }

        if ($isCreate && strlen($password) < 8) {
            return [null, lang('App.usersPasswordRequired')];
        }

        if (! $isCreate && $password !== '' && strlen($password) < 8) {
            return [null, lang('App.passwordTooShort')];
        }

        $payload = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'role' => $role,
        ];

        if ($password !== '') {
            $payload['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        return [$payload, null];
    }

    private function isLastActiveAdmin(array $user): bool
    {
        if ((string) ($user['role'] ?? 'client') !== 'admin' || (string) ($user['status'] ?? 'inactive') !== 'active') {
            return false;
        }

        return $this->userModel->where('role', 'admin')->where('status', 'active')->countAllResults() <= 1;
    }

    private function withAtomicAdminGuard(callable $operation): bool
    {
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $operation();

            $remaining = $this->userModel
                ->where('role', 'admin')
                ->where('status', 'active')
                ->countAllResults();

            if ($remaining < 1) {
                $db->transRollback();
                return false;
            }

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }

    private function syncSessionUser(array $user): void
    {
        if ((int) session()->get('user_id') !== (int) ($user['id'] ?? 0)) {
            return;
        }

        session()->set([
            'user_name' => trim(((string) ($user['first_name'] ?? '')) . ' ' . ((string) ($user['last_name'] ?? ''))),
            'user_email' => (string) ($user['email'] ?? ''),
            'user_role' => (string) ($user['role'] ?? 'client'),
        ]);
    }
}
