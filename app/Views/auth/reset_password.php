<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card">
        <h1 class="auth-title"><?= esc(lang('App.resetPasswordTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.resetPasswordSubtitle')) ?></p>

        <?php if (session()->getFlashdata('reset_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('reset_error')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('reset-password') ?>" class="auth-form">
            <?= csrf_field() ?>
            <input type="hidden" name="selector" value="<?= esc($selector) ?>">
            <input type="hidden" name="token" value="<?= esc($token) ?>">

            <label for="new_password" class="auth-label"><?= esc(lang('App.newPasswordLabel')) ?></label>
            <input id="new_password" name="new_password" type="password" class="auth-input" required autocomplete="new-password">

            <label for="confirm_password" class="auth-label"><?= esc(lang('App.confirmPasswordLabel')) ?></label>
            <input id="confirm_password" name="confirm_password" type="password" class="auth-input" required autocomplete="new-password">

            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.resetPasswordButton')) ?></button>
        </form>
    </section>
</main>
<?= $this->endSection() ?>
