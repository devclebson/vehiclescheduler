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
    const CLASS_DISMISS = 'vs-flash__dismiss';
    const CLASS_ICON = 'vs-flash__icon';
    const CLASS_TITLE = 'vs-flash__title';
    const CLASS_BODY = 'vs-flash__body';
    const CLASS_MESSAGE = 'vs-flash__message';
    const CLASS_TITLE_TEXT = 'vs-flash__title-text';
    const CLASS_MAP = {
        success: 'vs-flash--success',
        info: 'vs-flash--info',
        warning: 'vs-flash--warning',
        error: 'vs-flash--error'
    };
    const ALERT_CLASS_MAP = {
        success: 'alert-success',
        info: 'alert-info',
        warning: 'alert-warning',
        error: 'alert-danger'
    };
    const ICON_MAP = {
        success: 'check',
        info: 'info',
        warning: 'warning',
        error: 'danger'
    };
    const TITLE_MAP = {
        success: 'Sucesso',
        info: 'Informação',
        warning: 'Aviso',
        error: 'Erro'
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
        node.classList.remove('alert-success');
        node.classList.remove('alert-info');
        node.classList.remove('alert-warning');
        node.classList.remove('alert-danger');
    }

    function removeNativeControls(node) {
        const nativeControls = node.querySelectorAll([
            '.ui-pnotify-title',
            '.ui-pnotify-sticker',
            '.ui-pnotify-closer',
            '.ui-pnotify-icon',
            '.btn-close',
            '[data-bs-dismiss="alert"]',
            '.close'
        ].join(','));

        nativeControls.forEach((control) => {
            control.remove();
        });
    }

    function getFlashMessage(node) {
        const marked = node.querySelector('[data-vs-flash-kind]');

        if (marked && marked.textContent) {
            return marked.textContent.trim();
        }

        return (node.textContent || '').trim();
    }

    function rebuildFlash(node, kind, messageText) {
        node.replaceChildren();

        const body = document.createElement('div');
        body.className = `${CLASS_BODY} alert-main`;

        const title = document.createElement('div');
        title.className = `${CLASS_TITLE} alert-title`;

        const titleIcon = document.createElement('span');
        titleIcon.className = `${CLASS_ICON} alert-icon`;
        titleIcon.setAttribute('aria-hidden', 'true');
        titleIcon.setAttribute('data-vs-flash-icon', ICON_MAP[kind] || 'info');

        const titleText = document.createElement('strong');
        titleText.className = CLASS_TITLE_TEXT;
        titleText.textContent = TITLE_MAP[kind] || TITLE_MAP.info;

        title.appendChild(titleIcon);
        title.appendChild(titleText);

        const message = document.createElement('p');
        message.className = `${CLASS_MESSAGE} alert-message`;
        message.textContent = messageText;

        body.appendChild(title);
        body.appendChild(message);
        node.appendChild(body);

        const button = document.createElement('button');
        button.type = 'button';
        button.className = `${CLASS_DISMISS} alert-close`;
        button.setAttribute('aria-label', 'Fechar aviso');
        button.innerHTML = '&times;';
        button.addEventListener('click', () => {
            node.remove();
        });
        node.appendChild(button);
    }

    function ensureDivContainer(node) {
        if (node.tagName.toLowerCase() !== 'button') {
            return node;
        }

        const replacement = document.createElement('div');
        replacement.className = node.className;

        Array.from(node.attributes).forEach((attribute) => {
            if (attribute.name === 'type') {
                return;
            }

            replacement.setAttribute(attribute.name, attribute.value);
        });

        replacement.textContent = node.textContent || '';
        node.replaceWith(replacement);

        return replacement;
    }

    function enhanceFlash(node) {
        if (!(node instanceof HTMLElement)) {
            return;
        }

        if (node.getAttribute('data-vs-flash-enhanced') === '1') {
            return;
        }

        const kind = detectKind(node);

        if (!kind || !CLASS_MAP[kind]) {
            return;
        }

        const messageText = getFlashMessage(node);
        node = ensureDivContainer(node);

        clearFlashClasses(node);
        node.classList.add(CLASS_BASE);
        node.classList.add('alert');
        node.classList.add('alert-dismissible');
        node.classList.add(ALERT_CLASS_MAP[kind]);
        node.classList.add(CLASS_MAP[kind]);
        node.setAttribute('role', 'alert');
        node.setAttribute('data-vs-flash-enhanced', '1');

        removeNativeControls(node);
        rebuildFlash(node, kind, messageText);
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
