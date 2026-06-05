<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * DriverFine — Registro de infrações de trânsito
 * LGPD: apenas dados operacionais mínimos. Sem CPF, endereço ou biometria.
 */

if (!defined('GLPI_ROOT')) {
    die("Acesso direto não permitido");
}

class PluginVehicleschedulerDriverfine extends CommonDBChild {

    static public $itemtype  = 'PluginVehicleschedulerDriver';
    static public $items_id  = 'plugin_vehiclescheduler_drivers_id';
    public $dohistory        = true;
    static $rightname        = 'plugin_vehiclescheduler';

    const SEVERITY_MILD       = 1;
    const SEVERITY_MEDIUM     = 2;
    const SEVERITY_SEVERE     = 3;
    const SEVERITY_VERYSEVERE = 4;

    const STATUS_OPEN      = 1;
    const STATUS_PAID      = 2;
    const STATUS_APPEALED  = 3;
    const STATUS_CANCELLED = 4;

    static function getTypeName($nb = 0) {
        return ($nb === 1) ? 'Infração de Trânsito' : 'Infrações de Trânsito';
    }

    static function getIcon() {
        return 'ti ti-ticket';
    }

    static function getAllSeverities() {
        return [
            self::SEVERITY_MILD       => 'Leve — 3 pts',
            self::SEVERITY_MEDIUM     => 'Média — 4 pts',
            self::SEVERITY_SEVERE     => 'Grave — 5 pts',
            self::SEVERITY_VERYSEVERE => 'Gravíssima — 7 pts',
        ];
    }

    static function getSeverityPoints() {
        return [
            self::SEVERITY_MILD       => 3,
            self::SEVERITY_MEDIUM     => 4,
            self::SEVERITY_SEVERE     => 5,
            self::SEVERITY_VERYSEVERE => 7,
        ];
    }

    static function getAllStatus() {
        return [
            self::STATUS_OPEN      => 'Em aberto',
            self::STATUS_PAID      => 'Paga',
            self::STATUS_APPEALED  => 'Recurso',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    // INSTÂNCIA — obrigatório, CommonGLPI::getTabNameForItem() é non-static
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item instanceof PluginVehicleschedulerDriver) {
            $count = countElementsInTable(
                self::getTable(),
                ['plugin_vehiclescheduler_drivers_id' => $item->getID()]
            );
            return self::createTabEntry('Infrações de Trânsito', $count);
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item instanceof PluginVehicleschedulerDriver) {
            self::showFinesForDriver($item);
        }
        return true;
    }

    static function showFinesForDriver(PluginVehicleschedulerDriver $driver) {
        global $DB;

        $driver_id  = $driver->getID();
        $canedit    = Session::haveRight('plugin_vehiclescheduler', UPDATE);
        $points_map = self::getSeverityPoints();

        $rows  = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['plugin_vehiclescheduler_drivers_id' => $driver_id],
            'ORDER' => ['fine_date DESC'],
        ]);
        $fines = iterator_to_array($rows);

        $total_points = 0;
        foreach ($fines as $fine) {
            if ($fine['status'] != self::STATUS_CANCELLED) {
                $total_points += $points_map[$fine['severity']] ?? 0;
            }
        }

        $bar_color = '#28a745';
        if ($total_points >= 20)     $bar_color = '#dc3545';
        elseif ($total_points >= 15) $bar_color = '#fd7e14';
        elseif ($total_points >= 10) $bar_color = '#ffc107';
        $pct = min(100, (int) round($total_points / 40 * 100));

        echo "<div style='background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:12px 16px;margin-bottom:16px;'>";
        echo "<strong>Saldo de Pontos:</strong> ";
        echo "<span style='font-size:1.3em;font-weight:bold;color:{$bar_color};'>{$total_points}</span> / 40 pontos (limite de suspensão — CTB)";
        echo "<div style='margin-top:6px;background:#dee2e6;border-radius:4px;height:10px;'>";
        echo "<div style='background:{$bar_color};width:{$pct}%;height:10px;border-radius:4px;'></div></div>";
        if ($total_points >= 20) {
            echo "<p style='color:#dc3545;margin:6px 0 0;font-size:12px;'>⚠️ Pontuação elevada — recomenda-se conversa com o motorista.</p>";
        }
        echo "</div>";

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='table-row'>";
        foreach (['Data', 'Descrição', 'Severidade', 'Pontos', 'Status', 'Veículo', 'Ações'] as $h) {
            echo "<th>{$h}</th>";
        }
        echo "</tr>";

        if (count($fines) === 0) {
            echo "<tr><td colspan='7' class='center'><i>Nenhuma infração registrada.</i></td></tr>";
        }

        $severities = self::getAllSeverities();
        $statuses   = self::getAllStatus();
        $vcache     = [];

        foreach ($fines as $fine) {
            $veh_name = '—';
            $vid = (int)($fine['plugin_vehiclescheduler_vehicles_id'] ?? 0);
            if ($vid > 0) {
                if (!isset($vcache[$vid])) {
                    $v = new PluginVehicleschedulerVehicle();
                    $vcache[$vid] = $v->getFromDB($vid)
                        ? htmlspecialchars($v->fields['name'] . ' (' . $v->fields['plate'] . ')')
                        : '—';
                }
                $veh_name = $vcache[$vid];
            }

            $pts       = $points_map[$fine['severity']] ?? '?';
            $sev_label = $severities[$fine['severity']] ?? '?';
            $sta_label = $statuses[$fine['status']]    ?? '?';
            $row_class = ($fine['status'] == self::STATUS_PAID || $fine['status'] == self::STATUS_CANCELLED)
                       ? 'table-row' : 'table-row';

            echo "<tr class='{$row_class}'>";
            echo "<td>" . Html::convDate($fine['fine_date']) . "</td>";
            echo "<td>" . htmlspecialchars($fine['description']) . "</td>";
            echo "<td>{$sev_label}</td>";
            echo "<td><strong>{$pts}</strong></td>";
            echo "<td>{$sta_label}</td>";
            echo "<td>{$veh_name}</td>";
            echo "<td>";
            if ($canedit) {
                $url = Plugin::getWebDir('vehiclescheduler') . '/front/driverfine.form.php?id=' . $fine['id'];
                echo "<a href='{$url}' class='btn btn-sm btn-ghost-secondary'><i class='ti ti-pencil'></i></a>";
            }
            echo "</td></tr>";
        }
        echo "</table>";

        if ($canedit) {
            $form_url = Plugin::getWebDir('vehiclescheduler') . '/front/driverfine.form.php';
            echo "<br/>";
            echo "<div style='background:#fff;border:1px solid #dee2e6;border-radius:6px;padding:16px;'>";
            echo "<h4 style='margin:0 0 12px;'>Registrar Nova Infração</h4>";
            echo "<form method='post' action='{$form_url}'>";
            echo Html::hidden('plugin_vehiclescheduler_drivers_id', ['value' => $driver_id]);
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo "<table class='tab_cadre_fixe'>";

            echo "<tr class='table-row'>";
            echo "<td>Data <span class='red'>*</span></td><td>";
            Html::showDateField('fine_date', ['value' => date('Y-m-d')]);
            echo "</td><td>Severidade <span class='red'>*</span></td><td>";
            Dropdown::showFromArray('severity', self::getAllSeverities(), ['value' => self::SEVERITY_SEVERE]);
            echo "</td></tr>";

            echo "<tr class='table-row'>";
            echo "<td>Veículo no momento</td><td>";
            PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => 0]);
            echo "</td><td>Status</td><td>";
            Dropdown::showFromArray('status', self::getAllStatus(), ['value' => self::STATUS_OPEN]);
            echo "</td></tr>";

            echo "<tr class='table-row'>";
            echo "<td>Descrição <span class='red'>*</span></td>";
            echo "<td colspan='3'><textarea name='description' rows='2' style='width:98%;'"
                . " placeholder='Descreva a infração (não inclua dados pessoais como CPF)'></textarea></td></tr>";

            echo "<tr class='table-row'><td colspan='4'>";
            echo "<input type='submit' name='add' value='Adicionar Infração' class='btn btn-primary'>";
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }
    }

    function prepareInputForAdd($input) {
        if (empty(trim($input['description'] ?? ''))) {
            Session::addMessageAfterRedirect('A descrição é obrigatória.', false, ERROR);
            return false;
        }
        if (empty($input['fine_date'])) {
            Session::addMessageAfterRedirect('A data da infração é obrigatória.', false, ERROR);
            return false;
        }
        if (!isset($input['status'])) {
            $input['status'] = self::STATUS_OPEN;
        }
        if (!isset($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
        }
        return $input;
    }

    function showForm($ID, array $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        echo "<tr class='table-row'><td colspan='4' class='text-end' style='text-align: right;'><a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'><i class='ti ti-arrow-left'></i> Voltar</a></td></tr>";

        echo "<tr class='table-row'>";
        echo "<td>Motorista</td><td>";
        $driver = new PluginVehicleschedulerDriver();
        if ($driver->getFromDB($this->fields['plugin_vehiclescheduler_drivers_id'])) {
            echo $driver->getLink();
        }
        echo Html::hidden('plugin_vehiclescheduler_drivers_id',
            ['value' => $this->fields['plugin_vehiclescheduler_drivers_id']]);
        echo "</td><td>Data</td><td>";
        Html::showDateField('fine_date', ['value' => $this->fields['fine_date']]);
        echo "</td></tr>";

        echo "<tr class='table-row'>";
        echo "<td>Severidade</td><td>";
        Dropdown::showFromArray('severity', self::getAllSeverities(), ['value' => $this->fields['severity']]);
        echo "</td><td>Status</td><td>";
        Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status']]);
        echo "</td></tr>";

        echo "<tr class='table-row'>";
        echo "<td>Veículo no momento</td><td>";
        PluginVehicleschedulerVehicle::dropdown([
            'name'  => 'plugin_vehiclescheduler_vehicles_id',
            'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0,
        ]);
        echo "</td><td colspan='2'></td></tr>";

        echo "<tr class='table-row'>";
        echo "<td>Descrição</td>";
        echo "<td colspan='3'><textarea name='description' rows='3' style='width:98%;'>"
            . htmlspecialchars($this->fields['description'] ?? '') . "</textarea></td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    function rawSearchOptions() {
        $tab   = [];
        $tab[] = ['id' => 'common', 'name' => 'Infrações de Trânsito'];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'id',
                  'name' => 'ID', 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => self::getTable(), 'field' => 'fine_date',
                  'name' => 'Data', 'datatype' => 'date'];
        $tab[] = ['id' => '3', 'table' => self::getTable(), 'field' => 'description',
                  'name' => 'Descrição', 'datatype' => 'text'];
        $tab[] = ['id' => '4', 'table' => self::getTable(), 'field' => 'severity',
                  'name' => 'Severidade', 'datatype' => 'specific', 'searchtype' => ['equals']];
        $tab[] = ['id' => '5', 'table' => self::getTable(), 'field' => 'status',
                  'name' => 'Status', 'datatype' => 'specific', 'searchtype' => ['equals']];
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'severity') return self::getAllSeverities()[$values[$field]] ?? $values[$field];
        if ($field === 'status')   return self::getAllStatus()[$values[$field]]    ?? $values[$field];
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
