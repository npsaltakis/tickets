(function () {
    if (typeof Chart === 'undefined') return;

    const chartDefaults = {
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { color: 'rgba(148,163,184,0.1)' } },
            y: { ticks: { color: '#94a3b8', font: { size: 10 } }, grid: { color: 'rgba(148,163,184,0.1)' }, beginAtZero: true },
        },
    };

    const build = (id, color, border, extra) => {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        const cfg = JSON.parse(canvas.dataset.chart || '{}');
        new Chart(canvas, {
            type: 'bar',
            data: { labels: cfg.labels || [], datasets: [{ data: cfg.values || [], backgroundColor: color, borderColor: border, borderWidth: 1, borderRadius: 4 }] },
            options: { ...chartDefaults, ...extra, responsive: true },
        });
    };

    build('chart-bookings', 'rgba(20,184,166,0.6)', '#14b8a6', {});
    build('chart-revenue', 'rgba(245,158,11,0.6)', '#f59e0b', { indexAxis: 'y' });
})();
