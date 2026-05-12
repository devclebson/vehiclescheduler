(function () {
    const page = document.querySelector('.vs-page-management');
    const root = document.documentElement;

    if (!page) {
        return;
    }

    const THEME_KEY = 'vs_ui_theme';
    const FONT_KEY = 'vs_ui_font_scale';

    const themeToggle = document.getElementById('vsMgmtThemeToggle');
    const fontDecrease = document.getElementById('vsFontDecrease');
    const fontIncrease = document.getElementById('vsFontIncrease');
    const fontReset = document.getElementById('vsFontReset');
    const visualReset = document.getElementById('vsVisualReset');

    if (!themeToggle || !fontDecrease || !fontIncrease || !fontReset || !visualReset) {
        return;
    }

    const minScale = 0.90;
    const maxScale = 1.30;
    const step = 0.05;
    const basePx = 16;

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function applyTheme(mode) {
        if (mode === 'dark') {
            page.classList.add('vs-dark-mode');
            document.body.classList.add('vs-dark');
            themeToggle.checked = true;
            return;
        }

        page.classList.remove('vs-dark-mode');
        document.body.classList.remove('vs-dark');
        themeToggle.checked = false;
    }

    function applyFontScale(scale) {
        const safeScale = clamp(scale, minScale, maxScale);
        root.style.fontSize = (basePx * safeScale) + 'px';
    }

    function applyCompactMode() {
        page.classList.add('vs-compact-mode');
    }

    const savedTheme = localStorage.getItem(THEME_KEY);
    const savedScale = parseFloat(localStorage.getItem(FONT_KEY) || '1');
    const systemPrefersDark = window.matchMedia
        && window.matchMedia('(prefers-color-scheme: dark)').matches;

    const initialTheme = savedTheme || (systemPrefersDark ? 'dark' : 'light');

    applyTheme(initialTheme);
    applyFontScale(Number.isNaN(savedScale) ? 1 : savedScale);
    applyCompactMode();

    themeToggle.addEventListener('change', function () {
        const mode = themeToggle.checked ? 'dark' : 'light';
        localStorage.setItem(THEME_KEY, mode);
        applyTheme(mode);
    });

    fontDecrease.addEventListener('click', function () {
        const current = parseFloat(localStorage.getItem(FONT_KEY) || '1');
        const next = clamp(current - step, minScale, maxScale);
        localStorage.setItem(FONT_KEY, String(next));
        applyFontScale(next);
    });

    fontIncrease.addEventListener('click', function () {
        const current = parseFloat(localStorage.getItem(FONT_KEY) || '1');
        const next = clamp(current + step, minScale, maxScale);
        localStorage.setItem(FONT_KEY, String(next));
        applyFontScale(next);
    });

    fontReset.addEventListener('click', function () {
        localStorage.setItem(FONT_KEY, '1');
        applyFontScale(1);
    });

    visualReset.addEventListener('click', function () {
        localStorage.setItem(THEME_KEY, 'light');
        localStorage.setItem(FONT_KEY, '1');
        applyTheme('light');
        applyFontScale(1);
        applyCompactMode();
    });
})();