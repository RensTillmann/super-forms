
@sessions/CLAUDE.sessions.md

# Super Forms - Project Configuration

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

## Summary instructions

When you are using compact, please focus on code changes

## Project Overview
Super Forms is a WordPress drag & drop form builder plugin with various add-ons and extensions.

## Development Commands

### Build & Production
- `npm run prod` - Full production build with all optimizations
- `npm run sass` - Compile SASS files
- `npm run zip` - Create distribution zip files

### React Development (Email Builder v2)
- `npm run watch` - **REQUIRED for all React development** (development mode with debugging)
- `npm run build` - **PRODUCTION ONLY** (minified, no debugging - DO NOT use during development)

### Code Quality
- `npm run jshint` - Run JSHint on source files

### Cleanup
- `npm run delswaps` - Remove swap files and sass cache
- `npm run rmrf` - Clean dist folder and docs

## Server Access & Development Testing

### SSH into Development Server

**Connection Details** (SiteGround):
```bash
# SSH into dev server
ssh -p 18765 -i ~/.ssh/id_sftp u2669-dvgugyayggy5@gnldm1014.siteground.biz

# Server details:
# Host: gnldm1014.siteground.biz
# Port: 18765
# User: u2669-dvgugyayggy5
# Key:  ~/.ssh/id_sftp
# WordPress Path: /home/u2669-dvgugyayggy5/www/f4d.nl/public_html/dev/
```

**Sync Local Changes to Server**:
```bash
# Run the sync script from project root
./sync-to-webserver.sh

# This syncs /src/ to remote server using rsync
# Excludes: .git, node_modules, .vscode, *.log
# Uses --delete (removes remote files not in local)
```

### Database Access on Server

**Via SSH + MySQL**:
```bash
# SSH into server first
ssh -p 18765 -i ~/.ssh/id_sftp u2669-dvgugyayggy5@gnldm1014.siteground.biz

# Access MySQL (SiteGround typically auto-configures)
cd www/f4d.nl/public_html/dev/
wp db query "SHOW TABLES LIKE '%superforms%';"
```

**Via WP-CLI** (recommended):
```bash
# List all Super Forms tables
wp db query "SHOW TABLES LIKE '%superforms%';"

# Check migration status
wp option get superforms_eav_migration --format=json

# Check Action Scheduler queue
wp action-scheduler list --hook=superforms_migrate_batch
```

### Running Migration with Clean Table

To test migration from scratch (clears all migrated data and resets state):

**1. Truncate EAV Table**:
```bash
# Via WP-CLI (safest)
wp db query "TRUNCATE TABLE wp_superforms_entry_data;"

# Verify table is empty
wp db query "SELECT COUNT(*) FROM wp_superforms_entry_data;"
```

**2. Reset Migration State**:
```bash
# Delete migration state option
wp option delete superforms_eav_migration

# Optional: Reset plugin version to trigger version-based detection
wp option delete super_plugin_version

# Optional: Clear Action Scheduler queue
wp action-scheduler clean --hooks=superforms_migrate_batch,superforms_migration_health_check
```

**3. Trigger Migration**:
```bash
# Method 1: Version detection (recommended)
# Just visit WordPress admin - version detection runs on 'init' hook
# Navigate to: https://f4d.nl/dev/wp-admin/

# Method 2: Manually schedule first batch
wp eval 'SUPER_Background_Migration::schedule_if_needed("manual");'

# Method 3: Run WP-Cron immediately (if cron disabled)
wp cron event run --due-now
```

**4. Monitor Migration Progress**:
```bash
# Watch debug log in real-time (requires WP_DEBUG=true)
tail -f wp-content/debug.log | grep "Super Forms Migration"

# Check migration status
wp option get superforms_eav_migration --format=json

# Check Action Scheduler queue
wp action-scheduler list --hook=superforms_migrate_batch --status=pending

# Check completed actions
wp action-scheduler list --hook=superforms_migrate_batch --status=complete --limit=10
```

**5. Verify Migration Results**:
```bash
# Count migrated entries in EAV table
wp db query "SELECT COUNT(DISTINCT entry_id) as migrated_entries FROM wp_superforms_entry_data;"

# Count total contact entries
wp db query "SELECT COUNT(*) as total_entries FROM wp_posts WHERE post_type='super_contact_entry';"

# Compare counts (should match if migration complete)
wp db query "
  SELECT
    (SELECT COUNT(*) FROM wp_posts WHERE post_type='super_contact_entry') as total_entries,
    (SELECT COUNT(DISTINCT entry_id) FROM wp_superforms_entry_data) as migrated_entries;
"
```

### Developer Tools Page Access

**Enable Developer Tools**:
```bash
# SSH into server and edit wp-config.php
ssh -p 18765 -i ~/.ssh/id_sftp u2669-dvgugyayggy5@gnldm1014.siteground.biz
cd www/f4d.nl/public_html/dev/
nano wp-config.php

# Add this line before "That's all, stop editing!"
define('DEBUG_SF', true);
```

**Access Developer Tools**:
- URL: `https://f4d.nl/dev/wp-admin/admin.php?page=super_developer_tools`
- Features:
  - Test data generator
  - Migration controls (start/reset/monitor)
  - Automated verification
  - Performance benchmarks
  - Database inspector

### Common Migration Debugging Commands

```bash
# Check if migration is stuck
wp option get superforms_eav_migration --format=json | grep -E '(status|last_batch_processed_at)'

# Force release migration lock (if stuck)
wp transient delete super_migration_lock

# Check for failed entries
wp option get superforms_eav_migration --format=json | grep -A 10 'failed_entries'

# Manually run single batch (for testing)
wp eval 'SUPER_Background_Migration::process_batch_action(10);'

# Check server resource limits
wp eval 'echo "Memory: " . ini_get("memory_limit") . "\nTime: " . ini_get("max_execution_time") . "s\n";'
```

### Quick Reset for Testing

One-liner to completely reset migration for fresh test:
```bash
wp db query "TRUNCATE TABLE wp_superforms_entry_data;" && \
wp option delete superforms_eav_migration && \
wp option delete super_plugin_version && \
wp action-scheduler clean --hooks=superforms_migrate_batch,superforms_migration_health_check && \
echo "Migration reset complete. Visit admin to trigger."
```

## Project Structure
- `/src/` - Source files
  - `/add-ons/` - Plugin add-ons (calculator, csv, email reminders, etc.)
  - `/assets/` - CSS, JS, images, fonts
  - `/includes/` - Core PHP classes and extensions
  - `/i18n/` - Internationalization files
- `/dist/` - Production build output
- `/docs/` - Documentation

## Key Files
- `super-forms.php` - Main plugin file
- `src/includes/class-*.php` - Core functionality classes
- `src/includes/class-background-migration.php` - Automatic background migration orchestration
- `src/includes/class-migration-manager.php` - Entry-by-entry migration processing
- `src/includes/class-data-access.php` - Storage abstraction layer (serialized/EAV routing)
- `src/includes/class-install.php` - Database setup and self-healing
- `src/includes/lib/action-scheduler/` - Action Scheduler library (v3.9.3) for background processing
- `src/assets/js/frontend/*.js` - JavaScript files related to the front-end (rendering form)
- `src/assets/js/backend/*.js` - JavaScript files related to the backend (in wordpress dashboard/admin side)
- `src/assets/js/backend/create-form.js` - Form builder (create form) JavaScript
- `src/assets/css/frontend/create-form.css` - CSS styles related to builder page in back-end
- `src/assets/css/backend/*.css` - CSS styles related to back-end UI
- `src/assets/css/frontend/*.css` - CSS styles related to front-end UI
- `src/assets/css/frontend/elements.sass` - Main frontend styles (compiled to CSS)

## Current Development Focus
Based on recent commits:
- Completed automatic background migration system (v6.4.111-6.4.114)
- Implemented dynamic batch sizing and resource monitoring for migrations
- Added Developer Tools page with migration monitoring UI
- Working on data verification and integrity validation for EAV migration

## Testing & Validation
- Use `npm run jshint` for JavaScript linting
- No automated test suite currently configured

## WordPress Plugin Development Context

### Environment
- WordPress plugin requiring PHP 7.4+ and WordPress 5.8+
- Uses jQuery (but prefers vanilla JavaScript for frontend interactions and WordPress REST API
- Includes various third-party integrations (PayPal, Mailchimp, WooCommerce, etc.)

### Coding Standards
- Follow WordPress Coding Standards for PHP
- Use consistent indentation (4 spaces for PHP, 4 spaces for JS)
- Prefix all functions, classes objects with `super`, `SUPER`, or `sfui` to avoid conflicts 
- Use WordPress hooks and filters system for extensibility

### Common Patterns in This Codebase
- Form elements are defined in `/src/includes/shortcodes/`
- AJAX handlers are in `/src/includes/class-ajax.php`
- Frontend form rendering uses shortcode system
- Backend form builder uses drag-and-drop with jQuery UI

## Debugging Guidelines

No debugging guidelines defined try to use your best approach

## Performance Considerations

- Always try to look for the best optimized method
- Minimize database queries when possible and optimize them if possible
- Use WordPress transients for caching when appropriate
- Only load assets on pages that actually require them or Lazy load assets only when needed

## Security Best Practices

- Always sanitize user input using WordPress functions
- Use nonces for all AJAX requests
- Escape output with appropriate WordPress functions
- Validate capabilities before allowing admin actions

## Git Workflow

- Main branch: `master`
- Make atomic commits with clear messages
- Test changes locally before committing
- Run `npm run jshint` before committing JS changes

## Common Tasks Reference

### Adding a new form element:
1. Define element in `/src/includes/shortcodes/form-elements.php`
2. Add frontend JS in `/src/assets/js/frontend/elements.js`
3. Add backend JS in `/src/assets/js/backend/create-form.js`
4. Style in `/src/assets/css/frontend/elements.sass`

### Working with add-ons:
- Each add-on is self-contained in `/src/add-ons/[addon-name]/`
- Follow the existing add-on structure when creating new ones

### Working with build in extensions:
- Super Forms comes with build-in extensions such as Listings, Stripe, PDF generator
- These are located inside `/src/includes/extensions` and mostly require paymnet either via yearly manual payment or subscription via our third party custom build license system under Super Forms > Licenses

## Background Migration System

### Overview
The automatic background migration system transforms contact entry data from serialized WordPress post_meta storage (`_super_contact_entry_data`) to a dedicated EAV (Entity-Attribute-Value) table (`wp_superforms_entry_data`). This migration enables:

- **10-100x faster queries** for search, filtering, and sorting
- **Indexed field-level access** instead of full-text serialized data scans
- **Optimized CSV exports** with bulk queries instead of N+1 problems
- **Scalability** for large datasets (10K-100K+ entries)

### Architecture

**Core Components:**
- `SUPER_Background_Migration` - Orchestrates background processing using Action Scheduler
- `SUPER_Migration_Manager` - Handles entry-by-entry migration logic
- `SUPER_Data_Access` - Abstract storage layer (routes reads/writes based on migration state)
- `SUPER_Install` - Database setup with self-healing capabilities

**Infrastructure:**
- Uses Action Scheduler library (v3.9.3) bundled at `src/includes/lib/action-scheduler/`
- Falls back to WP-Cron if Action Scheduler unavailable
- Self-scheduling pattern: each batch schedules the next batch
- Survives browser closings, server restarts, and PHP timeouts

### Automatic Detection & Triggers

**Plugin Activation:**
- Hook: `register_activation_hook(__FILE__, array('SUPER_Install', 'install'))`
- Location: `super-forms.php` line 317
- Calls `SUPER_Background_Migration::schedule_if_needed('activation')`
- Creates tables, initializes state, schedules first batch

**Plugin Updates (Version-Based Detection):**
- Hook: `add_action('init', array('SUPER_Background_Migration', 'check_version_and_schedule'), 5)`
- Location: `class-background-migration.php` lines 66-136
- Compares stored version (`super_plugin_version` option) with `SUPER_VERSION` constant
- Triggers on UPGRADES only (catches FTP uploads, git pulls, manual updates)
- Self-healing: Auto-creates missing tables and state
- Race condition protection: Acquires setup lock BEFORE checking migration needs

**Hourly Health Checks:**
- Scheduled via Action Scheduler: `superforms_migration_health_check`
- Frequency: `HOUR_IN_SECONDS` (3600 seconds)
- Detects stuck migrations and resumes processing
- Maximum recovery time: 1.5 hours (30min lock TTL + 1hr health check)

### Dynamic Batch Sizing Algorithm

**Calculation Factors:**
1. **Memory-based:** `floor((memory_limit * 0.5) / 100KB per entry)`
2. **Time-based:** `floor((max_execution_time * 0.3) / 0.1s per entry)`
3. **Dataset-based:** Scales by total entries (10 for <1K, 25 for <10K, 50 for <50K, 100 for 50K+)

**Takes minimum of all three, then:**
- Applies hard caps: min 1 entry, max 100 entries per batch
- Adaptive failure handling: halves batch size if >5 failures detected
- Filter hook: `super_forms_migration_batch_size` for manual override

**Why Dynamic?**
- Shared hosting: 128M memory, 30s timeout → calculates ~20 entries/batch
- VPS hosting: 512M memory, 300s timeout → calculates ~100 entries/batch
- Prevents death spirals where static batch size causes infinite failures

### Real-Time Resource Monitoring

**Memory Threshold (85%):**
- Checks BEFORE processing each entry
- Compares `memory_get_usage(true)` vs `$memory_limit * 0.85`
- Stops batch early if exceeded, saves progress, schedules next batch

**Execution Time Threshold (70%):**
- Checks BEFORE processing each entry
- Compares `(microtime(true) - $start_time)` vs `$max_execution_time * 0.7`
- Prevents PHP timeout fatal errors

**Why These Thresholds?**
- 85% memory: Safety margin before PHP exhausts memory and dies
- 70% time: Conservative buffer before `max_execution_time` kills process
- Early stopping with saved progress is safer than hitting fatal limits

### Migration State Tracking

**Option Key:** `superforms_eav_migration`

**Structure:**
```php
array(
    'status'               => 'not_started|in_progress|completed',
    'using_storage'        => 'serialized|eav',  // Controls which storage to read from
    'total_entries'        => 1000,
    'migrated_entries'     => 450,
    'failed_entries'       => array(123 => 'error message'),
    'started_at'           => '2025-01-15 10:30:00',
    'completed_at'         => '',
    'last_processed_id'    => 4567,  // Resume point for interrupted migrations
    'verification_passed'  => false,
    'rollback_available'   => true
)
```

**Phase-Based Storage Routing:**
- Phase 1 (not_started): Write serialized only
- Phase 2 (in_progress): DUAL-WRITE to both storages (safety during migration)
- Phase 3 (completed + rolled back): Write serialized only
- Phase 4 (completed + using_storage=eav): Write EAV only (optimized)

### Database Architecture

**EAV Table:** `wp_superforms_entry_data`
```sql
CREATE TABLE wp_superforms_entry_data (
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

**Indexes:**
- `entry_id` - Lookup by entry (get all fields for one entry)
- `field_name` - Lookup by field (get specific field across entries)
- `entry_field` - Combined lookup (get specific field for specific entry)
- `field_value(191)` - Search/filter support (prefix index for LONGTEXT)

**Post Meta Keys:**
- `_super_contact_entry_data` - Serialized entry data (pre-migration or dual-write)
- `_super_test_entry` - Test entry flag (value: 1/true)
- `_super_contact_entry_ip` - Entry IP address
- `_super_contact_entry_status` - Custom entry status

**Options:**
- `superforms_eav_migration` - Migration state tracking
- `super_plugin_version` - Stored version for update detection
- `superforms_db_version` - Database schema version (currently '1.0.0')

### Lock Mechanisms

**Migration Lock (prevents concurrent batch processing):**
- Transient key: `super_migration_lock`
- Duration: 1800 seconds (30 minutes)
- Purpose: Ensures only one batch processes at a time

**Setup Lock (prevents race conditions during table creation):**
- Transient key: `super_setup_lock`
- Duration: 600 seconds (10 minutes)
- Purpose: Prevents multiple simultaneous table creation attempts after FTP uploads
- **Critical Pattern:** Lock acquired BEFORE checking if migration needed

**Why Lock Before Check?**
After FTP upload, many visitors hit site simultaneously. Each runs `check_version_and_schedule()` on `init` hook. Without proper ordering, dozens of requests could start table creation simultaneously. Correct pattern:
1. Acquire lock FIRST
2. Check if migration needed SECOND (inside lock protection)
3. Release lock in `finally` block for guaranteed cleanup

### Configuration & Filters

**Filter Hooks:**
- `super_forms_migration_batch_size` - Override dynamic batch size calculation
- Applied after all calculations, allows manual tuning

**Constants:**
- `DEBUG_SF` - When true, enables Developer Tools menu for migration monitoring
- `SUPER_VERSION` - Current plugin version for update detection
- `DISABLE_WP_CRON` - If true, WP-Cron fallback won't work (Action Scheduler still works)

**Action Scheduler Hooks:**
- `superforms_migrate_batch` - Processes one batch of entries
- `superforms_migration_health_check` - Hourly health check for stuck migrations

**WP-Cron Fallback Hooks:**
- `super_migration_cron_batch` - Processes batch if Action Scheduler unavailable
- `super_migration_cron_health` - Health check via WP-Cron

### Monitoring & Troubleshooting

**Developer Tools Page:**
- Location: Super Forms > Developer Tools (only visible when `DEBUG_SF` constant is true)
- Features: Migration status, progress tracking, manual controls, CSV import
- File: `src/includes/admin/views/page-developer-tools.php`

**Progress Monitoring:**
- Real-time progress bar with percentage
- Entry counts (migrated/total/failed)
- Status badges (not_started, in_progress, completed)
- Storage mode indicator (serialized vs EAV)

**Debug Logging:**
- All migration actions logged to `debug.log` with `[SF Migration]` prefix
- Batch processing logs include: entry counts, memory usage, execution time
- Failed entries logged with detailed error messages

**Common Issues:**

1. **Migration Stuck:**
   - Check `debug.log` for errors
   - Verify health check is scheduled: `wp cron event list | grep migration_health`
   - Check if setup lock expired: `wp transient get super_setup_lock`
   - Maximum stuck time: 1.5 hours before automatic recovery

2. **Memory Errors:**
   - Dynamic batch size should prevent this
   - If recurring, check `debug.log` for memory_limit value
   - Consider increasing PHP `memory_limit` or using filter to reduce batch size

3. **Timeout Errors:**
   - Dynamic batch size should prevent this
   - Check `max_execution_time` in PHP settings
   - Real-time monitoring stops at 70% threshold to prevent timeouts

4. **Action Scheduler Issues:**
   - View Action Scheduler admin: Tools > Action Scheduler
   - Check for failed actions with "superforms" in action name
   - Failed actions automatically retry 3 times before marking as failed

**Manual Controls (Developer Tools):**
- Start Migration - Initiate foreground migration (legacy)
- Pause Migration - Stop processing (used for debugging)
- Reset Migration - Reset to not_started (destructive, testing only)
- Rollback - Switch back to serialized storage (preserves EAV data)
- Force Complete - Skip migration, switch to EAV (debug tool, dangerous)

### Performance Impact

**During Migration:**
- Background processing doesn't block user interactions
- Minimal resource usage due to dynamic batch sizing
- Typical completion time: 24-48 hours for 10K-50K entries
- No impact on frontend form submissions (dual-write ensures data in both formats)

**After Migration:**
- **Search:** 10-100x faster (indexed field_value vs full-text LIKE on serialized)
- **Sorting:** Enables custom field sorting (impossible with serialized)
- **CSV Export:** Single bulk query vs N+1 serialized reads
- **Listings:** 100x+ faster filters (indexed JOIN vs SUBSTRING_INDEX on serialized)

**Query Optimizations Enabled:**
- Contact Entry Search: `field_value LIKE '%keyword%'` with prefix index
- Custom Sorting: Subquery `(SELECT field_value FROM eav WHERE field_name='email')`
- Listings Filters: Indexed JOIN on `field_name` and `field_value`
- Bulk Export: `WHERE entry_id IN (...)` single query

### Key Implementation Learnings

**Action Scheduler Behavior:**
- `as_enqueue_async_action()` processes batches back-to-back in single PHP request
- 1,837 entries processed in 6 seconds with 19 batches (no breathing room)
- Solution: Use `as_schedule_single_action(time() + BATCH_DELAY)` to force separate requests

**PHP Fatal Error Handling:**
- PHP timeout/memory fatal errors bypass `finally` blocks
- Lock cleanup in `finally` won't execute if PHP dies
- Mitigation: Transient TTL (30min) + hourly health checks (max 1.5hr stuck time)

**Race Condition Pattern:**
- WRONG: Check needs_migration() → Acquire lock
- CORRECT: Acquire lock → Check needs_migration() → Release lock in finally

**Batch Size Philosophy:**
- Static batch size: Works in testing, fails in production
- Dynamic calculation: Adapts to server resources automatically
- Real-time monitoring: Stops early if resources constrained
- Adaptive failure handling: Reduces batch size if failures detected

### Code Entry Points

**Background Migration Class:**
- File: `src/includes/class-background-migration.php`
- Init: `SUPER_Background_Migration::init()` (registers hooks)
- Version Check: `check_version_and_schedule()` method (lines 83-136)
- Batch Processing: `process_batch_action()` method (schedules via Action Scheduler)
- Health Check: `health_check_action()` method (hourly verification)

**Migration Manager Class:**
- File: `src/includes/class-migration-manager.php`
- Start: `start_migration()` method (initializes state)
- Process: `process_batch($batch_size)` method (migrates N entries)
- Migrate: `migrate_entry($entry_id)` method (single entry migration)
- Complete: `complete_migration()` method (switches to EAV storage)
- Rollback: `rollback_migration()` method (switches back to serialized)

**Data Access Layer:**
- File: `src/includes/class-data-access.php`
- Read: `get_entry_data($entry_id)` method (migration-aware routing)
- Write: `save_entry_data($entry_id, $data)` method (phase-aware dual-write)
- Bulk: `get_bulk_entry_data($entry_ids)` method (optimized for exports)
- Verify: `validate_entry_integrity($entry_id)` method (compares serialized vs EAV)

**Install & Setup:**
- File: `src/includes/class-install.php`
- Activation: `install()` method (creates tables, schedules migration)
- Tables: `create_tables()` method (uses dbDelta for schema management)
- State Init: `init_migration_state()` method (creates migration option)
- Self-Healing: `ensure_tables_exist()` and `ensure_migration_state_initialized()` methods

## Development Workflow

### React Components (Email Builder v2)
When developing React components in `/src/react/emails-v2/`:

1. **Development Mode (REQUIRED for all development work)**:
   ```bash
   cd /projects/super-forms/src/react/emails-v2
   npm run watch
   ```
   This runs webpack in development mode with:
   - **Unminified code** for debugging
   - **React DevTools support** (Components & Profiler tabs)  
   - **Source maps** for easier debugging
   - **Automatic recompilation** when files change
   
   **⚠️ ALWAYS use `npm run watch` during development - NEVER use `npm run build`**

2. **Production Mode (ONLY for final releases)**:
   ```bash
   cd /projects/super-forms/src/react/emails-v2
   npm run build
   ```
   **⚠️ WARNING**: Use ONLY for final production releases. 
   - No debugging capabilities
   - Minified code makes troubleshooting impossible
   - Component names are obfuscated
   - Should NEVER be used during development or testing

3. **Development Debugging Setup**:
   - **Install React DevTools** browser extension
   - **Open DevTools** (F12) → look for **Components** and **Profiler** tabs
   - **Development build required** - component names only visible in dev mode
   - **Hard refresh** after switching modes: Ctrl+F5 (Windows/Linux) or Cmd+Shift+R (Mac)

4. **React Component Debugging**:
   - **Components tab**: View component tree, props, and state in real-time
   - **Console logs**: Added throughout components for debugging
   - **State changes**: Watch state updates live in React DevTools
   - **Event handlers**: Debug onClick, onChange events through Components tab

5. **Compiled Output**: React component changes compile to:
   - `/src/assets/js/backend/emails-v2.js` (5.7MB dev vs 424KB prod)
   - `/src/assets/css/backend/emails-v2.css`

**⚠️ Important**: Always use `npm run watch` (development mode) when debugging React components. Production builds remove all debugging capabilities.

### UI Component Guidelines

#### Icons - CRITICAL RULES
- **ONLY use Lucide React icons** for ALL UI components - NO EXCEPTIONS
- **NEVER use emoji icons (❌ 📧 🔔 📅 etc.)** - ALWAYS replace with Lucide icons
- **NEVER use custom SVG icons** - always find appropriate Lucide icon
- Import from `lucide-react`: `import { IconName } from 'lucide-react'`
- Standard icon sizing: `className="ev2-w-4 ev2-h-4"` for buttons, `ev2-w-5 ev2-h-5` for larger elements
- Examples: `<Mail />`, `<Bell />`, `<Calendar />`, `<Settings />`, etc.
- Documentation, examples, and help text MUST use Lucide icons, NOT emojis
- If tempted to use an emoji, STOP and find the appropriate Lucide icon instead

#### UI Consistency
- Use Tailwind CSS with `ev2-` prefix for all styling
- Follow existing component patterns and naming conventions
- Maintain consistent spacing, colors, and interaction patterns
- Always use Lucide icons for visual elements in the UI - no emoji icons anywhere

### Remote Server Sync
- ⚠️ Stop syncing to webserver unless told otherwise

## Memory: Tab Settings Grouping
- Tab settings are sometimes grouped with attributes `data-g` or `data-r` for repeater elements

## WordPress Plugin Development Protocol

### Core WordPress Development Principles
- **WordPress Standards First**: Always follow WordPress Coding Standards (WPCS)
- **Security by Design**: Implement sanitization, nonces, and capability checks
- **Performance Minded**: Optimize queries, use caching, conditional loading
- **Accessibility Required**: Ensure WCAG 2.1 AA compliance
- **Translation Ready**: All strings must be translatable with proper text domain

### WordPress-Specific Testing Requirements
- **Frontend Validation**: Always test forms render correctly on frontend
- **Admin Functionality**: Verify all admin features work without errors
- **Database Operations**: Confirm all database queries execute properly
- **JavaScript Console**: Check for JS errors in browser console
- **Plugin Lifecycle**: Test activation, deactivation, and uninstall processes
- **Security Verification**: Confirm nonces, sanitization, and capability checks
- **Performance Impact**: Monitor query count and page load times

### WordPress Development Stack Requirements
- **PHPCS**: Run WordPress Coding Standards validation
- **Plugin Check**: Use WordPress.org Plugin Check tool
- **Security Scanning**: Regular WPScan vulnerability checks
- **Database Optimization**: Monitor Query Monitor for performance
- **Accessibility Testing**: Use accessibility validation tools

### WordPress-Specific File Change Protocol
When editing WordPress plugin files, ALWAYS:
1. Follow WordPress naming conventions (prefix with `super_forms_`)
2. Use WordPress functions instead of native PHP when available
3. Implement proper error handling with WP_Error
4. Add inline documentation with PHPDoc standards
5. Test both frontend and admin functionality

## Automated Code Quality Hooks

### React/JavaScript File Changes - MANDATORY VALIDATION
After ANY change to React/JavaScript files (`.js`, `.jsx`, `.ts`, `.tsx`):

**HOOK 1: Build Validation (MANDATORY)**
```bash
cd /projects/super-forms/src/react/emails-v2 && npm run build
```
- **FAIL** → Fix syntax errors immediately, re-run until pass
- **PASS** → Continue with changes

**HOOK 2: Development Mode Validation (MANDATORY)**
```bash  
cd /projects/super-forms/src/react/emails-v2 && npm run watch &
```
- Always run in development mode for debugging
- Check browser console for errors
- Verify functionality works as expected

**HOOK 3: Syntax Pre-Check (RECOMMENDED)**
Before making complex changes, run:
```bash
cd /projects/super-forms/src/react/emails-v2 && npx eslint src/ --ext .js,.jsx
```

### Auto-Fix Protocol for Common Errors

**JavaScript/React Syntax Errors:**
1. **Missing commas in object spreads** - Always check `...condition && { }` syntax
2. **Unclosed parentheses/braces** - Count opening/closing brackets
3. **Import statement errors** - Verify all imports exist and are spelled correctly
4. **JSX syntax errors** - Ensure proper JSX attribute syntax

**Zustand Store Errors:**
- Use `createWithEqualityFn` from `zustand/traditional` instead of deprecated `create`

**WordPress PHP Errors:**
- Run `composer run phpcs` for WordPress Coding Standards
- Test in WordPress admin area after changes

### Error Recovery Workflow

When build fails:
1. **READ THE ERROR** - Don't guess, read the exact line number and error
2. **FIX IMMEDIATELY** - Don't continue with other changes  
3. **RE-RUN BUILD** - Verify fix works
4. **COMMIT WORKING CODE** - Only commit when build passes

### Pre-Edit Checklist
Before editing complex files:
- [ ] Know the exact line numbers to change
- [ ] Understand the surrounding syntax context  
- [ ] Have a plan for testing the change
- [ ] Build is currently passing

### Why We Ship Broken Code (And How to Stop)

Every AI assistant has done this: Made a change, thought "that looks right," told the user it's fixed, and then... it wasn't. The user comes back frustrated. We apologize. We try again. We waste everyone's time.

This happens because we're optimizing for speed over correctness. We see the code, understand the logic, and our pattern-matching says "this should work." But "should work" and "does work" are different universes.

#### The Protocol: Before You Say "Fixed"

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

#### Red Flags in Your Own Responses

If you catch yourself writing these phrases, STOP and actually test:
- "This should work now"
- "I've fixed the issue" (for the 2nd+ time)
- "Try it now" (without having tried it yourself)
- "The logic is correct so..."
- "I've made the necessary changes"

#### The Minimum Viable Test

For any change, no matter how small:

1. **UI Changes**: Actually click the button/link/form
2. **API Changes**: Make the actual API call with curl/PostMan
3. **Data Changes**: Query the database to verify the state
4. **Logic Changes**: Run the specific scenario that uses that logic
5. **Config Changes**: Restart the service and verify it loads

#### WordPress-Specific Testing Requirements

1. **Form Changes**: Load the form on frontend and test submission
2. **Admin Changes**: Access the admin area and verify functionality
3. **Database Changes**: Check WordPress database tables directly
4. **JavaScript Changes**: Open browser console and test interactions
5. **Plugin Changes**: Test activation, deactivation, and functionality

#### The Professional Pride Principle

Every time you claim something is fixed without testing, you're saying:
- "I value my time more than yours"
- "I'm okay with you discovering my mistakes"
- "I don't take pride in my craft"

That's not who we want to be.

#### Make It a Ritual

Before typing "fixed" or "should work now":
1. Pause
2. Run the actual test
3. See the actual result
4. Only then respond

**Time saved by skipping tests: 30 seconds**
**Time wasted when it doesn't work: 30 minutes**
**User trust lost: Immeasurable**

#### Bottom Line

The user isn't paying you to write code. They're paying you to solve problems. Untested code isn't a solution—it's a guess.

**Test your work. Every time. No exceptions.**

---
*Remember: The user describing a bug for the third time isn't thinking "wow, this AI is really trying." They're thinking "why am I wasting my time with this incompetent tool?"*

### WordPress Plugin Guidelines Reference

For comprehensive WordPress plugin development guidelines, security best practices, and automated validation processes, refer to `wp-plugin-guidelines.md` in this project.

**Key Guidelines to Remember:**
- Always sanitize user input with WordPress functions
- Use nonces for all form submissions and AJAX requests
- Escape all output with appropriate WordPress functions
- Check user capabilities before allowing admin actions
- Follow WordPress naming conventions for all code elements
- Use WordPress APIs instead of native PHP functions
- Implement proper error handling with WP_Error
- Test both frontend and admin functionality thoroughly