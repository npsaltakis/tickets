<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Logs every POST to /login per IP and blocks the IP after 5 failures in 15 minutes.
 * LoginController's own per-email lock stays; this one stops an IP that tries many emails.
 */
class LogLoginAttempt implements FilterInterface
{
    private const MAX_FAILURES   = 5;
    private const WINDOW_MINUTES = 15;

    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        if (! $request instanceof IncomingRequest || strtolower($request->getMethod()) !== 'post') {
            return;
        }

        $session = session();
        // A correct password waiting on the 2FA code is not a failure.
        $success = $session->get('is_logged_in') === true || (int) $session->get('pending_2fa_user') > 0;
        $ip      = $request->getIPAddress();
        $now     = date('Y-m-d H:i:s');

        try {
            $db = db_connect();
            $db->table('login_attempts')->insert([
                'ip_address'         => $ip,
                'username_attempted' => mb_substr(trim((string) $request->getPost('email')), 0, 255),
                'success'            => $success ? 1 : 0,
                'attempted_at'       => $now,
            ]);

            if ($success || CheckBlockedIp::isBlocked($ip) || CheckBlockedIp::isWhitelisted($ip)) {
                return;
            }

            $failures = $db->table('login_attempts')
                ->where('ip_address', $ip)
                ->where('success', 0)
                ->where('attempted_at >=', date('Y-m-d H:i:s', strtotime('-' . self::WINDOW_MINUTES . ' minutes')))
                ->countAllResults();

            if ($failures < self::MAX_FAILURES) {
                return;
            }

            // ip_address is unique: an expired automatic block is replaced, a manual one never reaches here.
            $db->table('blocked_ips')->where('ip_address', $ip)->where('blocked_by', 'system')->delete();
            $db->table('blocked_ips')->insert([
                'ip_address' => $ip,
                'reason'     => 'Auto-blocked: ' . $failures . ' failed login attempts in ' . self::WINDOW_MINUTES . ' minutes',
                'blocked_by' => 'system',
                'blocked_at' => $now,
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Login attempt logging failed: {message}', ['message' => $e->getMessage()]);
        }
    }
}
