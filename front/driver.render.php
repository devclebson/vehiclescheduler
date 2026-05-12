<?php
// front/driver.render.php

/**
 * Driver form renderer.
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Escapes HTML output for safe rendering.
 *
 * @param string|null $value Raw value.
 *
 * @return string
 */
function vs_driver_render_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Renders the CNH expiry badge for the current form.
 *
 * @param array<string, int|string|null> $status Expiry status payload.
 *
 * @return string
 */
function vs_driver_render_expiry_badge(array $status): string
{
    $badge = PluginVehicleschedulerDriver::getCNHExpiryBadgeData($status);

    return '<span class="vs-driver-expiry-badge ' . vs_driver_render_escape((string) ($badge['class'] ?? '')) . '">'
        . vs_driver_render_escape((string) ($badge['label'] ?? 'Sem data'))
        . '</span>';
}

/**
 * Returns the icon class associated with a CNH category.
 *
 * @param string $category CNH category code.
 *
 * @return string
 */
function vs_driver_render_category_icon(string $category): string
{
    $icons = [
        'A' => 'ti ti-motorbike',
        'B' => 'ti ti-car',
        'D' => 'ti ti-truck',
    ];

    return $icons[$category] ?? 'ti ti-license';
}

/**
 * Renders the driver form body inside the GLPI form wrapper.
 *
 * @param PluginVehicleschedulerDriver $driver Driver instance.
 * @param int                          $id     Current driver identifier.
 *
 * @return void
 */
function vs_render_driver_form(PluginVehicleschedulerDriver $driver, int $id): void
{
    $selectedCategories = PluginVehicleschedulerDriver::getDriverCNHCategoryList(
        $driver->fields['cnh_category'] ?? ''
    );

    $badgeHtml = '';

    if ($id > 0 && !empty($driver->fields['cnh_expiry'])) {
        $badgeHtml = vs_driver_render_expiry_badge(
            PluginVehicleschedulerDriver::getCNHExpiryStatus((string) ($driver->fields['cnh_expiry'] ?? ''))
        );
    }
    ?>
    <div class="vs-driver-wrap" data-vs-driver-form>
        <div class="vs-driver-surface">
            <div class="vs-driver-card">
                <div class="vs-driver-head">
                    <div>
                        <h3 class="vs-driver-title">
                            <i class="ti ti-steering-wheel"></i>
                            Cadastro de Motorista
                        </h3>
                        <div class="vs-driver-sub">
                            Campos essenciais para gestão de frota com privacidade por padrão.
                        </div>
                    </div>
                    <div class="vs-driver-pill">
                        <span class="dot"></span>
                        Motoristas
                    </div>
                </div>

                <div class="vs-driver-privacy">
                    <strong>Aviso LGPD:</strong>
                    Coletamos apenas dados mínimos necessários. Não armazenamos CPF, RG, número da CNH
                    ou biometria. Base legal: execução de contrato e legítimo interesse operacional.
                </div>

                <div class="vs-form-feedback" data-driver-validation hidden></div>

                <div class="vs-driver-form-grid">
                    <div class="vs-driver-field vs-driver-field--user">
                        <div class="vs-driver-label">
                            Usuário (GLPI) <span class="red">*</span>
                        </div>
                        <?php
                        User::dropdown([
                            'name'   => 'users_id',
                            'value'  => (int) ($driver->fields['users_id'] ?? 0),
                            'entity' => (int) ($driver->fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
                            'right'  => 'all',
                        ]);
                        ?>
                        <div class="vs-driver-hint">
                            O nome do motorista será preenchido automaticamente a partir do usuário selecionado.
                        </div>
                    </div>

                    <div class="vs-driver-field vs-driver-field--categories">
                        <div class="vs-driver-label">
                            Categorias CNH <span class="red">*</span>
                        </div>
                        <div class="vs-driver-category-grid" data-driver-category-group>
                            <?php foreach (PluginVehicleschedulerDriver::getDriverSelectableCNHCategories() as $category => $label) : ?>
                                <label class="vs-driver-category-option">
                                    <input
                                        type="checkbox"
                                        name="cnh_category[]"
                                        value="<?= vs_driver_render_escape($category) ?>"
                                        <?= in_array($category, $selectedCategories, true) ? 'checked' : '' ?>
                                    >
                                    <span class="vs-driver-category-option__icon">
                                        <i class="<?= vs_driver_render_escape(vs_driver_render_category_icon($category)) ?>"></i>
                                    </span>
                                    <span class="vs-driver-category-option__meta">
                                        <span class="vs-driver-category-option__code">
                                            <?= vs_driver_render_escape($category) ?>
                                        </span>
                                        <span class="vs-driver-category-option__text">
                                            <?= vs_driver_render_escape($label) ?>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="vs-driver-hint">
                            Regra do MVP: moto exige A, carro aceita B ou D, e caminhão/van exige D.
                        </div>
                    </div>

                    <div class="vs-driver-field vs-driver-field--registration">
                        <div class="vs-driver-label">
                            Matrícula Interna <span class="vs-driver-hint-inline">opcional</span>
                        </div>
                        <?= Html::input('registration', [
                            'value'       => $driver->fields['registration'] ?? '',
                            'size'        => 18,
                            'placeholder' => 'ex: EMP-0042',
                        ]) ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--group">
                        <div class="vs-driver-label">
                            Departamento/Setor <span class="vs-driver-hint-inline">opcional</span>
                        </div>
                        <?php
                        Group::dropdown([
                            'name'   => 'groups_id',
                            'value'  => (int) ($driver->fields['groups_id'] ?? 0),
                            'entity' => (int) ($driver->fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0)),
                        ]);
                        ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--phone">
                        <div class="vs-driver-label">
                            Telefone para Contato <span class="vs-driver-hint-inline">opcional</span>
                        </div>
                        <?= Html::input('contact_phone', [
                            'value'       => $driver->fields['contact_phone'] ?? '',
                            'size'        => 18,
                            'placeholder' => '(00) 00000-0000',
                        ]) ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--expiry">
                        <div class="vs-driver-label">
                            <span>Vencimento da CNH <span class="red">*</span></span>
                            <span class="vs-driver-badge-slot"><?= $badgeHtml ?></span>
                        </div>
                        <?php
                        Html::showDateField('cnh_expiry', [
                            'value' => $driver->fields['cnh_expiry'] ?? '',
                        ]);
                        ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--active">
                        <div class="vs-driver-label">Ativo</div>
                        <?php Dropdown::showYesNo('is_active', (int) ($driver->fields['is_active'] ?? 1)); ?>
                    </div>

                    <div class="vs-driver-field vs-driver-field--comment">
                        <div class="vs-driver-label">
                            Observações <span class="vs-driver-hint-inline">opcional</span>
                        </div>
                        <?= Html::textarea([
                            'name'  => 'comment',
                            'value' => $driver->fields['comment'] ?? '',
                            'rows'  => 3,
                        ]) ?>
                        <div class="vs-driver-hint">
                            Use este campo para orientações operacionais úteis, evitando dados pessoais sensíveis.
                        </div>
                    </div>
                </div>

                <div class="vs-driver-foot">
                    Mantenha apenas informações operacionais necessárias para alocação e conformidade da frota.
                </div>
            </div>
        </div>
    </div>
    <?php
}