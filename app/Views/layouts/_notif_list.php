<?php
/**
 * A list of notifications (bell panel and the notifications pages).
 *
 * @var array $notifications Rows with text, link, unread and created_at
 */
?>
<ul class="notif-list">
    <?php foreach ($notifications as $item): ?>
        <li class="notif-item<?= $item['unread'] ? ' is-unread' : '' ?>">
            <?php if (! empty($item['link'])): ?>
                <a class="notif-text" href="<?= esc(base_url((string) $item['link'])) ?>"><?= esc((string) $item['text']) ?></a>
            <?php else: ?>
                <span class="notif-text"><?= esc((string) $item['text']) ?></span>
            <?php endif; ?>
            <time class="notif-time" datetime="<?= esc(date('c', strtotime((string) $item['created_at'])), 'attr') ?>"><?= esc(date('d/m/Y H:i', strtotime((string) $item['created_at']))) ?></time>
        </li>
    <?php endforeach; ?>
</ul>
