<?php
/**
 * Gestão de Multas
 */
include('../../../inc/includes.php');
Session::checkRight('plugin_vehiclescheduler', READ);

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

global $DB;

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
<style>
.fines{max-width:1400px;margin:0 auto;padding:0 12px;font-family:inherit;}
.fines-header{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border-radius:14px;padding:24px 32px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;}
.fines-header h1{margin:0;font-size:1.8rem;font-weight:700;}
.fines-kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;}
.fines-kpi-card{background:#fff;border:2px solid #e2e8f0;border-radius:12px;padding:20px;text-align:center;}
.fines-kpi-card .val{font-size:2.5rem;font-weight:800;color:#dc2626;line-height:1;}
.fines-kpi-card .lbl{font-size:.8rem;color:#64748b;font-weight:600;margin-top:8px;}
.fines-table-container{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
.fines-table{width:100%;border-collapse:collapse;font-size:.85rem;}
.fines-table th{background:#f8fafc;padding:12px 14px;text-align:left;color:#64748b;font-weight:600;font-size:.75rem;text-transform:uppercase;border-bottom:1px solid #e2e8f0;}
.fines-table td{padding:12px 14px;border-bottom:1px solid #f1f5f9;}
.fines-table tr:hover td{background:#f8fafc;}
.fines-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;}
.badge-open{background:#fee2e2;color:#991b1b;}
.badge-paid{background:#dcfce7;color:#166534;}
.fines-quick-btn{padding:5px 12px;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;border:none;margin-right:4px;}
.btn-pay{background:#dcfce7;color:#166534;} .btn-pay:hover{background:#bbf7d0;}
.btn-cancel{background:#f1f5f9;color:#64748b;} .btn-cancel:hover{background:#e2e8f0;}
.fines-empty{padding:40px;text-align:center;color:#94a3b8;}
</style>

<div class="fines">

<div class="fines-header">
  <div>
    <h1>🎫 Gestão de Multas de Trânsito</h1>
    <p style="margin:6px 0 0;opacity:.9;font-size:.9rem;">Controle de infrações e pontuação CNH</p>
  </div>
  <a href="driver.php" class="btn btn-light"><i class="ti ti-steering-wheel"></i> Ver Motoristas</a>
</div>

<!-- KPIs -->
<div class="fines-kpi">
  <div class="fines-kpi-card">
    <div class="val"><?= count($open_fines) ?></div>
    <div class="lbl">MULTAS ABERTAS</div>
  </div>
  <div class="fines-kpi-card">
    <div class="val"><?= $total_points ?></div>
    <div class="lbl">PONTOS TOTAIS EM ABERTO</div>
  </div>
  <div class="fines-kpi-card">
    <div class="val">R$ <?= number_format(array_sum(array_column($open_fines, 'id')) * 195.23, 2, ',', '.') ?></div>
    <div class="lbl">VALOR ESTIMADO (média R$ 195,23)</div>
  </div>
</div>

<!-- Tabela -->
<div class="fines-table-container">
  <?php if (empty($open_fines)): ?>
    <div class="fines-empty">✅ Nenhuma multa em aberto</div>
  <?php else: ?>
    <table class="fines-table">
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
          <td><a href="driver.form.php?id=<?= $f['plugin_vehiclescheduler_drivers_id'] ?>"><?= htmlspecialchars($f['driver_name']) ?></a></td>
          <td><?= htmlspecialchars($f['vehicle_name'] . ' (' . $f['vehicle_plate'] . ')') ?></td>
          <td><?= $sev_label ?></td>
          <td><strong style="color:#dc2626;"><?= $pts ?></strong></td>
          <td><?= htmlspecialchars(substr($f['description'], 0, 50)) ?><?= strlen($f['description']) > 50 ? '...' : '' ?></td>
          <td><span class="fines-badge badge-open">Em Aberto</span></td>
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="fine_id" value="<?= $f['id'] ?>">
              <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
              <button type="submit" name="quick_fine_action" value="paid" class="fines-quick-btn btn-pay" title="Marcar como paga">💰 Pagar</button>
              <button type="submit" name="quick_fine_action" value="cancel" class="fines-quick-btn btn-cancel" title="Cancelar multa">✗ Cancelar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

</div>
<?php Html::footer(); ?>
