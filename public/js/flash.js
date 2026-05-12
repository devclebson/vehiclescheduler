(function () {
    const FLASH_SELECTORS = [
        '.message_after_redirect',
        '.messages_after_redirect .alert',
        '.messages_after_redirect .message_after_redirect',
        '.alert',
        '.alert-info',
        '.alert-warning',
        '.alert-danger',
        '.alert-error',
        '.alert-success',
        '.ui-pnotify',
        '.toast',
        '.notification'
    ];

    const CLASS_BASE = 'vs-flash';
    const CLASS_MAP = {
        success: 'vs-flash--success',
        info: 'vs-flash--info',
        warning: 'vs-flash--warning',
        error: 'vs-flash--error'
    };

    function normalize(value) {
        return String(value || '').toLowerCase().trim();
    }

    function getText(node) {
        return normalize(node && node.textContent ? node.textContent : '');
    }

    function hasAnyClass(node, names) {
        if (!node || !node.classList) {
            return false;
        }

        return names.some((name) => node.classList.contains(name));
    }

    function detectKindFromClasses(node) {
        if (!node || !node.classList) {
            return null;
        }

        const classText = normalize(node.className);

        if (
            hasAnyClass(node, ['alert-success', 'success']) ||
            classText.includes('success')
        ) {
            return 'success';
        }

        if (
            hasAnyClass(node, ['alert-danger', 'alert-error', 'error', 'danger']) ||
            classText.includes('alert-danger') ||
            classText.includes('alert-error') ||
            classText.includes(' error') ||
            classText.includes(' danger')
        ) {
            return 'error';
        }

        if (
            hasAnyClass(node, ['alert-warning', 'warning']) ||
            classText.includes('warning')
        ) {
            return 'warning';
        }

        if (
            hasAnyClass(node, ['alert-info', 'info']) ||
            classText.includes('alert-info') ||
            classText.includes(' info')
        ) {
            return 'info';
        }

        return null;
    }

    function detectKindFromDataset(node) {
        if (!node) {
            return null;
        }

        const direct = normalize(node.getAttribute('data-vs-flash-kind'));
        if (direct === 'success' || direct === 'info' || direct === 'warning' || direct === 'error') {
            return direct;
        }

        const inner = node.querySelector('[data-vs-flash-kind]');
        if (inner) {
            const nested = normalize(inner.getAttribute('data-vs-flash-kind'));
            if (nested === 'success' || nested === 'info' || nested === 'warning' || nested === 'error') {
                return nested;
            }
        }

        return null;
    }

    function detectKindFromText(node) {
        const text = getText(node);

        if (!text) {
            return null;
        }

        if (
            text.includes('com sucesso') ||
            text.includes('sucesso') ||
            text.includes('cadastrado') ||
            text.includes('cadastrada') ||
            text.includes('atualizado') ||
            text.includes('atualizada') ||
            text.includes('removido') ||
            text.includes('removida') ||
            text.includes('restaurado') ||
            text.includes('restaurada') ||
            text.includes('aprovado') ||
            text.includes('aprovada')
        ) {
            return 'success';
        }

        if (
            text.includes('não foi possível') ||
            text.includes('nao foi possivel') ||
            text.includes('erro') ||
            text.includes('inválido') ||
            text.includes('invalido') ||
            text.includes('obrigatório') ||
            text.includes('obrigatoria') ||
            text.includes('já está em uso') ||
            text.includes('ja esta em uso') ||
            text.includes('falhou')
        ) {
            return 'error';
        }

        if (
            text.includes('atenção') ||
            text.includes('atencao') ||
            text.includes('aguardando aprovação') ||
            text.includes('aguardando aprovacao') ||
            text.includes('alerta')
        ) {
            return 'warning';
        }

        return 'info';
    }

    function detectKind(node) {
        return (
            detectKindFromDataset(node) ||
            detectKindFromClasses(node) ||
            detectKindFromText(node)
        );
    }

    function clearFlashClasses(node) {
        node.classList.remove(CLASS_BASE);
        node.classList.remove(CLASS_MAP.success);
        node.classList.remove(CLASS_MAP.info);
        node.classList.remove(CLASS_MAP.warning);
        node.classList.remove(CLASS_MAP.error);
    }

    function enhanceFlash(node) {
        if (!(node instanceof HTMLElement)) {
            return;
        }

        const kind = detectKind(node);

        if (!kind || !CLASS_MAP[kind]) {
            return;
        }

        clearFlashClasses(node);
        node.classList.add(CLASS_BASE);
        node.classList.add(CLASS_MAP[kind]);
        node.setAttribute('data-vs-flash-enhanced', '1');
    }

    function scan(root) {
        if (!root) {
            return;
        }

        if (root instanceof HTMLElement) {
            FLASH_SELECTORS.forEach((selector) => {
                if (root.matches(selector)) {
                    enhanceFlash(root);
                }
            });
        }

        const nodes = root.querySelectorAll ? root.querySelectorAll(FLASH_SELECTORS.join(',')) : [];
        nodes.forEach(enhanceFlash);
    }

    function boot() {
        scan(document);

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node instanceof HTMLElement) {
                        scan(node);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();