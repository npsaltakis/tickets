(() => {
    /* ── Inline status edit ─────────────────────────────── */
    document.querySelectorAll('.js-status-select').forEach(select => {
        select.addEventListener('change', async () => {
            const slug   = select.dataset.slug;
            const status = select.value;
            const csrf   = document.querySelector('meta[name="csrf-value"]')?.content || '';
            const csrfName = document.querySelector('meta[name="csrf-name"]')?.content || '_token';

            select.disabled = true;

            try {
                const body = new URLSearchParams();
                body.set('status', status);
                body.set(csrfName, csrf);

                const resp = await fetch(`${window.baseUrl}admin/events/${encodeURIComponent(slug)}/status`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString(),
                });

                const data = await resp.json();
                if (data.success) {
                    select.className = select.className.replace(/status-\S+/g, '').trim();
                    select.classList.add('js-status-select', `status-${data.status}`);
                } else {
                    alert('Update failed');
                }
            } catch {
                alert('Network error');
            } finally {
                select.disabled = false;
            }
        });
    });

    /* ── Bulk actions ────────────────────────────────────── */
    const selectAll  = document.getElementById('bulk-select-all');
    const bulkBar    = document.getElementById('bulk-bar');
    const bulkCount  = document.getElementById('bulk-count');
    const bulkAction = document.getElementById('bulk-action');
    const bulkApply  = document.getElementById('bulk-apply');

    const getChecked = () => [...document.querySelectorAll('.bulk-check:checked')];

    const updateBulkBar = () => {
        const checked = getChecked();
        if (bulkBar) bulkBar.classList.toggle('is-visible', checked.length > 0);
        if (bulkCount) bulkCount.textContent = checked.length;
    };

    selectAll?.addEventListener('change', () => {
        document.querySelectorAll('.bulk-check').forEach(cb => { cb.checked = selectAll.checked; });
        updateBulkBar();
    });

    document.querySelectorAll('.bulk-check').forEach(cb => {
        cb.addEventListener('change', updateBulkBar);
    });

    bulkApply?.addEventListener('click', async () => {
        const action  = bulkAction?.value;
        const checked = getChecked();
        if (!action || checked.length === 0) return;

        const label = bulkAction?.options[bulkAction.selectedIndex]?.text;
        if (!confirm(`${label} ${checked.length} event(s)?`)) return;

        const csrf     = document.querySelector('meta[name="csrf-value"]')?.content || '';
        const csrfName = document.querySelector('meta[name="csrf-name"]')?.content || '_token';

        const body = new URLSearchParams();
        body.set('action', action);
        body.set(csrfName, csrf);
        checked.forEach(cb => body.append('slugs[]', cb.dataset.slug));

        try {
            const resp = await fetch(`${window.baseUrl}admin/events/bulk`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });
            const data = await resp.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Bulk action failed');
            }
        } catch {
            alert('Network error');
        }
    });
})();
