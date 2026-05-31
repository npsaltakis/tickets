<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper admin-tickets-page">

    <?php if (session()->getFlashdata('ticket_info')): ?>
        <p class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('ticket_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('ticket_error')): ?>
        <p class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('ticket_error')) ?></p>
    <?php endif; ?>

    <div class="events-header" style="margin-bottom:20px">
        <div>
            <a class="back-link" href="<?= base_url('admin/events') ?>">&larr; <?= esc(lang('App.adminEventsPageTitle')) ?></a>
            <p class="meta" style="margin:4px 0 0">
                <?= esc(lang('App.adminTicketsEventLabel')) ?>:
                <a href="<?= base_url('events/' . $event['slug']) ?>" style="color:var(--accent)"><?= esc($event['title']) ?></a>
                &nbsp;<span class="status <?= esc(strtolower($event['status'] ?? 'inactive')) ?>"><?= esc(lang('App.eventStatus' . ucfirst((string)($event['status'] ?? 'inactive')))) ?></span>
            </p>
        </div>
        <div>
            <a href="<?= base_url('admin/events/' . $event['slug'] . '/tickets/export') ?>" class="admin-event-btn admin-event-btn--secondary"><?= esc(lang('App.adminEventsExport')) ?> CSV</a>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

        <div class="card" style="overflow:auto">
            <?php if (empty($tickets)): ?>
                <div class="empty"><?= esc(lang('App.adminTicketsEmpty')) ?></div>
            <?php else: ?>
                <table class="admin-table admin-tickets-table">
                    <thead>
                        <tr>
                            <th><?= esc(lang('App.adminTicketsCode')) ?></th>
                            <th><?= esc(lang('App.adminTicketsUser')) ?></th>
                            <th><?= esc(lang('App.adminTicketsStatus')) ?></th>
                            <th><?= esc(lang('App.adminTicketsPayment')) ?></th>
                            <th><?= esc(lang('App.adminTicketsCheckedIn')) ?></th>
                            <th><?= esc(lang('App.adminTicketsBooked')) ?></th>
                            <th><?= esc(lang('App.adminEventsActions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <?php
                            $name = trim(((string)($ticket['first_name'] ?? '')) . ' ' . ((string)($ticket['last_name'] ?? '')));
                            $isValid = (string)($ticket['status'] ?? '') === 'valid';
                            $checkedIn = !empty($ticket['checked_in_at']);
                            ?>
                            <tr class="<?= $isValid ? '' : 'admin-ticket-row--cancelled' ?>">
                                <td>
                                    <code class="admin-ticket-code"><?= esc($ticket['ticket_code']) ?></code>
                                </td>
                                <td>
                                    <div><?= esc($name ?: '—') ?></div>
                                    <div class="meta" style="font-size:0.8rem"><?= esc($ticket['email'] ?? '—') ?></div>
                                </td>
                                <td><span class="status <?= $isValid ? 'active' : 'cancelled' ?>"><?= $isValid ? esc(lang('App.eventStatusActive')) : esc(lang('App.eventStatusCancelled')) ?></span></td>
                                <td><?= esc(lang('App.paymentStatus' . ucfirst((string)($ticket['payment_status'] ?? 'free')))) ?></td>
                                <td>
                                    <?php if ($checkedIn): ?>
                                        <span style="color:#4ade80;font-size:0.85rem">✓ <?= esc(date('d/m H:i', strtotime((string)$ticket['checked_in_at']))) ?></span>
                                    <?php else: ?>
                                        <span class="meta">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="meta" style="font-size:0.82rem;white-space:nowrap"><?= esc(!empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime((string)$ticket['created_at'])) : '—') ?></td>
                                <td>
                                    <div class="admin-actions">
                                        <a class="admin-action-link" href="<?= base_url('admin/tickets/qr/' . urlencode($ticket['ticket_code'])) ?>" target="_blank" title="QR">QR</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="margin:0 0 16px;font-size:1rem;color:#f8fafc"><?= esc(lang('App.adminTicketsCreateTitle')) ?></h2>
            <form method="post" action="<?= base_url('admin/events/' . $event['slug'] . '/tickets') ?>" class="auth-form">
                <?= csrf_field() ?>
                <label class="auth-label" for="ticket_user"><?= esc(lang('App.adminTicketsUserLabel')) ?></label>
                <select id="ticket_user" name="user_id" class="auth-input" required>
                    <option value=""><?= esc(lang('App.adminTicketsSelectUser')) ?></option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= esc((string)$user['id']) ?>">
                            <?= esc(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?> (<?= esc($user['email'] ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="auth-label" for="ticket_seats"><?= esc(lang('App.seats')) ?></label>
                <input id="ticket_seats" name="seats" type="number" min="1" max="<?= esc((string)max((int)($event['capacity'] ?? 1), 1)) ?>" value="1" class="auth-input seats-input" style="width:100%">
                <button type="submit" class="book-btn auth-submit"><?= esc(lang('App.adminTicketsCreate')) ?></button>
            </form>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
