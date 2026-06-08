<?php
/**
 * UI Helpers - Funções auxiliares para interface
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza botão "Voltar" estilizado (Legado - manter por compatibilidade)
 */
function vs_render_back_button($url = null, $label = 'Voltar') {
    if ($url === null) {
        $url = $_SERVER['HTTP_REFERER'] ?? 'javascript:history.back()';
    }
    
    echo "<div style='margin:20px 0;'>";
    echo "<a href='$url' class='vs-btn vs-btn-light' style='display:inline-flex;align-items:center;gap:8px;'>";
    echo "<i class='ti ti-arrow-left'></i> $label";
    echo "</a>";
    echo "</div>";
}

/**
 * Renderiza a Navbar unificada do plugin
 */
function vs_render_navbar($active_page = '') {
    global $CFG_GLPI;
    $base = $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler';
    
    // Injetar CSS INLINE para bypassar qualquer bloqueio de cache ou CSP do servidor!
    echo "<style>
/* Vehicle Scheduler Core UI */
:root {
  --vs-primary: #3b82f6;
  --vs-primary-hover: #2563eb;
  --vs-secondary: #64748b;
  --vs-secondary-hover: #475569;
  --vs-warning: #f59e0b;
  --vs-warning-hover: #d97706;
  --vs-danger: #ef4444;
  --vs-danger-hover: #dc2626;
  --vs-bg: #f8fafc;
  --vs-card-bg: rgba(255, 255, 255, 0.9);
  --vs-border: #e2e8f0;
  --vs-text: #1e293b;
  --vs-text-light: #64748b;
}

/* Base */
body.vs-app-view {
  background: var(--vs-bg);
  color: var(--vs-text);
  font-family: inherit;
}

/* Navbar */
.vs-navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--vs-card-bg);
  backdrop-filter: blur(12px);
  padding: 14px 28px;
  border-radius: 16px;
  margin-bottom: 28px;
  box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05), 0 8px 16px -6px rgba(15,23,42,0.02);
  border: 1px solid rgba(226, 232, 240, 0.8);
  position: relative;
  overflow: hidden;
}

.vs-navbar::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
}

.vs-nav-left, .vs-nav-right, .vs-nav-center {
  display: flex;
  align-items: center;
  gap: 16px;
}

.vs-nav-center {
  flex: 1;
  justify-content: center;
}

.vs-nav-link {
  color: var(--vs-text-light);
  text-decoration: none;
  font-weight: 700;
  font-size: 0.92rem;
  padding: 8px 18px;
  border-radius: 20px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.vs-nav-link:hover {
  background: rgba(59, 130, 246, 0.05);
  color: var(--vs-primary);
}

.vs-nav-link.active {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: #ffffff !important;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
}

/* Buttons */
.vs-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 0.92rem;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.vs-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 15px rgba(59, 130, 246, 0.15);
}

.vs-btn-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff !important; }
.vs-btn-primary:hover { background: linear-gradient(135deg, #2563eb, #1d4ed8); }

.vs-btn-secondary { background: linear-gradient(135deg, #64748b, #475569); color: #fff !important; }
.vs-btn-secondary:hover { background: linear-gradient(135deg, #475569, #334155); }

.vs-btn-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff !important; }
.vs-btn-warning:hover { background: linear-gradient(135deg, #d97706, #b45309); }

.vs-btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff !important; }
.vs-btn-danger:hover { background: linear-gradient(135deg, #dc2626, #b91c1c); }

.vs-btn-light { background: #fff; color: var(--vs-text) !important; border: 1px solid var(--vs-border); }
.vs-btn-light:hover { background: #f1f5f9; border-color: #cbd5e1; }

/* Cards */
.vs-card {
  background: var(--vs-card-bg);
  backdrop-filter: blur(12px);
  border-radius: 16px;
  border: 1px solid rgba(226, 232, 240, 0.8);
  box-shadow: 0 10px 25px -5px rgba(15,23,42,0.05), 0 8px 16px -6px rgba(15,23,42,0.02);
  padding: 24px !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.vs-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid var(--vs-border);
}

.vs-card-header h2, .vs-card-header h3 {
  margin: 0;
  font-weight: 800;
  color: var(--vs-text);
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Badges */
.vs-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 12px;
  border-radius: 9999px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.vs-badge-green { background: linear-gradient(135deg, #f0fdf4, #dcfce7); color: #15803d; border: 1px solid #bbf7d0; }
.vs-badge-blue { background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #1d4ed8; border: 1px solid #bfdbfe; }
.vs-badge-yellow { background: linear-gradient(135deg, #fffbeb, #fef3c7); color: #b45309; border: 1px solid #fde68a; }
.vs-badge-red { background: linear-gradient(135deg, #fef2f2, #fee2e2); color: #b91c1c; border: 1px solid #fecaca; }
.vs-badge-gray { background: linear-gradient(135deg, #f8fafc, #f1f5f9); color: #475569; border: 1px solid #cbd5e1; }

/* Tables */
.vs-table {
  width: 100%;
  border-collapse: collapse;
}
.vs-table th {
  background: var(--vs-bg);
  padding: 12px 16px;
  text-align: left;
  font-weight: 700;
  color: var(--vs-text-light);
  border-bottom: 2px solid var(--vs-border);
}
.vs-table td {
  padding: 14px 16px;
  border-bottom: 1px solid var(--vs-border);
  vertical-align: middle;
}
.vs-table tr:last-child td { border-bottom: none; }
.vs-table tr:hover { background: rgba(0,0,0,0.01); }
</style>";

    // Adicionar classe ao body para os backgrounds funcionarem (fallback se não houver body.vs-app-view)
    echo "<script>if(!document.body.classList.contains('vs-app-view')) document.body.classList.add('vs-app-view');</script>";

    $is_manager = class_exists('PluginVehicleschedulerProfile') && PluginVehicleschedulerProfile::canViewManagement();

    echo '<div class="vs-navbar">';
    
    // Left: Navigation Links
    echo '<div class="vs-nav-left">';
    $portal_class = ($active_page == 'portal') ? 'active' : '';
    echo "<a href='$base/front/dashboards/portal.php' class='vs-nav-link $portal_class'><i class='ti ti-home'></i> Portal</a>";
    
    $booking_class = ($active_page == 'booking') ? 'active' : '';
    echo "<a href='$base/front/pages/booking.php' class='vs-nav-link $booking_class'><i class='ti ti-calendar-event'></i> Agendamento</a>";

    if ($is_manager) {
        $dash_class = ($active_page == 'dashboard') ? 'active' : '';
        echo "<a href='$base/front/dashboards/management.php' class='vs-nav-link $dash_class'><i class='ti ti-layout-dashboard'></i> Gestão</a>";
        
    }
    echo '</div>';
    
    // Right: Info or Empty
    echo '<div class="vs-nav-right">';
    echo '<span class="vs-badge" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #ffffff; border: none; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25); padding: 6px 14px; border-radius: 20px;"><i class="ti ti-car"></i> Frota</span>';
    echo '</div>';

    echo '</div>'; // close vs-navbar
}
