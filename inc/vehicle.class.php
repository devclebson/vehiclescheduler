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
        
        // Esconder a tabela padrão do GLPI e injetar nosso HTML
        echo "<tr style='display:none;'><td></td></tr>";
        echo "<tr><td colspan='4' style='padding:0; border:none; background:transparent;'>";
        
        echo "<div class='container-fluid px-3 py-4'>";
        
        // Back Button
        echo "<div class='d-flex justify-content-end mb-3'>
                <a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'>
                    <i class='ti ti-arrow-left'></i> Voltar
                </a>
              </div>";

        // Card: Características
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-car'></i> Dados do Veículo</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";
        
        // Row 1
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Nome <span class='text-danger'>*</span></label>
                            <input type='text' name='name' value='".htmlspecialchars($this->fields['name'] ?? '')."' class='form-control form-control-lg'>
                        </div>";
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Placa <span class='text-danger'>*</span></label>
                            <input type='text' name='plate' value='".htmlspecialchars($this->fields['plate'] ?? '')."' class='form-control form-control-lg'>
                        </div>";
        
        // Row 2
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Marca</label>
                            <input type='text' name='brand' value='".htmlspecialchars($this->fields['brand'] ?? '')."' class='form-control'>
                        </div>";
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Modelo</label>
                            <input type='text' name='model' value='".htmlspecialchars($this->fields['model'] ?? '')."' class='form-control'>
                        </div>";

        // Row 3
        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Ano</label>
                            <input type='number' name='year' value='".($this->fields['year'] ?? 2020)."' min='1900' max='2100' class='form-control'>
                        </div>";
        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Nº de Assentos</label>
                            <input type='number' name='seats' value='".($this->fields['seats'] ?: 5)."' min='1' max='100' class='form-control'>
                        </div>";
        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Status</label>
                            <select name='is_active' class='form-select'>
                                <option value='1' ".($this->fields['is_active'] == 1 ? 'selected' : '').">Ativo na Frota</option>
                                <option value='0' ".($this->fields['is_active'] == 0 ? 'selected' : '').">Inativo</option>
                            </select>
                        </div>";
        
        echo "      </div>
                </div>
              </div>";

        // Card 2: Observações
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-align-left'></i> Observações</h5>
                </div>
                <div class='card-body'>
                    <textarea name='comment' rows='4' class='form-control'>".htmlspecialchars($this->fields['comment'] ?? '')."</textarea>
                </div>
              </div>";

        echo "</div>"; // Container End
        echo "</td></tr>";
        
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
        $tab[] = ['id' => '8', 'table' => $this->getTable(), 'field' => 'id',
                  'name' => 'ID', 'datatype' => 'integer'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'comment',
                  'name' => 'Observações', 'datatype' => 'text'];
        return $tab;
    }
}
