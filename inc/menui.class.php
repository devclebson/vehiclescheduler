<?php

/**
 * Requester menu definition for future use.
 *
 * The requester portal is currently accessed from a Helpdesk Home external card,
 * not from a plugin menu hook.
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerMenui extends CommonGLPI
{
    /**
     * ACL right used by the requester portal.
     *
     * @var string
     */
    public static $rightname = 'plugin_vehiclescheduler_portal';
}