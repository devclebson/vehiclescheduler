<?php
/**
 * Sistema de Permissões - Vehicle Scheduler
 * Controla acesso dos perfis ao plugin
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerProfile extends CommonDBTM {
    
    static $rightname = 'plugin_vehiclescheduler';
    
    /**
     * Verifica se usuário tem acesso ao portal de reservas (requerentes)
     * Se não houver configuração no perfil, permite acesso por padrão
     */
    static function canAccessRequester() {
        global $DB;
        
        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return false;
        }
        
        $profile_id = $_SESSION['glpiactiveprofile']['id'];
        
        // Verificar se existe configuração para este perfil
        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_profiles',
            'WHERE' => ['profiles_id' => $profile_id]
        ]);
        
        if (count($iterator) > 0) {
            // Se existe configuração, verificar se tem permissão
            $data = $iterator->current();
            return ($data['requester_access'] == 1);
        }
        
        // Se não existe configuração, permite acesso por padrão
        // (compatibilidade com instalações antigas)
        return true;
    }
    
    /**
     * Verifica se usuário tem acesso à gestão (leitura)
     * Se não houver configuração, bloqueia por padrão (segurança)
     */
    static function canViewManagement() {
        global $DB;
        
        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return false;
        }
        
        $profile_id = $_SESSION['glpiactiveprofile']['id'];
        
        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_profiles',
            'WHERE' => ['profiles_id' => $profile_id]
        ]);
        
        if (count($iterator) > 0) {
            $data = $iterator->current();
            return in_array($data['management_access'], ['r', 'w']);
        }
        
        // Se não existe configuração, nega acesso por padrão
        return false;
    }
    
    /**
     * Verifica se usuário pode editar na gestão (escrita)
     */
    static function canEditManagement() {
        global $DB;
        
        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return false;
        }
        
        $profile_id = $_SESSION['glpiactiveprofile']['id'];
        
        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_profiles',
            'WHERE' => ['profiles_id' => $profile_id]
        ]);
        
        if (count($iterator) > 0) {
            $data = $iterator->current();
            return ($data['management_access'] === 'w');
        }
        
        return false;
    }
    
    /**
     * Verifica se pode aprovar/rejeitar reservas
     */
    static function canApproveReservations() {
        global $DB;
        
        if (!isset($_SESSION['glpiactiveprofile']['id'])) {
            return false;
        }
        
        $profile_id = $_SESSION['glpiactiveprofile']['id'];
        
        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_profiles',
            'WHERE' => ['profiles_id' => $profile_id]
        ]);
        
        if (count($iterator) > 0) {
            $data = $iterator->current();
            return ($data['can_approve'] == 1);
        }
        
        return false;
    }
    
    /**
     * Inicializa permissões padrão para perfis existentes
     */
    static function initProfile() {
        global $DB;
        
        $profiles = $DB->request([
            'FROM' => 'glpi_profiles',
            'WHERE' => ['interface' => 'central']
        ]);
        
        foreach ($profiles as $profile) {
            $exists = $DB->request([
                'FROM' => 'glpi_plugin_vehiclescheduler_profiles',
                'WHERE' => ['profiles_id' => $profile['id']]
            ])->current();
            
            if (!$exists) {
                // Perfil Super-Admin (id=4) tem tudo
                // Outros perfis: apenas acesso requerente
                $is_super = ($profile['id'] == 4 || $profile['name'] == 'Super-Admin');
                
                $DB->insert('glpi_plugin_vehiclescheduler_profiles', [
                    'profiles_id'       => $profile['id'],
                    'requester_access'  => 1,
                    'management_access' => $is_super ? 'w' : '',
                    'can_approve'       => $is_super ? 1 : 0,
                ]);
            }
        }
    }
    
    /**
     * Remove permissões do banco
     */
    static function removeRightsFromDatabase() {
        global $DB;
        $DB->delete('glpi_plugin_vehiclescheduler_profiles', [1 => 1]);
    }
    
    // ══════════════════════════════════════════════════════════════════════
    // INTERFACE DE CONFIGURAÇÃO NO PERFIL
    // ══════════════════════════════════════════════════════════════════════
    
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if ($item instanceof Profile && $item->getField('id')) {
            return 'Gestão de Frota';
        }
        return '';
    }
    
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if (!($item instanceof Profile)) {
            return false;
        }
        
        if (!$item->canView()) {
            return false;
        }
        
        global $DB;
        $profile_id = $item->getID();
        
        // Buscar permissões atuais
        $current = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_profiles',
            'WHERE' => ['profiles_id' => $profile_id]
        ])->current();
        
        $requester_access  = $current['requester_access'] ?? 0;
        $management_access = $current['management_access'] ?? '';
        $can_approve       = $current['can_approve'] ?? 0;
        
        $canedit = $item->canEdit($profile_id);
        
        echo "<form name='fleet_profile_form' method='post' action='" . 
             Plugin::getWebDir('vehiclescheduler') . "/front/profile.form.php'>";
        
        echo "<div class='spaced'><table class='tab_cadre_fixehov'>";
        echo "<tr class='headerRow'><th colspan='2'>Permissões — Gestão de Frota</th></tr>";
        
        // Acesso Requerente
        echo "<tr class='table-row'>";
        echo "<td width='50%'><strong>Acesso ao Portal de Reservas</strong><br><small>Permite solicitar reservas e reportar incidentes</small></td>";
        echo "<td>";
        if ($canedit) {
            Dropdown::showYesNo("requester_access", $requester_access);
        } else {
            echo $requester_access ? 'Sim' : 'Não';
        }
        echo "</td></tr>";
        
        // Acesso Gestão
        echo "<tr class='table-row'>";
        echo "<td><strong>Acesso à Gestão de Frota</strong><br><small>Dashboard, veículos, motoristas, manutenções, etc.</small></td>";
        echo "<td>";
        if ($canedit) {
            $options = [
                ''  => '— Sem acesso —',
                'r' => '🔍 Leitura (visualizar)',
                'w' => '✏️ Escrita (editar)'
            ];
            Dropdown::showFromArray("management_access", $options, ['value' => $management_access]);
        } else {
            $labels = ['' => 'Não', 'r' => 'Leitura', 'w' => 'Escrita'];
            echo $labels[$management_access] ?? 'Não';
        }
        echo "</td></tr>";
        
        // Aprovação de Reservas
        echo "<tr class='table-row'>";
        echo "<td><strong>Aprovar/Rejeitar Reservas</strong><br><small>Ações de aprovação rápida no dashboard</small></td>";
        echo "<td>";
        if ($canedit) {
            Dropdown::showYesNo("can_approve", $can_approve);
        } else {
            echo $can_approve ? 'Sim' : 'Não';
        }
        echo "</td></tr>";
        
        if ($canedit) {
            echo "<tr class='table-row'><td colspan='2' class='center'>";
            echo Html::hidden('profiles_id', ['value' => $profile_id]);
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo "<input type='submit' name='update' value='Salvar' class='btn btn-primary'>";
            echo "</td></tr>";
        }
        
        echo "</table></div>";
        Html::closeForm();
        
        return true;
    }
}
