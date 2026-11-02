<?php
$session = session();
$userName = trim((string) ($session->get('user_name') ?? ''));
$userEmail = (string) ($session->get('user_email') ?? '');
$displayName = $userName !== '' ? $userName : $userEmail;
$pageTitle = (string) ($pageTitle ?? lang('App.siteTitle'));
$isAdminUser = (string) ($session->get('user_role') ?? '') === 'admin';
if ($isAdminUser) {
    $notifUserId = (int) $session->get('user_id');
    $notifUnread = \App\Libraries\AdminNotifications::unreadCount($notifUserId);
    $notifLatest = \App\Libraries\AdminNotifications::latest($notifUserId, 8);
}
?>
<header class="admin-topbar">
    <div class="admin-topbar-left">
        <button type="button" class="admin-menu-btn" id="admin-menu-btn" aria-controls="admin-sidebar" aria-expanded="false" aria-label="<?= esc(lang('App.adminMenu'), 'attr') ?>">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>
        <h1 class="admin-topbar-title"><?= esc($pageTitle) ?></h1>
    </div>
    <div class="admin-topbar-right">
        <?php if ($isAdminUser): ?>
            <details class="admin-notif">
                <summary class="admin-notif-bell" title="<?= esc(lang('App.notifTitle'), 'attr') ?>" aria-label="<?= esc(lang('App.notifTitle') . ($notifUnread > 0 ? ', ' . lang('App.notifUnread', [$notifUnread]) : ''), 'attr') ?>">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
                    <?php if ($notifUnread > 0): ?>
                        <span class="admin-notif-badge"><?= $notifUnread > 99 ? '99+' : $notifUnread ?></span>
                    <?php endif; ?>
                </summary>
                <div class="admin-notif-panel">
                    <div class="admin-notif-head">
                        <strong><?= esc(lang('App.notifTitle')) ?></strong>
                        <?php if ($notifUnread > 0): ?>
                            <form method="post" action="<?= base_url('admin/notifications/read') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="admin-notif-mark"><?= esc(lang('App.notifMarkAllRead')) ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if (empty($notifLatest)): ?>
                        <p class="admin-notif-empty"><?= esc(lang('App.notifEmpty')) ?></p>
                    <?php else: ?>
                        <ul class="admin-notif-list">
                            <?php foreach ($notifLatest as $item): ?>
                                <li class="admin-notif-item<?= $item['unread'] ? ' is-unread' : '' ?>">
                                    <?php if (! empty($item['link'])): ?>
                                        <a class="admin-notif-text" href="<?= esc(base_url((string) $item['link'])) ?>"><?= esc((string) $item['text']) ?></a>
                                    <?php else: ?>
                                        <span class="admin-notif-text"><?= esc((string) $item['text']) ?></span>
                                    <?php endif; ?>
                                    <time class="admin-notif-time" datetime="<?= esc(date('c', strtotime((string) $item['created_at'])), 'attr') ?>"><?= esc(date('d/m/Y H:i', strtotime((string) $item['created_at']))) ?></time>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <a class="admin-notif-all" href="<?= base_url('admin/notifications') ?>"><?= esc(lang('App.notifViewAll')) ?></a>
                </div>
            </details>
        <?php endif; ?>
        <span class="admin-topbar-user"><?= esc($displayName) ?></span>
        <a class="admin-topbar-logout" href="<?= base_url('logout') ?>"><?= esc(lang('App.navLogout')) ?></a>
    </div>
</header>
