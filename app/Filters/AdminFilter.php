<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?RedirectResponse
    {
        if (session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin') {
            return null;
        }

        return redirect()->to(base_url('/'))->with('login_error', lang('App.adminAccessRequired'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
