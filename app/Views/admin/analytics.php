<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="wrapper analytics-page">

    <div class="analytics-grid">

        <div class="card analytics-card analytics-card--full">
            <h2 class="analytics-title"><?= esc(lang('App.viewsTitle')) ?></h2>
            <p class="meta analytics-views-hint"><?= esc(lang('App.viewsHint')) ?></p>
            <?php if (array_sum($viewDaily['visitors']) === 0): ?>
                <p class="meta"><?= esc(lang('App.viewsEmpty')) ?></p>
            <?php else: ?>
                <canvas id="chart-views" height="70" data-chart="<?= esc(json_encode(['labels' => $viewDaily['labels'], 'values' => $viewDaily['visitors']]), 'attr') ?>" aria-label="<?= esc(lang('App.viewsTitle'), 'attr') ?>" role="img"></canvas>
            <?php endif; ?>
        </div>

        <?php if ($viewSources !== []): ?>
            <div class="card analytics-card">
                <h2 class="analytics-title"><?= esc(lang('App.viewsSourcesTitle')) ?></h2>
                <?php $sourceTotal = max(1, array_sum($viewSources)); ?>
                <div class="analytics-check-in-list">
                    <?php foreach ($viewSources as $source => $visitors): ?>
                        <?php $share = round($visitors / $sourceTotal * 100); ?>
                        <div class="analytics-ci-row">
                            <span class="analytics-ci-title"><?= esc(\App\Libraries\EventViews::label((string) $source)) ?></span>
                            <div class="analytics-ci-bar-wrap">
                                <div class="analytics-ci-bar analytics-source-bar" style="width:<?= (int) $share ?>%"></div>
                            </div>
                            <span class="analytics-ci-label"><?= (int) $visitors ?> (<?= (int) $share ?>%)</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="meta analytics-views-hint"><?= esc(lang('App.viewsUtmHint')) ?></p>
            </div>

            <div class="card analytics-card">
                <h2 class="analytics-title"><?= esc(lang('App.viewsEventsTitle')) ?></h2>
                <div class="analytics-table-wrap">
                    <table class="admin-table analytics-views-table">
                        <thead>
                            <tr>
                                <th><?= esc(lang('App.eventTitleLabel')) ?></th>
                                <th><?= esc(lang('App.viewsVisitors')) ?></th>
                                <th><?= esc(lang('App.viewsBookers')) ?></th>
                                <th><?= esc(lang('App.viewsConversion')) ?></th>
                                <th><?= esc(lang('App.viewsTopSource')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($viewStats as $eventId => $vs): ?>
                                <tr>
                                    <td><?= esc((string) ($viewTitles[$eventId] ?? '#' . $eventId)) ?></td>
                                    <td title="<?= esc(lang('App.viewsVisitorsViews', [$vs['visitors'], $vs['views']]), 'attr') ?>"><?= (int) $vs['visitors'] ?></td>
                                    <td><?= (int) $vs['bookers'] ?></td>
                                    <td><?= esc(number_format((float) $vs['conversion'], 1, ',', '')) ?>%</td>
                                    <td><?= esc(\App\Libraries\EventViews::label($vs['top_source'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

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
