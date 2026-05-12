# Registro de Alterações

Todas as alterações relevantes deste projeto devem ser documentadas neste arquivo.

O formato segue os princípios do **Keep a Changelog** e o projeto deve preferir **Versionamento Semântico** para releases.

> Entradas históricas anteriores a esta linha de base de documentação não foram totalmente reconstruídas aqui.
> Adicione releases antigas depois apenas quando elas puderem ser recuperadas com confiabilidade.

## [Não lançado]

### Notas não lançadas

- Preencher as seções de release abaixo quando as versões marcadas reais forem confirmadas.

## [28ABR26] - Carregamento de CSS e configuração GLPI em subdiretório

### 28ABR26 Alterado

- Restaurada a geração de URLs do plugin compatível com GLPI para que implantações na raiz usem `/plugins/...` e implantações em subdiretório usem `/glpi/plugins/...`.

- Refeito o carregamento de CSS do plugin para expandir `public/css/app.css` e imports de folhas de estilo específicas de página antes da renderização.

- Adicionada resolução de assets públicos de CSS baseada no filesystem, mantendo os estilos importados restritos ao diretório `public/` do plugin.

- Esclarecidos os exemplos de implantação Apache para que `glpi-root.conf.example` seja usado em `http://servidor/` e `glpi-subdir.conf.example` seja usado em `http://servidor/glpi/`.

- Adicionado redirecionamento da URL raiz de `/` para `/glpi/` no cenário de implantação em subdiretório.

- Removida a cópia duplicada `glpi.conf` no nível do repositório para evitar confusão com os dois exemplos de implantação.

- Separadas as instruções de instalação e configuração Apache em arquivos dedicados `INSTALL.md`, `INSTALL_pt-BR.md`, `INSTALL_fr.md` e `INSTALL_es.md`.

- Adicionada documentação de instalação em espanhol em `INSTALL_es.md`.

- Adicionada documentação de changelog em francês em `CHANGELOG_fr.md`.

- Adicionada documentação README em espanhol em `README_vehiclescheduler_es.md`.

- Padronizados os sufixos dos arquivos de documentação em francês de `_fr-FR` para `_fr`.

- Reduzidos os arquivos README para visões gerais do projeto voltadas ao GitHub, com links relativos para os guias de instalação por idioma.

- Alterados os metadados e a documentação de licença do projeto para PolyForm Noncommercial License 1.0.0.

- Adicionado `NOTICE` com atribuição a Vinicius Lopes (`generalvini@gmail.com`, Telegram `@ViniciusHonorato`) e à origem original do fork, usuário Telegram `@mendesmarcio`.

### 28ABR26 Corrigido

- Corrigidos problemas de resolução de folhas de estilo causados por caminhos aninhados de CSS `@import`, mantendo compatibilidade com implantações em `/glpi/plugins/vehiclescheduler`.

- Corrigido o cenário `http://IP/` que retornava Apache 403 quando `/var/www/html` não tinha arquivo de índice.

### 28ABR26 Técnico

- Adicionada expansão recursiva de imports CSS locais com proteção contra arquivos duplicados, evitando carregar a mesma folha de estilo mais de uma vez.

- Preservado o carregamento anterior via `<link rel="stylesheet">` como fallback quando os arquivos CSS não puderem ser resolvidos em disco.

## [27ABR26] - Endurecimento do MVP e refinamento das operações de frota

### 27ABR26 Adicionado

- Tela de configuração do plugin com flags operacionais persistidas, incluindo comportamento automático de checklist de saída após aprovação de reserva.

- Fluxo de registro de ocorrências de motorista com acesso para solicitante, lista de gestão, layout de formulário e vínculo opcional com reserva/viagem.

- Suporte ao fluxo de checklist de saída e retorno, com telas de resposta alinhadas ao fluxo operacional da frota.

- Entrada do módulo de multas somente para administradores no acesso rápido da gestão de frota.

- Catálogo de infrações RENAINF gerado a partir da planilha brasileira de infrações de trânsito.

- Seletor RENAINF compacto e pesquisável para multas de motorista, com resultados controlados na página, rolagem interna completa e seleção automática de código/desdobramento.

- Metadados RENAINF persistidos para multas: código da infração, desdobramento, amparo legal, infrator, órgão autuador, gravidade derivada e tratamento de pontos.

- Suporte a infrações sem pontos na CNH como `Sem pontuação`.

- Exemplos de implantação Apache para GLPI na raiz web e em subdiretório:

  - `glpi-root.conf.example`

  - `glpi-subdir.conf.example`

- Orientação de compatibilidade de caminho raiz na documentação do projeto para ambientes usando `/` ou `/glpi`.

- Base genérica de feedback flash para reutilização no projeto:

  - `public/js/flash.js`

  - `public/css/core/flash.css`

  - padrão auxiliar para mensagens semânticas de sucesso, aviso, informação e erro.

- Grade compacta customizada de gestão de veículos substituindo a lista de pesquisa padrão do GLPI em `front/vehicle.php`.

- Filtro client-side da grade de veículos por texto de busca, status ativo e categoria de CNH exigida.

- Estilo compacto da grade de veículos em `public/css/pages/vehicle-grid.css`.

- Comportamento da lista operacional de veículos em `public/js/vehicle-grid.js`.

- Variante em português (`pt-BR`) da documentação README do repositório.

### 27ABR26 Alterado

- Reformulado o layout do painel de gestão para um console operacional mais profissional, incluindo acesso rápido aprimorado, faixa de KPIs e melhor posicionamento dos controles visuais.

- Padronizados a lista de reservas e o layout do formulário de reserva para combinar com o padrão visual atual da gestão de frota.

- Reformuladas a lista de multas, o formulário de multa e a aba de multa do motorista para o layout operacional compacto usado pelo restante do plugin.

- A gravidade e os pontos da multa passaram a ser derivados da infração RENAINF selecionada, em vez de editáveis manualmente.

- Melhorado o modo standalone do wallboard administrativo com saída UTF-8, relógio/contagem regressiva funcionando e barra superior do GLPI oculta.

- Atualizado o layout do formulário de configuração para seguir a mesma linguagem visual das telas de lista operacional.

- Versão do plugin elevada para `2.0.5` para cobertura de upgrade de schema.

- `front/management.php` e trabalhos relacionados ao dashboard foram ajustados para um layout operacional mais compacto, incluindo espaçamento mais denso e refinamentos no acesso rápido.

- O tratamento de URLs do plugin foi alinhado às expectativas de compatibilidade com raiz/subdiretório em vez de assumir `/glpi` como base fixa.

- O fluxo pós-adição de `front/driver.form.php` foi ajustado para retornar a `front/driver.php`.

- O fluxo pós-adição e pós-atualização de `front/vehicle.form.php` foi ajustado para retornar a `front/vehicle.php`.

- `front/vehicle.php` foi redesenhado para seguir o mesmo padrão operacional compacto usado na grade customizada de gestão de motoristas.

- A apresentação da lista de veículos foi refinada para:

  - remover o ícone de busca do campo de pesquisa

  - exibir uma etiqueta compacta de abreviação do veículo

  - renderizar marca e modelo em linhas separadas

  - manter colunas operacionais focadas no uso diário da frota

### 27ABR26 Corrigido

- Corrigido o fluxo de salvamento/permissão da configuração do plugin e redirecionamentos para acesso não autorizado.

- Corrigido o caminho de criação/upgrade de `glpi_plugin_vehiclescheduler_configs`.

- Corrigidas strings quebradas do dashboard standalone com mojibake UTF-8.

- Corrigidas a contagem regressiva de atualização e a inicialização do relógio do dashboard causadas pela ordem de carregamento dos scripts.

- Corrigidas regressões de layout no seletor de tema nos controles visuais da gestão.

- Corrigido overflow de layout no combobox nativo RENAINF ao substituí-lo por um seletor controlado na página.

### 27ABR26 Documentação

- Refeito o conteúdo do README em inglês e português a partir da base existente do projeto.

- Documentados escopo do MVP, requisitos de instalação/execução, setup de dependências Composer e configuração de perfis GLPI para acesso de solicitante/admin-aprovador.

- Corrigidos problemas markdownlint `MD032/blanks-around-lists` nos arquivos README.

- Linha de base de documentação para orientação do projeto voltada ao repositório.

- `AGENTS.md` como conjunto normativo de regras para IA/geração de código.

- `CODEX_HANDOFF.md` como guia prático de implementação para Codex.

- `README.md` reestruturado para separar contexto público do projeto de regras internas de geração.

- Regras explícitas para namespaces, imports `use`, layout PSR-4 e coexistência com legado `inc/*.class.php`.

- Regras explícitas de compatibilidade com banco de dados GLPI 11 para tratamento de SQL bruto.

- Orientação explícita para `setup.php`, `hook.php`, upgrades de schema idempotentes e incrementos de versão conscientes de upgrade.

- Responsabilidades de documentação agora divididas por finalidade, em vez de concentrar regras operacionais e arquiteturais em um único arquivo.

- `AGENTS.md` agora é intencionalmente conciso e normativo.

- `README.md` agora é voltado ao público e ao repositório.

- `CODEX_HANDOFF.md` agora é operacional e orientado à implementação.

### 27ABR26 Técnico

- A orientação do projeto foi reforçada em torno do uso de helpers de URL compatíveis com GLPI, como:

  - `plugin_vehiclescheduler_get_root_doc()`

  - `plugin_vehiclescheduler_get_front_url()`

  - helpers de URL para assets públicos

- A renderização da lista de veículos permaneceu na camada `front/`, com normalização de dados de backend/domínio esperada na entidade/serviço de veículo.

- O tratamento de flash foi estruturado para que páginas de destino de redirecionamento renderizem feedback visual, mantendo o fluxo de controller explícito e previsível.

- A documentação foi atualizada para esclarecer expectativas de implantação em ambientes GLPI baseados em Apache.

### 27ABR26 Notas

- Esta entrada consolida os principais ajustes de implementação e documentação produzidos durante a conversa de desenvolvimento atual.

- Algumas ideias discutidas foram exploratórias; este changelog captura saídas concretas e artefatos de projeto gerados, em vez de passos de troubleshooting de terminal ou operações locais de recuperação Git.

## [0.1.0] - 2026-04-27 13:46 BRT

### 0.1.0 Adicionado

- Versão pública inicial do plugin SisViaturas / Vehiclescheduler para GLPI 11.

- Fluxo de solicitação de reserva de veículo.

- Fluxo de aprovação e rejeição de reservas.

- Recursos operacionais de atribuição de veículo e motorista.

- Validação de conflito de data/hora para reservas.

- Telas de dashboard de gestão, operacional e executivo.

- Visibilidade de solicitante e gestão controlada por permissões do plugin.

- Assets front-end para UI operacional compacta.

- Estrutura de localização para labels do plugin.

- Metadados iniciais do repositório:

  - `.gitignore`

  - `.gitattributes`

  - `README.md`

  - `CHANGELOG.md`

### 0.1.0 Técnico

- Projeto preparado como plugin GLPI 11.

- Repositório inicializado com `main` como branch padrão.

- Finais de linha normalizados para LF por meio de `.gitattributes`.

- Pastas locais de desenvolvimento, cache, build, release e dependências ignoradas por `.gitignore`.

- Fluxo inicial de publicação no GitHub preparado usando remote HTTPS.

### 0.1.0 Notas

- Esta entrada representa a versão de base destinada a ser publicada como o estado inicial do repositório.
