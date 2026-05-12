(function () {
    'use strict';

    var page = document.querySelector('.vs-page-admin-dashboard');
    var root = document.documentElement;

    if (!page) {
        return;
    }

    var THEME_KEY = 'vs_ui_theme';
    var FONT_KEY = 'vs_ui_font_scale';
    var themeToggle = document.getElementById('vsExecThemeToggle');
    var fontDecrease = document.getElementById('vsFontDecrease');
    var fontIncrease = document.getElementById('vsFontIncrease');
    var fontReset = document.getElementById('vsFontReset');
    var visualReset = document.getElementById('vsVisualReset');
    var clockEl = document.getElementById('vsWallClock');
    var countdownEl = document.getElementById('vsRefreshCountdown');
    var minScale = 0.90;
    var maxScale = 1.18;
    var step = 0.04;
    var basePx = 16;
    var REFRESH_INTERVAL_MS = 15000;
    var refreshDeadline = getNextRefreshDeadline();

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function applyTheme(mode) {
        if (!themeToggle) {
            return;
        }

        if (mode === 'dark') {
            page.classList.add('vs-dark-mode');
            themeToggle.checked = true;
        } else {
            page.classList.remove('vs-dark-mode');
            themeToggle.checked = false;
        }
    }

    function applyFontScale(scale) {
        var safeScale = clamp(scale, minScale, maxScale);
        root.style.fontSize = (basePx * safeScale) + 'px';
    }

    function applyCompactMode() {
        page.classList.add('vs-compact-mode');
    }

    function getNextRefreshDeadline() {
        return Date.now() + REFRESH_INTERVAL_MS;
    }

    function formatTime(date) {
        return date.toLocaleTimeString('pt-BR');
    }

    function updateClock() {
        if (!clockEl) {
            return;
        }

        clockEl.textContent = formatTime(new Date());
    }

    function updateCountdown() {
        if (!countdownEl) {
            return;
        }

        var now = Date.now();

        if (now >= refreshDeadline) {
            window.location.reload();
            return;
        }

        var remainingMs = refreshDeadline - now;
        var remainingSeconds = Math.ceil(remainingMs / 1000);

        countdownEl.textContent = remainingSeconds + 's';
        countdownEl.title = 'Próxima atualização às ' + formatTime(new Date(refreshDeadline));
    }

    function resyncDeadline() {
        refreshDeadline = getNextRefreshDeadline();
        updateCountdown();
    }

    var savedTheme = localStorage.getItem(THEME_KEY);
    var savedScale = parseFloat(localStorage.getItem(FONT_KEY) || '1');
    var systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    var initialTheme = savedTheme ? savedTheme : (systemPrefersDark ? 'dark' : 'light');

    applyTheme(initialTheme);
    applyFontScale(isNaN(savedScale) ? 1 : savedScale);
    applyCompactMode();

    if (themeToggle) {
        themeToggle.addEventListener('change', function () {
            var mode = themeToggle.checked ? 'dark' : 'light';
            localStorage.setItem(THEME_KEY, mode);
            applyTheme(mode);
        });
    }

    if (fontDecrease) {
        fontDecrease.addEventListener('click', function () {
            var current = parseFloat(localStorage.getItem(FONT_KEY) || '1');
            var next = clamp(current - step, minScale, maxScale);
            localStorage.setItem(FONT_KEY, String(next));
            applyFontScale(next);
        });
    }

    if (fontIncrease) {
        fontIncrease.addEventListener('click', function () {
            var current = parseFloat(localStorage.getItem(FONT_KEY) || '1');
            var next = clamp(current + step, minScale, maxScale);
            localStorage.setItem(FONT_KEY, String(next));
            applyFontScale(next);
        });
    }

    if (fontReset) {
        fontReset.addEventListener('click', function () {
            localStorage.setItem(FONT_KEY, '1');
            applyFontScale(1);
        });
    }

    if (visualReset) {
        visualReset.addEventListener('click', function () {
            localStorage.setItem(THEME_KEY, 'light');
            localStorage.setItem(FONT_KEY, '1');
            applyTheme('light');
            applyFontScale(1);
            applyCompactMode();
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            resyncDeadline();
            updateClock();
        }
    });

    updateClock();
    updateCountdown();

    window.setInterval(updateClock, 1000);
    window.setInterval(updateCountdown, 250);
})();
