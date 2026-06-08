<?php
include('../../../inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', UPDATE);

// Verificar permissão básica de acesso ao portal ou gestão
if (!PluginVehicleschedulerProfile::canAccessRequester() && !PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

$item = new PluginVehicleschedulerIncident();
$id = isset($_REQUEST["id"]) ? (int)$_REQUEST["id"] : 0;

if ($id > 0) {
    if (!$item->getFromDB($id)) {
        Html::displayNotFoundError();
        exit;
    }
    
    // Se não for gestor, só pode ver/alterar o próprio incidente
    if (!PluginVehicleschedulerProfile::canViewManagement() && $item->fields['users_id'] != Session::getLoginUserID()) {
        Html::displayRightError();
        exit;
    }
}

if (isset($_POST['add'])) {
    $item->check(-1, CREATE, $_POST);
    $item->add($_POST);
    Session::addMessageAfterRedirect('Incidente reportado com sucesso!', false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
} elseif (isset($_POST['update'])) {
    $item->check($_POST['id'], UPDATE);
    $item->update($_POST);
    Session::addMessageAfterRedirect('Incidente atualizado.', false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
} elseif (isset($_POST['delete'])) {
    $item->check($_POST['id'], DELETE);
    $item->delete($_POST);
    Session::addMessageAfterRedirect('Incidente cancelado.', false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
} elseif (isset($_POST['purge'])) {
    $item->check($_POST['id'], PURGE);
    $item->delete($_POST, 1);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
} else {
    $item->checkGlobal(READ);
    Html::header(PluginVehicleschedulerIncident::getTypeName(1), $_SERVER['PHP_SELF'], 'plugins', 'pluginvehicleschedulerincident', 'incident');
    $item->display(['id' => isset($_GET['id']) ? (int)$_GET['id'] : 0]);
    Html::footer();
}
