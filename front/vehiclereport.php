<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * 
 * Vehicle Report list page
 * 
 * @category Plugin
 * @package  VehicleScheduler
 * @author   Plugin Development Team
 * @license  GPLv3+
 */

include ('../../../inc/includes.php');

Session::checkRight('plugin_vehiclescheduler', READ);

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

Html::header(
    PluginVehicleschedulerVehiclereport::getTypeName(Session::getPluralNumber()),
    $_SERVER['PHP_SELF'],
    'plugins',
    'pluginvehicleschedulervehiclereport',
    'vehiclereport'
);

Search::show('PluginVehicleschedulerVehiclereport');

Html::footer();
