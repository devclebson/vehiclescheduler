<?php

/**
 * Vehicle Scheduler plugin setup.
 */

define('PLUGIN_VEHICLESCHEDULER_VERSION', '2.0.5');
define('PLUGIN_VEHICLESCHEDULER_MIN_GLPI_VERSION', '11.0.0');
define('PLUGIN_VEHICLESCHEDULER_MAX_GLPI_VERSION', '12.0.0');

/**
 * Initialize plugin hooks.
 *
 * @return void
 */
function plugin_init_vehiclescheduler(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['vehiclescheduler'] = true;

    Plugin::registerClass('PluginVehicleschedulerProfile', [
        'addtabon' => ['Profile']
    ]);
    Plugin::registerClass('PluginVehicleschedulerMenu');
    Plugin::registerClass('PluginVehicleschedulerMenug');

    $PLUGIN_HOOKS['change_profile']['vehiclescheduler'] = [
        'PluginVehicleschedulerProfile',
        'changeProfile'
    ];

    /**
     * Registra somente o menu de gestão em Ferramentas.
     *
     * O portal requester continuará sendo acessado por card externo.
     */
    $PLUGIN_HOOKS['menu_toadd']['vehiclescheduler'] = [
        'tools' => 'PluginVehicleschedulerMenu',
    ];

    Plugin::registerClass('PluginVehicleschedulerDashboard');
    Plugin::registerClass('PluginVehicleschedulerVehicle');
    Plugin::registerClass('PluginVehicleschedulerDriver');
    Plugin::registerClass('PluginVehicleschedulerSchedule');
    Plugin::registerClass('PluginVehicleschedulerMaintenance');
    Plugin::registerClass('PluginVehicleschedulerIncident');
    Plugin::registerClass('PluginVehicleschedulerInsuranceclaim');
    Plugin::registerClass('PluginVehicleschedulerDriverfine');
    Plugin::registerClass('PluginVehicleschedulerChecklist');
    Plugin::registerClass('PluginVehicleschedulerChecklistitem');
    Plugin::registerClass('PluginVehicleschedulerVehiclereport');
    Plugin::registerClass('PluginVehicleschedulerTheme');
    Plugin::registerClass('PluginVehicleschedulerConfig');
}

/**
 * Plugin metadata.
 *
 * @return array<string,mixed>
 */
function plugin_version_vehiclescheduler(): array
{
    return [
        'name'         => 'Vehicle Scheduler',
        'version'      => PLUGIN_VEHICLESCHEDULER_VERSION,
        'author'       => 'Vinicius Lopes <generalvini@gmail.com> (@ViniciusHonorato)',
        'license'      => 'PolyForm Noncommercial 1.0.0',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_VEHICLESCHEDULER_MIN_GLPI_VERSION,
                'max' => PLUGIN_VEHICLESCHEDULER_MAX_GLPI_VERSION,
            ],
            'php' => [
                'min' => '8.1'
            ]
        ]
    ];
}

/**
 * Check plugin prerequisites.
 *
 * @return bool
 */
function plugin_vehiclescheduler_check_prerequisites(): bool
{
    if (version_compare(GLPI_VERSION, PLUGIN_VEHICLESCHEDULER_MIN_GLPI_VERSION, '<')) {
        echo "Este plugin requer GLPI >= " . PLUGIN_VEHICLESCHEDULER_MIN_GLPI_VERSION;
        return false;
    }

    if (version_compare(GLPI_VERSION, PLUGIN_VEHICLESCHEDULER_MAX_GLPI_VERSION, '>=')) {
        echo "Este plugin requer GLPI < " . PLUGIN_VEHICLESCHEDULER_MAX_GLPI_VERSION;
        return false;
    }

    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        echo "Este plugin requer PHP >= 8.1";
        return false;
    }

    return true;
}

/**
 * Check runtime configuration.
 *
 * @param bool $verbose
 * @return bool
 */
function plugin_vehiclescheduler_check_config(bool $verbose = false): bool
{
    return true;
}
