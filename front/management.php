<?php
/**
 * Dashboard Gerencial - Gestão completa de frota
 */
include('../../../inc/includes.php');

// Verificar permissão de acesso à gestão
if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

// Carregar CSS glassmorphism
include_once(__DIR__ . '/../inc/common.inc.php');

global $DB;

// Processar aprovação/rejeição rápida
if (isset($_POST['quick_action']) && isset($_POST['schedule_id'])) {
    // Verificar se tem permissão para aprovar
    if (!PluginVehicleschedulerProfile::canApproveReservations()) {
        Session::addMessageAfterRedirect('Você não tem permissão para aprovar/rejeitar reservas.', false, ERROR);
        Html::redirect($_SERVER['PHP_SELF']);
    }
    
    $sch = new PluginVehicleschedulerSchedule();
    if ($sch->getFromDB($_POST['schedule_id'])) {
        $new_status = $_POST['quick_action'] == 'approve' ? 2 : 3;
        $sch->update(['id' => $_POST['schedule_id'], 'status' => $new_status]);
        Session::addMessageAfterRedirect(
            $_POST['quick_action'] == 'approve' ? 'Reserva aprovada!' : 'Reserva recusada!',
            false,
            INFO
        );
        Html::redirect($_SERVER['PHP_SELF']);
    }
}

// KPIs
$kpi = [
    'veiculos_ativos'   => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['is_active' => 1], 'COUNT' => 'c'])->current()['c'],
    'motoristas_ativos' => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_drivers', 'WHERE' => ['is_active' => 1], 'COUNT' => 'c'])->current()['c'],
    'reservas_novas'    => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_schedules', 'WHERE' => ['status' => 1], 'COUNT' => 'c'])->current()['c'],
    'reservas_aprovadas'=> (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_schedules', 'WHERE' => ['status' => 2], 'COUNT' => 'c'])->current()['c'],
    'incidentes_abertos'=> (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_incidents', 'WHERE' => ['status' => [1,2]], 'COUNT' => 'c'])->current()['c'],
    'manut_agendadas'   => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_maintenances', 'WHERE' => ['status' => 1], 'COUNT' => 'c'])->current()['c'],
];

// Reservas pendentes de aprovação
$pending = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => ['status' => 1],
    'ORDER' => ['date_creation DESC'],
    'LIMIT' => 10,
]));

// CNH vencendo
$cnh_alert = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_drivers',
    'WHERE' => [
        'is_active' => 1,
        'cnh_expiry' => ['<=', date('Y-m-d', strtotime('+90 days'))],
        'cnh_expiry' => ['>=', date('Y-m-d')],
    ],
    'ORDER' => ['cnh_expiry ASC'],
    'LIMIT' => 5,
]));

// Próximas manutenções
$upcoming_maint = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
    'WHERE' => ['status' => [1, 2]],
    'ORDER' => ['scheduled_date ASC'],
    'LIMIT' => 5,
]));

Html::header('Gestão de Frota', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'dashboard');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
?>
<style>
.mgmt{font-family:inherit;padding:0 12px;max-width:1600px;margin:0 auto;}
.mgmt-header{background:#1e293b;color:#f8fafc;border-radius:14px;padding:28px 32px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;border:1px solid #334155;}
.mgmt-header h1{margin:0;font-size:1.8rem;font-weight:700;color:#f8fafc;}
.mgmt-actions{display:flex;gap:10px;}
.mgmt-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background:#334155;color:#f8fafc;border-radius:8px;font-weight:600;text-decoration:none;font-size:.85rem;border:1px solid #475569;transition:all 0.2s ease;}
.mgmt-btn:hover{background:#475569;color:#fff;text-decoration:none;}
.btn-primary-top{background:#2563eb;color:#fff;border-color:#1d4ed8;}
.btn-primary-top:hover{background:#1d4ed8;color:#fff;}
.mgmt-kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px;}
.mgmt-kpi{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.05);}
.mgmt-kpi .val{font-size:2.4rem;font-weight:800;line-height:1;margin-bottom:6px;color:#0f172a;}
.mgmt-kpi .lbl{font-size:.75rem;color:#64748b;font-weight:700;letter-spacing:0.5px;text-transform:uppercase;}
.kpi-icon{display:inline-block;margin-right:6px;font-size:1.1rem;vertical-align:middle;}
.kpi-blue .kpi-icon{color:#3b82f6;} .kpi-green .kpi-icon{color:#10b981;} .kpi-amber .kpi-icon{color:#f59e0b;} .kpi-red .kpi-icon{color:#ef4444;} .kpi-purple .kpi-icon{color:#8b5cf6;}
.mgmt-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px;}
.mgmt-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);}
.mgmt-card-header{padding:16px 20px;border-bottom:1px solid #f1f5f9;font-weight:700;font-size:.95rem;display:flex;justify-content:space-between;align-items:center;color:#1e293b;}
.mgmt-table{width:100%;border-collapse:collapse;font-size:.85rem;}
.mgmt-table th{background:#f8fafc;padding:12px 16px;text-align:left;color:#64748b;font-weight:600;font-size:.75rem;text-transform:uppercase;border-bottom:1px solid #e2e8f0;}
.mgmt-table td{padding:14px 16px;border-bottom:1px solid #f1f5f9;color:#334155;}
.mgmt-table tr:hover td{background:#f8fafc;}
.mgmt-badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.72rem;font-weight:700;}
.badge-new{background:#f1f5f9;color:#475569;} .badge-approved{background:#ecfdf5;color:#059669;} .badge-critical{background:#fef2f2;color:#dc2626;}
.mgmt-quick-btns{display:flex;gap:6px;}
.mgmt-quick-btn{padding:5px 12px;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;border:1px solid transparent;transition:all 0.2s;}
.btn-approve{background:#fff;color:#059669;border-color:#a7f3d0;} .btn-approve:hover{background:#ecfdf5;}
.btn-reject{background:#fff;color:#dc2626;border-color:#fecaca;} .btn-reject:hover{background:#fef2f2;}
.mgmt-empty{padding:30px;text-align:center;color:#94a3b8;font-size:.9rem;}
.mgmt-shortcut{display:inline-flex;align-items:center;gap:8px;padding:14px 24px;background:#fff;color:#334155;border-radius:10px;font-weight:600;text-decoration:none;font-size:.95rem;border:1px solid #e2e8f0;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.2s;}
.mgmt-shortcut:hover{border-color:#cbd5e1;background:#f8fafc;transform:translateY(-1px);color:#0f172a;}
.mgmt-shortcut i{font-size:1.3rem;color:#64748b;}
@media(max-width:1000px){.mgmt-grid{grid-template-columns:1fr;}}
</style>

<div class="mgmt">

<!-- Header -->
<div class="mgmt-header">
  <div>
    <h1>📊 Dashboard de Gestão de Frota</h1>
    <p style="margin:6px 0 0;opacity:.9;font-size:.9rem;">Visão geral e aprovação de reservas</p>
  </div>
  <div class="mgmt-actions">
    <a href="dashboard.php" class="mgmt-btn btn-primary-top"><i class="ti ti-layout-dashboard"></i> Dashboard Moderno</a>
    <a href="calendar.php" class="mgmt-btn"><i class="ti ti-calendar"></i> Calendário</a>
    <a href="schedule.php" class="mgmt-btn"><i class="ti ti-list"></i> Todas Reservas</a>
  </div>
</div>

<!-- KPIs -->
<div class="mgmt-kpi-grid">
  <div class="mgmt-kpi kpi-blue">
    <div class="val"><?= $kpi['veiculos_ativos'] ?></div>
    <div class="lbl"><i class="ti ti-car kpi-icon"></i> VEÍCULOS ATIVOS</div>
  </div>
  <div class="mgmt-kpi kpi-green">
    <div class="val"><?= $kpi['motoristas_ativos'] ?></div>
    <div class="lbl"><i class="ti ti-steering-wheel kpi-icon"></i> MOTORISTAS ATIVOS</div>
  </div>
  <div class="mgmt-kpi kpi-amber">
    <div class="val"><?= $kpi['reservas_novas'] ?></div>
    <div class="lbl"><i class="ti ti-clock-pause kpi-icon"></i> AGUARDANDO APROVAÇÃO</div>
  </div>
  <div class="mgmt-kpi kpi-green">
    <div class="val"><?= $kpi['reservas_aprovadas'] ?></div>
    <div class="lbl"><i class="ti ti-check kpi-icon"></i> RESERVAS APROVADAS</div>
  </div>
  <div class="mgmt-kpi kpi-red">
    <div class="val"><?= $kpi['incidentes_abertos'] ?></div>
    <div class="lbl"><i class="ti ti-alert-triangle kpi-icon"></i> INCIDENTES ABERTOS</div>
  </div>
  <div class="mgmt-kpi kpi-purple">
    <div class="val"><?= $kpi['manut_agendadas'] ?></div>
    <div class="lbl"><i class="ti ti-tool kpi-icon"></i> MANUTENÇÕES AGENDADAS</div>
  </div>
</div>

<!-- Grid Principal -->
<div class="mgmt-grid">

  <!-- Aprovação Rápida -->
  <div class="mgmt-card">
    <div class="mgmt-card-header">
      <span><i class="ti ti-clock-check"></i> Aprovação Rápida de Reservas</span>
      <a href="schedule.php?status=1" style="font-size:.75rem;color:#3b82f6;">Ver todas →</a>
    </div>
    <?php if (empty($pending)): ?>
      <div class="mgmt-empty">✅ Nenhuma reserva aguardando aprovação</div>
    <?php else: ?>
      <table class="mgmt-table">
        <tr>
          <th>Solicitante</th>
          <th>Veículo</th>
          <th>Período</th>
          <th>Destino</th>
          <th>Ações</th>
        </tr>
        <?php foreach ($pending as $p):
          $veh_row = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['id' => $p['plugin_vehiclescheduler_vehicles_id']]])->current();
          $veh_name = $veh_row ? htmlspecialchars($veh_row['name'] . ' (' . $veh_row['plate'] . ')') : '#' . $p['plugin_vehiclescheduler_vehicles_id'];
        ?>
          <tr>
            <td><?= getUserName($p['users_id']) ?></td>
            <td><?= $veh_name ?></td>
            <td style="white-space:nowrap"><?= Html::convDate(substr($p['begin_date'],0,10)) ?> → <?= Html::convDate(substr($p['end_date'],0,10)) ?></td>
            <td><?= htmlspecialchars(substr($p['destination'], 0, 30)) ?></td>
            <td>
              <?php if (PluginVehicleschedulerProfile::canApproveReservations()): ?>
                <form method="post" style="display:inline-flex;gap:6px;">
                  <input type="hidden" name="schedule_id" value="<?= $p['id'] ?>">
                  <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                  <button type="submit" name="quick_action" value="approve" class="mgmt-quick-btn btn-approve">✓ Aprovar</button>
                  <button type="submit" name="quick_action" value="reject" class="mgmt-quick-btn btn-reject">✗ Recusar</button>
                  <a href="schedule.form.php?id=<?= $p['id'] ?>" style="padding:4px 8px;color:#64748b;"><i class="ti ti-eye"></i></a>
                </form>
              <?php else: ?>
                <a href="schedule.form.php?id=<?= $p['id'] ?>" class="mgmt-quick-btn btn-approve" style="text-decoration:none;"><i class="ti ti-eye"></i> Ver</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

  <!-- Alertas CNH -->
  <div class="mgmt-card">
    <div class="mgmt-card-header">
      <span><i class="ti ti-id-badge"></i> Alertas CNH</span>
      <a href="driver.php" style="font-size:.75rem;color:#3b82f6;">Ver todos →</a>
    </div>
    <?php if (empty($cnh_alert)): ?>
      <div class="mgmt-empty">✅ Sem CNH vencendo em 90 dias</div>
    <?php else: ?>
      <table class="mgmt-table">
        <tr><th>Motorista</th><th>Vencimento</th></tr>
        <?php foreach ($cnh_alert as $d):
          $days = (int)((strtotime($d['cnh_expiry']) - time()) / 86400);
          $badge = $days <= 30 ? 'badge-critical' : 'badge-new';
        ?>
          <tr>
            <td><a href="driver.form.php?id=<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></a></td>
            <td><span class="mgmt-badge <?= $badge ?>"><?= Html::convDate($d['cnh_expiry']) ?> (<?= $days ?> dias)</span></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>

</div>

<!-- Nova Linha: Próximas Manutenções -->
<div class="mgmt-grid" style="grid-template-columns:1fr; margin-top:20px;">
  <div class="mgmt-card">
    <div class="mgmt-card-header">
      <span><i class="ti ti-tool"></i> Próximas Manutenções</span>
      <a href="maintenance.php" style="font-size:.75rem;color:#3b82f6;">Ver todas →</a>
    </div>
    <?php if (empty($upcoming_maint)): ?>
      <div class="mgmt-empty">✅ Nenhuma manutenção pendente ou em atraso</div>
    <?php else: ?>
      <table class="mgmt-table">
        <tr><th>Veículo</th><th>Tipo</th><th>Agendamento</th><th>Status</th></tr>
        <?php
        $maint_types = PluginVehicleschedulerMaintenance::getAllTypes();
        foreach ($upcoming_maint as $m):
          $overdue = !empty($m['scheduled_date']) && $m['scheduled_date'] < date('Y-m-d') && $m['status'] == 1;
          $st_class = $m['status'] == 1 ? 'badge-new' : 'badge-approved';
          $st_label = $m['status'] == 1 ? 'Agendada' : 'Em andamento';
          if ($overdue) { $st_class = 'badge-critical'; $st_label = '⚠️ Em atraso'; }
          $veh_row = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['id' => $m['plugin_vehiclescheduler_vehicles_id']]])->current();
          $veh_name = $veh_row ? htmlspecialchars($veh_row['name'] . ' (' . $veh_row['plate'] . ')') : '#' . $m['plugin_vehiclescheduler_vehicles_id'];
        ?>
          <tr>
            <td><?= $veh_name ?></td>
            <td><a href="maintenance.form.php?id=<?= $m['id'] ?>"><?= $maint_types[$m['type']] ?? '?' ?></a></td>
            <td><?= Html::convDate($m['scheduled_date']) ?></td>
            <td><span class="mgmt-badge <?= $st_class ?>"><?= $st_label ?></span></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>

<!-- Atalhos Rápidos -->
<div style="margin-top:30px;display:flex;gap:16px;flex-wrap:wrap;">
  <a href="vehicle.php" class="mgmt-shortcut"><i class="ti ti-car"></i> Gerenciar Veículos</a>
  <a href="driver.php" class="mgmt-shortcut"><i class="ti ti-steering-wheel"></i> Gerenciar Motoristas</a>
  <a href="incident.php" class="mgmt-shortcut"><i class="ti ti-alert-triangle"></i> Ver Incidentes</a>
  <a href="maintenance.php" class="mgmt-shortcut"><i class="ti ti-tool"></i> Manutenções</a>
  <a href="insuranceclaim.php" class="mgmt-shortcut"><i class="ti ti-shield"></i> Sinistros</a>
</div>

</div>
<?php Html::footer(); ?>
