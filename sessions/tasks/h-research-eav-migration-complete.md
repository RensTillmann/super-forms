---
name: h-research-eav-migration-complete
branch: none
status: pending
created: 2025-10-30
---

# Complete EAV Migration Research & Analysis

## Problem/Goal

Super Forms currently stores contact entry field data as serialized arrays in `wp_postmeta` under the key `_super_contact_entry_data`. This approach causes severe performance issues when filtering/searching large datasets (8,000+ entries taking 15-20 seconds) because:

1. MySQL must parse serialized strings using nested `SUBSTRING_INDEX()` functions
2. Indexes cannot be used on serialized data
3. Filtering happens in `HAVING` clauses after full data retrieval

We need to transition to an Entity-Attribute-Value (EAV) table structure where each field is stored as a separate row, enabling indexed queries and 10-20x performance improvements.

**This research task will comprehensively map every system that touches entry data to ensure safe migration without breaking existing functionality.**

## Success Criteria

- [ ] **CRITICAL: {tags} system verification** - Ensure all {field_name} tag parsing works identically in frontend, backend, emails, PDFs, triggers, calculations, conditional logic, and everywhere else tags are used
- [ ] Complete inventory of every PHP file that writes/reads `_super_contact_entry_data`
- [ ] Complete inventory of every JavaScript file that accesses entry data
- [ ] Deep analysis of all add-ons and their entry data dependencies
- [ ] Deep analysis of all extensions and their entry data dependencies
- [ ] Comprehensive migration strategy document with blocking vs non-blocking approaches
- [ ] Risk assessment report with all identified risks and mitigations
- [ ] User experience flow design for migration UI
- [ ] Backward compatibility strategy ensuring zero breaking changes
- [ ] API stability guarantee for third-party integrations
- [ ] Performance benchmark baseline for current system
- [ ] GDPR compliance verification plan
- [ ] Test strategy covering all edge cases
- [ ] Developer documentation for users with custom queries
- [ ] Ready-to-implement subtask breakdown for `h-implement-eav-migration/` directory task

## Context Manifest
<!-- Added by context-gathering agent -->

### How Contact Entry Data Storage Currently Works

**The Complete Entry Data Flow - From Form Submission to Database:**

When a user submits a Super Forms form, the journey begins in `src/includes/class-ajax.php` in the `submit_form()` method (line 4559). This is the AJAX endpoint that handles all form submissions from the frontend. The method performs extensive validation, processes uploaded files, and collects all field data into a PHP array structure. Each field's data is stored with its name as the key and includes the value, type, label, and other metadata.

After data collection and validation, the system checks if contact entry saving is enabled via the `$settings['save_contact_entry']` setting. If enabled, Super Forms creates a new WordPress post of type `super_contact_entry` using `wp_insert_post()`. The post title is generated from a configurable template that can include field values via {tags}. The actual field data array is then serialized using PHP's `serialize()` function and stored as a single meta value under the key `_super_contact_entry_data` using `add_post_meta()`.

This serialized approach means ALL form field data - text inputs, dropdowns, checkboxes, file uploads, repeater fields - everything is converted into one long serialized string and stored in the `wp_postmeta` table. For a form with 20 fields, this creates ONE meta row with a serialized array containing all 20 field values, their labels, types, and metadata.

**Why This Causes Performance Problems:**

The Listings Extension (`src/includes/extensions/listings/listings.php`) demonstrates the core performance issue. When displaying a list of entries with custom columns (lines 2400-3000), the system must filter and sort entries based on specific field values. Since the data is serialized, MySQL cannot use standard WHERE clauses. Instead, the query uses nested `SUBSTRING_INDEX()` functions to parse the serialized string:

```sql
SUBSTRING_INDEX( SUBSTRING_INDEX( SUBSTRING_INDEX(meta.meta_value, 's:4:"name";s:$fckLength:"$fck";s:5:"value";', -1), '";s:', 1), ':"', -1) AS filterValue_1
```

This parsing happens for EVERY entry in the database, for EVERY custom column filter, and results are filtered in HAVING clauses AFTER retrieving all data. With 8,000+ entries, this means MySQL is parsing megabytes of serialized strings character-by-character. No indexes can help. The query must examine every single entry's full serialized data blob.

**Critical Tag System - The Heart of Super Forms:**

The {tags} system (`src/includes/class-common.php` - `email_tags()` function starting line 5649) is how Super Forms accesses field values throughout the entire plugin. This is not just for emails - it's used EVERYWHERE:

- **Emails**: Admin emails, confirmation emails, email reminders - all use `{field_name}` tags to insert submitted values
- **PDF Generation**: Templates use tags to populate PDF content with entry data
- **Conditional Logic**: Triggers evaluate conditions like "if {email} == user@domain.com then..."
- **Redirect URLs**: Can contain tags like `/thank-you?name={first_name}`
- **Entry Titles**: Contact entry post titles are generated from tags
- **Calculator Fields**: Formulas use tags like `{price} * {quantity}`
- **Front-end Posting**: Post content/title/meta populated via tags
- **WooCommerce Integration**: Product data, order meta - all via tags
- **Webhooks**: Payload data populated with tags

The tag replacement engine works by:
1. Detecting `{field_name}` patterns in any string value
2. Looking up the field value from the submitted data array OR from the serialized `_super_contact_entry_data` if reading an existing entry
3. Unserializing the entry data (which requires PHP to parse the entire serialized blob)
4. Extracting the specific field value
5. Replacing the tag with the value

This happens hundreds of times per form submission across emails, triggers, PDFs, and integrations. Every tag replacement requires unserializing the entire entry data array.

**Data Write Operations - Where Entries Are Created:**

Entry creation happens in multiple places:

1. **Primary Save Location** (`src/includes/class-ajax.php` - `submit_form()` around line 4996):
   - Creates the `super_contact_entry` post
   - Serializes the data array: `$data = serialize($data_array)`
   - Saves via: `add_post_meta($entry_id, '_super_contact_entry_data', $data)`

2. **Entry Updates** (`src/includes/class-ajax.php` - `update_contact_entry()` line 1259):
   - Retrieves existing entry: `$data = get_post_meta($id, '_super_contact_entry_data', true)`
   - Unserializes, modifies values, re-serializes
   - Updates via: `update_post_meta($id, '_super_contact_entry_data', $data)`

3. **Entry Duplication** (`src/super-forms.php` - `duplicate_entry_post_meta()` line 3167):
   - Copies all post meta including the serialized data blob directly
   - `add_post_meta($new_id, '_super_contact_entry_data', $entry_data)`

**Data Read Operations - Everywhere Data Is Accessed:**

Reading happens in numerous locations:

1. **Contact Entries Backend** (`src/includes/class-pages.php`):
   - Admin screens display entries in tables
   - Each entry unserialized to display column values
   - Search/filter requires examining serialized data

2. **Listings Extension** (`src/includes/extensions/listings/listings.php` line 2979):
   - Queries retrieve serialized data: `meta.meta_value AS contact_entry_data`
   - Unserializes every entry: `$data = unserialize($entry->contact_entry_data)`
   - Extracts specific field values for display columns

3. **Populate Form** (`src/includes/class-ajax.php` - `populate_form_data()` line 1194):
   - Retrieves entry by title/ID
   - Unserializes data: `$data = get_post_meta($entry_id, '_super_contact_entry_data', true)`
   - Returns entire data array to populate form fields

4. **CSV Export** (`src/includes/class-ajax.php` - `export_selected_entries()` line 1302):
   - Queries multiple entries with their serialized data
   - Unserializes each: `$data = unserialize($v->data)` (line 1329)
   - Extracts values for CSV columns

5. **Tag Replacement** (everywhere via `SUPER_Common::email_tags()`):
   - Any time a tag needs replacement, entry data is loaded and unserialized
   - This happens in email sending, PDF generation, webhooks, triggers, etc.

**Add-on Dependencies:**

Multiple add-ons read/write entry data:

- **WooCommerce Add-on** (`src/add-ons/super-forms-woocommerce/`): Links entries to WC orders, reads entry data for order meta
- **PayPal Add-on** (`src/add-ons/super-forms-paypal/`): Stores payment data, reads entry data for IPN processing
- **Front-end Posting** (`src/add-ons/super-forms-front-end-posting/`): Creates posts from entry data, uses tags extensively
- **Register & Login** (`src/add-ons/super-forms-register-login/`): Maps entry fields to user meta
- **Calculator** (`src/add-ons/super-forms-calculator/`): Evaluates formulas with field values via tags
- **CSV Attachment** (`src/add-ons/super-forms-csv-attachment/`): Generates CSV from entry data
- **Email Reminders** (`src/add-ons/super-forms-email-reminders/`): Scheduled emails use tags to access entry data

All of these addons access entry data through the tag system or direct get_post_meta() calls, then unserialize to extract specific field values.

**Extension Dependencies:**

- **PDF Generator** (`src/includes/extensions/pdf-generator/`): Templates use tags, requires full entry data
- **Stripe** (`src/includes/extensions/stripe/`): Payment metadata links to entries, reads data for webhooks
- **WC Instant Orders** (`src/includes/extensions/wc-instant-orders/`): Creates WooCommerce orders from entry data
- **Listings** (`src/includes/extensions/listings/`): Most complex - queries, filters, sorts, displays entries

**The Migration Challenge:**

Every single place that currently does:
```php
$data = get_post_meta($entry_id, '_super_contact_entry_data', true);
$value = $data['field_name']['value'];
```

Must continue working identically after migration. The {tags} system that powers emails, PDFs, conditionals, integrations - everything - must keep functioning without ANY developer noticing a difference.

The EAV migration will replace the serialized blob with individual rows per field in a new table, but the entire existing API surface must remain stable for backward compatibility, third-party integrations, and custom user code.

### Technical Reference - File Locations

#### Core Entry Data Operations

**Primary Write Locations:**
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-ajax.php` (lines 4559-5000+) - `submit_form()` method
  - Line 4996: Entry creation with `add_post_meta($entry_id, '_super_contact_entry_data', $data)`

**Primary Read Locations:**
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-ajax.php`:
  - Line 1216: `populate_form_data()` - Get entry to populate form
  - Line 1259: `update_contact_entry()` - Edit entry data
  - Line 1329: `export_selected_entries()` - CSV export unserialization
  - Line 1462: `get_entry_export_columns()` - Export column detection

**Tag System (CRITICAL):**
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-common.php`:
  - Line 5649: `email_tags()` - Master tag replacement function
  - Line 58: `get_tag_parts()` - Parse tag syntax like `{field_name;1}` or `{field_name;label}`
  - Used in: Emails, PDFs, triggers, redirects, conditional logic, webhooks, everywhere

**Listings Extension (Performance Critical):**
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/extensions/listings/listings.php`:
  - Lines 2437-2443: SUBSTRING_INDEX filtering on serialized data
  - Lines 2622-2640: SUBSTRING_INDEX sorting on serialized data
  - Lines 2717-2857: Main query construction with HAVING clauses
  - Line 2979: Entry data unserialization for display

**Contact Entries Backend:**
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/class-pages.php` - Admin interface
- `/root/go/src/github.com/RensTillmann/super-forms/src/super-forms.php`:
  - Line 3181: Entry duplication - `get_post_meta($id, '_super_contact_entry_data', true)`
  - Lines 1574-1603: Admin search on serialized data with LIKE queries

#### Add-ons (All Access Entry Data)

- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-woocommerce/super-forms-woocommerce.php`
- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-paypal/super-forms-paypal.php`
- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-front-end-posting/super-forms-front-end-posting.php`
- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-register-login/super-forms-register-login.php`
- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-calculator/super-forms-calculator.php`
- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-csv-attachment/super-forms-csv-attachment.php`
- `/root/go/src/github.com/RensTillmann/super-forms/src/add-ons/super-forms-email-reminders/super-forms-email-reminders.php`

#### Extensions

- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/extensions/pdf-generator/pdf-generator.php`
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/extensions/stripe/stripe.php`
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/extensions/wc-instant-orders/wc-instant-orders.php`
- `/root/go/src/github.com/RensTillmann/super-forms/src/includes/extensions/listings/listings.php` (most complex)

#### Database Table Structure

**Current:**
- `wp_posts`: Entry posts (post_type = 'super_contact_entry')
- `wp_postmeta`: Serialized data (meta_key = '_super_contact_entry_data', meta_value = serialized PHP array)

**Proposed EAV:**
- `wp_super_forms_entry_fields`: Individual field value rows
  - Columns: entry_id, field_name, field_value, field_type, field_label, etc.
  - Indexed on: entry_id, field_name for fast lookups
  - Enables direct WHERE clauses instead of SUBSTRING_INDEX parsing

### Migration Strategy Considerations

**Dual-Read Approach:**
Create abstraction layer that checks EAV table first, falls back to serialized data if not found. This allows gradual migration without breaking existing entries.

**Tag System Compatibility:**
The `email_tags()` function must be updated to:
1. Check if entry has been migrated (flag or version check)
2. Use EAV query if migrated: `SELECT field_value FROM wp_super_forms_entry_fields WHERE entry_id = X AND field_name = Y`
3. Fall back to unserialize approach if not migrated
4. Maintain identical output format - no breaking changes

**Listings Query Rewrite:**
Replace SUBSTRING_INDEX queries with proper JOINs:
```sql
LEFT JOIN wp_super_forms_entry_fields AS field1
  ON field1.entry_id = post.ID
  AND field1.field_name = 'email'
WHERE field1.field_value LIKE '%search%'
```

**Backward Compatibility Requirements:**
- Keep `_super_contact_entry_data` meta for safety (can delete after confirming migration)
- All existing get_post_meta() calls continue working
- Third-party code that directly queries postmeta still functions
- Export/import features handle both formats

**Performance Impact:**
- Listings with filters: From 15-20 seconds to under 1 second (indexed WHERE clauses)
- Tag replacement: Minimal impact (single field lookup vs array unserialization)
- Entry display: Faster (no full unserialization needed, fetch only displayed fields)
- CSV export: Slightly slower initially (multiple queries vs one), but can batch optimize

### Critical Risks & Mitigations

**Risk 1: Tag System Breaking**
- **Mitigation**: Comprehensive test suite for all tag types in all contexts
- Test matrix: Every tag modifier (;label, ;1 for repeaters) in emails/PDFs/triggers/conditionals

**Risk 2: Third-Party Integration Breakage**
- **Mitigation**: Maintain serialized data as fallback, document migration for developers
- Provide helper functions for accessing EAV data

**Risk 3: Data Loss During Migration**
- **Mitigation**: Keep original serialized data, validation checks, rollback capability
- Test migration on copy of production data first

**Risk 4: Migration Timeout on Large Databases**
- **Mitigation**: Batch processing (100-500 entries per batch), AJAX progress UI
- WP-CLI command for server-side migration

**Risk 5: Custom User Queries Breaking**
- **Mitigation**: Document migration, provide compatibility functions
- Maintain serialized data for backward compatibility period

## Research Investigation Phases

### Phase 1: Data Access Layer Discovery (Exhaustive)

#### 1.1 PHP - Contact Entry Data WRITE Operations
- [ ] Grep entire codebase for `add_post_meta.*_super_contact_entry_data`
- [ ] Grep for `update_post_meta.*_super_contact_entry_data`
- [ ] Grep for `wp_insert_post.*super_contact_entry`
- [ ] Grep for any serialization operations (`serialize(`, `maybe_serialize(`)
- [ ] Search for `$wpdb->insert` with contact entry references
- [ ] Check all add-on PHP files individually
- [ ] Check all extension PHP files individually
- [ ] Search for any custom entry save hooks/actions

**Files to deeply investigate:**
- `src/super-forms.php` (main plugin file)
- `src/includes/class-ajax.php` (AJAX handlers)
- `src/includes/class-common.php` (utility functions)
- `src/includes/class-triggers.php` (trigger system)
- `src/includes/class-shortcodes.php` (shortcode handlers)
- All add-on main files
- All extension main files

#### 1.2 PHP - Contact Entry Data READ Operations
- [ ] Grep for `get_post_meta.*_super_contact_entry_data`
- [ ] Grep for `get_posts.*super_contact_entry`
- [ ] Grep for `WP_Query.*super_contact_entry`
- [ ] Grep for `$wpdb->get_results.*super_contact_entry`
- [ ] Search for unserialization (`unserialize(`, `maybe_unserialize(`)
- [ ] Find all entry retrieval functions
- [ ] Map all data access patterns

#### 1.3 JavaScript - Frontend Dependencies
- [ ] Search for `_super_contact_entry_data` in all JS files
- [ ] Check AJAX calls that retrieve entry data
- [ ] Analyze form population logic (edit entry functionality)
- [ ] Check any JSON parsing of entry data
- [ ] Review frontend listing scripts

**JS files to investigate:**
- `src/assets/js/frontend/*.js`
- `src/includes/extensions/listings/assets/js/frontend/script.js`
- Any add-on frontend JS files

#### 1.4 JavaScript - Backend Dependencies
- [ ] Search backend JS for entry data access
- [ ] Check contact entry edit page scripts
- [ ] Review any admin AJAX handlers
- [ ] Analyze export functionality JS
- [ ] Check duplicate entry JS logic

**JS files to investigate:**
- `src/assets/js/backend/*.js`
- `src/assets/js/backend/contact-entry.js`
- `src/includes/extensions/listings/assets/js/backend/script.js`

---

### Phase 2: Feature/System Dependencies (Deep Analysis)

#### 2.1 Backend Contact Entries Page
**Deep dive into:**
- [ ] Filter system implementation
- [ ] Search functionality
- [ ] Column display logic
- [ ] Sorting mechanism
- [ ] Bulk actions (delete, export, status change)
- [ ] Entry editing interface
- [ ] Entry viewing interface
- [ ] Status management
- [ ] Query performance on current system

**Files:** `src/includes/class-pages.php`, `src/includes/class-menu.php`

#### 2.2 Listings Extension (Complete Re-analysis)
**Comprehensive audit:**
- [ ] All query patterns in listings.php (re-examine lines 2400-3000)
- [ ] Filter implementation for standard columns
- [ ] Filter implementation for custom columns
- [ ] Sorting mechanism
- [ ] Pagination logic
- [ ] Entry retrieval for view/edit modals
- [ ] AJAX endpoints used
- [ ] Frontend JavaScript interactions
- [ ] Backend configuration interface
- [ ] Performance bottlenecks identification
- [ ] Cache mechanisms (if any)

**Questions to answer:**
- How exactly does SUBSTRING_INDEX work in current queries?
- What happens when listing has 10+ filters active?
- How is entry data passed to frontend?
- Are there any caching layers?

#### 2.3 CSV Export System
- [ ] How entries are queried for export
- [ ] How field data is extracted
- [ ] Column mapping logic
- [ ] Filter application during export
- [ ] Large dataset handling
- [ ] Memory limits and batch processing

#### 2.4 Entry Duplication/Cloning
- [ ] Where duplication happens
- [ ] How data is copied
- [ ] Meta data handling
- [ ] Status preservation

#### 2.5 Entry Update/Edit System
- [ ] How existing entries are loaded for editing
- [ ] How updated data is saved
- [ ] Field value retrieval
- [ ] Validation on update

---

### Phase 3: Tag System Dependencies (CRITICAL - HIGHEST PRIORITY)

**⚠️ THE ENTIRE {TAGS} SYSTEM MUST CONTINUE WORKING IDENTICALLY AFTER MIGRATION**

The {tags} system is the primary way Super Forms accesses field values throughout the entire plugin. If this breaks, virtually every feature breaks. This requires exhaustive analysis.

#### 3.1 Tag Parsing Engine (Core Logic)
- [ ] Find all tag parsing functions (search for `{`, `}`, tag replacement logic)
- [ ] Identify where serialized entry data is accessed for tag values
- [ ] Document tag syntax variations: `{field_name}`, `{field_name;label}`, `{field_name;1}` (repeaters)
- [ ] Map the complete tag replacement pipeline from detection to value insertion
- [ ] Test all tag modifiers (semicolon parameters)
- [ ] Check nested tag support (tags within tags)
- [ ] Verify repeater field tag handling with indexes

**Files to search:**
- `src/includes/class-common.php` (likely has tag parsing functions)
- Search for functions with names like `replace_tags`, `parse_tags`, `SUPER_Common::*tags*`

#### 3.2 Email Tags (Frontend & Backend)
- [ ] Admin email tag replacement
- [ ] Confirmation email tag replacement
- [ ] Email reminders tag replacement
- [ ] Custom email template tags
- [ ] Email subject line tags
- [ ] Email from/reply-to tags
- [ ] CC/BCC field tags
- [ ] Email loop tags `{loop_label}` and `{loop_value}`
- [ ] Conditional email content with tags
- [ ] Email attachments using tags (file paths)

**Critical:** Emails are sent AFTER entry creation, so must read from database

#### 3.3 PDF Generator Tags
- [ ] PDF template tag replacement
- [ ] PDF filename tags
- [ ] PDF merge field tags
- [ ] PDF conditional content tags
- [ ] PDF header/footer tags
- [ ] Generated PDF storage path tags

**Extension location:** `src/includes/extensions/pdf/`

#### 3.4 Frontend Form Tags
- [ ] Pre-populated field values using tags
- [ ] Dynamic field visibility tags
- [ ] Frontend conditional logic evaluation
- [ ] Field placeholder tags
- [ ] Field label tags
- [ ] Default value tags
- [ ] Auto-increment field tags
- [ ] Hidden field tags (often use tags for dynamic values)

**JavaScript integration:** Check if tags are parsed in JS or PHP

#### 3.5 Backend Form Settings Tags
- [ ] Redirect URL tags `{field_name}`
- [ ] Custom entry title tags
- [ ] Thank you message tags
- [ ] Error message tags
- [ ] Post submission content tags
- [ ] Form locker message tags

#### 3.6 Conditional Logic Tags
- [ ] Frontend conditional logic: `if {field_name} == value`
- [ ] Backend conditional logic (triggers)
- [ ] Conditional save entry logic
- [ ] Conditional email sending
- [ ] Conditional redirect
- [ ] Conditional field visibility
- [ ] Conditional field requirement

**Critical:** These often compare tag values directly in strings

#### 3.7 Calculator & Variable Tags
- [ ] Calculator field formulas using tags: `{price} * {quantity}`
- [ ] Mathematical operations on tag values
- [ ] Variable fields that reference other fields
- [ ] Dynamic calculations
- [ ] Real-time vs server-side calculation differences

**Add-on:** `src/add-ons/super-forms-calculator/`

#### 3.8 Trigger System Tags
- [ ] Trigger conditions using tags
- [ ] Trigger actions using tags (create post, update user, etc.)
- [ ] Webhook payload tags
- [ ] Database query tags in triggers
- [ ] Custom code execution with tags

**Files:** `src/includes/class-triggers.php`

#### 3.9 Front-end Posting Tags
- [ ] Post title tags
- [ ] Post content tags
- [ ] Post excerpt tags
- [ ] Post meta tags
- [ ] Custom field tags
- [ ] Taxonomy tags
- [ ] Featured image tags

**Add-on:** `src/add-ons/super-forms-front-end-posting/`

#### 3.10 WooCommerce Integration Tags
- [ ] Product data tags
- [ ] Order meta tags
- [ ] Customer data tags
- [ ] Billing/shipping address tags
- [ ] Cart item tags
- [ ] Custom order field tags

**Add-on:** `src/add-ons/super-forms-woocommerce/`

#### 3.11 User Registration & Login Tags
- [ ] Username tags
- [ ] User meta tags
- [ ] User role tags
- [ ] Custom user field tags
- [ ] Multisite blog creation tags

**Add-on:** `src/add-ons/super-forms-register-login/`

#### 3.12 Listings Display Tags
- [ ] Entry display using tags
- [ ] Column value tags
- [ ] Custom column tags
- [ ] Detail view tags
- [ ] Edit modal pre-population tags

**Extension:** `src/includes/extensions/listings/`

#### 3.13 Dynamic Column Tags (Contact Entries Backend)
- [ ] Custom column definitions using field names
- [ ] Column value extraction from entries
- [ ] Filter/search using field values

**Files:** `src/includes/class-pages.php`

#### 3.14 Import/Export Tags
- [ ] CSV export column tags
- [ ] CSV import mapping tags
- [ ] Data transformation tags

#### 3.15 Webhook & API Tags
- [ ] REST API response tags
- [ ] Webhook payload tags
- [ ] Third-party integration tags
- [ ] Zapier field mapping tags

#### 3.16 System Option Tags
- [ ] `{option_admin_email}` and similar WordPress option tags
- [ ] `{option_blogname}` tags
- [ ] User info tags: `{user_login}`, `{user_email}`
- [ ] Date/time tags: `{date}`, `{time}`
- [ ] Entry metadata tags: `{entry_id}`, `{form_id}`

#### 3.17 Tag Compatibility Testing Plan
- [ ] Create test matrix of all tag locations vs tag types
- [ ] Test each tag type in each location
- [ ] Verify values match between old and new systems
- [ ] Test edge cases: empty values, special characters, HTML
- [ ] Test repeater field tags with multiple indexes
- [ ] Test conditional tags with complex logic
- [ ] Test calculator tags with multiple operations

#### 3.18 Tag System Migration Strategy
- [ ] How to ensure dual-read works for tags
- [ ] Whether tag parsing needs modification
- [ ] Whether tag caching exists and needs invalidation
- [ ] Performance impact of tag parsing with EAV vs serialized

---

### Phase 4: Add-on Deep Audit

#### 4.1 CSV Attachment Add-on
- [ ] How it reads entry data
- [ ] CSV generation logic
- [ ] Field selection
- [ ] Delimiter handling

#### 4.2 Email Reminders Add-on
- [ ] Entry data access for reminders
- [ ] Scheduled task implementation
- [ ] Field value retrieval
- [ ] Tag replacement in reminders

#### 4.3 Front-end Posting Add-on
- [ ] Post creation from entry data
- [ ] Field mapping logic
- [ ] Meta data population

#### 4.4 WooCommerce Add-on
- [ ] Order creation from entries
- [ ] Product data extraction
- [ ] Customer data mapping
- [ ] Order meta population

#### 4.5 PayPal Add-on
- [ ] Payment data storage
- [ ] Transaction linking
- [ ] Entry status updates
- [ ] IPN handling

#### 4.6 Register & Login Add-on
- [ ] User registration data
- [ ] Meta data mapping
- [ ] User update logic

#### 4.7 All Other Add-ons
- [ ] Complete audit of each add-on
- [ ] Document every entry data access point

---

### Phase 5: Extension Dependencies

#### 5.1 PDF Generator Extension
- [ ] Template population with entry data
- [ ] Field value retrieval
- [ ] PDF generation trigger
- [ ] File storage

#### 5.2 Stripe Extension
- [ ] Payment intent creation
- [ ] Entry data access
- [ ] Webhook handling
- [ ] Transaction storage

#### 5.3 WC Instant Orders Extension
- [ ] Order creation logic
- [ ] Entry data mapping
- [ ] Product variation handling

---

### Phase 6: Database Query Patterns

#### 6.1 All Serialized Data Queries
- [ ] Find every SUBSTRING_INDEX usage
- [ ] Find every LIKE on serialized data
- [ ] Document current performance
- [ ] Identify bottlenecks

#### 6.2 Meta Table Usage
- [ ] Current indexes
- [ ] Query frequency
- [ ] Join patterns
- [ ] Performance metrics

---

### Phase 7: API & Integration Points

#### 7.1 REST API Endpoints
- [ ] Check if there are REST endpoints
- [ ] How they return entry data
- [ ] JSON structure expectations

#### 7.2 Webhooks
- [ ] What data is sent
- [ ] Format expectations
- [ ] Third-party dependencies

#### 7.3 Third-party Integrations
- [ ] Zapier (if applicable)
- [ ] Mailchimp
- [ ] ActiveCampaign
- [ ] Any other integrations

---

### Phase 8: WordPress Hooks & Filters

#### 8.1 Custom Hooks
- [ ] Find all `do_action` with entry data
- [ ] Find all `apply_filters` with entry data
- [ ] Document hook parameters
- [ ] Check for third-party usage

#### 8.2 WordPress Core Hooks
- [ ] `save_post_super_contact_entry`
- [ ] `wp_insert_post_data`
- [ ] Any meta-related hooks

---

### Phase 9: Migration Strategy Design

#### 9.1 Data Integrity Requirements
- [ ] What must be preserved exactly
- [ ] What can be transformed
- [ ] Validation criteria
- [ ] Rollback requirements

#### 9.2 Migration Approaches
**Option A: Blocking Migration**
- Pros/cons analysis
- UX flow design
- Error handling
- Time estimates

**Option B: Non-blocking Migration**
- Background processing design
- Hybrid read system (old + new)
- Cutover strategy
- Data consistency during migration

**Option C: Opt-in Migration**
- Per-form basis
- User control
- Partial migration support

#### 9.3 Version Detection System
- [ ] How to mark plugin version
- [ ] How to detect migration status
- [ ] How to prevent double migration
- [ ] How to track per-entry migration state

#### 9.4 UI/UX Design
**Blocking approach:**
- [ ] Full-screen migration modal
- [ ] Progress bar implementation
- [ ] Time estimation
- [ ] Error display
- [ ] Retry mechanism
- [ ] Support contact information

**Non-blocking approach:**
- [ ] Admin notice design
- [ ] Start migration button
- [ ] Progress widget
- [ ] Background status indicator
- [ ] Completion notification

#### 9.5 Testing Strategy
- [ ] How to test with large datasets
- [ ] Performance benchmarks
- [ ] Data integrity verification
- [ ] Edge case identification

---

### Phase 10: Backward Compatibility Analysis

#### 10.1 What Must Stay Compatible
- [ ] Old entry data must remain readable
- [ ] Existing exports must work
- [ ] Add-ons on older versions
- [ ] Custom code from users

#### 10.2 Dual-Read System Design
```php
// Concept: Always check new table first, fallback to old
function get_entry_field_value($entry_id, $field_name) {
    // Try new EAV table
    $value = get_from_eav_table($entry_id, $field_name);

    if($value === null) {
        // Fallback to old serialized data
        $value = get_from_serialized_data($entry_id, $field_name);
    }

    return $value;
}
```

#### 10.3 Dual-Write System Design
```php
// Concept: Write to both during transition period
function save_entry_field_value($entry_id, $field_name, $value) {
    // Always write to old format (safety)
    save_to_serialized_data($entry_id, $field_name, $value);

    // Also write to new EAV table
    save_to_eav_table($entry_id, $field_name, $value);
}
```

---

### Phase 11: Risk Assessment

#### 11.1 Critical Risks
- [ ] Data loss scenarios
- [ ] Performance degradation during migration
- [ ] Plugin conflicts
- [ ] Server timeouts
- [ ] Memory exhaustion
- [ ] Disk space issues

#### 11.2 Mitigation Strategies
- [ ] Backup requirements
- [ ] Incremental migration
- [ ] Resource monitoring
- [ ] Graceful failure handling

---

### Phase 12: Documentation Mapping

#### 12.1 Code Comments
- [ ] Find all comments referencing serialized data
- [ ] Developer documentation
- [ ] API documentation

#### 12.2 User Documentation
- [ ] Docs that explain data storage
- [ ] Migration guides needed
- [ ] FAQ updates

---

### Phase 13: Import & Data Portability

#### 13.1 Import Functionality
- [ ] Is there an import feature for entries?
- [ ] CSV import logic
- [ ] JSON import capability
- [ ] Field mapping during import
- [ ] Validation during import
- [ ] Duplicate detection on import
- [ ] How imported data is serialized

---

### Phase 14: GDPR & Privacy Compliance

#### 14.1 Data Export (GDPR Right to Access)
- [ ] WordPress privacy exporter integration
- [ ] Personal data identification in entries
- [ ] Export format and structure
- [ ] How serialized data is included in exports

#### 14.2 Data Erasure (GDPR Right to be Forgotten)
- [ ] WordPress privacy eraser integration
- [ ] Personal data anonymization
- [ ] Entry deletion vs anonymization
- [ ] Cascading deletions

#### 14.3 Data Retention Policies
- [ ] Automatic entry deletion
- [ ] Archive before delete
- [ ] Retention period settings

---

### Phase 15: Caching Systems

#### 15.1 WordPress Object Cache
- [ ] Are entries cached?
- [ ] Cache invalidation on entry update
- [ ] Cache keys used
- [ ] Persistent cache (Redis/Memcached) compatibility

#### 15.2 Transients
- [ ] Entry-related transients
- [ ] Listing result caching
- [ ] Filter result caching
- [ ] Expiration logic

#### 15.3 Page Caching Plugins
- [ ] How listings interact with WP Rocket, W3 Total Cache, etc.
- [ ] Cache busting requirements
- [ ] Dynamic content handling

---

### Phase 16: Conditional Logic & Calculations

#### 16.1 Conditional Logic System
- [ ] How conditions evaluate field values
- [ ] Where field values are retrieved for conditions
- [ ] Frontend conditional logic
- [ ] Backend conditional logic (triggers)
- [ ] Conditional visibility rules
- [ ] Conditional required fields

#### 16.2 Calculator Add-on
- [ ] How calculations read field values
- [ ] Mathematical operations on serialized data
- [ ] Real-time calculations
- [ ] Stored calculation results

#### 16.3 Variable Fields
- [ ] Fields that reference other field values
- [ ] Dynamic field population
- [ ] Chained field dependencies

---

### Phase 17: Entry Autosave & Drafts

#### 17.1 Form Progress Saving
- [ ] Is there an autosave feature?
- [ ] How partial entries are stored
- [ ] Draft entry format
- [ ] Resume functionality

#### 17.2 Entry Preview
- [ ] Can users preview before submitting?
- [ ] How preview data is stored temporarily
- [ ] Preview to final submission conversion

---

### Phase 18: Entry Relationships & Linking

#### 18.1 Parent/Child Entries
- [ ] Can entries have parent entries?
- [ ] Entry hierarchy
- [ ] Related entry queries

#### 18.2 Entry to Post Relationships
- [ ] Entries linked to WooCommerce orders
- [ ] Entries linked to posts (Front-end Posting)
- [ ] Entries linked to users
- [ ] Bidirectional relationship queries

#### 18.3 Entry to Entry Relationships
- [ ] Can entries reference other entries?
- [ ] Entry duplication relationships
- [ ] Entry merge relationships

---

### Phase 19: Spam Protection & Validation

#### 19.1 Spam Detection Systems
- [ ] Akismet integration
- [ ] Honeypot fields
- [ ] Spam filtering rules
- [ ] How entry data is analyzed for spam

#### 19.2 Duplicate Entry Detection
- [ ] How duplicates are detected
- [ ] Field comparison logic
- [ ] Hash generation from entry data
- [ ] Unique constraint checking

#### 19.3 Custom Validation Rules
- [ ] Server-side validation
- [ ] Field value validation
- [ ] Cross-field validation

---

### Phase 20: WordPress Multisite

#### 20.1 Multisite Architecture
- [ ] Does Super Forms support multisite?
- [ ] Network-wide forms
- [ ] Site-specific entries
- [ ] Cross-site entry queries
- [ ] Database table structure in multisite

#### 20.2 Multisite Migration
- [ ] Migrate all sites or per-site?
- [ ] Network admin controls
- [ ] Site admin controls

---

### Phase 21: Custom User Code & Extensibility

#### 21.1 User-Added SQL Queries
- [ ] Custom SQL via `functions.php`
- [ ] Theme-specific queries
- [ ] Custom plugin integrations
- [ ] Raw SQL in hooks

#### 21.2 Developer Hooks & Filters
- [ ] All hooks that pass entry data
- [ ] Filter parameters that include serialized data
- [ ] Action hooks triggered on entry events
- [ ] How third-party code might access data

#### 21.3 Template Overrides
- [ ] Can users override entry display templates?
- [ ] Custom entry rendering
- [ ] Template data expectations

---

### Phase 22: Entry Display & Shortcodes

#### 22.1 Entry Display Shortcodes
- [ ] Are there shortcodes to display entries?
- [ ] `[super_entry]` or similar
- [ ] Field value shortcodes
- [ ] Entry list shortcodes

#### 22.2 Frontend Entry Display
- [ ] Public entry pages
- [ ] Entry detail views
- [ ] Entry cards/widgets
- [ ] Entry search interfaces

#### 22.3 Admin Entry Display
- [ ] Entry detail page in admin
- [ ] Quick view popups
- [ ] Entry comparison views

---

### Phase 23: Scheduled Tasks & Maintenance

#### 23.1 WP-Cron Jobs
- [ ] Scheduled entry cleanup
- [ ] Automatic entry status updates
- [ ] Scheduled exports
- [ ] Entry expiration handling

#### 23.2 Database Maintenance
- [ ] Orphaned entry cleanup
- [ ] Database optimization routines
- [ ] Entry statistics generation
- [ ] Cache warming jobs

#### 23.3 Background Processes
- [ ] Long-running entry operations
- [ ] Bulk entry updates
- [ ] Mass status changes

---

### Phase 24: Entry Revision History

#### 24.1 Version Tracking
- [ ] Does Super Forms track entry edits?
- [ ] Revision storage format
- [ ] Revision comparison
- [ ] Revision restoration

#### 24.2 Audit Logs
- [ ] Entry change logging
- [ ] Who edited what when
- [ ] Field-level change tracking

---

### Phase 25: Performance & Monitoring

#### 25.1 Query Monitoring
- [ ] Query Monitor plugin compatibility
- [ ] Slow query identification
- [ ] N+1 query detection

#### 25.2 Performance Metrics
- [ ] Baseline query times (before migration)
- [ ] Memory usage patterns
- [ ] Disk I/O patterns
- [ ] Index effectiveness

#### 25.3 Error Logging
- [ ] PHP error logs
- [ ] WordPress debug logs
- [ ] Custom error tracking

---

### Phase 26: Security & Permissions

#### 26.1 Entry Access Control
- [ ] Who can view entries
- [ ] Who can edit entries
- [ ] Who can delete entries
- [ ] Role-based access to fields

#### 26.2 Field-Level Security
- [ ] Sensitive field protection
- [ ] Encrypted field storage
- [ ] PCI compliance considerations
- [ ] Field masking in admin

#### 26.3 Entry Locking
- [ ] Concurrent edit prevention
- [ ] Entry lock timeout
- [ ] Lock override capabilities

---

### Phase 27: Analytics & Reporting

#### 27.1 Entry Analytics
- [ ] Dashboard widgets showing entry stats
- [ ] Form submission trends
- [ ] Field value analytics
- [ ] Conversion tracking

#### 27.2 Custom Reports
- [ ] Report generation from entries
- [ ] Aggregate queries
- [ ] Field value grouping
- [ ] Time-based analysis

#### 27.3 Export to Analytics Tools
- [ ] Google Analytics integration
- [ ] Custom tracking pixels
- [ ] Data layer population

---

### Phase 28: Search Functionality

#### 28.1 WordPress Search
- [ ] Are entries in WordPress search?
- [ ] Search relevance
- [ ] Search result display

#### 28.2 Custom Entry Search
- [ ] Admin entry search
- [ ] Frontend entry search
- [ ] Full-text search implementation
- [ ] Search index structure

#### 28.3 Advanced Filtering
- [ ] Faceted search
- [ ] Multiple criteria search
- [ ] Range queries
- [ ] Date range filtering

---

### Phase 29: API & Webhooks (Deep Dive)

#### 29.1 REST API Endpoints
- [ ] Complete endpoint inventory
- [ ] Request/response schemas
- [ ] Authentication requirements
- [ ] API version management

#### 29.2 Outbound Webhooks
- [ ] Webhook payload structure
- [ ] Retry logic
- [ ] Failure handling
- [ ] Webhook logs

#### 29.3 Inbound Webhooks
- [ ] Payment processor webhooks
- [ ] Third-party service webhooks
- [ ] Data synchronization webhooks

---

### Phase 30: Testing Infrastructure

#### 30.1 Existing Tests
- [ ] Are there any PHPUnit tests?
- [ ] JavaScript tests?
- [ ] Integration tests?
- [ ] What do they test?

#### 30.2 Test Data
- [ ] How to generate test entries
- [ ] Fixture data format
- [ ] Test database setup

#### 30.3 Test Coverage Gaps
- [ ] What's not currently tested
- [ ] High-risk areas without tests

---

## Research Deliverables

This research task will produce:

1. **Complete Dependency Map** - Every file/function that touches entry data
2. **Migration Strategy Document** - Detailed implementation plan with chosen approach
3. **Risk Assessment Report** - All identified risks + mitigations
4. **UI/UX Mockups** - Migration interface designs (blocking vs non-blocking)
5. **Performance Baseline** - Current query benchmarks for comparison
6. **Test Plan** - Comprehensive testing strategy covering all edge cases
7. **Implementation Subtasks** - Ready-to-execute subtask breakdown for `h-implement-eav-migration/`
8. **User Custom Code Migration Guide** - Documentation for users with custom queries
9. **API Stability Guarantee Document** - What won't break for API consumers
10. **Multisite Migration Strategy** - If applicable
11. **GDPR Compliance Verification** - Ensuring privacy tools still work
12. **Cache Invalidation Plan** - How to clear all cached serialized data
13. **Security Audit Report** - Permission/access control verification
14. **Search Reindexing Plan** - If search indexes need rebuilding

## User Notes

**Critical realizations from analysis:**
- Conditional Logic could be a showstopper if it directly parses serialized strings
- User Custom SQL in `functions.php` is impossible to automatically migrate
- GDPR tools are legally critical - must work perfectly
- Caching could cause "ghost data" issues if not cleared properly
- Multisite could multiply migration complexity by 10x or more
- API consumers (mobile apps, integrations) could break silently

**Migration approach considerations:**
- Blocking migration ensures no data inconsistencies during transition
- Non-blocking migration allows users to continue working but more complex
- Dual-write + dual-read system provides safety net during transition
- Must always preserve original serialized data (never delete)

## Work Log
- [2025-10-30] Task created with comprehensive 30-phase investigation plan
