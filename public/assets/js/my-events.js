document.querySelectorAll('[data-confirm-action]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.getAttribute('data-confirm-message') || 'Are you sure?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('.js-ticket-select').forEach((select) => {
    const block = select.closest('.ticket-cancel-block');
    const form = block ? block.querySelector('.js-ticket-cancel-form') : null;
    if (!form) return;

    select.addEventListener('change', () => {
        const base = form.dataset.cancelBase;
        form.action = base + select.value + '/cancel';
    });
});
