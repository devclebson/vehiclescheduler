<?php
// front/incident.form.php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkLoginUser();

function vs_incident_form_get_id(): int
{
    return isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
}

$incident = new PluginVehicleschedulerIncident();
$incidentId = vs_incident_form_get_id();

if (isset($_POST['add'])) {
    $incident->check(-1, CREATE, $_POST);
    $newId = $incident->add($_POST);

    if ($newId !== false) {
        if (isset($_SESSION['vehiclescheduler_created_ticket_id'])) {
            $ticketId = (int) $_SESSION['vehiclescheduler_created_ticket_id'];
            unset($_SESSION['vehiclescheduler_created_ticket_id']);

            Session::addMessageAfterRedirect(
                'Sinistro informado! Chamado #' . $ticketId . ' criado automaticamente.',
                false,
                INFO
            );
        }

        Html::redirect(plugin_vehiclescheduler_get_front_url('incident.form.php') . '?id=' . (int) $newId);
    }

    Html::back();
}

if (isset($_POST['update'])) {
    $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $incident->check($postId, UPDATE);
    $incident->update($_POST);
    Html::back();
}

if (isset($_POST['purge'])) {
    $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $incident->check($postId, PURGE);
    $incident->delete($_POST, 1);

    Html::redirect(plugin_vehiclescheduler_get_front_url('incident.php'));
}

if ($incidentId > 0) {
    $incident->check($incidentId, READ);
} else {
    $incident->check(-1, CREATE);
}

$menuClass = PluginVehicleschedulerProfile::canViewManagement()
    ? PluginVehicleschedulerMenu::class
    : '';
$section = PluginVehicleschedulerProfile::canViewManagement()
    ? 'management'
    : '';

Html::header(
    'Informar Sinistro',
    $_SERVER['PHP_SELF'],
    PluginVehicleschedulerProfile::canViewManagement() ? 'tools' : 'helpdesk',
    $menuClass,
    $section
);

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

if (PluginVehicleschedulerProfile::canViewManagement()) {
    plugin_vehiclescheduler_render_back_to_management();
}

plugin_vehiclescheduler_load_script('js/form-feedback.js');
plugin_vehiclescheduler_load_script('js/incident-form.js');

$incident->display([
    'id' => $incidentId,
]);

Html::footer();
