---
index: extensions-features
name: Extensions & Features
description: Tasks related to plugin extensions, feature enhancements, and functionality improvements
---

# Extensions & Features

## Active Tasks

### High Priority
- `h-implement-triggers-actions-extensibility/` - Complete refactor of triggers/actions system with custom tables, registry pattern, and full extensibility for add-ons (CRM, AI, webhooks)

### Medium Priority

### Low Priority

### Investigate

## Completed Tasks
<!-- Move tasks here when completed, maintaining the format -->

### 2025-11-14
- `h-fix-listings-backward-compatibility.md` - Fixed data structure incompatibility between v6.3.313 and v6.4.x for Listings extension
  - Implemented automatic migration from object to array format with unique IDs
  - Added backward compatibility for numeric list indices in shortcodes and POST data
  - Relocated 5 fields from top-level to proper groups (display, date_range)
  - Fixed EAV storage compatibility (INNER → LEFT JOIN)
  - Fixed CSV export column selector for EAV entries
