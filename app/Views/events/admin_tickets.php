<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper admin-tickets-page">

    <?php if (session()->getFlashdata('ticket_info')): ?>
        <p role="status" class="auth-info alert-inline"><?= esc((string) session()->getFlashdata('ticket_info')) ?></p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('ticket_error')): ?>
        <p role="alert" class="auth-error alert-inline"><?= esc((string) session()->getFlashdata('ticket_error')) ?></p>
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
            <a href="<?= base_url('admin/events/' . $event['slug'] . '/tickets/export') ?>" class="admin-event-btn admin-event-btn--secondary"><?= esc(lang('App.adminEventsExport')) ?></a>
            <a href="<?= base_url('admin/events/' . $event['slug'] . '/tickets/print-qr') ?>" class="admin-event-btn admin-event-btn--secondary" target="_blank"><?= esc(lang('App.printQrTitle')) ?></a>
            <a href="<?= base_url('admin/events/' . $event['slug'] . '/email-attendees') ?>" class="admin-event-btn"><?= esc(lang('App.emailAttendeesTitle')) ?></a>
        </div>
    </div>

    <section class="ticket-stats" aria-label="<?= esc(lang('App.ticketStatsTitle'), 'attr') ?>">
        <article><span><?= esc(lang('App.ticketStatsCapacity')) ?></span><strong><?= (int) $stats['capacity'] ?></strong></article>
        <article><span><?= esc(lang('App.ticketStatsBooked')) ?></span><strong><?= (int) $stats['booked'] ?></strong></article>
        <article class="<?= $stats['available'] === 0 ? 'is-full' : '' ?>"><span><?= esc(lang('App.ticketStatsAvailable')) ?></span><strong><?= (int) $stats['available'] ?></strong></article>
        <article><span><?= esc(lang('App.ticketStatsCheckedIn')) ?></span><strong><?= (int) $stats['checkedIn'] ?><small> / <?= (int) $stats['booked'] ?></small></strong></article>
        <?php if ($waitlistEnabled): ?>
            <article><span><?= esc(lang('App.ticketStatsWaitlist')) ?></span><strong><?= (int) $stats['waitlist'] ?></strong></article>
        <?php endif; ?>
        <?php if (($event['event_type'] ?? 'free') === 'donation'): ?>
            <article><span><?= esc(lang('App.ticketStatsDonations')) ?></span><strong><?= esc(money((float) $stats['donations'])) ?></strong></article>
        <?php endif; ?>
        <?php if ($stats['cancelled'] > 0): ?>
            <article><span><?= esc(lang('App.ticketStatsCancelled')) ?></span><strong><?= (int) $stats['cancelled'] ?></strong></article>
        <?php endif; ?>
    </section>

    <div class="ticket-fill">
        <div class="ticket-fill-label">
            <span><?= esc(lang('App.ticketStatsFill')) ?></span>
            <strong><?= (int) $stats['fillPct'] ?>%</strong>
        </div>
        <div class="ticket-fill-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $stats['fillPct'] ?>" aria-label="<?= esc(lang('App.ticketStatsFill'), 'attr') ?>">
            <span style="width:<?= (int) $stats['fillPct'] ?>%"></span>
        </div>
    </div>

    <div class="admin-split">

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
                                <td class="admin-tickets-code">
                                    <code class="admin-ticket-code"><?= esc($ticket['ticket_code']) ?></code>
                                </td>
                                <td class="admin-tickets-user">
                                    <div><?= esc($name ?: '—') ?></div>
                                    <div class="meta" style="font-size:0.8rem"><?= esc($ticket['email'] ?? '—') ?></div>
                                </td>
                                <td data-label="<?= esc(lang('App.adminTicketsStatus'), 'attr') ?>"><span class="status <?= $isValid ? 'active' : 'cancelled' ?>"><?= $isValid ? esc(lang('App.eventStatusActive')) : esc(lang('App.eventStatusCancelled')) ?></span></td>
                                <td data-label="<?= esc(lang('App.adminTicketsPayment'), 'attr') ?>"><?= esc(lang('App.paymentStatus' . ucfirst((string)($ticket['payment_status'] ?? 'free')))) ?></td>
                                <td data-label="<?= esc(lang('App.adminTicketsCheckedIn'), 'attr') ?>">
                                    <?php if ($checkedIn): ?>
                                        <span style="color:#4ade80;font-size:0.85rem">✓ <?= esc(date('d/m H:i', strtotime((string)$ticket['checked_in_at']))) ?></span>
                                    <?php else: ?>
                                        <span class="meta">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="meta" data-label="<?= esc(lang('App.adminTicketsBooked'), 'attr') ?>" style="font-size:0.82rem;white-space:nowrap"><?= esc(!empty($ticket['created_at']) ? date('d/m/Y H:i', strtotime((string)$ticket['created_at'])) : '—') ?></td>
                                <td class="admin-tickets-actions">
                                    <div class="admin-actions">
                                        <a class="admin-action-link admin-icon-btn" href="<?= base_url('admin/tickets/qr/' . urlencode($ticket['ticket_code'])) ?>" target="_blank" rel="noopener" title="<?= esc(lang('App.adminTicketsShowQr'), 'attr') ?>" aria-label="<?= esc(lang('App.adminTicketsShowQr') . ': ' . $ticket['ticket_code'], 'attr') ?>">
                                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <?php if (! $waitlistEnabled): ?>
            <?php elseif (empty($waitlist)): ?>
                <h2 style="margin:0 0 12px;font-size:1rem;color:#f8fafc"><?= esc(lang('App.waitlistAdminTitle')) ?> (0)</h2>
                <p class="meta"><?= esc(lang('App.waitlistAdminEmpty')) ?></p>
            <?php else: ?>
                <h2 style="margin:0 0 12px;font-size:1rem;color:#f8fafc"><?= esc(lang('App.waitlistAdminTitle')) ?> (<?= count($waitlist) ?>)</h2>
                <ol style="margin:0 0 20px;padding-left:18px">
                    <?php foreach ($waitlist as $w): ?>
                        <li class="meta" style="margin-bottom:6px">
                            <?= esc(trim((string) $w['first_name'] . ' ' . (string) $w['last_name'])) ?> · <?= esc((string) $w['email']) ?><br>
                            <span style="font-size:0.78rem"><?= esc(date('d/m H:i', strtotime((string) $w['created_at']))) ?><?= !empty($w['notified_at']) ? ' · ' . esc(lang('App.waitlistAdminNotified')) : '' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

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
