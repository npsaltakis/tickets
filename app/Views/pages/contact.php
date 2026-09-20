<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card" style="max-width:600px">
        <h1 class="auth-title"><?= esc(lang('App.contactTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.contactSubtitle')) ?></p>

        <?php if (session()->getFlashdata('contact_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('contact_error')) ?></p>
        <?php endif; ?>
        <?php if (session()->getFlashdata('contact_info')): ?>
            <p class="auth-info"><?= esc((string) session()->getFlashdata('contact_info')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('contact') ?>" class="auth-form" style="margin-top:16px">
            <?= csrf_field() ?>
            <div style="position:absolute;left:-9999px" aria-hidden="true">
                <label for="website">Website</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <label class="auth-label" for="name"><?= esc(lang('App.contactName')) ?></label>
            <input id="name" name="name" type="text" class="auth-input" maxlength="100" value="<?= esc((string) old('name'), 'attr') ?>" required>

            <label class="auth-label" for="email"><?= esc(lang('App.emailLabel')) ?></label>
            <input id="email" name="email" type="email" class="auth-input" maxlength="191" value="<?= esc((string) old('email'), 'attr') ?>" required>

            <label class="auth-label" for="message"><?= esc(lang('App.contactMessage')) ?></label>
            <textarea id="message" name="message" class="auth-input" rows="6" maxlength="5000" required><?= esc((string) old('message')) ?></textarea>

            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.contactSend')) ?></button>
        </form>
    </section>
</main>
<?= $this->endSection() ?>
