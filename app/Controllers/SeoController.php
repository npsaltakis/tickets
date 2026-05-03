<?php

namespace App\Controllers;

use App\Models\EventModel;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class SeoController extends BaseController
{
    public function robots(): ResponseInterface
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin-logs',
            'Disallow: /admin/',
            'Disallow: /check-in',
            'Disallow: /events/create',
            'Disallow: /events/deleted',
            'Disallow: /login',
            'Disallow: /lost-password',
            'Disallow: /my-events',
            'Disallow: /register',
            'Disallow: /report',
            'Disallow: /reset-password',
            'Disallow: /users',
            'Disallow: /verify-email',
            'Sitemap: ' . base_url('sitemap.xml'),
        ];

        return $this->response
            ->setContentType('text/plain')
            ->setBody(implode("\n", $lines) . "\n");
    }

    public function sitemap(): ResponseInterface
    {
        $events = [];

        try {
            $eventModel = new EventModel();
            $events = $eventModel
                ->select('slug, updated_at, start_date')
                ->where('status', 'active')
                ->orderBy('start_date', 'ASC')
                ->findAll();
        } catch (Throwable $exception) {
            log_message('error', 'Sitemap event query failed: {message}', ['message' => $exception->getMessage()]);
        }

        $urls = [
            [
                'loc' => base_url('/'),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => base_url('privacy-policy'),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
            [
                'loc' => base_url('terms'),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
            [
                'loc' => base_url('gdpr'),
                'changefreq' => 'yearly',
                'priority' => '0.3',
            ],
        ];

        foreach ($events as $event) {
            $slug = trim((string) ($event['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $urls[] = [
                'loc' => base_url('events/' . $slug),
                'lastmod' => $this->formatSitemapDate((string) ($event['updated_at'] ?? $event['start_date'] ?? '')),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . $this->escapeXml($url['loc']) . '</loc>';
            if (! empty($url['lastmod'])) {
                $xml[] = '    <lastmod>' . $this->escapeXml($url['lastmod']) . '</lastmod>';
            }
            $xml[] = '    <changefreq>' . $this->escapeXml($url['changefreq']) . '</changefreq>';
            $xml[] = '    <priority>' . $this->escapeXml($url['priority']) . '</priority>';
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return $this->response
            ->setContentType('application/xml')
            ->setBody(implode("\n", $xml) . "\n");
    }

    private function formatSitemapDate(string $date): string
    {
        $timestamp = $date !== '' ? strtotime($date) : false;

        return $timestamp !== false ? date('Y-m-d', $timestamp) : date('Y-m-d');
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
