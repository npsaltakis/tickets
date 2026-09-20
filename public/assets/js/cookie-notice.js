(function () {
    const box = document.getElementById('cookie-notice');
    if (!box) return;

    const KEY = 'cookie_notice_ack';
    let acknowledged = false;
    try { acknowledged = window.localStorage.getItem(KEY) === '1'; } catch (e) { acknowledged = false; }

    if (acknowledged) return;
    box.hidden = false;

    box.querySelector('[data-cookie-accept]')?.addEventListener('click', () => {
        try { window.localStorage.setItem(KEY, '1'); } catch (e) { /* storage unavailable: hide for this page only */ }
        box.hidden = true;
    });
})();
