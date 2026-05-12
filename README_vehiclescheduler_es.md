# SisViaturas

Plugin de gestion de flota y programacion de vehiculos para **GLPI 11**.

**SisViaturas** (`vehiclescheduler`) apoya solicitudes de reserva de vehiculos, flujo de aprobacion, asignacion operacional, validacion de conflictos y visibilidad mediante dashboards para la operacion diaria de la flota.

## Alcance Actual del MVP

- CRUD de vehiculos
- CRUD de conductores
- flujo de solicitud/reserva
- dashboard

Modulos operacionales adicionales pueden estar presentes o en evolucion, incluyendo mantenimiento, incidentes, reportes, checklists, multas, siniestros y helpers de tema/interfaz.

## Documentacion

- [INSTALL_es.md](INSTALL_es.md): instalacion, actualizacion, activacion en GLPI y publicacion Apache
- [INSTALL.md](INSTALL.md): guia de instalacion en ingles
- [INSTALL_pt-BR.md](INSTALL_pt-BR.md): guia de instalacion en portugues brasileno
- [INSTALL_fr.md](INSTALL_fr.md): guia de instalacion en frances
- [README.md](README.md): README en ingles
- [README_vehiclescheduler_pt-BR.md](README_vehiclescheduler_pt-BR.md): README en portugues brasileno
- [README_vehiclescheduler_fr.md](README_vehiclescheduler_fr.md): README en frances
- [CHANGELOG_es.md](CHANGELOG_es.md): historico de cambios en espanol
- [CHANGELOG.md](CHANGELOG.md): historico de cambios en ingles
- [CHANGELOG_pt-BR.md](CHANGELOG_pt-BR.md): historico de cambios en portugues brasileno
- [CHANGELOG_fr.md](CHANGELOG_fr.md): historico de cambios en frances
- [AGENTS.md](AGENTS.md): reglas normativas para IA/generacion de codigo
- [CODEX_HANDOFF.md](CODEX_HANDOFF.md): guia practica de implementacion para Codex

## Requisitos

- GLPI 11 instalado y funcionando
- PHP 8.1 o superior
- Composer
- Apache u otro servidor web configurado para GLPI

## Instalacion Rapida

```bash
cd /var/www/glpi/plugins
git clone https://github.com/GeneralVini/vehiclescheduler.git vehiclescheduler
cd vehiclescheduler
composer install
```

Luego abra GLPI, vaya a **Configuracion > Plugins**, instale **SisViaturas / Vehicle Scheduler** y habilite el plugin.

Para ejemplos Apache y pasos completos, consulte [INSTALL_es.md](INSTALL_es.md).

## Direccion Tecnica

El proyecto sigue una separacion estricta entre logica de negocio y renderizado de interfaz:

- `src/`: ubicacion preferida para backend/dominio nuevo o refactorizado
- `front/`: entry points PHP delgados y renderizado de paginas
- `ajax/`: endpoints asincronos delgados
- `public/css/`: estilos
- `public/js/`: comportamiento en el cliente
- `locales/`: traducciones
- `inc/`: clases legacy/compatibles mientras ocurre la migracion

Las clases backend/dominio no deben contener layout de pantalla, CSS inline, JavaScript inline, composicion de pagina ni marcado de botones.

## Modos de Publicacion Apache

El repositorio incluye dos ejemplos Apache. Mantenga solo uno activo en el directorio de configuracion Apache del servidor:

- [glpi-root.conf.example](glpi-root.conf.example): GLPI en `http://servidor/`
- [glpi-subdir.conf.example](glpi-subdir.conf.example): GLPI en `http://servidor/glpi/`

Las URLs del plugin deben usar helpers compatibles con GLPI en lugar de asumir rutas fijas como `/glpi`.

## Licencia y Atribucion

SisViaturas / Vehiclescheduler esta licenciado bajo la [PolyForm Noncommercial License 1.0.0](LICENSE).

El proyecto es mantenido por Vinicius Lopes (`generalvini@gmail.com`, Telegram `@ViniciusHonorato`) y se origino como un fork de un trabajo del usuario Telegram `@mendesmarcio`. La atribucion a ambos debe preservarse en forks, redistribuciones y trabajos derivados. El uso comercial no esta permitido sin autorizacion previa por escrito de Vinicius Lopes.

Consulte [NOTICE](NOTICE) para los avisos de atribucion obligatorios.
