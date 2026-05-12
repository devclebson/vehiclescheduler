<?php
/**
 * Theme Manager - 8 TEMAS UNIFICADOS
 * Cada tema tem modo claro/escuro - toggle decide qual usar
 */
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerTheme extends CommonDBTM {

    static $rightname = 'plugin_vehiclescheduler';

    const THEME_PURPLE  = 'purple';
    const THEME_BLUE    = 'blue';
    const THEME_GREEN   = 'green';
    const THEME_ORANGE  = 'orange';
    const THEME_RED     = 'red';
    const THEME_TEAL    = 'teal';
    const THEME_NEUTRAL = 'neutral';
    const THEME_PREMIUM = 'premium';

    static function getAllThemes() {
        return [
            self::THEME_PURPLE => [
                'name' => '💜 Roxo',
                'primary' => '#8b5cf6',
                'secondary' => '#a78bfa',
                'light' => [
                    'bg_start' => '#caaae9',
                    'bg_end' => '#f7f7f736',
                    'card_bg' => 'rgba(255,255,255,0.95)',
                    'text_color' => '#3b0764',
                ],
                'dark' => [
                    'bg_start' => '#393773',
                    'bg_end' => '#554487',
                    'card_bg' => 'rgba(30,41,59,0.85)',
                    'text_color' => '#e9d5ff',
                ],
            ],
            
            self::THEME_BLUE => [
                'name' => '💙 Azul',
                'primary' => '#3b82f6',
                'secondary' => '#60a5fa',
                'light' => [
                    'bg_start' => '#4587db',
                    'bg_end' => '#dbeafe',
                    'card_bg' => 'rgba(255,255,255,0.95)',
                    'text_color' => '#1e3a8a',
                ],
                'dark' => [
                    'bg_start' => '#0c4a6e',
                    'bg_end' => '#000216',
                    'card_bg' => 'rgba(30,41,59,0.85)',
                    'text_color' => '#dbeafe',
                ],
            ],
            
            self::THEME_GREEN => [
                'name' => '💚 Verde',
                'primary' => '#10b981',
                'secondary' => '#34d399',
                'light' => [
                    'bg_start' => '#f0fdf4',
                    'bg_end' => '#dcfce7',
                    'card_bg' => 'rgba(255,255,255,0.95)',
                    'text_color' => '#064e3b',
                ],
                'dark' => [
                    'bg_start' => '#064e3b',
                    'bg_end' => '#065f46',
                    'card_bg' => 'rgba(30,41,59,0.85)',
                    'text_color' => '#d1fae5',
                ],
            ],
            
            self::THEME_ORANGE => [
                'name' => '🧡 Laranja',
                'primary' => '#f97316',
                'secondary' => '#fb923c',
                'light' => [
                    'bg_start' => '#fd7e14',
                    'bg_end' => '#ffedd57d',
                    'card_bg' => 'rgba(255,255,255,0.95)',
                    'text_color' => '#7c2d12',
                ],
                'dark' => [
                    'bg_start' => '#dc3545',
                    'bg_end' => '#fd7e14',
                    'card_bg' => 'rgba(30,41,59,0.85)',
                    'text_color' => '#fed7aa',
                ],
            ],
            
            self::THEME_RED => [
                'name' => '❤️ Vermelho',
                'primary' => '#ef4444',
                'secondary' => '#f87171',
                'light' => [
                    'bg_start' => '#fd000030',
                    'bg_end' => '#ff0808eb',
                    'card_bg' => 'rgba(255,255,255,0.95)',
                    'text_color' => '#7f1d1d',
                ],
                'dark' => [
                    'bg_start' => '#b91414',
                    'bg_end' => '#770303',
                    'card_bg' => 'rgba(30,41,59,0.85)',
                    'text_color' => '#fecaca',
                ],
            ],
            
            self::THEME_TEAL => [
                'name' => '💎 Turquesa',
                'primary' => '#14b8a6',
                'secondary' => '#2dd4bf',
                'light' => [
                    'bg_start' => '#f0fdfa',
                    'bg_end' => '#ccfbf1',
                    'card_bg' => 'rgba(255,255,255,0.95)',
                    'text_color' => '#134e4a',
                ],
                'dark' => [
                    'bg_start' => '#134e4a',
                    'bg_end' => '#115e59',
                    'card_bg' => 'rgba(30,41,59,0.85)',
                    'text_color' => '#ccfbf1',
                ],
            ],
            
            self::THEME_NEUTRAL => [
                'name' => '⚪ Neutro',
                'primary' => '#6366f1',
                'secondary' => '#8b5cf6',
                'light' => [
                    'bg_start' => '#f8fafc',
                    'bg_end' => '#f1f5f9',
                    'card_bg' => 'rgba(255,255,255,0.95)',
                    'text_color' => '#0f172a',
                ],
                'dark' => [
                    'bg_start' => '#0f172a',
                    'bg_end' => '#1e293b',
                    'card_bg' => 'rgba(30,41,59,0.85)',
                    'text_color' => '#e2e8f0',
                ],
            ],
            
            self::THEME_PREMIUM => [
                'name' => '✨ Premium',
                'primary' => '#fbbf24',
                'secondary' => '#f59e0b',
                'light' => [
                    'bg_start' => '#ffee80',
                    'bg_end' => '#fef3c7',
                    'card_bg' => 'rgba(255,255,255,0.95)',
                    'text_color' => '#78350f',
                ],
                'dark' => [
                    'bg_start' => '#78350f',
                    'bg_end' => '#FFBF00',
                    'card_bg' => 'rgba(30,41,59,0.85)',
                    'text_color' => '#fef3c7',
                ],
            ],
        ];
    }

    static function getCurrentTheme() {
        global $DB;
        $user_id = Session::getLoginUserID();
        if (!$user_id) return self::THEME_BLUE;
        $result = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_themes', 'WHERE' => ['users_id' => $user_id]])->current();
        if ($result && isset($result['theme_code'])) {
            $themes = self::getAllThemes();
            if (isset($themes[$result['theme_code']])) return $result['theme_code'];
        }
        return self::THEME_BLUE;
    }

    static function saveTheme($theme_code) {
        global $DB;
        $user_id = Session::getLoginUserID();
        if (!$user_id) return false;
        $themes = self::getAllThemes();
        if (!isset($themes[$theme_code])) $theme_code = self::THEME_BLUE;
        $exists = $DB->request(['FROM' => 'glpi_plugin_vehiclescheduler_themes', 'WHERE' => ['users_id' => $user_id]])->current();
        if ($exists) {
            $DB->update('glpi_plugin_vehiclescheduler_themes', ['theme_code' => $theme_code, 'date_mod' => date('Y-m-d H:i:s')], ['users_id' => $user_id]);
        } else {
            $DB->insert('glpi_plugin_vehiclescheduler_themes', ['users_id' => $user_id, 'theme_code' => $theme_code, 'date_creation' => date('Y-m-d H:i:s')]);
        }
        return true;
    }

    static function generateThemeCSS() {
        $theme_code = self::getCurrentTheme();
        $themes = self::getAllThemes();
        $theme = $themes[$theme_code] ?? $themes[self::THEME_BLUE];

        $css = "
        :root {
            --vs-primary: {$theme['primary']};
            --vs-secondary: {$theme['secondary']};
            --vs-bg-light-start: {$theme['light']['bg_start']};
            --vs-bg-light-end: {$theme['light']['bg_end']};
            --vs-bg-dark-start: {$theme['dark']['bg_start']};
            --vs-bg-dark-end: {$theme['dark']['bg_end']};
            --vs-card-light-bg: {$theme['light']['card_bg']};
            --vs-card-dark-bg: {$theme['dark']['card_bg']};
            --vs-text-light: {$theme['light']['text_color']};
            --vs-text-dark: {$theme['dark']['text_color']};
        }

        /* MODO CLARO (padrão) */
        body.vs-app-body {
            background: linear-gradient(135deg, var(--vs-bg-light-start), var(--vs-bg-light-end)) !important;
            background-attachment: fixed !important;
        }
        
        /* CARD CLARO */
        body.vs-app-body .card {
            background: #ffffff !important;
            border: 1px solid rgba(98, 105, 118, 0.16) !important;
        }
        
        .vs-glass-card, .vs-hero, .vs-circle {
            background: var(--vs-card-light-bg) !important;
            color: var(--vs-text-light) !important;
        }
        
        .vs-glass-card h1, .vs-glass-card h2, .vs-glass-card h3, .vs-title {
            color: var(--vs-text-light) !important;
        }
        
        .vs-glass-card input, .vs-glass-card textarea, .vs-glass-card select {
            background: #ffffff !important;
            color: #1e293b !important;
            border: 1px solid rgba(30,41,59,0.2) !important;
        }
        
        /* MODO ESCURO (classe .vs-dark no body) */
        body.vs-app-body.vs-dark,
        .vs-dark body.vs-app-body {
            background: linear-gradient(135deg, var(--vs-bg-dark-start), var(--vs-bg-dark-end)) !important;
        }
        
        /* CARD ESCURO */
        body.vs-app-body.vs-dark .card,
        .vs-dark body.vs-app-body .card {
            background: rgba(30,41,59,0.85) !important;
            border: 1px solid rgba(255,255,255,0.16) !important;
            color: #e5e7eb !important;
        }
        
        body.vs-app-body.vs-dark .vs-glass-card,
        body.vs-app-body.vs-dark .vs-hero,
        body.vs-app-body.vs-dark .vs-circle,
        .vs-dark .vs-glass-card,
        .vs-dark .vs-hero,
        .vs-dark .vs-circle {
            background: var(--vs-card-dark-bg) !important;
            color: var(--vs-text-dark) !important;
        }
        
        body.vs-app-body.vs-dark .vs-glass-card h1,
        body.vs-app-body.vs-dark .vs-glass-card h2,
        body.vs-app-body.vs-dark .vs-glass-card h3,
        body.vs-app-body.vs-dark .vs-title,
        .vs-dark .vs-glass-card h1,
        .vs-dark .vs-glass-card h2,
        .vs-dark .vs-glass-card h3,
        .vs-dark .vs-title {
            color: var(--vs-text-dark) !important;
        }
        
        body.vs-app-body.vs-dark .vs-glass-card input,
        body.vs-app-body.vs-dark .vs-glass-card textarea,
        body.vs-app-body.vs-dark .vs-glass-card select,
        .vs-dark .vs-glass-card input,
        .vs-dark .vs-glass-card textarea,
        .vs-dark .vs-glass-card select {
            background: rgba(0,0,0,0.3) !important;
            color: #e5e7eb !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
        }
        
        .vs-btn-primary {
            background: linear-gradient(135deg, var(--vs-primary), var(--vs-secondary)) !important;
            color: #ffffff !important;
        }
        ";

        return $css;
    }

    static function getThemePairs() {
        return [];
    }
}