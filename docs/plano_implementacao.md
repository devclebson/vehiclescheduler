# Plano de Implementação — Integração de Incidentes e Multas com Chamados GLPI

Este plano descreve as alterações necessárias para aprofundar o relacionamento entre os módulos do plugin **Vehicle Scheduler** e a abertura/sincronização de chamados nativos do GLPI.

---

## User Review Required

> [!IMPORTANT]
> A atualização do banco de dados (inclusão de colunas de chaves estrangeiras) requer a execução do script `update_db.php` após a atualização do código. Certifique-se de realizar um backup do banco de dados antes da execução em produção.

> [!NOTE]
> A sincronização de incidentes atualizará automaticamente o status do chamado no GLPI. Certifique-se de que os status de incidentes mapeados correspondam ao fluxo de trabalho esperado pela equipe.

---

## Proposed Changes

### Componente 1: Banco de Dados (Database Schema)

Precisamos adicionar a coluna `tickets_id` nas tabelas de **Incidentes** e **Multas** para viabilizar o rastreamento dos chamados abertos.

#### [MODIFY] [hook.php](file:///var/www/glpi/plugins/vehiclescheduler/hook.php)
- Adicionar a coluna `tickets_id` (e seu índice) na instrução `CREATE TABLE` das tabelas:
  - `glpi_plugin_vehiclescheduler_incidents` (após `groups_id`)
  - `glpi_plugin_vehiclescheduler_driverfines` (após `status` ou no final da tabela)

#### [MODIFY] [update_db.php](file:///var/www/glpi/plugins/vehiclescheduler/update_db.php)
- Implementar a verificação e alteração incremental de banco de dados:
  - Verificar se a coluna `tickets_id` existe na tabela `glpi_plugin_vehiclescheduler_incidents` e criá-la se necessário.
  - Verificar se a coluna `tickets_id` existe na tabela `glpi_plugin_vehiclescheduler_driverfines` e criá-la se necessário.

---

### Componente 2: Gestão de Incidentes (Incidents)

Vincular o chamado criado ao incidente correspondente e habilitar a sincronização de status e a visualização do chamado na tela de edição.

#### [MODIFY] [incident.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/incident.class.php)
- **Método `createTicketFromIncident()`**:
  - Salvar o `$ticket_id` de retorno no registro do incidente executando `$this->update(['id' => $this->fields['id'], 'tickets_id' => $ticket_id])`.
- **Método `showForm()`**:
  - Adicionar a renderização do bloco "Chamado Relacionado" (alerta azul) exibindo o link clicável do chamado do GLPI se `tickets_id > 0`.
- **Método `post_updateItem()`**:
  - Interceptar mudanças no campo `status` e chamar a nova função `updateTicketStatus()`.
- **Novo Método `updateTicketStatus()`**:
  - Mapear a mudança de status do incidente para o chamado correspondente:
    - `STATUS_OPEN` (Aberto) $\rightarrow$ `CommonITILObject::INCOMING` (Novo)
    - `STATUS_ANALYZING` (Em Análise) $\rightarrow$ `CommonITILObject::ASSIGNED` (Atribuído)
    - `STATUS_RESOLVED` (Resolvido) $\rightarrow$ `CommonITILObject::SOLVED` (Solucionado)
    - `STATUS_CLOSED` (Fechado) $\rightarrow$ `CommonITILObject::CLOSED` (Fechado)
  - Inserir um acompanhamento (`ITILFollowup`) no chamado informando a equipe sobre a mudança do status do veículo e as ações tomadas pelo gestor.

---

### Componente 3: Gestão de Multas (Driver Fines)

Gerar automaticamente um chamado de notificação para o motorista no momento da autuação e linkar a multa ao chamado correspondente.

#### [MODIFY] [driverfine.class.php](file:///var/www/glpi/plugins/vehiclescheduler/inc/driverfine.class.php)
- **Método `post_addItem()`**:
  - Sobrescrever para acionar o método auxiliar `createTicketFromFine()` logo após a inclusão de uma multa.
- **Novo Método `createTicketFromFine()`**:
  - Criar um chamado do tipo **Requisição** no GLPI em nome do motorista autuado.
  - O conteúdo do chamado detalhará a infração, os pontos a serem deduzidos, a data, o veículo envolvido e orientações sobre como proceder para o pagamento ou recurso.
  - Salvar o ID do chamado gerado na coluna `tickets_id` recém-criada na tabela de multas.
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
   - Executar `php update_db.php` via linha de comando para atualizar as tabelas existentes e verificar se as colunas `tickets_id` foram criadas corretamente.
2. **Fluxo de Incidentes:**
   - Entrar como usuário comum e reportar um incidente.
   - Verificar se o chamado correspondente foi criado no GLPI.
   - Entrar como gestor de frotas, abrir o incidente criado e validar se o bloco "Chamado Relacionado" exibe o link do chamado.
   - Alterar o status do incidente para "Em Análise" e depois para "Resolvido". Validar se o chamado no GLPI foi atualizado para "Atribuído" e depois "Solucionado", respectivamente, e se os acompanhamentos foram adicionados.
3. **Fluxo de Multas:**
   - Entrar como gestor de frotas e cadastrar uma multa para um motorista.
   - Validar se um chamado no nome do motorista foi criado com as informações da autuação.
   - Abrir o formulário da multa e confirmar se o link para o chamado está funcional.
