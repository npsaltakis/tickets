document.getElementById('reminders-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('reminders-btn');
    const res = document.getElementById('reminders-result');
    btn.disabled = true;
    res.style.display = 'none';
    try {
        const body = new URLSearchParams(new FormData(e.target));
        const resp = await fetch(e.target.action, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() });
        const data = await resp.json();
        res.textContent = data.sent + ' / ' + (data.total || 0) + ' emails sent';
        res.style.display = 'inline';
    } catch { res.textContent = 'Error'; res.style.display = 'inline'; }
    finally { btn.disabled = false; }
});
