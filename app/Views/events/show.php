<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <?php
    $status = strtolower((string) ($event['status'] ?? 'inactive'));
    $capacity = isset($event['capacity']) && $event['capacity'] !== null ? (int) $event['capacity'] : 0;
    $rawImage = (string) ($event['image'] ?? '');
    $imageUrl = $rawImage !== ''
        ? (preg_match('#^https?://#i', $rawImage) ? $rawImage : base_url(ltrim($rawImage, '/')))
        : '';
    ?>

    <a class="back-link" href="<?= base_url('/') ?>">← Back to events page</a>

    <section class="event-details-card">
        <?php if ($imageUrl !== ''): ?>
            <img class="event-hero" src="<?= esc($imageUrl) ?>" alt="<?= esc($event['title']) ?>">
        <?php else: ?>
            <div class="event-hero event-image-placeholder">No image</div>
        <?php endif; ?>

        <div class="event-details-body">
            <div class="row">
                <h1 class="event-page-title"><?= esc($event['title']) ?></h1>
                <span class="status <?= esc($status) ?>"><?= esc($status) ?></span>
            </div>

            <p class="meta"><strong>Date:</strong> <?= esc(date('d/m/Y H:i', strtotime($event['event_date']))) ?></p>

            <?php if (!empty($event['location'])): ?>
                <p class="meta"><strong>Location:</strong> <?= esc($event['location']) ?></p>
            <?php endif; ?>

            <p class="meta"><strong>Type:</strong> <?= esc($event['event_type'] ?? 'free') ?></p>
            <p class="meta"><strong>Seats remaining:</strong> <?= esc((string) $capacity) ?></p>

            <?php if (($event['event_type'] ?? 'free') === 'donation'): ?>
                <p class="meta"><strong>Minimum Donation:</strong> €<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2)) ?></p>
            <?php endif; ?>

            <?php if (!empty($event['description'])): ?>
                <p class="event-description"><?= esc($event['description']) ?></p>
            <?php endif; ?>

            <div class="booking-box">
                <label class="meta" for="seats"><strong>Θέσεις:</strong></label>
                <input id="seats" class="seats-input" type="number" min="1" max="<?= esc((string) max($capacity, 1)) ?>" value="1">
                <button type="button" class="book-btn">Book Seat</button>
            </div>
        </div>
    </section>
</main>
<?= $this->endSection() ?>




