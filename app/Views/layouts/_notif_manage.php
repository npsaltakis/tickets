<?php
/**
 * Notifications page list with delete: tick some and delete them, delete one with its ✕, or delete all.
 *
 * @var array  $notifications Rows with id, text, link, unread and created_at
 * @var string $notifBase     'admin/notifications' or 'notifications'; deletes post to "<base>/delete"
 * @var string $deleteAllConfirm
 */
?>
<form method="post" action="<?= base_url($notifBase . '/delete') ?>" class="notif-manage">
    <?= csrf_field() ?>
    <div class="notif-toolbar">
        <label class="notif-check-all">
            <input type="checkbox" data-check-all="ids[]">
            <?= esc(lang('App.notifSelectAll')) ?>
        </label>
        <button type="submit" class="notif-tool-btn"><?= esc(lang('App.notifDeleteSelected')) ?></button>
    </div>

    <ul class="notif-list">
        <?php foreach ($notifications as $item): ?>
            <li class="notif-item notif-item--manage<?= $item['unread'] ? ' is-unread' : '' ?>">
                <input type="checkbox" name="ids[]" value="<?= (int) $item['id'] ?>" class="notif-select" aria-label="<?= esc(lang('App.notifSelect'), 'attr') ?>">
                <div class="notif-body">
                    <?php if (! empty($item['link'])): ?>
                        <a class="notif-text" href="<?= esc(base_url((string) $item['link'])) ?>"><?= esc((string) $item['text']) ?></a>
                    <?php else: ?>
                        <span class="notif-text"><?= esc((string) $item['text']) ?></span>
                    <?php endif; ?>
                    <time class="notif-time" datetime="<?= esc(date('c', strtotime((string) $item['created_at'])), 'attr') ?>"><?= esc(date('d/m/Y H:i', strtotime((string) $item['created_at']))) ?></time>
                </div>
                <button type="submit" name="one" value="<?= (int) $item['id'] ?>" class="notif-delete" title="<?= esc(lang('App.notifDelete'), 'attr') ?>" aria-label="<?= esc(lang('App.notifDelete'), 'attr') ?>">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </li>
        <?php endforeach; ?>
    </ul>
</form>

<form method="post" action="<?= base_url($notifBase . '/delete') ?>" class="notif-delete-all" data-confirm="<?= esc($deleteAllConfirm, 'attr') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="all" value="1">
    <button type="submit" class="notif-tool-btn notif-tool-btn--danger"><?= esc(lang('App.notifDeleteAll')) ?></button>
</form>
