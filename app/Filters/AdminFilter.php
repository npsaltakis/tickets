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
            $user = (int) session()->get('user_id') > 0 ? (new \App\Models\UserModel())->find((int) session()->get('user_id')) : null;
            if ($user !== null && ((string) ($user['status'] ?? '') !== 'active' || (string) ($user['role'] ?? '') !== 'admin')) {
                return redirect()->to(base_url('/'))->with('login_error', lang('App.adminAccessRequired'));
            }
            return null;
        }

        return redirect()->to(base_url('/'))->with('login_error', lang('App.adminAccessRequired'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}
