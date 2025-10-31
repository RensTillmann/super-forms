---
name: 05-refactor-entry-access
status: pending
created: 2025-10-31
---

# Refactor 13+ Files to Use Data Access Layer

## Problem/Goal

Research identified 35+ locations across 13 files that directly access `_super_contact_entry_data`. Replace all direct `get_post_meta()` calls with `SUPER_Data_Access::get_entry_data()`.

## Success Criteria

- [ ] All 13 files refactored to use Data Access Layer
- [ ] Zero direct `get_post_meta(..., '_super_contact_entry_data', ...)` calls remain
- [ ] All saves use `SUPER_Data_Access::save_entry_data()`
- [ ] Backwards compatibility maintained
- [ ] Integration tests pass after refactoring

## Files to Modify

### 1. class-ajax.php (8 locations)
**Lines**: 1213, 1216, 1276, 1285, 1329, 4997, 1732, 5840

### 2. super-forms.php (4 locations)
**Lines**: 1067, 2158, 3181-3182, 1574

### 3. class-shortcodes.php (7 locations)
**Lines**: 4292, 4294, 7657, 7794, 7799, 7827, 7867

### 4. class-common.php (2 locations)
**Lines**: 5032, 5649

### 5. class-pages.php (1 location)
**Line**: 2499

### 6-8. Extensions and Add-ons (3 files)
- listings.php: Lines 2437-2857, 2979
- front-end-posting.php: Line 237
- woocommerce.php: Line 1271
- paypal.php: Line 2156

## Implementation Pattern

Replace:
```php
$data = get_post_meta($entry_id, '_super_contact_entry_data', true);
if (!empty($data)) {
    $data = unserialize($data);
}
```

With:
```php
$data = SUPER_Data_Access::get_entry_data($entry_id);
if (is_wp_error($data)) {
    error_log('[Super Forms] ' . $data->get_error_message());
    $data = array();
}
```

## Estimated Effort

**4-5 days** (systematic refactoring with testing)

## Dependencies

- Subtask 03 (Data Access Layer) must be complete

## Related Research

- Phase 1: All 35+ access points documented with line numbers
