<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Super_Forms_Listings_Security extends Super_Forms_Upload_Security_Test_Case {
    private $original_get;

    public function set_up() {
        $this->original_get = $_GET;
        parent::set_up();
        $_GET = array();
    }

    public function tear_down() {
        unset( $_COOKIE[LOGGED_IN_COOKIE] );
        $_GET = $this->original_get;
        parent::tear_down();
    }

    private function authenticate_actor( $user_id ) {
        $expiration = time() + HOUR_IN_SECONDS;
        $session_token = WP_Session_Tokens::get_instance($user_id)->create($expiration);
        $_COOKIE[LOGGED_IN_COOKIE] = wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $session_token);
        wp_set_current_user( $user_id );
        $browser_session_id = SUPER_Common::startClientSession( array( 'force' => true ) );
        $this->assertTrue( is_string( $browser_session_id ) && $browser_session_id!=='' );
        return $session_token;
    }

    private function query_test_column($name, $field_name, $filter_type='text') {
        return array(
            'name' => $name,
            'field_name' => $field_name,
            'filter' => array(
                'enabled' => 'true',
                'type' => $filter_type,
                'items' => '',
                'placeholder' => 'search'
            ),
            'sort' => 'true',
            'link' => array(
                'type' => 'none',
                'url' => ''
            ),
            'width' => 120,
            'order' => 10
        );
    }

    private function create_query_test_listing($enable_author_id=false) {
        $administrator_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $administrator_id );
        $list = array(
            'enabled' => 'true',
            'display' => array(
                'enabled' => 'true',
                'user_roles' => 'administrator',
                'user_ids' => '',
                'message' => ''
            ),
            'retrieve' => 'this_form',
            'limit' => 13,
            'custom_columns' => array(
                'enabled' => 'true',
                'columns' => array(
                    $this->query_test_column( 'Favorite color', 'favorite_color' ),
                    $this->query_test_column( 'Favorite food', 'favorite_food' ),
                    $this->query_test_column( 'Ignored type', 'ignored_type', 'raw' )
                )
            )
        );
        if($enable_author_id){
            $list['author_id_column'] = array(
                'enabled' => 'true',
                'name' => 'Author ID',
                'filter' => array(
                    'enabled' => 'true',
                    'type' => 'text',
                    'placeholder' => 'search'
                ),
                'sort' => 'true',
                'link' => array(
                    'type' => 'none',
                    'url' => ''
                ),
                'order' => 10,
                'width' => 100
            );
        }
        return $this->create_form(
            'publish',
            array(),
            array( '_listings' => array( 'lists' => array( $list ) ) ),
            $administrator_id
        );
    }

    private function capture_listing_queries($form_id, $request) {
        $captured = array(
            'ordered' => '',
            'counts' => array(),
        );
        $capture_query = function($query) use (&$captured) {
            if(strpos($query, 'COUNT(entry_id) AS total')!==false){
                $captured['counts'][] = $query;
            }
            if(strpos($query, 'post.post_type AS post_type')!==false && strpos($query, 'ORDER BY')!==false){
                $captured['ordered'] = $query;
            }
            return $query;
        };
        $this->add_upload_filter( 'query', $capture_query );
        $_GET = $request;
        SUPER_Listings::super_listings_func(
            array(
                'id' => $form_id,
                'list' => 1
            )
        );
        $this->remove_upload_filter( 'query', $capture_query );
        $this->assertNotSame( '', $captured['ordered'], 'The Listings entries query was not executed.' );
        return $captured;
    }

    private function capture_entries_query($form_id, $request) {
        $captured = $this->capture_listing_queries($form_id, $request);
        return $captured['ordered'];
    }

    private function render_listing_modal($post) {
        $original_post = $_POST;
        $original_get = $_GET;
        $_POST = $post;
        ob_start();
        include SUPER_PLUGIN_DIR . '/includes/extensions/listings/form-blank-page-template.php';
        $output = ob_get_clean();
        $_POST = $original_post;
        $_GET = $original_get;
        return $output;
    }

    public function test_delete_endpoint_requires_exact_nonce_scope_and_ownership() {
        $owner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $attacker_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $list = array(
            'enabled' => 'true',
            'display' => array( 'retrieve' => 'this_form' ),
            'edit_any' => array(
                'enabled' => 'false',
                'user_roles' => '',
                'user_ids' => '',
            ),
            'edit_own' => array(
                'enabled' => 'true',
                'user_roles' => '',
                'user_ids' => '',
            ),
            'delete_any' => array(
                'enabled' => 'false',
                'user_roles' => '',
                'user_ids' => '',
                'permanent' => 'false',
            ),
            'delete_own' => array(
                'enabled' => 'true',
                'user_roles' => '',
                'user_ids' => '',
                'permanent' => 'false',
            ),
        );
        $form_id = $this->create_form(
            'publish',
            array(),
            array( '_listings' => array( 'lists' => array( $list ) ) )
        );
        $other_form_id = $this->create_form( 'publish' );
        $owned_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
                'post_author' => $owner_id,
            )
        );
        $other_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $other_form_id,
                'post_author' => $owner_id,
            )
        );
        wp_set_current_user( $owner_id );
        $before_status = get_post_status( $owned_entry_id );
        $_POST = array(
            'entry_id' => $owned_entry_id,
            'form_id' => $form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_delete_entry_' . $form_id . '_1' ),
        );
        $wrong_nonce = $this->run_dying_handler( array( 'SUPER_Ajax', 'listings_delete_entry' ) );
        $this->assertSame( 0, $wrong_nonce['status'], $wrong_nonce['output'] );
        $this->assertStringContainsString( 'permission to delete', strtolower( $wrong_nonce['output'] ) );
        $this->assertSame( $before_status, get_post_status( $owned_entry_id ) );
        $this->assertSame( 'super_contact_entry', get_post_type( $owned_entry_id ) );
        wp_set_current_user( $attacker_id );

        $_POST = array(
            'entry_id' => $other_entry_id,
            'form_id' => $form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_delete_entry_' . $form_id . '_0' ),
        );
        $wrong_scope = $this->run_dying_handler( array( 'SUPER_Ajax', 'listings_delete_entry' ) );
        $this->assertSame( 0, $wrong_scope['status'], $wrong_scope['output'] );
        $this->assertStringContainsString( 'permission to delete', strtolower( $wrong_scope['output'] ) );
        $this->assertSame( 'super_contact_entry', get_post_type( $other_entry_id ) );

        $_POST['entry_id'] = $owned_entry_id;
        $not_owner = $this->run_dying_handler( array( 'SUPER_Ajax', 'listings_delete_entry' ) );
        $this->assertSame( 0, $not_owner['status'], $not_owner['output'] );
        $this->assertStringContainsString( 'permission to delete', strtolower( $not_owner['output'] ) );
        $this->assertSame( 'super_contact_entry', get_post_type( $owned_entry_id ) );

        wp_set_current_user( $owner_id );
        $this->assertTrue(
            $this->invoke_ajax_private(
                'submission_entry_update_is_authorized',
                array( $owned_entry_id, '0', $form_id, array( '_listings' => array( 'lists' => array( $list ) ) ) )
            )
        );
        update_post_meta( $owned_entry_id, '_super_contact_entry_wc_order_id', 24680 );
        $this->assertFalse(
            $this->invoke_ajax_private(
                'submission_entry_update_is_authorized',
                array( $owned_entry_id, '0', $form_id, array( '_listings' => array( 'lists' => array( $list ) ) ) )
            ),
            'A modal Listings update must not bypass WooCommerce order ownership.'
        );
    }

    public function test_submission_entry_update_requires_any_permission_or_exact_nonzero_owner() {
        $owner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $other_user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $list = array(
            'enabled' => 'true',
            'display' => array( 'retrieve' => 'this_form' ),
            'edit_any' => array(
                'enabled' => 'false',
                'user_roles' => '',
                'user_ids' => '',
            ),
            'edit_own' => array(
                'enabled' => 'true',
                'user_roles' => '',
                'user_ids' => '',
            ),
        );
        $settings = array( '_listings' => array( 'lists' => array( $list ) ) );
        $form_id = $this->create_form( 'publish', array(), $settings );
        wp_set_current_user( 0 );
        $anonymous_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
                'post_author' => 0,
            )
        );
        $owned_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
                'post_author' => $owner_id,
            )
        );

        $this->assertFalse(
            $this->invoke_ajax_private(
                'submission_entry_update_is_authorized',
                array( $anonymous_entry_id, '0', $form_id, $settings )
            ),
            'Anonymous user ID 0 must never match an anonymous entry author as edit-own.'
        );

        wp_set_current_user( $other_user_id );
        $this->assertFalse(
            $this->invoke_ajax_private(
                'submission_entry_update_is_authorized',
                array( $owned_entry_id, '0', $form_id, $settings )
            ),
            'Edit-own must not authorize a different logged-in user.'
        );

        wp_set_current_user( $owner_id );
        $this->assertTrue(
            $this->invoke_ajax_private(
                'submission_entry_update_is_authorized',
                array( $owned_entry_id, '0', $form_id, $settings )
            ),
            'Edit-own must authorize the exact positive entry author.'
        );

        $list['edit_any']['enabled'] = 'true';
        $list['edit_own']['enabled'] = 'false';
        $settings = array( '_listings' => array( 'lists' => array( $list ) ) );
        wp_set_current_user( 0 );
        $this->assertTrue(
            $this->invoke_ajax_private(
                'submission_entry_update_is_authorized',
                array( $owned_entry_id, '0', $form_id, $settings )
            ),
            'An applicable edit-any policy remains sufficient on its own.'
        );
    }

    public function test_delete_endpoint_rejects_same_parent_entry_excluded_by_list_scope() {
        $actor_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        $retrieved_form_id = $this->create_form( 'publish' );
        $list = array(
            'enabled' => 'true',
            'retrieve' => 'specific_forms',
            'form_ids' => (string) $retrieved_form_id,
            'delete_any' => array(
                'enabled' => 'true',
                'user_roles' => 'administrator',
                'user_ids' => '',
                'permanent' => 'true',
            ),
            'delete_own' => array(
                'enabled' => 'false',
                'user_roles' => '',
                'user_ids' => '',
                'permanent' => 'false',
            ),
        );
        $host_form_id = $this->create_form(
            'publish',
            array(),
            array( '_listings' => array( 'lists' => array( $list ) ) )
        );
        $entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $host_form_id,
                'post_author' => $actor_id,
            )
        );
        wp_set_current_user( $actor_id );
        $_POST = array(
            'entry_id' => $entry_id,
            'form_id' => $host_form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_delete_entry_' . $host_form_id . '_0' ),
        );

        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'listings_delete_entry' ) );

        $this->assertSame( 0, $result['status'], $result['output'] );
        $this->assertStringContainsString( 'permission to delete', strtolower( $result['output'] ) );
        $this->assertSame( 'super_contact_entry', get_post_type( $entry_id ) );
    }

    public function test_cross_form_entry_actions_follow_configured_retrieval_scope() {
        $actor_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        $source_form_id = $this->create_form( 'publish' );
        $outside_form_id = $this->create_form( 'publish' );
        wp_set_current_user( $actor_id );

        foreach( array( 'specific_forms', 'all_forms' ) as $retrieve ) {
            $list = array(
                'enabled' => 'true',
                'retrieve' => $retrieve,
                'form_ids' => $retrieve==='specific_forms' ? (string) $source_form_id : '',
                'edit_any' => array(
                    'enabled' => 'true',
                    'user_roles' => 'administrator',
                    'user_ids' => '',
                ),
                'edit_own' => array(
                    'enabled' => 'false',
                    'user_roles' => '',
                    'user_ids' => '',
                ),
                'delete_any' => array(
                    'enabled' => 'true',
                    'user_roles' => 'administrator',
                    'user_ids' => '',
                    'permanent' => 'true',
                ),
                'delete_own' => array(
                    'enabled' => 'false',
                    'user_roles' => '',
                    'user_ids' => '',
                    'permanent' => 'false',
                ),
            );
            $settings = array( '_listings' => array( 'lists' => array( $list ) ) );
            $host_form_id = $this->create_form( 'publish', array(), $settings );
            $entry_id = self::factory()->post->create(
                array(
                    'post_type' => 'super_contact_entry',
                    'post_status' => 'super_unread',
                    'post_parent' => $source_form_id,
                    'post_author' => $actor_id,
                )
            );

            $this->authenticate_actor( $actor_id );
            SUPER_Common::setClientData( array(
                'name' => 'update_contact_entry_' . $source_form_id . '_' . $host_form_id . '_0_' . $entry_id,
                'value' => SUPER_Common::current_entry_update_grant_value(),
                'force' => true,
            ) );
            $this->assertTrue(
                $this->invoke_ajax_private(
                    'submission_entry_update_is_authorized',
                    array( $entry_id, '0', $source_form_id, $settings, $host_form_id, true )
                ),
                $retrieve . ' must authorize an in-scope cross-form entry update.'
            );

            $_POST = array(
                'entry_id' => $entry_id,
                'form_id' => $host_form_id,
                'list_id' => 0,
                'nonce' => wp_create_nonce( 'super_listings_delete_entry_' . $host_form_id . '_0' ),
            );
            $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'listings_delete_entry' ) );
            $this->assertSame( 0, $result['status'], $result['output'] );
            $this->assertSame( '1', $result['output'] );
            $this->assertNull( get_post( $entry_id ) );
        }

        $specific_list = array(
            'enabled' => 'true',
            'retrieve' => 'specific_forms',
            'form_ids' => (string) $source_form_id,
            'edit_any' => array(
                'enabled' => 'true',
                'user_roles' => 'administrator',
                'user_ids' => '',
            ),
            'delete_any' => array(
                'enabled' => 'true',
                'user_roles' => 'administrator',
                'user_ids' => '',
                'permanent' => 'true',
            ),
        );
        $specific_settings = array( '_listings' => array( 'lists' => array( $specific_list ) ) );
        $specific_host_form_id = $this->create_form( 'publish', array(), $specific_settings );
        $outside_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $outside_form_id,
                'post_author' => $actor_id,
            )
        );
        $this->authenticate_actor( $actor_id );
        SUPER_Common::setClientData( array(
            'name' => 'update_contact_entry_' . $outside_form_id . '_' . $specific_host_form_id . '_0_' . $outside_entry_id,
            'value' => SUPER_Common::current_entry_update_grant_value(),
            'force' => true,
        ) );
        $this->assertFalse(
            $this->invoke_ajax_private(
                'submission_entry_update_is_authorized',
                array( $outside_entry_id, '0', $outside_form_id, $specific_settings, $specific_host_form_id, true )
            ),
            'A cross-form entry outside specific_forms must remain unauthorized.'
        );

        $_POST = array(
            'entry_id' => $outside_entry_id,
            'form_id' => $specific_host_form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_delete_entry_' . $specific_host_form_id . '_0' ),
        );
        $denied = $this->run_dying_handler( array( 'SUPER_Ajax', 'listings_delete_entry' ) );
        $this->assertSame( 0, $denied['status'], $denied['output'] );
        $this->assertStringContainsString( 'permission to delete', strtolower( $denied['output'] ) );
        $this->assertSame( 'super_contact_entry', get_post_type( $outside_entry_id ) );
    }
    public function test_edit_modal_uses_the_target_form_and_localizes_the_listings_handle() {
        $actor_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $actor_id );
        $target_form_id = $this->create_form( 'publish' );
        $list = array(
            'enabled' => 'true',
            'retrieve' => 'all_forms',
            'edit_any' => array(
                'enabled' => 'true',
                'user_roles' => 'administrator',
                'user_ids' => '',
            ),
            'edit_own' => array(
                'enabled' => 'false',
                'user_roles' => '',
                'user_ids' => '',
            ),
        );
        $settings = array( '_listings' => array( 'lists' => array( $list ) ) );
        $host_form_id = $this->create_form(
            'publish',
            array(),
            $settings
        );
        $entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $target_form_id,
                'post_author' => $actor_id,
            )
        );
        $same_form_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $host_form_id,
                'post_author' => $actor_id,
            )
        );
        $this->authenticate_actor( $actor_id );
        SUPER_Common::setClientData( array(
            'name' => 'update_contact_entry_' . $host_form_id . '_' . $host_form_id . '_0_' . $same_form_entry_id,
            'value' => SUPER_Common::current_entry_update_grant_value(),
            'force' => true,
        ) );
        $this->assertTrue(
            $this->invoke_ajax_private(
                'submission_entry_update_is_authorized',
                array( $same_form_entry_id, '0', $host_form_id, $settings, $host_form_id, true )
            )
        );

        SUPER_Listings::super_listings_func(
            array(
                'id' => $host_form_id,
                'list' => 1,
            )
        );
        $this->assertTrue( wp_script_is( 'super-listings', 'registered' ) );
        $this->assertStringContainsString( 'super_listings_i18n', (string) wp_scripts()->get_data( 'super-listings', 'data' ) );
        $this->assertStringNotContainsString( 'super_listings_i18n', (string) wp_scripts()->get_data( 'super-common', 'data' ) );

        $_POST = array(
            'action' => 'super_listings_edit_entry',
            'entry_id' => $entry_id,
            'form_id' => $host_form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_entry_' . $host_form_id . '_0' ),
        );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'listings_edit_entry' ) );

        $this->assertSame( 0, $result['status'], $result['output'] );
        $this->assertRenderedInputValue( $result['output'], 'hidden_form_id', $target_form_id );
        $this->assertRenderedInputValue( $result['output'], 'hidden_listing_form_id', $host_form_id );
    }

    public function test_public_edit_modal_bootstraps_an_anonymous_session_renders_the_exact_entry_and_sets_the_update_grant() {
        wp_set_current_user( 0 );
        unset( $_COOKIE['_sfs_id'] );
        $target_form_id = $this->create_form(
            'publish',
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'favorite_color',
                        'label' => 'Favorite color',
                    ),
                ),
            )
        );
        $list = array(
            'enabled' => 'true',
            'retrieve' => 'all_forms',
            'edit_any' => array(
                'enabled' => 'true',
                'user_roles' => '',
                'user_ids' => '',
            ),
            'edit_own' => array(
                'enabled' => 'false',
                'user_roles' => '',
                'user_ids' => '',
            ),
        );
        $host_form_id = $this->create_form(
            'publish',
            array(),
            array( '_listings' => array( 'lists' => array( $list ) ) )
        );
        $target_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $target_form_id,
                'post_author' => 0,
            )
        );
        update_post_meta(
            $target_entry_id,
            '_super_contact_entry_data',
            array(
                'favorite_color' => array(
                    'name' => 'favorite_color',
                    'value' => 'target-blue',
                    'type' => 'text',
                ),
            )
        );
        $other_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $target_form_id,
                'post_author' => 0,
            )
        );
        update_post_meta(
            $other_entry_id,
            '_super_contact_entry_data',
            array(
                'favorite_color' => array(
                    'name' => 'favorite_color',
                    'value' => 'latest-fallback',
                    'type' => 'text',
                ),
            )
        );
        $grant = 'update_contact_entry_' . $target_form_id . '_' . $host_form_id . '_0_' . $target_entry_id;
        $this->assertFalse( SUPER_Common::getClientData( $grant ) );
        $output = $this->render_listing_modal( array(
            'action' => 'super_listings_edit_entry',
            'entry_id' => $target_entry_id,
            'form_id' => $host_form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_entry_' . $host_form_id . '_0' ),
        ) );
        $this->assertArrayHasKey( '_sfs_id', $_COOKIE );
        $this->assertRenderedInputValue( $output, 'hidden_form_id', $target_form_id );
        $this->assertRenderedInputValue( $output, 'hidden_listing_form_id', $host_form_id );
        $this->assertRenderedInputValue( $output, 'hidden_contact_entry_id', $target_entry_id );
        $this->assertStringContainsString( 'target-blue', $output );
        $this->assertStringNotContainsString( 'latest-fallback', $output );
        $this->assertArrayNotHasKey( 'super_form_entry_access_' . $target_entry_id, $_COOKIE );
        $this->assertSame( SUPER_Common::current_entry_update_grant_value(), SUPER_Common::getClientData( $grant ) );
    }
    public function test_legacy_public_modal_delegate_issues_the_exact_listing_grant_and_public_submit_updates_only_that_entry() {
        wp_set_current_user( 0 );
        unset( $_COOKIE['_sfs_id'] );
        $target_form_id = $this->create_form(
            'publish',
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'favorite_color',
                        'label' => 'Favorite color',
                    ),
                ),
            ),
            array(
                'send' => 'no',
                'confirm' => 'no',
                'save_contact_entry' => 'no',
                'form_thanks_title' => '',
                'form_thanks_description' => '',
                'form_show_thanks_msg' => '',
                'form_redirect_option' => '',
            )
        );
        $list = array(
            'enabled' => 'true',
            'retrieve' => 'all_forms',
            'edit_any' => array(
                'enabled' => 'true',
                'user_roles' => '',
                'user_ids' => '',
            ),
            'edit_own' => array(
                'enabled' => 'false',
                'user_roles' => '',
                'user_ids' => '',
            ),
        );
        $host_form_id = $this->create_form(
            'publish',
            array(),
            array( '_listings' => array( 'lists' => array( $list ) ) )
        );
        $target_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $target_form_id,
                'post_author' => 0,
            )
        );
        SUPER_Data_Access::update_entry_data(
            $target_entry_id,
            array(
                'favorite_color' => array(
                    'name' => 'favorite_color',
                    'value' => 'before',
                    'type' => 'text',
                ),
            )
        );
        $before_ids = get_posts(
            array(
                'post_type' => 'super_contact_entry',
                'post_parent' => $target_form_id,
                'post_status' => array( 'super_unread', 'super_read', 'publish' ),
                'fields' => 'ids',
                'posts_per_page' => -1,
            )
        );
        $this->assertSame( array( $target_entry_id ), array_map( 'intval', $before_ids ) );
        $this->assertNotFalse( has_action( 'wp_ajax_nopriv_super_load_form_inside_modal', array( 'SUPER_Ajax', 'load_form_inside_modal' ) ) );
        $_POST = array(
            'action' => 'super_load_form_inside_modal',
        );
        $missing_modal = $this->run_dying_handler( static function() {
            do_action( 'wp_ajax_nopriv_super_load_form_inside_modal' );
        } );
        $this->assertSame( 0, $missing_modal['status'], $missing_modal['output'] );
        $missing_modal_payload = json_decode( $missing_modal['output'], true );
        $this->assertIsArray( $missing_modal_payload, $missing_modal['output'] );
        $this->assertTrue( $missing_modal_payload['error'] );
        $this->assertStringContainsString( 'invalid form data', strtolower( wp_strip_all_tags( $missing_modal_payload['msg'] ) ) );
        $_POST = array(
            'action' => 'super_load_form_inside_modal',
            'entry_id' => $target_entry_id,
            'form_id' => $host_form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_entry_' . $host_form_id . '_0' ),
        );
        $modal = $this->run_dying_handler( static function() {
            do_action( 'wp_ajax_nopriv_super_load_form_inside_modal' );
        } );
        $this->assertSame( 0, $modal['status'], $modal['output'] );
        $this->assertRenderedInputValue( $modal['output'], 'hidden_form_id', $target_form_id );
        $this->assertRenderedInputValue( $modal['output'], 'hidden_listing_form_id', $host_form_id );
        $this->assertRenderedInputValue( $modal['output'], 'hidden_contact_entry_id', $target_entry_id );
        $authorized_session = $_COOKIE['_sfs_id'];
        $grant = 'update_contact_entry_' . $target_form_id . '_' . $host_form_id . '_0_' . $target_entry_id;
        $this->assertSame( SUPER_Common::current_entry_update_grant_value(), SUPER_Common::getClientData( $grant ) );

        $updated_value = "after 'slash\\ \"quote";
        $data = array(
            'favorite_color' => array(
                'name' => 'favorite_color',
                'value' => $updated_value,
                'type' => 'text',
            ),
            'hidden_form_id' => array(
                'name' => 'hidden_form_id',
                'value' => (string) $target_form_id,
                'type' => 'form_id',
            ),
            'hidden_contact_entry_id' => array(
                'name' => 'hidden_contact_entry_id',
                'value' => (string) $target_entry_id,
                'type' => 'entry_id',
            ),
            'hidden_list_id' => array(
                'name' => 'hidden_list_id',
                'value' => '0',
                'type' => 'var',
            ),
        );
        $this->set_submit_request(
            $target_form_id,
            $data,
            array(
                'entry_id' => (string) $target_entry_id,
                'list_id' => '0',
                'listing_form_id' => (string) $host_form_id,
            )
        );
        $submit = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $submit['status'], $submit['output'] );
        $decoded = json_decode( $submit['output'], true );
        $this->assertIsArray( $decoded, $submit['output'] );
        $this->assertFalse( $decoded['error'] );
        $stored = SUPER_Data_Access::get_entry_data( $target_entry_id );
        $this->assertSame( $updated_value, $stored['favorite_color']['value'] );
        $this->assertSame( $before_ids, get_posts(
            array(
                'post_type' => 'super_contact_entry',
                'post_parent' => $target_form_id,
                'post_status' => array( 'super_unread', 'super_read', 'publish' ),
                'fields' => 'ids',
                'posts_per_page' => -1,
            )
        ) );

        unset( $_COOKIE['_sfs_id'] );
        $this->set_submit_request(
            $target_form_id,
            $data,
            array(
                'entry_id' => (string) $target_entry_id,
                'list_id' => '0',
                'listing_form_id' => (string) $host_form_id,
            )
        );
        $denied = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $denied['status'], $denied['output'] );
        $denied_decoded = json_decode( $denied['output'], true );
        $this->assertIsArray( $denied_decoded, $denied['output'] );
        $this->assertTrue( $denied_decoded['error'] );
        $this->assertStringContainsString( 'permission to edit this entry', strtolower( wp_strip_all_tags( $denied_decoded['msg'] ) ) );
        $this->assertSame( $stored, SUPER_Data_Access::get_entry_data( $target_entry_id ) );
        $denied_session = isset( $_COOKIE['_sfs_id'] ) ? $_COOKIE['_sfs_id'] : '';
        if( is_string( $denied_session ) && $denied_session!=='' && $denied_session!==$authorized_session ) {
            delete_option( '_sfsdata_' . $denied_session );
        }
        $_COOKIE['_sfs_id'] = $authorized_session;
    }

    public function test_public_edit_modal_does_not_create_an_update_grant_when_rendering_is_denied() {
        wp_set_current_user( 0 );
        unset( $_COOKIE['_sfs_id'] );
        $target_form_id = $this->create_form( 'publish' );
        $list = array(
            'enabled' => 'true',
            'retrieve' => 'all_forms',
            'edit_any' => array(
                'enabled' => 'true',
                'user_roles' => '',
                'user_ids' => '',
            ),
            'edit_own' => array(
                'enabled' => 'false',
                'user_roles' => '',
                'user_ids' => '',
            ),
        );
        $host_form_id = $this->create_form(
            'publish',
            array(),
            array( '_listings' => array( 'lists' => array( $list ) ) )
        );
        $entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $target_form_id,
                'post_author' => 0,
            )
        );
        update_post_meta( $entry_id, '_super_contact_entry_wc_order_id', 24680 );
        $grant = 'update_contact_entry_' . $target_form_id . '_' . $host_form_id . '_0_' . $entry_id;
        $output = $this->render_listing_modal( array(
            'action' => 'super_listings_edit_entry',
            'entry_id' => $entry_id,
            'form_id' => $host_form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_entry_' . $host_form_id . '_0' ),
        ) );
        $this->assertStringContainsString( 'not allowed to edit this entry because it is connected to Order', $output );
        $this->assertFalse( SUPER_Common::getClientData( $grant ) );
        unset( $_COOKIE['super_form_entry_access_' . $entry_id] );
    }
    public function test_edit_own_requires_a_positive_authenticated_owner_for_render_and_submission() {
        $owner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $list = array(
            'enabled' => 'true',
            'retrieve' => 'this_form',
            'edit_any' => array(
                'enabled' => 'false',
                'user_roles' => '',
                'user_ids' => '',
            ),
            'edit_own' => array(
                'enabled' => 'true',
                'user_roles' => '',
                'user_ids' => '',
            ),
        );
        $settings = array( '_listings' => array( 'lists' => array( $list ) ) );
        $form_id = $this->create_form(
            'publish',
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'favorite_color',
                        'label' => 'Favorite color',
                    ),
                ),
            ),
            $settings
        );
        $anonymous_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
                'post_author' => 0,
            )
        );
        SUPER_Data_Access::update_entry_data(
            $anonymous_entry_id,
            array(
                'favorite_color' => array(
                    'name' => 'favorite_color',
                    'value' => 'anonymous-only',
                    'type' => 'text',
                ),
            )
        );
        $anonymous_grant = 'update_contact_entry_' . $form_id . '_' . $form_id . '_0_' . $anonymous_entry_id;
        wp_set_current_user( 0 );
        $listing_output = SUPER_Listings::super_listings_func(
            array(
                'id' => $form_id,
                'list' => 1,
            )
        );
        $this->assertStringNotContainsString( 'SUPER.frontEndListing.editEntry', $listing_output );
        $anonymous_modal = $this->render_listing_modal( array(
            'action' => 'super_listings_edit_entry',
            'entry_id' => $anonymous_entry_id,
            'form_id' => $form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_entry_' . $form_id . '_0' ),
        ) );
        $this->assertStringNotContainsString( 'name="hidden_contact_entry_id"', $anonymous_modal );
        $this->assertFalse( SUPER_Common::getClientData( $anonymous_grant ) );
        $this->assertArrayNotHasKey( 'super_form_entry_access_' . $anonymous_entry_id, $_COOKIE );

        $selected_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
                'post_author' => $owner_id,
            )
        );
        $fallback_entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
                'post_author' => $owner_id,
            )
        );
        SUPER_Data_Access::update_entry_data(
            $selected_entry_id,
            array(
                'favorite_color' => array(
                    'name' => 'favorite_color',
                    'value' => 'owner-selected',
                    'type' => 'text',
                ),
            )
        );
        SUPER_Data_Access::update_entry_data(
            $fallback_entry_id,
            array(
                'favorite_color' => array(
                    'name' => 'favorite_color',
                    'value' => 'owner-fallback',
                    'type' => 'text',
                ),
            )
        );
        $grant = 'update_contact_entry_' . $form_id . '_' . $form_id . '_0_' . $selected_entry_id;
        $expiration = time() + HOUR_IN_SECONDS;
        $session_token = WP_Session_Tokens::get_instance($owner_id)->create($expiration);
        $_COOKIE[LOGGED_IN_COOKIE] = wp_generate_auth_cookie($owner_id, $expiration, 'logged_in', $session_token);
        wp_set_current_user( $owner_id );
        try {
            $owner_listing = SUPER_Listings::super_listings_func(
                array(
                    'id' => $form_id,
                    'list' => 1,
                )
            );
            $this->assertStringContainsString( 'SUPER.frontEndListing.editEntry', $owner_listing );
            $owner_modal = $this->render_listing_modal( array(
                'action' => 'super_listings_edit_entry',
                'entry_id' => $selected_entry_id,
                'form_id' => $form_id,
                'list_id' => 0,
                'nonce' => wp_create_nonce( 'super_listings_entry_' . $form_id . '_0' ),
            ) );
            $this->assertRenderedInputValue( $owner_modal, 'hidden_contact_entry_id', $selected_entry_id );
            $this->assertStringContainsString( 'owner-selected', $owner_modal );
            $this->assertStringNotContainsString( 'owner-fallback', $owner_modal );
            $this->assertSame( SUPER_Common::current_entry_update_grant_value(), SUPER_Common::getClientData( $grant ) );
            $this->assertTrue(
                $this->invoke_ajax_private(
                    'submission_entry_update_is_authorized',
                    array( $selected_entry_id, '0', $form_id, $settings, $form_id, true )
                )
            );
        } finally {
            unset( $_COOKIE[LOGGED_IN_COOKIE] );
        }
    }


    public function test_specific_forms_query_scope_applies_to_initial_and_count_queries() {
        global $wpdb;
        $administrator_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $administrator_id );
        $retrieved_form_id = $this->create_form( 'publish' );
        $list = array(
            'enabled' => 'true',
            'display' => array(
                'enabled' => 'true',
                'user_roles' => 'administrator',
                'user_ids' => '',
                'message' => '',
            ),
            'retrieve' => 'specific_forms',
            'form_ids' => (string) $retrieved_form_id,
            'limit' => 13,
            'custom_columns' => array(
                'enabled' => 'true',
                'columns' => array(
                    $this->query_test_column( 'Favorite color', 'favorite_color' ),
                ),
            ),
        );
        $host_form_id = $this->create_form(
            'publish',
            array(),
            array( '_listings' => array( 'lists' => array( $list ) ) ),
            $administrator_id
        );
        $queries = $this->capture_listing_queries( $host_form_id, array() );
        $scope = $wpdb->prepare( ' AND post.post_parent IN (%d)', $retrieved_form_id );
        $this->assertStringContainsString( $scope, $queries['ordered'] );
        $this->assertCount( 2, $queries['counts'] );
        foreach( $queries['counts'] as $query ) {
            $this->assertStringContainsString( $scope, $query );
        }
    }

    public function test_public_listing_query_prepares_like_and_having_values() {
        global $wpdb;
        $form_id = $this->create_query_test_listing();
        $title = "100%_match' OR '1'='1";
        $pdf = "invoice%_' UNION SELECT";
        $color = "blue%_' OR 1=1";
        $food = "pie_' UNION SELECT";
        $query = $this->capture_entries_query(
            $form_id,
            array(
                'fc_post_title' => $title,
                'fc_generated_pdf' => $pdf,
                'fc__favorite_color' => $color,
                'fc__favorite_food' => $food,
                'sc' => '_favorite_color',
                'sm' => 'a'
            )
        );

        $this->assertStringContainsString(
            $wpdb->remove_placeholder_escape( $wpdb->prepare('post.post_title LIKE %s', '%' . $wpdb->esc_like($title) . '%') ),
            $query
        );
        $this->assertStringContainsString(
            $wpdb->remove_placeholder_escape( $wpdb->prepare('pdfFileName LIKE %s', '%' . $wpdb->esc_like($pdf) . '%') ),
            $query
        );
        $this->assertStringContainsString(
            $wpdb->remove_placeholder_escape( $wpdb->prepare('filterValue_1 LIKE %s', '%' . $wpdb->esc_like($color) . '%') ),
            $query
        );
        $this->assertStringContainsString(
            $wpdb->remove_placeholder_escape( $wpdb->prepare('filterValue_2 LIKE %s', '%' . $wpdb->esc_like($food) . '%') ),
            $query
        );
        $this->assertStringContainsString( 'AS filterValue_1', $query );
        $this->assertStringContainsString( 'AS filterValue_2', $query );
        $this->assertStringContainsString( 'ORDER BY orderValue ASC', $query );
    }

    public function test_public_listing_query_ignores_invalid_or_unconfigured_query_controls() {
        $form_id = $this->create_query_test_listing();
        $query = $this->capture_entries_query(
            $form_id,
            array(
                'fc_entry_date' => "2024-01-01' OR date_marker -- ;2024-12-31",
                'fc_entry_status' => "super_unread' OR status_marker",
                'fc_wc_order' => '12 OR wc_id_marker',
                'fc_author_id' => '19',
                'fc__ignored_type' => 'unsupported_type_marker',
                'fc_not_configured' => 'unknown_filter_marker',
                'sc' => 'post.post_date, sort_marker',
                'sm' => 'ASC, direction_marker',
                'limit' => '5 UNION limit_marker',
                'sfp' => '2 OR page_marker'
            )
        );

        foreach(
            array(
                'date_marker',
                'status_marker',
                'wc_id_marker',
                'unsupported_type_marker',
                'unknown_filter_marker',
                'sort_marker',
                'direction_marker',
                'limit_marker',
                'page_marker'
            ) as $marker
        ){
            $this->assertStringNotContainsString( $marker, $query );
        }
        $this->assertStringNotContainsString( 'post.post_author = "19"', $query );
        $this->assertStringNotContainsString( 'post.post_author = 19', $query );
        $this->assertStringContainsString( 'ORDER BY post.post_date DESC', $query );
        $this->assertStringContainsString( 'LIMIT 13 OFFSET 0', $query );
    }

    public function test_public_listing_query_rejects_invalid_or_reversed_dates() {
        $form_id = $this->create_query_test_listing();
        $invalid_query = $this->capture_entries_query(
            $form_id,
            array( 'fc_entry_date' => '2024-02-30' )
        );
        $reversed_query = $this->capture_entries_query(
            $form_id,
            array( 'fc_entry_date' => '2024-12-31;2024-01-01' )
        );

        $this->assertStringNotContainsString( '2024-02-30', $invalid_query );
        $this->assertStringNotContainsString( '2024-12-31', $reversed_query );
        $this->assertStringNotContainsString( '2024-01-01', $reversed_query );
    }

    public function test_public_listing_query_preserves_valid_typed_filters_and_sorting() {
        global $wpdb;
        $form_id = $this->create_query_test_listing( true );
        $statuses = SUPER_Settings::get_entry_statuses();
        unset($statuses['']);
        $status_keys = array_keys($statuses);
        $status = (string)reset($status_keys);
        $this->assertNotSame( '', $status, 'An entry status is required for this regression.' );
        $query = $this->capture_entries_query(
            $form_id,
            array(
                'fc_entry_date' => '2024-01-01;2024-12-31',
                'fc_entry_status' => $status,
                'fc_wc_order' => '#42',
                'fc_author_id' => '7',
                'sc' => 'author_id',
                'sm' => 'a',
                'limit' => '7',
                'sfp' => '2'
            )
        );

        $this->assertStringContainsString(
            $wpdb->prepare('DATE(post.post_date) BETWEEN %s AND %s', '2024-01-01', '2024-12-31'),
            $query
        );
        $this->assertStringContainsString(
            $wpdb->prepare('entry_status.meta_value = %s', $status),
            $query
        );
        $this->assertStringContainsString( $wpdb->prepare('wc_order.ID = %d', 42), $query );
        $this->assertStringContainsString( $wpdb->prepare('post.post_author = %d', 7), $query );
        $this->assertStringContainsString( 'ORDER BY post.post_author ASC', $query );
        $this->assertStringContainsString( 'LIMIT 7 OFFSET 7', $query );
    }

}
