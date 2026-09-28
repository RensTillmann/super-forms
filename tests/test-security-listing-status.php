<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

/**
 * The Listings "change status" carrier (update_entry_status) on entry edits. It is accepted by
 * the submission contract only on listing edits, is never stored as entry data, and changes
 * _super_contact_entry_status only when the listing grants edit_any + change_status and the
 * value is a configured entry status. Every scenario drives the complete
 * SUPER_Ajax::submit_form() flow inside the fork helper and asserts in the parent.
 */
class Test_Super_Forms_Listing_Status_Security extends Super_Forms_Upload_Security_Test_Case {
    private $had_logged_in_cookie = false;
    private $original_logged_in_cookie = null;
    private $capture_files = array();

    public function set_up() {
        parent::set_up();
        $this->had_logged_in_cookie = array_key_exists( LOGGED_IN_COOKIE, $_COOKIE );
        $this->original_logged_in_cookie = $this->had_logged_in_cookie ? $_COOKIE[LOGGED_IN_COOKIE] : null;
    }

    public function tear_down() {
        if( $this->had_logged_in_cookie ) {
            $_COOKIE[LOGGED_IN_COOKIE] = $this->original_logged_in_cookie;
        } else {
            unset( $_COOKIE[LOGGED_IN_COOKIE] );
        }
        foreach( $this->capture_files as $capture ) {
            if( is_file( $capture ) ) unlink( $capture );
        }
        parent::tear_down();
    }

    public function test_listing_edit_with_change_status_grant_applies_configured_status_without_storing_carrier() {
        $fx = $this->fixture( $this->listing( true ) );

        $decoded = $this->submit_edit( $fx, $this->edit_data( 'after', 'completed' ) );

        $this->assert_success( $decoded );
        $this->assertSame( 'completed', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertArrayNotHasKey( 'update_entry_status', $saved, wp_json_encode( $saved ) );
        $this->assertSame( 'after', $saved['guest_name']['value'] );
        $this->assertSame( 'completed', $decoded['response_data']['entry_status']['key'], wp_json_encode( $decoded ) );
    }

    public function test_listing_status_transition_restriction_is_enforced_at_submission() {
        $list = $this->listing( true );
        $list['edit_any']['change_status']['when_not'] = 'completed, pending';
        $fx = $this->fixture( $list );
        $decoded = $this->submit_edit( $fx, $this->edit_data( 'after', 'completed' ) );
        $this->assert_success( $decoded );
        $this->assertSame( 'pending', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
        $this->assertSame( 'pending', $decoded['response_data']['entry_status']['key'] );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertSame( 'after', $saved['guest_name']['value'] );
        $this->assertArrayNotHasKey( 'update_entry_status', $saved );
    }

    public function test_cross_form_listing_uses_host_status_and_post_submit_controls() {
        $fx = $this->fixture( null );
        $list = $this->listing( true );
        $list['retrieve'] = 'all_forms';
        $list['form_processing_overlay'] = 'host overlay';
        $list['close_form_processing_overlay'] = 'false';
        $list['close_editor_window_after_editing'] = 'false';
        $host_id = $this->create_form( 'publish', array(), array( '_listings' => array( 'lists' => array( $list ) ) ) );
        $fx['listing_form_id'] = $host_id;
        $_POST = array( 'action' => 'super_listings_edit_entry', 'list_id' => '0', 'entry_id' => $fx['entry_id'] );
        $html = SUPER_Listings::display_edit_entry_status_dropdown( '', array(
            'id' => $fx['form_id'], 'listing_form_id' => $host_id,
            'settings' => SUPER_Common::get_form_settings($fx['form_id']),
        ) );
        $this->assertStringContainsString( 'super-listings-entry-status-changer', $html );
        $decoded = $this->submit_edit( $fx, $this->edit_data( 'cross form', 'completed' ) );
        $this->assert_success($decoded);
        foreach( array('form_processing_overlay', 'close_form_processing_overlay', 'close_editor_window_after_editing') as $key ) {
            $this->assertSame($list[$key], $decoded['response_data'][$key]);
        }
        $this->assertSame('completed', get_post_meta($fx['entry_id'], '_super_contact_entry_status', true));
    }

    public function test_listing_none_status_control_clears_the_stored_status() {
        $fx = $this->fixture( $this->listing( true ) );
        $decoded = $this->submit_edit( $fx, $this->edit_data( 'after', '0' ) );
        $this->assert_success( $decoded );
        $this->assertSame( '', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
        $this->assertSame( '', $decoded['response_data']['entry_status']['key'] );
        $this->assertArrayNotHasKey( 'update_entry_status', SUPER_Data_Access::get_entry_data( $fx['entry_id'] ) );
    }

    public function test_listing_edit_with_unknown_status_value_saves_entry_but_keeps_status() {
        $fx = $this->fixture( $this->listing( true ) );

        $decoded = $this->submit_edit( $fx, $this->edit_data( 'after', 'not_a_configured_status' ) );

        $this->assert_success( $decoded );
        $this->assertSame( 'pending', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertArrayNotHasKey( 'update_entry_status', $saved, wp_json_encode( $saved ) );
        $this->assertSame( 'after', $saved['guest_name']['value'] );
        // The listing still gets the entry's current (unchanged) status to redraw its column.
        $this->assertSame( 'pending', $decoded['response_data']['entry_status']['key'] );

        // A non-scalar value fails the submission contract before anything is saved.
        $data = $this->edit_data( 'again' );
        $data['update_entry_status'] = array( 'name' => 'update_entry_status', 'value' => array( 'completed' ), 'type' => 'var' );
        $decoded = $this->submit_edit( $fx, $data );
        $this->assert_error( $decoded, 'Invalid form data.' );
        $this->assertSame( $saved, SUPER_Data_Access::get_entry_data( $fx['entry_id'] ) );
        $this->assertSame( 'pending', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
    }

    public function test_listing_edit_without_change_status_grant_keeps_status_and_drops_carrier() {
        // edit_any granted, change_status disabled.
        $fx = $this->fixture( $this->listing( false ) );
        $decoded = $this->submit_edit( $fx, $this->edit_data( 'after', 'completed' ) );
        $this->assert_success( $decoded );
        $this->assertSame( 'pending', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertArrayNotHasKey( 'update_entry_status', $saved, wp_json_encode( $saved ) );
        $this->assertSame( 'after', $saved['guest_name']['value'] );

        // change_status enabled, but the actor's role is not an edit_any role; the edit itself is
        // authorized through edit_own (the actor authored the entry).
        $list = $this->listing( true, 'editor' );
        $list['edit_own'] = array( 'enabled' => 'true', 'user_roles' => '', 'user_ids' => '' );
        $fx = $this->fixture( $list );
        $permissions = SUPER_Listings::get_action_permissions( array( 'list' => $list ) );
        $this->assertFalse( $permissions['allowEditAny'] );
        $this->assertFalse( $permissions['allowChangeEntryStatus'] );
        $decoded = $this->submit_edit( $fx, $this->edit_data( 'after', 'completed' ) );
        $this->assert_success( $decoded );
        $this->assertSame( 'pending', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertArrayNotHasKey( 'update_entry_status', $saved, wp_json_encode( $saved ) );
        $this->assertSame( 'after', $saved['guest_name']['value'] );
    }

    public function test_localized_listing_edit_cannot_reenable_entry_creation_or_notifications() {
        $fx = $this->fixture( $this->listing( true ), array(
            'i18n' => array( 'nl_NL' => array(
                'update_contact_entry' => 'false',
                'contact_entry_prevent_creation' => 'false',
                'save_contact_entry' => 'yes',
                'send' => 'yes',
                'confirm' => 'yes',
            ) ),
        ) );
        $entries_before = $this->entry_ids();
        $decoded = $this->submit_edit( $fx, $this->edit_data( 'localized edit' ), true, 'nl_NL' );
        $this->assert_success( $decoded );
        $this->assertSame( $entries_before, $this->entry_ids() );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertSame( 'localized edit', $saved['guest_name']['value'] );
    }

    public function test_listing_response_uses_saved_selection_presentation() {
        foreach( array( 'value' => 'alice', 'label' => 'Alice Example', 'both' => 'Alice Example (alice)' ) as $mode => $expected ) {
            $elements = array( array( 'tag' => 'dropdown', 'data' => array(
                'name' => 'guest_name',
                'contact_entry_value' => $mode,
                'dropdown_items' => array( array( 'label' => 'Alice Example', 'value' => 'alice' ) ),
            ) ) );
            $fx = $this->fixture( $this->listing( true ), array(), $elements );
            $data = $this->edit_data( 'alice' );
            $data['guest_name']['selected_values'] = array( 'alice' );
            $decoded = $this->submit_edit( $fx, $data );
            $this->assert_success( $decoded );
            $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
            $this->assertSame( $expected, $saved['guest_name']['value'] );
            $this->assertSame( $expected, $decoded['response_data']['entry_values']['guest_name'] );
        }
    }

    public function test_non_listing_submissions_carrying_status_are_rejected_before_saving() {
        // update_contact_entry edit (no list_id).
        $fx = $this->fixture( null );
        $before = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $decoded = $this->submit_edit( $fx, $this->edit_data( 'after', 'completed' ), false );
        $this->assert_error( $decoded, 'Invalid form data.' );
        $this->assertSame( $before, SUPER_Data_Access::get_entry_data( $fx['entry_id'] ) );
        $this->assertSame( 'pending', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );

        // A new submission (no entry_id, no list_id).
        $entries_before = $this->entry_ids();
        $this->set_request( $fx['form_id'], $this->edit_data( 'new', 'completed' ) );
        $decoded = $this->run_submission();
        $this->assert_error( $decoded, 'Invalid form data.' );
        $this->assertSame( $entries_before, $this->entry_ids() );
        $this->assertSame( $before, SUPER_Data_Access::get_entry_data( $fx['entry_id'] ) );
    }

    public function test_entry_updates_without_status_carrier_preserve_existing_status() {
        // Listing edits without the carrier never touch the status, with or without the
        // change_status grant (6.4.007 wrote a null status on every entry edit).
        foreach( array( true, false ) as $change_status ) {
            $fx = $this->fixture( $this->listing( $change_status ) );
            $decoded = $this->submit_edit( $fx, $this->edit_data( 'after' ) );
            $this->assert_success( $decoded );
            $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
            $this->assertSame( 'after', $saved['guest_name']['value'] );
            $this->assertSame( 'pending', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
        }

        // Plain update_contact_entry edit whose "Contact entry status after updating" equals the
        // current status: the status survives the edit.
        $fx = $this->fixture( null, array( 'contact_entry_custom_status_update' => 'pending' ) );
        $decoded = $this->submit_edit( $fx, $this->edit_data( 'after' ), false );
        $this->assert_success( $decoded );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertSame( 'after', $saved['guest_name']['value'] );
        $this->assertSame( 'pending', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
    }

    public function test_plain_entry_update_applies_configured_status_after_updating() {
        $fx = $this->fixture( null, array( 'contact_entry_custom_status_update' => 'completed' ) );
        $decoded = $this->submit_edit( $fx, $this->edit_data( 'after' ), false );
        $this->assert_success( $decoded );
        $this->assertSame( 'completed', get_post_meta( $fx['entry_id'], '_super_contact_entry_status', true ) );
    }

    // ---------------------------------------------------------------------------------------

    private function login_actor() {
        $actor_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $actor_id );
        // Opening the edit UI issues a browser- and login-session-bound update grant; a CLI
        // run has no login cookie, so give the actor the WordPress session a browser carries.
        $expiration = time() + HOUR_IN_SECONDS;
        $session_token = WP_Session_Tokens::get_instance( $actor_id )->create( $expiration );
        $_COOKIE[LOGGED_IN_COOKIE] = wp_generate_auth_cookie( $actor_id, $expiration, 'logged_in', $session_token );
        return $actor_id;
    }

    private function listing( $change_status, $edit_any_roles='administrator' ) {
        return array(
            'display' => array( 'enabled' => 'false' ),
            'edit_any' => array(
                'enabled' => 'true',
                'user_roles' => $edit_any_roles,
                'user_ids' => '',
                'change_status' => array( 'enabled' => $change_status ? 'true' : 'false' ),
            ),
        );
    }

    /** A form with one text field and an entry by the logged-in actor, status "pending". */
    private function fixture( $list, $form_settings=array(), $elements=null ) {
        $actor_id = $this->login_actor();
        $base = ( $list!==null )
            ? array( 'csrf_check' => 'false', '_listings' => array( 'lists' => array( $list ) ) )
            : array(
                'csrf_check' => 'false',
                'update_contact_entry' => 'true',
                'contact_entry_prevent_creation' => 'true',
                'send' => 'no',
                'confirm' => 'no',
            );
        if( $elements===null ) {
            $elements = array(
                array( 'tag' => 'text', 'data' => array( 'name' => 'guest_name', 'validation' => 'none' ) ),
            );
        }
        $form_id = $this->create_form( 'publish', $elements, array_merge( $base, $form_settings ), $actor_id );
        $entry_id = self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'super_unread',
            'post_parent' => $form_id,
            'post_author' => $actor_id,
        ) );
        SUPER_Data_Access::update_entry_data( $entry_id, array(
            'guest_name' => array( 'name' => 'guest_name', 'value' => 'before', 'type' => 'var' ),
        ) );
        update_post_meta( $entry_id, '_super_contact_entry_status', 'pending' );
        $this->configure_csrf( 'false' );
        return array( 'actor_id' => $actor_id, 'form_id' => $form_id, 'entry_id' => $entry_id );
    }

    private function edit_data( $guest_name, $status=null ) {
        $data = array(
            'guest_name' => array( 'name' => 'guest_name', 'value' => $guest_name, 'type' => 'var' ),
        );
        if( $status!==null ) {
            $data['update_entry_status'] = array( 'name' => 'update_entry_status', 'value' => $status, 'type' => 'var' );
        }
        return $data;
    }

    private function submit_edit( $fx, $data, $listing=true, $i18n='' ) {
        $form_id = $fx['form_id'];
        $entry_id = $fx['entry_id'];
        $extra = array( 'entry_id' => (string) $entry_id );
        $host_id = isset($fx['listing_form_id']) ? $fx['listing_form_id'] : $form_id;
        if($listing) $extra['listing_form_id'] = (string) $host_id;
        if( $i18n!=='' ) {
            $extra['i18n'] = $i18n;
        }
        if( $listing ) {
            $extra['list_id'] = '0';
        }
        $this->set_request( $form_id, $data, array(), $extra );
        $grant = SUPER_Common::current_entry_update_grant_value();
        $this->assertTrue( is_array( $grant ) );
        SUPER_Common::setClientData( array(
            'name' => $listing
                ? 'update_contact_entry_' . $form_id . '_' . $host_id . '_0_' . $entry_id
                : 'update_contact_entry_' . $form_id . '_' . $entry_id,
            'value' => $grant,
            'force' => true,
        ) );
        return $this->run_submission();
    }

    /** Runs submit_form() in the fork helper; any PHP notice on the submit path fails the test. */
    private function run_submission() {
        $notice_log = tempnam( sys_get_temp_dir(), 'sf-status-notices-' );
        $this->assertNotFalse( $notice_log );
        $this->capture_files[] = $notice_log;
        $result = $this->run_dying_handler( static function() use ( $notice_log ) {
            mt_srand( random_int( 0, 0x7fffffff ) );
            set_error_handler(
                static function( $errno, $errstr, $errfile, $errline ) use ( $notice_log ) {
                    file_put_contents( $notice_log, $errstr . ' (' . basename( $errfile ) . ':' . $errline . ")\n", FILE_APPEND | LOCK_EX );
                    return true;
                },
                E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED
            );
            SUPER_Ajax::submit_form();
        } );
        $notices = file( $notice_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        $this->assertSame( array(), ( $notices===false ? array() : $notices ), 'PHP notices during submit_form()' );
        $this->assertTrue( $result['exited'] );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertTrue( is_array( $decoded ), $result['output'] );
        return $decoded;
    }

    private function entry_ids() {
        $ids = get_posts( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC',
        ) );
        return array_map( 'absint', $ids );
    }

    private function assert_success( $decoded ) {
        $this->assertFalse( $decoded['error'], wp_json_encode( $decoded ) );
    }

    private function assert_error( $decoded, $message ) {
        $this->assertTrue( $decoded['error'], wp_json_encode( $decoded ) );
        $this->assertStringContainsString( $message, wp_strip_all_tags( $decoded['msg'] ) );
    }
}
