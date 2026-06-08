<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Maintenance class — preventive and corrective maintenance
 */
if (!defined('GLPI_ROOT')) { die("Sorry. You can't access this file directly"); }

class PluginVehicleschedulerMaintenance extends CommonDBTM {

    public $dohistory = true;
    static $rightname = 'plugin_vehiclescheduler';

    const TYPE_PREVENTIVE = 1;
    const TYPE_CORRECTIVE = 2;

    const STATUS_SCHEDULED  = 1;
    const STATUS_IN_PROGRESS= 2;
    const STATUS_DONE       = 3;
    const STATUS_CANCELLED  = 4;

    static function getTypeName($nb = 0) {
        return _n('Maintenance', 'Maintenances', $nb, 'vehiclescheduler');
    }
    static function getMenuName() { return __('Maintenances', 'vehiclescheduler'); }
    static function getIcon()     { return 'ti ti-tool'; }

    static function getMenuContent() {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) return false;
        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page']  = '/plugins/vehiclescheduler/front/maintenance.php';
        $menu['icon']  = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/maintenance.php';
        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/maintenance.form.php';
        }
        $menu['options']['maintenance'] = [
            'title' => self::getTypeName(2),
            'page'  => '/plugins/vehiclescheduler/front/maintenance.php',
            'icon'  => self::getIcon(),
            'links' => ['search' => '/plugins/vehiclescheduler/front/maintenance.php',
                        'add'    => '/plugins/vehiclescheduler/front/maintenance.form.php'],
            'lists_itemtype' => 'PluginVehicleschedulerMaintenance',
        ];
        return $menu;
    }

    static function getAllTypes() {
        return [
            self::TYPE_PREVENTIVE => __('Preventive', 'vehiclescheduler'),
            self::TYPE_CORRECTIVE => __('Corrective', 'vehiclescheduler'),
        ];
    }

    static function getAllStatus() {
        return [
            self::STATUS_SCHEDULED   => __('Scheduled', 'vehiclescheduler'),
            self::STATUS_IN_PROGRESS => __('In Progress', 'vehiclescheduler'),
            self::STATUS_DONE        => __('Done', 'vehiclescheduler'),
            self::STATUS_CANCELLED   => __('Cancelled', 'vehiclescheduler'),
        ];
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
        
        echo "<tr style='display:none;'><td></td></tr>";
        echo "<tr><td colspan='4' style='padding:0; border:none; background:transparent;'>";
        
        echo "<div class='container-fluid px-3 py-4'>";
        
        // Back Button
        echo "<div class='d-flex justify-content-end mb-3'>
                <a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'>
                    <i class='ti ti-arrow-left'></i> Voltar
                </a>
              </div>";

        // Card 1: Dados da Manutenção
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-tool'></i> Ordem de Manutenção</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Título', 'vehiclescheduler') . " <span class='text-danger'>*</span></label>
                            <input type='text' name='name' value='".htmlspecialchars($this->fields['name'] ?? '')."' class='form-control form-control-lg'>
                        </div>";
                        
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Veículo', 'vehiclescheduler') . " <span class='text-danger'>*</span></label>";
        echo "              <div>";
        PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>" . __('Tipo', 'vehiclescheduler') . " <span class='text-danger'>*</span></label>";
        echo "              <div>";
        Dropdown::showFromArray('type', self::getAllTypes(), ['value' => $this->fields['type'] ?? self::TYPE_PREVENTIVE]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>" . __('Status do Serviço', 'vehiclescheduler') . "</label>";
        echo "              <div>";
        Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status'] ?? self::STATUS_SCHEDULED]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>" . __('Quilometragem (km)', 'vehiclescheduler') . "</label>
                            <input type='number' name='mileage' value='".htmlspecialchars($this->fields['mileage'] ?? '')."' class='form-control'>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Data Agendada', 'vehiclescheduler') . "</label>
                            <div>";
        Html::showDateField('scheduled_date', ['value' => $this->fields['scheduled_date'] ?? '']);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Data de Conclusão', 'vehiclescheduler') . "</label>
                            <div>";
        Html::showDateField('completion_date', ['value' => $this->fields['completion_date'] ?? '']);
        echo "              </div>
                        </div>";

        echo "          <div class='col-12'>
                            <label class='form-label text-muted fw-bold'>" . __('Descrição do Serviço', 'vehiclescheduler') . "</label>
                            <textarea name='description' class='form-control' rows='4' placeholder='Detalhes do que será / foi realizado'>".htmlspecialchars($this->fields['description'] ?? '')."</textarea>
                        </div>";

        echo "      </div>
                </div>
              </div>";

        // Card 2: Financeiro e Fornecedor
        echo "<div class='card shadow-sm border-0 mb-4' style='border-left: 4px solid #10b981 !important;'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-success fw-bold'><i class='ti ti-cash'></i> Financeiro e Fornecedor</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Oficina / Fornecedor', 'vehiclescheduler') . "</label>
                            <input type='text' name='supplier' value='".htmlspecialchars($this->fields['supplier'] ?? '')."' class='form-control' placeholder='Nome da oficina'>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Custo Total (R$)', 'vehiclescheduler') . "</label>
                            <div class='input-group'>
                                <span class='input-group-text'>R$</span>
                                <input type='number' name='cost' value='".htmlspecialchars($this->fields['cost'] ?? '')."' class='form-control' step='0.01' placeholder='0.00'>
                            </div>
                        </div>";

        echo "      </div>
                </div>
              </div>";

        // Card 3: Vínculo com Incidente
        $inc_id = $this->fields['plugin_vehiclescheduler_incidents_id'] ?? 0;
        if (isset($_GET['plugin_vehiclescheduler_incidents_id'])) {
            $inc_id = $_GET['plugin_vehiclescheduler_incidents_id'];
            echo "<input type='hidden' name='plugin_vehiclescheduler_incidents_id' value='".intval($inc_id)."'>";
        }
        
        if ($inc_id) {
            echo "<div class='alert alert-secondary d-flex align-items-center border-0 shadow-sm'>
                    <i class='ti ti-alert-triangle me-2 fs-4 text-warning'></i>
                    <div>
                        <strong>" . __('Origem do Serviço', 'vehiclescheduler') . ":</strong> Incidente Reportado — ";
            $inc = new PluginVehicleschedulerIncident();
            if ($inc->getFromDB($inc_id)) echo $inc->getLink();
            echo "  </div>
                  </div>";
        }

        echo "</div>"; // Container End
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    function prepareInputForAdd($input) {
        if (empty(trim($input['name'] ?? ''))) {
            Session::addMessageAfterRedirect(__('Title is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }
        if (empty($input['plugin_vehiclescheduler_vehicles_id'])) {
            Session::addMessageAfterRedirect(__('Vehicle is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }
        if (!isset($input['status']))           $input['status'] = self::STATUS_SCHEDULED;
        if (!isset($input['entities_id']))       $input['entities_id'] = $_SESSION['glpiactive_entity'];
        if (!isset($input['type']))  $input['type'] = self::TYPE_PREVENTIVE;
        return $input;
    }

    function prepareInputForUpdate($input) { return $this->prepareInputForAdd($input); }

    function rawSearchOptions() {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => self::getTypeName(2)];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'name',   'name' => __('Title', 'vehiclescheduler'), 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => 'glpi_plugin_vehiclescheduler_vehicles', 'field' => 'name', 'name' => __('Vehicle', 'vehiclescheduler'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'type', 'name' => __('Type', 'vehiclescheduler'), 'datatype' => 'specific', 'searchtype' => ['equals']];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'status',           'name' => __('Status'), 'datatype' => 'specific', 'searchtype' => ['equals']];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'scheduled_date',   'name' => __('Scheduled Date', 'vehiclescheduler'), 'datatype' => 'date'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'cost',             'name' => __('Cost (R$)', 'vehiclescheduler'), 'datatype' => 'decimal'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'supplier',         'name' => __('Supplier/Workshop', 'vehiclescheduler'), 'datatype' => 'string'];
        $tab[] = ['id' => '8', 'table' => $this->getTable(), 'field' => 'id',               'name' => 'ID', 'datatype' => 'integer'];
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'type') return self::getAllTypes()[$values[$field]] ?? '';
        if ($field === 'status')           return self::getAllStatus()[$values[$field]] ?? '';
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
