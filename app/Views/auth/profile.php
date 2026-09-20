<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card" style="max-width:540px">
        <h1 class="auth-title"><?= esc(lang('App.profilePageTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.profileSubtitle')) ?></p>

        <?php if (session()->getFlashdata('profile_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('profile_error')) ?></p>
        <?php endif; ?>
        <?php if (session()->getFlashdata('profile_info')): ?>
            <p class="auth-info"><?= esc((string) session()->getFlashdata('profile_info')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('profile') ?>" class="auth-form" style="margin-top:16px">
            <?= csrf_field() ?>

            <label class="auth-label" for="first_name"><?= esc(lang('App.firstNameLabel')) ?></label>
            <input id="first_name" name="first_name" type="text" class="auth-input" value="<?= esc((string) ($user['first_name'] ?? '')) ?>" required>

            <label class="auth-label" for="last_name"><?= esc(lang('App.lastNameLabel')) ?></label>
            <input id="last_name" name="last_name" type="text" class="auth-input" value="<?= esc((string) ($user['last_name'] ?? '')) ?>" required>

            <label class="auth-label" for="email"><?= esc(lang('App.emailLabel')) ?></label>
            <input id="email" type="email" class="auth-input" value="<?= esc((string) ($user['email'] ?? '')) ?>" disabled style="opacity:0.55">

            <hr style="border:none;border-top:1px solid var(--border);margin:8px 0">
            <p class="meta" style="margin:0 0 4px"><?= esc(lang('App.profilePasswordSection')) ?></p>

            <label class="auth-label" for="current_password"><?= esc(lang('App.profileCurrentPassword')) ?></label>
            <input id="current_password" name="current_password" type="password" class="auth-input" autocomplete="current-password">

            <label class="auth-label" for="new_password"><?= esc(lang('App.profileNewPassword')) ?></label>
            <input id="new_password" name="new_password" type="password" class="auth-input" autocomplete="new-password">

            <label class="auth-label" for="confirm_password"><?= esc(lang('App.profileConfirmPassword')) ?></label>
            <input id="confirm_password" name="confirm_password" type="password" class="auth-input" autocomplete="new-password">

            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.profileSave')) ?></button>
        </form>

        <hr style="border:none;border-top:1px solid var(--border);margin:24px 0 12px">
        <h2 class="meta" style="margin:0 0 8px"><strong><?= esc(lang('App.profileEmailSection')) ?></strong></h2>
        <form method="post" action="<?= base_url('profile/email') ?>" class="auth-form">
            <?= csrf_field() ?>
            <label class="auth-label" for="new_email"><?= esc(lang('App.profileNewEmail')) ?></label>
            <input id="new_email" name="new_email" type="email" class="auth-input" maxlength="191" autocomplete="off" required>
            <label class="auth-label" for="email_password"><?= esc(lang('App.profileCurrentPassword')) ?></label>
            <input id="email_password" name="email_password" type="password" class="auth-input" autocomplete="current-password" required>
            <button type="submit" class="auth-link-btn"><?= esc(lang('App.profileEmailChangeButton')) ?></button>
        </form>

        <hr style="border:none;border-top:1px solid var(--border);margin:24px 0 12px">
        <h2 class="meta" style="margin:0 0 8px"><strong><?= esc(lang('App.twoFactorSection')) ?></strong></h2>
        <?php $totpEnabled = (int) ($user['totp_enabled'] ?? 0) === 1; $totpSecret = (string) ($user['totp_secret'] ?? ''); ?>
        <?php if ($totpEnabled): ?>
            <p class="auth-info"><?= esc(lang('App.twoFactorIsOn')) ?></p>
            <form method="post" action="<?= base_url('profile/2fa/disable') ?>" class="auth-form">
                <?= csrf_field() ?>
                <label class="auth-label" for="disable_password"><?= esc(lang('App.profileCurrentPassword')) ?></label>
                <input id="disable_password" name="disable_password" type="password" class="auth-input" autocomplete="current-password" required>
                <label class="auth-label" for="disable_code"><?= esc(lang('App.twoFactorCodeLabel')) ?></label>
                <input id="disable_code" name="code" type="text" inputmode="numeric" maxlength="7" class="auth-input" autocomplete="one-time-code" required>
                <button type="submit" class="auth-link-btn admin-action-btn admin-action-btn--danger"><?= esc(lang('App.twoFactorDisableButton')) ?></button>
            </form>
        <?php elseif ($totpSecret !== ''): ?>
            <p class="meta"><?= esc(lang('App.twoFactorScan')) ?></p>
            <img src="<?= base_url('profile/2fa/qr') ?>" alt="2FA QR" width="180" height="180" style="background:#fff;padding:8px;border-radius:8px">
            <p class="meta"><?= esc(lang('App.twoFactorManualKey')) ?>: <code><?= esc(trim(chunk_split($totpSecret, 4, ' '))) ?></code></p>
            <form method="post" action="<?= base_url('profile/2fa/enable') ?>" class="auth-form">
                <?= csrf_field() ?>
                <label class="auth-label" for="enable_code"><?= esc(lang('App.twoFactorCodeLabel')) ?></label>
                <input id="enable_code" name="code" type="text" inputmode="numeric" maxlength="7" class="auth-input" autocomplete="one-time-code" required>
                <button type="submit" class="auth-link-btn"><?= esc(lang('App.twoFactorEnableButton')) ?></button>
            </form>
        <?php else: ?>
            <p class="meta"><?= esc(lang('App.twoFactorHelp')) ?></p>
            <form method="post" action="<?= base_url('profile/2fa/setup') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="auth-link-btn"><?= esc(lang('App.twoFactorSetupButton')) ?></button>
            </form>
        <?php endif; ?>

        <hr style="border:none;border-top:1px solid var(--border);margin:24px 0 12px">
        <h2 class="meta" style="margin:0 0 8px"><strong><?= esc(lang('App.profilePrivacySection')) ?></strong></h2>
        <p class="meta"><?= esc(lang('App.profileExportHelp')) ?></p>
        <a class="auth-link-btn" href="<?= base_url('profile/export') ?>"><?= esc(lang('App.profileExportButton')) ?></a>

        <form method="post" action="<?= base_url('profile/delete') ?>" class="auth-form" style="margin-top:20px" data-confirm="<?= esc(lang('App.profileDeleteConfirm'), 'attr') ?>">
            <?= csrf_field() ?>
            <p class="meta"><?= esc(lang('App.profileDeleteHelp')) ?></p>
            <label class="auth-label" for="delete_password"><?= esc(lang('App.profileCurrentPassword')) ?></label>
            <input id="delete_password" name="delete_password" type="password" class="auth-input" autocomplete="off" required>
            <button type="submit" class="auth-link-btn admin-action-btn admin-action-btn--danger"><?= esc(lang('App.profileDeleteButton')) ?></button>
        </form>
    </section>
</main>
<?= $this->endSection() ?>
