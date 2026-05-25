<?php

include_once __DIR__ . '/../inc/common.inc.php';

Session::checkRight('plugin_vehiclescheduler_management', READ);

plugin_vehiclescheduler_flash_warning('Manutenções em breve: este módulo ainda não está disponível.');

Html::redirect(plugin_vehiclescheduler_get_front_url('management.php'));
exit;
