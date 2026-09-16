<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

/**
 * Matrix row 1 - retained file lifecycle through the real Listings public-edit
 * grant, where the entry's persisted field key ("stored field name") is bound
 * exactly, including a repeater-suffixed carrier that must resolve through the
 * same stored field name, and negatives for a mismatched target form and a
 * mismatched actor.
 */
class Test_Super_Forms_Proof_Row1_Retained_Listings_Lifecycle extends Super_Forms_Upload_Security_Test_Case {
    private $created_user_sessions = array();
    private $original_logged_in_cookie_exists = false;
    private $original_logged_in_cookie_value = null;

    public function set_up() {
        parent::set_up();
        $this->original_logged_in_cookie_exists = array_key_exists( LOGGED_IN_COOKIE, $_COOKIE );
        $this->original_logged_in_cookie_value = $this->original_logged_in_cookie_exists ? $_COOKIE[LOGGED_IN_COOKIE] : null;
    }

    public function tear_down() {
        foreach( $this->created_user_sessions as $session ) {
            WP_Session_Tokens::get_instance( $session[0] )->destroy( $session[1] );
        }
        if( $this->original_logged_in_cookie_exists ) {
            $_COOKIE[LOGGED_IN_COOKIE] = $this->original_logged_in_cookie_value;
        }else{
            unset( $_COOKIE[LOGGED_IN_COOKIE] );
        }
        wp_set_current_user( 0 );
        parent::tear_down();
    }


    private function authenticate_actor( $user_id ) {
        $expiration = time() + HOUR_IN_SECONDS;
        $session_token = WP_Session_Tokens::get_instance($user_id)->create($expiration);
        $cookie = wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $session_token);
        $_COOKIE[LOGGED_IN_COOKIE] = $cookie;
        wp_set_current_user( $user_id );
        // A real logged-in browser also presents the Super Forms session cookie a
        // previous response persisted; the CLI SAPI can never publish one
        // (headers_sent() is permanently true, includes/class-common.php:610-617), and
        // without it no render grant can be stored at all. The third record keeps the
        // row alive across `value => false` writes (includes/class-common.php:649-657).
        // Switching actors must not rotate the browser session: the negatives below
        // isolate the actor, so an already-presented session is kept as-is.
        if( !isset( $_COOKIE['_sfs_id'] ) || !is_string( $_COOKIE['_sfs_id'] ) || $_COOKIE['_sfs_id']==='' ) {
            $session_id = bin2hex( random_bytes( 32 ) );
            $now = time();
            update_option( '_sfsdata_' . $session_id, array(
                'expires' => $now + HOUR_IN_SECONDS,
                'exp_var' => $now + ( 20 * MINUTE_IN_SECONDS ),
                'session_marker' => array(
                    'expires' => $now + HOUR_IN_SECONDS,
                    'exp_var' => $now + ( 20 * MINUTE_IN_SECONDS ),
                    'value' => 'seeded-session-marker',
                ),
            ), 'no' );
            $_COOKIE['_sfs_id'] = $session_id;
            $this->assertSame( $session_id, SUPER_Common::startClientSession( array( 'force' => true ) ) );
        }
        $this->created_user_sessions[] = array( $user_id, $session_token );
        return array( 'token' => $session_token, 'cookie' => $cookie );
    }

    private function render_listing_modal( $post ) {
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

    private function valid_png_bytes() {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
    }

    /**
     * Build a proof-backed custom-root retained file record exactly as the
     * server would have originally produced it, so the fixture is a faithful
     * "already stored" prior state rather than an invented shape.
     */
    private function build_retained_record( $form_id, $root, $file_upload_dir_setting, $field_name, $route_name=null ) {
        $slot = str_pad( (string) wp_rand( 1, 9999999999999 ), 13, '0', STR_PAD_LEFT );
        $directory = trailingslashit( $root ) . $slot;
        $this->assertTrue( wp_mkdir_p( $directory ) );
        $filename = trailingslashit( $directory ) . 'retained.png';
        $this->assertNotFalse( file_put_contents( $filename, $this->valid_png_bytes() ) );
        $subdir = '/' . $file_upload_dir_setting . '/' . $slot . '/retained.png';
        $url = trailingslashit( get_option( 'siteurl' ) ) . 'sfgtfi/' . ltrim( str_replace( '../', '__/', $subdir ), '/' );
        $owned = $this->invoke_ajax_private( 'build_owned_upload', array(
            $form_id, $field_name, $filename, 'image/png', $url, 0, $root, filesize( $filename ), $subdir,
        ) );
        $this->assertTrue( is_array( $owned ) );
        $stored = $this->invoke_ajax_private( 'owned_upload_file_record', array( $owned, $route_name ) );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $stored['_super_file_proof'] );
        return array( 'stored' => $stored, 'filename' => $filename );
    }

    public function test_listing_grant_authorizes_retained_resubmission_under_the_stored_field_name_with_cleanup_authority_while_wrong_form_and_wrong_actor_are_rejected() {
        $admin_1 = self::factory()->user->create( array( 'role' => 'administrator' ) );
        $admin_2 = self::factory()->user->create( array( 'role' => 'administrator' ) );

        list( $parent, $root ) = $this->create_temporary_root( true );
        $file_upload_dir_setting = '../' . basename( $parent ) . '/' . basename( $root );
        // Real saved elements always carry their builder `group` (see the exported
        // form JSON in includes/admin/views/page-demos.php:139); the render loop at
        // includes/class-shortcodes.php:6356 reads it directly.
        $elements = array( array_merge(
            $this->file_element( 'documents_archive', array( 'extensions' => 'png' ) ),
            array( 'group' => 'form_elements' )
        ) );
        $target_settings = array(
            'file_upload_dir' => $file_upload_dir_setting,
            'send' => 'no',
            'confirm' => 'no',
            'save_contact_entry' => 'no',
            'form_thanks_title' => '',
            'form_thanks_description' => '',
            'form_show_thanks_msg' => '',
            'form_redirect_option' => '',
        );
        $target_form_id = $this->create_form( 'publish', $elements, $target_settings );

        $list = array(
            'enabled' => 'true',
            'retrieve' => 'all_forms',
            'edit_any' => array( 'enabled' => 'true', 'user_roles' => 'administrator', 'user_ids' => '' ),
            'edit_own' => array( 'enabled' => 'false', 'user_roles' => '', 'user_ids' => '' ),
        );
        $host_form_id = $this->create_form(
            'publish',
            array(),
            array( '_listings' => array( 'lists' => array( $list ) ) )
        );

        $entry_id = self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'super_unread',
            'post_parent' => $target_form_id,
            'post_author' => $admin_1,
        ) );

        $built = $this->build_retained_record( $target_form_id, $root, $file_upload_dir_setting, 'documents_archive' );
        $stored = $built['stored'];
        $filename = $built['filename'];
        $original_bytes = file_get_contents( $filename );
        update_post_meta(
            $entry_id,
            '_super_contact_entry_data',
            array(
                'documents_archive' => array(
                    'type' => 'files',
                    'files' => array( $stored ),
                ),
            )
        );

        $authorized_auth = $this->authenticate_actor( $admin_1 );
        $grant = 'update_contact_entry_' . $target_form_id . '_' . $host_form_id . '_0_' . $entry_id;
        $this->assertFalse( SUPER_Common::getClientData( $grant ) );
        $modal_output = $this->render_listing_modal( array(
            'action' => 'super_listings_edit_entry',
            'entry_id' => $entry_id,
            'form_id' => $host_form_id,
            'list_id' => 0,
            'nonce' => wp_create_nonce( 'super_listings_entry_' . $host_form_id . '_0' ),
        ) );
        $this->assertRenderedInputValue( $modal_output, 'hidden_form_id', $target_form_id );
        $this->assertRenderedInputValue( $modal_output, 'hidden_contact_entry_id', $entry_id );
        $this->assertSame( SUPER_Common::current_entry_update_grant_value(), SUPER_Common::getClientData( $grant ) );

        // From here on the CLI SAPI cannot satisfy verifyCSRF(): filter_input(INPUT_POST)
        // is never populated outside a real HTTP request (recorded in
        // tests/UNVERIFIABLE-http-headers.md). Disable only that gate -- the listing
        // grant check the submit path performs (includes/class-ajax.php:5292-5338)
        // never consults the sessionless-mode flag, so every boundary below still runs.
        $this->configure_csrf( 'false' );

        $data = array(
            'documents_archive' => array(
                'type' => 'files',
                'files' => array( array(
                    'value' => $stored['value'],
                    'url' => $stored['url'],
                    'retention_token' => 'entry',
                ) ),
            ),
            'hidden_list_id' => array(
                'name' => 'hidden_list_id',
                'value' => '0',
                'type' => 'var',
            ),
        );

        // Negative: the wrong target form is rejected before any file effect,
        // regardless of the valid grant for the real target form.
        $wrong_form_id = $this->create_form( 'publish' );
        $this->set_submit_request(
            $wrong_form_id,
            $data,
            array( 'entry_id' => (string) $entry_id, 'list_id' => '0', 'listing_form_id' => (string) $host_form_id )
        );
        $wrong_form = $this->assert_handler_rejected_with(
            array( 'SUPER_Ajax', 'submit_form' ),
            'permission to edit this entry'
        );
        $this->assertTrue( $wrong_form['error'] );
        $this->assertSame( $stored, SUPER_Data_Access::get_entry_data( $entry_id )['documents_archive']['files'][0] );
        $this->assertFileExists( $filename );
        $this->assertSame( $original_bytes, file_get_contents( $filename ) );

        // Negative: a different authenticated actor without their own grant is
        // rejected on the correct target form.
        $this->authenticate_actor( $admin_2 );
        $this->set_submit_request(
            $target_form_id,
            $data,
            array( 'entry_id' => (string) $entry_id, 'list_id' => '0', 'listing_form_id' => (string) $host_form_id )
        );
        $wrong_actor = $this->assert_handler_rejected_with(
            array( 'SUPER_Ajax', 'submit_form' ),
            'permission to edit this entry'
        );
        $this->assertTrue( $wrong_actor['error'] );
        $this->assertSame( $stored, SUPER_Data_Access::get_entry_data( $entry_id )['documents_archive']['files'][0] );
        $this->assertFileExists( $filename );
        $this->assertSame( $original_bytes, file_get_contents( $filename ) );

        // Positive: the authorized actor's grant lets the resubmission survive
        // under the exact stored field name.
        wp_set_current_user( $admin_1 );
        $_COOKIE[LOGGED_IN_COOKIE] = $authorized_auth['cookie'];
        $this->set_submit_request(
            $target_form_id,
            $data,
            array( 'entry_id' => (string) $entry_id, 'list_id' => '0', 'listing_form_id' => (string) $host_form_id )
        );
        $submit = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $submit['status'], $submit['output'] );
        $decoded = json_decode( $submit['output'], true );
        $this->assertIsArray( $decoded, $submit['output'] );
        $this->assertFalse( $decoded['error'], isset($decoded['msg']) ? wp_strip_all_tags($decoded['msg']) : '' );

        $after_first = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( 'documents_archive', $after_first['documents_archive']['files'][0]['name'] );
        $this->assertSame( $stored['value'], $after_first['documents_archive']['files'][0]['value'] );
        $this->assertSame( $stored['_super_file_proof'], $after_first['documents_archive']['files'][0]['_super_file_proof'] );
        $this->assertFileExists( $filename );
        $this->assertSame( $original_bytes, file_get_contents( $filename ) );

        // Prove cleanup authority for real: enabling submission-delete on the
        // form and resubmitting the SAME retained token now deletes the exact
        // owned file, which only happens when the resolved record carried
        // cleanup authority.
        $target_settings['file_upload_submission_delete'] = 'true';
        update_post_meta( $target_form_id, '_super_form_settings', $target_settings );
        $this->set_submit_request(
            $target_form_id,
            $data,
            array( 'entry_id' => (string) $entry_id, 'list_id' => '0', 'listing_form_id' => (string) $host_form_id )
        );
        $second_submit = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $second_submit['status'], $second_submit['output'] );
        $second_decoded = json_decode( $second_submit['output'], true );
        $this->assertIsArray( $second_decoded, $second_submit['output'] );
        $this->assertFalse( $second_decoded['error'], isset($second_decoded['msg']) ? wp_strip_all_tags($second_decoded['msg']) : '' );
        $this->assertFileDoesNotExist( $filename, 'Cleanup authority must let the exact owned file be deleted.' );
    }

    public function test_repeater_suffixed_carrier_resolves_the_retained_file_through_the_stored_field_name() {
        list( $parent, $root ) = $this->create_temporary_root( true );
        $file_upload_dir_setting = '../' . basename( $parent ) . '/' . basename( $root );
        $elements = array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    $this->file_element( 'documents_archive', array( 'extensions' => 'png' ) ),
                ),
            ),
        );
        $settings = array( 'file_upload_dir' => $file_upload_dir_setting );
        $form_id = $this->create_form( 'publish', $elements, $settings );
        $entry_id = self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'super_unread',
            'post_parent' => $form_id,
        ) );

        // The stored proof/record is built under the real (base) stored field
        // name "documents_archive", but its exposed "name" is the repeater
        // route "documents_archive_2", exactly as production would persist a
        // second repeated row.
        $built = $this->build_retained_record( $form_id, $root, $file_upload_dir_setting, 'documents_archive', 'documents_archive_2' );
        $stored = $built['stored'];
        $filename = $built['filename'];
        $this->assertSame( 'documents_archive_2', $stored['name'] );

        update_post_meta(
            $entry_id,
            '_super_contact_entry_data',
            array(
                'documents_archive_2' => array(
                    'type' => 'files',
                    'files' => array( $stored ),
                ),
            )
        );

        $data = array(
            'documents_archive_2' => array(
                'field_name' => 'documents_archive',
                'type' => 'files',
                'files' => array( array(
                    'value' => $stored['value'],
                    'url' => $stored['url'],
                    'retention_token' => 'entry',
                ) ),
            ),
        );

        $resolved = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $data, $form_id, $elements, $entry_id )
        );
        $this->assertTrue( is_array( $resolved ), is_wp_error($resolved) ? $resolved->get_error_message() : '' );
        $this->assertCount( 1, $resolved['retained_owned_files'] );
        $resolved_record = $resolved['data']['documents_archive_2']['files'][0];
        $this->assertSame( 'retained', $resolved_record['_super_file_authority'] );
        $this->assertSame( 'documents_archive_2', $resolved_record['name'] );
        $this->assertSame( $stored['_super_file_proof'], $resolved_record['_super_file_proof'] );
        $this->assertSame( 'documents_archive', $resolved['data']['documents_archive_2']['field_name'] );

        update_post_meta( $entry_id, '_super_contact_entry_data', $resolved['data'] );
        $persisted = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( $stored['_super_file_proof'], $persisted['documents_archive_2']['files'][0]['_super_file_proof'] );
        $this->assertFileExists( $filename );

        // Negative: a mismatched carrier that does not carry the repeater
        // suffix identity ("documents_archive" plainly, claiming the row-2
        // record) cannot adopt a record actually persisted at the suffixed key.
        $mismatched_data = array(
            'documents_archive' => array(
                'field_name' => 'documents_archive',
                'type' => 'files',
                'files' => array( array(
                    'value' => $stored['value'],
                    'url' => $stored['url'],
                    'retention_token' => 'entry',
                ) ),
            ),
        );
        $mismatched = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $mismatched_data, $form_id, $elements, $entry_id )
        );
        $this->assertInstanceOf( 'WP_Error', $mismatched );
        $this->assertSame( $persisted, SUPER_Data_Access::get_entry_data( $entry_id ) );
        $this->assertFileExists( $filename );
    }
}

/**
 * Matrix row 9 - generated PDF materialization followed by the
 * {_generated_pdf_file_name} / {_generated_pdf_file_url} email tag
 * resolution, plus the negatives that must leave zero file/receipt/option
 * effects.
 */
class Test_Super_Forms_Proof_Row9_Generated_Pdf_Email_Tags extends Super_Forms_Upload_Security_Test_Case {

    private function generated_pdf_data( $data_uri ) {
        return array(
            '_generated_pdf_file' => array(
                'type' => 'files',
                'files' => array( array(
                    'label' => 'Generated PDF',
                    'name' => 'invoice.pdf',
                    'value' => 'invoice.pdf',
                    'datauristring' => $data_uri,
                ) ),
            ),
        );
    }

    private function valid_pdf_bytes() {
        return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF\n";
    }

    private function attachment_inventory() {
        $attachments = get_posts( array(
            'post_type' => 'attachment',
            'post_status' => 'any',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'orderby' => 'ID',
            'order' => 'ASC',
        ) );
        return array_map( 'intval', $attachments );
    }

    private function upload_root_inventory() {
        $upload_dir = wp_upload_dir();
        $root = isset( $upload_dir['basedir'] ) ? $upload_dir['basedir'] : '';
        $files = array();
        if( !is_string($root) || $root==='' || !is_dir($root) ) {
            return $files;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS )
        );
        foreach( $iterator as $file ) {
            if( !$file->isFile() || $file->isLink() ) {
                continue;
            }
            $path = wp_normalize_path( $file->getPathname() );
            $files[$path] = hash_file( 'sha256', $path );
        }
        ksort( $files );
        return $files;
    }

    private function receipt_artifact_inventory() {
        global $wpdb;
        return $wpdb->get_col(
            "SELECT option_name FROM $wpdb->options
            WHERE option_name LIKE '\\_super\\_upload\\_receipt\\_%'
            OR option_name LIKE '\\_super\\_upload\\_receipt\\_claim\\_%'
            ORDER BY option_name ASC"
        );
    }

    private function assert_generated_pdf_rejection_has_no_artifacts( $callback, $message ) {
        $before_attachments = $this->attachment_inventory();
        $before_receipts = $this->receipt_artifact_inventory();
        $before_files = $this->upload_root_inventory();
        $marker = tempnam( sys_get_temp_dir(), 'sf-row9-marker-' );
        $this->assertNotFalse( $marker );
        @unlink( $marker );
        $data_filter = static function( $data ) use ( $marker ) {
            $observed = wp_json_encode( $data );
            if( strpos( $observed, '_generated_pdf_file' )!==false || strpos( $observed, 'datauristring' )!==false ) {
                file_put_contents( $marker, 'data-filter', FILE_APPEND );
            }
            return $data;
        };
        $settings_filter = static function( $settings, $atts ) use ( $marker ) {
            $observed = wp_json_encode( $atts );
            if( strpos( $observed, '_generated_pdf_file' )!==false || strpos( $observed, 'datauristring' )!==false ) {
                file_put_contents( $marker, 'settings-filter', FILE_APPEND );
            }
            return $settings;
        };
        $action = static function( $atts ) use ( $marker ) {
            $observed = wp_json_encode( $atts );
            if( strpos( $observed, '_generated_pdf_file' )!==false || strpos( $observed, 'datauristring' )!==false ) {
                file_put_contents( $marker, 'action', FILE_APPEND );
            }
        };
        add_filter( 'super_before_submit_form_settings_filter', $settings_filter, 10, 2 );
        add_filter( 'super_before_sending_email_data_filter', $data_filter, 10, 2 );
        add_action( 'super_before_sending_email_hook', $action, 10, 1 );
        try {
            $this->assert_handler_rejected_with( $callback, $message );
        } finally {
            remove_filter( 'super_before_submit_form_settings_filter', $settings_filter, 10 );
            remove_filter( 'super_before_sending_email_data_filter', $data_filter, 10 );
            remove_action( 'super_before_sending_email_hook', $action, 10 );
        }
        $this->assertSame( $before_attachments, $this->attachment_inventory() );
        $this->assertSame( $before_receipts, $this->receipt_artifact_inventory() );
        $this->assertSame( $before_files, $this->upload_root_inventory() );
        $this->assertFileDoesNotExist( $marker, 'Rejected generated PDF data must not reach downstream hooks.' );
    }

    public function test_generated_pdf_name_and_url_tags_resolve_to_a_safe_basename_and_url_with_no_path_segment_or_data_uri() {
        $settings = array(
            '_pdf' => array( 'generate' => 'true', 'filename' => 'stored-invoice.pdf', 'emailLabel' => 'Stored PDF' ),
        );
        $form_id = $this->create_form( 'publish', array(), $settings );
        $data = $this->generated_pdf_data( 'data:application/pdf;base64,' . base64_encode( $this->valid_pdf_bytes() ) );
        $this->set_request( $form_id, $data );

        $atts = SUPER_Ajax::submit_form_checks( $settings, false );
        $this->assertCount( 1, $atts['owned_files'] );
        foreach( $atts['owned_files'] as $owned ) {
            $this->track_owned_cleanup( $owned );
        }
        $file_record = $atts['data']['_generated_pdf_file']['files'][0];
        $this->assertArrayHasKey( 'attachment', $file_record );
        $this->attachment_ids[] = $file_record['attachment'];

        $rendered = SUPER_Common::email_tags(
            '{_generated_pdf_file_name}|{_generated_pdf_file_url}',
            $atts['data'],
            $settings
        );
        $parts = explode( '|', $rendered, 2 );
        $this->assertCount( 2, $parts );
        list( $name, $url ) = $parts;

        $this->assertNotSame( '', $name );
        $this->assertSame( basename( $name ), $name, 'The name tag must be a plain basename.' );
        $this->assertStringNotContainsString( '/', $name );
        $this->assertStringNotContainsString( '..', $name );
        $this->assertSame( 'pdf', strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) );

        $this->assertNotSame( '', $url );
        $this->assertMatchesRegularExpression( '#^https?://#', $url );
        $this->assertStringNotContainsString( 'data:', strtolower( $url ) );
        $this->assertStringNotContainsString( 'base64', strtolower( $url ) );
        $this->assertStringNotContainsString( '..', $url );
        $this->assertSame( $name, basename( wp_parse_url( $url, PHP_URL_PATH ) ) );
    }

    public function test_oversize_decoded_bound_produces_zero_file_receipt_and_option_effects() {
        $settings = array( '_pdf' => array( 'generate' => 'true', 'filesize' => '0.000001' ) );
        $form_id = $this->create_form( 'publish', array(), $settings );
        $data = $this->generated_pdf_data( 'data:application/pdf;base64,' . base64_encode( $this->valid_pdf_bytes() ) );
        $this->set_request( $form_id, $data );

        $callback = static function() use ( $settings ) {
            SUPER_Ajax::submit_form_checks( $settings, false );
        };
        $this->assert_generated_pdf_rejection_has_no_artifacts( $callback, 'Invalid file upload rejected.' );
    }

    public function test_invalid_base64_produces_zero_file_receipt_and_option_effects() {
        $settings = array( '_pdf' => array( 'generate' => 'true' ) );
        $form_id = $this->create_form( 'publish', array(), $settings );
        $data = $this->generated_pdf_data( 'data:application/pdf;base64,' . 'not-valid-base64!!!///***' );
        $this->set_request( $form_id, $data );

        $callback = static function() use ( $settings ) {
            SUPER_Ajax::submit_form_checks( $settings, false );
        };
        $this->assert_generated_pdf_rejection_has_no_artifacts( $callback, 'Invalid file upload rejected.' );
    }

    public function test_wrong_magic_bytes_produces_zero_file_receipt_and_option_effects() {
        $settings = array( '_pdf' => array( 'generate' => 'true' ) );
        $form_id = $this->create_form( 'publish', array(), $settings );
        $data = $this->generated_pdf_data(
            'data:application/pdf;base64,' . base64_encode( 'this is definitely not a real pdf document body' )
        );
        $this->set_request( $form_id, $data );

        $callback = static function() use ( $settings ) {
            SUPER_Ajax::submit_form_checks( $settings, false );
        };
        $this->assert_generated_pdf_rejection_has_no_artifacts( $callback, 'Invalid file upload rejected.' );
    }

    public function test_forged_mime_prefix_produces_zero_file_receipt_and_option_effects() {
        $settings = array( '_pdf' => array( 'generate' => 'true' ) );
        $form_id = $this->create_form( 'publish', array(), $settings );
        $data = $this->generated_pdf_data(
            'data:image/png;base64,' . base64_encode( $this->valid_pdf_bytes() )
        );
        $this->set_request( $form_id, $data );

        $callback = static function() use ( $settings ) {
            SUPER_Ajax::submit_form_checks( $settings, false );
        };
        $this->assert_generated_pdf_rejection_has_no_artifacts( $callback, 'Invalid file upload rejected.' );
    }

    public function test_disabled_stored_form_produces_zero_file_receipt_and_option_effects() {
        $settings = array( '_pdf' => array( 'generate' => 'true' ) );
        $form_id = $this->create_form( 'draft', array(), $settings );
        $data = $this->generated_pdf_data( 'data:application/pdf;base64,' . base64_encode( $this->valid_pdf_bytes() ) );
        wp_set_current_user( 0 );
        $this->set_request( $form_id, $data );

        $callback = static function() {
            SUPER_Ajax::submit_form_checks( null, false );
        };
        $this->assert_generated_pdf_rejection_has_no_artifacts( $callback, 'Invalid form.' );
    }
}

/**
 * Matrix row 13 (ownership half) - a retained-file proof minted under a
 * rotated salt reseals without cleanup authority, a second resolution then
 * authorizes it, a tampered reseal still fails, and the same root-change
 * cleanup-authority coverage is repeated for an old root that sits inside
 * ABSPATH (an uploads subdirectory) rather than outside it.
 */
class Test_Super_Forms_Proof_Row13_Ownership_Salt_And_Root extends Super_Forms_Upload_Security_Test_Case {

    private function valid_png_bytes() {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
    }

    /**
     * A retained-upload root inside ABSPATH, under wp-content/uploads,
     * matching how SUPER_FORMS_UPLOAD_DIR itself is derived, but under a
     * private per-test subdirectory so it never collides with the real
     * default upload root.
     */
    private function create_uploads_subdir_root() {
        $relative_uploads = trailingslashit( str_replace( ABSPATH, '', WP_CONTENT_DIR ) ) . 'uploads';
        $slug = 'sf-row13-old-root-' . str_replace( '-', '', wp_generate_uuid4() );
        $parent = trailingslashit( WP_CONTENT_DIR ) . 'uploads/' . $slug;
        $root = trailingslashit( $parent ) . 'owned';
        $this->assertTrue( wp_mkdir_p( $root ) );
        $parent_real = realpath( $parent );
        $root_real = realpath( $root );
        $this->assertNotFalse( $parent_real );
        $this->assertNotFalse( $root_real );
        $this->temporary_parents[] = $parent_real;
        // The configured setting must name the exact directory the files live in
        // (super-forms.php:1145-1152 resolves the stored subdir's own setting back to a
        // root and requires the resolved file to be the stored one).
        $setting = trailingslashit( $relative_uploads ) . $slug . '/owned';
        return array( $root_real, $setting );
    }

    public function test_proof_minted_under_a_rotated_salt_reseals_without_cleanup_authority_then_a_second_resolution_authorizes_while_a_tampered_reseal_still_fails() {
        list( $parent, $root ) = $this->create_temporary_root( true );
        $setting = '../' . basename( $parent ) . '/' . basename( $root );
        $elements = array( $this->file_element( 'documents', array( 'extensions' => 'png' ) ) );
        $settings = array( 'file_upload_dir' => $setting );
        $form_id = $this->create_form( 'publish', $elements, $settings );
        $entry_id = self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'super_unread',
            'post_parent' => $form_id,
        ) );

        $slot = str_pad( (string) wp_rand( 1, 9999999999999 ), 13, '0', STR_PAD_LEFT );
        $directory = trailingslashit( $root ) . $slot;
        $filename = trailingslashit( $directory ) . 'retained.png';
        $this->assertTrue( wp_mkdir_p( $directory ) );
        $this->assertNotFalse( file_put_contents( $filename, $this->valid_png_bytes() ) );
        $subdir = '/' . $setting . '/' . $slot . '/retained.png';
        $url = trailingslashit( get_option( 'siteurl' ) ) . 'sfgtfi/' . ltrim( str_replace( '../', '__/', $subdir ), '/' );

        // Mint the stored proof under a rotated ("old") salt.
        $old_salt_filter = static function( $value, $scheme ) {
            return 'row13-rotated-salt::' . $scheme;
        };
        add_filter( 'salt', $old_salt_filter, 10, 2 );
        $owned = $this->invoke_ajax_private( 'build_owned_upload', array(
            $form_id, 'documents', $filename, 'image/png', $url, 0, $root, filesize( $filename ), $subdir,
        ) );
        $this->assertTrue( is_array( $owned ) );
        $stored = $this->invoke_ajax_private( 'owned_upload_file_record', array( $owned ) );
        remove_filter( 'salt', $old_salt_filter, 10 );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $stored['_super_file_proof'] );

        update_post_meta(
            $entry_id,
            '_super_contact_entry_data',
            array( 'documents' => array( 'type' => 'files', 'files' => array( $stored ) ) )
        );
        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array( array(
                    'value' => $stored['value'],
                    'url' => $stored['url'],
                    'retention_token' => 'entry',
                ) ),
            ),
        );

        // First resolution: the current (rotated) salt no longer produces the
        // same proof, so this reseals the record without cleanup authority.
        $first = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $data, $form_id, $elements, $entry_id )
        );
        $this->assertTrue( is_array( $first ), is_wp_error($first) ? $first->get_error_message() : '' );
        $this->assertSame( array(), $first['retained_owned_files'], 'A proof minted under a rotated salt must not carry cleanup authority on first resolution.' );
        $resealed = $first['data']['documents']['files'][0];
        $this->assertSame( 'retained', $resealed['_super_file_authority'] );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $resealed['_super_file_proof'] );
        $this->assertNotSame( $stored['_super_file_proof'], $resealed['_super_file_proof'], 'Resealing must mint a fresh proof under the current salt.' );
        $this->assertFileExists( $filename );
        update_post_meta( $entry_id, '_super_contact_entry_data', $first['data'] );

        // Second resolution with the same client carrier: the resealed proof
        // now matches the current salt, so cleanup authority is granted.
        $second = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $data, $form_id, $elements, $entry_id )
        );
        $this->assertTrue( is_array( $second ), is_wp_error($second) ? $second->get_error_message() : '' );
        $this->assertCount( 1, $second['retained_owned_files'] );
        $this->assertSame( $resealed['_super_file_proof'], $second['data']['documents']['files'][0]['_super_file_proof'] );
        $this->assertFileExists( $filename );

        // A tampered reseal (proof corrupted to an invalid shape) still fails,
        // leaving the entry data and the file untouched.
        $tampered_entry_data = $first['data'];
        $tampered_entry_data['documents']['files'][0]['_super_file_proof'] = substr( $resealed['_super_file_proof'], 0, 63 ) . 'z';
        update_post_meta( $entry_id, '_super_contact_entry_data', $tampered_entry_data );
        $tampered_resolution = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $data, $form_id, $elements, $entry_id )
        );
        $this->assertInstanceOf( 'WP_Error', $tampered_resolution );
        $this->assertSame( $tampered_entry_data, SUPER_Data_Access::get_entry_data( $entry_id ) );
        $this->assertFileExists( $filename );
    }

    public function test_custom_root_retained_upload_inside_abspath_uploads_subdir_keeps_exact_cleanup_authority_after_the_root_setting_changes() {
        list( $original_root, $original_setting ) = $this->create_uploads_subdir_root();
        $this->assertStringStartsWith(
            untrailingslashit( wp_normalize_path( ABSPATH ) ),
            wp_normalize_path( $original_root ),
            'This coverage requires the old root to sit inside ABSPATH.'
        );
        $elements = array( $this->file_element( 'documents', array( 'extensions' => 'png' ) ) );
        $settings = array( 'file_upload_dir' => $original_setting );
        $form_id = $this->create_form( 'publish', $elements, $settings );
        $entry_id = self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'super_unread',
            'post_parent' => $form_id,
        ) );

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
        $this->assertTrue( is_array( $owned ) );
        $stored = $this->invoke_ajax_private( 'owned_upload_file_record', array( $owned ) );
        update_post_meta(
            $entry_id,
            '_super_contact_entry_data',
            array( 'documents' => array( 'type' => 'files', 'files' => array( $stored ) ) )
        );

        list( $new_parent, $new_root ) = $this->create_temporary_root( true );
        $new_settings = array( 'file_upload_dir' => '../' . basename( $new_parent ) . '/' . basename( $new_root ) );
        update_post_meta( $form_id, '_super_form_settings', $new_settings );

        $data = array(
            'documents' => array(
                'type' => 'files',
                'files' => array( array(
                    'value' => $stored['value'],
                    'url' => $stored['url'],
                    'retention_token' => 'entry',
                ) ),
            ),
        );
        $resolved = $this->invoke_ajax_private(
            'resolve_submission_files',
            array( $data, $form_id, $elements, $entry_id )
        );
        $this->assertTrue( is_array( $resolved ), is_wp_error($resolved) ? $resolved->get_error_message() : '' );
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
}
