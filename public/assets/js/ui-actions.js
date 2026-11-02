// CSP forbids inline event handlers (script-src-attr 'none'), so views declare intent with data attributes.
const primaryMain = document.querySelector('main');
if (primaryMain && !primaryMain.id) {
    primaryMain.id = 'main-content';
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.querySelectorAll('details.user-menu[open], details.admin-notif[open]').forEach((menu) => {
            menu.removeAttribute('open');
            const summary = menu.querySelector('summary');
            if (summary instanceof HTMLElement) {
                summary.focus();
            }
        });
    }
});

// Forms marked data-submit-once (booking, waitlist) ignore a second submit for a few seconds.
// Runs on window, after the form's own and the data-confirm handlers had their chance to cancel.
window.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-submit-once') || event.defaultPrevented) {
        return;
    }
    if (form.dataset.submitting === '1') {
        event.preventDefault();
        return;
    }
    form.dataset.submitting = '1';
    const submitter = event.submitter instanceof HTMLElement ? event.submitter : form.querySelector('button[type="submit"], input[type="submit"]');
    if (submitter instanceof HTMLElement) {
        submitter.setAttribute('aria-busy', 'true');
    }
    window.setTimeout(() => {
        delete form.dataset.submitting;
        if (submitter instanceof HTMLElement) {
            submitter.removeAttribute('aria-busy');
        }
    }, 8000);
});
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) {
        return;
    }

    if (!window.confirm(form.getAttribute('data-confirm') || 'Are you sure?')) {
        event.preventDefault();
    }
});

document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (target) {
        document.querySelectorAll('details.user-menu[open], details.admin-notif[open]').forEach((menu) => {
            if (!menu.contains(target)) {
                menu.removeAttribute('open');
            }
        });
    }
    if (!target) {
        return;
    }

    const printButton = target.closest('[data-print]');
    if (printButton) {
        window.print();
        return;
    }

    const copyButton = target.closest('[data-copy-url]');
    if (copyButton && navigator.clipboard) {
        navigator.clipboard.writeText(copyButton.getAttribute('data-copy-url') || '').then(() => {
            const english = document.documentElement.lang === 'en';
            const copiedLabel = english ? 'Copied!' : 'Αντιγράφηκε!';
            const origTitle = copyButton.getAttribute('data-orig-title') || copyButton.getAttribute('title') || '';
            if (!copyButton.hasAttribute('data-orig-title')) {
                copyButton.setAttribute('data-orig-title', origTitle);
            }
            copyButton.title = copiedLabel;
            copyButton.setAttribute('aria-label', copiedLabel);
            copyButton.setAttribute('data-copied-label', copiedLabel);
            copyButton.classList.add('is-copied');
            window.setTimeout(() => {
                copyButton.classList.remove('is-copied');
                if (origTitle) {
                    copyButton.title = origTitle;
                    copyButton.setAttribute('aria-label', origTitle);
                }
            }, 2000);
        });
    }
});

// Show/hide toggle for every password field.
document.querySelectorAll('input[type="password"]').forEach((input) => {
    const english = document.documentElement.lang === 'en';
    const labels = english ? ['Show', 'Hide'] : ['Εμφάνιση', 'Απόκρυψη'];
    const wrapper = document.createElement('div');
    wrapper.className = 'password-field';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'password-toggle';
    button.textContent = labels[0];
    button.setAttribute('aria-pressed', 'false');
    button.setAttribute('aria-label', english ? 'Show password' : 'Εμφάνιση κωδικού');
    button.addEventListener('click', () => {
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = labels[show ? 1 : 0];
        button.setAttribute('aria-pressed', show ? 'true' : 'false');
        button.setAttribute('aria-label', english ? (show ? 'Hide password' : 'Show password') : (show ? 'Απόκρυψη κωδικού' : 'Εμφάνιση κωδικού'));
    });
    wrapper.appendChild(button);
});

// Links with data-lightbox open their image enlarged in a dialog (click or Esc closes it).
document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[data-lightbox]') : null;
    if (!link || typeof HTMLDialogElement === 'undefined') {
        return;
    }

    event.preventDefault();
    const dialog = document.createElement('dialog');
    dialog.className = 'lightbox';
    const image = document.createElement('img');
    image.src = link.getAttribute('href') || '';
    image.alt = link.getAttribute('aria-label') || '';
    dialog.appendChild(image);
    dialog.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => dialog.remove());
    document.body.appendChild(dialog);
    dialog.showModal();
});

// Tables become cards on phones (CSS); each cell gets its column name as a label to show in front of the value.
function labelTableCells(table) {
    const headers = Array.from(table.querySelectorAll('thead tr:last-child th')).map((th) => th.textContent.replace(/\s+/g, ' ').trim());
    if (!headers.length) {
        return;
    }

    table.querySelectorAll('tbody tr').forEach((row) => {
        Array.from(row.children).forEach((cell, i) => {
            if (!cell.hasAttribute('data-label') && headers[i]) {
                cell.setAttribute('data-label', headers[i]);
            }
        });
    });
}

document.querySelectorAll('table.admin-table, table.report-table').forEach((table) => {
    labelTableCells(table);

    // DataTables adds and replaces rows when paging, sorting or searching.
    const body = table.tBodies[0];
    if (body && 'MutationObserver' in window) {
        new MutationObserver(() => labelTableCells(table)).observe(body, { childList: true });
    }
});
