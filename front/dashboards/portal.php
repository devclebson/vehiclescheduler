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

<div class="vs-app-view" style="max-width: 1200px; margin: 0 auto;">

<!-- Quick Actions -->
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:22px;">
  <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.form.php" class="vs-btn vs-btn-primary">
    <i class="ti ti-calendar-plus"></i> <?= __('Request Reservation', 'vehiclescheduler') ?>
  </a>
  <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/incident.form.php" class="vs-btn vs-btn-warning">
    <i class="ti ti-alert-triangle"></i> <?= __('Report Incident', 'vehiclescheduler') ?>
  </a>
  <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/driver.form.php" class="vs-btn vs-btn-secondary">
    <i class="ti ti-steering-wheel"></i> <?= __('Register Driver', 'vehiclescheduler') ?>
  </a>
</div>

<!-- Main Grid -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap:20px;">

  <!-- My Reservations -->
  <div class="vs-card">
    <div class="vs-card-header">
      <h2><i class="ti ti-calendar-event"></i> <?= __('My Reservations', 'vehiclescheduler') ?></h2>
      <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.php" style="font-size:.85rem;color:var(--vs-primary);text-decoration:none;font-weight:600;"><?= __('View all', 'vehiclescheduler') ?></a>
    </div>
    <?php if (empty($my_schedules)): ?>
      <div style="padding:20px; text-align:center; color:var(--vs-text-light); font-size:.9rem;"><?= __('You have no reservations yet.', 'vehiclescheduler') ?> <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.form.php" style="color:var(--vs-primary);"><?= __('Create one', 'vehiclescheduler') ?></a></div>
    <?php else: ?>
    <table class="vs-table">
      <tr>
        <th><?= __('Vehicle', 'vehiclescheduler') ?></th>
        <th><?= __('Period', 'vehiclescheduler') ?></th>
        <th><?= __('Destination', 'vehiclescheduler') ?></th>
        <th><?= __('Status') ?></th>
      </tr>
      <?php foreach ($my_schedules as $s):
        $st_map = [1=>'vs-badge-blue',2=>'vs-badge-green',3=>'vs-badge-red',4=>'vs-badge-gray'];
        $st_class = $st_map[$s['status']] ?? 'vs-badge-blue';
        $st_label = $sch_statuses[$s['status']] ?? '?';
      ?>
      <tr>
        <td><a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/schedule.form.php?id=<?= $s['id'] ?>" style="color:var(--vs-primary);text-decoration:none;font-weight:600;"><?= vs_vehicle_label($s['plugin_vehiclescheduler_vehicles_id']) ?></a></td>
        <td style="white-space:nowrap"><?= Html::convDate(substr($s['begin_date'],0,10)) ?><br><small style="color:var(--vs-text-light)"><?= Html::convDate(substr($s['end_date'],0,10)) ?></small></td>
        <td><?= htmlspecialchars($s['destination']) ?></td>
        <td><span class="vs-badge <?= $st_class ?>"><?= $st_label ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

  <!-- My Incidents -->
  <div class="vs-card">
    <div class="vs-card-header">
      <h2><i class="ti ti-alert-triangle"></i> <?= __('My Incident Reports', 'vehiclescheduler') ?></h2>
      <a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/incident.php" style="font-size:.85rem;color:var(--vs-primary);text-decoration:none;font-weight:600;"><?= __('View all', 'vehiclescheduler') ?></a>
    </div>
    <?php if (empty($my_incidents)): ?>
      <div style="padding:20px; text-align:center; color:var(--vs-text-light); font-size:.9rem;"><?= __('No incidents reported.', 'vehiclescheduler') ?></div>
    <?php else: ?>
    <table class="vs-table">
      <tr>
        <th><?= __('Date', 'vehiclescheduler') ?></th>
        <th><?= __('Type', 'vehiclescheduler') ?></th>
        <th><?= __('Vehicle', 'vehiclescheduler') ?></th>
        <th><?= __('Status') ?></th>
      </tr>
      <?php foreach ($my_incidents as $inc):
        $st_map = [1=>'vs-badge-red',2=>'vs-badge-yellow',3=>'vs-badge-green',4=>'vs-badge-gray'];
        $st_class = $st_map[$inc['status']] ?? 'vs-badge-red';
      ?>
      <tr>
        <td><?= Html::convDate(substr($inc['incident_date'],0,10)) ?></td>
        <td><a href="<?= Plugin::getWebDir('vehiclescheduler') ?>/front/incident.form.php?id=<?= $inc['id'] ?>" style="color:var(--vs-primary);text-decoration:none;font-weight:600;"><?= $inc_types[$inc['incident_type']] ?? '?' ?></a></td>
        <td><?= vs_vehicle_label($inc['plugin_vehiclescheduler_vehicles_id']) ?></td>
        <td><span class="vs-badge <?= $st_class ?>"><?= $inc_statuses[$inc['status']] ?? '?' ?></span></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php endif; ?>
  </div>

</div>
</div>

<?php
if (!$is_tab) {
    Html::footer();
}
?>
