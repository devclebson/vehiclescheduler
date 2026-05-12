document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.tab_cadre_fixe, .tab_cadre_fixehov').forEach((element) => {
        if (!element.classList.contains('vs-glass-card-solid')) {
            element.classList.add('vs-table-glass');
        }
    });

    document.querySelectorAll('.btn').forEach((button) => {
        if (!button.classList.contains('vs-btn-app')) {
            button.classList.add('vs-btn-app');
        }
    });

    document.querySelectorAll('input[type=text], input[type=number], input[type=date], select, textarea').forEach((input) => {
        if (!input.classList.contains('vs-input-glass')) {
            input.classList.add('vs-input-glass');
        }
    });

    document.querySelectorAll('[data-vs-history-back]').forEach((button) => {
        button.addEventListener('click', () => {
            window.history.back();
        });
    });
});
