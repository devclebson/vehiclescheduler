<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Unified Dashboard/Portal Entry Point
 */

include('../../../inc/includes.php');

Session::checkRight('plugin_vehiclescheduler', READ);

if (!PluginVehicleschedulerProfile::canAccessRequester() && !PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

if (Session::getCurrentInterface() == "helpdesk") {
    Html::helpHeader(__('Reserva de Frota', 'vehiclescheduler'));
} else {
    Html::header('Reserva de Frota', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'dashboard');
}

$dashboard = new PluginVehicleschedulerDashboard();
$dashboard->display(['id' => 1]);

if (Session::getCurrentInterface() == "helpdesk") {
    Html::helpFooter();
} else {
    Html::footer();
}
