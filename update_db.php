<?php
require_once '/var/www/glpi/vendor/autoload.php';
$kernel = new \Glpi\Kernel\Kernel();
$kernel->boot();
global $DB;

// 1. Verificar coluna name (legada)
$tables = [
    'glpi_plugin_vehiclescheduler_schedules',
    'glpi_plugin_vehiclescheduler_maintenances',
    'glpi_plugin_vehiclescheduler_insuranceclaims'
];
foreach ($tables as $table) {
    if (!$DB->fieldExists($table, 'name')) {
        $DB->doQuery("ALTER TABLE `$table` ADD `name` varchar(255) NOT NULL DEFAULT '' AFTER `id`");
        echo "Added 'name' column to $table\n";
    } else {
        echo "'name' column already exists in $table\n";
    }
}

// 2. Adicionar users_id em drivers (Motoristas)
$driver_table = 'glpi_plugin_vehiclescheduler_drivers';
if ($DB->tableExists($driver_table)) {
    if (!$DB->fieldExists($driver_table, 'users_id')) {
        $DB->doQuery("ALTER TABLE `$driver_table` ADD `users_id` int unsigned NOT NULL DEFAULT '0' AFTER `name`");
        $DB->doQuery("ALTER TABLE `$driver_table` ADD KEY `users_id` (`users_id`)");
        echo "Added 'users_id' column and index to $driver_table\n";
    } else {
        echo "'users_id' column already exists in $driver_table\n";
    }
}

// 3. Adicionar tickets_id em incidents (Incidentes)
$incident_table = 'glpi_plugin_vehiclescheduler_incidents';
if ($DB->tableExists($incident_table)) {
    if (!$DB->fieldExists($incident_table, 'tickets_id')) {
        $DB->doQuery("ALTER TABLE `$incident_table` ADD `tickets_id` int unsigned NOT NULL DEFAULT '0' AFTER `groups_id`");
        $DB->doQuery("ALTER TABLE `$incident_table` ADD KEY `tickets_id` (`tickets_id`)");
        echo "Added 'tickets_id' column and index to $incident_table\n";
    } else {
        echo "'tickets_id' column already exists in $incident_table\n";
    }
}

// 4. Adicionar tickets_id em maintenances (Manutenções)
$maintenance_table = 'glpi_plugin_vehiclescheduler_maintenances';
if ($DB->tableExists($maintenance_table)) {
    if (!$DB->fieldExists($maintenance_table, 'tickets_id')) {
        $DB->doQuery("ALTER TABLE `$maintenance_table` ADD `tickets_id` int unsigned NOT NULL DEFAULT '0' AFTER `plugin_vehiclescheduler_incidents_id`");
        $DB->doQuery("ALTER TABLE `$maintenance_table` ADD KEY `tickets_id` (`tickets_id`)");
        echo "Added 'tickets_id' column and index to $maintenance_table\n";
    } else {
        echo "'tickets_id' column already exists in $maintenance_table\n";
    }
}

// 5. Adicionar tickets_id em driverfines (Multas)
$fine_table = 'glpi_plugin_vehiclescheduler_driverfines';
if ($DB->tableExists($fine_table)) {
    if (!$DB->fieldExists($fine_table, 'tickets_id')) {
        $DB->doQuery("ALTER TABLE `$fine_table` ADD `tickets_id` int unsigned NOT NULL DEFAULT '0' AFTER `status`");
        $DB->doQuery("ALTER TABLE `$fine_table` ADD KEY `tickets_id` (`tickets_id`)");
        echo "Added 'tickets_id' column and index to $fine_table\n";
    } else {
        echo "'tickets_id' column already exists in $fine_table\n";
    }
}

// 6. Adicionar mileage em vehicles (Veículos)
$vehicle_table = 'glpi_plugin_vehiclescheduler_vehicles';
if ($DB->tableExists($vehicle_table)) {
    if (!$DB->fieldExists($vehicle_table, 'mileage')) {
        $DB->doQuery("ALTER TABLE `$vehicle_table` ADD `mileage` int NOT NULL DEFAULT '0' AFTER `seats`");
        echo "Added 'mileage' column to $vehicle_table\n";
    } else {
        echo "'mileage' column already exists in $vehicle_table\n";
    }
}

// 7. Adicionar colunas de controle de devolução de veículo em schedules (Reservas)
$schedule_table = 'glpi_plugin_vehiclescheduler_schedules';
if ($DB->tableExists($schedule_table)) {
    $schedule_fields = [
        'real_begin_date'  => "timestamp NULL DEFAULT NULL",
        'real_end_date'    => "timestamp NULL DEFAULT NULL",
        'initial_mileage'  => "int NOT NULL DEFAULT '0'",
        'final_mileage'    => "int NOT NULL DEFAULT '0'",
        'initial_fuel'     => "int NOT NULL DEFAULT '0'",
        'final_fuel'       => "int NOT NULL DEFAULT '0'",
        'return_checklist' => "text",
        'return_comment'   => "text"
    ];
    foreach ($schedule_fields as $field => $def) {
        if (!$DB->fieldExists($schedule_table, $field)) {
            $DB->doQuery("ALTER TABLE `$schedule_table` ADD `$field` $def AFTER `comment`");
            echo "Added '$field' column to $schedule_table\n";
        } else {
            echo "'$field' column already exists in $schedule_table\n";
        }
    }
}

echo "Database update script executed.\n";
