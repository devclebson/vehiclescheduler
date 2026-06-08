<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Driver form page (create / edit / delete)
 */

include('../../../inc/includes.php');

Session::checkRight('plugin_vehiclescheduler', UPDATE);

$driver = new PluginVehicleschedulerDriver();

if (isset($_POST['add'])) {
    $driver->check(-1, CREATE, $_POST);
    $driver->add($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} elseif (isset($_POST['update'])) {
    $driver->check($_POST['id'], UPDATE);
    $driver->update($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} elseif (isset($_POST['delete'])) {
    $driver->check($_POST['id'], DELETE);
    $driver->delete($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} elseif (isset($_POST['restore'])) {
    $driver->check($_POST['id'], DELETE);
    $driver->restore($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} elseif (isset($_POST['purge'])) {
    $driver->check($_POST['id'], PURGE);
    $driver->delete($_POST, 1);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else {
    $driver->checkGlobal(READ);

    Html::header(
        PluginVehicleschedulerDriver::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'plugins',
        'pluginvehicleschedulerdriver',
        'driver'
    );

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $driver->display(['id' => $id]);

    Html::footer();
}
