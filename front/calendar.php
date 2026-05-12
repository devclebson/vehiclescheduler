<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

plugin_vehiclescheduler_redirect_future_plan('CALENDAR', 'EM OMRAS !!!');
exit;

global $DB;

$rootDoc = plugin_vehiclescheduler_get_root_doc();

$month = PluginVehicleschedulerInput::int($_GET, 'month', (int) date('n'), 1, 12);
$year = PluginVehicleschedulerInput::int($_GET, 'year', (int) date('Y'), 2020, 2100);

$monthStart = sprintf('%04d-%02d-01', $year, $month);
$monthStartDateTime = $monthStart . ' 00:00:00';
$monthEnd = date('Y-m-t', strtotime($monthStart));
$monthEndDateTime = $monthEnd . ' 23:59:59';

$reservations = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => [
        'OR' => [
            [
                'AND' => [
                    ['begin_date' => ['>=', $monthStartDateTime]],
                    ['begin_date' => ['<=', $monthEndDateTime]],
                ],
            ],
            [
                'AND' => [
                    ['end_date' => ['>=', $monthStartDateTime]],
                    ['end_date' => ['<=', $monthEndDateTime]],
                ],
            ],
        ],
    ],
]));

$maintenances = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
    'WHERE' => [
        'AND' => [
            ['scheduled_date' => ['>=', $monthStart]],
            ['scheduled_date' => ['<=', $monthEnd]],
        ],
    ],
]));

$incidents = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_incidents',
    'WHERE' => [
        'AND' => [
            ['incident_date' => ['>=', $monthStartDateTime]],
            ['incident_date' => ['<=', $monthEndDateTime]],
        ],
    ],
]));

$vehicleIds = [];
$userIds = [];

foreach ($reservations as $reservation) {
    $vehicleIds[] = (int) ($reservation['plugin_vehiclescheduler_vehicles_id'] ?? 0);
    $userIds[] = (int) ($reservation['users_id'] ?? 0);
}

foreach ($maintenances as $maintenance) {
    $vehicleIds[] = (int) ($maintenance['plugin_vehiclescheduler_vehicles_id'] ?? 0);
}

foreach ($incidents as $incident) {
    $vehicleIds[] = (int) ($incident['plugin_vehiclescheduler_vehicles_id'] ?? 0);
}

$vehicleIds = array_values(array_unique(array_filter($vehicleIds)));
$userIds = array_values(array_unique(array_filter($userIds)));

$vehicleMap = [];
$userMap = [];

if ($vehicleIds !== []) {
    foreach (
        $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_vehicles',
            'WHERE' => ['id' => $vehicleIds],
        ]) as $row
    ) {
        $vehicleMap[(int) $row['id']] = [
            'name'  => (string) ($row['name'] ?? ''),
            'plate' => (string) ($row['plate'] ?? ''),
        ];
    }
}

if ($userIds !== []) {
    foreach (
        $DB->request([
            'FROM'  => 'glpi_users',
            'WHERE' => ['id' => $userIds],
        ]) as $row
    ) {
        $userMap[(int) $row['id']] = (string) ($row['name'] ?? '');
    }
}

$eventsByDay = [];

$scheduleStatusMeta = [
    1 => ['label' => 'Nova', 'variant' => 'new'],
    2 => ['label' => 'Aprovada', 'variant' => 'approved'],
    3 => ['label' => 'Recusada', 'variant' => 'rejected'],
    4 => ['label' => 'Cancelada', 'variant' => 'cancelled'],
];

foreach ($reservations as $reservation) {
    $start = substr((string) ($reservation['begin_date'] ?? ''), 0, 10);
    $end = substr((string) ($reservation['end_date'] ?? ''), 0, 10);
    $vehicle = $vehicleMap[(int) ($reservation['plugin_vehiclescheduler_vehicles_id'] ?? 0)] ?? ['name' => 'Veiculo', 'plate' => ''];
    $requester = $userMap[(int) ($reservation['users_id'] ?? 0)] ?? '';
    $status = $scheduleStatusMeta[(int) ($reservation['status'] ?? 0)] ?? ['label' => '', 'variant' => 'new'];
    $current = $start;

    while ($current !== '' && $current <= $end && $current <= $monthEnd) {
        if ($current >= $monthStart) {
            $eventsByDay[$current][] = [
                'tipo'           => 'reserva',
                'status'         => (int) ($reservation['status'] ?? 0),
                'status_label'   => $status['label'],
                'status_variant' => $status['variant'],
                'titulo'         => 'Reserva: ' . $vehicle['name'],
                'link'           => plugin_vehiclescheduler_get_front_url('schedule.form.php') . '?id=' . (int) ($reservation['id'] ?? 0),
                'veiculo'        => $vehicle['name'],
                'placa'          => $vehicle['plate'],
                'solicitante'    => $requester,
                'destino'        => (string) ($reservation['destination'] ?? ''),
                'horario'        => Html::convDateTime((string) ($reservation['begin_date'] ?? ''))
                    . ' - '
                    . Html::convDateTime((string) ($reservation['end_date'] ?? '')),
            ];
        }

        $current = date('Y-m-d', strtotime($current . ' +1 day'));
    }
}

foreach ($maintenances as $maintenance) {
    $date = (string) ($maintenance['scheduled_date'] ?? '');

    if ($date === '') {
        continue;
    }

    $vehicle = $vehicleMap[(int) ($maintenance['plugin_vehiclescheduler_vehicles_id'] ?? 0)] ?? ['name' => 'Veiculo', 'plate' => ''];

    $eventsByDay[$date][] = [
        'tipo'      => 'manutencao',
        'titulo'    => 'Manutencao: ' . $vehicle['name'],
        'link'      => plugin_vehiclescheduler_get_front_url('maintenance.form.php') . '?id=' . (int) ($maintenance['id'] ?? 0),
        'veiculo'   => $vehicle['name'],
        'placa'     => $vehicle['plate'],
        'fornecedor' => (string) ($maintenance['supplier'] ?? ''),
        'custo'     => (float) ($maintenance['cost'] ?? 0),
        'descricao' => (string) ($maintenance['description'] ?? ''),
    ];
}

foreach ($incidents as $incident) {
    $date = substr((string) ($incident['incident_date'] ?? ''), 0, 10);

    if ($date === '') {
        continue;
    }

    $vehicle = $vehicleMap[(int) ($incident['plugin_vehiclescheduler_vehicles_id'] ?? 0)] ?? ['name' => 'Veiculo', 'plate' => ''];

    $eventsByDay[$date][] = [
        'tipo'      => 'incidente',
        'titulo'    => 'Incidente: ' . $vehicle['name'],
        'link'      => plugin_vehiclescheduler_get_front_url('incident.form.php') . '?id=' . (int) ($incident['id'] ?? 0),
        'veiculo'   => $vehicle['name'],
        'placa'     => $vehicle['plate'],
        'local'     => (string) ($incident['location'] ?? ''),
        'descricao' => (string) ($incident['description'] ?? ''),
    ];
}

$previousMonth = $month - 1;
$previousYear = $year;

if ($previousMonth < 1) {
    $previousMonth = 12;
    $previousYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;

if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
$months = ['', 'Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$today = date('Y-m-d');
$firstWeekday = (int) date('w', strtotime($monthStart));
$daysInMonth = (int) date('t', strtotime($monthStart));

$stats = [
    'total'        => count($reservations) + count($maintenances) + count($incidents),
    'reservations' => count($reservations),
    'maintenances' => count($maintenances),
    'incidents'    => count($incidents),
];

Html::header('Calendario de Eventos', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'calendar');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

$calendarJsFile = GLPI_ROOT . '/plugins/vehiclescheduler/public/js/calendar.js';
$calendarJsVer = is_file($calendarJsFile) ? filemtime($calendarJsFile) : PLUGIN_VEHICLESCHEDULER_VERSION;
$calendarJsUrl = plugin_vehiclescheduler_get_public_url('js/calendar.js') . '?v=' . $calendarJsVer;
?>

<div class="vs-calendar-page">
    <div class="vs-calendar-surface">
        <div class="vs-calendar-card">
            <div class="vs-calendar-header">
                <div>
                    <h1 class="vs-calendar-title">
                        <i class="ti ti-calendar-event"></i>
                        <?= htmlspecialchars($months[$month] . ' de ' . $year, ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <p class="vs-calendar-subtitle">
                        Leitura rapida do mes com reservas, manutencoes e incidentes da frota em uma unica visao.
                    </p>
                </div>

                <div class="vs-calendar-nav">
                    <a href="?month=<?= (int) $previousMonth ?>&year=<?= (int) $previousYear ?>" class="vs-calendar-nav-btn">
                        <i class="ti ti-arrow-left"></i>
                        Anterior
                    </a>
                    <a href="?month=<?= (int) date('n') ?>&year=<?= (int) date('Y') ?>" class="vs-calendar-nav-btn">
                        <i class="ti ti-calendar"></i>
                        Hoje
                    </a>
                    <a href="?month=<?= (int) $nextMonth ?>&year=<?= (int) $nextYear ?>" class="vs-calendar-nav-btn">
                        Proximo
                        <i class="ti ti-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="vs-calendar-overview">
                <div class="vs-calendar-overview-grid">
                    <div class="vs-calendar-overview-card">
                        <div class="vs-calendar-overview-value"><?= (int) $stats['total'] ?></div>
                        <div class="vs-calendar-overview-label">Eventos no mes</div>
                    </div>
                    <div class="vs-calendar-overview-card">
                        <div class="vs-calendar-overview-value"><?= (int) $stats['reservations'] ?></div>
                        <div class="vs-calendar-overview-label">Reservas</div>
                    </div>
                    <div class="vs-calendar-overview-card">
                        <div class="vs-calendar-overview-value"><?= (int) $stats['maintenances'] ?></div>
                        <div class="vs-calendar-overview-label">Manutencoes</div>
                    </div>
                    <div class="vs-calendar-overview-card">
                        <div class="vs-calendar-overview-value"><?= (int) $stats['incidents'] ?></div>
                        <div class="vs-calendar-overview-label">Incidentes</div>
                    </div>
                </div>
            </div>

            <div class="vs-calendar-legend">
                <span class="vs-calendar-legend-title">Legenda</span>
                <div class="vs-calendar-legend-item">
                    <span class="vs-calendar-legend-color vs-calendar-legend-color--reservation"></span>
                    Reservas
                </div>
                <div class="vs-calendar-legend-item">
                    <span class="vs-calendar-legend-color vs-calendar-legend-color--maintenance"></span>
                    Manutencoes
                </div>
                <div class="vs-calendar-legend-item">
                    <span class="vs-calendar-legend-color vs-calendar-legend-color--incident"></span>
                    Incidentes
                </div>
                <div class="vs-calendar-legend-summary">
                    <strong><?= (int) $stats['reservations'] ?></strong> reservas •
                    <strong><?= (int) $stats['maintenances'] ?></strong> manutencoes •
                    <strong><?= (int) $stats['incidents'] ?></strong> incidentes
                </div>
            </div>

            <table class="vs-calendar-table">
                <thead>
                    <tr>
                        <?php foreach ($weekdays as $weekday) : ?>
                            <th><?= htmlspecialchars($weekday, ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $day = 1;

                    for ($week = 0; $week < 6; $week++) :
                        if ($day > $daysInMonth) {
                            break;
                        }
                    ?>
                        <tr>
                            <?php for ($weekday = 0; $weekday < 7; $weekday++) : ?>
                                <?php if ($week === 0 && $weekday < $firstWeekday) : ?>
                                    <td class="vs-calendar-day--outside"></td>
                                <?php elseif ($day > $daysInMonth) : ?>
                                    <td class="vs-calendar-day--outside"></td>
                                <?php else : ?>
                                    <?php
                                    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                    $dayEvents = $eventsByDay[$date] ?? [];
                                    $dayClasses = $date === $today ? 'vs-calendar-day--today' : '';
                                    ?>
                                    <td class="<?= $dayClasses ?>">
                                        <div class="vs-calendar-day-head">
                                            <span class="vs-calendar-day-number"><?= (int) $day ?></span>
                                            <?php if ($dayEvents !== []) : ?>
                                                <span class="vs-calendar-day-count"><?= count($dayEvents) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="vs-calendar-events">
                                            <?php foreach ($dayEvents as $event) : ?>
                                                <?php
                                                $eventClass = 'vs-calendar-event--' . $event['tipo'];

                                                if ($event['tipo'] === 'reserva' && (int) ($event['status'] ?? 0) === 2) {
                                                    $eventClass = 'vs-calendar-event--reservation-approved';
                                                }
                                                ?>
                                                <div
                                                    class="vs-calendar-event <?= htmlspecialchars($eventClass, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-calendar-event="<?= htmlspecialchars(json_encode($event, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <?php $day++; ?>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <div class="vs-calendar-shortcuts">
                <a href="<?= htmlspecialchars(plugin_vehiclescheduler_get_front_url('management.php'), ENT_QUOTES, 'UTF-8') ?>" class="vs-calendar-shortcut vs-calendar-shortcut--dashboard">
                    <i class="ti ti-layout-dashboard"></i>
                    Dashboard
                </a>
                <a href="<?= htmlspecialchars(plugin_vehiclescheduler_get_front_url('schedule.php'), ENT_QUOTES, 'UTF-8') ?>" class="vs-calendar-shortcut vs-calendar-shortcut--schedule">
                    <i class="ti ti-calendar-event"></i>
                    Todas Reservas
                </a>
                <a href="<?= htmlspecialchars(plugin_vehiclescheduler_get_front_url('maintenance.php'), ENT_QUOTES, 'UTF-8') ?>" class="vs-calendar-shortcut vs-calendar-shortcut--maintenance">
                    <i class="ti ti-tool"></i>
                    Manutencoes
                </a>
                <a href="<?= htmlspecialchars(plugin_vehiclescheduler_get_front_url('incident.php'), ENT_QUOTES, 'UTF-8') ?>" class="vs-calendar-shortcut vs-calendar-shortcut--incident">
                    <i class="ti ti-alert-triangle"></i>
                    Incidentes
                </a>
            </div>
        </div>
    </div>
</div>

<div class="vs-calendar-modal" id="vsCalendarModal">
    <div class="vs-calendar-modal-content">
        <div class="vs-calendar-modal-header">
            <h3 id="vsCalendarModalTitle">Detalhes do evento</h3>
            <button type="button" class="vs-calendar-modal-close" data-calendar-close>×</button>
        </div>
        <div class="vs-calendar-modal-body" id="vsCalendarModalBody"></div>
        <div class="vs-calendar-modal-footer">
            <button type="button" class="vs-calendar-btn vs-calendar-btn--secondary" data-calendar-close>Fechar</button>
            <a href="#" id="vsCalendarModalLink" class="vs-calendar-btn vs-calendar-btn--primary">Ver detalhes completos</a>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars($calendarJsUrl, ENT_QUOTES, 'UTF-8') ?>" defer></script>

<?php Html::footer(); ?>