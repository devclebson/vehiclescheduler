<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);
plugin_vehiclescheduler_redirect_future_plan('INCIDENTES', 'EM OBRAS !!!');
exit;

/**
 * Excel-compatible report export.
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
    'reservas'     => 'Reservas por Período',
    'manutencoes'  => 'Manutenções e Custos',
    'incidentes'   => 'Incidentes e Sinistros',
    'utilizacao'   => 'Utilização de Frota',
    'motoristas'   => 'Motoristas e CNH',
    'financeiro'   => 'Consolidado Financeiro',
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
$filename = 'relatorio_' . $report . '_' . date('Ymd_His') . '.xls';
$total = isset($reportData['total']) ? (int) $reportData['total'] : count($reportData['data'] ?? []);

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
echo '<x:Name>' . htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') . '</x:Name>';
echo '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
echo '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
echo '<style>';
echo 'table{border-collapse:collapse;width:100%;}';
echo 'th{background-color:#3B82F6;color:white;font-weight:bold;padding:10px;text-align:left;border:1px solid #1E293B;}';
echo 'td{padding:8px;border:1px solid #E2E8F0;}';
echo 'tr:nth-child(even){background-color:#F8FAFC;}';
echo '.title{font-size:16pt;font-weight:bold;text-align:center;margin:15px 0;background-color:#E0E7FF;padding:10px;}';
echo '.info{font-size:10pt;text-align:center;color:#64748B;margin-bottom:15px;}';
echo '.totals{background-color:#DBEAFE;padding:10px;margin:10px 0;font-weight:bold;}';
echo '.total-row{background-color:#3B82F6;color:white;font-weight:bold;}';
echo '</style></head><body>';

echo '<div class="title">' . htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8') . '</div>';
echo '<div class="info">';
if (isset($reportData['periodo'])) {
    echo 'Período: ' . htmlspecialchars((string) $reportData['periodo'], ENT_QUOTES, 'UTF-8') . ' | ';
}
echo 'Gerado em: ' . date('d/m/Y H:i:s') . ' | Total: ' . $total . ' registro(s)';
echo '</div>';

if ($report === 'financeiro') {
    echo '<div class="totals"><table style="width:60%;margin:10px auto;">';
    echo '<tr><th>Categoria</th><th style="text-align:right;">Valor (R$)</th></tr>';
    echo '<tr><td>Manutenções</td><td style="text-align:right;">' . number_format((float) $reportData['custo_manutencao'], 2, ',', '.') . '</td></tr>';
    echo '<tr><td>Multas (' . (int) $reportData['qtd_multas'] . ' registro(s))</td><td style="text-align:right;">' . number_format((float) $reportData['valor_multas'], 2, ',', '.') . '</td></tr>';
    echo '<tr><td>Sinistros</td><td style="text-align:right;">' . number_format((float) $reportData['valor_sinistros'], 2, ',', '.') . '</td></tr>';
    echo '<tr class="total-row"><td>TOTAL GERAL</td><td style="text-align:right;">' . number_format((float) $reportData['total_geral'], 2, ',', '.') . '</td></tr>';
    echo '</table></div>';
}

if (isset($reportData['custo_total'])) {
    echo '<div class="totals"><strong>Custo Total: R$ ' . number_format((float) $reportData['custo_total'], 2, ',', '.') . '</strong></div>';
}

$rows = $reportData['data'] ?? [];
if ($rows !== []) {
    $firstRow = reset($rows);
    echo '<table><thead><tr>';
    foreach (array_keys($firstRow) as $header) {
        echo '<th>' . htmlspecialchars(mb_strtoupper(str_replace('_', ' ', $header)), ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($row as $key => $value) {
            $style = '';
            if (is_numeric($value) && (str_contains($key, 'custo') || str_contains($key, 'valor'))) {
                $value = 'R$ ' . number_format((float) $value, 2, ',', '.');
                $style = ' style="text-align:right;"';
            }

            echo '<td' . $style . '>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
} else {
    echo '<div style="text-align:center;padding:40px;color:#94A3B8;font-style:italic;">Nenhum dado encontrado para o período selecionado.</div>';
}

echo '<div style="margin-top:30px;text-align:center;font-size:9pt;color:#94A3B8;">GLPI - Sistema de Gestão de Frota | Gerado automaticamente</div>';
echo '</body></html>';
