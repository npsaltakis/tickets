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
}
