<?php
/**
 * Entry-access and render-grant proof package for rows 5, 16, and 19.
 *
 * Row 5  - a render-issued non-Listings update grant authorizes one exact update
 *          and rejects replay or cross-actor/session reuse without mutation.
 * Row 16 - a headers-sent child still round-trips persistent client data and
 *          preserves the fail-closed no-session boundary.
 * Row 19 - a render-issued retrieve-last-entry grant survives the intended
 *          refresh window, then fails closed after expiry or actor/session
 *          supersession; sf_nonce remains absolutely expiring.
 *
 * @package Super_Forms_Tests
 */

require_once __DIR__ . '/test-security-upload-00-base.php';

/**
 * Row 5.
 */
class Test_Super_Forms_Proof_Row5_EntryAccess extends Super_Forms_Upload_Security_Test_Case {

    private $extra_session_ids = array();
    private $extra_user_sessions = array();

    public function tear_down() {
        foreach( array_unique( $this->extra_session_ids ) as $session_id ) {
            delete_option( '_sfsdata_' . $session_id );
        }
        foreach( $this->extra_user_sessions as $pair ) {
            list( $user_id, $token ) = $pair;
            WP_Session_Tokens::get_instance( $user_id )->destroy( $token );
        }
        unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
        wp_set_current_user( 0 );
        parent::tear_down();
    }

    private function log_in_as( $user_id ) {
        $expiration = time() + HOUR_IN_SECONDS;
        $token = WP_Session_Tokens::get_instance( $user_id )->create( $expiration );
        $_COOKIE[ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );
        wp_set_current_user( $user_id );
        $this->extra_user_sessions[] = array( $user_id, $token );
        return $token;
    }

    /**
     * Mirrors the shared submit helper but keeps this row's byte-preserving
     * request setup local to the fixture. The payload is pre-slashed once so
     * submit_form_checks()'s wp_unslash() lands on the exact JSON a real
     * browser request would deliver after WordPress bootstrap.
     */
    private function set_realistic_submit_request( $form_id, $data, $extra_post = array() ) {
        if( !isset( $data['hidden_form_id'] ) ) {
            $data['hidden_form_id'] = array(
                'name' => 'hidden_form_id',
                'value' => (string) $form_id,
                'type' => 'form_id',
            );
        }
        if( !isset( $data['hidden_contact_entry_id'] ) ) {
            $data['hidden_contact_entry_id'] = array(
                'name' => 'hidden_contact_entry_id',
                'value' => isset( $extra_post['entry_id'] ) && absint( $extra_post['entry_id'] )
                    ? (string) absint( $extra_post['entry_id'] )
                    : '',
                'type' => 'entry_id',
            );
        }
        $_POST = array_merge(
            array(
                'action' => 'super_submit_form',
                'i18n' => '',
                'form_id' => (string) $form_id,
                'data' => wp_slash( wp_json_encode( $data ) ),
            ),
            $extra_post
        );
        $_REQUEST = $_POST;
        $_FILES = array();
    }

    private function issue_render_grant( $form_id, $entry_id ) {
        $this->assertTrue( SUPER_Common::issue_entry_access_credential( get_post( $entry_id ) ) );
        $_GET = array( 'contact_entry_id' => (string) $entry_id );
        $output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
        $_GET = array();
        return $output;
    }

    public function test_render_issued_grant_authorizes_special_byte_update_and_rejects_replayed_wrong_context() {
        wp_set_current_user( 0 );
        unset( $_COOKIE['_sfs_id'] );

        $form_id = $this->create_form(
            'publish',
            array(
                array( 'tag' => 'text', 'data' => array( 'name' => 'favorite_color', 'label' => 'Favorite color' ) ),
            ),
            array( 'update_contact_entry' => 'true' )
        );
        $entry_id = self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'super_unread',
            'post_parent' => $form_id,
            'post_author' => 0,
        ) );
        SUPER_Data_Access::update_entry_data( $entry_id, array(
            'favorite_color' => array( 'name' => 'favorite_color', 'value' => 'before-render', 'type' => 'text' ),
        ) );

        $grant_name = 'update_contact_entry_' . $form_id . '_' . $entry_id;
        $this->assertFalse( SUPER_Common::getClientData( $grant_name ) );

        // Real render, consuming the single-use entry-access credential, issues
        // the exact non-Listings update grant for this anonymous browser session.
        $output = $this->issue_render_grant( $form_id, $entry_id );
        $this->assertArrayHasKey( '_sfs_id', $_COOKIE );
        $this->assertRenderedInputValue( $output, 'hidden_contact_entry_id', $entry_id );
        $authorized_session = $_COOKIE['_sfs_id'];
        $this->extra_session_ids[] = $authorized_session;
        $this->assertSame( SUPER_Common::current_entry_update_grant_value(), SUPER_Common::getClientData( $grant_name ) );

        $special_value = 'row5-' . chr( 39 ) . 'apostrophe' . chr( 92 ) . 'backslash' . chr( 34 ) . 'doublequote-' . wp_generate_uuid4();
        $this->assertStringContainsString( chr( 39 ), $special_value );
        $this->assertStringContainsString( chr( 92 ), $special_value );
        $this->assertStringContainsString( chr( 34 ), $special_value );

        $data = array(
            'favorite_color' => array( 'name' => 'favorite_color', 'value' => $special_value, 'type' => 'text' ),
        );
        $this->set_realistic_submit_request( $form_id, $data, array( 'entry_id' => (string) $entry_id ) );
        $submit = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $submit['status'], $submit['output'] );
        $decoded = json_decode( $submit['output'], true );
        $this->assertIsArray( $decoded, $submit['output'] );
        $this->assertFalse( $decoded['error'], $submit['output'] );
        $stored = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( $special_value, $stored['favorite_color']['value'] );

        // Replay: the grant is a day-long session credential, not single-use, so
        // an identical resubmission with the same authorized session succeeds
        // again but leaves the persisted bytes exactly as they already were.
        $_COOKIE['_sfs_id'] = $authorized_session;
        $this->set_realistic_submit_request( $form_id, $data, array( 'entry_id' => (string) $entry_id ) );
        $replay = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $replay['status'], $replay['output'] );
        $replay_decoded = json_decode( $replay['output'], true );
        $this->assertIsArray( $replay_decoded, $replay['output'] );
        $this->assertFalse( $replay_decoded['error'], $replay['output'] );
        $this->assertSame( $special_value, SUPER_Data_Access::get_entry_data( $entry_id )['favorite_color']['value'] );

        // Wrong session: a fresh anonymous browser session (run_dying_handler's
        // bootstrap mints one because none is present) never received the grant.
        unset( $_COOKIE['_sfs_id'] );
        $tampered_wrong_session = $special_value . '-wrong-session-tamper';
        $this->set_realistic_submit_request(
            $form_id,
            array( 'favorite_color' => array( 'name' => 'favorite_color', 'value' => $tampered_wrong_session, 'type' => 'text' ) ),
            array( 'entry_id' => (string) $entry_id )
        );
        $wrong_session = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $wrong_session['status'], $wrong_session['output'] );
        $wrong_session_decoded = json_decode( $wrong_session['output'], true );
        $this->assertIsArray( $wrong_session_decoded, $wrong_session['output'] );
        $this->assertTrue( $wrong_session_decoded['error'], $wrong_session['output'] );
        $this->assertStringContainsString(
            'permission to edit this entry',
            strtolower( wp_strip_all_tags( $wrong_session_decoded['msg'] ) )
        );
        $this->assertSame( $special_value, SUPER_Data_Access::get_entry_data( $entry_id )['favorite_color']['value'] );
        if( isset( $_COOKIE['_sfs_id'] ) && $_COOKIE['_sfs_id'] !== $authorized_session ) {
            $this->extra_session_ids[] = $_COOKIE['_sfs_id'];
        }

        // Wrong actor: same browser session, but a different logged-in user tries
        // to reuse the anonymous grant. actor_id and user_session_hash no longer match.
        $_COOKIE['_sfs_id'] = $authorized_session;
        $other_user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $this->log_in_as( $other_user_id );
        $tampered_wrong_actor = $special_value . '-wrong-actor-tamper';
        $this->set_realistic_submit_request(
            $form_id,
            array( 'favorite_color' => array( 'name' => 'favorite_color', 'value' => $tampered_wrong_actor, 'type' => 'text' ) ),
            array( 'entry_id' => (string) $entry_id )
        );
        $wrong_actor = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $wrong_actor['status'], $wrong_actor['output'] );
        $wrong_actor_decoded = json_decode( $wrong_actor['output'], true );
        $this->assertIsArray( $wrong_actor_decoded, $wrong_actor['output'] );
        $this->assertTrue( $wrong_actor_decoded['error'], $wrong_actor['output'] );
        $this->assertSame( $special_value, SUPER_Data_Access::get_entry_data( $entry_id )['favorite_color']['value'] );

        wp_set_current_user( 0 );
        $_COOKIE['_sfs_id'] = $authorized_session;
    }
}

/**
 * Row 16.
 */
class Test_Super_Forms_Proof_Row16_HeadersSent extends Super_Forms_Upload_Security_Test_Case {

    private $extra_session_ids = array();

    public function tear_down() {
        foreach( array_unique( $this->extra_session_ids ) as $session_id ) {
            delete_option( '_sfsdata_' . $session_id );
        }
        parent::tear_down();
    }

    /**
     * Forks a child that first pushes a real byte to the SAPI output layer and
     * flushes every buffering level so headers_sent() stays true for the rest
     * of the child's life. The durable contract here is warning-free client-data
     * persistence under that boundary; raw header observation remains HTTP-SAPI-only.
     */
    private function run_headers_sent_probe( $callback ) {
        $this->require_process_forking( 'The headers-sent client-data regression requires pcntl fork, wait, and exec support.' );
        $result_file = tempnam( sys_get_temp_dir(), 'sf-headers-sent-' );
        $this->assertNotFalse( $result_file );
        $pid = pcntl_fork();
        $this->assertNotSame( -1, $pid );

        if( $pid === 0 ) {
            $payload = array(
                'headers_sent_before' => null,
                'headers_sent_after' => null,
                'result' => null,
                'warnings' => array(),
                'error' => null,
            );
            $previous_error_handler = set_error_handler( static function( $severity, $message ) use ( &$payload, &$previous_error_handler ) {
                if( in_array( $severity, array( E_WARNING, E_NOTICE, E_USER_WARNING, E_USER_NOTICE ), true ) ) {
                    $payload['warnings'][] = $message;
                    return true;
                }
                if( is_callable( $previous_error_handler ) ) {
                    return (bool) call_user_func( $previous_error_handler, $severity, $message );
                }
                return false;
            } );
            register_shutdown_function( static function() use ( $result_file, &$payload ) {
                file_put_contents( $result_file, wp_json_encode( $payload ), LOCK_EX );
                while( ob_get_level() > 0 ) {
                    @ob_end_flush();
                }
                pcntl_exec( PHP_BINARY, array( '-r', 'exit(0);' ) );
            } );
            try {
                while( ob_get_level() > 0 ) {
                    @ob_end_flush();
                }
                echo 'x';
                flush();
                $payload['headers_sent_before'] = headers_sent();
                $payload['result'] = call_user_func( $callback );
                $payload['headers_sent_after'] = headers_sent();
            } catch( Throwable $e ) {
                $payload['error'] = get_class( $e ) . ': ' . $e->getMessage();
            }
            restore_error_handler();
            exit( 0 );
        }

        $status = 0;
        pcntl_waitpid( $pid, $status );
        global $wpdb;
        if( isset( $wpdb ) && method_exists( $wpdb, 'check_connection' ) ) {
            $wpdb->check_connection( false );
        }
        wp_cache_flush();

        $raw = file_get_contents( $result_file );
        unlink( $result_file );
        $decoded = json_decode( $raw, true );
        $this->assertIsArray( $decoded, (string) $raw );
        $this->assertNull( $decoded['error'], (string) $raw );
        $this->assertTrue( $decoded['headers_sent_before'], 'headers_sent() must already be true before the client-data call.' );
        $this->assertTrue( $decoded['headers_sent_after'], 'headers_sent() must remain true after the client-data call.' );
        $this->assertSame( array(), $decoded['warnings'], 'The client-data call must not emit a "headers already sent" warning: ' . wp_json_encode( $decoded['warnings'] ) );
        return $decoded;
    }

    public function test_headers_sent_round_trips_client_data_without_cookie_emission_and_preserves_stale_payload() {
        $session_id = substr( hash( 'sha256', 'row16-sent:' . wp_generate_uuid4() ), 0, 42 );
        $this->extra_session_ids[] = $session_id;
        $existing_value = 'existing-preexisting-' . wp_generate_uuid4();
        update_option(
            '_sfsdata_' . $session_id,
            array(
                'expires' => time() + HOUR_IN_SECONDS,
                'exp_var' => time() - 10,
                'existing_stale_key' => array(
                    'expires' => time() + HOUR_IN_SECONDS,
                    'exp_var' => time() - 10,
                    'value' => $existing_value,
                ),
            ),
            false
        );
        $_COOKIE['_sfs_id'] = $session_id;

        $probe_value = 'row16-persisted-' . wp_generate_uuid4();
        $result = $this->run_headers_sent_probe( function() use ( $probe_value, $session_id ) {
            $existing_before_setclientdata = SUPER_Common::getClientData( 'existing_stale_key' );
            SUPER_Common::setClientData( array( 'name' => 'proof_payload', 'value' => $probe_value ) );
            return array(
                'existing_before_setclientdata' => $existing_before_setclientdata,
                'round_trip' => SUPER_Common::getClientData( 'proof_payload' ),
                'stored' => get_option( '_sfsdata_' . $session_id ),
            );
        } );

        $this->assertSame( $existing_value, $result['result']['existing_before_setclientdata'] );
        $this->assertSame( $probe_value, $result['result']['round_trip'] );
        $stored = $result['result']['stored'];
        $this->assertIsArray( $stored );
        $this->assertSame( $probe_value, $stored['proof_payload']['value'] );
        $this->assertSame( $existing_value, $stored['existing_stale_key']['value'] );
        $this->assertGreaterThan( time(), $stored['existing_stale_key']['exp_var'] );
        $this->assertGreaterThan( time(), $stored['expires'] );
    }

    public function test_headers_sent_without_a_cookie_fails_closed_and_creates_no_session() {
        global $wpdb;
        unset( $_COOKIE['_sfs_id'] );
        $before = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like( '_sfsdata_' ) . '%'
        ) );

        $probe_value = 'row16-cookieless-' . wp_generate_uuid4();
        $result = $this->run_headers_sent_probe( function() use ( $probe_value ) {
            SUPER_Common::setClientData( array( 'name' => 'proof_payload', 'value' => $probe_value ) );
            return array(
                'cookie_present' => isset( $_COOKIE['_sfs_id'] ),
                'round_trip' => SUPER_Common::getClientData( 'proof_payload' ),
            );
        } );

        $this->assertFalse( $result['result']['cookie_present'], 'No session cookie may be minted once headers are already sent.' );
        $this->assertFalse( $result['result']['round_trip'], 'A cookieless, headers-already-sent write must fail closed rather than persist anywhere readable.' );
        $after = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like( '_sfsdata_' ) . '%'
        ) );
        $this->assertSame( $before, $after, 'No orphan _sfsdata_ session row may be created.' );
    }
}

/**
 * Row 19.
 */
class Test_Super_Forms_Proof_Row19_GrantBoundary extends Super_Forms_Upload_Security_Test_Case {

    private $extra_session_ids = array();
    private $extra_user_sessions = array();

    public function tear_down() {
        foreach( array_unique( $this->extra_session_ids ) as $session_id ) {
            delete_option( '_sfsdata_' . $session_id );
        }
        foreach( $this->extra_user_sessions as $pair ) {
            list( $user_id, $token ) = $pair;
            WP_Session_Tokens::get_instance( $user_id )->destroy( $token );
        }
        unset( $_COOKIE[ LOGGED_IN_COOKIE ] );
        wp_set_current_user( 0 );
        parent::tear_down();
    }

    private function log_in_as( $user_id ) {
        $expiration = time() + HOUR_IN_SECONDS;
        $token = WP_Session_Tokens::get_instance( $user_id )->create( $expiration );
        $_COOKIE[ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );
        wp_set_current_user( $user_id );
        $this->extra_user_sessions[] = array( $user_id, $token );
        return $_COOKIE[ LOGGED_IN_COOKIE ];
    }

    /**
     * Mirrors the shared submit helper so this grant-boundary fixture keeps
     * its byte-preserving request setup local to the row.
     */
    private function set_realistic_submit_request( $form_id, $data, $extra_post = array() ) {
        if( !isset( $data['hidden_form_id'] ) ) {
            $data['hidden_form_id'] = array(
                'name' => 'hidden_form_id',
                'value' => (string) $form_id,
                'type' => 'form_id',
            );
        }
        if( !isset( $data['hidden_contact_entry_id'] ) ) {
            $data['hidden_contact_entry_id'] = array(
                'name' => 'hidden_contact_entry_id',
                'value' => isset( $extra_post['entry_id'] ) && absint( $extra_post['entry_id'] )
                    ? (string) absint( $extra_post['entry_id'] )
                    : '',
                'type' => 'entry_id',
            );
        }
        $_POST = array_merge(
            array(
                'action' => 'super_submit_form',
                'i18n' => '',
                'form_id' => (string) $form_id,
                'data' => wp_slash( wp_json_encode( $data ) ),
            ),
            $extra_post
        );
        $_REQUEST = $_POST;
        $_FILES = array();
    }

    public function test_grant_survives_the_legacy_refresh_boundary_and_rejects_expired_wrong_context_and_superseded_replay() {
        $owner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $owner_logged_in_cookie = $this->log_in_as( $owner_id );

        $form_id = $this->create_form(
            'publish',
            array(
                array( 'tag' => 'text', 'data' => array( 'name' => 'favorite_color', 'label' => 'Favorite color' ) ),
            ),
            array(
                'update_contact_entry' => 'true',
                'retrieve_last_entry_data' => 'true',
            )
        );
        $entry_id = self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'super_unread',
            'post_parent' => $form_id,
            'post_author' => $owner_id,
        ) );
        SUPER_Data_Access::update_entry_data( $entry_id, array(
            'favorite_color' => array( 'name' => 'favorite_color', 'value' => 'before-render', 'type' => 'text' ),
        ) );

        // Real render, via the retrieve-last-entry config (not the entry-access
        // credential path Row 5 already covers), issues the exact update grant.
        $output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
        $this->assertRenderedInputValue( $output, 'hidden_contact_entry_id', $entry_id );
        $authorized_session = $_COOKIE['_sfs_id'];
        $this->extra_session_ids[] = $authorized_session;
        $grant_name = 'update_contact_entry_' . $form_id . '_' . $entry_id;
        $this->assertSame( SUPER_Common::current_entry_update_grant_value(), SUPER_Common::getClientData( $grant_name ) );

        // Advance past the legacy 20/30-minute boundary directly on the stored
        // session row: the exp_var refresh mark is put 35 minutes in the past
        // (crossing both the historical 20 and 30 minute marks), while the
        // fix's day-long `expires` floor is left completely untouched.
        $stored = get_option( '_sfsdata_' . $authorized_session );
        $this->assertGreaterThanOrEqual( time() + DAY_IN_SECONDS - 5, $stored[ $grant_name ]['expires'] );
        $stored[ $grant_name ]['exp_var'] = time() - ( 35 * MINUTE_IN_SECONDS );
        update_option( '_sfsdata_' . $authorized_session, $stored, false );

        $update_value_1 = 'row19-boundary-' . chr( 39 ) . 'quote' . chr( 92 ) . chr( 34 ) . wp_generate_uuid4();
        $this->set_realistic_submit_request(
            $form_id,
            array( 'favorite_color' => array( 'name' => 'favorite_color', 'value' => $update_value_1, 'type' => 'text' ) ),
            array( 'entry_id' => (string) $entry_id )
        );
        $submit = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $submit['status'], $submit['output'] );
        $decoded = json_decode( $submit['output'], true );
        $this->assertIsArray( $decoded, $submit['output'] );
        $this->assertFalse( $decoded['error'], $submit['output'] );
        $this->assertSame( $update_value_1, SUPER_Data_Access::get_entry_data( $entry_id )['favorite_color']['value'] );

        // The boundary-crossing authorized read refreshed (not revoked) the grant.
        $refreshed = get_option( '_sfsdata_' . $authorized_session );
        $this->assertGreaterThan( time(), $refreshed[ $grant_name ]['exp_var'] );

        // Negative: expired grant (absolute `expires` elapsed) -> zero mutation,
        // and the read purges rather than silently reviving it.
        $expired = get_option( '_sfsdata_' . $authorized_session );
        $expired[ $grant_name ]['expires'] = time() - 1;
        update_option( '_sfsdata_' . $authorized_session, $expired, false );
        $tamper_expired = $update_value_1 . '-expired-tamper';
        $this->set_realistic_submit_request(
            $form_id,
            array( 'favorite_color' => array( 'name' => 'favorite_color', 'value' => $tamper_expired, 'type' => 'text' ) ),
            array( 'entry_id' => (string) $entry_id )
        );
        $expired_result = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $expired_result['status'], $expired_result['output'] );
        $expired_decoded = json_decode( $expired_result['output'], true );
        $this->assertIsArray( $expired_decoded, $expired_result['output'] );
        $this->assertTrue( $expired_decoded['error'], $expired_result['output'] );
        $this->assertSame( $update_value_1, SUPER_Data_Access::get_entry_data( $entry_id )['favorite_color']['value'] );
        $this->assertFalse( SUPER_Common::getClientData( $grant_name ) );

        // Reissue a legitimate grant (same owner/session context) for the next negatives.
        SUPER_Common::setClientData( array(
            'name' => $grant_name,
            'value' => SUPER_Common::current_entry_update_grant_value(),
            'force' => true,
        ) );

        // Negative: wrong actor/session -- a different logged-in user, same
        // browser session, cannot reuse the owner's grant -> zero mutation.
        $other_owner = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $this->log_in_as( $other_owner );
        $tamper_actor = $update_value_1 . '-wrong-actor-tamper';
        $this->set_realistic_submit_request(
            $form_id,
            array( 'favorite_color' => array( 'name' => 'favorite_color', 'value' => $tamper_actor, 'type' => 'text' ) ),
            array( 'entry_id' => (string) $entry_id )
        );
        $wrong_actor = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $wrong_actor['status'], $wrong_actor['output'] );
        $wrong_actor_decoded = json_decode( $wrong_actor['output'], true );
        $this->assertIsArray( $wrong_actor_decoded, $wrong_actor['output'] );
        $this->assertTrue( $wrong_actor_decoded['error'], $wrong_actor['output'] );
        $this->assertSame( $update_value_1, SUPER_Data_Access::get_entry_data( $entry_id )['favorite_color']['value'] );

        // Restore the exact original owner context (same WP session token, same
        // browser session) so only the browser session differs in the next negative.
        wp_set_current_user( $owner_id );
        $_COOKIE[ LOGGED_IN_COOKIE ] = $owner_logged_in_cookie;
        $_COOKIE['_sfs_id'] = $authorized_session;

        // Negative: replayed superseded grant -- the browser session is
        // superseded (rotated to a new id, e.g. a cookie reset) while the same
        // owner stays logged in with the same WP session token; an attacker
        // replays the OLD grant's exact raw bytes into the NEW session row
        // under the same key. Only the browser_session_hash pin now differs,
        // and that alone must still cause rejection -> zero mutation.
        $captured_grant = SUPER_Common::getClientData( $grant_name );
        $this->assertIsArray( $captured_grant );
        $new_session_id = substr( hash( 'sha256', 'row19-rotated:' . wp_generate_uuid4() ), 0, 42 );
        update_option( '_sfsdata_' . $new_session_id, array( 'expires' => time() + HOUR_IN_SECONDS, 'exp_var' => time() + 1800 ), false );
        $this->extra_session_ids[] = $new_session_id;
        $_COOKIE['_sfs_id'] = $new_session_id;
        SUPER_Common::setClientData( array( 'name' => $grant_name, 'value' => $captured_grant, 'force' => true ) );
        $this->assertSame( $captured_grant, SUPER_Common::getClientData( $grant_name ) );
        $current_context_grant = SUPER_Common::current_entry_update_grant_value();
        $this->assertSame( $captured_grant['actor_id'], $current_context_grant['actor_id'] );
        $this->assertSame( $captured_grant['user_session_hash'], $current_context_grant['user_session_hash'] );
        $this->assertNotSame( $captured_grant['browser_session_hash'], $current_context_grant['browser_session_hash'] );

        $tamper_superseded = $update_value_1 . '-superseded-replay-tamper';
        $this->set_realistic_submit_request(
            $form_id,
            array( 'favorite_color' => array( 'name' => 'favorite_color', 'value' => $tamper_superseded, 'type' => 'text' ) ),
            array( 'entry_id' => (string) $entry_id )
        );
        $superseded = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $superseded['status'], $superseded['output'] );
        $superseded_decoded = json_decode( $superseded['output'], true );
        $this->assertIsArray( $superseded_decoded, $superseded['output'] );
        $this->assertTrue( $superseded_decoded['error'], $superseded['output'] );
        $this->assertSame( $update_value_1, SUPER_Data_Access::get_entry_data( $entry_id )['favorite_color']['value'] );

        $_COOKIE['_sfs_id'] = $authorized_session;
        wp_set_current_user( 0 );
    }

    public function test_sf_nonce_has_an_absolute_expiry_with_no_sliding_refresh_on_read() {
        wp_set_current_user( 0 );
        unset( $_COOKIE['_sfs_id'] );
        $nonce = SUPER_Common::generate_nonce();
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{96}$/D', $nonce );
        $session_id = $_COOKIE['_sfs_id'];
        $this->extra_session_ids[] = $session_id;

        // Supplement only: CLI cannot drive verifyCSRF()'s filter_input(INPUT_POST)
        // read. The observable contract here is the persisted nonce record lifecycle
        // that verifyCSRF() consumes in production.
        $this->assertSame( $nonce, SUPER_Common::getClientData( 'sf_nonce', false ) );

        $due_for_refresh = get_option( '_sfsdata_' . $session_id );
        $stale_exp_var = time() - 5;
        $due_for_refresh['sf_nonce']['exp_var'] = $stale_exp_var;
        update_option( '_sfsdata_' . $session_id, $due_for_refresh, false );
        $this->assertSame( $nonce, SUPER_Common::getClientData( 'sf_nonce', false ) );
        $after_read = get_option( '_sfsdata_' . $session_id );
        $this->assertSame( $stale_exp_var, $after_read['sf_nonce']['exp_var'], 'A CSRF-path read must not slide the nonce exp_var forward.' );
        $this->assertSame( $due_for_refresh['sf_nonce']['expires'], $after_read['sf_nonce']['expires'] );

        // Rejected after absolute expiry, and purged rather than revived.
        $expired = get_option( '_sfsdata_' . $session_id );
        $expired['sf_nonce']['expires'] = time() - 1;
        update_option( '_sfsdata_' . $session_id, $expired, false );
        $this->assertFalse( SUPER_Common::getClientData( 'sf_nonce', false ) );
        $after_expiry = get_option( '_sfsdata_' . $session_id );
        $this->assertArrayNotHasKey( 'sf_nonce', $after_expiry );

        // A refresh=true read is equally absolute post-expiry: the unconditional
        // `expires` purge runs before the exp_var-refresh branch is ever reached.
        update_option( '_sfsdata_' . $session_id, array(
            'expires' => time() + HOUR_IN_SECONDS,
            'exp_var' => time() + 1800,
            'sf_nonce' => array( 'expires' => time() - 1, 'exp_var' => time() - 1, 'value' => $nonce ),
        ), false );
        $this->assertFalse( SUPER_Common::getClientData( 'sf_nonce' ) );
        $after_refresh_attempt = get_option( '_sfsdata_' . $session_id );
        $this->assertArrayNotHasKey( 'sf_nonce', $after_refresh_attempt );
    }
}
