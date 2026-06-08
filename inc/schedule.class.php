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
    const STATUS_ONGOING   = 5;
    const STATUS_RETURNED  = 6;

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
            self::STATUS_ONGOING   => 'Em Viagem',
            self::STATUS_RETURNED  => 'Devolvido',
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
        $menu['links']['<i class="ti ti-home"></i>']             = '/plugins/vehiclescheduler/front/dashboards/portal.php';
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

        $is_manager = PluginVehicleschedulerProfile::canViewManagement();
        $disabled = ($ID > 0 && !$is_manager) ? "disabled='disabled'" : "";

        // Card 1: Detalhes da Solicitação
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-calendar-event'></i> Solicitação de Reserva</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";

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
                            <label class='form-label text-muted fw-bold'>Departamento/Setor <span class='text-danger'>*</span></label>
                            <input type='text' name='department' value='".htmlspecialchars($this->fields['department'] ?? '')."' class='form-control' $disabled>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Telefone para Contato <span class='text-danger'>*</span></label>
                            <input type='text' name='contact_phone' value='".htmlspecialchars($this->fields['contact_phone'] ?? '')."' class='form-control' $disabled>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Veículo Solicitado <span class='text-danger'>*</span></label>";
        if ($ID > 0 && !$is_manager) {
            $v_id = $this->fields['plugin_vehiclescheduler_vehicles_id'];
            echo "          <input type='hidden' name='plugin_vehiclescheduler_vehicles_id' value='$v_id'>";
            echo "          <div class='form-control bg-light'>" . Dropdown::getDropdownName('glpi_plugin_vehiclescheduler_vehicles', $v_id) . "</div>";
        } else {
            echo "          <div>";
            PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'], 'entity' => $this->fields['entities_id']]);
            echo "          </div>";
        }
        echo "          </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Data/Hora de Saída <span class='text-danger'>*</span></label>
                            <div>";
        if ($ID > 0 && !$is_manager) {
            echo "          <input type='hidden' name='begin_date' value='{$this->fields['begin_date']}'>";
            echo "          <div class='form-control bg-light'>" . Html::convDateTime($this->fields['begin_date']) . "</div>";
        } else {
            Html::showDateTimeField('begin_date', ['value' => $this->fields['begin_date']]);
        }
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Data/Hora de Retorno <span class='text-danger'>*</span></label>
                            <div>";
        if ($ID > 0 && !$is_manager) {
            echo "          <input type='hidden' name='end_date' value='{$this->fields['end_date']}'>";
            echo "          <div class='form-control bg-light'>" . Html::convDateTime($this->fields['end_date']) . "</div>";
        } else {
            Html::showDateTimeField('end_date', ['value' => $this->fields['end_date']]);
        }
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-8'>
                            <label class='form-label text-muted fw-bold'>Destino <span class='text-danger'>*</span></label>
                            <input type='text' name='destination' value='".htmlspecialchars($this->fields['destination'] ?? '')."' class='form-control' $disabled>
                        </div>";
        
        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Nº de Passageiros</label>
                            <input type='number' name='passengers' value='".($this->fields['passengers'] ?: 1)."' min='1' class='form-control' $disabled>
                        </div>";

        echo "          <div class='col-12'>
                            <label class='form-label text-muted fw-bold'>Descrição / Finalidade <span class='text-danger'>*</span></label>
                            <textarea name='purpose' rows='3' class='form-control' placeholder='Descreva a finalidade desta reserva' $disabled>".htmlspecialchars($this->fields['purpose'] ?? '')."</textarea>
                        </div>";

        echo "      </div>
                </div>
              </div>";

        // Card 2: Gestão
        if ($is_manager || (!empty($this->fields['plugin_vehiclescheduler_drivers_id']) && $this->fields['plugin_vehiclescheduler_drivers_id'] > 0)) {
            echo "<div class='card shadow-sm border-0 mb-4'>
                    <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                        <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-steering-wheel'></i> Gestão da Reserva</h5>
                    </div>
                    <div class='card-body'>
                        <div class='row g-4'>";
            
            if ($is_manager) {
                echo "      <div class='col-md-6'>
                                <label class='form-label text-muted fw-bold'>Motorista Designado</label>
                                <div>";
                PluginVehicleschedulerDriver::dropdown(['name' => 'plugin_vehiclescheduler_drivers_id', 'value' => $this->fields['plugin_vehiclescheduler_drivers_id'] ?? 0]);
                echo "          </div>
                            </div>";
                
                if ($ID > 0 && Session::haveRight('plugin_vehiclescheduler', UPDATE)) {
                    echo "  <div class='col-md-6'>
                                <label class='form-label text-muted fw-bold'>Status</label>
                                <div>";
                    Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status']]);
                    echo "          </div>
                            </div>";
                }
            } else {
                echo "      <div class='col-md-6'>
                                <label class='form-label text-muted fw-bold'>Motorista Designado</label>
                                <div class='form-control bg-light'>".Dropdown::getDropdownName('glpi_plugin_vehiclescheduler_drivers', $this->fields['plugin_vehiclescheduler_drivers_id'])."</div>
                            </div>";
                // Show status as read-only badge/alert
                echo "      <div class='col-md-6'>
                                <label class='form-label text-muted fw-bold'>Status</label>
                                <div class='form-control bg-light'>" . (self::getAllStatus()[$this->fields['status']] ?? 'Desconhecido') . "</div>
                            </div>";
            }

            $comment_readonly = !$is_manager ? "readonly='readonly'" : "";
            echo "          <div class='col-12'>
                                <label class='form-label text-muted fw-bold'>Comentários Adicionais</label>
                                <textarea name='comment' rows='2' class='form-control' placeholder='Anotações do gestor' $comment_readonly>".htmlspecialchars($this->fields['comment'] ?? '')."</textarea>
                            </div>";

            echo "      </div>
                    </div>
                  </div>";
        }

        // Se a reserva estiver em viagem ou já retornou, mostrar informações de devolução/checklist
        if (!empty($this->fields['real_begin_date'])) {
            $fuel_labels = [1 => '1/4', 2 => '2/4', 3 => '3/4', 4 => '4/4 (Cheio)'];
            
            echo "<div class='card shadow-sm border-0 mb-4' style='border-left: 4px solid #3b82f6 !important;'>
                    <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                        <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-steering-wheel'></i> Registro de Viagem</h5>
                    </div>
                    <div class='card-body'>
                        <div class='row g-4'>
                            <div class='col-md-6'>
                                <label class='form-label text-muted fw-bold'>Retirada (Check-out)</label>
                                <div class='form-control bg-light'>
                                    <strong>Data/Hora:</strong> " . Html::convDateTime($this->fields['real_begin_date']) . "<br>
                                    <strong>Odômetro Inicial:</strong> " . $this->fields['initial_mileage'] . " km<br>
                                    <strong>Nível Combustível:</strong> " . ($fuel_labels[$this->fields['initial_fuel']] ?? 'Não informado') . "
                                </div>
                            </div>";
                            
            if (!empty($this->fields['real_end_date'])) {
                $chk = json_decode($this->fields['return_checklist'] ?? '{}', true);
                $clean_txt = (isset($chk['clean']) && $chk['clean'] == '1') ? 'Sim' : 'Não';
                $damage_txt = (isset($chk['damage']) && $chk['damage'] == '1') ? 'Sim' : 'Não';
                
                echo "<div class='col-md-6'>
                        <label class='form-label text-muted fw-bold'>Devolução (Check-in)</label>
                        <div class='form-control bg-light'>
                            <strong>Data/Hora:</strong> " . Html::convDateTime($this->fields['real_end_date']) . "<br>
                            <strong>Odômetro Final:</strong> " . $this->fields['final_mileage'] . " km (Total rodado: " . ($this->fields['final_mileage'] - $this->fields['initial_mileage']) . " km)<br>
                            <strong>Nível Combustível:</strong> " . ($fuel_labels[$this->fields['final_fuel']] ?? 'Não informado') . "<br>
                            <strong>Veículo Limpo:</strong> " . $clean_txt . "<br>
                            <strong>Com Avaria:</strong> " . $damage_txt . "<br>
                            <strong>Observações:</strong> " . htmlspecialchars($this->fields['return_comment'] ?? '') . "
                        </div>
                      </div>";
            }
            
            echo "      </div>
                    </div>
                  </div>";
        }

        // Botões de Viagem (Retirada / Devolução)
        if ($ID > 0) {
            $is_driver = false;
            $active_driver = PluginVehicleschedulerDriver::getActiveDriverByUserId(Session::getLoginUserID());
            if ($active_driver && $this->fields['plugin_vehiclescheduler_drivers_id'] == $active_driver['id']) {
                $is_driver = true;
            }
            // Se for gestor ou o motorista designado
            if ($is_manager || $is_driver || $this->fields['users_id'] == Session::getLoginUserID()) {
                if ($this->fields['status'] == self::STATUS_APPROVED) {
                    $v_mileage = 0;
                    if ($this->fields['plugin_vehiclescheduler_vehicles_id'] > 0) {
                        $v = new PluginVehicleschedulerVehicle();
                        if ($v->getFromDB($this->fields['plugin_vehiclescheduler_vehicles_id'])) {
                            $v_mileage = (int)$v->fields['mileage'];
                        }
                    }
                    
                    echo "<div class='text-center my-4'>
                            <button type='button' class='btn btn-lg btn-success shadow-sm fw-bold px-4' data-bs-toggle='modal' data-bs-target='#startTripModal'>
                                <i class='ti ti-steering-wheel fs-4 me-2'></i> Iniciar Viagem (Retirar Chave)
                            </button>
                          </div>";
                          
                    echo "
                    <div class='modal fade' id='startTripModal' tabindex='-1' aria-labelledby='startTripModalLabel' aria-hidden='true'>
                      <div class='modal-dialog'>
                        <div class='modal-content border-0 shadow-lg'>
                          <div class='modal-header bg-success text-white'>
                            <h5 class='modal-title fw-bold' id='startTripModalLabel'><i class='ti ti-steering-wheel me-2'></i> Iniciar Viagem</h5>
                            <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
                          </div>
                          <form method='post' action='schedule.form.php'>
                            <input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>
                            <div class='modal-body'>
                              <input type='hidden' name='id' value='{$this->fields['id']}'>
                              <div class='mb-3'>
                                <label class='form-label fw-bold'>Odômetro Inicial (km) <span class='text-danger'>*</span></label>
                                <input type='number' name='initial_mileage' value='{$v_mileage}' class='form-control' required min='0'>
                              </div>
                              <div class='mb-3'>
                                <label class='form-label fw-bold'>Nível de Combustível Inicial <span class='text-danger'>*</span></label>
                                <select name='initial_fuel' class='form-select' required>
                                  <option value='4'>4/4 (Cheio)</option>
                                  <option value='3'>3/4</option>
                                  <option value='2'>2/4</option>
                                  <option value='1'>1/4</option>
                                </select>
                              </div>
                            </div>
                            <div class='modal-footer bg-light'>
                              <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button>
                              <button type='submit' name='start_trip' class='btn btn-success fw-bold'>Confirmar Retirada</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>";
                }
                
                if ($this->fields['status'] == self::STATUS_ONGOING) {
                    $init_mileage = (int)$this->fields['initial_mileage'];
                    echo "<div class='text-center my-4'>
                            <button type='button' class='btn btn-lg btn-danger shadow-sm fw-bold px-4' data-bs-toggle='modal' data-bs-target='#endTripModal'>
                                <i class='ti ti-flag fs-4 me-2'></i> Concluir Viagem (Devolver Veículo)
                            </button>
                          </div>";
                          
                    echo "
                    <div class='modal fade' id='endTripModal' tabindex='-1' aria-labelledby='endTripModalLabel' aria-hidden='true'>
                      <div class='modal-dialog'>
                        <div class='modal-content border-0 shadow-lg'>
                          <div class='modal-header bg-danger text-white'>
                            <h5 class='modal-title fw-bold' id='endTripModalLabel'><i class='ti ti-flag me-2'></i> Devolução do Veículo</h5>
                            <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>
                          </div>
                          <form method='post' action='schedule.form.php'>
                            <input type='hidden' name='_glpi_csrf_token' value='" . Session::getNewCSRFToken() . "'>
                            <div class='modal-body'>
                              <input type='hidden' name='id' value='{$this->fields['id']}'>
                              <div class='mb-3'>
                                <label class='form-label fw-bold'>Odômetro Final (km) <span class='text-danger'>*</span></label>
                                <input type='number' name='final_mileage' value='{$init_mileage}' class='form-control' required min='{$init_mileage}'>
                                <div class='form-text'>Deve ser maior ou igual a {$init_mileage} km.</div>
                              </div>
                              <div class='mb-3'>
                                <label class='form-label fw-bold'>Nível de Combustível Final <span class='text-danger'>*</span></label>
                                <select name='final_fuel' class='form-select' required>
                                  <option value='4'>4/4 (Cheio)</option>
                                  <option value='3'>3/4</option>
                                  <option value='2'>2/4</option>
                                  <option value='1'>1/4</option>
                                </select>
                              </div>
                              
                              <div class='card bg-light border-0 mb-3'>
                                <div class='card-body py-3'>
                                  <h6 class='fw-bold mb-3'><i class='ti ti-checkbox'></i> Checklist de Devolução</h6>
                                  <div class='form-check mb-2'>
                                    <input class='form-check-input' type='checkbox' name='return_checklist[clean]' value='1' id='chkClean' checked>
                                    <label class='form-check-label' for='chkClean'>
                                      O veículo está limpo por dentro e por fora?
                                    </label>
                                  </div>
                                  <div class='form-check'>
                                    <input class='form-check-input' type='checkbox' name='return_checklist[damage]' value='1' id='chkDamage'>
                                    <label class='form-check-label text-danger fw-bold' for='chkDamage'>
                                      Houve alguma avaria / problema durante a viagem?
                                    </label>
                                  </div>
                                </div>
                              </div>
                              
                              <div class='mb-3'>
                                <label class='form-label fw-bold'>Comentários / Observações</label>
                                <textarea name='return_comment' class='form-control' rows='2' placeholder='Descreva avarias ou observações gerais'></textarea>
                              </div>
                            </div>
                            <div class='modal-footer bg-light'>
                              <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancelar</button>
                              <button type='submit' name='end_trip' class='btn btn-danger fw-bold'>Confirmar Devolução</button>
                            </div>
                          </form>
                        </div>
                      </div>
                    </div>";
                }
            }
        }

        // Ticket Relation
        if (!empty($this->fields['tickets_id']) && $this->fields['tickets_id'] > 0) {
            echo "<div class='alert alert-info d-flex align-items-center mb-4 border-0 shadow-sm'>
                    <i class='ti ti-ticket me-2 fs-4 text-info'></i>
                    <div>";
            echo "      <strong>Chamado Relacionado:</strong> ";
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->fields['tickets_id'])) {
                echo $ticket->getLink();
            }
            echo "  </div>
                  </div>";
        }

        echo "</div>"; // Container End
        echo "</td></tr>";

        // Inject JS to enforce Flatpickr date rules
        echo "<script>
        $(document).ready(function() {
            var begin_input = $('input[name=\"begin_date\"]');
            var end_input = $('input[name=\"end_date\"]');
            
            function setupFlatpickr() {
                var begin_wrapper = begin_input.closest('.flatpickr');
                var end_wrapper = end_input.closest('.flatpickr');
                
                if (begin_wrapper.length && begin_wrapper[0]._flatpickr) {
                    var begin_picker = begin_wrapper[0]._flatpickr;
                    var end_picker = end_wrapper.length ? end_wrapper[0]._flatpickr : null;
                    
                    var isNew = " . (($ID > 0) ? 'false' : 'true') . ";
                    if (isNew) {
                        begin_picker.set('minDate', new Date());
                    }
                    
                    begin_input.on('change', function() {
                        var val = $(this).val();
                        if (val && end_picker) {
                            end_picker.set('minDate', val);
                        }
                    });
                    
                    var initial_val = begin_input.val();
                    if (initial_val && end_picker) {
                        end_picker.set('minDate', initial_val);
                    }
                } else {
                    setTimeout(setupFlatpickr, 100);
                }
            }
            setupFlatpickr();
        });
        </script>";
        
        if ($ID == 0 || $is_manager) {
            $this->showFormButtons($options);
        } else {
            echo "<tr style='display:none;'><td></td></tr>";
            if ($this->fields['status'] == self::STATUS_NEW) {
                echo "<div class='d-flex justify-content-center gap-3 mb-4'>";
                echo "<button type='submit' name='delete' class='btn btn-danger shadow-sm' onclick='return confirm(\"Deseja realmente cancelar esta reserva?\")'><i class='ti ti-trash me-2'></i> Cancelar Reserva</button>";
                echo "</div>";
            }
            Html::closeForm();
        }
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

        // Validação de datas
        if (empty($input['begin_date'])) {
            Session::addMessageAfterRedirect('A data de saída é obrigatória.', false, ERROR);
            return false;
        }
        if (empty($input['end_date'])) {
            Session::addMessageAfterRedirect('A data de retorno é obrigatória.', false, ERROR);
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $begin = date('Y-m-d H:i:s', strtotime($input['begin_date']));
        $end = date('Y-m-d H:i:s', strtotime($input['end_date']));

        if ($begin < $now) {
            Session::addMessageAfterRedirect('A data de saída não pode ser no passado.', false, ERROR);
            return false;
        }
        if ($end < $begin) {
            Session::addMessageAfterRedirect('A data de retorno não pode ser anterior à data de saída.', false, ERROR);
            return false;
        }

        // Se for condutor comum, o motorista é ele mesmo
        if (!PluginVehicleschedulerProfile::canViewManagement()) {
            $active_driver = PluginVehicleschedulerDriver::getActiveDriverByUserId(Session::getLoginUserID());
            if ($active_driver) {
                $input['plugin_vehiclescheduler_drivers_id'] = $active_driver['id'];
            }
        }

        return $input;
    }

    function prepareInputForUpdate($input) {
        // Se for uma atualização de início ou fim de viagem, permitir para condutores comuns
        if (isset($input['_bypass_driver_check']) && $input['_bypass_driver_check']) {
            return $input;
        }

        $is_manager = PluginVehicleschedulerProfile::canViewManagement();
        
        // Bloquear alterações de condutores comuns que não sejam as ações de viagem
        // (as ações de viagem rodam métodos específicos startTrip/endTrip, não o standard update)
        if (!$is_manager) {
            Session::addMessageAfterRedirect('Você não tem permissão para editar agendamentos.', false, ERROR);
            return false;
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

        // Validação de datas
        if (isset($input['begin_date'])) {
            $begin = date('Y-m-d H:i:s', strtotime($input['begin_date']));
            if (isset($this->fields['begin_date']) && $this->fields['begin_date'] !== $input['begin_date']) {
                $now = date('Y-m-d H:i:s');
                if ($begin < $now) {
                    Session::addMessageAfterRedirect('A data de saída não pode ser no passado.', false, ERROR);
                    return false;
                }
            }
            
            $end_date = $input['end_date'] ?? $this->fields['end_date'] ?? '';
            if (!empty($end_date)) {
                $end = date('Y-m-d H:i:s', strtotime($end_date));
                if ($end < $begin) {
                    Session::addMessageAfterRedirect('A data de retorno não pode ser anterior à data de saída.', false, ERROR);
                    return false;
                }
            }
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
        $tab[] = ['id' => '17', 'table' => $this->getTable(), 'field' => 'id',
                  'name' => 'ID', 'datatype' => 'integer'];
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

    /**
     * Inicia a viagem (Check-out)
     */
    function startTrip($input) {
        $id = (int)$input['id'];
        if (!$this->getFromDB($id)) {
            return false;
        }
        
        $now = date('Y-m-d H:i:s');
        $initial_mileage = (int)$input['initial_mileage'];
        $initial_fuel = (int)$input['initial_fuel'];
        
        // Update database record
        $this->update([
            'id'                   => $id,
            'real_begin_date'      => $now,
            'initial_mileage'      => $initial_mileage,
            'initial_fuel'         => $initial_fuel,
            'status'               => self::STATUS_ONGOING,
            '_bypass_driver_check' => true
        ]);
        
        // Add followup to ticket and assign ticket
        if ($this->fields['tickets_id'] > 0) {
            $fuel_labels = [1 => '1/4', 2 => '2/4', 3 => '3/4', 4 => '4/4 (Cheio)'];
            $fuel_txt = $fuel_labels[$initial_fuel] ?? 'Não informado';
            
            $followup = new ITILFollowup();
            $followup->add([
                'itemtype'   => 'Ticket',
                'items_id'   => $this->fields['tickets_id'],
                'users_id'   => Session::getLoginUserID(),
                'content'    => sprintf("🚗 Viagem iniciada! Chave retirada com odômetro de %d km e nível de combustível %s.", $initial_mileage, $fuel_txt),
                'is_private' => 0
            ]);
            
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->fields['tickets_id'])) {
                $ticket->update([
                    'id'     => $this->fields['tickets_id'],
                    'status' => CommonITILObject::ASSIGNED
                ]);
            }
        }
        return true;
    }

    /**
     * Conclui a viagem (Check-in)
     */
    function endTrip($input) {
        $id = (int)$input['id'];
        if (!$this->getFromDB($id)) {
            return false;
        }
        
        $final_mileage = (int)$input['final_mileage'];
        $initial_mileage = (int)($this->fields['initial_mileage'] ?: 0);
        
        if ($final_mileage < $initial_mileage) {
            Session::addMessageAfterRedirect('O odômetro final não pode ser menor que o inicial (' . $initial_mileage . ' km).', false, ERROR);
            return false;
        }
        
        $now = date('Y-m-d H:i:s');
        $final_fuel = (int)$input['final_fuel'];
        $return_checklist = json_encode($input['return_checklist'] ?? []);
        $return_comment = $input['return_comment'] ?? '';
        
        // Update database record
        $this->update([
            'id'                   => $id,
            'real_end_date'        => $now,
            'final_mileage'        => $final_mileage,
            'final_fuel'           => $final_fuel,
            'return_checklist'     => $return_checklist,
            'return_comment'       => $return_comment,
            'status'               => self::STATUS_RETURNED,
            '_bypass_driver_check' => true
        ]);
        
        // Update vehicle mileage
        if ($this->fields['plugin_vehiclescheduler_vehicles_id'] > 0) {
            $vehicle = new PluginVehicleschedulerVehicle();
            if ($vehicle->getFromDB($this->fields['plugin_vehiclescheduler_vehicles_id'])) {
                $vehicle->update([
                    'id'      => $this->fields['plugin_vehiclescheduler_vehicles_id'],
                    'mileage' => $final_mileage
                ]);
            }
        }
        
        // Add detailed followup and solve the ticket
        if ($this->fields['tickets_id'] > 0) {
            $fuel_labels = [1 => '1/4', 2 => '2/4', 3 => '3/4', 4 => '4/4 (Cheio)'];
            $fuel_txt = $fuel_labels[$final_fuel] ?? 'Não informado';
            
            $chk = $input['return_checklist'] ?? [];
            $clean_txt = (isset($chk['clean']) && $chk['clean'] == '1') ? 'Sim' : 'Não';
            $damage_txt = (isset($chk['damage']) && $chk['damage'] == '1') ? 'Sim (Avaria/problema reportado)' : 'Não';
            
            $followup = new ITILFollowup();
            $followup->add([
                'itemtype'   => 'Ticket',
                'items_id'   => $this->fields['tickets_id'],
                'users_id'   => Session::getLoginUserID(),
                'content'    => sprintf(
                    "🏁 Viagem concluída e veículo devolvido!\n\nOdômetro Final: %d km (Total rodado: %d km)\nNível de Combustível: %s\nVeículo Limpo: %s\nNova Avaria: %s\nObservações: %s",
                    $final_mileage,
                    ($final_mileage - $initial_mileage),
                    $fuel_txt,
                    $clean_txt,
                    $damage_txt,
                    $return_comment
                ),
                'is_private' => 0
            ]);
            
            $ticket = new Ticket();
            if ($ticket->getFromDB($this->fields['tickets_id'])) {
                $ticket->update([
                    'id'     => $this->fields['tickets_id'],
                    'status' => CommonITILObject::SOLVED
                ]);
            }
        }
        return true;
    }
}
