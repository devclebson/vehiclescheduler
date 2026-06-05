<?php
/**
 * UI Helpers - Funções auxiliares para interface
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Renderiza botão "Voltar" estilizado
 */
function vs_render_back_button($url = null, $label = 'Voltar') {
    if ($url === null) {
        $url = $_SERVER['HTTP_REFERER'] ?? 'javascript:history.back()';
    }
    
    echo "<div style='margin:20px 0;'>";
    echo "<a href='$url' class='btn btn-secondary vs-btn-app' style='display:inline-flex;align-items:center;gap:8px;'>";
    echo "<i class='ti ti-arrow-left'></i> $label";
    echo "</a>";
    echo "</div>";
}
