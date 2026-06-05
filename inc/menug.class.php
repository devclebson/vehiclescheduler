<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * MenuG - Menu de Gestão em "Ferramentas"
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerMenug extends CommonDBTM {
    
    static $rightname = 'plugin_vehiclescheduler';
    
    static function getMenuContent() {
        global $CFG_GLPI;
        
        // Verificar se tem acesso à gestão (leitura ou escrita)
        if (!PluginVehicleschedulerProfile::canViewManagement()) {
            return false;
        }
        
        $menu = [
            'title' => 'Gestão de Frota',
            'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/management.php',
            'icon'  => 'ti ti-building-warehouse',
            'options' => [
                'dashboard' => [
                    'title' => 'Dashboard',
                    'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/management.php',
                    'icon'  => 'ti ti-layout-dashboard',
                ],
                'calendar' => [
                    'title' => 'Calendário',
                    'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/calendar.php',
                    'icon'  => 'ti ti-calendar',
                ],
                'reservations' => [
                    'title' => 'Reservas',
                    'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/schedule.php',
                    'icon'  => 'ti ti-calendar-event',
                ],
                'vehicles' => [
                    'title' => 'Veículos',
                    'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/vehicle.php',
                    'icon'  => 'ti ti-car',
                ],
                'drivers' => [
                    'title' => 'Motoristas',
                    'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/driver.php',
                    'icon'  => 'ti ti-steering-wheel',
                ],
                'incidents' => [
                    'title' => 'Incidentes',
                    'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/incident.php',
                    'icon'  => 'ti ti-alert-triangle',
                ],
                'maintenances' => [
                    'title' => 'Manutenções',
                    'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/maintenance.php',
                    'icon'  => 'ti ti-tool',
                ],
                'insurance' => [
                    'title' => 'Sinistros',
                    'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/insuranceclaim.php',
                    'icon'  => 'ti ti-shield-check',
                ],
                'fines' => [
                    'title' => 'Multas',
                    'page'  => $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/front/fines.php',
                    'icon'  => 'ti ti-ticket',
                ],
            ],
        ];
        
        return $menu;
    }
}
