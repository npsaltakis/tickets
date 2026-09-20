(function () {
    const statsUrl = window.baseUrl + 'check-in/stats';
    const updateEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

    async function refresh() {
        try {
            const data = await (await fetch(statsUrl, { credentials: 'same-origin' })).json();
            const t = data.totals || {};
            updateEl('ci-stat-issued', t.issued ?? '');
            updateEl('ci-stat-checkedin', t.checked_in ?? '');
            updateEl('ci-stat-pending', t.pending ?? '');
        } catch {}
    }

    setInterval(refresh, 15000);
})();
