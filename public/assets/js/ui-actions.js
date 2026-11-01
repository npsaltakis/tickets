// CSP forbids inline event handlers (script-src-attr 'none'), so views declare intent with data attributes.
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
            copyButton.title = 'Copied!';
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
