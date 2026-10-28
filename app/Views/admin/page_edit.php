<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $value = static fn (string $key): string => (string) old($key, $values[$key] ?? ''); ?>
<div class="wrapper">
    <a class="back-link" href="<?= base_url('admin/pages') ?>">&larr; <?= esc(lang('App.pagesAdminTitle')) ?></a>

    <?php if (session()->getFlashdata('pages_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('pages_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('pages_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('pages_error')) ?></p>
    <?php endif; ?>

    <div class="page-edit-grid">
        <form method="post" action="<?= base_url('admin/pages/' . $slug . '/' . $locale) ?>" class="card settings-form-card">
            <?= csrf_field() ?>
            <h2><?= esc(lang('App.pagesName' . ucfirst($slug))) ?> · <?= esc(strtoupper($locale)) ?></h2>
            <?php if ($saved === null): ?>
                <p class="field-hint"><?= esc(lang('App.pagesPrefilled')) ?></p>
            <?php endif; ?>

            <label class="auth-label" for="page_title"><?= esc(lang('App.pagesFieldTitle')) ?> *</label>
            <input id="page_title" name="title" type="text" class="auth-input" maxlength="191" value="<?= esc($value('title'), 'attr') ?>" required>

            <label class="auth-label" for="page_subtitle"><?= esc(lang('App.pagesFieldSubtitle')) ?></label>
            <input id="page_subtitle" name="subtitle" type="text" class="auth-input" maxlength="255" value="<?= esc($value('subtitle'), 'attr') ?>">

            <label class="auth-label" for="page_body"><?= esc(lang('App.pagesFieldBody')) ?> *</label>
            <textarea id="page_body" name="body" class="auth-input page-body-input" rows="24" required><?= esc($value('body')) ?></textarea>

            <div class="page-edit-actions">
                <button type="submit" class="book-btn"><?= esc(lang('App.settingsSave')) ?></button>
            </div>
        </form>

        <aside class="card page-edit-help">
            <h2><?= esc(lang('App.pagesHelpTitle')) ?></h2>
            <dl>
                <dt><code>## <?= esc(lang('App.pagesHelpHeadingSample')) ?></code></dt><dd><?= esc(lang('App.pagesHelpHeading')) ?></dd>
                <dt><code><?= esc(lang('App.pagesHelpBlankSample')) ?></code></dt><dd><?= esc(lang('App.pagesHelpBlank')) ?></dd>
                <dt><code>- <?= esc(lang('App.pagesHelpListSample')) ?></code></dt><dd><?= esc(lang('App.pagesHelpList')) ?></dd>
                <dt><code>**<?= esc(lang('App.pagesHelpBoldSample')) ?>**</code></dt><dd><?= esc(lang('App.pagesHelpBold')) ?></dd>
                <dt><code>[<?= esc(lang('App.footerContact')) ?>](/contact)</code></dt><dd><?= esc(lang('App.pagesHelpLink')) ?></dd>
            </dl>
            <?php if ($saved !== null): ?>
                <form method="post" action="<?= base_url('admin/pages/' . $slug . '/' . $locale . '/reset') ?>" data-confirm="<?= esc(lang('App.pagesResetConfirm'), 'attr') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="admin-action-btn admin-action-btn--danger"><?= esc(lang('App.pagesReset')) ?></button>
                </form>
            <?php endif; ?>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
