# 🚗 Análise Técnica do Projeto: Vehicle Scheduler (Gestão de Frotas)

Este documento apresenta uma análise técnica detalhada do plugin **Vehicle Scheduler** para GLPI (compatível com as versões 10.x/11.x). Ele serve como documentação de referência para a arquitetura, modelo de dados, fluxos de permissão e interface do usuário.

---

## 📂 1. Estrutura de Diretórios e Arquivos

O projeto segue a estrutura padrão de desenvolvimento de plugins do GLPI:

*   📂 **`css/`**: Contém as folhas de estilo customizadas.
    *   [vehiclescheduler-core.css](file:///var/www/glpi/plugins/vehiclescheduler/css/vehiclescheduler-core.css): Define a identidade visual do plugin, incluindo elementos de layout responsivos, tabelas modernas, botões e cartões com efeitos visuais.
    *   [calendar.css](file:///var/www/glpi/plugins/vehiclescheduler/css/calendar.css): Estilo específico da grade do calendário de agendamento de reservas.
    *   `app-style.css`: Estilizações secundárias.
*   📂 **`docs/`**: Armazena as documentações do plugin (incluindo este arquivo de análise).
*   📂 **`front/`**: Controladores de visualização e páginas de interface acessíveis pelo navegador.
    *   📂 **`dashboards/`**: Painéis operacionais do sistema.
        *   [management.php](file:///var/www/glpi/plugins/vehiclescheduler/front/dashboards/management.php): Painel do gestor de frota, exibindo KPIs operacionais em tempo real, atalhos para aprovação rápida de reservas e alertas de vencimento de CNH.
        *   [portal.php](file:///var/www/glpi/plugins/vehiclescheduler/front/dashboards/portal.php): Portal do requerente (usuário comum), exibindo suas reservas ativas e o histórico de incidentes relatados.
        *   `fines.php`: Tela de controle e listagem de multas dos motoristas.
    *   📂 **`pages/`**: Páginas do portal de reservas e interface de agendamento.
        *   [requester.php](file:///var/www/glpi/plugins/vehiclescheduler/front/pages/requester.php): Hub simplificado para usuários finais (Self-Service) solicitarem reservas ou reportarem incidentes.
        *   [booking.php](file:///var/www/glpi/plugins/vehiclescheduler/front/pages/booking.php): Calendário de agendamento interativo mensal (estilo *Rent-a-car*).
        *   `requester_list.php`: Histórico completo de reservas do usuário logado.
    *   📄 **Telas CRUD (Lista/Formulário)**: Páginas individuais para manipulação das entidades (ex: `vehicle.php` / `vehicle.form.php`, `driver.php` / `driver.form.php`, `schedule.php` / `schedule.form.php`, `incident.php` / `incident.form.php`, `maintenance.php` / `maintenance.form.php`, `insuranceclaim.php` / `insuranceclaim.form.php`).
*   📂 **`inc/`**: Classes de negócio que encapsulam a lógica e o acesso ao banco (Modelos ORM).
    *   📂 **`helpers/`**: Funções auxiliares.
        *   [ui-helpers.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/helpers/ui-helpers.php): Contém a navbar unificada (`vs_render_navbar`) e estilos CSS inline globais.
        *   `common.inc.php`: Arquivo comum de bootstrap e inclusões.
    *   📄 **Classes de Modelo (CommonDBTM)**:
        *   [vehicle.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/vehicle.class.php): Gerenciamento de veículos da frota.
        *   [driver.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/driver.class.php): Gerenciamento de motoristas, qualificações e fluxos de aprovação.
        *   [schedule.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/schedule.class.php): Fluxo de solicitação e status de reservas.
        *   [incident.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/incident.class.php): Registro de sinistros leves, acidentes ou quebras de veículos em rota.
        *   [maintenance.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/maintenance.class.php): Agendamento e controle financeiro de manutenções.
        *   [insuranceclaim.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/insuranceclaim.class.php): Abertura e acompanhamento de sinistros junto às seguradoras.
        *   [driverfine.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/driverfine.class.php): Gestão de infrações de trânsito e multas atribuídas.
        *   [profile.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/profile.class.php): Gerencia os direitos de acesso e permissões customizadas do plugin.
        *   [theme.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/theme.class.php): Classe de temas e paletas de cores (atualmente inativa na interface do usuário).
        *   `menug.class.php` / `menui.class.php`: Responsáveis pela injeção dos menus nos painéis "Ferramentas" (Gestão) e "Assistência" (Portal) do GLPI.
*   📂 **`locales/`**: Arquivos PO/MO para internacionalização do sistema.
*   📄 **Raiz do Plugin**:
    *   [setup.php](file:///var/www/glpi/plugins/vehiclescheduler/setup.php): Arquivo de configuração e inicialização do plugin.
    *   [hook.php](file:///var/www/glpi/plugins/vehiclescheduler/hook.php): Contém as rotinas executadas na instalação e desinstalação (gerenciamento do banco de dados).

---

## 🗄️ 2. Arquitetura do Banco de Dados

A instalação do plugin cria 8 tabelas principais. Abaixo estão listadas as tabelas e suas principais colunas:

### 1. Veículos (`glpi_plugin_vehiclescheduler_vehicles`)
Armazena as informações físicas e cadastrais de cada veículo da frota.
*   `id` (int unsigned): Chave primária.
*   `name` (varchar): Nome identificador do veículo (ex: "Chevrolet Onix").
*   `plate` (varchar): Placa do veículo.
*   `brand` (varchar): Marca fabricante.
*   `model` (varchar): Modelo do veículo.
*   `year` (int): Ano de fabricação.
*   `seats` (int): Capacidade de passageiros (assentos).
*   `is_active` (tinyint): Status do veículo (1 = Ativo, 0 = Inativo).
*   `comment` (text): Observações gerais.

### 2. Motoristas (`glpi_plugin_vehiclescheduler_drivers`)
Gerencia o cadastro de pessoas autorizadas a dirigir os veículos.
*   `id` (int unsigned): Chave primária.
*   `name` (varchar): Nome completo do motorista.
*   `registration` (varchar): Matrícula corporativa.
*   `cnh_category` (varchar): Categoria da CNH (ex: "B", "AD").
*   `cnh_expiry` (date): Vencimento da CNH (gera alertas 90 dias antes).
*   `contact_phone` (varchar): Telefone de contato.
*   `is_active` (tinyint): Cadastro ativo ou inativo.
*   `is_approved` (tinyint): Indica se o motorista está homologado pela gestão (1 = Sim, 0 = Não).
*   `approved_by` (int unsigned): ID do gestor que aprovou o cadastro.

### 3. Reservas (`glpi_plugin_vehiclescheduler_schedules`)
Registra as solicitações de uso dos veículos da frota.
*   `id` (int unsigned): Chave primária.
*   `name` (varchar): Nome ou assunto do agendamento.
*   `plugin_vehiclescheduler_vehicles_id` (int unsigned): Veículo alocado.
*   `plugin_vehiclescheduler_drivers_id` (int unsigned): Motorista condutor.
*   `users_id` (int unsigned): Usuário solicitante da reserva.
*   `status` (int): Estado da reserva (1 = Nova/Pendente, 2 = Aprovada, 3 = Recusada, 4 = Cancelada).
*   `begin_date` (timestamp): Data e hora de retirada do veículo.
*   `end_date` (timestamp): Data e hora prevista de devolução.
*   `destination` (varchar): Destino da viagem.
*   `purpose` (text): Justificativa/motivo do deslocamento.
*   `passengers` (int): Quantidade de passageiros previstos.

### 4. Incidentes (`glpi_plugin_vehiclescheduler_incidents`)
Registra imprevistos ocorridos durante os trajetos.
*   `id` (int unsigned): Chave primária.
*   `plugin_vehiclescheduler_vehicles_id` (int unsigned): Veículo envolvido.
*   `plugin_vehiclescheduler_drivers_id` (int unsigned): Motorista envolvido.
*   `users_id` (int unsigned): Usuário que abriu o chamado.
*   `incident_type` (int): Tipo do incidente (Acidente, pane mecânica, avaria leve, furto, etc.).
*   `status` (int): Status de resolução do incidente.
*   `incident_date` (timestamp): Data e hora do acontecimento.
*   `location` (varchar): Localização geográfica do fato.
*   `needs_maintenance` (tinyint): Flag que indica necessidade de envio à oficina (1 = Sim, 0 = Não).
*   `needs_insurance` (tinyint): Flag que indica abertura de sinistro (1 = Sim, 0 = Não).

### 5. Manutenções (`glpi_plugin_vehiclescheduler_maintenances`)
Controle das revisões e reparos executados nos veículos da frota.
*   `id` (int unsigned): Chave primária.
*   `plugin_vehiclescheduler_vehicles_id` (int unsigned): Veículo em manutenção.
*   `plugin_vehiclescheduler_incidents_id` (int): Incidente de origem (se houver).
*   `type` (int): Tipo da manutenção (1 = Preventiva, 2 = Corretiva).
*   `status` (int): Status do serviço (1 = Agendada/Pendente, 2 = Em Execução, 3 = Concluída).
*   `scheduled_date` (date): Data planejada para entrada na oficina.
*   `completion_date` (date): Data de conclusão/saída da oficina.
*   `supplier` (varchar): Oficina/Prestador de serviço.
*   `cost` (decimal 10,2): Custo total do serviço.
*   `mileage` (int): Quilometragem registrada no momento da entrada.

### 6. Sinistros (`glpi_plugin_vehiclescheduler_insuranceclaims`)
Registro e acompanhamento de acionamentos de apólices de seguro.
*   `id` (int unsigned): Chave primária.
*   `plugin_vehiclescheduler_vehicles_id` (int unsigned): Veículo afetado.
*   `plugin_vehiclescheduler_incidents_id` (int): Incidente vinculado.
*   `claim_number` (varchar): Número do sinistro fornecido pela seguradora.
*   `status` (int): Status do processo de seguro.
*   `opening_date` (date): Data de abertura do sinistro.
*   `closing_date` (date): Data de encerramento do processo.
*   `insurance_company` (varchar): Seguradora responsável.
*   `estimated_value` (decimal 10,2): Custo estimado dos danos.
*   `approved_value` (decimal 10,2): Custo de conserto coberto/aprovado.

### 7. Multas (`glpi_plugin_vehiclescheduler_driverfines`)
Controle de infrações de trânsito recebidas.
*   `id` (int unsigned): Chave primária.
*   `plugin_vehiclescheduler_drivers_id` (int unsigned): Motorista infrator.
*   `plugin_vehiclescheduler_vehicles_id` (int unsigned): Veículo da infração.
*   `fine_date` (date): Data em que a multa foi cometida.
*   `severity` (int): Gravidade (Leve, Média, Grave, Gravíssima).
*   `status` (int): Situação do pagamento/recurso.

### 8. Perfis e Permissões (`glpi_plugin_vehiclescheduler_profiles`)
Guarda as definições de permissões para cada perfil de acesso do GLPI.
*   `id` (int unsigned): Chave primária.
*   `profiles_id` (int unsigned): ID do perfil nativo do GLPI (ex: `4` para *Super-Admin*).
*   `requester_access` (tinyint): Permite acessar o Portal de Reservas (1 = Sim, 0 = Não).
*   `management_access` (varchar): Nível de acesso à Gestão de Frota ('' = Sem Acesso, 'r' = Leitura, 'w' = Escrita/Edição).
*   `can_approve` (tinyint): Permissão para aprovar ou recusar reservas (1 = Sim, 0 = Não).

---

## 🔒 3. Modelo de Permissões e Segurança

A gestão de acesso do plugin baseia-se na classe `PluginVehicleschedulerProfile`. Ela estende a aba de Perfis do GLPI de forma que:
*   **Usuários Comuns (Requerentes):** Têm apenas o `requester_access` habilitado. Eles acessam uma interface simplificada (portal) onde podem solicitar reservas, visualizar suas próprias solicitações e abrir registros de incidentes.
*   **Gestores de Frota:** Têm o `management_access` definido como `'w'` (Escrita). Podem ver e alterar as listas de veículos, motoristas, manutenções, sinistros e multas.
*   **Aprovadores:** Perfis que possuem `can_approve` habilitado conseguem deferir ou indeferir as reservas de veículos diretamente no painel gerencial.

---

## 🎨 4. Aspectos Visuais e Customização (Aesthetics)

O plugin adota práticas de design modernos que contrastam com a interface padrão do GLPI:
*   **Design Glassmorphism:** Cards e menus possuem fundo semitransparente com efeito de desfoque (`backdrop-filter: blur()`).
*   **Esquema de Cores SaaS:** Uso de tons harmoniosos e modernos (`var(--vs-primary) = #3b82f6` para ações, verde para status aprovado/ativo, e vermelho para alertas e incidentes).
*   **Navegação Fluida:** Menus construídos para respeitar o histórico do navegador e prover links de "Voltar" intuitivos nos formulários.
*   **Calendário Integrado:** Interface em [booking.php](file:///var/www/glpi/plugins/vehiclescheduler/front/pages/booking.php) que apresenta as reservas mensais agregadas por veículo de maneira visual, facilitando a identificação de datas disponíveis para agendamentos.

---

## 🛠️ 5. Considerações e Próximos Passos

1.  **Reativação do Sistema de Temas:** A estrutura de temas de cores (Roxo, Azul, Verde, Laranja) definida no [theme.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/theme.class.php) está atualmente sem uso prático. Caso seja necessário permitir que o usuário mude a cor do painel, a criação da tabela `glpi_plugin_vehiclescheduler_themes` precisa ser incluída no `hook.php` e a tela de configuração precisa ser reativada no `setup.php`.
2.  **Segurança e CSRF:** O plugin declara conformidade com a segurança CSRF do GLPI (`$PLUGIN_HOOKS['csrf_compliant']['vehiclescheduler'] = true`), o que garante compatibilidade total com os mecanismos modernos de proteção de sessões do GLPI 10/11.
