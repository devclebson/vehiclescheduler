<?php
/**
 * Lista de Reservas do Requerente
 * Interface simplificada para o usuário final
 */
include('../../../inc/includes.php');

// Verificar permissão básica
if (!PluginVehicleschedulerProfile::canAccessRequester()) {
    Html::displayRightError();
    exit;
}

// Carregar CSS glassmorphism
include_once(__DIR__ . '/../inc/common.inc.php');

if (Session::getCurrentInterface() == "helpdesk") {
    Html::helpHeader(__('Minhas Reservas', 'vehiclescheduler'));
} else {
    Html::header('Minhas Reservas', $_SERVER['PHP_SELF'], 'helpdesk', 'PluginVehicleschedulerMenui');
}

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

global $DB;
$my_id = Session::getLoginUserID();

// Buscar Reservas
$reservations = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => ['users_id' => $my_id],
    'ORDER' => ['date_creation DESC'],
]));

$statuses = ['', 'Nova', 'Aprovada', 'Recusada', 'Cancelada'];
?>
<style>
.req-container{max-width:1200px;margin:30px auto;padding:0 20px;}
.req-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;}
.req-title{font-size:1.6rem;font-weight:700;color:#1e293b;margin:0;}
.req-btn-back{background:#ffffff;border:1px solid #cbd5e1;padding:8px 16px;border-radius:8px;color:#475569;text-decoration:none;font-weight:600;font-size:.9rem;display:inline-flex;align-items:center;gap:6px;}
.req-btn-back:hover{background:#f8fafc;color:#1e293b;}
.req-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;box-shadow:0 4px 16px rgba(0,0,0,0.05);margin-bottom:16px;}
.req-empty{text-align:center;padding:40px;color:#64748b;}
.req-item{display:flex;justify-content:space-between;align-items:center;padding:16px;border:1px solid #f1f5f9;border-radius:8px;margin-bottom:12px;background:#fafafa;}
.req-item-left{display:flex;align-items:center;gap:16px;}
.req-icon{width:48px;height:48px;background:#e0f2fe;color:#0369a1;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;}
.req-info-title{font-weight:600;color:#1e293b;font-size:1.1rem;margin-bottom:4px;}
.req-info-desc{color:#64748b;font-size:.85rem;}
.req-badge{padding:6px 12px;border-radius:20px;font-size:.8rem;font-weight:700;}
.badge-new{background:#dbeafe;color:#1d4ed8;}
.badge-approved{background:#dcfce7;color:#166534;}
.badge-rejected{background:#fee2e2;color:#991b1b;}
.badge-cancelled{background:#f1f5f9;color:#475569;}
</style>

<div class="req-container">
    <div class="req-header">
        <h1 class="req-title">📋 Histórico de Reservas</h1>
        <a href="requester.php" class="req-btn-back"><i class="ti ti-arrow-left"></i> Voltar ao Portal</a>
    </div>

    <div class="req-card">
        <?php if (empty($reservations)): ?>
            <div class="req-empty">
                <i class="ti ti-calendar-x" style="font-size:48px;color:#cbd5e1;margin-bottom:16px;display:block;"></i>
                <h3>Nenhuma reserva encontrada</h3>
                <p>Você ainda não realizou nenhuma solicitação de veículo.</p>
                <a href="schedule.form.php" class="btn btn-primary mt-3">Nova Reserva</a>
            </div>
        <?php else: ?>
            <?php foreach ($reservations as $r):
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
                    <div class="req-icon">🚙</div>
                    <div>
                        <div class="req-info-title"><?= htmlspecialchars($r['destination']) ?></div>
                        <div class="req-info-desc">
                            <strong>Veículo:</strong> <?= $veh_name ?><br>
                            <strong>Período:</strong> <?= Html::convDate(substr($r['begin_date'],0,10)) ?> até <?= Html::convDate(substr($r['end_date'],0,10)) ?>
                        </div>
                    </div>
                </div>
                <div style="text-align:right;">
                    <span class="req-badge <?= $badge_class ?> mb-2 d-block text-center"><?= $statuses[$r['status']] ?? 'N/A' ?></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalRes<?= $r['id'] ?>">Detalhes</button>
                </div>
            </div>

            <!-- Modal Detalhes Reserva -->
            <div class="modal fade" id="modalRes<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
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
        <?php endif; ?>
    </div>
</div>

<?php Html::footer(); ?>
