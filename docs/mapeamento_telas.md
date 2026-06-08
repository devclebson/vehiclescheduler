# 🗺️ Mapeamento de Telas e Formulários — Vehicle Scheduler

Este documento contém o levantamento completo de todas as interfaces (telas, dashboards e formulários) do plugin de Gestão de Frotas, divididas pelo perfil de acesso esperado (Requerentes vs. Gestores).

---

## 🏠 1. Portais e Dashboards (Visão Geral)

### 1.1. Ponto de Entrada Unificado (`/front/index.php`)
- **Objetivo**: Detectar a interface do GLPI em que o usuário está (Helpdesk/Simplificada vs. Padrão/Central) e carregar o painel central em formato de abas.
- **Público**: Todos os usuários.

### 1.2. Portal do Requerente (`/front/dashboards/portal.php` — Aba: 🏠 Portal)
- **Objetivo**: Dashboard inicial simplificado para o usuário final.
- **Recursos**:
  - Banner de boas-vindas com contagem de reservas ativas e incidentes abertos.
  - Atalhos rápidos (Solicitar Reserva, Reportar Incidente).
  - Lista simplificada de "Minhas Reservas" (com link de histórico).
  - Lista simplificada de "Meus Incidentes".
- **Público**: Requerente (Self-Service) e Gestores.

### 1.3. Calendário de Agendamentos (`/front/pages/booking.php` — Aba: 🗓️ Agendamento)
- **Objetivo**: Painel estilo "Rent-a-car" contendo calendário de reservas.
- **Recursos**:
  - Painel lateral com lista de veículos ativos e status de disponibilidade hoje (Disponível em verde, Em viagem em vermelho).
  - Grade mensal do calendário exibindo chaves coloridas dos veículos reservados por dia.
  - Modal interativo ao clicar nos dias para visualização de detalhes ou aprovação rápida.
- **Público**: Gestores (ocultado para usuários comuns).

### 1.4. Meus Agendamentos (`/front/pages/requester_list.php` — Aba: 📋 Meus Agendamentos)
- **Objetivo**: Histórico completo de reservas apenas do usuário logado.
- **Recursos**:
  - Lista de agendamentos com placa do veículo, período e status (Nova, Aprovada, Recusada, Cancelada).
  - Modais de detalhes para cada agendamento com histórico e opção de cancelamento de reservas pendentes.
- **Público**: Requerente (Self-Service) (ocultado para gestores).

### 1.5. Dashboard de Gestão (`/front/dashboards/management.php` — Aba: 📊 Gestão de Frota)
- **Objetivo**: Centro operacional de tomada de decisão do gestor.
- **Recursos**:
  - Cartões de KPIs dinâmicos (Veículos, Motoristas, Pendências, Incidentes, Manutenções).
  - Banner dinâmico com alertas operacionais prioritários (CNH vencendo em 90 dias, reservas pendentes, manutenções em atraso).
  - Bloco de "Solicitações Aguardando Aprovação" com ações rápidas de "Aprovar" ou "Recusar" (com solicitação de justificativa).
  - Bloco de "Próximas Manutenções" com alertas de atraso.
  - Bloco de "Uso da Frota (Hoje)" com gráfico de barras segmentado (Disponíveis vs. Viagem vs. Manutenção).
  - Bloco de "Alertas CNH (Próximas a Vencer)".
  - Links de atalhos administrativos rápidos no rodapé.
- **Público**: Gestores.

### 1.6. Dashboard de Multas (`/front/dashboards/fines.php` — Aba: 🎫 Multas)
- **Objetivo**: Controle financeiro e de pontuação de infrações.
- **Recursos**:
  - KPIs de multas em aberto, pontos na CNH acumulados e valor financeiro estimado.
  - Tabela geral de multas com gravidade, motorista autuado, pontos e ações rápidas (Marcar como paga, Cancelar).
- **Público**: Gestores.

---

## 📝 2. Telas de Cadastro e Pesquisa (CRUDs Padrão GLPI)

Estas telas utilizam o mecanismo nativo do GLPI para renderizar tabelas de busca estruturadas (`Search::show`) e formulários de edição.

| Funcionalidade / Item | Página de Listagem e Busca (`/front/...`) | Formulário de Cadastro e Edição (`/front/...`) | Perfil de Acesso Permitido |
| :--- | :--- | :--- | :--- |
| **Reservas (Schedules)** | `schedule.php` | `schedule.form.php` | Visualizar (Todos) / Criar e Editar (Requerentes e Gestores) |
| **Incidentes (Incidents)** | `incident.php` | `incident.form.php` | Visualizar (Gestores) / Criar (Todos) / Editar (Gestores) |
| **Veículos (Vehicles)** | `vehicle.php` | `vehicle.form.php` | Exclusivo Gestores (Leitura/Escrita) |
| **Motoristas (Drivers)** | `driver.php` | `driver.form.php` | Exclusivo Gestores (Leitura/Escrita) |
| **Manutenções (Maintenances)**| `maintenance.php` | `maintenance.form.php` | Exclusivo Gestores (Leitura/Escrita) |
| **Sinistros (InsuranceClaims)**| `insuranceclaim.php` | `insuranceclaim.form.php`| Exclusivo Gestores (Leitura/Escrita) |
| **Multas (DriverFines)** | *(Visualizado no Dash de Multas)*| `driverfine.form.php` | Exclusivo Gestores (Leitura/Escrita) |
| **Relatórios (VehicleReports)**| `vehiclereport.php` | `vehiclereport.form.php` | Exclusivo Gestores (Leitura/Escrita) |

---

## 🛡️ 3. Telas de Configuração do Sistema

### 3.1. Formulário de Direitos de Perfil (`/front/profile.form.php`)
- **Objetivo**: Salvar as configurações de direitos do plugin enviadas a partir da aba "Gestão de Frota" no menu de Perfis nativo do GLPI (`Administration > Profiles`).
- **Público**: Super-Admin do GLPI.
