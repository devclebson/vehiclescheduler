# Changelog

All notable changes to this project should be documented in this file.

The format is based on **Keep a Changelog** principles and the project should prefer **Semantic Versioning** for releases.

> Historical entries prior to this documentation baseline were not fully reconstructed here.
> Add older releases later only when they can be recovered reliably.

## [Unreleased]

### Unreleased Notes

- Populate release sections below once actual tagged versions are confirmed.

## [28ABR26] - CSS asset loading and GLPI subdirectory config

### 28ABR26 Changed

- Restored GLPI-aware plugin URL generation so root deployments use `/plugins/...` and subdirectory deployments use `/glpi/plugins/...`.

- Reworked plugin CSS loading to expand `public/css/app.css` and page-specific stylesheet imports before rendering.

- Added filesystem-based public asset resolution for plugin CSS files, keeping imported styles restricted to the plugin `public/` directory.

- Clarified Apache deployment examples so `glpi-root.conf.example` is for `http://server/` and `glpi-subdir.conf.example` is for `http://server/glpi/`.

- Added a root URL redirect from `/` to `/glpi/` for the subdirectory deployment scenario.

- Removed the duplicate repository-level `glpi.conf` copy to avoid confusion with the two deployment examples.

- Split installation and Apache setup instructions into dedicated `INSTALL.md`, `INSTALL_pt-BR.md`, `INSTALL_fr.md`, and `INSTALL_es.md` files.

- Added Spanish installation documentation in `INSTALL_es.md`.

- Added French changelog documentation in `CHANGELOG_fr.md`.

- Added Spanish README documentation in `README_vehiclescheduler_es.md`.

- Standardized French documentation file suffixes from `_fr-FR` to `_fr`.

- Reduced README files to GitHub-facing project overviews with relative links to the language-specific install guides.

- Changed project licensing metadata and documentation to PolyForm Noncommercial License 1.0.0.

- Added `NOTICE` attribution for Vinicius Lopes (`generalvini@gmail.com`, Telegram `@ViniciusHonorato`) and the original fork source, Telegram user `@mendesmarcio`.

### 28ABR26 Fixed

- Fixed stylesheet resolution issues caused by nested CSS `@import` paths while keeping compatibility with deployments under `/glpi/plugins/vehiclescheduler`.

- Fixed the `http://IP/` scenario returning Apache 403 when `/var/www/html` has no index file.

### 28ABR26 Technical

- Added recursive local CSS import expansion with duplicate-file protection to avoid loading the same stylesheet more than once.

- Preserved the previous `<link rel="stylesheet">` loading path as a fallback when CSS files cannot be resolved from disk.

## [27ABR26] - MVP hardening and fleet operations polish

### 27ABR26 Added

- Plugin configuration screen with persisted operational flags, including automatic departure checklist behavior after reservation approval.

- Driver incident reporting flow with requester access, management list, form layout, and optional reservation/trip linkage.

- Departure and return checklist flow support, with checklist response screens aligned to the fleet operation workflow.

- Admin-only fines module entry in fleet management quick access.

- RENAINF infraction catalog generated from the Brazilian traffic violation spreadsheet.

- Compact searchable RENAINF picker for driver fines, with controlled in-page results, full internal scrolling, and automatic code/split selection.

- Persisted RENAINF metadata for fines: violation code, split, legal basis, offender, authority, derived severity, and point handling.

- Support for infractions without driver points as `Sem pontuação`.

- Apache deployment examples for GLPI at the web root and under a subdirectory:

  - `glpi-root.conf.example`

  - `glpi-subdir.conf.example`

- Root path compatibility guidance in project documentation for environments using either `/` or `/glpi`.

- Generic flash feedback foundation for reuse across the project:

  - `public/js/flash.js`

  - `public/css/core/flash.css`

  - helper pattern for semantic success, warning, info, and error messages.

- Custom compact vehicle management grid replacing the default GLPI search list in `front/vehicle.php`.

- Vehicle grid client-side filtering by search text, active status, and required CNH category.

- Compact vehicle grid styling in `public/css/pages/vehicle-grid.css`.

- Operational vehicle list behavior in `public/js/vehicle-grid.js`.

- Portuguese (`pt-BR`) README variant for the repository documentation.

### 27ABR26 Changed

- Reworked management dashboard layout to a more professional operational console, including improved quick access, KPI strip, and visual controls placement.

- Standardized schedule list and reservation form layout to match the current fleet management visual pattern.

- Reworked fines list, fine form, and driver fine tab to the compact operational layout used by the rest of the plugin.

- Made fine severity and points derived from the selected RENAINF infraction instead of manually editable.

- Improved admin wallboard standalone mode with UTF-8 output, working clock/countdown refresh, and hidden GLPI top bar.

- Updated config form layout to follow the same visual language as operational list screens.

- Bumped plugin version to `2.0.5` for schema upgrade coverage.

- `front/management.php` and related dashboard work were adjusted toward a more compact operational layout, including denser spacing and quick-access refinements.

- Plugin URL handling was aligned with root/subdirectory compatibility expectations instead of assuming `/glpi` as a fixed base path.

- `front/driver.form.php` post-add flow was adjusted to return to `front/driver.php`.

- `front/vehicle.form.php` post-add and post-update flow was adjusted to return to `front/vehicle.php`.

- `front/vehicle.php` was redesigned to follow the same compact operational pattern used in the custom driver management grid.

- Vehicle list presentation was refined to:

  - remove the search icon from the search field

  - display a compact vehicle abbreviation pill

  - render brand and model on separate lines

  - keep operational columns focused on daily fleet usage

### 27ABR26 Fixed

- Fixed plugin config save/permission flow and redirects for unauthorized access.

- Fixed creation/upgrade path for `glpi_plugin_vehiclescheduler_configs`.

- Fixed broken standalone dashboard strings with UTF-8 mojibake.

- Fixed dashboard refresh countdown and clock initialization caused by script loading order.

- Fixed theme selector layout regressions in management visual controls.

- Fixed native RENAINF combobox layout overflow by replacing it with an in-page controlled picker.

### 27ABR26 Documentation

- Reworked README content in English and Portuguese from the existing project baseline.

- Documented MVP scope, install/run requirements, Composer dependency setup, and GLPI profile configuration for requester/admin-approver access.

- Fixed markdownlint `MD032/blanks-around-lists` issues in README files.

- Documentation baseline for repository-facing project guidance.

- `AGENTS.md` as the normative rule set for AI/code generation.

- `CODEX_HANDOFF.md` as the practical implementation guide for Codex.

- `README.md` restructured to separate public project context from internal generation rules.

- Explicit rules for namespaces, `use` imports, PSR-4 layout, and legacy coexistence with `inc/*.class.php`.

- Explicit GLPI 11 database compatibility rules for raw SQL handling.

- Explicit guidance for `setup.php`, `hook.php`, idempotent schema upgrades, and upgrade-aware version bumps.

- Documentation responsibilities are now split by purpose instead of concentrating operational and architectural rules in a single file.

- `AGENTS.md` is now intentionally concise and normative.

- `README.md` is now public-facing and repository-oriented.

- `CODEX_HANDOFF.md` is now operational and implementation-oriented.

### 27ABR26 Technical

- Project guidance was reinforced around using GLPI-aware URL helpers such as:

  - `plugin_vehiclescheduler_get_root_doc()`

  - `plugin_vehiclescheduler_get_front_url()`

  - public asset URL helpers

- Vehicle list rendering remained in the `front/` layer, with backend/domain data normalization expected in the vehicle entity/service layer.

- Flash handling was structured so redirect destination pages render visual feedback, keeping controller flow explicit and predictable.

- Documentation was updated to clarify deployment expectations for Apache-based GLPI environments.

### 27ABR26 Notes

- This entry consolidates the main implementation and documentation adjustments produced during the current development chat.

- Some discussed ideas were exploratory; this changelog captures concrete outputs and generated project artifacts rather than terminal troubleshooting steps or local Git recovery operations.

## [0.1.0] - 2026-04-27 13:46 BRT

### 0.1.0 Added

- Initial public version of the SisViaturas / Vehiclescheduler plugin for GLPI 11.

- Vehicle reservation request workflow.

- Approval and rejection flow for reservations.

- Operational vehicle and driver assignment features.

- Date/time conflict validation for reservations.

- Management, operational, and executive dashboard screens.

- Requester and management visibility controlled by plugin permissions.

- Front-end assets for compact operational UI.

- Localization structure for plugin labels.

- Initial repository metadata files:

  - `.gitignore`

  - `.gitattributes`

  - `README.md`

  - `CHANGELOG.md`

### 0.1.0 Technical

- Project prepared as a GLPI 11 plugin.

- Repository initialized with `main` as the default branch.

- Line endings normalized to LF through `.gitattributes`.

- Local development, cache, build, release, and dependency folders ignored through `.gitignore`.

- Initial GitHub publication workflow prepared using HTTPS remote.

### 0.1.0 Notes

- This entry represents the baseline version intended to be published as the initial repository state.
