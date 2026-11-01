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

    // Menu groups: the one with the current page is open; the others open/close on click and are remembered.
    const GROUPS_KEY = 'admin_nav_open_groups';
    const readOpen = () => {
        try { return JSON.parse(localStorage.getItem(GROUPS_KEY) || '[]'); } catch (e) { return []; }
    };
    const saveOpen = (list) => {
        try { localStorage.setItem(GROUPS_KEY, JSON.stringify(list)); } catch (e) { /* not stored */ }
    };

    const setGroup = (group, open) => {
        const button = group.querySelector('.admin-nav-group-toggle');
        const items  = group.querySelector('.admin-nav-group-items');
        group.classList.toggle('is-open', open);
        button?.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (items) items.hidden = !open;
    };

    const remembered = readOpen();
    sidebar.querySelectorAll('[data-nav-group]').forEach((group) => {
        setGroup(group, group.dataset.active === '1' || remembered.includes(group.dataset.navGroup));

        group.querySelector('.admin-nav-group-toggle')?.addEventListener('click', () => {
            const open = !group.classList.contains('is-open');
            setGroup(group, open);
            const list = readOpen().filter((key) => key !== group.dataset.navGroup);
            if (open) list.push(group.dataset.navGroup);
            saveOpen(list);
        });
    });
})();
