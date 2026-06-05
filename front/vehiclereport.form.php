<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * 
 * Vehicle Report form page
 * 
 * @category Plugin
 * @package  VehicleScheduler
 * @author   Plugin Development Team
 * @license  GPLv3+
 */

include ('../../../inc/includes.php');

Session::checkRight('plugin_vehiclescheduler', UPDATE);

$report = new PluginVehicleschedulerVehiclereport();

if (isset($_POST["add"])) {
    $report->check(-1, CREATE, $_POST);
    
    if ($newID = $report->add($_POST)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($report->getLinkURL());
        }
    }
    Html::back();

} else if (isset($_POST["delete"])) {
    $report->check($_POST["id"], DELETE);
    $report->delete($_POST);
    $report->redirectToList();

} else if (isset($_POST["restore"])) {
    $report->check($_POST["id"], DELETE);
    $report->restore($_POST);
    $report->redirectToList();

} else if (isset($_POST["purge"])) {
    $report->check($_POST["id"], PURGE);
    $report->delete($_POST, 1);
    $report->redirectToList();

} else if (isset($_POST["update"])) {
    $report->check($_POST["id"], UPDATE);
    $report->update($_POST);
    Html::back();

} else {
    $report->checkGlobal(READ);

    Html::header(
        PluginVehicleschedulerVehiclereport::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'plugins',
        'pluginvehicleschedulervehiclereport'
    );
    
    $id = isset($_GET["id"]) ? $_GET["id"] : 0;
    $report->display([
        'id' => $id
    ]);

    Html::footer();
}
