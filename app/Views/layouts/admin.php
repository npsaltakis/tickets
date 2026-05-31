<?php
$assetVersion = static function (string $relativePath): string {
    $fullPath = rtrim(FCPATH, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($fullPath) ? (string) filemtime($fullPath) : (string) time();
};
$currentLocale = service('request')->getLocale();
$siteTitle = lang('App.siteTitle');
$seoTitle = (string) ($pageTitle ?? $siteTitle);
?>
<!doctype html>
<html lang="<?= esc($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($seoTitle) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-name" content="<?= csrf_token() ?>">
    <meta name="csrf-value" content="<?= csrf_hash() ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/styles.css') ?>?v=<?= esc($assetVersion('assets/css/styles.css')) ?>">
</head>
<body class="admin-body">
    <div class="admin-shell">
        <?= $this->include('layouts/admin_sidebar') ?>
        <div class="admin-main" id="admin-main">
            <?= $this->include('layouts/admin_topbar') ?>
            <main class="admin-content">
                <?= $this->renderSection('content') ?>
            </main>
        </div>
    </div>
    <script src="<?= base_url('assets/js/admin.js') ?>?v=<?= esc($assetVersion('assets/js/admin.js')) ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
