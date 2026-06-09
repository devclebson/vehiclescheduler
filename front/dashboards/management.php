<?php
/**
 * Vehicle Scheduler - Management Dashboard
 */

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 4));
}
include_once(GLPI_ROOT . '/inc/includes.php');
include_once(__DIR__ . '/../../inc/helpers/common.inc.php');

// Unconditional Manager Security Check
if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

$is_tab = isset($_GET['is_tab']) || isset($_POST['is_tab']);

global $DB, $CFG_GLPI;

if (!$is_tab) {
    Html::header('Gestão da Frota', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'management');
}

// Handle quick actions (approve/refuse)
if (isset($_POST['quick_action'])) {
    if (Session::haveRight('plugin_vehiclescheduler', UPDATE)) {
        $schedule = new PluginVehicleschedulerSchedule();
        if ($schedule->getFromDB($_POST['id'])) {
            $update_data = [
                'id' => $_POST['id'],
                'status' => $_POST['quick_action'] == 'approve' ? 2 : 3, // 2=approved, 3=refused
                'approver_users_id' => Session::getLoginUserID()
            ];
            if ($_POST['quick_action'] == 'refuse' && isset($_POST['comment'])) {
                $update_data['comment'] = $_POST['comment'];
            }
            $schedule->update($update_data);
        }
        Session::addMessageAfterRedirect(
            $_POST['quick_action'] == 'approve' ? 'Reserva aprovada com sucesso!' : 'Reserva recusada com sucesso.',
            false,
            INFO
        );
        $redirect_url = Plugin::getWebDir('vehiclescheduler') . '/front/index.php';
        Html::redirect($redirect_url);
    }
}

$today = date('Y-m-d');

// Query dynamic warning counts
$cnh_expiry_count = (int)$DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_drivers',
    'WHERE' => [
        'is_active' => 1,
        'cnh_expiry' => ['<=', date('Y-m-d', strtotime('+90 days'))]
    ],
    'COUNT' => 'c'
])->current()['c'];

$maintenance_alerts_count = (int)$DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
    'WHERE' => [
        'status' => 1,
        'scheduled_date' => ['<=', $today]
    ],
    'COUNT' => 'c'
])->current()['c'];

// KPIs
$kpi = [
    'veiculos_ativos'   => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['is_active' => 1], 'COUNT' => 'c'])->current()['c'],
    'motoristas_ativos' => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_drivers', 'WHERE' => ['is_active' => 1], 'COUNT' => 'c'])->current()['c'],
    'reservas_novas'    => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_schedules', 'WHERE' => ['status' => 1], 'COUNT' => 'c'])->current()['c'],
    'reservas_aprovadas'=> (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_schedules', 'WHERE' => ['status' => 2], 'COUNT' => 'c'])->current()['c'],
    'incidentes_abertos'=> (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_incidents', 'WHERE' => ['status' => 1], 'COUNT' => 'c'])->current()['c'],
    'manutencoes_futuras'=> (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_maintenances', 'WHERE' => ['status' => 1], 'COUNT' => 'c'])->current()['c']
];

// Query exact fleet utilization status for TODAY
$maintenance_veh_ids = [];
$m_today = $DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_maintenances',
    'WHERE' => [
        'status' => 1,
        'scheduled_date' => ['<=', $today]
    ]
]);
foreach ($m_today as $mt) {
    $maintenance_veh_ids[] = $mt['plugin_vehiclescheduler_vehicles_id'];
}
$maintenance_veh_ids = array_unique($maintenance_veh_ids);
$in_maintenance_today = count($maintenance_veh_ids);

$reserved_veh_ids = [];
$s_today = $DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => [
        'status' => 2, // Approved
        'begin_date' => ['<=', $today . ' 23:59:59'],
        'end_date'   => ['>=', $today . ' 00:00:00']
    ]
]);
foreach ($s_today as $st) {
    if (!in_array($st['plugin_vehiclescheduler_vehicles_id'], $maintenance_veh_ids)) {
        $reserved_veh_ids[] = $st['plugin_vehiclescheduler_vehicles_id'];
    }
}
$reserved_veh_ids = array_unique($reserved_veh_ids);
$in_use_today = count($reserved_veh_ids);

$available_today = max(0, $kpi['veiculos_ativos'] - ($in_maintenance_today + $in_use_today));

$total_active = max(1, $kpi['veiculos_ativos']);
$pct_available = round(($available_today / $total_active) * 100);
$pct_in_use = round(($in_use_today / $total_active) * 100);
$pct_maintenance = round(($in_maintenance_today / $total_active) * 100);

if (!$is_tab) {
    vs_render_navbar('dashboard');
}
?>

<style>
.vs-card {
  padding: 24px !important;
}
.vs-kpi-card {
  flex: 1;
  min-width: 180px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border: 1px solid #e2e8f0;
  border-top: 4px solid #3b82f6 !important;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.vs-kpi-card:hover {
  transform: translateY(-4px);
}
.vs-kpi-card.kpi-primary { border-top-color: #3b82f6 !important; }
.vs-kpi-card.kpi-primary:hover { border-color: #3b82f6; box-shadow: 0 12px 20px -5px rgba(59, 130, 246, 0.15); }

.vs-kpi-card.kpi-success { border-top-color: #10b981 !important; }
.vs-kpi-card.kpi-success:hover { border-color: #10b981; box-shadow: 0 12px 20px -5px rgba(16, 185, 129, 0.15); }

.vs-kpi-card.kpi-warning { border-top-color: #f59e0b !important; }
.vs-kpi-card.kpi-warning:hover { border-color: #f59e0b; box-shadow: 0 12px 20px -5px rgba(245, 158, 11, 0.15); }

.vs-kpi-card.kpi-danger { border-top-color: #ef4444 !important; }
.vs-kpi-card.kpi-danger:hover { border-color: #ef4444; box-shadow: 0 12px 20px -5px rgba(239, 68, 68, 0.15); }

.vs-kpi-card.kpi-info { border-top-color: #0ea5e9 !important; }
.vs-kpi-card.kpi-info:hover { border-color: #0ea5e9; box-shadow: 0 12px 20px -5px rgba(14, 165, 233, 0.15); }

.vs-management-row {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 18px;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  justify-content: space-between;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border-left: 4px solid #f59e0b !important;
}
.vs-management-row:hover {
  background: #fff;
  border-color: #f59e0b;
  box-shadow: 0 8px 16px rgba(245, 158, 11, 0.08);
  transform: translateY(-2px);
}

.vs-initials-badge {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6) !important;
  color: #ffffff !important;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.9rem;
  flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.vs-maintenance-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 18px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border-left: 4px solid #0ea5e9 !important;
}
.vs-maintenance-row:hover {
  background: #fff;
  border-color: #0ea5e9;
  box-shadow: 0 8px 16px rgba(14, 165, 233, 0.08);
  transform: translateY(-2px);
}

.vs-driver-cnh-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border-left: 4px solid #ef4444 !important;
}
.vs-driver-cnh-row:hover {
  background: #fff;
  border-color: #ef4444;
  box-shadow: 0 8px 16px rgba(239, 68, 68, 0.08);
  transform: translateY(-2px);
}
</style>

<div class="vs-app-view" style="max-width: 1350px; margin: 0 auto; padding: 0 4px;">

  <!-- Operational Alerts Banner -->
  <?php
  $total_alerts = $kpi['reservas_novas'] + $cnh_expiry_count + $maintenance_alerts_count;
  if ($total_alerts > 0):
  ?>
    <div class="alert alert-warning d-flex align-items-center mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #fffdf5, #fef3c7); border: 1px solid #fcd34d; border-left: 5px solid #f59e0b !important; border-radius:12px; padding:16px 24px; color:#92400e; font-size:0.95rem; gap:12px; margin-bottom: 24px;">
      <i class="ti ti-alert-triangle" style="font-size:1.5rem; color:#d97706;"></i>
      <div style="flex:1;">
        <strong>Alertas Operacionais:</strong>
        <?php
        $alert_items = [];
        if ($kpi['reservas_novas'] > 0) {
            $alert_items[] = "<strong>{$kpi['reservas_novas']}</strong> " . ($kpi['reservas_novas'] == 1 ? "reserva aguardando aprovação" : "reservas aguardando aprovação");
        }
        if ($cnh_expiry_count > 0) {
            $alert_items[] = "<strong>{$cnh_expiry_count}</strong> " . ($cnh_expiry_count == 1 ? "condutor com CNH vencendo nos próximos 90 dias" : "condutores com CNH vencendo nos próximos 90 dias");
        }
        if ($maintenance_alerts_count > 0) {
            $alert_items[] = "<strong>{$maintenance_alerts_count}</strong> " . ($maintenance_alerts_count == 1 ? "manutenção pendente/atrasada" : "manutenções pendentes/atrasadas");
        }
        echo implode(', ', $alert_items) . ".";
        ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- KPI Cards Grid -->
  <div style="display: flex; gap: 20px; margin-bottom: 28px; flex-wrap: wrap;">
    <?php
    $cards = [
        ['icon' => 'ti-car', 'value' => $kpi['veiculos_ativos'], 'label' => 'Veículos Ativos', 'color' => 'primary'],
        ['icon' => 'ti-steering-wheel', 'value' => $kpi['motoristas_ativos'], 'label' => 'Motoristas Ativos', 'color' => 'success'],
        ['icon' => 'ti-clock', 'value' => $kpi['reservas_novas'], 'label' => 'Pendente Aprovação', 'color' => 'warning'],
        ['icon' => 'ti-check', 'value' => $kpi['reservas_aprovadas'], 'label' => 'Reservas Aprovadas', 'color' => 'success'],
        ['icon' => 'ti-alert-triangle', 'value' => $kpi['incidentes_abertos'], 'label' => 'Incidentes Abertos', 'color' => 'danger'],
        ['icon' => 'ti-tool', 'value' => $kpi['manutencoes_futuras'], 'label' => 'Revisões Agendadas', 'color' => 'info']
    ];

    $color_map = [
        'primary' => ['text' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.08)'],
        'success' => ['text' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.08)'],
        'warning' => ['text' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.08)'],
        'danger'  => ['text' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.08)'],
        'info'    => ['text' => '#0ea5e9', 'bg' => 'rgba(14, 165, 233, 0.08)'],
    ];

    foreach ($cards as $card):
        $c = $color_map[$card['color']] ?? ['text' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.08)'];
    ?>
      <div class="vs-kpi-card kpi-<?= $card['color'] ?>">
        <div style="text-align: left;">
          <div style="color: var(--vs-text-light); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;"><?= $card['label'] ?></div>
          <h2 style="font-size: 2.2rem; font-weight: 800; margin: 6px 0 0; color: <?= $c['text'] ?>; line-height: 1;"><?= $card['value'] ?></h2>
        </div>
        <div style="width: 48px; height: 48px; border-radius: 12px; background: <?= $c['bg'] ?>; display: flex; align-items: center; justify-content: center; color: <?= $c['text'] ?>;">
          <i class="ti <?= $card['icon'] ?>" style="font-size: 1.5rem;"></i>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Two-Column Layout -->
  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    <!-- Left Column (Aprovação & Manutenções) -->
    <div>
      
      <!-- Approvals Card -->
      <div class="vs-card mb-4" style="margin-bottom: 24px; border-radius: 16px; border: 1px solid #e5e7eb; border-left: 4px solid #f59e0b;">
        <div class="vs-card-header" style="margin-bottom: 18px; padding-bottom: 12px;">
          <h3><i class="ti ti-clock" style="color: #f59e0b;"></i> Solicitações Aguardando Aprovação</h3>
          <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.php" style="font-size: 0.82rem; color:#3b82f6; text-decoration:none; font-weight:700;">Ver todos &rarr;</a>
        </div>

        <?php
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_vehiclescheduler_schedules',
            'WHERE' => ['status' => 1],
            'ORDER' => 'begin_date ASC',
            'LIMIT' => 5
        ]);

        if ($iterator->count() > 0):
            echo "<div style='display:flex; flex-direction:column; gap:16px;'>";
            foreach ($iterator as $row):
                $user = new User();
                $user->getFromDB($row['users_id']);
                $user_name = $user->getName();
                $initials = mb_strtoupper(mb_substr($user->fields['realname'] ?? $user_name, 0, 2));
                
                $vehicle = new PluginVehicleschedulerVehicle();
                $vehicle->getFromDB($row['plugin_vehiclescheduler_vehicles_id']);
                $vehicle_name = $vehicle->fields['name'] . " (" . $vehicle->fields['plate'] . ")";
                
                $begin_dt = date('d/m/Y H:i', strtotime($row['begin_date']));
                $end_dt = date('d/m/Y H:i', strtotime($row['end_date']));
        ?>
              <div class="vs-management-row">
                <div style="display:flex; gap:14px; flex:1;">
                  <div class="vs-initials-badge"><?= $initials ?></div>
                  <div style="flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                      <span style="font-weight:700; font-size:0.95rem; color:#1e293b;"><?= $user_name ?></span>
                      <span style="font-size:0.75rem; color:#64748b; font-weight:600; background:#f1f5f9; padding:2px 8px; border-radius:4px;"><?= htmlspecialchars($row['department'] ?? 'Geral') ?></span>
                    </div>
                    <div style="margin-top:6px; font-size:0.85rem; color:#334155;">
                      <strong>Veículo:</strong> <?= $vehicle_name ?>
                    </div>
                    <div style="margin-top:4px; font-size:0.8rem; color:#64748b; display:flex; flex-wrap:wrap; gap:12px;">
                      <span><i class="ti ti-calendar"></i> <?= $begin_dt ?> &rarr; <?= $end_dt ?></span>
                      <span><i class="ti ti-map-pin"></i> <?= htmlspecialchars($row['destination']) ?></span>
                    </div>
                    <?php if (!empty($row['purpose'])): ?>
                      <div style="margin-top:6px; font-size:0.8rem; color:#64748b; font-style:italic; background:#fff; padding:6px 10px; border-radius:6px; border:1px solid #f1f5f9;"><?= htmlspecialchars($row['purpose']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <!-- Action Buttons -->
                <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end; margin-left:12px; flex-shrink:0;">
                  <form method="post" action="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/dashboards/management.php" onsubmit="return handleManagementAction(this, event);" style="display:flex; gap:6px;">
                    <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="comment" value="" class="action-comment">
                    <button type="submit" name="quick_action" value="approve" class="vs-btn vs-btn-primary" style="padding:6px 12px; font-size:0.8rem; background:#22c55e !important; border:none; box-shadow:none;"><i class="ti ti-check"></i> Aprovar</button>
                    <button type="submit" name="quick_action" value="refuse" class="vs-btn vs-btn-danger" style="padding:6px 12px; font-size:0.8rem; background:#ef4444 !important; border:none; box-shadow:none;"><i class="ti ti-x"></i> Recusar</button>
                  </form>
                  <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.form.php?id=<?= $row['id'] ?>" class="vs-btn vs-btn-light" style="padding:6px 12px; font-size:0.8rem; border-color:#cbd5e1;"><i class="ti ti-eye"></i> Detalhes</a>
                </div>
              </div>
        <?php
            endforeach;
            echo "</div>";
        else:
        ?>
          <div class="vs-empty-state" style="padding:32px 16px; text-align:center;">
            <div class="vs-empty-icon" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:8px;"><i class="ti ti-circle-check" style="color:#10b981; opacity:0.8;"></i></div>
            <p class="vs-empty-text" style="font-weight:700; color:#475569; margin:0;">Nenhuma aprovação pendente</p>
            <p class="vs-empty-desc" style="font-size:0.8rem; color:#94a3b8; margin:4px 0 0;">Todas as solicitações de reservas foram processadas.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Maintenances Card -->
      <div class="vs-card" style="border-radius: 16px; border: 1px solid #e5e7eb; border-left: 4px solid #0ea5e9;">
        <div class="vs-card-header" style="margin-bottom: 18px; padding-bottom: 12px;">
          <h3><i class="ti ti-tool" style="color: #0ea5e9;"></i> Cronograma de Próximas Manutenções</h3>
          <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/maintenance.php" style="font-size: 0.82rem; color:#3b82f6; text-decoration:none; font-weight:700;">Ver todas &rarr;</a>
        </div>

        <?php
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_vehiclescheduler_maintenances',
            'WHERE' => ['status' => 1],
            'LIMIT' => 5,
            'ORDER' => 'scheduled_date ASC'
        ]);

        if ($iterator->count() > 0):
            echo "<div style='display:flex; flex-direction:column; gap:12px;'>";
            foreach ($iterator as $row):
                $vehicle = new PluginVehicleschedulerVehicle();
                $vehicle->getFromDB($row['plugin_vehiclescheduler_vehicles_id']);
                
                $date_formatted = date('d/m/Y', strtotime($row['scheduled_date']));
                $days_left = round((strtotime($row['scheduled_date']) - time()) / (60 * 60 * 24));
                
                if ($days_left <= 0) {
                    $days_label = "Hoje ou Atrasada";
                    $days_badge = "vs-badge-red";
                } else {
                    $days_label = "Em {$days_left} dias";
                    $days_badge = $days_left <= 7 ? "vs-badge-yellow" : "vs-badge-blue";
                }
                
                $m_type = $row['type'] == 1 ? 'Preventiva' : 'Corretiva';
                $m_badge = $row['type'] == 1 ? 'vs-badge-green' : 'vs-badge-red';
        ?>
              <div class="vs-maintenance-row">
                <div>
                  <div style="font-weight:700; font-size:0.9rem; color:#1e293b;">
                    <?= htmlspecialchars($vehicle->fields['name']) ?> 
                    <span style="font-size:0.75rem; color:#64748b; font-weight:normal;">(<?= htmlspecialchars($vehicle->fields['plate']) ?>)</span>
                  </div>
                  <div style="margin-top:4px; font-size:0.8rem; color:#64748b;">
                    Agendada: <?= $date_formatted ?> • Custo Estimado: R$ <?= number_format($row['cost'] ?? 0, 2, ',', '.') ?>
                  </div>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                  <span class="vs-badge <?= $m_badge ?>"><?= $m_type ?></span>
                  <span class="vs-badge <?= $days_badge ?>"><?= $days_label ?></span>
                </div>
              </div>
        <?php
            endforeach;
            echo "</div>";
        else:
        ?>
          <div class="vs-empty-state" style="padding:24px 12px; text-align:center;">
            <div class="vs-empty-icon" style="font-size:2.2rem; color:#cbd5e1; margin-bottom:8px;"><i class="ti ti-tool"></i></div>
            <p class="vs-empty-text" style="font-weight:700; color:#475569; margin:0;">Nenhuma manutenção agendada</p>
            <p class="vs-empty-desc" style="font-size:0.8rem; color:#94a3b8; margin:4px 0 0;">Tudo em ordem na frota.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Column (Utilization & CNH Alerts) -->
    <div>
      
      <!-- Fleet Utilization Status Card -->
      <div class="vs-card mb-4" style="margin-bottom: 24px; border-radius: 16px; border: 1px solid #e5e7eb; border-left: 4px solid #3b82f6;">
        <div class="vs-card-header" style="margin-bottom: 18px; padding-bottom: 12px;">
          <h3><i class="ti ti-chart-bar" style="color: #3b82f6;"></i> Uso da Frota (Hoje)</h3>
        </div>
        
        <div style="margin-bottom: 18px;">
          <div style="display:flex; justify-content:space-between; font-size:0.8rem; color:#64748b; font-weight:700; margin-bottom:8px;">
            <span>Taxa de Utilização</span>
            <span><?= round((($in_use_today + $in_maintenance_today) / $total_active) * 100) ?>%</span>
          </div>
          <!-- Segmented Progress Bar -->
          <div style="display:flex; width:100%; height:14px; border-radius:7px; overflow:hidden; background:#e2e8f0; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
            <?php if ($available_today > 0): ?>
              <div style="width:<?= $pct_available ?>%; background: linear-gradient(90deg, #10b981, #059669);" title="Disponíveis: <?= $pct_available ?>%"></div>
            <?php endif; ?>
            <?php if ($in_use_today > 0): ?>
              <div style="width:<?= $pct_in_use ?>%; background: linear-gradient(90deg, #3b82f6, #1d4ed8);" title="Em Uso: <?= $pct_in_use ?>%"></div>
            <?php endif; ?>
            <?php if ($in_maintenance_today > 0): ?>
              <div style="width:<?= $pct_maintenance ?>%; background: linear-gradient(90deg, #f59e0b, #d97706);" title="Em Manutenção: <?= $pct_maintenance ?>%"></div>
            <?php endif; ?>
          </div>
        </div>
        
        <!-- Detailed Legend -->
        <div style="display:flex; flex-direction:column; gap:10px; font-size:0.82rem; font-weight:600; color:#475569;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:6px;"><span style="width:10px; height:10px; border-radius:50%; background:#22c55e;"></span> Disponíveis</span>
            <strong><?= $available_today ?> / <?= $total_active ?></strong>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:6px;"><span style="width:10px; height:10px; border-radius:50%; background:#3b82f6;"></span> Em Viagem</span>
            <strong><?= $in_use_today ?> / <?= $total_active ?></strong>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:6px;"><span style="width:10px; height:10px; border-radius:50%; background:#f59e0b;"></span> Em Manutenção</span>
            <strong><?= $in_maintenance_today ?> / <?= $total_active ?></strong>
          </div>
        </div>
      </div>

      <!-- CNH Expiry Alerts Card -->
      <div class="vs-card" style="border-radius: 16px; border: 1px solid #e5e7eb; border-left: 4px solid #ef4444;">
        <div class="vs-card-header" style="margin-bottom: 18px; padding-bottom: 12px;">
          <h3><i class="ti ti-id" style="color: #ef4444;"></i> Alertas CNH (Próximas a Vencer)</h3>
          <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/driver.php" style="font-size: 0.82rem; color:#3b82f6; text-decoration:none; font-weight:700;">Ver todos &rarr;</a>
        </div>

        <?php
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_vehiclescheduler_drivers',
            'WHERE' => [
                'is_active' => 1,
                'cnh_expiry' => ['<=', date('Y-m-d', strtotime('+90 days'))]
            ],
            'ORDER' => 'cnh_expiry ASC'
        ]);

        if ($iterator->count() > 0):
            echo "<div style='display:flex; flex-direction:column; gap:12px;'>";
            foreach ($iterator as $row):
                $days = round((strtotime($row['cnh_expiry']) - time()) / (60 * 60 * 24));
                $badge_class = $days <= 30 ? 'vs-badge-red' : 'vs-badge-yellow';
                $days_lbl = $days <= 0 ? 'VENCIDA' : "{$days} dias";
        ?>
              <div class="vs-driver-cnh-row">
                <div>
                  <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/driver.form.php?id=<?= $row['id'] ?>" style="font-weight:700; text-decoration:none; font-size:0.88rem; color:#1e293b;"><?= $row['name'] ?></a>
                  <div style="font-size:0.75rem; color:#64748b; margin-top:2px;">Vence em: <?= date('d/m/Y', strtotime($row['cnh_expiry'])) ?></div>
                </div>
                <div>
                  <span class="vs-badge <?= $badge_class ?>"><?= $days_lbl ?></span>
                </div>
              </div>
        <?php
            endforeach;
            echo "</div>";
        else:
        ?>
          <div class="vs-empty-state" style="padding:24px 12px; text-align:center;">
            <div class="vs-empty-icon" style="font-size:2.2rem; color:#cbd5e1; margin-bottom:8px;"><i class="ti ti-circle-check" style="color:#10b981; opacity:0.8;"></i></div>
            <p class="vs-empty-text" style="font-weight:700; color:#475569; margin:0;">CNHs em dia</p>
            <p class="vs-empty-desc" style="font-size:0.8rem; color:#94a3b8; margin:4px 0 0;">Nenhum condutor com CNH vencendo.</p>
          </div>
        <?php endif; ?>
      </div>

    </div>

  </div><!-- .display-grid -->

  <!-- Quick Action Footer Links -->
  <?php if (!$is_tab): ?>
    <div style="display: flex; justify-content: center; gap: 15px; margin-top: 30px; flex-wrap: wrap;">
      <?php
      $links = [
          ['icon' => 'ti-car', 'label' => 'Gerenciar Veículos', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/vehicle.php'],
          ['icon' => 'ti-steering-wheel', 'label' => 'Gerenciar Motoristas', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/driver.php'],
          ['icon' => 'ti-alert-triangle', 'label' => 'Ver Incidentes', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/incident.php'],
          ['icon' => 'ti-tool', 'label' => 'Manutenções', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/maintenance.php'],
          ['icon' => 'ti-shield-lock', 'label' => 'Sinistros', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/insuranceclaim.php'],
          ['icon' => 'ti-calendar', 'label' => 'Todas as Reservas', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/schedule.php']
      ];

      foreach ($links as $link):
      ?>
        <a href="<?= $link['url'] ?>" class="vs-btn vs-btn-light"><i class="ti <?= $link['icon'] ?>"></i> <?= $link['label'] ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div><!-- .vs-app-view -->

<script>
function handleManagementAction(form, event) {
    const action = event.submitter ? event.submitter.value : null;
    if (action === 'refuse') {
        const comment = prompt('Por favor, informe o motivo da recusa:');
        if (comment === null) return false;
        if (comment.trim() === '') {
            alert('O motivo da recusa é obrigatório.');
            return false;
        }
        form.querySelector('.action-comment').value = comment;
    }
    return true;
}
</script>

<?php
if (!$is_tab) {
    Html::footer();
}
?>