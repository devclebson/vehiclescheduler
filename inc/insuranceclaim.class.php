<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerInsuranceclaim extends CommonDBTM
{
    public $dohistory = true;
    public static $rightname = 'plugin_vehiclescheduler';

    public const STATUS_OPENED = 1;
    public const STATUS_ANALYSIS = 2;
    public const STATUS_APPROVED = 3;
    public const STATUS_REJECTED = 4;
    public const STATUS_CLOSED = 5;

    public static function getTypeName($nb = 0)
    {
        return _n('Insurance Claim', 'Insurance Claims', $nb, 'vehiclescheduler');
    }

    public static function getMenuName()
    {
        return 'Sinistros';
    }

    public static function getIcon()
    {
        return 'ti ti-shield-check';
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
            return false;
        }

        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page'] = '/plugins/vehiclescheduler/front/insuranceclaim.php';
        $menu['icon'] = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/insuranceclaim.php';

        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/insuranceclaim.form.php';
        }

        $menu['options']['insuranceclaim'] = [
            'title'          => self::getTypeName(2),
            'page'           => '/plugins/vehiclescheduler/front/insuranceclaim.php',
            'icon'           => self::getIcon(),
            'links'          => [
                'search' => '/plugins/vehiclescheduler/front/insuranceclaim.php',
                'add'    => '/plugins/vehiclescheduler/front/insuranceclaim.form.php',
            ],
            'lists_itemtype' => 'PluginVehicleschedulerInsuranceclaim',
        ];

        return $menu;
    }

    public static function getAllStatus(): array
    {
        return [
            self::STATUS_OPENED   => 'Aberto',
            self::STATUS_ANALYSIS => 'Em Analise',
            self::STATUS_APPROVED => 'Aprovado',
            self::STATUS_REJECTED => 'Rejeitado',
            self::STATUS_CLOSED   => 'Fechado',
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

        if ($input['opening_date'] === '') {
            $input['opening_date'] = date('Y-m-d');
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->normalizeInput($input);

        if ((int) ($input['id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('Sinistro invalido.', false, ERROR);

            return false;
        }

        if ((int) ($input['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('O veiculo e obrigatorio.', false, ERROR);

            return false;
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
            'field'         => 'claim_number',
            'name'          => 'N Sinistro',
            'datatype'      => 'string',
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
            'field'      => 'status',
            'name'       => 'Status',
            'datatype'   => 'specific',
            'searchtype' => ['equals'],
        ];

        $tab[] = [
            'id'       => '4',
            'table'    => $this->getTable(),
            'field'    => 'opening_date',
            'name'     => 'Data Abertura',
            'datatype' => 'date',
        ];

        $tab[] = [
            'id'       => '5',
            'table'    => $this->getTable(),
            'field'    => 'insurance_company',
            'name'     => 'Seguradora',
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => '6',
            'table'    => $this->getTable(),
            'field'    => 'approved_value',
            'name'     => 'Valor Aprovado (R$)',
            'datatype' => 'decimal',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'status') {
            return self::getAllStatus()[(int) ($values[$field] ?? 0)] ?? '';
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private function normalizeInput(array $input): array
    {
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
        $input['claim_number'] = PluginVehicleschedulerInput::string($input, 'claim_number', 100);
        $input['status'] = PluginVehicleschedulerInput::int(
            $input,
            'status',
            self::STATUS_OPENED,
            self::STATUS_OPENED,
            self::STATUS_CLOSED
        );
        $input['opening_date'] = PluginVehicleschedulerInput::date($input, 'opening_date', date('Y-m-d')) ?? '';
        $input['closing_date'] = PluginVehicleschedulerInput::date($input, 'closing_date', null) ?? '';
        $input['insurance_company'] = PluginVehicleschedulerInput::string($input, 'insurance_company', 255);
        $input['contact_name'] = PluginVehicleschedulerInput::string($input, 'contact_name', 255);
        $input['estimated_value'] = $this->normalizeDecimal((string) ($input['estimated_value'] ?? '0'));
        $input['approved_value'] = $this->normalizeDecimal((string) ($input['approved_value'] ?? '0'));
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
