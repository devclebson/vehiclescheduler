<?php
include('../../../inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', UPDATE);
$item = new PluginVehicleschedulerIncident();
if (isset($_POST['add'])) {
    $item->check(-1, CREATE, $_POST);
    if ($nid = $item->add($_POST)) {
        if (!PluginVehicleschedulerProfile::canViewManagement()) {
            Session::addMessageAfterRedirect('Incidente reportado com sucesso!', false, INFO);
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/requester_list.php');
        }
        if ($_SESSION['glpibackcreated']) Html::redirect($item->getLinkURL());
    }
    Html::back();
} elseif (isset($_POST['update'])) {
    $item->check($_POST['id'], UPDATE); $item->update($_POST);
    if (!PluginVehicleschedulerProfile::canViewManagement()) {
        Session::addMessageAfterRedirect('Incidente atualizado.', false, INFO);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/requester_list.php');
    }
    Html::back();
} elseif (isset($_POST['delete'])) {
    $item->check($_POST['id'], DELETE); $item->delete($_POST);
    if (!PluginVehicleschedulerProfile::canViewManagement()) {
        Session::addMessageAfterRedirect('Incidente cancelado.', false, INFO);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/requester_list.php');
    }
    $item->redirectToList();
} elseif (isset($_POST['purge'])) {
    $item->check($_POST['id'], PURGE); $item->delete($_POST, 1); $item->redirectToList();
} else {
    $item->checkGlobal(READ);
    Html::header(PluginVehicleschedulerIncident::getTypeName(1), $_SERVER['PHP_SELF'], 'plugins', 'pluginvehicleschedulerincident', 'incident');
    $item->display(['id' => isset($_GET['id']) ? (int)$_GET['id'] : 0]);
    Html::footer();
}
