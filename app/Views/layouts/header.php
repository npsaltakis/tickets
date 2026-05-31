<?php helper('url'); ?>
<?php
$assetVersion = static function (string $relativePath): string {
    $fullPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
$currentLocale = service('request')->getLocale();
$selectedLanguage = $currentLocale === 'en' ? 'en' : 'el';
$currentPath = trim((string) service('request')->getUri()->getPath(), '/');
$isUsersSection = $currentPath === 'users' || str_starts_with($currentPath, 'users/');
$isAdminLogsSection = $currentPath === 'admin-logs';

$session = session();
$isLoggedIn = $session->get('is_logged_in') === true;
$isAdmin = $isLoggedIn && (string) $session->get('user_role') === 'admin';
$userName = trim((string) ($session->get('user_name') ?? ''));
$userEmail = (string) ($session->get('user_email') ?? '');
$avatarTitle = $userName !== '' ? $userName : ($userEmail !== '' ? $userEmail : 'User');
$siteTitle = lang('App.siteTitle');
$seoTitle = (string) ($pageTitle ?? $siteTitle);
$seoDescription = trim((string) ($metaDescription ?? lang('App.eventsPageSubtitle')));
$seoDescription = $seoDescription !== '' ? mb_substr($seoDescription, 0, 160, 'UTF-8') : $siteTitle;
$canonicalUrl = (string) ($canonicalUrl ?? current_url());
$metaImage = trim((string) ($metaImage ?? ''));
$metaType = (string) ($metaType ?? 'website');
$privatePathPrefixes = [
    'admin',
    'admin-logs',
    'check-in',
    'events/create',
    'events/deleted',
    'login',
    'lost-password',
    'my-events',
    'register',
    'report',
    'reset-password',
    'users',
    'verify-email',
];
$isPrivateSeoPath = false;
foreach ($privatePathPrefixes as $privatePathPrefix) {
    if ($currentPath === $privatePathPrefix || str_starts_with($currentPath, $privatePathPrefix . '/')) {
        $isPrivateSeoPath = true;
        break;
    }
}
$metaRobots = (string) ($metaRobots ?? ($isPrivateSeoPath ? 'noindex, nofollow' : 'index, follow'));
$structuredData = array_values(array_filter((array) ($structuredData ?? [])));
?>
<!doctype html>
<html lang="<?= esc($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($seoTitle) ?></title>
    <meta name="description" content="<?= esc($seoDescription, 'attr') ?>">
    <meta name="robots" content="<?= esc($metaRobots, 'attr') ?>">
    <link rel="canonical" href="<?= esc($canonicalUrl, 'attr') ?>">
    <link rel="alternate" hreflang="el" href="<?= esc($canonicalUrl . '?lang=el', 'attr') ?>">
    <link rel="alternate" hreflang="en" href="<?= esc($canonicalUrl . '?lang=en', 'attr') ?>">
    <link rel="alternate" hreflang="x-default" href="<?= esc($canonicalUrl, 'attr') ?>">
    <meta property="og:site_name" content="<?= esc($siteTitle, 'attr') ?>">
    <meta property="og:title" content="<?= esc($seoTitle, 'attr') ?>">
    <meta property="og:description" content="<?= esc($seoDescription, 'attr') ?>">
    <meta property="og:type" content="<?= esc($metaType, 'attr') ?>">
    <meta property="og:url" content="<?= esc($canonicalUrl, 'attr') ?>">
    <?php if ($metaImage !== ''): ?>
        <meta property="og:image" content="<?= esc($metaImage, 'attr') ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="<?= $metaImage !== '' ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= esc($seoTitle, 'attr') ?>">
    <meta name="twitter:description" content="<?= esc($seoDescription, 'attr') ?>">
    <?php if ($metaImage !== ''): ?>
        <meta name="twitter:image" content="<?= esc($metaImage, 'attr') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/styles.css') ?>?v=<?= esc($assetVersion('assets/css/styles.css')) ?>">
    <?php foreach ($structuredData as $structuredDataItem): ?>
        <script type="application/ld+json"><?= json_encode($structuredDataItem, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endforeach; ?>
</head>
<body>
<header class="top-nav">
    <div class="top-nav-inner">
        <div class="top-nav-left">
            <a class="brand" href="<?= base_url('/') ?>"><?= esc(lang('App.siteTitle')) ?></a>
            <nav aria-label="Main navigation">
                <ul class="menu">
                    <li><a class="menu-link <?= $currentPath === '' ? 'is-active' : '' ?>" href="<?= base_url('/') ?>"><?= esc(lang('App.navHome')) ?></a></li>
                    <?php if ($isAdmin): ?>
                        <li><a class="menu-link <?= $currentPath === 'admin/dashboard' ? 'is-active' : '' ?>" href="<?= base_url('admin/dashboard') ?>"><?= esc(lang('App.navAdminDashboard')) ?></a></li>
                    <?php endif; ?>
                    <?php if (! $isLoggedIn): ?>
                        <li><a class="menu-link <?= $currentPath === 'login' ? 'is-active' : '' ?>" href="<?= base_url('login') ?>"><?= esc(lang('App.loginButton')) ?></a></li>
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
                        <a href="<?= base_url('profile') ?>" class="user-dropdown-link"><?= esc(lang('App.profilePageTitle')) ?></a>
                        <a href="<?= base_url('logout') ?>" class="user-dropdown-link user-dropdown-logout"><?= esc(lang('App.navLogout')) ?></a>
                    </div>
                </details>
            <?php endif; ?>
        </div>
    </div>
</header>
