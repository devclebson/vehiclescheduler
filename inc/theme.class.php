<?php
/**
 * Theme Manager - Sistema de Temas do Plugin
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerTheme extends CommonDBTM {
    
    static $rightname = 'plugin_vehiclescheduler';
    
    // Temas disponíveis
    const THEME_PURPLE_DARK = 'purple-dark';
    const THEME_BLUE_LIGHT  = 'blue-light';
    const THEME_GREEN_DARK  = 'green-dark';
    const THEME_ORANGE_LIGHT= 'orange-light';
    
    /**
     * Retorna todos os temas disponíveis
     */
    static function getAllThemes() {
        return [
            self::THEME_PURPLE_DARK => [
                'name'       => 'Roxo Escuro (Padrão)',
                'type'       => 'dark',
                'primary'    => '#667eea',
                'secondary'  => '#764ba2',
                'bg_start'   => '#667eea',
                'bg_end'     => '#764ba2',
                'card_bg'    => 'rgba(255, 255, 255, 0.15)',
                'text_color' => '#ffffff',
            ],
            self::THEME_BLUE_LIGHT => [
                'name'       => 'Azul Claro',
                'type'       => 'light',
                'primary'    => '#4f46e5',
                'secondary'  => '#06b6d4',
                'bg_start'   => '#f0f9ff',
                'bg_end'     => '#e0f2fe',
                'card_bg'    => 'rgba(255, 255, 255, 0.95)',
                'text_color' => '#1e293b',
            ],
            self::THEME_GREEN_DARK => [
                'name'       => 'Verde Escuro',
                'type'       => 'dark',
                'primary'    => '#10b981',
                'secondary'  => '#059669',
                'bg_start'   => '#064e3b',
                'bg_end'     => '#047857',
                'card_bg'    => 'rgba(255, 255, 255, 0.12)',
                'text_color' => '#ffffff',
            ],
            self::THEME_ORANGE_LIGHT => [
                'name'       => 'Laranja Claro',
                'type'       => 'light',
                'primary'    => '#f97316',
                'secondary'  => '#fb923c',
                'bg_start'   => '#fff7ed',
                'bg_end'     => '#fed7aa',
                'card_bg'    => 'rgba(255, 255, 255, 0.9)',
                'text_color' => '#1e293b',
            ],
        ];
    }
    
    /**
     * Retorna o tema atual do usuário
     */
    static function getCurrentTheme() {
        global $DB;
        
        $user_id = Session::getLoginUserID();
        if (!$user_id) {
            return self::THEME_PURPLE_DARK;
        }
        
        $result = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_themes',
            'WHERE' => ['users_id' => $user_id]
        ])->current();
        
        if ($result && isset($result['theme_code'])) {
            return $result['theme_code'];
        }
        
        return self::THEME_PURPLE_DARK;
    }
    
    /**
     * Salva o tema escolhido pelo usuário
     */
    static function saveTheme($theme_code) {
        global $DB;
        
        $user_id = Session::getLoginUserID();
        if (!$user_id) {
            return false;
        }
        
        // Verificar se já existe
        $exists = $DB->request([
            'FROM'  => 'glpi_plugin_vehiclescheduler_themes',
            'WHERE' => ['users_id' => $user_id]
        ])->current();
        
        if ($exists) {
            // Atualizar
            $DB->update(
                'glpi_plugin_vehiclescheduler_themes',
                ['theme_code' => $theme_code, 'date_mod' => date('Y-m-d H:i:s')],
                ['users_id' => $user_id]
            );
        } else {
            // Inserir
            $DB->insert('glpi_plugin_vehiclescheduler_themes', [
                'users_id'   => $user_id,
                'theme_code' => $theme_code,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);
        }
        
        return true;
    }
    
    /**
     * Gera o CSS do tema atual
     */
    static function generateThemeCSS() {
        $theme_code = self::getCurrentTheme();
        $themes = self::getAllThemes();
        $theme = $themes[$theme_code] ?? $themes[self::THEME_PURPLE_DARK];
        
        $css = "
        :root {
            --vs-primary: {$theme['primary']};
            --vs-secondary: {$theme['secondary']};
            --vs-bg-start: {$theme['bg_start']};
            --vs-bg-end: {$theme['bg_end']};
            --vs-card-bg: {$theme['card_bg']};
            --vs-text-color: {$theme['text_color']};
        }
        
        body.vs-app-body {
            background: linear-gradient(135deg, var(--vs-bg-start) 0%, var(--vs-bg-end) 100%) !important;
            background-attachment: fixed !important;
            color: var(--vs-text-color) !important;
        }
        
        .vs-glass-card {
            background: var(--vs-card-bg) !important;
            color: var(--vs-text-color) !important;
        }
        
        .vs-btn-primary {
            background: linear-gradient(135deg, var(--vs-primary) 0%, var(--vs-secondary) 100%) !important;
        }
        
        .vs-badge-primary {
            background: linear-gradient(135deg, var(--vs-primary) 0%, var(--vs-secondary) 100%) !important;
        }
        ";
        
        return $css;
    }
}
