<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Driver fine form — handles POST actions from the driver tab
 */

include('../../../inc/includes.php');

Session::checkRight('plugin_vehiclescheduler', UPDATE);

$fine = new PluginVehicleschedulerDriverfine();

if (isset($_POST['add'])) {
    $fine->check(-1, CREATE, $_POST);
    $fine->add($_POST);
    Html::back();

} elseif (isset($_POST['update'])) {
    $fine->check($_POST['id'], UPDATE);
    $fine->update($_POST);
    Html::back();

} elseif (isset($_POST['delete'])) {
    $fine->check($_POST['id'], DELETE);
    $fine->delete($_POST);
    Html::back();

} elseif (isset($_POST['purge'])) {
    $fine->check($_POST['id'], PURGE);
    $fine->delete($_POST, 1);
    Html::back();

} else {
    // Edit form for a single fine
    $fine->checkGlobal(READ);

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id && $fine->getFromDB($id)) {
        $driver_id = $fine->fields['plugin_vehiclescheduler_drivers_id'];
    } else {
        $driver_id = isset($_GET['plugin_vehiclescheduler_drivers_id'])
            ? (int) $_GET['plugin_vehiclescheduler_drivers_id']
            : 0;
    }

    $driver = new PluginVehicleschedulerDriver();

    Html::header(
        PluginVehicleschedulerDriverfine::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'plugins',
        'pluginvehicleschedulerdriver',
        'driver'
    );

    // Simple edit form
    $fine->showForm($id);

    Html::footer();
}
