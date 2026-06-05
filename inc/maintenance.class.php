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
        
        echo "<form method='post' action='" . $this->getFormURL() . "' enctype='multipart/form-data'>";
        echo "<div class='container-fluid mb-4'><div class='card'>";
        echo "<div class='card-header d-flex justify-content-between align-items-center'><h3 class='card-title'>" . __('Maintenance', 'vehiclescheduler') . "</h3> <a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'><i class='ti ti-arrow-left'></i> Voltar</a></div>";
        echo "<div class='card-body'>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Title', 'vehiclescheduler') . " <span class='red'>*</span></label>";
        echo Html::input('name', ['value' => $this->fields['name'] ?? '', 'class' => 'form-control']);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Type', 'vehiclescheduler') . " <span class='red'>*</span></label><br>";
        Dropdown::showFromArray('type', self::getAllTypes(), ['value' => $this->fields['type'] ?? self::TYPE_PREVENTIVE]);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Vehicle', 'vehiclescheduler') . " <span class='red'>*</span></label><br>";
        PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0]);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Status') . "</label><br>";
        Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status'] ?? self::STATUS_SCHEDULED]);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Scheduled Date', 'vehiclescheduler') . "</label><br>";
        Html::showDateField('scheduled_date', ['value' => $this->fields['scheduled_date'] ?? '']);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Completion Date', 'vehiclescheduler') . "</label><br>";
        Html::showDateField('completion_date', ['value' => $this->fields['completion_date'] ?? '']);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Supplier/Workshop', 'vehiclescheduler') . "</label>";
        echo Html::input('supplier', ['value' => $this->fields['supplier'] ?? '', 'class' => 'form-control']);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Cost (R$)', 'vehiclescheduler') . "</label>";
        echo Html::input('cost', ['value' => $this->fields['cost'] ?? '', 'class' => 'form-control', 'type' => 'number', 'step' => '0.01']);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Mileage (km)', 'vehiclescheduler') . "</label>";
        echo Html::input('mileage', ['value' => $this->fields['mileage'] ?? '', 'class' => 'form-control', 'type' => 'number']);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>" . __('Origin Incident', 'vehiclescheduler') . "</label><br>";
        $inc_id = $this->fields['plugin_vehiclescheduler_incidents_id'] ?? 0;
        if ($inc_id) {
            $inc = new PluginVehicleschedulerIncident();
            if ($inc->getFromDB($inc_id)) echo $inc->getLink();
        } else {
            echo Html::input('plugin_vehiclescheduler_incidents_id', ['value' => 0, 'type' => 'hidden']);
            echo '<span class="text-muted">' . __('None', 'vehiclescheduler') . '</span>';
        }
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
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'type') return self::getAllTypes()[$values[$field]] ?? '';
        if ($field === 'status')           return self::getAllStatus()[$values[$field]] ?? '';
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
