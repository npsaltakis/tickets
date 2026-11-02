<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper">
    <p class="meta" style="margin:0 0 16px"><?= esc(lang('App.notifSubtitle')) ?></p>

    <div class="card">
        <?php if (empty($notifications)): ?>
            <div class="empty"><?= esc(lang('App.notifEmpty')) ?></div>
        <?php else: ?>
            <ul class="admin-notif-list">
                <?php foreach ($notifications as $item): ?>
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

            <?php if ($pages > 1): ?>
                <nav class="admin-notif-pager" aria-label="<?= esc(lang('App.notifTitle'), 'attr') ?>">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <a class="admin-event-btn<?= $p === $page ? '' : ' admin-event-btn--secondary' ?>" href="<?= esc(base_url('admin/notifications?page=' . $p)) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
