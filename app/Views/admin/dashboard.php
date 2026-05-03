<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<main class="wrapper admin-dashboard-page">
    <div class="events-header">
        <div>
            <span class="eyebrow"><?= esc(lang('App.adminDashboardEyebrow')) ?></span>
            <h1><?= esc(lang('App.adminDashboardTitle')) ?></h1>
            <p class="subtitle"><?= esc(lang('App.adminDashboardSubtitle')) ?></p>
        </div>
    </div>

    <section class="admin-dashboard-stats">
        <article>
            <span><?= esc(lang('App.adminDashboardActiveEvents')) ?></span>
            <strong><?= esc((string) ($stats['active_events'] ?? 0)) ?></strong>
        </article>
        <article>
            <span><?= esc(lang('App.adminDashboardIssuedTickets')) ?></span>
            <strong><?= esc((string) ($stats['issued_tickets'] ?? 0)) ?></strong>
        </article>
        <article>
            <span><?= esc(lang('App.adminDashboardCheckedInTickets')) ?></span>
            <strong><?= esc((string) ($stats['checked_in_tickets'] ?? 0)) ?></strong>
        </article>
        <article>
            <span><?= esc(lang('App.adminDashboardDonationTotal')) ?></span>
            <strong>EUR <?= esc(number_format((float) ($stats['donation_total'] ?? 0), 2)) ?></strong>
        </article>
    </section>

    <section class="admin-dashboard-grid">
        <div class="card admin-dashboard-panel">
            <h2><?= esc(lang('App.adminDashboardUpcoming')) ?></h2>
            <?php if (empty($upcomingEvents)): ?>
                <p class="meta"><?= esc(lang('App.adminDashboardEmpty')) ?></p>
            <?php else: ?>
                <div class="admin-dashboard-list">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <a href="<?= base_url('events/' . $event['slug']) ?>">
                            <strong><?= esc($event['title']) ?></strong>
                            <span><?= esc(! empty($event['start_date']) ? date('d/m/Y H:i', strtotime((string) $event['start_date'])) : '-') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card admin-dashboard-panel">
            <h2><?= esc(lang('App.adminDashboardAttention')) ?></h2>
            <?php if (empty($attentionEvents)): ?>
                <p class="meta"><?= esc(lang('App.adminDashboardNoAttention')) ?></p>
            <?php else: ?>
                <div class="admin-dashboard-list">
                    <?php foreach ($attentionEvents as $event): ?>
                        <?php
                        $issued = (int) ($event['issued_tickets'] ?? 0);
                        $capacity = (int) ($event['capacity'] ?? 0);
                        $label = (int) ($event['bookings_enabled'] ?? 1) === 0
                            ? lang('App.adminDashboardBookingsClosed')
                            : lang('App.adminDashboardSoldOut');
                        ?>
                        <a href="<?= base_url('events/' . $event['slug']) ?>">
                            <strong><?= esc($event['title']) ?></strong>
                            <span><?= esc($label) ?> &middot; <?= esc((string) $issued) ?>/<?= esc((string) $capacity) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>
<?= $this->endSection() ?>
