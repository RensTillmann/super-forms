<?php

class Test_Super_Forms_Register_Login_Security extends WP_UnitTestCase {
    private $client_key;
    private $created_users = array();
    private $created_roles = array();
    private $added_filters = array();
    private $original_cookie_exists;
    private $original_cookie_value;
    private $original_post;
    private $original_request;
    private $sequence = 0;
    private $created_client_sessions = array();

    public static function set_up_before_class() {
        parent::set_up_before_class();
        // DOING_AJAX is never defined in the WP test bootstrap, so super-forms.php
        // is_request('ajax') is false and ajax_includes() never loads SUPER_Ajax.
        if( !class_exists( 'SUPER_Ajax' ) ) {
            require_once dirname( __DIR__ ) . '/includes/class-ajax.php';
        }
    }

    public function set_up() {
        parent::set_up();
        if( !class_exists( 'SUPER_Register_Login' ) ) {
            require_once dirname( __DIR__ ) . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }

        $this->original_cookie_exists = array_key_exists( '_sfs_id', $_COOKIE );
        $this->original_cookie_value = $this->original_cookie_exists ? $_COOKIE['_sfs_id'] : null;
        $this->original_post = $_POST;
        $this->original_request = $_REQUEST;
        $_POST = array();
        $_REQUEST = array();
        wp_set_current_user( 0 );
        // clear_user_meta_bridge() writes `update_user_meta => false`, and
        // SUPER_Common::setClientData() DELETES a session option that drops below
        // three keys (class-common.php:649-657). A served response would simply
        // re-issue the cookie; under the CLI SAPI setcookie() can never succeed
        // (headers already sent), so seed the session AFTER clearing the bridge and
        // require the hardened adoption path to accept it unchanged.
        $this->invoke_private( 'clear_user_meta_bridge' );
        // The hardened cookie format is strictly alphanumeric
        // (class-common.php:580-584); any other shape is rolled back, which would
        // make every setClientData() call a silent no-op.
        $this->client_key = 'sfsecurity' . str_replace( '-', '', wp_generate_uuid4() );
        $_COOKIE['_sfs_id'] = $this->client_key;
        update_option(
            '_sfsdata_' . $this->client_key,
            array(
                'expires' => time() + HOUR_IN_SECONDS,
                'exp_var' => time() + ( 20 * MINUTE_IN_SECONDS ),
            ),
            false
        );
        $this->assertSame( $this->client_key, SUPER_Common::startClientSession( array( 'force' => true ) ) );
    }

    public function tear_down() {
        wp_set_current_user( 0 );
        if( class_exists( 'SUPER_Register_Login' ) ) {
            $this->invoke_private( 'clear_user_meta_bridge' );
        }

        foreach( array_reverse($this->added_filters) as $filter ) {
            remove_filter( $filter[0], $filter[1], $filter[2] );
        }

        if( !function_exists('wp_delete_user') ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        foreach( array_unique($this->created_users) as $user_id ) {
            if( get_userdata($user_id)!==false ) {
                wp_delete_user( $user_id );
            }
        }
        foreach( array_unique($this->created_roles) as $role ) {
            remove_role( $role );
        }

        delete_option( '_sfsdata_' . $this->client_key );
        if( isset( $_COOKIE['_sfs_id'] ) && is_string( $_COOKIE['_sfs_id'] ) && $_COOKIE['_sfs_id']!=='' ) {
            delete_option( '_sfsdata_' . wp_unslash( $_COOKIE['_sfs_id'] ) );
        }
        foreach( array_unique( $this->created_client_sessions ) as $session_id ) {
            delete_option( '_sfsdata_' . $session_id );
        }
        if( $this->original_cookie_exists ) {
            $_COOKIE['_sfs_id'] = $this->original_cookie_value;
        }else{
            unset( $_COOKIE['_sfs_id'] );
        }
        $_POST = $this->original_post;
        $_REQUEST = $this->original_request;

        parent::tear_down();
    }

    private function invoke_private( $method_name, $arguments=array() ) {
        $method = new ReflectionMethod( 'SUPER_Register_Login', $method_name );
        $method->setAccessible( true );
        return $method->invokeArgs( null, $arguments );
    }
    private function track_current_client_session() {
        if( isset( $_COOKIE['_sfs_id'] ) && is_string( $_COOKIE['_sfs_id'] ) && $_COOKIE['_sfs_id']!=='' ) {
            $this->created_client_sessions[] = wp_unslash( $_COOKIE['_sfs_id'] );
        }
    }


    private function token( $prefix ) {
        $this->sequence++;
        return $prefix . '_' . $this->sequence . '_' . substr(md5($this->client_key), 0, 10);
    }

    private function create_user( $role='subscriber', $overrides=array() ) {
        $login = $this->token( 'sf_user' );
        $defaults = array(
            'user_login' => $login,
            'user_email' => $login . '@example.test',
            'user_pass' => 'Original-password-123!',
            'role' => $role,
        );
        $user_id = self::factory()->user->create( array_merge($defaults, $overrides) );
        $this->created_users[] = $user_id;
        return $user_id;
    }

    private function add_test_role( $role, $capabilities ) {
        remove_role( $role );
        $this->assertInstanceOf( 'WP_Role', add_role($role, $role, $capabilities) );
        $this->created_roles[] = $role;
        return $role;
    }

    private function register_settings( $role='subscriber', $meta_mapping='' ) {
        return array(
            'register_login_action' => 'register',
            'register_user_role' => $role,
            'register_login_action_skip_register' => '',
            'register_login_activation' => 'none',
            'register_login_show_toolbar' => '',
            'register_user_signup_status' => 'active',
            'register_send_approve_email' => '',
            'register_login_multisite_enabled' => '',
            'register_login_user_meta' => $meta_mapping,
            'register_login_update_user_meta' => '',
        );
    }

    private function update_settings( $meta_mapping='', $role='_super_keep_existing_role' ) {
        return array(
            'register_login_action' => 'update',
            'register_login_user_id_update' => 'true',
            'register_login_register_not_logged_in' => '',
            'register_login_not_logged_in_msg' => 'Please log in.',
            'register_login_show_toolbar' => '',
            'register_user_role' => $role,
            'register_login_update_user_meta' => $meta_mapping,
            'register_login_user_meta' => '',
            'register_login_action_skip_register' => '',
            'register_login_activation' => 'none',
            'register_user_signup_status' => 'active',
            'register_send_approve_email' => '',
            'register_login_multisite_enabled' => '',
        );
    }

    private function registration_data( $login, $email, $password='Registration-password-123!' ) {
        return array(
            'user_login' => array( 'type'=>'text', 'value'=>$login ),
            'user_email' => array( 'type'=>'email', 'value'=>$email ),
            'user_pass' => array( 'type'=>'password', 'value'=>$password ),
        );
    }

    private function request_atts( $settings, $data, $form_id=731 ) {
        $post = array(
            'action' => 'super_submit_form',
            'form_id' => (string) $form_id,
            'data' => wp_json_encode( $data ),
        );
        return array(
            'settings' => $settings,
            'data' => $data,
            'post' => $post,
            'entry_id' => 0,
            'attachments' => array(),
        );
    }

    private function set_request_globals( $post ) {
        $_POST = $post;
        $_REQUEST = $post;
    }

    private function begin_account_action( $settings, $data, $form_id=731 ) {
        $atts = $this->request_atts( $settings, $data, $form_id );
        $this->set_request_globals( $atts['post'] );
        SUPER_Register_Login::before_sending_email( $atts );
        return $atts;
    }

    private function consume_account_action( $atts ) {
        $this->set_request_globals( $atts['post'] );
        SUPER_Register_Login::before_email_success_msg( $atts );
    }

    private function strict_security_pcntl_required() {
        $flag = getenv( 'SUPER_FORMS_STRICT_SECURITY_TESTS' );
        return is_string($flag) && $flag!=='' && $flag!=='0' && strtolower($flag)!=='false';
    }

    private function require_process_forking( $message ) {
        if( function_exists('pcntl_fork') && function_exists('pcntl_waitpid') && function_exists('pcntl_exec') ) {
            return;
        }
        if( $this->strict_security_pcntl_required() ) {
            $this->fail( $message );
        }
        $this->markTestSkipped( $message );
    }

    private function run_dying_handler( $callback ) {
        $this->require_process_forking( 'The fail-closed account regression requires pcntl fork, wait, and exec support.' );
        $capture = tempnam( sys_get_temp_dir(), 'sf-account-die-' );
        $this->assertNotFalse( $capture );
        $pid = pcntl_fork();
        $this->assertNotSame( -1, $pid );
        if( $pid===0 ) {
            $returned = false;
            ob_start( static function( $buffer ) use ( $capture ) {
                file_put_contents( $capture, $buffer, FILE_APPEND | LOCK_EX );
                return '';
            } );
            // Preserve the parent's transactional mysqli connection across a bare die().
            register_shutdown_function( static function() use ( &$returned ) {
                $last_error = error_get_last();
                $fatal = $last_error && in_array(
                    $last_error['type'],
                    array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ),
                    true
                );
                $status = ( ! $returned && ! $fatal ) ? 0 : 97;
                while( ob_get_level() > 0 ) {
                    @ob_end_flush();
                }
                pcntl_exec( PHP_BINARY, array( '-r', 'exit(' . $status . ');' ) );
            } );
            try {
                call_user_func( $callback );
                $returned = true;
            } catch( Throwable $e ) {
                echo get_class($e) . ': ' . $e->getMessage();
                $returned = true;
            }
            exit(97);
        }
        $status = 0;
        pcntl_waitpid( $pid, $status );
        global $wpdb;
        if( isset($wpdb) && method_exists($wpdb, 'check_connection') ) {
            $wpdb->check_connection(false);
        }
        wp_cache_flush();
        $output = file_get_contents( $capture );
        unlink( $capture );
        return array(
            'output' => $output===false ? '' : $output,
            'status' => pcntl_wifexited($status) ? pcntl_wexitstatus($status) : null,
        );
    }

    private function user_state( $user_id, $meta_keys=array() ) {
        $user = get_userdata( $user_id );
        $meta = array();
        foreach( $meta_keys as $meta_key ) {
            $meta[$meta_key] = get_user_meta( $user_id, $meta_key, true );
        }
        return array(
            'email' => $user->user_email,
            'password_hash' => $user->user_pass,
            'roles' => array_values($user->roles),
            'meta' => $meta,
        );
    }

    private function assert_bridge_cleared() {
        $this->assertFalse( SUPER_Common::getClientData('update_user_meta') );
    }

    public function test_public_registration_accepts_the_configured_subscriber_role() {
        $login = $this->token( 'sf_register' );
        $email = $login . '@example.test';
        $data = $this->registration_data( $login, $email );
        $atts = $this->begin_account_action( $this->register_settings('subscriber'), $data );

        $user = get_user_by( 'login', $login );
        $this->assertInstanceOf( 'WP_User', $user );
        $this->created_users[] = $user->ID;
        $this->assertSame( array('subscriber'), array_values($user->roles) );

        $this->consume_account_action( $atts );
        $this->assert_bridge_cleared();
    }

    public function test_public_registration_ignores_a_submitted_administrator_role() {
        $login = $this->token( 'sf_injected_role' );
        $email = $login . '@example.test';
        $data = $this->registration_data( $login, $email );
        $data['role'] = array( 'type'=>'text', 'value'=>'administrator' );
        $atts = $this->begin_account_action( $this->register_settings('subscriber'), $data );

        $user = get_user_by( 'login', $login );
        $this->assertInstanceOf( 'WP_User', $user );
        $this->created_users[] = $user->ID;
        $this->assertSame( array('subscriber'), array_values($user->roles) );
        $this->assertFalse( user_can($user, 'manage_options') );

        $this->consume_account_action( $atts );
        $this->assert_bridge_cleared();
    }

    public function test_public_registration_rejects_administrator_and_unknown_configured_roles() {
        $this->assertFalse( $this->invoke_private('get_safe_public_registration_role', array(array('register_user_role'=>'administrator'))) );
        $this->assertFalse( $this->invoke_private('get_safe_public_registration_role', array(array('register_user_role'=>'sf_role_that_does_not_exist'))) );
    }
    public function test_public_registration_uses_the_wordpress_default_role_when_the_form_setting_is_missing_or_empty() {
        $previous = get_option( 'default_role' );
        update_option( 'default_role', 'subscriber', false );
        try {
            $this->assertSame(
                'subscriber',
                $this->invoke_private( 'get_safe_public_registration_role', array( array() ) )
            );
            $this->assertSame(
                'subscriber',
                $this->invoke_private( 'get_safe_public_registration_role', array( array( 'register_user_role' => '' ) ) )
            );
        } finally {
            update_option( 'default_role', $previous, false );
        }
    }

    public function test_public_registration_rejects_an_unsafe_wordpress_default_role() {
        $previous = get_option( 'default_role' );
        update_option( 'default_role', 'administrator', false );
        try {
            $this->assertFalse(
                $this->invoke_private( 'get_safe_public_registration_role', array( array( 'register_user_role' => '' ) ) )
            );
        } finally {
            update_option( 'default_role', $previous, false );
        }
    }

    public function test_public_registration_accepts_one_saved_dropdown_role_choice_and_falls_back_from_out_of_set_values() {
        $previous = get_option( 'default_role' );
        update_option( 'default_role', 'subscriber', false );
        try {
            $role = 'sf_member_' . substr(md5($this->client_key), 0, 12);
            $this->add_test_role(
                $role,
                array(
                    'read' => true,
                    'level_0' => true,
                )
            );
            $form_id = self::factory()->post->create( array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            ) );
            update_post_meta(
                $form_id,
                '_super_elements',
                array(
                    array(
                        'tag' => 'dropdown',
                        'data' => array(
                            'name' => 'desired_role',
                            'retrieve_method' => 'custom',
                            'dropdown_items' => array(
                                array( 'checked' => false, 'label' => 'Member', 'value' => $role . ';Member' ),
                                array( 'checked' => false, 'label' => 'Subscriber', 'value' => 'subscriber;Subscriber' ),
                            ),
                        ),
                    ),
                )
            );
            $data = $this->registration_data( $this->token( 'sf_dropdown_role' ), $this->token( 'sf_dropdown_mail' ) . '@example.test' );
            $data['desired_role'] = array( 'type' => 'var', 'value' => $role );
            $this->assertSame(
                $role,
                $this->invoke_private(
                    'get_safe_public_registration_role',
                    array( array( 'register_user_role' => '{desired_role}' ), $data, $form_id )
                )
            );
            $data['desired_role']['value'] = 'administrator';
            $this->assertSame(
                'subscriber',
                $this->invoke_private(
                    'get_safe_public_registration_role',
                    array( array( 'register_user_role' => '{desired_role}' ), $data, $form_id )
                )
            );
        } finally {
            update_option( 'default_role', $previous, false );
        }
    }

    public function test_public_registration_accepts_nested_saved_radio_member_roles_and_safe_built_in_content_roles() {
        $role = 'sf_member_account_' . substr(md5($this->client_key), 0, 12);
        $this->add_test_role(
            $role,
            array(
                'read' => true,
                'level_0' => true,
                'view_account' => true,
            )
        );
        $form_id = self::factory()->post->create( array(
            'post_type' => 'super_form',
            'post_status' => 'publish',
        ) );
        update_post_meta(
            $form_id,
            '_super_elements',
            array(
                array(
                    'tag' => 'column',
                    'inner' => array(
                        array(
                            'tag' => 'radio',
                            'data' => array(
                                'name' => 'desired_role',
                                'retrieve_method' => 'custom',
                                'radio_items' => array(
                                    array( 'checked' => false, 'label' => 'Member', 'value' => $role ),
                                    array( 'checked' => false, 'label' => 'Author', 'value' => 'author' ),
                                ),
                            ),
                        ),
                    ),
                ),
            )
        );
        $data = $this->registration_data( $this->token( 'sf_radio_role' ), $this->token( 'sf_radio_mail' ) . '@example.test' );
        $data['desired_role'] = array( 'type' => 'var', 'value' => $role );
        $this->assertSame(
            $role,
            $this->invoke_private(
                'get_safe_public_registration_role',
                array( array( 'register_user_role' => '{desired_role}' ), $data, $form_id )
            )
        );
        $radio_atts = $this->begin_account_action( $this->register_settings('{desired_role}'), $data, $form_id );
        $radio_user = get_user_by( 'login', $data['user_login']['value'] );
        $this->assertInstanceOf( 'WP_User', $radio_user );
        $this->created_users[] = $radio_user->ID;
        $this->assertSame( array($role), array_values($radio_user->roles) );
        $this->consume_account_action( $radio_atts );
        $this->assertSame(
            'author',
            $this->invoke_private(
                'get_safe_public_registration_role',
                array( array( 'register_user_role' => 'author' ) )
            )
        );
        $login = $this->token( 'sf_author_role' );
        $email = $login . '@example.test';
        $atts = $this->begin_account_action( $this->register_settings('author'), $this->registration_data($login, $email) );
        $user = get_user_by( 'login', $login );
        $this->assertInstanceOf( 'WP_User', $user );
        $this->created_users[] = $user->ID;
        $this->assertSame( array('author'), array_values($user->roles) );
        $this->consume_account_action( $atts );
        $this->assertFalse(
            $this->invoke_private(
                'get_safe_public_registration_role',
                array( array( 'register_user_role' => 'editor' ) )
            )
        );
    }

    public function test_public_registration_preserves_the_legacy_role_field_override_and_safe_fallback() {
        $role = 'sf_role_field_' . substr(md5($this->client_key), 0, 12);
        $this->add_test_role(
            $role,
            array(
                'read' => true,
                'level_0' => true,
            )
        );
        $form_id = self::factory()->post->create( array(
            'post_type' => 'super_form',
            'post_status' => 'publish',
        ) );
        update_post_meta(
            $form_id,
            '_super_elements',
            array(
                array(
                    'tag' => 'dropdown',
                    'data' => array(
                        'name' => 'role',
                        'retrieve_method' => 'custom',
                        'dropdown_items' => array(
                            array( 'checked' => false, 'label' => 'Member', 'value' => $role ),
                            array( 'checked' => false, 'label' => 'Subscriber', 'value' => 'subscriber' ),
                        ),
                    ),
                ),
            )
        );
        $data = $this->registration_data( $this->token( 'sf_role_field' ), $this->token( 'sf_role_field_mail' ) . '@example.test' );
        $data['role'] = array( 'type' => 'var', 'value' => $role );
        $this->assertSame(
            $role,
            $this->invoke_private(
                'get_safe_public_registration_role',
                array( array( 'register_user_role' => 'subscriber' ), $data, $form_id )
            )
        );
        $atts = $this->begin_account_action( $this->register_settings('subscriber'), $data, $form_id );
        $user = get_user_by( 'login', $data['user_login']['value'] );
        $this->assertInstanceOf( 'WP_User', $user );
        $this->created_users[] = $user->ID;
        $this->assertSame( array($role), array_values($user->roles) );
        $this->consume_account_action( $atts );
        $this->assert_bridge_cleared();

        $fallback = $this->registration_data( $this->token( 'sf_role_fallback' ), $this->token( 'sf_role_fallback_mail' ) . '@example.test' );
        $fallback['role'] = array( 'type' => 'var', 'value' => 'administrator' );
        $this->assertSame(
            'subscriber',
            $this->invoke_private(
                'get_safe_public_registration_role',
                array( array( 'register_user_role' => 'subscriber' ), $fallback, $form_id )
            )
        );
        $fallback_atts = $this->begin_account_action( $this->register_settings('subscriber'), $fallback, $form_id );
        $fallback_user = get_user_by( 'login', $fallback['user_login']['value'] );
        $this->assertInstanceOf( 'WP_User', $fallback_user );
        $this->created_users[] = $fallback_user->ID;
        $this->assertSame( array('subscriber'), array_values($fallback_user->roles) );
        $this->consume_account_action( $fallback_atts );
        $this->assert_bridge_cleared();
    }
    public function test_public_registration_ignores_injected_saved_role_values_when_the_form_has_no_matching_role_field() {
        $previous = get_option( 'default_role' );
        update_option( 'default_role', 'subscriber', false );
        try {
            $form_id = self::factory()->post->create( array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            ) );
            update_post_meta(
                $form_id,
                '_super_elements',
                array(
                    array(
                        'tag' => 'text',
                        'data' => array(
                            'name' => 'plain_text_field',
                        ),
                    ),
                )
            );
            $data = $this->registration_data( $this->token( 'sf_missing_role' ), $this->token( 'sf_missing_role_mail' ) . '@example.test' );
            $data['desired_role'] = array( 'type' => 'var', 'value' => 'administrator' );
            $this->assertSame(
                'subscriber',
                $this->invoke_private(
                    'get_safe_public_registration_role',
                    array( array( 'register_user_role' => '{desired_role}' ), $data, $form_id )
                )
            );
            $atts = $this->begin_account_action( $this->register_settings('{desired_role}'), $data, $form_id );
            $user = get_user_by( 'login', $data['user_login']['value'] );
            $this->assertInstanceOf( 'WP_User', $user );
            $this->created_users[] = $user->ID;
            $this->assertSame( array('subscriber'), array_values($user->roles) );
            $this->assertFalse( user_can($user, 'manage_options') );
            $this->consume_account_action( $atts );
        } finally {
            update_option( 'default_role', $previous, false );
        }
    }

    public function test_public_registration_invalid_saved_role_configurations_fail_before_any_user_is_created() {
        $role = 'sf_saved_choice_' . substr(md5($this->client_key), 0, 12);
        $this->add_test_role(
            $role,
            array(
                'read' => true,
                'level_0' => true,
            )
        );
        $valid_dropdown = array(
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'desired_role',
                    'retrieve_method' => 'custom',
                    'dropdown_items' => array(
                        array( 'checked' => false, 'label' => 'Member', 'value' => $role ),
                        array( 'checked' => false, 'label' => 'Subscriber', 'value' => 'subscriber' ),
                    ),
                ),
            ),
        );
        $cases = array(
            'duplicate' => array(
                'elements' => array( $valid_dropdown[0], $valid_dropdown[0] ),
                'selected' => $role,
            ),
            'malformed' => array(
                'elements' => array(
                    array(
                        'tag' => 'dropdown',
                        'data' => array(
                            'name' => 'desired_role',
                            'retrieve_method' => 'custom',
                            'dropdown_items' => 'not-an-array',
                        ),
                    ),
                ),
                'selected' => $role,
            ),
        );
        foreach( $cases as $label => $case ) {
            $form_id = self::factory()->post->create( array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            ) );
            update_post_meta( $form_id, '_super_elements', $case['elements'] );
            $login = $this->token( 'sf_invalid_role_' . $label );
            $email = $login . '@example.test';
            $data = $this->registration_data( $login, $email );
            $data['desired_role'] = array( 'type' => 'var', 'value' => $case['selected'] );
            $atts = $this->request_atts( $this->register_settings('{desired_role}'), $data, $form_id );
            $result = $this->run_dying_handler( static function() use ( $atts ) {
                SUPER_Register_Login::before_sending_email( $atts );
            } );
            $this->assertSame( 0, $result['status'], $result['output'] );
            $this->assertFalse( get_user_by( 'login', $login ), 'Unexpected user created for ' . $label );
            $this->assertFalse( get_user_by( 'email', $email ), 'Unexpected user email reserved for ' . $label );
            $this->assert_bridge_cleared();
        }
    }

    public function administrative_capability_provider() {
        return array(
            array( 'add_users' ),
            array( 'edit_users' ),
            array( 'install_languages' ),
            array( 'install_plugins' ),
            array( 'manage_network' ),
            array( 'manage_options' ),
            array( 'resume_plugins' ),
            array( 'resume_themes' ),
            array( 'switch_themes' ),
            array( 'unfiltered_upload' ),
            array( 'update_core' ),
            array( 'upgrade_network' ),
            array( 'upload_plugins' ),
            array( 'upload_themes' ),
        );
    }

    /**
     * @dataProvider administrative_capability_provider
     */
    public function test_public_registration_rejects_roles_with_wordpress_administration_authority( $capability ) {
        $role = 'sf_admin_cap_' . substr(md5($capability . $this->client_key), 0, 12);
        $this->add_test_role(
            $role,
            array(
                'read' => true,
                'level_0' => true,
                $capability => true,
            )
        );

        $this->assertFalse( $this->invoke_private('get_safe_public_registration_role', array(array('register_user_role'=>$role))) );
    }

    public function test_public_registration_accepts_a_read_only_custom_role() {
        $role = 'sf_benign_' . substr(md5($this->client_key), 0, 12);
        $this->add_test_role(
            $role,
            array(
                'read' => true,
                'level_0' => true,
            )
        );
        $this->assertSame( $role, $this->invoke_private('get_safe_public_registration_role', array(array('register_user_role'=>$role))) );

        $login = $this->token( 'sf_custom_role' );
        $email = $login . '@example.test';
        $atts = $this->begin_account_action( $this->register_settings($role), $this->registration_data($login, $email) );
        $user = get_user_by( 'login', $login );
        $this->assertInstanceOf( 'WP_User', $user );
        $this->created_users[] = $user->ID;
        $this->assertSame( array($role), array_values($user->roles) );
        $this->consume_account_action( $atts );
    }

    public function test_public_registration_rejects_unknown_third_party_privilege_capabilities() {
        $role = 'sf_plugin_admin_' . substr(md5($this->client_key), 0, 12);
        $this->add_test_role(
            $role,
            array(
                'read' => true,
                'level_0' => true,
                'manage_commerce_platform' => true,
            )
        );
        $this->assertFalse(
            $this->invoke_private(
                'get_safe_public_registration_role',
                array(array('register_user_role'=>$role))
            )
        );
    }

    public function test_subscriber_self_update_succeeds_but_cannot_promote_its_role() {
        $user_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $user_id );
        $new_email = $this->token( 'sf_self' ) . '@example.test';
        $new_password = 'Updated-self-password-123!';
        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $user_id ),
            'desired_role' => array( 'name'=>'desired_role', 'type'=>'text', 'value'=>'administrator' ),
            'user_email' => array( 'type'=>'email', 'value'=>$new_email ),
            'user_pass' => array( 'type'=>'password', 'value'=>$new_password ),
            'profile_note' => array( 'type'=>'text', 'value'=>'self-service value' ),
        );
        $settings = $this->update_settings( 'profile_note|sf_profile_note', '{desired_role}' );
        $atts = $this->begin_account_action( $settings, $data );
        $this->consume_account_action( $atts );

        $user = get_userdata( $user_id );
        $this->assertSame( $new_email, $user->user_email );
        $this->assertTrue( wp_check_password($new_password, $user->user_pass, $user_id) );
        $this->assertSame( 'self-service value', get_user_meta($user_id, 'sf_profile_note', true) );
        $this->assertSame( array('subscriber'), array_values($user->roles) );
        $this->assertFalse( user_can($user, 'manage_options') );
        $this->assert_bridge_cleared();
    }

    public function test_unauthorized_other_user_password_email_and_meta_update_leaves_all_state_unchanged() {
        $actor_id = $this->create_user( 'subscriber' );
        $target_id = $this->create_user( 'administrator' );
        update_user_meta( $target_id, 'sf_profile_note', 'unchanged' );
        $before = $this->user_state( $target_id, array('sf_profile_note') );
        wp_set_current_user( $actor_id );

        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $target_id ),
            'user_email' => array( 'type'=>'email', 'value'=>$this->token('sf_unauthorized') . '@example.test' ),
            'user_pass' => array( 'type'=>'password', 'value'=>'Unauthorized-password-123!' ),
            'profile_note' => array( 'type'=>'text', 'value'=>'unauthorized meta' ),
        );
        $settings = $this->update_settings( 'profile_note|sf_profile_note' );
        $atts = $this->request_atts( $settings, $data, 802 );
        $post = $atts['post'];
        $mapping = $this->invoke_private( 'validate_custom_meta_mapping', array('profile_note|sf_profile_note') );
        $context = $this->invoke_private(
            'build_user_action_context',
            array( $post, $actor_id, $target_id, 'update', $mapping )
        );
        $this->assertTrue( $this->invoke_private('set_deferred_user_action', array($context)) );
        $this->assertSame( $target_id, absint(SUPER_Common::getClientData('update_user_meta')) );
        $this->assertFalse( $this->invoke_private('consume_deferred_user_action', array($post)) );

        $this->consume_account_action( $atts );
        $this->assertSame( $before, $this->user_state($target_id, array('sf_profile_note')) );
        $this->assert_bridge_cleared();
    }

    public function test_authorized_target_update_works_but_role_change_separately_requires_promote_user() {
        $actor_id = $this->create_user( 'administrator' );
        $target_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $actor_id );
        $seen_promote_targets = array();
        $deny_promote = function( $allcaps, $caps, $args ) use ( &$seen_promote_targets ) {
            if( isset($args[0]) && ($args[0]==='promote_user') ) {
                $seen_promote_targets[] = isset($args[2]) ? absint($args[2]) : 0;
                $allcaps['promote_users'] = false;
            }
            return $allcaps;
        };
        add_filter( 'user_has_cap', $deny_promote, 10, 3 );
        $this->added_filters[] = array( 'user_has_cap', $deny_promote, 10 );

        $new_email = $this->token( 'sf_managed' ) . '@example.test';
        $new_password = 'Managed-password-123!';
        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $target_id ),
            'desired_role' => array( 'name'=>'desired_role', 'type'=>'text', 'value'=>'editor' ),
            'user_email' => array( 'type'=>'email', 'value'=>$new_email ),
            'user_pass' => array( 'type'=>'password', 'value'=>$new_password ),
            'profile_note' => array( 'type'=>'text', 'value'=>'authorized edit' ),
        );
        $settings = $this->update_settings( 'profile_note|sf_profile_note', '{desired_role}' );
        $atts = $this->begin_account_action( $settings, $data );
        $this->consume_account_action( $atts );

        $target = get_userdata( $target_id );
        $this->assertSame( $new_email, $target->user_email );
        $this->assertTrue( wp_check_password($new_password, $target->user_pass, $target_id) );
        $this->assertSame( 'authorized edit', get_user_meta($target_id, 'sf_profile_note', true) );
        $this->assertSame( array('subscriber'), array_values($target->roles) );
        $this->assertContains( $target_id, $seen_promote_targets );

        remove_filter( 'user_has_cap', $deny_promote, 10 );
        $this->added_filters = array();
        $role_data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $target_id ),
            'desired_role' => array( 'name'=>'desired_role', 'type'=>'text', 'value'=>'editor' ),
        );
        $role_atts = $this->begin_account_action( $this->update_settings('', '{desired_role}'), $role_data, 803 );
        $this->consume_account_action( $role_atts );
        $this->assertSame( array('editor'), array_values(get_userdata($target_id)->roles) );
        $this->assert_bridge_cleared();
    }

    public function test_unresolved_update_role_tag_is_ignored() {
        $actor_id = $this->create_user( 'administrator' );
        $target_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $actor_id );
        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $target_id ),
        );
        $atts = $this->begin_account_action( $this->update_settings('', '{missing_role}'), $data, 804 );
        $this->consume_account_action( $atts );

        $this->assertSame( array('subscriber'), array_values(get_userdata($target_id)->roles) );
        $this->assert_bridge_cleared();
    }

    public function test_account_hooks_reject_every_protected_user_meta_key_before_any_effect() {
        global $wpdb;
        $user_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $user_id );
        update_user_meta( $user_id, 'sf_benign_meta', 'original' );
        $keys = array_unique(
            array(
                'capabilities',
                'user_level',
                $wpdb->prefix . 'capabilities',
                $wpdb->prefix . 'user_level',
                $wpdb->base_prefix . 'capabilities',
                $wpdb->base_prefix . 'user_level',
                $wpdb->base_prefix . '2_capabilities',
                $wpdb->base_prefix . '987_user_level',
                'session_tokens',
                '_application_passwords',
                'application_passwords',
                'super_account_status',
                'super_account_activation',
                'super_user_login_status',
                'super_user_approve_data',
                'super_last_login',
            )
        );

        foreach( $keys as $meta_key ) {
            $before = $this->user_state( $user_id, array( 'sf_benign_meta', $meta_key ) );
            $data = array(
                'user_id' => array( 'type' => 'text', 'value' => (string) $user_id ),
                'benign' => array( 'type' => 'text', 'value' => 'must-not-persist' ),
                'attack' => array( 'type' => 'text', 'value' => 'must-not-persist' ),
            );
            $atts = $this->request_atts(
                $this->update_settings( "benign|sf_benign_meta\nattack|" . $meta_key ),
                $data,
                805
            );

            $result = $this->run_dying_handler( static function() use ( $atts ) {
                SUPER_Register_Login::before_sending_email( $atts );
            } );

            $this->assertSame( 0, $result['status'], $result['output'] );
            $this->assertStringContainsString( 'protected key', wp_strip_all_tags( $result['output'] ) );
            $this->assertSame(
                $before,
                $this->user_state( $user_id, array( 'sf_benign_meta', $meta_key ) ),
                'Protected account state changed for ' . $meta_key
            );
            $this->assert_bridge_cleared();
        }

        $safe_atts = $this->begin_account_action(
            $this->update_settings( 'benign|sf_benign_meta' ),
            array(
                'user_id' => array( 'type' => 'text', 'value' => (string) $user_id ),
                'benign' => array( 'type' => 'text', 'value' => 'safe persisted metadata' ),
            ),
            806
        );
        $this->consume_account_action( $safe_atts );
        $this->assertSame( 'safe persisted metadata', get_user_meta( $user_id, 'sf_benign_meta', true ) );
        $this->assert_bridge_cleared();
    }

    public function test_malformed_custom_meta_mapping_is_rejected_before_mutation() {
        $user_id = $this->create_user( 'subscriber' );
        update_user_meta( $user_id, 'sf_benign_meta', 'original' );
        $before = $this->user_state( $user_id, array('sf_benign_meta') );
        $malformed = array(
            'missing_separator',
            '|sf_benign_meta',
            'profile_field|',
            'profile_field|sf_benign_meta|extra',
            "profile_field|sf_benign_meta\nmalformed",
        );

        foreach( $malformed as $mapping ) {
            $this->assertFalse( $this->invoke_private('validate_custom_meta_mapping', array($mapping)) );
            $this->assertSame( $before, $this->user_state($user_id, array('sf_benign_meta')) );
            $this->assert_bridge_cleared();
        }
    }

    public function test_benign_custom_meta_mapping_updates_the_authorized_target() {
        $user_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $user_id );
        $settings = $this->update_settings( "profile_field | sf_application_preference\n" );
        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $user_id ),
            'profile_field' => array( 'type'=>'text', 'value'=>'compact-dashboard' ),
        );
        $atts = $this->begin_account_action( $settings, $data );
        $this->consume_account_action( $atts );

        $this->assertSame( 'compact-dashboard', get_user_meta($user_id, 'sf_application_preference', true) );
        $this->assert_bridge_cleared();
    }

    public function test_array_valued_user_meta_tag_mapping_resolves_and_persists() {
        $user_id = $this->create_user( 'subscriber' );
        $expected = array( 'plan'=>'pro', 'features'=>array('reports', 'exports') );
        $source = '{user_meta_sf_array_meta_source}';
        wp_set_current_user( $user_id );
        update_user_meta( $user_id, 'sf_array_meta_source', $expected );
        $this->assertSame( $expected, $this->invoke_private('resolve_custom_meta_value', array($source, array(), array())) );
        $this->assertSame( $expected, $this->invoke_private('resolve_custom_meta_value', array(serialize($expected), array(), array())) );

        $settings = $this->update_settings( $source . ' | sf_array_meta_destination' );
        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $user_id ),
        );
        $atts = $this->begin_account_action( $settings, $data );
        $this->consume_account_action( $atts );

        $this->assertSame( $expected, get_user_meta($user_id, 'sf_array_meta_destination', true) );
        $this->assert_bridge_cleared();
    }

    public function bridge_context_mismatch_provider() {
        return array(
            array( 'request' ),
            array( 'action' ),
            array( 'actor' ),
            array( 'target' ),
        );
    }

    /**
     * @dataProvider bridge_context_mismatch_provider
     */
    public function test_deferred_bridge_is_request_action_actor_and_target_bound_and_clears_on_error( $mismatch ) {
        $actor_id = $this->create_user( 'administrator' );
        $other_actor_id = $this->create_user( 'administrator' );
        $target_id = $this->create_user( 'subscriber' );
        $other_target_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $actor_id );

        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $target_id ),
            'profile_note' => array( 'type'=>'text', 'value'=>'bound deferred value' ),
        );
        $atts = $this->begin_account_action( $this->update_settings('profile_note|sf_profile_note'), $data, 804 );
        $mismatched = $atts;

        if( $mismatch==='request' ) {
            $mismatched['post']['form_id'] = '805';
        }elseif( $mismatch==='action' ) {
            $mismatched['post']['action'] = 'different_submission_action';
        }elseif( $mismatch==='actor' ) {
            wp_set_current_user( $other_actor_id );
        }elseif( $mismatch==='target' ) {
            $mismatched['data']['user_id']['value'] = (string) $other_target_id;
            $mismatched['post']['data'] = wp_json_encode( $mismatched['data'] );
        }

        $result = $this->run_dying_handler(function() use ( $mismatched ) {
            $this->consume_account_action( $mismatched );
        });
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertTrue( is_array($decoded), $result['output'] );
        $this->assertTrue( $decoded['error'] );
        $this->assertStringContainsString( 'Unable to authorize the account action', $decoded['msg'] );
        $this->invoke_private( 'clear_user_meta_bridge' );
        $this->assertSame( '', get_user_meta($target_id, 'sf_profile_note', true), 'Mismatched ' . $mismatch . ' context mutated the original target.' );
        $this->assertSame( '', get_user_meta($other_target_id, 'sf_profile_note', true), 'Mismatched ' . $mismatch . ' context mutated another target.' );
        $this->assert_bridge_cleared();

        wp_set_current_user( $actor_id );
        $this->consume_account_action( $atts );
        $this->assertSame( '', get_user_meta($target_id, 'sf_profile_note', true), 'Consumed mismatched bridge remained reusable.' );
        $this->assert_bridge_cleared();
    }

    public function test_persisted_client_bridge_values_never_authorize_a_later_submission() {
        $actor_id = $this->create_user( 'administrator' );
        $target_id = $this->create_user( 'subscriber' );
        $before = $this->user_state( $target_id, array('sf_profile_note') );
        wp_set_current_user( $actor_id );
        SUPER_Common::setClientData( array('name'=>'update_user_meta', 'value'=>$target_id) );

        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $target_id ),
            'user_email' => array( 'type'=>'email', 'value'=>$this->token('sf_stale') . '@example.test' ),
            'profile_note' => array( 'type'=>'text', 'value'=>'stale authority' ),
        );
        $atts = $this->request_atts( $this->update_settings('profile_note|sf_profile_note'), $data, 806 );
        $this->consume_account_action( $atts );

        $this->assertSame( $before, $this->user_state($target_id, array('sf_profile_note')) );
        $this->assert_bridge_cleared();
    }

    public function test_no_op_path_unconditionally_clears_a_pending_bridge() {
        $actor_id = $this->create_user( 'administrator' );
        $target_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $actor_id );
        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $target_id ),
            'profile_note' => array( 'type'=>'text', 'value'=>'must not survive no-op' ),
        );
        $pending = $this->begin_account_action( $this->update_settings('profile_note|sf_profile_note'), $data, 807 );

        $no_op = $this->request_atts( array('register_login_action'=>'none'), array(), 808 );
        $this->set_request_globals( $no_op['post'] );
        SUPER_Register_Login::before_sending_email( $no_op );
        $this->assert_bridge_cleared();

        $this->consume_account_action( $pending );
        $this->assertSame( '', get_user_meta($target_id, 'sf_profile_note', true) );
        $this->assert_bridge_cleared();
    }

    public function test_successful_bridge_is_single_use_and_cleared() {
        $user_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $user_id );
        $data = array(
            'user_id' => array( 'type'=>'text', 'value'=>(string) $user_id ),
            'profile_note' => array( 'type'=>'text', 'value'=>'single-use value' ),
        );
        $atts = $this->begin_account_action( $this->update_settings('profile_note|sf_profile_note'), $data, 809 );
        $this->consume_account_action( $atts );
        $this->assertSame( 'single-use value', get_user_meta($user_id, 'sf_profile_note', true) );
        $this->assert_bridge_cleared();

        update_user_meta( $user_id, 'sf_profile_note', 'after first consume' );
        $this->consume_account_action( $atts );
        $this->assertSame( 'after first consume', get_user_meta($user_id, 'sf_profile_note', true) );
        $this->assert_bridge_cleared();
    }

    public function test_update_to_register_fallback_uses_the_register_action_and_clears_its_bridge() {
        wp_set_current_user( 0 );
        $login = $this->token( 'sf_fallback' );
        $email = $login . '@example.test';
        $data = $this->registration_data( $login, $email );
        $data['profile_note'] = array( 'type'=>'text', 'value'=>'registered by fallback' );
        $settings = $this->update_settings();
        $settings['register_login_register_not_logged_in'] = 'true';
        $settings['register_user_role'] = 'subscriber';
        $settings['register_login_user_meta'] = 'profile_note|sf_profile_note';
        $atts = $this->begin_account_action( $settings, $data, 810 );

        $user = get_user_by( 'login', $login );
        $this->assertInstanceOf( 'WP_User', $user );
        $this->created_users[] = $user->ID;
        $this->assertSame( array('subscriber'), array_values($user->roles) );
        $this->consume_account_action( $atts );
        $this->assertSame( 'registered by fallback', get_user_meta($user->ID, 'sf_profile_note', true) );
        $this->assert_bridge_cleared();

        $atts['data']['profile_note']['value'] = 'stale fallback';
        $atts['post']['data'] = wp_json_encode( $atts['data'] );
        $this->consume_account_action( $atts );
        $this->assertSame( 'registered by fallback', get_user_meta($user->ID, 'sf_profile_note', true) );
        $this->assert_bridge_cleared();
    }

    private function build_proof_backed_custom_record( $form_id, $field, $bytes='authorized profile upload', $basename='profile.txt' ) {
        $this->assertTrue( class_exists('SUPER_Ajax') );
        $root = trailingslashit(ABSPATH) . trim(SUPER_FORMS_UPLOAD_DIR, '/');
        $slot = '1735689' . str_pad((string) (++$this->sequence), 6, '0', STR_PAD_LEFT);
        $directory = trailingslashit($root) . $slot;
        $file = trailingslashit($directory) . $basename;
        $this->assertTrue( wp_mkdir_p($directory) );
        $this->assertNotFalse( file_put_contents($file, $bytes) );
        $subdir = '/' . trim(SUPER_FORMS_UPLOAD_DIR, '/') . '/' . $slot . '/' . $basename;
        $candidates = SUPER_Forms::resolve_stored_owned_upload_candidates( wp_normalize_path($file), $subdir );
        $this->assertCount( 1, $candidates );
        $candidate = $candidates[0];
        $url = trailingslashit( get_option('siteurl') ) . 'sfgtfi/' . ltrim( trailingslashit($candidate['route_prefix']) . $candidate['relative_path'], '/' );
        $owned = SUPER_Ajax::build_owned_upload( $form_id, $field, $candidate['file'], 'text/plain', $url, 0, $candidate['root'], filesize($candidate['file']), $subdir );
        $this->assertIsArray( $owned );
        $proof = SUPER_Ajax::owned_custom_upload_proof( $owned );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $proof );
        $record = array(
            '_super_file_authority' => 'owned',
            'name' => $field,
            'value' => $basename,
            'type' => $owned['mime'],
            'url' => $owned['url'],
            'size' => $owned['size'],
            'path' => $owned['custom_path'],
            'subdir' => $owned['legacy_subdir'],
            '_super_file_proof' => $proof,
        );
        return array( 'record'=>$record, 'file'=>$file, 'directory'=>$directory, 'candidate'=>$candidate );
    }

    public function test_custom_meta_file_mapping_uses_account_hooks_and_requires_server_authority() {
        $user_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $user_id );
        $built = $this->build_proof_backed_custom_record( 812, 'upload_field' );
        $record = $built['record'];
        $file = $built['file'];
        $directory = $built['directory'];

        try {
            $settings = $this->update_settings( 'upload_field|sf_upload_meta' );
            $data = array(
                'user_id' => array( 'type' => 'text', 'value' => (string) $user_id ),
                'upload_field' => array( 'type' => 'files', 'files' => array( $record ) ),
            );
            $atts = $this->begin_account_action( $settings, $data, 812 );
            $this->consume_account_action( $atts );
            $this->assertSame( $built['candidate']['file'], get_user_meta($user_id, 'sf_upload_meta', true) );
            $this->assert_bridge_cleared();

            foreach( array(
                'missing authority' => static function( $candidate ) {
                    unset($candidate['_super_file_authority']);
                    return $candidate;
                },
                'wrong source field' => static function( $candidate ) {
                    $candidate['name'] = 'another_field';
                    return $candidate;
                },
                'client token' => static function( $candidate ) {
                    $candidate['upload_token'] = 'client-selector';
                    return $candidate;
                },
                'tampered proof' => static function( $candidate ) {
                    $candidate['_super_file_proof'] = str_repeat('0', 64);
                    return $candidate;
                },
                'forged url' => static function( $candidate ) {
                    $candidate['url'] = 'https://evil.test/x.txt';
                    return $candidate;
                },
            ) as $label => $forge ) {
                update_user_meta( $user_id, 'sf_upload_meta', 'unchanged-' . $label );
                $forged_data = $data;
                $forged_data['upload_field']['files'] = array( $forge($record) );
                $forged_atts = $this->begin_account_action( $settings, $forged_data, 813 );
                $result = $this->run_dying_handler( static function() use ( $forged_atts ) {
                    SUPER_Register_Login::before_email_success_msg( $forged_atts );
                } );

                $this->assertSame( 0, $result['status'], $result['output'] );
                $this->assertStringContainsString( 'Invalid file upload', wp_strip_all_tags($result['output']) );
                $this->assertSame(
                    'unchanged-' . $label,
                    get_user_meta($user_id, 'sf_upload_meta', true),
                    'Forged file metadata persisted for ' . $label
                );
                $this->invoke_private( 'clear_user_meta_bridge' );
                $this->assert_bridge_cleared();
            }
        } finally {
            if( is_file($file) ) {
                unlink($file);
            }
            if( is_dir($directory) ) {
                rmdir($directory);
            }
        }
    }

    public function test_custom_meta_file_mapping_survives_a_later_upload_root_change() {
        $user_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $user_id );
        $built = $this->build_proof_backed_custom_record( 814, 'upload_field' );
        $record = $built['record'];

        try {
            $data = array( 'upload_field' => array( 'type' => 'files', 'files' => array( $record ) ) );
            // Simulate the admin changing the form's upload-root setting AFTER the
            // file was stored. Resolution derives the root from the stored subdir,
            // not the current setting, so a valid account update still succeeds.
            $changed_settings = array( 'file_upload_dir' => 'wp-content/uploads/sf-relocated-' . $this->sequence );
            $value = $this->invoke_private(
                'resolve_custom_meta_value',
                array( 'upload_field', $data, $changed_settings, 814 )
            );
            $this->assertSame( $built['candidate']['file'], $value );

            // A tampered proof is still rejected regardless of the root change.
            $tampered = $record;
            $tampered['_super_file_proof'] = str_repeat('0', 64);
            $tampered_data = array( 'upload_field' => array( 'type' => 'files', 'files' => array( $tampered ) ) );
            $this->assertWPError( $this->invoke_private(
                'resolve_custom_meta_value',
                array( 'upload_field', $tampered_data, $changed_settings, 814 )
            ) );
        } finally {
            if( is_file($built['file']) ) {
                unlink($built['file']);
            }
            if( is_dir($built['directory']) ) {
                rmdir($built['directory']);
            }
        }
    }

    public function test_anonymous_registration_cannot_delete_an_existing_pending_account() {
        $login = $this->token( 'sf_pending_collision' );
        $email = $login . '@example.test';
        $user_id = $this->create_user( 'subscriber', array(
            'user_login' => $login,
            'user_email' => $email,
        ) );
        update_user_meta( $user_id, 'super_user_login_status', 'pending' );
        $before = $this->user_state( $user_id, array('super_user_login_status') );
        wp_set_current_user( 0 );
        $atts = $this->request_atts(
            $this->register_settings(),
            $this->registration_data($login, $email),
            811
        );

        $result = $this->run_dying_handler( static function() use ( $atts ) {
            SUPER_Register_Login::before_sending_email( $atts );
        } );

        $this->assertStringContainsString( 'already exists', $result['output'] );
        $this->assertSame( $before, $this->user_state($user_id, array('super_user_login_status')) );
        $user = get_user_by( 'login', $login );
        $this->assertInstanceOf( 'WP_User', $user );
        $this->assertSame( $user_id, $user->ID );
    }
    public function test_same_session_pending_registration_retry_resends_verification_without_replacing_the_account() {
        $login = $this->token( 'sf_pending_retry' );
        $email = $login . '@example.test';
        $user_id = $this->create_user( 'subscriber', array(
            'user_login' => $login,
            'user_email' => $email,
        ) );
        update_user_meta( $user_id, 'super_user_login_status', 'pending' );
        update_user_meta( $user_id, 'super_account_activation', 'ABCD1234' );
        $settings = $this->register_settings();
        $settings['register_login_activation'] = 'verify';
        $settings['register_activation_subject'] = 'Verify';
        $settings['register_activation_email'] = 'Code {register_activation_code}';
        // A real handler always receives settings that already carry every stored
        // default, because SUPER_Common::get_form_settings() merges
        // SUPER_Settings::get_defaults() under the saved form settings
        // (class-common.php:1464-1477). The verification mail reads several of
        // those keys unguarded (register_login_url at
        // super-forms-register-login.php:2766, the admin e-mail headers at
        // 2701-2711, email_template inside SUPER_Common::email) - in LTS and in
        // beta alike - so build the fixture the same way instead of hand-listing
        // keys.
        $settings['register_login_url'] = 'https://example.test/login/';
        $settings = array_merge(
            SUPER_Settings::get_defaults( SUPER_Common::get_global_settings() ),
            $settings
        );
        $this->assertTrue(
            $this->invoke_private( 'issue_pending_registration_recovery', array( $user_id, 812, $login, $email ) )
        );
        $before = $this->user_state( $user_id, array( 'super_user_login_status', 'super_account_activation' ) );
        $atts = $this->request_atts( $settings, $this->registration_data($login, $email), 812 );

        $result = $this->run_dying_handler( static function() use ( $atts ) {
            SUPER_Register_Login::before_sending_email( $atts );
        } );

        $this->assertStringContainsString( 'verification code', strtolower( $result['output'] ) );
        $this->assertSame( $before['meta']['super_user_login_status'], get_user_meta( $user_id, 'super_user_login_status', true ) );
        $this->assertSame( $before['meta']['super_account_activation'], get_user_meta( $user_id, 'super_account_activation', true ) );
        $user = get_user_by( 'login', $login );
        $this->assertInstanceOf( 'WP_User', $user );
        $this->assertSame( $user_id, $user->ID );
    }
    public function test_resend_activation_refuses_a_pending_unverified_account_from_a_new_session_without_mutation() {
        $login = $this->token( 'sf_pending_new_session' );
        $email = $login . '@example.test';
        $user_id = $this->create_user( 'subscriber', array(
            'user_login' => $login,
            'user_email' => $email,
        ) );
        update_user_meta( $user_id, 'super_user_login_status', 'pending' );
        update_user_meta( $user_id, 'super_account_status', 0 );
        update_user_meta( $user_id, 'super_account_activation', 'ABCD1234' );
        $settings = $this->register_settings();
        $settings['register_login_activation'] = 'verify';
        $settings['register_activation_subject'] = 'Verify';
        $settings['register_activation_email'] = 'Code {register_activation_code}';
        $form_id = self::factory()->post->create(
            array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            )
        );
        update_post_meta( $form_id, '_super_form_settings', $settings );
        $this->assertTrue(
            $this->invoke_private( 'issue_pending_registration_recovery', array( $user_id, $form_id, $login, $email ) )
        );
        unset( $_COOKIE['_sfs_id'] );
        $before = $this->user_state( $user_id, array( 'super_user_login_status', 'super_account_status', 'super_account_activation' ) );
        $_POST = array(
            'action' => 'super_resend_activation',
            'data' => array(
                'username' => $login,
                'email' => $email,
                'form' => (string) $form_id,
            ),
            'nonce' => wp_create_nonce( 'super_resend_activation' ),
        );
        $_REQUEST = $_POST;

        $result = $this->run_dying_handler( static function() {
            SUPER_Register_Login::resend_activation();
        } );
        $this->track_current_client_session();
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded );
        $this->assertFalse( $decoded['error'] );
        $this->assertStringContainsString( 'If the account can receive verification messages', $decoded['msg'] );
        $this->assertStringNotContainsString( $login, $decoded['msg'] );
        $this->assertStringNotContainsString( $email, $decoded['msg'] );
        $this->assertSame( $before, $this->user_state( $user_id, array( 'super_user_login_status', 'super_account_status', 'super_account_activation' ) ) );

        $second = $this->run_dying_handler( static function() {
            SUPER_Register_Login::resend_activation();
        } );
        $this->track_current_client_session();
        $this->assertSame( 0, $second['status'], $second['output'] );
        $second_decoded = json_decode( $second['output'], true );
        $this->assertIsArray( $second_decoded );
        $this->assertFalse( $second_decoded['error'] );
        $this->assertStringContainsString( 'If the account can receive verification messages', $second_decoded['msg'] );
        $this->assertStringNotContainsString( $login, $second_decoded['msg'] );
        $this->assertStringNotContainsString( $email, $second_decoded['msg'] );
        $this->assertSame( $before, $this->user_state( $user_id, array( 'super_user_login_status', 'super_account_status', 'super_account_activation' ) ) );
        wp_delete_post( $form_id, true );
    }
    public function test_resend_activation_refuses_default_unverified_registration_accounts_from_a_new_session_without_mutation() {
        $login = $this->token( 'sf_active_default_session' );
        $email = $login . '@example.test';
        $user_id = $this->create_user( 'subscriber', array(
            'user_login' => $login,
            'user_email' => $email,
        ) );
        update_user_meta( $user_id, 'super_user_login_status', 'active' );
        update_user_meta( $user_id, 'super_account_status', 0 );
        update_user_meta( $user_id, 'super_account_activation', 'ABCD5678' );
        $form_id = self::factory()->post->create(
            array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            )
        );
        update_post_meta( $form_id, '_super_form_settings', array(
            'register_login_action' => 'register',
            'register_activation_subject' => 'Verify',
            'register_activation_email' => 'Code {register_activation_code}',
        ) );
        $this->assertIsArray( $this->invoke_private( 'resend_activation_form_settings', array( $form_id ) ) );
        $this->assertTrue(
            $this->invoke_private( 'issue_pending_registration_recovery', array( $user_id, $form_id, $login, $email ) )
        );
        unset( $_COOKIE['_sfs_id'] );
        $before = $this->user_state( $user_id, array( 'super_user_login_status', 'super_account_status', 'super_account_activation' ) );
        $_POST = array(
            'action' => 'super_resend_activation',
            'data' => array(
                'username' => $login,
                'email' => $email,
                'form' => (string) $form_id,
            ),
            'nonce' => wp_create_nonce( 'super_resend_activation' ),
        );
        $_REQUEST = $_POST;
        $result = $this->run_dying_handler( static function() {
            SUPER_Register_Login::resend_activation();
        } );
        $this->track_current_client_session();
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded );
        $this->assertFalse( $decoded['error'] );
        $this->assertStringContainsString( 'If the account can receive verification messages', $decoded['msg'] );
        $this->assertStringNotContainsString( $login, $decoded['msg'] );
        $this->assertStringNotContainsString( $email, $decoded['msg'] );
        $this->assertSame( $before, $this->user_state( $user_id, array( 'super_user_login_status', 'super_account_status', 'super_account_activation' ) ) );
        $second = $this->run_dying_handler( static function() {
            SUPER_Register_Login::resend_activation();
        } );
        $this->track_current_client_session();
        $this->assertSame( 0, $second['status'], $second['output'] );
        $second_decoded = json_decode( $second['output'], true );
        $this->assertIsArray( $second_decoded );
        $this->assertFalse( $second_decoded['error'] );
        $this->assertStringContainsString( 'If the account can receive verification messages', $second_decoded['msg'] );
        $this->assertStringNotContainsString( $login, $second_decoded['msg'] );
        $this->assertStringNotContainsString( $email, $second_decoded['msg'] );
        $this->assertSame( $before, $this->user_state( $user_id, array( 'super_user_login_status', 'super_account_status', 'super_account_activation' ) ) );
        wp_delete_post( $form_id, true );
    }
    public function test_resend_activation_accepts_only_published_registration_forms_with_verification_enabled() {
        $verify_settings = $this->register_settings();
        $verify_settings['register_login_activation'] = 'verify';
        $verify_login_settings = $this->register_settings();
        $verify_login_settings['register_login_activation'] = 'verify_login';
        $default_verify_settings = $this->register_settings();
        unset( $default_verify_settings['register_login_activation'] );
        $login_settings = $this->register_settings();
        $login_settings['register_login_action'] = 'login';
        $auto_settings = $this->register_settings();
        $auto_settings['register_login_activation'] = 'auto';
        $ids = array(
            self::factory()->post->create( array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            ) ),
            self::factory()->post->create( array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            ) ),
            self::factory()->post->create( array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            ) ),
            self::factory()->post->create( array(
                'post_type' => 'super_form',
                'post_status' => 'draft',
            ) ),
            self::factory()->post->create( array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            ) ),
            self::factory()->post->create( array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            ) ),
            self::factory()->post->create( array(
                'post_type' => 'post',
                'post_status' => 'publish',
            ) ),
        );
        update_post_meta( $ids[0], '_super_form_settings', $verify_settings );
        update_post_meta( $ids[1], '_super_form_settings', $verify_login_settings );
        update_post_meta( $ids[2], '_super_form_settings', $default_verify_settings );
        update_post_meta( $ids[3], '_super_form_settings', $verify_settings );
        update_post_meta( $ids[4], '_super_form_settings', $login_settings );
        update_post_meta( $ids[5], '_super_form_settings', $auto_settings );
        try {
            $this->assertIsArray( $this->invoke_private( 'resend_activation_form_settings', array( $ids[0] ) ) );
            $this->assertIsArray( $this->invoke_private( 'resend_activation_form_settings', array( $ids[1] ) ) );
            $this->assertIsArray( $this->invoke_private( 'resend_activation_form_settings', array( $ids[2] ) ) );
            $this->assertFalse( $this->invoke_private( 'resend_activation_form_settings', array( $ids[3] ) ) );
            $this->assertFalse( $this->invoke_private( 'resend_activation_form_settings', array( $ids[4] ) ) );
            $this->assertFalse( $this->invoke_private( 'resend_activation_form_settings', array( $ids[5] ) ) );
            $this->assertFalse( $this->invoke_private( 'resend_activation_form_settings', array( $ids[6] ) ) );
            $this->assertFalse( $this->invoke_private( 'resend_activation_form_settings', array( 0 ) ) );
        } finally {
            foreach( array_reverse( $ids ) as $id ) {
                wp_delete_post( $id, true );
            }
        }
    }


    public function test_profile_status_save_requires_privileged_exact_target_authority_and_known_status() {
        if( function_exists('set_current_screen') ) {
            set_current_screen( 'profile.php' );
        }
        $handler = SUPER_Register_Login();
        $callback = array( $handler, 'save_customer_meta_fields' );
        $added_personal = false;
        $added_edit = false;
        if( !has_action( 'personal_options_update', $callback ) ) {
            add_action( 'personal_options_update', $callback, 10, 1 );
            $added_personal = true;
        }
        if( !has_action( 'edit_user_profile_update', $callback ) ) {
            add_action( 'edit_user_profile_update', $callback, 10, 1 );
            $added_edit = true;
        }
        try {
            $this->assertSame( 10, has_action('personal_options_update', $callback) );
            $this->assertSame( 10, has_action('edit_user_profile_update', $callback) );

            $self_id = $this->create_user( 'subscriber' );
            update_user_meta( $self_id, 'super_user_login_status', 'pending' );
            $actor_id = $this->create_user( 'subscriber' );
            $target_id = $this->create_user( 'subscriber' );
            update_user_meta( $target_id, 'super_user_login_status', 'pending' );
            $previous_post = $_POST;
            try {
                // A real profile.php submit always carries the core user_id/email
                // pair that WordPress' own personal_options_update callback
                // send_confirmation_on_profile_email() reads (wp-includes/user.php).
                $_POST = array(
                    'super_user_login_status' => 'active',
                    'user_id' => (string) $self_id,
                    'email' => get_userdata( $self_id )->user_email,
                );
                wp_set_current_user( $self_id );
                do_action( 'personal_options_update', $self_id );
                $this->assertSame( 'pending', get_user_meta($self_id, 'super_user_login_status', true) );

                wp_set_current_user( $actor_id );
                do_action( 'edit_user_profile_update', $target_id );
                $this->assertSame( 'pending', get_user_meta($target_id, 'super_user_login_status', true) );

                $admin_id = $this->create_user( 'administrator' );
                wp_set_current_user( $admin_id );
                $_POST['super_user_login_status'] = 'administrator';
                do_action( 'personal_options_update', $target_id );
                $this->assertSame( 'pending', get_user_meta($target_id, 'super_user_login_status', true) );

                $_POST['super_user_login_status'] = 'active';
                do_action( 'edit_user_profile_update', $target_id );
                $this->assertSame( 'active', get_user_meta($target_id, 'super_user_login_status', true) );
            } finally {
                $_POST = $previous_post;
            }
        } finally {
            if( $added_personal ) {
                remove_action( 'personal_options_update', $callback, 10 );
            }
            if( $added_edit ) {
                remove_action( 'edit_user_profile_update', $callback, 10 );
            }
            if( function_exists('set_current_screen') ) {
                set_current_screen( 'front' );
            }
        }
    }

}
