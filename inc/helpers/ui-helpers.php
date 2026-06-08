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
  --vs-card-bg: rgba(255, 255, 255, 0.85);
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
  backdrop-filter: blur(10px);
  padding: 12px 24px;
  border-radius: 12px;
  margin-bottom: 24px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
  border: 1px solid rgba(255,255,255,0.4);
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
  font-weight: 600;
  font-size: 0.95rem;
  padding: 8px 16px;
  border-radius: 8px;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.vs-nav-link:hover, .vs-nav-link.active {
  background: var(--vs-bg);
  color: var(--vs-primary);
}

/* Buttons */
.vs-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.vs-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.15);
}

.vs-btn-primary { background: var(--vs-primary); color: #fff !important; }
.vs-btn-primary:hover { background: var(--vs-primary-hover); }

.vs-btn-secondary { background: var(--vs-secondary); color: #fff !important; }
.vs-btn-secondary:hover { background: var(--vs-secondary-hover); }

.vs-btn-warning { background: var(--vs-warning); color: #fff !important; }
.vs-btn-warning:hover { background: var(--vs-warning-hover); }

.vs-btn-danger { background: var(--vs-danger); color: #fff !important; }
.vs-btn-danger:hover { background: var(--vs-danger-hover); }

.vs-btn-light { background: #fff; color: var(--vs-text) !important; border: 1px solid var(--vs-border); }
.vs-btn-light:hover { background: #f1f5f9; }

/* Cards */
.vs-card {
  background: var(--vs-card-bg);
  backdrop-filter: blur(12px);
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,0.4);
  box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01);
  padding: 24px;
  transition: transform 0.2s;
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
  font-weight: 700;
  color: var(--vs-text);
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Badges */
.vs-badge {
  display: inline-flex;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
}
.vs-badge-green { background: #dcfce7; color: #166534; }
.vs-badge-blue { background: #dbeafe; color: #1e40af; }
.vs-badge-yellow { background: #fef3c7; color: #92400e; }
.vs-badge-red { background: #fee2e2; color: #991b1b; }
.vs-badge-gray { background: #f1f5f9; color: #475569; }

/* Tables */
.vs-table {
  width: 100%;
  border-collapse: collapse;
}
.vs-table th {
  background: var(--vs-bg);
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
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
    echo '<span class="vs-badge vs-badge-blue"><i class="ti ti-car"></i> Frota</span>';
    echo '</div>';

    echo '</div>'; // close vs-navbar
}
