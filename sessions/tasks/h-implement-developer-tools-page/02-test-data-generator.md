---
name: 02-test-data-generator
parent: h-implement-developer-tools-page
status: completed
created: 2025-11-02
completed: 2025-11-05
---

# Phase 2: Test Data Generator

## Goal

Enable generation of synthetic contact entries with configurable complexity patterns for testing the EAV migration system. Test entries will be tagged for safe deletion and support various data types to validate migration integrity.

## Success Criteria

- [ ] Generate 10/100/1,000/10,000 test entries successfully
- [ ] Support all 7 data complexity patterns (basic text, UTF-8, long text, numeric, empty, arrays, files)
- [ ] Entries tagged with `_super_test_entry = true` meta flag
- [ ] Date distribution works (all today, random 30 days, random year)
- [ ] Form assignment links entries to selected form
- [ ] Progress bar updates during generation
- [ ] Log area shows timestamp and progress messages
- [ ] Delete test entries function only removes tagged entries
- [ ] Batch processing prevents timeouts for 1,000+ entries
- [ ] Data Access Layer dual-write works during migration

## Implementation Requirements

### Files to Create

1. **`/src/includes/class-developer-tools.php`** - Generator logic class (~400 lines)

### Files to Modify

1. **`/src/includes/class-ajax.php`** - Add AJAX handlers:
   - `dev_generate_entries` - Create test entries in batches
   - `dev_delete_test_entries` - Delete tagged entries
   - Register in init() array

2. **`/src/includes/admin/views/page-developer-tools.php`** - Add UI section for Test Data Generator

## Technical Specifications

### Entry Data Structure

Contact entries in Super Forms use this structure (from class-ajax.php line 4622):

```php
$entry_data = array(
    'first_name' => array(
        'name'  => 'first_name',
        'value' => 'John',
        'type'  => 'text',
        'label' => 'First Name'
    ),
    'email' => array(
        'name'  => 'email',
        'value' => 'john@test.com',
        'type'  => 'email',
        'label' => 'Email Address'
    ),
    // ... more fields
);
```

### The 7 Data Complexity Patterns

**1. Basic Text Fields**
```php
'first_name' => array('name' => 'first_name', 'value' => 'John', 'type' => 'text', 'label' => 'First Name'),
'last_name' => array('name' => 'last_name', 'value' => 'Doe', 'type' => 'text', 'label' => 'Last Name'),
'email' => array('name' => 'email', 'value' => 'john.doe@test.com', 'type' => 'email', 'label' => 'Email'),
'phone' => array('name' => 'phone', 'value' => '+1-555-0100', 'type' => 'text', 'label' => 'Phone'),
```

**2. Special Characters (UTF-8)**
```php
'name_utf8' => array(
    'name' => 'name_utf8',
    'value' => 'José Müller 中文名字 مرحبا 🚀 ®™',  // Actual UTF-8 characters
    'type' => 'text',
    'label' => 'International Name'
),
```

**3. Long Text (>10KB)**
```php
'description' => array(
    'name' => 'description',
    'value' => str_repeat("Lorem ipsum dolor sit amet, consectetur adipiscing elit. ", 200),  // ~11KB
    'type' => 'textarea',
    'label' => 'Description'
),
```

**4. Numeric Values (stored as strings)**
```php
'age' => array('name' => 'age', 'value' => '25', 'type' => 'number', 'label' => 'Age'),
'price' => array('name' => 'price', 'value' => '199.99', 'type' => 'currency', 'label' => 'Price'),
'temperature' => array('name' => 'temperature', 'value' => '-42.5', 'type' => 'number', 'label' => 'Temperature'),
```

**5. Empty/Null Values**
```php
'optional_field' => array('name' => 'optional_field', 'value' => '', 'type' => 'text', 'label' => 'Optional'),
'zero_value' => array('name' => 'zero_value', 'value' => '0', 'type' => 'number', 'label' => 'Zero'),
```

**6. Checkbox Arrays (multi-select - JSON in EAV)**
```php
'interests' => array(
    'name' => 'interests',
    'value' => array('sports', 'music', 'reading', 'travel'),  // Will be JSON-encoded in EAV
    'type' => 'checkbox',
    'label' => 'Interests'
),
```

**7. File Upload URLs**
```php
'resume' => array(
    'name' => 'resume',
    'value' => array(array(
        'value' => 'resume.pdf',
        'url' => 'https://example.com/uploads/resume.pdf',
        'attachment' => 123
    )),
    'type' => 'file',
    'label' => 'Resume'
),
```

### Entry Creation Process

```php
public static function generate_test_entries($args) {
    $defaults = array(
        'count' => 10,
        'form_id' => 0,
        'date_mode' => 'today',
        'complexity' => array('basic_text'),
        'batch_offset' => 0
    );
    $args = wp_parse_args($args, $defaults);

    $generated = 0;
    $failed = 0;
    $errors = array();

    for ($i = 0; $i < $args['count']; $i++) {
        try {
            // 1. Generate entry data based on complexity options
            $entry_data = self::generate_entry_data($args['complexity'], $args['batch_offset'] + $i);

            // 2. Generate post date based on date mode
            $post_date = self::generate_post_date($args['date_mode']);

            // 3. Create WordPress post
            $entry_id = wp_insert_post(array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $args['form_id'],
                'post_title' => 'Test Entry ' . ($args['batch_offset'] + $i + 1),
                'post_date' => $post_date,
                'post_date_gmt' => get_gmt_from_date($post_date)
            ));

            if (is_wp_error($entry_id)) {
                throw new Exception($entry_id->get_error_message());
            }

            // 4. Save data via Data Access Layer (migration-aware!)
            $result = SUPER_Data_Access::save_entry_data($entry_id, $entry_data);

            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            // 5. Tag as test entry (CRITICAL for safe deletion)
            add_post_meta($entry_id, '_super_test_entry', true);

            $generated++;

        } catch (Exception $e) {
            $failed++;
            $errors[] = $e->getMessage();
        }
    }

    return array(
        'generated' => $generated,
        'failed' => $failed,
        'errors' => $errors,
        'total_offset' => $args['batch_offset'] + $generated
    );
}
```

### Date Distribution Implementation

```php
private static function generate_post_date($mode) {
    switch ($mode) {
        case 'today':
            return current_time('mysql');

        case 'random_30_days':
            $days = rand(0, 30);
            $hours = rand(0, 23);
            $minutes = rand(0, 59);
            return date('Y-m-d H:i:s', strtotime("-{$days} days -{$hours} hours -{$minutes} minutes"));

        case 'random_year':
            $days = rand(0, 365);
            $hours = rand(0, 23);
            $minutes = rand(0, 59);
            return date('Y-m-d H:i:s', strtotime("-{$days} days -{$hours} hours -{$minutes} minutes"));

        default:
            return current_time('mysql');
    }
}
```

### Test Entry Tagging (Safety System)

**Tagging entries:**
```php
add_post_meta($entry_id, '_super_test_entry', true);
```

**Finding test entries:**
```php
global $wpdb;
$test_entry_ids = $wpdb->get_col("
    SELECT post_id
    FROM {$wpdb->postmeta}
    WHERE meta_key = '_super_test_entry'
    AND meta_value = '1'
");
```

**Safe deletion:**
```php
public static function delete_test_entries() {
    global $wpdb;

    // Find ONLY test entries
    $test_ids = $wpdb->get_col("
        SELECT post_id FROM {$wpdb->postmeta}
        WHERE meta_key = '_super_test_entry' AND meta_value = '1'
    ");

    $deleted = 0;
    foreach ($test_ids as $entry_id) {
        // Delete from EAV tables (if exists)
        SUPER_Data_Access::delete_entry_data($entry_id);

        // Delete WordPress post
        wp_delete_post($entry_id, true);  // true = force delete (skip trash)

        $deleted++;
    }

    return array('deleted' => $deleted);
}
```

### AJAX Handler Implementation

**Registration in class-ajax.php init():**
```php
'dev_generate_entries'     => false,  // Admin only
'dev_delete_test_entries'  => false,  // Admin only
```

**Handler method:**
```php
public static function dev_generate_entries() {
    // 1. Permission check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => esc_html__('Unauthorized', 'super-forms')));
    }

    // 2. Sanitize inputs
    $count = isset($_POST['count']) ? absint($_POST['count']) : 10;
    $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
    $date_mode = isset($_POST['date_mode']) ? sanitize_text_field($_POST['date_mode']) : 'today';
    $complexity = isset($_POST['complexity']) ? (array) $_POST['complexity'] : array('basic_text');
    $batch_offset = isset($_POST['batch_offset']) ? absint($_POST['batch_offset']) : 0;

    // 3. Validate
    if ($count > 100) {
        wp_send_json_error(array('message' => esc_html__('Max 100 entries per batch', 'super-forms')));
    }

    if ($count < 1) {
        wp_send_json_error(array('message' => esc_html__('Count must be at least 1', 'super-forms')));
    }

    // 4. Generate entries
    $result = SUPER_Developer_Tools::generate_test_entries(array(
        'count' => $count,
        'form_id' => $form_id,
        'date_mode' => $date_mode,
        'complexity' => $complexity,
        'batch_offset' => $batch_offset
    ));

    // 5. Send response
    if (isset($result['failed']) && $result['failed'] > 0) {
        wp_send_json_error(array(
            'message' => sprintf(__('Generated %d, failed %d', 'super-forms'), $result['generated'], $result['failed']),
            'data' => $result
        ));
    }

    wp_send_json_success($result);
}

public static function dev_delete_test_entries() {
    // 1. Permission check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => esc_html__('Unauthorized', 'super-forms')));
    }

    // 2. Delete test entries
    $result = SUPER_Developer_Tools::delete_test_entries();

    // 3. Send response
    wp_send_json_success($result);
}
```

### Batch Processing Pattern (JavaScript)

For generating 1,000+ entries without timeout:

```javascript
var totalToGenerate = 1000;
var totalGenerated = 0;
var batchSize = 50;  // Generate 50 at a time

function generateBatch() {
    var remaining = totalToGenerate - totalGenerated;
    var thisRatchet = Math.min(batchSize, remaining);

    if (thisRatchet <= 0) {
        // All done
        appendLog('✓ Generation complete: ' + totalGenerated + ' entries created');
        $('#generate-entries-btn').prop('disabled', false).text('Generate Test Entries');
        return;
    }

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'super_dev_generate_entries',
            security: devtoolsNonce,
            count: thisRatchet,
            form_id: $('#test-form-id').val(),
            date_mode: $('input[name="date_mode"]:checked').val(),
            complexity: getSelectedComplexity(),
            batch_offset: totalGenerated
        },
        success: function(response) {
            if (response.success) {
                totalGenerated += response.data.generated;
                updateProgress(totalGenerated, totalToGenerate);
                appendLog('Generated batch: ' + response.data.generated + ' entries (' + totalGenerated + ' / ' + totalToGenerate + ')');

                // Continue with next batch after short delay
                setTimeout(generateBatch, 500);
            } else {
                appendLog('✗ Error: ' + response.data.message);
                $('#generate-entries-btn').prop('disabled', false).text('Generate Test Entries');
            }
        },
        error: function() {
            appendLog('✗ AJAX error occurred');
            $('#generate-entries-btn').prop('disabled', false).text('Generate Test Entries');
        }
    });
}

function updateProgress(current, total) {
    var percent = (current / total) * 100;
    $('.super-devtools-progress-fill').css('width', percent + '%');
    $('.super-devtools-progress-text').text(current + ' / ' + total + ' (' + Math.round(percent) + '%)');
}

function appendLog(message) {
    var timestamp = new Date().toLocaleTimeString();
    $('.super-devtools-log').prepend(
        '<div class="super-devtools-log-entry">[' + timestamp + '] ' + message + '</div>'
    );
}
```

### UI Section HTML

Add to page-developer-tools.php:

```php
<!-- Test Data Generator Section -->
<div class="super-devtools-section">
    <h2><?php echo esc_html__('1. Test Data Generator', 'super-forms'); ?></h2>
    <p><?php echo esc_html__('Generate synthetic contact entries in old serialized format for migration testing.', 'super-forms'); ?></p>

    <!-- Entry Count -->
    <h3><?php echo esc_html__('Entry Count:', 'super-forms'); ?></h3>
    <p>
        <label><input type="radio" name="entry_count" value="10" checked> 10</label>
        <label><input type="radio" name="entry_count" value="100"> 100</label>
        <label><input type="radio" name="entry_count" value="1000"> 1,000</label>
        <label><input type="radio" name="entry_count" value="10000"> 10,000</label>
        <label><input type="radio" name="entry_count" value="custom"> Custom: <input type="number" id="custom-entry-count" min="1" max="100000" value="50" style="width: 100px;"></label>
    </p>

    <!-- Data Complexity -->
    <h3><?php echo esc_html__('Data Complexity:', 'super-forms'); ?></h3>
    <p>
        <label><input type="checkbox" name="complexity[]" value="basic_text" checked> Basic text (name, email, phone)</label><br>
        <label><input type="checkbox" name="complexity[]" value="special_chars"> Special characters (UTF-8: é, ñ, 中文, emoji: 🚀)</label><br>
        <label><input type="checkbox" name="complexity[]" value="long_text"> Long text fields (&gt;10KB lorem ipsum)</label><br>
        <label><input type="checkbox" name="complexity[]" value="numeric"> Numeric values (integers, decimals)</label><br>
        <label><input type="checkbox" name="complexity[]" value="empty"> Empty/null values</label><br>
        <label><input type="checkbox" name="complexity[]" value="arrays"> Checkbox arrays (multi-select)</label><br>
        <label><input type="checkbox" name="complexity[]" value="files"> File upload URLs</label>
    </p>

    <!-- Form Assignment -->
    <h3><?php echo esc_html__('Assign to Form:', 'super-forms'); ?></h3>
    <p>
        <select id="test-form-id">
            <option value="0"><?php echo esc_html__('None (Generic)', 'super-forms'); ?></option>
            <?php
            $forms = get_posts(array(
                'post_type' => 'super_form',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            foreach ($forms as $form) :
            ?>
                <option value="<?php echo esc_attr($form->ID); ?>">
                    <?php echo esc_html($form->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>

    <!-- Date Distribution -->
    <h3><?php echo esc_html__('Entry Dates:', 'super-forms'); ?></h3>
    <p>
        <label><input type="radio" name="date_mode" value="today" checked> All today</label><br>
        <label><input type="radio" name="date_mode" value="random_30_days"> Random past 30 days</label><br>
        <label><input type="radio" name="date_mode" value="random_year"> Random past year</label>
    </p>

    <!-- Actions -->
    <p>
        <button id="generate-entries-btn" class="button button-primary"><?php echo esc_html__('Generate Test Entries', 'super-forms'); ?></button>
        <button id="delete-test-entries-btn" class="button button-secondary"><?php echo esc_html__('Delete All Test Entries', 'super-forms'); ?></button>
    </p>

    <!-- Progress -->
    <div class="super-devtools-progress-bar" style="display: none;">
        <div class="super-devtools-progress-fill" style="width: 0%;"></div>
    </div>
    <span class="super-devtools-progress-text"></span>

    <!-- Log Area -->
    <div class="super-devtools-log"></div>
</div>
```

## Testing Requirements

1. **Basic Generation Test**
   - Generate 10 entries with basic text only
   - ✓ Verify in Contact Entries admin page
   - ✓ Check entries have test flag

2. **All Complexity Test**
   - Generate 100 entries with all 7 complexity options
   - ✓ Verify UTF-8 characters preserved
   - ✓ Verify arrays stored correctly
   - ✓ Verify long text not truncated

3. **Date Distribution Test**
   - Generate 50 entries with "random past 30 days"
   - ✓ Verify entry dates spread across time period
   - ✓ Check post_date and post_date_gmt values

4. **Safety Test**
   - Create one real entry manually
   - Generate 10 test entries
   - Delete test entries
   - ✓ Real entry still exists
   - ✓ Only test entries deleted

5. **Large Dataset Test**
   - Generate 1,000 entries
   - ✓ No PHP timeout errors
   - ✓ Progress bar updates smoothly
   - ✓ All entries created successfully

6. **Migration State Test**
   - Generate before migration starts
   - ✓ Data saved to serialized only
   - Start migration
   - Generate during migration
   - ✓ Data dual-written to both serialized and EAV
   - Complete migration
   - Generate after migration
   - ✓ Data saved to EAV only

7. **Form Assignment Test**
   - Generate entries with form_id = 0
   - ✓ Entries have no parent
   - Generate entries assigned to form
   - ✓ Entries have correct post_parent

## Estimated Time

**2-3 hours** for implementation and testing

## Dependencies

- Phase 1 must be complete (page foundation)
- SUPER_Data_Access class (already exists)
- SUPER_Migration_Manager class (already exists)

## Notes

- Test entries are marked with `_super_test_entry = true` meta
- Batch size of 50 prevents PHP timeouts
- 500ms delay between batches prevents server overload
- Data Access Layer automatically handles migration state
- All field values stored as strings (even numbers)
- Arrays JSON-encoded when stored in EAV tables
- Progress updates happen client-side for smooth UX
