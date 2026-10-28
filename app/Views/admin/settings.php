<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$value = static fn (string $key): string => (string) old($key, $settings[$key] ?? '');
$logo    = \App\Libraries\SiteSettings::assetUrl('logo_path');
$favicon = \App\Libraries\SiteSettings::assetUrl('favicon_path');
?>
<div class="wrapper">
    <?php if (session()->getFlashdata('settings_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('settings_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('settings_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('settings_error')) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= base_url('admin/settings') ?>" enctype="multipart/form-data" class="settings-form">
        <?= csrf_field() ?>

        <section class="card">
            <h2><?= esc(lang('App.settingsBrandTitle')) ?></h2>

            <label class="auth-label" for="site_name"><?= esc(lang('App.settingsSiteName')) ?> *</label>
            <input id="site_name" name="site_name" type="text" class="auth-input" maxlength="100" value="<?= esc($value('site_name'), 'attr') ?>" required>

            <label class="auth-label" for="accent_color"><?= esc(lang('App.settingsAccent')) ?></label>
            <input id="accent_color" name="accent_color" type="color" class="settings-color" value="<?= esc($value('accent_color'), 'attr') ?>">

            <label class="auth-label" for="logo_path"><?= esc(lang('App.settingsLogo')) ?></label>
            <?php if ($logo !== ''): ?>
                <div class="settings-preview">
                    <img src="<?= esc($logo, 'attr') ?>" alt="" width="64" height="64">
                    <label class="booking-consent" for="remove_logo_path"><input id="remove_logo_path" name="remove_logo_path" type="checkbox" value="1"><span><?= esc(lang('App.settingsRemove')) ?></span></label>
                </div>
            <?php endif; ?>
            <input id="logo_path" name="logo_path" type="file" class="auth-input" accept="image/png,image/jpeg,image/webp">
            <p class="field-hint"><?= esc(lang('App.settingsLogoHint')) ?></p>

            <label class="auth-label" for="favicon_path"><?= esc(lang('App.settingsFavicon')) ?></label>
            <?php if ($favicon !== ''): ?>
                <div class="settings-preview">
                    <img src="<?= esc($favicon, 'attr') ?>" alt="" width="32" height="32">
                    <label class="booking-consent" for="remove_favicon_path"><input id="remove_favicon_path" name="remove_favicon_path" type="checkbox" value="1"><span><?= esc(lang('App.settingsRemove')) ?></span></label>
                </div>
            <?php endif; ?>
            <input id="favicon_path" name="favicon_path" type="file" class="auth-input" accept="image/png,image/x-icon,.ico">
            <p class="field-hint"><?= esc(lang('App.settingsFaviconHint')) ?></p>

            <label class="auth-label" for="footer_text"><?= esc(lang('App.settingsFooter')) ?></label>
            <input id="footer_text" name="footer_text" type="text" class="auth-input" maxlength="255" value="<?= esc($value('footer_text'), 'attr') ?>" placeholder="© <?= date('Y') ?> <?= esc($value('site_name'), 'attr') ?>">
        </section>

        <section class="card">
            <h2><?= esc(lang('App.settingsContactTitle')) ?></h2>
            <p class="field-hint"><?= esc(lang('App.settingsContactHint')) ?></p>

            <label class="auth-label" for="contact_email"><?= esc(lang('App.emailLabel')) ?></label>
            <input id="contact_email" name="contact_email" type="email" class="auth-input" maxlength="191" value="<?= esc($value('contact_email'), 'attr') ?>">

            <label class="auth-label" for="contact_phone"><?= esc(lang('App.settingsContactPhone')) ?></label>
            <input id="contact_phone" name="contact_phone" type="tel" class="auth-input" maxlength="50" value="<?= esc($value('contact_phone'), 'attr') ?>">

            <label class="auth-label" for="contact_address"><?= esc(lang('App.settingsContactAddress')) ?></label>
            <input id="contact_address" name="contact_address" type="text" class="auth-input" maxlength="255" value="<?= esc($value('contact_address'), 'attr') ?>">
        </section>

        <section class="card">
            <h2><?= esc(lang('App.settingsOrgTitle')) ?></h2>
            <p class="field-hint"><?= esc(lang('App.settingsOrgHint')) ?></p>

            <label class="auth-label" for="org_name"><?= esc(lang('App.settingsOrgName')) ?></label>
            <input id="org_name" name="org_name" type="text" class="auth-input" maxlength="191" value="<?= esc($value('org_name'), 'attr') ?>">

            <label class="auth-label" for="org_tax_id"><?= esc(lang('App.settingsOrgTaxId')) ?></label>
            <input id="org_tax_id" name="org_tax_id" type="text" class="auth-input" maxlength="50" value="<?= esc($value('org_tax_id'), 'attr') ?>">

            <label class="auth-label" for="org_address"><?= esc(lang('App.settingsOrgAddress')) ?></label>
            <input id="org_address" name="org_address" type="text" class="auth-input" maxlength="255" value="<?= esc($value('org_address'), 'attr') ?>">
        </section>

        <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.settingsSave')) ?></button>
    </form>
</div>
<?= $this->endSection() ?>
