<?php
/**
 * Site settings in <head>: favicon and the accent colour (as a CSS variable the stylesheet uses).
 */
$siteAccent  = \App\Libraries\SiteSettings::get('accent_color');
$siteFavicon = \App\Libraries\SiteSettings::assetUrl('favicon_path');
if (preg_match('/^#([0-9a-f]{6})$/i', $siteAccent, $hex) !== 1) {
    $siteAccent = \App\Libraries\SiteSettings::DEFAULTS['accent_color'];
    preg_match('/^#([0-9a-f]{6})$/i', $siteAccent, $hex);
}
$siteAccentRgb = implode(', ', array_map('hexdec', str_split($hex[1], 2)));
// Lets assets/js/pwa.js tell accounts apart without exposing the user id.
$pwaUserId = (int) session()->get('user_id');
$pwaUser   = session()->get('is_logged_in') === true && $pwaUserId > 0 ? substr(hash('sha256', 'pwa|' . $pwaUserId), 0, 16) : '';
?>
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="<?= esc($siteAccent, 'attr') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('pwa/icon-180.png') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="sw-url" content="<?= esc(base_url('sw.js'), 'attr') ?>">
    <meta name="pwa-user" content="<?= esc($pwaUser, 'attr') ?>">
    <script src="<?= base_url('assets/js/pwa.js') ?>?v=<?= @filemtime(FCPATH . 'assets/js/pwa.js') ?: time() ?>"></script>
<?php if ($siteFavicon !== ''): ?>
    <link rel="icon" href="<?= esc($siteFavicon, 'attr') ?>">
<?php endif; ?>
    <?php /* The CSP sends a style nonce, which makes browsers ignore 'unsafe-inline' for <style> elements. */ ?>
    <style <?= csp_style_nonce() ?>>:root { --accent: <?= esc($siteAccent) ?>; --accent-rgb: <?= esc($siteAccentRgb) ?>; }</style>
