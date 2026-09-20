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
