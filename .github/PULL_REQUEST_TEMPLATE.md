## Description

<!-- What changes and why. -->

Fixes # (issue)

## Target Branch

- [ ] `stable` (current stable line: bug fixes, security patches, backward-compatible improvements)
- [ ] `beta` (next-minor line: feature work and release-candidate soak)
- [ ] `alpha` (internal next-major development, unreleased; not for current-user fixes)
- [ ] LTS branch (security and critical bugfix backports only)

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

## Verification

<!-- Keep this heading. Describe what proves the change works: tests run,
     manual checks, the scenario from the linked issue. There is no hosted CI
     on pull requests, so these local checks are the floor. -->

- [ ] `npm run jshint` passes, or no JavaScript was modified
- [ ] `npm run prod` succeeds, or no build sources were modified
- [ ] Tested against a supported WordPress install (PHP 7.4 minimum)
- [ ] The specific scenario from the linked issue was exercised
