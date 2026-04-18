<?php
$assetVersion = static function (string $relativePath): string {
    $fullPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
?><?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main
    class="wrapper"
    data-my-events-pdf
    data-ticket-label="<?= esc(lang('App.myEventsPdfTicketLabel'), 'attr') ?>"
    data-event-label="<?= esc(lang('App.bookingEmailEventLabel'), 'attr') ?>"
    data-start-label="<?= esc(lang('App.bookingEmailStartLabel'), 'attr') ?>"
    data-end-label="<?= esc(lang('App.bookingEmailEndLabel'), 'attr') ?>"
    data-location-label="<?= esc(lang('App.bookingEmailLocationLabel'), 'attr') ?>"
    data-address-label="<?= esc(lang('App.address'), 'attr') ?>"
    data-booked-at-label="<?= esc(lang('App.myEventsBookedAt'), 'attr') ?>"
    data-payment-label="<?= esc(lang('App.myEventsPaymentStatus'), 'attr') ?>"
    data-donation-label="<?= esc(lang('App.myEventsDonationTotal'), 'attr') ?>"
    data-export-filename-prefix="<?= esc(lang('App.myEventsPdfFilenamePrefix'), 'attr') ?>"
    data-export-title="<?= esc(lang('App.myEventsPdfTitle'), 'attr') ?>"
    data-export-subtitle="<?= esc(lang('App.myEventsPdfSubtitle'), 'attr') ?>"
>
    <div class="events-header">
        <div>
            <h1><?= esc(lang('App.myEventsTitle')) ?></h1>
            <p class="subtitle"><?= esc(lang('App.myEventsSubtitle')) ?></p>
        </div>
    </div>

    <?php if (empty($events)): ?>
        <div class="empty">
            <?= esc(lang('App.myEventsEmpty')) ?>
        </div>
    <?php else: ?>
        <section class="grid">
            <?php foreach ($events as $event): ?>
                <?php
                $status = strtolower((string) ($event['status'] ?? 'inactive'));
                $statusLabel = lang('App.eventStatus' . ucfirst($status));
                $eventUrl = !empty($event['slug']) ? base_url('events/' . $event['slug']) : '#';
                $rawImage = (string) ($event['image'] ?? '');
                $imageUrl = $rawImage !== ''
                    ? (preg_match('#^https?://#i', $rawImage) ? $rawImage : base_url(ltrim($rawImage, '/')))
                    : '';
                $tickets = array_values(array_filter((array) ($event['tickets'] ?? [])));
                $paymentSummary = (string) ($event['payment_summary'] ?? 'free');
                $paymentLabel = $paymentSummary === 'paid' ? lang('App.paymentStatusPaid') : lang('App.paymentStatusFree');
                $startDateFormatted = !empty($event['start_date']) ? date('d/m/Y H:i', strtotime((string) $event['start_date'])) : '';
                $endDateFormatted = !empty($event['end_date']) ? date('d/m/Y H:i', strtotime((string) $event['end_date'])) : '';
                $bookedAtFormatted = !empty($event['booked_at']) ? date('d/m/Y H:i', strtotime((string) $event['booked_at'])) : '';
                $eventPdfPayload = [
                    'title' => (string) ($event['title'] ?? ''),
                    'location' => (string) ($event['location'] ?? ''),
                    'address' => (string) ($event['address'] ?? ''),
                    'start_date' => $startDateFormatted,
                    'end_date' => $endDateFormatted,
                    'booked_at' => $bookedAtFormatted,
                    'payment_status' => $paymentLabel,
                    'donation_total' => 'EUR ' . number_format((float) ($event['donation_total'] ?? 0), 2),
                ];
                ?>
                <article class="card my-event-card">
                    <a class="card-link" href="<?= esc($eventUrl) ?>">
                        <?php if ($imageUrl !== ''): ?>
                            <img class="event-image" src="<?= esc($imageUrl) ?>" alt="<?= esc($event['title']) ?>">
                        <?php else: ?>
                            <div class="event-image event-image-placeholder"><?= esc(lang('App.noImage')) ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <h2 class="title"><?= esc($event['title']) ?></h2>
                            <span class="status <?= esc($status) ?>"><?= esc($statusLabel) ?></span>
                        </div>

                        <?php if ($startDateFormatted !== ''): ?>
                            <p class="meta"><?= esc(lang('App.startDate')) ?>: <?= esc($startDateFormatted) ?></p>
                        <?php endif; ?>

                        <?php if ($endDateFormatted !== ''): ?>
                            <p class="meta"><?= esc(lang('App.endDate')) ?>: <?= esc($endDateFormatted) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($event['location'])): ?>
                            <p class="meta"><?= esc(lang('App.location')) ?>: <?= esc($event['location']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($event['address'])): ?>
                            <p class="meta"><?= esc(lang('App.address')) ?>: <?= esc($event['address']) ?></p>
                        <?php endif; ?>

                        <p class="meta"><?= esc(lang('App.myEventsTicketsBooked')) ?>: <?= esc((string) ($event['tickets_count'] ?? 0)) ?></p>
                        <p class="meta"><?= esc(lang('App.myEventsDonationTotal')) ?>: €<?= esc(number_format((float) ($event['donation_total'] ?? 0), 2)) ?></p>
                        <p class="meta"><?= esc(lang('App.myEventsPaymentStatus')) ?>: <?= esc($paymentLabel) ?></p>

                        <?php if ($bookedAtFormatted !== ''): ?>
                            <p class="meta"><?= esc(lang('App.myEventsBookedAt')) ?>: <?= esc($bookedAtFormatted) ?></p>
                        <?php endif; ?>

                        <?php if (($event['event_type'] ?? 'free') === 'donation'): ?>
                            <span class="pill"><?= esc(lang('App.eventCreateDonationType')) ?></span>
                        <?php else: ?>
                            <span class="pill"><?= esc(lang('App.freeEvent')) ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if (!empty($tickets)): ?>
                        <div class="ticket-cancel-block">
                            <div class="ticket-code-header">
                                <p class="meta ticket-code-title"><?= esc(lang('App.myEventsTicketCodes')) ?>:</p>
                                <?php if (count($tickets) > 1): ?>
                                    <button
                                        type="button"
                                        class="ticket-export-btn ticket-export-btn--secondary"
                                        data-export-all-tickets
                                        data-event='<?= esc(json_encode($eventPdfPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>'
                                        data-tickets='<?= esc(json_encode(array_map(static fn(array $ticket): string => (string) ($ticket['code'] ?? ''), $tickets), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>'
                                    >
                                        <?= esc(lang('App.myEventsExportAllTicketsPdf')) ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="ticket-code-list">
                                <?php foreach ($tickets as $ticket): ?>
                                    <div class="ticket-code-item">
                                        <code class="ticket-cancel-code"><?= esc($ticket['code']) ?></code>
                                        <button
                                            type="button"
                                            class="ticket-export-btn"
                                            data-export-ticket-pdf
                                            data-event='<?= esc(json_encode($eventPdfPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>'
                                            data-ticket-code="<?= esc((string) ($ticket['code'] ?? ''), 'attr') ?>"
                                        >
                                            <?= esc(lang('App.myEventsExportTicketPdf')) ?>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="<?= base_url('assets/js/my-events.js') ?>?v=<?= esc($assetVersion('assets/js/my-events.js')) ?>"></script>
<?= $this->endSection() ?>
