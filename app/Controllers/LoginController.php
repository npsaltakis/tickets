<?php

namespace App\Controllers;

use App\Models\PasswordResetModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;

class LoginController extends BaseController
{
    private UserModel $userModel;
    private PasswordResetModel $passwordResetModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->passwordResetModel = new PasswordResetModel();
    }

    public function index(): string|RedirectResponse
    {
        if (session()->get('is_logged_in') === true) {
            return redirect()->to(base_url('/'));
        }

        return view('auth/login', [
            'pageTitle' => lang('App.loginPageTitle'),
        ]);
    }

    public function authenticate(): RedirectResponse
    {
        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        if ($email === '' || $password === '') {
            return redirect()->back()->withInput()->with('login_error', lang('App.loginRequiredFields'));
        }

        $user = $this->userModel
            ->where('email', $email)
            ->where('status', 'active')
            ->first();

        if (empty($user) || !password_verify($password, (string) $user['password'])) {
            return redirect()->back()->withInput()->with('login_error', lang('App.loginInvalidCredentials'));
        }

        session()->set([
            'is_logged_in' => true,
            'user_id' => $user['id'],
            'user_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'user_email' => $user['email'],
            'user_role' => $user['role'] ?? 'client',
        ]);

        return redirect()->to(base_url('/'));
    }

    public function register(): string|RedirectResponse
    {
        if (session()->get('is_logged_in') === true) {
            return redirect()->to(base_url('/'));
        }

        return view('auth/register', [
            'pageTitle' => lang('App.registerPageTitle'),
        ]);
    }

    public function storeRegister(): RedirectResponse
    {
        $firstName = trim((string) $this->request->getPost('first_name'));
        $lastName = trim((string) $this->request->getPost('last_name'));
        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $confirmPassword === '') {
            return redirect()->back()->withInput()->with('register_error', lang('App.registerRequiredFields'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('register_error', lang('App.invalidEmail'));
        }

        if (strlen($password) < 6) {
            return redirect()->back()->withInput()->with('register_error', lang('App.passwordTooShort'));
        }

        if ($password !== $confirmPassword) {
            return redirect()->back()->withInput()->with('register_error', lang('App.passwordsDoNotMatch'));
        }

        $existingUser = $this->userModel->where('email', $email)->first();

        if (!empty($existingUser)) {
            return redirect()->back()->withInput()->with('register_error', lang('App.emailAlreadyExists'));
        }

        $this->userModel->insert([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'client',
            'status' => 'active',
        ]);

        return redirect()->to(base_url('login'))->with('login_info', lang('App.registerSuccess'));
    }

    public function lostPassword(): string|RedirectResponse
    {
        if (session()->get('is_logged_in') === true) {
            return redirect()->to(base_url('/'));
        }

        return view('auth/lost_password', [
            'pageTitle' => lang('App.lostPasswordPageTitle'),
        ]);
    }

    public function sendResetLink(): RedirectResponse
    {
        $email = trim((string) $this->request->getPost('email'));

        if ($email === '') {
            return redirect()->back()->withInput()->with('lost_error', lang('App.lostRequiredEmail'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('lost_error', lang('App.invalidEmail'));
        }

        $user = $this->userModel
            ->where('email', $email)
            ->where('status', 'active')
            ->first();

        if (empty($user)) {
            return redirect()->back()->with('lost_info', lang('App.resetLinkSentGeneric'));
        }

        $selector = bin2hex(random_bytes(8));
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = Time::now()->addMinutes(10)->toDateTimeString();

        $this->passwordResetModel->where('user_id', $user['id'])->delete();

        $this->passwordResetModel->insert([
            'user_id' => $user['id'],
            'selector' => $selector,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at' => null,
        ]);

        $resetUrl = base_url('reset-password?selector=' . urlencode($selector) . '&token=' . urlencode($token));

        $emailService = service('email');
        $emailService->setTo($user['email']);
        $emailService->setSubject(lang('App.resetEmailSubject'));
        $emailService->setMessage(
            lang('App.resetEmailIntro') . "\n\n" .
            $resetUrl . "\n\n" .
            lang('App.resetEmailExpiry')
        );

        if (! $emailService->send()) {
            return redirect()->back()->withInput()->with('lost_error', lang('App.emailSendFailed'));
        }

        return redirect()->back()->with('lost_info', lang('App.resetLinkSent'));
    }

    public function resetPasswordForm(): string|RedirectResponse
    {
        if (session()->get('is_logged_in') === true) {
            return redirect()->to(base_url('/'));
        }

        $selector = trim((string) $this->request->getGet('selector'));
        $token = trim((string) $this->request->getGet('token'));

        if ($selector === '' || $token === '') {
            return redirect()->to(base_url('lost-password'))->with('lost_error', lang('App.invalidOrExpiredToken'));
        }

        $reset = $this->passwordResetModel->where('selector', $selector)->first();

        if (! $this->isValidResetToken($reset, $token)) {
            return redirect()->to(base_url('lost-password'))->with('lost_error', lang('App.invalidOrExpiredToken'));
        }

        return view('auth/reset_password', [
            'pageTitle' => lang('App.resetPasswordPageTitle'),
            'selector' => $selector,
            'token' => $token,
        ]);
    }

    public function updatePasswordWithToken(): RedirectResponse
    {
        $selector = trim((string) $this->request->getPost('selector'));
        $token = trim((string) $this->request->getPost('token'));
        $newPassword = (string) $this->request->getPost('new_password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        if ($selector === '' || $token === '') {
            return redirect()->to(base_url('lost-password'))->with('lost_error', lang('App.invalidOrExpiredToken'));
        }

        if ($newPassword === '' || $confirmPassword === '') {
            return redirect()->back()->withInput()->with('reset_error', lang('App.lostRequiredFields'));
        }

        if (strlen($newPassword) < 6) {
            return redirect()->back()->withInput()->with('reset_error', lang('App.passwordTooShort'));
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->withInput()->with('reset_error', lang('App.passwordsDoNotMatch'));
        }

        $reset = $this->passwordResetModel->where('selector', $selector)->first();

        if (! $this->isValidResetToken($reset, $token)) {
            return redirect()->to(base_url('lost-password'))->with('lost_error', lang('App.invalidOrExpiredToken'));
        }

        $user = $this->userModel->find($reset['user_id']);

        if (empty($user)) {
            return redirect()->to(base_url('lost-password'))->with('lost_error', lang('App.userNotFound'));
        }

        $this->userModel->update($user['id'], [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $this->passwordResetModel->update($reset['id'], [
            'used_at' => Time::now()->toDateTimeString(),
        ]);

        return redirect()->to(base_url('login'))->with('login_info', lang('App.passwordResetSuccess'));
    }

    private function isValidResetToken(?array $reset, string $token): bool
    {
        if (empty($reset)) {
            return false;
        }

        if (! empty($reset['used_at'])) {
            return false;
        }

        if ((string) $reset['expires_at'] < Time::now()->toDateTimeString()) {
            return false;
        }

        return hash_equals((string) $reset['token_hash'], hash('sha256', $token));
    }
}
