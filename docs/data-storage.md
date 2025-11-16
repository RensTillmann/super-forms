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

All entries are stored inside `wp_posts` table as post_type `super_contact_entry`

**Entry Data Storage (v6.0.0+):**
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
- Entry creation: Allowed (new entries use dual-write to both storage formats)
- Entry editing: **Blocked** (prevents race conditions and data inconsistencies)
- Entry deletion: Allowed (deletes from both storage formats)

## Where are the Forms stored?

All forms are stored inside `wp_posts` table as post_type `super_form`

## Where are the individual form settings stored?

Individual form Settings are stored inside `wp_postmeta` table under the meta key `_super_form_settings`

## Where are the form elements stored?

Individual form Elements are stored inside `wp_postmeta` table under the meta key `_super_elements`

## Where are the form triggers stored?

Individual form Triggers are stored inside `wp_postmeta` table under the meta key `_super_triggers`

## Where are the form translations stored?

Individual form translations are stored inside `wp_postmeta` table under the meta key `_super_translations`
