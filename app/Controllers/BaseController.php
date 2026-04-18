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

    protected function buildBilingualEmailHtml(array $greekLines, array $englishLines): string
    {
        $renderSection = static function (array $lines, string $heading): string {
            $html = '<div style="margin:0 0 28px;">';
            $html .= '<div style="display:inline-block;margin:0 0 18px;padding:6px 12px;background:#e2e8f0;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#334155;">'
                . esc($heading)
                . '</div>';

            if ($lines !== []) {
                $greeting = array_shift($lines);
                $html .= '<h2 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#0f172a;">' . esc((string) $greeting) . '</h2>';
            }

            if ($lines !== []) {
                $intro = array_shift($lines);
                $html .= '<p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#475569;">' . esc((string) $intro) . '</p>';
            }

            $html .= '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:18px 18px 6px;">';

            foreach ($lines as $line) {
                $line = (string) $line;
                $parts = explode(':', $line, 2);

                if (count($parts) === 2) {
                    $label = trim($parts[0]);
                    $value = ltrim($parts[1]);

                    if (str_contains($value, PHP_EOL)) {
                        $html .= '<div style="margin:0 0 16px;padding:14px 16px;background:#ffffff;border:1px solid #dbeafe;border-radius:14px;">';
                        $html .= '<div style="margin:0 0 10px;font-size:13px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#1d4ed8;">' . esc($label) . '</div>';
                        $html .= '<div style="font-size:14px;line-height:1.7;color:#0f172a;">' . nl2br(esc($value)) . '</div>';
                        $html .= '</div>';
                        continue;
                    }

                    $html .= '<div style="margin:0 0 12px;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">';
                    $html .= '<div style="margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;">' . esc($label) . '</div>';
                    $html .= '<div style="font-size:16px;line-height:1.5;color:#0f172a;">' . esc($value !== '' ? $value : '-') . '</div>';
                    $html .= '</div>';
                    continue;
                }

                $html .= '<p style="margin:0 0 12px;font-size:15px;line-height:1.7;color:#334155;">' . nl2br(esc($line)) . '</p>';
            }

            $html .= '</div>';

            return $html . '</div>';
        };

        return '<div style="font-family:Arial,Helvetica,sans-serif;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);padding:32px 16px;">'
            . '<div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 18px 50px rgba(15,23,42,0.25);">'
            . '<div style="padding:28px 32px;background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 100%);color:#ffffff;">'
            . '<div style="font-size:12px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;opacity:0.78;">Tickets</div>'
            . '<div style="margin-top:8px;font-size:28px;line-height:1.2;font-weight:700;">Booking Confirmation</div>'
            . '<div style="margin-top:8px;font-size:15px;line-height:1.6;opacity:0.88;">Τα στοιχεία της κράτησής σου συγκεντρωμένα σε καθαρή μορφή.</div>'
            . '</div>'
            . '<div style="padding:32px;">'
            . $renderSection($greekLines, 'Ελληνικά')
            . '<div style="height:1px;background:#e2e8f0;margin:8px 0 28px;"></div>'
            . $renderSection($englishLines, 'English')
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function buildBookingConfirmationEmailHtml(array $greekLines, array $englishLines, array $ticketCodes): string
    {
        $renderSection = function (array $lines, string $heading, string $qrTitle, string $qrHint) use ($ticketCodes): string {
            $html = '<div style="margin:0 0 28px;">';
            $html .= '<div style="display:inline-block;margin:0 0 18px;padding:6px 12px;background:#e2e8f0;border-radius:999px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#334155;">'
                . esc($heading)
                . '</div>';

            if ($lines !== []) {
                $greeting = array_shift($lines);
                $html .= '<h2 style="margin:0 0 10px;font-size:22px;line-height:1.3;color:#0f172a;">' . esc((string) $greeting) . '</h2>';
            }

            if ($lines !== []) {
                $intro = array_shift($lines);
                $html .= '<p style="margin:0 0 22px;font-size:15px;line-height:1.7;color:#475569;">' . esc((string) $intro) . '</p>';
            }

            $html .= '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:18px 18px 6px;">';

            foreach ($lines as $line) {
                $line = (string) $line;
                $parts = explode(':', $line, 2);

                if (count($parts) === 2) {
                    $label = trim($parts[0]);
                    $value = ltrim($parts[1]);

                    if (str_contains($value, PHP_EOL)) {
                        $html .= '<div style="margin:0 0 16px;padding:14px 16px;background:#ffffff;border:1px solid #dbeafe;border-radius:14px;">';
                        $html .= '<div style="margin:0 0 10px;font-size:13px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#1d4ed8;">' . esc($label) . '</div>';
                        $html .= '<div style="font-size:14px;line-height:1.7;color:#0f172a;">' . nl2br(esc($value)) . '</div>';
                        $html .= '</div>';
                        continue;
                    }

                    $html .= '<div style="margin:0 0 12px;padding-bottom:12px;border-bottom:1px solid #e2e8f0;">';
                    $html .= '<div style="margin:0 0 4px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;">' . esc($label) . '</div>';
                    $html .= '<div style="font-size:16px;line-height:1.5;color:#0f172a;">' . esc($value !== '' ? $value : '-') . '</div>';
                    $html .= '</div>';
                    continue;
                }

                $html .= '<p style="margin:0 0 12px;font-size:15px;line-height:1.7;color:#334155;">' . nl2br(esc($line)) . '</p>';
            }

            if ($ticketCodes !== []) {
                $html .= '<div style="margin:12px 0 18px;padding:18px;background:#ffffff;border:1px solid #dbeafe;border-radius:18px;">';
                $html .= '<div style="margin:0 0 8px;font-size:13px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#1d4ed8;">' . esc($qrTitle) . '</div>';
                $html .= '<p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">' . esc($qrHint) . '</p>';
                $html .= '<table role="presentation" style="width:100%;border-collapse:collapse;"><tr>';

                foreach (array_values($ticketCodes) as $index => $ticketCode) {
                    if ($index > 0 && $index % 2 === 0) {
                        $html .= '</tr><tr>';
                    }

                    $qrUrl = $this->buildTicketQrImageUrl((string) $ticketCode);
                    $html .= '<td style="width:50%;padding:0 8px 16px;vertical-align:top;">';
                    $html .= '<div style="border:1px solid #e2e8f0;border-radius:16px;padding:16px;text-align:center;background:#f8fafc;">';
                    $html .= '<img src="' . esc($qrUrl, 'attr') . '" alt="' . esc((string) $ticketCode, 'attr') . '" width="180" height="180" style="display:block;width:180px;height:180px;margin:0 auto 12px;border-radius:12px;background:#ffffff;">';
                    $html .= '<div style="font-size:13px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;">Ticket Code</div>';
                    $html .= '<div style="margin-top:6px;font-size:15px;line-height:1.6;color:#0f172a;word-break:break-word;">' . esc((string) $ticketCode) . '</div>';
                    $html .= '</div>';
                    $html .= '</td>';
                }

                if (count($ticketCodes) % 2 !== 0) {
                    $html .= '<td style="width:50%;padding:0 8px 16px;"></td>';
                }

                $html .= '</tr></table>';
                $html .= '</div>';
            }

            $html .= '</div>';

            return $html . '</div>';
        };

        return '<div style="font-family:Arial,Helvetica,sans-serif;background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);padding:32px 16px;">'
            . '<div style="max-width:720px;margin:0 auto;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 18px 50px rgba(15,23,42,0.25);">'
            . '<div style="padding:28px 32px;background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 100%);color:#ffffff;">'
            . '<div style="font-size:12px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;opacity:0.78;">Tickets</div>'
            . '<div style="margin-top:8px;font-size:28px;line-height:1.2;font-weight:700;">Booking Confirmation</div>'
            . '<div style="margin-top:8px;font-size:15px;line-height:1.6;opacity:0.88;">Τα στοιχεία της κράτησής σου συγκεντρωμένα σε καθαρή μορφή.</div>'
            . '</div>'
            . '<div style="padding:32px;">'
            . $renderSection($greekLines, 'Ελληνικά', 'QR Tickets', 'Σκάναρε το QR στην είσοδο ή χρησιμοποίησε τον ticket code αν χρειαστεί.')
            . '<div style="height:1px;background:#e2e8f0;margin:8px 0 28px;"></div>'
            . $renderSection($englishLines, 'English', 'QR Tickets', 'Scan the QR at the entrance or use the ticket code if needed.')
            . '</div>'
            . '</div>'
            . '</div>';
    }

    protected function buildTicketQrImageUrl(string $ticketCode): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=12&data=' . rawurlencode($ticketCode);
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
