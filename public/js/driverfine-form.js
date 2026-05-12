(function () {
    'use strict';

    var severityByLevel = {
        mild: '1',
        medium: '2',
        severe: '3',
        verysevere: '4'
    };

    var severityLabelByValue = {
        '0': 'Sem pontuação',
        '1': 'Leve - 3 pts',
        '2': 'Média - 4 pts',
        '3': 'Grave - 5 pts',
        '4': 'Gravíssima - 7 pts'
    };

    function normalize(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function buildKey(item) {
        return String(item.code || '') + '-' + String(item.split || '');
    }

    function buildSearchText(item) {
        return normalize([
            item.code,
            item.split,
            item.description,
            item.legal_basis,
            item.offender,
            item.severity_label,
            item.authority
        ].join(' '));
    }

    function renderSelected(node, item) {
        if (!node) {
            return;
        }

        if (!item) {
            node.innerHTML = '<span>Nenhuma infração selecionada.</span>';
            return;
        }

        node.innerHTML = ''
            + '<strong>' + escapeHtml(buildKey(item)) + '</strong>'
            + '<span>' + escapeHtml(item.description) + '</span>'
            + '<small>' + escapeHtml(item.legal_basis || 'Sem amparo legal informado')
            + ' · ' + escapeHtml(item.severity_label || 'Sem gravidade') + '</small>';
    }

    function renderResults(resultsNode, items, shouldShowEmpty) {
        if (!resultsNode) {
            return;
        }

        if (!items.length) {
            resultsNode.innerHTML = shouldShowEmpty ? '<div class="vs-renainf-empty">Nenhuma infração encontrada.</div>' : '';
            return;
        }

        resultsNode.innerHTML = items.map(function (item) {
            return ''
                + '<button type="button" class="vs-renainf-option" data-renainf-key="' + escapeHtml(buildKey(item)) + '">'
                + '  <span class="vs-renainf-option__code">' + escapeHtml(buildKey(item)) + '</span>'
                + '  <span class="vs-renainf-option__body">'
                + '    <strong>' + escapeHtml(item.description) + '</strong>'
                + '    <small>' + escapeHtml(item.legal_basis || 'Sem amparo legal') + ' · '
                + escapeHtml(item.offender || 'Infrator não informado') + ' · '
                + escapeHtml(item.severity_label || 'Sem gravidade') + '</small>'
                + '  </span>'
                + '</button>';
        }).join('');
    }

    function renderTrigger(triggerLabel, item, loaded) {
        if (!triggerLabel) {
            return;
        }

        if (item) {
            triggerLabel.textContent = buildKey(item) + ' · ' + item.description;
            return;
        }

        triggerLabel.textContent = loaded ? 'Selecione uma infração RENAINF' : 'Carregando tabela RENAINF...';
    }

    function initForm(form) {
        var catalogUrl = form.getAttribute('data-renainf-catalog-url');
        var search = form.querySelector('[data-renainf-search]');
        var trigger = form.querySelector('[data-renainf-trigger]');
        var triggerLabel = form.querySelector('[data-renainf-trigger-label]');
        var results = form.querySelector('[data-renainf-results]');
        var selected = form.querySelector('[data-renainf-selected]');
        var clear = form.querySelector('[data-renainf-clear]');
        var description = form.querySelector('[data-renainf-description]');
        var severity = form.querySelector('[name="severity"]');
        var severityDisplay = form.querySelector('[data-renainf-severity-display]');
        var code = form.querySelector('[data-renainf-code]');
        var split = form.querySelector('[data-renainf-split]');
        var legal = form.querySelector('[data-renainf-legal]');
        var offender = form.querySelector('[data-renainf-offender]');
        var authority = form.querySelector('[data-renainf-authority]');
        var catalog = [];
        var byKey = {};
        var currentKey = code.value ? code.value + '-' + split.value : '';

        function openResults() {
            if (results) {
                results.classList.add('is-open');
            }

            if (trigger) {
                trigger.setAttribute('aria-expanded', 'true');
            }
        }

        function closeResults() {
            if (results) {
                results.classList.remove('is-open');
            }

            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }
        }

        function setSeverityFromItem(item) {
            var severityValue = item && item.severity_level && severityByLevel[item.severity_level]
                ? severityByLevel[item.severity_level]
                : (item && Number(item.points || 0) === 0 ? '0' : severity.value);

            severity.value = severityValue || '3';

            if (severityDisplay) {
                severityDisplay.textContent = item && item.severity_label
                    ? item.severity_label
                    : (severityLabelByValue[severity.value] || 'Definida pela infração');
            }
        }

        function applyItem(item) {
            if (!item) {
                return;
            }

            code.value = item.code || '';
            split.value = item.split || '';
            legal.value = item.legal_basis || '';
            offender.value = item.offender || '';
            authority.value = item.authority || '';
            currentKey = buildKey(item);

            if (description && !description.value.trim()) {
                description.value = item.description || '';
            } else if (description) {
                description.value = item.description || description.value;
            }

            setSeverityFromItem(item);

            renderSelected(selected, item);
            renderResults(results, [], false);
            renderTrigger(triggerLabel, item, true);
            if (search) {
                search.value = '';
            }
            closeResults();
        }

        function clearItem() {
            code.value = '';
            split.value = '';
            legal.value = '';
            offender.value = '';
            authority.value = '';
            currentKey = '';
            severity.value = '3';
            if (severityDisplay) {
                severityDisplay.textContent = 'Definida pela infração';
            }
            renderSelected(selected, null);
            renderResults(results, [], false);
            renderTrigger(triggerLabel, null, true);
            closeResults();
            if (search) {
                search.value = '';
                search.focus();
            }
        }

        function filterCatalog(forceList) {
            var query = normalize(search ? search.value : '');
            var items = catalog;

            if (query.length < 2) {
                renderResults(results, forceList ? catalog : [], false);
                if (forceList) {
                    openResults();
                }
                return;
            }

            items = catalog.filter(function (item) {
                return item.searchText.indexOf(query) !== -1;
            });

            renderResults(results, items, true);
            openResults();
        }

        if (!catalogUrl || !search || !trigger || !results) {
            return;
        }

        fetch(catalogUrl, { credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Catalog request failed');
                }
                return response.json();
            })
            .then(function (items) {
                catalog = items.map(function (item) {
                    item.searchText = buildSearchText(item);
                    byKey[buildKey(item)] = item;
                    return item;
                });

                if (code.value) {
                    currentKey = code.value + '-' + split.value;
                    renderSelected(selected, byKey[currentKey] || null);
                    setSeverityFromItem(byKey[currentKey] || null);
                } else {
                    renderTrigger(triggerLabel, null, true);
                }
            })
            .catch(function () {
                results.innerHTML = '<div class="vs-renainf-empty">Não foi possível carregar a tabela RENAINF.</div>';
                renderTrigger(triggerLabel, null, true);
            });

        search.addEventListener('input', function () {
            filterCatalog(false);
        });

        search.addEventListener('focus', function () {
            if (search.value.length >= 2) {
                filterCatalog(false);
            }
        });

        results.addEventListener('click', function (event) {
            var option = event.target.closest('[data-renainf-key]');

            if (!option) {
                return;
            }

            applyItem(byKey[option.getAttribute('data-renainf-key')]);
        });

        trigger.addEventListener('click', function () {
            filterCatalog(true);
        });

        if (clear) {
            clear.addEventListener('click', clearItem);
        }

        document.addEventListener('click', function (event) {
            if (!form.contains(event.target)) {
                closeResults();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.slice.call(document.querySelectorAll('[data-vs-driverfine-form]')).forEach(initForm);
    });
}());
