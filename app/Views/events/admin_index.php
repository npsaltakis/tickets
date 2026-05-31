<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper admin-events-page">

    <?php if (session()->getFlashdata('event_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('event_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('event_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('event_error')) ?></p>
    <?php endif; ?>

    <div class="events-header" style="margin-bottom:20px">
        <div>
            <p class="subtitle" style="margin:0"><?= esc(lang('App.adminEventsSubtitle')) ?></p>
        </div>
        <div class="admin-home-actions">
            <a href="<?= base_url('events/create') ?>" class="admin-event-btn"><?= esc(lang('App.adminNewEventButton')) ?></a>
            <a href="<?= base_url('events/deleted') ?>" class="admin-event-btn admin-event-btn--secondary"><?= esc(lang('App.deletedEventsButton')) ?></a>
        </div>
    </div>

    <div class="card" style="overflow:auto">
        <?php if (empty($events)): ?>
            <div class="empty"><?= esc(lang('App.eventsEmpty')) ?></div>
        <?php else: ?>
            <table class="admin-table admin-events-table">
                <thead>
                    <tr>
                        <th><?= esc(lang('App.eventTitleLabel')) ?></th>
                        <th><?= esc(lang('App.eventStatusLabel')) ?></th>
                        <th><?= esc(lang('App.startDate')) ?></th>
                        <th><?= esc(lang('App.endDate')) ?></th>
                        <th><?= esc(lang('App.type')) ?></th>
                        <th><?= esc(lang('App.adminEventsCapacity')) ?></th>
                        <th><?= esc(lang('App.adminEventsActions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <?php
                        $status    = strtolower((string) ($event['status'] ?? 'inactive'));
                        $statusLabel = lang('App.eventStatus' . ucfirst($status));
                        $capacity  = (int) ($event['capacity'] ?? 0);
                        $issued    = (int) ($issuedMap[(int) ($event['id'] ?? 0)] ?? 0);
                        $startDate = ! empty($event['start_date']) ? date('d/m/Y H:i', strtotime((string) $event['start_date'])) : '—';
                        $endDate   = ! empty($event['end_date'])   ? date('d/m/Y H:i', strtotime((string) $event['end_date']))   : '—';
                        $type      = ($event['event_type'] ?? 'free') === 'donation'
                            ? lang('App.eventCreateDonationType') . ' €' . number_format((float) ($event['min_donation'] ?? 0), 2)
                            : lang('App.freeEvent');
                        ?>
                        <tr>
                            <td>
                                <a class="admin-events-title-link" href="<?= esc(base_url('events/' . $event['slug'])) ?>">
                                    <?= esc($event['title']) ?>
                                </a>
                            </td>
                            <td><span class="status <?= esc($status) ?>"><?= esc($statusLabel) ?></span></td>
                            <td class="admin-events-date"><?= esc($startDate) ?></td>
                            <td class="admin-events-date"><?= esc($endDate) ?></td>
                            <td><span class="table-pill"><?= esc($type) ?></span></td>
                            <td class="admin-events-capacity">
                                <?= esc((string) $issued) ?> / <?= esc((string) $capacity) ?>
                            </td>
                            <td>
                                <div class="admin-actions">
                                    <a class="admin-action-link" href="<?= esc(base_url('events/' . $event['slug'] . '/edit')) ?>"><?= esc(lang('App.eventEditButton')) ?></a>
                                    <form method="post" action="<?= esc(base_url('events/' . $event['slug'] . '/duplicate')) ?>" style="margin:0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="admin-action-btn"><?= esc(lang('App.eventDuplicateButton')) ?></button>
                                    </form>
                                    <form method="post" action="<?= esc(base_url('events/' . $event['slug'] . '/delete')) ?>" style="margin:0" onsubmit="return confirm('<?= esc(lang('App.eventDeleteConfirm'), 'attr') ?>')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="admin-action-btn admin-action-btn--danger"><?= esc(lang('App.eventDeleteButton')) ?></button>
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
<?= $this->endSection() ?>
