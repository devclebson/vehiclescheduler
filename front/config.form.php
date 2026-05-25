<?php

include_once __DIR__ . '/../inc/common.inc.php';

$managementUrl = plugin_vehiclescheduler_get_front_url('management.php');
$formUrl = plugin_vehiclescheduler_get_front_url('config.form.php');
$supportedLocales = PluginVehicleschedulerConfig::getSupportedLocales();

if (!PluginVehicleschedulerProfile::canViewManagement()) {
    plugin_vehiclescheduler_flash_error(__('You are not allowed to access plugin settings.', 'vehiclescheduler'));
    Html::redirect($managementUrl);
    exit;
}

if (isset($_POST['save_config'])) {
    if (!PluginVehicleschedulerProfile::canViewManagement()) {
        plugin_vehiclescheduler_flash_error(__('You are not allowed to change plugin settings.', 'vehiclescheduler'));
        Html::redirect($managementUrl);
        exit;
    }

    $saved = PluginVehicleschedulerConfig::setBool(
        'auto_departure_checklist_after_approval',
        PluginVehicleschedulerInput::bool($_POST, 'auto_departure_checklist_after_approval', false) === 1
    );

    if ($saved) {
        plugin_vehiclescheduler_flash_success(__('Settings saved successfully.', 'vehiclescheduler'));
    } else {
        plugin_vehiclescheduler_flash_error(__('Unable to save plugin settings.', 'vehiclescheduler'));
    }

    Html::redirect($managementUrl);
    exit;
}

if (isset($_POST['set_plugin_locale'])) {
    if (!PluginVehicleschedulerProfile::canViewManagement()) {
        plugin_vehiclescheduler_flash_error(__('You are not allowed to change plugin settings.', 'vehiclescheduler'));
        Html::redirect($managementUrl);
        exit;
    }

    $locale = PluginVehicleschedulerInput::enum(
        $_POST,
        'set_plugin_locale',
        array_keys($supportedLocales),
        PluginVehicleschedulerConfig::getPluginLocale()
    );

    if (PluginVehicleschedulerConfig::setPluginLocale($locale)) {
        plugin_vehiclescheduler_flash_success(__('Plugin language saved successfully.', 'vehiclescheduler'));
    } else {
        plugin_vehiclescheduler_flash_error(__('Unable to save plugin language.', 'vehiclescheduler'));
    }

    Html::redirect($formUrl);
    exit;
}

$autoDepartureChecklist = PluginVehicleschedulerConfig::shouldAutoOpenDepartureChecklistAfterApproval();
$currentLocale = PluginVehicleschedulerConfig::getPluginLocale();

Html::header(__('Plugin settings', 'vehiclescheduler'), $formUrl, 'tools', PluginVehicleschedulerMenu::class, 'management');
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
                <h2><?= htmlspecialchars(__('Plugin settings', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="vs-page-subtitle"><?= htmlspecialchars(__('Operational settings for SisViaturas behavior.', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>

        <button type="submit" form="vsPluginConfigForm" name="save_config" class="vs-btn-add">
            <i class="ti ti-device-floppy"></i>
            <span><?= htmlspecialchars(__('Save', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?></span>
        </button>
    </div>
</div>

<div class="vs-driver-grid">

    <form id="vsPluginConfigForm" method="post" action="<?= htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">

        <section class="vs-driver-grid__toolbar">
            <label class="vs-driver-grid__filter">
                <span><?= htmlspecialchars(__('Checklist', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?></span>
                <div class="vs-config-toggle">
                    <input type="checkbox" name="auto_departure_checklist_after_approval" value="1"<?= $autoDepartureChecklist ? ' checked' : '' ?>>
                    <div>
                        <strong><?= htmlspecialchars(__('Automatic departure checklist', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <small><?= htmlspecialchars(__('Open the first checklist after reservation approval.', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </div>
            </label>
        </section>

        <section class="vs-driver-grid__toolbar vs-config-section">
            <div class="vs-config-section__intro">
                <span><?= htmlspecialchars(__('Language', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= htmlspecialchars(__('Plugin language', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars(__('Choose the language used by SisViaturas screens.', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?></small>
            </div>

            <div class="vs-language-buttons" role="group" aria-label="<?= htmlspecialchars(__('Plugin language', 'vehiclescheduler'), ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($supportedLocales as $localeCode => $locale): ?>
                    <?php $isCurrentLocale = $localeCode === $currentLocale; ?>
                    <button
                        type="submit"
                        name="set_plugin_locale"
                        value="<?= htmlspecialchars($localeCode, ENT_QUOTES, 'UTF-8') ?>"
                        class="vs-language-button<?= $isCurrentLocale ? ' vs-language-button--active' : '' ?>"
                        aria-pressed="<?= $isCurrentLocale ? 'true' : 'false' ?>"
                    >
                        <span><?= htmlspecialchars($locale['native'], ENT_QUOTES, 'UTF-8') ?></span>
                        <small><?= htmlspecialchars($localeCode, ENT_QUOTES, 'UTF-8') ?></small>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>
    </form>
</div>
<?php Html::footer(); ?>
