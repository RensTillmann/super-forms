<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

/**
 * File cleanup after a finalized submission (file_upload_submission_delete, vcard_delete,
 * file_upload_entry_delete) on entry edits that keep an existing file. Every scenario drives
 * the complete SUPER_Ajax::submit_form() flow inside the fork helper and asserts on the
 * database and filesystem afterwards in the parent.
 */
class Test_Super_Forms_Upload_Cleanup_Security extends Super_Forms_Upload_Security_Test_Case {
    private $had_logged_in_cookie = false;
    private $original_logged_in_cookie = null;
    private $capture_files = array();

    /**
     * PHP notices tolerated on the submit path: none. (The undefined $update_entry_status
     * notice is fixed; any notice now fails these tests.)
     */
    private static $tolerated_notices = array();

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

    public function test_flag_on_edit_deletes_kept_attachment_and_new_upload_after_saving_entry() {
        $fx = $this->kept_attachment_fixture( array( 'file_upload_submission_delete' => 'true' ) );
        $new = $this->new_media_upload( $fx );

        $decoded = $this->submit_edit( $fx, $this->edit_data( $fx, $new ) );

        $this->assert_success( $decoded );
        $this->assertNull( get_post( $fx['attachment'] ), 'The kept attachment is deleted like 6.4.007 did.' );
        $this->assertFileDoesNotExist( $fx['file'] );
        $this->assertNull( get_post( $new['attachment'] ) );
        $this->assertFileDoesNotExist( $new['file'] );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertSame( array( $fx['attachment'], $new['attachment'] ), $this->saved_attachment_ids( $saved ) );
        $this->assertSame( 'super_contact_entry', get_post_type( $fx['entry_id'] ) );
    }

    public function test_flag_off_edit_keeps_attachment_and_reparents_new_upload_to_entry() {
        $fx = $this->kept_attachment_fixture();
        $new = $this->new_media_upload( $fx );

        $decoded = $this->submit_edit( $fx, $this->edit_data( $fx, $new ) );

        $this->assert_success( $decoded );
        $this->assertSame( 'attachment', get_post_type( $fx['attachment'] ) );
        $this->assertSame( $fx['entry_id'], wp_get_post_parent_id( $fx['attachment'] ) );
        $this->assertFileExists( $fx['file'] );
        $this->assertSame( 'attachment', get_post_type( $new['attachment'] ) );
        $this->assertSame( $fx['entry_id'], wp_get_post_parent_id( $new['attachment'] ) );
        $this->assertFileExists( $new['file'] );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertSame( array( $fx['attachment'], $new['attachment'] ), $this->saved_attachment_ids( $saved ) );
    }

    public function test_refused_edits_with_flag_on_never_delete_the_kept_attachment() {
        $fx = $this->kept_attachment_fixture( array( 'file_upload_submission_delete' => 'true' ) );
        $original_entry_data = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );

        // Invalid upload receipt next to the kept file.
        $data = $this->edit_data( $fx );
        $data['documents']['files'][] = array( 'upload_token' => str_repeat( 'a', 64 ) );
        $decoded = $this->submit_edit( $fx, $data );
        $this->assert_error( $decoded, 'Invalid file upload receipt.' );
        $this->assert_kept_attachment_untouched( $fx, $original_entry_data );

        // Contract mismatch: a field the stored form does not have.
        $data = $this->edit_data( $fx );
        $data['not_a_form_field'] = array( 'name' => 'not_a_form_field', 'value' => 'x', 'type' => 'text' );
        $decoded = $this->submit_edit( $fx, $data );
        $this->assert_error( $decoded, 'Invalid form data.' );
        $this->assert_kept_attachment_untouched( $fx, $original_entry_data );

        // A downstream refusal after the checks passed (e.g. an account or payment action).
        $new = $this->new_media_upload( $fx );
        $refuse = static function() {
            SUPER_Common::output_message( array( 'error' => true, 'msg' => 'Downstream refusal.' ) );
        };
        $this->add_upload_filter( 'super_before_sending_email_hook', $refuse, 10, 1 );
        $decoded = $this->submit_edit( $fx, $this->edit_data( $fx, $new ) );
        $this->assert_error( $decoded, 'Downstream refusal.' );
        $this->assert_kept_attachment_untouched( $fx, $original_entry_data );
    }

    public function test_flag_on_files_exist_while_emails_are_sent_and_loop_shows_plain_names() {
        $fx = $this->kept_attachment_fixture(
            array_merge( array( 'file_upload_submission_delete' => 'true' ), $this->email_settings() ),
            false
        );
        $new = $this->new_media_upload( $fx );
        $capture = $this->capture_file();
        $kept_file = $fx['file'];
        $new_file = $new['file'];
        $kept_real = wp_normalize_path( realpath( $kept_file ) );
        $new_real = wp_normalize_path( realpath( $new_file ) );
        $kept_id = $fx['attachment'];
        $new_id = $new['attachment'];
        $mail_filter = static function( $atts ) use ( $capture, $kept_file, $new_file, $kept_id, $new_id ) {
            $attachments = array_values( (array) $atts['attachments'] );
            $existing = array();
            foreach( $attachments as $path ) {
                $existing[] = is_file( $path );
            }
            file_put_contents( $capture, wp_json_encode( array(
                'to' => $atts['to'],
                'message' => $atts['message'],
                'attachments' => $attachments,
                'attachments_exist' => $existing,
                'kept_file' => is_file( $kept_file ),
                'new_file' => is_file( $new_file ),
                'kept_post' => get_post_type( $kept_id ),
                'new_post' => get_post_type( $new_id ),
            ) ) . "\n", FILE_APPEND | LOCK_EX );
            return $atts;
        };
        $this->add_upload_filter( 'wp_mail', $mail_filter, 10, 1 );

        $decoded = $this->submit_edit( $fx, $this->edit_data( $fx, $new ), false );

        $this->assert_success( $decoded );
        $mails = $this->read_capture( $capture );
        $this->assertCount( 2, $mails, 'Admin and confirmation emails are both sent.' );
        $this->assertSame( array( 'admin@example.org' ), (array) $mails[0]['to'] );
        $this->assertSame( array( 'visitor@example.org' ), (array) $mails[1]['to'] );
        foreach( $mails as $mail ) {
            $this->assertTrue( $mail['kept_file'], 'The kept file must still exist when the email is sent.' );
            $this->assertTrue( $mail['new_file'], 'The new upload must still exist when the email is sent.' );
            $this->assertSame( 'attachment', $mail['kept_post'] );
            $this->assertSame( 'attachment', $mail['new_post'] );
            $this->assertContains( $kept_real, $mail['attachments'] );
            $this->assertContains( $new_real, $mail['attachments'] );
            $this->assertNotContains( false, $mail['attachments_exist'] );
            $this->assertStringContainsString( basename( $kept_file ), $mail['message'] );
            $this->assertStringContainsString( basename( $new_file ), $mail['message'] );
            $this->assertStringNotContainsString( '<a ', $mail['message'], 'Deleted files are listed as plain names.' );
            $this->assertStringNotContainsString( 'href', $mail['message'] );
        }
        $this->assertNull( get_post( $fx['attachment'] ) );
        $this->assertFileDoesNotExist( $fx['file'] );
        $this->assertNull( get_post( $new['attachment'] ) );
        $this->assertFileDoesNotExist( $new['file'] );
    }

    public function test_vcard_delete_with_flag_off_removes_only_the_vcard() {
        $fx = $this->kept_attachment_fixture( array(
            'vcard_enable' => 'admin',
            'vcard_name' => 'cleanup-vcard',
            'vcard_content' => "VERSION:4.0\nFN:Cleanup Test",
            'vcard_delete' => 'true',
        ) );
        $new = $this->new_media_upload( $fx );
        $capture = $this->capture_file();
        $observer = static function( $args ) use ( $capture ) {
            $vcard = isset( $args['data']['_vcard']['files'][0] ) ? $args['data']['_vcard']['files'][0] : array();
            $attachment = isset( $vcard['attachment'] ) ? absint( $vcard['attachment'] ) : 0;
            $path = $attachment ? get_attached_file( $attachment ) : ( isset( $vcard['path'] ) ? $vcard['path'] : '' );
            file_put_contents( $capture, wp_json_encode( array(
                'attachment' => $attachment,
                'path' => $path,
                'exists' => is_string( $path ) && $path!=='' && is_file( $path ),
            ) ) . "\n", FILE_APPEND | LOCK_EX );
        };
        $this->add_upload_filter( 'super_before_email_success_msg_action', $observer, 10, 1 );

        $decoded = $this->submit_edit( $fx, $this->edit_data( $fx, $new ) );

        $this->assert_success( $decoded );
        $observed = $this->read_capture( $capture );
        $this->assertCount( 1, $observed );
        $this->assertTrue( $observed[0]['exists'], 'The vCard was generated for this submission.' );
        $this->assertGreaterThan( 0, $observed[0]['attachment'] );
        $this->attachment_ids[] = $observed[0]['attachment'];
        $this->assertNull( get_post( $observed[0]['attachment'] ), 'vcard_delete removes the vCard.' );
        $this->assertFileDoesNotExist( $observed[0]['path'] );
        $this->assertSame( 'attachment', get_post_type( $fx['attachment'] ) );
        $this->assertSame( $fx['entry_id'], wp_get_post_parent_id( $fx['attachment'] ) );
        $this->assertFileExists( $fx['file'] );
        $this->assertSame( 'attachment', get_post_type( $new['attachment'] ) );
        $this->assertSame( $fx['entry_id'], wp_get_post_parent_id( $new['attachment'] ) );
        $this->assertFileExists( $new['file'] );
    }

    public function test_flag_on_deletes_proof_matched_kept_custom_file_and_keeps_legacy_one() {
        list( $parent, $root ) = $this->create_temporary_root( true );
        $actor_id = $this->login_actor();
        $settings = array_merge(
            $this->listing_settings(),
            array(
                'file_upload_dir' => '../' . basename( $parent ) . '/' . basename( $root ),
                'file_upload_submission_delete' => 'true',
            )
        );
        $elements = array( $this->file_element( 'documents', array( 'extensions' => 'png' ) ) );
        $form_id = $this->create_form( 'publish', $elements, $settings, $actor_id );
        $entry_id = $this->create_entry( $form_id, $actor_id );
        $proof_record = $this->custom_file_record( $form_id, $parent, $root, '1234567890123', 'proof.png', $settings );
        $legacy_record = $this->custom_file_record( $form_id, $parent, $root, '1234567890124', 'legacy.png', $settings );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $proof_record['_super_file_proof'] );
        unset( $legacy_record['_super_file_proof'] );
        update_post_meta( $entry_id, '_super_contact_entry_data', array(
            'documents' => array(
                'type' => 'files',
                'files' => array( $proof_record, $legacy_record ),
            ),
        ) );
        $this->configure_csrf( 'false' );
        $fx = array( 'form_id' => $form_id, 'entry_id' => $entry_id );
        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array(
                    array( 'value' => $proof_record['value'], 'url' => $proof_record['url'], 'retention_token' => 'entry' ),
                    array( 'value' => $legacy_record['value'], 'url' => $legacy_record['url'], 'retention_token' => 'entry' ),
                ),
            ),
        );

        $decoded = $this->submit_edit( $fx, $data );

        $this->assert_success( $decoded );
        $this->assertFileDoesNotExist( $proof_record['path'], 'A proof-matched kept custom file is deleted.' );
        $this->assertFileExists( $legacy_record['path'], 'A legacy kept custom file without a proof is kept.' );
    }

    public function test_exclude_entry_file_field_edit_with_flag_on_succeeds_and_keeps_kept_file() {
        $fx = $this->kept_attachment_fixture( array( 'file_upload_submission_delete' => 'true' ) );
        $new = $this->new_media_upload( $fx );

        $decoded = $this->submit_edit( $fx, $this->edit_data( $fx, $new, array( 'exclude_entry' => 'true' ) ) );

        $this->assert_success( $decoded );
        $this->assertStringNotContainsString( 'Unable to delete uploaded file.', wp_json_encode( $decoded ) );
        $saved = SUPER_Data_Access::get_entry_data( $fx['entry_id'] );
        $this->assertArrayNotHasKey( 'documents', $saved, 'The excluded field is not saved in the entry.' );
        $this->assertSame( 'attachment', get_post_type( $fx['attachment'] ), 'A kept file that no longer matches the entry is skipped.' );
        $this->assertFileExists( $fx['file'] );
        // The new upload is owned by this submission, so it is still cleaned up.
        $this->assertNull( get_post( $new['attachment'] ) );
        $this->assertFileDoesNotExist( $new['file'] );
    }

    public function test_generated_pdf_on_edit_with_flag_on_is_deleted_after_emails() {
        $fx = $this->kept_attachment_fixture(
            array_merge(
                array(
                    'file_upload_submission_delete' => 'true',
                    '_pdf' => array( 'generate' => 'true' ),
                ),
                $this->email_settings()
            ),
            false
        );
        $capture = $this->capture_file();
        $mail_filter = static function( $atts ) use ( $capture ) {
            $existing = array();
            foreach( (array) $atts['attachments'] as $path ) {
                $existing[ basename( $path ) ] = is_file( $path );
            }
            file_put_contents( $capture, wp_json_encode( array( 'mail' => $existing ) ) . "\n", FILE_APPEND | LOCK_EX );
            return $atts;
        };
        $observer = static function( $args ) use ( $capture ) {
            $pdf = isset( $args['data']['_generated_pdf_file']['files'][0] ) ? $args['data']['_generated_pdf_file']['files'][0] : array();
            $attachment = isset( $pdf['attachment'] ) ? absint( $pdf['attachment'] ) : 0;
            $path = $attachment ? get_attached_file( $attachment ) : ( isset( $pdf['path'] ) ? $pdf['path'] : '' );
            file_put_contents( $capture, wp_json_encode( array( 'pdf' => array(
                'attachment' => $attachment,
                'path' => $path,
                'exists' => is_string( $path ) && $path!=='' && is_file( $path ),
            ) ) ) . "\n", FILE_APPEND | LOCK_EX );
        };
        $this->add_upload_filter( 'wp_mail', $mail_filter, 10, 1 );
        $this->add_upload_filter( 'super_before_email_success_msg_action', $observer, 10, 1 );
        $data = $this->edit_data( $fx );
        $data['_generated_pdf_file'] = array(
            'type' => 'files',
            'files' => array( array(
                'label' => 'Generated PDF',
                'name' => 'invoice.pdf',
                'value' => 'invoice.pdf',
                'datauristring' => 'data:application/pdf;base64,' . base64_encode(
                    "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF\n"
                ),
            ) ),
        );

        $decoded = $this->submit_edit( $fx, $data, false );

        $this->assert_success( $decoded );
        $observed = $this->read_capture( $capture );
        $mails = array_values( array_filter( $observed, static function( $row ) { return isset( $row['mail'] ); } ) );
        $pdfs = array_values( array_filter( $observed, static function( $row ) { return isset( $row['pdf'] ); } ) );
        $this->assertCount( 2, $mails );
        $this->assertCount( 1, $pdfs );
        $pdf = $pdfs[0]['pdf'];
        $this->assertTrue( $pdf['exists'], 'The generated PDF still exists after the emails were sent.' );
        foreach( $mails as $mail ) {
            $this->assertNotContains( false, $mail['mail'], 'Every email attachment exists at send time.' );
        }
        if( $pdf['attachment'] ) {
            $this->attachment_ids[] = $pdf['attachment'];
            $this->assertNull( get_post( $pdf['attachment'] ) );
        }
        $this->assertFileDoesNotExist( $pdf['path'], 'The generated PDF is deleted after the emails.' );
        $this->assertNull( get_post( $fx['attachment'] ) );
        $this->assertFileDoesNotExist( $fx['file'] );
    }

    public function test_entry_delete_setting_removes_kept_attachment_after_an_edit() {
        $fx = $this->kept_attachment_fixture();
        $new = $this->new_media_upload( $fx );

        $decoded = $this->submit_edit( $fx, $this->edit_data( $fx, $new ) );
        $this->assert_success( $decoded );
        $this->assertSame( $fx['entry_id'], wp_get_post_parent_id( $fx['attachment'] ) );
        $this->assertFileExists( $fx['file'] );

        $global = get_option( 'super_settings', array() );
        $global['file_upload_entry_delete'] = 'true';
        update_option( 'super_settings', $global, false );
        SUPER_Forms()->global_settings = $global;
        $this->assertNotFalse( wp_delete_post( $fx['entry_id'], true ) );

        $this->assertNull( get_post( $fx['entry_id'] ) );
        $this->assertNull( get_post( $fx['attachment'] ), 'Deleting the entry removes the kept attachment.' );
        $this->assertFileDoesNotExist( $fx['file'] );
        $this->assertNull( get_post( $new['attachment'] ) );
        $this->assertFileDoesNotExist( $new['file'] );
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

    private function listing_settings() {
        return array(
            'csrf_check' => 'false',
            '_listings' => array(
                'lists' => array(
                    array(
                        'display' => array( 'enabled' => 'false' ),
                        'edit_any' => array(
                            'enabled' => 'true',
                            'user_roles' => 'administrator',
                            'user_ids' => '',
                        ),
                    ),
                ),
            ),
        );
    }

    private function email_settings() {
        $loop = '<tr><th>{loop_label}</th><td>{loop_value}</td></tr>';
        return array(
            'send' => 'yes',
            'confirm' => 'yes',
            'header_to' => 'admin@example.org',
            'confirm_to' => 'visitor@example.org',
            'header_subject' => 'Admin copy',
            'confirm_subject' => 'Your copy',
            'header_from_type' => 'default',
            'confirm_from_type' => 'default',
            'email_body_open' => '',
            'email_body' => '{loop_fields}',
            'email_body_close' => '',
            'confirm_body_open' => '',
            'confirm_body' => '{loop_fields}',
            'confirm_body_close' => '',
            'email_loop' => $loop,
            'confirm_email_loop' => $loop,
        );
    }

    private function create_entry( $form_id, $actor_id ) {
        return self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'super_unread',
            'post_parent' => $form_id,
            'post_author' => $actor_id,
        ) );
    }

    /**
     * Media-library files live under the default Super Forms upload root inside the uploads
     * directory, like real uploads, so wp_delete_attachment() also removes them from disk.
     */
    private function create_media_root() {
        $base = trailingslashit( ABSPATH ) . SUPER_FORMS_UPLOAD_DIR;
        $this->assertTrue( wp_mkdir_p( $base ) );
        $parent = trailingslashit( $base ) . 'sf-cleanup-' . str_replace( '-', '', wp_generate_uuid4() );
        $root = trailingslashit( $parent ) . 'owned';
        $this->assertTrue( wp_mkdir_p( $root ) );
        $this->temporary_parents[] = realpath( $parent );
        return wp_normalize_path( realpath( $root ) );
    }

    private function media_attachment( $root, $prefix, $form_id, $parent_id ) {
        $filename = trailingslashit( $root ) . $prefix . '-' . bin2hex( random_bytes( 4 ) ) . '.png';
        $this->assertNotFalse( file_put_contents( $filename, $this->valid_png_bytes() ) );
        $attachment_id = wp_insert_attachment( array(
            'post_mime_type' => 'image/png',
            'post_title' => $prefix,
            'post_status' => 'inherit',
            'post_parent' => $parent_id,
        ), $filename, $parent_id );
        $this->assertTrue( is_int( $attachment_id ) && $attachment_id > 0 );
        $this->attachment_ids[] = $attachment_id;
        update_attached_file( $attachment_id, $filename );
        add_post_meta( $attachment_id, 'super-forms-form-upload-file', true );
        add_post_meta( $attachment_id, '_super_forms_upload_form_id', $form_id );
        add_post_meta( $attachment_id, '_super_forms_upload_field', 'documents' );
        return array( $filename, $attachment_id );
    }

    /**
     * An entry with one kept media-library attachment on field 'documents'; $listing chooses a
     * listing edit (listing grant) or an update_contact_entry edit (retrieve_last_entry_data).
     */
    private function kept_attachment_fixture( $form_settings=array(), $listing=true ) {
        $root = $this->create_media_root();
        $actor_id = $this->login_actor();
        $base = $listing
            ? $this->listing_settings()
            : array(
                'csrf_check' => 'false',
                'update_contact_entry' => 'true',
                'contact_entry_prevent_creation' => 'true',
                'send' => 'no',
                'confirm' => 'no',
            );
        $settings = array_merge( $base, $form_settings );
        $elements = array( $this->file_element( 'documents' ) );
        $form_id = $this->create_form( 'publish', $elements, $settings, $actor_id );
        $entry_id = $this->create_entry( $form_id, $actor_id );
        list( $filename, $attachment_id ) = $this->media_attachment( $root, 'kept', $form_id, $entry_id );
        $stored = array(
            'value' => basename( $filename ),
            'name' => 'documents',
            'type' => 'image/png',
            'url' => wp_get_attachment_url( $attachment_id ),
            'attachment' => $attachment_id,
        );
        update_post_meta( $entry_id, '_super_contact_entry_data', array(
            'documents' => array(
                'type' => 'files',
                'files' => array( $stored ),
            ),
        ) );
        $this->configure_csrf( 'false' );
        return array(
            'root' => $root,
            'actor_id' => $actor_id,
            'form_id' => $form_id,
            'entry_id' => $entry_id,
            'file' => $filename,
            'attachment' => $attachment_id,
            'stored' => $stored,
        );
    }

    /** A fresh upload receipt for field 'documents', as super_upload_files issues it. */
    private function new_media_upload( $fx ) {
        list( $filename, $attachment_id ) = $this->media_attachment( $fx['root'], 'new', $fx['form_id'], 0 );
        $owned = $this->invoke_ajax_private( 'build_owned_upload', array(
            $fx['form_id'],
            'documents',
            $filename,
            'image/png',
            wp_get_attachment_url( $attachment_id ),
            $attachment_id,
            $fx['root'],
            filesize( $filename ),
        ) );
        $this->assertTrue( is_array( $owned ) );
        $owned['route_name'] = 'documents';
        return array(
            'file' => $filename,
            'attachment' => $attachment_id,
            'token' => $this->issue_receipt( $owned ),
        );
    }

    private function custom_file_record( $form_id, $parent, $root, $slot_name, $basename, $settings ) {
        $slot = trailingslashit( $root ) . $slot_name;
        $this->assertTrue( wp_mkdir_p( $slot ) );
        $filename = trailingslashit( $slot ) . $basename;
        $this->assertNotFalse( file_put_contents( $filename, $this->valid_png_bytes() ) );
        $descriptor = SUPER_Forms::resolve_owned_upload_file( $filename, $settings );
        $this->assertTrue( is_array( $descriptor ) );
        $route = basename( $parent ) . '/' . basename( $root ) . '/' . $slot_name . '/' . $basename;
        $owned = $this->invoke_ajax_private( 'build_owned_upload', array(
            $form_id,
            'documents',
            $descriptor['file'],
            $descriptor['mime'],
            trailingslashit( get_option( 'siteurl' ) ) . 'sfgtfi/__/' . $route,
            0,
            $descriptor['root'],
            filesize( $filename ),
            '/../' . $route,
        ) );
        $this->assertTrue( is_array( $owned ) );
        $record = $this->invoke_ajax_private( 'owned_upload_file_record', array( $owned ) );
        $this->assertSame( wp_normalize_path( realpath( $filename ) ), $record['path'] );
        return $record;
    }

    private function edit_data( $fx, $new=null, $carrier_extra=array() ) {
        $files = array( array(
            'value' => $fx['stored']['value'],
            'url' => $fx['stored']['url'],
            'retention_token' => 'entry',
        ) );
        if( $new ) {
            $files[] = array( 'upload_token' => $new['token'] );
        }
        return array(
            'documents' => array_merge( array( 'type' => 'files', 'files' => $files ), $carrier_extra ),
        );
    }

    private function submit_edit( $fx, $data, $listing=true ) {
        $form_id = $fx['form_id'];
        $entry_id = $fx['entry_id'];
        $extra = array( 'entry_id' => (string) $entry_id );
        if( $listing ) {
            $extra['list_id'] = '0';
        }
        $this->set_request( $form_id, $data, array(), $extra );
        $grant = SUPER_Common::current_entry_update_grant_value();
        $this->assertTrue( is_array( $grant ) );
        SUPER_Common::setClientData( array(
            'name' => $listing
                ? 'update_contact_entry_' . $form_id . '_' . $form_id . '_0_' . $entry_id
                : 'update_contact_entry_' . $form_id . '_' . $entry_id,
            'value' => $grant,
            'force' => true,
        ) );
        return $this->run_submission();
    }

    private function run_submission() {
        $notice_log = $this->capture_file();
        $result = $this->run_dying_handler( static function() use ( $notice_log ) {
            // The fork inherits the parent's mt_rand state. Upload folders are named with rand()
            // (SUPER_Common::generate_random_folder), so without a reseed the parent would later
            // draw the same folder name the child created and hit the collision branch, which
            // drops its recursive result (reported as a product bug).
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
        foreach( ( $notices===false ? array() : $notices ) as $notice ) {
            $tolerated = false;
            foreach( self::$tolerated_notices as $pattern ) {
                if( preg_match( $pattern, $notice ) ) $tolerated = true;
            }
            $this->assertTrue( $tolerated, 'Unexpected PHP notice during submit_form(): ' . $notice );
        }
        $this->assertTrue( $result['exited'] );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertTrue( is_array( $decoded ), $result['output'] );
        return $decoded;
    }

    private function assert_success( $decoded ) {
        $this->assertFalse( $decoded['error'], wp_json_encode( $decoded ) );
    }

    private function assert_error( $decoded, $message ) {
        $this->assertTrue( $decoded['error'], wp_json_encode( $decoded ) );
        $this->assertStringContainsString( $message, wp_strip_all_tags( $decoded['msg'] ) );
    }

    private function assert_kept_attachment_untouched( $fx, $original_entry_data ) {
        $this->assertSame( 'attachment', get_post_type( $fx['attachment'] ) );
        $this->assertSame( $fx['entry_id'], wp_get_post_parent_id( $fx['attachment'] ) );
        $this->assertFileExists( $fx['file'] );
        $this->assertSame( $original_entry_data, SUPER_Data_Access::get_entry_data( $fx['entry_id'] ) );
    }

    private function saved_attachment_ids( $saved ) {
        $this->assertTrue( is_array( $saved ) && isset( $saved['documents']['files'] ), wp_json_encode( $saved ) );
        $ids = array();
        foreach( $saved['documents']['files'] as $file ) {
            $ids[] = isset( $file['attachment'] ) ? absint( $file['attachment'] ) : 0;
        }
        return $ids;
    }

    private function capture_file() {
        $capture = tempnam( sys_get_temp_dir(), 'sf-cleanup-capture-' );
        $this->assertNotFalse( $capture );
        $this->capture_files[] = $capture;
        return $capture;
    }

    private function read_capture( $capture ) {
        $rows = array();
        $lines = file( $capture, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        foreach( ( $lines===false ? array() : $lines ) as $line ) {
            $row = json_decode( $line, true );
            $this->assertTrue( is_array( $row ), $line );
            $rows[] = $row;
        }
        return $rows;
    }

    private function valid_png_bytes() {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
    }
}
