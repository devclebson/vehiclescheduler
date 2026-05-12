<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * 
 * Vehicle Report list page
 * 
 * @category Plugin
 * @package  VehicleScheduler
 * @author   Plugin Development Team
 * @license  PolyForm Noncommercial License 1.0.0
 */


Session::checkRight('plugin_vehiclescheduler', READ);

Html::header(
    PluginVehicleschedulerVehiclereport::getTypeName(Session::getPluralNumber()),
    $_SERVER['PHP_SELF'],
    'plugins',
    'pluginvehicleschedulervehiclereport',
    'vehiclereport'
);

Search::show('PluginVehicleschedulerVehiclereport');

Html::footer();
