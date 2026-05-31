<?php
$path = trim(service('request')->getUri()->getPath(), '/');
$isActive = static function (string $target) use ($path): bool {
    $target = trim($target, '/');
    return $path === $target || ($target !== '' && str_starts_with($path, $target . '/'));
};
$linkClass = static function (bool $active): string {
    return 'admin-nav-link' . ($active ? ' is-active' : '');
};
$session = session();
$userName = trim((string) ($session->get('user_name') ?? ''));
$userEmail = (string) ($session->get('user_email') ?? '');
$displayName = $userName !== '' ? $userName : $userEmail;
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-sidebar-header">
        <a href="<?= base_url('/') ?>" class="admin-sidebar-brand">
            <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
            </svg>
            <span class="admin-sidebar-label"><?= esc(lang('App.siteTitle')) ?></span>
        </a>
        <button class="admin-sidebar-toggle" id="admin-sidebar-toggle" title="Toggle sidebar" aria-label="Toggle sidebar">
            <svg class="admin-sidebar-arrow" id="admin-sidebar-arrow" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
            </svg>
        </button>
    </div>

    <nav class="admin-sidebar-nav" aria-label="Admin navigation">

        <a href="<?= base_url('admin/dashboard') ?>" class="<?= $linkClass($isActive('admin/dashboard')) ?>" title="<?= esc(lang('App.navAdminDashboard')) ?>">
            <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            <span class="admin-sidebar-label"><?= esc(lang('App.navAdminDashboard')) ?></span>
        </a>

        <div class="admin-nav-sep"><span class="admin-sidebar-label"><?= esc(lang('App.adminNavSectionEvents')) ?></span></div>

        <a href="<?= base_url('admin/events') ?>" class="<?= $linkClass($isActive('admin/events')) ?>" title="Events">
            <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
            </svg>
            <span class="admin-sidebar-label">Events</span>
        </a>

        <div class="admin-nav-sep"><span class="admin-sidebar-label"><?= esc(lang('App.adminNavSectionManagement')) ?></span></div>

        <a href="<?= base_url('check-in') ?>" class="<?= $linkClass($isActive('check-in')) ?>" title="<?= esc(lang('App.navCheckIn')) ?>">
            <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <span class="admin-sidebar-label"><?= esc(lang('App.navCheckIn')) ?></span>
        </a>

        <a href="<?= base_url('report') ?>" class="<?= $linkClass($isActive('report')) ?>" title="<?= esc(lang('App.navReport')) ?>">
            <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
            </svg>
            <span class="admin-sidebar-label"><?= esc(lang('App.navReport')) ?></span>
        </a>

        <a href="<?= base_url('users') ?>" class="<?= $linkClass($isActive('users')) ?>" title="<?= esc(lang('App.navUsers')) ?>">
            <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
            </svg>
            <span class="admin-sidebar-label"><?= esc(lang('App.navUsers')) ?></span>
        </a>

        <a href="<?= base_url('admin-logs') ?>" class="<?= $linkClass($isActive('admin-logs')) ?>" title="<?= esc(lang('App.navAdminLogs')) ?>">
            <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/>
            </svg>
            <span class="admin-sidebar-label"><?= esc(lang('App.navAdminLogs')) ?></span>
        </a>

        <div class="admin-nav-sep"></div>

        <a href="<?= base_url('/') ?>" class="admin-nav-link admin-nav-link--muted" title="<?= esc(lang('App.adminNavViewSite')) ?>" target="_blank" rel="noopener noreferrer">
            <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
            </svg>
            <span class="admin-sidebar-label"><?= esc(lang('App.adminNavViewSite')) ?></span>
        </a>

    </nav>

    <div class="admin-sidebar-footer">
        <div class="admin-sidebar-user">
            <svg class="admin-nav-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
            </svg>
            <span class="admin-sidebar-label admin-sidebar-username"><?= esc($displayName) ?></span>
        </div>
        <a href="<?= base_url('logout') ?>" class="admin-sidebar-logout admin-sidebar-label">
            <?= esc(lang('App.navLogout')) ?>
        </a>
    </div>
</aside>
