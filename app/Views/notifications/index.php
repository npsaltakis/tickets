<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper" id="main-content">
    <div class="events-header">
        <div>
            <h1><?= esc(lang('App.notifTitle')) ?></h1>
            <p class="subtitle"><?= esc(lang('App.unotifSubtitle')) ?></p>
        </div>
    </div>

    <div class="card notif-page">
        <?php if (empty($notifications)): ?>
            <div class="empty"><?= esc(lang('App.notifEmpty')) ?></div>
        <?php else: ?>
            <?= view('layouts/_notif_manage', ['notifications' => $notifications, 'notifBase' => 'notifications', 'deleteAllConfirm' => lang('App.notifDeleteAllConfirm')]) ?>

            <?php if ($pages > 1): ?>
                <nav class="notif-pager" aria-label="<?= esc(lang('App.notifTitle'), 'attr') ?>">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <a class="notif-page-link<?= $p === $page ? ' is-active' : '' ?>" href="<?= esc(base_url('notifications?page=' . $p)) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
<?= $this->endSection() ?>
