# Instalação

Este guia cobre a configuração necessária para executar **SisViaturas / Vehiclescheduler** como plugin do GLPI 11.

## Requisitos

- GLPI 11 instalado e funcionando
- PHP 8.1 ou superior
- Composer
- Apache ou outro servidor web configurado para o GLPI
- Acesso shell ao servidor GLPI

## Instalação do Plugin

Coloque o plugin no diretório de plugins do GLPI:

```bash
cd /var/www/glpi/plugins
git clone https://github.com/GeneralVini/vehiclescheduler.git vehiclescheduler
cd vehiclescheduler
```

Instale as dependências PHP:

```bash
composer install
```

Use `composer update` somente quando a intenção for atualizar versões de dependências.

## Ativação no GLPI

1. Abra o GLPI no navegador.
2. Acesse **Configurar > Plugins**.
3. Instale o **SisViaturas / Vehicle Scheduler**.
4. Habilite o plugin.

## Configuração de Perfil

As permissões de solicitante e administrador/aprovador são configuradas na tela nativa de Perfil do GLPI.

Abra o perfil desejado no GLPI e use a aba **Gestao de Frota** adicionada pelo plugin. O formulário é renderizado por `PluginVehicleschedulerProfile` e salvo por `front/profile.form.php`.

Permissões disponíveis do plugin:

- **Acesso ao Portal de Reservas**: permite solicitar reservas e reportar incidentes.
- **Acesso a Gestao de Frota**: permite acessar dashboard, veículos, motoristas, manutenções, relatórios e cadastros. Pode ser configurado como sem acesso, leitura ou escrita/CRUD.
- **Aprovar/Rejeitar Reservas**: permite aprovar ou rejeitar solicitações de reserva.

## Configuração Apache

O repositório inclui dois exemplos de configuração Apache. Use apenas uma configuração ativa no servidor.

### GLPI em `http://servidor/`

Use:

```text
glpi-root.conf.example
```

Neste modo, as rotas do GLPI e do plugin são servidas sem o prefixo `/glpi`:

```text
http://servidor/plugins/vehiclescheduler/front/management.php
```

### GLPI em `http://servidor/glpi/`

Use:

```text
glpi-subdir.conf.example
```

Neste modo, `http://servidor/` redireciona para `http://servidor/glpi/`, e as rotas do GLPI/plugin usam o prefixo `/glpi`:

```text
http://servidor/glpi/plugins/vehiclescheduler/front/management.php
```

## Validação do Apache

Depois de colocar a configuração Apache escolhida no diretório de configuração do servidor, valide e reinicie o Apache:

```bash
apachectl configtest
systemctl restart httpd
systemctl is-active httpd
```

Em sistemas Debian/Ubuntu, o serviço pode se chamar `apache2` em vez de `httpd`.

## Atualização

A partir do diretório do plugin:

```bash
cd /var/www/glpi/plugins/vehiclescheduler
git pull
composer install
```

Use `composer update` somente quando a intenção for atualizar versões de dependências.
