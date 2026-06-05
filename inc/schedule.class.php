<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Schedule — Agendamento de Veículos
 */

if (!defined('GLPI_ROOT')) {
    die("Acesso direto não permitido");
}

class PluginVehicleschedulerSchedule extends CommonDBTM {

    public $dohistory = true;
    static $rightname = 'plugin_vehiclescheduler';

    const STATUS_NEW       = 1;
    const STATUS_APPROVED  = 2;
    const STATUS_REJECTED  = 3;
    const STATUS_CANCELLED = 4;

    static function getTypeName($nb = 0) {
        return ($nb === 1) ? 'Agendamento' : 'Agendamentos';
    }

    static function getMenuName() {
        return 'Agendamento de Veículos';
    }

    static function getIcon() {
        return 'ti ti-calendar-event';
    }

    static function getAllStatus() {
        return [
            self::STATUS_NEW       => 'Nova',
            self::STATUS_APPROVED  => 'Aprovada',
            self::STATUS_REJECTED  => 'Recusada',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    static function getMenuContent() {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
            return false;
        }
        $menu = [];
        $menu['title'] = 'Agendamento de Veículos';
        $menu['page']  = '/plugins/vehiclescheduler/front/schedule.php';
        $menu['icon']  = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/schedule.php';
        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/schedule.form.php';
        }
        // Extra quick links
        $menu['links']['<i class="ti ti-layout-dashboard"></i>'] = '/plugins/vehiclescheduler/front/dashboard.php';
        $menu['links']['<i class="ti ti-home"></i>']             = '/plugins/vehiclescheduler/front/portal.php';
        $menu['options']['schedule'] = [
            'title'          => 'Agendamentos',
            'page'           => '/plugins/vehiclescheduler/front/schedule.php',
            'icon'           => self::getIcon(),
            'links'          => [
                'search' => '/plugins/vehiclescheduler/front/schedule.php',
                'add'    => '/plugins/vehiclescheduler/front/schedule.form.php',
            ],
            'lists_itemtype' => 'PluginVehicleschedulerSchedule',
        ];
        return $menu;
    }

    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Ticket', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    function showForm($ID, array $options = []) {
        $this->initForm($ID, $options);
        
        echo "<form method='post' action='" . $this->getFormURL() . "' enctype='multipart/form-data'>";
        echo "<div class='container-fluid mb-4'><div class='card'>";
        echo "<div class='card-header d-flex justify-content-between align-items-center'><h3 class='card-title'>Solicitação de Reserva</h3> <a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'><i class='ti ti-arrow-left'></i> Voltar</a></div>";
        echo "<div class='card-body'>";

        $is_manager = PluginVehicleschedulerProfile::canViewManagement();

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>Solicitante <span class='red'>*</span></label>";
        if ($is_manager) {
            User::dropdown(['name' => 'users_id', 'value' => $this->fields['users_id'] ?: Session::getLoginUserID(), 'right' => 'all']);
        } else {
            $u_id = $this->fields['users_id'] ?: Session::getLoginUserID();
            echo "<input type='hidden' name='users_id' value='$u_id'>";
            echo "<div class='form-control bg-light'>" . getUserName($u_id) . "</div>";
        }
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>Departamento/Setor <span class='red'>*</span></label>";
        echo Html::input('department', ['value' => $this->fields['department'] ?? '', 'class' => 'form-control']);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>Telefone para Contato <span class='red'>*</span></label>";
        echo Html::input('contact_phone', ['value' => $this->fields['contact_phone'], 'class' => 'form-control']);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>Veículo <span class='red'>*</span></label>";
        PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'], 'entity' => $this->fields['entities_id']]);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>Data/Hora de Saída <span class='red'>*</span></label>";
        Html::showDateTimeField('begin_date', ['value' => $this->fields['begin_date']]);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>Data/Hora de Retorno <span class='red'>*</span></label>";
        Html::showDateTimeField('end_date', ['value' => $this->fields['end_date']]);
        echo "</div></div>";

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-6'><label class='form-label'>Destino <span class='red'>*</span></label>";
        echo Html::input('destination', ['value' => $this->fields['destination'], 'class' => 'form-control']);
        echo "</div>";
        echo "<div class='col-md-6'><label class='form-label'>Nº de Passageiros</label>";
        echo Html::input('passengers', ['value' => $this->fields['passengers'] ?: 1, 'type' => 'number', 'min' => 1, 'class' => 'form-control']);
        echo "</div></div>";

        if ($is_manager) {
            echo "<div class='row mb-3'>";
            echo "<div class='col-md-12'><label class='form-label'>Motorista Designado <small class='text-muted'>(opcional — atribuir após aprovação)</small></label>";
            PluginVehicleschedulerDriver::dropdown(['name' => 'plugin_vehiclescheduler_drivers_id', 'value' => $this->fields['plugin_vehiclescheduler_drivers_id'] ?? 0]);
            echo "</div></div>";
        }

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-12'><label class='form-label'>Descrição / Finalidade <span class='red'>*</span></label>";
        echo "<textarea name='purpose' class='form-control' rows='4' placeholder='Descreva a finalidade desta reserva'>" . htmlspecialchars($this->fields['purpose'] ?? '') . "</textarea>";
        echo "</div></div>";

        if ($ID > 0 && Session::haveRight('plugin_vehiclescheduler', UPDATE) && $is_manager) {
            echo "<div class='row mb-3'>";
            echo "<div class='col-md-6'><label class='form-label'>Status</label>";
            Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status']]);
            echo "</div></div>";
        }

        echo "<div class='row mb-3'>";
        echo "<div class='col-md-12'><label class='form-label'>Comentários Adicionais</label>";
        echo "<textarea name='comment' class='form-control' rows='3' placeholder='Observações adicionais'>" . htmlspecialchars($this->fields['comment'] ?? '') . "</textarea>";
        echo "</div></div>";

        if (!empty($this->fields['tickets_id']) && $this->fields['tickets_id'] > 0) {
            echo "<div class='row mb-3'><div class='col-md-12'><label class='form-label'>Chamado Relacionado:</label> ";
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->fields['tickets_id'])) {
                echo $ticket->getLink();
            }
            echo "</div></div>";
        }

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
        if (!isset($input['users_id']) || $input['users_id'] == 0) {
            $input['users_id'] = Session::getLoginUserID();
        }
        if (!isset($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
        }
        if (!isset($input['status'])) {
            $input['status'] = self::STATUS_NEW;
        }
        if (empty($input['name'])) {
            $vname = '';
            if (!empty($input['plugin_vehiclescheduler_vehicles_id'])) {
                $v = new PluginVehicleschedulerVehicle();
                if ($v->getFromDB($input['plugin_vehiclescheduler_vehicles_id'])) {
                    $vname = $v->fields['name'];
                }
            }
            $input['name'] = 'Reserva ' . $vname . ' — ' . date('d/m/Y H:i');
        }

        if (empty(trim($input['department'] ?? ''))) {
            Session::addMessageAfterRedirect('O departamento é obrigatório.', false, ERROR);
            return false;
        }
        if (empty(trim($input['contact_phone'] ?? ''))) {
            Session::addMessageAfterRedirect('O telefone para contato é obrigatório.', false, ERROR);
            return false;
        }
        if (empty(trim($input['destination'] ?? ''))) {
            Session::addMessageAfterRedirect('O destino é obrigatório.', false, ERROR);
            return false;
        }
        if (empty(trim($input['purpose'] ?? ''))) {
            Session::addMessageAfterRedirect('A descrição/finalidade é obrigatória.', false, ERROR);
            return false;
        }
        return $input;
    }

    function post_addItem() {
        parent::post_addItem();
        $this->createTicketFromSchedule();
    }

    /**
     * Hook após update - atualiza chamado quando status muda
     */
    function post_updateItem($history = true) {
        parent::post_updateItem($history);
        
        // Se status mudou e existe ticket vinculado
        if (in_array('status', $this->updates) && $this->fields['tickets_id'] > 0) {
            $this->updateTicketStatus();
        }
        return true;
    }

    function createTicketFromSchedule() {
        if (!empty($this->fields['tickets_id']) && $this->fields['tickets_id'] > 0) {
            return false;
        }

        $vname = '';
        $vehicle = new PluginVehicleschedulerVehicle();
        if ($vehicle->getFromDB($this->fields['plugin_vehiclescheduler_vehicles_id'])) {
            $vname = $vehicle->fields['name'] . ' (' . $vehicle->fields['plate'] . ')';
        }

        $dname = '';
        if (!empty($this->fields['plugin_vehiclescheduler_drivers_id'])) {
            $driver = new PluginVehicleschedulerDriver();
            if ($driver->getFromDB($this->fields['plugin_vehiclescheduler_drivers_id'])) {
                $dname = $driver->fields['name'];
            }
        }

        $title = "Reserva de Veículo: {$vname} — " . $this->fields['destination'];

        $content = "Detalhes da Reserva de Veículo:\n\n"
            . "Veículo: {$vname}\n"
            . "Solicitante: " . getUserName($this->fields['users_id']) . "\n"
            . "Departamento: " . $this->fields['department'] . "\n"
            . "Telefone: " . $this->fields['contact_phone'] . "\n"
            . "Saída: " . $this->fields['begin_date'] . "\n"
            . "Retorno: " . $this->fields['end_date'] . "\n"
            . "Destino: " . $this->fields['destination'] . "\n"
            . "Passageiros: " . $this->fields['passengers'] . "\n"
            . ($dname ? "Motorista: {$dname}\n" : "")
            . "Finalidade: " . $this->fields['purpose'];

        $ticket    = new Ticket();
        $ticket_id = $ticket->add([
            'name'                 => $title,
            'content'              => $content,
            'entities_id'          => $this->fields['entities_id'],
            'type'                 => Ticket::DEMAND_TYPE,
            'urgency'              => 3,
            'impact'               => 3,
            'priority'             => CommonITILObject::computePriority(3, 3),
            '_users_id_requester'  => $this->fields['users_id'],
        ]);

        if ($ticket_id) {
            $this->update(['id' => $this->fields['id'], 'tickets_id' => $ticket_id]);
            return true;
        }
        return false;
    }

    function rawSearchOptions() {
        $tab   = [];
        $tab[] = ['id' => 'common', 'name' => 'Agendamentos'];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'name',
                  'name' => 'Nome', 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => 'glpi_plugin_vehiclescheduler_vehicles', 'field' => 'name',
                  'name' => 'Veículo', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'begin_date',
                  'name' => 'Data de Saída', 'datatype' => 'datetime'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'end_date',
                  'name' => 'Data de Retorno', 'datatype' => 'datetime'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'destination',
                  'name' => 'Destino', 'datatype' => 'string'];
        $tab[] = ['id' => '6', 'table' => 'glpi_users', 'field' => 'name',
                  'name' => 'Solicitante', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'status',
                  'name' => 'Status', 'datatype' => 'specific',
                  'searchtype' => ['equals', 'notequals']];
        $tab[] = ['id' => '8', 'table' => $this->getTable(), 'field' => 'passengers',
                  'name' => 'Passageiros', 'datatype' => 'number'];
        $tab[] = ['id' => '9', 'table' => 'glpi_tickets', 'field' => 'name',
                  'name' => 'Chamado', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '10', 'table' => $this->getTable(), 'field' => 'department',
                  'name' => 'Departamento/Setor', 'datatype' => 'string'];
        $tab[] = ['id' => '11', 'table' => $this->getTable(), 'field' => 'contact_phone',
                  'name' => 'Telefone', 'datatype' => 'string'];
        $tab[] = ['id' => '12', 'table' => 'glpi_plugin_vehiclescheduler_drivers', 'field' => 'name',
                  'name' => 'Motorista', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'comment',
                  'name' => 'Observações', 'datatype' => 'text'];
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'status') {
            return self::getAllStatus()[$values[$field]] ?? '';
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Atualiza o chamado quando status da reserva muda
     */
    function updateTicketStatus() {
        $ticket = new Ticket();
        if (!$ticket->getFromDB($this->fields['tickets_id'])) {
            return false;
        }

        $statuses = self::getAllStatus();
        $status_label = $statuses[$this->fields['status']] ?? 'Desconhecido';

        // Mapear status da reserva para status do chamado
        $ticket_status_map = [
            self::STATUS_NEW       => CommonITILObject::INCOMING,  // Nova → Novo
            self::STATUS_APPROVED  => CommonITILObject::SOLVED,    // Aprovada → Solucionado
            self::STATUS_REJECTED  => CommonITILObject::CLOSED,    // Recusada → Fechado
            self::STATUS_CANCELLED => CommonITILObject::CLOSED,    // Cancelada → Fechado
        ];

        $new_ticket_status = $ticket_status_map[$this->fields['status']] ?? $ticket->fields['status'];

        // Mensagens de acompanhamento
        $messages = [
            self::STATUS_APPROVED  => '✅ Reserva APROVADA! O veículo está confirmado para o período solicitado.',
            self::STATUS_REJECTED  => '❌ Reserva RECUSADA. Entre em contato com a gestão de frota para mais informações.',
            self::STATUS_CANCELLED => '🚫 Reserva CANCELADA pelo solicitante.',
        ];

        // Adicionar acompanhamento
        if (isset($messages[$this->fields['status']])) {
            $followup = new ITILFollowup();
            $followup->add([
                'itemtype'  => 'Ticket',
                'items_id'  => $this->fields['tickets_id'],
                'users_id'  => Session::getLoginUserID(),
                'content'   => sprintf("Status: %s\n\n%s", strtoupper($status_label), $messages[$this->fields['status']]),
                'is_private'=> 0,
            ]);
        }

        // Atualizar status do chamado
        $ticket->update(['id' => $this->fields['tickets_id'], 'status' => $new_ticket_status]);
        return true;
    }
}
