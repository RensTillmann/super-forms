# Data storage

?> Below you can find out where all the data and settings are stored by Super Form

- [Global Settings](where-are-the-global-settings-stored)
- [Contact Entries](where-are-the-contact-entries-stored)
- [Forms](where-are-the-forms-stored)
- [Individual Form Settings](where-are-the-individual-form-settings-stored)
- [Form Elements](where-are-the-form-elements-stored)
- [Form Translations](where-are-the-form-translations-stored)

## Where are the global settings stored?

The global settings are stored inside `wp_option` table under option key `super_settings`

## Where are the Contact Entries stored?

**Current (v6.5.0+):** Entries stored in `wp_posts` table as post_type `super_contact_entry` with migration path to custom tables.

**Entry Records (Phase 17 - In Progress):**
- **Legacy:** Entry records stored in `wp_posts` table as `super_contact_entry` post type
- **Modern:** Entry records migrating to dedicated `wp_superforms_entries` table
- **System metadata:** Payment IDs, integration links stored in `wp_superforms_entry_meta` table
- **Access Layer:** `SUPER_Entry_DAL` class (always writes to custom table, reads check migration state for backwards compat)

**Entry Field Data (EAV - v6.0.0+):**
- **Legacy:** Entry field data stored in `wp_postmeta` table under meta key `_super_contact_entry_data` (serialized)
- **Modern (EAV):** Entry field data stored in dedicated `wp_superforms_entry_data` table with indexed columns
- **Migration:** Automatic background migration from serialized to EAV format after plugin update
  - **v6.4.126:** Security hardening with SQL injection fixes and query caching optimization
  - **v6.4.111-6.4.125:** Automatic background migration implementation using Action Scheduler
- **Performance:** EAV storage provides 10-100x faster queries for search, filtering, and sorting
- **Security:** All migration queries use prepared statements and proper sanitization (v6.4.126+)
- **Entry Editing Lock:** Entry editing (both admin and front-end) is temporarily disabled while migration is in progress to prevent data integrity issues

The system automatically handles the transition transparently - no user action required.

**During Migration (status = 'in_progress'):**
- Entry viewing: Allowed (read-only operations are safe)
- Entry creation: Allowed (new entries always written to custom table)
- Entry editing: **Blocked** (prevents race conditions and data inconsistencies)
- Entry deletion: Allowed (deletes from both storage formats)

## Where are the Forms stored?

All forms are stored inside `wp_posts` table as post_type `super_form`

## Where are the individual form settings stored?

Individual form Settings are stored inside `wp_postmeta` table under the meta key `_super_form_settings`

## Where are the form elements stored?

Individual form Elements are stored inside `wp_postmeta` table under the meta key `_super_elements`

## Where are form versions stored?

**Form Version Control (v6.6.0+):**
Form versions are stored in the `wp_superforms_form_versions` table with:
- Full form snapshots (elements, settings, translations)
- Operation history since last save
- Version metadata (creator, timestamp, commit message)

**Key Features:**
- Git-like version control with snapshots
- Undo/redo via JSON Patch operations (RFC 6902)
- Automatic cleanup (keeps last 20 versions by default)
- Supports revert to any previous version

**Implementation:**
- Operations handler: `SUPER_Form_Operations` class
- REST API: `/wp-json/super-forms/v1/forms/{id}/versions`
- UI component: `VersionHistory.tsx` (React admin)
- Data layer: `SUPER_Form_DAL::create_version()`, `SUPER_Form_DAL::revert_to_version()`

## Where are the automations stored?

**Legacy Triggers (v6.4 and earlier):**
Individual form Triggers were stored inside `wp_postmeta` table under the meta key `_super_triggers`. As of version 6.5.0, this data is obsolete and is automatically removed from the database upon plugin update.

**Automation System (v6.5.0+):**
The extensible automation system uses dedicated database tables:
- `wp_superforms_automations` - Automation definitions with workflow graph support
- `wp_superforms_automation_actions` - Action configurations for code-based automations
- `wp_superforms_automation_logs` - Execution history and debugging logs
- `wp_superforms_automation_states` - Workflow state management for delayed/scheduled executions
- `wp_superforms_compliance_audit` - GDPR audit trail and compliance logging
- `wp_superforms_api_credentials` - Encrypted OAuth tokens and API credentials (AES-256-CBC)
- `wp_superforms_api_keys` - API key management for external REST access
- `wp_superforms_sessions` - Progressive form sessions (auto-save, client token recovery, pre-submission firewall)
- `wp_superforms_entries` - Entry records (Phase 17, migrating from `super_contact_entry` post type)
- `wp_superforms_entry_meta` - Entry system metadata (payment IDs, integration links, flags)

**Key differences from legacy triggers:**
- Visual workflow builder: Node-based automation flows with drag-drop interface
- Workflow storage: JSON graph structure in `workflow_graph` column for visual mode
- Node categories: Triggers (events), Actions (tasks), Conditions (logic), Control (flow)
- Dual execution modes: Visual (graph-based) and Code (action list)
- REST API: Full CRUD via `/wp-json/super-forms/v1/automations`
- Payment webhooks: `/wp-json/super-forms/v1/webhooks/stripe` and `/webhooks/paypal`
- Performance: Indexed queries optimized for high-volume execution
- Security: Encrypted credential storage, OAuth 2.0 support, rate limiting

## Where are the form translations stored?

Individual form translations are stored inside `wp_postmeta` table under the meta key `_super_translations`
