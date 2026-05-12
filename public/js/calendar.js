(function () {
    var modal = document.getElementById('vsCalendarModal');

    if (!modal) {
        return;
    }

    var title = document.getElementById('vsCalendarModalTitle');
    var body = document.getElementById('vsCalendarModalBody');
    var link = document.getElementById('vsCalendarModalLink');
    var closeButtons = document.querySelectorAll('[data-calendar-close]');
    var triggers = document.querySelectorAll('[data-calendar-event]');

    var escapeHtml = function (value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    var formatCurrency = function (value) {
        var number = Number(value || 0);

        return number.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    var getStatusBadge = function (event) {
        var variants = {
            'new': 'vs-calendar-status-badge--new',
            'approved': 'vs-calendar-status-badge--approved',
            'rejected': 'vs-calendar-status-badge--rejected',
            'cancelled': 'vs-calendar-status-badge--cancelled'
        };

        if (!event.status_label) {
            return '';
        }

        var variant = variants[event.status_variant] || 'vs-calendar-status-badge--new';

        return '<span class="vs-calendar-status-badge ' + variant + '">'
            + escapeHtml(event.status_label)
            + '</span>';
    };

    var detailRow = function (labelText, valueHtml) {
        return '<div class="vs-calendar-detail-row">'
            + '<div class="vs-calendar-detail-label">' + escapeHtml(labelText) + '</div>'
            + '<div class="vs-calendar-detail-value">' + valueHtml + '</div>'
            + '</div>';
    };

    var renderBody = function (event) {
        if (event.tipo === 'reserva') {
            return [
                detailRow('Veiculo', escapeHtml(event.veiculo + ' (' + event.placa + ')')),
                detailRow('Solicitante', escapeHtml(event.solicitante || 'Nao informado')),
                detailRow('Destino', escapeHtml(event.destino || 'Nao informado')),
                detailRow('Periodo', escapeHtml(event.horario || 'Nao informado')),
                detailRow('Status', getStatusBadge(event))
            ].join('');
        }

        if (event.tipo === 'manutencao') {
            return [
                detailRow('Veiculo', escapeHtml(event.veiculo + ' (' + event.placa + ')')),
                detailRow('Fornecedor', escapeHtml(event.fornecedor || 'Nao informado')),
                detailRow('Custo', 'R$ ' + formatCurrency(event.custo)),
                detailRow('Descricao', escapeHtml(event.descricao || 'Sem descricao'))
            ].join('');
        }

        return [
            detailRow('Veiculo', escapeHtml(event.veiculo + ' (' + event.placa + ')')),
            detailRow('Local', escapeHtml(event.local || 'Nao informado')),
            detailRow('Descricao', escapeHtml(event.descricao || 'Sem descricao'))
        ].join('');
    };

    var openModal = function (eventData) {
        title.textContent = eventData.titulo || 'Detalhes do evento';
        body.innerHTML = renderBody(eventData);
        link.setAttribute('href', eventData.link || '#');
        modal.classList.add('is-open');
    };

    var closeModal = function () {
        modal.classList.remove('is-open');
    };

    triggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            try {
                var eventData = JSON.parse(trigger.getAttribute('data-calendar-event') || '{}');
                openModal(eventData);
            } catch (error) {
                console.error('Unable to parse calendar event payload.', error);
            }
        });
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
})();
