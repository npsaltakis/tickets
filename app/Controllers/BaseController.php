<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $session = service('session');

        $requestedLang = '';

        if ($request instanceof IncomingRequest) {
            $requestedLang = strtolower((string) ($request->getGet('lang') ?? ''));
        }

        $localeMap = [
            'el' => 'el',
            'en' => 'en',
        ];

        if (isset($localeMap[$requestedLang])) {
            $session->set('locale', $localeMap[$requestedLang]);
        }

        $locale = (string) ($session->get('locale') ?? config('App')->defaultLocale);

        if ($request instanceof IncomingRequest) {
            $request->setLocale($locale);
        }

        service('language')->setLocale($locale);
    }

    protected function localizedLine(string $key, array $args = [], string $locale = 'el'): string
    {
        return lang($key, $args, $locale);
    }

    protected function bilingualSubject(string $key, array $args = []): string
    {
        return $this->localizedLine($key, $args, 'el') . ' / ' . $this->localizedLine($key, $args, 'en');
    }

    protected function buildBilingualEmail(array $greekLines, array $englishLines): string
    {
        return implode(PHP_EOL . PHP_EOL, array_merge(
            $greekLines,
            ['----------------------------------------'],
            $englishLines
        ));
    }

    protected function sendVerificationEmail(int $userId, string $email, string $selector, string $token): bool
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
            'email'  => $email,
        ]);

        return false;
    }

    protected function logAdminAction(string $action, string $targetType, array $context = []): void
    {
        $session = session();

        log_message('info', 'admin_action {payload}', [
            'payload' => json_encode([
                'action' => $action,
                'target_type' => $targetType,
                'admin_id' => (int) ($session->get('user_id') ?? 0),
                'admin_email' => (string) ($session->get('user_email') ?? ''),
                'ip' => method_exists($this->request, 'getIPAddress') ? (string) $this->request->getIPAddress() : '',
                'context' => $context,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
