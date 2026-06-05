<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Booking Portal — Rent-a-car style interface with calendar
 * Inspired by Localiza, Kayak, RentCar
 */
include('../../../inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', READ);

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

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

Html::header(__('Fleet Reservation', 'vehiclescheduler'), $_SERVER['PHP_SELF'], 'plugins', 'menui', 'reservation');
?>
<style>
.vbk{font-family:inherit;padding:0 8px;max-width:1400px;margin:0 auto;}
.vbk-hero{background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%);color:#fff;border-radius:14px;padding:28px 32px;margin-bottom:24px;}
.vbk-hero h1{margin:0;font-size:1.8rem;font-weight:700;}
.vbk-hero p{margin:8px 0 0;opacity:.9;font-size:.95rem;}
.vbk-tabs{display:flex;gap:8px;margin-bottom:20px;border-bottom:2px solid #e2e8f0;}
.vbk-tab{padding:10px 20px;background:none;border:none;border-bottom:3px solid transparent;cursor:pointer;font-weight:600;font-size:.88rem;color:#64748b;transition:all .2s;}
.vbk-tab.active{color:#3b82f6;border-bottom-color:#3b82f6;}
.vbk-tab:hover{color:#1e40af;}
.vbk-filters{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;gap:14px;align-items:center;flex-wrap:wrap;}
.vbk-filters label{font-size:.82rem;font-weight:600;color:#475569;margin-right:6px;}
.vbk-filters select,.vbk-filters input{padding:6px 12px;border:1px solid #cbd5e1;border-radius:6px;font-size:.85rem;}
.vbk-cal-nav{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.vbk-cal-nav h2{font-size:1.3rem;margin:0;}
.vbk-cal-nav button{background:#f1f5f9;border:1px solid #cbd5e1;padding:6px 14px;border-radius:6px;cursor:pointer;font-weight:600;}
.vbk-cal-nav button:hover{background:#e2e8f0;}
.vbk-grid{display:grid;grid-template-columns:200px 1fr;gap:18px;}
.vbk-vehicles{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:12px;overflow-y:auto;max-height:700px;}
.vbk-vehicle-item{padding:10px;border-radius:8px;cursor:pointer;margin-bottom:6px;transition:background .2s;}
.vbk-vehicle-item:hover{background:#f8fafc;}
.vbk-vehicle-item.selected{background:#dbeafe;border:2px solid #3b82f6;}
.vbk-vehicle-name{font-weight:600;font-size:.86rem;}
.vbk-vehicle-plate{font-size:.74rem;color:#64748b;}
.vbk-calendar{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;}
.vbk-cal-table{width:100%;border-collapse:collapse;font-size:.8rem;}
.vbk-cal-table th{background:#f8fafc;padding:10px 8px;text-align:center;color:#64748b;font-weight:600;font-size:.72rem;text-transform:uppercase;border:1px solid #e2e8f0;}
.vbk-cal-table td{padding:8px;border:1px solid #e2e8f0;vertical-align:top;min-width:50px;height:60px;position:relative;}
.vbk-day-number{font-size:.75rem;color:#64748b;font-weight:600;}
.vbk-event{background:#fef3c7;border-left:3px solid #f59e0b;padding:2px 4px;margin-top:4px;font-size:.7rem;border-radius:3px;cursor:pointer;}
.vbk-event.approved{background:#dcfce7;border-left-color:#22c55e;}
.vbk-event.new{background:#dbeafe;border-left-color:#3b82f6;}
.vbk-event:hover{opacity:.8;}
.vbk-cta{position:fixed;bottom:24px;right:24px;background:#3b82f6;color:#fff;padding:14px 24px;border-radius:50px;font-weight:700;box-shadow:0 4px 12px rgba(59,130,246,.4);cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:8px;}
.vbk-cta:hover{background:#2563eb;color:#fff;}
@media(max-width:900px){.vbk-grid{grid-template-columns:1fr;}}
</style>

<div class="vbk">

<!-- Hero -->
<div class="vbk-hero">
  <h1>🚗 <?= __('Fleet Reservation', 'vehiclescheduler') ?></h1>
  <p><?= __('Select a vehicle and period to make your reservation. Manage your trips efficiently.', 'vehiclescheduler') ?></p>
</div>

<!-- Tabs -->
<div class="vbk-tabs">
  <button class="vbk-tab active" onclick="location.href='booking.php'"><?= __('Calendar View', 'vehiclescheduler') ?></button>
  <button class="vbk-tab" onclick="location.href='portal.php'"><?= __('My Reservations', 'vehiclescheduler') ?></button>
</div>

<!-- Calendar Navigation -->
<div class="vbk-cal-nav">
  <h2><?= strftime('%B %Y', strtotime($month_start)) ?></h2>
  <div>
    <?php
    $prev_m = $current_month - 1; $prev_y = $current_year;
    if ($prev_m < 1) { $prev_m = 12; $prev_y--; }
    $next_m = $current_month + 1; $next_y = $current_year;
    if ($next_m > 12) { $next_m = 1; $next_y++; }
    ?>
    <button onclick="location.href='?month=<?= $prev_m ?>&year=<?= $prev_y ?>'">&larr; <?= __('Previous', 'vehiclescheduler') ?></button>
    <button onclick="location.href='?month=<?= date('n') ?>&year=<?= date('Y') ?>'"><?= __('Today', 'vehiclescheduler') ?></button>
    <button onclick="location.href='?month=<?= $next_m ?>&year=<?= $next_y ?>'"><?= __('Next', 'vehiclescheduler') ?> &rarr;</button>
  </div>
</div>

<!-- Main Grid: Vehicles + Calendar -->
<div class="vbk-grid">
  
  <!-- Vehicle List -->
  <div class="vbk-vehicles">
    <div style="font-weight:700;margin-bottom:12px;color:#1e293b;"><?= __('Available Vehicles', 'vehiclescheduler') ?></div>
    <?php if (empty($vehicles)): ?>
      <p style="color:#94a3b8;font-size:.85rem;"><?= __('No vehicles available', 'vehiclescheduler') ?></p>
    <?php else: ?>
      <?php foreach ($vehicles as $v): ?>
        <div class="vbk-vehicle-item" data-vehicle-id="<?= $v['id'] ?>">
          <div class="vbk-vehicle-name"><?= htmlspecialchars($v['name']) ?></div>
          <div class="vbk-vehicle-plate"><?= htmlspecialchars($v['plate']) ?> • <?= $v['seats'] ?> <?= __('seats', 'vehiclescheduler') ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Calendar -->
  <div class="vbk-calendar">
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
            echo "<td></td>";
          elseif ($day > $days_in_month):
            echo "<td></td>";
          else:
            $date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
            echo "<td>";
            echo "<div class='vbk-day-number'>$day</div>";
            // Show events for this day (all vehicles combined for now)
            foreach ($reservations as $r):
              $bd = substr($r['begin_date'], 0, 10);
              $ed = substr($r['end_date'], 0, 10);
              if ($date >= $bd && $date <= $ed):
                $st_class = $r['status'] == 2 ? 'approved' : 'new';
                echo "<div class='vbk-event $st_class' title='" . htmlspecialchars($r['name']) . "'>";
                $veh = array_values(array_filter($vehicles, fn($x) => $x['id'] == $r['plugin_vehiclescheduler_vehicles_id']))[0] ?? null;
                echo $veh ? htmlspecialchars(substr($veh['name'], 0, 12)) : '#' . $r['plugin_vehiclescheduler_vehicles_id'];
                echo "</div>";
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
<a href="schedule.form.php" class="vbk-cta">
  <i class="ti ti-calendar-plus"></i> <?= __('New Reservation', 'vehiclescheduler') ?>
</a>

</div><!-- .vbk -->
<?php Html::footer(); ?>
