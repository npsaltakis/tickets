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
?>
<?php if ($siteFavicon !== ''): ?>
    <link rel="icon" href="<?= esc($siteFavicon, 'attr') ?>">
<?php endif; ?>
    <?php /* The CSP sends a style nonce, which makes browsers ignore 'unsafe-inline' for <style> elements. */ ?>
    <style <?= csp_style_nonce() ?>>:root { --accent: <?= esc($siteAccent) ?>; --accent-rgb: <?= esc($siteAccentRgb) ?>; }</style>
