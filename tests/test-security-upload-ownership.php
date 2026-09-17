<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Super_Forms_Upload_Ownership_Security extends Super_Forms_Upload_Security_Test_Case {
    public function test_forged_generated_pdf_is_rejected_before_any_file_aware_filter_or_action() {
        $settings = array( '_pdf' => array( 'generate' => 'true' ) );
        $form_id = $this->create_form( 'publish', array(), $settings );
        $data = $this->generated_pdf_data( 'not a pdf' );

        $materialized = $this->invoke_ajax_private( 'materialize_generated_pdf', array( $data, $form_id, $settings ) );
        $this->assertInstanceOf( 'WP_Error', $materialized );

        list( $parent, $root ) = $this->create_temporary_root();
        $marker = trailingslashit( $parent ) . 'extension-hook-observed-forgery';
        $data_filter = static function( $filtered ) use ( $marker ) {
            $observed = wp_json_encode( $filtered );
            if( strpos( $observed, '_generated_pdf_file' ) !== false || strpos( $observed, 'datauristring' ) !== false ) {
                file_put_contents( $marker, 'data-filter forged carrier', FILE_APPEND );
            }
            return $filtered;
        };
        $settings_filter = static function( $filtered, $atts ) use ( $marker ) {
            $observed = wp_json_encode( $atts );
            if( strpos( $observed, '_generated_pdf_file' ) !== false || strpos( $observed, 'datauristring' ) !== false ) {
                file_put_contents( $marker, 'settings-filter forged carrier', FILE_APPEND );
            }
            return $filtered;
        };
        $action = static function( $atts ) use ( $marker ) {
            $observed = wp_json_encode( $atts );
            if( strpos( $observed, '_generated_pdf_file' ) !== false || strpos( $observed, 'datauristring' ) !== false ) {
                file_put_contents( $marker, 'action forged carrier', FILE_APPEND );
            }
        };
        $this->add_upload_filter( 'super_before_sending_email_data_filter', $data_filter, 10, 2 );
        $this->add_upload_filter( 'super_before_submit_form_settings_filter', $settings_filter, 10, 2 );
        $this->add_upload_filter( 'super_before_sending_email_hook', $action, 10, 1 );

        $this->set_request( $form_id, $data );
        $callback = static function() use ( $settings ) {
            SUPER_Ajax::submit_form_checks( $settings, false );
        };
        $this->assert_handler_rejected_with( $callback, 'Invalid file upload rejected.' );
        $this->assertFileDoesNotExist( $marker, 'No extension callback may observe a forged generated-PDF carrier.' );
    }

    public function test_valid_generated_pdf_is_materialized_and_raw_data_is_removed_before_hooks() {
        $settings = array( '_pdf' => array( 'generate' => 'true', 'filename' => 'stored-invoice.pdf', 'emailLabel' => 'Stored PDF' ) );
        $form_id = $this->create_form( 'publish', array(), $settings );
        $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF\n";
        $data = $this->generated_pdf_data( $pdf );
        $seen_filter_data = null;
        $seen_action_data = null;

        $data_filter = static function( $filtered ) use ( &$seen_filter_data ) {
            $seen_filter_data = $filtered;
            return $filtered;
        };
        $action = static function( $atts ) use ( &$seen_action_data ) {
            $seen_action_data = $atts['data'];
        };
        $this->add_upload_filter( 'super_before_sending_email_data_filter', $data_filter, 10, 2 );
        $this->add_upload_filter( 'super_before_sending_email_hook', $action, 10, 1 );
        $this->set_request( $form_id, $data );

        $atts = SUPER_Ajax::submit_form_checks( $settings, false );
        if( isset( $atts['owned_files'] ) && is_array( $atts['owned_files'] ) ) {
            foreach( $atts['owned_files'] as $owned_file ) {
                $this->track_owned_cleanup( $owned_file );
            }
        }
        $this->assertCount( 1, $atts['owned_files'] );
        $this->assertTrue( $this->invoke_ajax_private( 'owned_upload_is_current', array( $atts['owned_files'][0], 0 ) ) );

        foreach( array( $seen_filter_data, $seen_action_data, $atts['data'] ) as $observed ) {
            $this->assertTrue( is_array( $observed ) );
            $this->assertArrayHasKey( '_generated_pdf_file', $observed );
            $this->assertCount( 1, $observed['_generated_pdf_file']['files'] );
            $server_file = $observed['_generated_pdf_file']['files'][0];
            $this->assertArrayNotHasKey( 'datauristring', $server_file );
            $this->assertSame( 'application/pdf', $server_file['type'] );
            $this->assertSame( 'Stored PDF', $server_file['label'] );
            $this->assertSame( 'stored-invoice.pdf', $server_file['name'] );
            $this->assertSame( strlen($pdf), $server_file['size'] );
            $this->assertSame( 'pdf', strtolower( pathinfo( $server_file['value'], PATHINFO_EXTENSION ) ) );
        }
    }

    public function test_attachment_marker_parent_mime_path_and_root_drift_revoke_ownership() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id, 'documents', true );
        $owned = $created['owned'];
        $attachment_id = $created['attachment'];
        $this->assertTrue( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );

        delete_post_meta( $attachment_id, 'super-forms-form-upload-file' );
        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        add_post_meta( $attachment_id, 'super-forms-form-upload-file', true );

        update_post_meta( $attachment_id, '_super_forms_upload_form_id', $form_id + 1 );
        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        update_post_meta( $attachment_id, '_super_forms_upload_form_id', $form_id );

        update_post_meta( $attachment_id, '_super_forms_upload_field', 'another_field' );
        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        update_post_meta( $attachment_id, '_super_forms_upload_field', 'documents' );

        wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => $form_id ) );
        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => 0 ) );

        wp_update_post( array( 'ID' => $attachment_id, 'post_mime_type' => 'text/plain' ) );
        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        wp_update_post( array( 'ID' => $attachment_id, 'post_mime_type' => 'image/jpeg' ) );

        $original_attached_meta = get_post_meta( $attachment_id, '_wp_attached_file', true );
        $other_file = trailingslashit( $created['root'] ) . 'other.jpg';
        file_put_contents( $other_file, 'other' );
        update_post_meta( $attachment_id, '_wp_attached_file', $other_file );
        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        update_post_meta( $attachment_id, '_wp_attached_file', $original_attached_meta );

        list( $other_parent, $other_root ) = $this->create_temporary_root();
        $root_drift = $owned;
        $root_drift['allowed_root'] = $other_root;
        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $root_drift, 0 ) ) );

        $lexical_drift = $owned;
        $lexical_drift['allowed_root'] = trailingslashit( dirname( $created['root'] ) ) . basename( $created['root'] ) . '/../' . basename( $created['root'] );
        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $lexical_drift, 0 ) ) );
        $this->assertTrue( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
    }

    public function test_symlink_replacement_revokes_owned_file_and_receipt_authority() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id, 'documents' );
        $token = $this->issue_receipt( $created['owned'] );
        $this->assertTrue( is_array(
            $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) )
        ) );

        $backup = trailingslashit( $created['root'] ) . 'symlink-target.jpg';
        $this->assertTrue( rename( $created['file'], $backup ) );
        $this->assertTrue( symlink( $backup, $created['file'] ) );
        $this->assertFalse(
            $this->invoke_ajax_private( 'owned_upload_is_current', array( $created['owned'], 0 ) ),
            'Replacing the exact owned path with a symlink must revoke ownership.'
        );
        $this->assertFalse(
            $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) )
        );

        unlink( $created['file'] );
        $this->assertTrue( rename( $backup, $created['file'] ) );
        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ), 'Inspection must not consume the restored exact receipt.' );
        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
        $this->assertCount( 1, $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) ) );
    }

    public function test_receipt_inspection_revalidates_attachment_marker_and_parent() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id, 'documents', true );
        $token = $this->issue_receipt( $created['owned'] );
        $this->assertTrue( is_array( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) ) ) );

        delete_post_meta( $created['attachment'], 'super-forms-form-upload-file' );
        $this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) ) );
        add_post_meta( $created['attachment'], 'super-forms-form-upload-file', true );

        wp_update_post( array( 'ID' => $created['attachment'], 'post_parent' => $form_id ) );
        $this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) ) );
        wp_update_post( array( 'ID' => $created['attachment'], 'post_parent' => 0 ) );

        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
        $this->assertCount( 1, $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) ) );
    }

    public function test_exact_deletion_refuses_root_escape_aliases_symlinks_protected_roots_and_partial_trees() {
        list( $parent, $root ) = $this->create_temporary_root();
        $exact = trailingslashit( $root ) . 'exact.txt';
        file_put_contents( $exact, 'delete me' );
        $this->assertTrue( SUPER_Common::delete_file( $exact, $root ) );
        $this->assertFileDoesNotExist( $exact );

        $outside = trailingslashit( $parent ) . 'outside.txt';
        file_put_contents( $outside, 'preserve me' );
        $this->assertFalse( SUPER_Common::delete_file( $outside, $root ) );
        $this->assertFalse( SUPER_Common::delete_file( trailingslashit( $root ) . '../outside.txt', $root ) );
        $this->assertFileExists( $outside );
        $this->assertFalse( SUPER_Common::delete_file( $root, $root ) );

        $link = trailingslashit( $root ) . 'outside-link';
        $this->assertTrue( symlink( $outside, $link ) );
        $this->assertFalse( SUPER_Common::delete_file( $link, $root ) );
        $this->assertFileExists( $outside );
        unlink( $link );

        $valid_dir = trailingslashit( $root ) . 'valid-dir';
        wp_mkdir_p( $valid_dir );
        file_put_contents( trailingslashit( $valid_dir ) . 'child.txt', 'delete child' );
        $this->assertTrue( SUPER_Common::delete_dir( $valid_dir, $root ) );
        $this->assertDirectoryDoesNotExist( $valid_dir );

        $blocked_dir = trailingslashit( $root ) . 'blocked-dir';
        wp_mkdir_p( $blocked_dir );
        $regular = trailingslashit( $blocked_dir ) . 'a-regular.txt';
        $blocked_link = trailingslashit( $blocked_dir ) . 'z-outside-link';
        file_put_contents( $regular, 'must survive preflight' );
        $this->assertTrue( symlink( $outside, $blocked_link ) );
        $this->assertFalse( SUPER_Common::delete_dir( $blocked_dir, $root ) );
        $this->assertFileExists( $regular, 'The complete tree must validate before any child is removed.' );
        $this->assertFileExists( $outside );
        unlink( $blocked_link );

        $this->assertFalse( SUPER_Common::delete_dir( $root, $root ) );
        $this->assertTrue( SUPER_Common::delete_target_is_protected( WP_CONTENT_DIR ) );
        $this->assertDirectoryExists( WP_CONTENT_DIR );
    }

    public function test_preseeded_custom_retained_path_cannot_become_cleanup_authority() {
        list( $parent, $root ) = $this->create_temporary_root( true );
        $slot = trailingslashit( $root ) . '1234567890123';
        $this->assertTrue( wp_mkdir_p( $slot ) );
        $filename = trailingslashit( $slot ) . 'retained.png';
        $this->assertNotFalse( file_put_contents( $filename, $this->valid_png_bytes() ) );
        $settings = array(
            'csrf_check' => 'false',
            'file_upload_dir' => '../' . basename( $parent ) . '/' . basename( $root ),
        );
        $elements = array( $this->file_element( 'documents' ) );
        $form_id = $this->create_form( 'publish', $elements, $settings );
        $entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_read',
                'post_parent' => $form_id,
            )
        );
        $route = '1234567890123/retained.png';
        $stored = array(
            'value' => 'retained.png',
            'name' => 'documents',
            'type' => 'image/png',
            'url' => trailingslashit( get_option( 'siteurl' ) ) . 'sfgtfi/' . $route,
            'subdir' => '/' . $route,
        );
        update_post_meta(
            $entry_id,
            '_super_contact_entry_data',
            array(
                'documents' => array(
                    'type' => 'files',
                    'files' => array( $stored ),
                ),
            )
        );
        $this->assertFalse(
            $this->invoke_ajax_private(
                'rebuild_retained_entry_file',
                array( $stored, $entry_id, $form_id, 'documents', $elements[0], $settings, 0 )
            )
        );
        $client = array(
            'value' => $stored['value'],
            'url' => $stored['url'],
            'retention_token' => 'entry',
        );
        $resolved = $this->invoke_ajax_private(
            'resolve_submission_files',
            array(
                array(
                    'documents' => array(
                        'type' => 'files',
                        'files' => array( $client ),
                    ),
                ),
                $form_id,
                $elements,
                $entry_id
            )
        );
        $this->assertInstanceOf( 'WP_Error', $resolved );
        $this->assertFileExists( $filename );
    }

    public function test_receipt_owned_custom_retention_requires_exact_proof_and_survives_cleanup() {
        list( $parent, $root ) = $this->create_temporary_root( true );
        $slot_name = '1234567890123';
        $slot = trailingslashit( $root ) . $slot_name;
        $this->assertFileDoesNotExist( $slot );
        $this->assertTrue( wp_mkdir_p( $slot ) );
        $filename = trailingslashit( $slot ) . 'retained.png';
        $this->assertNotFalse( file_put_contents( $filename, $this->valid_png_bytes() ) );
        $settings = array(
            'csrf_check' => 'false',
            'file_upload_dir' => '../' . basename( $parent ) . '/' . basename( $root ),
        );
        $elements = array( $this->file_element( 'documents', array( 'extensions' => 'png' ) ) );
        $form_id = $this->create_form( 'publish', $elements, $settings );
        $entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_read',
                'post_parent' => $form_id,
            )
        );
        $descriptor = SUPER_Forms::resolve_owned_upload_file( $filename, $settings );
        $this->assertTrue( is_array( $descriptor ) );
        // The upload endpoint stores the WordPress subdir verbatim, so a configured
        // parent-relative root yields a leading-slash legacy subdir
        // (includes/class-ajax.php:8282) and that exact string is what the proof HMAC
        // covers (includes/class-ajax.php:6082-6093).
        $subdir = '/../' . basename( $parent ) . '/' . basename( $root ) . '/' . $slot_name . '/retained.png';
        $url = trailingslashit( get_option( 'siteurl' ) ) . 'sfgtfi/__/' . basename( $parent ) . '/' . basename( $root ) . '/' . $slot_name . '/retained.png';
        $owned = $this->invoke_ajax_private(
            'build_owned_upload',
            array(
                $form_id,
                'documents',
                $descriptor['file'],
                $descriptor['mime'],
                $url,
                0,
                $descriptor['root'],
                filesize( $descriptor['file'] ),
                $subdir,
            )
        );
        $stored = $this->invoke_ajax_private( 'owned_upload_file_record', array( $owned ) );
        $this->assertSame( $subdir, $owned['legacy_subdir'] );
        $this->assertSame( $subdir, $stored['subdir'] );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $stored['_super_file_proof'] );
        update_post_meta(
            $entry_id,
            '_super_contact_entry_data',
            array(
                'documents' => array(
                    'type' => 'files',
                    'files' => array( $stored ),
                ),
            )
        );

        $matched_owned = null;
        $rebuild = new ReflectionMethod( 'SUPER_Ajax', 'rebuild_retained_entry_file' );
        $rebuild->setAccessible( true );
        $args = array(
            $stored,
            $entry_id,
            $form_id,
            'documents',
            $elements[0],
            $settings,
            0,
            'documents',
            &$matched_owned,
        );
        $retained = $rebuild->invokeArgs( null, $args );
        $this->assertTrue( is_array( $retained ) );
        $this->assertSame( 'retained', $retained['_super_file_authority'] );
        $this->assertSame( $stored['_super_file_proof'], $retained['_super_file_proof'] );
        $this->assertSame( wp_normalize_path( realpath( $filename ) ), $retained['path'] );
        $this->assertSame( $entry_id, $matched_owned['legacy_entry_id'] );
        $this->assertTrue( $this->invoke_ajax_private( 'cleanup_owned_uploads', array( array( $matched_owned ) ) ) );
        $this->assertFileExists( $filename );

        $tampered = $stored;
        $tampered['url'] .= '?forged=1';
        $tampered_owned = null;
        $tampered_args = array(
            $tampered,
            $entry_id,
            $form_id,
            'documents',
            $elements[0],
            $settings,
            0,
            'documents',
            &$tampered_owned,
        );
        $this->assertFalse( $rebuild->invokeArgs( null, $tampered_args ) );
        $this->assertFileExists( $filename );

        $mutated_entry_data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array( $stored ),
            ),
        );
        $mutated_entry_data['documents']['files'][0]['_super_file_proof'] = str_repeat( '0', 64 );
        update_post_meta( $entry_id, '_super_contact_entry_data', $mutated_entry_data );
        $this->assertFalse(
            $this->invoke_ajax_private(
                'delete_finalized_owned_uploads',
                array( array( $matched_owned ), $entry_id, $form_id )
            )
        );
        $this->assertFileExists( $filename );

        $mutated_entry_data['documents']['files'][0] = $stored;
        update_post_meta( $entry_id, '_super_contact_entry_data', $mutated_entry_data );
        $this->assertTrue(
            $this->invoke_ajax_private(
                'delete_finalized_owned_uploads',
                array( array( $matched_owned ), $entry_id, $form_id )
            )
        );
        $this->assertFileDoesNotExist( $filename );
    }

    public function test_downstream_account_rejection_cleans_consumed_upload_receipt_file() {
        wp_set_current_user( 0 );
        unset( $_COOKIE[ LOGGED_IN_COOKIE ], $_COOKIE['_sfs_id'] );
        $this->bootstrap_shared_anonymous_session();
        $settings = array(
            'csrf_check' => 'false',
            'register_login_action' => 'update',
            'register_login_register_not_logged_in' => '',
            'register_login_not_logged_in_msg' => 'Account authorization rejected.',
        );
        $elements = array($this->file_element('documents'));
        $form_id = $this->create_form( 'publish', $elements, $settings );
        $created = $this->create_owned_upload( $form_id, 'documents' );
        $token = $this->issue_receipt( $created['owned'] );
        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ) );
        // The Register & Login add-on only hooks super_before_sending_email_hook on an
        // ajax request (add-ons/super-forms-register-login/super-forms-register-login.php:207-210),
        // and the WordPress test bootstrap never defines DOING_AJAX, so register the exact
        // public callback the plugin registers for a real super_submit_form request.
        $this->assertTrue( class_exists( 'SUPER_Register_Login' ) );
        $this->add_upload_filter(
            'super_before_sending_email_hook',
            array( 'SUPER_Register_Login', 'before_sending_email' )
        );
        $this->configure_csrf( 'false' );
        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array(array('upload_token'=>$token)),
            ),
        );
        $this->set_request( $form_id, $data );
        $this->assertFileExists( $created['file'] );

        $this->assert_handler_rejected_with(
            static function() use ( $settings ) {
                SUPER_Ajax::submit_form_checks( $settings, false );
            },
            'Account authorization rejected.'
        );
        $this->assertFileDoesNotExist(
            $created['file'],
            'Shutdown cleanup must delete a consumed upload when a downstream account action terminates.'
        );
    }

    public function test_sessionless_submit_publishes_no_session_artifacts_end_to_end() {
        global $wpdb;
        wp_set_current_user( 0 );
        unset( $_COOKIE['_sfs_id'] );
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form(
            'publish',
            array(
                array(
                    'tag' => 'text',
                    'group' => 'form_elements',
                    'data' => array( 'name' => 'favorite_color', 'label' => 'Favorite color' ),
                ),
            ),
            array(
                'csrf_check' => 'false',
                // The stored setting is the literal 'yes' (includes/class-ajax.php:8440).
                'save_contact_entry' => 'yes',
                'send' => 'no',
                'confirm' => 'no',
                'form_show_thanks_msg' => 'true',
                'form_thanks_title' => 'Thanks',
                'form_thanks_description' => 'Done',
                'form_redirect_option' => '',
            )
        );
        $data = array(
            // A stored `text` element's browser carrier type is `var`
            // (includes/class-ajax.php:3577-3579).
            'favorite_color' => array( 'name' => 'favorite_color', 'value' => 'green', 'type' => 'var' ),
        );
        $count_sql = "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s";
        $before_sessions = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $wpdb->esc_like( '_sfsdata_' ) . '%' ) );

        $this->set_submit_request( $form_id, $data );
        $submit = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ), false );
        $this->assertSame( 0, $submit['status'], $submit['output'] );
        $decoded = json_decode( $submit['output'], true );
        $this->assertIsArray( $decoded, $submit['output'] );
        $this->assertFalse( $decoded['error'], $submit['output'] );

        // The submit ran end to end: a contact entry was created. The contact-entry
        // statuses are only registered on admin requests (super-forms.php:333,343), so a
        // frontend-context WP_Query would silently drop them; register them exactly like
        // tests/test-security-contact-entry-export.php:65 does before querying.
        SUPER_Forms::custom_contact_entry_status();
        $entries = get_posts( array(
            'post_type' => 'super_contact_entry',
            'post_parent' => $form_id,
            'post_status' => array( 'super_unread', 'super_read', 'publish' ),
            'fields' => 'ids',
            'posts_per_page' => -1,
        ) );
        $this->assertCount( 1, $entries, $submit['output'] );

        // No browser-session option was published (the unconditional progress
        // clear and the success-message writes are skipped in sessionless mode)...
        $after_sessions = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $wpdb->esc_like( '_sfsdata_' ) . '%' ) );
        $this->assertSame( $before_sessions, $after_sessions, 'A sessionless submit published a browser-session option.' );
        // ...and no entry-access credential grant was issued.
        $entry_access = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $wpdb->esc_like( '_transient__super_form_entry_access_' ) . '%' ) );
        $this->assertSame( 0, $entry_access, 'A sessionless submit issued an entry-access credential.' );
    }
    public function test_cron_cleanup_hook_is_registered_and_reclaims_expired_receipts() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id );
        $token = $this->issue_receipt( $created['owned'] );
        $hash = hash( 'sha256', $token );
        $this->expire_receipt_for_cleanup( $token );
        $this->assertNotFalse(
            has_action( 'super_cleanup_upload_receipt', array( 'SUPER_Ajax', 'cleanup_expired_upload_receipt' ) )
        );
        do_action( 'super_cleanup_upload_receipt', $hash );
        $this->assertFileDoesNotExist( $created['file'] );
        $this->assertFalse( get_option( '_super_upload_receipt_' . $hash, false ) );
        $this->assertFalse( get_option( '_super_upload_receipt_claim_' . $hash, false ) );
    }

    public function test_missed_receipt_cron_is_recovered_by_bounded_fallback() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id );
        $token = $this->issue_receipt( $created['owned'] );
        $hash = hash( 'sha256', $token );
        $receipt = get_option( '_super_upload_receipt_' . $hash, false );

        $this->assertTrue( is_array( $receipt ) );
        $this->assertSame(
            $receipt['expires'] + 1,
            wp_next_scheduled( 'super_cleanup_upload_receipt', array( $hash ) )
        );

        $this->invoke_ajax_private(
            'unschedule_upload_receipt_cleanup',
            array( $hash, $receipt['expires'] )
        );
        $receipt['expires'] = time() - 2;
        update_option( '_super_upload_receipt_' . $hash, $receipt, false );
        $this->assertFalse( wp_next_scheduled( 'super_cleanup_upload_receipt', array( $hash ) ) );

        $missing = '__missing_cleanup_cursor__';
        $previous_cursor = get_option( '_super_upload_receipt_cleanup_cursor', $missing );
        $scanned = 0;
        try {
            update_option( '_super_upload_receipt_cleanup_cursor', 0, false );
            delete_transient( '_super_upload_receipt_cleanup_sweep' );
            $scanned = SUPER_Ajax::cleanup_expired_upload_receipts_fallback();
        } finally {
            delete_transient( '_super_upload_receipt_cleanup_sweep' );
            if( $previous_cursor === $missing ) {
                delete_option( '_super_upload_receipt_cleanup_cursor' );
            } else {
                update_option( '_super_upload_receipt_cleanup_cursor', $previous_cursor, false );
            }
        }

        $this->assertGreaterThanOrEqual( 1, $scanned );
        $this->assertLessThanOrEqual( 20, $scanned );
        $this->assertFileDoesNotExist( $created['file'] );
        $this->assertFalse( get_option( '_super_upload_receipt_' . $hash, false ) );
        $this->assertFalse( get_option( '_super_upload_receipt_claim_' . $hash, false ) );
    }

    public function test_expired_receipt_cleanup_refuses_claim_races_and_preserves_legacy_and_reparented_uploads() {
        $form_id = $this->create_form( 'publish' );
        $claimed = $this->create_owned_upload( $form_id );
        $claimed_token = $this->issue_receipt( $claimed['owned'] );
        $claimed_receipt = $this->expire_receipt_for_cleanup( $claimed_token );
        $claimed_hash = hash( 'sha256', $claimed_token );
        $claim = array(
            'version' => 1,
            'claim_id' => str_repeat( 'b', 64 ),
            'token_hash' => $claimed_hash,
            'expires' => $claimed_receipt['expires'],
            'purpose' => 'submission',
            'claimed_at' => time(),
        );
        add_option( '_super_upload_receipt_claim_' . $claimed_hash, $claim, '', 'no' );

        $this->assertFalse( SUPER_Ajax::cleanup_expired_upload_receipt( $claimed_hash ) );
        $this->assertFileExists( $claimed['file'] );
        $this->assertSame( $claim, get_option( '_super_upload_receipt_claim_' . $claimed_hash, false ) );
        // A *stale* submission claim (older than the 2 minute window) on an expired
        // receipt is reclaimable by cleanup, exactly like the receipt suite proves
        // (includes/class-ajax.php:5761-5766 and 5921,5930-5934); only the live claim
        // asserted above may block it.
        $claim['claimed_at'] = time() - HOUR_IN_SECONDS;
        update_option( '_super_upload_receipt_claim_' . $claimed_hash, $claim, false );
        $this->assertTrue( SUPER_Ajax::cleanup_expired_upload_receipt( $claimed_hash ) );
        $this->assertFileDoesNotExist( $claimed['file'] );
        $this->assertFalse( get_option( '_super_upload_receipt_' . $claimed_hash, false ) );
        $this->assertFalse( get_option( '_super_upload_receipt_claim_' . $claimed_hash, false ) );

        $legacy = $this->create_owned_upload( $form_id );
        $legacy_token = $this->issue_receipt( $legacy['owned'] );
        $legacy_receipt = $this->expire_receipt_for_cleanup( $legacy_token );
        $legacy_hash = hash( 'sha256', $legacy_token );
        $legacy_claim = array( 'token_hash' => $legacy_hash );
        add_option( '_super_upload_receipt_claim_' . $legacy_hash, $legacy_claim, '', 'no' );
        $this->assertFalse( SUPER_Ajax::cleanup_expired_upload_receipt( $legacy_hash ) );
        $this->assertFileExists( $legacy['file'] );
        $this->assertSame( $legacy_receipt, get_option( '_super_upload_receipt_' . $legacy_hash, false ) );
        $this->assertSame( $legacy_claim, get_option( '_super_upload_receipt_claim_' . $legacy_hash, false ) );
        delete_option( '_super_upload_receipt_claim_' . $legacy_hash );
        $this->assertTrue( SUPER_Ajax::cleanup_expired_upload_receipt( $legacy_hash ) );

        $current = $this->create_owned_upload( $form_id, 'documents', true );
        $current_token = $this->issue_receipt( $current['owned'] );
        $current_receipt = $this->expire_receipt_for_cleanup( $current_token );
        $entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
            )
        );
        wp_update_post(
            array(
                'ID' => $current['attachment'],
                'post_parent' => $entry_id,
            )
        );

        $this->assertTrue( SUPER_Ajax::cleanup_expired_upload_receipt( $current_receipt['token_hash'] ) );
        $this->assertSame( 'attachment', get_post_type( $current['attachment'] ) );
        $this->assertFileExists( $current['file'] );
        $this->assertFalse( get_option( '_super_upload_receipt_' . $current_receipt['token_hash'], false ) );
    }
    public function test_expired_receipt_cleanup_reclaims_a_stale_cleanup_claim_before_deleting_the_file() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id );
        $token = $this->issue_receipt( $created['owned'] );
        $receipt = $this->expire_receipt_for_cleanup( $token );
        $hash = hash( 'sha256', $token );
        $claim = array(
            'version' => 1,
            'claim_id' => str_repeat( 'c', 64 ),
            'token_hash' => $hash,
            'expires' => $receipt['expires'],
            'purpose' => 'cleanup',
            'claimed_at' => time() - HOUR_IN_SECONDS,
        );
        add_option( '_super_upload_receipt_claim_' . $hash, $claim, '', 'no' );

        $this->assertTrue( SUPER_Ajax::cleanup_expired_upload_receipt( $hash ) );
        $this->assertFileDoesNotExist( $created['file'] );
        $this->assertFalse( get_option( '_super_upload_receipt_' . $hash, false ) );
        $this->assertFalse( get_option( '_super_upload_receipt_claim_' . $hash, false ) );
    }


    public function test_legacy_retained_source_keys_produce_exact_cleanup_authority() {
        $elements = array( $this->file_element( 'documents' ) );
        $form_id = $this->create_form( 'publish', $elements );
        $entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
            )
        );
        $first = $this->create_owned_upload( $form_id, 'documents', true, $this->valid_png_bytes() );
        $second = $this->create_owned_upload( $form_id, 'documents', true, $this->valid_png_bytes() );
        list( $configured_parent, $configured_root ) = $this->create_temporary_root( true );
        $uploads = array( $first, $second );
        foreach( $uploads as &$created ) {
            $configured_file = trailingslashit( $configured_root ) . basename( $created['file'] );
            $this->assertTrue( rename( $created['file'], $configured_file ) );
            $this->assertTrue( update_attached_file( $created['attachment'], $configured_file ) );
            $created['file'] = $configured_file;
        }
        unset( $created );
        $first = $uploads[0];
        $second = $uploads[1];
        $settings = array(
            'file_upload_dir' => '../' . basename( $configured_parent ) . '/' . basename( $configured_root ),
        );
        update_post_meta( $form_id, '_super_form_settings', $settings );
        $first_png = preg_replace( '/\.jpg$/', '.png', $first['file'] );
        $second_png = preg_replace( '/\.jpg$/', '.png', $second['file'] );
        $this->assertTrue( rename( $first['file'], $first_png ) );
        $this->assertTrue( rename( $second['file'], $second_png ) );
        $this->assertTrue( update_attached_file( $first['attachment'], $first_png ) );
        $this->assertTrue( update_attached_file( $second['attachment'], $second_png ) );
        $first['file'] = $first_png;
        $second['file'] = $second_png;
        foreach( array( $first, $second ) as $created ) {
            wp_update_post(
                array(
                    'ID' => $created['attachment'],
                    'post_parent' => $entry_id,
                    'post_mime_type' => 'image/png',
                )
            );
            delete_post_meta( $created['attachment'], '_super_forms_upload_form_id' );
            delete_post_meta( $created['attachment'], '_super_forms_upload_field' );
        }
        $stored = array(
            7 => array(
                'value' => basename( $first['file'] ),
                'name' => 'documents',
                'type' => 'image/png',
                'url' => wp_get_attachment_url( $first['attachment'] ),
                'attachment' => $first['attachment'],
            ),
            19 => array(
                'value' => basename( $second['file'] ),
                'name' => 'documents',
                'type' => 'image/png',
                'url' => wp_get_attachment_url( $second['attachment'] ),
                'attachment' => $second['attachment'],
            ),
        );
        update_post_meta(
            $entry_id,
            '_super_contact_entry_data',
            array(
                'documents' => array(
                    'type' => 'files',
                    'files' => $stored,
                ),
            )
        );
        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array(
                    array(
                        'value' => $stored[19]['value'],
                        'url' => $stored[19]['url'],
                        'retention_token' => 'entry',
                    ),
                ),
            ),
        );

        $resolved = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $data, $form_id, $elements, $entry_id )
        );
        $this->assertTrue( is_array( $resolved ) );
        $this->assertCount( 1, $resolved['retained_owned_files'] );
        $this->assertSame( 19, $resolved['retained_owned_files'][0]['legacy_source_key'] );
        $this->assertArrayHasKey( 19, $resolved['data']['documents']['files'] );
        $this->assertArrayNotHasKey( 7, $resolved['data']['documents']['files'] );
        update_post_meta( $entry_id, '_super_contact_entry_data', $resolved['data'] );

        $this->assertTrue(
            $this->invoke_ajax_private(
                'cleanup_owned_uploads',
                array( $resolved['retained_owned_files'] )
            )
        );
        $this->assertSame( 'attachment', get_post_type( $first['attachment'] ) );
        $this->assertSame( 'attachment', get_post_type( $second['attachment'] ) );

        $current = SUPER_Data_Access::get_entry_data( $entry_id );
        $mutated = $current;
        $mutated['documents']['files'][19]['url'] .= '?changed';
        update_post_meta( $entry_id, '_super_contact_entry_data', $mutated );
        $this->assertFalse(
            $this->invoke_ajax_private(
                'delete_finalized_owned_uploads',
                array( $resolved['retained_owned_files'], $entry_id, $form_id )
            )
        );
        $this->assertSame( 'attachment', get_post_type( $second['attachment'] ) );

        $rekeyed = $current;
        $rekeyed['documents']['files'][20] = $rekeyed['documents']['files'][19];
        unset( $rekeyed['documents']['files'][19] );
        update_post_meta( $entry_id, '_super_contact_entry_data', $rekeyed );
        $this->assertFalse(
            $this->invoke_ajax_private(
                'delete_finalized_owned_uploads',
                array( $resolved['retained_owned_files'], $entry_id, $form_id )
            )
        );
        $this->assertSame( 'attachment', get_post_type( $second['attachment'] ) );

        update_post_meta( $entry_id, '_super_contact_entry_data', $current );
        $this->assertTrue(
            $this->invoke_ajax_private(
                'delete_finalized_owned_uploads',
                array( $resolved['retained_owned_files'], $entry_id, $form_id )
            )
        );
        $this->assertSame( 'attachment', get_post_type( $first['attachment'] ) );
        $this->assertNull( get_post( $second['attachment'] ) );

    }
    public function test_custom_root_retained_uploads_keep_exact_cleanup_authority_after_root_setting_changes() {
        list( $original_parent, $original_root ) = $this->create_temporary_root( true );
        $original_setting = '../' . basename( $original_parent ) . '/' . basename( $original_root );
        $elements = array( $this->file_element( 'documents', array( 'extensions' => 'png' ) ) );
        $settings = array( 'file_upload_dir' => $original_setting );
        $form_id = $this->create_form( 'publish', $elements, $settings );
        $entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
            )
        );
        $slot = str_pad( (string) wp_rand( 1, 9999999999999 ), 13, '0', STR_PAD_LEFT );
        $directory = trailingslashit( $original_root ) . $slot;
        $filename = $directory . '/retained.png';
        $this->assertTrue( wp_mkdir_p( $directory ) );
        $this->assertNotFalse( file_put_contents( $filename, $this->valid_png_bytes() ) );
        $subdir = '/' . $original_setting . '/' . $slot . '/retained.png';
        $url = trailingslashit( get_option( 'siteurl' ) ) . 'sfgtfi/' . ltrim( str_replace( '../', '__/', $subdir ), '/' );
        $owned = $this->invoke_ajax_private(
            'build_owned_upload',
            array( $form_id, 'documents', $filename, 'image/png', $url, 0, $original_root, filesize( $filename ), $subdir )
        );
        $stored = $this->invoke_ajax_private( 'owned_upload_file_record', array( $owned ) );
        update_post_meta(
            $entry_id,
            '_super_contact_entry_data',
            array(
                'documents' => array(
                    'type' => 'files',
                    'files' => array( $stored ),
                ),
            )
        );

        list( $new_parent, $new_root ) = $this->create_temporary_root( true );
        $new_settings = array( 'file_upload_dir' => '../' . basename( $new_parent ) . '/' . basename( $new_root ) );
        update_post_meta( $form_id, '_super_form_settings', $new_settings );
        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array(
                    array(
                        'value' => $stored['value'],
                        'url' => $stored['url'],
                        'retention_token' => 'entry',
                    ),
                ),
            ),
        );

        $resolved = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $data, $form_id, $elements, $entry_id )
        );
        $this->assertTrue( is_array( $resolved ) );
        $this->assertCount( 1, $resolved['retained_owned_files'] );
        $this->assertSame( wp_normalize_path( $original_root ), $resolved['retained_owned_files'][0]['allowed_root'] );
        update_post_meta( $entry_id, '_super_contact_entry_data', $resolved['data'] );

        $this->assertTrue(
            $this->invoke_ajax_private(
                'delete_finalized_owned_uploads',
                array( $resolved['retained_owned_files'], $entry_id, $form_id )
            )
        );
        $this->assertFileDoesNotExist( $filename );
    }
    public function test_proofless_custom_retained_upload_is_resealed_before_it_can_regain_cleanup_authority() {
        list( $parent, $root ) = $this->create_temporary_root( true );
        $setting = '../' . basename( $parent ) . '/' . basename( $root );
        $elements = array( $this->file_element( 'documents', array( 'extensions' => 'png' ) ) );
        $settings = array( 'file_upload_dir' => $setting );
        $form_id = $this->create_form( 'publish', $elements, $settings );
        $entry_id = self::factory()->post->create(
            array(
                'post_type' => 'super_contact_entry',
                'post_status' => 'super_unread',
                'post_parent' => $form_id,
            )
        );
        $slot = str_pad( (string) wp_rand( 1, 9999999999999 ), 13, '0', STR_PAD_LEFT );
        $directory = trailingslashit( $root ) . $slot;
        $filename = $directory . '/retained.png';
        $this->assertTrue( wp_mkdir_p( $directory ) );
        $this->assertNotFalse( file_put_contents( $filename, $this->valid_png_bytes() ) );
        $subdir = '/' . $setting . '/' . $slot . '/retained.png';
        $url = trailingslashit( get_option( 'siteurl' ) ) . 'sfgtfi/' . ltrim( str_replace( '../', '__/', $subdir ), '/' );
        $owned = $this->invoke_ajax_private(
            'build_owned_upload',
            array( $form_id, 'documents', $filename, 'image/png', $url, 0, $root, filesize( $filename ), $subdir )
        );
        $stored = $this->invoke_ajax_private( 'owned_upload_file_record', array( $owned ) );
        unset( $stored['_super_file_proof'] );
        update_post_meta(
            $entry_id,
            '_super_contact_entry_data',
            array(
                'documents' => array(
                    'type' => 'files',
                    'files' => array( $stored ),
                ),
            )
        );
        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array(
                    array(
                        'value' => $stored['value'],
                        'url' => $stored['url'],
                        'retention_token' => 'entry',
                    ),
                ),
            ),
        );

        $resolved = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $data, $form_id, $elements, $entry_id )
        );
        $this->assertTrue( is_array( $resolved ) );
        $this->assertSame( array(), $resolved['retained_owned_files'] );
        $migrated = $resolved['data']['documents']['files'][0];
        $this->assertSame( 'retained', $migrated['_super_file_authority'] );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $migrated['_super_file_proof'] );

        update_post_meta( $entry_id, '_super_contact_entry_data', $resolved['data'] );
        $resolved_again = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $data, $form_id, $elements, $entry_id )
        );
        $this->assertTrue( is_array( $resolved_again ) );
        $this->assertCount( 1, $resolved_again['retained_owned_files'] );
    }


    private function expire_receipt_for_cleanup( $token ) {
        $hash = hash( 'sha256', $token );
        $receipt = get_option( '_super_upload_receipt_' . $hash, false );
        $this->assertTrue( is_array( $receipt ) );
        $this->invoke_ajax_private(
            'unschedule_upload_receipt_cleanup',
            array( $hash, $receipt['expires'] )
        );
        $receipt['expires'] = time() - 2;
        update_option( '_super_upload_receipt_' . $hash, $receipt, false );
        wp_schedule_single_event(
            $receipt['expires'] + 1,
            'super_cleanup_upload_receipt',
            array( $hash )
        );
        return $receipt;
    }

    private function generated_pdf_data( $bytes ) {
        return array(
            '_generated_pdf_file' => array(
                'type' => 'files',
                'files' => array( array(
                    'label' => 'Generated PDF',
                    'name' => 'invoice.pdf',
                    'value' => 'invoice.pdf',
                    'datauristring' => 'data:application/pdf;base64,' . base64_encode( $bytes ),
                ) ),
            ),
        );
    }

    private function valid_png_bytes() {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
    }
}
