<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card">
        <h1 class="auth-title"><?= esc(lang('App.registerTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.registerSubtitle')) ?></p>

        <?php if (session()->getFlashdata('register_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('register_error')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('register') ?>" class="auth-form">
            <?= csrf_field() ?>

            <label for="first_name" class="auth-label"><?= esc(lang('App.firstNameLabel')) ?></label>
            <input id="first_name" name="first_name" type="text" value="<?= esc((string) old('first_name')) ?>" class="auth-input" required>

            <label for="last_name" class="auth-label"><?= esc(lang('App.lastNameLabel')) ?></label>
            <input id="last_name" name="last_name" type="text" value="<?= esc((string) old('last_name')) ?>" class="auth-input" required>

            <label for="email" class="auth-label"><?= esc(lang('App.emailLabel')) ?></label>
            <input id="email" name="email" type="email" value="<?= esc((string) old('email')) ?>" class="auth-input" required autocomplete="email">

            <label for="password" class="auth-label"><?= esc(lang('App.passwordLabel')) ?></label>
            <input id="password" name="password" type="password" class="auth-input" required autocomplete="new-password">

            <label for="confirm_password" class="auth-label"><?= esc(lang('App.confirmPasswordLabel')) ?></label>
            <input id="confirm_password" name="confirm_password" type="password" class="auth-input" required autocomplete="new-password">

            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.registerButton')) ?></button>
        </form>

        <div class="auth-links">
            <a class="auth-link-btn" href="<?= base_url('login') ?>"><?= esc(lang('App.loginButton')) ?></a>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
