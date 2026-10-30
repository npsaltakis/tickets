(() => {
    const sidebar = document.getElementById('admin-sidebar');
    const toggle  = document.getElementById('admin-sidebar-toggle');
    if (!sidebar || !toggle) return;

    const menuBtn     = document.getElementById('admin-menu-btn');
    const backdrop    = document.getElementById('admin-backdrop');
    const STORAGE_KEY = 'admin_sidebar_collapsed';
    const mobile      = window.matchMedia('(max-width: 768px)');

    const savedCollapsed = () => {
        try { return localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) { return false; }
    };

    // Phones: the sidebar is a drawer opened from the topbar button, closed by default.
    const setDrawer = (open) => {
        sidebar.classList.toggle('is-mobile-open', open);
        if (backdrop) backdrop.hidden = !open;
        if (menuBtn) menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) sidebar.querySelector('.admin-nav-link.is-active')?.scrollIntoView({ block: 'nearest' });
    };

    const applyLayout = () => {
        setDrawer(false);
        sidebar.classList.toggle('is-collapsed', !mobile.matches && savedCollapsed());
    };

    applyLayout();
    mobile.addEventListener('change', applyLayout);

    toggle.addEventListener('click', () => {
        if (mobile.matches) {
            setDrawer(false);
            menuBtn?.focus();
            return;
        }

        const collapsed = !sidebar.classList.contains('is-collapsed');
        sidebar.classList.toggle('is-collapsed', collapsed);
        try { localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0'); } catch (e) { /* not stored */ }
    });

    menuBtn?.addEventListener('click', () => setDrawer(!sidebar.classList.contains('is-mobile-open')));
    backdrop?.addEventListener('click', () => setDrawer(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-mobile-open')) {
            setDrawer(false);
            menuBtn?.focus();
        }
    });
})();
