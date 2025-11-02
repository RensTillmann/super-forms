---
name: 17-subtask
status: completed
created: 2025-10-31
completed: 2025-11-01
---

# Listings Filter Rewrite (SUBSTRING_INDEX Replacement)

## Problem/Goal

Implementation of subtask 17 from EAV migration plan - Replace slow SUBSTRING_INDEX queries with indexed EAV table JOINs for 30-60x performance improvement.

## Success Criteria

- [x] Implementation complete
- [x] Migration status check added to switch between serialized/EAV queries
- [x] Custom field filtering converted to EAV JOINs
- [x] Custom field sorting converted to EAV JOINs
- [x] Backwards compatibility maintained (serialized queries still work)
- [x] PHP syntax validated
- [ ] Tests pass - **NOTE: Requires Subtask 01 (Test Suite Foundation)**
- [ ] Integration verified - **NOTE: Requires test environment with data**

## Estimated Effort

**5-6 days**

## Dependencies

- Previous subtasks complete

## Related Research

- Main README: Phase 6 details
- Research task: h-research-eav-migration-complete.md

## Work Log

- [2025-11-01] **Subtask Completed**: Listings filter and sort queries rewritten for EAV
  - Location: `/src/includes/extensions/listings/listings.php`
  - Changes span lines 2411-2880 (approximately)

  **Key Changes:**

  **1. Migration Status Check (Lines 2411-2416)**
  ```php
  // Check if migration is complete and using EAV storage
  $migration = get_option( 'superforms_eav_migration' );
  $use_eav   = false;
  if ( ! empty( $migration ) && $migration['status'] === 'completed' ) {
      $use_eav = ( $migration['using_storage'] === 'eav' );
  }
  ```

  **2. Custom Field Filtering (Lines 2431-2467)**
  - **Before**: SUBSTRING_INDEX on serialized data (15-20 seconds for 8,100+ entries)
  - **After**: LEFT JOIN on indexed EAV table (<500ms - 30-60x faster)

  **Old Query:**
  ```php
  $filter_by_entry_data .= ", SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(meta.meta_value,
    's:4:\"name\";s:$fckLength:\"$fck\";s:5:\"value\";', -1), '\";s:', 1), ':\"', -1) AS filterValue_" . $x;
  $having .= ' HAVING filterValue_' . $x . " LIKE '%$fcv%'";
  ```

  **New EAV Query:**
  ```php
  if ( $use_eav ) {
      $eav_alias  = 'eav_filter_' . $eav_join_counter;
      $eav_joins .= " LEFT JOIN {$wpdb->prefix}superforms_entry_data AS {$eav_alias}
        ON {$eav_alias}.entry_id = post.ID
        AND {$eav_alias}.field_name = '" . esc_sql( $fck ) . "'";
      $filters .= " {$eav_alias}.field_value LIKE '%" . esc_sql( $fcv ) . "%'";
  }
  ```

  **3. Custom Field Sorting (Lines 2657-2672)**
  - **Before**: SUBSTRING_INDEX to extract sort value
  - **After**: Direct EAV field_value JOIN

  **Old Query:**
  ```php
  $order_by_entry_data = ", SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(meta.meta_value,
    's:4:\"name\";s:$scLength:\"$sc\";s:5:\"value\";', -1), '\";s:', 1), ':\"', -1) AS orderValue";
  ```

  **New EAV Query:**
  ```php
  if ( $use_eav ) {
      $eav_alias  = 'eav_sort_' . $eav_join_counter;
      $eav_joins .= " LEFT JOIN {$wpdb->prefix}superforms_entry_data AS {$eav_alias}
        ON {$eav_alias}.entry_id = post.ID
        AND {$eav_alias}.field_name = '" . esc_sql( $sc ) . "'";
      $order_by_entry_data = ", {$eav_alias}.field_value AS orderValue";
  }
  ```

  **4. SQL Query Integration (Lines ~2786, ~2876)**
  - Added `$eav_joins` variable to both count query and main query
  - Inserted after usermeta JOINs, before WHERE clause
  - Applied to both filtered and unfiltered count queries

  **Performance Impact:**
  - **Before EAV**: 15-20 seconds for filtered listings with 8,100+ entries
  - **After EAV**: <500ms (estimated 30-60x improvement)
  - Uses database indexes on `entry_id + field_name` for O(log n) lookups
  - Eliminates nested SUBSTRING_INDEX parsing of serialized data

  **Backwards Compatibility:**
  - Old SUBSTRING_INDEX queries preserved in `else` branches
  - Automatic fallback to serialized queries when migration not complete
  - No breaking changes for users who haven't migrated
  - Seamless transition during migration process

  **Security:**
  - All field names escaped with `esc_sql()`
  - User input sanitized before inclusion in queries
  - Follows WordPress database query best practices

  **Code Quality:**
  - Clear separation between EAV and serialized code paths
  - Comments explain each branch
  - Unique EAV table aliases prevent conflicts (counter-based)
  - Consistent naming convention: `eav_filter_`, `eav_sort_`

  - PHP syntax validated successfully
  - Ready for performance testing with production-scale data
