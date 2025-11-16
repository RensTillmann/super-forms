# Super Forms - Project Documentation Hub

@sessions/CLAUDE.sessions.md

## Quick Navigation

- **[Development & Deployment](docs/CLAUDE.development.md)** - Build commands, wp-env, server access, database operations
- **[JavaScript & React](docs/CLAUDE.javascript.md)** - React workflow, ESLint, pre-commit hooks, code quality
- **[PHP & WordPress](docs/CLAUDE.php.md)** - WordPress standards, security, coding conventions
- **[Testing & Quality](docs/CLAUDE.testing.md)** - Testing requirements, validation protocols

## Project Overview

Super Forms is a WordPress drag & drop form builder plugin with various add-ons and extensions.

**Tech Stack:**
- WordPress plugin (PHP 7.4+, WordPress 5.8+)
- React for Email Builder v2
- jQuery for form builder and frontend
- SASS for styling
- Action Scheduler for background jobs

**Key Directories:**
- `/src/` - Source files (add-ons, assets, core classes)
- `/dist/` - Production build output
- `/docs/` - Documentation
- `/sessions/` - Task management and session state

## Task Management Guidelines

When I give you complex requests:
- Break down the task into smaller, focused steps before starting
- Ask me to confirm your approach before proceeding
- Work on one component at a time
- Use /compact between major sections to manage context

## When I ask you to "improve code"

Instead of scanning the entire codebase, ask me to specify:
- Which specific function or file section needs improvement
- What type of improvement I'm looking for (performance, readability, etc.)
- The specific issue I want addressed

## Summary Instructions

When you are using compact, please focus on code changes

## Why We Ship Broken Code (And How to Stop)

Every AI assistant has done this: Made a change, thought "that looks right," told the user it's fixed, and then... it wasn't. The user comes back frustrated. We apologize. We try again. We waste everyone's time.

This happens because we're optimizing for speed over correctness. We see the code, understand the logic, and our pattern-matching says "this should work." But "should work" and "does work" are different universes.

### The Protocol: Before You Say "Fixed"

**1. The 30-Second Reality Check**
Can you answer ALL of these with "yes"?

□ Did I run/build the code?
□ Did I trigger the exact feature I changed?
□ Did I see the expected result with my own observation (including in the front-end GUI)?
□ Did I check for error messages (console/logs/terminal)?
□ Would I bet $100 of my own money this works?

**2. Common Lies We Tell Ourselves**
- "The logic is correct, so it must work" → **Logic ≠ Working Code**
- "I fixed the obvious issue" → **The bug is never what you think**
- "It's a simple change" → **Simple changes cause complex failures**
- "The pattern matches working code" → **Context matters**

**3. The Embarrassment Test**
Before claiming something is fixed, ask yourself:
> "If the user screen-records themselves trying this feature and it fails,
> will I feel embarrassed when I see the video?"

If yes, you haven't tested enough.

### Red Flags in Your Own Responses

If you catch yourself writing these phrases, STOP and actually test:
- "This should work now"
- "I've fixed the issue" (for the 2nd+ time)
- "Try it now" (without having tried it yourself)
- "The logic is correct so..."
- "I've made the necessary changes"

### The Minimum Viable Test

For any change, no matter how small:

1. **UI Changes**: Actually click the button/link/form
2. **API Changes**: Make the actual API call with curl/PostMan
3. **Data Changes**: Query the database to verify the state
4. **Logic Changes**: Run the specific scenario that uses that logic
5. **Config Changes**: Restart the service and verify it loads

### WordPress-Specific Testing Requirements

1. **Form Changes**: Load the form on frontend and test submission
2. **Admin Changes**: Access the admin area and verify functionality
3. **Database Changes**: Check WordPress database tables directly
4. **JavaScript Changes**: Open browser console and test interactions
5. **Plugin Changes**: Test activation, deactivation, and functionality

### The Professional Pride Principle

Every time you claim something is fixed without testing, you're saying:
- "I value my time more than yours"
- "I'm okay with you discovering my mistakes"
- "I don't take pride in my craft"

That's not who we want to be.

### Make It a Ritual

Before typing "fixed" or "should work now":
1. Pause
2. Run the actual test
3. See the actual result
4. Only then respond

**Time saved by skipping tests: 30 seconds**
**Time wasted when it doesn't work: 30 minutes**
**User trust lost: Immeasurable**

### Bottom Line

The user isn't paying you to write code. They're paying you to solve problems. Untested code isn't a solution—it's a guess.

**Test your work. Every time. No exceptions.**

---
*Remember: The user describing a bug for the third time isn't thinking "wow, this AI is really trying." They're thinking "why am I wasting my time with this incompetent tool?"*

## EAV Contact Entry Storage System

**Background:** Contact entry data migrated from serialized WordPress postmeta to dedicated EAV (Entity-Attribute-Value) tables for performance.

**Performance Impact:**
- Listings queries: 15-20 seconds → <500ms (30-60x improvement)
- Search queries: LIKE on serialized data → indexed EAV queries
- CSV exports: N+1 queries → single bulk query

**Key Components:**
- `SUPER_Data_Access` - Storage abstraction layer (routes between serialized/EAV based on migration state)
- `SUPER_Background_Migration` - Automatic migration orchestration using Action Scheduler
- `SUPER_Migration_Manager` - Entry-by-entry migration logic + backwards compatibility hooks

**Backwards Compatibility Guarantee:**
- Third-party code using `get_post_meta($entry_id, '_super_contact_entry_data', true)` continues working indefinitely
- WordPress meta hooks intercept and route to EAV storage after migration completes
- Performance: <1ms overhead per page load (fast string comparison bailout)

**30-Day Retention Policy:**
- After migration completes, serialized data is retained for 30 days
- Automatic cleanup via Action Scheduler (`super_cleanup_old_serialized_data` hook)
- Provides safety buffer for issue detection while preventing storage bloat
- Cleanup runs every 5 minutes with configurable batch limiting

**Developer Guidelines:**
- **Use Data Access Layer**: Always use `SUPER_Data_Access::get_entry_data()` instead of direct `get_post_meta()`
- **LEFT JOIN for Listings**: Use LEFT JOIN (not INNER JOIN) to include both serialized and EAV entries
- **Migration Testing**: Import test data via Developer Tools → CSV Import (see Developer Tools section)

**Documentation:**
- Architecture details: [docs/CLAUDE.development.md](docs/CLAUDE.development.md#background-migration-system)
- PHP patterns: [docs/CLAUDE.php.md](docs/CLAUDE.php.md#database-migration-patterns)
- Data storage: [docs/data-storage.md](docs/data-storage.md)

## Common Patterns in This Codebase

- Form elements are defined in `/src/includes/shortcodes/`
- AJAX handlers are in `/src/includes/class-ajax.php`
- Frontend form rendering uses shortcode system
- Backend form builder uses drag-and-drop with jQuery UI

## Git Workflow

- Main branch: `master`
- Make atomic commits with clear messages
- Test changes locally before committing
- Run code quality checks before committing

## Memory: Tab Settings Grouping

Tab settings are sometimes grouped with attributes `data-g` or `data-r` for repeater elements

## Developer Tools: CSV/XML Import Testing

The Developer Tools page supports importing real production data for migration testing:

**Key Features:**
- Import contact entries from CSV or WordPress XML exports
- Test migration with realistic data patterns (3K-26K+ entries)
- Automatic cleanup after tests complete
- Integration tests can use imported data instead of programmatic generation

**Quick Access:**
- Enable: Add `define('DEBUG_SF', true);` to wp-config.php
- URL: `https://f4d.nl/dev/wp-admin/admin.php?page=super_developer_tools`
- Documentation: See [Developer Tools Page Access](docs/CLAUDE.development.md#developer-tools-page-access)

**Preloaded Test Files** (f4d.nl/dev server):
- `superforms-test-data-3943-entries.csv` (3.4 MB)
- `superforms-test-data-3596-entries.csv` (2.8 MB)
- `superforms-test-data-26581-entries.csv` (18 MB)

**Usage Notes:**
- Imported entries tagged with `_super_test_entry` meta for cleanup
- Tests automatically delete imported data after completion
- CSV files should be in Super Forms export format
- XML import support is planned (placeholder in UI)

## Extensions: Listings Data Structure

**Storage Location:** Listings settings are stored in a SEPARATE meta key `_listings` (NOT in `_super_form_settings`)

**Data Structure Evolution:**
- **v6.3.x and earlier:** Lists stored as object with numeric keys `{"0": {...}, "1": {...}}`
- **v6.4.x and later:** Lists stored as array with unique IDs `[{"id": "NMMkW", ...}, {"id": "XyZ12", ...}]`

**Why the Change Was Required:**
- **Translation Support:** The old numeric-key format made it impossible to reliably reference listings across translations
- **Unstable References:** Numeric keys (0, 1, 2...) change when listings are reordered or deleted, breaking translation mappings
- **Unique ID Solution:** Each listing now has a permanent 5-character ID (e.g., "NMMkW") that remains constant regardless of position
- **Translation Mapping:** The i18n system can now reliably map `lists[id="NMMkW"]` to its translated version in other languages
- **Shortcode Stability:** Shortcodes using unique IDs `[super_listings list="NMMkW"]` continue working even if listing order changes
- **Field Grouping for i18n:** Moving fields into groups (display, date_range) allows the translation system to merge translated settings correctly

**Backward Compatibility (v6.4.127+):**
- **Automatic Migration:** Runs in `SUPER_Common::get_form_listings_settings()` on form load when old format detected
- **Shortcode Compatibility:** Old shortcodes with numeric IDs work: `[super_listings list="0" id="72141"]`
- **ID-based Shortcodes:** New shortcodes with unique IDs work: `[super_listings list="NMMkW" id="61768"]`
- **Field Relocation:** Migration moves 5 fields from top-level to proper groups automatically
- **Array Conversion:** Converts `custom_columns.columns` from object to array format
- **EAV Storage:** LEFT JOIN support ensures entries in EAV format are included in listings
- **CSV Export:** Uses Data Access layer to support both serialized and EAV entry formats

**Field Grouping Structure:**
- `retrieve` and `form_ids` → `display` group (not top-level)
- `noResultsFilterMessage`, `noResultsMessage`, `onlyDisplayMessage` → `date_range` group
- `custom_columns.columns` → array format (not object with numeric keys)

**Migration Details:**
- Detection: Checks for numeric keys or misplaced fields
- ID Generation: Creates unique 5-character IDs for lists missing them
- Statistics Logging: Tracks IDs generated, fields relocated, arrays converted (DEBUG_SF mode)
- Persistence: Saves migrated data immediately to prevent repeated migration

**Reference:**
- Migration implementation: `/home/rens/super-forms/src/includes/class-common.php` lines 427-548
- PHP migration pattern: `/home/rens/super-forms/docs/CLAUDE.php.md` section "Database Migration Patterns"
- JavaScript structure requirements: `/home/rens/super-forms/docs/CLAUDE.javascript.md` section "Extension JavaScript Patterns"

## Domain-Specific Documentation

For detailed information on specific development domains:

- **Build & Deployment** → [docs/CLAUDE.development.md](docs/CLAUDE.development.md)
- **JavaScript & React** → [docs/CLAUDE.javascript.md](docs/CLAUDE.javascript.md)
- **PHP & WordPress** → [docs/CLAUDE.php.md](docs/CLAUDE.php.md)
- **Testing & Quality** → [docs/CLAUDE.testing.md](docs/CLAUDE.testing.md)
