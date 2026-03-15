<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;

class UserAdminController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
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

        return view('users/index', [
            'users' => $users,
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
        $this->userModel->insert($payload);

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

        if ($this->isLastActiveAdmin($user)) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersLastAdminError'));
        }

        $this->userModel->update($userId, ['status' => 'banned']);

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

        if ($this->isLastActiveAdmin($user)) {
            return redirect()->to(base_url('users'))->with('users_error', lang('App.usersLastAdminError'));
        }

        $this->userModel->delete($userId);

        return redirect()->to(base_url('users'))->with('users_info', lang('App.usersDeleteSuccess'));
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

        if ($isCreate && strlen($password) < 6) {
            return [null, lang('App.usersPasswordRequired')];
        }

        if (! $isCreate && $password !== '' && strlen($password) < 6) {
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

        $activeAdmins = $this->userModel
            ->where('role', 'admin')
            ->where('status', 'active')
            ->countAllResults();

        return $activeAdmins <= 1;
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
