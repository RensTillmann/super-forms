# Phase 13: FluentCRM Integration Add-on

## Overview

Create a new production-ready add-on that integrates Super Forms with FluentCRM, a popular WordPress-native CRM plugin. This add-on demonstrates the triggers/actions extensibility system and provides real value to users who have FluentCRM installed.

## Why FluentCRM

- **WordPress-Native**: No external API keys, data stays in WordPress, works offline
- **70K+ Active Installs**: Growing user base with 4.8/5 rating
- **100+ Hooks + REST API**: Excellent developer documentation
- **CRM = #1 Request**: Most common integration request for form plugins
- **GDPR Compliant**: Data never leaves WordPress site
- **Same Ecosystem**: WP Manage Ninja also makes Fluent Forms (600K installs)

## Add-on Structure

```
/src/add-ons/super-forms-fluentcrm/
├── super-forms-fluentcrm.php       # Main plugin file
├── includes/
│   ├── class-fluentcrm-actions.php # Action implementations
│   └── class-fluentcrm-fields.php  # Field mapping UI
├── assets/
│   └── css/
│       └── admin.css               # Settings styling
└── readme.txt                      # WordPress.org readme
```

## Actions to Register

### 1. `fluentcrm.create_contact`
Create a new contact or update existing (by email).

**Config Schema:**
```json
{
  "email": "{email}",
  "first_name": "{first_name}",
  "last_name": "{last_name}",
  "phone": "{phone}",
  "address_line_1": "{address}",
  "city": "{city}",
  "state": "{state}",
  "postal_code": "{zip}",
  "country": "{country}",
  "company": "{company}",
  "status": "subscribed",
  "update_if_exists": true,
  "custom_fields": {
    "source": "super_forms",
    "form_id": "{form_id}",
    "custom_field_1": "{custom_value}"
  }
}
```

**Implementation:**
```php
public function execute($config, $context) {
    // Check FluentCRM is active
    if (!defined('FLUENTCRM')) {
        return new WP_Error('fluentcrm_not_active', 'FluentCRM is not installed or activated');
    }

    $email = $this->replace_tags($config['email'], $context);
    if (!is_email($email)) {
        return new WP_Error('invalid_email', 'Invalid email address: ' . $email);
    }

    $contact_data = [
        'email' => $email,
        'first_name' => $this->replace_tags($config['first_name'] ?? '', $context),
        'last_name' => $this->replace_tags($config['last_name'] ?? '', $context),
        'phone' => $this->replace_tags($config['phone'] ?? '', $context),
        'status' => $config['status'] ?? 'subscribed',
    ];

    // Use FluentCRM API
    $contact_api = FluentCrmApi('contacts');

    if ($config['update_if_exists'] ?? true) {
        $contact = $contact_api->createOrUpdate($contact_data);
    } else {
        $existing = $contact_api->getContactByEmail($email);
        if ($existing) {
            return ['success' => true, 'contact_id' => $existing->id, 'action' => 'skipped'];
        }
        $contact = $contact_api->create($contact_data);
    }

    return [
        'success' => true,
        'contact_id' => $contact->id,
        'action' => 'created_or_updated',
    ];
}
```

### 2. `fluentcrm.add_to_lists`
Add contact to one or more email lists.

**Config Schema:**
```json
{
  "email": "{email}",
  "list_ids": [1, 5, 12],
  "create_if_not_exists": true
}
```

### 3. `fluentcrm.apply_tags`
Apply tags to a contact.

**Config Schema:**
```json
{
  "email": "{email}",
  "tag_ids": [3, 7, 15],
  "tag_names": ["lead", "website-inquiry"],
  "create_if_not_exists": true
}
```

### 4. `fluentcrm.remove_tags`
Remove tags from a contact.

**Config Schema:**
```json
{
  "email": "{email}",
  "tag_ids": [3, 7],
  "tag_names": ["unsubscribed"]
}
```

### 5. `fluentcrm.start_automation`
Trigger a FluentCRM automation funnel for a contact.

**Config Schema:**
```json
{
  "email": "{email}",
  "funnel_id": 5,
  "skip_if_already_in_funnel": true
}
```

### 6. `fluentcrm.add_note`
Add a note to a contact's profile.

**Config Schema:**
```json
{
  "email": "{email}",
  "title": "Form Submission",
  "description": "Submitted {form_title} on {submission_date}",
  "type": "note"
}
```

## Success Criteria

### Core Functionality
- [ ] `fluentcrm.create_contact` creates/updates contacts
- [ ] `fluentcrm.add_to_lists` adds to specified lists
- [ ] `fluentcrm.apply_tags` applies tags
- [ ] `fluentcrm.remove_tags` removes tags
- [ ] `fluentcrm.start_automation` triggers funnels
- [ ] `fluentcrm.add_note` adds notes to contacts

### Field Mapping
- [ ] All standard FluentCRM fields mappable
- [ ] Custom fields supported
- [ ] Tag replacement works in all fields
- [ ] Repeater data can populate custom fields

### Error Handling
- [ ] Graceful error when FluentCRM not active
- [ ] Invalid email validation
- [ ] Missing required fields reported
- [ ] API errors logged and retried

### Logging
- [ ] All FluentCRM operations logged
- [ ] Contact ID returned in logs
- [ ] Action taken (created/updated/skipped) recorded

## Technical Implementation

### Main Plugin File
```php
<?php
/**
 * Plugin Name: Super Forms - FluentCRM
 * Plugin URI:  https://super-forms.com/add-ons/fluentcrm/
 * Description: Connect Super Forms with FluentCRM for powerful marketing automation
 * Version:     1.0.0
 * Author:      WebRehab
 * Author URI:  https://super-forms.com/
 * Text Domain: super-forms-fluentcrm
 * Domain Path: /languages/
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

// Check dependencies
add_action('plugins_loaded', function() {
    // Check Super Forms
    if (!class_exists('SUPER_Forms')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Super Forms - FluentCRM requires Super Forms to be installed and activated.', 'super-forms-fluentcrm');
            echo '</p></div>';
        });
        return;
    }

    // Check FluentCRM
    if (!defined('FLUENTCRM')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('Super Forms - FluentCRM: FluentCRM plugin is not active. Actions will be skipped.', 'super-forms-fluentcrm');
            echo '</p></div>';
        });
        // Don't return - still register actions (they'll return errors if FluentCRM not active)
    }

    // Register actions
    add_action('super_trigger_register_actions', 'super_fluentcrm_register_actions');
});

function super_fluentcrm_register_actions() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-fluentcrm-actions.php';

    $actions = [
        'fluentcrm.create_contact' => 'SUPER_Action_FluentCRM_Create_Contact',
        'fluentcrm.add_to_lists' => 'SUPER_Action_FluentCRM_Add_To_Lists',
        'fluentcrm.apply_tags' => 'SUPER_Action_FluentCRM_Apply_Tags',
        'fluentcrm.remove_tags' => 'SUPER_Action_FluentCRM_Remove_Tags',
        'fluentcrm.start_automation' => 'SUPER_Action_FluentCRM_Start_Automation',
        'fluentcrm.add_note' => 'SUPER_Action_FluentCRM_Add_Note',
    ];

    foreach ($actions as $action_type => $class_name) {
        SUPER_Trigger_Registry::get_instance()->register_action($action_type, new $class_name());
    }
}
```

### Action Base Class
```php
abstract class SUPER_Action_FluentCRM_Base extends SUPER_Trigger_Action_Base {

    /**
     * Check if FluentCRM is available
     */
    protected function check_fluentcrm() {
        if (!defined('FLUENTCRM') || !function_exists('FluentCrmApi')) {
            return new WP_Error(
                'fluentcrm_not_active',
                __('FluentCRM is not installed or activated', 'super-forms-fluentcrm')
            );
        }
        return true;
    }

    /**
     * Get contact by email, optionally creating if not exists
     */
    protected function get_or_create_contact($email, $create_if_not_exists = false) {
        $contact_api = FluentCrmApi('contacts');
        $contact = $contact_api->getContactByEmail($email);

        if (!$contact && $create_if_not_exists) {
            $contact = $contact_api->create(['email' => $email, 'status' => 'subscribed']);
        }

        return $contact;
    }

    /**
     * Get list IDs from names (for convenience)
     */
    protected function resolve_list_ids($list_ids = [], $list_names = []) {
        $ids = is_array($list_ids) ? $list_ids : [];

        if (!empty($list_names)) {
            $lists_api = FluentCrmApi('lists');
            $all_lists = $lists_api->all();
            foreach ($all_lists as $list) {
                if (in_array($list->title, $list_names)) {
                    $ids[] = $list->id;
                }
            }
        }

        return array_unique(array_filter($ids));
    }

    /**
     * Get tag IDs from names (for convenience)
     */
    protected function resolve_tag_ids($tag_ids = [], $tag_names = []) {
        $ids = is_array($tag_ids) ? $tag_ids : [];

        if (!empty($tag_names)) {
            $tags_api = FluentCrmApi('tags');
            $all_tags = $tags_api->all();
            foreach ($all_tags as $tag) {
                if (in_array($tag->title, $tag_names)) {
                    $ids[] = $tag->id;
                }
            }
        }

        return array_unique(array_filter($ids));
    }

    public function supports_async() {
        return true;
    }

    public function get_execution_mode() {
        return 'auto'; // Run async if configured, sync by default
    }
}
```

## Testing Requirements

### Unit Tests
- [ ] Action registration works
- [ ] Config validation catches missing email
- [ ] Tag replacement works in all fields
- [ ] Error handling when FluentCRM not active

### Integration Tests (requires FluentCRM)
- [ ] Contact creation works
- [ ] Contact update (duplicate email) works
- [ ] List assignment works
- [ ] Tag application works
- [ ] Automation triggering works

### Manual Testing
- [ ] Install add-on with FluentCRM active
- [ ] Create trigger with `fluentcrm.create_contact` action
- [ ] Submit form → verify contact created in FluentCRM
- [ ] Check trigger logs show success
- [ ] Test with FluentCRM deactivated → verify graceful error

## UI Considerations

### Action Configuration in Triggers Admin
When building the triggers admin UI (future phase), the FluentCRM actions should show:

1. **List/Tag Dropdowns**: Dynamically load from FluentCRM API
2. **Field Mapping UI**: Show available FluentCRM fields with tag pickers
3. **Automation Selector**: Dropdown of available funnels
4. **Status Selector**: Dropdown of FluentCRM contact statuses

### Form Builder Integration (Optional)
Could add a "FluentCRM" tab to form builder that creates triggers under the hood (like Email v2 pattern).

## Dependencies

- Phase 1: Foundation (triggers tables, registry) - COMPLETE
- Phase 2: Action Scheduler - COMPLETE (for async execution)
- FluentCRM plugin installed on target site
- Super Forms core plugin

## Documentation

### User Documentation
- Installation instructions
- Connecting to FluentCRM (just activate both plugins)
- Creating triggers with FluentCRM actions
- Field mapping examples
- Troubleshooting common issues

### Developer Documentation
- Hook reference for extending
- Adding custom FluentCRM actions
- FluentCRM API reference links

## Notes

- No migration needed - this is a new feature
- FluentCRM has a free version - lower barrier for users
- Consider adding FluentCRM events (contact updated, etc.) in future
- This add-on validates the triggers extensibility pattern
- Can be template for other CRM integrations (HubSpot, Pipedrive, etc.)
