<?php

/**
 * Vehicle entity for Vehicle Scheduler.
 *
 * Handles fleet vehicle registration and basic operational validation.
 */
if (!defined('GLPI_ROOT')) {
    die("Acesso direto não permitido");
}

class PluginVehicleschedulerVehicle extends \CommonDBTM
{
    public $dohistory = true;

    public static $rightname = 'plugin_vehiclescheduler_management';

    public const MIN_YEAR = 1900;
    public const MAX_YEAR = 2100;
    public const MIN_SEATS = 1;
    public const MAX_SEATS = 100;

    public const REQUIRED_CNH_A = 'A';
    public const REQUIRED_CNH_B = 'B';
    public const REQUIRED_CNH_D = 'D';

    public static function getTypeName($nb = 0)
    {
        return ($nb === 1) ? 'Veículo' : 'Veículos';
    }

    public static function getMenuName()
    {
        return 'Veículos';
    }

    public static function getIcon()
    {
        return 'ti ti-car';
    }

    public static function getRequiredCNHOptions(): array
    {
        return [
            self::REQUIRED_CNH_A => 'A - Moto',
            self::REQUIRED_CNH_B => 'B - Carro',
            self::REQUIRED_CNH_D => 'D - Caminhão ou van',
        ];
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight(self::$rightname, READ)) {
            return false;
        }

        $menu = [
            'title' => self::getMenuName(),
            'page'  => '/plugins/vehiclescheduler/front/vehicle.php',
            'icon'  => self::getIcon(),
            'links' => [
                'search' => '/plugins/vehiclescheduler/front/vehicle.php',
            ],
            'options' => [
                'vehicle' => [
                    'title'          => self::getMenuName(),
                    'page'           => '/plugins/vehiclescheduler/front/vehicle.php',
                    'icon'           => self::getIcon(),
                    'links'          => [
                        'search' => '/plugins/vehiclescheduler/front/vehicle.php',
                    ],
                    'lists_itemtype' => self::class,
                ],
            ],
        ];

        if (Session::haveRight(self::$rightname, CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/vehicle.form.php';
            $menu['options']['vehicle']['links']['add'] = '/plugins/vehiclescheduler/front/vehicle.form.php';
        }

        return $menu;
    }

    public static function dropdown($options = [])
    {
        $params = [
            'name'      => 'plugin_vehiclescheduler_vehicles_id',
            'value'     => 0,
            'condition' => ['is_active' => 1],
            'display'   => true,
        ];

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        return Dropdown::show(self::class, $params);
    }

    public static function getVehicleRequiredCNHMap(): array
    {
        global $DB;

        $table = (new self())->getTable();
        $labels = self::getRequiredCNHOptions();
        $map = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'required_cnh_category'],
            'FROM'   => $table,
            'WHERE'  => ['is_active' => 1],
            'ORDER'  => ['name ASC', 'id ASC'],
        ]);

        foreach ($iterator as $row) {
            $requiredCategory = (string) ($row['required_cnh_category'] ?? self::REQUIRED_CNH_B);

            $map[(string) ((int) $row['id'])] = [
                'id'                 => (int) $row['id'],
                'name'               => (string) ($row['name'] ?? ''),
                'requiredCategory'   => $requiredCategory,
                'requiredLabel'      => (string) ($labels[$requiredCategory] ?? $requiredCategory),
            ];
        }

        return $map;
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
        require_once GLPI_ROOT . '/plugins/vehiclescheduler/front/vehicle.render.php';
        vs_render_vehicle_form($this);
        echo "</td></tr>";

        $this->showFormButtons($options);

        return true;
    }

    public function prepareInputForAdd($input)
    {
        $input = self::normalizeVehicleInput($input);

        if ($input['name'] === '') {
            Session::addMessageAfterRedirect('O nome do veÃ­culo Ã© obrigatÃ³rio.', false, ERROR);
            return false;
        }

        if ($input['plate'] === '') {
            Session::addMessageAfterRedirect('A placa Ã© obrigatÃ³ria.', false, ERROR);
            return false;
        }

        if ($input['year'] < self::MIN_YEAR || $input['year'] > self::MAX_YEAR) {
            Session::addMessageAfterRedirect('O ano informado Ã© invÃ¡lido.', false, ERROR);
            return false;
        }

        if ($input['seats'] < self::MIN_SEATS || $input['seats'] > self::MAX_SEATS) {
            Session::addMessageAfterRedirect('A capacidade de passageiros Ã© invÃ¡lida.', false, ERROR);
            return false;
        }

        if (!array_key_exists($input['required_cnh_category'], self::getRequiredCNHOptions())) {
            Session::addMessageAfterRedirect('Selecione a categoria de CNH exigida para a viatura.', false, ERROR);
            return false;
        }

        if (self::isPlateAlreadyUsed($input['plate'])) {
            Session::addMessageAfterRedirect('A placa informada jÃ¡ estÃ¡ em uso.', false, ERROR);
            return false;
        }

        if ($input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = self::normalizeVehicleInput($input);

        if ($input['id'] <= 0) {
            Session::addMessageAfterRedirect('ID do veÃ­culo invÃ¡lido.', false, ERROR);
            return false;
        }

        if ($input['name'] === '') {
            Session::addMessageAfterRedirect('O nome do veÃ­culo Ã© obrigatÃ³rio.', false, ERROR);
            return false;
        }

        if ($input['plate'] === '') {
            Session::addMessageAfterRedirect('A placa Ã© obrigatÃ³ria.', false, ERROR);
            return false;
        }

        if ($input['year'] < self::MIN_YEAR || $input['year'] > self::MAX_YEAR) {
            Session::addMessageAfterRedirect('O ano informado Ã© invÃ¡lido.', false, ERROR);
            return false;
        }

        if ($input['seats'] < self::MIN_SEATS || $input['seats'] > self::MAX_SEATS) {
            Session::addMessageAfterRedirect('A capacidade de passageiros Ã© invÃ¡lida.', false, ERROR);
            return false;
        }

        if (!array_key_exists($input['required_cnh_category'], self::getRequiredCNHOptions())) {
            Session::addMessageAfterRedirect('Selecione a categoria de CNH exigida para a viatura.', false, ERROR);
            return false;
        }

        if (self::isPlateAlreadyUsed($input['plate'], $input['id'])) {
            Session::addMessageAfterRedirect('A placa informada jÃ¡ estÃ¡ em uso.', false, ERROR);
            return false;
        }

        if ($input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        return $input;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => 'VeÃ­culos'
        ];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => 'Nome',
            'datatype'      => 'itemlink',
            'massiveaction' => false
        ];

        $tab[] = [
            'id'       => '2',
            'table'    => $this->getTable(),
            'field'    => 'plate',
            'name'     => 'Placa',
            'datatype' => 'string'
        ];

        $tab[] = [
            'id'       => '3',
            'table'    => $this->getTable(),
            'field'    => 'brand',
            'name'     => 'Marca',
            'datatype' => 'string'
        ];

        $tab[] = [
            'id'       => '4',
            'table'    => $this->getTable(),
            'field'    => 'model',
            'name'     => 'Modelo',
            'datatype' => 'string'
        ];

        $tab[] = [
            'id'       => '5',
            'table'    => $this->getTable(),
            'field'    => 'year',
            'name'     => 'Ano',
            'datatype' => 'number'
        ];

        $tab[] = [
            'id'       => '6',
            'table'    => $this->getTable(),
            'field'    => 'seats',
            'name'     => 'Passageiros',
            'datatype' => 'number'
        ];

        $tab[] = [
            'id'       => '7',
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => 'Ativo',
            'datatype' => 'bool'
        ];

        $tab[] = [
            'id'         => '8',
            'table'      => $this->getTable(),
            'field'      => 'required_cnh_category',
            'name'       => 'CNH exigida',
            'datatype'   => 'specific',
            'searchtype' => ['equals', 'notequals']
        ];

        $tab[] = [
            'id'       => '19',
            'table'    => $this->getTable(),
            'field'    => 'date_mod',
            'name'     => 'Ãšltima modificaÃ§Ã£o',
            'datatype' => 'datetime'
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'required_cnh_category') {
            return self::getRequiredCNHOptions()[$values[$field]] ?? $values[$field];
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private static function normalizeVehicleInput(array $input): array
    {
        $plate = PluginVehicleschedulerInput::string($input, 'plate', 50, '');
        $plate = self::normalizePlate($plate);

        return [
            'id'          => PluginVehicleschedulerInput::int($input, 'id', 0, 0),
            'entities_id' => PluginVehicleschedulerInput::int(
                $input,
                'entities_id',
                (int) ($_SESSION['glpiactive_entity'] ?? 0),
                0
            ),
            'is_recursive' => PluginVehicleschedulerInput::int($input, 'is_recursive', 0, 0, 1),
            'name'        => PluginVehicleschedulerInput::string($input, 'name', 255, ''),
            'plate'       => $plate,
            'brand'       => PluginVehicleschedulerInput::string($input, 'brand', 100, ''),
            'model'       => PluginVehicleschedulerInput::string($input, 'model', 100, ''),
            'year'        => PluginVehicleschedulerInput::int($input, 'year', (int) date('Y')),
            'seats'       => PluginVehicleschedulerInput::int($input, 'seats', 5),
            'is_active'   => PluginVehicleschedulerInput::bool($input, 'is_active', true),
            'required_cnh_category' => PluginVehicleschedulerInput::enum(
                $input,
                'required_cnh_category',
                array_keys(self::getRequiredCNHOptions()),
                self::REQUIRED_CNH_B
            ),
            'comment'     => PluginVehicleschedulerInput::text($input, 'comment', 65535, ''),
        ];
    }

    private static function normalizePlate(string $plate): string
    {
        $plate = mb_strtoupper(trim($plate));
        return preg_replace('/[^A-Z0-9]/', '', $plate) ?? '';
    }

    private static function isPlateAlreadyUsed(string $plate, int $current_id = 0): bool
    {
        global $DB;

        if ($plate === '') {
            return false;
        }

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['plate' => $plate]
        ]);

        foreach ($iterator as $row) {
            if ((int) $row['id'] !== $current_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns normalized rows for the custom vehicle management grid.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getManagementGridRows(): array
    {
        global $DB;

        $rows = [];
        $table = (new self())->getTable();
        $requiredLabels = self::getRequiredCNHOptions();

        $iterator = $DB->request([
            'FROM'  => $table,
            'ORDER' => [
                'is_active DESC',
                'name ASC',
                'id ASC',
            ],
        ]);

        foreach ($iterator as $row) {
            $requiredCategory = (string) ($row['required_cnh_category'] ?? self::REQUIRED_CNH_B);
            $comment = trim((string) ($row['comment'] ?? ''));
            $commentExcerpt = $comment;

            if ($commentExcerpt !== '' && function_exists('mb_substr') && function_exists('mb_strlen')) {
                if (mb_strlen($commentExcerpt) > 72) {
                    $commentExcerpt = (string) mb_substr($commentExcerpt, 0, 72) . '...';
                }
            } elseif ($commentExcerpt !== '' && strlen($commentExcerpt) > 72) {
                $commentExcerpt = substr($commentExcerpt, 0, 72) . '...';
            }

            $rows[] = [
                'id'                    => (int) ($row['id'] ?? 0),
                'name'                  => (string) ($row['name'] ?? ''),
                'plate'                 => (string) ($row['plate'] ?? ''),
                'brand'                 => (string) ($row['brand'] ?? ''),
                'model'                 => (string) ($row['model'] ?? ''),
                'year'                  => (int) ($row['year'] ?? 0),
                'seats'                 => (int) ($row['seats'] ?? 0),
                'required_cnh_category' => $requiredCategory,
                'required_cnh_label'    => (string) ($requiredLabels[$requiredCategory] ?? $requiredCategory),
                'comment'               => (string) ($row['comment'] ?? ''),
                'comment_excerpt'       => $commentExcerpt,
                'is_active'             => (int) ($row['is_active'] ?? 0),
                'date_mod'              => (string) ($row['date_mod'] ?? ''),
            ];
        }

        return $rows;
    }
}
