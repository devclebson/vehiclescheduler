<?php

include_once __DIR__ . '/../inc/common.inc.php';

$managementUrl = plugin_vehiclescheduler_get_front_url('management.php');
$formUrl = plugin_vehiclescheduler_get_front_url('config.form.php');

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    plugin_vehiclescheduler_flash_error('Você não tem permissão para acessar as configurações do plugin.');
    Html::redirect($managementUrl);
    exit;
}

if (isset($_POST['save_config'])) {
    if (!PluginVehicleschedulerProfile::canViewManagement()) {
        plugin_vehiclescheduler_flash_error('Você não tem permissão para alterar as configurações do plugin.');
        Html::redirect($managementUrl);
        exit;
    }

    $saved = PluginVehicleschedulerConfig::setBool(
        'auto_departure_checklist_after_approval',
        PluginVehicleschedulerInput::bool($_POST, 'auto_departure_checklist_after_approval', false) === 1
    );

    if ($saved) {
        Session::addMessageAfterRedirect('Configurações salvas com sucesso.', false, INFO);
    } else {
        plugin_vehiclescheduler_flash_error('Não foi possível salvar as configurações do plugin.');
    }

    Html::redirect($managementUrl);
    exit;
}

$autoDepartureChecklist = PluginVehicleschedulerConfig::shouldAutoOpenDepartureChecklistAfterApproval();

Html::header('Configurações do Plugin', $formUrl, 'tools', PluginVehicleschedulerMenu::class, 'management');
plugin_vehiclescheduler_load_css([
    'css/pages/driver-grid.css',
]);
plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_render_back_to_management();
?>
<div class="vs-page-header">
    <div class="vs-header-content">
        <div class="vs-header-title">
            <div class="vs-header-icon-wrapper">
                <i class="ti ti-settings vs-header-icon"></i>
            </div>
            <div>
                <h2>Configurações do Plugin</h2>
                <p class="vs-page-subtitle">Flags operacionais para ajustar o comportamento do SisViaturas.</p>
            </div>
        </div>

        <button type="submit" form="vsPluginConfigForm" name="save_config" class="vs-btn-add">
            <i class="ti ti-device-floppy"></i>
            <span>Salvar</span>
        </button>
    </div>
</div>

<div class="vs-driver-grid">

    <form id="vsPluginConfigForm" method="post" action="<?= htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">

        <section class="vs-driver-grid__toolbar">
            <label class="vs-driver-grid__filter">
                <span>Checklist</span>
                <div class="vs-config-toggle">
                    <input type="checkbox" name="auto_departure_checklist_after_approval" value="1"<?= $autoDepartureChecklist ? ' checked' : '' ?>>
                    <div>
                        <strong>Checklist de saída automático</strong>
                        <small>Abre o primeiro checklist após a aprovação da reserva.</small>
                    </div>
                </div>
            </label>
        </section>
    </form>
</div>
<?php Html::footer(); ?>
