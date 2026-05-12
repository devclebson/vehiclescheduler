<?php

/**
 * AJAX - available resources response.
 */

define('GLPI_ROOT', '../../..');

ob_end_clean();
header('Content-Type: application/json; charset=UTF-8');

Session::checkLoginUser();

if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
    echo json_encode(['error' => 'Sem permissão', 'vehicles' => [], 'drivers' => []]);
    exit;
}

global $DB;

$beginDate = PluginVehicleschedulerInput::string($_POST, 'begin_date', 19, '');
$endDate   = PluginVehicleschedulerInput::string($_POST, 'end_date', 19, '');
$entityId  = PluginVehicleschedulerInput::int($_POST, 'entity_id', (int) ($_SESSION['glpiactive_entity'] ?? 0), 0);
$excludeId = PluginVehicleschedulerInput::int($_POST, 'exclude_id', 0, 0);
$selectedVehicleId = PluginVehicleschedulerInput::int($_POST, 'vehicle_id', 0, 0);

if ($beginDate === '' || $endDate === '') {
    echo json_encode(['vehicles' => [], 'drivers' => []]);
    exit;
}

$busyVehicles = [];
$busyVehicleRows = $DB->request([
    'SELECT'   => ['plugin_vehiclescheduler_vehicles_id'],
    'DISTINCT' => true,
    'FROM'     => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE'    => [
        'status' => PluginVehicleschedulerSchedule::STATUS_APPROVED,
        'id'     => ['!=', $excludeId],
        'OR'     => [
            [
                'AND' => [
                    ['begin_date' => ['<=', $beginDate]],
                    ['end_date' => ['>=', $beginDate]],
                ],
            ],
            [
                'AND' => [
                    ['begin_date' => ['<=', $endDate]],
                    ['end_date' => ['>=', $endDate]],
                ],
            ],
            [
                'AND' => [
                    ['begin_date' => ['>=', $beginDate]],
                    ['end_date' => ['<=', $endDate]],
                ],
            ],
        ],
    ],
]);

foreach ($busyVehicleRows as $row) {
    $busyVehicles[] = (int) $row['plugin_vehiclescheduler_vehicles_id'];
}

$maintenanceRows = $DB->request([
    'SELECT'   => ['plugin_vehiclescheduler_vehicles_id'],
    'DISTINCT' => true,
    'FROM'     => 'glpi_plugin_vehiclescheduler_maintenances',
    'WHERE'    => [
        'status' => 1,
        'AND'    => [
            ['scheduled_date' => ['>=', substr($beginDate, 0, 10)]],
            ['scheduled_date' => ['<=', substr($endDate, 0, 10)]],
        ],
    ],
]);

foreach ($maintenanceRows as $row) {
    $vehicleId = (int) $row['plugin_vehiclescheduler_vehicles_id'];
    if (!in_array($vehicleId, $busyVehicles, true)) {
        $busyVehicles[] = $vehicleId;
    }
}

$busyDrivers = [];
$busyDriverRows = $DB->request([
    'SELECT'   => ['plugin_vehiclescheduler_drivers_id'],
    'DISTINCT' => true,
    'FROM'     => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE'    => [
        'status' => PluginVehicleschedulerSchedule::STATUS_APPROVED,
        'id'     => ['!=', $excludeId],
        'OR'     => [
            [
                'AND' => [
                    ['begin_date' => ['<=', $beginDate]],
                    ['end_date' => ['>=', $beginDate]],
                ],
            ],
            [
                'AND' => [
                    ['begin_date' => ['<=', $endDate]],
                    ['end_date' => ['>=', $endDate]],
                ],
            ],
            [
                'AND' => [
                    ['begin_date' => ['>=', $beginDate]],
                    ['end_date' => ['<=', $endDate]],
                ],
            ],
        ],
    ],
]);

foreach ($busyDriverRows as $row) {
    $driverId = (int) ($row['plugin_vehiclescheduler_drivers_id'] ?? 0);
    if ($driverId > 0) {
        $busyDrivers[] = $driverId;
    }
}

$vehicles = [];
$vehicleWhere = [
    'entities_id' => $entityId,
    'is_active'   => 1,
];
if ($busyVehicles !== []) {
    $vehicleWhere['id'] = ['NOT IN', $busyVehicles];
}

foreach ($DB->request([
    'SELECT' => ['id', 'name', 'plate'],
    'FROM'   => 'glpi_plugin_vehiclescheduler_vehicles',
    'WHERE'  => $vehicleWhere,
    'ORDER'  => ['name ASC'],
]) as $row) {
    $vehicles[] = [
        'id'   => (int) $row['id'],
        'text' => (string) $row['name'] . ' (' . (string) $row['plate'] . ')',
    ];
}

$drivers = [];
$driverWhere = [
    'is_active'   => 1,
    'is_approved' => 1,
];
if ($busyDrivers !== []) {
    $driverWhere['id'] = ['NOT IN', $busyDrivers];
}

foreach ($DB->request([
    'SELECT' => ['id', 'users_id', 'cnh_category'],
    'FROM'   => 'glpi_plugin_vehiclescheduler_drivers',
    'WHERE'  => $driverWhere,
    'ORDER'  => ['id ASC'],
]) as $row) {
    if ($selectedVehicleId > 0) {
        $compatibilityError = PluginVehicleschedulerSchedule::getDriverVehicleCompatibilityError(
            $selectedVehicleId,
            (int) $row['id']
        );

        if ($compatibilityError !== null) {
            continue;
        }
    }

    $drivers[] = [
        'id'   => (int) $row['id'],
        'text' => getUserName((int) $row['users_id']),
    ];
}

echo json_encode([
    'vehicles'            => $vehicles,
    'drivers'             => $drivers,
    'busy_vehicles_count' => count($busyVehicles),
    'busy_drivers_count'  => count($busyDrivers),
]);
