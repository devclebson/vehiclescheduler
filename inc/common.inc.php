<?php

function plugin_vehiclescheduler_get_root_doc(): string
{
    global $CFG_GLPI;

    $rootDoc = (string) ($CFG_GLPI['root_doc'] ?? '');

    if ($rootDoc === '' || $rootDoc === '/') {
        return '';
    }

    return rtrim($rootDoc, '/');
}

/**
 * Build a plugin front URL respecting GLPI root_doc.
 *
 * @param string $file
 * @return string
 */
function plugin_vehiclescheduler_get_front_url(string $file): string
{
    return plugin_vehiclescheduler_get_root_doc()
        . '/plugins/vehiclescheduler/front/' . ltrim($file, '/');
}

/**
 * Build a plugin public asset URL respecting GLPI root_doc.
 *
 * @param string $path
 * @return string
 */
function plugin_vehiclescheduler_get_public_url(string $path): string
{
    return plugin_vehiclescheduler_get_root_doc()
        . '/plugins/vehiclescheduler/public/' . ltrim($path, '/');
}


/**
 * Include app-style CSS in page
 */
function plugin_vehiclescheduler_load_theme_css(): void
{
    $css = \PluginVehicleschedulerTheme::generateThemeCSS();
    echo "<style id='vs-theme-dynamic'>{$css}</style>";
}

function plugin_vehiclescheduler_get_public_asset_url(string $relativePath): string
{
    $root_doc = plugin_vehiclescheduler_get_root_doc();

    $absolutePath = GLPI_ROOT . '/plugins/vehiclescheduler/public/' . ltrim($relativePath, '/');
    $version = is_file($absolutePath) ? filemtime($absolutePath) : PLUGIN_VEHICLESCHEDULER_VERSION;

    return $root_doc . '/plugins/vehiclescheduler/public/' . ltrim($relativePath, '/') . '?v=' . $version;
}

/**
 * Resolve a public plugin asset to an absolute filesystem path.
 */
function plugin_vehiclescheduler_get_public_asset_path(string $relativePath): ?string
{
    $basePath = realpath(GLPI_ROOT . '/plugins/vehiclescheduler/public');

    if ($basePath === false) {
        return null;
    }

    $path = realpath($basePath . '/' . ltrim($relativePath, '/'));

    if ($path === false || !is_file($path)) {
        return null;
    }

    if (!str_starts_with($path, $basePath . DIRECTORY_SEPARATOR)) {
        return null;
    }

    return $path;
}

/**
 * Expand a CSS file and its local @import dependencies.
 *
 * GLPI installations behind different roots/proxies can fail to resolve nested
 * @import paths from plugin assets. Keeping CSS in public/css and flattening it
 * here makes page rendering independent from that URL resolution detail.
 *
 * @param string              $relativePath Public asset path inside public/.
 * @param array<string, bool> $loadedFiles  Absolute paths already expanded.
 */
function plugin_vehiclescheduler_expand_css_asset(string $relativePath, array &$loadedFiles = []): string
{
    $path = plugin_vehiclescheduler_get_public_asset_path($relativePath);

    if ($path === null || isset($loadedFiles[$path])) {
        return '';
    }

    $loadedFiles[$path] = true;
    $css = file_get_contents($path);

    if ($css === false) {
        return '';
    }

    $basePublicPath = realpath(GLPI_ROOT . '/plugins/vehiclescheduler/public');
    $currentDir = dirname($path);

    $css = preg_replace_callback(
        '/@import\s+(?:url\()?["\']?([^"\')\s;]+)["\']?\)?\s*;/i',
        static function (array $matches) use ($basePublicPath, $currentDir, &$loadedFiles): string {
            if ($basePublicPath === false) {
                return '';
            }

            $import = trim((string) ($matches[1] ?? ''));

            if (
                $import === ''
                || preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/\/)/i', $import) === 1
            ) {
                return '';
            }

            $importPath = realpath($currentDir . '/' . $import);

            if (
                $importPath === false
                || !is_file($importPath)
                || !str_starts_with($importPath, $basePublicPath . DIRECTORY_SEPARATOR)
            ) {
                return '';
            }

            $relativeImportPath = ltrim(substr($importPath, strlen($basePublicPath)), DIRECTORY_SEPARATOR);
            $relativeImportPath = str_replace(DIRECTORY_SEPARATOR, '/', $relativeImportPath);

            return "\n" . plugin_vehiclescheduler_expand_css_asset($relativeImportPath, $loadedFiles) . "\n";
        },
        $css
    );

    return $css ?? '';
}

/**
 * Loads the base plugin stylesheet and optional page-specific stylesheets.
 *
 * @param array<int, string> $extra_css Relative asset paths inside public/.
 *
 * @return void
 */
function plugin_vehiclescheduler_load_css(array $extra_css = []): void
{
    $loadedCss = [];
    $css = plugin_vehiclescheduler_expand_css_asset('css/app.css', $loadedCss);

    foreach ($extra_css as $css_asset) {
        $css .= "\n" . plugin_vehiclescheduler_expand_css_asset(trim($css_asset), $loadedCss);
    }

    if (trim($css) !== '') {
        echo "<style id='vs-app-css'>\n" . str_ireplace('</style', '<\/style', $css) . "\n</style>";
    } else {
        $css_url = plugin_vehiclescheduler_get_public_asset_url('css/app.css');

        echo "<link rel='stylesheet' href='" . htmlspecialchars($css_url, ENT_QUOTES, 'UTF-8') . "'>";

        foreach ($extra_css as $css_asset) {
            $css_asset = trim($css_asset);

            if ($css_asset === '') {
                continue;
            }

            $extra_css_url = plugin_vehiclescheduler_get_public_asset_url($css_asset);

            echo "<link rel='stylesheet' href='" . htmlspecialchars($extra_css_url, ENT_QUOTES, 'UTF-8') . "'>";
        }
    }

    plugin_vehiclescheduler_load_theme_css();

    plugin_vehiclescheduler_load_script('js/plugin-shell.js');
    plugin_vehiclescheduler_load_script('js/action-confirm.js');
}

function plugin_vehiclescheduler_get_management_url(): string
{
    $root_doc = plugin_vehiclescheduler_get_root_doc();
    return plugin_vehiclescheduler_get_front_url('management.php');
}

function plugin_vehiclescheduler_render_back_to_management(string $label = 'Voltar para Gestão de Frota'): void
{
    $url = plugin_vehiclescheduler_get_management_url();

    echo "<div class='vs-page-toolbar'>";
    echo "   <a href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "' class='vs-btn-back'>";
    echo "      <i class='ti ti-arrow-left'></i>";
    echo "      <span>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</span>";
    echo "   </a>";
    echo "</div>";
}

/**
 * Apply glassmorphism classes to existing GLPI elements
 */
function plugin_vehiclescheduler_enhance_ui(): void
{
    plugin_vehiclescheduler_load_script('js/plugin-enhance-ui.js');
}

function plugin_vehiclescheduler_load_script(string $relativePath): void
{
    static $loadedScripts = [];

    if (isset($loadedScripts[$relativePath])) {
        return;
    }

    $loadedScripts[$relativePath] = true;
    $scriptUrl = plugin_vehiclescheduler_get_public_asset_url($relativePath);

    echo "<script src='" . htmlspecialchars($scriptUrl, ENT_QUOTES, 'UTF-8') . "'></script>";
}

/**
 * Adds a redirect flash message using a semantic kind for front-end styling.
 *
 * Kinds supported by public/js/flash.js:
 * - success
 * - error
 * - warning
 * - info
 *
 * @param string $message Visible message text.
 * @param string $kind    Semantic flash kind.
 * @param bool   $isError Whether GLPI should mark it as an error internally.
 *
 * @return void
 */
function plugin_vehiclescheduler_add_flash(string $message, string $kind = 'info', bool $isError = false): void
{
    $allowedKinds = ['success', 'error', 'warning', 'info'];
    $kind = in_array($kind, $allowedKinds, true) ? $kind : 'info';

    $safeMessage = '<span data-vs-flash-kind="'
        . htmlspecialchars($kind, ENT_QUOTES, 'UTF-8')
        . '">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</span>';

    Session::addMessageAfterRedirect($safeMessage, $isError, $isError ? ERROR : INFO, true);
}

/**
 * Adds a success flash message.
 *
 * @param string $message Visible message text.
 *
 * @return void
 */
function plugin_vehiclescheduler_flash_success(string $message): void
{
    plugin_vehiclescheduler_add_flash($message, 'success', false);
}

/**
 * Adds an info flash message.
 *
 * @param string $message Visible message text.
 *
 * @return void
 */
function plugin_vehiclescheduler_flash_info(string $message): void
{
    plugin_vehiclescheduler_add_flash($message, 'info', false);
}

/**
 * Adds a warning flash message.
 *
 * @param string $message Visible message text.
 *
 * @return void
 */
function plugin_vehiclescheduler_flash_warning(string $message): void
{
    plugin_vehiclescheduler_add_flash($message, 'warning', false);
}

/**
 * Adds an error flash message.
 *
 * @param string $message Visible message text.
 *
 * @return void
 */
function plugin_vehiclescheduler_flash_error(string $message): void
{
    plugin_vehiclescheduler_add_flash($message, 'error', true);
}
