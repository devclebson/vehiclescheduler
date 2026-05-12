<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;
require_once(__DIR__ . '/insuranceclaim.render.php');

Session::checkRight('plugin_vehiclescheduler', UPDATE);

$item = new PluginVehicleschedulerInsuranceclaim();

$rootDoc = plugin_vehiclescheduler_get_root_doc();

$post = $_POST;
$postId = PluginVehicleschedulerInput::int($_POST, 'id', 0, 0);
$getId = PluginVehicleschedulerInput::int($_GET, 'id', 0, 0);
$claimId = $postId > 0 ? $postId : $getId;

if (isset($_POST['add']) || isset($_POST['update'])) {
    $post['id'] = $postId;
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
    $post['claim_number'] = PluginVehicleschedulerInput::string($_POST, 'claim_number', 100);
    $post['status'] = PluginVehicleschedulerInput::int(
        $_POST,
        'status',
        PluginVehicleschedulerInsuranceclaim::STATUS_OPENED,
        PluginVehicleschedulerInsuranceclaim::STATUS_OPENED,
        PluginVehicleschedulerInsuranceclaim::STATUS_CLOSED
    );
    $post['opening_date'] = PluginVehicleschedulerInput::date($_POST, 'opening_date', date('Y-m-d')) ?? '';
    $post['closing_date'] = PluginVehicleschedulerInput::date($_POST, 'closing_date', null) ?? '';
    $post['insurance_company'] = PluginVehicleschedulerInput::string($_POST, 'insurance_company', 255);
    $post['contact_name'] = PluginVehicleschedulerInput::string($_POST, 'contact_name', 255);
    $post['description'] = PluginVehicleschedulerInput::text($_POST, 'description', 5000);
    $post['estimated_value'] = PluginVehicleschedulerInput::string($_POST, 'estimated_value', 50, '0');
    $post['approved_value'] = PluginVehicleschedulerInput::string($_POST, 'approved_value', 50, '0');
}

if (isset($_POST['add'])) {
    $item->check(-1, CREATE, $post);

    if ($newId = $item->add($post)) {
        Session::addMessageAfterRedirect('Sinistro aberto com sucesso!', false, INFO);
        Html::redirect(plugin_vehiclescheduler_get_front_url('insuranceclaim.form.php') . '?id=' . (int) $newId);
    }

    Html::back();
} elseif (isset($_POST['update'])) {
    $item->check($postId, UPDATE);
    $item->update($post);
    Session::addMessageAfterRedirect('Sinistro atualizado com sucesso!', false, INFO);
    Html::redirect(plugin_vehiclescheduler_get_front_url('insuranceclaim.form.php') . '?id=' . $postId);
} elseif (isset($_POST['delete'])) {
    $deleteInput = ['id' => $postId];
    $item->check($postId, DELETE);
    $item->delete($deleteInput);
    Html::redirect(plugin_vehiclescheduler_get_front_url('insuranceclaim.php'));
} elseif (isset($_POST['purge'])) {
    $purgeInput = ['id' => $postId];
    $item->check($postId, PURGE);
    $item->delete($purgeInput, 1);
    Html::redirect(plugin_vehiclescheduler_get_front_url('insuranceclaim.php'));
} else {
    $item->checkGlobal(READ);

    if ($claimId > 0) {
        $item->check($claimId, READ);
        $item->getFromDB($claimId);
    } else {
        $item->fields = [
            'plugin_vehiclescheduler_vehicles_id'  => PluginVehicleschedulerInput::int(
                $_GET,
                'plugin_vehiclescheduler_vehicles_id',
                0,
                0
            ),
            'plugin_vehiclescheduler_incidents_id' => PluginVehicleschedulerInput::int(
                $_GET,
                'plugin_vehiclescheduler_incidents_id',
                0,
                0
            ),
            'claim_number'                         => '',
            'status'                               => PluginVehicleschedulerInsuranceclaim::STATUS_OPENED,
            'opening_date'                         => date('Y-m-d'),
            'closing_date'                         => '',
            'insurance_company'                    => '',
            'contact_name'                         => '',
            'estimated_value'                      => '0.00',
            'approved_value'                       => '0.00',
            'description'                          => '',
            'entities_id'                          => (int) ($_SESSION['glpiactive_entity'] ?? 0),
        ];
    }

    $incidentLinkHtml = null;
    $incidentId = (int) ($item->fields['plugin_vehiclescheduler_incidents_id'] ?? 0);

    if ($incidentId > 0) {
        $incident = new PluginVehicleschedulerIncident();

        if ($incident->getFromDB($incidentId)) {
            $incidentLabel = plugin_vehiclescheduler_insuranceclaim_escape((string) ($incident->fields['name'] ?? ('Incidente #' . $incidentId)));
            $incidentUrl = plugin_vehiclescheduler_get_front_url('incident.form.php') . '?id=' . $incidentId;
            $incidentLinkHtml = "<a href='" . plugin_vehiclescheduler_insuranceclaim_escape($incidentUrl) . "'>" . $incidentLabel . '</a>';
        }
    }

    Html::header(
        PluginVehicleschedulerInsuranceclaim::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'tools',
        'PluginVehicleschedulerMenug',
        'insurance'
    );

    plugin_vehiclescheduler_load_css();
    plugin_vehiclescheduler_enhance_ui();

    $isManager = PluginVehicleschedulerProfile::canEditManagement();
    $backUrl = $isManager
        ? plugin_vehiclescheduler_get_front_url('management.php')
        : plugin_vehiclescheduler_get_front_url('requester.php');

    plugin_vehiclescheduler_render_insuranceclaim_form(
        $item,
        $claimId,
        $rootDoc,
        $backUrl,
        $incidentLinkHtml
    );

    $feedbackJsFile = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/form-feedback.js';
    $feedbackJsVer = is_file($feedbackJsFile) ? filemtime($feedbackJsFile) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $feedbackJsUrl = plugin_vehiclescheduler_get_public_url('js/form-feedback.js') . '?v=' . $feedbackJsVer;
    $jsFile = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/insuranceclaim-form.js';
    $jsVer = is_file($jsFile) ? filemtime($jsFile) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $jsUrl = plugin_vehiclescheduler_get_public_url('js/insuranceclaim-form.js') . '?v=' . $jsVer;

    echo "<script src='" . plugin_vehiclescheduler_insuranceclaim_escape($feedbackJsUrl) . "' defer></script>";
    echo "<script src='" . plugin_vehiclescheduler_insuranceclaim_escape($jsUrl) . "' defer></script>";

    Html::footer();
}
