(function () {
    'use strict';

    var feedback = window.PluginVehicleschedulerFormFeedback || null;
    var form = document.querySelector('form[action*="vehicle.form.php"]');

    if (!form) {
        return;
    }

    var alertBox = feedback ? feedback.ensureFormAlert(form, 'vs-form-feedback') : null;
    var nameField = form.querySelector('[name="name"]');
    var plateField = form.querySelector('[name="plate"]');
    var yearField = form.querySelector('[name="year"]');
    var seatsField = form.querySelector('[name="seats"]');
    var requiredCnhField = form.querySelector('[name="required_cnh_category"]');

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

    function normalizePlate(value) {
        return String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    }

    function isValidPlate(value) {
        return /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/.test(value);
    }

    function validate() {
        var message = '';
        var normalizedPlate = normalizePlate(plateField ? plateField.value : '');
        var currentYear = new Date().getFullYear();
        var yearValue = yearField ? Number(yearField.value || 0) : 0;
        var seatsValue = seatsField ? Number(seatsField.value || 0) : 0;

        setError(nameField, '');
        setError(plateField, '');
        setError(yearField, '');
        setError(seatsField, '');
        setError(requiredCnhField, '');

        if (plateField && plateField.value) {
            plateField.value = normalizedPlate;
        }

        if (nameField && !nameField.value.trim()) {
            message = 'Informe o nome do veiculo.';
            setError(nameField, message);
        } else if (plateField && normalizedPlate === '') {
            message = 'Informe a placa do veiculo.';
            setError(plateField, message);
        } else if (plateField && !isValidPlate(normalizedPlate)) {
            message = 'Informe uma placa valida no padrao brasileiro.';
            setError(plateField, message);
        } else if (yearField && (yearValue < 1900 || yearValue > 2100 || yearValue > currentYear + 1)) {
            message = 'Informe um ano valido para o veiculo.';
            setError(yearField, message);
        } else if (seatsField && (seatsValue < 1 || seatsValue > 100)) {
            message = 'A capacidade de passageiros deve ficar entre 1 e 100.';
            setError(seatsField, message);
        } else if (requiredCnhField && !requiredCnhField.value) {
            message = 'Selecione a categoria de CNH exigida para a viatura.';
            setError(requiredCnhField, message);
        }

        showAlert(message);
        return message === '';
    }

    function syncCnhCards() {
        var cards = Array.prototype.slice.call(form.querySelectorAll('[name="required_cnh_category"]'))
            .map(function (field) {
                return field.closest('.vs-vehicle-cnh-option');
            })
            .filter(function (card) {
                return !!card;
            });

        cards.forEach(function (card) {
            var input = card.querySelector('[name="required_cnh_category"]');
            var checked = !!(input && input.checked);
            card.classList.toggle('is-selected', checked);
            card.setAttribute('aria-checked', checked ? 'true' : 'false');
        });
    }

    function removeActionsDropdown() {
        var dropdowns = Array.prototype.slice.call(
            document.querySelectorAll('.dropdown-menu, .dropdown-menu-end, [role="menu"]')
        );

        dropdowns.forEach(function (menu) {
            var text = String(menu.textContent || '').replace(/\s+/g, ' ').trim();

            if (
                text.indexOf('Alterar Comentário') === -1 &&
                text.indexOf('Alterar ComentÃ¡rio') === -1
            ) {
                return;
            }

            var container = menu.closest('.dropdown') || menu.closest('.btn-group') || menu.parentElement;

            if (container) {
                container.remove();
            }
        });

        Array.prototype.slice.call(document.querySelectorAll('button, a')).forEach(function (button) {
            var label = String(button.textContent || '').replace(/\s+/g, ' ').trim();
            var title = String(button.getAttribute('title') || '').trim();
            var ariaLabel = String(button.getAttribute('aria-label') || '').trim();

            var isActionsButton =
                label === 'Ações' ||
                label.indexOf('Ações') === 0 ||
                label === 'AÃ§Ãµes' ||
                label.indexOf('AÃ§Ãµes') === 0 ||
                title === 'Ações' ||
                title === 'AÃ§Ãµes' ||
                ariaLabel === 'Ações' ||
                ariaLabel === 'AÃ§Ãµes';

            if (isActionsButton) {
                var parent = button.closest('.dropdown') || button.closest('.btn-group') || button.parentElement;

                if (parent) {
                    parent.remove();
                } else {
                    button.remove();
                }
            }
        });
    }

    function watchActionsDropdown() {
        removeActionsDropdown();

        if (!window.MutationObserver || !document.body) {
            return;
        }

        var observer = new MutationObserver(function () {
            removeActionsDropdown();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    [nameField, plateField, yearField, seatsField, requiredCnhField].forEach(function (field) {
        if (!field) {
            return;
        }

        field.addEventListener('change', validate);
        field.addEventListener('input', validate);
    });

    if (requiredCnhField) {
        form.querySelectorAll('[name="required_cnh_category"]').forEach(function (field) {
            field.addEventListener('change', syncCnhCards);
        });
    }

    form.addEventListener('submit', function (event) {
        if (!validate()) {
            event.preventDefault();

            if (feedback) {
                feedback.focusFirstInvalidField(form);
            }
        }
    });

    watchActionsDropdown();
    validate();
    syncCnhCards();
})();
