<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function plugin_vehiclescheduler_render_maintenance_form(
    PluginVehicleschedulerMaintenance $maintenance,
    int $maintenanceId,
    string $rootDoc,
    string $backUrl,
    ?string $incidentLinkHtml = null
): void {
    $fields = $maintenance->fields;
    $statuses = PluginVehicleschedulerMaintenance::getAllStatus();
    $types = PluginVehicleschedulerMaintenance::getAllTypes();
    $statusValue = (int) ($fields['status'] ?? PluginVehicleschedulerMaintenance::STATUS_SCHEDULED);
    $statusClassMap = [
        PluginVehicleschedulerMaintenance::STATUS_SCHEDULED   => 'vs-maintenance-form-pill-dot--scheduled',
        PluginVehicleschedulerMaintenance::STATUS_IN_PROGRESS => 'vs-maintenance-form-pill-dot--progress',
        PluginVehicleschedulerMaintenance::STATUS_DONE        => 'vs-maintenance-form-pill-dot--done',
        PluginVehicleschedulerMaintenance::STATUS_CANCELLED   => 'vs-maintenance-form-pill-dot--cancelled',
    ];
    $statusDotClass = $statusClassMap[$statusValue] ?? 'vs-maintenance-form-pill-dot--scheduled';
    $statusLabel = $statuses[$statusValue] ?? 'Nova manutencao';
    $typeValue = (int) ($fields['type'] ?? PluginVehicleschedulerMaintenance::TYPE_PREVENTIVE);
    $formAction = plugin_vehiclescheduler_get_front_url('maintenance.form.php');

    echo "<div class='vs-maintenance-form-page' data-vs-maintenance-form>";
    echo "<div class='vs-maintenance-form-surface'>";
    echo "<div class='vs-maintenance-form-card'>";
    echo "<div class='vs-maintenance-form-head'>";
    echo '<div>';
    echo "<h3 class='vs-maintenance-form-title'><i class='ti ti-tool'></i>"
        . ($maintenanceId > 0 ? 'Detalhes da Manutencao' : 'Agendar Nova Manutencao')
        . '</h3>';
    echo "<div class='vs-maintenance-form-subtitle'>Registre manutencoes preventivas e corretivas da frota.</div>";
    echo '</div>';
    echo "<div class='vs-maintenance-form-pill'><span class='vs-maintenance-form-pill-dot "
        . plugin_vehiclescheduler_maintenance_escape($statusDotClass)
        . "'></span>"
        . plugin_vehiclescheduler_maintenance_escape($maintenanceId > 0 ? $statusLabel : 'Nova manutencao')
        . '</div>';
    echo '</div>';

    echo "<form method='post' action='" . plugin_vehiclescheduler_maintenance_escape($formAction) . "' data-vs-maintenance-form-body>";
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
    echo "<div class='vs-form-feedback' data-maintenance-validation hidden></div>";

    if ($maintenanceId > 0) {
        echo Html::hidden('id', ['value' => $maintenanceId]);
    }

    echo Html::hidden(
        'plugin_vehiclescheduler_incidents_id',
        ['value' => (int) ($fields['plugin_vehiclescheduler_incidents_id'] ?? 0)]
    );

    echo "<div class='vs-maintenance-form-grid'>";

    echo "<div class='vs-maintenance-form-field'>";
    echo "<label class='vs-maintenance-form-label' for='vs-maintenance-type'>Tipo de manutencao <span class='red'>*</span></label>";
    echo "<select id='vs-maintenance-type' name='type'>";

    foreach ($types as $value => $label) {
        $selected = $typeValue === (int) $value ? ' selected' : '';
        echo "<option value='" . (int) $value . "'" . $selected . '>'
            . plugin_vehiclescheduler_maintenance_escape($label)
            . '</option>';
    }

    echo '</select>';
    echo '</div>';

    echo "<div class='vs-maintenance-form-field'>";
    echo "<div class='vs-maintenance-form-label'>Veiculo <span class='red'>*</span></div>";
    PluginVehicleschedulerVehicle::dropdown([
        'name'  => 'plugin_vehiclescheduler_vehicles_id',
        'value' => (int) ($fields['plugin_vehiclescheduler_vehicles_id'] ?? 0),
    ]);
    echo '</div>';

    echo "<div class='vs-maintenance-form-field'>";
    echo "<label class='vs-maintenance-form-label' for='vs-maintenance-scheduled'>Data agendada</label>";
    echo "<input type='date' id='vs-maintenance-scheduled' name='scheduled_date' value='"
        . plugin_vehiclescheduler_maintenance_escape((string) ($fields['scheduled_date'] ?? ''))
        . "'>";
    echo '</div>';

    echo "<div class='vs-maintenance-form-field'>";
    echo "<label class='vs-maintenance-form-label' for='vs-maintenance-completion'>Data de conclusao</label>";
    echo "<input type='date' id='vs-maintenance-completion' name='completion_date' value='"
        . plugin_vehiclescheduler_maintenance_escape((string) ($fields['completion_date'] ?? ''))
        . "'>";
    echo '</div>';

    echo "<div class='vs-maintenance-form-field'>";
    echo "<label class='vs-maintenance-form-label' for='vs-maintenance-supplier'>Fornecedor/Oficina</label>";
    echo "<input type='text' id='vs-maintenance-supplier' name='supplier' value='"
        . plugin_vehiclescheduler_maintenance_escape((string) ($fields['supplier'] ?? ''))
        . "' maxlength='255' placeholder='Nome da oficina'>";
    echo '</div>';

    echo "<div class='vs-maintenance-form-field'>";
    echo "<label class='vs-maintenance-form-label' for='vs-maintenance-cost'>Custo (R$)</label>";
    echo "<input type='number' id='vs-maintenance-cost' name='cost' value='"
        . plugin_vehiclescheduler_maintenance_escape(plugin_vehiclescheduler_maintenance_decimal((string) ($fields['cost'] ?? '0.00')))
        . "' min='0' step='0.01' placeholder='0.00'>";
    echo '</div>';

    echo "<div class='vs-maintenance-form-field'>";
    echo "<label class='vs-maintenance-form-label' for='vs-maintenance-mileage'>Quilometragem (km)</label>";
    echo "<input type='number' id='vs-maintenance-mileage' name='mileage' value='"
        . (int) ($fields['mileage'] ?? 0)
        . "' min='0' placeholder='km atual'>";
    echo '</div>';

    echo "<div class='vs-maintenance-form-field'>";
    echo "<label class='vs-maintenance-form-label' for='vs-maintenance-status'>Status</label>";
    echo "<select id='vs-maintenance-status' name='status'>";

    foreach ($statuses as $value => $label) {
        $selected = $statusValue === (int) $value ? ' selected' : '';
        echo "<option value='" . (int) $value . "'" . $selected . '>'
            . plugin_vehiclescheduler_maintenance_escape($label)
            . '</option>';
    }

    echo '</select>';
    echo '</div>';

    if ($incidentLinkHtml !== null && $incidentLinkHtml !== '') {
        echo "<div class='vs-maintenance-form-field vs-maintenance-form-field--full'>";
        echo "<div class='vs-maintenance-form-label'>Incidente de origem</div>";
        echo "<div class='vs-maintenance-form-related'>" . $incidentLinkHtml . '</div>';
        echo '</div>';
    }

    echo "<div class='vs-maintenance-form-field vs-maintenance-form-field--full'>";
    echo "<label class='vs-maintenance-form-label' for='vs-maintenance-description'>Descricao dos servicos</label>";
    echo "<textarea id='vs-maintenance-description' name='description' placeholder='Descreva os servicos realizados ou a serem realizados...'>"
        . plugin_vehiclescheduler_maintenance_escape((string) ($fields['description'] ?? ''))
        . '</textarea>';
    echo '</div>';

    echo '</div>';

    echo "<div class='vs-maintenance-form-actions'>";

    if ($maintenanceId > 0) {
        echo "<button type='submit' name='update' class='vs-maintenance-form-button vs-maintenance-form-button--primary'><i class='ti ti-device-floppy'></i>Salvar</button>";
        echo "<button type='submit' name='delete' class='vs-maintenance-form-button vs-maintenance-form-button--danger' data-confirm-message='Excluir esta manutencao?'><i class='ti ti-trash'></i>Excluir</button>";
    } else {
        echo "<button type='submit' name='add' class='vs-maintenance-form-button vs-maintenance-form-button--primary'><i class='ti ti-plus'></i>Agendar manutencao</button>";
    }

    echo "<a href='" . plugin_vehiclescheduler_maintenance_escape($backUrl) . "' class='vs-maintenance-form-link'><i class='ti ti-arrow-left'></i>Voltar</a>";
    echo '</div>';

    echo '</form>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function plugin_vehiclescheduler_maintenance_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function plugin_vehiclescheduler_maintenance_decimal(string $value): string
{
    $normalized = str_replace(',', '.', trim($value));

    if ($normalized === '' || !is_numeric($normalized)) {
        return '0.00';
    }

    return number_format((float) $normalized, 2, '.', '');
}
