<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class HealthController extends BaseController
{
    public function index(): ResponseInterface
    {
        $database = 'ok';

        try {
            db_connect()->query('SELECT 1');
        } catch (Throwable) {
            $database = 'error';
        }

        $statusCode = $database === 'ok' ? 200 : 503;

        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status' => $database === 'ok' ? 'ok' : 'degraded',
                'database' => $database,
                'time' => date(DATE_ATOM),
            ]);
    }
}
