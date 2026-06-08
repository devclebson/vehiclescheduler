<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Requester Portal — my reservations, report incident, register driver
 */
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 4));
}
include_once(GLPI_ROOT . '/inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', READ);

$is_tab = isset($_GET['is_tab']) || isset($_POST['is_tab']);

if (!$is_tab) {
    if (!PluginVehicleschedulerProfile::canViewManagement()) {
        Html::displayRightError();
        exit;
    }
    Html::header(
        __('Fleet Portal', 'vehiclescheduler'),
        $_SERVER['PHP_SELF'],
        'plugins',
        'pluginvehicleschedulerschedule',
        'schedule'
    );
    vs_render_navbar('portal');
}

include_once(__DIR__ . '/../../inc/helpers/common.inc.php');

global $DB, $CFG_GLPI;
$my_uid = Session::getLoginUserID();
$user_name = getUserName($my_uid);

// Active Reservations Count (1 = New/Pending, 2 = Approved/Active)
$active_reservations_count = (int)$DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => [
        'users_id' => $my_uid,
        'status'   => [1, 2]
    ],
    'COUNT' => 'c'
])->current()['c'];

// Open Incidents Count (1 = Open, 2 = Under Analysis)
$open_incidents_count = (int)$DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_incidents',
    'WHERE' => [
        'users_id' => $my_uid,
        'status'   => [1, 2]
    ],
    'COUNT' => 'c'
])->current()['c'];

// My reservations list (All of them, sorted by newest)
$my_schedules = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => ['users_id' => $my_uid],
    'ORDER' => ['begin_date DESC'],
    'LIMIT' => 10,
]));

// My incidents list (Sorted by newest)
$my_incidents = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_incidents',
    'WHERE' => ['users_id' => $my_uid],
    'ORDER' => ['incident_date DESC'],
    'LIMIT' => 6,
]));

$sch_statuses = PluginVehicleschedulerSchedule::getAllStatus();
$inc_types    = PluginVehicleschedulerIncident::getAllTypes();
$inc_statuses = PluginVehicleschedulerIncident::getAllStatus();

function vs_vehicle_label(int $id): string {
    global $DB;
    static $cache = [];
    if (!$id) return '—';
    if (!isset($cache[$id])) {
        $r = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['id' => $id]])->current();
        $cache[$id] = $r ? htmlspecialchars($r['name'] . ' (' . $r['plate'] . ')') : "#{$id}";
    }
    return $cache[$id];
}
?>

<style>
.vs-app-view {
  background: transparent;
  padding: 8px 0;
}
.vs-portal-welcome {
  background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #7c3aed 100%);
  color: #fff;
  border-radius: 16px;
  padding: 28px 32px;
  margin-bottom: 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 10px 30px -5px rgba(124, 58, 237, 0.3);
  flex-wrap: wrap;
  gap: 20px;
  position: relative;
  overflow: hidden;
}
.vs-portal-welcome::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
  pointer-events: none;
}
.vs-portal-welcome h1 {
  margin: 0;
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: #fff;
  text-shadow: 0 2px 4px rgba(0,0,0,0.15);
}
.vs-portal-welcome p {
  margin: 6px 0 0;
  opacity: 0.95;
  font-size: 0.98rem;
  color: #e0f2fe;
}
.vs-portal-stats {
  display: flex;
  gap: 16px;
}
.vs-portal-stat-badge {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(8px);
  padding: 10px 18px;
  border-radius: 12px;
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.2);
  min-width: 110px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}
.vs-portal-stat-val {
  font-size: 1.7rem;
  font-weight: 800;
  line-height: 1.1;
  color: #fff;
}
.vs-portal-stat-lbl {
  font-size: 0.7rem;
  font-weight: 700;
  opacity: 0.95;
  margin-top: 4px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #bae6fd;
}

.vs-portal-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 28px;
}
.vs-action-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 24px;
  background: #fff;
  border-radius: 16px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  text-decoration: none !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04), 0 4px 6px -2px rgba(0, 0, 0, 0.01);
}
.vs-action-card:hover {
  transform: translateY(-5px);
}
.vs-action-card-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  transition: all 0.3s;
}
.vs-action-card:hover .vs-action-card-icon {
  transform: scale(1.1) rotate(5deg);
}
.vs-action-card-content {
  flex: 1;
}
.vs-action-card-title {
  font-size: 1.08rem;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
}
.vs-action-card-desc {
  font-size: 0.85rem;
  color: #64748b;
  margin: 4px 0 0;
  line-height: 1.35;
}

/* Specific Card themes */
.vs-card-reserva { border-left: 5px solid #3b82f6; }
.vs-card-reserva .vs-action-card-icon { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.15); }
.vs-card-reserva:hover { border-color: #3b82f6; box-shadow: 0 15px 30px -10px rgba(59, 130, 246, 0.2), 0 4px 6px -2px rgba(59, 130, 246, 0.05); }

.vs-card-incidente { border-left: 5px solid #f59e0b; }
.vs-card-incidente .vs-action-card-icon { background: linear-gradient(135deg, #fffbeb, #fef3c7); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.15); }
.vs-card-incidente:hover { border-color: #f59e0b; box-shadow: 0 15px 30px -10px rgba(245, 158, 11, 0.2), 0 4px 6px -2px rgba(245, 158, 11, 0.05); }

.vs-card-motorista { border-left: 5px solid #10b981; }
.vs-card-motorista .vs-action-card-icon { background: linear-gradient(135deg, #f0fdf4, #dcfce7); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.15); }
.vs-card-motorista:hover { border-color: #10b981; box-shadow: 0 15px 30px -10px rgba(16, 185, 129, 0.2), 0 4px 6px -2px rgba(16, 185, 129, 0.05); }

.vs-portal-content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
  gap: 24px;
  margin-bottom: 28px;
}

.vs-empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
  color: #64748b;
}
.vs-empty-icon {
  font-size: 3rem;
  margin-bottom: 12px;
  opacity: 0.5;
  color: #94a3b8;
}
.vs-empty-text {
  font-size: 0.95rem;
  font-weight: 700;
  margin: 0;
  color: #475569;
}
.vs-empty-desc {
  font-size: 0.82rem;
  color: #94a3b8;
  margin: 4px 0 0;
}
.vs-empty-action {
  margin-top: 14px;
  font-size: 0.85rem;
  color: #3b82f6;
  font-weight: 700;
  text-decoration: none !important;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  transition: opacity 0.2s;
}
.vs-empty-action:hover {
  opacity: 0.85;
}

.vs-row-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  background: #f8fafc;
  margin-bottom: 12px;
}
.vs-row-item:last-child {
  margin-bottom: 0;
}
.vs-row-item:hover {
  background: #fff;
  box-shadow: 0 8px 16px rgba(15, 23, 42, 0.05);
  transform: translateY(-2px);
}

.vs-row-reserva {
  border-left: 4px solid #3b82f6;
}
.vs-row-reserva.status-1 { border-left-color: #f59e0b; }
.vs-row-reserva.status-2 { border-left-color: #10b981; }
.vs-row-reserva.status-3 { border-left-color: #ef4444; }
.vs-row-reserva.status-4 { border-left-color: #64748b; }
.vs-row-reserva:hover { border-color: rgba(59, 130, 246, 0.2) rgba(226, 232, 240, 0.8) rgba(226, 232, 240, 0.8) !important; }

.vs-row-incidente {
  border-left: 4px solid #ef4444;
}
.vs-row-incidente.status-1 { border-left-color: #ef4444; }
.vs-row-incidente.status-2 { border-left-color: #f59e0b; }
.vs-row-incidente.status-3 { border-left-color: #10b981; }
.vs-row-incidente.status-4 { border-left-color: #64748b; }
.vs-row-incidente:hover { border-color: rgba(239, 68, 68, 0.2) rgba(226, 232, 240, 0.8) rgba(226, 232, 240, 0.8) !important; }

.vs-item-details {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.vs-item-title {
  font-size: 0.95rem;
  font-weight: 750;
  color: #1e293b;
  text-decoration: none !important;
}
.vs-item-title:hover {
  color: #3b82f6;
}
.vs-item-sub {
  font-size: 0.8rem;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 12px;
}
.vs-item-sub span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
</style>

<div class="vs-app-view" style="max-width: 1250px; margin: 0 auto; padding: 0 4px;">

  <!-- Welcome Banner -->
  <div class="vs-portal-welcome">
    <div>
      <h1>Olá, <?= htmlspecialchars($user_name) ?>!</h1>
      <p>Gerencie seus agendamentos de veículos corporativos e incidentes de forma simples e rápida.</p>
    </div>
    <div class="vs-portal-stats">
      <div class="vs-portal-stat-badge">
        <div class="vs-portal-stat-val"><?= $active_reservations_count ?></div>
        <div class="vs-portal-stat-lbl">Reservas Ativas</div>
      </div>
      <div class="vs-portal-stat-badge">
        <div class="vs-portal-stat-val"><?= $open_incidents_count ?></div>
        <div class="vs-portal-stat-lbl">Incidentes Abertos</div>
      </div>
    </div>
  </div>

  <!-- Quick Actions Grid -->
  <div class="vs-portal-actions-grid">
    <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.form.php" class="vs-action-card vs-card-reserva">
      <div class="vs-action-card-icon"><i class="ti ti-calendar-plus"></i></div>
      <div class="vs-action-card-content">
        <h3 class="vs-action-card-title">Solicitar Reserva</h3>
        <p class="vs-action-card-desc">Agende um carro para sua próxima viagem corporativa.</p>
      </div>
    </a>
    <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/incident.form.php" class="vs-action-card vs-card-incidente">
      <div class="vs-action-card-icon"><i class="ti ti-alert-triangle"></i></div>
      <div class="vs-action-card-content">
        <h3 class="vs-action-card-title">Reportar Incidente</h3>
        <p class="vs-action-card-desc">Registre panes, sinistros ou avarias ocorridas no veículo.</p>
      </div>
    </a>
    <?php if (PluginVehicleschedulerProfile::canViewManagement()): ?>
    <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/driver.form.php" class="vs-action-card vs-card-motorista">
      <div class="vs-action-card-icon"><i class="ti ti-steering-wheel"></i></div>
      <div class="vs-action-card-content">
        <h3 class="vs-action-card-title">Perfil do Condutor</h3>
        <p class="vs-action-card-desc">Cadastre ou atualize seus dados de CNH para dirigir.</p>
      </div>
    </a>
    <?php endif; ?>
  </div>

  <!-- Content Grid: My Reservations / My Incidents -->
  <div class="vs-portal-content-grid">
    
    <!-- My Reservations -->
    <div class="vs-card" style="padding: 24px;">
      <div class="vs-card-header" style="margin-bottom: 18px; padding-bottom: 12px;">
        <h3><i class="ti ti-calendar-event" style="color: #3b82f6;"></i> Minhas Reservas</h3>
        <?php
        $history_url = PluginVehicleschedulerProfile::canViewManagement() 
            ? Plugin::getWebDir('vehiclescheduler') . '/front/schedule.php' 
            : Plugin::getWebDir('vehiclescheduler') . '/front/pages/requester_list.php';
        ?>
        <a href="<?= $history_url ?>" style="font-size: 0.8rem; color: #3b82f6; text-decoration: none; font-weight: 700;">Ver histórico &rarr;</a>
      </div>
      
      <?php if (empty($my_schedules)): ?>
        <div class="vs-empty-state">
          <div class="vs-empty-icon"><i class="ti ti-calendar-x"></i></div>
          <p class="vs-empty-text">Nenhuma reserva ativa</p>
          <p class="vs-empty-desc">Você ainda não realizou solicitações de veículos.</p>
          <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.form.php" class="vs-empty-action">
            <i class="ti ti-plus"></i> Solicitar Primeira Reserva
          </a>
        </div>
      <?php else: ?>
        <div style="max-height: 480px; overflow-y: auto; padding-right: 2px;">
          <?php foreach ($my_schedules as $s):
            $st_map = [1 => 'vs-badge-blue', 2 => 'vs-badge-green', 3 => 'vs-badge-red', 4 => 'vs-badge-gray'];
            $st_class = $st_map[$s['status']] ?? 'vs-badge-blue';
            $st_label = $sch_statuses[$s['status']] ?? '?';
            
            $begin_dt = date('d/m/Y', strtotime($s['begin_date']));
            $end_dt = date('d/m/Y', strtotime($s['end_date']));
            $period = ($begin_dt === $end_dt) ? $begin_dt : "De {$begin_dt} a {$end_dt}";
          ?>
            <div class="vs-row-item vs-row-reserva status-<?= $s['status'] ?>">
              <div class="vs-item-details">
                <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.form.php?id=<?= $s['id'] ?>" class="vs-item-title">
                  <?= vs_vehicle_label($s['plugin_vehiclescheduler_vehicles_id']) ?>
                </a>
                <div class="vs-item-sub">
                  <span><i class="ti ti-calendar"></i> <?= $period ?></span>
                  <span><i class="ti ti-map-pin"></i> <?= htmlspecialchars($s['destination']) ?></span>
                </div>
              </div>
              <div>
                <span class="vs-badge <?= $st_class ?>"><?= $st_label ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- My Incident Reports -->
    <div class="vs-card" style="padding: 24px;">
      <div class="vs-card-header" style="margin-bottom: 18px; padding-bottom: 12px;">
        <h3><i class="ti ti-alert-triangle" style="color: #f59e0b;"></i> Meus Incidentes</h3>
        <?php if (PluginVehicleschedulerProfile::canViewManagement()): ?>
          <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/incident.php" style="font-size: 0.8rem; color: #f59e0b; text-decoration: none; font-weight: 700;">Ver histórico &rarr;</a>
        <?php endif; ?>
      </div>
      
      <?php if (empty($my_incidents)): ?>
        <div class="vs-empty-state">
          <div class="vs-empty-icon"><i class="ti ti-circle-check" style="color: #10b981; opacity: 0.7;"></i></div>
          <p class="vs-empty-text">Tudo em ordem!</p>
          <p class="vs-empty-desc">Nenhum incidente registrado sob sua responsabilidade.</p>
        </div>
      <?php else: ?>
        <div style="max-height: 480px; overflow-y: auto; padding-right: 2px;">
          <?php foreach ($my_incidents as $inc):
            $st_map = [1 => 'vs-badge-red', 2 => 'vs-badge-yellow', 3 => 'vs-badge-green', 4 => 'vs-badge-gray'];
            $st_class = $st_map[$inc['status']] ?? 'vs-badge-red';
            $st_label = $inc_statuses[$inc['status']] ?? '?';
            $inc_date = date('d/m/Y H:i', strtotime($inc['incident_date']));
            $type_label = $inc_types[$inc['incident_type']] ?? 'Incidente';
          ?>
            <div class="vs-row-item vs-row-incidente status-<?= $inc['status'] ?>">
              <div class="vs-item-details">
                <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/incident.form.php?id=<?= $inc['id'] ?>" class="vs-item-title">
                  <?= $type_label ?> — <?= vs_vehicle_label($inc['plugin_vehiclescheduler_vehicles_id']) ?>
                </a>
                <div class="vs-item-sub">
                  <span><i class="ti ti-clock"></i> <?= $inc_date ?></span>
                </div>
              </div>
              <div>
                <span class="vs-badge <?= $st_class ?>"><?= $st_label ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

</div><!-- .vs-app-view -->

<script>
(function() {
    function applyTabBackground() {
        const view = document.querySelector('.vs-app-view');
        if (view) {
            let parent = view.parentElement;
            while (parent && !parent.classList.contains('tab-content') && parent.tagName !== 'BODY') {
                parent.style.setProperty('background', '#f1f5f9', 'important');
                parent.style.setProperty('background-color', '#f1f5f9', 'important');
                if (parent.classList.contains('card') || parent.classList.contains('tab_cadre_fixe') || parent.classList.contains('card-body')) {
                    parent.style.setProperty('border', 'none', 'important');
                    parent.style.setProperty('box-shadow', 'none', 'important');
                }
                parent = parent.parentElement;
            }
        }
    }
    applyTabBackground();
    setTimeout(applyTabBackground, 50);
    setTimeout(applyTabBackground, 200);
})();
</script>

<?php
if (!$is_tab) {
    Html::footer();
}
?>
