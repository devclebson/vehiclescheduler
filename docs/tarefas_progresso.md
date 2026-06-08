# Checklist de Progresso das Implementações

Este arquivo registra e acompanha a execução do Plano de Implementação de integrações do plugin **Vehicle Scheduler** com chamados nativos do GLPI, controle de acesso a motoristas e fluxo de devolução de veículos.

- [x] **Componente 1: Banco de Dados (Database Schema)**
  - [x] Modificar `hook.php` para incluir as novas colunas (`tickets_id`, `users_id`, `mileage`, colunas de devolução)
  - [x] Implementar verificação incremental de banco de dados em `update_db.php`
  - [x] Executar script de migração e verificar banco de dados

- [x] **Componente 2: Gestão de Motoristas (Drivers)**
  - [x] Adicionar dropdown "Usuário do GLPI" no `showForm()` de `driver.class.php`
  - [x] Tratar persistência de `users_id` in `prepareInputForAdd` / `prepareInputForUpdate`
  - [x] Mapear `users_id` em `rawSearchOptions` para busca integrada
  - [x] Implementar `requestDriverRegistration($input)` (cadastro pendente + ticket de solicitação no GLPI)
  - [x] Implementar solução de ticket automático ao aprovar motorista em `post_updateItem`

- [x] **Componente 3: Gestão de Incidentes (Incidents)**
  - [x] Auto-preenchimento de motorista, telefone, setor e veículo (reserva ativa) no `showForm()`
  - [x] Habilitar upload de fotos/anexos e processar em `post_addItem`
  - [x] Exibir link do chamado relacionado se `tickets_id > 0`
  - [x] Implementar `createTicketFromIncident` salvando `tickets_id`
  - [x] Sincronizar status do chamado a partir do incidente (`updateTicketStatus()`)
  - [x] Configurar `enctype="multipart/form-data"` no `incident.form.php`

- [x] **Componente 4: Gestão e Sincronização de Manutenções (Maintenances)**
  - [x] Adicionar validações de data no backend (`prepareInputForAdd` / `prepareInputForUpdate`)
  - [x] Adicionar restrições de data interativas no front-end (`min` / flatpickr) no `showForm`
  - [x] Tratar manutenções corretivas (vincular tarefa/followup ao chamado do incidente original)
  - [x] Tratar manutenções preventivas (abrir novo chamado de requisição e sincronizar status)
  - [x] Exibir link do chamado relacionado se `tickets_id > 0`

- [x] **Componente 5: Gestão de Reservas (Schedules — Datas, Acesso, Telas Separadas e Devolução)**
  - [x] Implementar método auxiliar `PluginVehicleschedulerDriver::getActiveDriverByUserId($users_id)`
  - [x] Modificar `schedule.form.php` com controle de acesso estrito (Cenários A, B, C)
  - [x] Desenhar telas amigáveis para Não Cadastrado (com formulário de cadastro) e Pendente de Aprovação
  - [x] Diferenciar formulário de reserva no `showForm()` de `schedule.class.php` (Gestor vs Condutor)
  - [x] Adicionar validações de data no backend (`begin_date` / `end_date`)
  - [x] Adicionar restrições de data no frontend (Flatpickr) no formulário de reservas
  - [x] Implementar as ações de Início de Viagem (`startTrip()`) e Devolução (`endTrip()`)
  - [x] Adicionar os modais e botões de Retirada e Devolução no formulário
  - [x] Atualizar odômetro do veículo e solucionar chamado correspondente do GLPI ao concluir viagem

- [x] **Componente 6: Gestão de Multas (Driver Fines)**
  - [x] Implementar `createTicketFromFine` abrindo chamado no nome do motorista infrator
  - [x] Exibir link do chamado relacionado no formulário de multas
  - [x] Sincronizar status de multas via acompanhamentos do chamado
