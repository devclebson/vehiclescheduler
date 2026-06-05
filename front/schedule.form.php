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
    
    if ($newID = $schedule->add($_POST)) {
        if (!PluginVehicleschedulerProfile::canViewManagement()) {
            Session::addMessageAfterRedirect('Reserva solicitada com sucesso!', false, INFO);
            Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/requester_list.php');
        }
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($schedule->getLinkURL());
        }
    }
    Html::back();

} else if (isset($_POST["delete"])) {
    $schedule->check($_POST["id"], DELETE);
    $schedule->delete($_POST);
    if (!PluginVehicleschedulerProfile::canViewManagement()) {
        Session::addMessageAfterRedirect('Reserva cancelada com sucesso.', false, INFO);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/requester_list.php');
    }
    $schedule->redirectToList();

} else if (isset($_POST["restore"])) {
    $schedule->check($_POST["id"], DELETE);
    $schedule->restore($_POST);
    $schedule->redirectToList();

} else if (isset($_POST["purge"])) {
    $schedule->check($_POST["id"], PURGE);
    $schedule->delete($_POST, 1);
    $schedule->redirectToList();

} else if (isset($_POST["update"])) {
    $schedule->check($_POST["id"], UPDATE);
    $schedule->update($_POST);
    if (!PluginVehicleschedulerProfile::canViewManagement()) {
        Session::addMessageAfterRedirect('Reserva atualizada com sucesso!', false, INFO);
        Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/requester_list.php');
    }
    Html::back();

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
