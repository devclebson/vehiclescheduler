---
project: SisViaturas
product: "GLPI 11 fleet management plugin"
format: agents-md
version: 4.0
language: en
owner: Vinicius Lopes
technical_key: vehiclescheduler
repository: GeneralVini/vehiclescheduler
---

# SisViaturas Rules

## Purpose
This file is the normative rule set for AI/code generation on SisViaturas.
It must stay concise, prescriptive, and architecture-oriented.

Do not turn this file into a README, changelog, or long narrative handoff.

## Source of truth order
When there is doubt, use this precedence:
1. Official GLPI plugin documentation/tutorial
2. `AGENTS.md`
3. `CODEX_HANDOFF.md`
4. `README.md`
5. Existing project code, only if it does not conflict with the rules above

## Plugin baseline
SisViaturas is a GLPI 11 plugin for fleet and reservation management.
Default plugin mental model:
- `src/` for modern PHP classes
- `front/` for thin PHP entry points and screens
- `ajax/` for thin async endpoints
- `public/css/` for styling
- `public/js/` for client behavior
- `setup.php` for bootstrap/metadata
- `hook.php` for install/uninstall/upgrade logic
- `locales/` for translations
- `tools/` for helper scripts

`inc/*.class.php` is legacy compatibility only, not the preferred target for new code.

## Hard architectural rule
Backend/domain PHP classes are for business logic only.
They must not contain:
- HTML
- page composition
- inline CSS
- inline JavaScript
- `<style>`
- `<script>`
- button markup
- screen layout

## Responsibility split

### Backend / domain
Preferred location:
- `src/...`

Responsibilities:
- ACL and authorization
- validation
- conflict detection
- create/update/delete rules
- approval/rejection flow
- persistence rules
- service logic
- ticket integration
- cache logic
- reporting/aggregation
- search options

Must not contain UI composition.

### Front / rendering
Preferred location:
- `front/*.php`

Responsibilities:
- page rendering
- title and layout composition
- field visibility by permission/profile
- button placement
- calling backend/domain code
- loading CSS/JS assets
- entry-point request flow

Must not become the source of truth for business rules.

### AJAX
Preferred location:
- `ajax/*.php`

Responsibilities:
- thin request/response endpoints
- async orchestration
- calling backend/domain services

Must not duplicate core rules or persistence logic.

### Style
Preferred location:
- `public/css/*.css`

Responsibilities:
- layout
- spacing
- compact UI
- readability
- hover states
- zebra striping
- responsiveness

### Behavior
Preferred location:
- `public/js/*.js`

Responsibilities:
- dynamic field behavior
- toggles
- date/time interactions
- AJAX/resource loading
- non-persistent UI state

JS is not the source of truth for ACL or persistence.

## Namespace and import rules

### `src/` code
All new/refactored classes in `src/` must use PSR-4 namespaces.

Rules:
- Base namespace: `GlpiPlugin\Vehiclescheduler`
- Namespace must mirror the directory tree under `src/`
- Use explicit `use` imports for GLPI classes, vendor classes, PSR interfaces, and project classes
- Remove unused and duplicate imports
- Keep file path, namespace, and class name aligned
- Prefer one main class/interface/trait per file

Examples:
- `src/Service/ReservationConflictService.php` -> `namespace GlpiPlugin\Vehiclescheduler\Service;`
- `src/Controller/ManagementController.php` -> `namespace GlpiPlugin\Vehiclescheduler\Controller;`

### Files that normally stay without namespace
These are usually thin entry points:
- `front/*.php`
- `ajax/*.php`
- `setup.php`
- `hook.php`

They may still import namespaced classes with `use`.

### Legacy classes
- Legacy `inc/*.class.php` may remain in `PluginVehiclescheduler...` format while migrating
- Do not create new legacy-style classes without a compatibility reason

## GLPI 11 database rules
Never use raw SQL through:

```php
$DB->request($sql);
```

Preferred:
- structured criteria arrays with `$DB->request(...)`
- `$DB->doQuery($sql)` only when raw SQL is unavoidable
- iterate with `$DB->fetchAssoc(...)`

Do not place SQL/reporting logic in front-end rendering files.

## setup.php / hook.php / migration rules

### `setup.php`
Keep focused on:
- version constant(s)
- bootstrap/init
- plugin metadata
- requirements
- config checks

### `hook.php`
Keep focused on:
- install
- uninstall
- schema creation/upgrade
- upgrade-time reinforcement for old installs

Rules:
- schema changes must be idempotent
- important schema changes must be reflected in install/upgrade logic
- if upgrade behavior changes, bump plugin version in `setup.php`
- migration files must be explicitly included when used

## Index and schema helpers
- Do not use `$DB->indexExists()`
- Use a project helper to check indexes
- For important schema changes, combine latest table definition + conditional upgrades + conditional indexes

## Configuration rule
For simple plugin settings, prefer GLPI configuration storage before creating custom config tables.

## ACL rule
Reuse existing project permission methods whenever they already exist.
Examples include:
- `PluginVehicleschedulerProfile::canAccessRequester()`
- `PluginVehicleschedulerProfile::canApproveReservations()`
- `PluginVehicleschedulerProfile::canViewManagement()`
- `PluginVehicleschedulerProfile::canEditManagement()`
- `PluginVehicleschedulerSchedule::canAssignResources()`
- `PluginVehicleschedulerSchedule::canChangeStatus()`
- `PluginVehicleschedulerSchedule::canOpenForm()`
- `PluginVehicleschedulerSchedule::canCreateRequest()`

Front-end may hide/show controls.
Backend remains the enforcement layer.

## Routing by responsibility
If the issue is about:
- layout
- spacing
- compactness
- labels
- button placement
- visual density
- hover/striping
- dynamic form behavior

Fix it in `front/`, CSS, or JS.

If the issue is about:
- ACL
- validation
- conflict logic
- persistence
- approval/rejection rules
- ticket integration
- cache
- search options
- reporting/business aggregation

Fix it in backend/domain code.

## Coding standards
- PHP must follow PSR-12
- Cache abstractions should follow PSR-6
- Technical comments/documentation must be in English
- User-facing labels may remain in Portuguese
- Prefer production-ready, maintainable, minimal solutions

## Delivery rule for AI
When asked to change code:
- identify the owning layer first
- do not mix responsibilities
- do not expand legacy unnecessarily
- prefer full-file replacements when the change is broad and structural
- prefer architecture correctness over speed
