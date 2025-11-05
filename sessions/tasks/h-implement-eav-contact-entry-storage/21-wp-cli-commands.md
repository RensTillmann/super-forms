---
name: 21-subtask
status: pending
priority: high
created: 2025-10-31
updated: 2025-11-05
---

# WP-CLI Commands

## Problem/Goal

**PRIORITY UPGRADED TO HIGH (2025-11-05):** With automatic background migration, WP-CLI commands are critical for debugging and managing migrations on production sites.

**Required Commands:**
- `wp superforms migration status` - Check background migration progress
- `wp superforms migration reset` - Recover from stuck migrations
- `wp superforms migration force-start` - Manual trigger for testing
- `wp superforms migration rollback` - Emergency rollback to serialized
- `wp superforms db stats` - EAV table statistics (entry count, table size, indexes)

## Success Criteria

- [ ] Implementation complete
- [ ] Tests pass
- [ ] Integration verified

## Estimated Effort

**2-3 days**

## Dependencies

- Previous subtasks complete

## Related Research

- Main README: Phase 7 details
- Research task: h-research-eav-migration-complete.md
