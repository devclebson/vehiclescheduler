<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * MenuI - Menu dos Requerentes em "Assistência"
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerMenui extends CommonDBTM {
    
    static $rightname = 'plugin_vehiclescheduler';
    
    static function getMenuContent() {
        global $CFG_GLPI;
        
        // Verificar permissão de acesso ao portal
        if (!PluginVehicleschedulerProfile::canAccessRequester()) {
            return false;
        }
        
        $menu = [
            'title' => 'Reserva de Frota',
            'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/requester.php',
            'icon'  => 'ti ti-car',
        ];
        
        return $menu;
    }
}
