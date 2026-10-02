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

    public function set_up() {
        parent::set_up();

        $this->original_cookie_exists = array_key_exists( '_sfs_id', $_COOKIE );
        $this->original_cookie_value = $this->original_cookie_exists ? $_COOKIE['_sfs_id'] : null;
        $this->original_post = $_POST;
        $this->original_request = $_REQUEST;
        // Session ids must be 32-128 alphanumeric characters (startClientSession rejects others).
        $this->client_key = 'sfsecurity' . str_replace( '-', '', wp_generate_uuid4() );
        $_COOKIE['_sfs_id'] = $this->client_key;
        update_option(
            '_sfsdata_' . $this->client_key,
            array(
                'expires' => time() + HOUR_IN_SECONDS,
                'exp_var' => time() + HOUR_IN_SECONDS,
                // A browser session normally holds other client data (e.g. sf_nonce). Without
                // it, clearing the bridge empties the session, setClientData deletes it, and
                // the next write must issue a new cookie, which a CLI test run cannot send.
                'sf_test_session_anchor' => array(
                    'expires' => time() + HOUR_IN_SECONDS,
                    'exp_var' => time() + HOUR_IN_SECONDS,
                    'value' => 'anchor',
                ),
            ),
            false
        );
        $_POST = array();
        $_REQUEST = array();
        wp_set_current_user( 0 );
        $this->invoke_private( 'clear_user_meta_bridge' );
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
            'register_user_role' => 'administrator',
            'register_update_user_role' => $role,
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

    private function run_dying_handler( $callback ) {
        if( !function_exists('pcntl_fork') || !function_exists('pcntl_waitpid') || !function_exists('pcntl_exec') ) {
            $this->markTestSkipped( 'The fail-closed account regression requires pcntl fork, wait, and exec support.' );
        }
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
                $status = $returned ? 97 : 0;
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
        $this->assertFalse( SUPER_Common::getClientData('super_forms_registered_user_id') );
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

    public function administrative_capability_provider() {
        return array(
            array( 'edit_users' ),
            array( 'install_plugins' ),
            array( 'switch_themes' ),
            array( 'update_core' ),
            array( 'manage_network' ),
            array( 'manage_options' ),
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
        $this->assertSame( $target_id, absint(SUPER_Common::getClientData('super_forms_registered_user_id')) );
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
                'super_pending_registration_recovery',
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
        SUPER_Common::setClientData( array('name'=>'register_login_user_action', 'value'=>'update') );
        SUPER_Common::setClientData( array('name'=>'super_forms_registered_user_id', 'value'=>$target_id) );

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

    public function test_custom_meta_file_mapping_uses_account_hooks_and_requires_server_authority() {
        $user_id = $this->create_user( 'subscriber' );
        wp_set_current_user( $user_id );
        $root = trailingslashit(ABSPATH) . trim(SUPER_FORMS_UPLOAD_DIR, '/');
        $slot = '1735689' . str_pad((string) (++$this->sequence), 6, '0', STR_PAD_LEFT);
        $directory = trailingslashit($root) . $slot;
        $file = trailingslashit($directory) . 'profile.txt';
        $this->assertTrue( wp_mkdir_p($directory) );
        $this->assertNotFalse( file_put_contents($file, 'authorized profile upload') );

        try {
            $record = array(
                '_super_file_authority' => 'owned',
                'name' => 'upload_field',
                'value' => 'profile.txt',
                'type' => 'text/plain',
                'url' => 'https://example.test/private/profile.txt',
                'path' => wp_normalize_path($file),
                'subdir' => '/' . trim(SUPER_FORMS_UPLOAD_DIR, '/') . '/' . $slot . '/profile.txt',
            );
            // Authority comes from the server-issued proof that binds form, field, path,
            // subdir, mime, url and size: issue it the way the upload pipeline does.
            if( !class_exists('SUPER_Ajax') ) {
                require_once SUPER_PLUGIN_DIR . '/includes/class-ajax.php';
            }
            $candidates = SUPER_Forms::resolve_stored_owned_upload_candidates( $record['path'], $record['subdir'] );
            $this->assertCount( 1, $candidates );
            $owned = SUPER_Ajax::build_owned_upload(
                812, 'upload_field', $candidates[0]['file'], $record['type'], $record['url'], 0,
                $candidates[0]['root'], filesize( $candidates[0]['file'] ), $record['subdir']
            );
            $this->assertIsArray( $owned );
            $record['_super_file_proof'] = SUPER_Ajax::owned_custom_upload_proof( $owned );
            $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $record['_super_file_proof'] );
            $settings = $this->update_settings( 'upload_field|sf_upload_meta' );
            $data = array(
                'user_id' => array( 'type' => 'text', 'value' => (string) $user_id ),
                'upload_field' => array( 'type' => 'files', 'files' => array( $record ) ),
            );
            $atts = $this->begin_account_action( $settings, $data, 812 );
            $this->consume_account_action( $atts );
            $this->assertSame( wp_normalize_path($file), get_user_meta($user_id, 'sf_upload_meta', true) );
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

    public function test_profile_status_save_requires_privileged_exact_target_authority_and_known_status() {
        $handler = SUPER_Register_Login();
        $this->assertFalse( has_action('personal_options_update', array($handler, 'save_customer_meta_fields')) );

        $self_id = $this->create_user( 'subscriber' );
        update_user_meta( $self_id, 'super_user_login_status', 'pending' );
        $actor_id = $this->create_user( 'subscriber' );
        $target_id = $this->create_user( 'subscriber' );
        update_user_meta( $target_id, 'super_user_login_status', 'pending' );
        $previous_post = $_POST;
        try {
            $_POST = array( 'super_user_login_status'=>'active' );
            wp_set_current_user( $self_id );
            $handler->save_customer_meta_fields( $self_id );
            $this->assertSame( 'pending', get_user_meta($self_id, 'super_user_login_status', true) );

            wp_set_current_user( $actor_id );
            $handler->save_customer_meta_fields( $target_id );
            $this->assertSame( 'pending', get_user_meta($target_id, 'super_user_login_status', true) );

            $admin_id = $this->create_user( 'administrator' );
            wp_set_current_user( $admin_id );
            $_POST['super_user_login_status'] = 'administrator';
            $handler->save_customer_meta_fields( $target_id );
            $this->assertSame( 'pending', get_user_meta($target_id, 'super_user_login_status', true) );

            $_POST['super_user_login_status'] = 'active';
            $handler->save_customer_meta_fields( $target_id );
            $this->assertSame( 'active', get_user_meta($target_id, 'super_user_login_status', true) );
        } finally {
            $_POST = $previous_post;
        }
    }

    public function test_legacy_inactive_login_establishes_bound_resend_only_after_valid_credentials() {
        global $wpdb;
        // A served page has session data besides expiry. Keep that anchor across
        // account bridge cleanup; CLI cannot reissue a cookie after headers are sent.
        $session_key = '_sfsdata_' . $_COOKIE['_sfs_id'];
        $session = get_option($session_key);
        $session['legacy_resend_test_anchor'] = array('expires' => time()+HOUR_IN_SECONDS, 'exp_var' => time()+HOUR_IN_SECONDS, 'value' => 'anchor');
        update_option($session_key, $session, false);
        $login = $this->token('legacy_resend');
        $email = $login . '@example.test';
        $password = 'Synthetic-test-password-49!';
        $user_id = self::factory()->user->create(array('user_login' => $login, 'user_email' => $email, 'user_pass' => $password, 'role' => 'subscriber'));
        $this->created_users[] = $user_id;
        update_user_meta($user_id, 'super_account_status', '0');
        update_user_meta($user_id, 'super_account_activation', 'existing-activation-code');
        update_user_meta($user_id, 'super_user_login_status', 'active');
        $form_id = self::factory()->post->create(array('post_type' => 'super_form', 'post_status' => 'publish'));
        $saved_settings = array('register_login_action' => 'login', 'register_login_activation' => 'none',
            'register_login_url' => 'https://example.test/activate', 'register_activation_subject' => 'Verify your account',
            'register_activation_email' => 'Code: {register_activation_code}', 'form_processing_overlay' => 'true',
            'header_reply_enabled' => 'false');
        update_post_meta($form_id, '_super_form_settings', $saved_settings);
        $settings = SUPER_Common::get_form_settings($form_id);
        $data = array('user_login' => array('value' => $login), 'user_pass' => array('value' => 'wrong-password'));
        $atts = $this->request_atts($settings, $data, $form_id);
        $atts['form_id'] = $form_id;
        $atts['sfs_uid'] = $this->token('resend_submission');
        update_option('_sfsi_' . $atts['sfs_uid'], array('form_id' => $form_id), false);
        $mail_key = '_legacy_resend_mail_' . $user_id;
        $capture_mail = static function($return, $mail) use ($mail_key) {
            update_option($mail_key, array('to' => $mail['to'], 'message' => $mail['message']), false);
            return true;
        };
        add_filter('pre_wp_mail', $capture_mail, 10, 2);
        add_filter('send_auth_cookies', '__return_false');
        try {
            $this->set_request_globals($atts['post']);
            $bad = $this->run_dying_handler(static function() use ($atts) { SUPER_Register_Login::before_sending_email($atts); });
            $this->assertSame(0, $bad['status'], $bad['output']);
            $this->assertStringNotContainsString('resend-code', $bad['output']);
            wp_cache_delete($user_id, 'user_meta');
            $this->assertSame('', get_user_meta($user_id, 'super_pending_registration_recovery', true));
            $atts['data']['user_pass']['value'] = $password;
            $atts['post']['data'] = wp_json_encode($atts['data']);
            $this->set_request_globals($atts['post']);
            $good = $this->run_dying_handler(static function() use ($atts) { SUPER_Register_Login::before_sending_email($atts); });
            $this->assertSame(0, $good['status'], $good['output']);
            $this->assertStringContainsString('resend-code', $good['output']);
            wp_cache_delete($user_id, 'user_meta');
            $payload = get_user_meta($user_id, 'super_pending_registration_recovery', true);
            $this->assertIsArray($payload);
            $this->assertSame($form_id, $payload['form_id']);
            $this->assertSame($user_id, $payload['user_id']);
            $this->assertNotSame($password, $payload['token_hash']);
            $request = array('action' => 'super_resend_activation', 'nonce' => wp_create_nonce('super_resend_activation'),
                'data' => array('username' => $login, 'email' => $email, 'form' => $form_id));
            $original_cookie = $_COOKIE['_sfs_id'];
            $_COOKIE['_sfs_id'] = str_repeat('d', 48);
            $this->set_request_globals($request);
            $other_browser = $this->run_dying_handler(array('SUPER_Register_Login', 'resend_activation'));
            $this->assertSame(0, $other_browser['status'], $other_browser['output']);
            $this->assertNull($wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $mail_key)));
            $_COOKIE['_sfs_id'] = $original_cookie;
            $other_form = self::factory()->post->create(array('post_type' => 'super_form', 'post_status' => 'publish'));
            update_post_meta($other_form, '_super_form_settings', $saved_settings);
            $request['data']['form'] = $other_form;
            $this->set_request_globals($request);
            $denied = $this->run_dying_handler(array('SUPER_Register_Login', 'resend_activation'));
            $this->assertSame(0, $denied['status'], $denied['output']);
            $this->assertNull($wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $mail_key)));
            $request['data']['form'] = $form_id;
            $this->set_request_globals($request);
            $sent = $this->run_dying_handler(array('SUPER_Register_Login', 'resend_activation'));
            $this->assertSame(0, $sent['status'], $sent['output']);
            $mail = maybe_unserialize($wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $mail_key)));
            $this->assertIsArray($mail);
            $this->assertSame($email, is_array($mail['to']) ? $mail['to'][0] : $mail['to']);
            $this->assertStringContainsString('existing-activation-code', $mail['message']);
            $this->assertStringNotContainsString($password, $mail['message']);
            $this->assertSame('0', get_user_meta($user_id, 'super_account_status', true));
        } finally {
            remove_filter('pre_wp_mail', $capture_mail, 10);
            remove_filter('send_auth_cookies', '__return_false');
            delete_option($mail_key);
            delete_option('_sfsi_' . $atts['sfs_uid']);
        }
    }

    public function test_resend_activation_accepts_published_verification_registration_and_login_forms() {
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
            $this->assertIsArray( $this->invoke_private( 'resend_activation_form_settings', array( $ids[4] ) ) );
            $this->assertFalse( $this->invoke_private( 'resend_activation_form_settings', array( $ids[5] ) ) );
            $this->assertFalse( $this->invoke_private( 'resend_activation_form_settings', array( $ids[6] ) ) );
            $this->assertFalse( $this->invoke_private( 'resend_activation_form_settings', array( 0 ) ) );
        } finally {
            foreach( array_reverse( $ids ) as $id ) {
                wp_delete_post( $id, true );
            }
        }
    }

}
