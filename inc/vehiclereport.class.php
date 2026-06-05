<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * VehicleReport — Relatórios sobre veículos
 */

if (!defined('GLPI_ROOT')) {
    die("Acesso direto não permitido");
}

class PluginVehicleschedulerVehiclereport extends CommonDBTM {

    public $dohistory = true;
    static $rightname = 'plugin_vehiclescheduler';

    const TYPE_MAINTENANCE = 1;
    const TYPE_PROBLEM     = 2;
    const TYPE_ACCIDENT    = 3;
    const TYPE_OBSERVATION = 4;

    static function getTypeName($nb = 0) {
        return ($nb === 1) ? 'Relatório de Veículo' : 'Relatórios de Veículos';
    }

    static function getMenuName() {
        return 'Relatórios de Veículos';
    }

    static function getIcon() {
        return 'ti ti-file-report';
    }

    static function getAllTypes() {
        return [
            self::TYPE_MAINTENANCE => 'Necessita Manutenção',
            self::TYPE_PROBLEM     => 'Problema / Defeito',
            self::TYPE_ACCIDENT    => 'Acidente',
            self::TYPE_OBSERVATION => 'Observação Geral',
        ];
    }

    static function getMenuContent() {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
            return false;
        }
        $menu = [];
        $menu['title'] = 'Relatórios de Veículos';
        $menu['page']  = '/plugins/vehiclescheduler/front/vehiclereport.php';
        $menu['icon']  = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/vehiclereport.php';
        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/vehiclereport.form.php';
        }
        $menu['options']['vehiclereport'] = [
            'title'          => 'Relatórios de Veículos',
            'page'           => '/plugins/vehiclescheduler/front/vehiclereport.php',
            'icon'           => self::getIcon(),
            'links'          => [
                'search' => '/plugins/vehiclescheduler/front/vehiclereport.php',
                'add'    => '/plugins/vehiclescheduler/front/vehiclereport.form.php',
            ],
            'lists_itemtype' => 'PluginVehicleschedulerVehiclereport',
        ];
        return $menu;
    }

    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    function showForm($ID, array $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        echo "<tr class='table-row'><td colspan='4' class='text-end' style='text-align: right;'><a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'><i class='ti ti-arrow-left'></i> Voltar</a></td></tr>";

        echo "<tr class='table-row'><td colspan='4' class='center'>"
            . "<h3>Relatório de Veículo</h3></td></tr>";

        echo "<tr class='table-row'>";
        echo "<td>Veículo <span class='red'>*</span></td><td>";
        PluginVehicleschedulerVehicle::dropdown([
            'name'   => 'plugin_vehiclescheduler_vehicles_id',
            'value'  => $this->fields['plugin_vehiclescheduler_vehicles_id'],
            'entity' => $this->fields['entities_id'],
        ]);
        echo "</td>";
        echo "<td>Tipo de Relatório <span class='red'>*</span></td><td>";
        Dropdown::showFromArray('report_type', self::getAllTypes(), [
            'value' => $this->fields['report_type'] ?: self::TYPE_OBSERVATION,
        ]);
        echo "</td></tr>";

        echo "<tr class='table-row'>";
        echo "<td>Reportado por</td><td>";
        User::dropdown(['name' => 'users_id', 'value' => $this->fields['users_id'], 'right' => 'all']);
        echo "</td>";
        echo "<td>Departamento/Setor</td>";
        echo "<td>" . Html::input('department', ['value' => $this->fields['department'], 'size' => 40]) . "</td>";
        echo "</tr>";

        echo "<tr class='table-row'>";
        echo "<td>Telefone para Contato</td>";
        echo "<td>" . Html::input('contact_phone', ['value' => $this->fields['contact_phone'], 'size' => 20]) . "</td>";
        echo "<td>Data do Relatório</td><td>";
        Html::showDateTimeField('report_date', [
            'value' => $this->fields['report_date'] ?: date('Y-m-d H:i:s'),
        ]);
        echo "</td></tr>";

        echo "<tr class='table-row'>";
        echo "<td>Descrição <span class='red'>*</span></td>";
        echo "<td colspan='3'><textarea name='description' rows='6' style='width:98%;'"
            . " placeholder='Descreva o problema, observação ou situação em detalhes'>"
            . htmlspecialchars($this->fields['description'] ?? '')
            . "</textarea></td></tr>";

        echo "<tr class='table-row'>";
        echo "<td>Comentários Adicionais</td>";
        echo "<td colspan='3'><textarea name='comment' rows='3' style='width:98%;'>"
            . htmlspecialchars($this->fields['comment'] ?? '') . "</textarea></td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    function prepareInputForAdd($input) {
        if (empty($input['plugin_vehiclescheduler_vehicles_id'])) {
            Session::addMessageAfterRedirect('O veículo é obrigatório.', false, ERROR);
            return false;
        }
        if (empty(trim($input['description'] ?? ''))) {
            Session::addMessageAfterRedirect('A descrição é obrigatória.', false, ERROR);
            return false;
        }
        if (!isset($input['users_id']) || $input['users_id'] == 0) {
            $input['users_id'] = Session::getLoginUserID();
        }
        if (!isset($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
        }
        return $input;
    }

    function rawSearchOptions() {
        $tab   = [];
        $tab[] = ['id' => 'common', 'name' => 'Relatórios de Veículos'];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'id',
                  'name' => 'ID', 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => 'glpi_plugin_vehiclescheduler_vehicles', 'field' => 'name',
                  'name' => 'Veículo', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'report_type',
                  'name' => 'Tipo', 'datatype' => 'specific'];
        $tab[] = ['id' => '4', 'table' => 'glpi_users', 'field' => 'name',
                  'name' => 'Reportado por', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'department',
                  'name' => 'Departamento/Setor', 'datatype' => 'string'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'contact_phone',
                  'name' => 'Telefone', 'datatype' => 'string'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'report_date',
                  'name' => 'Data do Relatório', 'datatype' => 'datetime'];
        $tab[] = ['id' => '8', 'table' => $this->getTable(), 'field' => 'description',
                  'name' => 'Descrição', 'datatype' => 'text'];
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'report_type') {
            return self::getAllTypes()[$values[$field]] ?? $values[$field];
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
