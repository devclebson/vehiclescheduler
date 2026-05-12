<?php

/**
 * Theme configuration page.
 */

include_once(__DIR__ . '/../inc/common.inc.php');

Session::checkRight('config', UPDATE);

if (isset($_POST['save_theme'])) {
    $themeCode = PluginVehicleschedulerInput::enum(
        $_POST,
        'theme_code',
        array_keys(PluginVehicleschedulerTheme::getAllThemes()),
        PluginVehicleschedulerTheme::THEME_BLUE
    );

    if (PluginVehicleschedulerTheme::saveTheme($themeCode)) {
        Session::addMessageAfterRedirect('Tema salvo com sucesso!', false, INFO);
    }

    Html::redirect($_SERVER['PHP_SELF']);
}

$currentTheme = PluginVehicleschedulerTheme::getCurrentTheme();
$allThemes    = PluginVehicleschedulerTheme::getAllThemes();

Html::header('Configuração de Temas', $_SERVER['PHP_SELF'], 'config', 'plugins');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();
?>
<div class="vs-theme-config-page">
    <section class="vs-theme-config-hero">
        <div>
            <p class="vs-theme-config-hero__eyebrow">Personalização visual</p>
            <h1>Configuração de temas</h1>
            <p>Escolha uma paleta que melhore a leitura operacional sem perder contraste entre claro e escuro.</p>
        </div>
        <div class="vs-theme-config-hero__tip">
            <strong>Dica</strong>
            <span>O toggle claro/escuro continua disponível nas telas do plugin.</span>
        </div>
    </section>

    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" class="vs-theme-config-form">
        <input type="hidden" name="_glpi_csrf_token" value="<?= Session::getNewCSRFToken() ?>">

        <section class="vs-theme-grid">
            <?php foreach ($allThemes as $code => $theme): ?>
                <?php $checked = $code === $currentTheme ? ' checked' : ''; ?>
                <label class="vs-theme-card">
                    <div class="vs-theme-card__selector">
                        <input type="radio" name="theme_code" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"<?= $checked ?>>
                        <div>
                            <strong><?= htmlspecialchars($theme['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="vs-theme-card__previews">
                        <div class="vs-theme-card__preview vs-theme-card__preview--<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>-light">
                            <span>☀ Claro</span>
                        </div>
                        <div class="vs-theme-card__preview vs-theme-card__preview--<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>-dark">
                            <span>🌙 Escuro</span>
                        </div>
                    </div>
                </label>
            <?php endforeach; ?>
        </section>

        <footer class="vs-theme-config-form__footer">
            <button type="submit" name="save_theme" class="vs-theme-config-save">Salvar tema</button>
        </footer>
    </form>
</div>
<?php Html::footer(); ?>
