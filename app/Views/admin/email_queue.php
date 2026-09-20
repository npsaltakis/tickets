<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper">
    <?php if (session()->getFlashdata('queue_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('queue_info')) ?></p>
    <?php endif; ?>

    <div class="card" style="margin-bottom:16px">
        <div style="display:flex;gap:24px;flex-wrap:wrap">
            <div><div class="meta"><?= esc(lang('App.emailQueuePending')) ?></div><strong style="font-size:1.6rem"><?= (int) $stats['pending'] ?></strong></div>
            <div><div class="meta"><?= esc(lang('App.emailQueueFailed')) ?></div><strong style="font-size:1.6rem;color:<?= $stats['failed'] > 0 ? '#f87171' : 'inherit' ?>"><?= (int) $stats['failed'] ?></strong></div>
            <div><div class="meta"><?= esc(lang('App.emailQueueSent24h')) ?></div><strong style="font-size:1.6rem"><?= (int) $stats['sent24h'] ?></strong></div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:16px">
            <form method="post" action="<?= base_url('admin/email-queue/flush') ?>"><?= csrf_field() ?><button class="admin-event-btn" type="submit"><?= esc(lang('App.emailQueueFlush')) ?></button></form>
            <form method="post" action="<?= base_url('admin/email-queue/retry') ?>"><?= csrf_field() ?><button class="admin-event-btn admin-event-btn--secondary" type="submit"><?= esc(lang('App.emailQueueRetry')) ?></button></form>
            <form method="post" action="<?= base_url('admin/email-queue/purge') ?>" data-confirm="<?= esc(lang('App.emailQueuePurgeConfirm'), 'attr') ?>"><?= csrf_field() ?><button class="admin-event-btn admin-event-btn--secondary" type="submit"><?= esc(lang('App.emailQueuePurge')) ?></button></form>
        </div>
    </div>

    <div class="card" style="overflow:auto">
        <?php if (empty($rows)): ?>
            <div class="empty"><?= esc(lang('App.emailQueueEmpty')) ?></div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>#</th><th><?= esc(lang('App.emailLabel')) ?></th><th><?= esc(lang('App.emailQueueSubject')) ?></th><th><?= esc(lang('App.emailQueueAttempts')) ?></th><th><?= esc(lang('App.emailQueueError')) ?></th></tr></thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= (int) $row['id'] ?></td>
                        <td><?= esc((string) $row['to_email']) ?></td>
                        <td><?= esc((string) $row['subject']) ?></td>
                        <td><?= (int) $row['attempts'] ?> / <?= (int) $maxTries ?></td>
                        <td class="meta"><?= esc((string) ($row['last_error'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>