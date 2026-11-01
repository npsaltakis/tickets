// Admin → Hero slider: drag the rows (or use the arrow keys on a handle) to change the slide order; it is saved at once.
(function () {
    const tbody = document.querySelector('[data-hero-sortable]');
    if (!tbody) {
        return;
    }

    const status = document.querySelector('[data-hero-status]');
    const csrfName = document.querySelector('meta[name="csrf-name"]')?.content || '_token';
    const csrfValue = document.querySelector('meta[name="csrf-value"]')?.content || '';

    function rows() {
        return Array.from(tbody.querySelectorAll('tr[data-id]'));
    }

    function renumber() {
        rows().forEach(function (row, i) {
            const order = row.querySelector('[data-hero-order]');
            if (order) {
                order.textContent = String(i + 1);
            }
        });
    }

    async function save() {
        renumber();
        const body = new URLSearchParams();
        body.set(csrfName, csrfValue);
        rows().forEach(function (row) { body.append('ids[]', row.dataset.id); });

        try {
            const resp = await fetch(tbody.dataset.reorderUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: body.toString(),
            });
            const data = await resp.json();
            if (!resp.ok || !data.success) {
                throw new Error();
            }
            status.textContent = tbody.dataset.savedText;
        } catch {
            status.textContent = tbody.dataset.errorText;
        }
    }

    if (window.Sortable) {
        window.Sortable.create(tbody, {
            handle: '[data-hero-handle]',
            draggable: 'tr[data-id]',
            animation: 150,
            ghostClass: 'hero-row-ghost',
            chosenClass: 'hero-row-chosen',
            onEnd: function (e) {
                if (e.oldIndex !== e.newIndex) {
                    save();
                }
            },
        });
    }

    // Keyboard: ↑/↓ on a handle moves its row.
    tbody.addEventListener('keydown', function (e) {
        const handle = e.target.closest('[data-hero-handle]');
        if (!handle || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) {
            return;
        }
        e.preventDefault();
        const row = handle.closest('tr');
        const target = e.key === 'ArrowUp' ? row.previousElementSibling : row.nextElementSibling;
        if (!target) {
            return;
        }
        if (e.key === 'ArrowUp') {
            target.before(row);
        } else {
            target.after(row);
        }
        handle.focus();
        save();
    });
})();
