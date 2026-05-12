<?php

include_once(__DIR__ . '/../inc/common.inc.php');
include_once(__DIR__ . '/../inc/ui-helpers.php');

$schedule = new \PluginVehicleschedulerSchedule();

$root_doc = plugin_vehiclescheduler_get_root_doc();

$form_action = plugin_vehiclescheduler_get_front_url('schedule.form.php');
$self = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

$post_id = PluginVehicleschedulerInput::int($_POST, 'id', 0, 0);
$get_id  = PluginVehicleschedulerInput::int($_GET, 'id', 0, 0);
$id      = $post_id > 0 ? $post_id : $get_id;

$request_post = $_POST;
$request_post['id'] = $post_id;
$request_post['plugin_vehiclescheduler_vehicles_id'] = PluginVehicleschedulerInput::int(
    $_POST,
    'plugin_vehiclescheduler_vehicles_id',
    0,
    0
);
$request_post['plugin_vehiclescheduler_drivers_id'] = PluginVehicleschedulerInput::int(
    $_POST,
    'plugin_vehiclescheduler_drivers_id',
    0,
    0
);
$request_post['rejection_justification'] = PluginVehicleschedulerInput::text(
    $_POST,
    'rejection_justification',
    5000
);

$can_request   = \PluginVehicleschedulerProfile::canAccessRequester();
$can_manage    = \PluginVehicleschedulerProfile::canViewManagement();
$can_approve   = \PluginVehicleschedulerProfile::canApproveReservations();
$can_edit      = \PluginVehicleschedulerProfile::canEditManagement();
$can_assign    = \PluginVehicleschedulerSchedule::canAssignResources();
$can_status    = \PluginVehicleschedulerSchedule::canChangeStatus();
$can_open_form = \PluginVehicleschedulerSchedule::canOpenForm();
$can_create    = \PluginVehicleschedulerSchedule::canCreateRequest();

$can_manage_assignments = $can_edit || $can_approve;
$can_approval_action    = $can_approve && $can_status;
$csrf_token             = \Session::getNewCSRFToken();


$is_self_service_mode = !$can_approve && !$can_edit && !$can_manage;
$getSuccessRedirect = static function () use ($is_self_service_mode, $root_doc): string {
    if ($is_self_service_mode) {
        return $root_doc . '/front/ticket.php';
    }

    return plugin_vehiclescheduler_get_front_url('schedule.php');
};


$h = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$json_attr = static function ($value): string {
    $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return htmlspecialchars($encoded !== false ? $encoded : '{}', ENT_QUOTES, 'UTF-8');
};

$to_date_time_local = static function (?string $value): string {
    if (!$value || $value === '0000-00-00 00:00:00') {
        return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '';
    }

    return date('Y-m-d\TH:i', $timestamp);
};

$merge_schedule_input = static function (
    array $base,
    array $post,
    int $fallback_user_id,
    int $fallback_entity_id
): array {
    $input = $base;

    $input['users_id'] = PluginVehicleschedulerInput::int($post, 'users_id', (int)($base['users_id'] ?? $fallback_user_id), 0);
    $input['entities_id'] = PluginVehicleschedulerInput::int($post, 'entities_id', (int)($base['entities_id'] ?? $fallback_entity_id), 0);
    $input['groups_id'] = PluginVehicleschedulerInput::int($post, 'groups_id', (int)($base['groups_id'] ?? 0), 0);
    $input['tickets_id'] = PluginVehicleschedulerInput::int($post, 'tickets_id', (int)($base['tickets_id'] ?? 0), 0);
    $input['status'] = PluginVehicleschedulerInput::int(
        $post,
        'status',
        (int)($base['status'] ?? \PluginVehicleschedulerSchedule::STATUS_PENDING),
        \PluginVehicleschedulerSchedule::STATUS_PENDING,
        \PluginVehicleschedulerSchedule::STATUS_REJECTED
    );

    if (array_key_exists('plugin_vehiclescheduler_vehicles_id', $post)) {
        $input['plugin_vehiclescheduler_vehicles_id'] = PluginVehicleschedulerInput::int(
            $post,
            'plugin_vehiclescheduler_vehicles_id',
            0,
            0
        );
    }

    if (array_key_exists('plugin_vehiclescheduler_drivers_id', $post)) {
        $input['plugin_vehiclescheduler_drivers_id'] = PluginVehicleschedulerInput::int(
            $post,
            'plugin_vehiclescheduler_drivers_id',
            0,
            0
        );
    }

    if (array_key_exists('begin_date', $post)) {
        $input['begin_date'] = PluginVehicleschedulerInput::datetime($post, 'begin_date');
    }

    if (array_key_exists('end_date', $post)) {
        $input['end_date'] = PluginVehicleschedulerInput::datetime($post, 'end_date');
    }

    if (array_key_exists('destination', $post)) {
        $input['destination'] = PluginVehicleschedulerInput::string($post, 'destination', 255);
    }

    if (array_key_exists('purpose', $post)) {
        $input['purpose'] = PluginVehicleschedulerInput::text($post, 'purpose', 2000);
    }

    if (array_key_exists('passengers', $post)) {
        $input['passengers'] = PluginVehicleschedulerInput::int($post, 'passengers', 1, 1, 999);
    }

    if (array_key_exists('contact_phone', $post)) {
        $input['contact_phone'] = PluginVehicleschedulerInput::string($post, 'contact_phone', 30);
    }

    if (array_key_exists('department', $post)) {
        $input['department'] = PluginVehicleschedulerInput::string($post, 'department', 255);
    }

    if (array_key_exists('comment', $post)) {
        $input['comment'] = PluginVehicleschedulerInput::text($post, 'comment', 5000);
    }

    return $input;
};

$render_dropdown = static function (string $itemtype, string $name, int $value, bool $disabled = false): string {
    ob_start();
    \Dropdown::show($itemtype, [
        'name'                => $name,
        'value'               => $value,
        'display_emptychoice' => true,
        'disabled'            => $disabled,
        'width'               => '100%',
        'rand'                => mt_rand(),
    ]);

    return (string)ob_get_clean();
};

$get_user_label = static function (int $user_id): string {
    if ($user_id <= 0) {
        return 'Não informado';
    }

    $label = \Dropdown::getDropdownName('glpi_users', $user_id);

    return $label !== '' ? $label : 'Não informado';
};

$get_status_meta = static function (int $status): array {
    switch ($status) {
        case \PluginVehicleschedulerSchedule::STATUS_APPROVED:
            return [
                'label' => 'Aprovada',
                'class' => 'vs-status-badge--approved',
                'icon'  => 'ti ti-check',
            ];

        case \PluginVehicleschedulerSchedule::STATUS_REJECTED:
            return [
                'label' => 'Recusada',
                'class' => 'vs-status-badge--rejected',
                'icon'  => 'ti ti-x',
            ];

        case \PluginVehicleschedulerSchedule::STATUS_PENDING:
        default:
            return [
                'label' => 'Pendente',
                'class' => 'vs-status-badge--pending',
                'icon'  => 'ti ti-hourglass',
            ];
    }
};

$validate_assignment_requirement = static function (array $post, bool $assignments_required): ?string {
    if (!$assignments_required) {
        return null;
    }

    if ((int)($post['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
        return 'Selecione a viatura.';
    }

    if ((int)($post['plugin_vehiclescheduler_drivers_id'] ?? 0) <= 0) {
        return 'Selecione o motorista.';
    }

    return null;
};

$current_user_id = (int)\Session::getLoginUserID();
$current_entity  = method_exists(\Session::class, 'getActiveEntity')
    ? (int)\Session::getActiveEntity()
    : 0;

if (isset($_POST['add'])) {
    if (!$can_create) {
        \Html::displayRightError();
    }

    $assignment_error = $validate_assignment_requirement($request_post, $can_manage_assignments);
    if ($assignment_error !== null) {
        \Session::addMessageAfterRedirect($assignment_error, true, ERROR);
        \Html::back();
    }

    $base_input = [
        'users_id'                             => $current_user_id,
        'entities_id'                          => $current_entity,
        'groups_id'                            => 0,
        'tickets_id'                           => 0,
        'plugin_vehiclescheduler_vehicles_id'  => 0,
        'plugin_vehiclescheduler_drivers_id'   => 0,
        'status'                               => \PluginVehicleschedulerSchedule::STATUS_PENDING,
        'begin_date'                           => null,
        'end_date'                             => null,
        'destination'                          => '',
        'purpose'                              => '',
        'passengers'                           => 1,
        'department'                           => '',
        'contact_phone'                        => '',
        'comment'                              => '',
    ];

    $input = $merge_schedule_input($base_input, $request_post, $current_user_id, $current_entity);
    $input['status'] = \PluginVehicleschedulerSchedule::STATUS_PENDING;

    $schedule->check(-1, CREATE, $input);

    $new_id = $schedule->add($input);

    \Html::redirect($getSuccessRedirect());
} elseif (isset($_POST['update'])) {
    if ($post_id <= 0) {
        \Session::addMessageAfterRedirect('ID da reserva inválido.', true, ERROR);
        \Html::back();
    }

    if (!$schedule->getFromDB($post_id)) {
        \Session::addMessageAfterRedirect('Reserva não encontrada.', true, ERROR);
        \Html::back();
    }

    $can_update_own_request = \PluginVehicleschedulerSchedule::canUpdateOwnPendingRequest($post_id);
    $can_update_form        = $can_edit || $can_assign || $can_update_own_request;

    if (!$can_update_form) {
        \Html::displayRightError();
    }

    $assignment_error = $validate_assignment_requirement($request_post, $can_manage_assignments);
    if ($assignment_error !== null) {
        \Session::addMessageAfterRedirect($assignment_error, true, ERROR);
        \Html::back();
    }

    if (!$can_edit && !$can_update_own_request && $can_assign) {
        $schedule->check($post_id, UPDATE);

        $update_input = [
            'id'                                  => $post_id,
            'plugin_vehiclescheduler_vehicles_id' => (int) $request_post['plugin_vehiclescheduler_vehicles_id'],
            'plugin_vehiclescheduler_drivers_id'  => (int) $request_post['plugin_vehiclescheduler_drivers_id'],
        ];

        $schedule->update($update_input);
        \Session::addMessageAfterRedirect('Atribuição atualizada com sucesso.');
    } else {
        $input = $merge_schedule_input($schedule->fields, $request_post, $current_user_id, $current_entity);
        $input['id'] = $post_id;

        $schedule->check($post_id, UPDATE, $input);
        $schedule->update($input);
        \Session::addMessageAfterRedirect('Reserva atualizada com sucesso.');
    }

    \Html::redirect($getSuccessRedirect());
} elseif (isset($_POST['delete'])) {
    if (!$can_edit) {
        \Html::displayRightError();
    }

    if ($post_id <= 0) {
        \Session::addMessageAfterRedirect('ID da reserva inválido.', true, ERROR);
        \Html::back();
    }

    $schedule->check($post_id, DELETE, $request_post);
    $schedule->delete($request_post);
    $schedule->redirectToList();
} elseif (isset($_POST['save_and_approve'])) {
    if ($post_id <= 0) {
        \Session::addMessageAfterRedirect('ID da reserva inválido.', true, ERROR);
        \Html::back();
    }

    if (!$schedule->getFromDB($post_id)) {
        \Session::addMessageAfterRedirect('Reserva não encontrada.', true, ERROR);
        \Html::back();
    }

    if (!$can_approval_action) {
        \Html::displayRightError();
    }

    $assignment_error = $validate_assignment_requirement($request_post, true);
    if ($assignment_error !== null) {
        \Session::addMessageAfterRedirect($assignment_error, true, ERROR);
        \Html::back();
    }

    try {
        if ($can_edit) {
            $input = $merge_schedule_input($schedule->fields, $request_post, $current_user_id, $current_entity);
            $input['id'] = $post_id;
            $schedule->check($post_id, UPDATE, $input);
            $schedule->update($input);
        } else {
            $schedule->check($post_id, UPDATE);
            $schedule->update([
                'id'                                  => $post_id,
                'plugin_vehiclescheduler_vehicles_id' => (int) $request_post['plugin_vehiclescheduler_vehicles_id'],
                'plugin_vehiclescheduler_drivers_id'  => (int) $request_post['plugin_vehiclescheduler_drivers_id'],
            ]);
        }

        $schedule->approveReservation($post_id);
        \Session::addMessageAfterRedirect('Reserva aprovada com sucesso.');
    } catch (\RuntimeException $exception) {
        \Session::addMessageAfterRedirect($exception->getMessage(), true, ERROR);
    }

    \Html::redirect(
        plugin_vehiclescheduler_get_front_url('schedule.php') . '?status=' . (int)\PluginVehicleschedulerSchedule::STATUS_PENDING
    );
} elseif (isset($_POST['reject_reservation'])) {
    if ($post_id <= 0) {
        \Session::addMessageAfterRedirect('ID da reserva inválido.', true, ERROR);
        \Html::back();
    }

    if (!$schedule->getFromDB($post_id)) {
        \Session::addMessageAfterRedirect('Reserva não encontrada.', true, ERROR);
        \Html::back();
    }

    if (!$can_approval_action) {
        \Html::displayRightError();
    }

    $justification = trim((string) $request_post['rejection_justification']);

    try {
        $schedule->rejectReservation($post_id, $justification);
        \Session::addMessageAfterRedirect('Reserva recusada com sucesso.');
    } catch (\RuntimeException $exception) {
        \Session::addMessageAfterRedirect($exception->getMessage(), true, ERROR);
    }

    \Html::redirect(
        plugin_vehiclescheduler_get_front_url('schedule.php') . '?status=' . (int)\PluginVehicleschedulerSchedule::STATUS_PENDING
    );
} else {
    if (!$can_open_form) {
        \Html::displayRightError();
    }

    $fields = [
        'id'                                  => 0,
        'plugin_vehiclescheduler_vehicles_id' => 0,
        'plugin_vehiclescheduler_drivers_id'  => 0,
        'users_id'                            => $current_user_id,
        'entities_id'                         => $current_entity,
        'groups_id'                           => 0,
        'tickets_id'                          => 0,
        'status'                              => \PluginVehicleschedulerSchedule::STATUS_PENDING,
        'begin_date'                          => null,
        'end_date'                            => null,
        'destination'                         => '',
        'purpose'                             => '',
        'passengers'                          => 1,
        'department'                          => '',
        'contact_phone'                       => '',
        'comment'                             => '',
        'approved_by'                         => 0,
        'approval_date'                       => null,
        'rejection_justification'             => '',
        'date_creation'                       => null,
        'date_mod'                            => null,
    ];

    if ($id > 0) {
        if (!$schedule->getFromDB($id)) {
            \Session::addMessageAfterRedirect('Reserva não encontrada.', true, ERROR);
            \Html::redirect(plugin_vehiclescheduler_get_front_url('schedule.php'));
        }

        $fields = array_merge($fields, $schedule->fields);
    }

    $is_existing               = $id > 0;
    $can_update_own_request    = $is_existing && \PluginVehicleschedulerSchedule::canUpdateOwnPendingRequest((int)$fields['id']);
    $can_show_approval_card    = $is_existing && $can_approval_action && $schedule->canBeApproved();
    $can_show_assignment_focus = $is_existing && $schedule->canBeApproved() && $can_manage_assignments;
    $can_edit_request_fields   = ($id === 0 && $can_create) || $can_edit || $can_update_own_request;
    $status_meta               = $get_status_meta((int)$fields['status']);
    $vehicleCompatibilityMap   = $can_manage_assignments
        ? \PluginVehicleschedulerVehicle::getVehicleRequiredCNHMap()
        : [];
    $driverCompatibilityMap    = $can_manage_assignments
        ? \PluginVehicleschedulerDriver::getApprovedDriverCategoryMap()
        : [];

    \Html::header(
        'Reservas',
        $form_action,
        'tools',
        \PluginVehicleschedulerMenu::class,
        'management'
    );

    plugin_vehiclescheduler_load_css();
    plugin_vehiclescheduler_enhance_ui();

    if ($can_approve || $can_edit) {
        plugin_vehiclescheduler_render_back_to_management();
    } else {
        echo "<div class='mb-2'>";
        echo "    <button type='button' class='btn btn-secondary' data-vs-history-back='true'>";
        echo "        <i class='ti ti-arrow-left'></i> Voltar";
        echo "    </button>";
        echo "</div>";
    }

    $feedback_js_file = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/form-feedback.js';
    $feedback_js_ver  = is_file($feedback_js_file) ? filemtime($feedback_js_file) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $feedback_js_url  = plugin_vehiclescheduler_get_public_url('js/form-feedback.js') . '?v=' . $feedback_js_ver;

    $js_file = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/schedule-form.js';
    $js_ver  = is_file($js_file) ? filemtime($js_file) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $js_url  = plugin_vehiclescheduler_get_public_url('js/schedule-form.js') . '?v=' . $js_ver;

    echo "<script src='" . $h($feedback_js_url) . "' defer></script>";
    echo "<script src='" . $h($js_url) . "' defer></script>";

    $page_title = $id > 0 ? 'Reserva #' . (int)$id : 'Nova solicitação';
    $page_subtitle = $can_show_assignment_focus
        ? 'Revise os dados e atribua viatura e motorista antes da decisão.'
        : 'Dados da viagem, solicitante, período e finalidade.';

    echo "<div id='vs-schedule-form-root' class='vs-page vs-page-schedule-form'>";
    echo "    <div class='vs-page-header'>";
    echo "        <div class='vs-header-content'>";
    echo "            <div class='vs-header-title'>";
    echo "                <div class='vs-header-icon-wrapper'>";
    echo "                    <i class='ti ti-calendar-event vs-header-icon'></i>";
    echo "                </div>";
    echo "                <div>";
    echo "                    <h2>" . $h($page_title) . "</h2>";
    echo "                    <p class='vs-page-subtitle'>" . $h($page_subtitle) . "</p>";
    echo "                </div>";
    echo "            </div>";
    echo "            <span class='vs-status-badge " . $h($status_meta['class']) . "'>";
    echo "                <i class='" . $h($status_meta['icon']) . "'></i>";
    echo "                <span>" . $h($status_meta['label']) . "</span>";
    echo "            </span>";
    echo "        </div>";
    echo "    </div>";

    $content_classes = 'vs-content-card vs-content-card--compact';
    if ($can_show_assignment_focus) {
        $content_classes .= ' vs-content-card--approval-focus';
    }

    echo "<section class='" . $h($content_classes) . "'>";

    if ($can_show_approval_card) {
        echo "    <section class='vs-approval-card vs-approval-card--top'>";
        echo "        <div class='vs-approval-card__header'>";
        echo "            <div>";
        echo "                <h3>Aprovação da reserva</h3>";
        echo "                <p>Como aprovador, defina viatura e motorista e então use Salvar e aprovar, ou recuse com justificativa.</p>";
        echo "            </div>";
        echo "            <div class='vs-approval-actions'>";
        echo "                <button type='submit' name='save_and_approve' value='1' class='vs-btn-approve' form='vs-schedule-main-form'>";
        echo "                    <i class='ti ti-check'></i>";
        echo "                    <span>Salvar e aprovar</span>";
        echo "                </button>";
        echo "                <button type='button' class='vs-btn-reject-toggle' data-vs-toggle-rejection>";
        echo "                    <i class='ti ti-x'></i>";
        echo "                    <span>Recusar</span>";
        echo "                </button>";
        echo "            </div>";
        echo "        </div>";
        echo "        <form method='post' action='" . $h($form_action) . "' class='vs-rejection-form' data-vs-rejection-form hidden>";
        echo "            <input type='hidden' name='_glpi_csrf_token' value='" . $h($csrf_token) . "'>";
        echo "            <input type='hidden' name='id' value='" . (int)$id . "'>";
        echo "            <label for='vs-rejection-justification'><strong>Justificativa da recusa</strong></label>";
        echo "            <textarea id='vs-rejection-justification' name='rejection_justification' rows='2' class='vs-input-glass' placeholder='Informe o motivo da recusa.' required></textarea>";
        echo "            <div class='vs-rejection-form__actions'>";
        echo "                <button type='submit' name='reject_reservation' class='vs-btn-reject'>";
        echo "                    <i class='ti ti-alert-circle'></i>";
        echo "                    <span>Confirmar recusa</span>";
        echo "                </button>";
        echo "                <button type='button' class='vs-btn-reject-cancel' data-vs-cancel-rejection>";
        echo "                    <i class='ti ti-arrow-back-up'></i>";
        echo "                    <span>Cancelar</span>";
        echo "                </button>";
        echo "            </div>";
        echo "        </form>";
        echo "    </section>";
    }

    echo "<form id='vs-schedule-main-form' method='post' action='" . $h($form_action) . "' class='vs-schedule-form-shell'>";
    echo "    <input type='hidden' name='_glpi_csrf_token' value='" . $h($csrf_token) . "'>";
    echo "    <input type='hidden' name='id' value='" . (int)$fields['id'] . "'>";
    echo "    <input type='hidden' name='users_id' value='" . (int)$fields['users_id'] . "'>";
    echo "    <input type='hidden' name='entities_id' value='" . (int)$fields['entities_id'] . "'>";
    echo "    <input type='hidden' name='groups_id' value='" . (int)$fields['groups_id'] . "'>";
    echo "    <input type='hidden' name='tickets_id' value='" . (int)$fields['tickets_id'] . "'>";
    echo "    <input type='hidden' name='status' value='" . (int)$fields['status'] . "'>";

    if (!$can_manage_assignments) {
        echo "    <input type='hidden' name='plugin_vehiclescheduler_vehicles_id' value='" . (int)$fields['plugin_vehiclescheduler_vehicles_id'] . "'>";
        echo "    <input type='hidden' name='plugin_vehiclescheduler_drivers_id' value='" . (int)$fields['plugin_vehiclescheduler_drivers_id'] . "'>";
    }

    if ($can_manage_assignments) {
        echo "    <div class='vs-schedule-compatibility-data' data-vs-schedule-compatibility";
        echo " data-vs-vehicle-map='" . $json_attr($vehicleCompatibilityMap) . "'";
        echo " data-vs-driver-map='" . $json_attr($driverCompatibilityMap) . "'";
        echo " hidden></div>";
    }

    echo "    <div class='vs-form-section-header'>";
    echo "        <div>";
    echo "            <h3>Detalhes da reserva</h3>";
    echo "            <p>Preencha os dados da solicitação e, no fluxo de aprovação, faça a atribuição de recursos antes da decisão final.</p>";
    echo "        </div>";
    echo "        <span class='vs-status-badge " . $h($status_meta['class']) . "'>";
    echo "            <i class='" . $h($status_meta['icon']) . "'></i>";
    echo "            <span>" . $h($status_meta['label']) . "</span>";
    echo "        </span>";
    echo "    </div>";

    echo "    <div class='vs-form-info-strip'>";
    echo "        <div class='vs-form-info-chip'>";
    echo "            <i class='ti ti-user'></i>";
    echo "            <span><strong>Solicitante:</strong> " . $h($get_user_label((int)$fields['users_id'])) . "</span>";
    echo "        </div>";

    if (!empty($fields['date_creation'])) {
        echo "    <div class='vs-form-info-chip'>";
        echo "        <i class='ti ti-clock'></i>";
        echo "        <span><strong>Criado em:</strong> " . $h(\Html::convDateTime($fields['date_creation'])) . "</span>";
        echo "    </div>";
    }

    if (!empty($fields['date_mod'])) {
        echo "    <div class='vs-form-info-chip'>";
        echo "        <i class='ti ti-refresh'></i>";
        echo "        <span><strong>Atualizado em:</strong> " . $h(\Html::convDateTime($fields['date_mod'])) . "</span>";
        echo "    </div>";
    }

    if ((int)$fields['approved_by'] > 0 && !empty($fields['approval_date'])) {
        echo "    <div class='vs-form-info-chip'>";
        echo "        <i class='ti ti-user-check'></i>";
        echo "        <span><strong>Aprovador:</strong> " . $h($get_user_label((int)$fields['approved_by'])) . " em " . $h(\Html::convDateTime($fields['approval_date'])) . "</span>";
        echo "    </div>";
    }

    echo "    </div>";

    echo "    <div class='vs-schedule-form-grid'>";
    echo "        <div class='vs-form-field'>";
    echo "            <label for='vs-begin-date'>Saída *</label>";
    echo "            <input id='vs-begin-date' class='vs-input-glass' type='datetime-local' name='begin_date' value='" . $h($to_date_time_local($fields['begin_date'])) . "' " . (!$can_edit_request_fields ? 'readonly' : '') . " required>";
    echo "        </div>";

    echo "        <div class='vs-form-field'>";
    echo "            <label for='vs-end-date'>Retorno *</label>";
    echo "            <input id='vs-end-date' class='vs-input-glass' type='datetime-local' name='end_date' value='" . $h($to_date_time_local($fields['end_date'])) . "' " . (!$can_edit_request_fields ? 'readonly' : '') . " required>";
    echo "        </div>";

    echo "        <div class='vs-form-field vs-form-field--full'>";
    echo "            <div class='vs-date-validation-note' data-vs-date-validation hidden></div>";
    echo "        </div>";

    echo "        <div class='vs-form-field'>";
    echo "            <label>Status</label>";
    echo "            <div class='vs-form-status-display'>";
    echo "                <span class='vs-status-badge " . $h($status_meta['class']) . "'>";
    echo "                    <i class='" . $h($status_meta['icon']) . "'></i>";
    echo "                    <span>" . $h($status_meta['label']) . "</span>";
    echo "                </span>";
    echo "            </div>";
    echo "        </div>";

    if ($can_manage_assignments) {
        echo "        <div class='vs-form-field'>";
        echo "            <label>Viatura *</label>";
        echo              $render_dropdown(\PluginVehicleschedulerVehicle::class, 'plugin_vehiclescheduler_vehicles_id', (int)$fields['plugin_vehiclescheduler_vehicles_id'], false);
        echo "            <div class='vs-inline-help' data-vs-vehicle-compatibility-note hidden></div>";
        echo "        </div>";

        echo "        <div class='vs-form-field'>";
        echo "            <label>Motorista *</label>";
        echo              $render_dropdown(\PluginVehicleschedulerDriver::class, 'plugin_vehiclescheduler_drivers_id', (int)$fields['plugin_vehiclescheduler_drivers_id'], false);
        echo "            <div class='vs-inline-help' data-vs-driver-compatibility-note hidden></div>";
        echo "            <div class='vs-schedule-driver-quick-list' data-vs-driver-quick-list hidden></div>";
        echo "        </div>";
    }

    echo "        <div class='vs-form-field'>";
    echo "            <label for='vs-passengers'>Passageiros</label>";
    echo "            <input id='vs-passengers' class='vs-input-glass' type='number' min='1' name='passengers' value='" . (int)$fields['passengers'] . "' " . (!$can_edit_request_fields ? 'readonly' : '') . ">";
    echo "        </div>";

    echo "        <div class='vs-form-field'>";
    echo "            <label for='vs-contact-phone'>Telefone *</label>";
    echo "            <input id='vs-contact-phone' class='vs-input-glass' type='text' name='contact_phone' value='" . $h($fields['contact_phone']) . "' " . (!$can_edit_request_fields ? 'readonly' : '') . " required>";
    echo "        </div>";

    echo "        <div class='vs-form-field'>";
    echo "            <label for='vs-destination'>Destino *</label>";
    echo "            <input id='vs-destination' class='vs-input-glass' type='text' name='destination' value='" . $h($fields['destination']) . "' " . (!$can_edit_request_fields ? 'readonly' : '') . " required>";
    echo "        </div>";

    echo "        <div class='vs-form-field vs-form-field--full'>";
    echo "            <label for='vs-purpose'>Finalidade *</label>";
    echo "            <textarea id='vs-purpose' class='vs-input-glass' name='purpose' rows='2' " . (!$can_edit_request_fields ? 'readonly' : '') . " required>" . $h($fields['purpose']) . "</textarea>";
    echo "        </div>";

    if ($can_edit || trim((string)$fields['comment']) !== '') {
        echo "    <div class='vs-form-field vs-form-field--full'>";
        echo "        <label for='vs-comment'>Observações internas</label>";
        echo "        <textarea id='vs-comment' class='vs-input-glass' name='comment' rows='2' " . (!$can_edit ? 'readonly' : '') . ">" . $h($fields['comment']) . "</textarea>";
        echo "    </div>";
    }

    if ((int)$fields['status'] === \PluginVehicleschedulerSchedule::STATUS_REJECTED && trim((string)$fields['rejection_justification']) !== '') {
        echo "    <div class='vs-form-field vs-form-field--full'>";
        echo "        <label>Justificativa da recusa</label>";
        echo "        <div class='vs-rejection-note'>";
        echo "            <i class='ti ti-message-report'></i>";
        echo "            <span>" . $h($fields['rejection_justification']) . "</span>";
        echo "        </div>";
        echo "    </div>";
    }

    echo "    </div>";

    if (($id === 0 && $can_create) || $can_update_own_request || ($is_existing && $can_edit) || ($is_existing && $can_assign)) {
        $footer_title    = 'Ações da reserva';
        $footer_subtitle = 'Revise os dados e conclua a operação desta reserva.';

        if ($id === 0) {
            $footer_title    = 'Nova solicitação';
            $footer_subtitle = 'Confira os dados antes de registrar a solicitação.';
        } elseif ($can_update_own_request) {
            $footer_title    = 'Sua solicitação';
            $footer_subtitle = 'Você pode ajustar os dados enquanto a reserva estiver pendente.';
        } elseif ($is_existing && $can_assign && !$can_edit) {
            $footer_title    = 'Atribuição de recursos';
            $footer_subtitle = 'Defina viatura e motorista antes da aprovação final.';
        }

        echo "    <div class='vs-content-card__footer'>";
        echo "        <div class='vs-content-card__footer-copy'>";
        echo "            <span class='vs-content-card__footer-title'>" . $h($footer_title) . "</span>";
        echo "            <span class='vs-content-card__footer-subtitle'>" . $h($footer_subtitle) . "</span>";
        echo "        </div>";
        echo "        <div class='vs-form-actions'>";

        if ($id === 0 && $can_create) {
            echo "        <button type='submit' name='add' class='vs-btn-save'>";
            echo "            <i class='ti ti-send'></i>";
            echo "            <span>Solicitar viagem</span>";
            echo "        </button>";
        } elseif ($can_update_own_request) {
            echo "        <button type='submit' name='update' class='vs-btn-save'>";
            echo "            <i class='ti ti-device-floppy'></i>";
            echo "            <span>Salvar reserva</span>";
            echo "        </button>";
        } elseif ($is_existing && $can_assign && !$can_edit) {
            echo "        <button type='submit' name='update' class='vs-btn-save'>";
            echo "            <i class='ti ti-device-floppy'></i>";
            echo "            <span>Salvar atribuição</span>";
            echo "        </button>";
        } elseif ($is_existing && $can_edit) {
            echo "        <button type='submit' name='update' class='vs-btn-save'>";
            echo "            <i class='ti ti-device-floppy'></i>";
            echo "            <span>Salvar reserva</span>";
            echo "        </button>";

            echo "        <button type='submit' name='delete' class='vs-btn-delete' data-confirm-message='Deseja excluir esta reserva?'>";
            echo "            <i class='ti ti-trash'></i>";
            echo "            <span>Excluir reserva</span>";
            echo "        </button>";
        }

        echo "        </div>";
        echo "    </div>";
    }

    echo "</form>";
    echo "</section>";
    echo "</div>";

    \Html::footer();
}
