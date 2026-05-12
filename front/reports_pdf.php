<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;
/**
 * PDF report export.
 */

include_once(__DIR__ . '/../inc/common.inc.php');

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

if (!class_exists('PluginVehicleschedulerReportsData')) {
    require_once __DIR__ . '/../inc/reports_data.php';
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

$titles = [
    'reservas'     => 'Relatório de Reservas por Período',
    'manutencoes'  => 'Relatório de Manutenções e Custos',
    'incidentes'   => 'Relatório de Incidentes e Sinistros',
    'utilizacao'   => 'Relatório de Utilização de Frota',
    'motoristas'   => 'Relatório de Motoristas e CNH',
    'financeiro'   => 'Relatório Consolidado Financeiro',
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

$reportTitle = $titles[$report];
$total = isset($reportData['total']) ? (int) $reportData['total'] : count($reportData['data'] ?? []);
$rows = $reportData['data'] ?? [];

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') . '</title><style>
@page { margin: 2cm; }
body { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.4; color: #0f172a; }
.header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
.header h1 { margin: 0 0 5px; font-size: 18pt; }
.header .info { color: #64748b; font-size: 9pt; }
.totals { background: #e0e7ff; padding: 15px; margin: 15px 0; border-radius: 8px; }
.total-item { display: inline-block; margin-right: 30px; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 9pt; }
th { background: #3b82f6; color: white; padding: 10px 8px; text-align: left; font-weight: bold; text-transform: uppercase; }
td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
tr:nth-child(even) { background: #f8fafc; }
.empty { text-align: center; padding: 40px; color: #94a3b8; font-style: italic; }
.footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; }
</style></head><body>';

$html .= '<div class="header">';
$html .= '<h1>' . htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') . '</h1>';
$html .= '<div class="info">';
if (isset($reportData['periodo'])) {
    $html .= 'Período: ' . htmlspecialchars((string) $reportData['periodo'], ENT_QUOTES, 'UTF-8') . ' | ';
}
$html .= 'Gerado em: ' . date('d/m/Y H:i:s') . ' | Total: ' . $total . ' registro(s)';
$html .= '</div></div>';

if ($report === 'financeiro') {
    $html .= '<div class="totals">';
    $html .= '<div class="total-item">Manutenções: R$ ' . number_format((float) $reportData['custo_manutencao'], 2, ',', '.') . '</div>';
    $html .= '<div class="total-item">Multas (' . (int) $reportData['qtd_multas'] . '): R$ ' . number_format((float) $reportData['valor_multas'], 2, ',', '.') . '</div>';
    $html .= '<div class="total-item">Sinistros: R$ ' . number_format((float) $reportData['valor_sinistros'], 2, ',', '.') . '</div>';
    $html .= '<div class="total-item">TOTAL: R$ ' . number_format((float) $reportData['total_geral'], 2, ',', '.') . '</div>';
    $html .= '</div>';
}

if (isset($reportData['custo_total'])) {
    $html .= '<div class="totals"><div class="total-item">Custo Total: R$ ' . number_format((float) $reportData['custo_total'], 2, ',', '.') . '</div></div>';
}

if ($rows !== []) {
    $firstRow = reset($rows);
    $html .= '<table><thead><tr>';
    foreach (array_keys($firstRow) as $header) {
        $html .= '<th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $header)), ENT_QUOTES, 'UTF-8') . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $key => $value) {
            $formatted = (is_numeric($value) && (str_contains($key, 'custo') || str_contains($key, 'valor')))
                ? 'R$ ' . number_format((float) $value, 2, ',', '.')
                : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
            $html .= '<td>' . $formatted . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
} else {
    $html .= '<div class="empty">Nenhum dado encontrado para o período selecionado.</div>';
}

$html .= '<div class="footer">GLPI - Sistema de Gestão de Frota | Gerado automaticamente</div>';
$html .= '</body></html>';

if (class_exists('Dompdf\Dompdf')) {
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $filename = 'relatorio_' . $report . '_' . date('Ymd_His') . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
echo $html;
echo '<script src="' . htmlspecialchars(plugin_vehiclescheduler_get_public_asset_url('js/report-print.js'), ENT_QUOTES, 'UTF-8') . '"></script>';
