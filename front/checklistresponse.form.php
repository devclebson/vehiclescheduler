<?php

include_once __DIR__ . '/../inc/common.inc.php';

/**
 * Public checklist response form.
 */

include_once(__DIR__ . '/../inc/common.inc.php');

global $DB, $CFG_GLPI;

function vs_checklist_response_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vs_checklist_response_type_label(string $type): string
{
    return $type === 'departure' ? 'Saída' : 'Chegada';
}

function vs_checklist_response_icon(string $type): string
{
    return $type === 'departure' ? '📤' : '📥';
}

function vs_checklist_response_fetch_existing(int $scheduleId, string $type): ?array
{
    global $DB;

    foreach (
        $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_checklistresponses',
            'WHERE' => [
                'plugin_vehiclescheduler_schedules_id' => $scheduleId,
                'response_type'                        => $type,
            ],
            'LIMIT' => 1,
        ]) as $row
    ) {
        return $row;
    }

    return null;
}

function vs_checklist_response_render_input(array $item): string
{
    $fieldName   = 'item_' . (int) $item['id'];
    $isMandatory = (int) ($item['is_mandatory'] ?? 0) === 1;
    $required    = $isMandatory ? ' required' : '';
    $helpText    = trim((string) ($item['help_text'] ?? ''));
    $inputHtml   = '';

    switch ((int) $item['item_type']) {
        case PluginVehicleschedulerChecklistitem::TYPE_CHECKBOX:
            $inputHtml .= '<div class="vs-checkbox-wrap">';
            $inputHtml .= '<label class="vs-checkbox-option">';
            $inputHtml .= '<input type="radio" name="' . $fieldName . '" value="Sim"' . $required . '>';
            $inputHtml .= '<span>✅ Sim</span>';
            $inputHtml .= '</label>';
            $inputHtml .= '<label class="vs-checkbox-option">';
            $inputHtml .= '<input type="radio" name="' . $fieldName . '" value="Não"' . $required . '>';
            $inputHtml .= '<span>❌ Não</span>';
            $inputHtml .= '</label>';
            $inputHtml .= '</div>';
            break;

        case PluginVehicleschedulerChecklistitem::TYPE_TEXT:
            $inputHtml .= '<textarea name="' . $fieldName . '" rows="3" placeholder="Digite aqui..."' . $required . '></textarea>';
            break;

        case PluginVehicleschedulerChecklistitem::TYPE_NUMBER:
            $inputHtml .= '<input type="number" name="' . $fieldName . '" placeholder="Digite o número..."' . $required . '>';
            break;

        case PluginVehicleschedulerChecklistitem::TYPE_PHOTO:
            $inputHtml .= '<input type="file" name="' . $fieldName . '" accept="image/*"' . $required . '>';
            $inputHtml .= '<p class="vs-item-help">📷 Tire uma foto ou selecione da galeria</p>';
            break;

        case PluginVehicleschedulerChecklistitem::TYPE_SIGNATURE:
            $inputHtml .= '<input type="text" name="' . $fieldName . '" placeholder="Digite seu nome para assinar..."' . $required . '>';
            break;
    }

    if ($helpText !== '') {
        $inputHtml .= '<p class="vs-item-help">💡 ' . vs_checklist_response_escape($helpText) . '</p>';
    }

    return $inputHtml;
}

$scheduleId = PluginVehicleschedulerInput::int($_GET, 'schedule_id', 0, 1);
$type       = PluginVehicleschedulerInput::enum(
    $_GET,
    'type',
    ['departure', 'arrival'],
    'departure'
);

if ($scheduleId <= 0) {
    Session::addMessageAfterRedirect('ID de agendamento inválido.', false, ERROR);
    $rootDoc = plugin_vehiclescheduler_get_root_doc();
    Html::redirect($rootDoc . '/front/central.php');
}

$schedule = new PluginVehicleschedulerSchedule();
if (!$schedule->getFromDB($scheduleId)) {
    Session::addMessageAfterRedirect('Agendamento não encontrado.', false, ERROR);
    $rootDoc = plugin_vehiclescheduler_get_root_doc();
    Html::redirect($rootDoc . '/front/central.php');
}

$vehicle = new PluginVehicleschedulerVehicle();
$vehicle->getFromDB((int) $schedule->fields['plugin_vehiclescheduler_vehicles_id']);

$existingResponse = vs_checklist_response_fetch_existing($scheduleId, $type);

$checklistType = $type === 'departure'
    ? PluginVehicleschedulerChecklist::TYPE_DEPARTURE
    : PluginVehicleschedulerChecklist::TYPE_ARRIVAL;

$checklist = null;
foreach (
    $DB->request([
        'FROM'  => 'glpi_plugin_vehiclescheduler_checklists',
        'WHERE' => [
            'is_active' => 1,
            'OR'        => [
                ['checklist_type' => $checklistType],
                ['checklist_type' => PluginVehicleschedulerChecklist::TYPE_BOTH],
            ],
        ],
        'ORDER' => 'id ASC',
        'LIMIT' => 1,
    ]) as $row
) {
    $checklist = $row;
    break;
}

if (!$checklist || !isset($checklist['id'])) {
    Session::addMessageAfterRedirect('Nenhum checklist ativo encontrado para este tipo de operação.', false, ERROR);
    $rootDoc = plugin_vehiclescheduler_get_root_doc();
    Html::redirect($rootDoc . '/front/central.php');
}

$items = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_checklistitems',
    'WHERE' => ['plugin_vehiclescheduler_checklists_id' => (int) $checklist['id']],
    'ORDER' => ['position ASC', 'id ASC'],
]));

if (isset($_POST['submit_checklist'])) {
    $existingResponse = vs_checklist_response_fetch_existing($scheduleId, $type);

    if ($existingResponse !== null) {
        Session::addMessageAfterRedirect('Este checklist já foi preenchido.', false, WARNING);
        $rootDoc = plugin_vehiclescheduler_get_root_doc();
        Html::redirect($rootDoc . '/front/ticket.form.php?id=' . (int) $schedule->fields['tickets_id']);
    }

    $userId       = Session::getLoginUserID();
    $responseData = [
        'plugin_vehiclescheduler_schedules_id'  => $scheduleId,
        'plugin_vehiclescheduler_checklists_id' => (int) $checklist['id'],
        'users_id'                              => $userId,
        'response_type'                         => $type,
        'completed_at'                          => date('Y-m-d H:i:s'),
        'date_creation'                         => date('Y-m-d H:i:s'),
    ];

    $responseId = $DB->insert('glpi_plugin_vehiclescheduler_checklistresponses', $responseData);

    if ($responseId) {
        foreach ($items as $item) {
            $fieldName = 'item_' . (int) $item['id'];
            $value     = PluginVehicleschedulerInput::text($_POST, $fieldName, 65535, '');

            if ((int) $item['item_type'] === PluginVehicleschedulerChecklistitem::TYPE_PHOTO && isset($_FILES[$fieldName])) {
                $value = PluginVehicleschedulerInput::string(
                    ['upload' => $_FILES[$fieldName]['name'] ?? ''],
                    'upload',
                    255,
                    ''
                );
            }

            $DB->insert('glpi_plugin_vehiclescheduler_checklistresponse_items', [
                'plugin_vehiclescheduler_checklistresponses_id' => $responseId,
                'plugin_vehiclescheduler_checklistitems_id'     => (int) $item['id'],
                'response_value'                                => $value,
                'date_creation'                                 => date('Y-m-d H:i:s'),
            ]);
        }

        if ((int) $schedule->fields['tickets_id'] > 0) {
            $followupContent = ($type === 'departure'
                ? '✅ CHECKLIST DE SAÍDA PREENCHIDO'
                : '✅ CHECKLIST DE CHEGADA PREENCHIDO') . "\n\n"
                . 'Preenchido por: ' . getUserName($userId) . "\n"
                . 'Data: ' . date('d/m/Y H:i:s') . "\n"
                . 'Template: ' . (string) $checklist['name'];

            $followup = new ITILFollowup();
            $followup->add([
                'itemtype'   => 'Ticket',
                'items_id'   => (int) $schedule->fields['tickets_id'],
                'users_id'   => $userId,
                'content'    => $followupContent,
                'is_private' => 0,
            ]);

            if ($type === 'departure') {
                $hasArrivalChecklist = false;
                foreach (
                    $DB->request([
                        'FROM'  => 'glpi_plugin_vehiclescheduler_checklists',
                        'WHERE' => [
                            'is_active' => 1,
                            'OR'        => [
                                ['checklist_type' => PluginVehicleschedulerChecklist::TYPE_ARRIVAL],
                                ['checklist_type' => PluginVehicleschedulerChecklist::TYPE_BOTH],
                            ],
                        ],
                        'LIMIT' => 1,
                    ]) as $unused
                ) {
                    $hasArrivalChecklist = true;
                    break;
                }

                if ($hasArrivalChecklist) {
                    $rootDoc = plugin_vehiclescheduler_get_root_doc();
                    $arrivalUrl  = plugin_vehiclescheduler_get_front_url('checklistresponse.form.php') . '?schedule_id='
                        . $scheduleId . '&type=arrival';
                    $incidentUrl = plugin_vehiclescheduler_get_front_url('incident.form.php') . '?schedule_id='
                        . $scheduleId;

                    $nextSteps = "📋 PRÓXIMOS PASSOS\n\n";
                    $nextSteps .= "✅ Checklist de saída concluído com sucesso.\n\n";
                    $nextSteps .= "1. Ao devolver o veículo, preencha o checklist de chegada:\n";
                    $nextSteps .= $arrivalUrl . "\n\n";
                    $nextSteps .= "2. Em caso de incidente, registre imediatamente:\n";
                    $nextSteps .= $incidentUrl . "\n\n";
                    $nextSteps .= "Importante:\n";
                    $nextSteps .= "- O checklist de chegada é obrigatório ao devolver o veículo.\n";
                    $nextSteps .= "- Incidentes devem ser reportados imediatamente.\n";
                    $nextSteps .= "- Guarde este chamado para acessar os links sempre que necessário.";

                    $followup2 = new ITILFollowup();
                    $followup2->add([
                        'itemtype'   => 'Ticket',
                        'items_id'   => (int) $schedule->fields['tickets_id'],
                        'users_id'   => $userId,
                        'content'    => $nextSteps,
                        'is_private' => 0,
                    ]);
                }
            }
        }

        Session::addMessageAfterRedirect('Checklist preenchido com sucesso!', false, INFO);
        $rootDoc = plugin_vehiclescheduler_get_root_doc();
        Html::redirect($rootDoc . '/front/ticket.form.php?id=' . (int) $schedule->fields['tickets_id']);
    }

    Session::addMessageAfterRedirect('Erro ao salvar checklist. Tente novamente.', false, ERROR);
    Html::back();
}

Html::header('Checklist de ' . vs_checklist_response_type_label($type), $_SERVER['PHP_SELF']);

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

$vehicleLabel = trim(
    (string) ($vehicle->fields['name'] ?? '') . ' (' . (string) ($vehicle->fields['plate'] ?? '') . ')'
);
?>
<div class="vs-checklist-response-page">
    <?php if ($existingResponse !== null): ?>
        <section class="vs-checklist-response-page__surface vs-checklist-response-page__surface--filled">
            <div class="vs-checklist-response-alert">
                <div class="vs-checklist-response-alert__icon">⚠️</div>
                <h1 class="vs-checklist-response-alert__title">Checklist já preenchido</h1>
                <p class="vs-checklist-response-alert__message">
                    Este checklist de <?= vs_checklist_response_escape(mb_strtoupper(vs_checklist_response_type_label($type))) ?>
                    já foi concluído anteriormente.
                </p>
                <div class="vs-checklist-response-alert__meta">
                    <div>
                        <span class="vs-checklist-response-label">Veículo</span>
                        <strong><?= vs_checklist_response_escape($vehicleLabel) ?></strong>
                    </div>
                    <div>
                        <span class="vs-checklist-response-label">Preenchido por</span>
                        <strong><?= vs_checklist_response_escape(getUserName((int) $existingResponse['users_id'])) ?></strong>
                    </div>
                    <div>
                        <span class="vs-checklist-response-label">Data</span>
                        <strong><?= Html::convDateTime($existingResponse['completed_at']) ?></strong>
                    </div>
                </div>
                <a
                    href="<?= plugin_vehiclescheduler_get_root_doc() ?>/front/ticket.form.php?id=<?= (int) $schedule->fields['tickets_id'] ?>"
                    class="vs-checklist-response-back">
                    Voltar ao chamado
                </a>
            </div>
        </section>
    <?php else: ?>
        <div class="vs-checklist-response-page__surface">
            <section class="vs-checklist-response-card">
                <header class="vs-checklist-response-card__header">
                    <div>
                        <p class="vs-checklist-response-eyebrow">Checklist operacional</p>
                        <h1>
                            <?= vs_checklist_response_escape(vs_checklist_response_icon($type) . ' Checklist de ' . vs_checklist_response_type_label($type)) ?>
                        </h1>
                        <p class="vs-checklist-response-subtitle">
                            <?= vs_checklist_response_escape((string) $checklist['name']) ?>
                        </p>
                    </div>
                    <div class="vs-checklist-response-status">
                        <span>Preenchimento único</span>
                        <strong>Antes de concluir a etapa</strong>
                    </div>
                </header>

                <div class="vs-checklist-response-summary">
                    <div class="vs-checklist-response-summary__item">
                        <span class="vs-checklist-response-label">Veículo</span>
                        <strong><?= vs_checklist_response_escape($vehicleLabel) ?></strong>
                    </div>
                    <div class="vs-checklist-response-summary__item">
                        <span class="vs-checklist-response-label">Destino</span>
                        <strong><?= vs_checklist_response_escape((string) $schedule->fields['destination']) ?></strong>
                    </div>
                    <div class="vs-checklist-response-summary__item">
                        <span class="vs-checklist-response-label">Saída</span>
                        <strong><?= Html::convDateTime($schedule->fields['begin_date']) ?></strong>
                    </div>
                    <div class="vs-checklist-response-summary__item">
                        <span class="vs-checklist-response-label">Retorno</span>
                        <strong><?= Html::convDateTime($schedule->fields['end_date']) ?></strong>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="vs-checklist-response-form">
                    <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">

                    <div class="vs-checklist-response-items">
                        <?php foreach ($items as $index => $item): ?>
                            <article class="vs-checklist-response-item">
                                <div class="vs-checklist-response-item__header">
                                    <span class="vs-checklist-response-item__number"><?= $index + 1 ?></span>
                                    <div class="vs-checklist-response-item__content">
                                        <h2><?= vs_checklist_response_escape((string) $item['description']) ?></h2>
                                        <?php if ((int) ($item['is_mandatory'] ?? 0) === 1): ?>
                                            <span class="vs-checklist-response-item__mandatory">Obrigatório</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="vs-checklist-response-item__input">
                                    <?= vs_checklist_response_render_input($item) ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <footer class="vs-checklist-response-form__footer">
                        <p>Revise os itens antes de enviar. O checklist não poderá ser preenchido novamente.</p>
                        <button type="submit" name="submit_checklist" class="vs-checklist-response-submit">
                            Enviar checklist
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    <?php endif; ?>
</div>

<script src="../public/js/checklistresponse-form.js"></script>
<?php Html::footer(); ?>
