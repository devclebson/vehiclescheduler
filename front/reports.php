<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

/**
 * Management reports landing page.
 */

include_once(__DIR__ . '/../inc/common.inc.php');

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

$report = PluginVehicleschedulerInput::enum(
    $_GET,
    'report',
    ['reservas', 'manutencoes', 'incidentes', 'utilizacao', 'motoristas', 'financeiro'],
    ''
);
$export = PluginVehicleschedulerInput::enum($_GET, 'export', ['pdf', 'xlsx'], '');

if ($report !== '' && $export !== '') {
    Html::redirect('reports_' . $export . '.php?report=' . urlencode($report));
}

Html::header('Relatórios Gerenciais', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'reports');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

$reports = [
    [
        'code'        => 'reservas',
        'icon'        => 'ti ti-calendar-stats',
        'title'       => 'Reservas por período',
        'description' => 'Análise completa de reservas com solicitante, veículo, motorista, período de uso e status de aprovação.',
        'meta'        => ['Reservas + Veículos + Motoristas', 'Gráfico de aprovações'],
    ],
    [
        'code'        => 'manutencoes',
        'icon'        => 'ti ti-tool',
        'title'       => 'Manutenções e custos',
        'description' => 'Histórico de manutenções preventivas e corretivas com custos, fornecedores e leitura de gasto por veículo.',
        'meta'        => ['Manutenções + Custos', 'Preventiva vs corretiva'],
    ],
    [
        'code'        => 'incidentes',
        'icon'        => 'ti ti-alert-triangle',
        'title'       => 'Incidentes e sinistros',
        'description' => 'Registro de incidentes, acidentes e sinistros com seguradora, incluindo valores aprovados e cobertura.',
        'meta'        => ['Incidentes + Sinistros', 'Análise de seguros'],
    ],
    [
        'code'        => 'utilizacao',
        'icon'        => 'ti ti-car',
        'title'       => 'Utilização de frota',
        'description' => 'Taxa de uso dos veículos, quilometragem, tempo em serviço e identificação de ociosidade.',
        'meta'        => ['Veículos + Reservas', 'Taxa de utilização'],
    ],
    [
        'code'        => 'motoristas',
        'icon'        => 'ti ti-id-badge',
        'title'       => 'Motoristas e CNH',
        'description' => 'Situação de CNH, vencimentos próximos, multas associadas e histórico de uso dos motoristas.',
        'meta'        => ['Motoristas + Multas', 'Alertas de vencimento'],
    ],
    [
        'code'        => 'financeiro',
        'icon'        => 'ti ti-report-money',
        'title'       => 'Consolidado financeiro',
        'description' => 'Resumo financeiro com manutenção, multas, sinistros e análise mensal de gastos por categoria.',
        'meta'        => ['Todas as fontes', 'Análise mensal'],
    ],
];
?>
<div class="vs-reports-page">
    <a href="management.php" class="vs-reports-back">Voltar ao dashboard</a>

    <section class="vs-reports-hero">
        <div>
            <p class="vs-reports-hero__eyebrow">Visão gerencial</p>
            <h1>Relatórios gerenciais</h1>
            <p>
                Escolha o recorte desejado para visualizar online ou exportar em PDF e Excel sem sair do fluxo de gestão.
            </p>
        </div>
        <div class="vs-reports-hero__note">
            <strong>6 relatórios prontos</strong>
            <span>Com foco em leitura rápida e exportação operacional.</span>
        </div>
    </section>

    <section class="vs-reports-grid">
        <?php foreach ($reports as $reportItem): ?>
            <article class="vs-report-card">
                <header class="vs-report-card__header">
                    <div class="vs-report-card__icon">
                        <i class="<?= htmlspecialchars($reportItem['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    </div>
                    <div>
                        <h2><?= htmlspecialchars($reportItem['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p><?= htmlspecialchars($reportItem['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </header>

                <div class="vs-report-card__meta">
                    <?php foreach ($reportItem['meta'] as $meta): ?>
                        <span><?= htmlspecialchars($meta, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>

                <footer class="vs-report-card__actions">
                    <a href="reports_view.php?report=<?= urlencode($reportItem['code']) ?>" class="vs-report-btn vs-report-btn--primary">
                        Visualizar
                    </a>
                    <a href="?report=<?= urlencode($reportItem['code']) ?>&export=pdf" class="vs-report-btn">
                        PDF
                    </a>
                    <a href="?report=<?= urlencode($reportItem['code']) ?>&export=xlsx" class="vs-report-btn">
                        Excel
                    </a>
                </footer>
            </article>
        <?php endforeach; ?>
    </section>
</div>
<?php Html::footer(); ?>