<?php
/**
 * Public (logged-out reachable) AJAX endpoints and the PayPal IPN listener:
 * WooCommerce order search / order populate, unique code preview and reservation, IPN verification.
 */
class Test_Security_Public_Endpoints extends Super_Forms_Upload_Security_Test_Case {

    private $customer_id = 0;
    private $other_customer_id = 0;
    private $staff_id = 0;
    private $orders = array();

    public function set_up() {
        parent::set_up();
        $this->customer_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $this->other_customer_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $this->staff_id = self::factory()->user->create( array( 'role' => 'editor' ) );
        get_user_by( 'id', $this->staff_id )->add_cap( 'edit_shop_orders' );
        $this->orders['mine'] = $this->create_order( $this->customer_id, 'alice@example.test', 'Alice', '+31600000001' );
        $this->orders['other'] = $this->create_order( $this->other_customer_id, 'bob@example.test', 'Bob', '+31600000002' );
        wp_set_current_user( 0 );
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

    private function populate_order( $form_id, $order_id ) {
        $this->seed_browser_session();
        $capability = SUPER_Common::issue_public_populate_capability( array(
            'form_id' => $form_id,
            'field_name' => 'order_lookup',
            'method' => 'wc_order_id',
            'skip' => '',
            'result_scope' => 'wc_order_entry',
        ) );
        $this->assertIsString( $capability );
        $this->set_post( array(
            'form_id' => (string) $form_id,
            'field_name' => 'order_lookup',
            'method' => 'wc_order_id',
            'skip' => '',
            'capability' => $capability,
            'order_id' => (string) $order_id,
        ) );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'populate_form_data' ) );
        $this->assertTrue( $result['exited'] );
        return json_decode( $result['output'], true );
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
        $this->assertSame( 'INV-0002', $result['output'] );
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

    public function test_code_length_is_bounded() {
        $code = SUPER_Common::generate_random_code( array( 'invoice_key' => '', 'len' => '100000', 'char' => '1', 'pre' => '', 'inv' => '', 'invp' => '', 'suf' => '', 'upper' => '', 'lower' => '' ), false );
        $this->assertLessThanOrEqual( 64, strlen( $code ) );
    }

    // ---- PayPal IPN --------------------------------------------------------------------

    private $ipn_requests = array();

    private function paypal() {
        if( !class_exists( 'SUPER_PayPal' ) ) {
            require_once SUPER_PLUGIN_DIR . '/add-ons/super-forms-paypal/super-forms-paypal.php';
        }
        return SUPER_PayPal::instance();
    }

    private function subscription( $sub_id ) {
        $post_id = self::factory()->post->create( array( 'post_type' => 'super_paypal_sub', 'post_status' => 'publish', 'post_title' => $sub_id ) );
        update_post_meta( $post_id, '_super_sub_id', $sub_id );
        update_post_meta( $post_id, '_super_txn_data', array( 'profile_status' => 'Active' ) );
        return $post_id;
    }

    private function send_ipn( $post, $paypal_answer ) {
        $this->ipn_requests = array();
        $body = http_build_query( $post );
        $this->add_upload_filter( 'super_paypal_ipn_raw_body', static function() use ( $body ) { return $body; } );
        $requests = &$this->ipn_requests;
        $this->add_upload_filter( 'pre_http_request', static function( $pre, $args, $url ) use ( $paypal_answer, &$requests ) {
            $requests[] = array( 'url' => $url, 'args' => $args );
            return array( 'headers' => array(), 'body' => $paypal_answer, 'response' => array( 'code' => 200, 'message' => 'OK' ), 'cookies' => array() );
        }, 10, 3 );
        $_GET['page'] = 'super_paypal_ipn';
        $this->set_post( $post );
        $paypal = $this->paypal();
        return $this->run_dying_handler( static function() use ( $paypal ) { $paypal->paypal_ipn(); } );
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
        $this->send_ipn( array( 'txn_type' => 'subscr_cancel', 'subscr_id' => 'I-UNIT2', 'payment_status' => 'Completed' ), 'VERIFIED' );
        $data = get_post_meta( $post_id, '_super_txn_data', true );
        $this->assertSame( 'Canceled', $data['profile_status'] );
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
}
