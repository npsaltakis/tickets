<!doctype html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(lang('App.printQrTitle')) ?> — <?= esc($event['title']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111; }
        .header { padding: 16px 24px; border-bottom: 2px solid #111; margin-bottom: 16px; }
        .header h1 { font-size: 1.1rem; }
        .header p { font-size: 0.85rem; color: #555; margin-top: 4px; }
        .qr-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; padding: 0 24px 24px; }
        .qr-card { border: 1px solid #ddd; border-radius: 8px; padding: 12px; text-align: center; break-inside: avoid; }
        .qr-card img { width: 140px; height: 140px; display: block; margin: 0 auto 8px; }
        .qr-code { font-size: 0.75rem; font-family: monospace; font-weight: 700; letter-spacing: 0.08em; color: #333; }
        .no-print { margin: 16px 24px; }
        .print-btn { padding: 10px 20px; background: #0f172a; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95rem; }
        @media print {
            .no-print { display: none; }
            .qr-grid { grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 0 12px; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()"><?= esc(lang('App.printQrPrint')) ?></button>
    </div>
    <div class="header">
        <h1><?= esc($event['title']) ?></h1>
        <p><?= esc(lang('App.printQrTitle')) ?> — <?= count($tickets) ?> <?= esc(lang('App.adminEventsCapacity')) ?></p>
    </div>
    <div class="qr-grid">
        <?php foreach ($tickets as $ticket): ?>
            <div class="qr-card">
                <img src="<?= esc($qrBase . urlencode($ticket['ticket_code'])) ?>" alt="<?= esc($ticket['ticket_code']) ?>">
                <div class="qr-code"><?= esc($ticket['ticket_code']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
