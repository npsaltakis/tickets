<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="wrapper">
    <a class="back-link" href="<?= base_url('admin/newsletter') ?>">&larr; <?= esc(lang('App.newsletterTitle')) ?></a>

    <?php if (session()->getFlashdata('newsletter_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('newsletter_info')) ?></p>
    <?php endif; ?>

    <div class="card" style="margin:16px 0">
        <h2 style="margin:0 0 4px;color:#f8fafc;font-size:1.1rem"><?= esc($campaign['subject']) ?></h2>
        <p class="meta" style="margin:0 0 16px"><?= esc(date('d/m/Y H:i', strtotime((string) $campaign['created_at']))) ?> · <?= esc(lang('App.newsletterColRecipients')) ?>: <?= (int) $campaign['recipients'] ?></p>

        <div style="display:flex;gap:24px;flex-wrap:wrap">
            <div><div class="meta"><?= esc(lang('App.newsletterStatusSent')) ?></div><strong style="font-size:1.6rem"><?= (int) $counts['sent'] ?></strong></div>
            <div><div class="meta"><?= esc(lang('App.newsletterStatusPending')) ?></div><strong style="font-size:1.6rem"><?= (int) $counts['pending'] ?></strong></div>
            <div><div class="meta"><?= esc(lang('App.newsletterStatusFailed')) ?></div><strong style="font-size:1.6rem;color:<?= $counts['failed'] > 0 ? '#f87171' : 'inherit' ?>"><?= (int) $counts['failed'] ?></strong></div>
        </div>

        <?php if ($counts['failed'] > 0): ?>
            <form method="post" action="<?= base_url('admin/newsletter/' . (int) $campaign['id'] . '/retry') ?>" style="margin-top:16px">
                <?= csrf_field() ?>
                <button class="admin-event-btn" type="submit"><?= esc(lang('App.newsletterRetry')) ?></button>
            </form>
        <?php endif; ?>
        <?php if ($counts['pending'] > 0): ?>
            <p class="meta" style="margin:12px 0 0"><?= esc(lang('App.newsletterPendingHint')) ?></p>
        <?php endif; ?>
    </div>

    <div class="category-filter" style="margin-bottom:12px">
        <?php foreach (['' => 'App.newsletterFilterAll', 'sent' => 'App.newsletterStatusSent', 'pending' => 'App.newsletterStatusPending', 'failed' => 'App.newsletterStatusFailed'] as $key => $label): ?>
            <a class="category-pill <?= $status === $key ? 'is-active' : '' ?>" href="<?= base_url('admin/newsletter/' . (int) $campaign['id'] . ($key !== '' ? '?status=' . $key : '')) ?>"><?= esc(lang($label)) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="card" style="overflow:auto">
        <?php if (empty($rows)): ?>
            <div class="empty"><?= esc(lang('App.newsletterNoRecipients')) ?></div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= esc(lang('App.emailLabel')) ?></th>
                        <th><?= esc(lang('App.status')) ?></th>
                        <th><?= esc(lang('App.newsletterColAttempts')) ?></th>
                        <th><?= esc(lang('App.newsletterColWhen')) ?></th>
                        <th><?= esc(lang('App.newsletterColError')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $rowStatus = $row['sent_at'] !== null ? 'sent' : ((int) $row['attempts'] >= $maxTries ? 'failed' : 'pending');
                        $badge     = ['sent' => 'active', 'pending' => 'inactive', 'failed' => 'cancelled'][$rowStatus];
                        ?>
                        <tr>
                            <td><?= esc($row['to_email']) ?></td>
                            <td><span class="status <?= $badge ?>"><?= esc(lang('App.newsletterRow' . ucfirst($rowStatus))) ?></span></td>
                            <td><?= (int) $row['attempts'] ?>/<?= (int) $maxTries ?></td>
                            <td><?= esc(date('d/m/Y H:i', strtotime((string) ($row['sent_at'] ?? $row['updated_at'])))) ?></td>
                            <td class="meta" style="max-width:360px;word-break:break-word"><?= esc((string) ($row['last_error'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
