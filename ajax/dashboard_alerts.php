<?php

/**
 * Executive dashboard realtime polling endpoint.
 */

include_once(__DIR__ . '/../inc/common.inc.php');
include_once(__DIR__ . '/../inc/dashboard.class.php');

header('Content-Type: application/json; charset=UTF-8');

if (!Session::haveRight('plugin_vehiclescheduler_management', READ)) {
    http_response_code(403);

    echo json_encode([
        'ok'    => false,
        'error' => 'Acesso negado.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

try {
    $payload = PluginVehicleschedulerDashboard::getRealtimeAlertPayload();

    echo json_encode([
        'ok'   => true,
        'data' => $payload,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    Toolbox::logInFile(
        'php-errors',
        '[vehiclescheduler] Realtime alert endpoint error: ' . $e->getMessage() . PHP_EOL
    );

    http_response_code(500);

    echo json_encode([
        'ok'    => false,
        'error' => 'Erro ao obter alertas do dashboard.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}