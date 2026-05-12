# Installation

This guide covers the setup required to run **SisViaturas / Vehiclescheduler** as a GLPI 11 plugin.

## Requirements

- GLPI 11 installed and working
- PHP 8.1 or newer
- Composer
- Apache or another web server configured for GLPI
- Shell access to the GLPI server

## Plugin Installation

Place the plugin under the GLPI plugins directory:

```bash
cd /var/www/glpi/plugins
git clone https://github.com/GeneralVini/vehiclescheduler.git vehiclescheduler
cd vehiclescheduler
```

Install PHP dependencies:

```bash
composer install
```

Use `composer update` only when dependency versions intentionally need to be refreshed.

## GLPI Activation

1. Open GLPI in the browser.
2. Go to **Setup > Plugins**.
3. Install **SisViaturas / Vehicle Scheduler**.
4. Enable the plugin.

## Profile Configuration

Requester and administrator/approver access is configured in the native GLPI profile screen.

Open the target profile in GLPI and use the **Gestao de Frota** tab added by the plugin. The form is rendered by `PluginVehicleschedulerProfile` and saved through `front/profile.form.php`.

Available plugin rights:

- **Acesso ao Portal de Reservas**: allows users to create reservations and report incidents.
- **Acesso a Gestao de Frota**: allows access to dashboard, vehicles, drivers, maintenance, reports, and registrations. It may be configured as no access, read access, or write/CRUD access.
- **Aprovar/Rejeitar Reservas**: allows users to approve or reject reservation requests.

## Apache Configuration

The repository includes two Apache examples. Use only one active configuration on the server.

### GLPI at `http://server/`

Use:

```text
glpi-root.conf.example
```

In this mode, GLPI and plugin routes are served without the `/glpi` URL prefix:

```text
http://server/plugins/vehiclescheduler/front/management.php
```

### GLPI at `http://server/glpi/`

Use:

```text
glpi-subdir.conf.example
```

In this mode, `http://server/` redirects to `http://server/glpi/`, and GLPI/plugin routes use the `/glpi` prefix:

```text
http://server/glpi/plugins/vehiclescheduler/front/management.php
```

## Apache Validation

After placing the selected Apache config in the server configuration directory, validate and restart Apache:

```bash
apachectl configtest
systemctl restart httpd
systemctl is-active httpd
```

On Debian/Ubuntu systems, the service may be named `apache2` instead of `httpd`.

## Update

From the plugin directory:

```bash
cd /var/www/glpi/plugins/vehiclescheduler
git pull
composer install
```

Use `composer update` only when intentionally updating dependency versions.
