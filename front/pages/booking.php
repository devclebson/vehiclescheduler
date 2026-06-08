<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Booking Portal — Rent-a-car style interface with calendar
 */
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 4));
}
include_once(GLPI_ROOT . '/inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', READ);

if (!PluginVehicleschedulerProfile::canAccessRequester() && !PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

$is_tab = isset($_GET['is_tab']) || isset($_POST['is_tab']);

if (!$is_tab) {
    if (!PluginVehicleschedulerProfile::canViewManagement()) {
        Html::displayRightError();
        exit;
    }
}

include_once(__DIR__ . '/../../inc/helpers/common.inc.php');

global $DB;

// Translate months to Portuguese robustly
$months_pt = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$current_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$current_year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

// Get all active vehicles
$vehicles = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_vehicles',
    'WHERE' => ['is_active' => 1],
    'ORDER' => ['name ASC'],
]));

// Helper to render only the calendar card inner contents
function vs_render_calendar_card_inner($current_month, $current_year, $reservations, $vehicles, $months_pt) {
    $month_start = sprintf('%04d-%02d-01', $current_year, $current_month);
    $month_title = $months_pt[$current_month] . ' de ' . $current_year;
    
    $prev_m = $current_month - 1; $prev_y = $current_year;
    if ($prev_m < 1) { $prev_m = 12; $prev_y--; }
    $next_m = $current_month + 1; $next_y = $current_year;
    if ($next_m > 12) { $next_m = 1; $next_y++; }
    ?>
      <!-- Calendar Header Bar -->
      <div class="vbk-cal-header-bar">
        <h2 class="vbk-cal-title">
          <i class="ti ti-calendar" style="color: #3b82f6;"></i> <?= $month_title ?>
        </h2>
        <div style="display:flex; gap:8px;">
          <button type="button" class="vbk-btn vbk-nav-btn" data-month="<?= $prev_m ?>" data-year="<?= $prev_y ?>">
            <i class="ti ti-chevron-left"></i> Ant
          </button>
          <button type="button" class="vbk-btn vbk-nav-btn" data-month="<?= date('n') ?>" data-year="<?= date('Y') ?>">
            Hoje
          </button>
          <button type="button" class="vbk-btn vbk-nav-btn" data-month="<?= $next_m ?>" data-year="<?= $next_y ?>">
            Próx <i class="ti ti-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Active Filter Banner -->
      <div id="filter-banner" style="display:none; align-items:center; justify-content:space-between; background:#eff6ff; border-bottom:1px solid #bfdbfe; padding:10px 24px; font-size:0.85rem; color:#1e40af; font-weight:700;">
        <span><i class="ti ti-info-circle"></i> Filtrando reservas do veículo: <span id="filtered-vehicle-lbl" style="text-decoration: underline;"></span></span>
        <button type="button" id="clear-filter-btn" class="vbk-btn" style="padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; color: #1e40af !important; border-color: #bfdbfe;">
          <i class="ti ti-x"></i> Limpar Filtro
        </button>
      </div>

      <!-- Legend Bar -->
      <div class="vbk-legend-bar">
        <div class="vbk-legend-item">
          <span class="vbk-legend-dot" style="background:#22c55e;"></span> Reservas Aprovadas
        </div>
        <div class="vbk-legend-item">
          <span class="vbk-legend-dot" style="background:#eab308;"></span> Aguardando Aprovação
        </div>
      </div>

      <!-- Calendar Grid Table -->
      <table class="vbk-table-grid">
        <thead>
          <tr>
            <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $d): ?>
              <th><?= $d ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $first_day = (int)date('w', strtotime($month_start));
          $days_in_month = (int)date('t', strtotime($month_start));
          $today_date = date('Y-m-d');
          $day = 1;
          
          for ($week = 0; $week < 6; $week++):
            if ($day > $days_in_month) break;
            echo "<tr>";
            for ($dow = 0; $dow < 7; $dow++):
              if ($week === 0 && $dow < $first_day):
                echo "<td class='weekend'></td>";
              elseif ($day > $days_in_month):
                echo "<td class='weekend'></td>";
              else:
                $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                
                $is_today = ($date === $today_date);
                $is_weekend = ($dow === 0 || $dow === 6);
                
                $class_list = [];
                if ($is_today) $class_list[] = 'today';
                if ($is_weekend) $class_list[] = 'weekend';
                $class_str = implode(' ', $class_list);
                
                echo "<td class='{$class_str}' data-date='{$date}' style='cursor:pointer;'>";
                echo "<div class='vbk-day-num'>$day</div>";
                
                // Show events for this day
                foreach ($reservations as $r):
                  $bd = substr($r['begin_date'], 0, 10);
                  $ed = substr($r['end_date'], 0, 10);
                  if ($date >= $bd && $date <= $ed):
                    $st_class = $r['status'] == 2 ? 'approved' : 'new';
                    $v_name = '—';
                    foreach ($vehicles as $veh) {
                      if ($veh['id'] == $r['plugin_vehiclescheduler_vehicles_id']) {
                        $v_name = $veh['name'];
                        break;
                      }
                    }
                    echo "<a href='#' class='vbk-event-link $st_class' data-res-id='{$r['id']}' data-vehicle-id='{$r['plugin_vehiclescheduler_vehicles_id']}' title='" . htmlspecialchars($r['name']) . "'>";
                    echo "<i class='ti ti-key' style='font-size:0.65rem; margin-right:2px;'></i> " . htmlspecialchars($v_name);
                    echo "</a>";
                  endif;
                endforeach;
                
                echo "</td>";
                $day++;
              endif;
            endfor;
            echo "</tr>";
          endfor;
          ?>
        </tbody>
      </table>
    <?php
}

// Handle quick actions (approve/refuse/cancel) from the calendar modal
if (isset($_POST['quick_action']) && isset($_POST['id'])) {
    $schedule = new PluginVehicleschedulerSchedule();
    if ($schedule->getFromDB($_POST['id'])) {
        $is_owner = ($schedule->fields['users_id'] == Session::getLoginUserID());
        $is_manager = Session::haveRight('plugin_vehiclescheduler', UPDATE);
        
        if ($is_manager || $is_owner) {
            $action = $_POST['quick_action'];
            $update_data = ['id' => $_POST['id']];
            $msg = '';
            
            if ($action == 'approve' && $is_manager) {
                $update_data['status'] = 2; // Approved
                $update_data['approver_users_id'] = Session::getLoginUserID();
                $msg = 'Reserva aprovada com sucesso!';
            } elseif ($action == 'refuse' && $is_manager) {
                $update_data['status'] = 3; // Rejected
                $update_data['approver_users_id'] = Session::getLoginUserID();
                if (isset($_POST['comment'])) {
                    $update_data['comment'] = $_POST['comment'];
                }
                $msg = 'Reserva recusada com sucesso.';
            } elseif ($action == 'cancel') {
                $update_data['status'] = 4; // Cancelled
                $msg = 'Reserva cancelada com sucesso.';
            }
            
            if (isset($update_data['status'])) {
                $schedule->update($update_data);
                Session::addMessageAfterRedirect($msg, false, INFO);
            }
        }
    }
    $redirect_url = Plugin::getWebDir('vehiclescheduler') . '/front/index.php';
    Html::redirect($redirect_url);
}

// Get reservations for current month
$month_start = sprintf('%04d-%02d-01', $current_year, $current_month);
$month_end   = date('Y-m-t', strtotime($month_start));
$reservations = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => [
        'status' => [1, 2], // Only pending/approved
        'OR' => [
            ['begin_date' => ['>=', $month_start], 'begin_date' => ['<=', $month_end]],
            ['end_date'   => ['>=', $month_start], 'end_date'   => ['<=', $month_end]],
            ['begin_date' => ['<=', $month_start], 'end_date'   => ['>=', $month_end]],
        ],
    ],
]));

// AJAX fetch endpoint
if (isset($_GET['action']) && $_GET['action'] == 'fetch_month') {
    header('Content-Type: application/json');
    $sch_statuses = PluginVehicleschedulerSchedule::getAllStatus();
    $js_res = [];
    foreach ($reservations as $r) {
        $u = new User();
        $u_name = $u->getFromDB($r['users_id']) ? $u->getName() : 'Desconhecido';
        
        $v_name = '—';
        $v_plate = '';
        foreach ($vehicles as $veh) {
            if ($veh['id'] == $r['plugin_vehiclescheduler_vehicles_id']) {
                $v_name = $veh['name'];
                $v_plate = $veh['plate'];
                break;
            }
        }
        
        $d_name = '—';
        if ($r['plugin_vehiclescheduler_drivers_id'] > 0) {
            $d = new PluginVehicleschedulerDriver();
            $d_name = $d->getFromDB($r['plugin_vehiclescheduler_drivers_id']) ? $d->fields['name'] : '—';
        }

        $js_res[] = [
            'id'          => $r['id'],
            'name'        => $r['name'],
            'vehicle_id'  => $r['plugin_vehiclescheduler_vehicles_id'],
            'vehicle_name'=> $v_name . ($v_plate ? " ($v_plate)" : ""),
            'driver_name' => $d_name,
            'requester'   => $u_name,
            'users_id'    => $r['users_id'],
            'begin_date'  => substr($r['begin_date'], 0, 10),
            'begin_time'  => substr($r['begin_date'], 11, 5),
            'end_date'    => substr($r['end_date'], 0, 10),
            'end_time'    => substr($r['end_date'], 11, 5),
            'destination' => $r['destination'],
            'passengers'  => $r['passengers'],
            'purpose'     => $r['purpose'],
            'status'      => $r['status'],
            'status_lbl'  => $sch_statuses[$r['status']] ?? '?'
        ];
    }

    ob_start();
    vs_render_calendar_card_inner($current_month, $current_year, $reservations, $vehicles, $months_pt);
    $html = ob_get_clean();

    echo json_encode([
        'success'      => true,
        'month_title'  => $months_pt[$current_month] . ' de ' . $current_year,
        'reservations' => $js_res,
        'html'         => $html
    ]);
    exit;
}

if (!$is_tab) {
    Html::header(__('Fleet Reservation', 'vehiclescheduler'), $_SERVER['PHP_SELF'], 'plugins', 'menui', 'reservation');
    vs_render_navbar('booking');
}

$sch_statuses = PluginVehicleschedulerSchedule::getAllStatus();
$js_reservations = [];
foreach ($reservations as $r) {
    $u = new User();
    $u_name = $u->getFromDB($r['users_id']) ? $u->getName() : 'Desconhecido';
    
    $v_name = '—';
    $v_plate = '';
    foreach ($vehicles as $veh) {
        if ($veh['id'] == $r['plugin_vehiclescheduler_vehicles_id']) {
            $v_name = $veh['name'];
            $v_plate = $veh['plate'];
            break;
        }
    }
    
    $d_name = '—';
    if ($r['plugin_vehiclescheduler_drivers_id'] > 0) {
        $d = new PluginVehicleschedulerDriver();
        $d_name = $d->getFromDB($r['plugin_vehiclescheduler_drivers_id']) ? $d->fields['name'] : '—';
    }

    $js_reservations[] = [
        'id'          => $r['id'],
        'name'        => $r['name'],
        'vehicle_id'  => $r['plugin_vehiclescheduler_vehicles_id'],
        'vehicle_name'=> $v_name . ($v_plate ? " ($v_plate)" : ""),
        'driver_name' => $d_name,
        'requester'   => $u_name,
        'users_id'    => $r['users_id'],
        'begin_date'  => substr($r['begin_date'], 0, 10),
        'begin_time'  => substr($r['begin_date'], 11, 5),
        'end_date'    => substr($r['end_date'], 0, 10),
        'end_time'    => substr($r['end_date'], 11, 5),
        'destination' => $r['destination'],
        'passengers'  => $r['passengers'],
        'purpose'     => $r['purpose'],
        'status'      => $r['status'],
        'status_lbl'  => $sch_statuses[$r['status']] ?? '?'
    ];
}
?>

<style>
.vbk-wrapper {
  max-width: 1350px;
  margin: 0 auto;
}
.vbk-grid {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 24px;
}
.vbk-sidebar {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  padding: 24px;
  box-shadow: 0 10px 25px -5px rgba(15,23,42,0.04), 0 8px 16px -6px rgba(15,23,42,0.05);
  max-height: 800px;
  display: flex;
  flex-direction: column;
}
.vbk-sidebar-title {
  font-size: 1.1rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.vbk-vehicle-list {
  overflow-y: auto;
  flex: 1;
  padding-right: 4px;
}
.vbk-vehicle-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 16px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  cursor: pointer;
  margin-bottom: 12px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.vbk-vehicle-card:hover {
  transform: translateY(-2px);
  border-color: #cbd5e1;
  background: #f1f5f9;
}
.vbk-vehicle-card.selected {
  background: #eff6ff;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.vbk-vehicle-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #475569;
  font-size: 1.25rem;
  transition: all 0.2s;
}
.vbk-vehicle-card.selected .vbk-vehicle-icon {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: #fff;
}

/* Status Available / Occupied for sidebar vehicles */
.vbk-vehicle-card.status-available {
  background: linear-gradient(135deg, #f0fdf4, #ffffff) !important;
  border-color: rgba(16, 185, 129, 0.35) !important;
}
.vbk-vehicle-card.status-available:hover {
  border-color: #10b981 !important;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.08);
}
.vbk-vehicle-card.status-available .vbk-vehicle-icon {
  background: linear-gradient(135deg, #dcfce7, #f0fdf4) !important;
  color: #10b981 !important;
}

.vbk-vehicle-card.status-occupied {
  background: linear-gradient(135deg, #fef2f2, #ffffff) !important;
  border-color: rgba(239, 68, 68, 0.35) !important;
}
.vbk-vehicle-card.status-occupied:hover {
  border-color: #ef4444 !important;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.08);
}
.vbk-vehicle-card.status-occupied .vbk-vehicle-icon {
  background: linear-gradient(135deg, #fee2e2, #fef2f2) !important;
  color: #ef4444 !important;
}

.vbk-vehicle-info {
  flex: 1;
}
.vbk-vehicle-name {
  font-size: 0.9rem;
  font-weight: 750;
  color: #1e293b;
  line-height: 1.2;
}
.vbk-vehicle-meta {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 4px;
}

.vbk-calendar-card {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 25px -5px rgba(15,23,42,0.04), 0 8px 16px -6px rgba(15,23,42,0.05);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: opacity 0.2s ease;
}
.vbk-cal-header-bar {
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
}
.vbk-cal-title {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 800;
  color: #1e293b;
  display: flex;
  align-items: center;
  gap: 8px;
}

.vbk-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.85rem;
  text-decoration: none !important;
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #334155 !important;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.vbk-btn:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
  transform: translateY(-1px);
}

.vbk-legend-bar {
  display: flex;
  gap: 16px;
  padding: 12px 24px;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  flex-wrap: wrap;
}
.vbk-legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
}
.vbk-legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.vbk-table-grid {
  width: 100%;
  border-collapse: collapse;
}
.vbk-table-grid th {
  background: #fff;
  padding: 12px 8px;
  text-align: center;
  font-weight: 700;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  border-bottom: 1px solid #e2e8f0;
}
.vbk-table-grid td {
  width: 14.28%;
  height: 115px;
  padding: 8px;
  border-right: 1px solid #e2e8f0;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: top;
  position: relative;
  background: #fff;
  transition: all 0.15s ease;
}
.vbk-table-grid td:last-child {
  border-right: none;
}
.vbk-table-grid tr:last-child td {
  border-bottom: none;
}
.vbk-table-grid td.weekend {
  background: #f8fafc;
}
.vbk-table-grid td.today {
  background: linear-gradient(135deg, #fffdf5, #fffbeb);
  box-shadow: inset 0 0 0 2px #fbbf24 !important;
}
.vbk-table-grid td.selected-day {
  background: linear-gradient(135deg, #eff6ff, #dbeafe) !important;
  box-shadow: inset 0 0 0 3px #2563eb !important;
}
.vbk-day-num {
  font-size: 0.8rem;
  font-weight: 700;
  color: #64748b;
  margin-bottom: 6px;
  text-align: right;
  padding-right: 2px;
}
.vbk-table-grid td.today .vbk-day-num {
  color: #a16207;
}

.vbk-event-link {
  display: block;
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 700;
  margin-bottom: 5px;
  text-decoration: none !important;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid transparent;
  border-left-width: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.vbk-event-link:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.08);
}
.vbk-event-link.approved {
  background: linear-gradient(135deg, #f0fdf4, #dcfce7);
  color: #166534;
  border-color: rgba(16, 185, 129, 0.2);
  border-left-color: #10b981;
}
.vbk-event-link.new {
  background: linear-gradient(135deg, #fffbeb, #fef3c7);
  color: #b45309;
  border-color: rgba(245, 158, 11, 0.2);
  border-left-color: #f59e0b;
}

/* Modal Styling */
.vbk-modal {
  display: none;
  position: fixed;
  z-index: 2000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  align-items: center;
  justify-content: center;
}
.vbk-modal-content {
  background-color: #fff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: vbkFadeIn 0.2s ease-out;
}
@keyframes vbkFadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
.vbk-modal-header {
  padding: 18px 24px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  border-top-left-radius: 16px;
  border-top-right-radius: 16px;
}
.vbk-modal-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 800;
  color: #1e293b;
}
.vbk-modal-close {
  cursor: pointer;
  font-size: 1.4rem;
  color: #64748b;
  border: none;
  background: transparent;
  transition: color 0.2s;
  line-height: 1;
}
.vbk-modal-close:hover {
  color: #1e293b;
}
.vbk-modal-body {
  padding: 24px;
}
.vbk-modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  background: #f8fafc;
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
}
.vbk-detail-row {
  display: flex;
  margin-bottom: 12px;
  font-size: 0.88rem;
}
.vbk-detail-row:last-child {
  margin-bottom: 0;
}
.vbk-detail-label {
  width: 120px;
  font-weight: 700;
  color: #64748b;
}
.vbk-detail-value {
  flex: 1;
  color: #1e293b;
}

@media(max-width:950px) {
  .vbk-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="vs-app-view vbk-wrapper">

  <div class="vbk-grid">
    
    <!-- Sidebar (Vehicle selector) -->
    <div class="vbk-sidebar">
      <div class="vbk-sidebar-title">
        <i class="ti ti-car"></i> Veículos Disponíveis
      </div>
      <div class="vbk-vehicle-list">
        <?php if (empty($vehicles)): ?>
          <p style="color:var(--vs-text-light); font-size:0.9rem; text-align:center; padding:20px;">Nenhum veículo ativo.</p>
        <?php else: ?>
          <?php foreach ($vehicles as $v): ?>
            <div class="vbk-vehicle-card" data-vehicle-id="<?= $v['id'] ?>" data-vehicle-name="<?= htmlspecialchars($v['name']) ?>">
              <div class="vbk-vehicle-icon"><i class="ti ti-car"></i></div>
              <div class="vbk-vehicle-info">
                <div style="display:flex; justify-content:space-between; align-items:center; gap: 8px;">
                  <div class="vbk-vehicle-name"><?= htmlspecialchars($v['name']) ?></div>
                  <span class="vbk-card-status-badge vs-badge" style="display:none; font-size:0.65rem; padding:2px 6px;"></span>
                </div>
                <div class="vbk-vehicle-meta"><?= htmlspecialchars($v['plate']) ?> • <?= $v['seats'] ?> assentos</div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Calendar Wrapper -->
    <div class="vbk-calendar-card">
      <?php
      // Render initial calendar content directly
      vs_render_calendar_card_inner($current_month, $current_year, $reservations, $vehicles, $months_pt);
      ?>
    </div>

  </div><!-- .vbk-grid -->

</div><!-- .vs-app-view -->

<!-- Modal for Reservation Details -->
<div id="res-modal" class="vbk-modal" style="display:none;">
  <div class="vbk-modal-content">
    <div class="vbk-modal-header">
      <h3 id="modal-title">Detalhes da Reserva</h3>
      <button class="vbk-modal-close" onclick="window.closeResModal()">&times;</button>
    </div>
    <div class="vbk-modal-body">
      <div class="vbk-detail-row">
        <div class="vbk-detail-label">Título:</div>
        <div class="vbk-detail-value" id="lbl-title"></div>
      </div>
      <div class="vbk-detail-row">
        <div class="vbk-detail-label">Veículo:</div>
        <div class="vbk-detail-value" id="lbl-vehicle"></div>
      </div>
      <div class="vbk-detail-row">
        <div class="vbk-detail-label">Motorista:</div>
        <div class="vbk-detail-value" id="lbl-driver"></div>
      </div>
      <div class="vbk-detail-row">
        <div class="vbk-detail-label">Solicitante:</div>
        <div class="vbk-detail-value" id="lbl-requester"></div>
      </div>
      <div class="vbk-detail-row">
        <div class="vbk-detail-label">Período:</div>
        <div class="vbk-detail-value" id="lbl-period"></div>
      </div>
      <div class="vbk-detail-row">
        <div class="vbk-detail-label">Destino:</div>
        <div class="vbk-detail-value" id="lbl-destination"></div>
      </div>
      <div class="vbk-detail-row">
        <div class="vbk-detail-label">Passageiros:</div>
        <div class="vbk-detail-value" id="lbl-passengers"></div>
      </div>
      <div class="vbk-detail-row">
        <div class="vbk-detail-label">Objetivo:</div>
        <div class="vbk-detail-value" id="lbl-purpose"></div>
      </div>
      <div class="vbk-detail-row" style="margin-bottom:0;">
        <div class="vbk-detail-label">Status:</div>
        <div class="vbk-detail-value"><span id="lbl-status" class="vs-badge"></span></div>
      </div>
    </div>
    <div class="vbk-modal-footer">
      <button class="vs-btn vs-btn-secondary" onclick="window.closeResModal()" style="font-size:0.85rem; padding:8px 16px;">Fechar</button>
    </div>
  </div>
</div>

<script>
// Unify variables and bind to window object to bypass AJAX scope isolation
window.monthlyReservations = <?= json_encode($js_reservations) ?>;
window.allVehicles = <?= json_encode($vehicles) ?>;
window.baseWebDir = "<?= Plugin::getWebDir('vehiclescheduler') ?>";
window.isManager = <?= PluginVehicleschedulerProfile::canViewManagement() ? 'true' : 'false' ?>;
window.currentUserId = <?= Session::getLoginUserID() ?>;

window.closeResModal = function() {
    document.getElementById('res-modal').style.display = 'none';
};

(function() {
    let activeVehicleId = null;
    let activeDate = null;

    function initEvents() {
        const vehicleCards = document.querySelectorAll('.vbk-vehicle-card');
        const events = document.querySelectorAll('.vbk-event-link');
        const clearFilterBtn = document.getElementById('clear-filter-btn');
        const days = document.querySelectorAll('.vbk-table-grid td[data-date]');
        const navButtons = document.querySelectorAll('.vbk-nav-btn');

        navButtons.forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const m = this.getAttribute('data-month');
                const y = this.getAttribute('data-year');
                fetchMonth(m, y);
            };
        });

        vehicleCards.forEach(card => {
            card.onclick = function() {
                const vehicleId = this.getAttribute('data-vehicle-id');
                const vehicleName = this.getAttribute('data-vehicle-name');
                const isSelected = this.classList.contains('selected');

                if (isSelected) {
                    clearFilter();
                } else {
                    filterEvents(vehicleId, vehicleName);
                }
            };
        });

        if (clearFilterBtn) {
            clearFilterBtn.onclick = clearFilter;
        }

        days.forEach(day => {
            day.onclick = function(e) {
                if (e.target.closest('.vbk-event-link')) return;

                const clickedDate = this.getAttribute('data-date');
                
                if (this.classList.contains('selected-day')) {
                    this.classList.remove('selected-day');
                    activeDate = null;
                    updateVehicleStatusAndSorting(null);
                } else {
                    days.forEach(d => d.classList.remove('selected-day'));
                    this.classList.add('selected-day');
                    activeDate = clickedDate;
                    updateVehicleStatusAndSorting(clickedDate);
                }
            };
        });

        events.forEach(lnk => {
            lnk.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                const resId = this.getAttribute('data-res-id');
                const res = window.monthlyReservations.find(r => r.id == resId);
                
                if (res) {
                    openResModal(res);
                }
            };
        });
    }

    function fetchMonth(month, year) {
        const url = `${window.baseWebDir}/front/pages/booking.php?action=fetch_month&month=${month}&year=${year}&is_tab=1`;
        const container = document.querySelector('.vbk-calendar-card');
        if (container) {
            container.style.opacity = '0.6';
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.monthlyReservations = data.reservations;
                    const wrapper = document.querySelector('.vbk-calendar-card');
                    if (wrapper) {
                        wrapper.innerHTML = data.html;
                    }
                    
                    activeDate = null;
                    activeVehicleId = null;
                    
                    updateVehicleStatusAndSorting(null);
                    initEvents();
                }
            })
            .catch(err => {
                console.error("Erro ao carregar o mês:", err);
            })
            .finally(() => {
                const container = document.querySelector('.vbk-calendar-card');
                if (container) {
                    container.style.opacity = '1';
                }
            });
    }

    function filterEvents(vehicleId, vehicleName) {
        activeVehicleId = vehicleId;
        const vehicleCards = document.querySelectorAll('.vbk-vehicle-card');
        const events = document.querySelectorAll('.vbk-event-link');
        const filterBanner = document.getElementById('filter-banner');
        const filteredVehicleLbl = document.getElementById('filtered-vehicle-lbl');

        vehicleCards.forEach(c => {
            if (c.getAttribute('data-vehicle-id') === vehicleId) {
                c.classList.add('selected');
            } else {
                c.classList.remove('selected');
            }
        });

        events.forEach(e => {
            if (e.getAttribute('data-vehicle-id') === vehicleId) {
                e.style.display = 'block';
            } else {
                e.style.display = 'none';
            }
        });

        if (filteredVehicleLbl) filteredVehicleLbl.textContent = vehicleName;
        if (filterBanner) filterBanner.style.display = 'flex';
    }

    function clearFilter() {
        activeVehicleId = null;
        const vehicleCards = document.querySelectorAll('.vbk-vehicle-card');
        const events = document.querySelectorAll('.vbk-event-link');
        const filterBanner = document.getElementById('filter-banner');

        vehicleCards.forEach(c => c.classList.remove('selected'));
        events.forEach(e => e.style.display = 'block');
        if (filterBanner) filterBanner.style.display = 'none';
    }

    function updateVehicleStatusAndSorting(dateStr) {
        const listContainer = document.querySelector('.vbk-vehicle-list');
        if (!listContainer) return;
        const cards = Array.from(listContainer.querySelectorAll('.vbk-vehicle-card'));

        if (!dateStr) {
            document.querySelectorAll('.vbk-card-status-badge').forEach(b => b.style.display = 'none');
            cards.forEach(c => {
                c.classList.remove('status-available', 'status-occupied');
            });
            cards.sort((a, b) => {
                const aName = a.getAttribute('data-vehicle-name').toLowerCase();
                const bName = b.getAttribute('data-vehicle-name').toLowerCase();
                return aName.localeCompare(bName);
            });
            cards.forEach(card => listContainer.appendChild(card));
            return;
        }

        window.allVehicles.forEach(veh => {
            const isOccupied = window.monthlyReservations.some(res => {
                return res.vehicle_id == veh.id && dateStr >= res.begin_date && dateStr <= res.end_date;
            });

            const card = listContainer.querySelector(`.vbk-vehicle-card[data-vehicle-id="${veh.id}"]`);
            if (card) {
                const badge = card.querySelector('.vbk-card-status-badge');
                if (badge) {
                    badge.style.display = 'inline-block';
                    if (isOccupied) {
                        badge.className = 'vbk-card-status-badge vs-badge vs-badge-red';
                        badge.textContent = 'Ocupado';
                        card.classList.remove('status-available');
                        card.classList.add('status-occupied');
                    } else {
                        badge.className = 'vbk-card-status-badge vs-badge vs-badge-green';
                        badge.textContent = 'Livre';
                        card.classList.remove('status-occupied');
                        card.classList.add('status-available');
                    }
                }
            }
        });

        cards.sort((a, b) => {
            const aId = a.getAttribute('data-vehicle-id');
            const bId = b.getAttribute('data-vehicle-id');
            
            const aOccupied = window.monthlyReservations.some(res => res.vehicle_id == aId && dateStr >= res.begin_date && dateStr <= res.end_date);
            const bOccupied = window.monthlyReservations.some(res => res.vehicle_id == bId && dateStr >= res.begin_date && dateStr <= res.end_date);
            
            if (aOccupied && !bOccupied) return 1;
            if (!aOccupied && bOccupied) return -1;
            
            const aName = a.getAttribute('data-vehicle-name').toLowerCase();
            const bName = b.getAttribute('data-vehicle-name').toLowerCase();
            return aName.localeCompare(bName);
        });

        cards.forEach(card => listContainer.appendChild(card));
    }

    function openResModal(res) {
        document.getElementById('lbl-title').textContent = res.name;
        document.getElementById('lbl-vehicle').textContent = res.vehicle_name;
        document.getElementById('lbl-driver').textContent = res.driver_name;
        document.getElementById('lbl-requester').textContent = res.requester;
        document.getElementById('lbl-period').textContent = `${res.begin_date} (${res.begin_time}) às ${res.end_date} (${res.end_time})`;
        document.getElementById('lbl-destination').textContent = res.destination;
        document.getElementById('lbl-passengers').textContent = res.passengers;
        document.getElementById('lbl-purpose').textContent = res.purpose || '—';
        
        const statusSpan = document.getElementById('lbl-status');
        statusSpan.textContent = res.status_lbl;
        statusSpan.className = 'vs-badge ';
        if (res.status == 2) {
            statusSpan.className += 'vs-badge-green';
        } else if (res.status == 1) {
            statusSpan.className += 'vs-badge-yellow';
        } else if (res.status == 3) {
            statusSpan.className += 'vs-badge-red';
        } else {
            statusSpan.className += 'vs-badge-gray';
        }

        document.getElementById('res-modal').style.display = 'flex';
    }

    initEvents();
    setTimeout(initEvents, 100);
})();
</script>

<?php
if (!$is_tab) {
    Html::footer();
}
?>
