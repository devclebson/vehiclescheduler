<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Incident class — vehicle incidents, accidents, breakdowns
 * Replaces and expands the old VehicleReport concept.
 * Can originate Maintenance and/or Insurance Claims.
 */
if (!defined('GLPI_ROOT')) { die("Sorry. You can't access this file directly"); }

class PluginVehicleschedulerIncident extends CommonDBTM {

    public $dohistory = true;
    static $rightname = 'plugin_vehiclescheduler';

    const TYPE_ACCIDENT     = 1;
    const TYPE_BREAKDOWN    = 2;
    const TYPE_THEFT        = 3;
    const TYPE_DAMAGE       = 4;
    const TYPE_OBSERVATION  = 5;
    const TYPE_OTHER        = 6;

    const STATUS_OPEN       = 1;
    const STATUS_ANALYZING  = 2;
    const STATUS_RESOLVED   = 3;
    const STATUS_CLOSED     = 4;

    static function getTypeName($nb = 0) {
        return _n('Incident', 'Incidents', $nb, 'vehiclescheduler');
    }
    static function getMenuName() { return __('Incidents', 'vehiclescheduler'); }
    static function getIcon()     { return 'ti ti-alert-triangle'; }

    static function getMenuContent() {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) return false;
        $menu = [];
        $menu['title'] = self::getMenuName();
        $menu['page']  = '/plugins/vehiclescheduler/front/incident.php';
        $menu['icon']  = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/incident.php';
        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/incident.form.php';
        }
        $menu['options']['incident'] = [
            'title' => self::getTypeName(2),
            'page'  => '/plugins/vehiclescheduler/front/incident.php',
            'icon'  => self::getIcon(),
            'links' => ['search' => '/plugins/vehiclescheduler/front/incident.php',
                        'add'    => '/plugins/vehiclescheduler/front/incident.form.php'],
            'lists_itemtype' => 'PluginVehicleschedulerIncident',
        ];
        return $menu;
    }

    static function getAllTypes() {
        return [
            self::TYPE_ACCIDENT    => __('Acidente', 'vehiclescheduler'),
            self::TYPE_BREAKDOWN   => __('Pane/Falha Mecânica', 'vehiclescheduler'),
            self::TYPE_THEFT       => __('Furto/Roubo', 'vehiclescheduler'),
            self::TYPE_DAMAGE      => __('Avaria/Dano', 'vehiclescheduler'),
            self::TYPE_OBSERVATION => __('Observação', 'vehiclescheduler'),
            self::TYPE_OTHER       => __('Outros', 'vehiclescheduler'),
        ];
    }

    static function getAllStatus() {
        return [
            self::STATUS_OPEN      => __('Aberto', 'vehiclescheduler'),
            self::STATUS_ANALYZING => __('Em Análise', 'vehiclescheduler'),
            self::STATUS_RESOLVED  => __('Resolvido', 'vehiclescheduler'),
            self::STATUS_CLOSED    => __('Fechado', 'vehiclescheduler'),
        ];
    }

    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    function showForm($ID, array $options = []) {
        global $DB;
        $this->initForm($ID, $options);
        
        // Ativar multipart para upload de arquivos
        $options['multipart'] = true;
        $this->showFormHeader($options);

        // Definir valores padrão para auto-preenchimento
        $default_driver_id = 0;
        $default_phone = '';
        $default_dept = '';
        $default_vehicle_id = 0;

        if ($ID <= 0) {
            $user_id = Session::getLoginUserID();
            // Buscar perfil do motorista vinculado ao usuário logado
            $drv = PluginVehicleschedulerDriver::getDriverByUserId($user_id);
            if ($drv) {
                $default_driver_id = $drv['id'];
                $default_phone = $drv['contact_phone'];
                $default_dept = $drv['department'];
            }
            
            // Buscar reserva ativa nesse exato momento para preencher o veículo automaticamente
            $now = date('Y-m-d H:i:s');
            $res_iterator = $DB->request([
                'FROM'  => 'glpi_plugin_vehiclescheduler_schedules',
                'WHERE' => [
                    'users_id' => $user_id,
                    'status'   => [5, 2], // Em Viagem (5) ou Aprovada (2)
                    'begin_date' => ['<=', $now],
                    'end_date'   => ['>=', $now]
                ]
            ]);
            if (count($res_iterator) > 0) {
                $res_data = $res_iterator->current();
                $default_vehicle_id = $res_data['plugin_vehiclescheduler_vehicles_id'];
                $default_driver_id = $res_data['plugin_vehiclescheduler_drivers_id'];
            }
        }

        echo "<tr style='display:none;'><td></td></tr>";
        echo "<tr><td colspan='4' style='padding:0; border:none; background:transparent;'>";
        
        echo "<div class='container-fluid px-3 py-4'>";
        
        // Back Button
        echo "<div class='d-flex justify-content-end mb-3'>
                <a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'>
                    <i class='ti ti-arrow-left'></i> Voltar
                </a>
              </div>";

        // Exibir link do chamado se houver
        if ($ID > 0 && !empty($this->fields['tickets_id'])) {
            echo "<div class='alert alert-info d-flex align-items-center mb-4 border-0 shadow-sm'>
                    <i class='ti ti-ticket me-2 fs-4'></i>
                    <div>";
            echo "      <strong>Chamado Relacionado:</strong> ";
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->fields['tickets_id'])) {
                echo $ticket->getLink();
            }
            echo "  </div>
                  </div>";
        }

        $is_manager = PluginVehicleschedulerProfile::canViewManagement();
        $is_readonly = ($ID > 0 && !$is_manager);
        $disabled_attr = $is_readonly ? "disabled='disabled'" : "";

        // Card 1: Relatório de Incidente
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-danger fw-bold'><i class='ti ti-alert-triangle'></i> Relatório de Incidente</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";

        // Row 1: Requester / Department
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Solicitante <span class='text-danger'>*</span></label>";
        if ($is_manager) {
            echo "          <div>";
            User::dropdown(['name' => 'users_id', 'value' => $this->fields['users_id'] ?: Session::getLoginUserID(), 'right' => 'all']);
            echo "          </div>";
        } else {
            $u_id = $this->fields['users_id'] ?: Session::getLoginUserID();
            echo "          <input type='hidden' name='users_id' value='$u_id'>";
            echo "          <div class='form-control bg-light'>" . getUserName($u_id) . "</div>";
        }
        echo "          </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Departamento/Setor</label>
                            <input type='text' name='department' value='".htmlspecialchars($this->fields['department'] ?: $default_dept)."' class='form-control' $disabled_attr>
                        </div>";

        // Row 2: Vehicle / Driver
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Veículo Envolvido <span class='text-danger'>*</span></label>";
        if ($is_readonly) {
            $veh_name = 'Veículo não informado';
            $veh_id = $this->fields['plugin_vehiclescheduler_vehicles_id'] ?: $default_vehicle_id;
            if (!empty($veh_id)) {
                $veh = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_vehicles', 'WHERE' => ['id' => $veh_id]])->current();
                if ($veh) $veh_name = htmlspecialchars($veh['name'] . ' (' . $veh['plate'] . ')');
            }
            echo "          <div class='form-control bg-light'>$veh_name</div>";
        } else {
            echo "              <div>";
            PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'] ?: $default_vehicle_id]);
            echo "              </div>";
        }
        echo "          </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Motorista no Momento</label>";
        if ($is_readonly) {
            $drv_name = 'Motorista não informado';
            $drv_id = $this->fields['plugin_vehiclescheduler_drivers_id'] ?: $default_driver_id;
            if (!empty($drv_id)) {
                $drv = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_drivers', 'WHERE' => ['id' => $drv_id]])->current();
                if ($drv) $drv_name = htmlspecialchars($drv['name']);
            }
            echo "          <div class='form-control bg-light'>$drv_name</div>";
        } else {
            echo "              <div>";
            PluginVehicleschedulerDriver::dropdown(['name' => 'plugin_vehiclescheduler_drivers_id', 'value' => $this->fields['plugin_vehiclescheduler_drivers_id'] ?: $default_driver_id]);
            echo "              </div>";
        }
        echo "          </div>";

        // Row 3: Type / Date / Status (for common users viewing an existing incident)
        $col_width = ($ID > 0 && !$is_manager) ? 'col-md-4' : 'col-md-6';
        echo "          <div class='$col_width'>
                            <label class='form-label text-muted fw-bold'>Tipo de Incidente <span class='text-danger'>*</span></label>";
        if ($is_readonly) {
            $type_label = self::getAllTypes()[$this->fields['incident_type'] ?? self::TYPE_OTHER] ?? 'Outros';
            echo "          <div class='form-control bg-light'>$type_label</div>";
        } else {
            echo "              <div>";
            Dropdown::showFromArray('incident_type', self::getAllTypes(), ['value' => $this->fields['incident_type'] ?? self::TYPE_OTHER]);
            echo "              </div>";
        }
        echo "          </div>";

        echo "          <div class='$col_width'>
                            <label class='form-label text-muted fw-bold'>Data e Hora <span class='text-danger'>*</span></label>";
        if ($is_readonly) {
            $inc_date = Html::convDateTime($this->fields['incident_date'] ?? date('Y-m-d H:i:s'));
            echo "          <div class='form-control bg-light'>$inc_date</div>";
        } else {
            echo "              <div>";
            Html::showDateTimeField('incident_date', ['value' => $this->fields['incident_date'] ?? date('Y-m-d H:i:s')]);
            echo "              </div>";
        }
        echo "          </div>";

        if ($ID > 0 && !$is_manager) {
            echo "          <div class='col-md-4'>
                                <label class='form-label text-muted fw-bold'>Status do Incidente</label>";
            $status_label = self::getAllStatus()[$this->fields['status'] ?? self::STATUS_OPEN] ?? 'Aberto';
            $status_colors = [
                self::STATUS_OPEN => 'vs-badge-blue',
                self::STATUS_ANALYZING => 'vs-badge-yellow',
                self::STATUS_RESOLVED => 'vs-badge-green',
                self::STATUS_CLOSED => 'vs-badge-gray'
            ];
            $status_class = $status_colors[$this->fields['status'] ?? self::STATUS_OPEN] ?? 'vs-badge-blue';
            echo "              <div class='form-control bg-light'><span class='vs-badge $status_class'>$status_label</span></div>";
            echo "          </div>";
        }

        // Row 4: Location / Contact Phone
        echo "          <div class='col-md-8'>
                            <label class='form-label text-muted fw-bold'>Localização/Endereço</label>
                            <input type='text' name='location' value='".htmlspecialchars($this->fields['location'] ?? '')."' placeholder='Onde aconteceu?' class='form-control' $disabled_attr>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Telefone de Contato</label>
                            <input type='text' name='contact_phone' value='".htmlspecialchars($this->fields['contact_phone'] ?: $default_phone)."' class='form-control' $disabled_attr>
                        </div>";

        // Description (Full width)
        echo "          <div class='col-12'>
                            <label class='form-label text-muted fw-bold'>Descrição do Ocorrido <span class='text-danger'>*</span></label>
                            <textarea name='description' rows='5' class='form-control' placeholder='Descreva detalhadamente o que ocorreu' $disabled_attr>".htmlspecialchars($this->fields['description'] ?? '')."</textarea>
                        </div>";

        // Fotos / Anexos
        echo "          <div class='col-12'>
                            <label class='form-label text-muted fw-bold'>Fotos / Anexos do Ocorrido</label>";
        if ($is_readonly) {
            $attachments = $DB->request([
                'SELECT' => ['d.id', 'd.name', 'd.filename'],
                'FROM'   => 'glpi_documents_items AS di',
                'INNER JOIN' => [
                    'glpi_documents AS d' => [
                        'FKEY' => [
                            'di' => 'documents_id',
                            'd'  => 'id'
                        ]
                    ]
                ],
                'WHERE' => [
                    'di.itemtype' => self::class,
                    'di.items_id' => $ID
                ]
            ]);
            
            if (count($attachments) > 0) {
                echo "<div class='list-group mt-2'>";
                foreach ($attachments as $att) {
                    $doc_obj = new Document();
                    if ($doc_obj->getFromDB($att['id'])) {
                        echo "<div class='list-group-item d-flex justify-content-between align-items-center bg-light' style='padding: 8px 12px; border: 1px solid var(--vs-border); border-radius: 4px; margin-bottom: 4px;'>";
                        echo "<span><i class='ti ti-file-text me-2'></i>" . htmlspecialchars($att['name'] ?: $att['filename']) . "</span>";
                        echo $doc_obj->getLink();
                        echo "</div>";
                    }
                }
                echo "</div>";
            } else {
                echo "<div class='form-control bg-light text-muted'>Nenhum arquivo anexado</div>";
            }
        } else {
            echo "              <div>";
            Html::file(['name' => 'filename']);
            echo "              </div>";
        }
        echo "          </div>";

        echo "      </div>
                </div>
              </div>";

        // Card 2: Gestão de Status (Only managers and edit mode)
        if ($ID > 0 && $is_manager) {
            echo "<div class='card shadow-sm border-0 mb-4'>
                    <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                        <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-settings'></i> Análise e Gestão do Incidente</h5>
                    </div>
                    <div class='card-body'>
                        <div class='row g-4'>";

            echo "          <div class='col-md-4'>
                                <label class='form-label text-muted fw-bold'>Status do Incidente</label>";
            echo "              <div>";
            Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status'] ?? self::STATUS_OPEN]);
            echo "              </div>
                            </div>";

            echo "          <div class='col-md-4'>
                                <label class='form-label text-muted fw-bold'>Necessita Manutenção?</label>";
            echo "              <div>";
            Dropdown::showYesNo('needs_maintenance', $this->fields['needs_maintenance'] ?? 0);
            echo "              </div>
                            </div>";

            echo "          <div class='col-md-4'>
                                <label class='form-label text-muted fw-bold'>Acionar Seguro?</label>";
            echo "              <div>";
            Dropdown::showYesNo('needs_insurance', $this->fields['needs_insurance'] ?? 0);
            echo "              </div>
                            </div>";

            echo "      </div>
                    </div>
                  </div>";
        }

        // Linked records
        if ($ID > 0) {
            global $DB;
            $maint_count = countElementsInTable('glpi_plugin_vehiclescheduler_maintenances', ['plugin_vehiclescheduler_incidents_id' => $ID]);
            $claim_count = countElementsInTable('glpi_plugin_vehiclescheduler_insuranceclaims', ['plugin_vehiclescheduler_incidents_id' => $ID]);
            
            if ($maint_count || $claim_count) {
                echo "<div class='alert alert-info d-flex align-items-center mb-4 border-0 shadow-sm'>
                        <i class='ti ti-link me-2 fs-4'></i>
                        <div>
                            <strong>Registros Vinculados:</strong> ";
                if ($maint_count) echo "<a href='/plugins/vehiclescheduler/front/maintenance.php?plugin_vehiclescheduler_incidents_id=$ID' class='alert-link'>$maint_count manutenção(ões)</a> ";
                if ($maint_count && $claim_count) echo " | ";
                if ($claim_count) echo "<a href='/plugins/vehiclescheduler/front/insuranceclaim.php?plugin_vehiclescheduler_incidents_id=$ID' class='alert-link'>$claim_count sinistro(s)</a>";
                echo "          </div>
                      </div>";
            }

            if ($is_manager) {
                // Quick-action buttons
                $vid = $this->fields['plugin_vehiclescheduler_vehicles_id'];
                echo "<div class='d-flex gap-2 mb-4'>
                        <a href='/plugins/vehiclescheduler/front/maintenance.form.php?plugin_vehiclescheduler_vehicles_id=$vid&plugin_vehiclescheduler_incidents_id=$ID&type=2' class='btn btn-outline-danger'><i class='ti ti-tool'></i> Criar Manutenção Corretiva</a>
                        <a href='/plugins/vehiclescheduler/front/insuranceclaim.form.php?plugin_vehiclescheduler_vehicles_id=$vid&plugin_vehiclescheduler_incidents_id=$ID' class='btn btn-outline-primary'><i class='ti ti-shield'></i> Abrir Sinistro</a>
                      </div>";
            }
        }

        echo "</div>"; // Container End
        echo "</td></tr>";

        if (!$is_readonly) {
            $this->showFormButtons($options);
        } else {
            Html::closeForm();
        }
        return true;
    }

    function prepareInputForAdd($input) {
        if (empty($input['plugin_vehiclescheduler_vehicles_id'])) {
            Session::addMessageAfterRedirect(__('Veículo é obrigatório.', 'vehiclescheduler'), false, ERROR);
            return false;
        }
        if (empty($input['description'])) {
            Session::addMessageAfterRedirect(__('Descrição é obrigatória.', 'vehiclescheduler'), false, ERROR);
            return false;
        }
        // Auto-generate name from incident type and date
        if (empty($input['name'])) {
            $type_label = self::getAllTypes()[$input['incident_type'] ?? self::TYPE_OTHER] ?? __('Incidente', 'vehiclescheduler');
            $input['name'] = $type_label . ' — ' . date('d/m/Y');
        }
        if (!isset($input['status']))       $input['status'] = self::STATUS_OPEN;
        if (!isset($input['entities_id'])) $input['entities_id'] = $_SESSION['glpiactive_entity'];
        if (!isset($input['users_id']))    $input['users_id'] = Session::getLoginUserID();
        if (!isset($input['incident_date'])) $input['incident_date'] = date('Y-m-d H:i:s');
        return $input;
    }

    /**
     * Hook após adicionar - criar chamado automaticamente
     */
    function post_addItem() {
        parent::post_addItem();
        
        // 1. Processar anexos de arquivos
        if (isset($_FILES['filename']) && $_FILES['filename']['error'] == UPLOAD_ERR_OK) {
            $doc = new Document();
            $doc_id = $doc->add([
                'name'        => 'Anexo de Incidente — ' . $this->fields['name'],
                'entities_id' => $this->fields['entities_id'],
                '_uploader'   => ['filename']
            ]);
            if ($doc_id) {
                $doc_item = new Document_Item();
                $doc_item->add([
                    'documents_id' => $doc_id,
                    'itemtype'     => self::class,
                    'items_id'     => $this->fields['id'],
                    'entities_id'  => $this->fields['entities_id']
                ]);
            }
        }
        
        // 2. Criar chamado no GLPI
        $this->createTicketFromIncident();
    }

    function post_updateItem($history = true) {
        parent::post_updateItem($history);
        
        if (in_array('status', $this->updates) && $this->fields['tickets_id'] > 0) {
            $this->updateTicketStatus();
        }
        return true;
    }

    /**
     * Cria chamado automaticamente para incidente
     */
    function createTicketFromIncident() {
        // Buscar dados do veículo
        $vehicle = new PluginVehicleschedulerVehicle();
        $vname = '';
        if ($vehicle->getFromDB($this->fields['plugin_vehiclescheduler_vehicles_id'])) {
            $vname = $vehicle->fields['name'] . ' (' . $vehicle->fields['plate'] . ')';
        }

        $types = self::getAllTypes();
        $type_label = $types[$this->fields['incident_type']] ?? 'Incidente';

        $title = "Incidente com Veículo: {$vname} — {$type_label}";

        $content = "Reporte de Incidente:\n\n"
            . "Tipo: {$type_label}\n"
            . "Veículo: {$vname}\n"
            . "Data: " . Html::convDateTime($this->fields['incident_date']) . "\n"
            . "Local: " . $this->fields['location'] . "\n"
            . "Relatado por: " . getUserName($this->fields['users_id']) . "\n"
            . "Departamento: " . $this->fields['department'] . "\n"
            . "Telefone: " . $this->fields['contact_phone'] . "\n\n"
            . "Descrição:\n" . $this->fields['description'];

        $ticket = new Ticket();
        $ticket_id = $ticket->add([
            'name'                => $title,
            'content'             => $content,
            'entities_id'         => $this->fields['entities_id'],
            'type'                => Ticket::INCIDENT_TYPE,
            'urgency'             => 4, // Alta urgência para incidentes
            'impact'              => 3,
            'priority'            => CommonITILObject::computePriority(4, 3),
            '_users_id_requester' => $this->fields['users_id'],
        ]);

        if ($ticket_id) {
            $this->update([
                'id'         => $this->fields['id'],
                'tickets_id' => $ticket_id
            ]);
        }

        return $ticket_id;
    }

    function updateTicketStatus() {
        $ticket = new Ticket();
        if (!$ticket->getFromDB($this->fields['tickets_id'])) {
            return false;
        }

        $statuses = self::getAllStatus();
        $status_label = $statuses[$this->fields['status']] ?? 'Desconhecido';

        $ticket_status_map = [
            self::STATUS_OPEN      => CommonITILObject::INCOMING,  // Aberto -> Novo
            self::STATUS_ANALYZING => CommonITILObject::ASSIGNED,  // Em Análise -> Atribuído
            self::STATUS_RESOLVED  => CommonITILObject::SOLVED,    // Resolvido -> Solucionado
            self::STATUS_CLOSED    => CommonITILObject::CLOSED,    // Fechado -> Fechado
        ];

        $new_ticket_status = $ticket_status_map[$this->fields['status']] ?? $ticket->fields['status'];

        $messages = [
            self::STATUS_ANALYZING => '🔍 O incidente está sob análise pela gestão de frota.',
            self::STATUS_RESOLVED  => '✅ O incidente foi RESOLVIDO pela equipe de frotas.',
            self::STATUS_CLOSED    => '🔒 O processo do incidente foi concluído e encerrado.',
        ];

        if (isset($messages[$this->fields['status']])) {
            $followup = new ITILFollowup();
            $followup->add([
                'itemtype'   => 'Ticket',
                'items_id'   => $this->fields['tickets_id'],
                'users_id'   => Session::getLoginUserID(),
                'content'    => sprintf("Status do Incidente alterado para: %s\n\n%s", strtoupper($status_label), $messages[$this->fields['status']]),
                'is_private' => 0,
            ]);
        }

        $ticket->update(['id' => $this->fields['tickets_id'], 'status' => $new_ticket_status]);
        return true;
    }

    function rawSearchOptions() {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => self::getTypeName(2)];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'name',          'name' => __('Título', 'vehiclescheduler'), 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => 'glpi_plugin_vehiclescheduler_vehicles', 'field' => 'name', 'name' => __('Veículo', 'vehiclescheduler'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'incident_type', 'name' => __('Tipo', 'vehiclescheduler'), 'datatype' => 'specific', 'searchtype' => ['equals']];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'status',        'name' => __('Status'), 'datatype' => 'specific', 'searchtype' => ['equals']];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'incident_date', 'name' => __('Data', 'vehiclescheduler'), 'datatype' => 'datetime'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'department',    'name' => __('Departamento', 'vehiclescheduler'), 'datatype' => 'string'];
        $tab[] = ['id' => '8', 'table' => 'glpi_tickets', 'field' => 'name', 'name' => 'Chamado Relacionado', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'id',            'name' => 'ID', 'datatype' => 'integer'];
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'incident_type') return self::getAllTypes()[$values[$field]] ?? '';
        if ($field === 'status')        return self::getAllStatus()[$values[$field]] ?? '';
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
