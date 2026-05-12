# SisViaturas CSS Architecture

## Goal
Keep a single CSS entry point for the plugin while avoiding a monolithic stylesheet
with duplicated page rules.

## Entry point
- `app.css`: only file loaded by PHP screens

## Layers
- `core/`: shared tokens, layout primitives, reusable components, compatibility base
- `pages/`: screen-scoped rules only

## Current transition strategy
- `core/legacy-base.css` contains the previous shared application styles
- `pages/*.css` wrap the existing page files while we migrate them gradually
- legacy top-level files such as `management.css` and `schedule.css` are still used
  internally through the new page wrappers

## Rules for future changes
- Shared colors, spacing, shadows, radii, and typography must live in `core/`
- Reusable UI pieces such as buttons, cards, badges, tables, headers, and form
  fields must live in `core/`
- Page files must be scoped by a page root such as `.vs-page-management` or
  `#vs-schedule-queue-root`
- If the same selector or visual pattern appears on more than one screen, move it
  out of the page file into `core/`
- New screens should prefer `pages/<module>.css` instead of inline `<style>` blocks

## Migration direction
1. Remove inline `<style>` blocks from `front/*.php`
2. Move repeated rules from page files into `core/`
3. Keep `app.css` as the single runtime entry point
