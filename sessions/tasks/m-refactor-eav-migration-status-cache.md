---
name: m-refactor-eav-migration-status-cache
branch: feature/m-refactor-eav-migration-status-cache
status: pending
created: 2025-11-01
---

# Cache EAV Migration Status to Reduce Redundant get_option() Calls

## Problem/Goal

During the EAV migration optimization work (Subtasks 10 & 11), we added migration status checks in multiple locations:
- `custom_posts_where()` - checks migration status for search queries
- `custom_posts_join()` - checks migration status for table joins
- `bulk_fetch_entry_data()` - checks migration status in bulk data fetching
- `super_custom_columns()` - may need migration checks in future

Each location currently calls `get_option('superforms_eav_migration')` independently, which:
- Queries the database repeatedly per request
- Duplicates the same logic across multiple methods
- Makes the code harder to maintain

**Goal:** Create a centralized, cached mechanism for checking EAV migration status that queries the database once per request and provides a clean API for all methods to use.

## Success Criteria

- [ ] Single database query per request - migration status checked once and cached for entire request lifecycle
- [ ] Clean API for checking migration status - simple method like `SUPER_Forms::is_using_eav_storage()` returns boolean
- [ ] All existing checks updated:
  - [ ] `custom_posts_where()` updated (super-forms.php:~1635)
  - [ ] `custom_posts_join()` updated (super-forms.php:~1693)
  - [ ] `bulk_fetch_entry_data()` in SUPER_Data_Access updated (class-data-access.php:~362)
  - [ ] Any other locations identified during implementation
- [ ] No functional changes - system behavior remains identical, backwards compatibility maintained
- [ ] Code passes syntax validation - `php -l` passes on all modified files

## Context Manifest
<!-- Added by context-gathering agent -->

## User Notes
<!-- Any specific notes or requirements from the developer -->

## Work Log
<!-- Updated as work progresses -->
- [2025-11-01] Task created during EAV optimization work (Subtasks 10 & 11)
