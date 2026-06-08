<?php
/**
 * Installation/Uninstallation hooks
 */

function plugin_vehiclescheduler_install() {
    global $DB;
    
    $migration = new Migration(PLUGIN_VEHICLESCHEDULER_VERSION);
    $default_charset = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();
    
    // Veículos
    $DB->doQuery("CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_vehicles` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL DEFAULT '',
        `entities_id` int unsigned NOT NULL DEFAULT '0',
        `is_recursive` tinyint NOT NULL DEFAULT '0',
        `plate` varchar(50) NOT NULL DEFAULT '',
        `brand` varchar(100) NOT NULL DEFAULT '',
        `model` varchar(100) NOT NULL DEFAULT '',
        `year` int NOT NULL DEFAULT '2020',
        `seats` int NOT NULL DEFAULT '5',
        `mileage` int NOT NULL DEFAULT '0',
        `is_active` tinyint NOT NULL DEFAULT '1',
        `comment` text,
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `plate` (`plate`),
        KEY `entities_id` (`entities_id`),
        KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};");
    
    // Motoristas (COM APROVAÇÃO E VÍNCULO DE USUÁRIO)
    $DB->doQuery("CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_drivers` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL DEFAULT '',
        `users_id` int unsigned NOT NULL DEFAULT '0',
        `entities_id` int unsigned NOT NULL DEFAULT '0',
        `is_recursive` tinyint NOT NULL DEFAULT '0',
        `registration` varchar(50) NOT NULL DEFAULT '',
        `cnh_category` varchar(10) NOT NULL DEFAULT 'B',
        `cnh_expiry` date DEFAULT NULL,
        `department` varchar(255) NOT NULL DEFAULT '',
        `contact_phone` varchar(50) NOT NULL DEFAULT '',
        `is_active` tinyint NOT NULL DEFAULT '1',
        `is_approved` tinyint NOT NULL DEFAULT '1',
        `approved_by` int unsigned NOT NULL DEFAULT '0',
        `approval_date` timestamp NULL DEFAULT NULL,
        `comment` text,
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `users_id` (`users_id`),
        KEY `entities_id` (`entities_id`),
        KEY `cnh_expiry` (`cnh_expiry`),
        KEY `is_active` (`is_active`),
        KEY `is_approved` (`is_approved`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};");
    
    // Reservas (COM groups_id)
    $DB->doQuery("CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_schedules` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL DEFAULT '',
        `plugin_vehiclescheduler_vehicles_id` int unsigned NOT NULL DEFAULT '0',
        `plugin_vehiclescheduler_drivers_id` int unsigned NOT NULL DEFAULT '0',
        `users_id` int unsigned NOT NULL DEFAULT '0',
        `entities_id` int unsigned NOT NULL DEFAULT '0',
        `tickets_id` int unsigned NOT NULL DEFAULT '0',
        `groups_id` int unsigned NOT NULL DEFAULT '0',
        `status` int NOT NULL DEFAULT '1',
        `begin_date` timestamp NULL DEFAULT NULL,
        `end_date` timestamp NULL DEFAULT NULL,
        `destination` varchar(255) NOT NULL DEFAULT '',
        `purpose` text,
        `passengers` int NOT NULL DEFAULT '1',
        `department` varchar(255) NOT NULL DEFAULT '',
        `contact_phone` varchar(50) NOT NULL DEFAULT '',
        `comment` text,
        `real_begin_date` timestamp NULL DEFAULT NULL,
        `real_end_date` timestamp NULL DEFAULT NULL,
        `initial_mileage` int NOT NULL DEFAULT '0',
        `final_mileage` int NOT NULL DEFAULT '0',
        `initial_fuel` int NOT NULL DEFAULT '0',
        `final_fuel` int NOT NULL DEFAULT '0',
        `return_checklist` text,
        `return_comment` text,
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `plugin_vehiclescheduler_vehicles_id` (`plugin_vehiclescheduler_vehicles_id`),
        KEY `users_id` (`users_id`),
        KEY `entities_id` (`entities_id`),
        KEY `status` (`status`),
        KEY `begin_date` (`begin_date`),
        KEY `groups_id` (`groups_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};");
    
    // Incidentes (COM groups_id E tickets_id)
    $DB->doQuery("CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_incidents` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL DEFAULT '',
        `plugin_vehiclescheduler_vehicles_id` int unsigned NOT NULL DEFAULT '0',
        `plugin_vehiclescheduler_drivers_id` int unsigned NOT NULL DEFAULT '0',
        `users_id` int unsigned NOT NULL DEFAULT '0',
        `entities_id` int unsigned NOT NULL DEFAULT '0',
        `groups_id` int unsigned NOT NULL DEFAULT '0',
        `tickets_id` int unsigned NOT NULL DEFAULT '0',
        `incident_type` int NOT NULL DEFAULT '6',
        `status` int NOT NULL DEFAULT '1',
        `incident_date` timestamp NULL DEFAULT NULL,
        `location` varchar(255) NOT NULL DEFAULT '',
        `department` varchar(255) NOT NULL DEFAULT '',
        `contact_phone` varchar(50) NOT NULL DEFAULT '',
        `description` text,
        `needs_maintenance` tinyint NOT NULL DEFAULT '0',
        `needs_insurance` tinyint NOT NULL DEFAULT '0',
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `name` (`name`),
        KEY `plugin_vehiclescheduler_vehicles_id` (`plugin_vehiclescheduler_vehicles_id`),
        KEY `users_id` (`users_id`),
        KEY `entities_id` (`entities_id`),
        KEY `status` (`status`),
        KEY `groups_id` (`groups_id`),
        KEY `tickets_id` (`tickets_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};");
    
    // Manutenções
    $DB->doQuery("CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_maintenances` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL DEFAULT '',
        `plugin_vehiclescheduler_vehicles_id` int unsigned NOT NULL DEFAULT '0',
        `plugin_vehiclescheduler_incidents_id` int unsigned NOT NULL DEFAULT '0',
        `tickets_id` int unsigned NOT NULL DEFAULT '0',
        `type` int NOT NULL DEFAULT '1',
        `status` int NOT NULL DEFAULT '1',
        `scheduled_date` date DEFAULT NULL,
        `completion_date` date DEFAULT NULL,
        `supplier` varchar(255) NOT NULL DEFAULT '',
        `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
        `mileage` int NOT NULL DEFAULT '0',
        `description` text,
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `plugin_vehiclescheduler_vehicles_id` (`plugin_vehiclescheduler_vehicles_id`),
        KEY `status` (`status`),
        KEY `scheduled_date` (`scheduled_date`),
        KEY `tickets_id` (`tickets_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};");
    
    // Sinistros
    $DB->doQuery("CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_insuranceclaims` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL DEFAULT '',
        `plugin_vehiclescheduler_vehicles_id` int unsigned NOT NULL DEFAULT '0',
        `plugin_vehiclescheduler_incidents_id` int unsigned NOT NULL DEFAULT '0',
        `claim_number` varchar(100) NOT NULL DEFAULT '',
        `status` int NOT NULL DEFAULT '1',
        `opening_date` date DEFAULT NULL,
        `closing_date` date DEFAULT NULL,
        `insurance_company` varchar(255) NOT NULL DEFAULT '',
        `contact_name` varchar(255) NOT NULL DEFAULT '',
        `estimated_value` decimal(10,2) NOT NULL DEFAULT '0.00',
        `approved_value` decimal(10,2) NOT NULL DEFAULT '0.00',
        `description` text,
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `plugin_vehiclescheduler_vehicles_id` (`plugin_vehiclescheduler_vehicles_id`),
        KEY `status` (`status`),
        KEY `claim_number` (`claim_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};");
    
    // Multas
    $DB->doQuery("CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_driverfines` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `plugin_vehiclescheduler_drivers_id` int unsigned NOT NULL DEFAULT '0',
        `plugin_vehiclescheduler_vehicles_id` int unsigned NOT NULL DEFAULT '0',
        `fine_date` date DEFAULT NULL,
        `severity` int NOT NULL DEFAULT '1',
        `status` int NOT NULL DEFAULT '1',
        `tickets_id` int unsigned NOT NULL DEFAULT '0',
        `description` text,
        `date_creation` timestamp NULL DEFAULT NULL,
        `date_mod` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `plugin_vehiclescheduler_drivers_id` (`plugin_vehiclescheduler_drivers_id`),
        KEY `plugin_vehiclescheduler_vehicles_id` (`plugin_vehiclescheduler_vehicles_id`),
        KEY `status` (`status`),
        KEY `fine_date` (`fine_date`),
        KEY `tickets_id` (`tickets_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};");
    
    // Permissões
    $DB->doQuery("CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_profiles` (
        `id` int unsigned NOT NULL AUTO_INCREMENT,
        `profiles_id` int unsigned NOT NULL DEFAULT '0',
        `requester_access` tinyint NOT NULL DEFAULT '1',
        `management_access` varchar(1) NOT NULL DEFAULT '',
        `can_approve` tinyint NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `profiles_id` (`profiles_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};");
    
    // Removido Temas
    // Inicializar perfis
    include_once(Plugin::getPhpDir('vehiclescheduler') . "/inc/profile.class.php");
    PluginVehicleschedulerProfile::initProfile();
    
    return true;
}

function plugin_vehiclescheduler_uninstall() {
    global $DB;
    
    $tables = [
        'glpi_plugin_vehiclescheduler_profiles',
        'glpi_plugin_vehiclescheduler_driverfines',
        'glpi_plugin_vehiclescheduler_insuranceclaims',
        'glpi_plugin_vehiclescheduler_maintenances',
        'glpi_plugin_vehiclescheduler_incidents',
        'glpi_plugin_vehiclescheduler_schedules',
        'glpi_plugin_vehiclescheduler_drivers',
        'glpi_plugin_vehiclescheduler_vehicles',
    ];
    
    foreach ($tables as $table) {
        $DB->doQuery("DROP TABLE IF EXISTS `$table`");
    }
    
    return true;
}

function plugin_vehiclescheduler_redefine_menus($menus) {
    global $CFG_GLPI;

    // Apenas para interface simplificada (Self-service / Helpdesk)
    if (Session::getCurrentInterface() == "helpdesk" && Session::haveRight('plugin_vehiclescheduler', READ)) {
        
        // Remove do submenu de Plugins se estiver lá
        if (isset($menus['plugins']['content']['vehiclescheduler'])) {
            unset($menus['plugins']['content']['vehiclescheduler']);
        }
        
        // Adiciona como um menu principal na barra superior
        $menus['vehiclescheduler'] = [
            'default' => '/plugins/vehiclescheduler/front/index.php',
            'title'   => __('Reserva de Frota', 'vehiclescheduler'),
            'icon'    => 'ti ti-car',
            'page'    => '/plugins/vehiclescheduler/front/index.php',
        ];
    }
    
    return $menus;
}
