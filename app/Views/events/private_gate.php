<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card">
        <h1 class="auth-title"><?= esc(lang('App.privateEventTitle')) ?></h1>
        <p class="subtitle"><?= esc(lang('App.privateEventPrompt')) ?></p>

        <?php if (session()->getFlashdata('event_error')): ?>
            <p class="auth-error"><?= esc((string) session()->getFlashdata('event_error')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= base_url('events/' . $event['slug'] . '/access') ?>" class="auth-form">
            <?= csrf_field() ?>
            <label for="access_code" class="auth-label"><?= esc(lang('App.privateEventCodeLabel')) ?></label>
            <input id="access_code" name="access_code" type="text" class="auth-input" maxlength="32" autocomplete="off" autofocus required>
            <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.privateEventEnter')) ?></button>
        </form>
    </section>
</main>
<?= $this->endSection() ?>