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
    $bookingsEnabled = (int) ($event['bookings_enabled'] ?? 1) === 1;
    $isExpired = !empty($event['end_date']) && strtotime((string) $event['end_date']) !== false && strtotime((string) $event['end_date']) < time();
    $canBook = $bookingsEnabled && ! $isExpired && $remainingSeats > 0 && $status === 'active';
    $isDonationEvent = ($event['event_type'] ?? 'free') === 'donation';
    $isLoggedIn = session()->get('is_logged_in') === true;
    $isAdmin = $isLoggedIn && (string) session()->get('user_role') === 'admin';
    $hasOnlineAccess = (bool) ($hasOnlineAccess ?? false);
    $userTicketCodes = array_values(array_filter((array) ($userTicketCodes ?? [])));
    $hasExistingBooking = !empty($userTicketCodes);
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

    <div class="event-show-topbar">
        <a class="back-link" href="<?= base_url('/') ?>">&larr; <?= esc(lang('App.backToEvents')) ?></a>
        <?php
        $shareUrl   = urlencode(base_url('events/' . $event['slug']));
        $shareTitle = urlencode($event['title']);
        ?>
        <div class="social-share">
            <span class="meta"><?= esc(lang('App.shareLabel')) ?>:</span>
            <a class="social-share-btn social-share-btn--fb" href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener noreferrer" title="Facebook">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a class="social-share-btn social-share-btn--wa" href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener noreferrer" title="WhatsApp">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            </a>
            <a class="social-share-btn social-share-btn--x" href="https://x.com/intent/tweet?text=<?= $shareTitle ?>&url=<?= $shareUrl ?>" target="_blank" rel="noopener noreferrer" title="X / Twitter">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
            <button class="social-share-btn social-share-btn--copy" onclick="navigator.clipboard.writeText('<?= esc(base_url('events/' . $event['slug'])) ?>').then(()=>this.title='Copied!')" title="<?= esc(lang('App.shareCopyLink')) ?>">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </button>
        </div>
    </div>

    <?php if (session()->getFlashdata('event_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('event_info')) ?></p>
    <?php endif; ?>

    <?php if (session()->getFlashdata('event_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('event_error')) ?></p>
    <?php endif; ?>

    <section class="event-details-card">
        <?php if ($imageUrl !== ''): ?>
            <img class="event-hero" src="<?= esc($imageUrl) ?>" alt="<?= esc($event['title']) ?>" width="1200" height="675" fetchpriority="high">
        <?php else: ?>
            <div class="event-hero event-image-placeholder"><?= esc(lang('App.noImage')) ?></div>
        <?php endif; ?>

        <div class="event-details-body">
            <div class="row">
                <h1 class="event-page-title"><?= esc($event['title']) ?></h1>
                <span class="status <?= esc($status) ?>"><?= esc($status) ?></span>
            </div>

            <?php if ($isAdmin): ?>
                <div class="event-admin-actions">
                    <a class="book-btn event-edit-btn" href="<?= base_url('events/' . $event['slug'] . '/edit') ?>"><?= esc(lang('App.eventEditButton')) ?></a>
                    <form method="post" action="<?= base_url('events/' . $event['slug'] . '/duplicate') ?>" class="event-inline-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="book-btn event-duplicate-btn"><?= esc(lang('App.eventDuplicateButton')) ?></button>
                    </form>
                    <form method="post" action="<?= base_url('events/' . $event['slug'] . '/delete') ?>" class="event-inline-form" onsubmit="return confirm('<?= esc(lang('App.eventDeleteConfirm'), 'attr') ?>');">
                        <?= csrf_field() ?>
                        <button type="submit" class="book-btn event-delete-btn"><?= esc(lang('App.eventDeleteButton')) ?></button>
                    </form>
                </div>
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

            <?php if ($status === 'active' && ! $bookingsEnabled): ?>
                <p class="auth-info alert-inline"><?= esc(lang('App.bookingClosedMessage')) ?></p>
            <?php endif; ?>

            <?php if ($isDonationEvent): ?>
                <p class="meta"><strong><?= esc(lang('App.minimumDonation')) ?>:</strong> €<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2)) ?></p>
            <?php endif; ?>

            <?php if (!empty($event['description'])): ?>
                <p class="event-description"><?= esc($event['description']) ?></p>
            <?php endif; ?>

            <?php if (!empty($userTicketCodes)): ?>
                <section class="event-access-card">
                    <div class="event-map-header">
                        <h2 class="event-map-title"><?= esc(lang('App.eventYourTicketsTitle')) ?></h2>
                    </div>
                    <p class="meta"><?= esc(lang('App.eventYourTicketsHelp')) ?></p>
                    <p class="ticket-code-list"><?= esc(implode(', ', $userTicketCodes)) ?></p>
                </section>
            <?php endif; ?>

            <?php if ($showOnlineSection): ?>
                <section class="event-access-card">
                    <div class="event-map-header">
                        <h2 class="event-map-title"><?= esc(lang('App.eventOnlineAccessTitle')) ?></h2>
                    </div>

                    <?php if ($hasOnlineAccess): ?>
                        <?php if ($onlineUrl !== ''): ?>
                            <p class="meta"><a class="event-access-link" href="<?= esc($onlineUrl) ?>" target="_blank" rel="noopener noreferrer"><?= esc(lang('App.eventJoinOnline')) ?></a></p>
                        <?php else: ?>
                            <p class="meta"><?= esc(lang('App.eventOnlineAccessSentByEmail')) ?></p>
                        <?php endif; ?>
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
                <form method="post" action="<?= base_url('events/' . $event['slug'] . '/book') ?>" class="booking-box" id="free-booking-form">
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
                    <label class="booking-consent" for="booking_consent">
                        <input id="booking_consent" name="accept_terms" type="checkbox" value="1" data-error-message="<?= esc(lang('App.eventBookingConsentError'), 'attr') ?>" <?= $hasExistingBooking ? 'checked' : '' ?> <?= $canBook ? '' : 'disabled' ?>>
                        <span><?= esc(lang('App.eventBookingConsentLabelStart')) ?><a href="<?= base_url('terms') ?>" target="_blank" rel="noopener noreferrer"><?= esc(lang('App.eventBookingConsentTerms')) ?></a><?= esc(lang('App.eventBookingConsentMiddle')) ?><a href="<?= base_url('privacy-policy') ?>" target="_blank" rel="noopener noreferrer"><?= esc(lang('App.eventBookingConsentPrivacy')) ?></a>.</span>
                    </label>
                    <button type="submit" class="book-btn" <?= $canBook ? '' : 'disabled' ?>><?= esc(lang('App.bookSeat')) ?></button>
                    <p id="seats-error" class="field-error" aria-live="polite"></p>
                    <p id="booking-consent-error" class="field-error" aria-live="polite"></p>
                </form>
            <?php else: ?>
                <section
                    id="donation-booking"
                    class="booking-box donation-booking-box"
                    data-create-order-url="<?= esc(base_url('events/' . $event['slug'] . '/paypal/order'), 'attr') ?>"
                    data-capture-order-url="<?= esc(base_url('events/' . $event['slug'] . '/paypal/capture'), 'attr') ?>"
                    data-min-donation="<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2, '.', ''), 'attr') ?>"
                    data-min-message="<?= esc(lang('App.donationMinimumError'), 'attr') ?>"
                    data-paypal-error="<?= esc(lang('App.paypalGenericError'), 'attr') ?>"
                    data-total-label="<?= esc(lang('App.donationTotalLabel'), 'attr') ?>"
                    data-total-template="<?= esc(lang('App.donationTotalSummary'), 'attr') ?>"
                    data-consent-message="<?= esc(lang('App.eventBookingConsentError'), 'attr') ?>"
                    data-csrf-header="<?= esc(csrf_header(), 'attr') ?>"
                    data-csrf-token="<?= esc(csrf_hash(), 'attr') ?>"
                    data-csrf-name="<?= esc(csrf_token(), 'attr') ?>">
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

                    <p id="donation-total" class="meta"><strong><?= esc(lang('App.donationTotalLabel')) ?>:</strong> €<?= esc(number_format((float) ($event['min_donation'] ?? 0), 2)) ?></p>

                    <label class="booking-consent" for="donation_booking_consent">
                        <input id="donation_booking_consent" name="accept_terms" type="checkbox" value="1" data-error-message="<?= esc(lang('App.eventBookingConsentError'), 'attr') ?>" <?= $hasExistingBooking ? 'checked' : '' ?> <?= $canBook && $isLoggedIn && $paypalClientId !== '' ? '' : 'disabled' ?>>
                        <span><?= esc(lang('App.eventBookingConsentLabelStart')) ?><a href="<?= base_url('terms') ?>" target="_blank" rel="noopener noreferrer"><?= esc(lang('App.eventBookingConsentTerms')) ?></a><?= esc(lang('App.eventBookingConsentMiddle')) ?><a href="<?= base_url('privacy-policy') ?>" target="_blank" rel="noopener noreferrer"><?= esc(lang('App.eventBookingConsentPrivacy')) ?></a>.</span>
                    </label>

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
                    <p id="booking-consent-error" class="field-error" aria-live="polite"></p>
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

