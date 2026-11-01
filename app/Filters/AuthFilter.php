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
            $user = (int) session()->get('user_id') > 0 ? (new \App\Models\UserModel())->find((int) session()->get('user_id')) : null;
            if ($user !== null && (string) ($user['status'] ?? '') !== 'active') {
                // Clear rather than destroy: a destroyed session cannot carry the flash message below.
                session()->remove(array_keys((array) session()->get()));
                session()->regenerate(true);

                return redirect()->to(base_url('login'))->with('login_error', lang('App.loginInvalidCredentials'));
            }
            return null;
        }

        return redirect()->to(base_url('login'))->with('login_info', lang('App.bookingLoginRequired'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
