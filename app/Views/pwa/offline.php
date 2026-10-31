<?php
/**
 * Shown by the service worker when a page cannot be loaded. Cached once, so it must not show
 * anything about the signed-in user (no header/menu).
 */
$accent = \App\Libraries\SiteSettings::get('accent_color');
if (preg_match('/^#[0-9a-f]{6}$/i', $accent) !== 1) {
    $accent = \App\Libraries\SiteSettings::DEFAULTS['accent_color'];
}
?>
<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc(lang('App.pwaOfflineTitle')) ?> | <?= esc(\App\Libraries\SiteSettings::get('site_name')) ?></title>
    <style <?= csp_style_nonce() ?>>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; box-sizing: border-box;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #0f172a; }
        main { max-width: 420px; text-align: center; }
        h1 { font-size: 1.5rem; margin: 0 0 12px; }
        p { line-height: 1.5; color: #475569; margin: 0 0 24px; }
        a { display: inline-block; margin: 4px; padding: 10px 18px; border-radius: 10px; text-decoration: none; font-weight: 600;
            border: 1px solid <?= esc($accent) ?>; color: <?= esc($accent) ?>; }
        a.primary { background: <?= esc($accent) ?>; color: #fff; }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #f1f5f9; }
            p { color: #94a3b8; }
        }
    </style>
</head>
<body>
<main>
    <h1><?= esc(lang('App.pwaOfflineTitle')) ?></h1>
    <p><?= esc(lang('App.pwaOfflineText')) ?></p>
    <a class="primary" href="<?= base_url('my-events') ?>"><?= esc(lang('App.myEventsTitle')) ?></a>
    <a href="<?= base_url('/') ?>"><?= esc(lang('App.pwaOfflineRetry')) ?></a>
</main>
</body>
</html>
