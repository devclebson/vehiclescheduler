<?php

/**
 * Main management menu entry for Vehicle Scheduler.
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerMenu extends CommonGLPI
{
    /**
     * ACL right used by the management menu.
     *
     * @var string
     */
    public static $rightname = 'plugin_vehiclescheduler_management';

    /**
     * Returns the management menu content for the Tools section.
     *
     * @return array|false
     */
    public static function getMenuContent()
    {
        if (!PluginVehicleschedulerProfile::canViewManagement()) {
            return false;
        }

        return [
            'title' => 'Gestão de Frotas',
            'page'  => '/plugins/vehiclescheduler/front/management.php',
            'icon'  => 'ti ti-car-suv',
            'links' => [
                'search' => '/plugins/vehiclescheduler/front/management.php',
            ],
        ];
    }
}
