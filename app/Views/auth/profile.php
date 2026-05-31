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
    </section>
</main>
<?= $this->endSection() ?>
