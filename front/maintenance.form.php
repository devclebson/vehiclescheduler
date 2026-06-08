<?php
include('../../../inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', UPDATE);
$item = new PluginVehicleschedulerMaintenance();
if (isset($_POST['add'])) {
    $item->check(-1, CREATE, $_POST);
    $item->add($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
} elseif (isset($_POST['update'])) {
    $item->check($_POST['id'], UPDATE);
    $item->update($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
} elseif (isset($_POST['delete'])) {
    $item->check($_POST['id'], DELETE);
    $item->delete($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
} elseif (isset($_POST['purge'])) {
    $item->check($_POST['id'], PURGE);
    $item->delete($_POST, 1);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
} else {
    $item->checkGlobal(READ);
    // Pre-fill from GET (from incident quick-action)
    Html::header(PluginVehicleschedulerMaintenance::getTypeName(1), $_SERVER['PHP_SELF'], 'plugins', 'pluginvehicleschedulermaintenance', 'maintenance');
    $item->display(['id' => isset($_GET['id']) ? (int)$_GET['id'] : 0]);
    Html::footer();
}
