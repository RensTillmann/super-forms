# Development & Deployment Guide

## Development Commands

### Build & Production
- `npm run prod` - Full production build with all optimizations
- `npm run sass` - Compile SASS files
- `npm run zip` - Create distribution zip files

### wp-env (Local WordPress Environment)
- `npm run env:start` - Start Docker-based WordPress at localhost:8888
- `npm run env:stop` - Stop the local environment
- `npm run env:restart` - Destroy and restart (clean slate)
- `npm run env:logs` - View Docker container logs
- `npm run env:clean` - Clean all Docker resources

**Configuration:** `.wp-env.json` in project root
- WordPress 6.8
- PHP 8.0
- Development port: 8888
- MySQL port: 33066
- Debug mode enabled (WP_DEBUG, SCRIPT_DEBUG, DEBUG_SF)

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

**Note:** ⚠️ Stop syncing to webserver unless told otherwise

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
  - Test data generator (programmatic and CSV/XML import)
  - Migration controls (start/reset/monitor)
  - Migration integration tests
  - Automated verification
  - Performance benchmarks
  - Database inspector

**CSV/XML Import for Testing**:
The Developer Tools page now supports importing real production data from CSV or WordPress XML export files for testing migration with realistic data patterns.

**Data Sources:**
1. **Programmatic (Generated)** - Creates synthetic test data in code (default)
2. **CSV Import** - Imports real contact entries from CSV files
3. **XML Import** - Future support for WordPress XML exports (placeholder)

**Preloaded Test Files** (on f4d.nl/dev server):
- Located in: `wp-content/uploads/`
- `superforms-test-data-3943-entries.csv` (3.4 MB, 3,943 entries)
- `superforms-test-data-3596-entries.csv` (2.8 MB, 3,596 entries)
- `superforms-test-data-26581-entries.csv` (18 MB, 26,581 entries)
- `superforms-import.xml` (484 MB, placeholder for future XML support)

**CSV Import Features:**
- Automatic field mapping from CSV headers
- Tag imported entries with `_super_test_entry` meta for cleanup
- Support for large files (up to 18 MB tested)
- Progress logging during import
- Auto-migration option after import
- Automatic cleanup after test completion

**Usage:**
1. Navigate to Developer Tools > Test Data Generator > Import tab
2. Select data source: Programmatic, CSV, or XML
3. Choose a preloaded file or upload your own CSV
4. Enable "Tag as test entries" for automatic cleanup
5. Enable "Auto-migrate after import" to test migration immediately
6. Click "Import CSV" to begin
7. Import statistics display after completion

**Integration Tests with CSV/XML:**
The migration integration tests can now use imported data instead of programmatic generation:

1. Navigate to Migration Controls > Integration Tests
2. Select test data source:
   - **Programmatic** - Generate test data in code (default)
   - **CSV Import** - Use real production data from CSV file
   - **XML Import** - Use WordPress export data (future)
3. If CSV/XML selected, choose import file from dropdown
4. Select test to run (Full Flow, Counter Accuracy, etc.)
5. Click "Run Selected Test"
6. Test imports data, runs tests, and cleans up automatically

**Test Data Cleanup:**
- All imported entries are tagged with `_super_test_entry` meta
- Tests automatically delete test entries after completion
- Cleanup includes both post entries and EAV table data
- No manual cleanup required

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

### Database Cleanup Utilities (Troubleshooting)

**Clear Failed Action Scheduler Actions:**

When migration system encounters errors, failed actions can accumulate in the Action Scheduler database tables. Use these commands to clean up:

```bash
# SSH into server
ssh -p 18765 -i ~/.ssh/id_sftp u2669-dvgugyayggy5@gnldm1014.siteground.biz
cd www/f4d.nl/public_html/dev/

# Check failed action count
wp db query "SELECT COUNT(*) as failed_count FROM wp_actionscheduler_actions WHERE status='failed';"

# Delete failed Super Forms migration actions
wp db query "DELETE FROM wp_actionscheduler_actions WHERE hook LIKE 'superforms_%' AND status='failed';"

# Delete orphaned logs (logs referencing deleted actions)
wp db query "DELETE l FROM wp_actionscheduler_logs l
  LEFT JOIN wp_actionscheduler_actions a ON l.action_id = a.action_id
  WHERE a.action_id IS NULL;"

# Verify cleanup
wp db query "SELECT
  (SELECT COUNT(*) FROM wp_actionscheduler_actions WHERE status='failed') as failed_actions,
  (SELECT COUNT(*) FROM wp_actionscheduler_logs) as total_logs;"
```

**Complete Migration State Reset:**

Use when migration is broken and needs fresh start:

```bash
# Full reset (destructive - removes all migration progress)
wp db query "TRUNCATE TABLE wp_superforms_entry_data;" && \
wp option delete superforms_eav_migration && \
wp transient delete super_migration_lock && \
wp transient delete super_setup_lock && \
wp db query "DELETE FROM wp_actionscheduler_actions WHERE hook LIKE 'superforms_%';" && \
wp db query "DELETE l FROM wp_actionscheduler_logs l
  LEFT JOIN wp_actionscheduler_actions a ON l.action_id = a.action_id
  WHERE a.action_id IS NULL;" && \
echo "Migration fully reset. Visit admin to trigger new migration."
```

**Important:** These commands are destructive and should only be used during development/debugging. In production, always backup the database before running cleanup commands.

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
- `/sessions/` - Task management and session state

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
- These are located inside `/src/includes/extensions` and mostly require payment either via yearly manual payment or subscription via our third party custom build license system under Super Forms > Licenses

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
- Uses Action Scheduler library v3.9.3 (bundled, NOT WordPress core)
- Loaded early in `super-forms.php` (before `plugins_loaded` hook)
- Version resolution: WordPress loads highest version if multiple plugins bundle it
- Requires PHP 7.2+ minimum (Action Scheduler v3.9.3 requirement)
- Falls back to WP-Cron if Action Scheduler unavailable (theoretical - always loaded in Super Forms)
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

### Security Hardening (v6.4.126)

**SQL Injection Prevention:**
- All Action Scheduler cleanup queries use `$wpdb->prepare()` with proper placeholders
- TRUNCATE TABLE operations include table existence validation and `esc_sql()` sanitization
- No direct integer interpolation in SQL queries

**Race Condition Protection:**
- Lock acquisition uses explicit `!== false` comparison (not truthy check)
- Lock values use string `'locked'` instead of `time()` for clarity
- Setup lock acquired BEFORE checking migration needs (prevents simultaneous table creation)

**Query Optimization:**
- Expensive LEFT JOIN queries cached with 5-minute transients
- Prevents performance impact from frequent status polling (2-second intervals)
- Transient keys prefixed with `super_` for clear namespace isolation

**Production Debugging:**
- Debug filter (`super_forms_migration_debug`) allows safe troubleshooting without code changes
- Filter-based approach prevents accidental debug logging in production
- Detailed logging for lock acquisition, batch flow, and Action Scheduler execution

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

**Stored Structure:**
```php
array(
    'status'                => 'not_started|in_progress|completed',
    'using_storage'         => 'serialized|eav',  // Controls which storage to read from
    'initial_total_entries' => 1000,              // Snapshot at migration start (constant)
    'failed_entries'        => array(123 => 'error message'),
    'verification_failed'   => array(456 => 'verification error'),
    'started_at'            => '2025-01-15 10:30:00',
    'completed_at'          => 1736951234,        // Unix timestamp (changed in v6.4.127)
    'last_processed_id'     => 4567,              // Resume point for interrupted migrations
    'verification_passed'   => false,
    'rollback_available'    => true
)
```

**Live-Calculated Fields (via `get_migration_status()`):**
- `total_entries` - Current total count from database (uses snapshot during migration)
- `migrated_entries` - Live count from EAV table (excludes cleanup markers)
- `cleanup_queue` - Live counts for empty posts, posts without data, orphaned metadata, **old_serialized_data** (added in v6.4.127)

**Migration Completion Actions (v6.4.127):**
When `complete_migration()` is called, the system:
1. Updates migration state to `status: 'completed'` and `using_storage: 'eav'`
2. Records `completed_at` timestamp (Unix timestamp for 30-day retention calculations)
3. Cancels all scheduled Action Scheduler jobs (`superforms_migrate_batch`, `superforms_migration_health_check`)
4. Deletes `superforms_needs_migration` transient cache to ensure immediate status updates
5. Prevents notice reappearing if page reloads before JavaScript dismissal completes

**Why Live Calculation?**
- Database is single source of truth (no counter drift)
- Race conditions cannot inflate non-existent stored values
- Always accurate without recalculation overhead
- Simpler, more reliable architecture

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
- `super_forms_migration_debug` - Enable debug logging in production (since 6.4.126)
  - Usage: `add_filter('super_forms_migration_debug', '__return_true');`
  - Enables detailed Action Scheduler execution logging without code changes
  - Logs lock acquisition, batch flow, and processing details

**Constants:**
- `DEBUG_SF` - When true, enables Developer Tools menu for migration monitoring
- `SUPER_VERSION` - Current plugin version for update detection
- `DISABLE_WP_CRON` - If true, WP-Cron fallback won't work (Action Scheduler still works)
- `UNKNOWN_FORM_ID` (class constant) - Value -1, identifies entries with unknown/missing form association (since 6.4.126)
- `DEFAULT_MEMORY_LIMIT_MB` (class constant) - Value 256, fallback memory limit when parsing fails (since 6.4.126)

**Action Scheduler Hooks:**
- `superforms_migrate_batch` - Processes one batch of entries
- `superforms_migration_health_check` - Hourly health check for stuck migrations
- `super_cleanup_expired_sessions` - Cleans expired session data (every 5 minutes)
- `super_cleanup_expired_uploads` - Cleans expired upload directories (every 5 minutes)
- `super_cleanup_old_serialized_data` - Cleans serialized entry data after 30-day retention (every 5 minutes)

**WP-Cron Fallback Hooks:**
- `super_migration_cron_batch` - Processes batch if Action Scheduler unavailable
- `super_migration_cron_health` - Health check via WP-Cron

**Legacy Hooks (Deprecated):**
- `super_client_data_garbage_collection` - Old WP-Cron cleanup (replaced by Action Scheduler hooks)
- `super_client_data_cleanup` - Compatibility hook (fires Action Scheduler tasks)

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
- Detailed debug logs available with `[SF Migration Debug]` prefix in:
  - `SUPER_Background_Migration::process_batch_action()` - Lock acquisition, batch flow
  - `SUPER_Background_Migration::needs_migration()` - Table existence, entry counts
  - `SUPER_Migration_Manager::migrate_entry()` - Entry-level data processing
- Batch processing logs include: entry counts, memory usage, execution time
- Failed entries logged with detailed error messages
- Production debug logging: Enable via `super_forms_migration_debug` filter (since 6.4.126)
  - No code changes required in production environments
  - Safely enables Action Scheduler execution tracing

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

### Public Migration API

The migration system exposes several public methods for external access (e.g., Action Scheduler, WP-CLI, custom integrations):

**SUPER_Background_Migration (Public Methods):**
- `init()` - Register hooks and initialize background migration system
- `check_version_and_schedule()` - Version-based detection and auto-scheduling
- `schedule_if_needed($triggered_by)` - Manually schedule migration if needed
- `process_batch_action($batch_size)` - Process one batch (called by Action Scheduler)
- `health_check_action()` - Hourly health check (detects stuck migrations)
- `needs_migration()` - Check if unmigrated entries exist

**Note:** The `recalculate_migration_counter()` method was removed in v6.4.127. Migration counts are now calculated live from the database via `SUPER_Migration_Manager::get_migration_status()`, eliminating the need for counter recalculation.

**SUPER_Migration_Manager (Public Methods):**
- `get_migration_status()` - **RECOMMENDED** Get current migration status with live counts
  - **Returns:** Array with live-calculated `total_entries`, `migrated_entries`, `cleanup_queue`
  - **Since:** 6.0.0 (enhanced in 6.4.127 with live calculation)
  - **Usage:** Always use this method instead of reading option directly
  - **Example:** `$status = SUPER_Migration_Manager::get_migration_status();`
- `migrate_entry($entry_id)` - **PUBLIC** Migrate single entry from serialized to EAV
  - **Visibility changed:** Originally private, now public for Action Scheduler access
  - **Since:** 6.4.117
  - **Usage:** Called by background migration process to migrate individual entries
  - **Returns:** `true` on success, `'skipped'` for empty entries, `WP_Error` on failure
  - **Example:** `SUPER_Migration_Manager::migrate_entry(12345);`

**SUPER_Data_Access (Public Methods):**
- `get_entry_data($entry_id)` - Get entry data (migration-aware routing)
- `save_entry_data($entry_id, $data)` - Save entry data (phase-aware dual-write)
- `get_bulk_entry_data($entry_ids)` - Bulk retrieval for exports

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
- Migrate: `migrate_entry($entry_id)` method (single entry migration - **public** since v6.4.117)
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

## Garbage Collection & Cleanup System

### Overview
The plugin uses Action Scheduler for reliable automatic cleanup of expired data (sessions, uploads, serialized entry data). This replaced the legacy WP-Cron system in v6.4.127 for better reliability and maintainability.

### Cleanup Architecture

**Three Focused Cleanup Tasks:**
1. **Session Cleanup** (`super_cleanup_expired_sessions`) - Removes expired session data from wp_options
2. **Upload Cleanup** (`super_cleanup_expired_uploads`) - Deletes expired temporary upload directories
3. **Serialized Data Cleanup** (`super_cleanup_old_serialized_data`) - Removes old contact entry serialized data after 30-day retention

**Scheduling:**
- All tasks run every 5 minutes via Action Scheduler
- Configurable batch limiting for each task (default: 10 items/run)
- Guaranteed execution even on low-traffic sites

### Public API Methods

**`SUPER_Common::cleanup_expired_sessions($limit = 10)`**
- Cleans expired session data from wp_options table
- Returns: `array` with cleanup statistics (`super_session`, `sfs`, `sfsdata`, `sfsi` counts)
- Filter: `super_cleanup_sessions_limit` - Adjust batch size
- Location: `src/includes/class-common.php` line 1138

**`SUPER_Common::cleanup_expired_uploads($limit = 10)`**
- Deletes expired temporary upload directories from `wp-content/uploads/tmp/sf/`
- Returns: `array` with cleanup statistics (`uploads_deleted` count)
- Filter: `super_cleanup_uploads_limit` - Adjust batch size
- Location: `src/includes/class-common.php` line 1216

**`SUPER_Common::cleanup_old_serialized_data($limit = 10)`**
- Removes serialized entry data 30 days after EAV migration completes
- Returns: `array` with cleanup statistics (`serialized_deleted`, `days_remaining`, `reason`)
- Filter: `super_cleanup_serialized_limit` - Adjust batch size
- Location: `src/includes/class-common.php` line 1262
- **30-Day Retention:** Only runs if migration completed and 30 days have passed

**`SUPER_Common::deleteOldClientData($limit = 0)`** (Deprecated)
- Legacy wrapper for backward compatibility
- Calls the three new cleanup methods
- Location: `src/includes/class-common.php`

### 30-Day Retention Policy

After EAV migration completes:
1. `completed_at` timestamp recorded as Unix timestamp (changed from MySQL datetime in v6.4.127)
2. Serialized data retained for 30 days (safety buffer for issue detection)
3. After 30 days, `cleanup_old_serialized_data()` automatically removes `_super_contact_entry_data` postmeta
4. Batch limiting prevents database lock contention (10 entries per 5-minute run)

**Retention Calculation:**
```php
$days_since = (time() - $migration_state['completed_at']) / DAY_IN_SECONDS;
if ($days_since < 30) {
    // Wait - return days_remaining
}
// 30+ days - proceed with cleanup
```

### Migration from WP-Cron to Action Scheduler

**Changes in v6.4.127:**
- Removed: `super_client_data_garbage_collection` (WP-Cron hook, 1-minute interval)
- Added: Three separate Action Scheduler hooks (5-minute intervals)
- Improved: Error handling with try-catch blocks and error logging
- Enhanced: Return arrays with cleanup statistics for monitoring

**Benefits:**
- Reliable execution on low-traffic sites (WP-Cron requires visitors)
- Better logging and monitoring through Action Scheduler UI
- Granular control over each cleanup task
- Batch limiting prevents resource exhaustion
- Separate error handling per task type

### Customization Filters

```php
// Adjust session cleanup batch size
add_filter('super_cleanup_sessions_limit', function($limit) {
    return 25; // Clean 25 sessions per run instead of 10
});

// Adjust upload cleanup batch size
add_filter('super_cleanup_uploads_limit', function($limit) {
    return 5; // Clean 5 upload directories per run (slower servers)
});

// Adjust serialized data cleanup batch size
add_filter('super_cleanup_serialized_limit', function($limit) {
    return 50; // Clean 50 entries per run (faster cleanup after 30 days)
});
```

### Monitoring Cleanup Activity

**Debug Logging:**
```php
// Enable DEBUG_SF constant in wp-config.php
define('DEBUG_SF', true);

// Cleanup activity logged to debug.log:
// - "Super Forms: Cleaned sessions - {"super_session":123,"sfs":45,...}"
// - "Super Forms: Cleaned 10 expired upload directories"
// - "Super Forms: Cleaned 10 old serialized entry data records"
```

**Action Scheduler UI:**
- Navigate to: Tools > Action Scheduler
- Filter by: `super_cleanup_` prefix
- View: Execution history, failures, timing

**Manual Execution (Testing):**
```bash
# SSH into server
wp eval 'SUPER_Common::cleanup_expired_sessions();'
wp eval 'SUPER_Common::cleanup_expired_uploads();'
wp eval 'SUPER_Common::cleanup_old_serialized_data();'
```

## WP-Cron Fallback System

### Overview
The WP-Cron Fallback System ensures background jobs process reliably even when WordPress's WP-Cron system fails. Implemented in v6.4.127, it provides automatic detection and remediation of cron failures through a user-friendly admin interface.

### Problem Statement

Background job processing (EAV migration, cleanup tasks, email reminders) depends on WP-Cron, which can fail in several scenarios:

1. **Disabled WP-Cron** - Users set `DISABLE_WP_CRON = true` without configuring system cron
2. **Low-Traffic Sites** - WP-Cron only triggers on page loads; few visitors = delayed/missed jobs
3. **Server Issues** - Plugin conflicts, security restrictions, or server configuration problems

When WP-Cron fails, critical background jobs stall indefinitely, potentially affecting thousands of database entries.

### Architecture

**Core Component:**
- `SUPER_Cron_Fallback` - Detection, remediation, and admin notice logic
- File: `/src/includes/class-cron-fallback.php` (244 lines)
- Initialization: Auto-executes `SUPER_Cron_Fallback::init()` at bottom of file

**Integration Points:**
- Hook: `admin_init` (priority 20) - Health check runs on every admin page load via `check_cron_health()`
- Hook: `init` (priority 5) - Auto-enables async mode when `DISABLE_WP_CRON` detected via `maybe_enable_async_mode()`
- Hook: `action_scheduler_after_process_queue` - Tracks successful queue runs via `track_queue_run()` for staleness detection

**AJAX Endpoints (class-ajax.php lines 81-84, handlers 7527-7745):**
- `super_trigger_cron_fallback` - Intelligent mode detection with automatic fallback chain
  - Security: `check_ajax_referer()` and `manage_options` capability check
  - Returns 4 modes:
    - `async_monitor` - Async processing triggered, show progress bar with monitoring
    - `monitor` - Background migration already running (locked), monitor existing progress
    - `sync` - Async failed, fall back to sequential batch processing
    - `async` (legacy) - Backwards compatibility for instant dismissal
  - Lock detection: Checks `SUPER_Background_Migration::is_locked()` to prevent duplicate processing
  - Error handling: Try-catch with user-friendly error messages
- `super_get_migration_progress` - Get current migration progress for polling (NEW in 6.4.127)
  - Security: `manage_options` capability check (no nonce - read-only endpoint)
  - Returns: `percentage`, `processed`, `total`, `remaining`, `is_complete`
  - Used by: Progress bar JavaScript for real-time updates in async_monitor and monitor modes
  - Polling interval: Every 2 seconds during active migration
- `super_process_batch_sync` - Process single batch synchronously with progress data
  - Batch size: 10 entries per request (small for responsiveness)
  - Returns: Migration progress (`processed`, `remaining`, `is_complete`, `percentage`)
  - Used by: Progress bar JavaScript for sync mode sequential processing
- `super_dismiss_cron_notice` - Record dismissal timestamp in user meta
  - User meta key: `super_cron_notice_dismissed`
  - Prevents reappearance for 1 hour (3600 seconds)

### Staleness Detection

**Threshold:** 15 minutes (900 seconds)
- Constant: `SUPER_Cron_Fallback::STALENESS_THRESHOLD`
- Tracked via option: `superforms_last_queue_run` (MySQL datetime)
- Updated after each Action Scheduler queue run

**Detection Logic:**
```php
// Queue considered stale if:
$last_run = get_option('superforms_last_queue_run');

// Edge case: No run recorded yet (fresh install with broken cron)
if (!$last_run) {
    return self::has_pending_work(); // Stale if work pending but never ran
}

// Normal case: Check time since last run
$time_since_last = time() - strtotime($last_run);
return $time_since_last > 900; // 15 minutes
```

**Fresh Installation Detection:**
- If no queue run history exists AND pending work exists, queue is considered stale
- Catches broken WP-Cron on fresh installations (work scheduled but never processes)
- Prevents false negatives where absence of history is mistaken for healthy state

**Why 15 Minutes?**
- Balance between responsiveness and false positives
- Action Scheduler processes queues frequently in async mode
- Long enough to avoid triggering during normal batch delays
- Short enough to catch genuine cron failures quickly

### Auto-Detection Flow

**On Every Admin Page Load:**
1. `SUPER_Cron_Fallback::check_cron_health()` runs on `admin_init` hook
2. Checks if pending work exists (`needs_migration()`, pending Action Scheduler jobs)
3. Checks if queue is stale (>15 minutes since last run)
4. If both true, notice conditions are met (notice shown via `show_cron_fallback_notice()`)

**On Plugin Init:**
1. `SUPER_Cron_Fallback::maybe_enable_async_mode()` runs on `init` hook (priority 5)
2. Checks if `DISABLE_WP_CRON` constant is defined and true
3. If true, automatically enables Action Scheduler async processing mode
4. Sets option: `superforms_async_processing_enabled = true`

### Admin Notice System

**Notice Appearance Conditions:**
- User has `manage_options` capability (administrators only)
- Pending background work exists (migration, cleanup, or email reminders)
- Queue is stale (>15 minutes without processing)
- Not dismissed recently (last 1 hour)
- Migration not currently locked (actively running)

**Notice Content:**
- Title: "Database Upgrade Required" (non-technical, non-scary)
- Buttons: "Upgrade Now" (primary), "Dismiss" (secondary)
- Progress bar: Hidden initially, shown if sync fallback needed
- Location: Appears on ALL admin pages (high visibility)

**Design Philosophy:**
- No technical details (no mention of WP-Cron, Action Scheduler, or server issues)
- Simple call-to-action (one-click solution)
- Minimal user intervention required
- Auto-dismisses on success

### Smart Button Behavior

**When "Upgrade Now" Clicked:**

The system uses an intelligent 4-mode fallback chain:

1. **Monitor Mode** (migration already running):
   - Detects if `SUPER_Background_Migration::is_locked()` returns true
   - Shows progress bar monitoring existing background migration
   - Polls `super_get_migration_progress` every 2 seconds for updates
   - User can navigate away - migration continues in background
   - May start at 85%+ if migration was already in progress

2. **Async Monitor Mode** (preferred):
   - AJAX call to `super_trigger_cron_fallback` endpoint
   - Triggers `ActionScheduler_QueueRunner::instance()->async_request->maybe_dispatch()`
   - Returns `{mode: 'async_monitor'}` response
   - Shows progress bar with "Processing in background - safe to continue working" message
   - Polls `super_get_migration_progress` every 2 seconds for updates
   - User can navigate away - migration continues asynchronously
   - No blocking or sleep() calls (better UX)

3. **Sync Processing Fallback** (if async fails):
   - JavaScript receives `{mode: 'sync'}` response from AJAX endpoint
   - Shows inline progress bar with percentage display
   - Processes batches via AJAX (`super_process_batch_sync`) in loop
   - Each request processes 10 entries, returns updated progress
   - Updates progress bar with percentage after each batch
   - Continues until `is_complete: true` received
   - Auto-dismisses notice after 1-second delay

4. **Legacy Async Mode** (backwards compatibility):
   - Returns `{mode: 'async'}` for instant dismissal
   - Maintained for compatibility with older implementations

**Why This Four-Mode Approach?**
- **Monitor mode** prevents duplicate processing (Scenario 3 fix)
- **Async monitor** provides best UX with real-time progress feedback
- **Sync fallback** ensures completion even when async blocked by server config
- **Progress polling** gives consistent UX across async_monitor and monitor modes
- User always sees progress and completion confirmation
- No manual intervention required beyond initial button click

### Dismissal Handling

**User Dismissal:**
- Records timestamp in user meta: `super_cron_notice_dismissed`
- Notice won't reappear for 1 hour
- If jobs still stalled after 1 hour, notice reappears (persistent until resolved)

**Automatic Dismissal:**
- Async success: Notice fades out immediately
- Sync completion: Notice fades out after 1-second delay
- Migration completion: Notice won't appear again (no pending work)

**Dismissal Logic:**
```php
// Check if dismissed recently
$dismissed = get_user_meta(get_current_user_id(), 'super_cron_notice_dismissed', true);
if ($dismissed && (time() - $dismissed) < 3600) {
    return false; // Don't show notice
}
```

### Action Scheduler Async Mode

**Automatic Enablement:**
When `DISABLE_WP_CRON` is detected, the fallback system automatically enables Action Scheduler's async processing mode:

```php
// Triggered on 'init' hook (priority 5) via maybe_enable_async_mode()
if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON === true) {
    update_option('superforms_async_processing_enabled', true);
    // Set batch size for better performance (25 entries per async request)
    add_filter('action_scheduler_queue_runner_batch_size', array(__CLASS__, 'async_batch_size'));
}
```

**Important Note on Async Mode:**
- Action Scheduler's async mode is **already active by default** on admin pages
- The async request is dispatched automatically on `shutdown` hook when `is_admin()` returns true
- NO filter configuration needed to "enable" async mode - it's built into Action Scheduler
- The `action_scheduler_queue_runner_batch_size` filter only adjusts performance tuning (batch size)
- Previous implementation incorrectly used `action_scheduler_queue_runner_concurrent_batches` filter

**Async Mode Behavior:**
- Processes queue via admin-ajax.php after admin page loads (`shutdown` hook)
- Bypasses WP-Cron entirely (doesn't depend on page visitor triggers)
- Uses 60-second lock (`ActionScheduler_OptionLock`) to prevent duplicate simultaneous runs
- Batch size: 25 items per async request (vs dynamic sizing for WP-Cron mode)
- Dispatcher: `ActionScheduler_AsyncRequest_QueueRunner->maybe_dispatch()`

### Public API Methods

**SUPER_Cron_Fallback (Static Methods):**
- `init()` - Register hooks and initialize fallback system
  - Called automatically at bottom of class-cron-fallback.php
  - Registers: `admin_init`, `init`, and `action_scheduler_after_process_queue` hooks
- `track_queue_run()` - Record successful queue run timestamp
  - Hooked to: `action_scheduler_after_process_queue`
  - Updates option: `superforms_last_queue_run` (MySQL datetime format)
- `is_cron_disabled()` - Check if `DISABLE_WP_CRON` constant is true
  - Returns: Boolean
  - Usage: Determines if async mode should auto-enable
- `is_queue_stale()` - Check if >15 minutes since last queue run OR no history with pending work
  - Returns: Boolean
  - Edge case: Returns true if no history exists AND pending work exists (catches broken fresh installs)
- `has_pending_work()` - Check if migration or Action Scheduler jobs pending
  - Checks: `SUPER_Background_Migration::needs_migration()` and Action Scheduler pending actions
  - Group: `superforms-migration`
  - Returns: Boolean
- `should_show_notice()` - Determine if admin notice should display
  - Checks: Pending work, queue staleness, dismissal timestamp, migration lock status
  - Returns: Boolean (used by `show_cron_fallback_notice()` in super-forms.php)
- `try_async_processing()` - Attempt async dispatch, return true if successful
  - Non-blocking: Returns immediately without verification
  - Called by: `SUPER_Ajax::trigger_cron_fallback()` AJAX handler
  - Returns: Boolean (true if dispatch triggered, false if Action Scheduler unavailable)
- `maybe_enable_async_mode()` - Auto-enable async mode when WP-Cron disabled
  - Hooked to: `init` (priority 5)
  - Sets: `superforms_async_processing_enabled` option
  - Adds filter: `action_scheduler_queue_runner_batch_size` for performance tuning
- `async_batch_size()` - Return batch size for async processing
  - Returns: 25 (entries per async request)
  - Filter callback for: `action_scheduler_queue_runner_batch_size`
- `check_cron_health()` - Health check for stalled background jobs
  - Hooked to: `admin_init` (priority 20)
  - Runs on: Every admin page load
  - Action: Sets notice display conditions (actual notice rendered in super-forms.php)

**Constants:**
- `SUPER_Cron_Fallback::LAST_RUN_OPTION = 'superforms_last_queue_run'`
- `SUPER_Cron_Fallback::ASYNC_ENABLED_OPTION = 'superforms_async_processing_enabled'`
- `SUPER_Cron_Fallback::STALENESS_THRESHOLD = 900` (15 minutes in seconds)

### Integration with Background Migration

**How They Work Together:**

1. **Background Migration** schedules batches via Action Scheduler
2. **Action Scheduler** attempts to process queue (via WP-Cron or async)
3. **Cron Fallback** detects if queue stalls for >15 minutes
4. **Admin Notice** appears, prompting user intervention
5. **User clicks button** → Async attempt → Sync fallback if needed
6. **Migration completes** → Notice disappears automatically

**Coverage Beyond Migration:**
The fallback system works for ALL background jobs using Action Scheduler:
- EAV database migration (`superforms_migrate_batch`)
- 30-day serialized data cleanup (`super_cleanup_old_serialized_data`)
- Session cleanup (`super_cleanup_expired_sessions`)
- Upload cleanup (`super_cleanup_expired_uploads`)
- Email reminders from add-on (uses Action Scheduler hooks)

### Code Entry Points

**Class Files:**
- **Cron Fallback Class**: `/src/includes/class-cron-fallback.php` (244 lines)
  - Auto-initialization: `SUPER_Cron_Fallback::init()` called at bottom of file (line 249)
  - Hook registration: Lines 47-57 (`init()` method)
  - Detection logic: Lines 72-127 (`is_cron_disabled()`, `is_queue_stale()`, `has_pending_work()`)
  - Async processing: Lines 135-162 (`maybe_enable_async_mode()`, `async_batch_size()`)
  - Health check: Lines 166-185 (`check_cron_health()`)
  - Notice display check: Lines 189-218 (`should_show_notice()`)
  - Async trigger: Lines 220-242 (`try_async_processing()`)

**Admin Notice Display:**
- **File**: `/src/super-forms.php`
- **Method**: `show_cron_fallback_notice()` (lines 1717-1870, approximately 153 lines)
- **Called from**: `show_admin_notices()` method (line 1558)
- **JavaScript**: Inline JavaScript for button handlers and progress bar (embedded in notice HTML)
- **Notice structure**: WordPress standard `<div class="notice notice-warning">` with buttons and progress bar
- **AJAX calls**: Uses `wp.ajax.post()` for async/sync processing and dismissal

**AJAX Handlers:**
- **File**: `/src/includes/class-ajax.php`
- **Registration**: Lines 81-84 (`$ajax_events` array in `__construct()`)
  ```php
  'trigger_cron_fallback'  => false, // Admin-only
  'get_migration_progress' => false, // Admin-only (NEW in 6.4.127)
  'process_batch_sync'     => false, // Admin-only
  'dismiss_cron_notice'    => false, // Admin-only
  ```
- **Handlers**: Lines 7527-7745 (four methods)
  - `trigger_cron_fallback_logic()` - Lines 7529-7556 (28 lines) - Business logic
  - `trigger_cron_fallback()` - Lines 7565-7579 (15 lines) - AJAX wrapper
  - `process_batch_logic()` - Lines 7590-7617 (28 lines) - Business logic
  - `process_batch_sync()` - Lines 7626-7640 (15 lines) - AJAX wrapper
  - `get_migration_progress_logic()` - Lines 7702-7727 (26 lines) - Business logic
  - `get_migration_progress()` - Lines 7737-7745 (9 lines) - AJAX wrapper
  - `dismiss_cron_notice()` - Lines 7664-7676 (13 lines) - AJAX wrapper

**Hook Registration (via SUPER_Cron_Fallback::init()):**
- `action_scheduler_after_process_queue` → `track_queue_run()` (line 50)
- `admin_init` (priority 20) → `check_cron_health()` (line 54)
- `init` (priority 5) → `maybe_enable_async_mode()` (line 57)

**Plugin Loading:**
- **File**: `/src/super-forms.php`
- **Class inclusion**: Line 135 (in `includes()` method)
  ```php
  require_once( SUPER_PLUGIN_DIR . '/includes/class-cron-fallback.php' );
  ```

### Monitoring & Troubleshooting

**Check Queue Status:**
```bash
# SSH into server
wp option get superforms_last_queue_run

# Check if queue is stale (manual calculation)
# If timestamp is >15 minutes old, queue is stale
```

**Check Async Mode Status:**
```bash
wp option get superforms_async_processing_enabled
# Returns: true (enabled) or false/empty (disabled)
```

**Check Notice Dismissal:**
```bash
# For specific user (replace USER_ID)
wp user meta get USER_ID super_cron_notice_dismissed
# Returns: Unix timestamp of last dismissal
```

**Force Async Processing:**
```bash
# Manually trigger async dispatch
wp eval 'ActionScheduler_QueueRunner::instance()->async_request->maybe_dispatch();'
```

**Common Issues:**

1. **Notice Appears Repeatedly:**
   - Queue genuinely stalled (check Action Scheduler UI for failed actions)
   - Server blocking async requests (check server logs for admin-ajax.php errors)
   - Database connection issues preventing queue updates

2. **Async Processing Fails:**
   - Security plugins blocking admin-ajax.php requests
   - Server firewall blocking loopback requests
   - PHP memory/timeout limits too low for async processing

3. **Notice Doesn't Appear:**
   - Queue running normally (check `superforms_last_queue_run` timestamp)
   - User dismissed within last hour (check user meta)
   - No pending work exists (migration already complete)

### Design Decisions & Rationale

**Why 15-Minute Threshold?**
- Action Scheduler queues typically process every 1-5 minutes
- 15 minutes provides buffer for server load variations
- Short enough to catch failures before user frustration
- Long enough to avoid false positives during batch delays

**Why Two-Tier Fallback (Async → Sync)?**
- Async is preferred: Faster, doesn't block admin interface, better UX
- Sync ensures reliability: Works even when server blocks async requests
- User always gets resolution: Either silent background or visible progress

**Why Simple Admin Notice?**
- Non-technical users don't understand WP-Cron, Action Scheduler, or server issues
- "Database Upgrade Required" conveys urgency without confusion
- Single-click solution reduces support burden
- Auto-dismissal prevents notice fatigue

**Why Appear on All Admin Pages?**
- Stalled migration affects all forms functionality
- High visibility ensures admins notice and act quickly
- Prevents "out of sight, out of mind" problem

### Implementation Technical Details

**Queue Run Tracking:**
- Option key: `superforms_last_queue_run`
- Format: MySQL datetime (e.g., '2025-11-17 14:30:45')
- Updated by: `SUPER_Cron_Fallback::track_queue_run()` hooked to `action_scheduler_after_process_queue`
- Staleness calculation: `time() - strtotime($last_run) > 900` (15 minutes)
- Used for: Detecting when background job processing has stalled

**Async Mode Enablement:**
- Option key: `superforms_async_processing_enabled`
- Value: Boolean (true when auto-enabled)
- Trigger: `DISABLE_WP_CRON` constant detection on `init` hook
- Effect: Sets batch size filter for Action Scheduler (25 entries per async request)
- Note: Action Scheduler async mode is already active by default; this just optimizes batch size

**Notice Dismissal:**
- User meta key: `super_cron_notice_dismissed`
- Value: Unix timestamp of dismissal
- Duration: Notice won't reappear for 1 hour (3600 seconds)
- Persistent: If jobs still stalled after 1 hour, notice reappears
- Per-user: Each admin user can dismiss independently

**Admin Notice JavaScript:**
- Embedded inline in notice HTML (not separate file)
- Uses: `wp.ajax.post()` for AJAX calls (WordPress core abstraction)
- Nonce: `_wpnonce` field (WordPress standard)
- Progress bar: Updates via `is_complete` flag from AJAX responses
- Auto-dismiss: Fades out on completion with 1-second delay

**Error Handling:**
- AJAX endpoints use try-catch blocks for all processing
- Action Scheduler unavailable: Returns false from `try_async_processing()`
- Migration class missing: Returns error message to JavaScript
- WP_Error objects: Converted to JSON error responses
- JavaScript displays error messages in notice area

**Non-Blocking Async Test:**
- Original implementation: Used `sleep(2)` to wait for verification (blocked admin page)
- Current implementation: Returns immediately, assumes async will work
- Rationale: Better UX (no freeze), sync fallback handles failures gracefully
- Trade-off: Possible false positives where async fails but returns success

### Testing & Validation

**Test Scenario 1: DISABLE_WP_CRON = true**
1. Add `define('DISABLE_WP_CRON', true);` to wp-config.php
2. Start migration (import test data via Developer Tools)
3. Verify async mode auto-enabled: `wp option get superforms_async_processing_enabled`
4. Verify migration processes without manual intervention

**Test Scenario 2: Simulated Cron Failure**
1. Stop updating queue timestamp: Comment out `track_queue_run()` hook
2. Wait 16 minutes
3. Verify notice appears on admin dashboard
4. Click "Upgrade Now"
5. Verify async attempt → sync fallback → completion

**Test Scenario 3: Low-Traffic Site**
1. No visitors for extended period (>15 minutes)
2. Background jobs stall (WP-Cron never triggers)
3. Admin logs in, sees notice
4. Clicks button, migration completes

**Test Scenario 4: Fresh Installation with Broken Cron**
1. Fresh WordPress installation with `DISABLE_WP_CRON = true`
2. Import test data via Developer Tools
3. Migration schedules but never runs (no queue run history)
4. Admin loads any admin page
5. Verify notice appears immediately (edge case detection works)

## Current Development Focus

Based on recent commits:
- **v6.4.127** - WP-Cron fallback system and garbage collector refactoring
  - Implemented automatic WP-Cron failure detection and remediation
  - Added user-friendly admin notice with async/sync fallback processing
  - Auto-enables Action Scheduler async mode when DISABLE_WP_CRON detected
  - Replaced monolithic WP-Cron cleanup with three focused Action Scheduler tasks
  - Implemented 30-day retention for serialized data after EAV migration
  - Changed `completed_at` from MySQL datetime to Unix timestamp
  - Added batch limiting and error handling to all cleanup methods
- **v6.4.126** - Security hardening and production optimization
  - Fixed SQL injection vulnerabilities in migration cleanup queries
  - Fixed race condition in lock acquisition logic
  - Added query caching for expensive metadata operations
  - Introduced production debug filter for safe troubleshooting
  - Enhanced resource metrics tracking for batch optimization
- **v6.4.111-6.4.125** - Automatic background migration system implementation
  - Dynamic batch sizing and resource monitoring
  - Developer Tools page with migration monitoring UI
  - Data verification and integrity validation for EAV migration
