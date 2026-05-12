<?php

/**
 * Vehicle form renderer.
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function vs_vehicle_render_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vs_vehicle_render_cnh_icon(string $category): string
{
    $icons = [
        'A' => 'ti ti-motorbike',
        'B' => 'ti ti-car',
        'D' => 'ti ti-truck',
    ];

    return $icons[$category] ?? 'ti ti-license';
}

function vs_render_vehicle_form(PluginVehicleschedulerVehicle $vehicle): void
{
    $selectedRequiredCategory = (string) ($vehicle->fields['required_cnh_category'] ?? PluginVehicleschedulerVehicle::REQUIRED_CNH_B);
    ?>
    <div class="vs-vehicle-wrap" data-vs-vehicle-form>
        <div class="vs-vehicle-surface">
            <div class="vs-vehicle-card">
                <div class="vs-vehicle-head">
                    <div>
                        <h3 class="vs-vehicle-title"><i class="ti ti-car"></i> Cadastro de veículo</h3>
                        <div class="vs-vehicle-sub">Dados operacionais essenciais para disponibilidade, alocação e rastreabilidade da frota.</div>
                    </div>
                    <div class="vs-vehicle-pill"><span class="dot"></span> Veículos</div>
                </div>

                <div class="vs-form-feedback" data-vehicle-validation hidden></div>

                <div class="vs-vehicle-grid">
                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label">Nome do veículo <span class="red">*</span></div>
                        <?= Html::input('name', [
                            'value'       => $vehicle->fields['name'] ?? '',
                            'size'        => 40,
                            'placeholder' => 'Ex: Viatura Administrativo 01',
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label">Placa <span class="red">*</span></div>
                        <?= Html::input('plate', [
                            'value'       => $vehicle->fields['plate'] ?? '',
                            'size'        => 20,
                            'placeholder' => 'Ex: ABC1D23',
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label">Marca <span class="vs-vehicle-hint-inline">opcional</span></div>
                        <?= Html::input('brand', [
                            'value'       => $vehicle->fields['brand'] ?? '',
                            'size'        => 30,
                            'placeholder' => 'Ex: Toyota',
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label">Modelo <span class="vs-vehicle-hint-inline">opcional</span></div>
                        <?= Html::input('model', [
                            'value'       => $vehicle->fields['model'] ?? '',
                            'size'        => 30,
                            'placeholder' => 'Ex: Hilux',
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label">Ano <span class="red">*</span></div>
                        <?= Html::input('year', [
                            'value'       => (int) ($vehicle->fields['year'] ?? (int) date('Y')),
                            'type'        => 'number',
                            'min'         => PluginVehicleschedulerVehicle::MIN_YEAR,
                            'max'         => PluginVehicleschedulerVehicle::MAX_YEAR,
                            'placeholder' => 'Ex: 2025',
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label">Capacidade de passageiros <span class="red">*</span></div>
                        <?= Html::input('seats', [
                            'value'       => (int) ($vehicle->fields['seats'] ?? 5),
                            'type'        => 'number',
                            'min'         => PluginVehicleschedulerVehicle::MIN_SEATS,
                            'max'         => PluginVehicleschedulerVehicle::MAX_SEATS,
                            'placeholder' => 'Ex: 5',
                        ]) ?>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label">Ativo</div>
                        <?php Dropdown::showYesNo('is_active', (int) ($vehicle->fields['is_active'] ?? 1)); ?>
                        <div class="vs-vehicle-hint">Viaturas inativas deixam de aparecer na alocação operacional.</div>
                    </div>

                    <div class="vs-vehicle-field">
                        <div class="vs-vehicle-label">CNH exigida <span class="red">*</span></div>
                        <div class="vs-vehicle-cnh-grid" data-vehicle-cnh-group>
                            <?php foreach (PluginVehicleschedulerVehicle::getRequiredCNHOptions() as $category => $label) : ?>
                                <label class="vs-vehicle-cnh-option">
                                    <input
                                        type="radio"
                                        name="required_cnh_category"
                                        value="<?= vs_vehicle_render_escape($category) ?>"
                                        <?= $selectedRequiredCategory === $category ? 'checked' : '' ?>
                                    >
                                    <span class="vs-vehicle-cnh-option__icon">
                                        <i class="<?= vs_vehicle_render_escape(vs_vehicle_render_cnh_icon($category)) ?>"></i>
                                    </span>
                                    <span class="vs-vehicle-cnh-option__meta">
                                        <span class="vs-vehicle-cnh-option__code"><?= vs_vehicle_render_escape($category) ?></span>
                                        <span class="vs-vehicle-cnh-option__text"><?= vs_vehicle_render_escape($label) ?></span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="vs-vehicle-hint">Regra do MVP: moto exige A, carro aceita B ou D, e caminhao/van exige D.</div>
                    </div>

                    <div class="vs-vehicle-field vs-vehicle-field--full">
                        <div class="vs-vehicle-label">Observações <span class="vs-vehicle-hint-inline">opcional</span></div>
                        <textarea name="comment" rows="3"><?= vs_vehicle_render_escape($vehicle->fields['comment'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
