<!DOCTYPE html>
<html lang="<?= esc(service('request')->getLocale(), 'attr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?= esc(lang('App.receiptTitle')) ?> R-<?= (int) $payment['id'] ?></title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; color: #0f172a; background: #fff; margin: 0; padding: 32px; }
        .receipt { max-width: 720px; margin: 0 auto; }
        h1 { margin: 0 0 4px; font-size: 24px; }
        .muted { color: #64748b; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin: 24px 0; }
        td { padding: 10px 0; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        td:first-child { color: #64748b; width: 38%; }
        .total td { font-weight: 700; font-size: 18px; border-bottom: 0; }
        .print-btn { margin-bottom: 20px; padding: 10px 16px; border: 0; border-radius: 8px; background: #0f766e; color: #fff; cursor: pointer; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<div class="receipt">
    <button class="print-btn" data-print><?= esc(lang('App.printQrPrint')) ?></button>
    <h1><?= esc(lang('App.receiptTitle')) ?></h1>
    <p class="muted">
        R-<?= (int) $payment['id'] ?> · <?= esc(date('d/m/Y H:i', strtotime((string) $payment['created_at']))) ?><br>
        <?= esc($orgName) ?><?= $orgTaxId !== '' ? ' · ' . esc(lang('App.receiptTaxId')) . ': ' . esc($orgTaxId) : '' ?><?= $orgAddress !== '' ? '<br>' . esc($orgAddress) : '' ?>
    </p>

    <table>
        <tr><td><?= esc(lang('App.receiptPayer')) ?></td><td><?= esc($payerName) ?> (<?= esc($payerEmail) ?>)</td></tr>
        <tr><td><?= esc(lang('App.receiptEvent')) ?></td><td><?= esc((string) $ticket['event_title']) ?><?= ! empty($ticket['event_start_date']) ? '<br><span class="muted">' . esc(date('d/m/Y H:i', strtotime((string) $ticket['event_start_date']))) . '</span>' : '' ?></td></tr>
        <tr><td><?= esc(lang('App.reportTicketCode')) ?></td><td><?= esc((string) $ticket['ticket_code']) ?></td></tr>
        <tr><td><?= esc(lang('App.receiptTransaction')) ?></td><td><?= esc((string) $payment['paypal_transaction_id']) ?></td></tr>
        <tr class="total"><td><?= esc(lang('App.receiptAmount')) ?></td><td><?= esc((string) $payment['currency']) ?> <?= esc(number_format((float) $payment['amount'], 2)) ?></td></tr>
    </table>

    <p class="muted"><?= esc(lang('App.receiptDisclaimer')) ?></p>
</div>
<script src="<?= base_url('assets/js/ui-actions.js') ?>"></script>
</body>
</html>