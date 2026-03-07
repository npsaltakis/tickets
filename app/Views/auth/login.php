<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card">
        <h1 class="auth-title"><?= esc(lang('App.loginTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.loginSubtitle')) ?></p>

        <?php if (session()->getFlashdata('login_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('login_error')) ?></p>
        <?php endif; ?>

        <?php if (session()->getFlashdata('login_info')): ?>
            <p class="auth-info"><?= esc((string) session()->getFlashdata('login_info')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('login') ?>" class="auth-form">
            <?= csrf_field() ?>

            <label for="email" class="auth-label"><?= esc(lang('App.emailLabel')) ?></label>
            <input
                id="email"
                name="email"
                type="email"
                value="<?= esc((string) old('email')) ?>"
                class="auth-input"
                required
                autocomplete="email"
            >

            <label for="password" class="auth-label"><?= esc(lang('App.passwordLabel')) ?></label>
            <input
                id="password"
                name="password"
                type="password"
                class="auth-input"
                required
                autocomplete="current-password"
            >

            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.loginButton')) ?></button>
        </form>

        <div class="auth-links">
            <a class="auth-link-btn" href="<?= base_url('register') ?>"><?= esc(lang('App.registerButton')) ?></a>
            <a class="auth-link-btn" href="<?= base_url('lost-password') ?>"><?= esc(lang('App.lostPasswordButton')) ?></a>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
