# SisViaturas Codex Handoff

## Purpose
This document complements `AGENTS.md`.
It explains how the project behaves in practice and how Codex should reason before changing files.

If there is any conflict:
1. `AGENTS.md` wins
2. this handoff comes next
3. `README.md` is public-facing context

## What Codex should understand first
SisViaturas is not a generic PHP project.
It is a GLPI 11 plugin with a strong boundary between:
- backend/domain rules
- front-end rendering
- CSS styling
- JS behavior

The project regularly rejects fixes that solve a UI problem in a domain class or solve a business-rule problem in a front file.

## Practical architecture map

### Backend/domain changes belong in
- `src/...`
- legacy `inc/*.class.php` only when touching code that has not yet migrated

Use backend/domain for:
- ACL
- validation
- conflict rules
- status transitions
- approval/rejection rules
- persistence
- ticket integration
- cache invalidation
- reporting/business aggregation

### Front/UI changes belong in
- `front/*.php`
- `public/css/*.css`
- `public/js/*.js`
- `ajax/*.php` for async transport

Use UI-side changes for:
- layout
- spacing
- compactness
- labels
- button placement
- section toggles
- hover/striping
- responsive adjustments
- dynamic field behavior

## Namespace and import expectations

### New/refactored classes in `src/`
Always:
- declare a namespace under `GlpiPlugin\Vehiclescheduler\...`
- mirror the folder structure
- import dependencies with `use`
- remove duplicate and unused imports
- keep file/class/namespace aligned

Examples:
- `src/Service/ReservationApprovalService.php`
- `src/Controller/ManagementController.php`
- `src/Repository/VehicleRepository.php`

### Thin entry points
These usually stay procedural and without namespace declarations:
- `front/*.php`
- `ajax/*.php`
- `setup.php`
- `hook.php`

They may still consume namespaced classes via `use`.

### Legacy area
- old `inc/*.class.php` files may remain in `PluginVehiclescheduler...` format
- do not create new legacy classes unless compatibility forces it
- prefer migrating old logic into `src/` over growing `inc/`

## GLPI 11 database lesson
A known failure mode in this project is generating raw SQL through:

```php
$DB->request($sql);
```

Do not do that.

Preferred options:
- structured criteria with `$DB->request(...)`
- `$DB->doQuery($sql)` only when raw SQL is unavoidable
- iterate with `$DB->fetchAssoc(...)`

If Codex ignores this, the result may break in GLPI 11.

## Upgrade lesson
When an upgrade appears to do nothing, common causes are:
- plugin version not bumped
- schema logic only partially added
- migration file not included
- stale opcode/runtime cache

For important schema changes, reinforce them through:
- latest expected table definition
- conditional upgrade helpers
- conditional index creation helper
- explicit upgrade logic in the install/upgrade cycle

## ACL lesson
The project already has permission helpers.
Reuse them before inventing new ACL checks.

Typical examples:
- `PluginVehicleschedulerProfile::canAccessRequester()`
- `PluginVehicleschedulerProfile::canApproveReservations()`
- `PluginVehicleschedulerProfile::canViewManagement()`
- `PluginVehicleschedulerProfile::canEditManagement()`
- `PluginVehicleschedulerSchedule::canAssignResources()`
- `PluginVehicleschedulerSchedule::canChangeStatus()`
- `PluginVehicleschedulerSchedule::canOpenForm()`
- `PluginVehicleschedulerSchedule::canCreateRequest()`

Front-end visibility is convenience only.
Backend enforcement is mandatory.

## UI direction learned from project feedback
The project prefers a compact operational interface.

Good patterns:
- tighter spacing
- strong readability at 100% zoom
- consistent KPI cards
- zebra striping in dense tables
- hover highlight for active rows
- practical date/time abbreviations in list views
- coherent CSS patches instead of scattered one-off tweaks

Bad patterns:
- oversized headers/cards
- layouts that only look right at 80% or 90% zoom
- tiny subtitles on dashboards
- metadata broken across disconnected rows
- UI hacks inside domain classes

## Request handling and sanitization
There was explicit concern about weak direct request handling.
Preferred direction:
- sanitize request data consistently
- avoid ad hoc direct reads from `$_GET`/`$_POST`
- validate integer bounds and string length
- make sanitization reusable

## How Codex should deliver changes
Preferred delivery style:
- explain which layer owns the change
- explain why the file choice is correct
- keep compatibility with GLPI 11
- avoid unnecessary abstraction
- prefer complete file replacements when the requested change is broad
- prefer a safe CSS patch when a visual fix spans many components

## Pre-change checklist
Before writing code, verify:
1. which layer owns the change
2. whether a helper or permission method already exists
3. whether GLPI 11 database restrictions apply
4. whether version/install/upgrade behavior is impacted
5. whether namespace/import rules are being followed
6. whether the UI must remain readable at 100% zoom
7. whether the output should be a full file or a patch

## Final rule
If there is a choice between a quick hack and the correct architectural solution, choose the architectural solution.
