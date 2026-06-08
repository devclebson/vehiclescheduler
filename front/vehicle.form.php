<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * 
 * Vehicle form page
 * 
 * @category Plugin
 * @package  VehicleScheduler
 * @author   Plugin Development Team
 * @license  GPLv3+
 */

include ('../../../inc/includes.php');

Session::checkRight('plugin_vehiclescheduler', UPDATE);

$vehicle = new PluginVehicleschedulerVehicle();

if (isset($_POST["add"])) {
    $vehicle->check(-1, CREATE, $_POST);
    $vehicle->add($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["delete"])) {
    $vehicle->check($_POST["id"], DELETE);
    $vehicle->delete($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["restore"])) {
    $vehicle->check($_POST["id"], DELETE);
    $vehicle->restore($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["purge"])) {
    $vehicle->check($_POST["id"], PURGE);
    $vehicle->delete($_POST, 1);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["update"])) {
    $vehicle->check($_POST["id"], UPDATE);
    $vehicle->update($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else {
    $vehicle->checkGlobal(READ);

    Html::header(
        PluginVehicleschedulerVehicle::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'plugins',
        'pluginvehicleschedulervehicle'
    );
    
    $id = isset($_GET["id"]) ? $_GET["id"] : 0;
    $vehicle->display([
        'id' => $id
    ]);

    Html::footer();
}
