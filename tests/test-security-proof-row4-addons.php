<?php
/**
 * Row 4 proof package for the Register & Login activation carrier and the
 * Mailchimp interests carrier across render, submit, and consumer boundaries.
 *
 * @package Super_Forms_Tests
 */

require_once __DIR__ . '/test-security-upload-00-base.php';

trait Row4_Consumer_Capture {

    private function capture_hook_to_file( $hook, $capture_file ) {
        $callback = static function( $x ) use ( $capture_file ) {
            file_put_contents( $capture_file, wp_json_encode( $x ) . "\n", FILE_APPEND | LOCK_EX );
        };
        add_action( $hook, $callback, 1, 1 );
        return $callback;
    }

    private function new_capture_file( $prefix ) {
        $file = tempnam( sys_get_temp_dir(), $prefix );
        $this->assertNotFalse( $file );
        return $file;
    }

    private function read_jsonl( $file ) {
        $raw = file_exists( $file ) ? file_get_contents( $file ) : '';
        if( $raw === false || trim( $raw ) === '' ) {
            return array();
        }
        $decoded = array();
        foreach( explode( "\n", $raw ) as $line ) {
            if( trim( $line ) === '' ) continue;
            $decoded[] = json_decode( $line, true );
        }
        return $decoded;
    }
}

class Test_Super_Forms_Proof_Row4_Register_Login_Activation extends Super_Forms_Upload_Security_Test_Case {
    use Row4_Consumer_Capture;

    private $original_get;
    private $created_users = array();

    public function set_up() {
        if( !class_exists( 'SUPER_Register_Login' ) ) {
            require_once dirname( __DIR__ ) . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
        $this->original_get = $_GET;
        parent::set_up();
        // WP_UnitTestCase restores $wp_filter to the snapshot taken before the
        // add-on was loaded, so re-attach the hooks its constructor registers
        // (super-forms-register-login.php:178-179).
        $register_login = SUPER_Register_Login();
        foreach( array( 'add_activation_code_element', 'submission_carrier_contracts' ) as $method ) {
            $hook = ( $method==='add_activation_code_element' )
                ? 'super_shortcodes_after_form_elements_filter'
                : 'super_submission_carrier_contracts_filter';
            if( !has_filter( $hook, array( $register_login, $method ) ) ) {
                add_filter( $hook, array( $register_login, $method ), 10, 2 );
            }
        }
        $_GET = array();
        $this->configure_csrf( 'false' );
    }

    public function tear_down() {
        if( !function_exists( 'wp_delete_user' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        foreach( array_unique( $this->created_users ) as $user_id ) {
            if( get_userdata( $user_id )!==false ) {
                wp_delete_user( $user_id );
            }
        }
        $_GET = $this->original_get;
        parent::tear_down();
    }

    private function login_form_settings( $overrides=array() ) {
        return array_merge(
            array(
                'register_login_action' => 'login',
                'login_user_role' => array(),
                'register_welcome_back_msg' => '',
                'register_incorrect_code_msg' => 'ROW4_INCORRECT_CODE_MARKER',
                'register_account_activated_msg' => 'ROW4_ACTIVATED_MARKER',
                'register_login_url' => 'https://example.test/login/',
                'form_redirect_option' => '',
                'form_processing_overlay' => '',
                'send' => 'no',
                'confirm' => 'no',
                'save_contact_entry' => 'no',
                'form_thanks_title' => '',
                'form_thanks_description' => '',
                'form_show_thanks_msg' => '',
            ),
            $overrides
        );
    }

    private function create_login_form() {
        return $this->create_form(
            'publish',
            array(
                array(
                    'tag' => 'activation_code',
                    'group' => 'form_elements',
                    'data' => array(),
                ),
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'data' => array( 'name' => 'user_login', 'label' => 'Username' ),
                ),
                array(
                    'tag' => 'password',
                    'group' => 'form_elements',
                    'data' => array( 'name' => 'user_pass', 'label' => 'Password' ),
                ),
            ),
            $this->login_form_settings()
        );
    }

    private function seed_pending_user( $login, $password, $code ) {
        $user_id = self::factory()->user->create( array(
            'user_login' => $login,
            'user_email' => $login . '@example.test',
            'user_pass' => $password,
            'role' => 'subscriber',
        ) );
        $this->created_users[] = $user_id;
        update_user_meta( $user_id, 'super_user_login_status', 'active' );
        update_user_meta( $user_id, 'super_account_status', 0 ); // 0 = inactive/unverified
        update_user_meta( $user_id, 'super_account_activation', $code );
        return $user_id;
    }

    /**
     * Render the login form through the public shortcode path.
     */
    private function render_login_form( $form_id, $code ) {
        $saved = $_GET;
        $_GET = array( 'code' => $code );
        $html = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
        $_GET = $saved;
        return $html;
    }
    private function with_registration_consumer( $callback, $capture_file=null ) {
        $hook = 'super_before_sending_email_hook';
        $consumer = array( 'SUPER_Register_Login', 'before_sending_email' );
        $capture_callback = null;
        if( is_string($capture_file) && $capture_file!=='' ) {
            $capture_callback = $this->capture_hook_to_file( $hook, $capture_file );
        }
        $added_consumer = false;
        if( !has_action( $hook, $consumer ) ) {
            add_action( $hook, $consumer, 10, 1 );
            $added_consumer = true;
        }
        try {
            return call_user_func( $callback );
        } finally {
            if( $capture_callback!==null ) {
                remove_action( $hook, $capture_callback, 1 );
            }
            if( $added_consumer ) {
                remove_action( $hook, $consumer, 10 );
            }
        }
    }

    public function test_activation_code_renderer_public_submit_activates_pending_account_and_delivers_exact_carriers_to_consumer() {
        $login = 'sf_row4_user_' . substr( md5( wp_generate_uuid4() ), 0, 10 );
        $password = 'Row4-Secret-Pass-1!';
        $code = 'ROW4CODE1';
        $user_id = $this->seed_pending_user( $login, $password, $code );
        $form_id = $this->create_login_form();

        $html = $this->render_login_form( $form_id, $code );
        $this->assertStringContainsString( 'class="super-shortcode super-field super-activation_code', $html );
        $this->assertStringContainsString( 'name="activation_code"', $html );
        $this->assertStringContainsString( 'value="' . $code . '"', $html );
        $this->assertStringContainsString( 'name="user_login"', $html );
        $this->assertStringContainsString( 'name="user_pass"', $html );
        $this->assertRenderedInputValue( $html, 'hidden_form_id', $form_id );

        // Build the submitted carriers from the rendered form state.
        $data = array(
            'user_login' => array( 'name' => 'user_login', 'value' => $login, 'type' => 'var' ),
            'user_pass' => array( 'name' => 'user_pass', 'value' => $password, 'type' => 'var' ),
            'activation_code' => array( 'name' => 'activation_code', 'value' => $code, 'type' => 'var' ),
        );
        $this->set_submit_request( $form_id, $data );
        // The activation link carries the code in the request URL, which admits
        // the base activation-code carrier; the carrier value is validated below.
        $_GET['code'] = $code;

        $capture = $this->new_capture_file( 'sf-row4-rl-a-' );
        $before_status = get_user_meta( $user_id, 'super_account_status', true );
        $this->assertSame( '0', $before_status );
        $result = $this->with_registration_consumer(
            function() {
                return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
            },
            $capture
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );

        // The consumer receives only the validated carriers.
        $captured = $this->read_jsonl( $capture );
        $this->assertCount( 1, $captured, $result['output'] );
        $received = $captured[0]['data'];
        $expected_keys = array( 'user_login', 'user_pass', 'activation_code', 'hidden_form_id', 'hidden_contact_entry_id' );
        sort( $expected_keys );
        $actual_keys = array_keys( $received );
        sort( $actual_keys );
        $this->assertSame( $expected_keys, $actual_keys );
        $this->assertSame( $login, $received['user_login']['value'] );
        $this->assertSame( $password, $received['user_pass']['value'] );
        $this->assertSame( $code, $received['activation_code']['value'] );

        // Assert the activation-owned user state.
        $this->assertSame( '1', get_user_meta( $user_id, 'super_account_status', true ) );
        $this->assertSame( '', get_user_meta( $user_id, 'super_account_activation', true ) );

        @unlink( $capture );
    }

    public function test_incorrect_activation_code_is_rejected_and_leaves_pending_state_untouched() {
        $login = 'sf_row4_user_' . substr( md5( wp_generate_uuid4() ), 0, 10 );
        $password = 'Row4-Secret-Pass-2!';
        $code = 'ROW4CODE2';
        $user_id = $this->seed_pending_user( $login, $password, $code );
        $form_id = $this->create_login_form();

        // Submit a non-matching activation code and assert the account stays pending.

        $data = array(
            'user_login' => array( 'name' => 'user_login', 'value' => $login, 'type' => 'var' ),
            'user_pass' => array( 'name' => 'user_pass', 'value' => $password, 'type' => 'var' ),
            'activation_code' => array( 'name' => 'activation_code', 'value' => 'WRONG-CODE', 'type' => 'var' ),
        );
        $this->set_submit_request( $form_id, $data );
        $_GET['code'] = $code;

        $capture = $this->new_capture_file( 'sf-row4-rl-b-' );
        $result = $this->with_registration_consumer(
            function() {
                return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
            },
            $capture
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertTrue( $decoded['error'], $result['output'] );
        $this->assertStringContainsString( 'ROW4_INCORRECT_CODE_MARKER', wp_strip_all_tags( $decoded['msg'] ) );

        // The consumer runs, but the account state remains unchanged.
        $captured = $this->read_jsonl( $capture );
        $this->assertCount( 1, $captured, $result['output'] );
        $this->assertSame( 'WRONG-CODE', $captured[0]['data']['activation_code']['value'] );
        $this->assertSame( '0', get_user_meta( $user_id, 'super_account_status', true ) );
        $this->assertSame( $code, get_user_meta( $user_id, 'super_account_activation', true ) );

        @unlink( $capture );
    }

    public function test_unvalidated_extra_carrier_is_rejected_before_reaching_the_real_consumer() {
        $login = 'sf_row4_user_' . substr( md5( wp_generate_uuid4() ), 0, 10 );
        $password = 'Row4-Secret-Pass-3!';
        $code = 'ROW4CODE3';
        $user_id = $this->seed_pending_user( $login, $password, $code );
        $form_id = $this->create_login_form();


        $data = array(
            'user_login' => array( 'name' => 'user_login', 'value' => $login, 'type' => 'var' ),
            'user_pass' => array( 'name' => 'user_pass', 'value' => $password, 'type' => 'var' ),
            'activation_code' => array( 'name' => 'activation_code', 'value' => $code, 'type' => 'var' ),
            // Not part of the stored contract.
            'super_admin_override' => array( 'name' => 'super_admin_override', 'value' => 'true', 'type' => 'var' ),
        );
        $this->set_submit_request( $form_id, $data );
        $_GET['code'] = $code;

        $capture = $this->new_capture_file( 'sf-row4-rl-c-' );
        $result = $this->with_registration_consumer(
            function() {
                return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
            },
            $capture
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertTrue( $decoded['error'], $result['output'] );
        $this->assertStringContainsString( 'invalid form data', strtolower( wp_strip_all_tags( $decoded['msg'] ) ) );

        // Contract rejection happens before the consumer hook.
        $captured = $this->read_jsonl( $capture );
        $this->assertSame( array(), $captured, $result['output'] );
        $this->assertSame( '0', get_user_meta( $user_id, 'super_account_status', true ) );
        $this->assertSame( $code, get_user_meta( $user_id, 'super_account_activation', true ) );

        @unlink( $capture );
    }
}

class Test_Super_Forms_Proof_Row4_Mailchimp_Interests extends Super_Forms_Upload_Security_Test_Case {
    use Row4_Consumer_Capture;

    private $original_get;

    public function set_up() {
        if( !class_exists( 'SUPER_Mailchimp' ) ) {
            require_once dirname( __DIR__ ) . '/add-ons/super-forms-mailchimp/super-forms-mailchimp.php';
        }
        $this->original_get = $_GET;
        parent::set_up();
        // WP_UnitTestCase restores $wp_filter to the snapshot taken before the
        // add-on was loaded, so re-attach the hooks its constructor registers
        // (super-forms-mailchimp.php:163, 175-176).
        $mailchimp = SUPER_Mailchimp();
        $hooks = array(
            'super_shortcodes_after_form_elements_filter' => 'add_mailchimp_element',
            'super_before_sending_email_data_filter' => 'remove_mailchimp_data',
            'super_submission_carrier_contracts_filter' => 'submission_carrier_contracts',
        );
        foreach( $hooks as $hook => $method ) {
            if( !has_filter( $hook, array( $mailchimp, $method ) ) ) {
                add_filter( $hook, array( $mailchimp, $method ), 10, 2 );
            }
        }
        $_GET = array();
    }

    public function tear_down() {
        remove_all_filters( 'pre_http_request' );
        $_GET = $this->original_get;
        parent::tear_down();
    }

    /**
     * Set the Mailchimp test settings without dropping the API key.
     */
    private function configure_settings( $mailchimp_key ) {
        $settings = array(
            'csrf_check' => 'false',
            'email_reminder_amount' => 0,
            'mailchimp_key' => $mailchimp_key,
        );
        update_option( 'super_settings', $settings, false );
        SUPER_Forms()->global_settings = $settings;
    }

    private function invoke_mailchimp_private( $method, $args=array() ) {
        $ref = new ReflectionMethod( 'SUPER_Mailchimp', $method );
        $ref->setAccessible( true );
        return $ref->invokeArgs( null, $args );
    }

    private function mailchimp_element_data( $overrides=array() ) {
        return array_merge(
            array(
                'list_id' => 'LIST_ROW4',
                'display_interests' => 'yes',
                'send_confirmation' => 'no',
                'subscriber_status' => 'subscribed',
                'subscriber_tags' => '',
                'vip' => '',
                'custom_fields' => '',
            ),
            $overrides
        );
    }

    private function form_settings( $overrides=array() ) {
        return array_merge(
            array(
                'send' => 'no',
                'confirm' => 'no',
                'save_contact_entry' => 'no',
                'form_thanks_title' => '',
                'form_thanks_description' => '',
                'form_show_thanks_msg' => '',
                'form_redirect_option' => '',
            ),
            $overrides
        );
    }

    private function create_mailchimp_form( $mailchimp_elements ) {
        // Every saved element carries its builder group; the renderer reads
        // $v['group'] unguarded (includes/class-shortcodes.php:6356).
        $elements = array();
        foreach( $mailchimp_elements as $data ) {
            $elements[] = array( 'tag' => 'mailchimp', 'group' => 'form_elements', 'data' => $data );
        }
        $elements[] = array( 'tag' => 'text', 'group' => 'form_elements', 'data' => array( 'name' => 'email' ) );
        return $this->create_form( 'publish', $elements, $this->form_settings() );
    }

    /**
     * Render the Mailchimp fixture through the public shortcode path with a
     * non-live test configuration.
     */
    private function render_mailchimp_form( $form_id ) {
        $saved = $_GET;
        $this->configure_settings( 'row4nodatacenter' );
        $html = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
        $_GET = $saved;
        return $html;
    }

    private function install_http_stub( $capture_file, $existing_member ) {
        $callback = static function( $preempt, $args, $url ) use ( $capture_file, $existing_member ) {
            $method = isset( $args['method'] ) ? $args['method'] : 'POST';
            file_put_contents(
                $capture_file,
                wp_json_encode( array( 'url' => $url, 'method' => $method, 'body' => isset( $args['body'] ) ? $args['body'] : null ) ) . "\n",
                FILE_APPEND | LOCK_EX
            );
            if( $method === 'GET' ) {
                if( $existing_member === null ) {
                    return array(
                        'body' => wp_json_encode( array( 'status' => 404, 'title' => 'Resource Not Found', 'detail' => 'not found' ) ),
                        'response' => array( 'code' => 404, 'message' => 'Not Found' ),
                        'headers' => array(),
                    );
                }
                return array(
                    'body' => wp_json_encode( $existing_member ),
                    'response' => array( 'code' => 200, 'message' => 'OK' ),
                    'headers' => array(),
                );
            }
            return array(
                'body' => wp_json_encode( array( 'status' => 'subscribed' ) ),
                'response' => array( 'code' => 200, 'message' => 'OK' ),
                'headers' => array(),
            );
        };
        add_filter( 'pre_http_request', $callback, 10, 3 );
        return $callback;
    }
    private function with_mailchimp_consumer( $callback, $capture_file=null ) {
        $hook = 'super_before_sending_email_hook';
        $consumer = array( 'SUPER_Mailchimp', 'update_mailchimp_subscribers' );
        $capture_callback = null;
        if( is_string($capture_file) && $capture_file!=='' ) {
            $capture_callback = $this->capture_hook_to_file( $hook, $capture_file );
        }
        $added_consumer = false;
        if( !has_action( $hook, $consumer ) ) {
            add_action( $hook, $consumer, 10, 1 );
            $added_consumer = true;
        }
        try {
            return call_user_func( $callback );
        } finally {
            if( $capture_callback!==null ) {
                remove_action( $hook, $capture_callback, 1 );
            }
            if( $added_consumer ) {
                remove_action( $hook, $consumer, 10 );
            }
        }
    }

    public function test_interests_renderer_public_submit_creates_new_member_with_exact_interest_booleans_and_strips_selected_values_before_consumer() {
        $element = $this->mailchimp_element_data();
        $form_id = $this->create_mailchimp_form( array( $element ) );

        $html = $this->render_mailchimp_form( $form_id );
        $this->assertStringContainsString( 'super-mailchimp', $html );
        $this->assertStringContainsString( 'name="mailchimp_subscriber_status"', $html );
        $this->assertRenderedInputValue( $html, 'mailchimp_subscriber_status', 'subscribed' );
        $this->assertRenderedInputValue( $html, 'mailchimp_list_id', 'LIST_ROW4' );
        $variant_id = $this->invoke_mailchimp_private( 'mailchimp_variant_id', array( $element ) );
        $this->assertIsString( $variant_id );
        $this->assertStringContainsString( 'name="mailchimp_variant_' . $variant_id . '"', $html );

        $this->configure_settings( 'row4key-us6' );

        $selected = array( 'interest_a' );
        $data = array(
            'email' => array( 'name' => 'email', 'value' => 'row4-new@example.test', 'type' => 'var' ),
            'mailchimp_interests' => array(
                'name' => 'mailchimp_interests',
                'value' => implode( ',', $selected ),
                'selected_values' => $selected,
                'type' => 'var',
            ),
            'mailchimp_subscriber_status' => array( 'name' => 'mailchimp_subscriber_status', 'value' => 'subscribed', 'type' => 'var' ),
            'mailchimp_list_id' => array( 'name' => 'mailchimp_list_id', 'value' => 'LIST_ROW4', 'type' => 'var' ),
            ( 'mailchimp_variant_' . $variant_id ) => array( 'name' => 'mailchimp_variant_' . $variant_id, 'value' => '1', 'type' => 'var' ),
        );
        $this->set_submit_request( $form_id, $data );

        $http_capture = $this->new_capture_file( 'sf-row4-mc-a-http-' );
        $this->install_http_stub( $http_capture, null );

        $consumer_capture = $this->new_capture_file( 'sf-row4-mc-a-consumer-' );
        $result = $this->with_mailchimp_consumer(
            function() {
                return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
            },
            $consumer_capture
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );

        // The generic data payload is stripped before the hook; the consumer inspects the post data copy.
        $consumed = $this->read_jsonl( $consumer_capture );
        $this->assertCount( 1, $consumed );
        $this->assertArrayNotHasKey( 'mailchimp_interests', $consumed[0]['data'] );
        $this->assertArrayNotHasKey( 'mailchimp_list_id', $consumed[0]['data'] );
        // submit_form_checks republishes $_POST['data'] slashed, exactly like an
        // incoming WordPress request (class-ajax.php:6662-6665, 8073), so a
        // consumer must unslash before decoding - see the add-on's own
        // update_mailchimp_subscribers().
        $post_data = json_decode( wp_unslash( $consumed[0]['post']['data'] ), true );
        $this->assertIsArray( $post_data );
        $received_interests = $post_data['mailchimp_interests'];
        $this->assertArrayNotHasKey( 'selected_values', $received_interests );
        $this->assertSame( 'interest_a', $received_interests['value'] );

        $requests = $this->read_jsonl( $http_capture );
        $this->assertCount( 2, $requests, $result['output'] );
        $this->assertSame( 'GET', $requests[0]['method'] );
        $this->assertSame( 'POST', $requests[1]['method'] );
        $body = json_decode( $requests[1]['body'], true );
        $this->assertSame( array( 'interest_a' => true ), $body['interests'] );
        $this->assertSame( 'row4-new@example.test', $body['email_address'] );

        @unlink( $http_capture );
        @unlink( $consumer_capture );
    }

    public function test_interests_public_submit_deactivates_and_activates_interests_for_an_existing_member() {
        $element = $this->mailchimp_element_data();
        $form_id = $this->create_mailchimp_form( array( $element ) );
        $variant_id = $this->invoke_mailchimp_private( 'mailchimp_variant_id', array( $element ) );
        $rendered = $this->render_mailchimp_form( $form_id );
        $this->assertStringContainsString( 'name="mailchimp_variant_' . $variant_id . '"', $rendered );
        $this->configure_settings( 'row4key-us6' );

        // The request toggles the selected interests only.
        $selected = array( 'interest_b' );
        $data = array(
            'email' => array( 'name' => 'email', 'value' => 'row4-existing@example.test', 'type' => 'var' ),
            'mailchimp_interests' => array(
                'name' => 'mailchimp_interests',
                'value' => implode( ',', $selected ),
                'selected_values' => $selected,
                'type' => 'var',
            ),
            'mailchimp_subscriber_status' => array( 'name' => 'mailchimp_subscriber_status', 'value' => 'subscribed', 'type' => 'var' ),
            'mailchimp_list_id' => array( 'name' => 'mailchimp_list_id', 'value' => 'LIST_ROW4', 'type' => 'var' ),
            ( 'mailchimp_variant_' . $variant_id ) => array( 'name' => 'mailchimp_variant_' . $variant_id, 'value' => '1', 'type' => 'var' ),
        );
        $this->set_submit_request( $form_id, $data );

        $http_capture = $this->new_capture_file( 'sf-row4-mc-b-http-' );
        $this->install_http_stub( $http_capture, array(
            'status' => 'subscribed',
            'interests' => array( 'interest_a' => true, 'interest_b' => false ),
        ) );

        $result = $this->with_mailchimp_consumer( function() {
            return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        } );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );

        $requests = $this->read_jsonl( $http_capture );
        $this->assertCount( 2, $requests, $result['output'] );
        $this->assertSame( 'GET', $requests[0]['method'] );
        $this->assertSame( 'PATCH', $requests[1]['method'] );
        $body = json_decode( $requests[1]['body'], true );
        // Existing unselected interests are cleared; selected interests are enabled.
        $this->assertSame( array( 'interest_a' => false, 'interest_b' => true ), $body['interests'] );

        @unlink( $http_capture );
    }

    public function test_identical_duplicate_mailchimp_variant_elements_are_accepted_as_one_audience() {
        $element = $this->mailchimp_element_data( array( 'display_interests' => 'no' ) );
        // Byte-identical audience configuration reuses the same variant field name.
        $form_id = $this->create_mailchimp_form( array( $element, $element ) );
        $variant_id = $this->invoke_mailchimp_private( 'mailchimp_variant_id', array( $element ) );
        $rendered = $this->render_mailchimp_form( $form_id );
        // Both rendered occurrences reuse the same variant hash.
        $this->assertSame(
            2,
            substr_count( $rendered, 'name="mailchimp_variant_' . $variant_id . '"' )
        );
        $this->configure_settings( 'row4key-us6' );

        $data = array(
            'email' => array( 'name' => 'email', 'value' => 'row4-duplicate@example.test', 'type' => 'var' ),
            'mailchimp_subscriber_status' => array( 'name' => 'mailchimp_subscriber_status', 'value' => 'subscribed', 'type' => 'var' ),
            'mailchimp_list_id' => array( 'name' => 'mailchimp_list_id', 'value' => 'LIST_ROW4', 'type' => 'var' ),
            ( 'mailchimp_variant_' . $variant_id ) => array( 'name' => 'mailchimp_variant_' . $variant_id, 'value' => '1', 'type' => 'var' ),
        );
        $this->set_submit_request( $form_id, $data );

        $http_capture = $this->new_capture_file( 'sf-row4-mc-c-http-' );
        $this->install_http_stub( $http_capture, null );

        $result = $this->with_mailchimp_consumer( function() {
            return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        } );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );

        // The identical duplicate resolves to one audience update.
        $requests = $this->read_jsonl( $http_capture );
        $this->assertCount( 2, $requests, $result['output'] );
        $this->assertSame( 'POST', $requests[1]['method'] );
        $body = json_decode( $requests[1]['body'], true );
        $this->assertSame( 'row4-duplicate@example.test', $body['email_address'] );

        @unlink( $http_capture );
    }

    public function test_conflicting_mailchimp_variant_identity_is_refused_with_no_api_call() {
        $element_a = $this->mailchimp_element_data( array( 'display_interests' => 'no', 'list_id' => 'LIST_ROW4_A' ) );
        $element_b = $this->mailchimp_element_data( array( 'display_interests' => 'no', 'list_id' => 'LIST_ROW4_B' ) );
        $form_id = $this->create_mailchimp_form( array( $element_a, $element_b ) );
        $variant_a = $this->invoke_mailchimp_private( 'mailchimp_variant_id', array( $element_a ) );
        $variant_b = $this->invoke_mailchimp_private( 'mailchimp_variant_id', array( $element_b ) );
        $this->assertNotSame( $variant_a, $variant_b );
        // Both rendered audience blocks emit distinct variant fields.
        $rendered = $this->render_mailchimp_form( $form_id );
        $this->assertStringContainsString( 'name="mailchimp_variant_' . $variant_a . '"', $rendered );
        $this->assertStringContainsString( 'name="mailchimp_variant_' . $variant_b . '"', $rendered );
        $this->configure_settings( 'row4key-us6' );

        // A conflicting multi-audience submission is rejected before transport.
        $data = array(
            'email' => array( 'name' => 'email', 'value' => 'row4-conflict@example.test', 'type' => 'var' ),
            'mailchimp_subscriber_status' => array( 'name' => 'mailchimp_subscriber_status', 'value' => 'subscribed', 'type' => 'var' ),
            'mailchimp_list_id' => array( 'name' => 'mailchimp_list_id', 'value' => 'LIST_ROW4_A', 'type' => 'var' ),
            ( 'mailchimp_variant_' . $variant_a ) => array( 'name' => 'mailchimp_variant_' . $variant_a, 'value' => '1', 'type' => 'var' ),
            ( 'mailchimp_variant_' . $variant_b ) => array( 'name' => 'mailchimp_variant_' . $variant_b, 'value' => '1', 'type' => 'var' ),
        );
        $this->set_submit_request( $form_id, $data );

        $http_capture = $this->new_capture_file( 'sf-row4-mc-d-http-' );
        $this->install_http_stub( $http_capture, null );

        $result = $this->with_mailchimp_consumer( function() {
            return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        } );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertTrue( $decoded['error'], $result['output'] );
        $this->assertStringContainsString( 'invalid form data', strtolower( wp_strip_all_tags( $decoded['msg'] ) ) );

        // Refused before any Mailchimp API traffic.
        $requests = $this->read_jsonl( $http_capture );
        $this->assertSame( array(), $requests, $result['output'] );

        @unlink( $http_capture );
    }
}
