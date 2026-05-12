<?php

/**
 * Driver entity for Vehicle Scheduler.
 *
 * Stores only the minimum data required for fleet operation and driver allocation.
 * Sensitive personal data unrelated to fleet control must not be stored here.
 */
if (!defined('GLPI_ROOT')) {
    die('Acesso direto não permitido');
}

class PluginVehicleschedulerDriver extends CommonDBTM
{
    /** @var bool */
    public $dohistory = true;

    /** @var string */
    public static $rightname = 'plugin_vehiclescheduler_management';

    public const CNH_CAT_A = 'A';
    public const CNH_CAT_B = 'B';
    public const CNH_CAT_AB = 'AB';
    public const CNH_CAT_D = 'D';

    public const CNH_ALERT_CRITICAL = 30;
    public const CNH_ALERT_WARNING = 90;

    /**
     * Returns the translated item type name.
     *
     * @param int $nb Number of items.
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return ($nb === 1) ? 'Motorista' : 'Motoristas';
    }

    /**
     * Returns the menu label for the driver module.
     *
     * @return string
     */
    public static function getMenuName()
    {
        return 'Motoristas';
    }

    /**
     * Returns the icon used by the driver module.
     *
     * @return string
     */
    public static function getIcon()
    {
        return 'ti ti-steering-wheel';
    }

    /**
     * Builds the GLPI menu definition for the driver module.
     *
     * @return array<string, mixed>|false
     */
    public static function getMenuContent()
    {
        if (!Session::haveRight(self::$rightname, READ)) {
            return false;
        }

        $menu = [
            'title'   => self::getMenuName(),
            'page'    => '/plugins/vehiclescheduler/front/driver.php',
            'icon'    => self::getIcon(),
            'links'   => [
                'search' => '/plugins/vehiclescheduler/front/driver.php',
            ],
            'options' => [
                'driver' => [
                    'title'          => self::getMenuName(),
                    'page'           => '/plugins/vehiclescheduler/front/driver.php',
                    'icon'           => self::getIcon(),
                    'links'          => [
                        'search' => '/plugins/vehiclescheduler/front/driver.php',
                    ],
                    'lists_itemtype' => self::class,
                ],
            ],
        ];

        if (Session::haveRight(self::$rightname, CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/driver.form.php';
            $menu['options']['driver']['links']['add'] = '/plugins/vehiclescheduler/front/driver.form.php';
        }

        return $menu;
    }

    /**
     * Returns all known CNH categories handled by the plugin.
     *
     * @return array<string, string>
     */
    public static function getCNHCategories(): array
    {
        return [
            self::CNH_CAT_A  => 'A - Motos',
            self::CNH_CAT_B  => 'B - Carros',
            self::CNH_CAT_D  => 'D - Caminhões e vans',
            self::CNH_CAT_AB => 'AB - Motos e carros (legado)',
        ];
    }

    /**
     * Returns the selectable CNH categories shown in the form.
     *
     * @return array<string, string>
     */
    public static function getDriverSelectableCNHCategories(): array
    {
        return [
            self::CNH_CAT_A => 'Permite dirigir moto.',
            self::CNH_CAT_B => 'Permite dirigir carro.',
            self::CNH_CAT_D => 'Permite dirigir carro, caminhão e van.',
        ];
    }

    /**
     * Normalizes CNH categories from storage or request payload.
     *
     * Legacy AB values are expanded into A and B.
     *
     * @param mixed $rawValue Raw category value.
     *
     * @return array<int, string>
     */
    public static function normalizeCNHCategoryList($rawValue): array
    {
        if (is_array($rawValue)) {
            $values = $rawValue;
        } else {
            $value = trim((string) $rawValue);

            if ($value === '') {
                return [];
            }

            if ($value === self::CNH_CAT_AB) {
                $values = [self::CNH_CAT_A, self::CNH_CAT_B];
            } else {
                $values = preg_split('/\s*,\s*/', $value) ?: [];
            }
        }

        $normalized = [];
        $allowed = array_keys(self::getDriverSelectableCNHCategories());

        foreach ($values as $value) {
            $item = trim((string) $value);

            if ($item === '') {
                continue;
            }

            if ($item === self::CNH_CAT_AB) {
                $normalized[self::CNH_CAT_A] = self::CNH_CAT_A;
                $normalized[self::CNH_CAT_B] = self::CNH_CAT_B;
                continue;
            }

            if (in_array($item, $allowed, true)) {
                $normalized[$item] = $item;
            }
        }

        $ordered = [];

        foreach (array_keys(self::getDriverSelectableCNHCategories()) as $category) {
            if (isset($normalized[$category])) {
                $ordered[] = $category;
            }
        }

        return $ordered;
    }

    /**
     * Encodes a normalized CNH category list to its storage representation.
     *
     * @param array<int, string> $categories Category list.
     *
     * @return string
     */
    public static function encodeCNHCategoryList(array $categories): string
    {
        return implode(',', self::normalizeCNHCategoryList($categories));
    }

    /**
     * Returns normalized CNH categories from the stored value.
     *
     * @param mixed $storedValue Stored CNH value.
     *
     * @return array<int, string>
     */
    public static function getDriverCNHCategoryList($storedValue): array
    {
        return self::normalizeCNHCategoryList($storedValue);
    }

    /**
     * Expands stored categories to operationally qualified categories.
     *
     * Example: category D also qualifies the driver for category B vehicles.
     *
     * @param mixed $storedValue Stored CNH value.
     *
     * @return array<int, string>
     */
    public static function getDriverQualifiedCNHCategoryList($storedValue): array
    {
        $directCategories = self::getDriverCNHCategoryList($storedValue);
        $qualified = [];

        foreach ($directCategories as $category) {
            switch ($category) {
                case self::CNH_CAT_D:
                    $qualified[self::CNH_CAT_B] = self::CNH_CAT_B;
                    $qualified[self::CNH_CAT_D] = self::CNH_CAT_D;
                    break;

                case self::CNH_CAT_B:
                    $qualified[self::CNH_CAT_B] = self::CNH_CAT_B;
                    break;

                case self::CNH_CAT_A:
                    $qualified[self::CNH_CAT_A] = self::CNH_CAT_A;
                    break;
            }
        }

        $ordered = [];

        foreach ([self::CNH_CAT_A, self::CNH_CAT_B, self::CNH_CAT_D] as $category) {
            if (isset($qualified[$category])) {
                $ordered[] = $category;
            }
        }

        return $ordered;
    }

    /**
     * Checks whether a driver satisfies a required CNH category.
     *
     * @param mixed  $storedCategories Stored category value.
     * @param string $requiredCategory Required category code.
     *
     * @return bool
     */
    public static function hasRequiredCNHCategory($storedCategories, string $requiredCategory): bool
    {
        $requiredCategory = trim($requiredCategory);

        if ($requiredCategory === '') {
            return true;
        }

        return in_array(
            $requiredCategory,
            self::getDriverQualifiedCNHCategoryList($storedCategories),
            true
        );
    }

    /**
     * Computes the expiry status for a CNH date.
     *
     * @param mixed $cnh_expiry Expiry date value.
     *
     * @return array<string, int|string|null>
     */
    public static function getCNHExpiryStatus($cnh_expiry)
    {
        if (empty($cnh_expiry) || $cnh_expiry === '0000-00-00') {
            return ['status' => 'unknown', 'days' => null];
        }

        $today = new DateTime('today');
        $expiry = new DateTime((string) $cnh_expiry);
        $diff = (int) $today->diff($expiry)->format('%r%a');

        if ($diff < 0) {
            return ['status' => 'expired', 'days' => abs($diff)];
        }

        if ($diff <= self::CNH_ALERT_CRITICAL) {
            return ['status' => 'critical', 'days' => $diff];
        }

        if ($diff <= self::CNH_ALERT_WARNING) {
            return ['status' => 'warning', 'days' => $diff];
        }

        return ['status' => 'ok', 'days' => $diff];
    }

    /**
     * Builds badge metadata for CNH expiry visualization.
     *
     * @param array<string, int|string|null> $status CNH expiry status payload.
     *
     * @return array<string, string>
     */
    public static function getCNHExpiryBadgeData(array $status): array
    {
        $map = [
            'ok'       => ['class' => 'vs-driver-expiry-badge--ok', 'label' => 'Válida'],
            'warning'  => ['class' => 'vs-driver-expiry-badge--warning', 'label' => 'Vence em breve'],
            'critical' => ['class' => 'vs-driver-expiry-badge--critical', 'label' => 'Crítica'],
            'expired'  => ['class' => 'vs-driver-expiry-badge--expired', 'label' => 'VENCIDA'],
            'unknown'  => ['class' => 'vs-driver-expiry-badge--unknown', 'label' => 'Sem data'],
        ];

        $badge = $map[$status['status'] ?? 'unknown'] ?? $map['unknown'];

        if (in_array($status['status'] ?? '', ['critical', 'warning'], true) && $status['days'] !== null) {
            $badge['label'] = "Vence em {$status['days']} dias";
        } elseif (($status['status'] ?? '') === 'ok' && $status['days'] !== null) {
            $badge['label'] = "Válida - {$status['days']} dias restantes";
        } elseif (($status['status'] ?? '') === 'expired' && $status['days'] !== null) {
            $badge['label'] = "VENCIDA há {$status['days']} dias";
        }

        return $badge;
    }

    /**
     * Shows the default GLPI dropdown for drivers.
     *
     * @param array<string, mixed> $options Dropdown options.
     *
     * @return mixed
     */
    public static function dropdown($options = [])
    {
        $params = [
            'name'      => 'plugin_vehiclescheduler_drivers_id',
            'value'     => 0,
            'condition' => ['is_active' => 1],
            'display'   => true,
        ];

        foreach ($options as $key => $value) {
            $params[$key] = $value;
        }

        return Dropdown::show(self::class, $params);
    }

    /**
     * Returns a compact map of approved active drivers and their categories.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getApprovedDriverCategoryMap(): array
    {
        global $DB;

        $table = (new self())->getTable();
        $map = [];

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'cnh_category'],
            'FROM'   => $table,
            'WHERE'  => [
                'is_active'   => 1,
                'is_approved' => 1,
            ],
            'ORDER'  => ['name ASC', 'id ASC'],
        ]);

        foreach ($iterator as $row) {
            $categories = self::getDriverCNHCategoryList($row['cnh_category'] ?? '');
            $qualifiedCategories = self::getDriverQualifiedCNHCategoryList($row['cnh_category'] ?? '');

            $map[(string) ((int) $row['id'])] = [
                'id'                       => (int) $row['id'],
                'name'                     => (string) ($row['name'] ?? ''),
                'categories'               => $categories,
                'categoriesLabel'          => implode(', ', $categories),
                'qualifiedCategories'      => $qualifiedCategories,
                'qualifiedCategoriesLabel' => implode(', ', $qualifiedCategories),
            ];
        }

        return $map;
    }

    /**
     * Returns normalized rows for the custom driver management grid.
     *
     * This method centralizes data loading and normalization for the driver
     * operational grid while keeping rendering concerns in the front layer.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getManagementGridRows(): array
    {
        global $DB;

        $rows = [];
        $table = (new self())->getTable();
        $categoryLabels = self::getCNHCategories();

        $iterator = $DB->request([
            'FROM'  => $table,
            'ORDER' => [
                'is_active DESC',
                'is_approved DESC',
                'name ASC',
                'id ASC',
            ],
        ]);

        foreach ($iterator as $row) {
            $usersId = (int) ($row['users_id'] ?? 0);
            $groupsId = (int) ($row['groups_id'] ?? 0);

            $categories = self::getDriverCNHCategoryList($row['cnh_category'] ?? '');
            $categoryText = [];

            foreach ($categories as $category) {
                $categoryText[] = $categoryLabels[$category] ?? $category;
            }

            $expiryStatus = self::getCNHExpiryStatus((string) ($row['cnh_expiry'] ?? ''));
            $expiryBadge = self::getCNHExpiryBadgeData($expiryStatus);

            $rows[] = [
                'id'                => (int) ($row['id'] ?? 0),
                'name'              => (string) ($row['name'] ?? ''),
                'users_id'          => $usersId,
                'user_name'         => $usersId > 0 ? (string) getUserName($usersId) : '',
                'registration'      => (string) ($row['registration'] ?? ''),
                'groups_id'         => $groupsId,
                'group_name'        => $groupsId > 0 ? (string) Dropdown::getDropdownName('glpi_groups', $groupsId) : '',
                'contact_phone'     => (string) ($row['contact_phone'] ?? ''),
                'comment'           => (string) ($row['comment'] ?? ''),
                'cnh_expiry'        => (string) ($row['cnh_expiry'] ?? ''),
                'cnh_expiry_status' => (string) ($expiryStatus['status'] ?? 'unknown'),
                'cnh_expiry_days'   => $expiryStatus['days'] ?? null,
                'cnh_expiry_badge'  => $expiryBadge,
                'categories'        => $categories,
                'categories_text'   => implode(', ', $categoryText),
                'is_active'         => (int) ($row['is_active'] ?? 0),
                'is_approved'       => (int) ($row['is_approved'] ?? 0),
                'date_mod'          => (string) ($row['date_mod'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * Defines the tabs available in the driver form.
     *
     * @param array<string, mixed> $options GLPI tab options.
     *
     * @return array<int, mixed>
     */
    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginVehicleschedulerDriverfine', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }

    /**
     * Renders the driver form.
     *
     * @param int                  $ID      Current object ID.
     * @param array<string, mixed> $options Form options.
     *
     * @return bool
     */
    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        require_once GLPI_ROOT . '/plugins/vehiclescheduler/front/driver.render.php';

        echo "<tr><td colspan='4'>";
        vs_render_driver_form($this, (int) $ID);
        echo '</td></tr>';

        $this->showFormButtons($options);

        return true;
    }

    /**
     * Validates and normalizes input before creating a driver.
     *
     * @param array<string, mixed> $input Raw input payload.
     *
     * @return array<string, mixed>|false
     */
    public function prepareInputForAdd($input)
    {
        $input = self::normalizeDriverInput($input);

        if ($input['users_id'] <= 0) {
            Session::addMessageAfterRedirect('Selecione um usuário do GLPI para o motorista.', false, ERROR);
            return false;
        }

        if ($input['cnh_category'] === '') {
            Session::addMessageAfterRedirect('Selecione ao menos uma categoria de CNH para o motorista.', false, ERROR);
            return false;
        }

        if ($input['cnh_expiry'] === null) {
            Session::addMessageAfterRedirect('O vencimento da CNH é obrigatório e deve ser uma data válida.', false, ERROR);
            return false;
        }

        if (self::isUserAlreadyLinked($input['users_id'])) {
            Session::addMessageAfterRedirect('Este usuário do GLPI já está vinculado a outro motorista.', false, ERROR);
            return false;
        }

        if ($input['registration'] !== '' && self::isRegistrationAlreadyUsed($input['registration'])) {
            Session::addMessageAfterRedirect('A matrícula informada já está em uso.', false, ERROR);
            return false;
        }

        $input['name'] = getUserName($input['users_id']);

        if ($input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        if (PluginVehicleschedulerProfile::canEditManagement()) {
            $input['is_approved'] = 1;
        } else {
            $input['is_approved'] = 0;
            $input['is_active'] = 0;

            Session::addMessageAfterRedirect(
                'Motorista cadastrado! Aguardando aprovação da gestão.',
                false,
                INFO
            );
        }

        return $input;
    }

    /**
     * Validates and normalizes input before updating a driver.
     *
     * @param array<string, mixed> $input Raw input payload.
     *
     * @return array<string, mixed>|false
     */
    public function prepareInputForUpdate($input)
    {
        $input = self::normalizeDriverInput($input);

        if ($input['id'] <= 0) {
            Session::addMessageAfterRedirect('ID do motorista inválido.', false, ERROR);
            return false;
        }

        if ($input['users_id'] <= 0) {
            Session::addMessageAfterRedirect('Selecione um usuário do GLPI para o motorista.', false, ERROR);
            return false;
        }

        if ($input['cnh_category'] === '') {
            Session::addMessageAfterRedirect('Selecione ao menos uma categoria de CNH para o motorista.', false, ERROR);
            return false;
        }

        if ($input['cnh_expiry'] === null) {
            Session::addMessageAfterRedirect('O vencimento da CNH é obrigatório e deve ser uma data válida.', false, ERROR);
            return false;
        }

        if (self::isUserAlreadyLinked($input['users_id'], $input['id'])) {
            Session::addMessageAfterRedirect('Este usuário do GLPI já está vinculado a outro motorista.', false, ERROR);
            return false;
        }

        if ($input['registration'] !== '' && self::isRegistrationAlreadyUsed($input['registration'], $input['id'])) {
            Session::addMessageAfterRedirect('A matrícula informada já está em uso.', false, ERROR);
            return false;
        }

        $input['name'] = getUserName($input['users_id']);

        if ($input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        return $input;
    }

    /**
     * Returns GLPI search options for the driver entity.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => 'Motoristas',
        ];

        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => 'Nome',
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => '2',
            'table'    => 'glpi_users',
            'field'    => 'name',
            'name'     => 'Usuário GLPI',
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id'       => '3',
            'table'    => $this->getTable(),
            'field'    => 'registration',
            'name'     => 'Matrícula Interna',
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'         => '4',
            'table'      => $this->getTable(),
            'field'      => 'cnh_category',
            'name'       => 'Categoria CNH',
            'datatype'   => 'specific',
            'searchtype' => ['equals', 'notequals'],
        ];

        $tab[] = [
            'id'       => '5',
            'table'    => $this->getTable(),
            'field'    => 'cnh_expiry',
            'name'     => 'Vencimento da CNH',
            'datatype' => 'date',
        ];

        $tab[] = [
            'id'       => '6',
            'table'    => 'glpi_groups',
            'field'    => 'name',
            'name'     => 'Departamento/Setor',
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id'       => '7',
            'table'    => $this->getTable(),
            'field'    => 'contact_phone',
            'name'     => 'Telefone',
            'datatype' => 'string',
        ];

        $tab[] = [
            'id'       => '8',
            'table'    => $this->getTable(),
            'field'    => 'is_active',
            'name'     => 'Ativo',
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'       => '9',
            'table'    => $this->getTable(),
            'field'    => 'is_approved',
            'name'     => 'Aprovado',
            'datatype' => 'bool',
        ];

        $tab[] = [
            'id'       => '19',
            'table'    => $this->getTable(),
            'field'    => 'date_mod',
            'name'     => 'Última modificação',
            'datatype' => 'datetime',
        ];

        return $tab;
    }

    /**
     * Returns a specific display value for custom searchable fields.
     *
     * @param string               $field   Field name.
     * @param mixed                $values  GLPI values payload.
     * @param array<string, mixed> $options Display options.
     *
     * @return mixed
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'cnh_category') {
            $labels = [];

            foreach (self::getDriverCNHCategoryList($values[$field] ?? '') as $category) {
                $labels[] = self::getCNHCategories()[$category] ?? $category;
            }

            return $labels !== [] ? implode(', ', $labels) : (string) ($values[$field] ?? '');
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Normalizes the driver input payload for persistence.
     *
     * @param array<string, mixed> $input Raw input payload.
     *
     * @return array<string, mixed>
     */
    private static function normalizeDriverInput(array $input): array
    {
        $categoryList = PluginVehicleschedulerInput::enumList(
            $input,
            'cnh_category',
            array_keys(self::getDriverSelectableCNHCategories()),
            self::normalizeCNHCategoryList($input['cnh_category'] ?? '')
        );

        return [
            'id'            => PluginVehicleschedulerInput::int($input, 'id', 0, 0),
            'users_id'      => PluginVehicleschedulerInput::int($input, 'users_id', 0, 0),
            'groups_id'     => PluginVehicleschedulerInput::int($input, 'groups_id', 0, 0),
            'entities_id'   => PluginVehicleschedulerInput::int(
                $input,
                'entities_id',
                (int) ($_SESSION['glpiactive_entity'] ?? 0),
                0
            ),
            'is_active'     => PluginVehicleschedulerInput::bool($input, 'is_active', true),
            'registration'  => PluginVehicleschedulerInput::string($input, 'registration', 50, ''),
            'contact_phone' => PluginVehicleschedulerInput::string($input, 'contact_phone', 50, ''),
            'comment'       => PluginVehicleschedulerInput::text($input, 'comment', 65535, ''),
            'cnh_category'  => self::encodeCNHCategoryList($categoryList),
            'cnh_expiry'    => PluginVehicleschedulerInput::date($input, 'cnh_expiry', null),
        ];
    }

    /**
     * Checks whether a GLPI user is already linked to another driver.
     *
     * @param int $users_id   GLPI user ID.
     * @param int $current_id Current driver ID to ignore.
     *
     * @return bool
     */
    private static function isUserAlreadyLinked(int $users_id, int $current_id = 0): bool
    {
        global $DB;

        if ($users_id <= 0) {
            return false;
        }

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => (new self())->getTable(),
            'WHERE'  => ['users_id' => $users_id],
        ]);

        foreach ($iterator as $row) {
            if ((int) $row['id'] !== $current_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether a registration code is already used by another driver.
     *
     * @param string $registration Registration code.
     * @param int    $current_id   Current driver ID to ignore.
     *
     * @return bool
     */
    private static function isRegistrationAlreadyUsed(string $registration, int $current_id = 0): bool
    {
        global $DB;

        if ($registration === '') {
            return false;
        }

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => (new self())->getTable(),
            'WHERE'  => ['registration' => $registration],
        ]);

        foreach ($iterator as $row) {
            if ((int) $row['id'] !== $current_id) {
                return true;
            }
        }

        return false;
    }
}