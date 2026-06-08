<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Booking Portal — Rent-a-car style interface with calendar
 * Inspired by Localiza, Kayak, RentCar
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
}

include_once(__DIR__ . '/../../inc/helpers/common.inc.php');

global $DB;
$current_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$current_year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

// Get all active vehicles
$vehicles = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_vehicles',
    'WHERE' => ['is_active' => 1],
    'ORDER' => ['name ASC'],
]));

// Get reservations for current month
$month_start = sprintf('%04d-%02d-01', $current_year, $current_month);
$month_end   = date('Y-m-t', strtotime($month_start));
$reservations = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => [
        'OR' => [
            ['begin_date' => ['>=', $month_start], 'begin_date' => ['<=', $month_end]],
            ['end_date'   => ['>=', $month_start], 'end_date'   => ['<=', $month_end]],
            ['begin_date' => ['<=', $month_start], 'end_date'   => ['>=', $month_end]],
        ],
    ],
]));

// Build calendar data
$cal_data = [];
foreach ($reservations as $r) {
    $vid = $r['plugin_vehiclescheduler_vehicles_id'];
    if (!isset($cal_data[$vid])) $cal_data[$vid] = [];
    $cal_data[$vid][] = $r;
}

if (!$is_tab) {
    Html::header(__('Fleet Reservation', 'vehiclescheduler'), $_SERVER['PHP_SELF'], 'plugins', 'menui', 'reservation');
    vs_render_navbar('booking');
}
?>
<style>
.vbk-cal-nav{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.vbk-cal-nav h2{font-size:1.3rem;margin:0;color:var(--vs-text);}
.vbk-grid{display:grid;grid-template-columns:250px 1fr;gap:18px;}
.vbk-vehicle-item{padding:12px;border-radius:8px;cursor:pointer;margin-bottom:8px;transition:background .2s;border:1px solid transparent;}
.vbk-vehicle-item:hover{background:var(--vs-bg);border-color:var(--vs-border);}
.vbk-vehicle-item.selected{background:#dbeafe;border:1px solid #3b82f6;}
.vbk-cal-table{width:100%;border-collapse:collapse;font-size:.8rem;}
.vbk-cal-table th{background:var(--vs-bg);padding:10px 8px;text-align:center;color:var(--vs-text-light);font-weight:600;font-size:.75rem;text-transform:uppercase;border:1px solid var(--vs-border);}
.vbk-cal-table td{padding:8px;border:1px solid var(--vs-border);vertical-align:top;min-width:50px;height:70px;position:relative;background:#fff;}
.vbk-day-number{font-size:.8rem;color:var(--vs-text-light);font-weight:600;margin-bottom:4px;}
.vbk-event{padding:4px 6px;margin-top:4px;font-size:.75rem;border-radius:4px;cursor:pointer;font-weight:600;display:block;}
.vbk-event.approved{background:#dcfce7;color:#166534;}
.vbk-event.new{background:#fef3c7;color:#92400e;}
.vbk-event:hover{opacity:.8;}
@media(max-width:900px){.vbk-grid{grid-template-columns:1fr;}}
</style>

<div class="vs-app-view" style="max-width:1400px;margin:0 auto;padding:0 8px;">

<!-- Calendar Navigation -->
<div class="vs-card" style="margin-bottom:20px;">
  <div class="vbk-cal-nav">
    <h2><?= strftime('%B %Y', strtotime($month_start)) ?></h2>
    <div style="display:flex; gap:8px;">
      <?php
      $prev_m = $current_month - 1; $prev_y = $current_year;
      if ($prev_m < 1) { $prev_m = 12; $prev_y--; }
      $next_m = $current_month + 1; $next_y = $current_year;
      if ($next_m > 12) { $next_m = 1; $next_y++; }
      ?>
      <button onclick="location.href='?month=<?= $prev_m ?>&year=<?= $prev_y ?>'" class="vs-btn vs-btn-light">&larr; <?= __('Previous', 'vehiclescheduler') ?></button>
      <button onclick="location.href='?month=<?= date('n') ?>&year=<?= date('Y') ?>'" class="vs-btn vs-btn-light"><?= __('Today', 'vehiclescheduler') ?></button>
      <button onclick="location.href='?month=<?= $next_m ?>&year=<?= $next_y ?>'" class="vs-btn vs-btn-light"><?= __('Next', 'vehiclescheduler') ?> &rarr;</button>
    </div>
  </div>
</div>

<!-- Main Grid: Vehicles + Calendar -->
<div class="vbk-grid">
  
  <!-- Vehicle List -->
  <div class="vs-card" style="overflow-y:auto;max-height:700px;">
    <div style="font-weight:700;font-size:1.1rem;margin-bottom:16px;color:var(--vs-text);"><i class="ti ti-car"></i> <?= __('Available Vehicles', 'vehiclescheduler') ?></div>
    <?php if (empty($vehicles)): ?>
      <p style="color:var(--vs-text-light);font-size:.9rem;"><?= __('No vehicles available', 'vehiclescheduler') ?></p>
    <?php else: ?>
      <?php foreach ($vehicles as $v): ?>
        <div class="vbk-vehicle-item" data-vehicle-id="<?= $v['id'] ?>">
          <div style="font-weight:700;font-size:.95rem;color:var(--vs-text);"><?= htmlspecialchars($v['name']) ?></div>
          <div style="font-size:.8rem;color:var(--vs-text-light);"><?= htmlspecialchars($v['plate']) ?> • <?= $v['seats'] ?> <?= __('seats', 'vehiclescheduler') ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Calendar -->
  <div class="vs-card" style="padding:0; overflow:hidden;">
    <table class="vbk-cal-table">
      <tr>
        <?php foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $d): ?>
          <th><?= $d ?></th>
        <?php endforeach; ?>
      </tr>
      <?php
      $first_day = (int)date('w', strtotime($month_start));
      $days_in_month = (int)date('t', strtotime($month_start));
      $day = 1;
      for ($week = 0; $week < 6; $week++):
        if ($day > $days_in_month) break;
        echo "<tr>";
        for ($dow = 0; $dow < 7; $dow++):
          if ($week === 0 && $dow < $first_day):
            echo "<td style='background:var(--vs-bg)'></td>";
          elseif ($day > $days_in_month):
            echo "<td style='background:var(--vs-bg)'></td>";
          else:
            $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
            echo "<td>";
            echo "<div class='vbk-day-number'>$day</div>";
            // Show events for this day
            foreach ($reservations as $r):
              $bd = substr($r['begin_date'], 0, 10);
              $ed = substr($r['end_date'], 0, 10);
              if ($date >= $bd && $date <= $ed):
                $st_class = $r['status'] == 2 ? 'approved' : 'new';
                $url = Plugin::getWebDir('vehiclescheduler') . '/front/schedule.form.php?id=' . $r['id'];
                echo "<a href='{$url}' class='vbk-event $st_class' data-vehicle-id='{$r['plugin_vehiclescheduler_vehicles_id']}' title='" . htmlspecialchars($r['name']) . "' style='text-decoration:none;'>";
                $veh = array_values(array_filter($vehicles, fn($x) => $x['id'] == $r['plugin_vehiclescheduler_vehicles_id']))[0] ?? null;
                echo $veh ? htmlspecialchars(substr($veh['name'], 0, 12)) : '#' . $r['plugin_vehiclescheduler_vehicles_id'];
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
    </table>
  </div>

</div><!-- .vbk-grid -->

<!-- CTA -->
<a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.form.php" style="position:fixed;bottom:30px;right:30px;background:var(--vs-primary);color:#fff;padding:16px 24px;border-radius:50px;font-weight:700;box-shadow:0 4px 15px rgba(59,130,246,.4);cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:10px;font-size:1.1rem;transition:all .2s;z-index:100;">
  <i class="ti ti-calendar-plus" style="font-size:1.3rem;"></i> <?= __('New Reservation', 'vehiclescheduler') ?>
</a>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const vehicleItems = document.querySelectorAll('.vbk-vehicle-item');
    const events = document.querySelectorAll('.vbk-event');
    
    vehicleItems.forEach(item => {
        item.addEventListener('click', function() {
            const vehicleId = this.getAttribute('data-vehicle-id');
            const isSelected = this.classList.contains('selected');
            
            // Remove selected class from all items
            vehicleItems.forEach(i => i.classList.remove('selected'));
            
            if (isSelected) {
                // Deselecting: show all events
                events.forEach(e => e.style.display = 'block');
            } else {
                // Selecting: highlight this item and filter events
                this.classList.add('selected');
                events.forEach(e => {
                    if (e.getAttribute('data-vehicle-id') == vehicleId) {
                        e.style.display = 'block';
                    } else {
                        e.style.display = 'none';
                    }
                });
            }
        });
    });
});
</script>

</div><!-- .vs-app-view -->
<?php
if (!$is_tab) {
    Html::footer();
}
?>
