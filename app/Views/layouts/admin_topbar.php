<?php
$session = session();
$userName = trim((string) ($session->get('user_name') ?? ''));
$userEmail = (string) ($session->get('user_email') ?? '');
$displayName = $userName !== '' ? $userName : $userEmail;
$pageTitle = (string) ($pageTitle ?? lang('App.siteTitle'));
?>
<header class="admin-topbar">
    <div class="admin-topbar-left">
        <h1 class="admin-topbar-title"><?= esc($pageTitle) ?></h1>
    </div>
    <div class="admin-topbar-right">
        <span class="admin-topbar-user"><?= esc($displayName) ?></span>
        <a class="admin-topbar-logout" href="<?= base_url('logout') ?>"><?= esc(lang('App.navLogout')) ?></a>
    </div>
</header>
