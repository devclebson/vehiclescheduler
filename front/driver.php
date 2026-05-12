<?php
// front/driver.php

include_once(__DIR__ . '/../inc/common.inc.php');

Session::checkRight('plugin_vehiclescheduler_management', READ);

/**
 * Escapes HTML output for safe rendering.
 *
 * @param string|null $value Raw value to escape.
 *
 * @return string
 */
function vs_driver_list_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Formats a date value for UI rendering.
 *
 * @param string|null $value Raw date value.
 *
 * @return string
 */
function vs_driver_list_format_date(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return 'Não informado';
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y', $timestamp);
}

/**
 * Formats a datetime value for UI rendering.
 *
 * @param string|null $value Raw datetime value.
 *
 * @return string
 */
function vs_driver_list_format_datetime(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
        return 'Não informado';
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return $value;
    }

    return date('d/m/Y H:i', $timestamp);
}

/**
 * Returns the first character of a string using multibyte-safe functions when available.
 *
 * @param string $value Source string.
 *
 * @return string
 */
function vs_driver_list_first_char(string $value): string
{
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_substr')) {
        return (string) mb_substr($value, 0, 1);
    }

    return (string) substr($value, 0, 1);
}

/**
 * Converts a string to uppercase using multibyte-safe functions when available.
 *
 * @param string $value Source string.
 *
 * @return string
 */
function vs_driver_list_upper(string $value): string
{
    if (function_exists('mb_strtoupper')) {
        return (string) mb_strtoupper($value);
    }

    return strtoupper($value);
}

/**
 * Builds initials for the driver avatar.
 *
 * @param string $name Driver full name.
 *
 * @return string
 */
function vs_driver_list_initials(string $name): string
{
    $name = trim($name);

    if ($name === '') {
        return 'M';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= vs_driver_list_upper(vs_driver_list_first_char($part));
    }

    return $initials !== '' ? $initials : 'M';
}

/**
 * Builds a small status pill.
 *
 * @param string $label    Visible label.
 * @param string $modifier CSS modifier suffix.
 *
 * @return string
 */
function vs_driver_list_status_pill(string $label, string $modifier): string
{
    return '<span class="vs-driver-grid__pill vs-driver-grid__pill--' . vs_driver_list_escape($modifier) . '">'
        . vs_driver_list_escape($label)
        . '</span>';
}

/**
 * Builds the CNH expiry badge.
 *
 * @param array<string, mixed> $badge Badge data returned by the backend.
 *
 * @return string
 */
function vs_driver_list_expiry_badge(array $badge): string
{
    return '<span class="vs-driver-grid__expiry-badge ' . vs_driver_list_escape((string) ($badge['class'] ?? '')) . '">'
        . vs_driver_list_escape((string) ($badge['label'] ?? 'Sem data'))
        . '</span>';
}

$drivers = PluginVehicleschedulerDriver::getManagementGridRows();

global $CFG_GLPI;
$driverFormUrl = plugin_vehiclescheduler_get_front_url('driver.form.php');

Html::header(
    'Motoristas',
    $_SERVER['PHP_SELF'],
    'tools',
    PluginVehicleschedulerMenu::class,
    'management'
);

plugin_vehiclescheduler_load_css([
    'css/pages/driver-grid.css',
]);
plugin_vehiclescheduler_enhance_ui();
plugin_vehiclescheduler_render_back_to_management();

echo "<div class='vs-page-header'>";
echo "    <div class='vs-header-content'>";
echo "        <div class='vs-header-title'>";
echo "            <div class='vs-header-icon-wrapper'>";
echo "                <i class='ti ti-steering-wheel vs-header-icon'></i>";
echo "            </div>";
echo "            <div>";
echo "                <h2>Gestão de Motoristas</h2>";
echo "                <p class='vs-page-subtitle'>Grade operacional compacta para consulta, filtros rápidos e manutenção do cadastro.</p>";
echo "            </div>";
echo "        </div>";

if (Session::haveRight('plugin_vehiclescheduler_management', CREATE)) {
    echo "        <a href='" . vs_driver_list_escape($driverFormUrl) . "' class='vs-btn-add'>";
    echo "            <i class='ti ti-plus'></i>";
    echo "            <span>Adicionar Motorista</span>";
    echo "        </a>";
}

echo "    </div>";
echo "</div>";

echo "<div class='vs-driver-grid' data-vs-driver-grid>";

echo "    <section class='vs-driver-grid__toolbar'>";
echo "        <div class='vs-driver-grid__search-wrap'>";
echo "            <i class='ti ti-search'></i>";
echo "            <input type='search' placeholder='Buscar motorista...' aria-label='Buscar motoristas' data-driver-filter-search>";
echo "        </div>";

echo "        <div class='vs-driver-grid__results-text' data-driver-result-count>";
echo "            Exibindo " . (int) count($drivers) . " motoristas";
echo "        </div>";

echo "        <div class='vs-driver-grid__filters'>";
echo "            <label class='vs-driver-grid__filter'>";
echo "                <span>Situação</span>";
echo "                <select data-driver-filter-active>";
echo "                    <option value='all'>Todos</option>";
echo "                    <option value='1'>Ativos</option>";
echo "                    <option value='0'>Inativos</option>";
echo "                </select>";
echo "            </label>";

echo "            <label class='vs-driver-grid__filter'>";
echo "                <span>CNH</span>";
echo "                <select data-driver-filter-expiry>";
echo "                    <option value='all'>Todas</option>";
echo "                    <option value='ok'>Válida</option>";
echo "                    <option value='warning'>Alerta</option>";
echo "                    <option value='critical'>Crítica</option>";
echo "                    <option value='expired'>Vencida</option>";
echo "                    <option value='unknown'>Sem data</option>";
echo "                </select>";
echo "            </label>";

echo "            <button type='button' class='vs-driver-grid__clear' data-driver-clear-filters>";
echo "                <i class='ti ti-eraser'></i>";
echo "                <span>Limpar</span>";
echo "            </button>";
echo "        </div>";
echo "    </section>";

echo "    <div class='vs-driver-grid__table-wrap'>";
echo "        <table class='vs-driver-grid__table'>";
echo "            <thead>";
echo "                <tr>";
echo "                    <th>Motorista</th>";
echo "                    <th>Matrícula</th>";
echo "                    <th>Grupo</th>";
echo "                    <th>Telefone</th>";
echo "                    <th>Categorias CNH</th>";
echo "                    <th>Vencimento da CNH</th>";
echo "                    <th>Status</th>";
echo "                    <th>Atualizado em</th>";
echo "                    <th class='vs-driver-grid__actions-col'>Ações</th>";
echo "                </tr>";
echo "            </thead>";
echo "            <tbody data-driver-row-list>";

foreach ($drivers as $driver) {
    $driverName = (string) (($driver['name'] ?? '') !== '' ? $driver['name'] : 'Motorista sem nome');
    $driverUrl = $driverFormUrl . '?id=' . (int) ($driver['id'] ?? 0);

    $searchIndex = implode(' ', [
        (string) ($driver['name'] ?? ''),
        (string) ($driver['user_name'] ?? ''),
        (string) ($driver['registration'] ?? ''),
        (string) ($driver['group_name'] ?? ''),
        (string) ($driver['categories_text'] ?? ''),
        (string) ($driver['contact_phone'] ?? ''),
    ]);

    echo "            <tr data-driver-row"
        . " data-search='" . vs_driver_list_escape(strtolower($searchIndex)) . "'"
        . " data-active='" . (int) ($driver['is_active'] ?? 0) . "'"
        . " data-expiry-status='" . vs_driver_list_escape((string) ($driver['cnh_expiry_status'] ?? 'unknown')) . "'"
        . ">";

    echo "                <td>";
    echo "                    <div class='vs-driver-grid__identity'>";
    echo "                        <div class='vs-driver-grid__avatar'>" . vs_driver_list_escape(vs_driver_list_initials($driverName)) . "</div>";
    echo "                        <div class='vs-driver-grid__identity-body'>";
    echo "                            <div class='vs-driver-grid__name'>" . vs_driver_list_escape($driverName) . "</div>";
    echo "                            <div class='vs-driver-grid__subline'><i class='ti ti-user'></i> "
        . vs_driver_list_escape((string) ((($driver['user_name'] ?? '') !== '') ? $driver['user_name'] : 'Usuário não vinculado'))
        . "</div>";
    echo "                        </div>";
    echo "                    </div>";
    echo "                </td>";

    echo "                <td>" . vs_driver_list_escape((string) ((($driver['registration'] ?? '') !== '') ? $driver['registration'] : 'Não informada')) . "</td>";
    echo "                <td>" . vs_driver_list_escape((string) ((($driver['group_name'] ?? '') !== '') ? $driver['group_name'] : 'Não informado')) . "</td>";
    echo "                <td>" . vs_driver_list_escape((string) ((($driver['contact_phone'] ?? '') !== '') ? $driver['contact_phone'] : 'Não informado')) . "</td>";

    echo "                <td>";
    echo "                    <div class='vs-driver-grid__chip-list'>";

    if (!empty($driver['categories']) && is_array($driver['categories'])) {
        foreach ($driver['categories'] as $category) {
            $categoryCode = strtolower((string) $category);

            echo "<span class='vs-driver-grid__chip vs-driver-grid__chip--"
                . vs_driver_list_escape($categoryCode)
                . "'>"
                . vs_driver_list_escape((string) $category)
                . "</span>";
        }
    } else {
        echo "<span class='vs-driver-grid__muted'>Não informadas</span>";
    }

    echo "                    </div>";
    echo "                </td>";

    echo "                <td>";
    echo "                    <div class='vs-driver-grid__expiry'>";
    echo                          vs_driver_list_expiry_badge((array) ($driver['cnh_expiry_badge'] ?? []));
    echo "                        <span class='vs-driver-grid__subdate'>"
        . vs_driver_list_escape(vs_driver_list_format_date((string) ($driver['cnh_expiry'] ?? '')))
        . "</span>";
    echo "                    </div>";
    echo "                </td>";

    echo "                <td>";
    echo "                    <div class='vs-driver-grid__pill-stack'>";
    echo                          vs_driver_list_status_pill(
        ((int) ($driver['is_active'] ?? 0) === 1 ? 'Ativo' : 'Inativo'),
        ((int) ($driver['is_active'] ?? 0) === 1 ? 'active' : 'inactive')
    );
    echo "                    </div>";
    echo "                </td>";

    echo "                <td>" . vs_driver_list_escape(vs_driver_list_format_datetime((string) ($driver['date_mod'] ?? ''))) . "</td>";

    echo "                <td class='vs-driver-grid__actions-col'>";
    echo "                    <a href='" . vs_driver_list_escape($driverUrl) . "' class='vs-driver-grid__action'>";
    echo "                        <i class='ti ti-pencil'></i>";
    echo "                        <span>Abrir</span>";
    echo "                    </a>";
    echo "                </td>";

    echo "            </tr>";
}

echo "            </tbody>";
echo "        </table>";
echo "    </div>";

echo "    <div class='vs-driver-grid__empty' data-driver-empty hidden>";
echo "        <div class='vs-driver-grid__empty-icon'><i class='ti ti-users'></i></div>";
echo "        <h3>Nenhum motorista encontrado</h3>";
echo "        <p>Revise os filtros aplicados.</p>";
echo "    </div>";

echo "</div>";

plugin_vehiclescheduler_load_script('js/driver-grid.js');
Html::footer();
