---
name: 01-menu-registration-and-page-foundation
parent: h-implement-developer-tools-page
status: completed
created: 2025-11-02
completed: 2025-11-05
---

# Phase 1: Menu Registration and Page Foundation

## Goal

Establish the foundational infrastructure for the Developer Tools page by:
1. Adding conditional menu registration (only visible when DEBUG_SF = true)
2. Creating the page callback method
3. Building the basic page view structure with security checks and nonce

This phase creates the foundation that all other phases will build upon.

## Success Criteria

- [ ] Menu item appears in Super Forms admin menu only when DEBUG_SF = true
- [ ] Menu item does NOT appear when DEBUG_SF is false or undefined
- [ ] Page callback successfully includes the view file
- [ ] Page view has security check preventing direct access (ABSPATH)
- [ ] Page requires manage_options capability
- [ ] Nonce is created and available for AJAX requests
- [ ] Basic page structure follows WordPress admin patterns
- [ ] No PHP errors or warnings when accessing page

## Implementation Requirements

### Files to Modify

1. **`/src/includes/class-menu.php`** - Add conditional menu registration after Migration menu (after line 69)
2. **`/src/includes/class-pages.php`** - Add developer_tools() callback method

### Files to Create

1. **`/src/includes/admin/views/page-developer-tools.php`** - Main page view (~100 lines starter template)

## Technical Specifications

### Menu Registration Pattern

Location: `/src/includes/class-menu.php` after line 69 (after Migration menu)

```php
// Developer Tools (DEBUG mode only)
if (defined('DEBUG_SF') && DEBUG_SF === true) {
    add_submenu_page(
        'super_forms',                                      // Parent slug
        esc_html__('Developer Tools', 'super-forms'),       // Page title
        esc_html__('Developer Tools', 'super-forms'),       // Menu title
        'manage_options',                                   // Capability required
        'super_developer_tools',                            // Menu slug
        'SUPER_Pages::developer_tools'                      // Callback function
    );
}
```

### Page Callback Pattern

Location: `/src/includes/class-pages.php` after line 2478 (after migration() method)

```php
/**
 * Developer Tools page (DEBUG mode only)
 *
 * @since 6.0.0
 */
public static function developer_tools() {
    include_once SUPER_PLUGIN_DIR . '/includes/admin/views/page-developer-tools.php';
}
```

### Page View Security and Structure

Location: `/src/includes/admin/views/page-developer-tools.php` (new file)

```php
<?php
/**
 * Developer Tools Page (DEBUG mode only)
 *
 * @package Super Forms
 * @since   6.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Require admin capability
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have permission to access this page.', 'super-forms'));
}

// Create nonce for AJAX requests
$nonce = wp_create_nonce('super-form-builder');
?>

<div class="wrap super-developer-tools">
    <h1><?php echo esc_html__('Developer Tools', 'super-forms'); ?></h1>

    <!-- Intro Notice -->
    <div class="sfui-notice sfui-blue">
        <h3><?php echo esc_html__('About Developer Tools', 'super-forms'); ?></h3>
        <p><?php echo esc_html__('This page provides tools for testing the EAV migration system with synthetic data. Only visible when DEBUG_SF = true in wp-config.php.', 'super-forms'); ?></p>
    </div>

    <!-- Warning Notice -->
    <div class="sfui-notice sfui-yellow">
        <h3><?php echo esc_html__('Debug Mode Active', 'super-forms'); ?></h3>
        <p><?php echo esc_html__('This page is for development and testing only. Do not use in production.', 'super-forms'); ?></p>
    </div>

    <!-- Quick Actions Section (Placeholder) -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('Quick Actions', 'super-forms'); ?></h2>
        <p>
            <button class="button button-primary button-hero" disabled>
                <?php echo esc_html__('Full Test Cycle', 'super-forms'); ?>
            </button>
            <button class="button button-secondary" disabled>
                <?php echo esc_html__('Reset Everything', 'super-forms'); ?>
            </button>
        </p>
        <p class="description"><?php echo esc_html__('Quick actions will be implemented in later phases.', 'super-forms'); ?></p>
    </div>

    <!-- Test Data Generator Section (Placeholder) -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('1. Test Data Generator', 'super-forms'); ?></h2>
        <p class="description"><?php echo esc_html__('Generate synthetic contact entries for testing migration. Coming in Phase 2.', 'super-forms'); ?></p>
    </div>

    <!-- Migration Controls Section (Placeholder) -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('2. Migration Controls', 'super-forms'); ?></h2>
        <p class="description"><?php echo esc_html__('Control migration process with advanced options. Coming in Phase 3.', 'super-forms'); ?></p>
    </div>

    <!-- Automated Verification Section (Placeholder) -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('3. Automated Verification', 'super-forms'); ?></h2>
        <p class="description"><?php echo esc_html__('Run automated tests to verify data integrity. Coming in Phase 4.', 'super-forms'); ?></p>
    </div>

    <!-- Performance Benchmarks Section (Placeholder) -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('4. Performance Benchmarks', 'super-forms'); ?></h2>
        <p class="description"><?php echo esc_html__('Measure performance improvements. Coming in Phase 5.', 'super-forms'); ?></p>
    </div>

    <!-- Database Inspector Section (Placeholder) -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('5. Database Inspector', 'super-forms'); ?></h2>
        <p class="description"><?php echo esc_html__('View database statistics and state. Coming in Phase 6.', 'super-forms'); ?></p>
    </div>

    <!-- Developer Utilities Section (Placeholder) -->
    <div class="super-devtools-section">
        <h2><?php echo esc_html__('6. Developer Utilities', 'super-forms'); ?></h2>
        <p class="description"><?php echo esc_html__('Advanced utilities and SQL executor. Coming in Phase 6.', 'super-forms'); ?></p>
    </div>

    <!-- Hidden nonce for AJAX -->
    <input type="hidden" id="super-devtools-nonce" value="<?php echo esc_attr($nonce); ?>" />
</div>

<style>
.super-developer-tools {
    margin: 20px 20px 20px 0;
}

.super-devtools-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin: 20px 0;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.super-devtools-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.sfui-notice {
    padding: 15px;
    margin: 20px 0;
    border-left: 4px solid #0073aa;
    background: #fff;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.sfui-notice h3 {
    margin-top: 0;
    font-size: 14px;
}

.sfui-notice.sfui-blue {
    border-left-color: #0073aa;
}

.sfui-notice.sfui-yellow {
    border-left-color: #f0b429;
}

.sfui-notice ul {
    margin: 10px 0 10px 20px;
}

.sfui-notice ul li {
    margin: 5px 0;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Nonce available for future AJAX calls
    var devtoolsNonce = $('#super-devtools-nonce').val();

    console.log('Developer Tools page loaded. Nonce ready for AJAX.');

    // Future phases will add functionality here
});
</script>
```

## Testing Requirements

### Test Scenarios

1. **Test with DEBUG_SF = true**
   - Add to wp-config.php: `define('DEBUG_SF', true);`
   - Refresh WordPress admin
   - ✓ "Developer Tools" menu item should appear under Super Forms
   - ✓ Page should load without errors

2. **Test with DEBUG_SF = false**
   - Change wp-config.php: `define('DEBUG_SF', false);`
   - Refresh WordPress admin
   - ✓ "Developer Tools" menu item should NOT appear

3. **Test with DEBUG_SF undefined**
   - Remove or comment out DEBUG_SF in wp-config.php
   - Refresh WordPress admin
   - ✓ "Developer Tools" menu item should NOT appear

4. **Test non-admin user access**
   - Login as non-admin user
   - Try to access page directly: `/wp-admin/admin.php?page=super_developer_tools`
   - ✓ Should show permission error

5. **Test nonce availability**
   - View page source
   - Search for `super-devtools-nonce`
   - ✓ Hidden input should be present with valid nonce value

6. **Test PHP errors**
   - Enable WP_DEBUG and WP_DEBUG_LOG
   - Access the page
   - Check debug.log
   - ✓ No PHP errors or warnings

7. **Test translation strings**
   - All strings use proper translation functions
   - ✓ `esc_html__()` for translated output
   - ✓ `esc_attr()` for attributes

## WordPress Best Practices

### Security Patterns
- **ABSPATH check**: Prevents direct file access
- **Capability check**: `current_user_can('manage_options')`
- **Nonce creation**: `wp_create_nonce('super-form-builder')`
- **Escaping output**: `esc_html()`, `esc_attr()`, `esc_url()`

### Translation Functions
- `esc_html__($text, 'super-forms')` - Return translated text (escaped for HTML)
- `esc_html_e($text, 'super-forms')` - Echo translated text (escaped for HTML)

### WordPress Admin CSS Classes
- `.wrap` - Standard WordPress admin page wrapper
- `.button` - Base button class
- `.button-primary` - Primary action button (blue)
- `.button-secondary` - Secondary action button (gray)
- `.button-hero` - Larger button size
- `.description` - Muted descriptive text

## Estimated Time

**30-45 minutes** for implementation and testing

## Notes

- This phase creates a skeleton page with placeholders
- All sections show "Coming in Phase X" messages
- Later phases will replace placeholders with actual functionality
- The page structure follows the migration page pattern exactly
- Inline CSS matches the migration page styling
- jQuery setup is ready for future AJAX implementations
