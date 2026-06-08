<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Driver — Cadastro de Motoristas
 * LGPD Art. 6-III: apenas dados mínimos necessários para gestão da frota.
 * Base legal: execução de contrato + legítimo interesse (Art. 7-II/IX).
 * Dados NÃO coletados: CPF, RG, nº CNH, data nasc., foto, biometria.
 */

if (!defined('GLPI_ROOT')) {
    die("Acesso direto não permitido");
}

class PluginVehicleschedulerDriver extends CommonDBTM {

    public $dohistory = true;
    static $rightname = 'plugin_vehiclescheduler';

    const CNH_CAT_A   = 'A';
    const CNH_CAT_B   = 'B';
    const CNH_CAT_AB  = 'AB';
    const CNH_CAT_C   = 'C';
    const CNH_CAT_D   = 'D';
    const CNH_CAT_E   = 'E';
    const CNH_CAT_ACC = 'ACC';

    const CNH_ALERT_CRITICAL = 30;
    const CNH_ALERT_WARNING  = 90;

    static function getTypeName($nb = 0) {
        return ($nb === 1) ? 'Motorista' : 'Motoristas';
    }

    static function getMenuName() {
        return 'Motoristas';
    }

    static function getIcon() {
        return 'ti ti-steering-wheel';
    }

    static function getMenuContent() {
        if (!Session::haveRight('plugin_vehiclescheduler', READ)) {
            return false;
        }
        $menu = [];
        $menu['title'] = 'Motoristas';
        $menu['page']  = '/plugins/vehiclescheduler/front/driver.php';
        $menu['icon']  = self::getIcon();
        $menu['links']['search'] = '/plugins/vehiclescheduler/front/driver.php';
        if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
            $menu['links']['add'] = '/plugins/vehiclescheduler/front/driver.form.php';
        }
        $menu['options']['driver'] = [
            'title'          => 'Motoristas',
            'page'           => '/plugins/vehiclescheduler/front/driver.php',
            'icon'           => self::getIcon(),
            'links'          => [
                'search' => '/plugins/vehiclescheduler/front/driver.php',
                'add'    => '/plugins/vehiclescheduler/front/driver.form.php',
            ],
            'lists_itemtype' => 'PluginVehicleschedulerDriver',
        ];
        return $menu;
    }

    static function getCNHCategories() {
        return [
            self::CNH_CAT_A   => 'A — Motos',
            self::CNH_CAT_B   => 'B — Automóveis',
            self::CNH_CAT_AB  => 'AB — Motos e Automóveis',
            self::CNH_CAT_C   => 'C — Caminhões',
            self::CNH_CAT_D   => 'D — Ônibus',
            self::CNH_CAT_E   => 'E — Combinações de veículos',
            self::CNH_CAT_ACC => 'ACC — Ciclomotores',
        ];
    }

    static function getCNHExpiryStatus($cnh_expiry) {
        if (empty($cnh_expiry) || $cnh_expiry === '0000-00-00') {
            return ['status' => 'unknown', 'days' => null];
        }
        $today  = new DateTime('today');
        $expiry = new DateTime($cnh_expiry);
        $diff   = (int) $today->diff($expiry)->format('%r%a');
        if ($diff < 0)                         return ['status' => 'expired',  'days' => abs($diff)];
        if ($diff <= self::CNH_ALERT_CRITICAL) return ['status' => 'critical', 'days' => $diff];
        if ($diff <= self::CNH_ALERT_WARNING)  return ['status' => 'warning',  'days' => $diff];
        return ['status' => 'ok', 'days' => $diff];
    }

    static function renderExpiryBadge(array $s): string {
        $map = [
            'ok'       => ['#28a745', 'Válida'],
            'warning'  => ['#fd7e14', 'Vence em breve'],
            'critical' => ['#dc3545', 'Crítico'],
            'expired'  => ['#6c757d', 'VENCIDA'],
            'unknown'  => ['#aaa',    'Sem data'],
        ];
        [$color, $label] = $map[$s['status']] ?? ['#aaa', '?'];

        if ($s['status'] === 'critical' && $s['days'] !== null) {
            $label = "Vence em {$s['days']} dias";
        } elseif ($s['status'] === 'warning' && $s['days'] !== null) {
            $label = "Vence em {$s['days']} dias";
        } elseif ($s['status'] === 'ok' && $s['days'] !== null) {
            $label = "Válida — {$s['days']} dias restantes";
        } elseif ($s['status'] === 'expired' && $s['days'] !== null) {
            $label = "VENCIDA há {$s['days']} dias";
        }

        return "<span style='background:{$color};color:#fff;padding:2px 8px;"
             . "border-radius:10px;font-size:11px;font-weight:bold;'>"
             . htmlspecialchars($label) . "</span>";
    }

    static function dropdown($options = []) {
        $params = [
            'name'      => 'plugin_vehiclescheduler_drivers_id',
            'value'     => 0,
            'condition' => ['is_active' => 1],
            'display'   => true,
        ];
        foreach ($options as $k => $v) {
            $params[$k] = $v;
        }
        return Dropdown::show(self::class, $params);
    }

    function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginVehicleschedulerDriverfine', $ong, $options);
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

        // Aviso LGPD
        echo "<div class='alert alert-warning d-flex align-items-center mb-4' style='background:#fff3cd; color:#856404; border-color:#ffc107;'>
                <i class='ti ti-lock me-2 fs-4'></i>
                <div>
                    <strong>Aviso de Privacidade (LGPD):</strong> Coletamos apenas os dados mínimos necessários para a gestão da frota (LGPD Art. 6-III). Nenhum dado pessoal sensível é armazenado. Base legal: execução de contrato e legítimo interesse operacional.
                </div>
              </div>";

        // CNH Badge
        $cnh_badge_html = "";
        if ($ID > 0 && !empty($this->fields['cnh_expiry'])) {
            $s = self::getCNHExpiryStatus($this->fields['cnh_expiry']);
            $cnh_badge_html = "<div class='ms-auto'>" . self::renderExpiryBadge($s) . "</div>";
        }

        // Card: Perfil Motorista
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2 d-flex align-items-center'>
                    <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-id'></i> Perfil do Motorista</h5>
                    {$cnh_badge_html}
                </div>
                <div class='card-body'>
                    <div class='row g-4'>";
        
        // Row 1
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Nome Completo <span class='text-danger'>*</span></label>
                            <input type='text' name='name' value='".htmlspecialchars($this->fields['name'] ?? '')."' class='form-control form-control-lg'>
                        </div>";
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Matrícula Interna</label>
                            <input type='text' name='registration' value='".htmlspecialchars($this->fields['registration'] ?? '')."' placeholder='ex: EMP-0042' class='form-control form-control-lg'>
                        </div>";
        
        // Row 2
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Departamento/Setor</label>
                            <input type='text' name='department' value='".htmlspecialchars($this->fields['department'] ?? '')."' class='form-control'>
                        </div>";
        echo "          <div class='col-md-6'>
                            <label class='form-label text-muted fw-bold'>Telefone para Contato</label>
                            <input type='text' name='contact_phone' value='".htmlspecialchars($this->fields['contact_phone'] ?? '')."' class='form-control'>
                        </div>";

        // Row 3
        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Categoria CNH <span class='text-danger'>*</span></label>
                            <select name='cnh_category' class='form-select'>";
                            foreach (self::getCNHCategories() as $cat_key => $cat_val) {
                                $sel = ($this->fields['cnh_category'] == $cat_key) ? 'selected' : '';
                                echo "<option value='{$cat_key}' {$sel}>{$cat_val}</option>";
                            }
        echo "              </select>
                        </div>";
        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Vencimento da CNH <span class='text-danger'>*</span></label>
                            <input type='date' name='cnh_expiry' value='".htmlspecialchars($this->fields['cnh_expiry'] ?? '')."' class='form-control'>
                        </div>";
        echo "          <div class='col-md-4'>
                            <label class='form-label text-muted fw-bold'>Ativo</label>
                            <select name='is_active' class='form-select'>
                                <option value='1' ".($this->fields['is_active'] == 1 ? 'selected' : '').">Sim</option>
                                <option value='0' ".($this->fields['is_active'] == 0 ? 'selected' : '').">Não</option>
                            </select>
                        </div>";
        
        echo "      </div>
                </div>
              </div>";

        // Card 2: Observações
        echo "<div class='card shadow-sm border-0 mb-4'>
                <div class='card-header bg-white border-bottom-0 pt-4 pb-2'>
                    <h5 class='mb-0 text-primary fw-bold'><i class='ti ti-align-left'></i> Observações</h5>
                </div>
                <div class='card-body'>
                    <textarea name='comment' rows='3' class='form-control'>".htmlspecialchars($this->fields['comment'] ?? '')."</textarea>
                </div>
              </div>";

        if ($ID <= 0) {
            echo "<div class='text-muted small mt-2'>
                    <i class='ti ti-info-circle'></i> Retenção de dados: os registros são mantidos pelo período do vínculo funcional acrescido do mínimo legal aplicável. O titular tem direito a acesso, correção e exclusão mediante solicitação.
                  </div>";
        }

        echo "</div>"; // Container End
        echo "</td></tr>";
        
        $this->showFormButtons($options);
        return true;
    }

    function prepareInputForAdd($input) {
        if (empty(trim($input['name'] ?? ''))) {
            Session::addMessageAfterRedirect('O nome do motorista é obrigatório.', false, ERROR);
            return false;
        }
        if (empty($input['cnh_category'])) {
            Session::addMessageAfterRedirect('A categoria da CNH é obrigatória.', false, ERROR);
            return false;
        }
        if (empty($input['cnh_expiry'])) {
            Session::addMessageAfterRedirect('O vencimento da CNH é obrigatório.', false, ERROR);
            return false;
        }
        if (!isset($input['is_active'])) {
            $input['is_active'] = 1;
        }
        if (!isset($input['entities_id'])) {
            $input['entities_id'] = $_SESSION['glpiactive_entity'];
        }
        return $input;
    }

    function prepareInputForUpdate($input) {
        if (empty(trim($input['name'] ?? ''))) {
            Session::addMessageAfterRedirect('O nome do motorista é obrigatório.', false, ERROR);
            return false;
        }
        if (empty($input['cnh_category'])) {
            Session::addMessageAfterRedirect('A categoria da CNH é obrigatória.', false, ERROR);
            return false;
        }
        if (empty($input['cnh_expiry'])) {
            Session::addMessageAfterRedirect('O vencimento da CNH é obrigatório.', false, ERROR);
            return false;
        }
        return $input;
    }

    function rawSearchOptions() {
        $tab   = [];
        $tab[] = ['id' => 'common', 'name' => 'Motoristas'];
        $tab[] = [
            'id' => '1', 'table' => $this->getTable(), 'field' => 'name',
            'name' => 'Nome Completo', 'datatype' => 'itemlink', 'massiveaction' => false,
        ];
        $tab[] = [
            'id' => '2', 'table' => $this->getTable(), 'field' => 'registration',
            'name' => 'Matrícula Interna', 'datatype' => 'string',
        ];
        $tab[] = [
            'id' => '3', 'table' => $this->getTable(), 'field' => 'cnh_category',
            'name' => 'Categoria CNH', 'datatype' => 'specific',
            'searchtype' => ['equals', 'notequals'],
        ];
        $tab[] = [
            'id' => '4', 'table' => $this->getTable(), 'field' => 'cnh_expiry',
            'name' => 'Vencimento da CNH', 'datatype' => 'date',
        ];
        $tab[] = [
            'id' => '5', 'table' => $this->getTable(), 'field' => 'department',
            'name' => 'Departamento/Setor', 'datatype' => 'string',
        ];
        $tab[] = [
            'id' => '6', 'table' => $this->getTable(), 'field' => 'contact_phone',
            'name' => 'Telefone para Contato', 'datatype' => 'string',
        ];
        $tab[] = [
            'id' => '7', 'table' => $this->getTable(), 'field' => 'is_active',
            'name' => 'Ativo', 'datatype' => 'bool',
        ];
        $tab[] = [
            'id' => '8', 'table' => $this->getTable(), 'field' => 'id',
            'name' => 'ID', 'datatype' => 'integer',
        ];
        return $tab;
    }

    static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) $values = [$field => $values];
        if ($field === 'cnh_category') {
            return self::getCNHCategories()[$values[$field]] ?? $values[$field];
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
