<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Super_Forms_Upload_Policy_Security extends Super_Forms_Upload_Security_Test_Case {
    public function test_anonymous_and_editor_form_authorization_is_shared_by_upload_and_submit() {
        $published = $this->create_form( 'publish' );
        $draft = $this->create_form( 'draft' );
        $private = $this->create_form( 'private' );
        $trash = $this->create_form( 'trash' );
        $ordinary_post = self::factory()->post->create( array(
            'post_type' => 'post',
            'post_status' => 'publish',
        ) );

        wp_set_current_user( 0 );
        $this->assertTrue( $this->invoke_ajax_private( 'upload_form_id_is_valid', array( $published ) ) );
        foreach( array( $draft, $private, $trash, $ordinary_post, 999999, 0 ) as $rejected ) {
            $this->assertFalse( $this->invoke_ajax_private( 'upload_form_id_is_valid', array( $rejected ) ) );
        }

        $this->configure_csrf( 'false' );
        foreach( array( $draft, $private, $trash ) as $rejected ) {
            $this->set_request( $rejected );
            $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'Invalid form.' );
            $this->set_request( $rejected );
            $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'submit_form' ), 'Invalid form.' );
        }

        $subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        wp_set_current_user( $subscriber_id );
        $this->assertFalse( current_user_can( 'edit_post', $draft ) );
        $this->assertFalse( $this->invoke_ajax_private( 'upload_form_id_is_valid', array( $draft ) ) );
        $this->set_request( $draft );
        $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'Invalid form.' );

        $editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
        $editor_draft = $this->create_form( 'draft', array(), array(), $editor_id );
        $editor_private = $this->create_form( 'private', array(), array(), $editor_id );
        $editor_trash = $this->create_form( 'trash', array(), array(), $editor_id );
        wp_set_current_user( $editor_id );
        foreach( array( $editor_draft, $editor_private, $editor_trash ) as $preview_form ) {
            $this->assertTrue( current_user_can( 'edit_post', $preview_form ) );
            $this->assertTrue( $this->invoke_ajax_private( 'upload_form_id_is_valid', array( $preview_form ) ) );
            $this->set_request( $preview_form );
            $atts = SUPER_Ajax::submit_form_checks( null, true );
            $this->assertSame( $preview_form, $atts['form_id'] );
        }
        $this->set_request( $editor_draft );
        $this->assert_handler_rejected_with(
            array( 'SUPER_Ajax', 'upload_files' ),
            'Invalid file upload request.'
        );

        wp_set_current_user( 0 );
        $this->set_request( $published );
        $atts = SUPER_Ajax::submit_form_checks( null, true );
        $this->assertSame( $published, $atts['form_id'] );
    }

    public function test_upload_enforces_valid_nonce_or_explicit_csrf_disable_policy() {
        $form_id = $this->create_form( 'publish' );
        unset( $_COOKIE['_sfs_id'] );

        $this->assertTrue( $this->invoke_ajax_private( 'csrf_policy_allows_request', array( true, array() ) ) );
        $this->assertTrue( $this->invoke_ajax_private( 'csrf_policy_allows_request', array( false, array( 'csrf_check' => 'false' ) ) ) );
        $this->assertTrue( $this->invoke_ajax_private( 'csrf_policy_allows_request', array( false, array( 'allow_storing_cookies' => '0' ) ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'csrf_policy_allows_request', array( false, array( 'csrf_check' => 'true' ) ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'csrf_policy_allows_request', array( false, array() ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'csrf_policy_allows_request', array( false, array( 'csrf_check' => false ) ) ) );

        $this->configure_csrf( 'true' );
        $this->set_request( $form_id );
        $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'session expired' );

        $this->configure_csrf( 'false' );
        $this->set_request( $form_id );
        $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'Invalid file upload' );
    }

    public function test_recursive_exact_file_field_lookup_rejects_non_files_and_duplicates() {
        $documents = array( 'name' => 'documents', 'extensions' => 'png|pdf', 'filesize' => '7' );
        $elements = array(
            array(
                'tag' => 'column',
                'inner' => array(
                    array( 'tag' => 'text', 'data' => array( 'name' => 'not_a_file', 'extensions' => 'php' ) ),
                    array(
                        'tag' => 'column',
                        'inner' => array(
                            array( 'tag' => 'file', 'data' => $documents ),
                        ),
                    ),
                ),
            ),
        );

        $this->assertSame( $documents, $this->invoke_ajax_private( 'get_file_element', array( $elements, 'documents' ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'get_file_element', array( $elements, 'not_a_file' ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'get_file_element', array( $elements, '' ) ) );

        $elements[] = array( 'tag' => 'file', 'data' => array( 'name' => 'documents', 'extensions' => 'jpg' ) );
        $this->assertFalse(
            $this->invoke_ajax_private( 'get_file_element', array( $elements, 'documents' ) ),
            'Two stored file elements with the same name are ambiguous.'
        );
    }

    public function test_mime_filter_can_narrow_but_cannot_add_or_remap_core_types() {
        $widen = static function( $mimes ) {
            $mimes['php'] = 'image/jpeg';
            $mimes['madeup'] = 'image/jpeg';
            return $mimes;
        };
        $this->add_upload_filter( 'super_file_upload_mime_types_validation', $widen );
        $allowed = $this->invoke_ajax_private( 'allowed_file_mime_types', array( array(
            'extensions' => 'jpg|jpeg|php|madeup',
        ) ) );
        $this->assertArrayHasKey( 'jpg', $allowed );
        $this->assertArrayHasKey( 'jpeg', $allowed );
        $this->assertArrayNotHasKey( 'php', $allowed );
        $this->assertArrayNotHasKey( 'madeup', $allowed );
        $this->remove_upload_filter( 'super_file_upload_mime_types_validation', $widen );

        $remap = static function( $mimes ) {
            foreach( $mimes as $extensions => $mime ) {
                if( strpos( '|' . $extensions . '|', '|jpg|' ) !== false ) {
                    $mimes[$extensions] = 'text/plain';
                }
            }
            return $mimes;
        };
        $this->add_upload_filter( 'super_file_upload_mime_types_validation', $remap );
        $narrowed = $this->invoke_ajax_private( 'allowed_file_mime_types', array( array(
            'extensions' => 'jpg|jpeg|png',
        ) ) );
        $this->assertArrayNotHasKey( 'jpg', $narrowed );
        $this->assertArrayNotHasKey( 'jpeg', $narrowed );
        $this->assertArrayHasKey( 'png', $narrowed );
    }

    public function test_types_added_by_wordpress_mime_filters_cannot_expand_the_core_policy() {
        $add_types = static function( $mimes ) {
            $mimes['svg'] = 'image/svg+xml';
            $mimes['madeup'] = 'image/jpeg';
            return $mimes;
        };
        $this->add_upload_filter( 'mime_types', $add_types );

        $allowed = $this->invoke_ajax_private( 'allowed_file_mime_types', array( array(
            'extensions' => 'jpg|svg|madeup',
        ) ) );
        $this->assertArrayHasKey( 'jpg', $allowed );
        $this->assertArrayNotHasKey( 'svg', $allowed );
        $this->assertArrayNotHasKey(
            'madeup',
            $allowed,
            'A site-wide MIME filter must not turn a non-core extension into upload authority.'
        );

        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array(
            $this->file_element( 'documents', array( 'extensions' => 'svg' ) ),
        ) );
        list( $parent, $root ) = $this->create_temporary_root();
        $tmp = trailingslashit( $root ) . 'active.svg';
        file_put_contents( $tmp, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>' );
        $marker = trailingslashit( $parent ) . 'svg-upload-prefilter-ran';
        $prefilter = static function( $file ) use ( $marker ) {
            file_put_contents( $marker, 'called' );
            return $file;
        };
        $this->add_upload_filter( 'wp_handle_upload_prefilter', $prefilter );

        $files = $this->parallel_files( 'documents', array(
            array(
                'name' => 'active.svg',
                'tmp_name' => $tmp,
                'type' => 'image/svg+xml',
                'size' => filesize( $tmp ),
            ),
        ) );
        $this->set_request( $form_id, array(), array( 'files' => $files ) );
        $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'not permitted' );
        $this->assertFileDoesNotExist( $marker );
        $this->assertFileExists( $tmp );
    }

    public function test_dangerous_and_double_extensions_are_rejected_before_file_handling() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents' ) ) );
        list( $parent, $root ) = $this->create_temporary_root();
        $tmp = trailingslashit( $root ) . 'incoming';
        file_put_contents( $tmp, 'not processed' );

        foreach( array( 'shell.php', 'shell.php.jpg', 'shell.PHP8.jpg', 'shell.phtml.png', 'shell.phar.pdf' ) as $name ) {
            $files = $this->parallel_files( 'documents', array(
                array( 'name' => $name, 'tmp_name' => $tmp, 'type' => 'image/jpeg', 'size' => filesize( $tmp ) ),
            ) );
            $this->set_request( $form_id, array(), array( 'files' => $files ) );
            $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'not permitted' );
            $this->assertFileExists( $tmp );
        }
    }

    public function test_exact_field_filesize_accepts_only_positive_finite_numeric_values() {
        $default = $this->invoke_ajax_private( 'get_upload_field_size_limit', array( array() ) );
        $this->assertSame( 5.0, (float) $default['megabytes'] );
        $this->assertSame( 5000000.0, (float) $default['bytes'] );

        $limit = $this->invoke_ajax_private( 'get_upload_field_size_limit', array( array( 'filesize' => '7.5' ) ) );
        $this->assertSame( 7.5, (float) $limit['megabytes'] );
        $this->assertSame( 7500000.0, (float) $limit['bytes'] );

        foreach( array( array( '7' ), '-1', -1, '0', 0, '', '7 MB', 'INF', INF, NAN, null ) as $invalid ) {
            $this->assertFalse(
                $this->invoke_ajax_private( 'get_upload_field_size_limit', array( array( 'filesize' => $invalid ) ) ),
                'Malformed, non-positive, or non-finite stored sizes must fail closed.'
            );
        }
    }

    public function test_upload_endpoint_rejects_over_limit_payloads_before_persistence_and_accepts_the_exact_boundary() {
        list( $parent, $root ) = $this->create_temporary_root( true );
        $upload_settings = array(
            'csrf_check' => 'false',
            'email_reminder_amount' => 0,
            'file_upload_dir' => '../' . basename( $parent ) . '/owned',
            'file_upload_use_year_month_folders' => '',
        );
        update_option( 'super_settings', $upload_settings, false );
        SUPER_Forms()->global_settings = $upload_settings;

        $control_bytes = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL6eAAAAABJRU5ErkJggg==' );
        $this->assertIsString( $control_bytes );
        $this->assertSame( 70, strlen( $control_bytes ) );
        $control_bytes .= str_repeat( "\0", 30 );
        $this->assertSame( 100, strlen( $control_bytes ) );
        $control_tmp = trailingslashit( $root ) . 'at-limit.png';
        $this->assertNotFalse( file_put_contents( $control_tmp, $control_bytes ) );
        $move_marker = trailingslashit( $parent ) . 'moved-upload-path';
        $move_uploaded_file = static function( $moved, $file, $new_file ) use ( $move_marker ) {
            if( !copy( $file['tmp_name'], $new_file ) ) {
                return false;
            }
            file_put_contents( $move_marker, $new_file );
            return true;
        };
        $this->add_upload_filter( 'pre_move_uploaded_file', $move_uploaded_file, 10, 3 );

        $control_form = $this->create_form( 'publish', array(
            $this->file_element( 'documents', array( 'extensions' => 'png', 'filesize' => '0.0001' ) ),
        ) );
        $control_files = $this->parallel_files( 'documents', array(
            array(
                'name' => 'at-limit.png',
                'tmp_name' => $control_tmp,
                'type' => 'image/png',
                'size' => filesize( $control_tmp ),
            ),
        ) );
        $this->set_request( $control_form, array(), array( 'files' => $control_files ) );
        $control_result = $this->run_dying_handler( array( 'SUPER_Ajax', 'upload_files' ) );
        $this->assertSame( 0, $control_result['status'], $control_result['output'] );
        $control_response = json_decode( $control_result['output'], true );
        $this->assertIsArray( $control_response, $control_result['output'] );
        $this->assertSame( 'at-limit.png', $control_response['documents']['files'][0]['value'] );
        $this->assertSame( strlen( $control_bytes ), $control_response['documents']['files'][0]['size'] );
        $this->assertMatchesRegularExpression(
            '/^https?:\/\/.+\/sfgtfi\/__\/' . preg_quote( basename( $parent ), '/' ) . '\/owned\//',
            $control_response['documents']['files'][0]['url']
        );
        $control_token = $control_response['documents']['files'][0]['upload_token'];
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $control_token );
        $this->receipt_tokens[] = $control_token;
        $control_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $control_token, $control_form, 'documents' ) );
        $this->assertTrue( is_array( $control_descriptor ) );
        $this->assertSame( strlen( $control_bytes ), $control_descriptor['owned']['size'] );
        $this->assertTrue( is_array( get_option( $control_descriptor['receipt_option'], false ) ) );
        $moved_file = file_get_contents( $move_marker );
        $this->assertIsString( $moved_file );
        $this->assertFileExists( $moved_file );
        $this->assertSame( $control_bytes, file_get_contents( $moved_file ) );

        global $wpdb;
        $receipt_options_before = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
                $wpdb->esc_like( '_super_upload_receipt_' ) . '%'
            )
        );
        $oversized_tmp = trailingslashit( $root ) . 'over-limit.png';
        $this->assertNotFalse( file_put_contents( $oversized_tmp, $control_bytes . 'x' ) );
        $over_limit_form = $this->create_form( 'publish', array(
            $this->file_element( 'documents', array( 'extensions' => 'png', 'filesize' => '0.0001' ) ),
        ) );
        $over_limit_files = $this->parallel_files( 'documents', array(
            array(
                'name' => 'over-limit.png',
                'tmp_name' => $oversized_tmp,
                'type' => 'image/png',
                'size' => filesize( $oversized_tmp ),
            ),
        ) );
        $this->set_request( $over_limit_form, array(), array( 'files' => $over_limit_files ) );
        $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'filesize limitation' );
        $this->assertSame( $moved_file, file_get_contents( $move_marker ) );
        $this->assertSame( $control_bytes, file_get_contents( $moved_file ) );
        $this->assertFileExists( $oversized_tmp );
        $this->assertSame(
            $receipt_options_before,
            $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
                    $wpdb->esc_like( '_super_upload_receipt_' ) . '%'
                )
            ),
            'An over-limit payload must not persist a receipt or claim.'
        );

        $invalid_form = $this->create_form( 'publish', array(
            $this->file_element( 'documents', array( 'extensions' => 'png', 'filesize' => '-1' ) ),
        ) );
        $this->set_request( $invalid_form, array(), array( 'files' => $over_limit_files ) );
        $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'Invalid file upload configuration.' );
        $this->assertSame( $moved_file, file_get_contents( $move_marker ) );
    }

    public function test_complete_multipart_shape_is_preflighted_before_any_file_pipeline_effect() {
        $valid = $this->parallel_files( 'documents', array(
            array( 'name' => 'first.jpg', 'tmp_name' => '/tmp/first', 'type' => 'image/jpeg', 'size' => 10 ),
            array( 'name' => 'second.jpg', 'tmp_name' => '/tmp/second', 'type' => 'image/jpeg', 'size' => 20 ),
        ) );
        $this->assertTrue( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $valid ) ) );

        $with_full_path = $valid;
        $with_full_path['full_path'] = array( 'documents' => array( 'browser-directory/first.jpg', 'browser-directory/second.jpg' ) );
        $this->assertTrue( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $with_full_path ) ) );

        $malformed_full_path = $with_full_path;
        $malformed_full_path['full_path']['documents'][0] = array( 'browser-directory/first.jpg' );
        $this->assertFalse( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $malformed_full_path ) ) );

        $mismatched_full_path = $with_full_path;
        unset( $mismatched_full_path['full_path']['documents'][1] );
        $this->assertFalse( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $mismatched_full_path ) ) );

        $unrelated_part = $with_full_path;
        $unrelated_part['attacker_controlled'] = array();
        $this->assertFalse( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $unrelated_part ) ) );

        $missing = $valid;
        unset( $missing['tmp_name']['documents'][1] );
        $this->assertFalse( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $missing ) ) );

        $extra = $valid;
        $extra['size']['other'] = array( 0 => 10 );
        $this->assertFalse( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $extra ) ) );

        $nested = $valid;
        $nested['name']['documents'][0] = array( 'first.jpg' );
        $this->assertFalse( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $nested ) ) );

        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents', array( 'extensions' => 'jpg' ) ) ) );
        list( $parent, $root ) = $this->create_temporary_root();
        $tmp = trailingslashit( $root ) . 'first-upload';
        file_put_contents( $tmp, 'first upload' );
        $marker = trailingslashit( $parent ) . 'upload-prefilter-ran';
        $prefilter = static function( $file ) use ( $marker ) {
            file_put_contents( $marker, 'called' );
            return $file;
        };
        $this->add_upload_filter( 'wp_handle_upload_prefilter', $prefilter );

        $missing['tmp_name']['documents'][0] = $tmp;
        $this->set_request( $form_id, array(), array( 'files' => $missing ) );
        $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'Invalid file upload request.' );
        $this->assertFileDoesNotExist( $marker, 'Malformed later parts must reject before the first file reaches WordPress handling.' );
        $this->assertFileExists( $tmp );
    }
    public function test_upload_honeypot_rejects_the_request_before_any_file_move_or_receipt_write() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents', array( 'extensions' => 'jpg' ) ) ) );
        list( $parent, $root ) = $this->create_temporary_root();
        $tmp = trailingslashit( $root ) . 'honeypot-upload';
        file_put_contents( $tmp, 'honeypot upload' );
        $marker = trailingslashit( $parent ) . 'honeypot-upload-prefilter-ran';
        $prefilter = static function( $file ) use ( $marker ) {
            file_put_contents( $marker, 'called' );
            return $file;
        };
        $this->add_upload_filter( 'wp_handle_upload_prefilter', $prefilter );
        $files = $this->parallel_files( 'documents', array(
            array( 'name' => 'honeypot.jpg', 'tmp_name' => $tmp, 'type' => 'image/jpeg', 'size' => filesize( $tmp ) ),
        ) );
        global $wpdb;
        $before = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like( '_super_upload_receipt_' ) . '%'
            )
        );
        $this->set_request( $form_id, array(), array( 'files' => $files ), array( 'super_hp' => 'bot' ) );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'upload_files' ) );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $this->assertSame( '', $result['output'] );
        $this->assertFileDoesNotExist( $marker );
        $this->assertFileExists( $tmp );
        $this->assertSame(
            (string) $before,
            (string) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $wpdb->esc_like( '_super_upload_receipt_' ) . '%'
                )
            )
        );
    }

    public function test_client_upload_carrier_contains_display_data_validated_size_and_the_opaque_token_only() {
        $form_id = $this->create_form( 'publish' );
        foreach( array( false, true ) as $attachment ) {
            $created = $this->create_owned_upload( $form_id, 'documents', $attachment );
            $public = $this->invoke_ajax_private( 'owned_upload_public_record', array( $created['owned'] ) );
            $token = $this->issue_receipt( $created['owned'] );
            $public['upload_token'] = $token;

            $keys = array_keys( $public );
            sort( $keys );
            $this->assertSame( array( 'name', 'size', 'type', 'upload_token', 'url', 'value' ), $keys );
            $this->assertSame( filesize( $created['file'] ), $public['size'] );
            foreach( array( 'attachment', 'file', 'path', 'custom_path', 'allowed_root', 'subdir', 'legacy_subdir', 'form_id', 'field' ) as $authority_key ) {
                $this->assertArrayNotHasKey( $authority_key, $public );
            }
        }
    }

    public function test_upload_preflight_does_not_run_submission_side_effect_hooks() {
        $form_id = $this->create_form( 'publish' );
        $this->set_request( $form_id );
        $calls = 0;
        $callback = static function() use ( &$calls ) {
            $calls++;
        };
        add_action( 'super_before_sending_email_hook', $callback, 1 );
        try {
            $atts = SUPER_Ajax::submit_form_checks( null, true );
        } finally {
            remove_action( 'super_before_sending_email_hook', $callback, 1 );
        }

        $this->assertSame( $form_id, $atts['form_id'] );
        $this->assertSame( 0, $calls, 'The upload-only request must not execute account or delivery side effects.' );
    }
    public function test_stored_maximum_file_count_is_enforced_on_upload_and_final_submission() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form(
            'publish',
            array(
                $this->file_element(
                    'documents',
                    array(
                        'extensions' => 'png|jpg|jpeg',
                        'maxlength' => '2',
                    )
                ),
            )
        );
        list( $parent, $root ) = $this->create_temporary_root();
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
        $this->assertIsString( $png );
        $upload_files = array();
        for( $i = 0; $i < 3; $i++ ) {
            $tmp = trailingslashit( $root ) . 'too-many-' . $i . '.png';
            $this->assertNotFalse( file_put_contents( $tmp, $png ) );
            $upload_files[] = array(
                'name' => 'too-many-' . $i . '.png',
                'tmp_name' => $tmp,
                'type' => 'image/png',
                'size' => filesize( $tmp ),
            );
        }
        global $wpdb;
        $before = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
                $wpdb->esc_like( '_super_upload_receipt_' ) . '%'
            )
        );
        $this->set_request( $form_id, array(), array( 'files' => $this->parallel_files( 'documents', $upload_files ) ) );
        $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'upload_files' ), 'Invalid file upload rejected.' );
        $this->assertSame(
            $before,
            $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
                    $wpdb->esc_like( '_super_upload_receipt_' ) . '%'
                )
            )
        );

        $first = $this->create_owned_upload( $form_id );
        $second = $this->create_owned_upload( $form_id );
        $third = $this->create_owned_upload( $form_id );
        $first_token = $this->issue_receipt( $first['owned'] );
        $second_token = $this->issue_receipt( $second['owned'] );
        $third_token = $this->issue_receipt( $third['owned'] );
        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array(
                    array( 'upload_token' => $first_token ),
                    array( 'upload_token' => $second_token ),
                    array( 'upload_token' => $third_token ),
                ),
            ),
        );
        $this->set_request( $form_id, $data );
        $this->assert_handler_rejected_with(
            static function () {
                SUPER_Ajax::submit_form_checks( null, false );
            },
            'Invalid file upload rejected.'
        );
    }
    public function test_upload_endpoint_allows_a_partial_batch_but_public_submit_enforces_the_stored_minimum() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form(
            'publish',
            array(
                $this->file_element(
                    'documents',
                    array(
                        'extensions' => 'png',
                        'minlength' => '2',
                        'maxlength' => '3',
                    )
                ),
            )
        );
        list( $parent, $root ) = $this->create_temporary_root();
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
        $this->assertIsString( $png );
        $tmp = trailingslashit( $root ) . 'partial-minimum.png';
        $this->assertNotFalse( file_put_contents( $tmp, $png ) );
        $this->set_request(
            $form_id,
            array(),
            array(
                'files' => $this->parallel_files(
                    'documents',
                    array(
                        array(
                            'name' => 'partial-minimum.png',
                            'tmp_name' => $tmp,
                            'type' => 'image/png',
                            'size' => filesize( $tmp ),
                        ),
                    )
                ),
            )
        );
        $upload = $this->run_dying_handler( array( 'SUPER_Ajax', 'upload_files' ) );
        $this->assertSame( 0, $upload['status'], $upload['output'] );
        $uploaded = json_decode( $upload['output'], true );
        $this->assertIsArray( $uploaded, $upload['output'] );
        $this->assertSame( 'partial-minimum.png', $uploaded['documents']['files'][0]['value'] );
        $token = $uploaded['documents']['files'][0]['upload_token'];
        $this->receipt_tokens[] = $token;
        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ) );

        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array(
                    array( 'upload_token' => $token ),
                ),
            ),
        );
        $this->set_submit_request( $form_id, $data );
        $submit = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $submit['status'], $submit['output'] );
        $decoded = json_decode( $submit['output'], true );
        $this->assertIsArray( $decoded, $submit['output'] );
        $this->assertTrue( $decoded['error'] );
        $this->assertStringContainsString( 'Invalid file upload rejected.', $decoded['msg'] );
        $this->assertTrue( is_array( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) ) ) );
    }


    private function parallel_files( $field, $items ) {
        $files = array(
            'name' => array( $field => array() ),
            'type' => array( $field => array() ),
            'tmp_name' => array( $field => array() ),
            'error' => array( $field => array() ),
            'size' => array( $field => array() ),
        );
        foreach( $items as $key => $item ) {
            $files['name'][$field][$key] = $item['name'];
            $files['type'][$field][$key] = $item['type'];
            $files['tmp_name'][$field][$key] = $item['tmp_name'];
            $files['error'][$field][$key] = UPLOAD_ERR_OK;
            $files['size'][$field][$key] = $item['size'];
        }
        return $files;
    }
}
