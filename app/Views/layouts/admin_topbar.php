<?php
$session = session();
$userName = trim((string) ($session->get('user_name') ?? ''));
$userEmail = (string) ($session->get('user_email') ?? '');
$displayName = $userName !== '' ? $userName : $userEmail;
$pageTitle = (string) ($pageTitle ?? lang('App.siteTitle'));
?>
<header class="admin-topbar">
    <div class="admin-topbar-left">
        <button type="button" class="admin-menu-btn" id="admin-menu-btn" aria-controls="admin-sidebar" aria-expanded="false" aria-label="<?= esc(lang('App.adminMenu'), 'attr') ?>">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
        </button>
        <h1 class="admin-topbar-title"><?= esc($pageTitle) ?></h1>
    </div>
    <div class="admin-topbar-right">
        <span class="admin-topbar-user"><?= esc($displayName) ?></span>
        <a class="admin-topbar-logout" href="<?= base_url('logout') ?>"><?= esc(lang('App.navLogout')) ?></a>
    </div>
</header>
