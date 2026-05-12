(function () {
    'use strict';

    var feedback = window.PluginVehicleschedulerFormFeedback || null;
    var root = document.querySelector('[data-vs-incident-form]');

    if (!root) {
        return;
    }

    var form = root.closest('form') || root.querySelector('form');
    var typeSelect = root.querySelector('[data-incident-type]');
    var alertBox = root.querySelector('[data-incident-alert]');
    var validationBox = root.querySelector('[data-incident-validation]');
    var phoneInput = root.querySelector('[data-incident-phone]');
    var vehicleField = root.querySelector('[name="plugin_vehiclescheduler_vehicles_id"]');
    var incidentDateField = root.querySelector('[name="incident_date"]');
    var descriptionField = root.querySelector('[name="description"]');
    var severityTypes = ['1', '3'];

    function updateSeverity() {
        if (!typeSelect || !alertBox) {
            return;
        }

        var isSevere = severityTypes.indexOf(typeSelect.value) !== -1;
        alertBox.classList.toggle('vs-incident-form-severity', isSevere);
    }

    function formatPhone(value) {
        var digits = value.replace(/\D+/g, '').slice(0, 11);

        if (digits.length <= 2) {
            return digits;
        }

        if (digits.length <= 7) {
            return '(' + digits.slice(0, 2) + ') ' + digits.slice(2);
        }

        if (digits.length <= 10) {
            return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 6) + '-' + digits.slice(6);
        }

        return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 7) + '-' + digits.slice(7);
    }

    function parseDate(value) {
        if (!value) {
            return null;
        }

        var parsed = new Date(String(value).replace('T', ' '));
        return Number.isNaN(parsed.getTime()) ? null : parsed;
    }

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

    function showValidation(message) {
        if (feedback) {
            if (message) {
                feedback.showFormAlert(validationBox, message);
            } else {
                feedback.syncFormSummary(form, validationBox);
            }
            return;
        }

        if (validationBox) {
            validationBox.textContent = message || '';
            validationBox.toggleAttribute('hidden', !message);
        }
    }

    function validateForm() {
        var message = '';
        var incidentDate = incidentDateField ? parseDate(incidentDateField.value) : null;
        var phoneDigits = phoneInput ? phoneInput.value.replace(/\D+/g, '') : '';
        var now = new Date();

        setError(vehicleField, '');
        setError(incidentDateField, '');
        setError(descriptionField, '');
        setError(phoneInput, '');

        if (vehicleField && !vehicleField.value) {
            message = 'Selecione o veiculo relacionado ao incidente.';
            setError(vehicleField, message);
        } else if (incidentDateField && !incidentDateField.value) {
            message = 'Informe a data e hora do incidente.';
            setError(incidentDateField, message);
        } else if (incidentDateField && !incidentDate) {
            message = 'A data/hora do incidente esta fora do padrao esperado.';
            setError(incidentDateField, message);
        } else if (incidentDate && incidentDate > now) {
            message = 'A data/hora do incidente nao pode estar no futuro.';
            setError(incidentDateField, message);
        } else if (descriptionField && !descriptionField.value.trim()) {
            message = 'Descreva o ocorrido antes de salvar o incidente.';
            setError(descriptionField, message);
        } else if (phoneInput && phoneDigits !== '' && phoneDigits.length < 10) {
            message = 'Informe um telefone valido com DDD ou deixe o campo em branco.';
            setError(phoneInput, message);
        }

        showValidation(message);
        return message === '';
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', function () {
            updateSeverity();
            validateForm();
        });
        updateSeverity();
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            phoneInput.value = formatPhone(phoneInput.value);
            validateForm();
        });
    }

    [vehicleField, incidentDateField, descriptionField].forEach(function (field) {
        if (!field) {
            return;
        }

        field.addEventListener('change', validateForm);
        field.addEventListener('input', validateForm);
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!validateForm()) {
                event.preventDefault();

                if (feedback) {
                    feedback.focusFirstInvalidField(form);
                }
            }
        });
    }

    validateForm();
})();
