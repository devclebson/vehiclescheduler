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
        $this->showFormHeader($options);
        
        echo "<tr style='display:none;'><td></td></tr>";
        echo "<tr><td colspan='4' style='padding:0; border:none; background:transparent;'>";
        
        echo "<div class='container-fluid px-3 py-4'>";
        
        // Back Button
        echo "<div class='d-flex justify-content-end mb-3'>
                <a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'>
                    <i class='ti ti-arrow-left'></i> Voltar
                </a>
              </div>";

        // Card 1: Dados da Manutenção
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-tool'></i> Ordem de Manutenção</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Título', 'vehiclescheduler') . " <span class='text-danger'>*</span></label>
                            <input type='text' name='name' value='".htmlspecialchars($this->fields['name'] ?? '')."' class='form-control form-control-lg'>
                        </div>";
                        
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Veículo', 'vehiclescheduler') . " <span class='text-danger'>*</span></label>";
        echo "              <div>";
        PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>" . __('Tipo', 'vehiclescheduler') . " <span class='text-danger'>*</span></label>";
        echo "              <div>";
        Dropdown::showFromArray('type', self::getAllTypes(), ['value' => $this->fields['type'] ?? self::TYPE_PREVENTIVE]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>" . __('Status do Serviço', 'vehiclescheduler') . "</label>";
        echo "              <div>";
        Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status'] ?? self::STATUS_SCHEDULED]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>" . __('Quilometragem (km)', 'vehiclescheduler') . "</label>
                            <input type='number' name='mileage' value='".htmlspecialchars($this->fields['mileage'] ?? '')."' class='form-control'>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Data Agendada', 'vehiclescheduler') . "</label>
                            <div>";
        Html::showDateField('scheduled_date', ['value' => $this->fields['scheduled_date'] ?? '']);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Data de Conclusão', 'vehiclescheduler') . "</label>
                            <div>";
        Html::showDateField('completion_date', ['value' => $this->fields['completion_date'] ?? '']);
        echo "              </div>
                        </div>";

        echo "          <div class='col-12'>
                            <label class='form-label text-muted fw-bold'>" . __('Descrição do Serviço', 'vehiclescheduler') . "</label>
                            <textarea name='description' class='form-control' rows='4' placeholder='Detalhes do que será / foi realizado'>".htmlspecialchars($this->fields['description'] ?? '')."</textarea>
                        </div>";

        echo "      </div>
                </div>
              </div>";

        // Card 2: Financeiro e Fornecedor
        echo "<div class='card shadow-sm border-0 mb-4' style='border-left: 4px solid #10b981 !important;'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-success fw-bold'><i class='ti ti-cash'></i> Financeiro e Fornecedor</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Oficina / Fornecedor', 'vehiclescheduler') . "</label>
                            <input type='text' name='supplier' value='".htmlspecialchars($this->fields['supplier'] ?? '')."' class='form-control' placeholder='Nome da oficina'>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>" . __('Custo Total (R$)', 'vehiclescheduler') . "</label>
                            <div class='input-group'>
                                <span class='input-group-text'>R$</span>
                                <input type='number' name='cost' value='".htmlspecialchars($this->fields['cost'] ?? '')."' class='form-control' step='0.01' placeholder='0.00'>
                            </div>
                        </div>";

        echo "      </div>
                </div>
              </div>";

        // Card 3: Vínculo com Incidente ou Chamado
        $inc_id = $this->fields['plugin_vehiclescheduler_incidents_id'] ?? 0;
        if (isset($_GET['plugin_vehiclescheduler_incidents_id'])) {
            $inc_id = $_GET['plugin_vehiclescheduler_incidents_id'];
            echo "<input type='hidden' name='plugin_vehiclescheduler_incidents_id' value='".intval($inc_id)."'>";
        }
        
        if ($inc_id) {
            echo "<div class='alert alert-secondary d-flex align-items-center border-0 shadow-sm mb-4'>
                    <i class='ti ti-alert-triangle me-2 fs-4 text-warning'></i>
                    <div>
                        <strong>" . __('Origem do Serviço', 'vehiclescheduler') . ":</strong> Incidente Reportado — ";
            $inc = new PluginVehicleschedulerIncident();
            if ($inc->getFromDB($inc_id)) {
                echo $inc->getLink();
                if ($inc->fields['tickets_id'] > 0) {
                    $ticket = new Ticket();
                    if ($ticket->getFromDB($inc->fields['tickets_id'])) {
                        echo " (Chamado Relacionado: " . $ticket->getLink() . ")";
                    }
                }
            }
            echo "  </div>
                  </div>";
        }

        if (($this->fields['tickets_id'] ?? 0) > 0) {
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->fields['tickets_id'])) {
                echo "<div class='alert alert-info d-flex align-items-center border-0 shadow-sm mb-4'>
                        <i class='ti ti-ticket me-2 fs-4 text-info'></i>
                        <div>
                            <strong>" . __('Chamado Relacionado (Preventiva)', 'vehiclescheduler') . ":</strong> " . $ticket->getLink() . "
                        </div>
                      </div>";
            }
        }

        echo "</div>"; // Container End
        echo "</td></tr>";

        // Inject JS to enforce Flatpickr date rules
        echo "<script>
        $(document).ready(function() {
            var scheduled_input = $('input[name=\"scheduled_date\"]');
            var completion_input = $('input[name=\"completion_date\"]');
            
            function setupFlatpickr() {
                var scheduled_wrapper = scheduled_input.closest('.flatpickr');
                var completion_wrapper = completion_input.closest('.flatpickr');
                
                if (scheduled_wrapper.length && scheduled_wrapper[0]._flatpickr) {
                    var scheduled_picker = scheduled_wrapper[0]._flatpickr;
                    var completion_picker = completion_wrapper.length ? completion_wrapper[0]._flatpickr : null;
                    
                    var isNew = " . (($ID > 0) ? 'false' : 'true') . ";
                    if (isNew) {
                        scheduled_picker.set('minDate', 'today');
                    }
                    
                    scheduled_input.on('change', function() {
                        var val = $(this).val();
                        if (val && completion_picker) {
                            completion_picker.set('minDate', val);
                        }
                    });
                    
                    var initial_val = scheduled_input.val();
                    if (initial_val && completion_picker) {
                        completion_picker.set('minDate', initial_val);
                    }
                } else {
                    // Try again in 100ms if Flatpickr isn't initialized yet
                    setTimeout(setupFlatpickr, 100);
                }
            }
            setupFlatpickr();
        });
        </script>";

        $this->showFormButtons($options);
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
        if (empty($input['scheduled_date'])) {
            Session::addMessageAfterRedirect(__('Scheduled date is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }
        
        // Data agendada não pode ser no passado na criação
        $today = date('Y-m-d');
        $scheduled_day = date('Y-m-d', strtotime($input['scheduled_date']));
        if ($scheduled_day < $today) {
            Session::addMessageAfterRedirect(__('Scheduled date cannot be in the past.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        // Data de conclusão não pode ser menor que data agendada
        if (!empty($input['completion_date'])) {
            $completion_day = date('Y-m-d', strtotime($input['completion_date']));
            if ($completion_day < $scheduled_day) {
                Session::addMessageAfterRedirect(__('Completion date cannot be before scheduled date.', 'vehiclescheduler'), false, ERROR);
                return false;
            }
        }

        if (!isset($input['status']))           $input['status'] = self::STATUS_SCHEDULED;
        if (!isset($input['entities_id']))       $input['entities_id'] = $_SESSION['glpiactive_entity'];
        if (!isset($input['type']))              $input['type'] = self::TYPE_PREVENTIVE;
        return $input;
    }

    function prepareInputForUpdate($input) {
        if (empty(trim($input['name'] ?? ''))) {
            Session::addMessageAfterRedirect(__('Title is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }
        if (empty($input['plugin_vehiclescheduler_vehicles_id'])) {
            Session::addMessageAfterRedirect(__('Vehicle is required.', 'vehiclescheduler'), false, ERROR);
            return false;
        }

        // Se data agendada está sendo alterada, validar contra hoje
        if (isset($input['scheduled_date'])) {
            $scheduled_day = date('Y-m-d', strtotime($input['scheduled_date']));
            if (isset($this->fields['scheduled_date']) && $this->fields['scheduled_date'] !== $input['scheduled_date']) {
                $today = date('Y-m-d');
                if ($scheduled_day < $today) {
                    Session::addMessageAfterRedirect(__('Scheduled date cannot be in the past.', 'vehiclescheduler'), false, ERROR);
                    return false;
                }
            }

            // Validar data de conclusão contra data agendada
            $comp_date = $input['completion_date'] ?? $this->fields['completion_date'] ?? '';
            if (!empty($comp_date)) {
                $completion_day = date('Y-m-d', strtotime($comp_date));
                if ($completion_day < $scheduled_day) {
                    Session::addMessageAfterRedirect(__('Completion date cannot be before scheduled date.', 'vehiclescheduler'), false, ERROR);
                    return false;
                }
            }
        }

        return $input;
    }

    function post_addItem() {
        parent::post_addItem();

        $inc_id = $this->fields['plugin_vehiclescheduler_incidents_id'] ?? 0;

        if ($inc_id > 0) {
            // Corretiva: criar tarefa no chamado do incidente original
            $inc = new PluginVehicleschedulerIncident();
            if ($inc->getFromDB($inc_id) && $inc->fields['tickets_id'] > 0) {
                $task = new TicketTask();
                $task->add([
                    'tickets_id' => $inc->fields['tickets_id'],
                    'content'    => sprintf(
                        "🔧 Manutenção Corretiva Agendada\nOficina: %s\nCusto Estimado: R$ %s\nData Agendada: %s\nDescrição: %s",
                        $this->fields['supplier'] ?? '',
                        $this->fields['cost'] ?? '0.00',
                        Html::convDateTime($this->fields['scheduled_date']),
                        $this->fields['description'] ?? ''
                    ),
                    'state'      => 1, // TODO (Planejado)
                    'actiontime' => 0
                ]);
            }
        } else {
            // Preventiva: criar chamado de requisição
            $this->createTicketFromMaintenance();
        }
    }

    function post_updateItem($history = true) {
        parent::post_updateItem($history);

        if (in_array('status', $this->updates)) {
            $inc_id = $this->fields['plugin_vehiclescheduler_incidents_id'] ?? 0;

            if ($inc_id > 0) {
                // Corretiva
                $inc = new PluginVehicleschedulerIncident();
                if ($inc->getFromDB($inc_id) && $inc->fields['tickets_id'] > 0) {
                    $ticket_id = $inc->fields['tickets_id'];
                    
                    if ($this->fields['status'] == self::STATUS_DONE) {
                        // 1. Concluir a tarefa no GLPI
                        global $DB;
                        $iterator = $DB->request([
                            'FROM'   => 'glpi_tickettasks',
                            'WHERE'  => [
                                'tickets_id' => $ticket_id,
                                'content'    => ['LIKE', '%Manutenção Corretiva Agendada%']
                            ],
                            'LIMIT'  => 1
                        ]);
                        if (count($iterator)) {
                            $row = $iterator->current();
                            $task = new TicketTask();
                            $task->update([
                                'id'    => $row['id'],
                                'state' => 2 // Done
                            ]);
                        }

                        // Buscar dados do veículo
                        $vehicle = new PluginVehicleschedulerVehicle();
                        $vname = '';
                        if ($vehicle->getFromDB($this->fields['plugin_vehiclescheduler_vehicles_id'])) {
                            $vname = $vehicle->fields['name'] . ' (' . $vehicle->fields['plate'] . ')';
                        }

                        // 2. Adicionar acompanhamento com custo final
                        $followup = new ITILFollowup();
                        $followup->add([
                            'itemtype'   => 'Ticket',
                            'items_id'   => $ticket_id,
                            'users_id'   => Session::getLoginUserID(),
                            'content'    => sprintf(
                                "✅ Manutenção Corretiva Concluída!\nVeículo: %s\nData de Conclusão: %s\nCusto Real: R$ %s\nOficina: %s\nDescrição: %s",
                                $vname,
                                Html::convDateTime($this->fields['completion_date']),
                                $this->fields['cost'] ?? '0.00',
                                $this->fields['supplier'] ?? '',
                                $this->fields['description'] ?? ''
                            ),
                            'is_private' => 0
                        ]);
                    }
                }
            } else {
                // Preventiva
                if ($this->fields['tickets_id'] > 0) {
                    $this->updatePreventiveTicketStatus();
                }
            }
        }
        return true;
    }

    /**
     * Cria chamado para Manutenção Preventiva
     */
    function createTicketFromMaintenance() {
        $vehicle = new PluginVehicleschedulerVehicle();
        $vname = '';
        if ($vehicle->getFromDB($this->fields['plugin_vehiclescheduler_vehicles_id'])) {
            $vname = $vehicle->fields['name'] . ' (' . $vehicle->fields['plate'] . ')';
        }

        $title = "Manutenção Preventiva de Veículo: {$vname}";
        $content = "Solicitação de Manutenção Preventiva:\n\n"
            . "Veículo: {$vname}\n"
            . "Data Agendada: " . Html::convDateTime($this->fields['scheduled_date']) . "\n"
            . "Oficina/Fornecedor: " . ($this->fields['supplier'] ?? '') . "\n"
            . "Custo Estimado: R$ " . ($this->fields['cost'] ?? '0.00') . "\n\n"
            . "Descrição:\n" . $this->fields['description'];

        $ticket = new Ticket();
        $ticket_id = $ticket->add([
            'name'                => $title,
            'content'             => $content,
            'entities_id'         => $this->fields['entities_id'],
            'type'                => Ticket::REQUEST_TYPE,
            'urgency'             => 3,
            'impact'              => 2,
            'priority'            => CommonITILObject::computePriority(3, 2),
            '_users_id_requester' => Session::getLoginUserID(),
        ]);

        if ($ticket_id) {
            global $DB;
            $DB->update(
                $this->getTable(),
                ['tickets_id' => $ticket_id],
                ['id' => $this->fields['id']]
            );
        }
    }

    /**
     * Atualiza o status do chamado da preventiva
     */
    function updatePreventiveTicketStatus() {
        $ticket = new Ticket();
        if (!$ticket->getFromDB($this->fields['tickets_id'])) {
            return false;
        }

        $statuses = self::getAllStatus();
        $status_label = $statuses[$this->fields['status']] ?? 'Desconhecido';

        $ticket_status_map = [
            self::STATUS_SCHEDULED   => CommonITILObject::INCOMING,
            self::STATUS_IN_PROGRESS => CommonITILObject::ASSIGNED,
            self::STATUS_DONE        => CommonITILObject::SOLVED,
            self::STATUS_CANCELLED   => CommonITILObject::CLOSED,
        ];

        $new_ticket_status = $ticket_status_map[$this->fields['status']] ?? $ticket->fields['status'];

        $messages = [
            self::STATUS_IN_PROGRESS => '⚙️ A manutenção preventiva foi iniciada pela oficina/técnico.',
            self::STATUS_DONE        => '✅ A manutenção preventiva foi concluída com sucesso.',
            self::STATUS_CANCELLED   => '❌ A manutenção preventiva foi cancelada.',
        ];

        if (isset($messages[$this->fields['status']])) {
            $followup = new ITILFollowup();
            $followup->add([
                'itemtype'   => 'Ticket',
                'items_id'   => $this->fields['tickets_id'],
                'users_id'   => Session::getLoginUserID(),
                'content'    => sprintf("Status da Manutenção Preventiva alterado para: %s\n\n%s", strtoupper($status_label), $messages[$this->fields['status']]),
                'is_private' => 0,
            ]);
        }

        $ticket->update(['id' => $this->fields['tickets_id'], 'status' => $new_ticket_status]);
        return true;
    }

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
        $tab[] = ['id' => '8', 'table' => $this->getTable(), 'field' => 'id',               'name' => 'ID', 'datatype' => 'integer'];
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'type') return self::getAllTypes()[$values[$field]] ?? '';
        if ($field === 'status')           return self::getAllStatus()[$values[$field]] ?? '';
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
