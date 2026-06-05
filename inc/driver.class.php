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
        echo "<tr class='table-row'><td colspan='4' class='text-end' style='text-align: right;'><a href='javascript:history.back()' class='btn btn-sm btn-outline-secondary'><i class='ti ti-arrow-left'></i> Voltar</a></td></tr>";

        // Aviso LGPD
        echo "<tr><td colspan='4'>";
        echo "<div style='background:#fff3cd;border:1px solid #ffc107;border-radius:4px;"
            . "padding:8px 14px;font-size:12px;color:#856404;'>";
        echo "<strong>🔒 Aviso de Privacidade (LGPD):</strong> ";
        echo "Coletamos apenas os dados mínimos necessários para a gestão da frota "
            . "(LGPD Art. 6-III). Nenhum dado pessoal sensível (CPF, RG, nº CNH, biometria) "
            . "é armazenado. Base legal: execução de contrato e legítimo interesse operacional.";
        echo "</div></td></tr>";

        // Linha 1: Nome / Matrícula
        echo "<tr class='table-row'>";
        echo "<td>Nome Completo <span class='red'>*</span></td>";
        echo "<td>" . Html::input('name', ['value' => $this->fields['name'], 'size' => 40]) . "</td>";
        echo "<td>Matrícula Interna</td>";
        echo "<td>" . Html::input('registration', [
            'value'       => $this->fields['registration'],
            'size'        => 20,
            'placeholder' => 'ex: EMP-0042',
        ]) . "</td>";
        echo "</tr>";

        // Linha 2: Categoria CNH / Vencimento CNH
        echo "<tr class='table-row'>";
        echo "<td>Categoria CNH <span class='red'>*</span></td>";
        echo "<td>";
        Dropdown::showFromArray('cnh_category', self::getCNHCategories(), [
            'value' => $this->fields['cnh_category'] ?: self::CNH_CAT_B,
        ]);
        echo "</td>";
        echo "<td>Vencimento da CNH <span class='red'>*</span></td>";
        echo "<td>";
        Html::showDateField('cnh_expiry', ['value' => $this->fields['cnh_expiry']]);
        if ($ID > 0 && !empty($this->fields['cnh_expiry'])) {
            $s = self::getCNHExpiryStatus($this->fields['cnh_expiry']);
            echo " &nbsp; " . self::renderExpiryBadge($s);
        }
        echo "</td>";
        echo "</tr>";

        // Linha 3: Departamento / Telefone
        echo "<tr class='table-row'>";
        echo "<td>Departamento/Setor</td>";
        echo "<td>" . Html::input('department', ['value' => $this->fields['department'], 'size' => 40]) . "</td>";
        echo "<td>Telefone para Contato</td>";
        echo "<td>" . Html::input('contact_phone', ['value' => $this->fields['contact_phone'], 'size' => 20]) . "</td>";
        echo "</tr>";

        // Linha 4: Ativo / Observações
        echo "<tr class='table-row'>";
        echo "<td>Ativo</td>";
        echo "<td>";
        Dropdown::showYesNo('is_active', $this->fields['is_active'] ?? 1);
        echo "</td>";
        echo "<td>Observações</td>";
        echo "<td><textarea name='comment' rows='3' style='width:98%;'>"
            . htmlspecialchars($this->fields['comment'] ?? '') . "</textarea></td>";
        echo "</tr>";

        // Rodapé LGPD (somente no cadastro)
        if ($ID <= 0) {
            echo "<tr class='table-row'><td colspan='4'>";
            echo "<small style='color:#666;'>📋 Retenção de dados: os registros são mantidos "
                . "pelo período do vínculo funcional acrescido do mínimo legal aplicável. "
                . "O titular tem direito a acesso, correção e exclusão mediante solicitação.</small>";
            echo "</td></tr>";
        }

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
