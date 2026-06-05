<?php
/**
 * Portal do Requerente - 3 ações principais
 */
include('../../../inc/includes.php');

// Verificar permissão de acesso ao portal
if (!PluginVehicleschedulerProfile::canAccessRequester()) {
    Html::displayRightError();
    exit;
}

// Carregar CSS glassmorphism
include_once(__DIR__ . '/../inc/common.inc.php');

if (Session::getCurrentInterface() == "helpdesk") {
    Html::helpHeader(__('Bem-vindo à Reserva de Frota', 'vehiclescheduler'));
} else {
    Html::header('Reserva de Frota', $_SERVER['PHP_SELF'], 'helpdesk', 'PluginVehicleschedulerMenui');
}

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
?>
<style>
.req-portal{max-width:1200px;margin:30px auto;padding:0 20px;}
.req-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px 20px;text-align:center;cursor:pointer;transition:all .3s;box-shadow:0 4px 16px rgba(0,0,0,0.05);}
.req-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,0.1);border-color:#cbd5e1;}
.req-card-icon{width:64px;height:64px;margin:0 auto 16px;background:linear-gradient(135deg,#f8fafc 20%,#e2e8f0);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 4px 12px rgba(0,0,0,0.05);}
.req-card-title{font-size:1.15rem;font-weight:700;color:#1e293b;margin-bottom:8px;}
.req-card-desc{font-size:.85rem;color:#475569;line-height:1.4;}
.req-recent{background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;box-shadow:0 4px 16px rgba(0,0,0,0.05);}
.req-recent h3{margin:0 0 20px;font-size:1.15rem;color:#1e293b;font-weight:700;}
.req-item{display:flex;justify-content:space-between;align-items:center;padding:16px;border:1px solid #f1f5f9;border-radius:8px;margin-bottom:12px;background:#fafafa;}
.req-item-left{display:flex;align-items:center;gap:16px;}
.req-badge{padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:700;}
.badge-new{background:#dbeafe;color:#1d4ed8;}
.badge-approved{background:#dcfce7;color:#166534;}
.badge-rejected{background:#fee2e2;color:#991b1b;}
.badge-cancelled{background:#f1f5f9;color:#475569;}
</style>

<div class="req-portal">

<!-- Hero Glassmorphism -->
<div class="vs-hero-glass">
  <h1>🚗 Bem-vindo à Reserva de Frota</h1>
  <p>Escolha uma das opções abaixo para gerenciar suas solicitações</p>
<!-- 2 Cartões de Ação -->
<div class="row g-4 mb-4 justify-content-center">
  
  <!-- 1. Reservar Veículo -->
  <div class="col-md-5">
    <div class="req-card h-100" onclick="location.href='schedule.form.php'">
      <div class="req-card-icon">🚙</div>
      <div class="req-card-title">Reservar Veículo</div>
      <div class="req-card-desc">Solicite a reserva de um veículo para sua viagem ou compromisso</div>
    </div>
  </div>

  <!-- 2. Reportar Incidente -->
  <div class="col-md-5">
    <div class="req-card h-100" onclick="location.href='incident.form.php'">
      <div class="req-card-icon">⚠️</div>
      <div class="req-card-title">Reportar Incidente</div>
      <div class="req-card-desc">Relate acidentes, avarias ou problemas com veículos da frota</div>
    </div>
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
<div class="req-recent">
  <h3>📋 Minhas Últimas Reservas</h3>
  <?php if (empty($recent)): ?>
    <p style="color:#94a3b8;text-align:center;padding:20px;">Você ainda não possui reservas. Clique em "Reservar Veículo" para começar!</p>
  <?php else: ?>
    <div class="req-list">
      <?php foreach ($recent as $r):
        $badge_class = ['', 'badge-new', 'badge-approved', 'badge-rejected', 'badge-cancelled'][$r['status']] ?? 'badge-new';
        
        // Buscar nome do veículo
        $veh_name = 'Veículo não informado';
        if (!empty($r['plugin_vehiclescheduler_vehicles_id'])) {
            $veh = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['id' => $r['plugin_vehiclescheduler_vehicles_id']]])->current();
            if ($veh) $veh_name = htmlspecialchars($veh['name'] . ' (' . $veh['plate'] . ')');
        }
      ?>
        <div class="req-item">
          <div class="req-item-left">
            <i class="ti ti-calendar-event" style="font-size:1.2rem;color:#64748b;"></i>
            <div>
              <div style="font-weight:600;font-size:.9rem;"><?= htmlspecialchars($r['destination']) ?></div>
              <div style="font-size:.8rem;color:#64748b;"><?= Html::convDate(substr($r['begin_date'],0,10)) ?> → <?= Html::convDate(substr($r['end_date'],0,10)) ?></div>
            </div>
          </div>
          <div style="text-align:right;">
            <span class="req-badge <?= $badge_class ?> d-block mb-2"><?= $statuses[$r['status']] ?? 'N/A' ?></span>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalRecRes<?= $r['id'] ?>" style="font-size: 0.75rem; padding: 2px 8px;">Detalhes</button>
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
                    <span class="req-badge <?= $badge_class ?>"><?= $statuses[$r['status']] ?? 'N/A' ?></span>
                </div>
                <?php if (!empty($r['description'])): ?>
                <div class="mb-3">
                    <small class="text-muted d-block">Observações</small>
                    <div class="p-2 bg-light rounded" style="font-size:0.9rem;">
                        <?= nl2br(htmlspecialchars($r['description'])) ?>
                    </div>
                </div>
                <?php endif; ?>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <?php if ($r['status'] == 1): // Status Nova ?>
                    <a href="schedule.form.php?id=<?= $r['id'] ?>" class="btn btn-primary"><i class="ti ti-edit"></i> Editar</a>
                    
                    <form method="post" action="schedule.form.php" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja cancelar esta solicitação?');">
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
  <div style="text-align:center;margin-top:16px;">
    <a href="requester_list.php" style="color:#3b82f6;font-weight:600;font-size:.9rem;">Ver todas as minhas reservas →</a>
  </div>
</div>

</div>
<?php Html::footer(); ?>
