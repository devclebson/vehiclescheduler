<?php
include_once __DIR__ . '/../inc/common.inc.php';
include_once __DIR__ . '/../inc/ui-helpers.php';

\Session::checkLoginUser();

global $CFG_GLPI;

$self = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$root_doc = plugin_vehiclescheduler_get_root_doc();

$can_request = \PluginVehicleschedulerProfile::canAccessRequester();
$can_approve = \PluginVehicleschedulerProfile::canApproveReservations();
$can_manage  = \PluginVehicleschedulerProfile::canViewManagement();
$can_edit    = \PluginVehicleschedulerProfile::canEditManagement();

if (!$can_approve && !$can_manage) {
    \Html::displayRightError();
    exit;
}

$status_filter  = filter_input(INPUT_GET, 'status', FILTER_VALIDATE_INT);
$valid_statuses = array_keys(\PluginVehicleschedulerSchedule::getStatusOptions());

if ($status_filter !== null && $status_filter !== false && !in_array($status_filter, $valid_statuses, true)) {
    $status_filter = null;
}

$status_label = $status_filter !== null
    ? \PluginVehicleschedulerSchedule::getStatusLabel($status_filter)
    : 'Todas';

$counts = \PluginVehicleschedulerSchedule::getApprovalQueueCounts();
$rows   = \PluginVehicleschedulerSchedule::getApprovalQueueRows($status_filter);

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$formatCompactDateTime = static function (?string $value): string {
    if (!$value || $value === '0000-00-00 00:00:00') {
        return '—';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '—';
    }

    $months = [
        1 => 'JAN',
        2 => 'FEV',
        3 => 'MAR',
        4 => 'ABR',
        5 => 'MAI',
        6 => 'JUN',
        7 => 'JUL',
        8 => 'AGO',
        9 => 'SET',
        10 => 'OUT',
        11 => 'NOV',
        12 => 'DEZ',
    ];

    $weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];

    $day     = date('d', $timestamp);
    $month   = $months[(int) date('n', $timestamp)] ?? strtoupper(date('M', $timestamp));
    $weekday = $weekdays[(int) date('w', $timestamp)] ?? date('D', $timestamp);
    $time    = date('H:i', $timestamp);

    return sprintf('%s%s(%s) %s', $day, $month, $weekday, $time);
};

$getInitials = static function (?string $name): string {
    $name = trim((string) $name);
    if ($name === '') {
        return '?';
    }

    $parts    = preg_split('/\s+/u', $name) ?: [];
    $initials = '';

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }

        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        if (mb_strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : '?';
};

$getStatusMeta = static function (int $status): array {
    switch ($status) {
        case \PluginVehicleschedulerSchedule::STATUS_APPROVED:
            return [
                'label' => 'Aprovada',
                'class' => 'vs-status-badge--approved',
                'icon'  => 'ti ti-check',
            ];

        case \PluginVehicleschedulerSchedule::STATUS_REJECTED:
            return [
                'label' => 'Recusada',
                'class' => 'vs-status-badge--rejected',
                'icon'  => 'ti ti-x',
            ];

        case \PluginVehicleschedulerSchedule::STATUS_PENDING:
        default:
            return [
                'label' => 'Pendente',
                'class' => 'vs-status-badge--pending',
                'icon'  => 'ti ti-hourglass',
            ];
    }
};

$filters = [
    [
        'label' => 'Todas',
        'value' => null,
        'icon'  => 'ti ti-list-details',
        'slug'  => 'all',
        'count' => count($rows),
    ],
    [
        'label' => 'Pendentes',
        'value' => \PluginVehicleschedulerSchedule::STATUS_PENDING,
        'icon'  => 'ti ti-hourglass',
        'slug'  => 'pending',
        'count' => (int) ($counts[\PluginVehicleschedulerSchedule::STATUS_PENDING] ?? 0),
    ],
    [
        'label' => 'Aprovadas',
        'value' => \PluginVehicleschedulerSchedule::STATUS_APPROVED,
        'icon'  => 'ti ti-check',
        'slug'  => 'approved',
        'count' => (int) ($counts[\PluginVehicleschedulerSchedule::STATUS_APPROVED] ?? 0),
    ],
    [
        'label' => 'Recusadas',
        'value' => \PluginVehicleschedulerSchedule::STATUS_REJECTED,
        'icon'  => 'ti ti-x',
        'slug'  => 'rejected',
        'count' => (int) ($counts[\PluginVehicleschedulerSchedule::STATUS_REJECTED] ?? 0),
    ],
];

\Html::header(
    'Reservas',
    $self,
    'tools',
    \PluginVehicleschedulerMenu::class,
    'management'
);

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_render_back_to_management();
?>

<div id="vs-schedule-queue-root" class="vs-page vs-page-schedule-queue">
    <div class="vs-page-header">
        <div class="vs-header-content">
            <div class="vs-header-title">
                <div class="vs-header-icon-wrapper">
                    <i class="ti ti-calendar-event vs-header-icon"></i>
                </div>
                <div>
                    <h2>Reservas</h2>
                    <p class="vs-page-subtitle">
                        Fila operacional para análise, atribuição de recursos e aprovação de solicitações.
                    </p>
                </div>
            </div>

            <div class="vs-header-actions">
                <?php if ($can_request || $can_edit): ?>
                    <a href="schedule.form.php" class="vs-btn-add">
                        <i class="ti ti-plus"></i>
                        <span>Solicitar viagem</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="vs-schedule-queue-page">
        <section class="vs-schedule-status-card">
            <div class="vs-schedule-status-card__header">
                <h3><i class="ti ti-filter"></i> Status da fila</h3>
                <span class="vs-schedule-status-card__meta"><?php echo count($rows); ?> visível(eis)</span>
            </div>

            <div class="vs-schedule-status-strip">
                <?php foreach ($filters as $filter):
                    $is_active = $filter['value'] === null
                        ? $status_filter === null
                        : (int) $status_filter === (int) $filter['value'];

                    $url = $filter['value'] === null
                        ? plugin_vehiclescheduler_get_front_url('schedule.php')
                        : plugin_vehiclescheduler_get_front_url('schedule.php') . '?status=' . (int) $filter['value'];
                ?>
                    <a
                        href="<?php echo $h($url); ?>"
                        class="vs-schedule-status-pill vs-schedule-status-pill--<?php echo $h($filter['slug']); ?><?php echo $is_active ? ' is-active' : ''; ?>">
                        <span class="vs-schedule-status-pill__icon">
                            <i class="<?php echo $h($filter['icon']); ?>"></i>
                        </span>
                        <span class="vs-schedule-status-pill__content">
                            <span class="vs-schedule-status-pill__label"><?php echo $h($filter['label']); ?></span>
                            <strong class="vs-schedule-status-pill__count"><?php echo (int) $filter['count']; ?></strong>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="vs-schedule-table-card">
            <div class="vs-schedule-table-card__header">
                <h3><i class="ti ti-layout-list"></i> Solicitações</h3>
                <span class="vs-schedule-table-card__meta"><?php echo count($rows); ?> registro(s)</span>
            </div>

            <?php if ($rows === []) : ?>
                <div class="vs-empty-state">
                    <div class="vs-empty-state__icon">
                        <i class="ti ti-calendar-off"></i>
                    </div>
                    <div class="vs-empty-state__content">
                        <h4>Nenhuma solicitação encontrada</h4>
                        <p>Não há reservas para o filtro selecionado neste momento.</p>
                    </div>
                </div>
            <?php else : ?>
                <div class="vs-schedule-table-wrap">
                    <table class="vs-table vs-schedule-table">
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Destino</th>
                                <th>Status</th>
                                <th>Saída</th>
                                <th>Retorno</th>
                                <th>Solicitante</th>
                                <th>Viatura</th>
                                <th>Motorista</th>
                                <th class="vs-nowrap">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) :
                                $status      = (int) ($row['status'] ?? 0);
                                $status_meta = $getStatusMeta($status);

                                $requester_name = trim((string) ($row['requester_name'] ?? ''));
                                if ($requester_name === '') {
                                    $requester_name = 'Não informado';
                                }

                                $vehicle_name  = trim((string) ($row['vehicle_name'] ?? ''));
                                $vehicle_plate = trim((string) ($row['vehicle_plate'] ?? ''));
                                $vehicle_model = trim((string) ($row['vehicle_model'] ?? ''));

                                $driver_name = trim((string) ($row['driver_name'] ?? ''));

                                $ticket_id    = (int) ($row['tickets_id'] ?? 0);
                                $ticket_label = trim((string) ($row['ticket_ref'] ?? $row['ticket_number'] ?? $row['ticket_name'] ?? ''));
                                if ($ticket_label === '' && $ticket_id > 0) {
                                    $ticket_label = (string) $ticket_id;
                                }

                                $created_at         = $row['date_creation'] ?? null;
                                $created_timestamp  = $created_at ? strtotime((string) $created_at) : false;
                                $reservation_year   = $created_timestamp ? date('Y', $created_timestamp) : date('Y');
                                $reservation_ref    = $reservation_year . '-' . (int) $row['id'];
                                $missing_assignment = $status === \PluginVehicleschedulerSchedule::STATUS_PENDING
                                    && ($vehicle_name === '' || $driver_name === '');

                                $row_classes = [];
                                if ($missing_assignment) {
                                    $row_classes[] = 'vs-schedule-row--attention';
                                }
                            ?>
                                <tr class="<?php echo $h(implode(' ', $row_classes)); ?>">
                                    <td>
                                        <div class="vs-cell-order">
                                            <div class="vs-cell-order__icon">
                                                <i class="ti ti-hash"></i>
                                            </div>
                                            <div class="vs-cell-order__content">
                                                <span class="vs-cell-order__primary">Reserva #<?php echo $h($reservation_ref); ?></span>

                                                <?php if ($ticket_id > 0): ?>
                                                    <a class="vs-cell-order__link" href="<?php echo $h($root_doc . '/front/ticket.form.php?id=' . $ticket_id); ?>">
                                                        Ticket #<?php echo $h($ticket_label); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="vs-cell-order__muted">Sem ticket</span>
                                                <?php endif; ?>

                                                <span class="vs-cell-order__muted"><?php echo $h($formatCompactDateTime($created_at)); ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="vs-cell-destination">
                                            <div class="vs-cell-destination__icon">
                                                <i class="ti ti-map-pin"></i>
                                            </div>
                                            <div class="vs-cell-destination__content">
                                                <a class="vs-cell-primary-link" href="<?php echo $h(plugin_vehiclescheduler_get_front_url('schedule.form.php') . '?id=' . (int) $row['id']); ?>">
                                                    <?php echo $h($row['destination'] ?: 'Sem destino informado'); ?>
                                                </a>
                                                <div class="vs-cell-secondary">
                                                    <span><i class="ti ti-users"></i> <?php echo (int) ($row['passengers'] ?? 0); ?> passageiro(s)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="vs-status-cell">
                                            <span class="vs-status-badge <?php echo $h($status_meta['class']); ?>">
                                                <i class="<?php echo $h($status_meta['icon']); ?>"></i>
                                                <span><?php echo $h($status_meta['label']); ?></span>
                                            </span>

                                            <?php if ($status === \PluginVehicleschedulerSchedule::STATUS_REJECTED && trim((string) ($row['rejection_justification'] ?? '')) !== '') : ?>
                                                <div class="vs-status-note"><?php echo $h($row['rejection_justification']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="vs-nowrap">
                                        <div class="vs-cell-date vs-cell-date--departure">
                                            <i class="ti ti-arrow-up-right"></i>
                                            <span><?php echo $h($formatCompactDateTime($row['begin_date'] ?? null)); ?></span>
                                        </div>
                                    </td>

                                    <td class="vs-nowrap">
                                        <div class="vs-cell-date vs-cell-date--return">
                                            <i class="ti ti-arrow-back-up"></i>
                                            <span><?php echo $h($formatCompactDateTime($row['end_date'] ?? null)); ?></span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="vs-entity-chip">
                                            <span class="vs-entity-chip__label"><?php echo $h($requester_name); ?></span>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($vehicle_name !== '') : ?>
                                            <div class="vs-cell-vehicle">
                                                <div class="vs-cell-vehicle__icon">
                                                    <i class="ti ti-truck"></i>
                                                </div>
                                                <div class="vs-cell-vehicle__content">
                                                    <span class="vs-cell-vehicle__name"><?php echo $h($vehicle_name); ?></span>
                                                    <div class="vs-cell-secondary">
                                                        <?php if ($vehicle_plate !== '') : ?>
                                                            <span><i class="ti ti-license"></i> <?php echo $h($vehicle_plate); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($vehicle_model !== '') : ?>
                                                            <span><i class="ti ti-car"></i> <?php echo $h($vehicle_model); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else : ?>
                                            <span class="vs-missing-chip"><i class="ti ti-alert-triangle"></i> A definir</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($driver_name !== '') : ?>
                                            <div class="vs-entity-chip vs-entity-chip--driver">
                                                <span class="vs-entity-chip__avatar"><i class="ti ti-user"></i></span>
                                                <span class="vs-entity-chip__label"><?php echo $h($driver_name); ?></span>
                                            </div>
                                        <?php else : ?>
                                            <span class="vs-missing-chip"><i class="ti ti-alert-triangle"></i> A definir</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="vs-nowrap">
                                        <div class="vs-inline-actions">
                                            <a class="vs-queue-open-btn" href="<?php echo $h(plugin_vehiclescheduler_get_front_url('schedule.form.php') . '?id=' . (int) $row['id']); ?>">
                                                <i class="ti ti-eye"></i>
                                                <span>Abrir</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php
\Html::footer();
