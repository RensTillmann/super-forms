---
name: h-test-eav-migration-real-data
branch: feature/test-eav-migration-real-data
status: pending
created: 2025-11-02
---

# Test EAV Migration with Real Production Data

## Problem/Goal

The new EAV (Entity-Attribute-Value) migration system has been built and tested with synthetic data, but needs validation with real production data before deployment. We need to:

1. **Enable real data testing** - Add import functionality to Developer Tools page so we can test with actual contact entries from production sites
2. **Support multiple formats** - Handle both WordPress XML exports and Super Forms CSV exports
3. **Validate the entire system** - Prove that migration, verification, and performance improvements work with real-world data patterns
4. **Discover edge cases** - Find and document any issues that synthetic data didn't reveal
5. **Build confidence** - Demonstrate the system is ready for production migration

This task enhances the Developer Tools page (Section 1: Test Data Generator) with import capabilities, creating a complete testing workflow: Import/Generate → Migrate → Verify → Benchmark → Cleanup.

## Success Criteria

**Infrastructure & Setup:**
- [ ] WP-CLI is verified/installed and operational on dev server
- [ ] SSH database access is established and documented

**Import Functionality:**
- [ ] Developer Tools Section 1 has tabbed UI (Generate tab / Import tab)
- [ ] WordPress XML upload and import works with progress tracking
- [ ] Super Forms CSV upload and import works with progress tracking
- [ ] Import statistics are displayed after each upload (entries, forms, fields, size, errors)
- [ ] "Tag as test entries" option works (adds `_super_test_entry` meta)
- [ ] "Auto-migrate after import" option works

**Testing & Validation:**
- [ ] Can import 100-1000 real entries from your production CSV export
- [ ] All 9 verification tests pass on imported real data
- [ ] Performance benchmarks show expected improvements (10-100x) with real data
- [ ] Complete workflow works: Import → Migrate → Verify → Benchmark → Cleanup

**Comparison & Documentation:**
- [ ] XML vs CSV import comparison completed (speed, accuracy, edge cases)
- [ ] Any edge cases discovered with real data are documented
- [ ] Import feature is documented in Developer Tools section

**Production Readiness:**
- [ ] System proven ready for production migration with confidence
- [ ] Migration strategy validated with real-world data patterns

## Context Manifest

### How Import/Export Currently Works in Super Forms

**CSV Export Flow (Existing Functionality)**:
When users export contact entries from Super Forms, the system:

1. **Query Phase**: Retrieves entry IDs and basic post data from `wp_posts` where `post_type = 'super_contact_entry'`
2. **Data Fetching**: Uses `SUPER_Data_Access::get_bulk_entry_data($entry_ids)` to fetch all field data in a single optimized operation
   - If migration is completed and using EAV: Single JOIN query on `wp_superforms_entry_data` table
   - If using serialized: Individual `get_post_meta($entry_id, '_super_contact_entry_data', true)` calls
3. **Column Building**: Dynamically discovers all unique field names across entries to build CSV columns
4. **Row Generation**: Iterates through entries, mapping field values to columns (empty string for missing fields)
5. **File Generation**: Creates CSV with UTF-8 BOM for Excel compatibility: `\xef\xbb\xbf`
6. **Output**: Saves to uploads directory as `super-contact-entries-{timestamp}.csv`

**CSV Import Flow (Existing Functionality)**:
Located in `SUPER_Ajax::import_contact_entries()` at line 1690:

1. **File Upload**: Uses WordPress media upload (`$_POST['file_id']` → `get_attached_file($file_id)`)
2. **CSV Parsing**: Opens file with `fopen()` and reads with `fgetcsv($handle, 0, $delimiter, $enclosure)`
   - Handles UTF-8 BOM: Checks first 3 bytes for `\xef\xbb\xbf`, rewinds if not found
   - Default delimiter: `,` (customizable via `$_POST['import_delimiter']`)
   - Default enclosure: `"` (customizable via `$_POST['import_enclosure']`)
3. **Column Mapping**: Uses `prepare_contact_entry_import()` to map CSV columns to form fields via `$_POST['column_connections']`
4. **Entry Creation**: For each CSV row:
   - Creates `wp_posts` entry with `post_type = 'super_contact_entry'`
   - Sets `post_parent` to form ID
   - Saves field data via migration-aware `SUPER_Data_Access::save_entry_data($entry_id, $data)`
5. **Data Format**: Entry data is array of field objects:
   ```php
   $data[$field_name] = array(
       'name' => $field_name,
       'value' => $field_value,
       'type' => $field_type,
       'label' => $field_label
   );
   ```

**WordPress XML Import**:
No existing WordPress XML import functionality was found in the codebase. WordPress core provides `WP_Import` class via the WordPress Importer plugin for importing WXR (WordPress eXtended RSS) format, but Super Forms doesn't currently use it.

### Developer Tools Page Architecture

**Page Structure** (`/src/includes/admin/views/page-developer-tools.php`):
The page is organized into 7 sections:
1. **Test Data Generator** (lines 95-165) - Current synthetic data generation
2. **Migration Controls** (lines 167-279) - EAV migration controls
3. **Automated Verification** (lines 281-381) - 9 data integrity tests
4. **Performance Benchmarks** (lines 383-519) - 3 benchmark tests
5. **Database Inspector** (lines 520-599) - EAV stats and queries
6. **Database Utilities** (lines 600-613) - Maintenance operations
7. **Developer Utilities** (lines 615-673) - Logs, exports, SQL queries

**Current Section 1 UI** (Test Data Generator):
- **Entry Count**: Radio buttons (10, 100, 1000, 10000, custom)
- **Data Complexity**: 7 checkboxes for different data types
- **Form Assignment**: Dropdown of all forms
- **Date Distribution**: 3 radio options
- **Actions**: "Generate Test Entries" and "Delete All Test Entries" buttons
- **Progress Display**: Progress bar with batch updates
- **Log Area**: Real-time generation feedback

**Needed Enhancement**: Add tabbed interface with:
- **Tab 1: Generate** (existing functionality)
- **Tab 2: Import** (new - XML/CSV upload with progress)

### AJAX Handler Pattern in Developer Tools

**Registration Pattern** (in `SUPER_Ajax::init()` at line 28):
```php
$ajax_events = array(
    'dev_generate_entries'       => false, // @since 6.0.0
    'dev_delete_test_entries'    => false, // @since 6.0.0
    // Add: 'dev_import_xml'      => false,
    // Add: 'dev_import_csv'      => false,
);

foreach ($ajax_events as $ajax_event => $nopriv) {
    add_action('wp_ajax_super_' . $ajax_event, array(__CLASS__, $ajax_event));
    if ($nopriv) {
        add_action('wp_ajax_nopriv_super_' . $ajax_event, array(__CLASS__, $ajax_event));
    }
}
```

**Existing Handler Example** (`dev_generate_entries` at line 6520):
```php
public static function dev_generate_entries() {
    check_ajax_referer('super-form-builder', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Permission denied'));
    }

    $result = SUPER_Developer_Tools::generate_test_entries(array(
        'count' => absint($_POST['count']),
        'form_id' => absint($_POST['form_id']),
        'date_mode' => sanitize_text_field($_POST['date_mode']),
        'complexity' => $_POST['complexity'],
        'batch_offset' => absint($_POST['batch_offset'])
    ));

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success($result);
}
```

**Frontend AJAX Pattern** (page-developer-tools.php JavaScript at line 1180):
```javascript
$.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'super_dev_generate_entries',
        security: devtoolsNonce,
        count: thisBatch,
        form_id: formId,
        date_mode: dateMode,
        complexity: complexity,
        batch_offset: totalGenerated
    },
    success: function(response) {
        if (response.success) {
            // Update UI with response.data
        } else {
            // Show error: response.data.message
        }
    }
});
```

### Data Access Layer Integration

**Migration-Aware Data Saving** (`SUPER_Data_Access::save_entry_data()`):
The Data Access Layer automatically handles dual-write during migration:

**Phase 1** (Before Migration): Writes ONLY to serialized (`_super_contact_entry_data` postmeta)
**Phase 2** (During Migration): Writes to BOTH serialized AND EAV tables
**Phase 3** (After Migration - Rolled Back): Writes ONLY to serialized
**Phase 4** (After Migration - EAV Active): Writes ONLY to EAV tables

**Critical Pattern for Import**:
```php
// Create WordPress post
$entry_id = wp_insert_post(array(
    'post_type' => 'super_contact_entry',
    'post_status' => 'super_unread',
    'post_parent' => $form_id,
    'post_title' => 'Entry Title',
    'post_date' => $date,
    'post_date_gmt' => get_gmt_from_date($date)
));

// Save data via Data Access Layer (migration-aware!)
$result = SUPER_Data_Access::save_entry_data($entry_id, $entry_data);

// Tag as test entry (CRITICAL for safe deletion)
add_post_meta($entry_id, '_super_test_entry', true);
```

**Entry Data Format**:
```php
$entry_data = array(
    'field_name' => array(
        'name' => 'field_name',      // Required
        'value' => 'field_value',    // Required (can be array for checkboxes/files)
        'type' => 'text',            // Optional
        'label' => 'Field Label'     // Optional
    ),
    // ... more fields
);
```

### EAV Database Schema

**Table Structure** (`wp_superforms_entry_data`):
```sql
CREATE TABLE wp_superforms_entry_data (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    entry_id BIGINT(20) UNSIGNED NOT NULL,      -- FK to wp_posts.ID
    field_name VARCHAR(255) NOT NULL,            -- Form field name
    field_value LONGTEXT,                        -- Field value (JSON for arrays)
    field_type VARCHAR(50),                      -- text, email, checkbox, etc.
    field_label VARCHAR(255),                    -- Human-readable label
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY entry_id (entry_id),                     -- Query by entry
    KEY field_name (field_name),                 -- Query by field
    KEY entry_field (entry_id, field_name),      -- Composite lookup
    KEY field_value (field_value(191))           -- Search/filter (prefix index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Array/Complex Data Handling**:
- Checkbox arrays stored as JSON in `field_value`: `["option1","option2"]`
- File uploads stored as JSON array of objects: `[{"value":"file.pdf","url":"https://...","attachment":123}]`

### File Upload Handling in WordPress

**WordPress Media Upload Pattern**:
Super Forms uses WordPress's built-in file handling:

1. **Upload**: `wp_handle_upload($_FILES['file'], array('test_form' => false))`
2. **Attachment Creation**: `wp_insert_attachment($attachment, $file['file'])`
3. **File Reference**: Store attachment ID, retrieve with `get_attached_file($attachment_id)`

**Import Pattern**:
```php
$file_id = absint($_POST['file_id']);  // Uploaded via media library
$file_path = get_attached_file($file_id);
if ($file_path && file_exists($file_path)) {
    $handle = fopen($file_path, 'r');
    // Parse file...
    fclose($handle);
}
```

### Migration Manager Integration

**Migration Status** (`get_option('superforms_eav_migration')`):
```php
array(
    'status' => 'not_started|in_progress|completed',
    'using_storage' => 'serialized|eav',
    'total_entries' => 0,
    'migrated_entries' => 0,
    'failed_entries' => array(),
    'started_at' => '',
    'completed_at' => '',
    'last_processed_id' => 0,
    'verification_passed' => false,
    'rollback_available' => true
)
```

**Integration Points for Auto-Migrate Option**:
If "Auto-migrate after import" is checked:
1. Import entries (tagged with `_super_test_entry`)
2. Call `SUPER_Migration_Manager::start_migration()` (resets counters)
3. Poll `SUPER_Migration_Manager::process_batch($batch_size)` until complete
4. Display migration progress in import UI

### Test Entry Tagging System

**Tagging Mechanism**:
```php
add_post_meta($entry_id, '_super_test_entry', true);
```

**Safe Deletion** (`SUPER_Developer_Tools::delete_test_entries()`):
```php
// Find ONLY test entries
$test_ids = $wpdb->get_col("
    SELECT post_id
    FROM {$wpdb->postmeta}
    WHERE meta_key = '_super_test_entry'
    AND meta_value = '1'
");

// Delete from both storage systems
foreach ($test_ids as $entry_id) {
    $wpdb->delete($wpdb->prefix . 'superforms_entry_data', array('entry_id' => $entry_id));
    delete_post_meta($entry_id, '_super_contact_entry_data');
    delete_post_meta($entry_id, '_super_test_entry');
    wp_delete_post($entry_id, true);
}
```

**Critical for Import**: ALWAYS tag imported entries to enable safe cleanup without affecting production data.

### Progress Tracking Pattern

**Batch Processing with Progress Updates**:
Used throughout Developer Tools for long-running operations:

**Backend Pattern**:
```php
public static function import_csv_batch($args) {
    $batch_size = 50;
    $offset = isset($args['offset']) ? $args['offset'] : 0;

    // Process batch...
    $processed = 0;

    return array(
        'success' => true,
        'processed' => $processed,
        'total_processed' => $offset + $processed,
        'is_complete' => ($processed < $batch_size),
        'statistics' => array(
            'imported' => $processed,
            'failed' => 0,
            'errors' => array()
        )
    );
}
```

**Frontend Pattern**:
```javascript
var totalToImport = 1000;
var totalImported = 0;
var batchSize = 50;

function importNextBatch() {
    var remaining = totalToImport - totalImported;
    var thisBatch = Math.min(batchSize, remaining);

    if (thisBatch <= 0) {
        // Complete!
        showImportComplete();
        return;
    }

    $.ajax({
        url: ajaxurl,
        data: {
            action: 'super_dev_import_csv_batch',
            offset: totalImported,
            batch_size: thisBatch
        },
        success: function(response) {
            if (response.success) {
                totalImported += response.data.processed;
                updateProgressBar(totalImported / totalToImport * 100);

                // Continue with next batch
                setTimeout(importNextBatch, 500);
            }
        }
    });
}
```

### CSV Format Specifics

**Super Forms CSV Export Format**:
```csv
entry_id,entry_title,entry_date,entry_author,entry_status,first_name,last_name,email,phone
123,"Entry Title","2025-01-15 10:30:00",1,"super_unread","John","Doe","john@test.com","+1-555-1234"
```

**Columns**:
- **Fixed Columns**: `entry_id`, `entry_title`, `entry_date`, `entry_author`, `entry_status`, `entry_ip`, `entry_wc_order_id`, `entry_custom_status`
- **Dynamic Columns**: All form field names discovered across entries
- **Encoding**: UTF-8 with BOM (`\xef\xbb\xbf` at file start)
- **Delimiter**: Comma (`,`) by default
- **Enclosure**: Double quote (`"`)
- **Special Values**: Arrays as JSON strings, Files as newline-separated URLs

**Import Mapping**:
- `entry_id` → Skip (auto-generated on import)
- `entry_title` → `post_title`
- `entry_date` → `post_date`
- `entry_author` → `post_author`
- `entry_status` → `post_status`
- Other columns → Field data in `$entry_data[$field_name]` array

### WordPress XML (WXR) Format

**Standard WXR Structure**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:wp="http://wordpress.org/export/1.2/">
  <channel>
    <item>
      <title>Entry Title</title>
      <wp:post_id>123</wp:post_id>
      <wp:post_date>2025-01-15 10:30:00</wp:post_date>
      <wp:post_type>super_contact_entry</wp:post_type>
      <wp:post_parent>456</wp:post_parent>
      <wp:post_status>super_unread</wp:post_status>
      <wp:postmeta>
        <wp:meta_key>_super_contact_entry_data</wp:meta_key>
        <wp:meta_value><![CDATA[a:2:{s:5:"email";a:4:{...}}]]></wp:meta_value>
      </wp:postmeta>
      <wp:postmeta>
        <wp:meta_key>_super_test_entry</wp:meta_key>
        <wp:meta_value><![CDATA[1]]></wp:meta_value>
      </wp:postmeta>
    </item>
  </channel>
</rss>
```

**Parsing Approach**:
Use WordPress core's `WP_Import` class (if available) or SimpleXML:
```php
$xml = simplexml_load_file($file_path);
foreach ($xml->channel->item as $item) {
    $post_type = (string)$item->children('wp', true)->post_type;
    if ($post_type !== 'super_contact_entry') continue;

    // Extract post data
    // Extract postmeta
    // Create entry via SUPER_Data_Access
}
```

### Technical Reference Details

#### File Locations
- **Developer Tools Page**: `/src/includes/admin/views/page-developer-tools.php`
- **Developer Tools Class**: `/src/includes/class-developer-tools.php`
- **AJAX Handler**: `/src/includes/class-ajax.php`
- **Data Access Layer**: `/src/includes/class-data-access.php`
- **Migration Manager**: `/src/includes/class-migration-manager.php`

#### Key Function Signatures

**Data Access**:
```php
SUPER_Data_Access::save_entry_data(int $entry_id, array $data): bool|WP_Error
SUPER_Data_Access::get_entry_data(int $entry_id): array|WP_Error
SUPER_Data_Access::get_bulk_entry_data(array $entry_ids): array
```

**Developer Tools**:
```php
SUPER_Developer_Tools::generate_test_entries(array $args): array
SUPER_Developer_Tools::delete_test_entries(): array
SUPER_Developer_Tools::get_test_entry_count(): int
```

**Migration Manager**:
```php
SUPER_Migration_Manager::start_migration(): array|WP_Error
SUPER_Migration_Manager::process_batch(int $batch_size): array|WP_Error
SUPER_Migration_Manager::get_migration_status(): array
```

#### AJAX Actions to Implement

**For CSV Import**:
```php
add_action('wp_ajax_super_dev_import_csv', 'SUPER_Ajax::dev_import_csv');
add_action('wp_ajax_super_dev_import_csv_batch', 'SUPER_Ajax::dev_import_csv_batch');
```

**For XML Import**:
```php
add_action('wp_ajax_super_dev_import_xml', 'SUPER_Ajax::dev_import_xml');
add_action('wp_ajax_super_dev_import_xml_batch', 'SUPER_Ajax::dev_import_xml_batch');
```

#### Configuration Requirements

**WordPress Constants**:
- `DEBUG_SF` must be `true` in `wp-config.php` to access Developer Tools page

**WP-CLI Requirements**:
- WP-CLI installed on server: `which wp` should return path
- Server SSH access for verification
- Database credentials accessible via WP-CLI

**PHP Requirements**:
- `fopen()`, `fgetcsv()` for CSV parsing
- `simplexml_load_file()` or `XMLReader` for XML parsing
- Memory limit sufficient for large imports (recommend 256M+)
- Max execution time increased for batch processing

#### UI Components Needed

**Tabbed Interface**:
```html
<div class="devtools-tabs">
  <button class="devtools-tab active" data-tab="generate">Generate</button>
  <button class="devtools-tab" data-tab="import">Import</button>
</div>
<div class="devtools-tab-content active" id="tab-generate">
  <!-- Existing generator UI -->
</div>
<div class="devtools-tab-content" id="tab-import">
  <!-- New import UI -->
</div>
```

**Import UI Components**:
- File upload dropzone or input
- Format selector (WordPress XML / Super Forms CSV)
- Options: "Tag as test entries" checkbox (default checked)
- Options: "Auto-migrate after import" checkbox
- Progress bar with statistics
- Results display: Entries imported, Forms affected, Fields discovered, Errors
- Action buttons: "Clear Uploaded File", "Delete Imported Entries"

#### Error Handling Patterns

**Backend Validation**:
```php
// File validation
if (!$file_path || !file_exists($file_path)) {
    return new WP_Error('file_not_found', 'Uploaded file not found');
}

// Format validation
if (!in_array($file_ext, array('csv', 'xml'))) {
    return new WP_Error('invalid_format', 'Only CSV and XML files supported');
}

// Capability check
if (!current_user_can('manage_options')) {
    return new WP_Error('permission_denied', 'Insufficient permissions');
}
```

**Frontend Error Display**:
```javascript
if (!response.success) {
    showError(response.data.message || 'Unknown error occurred');
    enableImportButton();
}
```

### Implementation Strategy

**Phase 1: Backend Foundation**
1. Add `dev_import_csv` and `dev_import_csv_batch` methods to `SUPER_Ajax`
2. Add `import_csv_entries()` method to `SUPER_Developer_Tools`
3. Add XML parsing methods if XML support included

**Phase 2: Frontend UI**
1. Add tabbed interface to Section 1
2. Add file upload handling with progress tracking
3. Add statistics display after import

**Phase 3: Migration Integration**
1. Implement "Auto-migrate after import" option
2. Wire up progress tracking for combined import+migrate workflow

**Phase 4: Testing & Documentation**
1. Test with real production CSV exports
2. Test with WordPress XML exports containing contact entries
3. Document import feature in Developer Tools section
4. Compare import speeds and accuracy between formats

## User Notes
<!-- Any specific notes or requirements from the developer -->

## Work Log
<!-- Updated as work progresses -->
