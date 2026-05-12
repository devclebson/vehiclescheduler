# Instalacion

Esta guia cubre la configuracion necesaria para ejecutar **SisViaturas / Vehiclescheduler** como plugin de GLPI 11.

## Requisitos

- GLPI 11 instalado y funcionando
- PHP 8.1 o superior
- Composer
- Apache u otro servidor web configurado para GLPI
- Acceso shell al servidor GLPI

## Instalacion del Plugin

Coloque el plugin en el directorio de plugins de GLPI:

```bash
cd /var/www/glpi/plugins
git clone https://github.com/GeneralVini/vehiclescheduler.git vehiclescheduler
cd vehiclescheduler
```

Instale las dependencias PHP:

```bash
composer install
```

Use `composer update` solo cuando la intencion sea actualizar versiones de dependencias.

## Activacion en GLPI

1. Abra GLPI en el navegador.
2. Vaya a **Configuracion > Plugins**.
3. Instale **SisViaturas / Vehicle Scheduler**.
4. Habilite el plugin.

## Configuracion de Perfil

Los permisos de solicitante y administrador/aprobador se configuran en la pantalla nativa de Perfil de GLPI.

Abra el perfil deseado en GLPI y use la pestana **Gestao de Frota** agregada por el plugin. El formulario es renderizado por `PluginVehicleschedulerProfile` y guardado por `front/profile.form.php`.

Permisos disponibles del plugin:

- **Acesso ao Portal de Reservas**: permite crear reservas y reportar incidentes.
- **Acesso a Gestao de Frota**: permite acceder al dashboard, vehiculos, conductores, mantenimientos, reportes y registros. Puede configurarse como sin acceso, lectura o escritura/CRUD.
- **Aprovar/Rejeitar Reservas**: permite aprobar o rechazar solicitudes de reserva.

## Configuracion Apache

El repositorio incluye dos ejemplos de configuracion Apache. Use solo una configuracion activa en el servidor.

### GLPI en `http://servidor/`

Use:

```text
glpi-root.conf.example
```

En este modo, las rutas de GLPI y del plugin se sirven sin el prefijo `/glpi`:

```text
http://servidor/plugins/vehiclescheduler/front/management.php
```

### GLPI en `http://servidor/glpi/`

Use:

```text
glpi-subdir.conf.example
```

En este modo, `http://servidor/` redirige a `http://servidor/glpi/`, y las rutas de GLPI/plugin usan el prefijo `/glpi`:

```text
http://servidor/glpi/plugins/vehiclescheduler/front/management.php
```

## Validacion de Apache

Despues de colocar la configuracion Apache elegida en el directorio de configuracion del servidor, valide y reinicie Apache:

```bash
apachectl configtest
systemctl restart httpd
systemctl is-active httpd
```

En sistemas Debian/Ubuntu, el servicio puede llamarse `apache2` en lugar de `httpd`.

## Actualizacion

Desde el directorio del plugin:

```bash
cd /var/www/glpi/plugins/vehiclescheduler
git pull
composer install
```

Use `composer update` solo cuando la intencion sea actualizar versiones de dependencias.
