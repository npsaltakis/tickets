<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="wrapper analytics-page">

    <div class="analytics-grid">

        <div class="card analytics-card">
            <h2 class="analytics-title"><?= esc(lang('App.analyticsBookingsTitle')) ?></h2>
            <canvas id="chart-bookings" height="80"></canvas>
        </div>

        <div class="card analytics-card">
            <h2 class="analytics-title"><?= esc(lang('App.analyticsRevenueTitle')) ?></h2>
            <?php if (empty($revenueRows)): ?>
                <p class="meta"><?= esc(lang('App.analyticsNoRevenue')) ?></p>
            <?php else: ?>
                <canvas id="chart-revenue" height="80"></canvas>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const chartDefaults = {
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { color: 'rgba(148,163,184,0.1)' } },
        y: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { color: 'rgba(148,163,184,0.1)' }, beginAtZero: true }
    }
};

new Chart(document.getElementById('chart-bookings'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($days) ?>,
        datasets: [{ data: <?= json_encode($dayCounts) ?>, backgroundColor: 'rgba(20,184,166,0.6)', borderColor: '#14b8a6', borderWidth: 1, borderRadius: 4 }]
    },
    options: { ...chartDefaults, responsive: true }
});

<?php if (!empty($revenueRows)): ?>
new Chart(document.getElementById('chart-revenue'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($revenueRows, 'title')) ?>,
        datasets: [{ data: <?= json_encode(array_map(fn($r) => (float)$r['total'], $revenueRows)) ?>, backgroundColor: 'rgba(245,158,11,0.6)', borderColor: '#f59e0b', borderWidth: 1, borderRadius: 4 }]
    },
    options: { ...chartDefaults, indexAxis: 'y', responsive: true }
});
<?php endif; ?>
</script>
<?= $this->endSection() ?>
