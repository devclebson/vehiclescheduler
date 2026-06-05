<?php
/**
 * Processamento de permissões de perfil
 */
include('../../../inc/includes.php');
Session::checkRight('profile', READ);

if (isset($_POST['update'])) {
    global $DB;
    
    $profile_id = (int)$_POST['profiles_id'];
    $requester_access  = isset($_POST['requester_access']) && $_POST['requester_access'] == 1 ? 1 : 0;
    $management_access = $_POST['management_access'] ?? '';
    $can_approve       = isset($_POST['can_approve']) && $_POST['can_approve'] == 1 ? 1 : 0;
    
    $data = [
        'requester_access'  => $requester_access,
        'management_access' => $management_access,
        'can_approve'       => $can_approve,
    ];
    
    // Verificar se já existe
    $exists = $DB->request([
        'FROM'  => 'glpi_plugin_vehiclescheduler_profiles',
        'WHERE' => ['profiles_id' => $profile_id]
    ])->current();
    
    if ($exists) {
        // Atualizar
        $DB->update(
            'glpi_plugin_vehiclescheduler_profiles',
            $data,
            ['profiles_id' => $profile_id]
        );
        Session::addMessageAfterRedirect('Permissões atualizadas com sucesso!', false, INFO);
    } else {
        // Inserir
        $data['profiles_id'] = $profile_id;
        $DB->insert('glpi_plugin_vehiclescheduler_profiles', $data);
        Session::addMessageAfterRedirect('Permissões criadas com sucesso!', false, INFO);
    }
}

Html::back();
