<?php
/**
 * Lista de Reservas do Requerente
 * Interface simplificada para o usuário final
 */
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 4));
}
include_once(GLPI_ROOT . '/inc/includes.php');

// Verificar permissão básica
if (!PluginVehicleschedulerProfile::canAccessRequester()) {
    Html::displayRightError();
    exit;
}

$is_tab = isset($_GET['is_tab']) || isset($_POST['is_tab']);

if (!$is_tab) {
    if (Session::getCurrentInterface() == "helpdesk") {
        Html::helpHeader(__('Minhas Reservas', 'vehiclescheduler'));
    } else {
        Html::header('Minhas Reservas', $_SERVER['PHP_SELF'], 'helpdesk', 'PluginVehicleschedulerMenui');
    }
    
    if (Session::getCurrentInterface() != "helpdesk") {
        vs_render_navbar('requester');
    }
}

global $DB;
$my_id = Session::getLoginUserID();

// Buscar Reservas
$reservations = iterator_to_array($DB->request([
    'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => ['users_id' => $my_id],
    'ORDER' => ['date_creation DESC'],
]));

$statuses = PluginVehicleschedulerSchedule::getAllStatus();
?>

<div class="vs-app-view" style="max-width:1200px;margin:30px auto;padding:0 20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <h1 style="font-size:1.6rem;font-weight:700;color:var(--vs-text);margin:0;">📋 Histórico de Reservas</h1>
    </div>

    <div class="vs-card">
        <?php if (empty($reservations)): ?>
            <div style="text-align:center;padding:40px;color:var(--vs-text-light);">
                <i class="ti ti-calendar-x" style="font-size:48px;color:var(--vs-border);margin-bottom:16px;display:block;"></i>
                <h3>Nenhuma reserva encontrada</h3>
                <p>Você ainda não realizou nenhuma solicitação de veículo.</p>
                <a href="../schedule.form.php" class="vs-btn vs-btn-primary mt-3">Nova Reserva</a>
            </div>
        <?php else: ?>
            <?php foreach ($reservations as $r):
                $status_badges = [
                    PluginVehicleschedulerSchedule::STATUS_NEW       => 'vs-badge-blue',
                    PluginVehicleschedulerSchedule::STATUS_APPROVED  => 'vs-badge-green',
                    PluginVehicleschedulerSchedule::STATUS_REJECTED  => 'vs-badge-red',
                    PluginVehicleschedulerSchedule::STATUS_CANCELLED => 'vs-badge-gray',
                    PluginVehicleschedulerSchedule::STATUS_ONGOING   => 'vs-badge-yellow',
                    PluginVehicleschedulerSchedule::STATUS_RETURNED  => 'vs-badge-green',
                ];
                $badge_class = $status_badges[$r['status']] ?? 'vs-badge-blue';
                
                // Buscar nome do veículo
                $veh_name = 'Veículo não informado';
                if (!empty($r['plugin_vehiclescheduler_vehicles_id'])) {
                    $veh = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['id' => $r['plugin_vehiclescheduler_vehicles_id']]])->current();
                    if ($veh) $veh_name = htmlspecialchars($veh['name'] . ' (' . $veh['plate'] . ')');
                }
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:16px;border:1px solid var(--vs-border);border-radius:8px;margin-bottom:12px;background:var(--vs-bg);">
                <div style="display:flex;align-items:center;gap:16px;">
                    <div style="width:48px;height:48px;background:#e0f2fe;color:#0369a1;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;">🚙</div>
                    <div>
                        <div style="font-weight:600;color:var(--vs-text);font-size:1.1rem;margin-bottom:4px;"><?= htmlspecialchars($r['destination']) ?></div>
                        <div style="color:var(--vs-text-light);font-size:.85rem;">
                            <strong>Veículo:</strong> <?= $veh_name ?><br>
                            <strong>Período:</strong> <?= Html::convDate(substr($r['begin_date'],0,10)) ?> até <?= Html::convDate(substr($r['end_date'],0,10)) ?>
                        </div>
                    </div>
                </div>
                <div style="text-align:right;">
                    <span class="vs-badge <?= $badge_class ?> mb-2 d-block text-center"><?= $statuses[$r['status']] ?? 'N/A' ?></span>
                    <button type="button" class="vs-btn vs-btn-light" data-bs-toggle="modal" data-bs-target="#modalRes<?= $r['id'] ?>" style="font-size: 0.75rem; padding: 4px 10px;">Detalhes</button>
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
        <?php endif; ?>
    </div>
</div>
<script>
(function() {
    function applyTabBackground() {
        const view = document.querySelector('.vs-app-view');
        if (view) {
            let parent = view.parentElement;
            while (parent && !parent.classList.contains('tab-content') && parent.tagName !== 'BODY') {
                parent.style.setProperty('background', 'var(--vs-bg)', 'important');
                parent.style.setProperty('background-color', 'var(--vs-bg)', 'important');
                if (parent.classList.contains('card') || parent.classList.contains('tab_cadre_fixe') || parent.classList.contains('card-body')) {
                    parent.style.setProperty('border', 'none', 'important');
                    parent.style.setProperty('box-shadow', 'none', 'important');
                }
                parent = parent.parentElement;
            }
        }
    }
    applyTabBackground();
    setTimeout(applyTabBackground, 50);
    setTimeout(applyTabBackground, 200);
})();
</script>

<?php
if (!$is_tab) {
    Html::footer();
}
?>
