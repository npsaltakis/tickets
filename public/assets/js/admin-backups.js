// Admin → Backups: shows a progress bar while a backup is written. The server sends one JSON line per step
// (BackupAdminController::create with the X-Backup-Progress header); without fetch streams the form posts normally.
(() => {
    const box = document.getElementById('backup-progress');
    if (!box || !window.fetch || !window.TextDecoder || !('body' in Response.prototype)) return;

    const track   = box.querySelector('.backup-progress-track');
    const bar     = box.querySelector('.backup-progress-bar');
    const message = box.querySelector('.backup-progress-message');
    const percent = box.querySelector('.backup-progress-percent');
    const forms   = document.querySelectorAll('form[data-backup-progress]');

    const show = (value, text) => {
        const clamped = Math.max(0, Math.min(100, value));
        bar.style.width = clamped + '%';
        track.setAttribute('aria-valuenow', String(clamped));
        percent.textContent = clamped + '%';
        if (text) message.textContent = text;
    };

    const setBusy = (busy) => forms.forEach((form) => {
        form.querySelectorAll('button').forEach((button) => { button.disabled = busy; });
    });

    // Leaving mid-way would not stop the backup, but the admin would not see how it ended.
    const warnOnLeave = (event) => { event.preventDefault(); event.returnValue = ''; };

    forms.forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();

        box.hidden = false;
        box.classList.remove('is-failed');
        show(0, box.dataset.starting || '');
        setBusy(true);
        window.addEventListener('beforeunload', warnOnLeave);

        let result = null;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Backup-Progress': '1', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok || !response.body) throw new Error('HTTP ' + response.status);

            const reader  = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            for (;;) {
                const { value, done } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });

                let newline;
                while ((newline = buffer.indexOf('\n')) >= 0) {
                    const line = buffer.slice(0, newline).trim();
                    buffer = buffer.slice(newline + 1);
                    if (!line) continue;

                    const data = JSON.parse(line);
                    if (typeof data.percent === 'number') show(data.percent, data.message);
                    if (data.done || data.error) result = data;
                }
            }
        } catch (error) {
            result = { error: error.message };
        }

        window.removeEventListener('beforeunload', warnOnLeave);

        if (result && result.done) {
            show(100);
            window.location.reload();
            return;
        }

        // The server stores the reason as a flash message; reloading shows it. Without one, say it here.
        box.classList.add('is-failed');
        message.textContent = (box.dataset.failed || 'Failed.') + (result && result.error ? ' ' + result.error : '');
        setBusy(false);
        if (result && result.error && !String(result.error).startsWith('HTTP')) window.location.reload();
    }));
})();
