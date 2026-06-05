<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Manager Dashboard — full fleet overview
 */
include('../../../inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', READ);

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

Html::header(
    __('Fleet Dashboard', 'vehiclescheduler'),
    $_SERVER['PHP_SELF'],
    'tools',
    'PluginVehicleschedulerMenug',
    'dashboard'
);

// ── Helper: query count ────────────────────────────────────────────────────
function vs_count(string $table, array $where = []): int {
    global $DB;
    $criteria = ['FROM' => $table, 'COUNT' => 'cpt'];
    if ($where) $criteria['WHERE'] = $where;
    $row = $DB->request($criteria)->current();
    return (int)($row['cpt'] ?? 0);
}

// ── KPI data ───────────────────────────────────────────────────────────────
$kpi = [
    'vehicles_active'     => vs_count('glpi_plugin_vehiclescheduler_vehicles', ['is_active' => 1]),
    'vehicles_total'      => vs_count('glpi_plugin_vehiclescheduler_vehicles'),
    'drivers_active'      => vs_count('glpi_plugin_vehiclescheduler_drivers', ['is_active' => 1]),
    'schedules_new'       => vs_count('glpi_plugin_vehiclescheduler_schedules', ['status' => 1]),
    'schedules_approved'  => vs_count('glpi_plugin_vehiclescheduler_schedules', ['status' => 2]),
    'incidents_open'      => vs_count('glpi_plugin_vehiclescheduler_incidents', ['status' => 1]),
    'incidents_analyzing' => vs_count('glpi_plugin_vehiclescheduler_incidents', ['status' => 2]),
    'maint_scheduled'     => vs_count('glpi_plugin_vehiclescheduler_maintenances', ['status' => 1]),
    'maint_in_progress'   => vs_count('glpi_plugin_vehiclescheduler_maintenances', ['status' => 2]),
    'insurance_open'      => vs_count('glpi_plugin_vehiclescheduler_insuranceclaims', ['status' => [1, 2]]),
    'fines_open'          => vs_count('glpi_plugin_vehiclescheduler_driverfines', ['status' => 1]),
];

// CNH expiring in 90 days
global $DB;
$cnh_warning = iterator_to_array($DB->request([
    'FROM'    => 'glpi_plugin_vehiclescheduler_drivers',
    'WHERE'   => ['is_active' => 1, 'cnh_expiry' => ['<=', date('Y-m-d', strtotime('+90 days'))], 'cnh_expiry' => ['>=', date('Y-m-d')]],
    'ORDER'   => ['cnh_expiry ASC'],
    'LIMIT'   => 5,
]));

// Recent schedules pending approval
$pending_schedules = iterator_to_array($DB->request([
    'FROM'    => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE'   => ['status' => 1],
    'ORDER'   => ['date_creation DESC'],
    'LIMIT'   => 8,
]));

// Recent open incidents
$open_incidents = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_incidents',
    'WHERE' => ['status' => [1, 2]],
    'ORDER' => ['incident_date DESC'],
    'LIMIT' => 6,
]));

// Upcoming/overdue maintenances
$upcoming_maint = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
    'WHERE' => ['status' => [1, 2]],
    'ORDER' => ['scheduled_date ASC'],
    'LIMIT' => 6,
]));

// Vehicle name cache
$vcache = [];
function vs_vehicle_name(int $id): string {
    global $DB, $vcache;
    if (!$id) return '—';
    if (!isset($vcache[$id])) {
        $row = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['id' => $id]])->current();
        $vcache[$id] = $row ? $row['name'] . ' <small style="color:#888">(' . $row['plate'] . ')</small>' : "#{$id}";
    }
    return $vcache[$id];
}
?>
<style>
/* NOC Dark Mode Theme */
:fullscreen { background-color: #0f172a; }
:fullscreen #header, :fullscreen #footer, :fullscreen #page-wrapper > header, :fullscreen .glpi-header, :fullscreen .glpi-menu, :fullscreen .breadcrumb, :fullscreen #c_menu { display: none !important; }
:fullscreen .noc-wrapper { padding: 30px; height: 100vh; overflow-y: auto; box-sizing: border-box; }
.noc-wrapper { background: #0f172a; color: #e2e8f0; font-family: inherit; padding: 20px; border-radius: 12px; margin-top: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
.noc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid #1e293b; padding-bottom: 16px; }
.noc-header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; color: #f8fafc; display: flex; align-items: center; gap: 10px; }
.noc-btn { background: #334155; color: #f8fafc; border: 1px solid #475569; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
.noc-btn:hover { background: #475569; }
.noc-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
.noc-kpi { background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 20px; text-align: center; }
.noc-kpi .val { font-size: 2.6rem; font-weight: 800; line-height: 1; margin-bottom: 8px; }
.noc-kpi .lbl { font-size: 0.8rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
.noc-red .val { color: #ef4444; } .noc-amber .val { color: #f59e0b; } .noc-blue .val { color: #3b82f6; } .noc-green .val { color: #10b981; } .noc-purple .val { color: #8b5cf6; }
.noc-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.noc-card { background: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; }
.noc-card-header { padding: 16px 20px; background: #0f172a; border-bottom: 1px solid #334155; font-weight: 700; font-size: 1rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
.noc-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.noc-table th { background: #1e293b; padding: 12px 16px; text-align: left; color: #94a3b8; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #334155; }
.noc-table td { padding: 14px 16px; border-bottom: 1px solid #334155; color: #cbd5e1; }
.noc-table tr:last-child td { border-bottom: none; }
.noc-empty { padding: 40px; text-align: center; color: #475569; font-size: 1rem; font-style: italic; }
.noc-badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
.bg-critical { background: #7f1d1d; color: #fca5a5; } .bg-warning { background: #78350f; color: #fcd34d; } .bg-info { background: #1e3a8a; color: #bfdbfe; }
@media(max-width: 1100px) { .noc-grid-2 { grid-template-columns: 1fr; } }
</style>

<div class="noc-wrapper" id="noc-dashboard">
  <div class="noc-header">
    <h1><i class="ti ti-activity" style="color:#10b981;"></i> NOC Analytics - Monitoramento de Frota</h1>
    <div>
      <span style="color:#94a3b8; font-size:0.85rem; margin-right:15px; font-family:monospace;" id="noc-clock"></span>
      <button class="noc-btn" onclick="toggleFullscreen()"><i class="ti ti-maximize"></i> Tela Cheia</button>
    </div>
  </div>

  <!-- KPIs Topo -->
  <div class="noc-kpi-grid">
    <div class="noc-kpi noc-amber">
      <div class="val"><?= $kpi['schedules_new'] ?></div>
      <div class="lbl">Reservas Pendentes</div>
    </div>
    <div class="noc-kpi noc-red">
      <div class="val"><?= $kpi['incidents_open'] ?></div>
      <div class="lbl">Incidentes Abertos</div>
    </div>
    <div class="noc-kpi noc-purple">
      <div class="val"><?= $kpi['maint_scheduled'] ?></div>
      <div class="lbl">Manutenções Agendadas</div>
    </div>
    <div class="noc-kpi noc-blue">
      <div class="val"><?= $kpi['vehicles_active'] ?></div>
      <div class="lbl">Veículos Operacionais</div>
    </div>
  </div>

  <!-- Grid Principal -->
  <div class="noc-grid-2">
    <!-- Reservas Pendentes -->
    <div class="noc-card">
      <div class="noc-card-header"><i class="ti ti-clock-pause" style="color:#f59e0b;"></i> Aguardando Aprovação</div>
      <?php if (empty($pending_schedules)): ?>
        <div class="noc-empty">Nenhuma reserva pendente</div>
      <?php else: ?>
        <table class="noc-table">
          <tr><th>Solicitante</th><th>Veículo</th><th>Período</th></tr>
          <?php foreach ($pending_schedules as $s): ?>
            <tr>
              <td><strong style="color:#f8fafc;"><?= getUserName($s['users_id']) ?></strong></td>
              <td><?= strip_tags(vs_vehicle_name($s['plugin_vehiclescheduler_vehicles_id'])) ?></td>
              <td><?= Html::convDate(substr($s['begin_date'],0,10)) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <!-- Incidentes Abertos -->
    <div class="noc-card">
      <div class="noc-card-header"><i class="ti ti-alert-triangle" style="color:#ef4444;"></i> Incidentes Abertos</div>
      <?php if (empty($open_incidents)): ?>
        <div class="noc-empty">Nenhum incidente crítico</div>
      <?php else: ?>
        <table class="noc-table">
          <tr><th>Data</th><th>Veículo</th><th>Status</th></tr>
          <?php foreach ($open_incidents as $inc): 
            $st = $inc['status'] == 1 ? 'bg-critical' : 'bg-warning';
            $lbl = $inc['status'] == 1 ? 'ABERTO' : 'EM ANÁLISE';
          ?>
            <tr>
              <td><?= Html::convDate(substr($inc['incident_date'],0,10)) ?></td>
              <td><strong style="color:#f8fafc;"><?= strip_tags(vs_vehicle_name($inc['plugin_vehiclescheduler_vehicles_id'])) ?></strong></td>
              <td><span class="noc-badge <?= $st ?>"><?= $lbl ?></span></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="noc-grid-2">
    <!-- Manutenções -->
    <div class="noc-card">
      <div class="noc-card-header"><i class="ti ti-tool" style="color:#8b5cf6;"></i> Manutenções Próximas / Atrasadas</div>
      <?php if (empty($upcoming_maint)): ?>
        <div class="noc-empty">Nenhuma manutenção pendente</div>
      <?php else: ?>
        <table class="noc-table">
          <tr><th>Veículo</th><th>Agendamento</th><th>Status</th></tr>
          <?php foreach ($upcoming_maint as $m): 
            $overdue = !empty($m['scheduled_date']) && $m['scheduled_date'] < date('Y-m-d') && $m['status'] == 1;
            $st = $overdue ? 'bg-critical' : 'bg-info';
            $lbl = $overdue ? 'ATRASADA' : 'AGENDADA';
            if ($m['status'] == 2) { $st = 'bg-warning'; $lbl = 'EM ANDAMENTO'; }
          ?>
            <tr>
              <td><strong style="color:#f8fafc;"><?= strip_tags(vs_vehicle_name($m['plugin_vehiclescheduler_vehicles_id'])) ?></strong></td>
              <td><?= Html::convDate($m['scheduled_date']) ?></td>
              <td><span class="noc-badge <?= $st ?>"><?= $lbl ?></span></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>

    <!-- CNHs -->
    <div class="noc-card">
      <div class="noc-card-header"><i class="ti ti-id-badge" style="color:#fcd34d;"></i> Alertas de CNH (< 90 dias)</div>
      <?php if (empty($cnh_warning)): ?>
        <div class="noc-empty">Nenhum vencimento próximo</div>
      <?php else: ?>
        <table class="noc-table">
          <tr><th>Motorista</th><th>Vencimento</th></tr>
          <?php foreach ($cnh_warning as $d): 
            $days = (int)((strtotime($d['cnh_expiry']) - time()) / 86400);
            $st = $days <= 30 ? 'bg-critical' : 'bg-warning';
          ?>
            <tr>
              <td><strong style="color:#f8fafc;"><?= htmlspecialchars($d['name']) ?></strong></td>
              <td><?= Html::convDate($d['cnh_expiry']) ?> <span class="noc-badge <?= $st ?>" style="margin-left:10px;"><?= $days ?> dias</span></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Auto Refresh (60 segundos)
setTimeout(() => { window.location.reload(); }, 60000);

// Relógio em tempo real
function updateClock() {
  const d = new Date();
  document.getElementById('noc-clock').innerText = "Atualizado: " + d.toLocaleTimeString();
}
setInterval(updateClock, 1000);
updateClock();

// Lógica de Tela Cheia
function toggleFullscreen() {
  let elem = document.documentElement;
  if (!document.fullscreenElement) {
    elem.requestFullscreen().catch(err => {
      console.log(`Erro ao tentar tela cheia: ${err.message}`);
      alert("O seu navegador bloqueou a tela cheia. Aperte F11.");
    });
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    }
  }
}
</script>
<?php Html::footer(); ?>
