<?php
define('GLPI_ROOT', '/var/www/glpi');
define('DO_NOT_CHECK_HTTP_REFERER', 1);
$_SERVER['REQUEST_URI'] = '';
include (GLPI_ROOT . '/inc/includes.php');
global $DB;
$res = $DB->request("SHOW TABLES LIKE '%vehiclescheduler%'");
foreach ($res as $row) {
    print_r($row);
}
