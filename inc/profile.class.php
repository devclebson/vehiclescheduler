<?php

/**
 * Gestão de permissões de perfil - Vehicle Scheduler
 * V2: fonte única em glpi_profilerights
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

if (!function_exists('plugin_vehiclescheduler_get_front_url')) {
    include_once(__DIR__ . '/common.inc.php');
}

class PluginVehicleschedulerProfile extends CommonDBTM
{
    public static $rightname = 'profile';

    public const RIGHT_PORTAL     = 'plugin_vehiclescheduler_portal';
    public const RIGHT_MANAGEMENT = 'plugin_vehiclescheduler_management';
    public const RIGHT_APPROVE    = 'plugin_vehiclescheduler_approve';

    public const LEGACY_TABLE = 'glpi_plugin_vehiclescheduler_profiles';

    /**
     * Direitos declarados pelo plugin.
     */
    public static function getAllRights(): array
    {
        return [
            [
                'itemtype' => self::class,
                'label'    => 'Acesso ao Portal de Reservas',
                'field'    => self::RIGHT_PORTAL,
                'rights'   => [
                    READ => __('Read')
                ],
                'default'  => READ
            ],
            [
                'itemtype' => self::class,
                'label'    => 'Gestão de Frota',
                'field'    => self::RIGHT_MANAGEMENT,
                'rights'   => [
                    READ   => __('Read'),
                    UPDATE => __('Update'),
                    CREATE => __('Create'),
                    DELETE => __('Delete'),
                    PURGE  => __('Delete permanently')
                ],
                'default'  => READ
            ],
            [
                'itemtype' => self::class,
                'label'    => 'Aprovar/Rejeitar Reservas',
                'field'    => self::RIGHT_APPROVE,
                'rights'   => [
                    READ => __('Read')
                ],
                'default'  => 0
            ],
        ];
    }

    /**
     * Install/update do bloco de permissões.
     * Migra tabela legada -> glpi_profilerights e remove a tabela antiga.
     */
    public static function install(Migration $migration): bool
    {
        global $DB;

        foreach (
            $DB->request([
                'SELECT' => ['id'],
                'FROM'   => 'glpi_profiles'
            ]) as $profile
        ) {
            $profiles_id = (int)$profile['id'];

            if ($DB->tableExists(self::LEGACY_TABLE)) {
                self::migrateOneProfile($profiles_id);
            }

            self::addDefaultProfileInfos(
                $profiles_id,
                self::getDefaultRightsMap()
            );
        }

        if ($DB->tableExists(self::LEGACY_TABLE)) {
            $migration->dropTable(self::LEGACY_TABLE);
        }

        self::changeProfile();
        return true;
    }

    /**
     * Remove os direitos do plugin do glpi_profilerights.
     */
    public static function uninstall(): bool
    {
        global $DB;

        foreach (self::getAllRights() as $right) {
            $DB->delete(
                ProfileRight::getTable(),
                ['name' => $right['field']]
            );

            if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
                unset($_SESSION['glpiactiveprofile'][$right['field']]);
            }
        }

        return true;
    }

    /**
     * Garante que os direitos default existam para um perfil.
     */
    public static function addDefaultProfileInfos(int $profiles_id, array $rights): void
    {
        $profileRight = new ProfileRight();

        foreach ($rights as $right_name => $right_value) {
            if (!countElementsInTable(ProfileRight::getTable(), [
                'profiles_id' => $profiles_id,
                'name'        => $right_name
            ])) {
                $profileRight->add([
                    'profiles_id' => $profiles_id,
                    'name'        => $right_name,
                    'rights'      => $right_value,
                ]);
            }
        }
    }

    /**
     * Migra um perfil da tabela legada para glpi_profilerights.
     */
    public static function migrateOneProfile(int $profiles_id): void
    {
        $legacy = self::getLegacyProfileRow($profiles_id);
        if ($legacy === null) {
            return;
        }

        ProfileRight::updateProfileRights(
            $profiles_id,
            self::legacyRowToRights($legacy)
        );
    }

    /**
     * Recarrega os direitos do plugin no perfil ativo da sessão.
     * Deve ser chamado no hook change_profile.
     */
    public static function changeProfile(): void
    {
        global $DB;

        $active_profile_id = (int)($_SESSION['glpiactiveprofile']['id'] ?? 0);
        if ($active_profile_id <= 0) {
            return;
        }

        foreach (self::getAllRights() as $right) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
        }

        $iterator = $DB->request([
            'SELECT' => ['name', 'rights'],
            'FROM'   => ProfileRight::getTable(),
            'WHERE'  => [
                'profiles_id' => $active_profile_id,
                'name'        => array_column(self::getAllRights(), 'field')
            ]
        ]);

        foreach ($iterator as $row) {
            $_SESSION['glpiactiveprofile'][$row['name']] = (int)$row['rights'];
        }
    }

    /**
     * Helpers de autorização para o restante do plugin.
     * Mantém uma API simples enquanto o resto das classes é migrado.
     */
    public static function canAccessRequester(): bool
    {
        return Session::haveRight(self::RIGHT_PORTAL, READ);
    }

    public static function canViewManagement(): bool
    {
        return Session::haveRight(self::RIGHT_MANAGEMENT, READ);
    }

    public static function canEditManagement(): bool
    {
        return Session::haveRight(self::RIGHT_MANAGEMENT, UPDATE)
            || Session::haveRight(self::RIGHT_MANAGEMENT, CREATE)
            || Session::haveRight(self::RIGHT_MANAGEMENT, DELETE)
            || Session::haveRight(self::RIGHT_MANAGEMENT, PURGE);
    }

    public static function canApproveReservations(): bool
    {
        return Session::haveRight(self::RIGHT_APPROVE, READ);
    }

    /**
     * Sanitização/normalização centralizada do POST.
     * No próximo passo isso pode virar um helper dedicado do plugin.
     */
    public static function normalizeProfileInput(array $input): array
    {
        $profiles_id = max(0, (int)($input['profiles_id'] ?? 0));

        $requester_access = ((int)($input['requester_access'] ?? 0) === 1) ? 1 : 0;
        $can_approve      = ((int)($input['can_approve'] ?? 0) === 1) ? 1 : 0;

        $management_access = (string)($input['management_access'] ?? '');
        if (!in_array($management_access, ['', 'r', 'w'], true)) {
            $management_access = '';
        }

        return [
            'profiles_id'       => $profiles_id,
            'requester_access'  => $requester_access,
            'management_access' => $management_access,
            'can_approve'       => $can_approve,
        ];
    }

    /**
     * Salva o formulário no modelo nativo do GLPI.
     */
    public static function saveProfileRights(array $input): array
    {
        $data = self::normalizeProfileInput($input);

        if ($data['profiles_id'] <= 0) {
            throw new RuntimeException('Perfil inválido.');
        }

        $profile = new Profile();
        if (!$profile->getFromDB($data['profiles_id'])) {
            throw new RuntimeException('Perfil não encontrado.');
        }

        $rights = [
            self::RIGHT_PORTAL     => self::mapYesNoToRight($data['requester_access']),
            self::RIGHT_MANAGEMENT => self::mapManagementToRight($data['management_access']),
            self::RIGHT_APPROVE    => self::mapYesNoToRight($data['can_approve']),
        ];

        foreach ($rights as $right_name => $right_value) {
            self::saveOneProfileRight((int)$data['profiles_id'], $right_name, (int)$right_value);
        }

        if ((int)($_SESSION['glpiactiveprofile']['id'] ?? 0) === (int)$data['profiles_id']) {
            self::changeProfile();
        }

        return $data;
    }

    private static function saveOneProfileRight(int $profiles_id, string $right_name, int $right_value): void
    {
        global $DB;

        $row = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => ProfileRight::getTable(),
            'WHERE'  => [
                'profiles_id' => $profiles_id,
                'name'        => $right_name,
            ],
        ])->current();

        $profileRight = new ProfileRight();

        if (is_array($row) && isset($row['id'])) {
            $profileRight->update([
                'id'     => (int)$row['id'],
                'rights' => $right_value,
            ]);
            return;
        }

        $profileRight->add([
            'profiles_id' => $profiles_id,
            'name'        => $right_name,
            'rights'      => $right_value,
        ]);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Profile && $item->getField('id')) {
            return "<span class='d-inline-flex align-items-center gap-1'>"
                . "<i class='ti ti-car-suv'></i>"
                . "<span>Gestão de Frota</span>"
                . "</span>";
        }

        return '';
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        global $CFG_GLPI;

        if (!($item instanceof Profile)) {
            return false;
        }

        if (!$item->canView()) {
            return false;
        }

        $profiles_id = (int)$item->getID();

        self::addDefaultProfileInfos($profiles_id, self::getDefaultRightsMap());

        $form_values = self::getFormValuesFromProfile($profiles_id);
        $canedit = \Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);

        echo "<form name='fleet_profile_form' method='post' action='"
            . plugin_vehiclescheduler_get_front_url('profile.form.php')
            . "'>";

        echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
        echo "<tr class='headerRow'><th colspan='2'>Permissões — Gestão de Frota</th></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td width='50%'><strong>Acesso ao Portal de Reservas</strong><br>";
        echo "<small>Permite solicitar reservas e reportar incidentes</small></td>";
        echo "<td>";
        if ($canedit) {
            Dropdown::showYesNo('requester_access', $form_values['requester_access']);
        } else {
            echo $form_values['requester_access'] ? 'Sim' : 'Não';
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><strong>Acesso à Gestão de Frota</strong><br>";
        echo "<small>Dashboard, veículos, motoristas, manutenções, relatórios e cadastros</small></td>";
        echo "<td>";
        if ($canedit) {
            Dropdown::showFromArray(
                'management_access',
                [
                    ''  => '— Sem acesso —',
                    'r' => '🔍 Leitura (visualizar)',
                    'w' => '✏️ Escrita (CRUD)',
                ],
                ['value' => $form_values['management_access']]
            );
        } else {
            $labels = [
                ''  => 'Não',
                'r' => 'Leitura',
                'w' => 'Escrita',
            ];
            echo $labels[$form_values['management_access']] ?? 'Não';
        }
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td><strong>Aprovar/Rejeitar Reservas</strong><br>";
        echo "<small>Permite aprovar ou rejeitar reservas</small></td>";
        echo "<td>";
        if ($canedit) {
            Dropdown::showYesNo('can_approve', $form_values['can_approve']);
        } else {
            echo $form_values['can_approve'] ? 'Sim' : 'Não';
        }
        echo "</td></tr>";

        if ($canedit) {
            echo "<tr class='tab_bg_2'><td colspan='2' class='center'>";
            echo Html::hidden('profiles_id', ['value' => $profiles_id]);
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo "<button type='submit' name='update' value='1' class='btn btn-primary'>";
            echo "<i class='ti ti-device-floppy'></i> <span>Salvar</span>";
            echo "</button>";
            echo "</td></tr>";
        }

        echo "</table></div>";
        Html::closeForm();

        return true;
    }

    private static function getDefaultRightsMap(): array
    {
        $rights = [];

        foreach (self::getAllRights() as $right) {
            $rights[$right['field']] = $right['default'];
        }

        return $rights;
    }

    private static function getLegacyProfileRow(int $profiles_id): ?array
    {
        global $DB;

        if (!$DB->tableExists(self::LEGACY_TABLE)) {
            return null;
        }

        $row = $DB->request([
            'FROM'  => self::LEGACY_TABLE,
            'WHERE' => ['profiles_id' => $profiles_id]
        ])->current();

        return is_array($row) ? $row : null;
    }

    private static function legacyRowToRights(array $legacy): array
    {
        return [
            self::RIGHT_PORTAL     => self::mapYesNoToRight((int)($legacy['requester_access'] ?? 0)),
            self::RIGHT_MANAGEMENT => self::mapManagementToRight((string)($legacy['management_access'] ?? '')),
            self::RIGHT_APPROVE    => self::mapYesNoToRight((int)($legacy['can_approve'] ?? 0)),
        ];
    }

    private static function mapYesNoToRight(int $value): int
    {
        return $value === 1 ? READ : 0;
    }

    private static function mapManagementToRight(string $value): int
    {
        switch ($value) {
            case 'r':
                return READ;

            case 'w':
                return READ | UPDATE | CREATE | DELETE | PURGE;

            default:
                return 0;
        }
    }

    private static function getFormValuesFromProfile(int $profiles_id): array
    {
        $portal_right     = self::getProfileRightValue($profiles_id, self::RIGHT_PORTAL, READ);
        $management_right = self::getProfileRightValue($profiles_id, self::RIGHT_MANAGEMENT, 0);
        $approve_right    = self::getProfileRightValue($profiles_id, self::RIGHT_APPROVE, 0);

        return [
            'requester_access'  => (($portal_right & READ) === READ) ? 1 : 0,
            'management_access' => self::rightToManagementMode($management_right),
            'can_approve'       => (($approve_right & READ) === READ) ? 1 : 0,
        ];
    }

    private static function getProfileRightValue(
        int $profiles_id,
        string $right_name,
        int $default = 0
    ): int {
        global $DB;

        $row = $DB->request([
            'SELECT' => ['rights'],
            'FROM'   => ProfileRight::getTable(),
            'WHERE'  => [
                'profiles_id' => $profiles_id,
                'name'        => $right_name
            ]
        ])->current();

        if (is_array($row) && array_key_exists('rights', $row)) {
            return (int)$row['rights'];
        }

        return $default;
    }

    private static function rightToManagementMode(int $rights): string
    {
        if (($rights & (UPDATE | CREATE | DELETE | PURGE)) !== 0) {
            return 'w';
        }

        if (($rights & READ) === READ) {
            return 'r';
        }

        return '';
    }
}
