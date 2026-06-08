<?php
require_once '/var/www/glpi/vendor/autoload.php';
$kernel = new \Glpi\Kernel\Kernel();
$kernel->boot();
global $DB;
if ($DB === null) {
    echo "DB is still null after boot!\n";
} else {
    echo "DB is initialized successfully!\n";
    $res = $DB->request("SHOW TABLES LIKE '%vehiclescheduler%'");
    foreach ($res as $row) {
        print_r($row);
    }
}
