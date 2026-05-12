# SisViaturas

Plugin de gestão de frota e agendamento de veículos para **GLPI 11**.

**SisViaturas** (`vehiclescheduler`) apoia solicitações de reserva de veículos, fluxo de aprovação, alocação operacional, validação de conflitos e visibilidade por dashboards para a operação diária da frota.

## Escopo Atual do MVP

- CRUD de veículos
- CRUD de motoristas
- fluxo de solicitação/reserva
- dashboard

Módulos operacionais adicionais podem estar presentes ou em evolução, incluindo manutenção, incidentes, relatórios, checklists, multas, sinistros e helpers de tema/interface.

## Documentação

- [INSTALL_pt-BR.md](INSTALL_pt-BR.md): instalação, atualização, ativação no GLPI e publicação Apache
- [INSTALL.md](INSTALL.md): guia de instalação em inglês
- [INSTALL_fr.md](INSTALL_fr.md): guia de instalação em francês
- [INSTALL_es.md](INSTALL_es.md): guia de instalação em espanhol
- [README.md](README.md): README em inglês
- [README_vehiclescheduler_fr.md](README_vehiclescheduler_fr.md): README em francês
- [README_vehiclescheduler_es.md](README_vehiclescheduler_es.md): README em espanhol
- [CHANGELOG_pt-BR.md](CHANGELOG_pt-BR.md): histórico de mudanças em português brasileiro
- [CHANGELOG.md](CHANGELOG.md): histórico de mudanças em inglês
- [CHANGELOG_fr.md](CHANGELOG_fr.md): histórico de mudanças em francês
- [CHANGELOG_es.md](CHANGELOG_es.md): histórico de mudanças em espanhol
- [AGENTS.md](AGENTS.md): regras normativas para IA/geração de código
- [CODEX_HANDOFF.md](CODEX_HANDOFF.md): orientação prática de implementação para Codex

## Requisitos

- GLPI 11 instalado e funcionando
- PHP 8.1 ou superior
- Composer
- Apache ou outro servidor web configurado para o GLPI

## Instalação Rápida

```bash
cd /var/www/glpi/plugins
git clone https://github.com/GeneralVini/vehiclescheduler.git vehiclescheduler
cd vehiclescheduler
composer install
```

Depois, abra o GLPI, acesse **Configurar > Plugins**, instale **SisViaturas / Vehicle Scheduler** e habilite o plugin.

Para exemplos Apache e passos completos, consulte [INSTALL_pt-BR.md](INSTALL_pt-BR.md).

## Direção Técnica

O projeto segue separação rígida entre lógica de negócio e renderização de interface:

- `src/`: local preferencial para backend/domínio novo ou refatorado
- `front/`: entry points PHP finos e renderização de páginas
- `ajax/`: endpoints assíncronos finos
- `public/css/`: estilos
- `public/js/`: comportamento no cliente
- `locales/`: traduções
- `inc/`: classes legadas/compatíveis enquanto a migração ocorre

Classes backend/domínio não devem conter layout de tela, CSS inline, JavaScript inline, composição de página ou marcação de botões.

## Modos de Publicação Apache

O repositório inclui dois exemplos Apache. Mantenha apenas um ativo no diretório de configuração do Apache do servidor:

- [glpi-root.conf.example](glpi-root.conf.example): GLPI em `http://servidor/`
- [glpi-subdir.conf.example](glpi-subdir.conf.example): GLPI em `http://servidor/glpi/`

As URLs do plugin devem usar helpers compatíveis com GLPI em vez de assumir caminhos fixos como `/glpi`.

## Licença e Atribuição

SisViaturas / Vehiclescheduler é licenciado sob a [PolyForm Noncommercial License 1.0.0](LICENSE).

O projeto é mantido por Vinicius Lopes (`generalvini@gmail.com`, Telegram `@ViniciusHonorato`) e se originou como um fork de um trabalho do usuário Telegram `@mendesmarcio`. A atribuição a ambos deve ser preservada em forks, redistribuições e trabalhos derivados. Uso comercial não é permitido sem autorização prévia por escrito de Vinicius Lopes.

Consulte [NOTICE](NOTICE) para os avisos de atribuição obrigatórios.
