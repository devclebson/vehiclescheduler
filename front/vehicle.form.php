<?php

include_once __DIR__ . '/../inc/common.inc.php';
include_once __DIR__ . '/../inc/ui-helpers.php';

$vehicle = new PluginVehicleschedulerVehicle();

$post = $_POST;
$post_id      = PluginVehicleschedulerInput::int($_POST, 'id', 0, 0);
$get_id       = PluginVehicleschedulerInput::int($_GET, 'id', 0, 0);
$id           = $post_id > 0 ? $post_id : $get_id;
$withtemplate = PluginVehicleschedulerInput::int($_GET, 'withtemplate', 0, 0);

/**
 * Adds a success flash message with fallback to the GLPI standard queue.
 *
 * @param string $message
 *
 * @return void
 */
function vs_vehicle_form_flash_success(string $message): void
{
    if (function_exists('plugin_vehiclescheduler_flash_success')) {
        plugin_vehiclescheduler_flash_success($message);
        return;
    }

    Session::addMessageAfterRedirect($message, false, INFO);
}

/**
 * Adds an error flash message with fallback to the GLPI standard queue.
 *
 * @param string $message
 *
 * @return void
 */
function vs_vehicle_form_flash_error(string $message): void
{
    if (function_exists('plugin_vehiclescheduler_flash_error')) {
        plugin_vehiclescheduler_flash_error($message);
        return;
    }

    Session::addMessageAfterRedirect($message, true, ERROR);
}

if (isset($_POST['add']) || isset($_POST['update'])) {
    $post['id'] = $post_id;
    $post['entities_id'] = PluginVehicleschedulerInput::int(
        $_POST,
        'entities_id',
        (int) ($_SESSION['glpiactive_entity'] ?? 0),
        0
    );
    $post['is_recursive'] = PluginVehicleschedulerInput::int($_POST, 'is_recursive', 0, 0, 1);
    $post['name'] = PluginVehicleschedulerInput::string($_POST, 'name', 255);
    $post['plate'] = PluginVehicleschedulerInput::string($_POST, 'plate', 50);
    $post['brand'] = PluginVehicleschedulerInput::string($_POST, 'brand', 100);
    $post['model'] = PluginVehicleschedulerInput::string($_POST, 'model', 100);
    $post['year'] = PluginVehicleschedulerInput::int($_POST, 'year', (int) date('Y'));
    $post['seats'] = PluginVehicleschedulerInput::int($_POST, 'seats', 5);
    $post['is_active'] = PluginVehicleschedulerInput::bool($_POST, 'is_active', true);
    $post['required_cnh_category'] = PluginVehicleschedulerInput::enum(
        $_POST,
        'required_cnh_category',
        array_keys(PluginVehicleschedulerVehicle::getRequiredCNHOptions()),
        PluginVehicleschedulerVehicle::REQUIRED_CNH_B
    );
    $post['comment'] = PluginVehicleschedulerInput::text($_POST, 'comment', 65535);
}

if (isset($_POST['add'])) {
    Session::checkRight('plugin_vehiclescheduler_management', CREATE);
    $vehicle->check(-1, CREATE, $post);

    $newID = $vehicle->add($post);

    if ($newID !== false) {
        vs_vehicle_form_flash_success('Veículo cadastrado com sucesso.');
        Html::redirect(plugin_vehiclescheduler_get_front_url('vehicle.php'));
        exit;
    }

    Html::back();
    exit;
} elseif (isset($_POST['update'])) {
    Session::checkRight('plugin_vehiclescheduler_management', UPDATE);

    if ($post_id <= 0) {
        vs_vehicle_form_flash_error('ID do veículo inválido.');
        Html::back();
        exit;
    }

    $post['id'] = $post_id;
    $vehicle->check($post_id, UPDATE);
    $vehicle->update($post);

    vs_vehicle_form_flash_success('Veículo atualizado com sucesso.');
    Html::redirect(plugin_vehiclescheduler_get_front_url('vehicle.php'));
    exit;
} elseif (isset($_POST['delete'])) {
    Session::checkRight('plugin_vehiclescheduler_management', DELETE);

    if ($post_id <= 0) {
        vs_vehicle_form_flash_error('ID do veículo inválido.');
        Html::back();
        exit;
    }

    $post['id'] = $post_id;
    $vehicle->check($post_id, DELETE);
    $vehicle->delete($post);
    $vehicle->redirectToList();
    exit;
} elseif (isset($_POST['restore'])) {
    Session::checkRight('plugin_vehiclescheduler_management', DELETE);

    if ($post_id <= 0) {
        vs_vehicle_form_flash_error('ID do veículo inválido.');
        Html::back();
        exit;
    }

    $post['id'] = $post_id;
    $vehicle->check($post_id, DELETE);
    $vehicle->restore($post);
    $vehicle->redirectToList();
    exit;
} elseif (isset($_POST['purge'])) {
    Session::checkRight('plugin_vehiclescheduler_management', PURGE);

    if ($post_id <= 0) {
        vs_vehicle_form_flash_error('ID do veículo inválido.');
        Html::back();
        exit;
    }

    $post['id'] = $post_id;
    $vehicle->check($post_id, PURGE);
    $vehicle->delete($post, 1);
    $vehicle->redirectToList();
    exit;
} else {
    Session::checkRight('plugin_vehiclescheduler_management', READ);
    $vehicle->checkGlobal(READ);

    plugin_vehiclescheduler_load_css();
    plugin_vehiclescheduler_enhance_ui();

    Html::header(
        'Veículos',
        $_SERVER['PHP_SELF'],
        'tools',
        PluginVehicleschedulerVehicle::class,
        'vehicles'
    );

    $back_url = plugin_vehiclescheduler_get_front_url('vehicle.php');

    vs_render_back_button($back_url, 'Voltar');

    $feedback_js_file = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/form-feedback.js';
    $feedback_js_ver = is_file($feedback_js_file) ? filemtime($feedback_js_file) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $feedback_js_url = plugin_vehiclescheduler_get_public_url('js/form-feedback.js') . '?v=' . $feedback_js_ver;

    $js_file = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/vehicle-form.js';
    $js_ver = is_file($js_file) ? filemtime($js_file) : PLUGIN_VEHICLESCHEDULER_VERSION;
    $js_url = plugin_vehiclescheduler_get_public_url('js/vehicle-form.js') . '?v=' . $js_ver;

    echo "<script src='" . htmlspecialchars($feedback_js_url, ENT_QUOTES, 'UTF-8') . "' defer></script>";
    echo "<script src='" . htmlspecialchars($js_url, ENT_QUOTES, 'UTF-8') . "' defer></script>";

    $vehicle->display([
        'id'           => $id,
        'withtemplate' => $withtemplate,
    ]);

    Html::footer();
}
