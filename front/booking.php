<?php

/**
 * Fleet booking calendar portal.
 */

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler', READ);

global $DB, $CFG_GLPI;

function vs_booking_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$currentMonth = PluginVehicleschedulerInput::int($_GET, 'month', (int) date('n'), 1, 12);
$currentYear  = PluginVehicleschedulerInput::int($_GET, 'year', (int) date('Y'), 2020, 2100);

$monthStart = sprintf('%04d-%02d-01', $currentYear, $currentMonth);
$monthEnd   = date('Y-m-t', strtotime($monthStart));
$datetimeStart = $monthStart . ' 00:00:00';
$datetimeEnd   = $monthEnd . ' 23:59:59';

$vehicles = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_vehicles',
    'WHERE' => ['is_active' => 1],
    'ORDER' => ['name ASC'],
]));

$reservations = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => [
        'OR' => [
            [
                'AND' => [
                    ['begin_date' => ['>=', $datetimeStart]],
                    ['begin_date' => ['<=', $datetimeEnd]],
                ],
            ],
            [
                'AND' => [
                    ['end_date' => ['>=', $datetimeStart]],
                    ['end_date' => ['<=', $datetimeEnd]],
                ],
            ],
            [
                'AND' => [
                    ['begin_date' => ['<=', $datetimeStart]],
                    ['end_date' => ['>=', $datetimeEnd]],
                ],
            ],
        ],
    ],
]));

$vehicleMap = [];
foreach ($vehicles as $vehicle) {
    $vehicleMap[(int) $vehicle['id']] = $vehicle;
}

$rootDoc = plugin_vehiclescheduler_get_root_doc();

$prevMonth = $currentMonth - 1;
$prevYear  = $currentYear;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $currentMonth + 1;
$nextYear  = $currentYear;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

Html::header(__('Fleet Reservation', 'vehiclescheduler'), $_SERVER['PHP_SELF'], 'plugins', 'menui', 'reservation');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
?>
<div class="vs-booking-page">
    <section class="vs-booking-hero">
        <h1>Reservas de viaturas</h1>
        <p>Visualize a ocupação do mês, identifique conflitos rapidamente e siga para a abertura de uma nova reserva.</p>
    </section>

    <nav class="vs-booking-tabs" aria-label="Navegação do portal">
        <a class="vs-booking-tab is-active" href="<?= vs_booking_escape(plugin_vehiclescheduler_get_front_url('booking.php')) ?>">
            Visão calendário
        </a>
        <a class="vs-booking-tab" href="<?= vs_booking_escape(plugin_vehiclescheduler_get_front_url('requester.php')) ?>">
            Portal
        </a>
    </nav>

    <section class="vs-booking-nav">
        <h2><?= vs_booking_escape(strftime('%B %Y', strtotime($monthStart))) ?></h2>
        <div class="vs-booking-nav__actions">
            <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="vs-booking-nav__link">← Anterior</a>
            <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="vs-booking-nav__link">Hoje</a>
            <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="vs-booking-nav__link">Próximo →</a>
        </div>
    </section>

    <div class="vs-booking-grid">
        <aside class="vs-booking-sidebar">
            <h3>Viaturas ativas</h3>
            <?php if ($vehicles === []) : ?>
                <p class="vs-booking-empty">Nenhuma viatura disponível.</p>
            <?php else : ?>
                <div class="vs-booking-vehicles">
                    <?php foreach ($vehicles as $vehicle) : ?>
                        <div class="vs-booking-vehicle">
                            <strong><?= vs_booking_escape((string) $vehicle['name']) ?></strong>
                            <span><?= vs_booking_escape((string) $vehicle['plate']) ?> • <?= (int) ($vehicle['seats'] ?? 0) ?> assentos</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>

        <section class="vs-booking-calendar-card">
            <table class="vs-booking-calendar">
                <thead>
                    <tr>
                        <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $weekday) : ?>
                            <th><?= vs_booking_escape($weekday) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $firstDay = (int) date('w', strtotime($monthStart));
                    $daysInMonth = (int) date('t', strtotime($monthStart));
                    $day = 1;

                    for ($week = 0; $week < 6; $week++) :
                        if ($day > $daysInMonth) {
                            break;
                        }
                        echo '<tr>';

                        for ($dow = 0; $dow < 7; $dow++) :
                            if ($week === 0 && $dow < $firstDay) {
                                echo '<td></td>';
                                continue;
                            }

                            if ($day > $daysInMonth) {
                                echo '<td></td>';
                                continue;
                            }

                            $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                            echo '<td>';
                            echo '<div class="vs-booking-calendar__day">' . $day . '</div>';

                            foreach ($reservations as $reservation) {
                                $beginDate = substr((string) ($reservation['begin_date'] ?? ''), 0, 10);
                                $endDate   = substr((string) ($reservation['end_date'] ?? ''), 0, 10);

                                if ($date < $beginDate || $date > $endDate) {
                                    continue;
                                }

                                $statusClass = (int) ($reservation['status'] ?? 0) === PluginVehicleschedulerSchedule::STATUS_APPROVED
                                    ? 'is-approved'
                                    : 'is-pending';
                                $vehicle = $vehicleMap[(int) ($reservation['plugin_vehiclescheduler_vehicles_id'] ?? 0)] ?? null;
                                $label = $vehicle ? mb_substr((string) ($vehicle['name'] ?? ''), 0, 12) : '#' . (int) ($reservation['plugin_vehiclescheduler_vehicles_id'] ?? 0);

                                echo '<div class="vs-booking-event ' . $statusClass . '">';
                                echo vs_booking_escape($label);
                                echo '</div>';
                            }

                            echo '</td>';
                            $day++;
                        endfor;

                        echo '</tr>';
                    endfor;
                    ?>
                </tbody>
            </table>
        </section>
    </div>

    <a href="schedule.form.php" class="vs-booking-cta">Nova reserva</a>
</div>
<?php Html::footer(); ?>