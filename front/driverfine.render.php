<?php
// front/driverfine.render.php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Escapes HTML output for safe rendering.
 *
 * @param string|null $value Raw value.
 *
 * @return string
 */
function vs_driverfine_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Formats a date for UI rendering.
 *
 * @param string|null $value Raw date value.
 *
 * @return string
 */
function vs_driverfine_format_date(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return 'Não informada';
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y', $timestamp);
}

/**
 * Returns the visual badge for severity.
 *
 * @param int $severity Severity identifier.
 *
 * @return string
 */
function vs_driverfine_render_severity_badge(int $severity): string
{
    $labels = PluginVehicleschedulerDriverfine::getAllSeverities();
    $class = 'vs-driverfine-badge--' . PluginVehicleschedulerDriverfine::getSeverityModifier($severity);

    return '<span class="vs-driverfine-badge ' . vs_driverfine_escape($class) . '">'
        . vs_driverfine_escape($labels[$severity] ?? 'Não definida')
        . '</span>';
}

/**
 * Returns the visual badge for status.
 *
 * @param int $status Status identifier.
 *
 * @return string
 */
function vs_driverfine_render_status_badge(int $status): string
{
    $labels = PluginVehicleschedulerDriverfine::getAllStatus();
    $class = 'vs-driverfine-badge--' . PluginVehicleschedulerDriverfine::getStatusModifier($status);

    return '<span class="vs-driverfine-badge ' . vs_driverfine_escape($class) . '">'
        . vs_driverfine_escape($labels[$status] ?? 'Sem status')
        . '</span>';
}

/**
 * Renders the traffic infractions tab for a driver.
 *
 * @param PluginVehicleschedulerDriver $driver Driver instance.
 *
 * @return void
 */
function vs_render_driverfine_tab(PluginVehicleschedulerDriver $driver): void
{
    if (!PluginVehicleschedulerDriverfine::canAdminFines()) {
        return;
    }

    $driverId = (int) $driver->getID();
    $fines = PluginVehicleschedulerDriverfine::getFinesForDriver($driverId);
    $summary = PluginVehicleschedulerDriverfine::buildDriverSummary($fines);
    $vehicleLabels = PluginVehicleschedulerDriverfine::getVehicleLabels($fines);

    echo '<div class="vs-driverfine-wrap">';
    echo '    <div class="vs-driverfine-surface">';
    echo '        <div class="vs-driverfine-card">';

    echo '            <div class="vs-driverfine-head">';
    echo '                <div>';
    echo '                    <h3 class="vs-driverfine-title"><i class="ti ti-file-alert"></i> Infrações de Trânsito</h3>';
    echo '                    <div class="vs-driverfine-sub">Pontuação, autuações em aberto e histórico administrativo do condutor.</div>';
    echo '                </div>';
    echo '                <a class="vs-driverfine-tab-action" href="' . vs_driverfine_escape(plugin_vehiclescheduler_get_front_url('driverfine.form.php') . '?plugin_vehiclescheduler_drivers_id=' . $driverId) . '">';
    echo '                    <i class="ti ti-plus"></i><span>Nova multa</span>';
    echo '                </a>';
    echo '            </div>';

    echo '            <div class="vs-driverfine-summary-grid">';
    echo '                <div class="vs-driverfine-summary-card">';
    echo '                    <span class="vs-driverfine-summary-label">Total de Infrações</span>';
    echo '                    <strong class="vs-driverfine-summary-value">' . (int) $summary['total_fines'] . '</strong>';
    echo '                </div>';
    echo '                <div class="vs-driverfine-summary-card">';
    echo '                    <span class="vs-driverfine-summary-label">Em Aberto</span>';
    echo '                    <strong class="vs-driverfine-summary-value">' . (int) $summary['open_count'] . '</strong>';
    echo '                </div>';
    echo '                <div class="vs-driverfine-summary-card">';
    echo '                    <span class="vs-driverfine-summary-label">Pontuação</span>';
    echo '                    <strong class="vs-driverfine-summary-value">' . (int) $summary['total_points'] . ' pts</strong>';
    echo '                </div>';
    echo '                <div class="vs-driverfine-summary-card">';
    echo '                    <span class="vs-driverfine-summary-label">Situação</span>';
    echo '                    <strong class="vs-driverfine-summary-status">' . vs_driverfine_escape((string) $summary['status_text']) . '</strong>';
    echo '                </div>';
    echo '            </div>';

    echo '            <div class="vs-driverfine-progress">';
    echo '                <div class="vs-driverfine-progress__bar">';
    echo '                    <span class="vs-driverfine-progress__fill" style="width:' . (int) $summary['percentage'] . '%; background:' . vs_driverfine_escape((string) $summary['bar_color']) . ';"></span>';
    echo '                </div>';
    echo '            </div>';

    if ($fines === []) {
        echo '        <div class="vs-driverfine-empty">';
        echo '            <div class="vs-driverfine-empty-icon"><i class="ti ti-file-alert"></i></div>';
        echo '            <h3>Nenhuma infração registrada</h3>';
        echo '            <p>O condutor ainda não possui infrações cadastradas.</p>';
        echo '        </div>';
        echo '        </div>';
        echo '    </div>';
        echo '</div>';
        return;
    }

    echo '            <div class="vs-driverfine-table-wrap">';
    echo '                <table class="vs-driverfine-table">';
    echo '                    <thead>';
    echo '                        <tr>';
    echo '                            <th>Data</th>';
    echo '                            <th>Código</th>';
    echo '                            <th>Descrição</th>';
    echo '                            <th>Severidade</th>';
    echo '                            <th>Status</th>';
    echo '                            <th>Veículo</th>';
    echo '                        </tr>';
    echo '                    </thead>';
    echo '                    <tbody>';

    foreach ($fines as $fine) {
        $vehicleId = (int) ($fine['plugin_vehiclescheduler_vehicles_id'] ?? 0);
        $vehicleLabel = $vehicleId > 0
            ? ($vehicleLabels[$vehicleId] ?? ('Veículo #' . $vehicleId))
            : 'Não vinculado';
        $violationCode = trim((string) ($fine['violation_code'] ?? ''));
        $violationSplit = trim((string) ($fine['violation_split'] ?? ''));
        $violationDisplay = $violationCode !== ''
            ? $violationCode . ($violationSplit !== '' ? '-' . $violationSplit : '')
            : 'Manual';

        echo '                    <tr>';
        echo '                        <td>' . vs_driverfine_escape(vs_driverfine_format_date((string) ($fine['fine_date'] ?? ''))) . '</td>';
        echo '                        <td><span class="vs-fines-code">' . vs_driverfine_escape($violationDisplay) . '</span></td>';
        echo '                        <td>' . vs_driverfine_escape((string) ($fine['description'] ?? '')) . '</td>';
        echo '                        <td>' . vs_driverfine_render_severity_badge((int) ($fine['severity'] ?? 0)) . '</td>';
        echo '                        <td>' . vs_driverfine_render_status_badge((int) ($fine['status'] ?? 0)) . '</td>';
        echo '                        <td>' . vs_driverfine_escape($vehicleLabel) . '</td>';
        echo '                    </tr>';
    }

    echo '                    </tbody>';
    echo '                </table>';
    echo '            </div>';

    echo '        </div>';
    echo '    </div>';
    echo '</div>';
}
