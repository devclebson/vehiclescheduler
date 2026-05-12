<?php

/**
 * Processamento de permissões de perfil
 */

include_once(__DIR__ . '/../inc/common.inc.php');

Session::checkRight('profile', UPDATE);

if (!isset($_POST['update'])) {
    Html::back();
    exit;
}

try {
    $data = PluginVehicleschedulerProfile::saveProfileRights($_POST);
} catch (RuntimeException $e) {
    Session::addMessageAfterRedirect($e->getMessage(), true, ERROR);
    Html::back();
    exit;
} catch (Throwable $e) {
    Toolbox::logInFile(
        'php-errors',
        '[vehiclescheduler] Erro ao salvar permissões do perfil: '
            . $e->getMessage()
            . PHP_EOL
    );

    Session::addMessageAfterRedirect(
        'Não foi possível salvar as permissões do perfil.',
        true,
        ERROR
    );

    Html::back();
    exit;
}

Session::addMessageAfterRedirect(
    'Permissões atualizadas com sucesso!',
    false,
    INFO
);

Html::redirect(
    plugin_vehiclescheduler_get_root_doc()
        . '/front/profile.form.php?id='
        . (int) $data['profiles_id']
        . '&forcetab=PluginVehicleschedulerProfile$1'
);
exit;
