<?php

/**
 * Requester self-service portal for fleet actions.
 *
 * Scope:
 * - validates requester portal ACL;
 * - renders the fleet self-service landing page;
 * - exposes the active booking entry point.
 */

include_once __DIR__ . '/../inc/common.inc.php';
include_once __DIR__ . '/../inc/ui-helpers.php';

Session::checkRight('plugin_vehiclescheduler_portal', READ);

$root_doc = plugin_vehiclescheduler_get_root_doc();

$self = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: '';

$booking_form_url = $root_doc . '/Form/Render/3';

$escape = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

Html::header('Viaturas', $self, 'helpdesk');

plugin_vehiclescheduler_load_css();
plugin_vehiclescheduler_enhance_ui();

?>
<div class="vs-page vs-page-requester">
    <div class="vs-requester-wrap">
        <section class="vs-requester-hero">
            <div class="vs-requester-pill">
                <i class="ti ti-car-suv"></i>
                <span>Portal de Viaturas</span>
            </div>

            <h1 class="vs-requester-title">
                <i class="ti ti-steering-wheel"></i>
                <span>Viaturas</span>
            </h1>

            <p class="vs-requester-subtitle">
                Solicite uma viatura de forma simples e acompanhe a evolução do portal de atendimento da frota.
            </p>
        </section>

        <section class="vs-requester-grid">
            <article class="vs-requester-card">
                <div class="vs-requester-icon">
                    <i class="ti ti-calendar-plus"></i>
                </div>

                <h3>Agendar Viatura</h3>

                <p>
                    Abra o formulário de solicitação para reservar uma viatura e iniciar o fluxo de atendimento.
                </p>

                <a class="vs-requester-btn vs-requester-btn--primary"
                    href="<?php echo $escape($booking_form_url); ?>">
                    <i class="ti ti-arrow-right"></i>
                    <span>Acessar formulário</span>
                </a>
            </article>

            <article class="vs-requester-card is-disabled">
                <div class="vs-requester-icon">
                    <i class="ti ti-calendar-event"></i>
                </div>

                <h3>Minhas Reservas</h3>

                <p>
                    Consulte solicitações já abertas, acompanhe status e visualize seu histórico de reservas.
                </p>

                <span class="vs-requester-btn vs-requester-btn--secondary" aria-disabled="true">
                    <i class="ti ti-clock"></i>
                    <span>Em breve</span>
                </span>
            </article>

            <article class="vs-requester-card">
                <div class="vs-requester-icon">
                    <i class="ti ti-alert-triangle"></i>
                </div>

                <h3>Informar Sinistro</h3>

                <p>
                    Registre acidentes, avarias e ocorrências relacionadas ao uso da viatura.
                </p>

                <a class="vs-requester-btn vs-requester-btn--primary"
                    href="<?php echo $escape(plugin_vehiclescheduler_get_front_url('incident.form.php')); ?>">
                    <i class="ti ti-arrow-right"></i>
                    <span>Acessar formulário</span>
                </a>
            </article>
        </section>

        <div class="vs-requester-note">
            <strong>Observação:</strong> use este endereço como destino do card <strong>Viaturas</strong> na home self-service.
            Nesta primeira etapa, o fluxo ativo é o de agendamento.
        </div>
    </div>
</div>

<?php Html::footer(); ?>
