<?php
/**
 * Common functions and CSS/JS loader
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Include app-style CSS in page
 */
function plugin_vehiclescheduler_load_css() {
    global $CFG_GLPI;
    
    $css_url = $CFG_GLPI['root_doc'] . '/plugins/vehiclescheduler/css/app-style.css';
    
    echo "<link rel='stylesheet' href='$css_url?v=" . PLUGIN_VEHICLESCHEDULER_VERSION . "'>";
    
    // Adicionar classe ao body para ativar o background gradient
    echo "<script>document.body.classList.add('vs-app-body');</script>";
}

// Include UI helpers globally so vs_render_navbar is always available
include_once(__DIR__ . '/ui-helpers.php');

/**
 * Apply glassmorphism classes to existing GLPI elements
 */
function plugin_vehiclescheduler_enhance_ui() {
    global $CFG_GLPI;


    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Transformar cards do GLPI em glassmorphism
        document.querySelectorAll('.tab_cadre_fixe, .tab_cadre_fixehov').forEach(el => {
            if (!el.classList.contains('vs-glass-card-solid')) {
                el.classList.add('vs-table-glass');
            }
        });
        
        // Transformar botões
        document.querySelectorAll('.btn').forEach(btn => {
            if (!btn.classList.contains('vs-btn-app')) {
                btn.classList.add('vs-btn-app');
            }
        });
        
        // Transformar inputs e selects
        document.querySelectorAll('input[type=text], input[type=number], input[type=date], select, textarea').forEach(input => {
            if (!input.classList.contains('vs-input-glass')) {
                input.classList.add('vs-input-glass');
            }
        });
    });
    </script>";
}
