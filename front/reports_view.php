<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

/**
 * Report screen view.
 */

include_once(__DIR__ . '/../inc/common.inc.php');

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

if (!class_exists('PluginVehicleschedulerReportsData')) {
    require_once __DIR__ . '/../inc/reports_data.php';
}

function vs_reports_view_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$report = PluginVehicleschedulerInput::enum(
    $_GET,
    'report',
    ['reservas', 'manutencoes', 'incidentes', 'utilizacao', 'motoristas', 'financeiro'],
    'reservas'
);
$dateStart = PluginVehicleschedulerInput::date($_GET, 'date_start', date('Y-m-01'));
$dateEnd   = PluginVehicleschedulerInput::date($_GET, 'date_end', date('Y-m-t'));
$month     = PluginVehicleschedulerInput::int($_GET, 'mes', (int) date('m'), 1, 12);
$year      = PluginVehicleschedulerInput::int($_GET, 'ano', (int) date('Y'), 2000, 2100);

$reportMap = [
    'reservas' => ['title' => 'Reservas por Período', 'icon' => 'ti-calendar-stats'],
    'manutencoes' => ['title' => 'Manutenções e Custos', 'icon' => 'ti-tool'],
    'incidentes' => ['title' => 'Incidentes e Sinistros', 'icon' => 'ti-alert-triangle'],
    'utilizacao' => ['title' => 'Utilização de Frota', 'icon' => 'ti-car'],
    'motoristas' => ['title' => 'Motoristas e CNH', 'icon' => 'ti-id-badge'],
    'financeiro' => ['title' => 'Consolidado Financeiro', 'icon' => 'ti-report-money'],
];

$reportData = match ($report) {
    'reservas' => PluginVehicleschedulerReportsData::getReservasData($dateStart, $dateEnd),
    'manutencoes' => PluginVehicleschedulerReportsData::getManutencoesData($dateStart, $dateEnd),
    'incidentes' => PluginVehicleschedulerReportsData::getIncidentesData($dateStart, $dateEnd),
    'utilizacao' => PluginVehicleschedulerReportsData::getUtilizacaoData(),
    'motoristas' => PluginVehicleschedulerReportsData::getMotoristasData(),
    'financeiro' => PluginVehicleschedulerReportsData::getFinanceiroData($month, $year),
    default => PluginVehicleschedulerReportsData::getReservasData($dateStart, $dateEnd),
};

$reportTitle = $reportMap[$report]['title'];
$reportIcon  = $reportMap[$report]['icon'];
$totalRows   = isset($reportData['total']) ? (int) $reportData['total'] : count($reportData['data'] ?? []);
$rows        = $reportData['data'] ?? [];

Html::header($reportTitle, $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'reports');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
?>
<div class="vs-report-view-page">
    <section class="vs-report-view-hero">
        <div class="vs-report-view-hero__icon">
            <i class="ti <?= vs_reports_view_escape($reportIcon) ?>"></i>
        </div>
        <div class="vs-report-view-hero__content">
            <h1><?= vs_reports_view_escape($reportTitle) ?></h1>
            <div class="vs-report-view-meta">
                <span><i class="ti ti-calendar"></i><?= vs_reports_view_escape((string) ($reportData['periodo'] ?? 'Geral')) ?></span>
                <span><i class="ti ti-clock"></i>Gerado em <?= date('d/m/Y H:i') ?></span>
                <span><i class="ti ti-database"></i><?= $totalRows ?> registros</span>
            </div>
        </div>
    </section>

    <?php if (in_array($report, ['reservas', 'manutencoes', 'incidentes'], true)) : ?>
        <section class="vs-report-view-filters">
            <form method="get" class="vs-report-view-filters__form">
                <input type="hidden" name="report" value="<?= vs_reports_view_escape($report) ?>">
                <div class="vs-report-view-field">
                    <label for="date_start">Data início</label>
                    <input id="date_start" type="date" name="date_start" value="<?= vs_reports_view_escape((string) $dateStart) ?>">
                </div>
                <div class="vs-report-view-field">
                    <label for="date_end">Data fim</label>
                    <input id="date_end" type="date" name="date_end" value="<?= vs_reports_view_escape((string) $dateEnd) ?>">
                </div>
                <button type="submit" class="vs-report-view-btn vs-report-view-btn--primary">Filtrar</button>
            </form>
        </section>
    <?php elseif ($report === 'financeiro') : ?>
        <section class="vs-report-view-filters">
            <form method="get" class="vs-report-view-filters__form">
                <input type="hidden" name="report" value="<?= vs_reports_view_escape($report) ?>">
                <div class="vs-report-view-field">
                    <label for="mes">Mês</label>
                    <input id="mes" type="number" name="mes" min="1" max="12" value="<?= $month ?>">
                </div>
                <div class="vs-report-view-field">
                    <label for="ano">Ano</label>
                    <input id="ano" type="number" name="ano" min="2000" max="2100" value="<?= $year ?>">
                </div>
                <button type="submit" class="vs-report-view-btn vs-report-view-btn--primary">Atualizar</button>
            </form>
        </section>
    <?php endif; ?>

    <section class="vs-report-view-actions">
        <a href="reports.php" class="vs-report-view-btn">Voltar</a>
        <a
            href="reports_pdf.php?report=<?= urlencode($report) ?>&date_start=<?= urlencode((string) $dateStart) ?>&date_end=<?= urlencode((string) $dateEnd) ?>&mes=<?= $month ?>&ano=<?= $year ?>"
            class="vs-report-view-btn">
            Exportar PDF
        </a>
        <a
            href="reports_xlsx.php?report=<?= urlencode($report) ?>&date_start=<?= urlencode((string) $dateStart) ?>&date_end=<?= urlencode((string) $dateEnd) ?>&mes=<?= $month ?>&ano=<?= $year ?>"
            class="vs-report-view-btn">
            Exportar Excel
        </a>
        <button type="button" class="vs-report-view-btn" data-report-print>Imprimir</button>
    </section>

    <?php if (isset($reportData['custo_total'])) : ?>
        <section class="vs-report-view-totals">
            <article class="vs-report-view-total-card">
                <strong>R$ <?= number_format((float) $reportData['custo_total'], 2, ',', '.') ?></strong>
                <span>Custo total</span>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($report === 'financeiro') : ?>
        <section class="vs-report-view-totals">
            <article class="vs-report-view-total-card">
                <strong>R$ <?= number_format((float) $reportData['custo_manutencao'], 2, ',', '.') ?></strong>
                <span>Manutenções</span>
            </article>
            <article class="vs-report-view-total-card">
                <strong>R$ <?= number_format((float) $reportData['valor_multas'], 2, ',', '.') ?></strong>
                <span>Multas (<?= (int) $reportData['qtd_multas'] ?>)</span>
            </article>
            <article class="vs-report-view-total-card">
                <strong>R$ <?= number_format((float) $reportData['valor_sinistros'], 2, ',', '.') ?></strong>
                <span>Sinistros</span>
            </article>
            <article class="vs-report-view-total-card">
                <strong>R$ <?= number_format((float) $reportData['total_geral'], 2, ',', '.') ?></strong>
                <span>Total geral</span>
            </article>
        </section>
    <?php endif; ?>

    <section class="vs-report-view-table-card">
        <?php if ($rows === []) : ?>
            <div class="vs-report-view-empty">
                <div class="vs-report-view-empty__icon">🗂</div>
                <p>Nenhum dado encontrado para o recorte selecionado.</p>
            </div>
        <?php else : ?>
            <?php $firstRow = reset($rows); ?>
            <div class="vs-report-view-table-wrap">
                <table class="vs-report-view-table">
                    <thead>
                        <tr>
                            <?php foreach (array_keys($firstRow) as $key) : ?>
                                <th><?= vs_reports_view_escape(ucfirst(str_replace('_', ' ', $key))) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row) : ?>
                            <tr>
                                <?php foreach ($row as $key => $value) : ?>
                                    <td>
                                        <?php
                                        $stringValue = (string) $value;
                                        $isMoneyField = is_numeric($value) && (str_contains($key, 'custo') || str_contains($key, 'valor'));
                                        echo $isMoneyField
                                            ? 'R$ ' . number_format((float) $value, 2, ',', '.')
                                            : vs_reports_view_escape($stringValue);
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<script src="../public/js/reports-view.js"></script>
<?php Html::footer(); ?>