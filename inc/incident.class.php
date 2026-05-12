<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerIncident extends CommonDBTM
{
    public $dohistory = true;
    public static $rightname = 'plugin_vehiclescheduler';

    public const TYPE_ACCIDENT = 1;
    public const TYPE_BREAKDOWN = 2;
    public const TYPE_THEFT = 3;
    public const TYPE_DAMAGE = 4;
    public const TYPE_OBSERVATION = 5;
    public const TYPE_OTHER = 6;

    public const STATUS_OPEN = 1;
    public const STATUS_ANALYZING = 2;
    public const STATUS_RESOLVED = 3;
    public const STATUS_CLOSED = 4;

    public static function getTypeName($nb = 0)
    {
        return _n('Incident', 'Incidents', $nb, 'vehiclescheduler');
    }

    public static function getMenuName()
    {
        return __('Incidents', 'vehiclescheduler');
    }

    public static function getIcon()
    {
        return 'ti ti-alert-triangle';
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
            return false;
        }

        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page'] = '/plugins/vehiclescheduler/front/incident.php';
        $menu['icon'] = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/incident.php';

        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/incident.form.php';
        }

        $menu['options']['incident'] = [
            'title'          => self::getTypeName(2),
            'page'           => '/plugins/vehiclescheduler/front/incident.php',
            'icon'           => self::getIcon(),
            'links'          => [
                'search' => '/plugins/vehiclescheduler/front/incident.php',
                'add'    => '/plugins/vehiclescheduler/front/incident.form.php',
            ],
            'lists_itemtype' => 'PluginVehicleschedulerIncident',
        ];

        return $menu;
    }

    public static function getAllTypes(): array
    {
        return [
            self::TYPE_ACCIDENT    => 'Acidente',
            self::TYPE_BREAKDOWN   => 'Pane/Falha',
            self::TYPE_THEFT       => 'Roubo/Furto',
            self::TYPE_DAMAGE      => 'Dano/Avaria',
            self::TYPE_OBSERVATION => 'Observacao',
            self::TYPE_OTHER       => 'Outro',
        ];
    }

    public static function getAllStatus(): array
    {
        return [
            self::STATUS_OPEN      => 'Aberto',
            self::STATUS_ANALYZING => 'Analisando',
            self::STATUS_RESOLVED  => 'Resolvido',
            self::STATUS_CLOSED    => 'Fechado',
        ];
    }

    public function can($ID, int $right, ?array &$input = null): bool
    {
        switch ($right) {
            case READ:
                return PluginVehicleschedulerProfile::canViewManagement()
                    || $this->isCurrentUserRequester((int) $ID);

            case CREATE:
                return (int) Session::getLoginUserID() > 0;

            case UPDATE:
                return PluginVehicleschedulerProfile::canEditManagement()
                    || $this->canCurrentUserUpdateOwnOpenIncident((int) $ID);

            case DELETE:
            case PURGE:
                return PluginVehicleschedulerProfile::canEditManagement();
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return (int) Session::getLoginUserID() > 0;
    }

    public function defineTabs($options = []): array
    {
        $tabs = [];
        $this->addDefaultFormTab($tabs);
        $this->addStandardTab('Log', $tabs, $options);

        return $tabs;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);

        if ((int) $ID <= 0) {
            $scheduleId = (int) ($_REQUEST['schedule_id'] ?? 0);
            $this->fields = array_replace([
                'users_id'                            => (int) Session::getLoginUserID(),
                'groups_id'                           => 0,
                'plugin_vehiclescheduler_schedules_id' => $scheduleId,
                'plugin_vehiclescheduler_vehicles_id' => 0,
                'plugin_vehiclescheduler_drivers_id'  => 0,
                'incident_type'                       => self::TYPE_ACCIDENT,
                'incident_date'                       => date('Y-m-d H:i:s'),
                'location'                            => '',
                'contact_phone'                       => '',
                'status'                              => self::STATUS_OPEN,
                'needs_maintenance'                   => 0,
                'needs_insurance'                     => 0,
                'description'                         => '',
                'entities_id'                         => (int) ($_SESSION['glpiactive_entity'] ?? 0),
            ], $this->fields);

            if ($scheduleId > 0) {
                $this->applyScheduleContextToInput($this->fields, $scheduleId);
            }
        }

        $this->showFormHeader($options);

        require_once GLPI_ROOT . '/plugins/vehiclescheduler/front/incident.render.php';

        echo "<tr><td colspan='4'>";
        plugin_vehiclescheduler_render_incident_form($this, (int) $ID);
        echo '</td></tr>';

        $this->showFormButtons([
            'candel'      => PluginVehicleschedulerProfile::canEditManagement(),
            'canedit'     => $ID > 0
                ? $this->can((int) $ID, UPDATE)
                : $this->can(-1, CREATE),
            'addbuttons'  => [],
        ] + $options);

        return true;
    }

    public static function getManagementGridRows(): array
    {
        global $DB;

        $rows = [];
        $table = (new self())->getTable();
        $types = self::getAllTypes();
        $statuses = self::getAllStatus();

        $iterator = $DB->request([
            'FROM'  => $table,
            'ORDER' => [
                'incident_date DESC',
                'id DESC',
            ],
        ]);

        foreach ($iterator as $row) {
            $vehicleId = (int) ($row['plugin_vehiclescheduler_vehicles_id'] ?? 0);
            $driverId = (int) ($row['plugin_vehiclescheduler_drivers_id'] ?? 0);
            $scheduleId = (int) ($row['plugin_vehiclescheduler_schedules_id'] ?? 0);
            $requesterId = (int) ($row['users_id'] ?? 0);
            $status = (int) ($row['status'] ?? self::STATUS_OPEN);
            $scheduleSummary = $scheduleId > 0 ? self::getScheduleSummary($scheduleId) : '';

            $vehicleName = '';
            $vehiclePlate = '';
            if ($vehicleId > 0) {
                $vehicle = new PluginVehicleschedulerVehicle();
                if ($vehicle->getFromDB($vehicleId)) {
                    $vehicleName = (string) ($vehicle->fields['name'] ?? '');
                    $vehiclePlate = (string) ($vehicle->fields['plate'] ?? '');
                }
            }

            $driverName = '';
            if ($driverId > 0) {
                $driver = new PluginVehicleschedulerDriver();
                if ($driver->getFromDB($driverId)) {
                    $driverName = (string) ($driver->fields['name'] ?? '');
                }
            }

            $rows[] = [
                'id'             => (int) ($row['id'] ?? 0),
                'name'           => (string) ($row['name'] ?? ''),
                'type'           => (int) ($row['incident_type'] ?? self::TYPE_OTHER),
                'type_label'     => $types[(int) ($row['incident_type'] ?? self::TYPE_OTHER)] ?? 'Incidente',
                'schedule_id'    => $scheduleId,
                'schedule_label'  => $scheduleSummary,
                'status'         => $status,
                'status_label'   => $statuses[$status] ?? 'Aberto',
                'status_modifier' => self::getStatusModifier($status),
                'incident_date'  => (string) ($row['incident_date'] ?? ''),
                'location'       => (string) ($row['location'] ?? ''),
                'vehicle_id'     => $vehicleId,
                'vehicle_name'   => $vehicleName,
                'vehicle_plate'  => $vehiclePlate,
                'driver_id'      => $driverId,
                'driver_name'    => $driverName,
                'requester_id'   => $requesterId,
                'requester_name' => $requesterId > 0 ? (string) getUserName($requesterId) : '',
                'date_mod'       => (string) ($row['date_mod'] ?? ''),
            ];
        }

        return $rows;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->normalizeInput($input);

        if ((int) ($input['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
            $scheduleId = (int) ($input['plugin_vehiclescheduler_schedules_id'] ?? 0);
            if ($scheduleId > 0) {
                $this->applyScheduleContextToInput($input, $scheduleId);
            }
        }

        if ((int) ($input['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('Veiculo e obrigatorio.', false, ERROR);

            return false;
        }

        if ($input['description'] === '') {
            Session::addMessageAfterRedirect('Descricao e obrigatoria.', false, ERROR);

            return false;
        }

        if ($input['name'] === '') {
            $types = self::getAllTypes();
            $typeLabel = $types[(int) ($input['incident_type'] ?? self::TYPE_OTHER)] ?? 'Incidente';
            $input['name'] = $typeLabel . ' - ' . date('d/m/Y');
        }

        if (!isset($input['entities_id']) || (int) $input['entities_id'] <= 0) {
            $input['entities_id'] = (int) ($_SESSION['glpiactive_entity'] ?? 0);
        }

        if (!isset($input['users_id']) || (int) $input['users_id'] <= 0) {
            $input['users_id'] = (int) Session::getLoginUserID();
        }

        $scheduleId = (int) ($input['plugin_vehiclescheduler_schedules_id'] ?? 0);
        if ($scheduleId > 0) {
            $this->applyScheduleContextToInput($input, $scheduleId);
        }

        if (!PluginVehicleschedulerProfile::canEditManagement()) {
            $input['users_id'] = (int) Session::getLoginUserID();
            $input['status'] = self::STATUS_OPEN;
            $input['needs_maintenance'] = 0;
            $input['needs_insurance'] = 0;
        }

        if ($input['incident_date'] === '') {
            $input['incident_date'] = date('Y-m-d H:i:s');
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->normalizeInput($input);

        if ((int) ($input['id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('Incidente invalido.', false, ERROR);

            return false;
        }

        if ((int) ($input['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
            Session::addMessageAfterRedirect('Veiculo e obrigatorio.', false, ERROR);

            return false;
        }

        if ($input['description'] === '') {
            Session::addMessageAfterRedirect('Descricao e obrigatoria.', false, ERROR);

            return false;
        }

        $scheduleId = (int) ($input['plugin_vehiclescheduler_schedules_id'] ?? 0);
        if ($scheduleId > 0) {
            $this->applyScheduleContextToInput($input, $scheduleId);
        }

        if (!PluginVehicleschedulerProfile::canEditManagement()) {
            $current = new self();
            if ($current->getFromDB((int) $input['id'])) {
                $input['users_id'] = (int) ($current->fields['users_id'] ?? Session::getLoginUserID());
                $input['status'] = (int) ($current->fields['status'] ?? self::STATUS_OPEN);
                $input['needs_maintenance'] = (int) ($current->fields['needs_maintenance'] ?? 0);
                $input['needs_insurance'] = (int) ($current->fields['needs_insurance'] ?? 0);
            }
        }

        return $input;
    }

    public function post_addItem()
    {
        parent::post_addItem();
        $ticketId = $this->createTicketFromIncident();

        if ($ticketId) {
            $_SESSION['vehiclescheduler_created_ticket_id'] = $ticketId;
        }
    }

    public function createTicketFromIncident()
    {
        $vehicle = new PluginVehicleschedulerVehicle();
        $vehicleName = '';

        if ($vehicle->getFromDB((int) $this->fields['plugin_vehiclescheduler_vehicles_id'])) {
            $vehicleName = (string) ($vehicle->fields['name'] ?? '')
                . ' ('
                . (string) ($vehicle->fields['plate'] ?? '')
                . ')';
        }

        $types = self::getAllTypes();
        $typeLabel = $types[(int) ($this->fields['incident_type'] ?? self::TYPE_OTHER)] ?? 'Incidente';

        $title = 'Incidente com Veiculo: ' . $vehicleName . ' - ' . $typeLabel;

        $content = "Reporte de Incidente:\n\n"
            . 'Tipo: ' . $typeLabel . "\n"
            . 'Veiculo: ' . $vehicleName . "\n"
            . 'Data: ' . Html::convDateTime((string) ($this->fields['incident_date'] ?? '')) . "\n"
            . 'Local: ' . (string) ($this->fields['location'] ?? '') . "\n"
            . 'Relatado por: ' . getUserName((int) ($this->fields['users_id'] ?? 0)) . "\n"
            . 'Telefone: ' . (string) ($this->fields['contact_phone'] ?? '') . "\n\n"
            . "Descricao:\n"
            . (string) ($this->fields['description'] ?? '');

        $ticket = new Ticket();

        return $ticket->add([
            'name'                => $title,
            'content'             => $content,
            'entities_id'         => (int) ($this->fields['entities_id'] ?? 0),
            'type'                => Ticket::INCIDENT_TYPE,
            'urgency'             => 4,
            'impact'              => 3,
            'priority'            => CommonITILObject::computePriority(4, 3),
            '_users_id_requester' => (int) ($this->fields['users_id'] ?? 0),
        ]);
    }

    public function rawSearchOptions(): array
    {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => self::getTypeName(2)];
        $tab[] = [
            'id'            => '1',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => 'Titulo',
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
            'field'      => 'incident_type',
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
            'field'    => 'incident_date',
            'name'     => 'Data',
            'datatype' => 'datetime',
        ];
        $tab[] = [
            'id'       => '6',
            'table'    => $this->getTable(),
            'field'    => 'groups_id',
            'name'     => 'Grupo',
            'datatype' => 'dropdown',
            'itemtype' => 'Group',
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        if ($field === 'incident_type') {
            return self::getAllTypes()[(int) ($values[$field] ?? 0)] ?? '';
        }

        if ($field === 'status') {
            return self::getAllStatus()[(int) ($values[$field] ?? 0)] ?? '';
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    private function normalizeInput(array $input): array
    {
        $input['name'] = PluginVehicleschedulerInput::string($input, 'name', 255);
        $input['users_id'] = PluginVehicleschedulerInput::int($input, 'users_id', 0, 0);
        $input['groups_id'] = PluginVehicleschedulerInput::int($input, 'groups_id', 0, 0);
        $input['plugin_vehiclescheduler_schedules_id'] = PluginVehicleschedulerInput::int(
            $input,
            'plugin_vehiclescheduler_schedules_id',
            0,
            0
        );
        $input['plugin_vehiclescheduler_vehicles_id'] = PluginVehicleschedulerInput::int(
            $input,
            'plugin_vehiclescheduler_vehicles_id',
            0,
            0
        );
        $input['plugin_vehiclescheduler_drivers_id'] = PluginVehicleschedulerInput::int(
            $input,
            'plugin_vehiclescheduler_drivers_id',
            0,
            0
        );
        $input['incident_type'] = PluginVehicleschedulerInput::int(
            $input,
            'incident_type',
            self::TYPE_OTHER,
            self::TYPE_ACCIDENT,
            self::TYPE_OTHER
        );
        $input['status'] = PluginVehicleschedulerInput::int(
            $input,
            'status',
            self::STATUS_OPEN,
            self::STATUS_OPEN,
            self::STATUS_CLOSED
        );
        $input['needs_maintenance'] = PluginVehicleschedulerInput::bool($input, 'needs_maintenance', false);
        $input['needs_insurance'] = PluginVehicleschedulerInput::bool($input, 'needs_insurance', false);
        $input['location'] = PluginVehicleschedulerInput::string($input, 'location', 255);
        $input['contact_phone'] = PluginVehicleschedulerInput::string($input, 'contact_phone', 20);
        $input['description'] = PluginVehicleschedulerInput::text($input, 'description', 5000);
        $input['incident_date'] = $this->normalizeIncidentDate((string) ($input['incident_date'] ?? ''));

        if (array_key_exists('id', $input)) {
            $input['id'] = PluginVehicleschedulerInput::int($input, 'id', 0, 0);
        }

        if (array_key_exists('entities_id', $input)) {
            $input['entities_id'] = PluginVehicleschedulerInput::int($input, 'entities_id', 0, 0);
        }

        return $input;
    }

    private function normalizeIncidentDate(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        $timestamp = strtotime($trimmed);

        if ($timestamp === false) {
            return '';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function applyScheduleContextToInput(array &$input, int $scheduleId): void
    {
        if ($scheduleId <= 0) {
            return;
        }

        $schedule = new PluginVehicleschedulerSchedule();
        if (!$schedule->getFromDB($scheduleId)) {
            return;
        }

        $input['plugin_vehiclescheduler_schedules_id'] = $scheduleId;
        $input['plugin_vehiclescheduler_vehicles_id'] = (int) ($schedule->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0);
        $input['plugin_vehiclescheduler_drivers_id'] = (int) ($schedule->fields['plugin_vehiclescheduler_drivers_id'] ?? 0);
        $input['users_id'] = (int) ($schedule->fields['users_id'] ?? Session::getLoginUserID());
        $input['groups_id'] = (int) ($schedule->fields['groups_id'] ?? 0);

        if (trim((string) ($input['incident_date'] ?? '')) === '') {
            $input['incident_date'] = (string) ($schedule->fields['end_date'] ?? $schedule->fields['begin_date'] ?? '');
        }

        if (trim((string) ($input['location'] ?? '')) === '') {
            $input['location'] = (string) ($schedule->fields['destination'] ?? '');
        }
    }

    private static function getScheduleSummary(int $scheduleId): string
    {
        if ($scheduleId <= 0) {
            return '';
        }

        $schedule = new PluginVehicleschedulerSchedule();
        if (!$schedule->getFromDB($scheduleId)) {
            return '';
        }

        $begin = Html::convDateTime((string) ($schedule->fields['begin_date'] ?? ''));
        $end = Html::convDateTime((string) ($schedule->fields['end_date'] ?? ''));
        $destination = trim((string) ($schedule->fields['destination'] ?? ''));
        $requester = (int) ($schedule->fields['users_id'] ?? 0) > 0 ? getUserName((int) $schedule->fields['users_id']) : '';

        $parts = [
            '#' . $scheduleId,
            $destination !== '' ? $destination : 'Sem destino',
            $begin !== '' && $end !== '' ? $begin . ' - ' . $end : '',
            $requester !== '' ? $requester : '',
        ];

        return trim(implode(' | ', array_filter($parts, static fn ($part) => trim((string) $part) !== '')));
    }

    private function isCurrentUserRequester(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $incident = new self();
        if (!$incident->getFromDB($id)) {
            return false;
        }

        return (int) ($incident->fields['users_id'] ?? 0) === (int) Session::getLoginUserID();
    }

    private function canCurrentUserUpdateOwnOpenIncident(int $id): bool
    {
        if (!$this->isCurrentUserRequester($id)) {
            return false;
        }

        $incident = new self();
        if (!$incident->getFromDB($id)) {
            return false;
        }

        return (int) ($incident->fields['status'] ?? self::STATUS_OPEN) === self::STATUS_OPEN;
    }

    private static function getStatusModifier(int $status): string
    {
        return match ($status) {
            self::STATUS_OPEN => 'pending',
            self::STATUS_ANALYZING => 'active',
            self::STATUS_RESOLVED => 'approved',
            self::STATUS_CLOSED => 'inactive',
            default => 'active',
        };
    }
}
