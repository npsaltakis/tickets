<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card">
        <h1 class="auth-title"><?= esc(lang('App.lostPasswordTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.lostPasswordSubtitle')) ?></p>

        <?php if (session()->getFlashdata('lost_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('lost_error')) ?></p>
        <?php endif; ?>

        <?php if (session()->getFlashdata('lost_info')): ?>
            <p class="auth-info"><?= esc((string) session()->getFlashdata('lost_info')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('lost-password') ?>" class="auth-form">
            <?= csrf_field() ?>

            <label for="email" class="auth-label"><?= esc(lang('App.emailLabel')) ?></label>
            <input id="email" name="email" type="email" value="<?= esc((string) old('email')) ?>" class="auth-input" required autocomplete="email">

            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.sendResetLinkButton')) ?></button>
        </form>

        <div class="auth-links">
            <a class="auth-link-btn" href="<?= base_url('login') ?>"><?= esc(lang('App.loginButton')) ?></a>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
