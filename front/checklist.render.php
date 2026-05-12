<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function plugin_vehiclescheduler_render_checklist_form(
    PluginVehicleschedulerChecklist $checklist,
    int $checklistId,
    string $rootDoc
): void {
    $fields = $checklist->fields;
    $types = PluginVehicleschedulerChecklist::getChecklistTypes();
    $canEdit = $checklistId > 0
        ? Session::haveRight('plugin_vehiclescheduler', UPDATE)
        : Session::haveRight('plugin_vehiclescheduler', CREATE);

    $formAction = plugin_vehiclescheduler_get_front_url('checklist.form.php');
    $listUrl = plugin_vehiclescheduler_get_front_url('checklist.php');

    echo "<div class='vs-checklist-form-card'>";
    echo "<div class='vs-checklist-list-header'>";
    echo '<div>';
    echo '<h1><i class="ti ti-checkbox"></i> Template de Checklist</h1>';
    echo '<p class="vs-checklist-list-subtitle">Configure nome, tipo, status e itens do template.</p>';
    echo '</div>';
    echo "<a href='" . plugin_vehiclescheduler_escape($listUrl) . "' class='vs-checklist-list-create'>";
    echo '<i class="ti ti-arrow-left"></i>';
    echo '<span>Voltar para Checklists</span>';
    echo '</a>';
    echo '</div>';

    echo "<div class='vs-checklist-form-content'>";
    echo "<form method='post' action='" . plugin_vehiclescheduler_escape($formAction) . "'>";
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);

    if ($checklistId > 0) {
        echo Html::hidden('id', ['value' => $checklistId]);
    }

    echo "<div class='vs-checklist-form-grid'>";

    echo "<div class='vs-checklist-form-field'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-name'>Nome <span class='red'>*</span></label>";
    echo "<input class='vs-checklist-form-input' type='text' id='vs-checklist-name' name='name' value='"
        . plugin_vehiclescheduler_escape((string) ($fields['name'] ?? '')) . "' maxlength='255' required>";
    echo '</div>';

    echo "<div class='vs-checklist-form-field'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-type'>Tipo <span class='red'>*</span></label>";
    echo "<select class='vs-checklist-form-select' id='vs-checklist-type' name='checklist_type'>";

    foreach ($types as $typeId => $typeLabel) {
        $selected = ((int) ($fields['checklist_type'] ?? PluginVehicleschedulerChecklist::TYPE_DEPARTURE) === (int) $typeId)
            ? ' selected'
            : '';

        echo "<option value='" . (int) $typeId . "'" . $selected . '>'
            . plugin_vehiclescheduler_escape($typeLabel)
            . '</option>';
    }

    echo '</select>';
    echo '</div>';

    echo "<div class='vs-checklist-form-field'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-active'>Ativo</label>";
    echo "<select class='vs-checklist-form-select' id='vs-checklist-active' name='is_active'>";
    echo plugin_vehiclescheduler_render_yes_no_options((int) ($fields['is_active'] ?? 1));
    echo '</select>';
    echo '</div>';

    echo "<div class='vs-checklist-form-field'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-mandatory'>Obrigatorio</label>";
    echo "<select class='vs-checklist-form-select' id='vs-checklist-mandatory' name='is_mandatory'>";
    echo plugin_vehiclescheduler_render_yes_no_options((int) ($fields['is_mandatory'] ?? 1));
    echo '</select>';
    echo '</div>';

    echo "<div class='vs-checklist-form-field vs-checklist-form-field--full'>";
    echo "<label class='vs-checklist-form-label' for='vs-checklist-description'>Descricao</label>";
    echo "<textarea class='vs-checklist-form-textarea' id='vs-checklist-description' name='description' rows='4'>"
        . plugin_vehiclescheduler_escape((string) ($fields['description'] ?? ''))
        . '</textarea>';
    echo '</div>';

    echo '</div>';

    if ($canEdit) {
        echo "<div class='vs-checklist-form-actions'>";

        if ($checklistId > 0) {
            echo "<button type='submit' name='update' class='vs-checklist-form-button vs-checklist-form-button--primary'>Salvar</button>";
            echo "<button type='submit' name='delete' class='vs-checklist-form-button vs-checklist-form-button--danger' data-confirm-message='Excluir este template?'>Excluir</button>";
        } else {
            echo "<button type='submit' name='add' class='vs-checklist-form-button vs-checklist-form-button--primary'>Criar template</button>";
        }

        echo "<a href='" . plugin_vehiclescheduler_escape($listUrl) . "' class='vs-checklist-form-button vs-checklist-form-button--secondary'>Cancelar</a>";
        echo '</div>';
    }

    echo '</form>';
    echo '</div>';
    echo '</div>';
}

function plugin_vehiclescheduler_render_checklist_items_panel(
    int $checklistId,
    array $items,
    int $editingId,
    string $rootDoc,
    bool $canEdit
): void {
    $formAction = plugin_vehiclescheduler_get_front_url('checklistitem.form.php');
    $types = PluginVehicleschedulerChecklistitem::getItemTypes();

    echo "<div class='vs-checklist-form-card vs-checklist-items-panel'>";
    echo "<div class='vs-checklist-items-panel__header'>";
    echo '<h2>Itens do checklist</h2>';
    echo '<p>Monte a sequencia de verificacoes obrigatorias para o template.</p>';
    echo '</div>';
    echo "<div class='vs-checklist-items'>";

    if ($canEdit) {
        echo "<div class='vs-checklist-items__editor'>";
        echo "<h3 class='vs-checklist-items__title'>Adicionar item</h3>";
        echo "<form method='post' action='" . plugin_vehiclescheduler_escape($formAction) . "'>";
        echo Html::hidden('plugin_vehiclescheduler_checklists_id', ['value' => $checklistId]);
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo "<div class='vs-checklist-items__grid'>";
        echo "<div class='vs-checklist-items__field'>";
        echo "<label for='vs-checklist-item-description'>Descricao <span class='red'>*</span></label>";
        echo "<input type='text' id='vs-checklist-item-description' name='description' placeholder='Ex: Veiculo esta limpo?' maxlength='255' required>";
        echo '</div>';
        echo "<div class='vs-checklist-items__field'>";
        echo "<label for='vs-checklist-item-type'>Tipo</label>";
        echo "<select id='vs-checklist-item-type' name='item_type' class='vs-checklist-items__select'>";

        foreach ($types as $typeId => $typeLabel) {
            echo "<option value='" . (int) $typeId . "'>" . plugin_vehiclescheduler_escape($typeLabel) . '</option>';
        }

        echo '</select>';
        echo '</div>';
        echo "<div class='vs-checklist-items__field'>";
        echo "<button type='submit' name='add' class='vs-checklist-items__button vs-checklist-items__button--primary'>Adicionar</button>";
        echo '</div>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
    }

    if (empty($items)) {
        echo "<div class='vs-checklist-items__empty'>";
        echo "<div class='vs-checklist-items__empty-icon'>+</div>";
        echo "<div class='vs-checklist-items__empty-title'>Nenhum item adicionado</div>";
        echo '</div>';
        echo '</div>';
        echo '</div>';

        return;
    }

    echo "<div class='vs-checklist-items__list'>";

    foreach ($items as $item) {
        $itemId = (int) ($item['id'] ?? 0);
        $description = plugin_vehiclescheduler_escape((string) ($item['description'] ?? ''));
        $typeLabel = plugin_vehiclescheduler_escape($types[(int) ($item['item_type'] ?? PluginVehicleschedulerChecklistitem::TYPE_CHECKBOX)] ?? '-');
        $editUrl = plugin_vehiclescheduler_get_front_url('checklist.form.php') . '?id=' . $checklistId . '&edit_item=' . $itemId;
        $deleteUrl = $formAction . '?id=' . $itemId . '&delete=1';

        if ($canEdit && $editingId === $itemId) {
            echo "<div class='vs-checklist-items__card--editing'>";
            echo "<form method='post' action='" . plugin_vehiclescheduler_escape($formAction) . "'>";
            echo Html::hidden('id', ['value' => $itemId]);
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo "<div class='vs-checklist-items__grid vs-checklist-items__grid--editing'>";
            echo "<div class='vs-checklist-items__field'>";
            echo "<label for='vs-checklist-item-edit-" . $itemId . "'>Descricao</label>";
            echo "<input type='text' id='vs-checklist-item-edit-" . $itemId . "' name='description' value='" . $description . "' maxlength='255' required>";
            echo '</div>';
            echo "<div class='vs-checklist-items__field'>";
            echo "<label for='vs-checklist-item-type-edit-" . $itemId . "'>Tipo</label>";
            echo "<select id='vs-checklist-item-type-edit-" . $itemId . "' name='item_type' class='vs-checklist-items__select'>";

            foreach ($types as $typeId => $label) {
                $selected = ((int) ($item['item_type'] ?? PluginVehicleschedulerChecklistitem::TYPE_CHECKBOX) === (int) $typeId)
                    ? ' selected'
                    : '';

                echo "<option value='" . (int) $typeId . "'" . $selected . '>'
                    . plugin_vehiclescheduler_escape($label)
                    . '</option>';
            }

            echo '</select>';
            echo '</div>';
            echo "<div class='vs-checklist-items__field'>";
            echo "<button type='submit' name='update' class='vs-checklist-items__button vs-checklist-items__button--primary'>Salvar</button>";
            echo '</div>';
            echo "<div class='vs-checklist-items__field'>";
            echo "<a href='" . plugin_vehiclescheduler_escape(plugin_vehiclescheduler_get_front_url('checklist.form.php') . '?id=' . $checklistId) . "' class='vs-checklist-items__link vs-checklist-items__link--secondary'>Cancelar</a>";
            echo '</div>';
            echo '</div>';
            echo '</form>';
            echo '</div>';

            continue;
        }

        echo "<div class='vs-checklist-items__card'>";
        echo "<div class='vs-checklist-items__content'>";
        echo "<div class='vs-checklist-items__description'>" . $description . '</div>';
        echo "<span class='vs-checklist-items__badge'>" . $typeLabel . '</span>';
        echo '</div>';

        if ($canEdit) {
            echo "<div class='vs-checklist-items__actions'>";
            echo "<a href='" . plugin_vehiclescheduler_escape($editUrl) . "' class='vs-checklist-items__link vs-checklist-items__link--edit'>Editar</a>";
            echo "<a href='" . plugin_vehiclescheduler_escape($deleteUrl) . "' class='vs-checklist-items__link vs-checklist-items__link--danger' data-confirm-message='Excluir este item?'>Excluir</a>";
            echo '</div>';
        }

        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
    echo '</div>';
}

function plugin_vehiclescheduler_render_yes_no_options(int $selected): string
{
    $html = '';

    foreach ([1 => 'Sim', 0 => 'Nao'] as $value => $label) {
        $isSelected = $selected === $value ? ' selected' : '';
        $html .= "<option value='" . $value . "'" . $isSelected . '>'
            . plugin_vehiclescheduler_escape($label)
            . '</option>';
    }

    return $html;
}

function plugin_vehiclescheduler_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
