<?php

if (!defined('GLPI_ROOT')) {
    die("Acesso direto nao permitido");
}

class PluginVehicleschedulerChecklistitem extends CommonDBChild
{
    public static $itemtype = 'PluginVehicleschedulerChecklist';
    public static $items_id = 'plugin_vehiclescheduler_checklists_id';
    public $dohistory = true;
    public static $rightname = 'plugin_vehiclescheduler';

    public const TYPE_CHECKBOX = 1;
    public const TYPE_TEXT = 2;
    public const TYPE_NUMBER = 3;
    public const TYPE_PHOTO = 4;
    public const TYPE_SIGNATURE = 5;

    public static function getTypeName($nb = 0)
    {
        return ($nb === 1) ? 'Item' : 'Itens';
    }

    public static function getIcon()
    {
        return 'ti ti-list-check';
    }

    public static function getItemTypes(): array
    {
        return [
            self::TYPE_CHECKBOX  => 'Sim/Nao',
            self::TYPE_TEXT      => 'Texto',
            self::TYPE_NUMBER    => 'Numero',
            self::TYPE_PHOTO     => 'Foto',
            self::TYPE_SIGNATURE => 'Assinatura',
        ];
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->normalizeInput($input);

        if ($input['description'] === '') {
            Session::addMessageAfterRedirect('A descricao e obrigatoria.', false, ERROR);

            return false;
        }

        if (!isset($input['position']) || (int) $input['position'] <= 0) {
            global $DB;

            $max = $DB->request([
                'SELECT' => ['MAX' => 'position AS max_pos'],
                'FROM'   => $this->getTable(),
                'WHERE'  => [
                    'plugin_vehiclescheduler_checklists_id' => (int) $input['plugin_vehiclescheduler_checklists_id'],
                ],
            ])->current();

            $input['position'] = (int) ($max['max_pos'] ?? 0) + 1;
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->normalizeInput($input);

        if (empty($input['id'])) {
            Session::addMessageAfterRedirect('Item invalido.', false, ERROR);

            return false;
        }

        if ($input['description'] === '') {
            Session::addMessageAfterRedirect('A descricao e obrigatoria.', false, ERROR);

            return false;
        }

        return $input;
    }

    private function normalizeInput(array $input): array
    {
        $input['description'] = PluginVehicleschedulerInput::string($input, 'description', 255);
        $input['item_type'] = PluginVehicleschedulerInput::int(
            $input,
            'item_type',
            self::TYPE_CHECKBOX,
            self::TYPE_CHECKBOX,
            self::TYPE_SIGNATURE
        );
        $input['is_mandatory'] = PluginVehicleschedulerInput::bool($input, 'is_mandatory', true);

        if (array_key_exists('id', $input)) {
            $input['id'] = PluginVehicleschedulerInput::int($input, 'id', 0, 0);
        }

        if (array_key_exists('plugin_vehiclescheduler_checklists_id', $input)) {
            $input['plugin_vehiclescheduler_checklists_id'] = PluginVehicleschedulerInput::int(
                $input,
                'plugin_vehiclescheduler_checklists_id',
                0,
                0
            );
        }

        if (array_key_exists('position', $input)) {
            $input['position'] = PluginVehicleschedulerInput::int($input, 'position', 0, 0);
        }

        return $input;
    }
}
