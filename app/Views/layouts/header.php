<?php helper('url'); ?>
<?php
$currentLocale = service('request')->getLocale();
$selectedLanguage = $currentLocale === 'en' ? 'en' : 'el';
?>
<!doctype html>
<html lang="<?= esc($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? lang('App.siteTitle')) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/styles.css') ?>">
</head>
<body>
<header class="top-nav">
    <div class="top-nav-inner">
        <div class="top-nav-left">
            <a class="brand" href="<?= base_url('/') ?>"><?= esc(lang('App.siteTitle')) ?></a>
            <nav aria-label="Main navigation">
                <ul class="menu">
                    <li><a class="menu-link is-active" href="<?= base_url('/') ?>"><?= esc(lang('App.navHome')) ?></a></li>
                </ul>
            </nav>
        </div>

        <div class="lang-switcher" aria-label="Language switcher">
            <a class="lang-link <?= $selectedLanguage === 'el' ? 'is-active' : '' ?>" href="<?= esc(current_url() . '?lang=el') ?>"><?= esc(lang('App.langEl')) ?></a>
            <span class="lang-divider">|</span>
            <a class="lang-link <?= $selectedLanguage === 'en' ? 'is-active' : '' ?>" href="<?= esc(current_url() . '?lang=en') ?>"><?= esc(lang('App.langEn')) ?></a>
        </div>
    </div>
</header>
