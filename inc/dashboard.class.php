<?php
/**
 * Plugin Vehicle Scheduler for GLPI
 * Dashboard / Painel Centralizador em Abas
 */

if (!defined('GLPI_ROOT')) {
    die("Acesso direto não permitido");
}

class PluginVehicleschedulerDashboard extends CommonGLPI {

    static $rightname = 'plugin_vehiclescheduler';

    /**
     * Define as abas do menu lateral esquerdo
     */
    function defineTabs($options = []) {
        $ong = [];

        // Abas disponíveis para todos (desde que tenham permissão do plugin)
        $ong['PluginVehicleschedulerDashboard$1'] = '🏠 Portal';
        $ong['PluginVehicleschedulerDashboard$2'] = '🗓️ Agendamento';

        // Abas exclusivas dos gestores
        if (PluginVehicleschedulerProfile::canViewManagement()) {
            $ong['PluginVehicleschedulerDashboard$3'] = '📊 Gestão de Frota';
            $ong['PluginVehicleschedulerDashboard$4'] = '🚗 Veículos';
            $ong['PluginVehicleschedulerDashboard$5'] = '🪪 Motoristas';
            $ong['PluginVehicleschedulerDashboard$6'] = '⚠️ Incidentes';
            $ong['PluginVehicleschedulerDashboard$7'] = '🔧 Manutenções';
            $ong['PluginVehicleschedulerDashboard$8'] = '🛡️ Sinistros';
            $ong['PluginVehicleschedulerDashboard$9'] = '🎫 Multas';
        }

        return $ong;
    }

    /**
     * Evita renderizar um formulário vazio no topo do painel
     */
    function showForm($ID, array $options = []) {
        return true;
    }

    /**
     * Retorna o nome amigável (obrigatório da interface CommonGLPI)
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        return '';
    }

    /**
     * Renderiza o conteúdo de cada aba (carregado dinamicamente via AJAX)
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if (!($item instanceof PluginVehicleschedulerDashboard)) {
            return false;
        }

        switch ($tabnum) {
            case 1:
                self::showPortal();
                break;
            case 2:
                self::showBooking();
                break;
            case 3:
                self::showManagement();
                break;
            case 4:
                if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
                    echo "<div class='d-flex justify-content-end mb-3'>";
                    echo "<a href='vehicle.form.php' class='btn btn-primary'><i class='ti ti-plus'></i> Adicionar Veículo</a>";
                    echo "</div>";
                }
                Search::show('PluginVehicleschedulerVehicle');
                break;
            case 5:
                if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
                    echo "<div class='d-flex justify-content-end mb-3'>";
                    echo "<a href='driver.form.php' class='btn btn-primary'><i class='ti ti-plus'></i> Adicionar Motorista</a>";
                    echo "</div>";
                }
                Search::show('PluginVehicleschedulerDriver');
                break;
            case 6:
                if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
                    echo "<div class='d-flex justify-content-end mb-3'>";
                    echo "<a href='incident.form.php' class='btn btn-primary'><i class='ti ti-plus'></i> Reportar Incidente</a>";
                    echo "</div>";
                }
                Search::show('PluginVehicleschedulerIncident');
                break;
            case 7:
                if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
                    echo "<div class='d-flex justify-content-end mb-3'>";
                    echo "<a href='maintenance.form.php' class='btn btn-primary'><i class='ti ti-plus'></i> Agendar Manutenção</a>";
                    echo "</div>";
                }
                Search::show('PluginVehicleschedulerMaintenance');
                break;
            case 8:
                if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
                    echo "<div class='d-flex justify-content-end mb-3'>";
                    echo "<a href='insuranceclaim.form.php' class='btn btn-primary'><i class='ti ti-plus'></i> Abrir Sinistro</a>";
                    echo "</div>";
                }
                Search::show('PluginVehicleschedulerInsuranceclaim');
                break;
            case 9:
                if (Session::haveRight('plugin_vehiclescheduler', CREATE)) {
                    echo "<div class='d-flex justify-content-end mb-3'>";
                    echo "<a href='driverfine.form.php' class='btn btn-primary'><i class='ti ti-plus'></i> Adicionar Multa</a>";
                    echo "</div>";
                }
                Search::show('PluginVehicleschedulerDriverfine');
                break;
        }
        return true;
    }

    /**
     * Exibe o Portal do Requerente
     */
    static function showPortal() {
        $_GET['is_tab'] = 1;
        include(Plugin::getPhpDir('vehiclescheduler') . '/front/dashboards/portal.php');
    }

    /**
     * Exibe a grade de reservas (Booking/Calendar)
     */
    static function showBooking() {
        $_GET['is_tab'] = 1;
        include(Plugin::getPhpDir('vehiclescheduler') . '/front/pages/booking.php');
    }

    /**
     * Exibe o dashboard gerencial operacional
     */
    static function showManagement() {
        $_GET['is_tab'] = 1;
        include(Plugin::getPhpDir('vehiclescheduler') . '/front/dashboards/management.php');
    }
}
