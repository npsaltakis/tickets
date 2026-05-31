(() => {
    const sidebar = document.getElementById('admin-sidebar');
    const toggle  = document.getElementById('admin-sidebar-toggle');
    if (!sidebar || !toggle) return;

    const STORAGE_KEY = 'admin_sidebar_collapsed';
    const isMobile = () => window.innerWidth <= 768;

    const applyState = (collapsed) => {
        if (isMobile()) {
            sidebar.classList.toggle('is-mobile-open', !collapsed);
            sidebar.classList.remove('is-collapsed');
        } else {
            sidebar.classList.toggle('is-collapsed', collapsed);
            sidebar.classList.remove('is-mobile-open');
        }
    };

    const savedCollapsed = localStorage.getItem(STORAGE_KEY) === '1';
    applyState(savedCollapsed);

    toggle.addEventListener('click', () => {
        const collapsed = isMobile()
            ? sidebar.classList.contains('is-mobile-open')
            : !sidebar.classList.contains('is-collapsed');

        applyState(collapsed);

        if (!isMobile()) {
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        }
    });

    window.addEventListener('resize', () => {
        const collapsed = localStorage.getItem(STORAGE_KEY) === '1';
        applyState(collapsed);
    });
})();
