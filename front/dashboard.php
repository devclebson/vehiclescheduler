<?php

include_once(__DIR__ . '/../inc/common.inc.php');

Session::checkRight('plugin_vehiclescheduler', READ);

Html::header(
    __('Fleet Dashboard', 'vehiclescheduler'),
    $_SERVER['PHP_SELF'],
    'plugins',
    'pluginvehicleschedulerschedule',
    'schedule'
);

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

/**
 * Count rows using GLPI structured criteria.
 */
function vs_dashboard_count(string $table, array $where = []): int
{
    global $DB;

    $criteria = [
        'FROM'  => $table,
        'COUNT' => 'cpt',
    ];

    if ($where !== []) {
        $criteria['WHERE'] = $where;
    }

    $row = $DB->request($criteria)->current();

    return (int) ($row['cpt'] ?? 0);
}

/**
 * Escape helper for dashboard output.
 */
function vs_dashboard_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vs_dashboard_render_driver_expiry_badge(array $status): string
{
    $badge = PluginVehicleschedulerDriver::getCNHExpiryBadgeData($status);

    return '<span class="vs-driver-expiry-badge '
        . vs_dashboard_escape((string) $badge['class'])
        . '">'
        . vs_dashboard_escape((string) $badge['label'])
        . '</span>';
}

/**
 * Vehicle label cache for dashboard widgets.
 */
function vs_dashboard_vehicle_name(int $id): string
{
    global $DB;

    static $cache = [];

    if ($id <= 0) {
        return '&mdash;';
    }

    if (!array_key_exists($id, $cache)) {
        $row = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_vehicles',
            'WHERE' => ['id' => $id],
        ])->current();

        if ($row) {
            $cache[$id] = "<span class='vs-dashboard-vehicle'>"
                . '<span>' . vs_dashboard_escape($row['name'] ?? '') . '</span>'
                . "<small class='vs-dashboard-vehicle-plate'>("
                . vs_dashboard_escape($row['plate'] ?? '')
                . ')</small>'
                . '</span>';
        } else {
            $cache[$id] = '#' . $id;
        }
    }

    return $cache[$id];
}

/**
 * Dashboard section header renderer.
 */
function vs_dashboard_section(string $icon, string $title, string $link = '', string $linkLabel = ''): void
{
    echo "<div class='vs-dashboard-card-header'>";
    echo "   <span class='vs-dashboard-card-title'><i class='ti " . vs_dashboard_escape($icon) . "'></i>"
        . vs_dashboard_escape($title)
        . '</span>';

    if ($link !== '') {
        echo "   <a href='" . vs_dashboard_escape($link)
            . "' class='vs-dashboard-btn vs-dashboard-btn--secondary vs-dashboard-card-link'>"
            . vs_dashboard_escape($linkLabel)
            . '</a>';
    }

    echo '</div>';
}

global $DB;

$kpi = [
    'vehicles_active'     => vs_dashboard_count('glpi_plugin_vehiclescheduler_vehicles', ['is_active' => 1]),
    'vehicles_total'      => vs_dashboard_count('glpi_plugin_vehiclescheduler_vehicles'),
    'drivers_active'      => vs_dashboard_count('glpi_plugin_vehiclescheduler_drivers', ['is_active' => 1]),
    'schedules_new'       => vs_dashboard_count('glpi_plugin_vehiclescheduler_schedules', ['status' => 1]),
    'schedules_approved'  => vs_dashboard_count('glpi_plugin_vehiclescheduler_schedules', ['status' => 2]),
    'incidents_open'      => vs_dashboard_count('glpi_plugin_vehiclescheduler_incidents', ['status' => 1]),
    'incidents_analyzing' => vs_dashboard_count('glpi_plugin_vehiclescheduler_incidents', ['status' => 2]),
    'maint_scheduled'     => vs_dashboard_count('glpi_plugin_vehiclescheduler_maintenances', ['status' => 1]),
    'maint_in_progress'   => vs_dashboard_count('glpi_plugin_vehiclescheduler_maintenances', ['status' => 2]),
    'insurance_open'      => vs_dashboard_count('glpi_plugin_vehiclescheduler_insuranceclaims', ['status' => [1, 2]]),
    'fines_open'          => vs_dashboard_count('glpi_plugin_vehiclescheduler_driverfines', ['status' => 1]),
];

$cnhWarning = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_drivers',
    'WHERE' => [
        'is_active'  => 1,
        'cnh_expiry' => ['<=', date('Y-m-d', strtotime('+90 days'))],
    ],
    'ORDER' => ['cnh_expiry ASC'],
    'LIMIT' => 5,
]));

$cnhWarning = array_values(array_filter(
    $cnhWarning,
    static function (array $driver): bool {
        $expiry = (string) ($driver['cnh_expiry'] ?? '');

        return $expiry !== '' && $expiry >= date('Y-m-d');
    }
));

$pendingSchedules = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => ['status' => 1],
    'ORDER' => ['date_creation DESC'],
    'LIMIT' => 8,
]));

$openIncidents = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_incidents',
    'WHERE' => ['status' => [1, 2]],
    'ORDER' => ['incident_date DESC'],
    'LIMIT' => 6,
]));

$upcomingMaintenances = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
    'WHERE' => ['status' => [1, 2]],
    'ORDER' => ['scheduled_date ASC'],
    'LIMIT' => 6,
]));

$openClaims = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_insuranceclaims',
    'WHERE' => ['status' => [1, 2]],
    'ORDER' => ['opening_date DESC'],
    'LIMIT' => 6,
]));

$rootDoc = plugin_vehiclescheduler_get_root_doc();

$urls = [
    'schedule_form'       => plugin_vehiclescheduler_get_front_url('schedule.form.php'),
    'schedule'            => plugin_vehiclescheduler_get_front_url('schedule.php'),
    'calendar'            => plugin_vehiclescheduler_get_front_url('calendar.php'),
    'incident_form'       => plugin_vehiclescheduler_get_front_url('incident.form.php'),
    'incident'            => plugin_vehiclescheduler_get_front_url('incident.php'),
    'maintenance_form'    => plugin_vehiclescheduler_get_front_url('maintenance.form.php'),
    'maintenance'         => plugin_vehiclescheduler_get_front_url('maintenance.php'),
    'insurance_form'      => plugin_vehiclescheduler_get_front_url('insuranceclaim.form.php'),
    'insurance'           => plugin_vehiclescheduler_get_front_url('insuranceclaim.php'),
    'driver'              => plugin_vehiclescheduler_get_front_url('driver.php'),
    'driver_form'         => plugin_vehiclescheduler_get_front_url('driver.form.php'),
];

$incidentTypes = PluginVehicleschedulerIncident::getAllTypes();
$maintenanceTypes = PluginVehicleschedulerMaintenance::getAllTypes();
$claimStatuses = PluginVehicleschedulerInsuranceclaim::getAllStatus();
?>

<div class="vs-dashboard-page">
    <div class="vs-dashboard-actions">
        <a href="<?= vs_dashboard_escape($urls['schedule_form']) ?>" class="vs-dashboard-btn vs-dashboard-btn--primary">
            <i class="ti ti-calendar-plus"></i>
            <?= vs_dashboard_escape(__('New Reservation', 'vehiclescheduler')) ?>
        </a>
        <a href="<?= vs_dashboard_escape($urls['calendar']) ?>" class="vs-dashboard-btn vs-dashboard-btn--info">
            <i class="ti ti-calendar-month"></i>
            <?= vs_dashboard_escape(__('Open Calendar', 'vehiclescheduler')) ?>
        </a>
        <a href="<?= vs_dashboard_escape($urls['incident_form']) ?>" class="vs-dashboard-btn vs-dashboard-btn--warning">
            <i class="ti ti-alert-triangle"></i>
            <?= vs_dashboard_escape(__('Report Incident', 'vehiclescheduler')) ?>
        </a>
        <a href="<?= vs_dashboard_escape($urls['maintenance_form']) ?>" class="vs-dashboard-btn vs-dashboard-btn--secondary">
            <i class="ti ti-tool"></i>
            <?= vs_dashboard_escape(__('Schedule Maintenance', 'vehiclescheduler')) ?>
        </a>
        <a href="<?= vs_dashboard_escape($urls['insurance_form']) ?>" class="vs-dashboard-btn vs-dashboard-btn--secondary">
            <i class="ti ti-shield"></i>
            <?= vs_dashboard_escape(__('Open Insurance Claim', 'vehiclescheduler')) ?>
        </a>
    </div>

    <div class="vs-dashboard-kpi-grid">
        <?php
        $kpis = [
            ['blue', 'ti-car', $kpi['vehicles_active'], __('Active Vehicles', 'vehiclescheduler'), $kpi['vehicles_total'] . ' total'],
            ['green', 'ti-steering-wheel', $kpi['drivers_active'], __('Active Drivers', 'vehiclescheduler'), ''],
            ['amber', 'ti-clock', $kpi['schedules_new'], __('Pending Approval', 'vehiclescheduler'), __('Reservations', 'vehiclescheduler')],
            ['green', 'ti-calendar-check', $kpi['schedules_approved'], __('Approved Reservations', 'vehiclescheduler'), ''],
            ['red', 'ti-alert-triangle', $kpi['incidents_open'], __('Open Incidents', 'vehiclescheduler'), $kpi['incidents_analyzing'] . ' analyzing'],
            ['purple', 'ti-tool', $kpi['maint_scheduled'], __('Scheduled Maintenances', 'vehiclescheduler'), $kpi['maint_in_progress'] . ' in progress'],
            ['amber', 'ti-shield', $kpi['insurance_open'], __('Open Claims', 'vehiclescheduler'), ''],
            ['red', 'ti-ticket', $kpi['fines_open'], __('Open Fines', 'vehiclescheduler'), ''],
        ];

        foreach ($kpis as [$color, $icon, $value, $label, $sub]) :
        ?>
            <div class="vs-dashboard-kpi vs-dashboard-kpi--<?= vs_dashboard_escape($color) ?>">
                <div class="vs-dashboard-kpi-value"><?= (int) $value ?></div>
                <div class="vs-dashboard-kpi-label">
                    <i class="ti <?= vs_dashboard_escape($icon) ?>"></i>
                    <?= vs_dashboard_escape($label) ?>
                </div>
                <?php if ($sub !== '') : ?>
                    <div class="vs-dashboard-kpi-sub"><?= vs_dashboard_escape($sub) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="vs-dashboard-grid-2">
        <div class="vs-dashboard-card">
            <?php vs_dashboard_section('ti-calendar-event', __('Reservations Pending Approval', 'vehiclescheduler'), $urls['schedule'], __('View all', 'vehiclescheduler')); ?>
            <div class="vs-dashboard-card-body">
                <?php if ($pendingSchedules === []) : ?>
                    <div class="vs-dashboard-empty"><?= vs_dashboard_escape(__('No pending reservations', 'vehiclescheduler')) ?></div>
                <?php else : ?>
                    <table class="vs-dashboard-table">
                        <thead>
                            <tr>
                                <th><?= vs_dashboard_escape(__('Requester')) ?></th>
                                <th><?= vs_dashboard_escape(__('Vehicle', 'vehiclescheduler')) ?></th>
                                <th><?= vs_dashboard_escape(__('Dates', 'vehiclescheduler')) ?></th>
                                <th><?= vs_dashboard_escape(__('Status')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingSchedules as $schedule) : ?>
                                <tr>
                                    <td>
                                        <a href="<?= vs_dashboard_escape($urls['schedule_form'] . '?id=' . (int) ($schedule['id'] ?? 0)) ?>">
                                            <?= vs_dashboard_escape(getUserName((int) ($schedule['users_id'] ?? 0))) ?>
                                        </a>
                                    </td>
                                    <td><?= vs_dashboard_vehicle_name((int) ($schedule['plugin_vehiclescheduler_vehicles_id'] ?? 0)) ?></td>
                                    <td class="vs-dashboard-nowrap">
                                        <?= Html::convDate(substr((string) ($schedule['begin_date'] ?? ''), 0, 10)) ?>
                                        →
                                        <?= Html::convDate(substr((string) ($schedule['end_date'] ?? ''), 0, 10)) ?>
                                    </td>
                                    <td><span class="vs-dashboard-badge vs-dashboard-badge--new"><?= vs_dashboard_escape(__('New', 'vehiclescheduler')) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="vs-dashboard-card">
            <?php vs_dashboard_section('ti-id-badge', __('CNH Expiry Alerts (next 90 days)', 'vehiclescheduler'), $urls['driver'], __('View all', 'vehiclescheduler')); ?>
            <div class="vs-dashboard-card-body">
                <?php if ($cnhWarning === []) : ?>
                    <div class="vs-dashboard-empty"><?= vs_dashboard_escape(__('No CNH expiring soon', 'vehiclescheduler')) ?></div>
                <?php else : ?>
                    <table class="vs-dashboard-table">
                        <thead>
                            <tr>
                                <th><?= vs_dashboard_escape(__('Driver', 'vehiclescheduler')) ?></th>
                                <th>CNH Cat.</th>
                                <th><?= vs_dashboard_escape(__('Expiry', 'vehiclescheduler')) ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cnhWarning as $driver) : ?>
                                <?php
                                $expiryStatus = PluginVehicleschedulerDriver::getCNHExpiryStatus((string) ($driver['cnh_expiry'] ?? ''));
                                $badge = vs_dashboard_render_driver_expiry_badge($expiryStatus);
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= vs_dashboard_escape($urls['driver_form'] . '?id=' . (int) ($driver['id'] ?? 0)) ?>">
                                            <?= vs_dashboard_escape((string) ($driver['name'] ?? '')) ?>
                                        </a>
                                    </td>
                                    <td><?= vs_dashboard_escape((string) ($driver['cnh_category'] ?? '')) ?></td>
                                    <td><?= Html::convDate((string) ($driver['cnh_expiry'] ?? '')) ?></td>
                                    <td><?= $badge ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="vs-dashboard-grid-2">
        <div class="vs-dashboard-card">
            <?php vs_dashboard_section('ti-alert-triangle', __('Open Incidents', 'vehiclescheduler'), $urls['incident'], __('View all', 'vehiclescheduler')); ?>
            <div class="vs-dashboard-card-body">
                <?php if ($openIncidents === []) : ?>
                    <div class="vs-dashboard-empty"><?= vs_dashboard_escape(__('No open incidents', 'vehiclescheduler')) ?></div>
                <?php else : ?>
                    <table class="vs-dashboard-table">
                        <thead>
                            <tr>
                                <th><?= vs_dashboard_escape(__('Date', 'vehiclescheduler')) ?></th>
                                <th><?= vs_dashboard_escape(__('Type', 'vehiclescheduler')) ?></th>
                                <th><?= vs_dashboard_escape(__('Vehicle', 'vehiclescheduler')) ?></th>
                                <th><?= vs_dashboard_escape(__('Status')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($openIncidents as $incident) : ?>
                                <?php
                                $statusClass = (int) ($incident['status'] ?? 0) === 1
                                    ? 'vs-dashboard-badge--open'
                                    : 'vs-dashboard-badge--analyzing';
                                $statusLabel = (int) ($incident['status'] ?? 0) === 1
                                    ? __('Open', 'vehiclescheduler')
                                    : __('Analyzing', 'vehiclescheduler');
                                ?>
                                <tr>
                                    <td><?= Html::convDate(substr((string) ($incident['incident_date'] ?? ''), 0, 10)) ?></td>
                                    <td>
                                        <a href="<?= vs_dashboard_escape($urls['incident_form'] . '?id=' . (int) ($incident['id'] ?? 0)) ?>">
                                            <?= vs_dashboard_escape($incidentTypes[(int) ($incident['incident_type'] ?? 0)] ?? '?') ?>
                                        </a>
                                    </td>
                                    <td><?= vs_dashboard_vehicle_name((int) ($incident['plugin_vehiclescheduler_vehicles_id'] ?? 0)) ?></td>
                                    <td><span class="vs-dashboard-badge <?= vs_dashboard_escape($statusClass) ?>"><?= vs_dashboard_escape($statusLabel) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="vs-dashboard-card">
            <?php vs_dashboard_section('ti-tool', __('Upcoming Maintenances', 'vehiclescheduler'), $urls['maintenance'], __('View all', 'vehiclescheduler')); ?>
            <div class="vs-dashboard-card-body">
                <?php if ($upcomingMaintenances === []) : ?>
                    <div class="vs-dashboard-empty"><?= vs_dashboard_escape(__('No maintenances pending', 'vehiclescheduler')) ?></div>
                <?php else : ?>
                    <table class="vs-dashboard-table">
                        <thead>
                            <tr>
                                <th><?= vs_dashboard_escape(__('Vehicle', 'vehiclescheduler')) ?></th>
                                <th><?= vs_dashboard_escape(__('Type', 'vehiclescheduler')) ?></th>
                                <th><?= vs_dashboard_escape(__('Scheduled', 'vehiclescheduler')) ?></th>
                                <th><?= vs_dashboard_escape(__('Status')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingMaintenances as $maintenance) : ?>
                                <?php
                                $overdue = !empty($maintenance['scheduled_date'])
                                    && (string) $maintenance['scheduled_date'] < date('Y-m-d')
                                    && (int) ($maintenance['status'] ?? 0) === 1;
                                $statusClass = (int) ($maintenance['status'] ?? 0) === 1
                                    ? 'vs-dashboard-badge--scheduled'
                                    : 'vs-dashboard-badge--progress';
                                $statusLabel = (int) ($maintenance['status'] ?? 0) === 1
                                    ? __('Scheduled', 'vehiclescheduler')
                                    : __('In Progress', 'vehiclescheduler');

                                if ($overdue) {
                                    $statusClass = 'vs-dashboard-badge--open';
                                    $statusLabel = __('Overdue', 'vehiclescheduler');
                                }
                                ?>
                                <tr>
                                    <td><?= vs_dashboard_vehicle_name((int) ($maintenance['plugin_vehiclescheduler_vehicles_id'] ?? 0)) ?></td>
                                    <td>
                                        <a href="<?= vs_dashboard_escape($urls['maintenance_form'] . '?id=' . (int) ($maintenance['id'] ?? 0)) ?>">
                                            <?= vs_dashboard_escape($maintenanceTypes[(int) ($maintenance['type'] ?? 0)] ?? '?') ?>
                                        </a>
                                    </td>
                                    <td><?= Html::convDate((string) ($maintenance['scheduled_date'] ?? '')) ?></td>
                                    <td><span class="vs-dashboard-badge <?= vs_dashboard_escape($statusClass) ?>"><?= vs_dashboard_escape($statusLabel) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="vs-dashboard-card vs-dashboard-card--spaced">
        <?php vs_dashboard_section('ti-shield-check', __('Insurance Claims in Progress', 'vehiclescheduler'), $urls['insurance'], __('View all', 'vehiclescheduler')); ?>
        <div class="vs-dashboard-card-body">
            <?php if ($openClaims === []) : ?>
                <div class="vs-dashboard-empty"><?= vs_dashboard_escape(__('No open claims', 'vehiclescheduler')) ?></div>
            <?php else : ?>
                <table class="vs-dashboard-table">
                    <thead>
                        <tr>
                            <th>Claim #</th>
                            <th><?= vs_dashboard_escape(__('Vehicle', 'vehiclescheduler')) ?></th>
                            <th>Insurer</th>
                            <th><?= vs_dashboard_escape(__('Opened', 'vehiclescheduler')) ?></th>
                            <th>Est. Value</th>
                            <th><?= vs_dashboard_escape(__('Status')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($openClaims as $claim) : ?>
                            <?php
                            $statusClass = (int) ($claim['status'] ?? 0) === 1
                                ? 'vs-dashboard-badge--open'
                                : 'vs-dashboard-badge--analyzing';
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= vs_dashboard_escape($urls['insurance_form'] . '?id=' . (int) ($claim['id'] ?? 0)) ?>">
                                        <?= vs_dashboard_escape((string) ($claim['claim_number'] ?? ('#' . (int) ($claim['id'] ?? 0)))) ?>
                                    </a>
                                </td>
                                <td><?= vs_dashboard_vehicle_name((int) ($claim['plugin_vehiclescheduler_vehicles_id'] ?? 0)) ?></td>
                                <td><?= vs_dashboard_escape((string) ($claim['insurance_company'] ?? '')) ?></td>
                                <td><?= Html::convDate((string) ($claim['opening_date'] ?? '')) ?></td>
                                <td>R$ <?= number_format((float) ($claim['estimated_value'] ?? 0), 2, ',', '.') ?></td>
                                <td>
                                    <span class="vs-dashboard-badge <?= vs_dashboard_escape($statusClass) ?>">
                                        <?= vs_dashboard_escape($claimStatuses[(int) ($claim['status'] ?? 0)] ?? '?') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php Html::footer(); ?>