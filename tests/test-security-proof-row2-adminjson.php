<?php
/**
 * Proof package - Matrix rows 2 and 14.
 *
 * Row 2 covers rejected public registration states plus one denied
 * update_contact_entry admin-AJAX mutation with byte-identical snapshots.
 *
 * Row 14 covers atomic rejection of invalid update_contact_entry payloads,
 * stored-type-aware sanitization on admin edits, and resend-activation rate
 * limiting across eligible forms.
 *
 * @package Super_Forms_Tests
 */

require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Super_Forms_Proof_Row2_Row14_Register_Admin_Json extends Super_Forms_Upload_Security_Test_Case {

    private $created_users = array();
    private $created_client_sessions = array();

    public function set_up() {
        parent::set_up();
        $this->require_register_login_addon();
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
        foreach( array_unique( $this->created_client_sessions ) as $session_id ) {
            delete_option( '_sfsdata_' . $session_id );
        }
        parent::tear_down();
    }

    /* ------------------------------------------------------------------ *
     *  Shared helpers
     * ------------------------------------------------------------------ */

    private function require_register_login_addon() {
        if( !class_exists( 'SUPER_Register_Login' ) ) {
            require_once dirname( __DIR__ ) . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
    }

    private function unique_token( $prefix ) {
        return $prefix . '_' . substr( md5( uniqid( $prefix, true ) ), 0, 12 );
    }

    private function new_capture_file( $prefix ) {
        $file = tempnam( sys_get_temp_dir(), $prefix );
        $this->assertNotFalse( $file );
        return $file;
    }

    private function captured_invocation_count( $file ) {
        $lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        return is_array( $lines ) ? count( $lines ) : 0;
    }
    private function track_current_client_session() {
        if( isset( $_COOKIE['_sfs_id'] ) && is_string( $_COOKIE['_sfs_id'] ) && $_COOKIE['_sfs_id']!=='' ) {
            $this->created_client_sessions[] = wp_unslash( $_COOKIE['_sfs_id'] );
        }
    }


    /* -- registration (Row 2, duplicate / malformed / administrator) --- */

    private function registration_settings( $role='subscriber' ) {
        return array(
            'register_login_action' => 'register',
            'register_user_role' => $role,
            'register_login_action_skip_register' => '',
            'register_login_activation' => 'none',
            'register_login_show_toolbar' => '',
            'register_user_signup_status' => 'active',
            'register_send_approve_email' => '',
            'register_login_multisite_enabled' => '',
            'register_login_user_meta' => '',
            'register_login_update_user_meta' => '',
        );
    }

    private function base_registration_elements() {
        return array(
            array( 'tag' => 'text', 'data' => array( 'name' => 'user_login' ) ),
            array( 'tag' => 'text', 'data' => array( 'name' => 'user_email', 'validation' => 'email' ) ),
            array( 'tag' => 'password', 'data' => array( 'name' => 'user_pass' ) ),
        );
    }

    private function duplicate_role_field_elements() {
        $role_field = array(
            'tag' => 'radio',
            'data' => array(
                'name' => 'role',
                'retrieve_method' => 'custom',
                'radio_items' => array(
                    array( 'checked' => false, 'label' => 'Subscriber', 'value' => 'subscriber' ),
                ),
            ),
        );
        // Two elements sharing the same field name make the legacy 'role'
        // lookup ambiguous: resolve_saved_registration_role_choice() requires
        // exactly one match and returns 'invalid_field' when count()!==1.
        return array_merge( $this->base_registration_elements(), array( $role_field, $role_field ) );
    }

    private function plain_text_role_field_elements() {
        // A single 'role' field exists, but it is an ordinary TEXT field rather
        // than a dropdown/radio choice element, so
        // resolve_saved_registration_role_choice() reports it as 'not_selector'.
        // Through the LEGACY probe this is treated like a missing field: the
        // injected value is ignored and registration proceeds with the safe
        // configured/default role.
        return array_merge(
            $this->base_registration_elements(),
            array( array( 'tag' => 'text', 'data' => array( 'name' => 'role' ) ) )
        );
    }

    private function registration_carriers( $login, $email, $password ) {
        return array(
            'user_login' => array( 'name' => 'user_login', 'value' => $login, 'type' => 'var' ),
            'user_email' => array( 'name' => 'user_email', 'value' => $email, 'type' => 'var' ),
            'user_pass' => array( 'name' => 'user_pass', 'value' => $password, 'type' => 'var' ),
        );
    }

    /**
     * Register the real `before_sending_email` consumer on the real WordPress
     * action used by `SUPER_Ajax::submit_form_checks()`
     * (`do_action('super_before_sending_email_hook', ...)`), run the callback,
     * then restore hook state. This is the genuine dispatch path -- the
     * add-on's own constructor only wires this hook when `DOING_AJAX` was
     * already defined at construction time, which does not hold for a
     * `require_once`-loaded add-on inside PHPUnit, so the corpus's own
     * `test-security-submission-contract.php` uses the identical
     * add_action/has_action guard before invoking a forked submission.
     */
    private function with_registration_hook( $callback ) {
        $consumer = array( 'SUPER_Register_Login', 'before_sending_email' );
        $added = false;
        if( !has_action( 'super_before_sending_email_hook', $consumer ) ) {
            add_action( 'super_before_sending_email_hook', $consumer, 10, 1 );
            $added = true;
        }
        try {
            return call_user_func( $callback );
        } finally {
            if( $added ) {
                remove_action( 'super_before_sending_email_hook', $consumer, 10 );
            }
        }
    }

    private function attempt_forked_registration( $form_id, $login, $email, $extra_carriers=array() ) {
        $this->configure_csrf( 'false' );
        $data = array_merge(
            $this->registration_carriers( $login, $email, 'Registration-password-123!' ),
            $extra_carriers
        );
        $this->set_submit_request( $form_id, $data );
        return $this->with_registration_hook( function() {
            return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        } );
    }

    private function assert_registration_rejected( $result, $login ) {
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertTrue( $decoded['error'] );
        $this->assertIsString( $decoded['msg'] );
        $this->assertNotSame( '', trim( wp_strip_all_tags( $decoded['msg'] ) ) );
        $this->assertFalse( get_user_by( 'login', $login ) );
    }

    private function assert_registration_created_with_role( $result, $login, $role ) {
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );
        $user = get_user_by( 'login', $login );
        $this->assertInstanceOf( 'WP_User', $user );
        $this->created_users[] = $user->ID;
        $this->assertSame( array( $role ), array_values( $user->roles ) );
        $this->assertFalse( user_can( $user, 'manage_options' ) );
        return $user;
    }

    /* -- admin-ajax contact entry mutation (Row 2 denial / Row 14) ------ */

    private function create_admin_actor() {
        $user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        $this->created_users[] = $user_id;
        wp_set_current_user( $user_id );
        return $user_id;
    }

    private function admin_ajax_nonce() {
        return wp_create_nonce( 'super_admin_ajax' );
    }

    private function create_snapshot_entry( $data, $status='super_unread', $custom_status='reviewed', $title='Baseline title' ) {
        $entry_id = wp_insert_post(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => $status,
                'post_title' => $title,
            ),
            true
        );
        $this->assertNotWPError( $entry_id );
        update_post_meta( $entry_id, '_super_contact_entry_status', $custom_status );
        SUPER_Data_Access::update_entry_data( $entry_id, $data );
        return (int) $entry_id;
    }

    private function snapshot_entry( $entry_id ) {
        $post = get_post( $entry_id, ARRAY_A );
        $meta = get_post_meta( $entry_id );
        if( is_array( $post ) ) {
            ksort( $post );
        }
        ksort( $meta );
        return array( 'post' => $post, 'meta' => $meta );
    }
    private function snapshot_user_state( $user_id, $keys=array( 'super_user_login_status', 'super_account_status', 'super_account_activation' ) ) {
        $snapshot = array(
            'user_id' => absint( $user_id ),
            'meta' => array(),
        );
        foreach( $keys as $key ) {
            $snapshot['meta'][$key] = get_user_meta( $user_id, $key, true );
        }
        ksort( $snapshot['meta'] );
        return $snapshot;
    }


    /* -- resend-activation guard (Row 14) ------------------------------- */

    private function create_pending_user( $login ) {
        $email = $login . '@example.test';
        $user_id = self::factory()->user->create( array(
            'user_login' => $login,
            'user_email' => $email,
            'role' => 'subscriber',
        ) );
        $this->created_users[] = $user_id;
        update_user_meta( $user_id, 'super_user_login_status', 'pending' );
        update_user_meta( $user_id, 'super_account_status', 0 );
        update_user_meta( $user_id, 'super_account_activation', $this->unique_token( 'code' ) );
        return array( $user_id, $email );
    }

    private function verification_registration_settings() {
        return array(
            'register_login_action' => 'register',
            'register_user_role' => 'subscriber',
            'register_login_activation' => 'verify',
            'register_activation_subject' => 'Activate your account',
            'register_activation_email' => 'Code: {register_activation_code}',
            'register_login_url' => 'https://example.test/activate',
        );
    }

    private function resend_activation_rate_limit_key_for( $user_id, $email ) {
        $method = new ReflectionMethod( 'SUPER_Register_Login', 'resend_activation_rate_limit_key' );
        $method->setAccessible( true );
        return $method->invoke( null, $user_id, $email );
    }

    private function resend_activation_daily_limit_key_for( $user_id ) {
        $method = new ReflectionMethod( 'SUPER_Register_Login', 'resend_activation_daily_limit_key' );
        $method->setAccessible( true );
        return $method->invoke( null, $user_id );
    }

    private function resend_activation_form_settings_for( $form_id ) {
        $method = new ReflectionMethod( 'SUPER_Register_Login', 'resend_activation_form_settings' );
        $method->setAccessible( true );
        return $method->invoke( null, $form_id );
    }
    private function issue_pending_registration_recovery_for( $user_id, $form_id, $login, $email ) {
        $method = new ReflectionMethod( 'SUPER_Register_Login', 'issue_pending_registration_recovery' );
        $method->setAccessible( true );
        $result = $method->invoke( null, $user_id, $form_id, $login, $email );
        $this->track_current_client_session();
        return $result;
    }

    private function resend_activation_request( $username, $user_email, $form_id ) {
        $_POST = array(
            'action' => 'super_resend_activation',
            'data' => array(
                'username' => $username,
                'email' => $user_email,
                'form' => (string) $form_id,
            ),
            'nonce' => wp_create_nonce( 'super_resend_activation' ),
        );
        $_REQUEST = $_POST;
        $result = $this->run_dying_handler( array( 'SUPER_Register_Login', 'resend_activation' ) );
        $this->track_current_client_session();
        return $result;
    }

    /* ------------------------------------------------------------------ *
     *  Row 2 - forked public registration failures
     * ------------------------------------------------------------------ */

    public function test_forked_public_registration_rejects_duplicate_role_field_configuration() {
        $form_id = $this->create_form( 'publish', $this->duplicate_role_field_elements(), $this->registration_settings( 'subscriber' ) );
        $login = $this->unique_token( 'sf_dup_role' );
        $email = $login . '@example.test';

        $result = $this->attempt_forked_registration( $form_id, $login, $email );

        $this->assert_registration_rejected( $result, $login );
    }

    public function test_forked_public_registration_ignores_a_plain_text_role_field_and_uses_the_safe_configured_role() {
        // Corrected split: a form that merely contains an ordinary TEXT field
        // named 'role' must NOT hard-fail registration. Through the LEGACY
        // probe the 'not_selector' shape is treated like a missing field, the
        // injected role=administrator value is ignored, and the user is created
        // with the safe configured/default role.
        $form_id = $this->create_form( 'publish', $this->plain_text_role_field_elements(), $this->registration_settings( 'subscriber' ) );
        $login = $this->unique_token( 'sf_text_role' );
        $email = $login . '@example.test';

        $result = $this->attempt_forked_registration(
            $form_id,
            $login,
            $email,
            array( 'role' => array( 'name' => 'role', 'value' => 'administrator', 'type' => 'text' ) )
        );

        $user = $this->assert_registration_created_with_role( $result, $login, 'subscriber' );
        $this->assertFalse( in_array( 'administrator', (array) $user->roles, true ) );
    }

    public function test_forked_public_registration_rejects_a_stored_administrator_role_via_the_real_before_sending_email_consumer() {
        $form_id = $this->create_form( 'publish', $this->base_registration_elements(), $this->registration_settings( 'administrator' ) );
        $login = $this->unique_token( 'sf_admin_role' );
        $email = $login . '@example.test';

        // Dispatched through do_action('super_before_sending_email_hook', ...)
        // inside the real forked SUPER_Ajax::submit_form lifecycle -- see
        // with_registration_hook(). Not a direct call into
        // SUPER_Register_Login::before_sending_email() or the private
        // get_safe_public_registration_role() helper.
        $result = $this->attempt_forked_registration( $form_id, $login, $email );

        $this->assert_registration_rejected( $result, $login );
        $decoded = json_decode( $result['output'], true );
        $this->assertStringContainsString(
            'not allowed',
            strtolower( wp_strip_all_tags( $decoded['msg'] ) )
        );
    }

    /* ------------------------------------------------------------------ *
     *  Row 2 - denied admin-AJAX mutation (atomic, byte-exact snapshot)
     * ------------------------------------------------------------------ */

    public function test_denied_admin_ajax_update_contact_entry_rejects_an_empty_field_name_atomically() {
        $this->create_admin_actor();
        $protected_user_id = self::factory()->user->create( array(
            'role' => 'subscriber',
            'user_login' => $this->unique_token( 'sf_row2_state' ),
            'user_email' => $this->unique_token( 'sf_row2_state_email' ) . '@example.test',
        ) );
        $this->created_users[] = $protected_user_id;
        update_user_meta( $protected_user_id, 'super_user_login_status', 'pending' );
        update_user_meta( $protected_user_id, 'super_account_status', 0 );
        update_user_meta( $protected_user_id, 'super_account_activation', $this->unique_token( 'row2-code' ) );
        $initial = array(
            'note' => array( 'name' => 'note', 'value' => 'baseline note', 'type' => 'var' ),
        );
        $entry_id = $this->create_snapshot_entry( $initial );
        $before = $this->snapshot_entry( $entry_id );
        $user_before = $this->snapshot_user_state( $protected_user_id );
        $_POST = array(
            'action' => 'super_update_contact_entry',
            'nonce' => $this->admin_ajax_nonce(),
            'id' => $entry_id,
            'entry_status' => 'attacker-status',
            'data' => array(
                '' => 'attacker-value',
                'note' => 'attacker-note',
                'super_contact_entry_post_title' => 'Attacker title',
            ),
        );
        $_REQUEST = $_POST;

        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'update_contact_entry' ) );

        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertTrue( $decoded['error'] );
        $this->assertStringContainsString( 'Invalid contact entry data', wp_strip_all_tags( $decoded['msg'] ) );
        $this->assertSame( $before, $this->snapshot_entry( $entry_id ) );
        $this->assertSame( $user_before, $this->snapshot_user_state( $protected_user_id ) );
    }

    /* ------------------------------------------------------------------ *
     *  Row 14 - authorized admin update_contact_entry
     * ------------------------------------------------------------------ */

    public function test_authorized_admin_update_contact_entry_atomically_rejects_data_mixing_scalars_and_one_array_value() {
        $this->create_admin_actor();
        $initial = array(
            'note' => array( 'name' => 'note', 'value' => 'baseline note', 'type' => 'var' ),
            'plan' => array( 'name' => 'plan', 'value' => 'baseline plan', 'type' => 'var' ),
        );
        $entry_id = $this->create_snapshot_entry( $initial );
        $before = $this->snapshot_entry( $entry_id );

        $_POST = array(
            'action' => 'super_update_contact_entry',
            'nonce' => $this->admin_ajax_nonce(),
            'id' => $entry_id,
            'entry_status' => 'attacker-status',
            'data' => array(
                'note' => 'authorized scalar value',
                'plan' => array( 'nested' => 'array value' ),
                'super_contact_entry_post_title' => 'Authorized title change',
            ),
        );
        $_REQUEST = $_POST;

        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'update_contact_entry' ) );

        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertTrue( $decoded['error'] );
        $this->assertStringContainsString( 'Invalid contact entry data', wp_strip_all_tags( $decoded['msg'] ) );
        $this->assertSame( $before, $this->snapshot_entry( $entry_id ) );
    }

    public function test_authorized_admin_update_contact_entry_persists_a_typed_multiline_textarea_field_via_sanitize_textarea_field() {
        $this->create_admin_actor();
        $initial = array(
            'multiline_note' => array( 'name' => 'multiline_note', 'value' => 'baseline', 'type' => 'text' ),
            'plain_note' => array( 'name' => 'plain_note', 'value' => 'baseline', 'type' => 'var' ),
        );
        $entry_id = $this->create_snapshot_entry( $initial );

        $raw_input = "Line one\nLine two   <b>bold</b>   trailing spaces   ";
        $_POST = array(
            'action' => 'super_update_contact_entry',
            'nonce' => $this->admin_ajax_nonce(),
            'id' => $entry_id,
            'entry_status' => 'reviewed',
            'data' => array(
                'multiline_note' => $raw_input,
                'plain_note' => $raw_input,
            ),
        );
        $_REQUEST = $_POST;

        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'update_contact_entry' ) );

        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'] );

        $stored = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( sanitize_textarea_field( $raw_input ), $stored['multiline_note']['value'] );
        $this->assertSame( sanitize_text_field( $raw_input ), $stored['plain_note']['value'] );
        $this->assertNotSame( $stored['multiline_note']['value'], $stored['plain_note']['value'] );
    }

    /* ------------------------------------------------------------------ *
     *  Row 14 - resend-activation guard
     * ------------------------------------------------------------------ */

    public function test_resend_activation_requires_the_dedicated_wp_nonce_before_any_processing() {
        list( $user_id, $email ) = $this->create_pending_user( $this->unique_token( 'sf_resend_nonce' ) );
        $login = get_userdata( $user_id )->user_login;
        $form_id = $this->create_form( 'publish', array(), $this->verification_registration_settings() );
        $this->assertTrue( $this->issue_pending_registration_recovery_for( $user_id, $form_id, $login, $email ) );
        unset( $_COOKIE['_sfs_id'] );

        $rate_limit_key = $this->resend_activation_rate_limit_key_for( $user_id, $email );
        $daily_limit_key = $this->resend_activation_daily_limit_key_for( $user_id );
        $before = $this->snapshot_user_state( $user_id );
        $_POST = array(
            'action' => 'super_resend_activation',
            'data' => array(
                'username' => $login,
                'email' => $email,
                'form' => (string) $form_id,
            ),
        );
        $_REQUEST = $_POST;

        $result = $this->run_dying_handler( array( 'SUPER_Register_Login', 'resend_activation' ) );
        $this->track_current_client_session();

        $this->assertSame( 0, $result['status'], $result['output'] );
        $this->assertStringContainsString( '-1', trim( $result['output'] ) );
        $this->assertFalse( get_transient( $rate_limit_key ) );
        $this->assertFalse( get_transient( $daily_limit_key ) );
        $this->assertSame( $before, $this->snapshot_user_state( $user_id ) );
    }

    public function test_resend_activation_refuses_a_different_form_id_without_overwriting_the_existing_rate_limit() {
        list( $user_id, $email ) = $this->create_pending_user( $this->unique_token( 'sf_resend' ) );
        $login = get_userdata( $user_id )->user_login;
        $form_a = $this->create_form( 'publish', array(), $this->verification_registration_settings() );
        $form_b = $this->create_form( 'publish', array(), $this->verification_registration_settings() );
        $this->assertIsArray( $this->resend_activation_form_settings_for( $form_b ) );
        $this->assertNotSame( $form_a, $form_b );
        $this->assertTrue( $this->issue_pending_registration_recovery_for( $user_id, $form_a, $login, $email ) );
        unset( $_COOKIE['_sfs_id'] );

        $rate_limit_key = $this->resend_activation_rate_limit_key_for( $user_id, $email );
        $this->assertFalse( get_transient( $rate_limit_key ) );
        $seeded_value = time() - 5;
        $this->assertTrue( set_transient( $rate_limit_key, $seeded_value, MINUTE_IN_SECONDS ) );

        $capture_file = $this->new_capture_file( 'sf-row14-resend-form-' );
        $send_count = 0;
        $capture = static function( $message, $context ) use ( $capture_file ) {
            file_put_contents( $capture_file, wp_json_encode( $context ) . "\n", FILE_APPEND | LOCK_EX );
            return $message;
        };
        add_filter( 'super_before_sending_verification_email_body_filter', $capture, 10, 2 );
        try {
            $result = $this->resend_activation_request( $login, $email, $form_b );
            $send_count = $this->captured_invocation_count( $capture_file );
        } finally {
            remove_filter( 'super_before_sending_verification_email_body_filter', $capture, 10 );
            @unlink( $capture_file );
        }

        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'] );
        $this->assertStringContainsString( 'If the account can receive verification messages', $decoded['msg'] );
        $this->assertStringNotContainsString( $login, $decoded['msg'] );
        $this->assertStringNotContainsString( $email, $decoded['msg'] );
        $this->assertSame( 0, $send_count, 'A resend bound to one registration form must not send against a different form id.' );
        $this->assertSame( $seeded_value, absint( get_transient( $rate_limit_key ) ), 'A refused resend must not overwrite the existing per-minute limit.' );
    }
    public function test_resend_activation_without_the_bound_recovery_token_denies_generically_without_sending_or_setting_rate_limits() {
        list( $user_id, $email ) = $this->create_pending_user( $this->unique_token( 'sf_resend_daily' ) );
        $login = get_userdata( $user_id )->user_login;
        $form_id = $this->create_form( 'publish', array(), $this->verification_registration_settings() );
        $this->assertTrue( $this->issue_pending_registration_recovery_for( $user_id, $form_id, $login, $email ) );
        unset( $_COOKIE['_sfs_id'] );

        $rate_limit_key = $this->resend_activation_rate_limit_key_for( $user_id, $email );
        $daily_limit_key = $this->resend_activation_daily_limit_key_for( $user_id );
        $results = array();
        $capture_file = $this->new_capture_file( 'sf-row14-resend-token-' );
        $send_count = 0;
        $capture = static function( $message, $context ) use ( $capture_file ) {
            file_put_contents( $capture_file, wp_json_encode( $context ) . "\n", FILE_APPEND | LOCK_EX );
            return $message;
        };
        add_filter( 'super_before_sending_verification_email_body_filter', $capture, 10, 2 );
        try {
            for( $i = 0; $i < 6; $i++ ) {
                $results[] = $this->resend_activation_request( $login, $email, $form_id );
            }
            $send_count = $this->captured_invocation_count( $capture_file );
        } finally {
            remove_filter( 'super_before_sending_verification_email_body_filter', $capture, 10 );
            @unlink( $capture_file );
        }

        foreach( $results as $attempt => $result ) {
            $this->assertSame( 0, $result['status'], 'Attempt ' . ($attempt + 1) . ': ' . $result['output'] );
            $decoded = json_decode( $result['output'], true );
            $this->assertIsArray( $decoded, 'Attempt ' . ($attempt + 1) . ': ' . $result['output'] );
            $this->assertFalse( $decoded['error'], 'Attempt ' . ($attempt + 1) );
            $this->assertStringContainsString( 'If the account can receive verification messages', $decoded['msg'] );
            $this->assertStringNotContainsString( $login, $decoded['msg'] );
            $this->assertStringNotContainsString( $email, $decoded['msg'] );
        }
        $this->assertSame( 0, $send_count, 'A resend without the bound recovery token must never send a verification message.' );
        $this->assertFalse( get_transient( $rate_limit_key ), 'A refused resend without the bound recovery token must not set the per-minute limit.' );
        $this->assertFalse( get_transient( $daily_limit_key ), 'A refused resend without the bound recovery token must not set the daily limit.' );
    }

    public function test_resend_activation_sends_nothing_for_an_unpublished_or_non_registration_form() {
        list( $user_id, $email ) = $this->create_pending_user( $this->unique_token( 'sf_resend_guard' ) );
        $login = get_userdata( $user_id )->user_login;
        $rate_limit_key = $this->resend_activation_rate_limit_key_for( $user_id, $email );

        $draft_form = $this->create_form( 'draft', array(), $this->verification_registration_settings() );
        $this->assertFalse( $this->resend_activation_form_settings_for( $draft_form ) );

        $update_form_settings = $this->verification_registration_settings();
        $update_form_settings['register_login_action'] = 'update';
        $non_registration_form = $this->create_form( 'publish', array(), $update_form_settings );
        $this->assertFalse( $this->resend_activation_form_settings_for( $non_registration_form ) );

        $capture_file = $this->new_capture_file( 'sf-row14-resend-formguard-' );
        $send_count = 0;
        $capture = static function( $message, $context ) use ( $capture_file ) {
            file_put_contents( $capture_file, wp_json_encode( $context ) . "\n", FILE_APPEND | LOCK_EX );
            return $message;
        };
        add_filter( 'super_before_sending_verification_email_body_filter', $capture, 10, 2 );
        try {
            $draft_result = $this->resend_activation_request( $login, $email, $draft_form );
            $non_registration_result = $this->resend_activation_request( $login, $email, $non_registration_form );
            $send_count = $this->captured_invocation_count( $capture_file );
        } finally {
            remove_filter( 'super_before_sending_verification_email_body_filter', $capture, 10 );
            @unlink( $capture_file );
        }

        foreach( array( 'unpublished form' => $draft_result, 'non-registration form' => $non_registration_result ) as $label => $result ) {
            $this->assertSame( 0, $result['status'], $label . ': ' . $result['output'] );
            $decoded = json_decode( $result['output'], true );
            $this->assertIsArray( $decoded, $label . ': ' . $result['output'] );
            $this->assertFalse( $decoded['error'], $label );
            $this->assertStringContainsString( 'If the account can receive verification messages', $decoded['msg'] );
            $this->assertStringNotContainsString( $login, $decoded['msg'] );
            $this->assertStringNotContainsString( $email, $decoded['msg'] );
        }
        $this->assertSame( 0, $send_count, 'An unpublished or non-registration form triggered a verification email attempt.' );
        $this->assertFalse( get_transient( $rate_limit_key ), 'An unpublished or non-registration form set the resend rate-limit transient.' );
    }
}
