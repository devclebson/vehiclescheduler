<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Vehicle — Cadastro de Veículos
 */

if (!defined('GLPI_ROOT')) {
    die("Acesso direto não permitido");
}

class PluginVehicleschedulerVehicle extends CommonDBTM {

    public $dohistory = true;
    static $rightname = 'plugin_vehiclescheduler';

    static function getTypeName($nb = 0) {
        return ($nb === 1) ? 'Veículo' : 'Veículos';
    }

    static function getMenuName() {
        return 'Veículos';
    }

    static function getIcon() {
        return 'ti ti-car';
    }

    static function getMenuContent() {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
            return false;
        }
        $menu = [];
        $menu['title'] = 'Veículos';
        $menu['page']  = '/plugins/vehiclescheduler/front/vehicle.php';
        $menu['icon']  = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/vehicle.php';
        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/vehicle.form.php';
        }
        $menu['options']['vehicle'] = [
            'title'          => 'Veículos',
            'page'           => '/plugins/vehiclescheduler/front/vehicle.php',
            'icon'           => self::getIcon(),
            'links'          => [
                'search' => '/plugins/vehiclescheduler/front/vehicle.php',
                'add'    => '/plugins/vehiclescheduler/front/vehicle.form.php',
            ],
            'lists_itemtype' => 'PluginVehicleschedulerVehicle',
        ];
        return $menu;
    }

    static function dropdown($options = []) {
        $params = [
            'name'      => 'plugin_vehiclescheduler_vehicles_id',
            'value'     => 0,
            'entity'    => -1,
            'condition' => ['is_active' => 1],
            'display'   => true,
        ];
        foreach ($options as $k => $v) {
            $params[$k] = $v;
        }
        return Dropdown::show(self::class, $params);
    }

    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginVehicleschedulerSchedule', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    function showForm($ID, array $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);
        echo "<tr class='table-row'><td colspan='4' class='text-end' style='text-align: right;'><a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'><i class='ti ti-arrow-left'></i> Voltar</a></td></tr>";

        echo "<tr class='table-row'>";
        echo "<td>Nome <span class='red'>*</span></td>";
        echo "<td>" . Html::input('name', ['value' => $this->fields['name'], 'size' => 40]) . "</td>";
        echo "<td>Placa <span class='red'>*</span></td>";
        echo "<td>" . Html::input('plate', ['value' => $this->fields['plate'], 'size' => 20]) . "</td>";
        echo "</tr>";

        echo "<tr class='table-row'>";
        echo "<td>Marca</td>";
        echo "<td>" . Html::input('brand', ['value' => $this->fields['brand'], 'size' => 40]) . "</td>";
        echo "<td>Modelo</td>";
        echo "<td>" . Html::input('model', ['value' => $this->fields['model'], 'size' => 40]) . "</td>";
        echo "</tr>";

        echo "<tr class='table-row'>";
        echo "<td>Ano</td>";
        echo "<td>" . Html::input('year', [
            'value' => $this->fields['year'],
            'type'  => 'number',
            'min'   => 1900,
            'max'   => 2100,
        ]) . "</td>";
        echo "<td>Nº de Assentos</td>";
        echo "<td>" . Html::input('seats', [
            'value' => $this->fields['seats'] ?: 5,
            'type'  => 'number',
            'min'   => 1,
            'max'   => 100,
        ]) . "</td>";
        echo "</tr>";

        echo "<tr class='table-row'>";
        echo "<td>Ativo</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->fields['is_active'] ?? 1);
        echo "</td>";
        echo "<td colspan='2'></td>";
        echo "</tr>";

        echo "<tr class='table-row'>";
        echo "<td>Observações</td>";
        echo "<td colspan='3'><textarea name='comment' rows='4' style='width:98%;'>"
            . htmlspecialchars($this->fields['comment'] ?? '') . "</textarea></td>";
        echo "</tr>";

        $this->showFormButtons($options);
        return true;
    }

    function rawSearchOptions() {
        $tab   = [];
        $tab[] = ['id' => 'common', 'name' => 'Características'];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'name',
                  'name' => 'Nome', 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => $this->getTable(), 'field' => 'plate',
                  'name' => 'Placa', 'datatype' => 'string'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'brand',
                  'name' => 'Marca', 'datatype' => 'string'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'model',
                  'name' => 'Modelo', 'datatype' => 'string'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'year',
                  'name' => 'Ano', 'datatype' => 'number'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'seats',
                  'name' => 'Assentos', 'datatype' => 'number'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'is_active',
                  'name' => 'Ativo', 'datatype' => 'bool'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'comment',
                  'name' => 'Observações', 'datatype' => 'text'];
        return $tab;
    }
}
