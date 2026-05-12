<?php

include_once(__DIR__ . '/../inc/common.inc.php');
include_once(__DIR__ . '/../inc/ui-helpers.php');

Session::checkRight('plugin_vehiclescheduler', UPDATE);

$report = new PluginVehicleschedulerVehiclereport();

$root_doc = plugin_vehiclescheduler_get_root_doc();

$post = $_POST;
$post_id = PluginVehicleschedulerInput::int($_POST, 'id', 0, 0);
$get_id = PluginVehicleschedulerInput::int($_GET, 'id', 0, 0);
$id = $post_id > 0 ? $post_id : $get_id;
$withtemplate = PluginVehicleschedulerInput::int($_GET, 'withtemplate', 0, 0);

if (isset($_POST['add']) || isset($_POST['update'])) {
    $post['id'] = $post_id;
    $post['entities_id'] = PluginVehicleschedulerInput::int(
        $_POST,
        'entities_id',
        (int) ($_SESSION['glpiactive_entity'] ?? 0),
        0
    );
    $post['plugin_vehiclescheduler_vehicles_id'] = PluginVehicleschedulerInput::int(
        $_POST,
        'plugin_vehiclescheduler_vehicles_id',
        0,
        0
    );
    $post['report_type'] = PluginVehicleschedulerInput::int(
        $_POST,
        'report_type',
        PluginVehicleschedulerVehiclereport::TYPE_OBSERVATION,
        PluginVehicleschedulerVehiclereport::TYPE_MAINTENANCE,
        PluginVehicleschedulerVehiclereport::TYPE_OBSERVATION
    );
    $post['users_id'] = PluginVehicleschedulerInput::int(
        $_POST,
        'users_id',
        (int) Session::getLoginUserID(),
        0
    );
    $post['department'] = PluginVehicleschedulerInput::string($_POST, 'department', 255);
    $post['contact_phone'] = PluginVehicleschedulerInput::string($_POST, 'contact_phone', 50);
    $post['report_date'] = PluginVehicleschedulerInput::datetime($_POST, 'report_date', date('Y-m-d H:i:s'));
    $post['description'] = PluginVehicleschedulerInput::text($_POST, 'description', 65535);
    $post['comment'] = PluginVehicleschedulerInput::text($_POST, 'comment', 65535);
}

if (isset($_POST['add'])) {
    $report->check(-1, CREATE, $post);

    if ($newID = $report->add($post)) {
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($report->getLinkURL());
        }
    }

    Html::back();
} elseif (isset($_POST['delete'])) {
    $post['id'] = $post_id;
    $report->check($post_id, DELETE);
    $report->delete($post);
    $report->redirectToList();
} elseif (isset($_POST['restore'])) {
    $post['id'] = $post_id;
    $report->check($post_id, DELETE);
    $report->restore($post);
    $report->redirectToList();
} elseif (isset($_POST['purge'])) {
    $post['id'] = $post_id;
    $report->check($post_id, PURGE);
    $report->delete($post, 1);
    $report->redirectToList();
} elseif (isset($_POST['update'])) {
    $post['id'] = $post_id;
    $report->check($post_id, UPDATE);
    $report->update($post);
    Html::back();
} else {
    $report->checkGlobal(READ);

    Html::header(
        'Relatórios de Veículos',
        $_SERVER['PHP_SELF'],
        'tools',
        PluginVehicleschedulerVehiclereport::class,
        'vehiclereport'
    );

    plugin_vehiclescheduler_load_css();
    plugin_vehiclescheduler_enhance_ui();
    vs_render_back_button(plugin_vehiclescheduler_get_front_url('vehiclereport.php'), 'Voltar');

    echo "<script src='" . htmlspecialchars(plugin_vehiclescheduler_get_public_asset_url('js/form-feedback.js'), ENT_QUOTES, 'UTF-8') . "' defer></script>";
    echo "<script src='" . htmlspecialchars(plugin_vehiclescheduler_get_public_asset_url('js/vehiclereport-form.js'), ENT_QUOTES, 'UTF-8') . "' defer></script>";

    $report->display([
        'id'           => $id,
        'withtemplate' => $withtemplate,
    ]);

    Html::footer();
}
