document.querySelectorAll('[data-confirm-action]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.getAttribute('data-confirm-message') || 'Are you sure?';
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});
