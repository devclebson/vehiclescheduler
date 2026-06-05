<?php
include('/var/www/glpi/inc/includes.php');
global $DB;
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
echo "Done.\n";
