<?php
$assetVersion = static function (string $relativePath): string {
    $fullPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
?><?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
    <?php
    $status = strtolower((string) ($event['status'] ?? 'inactive'));
    $remainingSeats = isset($event['remaining_seats']) ? (int) $event['remaining_seats'] : 0;
    $canBook = $remainingSeats > 0 && $status === 'active';
    $isDonationEvent = ($event['event_type'] ?? 'free') === 'donation';
    $isLoggedIn = session()->get('is_logged_in') === true;
    $isAdmin = $isLoggedIn && (string) session()->get('user_role') === 'admin';
    $hasOnlineAccess = (bool) ($hasOnlineAccess ?? false);
    $paypalClientId = trim((string) ($paypalClientId ?? ''));
    $paypalLocale = service('request')->getLocale() === 'en' ? 'en_US' : 'el_GR';
    $rawImage = (string) ($event['image'] ?? '');
    $imageUrl = $rawImage !== ''
        ? (preg_match('#^https?://#i', $rawImage) ? $rawImage : base_url(ltrim($rawImage, '/')))
        : '';
    $startDate = $event['start_date'] ?? null;
    $endDate = $event['end_date'] ?? null;
    $infoUrl = trim((string) ($event['info_url'] ?? ''));
    $address = trim((string) ($event['address'] ?? ''));
    $eventFormat = (string) ($event['event_format'] ?? 'physical');
    $onlineUrl = trim((string) ($event['online_url'] ?? ''));
    $onlineAccessNotes = trim((string) ($event['online_access_notes'] ?? ''));
    $showMap = in_array($eventFormat, ['physical', 'hybrid'], true) && $address !== '';
    $mapQuery = $showMap ? $address : '';
    $mapEmbedUrl = $mapQuery !== ''
        ? 'https://www.google.com/maps?q=' . rawurlencode($mapQuery) . '&output=embed'
        : '';
    $mapLinkUrl = $mapQuery !== ''
        ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapQuery)
        : '';
    $showOnlineSection = in_array($eventFormat, ['online', 'hybrid'], true);
    $formatLabels = [
        'physical' => lang('App.eventFormatPhysical'),
        'online' => lang('App.eventFormatOnline'),
        'hybrid' => lang('App.eventFormatHybrid'),
    ];
    $formatLabel = $formatLabels[$eventFormat] ?? ucfirst($eventFormat);
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

            <?php if ($isAdmin): ?>
                <p class="event-admin-actions">
                    <a class="book-btn event-edit-btn" href="<?= base_url('events/' . $event['slug'] . '/edit') ?>"><?= esc(lang('App.eventEditButton')) ?></a>
                </p>
            <?php endif; ?>

            <?php if (!empty($startDate)): ?>
                <p class="meta"><strong><?= esc(lang('App.startDate')) ?>:</strong> <?= esc(date('d/m/Y H:i', strtotime((string) $startDate))) ?></p>
            <?php endif; ?>

            <?php if (!empty($endDate)): ?>
                <p class="meta"><strong><?= esc(lang('App.endDate')) ?>:</strong> <?= esc(date('d/m/Y H:i', strtotime((string) $endDate))) ?></p>
            <?php endif; ?>

            <?php if (!empty($event['location'])): ?>
                <p class="meta"><strong><?= esc(lang('App.location')) ?>:</strong> <?= esc($event['location']) ?></p>
            <?php endif; ?>

            <p class="meta"><strong><?= esc(lang('App.eventFormatLabel')) ?>:</strong> <?= esc($formatLabel) ?></p>

            <?php if ($showMap): ?>
                <p class="meta"><strong><?= esc(lang('App.address')) ?>:</strong> <?= esc($address) ?></p>
            <?php endif; ?>

            <?php if (!empty($event['info_phone'])): ?>
                <p class="meta"><strong><?= esc(lang('App.phone')) ?>:</strong> <?= esc($event['info_phone']) ?></p>
            <?php endif; ?>

            <?php if ($infoUrl !== ''): ?>
                <p class="meta"><strong><?= esc(lang('App.infoUrl')) ?>:</strong> <a class="event-info-link" href="<?= esc($infoUrl) ?>" target="_blank" rel="noopener noreferrer"><?= esc(lang('App.eventMoreInfo')) ?></a></p>
            <?php endif; ?>

            <p class="meta"><strong><?= esc(lang('App.type')) ?>:</strong> <?= esc($event['event_type'] ?? 'free') ?></p>
            <p class="meta"><strong><?= esc(lang('App.seatsRemaining')) ?>:</strong> <?= esc((string) $remainingSeats) ?></p>

            <?php if ($isDonationEvent): ?>
                <p class="meta"><strong><?= esc(lang('App.minimumDonation')) ?>:</strong> €<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2)) ?></p>
            <?php endif; ?>

            <?php if (!empty($event['description'])): ?>
                <p class="event-description"><?= esc($event['description']) ?></p>
            <?php endif; ?>

            <?php if ($showOnlineSection): ?>
                <section class="event-access-card">
                    <div class="event-map-header">
                        <h2 class="event-map-title"><?= esc(lang('App.eventOnlineAccessTitle')) ?></h2>
                    </div>

                    <?php if ($hasOnlineAccess): ?>
                        <p class="meta"><?= esc(lang('App.eventOnlineAccessSentByEmail')) ?></p>
                        <?php if ($onlineAccessNotes !== ''): ?>
                            <p class="event-access-note"><?= nl2br(esc($onlineAccessNotes)) ?></p>
                        <?php endif; ?>
                        <?php if ($onlineUrl === '' && $onlineAccessNotes === ''): ?>
                            <p class="meta"><?= esc(lang('App.eventOnlineAccessUnavailable')) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="meta"><?= esc(lang('App.eventOnlineAccessLocked')) ?></p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($mapEmbedUrl !== ''): ?>
                <section class="event-map-card">
                    <div class="event-map-header">
                        <h2 class="event-map-title"><?= esc(lang('App.eventMapTitle')) ?></h2>
                        <a class="event-map-link" href="<?= esc($mapLinkUrl) ?>" target="_blank" rel="noopener noreferrer"><?= esc(lang('App.eventOpenMap')) ?></a>
                    </div>
                    <iframe
                        class="event-map-frame"
                        src="<?= esc($mapEmbedUrl) ?>"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                        title="<?= esc(lang('App.eventMapTitle'), 'attr') ?>"></iframe>
                </section>
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
                    <div class="donation-booking-controls">
                        <div class="donation-booking-field">
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
                        </div>

                        <div class="donation-booking-field">
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
                        </div>
                    </div>

                    <div class="booking-paypal-block<?= !$isLoggedIn ? ' booking-paypal-block--auth' : '' ?><?= $paypalClientId === '' ? ' booking-paypal-block--message' : '' ?>">
                        <?php if (!$isLoggedIn): ?>
                            <p class="booking-auth-message"><?= esc(lang('App.bookingLoginRequired')) ?></p>
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
    <script src="https://www.paypal.com/sdk/js?client-id=<?= esc($paypalClientId) ?>&currency=EUR&intent=capture&locale=<?= esc($paypalLocale) ?>"></script>
<?php endif; ?>
<script src="<?= base_url('assets/js/event-show.js') ?>?v=<?= esc($assetVersion('assets/js/event-show.js')) ?>"></script>
<?= $this->endSection() ?>

