<?php
/**
 * Regression coverage for server-owned submission carrier contracts.
 *
 * The test methods 6.3.318 added to tests/test-security-submission-contract.php (numeric field
 * names, date / multi-date / minimum picks / yearless / translated dates, nested repeated codes,
 * unconditional generated codes, time-picker bounds), imported with the 6.3.318 scaffolding and
 * helpers they rely on. The 6.4 line has its own, smaller submission-contract test file with a
 * different fixture set, so these live in a separate class.
 *
 * @package Super_Forms_Tests
 */

class Test_Super_Forms_Submission_Contract_6318_Security extends WP_UnitTestCase {
    private $client_sessions = array();
    private $original_cookie_exists = false;
    private $original_cookie_value = null;
    public static function set_up_before_class() {
        parent::set_up_before_class();
        // DOING_AJAX is never defined in the WP test bootstrap, so super-forms.php
        // is_request('ajax') is false and ajax_includes() never loads SUPER_Ajax.
        if( !class_exists( 'SUPER_Ajax' ) ) {
            require_once dirname( __DIR__ ) . '/src/includes/class-ajax.php';
        }
        if( !class_exists( 'SUPER_Register_Login' ) ) {
            require_once dirname( __DIR__ ) . '/src/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
        if( !class_exists( 'SUPER_Mailchimp' ) ) {
            require_once dirname( __DIR__ ) . '/src/add-ons/super-forms-mailchimp/super-forms-mailchimp.php';
        }
    }
    /**
     * WP_UnitTestCase restores $wp_filter to the snapshot taken before the add-ons
     * were loaded, so the hooks their constructors registered are gone from the
     * second test onwards. Re-attach exactly the ones these regressions rely on.
     */
    private function ensure_addon_hooks() {
        $hooks = array(
            array( 'super_shortcodes_after_form_elements_filter', array( SUPER_Register_Login(), 'add_activation_code_element' ) ),
            array( 'super_submission_carrier_contracts_filter', array( SUPER_Register_Login(), 'submission_carrier_contracts' ) ),
            array( 'super_shortcodes_after_form_elements_filter', array( SUPER_Mailchimp(), 'add_mailchimp_element' ) ),
            array( 'super_submission_carrier_contracts_filter', array( SUPER_Mailchimp(), 'submission_carrier_contracts' ) ),
            array( 'super_before_sending_email_data_filter', array( SUPER_Mailchimp(), 'remove_mailchimp_data' ) ),
        );
        foreach( $hooks as $hook ) {
            if( !has_filter( $hook[0], $hook[1] ) ) {
                add_filter( $hook[0], $hook[1], 10, 2 );
            }
        }
    }
    public function set_up() {
        parent::set_up();
        if( !has_action( 'wp_ajax_nopriv_super_submit_form' ) ) {
            SUPER_Ajax::init();
        }
        $this->ensure_addon_hooks();
        $this->original_cookie_exists = array_key_exists( '_sfs_id', $_COOKIE );
        $this->original_cookie_value = $this->original_cookie_exists ? $_COOKIE['_sfs_id'] : null;
        $this->client_sessions = array();
        $_POST = array();
        $_REQUEST = array();
        wp_set_current_user( 0 );
    }
    public function tear_down() {
        foreach( array_unique( $this->client_sessions ) as $session_id ) {
            delete_option( '_sfsdata_' . $session_id );
        }
        if( $this->original_cookie_exists ) {
            $_COOKIE['_sfs_id'] = $this->original_cookie_value;
        }else{
            unset( $_COOKIE['_sfs_id'] );
        }
        parent::tear_down();
    }
    /**
     * A real first response mints the browser session cookie; under the CLI SAPI
     * setcookie() can never succeed (headers are already sent), so seed exactly the
     * cookie + `_sfsdata_` option a served page would have persisted. The hardened
     * adoption path in SUPER_Common::startClientSession then resolves it normally.
     */
    private function seed_client_session() {
        $session_id = 'sfcontract' . str_replace( '-', '', wp_generate_uuid4() );
        $_COOKIE['_sfs_id'] = $session_id;
        update_option( '_sfsdata_' . $session_id, array(
            'expires' => time() + HOUR_IN_SECONDS,
            'exp_var' => time() + HOUR_IN_SECONDS,
        ), false );
        $this->client_sessions[] = $session_id;
        $this->assertSame( $session_id, SUPER_Common::startClientSession( array( 'force' => true ) ) );
        return $session_id;
    }
    /**
     * Requiredness is deliberately NOT part of the carrier-shape contract
     * (class-ajax.php:4484-4486); submit_form_checks enforces repeater rows through
     * collect_required_fields() + validate_repeater_required_values()
     * (class-ajax.php:7903-7926). Exercise that exact route.
     */
    private function repeater_required_values_valid( $data, $elements, $form_id=41 ) {
        $collect = new ReflectionMethod( 'SUPER_Ajax', 'collect_required_fields' );
        $collect->setAccessible( true );
        $required_fields = $collect->invoke( null, $elements );
        $this->assertNotEmpty( $required_fields );
        $validate = new ReflectionMethod( 'SUPER_Ajax', 'validate_repeater_required_values' );
        $validate->setAccessible( true );
        return $validate->invoke(
            null,
            isset( $data['_super_dynamic_data'] ) ? $data['_super_dynamic_data'] : null,
            $required_fields,
            $elements,
            $form_id
        );
    }
    public function test_time_picker_bounds_are_not_text_length_requirements() {
        $elements = array( array( 'tag' => 'time', 'data' => array(
            'name' => 'from_time', 'minlength' => '09:00', 'maxlength' => '18:00',
        ) ) );
        $contract = array();
        $collect = new ReflectionMethod( 'SUPER_Ajax', 'collect_submission_field_contract' );
        $collect->setAccessible( true );
        $args = array( $elements, &$contract, 0, 41 );
        $collect->invokeArgs( null, $args );
        $this->assertSame( 'skip', $contract['from_time']['length_mode'] );
        $validate = new ReflectionMethod( 'SUPER_Ajax', 'submission_value_matches_validation' );
        $validate->setAccessible( true );
        $this->assertTrue( $validate->invoke( null, '10:00', $contract['from_time'] ) );
        $this->assertTrue( $validate->invoke( null, 'any text', array( 'maxlength' => '0' ) ) );
    }

    public function test_numeric_field_names_create_entries_through_full_submission() {
        foreach( array( '123', '0', '001' ) as $name ) {
            $form_id = $this->create_form(
                array( array( 'tag' => 'text', 'data' => array( 'name' => $name ) ) ),
                array( 'save_contact_entry' => 'yes', 'send' => 'no', 'confirm' => 'no',
                    'form_thanks_title' => '', 'form_thanks_description' => '',
                    'form_show_thanks_msg' => '', 'form_redirect_option' => '' )
            );
            $before_ids = $this->entry_ids_for( $form_id );
            $result = $this->with_super_settings( array( 'csrf_check' => 'false' ), function() use ( $form_id, $name ) {
                $this->set_submission_request( $form_id,
                    array( $name => array( 'name' => $name, 'value' => 'numeric entry', 'type' => 'var' ) ),
                    array( 'action' => 'super_submit_form', 'i18n' => '' )
                );
                return $this->run_dying_callback( static function() { SUPER_Ajax::submit_form(); } );
            } );
            $this->assertSame( 0, $result['status'], $result['output'] );
            $decoded = json_decode( $result['output'], true );
            $this->assertIsArray( $decoded, $result['output'] );
            $this->assertFalse( $decoded['error'], $result['output'] );
            $entry_id = (int)$decoded['response_data']['contact_entry_id'];
            $this->assertGreaterThan( 0, $entry_id );
            $this->assertCount( count($before_ids) + 1, $this->entry_ids_for($form_id) );
            $this->assertSame( 'numeric entry', SUPER_Data_Access::get_entry_data($entry_id)[$name]['value'] );
        }
    }

    public function test_numeric_upload_field_keys_match_the_declared_file_route() {
        $parallel = new ReflectionMethod( 'SUPER_Ajax', 'upload_files_are_parallel' );
        $parallel->setAccessible( true );
        $files = array();
        foreach( array( 'name' => 'test.pdf', 'type' => 'application/pdf', 'tmp_name' => '/scratch/test.pdf', 'error' => 0, 'size' => 128 ) as $part => $value ) {
            $files[$part] = array( 123 => array( $value ) );
        }
        $this->assertTrue( $parallel->invoke(null, $files) );
        $collect = new ReflectionMethod( 'SUPER_Ajax', 'collect_submission_file_routes' );
        $collect->setAccessible( true );
        $routes = array();
        $collect->invokeArgs(null, array(array(array('tag'=>'file', 'data'=>array('name'=>'123'))), &$routes));
        $identity = new ReflectionMethod( 'SUPER_Ajax', 'upload_request_field_identities' );
        $identity->setAccessible( true );
        $result = $identity->invoke(null, $files['name'], $routes);
        $this->assertSame( '123', $result[123]['stored_field_name'] );
        $_POST['file_field_map'] = array(123=>'0123');
        $this->assertFalse( $identity->invoke(null, $files['name'], $routes) );
    }

    public function test_numeric_field_names_survive_json_decoding_and_keep_exact_contracts() {
        foreach( array( '123', '0', '001', '2147483648' ) as $name ) {
            $elements = array( array( 'tag' => 'text', 'data' => array( 'name' => $name, 'validation' => 'none' ) ) );
            $data = array_intersect_key( $this->production_repeater_data(), array_flip( array( 'hidden_form_id', 'hidden_contact_entry_id' ) ) );
            $data[$name] = array( 'name' => $name, 'type' => 'var', 'value' => 'Numeric name value' );
            $data = json_decode( wp_json_encode( (object)$data ), true );
            $this->assertTrue( $this->validate( $data, $elements ), 'Stored numeric name: ' . $name );
            $rebuilt = $this->rebuild_selection_entry_data( $data, $elements );
            $this->assertTrue( is_array($rebuilt), 'Rebuilt numeric name: ' . $name );
            $this->assertSame( 'Numeric name value', $rebuilt[$name]['value'] );
            $data[$name]['name'] = 'different_name';
            $this->assertFalse( $this->validate( $data, $elements ) );
        }
    }

    public function test_numeric_repeater_names_match_both_rows_and_reject_changed_aliases() {
        foreach( array( '123', '0' ) as $name ) {
            $elements = json_decode( str_replace( array('guest_name', 'guest_document'), array($name, '456'), wp_json_encode($this->repeater_elements()) ), true );
            $data = json_decode( str_replace( array('guest_name', 'guest_document'), array($name, '456'), wp_json_encode($this->production_repeater_data()) ), true );
            $this->assertTrue( $this->validate( $data, $elements ), 'Numeric repeater: ' . $name );
            $missing_group = $data;
            unset($missing_group['_super_dynamic_data']);
            $this->assertFalse( $this->validate( $missing_group, $elements ) );
            $data[$name . '_2']['value'] = 'Forged alias';
            $this->assertFalse( $this->validate( $data, $elements ) );
        }
    }

    public function test_numeric_zero_names_retain_requiredness_and_single_field_repeater_rows() {
        $collect = new ReflectionMethod( 'SUPER_Ajax', 'collect_required_fields' );
        $collect->setAccessible( true );
        $required = $collect->invoke(null, array(array('tag'=>'text', 'data'=>array('name'=>'0', 'validation'=>'required'))));
        $this->assertArrayHasKey( 0, $required );
        $this->assertTrue( $required[0]['always_present'] );
        $this->assertFalse( $this->files_match_stored_policy(array(), array(array('tag'=>'file', 'data'=>array('name'=>'0', 'minlength'=>'1')))) );
        $elements = json_decode(str_replace('guest_name', '0', wp_json_encode($this->repeater_elements())), true);
        unset($elements[0]['inner'][1]);
        $data = json_decode(str_replace('guest_name', '0', wp_json_encode($this->production_repeater_data())), true);
        unset($data['guest_document'], $data['guest_document_2']);
        unset($data['_super_dynamic_data'][0][0]['guest_document'], $data['_super_dynamic_data'][0][1]['guest_document_2']);
        $this->assertTrue( $this->validate($data, $elements) );
        $data['_super_dynamic_data'][0][0][1] = $data['_super_dynamic_data'][0][0][0];
        $this->assertFalse( $this->validate($data, $elements) );
    }

    public function test_numeric_file_names_do_not_skip_stored_size_policy() {
        $elements = array( array( 'tag' => 'file', 'data' => array( 'name' => '123', 'filesize' => '1' ) ) );
        $data = json_decode( '{"123":{"type":"files","files":[{"size":2097152}]}}', true );
        $this->assertFalse( $this->files_match_stored_policy( $data, $elements ) );
        $data[123]['files'][0]['size'] = 128;
        $this->assertTrue( $this->files_match_stored_policy( $data, $elements ) );
        $data[999] = $data[123];
        $this->assertFalse( $this->files_match_stored_policy( $data, $elements ) );
    }

    private function validate( $data, $elements, $form_id=41, $entry_id='', $list_id='' ) {
        $method = new ReflectionMethod( 'SUPER_Ajax', 'submission_data_matches_contract' );
        $method->setAccessible( true );
        return $method->invoke( null, $data, $elements, $form_id, $entry_id, $list_id );
    }
    private function rebuild_selection_entry_data( $data, $elements, $form_id=41 ) {
        $method = new ReflectionMethod( 'SUPER_Ajax', 'rebuild_selection_entry_values' );
        $method->setAccessible( true );
        return $method->invoke( null, $data, $elements, $form_id );
    }
    private function files_match_stored_policy( $data, $elements ) {
        $method = new ReflectionMethod( 'SUPER_Ajax', 'submission_files_match_stored_policy' );
        $method->setAccessible( true );
        return $method->invoke( null, $data, $elements );
    }
    private function create_form( $elements, $settings=array() ) {
        $form_id = self::factory()->post->create(
            array(
                'post_type' => 'super_form',
                'post_status' => 'publish',
            )
        );
        update_post_meta( $form_id, '_super_elements', $elements );
        if( !empty($settings) ) {
            update_post_meta( $form_id, '_super_form_settings', $settings );
        }
        return $form_id;
    }
    /**
     * Query the posts table directly: the inventory must count EVERY contact entry
     * stored under the form, including statuses a WP_Query status whitelist would
     * silently drop, so "exactly one new entry" cannot pass by omission.
     */
    private function entry_ids_for( $form_id ) {
        global $wpdb;
        return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d",
            'super_contact_entry',
            absint( $form_id )
        ) ) );
    }
    private function with_super_settings( $settings, $callback ) {
        $missing = '__super_settings_missing__';
        $previous = get_option( 'super_settings', $missing );
        $forms = SUPER_Forms();
        $had_global_settings = isset( $forms->global_settings );
        $previous_global_settings = $had_global_settings ? $forms->global_settings : null;
        update_option( 'super_settings', $settings, false );
        $forms->global_settings = $settings;
        try {
            return call_user_func( $callback );
        } finally {
            if( $previous===$missing ) {
                delete_option( 'super_settings' );
            }else{
                update_option( 'super_settings', $previous, false );
            }
            if( $had_global_settings ) {
                $forms->global_settings = $previous_global_settings;
            }else{
                unset( $forms->global_settings );
            }
        }
    }
    private function render_form( $form_id ) {
        return SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
    }
    private function extract_mailchimp_variant_names( $output ) {
        $matches = array();
        preg_match_all( '/name="(mailchimp_variant_[a-f0-9]{64})"/', $output, $matches );
        if( empty($matches[1]) ) {
            return array();
        }
        return array_values( array_unique( $matches[1] ) );
    }
    private function mailchimp_submission_data( $form_id, $email, $list_id, $variant_names, $interests=null ) {
        $data = array(
            'email' => array(
                'name' => 'email',
                'value' => $email,
                // common.js:4303-4312 posts 'var' for every text input.
                'type' => 'var',
            ),
            'hidden_form_id' => array(
                'name' => 'hidden_form_id',
                'value' => (string) $form_id,
                'type' => 'form_id',
            ),
            'hidden_contact_entry_id' => array(
                'name' => 'hidden_contact_entry_id',
                'value' => '',
                'type' => 'entry_id',
            ),
            'mailchimp_list_id' => array(
                'name' => 'mailchimp_list_id',
                'value' => $list_id,
                'type' => 'var',
            ),
            'mailchimp_subscriber_status' => array(
                'name' => 'mailchimp_subscriber_status',
                'value' => 'subscribed',
                'type' => 'var',
            ),
        );
        foreach( $variant_names as $variant_name ) {
            $data[$variant_name] = array(
                'name' => $variant_name,
                'value' => '1',
                'type' => 'var',
            );
        }
        if( is_array($interests) ) {
            $data['mailchimp_interests'] = array(
                'name' => 'mailchimp_interests',
                'value' => implode( ',', $interests ),
                'selected_values' => $interests,
                'type' => 'var',
            );
        }
        return $data;
    }
    private function set_submission_request( $form_id, $data, $extra_post=array() ) {
        if( !isset($data['hidden_form_id']) ) {
            $data['hidden_form_id'] = array(
                'name' => 'hidden_form_id',
                'value' => (string) $form_id,
                'type' => 'form_id',
            );
        }
        if( !isset($data['hidden_contact_entry_id']) ) {
            $data['hidden_contact_entry_id'] = array(
                'name' => 'hidden_contact_entry_id',
                'value' => isset($extra_post['entry_id']) && absint($extra_post['entry_id'])
                    ? (string) absint($extra_post['entry_id'])
                    : '',
                'type' => 'entry_id',
            );
        }
        $_POST = array_merge( array(
            'form_id' => (string) $form_id,
            'data' => wp_json_encode( $data ),
        ), $extra_post );
        $_REQUEST = $_POST;
    }
    private function mailchimp_http_response( $body ) {
        return array(
            'headers' => array(),
            'body' => wp_json_encode( $body ),
            'response' => array(
                'code' => 200,
                'message' => 'OK',
            ),
            'cookies' => array(),
            'filename' => null,
        );
    }
    private function submit_mailchimp_and_capture_requests( $form_id, $data, $responses ) {
        $requests = array();
        $filter = function( $preempt, $args, $url ) use ( &$requests, $responses ) {
            $method = isset($args['method']) && is_string($args['method']) ? strtoupper($args['method']) : 'GET';
            $body = isset($args['body']) && is_string($args['body']) ? $args['body'] : '';
            $requests[] = array(
                'method' => $method,
                'url' => $url,
                'body' => $body,
            );
            $response_body = isset($responses[$method]) ? $responses[$method] : array();
            return $this->mailchimp_http_response( $response_body );
        };
        $callback = array( SUPER_Mailchimp(), 'update_mailchimp_subscribers' );
        $added_action = false;
        if( !has_action( 'super_before_sending_email_hook', $callback ) ) {
            add_action( 'super_before_sending_email_hook', $callback, 10, 1 );
            $added_action = true;
        }
        add_filter( 'pre_http_request', $filter, 10, 3 );
        try {
            $this->set_submission_request( $form_id, $data );
            $atts = SUPER_Ajax::submit_form_checks( null, false );
        } finally {
            remove_filter( 'pre_http_request', $filter, 10 );
            if( $added_action ) {
                remove_action( 'super_before_sending_email_hook', $callback, 10 );
            }
        }
        return array( $atts, $requests );
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
    private function run_dying_callback( $callback ) {
        $this->require_process_forking( 'The Mailchimp fail-closed regression requires pcntl fork, wait, and exec support.' );
        $capture = tempnam( sys_get_temp_dir(), 'sf-mailchimp-die-' );
        $this->assertNotFalse( $capture );
        $pid = pcntl_fork();
        $this->assertNotSame( -1, $pid );
        if( $pid===0 ) {
            $returned = false;
            ob_start( static function( $buffer ) use ( $capture ) {
                file_put_contents( $capture, $buffer, FILE_APPEND | LOCK_EX );
                return '';
            } );
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
    /**
     * Stored element shape of a real Mailchimp form: the builder's "Email Address"
     * entry is a predefined TEXT element (includes/shortcodes/form-elements.php:72-89),
     * and every saved element carries its builder group (class-shortcodes.php:6356
     * reads $v['group'] unguarded).
     */
    private function email_element() {
        return array(
            'tag' => 'text',
            'group' => 'form_elements',
            'data' => array(
                'name' => 'email',
                'type' => 'email',
                'validation' => 'email',
            ),
        );
    }
    private function mailchimp_element( $overrides=array() ) {
        return array(
            'tag' => 'mailchimp',
            'group' => 'form_elements',
            'data' => array_merge( array(
                'list_id' => 'audience123',
                'display_interests' => 'yes',
                'send_confirmation' => 'no',
                'subscriber_status' => 'subscribed',
                'vip' => 'false',
            ), $overrides ),
        );
    }
    private function mailchimp_elements( $display_interests='yes', $vip='false', $duplicates=1 ) {
        $elements = array( $this->email_element() );
        for( $i = 0; $i < $duplicates; $i++ ) {
            $elements[] = $this->mailchimp_element( array(
                'display_interests' => $display_interests,
                'vip' => $vip,
            ) );
        }
        return $elements;
    }
    private function conflicting_mailchimp_elements() {
        return array(
            $this->email_element(),
            $this->mailchimp_element( array( 'display_interests' => 'no', 'vip' => 'true' ) ),
            $this->mailchimp_element( array( 'display_interests' => 'no', 'vip' => 'false' ) ),
        );
    }


    private function elements() {
        return array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'address',
                    'enable_address_auto_complete' => 'true',
                    'may_be_empty' => 'false',
                ),
            ),
            array(
                'tag' => 'html',
                'data' => array(
                    'name' => 'formatted_note',
                    'may_be_empty' => 'false',
                ),
            ),
        );
    }

    private function production_data( $location ) {
        return array(
            'address' => array(
                'name' => 'address',
                'value' => 'Damrak 1, Amsterdam',
                'label' => 'Address',
                'exclude' => 'false',
                'replace_commas' => 'false',
                'exclude_entry' => 'false',
                'excludeconditional' => 'false',
                'type' => 'google_address',
                'geometry' => array( 'location' => $location ),
            ),
            'formatted_note' => array(
                'name' => 'formatted_note',
                'value' => '<strong>Confirmed</strong>',
                'label' => 'Formatted note',
                'exclude' => 'false',
                'replace_commas' => 'false',
                'exclude_entry' => 'false',
                'excludeconditional' => 'false',
                'type' => 'html',
            ),
            'hidden_form_id' => array(
                'name' => 'hidden_form_id',
                'value' => '41',
                'type' => 'form_id',
            ),
            'hidden_contact_entry_id' => array(
                'name' => 'hidden_contact_entry_id',
                'value' => '',
                'type' => 'entry_id',
            ),
        );
    }

    private function repeater_elements() {
        return array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    array(
                        'tag' => 'text',
                        'data' => array( 'name' => 'guest_name', 'validation' => 'none' ),
                    ),
                    array(
                        'tag' => 'file',
                        'data' => array( 'name' => 'guest_document' ),
                    ),
                ),
            ),
        );
    }

    private function production_repeater_data() {
        $data = array(
            'hidden_form_id' => array(
                'name' => 'hidden_form_id',
                'value' => '41',
                'type' => 'form_id',
            ),
            'hidden_contact_entry_id' => array(
                'name' => 'hidden_contact_entry_id',
                'value' => '',
                'type' => 'entry_id',
            ),
            '_super_dynamic_data' => array(
                'guest_name' => array(
                    array(
                        'guest_name' => array(
                            'name' => 'guest_name',
                            'value' => 'Ada',
                            'label' => 'Guest name',
                            'exclude' => 'false',
                            'replace_commas' => 'false',
                            'exclude_entry' => 'false',
                            'excludeconditional' => 'false',
                            'type' => 'var',
                        ),
                        'guest_document' => array(
                            'label' => 'Guest document',
                            'type' => 'files',
                            'exclude' => 'false',
                            'exclude_entry' => 'false',
                            'files' => array(),
                        ),
                    ),
                    array(
                        'guest_name_2' => array(
                            'name' => 'guest_name_2',
                            'value' => 'Grace',
                            'label' => 'Guest name',
                            'exclude' => 'false',
                            'replace_commas' => 'false',
                            'exclude_entry' => 'false',
                            'excludeconditional' => 'false',
                            'type' => 'var',
                        ),
                        'guest_document_2' => array(
                            'field_name' => 'guest_document',
                            'type' => 'files',
                            'files' => array(),
                        ),
                    ),
                ),
            ),
        );
        $data['guest_name'] = $data['_super_dynamic_data']['guest_name'][0]['guest_name'];
        $data['guest_document'] = $data['_super_dynamic_data']['guest_name'][0]['guest_document'];
        $data['guest_name_2'] = $data['_super_dynamic_data']['guest_name'][1]['guest_name_2'];
        $data['guest_document_2'] = $data['_super_dynamic_data']['guest_name'][1]['guest_document_2'];
        return $data;
    }
    private function nested_repeater_elements() {
        return array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    array(
                        'tag' => 'text',
                        'data' => array( 'name' => 'guest_name', 'validation' => 'none' ),
                    ),
                    array(
                        'tag' => 'column',
                        'data' => array( 'duplicate' => 'enabled' ),
                        'inner' => array(
                            array(
                                'tag' => 'text',
                                'data' => array( 'name' => 'guest_note', 'validation' => 'none' ),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    private function nested_repeater_data() {
        $data = array(
            'hidden_form_id' => array(
                'name' => 'hidden_form_id',
                'value' => '41',
                'type' => 'form_id',
            ),
            'hidden_contact_entry_id' => array(
                'name' => 'hidden_contact_entry_id',
                'value' => '',
                'type' => 'entry_id',
            ),
            '_super_dynamic_data' => array(
                'guest_name' => array(
                    array(
                        'guest_name' => array(
                            'name' => 'guest_name',
                            'value' => 'Ada',
                            'type' => 'var',
                        ),
                        'guest_note[0]' => array(
                            'name' => 'guest_note[0]',
                            'value' => 'Window seat',
                            'type' => 'var',
                        ),
                        'guest_note[1]' => array(
                            'name' => 'guest_note[1]',
                            'value' => 'Aisle seat',
                            'type' => 'var',
                        ),
                    ),
                ),
            ),
        );
        $data['guest_name'] = $data['_super_dynamic_data']['guest_name'][0]['guest_name'];
        $data['guest_note[0]'] = $data['_super_dynamic_data']['guest_name'][0]['guest_note[0]'];
        $data['guest_note[1]'] = $data['_super_dynamic_data']['guest_name'][0]['guest_note[1]'];
        return $data;
    }
    private function submit_review8_probe( $elements, $data, $allowed, $language='' ) {
        global $wpdb;
        $form_id = $this->create_form($elements, array(
            'save_contact_entry' => 'yes', 'send' => 'no', 'confirm' => 'no',
            'form_thanks_title' => '', 'form_thanks_description' => '',
            'form_show_thanks_msg' => '', 'form_redirect_option' => '',
        ));
        $marker = '_review8_hook_' . $form_id;
        $hook = static function( $value ) use ( $marker ) { update_option($marker, 'reached', false); return $value; };
        add_filter('super_before_sending_email_data_filter', $hook);
        try {
            $result = $this->with_super_settings(array('csrf_check' => 'false'), function() use ($form_id, $data, $language) {
                $this->set_submission_request($form_id, $data, array('action' => 'super_submit_form', 'i18n' => $language));
                // A real WordPress request is slashed before the handler unslashes JSON.
                $_POST['data'] = wp_slash($_POST['data']);
                $_REQUEST = $_POST;
                return $this->run_dying_callback(static function() { SUPER_Ajax::submit_form(); });
            });
        } finally { remove_filter('super_before_sending_email_data_filter', $hook); }
        $this->assertSame(0, $result['status'], $result['output']);
        $decoded = json_decode($result['output'], true);
        $this->assertIsArray($decoded, $result['output']);
        $this->assertSame(!$allowed, $decoded['error'], $result['output'] . wp_json_encode($data));
        $this->assertCount($allowed ? 1 : 0, $this->entry_ids_for($form_id));
        $observed = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $marker));
        $this->assertSame($allowed ? 'reached' : null, $observed);
        delete_option($marker);
        return $form_id;
    }

    public function test_translated_dates_use_saved_language_formats_before_submission_effects() {
        $cases = array(
            array('', '27-09-2026', true, '', ''),
            array('en', '09/27/2026', true, '', ''),
            array('de', '3 März 2026', true, '', ''),
            array('en', '02/31/2026', false, '', ''),
            array('en', '27-09-2026', false, '', ''),
            array('unknown', '09/27/2026', false, '', ''),
            array('unknown', '27-09-2026', true, '', ''),
            array(array('en'), '09/27/2026', false, '', ''),
            array('en', '27.09.2026', false, 'dd.mm.yy', 'de'),
        );
        foreach($cases as $case) {
            list($language, $value, $allowed, $client_format, $client_locale) = $case;
            $date = array('tag' => 'date', 'data' => array(
                'name' => 'appointment', 'format' => 'custom', 'custom_format' => 'dd-mm-yy',
                'localization' => '', 'maxPicks' => '1', 'validation' => 'none',
                'i18n' => array(
                    'en' => array('custom_format' => 'mm/dd/yy'),
                    'de' => array('custom_format' => 'd MM yy', 'localization' => 'de'),
                ),
            ));
            // Nest the date to exercise the same traversal used for layout columns.
            $elements = array(array('tag' => 'column', 'data' => array(), 'inner' => array($date)));
            $data = array('appointment' => array('name' => 'appointment', 'type' => 'var', 'value' => $value));
            if($client_format!=='') $data['appointment']['format'] = $client_format;
            if($client_locale!=='') $data['appointment']['localization'] = $client_locale;
            $form_id = $this->submit_review8_probe($elements, $data, $allowed, $language);
            if($allowed) {
                $entries = $this->entry_ids_for($form_id);
                $stored = SUPER_Data_Access::get_entry_data($entries[0]);
                $this->assertSame($value, $stored['appointment']['value']);
                $expected = $language==='de' ? gmmktime(0, 0, 0, 3, 3, 2026) : gmmktime(0, 0, 0, 9, 27, 2026);
                $this->assertSame((string)($expected * 1000), $stored['appointment']['timestamp']);
            }
        }
    }


    public function test_yearless_custom_dates_submit_with_a_server_owned_current_year() {
        $year = (int)gmdate('Y');
        foreach(array(
            array('dd-mm', '', '03-09', true, 9, 3),
            array('d MM', 'de', '3 März', true, 3, 3),
            array('dd-mm', '', '31-02', false, 2, 31),
            array('dd-mm', '', '29-02', checkdate(2, 29, $year), 2, 29),
            array('dd-mm', '', '03-09-2026', false, 9, 3),
            array('oo', '', '001', true, 1, 1),
            array('mm', '', '09', false, 9, 0),
        ) as $case) {
            list($format, $locale, $value, $allowed, $month, $day) = $case;
            $elements = array(array('tag' => 'date', 'data' => array(
                'name' => 'appointment', 'format' => 'custom', 'custom_format' => $format,
                'localization' => $locale, 'maxPicks' => '1', 'validation' => 'none',
            )));
            $data = array('appointment' => array('name' => 'appointment', 'type' => 'var', 'value' => $value, 'timestamp' => '1'));
            $form_id = $this->submit_review8_probe($elements, $data, $allowed);
            if($allowed) {
                $entries = $this->entry_ids_for($form_id);
                $stored = SUPER_Data_Access::get_entry_data($entries[0]);
                $this->assertSame($value, $stored['appointment']['value']);
                $this->assertSame((string)(gmmktime(0, 0, 0, $month, $day, $year) * 1000), $stored['appointment']['timestamp']);
            }
        }
    }

    public function test_saved_date_minimum_picks_are_enforced_before_submission_effects() {
        foreach(array(
            array('0', '', true),
            array('1', '', false),
            array('1', '03-09-2026', true),
            array('2', '', false),
            array('2', '03-09-2026', false),
            array('2', '03-09-2026, 04-09-2026', true),
            array('2', '03-09-2026, 03-09-2026', false),
            array('2', '03-09-2026, 31-02-2026', false),
        ) as $case) {
            list($minimum, $value, $allowed) = $case;
            $elements = array(array('tag' => 'date', 'data' => array(
                'name' => 'appointment', 'format' => 'custom', 'custom_format' => 'dd-mm-yy',
                'maxPicks' => '2', 'minPicks' => $minimum, 'validation' => 'none',
            )));
            $data = array('appointment' => array('name' => 'appointment', 'type' => 'var', 'value' => $value));
            $this->submit_review8_probe($elements, $data, $allowed);
        }
    }

    public function test_multi_date_submission_validates_every_pick_and_saved_limit_before_effects() {
        $cases = array(
            array('dd-mm-yy', '', '03-09-2026, 04-09-2026', true),
            array('dd-mm-yy', '', '03-09-2026', true),
            array('dd-mm-yy', '', '', true),
            array('dd-mm-yy', '', 'arbitrary text', false),
            array('dd-mm-yy', '', '03-09-2026, 31-02-2026', false),
            array('dd-mm-yy', '', '03-09-2026, 04-09-2026, 05-09-2026', false),
            array('dd-mm-yy', '', '03-09-2026, 03-09-2026', false),
            array('dd-mm-yy', '', '03-09-2026, ', false),
            array('dd-mm-yy', '', '03-09-2026; 04-09-2026', false),
            array('D, d M yy', '', 'Thu, 3 Sep 2026, Fri, 4 Sep 2026', true),
            array('dd-mm-yy', 'de', '03.03.2026, 04.03.2026', true),
            array('d MM yy', 'de', '3 März 2026, 4 März 2026', true),
        );
        foreach($cases as $case) {
            list($format, $locale, $value, $allowed) = $case;
            $elements = array(array('tag' => 'date', 'data' => array(
                'name' => 'appointment', 'format' => 'custom', 'custom_format' => $format,
                'localization' => $locale, 'maxPicks' => '2', 'validation' => 'none',
            )));
            $data = array('appointment' => array('name' => 'appointment', 'type' => 'var', 'value' => $value, 'timestamp' => 'forged'));
            $form_id = $this->submit_review8_probe($elements, $data, $allowed);
            if($allowed && strpos($value, ', ')!==false) {
                $entries = $this->entry_ids_for($form_id);
                $stored = SUPER_Data_Access::get_entry_data($entries[0]);
                $this->assertSame($value, $stored['appointment']['value']);
                $this->assertArrayNotHasKey('timestamp', $stored['appointment']);
            }
        }
    }

    public function test_nested_repeated_codes_are_reserved_and_mirrored_before_storage() {
        global $wpdb;
        foreach(array(1, 2) as $depth) {
            $prefix = 'NEST' . $depth . '-';
            $invoice_key = 'nestedreview10-' . $depth;
            $counter_key = '_sf_invoice_number_' . $invoice_key;
            update_option($counter_key, '1', false);
            update_option('_sf_unique_code-' . $prefix . '0001', $prefix . '0001', false);
            $code = array('tag' => 'hidden', 'data' => array(
                'name' => 'invoice_code', 'enable_random_code' => 'true', 'code_length' => '0',
                'code_prefix' => $prefix, 'code_invoice' => 'true', 'code_invoice_key' => $invoice_key, 'code_invoice_padding' => '4',
            ));
            $nested = $code;
            for($n=0; $n<$depth; $n++) $nested = array('tag' => 'column', 'data' => array('duplicate' => 'enabled'), 'inner' => array($nested));
            $elements = array(array('tag' => 'column', 'data' => array('duplicate' => 'enabled'), 'inner' => array(
                array('tag' => 'text', 'data' => array('name' => 'guest_name', 'validation' => 'none')), $nested,
            )));
            $data = array('_super_dynamic_data' => array('guest_name' => array()));
            $routes = array();
            for($row=0; $row<2; $row++) {
                $suffix = $row===0 ? '' : '_2';
                $guest = 'guest_name' . $suffix;
                $carriers = array($guest => array('name' => $guest, 'type' => 'var', 'value' => 'Guest ' . $row));
                for($inner=0; $inner<2; $inner++) {
                    $name = 'invoice_code' . str_repeat('[0]', $depth-1) . '[' . $inner . ']' . $suffix;
                    $carriers[$name] = array('name' => $name, 'type' => 'var', 'value' => $prefix . '0001');
                    $routes[$name] = $row;
                }
                $data['_super_dynamic_data']['guest_name'][] = $carriers;
                $data = array_merge($data, $carriers);
            }
            $hook_key = '_nested_code_hook_' . $depth;
            $hook = static function($value) use ($hook_key) { update_option($hook_key, $value, false); return $value; };
            add_filter('super_before_sending_email_data_filter', $hook);
            try { $form_id = $this->submit_review8_probe($elements, $data, true); }
            finally { remove_filter('super_before_sending_email_data_filter', $hook); }
            $entries = $this->entry_ids_for($form_id);
            $stored = SUPER_Data_Access::get_entry_data($entries[0]);
            $seen_by_hook = maybe_unserialize($wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $hook_key)));
            $number = 2;
            foreach($routes as $name => $row) {
                $expected = $prefix . sprintf('%04d', $number++);
                $this->assertSame($expected, $stored[$name]['value']);
                $this->assertSame($expected, $stored['_super_dynamic_data']['guest_name'][$row][$name]['value']);
                $this->assertSame($expected, $seen_by_hook[$name]['value']);
                $this->assertSame($expected, $seen_by_hook['_super_dynamic_data']['guest_name'][$row][$name]['value']);
                $this->assertSame($expected, $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", '_sf_unique_code-' . $expected)));
            }
            $this->assertSame('5', $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $counter_key)));
            delete_option($hook_key);
            // Malformed nested routes must fail before another invoice is reserved.
            $old_name = array_key_first($routes);
            $bad_name = str_replace('[0]', '[bad]', $old_name);
            $bad = $data;
            $bad[$bad_name] = $bad[$old_name];
            $bad[$bad_name]['name'] = $bad_name;
            unset($bad[$old_name]);
            $bad['_super_dynamic_data']['guest_name'][0][$bad_name] = $bad[$bad_name];
            unset($bad['_super_dynamic_data']['guest_name'][0][$old_name]);
            $this->submit_review8_probe($elements, $bad, false);
            $this->assertSame('5', $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $counter_key)));
        }
    }

    public function test_unconditional_generated_codes_cannot_be_omitted_but_dynamic_omissions_remain_valid() {
        global $wpdb;
        $code = array('tag' => 'hidden', 'data' => array(
            'name' => 'invoice_code', 'enable_random_code' => 'true', 'code_length' => '0',
            'code_prefix' => 'REVIEW8-', 'code_invoice' => 'true', 'code_invoice_key' => 'review8', 'code_invoice_padding' => '4',
        ));
        $carrier = array('tag' => 'text', 'data' => array('name' => 'carrier', 'validation' => 'none'));
        $data = array('carrier' => array('name' => 'carrier', 'type' => 'var', 'value' => 'kept'));
        $cases = array(
            array($code, false),
            array(array('tag' => 'multipart', 'inner' => array($code)), false),
            array(array('tag' => 'column', 'data' => array('conditional_action' => 'show'), 'inner' => array($code)), true),
            array(array('tag' => 'column', 'data' => array('hide_on_mobile' => 'true'), 'inner' => array($code)), true),
            array(array('tag' => 'column', 'data' => array('duplicate' => 'enabled'), 'inner' => array($code)), true),
        );
        foreach(array('conditional_action' => 'show', 'hide_on_mobile' => 'true') as $setting => $value) {
            $optional = array('tag' => 'column', 'data' => array($setting => $value), 'inner' => array($code));
            foreach(array(array($code, $optional), array($optional, $code)) as $declarations) {
                $cases[] = array(array('tag' => 'column', 'inner' => $declarations), false);
            }
        }
        foreach($cases as $case) {
            $before = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '_sf_invoice_number_review8'");
            $this->submit_review8_probe(array($carrier, $case[0]), $data, $case[1]);
            $this->assertSame($before, $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '_sf_invoice_number_review8'"));
        }
        $data['invoice_code'] = array('name' => 'invoice_code', 'type' => 'var', 'value' => '');
        $form_id = $this->submit_review8_probe(array($carrier, $code), $data, true);
        $entries = $this->entry_ids_for($form_id);
        $stored = SUPER_Data_Access::get_entry_data($entries[0]);
        $this->assertSame('REVIEW8-0001', $stored['invoice_code']['value']);
        $this->assertSame('1', $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '_sf_invoice_number_review8'"));
    }

    public function test_date_timestamps_are_rebuilt_and_invalid_date_values_are_rejected() {
        $elements = array(
            array(
                'tag' => 'date',
                'data' => array(
                    'name' => 'appointment',
                    'format' => 'dd-mm-yy',
                ),
            ),
        );
        $data = array(
            'appointment' => array(
                'name' => 'appointment',
                'value' => '03-09-2026',
                'timestamp' => array( 'forged' ),
                'type' => 'var',
            ),
        );
        $rebuilt = $this->rebuild_selection_entry_data( $data, $elements );
        $this->assertSame(
            (string) (((int) gmmktime( 0, 0, 0, 9, 3, 2026 )) * 1000),
            $rebuilt['appointment']['timestamp']
        );
        $blank = $data;
        $blank['appointment']['value'] = '';
        $blank['appointment']['timestamp'] = 'forged';
        $blank_rebuilt = $this->rebuild_selection_entry_data( $blank, $elements );
        $this->assertArrayNotHasKey( 'timestamp', $blank_rebuilt['appointment'] );

        $localized_elements = array(
            array(
                'tag' => 'date',
                'data' => array(
                    'name' => 'localized_appointment',
                    'format' => 'MM d yy / M',
                    'localization' => 'de',
                ),
            ),
        );
        $localized_data = array(
            'localized_appointment' => array(
                'name' => 'localized_appointment',
                'value' => 'März 3 2026 / Mär',
                'timestamp' => 'forged',
                'type' => 'var',
            ),
        );
        $localized = $this->rebuild_selection_entry_data( $localized_data, $localized_elements );
        $this->assertSame(
            (string) (((int) gmmktime( 0, 0, 0, 3, 3, 2026 )) * 1000),
            $localized['localized_appointment']['timestamp']
        );
        $localized_fallback_elements = array(
            array(
                'tag' => 'date',
                'data' => array(
                    'name' => 'localized_fallback_appointment',
                    'format' => 'dd-mm-yy',
                    'localization' => 'de',
                ),
            ),
        );
        $localized_fallback_data = array(
            'localized_fallback_appointment' => array(
                'name' => 'localized_fallback_appointment',
                'value' => '03.03.2026',
                'timestamp' => 'forged',
                'type' => 'var',
            ),
        );
        $localized_fallback = $this->rebuild_selection_entry_data( $localized_fallback_data, $localized_fallback_elements );
        $this->assertSame(
            (string) (((int) gmmktime( 0, 0, 0, 3, 3, 2026 )) * 1000),
            $localized_fallback['localized_fallback_appointment']['timestamp']
        );
        $multi_pick_elements = array(
            array(
                'tag' => 'date',
                'data' => array(
                    'name' => 'multi_pick_appointment',
                    'format' => 'dd-mm-yy',
                    'maxPicks' => '2',
                ),
            ),
        );
        $multi_pick_data = array(
            'multi_pick_appointment' => array(
                'name' => 'multi_pick_appointment',
                'value' => '03-09-2026, 04-09-2026',
                'timestamp' => 'forged',
                'type' => 'var',
            ),
        );
        $multi_pick = $this->rebuild_selection_entry_data( $multi_pick_data, $multi_pick_elements );
        $this->assertArrayNotHasKey( 'timestamp', $multi_pick['multi_pick_appointment'] );

        $unparseable = $data;
        $unparseable['appointment']['value'] = 'not-a-date';
        $unparseable['appointment']['timestamp'] = '999';
        $stripped = $this->rebuild_selection_entry_data( $unparseable, $elements );
        $this->assertFalse( $stripped, 'Invalid dates must fail the submission contract, not merely lose derived metadata.' );

        $unsupported_elements = array(
            array(
                'tag' => 'date',
                'data' => array(
                    'name' => 'unsupported_appointment',
                    'format' => 'dd-mm-yy HH',
                ),
            ),
        );
        $unsupported_data = array(
            'unsupported_appointment' => array(
                'name' => 'unsupported_appointment',
                'value' => '03-09-2026 12',
                'timestamp' => '999',
                'type' => 'var',
            ),
        );
        $unsupported = $this->rebuild_selection_entry_data( $unsupported_data, $unsupported_elements );
        $this->assertFalse( $unsupported, 'Values that cannot be parsed with the stored format must be rejected.' );
    }
}
