# Plano de Implementação — Integração de Incidentes, Manutenções, Multas e Motoristas com Chamados GLPI

Este plano descreve as alterações necessárias para aprofundar o relacionamento entre os módulos do plugin **Vehicle Scheduler** e a abertura/sincronização de chamados nativos do GLPI, incluindo a associação de motoristas a seus respectivos usuários, a otimização inteligente do formulário de incidentes, o controle de ordens de manutenção e a validação lógica de datas.

---

## User Review Required

> [!IMPORTANT]
> A atualização do banco de dados (inclusão de colunas de chaves estrangeiras) requer a execução do script `update_db.php` após a atualização do código. Certifique-se de realizar um backup do banco de dados antes da execução em produção.

> [!NOTE]
> A sincronização de incidentes e manutenções preventivas atualizará automaticamente o status do chamado correspondente no GLPI.

---

## Proposed Changes

### Componente 1: Banco de Dados (Database Schema)

Precisamos adicionar colunas de controle nas tabelas de **Veículos** (odômetro), **Reservas** (datas reais, combustível, odômetro inicial/final e comentários de devolução), além das chaves estrangeiras de chamados/usuários nas tabelas de **Incidentes**, **Manutenções**, **Multas** e **Motoristas**.

#### [MODIFY] [hook.php](file:///var/www/glpi/plugins/vehiclescheduler/hook.php)
- Adicionar as colunas e índices correspondentes nas tabelas:
  - `glpi_plugin_vehiclescheduler_vehicles`: adicionar `mileage` (int, padrão 0, após `seats`) para armazenar o odômetro acumulado atual do veículo.
  - `glpi_plugin_vehiclescheduler_schedules`: adicionar colunas de controle de check-in/check-out após `comment`:
    - `real_begin_date` (timestamp, NULL)
    - `real_end_date` (timestamp, NULL)
    - `initial_mileage` (int, padrão 0)
    - `final_mileage` (int, padrão 0)
    - `initial_fuel` (int, padrão 0)
    - `final_fuel` (int, padrão 0)
    - `return_checklist` (text, NULL)
    - `return_comment` (text, NULL)
  - `glpi_plugin_vehiclescheduler_incidents`: adicionar `tickets_id` (após `groups_id`)
  - `glpi_plugin_vehiclescheduler_maintenances`: adicionar `tickets_id` (após `plugin_vehiclescheduler_incidents_id`)
  - `glpi_plugin_vehiclescheduler_driverfines`: adicionar `tickets_id` (após `status`)
  - `glpi_plugin_vehiclescheduler_drivers`: adicionar `users_id` (após `name`)

#### [MODIFY] [update_db.php](file:///var/www/glpi/plugins/vehiclescheduler/update_db.php)
- Implementar a verificação e alteração incremental de banco de dados:
  - Verificar se a coluna `mileage` existe na tabela `glpi_plugin_vehiclescheduler_vehicles` e criá-la se necessário.
  - Verificar se as colunas de controle de devolução (`real_begin_date`, `real_end_date`, `initial_mileage`, `final_mileage`, `initial_fuel`, `final_fuel`, `return_checklist`, `return_comment`) existem na tabela `glpi_plugin_vehiclescheduler_schedules` e criá-las se necessário.
  - Verificar se a coluna `tickets_id` existe na tabela `glpi_plugin_vehiclescheduler_incidents` e criá-la se necessário.
  - Verificar se a coluna `tickets_id` existe na tabela `glpi_plugin_vehiclescheduler_maintenances` e criá-la se necessário.
  - Verificar se a coluna `tickets_id` existe na tabela `glpi_plugin_vehiclescheduler_driverfines` e criá-la se necessário.
  - Verificar se a coluna `users_id` existe na tabela `glpi_plugin_vehiclescheduler_drivers` e criá-la se necessário.

---

### Componente 2: Gestão de Motoristas (Drivers)

Vincular o cadastro funcional de motoristas a um usuário ativo do GLPI, permitindo o fluxo de auto-solicitação e aprovação de condutores.

#### [MODIFY] [driver.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/driver.class.php)
- **Método `showForm()`**:
  - Adicionar o campo "Usuário do GLPI" no formulário de edição usando o dropdown nativo do GLPI: `User::dropdown(['name' => 'users_id', 'value' => $this->fields['users_id']])`.
- **Métodos `prepareInputForAdd()` e `prepareInputForUpdate()`**:
  - Tratar a recepção do campo `users_id`, garantindo que seja persistido no banco de dados.
- **Método `rawSearchOptions()`**:
  - Mapear a coluna `users_id` como um relacionamento com a tabela `glpi_users` para permitir a busca de motoristas filtrando pelo nome do usuário GLPI.
- **Novo Método `requestDriverRegistration($input)`**:
  - Cadastrar o motorista com `is_approved = 0` (pendente de aprovação) e `is_active = 1` no banco.
  - Criar automaticamente um chamado de requisição no GLPI sob a responsabilidade do usuário requerente, com o título *"Solicitação de Cadastro de Motorista: [Nome]"* contendo todos os dados inseridos (matrícula, CNH, telefone, etc.) e o link direto para aprovação na gestão de motoristas.
- **Sincronização de Status de Aprovação em `post_updateItem()`**:
  - Interceptar quando `is_approved` mudar de `0` para `1` (aprovado) e solucionar o chamado de solicitação correspondente com a mensagem de que o acesso aos veículos foi liberado.

---

### Componente 3: Gestão de Incidentes (Incidents)

Vincular o chamado criado ao incidente, habilitar a sincronização de status e implementar melhorias de inteligência e upload de anexos/fotos no formulário.

#### [MODIFY] [incident.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/incident.class.php)
- **Auto-preenchimento e Otimização no `showForm()`**:
  - Detectar o usuário logado e pesquisar na tabela de motoristas se há perfil associado (`users_id = logado`).
    - Caso encontre, pré-selecionar o campo `plugin_vehiclescheduler_drivers_id` e pré-preencher os campos `contact_phone` e `department`.
  - Pesquisar se o usuário possui alguma reserva ativa aprovada *neste momento* na tabela `glpi_plugin_vehiclescheduler_schedules`.
    - Caso encontre, pré-selecionar o campo `plugin_vehiclescheduler_vehicles_id` com o carro da reserva.
- **Upload de Fotos/Anexos**:
  - Adicionar o suporte a envio de arquivos no formulário configurando a tag do formulário com `enctype="multipart/form-data"` e exibindo o campo de upload de arquivos nativo do GLPI (`Html::file()`).
  - No método `post_addItem()`, processar o envio dos arquivos e criar registros na tabela de documentos associados do GLPI vinculando-os ao incidente recém-criado.
- **Vínculo do Chamado e `createTicketFromIncident()`**:
  - Salvar o `$ticket_id` de retorno no registro do incidente executando `$this->update(['id' => $this->fields['id'], 'tickets_id' => $ticket_id])`.
- **Exibição do Chamado Relacionado**:
  - Adicionar no formulário a exibição do link clicável do chamado do GLPI se `tickets_id > 0`.
- **Sincronização de Status**:
  - Interceptar mudanças no campo `status` em `post_updateItem()` e acionar o método `updateTicketStatus()`.
- **Novo Método `updateTicketStatus()`**:
  - Mapear a mudança de status do incidente para o chamado correspondente:
    - `STATUS_OPEN` (Aberto) $\rightarrow$ `CommonITILObject::INCOMING` (Novo)
    - `STATUS_ANALYZING` (Em Análise) $\rightarrow$ `CommonITILObject::ASSIGNED` (Atribuído)
    - `STATUS_RESOLVED` (Resolvido) $\rightarrow$ `CommonITILObject::SOLVED` (Solucionado)
    - `STATUS_CLOSED` (Fechado) $\rightarrow$ `CommonITILObject::CLOSED` (Fechado)
  - Inserir acompanhamentos (`ITILFollowup`) no chamado informando a resolução ou alteração do status.

#### [MODIFY] [incident.form.php](file:///var/www/glpi/plugins/vehiclescheduler/front/incident.form.php)
- Garantir que a renderização do formulário receba o suporte para upload de multipart (`enctype`).

---

### Componente 4: Gestão e Sincronização de Manutenções (Maintenances)

Gerenciar a integração de ordens de manutenção com chamados existentes (corretivas) ou novos (preventivas) e aplicar regras lógicas de validação de datas tanto no back-end quanto na interface do usuário.

#### [MODIFY] [maintenance.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/maintenance.class.php)
- **Métodos `prepareInputForAdd()` e `prepareInputForUpdate()`**:
  - Adicionar validações de data no back-end:
    - A **Data Agendada** (`scheduled_date`) não pode ser inferior ao dia atual (no momento da criação).
    - A **Data de Conclusão** (`completion_date`), se preenchida, não pode ser inferior à **Data Agendada** (`scheduled_date`).
- **Validação e Restrição na Interface (Front-end) no Método `showForm()`**:
  - Inserir atributos HTML (`min` ou configurações JS para o seletor de data flatpickr) para garantir que:
    - O campo de **Data Agendada** (`scheduled_date`) não permita selecionar uma data anterior ao dia atual.
    - O campo de **Data de Conclusão** (`completion_date`) não permita selecionar uma data inferior à **Data Agendada**.
- **Geração/Atualização de Chamados (`post_addItem()` e `post_updateItem()`)**:
  - Se a manutenção for **Corretiva** (`plugin_vehiclescheduler_incidents_id > 0`):
    - Localizar o chamado do incidente original e **inserir uma Tarefa (Task)** informando o agendamento da manutenção (com oficina e custo estimado).
    - Ao concluir a manutenção (`status == STATUS_DONE`), marcar a tarefa como concluída e adicionar um acompanhamento no chamado informando o término e custo real do conserto.
  - Se for **Preventiva** (sem incidente vinculado):
    - Criar um novo chamado de Requisição no GLPI e salvar o ID em `tickets_id`.
    - Sincronizar o status do chamado conforme o andamento:
      - `STATUS_SCHEDULED` $\rightarrow$ `CommonITILObject::INCOMING` (Novo)
      - `STATUS_IN_PROGRESS` $\rightarrow$ `CommonITILObject::ASSIGNED` (Atribuído)
      - `STATUS_DONE` $\rightarrow$ `CommonITILObject::SOLVED` (Solucionado)
      - `STATUS_CANCELLED` $\rightarrow$ `CommonITILObject::CLOSED` (Fechado)
- **Método `showForm()`**:
  - Se `tickets_id > 0` (preventiva), exibir o link do chamado correspondente.

### Componente 5: Gestão de Reservas (Schedules — Validação, Acesso, Formulários Separados e Devolução do Veículo)

Garantir que apenas motoristas ativos e aprovados possam solicitar agendamentos, enquanto gestores e técnicos mantêm controle total de edição, além de implementar o fluxo de Retirada (Check-out) e Devolução (Check-in) integrado a chamados e controle de odômetro.

#### [MODIFY] [schedule.form.php](file:///var/www/glpi/plugins/vehiclescheduler/front/schedule.form.php)
- **Controle de Acesso estrito por Perfil e Condutor**:
  - Se o usuário logado for gestor/técnico (`PluginVehicleschedulerProfile::canViewManagement()` for verdadeiro), conceder acesso completo de visualização e edição.
  - Se o usuário logado for um colaborador comum:
    - Verificar a existência do cadastro de condutor e seu status de aprovação:
      - **Cenário A: Sem Cadastro de Motorista**:
        - Em vez de um erro genérico, renderizar uma tela amigável de **"Acesso Restrito — Solicitação de Cadastro"** contendo explicações e o formulário para se cadastrar como motorista (campos: Matrícula, Categoria da CNH, Validade da CNH, Departamento, Telefone de Contato e Comentários).
        - Ao submeter o formulário (ação disparada por `isset($_POST['request_driver'])`), chamar o novo método `PluginVehicleschedulerDriver::requestDriverRegistration($_POST)`.
      - **Cenário B: Cadastro Pendente de Aprovação (`is_approved = 0`)**:
        - Renderizar uma tela amigável informando que sua solicitação já foi enviada e está pendente de homologação pela gestão de frotas.
      - **Cenário C: Motorista Ativo e Aprovado (`is_approved = 1` e `is_active = 1`)**:
        - Permitir apenas a **criação** de novos agendamentos e o registro de início/término de viagem. Bloquear qualquer tentativa de **edição ou atualização** (`isset($_POST['update'])` ou `$id > 0` com tentativa de edição), tornando a tela estritamente para submissão, ações de viagem e visualização.
- **Tratamento das Ações de Viagem**:
  - Interceptar e tratar o envio das ações de início (`isset($_POST['start_trip'])`) e conclusão de viagem (`isset($_POST['end_trip'])`), acionando os novos métodos correspondentes da classe `PluginVehicleschedulerSchedule`.

#### [MODIFY] [schedule.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/schedule.class.php)
- **Constantes de Status Adicionais**:
  - `STATUS_ONGOING = 5` (Em Viagem)
  - `STATUS_RETURNED = 6` (Devolvido)
- **Novo Método Auxiliar de Driver**:
  - Criar `PluginVehicleschedulerDriver::getActiveDriverByUserId($users_id)` para retornar o registro do motorista ativo e aprovado correspondente ao usuário do GLPI.
- **Diferenciação de Formulários e Fluxo de Viagem no `showForm()`**:
  - **Interface do Gestor / Técnico**:
    - Exibir todos os campos editáveis.
  - **Interface do Requerente Comum (Motorista Ativo)**:
    - Ocultar/bloquear campos administrativos (motorista designado é o próprio, status é omitido, campos bloqueados se $ID > 0).
  - **Botões e Formulários de Retirada (Check-out) e Devolução (Check-in)**:
    - Se a reserva estiver **Aprovada** (`STATUS_APPROVED`), renderizar o botão **"Iniciar Viagem (Retirar Chave)"**, que exibe um modal ou formulário solicitando o **Km Inicial** (pré-preenchido com o odômetro do veículo) e **Nível de Combustível Inicial** (dropdown: 1/4, 2/4, 3/4, 4/4 / Cheio).
    - Se a reserva estiver **Em Viagem** (`STATUS_ONGOING`), renderizar o botão **"Concluir Viagem (Devolver Veículo)"**, que exibe um modal solicitando o **Km Final** (validado para ser maior ou igual ao Km Inicial), **Nível de Combustível Final**, **Checklist** (campos Sim/Não de Limpeza e Avarias) e **Comentários**.
- **Novo Método `startTrip($input)`**:
  - Atualizar `real_begin_date` com o horário atual, `initial_mileage`, `initial_fuel` e mudar status para `STATUS_ONGOING`.
  - Adicionar um acompanhamento ao chamado correspondente: *"🚗 Viagem iniciada! Chave retirada com odômetro de [KM] km."*
- **Novo Método `endTrip($input)`**:
  - Validar Km Final no back-end. Se menor que Km Inicial, bloquear e exibir erro.
  - Salvar `real_end_date` com o horário atual, `final_mileage`, `final_fuel`, `return_checklist` (JSON), `return_comment` e definir status como `STATUS_RETURNED`.
  - Atualizar automaticamente o odômetro atual do veículo na tabela `vehicles` (`mileage = final_mileage`).
  - Adicionar um acompanhamento detalhado ao chamado correspondente e **solucionar o chamado automaticamente** (`status = CommonITILObject::SOLVED`).
  - Se for informada avaria ou problema no checklist, redirecionar o usuário para a abertura do formulário de incidentes já pré-preenchido.
- **Métodos `prepareInputForAdd()` e `prepareInputForUpdate()`**:
  - Adicionar validações lógicas de datas no back-end (datas futuras e ordenação).
- **Validação e Restrição na Interface (Front-end) no Método `showForm()`**:
  - Configurar Flatpickr/HTML `min` nos campos para evitar agendamento de datas passadas no seletor.

---

### Componente 6: Gestão de Multas (Driver Fines)

Gerar automaticamente um chamado de notificação para o motorista no momento da autuação e linkar a multa ao chamado correspondente.

#### [MODIFY] [driverfine.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/driverfine.class.php)
- **Método `post_addItem()`**:
  - Sobrescrever para acionar o método auxiliar `createTicketFromFine()` logo após a inclusão de uma multa.
- **Novo Método `createTicketFromFine()`**:
  - Buscar as informações do motorista autuado. Caso ele possua um `users_id` vinculado (Usuário GLPI):
    - O chamado de Requisição no GLPI será criado **em nome deste usuário** (`_users_id_requester => $driver_users_id`), garantindo que ele receba e-mails e alertas nativos do GLPI.
    - Se não houver vínculo de usuário, o chamado será criado no nome do usuário logado (gestor).
  - O conteúdo do chamado detalhará a infração, os pontos a serem deduzidos, a data, o veículo envolvido e orientações sobre como proceder para o pagamento ou recurso.
  - Salvar o ID do chamado gerado na coluna `tickets_id` da tabela de multas.
- **Método `showForm()`**:
  - Adicionar o bloco "Chamado Relacionado" na visualização dos detalhes da infração se `tickets_id > 0`.
- **Método `post_updateItem()`**:
  - Interceptar mudanças de status da multa (ex: paga, recurso) e adicionar um acompanhamento no chamado informando a resolução da infração.

---

## Verification Plan

### Automated Tests
- Não se aplica (as rotinas utilizam chamadas ao ORM interno do GLPI e hooks nativos).

### Manual Verification
1. **Verificação de Banco:**
   - Executar `php update_db.php` para atualizar as tabelas com as novas colunas e chaves estrangeiras.
2. **Validação de Regras de Data:**
   - Tentar agendar uma reserva com data de início no passado e verificar o bloqueio.
   - Tentar agendar uma reserva com data de retorno anterior à de saída e verificar o bloqueio.
   - Tentar agendar uma manutenção com data agendada no passado e verificar o bloqueio.
   - Tentar concluir uma manutenção informando uma data de conclusão anterior à agendada e verificar o bloqueio.
3. **Fluxo de Manutenções:**
   - Agendar uma manutenção corretiva a partir de um incidente e verificar se a tarefa foi criada no chamado do incidente original.
   - Concluir a manutenção corretiva e validar se a tarefa foi finalizada e o custo anexado ao chamado do incidente.
   - Agendar uma manutenção preventiva e validar a abertura do novo chamado e a sincronização de seus status (Scheduled -> In Progress -> Done).
4. **Fluxo de Retirada, Devolução e Controle de Acesso (Reservas):**
   - Tentar acessar a tela de reservas com um usuário que não é motorista ativo e validar se o sistema exibe a tela bloqueada com o formulário de cadastro.
   - Preencher e enviar o formulário de solicitação de motorista, verificando se o registro foi criado como `is_approved = 0` e se o chamado foi aberto no GLPI.
   - Aprovar o motorista na tela de gestão e verificar se o chamado foi solucionado automaticamente.
   - Iniciar a viagem ("Retirar Veículo") informando Km Inicial e combustível, checando se o status da reserva foi para "Em Viagem" e o chamado do GLPI recebeu o acompanhamento.
   - Concluir a viagem ("Devolver Veículo") informando Km Final (testando erro de Km Final menor que Km Inicial), combustível e checklist, checando se o status foi para "Devolvido", o odômetro do veículo foi atualizado no banco e o chamado do GLPI foi solucionado.
   - Testar o checklist com avaria e verificar se o sistema direciona para a abertura de incidentes.

