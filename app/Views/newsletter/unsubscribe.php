<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <section class="auth-card">
        <h1 class="auth-title"><?= esc(lang('App.newsletterUnsubscribeTitle')) ?></h1>

        <?php if (! $valid): ?>
            <p role="alert" class="auth-error"><?= esc(lang('App.newsletterUnsubscribeInvalid')) ?></p>
        <?php elseif ($done || ! $subscribed): ?>
            <p role="status" class="auth-info"><?= esc(lang('App.newsletterUnsubscribeDone')) ?></p>
        <?php else: ?>
            <p class="subtitle"><?= esc(lang('App.newsletterUnsubscribeText')) ?></p>
            <form method="post" action="<?= base_url('newsletter/unsubscribe/' . rawurlencode($token)) ?>" class="auth-form">
                <?= csrf_field() ?>
                <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.newsletterUnsubscribeButton')) ?></button>
            </form>
        <?php endif; ?>

        <div class="auth-links">
            <a class="auth-link-btn" href="<?= base_url('/') ?>"><?= esc(lang('App.navHome')) ?></a>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
