<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use App\Filters\AdminFilter;
use App\Filters\AuthFilter;
use App\Filters\CheckBlockedIp;
use App\Filters\LogLoginAttempt;
use App\Filters\RateLimit;
use App\Filters\StaffFilter;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'admin'         => AdminFilter::class,
        'auth'          => AuthFilter::class,
        'staff'         => StaffFilter::class,
        'ratelimit'       => RateLimit::class,
        'checkblockedip'  => CheckBlockedIp::class,
        'logloginattempt' => LogLoginAttempt::class,
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
        ],
        'after' => [
            'performance', // Performance Metrics
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            // Admin/staff pages and machine endpoints are not throttled (check-in scans and polls quickly).
            'ratelimit' => ['except' => [
                'admin', 'admin/*', 'admin-logs', 'admin-logs/*', 'check-in', 'check-in/*',
                'robots.txt', 'sitemap.xml', 'health', 'install', 'paypal/webhook',
            ]],
            // Deliberately applies to the admin area too, so a blocked IP also loses a signed-in session.
            // admin/security and logout stay reachable, so a blocked admin can still lift the block.
            'checkblockedip' => ['except' => [
                'admin/security', 'admin/security/*', 'logout',
                'robots.txt', 'sitemap.xml', 'health', 'install', 'paypal/webhook',
            ]],
            'honeypot' => ['except' => ['admin', 'admin/*', 'install', 'paypal/webhook']],
            'csrf'     => ['except' => ['paypal/webhook']],
            'invalidchars',
        ],
        'after' => [
            'honeypot' => ['except' => ['admin', 'admin/*', 'install', 'paypal/webhook']],
            'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'logloginattempt' => ['after' => ['login']],
        'admin' => [
            'before' => [
                'admin/*',
                'admin-logs',
                'admin-logs/*',
                'check-in/export',
                'events/create',
                'events/deleted',
                'events/*/edit',
                'events',
                'events/*/update',
                'events/*/duplicate',
                'events/*/delete',
                'events/*/restore',
                'report',
                'report/*',
                'users',
                'users/*',
            ],
        ],
        'staff' => [
            'before' => [
                'check-in',
                'check-in/stats',
                'check-in/search',
            ],
        ],
        'auth' => [
            'before' => [
                'my-events',
                'my-events/*',
            ],
        ],
    ];
}
