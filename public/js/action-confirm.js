document.querySelectorAll('[data-confirm-message]').forEach((element) => {
    element.addEventListener('click', (event) => {
        const message = element.getAttribute('data-confirm-message') || 'Confirmar ação?';

        if (!window.confirm(message)) {
            event.preventDefault();
        }
    });
});
