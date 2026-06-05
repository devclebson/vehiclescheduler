<?php
/**
 * Calendário de Eventos - Gestão de Frota
 * Mostra: Reservas, Manutenções, Incidentes
 */
include('../../../inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', UPDATE);

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

global $DB;
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

$month_start = sprintf('%04d-%02d-01', $year, $month);
$month_end   = date('Y-m-t', strtotime($month_start));

// Buscar eventos do mês
$reservas = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => [
        'OR' => [
            ['begin_date' => ['>=', $month_start], 'begin_date' => ['<=', $month_end . ' 23:59:59']],
            ['end_date'   => ['>=', $month_start], 'end_date'   => ['<=', $month_end . ' 23:59:59']],
        ],
    ],
]));

$manutencoes = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
    'WHERE' => ['scheduled_date' => ['>=', $month_start], 'scheduled_date' => ['<=', $month_end]],
]));

$incidentes = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_incidents',
    'WHERE' => [
        'incident_date' => ['>=', $month_start . ' 00:00:00'],
        'incident_date' => ['<=', $month_end . ' 23:59:59'],
    ],
]));

// Montar estrutura de eventos por dia
$eventos = [];
foreach ($reservas as $r) {
    $start = substr($r['begin_date'], 0, 10);
    $end   = substr($r['end_date'], 0, 10);
    $current = $start;
    while ($current <= $end && $current <= $month_end) {
        if ($current >= $month_start) {
            if (!isset($eventos[$current])) $eventos[$current] = [];
            $eventos[$current][] = [
                'tipo'  => 'reserva',
                'status'=> $r['status'],
                'titulo'=> 'Reserva',
                'id'    => $r['id'],
                'link'  => 'schedule.form.php?id=' . $r['id'],
            ];
        }
        $current = date('Y-m-d', strtotime($current . ' +1 day'));
    }
}

foreach ($manutencoes as $m) {
    $data = $m['scheduled_date'];
    if (!isset($eventos[$data])) $eventos[$data] = [];
    $eventos[$data][] = [
        'tipo'  => 'manutencao',
        'status'=> $m['status'],
        'titulo'=> 'Manutenção',
        'id'    => $m['id'],
        'link'  => 'maintenance.form.php?id=' . $m['id'],
    ];
}

foreach ($incidentes as $i) {
    $data = substr($i['incident_date'], 0, 10);
    if (!isset($eventos[$data])) $eventos[$data] = [];
    $eventos[$data][] = [
        'tipo'  => 'incidente',
        'status'=> $i['status'],
        'titulo'=> 'Incidente',
        'id'    => $i['id'],
        'link'  => 'incident.form.php?id=' . $i['id'],
    ];
}

Html::header('Calendário de Eventos', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'calendar');

// Navegação de mês
$prev_m = $month - 1; $prev_y = $year;
if ($prev_m < 1) { $prev_m = 12; $prev_y--; }
$next_m = $month + 1; $next_y = $year;
if ($next_m > 12) { $next_m = 1; $next_y++; }

$dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
?>
<style>
.cal{max-width:1600px;margin:0 auto;padding:0 12px;font-family:inherit;}
.cal-header{background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;border-radius:14px;padding:24px 32px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;}
.cal-header h1{margin:0;font-size:1.8rem;font-weight:700;}
.cal-nav{display:flex;gap:10px;}
.cal-nav button{background:#fff;color:#1e40af;border:none;padding:8px 16px;border-radius:8px;font-weight:600;cursor:pointer;}
.cal-nav button:hover{background:#f1f5f9;}
.cal-legend{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;gap:24px;align-items:center;flex-wrap:wrap;}
.cal-legend-item{display:flex;align-items:center;gap:8px;font-size:.85rem;}
.cal-legend-color{width:14px;height:14px;border-radius:3px;}
.cal-table{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;width:100%;border-collapse:collapse;}
.cal-table th{background:#f8fafc;padding:12px 8px;text-align:center;color:#64748b;font-weight:600;font-size:.8rem;text-transform:uppercase;border:1px solid #e2e8f0;}
.cal-table td{padding:8px;border:1px solid #e2e8f0;vertical-align:top;min-height:100px;height:120px;position:relative;background:#fff;}
.cal-table td.hoje{background:#fef3c7;}
.cal-table td.outro-mes{background:#f8fafc;color:#cbd5e1;}
.cal-day-number{font-weight:700;font-size:.9rem;color:#1e293b;margin-bottom:4px;}
.cal-event{padding:3px 6px;margin:2px 0;font-size:.7rem;border-radius:4px;cursor:pointer;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.cal-event:hover{opacity:.8;}
.evt-reserva{background:#dbeafe;color:#1e40af;border-left:3px solid #3b82f6;}
.evt-reserva.st-2{background:#dcfce7;color:#166534;border-left-color:#22c55e;}
.evt-manutencao{background:#fef3c7;color:#92400e;border-left:3px solid #f59e0b;}
.evt-incidente{background:#fee2e2;color:#991b1b;border-left:3px solid #ef4444;}
</style>

<div class="cal">

<!-- Header -->
<div class="cal-header">
  <h1>📅 <?= $meses[$month] ?> de <?= $year ?></h1>
  <div class="cal-nav">
    <button onclick="location.href='?month=<?= $prev_m ?>&year=<?= $prev_y ?>'">← Anterior</button>
    <button onclick="location.href='?month=<?= date('n') ?>&year=<?= date('Y') ?>'">Hoje</button>
    <button onclick="location.href='?month=<?= $next_m ?>&year=<?= $next_y ?>'">Próximo →</button>
  </div>
</div>

<!-- Legenda -->
<div class="cal-legend">
  <strong style="color:#1e293b;">Legenda:</strong>
  <div class="cal-legend-item"><div class="cal-legend-color" style="background:#3b82f6;"></div> Reservas</div>
  <div class="cal-legend-item"><div class="cal-legend-color" style="background:#f59e0b;"></div> Manutenções</div>
  <div class="cal-legend-item"><div class="cal-legend-color" style="background:#ef4444;"></div> Incidentes</div>
  <div style="flex:1;text-align:right;color:#64748b;font-size:.85rem;">
    <strong><?= count($reservas) ?></strong> reservas • 
    <strong><?= count($manutencoes) ?></strong> manutenções • 
    <strong><?= count($incidentes) ?></strong> incidentes
  </div>
</div>

<!-- Calendário -->
<table class="cal-table">
  <tr>
    <?php foreach ($dias_semana as $d): ?>
      <th><?= $d ?></th>
    <?php endforeach; ?>
  </tr>
  <?php
  $primeiro_dia = (int)date('w', strtotime($month_start));
  $dias_no_mes  = (int)date('t', strtotime($month_start));
  $hoje = date('Y-m-d');
  
  $dia = 1;
  for ($semana = 0; $semana < 6; $semana++):
    if ($dia > $dias_no_mes) break;
    echo "<tr>";
    for ($dia_semana = 0; $dia_semana < 7; $dia_semana++):
      if ($semana === 0 && $dia_semana < $primeiro_dia):
        echo "<td class='outro-mes'></td>";
      elseif ($dia > $dias_no_mes):
        echo "<td class='outro-mes'></td>";
      else:
        $data = sprintf('%04d-%02d-%02d', $year, $month, $dia);
        $class_hoje = ($data === $hoje) ? ' hoje' : '';
        echo "<td class='$class_hoje'>";
        echo "<div class='cal-day-number'>$dia</div>";
        
        // Exibir eventos do dia
        if (isset($eventos[$data])):
          foreach ($eventos[$data] as $evt):
            $evt_class = 'evt-' . $evt['tipo'];
            if ($evt['tipo'] === 'reserva' && $evt['status'] == 2) $evt_class .= ' st-2';
            echo "<a href='{$evt['link']}' class='cal-event $evt_class' title='Ver detalhes'>";
            echo htmlspecialchars($evt['titulo']);
            echo "</a>";
          endforeach;
        endif;
        
        echo "</td>";
        $dia++;
      endif;
    endfor;
    echo "</tr>";
  endfor;
  ?>
</table>

<!-- Atalhos -->
<div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
  <a href="management.php" style="padding:12px 20px;background:#3b82f6;color:#fff;border-radius:8px;font-weight:600;text-decoration:none;font-size:.9rem;"><i class="ti ti-layout-dashboard"></i> Dashboard</a>
  <a href="schedule.php" style="padding:12px 20px;background:#22c55e;color:#fff;border-radius:8px;font-weight:600;text-decoration:none;font-size:.9rem;"><i class="ti ti-calendar-event"></i> Ver Todas Reservas</a>
  <a href="maintenance.php" style="padding:12px 20px;background:#f59e0b;color:#fff;border-radius:8px;font-weight:600;text-decoration:none;font-size:.9rem;"><i class="ti ti-tool"></i> Ver Manutenções</a>
  <a href="incident.php" style="padding:12px 20px;background:#ef4444;color:#fff;border-radius:8px;font-weight:600;text-decoration:none;font-size:.9rem;"><i class="ti ti-alert-triangle"></i> Ver Incidentes</a>
</div>

</div>
<?php Html::footer(); ?>
