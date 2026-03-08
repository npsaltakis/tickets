<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper">
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
                $ticketCodes = array_filter((array) ($event['ticket_codes'] ?? []));
                $paymentSummary = (string) ($event['payment_summary'] ?? 'free');
                $paymentLabel = $paymentSummary === 'paid' ? lang('App.paymentStatusPaid') : lang('App.paymentStatusFree');
                ?>
                <a class="card-link" href="<?= esc($eventUrl) ?>">
                    <article class="card">
                        <?php if ($imageUrl !== ''): ?>
                            <img class="event-image" src="<?= esc($imageUrl) ?>" alt="<?= esc($event['title']) ?>">
                        <?php else: ?>
                            <div class="event-image event-image-placeholder"><?= esc(lang('App.noImage')) ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <h2 class="title"><?= esc($event['title']) ?></h2>
                            <span class="status <?= esc($status) ?>"><?= esc($statusLabel) ?></span>
                        </div>

                        <?php if (!empty($event['start_date'])): ?>
                            <p class="meta"><?= esc(lang('App.startDate')) ?>: <?= esc(date('d/m/Y H:i', strtotime((string) $event['start_date']))) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($event['end_date'])): ?>
                            <p class="meta"><?= esc(lang('App.endDate')) ?>: <?= esc(date('d/m/Y H:i', strtotime((string) $event['end_date']))) ?></p>
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

                        <?php if (!empty($event['booked_at'])): ?>
                            <p class="meta"><?= esc(lang('App.myEventsBookedAt')) ?>: <?= esc(date('d/m/Y H:i', strtotime((string) $event['booked_at']))) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($ticketCodes)): ?>
                            <div class="ticket-code-block">
                                <p class="meta ticket-code-title"><?= esc(lang('App.myEventsTicketCodes')) ?>:</p>
                                <p class="ticket-code-list"><?= esc(implode(', ', $ticketCodes)) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (($event['event_type'] ?? 'free') === 'donation'): ?>
                            <span class="pill"><?= esc(lang('App.eventCreateDonationType')) ?></span>
                        <?php else: ?>
                            <span class="pill"><?= esc(lang('App.freeEvent')) ?></span>
                        <?php endif; ?>
                    </article>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?= $this->endSection() ?>
