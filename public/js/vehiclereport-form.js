(function () {
    'use strict';

    var feedback = window.PluginVehicleschedulerFormFeedback || null;
    var root = document.querySelector('[data-vs-vehiclereport-form]');
    var form = document.querySelector('form[action*="vehiclereport.form.php"]');

    if (!form) {
        return;
    }

    var alertBox = root ? root.querySelector('[data-vehiclereport-validation]') : null;
    if (!alertBox && feedback) {
        alertBox = feedback.ensureFormAlert(form, 'vs-form-feedback');
    }

    var vehicleField = form.querySelector('[name="plugin_vehiclescheduler_vehicles_id"]');
    var reportTypeField = form.querySelector('[name="report_type"]');
    var reportDateField = form.querySelector('[name="report_date"]');
    var phoneField = form.querySelector('[name="contact_phone"]');
    var descriptionField = form.querySelector('[name="description"]');

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
        var reportDate = reportDateField ? parseDate(reportDateField.value) : null;
        var phoneDigits = phoneField ? phoneField.value.replace(/\D+/g, '') : '';

        setError(vehicleField, '');
        setError(reportTypeField, '');
        setError(reportDateField, '');
        setError(phoneField, '');
        setError(descriptionField, '');

        if (vehicleField && !vehicleField.value) {
            message = 'Selecione o veículo relacionado ao relatório.';
            setError(vehicleField, message);
        } else if (reportTypeField && !reportTypeField.value) {
            message = 'Selecione o tipo do relatório.';
            setError(reportTypeField, message);
        } else if (reportDateField && !reportDateField.value) {
            message = 'Informe a data e hora do relatório.';
            setError(reportDateField, message);
        } else if (reportDateField && !reportDate) {
            message = 'A data/hora do relatório está fora do padrão esperado.';
            setError(reportDateField, message);
        } else if (descriptionField && !descriptionField.value.trim()) {
            message = 'Descreva a ocorrência antes de salvar o relatório.';
            setError(descriptionField, message);
        } else if (phoneField && phoneDigits !== '' && phoneDigits.length < 10) {
            message = 'Informe um telefone válido com DDD ou deixe o campo em branco.';
            setError(phoneField, message);
        }

        showAlert(message);
        return message === '';
    }

    [vehicleField, reportTypeField, reportDateField, phoneField, descriptionField].forEach(function (field) {
        if (!field) {
            return;
        }

        field.addEventListener('change', validate);
        field.addEventListener('input', validate);
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
})();
