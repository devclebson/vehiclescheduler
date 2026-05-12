<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

require_once(__DIR__ . '/checklist.render.php');

$checklist = new PluginVehicleschedulerChecklist();
$rootDoc = plugin_vehiclescheduler_get_root_doc();

$post = $_POST;
$postId = PluginVehicleschedulerInput::int($_POST, 'id', 0, 0);
$displayId = PluginVehicleschedulerInput::int($_GET, 'id', -1, -1);
$editingItemId = PluginVehicleschedulerInput::int($_GET, 'edit_item', 0, 0);

if (isset($_POST['add']) || isset($_POST['update'])) {
    $post['name'] = PluginVehicleschedulerInput::string($_POST, 'name', 255);
    $post['description'] = PluginVehicleschedulerInput::text($_POST, 'description', 2000);
    $post['checklist_type'] = PluginVehicleschedulerInput::int(
        $_POST,
        'checklist_type',
        PluginVehicleschedulerChecklist::TYPE_DEPARTURE,
        PluginVehicleschedulerChecklist::TYPE_DEPARTURE,
        PluginVehicleschedulerChecklist::TYPE_BOTH
    );
    $post['is_active'] = PluginVehicleschedulerInput::bool($_POST, 'is_active', true);
    $post['is_mandatory'] = PluginVehicleschedulerInput::bool($_POST, 'is_mandatory', true);
}

if (isset($_POST['add'])) {
    $checklist->check(-1, CREATE, $post);

    if ($newId = $checklist->add($post)) {
        Html::redirect($checklist->getFormURLWithID((int) $newId));
    }

    Html::back();
} elseif (isset($_POST['update'])) {
    $post['id'] = $postId;
    $checklist->check($postId, UPDATE);
    $checklist->update($post);
    Html::redirect(plugin_vehiclescheduler_get_front_url('checklist.form.php') . '?id=' . $postId);
} elseif (isset($_POST['delete'])) {
    $post['id'] = $postId;
    $checklist->check($postId, DELETE);
    $checklist->delete($post);
    Html::redirect(plugin_vehiclescheduler_get_front_url('checklist.php'));
} elseif (isset($_POST['purge'])) {
    $post['id'] = $postId;
    $checklist->check($postId, PURGE);
    $checklist->delete($post, 1);
    Html::redirect(plugin_vehiclescheduler_get_front_url('checklist.php'));
}

if ($displayId > 0) {
    $checklist->check($displayId, READ);
    $checklist->getFromDB($displayId);
} else {
    $checklist->fields = [
        'name'           => '',
        'description'    => '',
        'checklist_type' => PluginVehicleschedulerChecklist::TYPE_DEPARTURE,
        'is_active'      => 1,
        'is_mandatory'   => 1,
    ];
}

$items = [];

if ($displayId > 0) {
    global $DB;

    $items = iterator_to_array($DB->request([
        'FROM'  => PluginVehicleschedulerChecklistitem::getTable(),
        'WHERE' => ['plugin_vehiclescheduler_checklists_id' => $displayId],
        'ORDER' => ['position ASC', 'id ASC'],
    ]));
}

Html::header(
    'Template de Checklist',
    $_SERVER['PHP_SELF'],
    'tools',
    'PluginVehicleschedulerMenug',
    'checklist'
);

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
?>

<div class="asset vs-checklist-form-page">
    <?php plugin_vehiclescheduler_render_checklist_form($checklist, max($displayId, 0), $rootDoc); ?>

    <?php if ($displayId > 0): ?>
        <?php
        plugin_vehiclescheduler_render_checklist_items_panel(
            $displayId,
            $items,
            $editingItemId,
            $rootDoc,
            Session::haveRight('plugin_vehiclescheduler', UPDATE)
        );
        ?>
    <?php endif; ?>
</div>

<?php Html::footer(); ?>
