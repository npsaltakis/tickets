<?php
/**
 * Notifications bell with its drop-down panel (admin topbar and site header).
 *
 * @var int    $notifUnread
 * @var array  $notifLatest  Rows from AdminNotifications::latest() or UserNotifications::latest()
 * @var string $notifBase    'admin/notifications' or 'notifications': the list page; "<base>/read" marks all read
 */
?>
<details class="notif">
    <summary class="notif-bell" title="<?= esc(lang('App.notifTitle'), 'attr') ?>" aria-label="<?= esc(lang('App.notifTitle') . ($notifUnread > 0 ? ', ' . lang('App.notifUnread', [$notifUnread]) : ''), 'attr') ?>">
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
        <?php if ($notifUnread > 0): ?>
            <span class="notif-badge"><?= $notifUnread > 99 ? '99+' : $notifUnread ?></span>
        <?php endif; ?>
    </summary>
    <div class="notif-panel">
        <div class="notif-head">
            <strong><?= esc(lang('App.notifTitle')) ?></strong>
            <?php if ($notifUnread > 0): ?>
                <form method="post" action="<?= base_url($notifBase . '/read') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="notif-mark"><?= esc(lang('App.notifMarkAllRead')) ?></button>
                </form>
            <?php endif; ?>
        </div>
        <?php if (empty($notifLatest)): ?>
            <p class="notif-empty"><?= esc(lang('App.notifEmpty')) ?></p>
        <?php else: ?>
            <?= view('layouts/_notif_list', ['notifications' => $notifLatest]) ?>
        <?php endif; ?>
        <a class="notif-all" href="<?= base_url($notifBase) ?>"><?= esc(lang('App.notifViewAll')) ?></a>
    </div>
</details>
