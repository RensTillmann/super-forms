<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Super_Forms_Upload_Receipt_Security extends Super_Forms_Upload_Security_Test_Case {
    public function test_cookie_less_receipt_is_a_random_hash_bound_server_capability() {
        unset( $_COOKIE['_sfs_id'] );
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id );
        $token = $this->issue_receipt( $created['owned'] );
        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );

        $this->assertTrue( is_array( $descriptor ) );
        $this->assertSame( hash( 'sha256', $token ), $descriptor['token_hash'] );
        $this->assertNotSame( $descriptor['receipt_option'], $descriptor['claim_option'] );
        $this->assertStringContainsString( $descriptor['token_hash'], $descriptor['receipt_option'] );
        $this->assertStringContainsString( $descriptor['token_hash'], $descriptor['claim_option'] );
        $this->assertStringNotContainsString( $token, $descriptor['receipt_option'] );
        $this->assertStringNotContainsString( $token, $descriptor['claim_option'] );
        $this->assertGreaterThan( time(), $descriptor['expires'] );
        $this->assertSame( $created['owned']['file'], $descriptor['owned']['file'] );

        $stored = get_option( $descriptor['receipt_option'], false );
        $this->assertTrue( is_array( $stored ) );
        $this->assertSame( $descriptor['token_hash'], $stored['token_hash'] );
        $this->assertStringNotContainsString( $token, serialize( $stored ) );
        $this->assertFalse( SUPER_Common::getClientData( 'upload_receipts' ) );

        $this->assertTrue( $this->invoke_ajax_private( 'csrf_policy_allows_request', array( false, array( 'csrf_check' => 'false' ) ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'csrf_policy_allows_request', array( false, array() ) ) );

        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
        $this->assertCount( 1, $claims );
        $consumed = $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) );
        $this->assertCount( 1, $consumed );
        $this->assertSame( $created['owned']['file'], $consumed[0]['file'] );
    }

    public function test_partial_receipt_consumption_failure_removes_every_claim_receipt_and_owned_file_before_retry() {
        $form_id = $this->create_form( 'publish' );
        $first = $this->create_owned_upload( $form_id, 'documents', false, 'first receipt' );
        $second = $this->create_owned_upload( $form_id, 'documents', false, 'second receipt' );
        $first_token = $this->issue_receipt( $first['owned'] );
        $second_token = $this->issue_receipt( $second['owned'] );
        $first_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $first_token, $form_id, 'documents' ) );
        $second_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $second_token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $first_descriptor ) );
        $this->assertTrue( is_array( $second_descriptor ) );
        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $first_descriptor, $second_descriptor ) ) );
        $this->assertCount( 2, $claims );

        $failed_deletions = 0;
        $fail_second_receipt_delete_once = static function( $query ) use ( &$failed_deletions, $second_descriptor ) {
            if( $failed_deletions===0
                && strpos( strtoupper( ltrim( $query ) ), 'DELETE FROM ' )===0
                && strpos( $query, $second_descriptor['receipt_option'] )!==false ) {
                $failed_deletions++;
                return $query . ' AND 1 = 0';
            }
            return $query;
        };
        $this->add_upload_filter( 'query', $fail_second_receipt_delete_once );

        $this->assertFalse(
            $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) ),
            'A failed downstream receipt delete must fail closed instead of leaving a partially consumable set.'
        );
        $this->assertSame( 1, $failed_deletions, 'The test must inject exactly one receipt-delete failure.' );
        global $wpdb;

        foreach( array(
            array( $first_token, $first_descriptor, $first['file'] ),
            array( $second_token, $second_descriptor, $second['file'] ),
        ) as $receipt ) {
            list( $token, $descriptor, $file ) = $receipt;
            $this->assertFalse( get_option( $descriptor['receipt_option'], false ) );
            $this->assertFalse( get_option( $descriptor['claim_option'], false ) );
            foreach( array( $descriptor['receipt_option'], $descriptor['claim_option'] ) as $option_name ) {
                $durable_count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
                        $option_name
                    )
                );
                $this->assertSame( '0', (string) $durable_count, 'Failed delete left a durable receipt row behind the option cache.' );
            }
            $this->assertFileDoesNotExist( $file );
            $this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) ) );
            $this->assertFalse( $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) ) );
        }
    }

    public function test_receipts_reject_wrong_form_field_expiry_and_replay_without_burning_valid_use() {
        $form_id = $this->create_form( 'publish' );
        $other_form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id, 'documents' );
        $token = $this->issue_receipt( $created['owned'] );

        $this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( 'not-a-token', $form_id, 'documents' ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $other_form_id, 'documents' ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'avatar' ) ) );

        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ), 'Wrong binding checks must not consume a valid receipt.' );
        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
        $this->assertCount( 1, $claims );
        $this->assertCount( 1, $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) ) );
        $this->assertFalse( $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) ) );

        $expired_created = $this->create_owned_upload( $form_id, 'documents' );
        $expired_token = $this->issue_receipt( $expired_created['owned'] );
        $expired_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $expired_token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $expired_descriptor ) );
        $receipt_option = $expired_descriptor['receipt_option'];
        $record = get_option( $receipt_option, false );
        $this->assertTrue( is_array( $record ) );
        $record['expires'] = time() - 1;
        update_option( $receipt_option, $record, false );
        $this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $expired_token, $form_id, 'documents' ) ) );
        $this->assertTrue( SUPER_Ajax::cleanup_expired_upload_receipt( $expired_descriptor['token_hash'] ) );
        $this->assertFileDoesNotExist( $expired_created['file'] );
        $this->assertFalse( get_option( $expired_descriptor['receipt_option'], false ) );
        $this->assertFalse( get_option( $expired_descriptor['claim_option'], false ) );
    }

    public function test_multiple_claims_are_all_or_nothing_and_duplicate_tokens_fail_closed() {
        $form_id = $this->create_form( 'publish' );
        $first = $this->create_owned_upload( $form_id, 'documents', false, 'first' );
        $second = $this->create_owned_upload( $form_id, 'documents', false, 'second' );
        $first_token = $this->issue_receipt( $first['owned'] );
        $second_token = $this->issue_receipt( $second['owned'] );
        $first_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $first_token, $form_id, 'documents' ) );
        $second_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $second_token, $form_id, 'documents' ) );

        $this->assertFalse( $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $first_descriptor, $first_descriptor ) ) ) );
        $first_claim = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $first_descriptor ) ) );
        $this->assertCount( 1, $first_claim, 'A duplicate-token refusal must not strand a claim.' );
        $this->assertTrue( $this->invoke_ajax_private( 'rollback_upload_receipt_claims', array( $first_claim ) ) );

        $second_claim = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $second_descriptor ) ) );
        $this->assertCount( 1, $second_claim );
        $this->assertFalse(
            $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $first_descriptor, $second_descriptor ) ) ),
            'A conflict on the second token must roll back the first token acquired in this attempt.'
        );
        $this->assertTrue( $this->invoke_ajax_private( 'rollback_upload_receipt_claims', array( $second_claim ) ) );

        $fresh_first = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $first_token, $form_id, 'documents' ) );
        $fresh_second = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $second_token, $form_id, 'documents' ) );
        $both = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $fresh_first, $fresh_second ) ) );
        $this->assertCount( 2, $both );
        $this->assertTrue( $this->invoke_ajax_private( 'rollback_upload_receipt_claims', array( $both ) ) );

        $both = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $fresh_first, $fresh_second ) ) );
        $consumed = $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $both ) );
        $this->assertCount( 2, $consumed );
        $this->assertSame( array( $first['owned']['file'], $second['owned']['file'] ), array( $consumed[0]['file'], $consumed[1]['file'] ) );
    }

    public function test_only_exact_expired_submission_claims_are_reclaimed() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id, 'documents' );
        $token = $this->issue_receipt( $created['owned'] );
        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ) );

        update_option( $descriptor['claim_option'], array(
            'version' => 1,
            'claim_id' => str_repeat( 'a', 64 ),
            'token_hash' => $descriptor['token_hash'],
            'expires' => $descriptor['expires'],
            'purpose' => 'submission',
            'claimed_at' => time() - 2 * MINUTE_IN_SECONDS - 1,
        ), false );
        $reclaimed = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
        $this->assertCount( 1, $reclaimed );
        $this->assertTrue( $this->invoke_ajax_private( 'rollback_upload_receipt_claims', array( $reclaimed ) ) );

        $active = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
        $this->assertCount( 1, $active );
        $this->assertFalse(
            $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) ),
            'A live submission claim must never be reclaimed.'
        );
        $this->assertTrue( $this->invoke_ajax_private( 'rollback_upload_receipt_claims', array( $active ) ) );
    }

    public function test_failed_multi_consume_does_not_partially_consume_an_independent_receipt() {
        $form_id = $this->create_form( 'publish' );
        $first = $this->create_owned_upload( $form_id, 'documents', false, 'first' );
        $second = $this->create_owned_upload( $form_id, 'documents', false, 'second' );
        $first_token = $this->issue_receipt( $first['owned'] );
        $second_token = $this->issue_receipt( $second['owned'] );
        $first_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $first_token, $form_id, 'documents' ) );
        $second_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $second_token, $form_id, 'documents' ) );
        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $first_descriptor, $second_descriptor ) ) );
        $this->assertCount( 2, $claims );

        delete_option( $second_descriptor['receipt_option'] );
        $this->assertFalse( $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) ) );

        $first_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $first_token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $first_descriptor ), 'The first receipt must survive a later-record consume failure.' );
        $first_claim = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $first_descriptor ) ) );
        $this->assertCount( 1, $first_claim, 'Failed consumption must release claims it can safely roll back.' );
        $this->assertCount( 1, $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $first_claim ) ) );
    }

    public function test_independent_receipts_survive_interleaved_issue_claim_rollback_and_consume() {
        $form_id = $this->create_form( 'publish' );
        $first = $this->create_owned_upload( $form_id, 'documents', false, 'first' );
        $second = $this->create_owned_upload( $form_id, 'documents', false, 'second' );
        $third = $this->create_owned_upload( $form_id, 'documents', false, 'third' );
        $first_token = $this->issue_receipt( $first['owned'] );
        $second_token = $this->issue_receipt( $second['owned'] );
        $this->assertNotSame( $first_token, $second_token );

        $first_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $first_token, $form_id, 'documents' ) );
        $first_claim = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $first_descriptor ) ) );
        $this->assertCount( 1, $first_claim );

        $third_token = $this->issue_receipt( $third['owned'] );
        $this->assertNotContains( $third_token, array( $first_token, $second_token ) );
        $this->assertTrue( $this->invoke_ajax_private( 'rollback_upload_receipt_claims', array( $first_claim ) ) );

        $second_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $second_token, $form_id, 'documents' ) );
        $second_claim = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $second_descriptor ) ) );
        $this->assertCount( 1, $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $second_claim ) ) );
        $this->assertFalse( get_option( $second_descriptor['receipt_option'], false ) );

        $fourth = $this->create_owned_upload( $form_id, 'documents', false, 'fourth' );
        $fourth_token = $this->issue_receipt( $fourth['owned'] );
        $this->assertFalse( get_option( $second_descriptor['receipt_option'], false ), 'Issuing another token must not resurrect a consumed one.' );

        foreach( array(
            array( $first_token, $first['owned']['file'] ),
            array( $third_token, $third['owned']['file'] ),
            array( $fourth_token, $fourth['owned']['file'] ),
        ) as $expected ) {
            $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $expected[0], $form_id, 'documents' ) );
            $this->assertTrue( is_array( $descriptor ) );
            $claim = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
            $consumed = $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claim ) );
            $this->assertSame( $expected[1], $consumed[0]['file'] );
        }
    }

    public function test_unproven_terminal_cleanup_retains_its_receipt_for_retry() {
        $form_id = $this->create_form( 'publish' );
        $created = $this->create_owned_upload( $form_id, 'documents' );
        $token = $this->issue_receipt( $created['owned'] );
        $hash = hash( 'sha256', $token );
        $this->assertTrue( $this->invoke_ajax_private( 'append_pending_owned_upload_cleanup', array( $created['owned'], $token ) ) );
        // The owned file disappears out of band, so cleanup cannot prove deletion.
        $this->assertTrue( unlink( $created['file'] ) );
        $this->invoke_ajax_private( 'cleanup_pending_owned_uploads' );
        $this->assertTrue(
            is_array( get_option( '_super_upload_receipt_' . $hash, false ) ),
            'An unproven cleanup discarded the receipt that authorizes a retry.'
        );
    }

    public function test_receipt_is_present_at_validation_boundary_and_consumed_before_file_aware_hooks() {
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents' ) ) );
        $created = $this->create_owned_upload( $form_id, 'documents' );
        $token = $this->issue_receipt( $created['owned'] );
        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ) );
        $receipt_option = $descriptor['receipt_option'];
        $settings_boundary_record = null;
        $hook_boundary_record = null;
        $settings_boundary_data = null;
        $hook_boundary_data = null;

        $settings_filter = static function( $settings, $atts ) use ( $receipt_option, &$settings_boundary_record, &$settings_boundary_data ) {
            $settings_boundary_record = get_option( $receipt_option, false );
            $settings_boundary_data = $atts['data'];
            return $settings;
        };
        $file_hook = static function( $atts ) use ( $receipt_option, &$hook_boundary_record, &$hook_boundary_data ) {
            $hook_boundary_record = get_option( $receipt_option, false );
            $hook_boundary_data = $atts['data'];
        };
        $this->add_upload_filter( 'super_before_submit_form_settings_filter', $settings_filter, 10, 2 );
        $this->add_upload_filter( 'super_before_sending_email_hook', $file_hook, 10, 1 );

        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array( array( 'upload_token' => $token ) ),
            ),
        );
        $this->set_request( $form_id, $data );
        $atts = SUPER_Ajax::submit_form_checks( false );
        foreach( $atts['owned_files'] as $owned_file ) {
            $this->track_owned_cleanup( $owned_file );
        }

        $this->assertTrue( is_array( $settings_boundary_record ) );
        $this->assertTrue( is_array( $settings_boundary_data ) );
        $this->assertArrayNotHasKey(
            'documents',
            $settings_boundary_data,
            'Pre-validation settings extensions must receive no file carrier or authority.'
        );
        $this->assertFalse( $hook_boundary_record, 'The bearer capability must be consumed before the first file-aware hook.' );
        $this->assertTrue( is_array( $hook_boundary_data ) );
        $this->assertCount( 1, $hook_boundary_data['documents']['files'] );
        $server_file = $hook_boundary_data['documents']['files'][0];
        $this->assertArrayNotHasKey( 'upload_token', $server_file );
        $this->assertArrayNotHasKey( 'file', $server_file );
        $this->assertArrayNotHasKey( 'allowed_root', $server_file );
        $this->assertSame( 'documents', $server_file['name'] );
        $this->assertSame( $created['owned']['file'], $atts['owned_files'][0]['file'] );
        $this->assertFalse( $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) ) );
    }

    public function test_required_recaptcha_and_form_locker_rejections_preserve_receipt() {
        $required_elements = array(
            $this->file_element( 'documents' ),
            array( 'tag' => 'text', 'data' => array(
                'name' => 'required_name',
                'validation' => 'required',
                'may_be_empty' => 'false',
            ) ),
        );
        $this->assert_validation_rejection_preserves_receipt(
            $required_elements,
            array(),
            array( 'required_name' => array( 'type' => 'text', 'value' => '' ) ),
            array(),
            'required fields'
        );

        $recaptcha_elements = array(
            $this->file_element( 'documents' ),
            array( 'tag' => 'recaptcha', 'data' => array() ),
        );
        $this->assert_validation_rejection_preserves_receipt(
            $recaptcha_elements,
            array(),
            array(),
            array(),
            'reCAPTCHA verification is required'
        );

        $locker_settings = array(
            'form_locker' => 'true',
            'form_locker_limit' => 1,
            'form_locker_msg_title' => 'Locked receipt',
            'form_locker_msg_desc' => 'Try again later.',
            'user_form_locker' => '',
        );
        $this->assert_validation_rejection_preserves_receipt(
            array( $this->file_element( 'documents' ) ),
            $locker_settings,
            array(),
            array( '_super_submission_count' => 1 ),
            'Locked receipt'
        );
    }

    private function assert_validation_rejection_preserves_receipt( $elements, $settings, $extra_data, $form_meta, $message ) {
        $form_id = $this->create_form( 'publish', $elements, $settings );
        foreach( $form_meta as $key => $value ) {
            update_post_meta( $form_id, $key, $value );
        }
        $created = $this->create_owned_upload( $form_id, 'documents' );
        $token = $this->issue_receipt( $created['owned'] );
        $issued_descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $issued_descriptor ) );
        $data = array_merge( array(
            'documents' => array(
                'type' => 'files',
                'files' => array( array( 'upload_token' => $token ) ),
            ),
        ), $extra_data );
        $this->set_request( $form_id, $data );

        $callback = static function() use ( $settings ) {
            SUPER_Ajax::submit_form_checks( false );
        };
        $this->assert_handler_rejected_with( $callback, $message );
        $this->assertNotNull( get_post( $form_id ), 'The child validation request invalidated the parent test transaction.' );
        $this->assertFileExists( $created['owned']['file'], 'A validation-only rejection removed the unconsumed owned file.' );
        $this->assertTrue(
            is_array( get_option( $issued_descriptor['receipt_option'], false ) ),
            'A validation-only rejection removed the unconsumed receipt option.'
        );

        $descriptor = $this->invoke_ajax_private( 'inspect_upload_receipt', array( $token, $form_id, 'documents' ) );
        $this->assertTrue( is_array( $descriptor ), 'A validation-only rejection must leave the receipt usable.' );
        $claims = $this->invoke_ajax_private( 'claim_upload_receipts', array( array( $descriptor ) ) );
        $this->assertCount( 1, $claims );
        $consumed = $this->invoke_ajax_private( 'consume_upload_receipt_claims', array( $claims ) );
        $this->assertCount( 1, $consumed );
        $this->assertSame( $created['owned']['file'], $consumed[0]['file'] );
    }
}
