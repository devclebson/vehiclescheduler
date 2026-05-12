(function () {
    'use strict';

    var formFeedback = window.PluginVehicleschedulerFormFeedback || null;

    /**
     * Parse a date-time string from native inputs or flatpickr values.
     *
     * @param {string} value
     * @returns {Date|null}
     */
    function parseDateValue(value) {
        if (!value) {
            return null;
        }

        var normalized = String(value).trim().replace('T', ' ');
        if (normalized === '') {
            return null;
        }

        var date = new Date(normalized);
        return Number.isNaN(date.getTime()) ? null : date;
    }

    /**
     * Toggle the inline validation note for invalid date ranges.
     *
     * @param {HTMLElement|null} container
     * @param {string} message
     */
    function setValidationMessage(container, message, form) {
        if (
            formFeedback
            && typeof formFeedback.syncFormSummary === 'function'
            && typeof formFeedback.showFormAlert === 'function'
        ) {
            if (message) {
                formFeedback.showFormAlert(container, message);
            } else {
                formFeedback.syncFormSummary(form, container);
            }
            return;
        }

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

    /**
     * Mirror custom validity between the original field and the flatpickr alt input.
     *
     * @param {HTMLInputElement|null} input
     * @param {object|null} picker
     * @param {string} message
     */
    function syncFieldValidity(input, picker, message) {
        if (!input) {
            return;
        }

        if (formFeedback && typeof formFeedback.setFieldError === 'function') {
            formFeedback.setFieldError(input, message || '');
        } else {
            input.setCustomValidity(message || '');
            input.setAttribute('aria-invalid', message ? 'true' : 'false');
        }

        if (picker && picker.altInput) {
            if (formFeedback && typeof formFeedback.setFieldError === 'function') {
                formFeedback.setFieldError(picker.altInput, message || '');
            } else {
                picker.altInput.setCustomValidity(message || '');
                picker.altInput.setAttribute('aria-invalid', message ? 'true' : 'false');
            }
        }
    }

    /**
     * Disable or enable the target field and its flatpickr UI.
     *
     * @param {HTMLInputElement} input
     * @param {object|null} picker
     * @param {boolean} disabled
     */
    function setPickerDisabled(input, picker, disabled) {
        input.disabled = disabled;

        if (picker && picker.altInput) {
            picker.altInput.disabled = disabled;

            if (picker.altInput.parentElement) {
                picker.altInput.parentElement.classList.toggle('vs-field-disabled', disabled);
            }
        }
    }

    /**
     * Bootstraps flatpickr and range validation for reservation dates.
     */
    function initDateRules() {
        var beginInput = document.querySelector('[name="begin_date"]');
        var endInput = document.querySelector('[name="end_date"]');
        var form = document.querySelector('#vs-schedule-main-form');
        var validationNote = document.querySelector('[data-vs-date-validation]');

        if (!beginInput || !endInput || !form) {
            return;
        }

        if (beginInput.dataset.vsDateRulesBound === '1') {
            return;
        }

        beginInput.dataset.vsDateRulesBound = '1';

        var canEditDates = !beginInput.hasAttribute('readonly') && !endInput.hasAttribute('readonly');
        var beginPicker = null;
        var endPicker = null;

        if (typeof window.flatpickr === 'function') {
            beginPicker = window.flatpickr(beginInput, {
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',
                altInput: true,
                altFormat: 'd/m/Y H:i',
                allowInput: true,
                disableMobile: true,
                minuteIncrement: 5,
                minDate: 'today',
                clickOpens: canEditDates
            });

            endPicker = window.flatpickr(endInput, {
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',
                altInput: true,
                altFormat: 'd/m/Y H:i',
                allowInput: true,
                disableMobile: true,
                minuteIncrement: 5,
                clickOpens: canEditDates
            });
        }

        function getBeginDate() {
            if (beginPicker && beginPicker.selectedDates.length > 0) {
                return beginPicker.selectedDates[0];
            }

            return parseDateValue(beginInput.value);
        }

        function getEndDate() {
            if (endPicker && endPicker.selectedDates.length > 0) {
                return endPicker.selectedDates[0];
            }

            return parseDateValue(endInput.value);
        }

        function syncEndAvailability() {
            var beginDate = getBeginDate();
            var shouldDisable = !canEditDates || !beginDate;

            if (endPicker) {
                endPicker.set('clickOpens', !shouldDisable);
                endPicker.set('minDate', beginDate || null);
                endPicker.redraw();
            } else {
                endInput.min = beginDate ? beginInput.value : '';
            }

            setPickerDisabled(endInput, endPicker, shouldDisable);
        }

        function validateDateRange() {
            var beginDate = getBeginDate();
            var endDate = getEndDate();
            var message = '';

            if (!beginInput.value) {
                message = 'Informe a data e hora de saida.';
            } else if (!beginDate) {
                message = 'A data/hora de saida esta fora do padrao esperado.';
            } else if (!endInput.value) {
                message = 'Informe a data e hora de retorno.';
            } else if (!endDate) {
                message = 'A data/hora de retorno esta fora do padrao esperado.';
            } else
            if (beginDate && endDate && beginDate >= endDate) {
                message = 'A data/hora de saida deve ser menor que a data/hora de retorno.';
            }

            syncFieldValidity(beginInput, beginPicker, message);
            syncFieldValidity(endInput, endPicker, message);
            setValidationMessage(validationNote, message, form);

            return message === '';
        }

        function refreshDateRules() {
            if (beginPicker) {
                beginPicker.set('minDate', 'today');
                beginPicker.redraw();
            } else {
                beginInput.min = new Date().toISOString().slice(0, 16);
            }

            syncEndAvailability();
            validateDateRange();
        }

        beginInput.addEventListener('change', refreshDateRules);
        endInput.addEventListener('change', validateDateRange);

        if (beginPicker) {
            beginPicker.config.onChange.push(refreshDateRules);
            beginPicker.config.onClose.push(refreshDateRules);
        }

        if (endPicker) {
            endPicker.config.onChange.push(validateDateRange);
            endPicker.config.onClose.push(validateDateRange);
        }

        form.addEventListener('submit', function (event) {
            refreshDateRules();

            if (!validateDateRange()) {
                event.preventDefault();

                if (formFeedback && typeof formFeedback.focusFirstInvalidField === 'function') {
                    formFeedback.focusFirstInvalidField(form);
                }
            }
        });

        refreshDateRules();
    }

    /**
     * Controls the rejection justification panel.
     */
    function initApprovalActions() {
        var toggleButton = document.querySelector('[data-vs-toggle-rejection]');
        var cancelButton = document.querySelector('[data-vs-cancel-rejection]');
        var rejectionForm = document.querySelector('[data-vs-rejection-form]');

        if (!toggleButton || !rejectionForm) {
            return;
        }

        if (rejectionForm.dataset.vsApprovalBound === '1') {
            return;
        }

        rejectionForm.dataset.vsApprovalBound = '1';

        function showRejectionForm() {
            rejectionForm.removeAttribute('hidden');

            var textarea = rejectionForm.querySelector('textarea[name="rejection_justification"]');
            if (textarea) {
                textarea.focus();
            }
        }

        function hideRejectionForm() {
            rejectionForm.setAttribute('hidden', 'hidden');
        }

        toggleButton.addEventListener('click', function () {
            if (rejectionForm.hasAttribute('hidden')) {
                showRejectionForm();
                return;
            }

            hideRejectionForm();
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                hideRejectionForm();
            });
        }
    }

    function initDriverCompatibility() {
        var form = document.querySelector('#vs-schedule-main-form');
        var vehicleField = form ? form.querySelector('[name="plugin_vehiclescheduler_vehicles_id"]') : null;
        var driverField = form ? form.querySelector('[name="plugin_vehiclescheduler_drivers_id"]') : null;
        var compatibilityNode = document.querySelector('[data-vs-schedule-compatibility]');
        var vehicleNote = document.querySelector('[data-vs-vehicle-compatibility-note]');
        var driverNote = document.querySelector('[data-vs-driver-compatibility-note]');
        var driverQuickList = document.querySelector('[data-vs-driver-quick-list]');

        if (!form || !vehicleField || !driverField || !compatibilityNode) {
            return;
        }

        if (form.dataset.vsCompatibilityBound === '1') {
            return;
        }

        form.dataset.vsCompatibilityBound = '1';

        function parseMap(attributeName) {
            var rawValue = compatibilityNode.getAttribute(attributeName) || '{}';

            try {
                return JSON.parse(rawValue);
            } catch (error) {
                return {};
            }
        }

        var vehicleMap = parseMap('data-vs-vehicle-map');
        var driverMap = parseMap('data-vs-driver-map');

        function setInlineNote(node, message, tone) {
            if (!node) {
                return;
            }

            node.classList.remove('vs-inline-help--warning', 'vs-inline-help--success');

            if (!message) {
                node.textContent = '';
                node.setAttribute('hidden', 'hidden');
                return;
            }

            if (tone) {
                node.classList.add('vs-inline-help--' + tone);
            }

            node.textContent = message;
            node.removeAttribute('hidden');
        }

        function refreshSelectUi(field) {
            if (!field) {
                return;
            }

            if (window.jQuery && typeof window.jQuery.fn !== 'undefined') {
                window.jQuery(field).trigger('change.select2');
            }
        }

        function setDriverValue(value) {
            if (!driverField) {
                return;
            }

            if (window.jQuery && typeof window.jQuery.fn !== 'undefined') {
                window.jQuery(driverField).val(value).trigger('change');
                return;
            }

            driverField.value = value;
            var changeEvent = new Event('change', { bubbles: true });
            driverField.dispatchEvent(changeEvent);
        }

        function getVehicleMeta() {
            return vehicleMap[String(vehicleField.value || '')] || null;
        }

        function getDriverMeta(driverId) {
            return driverMap[String(driverId || '')] || null;
        }

        function isDriverCompatible(requiredCategory, driverId) {
            var driverMeta = getDriverMeta(driverId);

            if (!requiredCategory) {
                return true;
            }

            if (!driverMeta || !Array.isArray(driverMeta.categories)) {
                return false;
            }

            var categories = Array.isArray(driverMeta.qualifiedCategories) ? driverMeta.qualifiedCategories : driverMeta.categories;
            return Array.isArray(categories) && categories.indexOf(requiredCategory) !== -1;
        }

        function getCompatibleDriverCount(requiredCategory) {
            var count = 0;

            Array.prototype.forEach.call(driverField.options, function (option) {
                if (!option.value) {
                    return;
                }

                if (isDriverCompatible(requiredCategory, option.value)) {
                    count += 1;
                }
            });

            return count;
        }

        function renderCategoryBadges(driverMeta) {
            var categories = driverMeta && Array.isArray(driverMeta.qualifiedCategories)
                ? driverMeta.qualifiedCategories
                : [];

            return categories.map(function (category) {
                return '<span class="vs-schedule-driver-badge">' + String(category) + '</span>';
            }).join('');
        }

        function renderDriverQuickList(requiredCategory) {
            if (!driverQuickList) {
                return;
            }

            var selectedDriverId = String(driverField.value || '');
            var eligibleDrivers = Object.keys(driverMap)
                .map(function (driverId) {
                    return driverMap[driverId];
                })
                .filter(function (driverMeta) {
                    if (!driverMeta) {
                        return false;
                    }

                    if (!requiredCategory) {
                        return true;
                    }

                    return isDriverCompatible(requiredCategory, driverMeta.id);
                });

            driverQuickList.innerHTML = '';

            if (!requiredCategory) {
                driverQuickList.hidden = true;
                return;
            }

            if (eligibleDrivers.length === 0) {
                driverQuickList.hidden = false;
                driverQuickList.innerHTML = '<div class="vs-schedule-driver-empty">Nenhum motorista compatÃ­vel encontrado para esta viatura.</div>';
                return;
            }

            var header = document.createElement('div');
            header.className = 'vs-schedule-driver-quick-list__head';
            header.innerHTML = '<span>Motoristas elegÃ­veis</span><span>' + eligibleDrivers.length + '</span>';
            driverQuickList.appendChild(header);

            var grid = document.createElement('div');
            grid.className = 'vs-schedule-driver-quick-grid';

            eligibleDrivers.forEach(function (driverMeta) {
                var card = document.createElement('button');
                card.type = 'button';
                card.className = 'vs-schedule-driver-card';
                if (String(driverMeta.id) === selectedDriverId) {
                    card.classList.add('is-selected');
                }

                card.setAttribute('data-driver-id', String(driverMeta.id));
                card.innerHTML = [
                    '<span class="vs-schedule-driver-card__name">' + String(driverMeta.name || 'Motorista') + '</span>',
                    '<span class="vs-schedule-driver-card__badges">' + renderCategoryBadges(driverMeta) + '</span>'
                ].join('');

                card.addEventListener('click', function () {
                    setDriverValue(String(driverMeta.id));
                    renderDriverQuickList(requiredCategory);
                    syncCompatibilityFeedback();
                });

                grid.appendChild(card);
            });

            driverQuickList.appendChild(grid);
            driverQuickList.hidden = false;
        }

        function syncDriverOptions(resetSelection) {
            var vehicleMeta = getVehicleMeta();
            var requiredCategory = vehicleMeta ? String(vehicleMeta.requiredCategory || '') : '';
            var currentDriverId = String(driverField.value || '');

            if (resetSelection && currentDriverId && !isDriverCompatible(requiredCategory, currentDriverId)) {
                driverField.value = '';
                currentDriverId = '';
            }

            Array.prototype.forEach.call(driverField.options, function (option) {
                if (!option.value) {
                    return;
                }

                var compatible = isDriverCompatible(requiredCategory, option.value);
                var keepVisible = option.value === currentDriverId;

                option.disabled = !!requiredCategory && !compatible && !keepVisible;
                option.hidden = !!requiredCategory && !compatible && !keepVisible;
            });

            refreshSelectUi(driverField);
        }

        function syncCompatibilityFeedback() {
            var vehicleMeta = getVehicleMeta();
            var requiredCategory = vehicleMeta ? String(vehicleMeta.requiredCategory || '') : '';
            var driverId = String(driverField.value || '');

            if (!vehicleMeta) {
                setInlineNote(vehicleNote, '', '');
                setInlineNote(driverNote, '', '');
                if (formFeedback && typeof formFeedback.clearFieldError === 'function') {
                    formFeedback.clearFieldError(driverField);
                }
                return true;
            }

            setInlineNote(
                vehicleNote,
                'CNH exigida para esta viatura: ' + (vehicleMeta.requiredLabel || requiredCategory) + '. Carro aceita B ou D.',
                'success'
            );

            if (!driverId) {
                var compatibleCount = getCompatibleDriverCount(requiredCategory);
                if (compatibleCount > 0) {
                    setInlineNote(
                        driverNote,
                        compatibleCount + ' motorista(s) elegivel(is) para esta viatura.',
                        'success'
                    );
                } else {
                    setInlineNote(
                        driverNote,
                        'Nenhum motorista ativo/aprovado atende a regra desta viatura.',
                        'warning'
                    );
                }

                if (formFeedback && typeof formFeedback.clearFieldError === 'function') {
                    formFeedback.clearFieldError(driverField);
                }

                return true;
            }

            if (!isDriverCompatible(requiredCategory, driverId)) {
                if (formFeedback && typeof formFeedback.setFieldError === 'function') {
                    formFeedback.setFieldError(
                        driverField,
                        'O motorista selecionado nao possui CNH compativel com a viatura.'
                    );
                }

                setInlineNote(
                    driverNote,
                    'Compatibilidade invalida. Moto exige A, carro aceita B ou D, e caminhao/van exige D.',
                    'warning'
                );

                return false;
            }

            if (formFeedback && typeof formFeedback.clearFieldError === 'function') {
                formFeedback.clearFieldError(driverField);
            }

            var driverMeta = getDriverMeta(driverId);
            var driverCategories = driverMeta && driverMeta.qualifiedCategoriesLabel
                ? driverMeta.qualifiedCategoriesLabel
                : requiredCategory;

            setInlineNote(
                driverNote,
                'Motorista compativel. Categorias habilitadas: ' + driverCategories + '.',
                'success'
            );

            return true;
        }

        function refreshCompatibility(resetSelection) {
            syncDriverOptions(resetSelection);
            renderDriverQuickList(getVehicleMeta() ? String(getVehicleMeta().requiredCategory || '') : '');
            syncCompatibilityFeedback();
        }

        vehicleField.addEventListener('change', function () {
            refreshCompatibility(true);
        });

        driverField.addEventListener('change', function () {
            syncCompatibilityFeedback();
        });

        form.addEventListener('submit', function (event) {
            refreshCompatibility(false);

            if (!syncCompatibilityFeedback()) {
                event.preventDefault();

                if (formFeedback && typeof formFeedback.focusFirstInvalidField === 'function') {
                    formFeedback.focusFirstInvalidField(form);
                }
            }
        });

        refreshCompatibility(false);
    }

    function init() {
        initDateRules();
        initApprovalActions();
        initDriverCompatibility();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

