<?php

namespace App\Controllers;

use App\Libraries\AdminNotifications;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Admin → Notifications: everything recorded by App\Libraries\AdminNotifications, newest first.
 * Opening the page marks them as read for the admin viewing it.
 */
class NotificationAdminController extends BaseController
{
    private const PER_PAGE = 30;

    public function index(): string|RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        $userId = (int) session()->get('user_id');
        $total  = AdminNotifications::total();
        $pages  = max(1, (int) ceil($total / self::PER_PAGE));
        $page   = min($pages, max(1, (int) $this->request->getGet('page')));

        // Read before marking, so this visit still shows which ones are new.
        $notifications = AdminNotifications::latest($userId, self::PER_PAGE, ($page - 1) * self::PER_PAGE);
        AdminNotifications::markAllRead($userId);

        return view('admin/notifications', [
            'notifications' => $notifications,
            'page'          => $page,
            'pages'         => $pages,
            'pageTitle'     => lang('App.notifTitle'),
        ]);
    }

    public function markRead(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        AdminNotifications::markAllRead((int) session()->get('user_id'));

        return redirect()->back();
    }

    /**
     * Deletes one notification (the row's ✕, "one"), the ticked ones ("ids[]") or all of them ("all").
     */
    public function delete(): RedirectResponse
    {
        if (! $this->isAdmin()) {
            return redirect()->to(base_url('/'));
        }

        AdminNotifications::delete(self::selectedIds($this->request));

        return redirect()->to(base_url('admin/notifications'));
    }

    /**
     * @return list<int>|null Null for "all".
     */
    public static function selectedIds(\CodeIgniter\HTTP\IncomingRequest $request): ?array
    {
        if ($request->getPost('one') !== null) {
            return [(int) $request->getPost('one')];
        }
        if ($request->getPost('all') === '1') {
            return null;
        }

        return array_map('intval', (array) $request->getPost('ids'));
    }

    private function isAdmin(): bool
    {
        return session()->get('is_logged_in') === true && (string) session()->get('user_role') === 'admin';
    }
}
