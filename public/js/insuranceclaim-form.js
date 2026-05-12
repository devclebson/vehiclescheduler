(function () {
    'use strict';

    var feedback = window.PluginVehicleschedulerFormFeedback || null;
    var root = document.querySelector('[data-vs-claim-form]');

    if (!root) {
        return;
    }

    var form = root.querySelector('[data-vs-claim-form-body]');
    var alertBox = root.querySelector('[data-claim-validation]');
    var vehicleField = root.querySelector('[name="plugin_vehiclescheduler_vehicles_id"]');
    var openingField = root.querySelector('[name="opening_date"]');
    var closingField = root.querySelector('[name="closing_date"]');
    var estimatedField = root.querySelector('[name="estimated_value"]');
    var approvedField = root.querySelector('[name="approved_value"]');

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
        var message = '';
        var openingDate = openingField && openingField.value ? new Date(openingField.value + 'T00:00:00') : null;
        var closingDate = closingField && closingField.value ? new Date(closingField.value + 'T00:00:00') : null;
        var estimatedValue = estimatedField ? Number(estimatedField.value || 0) : 0;
        var approvedValue = approvedField ? Number(approvedField.value || 0) : 0;

        setError(vehicleField, '');
        setError(openingField, '');
        setError(closingField, '');
        setError(estimatedField, '');
        setError(approvedField, '');

        if (vehicleField && !vehicleField.value) {
            message = 'Selecione o veiculo relacionado ao sinistro.';
            setError(vehicleField, message);
        } else if (openingField && !openingField.value) {
            message = 'Informe a data de abertura do sinistro.';
            setError(openingField, message);
        } else if (openingDate && closingDate && closingDate < openingDate) {
            message = 'A data de fechamento nao pode ser anterior a data de abertura.';
            setError(closingField, message);
        } else if (estimatedField && estimatedField.value !== '' && estimatedValue < 0) {
            message = 'O valor estimado nao pode ser negativo.';
            setError(estimatedField, message);
        } else if (approvedField && approvedField.value !== '' && approvedValue < 0) {
            message = 'O valor aprovado nao pode ser negativo.';
            setError(approvedField, message);
        }

        showAlert(message);
        return message === '';
    }

    [vehicleField, openingField, closingField, estimatedField, approvedField].forEach(function (field) {
        if (!field) {
            return;
        }

        field.addEventListener('change', validate);
        field.addEventListener('input', validate);
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            if (!validate()) {
                event.preventDefault();

                if (feedback) {
                    feedback.focusFirstInvalidField(form);
                }
            }
        });
    }

    validate();
})();
