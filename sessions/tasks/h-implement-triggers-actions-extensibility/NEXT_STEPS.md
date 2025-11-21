# Next Steps: Triggers/Actions System Implementation

## Current Status ✅

**Phase 1a: Event Firing - COMPLETE**
- ✅ 10 events implemented and firing in form submission flow
- ✅ Events synced to dev server (f4d.nl/dev)
- ✅ Event context standardization complete
- ✅ WordPress action hooks integrated
- ✅ Documentation created (EVENT_FLOW_DOCUMENTATION.md)

**Phase 1b: Foundation Classes - COMPLETE**
- ✅ Database schema created (3 tables)
- ✅ Data Access Layer (DAL) implemented
- ✅ Trigger Manager with business logic
- ✅ Registry system for events/actions
- ✅ Condition engine (AND/OR/NOT logic)
- ✅ Base action class
- ✅ Executor with synchronous execution
- ✅ REST API v1 endpoints (CRUD)

---

## Immediate Priority: Testing & Validation

### 1. Add Event Firing Tests ⚡ HIGH PRIORITY

**Goal:** Verify all 10 events fire correctly in all scenarios

**Implementation Plan:**

#### A. Unit Tests for Event Firing
Location: `/tests/triggers/test-event-firing.php`

```php
<?php
/**
 * Test Event Firing in Form Submission Flow
 */
class Test_Event_Firing extends WP_UnitTestCase {

    private $fired_events = array();

    public function setUp() {
        parent::setUp();
        $this->fired_events = array();

        // Hook into all events
        add_action('super_trigger_event', array($this, 'capture_event'), 10, 2);
    }

    public function capture_event($event_id, $context) {
        $this->fired_events[] = array(
            'event_id' => $event_id,
            'context' => $context,
            'timestamp' => microtime(true)
        );
    }

    /**
     * Test normal submission flow
     */
    public function test_normal_submission_event_order() {
        // Simulate form submission
        $form_id = $this->create_test_form();
        $this->submit_form($form_id);

        // Assert event order
        $this->assertCount(4, $this->fired_events);
        $this->assertEquals('form.before_submit', $this->fired_events[0]['event_id']);
        $this->assertEquals('form.submitted', $this->fired_events[1]['event_id']);
        $this->assertEquals('entry.created', $this->fired_events[2]['event_id']);
        $this->assertEquals('entry.saved', $this->fired_events[3]['event_id']);

        // Assert context data
        $entry_created = $this->fired_events[2];
        $this->assertArrayHasKey('entry_id', $entry_created['context']);
        $this->assertGreaterThan(0, $entry_created['context']['entry_id']);
    }

    /**
     * Test spam detection
     */
    public function test_spam_detection_event() {
        $form_id = $this->create_test_form();
        $this->submit_form($form_id, array('super_hp' => 'spam'));

        // Only spam event should fire
        $this->assertCount(1, $this->fired_events);
        $this->assertEquals('form.spam_detected', $this->fired_events[0]['event_id']);
        $this->assertEquals('honeypot', $this->fired_events[0]['context']['detection_method']);
    }

    /**
     * Test entry update flow
     */
    public function test_entry_update_event_order() {
        $form_id = $this->create_test_form();
        $entry_id = $this->submit_form($form_id);
        $this->fired_events = array(); // Reset

        // Update entry
        $this->submit_form($form_id, array(), $entry_id);

        // Assert events
        $this->assertCount(4, $this->fired_events);
        $this->assertEquals('form.before_submit', $this->fired_events[0]['event_id']);
        $this->assertEquals('form.submitted', $this->fired_events[1]['event_id']);
        $this->assertEquals('entry.updated', $this->fired_events[2]['event_id']);
        $this->assertEquals('entry.saved', $this->fired_events[3]['event_id']);

        // Verify is_update flag
        $this->assertTrue($this->fired_events[3]['context']['is_update']);
    }

    /**
     * Test file upload event
     */
    public function test_file_upload_event() {
        $form_id = $this->create_test_form_with_file_field();
        $this->submit_form_with_file($form_id, 'test.jpg');

        // Find file.uploaded event
        $file_events = array_filter($this->fired_events, function($e) {
            return $e['event_id'] === 'file.uploaded';
        });

        $this->assertCount(1, $file_events);
        $file_event = reset($file_events);
        $this->assertArrayHasKey('attachment_id', $file_event['context']);
        $this->assertArrayHasKey('file_name', $file_event['context']);
        $this->assertEquals('test.jpg', $file_event['context']['file_name']);
    }

    /**
     * Test duplicate detection
     */
    public function test_duplicate_detection_event() {
        $form_id = $this->create_test_form_with_unique_title();
        $data = array('entry_title' => 'Unique Title 123');

        // First submission
        $this->submit_form($form_id, $data);
        $this->fired_events = array(); // Reset

        // Duplicate submission
        $this->submit_form($form_id, $data);

        // Find duplicate event
        $duplicate_events = array_filter($this->fired_events, function($e) {
            return $e['event_id'] === 'form.duplicate_detected';
        });

        $this->assertCount(1, $duplicate_events);
        $dup = reset($duplicate_events);
        $this->assertEquals('Unique Title 123', $dup['context']['duplicate_value']);
    }

    /**
     * Test validation failure (CSRF)
     */
    public function test_validation_failure_event() {
        // Disable CSRF check temporarily
        global $GLOBALS;
        $GLOBALS['super_csrf'] = false;

        $form_id = $this->create_test_form();
        $this->submit_form($form_id);

        // Should fire validation_failed
        $validation_events = array_filter($this->fired_events, function($e) {
            return $e['event_id'] === 'form.validation_failed';
        });

        $this->assertCount(1, $validation_events);
    }

    /**
     * Test status changed event
     */
    public function test_status_changed_event() {
        $form_id = $this->create_test_form();
        $entry_id = $this->submit_form($form_id);
        $this->fired_events = array(); // Reset

        // Update with new status
        $this->update_entry_status($entry_id, 'approved');

        // Find status_changed event
        $status_events = array_filter($this->fired_events, function($e) {
            return $e['event_id'] === 'entry.status_changed';
        });

        $this->assertCount(1, $status_events);
        $status = reset($status_events);
        $this->assertEquals('super_unread', $status['context']['previous_status']);
        $this->assertEquals('approved', $status['context']['new_status']);
    }

    // Helper methods
    private function create_test_form() { /* ... */ }
    private function submit_form($form_id, $data = array(), $entry_id = 0) { /* ... */ }
    private function create_test_form_with_file_field() { /* ... */ }
}
```

#### B. Integration Tests
Location: `/tests/triggers/test-trigger-execution.php`

Test complete trigger execution flow:
1. Event fires → Trigger matches → Condition evaluates → Action executes

```php
<?php
class Test_Trigger_Execution extends WP_UnitTestCase {

    /**
     * Test: Create trigger, fire event, verify execution
     */
    public function test_trigger_executes_on_event() {
        // Create a trigger
        $trigger_id = SUPER_Trigger_DAL::create_trigger(array(
            'trigger_name' => 'Test Send Email',
            'scope' => 'form',
            'scope_id' => 123,
            'event_id' => 'entry.created',
            'enabled' => 1
        ));

        // Add action
        SUPER_Trigger_DAL::create_action($trigger_id, array(
            'action_type' => 'send_email',
            'action_config' => json_encode(array(
                'to' => 'test@example.com',
                'subject' => 'New Entry'
            ))
        ));

        // Fire event
        $result = SUPER_Trigger_Executor::fire_event('entry.created', array(
            'form_id' => 123,
            'entry_id' => 456
        ));

        // Assert execution
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey($trigger_id, $result);
        $this->assertEquals('success', $result[$trigger_id]['status']);
    }

    /**
     * Test: Condition filtering
     */
    public function test_trigger_conditions_filter_execution() {
        // Trigger with condition: email contains '@gmail.com'
        $trigger_id = SUPER_Trigger_DAL::create_trigger(array(
            'trigger_name' => 'Gmail Only',
            'scope' => 'form',
            'scope_id' => 123,
            'event_id' => 'entry.created',
            'conditions' => json_encode(array(
                'operator' => 'AND',
                'rules' => array(
                    array('field' => '{email}', 'operator' => 'contains', 'value' => '@gmail.com')
                )
            )),
            'enabled' => 1
        ));

        // Test 1: Gmail email (should execute)
        $result = SUPER_Trigger_Executor::fire_event('entry.created', array(
            'form_id' => 123,
            'entry_id' => 456,
            'data' => array('email' => array('value' => 'user@gmail.com'))
        ));
        $this->assertArrayHasKey($trigger_id, $result);

        // Test 2: Yahoo email (should NOT execute)
        $result = SUPER_Trigger_Executor::fire_event('entry.created', array(
            'form_id' => 123,
            'entry_id' => 789,
            'data' => array('email' => array('value' => 'user@yahoo.com'))
        ));
        $this->assertEmpty($result);
    }
}
```

#### C. Performance Tests
Location: `/tests/triggers/test-performance.php`

```php
<?php
class Test_Trigger_Performance extends WP_UnitTestCase {

    /**
     * Test: Event firing overhead with no triggers
     */
    public function test_event_firing_overhead_no_triggers() {
        $iterations = 100;

        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            SUPER_Trigger_Executor::fire_event('entry.created', array(
                'form_id' => 123,
                'entry_id' => $i
            ));
        }
        $end = microtime(true);

        $avg_time = ($end - $start) / $iterations * 1000; // ms
        $this->assertLessThan(2, $avg_time, 'Event firing overhead should be < 2ms per event');
    }

    /**
     * Test: Lookup performance with 100 triggers
     */
    public function test_trigger_lookup_performance() {
        // Create 100 triggers
        for ($i = 0; $i < 100; $i++) {
            SUPER_Trigger_DAL::create_trigger(array(
                'trigger_name' => "Trigger {$i}",
                'scope' => 'form',
                'scope_id' => rand(1, 10),
                'event_id' => 'entry.created',
                'enabled' => 1
            ));
        }

        // Measure lookup time
        $start = microtime(true);
        $triggers = SUPER_Trigger_Manager::resolve_triggers_for_event('entry.created', array(
            'form_id' => 5
        ));
        $end = microtime(true);

        $lookup_time = ($end - $start) * 1000; // ms
        $this->assertLessThan(20, $lookup_time, 'Trigger lookup should be < 20ms with 100 triggers');
    }
}
```

---

### 2. Developer Tools Enhancement ⚡ HIGH PRIORITY

**Goal:** Add testing UI to Developer Tools page for easy event/trigger testing

**Implementation Plan:**

#### A. Event Firing Test Panel
Location: Add to `/src/includes/class-developer-tools.php`

```php
/**
 * Event Firing Test Panel
 */
private static function render_event_firing_tests() {
    ?>
    <div class="super-dev-tools-section">
        <h2>🔥 Event Firing Tests</h2>
        <p>Fire events manually and inspect the results</p>

        <div class="event-test-controls">
            <label>
                Select Event:
                <select id="test-event-id">
                    <option value="form.before_submit">form.before_submit</option>
                    <option value="form.submitted">form.submitted</option>
                    <option value="entry.created">entry.created</option>
                    <option value="entry.saved">entry.saved</option>
                    <option value="entry.updated">entry.updated</option>
                    <option value="entry.status_changed">entry.status_changed</option>
                    <option value="form.spam_detected">form.spam_detected</option>
                    <option value="form.validation_failed">form.validation_failed</option>
                    <option value="form.duplicate_detected">form.duplicate_detected</option>
                    <option value="file.uploaded">file.uploaded</option>
                </select>
            </label>

            <label>
                Form ID:
                <input type="number" id="test-form-id" value="1" min="1">
            </label>

            <button class="button button-primary" id="fire-test-event">
                Fire Test Event
            </button>
        </div>

        <div id="event-test-results" style="margin-top: 20px;">
            <!-- Results appear here -->
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#fire-test-event').on('click', function() {
            var eventId = $('#test-event-id').val();
            var formId = parseInt($('#test-form-id').val());

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'super_dev_fire_test_event',
                    event_id: eventId,
                    form_id: formId,
                    nonce: '<?php echo wp_create_nonce('super_dev_tools'); ?>'
                },
                success: function(response) {
                    var html = '<div class="notice notice-success"><p>✅ Event Fired</p></div>';
                    html += '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
                    $('#event-test-results').html(html);
                },
                error: function(xhr) {
                    $('#event-test-results').html(
                        '<div class="notice notice-error"><p>❌ Error: ' + xhr.responseText + '</p></div>'
                    );
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX handler for test event firing
 */
public static function ajax_fire_test_event() {
    check_ajax_referer('super_dev_tools', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    $event_id = sanitize_text_field($_POST['event_id']);
    $form_id = absint($_POST['form_id']);

    // Mock context data
    $context = array(
        'form_id' => $form_id,
        'entry_id' => 999,
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'user_ip' => '127.0.0.1'
    );

    // Fire event
    $results = SUPER_Trigger_Executor::fire_event($event_id, $context);

    wp_send_json_success(array(
        'event_id' => $event_id,
        'context' => $context,
        'triggers_executed' => count($results),
        'results' => $results
    ));
}
```

#### B. Event Log Viewer
Show recent event firings in real-time

```php
/**
 * Event Log Viewer
 */
private static function render_event_log_viewer() {
    global $wpdb;

    // Get recent logs (last 50)
    $logs = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}superforms_trigger_logs
        ORDER BY executed_at DESC
        LIMIT 50"
    );

    ?>
    <div class="super-dev-tools-section">
        <h2>📋 Event Execution Log</h2>
        <p>Recent event firings and trigger executions</p>

        <button class="button" id="refresh-event-log">Refresh</button>
        <button class="button" id="clear-event-log">Clear All Logs</button>

        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Event</th>
                    <th>Form</th>
                    <th>Entry</th>
                    <th>Trigger</th>
                    <th>Action</th>
                    <th>Status</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody id="event-log-tbody">
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->executed_at); ?></td>
                    <td><code><?php echo esc_html($log->event_id); ?></code></td>
                    <td><?php echo absint($log->form_id); ?></td>
                    <td><?php echo absint($log->entry_id); ?></td>
                    <td>#<?php echo absint($log->trigger_id); ?></td>
                    <td>#<?php echo absint($log->action_id); ?></td>
                    <td>
                        <?php if ($log->status === 'success'): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: green;"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-dismiss" style="color: red;"></span>
                        <?php endif; ?>
                        <?php echo esc_html($log->status); ?>
                    </td>
                    <td><?php echo number_format($log->execution_time_ms, 2); ?>ms</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
```

#### C. Trigger Testing Tool
Test specific trigger configuration

```php
/**
 * Trigger Testing Tool
 */
private static function render_trigger_tester() {
    ?>
    <div class="super-dev-tools-section">
        <h2>🎯 Trigger Tester</h2>
        <p>Test a specific trigger with mock data</p>

        <label>
            Trigger ID:
            <input type="number" id="test-trigger-id" min="1">
        </label>

        <label>
            Mock Entry Data (JSON):
            <textarea id="test-entry-data" rows="10" style="width: 100%; font-family: monospace;">
{
    "email": {"value": "test@example.com"},
    "name": {"value": "John Doe"},
    "country": {"value": "US"}
}
            </textarea>
        </label>

        <button class="button button-primary" id="test-trigger-execution">
            Test Trigger
        </button>

        <div id="trigger-test-results" style="margin-top: 20px;"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#test-trigger-execution').on('click', function() {
            var triggerId = parseInt($('#test-trigger-id').val());
            var entryData = $('#test-entry-data').val();

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'super_dev_test_trigger',
                    trigger_id: triggerId,
                    entry_data: entryData,
                    nonce: '<?php echo wp_create_nonce('super_dev_tools'); ?>'
                },
                success: function(response) {
                    var html = '<h3>Test Results</h3>';
                    html += '<p><strong>Conditions Met:</strong> ' + (response.data.conditions_met ? 'Yes ✅' : 'No ❌') + '</p>';
                    html += '<p><strong>Actions Executed:</strong> ' + response.data.actions_executed + '</p>';
                    html += '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
                    $('#trigger-test-results').html(html);
                },
                error: function(xhr) {
                    $('#trigger-test-results').html(
                        '<div class="notice notice-error"><p>Error: ' + xhr.responseText + '</p></div>'
                    );
                }
            });
        });
    });
    </script>
    <?php
}
```

---

### 3. Built-in Actions Implementation 🔧 MEDIUM PRIORITY

**Goal:** Implement the core action classes that triggers can execute

**Priority Order:**

#### Phase 2.1: Essential Actions
1. **`send_email`** - Email notifications (most requested)
2. **`webhook`** - HTTP POST to external URL
3. **`log_message`** - Debug logging

#### Phase 2.2: Entry Management Actions
4. **`update_entry_status`** - Change entry status
5. **`update_entry_field`** - Modify entry data
6. **`delete_entry`** - Delete entry

#### Phase 2.3: WordPress Integration
7. **`create_post`** - Create WordPress post
8. **`update_post_meta`** - Modify post metadata
9. **`update_user_meta`** - Modify user metadata

#### Phase 2.4: Advanced Actions
10. **`conditional_action`** - If/then/else logic
11. **`run_hook`** - Fire WordPress action hook
12. **`delay_execution`** - Schedule for later (Action Scheduler)

**Implementation Template:**

```php
<?php
/**
 * Send Email Action
 *
 * Location: /src/includes/triggers/actions/class-action-send-email.php
 */
class SUPER_Action_Send_Email extends SUPER_Trigger_Action_Base {

    public function get_id() {
        return 'send_email';
    }

    public function get_label() {
        return __('Send Email', 'super-forms');
    }

    public function get_group() {
        return 'notifications';
    }

    public function get_description() {
        return __('Send an email notification with form data', 'super-forms');
    }

    public function get_settings_schema() {
        return array(
            array(
                'name' => 'to',
                'label' => __('To', 'super-forms'),
                'type' => 'text',
                'default' => '{admin_email}',
                'required' => true
            ),
            array(
                'name' => 'subject',
                'label' => __('Subject', 'super-forms'),
                'type' => 'text',
                'default' => 'New form submission',
                'required' => true
            ),
            array(
                'name' => 'body',
                'label' => __('Message', 'super-forms'),
                'type' => 'textarea',
                'default' => '{all_fields}',
                'required' => true
            )
        );
    }

    public function execute($data, $config, $context) {
        // Replace tags
        $to = $this->replace_tags($config['to'], $data, $context);
        $subject = $this->replace_tags($config['subject'], $data, $context);
        $body = $this->replace_tags($config['body'], $data, $context);

        // Validate email
        if (!is_email($to)) {
            return new WP_Error('invalid_email', 'Invalid recipient email address');
        }

        // Send email
        $sent = wp_mail($to, $subject, $body);

        if (!$sent) {
            return new WP_Error('send_failed', 'Failed to send email');
        }

        return array(
            'success' => true,
            'message' => 'Email sent successfully',
            'to' => $to,
            'subject' => $subject
        );
    }

    public function supports_scheduling() {
        return true; // Can be delayed via Action Scheduler
    }
}
```

---

### 4. Admin UI Implementation 🎨 MEDIUM PRIORITY

**Goal:** Build the dedicated "Triggers" admin page

**Phase 1.5 Requirements:**

#### A. Admin Menu Registration
```php
add_action('admin_menu', function() {
    add_submenu_page(
        'super_forms',                          // Parent slug
        __('Triggers', 'super-forms'),          // Page title
        __('Triggers', 'super-forms'),          // Menu title
        'manage_options',                       // Capability
        'super_triggers',                       // Menu slug
        array('SUPER_Triggers_Admin', 'render_page') // Callback
    );
});
```

#### B. Triggers List View (React-based)
- Table with columns: Name, Event, Scope, Status, Last Run, Actions
- Filters: Event type, Scope, Status (active/inactive)
- Bulk actions: Enable/Disable/Delete
- Search by trigger name
- "Add New Trigger" button

#### C. Trigger Editor (React-based)
- Event selection dropdown
- Scope selection (form/global/user/role)
- Condition builder (visual query builder)
- Action builder (add multiple actions in sequence)
- Enable/disable toggle
- Execution order
- Save/Cancel buttons

#### D. REST API Consumption
All UI operations via REST API:
- `GET /wp-json/super-forms/v1/triggers` - List triggers
- `POST /wp-json/super-forms/v1/triggers` - Create trigger
- `PUT /wp-json/super-forms/v1/triggers/{id}` - Update trigger
- `DELETE /wp-json/super-forms/v1/triggers/{id}` - Delete trigger
- `GET /wp-json/super-forms/v1/events` - List available events
- `GET /wp-json/super-forms/v1/action-types` - List available actions

---

## Recommended Implementation Order

### Week 1: Testing & Validation ⚡
1. ✅ **Day 1-2:** Write unit tests for event firing (PRIORITY)
2. ✅ **Day 2-3:** Add Developer Tools enhancements (event tester, log viewer)
3. ✅ **Day 3-4:** Write integration tests for trigger execution
4. ✅ **Day 4-5:** Performance testing and optimization

### Week 2: Core Actions 🔧
1. **Day 1:** Implement `send_email` action
2. **Day 2:** Implement `webhook` action
3. **Day 3:** Implement `log_message` action
4. **Day 4:** Test all 3 actions end-to-end
5. **Day 5:** Documentation for action developers

### Week 3: Admin UI Foundation 🎨
1. **Day 1:** Set up React build environment
2. **Day 2:** Build triggers list view
3. **Day 3:** Build trigger editor (basic)
4. **Day 4:** Integrate with REST API
5. **Day 5:** Testing and refinement

### Week 4: Entry Management Actions 🔧
1. **Day 1-2:** Implement `update_entry_status`, `update_entry_field`
2. **Day 3:** Implement `delete_entry` action
3. **Day 4-5:** Testing and documentation

---

## Testing Checklist Before Production

### Event Firing Tests
- [ ] Normal submission fires all 4 events in correct order
- [ ] Spam detection fires only `form.spam_detected`
- [ ] Validation failure fires only `form.validation_failed`
- [ ] Duplicate detection fires events then deletes entry
- [ ] Entry update fires `entry.updated` and `entry.saved`
- [ ] File upload fires `file.uploaded` for each file
- [ ] Status change fires `entry.status_changed` only when changed
- [ ] All events include correct context data
- [ ] WordPress action hooks fire correctly
- [ ] Events fire with <2ms overhead when no triggers exist

### Trigger Execution Tests
- [ ] Trigger with matching event executes
- [ ] Trigger with non-matching event does NOT execute
- [ ] Conditions filter execution correctly
- [ ] Multiple triggers execute in order
- [ ] Disabled triggers do NOT execute
- [ ] Scope filtering works (form/global/user/role)
- [ ] Multiple actions execute in sequence
- [ ] Failed actions return WP_Error
- [ ] Execution logs are created
- [ ] Tag replacement works in all actions

### Performance Tests
- [ ] 100 triggers lookup completes in <20ms
- [ ] Complex conditions (5 levels deep) evaluate in <10ms
- [ ] Event firing overhead <2ms with no triggers
- [ ] Database queries optimized (no N+1 queries)
- [ ] Action execution logs don't slow down submissions

### Security Tests
- [ ] Only `manage_options` users can create triggers
- [ ] Scope isolation prevents cross-form trigger execution
- [ ] Tag replacement sanitizes output
- [ ] SQL injection protection in DAL
- [ ] XSS protection in admin UI
- [ ] CSRF tokens in all forms

---

## Documentation Requirements

### Developer Documentation
- [ ] Event firing API reference
- [ ] Creating custom actions tutorial
- [ ] Creating custom events tutorial
- [ ] Tag replacement system documentation
- [ ] Condition builder syntax reference
- [ ] REST API endpoint documentation

### User Documentation
- [ ] Triggers overview and concepts
- [ ] Creating your first trigger walkthrough
- [ ] Common trigger examples (use cases)
- [ ] Troubleshooting guide
- [ ] Performance best practices

---

## Success Metrics

**Phase 1 (Event Firing):**
- ✅ All 10 events fire correctly
- ✅ Event context data complete and accurate
- ✅ <2ms overhead per event
- ✅ 100% code coverage for event firing

**Phase 2 (Actions):**
- 5+ built-in actions implemented
- 80%+ test coverage for actions
- Action execution <100ms for non-network actions
- Clear error messages for failed actions

**Phase 3 (Admin UI):**
- Trigger creation takes <2 minutes
- UI responsive and intuitive
- No JavaScript errors in console
- Mobile-friendly interface

---

## Risk Mitigation

### Potential Issues and Solutions

**Issue 1: Performance degradation with many triggers**
- Solution: Implement caching for trigger lookups
- Solution: Add database indexes on key columns
- Solution: Lazy load action classes

**Issue 2: Complex conditions cause slow evaluation**
- Solution: Set complexity limit (max depth = 5)
- Solution: Cache condition evaluation results
- Solution: Add timeout for condition evaluation

**Issue 3: Failed actions block form submission**
- Solution: Catch all exceptions in executor
- Solution: Log errors but don't block submission
- Solution: Provide clear error messages

**Issue 4: Triggers executed multiple times**
- Solution: Add execution deduplication check
- Solution: Track executed triggers in session
- Solution: Add "execute once per submission" flag

---

## Next Immediate Action

**RECOMMENDED STARTING POINT:**

1. **Add Developer Tools Event Tester** (2-3 hours)
   - Allows immediate testing of event firing
   - Visual confirmation that events work
   - Easy to demonstrate to stakeholders

2. **Write Event Firing Unit Tests** (4-6 hours)
   - Ensure all 10 events fire correctly
   - Catch regressions early
   - Required for CI/CD pipeline

3. **Implement `send_email` Action** (4-6 hours)
   - Most requested feature
   - Relatively simple to implement
   - Demonstrates complete trigger flow

**Estimated Total Time to MVP:** 3-4 weeks
**Estimated Total Time to Production-Ready:** 6-8 weeks

---

## Questions to Answer Before Proceeding

1. **Should we implement admin UI before or after all core actions?**
   - Recommendation: Implement 3 core actions first (send_email, webhook, log_message), THEN build UI

2. **Should we use React or vanilla JS for admin UI?**
   - Recommendation: React (consistent with Email Builder v2, better component reusability)

3. **Should we migrate old triggers from form meta to new tables?**
   - Answer: NO - old system unreleased, no customer data to migrate

4. **Should we implement Action Scheduler async execution in Phase 1 or Phase 2?**
   - Recommendation: Phase 2 (current sync execution sufficient for testing)

5. **Should we add event firing to payment webhooks now or later?**
   - Recommendation: Later (Phase 6) - focus on core form events first

---

## Resources Needed

### Development Tools
- PHPUnit for unit testing
- Jest for JavaScript testing
- React development environment
- Webpack/Babel configuration

### Documentation Tools
- Markdown documentation
- API documentation generator
- Screenshot/video capture tools

### Testing Infrastructure
- Staging environment for integration tests
- Load testing tools for performance tests
- Browser testing (Chrome, Firefox, Safari)

---

## Conclusion

**Current Status:** Event firing complete and deployed ✅
**Next Priority:** Testing infrastructure + Developer Tools enhancement
**Timeline:** 3-4 weeks to MVP, 6-8 weeks to production-ready
**Risk Level:** Low (unreleased feature, no backward compatibility required)

**Recommended Action:** Start with Developer Tools event tester, then write unit tests, then implement first 3 core actions.
