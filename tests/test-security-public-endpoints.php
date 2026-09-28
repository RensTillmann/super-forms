<?php
/**
 * Public (logged-out reachable) AJAX endpoints and the PayPal IPN listener:
 * WooCommerce order search / order populate, unique code preview and reservation, IPN verification.
 */
class Test_Super_PayPal_Input_Stream {
    public static $body = '';
    public $context;
    private $offset = 0;

    public function stream_open( $path, $mode, $options, &$opened_path ) {
        return $path === 'php://input';
    }
    public function stream_read( $count ) {
        $result = substr( self::$body, $this->offset, $count );
        $this->offset += strlen( $result );
        return $result;
    }
    public function stream_eof() {
        return $this->offset >= strlen( self::$body );
    }
    public function stream_stat() {
        return array();
    }
}

class Test_Security_Public_Endpoints extends Super_Forms_Upload_Security_Test_Case {

    private $customer_id = 0;
    private $other_customer_id = 0;
    private $staff_id = 0;
    private $orders = array();
    private $original_get = array();

    public function set_up() {
        parent::set_up();
        $this->original_get = $_GET;
        $this->customer_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $this->other_customer_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $this->staff_id = self::factory()->user->create( array( 'role' => 'editor' ) );
        get_user_by( 'id', $this->staff_id )->add_cap( 'edit_shop_orders' );
        $this->orders['mine'] = $this->create_order( $this->customer_id, 'alice@example.test', 'Alice', '+31600000001' );
        $this->orders['other'] = $this->create_order( $this->other_customer_id, 'bob@example.test', 'Bob', '+31600000002' );
        wp_set_current_user( 0 );
    }

    public function tear_down() {
        $_GET = $this->original_get;
        parent::tear_down();
    }

    private function create_order( $customer_id, $email, $first_name, $phone ) {
        $order_id = self::factory()->post->create( array(
            'post_type' => 'shop_order',
            'post_status' => 'wc-processing',
            'post_title' => 'Order',
        ) );
        update_post_meta( $order_id, '_customer_user', (string) $customer_id );
        update_post_meta( $order_id, '_billing_email', $email );
        update_post_meta( $order_id, '_billing_first_name', $first_name );
        update_post_meta( $order_id, '_billing_last_name', 'Example' );
        update_post_meta( $order_id, '_billing_phone', $phone );
        return $order_id;
    }

    private function order_search_form( $overrides=array() ) {
        $data = array_merge( array(
            'name' => 'order_lookup',
            'wc_order_search' => 'true',
            'wc_order_search_method' => 'contains',
            'wc_order_search_filterby' => "_billing_email\n_billing_first_name",
            'wc_order_search_return_label' => 'Order #{ID} {_billing_email}',
            'wc_order_search_return_value' => 'ID;_billing_email',
            'wc_order_search_populate' => 'true',
        ), $overrides );
        return $this->create_form( 'publish', array( array( 'tag' => 'text', 'group' => 'form_elements', 'data' => $data ) ) );
    }

    private function set_post( $post ) {
        $_POST = $post;
        $_REQUEST = $post;
    }

    private function search( $form_id, $value, $extra=array(), $with_nonce=true ) {
        $post = array_merge( array(
            'form_id' => (string) $form_id,
            'field_name' => 'order_lookup',
            'value' => $value,
        ), $extra );
        if( $with_nonce ) {
            $post['nonce'] = wp_create_nonce( 'super_create_nonce_' . $form_id );
        }
        $this->set_post( $post );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'search_wc_orders' ) );
        $this->assertTrue( $result['exited'] );
        return $result['output'];
    }

    // ---- WooCommerce order search -------------------------------------------------------

    public function test_guest_order_search_returns_no_orders() {
        $form_id = $this->order_search_form();
        $this->assertSame( '', $this->search( $form_id, 'example.test' ) );
    }

    public function test_order_search_without_nonce_returns_nothing() {
        $form_id = $this->order_search_form();
        wp_set_current_user( $this->staff_id );
        $this->assertSame( '', $this->search( $form_id, 'example.test', array(), false ) );
    }

    public function test_order_search_requires_a_saved_order_search_field() {
        $form_id = $this->order_search_form( array( 'wc_order_search' => '' ) );
        wp_set_current_user( $this->staff_id );
        $this->assertSame( '', $this->search( $form_id, 'example.test' ) );
    }

    public function test_customer_order_search_only_lists_own_orders() {
        $form_id = $this->order_search_form();
        wp_set_current_user( $this->customer_id );
        $output = $this->search( $form_id, 'example.test' );
        $this->assertStringContainsString( 'alice@example.test', $output );
        $this->assertStringNotContainsString( 'bob@example.test', $output );
    }

    public function test_staff_order_search_lists_matching_orders() {
        $form_id = $this->order_search_form();
        wp_set_current_user( $this->staff_id );
        $output = $this->search( $form_id, 'example.test' );
        $this->assertStringContainsString( 'alice@example.test', $output );
        $this->assertStringContainsString( 'bob@example.test', $output );
    }

    public function test_order_search_ignores_request_supplied_query_settings() {
        $form_id = $this->order_search_form();
        wp_set_current_user( $this->staff_id );
        $output = $this->search( $form_id, 'example.test', array(
            'return_value' => '_billing_phone',
            'return_label' => '{_billing_phone}',
            'filterby' => '_billing_phone',
            'status' => "any') OR ('1'='1",
            'method' => 'equals',
        ) );
        $this->assertStringContainsString( 'alice@example.test', $output );
        $this->assertStringNotContainsString( '+3160000000', $output );
    }

    public function test_order_search_value_is_bound_not_interpolated() {
        $form_id = $this->order_search_form();
        wp_set_current_user( $this->staff_id );
        $this->assertSame( '', $this->search( $form_id, "zz' OR '1'='1" ) );
    }

    // ---- WooCommerce order populate (click on a search result) --------------------------

    /** Browser session cookie + stored session row (three records, as a live session carries). */
    private function seed_session() {
        $session_id = bin2hex( random_bytes( 32 ) );
        $_COOKIE['_sfs_id'] = $session_id;
        update_option( '_sfsdata_' . $session_id, array(
            'expires' => time() + HOUR_IN_SECONDS,
            'exp_var' => time() + HOUR_IN_SECONDS,
            'sf_test_session_anchor' => array( 'expires' => time() + HOUR_IN_SECONDS, 'exp_var' => time() + HOUR_IN_SECONDS, 'value' => 'anchor' ),
        ), false );
    }

    private function populate_order( $form_id, $order_id, $with_nonce=true ) {
        $this->seed_session();
        $capability = SUPER_Common::issue_public_populate_capability( array(
            'form_id' => $form_id,
            'field_name' => 'order_lookup',
            'method' => 'wc_order_id',
            'skip' => '',
            'result_scope' => 'wc_order_entry',
        ) );
        $this->assertIsString( $capability );
        $post = array(
            'form_id' => (string) $form_id,
            'field_name' => 'order_lookup',
            'method' => 'wc_order_id',
            'skip' => '',
            'capability' => $capability,
            'order_id' => (string) $order_id,
        );
        if( $with_nonce ) {
            $post['nonce'] = wp_create_nonce( 'super_create_nonce_' . $form_id );
        }
        $this->set_post( $post );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'populate_form_data' ) );
        $this->assertTrue( $result['exited'] );
        return json_decode( $result['output'], true );
    }

    public function test_contact_search_rejects_short_and_oversized_values_without_consuming_capability() {
        $form_id = $this->create_form('publish', array(
            array('tag' => 'text', 'data' => array('name' => 'lookup', 'enable_search' => 'true', 'search_method' => 'contains')),
            array('tag' => 'text', 'data' => array('name' => 'secret')),
        ));
        $entry_id = self::factory()->post->create(array(
            'post_type' => 'super_contact_entry', 'post_status' => 'super_unread',
            'post_parent' => $form_id, 'post_title' => 'ABC customer reference',
        ));
        update_post_meta($entry_id, '_super_contact_entry_data', array('secret' => array('value' => 'authorized-result')));
        $this->seed_session();
        foreach(array('', 'a', 'ab', 'é', 'éé', str_repeat('x', 201)) as $value) {
            $capability = SUPER_Common::issue_public_populate_capability(array(
                'form_id' => $form_id, 'field_name' => 'lookup', 'method' => 'contains',
                'skip' => '', 'result_scope' => 'contact_entry',
            ));
            $this->assertIsString($capability);
            $post = array('form_id' => $form_id, 'field_name' => 'lookup', 'method' => 'contains',
                'skip' => '', 'capability' => $capability, 'value' => $value,
                'nonce' => wp_create_nonce('super_create_nonce_' . $form_id));
            $this->set_post($post);
            $result = $this->run_dying_handler(array('SUPER_Ajax', 'populate_form_data'));
            $this->assertSame(0, $result['status'], $result['output']);
            $this->assertSame(array('_super_capability_rejected' => true), json_decode($result['output'], true));
            $post['value'] = 'ABC';
            $this->set_post($post);
            $result = $this->run_dying_handler(array('SUPER_Ajax', 'populate_form_data'));
            $this->assertSame(0, $result['status'], $result['output']);
            $decoded = json_decode($result['output'], true);
            $this->assertSame('authorized-result', $decoded['secret']['value'], $result['output']);
        }
    }

    public function test_order_populate_rejects_missing_form_nonce_before_consuming_capability() {
        $form_id = $this->order_search_form();
        $elements = get_post_meta($form_id, '_super_elements', true);
        $elements[] = array('tag' => 'text', 'group' => 'form_elements', 'data' => array('name' => 'entry_secret'));
        update_post_meta($form_id, '_super_elements', $elements);
        $entry = self::factory()->post->create(array('post_type' => 'super_contact_entry', 'post_status' => 'super_unread', 'post_parent' => $form_id));
        update_post_meta($entry, '_super_contact_entry_wc_order_id', (string)$this->orders['mine']);
        SUPER_Data_Access::update_entry_data($entry, array('entry_secret' => array('name' => 'entry_secret', 'value' => 'authorized-order-data', 'type' => 'text')));
        wp_set_current_user($this->customer_id);
        $this->assertSame(array('_super_capability_rejected' => true), $this->populate_order($form_id, $this->orders['mine'], false));
        // Reuse the exact request capability and existing browser session; do not issue another.
        $post = $_POST;
        $post['nonce'] = wp_create_nonce('super_create_nonce_' . $form_id);
        $this->set_post($post);
        $result = $this->run_dying_handler(array('SUPER_Ajax', 'populate_form_data'));
        $this->assertSame(0, $result['status'], $result['output']);
        $response = json_decode($result['output'], true);
        $this->assertSame('authorized-order-data', $response['entry_secret']['value'], $result['output']);
    }

    public function test_guest_cannot_populate_from_an_order() {
        $form_id = $this->order_search_form();
        $response = $this->populate_order( $form_id, $this->orders['mine'] );
        $this->assertSame( array( '_super_capability_rejected' => true ), $response );
    }

    public function test_customer_cannot_populate_from_someone_elses_order() {
        $form_id = $this->order_search_form();
        wp_set_current_user( $this->customer_id );
        $response = $this->populate_order( $form_id, $this->orders['other'] );
        $this->assertSame( array( '_super_capability_rejected' => true ), $response );
    }

    public function test_customer_can_populate_from_own_order() {
        $form_id = $this->order_search_form();
        wp_set_current_user( $this->customer_id );
        $response = $this->populate_order( $form_id, $this->orders['mine'] );
        $this->assertIsArray( $response );
        $this->assertArrayNotHasKey( '_super_capability_rejected', $response );
    }

    private function render_with_order_in_url( $order_id ) {
        $form_id = $this->create_form( 'publish', array(
            array( 'tag' => 'text', 'group' => 'form_elements', 'inner' => array(), 'data' => array(
                'name' => 'order_lookup',
                'wc_order_search' => 'true',
                'wc_order_search_method' => 'equals',
                'wc_order_search_filterby' => '_billing_email',
                'wc_order_search_populate' => 'true',
            ) ),
            array( 'tag' => 'text', 'group' => 'form_elements', 'inner' => array(), 'data' => array( 'name' => 'entry_secret' ) ),
        ) );
        $entry_id = self::factory()->post->create( array( 'post_type' => 'super_contact_entry', 'post_status' => 'super_unread', 'post_parent' => $form_id, 'post_title' => 'Linked entry' ) );
        SUPER_Data_Access::update_entry_data( $entry_id, array(
            'entry_secret' => array( 'name' => 'entry_secret', 'value' => 'order-linked-secret-' . $order_id, 'type' => 'text' ),
        ) );
        update_post_meta( $entry_id, '_super_contact_entry_wc_order_id', $order_id );
        $_GET = array( 'order_lookup' => (string) $order_id );
        return SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
    }

    public function test_guest_render_with_order_in_url_does_not_prefill_the_linked_entry() {
        $output = $this->render_with_order_in_url( $this->orders['mine'] );
        $this->assertStringNotContainsString( 'order-linked-secret-', $output );
    }

    public function test_customer_render_prefills_only_from_own_order() {
        wp_set_current_user( $this->customer_id );
        $this->assertStringNotContainsString( 'order-linked-secret-', $this->render_with_order_in_url( $this->orders['other'] ) );
        $this->assertStringContainsString( 'order-linked-secret-' . $this->orders['mine'], $this->render_with_order_in_url( $this->orders['mine'] ) );
    }

    // ---- Unique code / invoice numbers ---------------------------------------------------

    private function code_form( $overrides=array() ) {
        $data = array_merge( array(
            'name' => 'invoice_code',
            'enable_random_code' => 'true',
            'code_length' => '0',
            'code_prefix' => 'INV-',
            'code_invoice' => 'true',
            'code_invoice_padding' => '4',
            'code_invoice_key' => 'unit',
        ), $overrides );
        return $this->create_form( 'publish', array( array( 'tag' => 'hidden', 'group' => 'form_elements', 'data' => $data ) ) );
    }

    private function code_option_count() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '\\_sf\\_unique\\_code-%' OR option_name LIKE '\\_sf\\_invoice\\_number%'" );
    }

    private function invoice_counter( $key ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", '_sf_invoice_number_' . $key ) );
    }

    public function test_request_supplied_code_settings_never_write_options() {
        $before = $this->code_option_count();
        $this->set_post( array(
            'submittingForm' => 'true',
            'codesettings' => wp_json_encode( array( 'invoice_key' => 'attacker', 'len' => '5', 'char' => '1', 'pre' => 'X', 'inv' => 'true', 'invp' => '4', 'suf' => '', 'upper' => '', 'lower' => '' ) ),
        ) );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'update_unique_code' ) );
        $this->assertTrue( $result['exited'] );
        $this->assertSame( '', $result['output'] );
        $this->assertSame( $before, $this->code_option_count() );
    }

    public function test_code_preview_uses_saved_settings_and_reserves_nothing() {
        $form_id = $this->code_form();
        SUPER_Common::generate_random_code( SUPER_Common::stored_code_fields( $form_id )['invoice_code'], true ); // counter at 1
        $counter = $this->invoice_counter( 'unit' );
        $codes = $this->code_option_count();
        $this->set_post( array(
            'form_id' => (string) $form_id,
            'field_name' => 'invoice_code',
            'nonce' => wp_create_nonce( 'super_create_nonce_' . $form_id ),
            'submittingForm' => 'true',
            'codesettings' => wp_json_encode( array( 'pre' => 'EVIL-', 'len' => '999999' ) ),
        ) );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'update_unique_code' ) );
        $this->assertMatchesRegularExpression( '/\AINV-\d{4}\z/', $result['output'] ); // saved prefix and padding, not the request's
        $this->assertSame( $counter, $this->invoice_counter( 'unit' ) );
        $this->assertSame( $codes, $this->code_option_count() );
    }

    public function test_submission_reserves_the_code_server_side_once() {
        $form_id = $this->code_form();
        $data = array(
            'invoice_code' => array( 'name' => 'invoice_code', 'value' => 'CLIENT-CHOSEN', 'type' => 'var' ),
            'email' => array( 'name' => 'email', 'value' => 'a@example.test', 'type' => 'var' ),
        );
        $reserved = $this->invoke_ajax_private( 'reserve_generated_codes', array( $form_id, $data ) );
        $this->assertSame( 'INV-0001', $reserved['invoice_code']['value'] );
        $this->assertSame( 'a@example.test', $reserved['email']['value'] );
        $this->assertSame( '1', $this->invoice_counter( 'unit' ) );
        $again = $this->invoke_ajax_private( 'reserve_generated_codes', array( $form_id, $data ) );
        $this->assertSame( 'INV-0002', $again['invoice_code']['value'] );
    }

    public function test_symbol_preview_returns_plain_ampersand_and_can_be_reserved() {
        $form_id = $this->code_form( array(
            'code_invoice' => '', 'code_length' => '1', 'code_characters' => '2',
            'code_prefix' => '', 'code_uppercase' => '', 'code_lowercase' => '',
        ) );
        $settings = SUPER_Common::stored_code_fields( $form_id )['invoice_code'];
        $seed = null;
        for( $i=0; $i<200; $i++ ) {
            mt_srand($i);
            if( SUPER_Common::generate_random_code($settings, false)==='&' ) {
                $seed = $i;
                break;
            }
        }
        $this->assertNotNull($seed);
        $this->set_post( array(
            'form_id' => (string)$form_id,
            'field_name' => 'invoice_code',
            'nonce' => wp_create_nonce( 'super_create_nonce_' . $form_id ),
        ) );
        try {
            mt_srand($seed);
            $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'update_unique_code' ) );
        } finally {
            mt_srand();
        }
        $this->assertSame( '&', $result['output'] );
        $reserved = $this->invoke_ajax_private( 'reserve_generated_codes', array(
            $form_id, array( 'invoice_code' => array( 'name' => 'invoice_code', 'value' => $result['output'], 'type' => 'var' ) ),
        ) );
        $this->assertSame( '&', $reserved['invoice_code']['value'] );
    }

    public function test_submission_keeps_a_previewed_invoice_number_it_can_claim() {
        $form_id = $this->code_form();
        $settings = SUPER_Common::stored_code_fields( $form_id )['invoice_code'];
        $preview = SUPER_Common::generate_random_code( $settings, false ); // what the browser shows and a PDF may print
        $data = array( 'invoice_code' => array( 'name' => 'invoice_code', 'value' => $preview, 'type' => 'var' ) );
        $reserved = $this->invoke_ajax_private( 'reserve_generated_codes', array( $form_id, $data ) );
        $this->assertSame( $preview, $reserved['invoice_code']['value'] );
        $this->assertSame( '1', $this->invoice_counter( 'unit' ) );
        // The same number cannot be claimed twice.
        $again = $this->invoke_ajax_private( 'reserve_generated_codes', array( $form_id, $data ) );
        $this->assertSame( 'INV-0002', $again['invoice_code']['value'] );
    }

    public function test_code_over_150_bytes_keeps_full_value_with_bounded_claim_key() {
        global $wpdb;
        $prefix = str_repeat( 'L', 151 );
        $form_id = $this->code_form( array(
            'code_invoice' => '', 'code_length' => '0', 'code_prefix' => $prefix,
        ) );
        $settings = SUPER_Common::stored_code_fields( $form_id )['invoice_code'];
        $preview = SUPER_Common::generate_random_code( $settings, false );
        $this->assertSame( $prefix, $preview );
        $reserved = $this->invoke_ajax_private( 'reserve_generated_codes', array(
            $form_id, array( 'invoice_code' => array( 'name' => 'invoice_code', 'value' => $preview, 'type' => 'var' ) ),
        ) );
        $this->assertSame( $preview, $reserved['invoice_code']['value'] );
        $claim_name = '_sf_unique_code_sha256-' . hash( 'sha256', $preview );
        $this->assertSame( $preview, $wpdb->get_var( $wpdb->prepare(
            "SELECT option_value FROM $wpdb->options WHERE option_name = %s", $claim_name
        ) ) );
        $this->assertFalse( SUPER_Common::claim_generated_code( $settings, $preview ) );
    }

    public function test_legacy_final_code_generation_never_returns_an_unclaimed_collision() {
        global $wpdb;
        $form_id = $this->code_form( array(
            'code_invoice' => '',
            'code_prefix' => 'FIXED-',
            'code_length' => '0',
        ) );
        $settings = SUPER_Common::stored_code_fields( $form_id )['invoice_code'];
        $this->assertSame( 'FIXED-', SUPER_Common::generate_random_code( $settings, true ) );
        $this->assertFalse( SUPER_Common::generate_random_code( $settings, true ) );
        $count = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->options WHERE option_name = %s",
            '_sf_unique_code-FIXED-'
        ) );
        $this->assertSame( 1, $count );
    }

    public function test_fresh_invoice_retries_when_another_claim_wins_between_lookup_and_insert() {
        global $wpdb;
        $form_id = $this->code_form(array('code_prefix' => 'RACE-', 'code_invoice_key' => 'race'));
        $settings = SUPER_Common::stored_code_fields($form_id)['invoice_code'];
        $this->assertSame('RACE-0001', SUPER_Common::generate_random_code($settings, false));
        $rival_won = false;
        $interleave = function($query) use (&$rival_won, $settings) {
            if(!$rival_won && strpos($query, 'INSERT IGNORE INTO')!==false && strpos($query, '_sf_unique_code-RACE-0001')!==false) {
                $rival_won = true;
                // Run a rival reservation after this caller checked availability,
                // before its INSERT executes: the outer INSERT must report zero.
                $this->assertTrue(SUPER_Common::claim_generated_code($settings, 'RACE-0001'));
            }
            return $query;
        };
        add_filter('query', $interleave);
        try { $reserved = SUPER_Common::generate_random_code($settings, true); }
        finally { remove_filter('query', $interleave); }
        $this->assertTrue($rival_won);
        $this->assertSame('RACE-0002', $reserved);
        $this->assertSame('2', $this->invoice_counter('race'));
        $this->assertSame(2, (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name IN (%s, %s)",
            '_sf_unique_code-RACE-0001', '_sf_unique_code-RACE-0002'
        )));
    }


    public function test_submission_keeps_a_previewed_random_code_once() {
        $form_id = $this->code_form( array( 'code_invoice' => '', 'code_length' => '8', 'code_uppercase' => 'true', 'code_characters' => '1' ) );
        $settings = SUPER_Common::stored_code_fields( $form_id )['invoice_code'];
        $preview = SUPER_Common::generate_random_code( $settings, false );
        $data = array( 'invoice_code' => array( 'name' => 'invoice_code', 'value' => $preview, 'type' => 'var' ) );
        $first = $this->invoke_ajax_private( 'reserve_generated_codes', array( $form_id, $data ) );
        $this->assertSame( $preview, $first['invoice_code']['value'] );
        $second = $this->invoke_ajax_private( 'reserve_generated_codes', array( $form_id, $data ) );
        $this->assertNotSame( $preview, $second['invoice_code']['value'] );
    }

    public function test_submission_replaces_codes_that_do_not_match_the_saved_format() {
        $form_id = $this->code_form();
        foreach( array( 'EVIL-0001', 'INV-0005', 'INV-01', 'INV-0001x', 'INV-' ) as $tampered ) {
            $data = array( 'invoice_code' => array( 'name' => 'invoice_code', 'value' => $tampered, 'type' => 'var' ) );
            $reserved = $this->invoke_ajax_private( 'reserve_generated_codes', array( $form_id, $data ) );
            $this->assertMatchesRegularExpression( '/\AINV-\d{4}\z/', $reserved['invoice_code']['value'] );
            $this->assertNotSame( $tampered, $reserved['invoice_code']['value'] );
        }
    }

    public function test_dynamic_column_copies_are_reserved_but_unrelated_fields_are_left_alone() {
        $form_id = $this->create_form( 'publish', array(
            array('tag' => 'column', 'data' => array('duplicate' => 'enabled'), 'inner' => array(
                array( 'tag' => 'hidden', 'group' => 'form_elements', 'data' => array( 'name' => 'code', 'enable_random_code' => 'true', 'code_length' => '0', 'code_prefix' => 'C-', 'code_invoice' => 'true', 'code_invoice_padding' => '3', 'code_invoice_key' => 'dyn' ) ),
            )),
            array( 'tag' => 'text', 'group' => 'form_elements', 'data' => array( 'name' => 'code_9' ) ),
        ) );
        $data = array(
            'code' => array( 'name' => 'code', 'value' => 'x', 'type' => 'var' ),
            'code_2' => array( 'name' => 'code_2', 'value' => 'x', 'type' => 'var' ),
            'code_9' => array( 'name' => 'code_9', 'value' => 'keep me', 'type' => 'var' ),
            '_super_dynamic_data' => array( 'code' => array( array( 'code' => array( 'name' => 'code', 'value' => 'x' ) ), array( 'code_2' => array( 'name' => 'code_2', 'value' => 'x' ) ) ) ),
        );
        foreach($data['_super_dynamic_data']['code'] as &$row) {
            foreach($row as &$carrier) $carrier['type'] = 'var';
            unset($carrier);
        }
        unset($row);
        $this->set_request($form_id, $data);
        $this->assertTrue($this->invoke_ajax_private('submission_data_matches_contract', array(
            json_decode($_POST['data'], true), SUPER_Common::get_form_elements($form_id), $form_id, '', '',
        )));
        $reserved = $this->invoke_ajax_private( 'reserve_generated_codes', array( $form_id, $data ) );
        $this->assertSame( 'C-001', $reserved['code']['value'] );
        $this->assertSame( 'C-002', $reserved['code_2']['value'] );
        $this->assertSame( 'keep me', $reserved['code_9']['value'] );
        $this->assertSame( 'C-002', $reserved['_super_dynamic_data']['code'][1]['code_2']['value'] );
    }

    public function test_filters_already_see_the_reserved_code() {
        $form_id = $this->code_form();
        $seen = null;
        $this->add_upload_filter( 'super_before_sending_email_data_filter', static function( $filtered ) use ( &$seen ) {
            $seen = $filtered;
            return $filtered;
        }, 10, 2 );
        $this->set_request( $form_id, array( 'invoice_code' => array( 'name' => 'invoice_code', 'value' => 'CLIENT', 'type' => 'var' ) ) );
        $atts = SUPER_Ajax::submit_form_checks( false );
        $this->assertSame( 'INV-0001', $seen['invoice_code']['value'] );
        $this->assertSame( 'INV-0001', $atts['data']['invoice_code']['value'] );
    }

    public function test_code_length_is_bounded() {
        $code = SUPER_Common::generate_random_code( array( 'invoice_key' => '', 'len' => '100000', 'char' => '1', 'pre' => '', 'inv' => '', 'invp' => '', 'suf' => '', 'upper' => '', 'lower' => '' ), false );
        $this->assertLessThanOrEqual( 64, strlen( $code ) );
    }

    // ---- PayPal IPN --------------------------------------------------------------------

    private function paypal() {
        if( !class_exists( 'SUPER_PayPal' ) ) {
            require_once SUPER_PLUGIN_DIR . '/add-ons/super-forms-paypal/super-forms-paypal.php';
        }
        return SUPER_PayPal::instance();
    }

    private function subscription( $sub_id ) {
        $form_id = $this->paypal_form();
        $post_id = self::factory()->post->create( array( 'post_type' => 'super_paypal_sub', 'post_status' => 'publish', 'post_title' => $sub_id, 'post_parent' => $form_id ) );
        update_post_meta( $post_id, '_super_sub_id', $sub_id );
        update_post_meta( $post_id, '_super_txn_data', array( 'profile_status' => 'Active' ) );
        return $post_id;
    }

    private function send_ipn( $post, $paypal_answer ) {
        $body = http_build_query( $post );
        $this->add_upload_filter( 'pre_http_request', static function( $pre, $args, $url ) use ( $paypal_answer ) {
            return array( 'headers' => array(), 'body' => $paypal_answer, 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
        }, 10, 3 );
        $_GET['page'] = 'super_paypal_ipn';
        $this->set_post( $post );
        $paypal = $this->paypal();
        Test_Super_PayPal_Input_Stream::$body = $body;
        $this->assertTrue( stream_wrapper_unregister( 'php' ) );
        $this->assertTrue( stream_wrapper_register( 'php', 'Test_Super_PayPal_Input_Stream' ) );
        try {
            return $this->run_dying_handler( static function() use ( $paypal ) { $paypal->paypal_ipn(); } );
        } finally {
            stream_wrapper_restore( 'php' );
            Test_Super_PayPal_Input_Stream::$body = '';
        }
    }

    public function test_missing_or_array_callback_type_is_rejected_without_php_errors() {
        global $wpdb;
        $before = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('super_paypal_txn', 'super_paypal_sub')");
        foreach(array(array(), array('txn_type' => array('unexpected-type'))) as $post) {
            $result = $this->send_ipn($post, 'VERIFIED');
            $this->assertSame(0, $result['status'], $result['output']);
            $this->assertSame('', $result['output']);
            $this->assertSame($before, (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('super_paypal_txn', 'super_paypal_sub')"));
        }
    }

    public function test_payment_user_update_error_preserves_the_wordpress_error_message() {
        $this->paypal();
        $form_id = $this->paypal_form(array('register_login_action' => 'register', 'paypal_completed_user_role' => 'subscriber'));
        $user_id = self::factory()->user->create();
        wp_delete_user($user_id);
        $expected = wp_update_user(array('ID' => $user_id, 'role' => 'subscriber'));
        $this->assertWPError($expected);
        $custom = $this->signed_custom(array($form_id, 'product', 0, 0, 0, $user_id));
        $payment = $this->verified_payment($custom);
        $result = $this->send_ipn($payment, 'VERIFIED');
        $this->assertSame(97, $result['status'], $result['output']);
        $this->assertSame('Exception: ' . $expected->get_error_message(), $result['output']);
        $this->assertFalse(get_userdata($user_id));
    }

    public function test_ipn_validation_body_preserves_encoded_payment_date() {
        $this->paypal();
        $method = new ReflectionMethod( 'SUPER_PayPal', 'ipn_validation_body' );
        $method->setAccessible( true );
        $this->assertSame( 'cmd=_notify-validate&payment_date=Jan%2B1&txn_id=A%2BB', $method->invoke( null, 'payment_date=Jan+1&txn_id=A%2BB' ) );
    }

    private function signed_custom( $fields, $currency='USD', $floor='0.01', $plan='' ) {
        $method = new ReflectionMethod( 'SUPER_PayPal', 'sign_custom' );
        $method->setAccessible( true );
        return $method->invoke( null, array_merge( $fields, array( $currency, $floor, $plan ) ) );
    }

    private function paypal_form( $settings=array() ) {
        return $this->create_form( 'publish', array(), array_merge( array(
            'paypal_merchant_email' => 'merchant@example.test',
            'paypal_mode' => 'live',
            'paypal_completed_post_status' => 'publish',
        ), $settings ) );
    }

    private function verified_payment( $custom ) {
        return array(
            'txn_type' => 'web_accept', 'payment_status' => 'Completed', 'txn_id' => 'TXN-' . wp_generate_password( 8, false ),
            'receiver_email' => 'merchant@example.test', 'mc_gross' => '0.01', 'mc_currency' => 'USD', 'custom' => $custom,
        );
    }

    private function checkout_terms( $cmd, $values ) {
        $html = '';
        foreach( $values as $name => $value ) {
            $html .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        }
        $method = new ReflectionMethod( 'SUPER_PayPal', 'checkout_terms_from_form' );
        $method->setAccessible( true );
        return $method->invoke( null, $cmd, $html );
    }

    public function test_generated_product_discount_limit_is_signed_and_enforced_at_ipn() {
        $this->paypal();
        foreach(array('amount' => '2', 'rate' => '20') as $kind => $discount) {
            foreach(array(array('1', '36.00'), array('0', '38.00'), array('', '32.00'), array('9', '32.00')) as $case) {
                list($limit, $expected) = $case;
                $settings = array(
                    'paypal_checkout' => 'true', 'paypal_mode' => 'sandbox',
                    'paypal_payment_type' => 'product', 'paypal_item_amount' => '10', 'paypal_item_quantity' => '4',
                    'paypal_item_discount_' . $kind => $discount, 'paypal_item_discount_num' => $limit,
                    'paypal_merchant_email' => 'merchant@example.test', 'paypal_currency_code' => 'USD',
                    'paypal_completed_entry_status' => 'paid', 'save_contact_entry' => 'yes',
                    'form_show_thanks_msg' => 'true', 'form_thanks_title' => '', 'form_thanks_description' => '',
                );
                $form_id = $this->paypal_form($settings);
                $entry_id = self::factory()->post->create(array('post_type' => 'super_contact_entry', 'post_parent' => $form_id));
                $result = $this->run_dying_handler(static function() use ($form_id, $entry_id, $settings) {
                    SUPER_PayPal::before_email_success_msg(array(
                        'settings' => $settings, 'data' => array(), 'entry_id' => $entry_id, 'post' => array('form_id' => $form_id),
                    ));
                });
                $this->assertSame(0, $result['status'], $result['output']);
                $response = json_decode($result['output'], true);
                $this->assertIsArray($response, $result['output']);
                $this->assertSame(1, preg_match('/name="custom" value="([^"]+)"/', $response['msg'], $match));
                $custom = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
                $fields = explode('|', $custom);
                $this->assertSame($expected, $fields[7], $response['msg']);
                $payment = $this->verified_payment($custom);
                $payment['test_ipn'] = '1';
                $payment['mc_gross'] = number_format((float)$expected - 0.01, 2, '.', '');
                $this->send_ipn($payment, 'VERIFIED');
                $this->assertSame('', get_post_meta($entry_id, '_super_contact_entry_paypal_order_id', true));
                $this->assertSame('', get_post_meta($entry_id, '_super_contact_entry_status', true));
                $payment['txn_id'] .= '-paid';
                $payment['mc_gross'] = $expected;
                $accepted = $this->send_ipn($payment, 'VERIFIED');
                // The callback runs in a child process; discard the parent's cached
                // empty metadata before reading the database changes it committed.
                wp_cache_delete($entry_id, 'post_meta');
                $this->assertGreaterThan(0, (int)get_post_meta($entry_id, '_super_contact_entry_paypal_order_id', true), $accepted['output']);
                $this->assertSame('paid', get_post_meta($entry_id, '_super_contact_entry_status', true));
            }
        }
        foreach(array('-1', '1.5', 'text', '100000000000') as $invalid) {
            $this->assertFalse($this->checkout_terms('_xclick', array(
                'currency_code' => 'USD', 'amount' => '10', 'quantity' => '4',
                'discount_amount' => '2', 'discount_amount2' => '2', 'discount_num' => $invalid,
            )));
        }
    }

    public function test_generated_paypal_cart_uses_unique_indexes_across_repeated_and_static_items() {
        $this->paypal();
        foreach(array(
            "{price}|1|Repeated\n5|1|Static",
            "5|1|Static\n{price}|1|Repeated",
            "{price}|1|Repeated\n{other}|1|Other",
        ) as $items) {
            $form_id = $this->paypal_form();
            $settings = array(
                'paypal_checkout' => 'true', 'paypal_mode' => 'sandbox',
                'paypal_payment_type' => 'cart', 'paypal_cart_items' => $items,
                'paypal_merchant_email' => 'merchant@example.test', 'paypal_currency_code' => 'USD',
                'save_contact_entry' => 'no', 'form_show_thanks_msg' => 'true',
                'form_thanks_title' => '', 'form_thanks_description' => '',
            );
            $data = array();
            foreach(array('price' => '10', 'price_2' => '20', 'other' => '5', 'other_2' => '7') as $name => $value) {
                $data[$name] = array('name' => $name, 'value' => $value, 'type' => 'var');
            }
            $result = $this->run_dying_handler(static function() use ($form_id, $settings, $data) {
                SUPER_PayPal::before_email_success_msg(array(
                    'settings' => $settings, 'data' => $data, 'post' => array('form_id' => $form_id),
                ));
            });
            $this->assertSame(0, $result['status'], $result['output']);
            $response = json_decode($result['output'], true);
            $this->assertIsArray($response, $result['output']);
            $this->assertFalse($response['error']);
            $html = $response['msg'];
            preg_match_all('/name="amount_([0-9]+)"/', $html, $matches);
            $expected = strpos($items, '{other}')!==false ? array('1', '2', '3', '4') : array('1', '2', '3');
            $this->assertSame($expected, $matches[1], $html);
            $method = new ReflectionMethod('SUPER_PayPal', 'checkout_terms_from_form');
            $method->setAccessible(true);
            $terms = $method->invoke(null, '_cart', $html);
            $this->assertSame(array('USD', count($expected)===4 ? '42.00' : '35.00', ''), $terms);
            $this->assertStringContainsString('name="custom"', $html);
            $this->assertStringContainsString('.submit();', $html);
        }
    }

    public function test_checkout_terms_cover_product_cart_subscription_and_donation() {
        $this->paypal();
        $this->assertSame( array( 'USD', '21.00', '' ), $this->checkout_terms( '_xclick', array(
            'currency_code' => 'USD', 'amount' => '10.00', 'quantity' => '2', 'discount_amount' => '1.00',
            'discount_amount2' => '2.00', 'shipping' => '1.00', 'shipping2' => '2.00', 'tax' => '1.00',
        ) ) );
        $this->assertSame( array( 'USD', '20.00', '' ), $this->checkout_terms( '_cart', array(
            'currency_code' => 'USD', 'amount_1' => '10.00', 'quantity_1' => '2',
            'amount_2' => '5.00', 'quantity_2' => '1', 'discount_amount_cart' => '10.00',
            'tax_1' => '9.00', 'tax_2' => '5.00',
            'tax_cart' => '2.00', 'handling_cart' => '3.00',
        ) ) );
        $this->assertSame( array( 'USD', '39.00', '' ), $this->checkout_terms( '_cart', array(
            'currency_code' => 'USD', 'amount_1' => '10.00', 'quantity_1' => '2',
            'amount_2' => '5.00', 'quantity_2' => '1', 'tax_1' => '9.00', 'tax_2' => '5.00',
        ) ) );
        $subscription = $this->checkout_terms( '_xclick-subscriptions', array(
            'currency_code' => 'EUR', 'a1' => '0.99', 'p1' => '7', 't1' => 'D',
            'a3' => '9.99', 'p3' => '1', 't3' => 'M',
        ) );
        $this->assertSame( array( 'EUR', '0.99,9.99' ), array_slice( $subscription, 0, 2 ) );
        $this->assertMatchesRegularExpression( '/\A[a-f0-9]{32}\z/', $subscription[2] );
        $plan = new ReflectionMethod( 'SUPER_PayPal', 'subscription_plan_matches' );
        $plan->setAccessible( true );
        $fields = array_merge( array( 1, 'subscription', 0, 0, 0, 0 ), $subscription );
        $callback = array( 'mc_currency' => 'EUR', 'mc_amount1' => '0.99', 'period1' => '7 D', 'mc_amount3' => '9.99', 'period3' => '1 M' );
        $this->assertTrue( $plan->invoke( null, $fields, $callback ) );
        $callback['mc_amount3'] = '0.99';
        $this->assertFalse( $plan->invoke( null, $fields, $callback ) );
        $callback['mc_amount3'] = '9.99';
        $callback['period1'] = '1 D';
        $this->assertFalse( $plan->invoke( null, $fields, $callback ) );
        $this->assertSame( array( 'EUR', '0.00', '' ), $this->checkout_terms( '_donations', array(
            'currency_code' => 'EUR',
        ) ) );
    }

    public function test_donation_completion_respects_configured_and_buyer_chosen_amounts() {
        $this->paypal();
        foreach( array( '10.00', '0' ) as $configured ) {
            $form_id = $this->paypal_form( array(
                'paypal_payment_type' => 'donation', 'paypal_item_amount' => $configured,
                'paypal_completed_entry_status' => 'paid', 'register_login_action' => 'register',
                'paypal_completed_signup_status' => 'active',
            ) );
            $terms = $this->checkout_terms( '_donations', array( 'currency_code' => 'USD', 'amount' => $configured ) );
            $this->assertSame( $configured === '0' ? '0.00' : '10.00', $terms[1] );
            foreach( array( '0.00', '0.01', '9.99', '10.00', '10.01' ) as $paid ) {
                $allowed = (float)$paid > 0 && (float)$paid >= (float)$configured;
                $post_id = self::factory()->post->create( array( 'post_status' => 'draft' ) );
                $entry_id = self::factory()->post->create( array( 'post_type' => 'super_contact_entry' ) );
                $user_id = self::factory()->user->create();
                update_post_meta( $entry_id, '_super_contact_entry_status', 'pending' );
                update_user_meta( $user_id, 'super_user_login_status', 'pending' );
                $custom = $this->signed_custom( array( $form_id, 'donation', $entry_id, 0, $post_id, $user_id ), $terms[0], $terms[1], $terms[2] );
                $payment = $this->verified_payment( $custom );
                $payment['mc_gross'] = $paid;
                $this->send_ipn( $payment, 'VERIFIED' );
                $context = 'Configured ' . $configured . ', paid ' . $paid;
                $this->assertSame( $allowed ? 'publish' : 'draft', get_post_status( $post_id ), $context );
                $this->assertSame( $allowed ? 'paid' : 'pending', get_post_meta( $entry_id, '_super_contact_entry_status', true ), $context );
                $this->assertSame( $allowed ? 'active' : 'pending', get_user_meta( $user_id, 'super_user_login_status', true ), $context );
                $this->assertSame( $allowed, (bool)get_post_meta( $entry_id, '_super_contact_entry_paypal_order_id', true ), $context );
            }
        }
        foreach( array( '-1', 'invalid', '1e2' ) as $invalid ) {
            $this->assertFalse( $this->checkout_terms( '_donations', array( 'currency_code' => 'USD', 'amount' => $invalid ) ) );
        }
    }

    public function test_subscription_payments_accept_only_configured_paid_phase_amounts() {
        $this->paypal();
        $form_id = $this->paypal_form();
        $terms = $this->checkout_terms( '_xclick-subscriptions', array(
            'currency_code' => 'EUR', 'a1' => '0.99', 'p1' => '7', 't1' => 'D',
            'a2' => '2.99', 'p2' => '1', 't2' => 'M', 'a3' => '9.99', 'p3' => '1', 't3' => 'M',
        ) );
        $this->assertSame( '0.99,2.99,9.99', $terms[1] );
        foreach( array( '0.99' => true, '2.99' => true, '9.99' => true, '0.00' => false, '0.50' => false, '1.99' => false, '10.00' => false ) as $amount => $allowed ) {
            $post_id = self::factory()->post->create( array( 'post_status' => 'draft' ) );
            $custom = $this->signed_custom( array( $form_id, 'subscription', 0, 0, $post_id, 0 ), $terms[0], $terms[1], $terms[2] );
            $payment = $this->verified_payment( $custom );
            $payment['txn_type'] = 'subscr_payment';
            $payment['subscr_id'] = 'I-PHASES';
            $payment['mc_currency'] = 'EUR';
            $payment['mc_gross'] = (string) $amount;
            $this->send_ipn( $payment, 'VERIFIED' );
            $this->assertSame( $allowed ? 'publish' : 'draft', get_post_status( $post_id ), 'Phase amount ' . $amount );
        }
        $free_trial = $this->checkout_terms( '_xclick-subscriptions', array(
            'currency_code' => 'EUR', 'a1' => '0', 'p1' => '7', 't1' => 'D', 'a3' => '9.99', 'p3' => '1', 't3' => 'M',
        ) );
        $this->assertSame( '9.99', $free_trial[1] );
    }

    public function test_tampered_custom_cannot_point_a_payment_at_another_post() {
        $this->paypal();
        $form_id = $this->paypal_form();
        $victim_post = self::factory()->post->create( array( 'post_status' => 'draft' ) );
        $legit = $this->signed_custom( array( $form_id, 'single', 0, 0, 0, 0 ) );
        $tampered = preg_replace( '/^(\d+\|single\|0\|0\|)0/', '${1}' . $victim_post, $legit );
        $this->send_ipn( $this->verified_payment( $tampered ), 'VERIFIED' );
        $this->assertSame( 'draft', get_post_status( $victim_post ) );
        $unsigned = $form_id . '|single|0|0|' . $victim_post . '|0';
        $this->send_ipn( $this->verified_payment( $unsigned ), 'VERIFIED' );
        $this->assertSame( 'draft', get_post_status( $victim_post ) );
    }

    public function test_signed_custom_updates_the_post_it_was_issued_for() {
        $this->paypal();
        $form_id = $this->paypal_form();
        $own_post = self::factory()->post->create( array( 'post_status' => 'draft' ) );
        $this->send_ipn( $this->verified_payment( $this->signed_custom( array( $form_id, 'single', 0, 0, $own_post, 0 ) ) ), 'VERIFIED' );
        $this->assertSame( 'publish', get_post_status( $own_post ) );
    }

    public function test_verified_pending_payment_does_not_complete_a_post() {
        $this->paypal();
        $form_id = $this->paypal_form();
        $own_post = self::factory()->post->create( array( 'post_status' => 'draft' ) );
        $payment = $this->verified_payment( $this->signed_custom( array( $form_id, 'single', 0, 0, $own_post, 0 ) ) );
        $payment['payment_status'] = 'Pending';
        $this->add_upload_filter( 'super_after_paypal_ipn_payment_verified', static function( $event ) {
            echo 'VERIFIED_NOTIFICATION:' . $event['post']['payment_status'] . ':' . get_post_type($event['post_id']);
        } );
        $invalid = $this->send_ipn( $payment, 'INVALID' );
        $this->assertStringNotContainsString( 'VERIFIED_NOTIFICATION:', $invalid['output'] );
        $result = $this->send_ipn( $payment, 'VERIFIED' );
        $this->assertStringContainsString( 'VERIFIED_NOTIFICATION:Pending:super_paypal_txn', $result['output'] );
        $this->assertSame( 'draft', get_post_status( $own_post ) );
    }

    public function test_verified_wrong_currency_does_not_complete_a_post() {
        $this->paypal();
        $form_id = $this->paypal_form( array( 'paypal_currency_code' => 'EUR' ) );
        $own_post = self::factory()->post->create( array( 'post_status' => 'draft' ) );
        $payment = $this->verified_payment( $this->signed_custom( array( $form_id, 'single', 0, 0, $own_post, 0 ), 'EUR' ) );
        $this->send_ipn( $payment, 'VERIFIED' );
        $this->assertSame( 'draft', get_post_status( $own_post ) );
    }

    public function test_verified_underpayment_does_not_complete_a_post() {
        $this->paypal();
        $form_id = $this->paypal_form( array( 'paypal_payment_type' => 'product', 'paypal_item_amount' => '10.00' ) );
        $own_post = self::factory()->post->create( array( 'post_status' => 'draft' ) );
        $payment = $this->verified_payment( $this->signed_custom( array( $form_id, 'product', 0, 0, $own_post, 0 ), 'USD', '10.00' ) );
        $this->send_ipn( $payment, 'VERIFIED' );
        $this->assertSame( 'draft', get_post_status( $own_post ) );
    }

    public function test_signed_subscription_signup_requires_the_issued_plan() {
        $this->paypal();
        $form_id = $this->paypal_form( array( 'paypal_currency_code' => 'EUR', 'paypal_payment_type' => 'subscription' ) );
        $own_post = self::factory()->post->create( array( 'post_status' => 'draft' ) );
        $terms = $this->checkout_terms( '_xclick-subscriptions', array(
            'currency_code' => 'EUR', 'a1' => '0.99', 'p1' => '7', 't1' => 'D',
            'a3' => '9.99', 'p3' => '1', 't3' => 'M',
        ) );
        $sub_id = 'SUB-' . wp_generate_password( 8, false );
        $signup = array(
            'txn_type' => 'subscr_signup', 'subscr_id' => $sub_id,
            'receiver_email' => 'merchant@example.test', 'mc_currency' => 'EUR',
            'mc_amount1' => '0.99', 'period1' => '7 D',
            'mc_amount3' => '0.99', 'period3' => '1 M',
            'custom' => $this->signed_custom( array( $form_id, 'subscription', 0, 0, $own_post, 0 ), $terms[0], $terms[1], $terms[2] ),
        );
        $lookup = array( 'post_type' => 'super_paypal_sub', 'post_status' => 'any', 'meta_key' => '_super_sub_id', 'meta_value' => $sub_id, 'fields' => 'ids' );
        $this->send_ipn( $signup, 'VERIFIED' );
        $this->assertSame( array(), get_posts( $lookup ) );
        $signup['mc_amount3'] = '9.99';
        $this->send_ipn( $signup, 'VERIFIED' );
        $this->assertCount( 1, get_posts( $lookup ) );
        $this->assertSame( 'draft', get_post_status( $own_post ) );
    }

    public function test_unverified_subscription_cancel_changes_nothing() {
        $post_id = $this->subscription( 'I-UNIT1' );
        $this->send_ipn( array( 'txn_type' => 'subscr_cancel', 'subscr_id' => 'I-UNIT1', 'payment_status' => 'Completed' ), 'INVALID' );
        $this->assertSame( array( 'profile_status' => 'Active' ), get_post_meta( $post_id, '_super_txn_data', true ) );
    }

    public function test_unverified_refund_changes_nothing() {
        $txn_id = self::factory()->post->create( array( 'post_type' => 'super_paypal_txn', 'post_status' => 'Completed', 'post_title' => 'TXN-UNIT' ) );
        update_post_meta( $txn_id, '_super_txn_data', array( 'payment_status' => 'Completed' ) );
        $this->send_ipn( array( 'payment_status' => 'Refunded', 'parent_txn_id' => 'TXN-UNIT', 'txn_type' => 'web_accept' ), 'INVALID' );
        $this->assertSame( array( 'payment_status' => 'Completed' ), get_post_meta( $txn_id, '_super_txn_data', true ) );
    }

    public function test_verified_subscription_cancel_is_applied_and_verified_over_tls() {
        $post_id = $this->subscription( 'I-UNIT2' );
        $this->send_ipn( array( 'txn_type' => 'subscr_cancel', 'subscr_id' => 'I-UNIT2', 'payment_status' => 'Completed', 'receiver_email' => 'merchant@example.test' ), 'VERIFIED' );
        $data = get_post_meta( $post_id, '_super_txn_data', true );
        $this->assertSame( 'Canceled', $data['profile_status'] );
    }

    public function test_verified_subscription_change_requires_the_records_merchant_and_environment() {
        $post_id = $this->subscription( 'I-BOUND' );
        $before = get_post_meta( $post_id, '_super_txn_data', true );
        $this->send_ipn( array( 'txn_type' => 'subscr_cancel', 'subscr_id' => 'I-BOUND', 'payment_status' => 'Completed', 'receiver_email' => 'other@example.test' ), 'VERIFIED' );
        $this->assertSame( $before, get_post_meta( $post_id, '_super_txn_data', true ) );
        $this->send_ipn( array( 'txn_type' => 'subscr_cancel', 'subscr_id' => 'I-BOUND', 'payment_status' => 'Completed', 'receiver_email' => 'merchant@example.test', 'test_ipn' => '1' ), 'VERIFIED' );
        $this->assertSame( $before, get_post_meta( $post_id, '_super_txn_data', true ) );
    }

    public function test_subscription_lifecycle_hooks_require_local_merchant_and_environment() {
        $this->subscription( 'I-LIFECYCLE' );
        foreach( array(
            'subscr_eot' => 'super_after_paypal_ipn_subscription_expired',
            'subscr_failed' => 'super_after_paypal_ipn_subscription_payment_failed',
        ) as $event => $hook ) {
            $this->add_upload_filter( $hook, static function() { echo 'LIFECYCLE_HOOK_REACHED'; } );
            $valid = array('txn_type' => $event, 'subscr_id' => 'I-LIFECYCLE', 'receiver_email' => 'merchant@example.test');
            foreach( array(
                array('receiver_email' => ''),
                array('receiver_email' => 'other@example.test'),
                array('test_ipn' => '1'),
                array('subscr_id' => 'I-UNKNOWN'),
                array('subscr_id' => array('I-LIFECYCLE')),
                array('recurring_payment_id' => array('I-LIFECYCLE')),
                array('receiver_email' => array('merchant@example.test')),
            ) as $override ) {
                $result = $this->send_ipn( array_merge($valid, $override), 'VERIFIED' );
                $this->assertStringNotContainsString( 'LIFECYCLE_HOOK_REACHED', $result['output'], $event );
            }
            $result = $this->send_ipn( $valid, 'VERIFIED' );
            $this->assertStringContainsString( 'LIFECYCLE_HOOK_REACHED', $result['output'], $event );
        }
    }

    public function test_verified_refund_requires_the_records_merchant_and_environment() {
        $form_id = $this->paypal_form();
        $txn_id = self::factory()->post->create( array( 'post_type' => 'super_paypal_txn', 'post_status' => 'Completed', 'post_title' => 'TXN-BOUND', 'post_parent' => $form_id ) );
        $before = array( 'payment_status' => 'Completed', 'receiver_email' => 'merchant@example.test' );
        update_post_meta( $txn_id, '_super_txn_data', $before );
        $this->send_ipn( array( 'payment_status' => 'Refunded', 'parent_txn_id' => 'TXN-BOUND', 'txn_type' => 'web_accept', 'receiver_email' => 'other@example.test' ), 'VERIFIED' );
        $this->assertSame( $before, get_post_meta( $txn_id, '_super_txn_data', true ) );
        $this->send_ipn( array( 'payment_status' => 'Refunded', 'parent_txn_id' => 'TXN-BOUND', 'txn_type' => 'web_accept', 'receiver_email' => 'merchant@example.test', 'test_ipn' => '1' ), 'VERIFIED' );
        $this->assertSame( $before, get_post_meta( $txn_id, '_super_txn_data', true ) );
        $this->send_ipn( array( 'payment_status' => 'Refunded', 'parent_txn_id' => 'TXN-BOUND', 'txn_type' => 'web_accept', 'receiver_email' => 'merchant@example.test' ), 'VERIFIED' );
        $this->assertSame( 'Refunded', get_post_meta( $txn_id, '_super_txn_data', true )['payment_status'] );
    }

    public function test_ipn_verification_request_uses_tls_verification() {
        $this->subscription( 'I-UNIT3' );
        $captured = tempnam( sys_get_temp_dir(), 'sf-ipn-' );
        $this->add_upload_filter( 'pre_http_request', static function( $pre, $args, $url ) use ( $captured ) {
            file_put_contents( $captured, wp_json_encode( array( 'url' => $url, 'sslverify' => isset($args['sslverify']) ? $args['sslverify'] : null ) ) );
            return $pre;
        }, 1, 3 );
        $this->send_ipn( array( 'txn_type' => 'subscr_cancel', 'subscr_id' => 'I-UNIT3', 'payment_status' => 'Completed' ), 'INVALID' );
        $request = json_decode( (string) file_get_contents( $captured ), true );
        unlink( $captured );
        $this->assertSame( 'https://ipnpb.paypal.com/cgi-bin/webscr', $request['url'] );
        $this->assertTrue( $request['sslverify'] );
    }

    public function test_non_string_submission_payloads_are_rejected_without_fatal_errors() {
        $form_id = $this->create_form();
        foreach( array( array('unexpected' => 'array'), true, 42 ) as $payload ) {
            $_POST = array( 'form_id' => (string) $form_id, 'data' => $payload );
            $_REQUEST = $_POST;
            $this->assert_handler_rejected_with(
                array( 'SUPER_Ajax', 'submit_form_checks' ),
                'Invalid form data.'
            );
        }
    }


    public function test_print_capability_is_exact_and_single_use() {
        $this->configure_csrf( 'true' );
        $scope = array( 'form_id' => 41, 'file_id' => 82 );
        $token = SUPER_Common::issue_public_print_capability( $scope );
        $this->assertTrue( is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token)===1 );
        $this->assertFalse( SUPER_Common::consume_public_print_capability( 'invalid', $scope ) );
        $this->assertSame(
            array( 'version' => 1, 'form_id' => 41, 'file_id' => 82 ),
            SUPER_Common::consume_public_print_capability( $token, $scope )
        );
        $this->assertFalse( SUPER_Common::consume_public_print_capability( $token, $scope ) );
    }

    public function test_print_capability_rejects_other_form_or_attachment_and_burns_attempt() {
        $this->configure_csrf( 'true' );
        $scope = array( 'form_id' => 41, 'file_id' => 82 );
        foreach( array(
            array( 'form_id' => 42, 'file_id' => 82 ),
            array( 'form_id' => 41, 'file_id' => 83 ),
        ) as $other_scope ) {
            $token = SUPER_Common::issue_public_print_capability( $scope );
            $this->assertTrue( is_string($token) );
            $this->assertFalse( SUPER_Common::consume_public_print_capability( $token, $other_scope ) );
            $this->assertFalse( SUPER_Common::consume_public_print_capability( $token, $scope ) );
        }
    }

    public function test_print_capability_cannot_cross_browser_sessions() {
        $this->configure_csrf( 'true' );
        $scope = array( 'form_id' => 41, 'file_id' => 82 );
        $original_session = $_COOKIE['_sfs_id'];
        $token = SUPER_Common::issue_public_print_capability( $scope );
        $this->assertTrue( is_string($token) );
        $this->seed_browser_session();
        $this->assertFalse( SUPER_Common::consume_public_print_capability( $token, $scope ) );
        $_COOKIE['_sfs_id'] = $original_session;
        $this->assertSame(
            array( 'version' => 1, 'form_id' => 41, 'file_id' => 82 ),
            SUPER_Common::consume_public_print_capability( $token, $scope )
        );
    }

    public function test_print_capability_works_without_session_storage() {
        $this->configure_csrf( 'false' );
        unset($_COOKIE['_sfs_id']);
        $scope = array('form_id'=>41, 'file_id'=>82);
        $token = SUPER_Common::issue_public_print_capability($scope);
        $this->assertIsString($token);
        $this->assertSame(array('version'=>1) + $scope, SUPER_Common::consume_public_print_capability($token, $scope));
        $this->assertArrayNotHasKey('_sfs_id', $_COOKIE);
    }

}
