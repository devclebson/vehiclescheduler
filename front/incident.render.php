<?php

include_once __DIR__ . '/../inc/common.inc.php';

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function plugin_vehiclescheduler_render_incident_form(PluginVehicleschedulerIncident $incident, int $incidentId): void
{
    $fields = $incident->fields;
    $statuses = PluginVehicleschedulerIncident::getAllStatus();
    $types = PluginVehicleschedulerIncident::getAllTypes();
    $statusValue = (int) ($fields['status'] ?? PluginVehicleschedulerIncident::STATUS_OPEN);
    $statusLabel = $statuses[$statusValue] ?? 'Novo reporte';
    $statusClassMap = [
        PluginVehicleschedulerIncident::STATUS_OPEN      => 'vs-incident-form-pill-dot--open',
        PluginVehicleschedulerIncident::STATUS_ANALYZING => 'vs-incident-form-pill-dot--analyzing',
        PluginVehicleschedulerIncident::STATUS_RESOLVED  => 'vs-incident-form-pill-dot--resolved',
        PluginVehicleschedulerIncident::STATUS_CLOSED    => 'vs-incident-form-pill-dot--closed',
    ];
    $statusDotClass = $statusClassMap[$statusValue] ?? 'vs-incident-form-pill-dot--open';
    $canManage = PluginVehicleschedulerProfile::canEditManagement();
    $backUrl = $canManage
        ? plugin_vehiclescheduler_get_front_url('incident.php')
        : plugin_vehiclescheduler_get_front_url('requester.php');

    echo "<div class='vs-incident-form-page' data-vs-incident-form>";
    echo "<div class='vs-incident-form-surface'>";
    echo "<div class='vs-incident-form-card'>";
    echo "<div class='vs-incident-form-head'>";
    echo '<div>';
    echo "<h3 class='vs-incident-form-title'><i class='ti ti-alert-triangle'></i>"
        . ($incidentId > 0 ? 'Detalhes do Sinistro' : 'Informar Sinistro')
        . '</h3>';
    echo "<div class='vs-incident-form-subtitle'>Registre acidentes, avarias, furtos ou ocorrências relacionadas ao uso da viatura.</div>";
    echo '</div>';
    echo "<div class='vs-incident-form-pill'><span class='vs-incident-form-pill-dot "
        . plugin_vehiclescheduler_incident_escape($statusDotClass)
        . "'></span>"
        . plugin_vehiclescheduler_incident_escape($incidentId > 0 ? $statusLabel : 'Novo reporte')
        . '</div>';
    echo '</div>';

    echo "<div class='vs-incident-form-alert' data-incident-alert>";
    echo '<strong>Atenção:</strong> em caso de acidente com vítimas, acione imediatamente os canais oficiais de emergência antes de registrar o sinistro.';
    echo '</div>';

    echo "<div class='vs-form-feedback' data-incident-validation hidden></div>";
    echo "<div class='vs-incident-form-grid'>";

    echo "<div class='vs-incident-form-field'>";
    echo "<div class='vs-incident-form-label'>Viagem/Solicitação relacionada</div>";
    $scheduleId = (int) ($fields['plugin_vehiclescheduler_schedules_id'] ?? 0);
    if ($canManage) {
        echo "<select name='plugin_vehiclescheduler_schedules_id' data-incident-schedule>";
        echo "<option value='0'>Sem vínculo</option>";
        foreach (plugin_vehiclescheduler_incident_schedule_options() as $option) {
            $selected = $scheduleId === (int) $option['id'] ? ' selected' : '';
            echo "<option value='" . (int) $option['id'] . "'" . $selected . '>'
                . plugin_vehiclescheduler_incident_escape($option['label'])
                . '</option>';
        }
        echo '</select>';
    } else {
        echo Html::hidden('plugin_vehiclescheduler_schedules_id', ['value' => $scheduleId]);
        echo "<div class='vs-incident-form-readonly'>"
            . plugin_vehiclescheduler_incident_escape((string) (($fields['schedule_label'] ?? '') !== '' ? $fields['schedule_label'] : 'Sem vínculo'))
            . '</div>';
    }
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<div class='vs-incident-form-label'>Solicitante <span class='red'>*</span></div>";
    $requesterId = (int) ($fields['users_id'] ?? Session::getLoginUserID());
    if ($canManage) {
        User::dropdown([
            'name'   => 'users_id',
            'value'  => $requesterId,
            'entity' => (int) ($fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
            'right'  => 'all',
        ]);
    } else {
        echo Html::hidden('users_id', ['value' => $requesterId]);
        echo "<div class='vs-incident-form-readonly'>"
            . plugin_vehiclescheduler_incident_escape((string) getUserName($requesterId))
            . '</div>';
    }
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<div class='vs-incident-form-label'>Departamento/Setor</div>";
    Group::dropdown([
        'name'   => 'groups_id',
        'value'  => (int) ($fields['groups_id'] ?? 0),
        'entity' => (int) ($fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
    ]);
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<div class='vs-incident-form-label'>Veículo <span class='red'>*</span></div>";
    PluginVehicleschedulerVehicle::dropdown([
        'name'  => 'plugin_vehiclescheduler_vehicles_id',
        'value' => (int) ($fields['plugin_vehiclescheduler_vehicles_id'] ?? 0),
    ]);
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<div class='vs-incident-form-label'>Motorista no momento</div>";
    PluginVehicleschedulerDriver::dropdown([
        'name'  => 'plugin_vehiclescheduler_drivers_id',
        'value' => (int) ($fields['plugin_vehiclescheduler_drivers_id'] ?? 0),
    ]);
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-type'>Tipo de ocorrência <span class='red'>*</span></label>";
    echo "<select id='vs-incident-type' name='incident_type' data-incident-type>";

    foreach ($types as $typeId => $typeLabel) {
        $selected = ((int) ($fields['incident_type'] ?? PluginVehicleschedulerIncident::TYPE_ACCIDENT) === (int) $typeId)
            ? ' selected'
            : '';
        echo "<option value='" . (int) $typeId . "'" . $selected . '>'
            . plugin_vehiclescheduler_incident_escape($typeLabel)
            . '</option>';
    }

    echo '</select>';
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-date'>Data/Hora da ocorrência <span class='red'>*</span></label>";
    echo "<input type='datetime-local' id='vs-incident-date' name='incident_date' value='"
        . plugin_vehiclescheduler_incident_escape(plugin_vehiclescheduler_incident_to_datetime_local((string) ($fields['incident_date'] ?? date('Y-m-d H:i:s'))))
        . "'>";
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-location'>Local da ocorrência</label>";
    echo "<input type='text' id='vs-incident-location' name='location' value='"
        . plugin_vehiclescheduler_incident_escape((string) ($fields['location'] ?? ''))
        . "' maxlength='255' placeholder='Onde aconteceu?'>";
    echo '</div>';

    echo "<div class='vs-incident-form-field'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-phone'>Telefone para contato</label>";
    echo "<input type='tel' id='vs-incident-phone' name='contact_phone' value='"
        . plugin_vehiclescheduler_incident_escape((string) ($fields['contact_phone'] ?? ''))
        . "' maxlength='20' data-incident-phone>";
    echo '</div>';

    if ($canManage || $incidentId > 0) {
        echo "<div class='vs-incident-form-field'>";
        echo "<label class='vs-incident-form-label' for='vs-incident-status'>Status</label>";
        echo "<select id='vs-incident-status' name='status'" . ($canManage ? '' : ' disabled') . '>';

        foreach ($statuses as $value => $label) {
            $selected = $statusValue === (int) $value ? ' selected' : '';
            echo "<option value='" . (int) $value . "'" . $selected . '>'
                . plugin_vehiclescheduler_incident_escape($label)
                . '</option>';
        }

        echo '</select>';
        echo '</div>';
    }

    if ($canManage) {
        echo "<div class='vs-incident-form-field'>";
        echo "<label class='vs-incident-form-label' for='vs-incident-maintenance'>Requer manutenção?</label>";
        echo "<select id='vs-incident-maintenance' name='needs_maintenance'>";
        echo plugin_vehiclescheduler_render_incident_yes_no_options((int) ($fields['needs_maintenance'] ?? 0));
        echo '</select>';
        echo '</div>';

        echo "<div class='vs-incident-form-field'>";
        echo "<label class='vs-incident-form-label' for='vs-incident-insurance'>Requer seguro?</label>";
        echo "<select id='vs-incident-insurance' name='needs_insurance'>";
        echo plugin_vehiclescheduler_render_incident_yes_no_options((int) ($fields['needs_insurance'] ?? 0));
        echo '</select>';
        echo '</div>';
    }

    echo "<div class='vs-incident-form-field vs-incident-form-field--full'>";
    echo "<label class='vs-incident-form-label' for='vs-incident-description'>Descrição detalhada <span class='red'>*</span></label>";
    echo "<textarea id='vs-incident-description' name='description' placeholder='Descreva o que aconteceu com o máximo de detalhes possível...' required>"
        . plugin_vehiclescheduler_incident_escape((string) ($fields['description'] ?? ''))
        . '</textarea>';
    echo '</div>';

    echo '</div>';

    echo "<div class='vs-incident-form-actions'>";
    echo "<a href='" . plugin_vehiclescheduler_incident_escape($backUrl) . "' class='vs-incident-form-link'><i class='ti ti-arrow-left'></i>Voltar</a>";
    echo '</div>';

    if ($canManage && $incidentId > 0) {
        $vehicleId = (int) ($fields['plugin_vehiclescheduler_vehicles_id'] ?? 0);
        $maintenanceUrl = plugin_vehiclescheduler_get_front_url('maintenance.form.php') . '?plugin_vehiclescheduler_vehicles_id='
            . $vehicleId
            . '&plugin_vehiclescheduler_incidents_id='
            . $incidentId
            . '&maintenance_type=2';
        $insuranceUrl = plugin_vehiclescheduler_get_front_url('insuranceclaim.form.php') . '?plugin_vehiclescheduler_vehicles_id='
            . $vehicleId
            . '&plugin_vehiclescheduler_incidents_id='
            . $incidentId;

        echo "<div class='vs-incident-form-quick-actions'>";
        echo "<div class='vs-incident-form-quick-title'>Ações rápidas</div>";
        echo "<a href='" . plugin_vehiclescheduler_incident_escape($maintenanceUrl) . "' class='vs-incident-form-quick-link'><i class='ti ti-tool'></i>Criar manutenção corretiva</a>";
        echo "<a href='" . plugin_vehiclescheduler_incident_escape($insuranceUrl) . "' class='vs-incident-form-quick-link'><i class='ti ti-shield'></i>Abrir sinistro de seguro</a>";
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function plugin_vehiclescheduler_incident_to_datetime_local(string $value): string
{
    $trimmed = trim($value);

    if ($trimmed === '' || $trimmed === '0000-00-00 00:00:00') {
        return '';
    }

    $timestamp = strtotime($trimmed);

    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d\TH:i', $timestamp);
}

function plugin_vehiclescheduler_render_incident_yes_no_options(int $selected): string
{
    $html = '';

    foreach ([1 => 'Sim', 0 => 'Não'] as $value => $label) {
        $isSelected = $selected === $value ? ' selected' : '';
        $html .= "<option value='" . $value . "'" . $isSelected . '>'
            . plugin_vehiclescheduler_incident_escape($label)
            . '</option>';
    }

    return $html;
}

function plugin_vehiclescheduler_incident_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function plugin_vehiclescheduler_incident_schedule_options(): array
{
    global $DB;

    $options = [];

    foreach ($DB->request([
        'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
        'ORDER' => [
            'begin_date DESC',
            'id DESC',
        ],
        'LIMIT' => 100,
    ]) as $row) {
        $scheduleId = (int) ($row['id'] ?? 0);
        if ($scheduleId <= 0) {
            continue;
        }

        $begin = Html::convDateTime((string) ($row['begin_date'] ?? ''));
        $end = Html::convDateTime((string) ($row['end_date'] ?? ''));
        $destination = trim((string) ($row['destination'] ?? ''));
        $requester = (int) ($row['users_id'] ?? 0) > 0 ? getUserName((int) $row['users_id']) : '';

        $parts = [
            '#' . $scheduleId,
            $destination !== '' ? $destination : 'Sem destino',
            $begin !== '' && $end !== '' ? $begin . ' - ' . $end : '',
            $requester !== '' ? $requester : '',
        ];

        $options[] = [
            'id'    => $scheduleId,
            'label' => trim(implode(' | ', array_filter($parts, static fn ($part) => trim((string) $part) !== ''))),
        ];
    }

    return $options;
}
