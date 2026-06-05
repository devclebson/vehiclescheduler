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

        echo "<form method='post' action='" . $this->getFormURL() . "' enctype='multipart/form-data'>";
        echo "<div class='container-fluid mb-4'><div class='card'>";
        echo "<div class='card-header d-flex justify-content-between align-items-center'><h3 class='card-title'>" . __('Insurance Claim', 'vehiclescheduler') . "</h3> <a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'><i class='ti ti-arrow-left'></i> Voltar</a></div>";
        echo "<div class='card-body'>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Claim Number', 'vehiclescheduler') . "</label>";
        echo Html::input('name', ['value' => $this->fields['name'] ?? '', 'class' => 'form-control', 'placeholder' => __('Insurance claim reference number', 'vehiclescheduler')]);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Status') . "</label><br>";
        Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status'] ?? self::STATUS_OPENED]);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Vehicle', 'vehiclescheduler') . " <span class='red'>*</span></label><br>";
        PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0]);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Origin Incident', 'vehiclescheduler') . "</label><br>";
        $inc_id = $this->fields['plugin_vehiclescheduler_incidents_id'] ?? 0;
        if ($inc_id) {
            $inc = new PluginVehicleschedulerIncident();
            if ($inc->getFromDB($inc_id)) echo $inc->getLink();
            echo Html::hidden('plugin_vehiclescheduler_incidents_id', ['value' => $inc_id]);
        } else {
            echo Html::input('plugin_vehiclescheduler_incidents_id', ['value' => 0, 'type' => 'number', 'class' => 'form-control', 'placeholder' => 'ID']);
        }
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Opening Date', 'vehiclescheduler') . "</label><br>";
        Html::showDateField('opening_date', ['value' => $this->fields['opening_date'] ?? date('Y-m-d')]);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Closing Date', 'vehiclescheduler') . "</label><br>";
        Html::showDateField('closing_date', ['value' => $this->fields['closing_date'] ?? '']);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Insurance Company', 'vehiclescheduler') . "</label>";
        echo Html::input('insurance_company', ['value' => $this->fields['insurance_company'] ?? '', 'class' => 'form-control']);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Estimated Value (R$)', 'vehiclescheduler') . "</label>";
        echo Html::input('estimated_value', ['value' => $this->fields['estimated_value'] ?? '', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'class' => 'form-control']);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Approved Value (R$)', 'vehiclescheduler') . "</label>";
        echo Html::input('approved_value', ['value' => $this->fields['approved_value'] ?? '', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'class' => 'form-control']);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Contact at Insurer', 'vehiclescheduler') . "</label>";
        echo Html::input('insurer_contact', ['value' => $this->fields['insurer_contact'] ?? '', 'class' => 'form-control']);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-12'><label class='form-label'>" . __('Description', 'vehiclescheduler') . "</label>";
        echo "<textarea name='description' class='form-control' rows='4'>" . htmlspecialchars($this->fields['description'] ?? '') . "</textarea>";
        echo "</div></div>";

        echo "</div>"; // card-body
        echo "<div class='card-footer text-end'>";
        if ($ID > 0) {
            echo "<button type='submit' name='update' class='btn btn-primary'>" . __('Save') . "</button>";
        } else {
            echo "<button type='submit' name='add' class='btn btn-success'>" . __('Add') . "</button>";
        }
        echo "<input type='hidden' name='id' value='$ID'>";
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo "</div></div></div></form>";

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
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'status') return self::getAllStatus()[$values[$field]] ?? '';
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
