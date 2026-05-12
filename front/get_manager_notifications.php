<?php
/**
 * AJAX - Notificações (SEM HTML!)
 */

// IMPORTANTE: Não incluir header/footer do GLPI
define('GLPI_ROOT', '../../..');

// Desabilitar output buffering e headers HTML
ob_end_clean();
header('Content-Type: application/json; charset=UTF-8');

// Validar sessão
Session::checkLoginUser();

// Verificar direitos
if (!Session::haveRight('plugin_vehiclescheduler', UPDATE + DELETE)) {
    echo json_encode([
        'notifications' => [],
        'count' => 0,
        'info' => 'Sem direitos de gestão'
    ]);
    exit;
}

global $DB;

$user_id = Session::getLoginUserID();
$entity_id = $_SESSION['glpiactive_entity'] ?? 0;
$notifications = [];

try {
    $iterator = $DB->request([
        'SELECT' => [
            'id',
            'plugin_vehiclescheduler_vehicles_id',
            'users_id',
            'begin_date',
            'end_date',
            'destination',
            'date_creation'
        ],
        'FROM'   => 'glpi_plugin_vehiclescheduler_schedules',
        'WHERE'  => [
            'status' => 1,
            'entities_id' => $entity_id,
            'users_id' => ['!=', $user_id],
            'date_creation' => ['>=', date('Y-m-d H:i:s', strtotime('-7 days'))]
        ],
        'ORDER'  => 'date_creation DESC',
        'LIMIT'  => 10
    ]);

    foreach ($iterator as $data) {
        $vehicle = new PluginVehicleschedulerVehicle();
        if (!$vehicle->getFromDB($data['plugin_vehiclescheduler_vehicles_id'])) {
            continue;
        }
        
        $requester = getUserName($data['users_id']);
        $begin = new DateTime($data['begin_date']);
        $end = new DateTime($data['end_date']);
        $period = $begin->format('d/m/Y H:i') . ' até ' . $end->format('d/m/Y H:i');
        
        $now = new DateTime();
        $hours_until = ($begin->getTimestamp() - $now->getTimestamp()) / 3600;
        $priority = ($hours_until < 24 && $hours_until > 0) ? 'high' : 'normal';
        
        $notifications[] = [
            'id' => 'schedule_' . $data['id'],
            'schedule_id' => (int)$data['id'],
            'vehicle_name' => $vehicle->fields['name'] . ' (' . $vehicle->fields['plate'] . ')',
            'requester' => $requester,
            'period' => $period,
            'destination' => $data['destination'],
            'priority' => $priority,
            'created_at' => $data['date_creation']
        ];
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage(), 'notifications' => []]);
    exit;
}

echo json_encode([
    'notifications' => $notifications,
    'count' => count($notifications)
]);