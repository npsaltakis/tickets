<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="wrapper report-page">
    <div class="events-header report-header">
        <div>
            <span class="eyebrow"><?= esc(lang('App.adminLogsEyebrow')) ?></span>
            <h1><?= esc(lang('App.adminLogsTitle')) ?></h1>
            <p><?= esc(lang('App.adminLogsSubtitle')) ?></p>
        </div>
    </div>

    <div class="card report-card">
        <?php if (! $tableReady): ?>
            <p class="auth-info"><?= esc(lang('App.adminLogsMigrationRequired')) ?></p>
        <?php elseif (empty($logs)): ?>
            <p><?= esc(lang('App.adminLogsEmpty')) ?></p>
        <?php else: ?>
            <div class="report-table-wrap">
                <table class="display report-table">
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.adminLogsCreatedAt')) ?></th>
                            <th><?= esc(lang('App.adminLogsAdmin')) ?></th>
                            <th><?= esc(lang('App.adminLogsAction')) ?></th>
                            <th><?= esc(lang('App.adminLogsTarget')) ?></th>
                            <th><?= esc(lang('App.adminLogsIp')) ?></th>
                            <th><?= esc(lang('App.adminLogsContext')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= esc(! empty($log['created_at']) ? date('d/m/Y H:i:s', strtotime((string) $log['created_at'])) : '-') ?></td>
                                <td><?= esc((string) ($log['admin_email'] ?? '-')) ?></td>
                                <td><code><?= esc((string) ($log['action'] ?? '-')) ?></code></td>
                                <td><?= esc((string) ($log['target_type'] ?? '-')) ?></td>
                                <td><?= esc((string) ($log['ip_address'] ?? '-')) ?></td>
                                <td><pre class="admin-log-context"><?= esc((string) ($log['context_pretty'] ?? '{}')) ?></pre></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
