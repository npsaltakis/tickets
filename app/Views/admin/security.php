<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $fmt = static fn (?string $value): string => $value ? date('d/m/y H:i', strtotime($value)) : '—'; ?>
<div class="wrapper admin-security-page">

    <?php if (session()->getFlashdata('sec_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('sec_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('sec_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('sec_error')) ?></p>
    <?php endif; ?>

    <?php if (! $tableReady): ?>
        <p role="status" class="auth-info"><?= esc(lang('App.securityMigrationRequired')) ?></p>
    <?php else: ?>

    <div class="admin-log-stats">
        <article class="admin-log-stat">
            <span><?= esc(lang('App.securityBlockedTitle')) ?></span>
            <strong><?= count($blockedIps) ?></strong>
        </article>
        <article class="admin-log-stat">
            <span><?= esc(lang('App.securityWhitelistTitle')) ?></span>
            <strong><?= count($whitelistedIps) ?></strong>
        </article>
        <article class="admin-log-stat">
            <span><?= esc(lang('App.securityYourIp')) ?></span>
            <strong><?= esc($currentIp) ?></strong>
        </article>
    </div>

    <p class="meta"><?= esc(lang('App.securityInfo')) ?></p>

    <div class="admin-security-grid">

        <section class="card">
            <h2 class="admin-security-heading"><?= esc(lang('App.securityBlockedTitle')) ?></h2>
            <form method="post" action="<?= base_url('admin/security/block-ip') ?>" class="admin-security-form">
                <?= csrf_field() ?>
                <input name="ip_address" type="text" class="auth-input" placeholder="<?= esc(lang('App.securityIpPlaceholder'), 'attr') ?>" aria-label="<?= esc(lang('App.securityIp'), 'attr') ?>" required>
                <input name="reason" type="text" class="auth-input" placeholder="<?= esc(lang('App.securityReason'), 'attr') ?>" aria-label="<?= esc(lang('App.securityReason'), 'attr') ?>">
                <button type="submit" class="admin-action-btn admin-action-btn--danger"><?= esc(lang('App.securityBlock')) ?></button>
            </form>
            <?php if (empty($blockedIps)): ?>
                <div class="empty"><?= esc(lang('App.securityNoBlocked')) ?></div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.securityIp')) ?></th>
                            <th><?= esc(lang('App.securityReason')) ?></th>
                            <th><?= esc(lang('App.securityDate')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blockedIps as $b): ?>
                            <tr>
                                <td><code><?= esc($b['ip_address']) ?></code></td>
                                <td class="meta"><?= esc((string) ($b['reason'] ?? '—')) ?><?= $b['blocked_by'] === 'system' ? ' · ' . esc(lang('App.securityAuto')) : '' ?></td>
                                <td class="meta"><?= esc($fmt($b['blocked_at'])) ?></td>
                                <td>
                                    <form method="post" action="<?= base_url('admin/security/unblock/' . (int) $b['id']) ?>" style="margin:0" data-confirm="<?= esc(lang('App.securityUnblockConfirm'), 'attr') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="admin-action-btn"><?= esc(lang('App.securityUnblock')) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2 class="admin-security-heading"><?= esc(lang('App.securityWhitelistTitle')) ?></h2>
            <form method="post" action="<?= base_url('admin/security/whitelist') ?>" class="admin-security-form">
                <?= csrf_field() ?>
                <input name="ip_address" type="text" class="auth-input" placeholder="<?= esc(lang('App.securityIpPlaceholder'), 'attr') ?>" aria-label="<?= esc(lang('App.securityIp'), 'attr') ?>" required>
                <input name="note" type="text" class="auth-input" placeholder="<?= esc(lang('App.securityNote'), 'attr') ?>" aria-label="<?= esc(lang('App.securityNote'), 'attr') ?>">
                <button type="submit" class="admin-action-btn"><?= esc(lang('App.securityAdd')) ?></button>
            </form>
            <?php if (empty($whitelistedIps)): ?>
                <div class="empty"><?= esc(lang('App.securityNoWhitelisted')) ?></div>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.securityIp')) ?></th>
                            <th><?= esc(lang('App.securityNote')) ?></th>
                            <th><?= esc(lang('App.securityDate')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($whitelistedIps as $w): ?>
                            <tr>
                                <td><code><?= esc($w['ip_address']) ?></code></td>
                                <td class="meta"><?= esc((string) ($w['note'] ?: '—')) ?></td>
                                <td class="meta"><?= esc($fmt($w['added_at'])) ?></td>
                                <td>
                                    <form method="post" action="<?= base_url('admin/security/whitelist/remove/' . (int) $w['id']) ?>" style="margin:0" data-confirm="<?= esc(lang('App.securityRemoveConfirm'), 'attr') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="admin-action-btn admin-action-btn--danger"><?= esc(lang('App.securityRemove')) ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    </div>

    <section class="card">
        <h2 class="admin-security-heading"><?= esc(lang('App.securityAttemptsByIpTitle')) ?></h2>
        <?php if (empty($attemptsByIp)): ?>
            <div class="empty"><?= esc(lang('App.securityNoAttempts')) ?></div>
        <?php else: ?>
            <?php $blockedSet = array_flip(array_column($blockedIps, 'ip_address')); ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= esc(lang('App.securityIp')) ?></th>
                        <th><?= esc(lang('App.securityFailed')) ?></th>
                        <th><?= esc(lang('App.securitySucceeded')) ?></th>
                        <th><?= esc(lang('App.securityLastAttempt')) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attemptsByIp as $a): ?>
                        <tr>
                            <td><code><?= esc($a['ip_address']) ?></code><?= isset($blockedSet[$a['ip_address']]) ? ' <span class="meta">· ' . esc(lang('App.securityBlocked')) . '</span>' : '' ?></td>
                            <td><?= (int) $a['failed'] ?></td>
                            <td><?= (int) $a['succeeded'] ?></td>
                            <td class="meta"><?= esc($fmt($a['last_attempt'])) ?></td>
                            <td>
                                <?php if (! isset($blockedSet[$a['ip_address']]) && $a['ip_address'] !== $currentIp): ?>
                                    <form method="post" action="<?= base_url('admin/security/block-ip') ?>" style="margin:0" data-confirm="<?= esc(lang('App.securityBlockConfirm'), 'attr') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="ip_address" value="<?= esc($a['ip_address'], 'attr') ?>">
                                        <button type="submit" class="admin-action-btn admin-action-btn--danger"><?= esc(lang('App.securityBlock')) ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="card">
        <div class="admin-security-heading-row">
            <h2 class="admin-security-heading"><?= esc(lang('App.securityRecentTitle')) ?></h2>
            <form method="post" action="<?= base_url('admin/security/clear-attempts') ?>" style="margin:0" data-confirm="<?= esc(lang('App.securityClearConfirm'), 'attr') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="days" value="7">
                <button type="submit" class="admin-action-btn"><?= esc(lang('App.securityClear')) ?></button>
            </form>
        </div>
        <?php if (empty($recentAttempts)): ?>
            <div class="empty"><?= esc(lang('App.securityNoAttempts')) ?></div>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= esc(lang('App.securityDate')) ?></th>
                        <th><?= esc(lang('App.securityIp')) ?></th>
                        <th><?= esc(lang('App.securityEmail')) ?></th>
                        <th><?= esc(lang('App.securityResult')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAttempts as $r): ?>
                        <tr>
                            <td class="meta"><?= esc($r['attempted_at'] ? date('d/m/y H:i:s', strtotime($r['attempted_at'])) : '—') ?></td>
                            <td><code><?= esc($r['ip_address']) ?></code></td>
                            <td class="meta"><?= esc((string) ($r['username_attempted'] ?: '—')) ?></td>
                            <td><?= esc(lang((int) $r['success'] === 1 ? 'App.securitySuccess' : 'App.securityFailure')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <?php endif; ?>
</div>
<?= $this->endSection() ?>
