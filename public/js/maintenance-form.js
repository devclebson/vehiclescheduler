(function () {
    'use strict';

    var feedback = window.PluginVehicleschedulerFormFeedback || null;
    var root = document.querySelector('[data-vs-maintenance-form]');

    if (!root) {
        return;
    }

    var form = root.querySelector('[data-vs-maintenance-form-body]');
    var alertBox = root.querySelector('[data-maintenance-validation]');
    var vehicleField = root.querySelector('[name="plugin_vehiclescheduler_vehicles_id"]');
    var scheduledField = root.querySelector('[name="scheduled_date"]');
    var completionField = root.querySelector('[name="completion_date"]');
    var costField = root.querySelector('[name="cost"]');
    var mileageField = root.querySelector('[name="mileage"]');

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
        var scheduledDate = scheduledField && scheduledField.value ? new Date(scheduledField.value + 'T00:00:00') : null;
        var completionDate = completionField && completionField.value ? new Date(completionField.value + 'T00:00:00') : null;
        var costValue = costField ? Number(costField.value || 0) : 0;
        var mileageValue = mileageField ? Number(mileageField.value || 0) : 0;

        setError(vehicleField, '');
        setError(scheduledField, '');
        setError(completionField, '');
        setError(costField, '');
        setError(mileageField, '');

        if (vehicleField && !vehicleField.value) {
            message = 'Selecione o veiculo da manutencao.';
            setError(vehicleField, message);
        } else if (scheduledDate && completionDate && completionDate < scheduledDate) {
            message = 'A data de conclusao nao pode ser anterior a data agendada.';
            setError(completionField, message);
        } else if (costField && costField.value !== '' && costValue < 0) {
            message = 'O custo da manutencao nao pode ser negativo.';
            setError(costField, message);
        } else if (mileageField && mileageField.value !== '' && mileageValue < 0) {
            message = 'A quilometragem informada nao pode ser negativa.';
            setError(mileageField, message);
        }

        showAlert(message);
        return message === '';
    }

    [vehicleField, scheduledField, completionField, costField, mileageField].forEach(function (field) {
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
