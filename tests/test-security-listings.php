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
        $_GET = $this->original_get;
        parent::tear_down();
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
        // check_ajax_referer() reads the nonce from $_REQUEST, as a real admin-ajax POST fills it.
        $_REQUEST = $_POST;
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
        $_REQUEST = $_POST;
        $wrong_scope = $this->run_dying_handler( array( 'SUPER_Ajax', 'listings_delete_entry' ) );
        $this->assertSame( 0, $wrong_scope['status'], $wrong_scope['output'] );
        // An existing entry outside the list's retrieval scope is refused with the same
        // permission denial the other scope tests in this class expect; "No entry found"
        // is reserved for IDs that are not contact entries at all.
        $this->assertStringContainsString( 'permission to delete', strtolower( $wrong_scope['output'] ) );
        $this->assertSame( 'super_contact_entry', get_post_type( $other_entry_id ) );

        $_POST['entry_id'] = $owned_entry_id;
        $_REQUEST = $_POST;
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

        $_REQUEST = $_POST;

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

            // A cross-form listing edit submits the entry's own form (form-blank-page-template
            // renders the entry's parent form) with the host listing form as listing_form_id.
            $this->assertTrue(
                $this->invoke_ajax_private(
                    'submission_entry_update_is_authorized',
                    array( $entry_id, '0', $source_form_id, $settings, $host_form_id )
                ),
                $retrieve . ' must authorize an in-scope cross-form entry update.'
            );

            $_POST = array(
                'entry_id' => $entry_id,
                'form_id' => $host_form_id,
                'list_id' => 0,
                'nonce' => wp_create_nonce( 'super_listings_delete_entry_' . $host_form_id . '_0' ),
            );
            $_REQUEST = $_POST;
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
        $this->assertFalse(
            $this->invoke_ajax_private(
                'submission_entry_update_is_authorized',
                array( $outside_entry_id, '0', $outside_form_id, $specific_settings, $specific_host_form_id )
            ),
            'A cross-form entry outside specific_forms must remain unauthorized.'
        );

        $_POST = array(
            'entry_id' => $outside_entry_id,
            'form_id' => $specific_host_form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_delete_entry_' . $specific_host_form_id . '_0' ),
        );
        $_REQUEST = $_POST;
        $denied = $this->run_dying_handler( array( 'SUPER_Ajax', 'listings_delete_entry' ) );
        $this->assertSame( 0, $denied['status'], $denied['output'] );
        $this->assertStringContainsString( 'permission to delete', strtolower( $denied['output'] ) );
        $this->assertSame( 'super_contact_entry', get_post_type( $outside_entry_id ) );
    }








}
