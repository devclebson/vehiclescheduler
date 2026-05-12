<?php
// front/driver.form.php

include_once(__DIR__ . '/../inc/common.inc.php');

Session::checkRight('plugin_vehiclescheduler_management', READ);

global $CFG_GLPI;

/**
 * Returns the current requested driver identifier.
 *
 * @return int
 */
function vs_driver_form_get_id(): int
{
    return isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
}

$driver = new PluginVehicleschedulerDriver();
$driverId = vs_driver_form_get_id();

if (isset($_POST['add'])) {
    $driver->check(-1, CREATE, $_POST);
    $newId = $driver->add($_POST);

    if ($newId !== false) {
        Html::redirect(plugin_vehiclescheduler_get_front_url('driver.php'));
    }

    Html::back();
}

if (isset($_POST['update'])) {
    $driver->check((int) $_POST['id'], UPDATE);
    $driver->update($_POST);
    Html::back();
}

if (isset($_POST['purge'])) {
    $driver->check((int) $_POST['id'], PURGE);
    $driver->delete($_POST, 1);

    Html::redirect(
        plugin_vehiclescheduler_get_front_url('driver.php')
    );
}

if ($driverId > 0) {
    $driver->check($driverId, READ);
} else {
    $driver->check(-1, CREATE);
}

Html::header(
    'Motoristas',
    $_SERVER['PHP_SELF'],
    'tools',
    PluginVehicleschedulerMenu::class,
    'management'
);

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_render_back_to_management();
plugin_vehiclescheduler_load_script('js/driver-form.js');

$driver->display([
    'id' => $driverId,
]);

Html::footer();
