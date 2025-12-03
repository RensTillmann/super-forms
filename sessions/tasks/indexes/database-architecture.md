---
index: database-architecture
name: Database & Architecture
description: Tasks related to database structure, data storage, performance optimization, and architectural changes
---

# Database & Architecture

## Active Tasks

### High Priority
- `h-research-eav-migration-complete.md` - Comprehensive research for migrating contact entry storage from serialized format to EAV table structure for 10-20x performance improvement
- `h-implement-eav-contact-entry-storage/` - Implement EAV storage migration with backwards compatibility, 30-60x performance improvement, and zero-downtime migration strategy
- `h-implement-developer-tools-page.md` - Developer tools page for EAV migration testing with automated verification, performance benchmarks, and test data generation
- `h-test-migration-and-devtools-page.md` - Collaborative testing and validation of migration system and Developer Tools page functionality, data integrity, and performance
- `h-test-eav-migration-real-data.md` - Test EAV migration with real production data using CSV/XML imports via Developer Tools page
- `h-implement-automatic-background-migration/` - Automatic frictionless background migration using Action Scheduler with per-entry verification, self-healing, and zero user intervention
- `h-implement-cron-fallback-system.md` - WP-Cron fallback system to ensure background jobs process even when WP-Cron fails (disabled, low-traffic, server issues), with auto-detection, async processing, and simple admin UI
- `h-implement-triggers-actions-extensibility/` - Refactor triggers/actions with custom tables (`wp_super_triggers`, `wp_super_trigger_actions`), Action Scheduler integration, and registry pattern for add-on extensibility
- `h-refactor-form-storage-cleanup.md` - Remove legacy post-based form storage (`super_form` post type) and migrate completely to custom table system (`wp_superforms_forms`) with REST API, DAL, and JSON Patch operations

### Medium Priority
- `m-refactor-eav-migration-status-cache.md` - Cache EAV migration status to eliminate redundant get_option() calls across multiple methods

### Low Priority

### Investigate

## Completed Tasks
<!-- Move tasks here when completed, maintaining the format -->
- `h-fix-eav-migration-bugs-and-simplify.md` - Fix 2 critical bugs (missing form_id, default parameter bypass), add race condition protection, transaction support, and simplify architecture
