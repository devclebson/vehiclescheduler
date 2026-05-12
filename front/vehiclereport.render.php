<?php

/**
 * Vehicle report form renderer.
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function vs_vehiclereport_render_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function vs_render_vehiclereport_form(PluginVehicleschedulerVehiclereport $report): void
{
    $entityId = (int) ($report->fields['entities_id'] ?? ($_SESSION['glpiactive_entity'] ?? 0));
    ?>
    <div class="vs-vehiclereport-wrap" data-vs-vehiclereport-form>
        <div class="vs-vehiclereport-surface">
            <div class="vs-vehiclereport-card">
                <div class="vs-vehiclereport-head">
                    <div>
                        <h3 class="vs-vehiclereport-title"><i class="ti ti-file-report"></i> Relatório de veículo</h3>
                        <div class="vs-vehiclereport-sub">Registro estruturado de problemas, observações e ocorrências para apoio à gestão da frota.</div>
                    </div>
                    <div class="vs-vehiclereport-pill"><span class="dot"></span> Relatórios</div>
                </div>

                <div class="vs-form-feedback" data-vehiclereport-validation hidden></div>

                <div class="vs-vehiclereport-grid">
                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label">Veículo <span class="red">*</span></div>
                        <?php
                        PluginVehicleschedulerVehicle::dropdown([
                            'name'   => 'plugin_vehiclescheduler_vehicles_id',
                            'value'  => (int) ($report->fields['plugin_vehiclescheduler_vehicles_id'] ?? 0),
                            'entity' => $entityId,
                        ]);
                        ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label">Tipo de relatório <span class="red">*</span></div>
                        <?php
                        Dropdown::showFromArray('report_type', PluginVehicleschedulerVehiclereport::getAllTypes(), [
                            'value' => (int) ($report->fields['report_type'] ?? PluginVehicleschedulerVehiclereport::TYPE_OBSERVATION),
                        ]);
                        ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label">Reportado por</div>
                        <?php
                        User::dropdown([
                            'name'  => 'users_id',
                            'value' => (int) ($report->fields['users_id'] ?? Session::getLoginUserID()),
                            'right' => 'all',
                        ]);
                        ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label">Departamento/Setor <span class="vs-vehiclereport-hint-inline">opcional</span></div>
                        <?= Html::input('department', [
                            'value'       => $report->fields['department'] ?? '',
                            'size'        => 40,
                            'placeholder' => 'Ex: Operações',
                        ]) ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label">Telefone para contato <span class="vs-vehiclereport-hint-inline">opcional</span></div>
                        <?= Html::input('contact_phone', [
                            'value'       => $report->fields['contact_phone'] ?? '',
                            'size'        => 20,
                            'placeholder' => '(00) 00000-0000',
                        ]) ?>
                    </div>

                    <div class="vs-vehiclereport-field">
                        <div class="vs-vehiclereport-label">Data do relatório <span class="red">*</span></div>
                        <?php
                        Html::showDateTimeField('report_date', [
                            'value' => $report->fields['report_date'] ?? date('Y-m-d H:i:s'),
                        ]);
                        ?>
                    </div>

                    <div class="vs-vehiclereport-field vs-vehiclereport-field--full">
                        <div class="vs-vehiclereport-label">Descrição <span class="red">*</span></div>
                        <textarea name="description" rows="6" placeholder="Descreva o problema, observação ou situação em detalhes"><?= vs_vehiclereport_render_escape($report->fields['description'] ?? '') ?></textarea>
                    </div>

                    <div class="vs-vehiclereport-field vs-vehiclereport-field--full">
                        <div class="vs-vehiclereport-label">Comentários adicionais <span class="vs-vehiclereport-hint-inline">opcional</span></div>
                        <textarea name="comment" rows="3"><?= vs_vehiclereport_render_escape($report->fields['comment'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
