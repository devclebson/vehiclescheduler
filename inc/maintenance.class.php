<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerMaintenance extends CommonDBTM
{
    public $dohistory = true;
    public static $rightname = 'plugin_vehiclescheduler';

    public const TYPE_PREVENTIVE = 1;
    public const TYPE_CORRECTIVE = 2;

    public const STATUS_SCHEDULED = 1;
    public const STATUS_IN_PROGRESS = 2;
    public const STATUS_DONE = 3;
    public const STATUS_CANCELLED = 4;

    public static function getTypeName($nb = 0)
    {
        return _n('Maintenance', 'Maintenances', $nb, 'vehiclescheduler');
    }

    public static function getMenuName()
    {
        return 'Manutencoes';
    }

    public static function getIcon()
    {
        return 'ti ti-tool';
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
            return false;
        }

        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page'] = '/plugins/vehiclescheduler/front/maintenance.php';
        $menu['icon'] = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/maintenance.php';

        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/maintenance.form.php';
        }

        $menu['options']['maintenance'] = [
            'title'          => self::getTypeName(2),
            'page'           => '/plugins/vehiclescheduler/front/maintenance.php',
            'icon'           => self::getIcon(),
            'links'          => [
                'search' => '/plugins/vehiclescheduler/front/maintenance.php',
                'add'    => '/plugins/vehiclescheduler/front/maintenance.form.php',
            ],
            'lists_itemtype' => 'PluginVehicleschedulerMaintenance',
        ];

        return $menu;
    }

    public static function getAllTypes(): array
    {
        return [
            self::TYPE_PREVENTIVE => 'Preventiva',
            self::TYPE_CORRECTIVE => 'Corretiva',
        ];
    }

    public static function getAllStatus(): array
    {
        return [
            self::STATUS_SCHEDULED   => 'Agendada',
            self::STATUS_IN_PROGRESS => 'Em Andamento',
            self::STATUS_DONE        => 'Concluida',
            self::STATUS_CANCELLED   => 'Cancelada',
        ];
    }

    public function defineTabs($options = []): array
    {
        $tabs = [];
        $this->addDefaultFormTab($tabs);
        $this->addStandardTab('Log', $tabs, $options);

        return $tabs;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->normalizeInput($input);

        if ((int) ($input['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('O veiculo e obrigatorio.', false, ERROR);

            return false;
        }

        if (!isset($input['entities_id']) || (int) $input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        if ((int) ($input['plugin_vehiclescheduler_incidents_id'] ?? 0) > 0) {
            $input['type'] = self::TYPE_CORRECTIVE;
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->normalizeInput($input);

        if ((int) ($input['id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('Manutencao invalida.', false, ERROR);

            return false;
        }

        if ((int) ($input['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('O veiculo e obrigatorio.', false, ERROR);

            return false;
        }

        if ((int) ($input['plugin_vehiclescheduler_incidents_id'] ?? 0) > 0) {
            $input['type'] = self::TYPE_CORRECTIVE;
        }

        return $input;
    }

    public function rawSearchOptions(): array
    {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => self::getTypeName(2)];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => 'ID',
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => '2',
            'table'    => 'glpi_plugin_vehiclescheduler_vehicles',
            'field'    => 'name',
            'name'     => 'Veiculo',
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id'         => '3',
            'table'      => $this->getTable(),
            'field'      => 'type',
            'name'       => 'Tipo',
            'datatype'   => 'specific',
            'searchtype' => ['equals'],
        ];

        $tab[] = [
            'id'         => '4',
            'table'      => $this->getTable(),
            'field'      => 'status',
            'name'       => 'Status',
            'datatype'   => 'specific',
            'searchtype' => ['equals'],
        ];

        $tab[] = [
            'id'       => '5',
            'table'    => $this->getTable(),
            'field'    => 'scheduled_date',
            'name'     => 'Data Agendada',
            'datatype' => 'date',
        ];

        $tab[] = [
            'id'       => '6',
            'table'    => $this->getTable(),
            'field'    => 'cost',
            'name'     => 'Custo (R$)',
            'datatype' => 'decimal',
        ];

        $tab[] = [
            'id'       => '7',
            'table'    => $this->getTable(),
            'field'    => 'supplier',
            'name'     => 'Fornecedor',
            'datatype' => 'string',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'type') {
            return self::getAllTypes()[(int) ($values[$field] ?? 0)] ?? '';
        }

        if ($field === 'status') {
            return self::getAllStatus()[(int) ($values[$field] ?? 0)] ?? '';
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private function normalizeInput(array $input): array
    {
        $input['type'] = PluginVehicleschedulerInput::int(
            $input,
            'type',
            self::TYPE_PREVENTIVE,
            self::TYPE_PREVENTIVE,
            self::TYPE_CORRECTIVE
        );
        $input['plugin_vehiclescheduler_vehicles_id'] = PluginVehicleschedulerInput::int(
            $input,
            'plugin_vehiclescheduler_vehicles_id',
            0,
            0
        );
        $input['plugin_vehiclescheduler_incidents_id'] = PluginVehicleschedulerInput::int(
            $input,
            'plugin_vehiclescheduler_incidents_id',
            0,
            0
        );
        $input['scheduled_date'] = PluginVehicleschedulerInput::date($input, 'scheduled_date', null) ?? '';
        $input['completion_date'] = PluginVehicleschedulerInput::date($input, 'completion_date', null) ?? '';
        $input['supplier'] = PluginVehicleschedulerInput::string($input, 'supplier', 255);
        $input['cost'] = $this->normalizeDecimal((string) ($input['cost'] ?? '0'));
        $input['mileage'] = PluginVehicleschedulerInput::int($input, 'mileage', 0, 0);
        $input['status'] = PluginVehicleschedulerInput::int(
            $input,
            'status',
            self::STATUS_SCHEDULED,
            self::STATUS_SCHEDULED,
            self::STATUS_CANCELLED
        );
        $input['description'] = PluginVehicleschedulerInput::text($input, 'description', 5000);

        if (array_key_exists('id', $input)) {
            $input['id'] = PluginVehicleschedulerInput::int($input, 'id', 0, 0);
        }

        if (array_key_exists('entities_id', $input)) {
            $input['entities_id'] = PluginVehicleschedulerInput::int($input, 'entities_id', 0, 0);
        }

        return $input;
    }

    private function normalizeDecimal(string $value): string
    {
        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '' || !is_numeric($normalized)) {
            return '0.00';
        }

        return number_format((float) $normalized, 2, '.', '');
    }
}
