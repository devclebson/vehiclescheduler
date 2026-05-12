<?php

/**
 * UI helper functions.
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Render a simple back button without inline styles.
 */
function vs_render_back_button(?string $url = null, string $label = 'Voltar'): void
{
    $target = $url;

    if ($target === null || trim($target) === '') {
        $target = (string) ($_SERVER['HTTP_REFERER'] ?? 'javascript:history.back()');
    }

    echo "<div class='vs-page-toolbar'>";
    echo "   <a href='" . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . "' class='vs-btn-back'>";
    echo "      <i class='ti ti-arrow-left'></i>";
    echo "      <span>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    echo '   </a>';
    echo '</div>';
}
