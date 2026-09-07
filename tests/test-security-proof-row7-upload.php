<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

/**
 * Matrix row 7 and row 8 public-boundary proof for upload path validation and
 * receipt lifecycle handling. Browser and raw-header limits remain recorded in
 * tests/UNVERIFIABLE-http-headers.md.
 */

class Test_Super_Forms_Proof_Row7_Upload extends Super_Forms_Upload_Security_Test_Case {

    public function test_every_malformed_full_path_multipart_variant_is_rejected_by_the_forked_upload_endpoint_with_zero_effects() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents' ) ) );
        list( $parent, $root ) = $this->create_temporary_root();
        $tmp_first = trailingslashit( $root ) . 'first.jpg';
        $tmp_second = trailingslashit( $root ) . 'second.jpg';
        $this->assertNotFalse( file_put_contents( $tmp_first, 'first upload bytes' ) );
        $this->assertNotFalse( file_put_contents( $tmp_second, 'second upload bytes' ) );
        $first_contents = file_get_contents( $tmp_first );
        $second_contents = file_get_contents( $tmp_second );

        $base = array(
            'name' => array( 'documents' => array( 0 => 'first.jpg', 1 => 'second.jpg' ) ),
            'type' => array( 'documents' => array( 0 => 'image/jpeg', 1 => 'image/jpeg' ) ),
            'tmp_name' => array( 'documents' => array( 0 => $tmp_first, 1 => $tmp_second ) ),
            'error' => array( 'documents' => array( 0 => UPLOAD_ERR_OK, 1 => UPLOAD_ERR_OK ) ),
            'size' => array( 'documents' => array( 0 => filesize( $tmp_first ), 1 => filesize( $tmp_second ) ) ),
            'full_path' => array( 'documents' => array( 0 => 'browser-dir/first.jpg', 1 => 'browser-dir/second.jpg' ) ),
        );
        // Start from a valid fixture, then mutate one member per case.
        $this->assertTrue( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $base ) ) );

        $variants = array(
            'full_path is not an array' => static function( $files ) {
                $files['full_path'] = 'not-an-array';
                return $files;
            },
            'full_path field value is not an array' => static function( $files ) {
                $files['full_path']['documents'] = 'not-an-array';
                return $files;
            },
            'full_path entry is not a string' => static function( $files ) {
                $files['full_path']['documents'][0] = array( 'browser-dir/first.jpg' );
                return $files;
            },
            'full_path has fewer entries than name' => static function( $files ) {
                unset( $files['full_path']['documents'][1] );
                return $files;
            },
            'full_path has more entries than name' => static function( $files ) {
                $files['full_path']['documents'][2] = 'browser-dir/extra.jpg';
                return $files;
            },
            'full_path keys do not align with name keys' => static function( $files ) {
                unset( $files['full_path']['documents'][0] );
                $files['full_path']['documents'][2] = 'browser-dir/first.jpg';
                return $files;
            },
            'full_path is missing the declared field entirely' => static function( $files ) {
                unset( $files['full_path']['documents'] );
                return $files;
            },
            'full_path is keyed under the wrong top-level field name' => static function( $files ) {
                $files['full_path'] = array( 'unrelated_field' => $files['full_path']['documents'] );
                return $files;
            },
        );

        global $wpdb;
        foreach( $variants as $label => $mutate ) {
            $malformed = $mutate( $base );
            $this->assertFalse(
                $this->invoke_ajax_private( 'upload_files_are_parallel', array( $malformed ) ),
                'Reflection guard did not reject variant: ' . $label
            );

            $receipts_before = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $wpdb->esc_like( '_super_upload_receipt_' ) . '%'
                )
            );
            $attachments_before = count( $this->uploaded_attachment_ids() );

            $this->set_request( $form_id, array(), array( 'files' => $malformed ) );
            $this->assert_handler_rejected_with(
                array( 'SUPER_Ajax', 'upload_files' ),
                'Invalid file upload request.'
            );

            $this->assertFileExists( $tmp_first, 'Variant "' . $label . '" must not touch the first source file.' );
            $this->assertFileExists( $tmp_second, 'Variant "' . $label . '" must not touch the second source file.' );
            $this->assertSame( $first_contents, file_get_contents( $tmp_first ), 'Variant "' . $label . '" mutated the first file.' );
            $this->assertSame( $second_contents, file_get_contents( $tmp_second ), 'Variant "' . $label . '" mutated the second file.' );
            $this->assertSame(
                $receipts_before,
                (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                        $wpdb->esc_like( '_super_upload_receipt_' ) . '%'
                    )
                ),
                'Variant "' . $label . '" persisted an upload receipt.'
            );
            $this->assertCount(
                $attachments_before,
                $this->uploaded_attachment_ids(),
                'Variant "' . $label . '" created an attachment.'
            );
        }
    }

    public function test_valid_full_path_carrying_upload_returns_exactly_one_carrier_with_opaque_token_and_server_measured_size() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array(
            $this->file_element( 'documents', array( 'extensions' => 'png', 'filesize' => '1' ) ),
        ) );
        list( $parent, $root ) = $this->create_temporary_root();

        $png = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL6eAAAAABJRU5ErkJggg==' );
        $this->assertIsString( $png );
        // The stored size must come from the server-owned file, not the request.
        $png .= str_repeat( "\0", 40 );
        $real_size = strlen( $png );
        $tmp = trailingslashit( $root ) . 'row7-valid-full-path-upload.png';
        $this->assertNotFalse( file_put_contents( $tmp, $png ) );

        $files = array(
            'name' => array( 'documents' => array( 0 => 'row7-valid-full-path-upload.png' ) ),
            'type' => array( 'documents' => array( 0 => 'image/png' ) ),
            'tmp_name' => array( 'documents' => array( 0 => $tmp ) ),
            'error' => array( 'documents' => array( 0 => UPLOAD_ERR_OK ) ),
            // The request size is intentionally wrong.
            'size' => array( 'documents' => array( 0 => 1 ) ),
            'full_path' => array( 'documents' => array( 0 => 'nested/browser-folder/row7-valid-full-path-upload.png' ) ),
        );
        $this->assertTrue( $this->invoke_ajax_private( 'upload_files_are_parallel', array( $files ) ) );

        $this->set_request( $form_id, array(), array( 'files' => $files ) );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'upload_files' ) );
        $this->assertSame( 0, $result['status'], $result['output'] );

        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertSame( array( 'documents' ), array_keys( $decoded ) );
        $this->assertSame( 'files', $decoded['documents']['type'] );
        $this->assertCount( 1, $decoded['documents']['files'], 'The full_path-carrying request must yield exactly one carrier.' );

        $carrier = $decoded['documents']['files'][0];
        $keys = array_keys( $carrier );
        sort( $keys );
        $this->assertSame(
            array( 'name', 'size', 'type', 'upload_token', 'url', 'value' ),
            $keys,
            'The full_path multipart part must never leak into the response carrier shape.'
        );
        $this->assertSame( 'documents', $carrier['name'] );
        $this->assertSame( 'row7-valid-full-path-upload.png', $carrier['value'] );
        $this->assertSame( 'image/png', $carrier['type'] );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $carrier['upload_token'] );
        $this->assertNotSame( 1, $carrier['size'], 'The response size must not echo the lied-about client-declared size.' );
        $this->assertSame( $real_size, $carrier['size'], 'The response size must be the server-measured byte count of the moved file.' );

        $this->receipt_tokens[] = $carrier['upload_token'];
        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $carrier['upload_token'], $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ), 'The issued token must resolve to exactly one live receipt.' );
        $this->assertSame( $real_size, $descriptor['owned']['size'] );
        $this->assertFileExists( $descriptor['owned']['file'] );
        $this->assertSame( $real_size, filesize( $descriptor['owned']['file'] ) );
        $this->track_owned_cleanup( $descriptor['owned'] );
    }

    private function uploaded_attachment_ids() {
        return get_posts( array(
            'post_type' => 'attachment',
            'meta_key' => 'super-forms-form-upload-file',
            'fields' => 'ids',
            'posts_per_page' => -1,
        ) );
    }

    // Row 8 — upload-receipt lifecycle checks

    public function test_switching_the_anonymous_session_after_inspect_but_before_claim_blocks_claim_until_identity_is_restored() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id );
        $token = $this->issue_receipt( $created['owned'] );
        $issued_session = $_COOKIE['_sfs_id'];
        $this->assertIsString( $issued_session );

        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ) );
        $receipt_before = get_option( $descriptor['receipt_option'], false );
        $this->assertIsArray( $receipt_before );
        $this->assertFalse( get_option( $descriptor['claim_option'], false ), 'No claim must exist before the first claim attempt.' );
        $file_contents_before = file_get_contents( $created['file'] );

        $other_session = substr( hash( 'sha256', 'row8-inspect-before-claim:' . wp_generate_uuid4() ), 0, 42 );
        update_option( '_sfsdata_' . $other_session, array( 'expires' => time() + 3600 ), false );
        try {
            $_COOKIE['_sfs_id'] = $other_session;
            $this->assertFalse(
                $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) ),
                'A claim attempted under the wrong anonymous session must be refused.'
            );
            $this->assertSame( $receipt_before, get_option( $descriptor['receipt_option'], false ), 'The wrong-identity claim attempt mutated the receipt.' );
            $this->assertFalse( get_option( $descriptor['claim_option'], false ), 'The wrong-identity claim attempt created a claim record.' );
            $this->assertFileExists( $created['file'] );
            $this->assertSame( $file_contents_before, file_get_contents( $created['file'] ) );
        } finally {
            delete_option( '_sfsdata_' . $other_session );
        }

        $_COOKIE['_sfs_id'] = $issued_session;
        $restored_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $restored_descriptor ), 'Restoring the issuing session must still resolve the untouched receipt.' );
        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $restored_descriptor ) ) );
        $this->assertCount( 1, $claims );
        $consumed = $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) );
        $this->assertCount( 1, $consumed );
        $this->assertSame( $created['owned']['file'], $consumed[0]['file'] );
        $this->assertFalse( get_option( $descriptor['receipt_option'], false ) );
        $this->assertFalse( get_option( $descriptor['claim_option'], false ) );
    }

    public function test_switching_the_actor_after_claim_but_before_consume_blocks_consume_until_identity_is_restored() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id );
        $token = $this->issue_receipt( $created['owned'] );
        $this->assertSame( 0, get_current_user_id() );

        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ) );
        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
        $this->assertCount( 1, $claims );
        $receipt_before = get_option( $descriptor['receipt_option'], false );
        $claim_before = get_option( $descriptor['claim_option'], false );
        $this->assertIsArray( $receipt_before );
        $this->assertIsArray( $claim_before );
        $file_contents_before = file_get_contents( $created['file'] );

        $subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        wp_set_current_user( $subscriber_id );
        try {
            $this->assertFalse(
                $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) ),
                'A consume attempted as a different logged-in actor must be refused.'
            );
            $this->assertSame( $receipt_before, get_option( $descriptor['receipt_option'], false ), 'The wrong-actor consume attempt mutated the receipt.' );
            $this->assertSame( $claim_before, get_option( $descriptor['claim_option'], false ), 'The wrong-actor consume attempt mutated or released the claim.' );
            $this->assertFileExists( $created['file'] );
            $this->assertSame( $file_contents_before, file_get_contents( $created['file'] ) );
        } finally {
            wp_set_current_user( 0 );
        }

        $this->assertSame( $claim_before, get_option( $descriptor['claim_option'], false ), 'Restoring the anonymous actor must not by itself have changed the untouched claim.' );
        $consumed = $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) );
        $this->assertCount( 1, $consumed );
        $this->assertSame( $created['owned']['file'], $consumed[0]['file'] );
        $this->assertFalse( get_option( $descriptor['receipt_option'], false ) );
        $this->assertFalse( get_option( $descriptor['claim_option'], false ) );
    }

    public function test_switching_the_anonymous_session_before_rollback_blocks_rollback_until_identity_is_restored() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id );
        $token = $this->issue_receipt( $created['owned'] );
        $issued_session = $_COOKIE['_sfs_id'];
        $this->assertIsString( $issued_session );

        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ) );
        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
        $this->assertCount( 1, $claims );
        $receipt_before = get_option( $descriptor['receipt_option'], false );
        $claim_before = get_option( $descriptor['claim_option'], false );
        $this->assertIsArray( $receipt_before );
        $this->assertIsArray( $claim_before );
        $file_contents_before = file_get_contents( $created['file'] );

        $other_session = substr( hash( 'sha256', 'row8-before-rollback:' . wp_generate_uuid4() ), 0, 42 );
        update_option( '_sfsdata_' . $other_session, array( 'expires' => time() + 3600 ), false );
        try {
            $_COOKIE['_sfs_id'] = $other_session;
            $this->assertFalse(
                $this->invoke_ajax_private( 'rollback_upload_receipt_claims', array( $claims ) ),
                'A rollback attempted under the wrong anonymous session must be refused.'
            );
            $this->assertSame( $receipt_before, get_option( $descriptor['receipt_option'], false ), 'The wrong-identity rollback attempt mutated the receipt.' );
            $this->assertSame( $claim_before, get_option( $descriptor['claim_option'], false ), 'The wrong-identity rollback attempt released or mutated the claim.' );
            $this->assertFileExists( $created['file'] );
            $this->assertSame( $file_contents_before, file_get_contents( $created['file'] ) );
        } finally {
            delete_option( '_sfsdata_' . $other_session );
        }

        $_COOKIE['_sfs_id'] = $issued_session;
        $this->assertTrue(
            $this->invoke_ajax_private( 'rollback_upload_receipt_claims', array( $claims ) ),
            'Restoring the issuing session must let the very same claim set roll back.'
        );
        $this->assertFalse( get_option( $descriptor['claim_option'], false ), 'A successful rollback must release the claim.' );
        $this->assertSame( $receipt_before, get_option( $descriptor['receipt_option'], false ), 'A rollback must never touch the still-live receipt.' );
        $this->assertFileExists( $created['file'] );

        // After rollback, the receipt can still complete a fresh claim/consume cycle.
        $fresh_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $fresh_descriptor ) );
        $fresh_claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $fresh_descriptor ) ) );
        $this->assertCount( 1, $fresh_claims );
        $consumed = $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $fresh_claims ) );
        $this->assertCount( 1, $consumed );
        $this->assertSame( $created['owned']['file'], $consumed[0]['file'] );
        $this->assertFalse( get_option( $descriptor['receipt_option'], false ) );
        $this->assertFalse( get_option( $descriptor['claim_option'], false ) );
    }
}
