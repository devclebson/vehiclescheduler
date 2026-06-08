<?php
/**
 * Vehicle Scheduler - Management Dashboard
 */

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 4));
}
include_once(GLPI_ROOT . '/inc/includes.php');
include_once(__DIR__ . '/../../inc/helpers/common.inc.php');

$is_tab = isset($_GET['is_tab']) || isset($_POST['is_tab']);

if (!$is_tab) {
    if (!class_exists('PluginVehicleschedulerProfile') || !PluginVehicleschedulerProfile::canViewManagement()) {
        Html::displayRightError();
    }
    global $DB, $CFG_GLPI;
    Html::header('Gestão da Frota', $_SERVER['PHP_SELF'], 'tools', 'PluginVehicleschedulerMenug', 'management');
} else {
    global $DB, $CFG_GLPI;
}

// Handle quick actions
if (isset($_POST['quick_action'])) {
    if (Session::haveRight('plugin_vehiclescheduler', UPDATE)) {
        $schedule = new PluginVehicleschedulerSchedule();
        if ($schedule->getFromDB($_POST['id'])) {
            $schedule->update([
                'id' => $_POST['id'],
                'status' => $_POST['quick_action'] == 'approve' ? 2 : 3, // 2=approved, 3=refused
                'approver_users_id' => Session::getLoginUserID()
            ]);
        }
        Session::addMessageAfterRedirect(
            $_POST['quick_action'] == 'approve' ? 'Reserva aprovada!' : 'Reserva recusada!',
            false,
            INFO
        );
        $redirect_url = Plugin::getWebDir('vehiclescheduler') . '/front/index.php';
        Html::redirect($redirect_url);
    }
}

// KPIs
$kpi = [
    'veiculos_ativos'   => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['is_active' => 1], 'COUNT' => 'c'])->current()['c'],
    'motoristas_ativos' => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_drivers', 'WHERE' => ['is_active' => 1], 'COUNT' => 'c'])->current()['c'],
    'reservas_novas'    => (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_schedules', 'WHERE' => ['status' => 1], 'COUNT' => 'c'])->current()['c'],
    'reservas_aprovadas'=> (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_schedules', 'WHERE' => ['status' => 2], 'COUNT' => 'c'])->current()['c'],
    'incidentes_abertos'=> (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_incidents', 'WHERE' => ['status' => 1], 'COUNT' => 'c'])->current()['c'],
    'manutencoes_futuras'=> (int)$DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_maintenances', 'WHERE' => ['status' => 1], 'COUNT' => 'c'])->current()['c']
];

if (!$is_tab) {
    vs_render_navbar('management');
}

echo '<div style="display: flex; gap: 20px; margin-bottom: 24px; flex-wrap: wrap;">';

// KPI Cards
$cards = [
    ['icon' => 'ti-car', 'value' => $kpi['veiculos_ativos'], 'label' => 'VEÍCULOS ATIVOS', 'color' => 'primary'],
    ['icon' => 'ti-steering-wheel', 'value' => $kpi['motoristas_ativos'], 'label' => 'MOTORISTAS ATIVOS', 'color' => 'success'],
    ['icon' => 'ti-clock', 'value' => $kpi['reservas_novas'], 'label' => 'AGUARDANDO APROVAÇÃO', 'color' => 'warning'],
    ['icon' => 'ti-check', 'value' => $kpi['reservas_aprovadas'], 'label' => 'RESERVAS APROVADAS', 'color' => 'success'],
    ['icon' => 'ti-alert-triangle', 'value' => $kpi['incidentes_abertos'], 'label' => 'INCIDENTES ABERTOS', 'color' => 'danger'],
    ['icon' => 'ti-tool', 'value' => $kpi['manutencoes_futuras'], 'label' => 'MANUTENÇÕES AGENDADAS', 'color' => 'info']
];

$color_map = [
    'primary' => ['text' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.1)'],
    'success' => ['text' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.1)'],
    'warning' => ['text' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.1)'],
    'danger'  => ['text' => '#ef4444', 'bg' => 'rgba(239, 68, 68, 0.1)'],
    'info'    => ['text' => '#0ea5e9', 'bg' => 'rgba(14, 165, 233, 0.1)'],
];

foreach ($cards as $card) {
    $c = $color_map[$card['color']] ?? ['text' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.1)'];
    echo "<div class='vs-card' style='flex: 1; min-width: 220px; display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border: 1px solid #f1f5f9; background: #fff;'>";
    echo "  <div style='text-align: left;'>";
    echo "    <div style='color: var(--vs-text-light); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;'>{$card['label']}</div>";
    echo "    <h2 style='font-size: 2.25rem; font-weight: 800; margin: 6px 0 0; color: var(--vs-text); line-height: 1;'>{$card['value']}</h2>";
    echo "  </div>";
    echo "  <div style='width: 48px; height: 48px; border-radius: 12px; background: {$c['bg']}; display: flex; align-items: center; justify-content: center; color: {$c['text']};'>";
    echo "    <i class='ti {$card['icon']}' style='font-size: 1.5rem;'></i>";
    echo "  </div>";
    echo "</div>";
}
echo '</div>'; // End KPI cards

// Layout com duas colunas
echo '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">';

// Coluna Esquerda
echo '<div>';

// Reservas Aguardando Aprovação
echo "<div class='vs-card mb-4'>";
echo "<div class='vs-card-header'>";
echo "<h3><i class='ti ti-clock'></i> Aprovação Rápida de Reservas</h3>";
echo "<a href='" . Plugin::getWebDir('vehiclescheduler') . "/front/schedule.php' style='font-size: 0.85rem;'>Ver todas &rarr;</a>";
echo "</div>";

$iterator = $DB->request([
    'FROM' => 'glpi_plugin_vehiclescheduler_schedules',
    'WHERE' => ['status' => 1],
    'LIMIT' => 5,
    'ORDER' => 'begin_date ASC'
]);

if ($iterator->count() > 0) {
    echo "<table class='vs-table'>";
    echo "<tr><th>Solicitante</th><th>Veículo</th><th>Período</th><th>Destino</th><th>Ações</th></tr>";
    foreach ($iterator as $row) {
        $user = new User();
        $user->getFromDB($row['users_id']);
        
        $vehicle = new PluginVehicleschedulerVehicle();
        $vehicle->getFromDB($row['plugin_vehiclescheduler_vehicles_id']);
        
        $date_str = date('d-m-Y', strtotime($row['begin_date'])) . ' &rarr; ' . date('d-m-Y', strtotime($row['end_date']));
        
        echo "<tr>";
        echo "<td><strong>" . $user->getName() . "</strong></td>";
        echo "<td>" . $vehicle->fields['name'] . " (" . $vehicle->fields['plate'] . ")</td>";
        echo "<td>$date_str</td>";
        echo "<td>" . $row['destination'] . "</td>";
        echo "<td>
            <form method='post' style='display:inline;'>
                <input type='hidden' name='id' value='{$row['id']}'>
                <button type='submit' name='quick_action' value='approve' class='vs-badge vs-badge-green' style='border:none;cursor:pointer;'><i class='ti ti-check'></i> Aprovar</button>
                <button type='submit' name='quick_action' value='refuse' class='vs-badge vs-badge-red' style='border:none;cursor:pointer;'><i class='ti ti-x'></i> Recusar</button>
            </form>
            <a href='" . Plugin::getWebDir('vehiclescheduler') . "/front/schedule.form.php?id={$row['id']}' class='vs-btn vs-btn-light' style='padding:4px 8px;'><i class='ti ti-eye'></i></a>
        </td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='text-align:center; padding:20px; color:var(--vs-text-light);'>Nenhuma reserva aguardando aprovação.</div>";
}
echo "</div>";

// Próximas Manutenções
echo "<div class='vs-card'>";
echo "<div class='vs-card-header'>";
echo "<h3><i class='ti ti-tool'></i> Próximas Manutenções</h3>";
echo "<a href='" . Plugin::getWebDir('vehiclescheduler') . "/front/maintenance.php' style='font-size: 0.85rem;'>Ver todas &rarr;</a>";
echo "</div>";

$iterator = $DB->request([
    'FROM' => 'glpi_plugin_vehiclescheduler_maintenances',
    'WHERE' => ['status' => 1],
    'LIMIT' => 5,
    'ORDER' => 'scheduled_date ASC'
]);

if ($iterator->count() > 0) {
    echo "<table class='vs-table'>";
    echo "<tr><th>Veículo</th><th>Tipo</th><th>Agendamento</th><th>Status</th></tr>";
    foreach ($iterator as $row) {
        $vehicle = new PluginVehicleschedulerVehicle();
        $vehicle->getFromDB($row['plugin_vehiclescheduler_vehicles_id']);
        
        echo "<tr>";
        echo "<td><strong>" . $vehicle->fields['name'] . "<br><small>(" . $vehicle->fields['plate'] . ")</small></strong></td>";
        echo "<td><span style='color:var(--vs-primary); font-weight:600;'>" . ($row['type'] == 1 ? 'Preventiva' : 'Corretiva') . "</span></td>";
        echo "<td>" . date('d-m-Y', strtotime($row['scheduled_date'])) . "</td>";
        echo "<td><span class='vs-badge vs-badge-blue'>AGENDADA</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='text-align:center; padding:20px; color:var(--vs-text-light);'>Nenhuma manutenção agendada.</div>";
}
echo "</div>";

echo '</div>'; // Fim Coluna Esquerda

// Coluna Direita
echo '<div>';

// CNH a vencer
echo "<div class='vs-card'>";
echo "<div class='vs-card-header'>";
echo "<h3><i class='ti ti-id'></i> Alertas CNH</h3>";
echo "<a href='" . Plugin::getWebDir('vehiclescheduler') . "/front/driver.php' style='font-size: 0.85rem;'>Ver todos &rarr;</a>";
echo "</div>";

$iterator = $DB->request([
    'FROM' => 'glpi_plugin_vehiclescheduler_drivers',
    'WHERE' => [
        'is_active' => 1,
        'cnh_expiry' => ['<=', date('Y-m-d', strtotime('+90 days'))]
    ],
    'ORDER' => 'cnh_expiry ASC'
]);

if ($iterator->count() > 0) {
    echo "<table class='vs-table'>";
    echo "<tr><th>Motorista</th><th>Vencimento</th></tr>";
    foreach ($iterator as $row) {
        $days = (strtotime($row['cnh_expiry']) - time()) / (60 * 60 * 24);
        $badge = $days <= 30 ? 'vs-badge-red' : 'vs-badge-gray';
        
        echo "<tr>";
        echo "<td><a href='" . Plugin::getWebDir('vehiclescheduler') . "/front/driver.form.php?id={$row['id']}' style='font-weight:600; text-decoration:none;'>{$row['name']}</a></td>";
        echo "<td>" . date('d-m-Y', strtotime($row['cnh_expiry'])) . " <span class='vs-badge {$badge}'>" . round($days) . " DIAS</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='text-align:center; padding:20px; color:var(--vs-text-light);'>Nenhuma CNH próxima do vencimento.</div>";
}
echo "</div>";

echo '</div>'; // Fim Coluna Direita
echo '</div>'; // Fim Grid

// Quick Links
if (!$is_tab) {
    echo "<div style='display: flex; justify-content: center; gap: 15px; margin-top: 30px; flex-wrap: wrap;'>";
    $links = [
        ['icon' => 'ti-car', 'label' => 'Gerenciar Veículos', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/vehicle.php'],
        ['icon' => 'ti-steering-wheel', 'label' => 'Gerenciar Motoristas', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/driver.php'],
        ['icon' => 'ti-alert-triangle', 'label' => 'Ver Incidentes', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/incident.php'],
        ['icon' => 'ti-tool', 'label' => 'Manutenções', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/maintenance.php'],
        ['icon' => 'ti-shield-lock', 'label' => 'Sinistros', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/insuranceclaim.php'],
        ['icon' => 'ti-calendar', 'label' => 'Todas as Reservas', 'url' => Plugin::getWebDir('vehiclescheduler') . '/front/schedule.php', 'color' => 'secondary']
    ];

    foreach ($links as $link) {
        $class = isset($link['color']) && $link['color'] == 'primary' ? 'vs-btn-primary' : 'vs-btn-light';
        echo "<a href='{$link['url']}' class='vs-btn {$class}'><i class='ti {$link['icon']}'></i> {$link['label']}</a>";
    }
    echo "</div>";
}

if (!$is_tab) {
    Html::footer();
}
?>