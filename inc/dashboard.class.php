<?php

/**
 * Fleet dashboard service layer.
 *
 * Responsibilities:
 * - provide data for the operational dashboard;
 * - provide data for the executive wallboard;
 * - centralize dashboard-specific queries and aggregations;
 * - execute quick actions triggered from dashboard screens.
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerDashboard
{
    /**
     * Reservation status: new.
     */
    private const RESERVATION_STATUS_NEW = 1;

    /**
     * Reservation status: approved.
     */
    private const RESERVATION_STATUS_APPROVED = 2;

    /**
     * Reservation status: rejected.
     */
    private const RESERVATION_STATUS_REJECTED = 3;

    /**
     * Reservation status: cancelled.
     */
    private const RESERVATION_STATUS_CANCELLED = 4;

    /**
     * Default requester label used when no valid requester can be resolved.
     */
    private const DEFAULT_REQUESTER_LABEL = 'Solicitante não identificado';

    /**
     * Returns the full payload required by the operational dashboard.
     *
     * @return array<string,mixed>
     */
    public static function getDashboardData(): array
    {
        $checklists_enabled = self::hasChecklistTables();

        $kpi = [
            'vehicles_active'        => self::countRows('glpi_plugin_vehiclescheduler_vehicles', [
                'is_active' => 1,
            ]),
            'vehicles_total'         => self::countRows('glpi_plugin_vehiclescheduler_vehicles'),
            'drivers_active'         => self::countRows('glpi_plugin_vehiclescheduler_drivers', [
                'is_active'   => 1,
                'is_approved' => 1,
            ]),
            'reservations_new'       => self::countRows('glpi_plugin_vehiclescheduler_schedules', [
                'status' => self::RESERVATION_STATUS_NEW,
            ]),
            'reservations_approved'  => self::countRows('glpi_plugin_vehiclescheduler_schedules', [
                'status' => self::RESERVATION_STATUS_APPROVED,
            ]),
            'incidents_open'         => self::countRows('glpi_plugin_vehiclescheduler_incidents', [
                'status' => [1, 2],
            ]),
            'maintenances_active'    => self::countRows('glpi_plugin_vehiclescheduler_maintenances', [
                'status' => [1, 2],
            ]),
            'maintenance_cost_total' => self::sumMaintenanceCost(),
            'fines_open'             => self::countRows('glpi_plugin_vehiclescheduler_driverfines', [
                'status' => 1,
            ]),
            'checklist_templates'    => 0,
            'checklist_pending'      => 0,
            'checklist_completed'    => 0,
        ];

        if ($checklists_enabled) {
            $kpi['checklist_templates'] = self::countRows(
                'glpi_plugin_vehiclescheduler_checklists',
                ['is_active' => 1]
            );
            $kpi['checklist_pending'] = self::countPendingDepartureChecklists();
            $kpi['checklist_completed'] = self::countRows(
                'glpi_plugin_vehiclescheduler_checklistresponses'
            );
        }

        return [
            'checklists_enabled' => $checklists_enabled,
            'kpi'                => $kpi,
            'charts'             => [
                'reservations_by_status'   => self::getReservationsByStatus(),
                'maintenances_by_type'     => self::getMaintenancesByType(),
                'maintenance_cost_monthly' => self::getMaintenanceCostByMonth(6),
            ],
            'lists'              => [
                'pending_reservations' => self::getPendingReservations(5),
                'pending_drivers'      => self::getPendingDrivers(10),
                'cnh_alerts'           => self::getCnhAlerts(5, 90),
                'recent_incidents'     => self::getRecentIncidents(5),
                'top_vehicles'         => self::getTopVehiclesUsage(5),
                'top_drivers'          => self::getTopDriversUsage(5),
            ],
        ];
    }

    /**
     * Returns the full payload required by the executive wallboard.
     *
     * @return array<string,mixed>
     */
    public static function getExecutiveBoardData(): array
    {
        $base = self::getDashboardData();

        $vehicles_total   = (int)$base['kpi']['vehicles_total'];
        $vehicles_active  = (int)$base['kpi']['vehicles_active'];
        $availability_pct = $vehicles_total > 0
            ? round(($vehicles_active / $vehicles_total) * 100, 1)
            : 0.0;

        $reservations_total =
            (int)$base['charts']['reservations_by_status']['new']
            + (int)$base['charts']['reservations_by_status']['approved']
            + (int)$base['charts']['reservations_by_status']['rejected']
            + (int)$base['charts']['reservations_by_status']['cancelled'];

        $approval_rate = $reservations_total > 0
            ? round(((int)$base['charts']['reservations_by_status']['approved'] / $reservations_total) * 100, 1)
            : 0.0;

        return [
            'summary'  => [
                'vehicles_active'      => $vehicles_active,
                'vehicles_total'       => $vehicles_total,
                'vehicles_unavailable' => max(0, $vehicles_total - $vehicles_active),
                'availability_pct'     => $availability_pct,
                'pending_requests'     => (int)$base['kpi']['reservations_new'],
                'trips_in_progress'    => self::countTripsInProgress(),
                'incidents_open'       => (int)$base['kpi']['incidents_open'],
                'maintenances_active'  => (int)$base['kpi']['maintenances_active'],
                'cnh_critical'         => self::countCnhWithinDays(30),
                'checklist_pending'    => (int)$base['kpi']['checklist_pending'],
                'approval_rate'        => $approval_rate,
                'maintenance_cost'     => (float)$base['kpi']['maintenance_cost_total'],
                'fines_open'           => (int)$base['kpi']['fines_open'],
            ],
            'charts'   => $base['charts'],
            'lists'    => [
                'top_vehicles'     => self::getTopVehiclesUsage(5),
                'top_drivers'      => self::getTopDriversUsage(5),
                'recent_requests'  => self::getRecentRequests(5),
                'recent_incidents' => self::getRecentIncidents(5),
                'cnh_alerts'       => self::getCnhAlerts(5, 90),
            ],
            'realtime' => self::getRealtimeAlertPayload(),
        ];
    }

    /**
     * Returns the realtime polling payload used by the executive wallboard.
     *
     * @return array<string,mixed>
     */
    public static function getRealtimeAlertPayload(): array
    {
        $latest_request = self::getLatestRequest();
        $pending_count  = self::countRows('glpi_plugin_vehiclescheduler_schedules', [
            'status' => self::RESERVATION_STATUS_NEW,
        ]);

        return [
            'pending_count'  => $pending_count,
            'latest_request' => $latest_request,
            'generated_at'   => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Approves a reservation from dashboard quick actions.
     *
     * @param int $schedule_id
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public static function approveSchedule(int $schedule_id): void
    {
        if ($schedule_id <= 0) {
            throw new RuntimeException('Reserva inválida.');
        }

        $schedule = new PluginVehicleschedulerSchedule();
        if (!$schedule->getFromDB($schedule_id)) {
            throw new RuntimeException('Reserva não encontrada.');
        }

        $schedule->update([
            'id'     => $schedule_id,
            'status' => self::RESERVATION_STATUS_APPROVED,
        ]);
    }

    /**
     * Rejects a reservation from dashboard quick actions.
     *
     * @param int $schedule_id
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public static function rejectSchedule(int $schedule_id): void
    {
        if ($schedule_id <= 0) {
            throw new RuntimeException('Reserva inválida.');
        }

        $schedule = new PluginVehicleschedulerSchedule();
        if (!$schedule->getFromDB($schedule_id)) {
            throw new RuntimeException('Reserva não encontrada.');
        }

        $schedule->update([
            'id'     => $schedule_id,
            'status' => self::RESERVATION_STATUS_REJECTED,
        ]);
    }

    /**
     * Approves and activates a driver.
     *
     * @param int $driver_id
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public static function approveDriver(int $driver_id): void
    {
        if ($driver_id <= 0) {
            throw new RuntimeException('Motorista inválido.');
        }

        $driver = new PluginVehicleschedulerDriver();
        if (!$driver->getFromDB($driver_id)) {
            throw new RuntimeException('Motorista não encontrado.');
        }

        $driver->update([
            'id'            => $driver_id,
            'is_approved'   => 1,
            'is_active'     => 1,
            'approved_by'   => (int)Session::getLoginUserID(),
            'approval_date' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Rejects a driver without deleting the record.
     *
     * @param int $driver_id
     *
     * @return void
     *
     * @throws RuntimeException
     */
    public static function rejectDriver(int $driver_id): void
    {
        if ($driver_id <= 0) {
            throw new RuntimeException('Motorista inválido.');
        }

        $driver = new PluginVehicleschedulerDriver();
        if (!$driver->getFromDB($driver_id)) {
            throw new RuntimeException('Motorista não encontrado.');
        }

        $driver->update([
            'id'          => $driver_id,
            'is_approved' => 0,
            'is_active'   => 0,
        ]);
    }

    /**
     * Checks whether checklist tables exist.
     *
     * @return bool
     */
    private static function hasChecklistTables(): bool
    {
        global $DB;

        return $DB->tableExists('glpi_plugin_vehiclescheduler_checklists')
            && $DB->tableExists('glpi_plugin_vehiclescheduler_checklistresponses');
    }

    /**
     * Counts rows in a table using the GLPI request builder.
     *
     * @param string              $table
     * @param array<string,mixed> $where
     *
     * @return int
     */
    private static function countRows(string $table, array $where = []): int
    {
        global $DB;

        $criteria = [
            'COUNT' => 'c',
            'FROM'  => $table,
        ];

        if (!empty($where)) {
            $criteria['WHERE'] = $where;
        }

        $row = $DB->request($criteria)->current();

        return (int)($row['c'] ?? 0);
    }

    /**
     * Returns the total maintenance cost.
     *
     * @return float
     */
    private static function sumMaintenanceCost(): float
    {
        global $DB;

        $row = $DB->request([
            'SELECT' => ['SUM' => 'cost AS total'],
            'FROM'   => 'glpi_plugin_vehiclescheduler_maintenances',
            'WHERE'  => ['status' => [1, 2, 3]],
        ])->current();

        return (float)($row['total'] ?? 0);
    }

    /**
     * Counts approved schedules that still do not have a departure checklist.
     *
     * @return int
     */
    private static function countPendingDepartureChecklists(): int
    {
        global $DB;

        $today_start = date('Y-m-d 00:00:00');

        $approved_schedules = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_plugin_vehiclescheduler_schedules',
            'WHERE'  => [
                'status'     => self::RESERVATION_STATUS_APPROVED,
                'begin_date' => ['>=', $today_start],
            ],
        ]);

        $pending_count = 0;

        foreach ($approved_schedules as $schedule) {
            $response = $DB->request([
                'COUNT' => 'c',
                'FROM'  => 'glpi_plugin_vehiclescheduler_checklistresponses',
                'WHERE' => [
                    'plugin_vehiclescheduler_schedules_id' => (int)$schedule['id'],
                    'response_type'                        => 'departure',
                ],
            ])->current();

            if ((int)($response['c'] ?? 0) === 0) {
                $pending_count++;
            }
        }

        return $pending_count;
    }

    /**
     * Returns reservation counts grouped by status.
     *
     * @return array<string,int>
     */
    private static function getReservationsByStatus(): array
    {
        return [
            'new'       => self::countRows('glpi_plugin_vehiclescheduler_schedules', [
                'status' => self::RESERVATION_STATUS_NEW,
            ]),
            'approved'  => self::countRows('glpi_plugin_vehiclescheduler_schedules', [
                'status' => self::RESERVATION_STATUS_APPROVED,
            ]),
            'rejected'  => self::countRows('glpi_plugin_vehiclescheduler_schedules', [
                'status' => self::RESERVATION_STATUS_REJECTED,
            ]),
            'cancelled' => self::countRows('glpi_plugin_vehiclescheduler_schedules', [
                'status' => self::RESERVATION_STATUS_CANCELLED,
            ]),
        ];
    }

    /**
     * Returns maintenance counts grouped by type.
     *
     * @return array<string,int>
     */
    private static function getMaintenancesByType(): array
    {
        return [
            'preventive' => self::countRows('glpi_plugin_vehiclescheduler_maintenances', ['type' => 1]),
            'corrective' => self::countRows('glpi_plugin_vehiclescheduler_maintenances', ['type' => 2]),
        ];
    }

    /**
     * Returns maintenance cost grouped by month.
     *
     * @param int $months
     *
     * @return array<string,array<int,mixed>>
     */
    private static function getMaintenanceCostByMonth(int $months = 6): array
    {
        global $DB;

        $months = max(1, $months);

        $buckets = [];
        $labels  = [];
        $values  = [];

        $base = new DateTimeImmutable('first day of this month');

        for ($i = $months - 1; $i >= 0; $i--) {
            $period = $base->modify("-{$i} months");
            $key = $period->format('Y-m');

            $buckets[$key] = 0.0;
            $labels[$key]  = $period->format('m/Y');
        }

        $rows = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
            'WHERE' => ['status' => [1, 2, 3]],
        ]), false);

        foreach ($rows as $row) {
            $reference_date = self::extractMaintenanceReferenceDate($row);
            if ($reference_date === null) {
                continue;
            }

            $key = $reference_date->format('Y-m');
            if (!array_key_exists($key, $buckets)) {
                continue;
            }

            $buckets[$key] += (float)($row['cost'] ?? 0);
        }

        foreach ($buckets as $value) {
            $values[] = round($value, 2);
        }

        return [
            'labels' => array_values($labels),
            'values' => $values,
        ];
    }

    /**
     * Returns the latest pending reservations enriched with vehicle names
     * and requester names.
     *
     * @param int $limit
     *
     * @return array<int,array<string,mixed>>
     */
    private static function getPendingReservations(int $limit): array
    {
        global $DB;

        $rows = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
            'WHERE' => ['status' => self::RESERVATION_STATUS_NEW],
            'ORDER' => ['date_creation DESC'],
            'LIMIT' => $limit,
        ]), false);

        return self::enrichSchedules($rows, 60);
    }

    /**
     * Returns pending drivers waiting for approval.
     *
     * @param int $limit
     *
     * @return array<int,array<string,mixed>>
     */
    private static function getPendingDrivers(int $limit): array
    {
        global $DB;

        return iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_drivers',
            'WHERE' => ['is_approved' => 0],
            'ORDER' => ['date_creation DESC'],
            'LIMIT' => $limit,
        ]), false);
    }

    /**
     * Returns recent requests enriched with vehicle names
     * and requester names.
     *
     * @param int $limit
     *
     * @return array<int,array<string,mixed>>
     */
    private static function getRecentRequests(int $limit): array
    {
        global $DB;

        $rows = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
            'ORDER' => ['date_creation DESC'],
            'LIMIT' => $limit,
        ]), false);

        return self::enrichSchedules($rows, null);
    }

    /**
     * Returns the most recent request enriched for realtime polling.
     *
     * @return array<string,mixed>|null
     */
    private static function getLatestRequest(): ?array
    {
        $rows = self::getRecentRequests(1);

        if (empty($rows)) {
            return null;
        }

        return $rows[0];
    }

    /**
     * Returns driver license alerts within the specified time window.
     *
     * @param int $limit
     * @param int $window_days
     *
     * @return array<int,array<string,mixed>>
     */
    private static function getCnhAlerts(int $limit, int $window_days): array
    {
        global $DB;

        $today = new DateTimeImmutable('today');
        $drivers = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_drivers',
            'WHERE' => [
                'is_active'   => 1,
                'is_approved' => 1,
            ],
            'ORDER' => ['cnh_expiry ASC'],
        ]), false);

        $alerts = [];

        foreach ($drivers as $driver) {
            $expiry = (string)($driver['cnh_expiry'] ?? '');

            if ($expiry === '' || $expiry === '0000-00-00') {
                continue;
            }

            $expiry_date = DateTimeImmutable::createFromFormat('Y-m-d', $expiry);
            if (!$expiry_date instanceof DateTimeImmutable) {
                continue;
            }

            $days = (int)$today->diff($expiry_date)->format('%r%a');
            if ($days < 0 || $days > $window_days) {
                continue;
            }

            $driver['days_to_expiry'] = $days;
            $alerts[] = $driver;

            if (count($alerts) >= $limit) {
                break;
            }
        }

        return $alerts;
    }

    /**
     * Counts driver licenses expiring within the given number of days.
     *
     * @param int $days_limit
     *
     * @return int
     */
    private static function countCnhWithinDays(int $days_limit): int
    {
        return count(self::getCnhAlerts(9999, $days_limit));
    }

    /**
     * Returns recent incidents enriched with vehicle names.
     *
     * @param int $limit
     *
     * @return array<int,array<string,mixed>>
     */
    private static function getRecentIncidents(int $limit): array
    {
        global $DB;

        $rows = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_incidents',
            'ORDER' => ['incident_date DESC'],
            'LIMIT' => $limit,
        ]), false);

        if (empty($rows)) {
            return [];
        }

        $vehicle_ids = array_values(array_unique(array_map(
            static function (array $row): int {
                return (int)$row['plugin_vehiclescheduler_vehicles_id'];
            },
            $rows
        )));

        $vehicle_map = self::getVehicleMap($vehicle_ids);

        foreach ($rows as &$row) {
            $vehicle_id = (int)$row['plugin_vehiclescheduler_vehicles_id'];
            $row['vehicle_name'] = $vehicle_map[$vehicle_id] ?? ('#' . $vehicle_id);
        }
        unset($row);

        return $rows;
    }

    /**
     * Returns the most used vehicles based on approved reservations.
     *
     * @param int $limit
     *
     * @return array<int,array<string,mixed>>
     */
    private static function getTopVehiclesUsage(int $limit): array
    {
        global $DB;

        $rows = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
            'WHERE' => ['status' => self::RESERVATION_STATUS_APPROVED],
        ]), false);

        if (empty($rows)) {
            return [];
        }

        $aggregated = [];

        foreach ($rows as $row) {
            $vehicle_id = (int)($row['plugin_vehiclescheduler_vehicles_id'] ?? 0);
            if ($vehicle_id <= 0) {
                continue;
            }

            if (!isset($aggregated[$vehicle_id])) {
                $aggregated[$vehicle_id] = [
                    'vehicle_id'         => $vehicle_id,
                    'total_reservations' => 0,
                    'last_use'           => '',
                ];
            }

            $aggregated[$vehicle_id]['total_reservations']++;

            $begin_date = (string)($row['begin_date'] ?? '');
            if ($begin_date !== '' && $begin_date > $aggregated[$vehicle_id]['last_use']) {
                $aggregated[$vehicle_id]['last_use'] = $begin_date;
            }
        }

        uasort($aggregated, static function (array $a, array $b): int {
            if ($a['total_reservations'] === $b['total_reservations']) {
                return strcmp((string)$b['last_use'], (string)$a['last_use']);
            }

            return $b['total_reservations'] <=> $a['total_reservations'];
        });

        $top = array_slice(array_values($aggregated), 0, $limit);
        $vehicle_map = self::getVehicleMap(array_column($top, 'vehicle_id'));

        foreach ($top as &$row) {
            $row['vehicle_name'] = $vehicle_map[(int)$row['vehicle_id']] ?? ('#' . (int)$row['vehicle_id']);
        }
        unset($row);

        return $top;
    }

    /**
     * Returns the most used drivers based on approved reservations.
     *
     * @param int $limit
     *
     * @return array<int,array<string,mixed>>
     */
    private static function getTopDriversUsage(int $limit): array
    {
        global $DB;

        $rows = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
            'WHERE' => ['status' => self::RESERVATION_STATUS_APPROVED],
        ]), false);

        if (empty($rows)) {
            return [];
        }

        $aggregated = [];

        foreach ($rows as $row) {
            $driver_id = (int)($row['plugin_vehiclescheduler_drivers_id'] ?? 0);
            if ($driver_id <= 0) {
                continue;
            }

            if (!isset($aggregated[$driver_id])) {
                $aggregated[$driver_id] = [
                    'driver_id'          => $driver_id,
                    'total_reservations' => 0,
                    'last_use'           => '',
                ];
            }

            $aggregated[$driver_id]['total_reservations']++;

            $begin_date = (string)($row['begin_date'] ?? '');
            if ($begin_date !== '' && $begin_date > $aggregated[$driver_id]['last_use']) {
                $aggregated[$driver_id]['last_use'] = $begin_date;
            }
        }

        uasort($aggregated, static function (array $a, array $b): int {
            if ($a['total_reservations'] === $b['total_reservations']) {
                return strcmp((string)$b['last_use'], (string)$a['last_use']);
            }

            return $b['total_reservations'] <=> $a['total_reservations'];
        });

        $top = array_slice(array_values($aggregated), 0, $limit);
        $driver_map = self::getDriverMap(array_column($top, 'driver_id'));

        foreach ($top as &$row) {
            $driver_id = (int)$row['driver_id'];
            $row['driver_name'] = $driver_map[$driver_id]['name'] ?? ('#' . $driver_id);
            $row['users_id']    = $driver_map[$driver_id]['users_id'] ?? 0;
        }
        unset($row);

        return $top;
    }

    /**
     * Counts trips currently in progress.
     *
     * A trip is considered in progress when:
     * - the reservation is approved;
     * - current datetime is between begin_date and end_date.
     *
     * @return int
     */
    private static function countTripsInProgress(): int
    {
        global $DB;

        $now = date('Y-m-d H:i:s');

        $rows = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
            'WHERE' => ['status' => self::RESERVATION_STATUS_APPROVED],
        ]), false);

        $count = 0;

        foreach ($rows as $row) {
            $begin = (string)($row['begin_date'] ?? '');
            $end   = (string)($row['end_date'] ?? '');

            if ($begin !== '' && $end !== '' && $begin <= $now && $end >= $now) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Enriches schedule rows with vehicle and requester labels.
     *
     * @param array<int,array<string,mixed>> $rows
     * @param int|null                       $destination_limit
     *
     * @return array<int,array<string,mixed>>
     */
    private static function enrichSchedules(array $rows, ?int $destination_limit): array
    {
        if (empty($rows)) {
            return [];
        }

        $vehicle_ids = array_values(array_unique(array_map(
            static function (array $row): int {
                return (int)$row['plugin_vehiclescheduler_vehicles_id'];
            },
            $rows
        )));

        $vehicle_map = self::getVehicleMap($vehicle_ids);

        foreach ($rows as &$row) {
            $vehicle_id = (int)$row['plugin_vehiclescheduler_vehicles_id'];
            $users_id   = (int)($row['users_id'] ?? 0);

            $row['vehicle_name']   = $vehicle_map[$vehicle_id] ?? ('#' . $vehicle_id);
            $row['requester_name'] = self::resolveRequesterName($users_id);

            $destination = trim((string)($row['destination'] ?? ''));
            if ($destination_limit !== null) {
                $destination = mb_substr($destination, 0, $destination_limit);
            }

            $row['destination'] = $destination;
        }
        unset($row);

        return $rows;
    }

    /**
     * Resolves a requester display label from a GLPI user identifier.
     *
     * @param int $users_id
     *
     * @return string
     */
    private static function resolveRequesterName(int $users_id): string
    {
        if ($users_id <= 0) {
            return self::DEFAULT_REQUESTER_LABEL;
        }

        $name = trim((string)getUserName($users_id));

        return $name !== '' ? $name : self::DEFAULT_REQUESTER_LABEL;
    }

    /**
     * Extracts the most relevant maintenance reference date.
     *
     * Priority order:
     * - completion_date
     * - scheduled_date
     * - date_creation
     *
     * @param array<string,mixed> $row
     *
     * @return DateTimeImmutable|null
     */
    private static function extractMaintenanceReferenceDate(array $row): ?DateTimeImmutable
    {
        $date_source = '';

        if (!empty($row['completion_date'])) {
            $date_source = (string)$row['completion_date'];
        } elseif (!empty($row['scheduled_date'])) {
            $date_source = (string)$row['scheduled_date'];
        } elseif (!empty($row['date_creation'])) {
            $date_source = substr((string)$row['date_creation'], 0, 10);
        }

        if ($date_source === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $date_source);

        return $date instanceof DateTimeImmutable ? $date : null;
    }

    /**
     * Loads vehicle labels in a single batch query.
     *
     * @param array<int,int> $vehicle_ids
     *
     * @return array<int,string>
     */
    private static function getVehicleMap(array $vehicle_ids): array
    {
        global $DB;

        if (empty($vehicle_ids)) {
            return [];
        }

        $map = [];

        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_vehicles',
            'WHERE' => ['id' => $vehicle_ids],
        ]);

        foreach ($iterator as $row) {
            $label = trim((string)$row['name']);

            if (trim((string)($row['plate'] ?? '')) !== '') {
                $label .= ' (' . (string)$row['plate'] . ')';
            }

            $map[(int)$row['id']] = $label;
        }

        return $map;
    }

    /**
     * Loads driver labels in a single batch query.
     *
     * @param array<int,int> $driver_ids
     *
     * @return array<int,array<string,mixed>>
     */
    private static function getDriverMap(array $driver_ids): array
    {
        global $DB;

        if (empty($driver_ids)) {
            return [];
        }

        $map = [];

        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_drivers',
            'WHERE' => ['id' => $driver_ids],
        ]);

        foreach ($iterator as $row) {
            $map[(int)$row['id']] = [
                'name'     => trim((string)$row['name']),
                'users_id' => (int)($row['users_id'] ?? 0),
            ];
        }

        return $map;
    }
}