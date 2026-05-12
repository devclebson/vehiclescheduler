<?php
/**
 * Plugin Vehicle Scheduler for GLPI.
 * Vehicle reports for fleet operations.
 */

if (!defined('GLPI_ROOT')) {
    die("Acesso direto nÃ£o permitido");
}

class PluginVehicleschedulerVehiclereport extends CommonDBTM
{
    public $dohistory = true;

    public static $rightname = 'plugin_vehiclescheduler';

    public const TYPE_MAINTENANCE = 1;
    public const TYPE_PROBLEM = 2;
    public const TYPE_ACCIDENT = 3;
    public const TYPE_OBSERVATION = 4;

    public static function getTypeName($nb = 0)
    {
        return ($nb === 1) ? 'RelatÃ³rio de VeÃ­culo' : 'RelatÃ³rios de VeÃ­culos';
    }

    public static function getMenuName()
    {
        return 'RelatÃ³rios de VeÃ­culos';
    }

    public static function getIcon()
    {
        return 'ti ti-file-report';
    }

    public static function getAllTypes()
    {
        return [
            self::TYPE_MAINTENANCE => 'Necessita ManutenÃ§Ã£o',
            self::TYPE_PROBLEM     => 'Problema / Defeito',
            self::TYPE_ACCIDENT    => 'Acidente',
            self::TYPE_OBSERVATION => 'ObservaÃ§Ã£o Geral',
        ];
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
            return false;
        }

        $menu = [];
        $menu['title'] = 'RelatÃ³rios de VeÃ­culos';
        $menu['page'] = '/plugins/vehiclescheduler/front/vehiclereport.php';
        $menu['icon'] = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/vehiclereport.php';

        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/vehiclereport.form.php';
        }

        $menu['options']['vehiclereport'] = [
            'title'          => 'RelatÃ³rios de VeÃ­culos',
            'page'           => '/plugins/vehiclescheduler/front/vehiclereport.php',
            'icon'           => self::getIcon(),
            'links'          => [
                'search' => '/plugins/vehiclescheduler/front/vehiclereport.php',
                'add'    => '/plugins/vehiclescheduler/front/vehiclereport.form.php',
            ],
            'lists_itemtype' => self::class,
        ];

        return $menu;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr><td colspan='4'>";
        require_once GLPI_ROOT . '/plugins/vehiclescheduler/front/vehiclereport.render.php';
        vs_render_vehiclereport_form($this);
        echo "</td></tr>";

        $this->showFormButtons($options);

        return true;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->normalizeReportInput($input);

        if ($input['plugin_vehiclescheduler_vehicles_id'] <= 0) {
            Session::addMessageAfterRedirect('O veÃ­culo Ã© obrigatÃ³rio.', false, ERROR);
            return false;
        }

        if ($input['description'] === '') {
            Session::addMessageAfterRedirect('A descriÃ§Ã£o Ã© obrigatÃ³ria.', false, ERROR);
            return false;
        }

        if ($input['users_id'] <= 0) {
            $input['users_id'] = (int) Session::getLoginUserID();
        }

        if ($input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->normalizeReportInput($input);

        if ($input['id'] <= 0) {
            Session::addMessageAfterRedirect('ID do relatÃ³rio invÃ¡lido.', false, ERROR);
            return false;
        }

        return $this->prepareInputForAdd($input);
    }

    public function rawSearchOptions()
    {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => 'RelatÃ³rios de VeÃ­culos'];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'id', 'name' => 'ID', 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => 'glpi_plugin_vehiclescheduler_vehicles', 'field' => 'name', 'name' => 'VeÃ­culo', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'report_type', 'name' => 'Tipo', 'datatype' => 'specific'];
        $tab[] = ['id' => '4', 'table' => 'glpi_users', 'field' => 'name', 'name' => 'Reportado por', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'department', 'name' => 'Departamento/Setor', 'datatype' => 'string'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'contact_phone', 'name' => 'Telefone', 'datatype' => 'string'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'report_date', 'name' => 'Data do RelatÃ³rio', 'datatype' => 'datetime'];
        $tab[] = ['id' => '8', 'table' => $this->getTable(), 'field' => 'description', 'name' => 'DescriÃ§Ã£o', 'datatype' => 'text'];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'report_type') {
            return self::getAllTypes()[$values[$field]] ?? $values[$field];
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private function normalizeReportInput(array $input): array
    {
        return [
            'id' => PluginVehicleschedulerInput::int($input, 'id', 0, 0),
            'entities_id' => PluginVehicleschedulerInput::int(
                $input,
                'entities_id',
                (int) ($_SESSION['glpiactive_entity'] ?? 0),
                0
            ),
            'plugin_vehiclescheduler_vehicles_id' => PluginVehicleschedulerInput::int(
                $input,
                'plugin_vehiclescheduler_vehicles_id',
                0,
                0
            ),
            'report_type' => PluginVehicleschedulerInput::int(
                $input,
                'report_type',
                self::TYPE_OBSERVATION,
                self::TYPE_MAINTENANCE,
                self::TYPE_OBSERVATION
            ),
            'users_id' => PluginVehicleschedulerInput::int(
                $input,
                'users_id',
                (int) Session::getLoginUserID(),
                0
            ),
            'department' => PluginVehicleschedulerInput::string($input, 'department', 255, ''),
            'contact_phone' => PluginVehicleschedulerInput::string($input, 'contact_phone', 50, ''),
            'report_date' => PluginVehicleschedulerInput::datetime($input, 'report_date', date('Y-m-d H:i:s')),
            'description' => PluginVehicleschedulerInput::text($input, 'description', 65535, ''),
            'comment' => PluginVehicleschedulerInput::text($input, 'comment', 65535, ''),
        ];
    }
}
