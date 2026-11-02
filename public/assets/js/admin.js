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

    const setCollapsed = (collapsed) => {
        sidebar.classList.toggle('is-collapsed', collapsed);
        try { localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0'); } catch (e) { /* not stored */ }
    };

    applyLayout();
    mobile.addEventListener('change', applyLayout);

    toggle.addEventListener('click', () => {
        if (mobile.matches) {
            setDrawer(false);
            menuBtn?.focus();
            return;
        }

        setCollapsed(!sidebar.classList.contains('is-collapsed'));
    });

    menuBtn?.addEventListener('click', () => setDrawer(!sidebar.classList.contains('is-mobile-open')));
    backdrop?.addEventListener('click', () => setDrawer(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-mobile-open')) {
            setDrawer(false);
            menuBtn?.focus();
        }
    });

    // Menu groups: only the one with the current page is open; the others open/close on click for this page only.
    // Earlier versions remembered opened groups; drop what they stored.
    try { localStorage.removeItem('admin_nav_open_groups'); } catch (e) { /* nothing stored */ }

    const setGroup = (group, open) => {
        const button = group.querySelector('.admin-nav-group-toggle');
        const items  = group.querySelector('.admin-nav-group-items');
        group.classList.toggle('is-open', open);
        button?.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (items) items.hidden = !open;
    };

    sidebar.querySelectorAll('[data-nav-group]').forEach((group) => {
        setGroup(group, group.dataset.active === '1');

        group.querySelector('.admin-nav-group-toggle')?.addEventListener('click', () => {
            // In the icons-only sidebar a group shows just its icon: widen the sidebar with that group open.
            if (sidebar.classList.contains('is-collapsed')) {
                setCollapsed(false);
                setGroup(group, true);
                return;
            }
            setGroup(group, !group.classList.contains('is-open'));
        });
    });
})();
