<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card install-card">
        <h1 class="auth-title"><?= esc(lang('App.installTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.installSubtitle')) ?></p>

        <?php if (session()->getFlashdata('install_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('install_error')) ?></p>
        <?php endif; ?>

        <h2><?= esc(lang('App.installChecksTitle')) ?></h2>
        <ul class="install-checks">
            <?php foreach ($checks as $check): ?>
                <li class="install-check <?= $check['ok'] ? 'is-ok' : ($check['required'] ? 'is-error' : 'is-warning') ?>">
                    <span class="install-check-mark"><?= $check['ok'] ? '&#10003;' : ($check['required'] ? '&#10007;' : '!') ?></span>
                    <strong><?= esc($check['label']) ?></strong>
                    <span class="meta"><?= esc($check['detail']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (! $canSubmit): ?>
            <p class="auth-error"><?= esc(lang('App.installChecksFailed')) ?></p>
        <?php else: ?>
            <h2><?= esc(lang('App.installAdminTitle')) ?></h2>
            <form method="post" action="<?= base_url('install') ?>" class="auth-form">
                <?= csrf_field() ?>

                <label for="install_token" class="auth-label"><?= esc(lang('App.installTokenLabel')) ?></label>
                <input id="install_token" name="install_token" type="password" class="auth-input" required autocomplete="off">

                <label for="first_name" class="auth-label"><?= esc(lang('App.firstNameLabel')) ?></label>
                <input id="first_name" name="first_name" type="text" value="<?= esc((string) old('first_name')) ?>" class="auth-input" required>

                <label for="last_name" class="auth-label"><?= esc(lang('App.lastNameLabel')) ?></label>
                <input id="last_name" name="last_name" type="text" value="<?= esc((string) old('last_name')) ?>" class="auth-input" required>

                <label for="email" class="auth-label"><?= esc(lang('App.emailLabel')) ?></label>
                <input id="email" name="email" type="email" value="<?= esc((string) old('email')) ?>" class="auth-input" required autocomplete="email">

                <label for="password" class="auth-label"><?= esc(lang('App.passwordLabel')) ?></label>
                <input id="password" name="password" type="password" class="auth-input" required minlength="8" autocomplete="new-password">

                <label for="confirm_password" class="auth-label"><?= esc(lang('App.confirmPasswordLabel')) ?></label>
                <input id="confirm_password" name="confirm_password" type="password" class="auth-input" required minlength="8" autocomplete="new-password">

                <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.installButton')) ?></button>
            </form>
        <?php endif; ?>
    </section>
</main>
<?= $this->endSection() ?>
