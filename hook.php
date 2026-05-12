<?php
/**
 * Installation / Update / Uninstallation hooks
 * Vehicle Scheduler - V2
 */

function plugin_vehiclescheduler_install()
{
    global $DB;

    include_once(Plugin::getPhpDir('vehiclescheduler') . '/inc/profile.class.php');

    $migration         = new Migration(PLUGIN_VEHICLESCHEDULER_VERSION);
    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();

    // =========================================================
    // VEÍCULOS
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_vehicles` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `entities_id` int unsigned NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `plate` varchar(50) NOT NULL DEFAULT '',
            `brand` varchar(100) NOT NULL DEFAULT '',
            `model` varchar(100) NOT NULL DEFAULT '',
            `year` int NOT NULL DEFAULT '2020',
            `seats` int NOT NULL DEFAULT '5',
            `required_cnh_category` varchar(10) NOT NULL DEFAULT 'B',
            `is_active` tinyint NOT NULL DEFAULT '1',
            `comment` text,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `name` (`name`),
            KEY `plate` (`plate`),
            KEY `entities_id` (`entities_id`),
            KEY `is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // MOTORISTAS
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_drivers` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `entities_id` int unsigned NOT NULL DEFAULT '0',
            `is_recursive` tinyint NOT NULL DEFAULT '0',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `groups_id` int unsigned NOT NULL DEFAULT '0',
            `registration` varchar(50) NOT NULL DEFAULT '',
            `cnh_category` varchar(10) NOT NULL DEFAULT 'B',
            `cnh_expiry` date DEFAULT NULL,
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
            KEY `entities_id` (`entities_id`),
            KEY `users_id` (`users_id`),
            KEY `groups_id` (`groups_id`),
            KEY `cnh_expiry` (`cnh_expiry`),
            KEY `is_active` (`is_active`),
            KEY `is_approved` (`is_approved`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // RESERVAS / AGENDAMENTOS
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_schedules` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `plugin_vehiclescheduler_vehicles_id` int unsigned NOT NULL DEFAULT '0',
            `plugin_vehiclescheduler_drivers_id` int unsigned NOT NULL DEFAULT '0',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `entities_id` int unsigned NOT NULL DEFAULT '0',
            `tickets_id` int unsigned NOT NULL DEFAULT '0',
            `groups_id` int unsigned NOT NULL DEFAULT '0',
            `status` int NOT NULL DEFAULT '1',
            `approved_by` int unsigned DEFAULT NULL,
            `approval_date` timestamp NULL DEFAULT NULL,
            `rejection_justification` text,
            `begin_date` timestamp NULL DEFAULT NULL,
            `end_date` timestamp NULL DEFAULT NULL,
            `destination` varchar(255) NOT NULL DEFAULT '',
            `purpose` text,
            `passengers` int NOT NULL DEFAULT '1',
            `department` varchar(255) NOT NULL DEFAULT '',
            `contact_phone` varchar(50) NOT NULL DEFAULT '',
            `odometer_start` int unsigned DEFAULT NULL,
            `odometer_end` int unsigned DEFAULT NULL,
            `distance_traveled` int unsigned NOT NULL DEFAULT '0',
            `has_departure_damage` tinyint NOT NULL DEFAULT '0',
            `has_arrival_damage` tinyint NOT NULL DEFAULT '0',
            `comment` text,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_vehiclescheduler_vehicles_id` (`plugin_vehiclescheduler_vehicles_id`),
            KEY `users_id` (`users_id`),
            KEY `entities_id` (`entities_id`),
            KEY `status` (`status`),
            KEY `approved_by` (`approved_by`),
            KEY `begin_date` (`begin_date`),
            KEY `groups_id` (`groups_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // INCIDENTES
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_incidents` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL DEFAULT '',
            `plugin_vehiclescheduler_schedules_id` int unsigned NOT NULL DEFAULT '0',
            `plugin_vehiclescheduler_vehicles_id` int unsigned NOT NULL DEFAULT '0',
            `plugin_vehiclescheduler_drivers_id` int unsigned NOT NULL DEFAULT '0',
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `entities_id` int unsigned NOT NULL DEFAULT '0',
            `groups_id` int unsigned NOT NULL DEFAULT '0',
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
            KEY `plugin_vehiclescheduler_schedules_id` (`plugin_vehiclescheduler_schedules_id`),
            KEY `plugin_vehiclescheduler_vehicles_id` (`plugin_vehiclescheduler_vehicles_id`),
            KEY `users_id` (`users_id`),
            KEY `entities_id` (`entities_id`),
            KEY `status` (`status`),
            KEY `groups_id` (`groups_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // MANUTENÇÕES / REVISÕES
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_maintenances` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `plugin_vehiclescheduler_vehicles_id` int unsigned NOT NULL DEFAULT '0',
            `plugin_vehiclescheduler_incidents_id` int unsigned NOT NULL DEFAULT '0',
            `type` int NOT NULL DEFAULT '1',
            `status` int NOT NULL DEFAULT '1',
            `scheduled_date` date DEFAULT NULL,
            `completion_date` date DEFAULT NULL,
            `supplier` varchar(255) NOT NULL DEFAULT '',
            `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
            `mileage` int NOT NULL DEFAULT '0',
            `trigger_type` tinyint NOT NULL DEFAULT '1' COMMENT '1=Manual, 2=KM, 3=Data, 4=KM ou Data',
            `due_mileage` int unsigned NOT NULL DEFAULT '0',
            `due_date` date DEFAULT NULL,
            `is_recurring` tinyint NOT NULL DEFAULT '0',
            `recurrence_km` int unsigned NOT NULL DEFAULT '0',
            `recurrence_days` int unsigned NOT NULL DEFAULT '0',
            `description` text,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_vehiclescheduler_vehicles_id` (`plugin_vehiclescheduler_vehicles_id`),
            KEY `status` (`status`),
            KEY `scheduled_date` (`scheduled_date`),
            KEY `due_date` (`due_date`),
            KEY `due_mileage` (`due_mileage`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // SINISTROS
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_insuranceclaims` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
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
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // MULTAS
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_driverfines` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `plugin_vehiclescheduler_drivers_id` int unsigned NOT NULL DEFAULT '0',
            `plugin_vehiclescheduler_vehicles_id` int unsigned NOT NULL DEFAULT '0',
            `fine_date` date DEFAULT NULL,
            `violation_code` varchar(20) NOT NULL DEFAULT '',
            `violation_split` varchar(20) NOT NULL DEFAULT '',
            `legal_basis` varchar(255) NOT NULL DEFAULT '',
            `offender` varchar(100) NOT NULL DEFAULT '',
            `authority` varchar(100) NOT NULL DEFAULT '',
            `severity` int NOT NULL DEFAULT '1',
            `status` int NOT NULL DEFAULT '1',
            `description` text,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_vehiclescheduler_drivers_id` (`plugin_vehiclescheduler_drivers_id`),
            KEY `plugin_vehiclescheduler_vehicles_id` (`plugin_vehiclescheduler_vehicles_id`),
            KEY `violation_code` (`violation_code`),
            KEY `status` (`status`),
            KEY `fine_date` (`fine_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // TEMAS
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_themes` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `users_id` int unsigned NOT NULL DEFAULT '0',
            `theme_code` varchar(50) NOT NULL DEFAULT 'purple-dark',
            `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `date_mod` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `users_id` (`users_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // CONFIGURAÇÕES
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_configs` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `config_key` varchar(100) NOT NULL,
            `config_value` varchar(255) NOT NULL DEFAULT '',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `config_key` (`config_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // CHECKLISTS - TEMPLATES
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_checklists` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `entities_id` int unsigned NOT NULL DEFAULT '0',
            `name` varchar(255) NOT NULL,
            `description` text,
            `checklist_type` tinyint NOT NULL DEFAULT '1' COMMENT '1=Saída, 2=Chegada, 3=Ambos',
            `is_active` tinyint NOT NULL DEFAULT '1',
            `is_mandatory` tinyint NOT NULL DEFAULT '1',
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `entities_id` (`entities_id`),
            KEY `is_active` (`is_active`),
            KEY `checklist_type` (`checklist_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // CHECKLISTS - ITENS
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_checklistitems` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `plugin_vehiclescheduler_checklists_id` int unsigned NOT NULL,
            `description` text NOT NULL,
            `item_type` tinyint NOT NULL DEFAULT '1' COMMENT '1=Checkbox, 2=Text, 3=Number, 4=Photo, 5=Signature',
            `is_mandatory` tinyint NOT NULL DEFAULT '1',
            `position` int NOT NULL DEFAULT '0',
            `help_text` varchar(255) DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            `date_mod` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_vehiclescheduler_checklists_id` (`plugin_vehiclescheduler_checklists_id`),
            KEY `position` (`position`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // CHECKLISTS - RESPOSTAS
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_checklistresponses` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `plugin_vehiclescheduler_schedules_id` int unsigned NOT NULL,
            `plugin_vehiclescheduler_checklists_id` int unsigned NOT NULL,
            `users_id` int unsigned NOT NULL,
            `response_type` varchar(20) NOT NULL COMMENT 'departure ou arrival',
            `odometer` int unsigned DEFAULT NULL,
            `has_damage` tinyint NOT NULL DEFAULT '0',
            `damage_notes` text,
            `fuel_level` tinyint DEFAULT NULL,
            `completed_at` timestamp NULL DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_vehiclescheduler_schedules_id` (`plugin_vehiclescheduler_schedules_id`),
            KEY `plugin_vehiclescheduler_checklists_id` (`plugin_vehiclescheduler_checklists_id`),
            KEY `users_id` (`users_id`),
            KEY `response_type` (`response_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // CHECKLISTS - RESPOSTAS DOS ITENS
    // =========================================================
    $DB->doQuery("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_vehiclescheduler_checklistresponse_items` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `plugin_vehiclescheduler_checklistresponses_id` int unsigned NOT NULL,
            `plugin_vehiclescheduler_checklistitems_id` int unsigned NOT NULL,
            `response_value` text,
            `photo_path` varchar(255) DEFAULT NULL,
            `date_creation` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `plugin_vehiclescheduler_checklistresponses_id` (`plugin_vehiclescheduler_checklistresponses_id`),
            KEY `plugin_vehiclescheduler_checklistitems_id` (`plugin_vehiclescheduler_checklistitems_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation};
    ");

    // =========================================================
    // ALTERAÇÕES CONDICIONAIS PARA AMBIENTES JÁ EXISTENTES
    // =========================================================

    // schedules
    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_incidents',
        'plugin_vehiclescheduler_schedules_id',
        "`plugin_vehiclescheduler_schedules_id` int unsigned NOT NULL DEFAULT '0' AFTER `name`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_vehicles',
        'required_cnh_category',
        "`required_cnh_category` varchar(10) NOT NULL DEFAULT 'B' AFTER `seats`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_schedules',
        'approved_by',
        "`approved_by` int unsigned DEFAULT NULL AFTER `status`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_schedules',
        'approval_date',
        "`approval_date` timestamp NULL DEFAULT NULL AFTER `approved_by`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_schedules',
        'rejection_justification',
        "`rejection_justification` text AFTER `approval_date`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_schedules',
        'odometer_start',
        "`odometer_start` int unsigned DEFAULT NULL AFTER `contact_phone`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_schedules',
        'odometer_end',
        "`odometer_end` int unsigned DEFAULT NULL AFTER `odometer_start`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_schedules',
        'distance_traveled',
        "`distance_traveled` int unsigned NOT NULL DEFAULT '0' AFTER `odometer_end`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_schedules',
        'has_departure_damage',
        "`has_departure_damage` tinyint NOT NULL DEFAULT '0' AFTER `distance_traveled`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_schedules',
        'has_arrival_damage',
        "`has_arrival_damage` tinyint NOT NULL DEFAULT '0' AFTER `has_departure_damage`"
    );

    if (
        $DB->tableExists('glpi_plugin_vehiclescheduler_schedules')
        && !plugin_vehiclescheduler_index_exists('glpi_plugin_vehiclescheduler_schedules', 'approved_by')
    ) {
        $DB->doQuery(
            "ALTER TABLE `glpi_plugin_vehiclescheduler_schedules` ADD KEY `approved_by` (`approved_by`)"
        );
    }

    // checklistresponses
    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_checklistresponses',
        'odometer',
        "`odometer` int unsigned DEFAULT NULL AFTER `response_type`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_checklistresponses',
        'has_damage',
        "`has_damage` tinyint NOT NULL DEFAULT '0' AFTER `odometer`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_checklistresponses',
        'damage_notes',
        "`damage_notes` text AFTER `has_damage`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_checklistresponses',
        'fuel_level',
        "`fuel_level` tinyint DEFAULT NULL AFTER `damage_notes`"
    );

    // maintenances
    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_maintenances',
        'trigger_type',
        "`trigger_type` tinyint NOT NULL DEFAULT '1' COMMENT '1=Manual, 2=KM, 3=Data, 4=KM ou Data' AFTER `mileage`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_maintenances',
        'due_mileage',
        "`due_mileage` int unsigned NOT NULL DEFAULT '0' AFTER `trigger_type`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_maintenances',
        'due_date',
        "`due_date` date DEFAULT NULL AFTER `due_mileage`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_maintenances',
        'is_recurring',
        "`is_recurring` tinyint NOT NULL DEFAULT '0' AFTER `due_date`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_maintenances',
        'recurrence_km',
        "`recurrence_km` int unsigned NOT NULL DEFAULT '0' AFTER `is_recurring`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_maintenances',
        'recurrence_days',
        "`recurrence_days` int unsigned NOT NULL DEFAULT '0' AFTER `recurrence_km`"
    );

    // driver fines / RENAINF reference fields
    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_driverfines',
        'violation_code',
        "`violation_code` varchar(20) NOT NULL DEFAULT '' AFTER `fine_date`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_driverfines',
        'violation_split',
        "`violation_split` varchar(20) NOT NULL DEFAULT '' AFTER `violation_code`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_driverfines',
        'legal_basis',
        "`legal_basis` varchar(255) NOT NULL DEFAULT '' AFTER `violation_split`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_driverfines',
        'offender',
        "`offender` varchar(100) NOT NULL DEFAULT '' AFTER `legal_basis`"
    );

    plugin_vehiclescheduler_add_column_if_missing(
        'glpi_plugin_vehiclescheduler_driverfines',
        'authority',
        "`authority` varchar(100) NOT NULL DEFAULT '' AFTER `offender`"
    );

    if (
        $DB->tableExists('glpi_plugin_vehiclescheduler_driverfines')
        && !plugin_vehiclescheduler_index_exists('glpi_plugin_vehiclescheduler_driverfines', 'violation_code')
    ) {
        $DB->doQuery(
            "ALTER TABLE `glpi_plugin_vehiclescheduler_driverfines` ADD KEY `violation_code` (`violation_code`)"
        );
    }

    // =========================================================
    // ACL V2
    // =========================================================
    PluginVehicleschedulerProfile::install($migration);

    $migration->executeMigration();

    return true;
}

/**
 * Uninstall hook
 */
function plugin_vehiclescheduler_uninstall()
{
    global $DB;

    include_once(Plugin::getPhpDir('vehiclescheduler') . '/inc/profile.class.php');

    PluginVehicleschedulerProfile::uninstall();

    $tables = [
        'glpi_plugin_vehiclescheduler_checklistresponse_items',
        'glpi_plugin_vehiclescheduler_checklistresponses',
        'glpi_plugin_vehiclescheduler_checklistitems',
        'glpi_plugin_vehiclescheduler_checklists',
        'glpi_plugin_vehiclescheduler_configs',
        'glpi_plugin_vehiclescheduler_themes',
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

/**
 * Adiciona coluna apenas se ainda não existir.
 */
function plugin_vehiclescheduler_add_column_if_missing(
    string $table,
    string $field,
    string $definition
): void {
    global $DB;

    if (!$DB->tableExists($table)) {
        return;
    }

    if ($DB->fieldExists($table, $field)) {
        return;
    }

    $DB->doQuery("ALTER TABLE `$table` ADD COLUMN $definition");
}


/**
 * Verifica se um índice já existe na tabela.
 */
function plugin_vehiclescheduler_index_exists(string $table, string $index): bool
{
    global $DB;

    if (!$DB->tableExists($table)) {
        return false;
    }

    $query = $DB->doQuery(
        "SHOW INDEX FROM `$table` WHERE `Key_name` = '" . $DB->escape($index) . "'"
    );

    if ($query === false) {
        return false;
    }

    return $DB->numrows($query) > 0;
}
