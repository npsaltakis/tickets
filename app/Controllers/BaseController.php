<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $session = service('session');

        $requestedLang = strtolower((string) $request->getGet('lang'));
        $localeMap = [
            'gr' => 'el',
            'el' => 'el',
            'en' => 'en',
        ];

        if (isset($localeMap[$requestedLang])) {
            $session->set('locale', $localeMap[$requestedLang]);
        }

        $locale = (string) ($session->get('locale') ?? config('App')->defaultLocale);

        $request->setLocale($locale);
        service('language')->setLocale($locale);
    }
}
