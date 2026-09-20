<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Door staff: admins and users with the `staff` role may use the check-in area.
 */
class StaffFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?RedirectResponse
    {
        if (session()->get('is_logged_in') === true && in_array((string) session()->get('user_role'), ['admin', 'staff'], true)) {
            return null;
        }

        return redirect()->to(base_url('/'))->with('login_error', lang('App.adminAccessRequired'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
    }
}