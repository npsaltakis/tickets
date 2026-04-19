<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="wrapper report-page admin-logs-page">
    <div class="events-header report-header">
        <div>
            <span class="eyebrow"><?= esc(lang('App.adminLogsEyebrow')) ?></span>
            <h1><?= esc(lang('App.adminLogsTitle')) ?></h1>
            <p><?= esc(lang('App.adminLogsSubtitle')) ?></p>
        </div>
    </div>

    <div class="card report-card admin-logs-card">
        <?php if (! $tableReady): ?>
            <p class="auth-info"><?= esc(lang('App.adminLogsMigrationRequired')) ?></p>
        <?php else: ?>
            <?php
            $filters = is_array($filters ?? null) ? $filters : [];
            $stats = is_array($stats ?? null) ? $stats : [];
            $targetFilter = (string) ($filters['target'] ?? '');
            ?>

            <div class="admin-log-stats">
                <article class="admin-log-stat">
                    <span><?= esc(lang('App.adminLogsStatTotal')) ?></span>
                    <strong><?= esc((string) ($stats['total'] ?? 0)) ?></strong>
                </article>
                <article class="admin-log-stat">
                    <span><?= esc(lang('App.adminLogsStatToday')) ?></span>
                    <strong><?= esc((string) ($stats['today'] ?? 0)) ?></strong>
                </article>
                <article class="admin-log-stat">
                    <span><?= esc(lang('App.adminLogsStatAdmins')) ?></span>
                    <strong><?= esc((string) ($stats['admins'] ?? 0)) ?></strong>
                </article>
                <article class="admin-log-stat">
                    <span><?= esc(lang('App.adminLogsStatLatest')) ?></span>
                    <strong><?= esc((string) ($stats['latest'] ?? '-')) ?></strong>
                </article>
            </div>

            <form method="get" action="<?= base_url('admin-logs') ?>" class="admin-log-filters">
                <div class="admin-log-filter-field">
                    <label class="auth-label" for="admin-log-q"><?= esc(lang('App.adminLogsSearch')) ?></label>
                    <input id="admin-log-q" name="q" class="auth-input" type="search" value="<?= esc((string) ($filters['q'] ?? '')) ?>" placeholder="<?= esc(lang('App.adminLogsSearchPlaceholder')) ?>">
                </div>

                <div class="admin-log-filter-field">
                    <label class="auth-label" for="admin-log-action"><?= esc(lang('App.adminLogsAction')) ?></label>
                    <select id="admin-log-action" name="action" class="auth-input">
                        <option value=""><?= esc(lang('App.adminLogsAllActions')) ?></option>
                        <?php foreach ((array) ($actions ?? []) as $action): ?>
                            <option value="<?= esc((string) $action) ?>" <?= (string) ($filters['action'] ?? '') === (string) $action ? 'selected' : '' ?>>
                                <?= esc(ucwords(str_replace('_', ' ', (string) $action))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-log-filter-field">
                    <label class="auth-label" for="admin-log-target"><?= esc(lang('App.adminLogsTarget')) ?></label>
                    <select id="admin-log-target" name="target" class="auth-input">
                        <option value=""><?= esc(lang('App.adminLogsAllTargets')) ?></option>
                        <?php foreach (['event', 'ticket', 'user'] as $target): ?>
                            <option value="<?= esc($target) ?>" <?= $targetFilter === $target ? 'selected' : '' ?>><?= esc(ucfirst($target)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-log-filter-actions">
                    <button type="submit" class="book-btn"><?= esc(lang('App.adminLogsApplyFilters')) ?></button>
                    <a class="book-btn admin-log-clear" href="<?= base_url('admin-logs') ?>"><?= esc(lang('App.adminLogsClearFilters')) ?></a>
                </div>
            </form>

            <?php if (empty($logs)): ?>
                <p><?= esc(lang('App.adminLogsEmpty')) ?></p>
            <?php else: ?>
                <div class="admin-log-list">
                    <?php foreach ($logs as $log): ?>
                        <article class="admin-log-item">
                            <div class="admin-log-item-main">
                                <span class="admin-log-action-badge <?= esc((string) ($log['action_class'] ?? 'neutral')) ?>">
                                    <?= esc((string) ($log['action_label'] ?? $log['action'] ?? '-')) ?>
                                </span>
                                <div>
                                    <h2><?= esc((string) ($log['admin_email'] ?? '-')) ?></h2>
                                    <p>
                                        <?= esc((string) ($log['created_at_label'] ?? '-')) ?>
                                        <span aria-hidden="true">•</span>
                                        <?= esc(lang('App.adminLogsTarget')) ?>: <?= esc((string) ($log['target_type'] ?? '-')) ?>
                                        <span aria-hidden="true">•</span>
                                        IP: <?= esc((string) ($log['ip_address'] ?? '-')) ?>
                                    </p>
                                </div>
                            </div>

                            <?php if (! empty($log['context_items'])): ?>
                                <div class="admin-log-details">
                                    <?php foreach ((array) $log['context_items'] as $item): ?>
                                        <span class="admin-log-detail-chip">
                                            <strong><?= esc((string) ($item['label'] ?? '-')) ?></strong>
                                            <?= esc((string) ($item['value'] ?? '-')) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?= $this->endSection() ?>
