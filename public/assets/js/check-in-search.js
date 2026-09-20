(function () {
    const box = document.querySelector('.check-in-search');
    const input = document.getElementById('check-in-search-input');
    const list = document.getElementById('check-in-search-results');
    const form = document.getElementById('check-in-form');
    const codeInput = document.getElementById('ticket_code');
    if (!box || !input || !list || !form || !codeInput) return;

    let timer = 0;
    let token = 0;

    const render = (results) => {
        list.innerHTML = '';
        results.forEach((r) => {
            const li = document.createElement('li');
            const info = document.createElement('span');
            info.textContent = `${r.name || '-'} · ${r.email} · ${r.event} · ${r.ticket_code}`;
            li.appendChild(info);

            if (r.checked_in) {
                const done = document.createElement('em');
                done.textContent = ` ${box.dataset.checkedLabel || ''}`;
                li.appendChild(done);
            } else {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'auth-link-btn';
                btn.textContent = box.dataset.actionLabel || 'Check in';
                btn.addEventListener('click', () => {
                    codeInput.value = r.ticket_code;
                    form.requestSubmit();
                });
                li.appendChild(btn);
            }
            list.appendChild(li);
        });
    };

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        const term = input.value.trim();
        if (term.length < 3) {
            list.innerHTML = '';
            return;
        }
        timer = window.setTimeout(async () => {
            const current = ++token;
            try {
                const resp = await fetch(`${box.dataset.searchUrl}?q=${encodeURIComponent(term)}`, { credentials: 'same-origin' });
                const data = await resp.json();
                if (current === token) render(data.results || []);
            } catch (e) { /* ignore transient errors */ }
        }, 250);
    });
})();
