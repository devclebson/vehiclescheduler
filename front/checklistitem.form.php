<?php
include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('CHECKLIST', 'EM OBRAS !!!');
exit;

$item = new PluginVehicleschedulerChecklistitem();
$post = $_POST;
$postId = PluginVehicleschedulerInput::int($_POST, 'id', 0, 0);
$getId = PluginVehicleschedulerInput::int($_GET, 'id', 0, 0);

if (isset($_POST['add']) || isset($_POST['update'])) {
    $post['description'] = PluginVehicleschedulerInput::string($_POST, 'description', 255);
    $post['item_type'] = PluginVehicleschedulerInput::int(
        $_POST,
        'item_type',
        PluginVehicleschedulerChecklistitem::TYPE_CHECKBOX,
        PluginVehicleschedulerChecklistitem::TYPE_CHECKBOX,
        PluginVehicleschedulerChecklistitem::TYPE_SIGNATURE
    );
    $post['plugin_vehiclescheduler_checklists_id'] = PluginVehicleschedulerInput::int(
        $_POST,
        'plugin_vehiclescheduler_checklists_id',
        0,
        0
    );
}

if (isset($_POST['add'])) {
    $item->check(-1, CREATE, $post);

    if ($item->add($post)) {
        Session::addMessageAfterRedirect('Item adicionado!', false, INFO);
    }

    Html::back();
} elseif (isset($_POST['update'])) {
    $post['id'] = $postId;
    $item->check($postId, UPDATE);
    $item->update($post);
    Session::addMessageAfterRedirect('Item atualizado!', false, INFO);
    Html::back();
} elseif (isset($_GET['delete']) || isset($_POST['delete'])) {
    $id = $getId > 0 ? $getId : $postId;
    $item->check($id, DELETE);
    $item->delete(['id' => $id]);
    Session::addMessageAfterRedirect('Item excluido!', false, INFO);
    Html::back();
} else {
    Html::header(
        'Item de Checklist',
        $_SERVER['PHP_SELF'],
        'tools',
        'PluginVehicleschedulerMenug',
        'checklist'
    );

    $item->display([
        'id' => $getId,
    ]);

    Html::footer();
}
