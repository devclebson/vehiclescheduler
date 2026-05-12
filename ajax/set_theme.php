<?php
/**
 * AJAX endpoint para trocar tema
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$new_theme = $data['theme'] ?? null;

if (!$new_theme) {
    http_response_code(400);
    echo json_encode(['error' => 'Theme not specified']);
    exit;
}

global $DB;
$user_id = Session::getLoginUserID();

// Verificar se já existe config para este usuário
$existing = $DB->request([
    'FROM' => 'glpi_plugin_vehiclescheduler_configs',
    'WHERE' => ['users_id' => $user_id]
])->current();

if ($existing) {
    // Atualizar
    $DB->update('glpi_plugin_vehiclescheduler_configs', [
        'theme' => $new_theme
    ], [
        'users_id' => $user_id
    ]);
} else {
    // Inserir
    $DB->insert('glpi_plugin_vehiclescheduler_configs', [
        'users_id' => $user_id,
        'theme' => $new_theme
    ]);
}

echo json_encode(['success' => true, 'theme' => $new_theme]);