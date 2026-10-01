<?php
/**
 * Proof package for security matrix rows 11 and 18.
 *
 * Row 11 covers real save-form rejection/acceptance paths for custom-regex
 * validation plus Unicode-aware length parity on public submit.
 *
 * Row 18 covers byte preservation across public submit, admin edit, and CSV
 * import, plus keyword-count length enforcement.
 *
 * @package Super_Forms\Tests
 */

require_once __DIR__ . '/test-security-upload-00-base.php';

/**
 * Row 11: real save_form admin AJAX + forked public submit_form parity for
 * custom-regex validation and Unicode-aware minlength/maxlength counting.
 */
class Test_Super_Forms_Proof_Row11_Regex_And_Length_Parity extends Super_Forms_Upload_Security_Test_Case {

    private function admin_actor() {
        $user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $user_id );
        return $user_id;
    }

    private function all_form_ids() {
        global $wpdb;
        $ids = $wpdb->get_col(
            $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s ORDER BY ID ASC", 'super_form' )
        );
        return array_map( 'intval', $ids );
    }

    /**
     * Root forms only: an authorized re-save intentionally stores a 'backup'
     * child revision of the form it updated (class-ajax.php:2934-2941), so the
     * "no new form" check for an accepted re-save has to ignore those.
     */
    private function root_form_ids() {
        global $wpdb;
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = 0 AND post_status != 'backup' ORDER BY ID ASC",
                'super_form'
            )
        );
        return array_map( 'intval', $ids );
    }

    private function form_settings_no_side_effects() {
        return array(
            'send' => 'no',
            'confirm' => 'no',
            'save_contact_entry' => 'yes',
            'form_thanks_title' => '',
            'form_thanks_description' => '',
            'form_show_thanks_msg' => '',
            'form_redirect_option' => '',
        );
    }

    /**
     * Create a form whose stored elements keep their backslashes.
     *
     * update_post_meta() unslashes its value, so handing raw '\A' to the
     * shared create_form() helper stores 'A'. save_form() slashes the elements
     * before save_form_meta() (class-ajax.php:2838), so a fixture that stands
     * in for an already-saved form has to slash them the same way.
     */
    private function create_form_with_stored_elements( $elements, $settings=array() ) {
        $form_id = $this->create_form( 'publish', array(), $settings );
        update_post_meta( $form_id, '_super_elements', wp_slash( $elements ) );
        return $form_id;
    }

    /**
     * Build and run the real save_form admin-AJAX request.
     */
    private function save_form_via_admin_ajax( $title, $elements, $settings=array(), $form_id=0 ) {
        $_POST = array(
            'action' => 'super_save_form',
            'form_id' => (int) $form_id,
            'title' => $title,
            'elements' => 'true',
            'settings' => 'true',
            'translations' => 'true',
            'formElements' => wp_slash( wp_json_encode( $elements ) ),
            'formSettings' => wp_slash( wp_json_encode( $settings ) ),
            'translationSettings' => wp_slash( wp_json_encode( array() ) ),
            'localSecrets' => array(),
            'globalSecrets' => array(),
            'nonce' => wp_create_nonce( 'super_save_form' ),
        );
        $_REQUEST = $_POST;
        return $this->run_dying_handler( array( 'SUPER_Ajax', 'save_form' ) );
    }

    /**
     * Build and run the real public submit request.
     */
    private function submit_data_post( $form_id, $data, $extra_post=array() ) {
        if( !isset($data['hidden_form_id']) ) {
            $data['hidden_form_id'] = array(
                'name' => 'hidden_form_id',
                'value' => (string) $form_id,
                'type' => 'form_id',
            );
        }
        if( !isset($data['hidden_contact_entry_id']) ) {
            $data['hidden_contact_entry_id'] = array(
                'name' => 'hidden_contact_entry_id',
                'value' => '',
                'type' => 'entry_id',
            );
        }
        $_POST = array_merge(
            array(
                'action' => 'super_submit_form',
                'i18n' => '',
                'form_id' => (string) $form_id,
                'data' => wp_slash( wp_json_encode( $data ) ),
            ),
            $extra_post
        );
        $_REQUEST = $_POST;
        $_FILES = array();
        return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
    }

    /**
     * Row 11: malformed custom regex is rejected at save time.
     */
    public function test_malformed_custom_regex_bracket_fails_save_with_decoded_json_error() {
        $this->admin_actor();
        $before_ids = $this->all_form_ids();
        $elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'nickname',
                    'validation' => 'custom',
                    'custom_regex' => '[',
                ),
            ),
        );
        $result = $this->save_form_via_admin_ajax(
            'Row11 malformed regex ' . wp_generate_uuid4(),
            $elements,
            $this->form_settings_no_side_effects()
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertTrue( $decoded['error'], $result['output'] );
        $this->assertStringContainsString(
            'JavaScript Unicode regular expressions',
            wp_strip_all_tags( $decoded['msg'] )
        );
        $this->assertSame( $before_ids, $this->all_form_ids(), 'Malformed regex must not persist a new form.' );
    }

    /**
     * Row 11: a saved custom regex is enforced on public submit.
     */
    public function test_slash_and_backslash_regex_saves_and_enforces_the_right_values_on_public_submit() {
        $this->admin_actor();
        $elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'pattern_field',
                    'validation' => 'custom',
                    'custom_regex' => '^\d{2}/\d{2}$',
                ),
            ),
        );
        $save = $this->save_form_via_admin_ajax(
            'Row11 slash backslash regex ' . wp_generate_uuid4(),
            $elements,
            $this->form_settings_no_side_effects()
        );
        $this->assertSame( 0, $save['status'], $save['output'] );
        $form_id = (int) trim( $save['output'] );
        $this->assertGreaterThan( 0, $form_id, $save['output'] );

        $stored_elements = get_post_meta( $form_id, '_super_elements', true );
        $this->assertIsArray( $stored_elements );
        $this->assertSame( '^\d{2}/\d{2}$', $stored_elements[0]['data']['custom_regex'] );

        $this->configure_csrf( 'false' );
        wp_set_current_user( 0 );

        $accept = $this->submit_data_post( $form_id, array(
            'pattern_field' => array( 'name' => 'pattern_field', 'value' => '42/07', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $accept['status'], $accept['output'] );
        $accept_decoded = json_decode( $accept['output'], true );
        $this->assertIsArray( $accept_decoded, $accept['output'] );
        $this->assertFalse( $accept_decoded['error'], $accept['output'] );
        $entry_id = (int) $accept_decoded['response_data']['contact_entry_id'];
        $this->assertGreaterThan( 0, $entry_id );
        $stored_entry = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( '42/07', $stored_entry['pattern_field']['value'] );

        $reject = $this->submit_data_post( $form_id, array(
            'pattern_field' => array( 'name' => 'pattern_field', 'value' => '42-07', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $reject['status'], $reject['output'] );
        $reject_decoded = json_decode( $reject['output'], true );
        $this->assertIsArray( $reject_decoded, $reject['output'] );
        $this->assertTrue( $reject_decoded['error'], $reject['output'] );
        $this->assertStringContainsString(
            'Invalid form data.',
            wp_strip_all_tags( $reject_decoded['msg'] )
        );
    }

    /**
     * Row 11: an empty custom regex stays a no-op on public submit.
     */
    public function test_empty_custom_regex_is_a_noop_on_real_public_submit() {
        $this->admin_actor();
        $elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'freeform_field',
                    'validation' => 'custom',
                    'custom_regex' => '',
                ),
            ),
        );
        $save = $this->save_form_via_admin_ajax(
            'Row11 empty regex ' . wp_generate_uuid4(),
            $elements,
            $this->form_settings_no_side_effects()
        );
        $this->assertSame( 0, $save['status'], $save['output'] );
        $form_id = (int) trim( $save['output'] );
        $this->assertGreaterThan( 0, $form_id, $save['output'] );
        $this->assertSame( '', get_post_meta( $form_id, '_super_elements', true )[0]['data']['custom_regex'] );

        $this->configure_csrf( 'false' );
        wp_set_current_user( 0 );
        $value = 'Anything goes here! 123 @#&*()';
        $submit = $this->submit_data_post( $form_id, array(
            'freeform_field' => array( 'name' => 'freeform_field', 'value' => $value, 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $submit['status'], $submit['output'] );
        $decoded = json_decode( $submit['output'], true );
        $this->assertIsArray( $decoded, $submit['output'] );
        $this->assertFalse( $decoded['error'], $submit['output'] );
        $entry_id = (int) $decoded['response_data']['contact_entry_id'];
        $stored = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( $value, $stored['freeform_field']['value'] );
    }

    /**
     * Row 11: a regex without JavaScript parity is rejected at save time.
     */
    public function test_pcre_only_backslash_a_construct_is_rejected_at_save_with_js_u_parity_error() {
        $this->admin_actor();
        $before_ids = $this->all_form_ids();
        $elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'anchor_field',
                    'validation' => 'custom',
                    'custom_regex' => '\A',
                ),
            ),
        );
        $result = $this->save_form_via_admin_ajax(
            'Row11 pcre only anchor ' . wp_generate_uuid4(),
            $elements,
            $this->form_settings_no_side_effects()
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertTrue( $decoded['error'], $result['output'] );
        $this->assertStringContainsString(
            'JavaScript Unicode regular expressions',
            wp_strip_all_tags( $decoded['msg'] )
        );
        $this->assertSame( $before_ids, $this->all_form_ids(), 'PCRE-only regex must not persist a new form.' );
    }
    public function test_legacy_js_incompatible_custom_regex_can_be_resaved_unchanged() {
        $this->admin_actor();
        $elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'legacy_anchor_field',
                    'validation' => 'custom',
                    'custom_regex' => '\A',
                ),
            ),
        );
        $form_id = $this->create_form_with_stored_elements( $elements, $this->form_settings_no_side_effects() );
        $before_ids = $this->root_form_ids();
        $result = $this->save_form_via_admin_ajax(
            'Row11 legacy regex resave ' . wp_generate_uuid4(),
            $elements,
            $this->form_settings_no_side_effects(),
            $form_id
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $this->assertSame( (string) $form_id, trim( $result['output'] ) );
        $this->assertSame( $before_ids, $this->root_form_ids(), 'Resaving an existing form must not create a new form.' );
        $stored_elements = get_post_meta( $form_id, '_super_elements', true );
        $this->assertIsArray( $stored_elements );
        $this->assertSame( '\A', $stored_elements[0]['data']['custom_regex'] );
    }

    public function test_legacy_js_incompatible_custom_regex_matches_the_plain_regexp_fallback_on_public_submit() {
        $form_id = $this->create_form_with_stored_elements(
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'legacy_plain_field',
                        'validation' => 'custom',
                        'custom_regex' => '\A',
                    ),
                ),
            ),
            $this->form_settings_no_side_effects()
        );

        $this->configure_csrf( 'false' );
        wp_set_current_user( 0 );

        $accept = $this->submit_data_post( $form_id, array(
            'legacy_plain_field' => array( 'name' => 'legacy_plain_field', 'value' => 'A', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $accept['status'], $accept['output'] );
        $accept_decoded = json_decode( $accept['output'], true );
        $this->assertIsArray( $accept_decoded, $accept['output'] );
        $this->assertFalse( $accept_decoded['error'], $accept['output'] );
        $entry_id = (int) $accept_decoded['response_data']['contact_entry_id'];
        $this->assertGreaterThan( 0, $entry_id );
        $stored_entry = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( 'A', $stored_entry['legacy_plain_field']['value'] );

        $reject = $this->submit_data_post( $form_id, array(
            'legacy_plain_field' => array( 'name' => 'legacy_plain_field', 'value' => 'B', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $reject['status'], $reject['output'] );
        $reject_decoded = json_decode( $reject['output'], true );
        $this->assertIsArray( $reject_decoded, $reject['output'] );
        $this->assertTrue( $reject_decoded['error'], $reject['output'] );
        $this->assertStringContainsString(
            'Invalid form data.',
            wp_strip_all_tags( $reject_decoded['msg'] )
        );
    }
    public function test_legacy_plain_submit_treats_backslash_c_before_a_non_letter_as_a_literal_sequence() {
        $form_id = $this->create_form_with_stored_elements(
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'legacy_backslash_c_field',
                        'validation' => 'custom',
                        'custom_regex' => '\A\c+',
                    ),
                ),
            ),
            $this->form_settings_no_side_effects()
        );

        $this->configure_csrf( 'false' );
        wp_set_current_user( 0 );

        $accept = $this->submit_data_post( $form_id, array(
            'legacy_backslash_c_field' => array( 'name' => 'legacy_backslash_c_field', 'value' => "A\\c", 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $accept['status'], $accept['output'] );
        $accept_decoded = json_decode( $accept['output'], true );
        $this->assertIsArray( $accept_decoded, $accept['output'] );
        $this->assertFalse( $accept_decoded['error'], $accept['output'] );

        $reject = $this->submit_data_post( $form_id, array(
            'legacy_backslash_c_field' => array( 'name' => 'legacy_backslash_c_field', 'value' => 'Ac', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $reject['status'], $reject['output'] );
        $reject_decoded = json_decode( $reject['output'], true );
        $this->assertIsArray( $reject_decoded, $reject['output'] );
        $this->assertTrue( $reject_decoded['error'], $reject['output'] );
        $this->assertStringContainsString( 'Invalid form data.', wp_strip_all_tags( $reject_decoded['msg'] ) );
    }

    public function test_legacy_plain_submit_keeps_annex_b_braced_u_escape_quantifier_semantics() {
        $form_id = $this->create_form_with_stored_elements(
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'legacy_braced_u_field',
                        'validation' => 'custom',
                        'custom_regex' => '\A\u{2}',
                    ),
                ),
            ),
            $this->form_settings_no_side_effects()
        );

        $this->configure_csrf( 'false' );
        wp_set_current_user( 0 );

        $accept = $this->submit_data_post( $form_id, array(
            'legacy_braced_u_field' => array( 'name' => 'legacy_braced_u_field', 'value' => 'Auu', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $accept['status'], $accept['output'] );
        $accept_decoded = json_decode( $accept['output'], true );
        $this->assertIsArray( $accept_decoded, $accept['output'] );
        $this->assertFalse( $accept_decoded['error'], $accept['output'] );

        $reject = $this->submit_data_post( $form_id, array(
            'legacy_braced_u_field' => array( 'name' => 'legacy_braced_u_field', 'value' => 'Au{2}', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $reject['status'], $reject['output'] );
        $reject_decoded = json_decode( $reject['output'], true );
        $this->assertIsArray( $reject_decoded, $reject['output'] );
        $this->assertTrue( $reject_decoded['error'], $reject['output'] );
        $this->assertStringContainsString( 'Invalid form data.', wp_strip_all_tags( $reject_decoded['msg'] ) );
    }

    public function test_legacy_plain_submit_keeps_annex_b_property_escape_literal_semantics() {
        $form_id = $this->create_form_with_stored_elements(
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'legacy_property_escape_field',
                        'validation' => 'custom',
                        'custom_regex' => '\A\p{L}',
                    ),
                ),
            ),
            $this->form_settings_no_side_effects()
        );

        $this->configure_csrf( 'false' );
        wp_set_current_user( 0 );

        $accept = $this->submit_data_post( $form_id, array(
            'legacy_property_escape_field' => array( 'name' => 'legacy_property_escape_field', 'value' => 'Ap{L}', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $accept['status'], $accept['output'] );
        $accept_decoded = json_decode( $accept['output'], true );
        $this->assertIsArray( $accept_decoded, $accept['output'] );
        $this->assertFalse( $accept_decoded['error'], $accept['output'] );

        $reject = $this->submit_data_post( $form_id, array(
            'legacy_property_escape_field' => array( 'name' => 'legacy_property_escape_field', 'value' => 'Ap', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $reject['status'], $reject['output'] );
        $reject_decoded = json_decode( $reject['output'], true );
        $this->assertIsArray( $reject_decoded, $reject['output'] );
        $this->assertTrue( $reject_decoded['error'], $reject['output'] );
        $this->assertStringContainsString( 'Invalid form data.', wp_strip_all_tags( $reject_decoded['msg'] ) );
    }
    public function test_legacy_js_incompatible_custom_regex_cannot_move_to_a_different_field_name() {
        $this->admin_actor();
        $stored_elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'legacy_anchor_field',
                    'validation' => 'custom',
                    'custom_regex' => '\A',
                ),
            ),
        );
        $submitted_elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'moved_anchor_field',
                    'validation' => 'custom',
                    'custom_regex' => '\A',
                ),
            ),
        );
        $form_id = $this->create_form_with_stored_elements( $stored_elements, $this->form_settings_no_side_effects() );
        $result = $this->save_form_via_admin_ajax(
            'Row11 legacy regex moved field ' . wp_generate_uuid4(),
            $submitted_elements,
            $this->form_settings_no_side_effects(),
            $form_id
        );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertTrue( $decoded['error'], $result['output'] );
        $this->assertStringContainsString(
            'JavaScript Unicode regular expressions',
            wp_strip_all_tags( $decoded['msg'] )
        );
        $stored_after = get_post_meta( $form_id, '_super_elements', true );
        $this->assertIsArray( $stored_after );
        $this->assertSame( 'legacy_anchor_field', $stored_after[0]['data']['name'] );
        $this->assertSame( '\A', $stored_after[0]['data']['custom_regex'] );
    }

    /**
     * Row 11: astral Unicode length checks match browser codepoint counting.
     */
    public function test_astral_unicode_minlength_and_maxlength_match_browser_codepoint_counting() {
        $this->admin_actor();
        $elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'astral_field',
                    'minlength' => '3',
                    'maxlength' => '3',
                ),
            ),
        );
        $save = $this->save_form_via_admin_ajax(
            'Row11 astral parity ' . wp_generate_uuid4(),
            $elements,
            $this->form_settings_no_side_effects()
        );
        $this->assertSame( 0, $save['status'], $save['output'] );
        $form_id = (int) trim( $save['output'] );
        $this->assertGreaterThan( 0, $form_id, $save['output'] );

        // U+1F600 GRINNING FACE: one astral codepoint, a UTF-16 surrogate
        // pair (2 code units) in a browser, 4 UTF-8 bytes on the wire.
        $emoji = "\xF0\x9F\x98\x80";
        $this->configure_csrf( 'false' );
        wp_set_current_user( 0 );

        $accept = $this->submit_data_post( $form_id, array(
            'astral_field' => array( 'name' => 'astral_field', 'value' => str_repeat( $emoji, 3 ), 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $accept['status'], $accept['output'] );
        $accept_decoded = json_decode( $accept['output'], true );
        $this->assertIsArray( $accept_decoded, $accept['output'] );
        $this->assertFalse( $accept_decoded['error'], $accept['output'] );
        $entry_id = (int) $accept_decoded['response_data']['contact_entry_id'];
        $stored = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( str_repeat( $emoji, 3 ), $stored['astral_field']['value'] );

        $reject = $this->submit_data_post( $form_id, array(
            'astral_field' => array( 'name' => 'astral_field', 'value' => str_repeat( $emoji, 4 ), 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $reject['status'], $reject['output'] );
        $reject_decoded = json_decode( $reject['output'], true );
        $this->assertIsArray( $reject_decoded, $reject['output'] );
        $this->assertTrue( $reject_decoded['error'], $reject['output'] );
        $this->assertStringContainsString(
            'Invalid form data.',
            wp_strip_all_tags( $reject_decoded['msg'] )
        );
    }
}

/**
 * Row 18: byte preservation across public submit, admin edit, and CSV import,
 * plus keyword-count length enforcement.
 */
class Test_Super_Forms_Proof_Row18_Byte_Preservation extends Super_Forms_Upload_Security_Test_Case {

    private $extra_files = array();

    public function tear_down() {
        foreach( $this->extra_files as $file ) {
            if( is_file( $file ) ) {
                unlink( $file );
            }
        }
        $this->extra_files = array();
        parent::tear_down();
    }

    private function admin_actor() {
        $user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
        wp_set_current_user( $user_id );
        return $user_id;
    }

    private function form_settings_no_side_effects() {
        return array(
            'send' => 'no',
            'confirm' => 'no',
            'save_contact_entry' => 'yes',
            'form_thanks_title' => '',
            'form_thanks_description' => '',
            'form_show_thanks_msg' => '',
            'form_redirect_option' => '',
        );
    }

    private function count_contact_entries() {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s", 'super_contact_entry' )
        );
    }

    /**
     * Match the real submit JSON bytes the endpoint expects.
     */
    private function submit_data_post( $form_id, $data, $extra_post=array() ) {
        if( !isset($data['hidden_form_id']) ) {
            $data['hidden_form_id'] = array(
                'name' => 'hidden_form_id',
                'value' => (string) $form_id,
                'type' => 'form_id',
            );
        }
        if( !isset($data['hidden_contact_entry_id']) ) {
            $data['hidden_contact_entry_id'] = array(
                'name' => 'hidden_contact_entry_id',
                'value' => '',
                'type' => 'entry_id',
            );
        }
        $_POST = array_merge(
            array(
                'action' => 'super_submit_form',
                'i18n' => '',
                'form_id' => (string) $form_id,
                'data' => wp_slash( wp_json_encode( $data ) ),
            ),
            $extra_post
        );
        $_REQUEST = $_POST;
        $_FILES = array();
        return $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
    }

    /**
     * Build an admin AJAX request under the shared `super_admin_ajax` nonce
     * action (update_contact_entry / prepare_contact_entry_import /
     * import_contact_entries all authorize through the same
     * authorize_admin_ajax_request()).
     */
    private function admin_ajax_post( $overrides ) {
        $_POST = array_merge( array( 'nonce' => wp_create_nonce( 'super_admin_ajax' ) ), $overrides );
        $_REQUEST = $_POST;
    }

    /**
     * Write a CSV fixture for the real import endpoints.
     */
    private function write_csv_file( $scope, $rows ) {
        $uploads = wp_upload_dir();
        $this->assertEmpty( $uploads['error'] );
        $this->assertTrue( wp_mkdir_p( $uploads['path'] ) );
        $basename = wp_unique_filename( $uploads['path'], 'row18-import-' . $scope . '.csv' );
        $file = trailingslashit( $uploads['path'] ) . $basename;
        $fp = fopen( $file, 'w' );
        $this->assertNotFalse( $fp );
        foreach( $rows as $row ) {
            $this->assertNotFalse( fputcsv( $fp, $row, ',', '"' ) );
        }
        fclose( $fp );
        $this->extra_files[] = $file;
        return $file;
    }

    /**
     * Row 18: the same field bytes survive public submit, admin edit, and CSV
     * import.
     */
    public function test_public_submit_persists_apostrophe_backslash_and_quote_bytes_then_survive_admin_edit_and_csv_roundtrip() {
        $scope = 'row18-' . str_replace( '-', '', wp_generate_uuid4() );
        $byte_value = "It's a \\backslash\\ value with \"quotes\" inside " . $scope;

        $elements = array(
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'regex_field',
                    'validation' => 'custom',
                    'custom_regex' => '^[\s\S]{1,160}$',
                ),
            ),
            array(
                'tag' => 'text',
                'data' => array(
                    'name' => 'maxlen_field',
                    'maxlength' => '160',
                ),
            ),
        );
        $form_id = $this->create_form( 'publish', array(), $this->form_settings_no_side_effects() );
        // update_post_meta() unslashes its value, so handing the raw pattern to the
        // shared create_form() helper would store '^[sS]{1,160}$' and reject every
        // byte outside [sS]. save_form() slashes the elements before save_form_meta()
        // (includes/class-ajax.php:2838), so a fixture that stands in for an
        // already-saved form has to slash them the same way (same contract as the
        // Row11 helper at test-security-proof-row11-regex.php:72-76).
        update_post_meta( $form_id, '_super_elements', wp_slash( $elements ) );
        $stored_elements = get_post_meta( $form_id, '_super_elements', true );
        $this->assertSame(
            '^[\s\S]{1,160}$',
            $stored_elements[0]['data']['custom_regex'],
            'The fixture must store the exact custom regex the saved form carried.'
        );

        // Public submit preserves the stored bytes.
        $this->configure_csrf( 'false' );
        wp_set_current_user( 0 );
        $submit = $this->submit_data_post( $form_id, array(
            'regex_field' => array( 'name' => 'regex_field', 'value' => $byte_value, 'type' => 'var' ),
            'maxlen_field' => array( 'name' => 'maxlen_field', 'value' => $byte_value, 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $submit['status'], $submit['output'] );
        $decoded = json_decode( $submit['output'], true );
        $this->assertIsArray( $decoded, $submit['output'] );
        $this->assertFalse( $decoded['error'], $submit['output'] );
        $entry_id = (int) $decoded['response_data']['contact_entry_id'];
        $this->assertGreaterThan( 0, $entry_id );

        $stored = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( $byte_value, $stored['regex_field']['value'] );
        $this->assertSame( $byte_value, $stored['maxlen_field']['value'] );

        // Admin edit preserves the same bytes.
        $this->admin_actor();
        $this->admin_ajax_post( array(
            'action' => 'super_update_contact_entry',
            'id' => (string) $entry_id,
            'data' => wp_slash( array(
                'regex_field' => $byte_value,
                'maxlen_field' => $byte_value,
            ) ),
            'entry_status' => '',
        ) );
        $edit = $this->run_dying_handler( array( 'SUPER_Ajax', 'update_contact_entry' ) );
        $this->assertSame( 0, $edit['status'], $edit['output'] );
        $edit_decoded = json_decode( $edit['output'], true );
        $this->assertIsArray( $edit_decoded, $edit['output'] );
        $this->assertFalse( $edit_decoded['error'], $edit['output'] );
        $after_edit = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( $byte_value, $after_edit['regex_field']['value'] );
        $this->assertSame( $byte_value, $after_edit['maxlen_field']['value'] );

        // CSV import preserves the same bytes.
        $csv_path = $this->write_csv_file( $scope, array(
            array( 'Form ID', 'Title', 'Regex Field', 'Maxlen Field' ),
            array( (string) $form_id, 'CSV import ' . $scope, $byte_value, $byte_value ),
        ) );
        $file_id = wp_insert_attachment(
            array(
                'post_mime_type' => 'text/csv',
                'post_title' => basename( $csv_path ),
                'post_status' => 'inherit',
                'post_author' => get_current_user_id(),
            ),
            $csv_path,
            0
        );
        $this->assertIsInt( $file_id );
        $this->assertGreaterThan( 0, $file_id );
        $this->attachment_ids[] = $file_id;

        $this->admin_ajax_post( array(
            'action' => 'super_prepare_contact_entry_import',
            'file_id' => (string) $file_id,
            'import_delimiter' => ',',
            'import_enclosure' => '"',
        ) );
        $prepare = $this->run_dying_handler( array( 'SUPER_Ajax', 'prepare_contact_entry_import' ) );
        $this->assertSame( 0, $prepare['status'], $prepare['output'] );
        $this->assertSame(
            'super-forms-contact-entry-import-v1',
            get_post_meta( $file_id, '_super_forms_contact_entry_import_file', true )
        );

        $before_count = $this->count_contact_entries();
        $this->admin_ajax_post( array(
            'action' => 'super_import_contact_entries',
            'file_id' => (string) $file_id,
            'column_connections' => array(
                array( 'column' => 'form_id', 'name' => 'hidden_form_id', 'label' => 'Form ID' ),
                array( 'column' => 'post_title', 'name' => 'post_title', 'label' => 'Title' ),
                array( 'column' => 'var', 'name' => 'regex_field', 'label' => 'Regex Field' ),
                array( 'column' => 'var', 'name' => 'maxlen_field', 'label' => 'Maxlen Field' ),
            ),
            'skip_first' => 'true',
            'import_delimiter' => ',',
            'import_enclosure' => '"',
        ) );
        $import = $this->run_dying_handler( array( 'SUPER_Ajax', 'import_contact_entries' ) );
        $this->assertSame( 0, $import['status'], $import['output'] );
        $this->assertStringContainsString( '1 of 1 contact entries imported!', $import['output'] );
        $this->assertSame( $before_count + 1, $this->count_contact_entries() );
        $this->assertSame( '', get_post_meta( $file_id, '_super_forms_contact_entry_import_file', true ) );

        $imported_title = 'CSV import ' . $scope;
        $candidates = get_posts( array(
            'post_type' => 'super_contact_entry',
            'post_status' => array( 'super_unread', 'super_read', 'publish' ),
            'post_parent' => $form_id,
            's' => $imported_title,
            'numberposts' => 5,
        ) );
        $imported_entry = null;
        foreach( $candidates as $candidate ) {
            if( $candidate->post_title === $imported_title ) {
                $imported_entry = $candidate;
                break;
            }
        }
        $this->assertInstanceOf( 'WP_Post', $imported_entry, 'Imported entry with the exact title was not found.' );
        $imported_data = SUPER_Data_Access::get_entry_data( $imported_entry->ID );
        $this->assertSame( $byte_value, $imported_data['regex_field']['value'] );
        $this->assertSame( $byte_value, $imported_data['maxlen_field']['value'] );
    }

    /**
     * Row 18: keyword length limits count tags, not characters.
     */
    public function test_public_submit_enforces_keyword_tag_count_not_character_length_for_maxlength_three() {
        $form_id = $this->create_form(
            'publish',
            array(
                array(
                    'tag' => 'text',
                    'data' => array(
                        'name' => 'tags_field',
                        'enable_keywords' => 'true',
                        'maxlength' => '3',
                    ),
                ),
            ),
            $this->form_settings_no_side_effects()
        );

        $this->configure_csrf( 'false' );
        wp_set_current_user( 0 );

        // Accepted because the field contains three tags.
        $accept = $this->submit_data_post( $form_id, array(
            'tags_field' => array( 'name' => 'tags_field', 'value' => 'alpha, bravo, charlie', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $accept['status'], $accept['output'] );
        $accept_decoded = json_decode( $accept['output'], true );
        $this->assertIsArray( $accept_decoded, $accept['output'] );
        $this->assertFalse( $accept_decoded['error'], $accept['output'] );
        $entry_id = (int) $accept_decoded['response_data']['contact_entry_id'];
        $stored = SUPER_Data_Access::get_entry_data( $entry_id );
        $this->assertSame( 'alpha, bravo, charlie', $stored['tags_field']['value'] );

        $reject = $this->submit_data_post( $form_id, array(
            'tags_field' => array( 'name' => 'tags_field', 'value' => 'alpha, bravo, charlie, delta', 'type' => 'var' ),
        ) );
        $this->assertSame( 0, $reject['status'], $reject['output'] );
        $reject_decoded = json_decode( $reject['output'], true );
        $this->assertIsArray( $reject_decoded, $reject['output'] );
        $this->assertTrue( $reject_decoded['error'], $reject['output'] );
        $this->assertStringContainsString(
            'Invalid form data.',
            wp_strip_all_tags( $reject_decoded['msg'] )
        );
    }
}
