<?php


include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

require_once(__DIR__ . '/maintenance.render.php');

Session::checkRight('plugin_vehiclescheduler', UPDATE);

$item = new PluginVehicleschedulerMaintenance();

$rootDoc = plugin_vehiclescheduler_get_root_doc();

$post = $_POST;
$postId = PluginVehicleschedulerInput::int($_POST, 'id', 0, 0);
$getId = PluginVehicleschedulerInput::int($_GET, 'id', 0, 0);
$maintenanceId = $postId > 0 ? $postId : $getId;

if (isset($_POST['add']) || isset($_POST['update'])) {
    $post['id'] = $postId;
    $post['type'] = PluginVehicleschedulerInput::int(
        $_POST,
        'type',
        PluginVehicleschedulerMaintenance::TYPE_PREVENTIVE,
        PluginVehicleschedulerMaintenance::TYPE_PREVENTIVE,
        PluginVehicleschedulerMaintenance::TYPE_CORRECTIVE
    );
    $post['plugin_vehiclescheduler_vehicles_id'] = PluginVehicleschedulerInput::int(
        $_POST,
        'plugin_vehiclescheduler_vehicles_id',
        0,
        0
    );
    $post['plugin_vehiclescheduler_incidents_id'] = PluginVehicleschedulerInput::int(
        $_POST,
        'plugin_vehiclescheduler_incidents_id',
        0,
        0
    );
    $post['scheduled_date'] = PluginVehicleschedulerInput::date($_POST, 'scheduled_date', null) ?? '';
    $post['completion_date'] = PluginVehicleschedulerInput::date($_POST, 'completion_date', null) ?? '';
    $post['supplier'] = PluginVehicleschedulerInput::string($_POST, 'supplier', 255);
    $post['cost'] = PluginVehicleschedulerInput::string($_POST, 'cost', 50, '0');
    $post['mileage'] = PluginVehicleschedulerInput::int($_POST, 'mileage', 0, 0);
    $post['status'] = PluginVehicleschedulerInput::int(
        $_POST,
        'status',
        PluginVehicleschedulerMaintenance::STATUS_SCHEDULED,
        PluginVehicleschedulerMaintenance::STATUS_SCHEDULED,
        PluginVehicleschedulerMaintenance::STATUS_CANCELLED
    );
    $post['description'] = PluginVehicleschedulerInput::text($_POST, 'description', 5000);
}

if (isset($_POST['add'])) {
    $item->check(-1, CREATE, $post);

    if ($newId = $item->add($post)) {
        Session::addMessageAfterRedirect('Manutencao agendada com sucesso!', false, INFO);
        Html::redirect(plugin_vehiclescheduler_get_front_url('maintenance.form.php') . '?id=' . (int) $newId);
    }

    Html::back();
} elseif (isset($_POST['update'])) {
    $item->check($postId, UPDATE);
    $item->update($post);
    Session::addMessageAfterRedirect('Manutencao atualizada com sucesso!', false, INFO);
    Html::redirect(plugin_vehiclescheduler_get_front_url('maintenance.form.php') . '?id=' . $postId);
} elseif (isset($_POST['delete'])) {
    $deleteInput = ['id' => $postId];
    $item->check($postId, DELETE);
    $item->delete($deleteInput);
    Html::redirect(plugin_vehiclescheduler_get_front_url('maintenance.php'));
} elseif (isset($_POST['purge'])) {
    $purgeInput = ['id' => $postId];
    $item->check($postId, PURGE);
    $item->delete($purgeInput, 1);
    Html::redirect(plugin_vehiclescheduler_get_front_url('maintenance.php'));
} else {
    $item->checkGlobal(READ);

    if ($maintenanceId > 0) {
        $item->check($maintenanceId, READ);
        $item->getFromDB($maintenanceId);
    } else {
        $incidentId = PluginVehicleschedulerInput::int($_GET, 'plugin_vehiclescheduler_incidents_id', 0, 0);
        $item->fields = [
            'type'                               => $incidentId > 0
                ? PluginVehicleschedulerMaintenance::TYPE_CORRECTIVE
                : PluginVehicleschedulerMaintenance::TYPE_PREVENTIVE,
            'plugin_vehiclescheduler_vehicles_id'  => PluginVehicleschedulerInput::int(
                $_GET,
                'plugin_vehiclescheduler_vehicles_id',
                0,
                0
            ),
            'plugin_vehiclescheduler_incidents_id' => $incidentId,
            'scheduled_date'                     => '',
            'completion_date'                    => '',
            'supplier'                           => '',
            'cost'                               => '0.00',
            'mileage'                            => 0,
            'status'                             => PluginVehicleschedulerMaintenance::STATUS_SCHEDULED,
            'description'                        => '',
            'entities_id'                        => (int) ($_SESSION['glpiactive_entity'] ?? 0),
        ];
    }

    $incidentLinkHtml = null;
    $incidentId = (int) ($item->fields['plugin_vehiclescheduler_incidents_id'] ?? 0);

    if ($incidentId > 0) {
        $incident = new PluginVehicleschedulerIncident();

        if ($incident->getFromDB($incidentId)) {
            $incidentLabel = plugin_vehiclescheduler_maintenance_escape((string) ($incident->fields['name'] ?? ('Incidente #' . $incidentId)));
            $incidentUrl = plugin_vehiclescheduler_get_front_url('incident.form.php') . '?id=' . $incidentId;
            $incidentLinkHtml = "<a href='" . plugin_vehiclescheduler_maintenance_escape($incidentUrl) . "'>" . $incidentLabel . '</a>';
        }
    }

    Html::header(
        PluginVehicleschedulerMaintenance::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'tools',
        'PluginVehicleschedulerMenug',
        'maintenance'
    );

    plugin_vehiclescheduler_load_css();
    plugin_vehiclescheduler_enhance_ui();

    $isManager = PluginVehicleschedulerProfile::canEditManagement();
    $backUrl = $isManager
        ? plugin_vehiclescheduler_get_front_url('management.php')
        : plugin_vehiclescheduler_get_front_url('requester.php');

    plugin_vehiclescheduler_render_maintenance_form(
        $item,
        $maintenanceId,
        $rootDoc,
        $backUrl,
        $incidentLinkHtml
    );

    $feedbackJsFile = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/form-feedback.js';
    $feedbackJsVer = is_file($feedbackJsFile) ? filemtime($feedbackJsFile) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $feedbackJsUrl = plugin_vehiclescheduler_get_public_url('js/form-feedback.js') . '?v=' . $feedbackJsVer;
    $jsFile = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/maintenance-form.js';
    $jsVer = is_file($jsFile) ? filemtime($jsFile) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $jsUrl = plugin_vehiclescheduler_get_public_url('js/maintenance-form.js') . '?v=' . $jsVer;

    echo "<script src='" . plugin_vehiclescheduler_maintenance_escape($feedbackJsUrl) . "' defer></script>";
    echo "<script src='" . plugin_vehiclescheduler_maintenance_escape($jsUrl) . "' defer></script>";

    Html::footer();
}
