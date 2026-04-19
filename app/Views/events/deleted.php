<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper deleted-events-page">
    <a class="back-link" href="<?= base_url('/') ?>">&larr; <?= esc(lang('App.backToEvents')) ?></a>

    <div class="events-header">
        <div>
            <span class="eyebrow"><?= esc(lang('App.deletedEventsEyebrow')) ?></span>
            <h1><?= esc(lang('App.deletedEventsTitle')) ?></h1>
            <p class="subtitle"><?= esc(lang('App.deletedEventsSubtitle')) ?></p>
        </div>
    </div>

    <?php if (session()->getFlashdata('event_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('event_error')) ?></p>
    <?php endif; ?>

    <?php if (empty($events)): ?>
        <div class="empty"><?= esc(lang('App.deletedEventsEmpty')) ?></div>
    <?php else: ?>
        <section class="deleted-events-list">
            <?php foreach ($events as $event): ?>
                <article class="deleted-event-card">
                    <div>
                        <h2><?= esc((string) ($event['title'] ?? '-')) ?></h2>
                        <p><?= esc(lang('App.deletedEventsDeletedAt')) ?>: <?= esc(! empty($event['deleted_at']) ? date('d/m/Y H:i', strtotime((string) $event['deleted_at'])) : '-') ?></p>
                        <p><?= esc(lang('App.location')) ?>: <?= esc((string) ($event['location'] ?? '-')) ?></p>
                    </div>
                    <form method="post" action="<?= base_url('events/' . $event['slug'] . '/restore') ?>" onsubmit="return confirm('<?= esc(lang('App.eventRestoreConfirm'), 'attr') ?>');">
                        <?= csrf_field() ?>
                        <button type="submit" class="book-btn"><?= esc(lang('App.eventRestoreButton')) ?></button>
                    </form>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?= $this->endSection() ?>
