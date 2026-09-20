<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="wrapper analytics-page">

    <div class="analytics-grid">

        <div class="card analytics-card">
            <h2 class="analytics-title"><?= esc(lang('App.analyticsBookingsTitle')) ?></h2>
            <canvas id="chart-bookings" height="80" data-chart="<?= esc(json_encode(['labels' => $days, 'values' => $dayCounts]), 'attr') ?>"></canvas>
        </div>

        <div class="card analytics-card">
            <h2 class="analytics-title"><?= esc(lang('App.analyticsRevenueTitle')) ?></h2>
            <?php if (empty($revenueRows)): ?>
                <p class="meta"><?= esc(lang('App.analyticsNoRevenue')) ?></p>
            <?php else: ?>
                <canvas id="chart-revenue" height="80" data-chart="<?= esc(json_encode(['labels' => array_column($revenueRows, 'title'), 'values' => array_map(static fn ($r) => (float) $r['total'], $revenueRows)]), 'attr') ?>"></canvas>
            <?php endif; ?>
        </div>

        <div class="card analytics-card analytics-card--full">
            <h2 class="analytics-title"><?= esc(lang('App.analyticsCheckInTitle')) ?></h2>
            <?php if (empty($checkInRows)): ?>
                <p class="meta"><?= esc(lang('App.analyticsNoData')) ?></p>
            <?php else: ?>
                <div class="analytics-check-in-list">
                    <?php foreach ($checkInRows as $row): ?>
                        <?php
                        $issued    = (int) ($row['issued'] ?? 0);
                        $checkedIn = (int) ($row['checked_in'] ?? 0);
                        $rate      = $issued > 0 ? round($checkedIn / $issued * 100) : 0;
                        ?>
                        <div class="analytics-ci-row">
                            <span class="analytics-ci-title"><?= esc($row['title']) ?></span>
                            <div class="analytics-ci-bar-wrap">
                                <div class="analytics-ci-bar" style="width:<?= $rate ?>%"></div>
                            </div>
                            <span class="analytics-ci-label"><?= $checkedIn ?>/<?= $issued ?> (<?= $rate ?>%)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="<?= base_url('assets/js/analytics.js') ?>?v=<?= esc((string) (is_file(FCPATH . 'assets/js/analytics.js') ? filemtime(FCPATH . 'assets/js/analytics.js') : time())) ?>"></script>
<?= $this->endSection() ?>
