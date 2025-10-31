---
name: 02-backwards-compat-tests
status: pending
created: 2025-10-31
---

# Test External Integration Backwards Compatibility

## Problem/Goal

Phase 29 research identified critical external integrations (Zapier, Mailchimp, Stripe, PayPal) that depend on entry data format. Phase 11 revealed CSV exports must remain byte-for-byte identical. We must verify that ALL external integrations continue working without modification after EAV migration.

**Critical Requirement:**
ZERO breaking changes for external systems. Any integration that works before migration MUST work identically after migration.

## Success Criteria

- [ ] Zapier webhook integration tests pass (payload format unchanged)
- [ ] Mailchimp integration tests pass (field mapping unchanged)
- [ ] Stripe webhook handler tests pass (entry data accessible)
- [ ] PayPal IPN handler tests pass (transaction linking works)
- [ ] CSV export byte-for-byte identical before/after migration
- [ ] JSON export format unchanged
- [ ] WooCommerce order creation works identically
- [ ] Front-end posting creates posts with same content
- [ ] Email template rendering produces identical output
- [ ] PDF generation produces byte-for-byte identical PDFs

## Test Files to Create

### 1. Zapier Webhook Integration
**File**: `test/phpunit/tests/integration/test-zapier-integration.php`

```php
<?php
class Test_Zapier_Integration extends SUPER_Test_Helpers {

    public function test_zapier_webhook_payload_format() {
        // Create test entry
        $entry_data = array(
            'name' => array('value' => 'John Doe'),
            'email' => array('value' => 'john@example.com'),
        );
        $entry_id = $this->create_test_entry($entry_data);

        // Simulate Zapier webhook payload generation
        $settings = array(
            'zapier_webhook_url' => 'https://hooks.zapier.com/test',
        );

        // Capture what would be sent to Zapier
        add_filter('pre_http_request', array($this, 'capture_zapier_payload'), 10, 3);

        do_action('super_before_sending_zapier_webhook', array(
            'entry_id' => $entry_id,
            'data' => SUPER_Data_Access::get_entry_data($entry_id),
            'settings' => $settings,
        ));

        // Verify payload structure
        $this->assertArrayHasKey('data', $this->captured_payload);
        $this->assertEquals('John Doe', $this->captured_payload['data']['name']);
        $this->assertEquals('john@example.com', $this->captured_payload['data']['email']);
    }

    public $captured_payload = array();

    public function capture_zapier_payload($response, $args, $url) {
        if (strpos($url, 'zapier.com') !== false) {
            $this->captured_payload = json_decode($args['body'], true);
        }
        return array('response' => array('code' => 200));
    }
}
```

### 2. Mailchimp Integration
**File**: `test/phpunit/tests/integration/test-mailchimp-integration.php`

```php
<?php
class Test_Mailchimp_Integration extends SUPER_Test_Helpers {

    public function test_mailchimp_field_mapping() {
        $entry_data = array(
            'email' => array('value' => 'subscriber@example.com'),
            'first_name' => array('value' => 'Jane'),
            'last_name' => array('value' => 'Smith'),
        );
        $entry_id = $this->create_test_entry($entry_data);

        // Mailchimp field mapping configuration
        $settings = array(
            'mailchimp_map_email' => 'email',
            'mailchimp_map_fname' => 'first_name',
            'mailchimp_map_lname' => 'last_name',
        );

        // Get mapped data
        $data = SUPER_Data_Access::get_entry_data($entry_id);
        $mapped = array(
            'email' => $data[$settings['mailchimp_map_email']]['value'],
            'FNAME' => $data[$settings['mailchimp_map_fname']]['value'],
            'LNAME' => $data[$settings['mailchimp_map_lname']]['value'],
        );

        $this->assertEquals('subscriber@example.com', $mapped['email']);
        $this->assertEquals('Jane', $mapped['FNAME']);
        $this->assertEquals('Smith', $mapped['LNAME']);
    }
}
```

### 3. Stripe Webhook Handler
**File**: `test/phpunit/tests/integration/test-stripe-integration.php`

```php
<?php
class Test_Stripe_Integration extends SUPER_Test_Helpers {

    public function test_stripe_webhook_entry_lookup() {
        // Create entry with Stripe session ID
        $entry_id = $this->create_test_entry(array(
            'email' => array('value' => 'customer@example.com'),
        ));

        add_post_meta($entry_id, '_super_stripe_session_id', 'cs_test_123');

        // Simulate Stripe webhook
        $stripe_event = array(
            'type' => 'checkout.session.completed',
            'data' => array(
                'object' => array(
                    'id' => 'cs_test_123',
                    'payment_status' => 'paid',
                ),
            ),
        );

        // Lookup entry by Stripe session ID
        $found_entry = get_posts(array(
            'post_type' => 'super_contact_entry',
            'meta_key' => '_super_stripe_session_id',
            'meta_value' => 'cs_test_123',
        ));

        $this->assertCount(1, $found_entry);

        // Verify entry data accessible
        $data = SUPER_Data_Access::get_entry_data($found_entry[0]->ID);
        $this->assertEquals('customer@example.com', $data['email']['value']);
    }
}
```

### 4. PayPal IPN Handler
**File**: `test/phpunit/tests/integration/test-paypal-integration.php`

```php
<?php
class Test_PayPal_Integration extends SUPER_Test_Helpers {

    public function test_paypal_ipn_entry_update() {
        // Create entry with PayPal transaction
        $entry_id = $this->create_test_entry(array(
            'email' => array('value' => 'buyer@example.com'),
        ));

        add_post_meta($entry_id, '_super_paypal_order_id', 'ORDER123');

        // Simulate PayPal IPN update
        $_POST['txn_id'] = 'TXN456';
        $_POST['payment_status'] = 'Completed';
        $_POST['custom'] = $entry_id; // Entry ID passed in custom field

        // Verify entry can be updated
        $data = SUPER_Data_Access::get_entry_data($entry_id);
        $data['paypal_txn_id'] = array('value' => $_POST['txn_id']);
        SUPER_Data_Access::save_entry_data($entry_id, $data);

        // Verify update persisted
        $updated = SUPER_Data_Access::get_entry_data($entry_id);
        $this->assertEquals('TXN456', $updated['paypal_txn_id']['value']);
    }
}
```

### 5. CSV Export Compatibility
**File**: `test/phpunit/tests/integration/test-csv-export.php`

```php
<?php
class Test_CSV_Export extends SUPER_Test_Helpers {

    public function test_csv_export_byte_identical() {
        // Create entry with known data
        $entry_data = array(
            'name' => array('value' => 'Export Test', 'label' => 'Name'),
            'email' => array('value' => 'export@test.com', 'label' => 'Email'),
            'phone' => array('value' => '555-1234', 'label' => 'Phone'),
        );
        $entry_id = $this->create_test_entry($entry_data);

        // Export using old method (serialized)
        $old_csv = $this->generate_csv_export_old($entry_id);

        // Simulate migration
        // (In actual test, would call migration)

        // Export using new method (EAV)
        $new_csv = $this->generate_csv_export_new($entry_id);

        // CSV must be byte-for-byte identical
        $this->assertEquals($old_csv, $new_csv);
    }

    public function test_csv_column_order_preserved() {
        $entry_id = $this->create_test_entry(array(
            'field1' => array('value' => 'Value 1'),
            'field2' => array('value' => 'Value 2'),
            'field3' => array('value' => 'Value 3'),
        ));

        $csv = $this->generate_csv_export_new($entry_id);
        $rows = str_getcsv($csv, "\n");
        $columns = str_getcsv($rows[0]);

        // Column order must match form field order
        $this->assertEquals(array('field1', 'field2', 'field3'), $columns);
    }

    private function generate_csv_export_old($entry_id) {
        $data = unserialize(get_post_meta($entry_id, '_super_contact_entry_data', true));
        ob_start();
        fputcsv(STDOUT, array_keys($data));
        fputcsv(STDOUT, array_column($data, 'value'));
        return ob_get_clean();
    }

    private function generate_csv_export_new($entry_id) {
        $data = SUPER_Data_Access::get_entry_data($entry_id);
        ob_start();
        fputcsv(STDOUT, array_keys($data));
        fputcsv(STDOUT, array_column($data, 'value'));
        return ob_get_clean();
    }
}
```

### 6. WooCommerce Integration
**File**: `test/phpunit/tests/integration/test-woocommerce-integration.php`

```php
<?php
class Test_WooCommerce_Integration extends SUPER_Test_Helpers {

    public function test_woocommerce_order_creation() {
        if (!class_exists('WooCommerce')) {
            $this->markTestSkipped('WooCommerce not installed');
        }

        $entry_data = array(
            'customer_email' => array('value' => 'customer@example.com'),
            'product_id' => array('value' => '123'),
            'quantity' => array('value' => '2'),
        );
        $entry_id = $this->create_test_entry($entry_data);

        // Simulate WooCommerce order creation from entry
        $data = SUPER_Data_Access::get_entry_data($entry_id);

        // Verify order data accessible
        $this->assertEquals('customer@example.com', $data['customer_email']['value']);
        $this->assertEquals('123', $data['product_id']['value']);
    }
}
```

### 7. Email Template Rendering
**File**: `test/phpunit/tests/integration/test-email-templates.php`

```php
<?php
class Test_Email_Templates extends SUPER_Test_Helpers {

    public function test_email_template_tag_replacement() {
        $entry_data = array(
            'name' => array('value' => 'John Doe'),
            'email' => array('value' => 'john@example.com'),
        );
        $entry_id = $this->create_test_entry($entry_data);

        // Email template with tags
        $template = 'Hello {name}, your email is {email}';

        // Old rendering method
        $old_data = unserialize(get_post_meta($entry_id, '_super_contact_entry_data', true));
        $old_output = SUPER_Common::email_tags($template, $old_data);

        // New rendering method
        $new_data = SUPER_Data_Access::get_entry_data($entry_id);
        $new_output = SUPER_Common::email_tags($template, $new_data);

        // Output must be identical
        $this->assertEquals($old_output, $new_output);
        $this->assertEquals('Hello John Doe, your email is john@example.com', $new_output);
    }
}
```

### 8. PDF Generation
**File**: `test/phpunit/tests/integration/test-pdf-generation.php`

```php
<?php
class Test_PDF_Generation extends SUPER_Test_Helpers {

    public function test_pdf_merge_field_replacement() {
        $entry_data = array(
            'contract_name' => array('value' => 'John Smith'),
            'contract_date' => array('value' => '2025-10-31'),
        );
        $entry_id = $this->create_test_entry($entry_data);

        // PDF template with merge fields
        $pdf_template = 'Contract for {contract_name} dated {contract_date}';

        // Old rendering
        $old_data = unserialize(get_post_meta($entry_id, '_super_contact_entry_data', true));
        $old_content = SUPER_Common::email_tags($pdf_template, $old_data);

        // New rendering
        $new_data = SUPER_Data_Access::get_entry_data($entry_id);
        $new_content = SUPER_Common::email_tags($pdf_template, $new_data);

        // Content must be identical
        $this->assertEquals($old_content, $new_content);
    }
}
```

### 9. Front-end Posting
**File**: `test/phpunit/tests/integration/test-frontend-posting.php`

```php
<?php
class Test_Frontend_Posting extends SUPER_Test_Helpers {

    public function test_post_creation_from_entry() {
        $entry_data = array(
            'post_title' => array('value' => 'My Blog Post'),
            'post_content' => array('value' => 'This is the content'),
            'post_author' => array('value' => '1'),
        );
        $entry_id = $this->create_test_entry($entry_data);

        // Get data for post creation
        $data = SUPER_Data_Access::get_entry_data($entry_id);

        // Create post from entry data
        $post_id = wp_insert_post(array(
            'post_title' => $data['post_title']['value'],
            'post_content' => $data['post_content']['value'],
            'post_author' => $data['post_author']['value'],
            'post_status' => 'publish',
        ));

        $this->assertGreaterThan(0, $post_id);

        // Verify post created correctly
        $post = get_post($post_id);
        $this->assertEquals('My Blog Post', $post->post_title);
    }
}
```

### 10. Third-Party Code Compatibility
**File**: `test/phpunit/tests/integration/test-third-party-compat.php`

```php
<?php
class Test_Third_Party_Compat extends SUPER_Test_Helpers {

    public function test_direct_get_post_meta_still_works() {
        $entry_data = array(
            'field1' => array('value' => 'Test Value'),
        );
        $entry_id = $this->create_test_entry($entry_data);

        // Simulate third-party code directly accessing meta
        $serialized = get_post_meta($entry_id, '_super_contact_entry_data', true);

        // Must still be accessible (preserved during migration)
        $this->assertNotEmpty($serialized);

        $data = unserialize($serialized);
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('Test Value', $data['field1']['value']);
    }
}
```

## Test Execution Plan

### 1. Run Before Migration
```bash
composer test -- --testsuite integration
```

Capture all output as baseline.

### 2. Run Migration
Execute full EAV migration on test database.

### 3. Run After Migration
```bash
composer test -- --testsuite integration
```

All tests must pass with identical output.

### 4. Compare Outputs
```bash
diff before-migration.log after-migration.log
```

Must show ZERO differences (except timestamps).

## Integration Test Configuration

**File**: `test/phpunit/phpunit.xml` (add testsuite)
```xml
<testsuite name="integration">
    <directory>./tests/integration</directory>
</testsuite>
```

## Mock External Services

Create mocks for external APIs to avoid real API calls during tests:

**File**: `test/phpunit/tests/mocks/class-mock-zapier.php`
```php
<?php
class Mock_Zapier_API {
    public static $sent_webhooks = array();

    public static function send_webhook($url, $payload) {
        self::$sent_webhooks[] = array(
            'url' => $url,
            'payload' => $payload,
            'timestamp' => time(),
        );
        return array('success' => true);
    }

    public static function reset() {
        self::$sent_webhooks = array();
    }
}
```

## Dependencies

- Subtask 01 (test suite foundation) must be complete
- WooCommerce (optional, tests skipped if not installed)
- Stripe PHP SDK (optional, for Stripe tests)

## Estimated Effort

**4-5 days**
- Day 1: Zapier, Mailchimp integration tests
- Day 2: Stripe, PayPal integration tests
- Day 3: CSV export, email template tests
- Day 4: WooCommerce, frontend posting tests
- Day 5: Third-party compatibility, validation

## Related Research

- Phase 29: API & Webhooks (all external integration points documented)
- Phase 11: CSV Export (byte-for-byte identical requirement)
- Phase 8: Email System (template tag rendering)
- Phase 10: PDF Generation (merge field compatibility)

## Notes

These integration tests are CRITICAL for production deployment confidence. If ANY integration test fails after migration, the migration MUST be rolled back and fixed before re-attempting.
