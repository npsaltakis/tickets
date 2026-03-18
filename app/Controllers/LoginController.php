<?php

namespace App\Controllers;

use App\Models\EmailVerificationModel;
use App\Models\PasswordResetModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use Throwable;

class LoginController extends BaseController
{
    private UserModel $userModel;
    private PasswordResetModel $passwordResetModel;
    private EmailVerificationModel $emailVerificationModel;
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCK_SECONDS = 900;
    private const RESET_MAX_ATTEMPTS = 3;
    private const RESET_LOCK_SECONDS = 900;
    private const REGISTER_MAX_ATTEMPTS = 5;
    private const REGISTER_LOCK_SECONDS = 1800;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->passwordResetModel = new PasswordResetModel();
        $this->emailVerificationModel = new EmailVerificationModel();
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

        $lockInfo = $this->getLoginLockInfo($email);
        if ($lockInfo !== null) {
            return redirect()->back()->withInput()->with('login_error', strtr(lang('App.loginBlocked'), [
                '{minutes}' => (string) $lockInfo['minutes'],
            ]));
        }

        $user = $this->userModel->where('email', $email)->first();

        if (empty($user) || !password_verify($password, (string) $user['password'])) {
            $this->recordFailedLoginAttempt($email);

            return redirect()->back()->withInput()->with('login_error', lang('App.loginInvalidCredentials'));
        }

        if ((string) ($user['status'] ?? '') !== 'active') {
            if ((string) ($user['status'] ?? '') === 'inactive') {
                return redirect()->back()->withInput()->with('login_error', lang('App.verifyEmailRequired'));
            }

            return redirect()->back()->withInput()->with('login_error', lang('App.loginInvalidCredentials'));
        }

        $this->clearLoginAttempts($email);

        $session = session();
        $session->regenerate();
        $session->set([
            'is_logged_in' => true,
            'user_id' => $user['id'],
            'user_name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'user_email' => $user['email'],
            'user_role' => $user['role'] ?? 'client',
        ]);

        return redirect()->to(base_url('/'));
    }

    public function logout(): RedirectResponse
    {
        session()->destroy();

        return redirect()->to(base_url('login'))->with('login_info', lang('App.logoutSuccess'));
    }

    public function register(): string|RedirectResponse
    {
        if (session()->get('is_logged_in') === true) {
            return redirect()->to(base_url('/'));
        }

        return view('auth/register', [
            'pageTitle' => lang('App.registerPageTitle'),
            'turnstileSiteKey' => (string) env('turnstile.siteKey', ''),
        ]);
    }

    public function storeRegister(): RedirectResponse
    {
        $firstName = trim((string) $this->request->getPost('first_name'));
        $lastName = trim((string) $this->request->getPost('last_name'));
        $email = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');
        $turnstileToken = trim((string) $this->request->getPost('cf-turnstile-response'));

        $registerLockInfo = $this->getRegisterLockInfo();
        if ($registerLockInfo !== null) {
            return redirect()->back()->withInput()->with('register_error', strtr(lang('App.registerBlocked'), [
                '{minutes}' => (string) $registerLockInfo['minutes'],
            ]));
        }

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $confirmPassword === '') {
            return redirect()->back()->withInput()->with('register_error', lang('App.registerRequiredFields'));
        }

        if (! $this->verifyTurnstileToken($turnstileToken)) {
            $this->recordFailedRegisterAttempt();
            return redirect()->back()->withInput()->with('register_error', lang('App.turnstileValidationFailed'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->recordFailedRegisterAttempt();
            return redirect()->back()->withInput()->with('register_error', lang('App.invalidEmail'));
        }

        if (strlen($password) < 6) {
            $this->recordFailedRegisterAttempt();
            return redirect()->back()->withInput()->with('register_error', lang('App.passwordTooShort'));
        }

        if ($password !== $confirmPassword) {
            $this->recordFailedRegisterAttempt();
            return redirect()->back()->withInput()->with('register_error', lang('App.passwordsDoNotMatch'));
        }

        $existingUser = $this->userModel->where('email', $email)->first();

        if (!empty($existingUser)) {
            $this->recordFailedRegisterAttempt();
            return redirect()->back()->withInput()->with('register_error', lang('App.emailAlreadyExists'));
        }

        $this->clearRegisterAttempts();

        $userId = $this->userModel->insert([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'client',
            'status' => 'inactive',
        ], true);

        if (!is_int($userId) && !ctype_digit((string) $userId)) {
            return redirect()->back()->withInput()->with('register_error', lang('App.registerEmailVerificationFailed'));
        }

        $userId = (int) $userId;
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

        $verificationSent = $this->sendVerificationEmail($userId, $email, $selector, $token);

        if (! $verificationSent) {
            return redirect()->to(base_url('login'))
                ->with('login_error', lang('App.registerEmailVerificationFailedKeepUser'));
        }

        return redirect()->to(base_url('login'))
            ->with('login_info', lang('App.registerVerificationSent'));
    }

    public function verifyEmail(): RedirectResponse
    {
        $selector = trim((string) $this->request->getGet('selector'));
        $token = trim((string) $this->request->getGet('token'));

        if ($selector === '' || $token === '') {
            return redirect()->to(base_url('login'))->with('login_error', lang('App.invalidOrExpiredVerificationToken'));
        }

        $verification = $this->emailVerificationModel->where('selector', $selector)->first();

        if (! $this->isValidVerificationToken($verification, $token)) {
            return redirect()->to(base_url('login'))->with('login_error', lang('App.invalidOrExpiredVerificationToken'));
        }

        $user = $this->userModel->find($verification['user_id']);

        if (empty($user)) {
            return redirect()->to(base_url('login'))->with('login_error', lang('App.userNotFound'));
        }

        $this->userModel->update($user['id'], [
            'status' => 'active',
        ]);

        $this->emailVerificationModel->update($verification['id'], [
            'used_at' => Time::now()->toDateTimeString(),
        ]);

        return redirect()->to(base_url('login'))->with('login_info', lang('App.verifyEmailSuccess'));
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

        $lockInfo = $this->getResetLockInfo($email);
        if ($lockInfo !== null) {
            return redirect()->back()->withInput()->with('lost_error', strtr(lang('App.resetBlocked'), [
                '{minutes}' => (string) $lockInfo['minutes'],
            ]));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->recordFailedResetAttempt($email);
            return redirect()->back()->withInput()->with('lost_error', lang('App.invalidEmail'));
        }

        $user = $this->userModel->where('email', $email)->where('status', 'active')->first();

        if (empty($user)) {
            $this->recordFailedResetAttempt($email);
            return redirect()->back()->withInput()->with('lost_info', lang('App.resetLinkSentGeneric'));
        }

        $userId = (int) $user['id'];
        $selector = bin2hex(random_bytes(8));
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = Time::now()->addMinutes(10)->toDateTimeString();

        $this->passwordResetModel->where('user_id', $userId)->delete();

        $this->passwordResetModel->insert([
            'user_id'    => $userId,
            'selector'   => $selector,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at'    => null,
        ]);

        $sent = $this->sendResetEmail($userId, (string) $user['email'], $selector, $token);

        if (! $sent) {
            return redirect()->back()->withInput()->with('lost_error', lang('App.emailSendFailed'));
        }

        $this->clearResetAttempts($email);

        return redirect()->to(base_url('login'))->with('login_info', lang('App.resetLinkSentGeneric'));
    }

    public function resetPasswordForm(): string|RedirectResponse
    {
        $selector = trim((string) $this->request->getGet('selector'));
        $token = trim((string) $this->request->getGet('token'));

        if ($selector === '' || $token === '') {
            return redirect()->to(base_url('login'))->with('login_error', lang('App.invalidOrExpiredToken'));
        }

        $reset = $this->passwordResetModel->where('selector', $selector)->first();

        if (! $this->isValidResetToken($reset, $token)) {
            return redirect()->to(base_url('login'))->with('login_error', lang('App.invalidOrExpiredToken'));
        }

        return view('auth/reset_password', [
            'pageTitle' => lang('App.resetPasswordPageTitle'),
            'selector'  => $selector,
            'token'     => $token,
        ]);
    }

    public function updatePasswordWithToken(): RedirectResponse
    {
        $selector = trim((string) $this->request->getPost('selector'));
        $token = trim((string) $this->request->getPost('token'));
        $newPassword = (string) $this->request->getPost('new_password');
        $confirmPassword = (string) $this->request->getPost('confirm_password');

        if ($selector === '' || $token === '' || $newPassword === '' || $confirmPassword === '') {
            return redirect()->back()->with('reset_error', lang('App.lostRequiredFields'));
        }

        if (strlen($newPassword) < 6) {
            return redirect()->back()->with('reset_error', lang('App.passwordTooShort'));
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('reset_error', lang('App.passwordsDoNotMatch'));
        }

        $reset = $this->passwordResetModel->where('selector', $selector)->first();

        if (! $this->isValidResetToken($reset, $token)) {
            return redirect()->to(base_url('login'))->with('login_error', lang('App.invalidOrExpiredToken'));
        }

        $this->userModel->update((int) $reset['user_id'], [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);

        $this->passwordResetModel->update((int) $reset['id'], [
            'used_at' => Time::now()->toDateTimeString(),
        ]);

        return redirect()->to(base_url('login'))->with('login_info', lang('App.passwordResetSuccess'));
    }

    private function sendResetEmail(int $userId, string $email, string $selector, string $token): bool
    {
        $resetUrl = base_url('reset-password?selector=' . urlencode($selector) . '&token=' . urlencode($token));

        $emailService = service('email');
        $emailService->setTo($email);
        $emailService->setSubject($this->bilingualSubject('App.resetEmailSubject'));
        $emailService->setMailType('html');
        $emailService->setMessage(
            '<p>' . esc($this->localizedLine('App.resetEmailGreeting', [], 'el')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.resetEmailRequestNotice', [], 'el')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.resetEmailActionText', [], 'el')) . '</p>'
            . '<p><a href="' . esc($resetUrl, 'attr') . '">' . esc($resetUrl) . '</a></p>'
            . '<p>' . esc($this->localizedLine('App.resetEmailExpiry', [], 'el')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.resetEmailIgnoreNotice', [], 'el')) . '</p>'
            . '<p>' . nl2br(esc($this->localizedLine('App.resetEmailSignature', [], 'el'))) . '</p>'
            . '<hr>'
            . '<p>' . esc($this->localizedLine('App.resetEmailGreeting', [], 'en')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.resetEmailRequestNotice', [], 'en')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.resetEmailActionText', [], 'en')) . '</p>'
            . '<p><a href="' . esc($resetUrl, 'attr') . '">' . esc($resetUrl) . '</a></p>'
            . '<p>' . esc($this->localizedLine('App.resetEmailExpiry', [], 'en')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.resetEmailIgnoreNotice', [], 'en')) . '</p>'
            . '<p>' . nl2br(esc($this->localizedLine('App.resetEmailSignature', [], 'en'))) . '</p>'
        );

        if ($emailService->send()) {
            return true;
        }

        log_message('error', 'Password reset email send failed for user {userId} ({email}).', [
            'userId' => $userId,
            'email'  => $email,
        ]);

        return false;
    }

    private function getLoginCacheKey(string $email, string $suffix): string
    {
        return 'login_' . $suffix . '_' . sha1(strtolower($email) . '|' . $this->request->getIPAddress());
    }

    private function getLoginLockInfo(string $email): ?array
    {
        $cache = cache();
        $lockedUntil = (int) ($cache->get($this->getLoginCacheKey($email, 'lock')) ?? 0);
        $summaryKey = $this->getLoginEmailLockKey($email);

        if ($lockedUntil <= time()) {
            if ($lockedUntil > 0) {
                $cache->delete($this->getLoginCacheKey($email, 'lock'));
            }

            $summaryLockedUntil = (int) ($cache->get($summaryKey) ?? 0);
            if ($summaryLockedUntil > 0 && $summaryLockedUntil <= time()) {
                $cache->delete($summaryKey);
            }

            return null;
        }

        $summaryLockedUntil = (int) ($cache->get($summaryKey) ?? 0);
        if ($summaryLockedUntil < $lockedUntil) {
            $cache->save($summaryKey, $lockedUntil, max(1, $lockedUntil - time()));
        }

        return [
            'until' => $lockedUntil,
            'minutes' => max(1, (int) ceil(($lockedUntil - time()) / 60)),
        ];
    }

    private function recordFailedLoginAttempt(string $email): void
    {
        $cache = cache();
        $attemptKey = $this->getLoginCacheKey($email, 'attempts');
        $lockKey = $this->getLoginCacheKey($email, 'lock');
        $summaryKey = $this->getLoginEmailLockKey($email);
        $attempts = (int) ($cache->get($attemptKey) ?? 0) + 1;

        if ($attempts >= self::LOGIN_MAX_ATTEMPTS) {
            $lockedUntil = time() + self::LOGIN_LOCK_SECONDS;
            $cache->delete($attemptKey);
            $cache->save($lockKey, $lockedUntil, self::LOGIN_LOCK_SECONDS);
            $cache->save($summaryKey, $lockedUntil, self::LOGIN_LOCK_SECONDS);
            return;
        }

        $cache->save($attemptKey, $attempts, self::LOGIN_LOCK_SECONDS);
    }

    private function clearLoginAttempts(string $email): void
    {
        $cache = cache();
        $cache->delete($this->getLoginCacheKey($email, 'attempts'));
        $cache->delete($this->getLoginCacheKey($email, 'lock'));
        $cache->delete($this->getLoginEmailLockKey($email));
    }

    private function getLoginEmailLockKey(string $email): string
    {
        return 'login_user_lock_' . sha1(strtolower($email));
    }

    private function getRegisterCacheKey(string $suffix): string
    {
        return 'register_' . $suffix . '_' . sha1($this->request->getIPAddress());
    }

    private function getRegisterLockInfo(): ?array
    {
        $cache = cache();
        $lockedUntil = (int) ($cache->get($this->getRegisterCacheKey('lock')) ?? 0);

        if ($lockedUntil <= time()) {
            if ($lockedUntil > 0) {
                $cache->delete($this->getRegisterCacheKey('lock'));
            }

            return null;
        }

        return [
            'until' => $lockedUntil,
            'minutes' => max(1, (int) ceil(($lockedUntil - time()) / 60)),
        ];
    }

    private function recordFailedRegisterAttempt(): void
    {
        $cache = cache();
        $attemptKey = $this->getRegisterCacheKey('attempts');
        $lockKey = $this->getRegisterCacheKey('lock');
        $attempts = (int) ($cache->get($attemptKey) ?? 0) + 1;

        if ($attempts >= self::REGISTER_MAX_ATTEMPTS) {
            $cache->delete($attemptKey);
            $cache->save($lockKey, time() + self::REGISTER_LOCK_SECONDS, self::REGISTER_LOCK_SECONDS);
            return;
        }

        $cache->save($attemptKey, $attempts, self::REGISTER_LOCK_SECONDS);
    }

    private function clearRegisterAttempts(): void
    {
        $cache = cache();
        $cache->delete($this->getRegisterCacheKey('attempts'));
        $cache->delete($this->getRegisterCacheKey('lock'));
    }

    private function getResetCacheKey(string $email, string $suffix): string
    {
        return 'reset_' . $suffix . '_' . sha1(strtolower($email) . '|' . $this->request->getIPAddress());
    }

    private function getResetLockInfo(string $email): ?array
    {
        $cache = cache();
        $lockedUntil = (int) ($cache->get($this->getResetCacheKey($email, 'lock')) ?? 0);

        if ($lockedUntil <= time()) {
            if ($lockedUntil > 0) {
                $cache->delete($this->getResetCacheKey($email, 'lock'));
            }

            return null;
        }

        return [
            'until' => $lockedUntil,
            'minutes' => max(1, (int) ceil(($lockedUntil - time()) / 60)),
        ];
    }

    private function recordFailedResetAttempt(string $email): void
    {
        $cache = cache();
        $attemptKey = $this->getResetCacheKey($email, 'attempts');
        $lockKey = $this->getResetCacheKey($email, 'lock');
        $attempts = (int) ($cache->get($attemptKey) ?? 0) + 1;

        if ($attempts >= self::RESET_MAX_ATTEMPTS) {
            $cache->delete($attemptKey);
            $cache->save($lockKey, time() + self::RESET_LOCK_SECONDS, self::RESET_LOCK_SECONDS);
            return;
        }

        $cache->save($attemptKey, $attempts, self::RESET_LOCK_SECONDS);
    }

    private function clearResetAttempts(string $email): void
    {
        $cache = cache();
        $cache->delete($this->getResetCacheKey($email, 'attempts'));
        $cache->delete($this->getResetCacheKey($email, 'lock'));
    }

    private function sendVerificationEmail(int $userId, string $email, string $selector, string $token): bool
    {
        $verificationUrl = base_url('verify-email?selector=' . urlencode($selector) . '&token=' . urlencode($token));

        $emailService = service('email');
        $emailService->setTo($email);
        $emailService->setSubject($this->bilingualSubject('App.verifyEmailSubject'));
        $emailService->setMailType('html');
        $emailService->setMessage(
            '<p>' . esc($this->localizedLine('App.verifyEmailGreeting', [], 'el')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.verifyEmailRequestNotice', [], 'el')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.verifyEmailActionText', [], 'el')) . '</p>'
            . '<p><a href="' . esc($verificationUrl, 'attr') . '">' . esc($verificationUrl) . '</a></p>'
            . '<p>' . esc($this->localizedLine('App.verifyEmailExpiry', [], 'el')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.verifyEmailIgnoreNotice', [], 'el')) . '</p>'
            . '<p>' . nl2br(esc($this->localizedLine('App.verifyEmailSignature', [], 'el'))) . '</p>'
            . '<hr>'
            . '<p>' . esc($this->localizedLine('App.verifyEmailGreeting', [], 'en')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.verifyEmailRequestNotice', [], 'en')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.verifyEmailActionText', [], 'en')) . '</p>'
            . '<p><a href="' . esc($verificationUrl, 'attr') . '">' . esc($verificationUrl) . '</a></p>'
            . '<p>' . esc($this->localizedLine('App.verifyEmailExpiry', [], 'en')) . '</p>'
            . '<p>' . esc($this->localizedLine('App.verifyEmailIgnoreNotice', [], 'en')) . '</p>'
            . '<p>' . nl2br(esc($this->localizedLine('App.verifyEmailSignature', [], 'en'))) . '</p>'
        );

        if ($emailService->send()) {
            return true;
        }

        log_message('error', 'Verification email send failed for user {userId} ({email}).', [
            'userId' => $userId,
            'email' => $email,
        ]);

        return false;
    }

    private function isValidResetToken(?array $reset, string $token): bool
    {
        if (empty($reset)) {
            return false;
        }

        if (!empty($reset['used_at'])) {
            return false;
        }

        if ((string) $reset['expires_at'] < Time::now()->toDateTimeString()) {
            return false;
        }

        return hash_equals((string) $reset['token_hash'], hash('sha256', $token));
    }

    private function isValidVerificationToken(?array $verification, string $token): bool
    {
        if (empty($verification)) {
            return false;
        }

        if (!empty($verification['used_at'])) {
            return false;
        }

        if ((string) $verification['expires_at'] < Time::now()->toDateTimeString()) {
            return false;
        }

        return hash_equals((string) $verification['token_hash'], hash('sha256', $token));
    }

    private function verifyTurnstileToken(string $token): bool
    {
        $siteKey = (string) env('turnstile.siteKey', '');
        $secretKey = (string) env('turnstile.secretKey', '');

        if ($token === '') {
            return false;
        }

        // Keep production strict, but don't let local development get blocked
        // by Turnstile verification/network issues after the widget already passed.
        if (ENVIRONMENT !== 'production') {
            return true;
        }

        if ($siteKey === '' || $secretKey === '') {
            log_message('error', 'Turnstile verification skipped because keys are missing in production.');
            return false;
        }

        try {
            $response = service('curlrequest')->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'form_params' => [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $this->request->getIPAddress(),
                ],
                'http_errors' => false,
                'timeout' => 10,
            ]);

            $body = json_decode($response->getBody(), true);

            if (! is_array($body)) {
                log_message('error', 'Turnstile verification returned a non-JSON response.');
                return false;
            }

            if (($body['success'] ?? false) === true) {
                return true;
            }

            log_message('error', 'Turnstile verification failed: {response}', [
                'response' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            return false;
        } catch (Throwable $exception) {
            log_message('error', 'Turnstile verification exception: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
