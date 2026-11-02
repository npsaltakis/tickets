<?php
$assetVersion = static function (string $relativePath): string {
    $fullPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
?><?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper admin-events-page">

    <?php if (session()->getFlashdata('event_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('event_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('event_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('event_error')) ?></p>
    <?php endif; ?>

    <div class="events-header" style="margin-bottom:16px">
        <div>
            <p class="subtitle" style="margin:0"><?= esc(lang('App.adminEventsSubtitle')) ?></p>
        </div>
        <div class="admin-home-actions">
            <a href="<?= base_url('admin/events/export') ?>" class="admin-event-btn admin-event-btn--secondary"><?= esc(lang('App.adminEventsExport')) ?></a>
            <a href="<?= base_url('events/create') ?>" class="admin-event-btn"><?= esc(lang('App.adminNewEventButton')) ?></a>
            <a href="<?= base_url('events/deleted') ?>" class="admin-event-btn admin-event-btn--secondary"><?= esc(lang('App.deletedEventsButton')) ?></a>
        </div>
    </div>

    <div id="bulk-bar" class="bulk-bar">
        <span><?= esc(lang('App.adminBulkSelected')) ?> <strong id="bulk-count">0</strong></span>
        <select id="bulk-action" class="auth-input" style="width:auto;padding:6px 10px">
            <option value=""><?= esc(lang('App.adminBulkChoose')) ?></option>
            <option value="activate"><?= esc(lang('App.adminBulkActivate')) ?></option>
            <option value="deactivate"><?= esc(lang('App.adminBulkDeactivate')) ?></option>
            <option value="cancel"><?= esc(lang('App.adminBulkCancel')) ?></option>
            <option value="delete"><?= esc(lang('App.eventDeleteButton')) ?></option>
        </select>
        <button id="bulk-apply" class="book-btn"><?= esc(lang('App.adminBulkApply')) ?></button>
    </div>

    <div class="card" style="overflow:auto">
        <?php if (empty($events)): ?>
            <div class="empty"><?= esc(lang('App.eventsEmpty')) ?></div>
        <?php else: ?>
            <table class="admin-table admin-events-table">
                <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" id="bulk-select-all" class="bulk-checkbox"></th>
                        <th><?= esc(lang('App.eventTitleLabel')) ?></th>
                        <th><?= esc(lang('App.eventStatusLabel')) ?></th>
                        <th><?= esc(lang('App.startDate')) ?></th>
                        <th><?= esc(lang('App.endDate')) ?></th>
                        <th><?= esc(lang('App.type')) ?></th>
                        <th><?= esc(lang('App.adminEventsCapacity')) ?></th>
                        <th title="<?= esc(lang('App.viewsColumnHint'), 'attr') ?>"><?= esc(lang('App.viewsColumn')) ?></th>
                        <th><?= esc(lang('App.adminEventsActions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <?php
                        $status      = strtolower((string) ($event['status'] ?? 'inactive'));
                        $capacity    = (int) ($event['capacity'] ?? 0);
                        $issued      = (int) ($issuedMap[(int) ($event['id'] ?? 0)] ?? 0);
                        $startDate   = ! empty($event['start_date']) ? date('d/m/Y H:i', strtotime((string) $event['start_date'])) : '—';
                        $endDate     = ! empty($event['end_date'])   ? date('d/m/Y H:i', strtotime((string) $event['end_date']))   : '—';
                        $type        = ($event['event_type'] ?? 'free') === 'donation'
                            ? lang('App.eventCreateDonationType') . ' ' . money($event['min_donation'] ?? 0)
                            : lang('App.freeEvent');
                        ?>
                        <tr>
                            <td class="admin-events-select"><input type="checkbox" class="bulk-check bulk-checkbox" data-slug="<?= esc($event['slug']) ?>"></td>
                            <td class="admin-events-title">
                                <a class="admin-events-title-link" href="<?= esc(base_url('events/' . $event['slug'])) ?>">
                                    <?= esc($event['title']) ?>
                                </a>
                                <?php if (isset($creatorNames[(int) ($event['created_by'] ?? 0)])): ?>
                                    <span class="admin-events-creator"><?= esc(strtr(lang('App.eventCreatedBy'), ['{name}' => $creatorNames[(int) $event['created_by']]])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="<?= esc(lang('App.eventStatusLabel'), 'attr') ?>">
                                <select class="js-status-select admin-status-select status-<?= esc($status) ?>" data-slug="<?= esc($event['slug']) ?>" aria-label="<?= esc(lang('App.eventStatusLabel') . ': ' . $event['title'], 'attr') ?>">
                                    <option value="active"    <?= $status === 'active'    ? 'selected' : '' ?>><?= esc(lang('App.eventStatusActive')) ?></option>
                                    <option value="inactive"  <?= $status === 'inactive'  ? 'selected' : '' ?>><?= esc(lang('App.eventStatusInactive')) ?></option>
                                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>><?= esc(lang('App.eventStatusCancelled')) ?></option>
                                </select>
                            </td>
                            <td class="admin-events-date" data-label="<?= esc(lang('App.startDate'), 'attr') ?>"><?= esc($startDate) ?></td>
                            <td class="admin-events-date" data-label="<?= esc(lang('App.endDate'), 'attr') ?>"><?= esc($endDate) ?></td>
                            <td data-label="<?= esc(lang('App.type'), 'attr') ?>"><span class="table-pill"><?= esc($type) ?></span></td>
                            <td class="admin-events-capacity" data-label="<?= esc(lang('App.adminEventsCapacity'), 'attr') ?>"><?= esc((string) $issued) ?> / <?= esc((string) $capacity) ?></td>
                            <td class="admin-events-views" data-label="<?= esc(lang('App.viewsColumn'), 'attr') ?>">
                                <?php $vs = $viewStats[(int) ($event['id'] ?? 0)] ?? null; ?>
                                <?php if ($vs === null): ?>
                                    <span class="meta">—</span>
                                <?php else: ?>
                                    <span title="<?= esc(lang('App.viewsVisitorsViews', [$vs['visitors'], $vs['views']]), 'attr') ?>"><?= esc((string) $vs['visitors']) ?></span>
                                    <span class="meta admin-events-conversion" title="<?= esc(lang('App.viewsConversionHint', [$vs['bookers'], $vs['visitors']]), 'attr') ?>"><?= esc(number_format((float) $vs['conversion'], 1, ',', '')) ?>%</span>
                                <?php endif; ?>
                            </td>
                            <td class="admin-events-actions">
                                <div class="admin-actions">
                                    <a class="admin-action-link admin-icon-btn" href="<?= esc(base_url('admin/events/' . $event['slug'] . '/tickets')) ?>" title="<?= esc(lang('App.adminEventsTickets'), 'attr') ?>" aria-label="<?= esc(lang('App.adminEventsTickets') . ': ' . $event['title'], 'attr') ?>">
                                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                                    </a>
                                    <a class="admin-action-link admin-icon-btn" href="<?= esc(base_url('events/' . $event['slug'] . '/edit')) ?>" title="<?= esc(lang('App.eventEditButton'), 'attr') ?>" aria-label="<?= esc(lang('App.eventEditButton') . ': ' . $event['title'], 'attr') ?>">
                                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/></svg>
                                    </a>
                                    <form method="post" action="<?= esc(base_url('events/' . $event['slug'] . '/delete')) ?>" data-confirm="<?= esc(lang('App.eventDeleteConfirm'), 'attr') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="admin-action-btn admin-action-btn--danger admin-icon-btn" title="<?= esc(lang('App.eventDeleteButton'), 'attr') ?>" aria-label="<?= esc(lang('App.eventDeleteButton') . ': ' . $event['title'], 'attr') ?>">
                                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<script src="<?= base_url('assets/js/admin-events.js') ?>?v=<?= esc($assetVersion('assets/js/admin-events.js')) ?>"></script>
<?= $this->endSection() ?>
