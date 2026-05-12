<?php

include_once __DIR__ . '/../inc/common.inc.php';

PluginVehicleschedulerDriverfine::requireAdminFines();

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$self = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';
$statusFilter = PluginVehicleschedulerInput::int($_GET, 'status', PluginVehicleschedulerDriverfine::STATUS_OPEN, 0);
$validStatuses = array_keys(PluginVehicleschedulerDriverfine::getAllStatus());

if ($statusFilter > 0 && !in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = PluginVehicleschedulerDriverfine::STATUS_OPEN;
}

if (isset($_POST['quick_fine_action'])) {
    PluginVehicleschedulerDriverfine::requireAdminFines();

    $fineId = PluginVehicleschedulerInput::int($_POST, 'fine_id', 0, 1);
    $action = PluginVehicleschedulerInput::enum($_POST, 'quick_fine_action', ['paid', 'appealed', 'cancel'], '');
    $statusMap = [
        'paid'    => PluginVehicleschedulerDriverfine::STATUS_PAID,
        'appealed' => PluginVehicleschedulerDriverfine::STATUS_APPEALED,
        'cancel'  => PluginVehicleschedulerDriverfine::STATUS_CANCELLED,
    ];

    if ($fineId > 0 && isset($statusMap[$action])) {
        $fine = new PluginVehicleschedulerDriverfine();
        if ($fine->getFromDB($fineId)) {
            $fine->update([
                'id'     => $fineId,
                'status' => $statusMap[$action],
            ]);
            Session::addMessageAfterRedirect('Multa atualizada com sucesso.', false, INFO);
        }
    }

    Html::redirect(plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . $statusFilter);
}

$rows = PluginVehicleschedulerDriverfine::getManagementRows($statusFilter);
$summary = PluginVehicleschedulerDriverfine::buildManagementSummary(
    PluginVehicleschedulerDriverfine::getManagementRows(0)
);
$severities = PluginVehicleschedulerDriverfine::getAllSeverities();
$statuses = PluginVehicleschedulerDriverfine::getAllStatus();
$pointsMap = PluginVehicleschedulerDriverfine::getSeverityPoints();

Html::header('Multas', $self, 'tools', PluginVehicleschedulerMenu::class, 'management');

plugin_vehiclescheduler_load_css([
    'css/pages/fines.css',
]);
plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_render_back_to_management();

$filters = [
    0 => ['label' => 'Todas', 'count' => $summary['total']],
    PluginVehicleschedulerDriverfine::STATUS_OPEN => ['label' => 'Em aberto', 'count' => $summary['open']],
    PluginVehicleschedulerDriverfine::STATUS_APPEALED => ['label' => 'Recurso', 'count' => $summary['appealed']],
    PluginVehicleschedulerDriverfine::STATUS_PAID => ['label' => 'Pagas', 'count' => $summary['paid']],
    PluginVehicleschedulerDriverfine::STATUS_CANCELLED => ['label' => 'Canceladas', 'count' => $summary['cancelled']],
];

?>
<div class="vs-fines-page">
    <div class="vs-page-header">
        <div class="vs-header-content">
            <div class="vs-header-title">
                <div class="vs-header-icon-wrapper">
                    <i class="ti ti-file-alert vs-header-icon"></i>
                </div>
                <div>
                    <h2>Gestão de Multas</h2>
                    <p class="vs-page-subtitle">Controle administrativo de infrações, pontuação e status de tratamento.</p>
                </div>
            </div>

            <a href="<?= $h(plugin_vehiclescheduler_get_front_url('driverfine.form.php')) ?>" class="vs-btn-add">
                <i class="ti ti-plus"></i>
                <span>Nova Multa</span>
            </a>
        </div>
    </div>

    <section class="vs-fines-status-grid" aria-label="Resumo de multas">
        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . PluginVehicleschedulerDriverfine::STATUS_OPEN) ?>" class="vs-fines-status-card">
            <span class="vs-fines-status-card__icon"><i class="ti ti-alert-triangle"></i></span>
            <strong><?= (int) $summary['open'] ?></strong>
            <span>Em aberto</span>
        </a>
        <div class="vs-fines-status-card">
            <span class="vs-fines-status-card__icon"><i class="ti ti-id-badge"></i></span>
            <strong><?= (int) $summary['activePoints'] ?></strong>
            <span>Pontos ativos</span>
        </div>
        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . PluginVehicleschedulerDriverfine::STATUS_APPEALED) ?>" class="vs-fines-status-card">
            <span class="vs-fines-status-card__icon"><i class="ti ti-file-pencil"></i></span>
            <strong><?= (int) $summary['appealed'] ?></strong>
            <span>Em recurso</span>
        </a>
        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . PluginVehicleschedulerDriverfine::STATUS_PAID) ?>" class="vs-fines-status-card">
            <span class="vs-fines-status-card__icon"><i class="ti ti-circle-check"></i></span>
            <strong><?= (int) $summary['paid'] ?></strong>
            <span>Pagas</span>
        </a>
    </section>

    <section class="vs-fines-toolbar">
        <div class="vs-fines-filter-list">
            <?php foreach ($filters as $status => $filter): ?>
                <?php $url = plugin_vehiclescheduler_get_front_url('fines.php') . '?status=' . (int) $status; ?>
                <a href="<?= $h($url) ?>" class="vs-fines-filter<?= (int) $status === (int) $statusFilter ? ' is-active' : '' ?>">
                    <span><?= $h($filter['label']) ?></span>
                    <strong><?= (int) $filter['count'] ?></strong>
                </a>
            <?php endforeach; ?>
        </div>

        <span class="vs-fines-results"><?= count($rows) ?> registro(s)</span>
    </section>

    <section class="vs-fines-table-card">
        <div class="vs-fines-table-card__header">
            <span class="vs-fines-table-card__title"><i class="ti ti-list-details"></i> Multas cadastradas</span>
        </div>

        <?php if ($rows === []): ?>
            <div class="vs-fines-empty">
                <div class="vs-fines-empty__icon"><i class="ti ti-file-alert"></i></div>
                <h3>Nenhuma multa encontrada</h3>
                <p>Não há registros para o filtro selecionado.</p>
            </div>
        <?php else: ?>
            <div class="vs-fines-table-wrap">
                <table class="vs-fines-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Código</th>
                            <th>Motorista</th>
                            <th>Veículo</th>
                            <th>Gravidade</th>
                            <th>Pontos</th>
                            <th>Status</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $fine): ?>
                            <?php
                            $severity = (int) ($fine['severity'] ?? 0);
                            $status = (int) ($fine['status'] ?? 0);
                            $driverId = (int) ($fine['plugin_vehiclescheduler_drivers_id'] ?? 0);
                            $violationCode = trim((string) ($fine['violation_code'] ?? ''));
                            $violationSplit = trim((string) ($fine['violation_split'] ?? ''));
                            $violationDisplay = $violationCode !== ''
                                ? $violationCode . ($violationSplit !== '' ? '-' . $violationSplit : '')
                                : 'Manual';
                            $vehicleName = trim((string) ($fine['vehicle_name'] ?? ''));
                            $vehiclePlate = trim((string) ($fine['vehicle_plate'] ?? ''));
                            $vehicleDisplay = $vehicleName !== ''
                                ? trim($vehicleName . ($vehiclePlate !== '' ? ' / ' . $vehiclePlate : ''))
                                : 'Não vinculado';
                            $description = PluginVehicleschedulerInput::text(
                                ['description' => $fine['description'] ?? ''],
                                'description',
                                160,
                                ''
                            );
                            ?>
                            <tr>
                                <td><?= $h(Html::convDate((string) ($fine['fine_date'] ?? ''))) ?></td>
                                <td><span class="vs-fines-code"><?= $h($violationDisplay) ?></span></td>
                                <td>
                                    <?php if ($driverId > 0): ?>
                                        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('driver.form.php') . '?id=' . $driverId) ?>" class="vs-fines-driver-link">
                                            <?= $h((string) ($fine['driver_name'] ?? 'Não informado')) ?>
                                        </a>
                                    <?php else: ?>
                                        Não informado
                                    <?php endif; ?>
                                </td>
                                <td><?= $h($vehicleDisplay) ?></td>
                                <td>
                                    <span class="vs-driverfine-badge vs-driverfine-badge--<?= $h(PluginVehicleschedulerDriverfine::getSeverityModifier($severity)) ?>">
                                        <?= $h($severities[$severity] ?? 'Não definida') ?>
                                    </span>
                                </td>
                                <td><span class="vs-fines-points"><?= (int) ($pointsMap[$severity] ?? 0) ?></span></td>
                                <td>
                                    <span class="vs-driverfine-badge vs-driverfine-badge--<?= $h(PluginVehicleschedulerDriverfine::getStatusModifier($status)) ?>">
                                        <?= $h($statuses[$status] ?? 'Sem status') ?>
                                    </span>
                                </td>
                                <td><?= $h($description) ?></td>
                                <td>
                                    <div class="vs-fines-actions">
                                        <a href="<?= $h(plugin_vehiclescheduler_get_front_url('driverfine.form.php') . '?id=' . (int) $fine['id']) ?>" class="vs-fines-action">
                                            <i class="ti ti-pencil"></i>
                                            <span>Abrir</span>
                                        </a>
                                        <?php if ($status === PluginVehicleschedulerDriverfine::STATUS_OPEN || $status === PluginVehicleschedulerDriverfine::STATUS_APPEALED): ?>
                                            <form method="post">
                                                <input type="hidden" name="fine_id" value="<?= (int) $fine['id'] ?>">
                                                <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                                                <button type="submit" name="quick_fine_action" value="paid" class="vs-fines-action vs-fines-action--success">
                                                    Pagar
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
<?php
Html::footer();
