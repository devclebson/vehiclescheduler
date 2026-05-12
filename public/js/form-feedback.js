(function () {
    'use strict';

    function getFieldTarget(field) {
        if (!field) {
            return null;
        }

        if (field.type === 'hidden') {
            return null;
        }

        return field;
    }

    function getFieldMessageNode(target) {
        if (!target) {
            return null;
        }

        var nextElement = target.nextElementSibling;
        if (nextElement && nextElement.classList.contains('vs-field-feedback')) {
            return nextElement;
        }

        var message = document.createElement('div');
        message.className = 'vs-field-feedback';
        message.setAttribute('hidden', 'hidden');
        target.insertAdjacentElement('afterend', message);

        return message;
    }

    function setFieldError(field, message) {
        var target = getFieldTarget(field);
        if (!target) {
            return;
        }

        target.setCustomValidity(message || '');
        target.setAttribute('aria-invalid', message ? 'true' : 'false');

        var messageNode = getFieldMessageNode(target);
        if (!messageNode) {
            return;
        }

        if (!message) {
            messageNode.textContent = '';
            messageNode.setAttribute('hidden', 'hidden');
            return;
        }

        messageNode.textContent = message;
        messageNode.removeAttribute('hidden');
    }

    function clearFieldError(field) {
        setFieldError(field, '');
    }

    function showFormAlert(container, message) {
        if (!container) {
            return;
        }

        if (!message) {
            container.textContent = '';
            container.setAttribute('hidden', 'hidden');
            return;
        }

        container.textContent = message;
        container.removeAttribute('hidden');
    }

    function getFieldMessage(field) {
        var target = getFieldTarget(field);
        if (!target) {
            return '';
        }

        var nextElement = target.nextElementSibling;
        if (!nextElement || !nextElement.classList.contains('vs-field-feedback')) {
            return '';
        }

        return nextElement.hasAttribute('hidden') ? '' : (nextElement.textContent || '').trim();
    }

    function ensureFormAlert(form, className) {
        if (!form) {
            return null;
        }

        var existing = form.querySelector('.vs-form-feedback');
        if (existing) {
            return existing;
        }

        var alert = document.createElement('div');
        alert.className = className || 'vs-form-feedback';
        alert.setAttribute('hidden', 'hidden');
        form.insertBefore(alert, form.firstChild);

        return alert;
    }

    function syncFormSummary(form, container) {
        if (!form || !container) {
            return;
        }

        var invalidFields = Array.prototype.slice.call(
            form.querySelectorAll('[aria-invalid="true"]')
        ).filter(function (field) {
            return !field.disabled;
        });

        if (invalidFields.length <= 1) {
            showFormAlert(container, '');
            return;
        }

        var firstMessage = getFieldMessage(invalidFields[0]) || 'Revise os campos destacados.';
        var summary = 'Existem ' + invalidFields.length + ' campos com erro. ' + firstMessage;

        showFormAlert(container, summary);
    }

    function countInvalidFields(form) {
        if (!form) {
            return 0;
        }

        return Array.prototype.slice.call(
            form.querySelectorAll('[aria-invalid="true"]')
        ).filter(function (field) {
            return !field.disabled;
        }).length;
    }

    function showValidationDialog(message, title) {
        var dialogTitle = title || 'Campos invalidos';
        var body = message || 'Revise os campos destacados antes de continuar.';

        if (typeof window.GLPI !== 'undefined' && typeof window.GLPI.modal === 'function') {
            window.GLPI.modal({
                title: dialogTitle,
                message: body
            });
            return;
        }

        window.alert(dialogTitle + '\n\n' + body);
    }

    function focusFirstInvalidField(form) {
        if (!form) {
            return;
        }

        var invalidField = form.querySelector('[aria-invalid="true"], :invalid');
        if (invalidField && typeof invalidField.focus === 'function') {
            if (typeof invalidField.scrollIntoView === 'function') {
                invalidField.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });
            }

            invalidField.focus();
        }
    }

    window.PluginVehicleschedulerFormFeedback = {
        clearFieldError: clearFieldError,
        countInvalidFields: countInvalidFields,
        ensureFormAlert: ensureFormAlert,
        focusFirstInvalidField: focusFirstInvalidField,
        syncFormSummary: syncFormSummary,
        setFieldError: setFieldError,
        showFormAlert: showFormAlert,
        showValidationDialog: showValidationDialog
    };
})();
