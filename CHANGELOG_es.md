# Registro de Cambios

Todos los cambios relevantes de este proyecto deben documentarse en este archivo.

El formato se basa en los principios de **Keep a Changelog** y el proyecto debe preferir **Versionado Semántico** para los lanzamientos.

> Las entradas históricas anteriores a esta línea base de documentación no se reconstruyeron por completo aquí.
> Agregue versiones antiguas más adelante solo cuando puedan recuperarse de forma confiable.

## [No publicado]

### Notas no publicadas

- Completar las secciones de lanzamiento siguientes cuando se confirmen las versiones etiquetadas reales.

## [28ABR26] - Carga de CSS y configuración de GLPI en subdirectorio

### 28ABR26 Cambiado

- Se restauró la generación de URLs del plugin compatible con GLPI para que despliegues en la raíz usen `/plugins/...` y despliegues en subdirectorio usen `/glpi/plugins/...`.

- Se rehizo la carga de CSS del plugin para expandir `public/css/app.css` y los imports de hojas de estilo específicas de página antes del renderizado.

- Se agregó resolución de assets públicos de CSS basada en el sistema de archivos, manteniendo los estilos importados restringidos al directorio `public/` del plugin.

- Se aclararon los ejemplos de despliegue Apache para que `glpi-root.conf.example` se use en `http://servidor/` y `glpi-subdir.conf.example` se use en `http://servidor/glpi/`.

- Se agregó una redirección de la URL raíz de `/` a `/glpi/` para el escenario de despliegue en subdirectorio.

- Se eliminó la copia duplicada `glpi.conf` en el nivel del repositorio para evitar confusión con los dos ejemplos de despliegue.

- Se separaron las instrucciones de instalación y configuración Apache en archivos dedicados `INSTALL.md`, `INSTALL_pt-BR.md`, `INSTALL_fr.md` e `INSTALL_es.md`.

- Se agregó documentación de instalación en español en `INSTALL_es.md`.

- Se agregó documentación de changelog en francés en `CHANGELOG_fr.md`.

- Se agregó documentación README en español en `README_vehiclescheduler_es.md`.

- Se estandarizaron los sufijos de archivos de documentación en francés de `_fr-FR` a `_fr`.

- Se redujeron los archivos README a vistas generales del proyecto orientadas a GitHub, con enlaces relativos a las guías de instalación por idioma.

- Se cambiaron los metadatos y la documentación de licencia del proyecto a PolyForm Noncommercial License 1.0.0.

- Se agregó `NOTICE` con atribución a Vinicius Lopes (`generalvini@gmail.com`, Telegram `@ViniciusHonorato`) y al origen original del fork, usuario de Telegram `@mendesmarcio`.

### 28ABR26 Corregido

- Se corrigieron problemas de resolución de hojas de estilo causados por rutas anidadas de CSS `@import`, manteniendo compatibilidad con despliegues bajo `/glpi/plugins/vehiclescheduler`.

- Se corrigió el escenario `http://IP/` que devolvía Apache 403 cuando `/var/www/html` no tenía archivo de índice.

### 28ABR26 Técnico

- Se agregó expansión recursiva de imports CSS locales con protección contra archivos duplicados para evitar cargar la misma hoja de estilo más de una vez.

- Se conservó la carga anterior mediante `<link rel="stylesheet">` como fallback cuando los archivos CSS no puedan resolverse desde disco.

## [27ABR26] - Endurecimiento del MVP y refinamiento de operaciones de flota

### 27ABR26 Agregado

- Pantalla de configuración del plugin con flags operacionales persistidos, incluido el comportamiento automático del checklist de salida después de la aprobación de una reserva.

- Flujo de reporte de incidentes del conductor con acceso para solicitante, lista de gestión, layout de formulario y vínculo opcional con reserva/viaje.

- Soporte para el flujo de checklist de salida y retorno, con pantallas de respuesta alineadas al flujo operativo de la flota.

- Entrada del módulo de multas solo para administradores en el acceso rápido de gestión de flota.

- Catálogo de infracciones RENAINF generado a partir de la planilla brasileña de infracciones de tránsito.

- Selector RENAINF compacto y con búsqueda para multas de conductor, con resultados controlados en la página, desplazamiento interno completo y selección automática de código/desdoblamiento.

- Metadatos RENAINF persistidos para multas: código de infracción, desdoblamiento, base legal, infractor, autoridad, gravedad derivada y manejo de puntos.

- Soporte para infracciones sin puntos del conductor como `Sem pontuação`.

- Ejemplos de despliegue Apache para GLPI en la raíz web y bajo un subdirectorio:

  - `glpi-root.conf.example`

  - `glpi-subdir.conf.example`

- Guía de compatibilidad de ruta raíz en la documentación del proyecto para entornos que usan `/` o `/glpi`.

- Base genérica de feedback flash para reutilización en el proyecto:

  - `public/js/flash.js`

  - `public/css/core/flash.css`

  - patrón auxiliar para mensajes semánticos de éxito, advertencia, información y error.

- Grilla compacta personalizada de gestión de vehículos que reemplaza la lista de búsqueda predeterminada de GLPI en `front/vehicle.php`.

- Filtrado client-side de la grilla de vehículos por texto de búsqueda, estado activo y categoría CNH requerida.

- Estilo compacto de la grilla de vehículos en `public/css/pages/vehicle-grid.css`.

- Comportamiento de la lista operacional de vehículos en `public/js/vehicle-grid.js`.

- Variante en portugués (`pt-BR`) de la documentación README del repositorio.

### 27ABR26 Cambiado

- Se reformuló el layout del panel de gestión como una consola operacional más profesional, incluyendo acceso rápido mejorado, franja de KPIs y mejor ubicación de los controles visuales.

- Se estandarizó la lista de reservas y el layout del formulario de reserva para coincidir con el patrón visual actual de gestión de flota.

- Se reformularon la lista de multas, el formulario de multa y la pestaña de multa del conductor con el layout operacional compacto usado por el resto del plugin.

- La gravedad y los puntos de la multa pasaron a derivarse de la infracción RENAINF seleccionada en lugar de editarse manualmente.

- Se mejoró el modo standalone del wallboard administrativo con salida UTF-8, reloj/cuenta regresiva funcional y barra superior de GLPI oculta.

- Se actualizó el layout del formulario de configuración para seguir el mismo lenguaje visual de las pantallas de lista operacional.

- Se elevó la versión del plugin a `2.0.5` para cubrir upgrades de schema.

- `front/management.php` y el trabajo relacionado con el dashboard se ajustaron hacia un layout operacional más compacto, incluyendo espaciado más denso y refinamientos del acceso rápido.

- El manejo de URLs del plugin se alineó con expectativas de compatibilidad raíz/subdirectorio en lugar de asumir `/glpi` como base fija.

- El flujo posterior a la creación en `front/driver.form.php` se ajustó para volver a `front/driver.php`.

- El flujo posterior a la creación y actualización en `front/vehicle.form.php` se ajustó para volver a `front/vehicle.php`.

- `front/vehicle.php` se rediseñó para seguir el mismo patrón operacional compacto usado en la grilla personalizada de gestión de conductores.

- La presentación de la lista de vehículos se refinó para:

  - quitar el icono de búsqueda del campo de búsqueda

  - mostrar una etiqueta compacta de abreviatura del vehículo

  - renderizar marca y modelo en líneas separadas

  - mantener columnas operacionales enfocadas en el uso diario de la flota

### 27ABR26 Corregido

- Se corrigió el flujo de guardado/permisos de la configuración del plugin y las redirecciones para acceso no autorizado.

- Se corrigió la ruta de creación/upgrade de `glpi_plugin_vehiclescheduler_configs`.

- Se corrigieron cadenas rotas del dashboard standalone con mojibake UTF-8.

- Se corrigieron la cuenta regresiva de actualización y la inicialización del reloj del dashboard causadas por el orden de carga de scripts.

- Se corrigieron regresiones de layout en el selector de tema dentro de los controles visuales de gestión.

- Se corrigió el overflow de layout del combobox nativo RENAINF al reemplazarlo por un selector controlado en la página.

### 27ABR26 Documentación

- Se rehizo el contenido del README en inglés y portugués a partir de la base existente del proyecto.

- Se documentaron el alcance del MVP, requisitos de instalación/ejecución, setup de dependencias Composer y configuración de perfiles GLPI para acceso de solicitante/admin-aprobador.

- Se corrigieron problemas markdownlint `MD032/blanks-around-lists` en archivos README.

- Línea base de documentación para la guía del proyecto orientada al repositorio.

- `AGENTS.md` como conjunto normativo de reglas para IA/generación de código.

- `CODEX_HANDOFF.md` como guía práctica de implementación para Codex.

- `README.md` reestructurado para separar el contexto público del proyecto de las reglas internas de generación.

- Reglas explícitas para namespaces, imports `use`, layout PSR-4 y coexistencia con legado `inc/*.class.php`.

- Reglas explícitas de compatibilidad con base de datos GLPI 11 para el manejo de SQL crudo.

- Guía explícita para `setup.php`, `hook.php`, upgrades de schema idempotentes e incrementos de versión conscientes de upgrade.

- Las responsabilidades de documentación ahora están divididas por propósito en lugar de concentrar reglas operacionales y arquitectónicas en un solo archivo.

- `AGENTS.md` ahora es intencionalmente conciso y normativo.

- `README.md` ahora está orientado al público y al repositorio.

- `CODEX_HANDOFF.md` ahora es operacional y orientado a la implementación.

### 27ABR26 Técnico

- La guía del proyecto se reforzó alrededor del uso de helpers de URL compatibles con GLPI, como:

  - `plugin_vehiclescheduler_get_root_doc()`

  - `plugin_vehiclescheduler_get_front_url()`

  - helpers de URL para assets públicos

- El renderizado de la lista de vehículos permaneció en la capa `front/`, con normalización de datos de backend/dominio esperada en la entidad/servicio de vehículo.

- El manejo de flash se estructuró para que las páginas destino de redirección rendericen feedback visual, manteniendo el flujo de controller explícito y predecible.

- La documentación se actualizó para aclarar expectativas de despliegue en entornos GLPI basados en Apache.

### 27ABR26 Notas

- Esta entrada consolida los principales ajustes de implementación y documentación producidos durante la conversación de desarrollo actual.

- Algunas ideas discutidas fueron exploratorias; este changelog captura salidas concretas y artefactos de proyecto generados, en lugar de pasos de troubleshooting de terminal u operaciones locales de recuperación Git.

## [0.1.0] - 2026-04-27 13:46 BRT

### 0.1.0 Agregado

- Versión pública inicial del plugin SisViaturas / Vehiclescheduler para GLPI 11.

- Flujo de solicitud de reserva de vehículo.

- Flujo de aprobación y rechazo de reservas.

- Recursos operacionales de asignación de vehículo y conductor.

- Validación de conflictos de fecha/hora para reservas.

- Pantallas de dashboard de gestión, operacional y ejecutivo.

- Visibilidad de solicitante y gestión controlada por permisos del plugin.

- Assets front-end para UI operacional compacta.

- Estructura de localización para etiquetas del plugin.

- Metadatos iniciales del repositorio:

  - `.gitignore`

  - `.gitattributes`

  - `README.md`

  - `CHANGELOG.md`

### 0.1.0 Técnico

- Proyecto preparado como plugin GLPI 11.

- Repositorio inicializado con `main` como rama predeterminada.

- Finales de línea normalizados a LF mediante `.gitattributes`.

- Carpetas locales de desarrollo, caché, build, release y dependencias ignoradas mediante `.gitignore`.

- Flujo inicial de publicación en GitHub preparado usando remote HTTPS.

### 0.1.0 Notas

- Esta entrada representa la versión base destinada a publicarse como el estado inicial del repositorio.
