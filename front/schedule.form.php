<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * 
 * Schedule form page
 * 
 * @category Plugin
 * @package  VehicleScheduler
 * @author   Plugin Development Team
 * @license  GPLv3+
 */

include ('../../../inc/includes.php');

// Verificar permissão básica de acesso ao portal ou gestão
if (!Session::haveRight('plugin_vehiclescheduler', UPDATE) && !PluginVehicleschedulerProfile::canAccessRequester()) {
    Session::checkRight('plugin_vehiclescheduler', UPDATE);
}

if (!PluginVehicleschedulerProfile::canAccessRequester() && !PluginVehicleschedulerProfile::canViewManagement()) {
    Html::displayRightError();
    exit;
}

$schedule = new PluginVehicleschedulerSchedule();
$id = isset($_REQUEST["id"]) ? (int)$_REQUEST["id"] : 0;

if ($id > 0) {
    if (!$schedule->getFromDB($id)) {
        Html::displayNotFoundError();
        exit;
    }
    
    // Se não for gestor, só pode ver a própria reserva
    if (!PluginVehicleschedulerProfile::canViewManagement() && $schedule->fields['users_id'] != Session::getLoginUserID()) {
        Html::displayRightError();
        exit;
    }
}

// POST: request_driver
if (isset($_POST['request_driver'])) {
    if (PluginVehicleschedulerDriver::requestDriverRegistration($_POST)) {
        Session::addMessageAfterRedirect('Solicitação de cadastro de motorista enviada com sucesso! Um chamado foi aberto para análise.', false, INFO);
    }
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
}

// POST: start_trip
if (isset($_POST['start_trip'])) {
    if ($schedule->startTrip($_POST)) {
        Session::addMessageAfterRedirect('Viagem iniciada com sucesso! Boa viagem.', false, INFO);
    }
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/schedule.form.php?id=' . $_POST['id']);
}

// POST: end_trip
if (isset($_POST['end_trip'])) {
    if ($schedule->endTrip($_POST)) {
        Session::addMessageAfterRedirect('Viagem concluída e veículo devolvido com sucesso.', false, INFO);
        
        // Se houver avaria, redirecionar para abertura de incidentes pré-preenchido
        $chk = $_POST['return_checklist'] ?? [];
        if (isset($chk['damage']) && $chk['damage'] == '1') {
            $inc_url = $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/incident.form.php?plugin_vehiclescheduler_vehicles_id=' . $schedule->fields['plugin_vehiclescheduler_vehicles_id'];
            Session::addMessageAfterRedirect('Atenção: Como foi marcada avaria no checklist, por favor registre os detalhes do incidente.', false, WARNING);
            Html::redirect($inc_url);
        }
    }
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');
}

// Verificar restrições de condutores comuns (Cenários A, B e C)
$is_manager = PluginVehicleschedulerProfile::canViewManagement();
$driver = false;
$is_approved_driver = false;

if (!$is_manager) {
    $driver = PluginVehicleschedulerDriver::getDriverByUserId(Session::getLoginUserID());
    if ($driver && $driver['is_approved'] == 1 && $driver['is_active'] == 1) {
        $is_approved_driver = true;
    }
}

// Bloqueio / Telas para motoristas não autorizados ou pendentes
if (!$is_manager && !$is_approved_driver) {
    Html::header(
        "Acesso Restrito",
        $_SERVER['PHP_SELF'],
        'plugins',
        'pluginvehicleschedulerschedule'
    );
    
    echo "<div class='container px-3 py-5 d-flex justify-content-center'>";
    echo "  <div class='col-md-8 col-lg-6'>";
    
    if (!$driver) {
        // Cenário A: Sem cadastro de motorista
        echo "
        <div class='card shadow-lg border-0' style='border-radius:15px; overflow:hidden;'>
            <div class='card-header text-white text-center py-4' style='background: linear-gradient(135deg, #ef4444, #b91c1c);'>
                <i class='ti ti-lock-square-rounded fs-1 mb-2'></i>
                <h4 class='fw-bold mb-0'>Acesso Restrito</h4>
                <p class='mb-0 opacity-75'>Apenas motoristas cadastrados podem reservar veículos</p>
            </div>
            <div class='card-body p-4 bg-white'>
                <div class='alert alert-warning border-0 shadow-sm mb-4 d-flex align-items-start'>
                    <i class='ti ti-alert-triangle fs-4 me-3 mt-1'></i>
                    <div>
                        <strong>Cadastro Requerido:</strong> Para solicitar reservas de veículos no sistema, você precisa estar cadastrado e ativo como motorista no GLPI.
                    </div>
                </div>
                
                <h5 class='fw-bold mb-3 text-secondary border-bottom pb-2'><i class='ti ti-id'></i> Solicitar Cadastro</h5>
                <form method='post' action='schedule.form.php'>
                    <input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>
                    
                    <div class='row g-3'>
                        <div class='col-md-6'>
                            <label class='form-label fw-bold'>Matrícula <span class='text-danger'>*</span></label>
                            <input type='text' name='registration' class='form-control' required placeholder='Ex: 12345-6'>
                        </div>
                        <div class='col-md-6'>
                            <label class='form-label fw-bold'>Categoria CNH <span class='text-danger'>*</span></label>
                            <select name='cnh_category' class='form-select' required>
                                <option value='B'>B</option>
                                <option value='AB'>AB</option>
                                <option value='A'>A</option>
                                <option value='C'>C</option>
                                <option value='D'>D</option>
                                <option value='E'>E</option>
                            </select>
                        </div>
                        <div class='col-md-6'>
                            <label class='form-label fw-bold'>Vencimento CNH <span class='text-danger'>*</span></label>
                            <input type='date' name='cnh_expiry' class='form-control' required>
                        </div>
                        <div class='col-md-6'>
                            <label class='form-label fw-bold'>Telefone para Contato <span class='text-danger'>*</span></label>
                            <input type='text' name='contact_phone' class='form-control' required placeholder='(00) 00000-0000'>
                        </div>
                        <div class='col-12'>
                            <label class='form-label fw-bold'>Departamento/Setor <span class='text-danger'>*</span></label>
                            <input type='text' name='department' class='form-control' required placeholder='Sua área de atuação'>
                        </div>
                        <div class='col-12'>
                            <label class='form-label fw-bold'>Observações / Justificativa</label>
                            <textarea name='comment' class='form-control' rows='3' placeholder='Por que você precisa reservar veículos?'></textarea>
                        </div>
                    </div>
                    
                    <div class='d-grid gap-2 mt-4'>
                        <button type='submit' name='request_driver' class='btn btn-primary btn-lg fw-bold shadow-sm' style='background-color:#1e3a8a; border:none;'>
                            <i class='ti ti-send me-2'></i> Enviar Solicitação de Cadastro
                        </button>
                        <a href='index.php' class='btn btn-outline-secondary btn-sm mt-2'>Cancelar</a>
                    </div>
                </form>
            </div>
        </div>";
    } else {
        // Cenário B: Cadastro pendente
        echo "
        <div class='card shadow-lg border-0' style='border-radius:15px; overflow:hidden;'>
            <div class='card-header text-white text-center py-4' style='background: linear-gradient(135deg, #f59e0b, #d97706);'>
                <i class='ti ti-clock-hour-4 fs-1 mb-2'></i>
                <h4 class='fw-bold mb-0'>Cadastro em Análise</h4>
                <p class='mb-0 opacity-75'>Sua solicitação de motorista foi enviada</p>
            </div>
            <div class='card-body p-4 bg-white text-center'>
                <div class='alert alert-info border-0 shadow-sm mb-4 text-start d-flex align-items-start'>
                    <i class='ti ti-info-circle fs-4 me-3 mt-1'></i>
                    <div>
                        <strong>Aguardando Homologação:</strong> Seu perfil de motorista foi enviado à gestão de frotas e está em fase de homologação e aprovação.
                    </div>
                </div>
                
                <p class='text-muted mb-4'>Assim que um gestor de frotas analisar e liberar seu cadastro, você receberá uma notificação pelo chamado correspondente e seu acesso a reservas de veículos será desbloqueado.</p>
                
                <div class='d-grid gap-2'>
                    <a href='index.php' class='btn btn-primary fw-bold' style='background-color:#1e3a8a; border:none;'>
                        <i class='ti ti-arrow-left me-2'></i> Voltar ao Painel
                    </a>
                </div>
            </div>
        </div>";
    }
    
    echo "  </div>";
    echo "</div>";
    
    Html::footer();
    exit;
}

if (isset($_POST["add"])) {
    $schedule->check(-1, CREATE, $_POST);
    $schedule->add($_POST);
    Session::addMessageAfterRedirect('Reserva solicitada com sucesso!', false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["delete"])) {
    $schedule->check($_POST["id"], DELETE);
    $schedule->delete($_POST);
    Session::addMessageAfterRedirect('Reserva cancelada com sucesso.', false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["restore"])) {
    $schedule->check($_POST["id"], DELETE);
    $schedule->restore($_POST);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["purge"])) {
    $schedule->check($_POST["id"], PURGE);
    $schedule->delete($_POST, 1);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else if (isset($_POST["update"])) {
    $schedule->check($_POST["id"], UPDATE);
    $schedule->update($_POST);
    Session::addMessageAfterRedirect('Reserva atualizada com sucesso!', false, INFO);
    Html::redirect($CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/index.php');

} else {
    $schedule->checkGlobal(READ);

    Html::header(
        PluginVehicleschedulerSchedule::getTypeName(1),
        $_SERVER['PHP_SELF'],
        'plugins',
        'pluginvehicleschedulerschedule'
    );
    
    $id = isset($_GET["id"]) ? $_GET["id"] : 0;
    $options = $_GET;
    $options['id'] = $id;
    $schedule->display($options);

    Html::footer();
}
