<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * 
 * Schedule form page
 * 
 * @category Plugin
 * @package  VehicleScheduler
 * @author   Plugin Development Team
 * @license  GPLv3+
 */

include ('../../../inc/includes.php');

Session::checkRight('plugin_vehiclescheduler', UPDATE);

$schedule = new PluginVehicleschedulerSchedule();

if (isset($_POST["add"])) {
    $schedule->check(-1, CREATE, $_POST);
    $schedule->add($_POST);
    Session::addMessageAfterRedirect('Reserva solicitada com sucesso!', false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["delete"])) {
    $schedule->check($_POST["id"], DELETE);
    $schedule->delete($_POST);
    Session::addMessageAfterRedirect('Reserva cancelada com sucesso.', false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["restore"])) {
    $schedule->check($_POST["id"], DELETE);
    $schedule->restore($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["purge"])) {
    $schedule->check($_POST["id"], PURGE);
    $schedule->delete($_POST, 1);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["update"])) {
    $schedule->check($_POST["id"], UPDATE);
    $schedule->update($_POST);
    Session::addMessageAfterRedirect('Reserva atualizada com sucesso!', false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else {
    $schedule->checkGlobal(READ);

    Html::header(
        PluginVehicleschedulerSchedule::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'plugins',
        'pluginvehicleschedulerschedule'
    );
    
    $id = isset($_GET["id"]) ? $_GET["id"] : 0;
    $schedule->display([
        'id' => $id
    ]);

    Html::footer();
}
