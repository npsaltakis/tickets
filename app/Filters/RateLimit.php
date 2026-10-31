<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 120 requests per minute per IP on the public site (admin, check-in and webhooks are exempt in Config\Filters).
 */
class RateLimit implements FilterInterface
{
    private const MAX_REQUESTS   = 120;
    private const WINDOW_SECONDS = 60;

    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = service('throttler');

        if ($throttler->check('ratelimit_' . md5($request->getIPAddress()), self::MAX_REQUESTS, self::WINDOW_SECONDS) !== false) {
            return null;
        }

        return service('response')
            ->setStatusCode(429)
            ->setHeader('Retry-After', (string) max(1, $throttler->getTokenTime()))
            ->setBody(self::page('Too Many Requests', 'You are sending too many requests. Please slow down and try again shortly.', '#f59e0b'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }

    /**
     * Small self-contained error page, shared with CheckBlockedIp.
     */
    public static function page(string $title, string $message, string $color): string
    {
        return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . esc($title) . '</title>'
            . '<style>body{font-family:sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}'
            . '.box{text-align:center;max-width:420px;padding:40px;border:1px solid rgba(255,255,255,.1);border-radius:16px;background:#1e293b}'
            . 'h1{color:' . $color . ';font-size:1.5rem;margin-bottom:8px}p{color:#94a3b8;font-size:.9rem;margin:0}</style></head>'
            . '<body><div class="box"><h1>' . esc($title) . '</h1><p>' . esc($message) . '</p></div></body></html>';
    }
}
