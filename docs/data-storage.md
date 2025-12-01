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

## Where are the automations stored?

**Legacy Triggers (v6.4 and earlier):**
Individual form Triggers are stored inside `wp_postmeta` table under the meta key `_super_triggers`

**Automation System (v6.5.0+):**
The extensible automation system uses dedicated database tables (renamed from triggers in Phase 26):
- `wp_superforms_automations` - Automation definitions with workflow graph support (renamed from `wp_superforms_triggers`)
- `wp_superforms_automation_actions` - Action configurations for code-based automations (renamed from `wp_superforms_trigger_actions`)
- `wp_superforms_automation_logs` - Execution history and debugging logs (renamed from `wp_superforms_trigger_logs`)
- `wp_superforms_automation_states` - Workflow state management for delayed/scheduled executions (renamed from `wp_superforms_workflow_states`)
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
- REST API: Full CRUD via `/wp-json/super-forms/v1/automations` (renamed from `/v1/triggers`)
- Payment webhooks: `/wp-json/super-forms/v1/webhooks/stripe` and `/webhooks/paypal`
- Performance: Indexed queries optimized for high-volume execution
- Security: Encrypted credential storage, OAuth 2.0 support, rate limiting

## Where are the form translations stored?

Individual form translations are stored inside `wp_postmeta` table under the meta key `_super_translations`
