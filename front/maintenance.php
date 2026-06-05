<?php
include('../../../inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', READ);

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

Html::header(
    'Manutenções',
    $_SERVER['PHP_SELF'],
    'tools',
    'PluginVehicleschedulerMenug',
    'maintenances'
);

echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
echo "<div class='d-flex align-items-center gap-2'><a href='management.php' class='btn btn-sm btn-outline-secondary'><i class='ti ti-arrow-left'></i></a><h2 class='m-0'>Gestão de Manutenções</h2></div>";
if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
    echo "<a href='maintenance.form.php' class='btn btn-primary'><i class='ti ti-plus'></i> Agendar Manutenção</a>";
}
echo "</div>";

Search::show('PluginVehicleschedulerMaintenance');
Html::footer();
