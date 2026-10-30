<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="wrapper">
    <?php if (session()->getFlashdata('update_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('update_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('update_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('update_error')) ?></p>
    <?php endif; ?>

    <section class="card" style="margin-bottom:16px">
        <div style="display:flex;gap:24px;flex-wrap:wrap">
            <div><div class="meta"><?= esc(lang('App.updaterCurrent')) ?></div><strong style="font-size:1.6rem"><?= esc($current !== '' ? $current : lang('App.updaterUnversioned')) ?></strong></div>
            <div><div class="meta"><?= esc(lang('App.updaterLatest')) ?></div><strong style="font-size:1.6rem"><?= esc($latest['tag'] ?? '—') ?></strong></div>
        </div>

        <?php if ($latest === null): ?>
            <p class="auth-error alert-inline" style="margin-top:16px"><?= esc(lang('App.updaterUnreachable')) ?></p>
        <?php elseif (! $hasUpdate): ?>
            <p class="meta" style="margin-top:16px"><?= esc(lang('App.updaterUpToDate')) ?></p>
        <?php elseif ($isGitCheckout): ?>
            <p class="meta" style="margin-top:16px"><?= esc(lang('App.updaterGitCheckout')) ?></p>
        <?php elseif ($latest['zip'] === ''): ?>
            <p class="meta" style="margin-top:16px"><?= esc(lang('App.updaterNotReady')) ?></p>
        <?php else: ?>
            <p class="meta" style="margin-top:16px"><?= esc(lang('App.updaterHint')) ?></p>
            <form method="post" action="<?= base_url('admin/update') ?>" data-confirm="<?= esc(strtr(lang('App.updaterConfirm'), ['{version}' => $latest['tag']]), 'attr') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="book-btn"><?= esc(strtr(lang('App.updaterInstall'), ['{version}' => $latest['tag']])) ?></button>
            </form>
        <?php endif; ?>

        <p style="margin-top:16px"><a href="<?= base_url('admin/update?refresh=1') ?>"><?= esc(lang('App.updaterCheck')) ?></a></p>
    </section>

    <?php if (! empty($pendingMigrations)): ?>
        <section class="card" style="margin-bottom:16px">
            <h2><?= esc(lang('App.adminMigrationsTitle')) ?></h2>
            <p><strong><?= esc(strtr(lang('App.adminMigrationsPending'), ['{n}' => (string) count($pendingMigrations)])) ?></strong></p>
            <form method="post" action="<?= base_url('admin/migrate') ?>" data-confirm="<?= esc(lang('App.adminMigrationsConfirm'), 'attr') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="book-btn"><?= esc(lang('App.adminMigrationsButton')) ?></button>
            </form>
        </section>
    <?php endif; ?>

    <?php if ($latest !== null && trim($latest['notes']) !== ''): ?>
        <section class="card">
            <h2><?= esc(lang('App.updaterNotes')) ?> <?= esc($latest['name']) ?></h2>
            <div class="meta" style="white-space:pre-wrap"><?= esc($latest['notes']) ?></div>
            <?php if ($latest['url'] !== ''): ?>
                <p style="margin-top:12px"><a href="<?= esc($latest['url'], 'attr') ?>" target="_blank" rel="noopener noreferrer">GitHub</a></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
