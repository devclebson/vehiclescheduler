<?php
/**
 * Portal do Requerente - 3 ações principais
 */
include('../../../../inc/includes.php');

// Verificar permissão de acesso ao portal
if (!PluginVehicleschedulerProfile::canAccessRequester()) {
    Html::displayRightError();
    exit;
}

// Carregar CSS glassmorphism
include_once(__DIR__ . '/../../inc/helpers/common.inc.php');

if (Session::getCurrentInterface() == "helpdesk") {
    Html::helpHeader(__('Bem-vindo à Reserva de Frota', 'vehiclescheduler'));
} else {
    Html::header('Reserva de Frota', $_SERVER['PHP_SELF'], 'helpdesk', 'PluginVehicleschedulerMenui');
}

if (Session::getCurrentInterface() != "helpdesk") {
    vs_render_navbar('requester');
}

?>

<div class="vs-app-view" style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">

<!-- Hero -->
<div style="background: linear-gradient(135deg, var(--vs-primary) 0%, #1e40af 100%); color: #fff; padding: 30px; border-radius: 16px; margin-bottom: 30px; text-align: center;">
  <h1 style="margin: 0; font-size: 2rem; font-weight: 700;">🚗 Bem-vindo à Reserva de Frota</h1>
  <p style="margin: 10px 0 0; opacity: 0.9; font-size: 1.1rem;">Escolha uma das opções abaixo para gerenciar suas solicitações</p>
</div>

<!-- 2 Cartões de Ação -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
  
  <!-- 1. Reservar Veículo -->
  <div class="vs-card" style="text-align:center; cursor:pointer;" onclick="location.href='../schedule.form.php'">
    <div style="width:64px;height:64px;margin:0 auto 16px;background:var(--vs-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;">🚙</div>
    <div style="font-size:1.15rem;font-weight:700;color:var(--vs-text);margin-bottom:8px;">Reservar Veículo</div>
    <div style="font-size:.85rem;color:var(--vs-text-light);">Solicite a reserva de um veículo para sua viagem ou compromisso</div>
  </div>

  <!-- 2. Reportar Incidente -->
  <div class="vs-card" style="text-align:center; cursor:pointer;" onclick="location.href='../incident.form.php'">
    <div style="width:64px;height:64px;margin:0 auto 16px;background:var(--vs-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;">⚠️</div>
    <div style="font-size:1.15rem;font-weight:700;color:var(--vs-text);margin-bottom:8px;">Reportar Incidente</div>
    <div style="font-size:.85rem;color:var(--vs-text-light);">Relate acidentes, avarias ou problemas com veículos da frota</div>
  </div>

</div>

<!-- Minhas Últimas Reservas -->
<?php
global $DB;
$my_id = Session::getLoginUserID();
$recent = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => ['users_id' => $my_id],
    'ORDER' => ['date_creation DESC'],
    'LIMIT' => 5,
]));
$statuses = ['', 'Nova', 'Aprovada', 'Recusada', 'Cancelada'];
?>
<div class="vs-card">
  <div class="vs-card-header">
    <h2>📋 Minhas Últimas Reservas</h2>
  </div>
  <?php if (empty($recent)): ?>
    <p style="color:var(--vs-text-light);text-align:center;padding:20px;">Você ainda não possui reservas. Clique em "Reservar Veículo" para começar!</p>
  <?php else: ?>
    <div>
      <?php foreach ($recent as $r):
        $badge_class = ['', 'vs-badge-blue', 'vs-badge-green', 'vs-badge-red', 'vs-badge-gray'][$r['status']] ?? 'vs-badge-blue';
        
        // Buscar nome do veículo
        $veh_name = 'Veículo não informado';
        if (!empty($r['plugin_vehiclescheduler_vehicles_id'])) {
            $veh = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['id' => $r['plugin_vehiclescheduler_vehicles_id']]])->current();
            if ($veh) $veh_name = htmlspecialchars($veh['name'] . ' (' . $veh['plate'] . ')');
        }
      ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px;border:1px solid var(--vs-border);border-radius:8px;margin-bottom:12px;background:var(--vs-bg);">
          <div style="display:flex;align-items:center;gap:16px;">
            <i class="ti ti-calendar-event" style="font-size:1.2rem;color:var(--vs-text-light);"></i>
            <div>
              <div style="font-weight:600;font-size:.9rem;"><?= htmlspecialchars($r['destination']) ?></div>
              <div style="font-size:.8rem;color:var(--vs-text-light);"><?= Html::convDate(substr($r['begin_date'],0,10)) ?> → <?= Html::convDate(substr($r['end_date'],0,10)) ?></div>
            </div>
          </div>
          <div style="text-align:right;">
            <span class="vs-badge <?= $badge_class ?>" style="display:block;margin-bottom:8px;"><?= $statuses[$r['status']] ?? 'N/A' ?></span>
            <button type="button" class="vs-btn vs-btn-light" data-bs-toggle="modal" data-bs-target="#modalRecRes<?= $r['id'] ?>" style="font-size: 0.75rem; padding: 4px 10px;">Detalhes</button>
          </div>
        </div>

        <!-- Modal Detalhes Reserva Recente -->
        <div class="modal fade" id="modalRecRes<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content text-start">
              <div class="modal-header">
                <h5 class="modal-title">Reserva #<?= $r['id'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Destino</small>
                    <strong><?= htmlspecialchars($r['destination']) ?></strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Veículo Solicitado</small>
                    <strong><?= $veh_name ?></strong>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block">Data de Início</small>
                        <strong><?= Html::convDateTime($r['begin_date']) ?></strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Data de Fim</small>
                        <strong><?= Html::convDateTime($r['end_date']) ?></strong>
                    </div>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Status Atual</small>
                    <span class="vs-badge <?= $badge_class ?>"><?= $statuses[$r['status']] ?? 'N/A' ?></span>
                </div>
                <?php if (!empty($r['purpose'])): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Observações</small>
                    <div class="p-2 bg-light rounded" style="font-size:0.9rem;">
                        <?= nl2br(htmlspecialchars($r['purpose'])) ?>
                    </div>
                </div>
                <?php endif; ?>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <?php if ($r['status'] == 1): // Status Nova ?>
                    <a href="../schedule.form.php?id=<?= $r['id'] ?>" class="btn btn-primary"><i class="ti ti-edit"></i> Editar</a>
                    
                    <form method="post" action="../schedule.form.php" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja cancelar esta solicitação?');">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">
                        <button type="submit" name="delete" class="btn btn-danger"><i class="ti ti-trash"></i> Cancelar Reserva</button>
                    </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div style="text-align:center;margin-top:20px;">
    <a href="requester_list.php" style="color:var(--vs-primary);font-weight:600;font-size:.9rem;text-decoration:none;">Ver todas as minhas reservas →</a>
  </div>
</div>

</div>
<?php Html::footer(); ?>
