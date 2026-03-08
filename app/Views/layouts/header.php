<?php helper('url'); ?>
<?php
$currentLocale = service('request')->getLocale();
$selectedLanguage = $currentLocale === 'en' ? 'en' : 'el';

$session = session();
$isLoggedIn = $session->get('is_logged_in') === true;
$userName = trim((string) ($session->get('user_name') ?? ''));
$userEmail = (string) ($session->get('user_email') ?? '');
$avatarTitle = $userName !== '' ? $userName : ($userEmail !== '' ? $userEmail : 'User');
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
                    <?php if (!$isLoggedIn): ?>
                        <li><a class="menu-link" href="<?= base_url('login') ?>"><?= esc(lang('App.loginButton')) ?></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>

        <div class="top-nav-right">
            <div class="lang-switcher" aria-label="Language switcher">
                <a class="lang-link <?= $selectedLanguage === 'el' ? 'is-active' : '' ?>" href="<?= esc(current_url() . '?lang=el') ?>"><?= esc(lang('App.langEl')) ?></a>
                <span class="lang-divider">|</span>
                <a class="lang-link <?= $selectedLanguage === 'en' ? 'is-active' : '' ?>" href="<?= esc(current_url() . '?lang=en') ?>"><?= esc(lang('App.langEn')) ?></a>
            </div>

            <?php if ($isLoggedIn): ?>
                <details class="user-menu">
                    <summary class="user-avatar" title="<?= esc($avatarTitle) ?>" aria-label="User menu">
                        <img src="<?= base_url('assets/images/avatar-default.svg') ?>" alt="User avatar" class="user-avatar-icon">
                    </summary>
                    <div class="user-dropdown">
                        <a href="<?= base_url('my-events') ?>" class="user-dropdown-link"><?= esc(lang('App.navMyEvents')) ?></a>
                        <a href="<?= base_url('logout') ?>" class="user-dropdown-link user-dropdown-logout"><?= esc(lang('App.navLogout')) ?></a>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </div>
</header>

