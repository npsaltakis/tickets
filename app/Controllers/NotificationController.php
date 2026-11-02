<?php

namespace App\Controllers;

use App\Libraries\UserNotifications;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * /notifications: the signed-in user's own notifications, newest first (the auth filter guards it).
 * Opening the page marks them as read.
 */
class NotificationController extends BaseController
{
    private const PER_PAGE = 30;

    public function index(): string
    {
        $userId = (int) session()->get('user_id');
        $pages  = max(1, (int) ceil(UserNotifications::total($userId) / self::PER_PAGE));
        $page   = min($pages, max(1, (int) $this->request->getGet('page')));

        // Read before marking, so this visit still shows which ones are new.
        $notifications = UserNotifications::latest($userId, self::PER_PAGE, ($page - 1) * self::PER_PAGE);
        UserNotifications::markAllRead($userId);

        return view('notifications/index', [
            'notifications' => $notifications,
            'page'          => $page,
            'pages'         => $pages,
            'pageTitle'     => lang('App.notifTitle'),
        ]);
    }

    public function markRead(): RedirectResponse
    {
        UserNotifications::markAllRead((int) session()->get('user_id'));

        return redirect()->back();
    }
}
