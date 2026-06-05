<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Requester Portal — my reservations, report incident, register driver
 */
include('../../../inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', READ);

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

global $DB;
$my_uid = Session::getLoginUserID();

// My reservations
$my_schedules = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => ['users_id' => $my_uid],
    'ORDER' => ['date_creation DESC'],
    'LIMIT' => 10,
]));

// My incidents
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
.vsp { font-family:inherit; padding:0 4px; }
.vsp-hero { background:linear-gradient(135deg,#1d4ed8 0%,#3b82f6 100%); color:#fff; border-radius:12px; padding:24px 28px; margin-bottom:22px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px; }
.vsp-hero h2 { margin:0; font-size:1.4rem; }
.vsp-hero p  { margin:4px 0 0; opacity:.85; font-size:.9rem; }
.vsp-actions { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:22px; }
.vsp-btn     { display:inline-flex; align-items:center; gap:7px; padding:10px 18px; border-radius:8px; font-size:.84rem; font-weight:600; text-decoration:none; cursor:pointer; border:none; }
.vsp-btn-primary   { background:#3b82f6; color:#fff; }
.vsp-btn-primary:hover { background:#2563eb; color:#fff; }
.vsp-btn-warning   { background:#f59e0b; color:#fff; }
.vsp-btn-warning:hover { background:#d97706; color:#fff; }
.vsp-btn-secondary { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }
.vsp-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.vsp-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden; }
.vsp-card-header { padding:12px 16px; border-bottom:1px solid #f1f5f9; font-weight:600; font-size:.88rem; display:flex; justify-content:space-between; align-items:center; background:#f8fafc; }
.vsp-table { width:100%; border-collapse:collapse; font-size:.82rem; }
.vsp-table th { background:#f8fafc; padding:7px 12px; text-align:left; color:#64748b; font-weight:600; font-size:.73rem; text-transform:uppercase; border-bottom:1px solid #e2e8f0; }
.vsp-table td { padding:8px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.vsp-table tr:last-child td { border-bottom:none; }
.vsp-table tr:hover td { background:#f8fafc; }
.vsp-badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:.7rem; font-weight:600; }
.bg-new{background:#dbeafe;color:#1d4ed8;} .bg-approved{background:#dcfce7;color:#166534;} .bg-rejected{background:#fee2e2;color:#991b1b;} .bg-cancelled{background:#f1f5f9;color:#475569;} .bg-open{background:#fee2e2;color:#991b1b;} .bg-analyzing{background:#fef3c7;color:#92400e;} .bg-resolved{background:#dcfce7;color:#166534;}
.vsp-empty { padding:20px; text-align:center; color:#94a3b8; font-size:.85rem; }
@media(max-width:768px){ .vsp-grid-2 { grid-template-columns:1fr; } }
</style>

<div class="vsp">

<!-- Hero -->
<div class="vsp-hero">
  <div>
    <h2>🚗 <?= __('Fleet Portal', 'vehiclescheduler') ?></h2>
    <p><?= sprintf(__('Welcome, %s. Manage your reservations and report incidents.', 'vehiclescheduler'), htmlspecialchars(getUserName($my_uid))) ?></p>
  </div>
  <a href="/plugins/vehiclescheduler/front/dashboard.php" class="vsp-btn vsp-btn-secondary">
    <i class="ti ti-layout-dashboard"></i> <?= __('Manager Dashboard', 'vehiclescheduler') ?>
  </a>
</div>

<!-- Quick Actions -->
<div class="vsp-actions">
  <a href="/plugins/vehiclescheduler/front/schedule.form.php" class="vsp-btn vsp-btn-primary">
    <i class="ti ti-calendar-plus"></i> <?= __('Request Reservation', 'vehiclescheduler') ?>
  </a>
  <a href="/plugins/vehiclescheduler/front/incident.form.php" class="vsp-btn vsp-btn-warning">
    <i class="ti ti-alert-triangle"></i> <?= __('Report Incident', 'vehiclescheduler') ?>
  </a>
  <a href="/plugins/vehiclescheduler/front/driver.form.php" class="vsp-btn vsp-btn-secondary">
    <i class="ti ti-steering-wheel"></i> <?= __('Register Driver', 'vehiclescheduler') ?>
  </a>
</div>

<!-- Main Grid -->
<div class="vsp-grid-2">

  <!-- My Reservations -->
  <div class="vsp-card">
    <div class="vsp-card-header">
      <span><i class="ti ti-calendar-event"></i> <?= __('My Reservations', 'vehiclescheduler') ?></span>
      <a href="/plugins/vehiclescheduler/front/schedule.php" style="font-size:.75rem;color:#3b82f6;"><?= __('View all', 'vehiclescheduler') ?></a>
    </div>
    <?php if (empty($my_schedules)): ?>
      <div class="vsp-empty"><?= __('You have no reservations yet.', 'vehiclescheduler') ?> <a href="/plugins/vehiclescheduler/front/schedule.form.php"><?= __('Create one', 'vehiclescheduler') ?></a></div>
    <?php else: ?>
    <table class="vsp-table">
      <tr>
        <th><?= __('Vehicle', 'vehiclescheduler') ?></th>
        <th><?= __('Period', 'vehiclescheduler') ?></th>
        <th><?= __('Destination', 'vehiclescheduler') ?></th>
        <th><?= __('Status') ?></th>
      </tr>
      <?php foreach ($my_schedules as $s):
        $st_map = [1=>'bg-new',2=>'bg-approved',3=>'bg-rejected',4=>'bg-cancelled'];
        $st_class = $st_map[$s['status']] ?? 'bg-new';
        $st_label = $sch_statuses[$s['status']] ?? '?';
      ?>
      <tr>
        <td><a href="/plugins/vehiclescheduler/front/schedule.form.php?id=<?= $s['id'] ?>"><?= vs_vehicle_label($s['plugin_vehiclescheduler_vehicles_id']) ?></a></td>
        <td style="white-space:nowrap"><?= Html::convDate(substr($s['begin_date'],0,10)) ?><br><small><?= Html::convDate(substr($s['end_date'],0,10)) ?></small></td>
        <td><?= htmlspecialchars($s['destination']) ?></td>
        <td><span class="vsp-badge <?= $st_class ?>"><?= $st_label ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <!-- My Incidents -->
  <div class="vsp-card">
    <div class="vsp-card-header">
      <span><i class="ti ti-alert-triangle"></i> <?= __('My Incident Reports', 'vehiclescheduler') ?></span>
      <a href="/plugins/vehiclescheduler/front/incident.php" style="font-size:.75rem;color:#3b82f6;"><?= __('View all', 'vehiclescheduler') ?></a>
    </div>
    <?php if (empty($my_incidents)): ?>
      <div class="vsp-empty"><?= __('No incidents reported.', 'vehiclescheduler') ?></div>
    <?php else: ?>
    <table class="vsp-table">
      <tr>
        <th><?= __('Date', 'vehiclescheduler') ?></th>
        <th><?= __('Type', 'vehiclescheduler') ?></th>
        <th><?= __('Vehicle', 'vehiclescheduler') ?></th>
        <th><?= __('Status') ?></th>
      </tr>
      <?php foreach ($my_incidents as $inc):
        $st_map = [1=>'bg-open',2=>'bg-analyzing',3=>'bg-resolved',4=>'bg-cancelled'];
        $st_class = $st_map[$inc['status']] ?? 'bg-open';
      ?>
      <tr>
        <td><?= Html::convDate(substr($inc['incident_date'],0,10)) ?></td>
        <td><a href="/plugins/vehiclescheduler/front/incident.form.php?id=<?= $inc['id'] ?>"><?= $inc_types[$inc['incident_type']] ?? '?' ?></a></td>
        <td><?= vs_vehicle_label($inc['plugin_vehiclescheduler_vehicles_id']) ?></td>
        <td><span class="vsp-badge <?= $st_class ?>"><?= $inc_statuses[$inc['status']] ?? '?' ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

</div><!-- .vsp-grid-2 -->
</div><!-- .vsp -->
<?php Html::footer(); ?>
