<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <?php
    $status = strtolower((string) ($event['status'] ?? 'inactive'));
    $remainingSeats = isset($event['remaining_seats']) ? (int) $event['remaining_seats'] : 0;
    $canBook = $remainingSeats > 0 && $status === 'active';
    $isDonationEvent = ($event['event_type'] ?? 'free') === 'donation';
    $isLoggedIn = session()->get('is_logged_in') === true;
    $paypalClientId = trim((string) ($paypalClientId ?? ''));
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

    <?php if (session()->getFlashdata('event_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('event_error')) ?></p>
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
            <p class="meta"><strong><?= esc(lang('App.seatsRemaining')) ?>:</strong> <?= esc((string) $remainingSeats) ?></p>

            <?php if ($isDonationEvent): ?>
                <p class="meta"><strong><?= esc(lang('App.minimumDonation')) ?>:</strong> €<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2)) ?></p>
            <?php endif; ?>

            <?php if (!empty($event['description'])): ?>
                <p class="event-description"><?= esc($event['description']) ?></p>
            <?php endif; ?>

            <?php if (!$isDonationEvent): ?>
                <form method="post" action="<?= base_url('events/' . $event['slug'] . '/book') ?>" class="booking-box">
                    <?= csrf_field() ?>
                    <label class="meta" for="seats"><strong><?= esc(lang('App.seats')) ?>:</strong></label>
                    <input
                        id="seats"
                        name="seats"
                        class="seats-input"
                        type="number"
                        min="1"
                        max="<?= esc((string) max($remainingSeats, 1)) ?>"
                        value="<?= esc((string) ($canBook ? 1 : 0)) ?>"
                        <?= $canBook ? '' : 'disabled' ?>
                        data-limit-message="<?= esc(lang('App.seatsLimitError')) ?>">
                    <button type="submit" class="book-btn" <?= $canBook ? '' : 'disabled' ?>><?= esc(lang('App.bookSeat')) ?></button>
                    <p id="seats-error" class="field-error" aria-live="polite"></p>
                </form>
            <?php else: ?>
                <section
                    id="donation-booking"
                    class="booking-box donation-booking-box"
                    data-create-order-url="<?= esc(base_url('events/' . $event['slug'] . '/paypal/order'), 'attr') ?>"
                    data-capture-order-url="<?= esc(base_url('events/' . $event['slug'] . '/paypal/capture'), 'attr') ?>"
                    data-min-donation="<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2, '.', ''), 'attr') ?>"
                    data-min-message="<?= esc(lang('App.donationMinimumError'), 'attr') ?>"
                    data-paypal-error="<?= esc(lang('App.paypalGenericError'), 'attr') ?>">
                    <label class="meta" for="seats"><strong><?= esc(lang('App.seats')) ?>:</strong></label>
                    <input
                        id="seats"
                        name="seats"
                        class="seats-input"
                        type="number"
                        min="1"
                        max="<?= esc((string) max($remainingSeats, 1)) ?>"
                        value="<?= esc((string) ($canBook ? 1 : 0)) ?>"
                        <?= $canBook ? '' : 'disabled' ?>
                        data-limit-message="<?= esc(lang('App.seatsLimitError')) ?>">

                    <label class="meta" for="donation_amount"><strong><?= esc(lang('App.donationAmountLabel')) ?>:</strong></label>
                    <input
                        id="donation_amount"
                        name="donation_amount"
                        class="seats-input donation-input"
                        type="number"
                        min="<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2, '.', '')) ?>"
                        step="0.01"
                        value="<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2, '.', '')) ?>"
                        <?= $canBook && $isLoggedIn && $paypalClientId !== '' ? '' : 'disabled' ?>>

                    <div class="booking-paypal-block">
                        <?php if (!$isLoggedIn): ?>
                            <p class="meta"><?= esc(lang('App.bookingLoginRequired')) ?></p>
                            <a class="auth-link-btn" href="<?= base_url('login') ?>"><?= esc(lang('App.loginButton')) ?></a>
                        <?php elseif ($paypalClientId === ''): ?>
                            <p class="auth-error"><?= esc(lang('App.paypalConfigurationError')) ?></p>
                        <?php else: ?>
                            <div id="paypal-button-container"></div>
                        <?php endif; ?>
                    </div>

                    <p id="seats-error" class="field-error" aria-live="polite"></p>
                    <p id="booking-error" class="field-error" aria-live="polite"></p>
                </section>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php if ($isDonationEvent && $paypalClientId !== '' && $isLoggedIn && $canBook): ?>
    <script src="https://www.paypal.com/sdk/js?client-id=<?= esc($paypalClientId) ?>&currency=EUR&intent=capture"></script>
<?php endif; ?>
<script src="<?= base_url('assets/js/event-show.js') ?>"></script>
<?= $this->endSection() ?>
