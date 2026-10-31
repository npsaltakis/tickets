<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Rejects requests from blocked IPs (Admin → Security). Also applies to the admin area, so a blocked IP
 * loses an already signed-in session too; admin/security and logout stay reachable (see Config\Filters).
 */
class CheckBlockedIp implements FilterInterface
{
    // Automatic blocks expire, so a legitimate user who mistypes a password five times is not locked out
    // for good. Manual blocks from the Security page stay until an admin lifts them.
    public const BLOCK_TTL_HOURS = 24;

    public function before(RequestInterface $request, $arguments = null)
    {
        try {
            if (! self::isBlocked($request->getIPAddress())) {
                return null;
            }
        } catch (Throwable) {
            // Tables not migrated yet (fresh install, or an update before the migrations ran): fail open.
            return null;
        }

        return service('response')
            ->setStatusCode(403)
            ->setBody(RateLimit::page('Access Denied', 'Your IP address has been blocked due to suspicious activity. Contact the administrator if you believe this is a mistake.', '#f87171'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }

    public static function isWhitelisted(string $ip): bool
    {
        return db_connect()->table('whitelisted_ips')->where('ip_address', $ip)->countAllResults() > 0;
    }

    /**
     * A block still in effect: any manual one, or an automatic one younger than BLOCK_TTL_HOURS.
     */
    public static function isBlocked(string $ip): bool
    {
        if (self::isWhitelisted($ip)) {
            return false;
        }

        return db_connect()->table('blocked_ips')
            ->where('ip_address', $ip)
            ->groupStart()
                ->where('blocked_by !=', 'system')
                ->orWhere('blocked_at >=', date('Y-m-d H:i:s', strtotime('-' . self::BLOCK_TTL_HOURS . ' hours')))
            ->groupEnd()
            ->countAllResults() > 0;
    }
}
