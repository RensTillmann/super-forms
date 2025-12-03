---
name: h-refactor-form-storage-cleanup
branch: feature/h-refactor-form-storage-cleanup
status: pending
created: 2025-12-03
---

# Form Storage System Cleanup - Remove Legacy Post-Based System

## Problem/Goal

Super Forms currently has **two parallel form storage systems** running simultaneously:
- **OLD**: WordPress post type (`super_form`) with post meta (`_super_form_settings`)
- **NEW**: Custom table (`wp_superforms_forms`) with clean DAL and REST API

The new system is 95% complete but the old system is still active throughout the codebase, causing:
- Code duplication and maintenance burden
- Confusion about which system is authoritative
- Performance overhead from dual storage
- Risk of data inconsistency between systems

**Goal**: Make the new DAL system authoritative and completely remove the obsolete post-based form storage system.

## Success Criteria

### Pre-Flight Validation
- [ ] REST controller verified as registered on `rest_api_init` hook
- [ ] Form migration scheduled on plugin activation/upgrade
- [ ] Migration status check utility functions exist and work
- [ ] Data integrity validation script created and passes

### System Completion
- [ ] `SUPER_Form_DAL` has `duplicate()`, `search()`, `archive()`, `restore()` methods
- [ ] REST controller has import/export endpoints
- [ ] REST controller has bulk operations endpoint
- [ ] All missing DAL features implemented

### Dependencies Updated
- [ ] WooCommerce add-on uses `SUPER_Form_DAL` instead of post queries
- [ ] PayPal add-on uses `SUPER_Form_DAL` instead of post queries
- [ ] Stripe extension uses `SUPER_Form_DAL` instead of post queries
- [ ] Listings extension uses `SUPER_Form_DAL` instead of post queries

### Core System Updated
- [ ] Admin form list queries `wp_superforms_forms` via DAL
- [ ] `page-create-form.php` loads from DAL instead of post
- [ ] `duplicate_form()` uses `DAL::duplicate()`
- [ ] `super_form_func()` shortcode uses `SUPER_Form_DAL::get()`
- [ ] AJAX handlers replaced with REST API calls
- [ ] JavaScript updated to call REST endpoints

### Testing Complete
- [ ] PHPUnit test suite for `SUPER_Form_DAL` (CRUD, versions, operations)
- [ ] PHPUnit tests for `SUPER_Form_Operations` (JSON Patch)
- [ ] REST API integration tests for all endpoints
- [ ] Full migration verified with 100% data integrity
- [ ] Form workflows tested end-to-end (create, edit, duplicate, delete, restore)
- [ ] All add-ons tested with new DAL

### Legacy Code Removed
- [ ] `super_form` post type registration removed
- [ ] `duplicate_form_post_meta()` and related methods removed
- [ ] Form admin columns filter hooks removed
- [ ] Deprecated AJAX handlers removed (save_form, delete_form, import, export)
- [ ] Post meta read/write code removed from `class-common.php`
- [ ] Backup/restore post-based version system removed
- [ ] "Your Forms" menu item removed
- [ ] All `super_form` post_type references removed
- [ ] All `_super_form_settings` meta key references removed
- [ ] Test fixtures updated to use new system
- [ ] Migration-only code removed

### Post-Cleanup Verification
- [ ] Full test suite runs with zero errors
- [ ] Performance improvements verified (query count, load time)
- [ ] Documentation updated in CLAUDE.md

## Context Manifest

### How Form Storage Currently Works: Dual System Overview

Super Forms currently operates with **two parallel form storage systems** that were never meant to coexist long-term. Understanding this dual architecture is critical because the cleanup involves surgically removing the legacy system while ensuring the new system has complete feature parity.

**The Legacy Post-Based System (To Be Removed):**

When a user creates or edits a form in the WordPress admin, the system registers a custom post type `super_form` via `SUPER_Post_Types::register_post_types()` in `/src/includes/class-post-types.php` (lines 39-81). This post type configuration shows it's not meant for front-end display (`public => false`, `query_var => false`) and is purely for admin use with `show_ui => true`.

Form data is stored across multiple post meta keys on each `super_form` post:
- `_super_form_settings` - Form configuration (cached globally, autosave settings, theme, etc.)
- `_super_elements` - JSON-encoded array of form builder elements (fields, columns, steps)
- `_super_translations` - Multi-language translation data
- `_super_version` - Plugin version that created/last saved the form
- `_super_local_secrets` - Form-specific API keys and credentials

The CRUD operations for this legacy system are split across multiple files:

1. **Creating/Updating Forms** (`/src/includes/class-ajax.php`, lines 2475-2635):
   - AJAX handler `save_form()` receives form data from JavaScript
   - For new forms: calls `wp_insert_post()` with `post_type => 'super_form'` (line 2544)
   - Then calls `save_form_meta()` which uses `add_post_meta()` for each meta key
   - For existing forms: calls `wp_update_post()` and `update_post_meta()`
   - Also handles import via `super_import_single_form` action
   - Saves add-on specific settings via `SUPER_Common::save_form_*_settings()` methods

2. **Reading Forms** (`/src/includes/class-common.php`, line 3852):
   - `SUPER_Common::get_form_settings($form_id)` uses `get_post_meta($form_id, '_super_form_settings', true)`
   - Has recursion guards to prevent infinite loops during migration (lines 3854-3861)
   - Falls back to global settings if form-specific settings don't exist
   - Merges with default settings from `SUPER_Settings::get_defaults()`

3. **Duplicating Forms** (`/src/super-forms.php`, lines 3985-4118):
   - `duplicate_form_action()` triggered by admin action link
   - Uses direct `$wpdb->insert()` to clone the post record (faster than wp_insert_post)
   - `duplicate_form_post_meta()` performs bulk meta copy with single SQL query
   - Special handling for Google Sheets credentials (needs re-slashing)
   - Calls separate methods for each add-on's settings duplication

4. **Deleting Forms** (`/src/includes/class-ajax.php`, lines 2748-2770):
   - AJAX handler `delete_form()`
   - First queries for backup forms (post_parent relationship, post_status='backup')
   - Deletes all backups with `wp_delete_post($id, true)` (true = force delete, skip trash)
   - Then deletes the main form

5. **Listing Forms** (`/src/includes/admin/views/page-create-form.php`, line 11):
   - Admin UI loads forms via `$forms = get_posts(array('post_type' => 'super_form'))`
   - JavaScript dropdown shows all forms for switching (lines 58-73)
   - Form title editable inline, saves via AJAX

**The New Custom Table System (Authoritative After Cleanup):**

The new system was introduced in version 6.6.0 with a clean separation of concerns and modern architecture patterns.

**Database Tables** (created in `/src/includes/class-install.php`, lines 317-349):

1. `wp_superforms_forms` - Main table structure:
   - `id` - BIGINT primary key (can preserve original post IDs during migration)
   - `name` - VARCHAR(255) form title
   - `status` - VARCHAR(20) publish/draft/trash/archived
   - `elements` - LONGTEXT JSON (form builder structure)
   - `settings` - LONGTEXT JSON (all configuration)
   - `translations` - LONGTEXT JSON (i18n data)
   - `created_at` - DATETIME
   - `updated_at` - DATETIME (automatically updated on changes)
   - Indexed on: status, name(191) for fast queries

2. `wp_superforms_form_versions` - Git-like version control:
   - `id` - Version ID
   - `form_id` - Foreign key to forms table
   - `version_number` - Auto-incrementing per form (1, 2, 3...)
   - `snapshot` - LONGTEXT JSON of complete form state
   - `operations` - JSON array of operations since last version (for diff view)
   - `created_by` - User ID who created version
   - `created_at` - Timestamp
   - `message` - VARCHAR(500) commit message
   - Auto-cleanup keeps only latest 20 versions per form

**Data Access Layer** (`/src/includes/class-form-dal.php`):

The DAL provides all CRUD operations with consistent error handling:

- `SUPER_Form_DAL::get($form_id)` (lines 40-56):
  - Returns single form as object with decoded JSON columns
  - Returns null if not found (not WP_Error, not false - predictable)

- `SUPER_Form_DAL::create($data)` (lines 64-106):
  - Accepts array with name, status, elements, settings, translations
  - Auto-sets created_at and updated_at timestamps
  - Returns new form ID or WP_Error on failure
  - Uses parameterized queries (no SQL injection risk)

- `SUPER_Form_DAL::update($form_id, $data)` (lines 115-164):
  - Partial updates supported (only pass changed fields)
  - Auto-updates updated_at timestamp
  - Returns true on success, WP_Error on failure

- `SUPER_Form_DAL::delete($form_id)` (lines 172-183):
  - Hard delete (no trash state in custom table)
  - Returns true/WP_Error

- `SUPER_Form_DAL::query($args)` (lines 191-227):
  - Supports status filtering, pagination, ordering
  - Returns array of form objects with decoded JSON
  - Default: 20 forms, ordered by ID DESC

- **Version Management** (lines 253-518):
  - `create_version()` - Snapshot current state with optional message
  - `get_versions()` - List versions with limit
  - `get_version()` / `get_version_by_number()` - Retrieve specific version
  - `revert_to_version()` - Restore form to previous state
  - `cleanup_old_versions()` - Auto-prune keeps 20 latest
  - Versions created manually or auto on major changes

- **Operations Support** (lines 441-482):
  - `apply_operations($form_id, $operations)` - JSON Patch (RFC 6902)
  - Validates operations before applying
  - Enables AI/LLM integration, undo/redo, minimal payloads
  - Operations handled by `SUPER_Form_Operations` class

**Operations Handler** (`/src/includes/class-form-operations.php`):

Implements JSON Patch standard for atomic form updates:
- Supported ops: add, remove, replace, move, copy, test
- Path notation: `/elements/0/label` (JSON Pointer)
- `apply_operation()` and `apply_operations()` for single/batch
- `get_inverse_operation()` enables undo functionality
- `validate_operation()` / `validate_operations()` for safety
- Example: `{op: 'replace', path: '/settings/theme', value: 'style-2'}`
- Enables 2KB payloads instead of 200KB full form saves

**REST API Controller** (`/src/includes/class-form-rest-controller.php`):

Registered on `rest_api_init` hook in `/src/super-forms.php` line 389 via `register_forms_rest_routes()` method.

Endpoints under namespace `super-forms/v1`:

- `GET /forms` - List forms with pagination (lines 216-228)
  - Params: status, number, offset, orderby, order
  - Returns array of form objects

- `POST /forms` - Create new form (lines 253-270)
  - Body: {name, status?, elements?, settings?, translations?}
  - Returns: {id, form}

- `GET /forms/{id}` - Get single form (lines 236-245)
  - Returns form object or 404 WP_Error

- `PUT /forms/{id}` - Update form (lines 278-297)
  - Full replacement of specified fields
  - Returns updated form object

- `DELETE /forms/{id}` - Delete form (lines 305-326)
  - Hard delete, returns {deleted: true, id}

- `POST /forms/{id}/operations` - Apply JSON Patch (lines 334-361)
  - Body: {operations: [{op, path, value}]}
  - Returns: {success, operations: count, updated_form}
  - This is THE endpoint for form builder saves (99% smaller payload)

- `GET /forms/{id}/versions` - List versions (lines 369-382)
  - Param: limit (default 20, max 100)

- `POST /forms/{id}/versions` - Create version snapshot (lines 390-428)
  - Body: {message?, operations?}
  - Returns: {success, version_id, version}

- `POST /forms/{id}/revert/{version_id}` - Revert to version (lines 436-461)
  - Creates new version marking the revert
  - Returns: {success, reverted_to, updated_form}

Permission callback: `current_user_can('manage_options')` for all endpoints (line 167)

**Background Migration System** (`/src/includes/class-form-background-migration.php`):

Migration runs automatically via Action Scheduler when needed:

- Scheduled in `class-install.php` line 73 via `schedule_if_needed()`
- Checks for unmigrated forms: `post_type = 'super_form' AND meta_key '_super_migrated_to_table' IS NULL`
- Processes in batches of 25 forms (filterable via `super_form_migration_batch_size`)
- Uses transient lock to prevent concurrent batches
- Migration state stored in `superforms_form_migration_state` option:
  - status: not_started, in_progress, completed
  - total_to_migrate, migrated_count, failed_forms array
  - last_processed_id for resumable processing

- `migrate_form()` logic (lines 112-149):
  - Reads post meta `_super_form_settings`, `_super_elements`, `_super_translations`
  - Elements and translations extracted from settings if present
  - Attempts to preserve original post ID in custom table
  - If ID exists, updates instead of creates (handles re-runs)
  - Sets `_super_migrated_to_table` meta on success
  - Returns WP_Error on failure (logged to failed_forms array)

- Status utilities added (lines 219-254):
  - `get_migration_status()` - Returns computed progress percentage
  - `is_migration_complete()` - Boolean check
  - Used for admin notices and developer tools

**Missing DAL Features (Must Add Before Cleanup):**

The task success criteria (lines 33-37) lists missing methods:
1. `duplicate($form_id)` - Clone form with "(Copy)" suffix
2. `search($query)` - Text search in name/settings
3. `archive($form_id)` - Soft delete (status='archived')
4. `restore($form_id)` - Unarchive (status='publish')

REST controller also needs (lines 35-37):
1. Import/export endpoints (JSON format)
2. Bulk operations endpoint (delete multiple, change status, etc.)

### Integration Points: Where Old System Is Currently Used

**1. Admin Menu** (`/src/includes/class-menu.php`, lines 39-45):

The "Your Forms" menu item links directly to the post type list:
```php
add_submenu_page(
    'super_forms',
    esc_html__( 'Your Forms', 'super-forms' ),
    esc_html__( 'Your Forms', 'super-forms' ),
    'manage_options',
    'edit.php?post_type=super_form'  // <-- Links to WordPress post list
);
```

This shows the standard WordPress post table with columns, bulk actions, search. After cleanup, this needs to either:
- Be removed entirely (just use Create Form page)
- Or redirect to a custom admin page using DAL queries

**2. Form Builder UI** (`/src/includes/admin/views/page-create-form.php`):

Current implementation (lines 11, 66-70):
- Loads forms via `$forms = get_posts(array('post_type' => 'super_form'))`
- Renders dropdown with post titles: `$value->post_title`
- Links use post ID: `admin.php?page=super_create_form&id=$value->ID`

After cleanup needs to:
- Use `SUPER_Form_DAL::query()` instead
- Access `$form->name` instead of `$form->post_title`
- Post timestamps replaced with `$form->created_at`, `$form->updated_at`

**3. JavaScript Form Operations** (`/src/assets/js/backend/create-form.js`):

Current AJAX calls (referenced in save_form/delete_form handlers):
- Save: `action: 'super_save_form'` → Calls `SUPER_Ajax::save_form()`
- Delete: `action: 'super_delete_form'` → Calls `SUPER_Ajax::delete_form()`
- Sends entire form data as JSON (can be 200KB+)

After cleanup needs to:
- Call REST API endpoints via `fetch()` or `wp.apiFetch()`
- Use operations endpoint: `POST /super-forms/v1/forms/{id}/operations`
- Send only changed operations (2KB typical payload)
- Handle REST error responses (different format than AJAX)

**4. Shortcode Handler** (`/src/includes/class-shortcodes.php`, line 7575):

The `[super_form id="123"]` shortcode is rendered by `SUPER_Shortcodes::super_form_func($atts)`.

Current flow:
- Receives form ID from shortcode attribute
- Calls `get_post_meta($id, '_super_elements', true)` to load elements
- Calls `SUPER_Common::get_form_settings($id)` for settings
- Builds HTML form output

After cleanup needs to:
- Call `SUPER_Form_DAL::get($id)` instead
- Access `$form->elements`, `$form->settings`, `$form->translations`
- Keep backward compatibility (shortcode syntax unchanged)

**5. Form Duplication** (`/src/super-forms.php`, lines 3985-4118):

Triggered by admin action link with nonce verification:
- `duplicate_form_action()` checks nonce and post ID
- `duplicate_form()` uses direct `$wpdb->insert()` for speed
- `duplicate_form_post_meta()` bulk copies all meta with single query
- Special handling for each add-on (WooCommerce, Listings, PDF, Stripe)

After cleanup needs to:
- Call `SUPER_Form_DAL::duplicate($id)` (new method)
- DAL method should handle all add-on settings internally
- Return new form ID for redirect
- Update admin action hook to call new method

**6. Add-On Integration - PayPal** (`/src/add-ons/super-forms-paypal/super-forms-paypal.php`, lines 386-402):

The PayPal transaction admin page has a form filter dropdown:
```php
public static function filter_form_dropdown( $post_type ) {
    if ( $post_type == 'super_paypal_txn' ) {
        $args = array(
            'post_type' => 'super_form',  // <-- Queries post type
            'posts_per_page' => -1,
        );
        $forms = get_posts( $args );
        // Renders <select> with form options
    }
}
```

After cleanup needs to:
- Use `SUPER_Form_DAL::query(array('status' => 'publish', 'number' => -1))`
- Access `$form->name` instead of `$form->post_title`

**7. Add-On Integration - Listings, WooCommerce, Stripe**:

Based on search patterns, similar post type queries exist in:
- Listings extension (moved to `/src/legacy-addons/` based on git status)
- WooCommerce add-on (active)
- Stripe extension (active, but no post_type queries found)

Each needs audit and update to use DAL.

**8. Test Fixtures** (`/tests/fixtures/class-form-factory.php`, lines 569-592):

The test form factory creates forms via:
```php
private static function create_form( $title, $elements, $settings ) {
    $form_id = wp_insert_post( array(
        'post_type'   => 'super_form',
        'post_status' => 'publish',
        'post_title'  => $title,
    ) );

    update_post_meta( $form_id, '_super_elements', wp_json_encode( $elements ) );
    update_post_meta( $form_id, '_super_form_settings', $settings );
    update_post_meta( $form_id, '_super_test_form', true );
}
```

After cleanup needs to:
- Use `SUPER_Form_DAL::create()` instead
- Pass elements/settings as arrays (DAL handles JSON encoding)
- Test cleanup needs `SUPER_Form_DAL::delete()` (line 534)

**9. Entry Creation** (`/tests/fixtures/class-form-factory.php`, line 505):

When creating test entries, it stores form relationship:
```php
update_post_meta( $entry_id, '_super_form_id', $form_id );
```

This meta key is fine - entries are separate concern (not part of this cleanup).

### Critical Architectural Constraints

**Migration Dependency Chain:**

The cleanup CANNOT proceed until:
1. Form migration is 100% complete (`SUPER_Form_Background_Migration::is_migration_complete()` returns true)
2. Migration verification passes (no data loss, all forms readable via DAL)
3. All add-ons tested with new DAL (PayPal, WooCommerce, Stripe, Listings)

The migration preserves post IDs in the custom table (see `class-form-background-migration.php` line 123). This is crucial because:
- Shortcodes reference forms by ID: `[super_form id="123"]`
- Entries reference forms: `_super_form_id` meta
- URLs reference forms: `?page=super_create_form&id=123`
- Add-on settings reference forms by ID

**Backward Compatibility Requirements:**

Even after cleanup, these must continue working:
- Existing shortcodes in pages/posts: `[super_form id="123"]`
- Entry-to-form relationships: Entries still store `_super_form_id` pointing to form ID
- Add-on references: Transactions, orders, bookings linking to form IDs
- Import files: Old export format might reference post meta structure

**Deletion Sequencing:**

Cannot remove in wrong order:
1. First: Update all consumers to use DAL
2. Then: Remove AJAX handlers (save_form, delete_form)
3. Then: Remove post type registration
4. Finally: Remove helper functions (duplicate_form_post_meta, etc.)

If reversed, breaks intermediate states.

**REST vs AJAX Transition:**

JavaScript currently uses:
```javascript
jQuery.post(ajaxurl, {
    action: 'super_save_form',
    form_id: 123,
    form_data: {...}
})
```

Must transition to:
```javascript
wp.apiFetch({
    path: '/super-forms/v1/forms/123/operations',
    method: 'POST',
    data: {operations: [...]}
})
```

Different error handling, different response format, different authentication (REST nonce vs AJAX nonce).

### Error Scenarios to Prevent

**1. Orphaned Data:**
- Deleting post type before migrating all forms → forms become inaccessible
- Deleting AJAX handlers before updating JavaScript → saves fail silently
- Removing meta keys before add-ons updated → settings lost

**2. Duplicate Form Issues:**
- If duplicate_form() removed before DAL has duplicate() method → action link breaks
- Need feature parity check before removing old method

**3. Test Suite Breakage:**
- Test fixtures still creating post type forms → all tests fail after cleanup
- Need to update fixtures first, verify tests pass, then proceed

**4. Import/Export Corruption:**
- Old export format references post meta structure
- New import must handle both old and new formats
- Need migration path for exported forms

**5. Add-On Incompatibility:**
- If PayPal queries post type after removal → dropdown shows "No forms found"
- If WooCommerce references deleted meta → product forms break
- Each add-on needs version check and graceful fallback

### Implementation Strategy

**Phase 1: PRE-FLIGHT (Validation)**
1. Verify `SUPER_Form_REST_Controller` registered on `rest_api_init` hook
2. Check migration complete: `SUPER_Form_Background_Migration::is_migration_complete()`
3. Verify all forms readable: `SUPER_Form_DAL::query()` returns expected count
4. Test round-trip: Create → Read → Update → Delete via DAL
5. Create validation script: Compare post count vs DAL count, check for orphans

**Phase 2: COMPLETE NEW SYSTEM**
1. Add `SUPER_Form_DAL::duplicate($form_id)`
   - Clone form with "(Copy)" suffix
   - Handle all add-on settings
   - Return new form ID
2. Add `SUPER_Form_DAL::search($query)`
   - LIKE search on name and settings JSON
   - Return matching forms array
3. Add `SUPER_Form_DAL::archive($form_id)` and `::restore($form_id)`
   - Update status column
   - Maintain separate from delete
4. Add REST endpoints: `/forms/import`, `/forms/export`, `/forms/bulk`
5. Test all new methods with PHPUnit

**Phase 3: UPDATE DEPENDENCIES**
1. PayPal: Update `filter_form_dropdown()` to use DAL::query()
2. WooCommerce: Find and replace all `get_posts(array('post_type' => 'super_form'))`
3. Stripe: Verify no post type dependencies (likely clean)
4. Listings: Check legacy-addons directory, update or deprecate
5. Test each add-on independently

**Phase 4: UPDATE CORE**
1. Update `page-create-form.php`:
   - Replace `get_posts()` with `SUPER_Form_DAL::query()`
   - Access `$form->name` instead of `$form->post_title`
   - Update timestamps: `$form->updated_at` instead of `get_post_modified_time()`
2. Update `duplicate_form_action()`:
   - Call `SUPER_Form_DAL::duplicate($id)` instead of old method
   - Remove direct SQL queries
3. Update `super_form_func()` shortcode:
   - Replace `get_post_meta()` with `SUPER_Form_DAL::get($id)`
   - Add null check: If form not found, show error message
4. Add deprecation warnings to old AJAX handlers
5. Create REST API JavaScript helpers

**Phase 5: TESTING**
1. Create PHPUnit tests for SUPER_Form_DAL (all methods)
2. Create tests for SUPER_Form_Operations (JSON Patch validation)
3. Create REST API integration tests (all endpoints)
4. Test migration script: Run on fresh install with 100 test forms
5. Test form workflows: Create → Edit → Duplicate → Delete → Restore
6. Test each add-on with new DAL
7. Load test: 1000 forms, measure query performance
8. Verify JavaScript works with REST API

**Phase 6: CLEANUP (Safe Removal)**
1. Remove "Your Forms" menu item (line 39-45 in `class-menu.php`)
2. Remove `super_form` post type registration (`class-post-types.php` lines 39-81)
3. Remove AJAX handlers:
   - `save_form()` and `save_form_meta()` (lines 2475-2704 in `class-ajax.php`)
   - `delete_form()` (lines 2748-2770)
4. Remove duplicate functions:
   - `duplicate_form_action()` (line 3985)
   - `duplicate_form()` (line 4010)
   - `duplicate_form_post_meta()` (line 4066)
   - `get_form_to_duplicate()` (line 4053)
5. Remove post meta readers from `class-common.php`:
   - `get_form_settings()` post meta fallback (keep migration guard, remove eventually)
6. Remove backup/restore post-based system
7. Update test fixtures to use DAL exclusively
8. Remove migration-only code (keep migration class for upgrade path, but mark deprecated)

**Phase 7: POST-CLEANUP VERIFICATION**
1. Run full test suite (should be zero failures)
2. Measure query count reduction (expect 30-50% fewer queries)
3. Test form builder performance (operations-based saves should be sub-second)
4. Update documentation:
   - CLAUDE.md - Remove post type references
   - CLAUDE.php.md - Update data access patterns
   - Add migration guide for developers
5. Create upgrade notice for plugin updates

### File Locations Reference

**New System (Keep and Enhance):**
- `/src/includes/class-form-dal.php` - Data access layer (CRUD + versions)
- `/src/includes/class-form-operations.php` - JSON Patch operations handler
- `/src/includes/class-form-rest-controller.php` - REST API endpoints
- `/src/includes/class-form-background-migration.php` - Migration engine (keep for upgrades)
- Database tables created in `/src/includes/class-install.php` lines 317-349

**Legacy System (Remove After Migration):**
- `/src/includes/class-post-types.php` lines 39-81 - Post type registration
- `/src/includes/class-ajax.php` lines 2475-2770 - Save/delete AJAX handlers
- `/src/super-forms.php` lines 3985-4118 - Duplicate form functions
- `/src/includes/class-common.php` line 3852+ - Post meta readers
- `/src/includes/class-menu.php` lines 39-45 - "Your Forms" menu item

**Integration Points (Update to Use DAL):**
- `/src/includes/admin/views/page-create-form.php` - Admin form builder UI
- `/src/includes/class-shortcodes.php` line 7575 - Shortcode handler
- `/src/assets/js/backend/create-form.js` - JavaScript save/delete
- `/src/add-ons/super-forms-paypal/super-forms-paypal.php` lines 386-402 - Form dropdown
- Add-ons: WooCommerce, Listings, Stripe (search for `post_type.*super_form`)

**Testing (Update Fixtures):**
- `/tests/fixtures/class-form-factory.php` - Test form creation (lines 569-592)
- `/tests/fixtures/class-form-factory.php` - Entry creation (line 505 - keep as-is)

**Configuration:**
- REST API registration: `/src/super-forms.php` line 389 `rest_api_init` hook
- Migration scheduled: `/src/includes/class-install.php` line 73

### Critical Meta Keys to Remove

After all code updated to DAL:
- `_super_form_settings` - Settings now in `wp_superforms_forms.settings` column
- `_super_elements` - Elements now in `wp_superforms_forms.elements` column
- `_super_translations` - Translations now in `wp_superforms_forms.translations` column
- `_super_version` - Version tracking moved to versions table
- `_super_local_secrets` - Move to settings JSON or separate secrets table
- `_super_migrated_to_table` - Migration marker (can remove after cleanup)

Keep these meta keys (not part of forms):
- Entry meta: `_super_form_id`, `_super_contact_entry_data`
- Transaction meta: Various payment gateway meta
- Migration state: `superforms_eav_migration`, `superforms_form_migration_state` options

## User Notes

This cleanup must happen **after** form migration is complete and validated. The work follows strict sequencing:

1. **PRE-FLIGHT** - Validate infrastructure is ready
2. **COMPLETE NEW SYSTEM** - Add missing DAL methods so new system has feature parity
3. **UPDATE DEPENDENCIES** - Migrate add-ons to new system
4. **UPDATE CORE** - Replace core functions to use new system
5. **TESTING** - Comprehensive validation before deletion
6. **CLEANUP** - Safely remove old code in phases
7. **POST-CLEANUP** - Final verification and documentation

**Critical Files Affected (44+ files)**:
- Core: `class-post-types.php`, `super-forms.php`, `class-common.php`, `class-ajax.php`
- Add-ons: WooCommerce, PayPal, Stripe, Listings
- UI: `page-create-form.php`, `create-form.js`
- New System: `class-form-dal.php`, `class-form-rest-controller.php`

## Work Log

- [2025-12-03] Task created with comprehensive 41-step cleanup plan
- [2025-12-03] Added form migration scheduling to `class-install.php:73`
- [2025-12-03] Added migration status utilities to `class-form-background-migration.php:219-254`
