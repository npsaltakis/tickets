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
