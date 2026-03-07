<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <?php
    $status = strtolower((string) ($event['status'] ?? 'inactive'));
    $capacity = isset($event['capacity']) && $event['capacity'] !== null ? (int) $event['capacity'] : 0;
    $canBook = $capacity > 0;
    $rawImage = (string) ($event['image'] ?? '');
    $imageUrl = $rawImage !== ''
        ? (preg_match('#^https?://#i', $rawImage) ? $rawImage : base_url(ltrim($rawImage, '/')))
        : '';
    $startDate = $event['start_date'] ?? null;
    $endDate = $event['end_date'] ?? null;
    ?>

    <a class="back-link" href="<?= base_url('/') ?>">&larr; <?= esc(lang('App.backToEvents')) ?></a>

    <?php if (session()->getFlashdata('event_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('event_info')) ?></p>
    <?php endif; ?>

    <section class="event-details-card">
        <?php if ($imageUrl !== ''): ?>
            <img class="event-hero" src="<?= esc($imageUrl) ?>" alt="<?= esc($event['title']) ?>">
        <?php else: ?>
            <div class="event-hero event-image-placeholder"><?= esc(lang('App.noImage')) ?></div>
        <?php endif; ?>

        <div class="event-details-body">
            <div class="row">
                <h1 class="event-page-title"><?= esc($event['title']) ?></h1>
                <span class="status <?= esc($status) ?>"><?= esc($status) ?></span>
            </div>

            <?php if (!empty($startDate)): ?>
                <p class="meta"><strong><?= esc(lang('App.startDate')) ?>:</strong> <?= esc(date('d/m/Y H:i', strtotime((string) $startDate))) ?></p>
            <?php endif; ?>

            <?php if (!empty($endDate)): ?>
                <p class="meta"><strong><?= esc(lang('App.endDate')) ?>:</strong> <?= esc(date('d/m/Y H:i', strtotime((string) $endDate))) ?></p>
            <?php endif; ?>

            <?php if (!empty($event['location'])): ?>
                <p class="meta"><strong><?= esc(lang('App.location')) ?>:</strong> <?= esc($event['location']) ?></p>
            <?php endif; ?>

            <p class="meta"><strong><?= esc(lang('App.type')) ?>:</strong> <?= esc($event['event_type'] ?? 'free') ?></p>
            <p class="meta"><strong><?= esc(lang('App.seatsRemaining')) ?>:</strong> <?= esc((string) $capacity) ?></p>

            <?php if (($event['event_type'] ?? 'free') === 'donation'): ?>
                <p class="meta"><strong><?= esc(lang('App.minimumDonation')) ?>:</strong> €<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2)) ?></p>
            <?php endif; ?>

            <?php if (!empty($event['description'])): ?>
                <p class="event-description"><?= esc($event['description']) ?></p>
            <?php endif; ?>

            <div class="booking-box">
                <label class="meta" for="seats"><strong><?= esc(lang('App.seats')) ?>:</strong></label>
                <input
                    id="seats"
                    class="seats-input"
                    type="number"
                    min="1"
                    max="<?= esc((string) max($capacity, 1)) ?>"
                    value="<?= esc((string) ($canBook ? 1 : 0)) ?>"
                    <?= $canBook ? '' : 'disabled' ?>
                    data-limit-message="<?= esc(lang('App.seatsLimitError')) ?>">
                <button type="button" class="book-btn" <?= $canBook ? '' : 'disabled' ?>><?= esc(lang('App.bookSeat')) ?></button>
                <p id="seats-error" class="field-error" aria-live="polite"></p>
            </div>
        </div>
    </section>
</main>
<script src="<?= base_url('assets/js/event-show.js') ?>"></script>
<?= $this->endSection() ?>
