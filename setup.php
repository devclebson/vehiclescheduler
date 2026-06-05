<?php
/**
 * Vehicle Scheduler v4.0.0 FINAL
 * Plugin completo de gestão de frotas para GLPI 10.x
 */

define('PLUGIN_VEHICLESCHEDULER_VERSION', '4.0.0');

function plugin_version_vehiclescheduler() {
    return [
        'name'           => 'Vehicle Scheduler',
        'version'        => PLUGIN_VEHICLESCHEDULER_VERSION,
        'author'         => 'Fleet Management Team',
        'license'        => 'GPLv2+',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => '10.0.0',
                'max' => '11.1.99'
            ]
        ]
    ];
}

function plugin_vehiclescheduler_check_prerequisites() {
    return true;
}

function plugin_vehiclescheduler_check_config() {
    return true;
}

function plugin_init_vehiclescheduler() {
    global $PLUGIN_HOOKS, $CFG_GLPI;

    $PLUGIN_HOOKS['csrf_compliant']['vehiclescheduler'] = true;

    $plugin = new Plugin();
    
    if ($plugin->isActivated('vehiclescheduler')) {
        
        // ===================================================================
        // CORREÇÃO CRÍTICA #1: PERMISSÕES PARA SELF-SERVICE
        // Permite que usuários self-service acessem formulários
        // ===================================================================
        if (isset($_SESSION['glpiactiveprofile'])) {
            if (!isset($_SESSION['glpiactiveprofile']['plugin_vehiclescheduler'])) {
                $_SESSION['glpiactiveprofile']['plugin_vehiclescheduler'] = READ | CREATE | UPDATE | DELETE;
            }
        }
        
        // ═══════════════════════════════════════════════════════════════
        // ESTRUTURA DE MENUS
        // ═══════════════════════════════════════════════════════════════
        
        // MenuI: Requerentes (Assistência e Self-Service)
        $PLUGIN_HOOKS['menu_toadd']['vehiclescheduler']['helpdesk'] = 'PluginVehicleschedulerMenui';
        $PLUGIN_HOOKS['redefine_menus']['vehiclescheduler'] = 'plugin_vehiclescheduler_redefine_menus';
        Plugin::registerClass('PluginVehicleschedulerMenui');
        
        // MenuG: Gestores (Ferramentas)
        $PLUGIN_HOOKS['menu_toadd']['vehiclescheduler']['tools'] = 'PluginVehicleschedulerMenug';
        Plugin::registerClass('PluginVehicleschedulerMenug');

        // Registrar todas as classes
        Plugin::registerClass('PluginVehicleschedulerSchedule',       ['notificationtemplates_types' => true]);
        Plugin::registerClass('PluginVehicleschedulerVehicle',        ['notificationtemplates_types' => true]);
        Plugin::registerClass('PluginVehicleschedulerDriver',         ['notificationtemplates_types' => true]);
        Plugin::registerClass('PluginVehicleschedulerDriverfine');
        Plugin::registerClass('PluginVehicleschedulerIncident',       ['notificationtemplates_types' => true]);
        Plugin::registerClass('PluginVehicleschedulerMaintenance',    ['notificationtemplates_types' => true]);
        Plugin::registerClass('PluginVehicleschedulerInsuranceclaim', ['notificationtemplates_types' => true]);
        Plugin::registerClass('PluginVehicleschedulerProfile',        ['addtabon' => 'Profile']);
        Plugin::registerClass('PluginVehicleschedulerTheme');
        
        // Removido página de configuração (temas)
    }
}
