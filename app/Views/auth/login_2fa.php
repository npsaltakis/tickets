<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card">
        <h1 class="auth-title"><?= esc(lang('App.twoFactorTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.twoFactorPrompt')) ?></p>

        <?php if (session()->getFlashdata('login_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('login_error')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('login/2fa') ?>" class="auth-form">
            <?= csrf_field() ?>
            <label for="code" class="auth-label"><?= esc(lang('App.twoFactorCodeLabel')) ?></label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" class="auth-input" autocomplete="one-time-code" autofocus required>
            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.twoFactorVerify')) ?></button>
        </form>
    </section>
</main>
<?= $this->endSection() ?>