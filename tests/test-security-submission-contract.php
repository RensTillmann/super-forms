<?php
/**
 * Regression coverage for server-owned submission carrier contracts.
 *
 * @package Super_Forms_Tests
 */

class Test_Super_Forms_Submission_Contract_Security extends WP_UnitTestCase {
    private $client_sessions = array();
    private $original_cookie_exists = false;
    private $original_cookie_value = null;
    public static function set_up_before_class() {
        parent::set_up_before_class();
        // DOING_AJAX is never defined in the WP test bootstrap, so super-forms.php
        // is_request('ajax') is false and ajax_includes() never loads SUPER_Ajax.
        if( !class_exists( 'SUPER_Ajax' ) ) {
            require_once dirname( __DIR__ ) . '/includes/class-ajax.php';
        }
        if( !class_exists( 'SUPER_Register_Login' ) ) {
            require_once dirname( __DIR__ ) . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
        if( !class_exists( 'SUPER_Mailchimp' ) ) {
            require_once dirname( __DIR__ ) . '/add-ons/super-forms-mailchimp/super-forms-mailchimp.php';
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

    public function test_production_address_and_html_carriers_match_the_stored_schema() {
        $this->assertTrue( $this->validate(
            $this->production_data( array( 'lat' => '52.377956', 'lng' => '4.897070' ) ),
            $this->elements()
        ) );
        $this->assertTrue(
            $this->validate( $this->production_data( array() ), $this->elements() ),
            'The browser emits an empty location object when an address was typed without selecting a place.'
        );
    }

    public function test_google_address_geometry_rejects_non_browser_shapes() {
        $valid = $this->production_data( array( 'lat' => 52.377956, 'lng' => 4.897070 ) );
        $cases = array();

        $candidate = $valid;
        unset( $candidate['address']['geometry'] );
        $cases['missing geometry'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry'] = array();
        $cases['missing location'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['location'] = array( 'lat' => 52.377956 );
        $cases['one coordinate'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['location']['lat'] = 'north';
        $cases['non-numeric coordinate'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['location']['lat'] = 91;
        $cases['latitude out of range'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['location']['accuracy'] = 1;
        $cases['extra location member'] = $candidate;

        $candidate = $valid;
        $candidate['address']['geometry']['viewport'] = array();
        $cases['extra geometry member'] = $candidate;

        foreach ( $cases as $label => $candidate ) {
            $this->assertFalse( $this->validate( $candidate, $this->elements() ), $label );
        }
    }

    public function test_plain_text_schema_cannot_be_claimed_as_a_google_address() {
        $elements = $this->elements();
        $elements[0]['data']['enable_address_auto_complete'] = 'false';
        $data = $this->production_data( array() );
        unset( $data['formatted_note'] );
        $this->assertFalse( $this->validate( $data, array( $elements[0] ) ) );
    }

    public function test_exact_browser_identity_carriers_match_authoritative_submission_ids() {
        $data = $this->production_data( array() );
        $data['hidden_contact_entry_id']['value'] = '73';

        $this->assertTrue( $this->validate( $data, $this->elements(), 41, 73 ) );

        $forgeries = array();
        $candidate = $data;
        $candidate['hidden_form_id']['value'] = '42';
        $forgeries['form id from another form'] = $candidate;
        $candidate = $data;
        $candidate['hidden_contact_entry_id']['value'] = '074';
        $forgeries['non-canonical entry id'] = $candidate;
        $candidate = $data;
        $candidate['hidden_form_id']['name'] = 'form_id';
        $forgeries['form carrier name'] = $candidate;
        $candidate = $data;
        $candidate['hidden_contact_entry_id']['type'] = 'var';
        $forgeries['entry carrier type'] = $candidate;
        $candidate = $data;
        $candidate['hidden_form_id']['extra'] = 'forged';
        $forgeries['form carrier extra member'] = $candidate;
        $candidate = $data;
        $candidate['hidden_form_id']['value'] = '41junk';
        $forgeries['malformed form id'] = $candidate;
        $candidate = $data;
        unset( $candidate['hidden_contact_entry_id'] );
        $forgeries['missing entry carrier'] = $candidate;
        $candidate = $data;
        $candidate['hidden_contact_entry_id']['value'] = array( '73' );
        $forgeries['entry carrier non-scalar value'] = $candidate;

        foreach ( $forgeries as $label => $candidate ) {
            $this->assertFalse( $this->validate( $candidate, $this->elements(), 41, 73 ), $label );
        }
    }
    public function test_new_submissions_accept_zero_as_the_canonical_absent_entry_identity() {
        $data = $this->production_data( array() );
        $data['hidden_contact_entry_id']['value'] = '0';
        $this->assertTrue( $this->validate( $data, $this->elements() ) );
    }
    public function test_rendered_update_contact_entry_form_emits_a_zero_hidden_contact_entry_id_without_a_prefilled_entry() {
        $form_id = $this->create_form(
            array(),
            array(
                'update_contact_entry' => 'true',
            )
        );
        $output = $this->render_form( $form_id );
        $this->assertSame( 1, preg_match( '/<input\b[^>]*(?:name="hidden_contact_entry_id"[^>]*value="0"|value="0"[^>]*name="hidden_contact_entry_id")[^>]*>/', $output ) );
        $this->assertSame( 1, substr_count( $output, 'name="hidden_contact_entry_id"' ) );
    }
    public function test_posted_entry_id_is_ignored_when_form_updates_are_disabled_and_a_new_entry_is_created() {
        $form_id = $this->create_form(
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'note',
                    ),
                ),
            ),
            array(
                'save_contact_entry' => 'yes',
                'send' => 'no',
                'confirm' => 'no',
                'form_thanks_title' => '',
                'form_thanks_description' => '',
                'form_show_thanks_msg' => '',
                'form_redirect_option' => '',
            )
        );
        $existing_entry_id = self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'super_unread',
            'post_parent' => $form_id,
        ) );
        SUPER_Data_Access::update_entry_data( $existing_entry_id, array(
            'note' => array(
                'name' => 'note',
                'value' => 'original',
                'type' => 'var',
            ),
        ) );
        $before_ids = $this->entry_ids_for( $form_id );
        $result = $this->with_super_settings(
            array( 'csrf_check' => 'false' ),
            function() use ( $form_id, $existing_entry_id ) {
                $this->set_submission_request(
                    $form_id,
                    array(
                        'note' => array(
                            'name' => 'note',
                            'value' => 'replacement',
                            'type' => 'var',
                        ),
                    ),
                    array(
                        'action' => 'super_submit_form',
                        'i18n' => '',
                        'entry_id' => (string) $existing_entry_id,
                    )
                );
                return $this->run_dying_callback( function() {
                    SUPER_Ajax::submit_form();
                } );
            }
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );
        $new_entry_id = (int) $decoded['response_data']['contact_entry_id'];
        $this->assertGreaterThan( 0, $new_entry_id );
        $this->assertNotSame( $existing_entry_id, $new_entry_id );
        $this->assertSame( 'original', SUPER_Data_Access::get_entry_data( $existing_entry_id )['note']['value'] );
        $this->assertSame( 'replacement', SUPER_Data_Access::get_entry_data( $new_entry_id )['note']['value'] );
        $after_ids = $this->entry_ids_for( $form_id );
        sort( $before_ids );
        sort( $after_ids );
        $expected_ids = array_merge( $before_ids, array( $new_entry_id ) );
        sort( $expected_ids );
        $this->assertSame( $expected_ids, $after_ids );
    }
    /**
     * Fresh browser submissions omit the entry_id POST key entirely
     * (assets/js/common.js only appends entry_id when args.entry_id is truthy).
     * On PHP 8 the absent-key sentinel default of '' made every loose
     * $entry_id!=0 gate in submit_form() treat the request as a contact-entry
     * UPDATE, so the write-then-readback verification rejected the submission
     * with "Unable to save contact entry.".
     *
     * Masking condition: every other full submit_form() regression sets the
     * entry_id POST key explicitly (an existing id or the string '0'), so
     * absint() normalised a present value and the absent-key default branch --
     * reached only when the key is missing, i.e. the real browser shape -- was
     * never exercised. This test posts without the key so it fails against the
     * A49 candidate ('' sentinel) and passes once the sentinel defaults to 0.
     */
    public function test_fresh_browser_submission_without_entry_id_post_key_creates_exactly_one_entry() {
        $form_id = $this->create_form(
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'note',
                    ),
                ),
            ),
            array(
                'save_contact_entry' => 'yes',
                'send' => 'no',
                'confirm' => 'no',
                'form_thanks_title' => '',
                'form_thanks_description' => '',
                'form_show_thanks_msg' => '',
                'form_redirect_option' => '',
            )
        );
        $before_ids = $this->entry_ids_for( $form_id );
        $result = $this->with_super_settings(
            array( 'csrf_check' => 'false' ),
            function() use ( $form_id ) {
                $this->set_submission_request(
                    $form_id,
                    array(
                        'note' => array(
                            'name' => 'note',
                            'value' => 'fresh',
                            'type' => 'var',
                        ),
                    ),
                    array(
                        'action' => 'super_submit_form',
                        'i18n' => '',
                    )
                );
                // Browser shape: fresh submissions never send the entry_id key.
                $this->assertArrayNotHasKey( 'entry_id', $_POST );
                return $this->run_dying_callback( function() {
                    SUPER_Ajax::submit_form();
                } );
            }
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );
        $new_entry_id = (int) $decoded['response_data']['contact_entry_id'];
        $this->assertGreaterThan( 0, $new_entry_id );
        $after_ids = $this->entry_ids_for( $form_id );
        sort( $before_ids );
        sort( $after_ids );
        $expected_ids = array_merge( $before_ids, array( $new_entry_id ) );
        sort( $expected_ids );
        $this->assertSame( $expected_ids, $after_ids );
        $this->assertSame( 'fresh', SUPER_Data_Access::get_entry_data( $new_entry_id )['note']['value'] );
    }

    public function test_hidden_list_id_matches_the_present_post_list_identity() {
        $data = $this->production_data( array() );
        $data['hidden_list_id'] = array(
            'name'  => 'hidden_list_id',
            'value' => '0',
            'type'  => 'var',
        );
        $this->assertTrue( $this->validate( $data, $this->elements(), 41, '', 0 ) );

        $forgeries = array();
        $candidate = $data;
        $candidate['hidden_list_id']['value'] = '1';
        $forgeries['mismatched list identity'] = $candidate;
        $candidate = $data;
        $candidate['hidden_list_id']['value'] = '00';
        $forgeries['non-canonical list identity'] = $candidate;
        $candidate = $data;
        unset( $candidate['hidden_list_id']['value'] );
        $forgeries['missing list value'] = $candidate;
        $candidate = $data;
        unset( $candidate['hidden_list_id']['type'] );
        $forgeries['missing list type'] = $candidate;
        $candidate = $data;
        $candidate['hidden_list_id']['type'] = 'list_id';
        $forgeries['list carrier type'] = $candidate;
        $candidate = $data;
        $candidate['hidden_list_id'] = '0';
        $forgeries['non-array list carrier'] = $candidate;

        foreach ( $forgeries as $label => $candidate ) {
            $this->assertFalse( $this->validate( $candidate, $this->elements(), 41, '', 0 ), $label );
        }
        $this->assertFalse( $this->validate( $data, $this->elements() ), 'missing POST list identity' );
        $data['unknown_field'] = array( 'name' => 'unknown_field', 'value' => 'x', 'type' => 'var' );
        $this->assertFalse( $this->validate( $data, $this->elements(), 41, '', 0 ), 'unknown non-reserved field' );
    }
    public function test_register_login_activation_code_addon_declares_its_submission_carrier() {
        $this->assertTrue( class_exists( 'SUPER_Register_Login' ) );
        $data = array(
            'activation_code' => array(
                'name' => 'activation_code',
                'value' => 'ABC123',
                'type' => 'var',
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
        $elements = array(
            array(
                'tag' => 'activation_code',
                'data' => array(),
            ),
        );
        $original_get = $_GET;
        $_GET['code'] = 'ABC123';
        try {
            $this->assertTrue( $this->validate( $data, $elements ) );
        } finally {
            $_GET = $original_get;
        }
    }
    public function test_activation_code_repeater_routes_accept_the_submitted_alias_and_the_stored_group_key() {
        $elements = array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    array(
                        'tag' => 'activation_code',
                        'data' => array(
                            'conditional_action' => 'show',
                        ),
                    ),
                    array(
                        'tag' => 'text',
                        'data' => array(
                            'name' => 'user_login',
                        ),
                    ),
                ),
            ),
        );
        $with_codes = array(
            '_super_dynamic_data' => array(
                'activation_code' => array(
                    array(
                        'activation_code' => array(
                            'name' => 'activation_code',
                            'value' => 'CODE-1',
                            'type' => 'var',
                        ),
                        'user_login' => array(
                            'name' => 'user_login',
                            'value' => 'ada',
                            'type' => 'var',
                        ),
                    ),
                    array(
                        'activation_code_2' => array(
                            'name' => 'activation_code_2',
                            'value' => 'CODE-2',
                            'type' => 'var',
                        ),
                        'user_login_2' => array(
                            'name' => 'user_login_2',
                            'value' => 'grace',
                            'type' => 'var',
                        ),
                    ),
                ),
            ),
        );
        $with_codes['hidden_form_id'] = array(
            'name' => 'hidden_form_id',
            'value' => '41',
            'type' => 'form_id',
        );
        $with_codes['hidden_contact_entry_id'] = array(
            'name' => 'hidden_contact_entry_id',
            'value' => '',
            'type' => 'entry_id',
        );
        $with_codes['activation_code'] = $with_codes['_super_dynamic_data']['activation_code'][0]['activation_code'];
        $with_codes['user_login'] = $with_codes['_super_dynamic_data']['activation_code'][0]['user_login'];
        $with_codes['activation_code_2'] = $with_codes['_super_dynamic_data']['activation_code'][1]['activation_code_2'];
        $with_codes['user_login_2'] = $with_codes['_super_dynamic_data']['activation_code'][1]['user_login_2'];
        // Renderer-issued proof that the conditional activation-code field was
        // actually presented (mirrors the populate/print capability pattern). The
        // carrier is never admitted from the client payload alone.
        unset( $_GET['code'] );
        $this->seed_client_session();
        $grant = SUPER_Common::current_entry_update_grant_value( true );
        $this->assertIsArray( $grant );
        SUPER_Common::setClientData( array(
            'name' => 'activation_code_presented_41',
            'value' => $grant,
            'force' => true,
        ) );
        $this->assertTrue( SUPER_Common::entry_update_grant_matches_current(
            SUPER_Common::getClientData( 'activation_code_presented_41', false )
        ) );
        $this->set_submission_request( 41, $with_codes, array( 'action' => 'super_submit_form' ) );
        $this->assertTrue( $this->validate( $with_codes, $elements ) );

        // A tampered repeater suffix with no matching dynamic row is rejected even
        // though the base key is proven and the shared route matcher parses it.
        $tampered = $with_codes;
        $tampered['activation_code_999'] = array(
            'name' => 'activation_code_999',
            'value' => 'CODE-999',
            'type' => 'var',
        );
        $this->set_submission_request( 41, $tampered, array( 'action' => 'super_submit_form' ) );
        $this->assertFalse( $this->validate( $tampered, $elements ) );
        // Third shape: the activation-code field was never presented, so the
        // renderer issued no proof and the repeater group is keyed off the first
        // field that really rendered. Drop the proof issued above to model that.
        SUPER_Common::setClientData( array(
            'name' => 'activation_code_presented_41',
            'value' => false,
            'force' => true,
        ) );
        $this->assertFalse( SUPER_Common::entry_update_grant_matches_current(
            SUPER_Common::getClientData( 'activation_code_presented_41', false )
        ) );
        $without_codes = array(
            '_super_dynamic_data' => array(
                'user_login' => array(
                    array(
                        'user_login' => array(
                            'name' => 'user_login',
                            'value' => 'ada',
                            'type' => 'var',
                        ),
                    ),
                    array(
                        'user_login_2' => array(
                            'name' => 'user_login_2',
                            'value' => 'grace',
                            'type' => 'var',
                        ),
                    ),
                ),
            ),
        );
        $without_codes['hidden_form_id'] = array(
            'name' => 'hidden_form_id',
            'value' => '41',
            'type' => 'form_id',
        );
        $without_codes['hidden_contact_entry_id'] = array(
            'name' => 'hidden_contact_entry_id',
            'value' => '',
            'type' => 'entry_id',
        );
        $without_codes['user_login'] = $without_codes['_super_dynamic_data']['user_login'][0]['user_login'];
        $without_codes['user_login_2'] = $without_codes['_super_dynamic_data']['user_login'][1]['user_login_2'];
        $this->set_submission_request( 41, $without_codes, array( 'action' => 'super_submit_form' ) );
        $this->assertTrue( $this->validate( $without_codes, $elements ) );
    }

    public function test_mailchimp_addon_renders_variant_bound_hidden_submission_carriers() {
        $this->assertTrue( class_exists( 'SUPER_Mailchimp' ) );
        $form_id = $this->create_form( $this->mailchimp_elements( 'yes' ) );
        $output = $this->with_super_settings(
            array( 'mailchimp_key' => 'invalidkey' ),
            function() use ( $form_id ) {
                return $this->render_form( $form_id );
            }
        );
        $variant_names = $this->extract_mailchimp_variant_names( $output );
        $this->assertCount( 1, $variant_names );
        $data = $this->mailchimp_submission_data(
            $form_id,
            'person@example.test',
            'audience123',
            $variant_names,
            array( 'interest-1', 'interest-2' )
        );
        $this->with_super_settings(
            array( 'mailchimp_key' => 'test-us1' ),
            function() use ( $data, $form_id, $variant_names ) {
                $this->assertTrue( $this->validate( $data, $this->mailchimp_elements( 'yes' ), $form_id ) );

                $missing_variant = $data;
                unset( $missing_variant[$variant_names[0]] );
                $this->assertTrue( $this->validate( $missing_variant, $this->mailchimp_elements( 'yes' ), $form_id ) );

                $forged_variant = $data;
                $forged_variant[$variant_names[0]]['value'] = '0';
                $this->assertFalse( $this->validate( $forged_variant, $this->mailchimp_elements( 'yes' ), $form_id ) );

                $forged_selected_values = $data;
                $forged_selected_values['mailchimp_interests']['selected_values'] = array( 'interest-1', array( 'nested' ) );
                $this->assertFalse( $this->validate( $forged_selected_values, $this->mailchimp_elements( 'yes' ), $form_id ) );
            }
        );
    }
    public function test_mailchimp_existing_member_interest_merge_accepts_identical_duplicate_audiences_and_strips_hidden_carriers() {
        $form_elements = $this->mailchimp_elements( 'yes', 'false', 2 );
        $form_id = $this->create_form( $form_elements );
        $output = $this->with_super_settings(
            array( 'mailchimp_key' => 'invalidkey' ),
            function() use ( $form_id ) {
                return $this->render_form( $form_id );
            }
        );
        $variant_names = $this->extract_mailchimp_variant_names( $output );
        $this->assertCount( 1, $variant_names );
        $data = $this->mailchimp_submission_data(
            $form_id,
            'Person@Example.test',
            'audience123',
            $variant_names,
            array( 'interest-new' )
        );
        list( $atts, $requests ) = $this->with_super_settings(
            array( 'mailchimp_key' => 'test-us1' ),
            function() use ( $form_id, $data ) {
                return $this->submit_mailchimp_and_capture_requests(
                    $form_id,
                    $data,
                    array(
                        'GET' => array(
                            'status' => 'subscribed',
                            'interests' => array(
                                'interest-old' => true,
                            ),
                        ),
                        'PATCH' => array(
                            'status' => 'subscribed',
                        ),
                    )
                );
            }
        );
        $this->assertCount( 2, $requests );
        $this->assertSame( 'GET', $requests[0]['method'] );
        $this->assertSame( 'PATCH', $requests[1]['method'] );
        $payload = json_decode( $requests[1]['body'], true );
        $this->assertIsArray( $payload );
        $this->assertSame( 'person@example.test', $payload['email_address'] );
        $this->assertSame(
            array(
                'interest-old' => false,
                'interest-new' => true,
            ),
            $payload['interests']
        );
        $this->assertArrayNotHasKey( 'mailchimp_interests', $atts['data'] );
        $this->assertArrayNotHasKey( 'mailchimp_list_id', $atts['data'] );
        $this->assertArrayNotHasKey( 'mailchimp_subscriber_status', $atts['data'] );
        $this->assertArrayNotHasKey( $variant_names[0], $atts['data'] );
    }
    public function test_mailchimp_missing_variant_skips_subscription_requests_after_public_render() {
        $form_elements = $this->mailchimp_elements( 'yes' );
        $form_id = $this->create_form( $form_elements );
        $output = $this->with_super_settings(
            array( 'mailchimp_key' => 'invalidkey' ),
            function() use ( $form_id ) {
                return $this->render_form( $form_id );
            }
        );
        $variant_names = $this->extract_mailchimp_variant_names( $output );
        $this->assertCount( 1, $variant_names );
        $data = $this->mailchimp_submission_data(
            $form_id,
            'person@example.test',
            'audience123',
            array(),
            array( 'interest-new' )
        );
        list( $atts, $requests ) = $this->with_super_settings(
            array( 'mailchimp_key' => 'test-us1' ),
            function() use ( $form_id, $data ) {
                return $this->submit_mailchimp_and_capture_requests(
                    $form_id,
                    $data,
                    array(
                        'GET' => array(
                            'status' => 'subscribed',
                            'interests' => array(
                                'interest-old' => true,
                            ),
                        ),
                    )
                );
            }
        );
        $this->assertSame( array(), $requests );
        $this->assertArrayNotHasKey( 'mailchimp_interests', $atts['data'] );
        $this->assertArrayNotHasKey( 'mailchimp_list_id', $atts['data'] );
        $this->assertArrayNotHasKey( 'mailchimp_subscriber_status', $atts['data'] );
    }
    public function test_mailchimp_conflicting_duplicate_audiences_fail_closed_before_subscription_requests() {
        $form_elements = $this->conflicting_mailchimp_elements();
        $form_id = $this->create_form( $form_elements );
        $output = $this->with_super_settings(
            array( 'mailchimp_key' => 'invalidkey' ),
            function() use ( $form_id ) {
                return $this->render_form( $form_id );
            }
        );
        $variant_names = $this->extract_mailchimp_variant_names( $output );
        sort( $variant_names );
        $this->assertCount( 2, $variant_names );
        $data = $this->mailchimp_submission_data(
            $form_id,
            'person@example.test',
            'audience123',
            $variant_names
        );
        $requests_file = tempnam( sys_get_temp_dir(), 'sf-mailchimp-requests-' );
        $this->assertNotFalse( $requests_file );
        $result = $this->with_super_settings(
            array( 'mailchimp_key' => 'test-us1' ),
            function() use ( $form_id, $data, $requests_file ) {
                return $this->run_dying_callback(
                    function() use ( $form_id, $data, $requests_file ) {
                        add_filter(
                            'pre_http_request',
                            function( $preempt, $args, $url ) use ( $requests_file ) {
                                file_put_contents(
                                    $requests_file,
                                    wp_json_encode(
                                        array(
                                            'method' => isset($args['method']) ? $args['method'] : 'GET',
                                            'url' => $url,
                                        )
                                    ) . "\n",
                                    FILE_APPEND | LOCK_EX
                                );
                                return new WP_Error( 'blocked_http', 'blocked' );
                            },
                            10,
                            3
                        );
                        $callback = array( SUPER_Mailchimp(), 'update_mailchimp_subscribers' );
                        if( !has_action( 'super_before_sending_email_hook', $callback ) ) {
                            add_action( 'super_before_sending_email_hook', $callback, 10, 1 );
                        }
                        $this->set_submission_request( $form_id, $data );
                        SUPER_Ajax::submit_form_checks( null, false );
                    }
                );
            }
        );
        $logged_requests = file_get_contents( $requests_file );
        unlink( $requests_file );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded );
        $this->assertTrue( $decoded['error'] );
        $this->assertStringContainsString( 'Invalid form data.', $decoded['msg'] );
        $this->assertSame( '', $logged_requests===false ? '' : $logged_requests );
    }
    public function test_mailchimp_variant_binding_normalizes_the_default_subscriber_status() {
        $form_elements = $this->mailchimp_elements( 'yes' );
        unset( $form_elements[1]['data']['subscriber_status'] );
        $form_id = $this->create_form( $form_elements );
        $output = $this->with_super_settings(
            array( 'mailchimp_key' => 'invalidkey' ),
            function() use ( $form_id ) {
                return $this->render_form( $form_id );
            }
        );
        $variant_names = $this->extract_mailchimp_variant_names( $output );
        $this->assertCount( 1, $variant_names );
        $data = $this->mailchimp_submission_data(
            $form_id,
            'person@example.test',
            'audience123',
            $variant_names,
            array( 'interest-1' )
        );
        list( $atts, $requests ) = $this->with_super_settings(
            array( 'mailchimp_key' => 'test-us1' ),
            function() use ( $form_id, $data ) {
                return $this->submit_mailchimp_and_capture_requests(
                    $form_id,
                    $data,
                    array(
                        'GET' => array(
                            'status' => 'subscribed',
                            'interests' => array(),
                        ),
                        'PATCH' => array(
                            'status' => 'subscribed',
                        ),
                    )
                );
            }
        );
        $this->assertCount( 2, $requests );
        $payload = json_decode( $requests[1]['body'], true );
        $this->assertSame( 'subscribed', $payload['status'] );
        $this->assertTrue( $payload['interests']['interest-1'] );
        $this->assertArrayNotHasKey( $variant_names[0], $atts['data'] );
    }

    public function test_dropdown_and_checkbox_length_limits_count_selected_options() {
        // Every choice used below is a genuinely rendered option, so the rejections
        // are driven purely by the selected-option COUNT and not by an unknown-choice
        // refusal (a dropdown with no stored items legitimately admits nothing:
        // class-ajax.php:3873-3875 + 4481-4483).
        $elements = array(
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'choices',
                    'minlength' => '2',
                    'maxlength' => '2',
                    'dropdown_items' => array(
                        array( 'value' => 'alpha', 'label' => 'Alpha' ),
                        array( 'value' => 'beta', 'label' => 'Beta' ),
                        array( 'value' => 'alphabet', 'label' => 'Alphabet' ),
                    ),
                ),
            ),
            array(
                'tag' => 'checkbox',
                'data' => array(
                    'name' => 'checks',
                    'minlength' => '1',
                    'maxlength' => '2',
                    'checkbox_items' => array(
                        array( 'value' => 'one', 'label' => 'One' ),
                        array( 'value' => 'two', 'label' => 'Two' ),
                        array( 'value' => 'three', 'label' => 'Three' ),
                    ),
                ),
            ),
        );
        $data = array(
            'choices' => array(
                'name' => 'choices',
                'value' => 'alpha,beta',
                'type' => 'var',
            ),
            'checks' => array(
                'name' => 'checks',
                'value' => 'one,two',
                'type' => 'var',
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
        $this->assertTrue( $this->validate( $data, $elements ) );

        $too_few = $data;
        $too_few['choices']['value'] = 'alphabet';
        $this->assertFalse( $this->validate( $too_few, $elements ) );

        $too_many = $data;
        $too_many['checks']['value'] = 'one,two,three';
        $this->assertFalse( $this->validate( $too_many, $elements ) );
    }
    public function test_selection_carriers_require_exact_slugs_and_support_explicit_selected_values_for_comma_containing_choices() {
        $elements = array(
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'choices',
                    'dropdown_items' => array(
                        array( 'value' => 'Doe, John', 'label' => 'Doe, John' ),
                        array( 'value' => 'Doe', 'label' => 'Doe' ),
                        array( 'value' => 'John', 'label' => 'John' ),
                    ),
                ),
            ),
            array(
                'tag' => 'radio',
                'data' => array(
                    'name' => 'role',
                    'radio_items' => array(
                        array( 'value' => 'author', 'label' => 'Author' ),
                        array( 'value' => 'subscriber', 'label' => 'Subscriber' ),
                    ),
                ),
            ),
        );
        $data = array(
            'choices' => array(
                'name' => 'choices',
                'value' => 'Doe, John, Doe',
                'selected_values' => array( 'Doe, John', 'Doe' ),
                'type' => 'var',
            ),
            'role' => array(
                'name' => 'role',
                'value' => 'author',
                'selected_values' => array( 'author' ),
                'type' => 'var',
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
        $this->assertTrue( $this->validate( $data, $elements ) );

        $ambiguous = $data;
        unset( $ambiguous['choices']['selected_values'] );
        $this->assertFalse( $this->validate( $ambiguous, $elements ) );

        $forged_radio = $data;
        $forged_radio['role']['value'] = 'author;forged-label';
        $forged_radio['role']['selected_values'] = array( 'author;forged-label' );
        $this->assertFalse( $this->validate( $forged_radio, $elements ) );
    }
    public function test_selection_choice_values_preserve_exact_whitespace_bytes() {
        $elements = array(
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'choices',
                    'dropdown_items' => array(
                        array( 'value' => '  keep  ', 'label' => 'Keep spacing' ),
                        array( 'value' => 'plain', 'label' => 'Plain' ),
                    ),
                ),
            ),
        );
        $data = array(
            'choices' => array(
                'name' => 'choices',
                'value' => '  keep  ',
                'selected_values' => array( '  keep  ' ),
                'type' => 'var',
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
        $this->assertTrue( $this->validate( $data, $elements ) );

        $trimmed = $data;
        $trimmed['choices']['value'] = 'keep';
        $trimmed['choices']['selected_values'] = array( 'keep' );
        $this->assertFalse( $this->validate( $trimmed, $elements ) );
    }
    public function test_duplicate_valued_selection_options_accept_the_rendered_value() {
        $elements = array(
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'choices',
                    'dropdown_items' => array(
                        array( 'value' => 'member', 'label' => 'Member' ),
                        array( 'value' => 'member', 'label' => 'Duplicate member' ),
                    ),
                    'admin_email_value' => 'both',
                    'confirm_email_value' => 'label',
                    'contact_entry_value' => 'both',
                ),
            ),
        );
        $data = array(
            'choices' => array(
                'name' => 'choices',
                'value' => 'member',
                'selected_values' => array( 'member' ),
                'type' => 'var',
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
        $this->assertTrue( $this->validate( $data, $elements ) );
        $rebuilt = $this->rebuild_selection_entry_data( $data, $elements );
        $this->assertSame( 'Member', $rebuilt['choices']['option_label'] );
    }

    public function test_runtime_rendered_selection_choices_use_the_server_renderer_allowlist() {
        $term = wp_insert_term( 'Rendered choice', 'category', array( 'slug' => 'rendered-choice' ) );
        $this->assertFalse( is_wp_error( $term ) );
        $elements = array(
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'choices',
                    'retrieve_method' => 'taxonomy',
                    'retrieve_method_taxonomy' => 'category',
                    'retrieve_method_value' => 'slug',
                ),
            ),
        );
        $data = array(
            'choices' => array(
                'name' => 'choices',
                'value' => 'rendered-choice',
                'selected_values' => array( 'rendered-choice' ),
                'type' => 'var',
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
        $this->assertTrue( $this->validate( $data, $elements ) );

        $forged = $data;
        $forged['choices']['value'] = 'missing-choice';
        $forged['choices']['selected_values'] = array( 'missing-choice' );
        $this->assertFalse( $this->validate( $forged, $elements ) );
    }
    public function test_countries_fields_use_runtime_selection_choices_and_allow_empty_optional_values() {
        $filter = static function( $countries ) {
            return array(
                'NL' => 'Netherlands',
                'US' => 'United States',
            );
        };
        add_filter( 'super_countries_list_filter', $filter, 10, 1 );
        try {
            $elements = array(
                array(
                    'tag' => 'countries',
                    'data' => array(
                        'name' => 'billing_country',
                    ),
                ),
            );
            $data = array(
                'billing_country' => array(
                    'name' => 'billing_country',
                    'value' => 'NL',
                    'selected_values' => array( 'NL' ),
                    'type' => 'var',
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
            $this->assertTrue( $this->validate( $data, $elements ) );

            $empty = $data;
            $empty['billing_country']['value'] = '';
            $empty['billing_country']['selected_values'] = array();
            $this->assertTrue( $this->validate( $empty, $elements ) );

            $forged = $data;
            $forged['billing_country']['value'] = 'DE';
            $forged['billing_country']['selected_values'] = array( 'DE' );
            $this->assertFalse( $this->validate( $forged, $elements ) );
        } finally {
            remove_filter( 'super_countries_list_filter', $filter, 10 );
        }
    }

    public function test_duplicate_choice_items_dedupe_by_value_and_keep_presentation_server_owned() {
        $elements = array(
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'choices',
                    'dropdown_items' => array(
                        array( 'value' => 'member', 'label' => 'Member' ),
                        array( 'value' => 'member', 'label' => 'Member' ),
                    ),
                ),
            ),
        );
        $valid = array(
            'choices' => array(
                'name' => 'choices',
                'value' => 'member',
                'selected_values' => array( 'member' ),
                'type' => 'var',
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
        $this->assertTrue( $this->validate( $valid, $elements ) );

        // A second item repeating the same VALUE with a different label is deduped
        // by value, first label wins (class-ajax.php:3709-3724): the option really
        // was rendered, so the carrier stays admissible...
        $conflicting = $elements;
        $conflicting[0]['data']['dropdown_items'][1]['label'] = 'Conflicting';
        $empty = $valid;
        $empty['choices']['value'] = '';
        $empty['choices']['selected_values'] = array();
        $this->assertTrue( $this->validate( $empty, $conflicting ) );
        $this->assertTrue( $this->validate( $valid, $conflicting ) );

        // ...while a value that was never rendered is still refused,
        $forged = $valid;
        $forged['choices']['value'] = 'administrator';
        $forged['choices']['selected_values'] = array( 'administrator' );
        $this->assertFalse( $this->validate( $forged, $conflicting ) );

        // and every presentation key stays server-owned: the client-sent
        // selected_values never survive into the stored entry data
        // (class-ajax.php:7338-7354).
        $client_presentation = $valid;
        $client_presentation['choices']['label'] = 'Forged label';
        $rebuilt = $this->rebuild_selection_entry_data( $client_presentation, $conflicting );
        $this->assertIsArray( $rebuilt );
        $this->assertArrayNotHasKey( 'selected_values', $rebuilt['choices'] );
        $this->assertNotSame( 'Forged label', $rebuilt['choices']['label'] );
    }
    public function test_duplicate_selection_names_rebuild_server_owned_presentation_only_when_every_match_agrees() {
        $elements = array(
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'choices',
                    'label' => 'Membership',
                    'dropdown_items' => array(
                        array( 'value' => 'member', 'label' => 'Member' ),
                    ),
                    'admin_email_value' => 'both',
                    'confirm_email_value' => 'label',
                    'contact_entry_value' => 'both',
                ),
            ),
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'choices',
                    'label' => 'Membership',
                    'dropdown_items' => array(
                        array( 'value' => 'member', 'label' => 'Member' ),
                    ),
                    'admin_email_value' => 'both',
                    'confirm_email_value' => 'label',
                    'contact_entry_value' => 'both',
                ),
            ),
        );
        $data = array(
            'choices' => array(
                'name' => 'choices',
                'value' => 'member',
                'label' => 'Forged label',
                'option_label' => 'Forged option',
                'admin_value' => 'Forged admin',
                'confirm_value' => 'Forged confirm',
                'entry_value' => 'Forged entry',
                'selected_values' => array( 'member' ),
                'type' => 'var',
            ),
        );
        $rebuilt = $this->rebuild_selection_entry_data( $data, $elements );
        $this->assertSame( 'Membership', $rebuilt['choices']['label'] );
        $this->assertSame( 'Member', $rebuilt['choices']['option_label'] );
        $this->assertSame( 'Member (member)', $rebuilt['choices']['admin_value'] );
        $this->assertSame( 'Member', $rebuilt['choices']['confirm_value'] );
        $this->assertSame( 'Member (member)', $rebuilt['choices']['entry_value'] );
        $this->assertArrayNotHasKey( 'selected_values', $rebuilt['choices'] );

        $conflicting = $elements;
        $conflicting[1]['data']['dropdown_items'][0]['label'] = 'Conflicting';
        $stripped = $this->rebuild_selection_entry_data( $data, $conflicting );
        $this->assertSame( 'member', $stripped['choices']['value'] );
        $this->assertArrayNotHasKey( 'label', $stripped['choices'] );
        $this->assertArrayNotHasKey( 'option_label', $stripped['choices'] );
        $this->assertArrayNotHasKey( 'admin_value', $stripped['choices'] );
        $this->assertArrayNotHasKey( 'confirm_value', $stripped['choices'] );
        $this->assertArrayNotHasKey( 'entry_value', $stripped['choices'] );
        $this->assertArrayNotHasKey( 'selected_values', $stripped['choices'] );
    }
    public function test_date_timestamps_are_rebuilt_from_the_stored_format_and_stripped_when_unparseable() {
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
        $this->assertArrayNotHasKey( 'timestamp', $stripped['appointment'] );

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
        $this->assertArrayNotHasKey( 'timestamp', $unsupported['unsupported_appointment'] );
    }


    public function test_custom_regex_empty_pattern_is_a_noop_while_malformed_patterns_fail_closed() {
        $elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'nickname',
                    'validation' => 'custom',
                    'custom_regex' => '',
                ),
            ),
        );
        $data = array(
            'nickname' => array(
                'name' => 'nickname',
                'value' => "O'Reilly\\Docs",
                'type' => 'var',
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
        $this->assertTrue( $this->validate( $data, $elements ) );

        $malformed = $elements;
        $malformed[0]['data']['custom_regex'] = '[';
        $this->assertFalse( $this->validate( $data, $malformed ) );
    }
    public function test_repeater_group_name_skips_builtin_unnamed_html_before_the_first_named_field() {
        $elements = array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    array(
                        'tag' => 'html',
                        'data' => array( 'html' => 'Intro text' ),
                    ),
                    array(
                        'tag' => 'text',
                        'data' => array( 'name' => 'guest_name', 'validation' => 'none' ),
                    ),
                ),
            ),
        );
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
                    ),
                ),
            ),
        );
        $data['guest_name'] = $data['_super_dynamic_data']['guest_name'][0]['guest_name'];
        $this->assertTrue( $this->validate( $data, $elements ) );
    }
    public function test_repeater_group_name_skips_nameless_custom_leaf_before_the_first_named_payload() {
        $elements = array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    array(
                        'tag' => 'custom_leaf_without_carrier',
                        'data' => array(),
                    ),
                    array(
                        'tag' => 'text',
                        'data' => array(
                            'name' => 'guest_name',
                        ),
                    ),
                ),
            ),
        );
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
                    ),
                ),
            ),
        );
        $data['guest_name'] = $data['_super_dynamic_data']['guest_name'][0]['guest_name'];
        $this->assertTrue( $this->validate( $data, $elements ) );
    }
    public function test_repeater_required_validation_follows_the_actual_dynamic_row_route() {
        $elements = array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    array(
                        'tag' => 'text',
                        'data' => array(
                            'name' => 'guest_email',
                            'validation' => 'email',
                            'may_be_empty' => 'false',
                        ),
                    ),
                ),
            ),
        );
        $valid = array(
            'guest_email' => array(
                'name' => 'guest_email',
                'value' => 'ada@example.test',
                'type' => 'var',
            ),
            'guest_email_2' => array(
                'name' => 'guest_email_2',
                'value' => 'grace@example.test',
                'type' => 'var',
            ),
            '_super_dynamic_data' => array(
                'guest_email' => array(
                    array(
                        'guest_email' => array(
                            'name' => 'guest_email',
                            'value' => 'ada@example.test',
                            'type' => 'var',
                        ),
                    ),
                    array(
                        'guest_email_2' => array(
                            'name' => 'guest_email_2',
                            'value' => 'grace@example.test',
                            'type' => 'var',
                        ),
                    ),
                ),
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
        $this->assertTrue( $this->validate( $valid, $elements ) );
        $this->assertTrue( $this->repeater_required_values_valid( $valid, $elements ) );

        // An emptied row value keeps a well-formed carrier shape, so the contract
        // still matches; the row-level required rule is what must refuse it.
        $missing = $valid;
        $missing['_super_dynamic_data']['guest_email'][1]['guest_email_2']['value'] = '';
        $missing['guest_email_2']['value'] = '';
        $this->assertTrue( $this->validate( $missing, $elements ) );
        $this->assertFalse( $this->repeater_required_values_valid( $missing, $elements ) );
        $this->assertSame( 'ada@example.test', $valid['_super_dynamic_data']['guest_email'][0]['guest_email']['value'] );
    }
    public function test_repeater_required_validation_matches_the_exact_stored_route_name() {
        $elements = array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    array(
                        'tag' => 'text',
                        'data' => array(
                            'name' => 'part_1',
                            'validation' => 'email',
                            'may_be_empty' => 'false',
                        ),
                    ),
                    array(
                        'tag' => 'text',
                        'data' => array(
                            'name' => 'part_2',
                            'validation' => 'email',
                            'may_be_empty' => 'false',
                        ),
                    ),
                ),
            ),
        );
        $valid = array(
            'part_1' => array(
                'name' => 'part_1',
                'value' => 'left@example.test',
                'type' => 'var',
            ),
            'part_2' => array(
                'name' => 'part_2',
                'value' => 'right@example.test',
                'type' => 'var',
            ),
            '_super_dynamic_data' => array(
                'part_1' => array(
                    array(
                        'part_1' => array(
                            'name' => 'part_1',
                            'value' => 'left@example.test',
                            'type' => 'var',
                        ),
                        'part_2' => array(
                            'name' => 'part_2',
                            'value' => 'right@example.test',
                            'type' => 'var',
                        ),
                    ),
                ),
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
        $this->assertTrue( $this->validate( $valid, $elements ) );
        $this->assertTrue( $this->repeater_required_values_valid( $valid, $elements ) );
        $missing = $valid;
        $missing['part_1']['value'] = '';
        $missing['_super_dynamic_data']['part_1'][0]['part_1']['value'] = '';
        $this->assertTrue( $this->validate( $missing, $elements ) );
        $this->assertFalse( $this->repeater_required_values_valid( $missing, $elements ) );
    }
    public function test_required_file_routes_inside_multipart_steps_still_require_a_present_carrier() {
        $elements = array(
            array(
                'tag' => 'multipart',
                'inner' => array(
                    array(
                        'tag' => 'file',
                        'data' => array(
                            'name' => 'passport',
                            'extensions' => 'pdf',
                            'minlength' => '1',
                            'maxlength' => '1',
                        ),
                    ),
                ),
            ),
        );
        $valid = array(
            'passport' => array(
                'type' => 'files',
                'files' => array(
                    array(
                        'name' => 'passport',
                        'value' => 'passport.pdf',
                        'url' => 'https://example.test/passport.pdf',
                        'type' => 'application/pdf',
                        'size' => 128,
                    ),
                ),
            ),
        );
        $this->assertTrue( $this->files_match_stored_policy( $valid, $elements ) );
        unset( $valid['passport'] );
        $this->assertFalse( $this->files_match_stored_policy( $valid, $elements ) );
    }



    public function test_production_repeater_carriers_match_the_stored_repeater_structure() {
        $this->assertTrue( $this->validate( $this->production_repeater_data(), $this->repeater_elements() ) );
    }
    public function test_nested_repeater_descendant_carriers_accept_only_numeric_bracket_suffixes_from_the_stored_topology() {
        $data = $this->nested_repeater_data();
        $this->assertTrue( $this->validate( $data, $this->nested_repeater_elements() ) );

        $malformed = $data;
        $malformed['_super_dynamic_data']['guest_name'][0]['guest_note[label]'] = $malformed['_super_dynamic_data']['guest_name'][0]['guest_note[0]'];
        unset( $malformed['_super_dynamic_data']['guest_name'][0]['guest_note[0]'] );
        $this->assertFalse( $this->validate( $malformed, $this->nested_repeater_elements() ) );

        $unknown = $data;
        $unknown['_super_dynamic_data']['guest_name'][0]['forged_note[0]'] = $unknown['_super_dynamic_data']['guest_name'][0]['guest_note[0]'];
        unset( $unknown['_super_dynamic_data']['guest_name'][0]['guest_note[0]'] );
        $this->assertFalse( $this->validate( $unknown, $this->nested_repeater_elements() ) );
    }
    public function test_duplicate_row_aliases_and_repeated_file_routes_must_match_the_validated_row_payload() {
        $data = $this->production_repeater_data();
        $data['_super_dynamic_data']['guest_name'][1]['guest_document_2'] = array(
            'field_name' => 'guest_document',
            'type' => 'files',
            'files' => array(
                array(
                    'name' => 'guest_document_2',
                    'value' => 'passport.pdf',
                    'url' => 'https://example.test/passport.pdf',
                    'type' => 'application/pdf',
                ),
            ),
        );
        $data['guest_document_2'] = $data['_super_dynamic_data']['guest_name'][1]['guest_document_2'];
        $this->assertTrue( $this->validate( $data, $this->repeater_elements() ) );

        $conflict = $data;
        $conflict['guest_name']['value'] = 'Forged first row';
        $this->assertFalse( $this->validate( $conflict, $this->repeater_elements() ) );

        $conflict = $data;
        $conflict['guest_name_2']['value'] = 'Tampered';
        $this->assertFalse( $this->validate( $conflict, $this->repeater_elements() ) );

        $missing_identity = $data;
        unset( $missing_identity['guest_document_2']['field_name'] );
        $this->assertFalse( $this->validate( $missing_identity, $this->repeater_elements() ) );

        $forged_alias = $data;
        $forged_alias['guest_name_9'] = $forged_alias['guest_name_2'];
        unset( $forged_alias['guest_name_2'] );
        $this->assertFalse( $this->validate( $forged_alias, $this->repeater_elements() ) );
    }


    public function test_unknown_or_malformed_repeater_carriers_fail_the_stored_contract() {
        $valid = $this->production_repeater_data();
        $forgeries = array();

        $candidate = $valid;
        $candidate['_super_dynamic_data']['forged_group'] = $candidate['_super_dynamic_data']['guest_name'];
        $forgeries['unknown repeater group'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name'][0]['forged_field'] = array(
            'name' => 'forged_field',
            'value' => 'forged',
            'type' => 'var',
        );
        $forgeries['unknown nested field'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name'][0]['guest_name']['value'] = array( 'Ada' );
        $forgeries['nested scalar array'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name'][0]['guest_document']['files'] = 'forged';
        $forgeries['nested file shape'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name'][0]['guest_name']['type'] = 'files';
        $forgeries['nested carrier type'] = $candidate;
        $candidate = $valid;
        $candidate['_super_dynamic_data']['guest_name']['row'] = $candidate['_super_dynamic_data']['guest_name'][0];
        $forgeries['non-indexed rows'] = $candidate;

        foreach ( $forgeries as $label => $candidate ) {
            $this->assertFalse( $this->validate( $candidate, $this->repeater_elements() ), $label );
        }
    }
    /**
     * #114 gap-1 regression: a required, client-validated field inside a multipart
     * step must stay presence-enforced server-side. A multipart step is always part of
     * a final browser submit, so -- unlike conditional / repeater / mobile-hidden
     * subtrees -- its fields cannot legitimately be absent. A full submit_form() POST
     * that omits the required step-2 field must be rejected by the required-field layer
     * and create no entry, while the complete POST still succeeds and persists one entry.
     */
    public function test_required_field_inside_a_multipart_step_is_presence_enforced_on_full_submit() {
        $elements = array(
            array(
                'tag' => 'multipart',
                'inner' => array(
                    array(
                        'tag' => 'text',
                        'data' => array(
                            'name' => 'carrier',
                            'validation' => 'none',
                        ),
                    ),
                ),
            ),
            array(
                'tag' => 'multipart',
                'inner' => array(
                    array(
                        'tag' => 'text',
                        'data' => array(
                            'name' => 'required_step2',
                            'validation' => 'email',
                            'may_be_empty' => 'false',
                        ),
                    ),
                ),
            ),
        );
        $form_id = $this->create_form(
            $elements,
            array(
                'save_contact_entry' => 'yes',
                'send' => 'no',
                'confirm' => 'no',
                'form_thanks_title' => '',
                'form_thanks_description' => '',
                'form_show_thanks_msg' => '',
                'form_redirect_option' => '',
            )
        );
        $complete = array(
            'carrier' => array( 'name' => 'carrier', 'value' => 'step one', 'type' => 'var' ),
            'required_step2' => array( 'name' => 'required_step2', 'value' => 'step2@example.test', 'type' => 'var' ),
        );

        // A direct POST omitting the required step-2 field is rejected and creates no entry.
        $before_ids = $this->entry_ids_for( $form_id );
        $incomplete = $complete;
        unset( $incomplete['required_step2'] );
        $rejected = $this->with_super_settings(
            array( 'csrf_check' => 'false' ),
            function() use ( $form_id, $incomplete ) {
                $this->set_submission_request(
                    $form_id,
                    $incomplete,
                    array( 'action' => 'super_submit_form', 'i18n' => '' )
                );
                return $this->run_dying_callback( function() {
                    SUPER_Ajax::submit_form();
                } );
            }
        );
        $this->assertSame( 0, $rejected['status'], $rejected['output'] );
        $rejected_decoded = json_decode( $rejected['output'], true );
        $this->assertIsArray( $rejected_decoded, $rejected['output'] );
        $this->assertTrue( $rejected_decoded['error'], $rejected['output'] );
        $this->assertStringContainsString(
            'required',
            strtolower( wp_strip_all_tags( $rejected_decoded['msg'] ) ),
            $rejected['output']
        );
        $this->assertSame( $before_ids, $this->entry_ids_for( $form_id ) );

        // The complete POST still succeeds and persists exactly one new entry.
        $result = $this->with_super_settings(
            array( 'csrf_check' => 'false' ),
            function() use ( $form_id, $complete ) {
                $this->set_submission_request(
                    $form_id,
                    $complete,
                    array( 'action' => 'super_submit_form', 'i18n' => '' )
                );
                return $this->run_dying_callback( function() {
                    SUPER_Ajax::submit_form();
                } );
            }
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );
        $new_entry_id = (int) $decoded['response_data']['contact_entry_id'];
        $this->assertGreaterThan( 0, $new_entry_id );
        $after_ids = $this->entry_ids_for( $form_id );
        $expected_ids = array_merge( $before_ids, array( $new_entry_id ) );
        sort( $expected_ids );
        sort( $after_ids );
        $this->assertSame( $expected_ids, $after_ids );
    }
    /**
     * Customer regression: an extension (calculator/hidden) that redeclares a rendered
     * dropdown field name with conflicting normalized metadata must not poison the
     * rendered field's contract entry. The rendered field-collector entry stays
     * authoritative, so a complete browser-shaped submission of the real form is
     * accepted. A carrier for a name that was never rendered stays rejected.
     */
    public function test_extension_redeclaration_of_a_rendered_dropdown_keeps_the_rendered_contract_authoritative() {
        $elements = array(
            array(
                'tag' => 'dropdown',
                'data' => array(
                    'name' => 'plan',
                    'dropdown_items' => array(
                        array( 'value' => 'basic', 'label' => 'Basic' ),
                        array( 'value' => 'pro', 'label' => 'Pro' ),
                    ),
                ),
            ),
        );
        // Simulate the calculator/hidden extension redeclaring the rendered dropdown
        // with conflicting normalized metadata. Before the fix this poisoned 'plan' and
        // rejected every browser-shaped submission with "Invalid form data.".
        $filter = static function( $extra, $context ) {
            if( isset( $context['tag'] ) && $context['tag']==='dropdown' ) {
                $extra['plan'] = array(
                    'type' => 'var',
                    'validation' => 'numeric',
                    'length_mode' => 'text',
                    'enforce_choice_values' => false,
                );
            }
            return $extra;
        };
        add_filter( 'super_submission_carrier_contracts_filter', $filter, 10, 2 );
        try {
            $data = array(
                'plan' => array(
                    'name' => 'plan',
                    'value' => 'pro',
                    'selected_values' => array( 'pro' ),
                    'type' => 'var',
                ),
                'hidden_form_id' => array( 'name' => 'hidden_form_id', 'value' => '41', 'type' => 'form_id' ),
                'hidden_contact_entry_id' => array( 'name' => 'hidden_contact_entry_id', 'value' => '', 'type' => 'entry_id' ),
            );
            $this->assertTrue( $this->validate( $data, $elements ) );

            // A carrier for a name that was never rendered (and never declared by any
            // extension) must still be rejected.
            $forged = $data;
            $forged['forged_field'] = array( 'name' => 'forged_field', 'value' => 'x', 'type' => 'var' );
            $this->assertFalse( $this->validate( $forged, $elements ) );
        } finally {
            remove_filter( 'super_submission_carrier_contracts_filter', $filter, 10 );
        }
    }
}
