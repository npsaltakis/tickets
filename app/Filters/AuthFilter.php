<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?RedirectResponse
    {
        if (session()->get('is_logged_in') === true) {
            return null;
        }

        return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
