---
name: 06-database-schema
status: pending
created: 2025-10-31
---

# Create EAV Database Schema

## Problem/Goal

Create `wp_superforms_entry_data` table with proper indexes for fast querying.

## Success Criteria

- [ ] EAV table created with correct schema
- [ ] All indexes created (entry_id, field_name, entry_field composite, field_value prefix)
- [ ] Multi-site compatibility (uses $wpdb->prefix)
- [ ] Migration script handles existing table
- [ ] Character set: utf8mb4, Collation: utf8mb4_unicode_ci

## Schema

```sql
CREATE TABLE {$wpdb->prefix}superforms_entry_data (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    entry_id BIGINT(20) UNSIGNED NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    field_value LONGTEXT,
    field_type VARCHAR(50),
    field_label VARCHAR(255),
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY entry_id (entry_id),
    KEY field_name (field_name),
    KEY entry_field (entry_id, field_name),
    KEY field_value (field_value(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Estimated Effort

**2 days**

## Dependencies

None (can be first DB change)

## Related Research

- Main README: Database Schema (complete structure documented)
