<?php
/**
 * Gestão de Multas
 */
include('../../../../inc/includes.php');
if (!class_exists('PluginVehicleschedulerProfile') || !PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
}

global $DB, $CFG_GLPI;

include_once(__DIR__ . '/../../inc/helpers/common.inc.php');

// Processar ação rápida (pagar/cancelar)
if (isset($_POST['quick_fine_action']) && isset($_POST['fine_id'])) {
    $fine = new PluginVehicleschedulerDriverfine();
    if ($fine->getFromDB($_POST['fine_id'])) {
        $new_status = $_POST['quick_fine_action'] == 'paid' ? 2 : 4;
        $fine->update(['id' => $_POST['fine_id'], 'status' => $new_status]);
        Session::addMessageAfterRedirect('Multa atualizada!', false, INFO);
        Html::redirect($_SERVER['PHP_SELF']);
    }
}

Html::header('Multas de Trânsito', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'fines');

vs_render_navbar('fines');

// Buscar todas multas abertas
$open_fines = iterator_to_array($DB->request([
    'SELECT' => [
        'glpi_plugin_vehiclescheduler_driverfines.*',
        'glpi_plugin_vehiclescheduler_drivers.name AS driver_name',
        'glpi_plugin_vehiclescheduler_vehicles.name AS vehicle_name',
        'glpi_plugin_vehiclescheduler_vehicles.plate AS vehicle_plate',
    ],
    'FROM' => 'glpi_plugin_vehiclescheduler_driverfines',
    'LEFT JOIN' => [
        'glpi_plugin_vehiclescheduler_drivers' => [
            'FKEY' => [
                'glpi_plugin_vehiclescheduler_driverfines' => 'plugin_vehiclescheduler_drivers_id',
                'glpi_plugin_vehiclescheduler_drivers' => 'id',
            ],
        ],
        'glpi_plugin_vehiclescheduler_vehicles' => [
            'FKEY' => [
                'glpi_plugin_vehiclescheduler_driverfines' => 'plugin_vehiclescheduler_vehicles_id',
                'glpi_plugin_vehiclescheduler_vehicles' => 'id',
            ],
        ],
    ],
    'WHERE' => ['glpi_plugin_vehiclescheduler_driverfines.status' => 1],
    'ORDER' => ['glpi_plugin_vehiclescheduler_driverfines.fine_date DESC'],
]));

// Total de pontos em aberto
$total_points = 0;
$points_map = PluginVehicleschedulerDriverfine::getSeverityPoints();
foreach ($open_fines as $f) {
    $total_points += $points_map[$f['severity']] ?? 0;
}

$severities = PluginVehicleschedulerDriverfine::getAllSeverities();
?>

<div class="vs-app-view" style="max-width: 1400px; margin: 0 auto;">

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
  <div>
    <h1 style="margin:0; font-size:1.8rem; font-weight:700; color:var(--vs-text);">🎫 Gestão de Multas de Trânsito</h1>
    <p style="margin:6px 0 0; color:var(--vs-text-light); font-size:.9rem;">Controle de infrações e pontuação CNH</p>
  </div>
  <a href="../driver.php" class="vs-btn vs-btn-light"><i class="ti ti-steering-wheel"></i> Ver Motoristas</a>
</div>

<!-- KPIs -->
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:24px;">
  <div class="vs-card" style="text-align:center; padding:20px;">
    <div style="font-size:2.5rem; font-weight:800; color:var(--vs-danger); line-height:1;"><?= count($open_fines) ?></div>
    <div style="font-size:.8rem; color:var(--vs-text-light); font-weight:600; margin-top:8px;">MULTAS ABERTAS</div>
  </div>
  <div class="vs-card" style="text-align:center; padding:20px;">
    <div style="font-size:2.5rem; font-weight:800; color:var(--vs-danger); line-height:1;"><?= $total_points ?></div>
    <div style="font-size:.8rem; color:var(--vs-text-light); font-weight:600; margin-top:8px;">PONTOS TOTAIS EM ABERTO</div>
  </div>
  <div class="vs-card" style="text-align:center; padding:20px;">
    <div style="font-size:2.5rem; font-weight:800; color:var(--vs-danger); line-height:1;">R$ <?= number_format(array_sum(array_column($open_fines, 'id')) * 195.23, 2, ',', '.') ?></div>
    <div style="font-size:.8rem; color:var(--vs-text-light); font-weight:600; margin-top:8px;">VALOR ESTIMADO (média R$ 195,23)</div>
  </div>
</div>

<!-- Tabela -->
<div class="vs-card" style="padding:0;">
  <?php if (empty($open_fines)): ?>
    <div style="padding:40px; text-align:center; color:var(--vs-text-light);">✅ Nenhuma multa em aberto</div>
  <?php else: ?>
    <table class="vs-table">
      <tr>
        <th>Data</th>
        <th>Motorista</th>
        <th>Veículo</th>
        <th>Gravidade</th>
        <th>Pontos</th>
        <th>Descrição</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
      <?php foreach ($open_fines as $f):
        $pts = $points_map[$f['severity']] ?? 0;
        $sev_label = $severities[$f['severity']] ?? '?';
      ?>
        <tr>
          <td><?= Html::convDate($f['fine_date']) ?></td>
          <td><a href="../driver.form.php?id=<?= $f['plugin_vehiclescheduler_drivers_id'] ?>" style="color:var(--vs-primary);text-decoration:none;font-weight:600;"><?= htmlspecialchars($f['driver_name']) ?></a></td>
          <td><?= htmlspecialchars($f['vehicle_name'] . ' (' . $f['vehicle_plate'] . ')') ?></td>
          <td><?= $sev_label ?></td>
          <td><strong style="color:var(--vs-danger);"><?= $pts ?></strong></td>
          <td><?= htmlspecialchars(substr($f['description'], 0, 50)) ?><?= strlen($f['description']) > 50 ? '...' : '' ?></td>
          <td><span class="vs-badge vs-badge-red">Em Aberto</span></td>
          <td>
            <form method="post" style="display:inline; display:flex; gap:6px;">
              <input type="hidden" name="fine_id" value="<?= $f['id'] ?>">
              <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
              <button type="submit" name="quick_fine_action" value="paid" class="vs-btn vs-btn-light" style="color:#166534 !important; padding:4px 8px; font-size:.8rem;" title="Marcar como paga">💰 Pagar</button>
              <button type="submit" name="quick_fine_action" value="cancel" class="vs-btn vs-btn-light" style="padding:4px 8px; font-size:.8rem;" title="Cancelar multa">✗ Cancelar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

</div>
<?php Html::footer(); ?>
