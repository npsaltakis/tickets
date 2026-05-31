<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<main class="wrapper">
    <div class="booking-success-page">
        <div class="booking-success-icon">✓</div>
        <h1 class="booking-success-title"><?= esc(lang('App.bookingSuccessTitle')) ?></h1>
        <p class="subtitle"><?= esc($message ?: lang('App.bookingSuccess')) ?></p>

        <div class="booking-success-event">
            <strong><?= esc($event['title']) ?></strong>
            <?php if (!empty($event['start_date'])): ?>
                <span class="meta"><?= esc(date('d/m/Y H:i', strtotime((string)$event['start_date']))) ?></span>
            <?php endif; ?>
            <?php if (!empty($event['location'])): ?>
                <span class="meta"><?= esc($event['location']) ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($ticketCodes)): ?>
            <div class="booking-success-tickets">
                <p class="meta"><?= esc(lang('App.myEventsTicketCodes')) ?>:</p>
                <?php foreach ($ticketCodes as $code): ?>
                    <code class="booking-success-code"><?= esc($code) ?></code>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="booking-success-actions">
            <a href="<?= base_url('my-events') ?>" class="book-btn"><?= esc(lang('App.navMyEvents')) ?></a>
            <a href="<?= base_url('/') ?>" class="auth-link-btn"><?= esc(lang('App.backToEvents')) ?></a>
        </div>
    </div>
</main>
<?= $this->endSection() ?>
