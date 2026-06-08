<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * InsuranceClaim class — insurance claims, may originate from incidents
 */
if (!defined('GLPI_ROOT')) { die("Sorry. You can't access this file directly"); }

class PluginVehicleschedulerInsuranceclaim extends CommonDBTM {

    public $dohistory = true;
    static $rightname = 'plugin_vehiclescheduler';

    const STATUS_OPENED    = 1;
    const STATUS_ANALYSIS  = 2;
    const STATUS_APPROVED  = 3;
    const STATUS_REJECTED  = 4;
    const STATUS_CLOSED    = 5;

    static function getTypeName($nb = 0) {
        return _n('Insurance Claim', 'Insurance Claims', $nb, 'vehiclescheduler');
    }
    static function getMenuName() { return __('Insurance Claims', 'vehiclescheduler'); }
    static function getIcon()     { return 'ti ti-shield-check'; }

    static function getMenuContent() {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) return false;
        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page']  = '/plugins/vehiclescheduler/front/insuranceclaim.php';
        $menu['icon']  = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/insuranceclaim.php';
        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/insuranceclaim.form.php';
        }
        $menu['options']['insuranceclaim'] = [
            'title' => self::getTypeName(2),
            'page'  => '/plugins/vehiclescheduler/front/insuranceclaim.php',
            'icon'  => self::getIcon(),
            'links' => ['search' => '/plugins/vehiclescheduler/front/insuranceclaim.php',
                        'add'    => '/plugins/vehiclescheduler/front/insuranceclaim.form.php'],
            'lists_itemtype' => 'PluginVehicleschedulerInsuranceclaim',
        ];
        return $menu;
    }

    static function getAllStatus() {
        return [
            self::STATUS_OPENED   => __('Opened', 'vehiclescheduler'),
            self::STATUS_ANALYSIS => __('Under Analysis', 'vehiclescheduler'),
            self::STATUS_APPROVED => __('Approved', 'vehiclescheduler'),
            self::STATUS_REJECTED => __('Rejected', 'vehiclescheduler'),
            self::STATUS_CLOSED   => __('Closed', 'vehiclescheduler'),
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
        if ($ID <= 0) {
            if (isset($_GET['plugin_vehiclescheduler_vehicles_id'])) $this->fields['plugin_vehiclescheduler_vehicles_id'] = (int)$_GET['plugin_vehiclescheduler_vehicles_id'];
            if (isset($_GET['plugin_vehiclescheduler_incidents_id'])) $this->fields['plugin_vehiclescheduler_incidents_id'] = (int)$_GET['plugin_vehiclescheduler_incidents_id'];
        }

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

        // Card 1: Informações do Sinistro
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-shield-check'></i> Informações do Sinistro</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Número do Sinistro', 'vehiclescheduler') . "</label>
                            <input type='text' name='name' value='".htmlspecialchars($this->fields['name'] ?? '')."' class='form-control form-control-lg' placeholder='Ex: 2024/001-A'>
                        </div>";
                        
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Status do Sinistro', 'vehiclescheduler') . "</label>";
        echo "              <div>";
        Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status'] ?? self::STATUS_OPENED]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Veículo', 'vehiclescheduler') . " <span class='text-danger'>*</span></label>";
        echo "              <div>";
        PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-3'>
                            <label class='form-label text-muted fw-bold'>" . __('Data de Abertura', 'vehiclescheduler') . "</label>
                            <div>";
        Html::showDateField('opening_date', ['value' => $this->fields['opening_date'] ?? date('Y-m-d')]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-3'>
                            <label class='form-label text-muted fw-bold'>" . __('Data de Fechamento', 'vehiclescheduler') . "</label>
                            <div>";
        Html::showDateField('closing_date', ['value' => $this->fields['closing_date'] ?? '']);
        echo "              </div>
                        </div>";

        echo "          <div class='col-12'>
                            <label class='form-label text-muted fw-bold'>" . __('Descrição Detalhada', 'vehiclescheduler') . "</label>
                            <textarea name='description' class='form-control' rows='4' placeholder='Detalhes do ocorrido e trâmites'>".htmlspecialchars($this->fields['description'] ?? '')."</textarea>
                        </div>";

        echo "      </div>
                </div>
              </div>";

        // Card 2: Seguradora e Financeiro
        echo "<div class='card shadow-sm border-0 mb-4' style='border-left: 4px solid #3b82f6 !important;'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-info fw-bold'><i class='ti ti-building-bank'></i> Seguradora e Financeiro</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Seguradora', 'vehiclescheduler') . "</label>
                            <input type='text' name='insurance_company' value='".htmlspecialchars($this->fields['insurance_company'] ?? '')."' class='form-control' placeholder='Nome da seguradora'>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Contato na Seguradora (Corretor/Analista)', 'vehiclescheduler') . "</label>
                            <input type='text' name='insurer_contact' value='".htmlspecialchars($this->fields['insurer_contact'] ?? '')."' class='form-control'>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Valor Estimado do Prejuízo (R$)', 'vehiclescheduler') . "</label>
                            <div class='input-group'>
                                <span class='input-group-text'>R$</span>
                                <input type='number' name='estimated_value' value='".htmlspecialchars($this->fields['estimated_value'] ?? '')."' class='form-control' step='0.01' min='0'>
                            </div>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Valor Aprovado/Indenizado (R$)', 'vehiclescheduler') . "</label>
                            <div class='input-group'>
                                <span class='input-group-text'>R$</span>
                                <input type='number' name='approved_value' value='".htmlspecialchars($this->fields['approved_value'] ?? '')."' class='form-control' step='0.01' min='0'>
                            </div>
                        </div>";

        echo "      </div>
                </div>
              </div>";

        // Card 3: Vínculo com Incidente
        $inc_id = $this->fields['plugin_vehiclescheduler_incidents_id'] ?? 0;
        
        if ($inc_id) {
            echo "<div class='alert alert-secondary d-flex align-items-center border-0 shadow-sm'>
                    <i class='ti ti-alert-triangle me-2 fs-4 text-warning'></i>
                    <div>
                        <strong>" . __('Incidente de Origem', 'vehiclescheduler') . ":</strong> ";
            $inc = new PluginVehicleschedulerIncident();
            if ($inc->getFromDB($inc_id)) echo $inc->getLink();
            echo Html::hidden('plugin_vehiclescheduler_incidents_id', ['value' => $inc_id]);
            echo "  </div>
                  </div>";
        } else {
            // Hidden by default, rarely linked manually
            echo Html::input('plugin_vehiclescheduler_incidents_id', ['value' => 0, 'type' => 'hidden']);
        }

        echo "</div>"; // Container End
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    function prepareInputForAdd($input) {
        if (empty($input['plugin_vehiclescheduler_vehicles_id'])) {
            Session::addMessageAfterRedirect(__('Vehicle is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }
        if (!isset($input['status']))       $input['status'] = self::STATUS_OPENED;
        if (!isset($input['entities_id'])) $input['entities_id'] = $_SESSION['glpiactive_entity'];
        if (empty($input['opening_date'])) $input['opening_date'] = date('Y-m-d');
        return $input;
    }

    function rawSearchOptions() {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => self::getTypeName(2)];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'name',             'name' => __('Claim Number', 'vehiclescheduler'), 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => 'glpi_plugin_vehiclescheduler_vehicles', 'field' => 'name', 'name' => __('Vehicle', 'vehiclescheduler'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'status',           'name' => __('Status'), 'datatype' => 'specific', 'searchtype' => ['equals']];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'opening_date',     'name' => __('Opening Date', 'vehiclescheduler'), 'datatype' => 'date'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'insurance_company','name' => __('Insurance Company', 'vehiclescheduler'), 'datatype' => 'string'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'approved_value',   'name' => __('Approved Value', 'vehiclescheduler'), 'datatype' => 'decimal'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'id',               'name' => 'ID', 'datatype' => 'integer'];
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'status') return self::getAllStatus()[$values[$field]] ?? '';
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
