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
- WordPress plugin (PHP 7.4+, WordPress 6.4+)
- React for Email Builder v2
- jQuery for form builder and frontend
- SASS for styling
- Action Scheduler for background jobs

**Key Directories:**
- `/src/` - Source files (add-ons, assets, core classes)
- `/dist/` - Production build output
- `/docs/` - Documentation
- `/sessions/` - Task management and session state

## Action Scheduler: Bundled Library Architecture

**Critical Understanding:** Action Scheduler is NOT part of WordPress core - it's a third-party library bundled within Super Forms.

**Current Bundled Version:** 3.9.3
- Location: `/src/includes/lib/action-scheduler/`
- Requirements: PHP 7.2+, WordPress 6.5+
- Loaded early: Before `plugins_loaded` hook in `super-forms.php` line 168

**Version Conflict Resolution:**
WordPress uses automatic version resolution when multiple plugins bundle Action Scheduler:
1. Each plugin registers its bundled version via `ActionScheduler_Versions::register()`
2. WordPress loads the HIGHEST version number across all plugins
3. That single version handles background tasks for ALL plugins using Action Scheduler

**Why Plugin Requirements Must Align:**

The plugin's minimum requirements (PHP 7.4+, WordPress 6.4+) must be AT LEAST as strict as the bundled Action Scheduler's requirements. Here's why:

- Action Scheduler v3.9.3 requires PHP 7.2+ minimum (uses nullable type syntax)
- If Super Forms advertised PHP 5.4+ compatibility, users would encounter fatal errors
- The plugin cannot function without Action Scheduler (used for migration, cleanup, background processing)

**Version Update Protocol:**

When updating the bundled Action Scheduler library:
1. Check Action Scheduler's PHP/WordPress requirements in `action-scheduler.php` header
2. Update Super Forms plugin requirements if Action Scheduler's are stricter
3. Test version resolution behavior if other plugins bundle different versions
4. Document version change in changelog

**Reference:** Plugin requirements updated from WP 4.9/PHP 5.4 to WP 6.4/PHP 7.4 to align with Action Scheduler v3.9.3 requirements.

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
- `SUPER_Cron_Fallback` - WP-Cron failure detection and automatic remediation (since 6.4.127)

**Backwards Compatibility Guarantee:**
- Third-party code using `get_post_meta($entry_id, '_super_contact_entry_data', true)` continues working indefinitely
- WordPress meta hooks intercept and route to EAV storage after migration completes
- Performance: <1ms overhead per page load (fast string comparison bailout)

**30-Day Retention Policy:**
- After migration completes, serialized data is retained for 30 days
- Automatic cleanup via Action Scheduler (`super_cleanup_old_serialized_data` hook)
- Provides safety buffer for issue detection while preventing storage bloat
- Cleanup runs every 5 minutes with configurable batch limiting

**WP-Cron Fallback System (since 6.4.127):**
- **Auto-Detection**: Detects when WP-Cron fails (disabled, low traffic, server issues)
- **Staleness Threshold**: 15 minutes without background job processing triggers intervention
- **Smart Remediation**: Four processing modes with automatic fallback chain
- **User-Friendly Notice**: Simple "Database Upgrade Required" message (no technical details)
- **Automatic Async Mode**: Enables Action Scheduler async processing when `DISABLE_WP_CRON` detected
- **Coverage**: Works for all background jobs (migration, cleanup, email reminders)
- **AJAX Endpoints**: Three endpoints - trigger (4 modes), progress polling, and dismissal
- **Queue Tracking**: Monitors successful Action Scheduler runs via `action_scheduler_after_process_queue` hook
- **Progress Monitoring**: Real-time progress bar updates via polling for async and monitor modes

**Developer Guidelines:**
- **Use Data Access Layer**: Always use `SUPER_Data_Access::get_entry_data()` instead of direct `get_post_meta()`
- **LEFT JOIN for Listings**: Use LEFT JOIN (not INNER JOIN) to include both serialized and EAV entries
- **Migration Testing**: Import test data via Developer Tools → CSV Import (see Developer Tools section)

**Documentation:**
- Architecture details: [docs/CLAUDE.development.md](docs/CLAUDE.development.md#background-migration-system)
- WP-Cron fallback: [docs/CLAUDE.development.md](docs/CLAUDE.development.md#wp-cron-fallback-system)
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

## Trigger/Action Extensibility System (since 6.5.0)

**Overview:** Event-driven automation system enabling custom responses to form lifecycle events. Supports complex conditions, multiple action types, and scope-aware triggering.

**Architecture:**
- **Event-driven**: Triggers fire on form events (submission, validation, spam detection, etc.)
- **Scope-aware**: Triggers can apply to specific forms, global, user-specific, or role-based
- **Condition engine**: AND/OR/NOT grouping with tag replacement (`{field_name}`)
- **Extensible actions**: 20 built-in actions with base class for custom implementations

**Core Classes** (7 foundation + 1 scheduler + 3 DALs + 1 spam + 1 session cleanup + 1 email migration + 5 logging + 5 API security):
- `SUPER_Trigger_Registry` - Central event/action registration (singleton) - `/src/includes/triggers/class-trigger-registry.php`
- `SUPER_Trigger_DAL` - Database abstraction with scope-aware queries - `/src/includes/class-trigger-dal.php`
- `SUPER_Trigger_Manager` - Business logic, validation, permissions - `/src/includes/class-trigger-manager.php`
- `SUPER_Trigger_Executor` - Event firing and action execution - `/src/includes/class-trigger-executor.php`
- `SUPER_Trigger_Conditions` - Complex condition evaluation - `/src/includes/class-trigger-conditions.php`
- `SUPER_Trigger_Action_Base` - Abstract base for action implementations - `/src/includes/triggers/class-trigger-action-base.php`
- `SUPER_Trigger_REST_Controller` - REST API endpoints - `/src/includes/class-trigger-rest-controller.php`
- `SUPER_Trigger_Scheduler` - Action Scheduler integration for async execution - `/src/includes/class-trigger-scheduler.php`
- `SUPER_Session_DAL` - Session storage for progressive forms (auto-save, recovery via client token, pre-submission firewall) - `/src/includes/class-session-dal.php`
- `SUPER_Session_Cleanup` - Background session cleanup via Action Scheduler (abandoned detection, expiration, event firing) - `/src/includes/class-session-cleanup.php`
- **Client-Side**: Session manager (vanilla JS, diff-tracking, AbortController) - `/src/assets/js/frontend/session-manager.js`
- `SUPER_Entry_DAL` - Entry CRUD (always writes to custom table, reads check migration state for backwards compat) - `/src/includes/class-entry-dal.php`
- `SUPER_Spam_Detector` - Multi-method spam detection (honeypot, time-based, IP, keywords, Akismet) - `/src/includes/class-spam-detector.php`
- `SUPER_Email_Trigger_Migration` - Bidirectional Email v2 ↔ Triggers sync, legacy email migration - `/src/includes/class-email-trigger-migration.php`

**Logging Infrastructure** (5 classes):
- `SUPER_Trigger_Logger` - Centralized logging with levels (ERROR/WARNING/INFO/DEBUG), database storage - `/src/includes/class-trigger-logger.php`
- `SUPER_Trigger_Debugger` - Real-time debug data collection, visual debug panel in admin footer - `/src/includes/class-trigger-debugger.php`
- `SUPER_Trigger_Performance` - Timing, memory tracking, slow execution detection (1s/5s thresholds) - `/src/includes/class-trigger-performance.php`
- `SUPER_Trigger_Compliance` - GDPR (export/delete), PII scrubbing, audit trails, retention policies - `/src/includes/class-trigger-compliance.php`
- `SUPER_Trigger_Logs_Page` - Admin log viewer with filtering and CSV export - `/src/includes/admin/class-trigger-logs-page.php`

**API Security Infrastructure** (5 classes):
- `SUPER_Trigger_Credentials` - AES-256-CBC encrypted credential storage using WordPress salts - `/src/includes/class-trigger-credentials.php`
- `SUPER_Trigger_OAuth` - OAuth 2.0 flows with PKCE support, provider registration, token refresh - `/src/includes/class-trigger-oauth.php`
- `SUPER_Trigger_Security` - Rate limiting, suspicious pattern detection, security event logging - `/src/includes/class-trigger-security.php`
- `SUPER_Trigger_Permissions` - WordPress capabilities (super_manage_triggers, super_execute_triggers, etc.) - `/src/includes/class-trigger-permissions.php`
- `SUPER_Trigger_API_Keys` - API key generation, validation, permission management, usage tracking - `/src/includes/class-trigger-api-keys.php`

**Payment Integration** (1 class):
- `SUPER_Payment_OAuth` - Stripe Connect and PayPal OAuth flows, manual API key fallback, encrypted credential storage - `/src/includes/class-payment-oauth.php`

**Spam Detection** (`SUPER_Spam_Detector` in `/src/includes/class-spam-detector.php`):
Multi-method spam detection system running BEFORE entry creation. Fires `form.spam_detected` event with detection details.

**Detection Methods** (5 methods, ordered by speed):
1. **Honeypot** - Hidden fields that bots fill automatically (super_hp, website_url_hp, fax_number_hp)
2. **Time-based** - Submissions faster than minimum time threshold (default: 3 seconds, requires session data)
3. **IP Blacklist** - Exact match, CIDR ranges (192.168.1.0/24), wildcards (192.168.1.*) - disabled by default
4. **Keyword Filter** - Dual threshold logic: 3+ matches OR 2+ unique spam keywords - disabled by default
5. **Akismet** - Third-party API integration (requires Akismet plugin with valid key)

**Configuration**: Per-form spam settings stored in `_super_form_settings['spam_detection']` array. Default settings favor low false-positive detection methods (honeypot + time-based enabled, others disabled).

**Integration Point**: `/src/includes/class-ajax.php` in `submit_form()` method, immediately after validation passes but BEFORE entry creation. Spam detection uses session data for time-based checks and can abort submission via trigger actions.

**Logging**: Uses `SUPER_Trigger_Logger` for detection events and WP debug log if `WP_DEBUG` enabled.

**Email v2 ↔ Triggers Bidirectional Sync** (`SUPER_Email_Trigger_Migration` in `/src/includes/class-email-trigger-migration.php`):
Integration layer enabling Email v2 React builder to use the triggers system for execution while maintaining familiar UI.

**Architecture Pattern: Facade**
- Email v2 tab provides visual interface for creating/editing emails
- Backend automatically syncs to `wp_superforms_triggers` table via `send_email` actions
- Users unaware they're creating triggers (simplified UX)
- Power users can access triggers directly for advanced features

**Email Builder Modes:**
- **Visual Mode** (`body_type: 'visual'`) - Drag-drop email builder with reusable elements
- **HTML Mode** (`body_type: 'html'`) - Raw HTML editor with live preview
- Mode toggle UI in GmailChrome component (desktop/mobile layouts)
- Confirmation dialogs prevent accidental data loss when switching modes
- Visual→HTML: Converts elements to HTML via `generateHtml()` function
- HTML→Visual: Wraps content in HtmlElement component for editing

**Email Body Types** (handled by `send_email` action):
- `visual` - Email v2 builder JSON format (default for new emails)
- `html` - Raw HTML content (editable in HTML mode)
- `email_v2` - Legacy identifier for visual builder format
- `legacy_html` - Migrated legacy Admin/Confirmation email format

**Sync Flow:**
```
Email v2 UI saves → _emails postmeta → sync_emails_to_triggers() → triggers table
Email v2 UI loads ← _emails postmeta ← get_emails_for_ui() ← convert_triggers_to_emails_format()
```

**Public Methods:**
- `sync_emails_to_triggers($form_id, $emails)` - Save sync (Email v2 → Triggers)
  - Called automatically by `SUPER_Common::save_form_emails_settings()`
  - Creates/updates/deletes triggers based on email changes
  - Maintains email_id → trigger_id mapping in `_super_email_triggers` postmeta
  - Updates trigger name, enabled status, and `send_email` action config (including `body_type`)
- `convert_triggers_to_emails_format($form_id)` - Load sync (Triggers → Email v2)
  - Reads triggers table via EMAIL_TRIGGER_MAP
  - Converts `send_email` action config back to Email v2 format
  - Preserves all fields: to, from, subject, body, body_type, attachments, reply_to, cc, bcc, template, conditions, schedule
- `get_emails_for_ui($form_id)` - Main UI entry point
  - Called automatically by `SUPER_Common::get_form_emails_settings()`
  - First checks `_emails` postmeta
  - If empty, populates from triggers via `convert_triggers_to_emails_format()`
  - Saves populated data back to `_emails` for future loads
  - Enables Email v2 tab to display migrated legacy emails

**Migration Support:**
- `migrate_form($form_id)` - Converts legacy Admin/Confirmation email settings to triggers
- Background migration via Action Scheduler for all forms on plugin update
- 30-day backwards compatibility during migration window
- Trigger execution replaces legacy email sending after migration

**Integration Points:**
- `/src/includes/class-common.php` lines 121-156 - Automatic sync on save/load
- `/src/super-forms.php` line 4092 - Form builder save endpoint calls `save_form_emails_settings()`
- Email v2 React app stores data in `_emails` postmeta (sync happens transparently)

**React Components** (Email v2 builder at `/src/react/emails-v2/src/`):
- `EmailClientBuilder.jsx` - Main builder with mode toggle system, localStorage persistence
- `GmailChrome.jsx` - Gmail-style preview chrome with Visual/HTML toggle buttons
- `HtmlElement.jsx` - Custom HTML block component with code editor and live preview
- `ElementPaletteHorizontal.jsx` - Element palette (includes HtmlElement in Dynamic category)
- `useEmailBuilder.js` - Builder state management, element rendering, HTML generation

**Built-in Actions** (20 actions in `/src/includes/triggers/actions/`):
- **Communication**: `send_email`, `webhook`
- **Integration**: `http_request` (Postman-like HTTP client with auth, body formats, response mapping)
- **Data Management**: `update_entry_status`, `update_entry_field`, `delete_entry`, `increment_counter`, `set_variable`
- **WordPress Integration**: `create_post`, `update_post_meta`, `update_user_meta`, `modify_user`
- **Flow Control**: `abort_submission`, `redirect_user`, `stop_execution`, `conditional_action`, `delay_execution`
- **Utility**: `log_message`, `run_hook`, `clear_cache`

**HTTP Request Templates** (`SUPER_HTTP_Request_Templates` in `/src/includes/triggers/class-http-request-templates.php`):
Pre-built configurations for common integrations - Slack, Discord, Teams, Zapier, Make, n8n, Mailchimp, SendGrid, HubSpot, Salesforce, Airtable, Notion, Google Sheets, Telegram. Extensible via `super_http_request_register_templates` hook.

**Database Tables** (9 tables in `class-install.php`):
- `wp_superforms_triggers` - Trigger definitions with scope support
- `wp_superforms_trigger_actions` - Normalized actions (1:N with triggers)
- `wp_superforms_trigger_logs` - Execution history and debugging
- `wp_superforms_compliance_audit` - GDPR audit trail and compliance logging
- `wp_superforms_api_credentials` - Encrypted OAuth tokens and API credentials
- `wp_superforms_api_keys` - API key management for external REST access
- `wp_superforms_sessions` - Progressive form sessions for auto-save, client token recovery, and pre-submission firewall
- `wp_superforms_entries` - Entry records (Phase 17 migration from `super_contact_entry` post type)
- `wp_superforms_entry_meta` - Entry system metadata (payment IDs, integration links, flags)

**Registered Events** (36 events in `class-trigger-registry.php`):
- **Session**: `session.started`, `session.auto_saved`, `session.resumed`, `session.completed`, `session.abandoned`, `session.expired`
- **Form**: `form.loaded`, `form.before_submit`, `form.submitted`, `form.spam_detected`, `form.validation_failed`, `form.duplicate_detected`
- **Entry**: `entry.created`, `entry.saved`, `entry.updated`, `entry.status_changed`, `entry.deleted`
- **File**: `file.uploaded`, `file.upload_failed`, `file.deleted`
- **Payment (Stripe)**: `payment.stripe.checkout_completed`, `payment.stripe.payment_succeeded`, `payment.stripe.payment_failed`
- **Subscription (Stripe)**: `subscription.stripe.created`, `subscription.stripe.updated`, `subscription.stripe.cancelled`, `subscription.stripe.invoice_paid`, `subscription.stripe.invoice_failed`
- **Payment (PayPal)**: `payment.paypal.capture_completed`, `payment.paypal.capture_denied`, `payment.paypal.capture_refunded`
- **Subscription (PayPal)**: `subscription.paypal.created`, `subscription.paypal.activated`, `subscription.paypal.cancelled`, `subscription.paypal.suspended`, `subscription.paypal.payment_failed`

**Developer Tools Integration:**
- Section 8: "Trigger System Testing" in Developer Tools page
- Fire test events manually with custom context data
- View execution logs and debug trigger resolution
- AJAX endpoints: `super_dev_test_trigger`, `super_dev_get_trigger_logs`, `super_dev_clear_trigger_logs`

**REST API** (`/wp-json/super-forms/v1/`):
- `GET/POST /triggers` - List/create triggers
- `GET/PUT/DELETE /triggers/{id}` - Single trigger operations
- `POST /triggers/{id}/test` - Test trigger with mock data
- `GET /events` - List registered events
- `GET /action-types` - List registered action types
- `POST /webhooks/stripe` - Stripe webhook handler (signature verified, no auth)
- `POST /webhooks/paypal` - PayPal webhook handler (signature verified, no auth)

**Documentation:**
- Task documentation: [sessions/tasks/h-implement-triggers-actions-extensibility/README.md](sessions/tasks/h-implement-triggers-actions-extensibility/README.md)
- Event flow mapping: [sessions/tasks/h-implement-triggers-actions-extensibility/EVENT_FLOW_DOCUMENTATION.md](sessions/tasks/h-implement-triggers-actions-extensibility/EVENT_FLOW_DOCUMENTATION.md)

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
- use ssh if needed to login to dev server (see details from file @sync-to-webserver.sh and of course sync to site and check debug log if needed)