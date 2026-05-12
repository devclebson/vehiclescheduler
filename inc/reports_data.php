<?php

/**
 * Reporting data service.
 *
 * This service centralizes report aggregation logic for screen and export consumers.
 */

class PluginVehicleschedulerReportsData
{
    public static function getReservasData($date_start = null, $date_end = null): array
    {
        global $DB;

        [$dateStart, $dateEnd] = self::normalizePeriod($date_start, $date_end);
        $datetimeStart = $dateStart . ' 00:00:00';
        $datetimeEnd   = $dateEnd . ' 23:59:59';

        $reservas = iterator_to_array($DB->request([
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
            'ORDER' => ['begin_date DESC'],
        ]));

        $vehicleMap = self::getLabelMap(
            'glpi_plugin_vehiclescheduler_vehicles',
            self::collectIds($reservas, 'plugin_vehiclescheduler_vehicles_id'),
            static function (array $row): string {
                return trim((string) ($row['name'] ?? '') . ' (' . (string) ($row['plate'] ?? '') . ')');
            }
        );
        $driverRows = self::getRowMap(
            'glpi_plugin_vehiclescheduler_drivers',
            self::collectIds($reservas, 'plugin_vehiclescheduler_drivers_id')
        );
        $groupMap = self::getLabelMap(
            'glpi_groups',
            self::collectIds($reservas, 'groups_id'),
            static function (array $row): string {
                return (string) ($row['name'] ?? '');
            }
        );
        $statuses = PluginVehicleschedulerSchedule::getStatusOptions();

        $data = [];
        foreach ($reservas as $reserva) {
            $driverId   = (int) ($reserva['plugin_vehiclescheduler_drivers_id'] ?? 0);
            $driverUser = (int) ($driverRows[$driverId]['users_id'] ?? 0);

            $data[] = [
                'solicitante' => getUserName((int) ($reserva['users_id'] ?? 0)),
                'grupo'       => $groupMap[(int) ($reserva['groups_id'] ?? 0)] ?? '',
                'veiculo'     => $vehicleMap[(int) ($reserva['plugin_vehiclescheduler_vehicles_id'] ?? 0)] ?? '-',
                'motorista'   => $driverUser > 0 ? getUserName($driverUser) : '-',
                'begin_date'  => (string) ($reserva['begin_date'] ?? ''),
                'end_date'    => (string) ($reserva['end_date'] ?? ''),
                'destino'     => (string) ($reserva['destination'] ?? ''),
                'passageiros' => (int) ($reserva['passengers'] ?? 0),
                'status'      => $statuses[(int) ($reserva['status'] ?? 0)] ?? 'Desconhecido',
                'created'     => (string) ($reserva['date_creation'] ?? ''),
            ];
        }

        return [
            'periodo' => self::formatPeriod($dateStart, $dateEnd),
            'total'   => count($data),
            'data'    => $data,
        ];
    }

    public static function getManutencoesData($date_start = null, $date_end = null): array
    {
        global $DB;

        [$dateStart, $dateEnd] = self::normalizePeriod($date_start, $date_end);

        $manutencoes = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
            'WHERE' => [
                'OR' => [
                    [
                        'AND' => [
                            ['scheduled_date' => ['>=', $dateStart]],
                            ['scheduled_date' => ['<=', $dateEnd]],
                        ],
                    ],
                    [
                        'AND' => [
                            ['completion_date' => ['>=', $dateStart]],
                            ['completion_date' => ['<=', $dateEnd]],
                        ],
                    ],
                ],
            ],
            'ORDER' => ['scheduled_date DESC'],
        ]));

        $vehicleMap = self::getLabelMap(
            'glpi_plugin_vehiclescheduler_vehicles',
            self::collectIds($manutencoes, 'plugin_vehiclescheduler_vehicles_id'),
            static function (array $row): string {
                return trim((string) ($row['name'] ?? '') . ' (' . (string) ($row['plate'] ?? '') . ')');
            }
        );
        $types    = PluginVehicleschedulerMaintenance::getAllTypes();
        $statuses = PluginVehicleschedulerMaintenance::getAllStatus();

        $data       = [];
        $totalCusto = 0.0;

        foreach ($manutencoes as $manutencao) {
            $cost = (float) ($manutencao['cost'] ?? 0);
            $totalCusto += $cost;

            $data[] = [
                'veiculo'        => $vehicleMap[(int) ($manutencao['plugin_vehiclescheduler_vehicles_id'] ?? 0)] ?? '-',
                'tipo'           => $types[(int) ($manutencao['type'] ?? 0)] ?? '',
                'data_agendada'  => self::formatDate((string) ($manutencao['scheduled_date'] ?? '')),
                'data_conclusao' => self::formatDate((string) ($manutencao['completion_date'] ?? '')),
                'fornecedor'     => (string) ($manutencao['supplier'] ?? '-'),
                'custo'          => $cost,
                'km'             => (string) ($manutencao['mileage'] ?? '-'),
                'status'         => $statuses[(int) ($manutencao['status'] ?? 0)] ?? '',
                'descricao'      => self::truncate((string) ($manutencao['description'] ?? ''), 100),
            ];
        }

        return [
            'periodo'     => self::formatPeriod($dateStart, $dateEnd),
            'total'       => count($data),
            'custo_total' => $totalCusto,
            'data'        => $data,
        ];
    }

    public static function getIncidentesData($date_start = null, $date_end = null): array
    {
        global $DB;

        [$dateStart, $dateEnd] = self::normalizePeriod($date_start, $date_end);
        $datetimeStart = $dateStart . ' 00:00:00';
        $datetimeEnd   = $dateEnd . ' 23:59:59';

        $incidentes = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_incidents',
            'WHERE' => [
                'AND' => [
                    ['incident_date' => ['>=', $datetimeStart]],
                    ['incident_date' => ['<=', $datetimeEnd]],
                ],
            ],
            'ORDER' => ['incident_date DESC'],
        ]));

        $vehicleMap = self::getLabelMap(
            'glpi_plugin_vehiclescheduler_vehicles',
            self::collectIds($incidentes, 'plugin_vehiclescheduler_vehicles_id'),
            static function (array $row): string {
                return trim((string) ($row['name'] ?? '') . ' (' . (string) ($row['plate'] ?? '') . ')');
            }
        );
        $claimsByIncident = self::groupFirstByField(
            'glpi_plugin_vehiclescheduler_insuranceclaims',
            'plugin_vehiclescheduler_incidents_id',
            self::collectIds($incidentes, 'id')
        );
        $types    = PluginVehicleschedulerIncident::getAllTypes();
        $statuses = PluginVehicleschedulerIncident::getAllStatus();

        $data = [];
        foreach ($incidentes as $incidente) {
            $incidentId = (int) ($incidente['id'] ?? 0);
            $claim      = $claimsByIncident[$incidentId] ?? null;

            $data[] = [
                'data'           => self::formatDateTime((string) ($incidente['incident_date'] ?? '')),
                'veiculo'        => $vehicleMap[(int) ($incidente['plugin_vehiclescheduler_vehicles_id'] ?? 0)] ?? '-',
                'tipo'           => $types[(int) ($incidente['incident_type'] ?? 0)] ?? '',
                'local'          => (string) ($incidente['location'] ?? '-'),
                'status'         => $statuses[(int) ($incidente['status'] ?? 0)] ?? '',
                'tem_sinistro'   => $claim !== null ? 'Sim' : 'Não',
                'valor_sinistro' => $claim !== null
                    ? 'R$ ' . number_format((float) ($claim['approved_value'] ?? 0), 2, ',', '.')
                    : '-',
                'descricao'      => self::truncate((string) ($incidente['description'] ?? ''), 100),
            ];
        }

        return [
            'periodo' => self::formatPeriod($dateStart, $dateEnd),
            'total'   => count($data),
            'data'    => $data,
        ];
    }

    public static function getUtilizacaoData(): array
    {
        global $DB;

        $veiculos = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_vehicles',
            'WHERE' => ['is_active' => 1],
        ]));

        $vehicleIds          = self::collectIds($veiculos, 'id');
        $scheduleCountMap    = self::countByField('glpi_plugin_vehiclescheduler_schedules', 'plugin_vehiclescheduler_vehicles_id', $vehicleIds);
        $maintenanceCountMap = self::countByField('glpi_plugin_vehiclescheduler_maintenances', 'plugin_vehiclescheduler_vehicles_id', $vehicleIds);
        $incidentCountMap    = self::countByField('glpi_plugin_vehiclescheduler_incidents', 'plugin_vehiclescheduler_vehicles_id', $vehicleIds);

        $data = [];
        foreach ($veiculos as $veiculo) {
            $vehicleId    = (int) ($veiculo['id'] ?? 0);
            $reservas     = $scheduleCountMap[$vehicleId] ?? 0;
            $manutencoes  = $maintenanceCountMap[$vehicleId] ?? 0;
            $incidentes   = $incidentCountMap[$vehicleId] ?? 0;

            $data[] = [
                'veiculo'      => trim((string) ($veiculo['name'] ?? '') . ' (' . (string) ($veiculo['plate'] ?? '') . ')'),
                'marca_modelo' => trim((string) ($veiculo['brand'] ?? '') . ' ' . (string) ($veiculo['model'] ?? '')),
                'ano'          => (string) ($veiculo['year'] ?? '-'),
                'assentos'     => (string) ($veiculo['seats'] ?? '-'),
                'reservas'     => $reservas,
                'manutencoes'  => $manutencoes,
                'incidentes'   => $incidentes,
                'utilizacao'   => $reservas > 0 ? 'Alta' : 'Baixa',
            ];
        }

        return [
            'total_veiculos' => count($data),
            'data'           => $data,
        ];
    }

    public static function getMotoristasData(): array
    {
        global $DB;

        $drivers = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_drivers',
            'WHERE' => [
                'is_active'   => 1,
                'is_approved' => 1,
            ],
        ]));

        $fineCountMap = self::countByField(
            'glpi_plugin_vehiclescheduler_driverfines',
            'plugin_vehiclescheduler_drivers_id',
            self::collectIds($drivers, 'id')
        );

        $data = [];
        foreach ($drivers as $driver) {
            $expiry = (string) ($driver['cnh_expiry'] ?? '');
            $days   = self::daysUntil($expiry);
            $status = PluginVehicleschedulerDriver::getCNHExpiryStatus($expiry);

            $situacaoCnh = match ($status['status'] ?? 'unknown') {
                'expired'  => 'Vencida',
                'critical' => 'Crítica',
                'warning'  => 'Atenção',
                'ok'       => 'Válida',
                default    => 'Sem data',
            };

            $driverId = (int) ($driver['id'] ?? 0);

            $data[] = [
                'nome'             => getUserName((int) ($driver['users_id'] ?? 0)),
                'cnh_categoria'    => (string) ($driver['cnh_category'] ?? '-'),
                'cnh_vencimento'   => self::formatDate($expiry),
                'situacao_cnh'     => $situacaoCnh,
                'dias_vencimento'  => $days > 0 ? $days : 0,
                'telefone'         => (string) ($driver['contact_phone'] ?? '-'),
                'multas'           => $fineCountMap[$driverId] ?? 0,
                'ativo'            => (int) ($driver['is_active'] ?? 0) === 1 ? 'Sim' : 'Não',
            ];
        }

        return [
            'total' => count($data),
            'data'  => $data,
        ];
    }

    public static function getFinanceiroData($mes = null, $ano = null): array
    {
        $month = self::normalizeMonth($mes);
        $year  = self::normalizeYear($ano);

        $dateStart = sprintf('%04d-%02d-01', $year, $month);
        $dateEnd   = date('Y-m-t', strtotime($dateStart));

        $manutencoes = self::getManutencoesData($dateStart, $dateEnd);
        $incidentes  = self::getIncidentesData($dateStart, $dateEnd);

        global $DB;

        $multas = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_driverfines',
            'WHERE' => [
                'AND' => [
                    ['fine_date' => ['>=', $dateStart]],
                    ['fine_date' => ['<=', $dateEnd]],
                ],
            ],
        ]));

        $sinistros = iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_insuranceclaims',
            'WHERE' => [
                'AND' => [
                    ['opening_date' => ['>=', $dateStart]],
                    ['opening_date' => ['<=', $dateEnd]],
                ],
            ],
        ]));

        $custoManutencao = (float) ($manutencoes['custo_total'] ?? 0);
        $valorMultas     = count($multas) * 195.23;
        $valorSinistros  = 0.0;

        foreach ($sinistros as $sinistro) {
            $valorSinistros += (float) ($sinistro['approved_value'] ?? 0);
        }

        return [
            'periodo'          => sprintf('Mês %02d/%04d', $month, $year),
            'custo_manutencao' => $custoManutencao,
            'valor_multas'     => $valorMultas,
            'qtd_multas'       => count($multas),
            'valor_sinistros'  => $valorSinistros,
            'total_geral'      => $custoManutencao + $valorMultas + $valorSinistros,
        ];
    }

    private static function normalizePeriod(?string $dateStart, ?string $dateEnd): array
    {
        $start = self::normalizeDate($dateStart) ?? date('Y-m-01');
        $end   = self::normalizeDate($dateEnd) ?? date('Y-m-t');

        if ($start > $end) {
            return [$end, $start];
        }

        return [$start, $end];
    }

    private static function normalizeDate(?string $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = DateTime::createFromFormat('Y-m-d', trim($value));
        if (!$date instanceof DateTime) {
            return null;
        }

        return $date->format('Y-m-d');
    }

    private static function normalizeMonth($month): int
    {
        $value = (int) $month;
        return $value >= 1 && $value <= 12 ? $value : (int) date('m');
    }

    private static function normalizeYear($year): int
    {
        $value = (int) $year;
        return $value >= 2000 && $value <= 2100 ? $value : (int) date('Y');
    }

    private static function formatPeriod(string $dateStart, string $dateEnd): string
    {
        return self::formatDate($dateStart) . ' a ' . self::formatDate($dateEnd);
    }

    private static function formatDate(string $value): string
    {
        if ($value === '' || $value === '0000-00-00') {
            return '-';
        }

        return date('d/m/Y', strtotime($value));
    }

    private static function formatDateTime(string $value): string
    {
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return '-';
        }

        return date('d/m/Y H:i', strtotime($value));
    }

    private static function truncate(string $value, int $maxLen): string
    {
        $value = trim($value);

        if ($maxLen <= 0 || mb_strlen($value) <= $maxLen) {
            return $value;
        }

        return mb_substr($value, 0, $maxLen) . '...';
    }

    private static function collectIds(array $rows, string $field): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $id = (int) ($row[$field] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private static function getRowMap(string $table, array $ids): array
    {
        global $DB;

        if ($ids === []) {
            return [];
        }

        $map = [];
        foreach ($DB->request([
            'FROM'  => $table,
            'WHERE' => ['id' => $ids],
        ]) as $row) {
            $map[(int) $row['id']] = $row;
        }

        return $map;
    }

    private static function getLabelMap(string $table, array $ids, callable $builder): array
    {
        $rows = self::getRowMap($table, $ids);
        $map  = [];

        foreach ($rows as $id => $row) {
            $map[$id] = $builder($row);
        }

        return $map;
    }

    private static function countByField(string $table, string $field, array $ids): array
    {
        global $DB;

        if ($ids === []) {
            return [];
        }

        $counts = array_fill_keys($ids, 0);
        foreach ($DB->request([
            'SELECT' => ['id', $field],
            'FROM'   => $table,
            'WHERE'  => [$field => $ids],
        ]) as $row) {
            $id = (int) ($row[$field] ?? 0);
            if ($id > 0) {
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        }

        return $counts;
    }

    private static function groupFirstByField(string $table, string $field, array $ids): array
    {
        global $DB;

        if ($ids === []) {
            return [];
        }

        $map = [];
        foreach ($DB->request([
            'FROM'  => $table,
            'WHERE' => [$field => $ids],
            'ORDER' => ['id ASC'],
        ]) as $row) {
            $groupId = (int) ($row[$field] ?? 0);
            if ($groupId > 0 && !isset($map[$groupId])) {
                $map[$groupId] = $row;
            }
        }

        return $map;
    }

    private static function daysUntil(string $date): int
    {
        if ($date === '' || $date === '0000-00-00') {
            return 0;
        }

        $target = strtotime($date);
        if ($target === false) {
            return 0;
        }

        return (int) floor(($target - time()) / 86400);
    }
}
