<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="wrapper">
    <form method="get" action="<?= base_url('admin/payments') ?>" class="events-filters">
        <select name="event_id" class="auth-input">
            <option value=""><?= esc(lang('App.paymentsAllEvents')) ?></option>
            <?php foreach ($events as $ev): ?>
                <option value="<?= (int) $ev['id'] ?>" <?= (int) $filters['event_id'] === (int) $ev['id'] ? 'selected' : '' ?>><?= esc($ev['title']) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="meta"><?= esc(lang('App.filterFrom')) ?> <input type="date" name="from" class="auth-input" value="<?= esc($filters['from'], 'attr') ?>"></label>
        <label class="meta"><?= esc(lang('App.filterTo')) ?> <input type="date" name="to" class="auth-input" value="<?= esc($filters['to'], 'attr') ?>"></label>
        <button type="submit" class="auth-link-btn"><?= esc(lang('App.filterApply')) ?></button>
        <a class="auth-link-btn" href="<?= base_url('admin/payments/export') . '?' . esc(http_build_query(array_filter($filters)), 'attr') ?>">CSV</a>
    </form>

    <div class="card" style="margin-bottom:16px;display:flex;gap:32px;flex-wrap:wrap">
        <div><div class="meta"><?= esc(lang('App.paymentsReceived')) ?></div><strong style="font-size:1.6rem">€<?= esc(number_format($totals['completed'], 2)) ?></strong></div>
        <div><div class="meta"><?= esc(lang('App.paymentsRefunded')) ?></div><strong style="font-size:1.6rem">€<?= esc(number_format($totals['refunded'], 2)) ?></strong></div>
    </div>

    <div class="card" style="overflow:auto">
        <?php if (empty($rows)): ?>
            <div class="empty"><?= esc(lang('App.paymentsEmpty')) ?></div>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th><?= esc(lang('App.reportBookedAt')) ?></th><th><?= esc(lang('App.reportEvent')) ?></th><th><?= esc(lang('App.reportCustomer')) ?></th><th><?= esc(lang('App.paymentsAmount')) ?></th><th><?= esc(lang('App.reportPaymentStatus')) ?></th><th>PayPal</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="meta" style="white-space:nowrap"><?= esc(date('d/m/Y H:i', strtotime((string) $r['created_at']))) ?></td>
                        <td><?= esc((string) $r['event_title']) ?></td>
                        <td><?= esc(trim((string) $r['first_name'] . ' ' . (string) $r['last_name'])) ?><div class="meta" style="font-size:0.8rem"><?= esc((string) $r['email']) ?></div></td>
                        <td><?= esc((string) $r['currency']) ?> <?= esc(number_format((float) $r['amount'], 2)) ?></td>
                        <td><?= esc((string) $r['payment_status']) ?></td>
                        <td class="meta"><code><?= esc((string) $r['paypal_transaction_id']) ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>