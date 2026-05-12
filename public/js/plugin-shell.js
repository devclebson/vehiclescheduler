(function () {
    const applyBodyClass = () => {
        if (document.body) {
            document.body.classList.add('vs-app-body');
        }
    };

    const applyDarkTheme = () => {
        if (document.body) {
            document.body.classList.add('vs-dark');
        }

        document.documentElement.classList.add('vs-dark');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyBodyClass);
    } else {
        applyBodyClass();
    }

    let mode = localStorage.getItem('vs_ui_theme');

    if (!mode) {
        mode = localStorage.getItem('vs_requester_theme');
    }

    if (mode === 'dark') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyDarkTheme);
        } else {
            applyDarkTheme();
        }
    }
})();
