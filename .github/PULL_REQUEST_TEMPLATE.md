## Description

Describe the change and the motivation behind it.

Fixes # (issue)

## Target Branch

- [ ] `master` (stable — bug fixes, improvements, features for current users)
- [ ] `next/v7` (unreleased rewrite — Automations / React admin / Themes only)

## Super Forms Version

<!-- Which version will first ship this change? e.g. v6.4.201 -->
**Targets version:**

## User Population Affected

- [ ] All users (core form builder behavior)
- [ ] Users on v6.4.100+ (EAV migration / contact entries)
- [ ] Users on v6.4.127+ (listings extension)
- [ ] Add-on users only — add-on name: ___________
- [ ] Admin / developer only (no frontend impact)

## Type of Change

- [ ] Bug fix (non-breaking)
- [ ] New feature (non-breaking)
- [ ] Breaking change (alters behavior for users or third-party integrations)
- [ ] Documentation update
- [ ] Refactor (no behavior change)

## Backward Compatibility Checklist

<!-- Check each box to confirm it is NOT violated. Leave unchecked if not applicable. -->

- [ ] No `super_*` hook or filter name was renamed or removed
- [ ] No `[super_form]` shortcode attribute was removed or renamed
- [ ] Contact entry retrieval uses `SUPER_Data_Access::get_entry_data()`, not raw `get_post_meta()`
- [ ] No Action Scheduler hook name was changed
- [ ] No WordPress option key (`super_*`) was renamed without a migration
- [ ] No public add-on class method was removed or had its signature changed
- [ ] JSHint passes (`npm run jshint`) — or no JS was modified
- [ ] Production build succeeds (`npm run prod`) — or no build files were modified

## Testing

- [ ] Tested locally against WordPress 6.4+
- [ ] Tested the specific scenario from the linked issue
- [ ] Tested with PHP 7.4 (minimum supported)

---

## For AI-Generated PRs (Claude/Codex)

<!-- Populate these. Human contributors may delete this section. -->

**Source issue:** #
**Implementation confidence:** <!-- ready | uncertain | not_feasible -->
**Files modified:**
**New hooks added (if any):** <!-- exact apply_filters() / do_action() names -->
**New option keys added (if any):** <!-- exact get_option() / update_option() keys -->
**New shortcode attributes added (if any):**
**Migration required:** <!-- yes / no — if yes, describe what data is migrated and when -->
