<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * DriverFine — Registro de infrações de trânsito
 * LGPD: apenas dados operacionais mínimos. Sem CPF, endereço ou biometria.
 */

if (!defined('GLPI_ROOT')) {
    die("Acesso direto não permitido");
}

class PluginVehicleschedulerDriverfine extends CommonDBChild {

    static public $itemtype  = 'PluginVehicleschedulerDriver';
    static public $items_id  = 'plugin_vehiclescheduler_drivers_id';
    public $dohistory        = true;
    static $rightname        = 'plugin_vehiclescheduler';

    function canCreateItem(): bool {
        return Session::haveRight(self::$rightname, UPDATE);
    }
    function canUpdateItem(): bool {
        return Session::haveRight(self::$rightname, UPDATE);
    }
    function canDeleteItem(): bool {
        return Session::haveRight(self::$rightname, UPDATE);
    }
    function canPurgeItem(): bool {
        return Session::haveRight(self::$rightname, PURGE);
    }
    function canViewItem(): bool {
        return Session::haveRight(self::$rightname, READ);
    }

    const SEVERITY_MILD       = 1;
    const SEVERITY_MEDIUM     = 2;
    const SEVERITY_SEVERE     = 3;
    const SEVERITY_VERYSEVERE = 4;

    const STATUS_OPEN      = 1;
    const STATUS_PAID      = 2;
    const STATUS_APPEALED  = 3;
    const STATUS_CANCELLED = 4;

    static function getTypeName($nb = 0) {
        return ($nb === 1) ? 'Infração de Trânsito' : 'Infrações de Trânsito';
    }

    static function getIcon() {
        return 'ti ti-ticket';
    }

    static function getAllSeverities() {
        return [
            self::SEVERITY_MILD       => 'Leve — 3 pts',
            self::SEVERITY_MEDIUM     => 'Média — 4 pts',
            self::SEVERITY_SEVERE     => 'Grave — 5 pts',
            self::SEVERITY_VERYSEVERE => 'Gravíssima — 7 pts',
        ];
    }

    static function getSeverityPoints() {
        return [
            self::SEVERITY_MILD       => 3,
            self::SEVERITY_MEDIUM     => 4,
            self::SEVERITY_SEVERE     => 5,
            self::SEVERITY_VERYSEVERE => 7,
        ];
    }

    static function getAllStatus() {
        return [
            self::STATUS_OPEN      => 'Em aberto',
            self::STATUS_PAID      => 'Paga',
            self::STATUS_APPEALED  => 'Recurso',
            self::STATUS_CANCELLED => 'Cancelada',
        ];
    }

    // INSTÂNCIA — obrigatório, CommonGLPI::getTabNameForItem() é non-static
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item instanceof PluginVehicleschedulerDriver) {
            $count = countElementsInTable(
                self::getTable(),
                ['plugin_vehiclescheduler_drivers_id' => $item->getID()]
            );
            return self::createTabEntry('Infrações de Trânsito', $count);
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item instanceof PluginVehicleschedulerDriver) {
            self::showFinesForDriver($item);
        }
        return true;
    }

    static function showFinesForDriver(PluginVehicleschedulerDriver $driver) {
        global $DB;

        $driver_id  = $driver->getID();
        $canedit    = Session::haveRight('plugin_vehiclescheduler', UPDATE);
        $points_map = self::getSeverityPoints();

        $rows  = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['plugin_vehiclescheduler_drivers_id' => $driver_id],
            'ORDER' => ['fine_date DESC'],
        ]);
        $fines = iterator_to_array($rows);

        $total_points = 0;
        foreach ($fines as $fine) {
            if ($fine['status'] != self::STATUS_CANCELLED) {
                $total_points += $points_map[$fine['severity']] ?? 0;
            }
        }

        $bar_color = '#28a745';
        if ($total_points >= 20)     $bar_color = '#dc3545';
        elseif ($total_points >= 15) $bar_color = '#fd7e14';
        elseif ($total_points >= 10) $bar_color = '#ffc107';
        $pct = min(100, (int) round($total_points / 40 * 100));

        echo "<div style='background:#f8f9fa;border:1px solid #dee2e6;border-radius:6px;padding:12px 16px;margin-bottom:16px;'>";
        echo "<strong>Saldo de Pontos:</strong> ";
        echo "<span style='font-size:1.3em;font-weight:bold;color:{$bar_color};'>{$total_points}</span> / 40 pontos (limite de suspensão — CTB)";
        echo "<div style='margin-top:6px;background:#dee2e6;border-radius:4px;height:10px;'>";
        echo "<div style='background:{$bar_color};width:{$pct}%;height:10px;border-radius:4px;'></div></div>";
        if ($total_points >= 20) {
            echo "<p style='color:#dc3545;margin:6px 0 0;font-size:12px;'>⚠️ Pontuação elevada — recomenda-se conversa com o motorista.</p>";
        }
        echo "</div>";

        echo "<div class='table-responsive'><table class='table table-hover table-striped align-middle border shadow-sm rounded'>";
        echo "<thead class='table-light'><tr>";
        foreach (['Data', 'Descrição', 'Severidade', 'Pontos', 'Status', 'Veículo', 'Ações'] as $h) {
            echo "<th class='text-secondary'>{$h}</th>";
        }
        echo "</tr></thead><tbody>";

        if (count($fines) === 0) {
            echo "<tr><td colspan='7' class='text-center text-muted py-4'><i>Nenhuma infração registrada.</i></td></tr>";
        }

        $severities = self::getAllSeverities();
        $statuses   = self::getAllStatus();
        $vcache     = [];

        foreach ($fines as $fine) {
            $veh_name = '—';
            $vid = (int)($fine['plugin_vehiclescheduler_vehicles_id'] ?? 0);
            if ($vid > 0) {
                if (!isset($vcache[$vid])) {
                    $v = new PluginVehicleschedulerVehicle();
                    $vcache[$vid] = $v->getFromDB($vid)
                        ? htmlspecialchars($v->fields['name'] . ' (' . $v->fields['plate'] . ')')
                        : '—';
                }
                $veh_name = $vcache[$vid];
            }

            $pts       = $points_map[$fine['severity']] ?? '?';
            $sev_label = $severities[$fine['severity']] ?? '?';
            $sta_label = $statuses[$fine['status']]    ?? '?';
            
            $text_class = ($fine['status'] == self::STATUS_CANCELLED) ? 'text-decoration-line-through text-muted' : '';

            echo "<tr>";
            echo "<td class='{$text_class}'>" . Html::convDate($fine['fine_date']) . "</td>";
            echo "<td class='{$text_class}'>" . htmlspecialchars($fine['description']) . "</td>";
            echo "<td><span class='badge bg-secondary'>{$sev_label}</span></td>";
            echo "<td><strong class='text-danger'>{$pts}</strong></td>";
            echo "<td><span class='badge bg-light text-dark border'>{$sta_label}</span></td>";
            echo "<td class='{$text_class}'>{$veh_name}</td>";
            echo "<td>";
            if ($canedit) {
                $url = Plugin::getWebDir('vehiclescheduler') . '/front/driverfine.form.php?id=' . $fine['id'];
                echo "<a href='{$url}' class='btn btn-sm btn-outline-primary'><i class='ti ti-pencil'></i></a>";
            }
            echo "</td></tr>";
        }
        echo "</tbody></table></div>";

        if ($canedit) {
            $form_url = Plugin::getWebDir('vehiclescheduler') . '/front/driverfine.form.php';
            echo "<div class='card shadow-sm border-0 mt-4'>
                    <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                        <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-plus'></i> Registrar Nova Infração</h5>
                    </div>
                    <div class='card-body'>";
            echo "<form method='post' action='{$form_url}'>";
            echo Html::hidden('plugin_vehiclescheduler_drivers_id', ['value' => $driver_id]);
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            
            echo "      <div class='row g-4'>";
            
            echo "          <div class='col-md-6'>
                                <label class='form-label text-muted fw-bold'>Data <span class='text-danger'>*</span></label>
                                <div>";
            Html::showDateField('fine_date', ['value' => date('Y-m-d')]);
            echo "              </div>
                            </div>";

            echo "          <div class='col-md-6'>
                                <label class='form-label text-muted fw-bold'>Severidade <span class='text-danger'>*</span></label>
                                <div>";
            Dropdown::showFromArray('severity', self::getAllSeverities(), ['value' => self::SEVERITY_SEVERE]);
            echo "              </div>
                            </div>";

            echo "          <div class='col-md-6'>
                                <label class='form-label text-muted fw-bold'>Veículo no momento</label>
                                <div>";
            PluginVehicleschedulerVehicle::dropdown(['name' => 'plugin_vehiclescheduler_vehicles_id', 'value' => 0]);
            echo "              </div>
                            </div>";

            echo "          <div class='col-md-6'>
                                <label class='form-label text-muted fw-bold'>Status</label>
                                <div>";
            Dropdown::showFromArray('status', self::getAllStatus(), ['value' => self::STATUS_OPEN]);
            echo "              </div>
                            </div>";

            echo "          <div class='col-12'>
                                <label class='form-label text-muted fw-bold'>Descrição <span class='text-danger'>*</span></label>
                                <textarea name='description' rows='2' class='form-control' placeholder='Descreva a infração (não inclua dados pessoais como CPF)'></textarea>
                            </div>";

            echo "          <div class='col-12 text-end mt-4'>
                                <button type='submit' name='add' class='btn btn-primary'>
                                    <i class='ti ti-check'></i> Adicionar Infração
                                </button>
                            </div>";
                            
            echo "      </div>";
            Html::closeForm();
            echo "  </div></div>";
        }
    }

    function prepareInputForAdd($input) {
        if (empty($input['plugin_vehiclescheduler_drivers_id'])) {
            Session::addMessageAfterRedirect('O motorista é obrigatório.', false, ERROR);
            return false;
        }
        if (empty(trim($input['description'] ?? ''))) {
            Session::addMessageAfterRedirect('A descrição é obrigatória.', false, ERROR);
            return false;
        }
        if (empty($input['fine_date'])) {
            Session::addMessageAfterRedirect('A data da infração é obrigatória.', false, ERROR);
            return false;
        }
        if (!isset($input['status'])) {
            $input['status'] = self::STATUS_OPEN;
        }
        if (!isset($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
        }
        return $input;
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

        // Card 1: Detalhes da Infração
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-danger fw-bold'><i class='ti ti-ticket'></i> Detalhes da Infração</h5>
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Motorista <span class='text-danger'>*</span></label>";
        if (!empty($this->fields['plugin_vehiclescheduler_drivers_id']) && $this->fields['plugin_vehiclescheduler_drivers_id'] > 0) {
            echo "          <div class='form-control bg-light'>";
            $driver = new PluginVehicleschedulerDriver();
            if ($driver->getFromDB($this->fields['plugin_vehiclescheduler_drivers_id'])) {
                echo $driver->getLink();
            }
            echo Html::hidden('plugin_vehiclescheduler_drivers_id',
                ['value' => $this->fields['plugin_vehiclescheduler_drivers_id']]);
            echo "          </div>";
        } else {
            echo "          <div>";
            PluginVehicleschedulerDriver::dropdown(['name' => 'plugin_vehiclescheduler_drivers_id', 'value' => 0]);
            echo "          </div>";
        }
        echo "          </div>";

        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Data da Infração</label>
                            <div>";
        Html::showDateField('fine_date', ['value' => $this->fields['fine_date']]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Severidade</label>
                            <div>";
        Dropdown::showFromArray('severity', self::getAllSeverities(), ['value' => $this->fields['severity']]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Status</label>
                            <div>";
        Dropdown::showFromArray('status', self::getAllStatus(), ['value' => $this->fields['status']]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Veículo no Momento</label>
                            <div>";
        PluginVehicleschedulerVehicle::dropdown([
            'name'  => 'plugin_vehiclescheduler_vehicles_id',
            'value' => $this->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0,
        ]);
        echo "              </div>
                        </div>";

        echo "          <div class='col-12'>
                            <label class='form-label text-muted fw-bold'>Descrição do Ocorrido</label>
                            <textarea name='description' class='form-control' rows='3'>".htmlspecialchars($this->fields['description'] ?? '')."</textarea>
                        </div>";

        echo "      </div>
                </div>
              </div>";

        echo "</div>"; // Container End
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    function rawSearchOptions() {
        $tab   = [];
        $tab[] = ['id' => 'common', 'name' => 'Infrações de Trânsito'];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'id',
                  'name' => 'ID', 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => self::getTable(), 'field' => 'fine_date',
                  'name' => 'Data', 'datatype' => 'date'];
        $tab[] = ['id' => '3', 'table' => self::getTable(), 'field' => 'description',
                  'name' => 'Descrição', 'datatype' => 'text'];
        $tab[] = ['id' => '4', 'table' => self::getTable(), 'field' => 'severity',
                  'name' => 'Severidade', 'datatype' => 'specific', 'searchtype' => ['equals']];
        $tab[] = ['id' => '5', 'table' => self::getTable(), 'field' => 'status',
                  'name' => 'Status', 'datatype' => 'specific', 'searchtype' => ['equals']];
        $tab[] = ['id' => '6', 'table' => 'glpi_plugin_vehiclescheduler_drivers', 'field' => 'name',
                  'name' => 'Motorista', 'datatype' => 'dropdown'];
        $tab[] = ['id' => '7', 'table' => 'glpi_plugin_vehiclescheduler_vehicles', 'field' => 'name',
                  'name' => 'Veículo', 'datatype' => 'dropdown'];
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'severity') return self::getAllSeverities()[$values[$field]] ?? $values[$field];
        if ($field === 'status')   return self::getAllStatus()[$values[$field]]    ?? $values[$field];
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
