<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper">
    <p class="meta" style="margin:0 0 16px"><?= esc(lang('App.notifSubtitle')) ?></p>

    <div class="card">
        <?php if (empty($notifications)): ?>
            <div class="empty"><?= esc(lang('App.notifEmpty')) ?></div>
        <?php else: ?>
            <?= view('layouts/_notif_manage', ['notifications' => $notifications, 'notifBase' => 'admin/notifications', 'deleteAllConfirm' => lang('App.notifDeleteAllConfirmAdmin')]) ?>

            <?php if ($pages > 1): ?>
                <nav class="notif-pager" aria-label="<?= esc(lang('App.notifTitle'), 'attr') ?>">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <a class="admin-event-btn<?= $p === $page ? '' : ' admin-event-btn--secondary' ?>" href="<?= esc(base_url('admin/notifications?page=' . $p)) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
