<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Super_Forms_Upload_Consumers_Security extends Super_Forms_Upload_Security_Test_Case {
    private $mapped_user_ids = array();

    public function tear_down() {
        $this->invoke_register_login( 'clear_user_meta_bridge' );
        wp_set_current_user( 0 );
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach( array_unique( $this->mapped_user_ids ) as $user_id ) {
            if( get_userdata( $user_id )!==false ) wp_delete_user( $user_id );
        }
        parent::tear_down();
    }

    private function invoke_register_login( $method, $arguments=array() ) {
        $reflection = new ReflectionMethod( 'SUPER_Register_Login', $method );
        $reflection->setAccessible( true );
        return $reflection->invokeArgs( null, $arguments );
    }

    private function store_retained_attachment( $form_id, $attachment_id, $stored ) {
        $entry_id = self::factory()->post->create( array(
            'post_type' => 'super_contact_entry', 'post_status' => 'super_read', 'post_parent' => $form_id,
        ) );
        wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => $entry_id ) );
        update_post_meta( $entry_id, '_super_contact_entry_data', array( 'documents' =>
            array( 'type' => 'files', 'files' => array( $stored ) ) ) );
        return $entry_id;
    }

    private function assert_legacy_retention_without_cleanup( $form_id, $entry_id, $stored, $attached_file ) {
        $element = $this->file_element( 'documents' );
        $owned = false;
        $rebuilt = $this->invoke_ajax_private( 'rebuild_retained_entry_file', array(
            $stored, $entry_id, $form_id, 'documents', $element['data'], array(), 0, 'documents', &$owned,
        ) );
        $this->assertIsArray( $rebuilt );
        $this->assertSame( basename( $attached_file ), $rebuilt['value'] );
        $this->assertSame( $attached_file, $owned['file'] );
        $this->assertFalse( $owned['cleanup_authority'] );
        $this->assertFalse( $this->invoke_ajax_private( 'retained_owned_upload_is_current', array( $owned ) ) );
        $this->assertTrue( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, $entry_id ) ) );
        $client = array( 'value' => $stored['value'], 'url' => $stored['url'], 'retention_token' => 'entry' );
        $matched_owned = false;
        $matched = $this->invoke_ajax_private( 'resolve_retained_entry_file', array(
            $client, $entry_id, 'documents', 'documents', &$matched_owned,
        ) );
        $this->assertSame( $rebuilt, $matched );
        $this->assertSame( $attached_file, $matched_owned['file'] );
        $this->assertFalse( $matched_owned['cleanup_authority'] );
        $this->assertTrue( $this->invoke_ajax_private( 'delete_finalized_owned_uploads', array(
            array( $matched_owned ), $entry_id, $form_id,
        ) ) );
        $this->assertFileExists( $attached_file );
        $this->assertSame( 'attachment', get_post_type( $stored['attachment'] ) );
        $forged_client = $client;
        $forged_client['value'] = 'not-the-stored-client-name.jpg';
        $this->assertFalse( $this->invoke_ajax_private( 'resolve_retained_entry_file', array(
            $forged_client, $entry_id, 'documents',
        ) ) );
    }

    public function test_legacy_sanitized_and_uniquified_client_names_remain_selectable_without_cleanup() {
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents' ) ) );
        foreach( array( array( 'My Photo.jpg', 'My-Photo.jpg' ), array( 'photo.jpg', 'photo-1.jpg' ) ) as $names ) {
            $created = $this->create_processed_image_upload( $form_id, 64, 48, 'jpg', 1, true );
            $attached = $created['root'] . '/' . $names[1];
            $this->assertTrue( rename( $created['file'], $attached ) );
            update_attached_file( $created['attachment'], $attached );
            $stored = array( 'value' => $names[0], 'name' => 'documents', 'type' => $created['mime'],
                'url' => wp_get_attachment_url( $created['attachment'] ), 'attachment' => $created['attachment'] );
            $entry_id = $this->store_retained_attachment( $form_id, $created['attachment'], $stored );
            $this->assert_legacy_retention_without_cleanup( $form_id, $entry_id, $stored, $attached );
        }
    }

    public function test_legacy_processed_attachment_with_removed_original_retains_attached_identity_without_cleanup() {
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents' ) ) );
        $created = $this->create_processed_image_upload( $form_id, 3000, 2000, 'jpg', 1, true );
        $attached = wp_normalize_path( realpath( get_attached_file( $created['attachment'] ) ) );
        $stored = SUPER_Ajax::owned_upload_file_record( $created['owned'] );
        $entry_id = $this->store_retained_attachment( $form_id, $created['attachment'], $stored );
        $this->assertTrue( unlink( $created['file'] ) );
        clearstatcache();
        $this->assert_legacy_retention_without_cleanup( $form_id, $entry_id, $stored, $attached );
        $this->assertFileDoesNotExist( $created['file'] );
    }

    public function test_invalid_original_metadata_cannot_grant_original_or_cleanup_authority_on_legacy_retention() {
        $element = $this->file_element( 'documents' );
        $form_id = $this->create_form( 'publish', array( $element ) );
        $created = $this->create_processed_image_upload( $form_id, 3000, 2000, 'jpg', 1, true );
        $attached = wp_normalize_path( realpath( get_attached_file( $created['attachment'] ) ) );
        $other = $created['root'] . '/other.jpg';
        $this->assertTrue( copy( $created['file'], $other ) );
        $stored = SUPER_Ajax::owned_upload_file_record( $created['owned'] );
        $entry_id = $this->store_retained_attachment( $form_id, $created['attachment'], $stored );
        foreach( array( '../owned/camera.jpg', $created['file'], 'other.jpg', array( 'camera.jpg' ) ) as $forged ) {
            $metadata = $created['metadata'];
            $metadata['original_image'] = $forged;
            wp_update_attachment_metadata( $created['attachment'], $metadata );
            $this->assert_legacy_retention_without_cleanup( $form_id, $entry_id, $stored, $attached );
            $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $created['owned'], $entry_id ) ) );
            $path_selector = $stored;
            $path_selector['value'] = '../owned/' . $stored['value'];
            $this->assertFalse( $this->invoke_ajax_private( 'rebuild_retained_entry_file', array(
                $path_selector, $entry_id, $form_id, 'documents', $element['data'], array(), 0,
            ) ) );
            $this->assertFileExists( $other );
            $this->assertFileExists( $created['file'] );
        }
        wp_update_attachment_metadata( $created['attachment'], $created['metadata'] );
    }

    private function account_action_atts( $action, $form_id, $record, $user_id=0 ) {
        $settings = array(
            'register_login_action' => $action,
            'register_login_user_id_update' => 'true',
            'register_login_register_not_logged_in' => '',
            'register_login_not_logged_in_msg' => 'Please log in.',
            'register_login_show_toolbar' => '',
            'register_user_role' => 'subscriber',
            'register_update_user_role' => '_super_keep_existing_role',
            'register_login_user_meta' => $action==='register' ? 'documents|sf_camera_attachment' : '',
            'register_login_update_user_meta' => $action==='update' ? 'documents|sf_camera_attachment' : '',
            'register_login_action_skip_register' => '',
            'register_login_activation' => 'none',
            'register_user_signup_status' => 'active',
            'register_send_approve_email' => '',
            'register_login_multisite_enabled' => '',
        );
        $data = array( 'documents' => array( 'type' => 'files', 'files' => array( $record ) ) );
        if( $action==='update' ) {
            $data['user_id'] = array( 'type' => 'text', 'value' => (string) $user_id );
        } else {
            $login = 'sf_camera_' . str_replace( '-', '', wp_generate_uuid4() );
            $data['user_login'] = array( 'type' => 'text', 'value' => $login );
            $data['user_email'] = array( 'type' => 'email', 'value' => $login . '@example.test' );
            $data['user_pass'] = array( 'type' => 'password', 'value' => 'Camera-registration-password-123!' );
        }
        $post = array( 'action' => 'super_submit_form', 'form_id' => (string) $form_id, 'data' => wp_json_encode( $data ) );
        return array( 'settings' => $settings, 'data' => $data, 'post' => $post, 'entry_id' => 0, 'attachments' => array() );
    }

    public function test_register_login_custom_meta_maps_scaled_rotated_and_unprocessed_originals_on_registration_and_update() {
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents' ) ) );
        foreach( array( array( 3000, 2000, 1 ), array( 2000, 1500, 6 ), array( 64, 48, 1 ) ) as $case ) {
            $created = $this->create_processed_image_upload( $form_id, $case[0], $case[1], 'jpg', $case[2], true );
            $record = SUPER_Ajax::owned_upload_file_record( $created['owned'] );
            // Fail as an assertion on the old reviewed head, before a dying public
            // callback can terminate the runner. The fixed path is exercised below.
            $this->assertSame( $created['attachment'], $this->invoke_register_login( 'resolve_custom_meta_value', array(
                'documents', array( 'documents' => array( 'type' => 'files', 'files' => array( $record ) ) ), array(), $form_id,
            ) ) );
            foreach( array( 'register', 'update' ) as $action ) {
                wp_set_current_user( 0 );
                $user_id = 0;
                if( $action==='update' ) {
                    $user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
                    $this->mapped_user_ids[] = $user_id;
                    wp_set_current_user( $user_id );
                }
                $atts = $this->account_action_atts( $action, $form_id, $record, $user_id );
                $_POST = $_REQUEST = $atts['post'];
                SUPER_Register_Login::before_sending_email( $atts );
                if( $action==='register' ) {
                    $user = get_user_by( 'login', $atts['data']['user_login']['value'] );
                    $this->assertInstanceOf( 'WP_User', $user );
                    $user_id = $user->ID;
                    $this->mapped_user_ids[] = $user_id;
                }
                SUPER_Register_Login::before_email_success_msg( $atts );
                $this->assertSame( (string) $created['attachment'], get_user_meta( $user_id, 'sf_camera_attachment', true ) );
                $this->assertFalse( $this->invoke_register_login( 'consume_deferred_user_action', array( $atts['post'] ) ) );
            }
        }
    }

    public function test_register_login_rejects_forged_original_metadata_and_mismatched_values_without_mapping_user_meta() {
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents' ) ) );
        $user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $this->mapped_user_ids[] = $user_id;
        wp_set_current_user( $user_id );
        foreach( array( array( 3000, 2000, 1 ), array( 2000, 1500, 6 ) ) as $case ) {
            $created = $this->create_processed_image_upload( $form_id, $case[0], $case[1], 'jpg', $case[2], true );
            $record = SUPER_Ajax::owned_upload_file_record( $created['owned'] );
            foreach( array( '../owned/camera.jpg', $created['file'], 'other.jpg', array( 'camera.jpg' ), 'value-mismatch' ) as $forged ) {
                $candidate = $record;
                $metadata = $created['metadata'];
                if( $forged==='value-mismatch' ) {
                    $candidate['value'] = 'unrelated-original.jpg';
                } else {
                    $metadata['original_image'] = $forged;
                }
                wp_update_attachment_metadata( $created['attachment'], $metadata );
                $atts = $this->account_action_atts( 'update', $form_id, $candidate, $user_id );
                $this->assertInstanceOf( 'WP_Error', $this->invoke_register_login( 'resolve_custom_meta_value', array(
                    'documents', $atts['data'], $atts['settings'], $form_id,
                ) ) );
                update_user_meta( $user_id, 'sf_camera_attachment', 'unchanged' );
                $_POST = $_REQUEST = $atts['post'];
                SUPER_Register_Login::before_sending_email( $atts );
                $this->assert_handler_rejected_with( static function() use ( $atts ) {
                    SUPER_Register_Login::before_email_success_msg( $atts );
                }, 'Invalid file upload.' );
                $this->assertSame( 'unchanged', get_user_meta( $user_id, 'sf_camera_attachment', true ) );
                $this->invoke_register_login( 'clear_user_meta_bridge' );
                $this->assertFileExists( $created['file'] );
                wp_update_attachment_metadata( $created['attachment'], $created['metadata'] );
            }
        }
    }
}
