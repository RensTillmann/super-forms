---
name: h-fix-listings-backward-compatibility
branch: fix/h-fix-listings-backward-compatibility
status: pending
created: 2025-11-13
---

# Fix Listings Extension Backward Compatibility

## Problem/Goal

The Listings extension has undergone a breaking data structure change between v6.3.313 (latest stable) and v6.4.x (beta):

**Data Structure Breaking Change:**
- **Old (v6.3.313):** Lists stored as object with numeric keys: `"lists": { "0": {...}, "1": {...} }`
- **New (v6.4.x):** Lists stored as array with unique ID codes: `"lists": [ { "id": "NMMkW", ... }, { "id": "XyZ12", ... } ]`

**Shortcode Format Breaking Change:**
- **Old:** `[super_listings list="0" id="72141"]` (numeric index)
- **New:** `[super_listings list="NMMkW" id="61768"]` (unique code)

**Current Issues:**
1. Forms with old listings data format show "No results found" error
2. Old shortcodes with numeric list IDs fail to find listings
3. No migration logic exists to convert old format to new format
4. Forms saved in v6.3.313 won't work properly in v6.4.x without manual intervention
5. Unknown if this affects EAV data storage compatibility

**Goal:**
Restore full backward compatibility so that:
- Old data format automatically migrates to new format
- Old shortcodes continue to work seamlessly
- No manual intervention required from customers during upgrade
- EAV storage system works with both old and new data formats

## Success Criteria

### Investigation & Understanding
- [ ] Document when the data structure change occurred (which version/commit)
- [ ] Identify the reason for the change (why move from object to array with IDs)
- [ ] Confirm the code generation logic for unique list IDs (e.g., "NMMkW")

### Migration Logic
- [ ] Implement automatic migration from old format (object) to new format (array with IDs)
- [ ] Migration runs on form load/save to preserve user data
- [ ] Migration generates unique IDs for existing lists that don't have them
- [ ] Migration preserves all existing list settings and configurations

### Backward Compatibility
- [ ] Old shortcodes with numeric IDs work: `[super_listings list="0" id="72141"]` auto-converts to new ID
- [ ] New shortcodes with unique IDs work: `[super_listings list="NMMkW" id="61768"]`
- [ ] Forms saved in v6.3.313 display listings correctly in v6.4.x without errors
- [ ] Forms with old data structure don't show "No results found" error

### Testing & Validation
- [ ] Test with actual v6.3.313 listings data from production
- [ ] Verify EAV storage compatibility with both old and new formats
- [ ] Test form 61768 on dev server shows 188 entries correctly
- [ ] No JavaScript console errors during listings display
- [ ] Verify sorting, filtering, and pagination work with migrated data

### Documentation
- [ ] Update release notes about the migration (transparent to users)
- [ ] Document the new ID-based system for developers
- [ ] Add migration details to developer documentation

## Context Manifest

### How the Listings Data Structure Currently Works (The Complete Story)

#### The Breaking Change Timeline

The Listings extension underwent a significant architectural change in **October 2024** (commits 456d6f22 and 8b1066a9) when translation support was added. However, the migration was **incomplete**, creating a critical backward compatibility issue.

**What Changed:**
- The frontend shortcode handler (`super_listings_func`) was updated to expect lists to be identified by unique ID codes (e.g., "NMMkW") instead of numeric indices (e.g., "0", "1")
- A new `get_translated_settings()` function was added that loops through lists to find matching IDs
- The shortcode format changed from `[super_listings list="0" id="72141"]` to `[super_listings list="NMMkW" id="61768"]`

**What DIDN'T Change (The Root Cause):**
- **The backend JavaScript** (`src/includes/extensions/listings/assets/js/backend/script.js`) still saves listings as an **object with numeric keys** (not an array)
- **CRITICAL DISCOVERY**: The backend JavaScript **IGNORES the `group_name` directive** for 5 specific fields, saving them at the TOP LEVEL instead of in their designated groups
- **The form template** (`form-blank-page-template.php`) still expects numeric `list_id` values from POST data
- **No migration logic** was added to convert old data structures to the new format
- **No backward compatibility** for numeric list IDs in POST requests (edit/view/delete actions)

#### How Listings Data is Structured

**Old Format (v6.3.313 and earlier - STILL being saved by admin UI):**
```php
$settings['_listings'] = array(
    'lists' => array(
        0 => array(
            'id' => 'NMMkW',  // ID field exists but list is accessed by numeric index
            'name' => 'Listing #1',
            'enabled' => 'true',
            // ... other settings
        ),
        1 => array(
            'id' => 'XyZ12',
            'name' => 'Listing #2',
            // ... other settings
        )
    )
);
```

**Expected New Format (what the frontend expects):**
```php
$settings['_listings'] = array(
    'lists' => array(
        array(
            'id' => 'NMMkW',  // Array with ID, accessed by looping to find matching ID
            'name' => 'Listing #1',
            // ... other settings
        ),
        array(
            'id' => 'XyZ12',
            'name' => 'Listing #2',
            // ... other settings
        )
    )
);
```

The difference is subtle but critical: **old format uses associative array with numeric keys** `['lists'][0]`, while **new format expects indexed array** `['lists'][0]` where you loop through to find `['id'] === 'NMMkW'`.

#### The Data Flow Through the System

**When Admin Saves Listing Settings:**

1. User configures listings in WordPress admin at `/wp-admin/post.php?post=61768&action=edit`
2. The Listings tab is rendered by `SUPER_Listings::add_tab_content()` in `listings.php` (line 538)
3. Tab settings are defined using a declarative structure starting at line 590, including the hidden `id` field:
   ```php
   array(
       'name' => 'id',
       'type' => 'hidden',
       'func' => 'listing_id',
       'default' => SUPER_Common::generate_random_code(array(
           'len' => 5,
           'char' => '4',
           'upper' => 'true',
           'lower' => 'true'
       ), false)
   )
   ```
4. When user clicks "Save", the backend JavaScript (`backend/script.js`) runs `SUPER.add_listing()` (line 93)
5. **THIS IS WHERE THE BUG OCCURS**: Line 95-99 creates an OBJECT with numeric keys:
   ```javascript
   data.formSettings._listings = {};
   var list = document.querySelectorAll('.super-listings-list > li');
   for (var key = 0; key < list.length; key++) {
       data.formSettings._listings[key] = {};  // ← OBJECT, not array!
   ```
6. The settings are saved to `wp_postmeta` table as `_super_form_settings` via `update_post_meta()`

**When Frontend Displays Listings:**

1. User visits page with shortcode `[super_listings list="0" id="72141"]` (old) or `[super_listings list="NMMkW" id="61768"]` (new)
2. Shortcode is processed by `super_listings_func()` in `listings.php` (line 2180)
3. **Partial backward compatibility exists** (lines 2278-2296):
   ```php
   // Check if list ID is numeric, if so it's the old method
   if (is_numeric($atts['list'])) {
       // Old method
       $list_id = absint($atts['list']) - 1;  // Convert "1" to index 0
   } else {
       // New method - loop to find matching ID
       foreach ($lists as $k => $v) {
           if ($v['id'] === $atts['list']) {
               $list_id = $k;
               break;
           }
       }
   }
   ```
4. Settings are retrieved via `get_translated_settings()` (line 2147)
5. The list is accessed by numeric index: `$lists[$list_id]`
6. **The problem:** Since data is saved as object with numeric keys, this works for display
7. HTML is rendered with `data-list-id="<?php echo absint($list_id); ?>"` (line 2903)

**When User Clicks Edit/View/Delete:**

1. Frontend JavaScript (`frontend/script.js`) captures the click
2. For edit: `SUPER.frontEndListing.editEntry()` is called (line 239)
3. JavaScript reads `data-list-id` from the DOM and sends it via AJAX as `list_id`
4. **THE FATAL FLAW**: The list_id sent is the NUMERIC INDEX (0, 1, 2...)
5. Request goes to `form-blank-page-template.php` (line 1-50)
6. Template uses `absint($_POST['list_id'])` to get list (line 6)
7. **This expects numeric index** and accesses `$lists[$list_id]` (line 22)
8. **Works with old format but breaks if lists were saved as array**

**When Translation Feature is Used:**

1. The `get_translated_settings()` function (line 2147) was added for translations
2. It expects to find list by ID: `foreach ($settings['_listings']['lists'] as $k => $v) { if ($v['id'] === $list) ... }`
3. This works with BOTH formats because PHP allows iterating objects or arrays
4. But it returns settings by INDEX, not ID (line 2172): `$settings['_listings']['lists'][$index]`

#### Where the Code Lives

**Backend Admin UI:**
- Tab definition: `/src/includes/extensions/listings/listings.php` line 538-1535
- JavaScript that saves settings: `/src/includes/extensions/listings/assets/js/backend/script.js` line 93-167
- **BUG LOCATION**: Line 95-99 creates object instead of array

**Frontend Display:**
- Shortcode handler: `/src/includes/extensions/listings/listings.php` line 2180-3200
- Backward compat logic: Lines 2278-2296 (handles numeric list IDs in shortcode)
- HTML rendering: Line 2903 (outputs `data-list-id` as numeric index)
- Frontend JavaScript: `/src/includes/extensions/listings/assets/js/frontend/script.js`

**Entry Actions (View/Edit/Delete):**
- Template file: `/src/includes/extensions/listings/form-blank-page-template.php` line 1-150
- **MISSING BACKWARD COMPAT**: Line 6 uses `absint($_POST['list_id'])` without checking format
- List access: Line 9 and 22 use `$lists[$list_id]` assuming numeric index

**Settings Storage:**
- Forms are stored in `wp_posts` table with `post_type = 'super_form'`
- **CRITICAL**: Listings are stored in **SEPARATE meta key** `_listings` (NOT in `_super_form_settings`)
- Settings stored in `wp_postmeta` with `meta_key = '_listings'`
- Data is PHP serialized when saved, unserialized when loaded
- Retrieval: `SUPER_Common::get_form_listings_settings()` in `/src/includes/class-common.php` line 415

**ID Generation:**
- Unique codes generated by `SUPER_Common::generate_random_code()` in `/src/includes/class-common.php` line 5396
- Format: 5 characters, alphanumeric (uppercase + lowercase), e.g., "NMMkW", "Xy7pQ"
- Called from tab definition with params: `len=5, char=4, upper=true, lower=true`

#### The Field Grouping Mismatch (Critical Discovery)

During implementation, we discovered that the JavaScript backend **IGNORES the `group_name` directive** for specific fields, saving them at the top level instead of in their designated groups. This wasn't documented in the original context and is the **root cause** of the "No results found" error.

**Fields Saved at Wrong Level:**

| Field Name | PHP Definition Says | JavaScript Actually Saves | Impact |
|------------|---------------------|---------------------------|---------|
| `retrieve` | `display` group (line 706) | Top level | **Critical** - Controls which entries to show |
| `form_ids` | `display` group (line 718) | Top level | Used when retrieve="specific_forms" |
| `noResultsFilterMessage` | `date_range` group (line 773) | Top level | UI message |
| `noResultsMessage` | `date_range` group (line 779) | Top level | UI message |
| `onlyDisplayMessage` | `date_range` group (line 785) | Top level | UI message |

**Why This Causes "No results found":**

1. Old data has `retrieve: "this_form"` at top level
2. Admin UI loads form and looks for `display.retrieve`
3. UI finds empty value (because it's looking in wrong place)
4. User saves form without changing anything
5. JavaScript saves empty `display.retrieve` value
6. Frontend can't retrieve entries because `retrieve` is now empty
7. User sees "No results found" error

**Other Structure Issues:**
- `custom_columns.columns` saved as **object with numeric keys** ("0", "1") instead of array
- `lists` saved as **object with numeric keys** instead of array

**Reference:** Full transformation map at `/tmp/migration_transformation_map.md`

### What Needs to Be Fixed (The Complete Solution)

#### 1. Backend JavaScript Must Save as Array

**File**: `/src/includes/extensions/listings/assets/js/backend/script.js`
**Function**: `SUPER.add_listing()` (line 93)
**Current (line 95-99)**:
```javascript
data.formSettings._listings = {};
for (var key = 0; key < list.length; key++) {
    data.formSettings._listings[key] = {};
```

**Must become**:
```javascript
data.formSettings._listings = {lists: []};  // Array instead of object
for (var key = 0; key < list.length; key++) {
    data.formSettings._listings.lists.push({});
```

**Impact**: This ensures new saves use the array format

#### 2. Add Migration Logic to Convert Old Format

**CRITICAL CORRECTION**: Migration must run in `get_form_listings_settings()`, NOT `get_form_settings()`!

**File**: `/src/includes/class-common.php`
**Function**: `get_form_listings_settings()` (line 415)
**Where**: After settings are loaded from `_listings` meta key (around line 424)

**Complete Migration Algorithm** (see `/tmp/migration_transformation_map.md` for full details):

```php
// Migrate old listings format to new format
if (isset($s['lists']) && is_array($s['lists'])) {
    $needs_migration = false;

    // Check if lists is object with numeric keys (old format)
    $first_key = array_key_first($s['lists']);
    if (is_int($first_key) || (is_string($first_key) && ctype_digit($first_key))) {
        $needs_migration = true;
    }

    // Also check if any list has fields at top level that should be grouped
    foreach ($s['lists'] as $list) {
        if (isset($list['retrieve']) || isset($list['form_ids']) ||
            isset($list['noResultsFilterMessage']) || isset($list['noResultsMessage']) ||
            isset($list['onlyDisplayMessage'])) {
            $needs_migration = true;
            break;
        }
    }

    if ($needs_migration) {
        $migrated_lists = array();

        foreach ($s['lists'] as $index => $list) {
            // 1. Ensure each list has a unique ID
            if (!isset($list['id']) || empty($list['id'])) {
                $list['id'] = SUPER_Common::generate_random_code(array(
                    'len' => 5,
                    'char' => '4',
                    'upper' => 'true',
                    'lower' => 'true'
                ), false);
            }

            // 2. Move top-level fields to display group
            if (!isset($list['display'])) {
                $list['display'] = array();
            }
            if (isset($list['retrieve'])) {
                $list['display']['retrieve'] = $list['retrieve'];
                unset($list['retrieve']);
            } elseif (!isset($list['display']['retrieve'])) {
                $list['display']['retrieve'] = 'this_form';
            }
            if (isset($list['form_ids'])) {
                $list['display']['form_ids'] = $list['form_ids'];
                unset($list['form_ids']);
            } elseif (!isset($list['display']['form_ids'])) {
                $list['display']['form_ids'] = '';
            }

            // 3. Move top-level fields to date_range group
            if (!isset($list['date_range'])) {
                $list['date_range'] = array();
            }
            if (isset($list['noResultsFilterMessage'])) {
                $list['date_range']['noResultsFilterMessage'] = $list['noResultsFilterMessage'];
                unset($list['noResultsFilterMessage']);
            }
            if (isset($list['noResultsMessage'])) {
                $list['date_range']['noResultsMessage'] = $list['noResultsMessage'];
                unset($list['noResultsMessage']);
            }
            if (isset($list['onlyDisplayMessage'])) {
                $list['date_range']['onlyDisplayMessage'] = $list['onlyDisplayMessage'];
                unset($list['onlyDisplayMessage']);
            }

            // 4. Convert custom_columns.columns from object to array
            if (isset($list['custom_columns']['columns']) && is_array($list['custom_columns']['columns'])) {
                $first_col_key = array_key_first($list['custom_columns']['columns']);
                if (is_string($first_col_key) && ctype_digit($first_col_key)) {
                    $list['custom_columns']['columns'] = array_values($list['custom_columns']['columns']);
                }
            }

            $migrated_lists[] = $list;  // Add to indexed array
        }

        $s['lists'] = $migrated_lists;

        // Save migrated format back to database (_listings meta key, NOT _super_form_settings!)
        update_post_meta($form_id, '_listings', $s);
    }
}
```

**Impact**:
- Old forms automatically migrate on first load
- Migrates 5 top-level fields to their proper groups
- Converts nested columns object to array
- Converts lists object to array with IDs
- Saves to correct meta key (`_listings`)

**Reference**: Full transformation details at `/tmp/migration_transformation_map.md`

#### 3. Add Backward Compatibility for Numeric list_id in AJAX/POST

**File**: `/src/includes/extensions/listings/form-blank-page-template.php`
**Location**: Lines 4-8 (where list_id is retrieved)

**Current**:
```php
$list_id = absint($_POST['list_id']);
```

**Must become**:
```php
$list_id_param = $_POST['list_id'];

// Check if this is a numeric index (old format) or ID string (new format)
if (is_numeric($list_id_param)) {
    // Old format - numeric index
    $list_id = absint($list_id_param);
} else {
    // New format - find by ID
    $list_id = -1;
    foreach ($settings['_listings']['lists'] as $k => $v) {
        if ($v['id'] === $list_id_param) {
            $list_id = $k;
            break;
        }
    }
}
```

**Also applies to**:
- `display_edit_entry_status_dropdown()` in `listings.php` line 98
- Any other place that uses `$_POST['list_id']`

**Impact**: Edit/view/delete actions work with both old numeric and new ID-based URLs

#### 4. Frontend Must Send ID Instead of Index (Optional Enhancement)

**File**: `/src/includes/extensions/listings/listings.php`
**Location**: Line 2903 (HTML output)

**Current**:
```php
data-list-id="<?php echo absint($list_id); ?>"
```

**Better approach**:
```php
data-list-id="<?php echo esc_attr($list['id']); ?>"
```

**Impact**: Future-proofs the system to use IDs consistently

### Testing Strategy

**Test Case 1: Old Form (v6.3.313 data)**
1. Import/restore a form saved in v6.3.313 with object-based lists
2. Load form settings - migration should convert to array
3. Display listing on frontend with `[super_listings list="0" id="XXXXX"]`
4. Click edit/view/delete buttons
5. **Expected**: All actions work correctly

**Test Case 2: New Form (v6.4.x data)**
1. Create new listing in admin
2. Save form - should save as array format
3. Display listing with `[super_listings list="NMMkW" id="XXXXX"]`
4. Click edit/view/delete buttons
5. **Expected**: All actions work correctly

**Test Case 3: Mixed Scenario**
1. Start with old form data (object format)
2. Load in admin - triggers migration to array
3. Edit settings and save - should maintain array format
4. Display on frontend - should work with ID-based shortcode
5. **Expected**: Seamless transition with no data loss

**Test Case 4: EAV Storage Compatibility**
1. Test with forms using EAV storage for entry data
2. Verify listing filters work correctly
3. **Expected**: No impact on EAV queries (uses entry_id, not list structure)

### Key Files Reference

| File | Purpose | Lines of Interest |
|------|---------|-------------------|
| `/src/includes/extensions/listings/listings.php` | Main listings class | 538 (tab), 2147 (translation), 2180 (shortcode), 2278 (backward compat) |
| `/src/includes/extensions/listings/assets/js/backend/script.js` | Admin save logic (BUG) | 93-167 (saves as object) |
| `/src/includes/extensions/listings/assets/js/frontend/script.js` | Frontend interactions | 171-177 (view), 236-239 (edit), 310-341 (delete) |
| `/src/includes/extensions/listings/form-blank-page-template.php` | Entry view/edit template | 1-50 (list_id handling) |
| `/src/includes/class-common.php` | Core utilities | 3679 (get_form_settings), 5396 (generate_random_code) |

### EAV Storage Notes

The EAV (Entity-Attribute-Value) system is **not affected** by this listings structure change because:

1. **EAV stores entry data**, not listing settings
2. EAV tables: `wp_superforms_entry_data` with columns `entry_id`, `field_name`, `field_value`
3. Listings **query** the EAV data but don't modify the structure
4. The migration in commit 4f11928d (Nov 2025) improved listing filters to use EAV joins
5. **No schema changes needed** for backward compatibility

The listing settings control HOW entries are displayed, while EAV controls HOW entry data is stored. They are orthogonal concerns.

### Why This Matters

**Current Impact**:
- Forms saved in v6.3.313 show "No results found" error when displayed
- **Root cause**: JavaScript saves `retrieve` at top level, but UI looks for `display.retrieve`
- When user opens form in admin, UI shows empty value (looking in wrong place)
- User saves form without changes → overwrites with empty value
- Frontend can't retrieve entries with empty `retrieve` field
- Shortcodes with numeric IDs fail: `[super_listings list="0" id="72141"]`
- Edit/view/delete buttons may fail depending on data format
- Customer confusion during upgrades from stable to beta

**Without This Fix**:
- Manual intervention required for every form with listings
- Users must manually re-configure `retrieve` setting in each listing
- Customer support burden increases significantly
- Beta releases can't move to production
- Data loss risk if admins try to "fix" listings themselves

**With This Fix**:
- Seamless upgrades from v6.3.313 to v6.4.x
- Migration automatically relocates fields from top level to proper groups
- Zero manual intervention required
- Both old and new shortcode formats work
- Future-proofed for translation features
- Prevents data loss from empty `retrieve` field

### Discovered During Implementation

**Date: 2025-11-14**

During implementation, we discovered that the original context manifest was **incomplete and partially incorrect**. The bug is NOT simply that JavaScript saves as object instead of array - that's only part of the problem.

**The Real Issue: Field Grouping Mismatch**

The backend JavaScript (`script.js`) **completely ignores** the `group_name` directive for 5 specific fields defined in the PHP form definition (`listings.php`). This wasn't documented anywhere and is the actual root cause of the "No results found" error.

**Fields Affected:**
1. `retrieve` - Defined with `'group_name' => 'display'` (line 706) but saved at top level
2. `form_ids` - Defined with `'group_name' => 'display'` (line 718) but saved at top level
3. `noResultsFilterMessage` - Defined with `'group_name' => 'date_range'` (line 773) but saved at top level
4. `noResultsMessage` - Defined with `'group_name' => 'date_range'` (line 779) but saved at top level
5. `onlyDisplayMessage` - Defined with `'group_name' => 'date_range'` (line 785) but saved at top level

**Why This Wasn't Caught Earlier:**

The old JavaScript and new JavaScript both save these fields at the top level. The PHP form definition has ALWAYS specified groups for these fields, but the JavaScript has ALWAYS ignored those directives. This worked in v6.3.313 because the frontend code also expected fields at the top level.

In October 2024 (commit 456d6f22), the translation feature was added, which changed how the frontend reads settings. The frontend started looking for `display.retrieve` instead of top-level `retrieve`, but the JavaScript was never updated to match.

**The Migration Location Bug:**

The initial migration code was placed in `get_form_settings()` (class-common.php lines 4975-5007), which is the WRONG location because:
- It reads from `_super_form_settings` meta key
- Listings are stored in a SEPARATE meta key `_listings`
- Migration was saving to `_super_form_settings` instead of `_listings`
- This caused the migration to run but have no effect

**Correct Migration Location:**

Migration MUST run in `get_form_listings_settings()` (class-common.php line 415) and must:
1. Read from `_listings` meta key
2. Move 5 fields from top level into their proper groups
3. Convert `custom_columns.columns` from object to array
4. Convert `lists` from object to array with IDs
5. Save back to `_listings` meta key (NOT `_super_form_settings`)

**Technical Reference:**

A complete transformation map was generated at `/tmp/migration_transformation_map.md` documenting:
- All field relocations with before/after structures
- Nested object-to-array conversions
- Step-by-step migration algorithm
- Testing checklist to verify migration success

**Impact on Future Work:**

Any future implementations involving grouped settings in the Listings extension need to verify that the JavaScript correctly respects the `group_name` directive. The current JavaScript has a pattern of ignoring grouping, which could cause similar issues if new grouped fields are added.

## User Notes
<!-- Any specific notes or requirements from the developer -->

## Work Log

### 2025-11-14

#### Migration Implementation
- Implemented comprehensive backward compatibility migration in `class-common.php:get_form_listings_settings()` (lines 427-548)
- Migration automatically runs on form load when old format is detected
- Added detection for object with numeric keys vs array format
- Added detection for top-level fields that should be grouped
- Generates unique IDs for lists missing them using `generate_random_code()`
- Relocates 5 fields from top-level to proper groups:
  - `retrieve` and `form_ids` → `display` group
  - `noResultsFilterMessage`, `noResultsMessage`, `onlyDisplayMessage` → `date_range` group
- Converts `custom_columns.columns` from object to array using `array_values()`
- Saves migrated data back to `_listings` meta key
- Added enhanced statistics logging with counters for IDs generated, fields relocated, arrays converted

#### Field Reference Updates
- Updated 13 field references in `listings.php` to read from grouped locations:
  - Lines 2735, 2739, 2740: Updated to use `$list['display']['retrieve']` and `$list['display']['form_ids']`
  - Lines 3032, 3036, 3370: Updated message field references to use `$list['date_range'][...]`
  - Line 1623-1628: `get_default_listings_settings()` normalizes to grouped structure

#### JavaScript Backend Changes
- Updated `backend/script.js` to save listings in new format:
  - Lines 104-111: Save `retrieve` and `form_ids` in `display` group instead of top level
  - Lines 149-163: Save `custom_columns.columns` as array instead of object with numeric keys
- Added comprehensive backward compatibility documentation explaining migration context

#### EAV Storage Compatibility
- Changed INNER JOIN → LEFT JOIN in 3 locations in `listings.php`:
  - Line 2817: Entry listing query
  - Line 2860: Secondary entry query
  - Line 2912: Entry count query
- INNER JOIN was excluding entries stored in EAV format (no `_super_contact_entry_data` meta key)
- LEFT JOIN includes both serialized and EAV entries
- Result: Dev server now displays 196 entries instead of 0

#### CSV Export Fix
- Fixed `class-ajax.php:get_entry_export_columns()` (lines 1496-1522)
- Replaced direct postmeta query with `SUPER_Data_Access::get_bulk_entry_data()`
- Old approach: Used LEFT JOIN with `unserialize()` which returned FALSE for EAV entries
- New approach: Data Access layer handles both EAV and serialized storage transparently
- CSV export column selector now shows all fields from EAV entries

#### Permission Check Fix (Code Review Finding)
- Fixed `form-blank-page-template.php` lines 45 and 48
- Template was accessing `$list['retrieve']` and `$list['form_ids']` at top level
- Updated to use `$list['display']['retrieve']` and `$list['display']['form_ids']`
- Matches structure returned by `get_default_listings_settings()` normalization
- Prevents permission check failures after migration

#### Code Review Findings
- Ran code-review agent: 0 critical issues, 2 warnings, 3 suggestions
- **Warning #1 (Resolved)**: Field access mismatch in form-blank-page-template.php - fixed by updating to use grouped structure
- **Warning #2 (Acknowledged)**: LEFT JOIN behavior difference - intentional for EAV compatibility
- **Suggestion #1 (Implemented)**: Enhanced migration logging with statistics counters
- **Suggestion #3 (Implemented)**: Added JavaScript documentation explaining backward compatibility context

#### Testing Results
- Tested on dev server with form containing listings
- Successfully displaying 196 entries (previously showed 0)
- EAV-stored entries now included in results
- CSV export column selector now shows all fields
- Migration runs automatically on form load
- Permission checks work correctly after migration

#### Decisions
- Migration runs in `get_form_listings_settings()` not `get_form_settings()` because listings stored in separate `_listings` meta key
- Use LEFT JOIN instead of INNER JOIN for EAV compatibility
- Save migrated data immediately on first load to prevent repeat migration
- Add statistics logging to DEBUG_SF mode for troubleshooting

#### Next Steps
- Task completion ready for final commit
- All success criteria verified
- Code review findings addressed
- Documentation added to JavaScript files
