// public/js/driver-grid.js
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    /**
     * Root container for the driver grid page.
     *
     * The script stops immediately when the page is not the driver grid view.
     */
    let root = document.querySelector('[data-vs-driver-grid]');

    if (!root) {
        return;
    }

    /**
     * Cached UI references used by the client-side filtering workflow.
     */
    let rows = Array.prototype.slice.call(root.querySelectorAll('[data-driver-row]'));
    let searchField = root.querySelector('[data-driver-filter-search]');
    let activeField = root.querySelector('[data-driver-filter-active]');
    let expiryField = root.querySelector('[data-driver-filter-expiry]');
    let clearButton = root.querySelector('[data-driver-clear-filters]');
    let resultCount = root.querySelector('[data-driver-result-count]');
    let emptyState = root.querySelector('[data-driver-empty]');
    let resultLabel = resultCount && resultCount.dataset.resultLabel
        ? resultCount.dataset.resultLabel
        : 'motoristas';

    /**
     * Normalizes a string for accent-insensitive and case-insensitive matching.
     *
     * @param {*} value Raw value from input or dataset.
     *
     * @returns {string}
     */
    function normalize(value) {
        return (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    /**
     * Checks whether a table row matches the current filter state.
     *
     * @param {HTMLElement} row Driver table row.
     *
     * @returns {boolean}
     */
    function rowMatches(row) {
        let searchValue = normalize(searchField ? searchField.value : '');
        let activeValue = activeField ? activeField.value : 'all';
        let expiryValue = expiryField ? expiryField.value : 'all';

        let rowSearch = normalize(row.dataset.search || '');
        let rowActive = row.dataset.active || '';
        let rowExpiry = row.dataset.expiryStatus || '';

        if (searchValue !== '' && rowSearch.indexOf(searchValue) === -1) {
            return false;
        }

        if (activeValue !== 'all' && rowActive !== activeValue) {
            return false;
        }

        if (expiryValue !== 'all' && rowExpiry !== expiryValue) {
            return false;
        }

        return true;
    }

    /**
     * Updates the visible counter text.
     *
     * @param {number} visibleCount Number of currently visible rows.
     * @param {number} totalCount Total number of rows in the grid.
     *
     * @returns {void}
     */
    function syncCounter(visibleCount, totalCount) {
        if (!resultCount) {
            return;
        }

        if (visibleCount === totalCount) {
            resultCount.textContent = 'Exibindo ' + totalCount + ' ' + resultLabel;
            return;
        }

        resultCount.textContent = 'Exibindo ' + visibleCount + ' de ' + totalCount + ' ' + resultLabel;
    }

    /**
     * Toggles the empty-state message depending on visible rows.
     *
     * @param {number} visibleCount Number of currently visible rows.
     *
     * @returns {void}
     */
    function syncEmptyState(visibleCount) {
        if (!emptyState) {
            return;
        }

        emptyState.hidden = visibleCount !== 0;
    }

    /**
     * Applies all active filters to the table rows.
     *
     * @returns {void}
     */
    function applyFilters() {
        let visibleCount = 0;

        rows.forEach(function (row) {
            let visible = rowMatches(row);

            row.hidden = !visible;

            if (visible) {
                visibleCount += 1;
            }
        });

        syncCounter(visibleCount, rows.length);
        syncEmptyState(visibleCount);
    }

    /**
     * Resets all filter inputs to their default values.
     *
     * @returns {void}
     */
    function clearFilters() {
        if (searchField) {
            searchField.value = '';
        }

        if (activeField) {
            activeField.value = 'all';
        }

        if (expiryField) {
            expiryField.value = 'all';
        }

        applyFilters();
    }

    /**
     * Binds live filtering listeners to supported fields.
     */
    [searchField, activeField, expiryField].forEach(function (field) {
        if (!field) {
            return;
        }

        field.addEventListener('input', applyFilters);
        field.addEventListener('change', applyFilters);
    });

    /**
     * Binds reset behavior to the clear button.
     */
    if (clearButton) {
        clearButton.addEventListener('click', clearFilters);
    }

    /**
     * Applies the initial filter state on page load.
     */
    applyFilters();
});
