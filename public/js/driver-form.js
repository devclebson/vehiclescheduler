(function () {
    'use strict';

    let feedback = globalThis.PluginVehicleschedulerFormFeedback || null;
    let root = document.querySelector('[data-vs-driver-form]');
    let form = document.querySelector('form[action*="driver.form.php"]');

    if (!form) {
        return;
    }

    let alertBox = root ? root.querySelector('[data-driver-validation]') : null;
    if (!alertBox && feedback) {
        alertBox = feedback.ensureFormAlert(form, 'vs-form-feedback');
    }

    let userField = form.querySelector('[name="users_id"]');
    let categoryFields = Array.prototype.slice.call(form.querySelectorAll('[name="cnh_category[]"]'));
    let expiryField = form.querySelector('[name="cnh_expiry"]');
    let phoneField = form.querySelector('[name="contact_phone"]');

    function setError(field, message) {
        if (!field) {
            return;
        }

        if (feedback) {
            feedback.setFieldError(field, message);
            return;
        }

        field.setCustomValidity(message || '');
        field.setAttribute('aria-invalid', message ? 'true' : 'false');
    }

    function showAlert(message) {
        if (feedback) {
            if (message) {
                feedback.showFormAlert(alertBox, message);
            } else {
                feedback.syncFormSummary(form, alertBox);
            }
            return;
        }

        if (alertBox) {
            alertBox.textContent = message || '';
            alertBox.toggleAttribute('hidden', !message);
        }
    }

    function validate() {
        let message = '';
        let phoneDigits = phoneField ? phoneField.value.replaceAll(/\D+/g, '') : '';

        setError(userField, '');
        categoryFields.forEach(function (field) {
            setError(field, '');
        });
        setError(expiryField, '');
        setError(phoneField, '');

        if (userField && !userField.value) {
            message = 'Selecione o usuario GLPI vinculado ao motorista.';
            setError(userField, message);
        } else if (categoryFields.length > 0 && !categoryFields.some(function (field) { return field.checked; })) {
            message = 'Selecione ao menos uma categoria de CNH.';
            categoryFields.forEach(function (field) {
                setError(field, message);
            });
        } else if (expiryField && !expiryField.value) {
            message = 'Informe a data de vencimento da CNH.';
            setError(expiryField, message);
        } else if (phoneField && phoneDigits !== '' && phoneDigits.length < 10) {
            message = 'Informe um telefone valido com DDD ou deixe o campo em branco.';
            setError(phoneField, message);
        }

        showAlert(message);
        return message === '';
    }

    function syncCategoryCards() {
        let selectedCount = categoryFields.filter(function (field) {
            return field.checked;
        }).length;

        categoryFields.forEach(function (field) {
            let card = field.closest('.vs-driver-category-option');
            if (!card) {
                return;
            }

            card.classList.toggle('is-selected', field.checked);
            card.setAttribute('aria-checked', field.checked ? 'true' : 'false');
            card.dataset.selectedCount = String(selectedCount);
        });
    }

    [userField, expiryField, phoneField].forEach(function (field) {
        if (!field) {
            return;
        }

        field.addEventListener('change', validate);
        field.addEventListener('input', validate);
    });

    categoryFields.forEach(function (field) {
        field.addEventListener('change', validate);
        field.addEventListener('change', syncCategoryCards);
    });

    form.addEventListener('submit', function (event) {
        if (!validate()) {
            event.preventDefault();

            if (feedback) {
                feedback.focusFirstInvalidField(form);
            }
        }
    });

    validate();
    syncCategoryCards();
})();
