# Phase 21: Form Builder Conditional Settings Conversion to Automations

## Terminology Reference

See [Phase 26: Terminology Standardization](26-terminology-standardization.md) for the definitive naming convention:
- **Automation** = The saved entity (container)
- **Trigger** = Event node that starts execution
- **Action** = Task node that does something
- **Condition** = Logic node for branching
- **Control** = Flow node (delay, schedule, stop)

## Important Clarification

**This is NOT a migration of old automations to a new format.**

This phase converts **form builder settings** (legacy conditional logic stored in form meta) INTO **new automations** in the automations system. The automations system is brand new - there are no old automations to migrate.

**What we're converting:**
- Legacy form settings like `conditionally_save_entry` → New automation with `create_entry` action node
- Legacy `form_hide_after_submitting` → New automation with `ui.hide_form` action node
- Legacy MailPoet conditional settings → New automation with `mailpoet.add_subscriber` action node

## Overview

Convert the remaining conditional form builder settings (excluding Email v2 and WooCommerce, which have dedicated phases) to use the automations infrastructure. This eliminates scattered conditional logic across core and add-ons, centralizing all automation in the automations system.

## Problem Statement

### Current State: Scattered Conditional Logic

**Core Settings** (`class-settings.php`):
- `conditionally_save_entry` + `conditionally_save_entry_check` → Save entry only when condition met
- `form_hide_after_submitting` → Hide form after submit
- `form_clear_after_submitting` → Reset form after submit

**Add-on Settings** (distributed across multiple files):
- MailPoet: `mailpoet_conditionally_save` + `mailpoet_conditionally_save_check`
- Mailster: `mailster_conditionally_save` + `mailster_conditionally_save_check`
- PayPal: `conditionally_paypal_checkout` + `conditionally_paypal_checkout_check`
- Register/Login: User registration conditional logic

**Technical Debt:**
1. **Legacy Condition Format**: All use old `{field1},operator,{field2}` format (no AND/OR grouping)
2. **4 Unclosed `<select>` Tags**: Settings fields missing closing tags in dropdown definitions
3. **No Centralized Management**: Settings hidden in different tabs across form builder
4. **Hardcoded Execution**: No logging, no retry, no debugging
5. **Scattered Codebase**: Logic spread across `class-ajax.php`, add-on files, settings definitions

### Target State: Unified Automations System

```
┌──────────────────────────────────────────────────────────────────┐
│ FORM BUILDER: Settings Tab (Deprecated)                         │
│ ────────────────────────────────────────────────────            │
│ [!] Conditional settings have moved to the Automations tab       │
│ [Convert Now] [Learn More]                                      │
└──────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────────────┐
│ FORM BUILDER: Automations Tab (React UI)                        │
│ ────────────────────────────────────────────────────────────    │
│                                                                  │
│ ✓ Save Entry (Conditional)                                      │
│   Trigger: Form Submitted (payment_method = "stripe")            │
│   Action: Create Entry                                          │
│                                                                  │
│ ✓ Add to MailPoet                                               │
│   Trigger: Form Submitted (newsletter_optin = "yes")             │
│   Action: Add MailPoet Subscriber                               │
│                                                                  │
│ ✓ Hide Form After Submit                                        │
│   Trigger: Form Submitted                                        │
│   Action: Hide Form (client-side)                               │
└──────────────────────────────────────────────────────────────────┘
                           │
                           ▼
               wp_superforms_automations table
                    (centralized storage)
```

## Conditional Settings Inventory

### Core Settings Migration Map

| Current Setting | Maps to Event | Maps to Action | Priority |
|----------------|---------------|----------------|----------|
| `conditionally_save_entry` | `form.before_entry_created` | `create_entry` (conditional) | P0 |
| `form_hide_after_submitting` | `form.submitted` | `run_hook` → `super_hide_form_{form_id}` | P1 |
| `form_clear_after_submitting` | `form.submitted` | `run_hook` → `super_clear_form_{form_id}` | P1 |

### Add-on Settings Migration Map

| Add-on | Setting | Maps to Event | Maps to Action | Priority |
|--------|---------|---------------|----------------|----------|
| MailPoet | `mailpoet_conditionally_save` | `form.submitted` | `mailpoet.add_subscriber` (NEW) | P0 |
| Mailster | `mailster_conditionally_save` | `form.submitted` | `mailster.add_subscriber` (NEW) | P0 |
| PayPal | `conditionally_paypal_checkout` | `form.before_entry_created` | `abort_submission` (inverse logic) | P1 |
| Register/Login | User registration conditions | `form.submitted` | `create_user` (existing) | P2 |

### New Actions to Implement

```php
// MailPoet Integration Action
class SUPER_Action_MailPoet_Add_Subscriber extends SUPER_Action_Base {
    public function get_name() { return 'mailpoet.add_subscriber'; }
    public function get_label() { return 'Add MailPoet Subscriber'; }
    public function get_category() { return 'Email Marketing'; }

    public function execute($config, $context) {
        // Port logic from super-forms-mailpoet/super-forms-mailpoet.php:176-230
        // Check MailPoet plugin active
        // Map fields: email, first_name, last_name, lists, tags
        // Call MailPoet Subscriber API
        // Return success/error
    }
}

// Mailster Integration Action
class SUPER_Action_Mailster_Add_Subscriber extends SUPER_Action_Base {
    public function get_name() { return 'mailster.add_subscriber'; }
    public function get_label() { return 'Add Mailster Subscriber'; }
    public function get_category() { return 'Email Marketing'; }

    public function execute($config, $context) {
        // Port logic from super-forms-mailster/super-forms-mailster.php:176-260
    }
}

// Client-Side UI Actions (executed via JavaScript)
class SUPER_Action_Hide_Form extends SUPER_Action_Base {
    public function get_name() { return 'ui.hide_form'; }
    public function get_label() { return 'Hide Form'; }
    public function get_category() { return 'User Interface'; }
    public function supports_async() { return false; } // Must run immediately

    public function execute($config, $context) {
        // Fire WordPress hook that JavaScript listens to
        do_action('super_hide_form', $context['form_id']);
        return ['success' => true, 'message' => 'Form hidden'];
    }
}

class SUPER_Action_Clear_Form extends SUPER_Action_Base {
    public function get_name() { return 'ui.clear_form'; }
    public function get_label() { return 'Clear/Reset Form'; }
    public function get_category() { return 'User Interface'; }
    public function supports_async() { return false; }

    public function execute($config, $context) {
        do_action('super_clear_form', $context['form_id']);
        return ['success' => true, 'message' => 'Form cleared'];
    }
}
```

## Migration Strategy

### Phase 21.1: Backend Infrastructure (2 days)

**Files to Create:**
1. `/src/includes/class-trigger-settings-migration.php` - Migration orchestration class
2. `/src/includes/triggers/actions/class-action-mailpoet-add-subscriber.php`
3. `/src/includes/triggers/actions/class-action-mailster-add-subscriber.php`
4. `/src/includes/triggers/actions/class-action-hide-form.php`
5. `/src/includes/triggers/actions/class-action-clear-form.php`

**Conversion Class Structure:**
```php
class SUPER_Settings_To_Automation_Converter {

    /**
     * Convert form builder settings → new automations
     * Called via hook: add_action('super_before_save_form', ...)
     *
     * NOTE: This is converting SETTINGS to AUTOMATIONS, not migrating old automations.
     * The automations system is brand new - there are no old automations to migrate.
     */
    public static function convert_settings_to_automations($form_id, $settings) {
        // 1. Conditional Save Entry
        if (!empty($settings['conditionally_save_entry'])) {
            self::convert_conditional_save_entry($form_id, $settings);
        }

        // 2. Form Hide After Submit
        if (!empty($settings['form_hide_after_submitting'])) {
            self::convert_form_hide($form_id, $settings);
        }

        // 3. Form Clear After Submit
        if (!empty($settings['form_clear_after_submitting'])) {
            self::convert_form_clear($form_id, $settings);
        }
    }

    /**
     * Load triggers → form builder settings format
     * Called via hook: add_filter('super_get_form_settings', ...)
     */
    public static function get_triggers_as_settings($form_id) {
        // Query triggers for this form
        // Convert back to old settings format for display
        // Enables backwards compatibility during transition
    }

    /**
     * Convert legacy condition format
     * From: "{field1},operator,{field2}"
     * To: { operator: 'AND', rules: [{field, operator, value}] }
     */
    public static function migrate_legacy_condition($legacy_check) {
        $parts = explode(',', $legacy_check);
        return array(
            'operator' => 'AND',
            'rules' => array(
                array(
                    'field' => $parts[0] ?? '',
                    'operator' => $parts[1] ?? '==',
                    'value' => $parts[2] ?? ''
                )
            )
        );
    }

    /**
     * Convert conditional save entry setting to trigger
     *
     * Uses NEW schema with node-level scope architecture:
     * - Scope is in trigger node config, NOT at automation level
     * - Conditions are condition nodes in the workflow graph
     */
    private static function convert_conditional_save_entry($form_id, $settings) {
        // Check if automation already exists for this form (by name convention)
        $existing_automations = SUPER_Automation_DAL::get_all( true );
        $existing = null;

        foreach ( $existing_automations as $automation ) {
            if ( $automation['name'] === "Form {$form_id}: Conditional Save Entry" ) {
                $existing = $automation;
                break;
            }
        }

        // Convert legacy condition to condition node
        $legacy_condition = $settings['conditionally_save_entry_check'] ?? '';
        $condition_node = self::legacy_condition_to_node( $legacy_condition );

        // Build workflow graph with node-level scope
        $workflow_graph = array(
            'nodes' => array(
                // Trigger node with scope in config (NOT at automation level!)
                array(
                    'id'       => 'trigger-1',
                    'category' => 'trigger',
                    'type'     => 'form.before_entry_created',
                    'config'   => array(
                        'scope'  => 'specific',   // Scope is HERE in node config
                        'formId' => $form_id,     // Form filter is HERE
                    ),
                    'position' => array( 'x' => 100, 'y' => 100 ),
                ),
                // Condition node
                array(
                    'id'       => 'condition-1',
                    'category' => 'condition',
                    'type'     => 'field_comparison',
                    'config'   => $condition_node,
                    'position' => array( 'x' => 300, 'y' => 100 ),
                ),
                // Action node
                array(
                    'id'       => 'action-1',
                    'category' => 'action',
                    'type'     => 'create_entry',
                    'config'   => array(
                        'status' => $settings['contact_entry_custom_status'] ?? 'pending',
                    ),
                    'position' => array( 'x' => 500, 'y' => 100 ),
                ),
            ),
            'connections' => array(
                array( 'from' => 'trigger-1', 'to' => 'condition-1' ),
                array( 'from' => 'condition-1', 'to' => 'action-1', 'sourceHandle' => 'true' ),
            ),
        );

        // Create automation data (NEW schema - NO scope/event_id at automation level!)
        $automation_data = array(
            'name'           => "Form {$form_id}: Conditional Save Entry",
            'type'           => 'visual',
            'workflow_graph' => $workflow_graph,
            'enabled'        => 1,
        );

        if ( $existing ) {
            // Update existing automation
            SUPER_Automation_DAL::update( $existing['id'], $automation_data );
        } else {
            // Create new automation
            SUPER_Automation_DAL::create( $automation_data );
        }
    }

    /**
     * Convert legacy condition format to condition node config
     *
     * From: "{field1},operator,{field2}"
     * To: { operator: 'AND', rules: [{field, operator, value}] }
     */
    private static function legacy_condition_to_node( $legacy_check ) {
        if ( empty( $legacy_check ) ) {
            return array( 'operator' => 'AND', 'rules' => array() );
        }

        $parts = explode( ',', $legacy_check );

        return array(
            'operator' => 'AND',
            'rules'    => array(
                array(
                    'field'    => trim( $parts[0] ?? '', '{}' ),
                    'operator' => $parts[1] ?? '==',
                    'value'    => trim( $parts[2] ?? '', '{}' ),
                ),
            ),
        );
    }
}
```

### Phase 21.2: Add-on Action Implementation (3 days)

**MailPoet Action** (`class-action-mailpoet-add-subscriber.php`):
```php
public function execute($config, $context) {
    // 1. Verify MailPoet plugin active
    if (!class_exists('MailPoet\API\API')) {
        return new WP_Error('mailpoet_not_active', 'MailPoet plugin is not active');
    }

    // 2. Get MailPoet API
    $mailpoet_api = \MailPoet\API\API::MP('v1');

    // 3. Resolve field tags
    $email = SUPER_Common::email_tags($config['email'], $context['data'], $context['settings']);
    $first_name = SUPER_Common::email_tags($config['first_name'] ?? '', $context['data'], $context['settings']);
    $last_name = SUPER_Common::email_tags($config['last_name'] ?? '', $context['data'], $context['settings']);

    // 4. Create/update subscriber
    $subscriber = array(
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name
    );

    // 5. Add to lists
    $lists = $config['lists'] ?? array();

    try {
        $result = $mailpoet_api->addSubscriber($subscriber, $lists);

        return array(
            'success' => true,
            'subscriber_id' => $result['id'],
            'status' => $result['status']
        );
    } catch (Exception $e) {
        return new WP_Error('mailpoet_error', $e->getMessage());
    }
}
```

**Mailster Action** (similar structure, uses Mailster API)

**Client-Side Actions** (hide_form, clear_form):
- Backend: Fire WordPress action hooks
- Frontend: JavaScript listeners respond to events
- Integration in `/src/assets/js/frontend/form.js`:

```javascript
// Listen for form hide/clear events
jQuery(document).on('super_hide_form', function(e, formId) {
    const $form = jQuery('#super-form-' + formId);
    $form.slideUp();
});

jQuery(document).on('super_clear_form', function(e, formId) {
    const $form = jQuery('#super-form-' + formId);
    $form.find('input, textarea, select').val('').trigger('change');
});
```

### Phase 21.3: Fix Unclosed `<select>` Tags (30 minutes)

**Files to Fix:**
1. `/src/includes/class-settings.php` line ~1266 - `conditionally_save_entry_check`
2. `/src/add-ons/super-forms-mailpoet/super-forms-mailpoet.php` line ~344 - `mailpoet_conditionally_save_check`
3. `/src/add-ons/super-forms-mailster/super-forms-mailster.php` line ~TBD - `mailster_conditionally_save_check`
4. `/src/add-ons/super-forms-paypal/super-forms-paypal.php` line ~TBD - `conditionally_paypal_checkout_check`

**Problem:**
```php
// Current (BROKEN - missing </select>)
'conditionally_save_entry_check' => array(
    'type' => 'conditional_check',
    // ... field config
),
```

These fields use `type: 'conditional_check'` which renders a dropdown but the PHP template doesn't close the `<select>` tag.

**Solution:**
Search for dropdown rendering logic in `SUPER_UI` class or settings rendering, add closing `</select>` tags.

### Phase 21.4: Deprecation & Migration UI (2 days)

**Add Migration Banner** (in `/src/includes/admin/views/page-create-form.php`):
```php
// Show banner in Settings tab if legacy settings exist
if (!empty($settings['conditionally_save_entry']) ||
    !empty($settings['form_hide_after_submitting'])) {
    ?>
    <div class="notice notice-warning">
        <p>
            <strong>⚠️ Conditional settings have moved to the Automations tab!</strong><br>
            Your existing settings will continue to work, but we recommend converting to the new system.
        </p>
        <p>
            <button class="button button-primary" id="convert-to-automations">
                Convert Now
            </button>
            <a href="#" class="button">Learn More</a>
        </p>
    </div>
    <?php
}
```

**Conversion AJAX Handler:**
```php
add_action('wp_ajax_super_convert_settings_to_automations', function() {
    $form_id = intval($_POST['form_id']);

    // Get form settings
    $settings = SUPER_Common::get_form_settings($form_id);

    // Run conversion (NOT migration - there are no old automations!)
    SUPER_Settings_To_Automation_Converter::convert_settings_to_automations($form_id, $settings);

    // Clear legacy settings (optional - keep for rollback)
    // unset($settings['conditionally_save_entry']);
    // SUPER_Common::save_form_settings($settings, $form_id);

    wp_send_json_success(array(
        'message' => 'Settings converted to automations successfully!',
        'automations_created' => 3
    ));
});
```

### Phase 21.5: Integration Hooks (1 day)

**Hook into Form Save:**
```php
// /src/includes/class-common.php or init hook
add_action('super_before_save_form', function($form_id, $settings) {
    if (class_exists('SUPER_Settings_To_Automation_Converter')) {
        // NOTE: This CONVERTS settings to automations, not migrating old automations
        SUPER_Settings_To_Automation_Converter::convert_settings_to_automations($form_id, $settings);
    }
}, 10, 2);
```

**Hook into Form Load:**
```php
add_filter('super_get_form_settings', function($settings, $form_id) {
    if (class_exists('SUPER_Settings_To_Automation_Converter')) {
        // Load automations and populate legacy fields for display
        $automation_settings = SUPER_Settings_To_Automation_Converter::get_automations_as_settings($form_id);
        if ($automation_settings) {
            $settings = array_merge($settings, $automation_settings);
        }
    }
    return $settings;
}, 10, 2);
```

**Hook into Submission:**
```php
// Ensure conditional save entry logic uses automations
// Remove old hardcoded logic from class-ajax.php
// Let automation system handle via 'form.before_entry_created' event
```

## Success Criteria

### Core Functionality
- [ ] `conditionally_save_entry` → automation with `create_entry` action
- [ ] `form_hide_after_submitting` → automation with `ui.hide_form` action
- [ ] `form_clear_after_submitting` → automation with `ui.clear_form` action
- [ ] MailPoet conditional save → automation with `mailpoet.add_subscriber` action
- [ ] Mailster conditional save → automation with `mailster.add_subscriber` action
- [ ] PayPal conditional checkout → automation with condition node (inverse logic)

### Conversion
- [ ] Automatic conversion on form save
- [ ] Manual conversion via "Convert Now" button
- [ ] Backwards compatibility during transition (30 days)
- [ ] Legacy settings preserved for rollback
- [ ] Conversion status tracked per form

### UI/UX
- [ ] Conversion banner shows when legacy settings exist
- [ ] "Learn More" link explains benefits
- [ ] "Convert Now" runs conversion and shows success message
- [ ] Settings tab shows deprecation notice
- [ ] Automations tab shows converted automations

### Logging & Debug
- [ ] All actions logged via SUPER_Automation_Logger
- [ ] Conversion errors captured and reported
- [ ] Execution logs show success/failure
- [ ] Debug mode shows condition evaluation

## Implementation Order

### Priority P0 (Week 1)
1. Create `SUPER_Settings_To_Automation_Converter` class
2. Implement `legacy_condition_to_node()` helper
3. Implement MailPoet action (`mailpoet.add_subscriber`)
4. Implement Mailster action (`mailster.add_subscriber`)
5. Register new actions in `SUPER_Automation_Registry`
6. Test conversion with sample forms

### Priority P1 (Week 2)
7. Implement UI actions (`ui.hide_form`, `ui.clear_form`)
8. Add JavaScript event listeners for client-side actions
9. Fix 4 unclosed `<select>` tags
10. Add conversion banner to Settings tab
11. Implement AJAX conversion handler
12. Test end-to-end conversion flow

### Priority P2 (Week 3)
13. Add integration hooks (form save/load)
14. Remove hardcoded conditional logic from `class-ajax.php`
15. Test backwards compatibility
16. Write conversion documentation
17. Update user guide

## Technical Dependencies

**Required:**
- Phase 1: Foundation - COMPLETE ✅
- Phase 2: Action Scheduler - COMPLETE ✅
- Phase 3: Execution/Logging - COMPLETE ✅
- Phase 20: Triggers v2 UI - PENDING (optional for Phase 21)

**Related:**
- Phase 11: Email Migration - IN PROGRESS (separate migration path)
- Phase 12: WooCommerce Migration - PENDING (separate migration path)

**Blocks:**
- None (can implement independently)

## Files to Create/Modify

### New Files (5)
1. `/src/includes/class-settings-to-automation-converter.php` (~400 lines)
2. `/src/includes/automations/actions/class-action-mailpoet-add-subscriber.php` (~200 lines)
3. `/src/includes/automations/actions/class-action-mailster-add-subscriber.php` (~200 lines)
4. `/src/includes/automations/actions/class-action-hide-form.php` (~100 lines)
5. `/src/includes/automations/actions/class-action-clear-form.php` (~100 lines)

### Modified Files (8)
1. `/src/includes/automations/class-automation-registry.php` - Register 4 new actions
2. `/src/includes/class-settings.php` - Fix unclosed `<select>` tag (1 location)
3. `/src/add-ons/super-forms-mailpoet/super-forms-mailpoet.php` - Fix tag + deprecate old logic
4. `/src/add-ons/super-forms-mailster/super-forms-mailster.php` - Fix tag + deprecate old logic
5. `/src/add-ons/super-forms-paypal/super-forms-paypal.php` - Fix tag
6. `/src/includes/admin/views/page-create-form.php` - Add conversion banner
7. `/src/assets/js/backend/create-form.js` - Add conversion AJAX handler
8. `/src/assets/js/frontend/form.js` - Add hide/clear event listeners

## Estimated Effort

| Task | Days | Priority |
|------|------|----------|
| Backend Infrastructure | 2 | P0 |
| Add-on Actions | 3 | P0 |
| Fix Unclosed Tags | 0.5 | P0 |
| Deprecation UI | 2 | P1 |
| Integration Hooks | 1 | P1 |
| Testing & Polish | 2 | P1 |
| Documentation | 1 | P2 |
| **Total** | **11.5 days** | |

## Open Questions

1. **Rollback Strategy**: Should we keep legacy settings for 30 days or indefinitely?
2. **Global vs Form Scope**: Should hide/clear form automations be global or form-specific?
3. **PayPal Conditional Logic**: Inverse logic (abort when condition NOT met) or new action?
4. **Conversion Timing**: On form save only, or background job for all forms?
5. **UI Integration**: Show both old settings AND automations during transition?

## Success Metrics

- [ ] 100% of forms with `conditionally_save_entry` converted to automations
- [ ] 100% of forms with MailPoet/Mailster conditional saves converted
- [ ] 0 PHP errors from unclosed `<select>` tags
- [ ] Conversion banner shows for 100% of forms with legacy settings
- [ ] Automation execution logs show all converted automations working
- [ ] Zero reported issues from users during transition

## Notes

- **This is settings CONVERSION, not automation MIGRATION** - There are no old automations to migrate. We're converting legacy form builder settings into new automations.
- This phase completes the conversion trifecta: Email (Phase 11), WooCommerce (Phase 12), Form Settings (Phase 21)
- After Phase 21, ALL conditional logic lives in automations system
- Legacy settings code can be safely removed after 30-day transition period
- Phase 20 (Automations UI) enhances but isn't required for Phase 21

## Architecture Reference

This phase uses the **Node-Level Scope Architecture**:

```
Scope is in TRIGGER NODE config, NOT at automation level.

Example workflow_graph:
{
  "nodes": [
    {
      "id": "trigger-1",
      "category": "trigger",
      "type": "form.submitted",
      "config": {
        "scope": "specific",    // ← Scope lives HERE
        "formId": 123           // ← Form ID lives HERE
      }
    }
  ]
}
```

**Never use these at automation level** (they don't exist in the schema):
- ~~scope~~
- ~~scope_id~~
- ~~event_id~~
- ~~conditions~~ (conditions are nodes in the graph)
