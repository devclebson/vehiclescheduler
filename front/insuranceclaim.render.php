<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

function plugin_vehiclescheduler_render_insuranceclaim_form(
    PluginVehicleschedulerInsuranceclaim $claim,
    int $claimId,
    string $rootDoc,
    string $backUrl,
    ?string $incidentLinkHtml = null
): void {
    $fields = $claim->fields;
    $statuses = PluginVehicleschedulerInsuranceclaim::getAllStatus();
    $statusValue = (int) ($fields['status'] ?? PluginVehicleschedulerInsuranceclaim::STATUS_OPENED);
    $statusClassMap = [
        PluginVehicleschedulerInsuranceclaim::STATUS_OPENED   => 'vs-claim-form-pill-dot--opened',
        PluginVehicleschedulerInsuranceclaim::STATUS_ANALYSIS => 'vs-claim-form-pill-dot--analysis',
        PluginVehicleschedulerInsuranceclaim::STATUS_APPROVED => 'vs-claim-form-pill-dot--approved',
        PluginVehicleschedulerInsuranceclaim::STATUS_REJECTED => 'vs-claim-form-pill-dot--rejected',
        PluginVehicleschedulerInsuranceclaim::STATUS_CLOSED   => 'vs-claim-form-pill-dot--closed',
    ];
    $statusDotClass = $statusClassMap[$statusValue] ?? 'vs-claim-form-pill-dot--opened';
    $statusLabel = $statuses[$statusValue] ?? 'Novo sinistro';
    $formAction = plugin_vehiclescheduler_get_front_url('insuranceclaim.form.php');

    echo "<div class='vs-claim-form-page' data-vs-claim-form>";
    echo "<div class='vs-claim-form-surface'>";
    echo "<div class='vs-claim-form-card'>";
    echo "<div class='vs-claim-form-head'>";
    echo '<div>';
    echo "<h3 class='vs-claim-form-title'><i class='ti ti-shield-check'></i>"
        . ($claimId > 0 ? 'Detalhes do Sinistro' : 'Abrir Novo Sinistro')
        . '</h3>';
    echo "<div class='vs-claim-form-subtitle'>Registre sinistros com seguradora para cobertura de danos.</div>";
    echo '</div>';
    echo "<div class='vs-claim-form-pill'><span class='vs-claim-form-pill-dot "
        . plugin_vehiclescheduler_insuranceclaim_escape($statusDotClass)
        . "'></span>"
        . plugin_vehiclescheduler_insuranceclaim_escape($claimId > 0 ? $statusLabel : 'Novo sinistro')
        . '</div>';
    echo '</div>';

    echo "<form method='post' action='" . plugin_vehiclescheduler_insuranceclaim_escape($formAction) . "' data-vs-claim-form-body>";
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
    echo "<div class='vs-form-feedback' data-claim-validation hidden></div>";

    if ($claimId > 0) {
        echo Html::hidden('id', ['value' => $claimId]);
    }

    echo Html::hidden(
        'plugin_vehiclescheduler_incidents_id',
        ['value' => (int) ($fields['plugin_vehiclescheduler_incidents_id'] ?? 0)]
    );

    echo "<div class='vs-claim-form-grid'>";

    echo "<div class='vs-claim-form-field'>";
    echo "<label class='vs-claim-form-label' for='vs-claim-number'>N do Sinistro/Protocolo</label>";
    echo "<input type='text' id='vs-claim-number' name='claim_number' value='"
        . plugin_vehiclescheduler_insuranceclaim_escape((string) ($fields['claim_number'] ?? ''))
        . "' maxlength='100' placeholder='ex: SIN-2025-0001'>";
    echo '</div>';

    echo "<div class='vs-claim-form-field'>";
    echo "<label class='vs-claim-form-label' for='vs-claim-status'>Status</label>";
    echo "<select id='vs-claim-status' name='status'>";

    foreach ($statuses as $value => $label) {
        $selected = $statusValue === (int) $value ? ' selected' : '';
        echo "<option value='" . (int) $value . "'" . $selected . '>'
            . plugin_vehiclescheduler_insuranceclaim_escape($label)
            . '</option>';
    }

    echo '</select>';
    echo '</div>';

    echo "<div class='vs-claim-form-field'>";
    echo "<div class='vs-claim-form-label'>Veiculo <span class='red'>*</span></div>";
    PluginVehicleschedulerVehicle::dropdown([
        'name'  => 'plugin_vehiclescheduler_vehicles_id',
        'value' => (int) ($fields['plugin_vehiclescheduler_vehicles_id'] ?? 0),
    ]);
    echo '</div>';

    echo "<div class='vs-claim-form-field'>";
    echo "<label class='vs-claim-form-label' for='vs-claim-company'>Seguradora</label>";
    echo "<input type='text' id='vs-claim-company' name='insurance_company' value='"
        . plugin_vehiclescheduler_insuranceclaim_escape((string) ($fields['insurance_company'] ?? ''))
        . "' maxlength='255' placeholder='Nome da seguradora'>";
    echo '</div>';

    echo "<div class='vs-claim-form-field'>";
    echo "<label class='vs-claim-form-label' for='vs-claim-opening'>Data de abertura</label>";
    echo "<input type='date' id='vs-claim-opening' name='opening_date' value='"
        . plugin_vehiclescheduler_insuranceclaim_escape((string) ($fields['opening_date'] ?? date('Y-m-d')))
        . "'>";
    echo '</div>';

    echo "<div class='vs-claim-form-field'>";
    echo "<label class='vs-claim-form-label' for='vs-claim-closing'>Data de fechamento</label>";
    echo "<input type='date' id='vs-claim-closing' name='closing_date' value='"
        . plugin_vehiclescheduler_insuranceclaim_escape((string) ($fields['closing_date'] ?? ''))
        . "'>";
    echo '</div>';

    echo "<div class='vs-claim-form-field'>";
    echo "<label class='vs-claim-form-label' for='vs-claim-estimated'>Valor estimado (R$)</label>";
    echo "<input type='number' id='vs-claim-estimated' name='estimated_value' value='"
        . plugin_vehiclescheduler_insuranceclaim_escape(plugin_vehiclescheduler_insuranceclaim_decimal((string) ($fields['estimated_value'] ?? '0.00')))
        . "' min='0' step='0.01' placeholder='0.00'>";
    echo '</div>';

    echo "<div class='vs-claim-form-field'>";
    echo "<label class='vs-claim-form-label' for='vs-claim-approved'>Valor aprovado (R$)</label>";
    echo "<input type='number' id='vs-claim-approved' name='approved_value' value='"
        . plugin_vehiclescheduler_insuranceclaim_escape(plugin_vehiclescheduler_insuranceclaim_decimal((string) ($fields['approved_value'] ?? '0.00')))
        . "' min='0' step='0.01' placeholder='0.00'>";
    echo '</div>';

    echo "<div class='vs-claim-form-field'>";
    echo "<label class='vs-claim-form-label' for='vs-claim-contact'>Contato na seguradora</label>";
    echo "<input type='text' id='vs-claim-contact' name='contact_name' value='"
        . plugin_vehiclescheduler_insuranceclaim_escape((string) ($fields['contact_name'] ?? ''))
        . "' maxlength='255' placeholder='Nome do responsavel'>";
    echo '</div>';

    if ($incidentLinkHtml !== null && $incidentLinkHtml !== '') {
        echo "<div class='vs-claim-form-field'>";
        echo "<div class='vs-claim-form-label'>Incidente de origem</div>";
        echo "<div class='vs-claim-form-related'>" . $incidentLinkHtml . '</div>';
        echo '</div>';
    }

    echo "<div class='vs-claim-form-field vs-claim-form-field--full'>";
    echo "<label class='vs-claim-form-label' for='vs-claim-description'>Descricao do sinistro</label>";
    echo "<textarea id='vs-claim-description' name='description' placeholder='Descreva os danos e circunstancias do sinistro...'>"
        . plugin_vehiclescheduler_insuranceclaim_escape((string) ($fields['description'] ?? ''))
        . '</textarea>';
    echo '</div>';

    echo '</div>';

    echo "<div class='vs-claim-form-actions'>";

    if ($claimId > 0) {
        echo "<button type='submit' name='update' class='vs-claim-form-button vs-claim-form-button--primary'><i class='ti ti-device-floppy'></i>Salvar</button>";
        echo "<button type='submit' name='delete' class='vs-claim-form-button vs-claim-form-button--danger' data-confirm-message='Excluir este sinistro?'><i class='ti ti-trash'></i>Excluir</button>";
    } else {
        echo "<button type='submit' name='add' class='vs-claim-form-button vs-claim-form-button--primary'><i class='ti ti-plus'></i>Abrir sinistro</button>";
    }

    echo "<a href='" . plugin_vehiclescheduler_insuranceclaim_escape($backUrl) . "' class='vs-claim-form-link'><i class='ti ti-arrow-left'></i>Voltar</a>";
    echo '</div>';

    echo '</form>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function plugin_vehiclescheduler_insuranceclaim_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function plugin_vehiclescheduler_insuranceclaim_decimal(string $value): string
{
    $normalized = str_replace(',', '.', trim($value));

    if ($normalized === '' || !is_numeric($normalized)) {
        return '0.00';
    }

    return number_format((float) $normalized, 2, '.', '');
}
