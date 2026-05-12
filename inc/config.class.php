<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginVehicleschedulerConfig extends CommonDBTM
{
    public static function getTable($classname = null): string
    {
        return 'glpi_plugin_vehiclescheduler_configs';
    }

    public static function ensureTable(): bool
    {
        global $DB;

        if ($DB->tableExists(self::getTable())) {
            return true;
        }

        $defaultCharset = DBConnection::getDefaultCharset();
        $defaultCollation = DBConnection::getDefaultCollation();

        return (bool) $DB->doQuery("
            CREATE TABLE IF NOT EXISTS `" . self::getTable() . "` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `config_key` varchar(100) NOT NULL,
                `config_value` varchar(255) NOT NULL DEFAULT '',
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `config_key` (`config_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$defaultCharset} COLLATE={$defaultCollation}
        ");
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        global $DB;

        if (!self::ensureTable()) {
            return $default;
        }

        $row = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['config_key' => $key],
        ])->current();

        if (!$row) {
            return $default;
        }

        return (int) ($row['config_value'] ?? ($default ? 1 : 0)) === 1;
    }

    public static function setBool(string $key, bool $value): bool
    {
        global $DB;

        if (!self::ensureTable()) {
            return false;
        }

        $exists = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['config_key' => $key],
        ])->current();

        $payload = [
            'config_key'   => $key,
            'config_value' => $value ? '1' : '0',
            'date_mod'     => date('Y-m-d H:i:s'),
        ];

        if ($exists) {
            return (bool) $DB->update(self::getTable(), $payload, ['config_key' => $key]);
        }

        $payload['date_creation'] = date('Y-m-d H:i:s');

        return (bool) $DB->insert(self::getTable(), $payload);
    }

    public static function shouldAutoOpenDepartureChecklistAfterApproval(): bool
    {
        return self::getBool('auto_departure_checklist_after_approval', true);
    }
}
