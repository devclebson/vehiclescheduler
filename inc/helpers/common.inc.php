<?php
/**
 * Common functions and CSS/JS loader
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

// Include UI helpers globally so vs_render_navbar is always available
include_once(__DIR__ . '/ui-helpers.php');
