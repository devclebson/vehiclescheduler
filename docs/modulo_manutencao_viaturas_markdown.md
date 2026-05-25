# Especificação do Módulo de Manutenção de Viaturas

## 1. Visão Geral

Este documento descreve a modelagem funcional do **Módulo de Manutenção de Viaturas** para um sistema de gestão de frota.

O módulo deve controlar o ciclo completo de manutenção de uma viatura, desde a identificação de uma necessidade de manutenção até a liberação da viatura para operação.

Para o MVP, a execução deve controlar apenas o processo essencial de manutenção, sem transformar o plugin em um sistema completo de gestão de contratos, estoque, financeiro ou portal de oficinas. A especificação ampla deste documento permanece como referência funcional, mas a implementação inicial deve ser reduzida para um fluxo operacional controlável, centrado na Ordem de Serviço.

A manutenção será tratada conceitualmente como **Ordem de Serviço de Manutenção**. O registro atual de manutenção poderá ser evoluído para representar a OS simplificada, evitando criar uma estrutura paralela desnecessária no MVP.

O módulo deve contemplar:

- Oficinas próprias
- Oficinas credenciadas
- Checklist de entrega
- Checklist de retorno
- Checklist de manutenção
- Checklist de liberação
- Manutenção preventiva
- Manutenção corretiva
- Ordem de Serviço
- Diagnóstico
- Orçamento
- Aprovação
- Execução dos serviços
- Registro de peças e serviços
- Histórico da viatura
- Alertas e notificações
- Indicadores de manutenção
- Auditoria e rastreabilidade

---

## 1.1 Recorte Executivo do MVP

Entram no MVP:

- OS de manutenção
- Oficina responsável
- Serviços/especialidades da oficina
- Diagnóstico
- Custo estimado
- Aprovação simples
- Execução
- Custo final
- Bloqueio/liberação da viatura
- Histórico básico por OS
- Dashboard mínimo
- Auditoria essencial
- Internacionalização
- Revisão de CSS

Ficam fora do MVP:

- Contratos
- Controle de saldo contratual
- Estoque de peças
- Catálogo completo de serviços
- Portal de oficina
- Ranking de oficinas
- SLA
- Integração financeira
- Manutenção preditiva
- Telemetria

---

## 2. Objetivo do Módulo

O módulo deve permitir que gestores de frota e responsáveis pela manutenção acompanhem, registrem e controlem todas as manutenções realizadas nas viaturas.

O sistema deve responder, no mínimo:

- Quais viaturas estão em manutenção?
- Quais viaturas estão disponíveis, bloqueadas ou indisponíveis?
- Qual o motivo da manutenção?
- Onde a viatura está?
- Qual oficina está responsável pelo serviço?
- Quem abriu a solicitação?
- Quem aprovou o orçamento?
- Qual o custo previsto?
- Qual o custo final?
- Qual a previsão de conclusão?
- Quais manutenções preventivas estão próximas?
- Quais manutenções preventivas estão vencidas?
- Qual o histórico de manutenção da viatura?
- Quais viaturas geram maior custo de manutenção?

---

## 3. Premissas

- A entidade **Viatura** já existe no sistema.
- O módulo de Manutenção não deve criar um novo cadastro de viatura.
- O módulo deve apenas referenciar a viatura existente por meio de `viatura_id`.
- O odômetro deve ser usado como base para controle de manutenção preventiva por quilometragem.
- Toda manutenção deve gerar histórico vinculado à viatura.
- Toda alteração relevante deve gerar log de auditoria.
- O checklist deve ser usado como ponto de controle operacional da viatura.

---

## 4. Público-Alvo

O módulo será utilizado por diferentes perfis:

- Gestores de frota
- Responsáveis por manutenção
- Motoristas ou condutores
- Oficinas internas
- Oficinas credenciadas
- Aprovadores financeiros ou operacionais
- Usuários administrativos
- Administradores do sistema

---

## 5. Escopo do Módulo

### 5.1 Dentro do Escopo

- Cadastro e gestão de oficinas próprias e credenciadas
- Abertura e acompanhamento de Ordens de Serviço
- Manutenção preventiva por data e quilometragem
- Manutenção corretiva manual ou gerada por checklist
- Checklist de entrega
- Checklist de retorno
- Checklist de manutenção
- Checklist de liberação
- Registro de diagnóstico
- Registro de orçamento
- Aprovação de orçamento
- Registro de serviços executados
- Registro de peças utilizadas
- Bloqueio e liberação de viaturas
- Histórico de manutenção da viatura
- Dashboard de manutenção
- Alertas operacionais
- Logs de auditoria

### 5.2 Fora do Escopo Inicial

Os itens abaixo podem ser considerados para versões futuras:

- Integração com telemetria
- Controle avançado de estoque de peças
- Integração financeira completa
- Portal externo para oficinas credenciadas
- Assinatura digital
- Manutenção preditiva automatizada
- Ranking avançado de oficinas
- Integração com notas fiscais
- Integração com contratos de fornecedores
- Controle de contratos de oficinas credenciadas
- Controle de saldo contratual por valor global
- Abatimento automático de valores consumidos por OS
- Gestão de gestor do contrato, fiscal administrativo e fiscal técnico

---

## 6. Entidades Principais

### 6.1 Viatura

A entidade **Viatura** já existe no sistema e não deve ser remodelada como cadastro principal deste módulo.

No módulo de Manutenção, a viatura deve ser apenas referenciada por `viatura_id`.

O módulo deve consumir os dados já existentes da viatura, como:

- `id`
- `placa`
- `prefixo` ou código interno
- `marca`
- `modelo`
- `ano`
- `tipo`
- `unidade` ou base
- `status_operacional_atual`
- `odometro_atual`
- `plano_manutencao_id`, quando existir

O módulo de Manutenção pode atualizar informações operacionais da viatura quando necessário, como:

- Status operacional
- Odômetro atual
- Data da última manutenção
- Próxima manutenção preventiva
- Bloqueio para uso
- Liberação para uso

#### Status Operacionais Sugeridos da Viatura

- `disponivel`
- `em_uso`
- `agendada_para_manutencao`
- `em_manutencao`
- `aguardando_aprovacao`
- `aguardando_peca`
- `bloqueada`
- `indisponivel`
- `baixada`

---

### 6.2 Oficina

A entidade **Oficina** representa uma oficina própria ou credenciada.

No MVP, o cadastro de oficinas deve ser simples e operacional. A oficina serve principalmente para identificar **quem executará a manutenção** e **quais tipos de serviço ela presta**.

As especialidades prestadas pela oficina funcionam como **filtro auxiliar** na escolha da oficina, não como regra obrigatória de bloqueio.

Exemplo: ao abrir uma OS com problema elétrico, o sistema pode permitir filtrar oficinas que prestam serviço de elétrica.

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador da oficina |
| `tipo` | Própria ou credenciada |
| `nome` | Nome da oficina |
| `documento` | CNPJ ou CPF, quando aplicável |
| `telefone` | Telefone de contato |
| `email` | E-mail de contato |
| `cidade` | Cidade |
| `uf` | Unidade federativa |
| `especialidades` | Serviços/especialidades prestadas |
| `status` | Ativa ou inativa |
| `observacoes` | Observações gerais |

#### Tipos de Oficina

- `propria`
- `credenciada`

#### Status da Oficina

- `ativa`
- `inativa`

#### Especialidades Sugeridas

- Mecânica
- Elétrica
- Funilaria
- Pintura
- Pneus
- Freios
- Suspensão
- Motor
- Câmbio
- Ar-condicionado
- Diagnóstico
- Outros

#### Contratos de Oficina

O controle de contratos ficará para uma fase futura.

No cenário futuro, uma oficina credenciada poderá estar vinculada a um contrato de valor global. A cada serviço executado e aprovado, o valor correspondente poderá ser abatido do saldo disponível do contrato.

Esse controle não faz parte do MVP, mas deve ser considerado em modelagem futura para permitir rastreabilidade entre:

- Contrato vinculado à oficina
- Valor global contratado
- Saldo disponível
- Serviços executados
- Valores consumidos por OS
- Gestor do contrato
- Fiscal administrativo
- Fiscal técnico

No MVP, a OS deve registrar apenas a oficina responsável e os custos estimado/final da manutenção. A vinculação formal com contrato, controle de saldo e acompanhamento por fiscais ficam para versões futuras.

---

### 6.3 Ordem de Serviço

A **Ordem de Serviço**, também chamada de **OS**, deve ser a entidade central do módulo.

Ela representa o registro formal de uma manutenção preventiva ou corretiva.

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador da OS |
| `numero` | Número da Ordem de Serviço |
| `viatura_id` | Viatura vinculada |
| `unidade_id` | Unidade ou base da viatura |
| `tipo_manutencao` | Preventiva, corretiva ou preditiva |
| `origem` | Origem da OS |
| `prioridade` | Baixa, média, alta ou crítica |
| `status` | Status atual da OS |
| `descricao_problema` | Descrição do problema ou necessidade |
| `diagnostico` | Diagnóstico informado pela oficina ou manutenção |
| `oficina_id` | Oficina responsável |
| `servico_necessario` | Tipo de serviço/especialidade necessária |
| `solicitante_id` | Usuário que abriu a solicitação |
| `responsavel_id` | Responsável interno pela OS |
| `aprovador_id` | Usuário aprovador, quando aplicável |
| `data_abertura` | Data de abertura |
| `data_agendamento` | Data agendada para manutenção |
| `data_envio_oficina` | Data de envio para oficina |
| `data_previsao_conclusao` | Previsão de conclusão |
| `data_conclusao` | Data real de conclusão |
| `odometro_abertura` | Odômetro no momento da abertura |
| `odometro_conclusao` | Odômetro no momento da conclusão |
| `custo_estimado` | Valor previsto |
| `custo_final` | Valor final |
| `observacoes` | Observações gerais |
| `anexos` | Fotos, documentos e arquivos vinculados |
| `created_at` | Data de criação |
| `updated_at` | Data da última atualização |

#### Campos Mínimos para o MVP

Os nomes abaixo representam o modelo funcional mínimo. Na implementação GLPI, os campos de relacionamento devem seguir o padrão técnico do plugin, com prefixos de tabela quando aplicável.

```text
id
numero
vehicles_id
tipo_manutencao
origem
prioridade
status
descricao_problema
diagnostico
workshops_id
solicitante_id
responsavel_id
data_abertura
data_previsao_conclusao
data_conclusao
odometro_abertura
odometro_conclusao
custo_estimado
custo_final
aprovacao_status
aprovador_id
data_aprovacao
justificativa_aprovacao
bloqueia_viatura
observacoes
```

#### Tipos de Manutenção

- `preventiva`
- `corretiva`
- `preditiva` — opcional para versão futura

#### Origem da OS

- `manual`
- `checklist_entrega`
- `checklist_retorno`
- `alerta_preventivo`
- `ocorrencia`
- `integracao_externa`

#### Prioridades

| Prioridade | Descrição |
|---|---|
| `baixa` | Não impede o uso imediato |
| `media` | Exige atenção, mas pode não bloquear operação |
| `alta` | Pode comprometer segurança ou operação |
| `critica` | Compromete uso, segurança ou disponibilidade |

#### Status da OS

- `aberta`
- `em_analise`
- `oficina_definida`
- `diagnostico_orcamento`
- `aguardando_aprovacao`
- `aprovada`
- `em_execucao`
- `concluida`
- `liberada`
- `cancelada`

---

### 6.4 Checklist

O checklist deve registrar o estado da viatura em momentos operacionais importantes.

#### Tipos de Checklist

- `entrega`
- `retorno`
- `manutencao`
- `liberacao`

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador do checklist |
| `tipo` | Entrega, retorno, manutenção ou liberação |
| `viatura_id` | Viatura vinculada |
| `os_id` | OS vinculada, quando aplicável |
| `condutor_id` | Condutor responsável, quando aplicável |
| `responsavel_id` | Usuário responsável pelo checklist |
| `odometro` | Odômetro registrado no momento do checklist |
| `nivel_combustivel` | Nível de combustível |
| `status` | Resultado do checklist |
| `observacoes` | Observações gerais |
| `fotos_anexos` | Fotos e anexos |
| `data_realizacao` | Data e hora da realização |
| `created_at` | Data de criação |
| `updated_at` | Data da última atualização |

#### Campos Específicos por Tipo

##### Checklist de Entrega

Deve registrar:

- Odômetro inicial
- Nível inicial de combustível
- Estado geral da viatura antes do uso
- Itens obrigatórios de segurança
- Fotos, quando necessário
- Responsável pela entrega
- Condutor responsável

##### Checklist de Retorno

Deve registrar:

- Odômetro final
- Nível final de combustível
- Estado geral da viatura após o uso
- Novas avarias
- Problemas informados pelo condutor
- Divergências em relação ao checklist de entrega
- Fotos, quando necessário
- Responsável pelo recebimento

##### Checklist de Manutenção

Deve registrar:

- Odômetro no momento da manutenção
- Estado da viatura durante o serviço
- Itens técnicos verificados
- Observações da oficina ou responsável técnico
- Evidências de execução

##### Checklist de Liberação

Deve registrar:

- Odômetro no momento da liberação
- Validação dos itens reparados
- Confirmação de que a viatura está apta a operar
- Fotos, quando necessário
- Responsável pela liberação

#### Status do Checklist

- `aprovado`
- `aprovado_com_observacao`
- `reprovado`

---

### 6.5 Itens do Checklist

Cada checklist deve possuir uma lista de itens verificados.

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador do item |
| `checklist_id` | Checklist vinculado |
| `categoria` | Categoria do item |
| `nome` | Nome do item verificado |
| `resposta` | Conforme, não conforme ou não aplicável |
| `observacao` | Observação sobre o item |
| `foto_obrigatoria` | Indica se exige foto |
| `gera_manutencao` | Indica se pode gerar OS |
| `bloqueia_viatura` | Indica se bloqueia uso da viatura |
| `criticidade` | Baixa, média, alta ou crítica |

#### Respostas Possíveis

- `conforme`
- `nao_conforme`
- `nao_aplicavel`

#### Categorias Sugeridas

- Odômetro
- Combustível
- Pneus
- Estepe
- Freios
- Faróis
- Lanternas
- Setas
- Buzina
- Limpador de para-brisa
- Documentos
- Lataria
- Vidros
- Retrovisores
- Painel
- Equipamentos obrigatórios
- Itens de segurança
- Motor
- Suspensão
- Parte elétrica
- Interior da viatura

---

### 6.6 Plano de Manutenção Preventiva

O plano de manutenção preventiva define os serviços programados por tipo, modelo ou categoria de viatura.

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador do plano |
| `nome` | Nome do plano |
| `tipo_viatura` | Tipo de viatura aplicável |
| `modelo_aplicavel` | Modelo aplicável, quando necessário |
| `status` | Ativo ou inativo |
| `observacoes` | Observações gerais |

#### Status do Plano

- `ativo`
- `inativo`

---

### 6.7 Itens do Plano de Manutenção Preventiva

Cada plano deve conter itens de manutenção com periodicidade definida.

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador do item |
| `plano_id` | Plano vinculado |
| `servico_id` | Serviço vinculado |
| `descricao` | Descrição do item |
| `periodicidade_km` | Periodicidade em quilômetros |
| `periodicidade_dias` | Periodicidade em dias |
| `antecedencia_alerta_km` | Alerta antes do vencimento por km |
| `antecedencia_alerta_dias` | Alerta antes do vencimento por dias |
| `obrigatorio` | Indica se é obrigatório |
| `status` | Ativo ou inativo |

#### Exemplo de Plano Preventivo

Plano: Revisão padrão de veículo leve

| Serviço | Periodicidade |
|---|---|
| Troca de óleo | A cada 10.000 km ou 6 meses |
| Filtro de óleo | A cada 10.000 km |
| Filtro de ar | A cada 20.000 km |
| Alinhamento | A cada 10.000 km |
| Balanceamento | A cada 10.000 km |
| Revisão de freios | A cada 15.000 km |
| Fluido de freio | A cada 12 meses |

---

### 6.8 Orçamento

O orçamento registra a previsão de custo de uma OS, principalmente para oficinas credenciadas.

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador do orçamento |
| `os_id` | OS vinculada |
| `oficina_id` | Oficina responsável |
| `valor_mao_obra` | Valor de mão de obra |
| `valor_pecas` | Valor das peças |
| `valor_total` | Valor total do orçamento |
| `prazo_estimado` | Prazo previsto para conclusão |
| `garantia` | Garantia do serviço ou peças |
| `status` | Status do orçamento |
| `anexos` | Arquivos anexados |
| `observacoes` | Observações gerais |
| `created_at` | Data de criação |
| `updated_at` | Data da última atualização |

#### Status do Orçamento

- `recebido`
- `em_analise`
- `aprovado`
- `reprovado`
- `solicitado_ajuste`
- `cancelado`

---

### 6.9 Itens do Orçamento

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador do item |
| `orcamento_id` | Orçamento vinculado |
| `tipo` | Serviço ou peça |
| `servico_id` | Serviço vinculado, quando aplicável |
| `peca_id` | Peça vinculada, quando aplicável |
| `descricao` | Descrição do item |
| `quantidade` | Quantidade |
| `valor_unitario` | Valor unitário |
| `valor_total` | Valor total |
| `observacao` | Observação do item |

---

### 6.10 Aprovação

A aprovação registra a autorização formal de orçamento, serviço adicional ou liberação operacional.

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador da aprovação |
| `os_id` | OS vinculada |
| `orcamento_id` | Orçamento vinculado, quando aplicável |
| `aprovador_id` | Usuário aprovador |
| `tipo_aprovacao` | Tipo da aprovação |
| `status` | Status da aprovação |
| `justificativa` | Justificativa, quando aplicável |
| `data_aprovacao` | Data da aprovação ou reprovação |

#### Tipos de Aprovação

- `orcamento`
- `servico_adicional`
- `liberacao_viatura`
- `cancelamento_os`
- `ajuste_custo`

#### Status da Aprovação

- `pendente`
- `aprovado`
- `reprovado`
- `devolvido_para_ajuste`

---

### 6.11 Serviço

Representa um tipo de serviço executado em manutenção.

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador do serviço |
| `nome` | Nome do serviço |
| `categoria` | Categoria do serviço |
| `descricao` | Descrição |
| `valor_referencia` | Valor de referência |
| `status` | Ativo ou inativo |

#### Exemplos de Serviços

- Troca de óleo
- Troca de filtro
- Troca de pastilha de freio
- Alinhamento
- Balanceamento
- Reparo elétrico
- Reparo de suspensão
- Funilaria
- Pintura
- Diagnóstico eletrônico
- Revisão geral

---

### 6.12 Peça

Representa uma peça usada em manutenção.

#### Campos Principais

| Campo | Descrição |
|---|---|
| `id` | Identificador da peça |
| `nome` | Nome da peça |
| `codigo_interno` | Código interno |
| `categoria` | Categoria da peça |
| `unidade_medida` | Unidade de medida |
| `valor_referencia` | Valor de referência |
| `garantia_padrao` | Garantia padrão |
| `status` | Ativo ou inativo |

#### Exemplos de Peças

- Óleo do motor
- Filtro de óleo
- Filtro de ar
- Pastilha de freio
- Disco de freio
- Correia
- Bateria
- Pneu
- Lâmpada
- Amortecedor

---

### 6.13 Histórico da Viatura

O histórico deve ser alimentado automaticamente a partir das OS concluídas, checklists, peças e serviços executados.

#### Dados Registrados

- OS concluídas
- Preventivas realizadas
- Corretivas realizadas
- Peças substituídas
- Serviços executados
- Oficinas utilizadas
- Custos estimados
- Custos finais
- Dias parada
- Falhas recorrentes
- Garantias ativas
- Fotos e anexos
- Checklists vinculados
- Odômetro registrado em cada evento

---

### 6.14 Contratos de Oficinas Credenciadas (Futuro)

O controle de contratos não faz parte do MVP.

Em fase futura, uma oficina credenciada poderá estar vinculada a um contrato de valor global. A cada serviço executado, aprovado e associado ao contrato, o valor correspondente poderá ser abatido do saldo disponível.

#### Dados sugeridos para fase futura

| Campo | Descrição |
|---|---|
| `id` | Identificador do contrato |
| `oficina_id` | Oficina credenciada vinculada |
| `numero_contrato` | Número ou referência do contrato |
| `valor_global` | Valor total contratado |
| `saldo_disponivel` | Saldo contratual disponível |
| `gestor_contrato_id` | Usuário gestor do contrato |
| `fiscal_administrativo_id` | Usuário fiscal administrativo |
| `fiscal_tecnico_id` | Usuário fiscal técnico |
| `status` | Situação do contrato |
| `data_inicio` | Início da vigência |
| `data_fim` | Fim da vigência |

#### Consumo contratual por OS

Em fase futura, uma OS aprovada poderá gerar registro de consumo contratual contendo:

- Contrato vinculado
- OS vinculada
- Oficina responsável
- Valor consumido
- Saldo anterior
- Saldo posterior
- Data do consumo
- Usuário responsável pela aprovação

No MVP, não criar vínculo formal com contrato. A OS registra apenas oficina responsável, custo estimado e custo final.

---

## 7. Relacionamentos Entre Entidades

### 7.1 Relações Principais

- Uma **Viatura** pode ter várias **Ordens de Serviço**.
- Uma **Viatura** pode ter vários **Checklists**.
- Uma **Ordem de Serviço** pertence a uma **Viatura**.
- Uma **Ordem de Serviço** pode ter uma **Oficina** vinculada.
- Uma **Ordem de Serviço** pode ter um ou mais **Orçamentos**.
- Um **Orçamento** pode ter vários **Itens de Orçamento**.
- Uma **Ordem de Serviço** pode ter várias **Aprovações**.
- Uma **Ordem de Serviço** pode gerar registros no **Histórico da Viatura**.
- Um **Checklist** pode gerar uma **Ordem de Serviço**.
- Um **Plano de Manutenção Preventiva** pode estar vinculado a várias viaturas.
- Um **Plano de Manutenção Preventiva** possui vários **Itens de Plano**.

---

## 8. Fluxos Operacionais

## 8.0 Ciclo Essencial da OS no MVP

O processo de manutenção deve ser reduzido no MVP para controlar o ciclo essencial da Ordem de Serviço:

```text
abertura
→ análise
→ vinculação da oficina
→ diagnóstico/orçamento
→ aprovação, se necessário
→ execução
→ conclusão
→ liberação da viatura
```

A oficina entra no processo após a abertura e análise inicial da OS.

Fluxo simplificado:

1. A OS é aberta para uma viatura.
2. O responsável analisa o problema.
3. O tipo de serviço necessário é identificado.
4. Uma oficina ativa é selecionada.
5. A oficina registra diagnóstico, orçamento ou execução.
6. A OS segue para aprovação, execução, conclusão e liberação da viatura.

A oficina deve ficar vinculada à OS para permitir rastreabilidade do atendimento.

## 8.1 Fluxo de Manutenção Corretiva

1. Motorista, gestor ou checklist identifica um problema.
2. O sistema abre uma solicitação ou uma OS corretiva.
3. O gestor ou responsável pela manutenção faz a triagem.
4. A prioridade é definida.
5. Se a prioridade for crítica, a viatura pode ser bloqueada automaticamente.
6. O gestor escolhe uma oficina própria ou credenciada.
7. A oficina realiza o diagnóstico.
8. A oficina registra o orçamento, quando aplicável.
9. O responsável aprova, reprova ou solicita ajuste do orçamento.
10. Após aprovação, o serviço é executado.
11. A oficina registra peças, serviços e conclusão.
12. A viatura passa por checklist de retorno ou liberação.
13. Se o checklist for aprovado, a viatura volta para operação.
14. O sistema atualiza histórico, custos e odômetro da viatura.

---

## 8.2 Fluxo de Manutenção Preventiva

1. O sistema monitora odômetro, data ou horas de uso, quando aplicável.
2. O sistema gera alerta de preventiva próxima.
3. Se atingir o limite configurado, o sistema marca a preventiva como vencida.
4. O gestor agenda a manutenção.
5. A OS preventiva é criada.
6. A oficina executa os serviços previstos no plano.
7. A oficina registra peças e serviços executados.
8. O checklist de retorno ou liberação é realizado.
9. O sistema recalcula a próxima preventiva.
10. O histórico da viatura é atualizado.

---

## 8.3 Fluxo de Checklist de Entrega

1. O condutor ou responsável realiza o checklist antes do uso.
2. O sistema registra odômetro inicial.
3. O sistema registra nível inicial de combustível.
4. O usuário verifica os itens obrigatórios.
5. O usuário anexa fotos, quando necessário.
6. Se todos os itens estiverem conformes, a viatura é liberada.
7. Se houver item não conforme, o sistema pode:
   - Liberar com observação
   - Bloquear a viatura
   - Gerar OS corretiva
8. O sistema atualiza o status operacional da viatura quando aplicável.

---

## 8.4 Fluxo de Checklist de Retorno

1. O condutor devolve a viatura.
2. O sistema registra odômetro final.
3. O sistema registra nível final de combustível.
4. O usuário informa avarias, problemas ou observações.
5. O sistema compara o retorno com a entrega, quando houver checklist de entrega relacionado.
6. Se houver divergência, gera alerta ou ocorrência.
7. Se houver problema crítico, a viatura é bloqueada.
8. Se houver item que gere manutenção, o sistema abre OS corretiva.
9. Se aprovado, a viatura fica disponível.
10. O odômetro atual da viatura é atualizado, se o valor informado for maior que o valor registrado.

---

## 8.5 Fluxo de Orçamento e Aprovação

1. A oficina registra ou envia o orçamento.
2. O sistema registra serviços, peças, valores e prazo.
3. Se o valor estiver abaixo do limite configurado, segue aprovação simples.
4. Se o valor ultrapassar o limite, exige aprovação superior.
5. O aprovador pode aprovar, reprovar ou solicitar ajuste.
6. Se aprovado, a OS segue para execução.
7. Se reprovado, a OS retorna para análise.
8. Serviço adicional durante a execução deve gerar nova aprovação.
9. Toda aprovação deve registrar usuário, data, status e justificativa quando necessário.

---

## 8.6 Fluxo de Liberação da Viatura

1. A oficina conclui o serviço.
2. A OS muda para `concluida_pela_oficina`.
3. O responsável realiza checklist de retorno ou liberação.
4. O sistema verifica se existem pendências, reprovações ou aprovações pendentes.
5. Se tudo estiver conforme, a viatura é liberada para operação.
6. O status da OS muda para `liberada_para_operacao`.
7. O status da viatura muda para `disponivel`.
8. O histórico da viatura é atualizado.

---

## 9. Regras de Negócio

### 9.1 Regras Gerais

- Toda OS deve ter uma viatura vinculada.
- Toda OS deve ter tipo de manutenção.
- Toda OS deve ter origem.
- Toda OS deve ter prioridade.
- Toda OS deve ter responsável interno.
- Uma OS enviada para oficina deve ter oficina vinculada.
- Uma OS concluída deve gerar histórico da viatura.
- Uma OS concluída deve registrar custo final, mesmo que seja zero.
- Uma OS concluída deve exigir checklist de retorno ou liberação.
- Uma OS concluída não deve ser excluída.
- Uma OS concluída só pode sofrer ajuste por meio de registro de correção ou estorno, conforme regra administrativa.

---

### 9.2 Regras de Bloqueio da Viatura

- Uma viatura com OS crítica aberta deve ficar indisponível ou bloqueada.
- Uma viatura reprovada no checklist de retorno não pode ser liberada.
- Uma viatura com item crítico não conforme deve ser bloqueada automaticamente.
- Uma manutenção preventiva vencida pode bloquear a viatura, conforme configuração.
- O bloqueio deve registrar motivo, usuário, data e origem.
- A liberação deve registrar responsável, data e justificativa quando aplicável.

---

### 9.3 Regras de Odômetro

- Todo checklist deve registrar o odômetro da viatura.
- O odômetro informado no checklist não pode ser menor que o último odômetro registrado para a viatura, salvo justificativa administrativa.
- No checklist de retorno, o odômetro final deve ser maior ou igual ao odômetro inicial do checklist de entrega.
- Ao concluir um checklist válido, o sistema deve atualizar o odômetro atual da viatura, se o valor informado for maior que o odômetro registrado.
- O odômetro deve ser usado como base para alertas de manutenção preventiva por quilometragem.
- Divergências de odômetro devem gerar alerta para conferência.
- Quando houver justificativa administrativa para odômetro menor, o sistema deve registrar usuário, data, motivo e valor anterior.

---

### 9.4 Regras de Checklist

- Checklist com item crítico não conforme deve bloquear a viatura.
- Checklist reprovado deve impedir a liberação da viatura.
- Item marcado como `gera_manutencao` deve permitir gerar OS corretiva.
- Item com foto obrigatória não pode ser concluído sem anexo.
- Checklist de retorno deve permitir comparação com checklist de entrega.
- Checklist de liberação deve validar se a viatura está apta a retornar para operação.

---

### 9.5 Regras de Orçamento

- Orçamento acima do limite configurado deve exigir aprovação superior.
- Orçamento sem itens não pode ser aprovado.
- Orçamento aprovado deve registrar aprovador, data e status.
- Orçamento reprovado deve exigir justificativa.
- Serviço adicional durante execução deve gerar nova aprovação.
- Alteração de valor após aprovação deve gerar nova aprovação ou log específico.

---

### 9.6 Regras de Preventiva

- Preventiva pode ser controlada por quilometragem, data ou ambos.
- Quando a viatura atingir a antecedência configurada, o sistema deve gerar alerta de preventiva próxima.
- Quando ultrapassar a quilometragem ou data limite, o sistema deve marcar como preventiva vencida.
- Ao concluir uma OS preventiva, o sistema deve recalcular o próximo vencimento.
- O cálculo deve considerar odômetro e data da última execução.

---

### 9.7 Regras de Auditoria

- Alterações de status devem gerar log.
- Alterações de prioridade devem gerar log.
- Troca de oficina deve gerar log.
- Aprovação e reprovação devem gerar log.
- Alteração de custo deve gerar log.
- Bloqueio e liberação da viatura devem gerar log.
- Cancelamento de OS deve gerar log e justificativa.

---

## 10. Perfis e Permissões

### 10.1 Motorista ou Condutor

Pode:

- Preencher checklist de entrega
- Preencher checklist de retorno
- Informar problema na viatura
- Anexar fotos
- Consultar viaturas sob sua responsabilidade

Não pode:

- Aprovar orçamento
- Liberar OS
- Excluir registros
- Alterar custos

---

### 10.2 Gestor de Frota

Pode:

- Abrir OS
- Classificar prioridade
- Vincular oficina
- Acompanhar manutenção
- Liberar ou bloquear viatura
- Visualizar custos e histórico
- Consultar indicadores

---

### 10.3 Responsável pela Manutenção

Pode:

- Fazer triagem
- Alterar status da OS
- Registrar diagnóstico
- Registrar execução
- Validar retorno
- Controlar preventivas
- Solicitar orçamento
- Solicitar aprovação

---

### 10.4 Oficina

Pode:

- Visualizar OS direcionadas
- Registrar diagnóstico
- Enviar orçamento
- Informar peças e serviços
- Registrar conclusão
- Anexar evidências

Não pode:

- Aprovar orçamento próprio
- Liberar viatura diretamente para operação, salvo regra específica
- Alterar dados administrativos da viatura

---

### 10.5 Aprovador

Pode:

- Aprovar orçamento
- Reprovar orçamento
- Solicitar ajuste
- Aprovar serviço adicional
- Registrar justificativa

---

### 10.6 Administrador

Pode:

- Configurar oficinas
- Configurar planos preventivos
- Configurar limites de aprovação
- Configurar permissões
- Parametrizar regras de bloqueio
- Configurar tipos de checklist
- Configurar categorias de serviço

---

## 11. Alertas e Notificações

O sistema deve emitir alertas para eventos relevantes.

### 11.1 Tipos de Alerta

- Preventiva próxima
- Preventiva vencida
- OS atrasada
- Orçamento pendente
- Aprovação pendente
- Viatura parada há muitos dias
- Oficina atrasada
- Item crítico no checklist
- Viatura bloqueada
- Serviço adicional aguardando aprovação
- Previsão de conclusão vencida
- Divergência de odômetro
- Checklist reprovado

### 11.2 Campos do Alerta

| Campo | Descrição |
|---|---|
| `id` | Identificador do alerta |
| `tipo` | Tipo do alerta |
| `severidade` | Baixa, média, alta ou crítica |
| `destinatario_id` | Usuário destinatário |
| `data_hora` | Data e hora do alerta |
| `acao_esperada` | Ação recomendada |
| `registro_relacionado` | OS, checklist, viatura ou orçamento |
| `lido` | Indica se o alerta foi lido |

### 11.3 Severidades

- `baixa`
- `media`
- `alta`
- `critica`

---

## 12. Telas do Sistema

## 12.1 Dashboard de Manutenção

### Objetivo

Exibir a situação geral da manutenção da frota.

### Cards Sugeridos

- Viaturas em manutenção
- Viaturas indisponíveis
- OS abertas
- OS atrasadas
- Preventivas próximas
- Preventivas vencidas
- Orçamentos pendentes
- Custo de manutenção no mês
- Custo médio por viatura
- Tempo médio de parada

### Filtros

- Período
- Unidade ou base
- Tipo de viatura
- Oficina
- Status da OS
- Tipo de manutenção
- Prioridade

---

## 12.2 Lista de Ordens de Serviço

### Objetivo

Permitir consulta e acompanhamento das OS.

### Colunas Sugeridas

- Número da OS
- Viatura
- Placa
- Tipo de manutenção
- Prioridade
- Oficina
- Status
- Data de abertura
- Previsão de conclusão
- Custo estimado
- Responsável

### Ações

- Nova OS
- Visualizar detalhe
- Editar
- Enviar para oficina
- Registrar diagnóstico
- Registrar orçamento
- Aprovar orçamento
- Registrar execução
- Fazer checklist de retorno
- Liberar viatura
- Cancelar OS

---

## 12.3 Detalhe da Ordem de Serviço

### Objetivo

Exibir todas as informações de uma OS.

### Seções Sugeridas

- Dados gerais
- Viatura vinculada
- Descrição do problema
- Diagnóstico
- Oficina
- Orçamento
- Aprovações
- Serviços executados
- Peças utilizadas
- Checklists vinculados
- Anexos
- Histórico de status
- Logs de auditoria

---

## 12.4 Nova Ordem de Serviço

### Objetivo

Permitir abertura manual de uma OS.

### Campos Obrigatórios

- Viatura
- Tipo de manutenção
- Origem
- Prioridade
- Descrição
- Odômetro de abertura
- Responsável

### Campos Opcionais

- Oficina
- Data de agendamento
- Fotos ou anexos
- Observações

---

## 12.5 Cadastro de Oficinas

### Objetivo

Gerenciar oficinas próprias e credenciadas de forma simples e operacional.

### Campos do MVP

- Nome da oficina
- Tipo: própria ou credenciada
- Documento
- Telefone
- E-mail
- Cidade/UF
- Status: ativa ou inativa
- Serviços/especialidades prestadas
- Observações

### Serviços/Especialidades

A oficina deve permitir informar uma ou mais especialidades:

- Mecânica
- Elétrica
- Funilaria
- Pintura
- Pneus
- Freios
- Suspensão
- Motor
- Câmbio
- Ar-condicionado
- Diagnóstico
- Outros

No MVP, essas especialidades devem funcionar como filtro auxiliar na escolha da oficina, não como regra obrigatória de bloqueio.

### Ações

- Nova oficina
- Editar
- Ativar
- Inativar
- Visualizar histórico de atendimentos

---

## 12.6 Planos Preventivos

### Objetivo

Cadastrar e gerenciar planos de manutenção preventiva.

### Funcionalidades

- Criar plano
- Editar plano
- Adicionar itens ao plano
- Definir periodicidade por km
- Definir periodicidade por dias
- Definir antecedência de alerta
- Vincular plano a tipos ou modelos de viatura

---

## 12.7 Agenda de Manutenção

### Objetivo

Visualizar manutenções agendadas.

### Visões Sugeridas

- Calendário
- Lista
- Por oficina
- Por unidade
- Por tipo de manutenção

### Ações

- Agendar manutenção
- Reagendar
- Cancelar agendamento
- Confirmar envio para oficina

---

## 12.8 Checklists

### Objetivo

Criar e consultar checklists de entrega, retorno, manutenção e liberação.

### Funcionalidades

- Preencher checklist
- Registrar odômetro
- Registrar combustível
- Marcar itens como conforme, não conforme ou não aplicável
- Anexar fotos
- Gerar OS a partir de não conformidade
- Bloquear viatura por item crítico

---

## 12.9 Orçamentos e Aprovações

### Objetivo

Controlar orçamento e autorização de manutenção.

### Funcionalidades

- Registrar orçamento
- Adicionar serviços
- Adicionar peças
- Informar valor de mão de obra
- Informar valor total
- Anexar documentos
- Aprovar
- Reprovar
- Solicitar ajuste
- Registrar justificativa

---

## 12.10 Histórico da Viatura

### Objetivo

Exibir todos os eventos de manutenção vinculados à viatura.

### Informações Exibidas

- OS anteriores
- Checklists
- Preventivas realizadas
- Corretivas realizadas
- Peças trocadas
- Serviços executados
- Oficinas utilizadas
- Custos
- Dias parada
- Falhas recorrentes
- Odômetro por evento
- Garantias ativas
- Fotos e anexos

---

## 12.11 Relatórios de Manutenção

### Relatórios Sugeridos

- Custo por viatura
- Custo por oficina
- Custo por unidade
- Custo por tipo de manutenção
- OS por período
- Preventivas vencidas
- Preventivas realizadas no prazo
- Viaturas com maior tempo parada
- Viaturas com falhas recorrentes
- Oficinas com maior retrabalho

---

## 12.12 Configurações do Módulo

### Configurações Sugeridas

- Limites de aprovação
- Regras de bloqueio por checklist
- Regras de bloqueio por preventiva vencida
- Tipos de manutenção
- Prioridades
- Status permitidos
- Categorias de checklist
- Itens padrão de checklist
- Categorias de peças
- Categorias de serviços
- Antecedência padrão de alertas

---

## 13. Indicadores

### Indicadores Operacionais

- Quantidade de OS abertas
- Quantidade de OS atrasadas
- Quantidade de viaturas em manutenção
- Quantidade de viaturas indisponíveis
- Tempo médio de parada
- Tempo médio de conclusão por oficina
- Percentual de OS concluídas no prazo

### Indicadores Financeiros

- Custo total de manutenção
- Custo por viatura
- Custo por km
- Custo por tipo de manutenção
- Custo por oficina
- Custo por unidade
- Custo médio por OS

### Indicadores de Qualidade

- Falhas recorrentes
- Índice de retrabalho por oficina
- Oficinas com maior atraso
- Viaturas com maior custo acumulado
- Preventivas realizadas no prazo
- Preventivas vencidas

---

## 14. Auditoria e Rastreabilidade

O sistema deve registrar logs para ações críticas.

### Eventos Auditáveis

- Abertura de OS
- Alteração de status da OS
- Alteração de prioridade
- Troca de oficina
- Envio de orçamento
- Aprovação de orçamento
- Reprovação de orçamento
- Solicitação de ajuste
- Registro de diagnóstico
- Registro de execução
- Conclusão de serviço
- Checklist aprovado
- Checklist reprovado
- Liberação da viatura
- Bloqueio da viatura
- Alteração de custo
- Alteração de odômetro
- Cancelamento de OS

### Campos do Log

| Campo | Descrição |
|---|---|
| `id` | Identificador do log |
| `usuario_id` | Usuário responsável |
| `data_hora` | Data e hora |
| `acao` | Ação realizada |
| `entidade` | Entidade afetada |
| `entidade_id` | ID do registro afetado |
| `valor_anterior` | Valor anterior |
| `valor_novo` | Novo valor |
| `justificativa` | Justificativa, quando aplicável |

---

## 15. Critérios de Aceite do MVP

O MVP deve atender aos seguintes critérios:

- Deve ser possível cadastrar oficina própria.
- Deve ser possível cadastrar oficina credenciada.
- Deve ser possível informar serviços/especialidades prestadas pela oficina.
- Deve ser possível filtrar oficinas ativas por especialidade ao vincular uma OS.
- Deve ser possível abrir uma OS manualmente.
- Deve ser possível gerar OS a partir de checklist não conforme.
- Deve ser possível classificar OS como preventiva ou corretiva.
- Deve ser possível definir prioridade da OS.
- Deve ser possível alterar o status da OS conforme fluxo permitido.
- Deve ser possível vincular uma OS a uma oficina.
- A OS deve registrar a oficina responsável pela manutenção.
- A OS deve registrar custo estimado e custo final, sem vínculo contratual no MVP.
- Deve ser possível registrar diagnóstico.
- Deve ser possível registrar orçamento.
- Deve ser possível aprovar orçamento.
- Deve ser possível reprovar orçamento.
- Deve ser possível registrar execução da manutenção.
- Deve ser possível registrar peças utilizadas.
- Deve ser possível registrar serviços executados.
- Deve ser possível realizar checklist de entrega.
- Deve ser possível realizar checklist de retorno.
- Deve ser possível registrar odômetro em todo checklist.
- Deve ser possível validar que o odômetro informado não seja menor que o último registrado, salvo justificativa.
- Deve ser possível realizar checklist de liberação após manutenção.
- Uma viatura com checklist reprovado deve ficar bloqueada.
- Uma OS crítica deve bloquear ou indisponibilizar a viatura.
- Uma OS concluída deve atualizar o histórico da viatura.
- Uma OS concluída deve atualizar odômetro, quando aplicável.
- O dashboard deve exibir OS abertas, OS atrasadas, preventivas vencidas e viaturas em manutenção.
- Todas as alterações críticas devem gerar log de auditoria.
- O MVP não deve controlar contratos, saldo contratual, consumo por OS ou fiscais de contrato.

---

## 16. MVP por Fases

## 16.1 MVP 1

Incluir:

- Cadastro de oficinas próprias e credenciadas
- Serviços/especialidades prestadas por oficina como filtro auxiliar
- Abertura de OS
- Manutenção corretiva
- Manutenção preventiva simples por km e data
- Checklist de entrega
- Checklist de retorno
- Registro de odômetro em checklist
- Orçamento
- Aprovação simples
- Registro de serviços executados
- Registro de peças utilizadas
- Histórico da viatura
- Dashboard básico
- Logs essenciais
- Registro de oficina responsável, custo estimado e custo final da OS

Ficam fora do MVP 1:

- Contrato vinculado à oficina
- Valor global contratado
- Saldo disponível
- Abatimento automático por OS
- Consumo contratual
- Gestor do contrato
- Fiscal administrativo
- Fiscal técnico

---

## 16.2 Versão 2

Incluir:

- Agenda avançada de oficina
- Controle de garantias
- Controle básico de peças
- Contratos de oficinas credenciadas
- Limite de aprovação por perfil
- Relatórios avançados
- Ranking de oficinas
- Índice de retrabalho
- Configuração avançada de checklists

---

## 16.3 Versão 3

Incluir:

- Manutenção preditiva
- Integração com telemetria
- Integração com estoque
- Integração financeira
- Assinatura digital
- Portal externo da oficina
- Análise de custo por km em tempo real
- Previsão automática de falhas

---

## 17. Pontos de Atenção para Desenvolvimento

- Evitar duplicar cadastro de viatura dentro do módulo de manutenção.
- Usar `viatura_id` como referência para integração com o cadastro existente.
- Garantir consistência entre status da OS e status operacional da viatura.
- Garantir validação de odômetro em todos os checklists.
- Garantir que OS concluída alimente histórico da viatura.
- Garantir que custos sejam rastreáveis.
- Garantir que aprovações tenham logs.
- Garantir que bloqueios e liberações sejam auditáveis.
- Permitir parametrização de regras de bloqueio.
- Permitir parametrização de limites de aprovação.
- Separar regras de manutenção preventiva por quilometragem e por tempo.
- Evitar exclusão física de registros críticos.
- Usar cancelamento ou inativação quando necessário.
- Manter rastreabilidade entre checklist, OS, orçamento, aprovação e histórico.

---

## 18. Prompt para Time de Produto, UX e Desenvolvimento

```text
Você é um analista de produto especialista em gestão de frota e manutenção de viaturas.

Sua tarefa é detalhar o módulo de Manutenção de Viaturas para um sistema de gestão de frota.

Use como base a especificação em Markdown fornecida neste documento.

O módulo deve controlar oficinas próprias e credenciadas, manutenção preventiva, manutenção corretiva, checklist de entrega, checklist de retorno, checklist de manutenção, checklist de liberação, ordens de serviço, orçamento, aprovação, execução, retorno da viatura e histórico de custos.

Importante:
- A entidade Viatura já existe no sistema.
- Não criar novo cadastro de viatura dentro do módulo de Manutenção.
- O módulo deve apenas referenciar `viatura_id`.
- O módulo pode consumir dados da viatura existente, como placa, prefixo, modelo, unidade, status operacional e odômetro atual.
- O módulo pode atualizar status operacional, bloqueio/liberação e odômetro atual quando aplicável.
- Todo checklist deve registrar odômetro.

Entregue:
1. Histórias de usuário
2. Critérios de aceite por história
3. Modelo de telas
4. Campos por tela
5. Regras de validação
6. Fluxos operacionais
7. Regras de negócio
8. Permissões por perfil
9. Eventos de auditoria
10. Sugestão de backlog por prioridade

Use linguagem objetiva, técnica e organizada.
```
