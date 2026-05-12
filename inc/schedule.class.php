<?php

class PluginVehicleschedulerSchedule extends \CommonDBTM
{
    public const STATUS_PENDING  = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;

    public const SEARCHOPT_STATUS        = 80;
    public const SEARCHOPT_DESTINATION   = 81;
    public const SEARCHOPT_BEGIN_DATE    = 82;
    public const SEARCHOPT_END_DATE      = 83;
    public const SEARCHOPT_REQUESTER     = 84;
    public const SEARCHOPT_VEHICLE       = 85;
    public const SEARCHOPT_DRIVER        = 86;
    public const SEARCHOPT_DEPARTMENT    = 87;
    public const SEARCHOPT_PASSENGERS    = 88;
    public const SEARCHOPT_APPROVED_BY   = 89;
    public const SEARCHOPT_APPROVAL_DATE = 90;
    public const SEARCHOPT_CREATED_AT    = 91;

    protected array $preUpdateSnapshot = [];

    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_vehiclescheduler_schedules';
    }

    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING  => 'Pendente',
            self::STATUS_APPROVED => 'Aprovada',
            self::STATUS_REJECTED => 'Recusada',
        ];
    }

    public static function getStatusLabel(?int $status): string
    {
        $options = self::getStatusOptions();

        return $options[$status] ?? 'Desconhecido';
    }

    public static function getStatusSearchOptionId(): int
    {
        return self::SEARCHOPT_STATUS;
    }

    public static function canAssignResources(): bool
    {
        return \PluginVehicleschedulerProfile::canEditManagement()
            || \PluginVehicleschedulerProfile::canApproveReservations();
    }

    public static function canChangeStatus(): bool
    {
        return \PluginVehicleschedulerProfile::canApproveReservations();
    }

    public static function canOpenForm(): bool
    {
        return \PluginVehicleschedulerProfile::canAccessRequester()
            || \PluginVehicleschedulerProfile::canApproveReservations()
            || \PluginVehicleschedulerProfile::canViewManagement()
            || \PluginVehicleschedulerProfile::canEditManagement();
    }

    public static function canCreateRequest(): bool
    {
        return \PluginVehicleschedulerProfile::canAccessRequester()
            || \PluginVehicleschedulerProfile::canEditManagement();
    }

    public static function canUpdateOwnPendingRequest(int $id): bool
    {
        if ($id <= 0 || !self::canCreateRequest()) {
            return false;
        }

        $item = new self();
        if (!$item->getFromDB($id)) {
            return false;
        }

        return (int)($item->fields['users_id'] ?? 0) === (int)\Session::getLoginUserID()
            && (int)($item->fields['status'] ?? 0) === self::STATUS_PENDING;
    }

    protected function validateScheduleDateRange(array $input): array|false
    {
        $itemId = (int)($input['id'] ?? 0);
        $beginDate = trim((string)($input['begin_date'] ?? ''));
        $endDate = trim((string)($input['end_date'] ?? ''));

        if ($itemId > 0 && ($beginDate === '' || $endDate === '')) {
            $current = new self();
            if ($current->getFromDB($itemId)) {
                if ($beginDate === '') {
                    $beginDate = trim((string)($current->fields['begin_date'] ?? ''));
                    $input['begin_date'] = $beginDate;
                }

                if ($endDate === '') {
                    $endDate = trim((string)($current->fields['end_date'] ?? ''));
                    $input['end_date'] = $endDate;
                }
            }
        }

        if ($beginDate === '') {
            \Session::addMessageAfterRedirect('A data/hora de saida e obrigatoria.', false, ERROR);

            return false;
        }

        if ($endDate === '') {
            \Session::addMessageAfterRedirect('A data/hora de retorno e obrigatoria.', false, ERROR);

            return false;
        }

        $beginTimestamp = strtotime($beginDate);
        $endTimestamp = strtotime($endDate);

        if ($beginTimestamp === false || $endTimestamp === false) {
            \Session::addMessageAfterRedirect('Informe datas e horarios validos para a reserva.', false, ERROR);

            return false;
        }

        if ($beginTimestamp >= $endTimestamp) {
            \Session::addMessageAfterRedirect(
                'A data/hora de saida deve ser menor que a data/hora de retorno.',
                false,
                ERROR
            );

            return false;
        }

        return $input;
    }

    protected function validateDriverVehicleCompatibilityFromInput(array $input): array|false
    {
        $itemId = (int)($input['id'] ?? 0);
        $vehicleId = (int)($input['plugin_vehiclescheduler_vehicles_id'] ?? 0);
        $driverId = (int)($input['plugin_vehiclescheduler_drivers_id'] ?? 0);

        if ($itemId > 0 && ($vehicleId <= 0 || $driverId <= 0)) {
            $current = new self();
            if ($current->getFromDB($itemId)) {
                if ($vehicleId <= 0) {
                    $vehicleId = (int)($current->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0);
                    $input['plugin_vehiclescheduler_vehicles_id'] = $vehicleId;
                }

                if ($driverId <= 0) {
                    $driverId = (int)($current->fields['plugin_vehiclescheduler_drivers_id'] ?? 0);
                    $input['plugin_vehiclescheduler_drivers_id'] = $driverId;
                }
            }
        }

        if ($vehicleId <= 0 || $driverId <= 0) {
            return $input;
        }

        $error = self::getDriverVehicleCompatibilityError($vehicleId, $driverId);
        if ($error !== null) {
            \Session::addMessageAfterRedirect($error, false, ERROR);
            return false;
        }

        return $input;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->validateScheduleDateRange($input);
        if ($input === false) {
            return false;
        }

        $input = $this->validateDriverVehicleCompatibilityFromInput($input);
        if ($input === false) {
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->validateScheduleDateRange($input);
        if ($input === false) {
            return false;
        }

        $input = $this->validateDriverVehicleCompatibilityFromInput($input);
        if ($input === false) {
            return false;
        }

        $itemId = (int)($input['id'] ?? 0);
        if ($itemId > 0) {
            $current = new self();
            if ($current->getFromDB($itemId)) {
                $this->preUpdateSnapshot = $current->fields;
            }
        }

        return parent::prepareInputForUpdate($input);
    }

    public function can($ID, int $right, ?array &$input = null): bool
    {
        switch ($right) {
            case READ:
                return self::canOpenForm();

            case CREATE:
                return self::canCreateRequest();

            case UPDATE:
                return \PluginVehicleschedulerProfile::canEditManagement()
                    || self::canAssignResources()
                    || self::canApproveReservationFlow()
                    || self::canUpdateOwnPendingRequest((int)$ID);

            case DELETE:
            case PURGE:
                return \PluginVehicleschedulerProfile::canEditManagement();
        }

        return false;
    }

    public static function canCreate(): bool
    {
        return self::canCreateRequest();
    }

    public function redirectToList(): void
    {
        global $CFG_GLPI;

        $rootDoc = plugin_vehiclescheduler_get_root_doc();

        \Html::redirect(plugin_vehiclescheduler_get_front_url('schedule.php'));
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => __('Characteristics'),
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => self::getTable(),
            'field'         => 'destination',
            'linkfield'     => 'id',
            'name'          => __('Destino'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => 2,
            'table'         => self::getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'datatype'      => 'number',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_STATUS,
            'table'         => self::getTable(),
            'field'         => 'status',
            'name'          => __('Status'),
            'datatype'      => 'specific',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_BEGIN_DATE,
            'table'         => self::getTable(),
            'field'         => 'begin_date',
            'name'          => __('Saída'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_END_DATE,
            'table'         => self::getTable(),
            'field'         => 'end_date',
            'name'          => __('Retorno'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_REQUESTER,
            'table'         => 'glpi_users',
            'field'         => 'name',
            'linkfield'     => 'users_id',
            'name'          => __('Solicitante'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_VEHICLE,
            'table'         => 'glpi_plugin_vehiclescheduler_vehicles',
            'field'         => 'name',
            'linkfield'     => 'plugin_vehiclescheduler_vehicles_id',
            'name'          => __('Viatura'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_DRIVER,
            'table'         => 'glpi_plugin_vehiclescheduler_drivers',
            'field'         => 'name',
            'linkfield'     => 'plugin_vehiclescheduler_drivers_id',
            'name'          => __('Motorista'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_DEPARTMENT,
            'table'         => self::getTable(),
            'field'         => 'department',
            'name'          => __('Departamento'),
            'datatype'      => 'string',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_PASSENGERS,
            'table'         => self::getTable(),
            'field'         => 'passengers',
            'name'          => __('Passageiros'),
            'datatype'      => 'number',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_APPROVED_BY,
            'table'         => 'glpi_users',
            'field'         => 'name',
            'linkfield'     => 'approved_by',
            'name'          => __('Aprovado por'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_APPROVAL_DATE,
            'table'         => self::getTable(),
            'field'         => 'approval_date',
            'name'          => __('Data da aprovação'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => self::SEARCHOPT_CREATED_AT,
            'table'         => self::getTable(),
            'field'         => 'date_creation',
            'name'          => __('Criação'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        return $tab;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = []): string
    {
        if ((int)$field === self::SEARCHOPT_STATUS) {
            $status = 0;

            if (is_array($values)) {
                $status = (int)($values['raw'] ?? $values['value'] ?? 0);
            } else {
                $status = (int)$values;
            }

            return self::getStatusLabel($status);
        }

        return '';
    }

    public static function canApproveReservationFlow(): bool
    {
        return \PluginVehicleschedulerProfile::canApproveReservations()
            && self::canChangeStatus();
    }

    public function canBeApproved(): bool
    {
        return (int)($this->fields['status'] ?? 0) === self::STATUS_PENDING;
    }

    public function hasAssignedResources(): bool
    {
        return (int)($this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0) > 0
            && (int)($this->fields['plugin_vehiclescheduler_drivers_id'] ?? 0) > 0;
    }

    public static function getDriverVehicleCompatibilityError(int $vehicleId, int $driverId): ?string
    {
        if ($vehicleId <= 0 || $driverId <= 0) {
            return null;
        }

        $vehicle = new \PluginVehicleschedulerVehicle();
        $driver = new \PluginVehicleschedulerDriver();

        if (!$vehicle->getFromDB($vehicleId) || !$driver->getFromDB($driverId)) {
            return null;
        }

        $requiredCategory = trim((string)($vehicle->fields['required_cnh_category'] ?? ''));
        if ($requiredCategory === '') {
            return null;
        }

        if (\PluginVehicleschedulerDriver::hasRequiredCNHCategory($driver->fields['cnh_category'] ?? '', $requiredCategory)) {
            return null;
        }

        $driverCategories = \PluginVehicleschedulerDriver::getDriverCNHCategoryList($driver->fields['cnh_category'] ?? '');
        $qualifiedCategories = \PluginVehicleschedulerDriver::getDriverQualifiedCNHCategoryList($driver->fields['cnh_category'] ?? '');
        $driverLabel = $driverCategories !== [] ? implode(', ', $driverCategories) : 'nenhuma categoria informada';
        $qualifiedLabel = $qualifiedCategories !== [] ? implode(', ', $qualifiedCategories) : $driverLabel;

        return 'O motorista selecionado nÃ£o possui a CNH exigida para esta viatura. '
            . 'Exigida: ' . $requiredCategory . '. '
            . 'Motorista: ' . $driverLabel . '. '
            . 'Habilita: ' . $qualifiedLabel . '.';
    }

    protected function validateApprovalAssignments(): void
    {
        if ((int)($this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0) <= 0) {
            throw new \RuntimeException('Selecione a viatura antes de aprovar a reserva.');
        }

        if ((int)($this->fields['plugin_vehiclescheduler_drivers_id'] ?? 0) <= 0) {
            throw new \RuntimeException('Selecione o motorista antes de aprovar a reserva.');
        }

        $error = self::getDriverVehicleCompatibilityError(
            (int)($this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0),
            (int)($this->fields['plugin_vehiclescheduler_drivers_id'] ?? 0)
        );

        if ($error !== null) {
            throw new \RuntimeException($error);
        }
    }

    protected static function mapScheduleStatusToTicketStatus(int $scheduleStatus): int
    {
        switch ($scheduleStatus) {
            case self::STATUS_APPROVED:
                return \CommonITILObject::PLANNED;

            case self::STATUS_REJECTED:
                return \CommonITILObject::CLOSED;

            case self::STATUS_PENDING:
            default:
                return \CommonITILObject::INCOMING;
        }
    }

    protected function getVisualReference(): string
    {
        $rawDate = (string)($this->fields['date_creation'] ?? '');
        $timestamp = strtotime($rawDate);
        if ($timestamp === false) {
            $timestamp = time();
        }

        return date('Y', $timestamp) . '-' . (int)($this->fields['id'] ?? 0);
    }

    protected function getTicketTitle(): string
    {
        $destination = trim((string)($this->fields['destination'] ?? ''));
        if ($destination === '') {
            $destination = 'Destino não informado';
        }

        return 'Reserva: #' . $this->getVisualReference() . ' - ' . $destination;
    }

    protected function getTicketContent(): string
    {
        $lines = [];

        $lines[] = 'Pedido de viatura vinculado à reserva #' . $this->getVisualReference() . '.';
        $lines[] = '';

        $destination = trim((string)($this->fields['destination'] ?? ''));
        if ($destination !== '') {
            $lines[] = 'Destino: ' . $destination;
        }

        $purpose = trim((string)($this->fields['purpose'] ?? ''));
        if ($purpose !== '') {
            $lines[] = 'Finalidade: ' . $purpose;
        }

        if (!empty($this->fields['begin_date'])) {
            $lines[] = 'Saída: ' . \Html::convDateTime($this->fields['begin_date']);
        }

        if (!empty($this->fields['end_date'])) {
            $lines[] = 'Retorno: ' . \Html::convDateTime($this->fields['end_date']);
        }

        $passengers = (int)($this->fields['passengers'] ?? 0);
        if ($passengers > 0) {
            $lines[] = 'Passageiros: ' . $passengers;
        }

        $contactPhone = trim((string)($this->fields['contact_phone'] ?? ''));
        if ($contactPhone !== '') {
            $lines[] = 'Telefone de contato: ' . $contactPhone;
        }

        $vehicleId = (int)($this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0);
        if ($vehicleId > 0) {
            $vehicleName = \Dropdown::getDropdownName('glpi_plugin_vehiclescheduler_vehicles', $vehicleId);
            if ($vehicleName !== '') {
                $lines[] = 'Viatura: ' . $vehicleName;
            }
        }

        $driverId = (int)($this->fields['plugin_vehiclescheduler_drivers_id'] ?? 0);
        if ($driverId > 0) {
            $driverName = \Dropdown::getDropdownName('glpi_plugin_vehiclescheduler_drivers', $driverId);
            if ($driverName !== '') {
                $lines[] = 'Motorista: ' . $driverName;
            }
        }

        $statusLabel = self::getStatusLabel((int)($this->fields['status'] ?? self::STATUS_PENDING));
        $lines[] = 'Status da reserva: ' . $statusLabel;

        $rejection = trim((string)($this->fields['rejection_justification'] ?? ''));
        if ($rejection !== '') {
            $lines[] = 'Justificativa da recusa: ' . $rejection;
        }

        $comment = trim((string)($this->fields['comment'] ?? ''));
        if ($comment !== '') {
            $lines[] = 'Observações internas: ' . $comment;
        }

        return implode("\n", $lines);
    }

    protected function createLinkedTicket(): void
    {
        if ((int)($this->fields['tickets_id'] ?? 0) > 0) {
            return;
        }

        $ticket = new \Ticket();

        $input = [
            'name'                => $this->getTicketTitle(),
            'content'             => $this->getTicketContent(),
            'status'              => self::mapScheduleStatusToTicketStatus((int)($this->fields['status'] ?? self::STATUS_PENDING)),
            'type'                => \Ticket::DEMAND_TYPE,
            'entities_id'         => (int)($this->fields['entities_id'] ?? 0),
            '_users_id_requester' => (int)($this->fields['users_id'] ?? 0),
        ];

        $ticketId = $ticket->add($input);
        if ((int)$ticketId <= 0) {
            error_log('vehiclescheduler: failed to create linked ticket for schedule #' . (int)$this->fields['id']);
            return;
        }

        $this->update([
            'id'         => (int)$this->fields['id'],
            'tickets_id' => (int)$ticketId,
        ]);

        $this->fields['tickets_id'] = (int)$ticketId;
    }


    protected function shouldCreateAssignmentFollowup(): bool
    {
        if ((int)($this->fields['tickets_id'] ?? 0) <= 0) {
            return false;
        }

        $currentVehicleId = (int)($this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0);
        $currentDriverId  = (int)($this->fields['plugin_vehiclescheduler_drivers_id'] ?? 0);

        if ($currentVehicleId <= 0 || $currentDriverId <= 0) {
            return false;
        }

        $previousVehicleId = (int)($this->preUpdateSnapshot['plugin_vehiclescheduler_vehicles_id'] ?? 0);
        $previousDriverId  = (int)($this->preUpdateSnapshot['plugin_vehiclescheduler_drivers_id'] ?? 0);

        return $currentVehicleId !== $previousVehicleId || $currentDriverId !== $previousDriverId;
    }

    protected function buildAssignmentFollowupContent(): string
    {
        $lines = [];
        $lines[] = 'Recursos definidos para a reserva #' . $this->getVisualReference() . '.';

        $vehicleName = \Dropdown::getDropdownName(
            'glpi_plugin_vehiclescheduler_vehicles',
            (int)($this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0)
        );
        if ($vehicleName !== '') {
            $lines[] = 'Viatura: ' . $vehicleName;
        }

        $driverName = \Dropdown::getDropdownName(
            'glpi_plugin_vehiclescheduler_drivers',
            (int)($this->fields['plugin_vehiclescheduler_drivers_id'] ?? 0)
        );
        if ($driverName !== '') {
            $lines[] = 'Motorista: ' . $driverName;
        }

        return implode("\n", $lines);
    }

    protected function createAssignmentFollowup(): void
    {
        if (!$this->shouldCreateAssignmentFollowup()) {
            return;
        }

        $ticketId = (int)($this->fields['tickets_id'] ?? 0);
        if ($ticketId <= 0) {
            return;
        }

        $followup = new \ITILFollowup();
        $followupId = $followup->add([
            'itemtype'  => \Ticket::class,
            'items_id'  => $ticketId,
            'content'   => $this->buildAssignmentFollowupContent(),
            'users_id'  => (int)\Session::getLoginUserID(),
            'is_private' => 0,
        ]);

        if ((int)$followupId <= 0) {
            error_log('vehiclescheduler: failed to create ticket followup for ticket #' . $ticketId . ' and schedule #' . (int)$this->fields['id']);
        }
    }

    protected function syncLinkedTicket(): void
    {
        $ticketId = (int)($this->fields['tickets_id'] ?? 0);
        if ($ticketId <= 0) {
            $this->createLinkedTicket();
            $ticketId = (int)($this->fields['tickets_id'] ?? 0);
        }

        if ($ticketId <= 0) {
            return;
        }

        $ticket = new \Ticket();
        if (!$ticket->getFromDB($ticketId)) {
            error_log('vehiclescheduler: linked ticket #' . $ticketId . ' not found for schedule #' . (int)$this->fields['id']);
            return;
        }

        $ticket->update([
            'id'      => $ticketId,
            'name'    => $this->getTicketTitle(),
            'content' => $this->getTicketContent(),
            'status'  => self::mapScheduleStatusToTicketStatus((int)($this->fields['status'] ?? self::STATUS_PENDING)),
        ]);
    }

    public function post_addItem()
    {
        $this->getFromDB((int)$this->fields['id']);
        $this->syncLinkedTicket();
    }

    public function post_updateItem($history = 1)
    {
        $this->getFromDB((int)$this->fields['id']);
        $this->syncLinkedTicket();
        $this->createAssignmentFollowup();

        $previousStatus = (int) ($this->preUpdateSnapshot['status'] ?? 0);
        $currentStatus = (int) ($this->fields['status'] ?? 0);

        if (
            $previousStatus !== self::STATUS_APPROVED
            && $currentStatus === self::STATUS_APPROVED
        ) {
            PluginVehicleschedulerChecklist::maybeOpenDepartureChecklistAfterApproval($this);
        }
    }

    public function approveReservation(int $id): bool
    {
        if ($id <= 0) {
            throw new \RuntimeException('Invalid reservation ID.');
        }

        if (!$this->getFromDB($id)) {
            throw new \RuntimeException('Reservation not found.');
        }

        if (!self::canApproveReservationFlow()) {
            throw new \RuntimeException('User is not allowed to approve reservations.');
        }

        if (!$this->canBeApproved()) {
            throw new \RuntimeException('Reservation cannot be approved in the current status.');
        }

        $this->validateApprovalAssignments();

        $input = [
            'id'                      => $id,
            'status'                  => self::STATUS_APPROVED,
            'approved_by'             => \Session::getLoginUserID(),
            'approval_date'           => date('Y-m-d H:i:s'),
            'rejection_justification' => null,
        ];

        return (bool)$this->update($input);
    }

    public function rejectReservation(int $id, string $justification): bool
    {
        $justification = trim($justification);

        if ($id <= 0) {
            throw new \RuntimeException('Invalid reservation ID.');
        }

        if ($justification === '') {
            throw new \RuntimeException('A rejection justification is required.');
        }

        if (!$this->getFromDB($id)) {
            throw new \RuntimeException('Reservation not found.');
        }

        if (!self::canApproveReservationFlow()) {
            throw new \RuntimeException('User is not allowed to reject reservations.');
        }

        if (!$this->canBeApproved()) {
            throw new \RuntimeException('Reservation cannot be rejected in the current status.');
        }

        $input = [
            'id'                      => $id,
            'status'                  => self::STATUS_REJECTED,
            'approved_by'             => \Session::getLoginUserID(),
            'approval_date'           => date('Y-m-d H:i:s'),
            'rejection_justification' => $justification,
        ];

        return (bool)$this->update($input);
    }

    protected static function buildApprovalQueueWhere(?int $statusFilter = null): string
    {
        $where = [];

        if ($statusFilter !== null) {
            $where[] = 's.status = ' . (int)$statusFilter;
        }

        if (method_exists('Session', 'getActiveEntities')) {
            $entityIds = array_map('intval', (array)\Session::getActiveEntities());
            $entityIds = array_values(array_filter($entityIds, static fn($id) => $id >= 0));

            if ($entityIds !== []) {
                $where[] = 's.entities_id IN (' . implode(',', $entityIds) . ')';
            }
        }

        return $where ? 'WHERE ' . implode(' AND ', $where) : '';
    }

    public static function getApprovalQueueCounts(): array
    {
        global $DB;

        $counts = [
            self::STATUS_PENDING  => 0,
            self::STATUS_APPROVED => 0,
            self::STATUS_REJECTED => 0,
        ];

        $where = [];

        if (method_exists('Session', 'getActiveEntities')) {
            $entityIds = array_map('intval', (array)\Session::getActiveEntities());
            $entityIds = array_values(array_filter($entityIds, static fn($id) => $id >= 0));

            if ($entityIds !== []) {
                $where[] = 's.entities_id IN (' . implode(',', $entityIds) . ')';
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "
            SELECT
                s.status,
                COUNT(*) AS total
            FROM glpi_plugin_vehiclescheduler_schedules s
            {$whereSql}
            GROUP BY s.status
        ";

        $result = $DB->doQuery($sql);
        if ($result === false) {
            return $counts;
        }

        while ($row = $DB->fetchAssoc($result)) {
            $counts[(int)$row['status']] = (int)$row['total'];
        }

        return $counts;
    }

    public static function getApprovalQueueRows(?int $statusFilter = null): array
    {
        global $DB;

        $whereSql = self::buildApprovalQueueWhere($statusFilter);

        $sql = "
            SELECT
                s.id,
                s.tickets_id,
                s.status,
                s.destination,
                s.begin_date,
                s.end_date,
                s.passengers,
                s.rejection_justification,
                s.date_creation,
                requester.name AS requester_name,
                vehicle.name AS vehicle_name,
                driver.name AS driver_name,
                vehicle.name AS vehicle_name,
                vehicle.plate AS vehicle_plate,
                vehicle.brand AS vehicle_brand,
                vehicle.model AS vehicle_model
            FROM glpi_plugin_vehiclescheduler_schedules s
            LEFT JOIN glpi_users requester
                ON requester.id = s.users_id
            LEFT JOIN glpi_plugin_vehiclescheduler_vehicles vehicle
                ON vehicle.id = s.plugin_vehiclescheduler_vehicles_id
            LEFT JOIN glpi_plugin_vehiclescheduler_drivers driver
                ON driver.id = s.plugin_vehiclescheduler_drivers_id
            {$whereSql}
            ORDER BY
                CASE WHEN s.status = " . (int)self::STATUS_PENDING . " THEN 0 ELSE 1 END,
                s.begin_date ASC,
                s.id DESC
        ";

        $rows = [];

        $result = $DB->doQuery($sql);
        if ($result === false) {
            return $rows;
        }

        while ($row = $DB->fetchAssoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }
}
