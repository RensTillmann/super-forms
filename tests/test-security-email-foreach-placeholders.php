<?php
/**
 * E-mail foreach `{field}` placeholders (6.4.008 review, item A).
 *
 * 6.4 lets the form author write `{field}` next to `<%field%>` inside an E-mail foreach block
 * (`foreach(option): {options_list} endforeach;`) and `{url}`, `{name}` ... inside a file loop.
 * The 6.3.318 port moved email_if_statements() behind the first email_tags() pass (so foreach/if/isset
 * syntax a visitor typed stays inert). That pass resolved the author's `{field}` to the value of row 1
 * before the loop expanded (every row repeated row 1) and left file loop `{url}` literal.
 * SUPER_Common::protect_foreach_placeholders() rewrites those placeholders of the AUTHOR's template
 * to `<%...%>` before email_tags(). These tests prove both halves:
 * - author `{field}` / `<%field%>` placeholders resolve per row, file loop `{url}` gives the real URL;
 * - whatever a visitor typed (foreach/if/isset syntax, `<%x%>`, `{tags}`, `[shortcodes]`) stays plain text,
 *   in the admin and confirmation e-mail, the success message and the trigger send_email action.
 *
 * @package Super_Forms_Tests
 */

require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Super_Forms_Email_Foreach_Placeholders_Security extends Super_Forms_Upload_Security_Test_Case {

    const ADMIN_EMAIL = 'sec-fe-admin@example.test';
    const SC_MARKER = 'SEC-FE-SHORTCODE-RAN';
    const TEMPLATE = 'ROWS:foreach(guest_name):#{counter} {guest_name}/<%guest_name%>;endforeach;|FILES:foreach(guest_document;loop):U=<%url%>,V={url},N={name};endforeach;|NOTE:{note}|SITE:{option_blogname}|';

    private $original_post_global;

    public function set_up() {
        parent::set_up();
        $this->original_post_global = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
        update_option( 'admin_email', self::ADMIN_EMAIL );
        update_option( 'blogname', 'Sec FE Blog' );
        add_shortcode( 'sf_fe_sc', static function() {
            return self::SC_MARKER;
        } );
    }

    public function tear_down() {
        remove_shortcode( 'sf_fe_sc' );
        $GLOBALS['post'] = $this->original_post_global;
        parent::tear_down();
    }

    /**
     * Everything a visitor could type to try to get the e-mail template engine to run.
     */
    private function visitor_note() {
        return 'foreach(guest_name):<%guest_name%>{guest_name}endforeach; if(1==1):IFRAN endif; isset(note):ISSET endif; {option_admin_email} [sf_fe_sc]';
    }

    private function visitor_row_value() {
        return 'Grace {option_admin_email} [sf_fe_sc]';
    }

    private function assertVisitorTextInert( $output, $context ) {
        $decoded = html_entity_decode( $output, ENT_QUOTES );
        $this->assertStringNotContainsString( self::ADMIN_EMAIL, $decoded, $context );
        $this->assertStringNotContainsString( self::SC_MARKER, $decoded, $context );
        $this->assertStringNotContainsString( "\x1A", $output, $context );
    }

    /**
     * Rows: each row its own value (author `{field}` and `<%field%>`), visitor text inside a row stays literal.
     * Files: author `{url}` equals `<%url%>` and is a real URL, `{name}` the file name.
     */
    private function assertTemplateOutput( $output, $first_row, $context ) {
        $decoded = html_entity_decode( $output, ENT_QUOTES );
        $row2 = $this->visitor_row_value();
        $this->assertStringContainsString( 'ROWS:#1 ' . $first_row . '/' . $first_row . ';#2 ' . $row2 . '/' . $row2 . ';|', $decoded, $context );
        $this->assertStringContainsString( '|NOTE:' . $this->visitor_note() . '|', $decoded, $context );
        $this->assertStringContainsString( '|SITE:Sec FE Blog|', $decoded, $context );
        $this->assertSame( 1, preg_match( '/\|FILES:(.*?)\|NOTE:/s', $decoded, $files ), $context . ': ' . $decoded );
        $this->assertSame( 2, preg_match_all( '/U=([^,]*),V=([^,]*),N=([^;]*);/', $files[1], $rows, PREG_SET_ORDER ), $context . ': ' . $files[1] );
        foreach( $rows as $row ) {
            $this->assertSame( $row[1], $row[2], $context . ': {url} must equal <%url%>' );
            $this->assertMatchesRegularExpression( '#^https?://#', $row[2], $context );
            $this->assertNotSame( '', $row[3], $context );
            $this->assertStringNotContainsString( '{name}', $row[3], $context );
        }
        $this->assertStringNotContainsString( '{url}', $decoded, $context );
        $this->assertVisitorTextInert( $output, $context );
    }

    public function test_protect_rewrites_only_author_foreach_placeholders_of_submitted_fields() {
        $data = array(
            'first_name' => array( 'name' => 'first_name', 'value' => 'Jane' ),
            'file' => array( 'name' => 'file', 'type' => 'files', 'files' => array() ),
        );
        $this->assertSame(
            'A {first_name} foreach(first_name):<%counter%> <%first_name%> <%first_name;label%> {option_blogname} {missing}endforeach; B {first_name}',
            SUPER_Common::protect_foreach_placeholders( 'A {first_name} foreach(first_name):{counter} {first_name} {first_name;label} {option_blogname} {missing}endforeach; B {first_name}', $data )
        );
        $this->assertSame(
            'foreach(file;loop):<%url%> <%name%> <%counter%> {file;url} {option_blogname}endforeach;',
            SUPER_Common::protect_foreach_placeholders( 'foreach(file;loop):{url} {name} {counter} {file;url} {option_blogname}endforeach;', $data )
        );
        // No foreach block: unchanged.
        $this->assertSame( '{first_name} {url}', SUPER_Common::protect_foreach_placeholders( '{first_name} {url}', $data ) );
    }

    /*
     * Full submission: admin e-mail, confirmation e-mail and success message (SUPER_Ajax::submit_form()).
     */

    private function repeater_elements() {
        return array(
            array(
                'tag' => 'column',
                'data' => array( 'duplicate' => 'enabled' ),
                'inner' => array(
                    array( 'tag' => 'text', 'data' => array( 'name' => 'guest_name', 'validation' => 'none', 'email' => 'Guest' ) ),
                    $this->file_element( 'guest_document' ),
                ),
            ),
            // A TinyMCE field keeps every character the visitor typed (also `<%x%>`), the worst case here
            array( 'tag' => 'tinymce', 'data' => array( 'name' => 'note', 'email' => 'Note' ) ),
        );
    }

    private function field( $name, $value, $label ) {
        return array(
            'name' => $name,
            'value' => $value,
            'label' => $label,
            'exclude' => 'false',
            'replace_commas' => 'false',
            'exclude_entry' => 'false',
            'excludeconditional' => 'false',
            'type' => 'var',
        );
    }

    private function submission_data( $row1_token, $row2_token ) {
        $data = array(
            '_super_dynamic_data' => array(
                'guest_name' => array(
                    array(
                        'guest_name' => $this->field( 'guest_name', 'Ada', 'Guest' ),
                        'guest_document' => array( 'label' => 'Document', 'type' => 'files', 'exclude' => 'false', 'exclude_entry' => 'false', 'files' => array( array( 'upload_token' => $row1_token ) ) ),
                    ),
                    array(
                        'guest_name_2' => $this->field( 'guest_name_2', $this->visitor_row_value(), 'Guest' ),
                        'guest_document_2' => array( 'field_name' => 'guest_document', 'type' => 'files', 'files' => array( array( 'upload_token' => $row2_token ) ) ),
                    ),
                ),
            ),
        );
        $data['guest_name'] = $data['_super_dynamic_data']['guest_name'][0]['guest_name'];
        $data['guest_document'] = $data['_super_dynamic_data']['guest_name'][0]['guest_document'];
        $data['guest_name_2'] = $data['_super_dynamic_data']['guest_name'][1]['guest_name_2'];
        $data['guest_document_2'] = $data['_super_dynamic_data']['guest_name'][1]['guest_document_2'];
        $data['note'] = $this->field( 'note', $this->visitor_note(), 'Note' );
        $data['note']['type'] = 'html';
        return $data;
    }

    public function test_full_submission_admin_and_confirmation_email_and_success_message_resolve_rows_and_keep_visitor_text_literal() {
        wp_set_current_user( 0 );
        $GLOBALS['post'] = null;
        $this->configure_csrf( 'false' );
        $loop = '<tr><th>{loop_label}</th><td>{loop_value}</td></tr>';
        $form_id = $this->create_form( 'publish', $this->repeater_elements(), array(
            'save_contact_entry' => 'yes',
            'send' => 'yes',
            'confirm' => 'yes',
            'header_to' => 'sec-fe-recipient@example.test',
            'confirm_to' => 'sec-fe-visitor@example.test',
            'header_subject' => 'Admin copy',
            'confirm_subject' => 'Your copy',
            'header_from_type' => 'default',
            'confirm_from_type' => 'default',
            'email_body_open' => '',
            'email_body' => self::TEMPLATE,
            'email_body_close' => '',
            'email_body_nl2br' => 'false',
            'confirm_body_open' => '',
            'confirm_body' => self::TEMPLATE,
            'confirm_body_close' => '',
            'confirm_body_nl2br' => 'false',
            'email_loop' => $loop,
            'confirm_email_loop' => $loop,
            'email_exclude_empty' => '',
            'confirm_exclude_empty' => '',
            'form_thanks_title' => '',
            'form_thanks_description' => 'Thanks foreach(guest_name):{guest_name},endforeach;',
            'form_show_thanks_msg' => 'true',
            'form_redirect_option' => '',
        ) );
        $row1 = $this->create_owned_upload( $form_id, 'guest_document', false, 'row one bytes' );
        $row2 = $this->create_owned_upload( $form_id, 'guest_document', false, 'row two bytes' );
        $this->set_submit_request( $form_id, $this->submission_data( $this->issue_receipt( $row1['owned'] ), $this->issue_receipt( $row2['owned'] ) ) );

        // The handler runs in a forked child, so the captured mail goes through a file.
        $mail_log = tempnam( sys_get_temp_dir(), 'sf-sec-fe-mail-' );
        $capture = static function( $short_circuit, $atts ) use ( $mail_log ) {
            file_put_contents( $mail_log, wp_json_encode( array( 'to' => $atts['to'], 'message' => $atts['message'] ) ) . "\n", FILE_APPEND | LOCK_EX );
            return true;
        };
        add_filter( 'pre_wp_mail', $capture, 10, 2 );
        try {
            $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        } finally {
            remove_filter( 'pre_wp_mail', $capture, 10 );
        }
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );
        $this->assertStringContainsString( 'Thanks Ada,' . $this->visitor_row_value() . ',', html_entity_decode( $decoded['msg'], ENT_QUOTES ), $decoded['msg'] );
        $this->assertVisitorTextInert( $decoded['msg'], 'success message' );

        $lines = array_values( array_filter( explode( "\n", (string) file_get_contents( $mail_log ) ) ) );
        unlink( $mail_log );
        $this->assertCount( 2, $lines, 'admin and confirmation e-mail were not both captured' );
        $seen = array();
        foreach( $lines as $line ) {
            $mail = json_decode( $line, true );
            $to = is_array( $mail['to'] ) ? implode( ',', $mail['to'] ) : (string) $mail['to'];
            $context = ( strpos( $to, 'sec-fe-visitor@' )!==false ) ? 'confirmation e-mail' : 'admin e-mail';
            $seen[$context] = true;
            $this->assertTemplateOutput( $mail['message'], 'Ada', $context );
        }
        $this->assertCount( 2, $seen );
    }

    /*
     * Trigger action `send_email` (SUPER_Triggers::send_email(), 6.4 only).
     */

    private function trigger_data() {
        $file = static function( $name, $url ) {
            return array( 'name' => 'guest_document', 'value' => $name, 'url' => $url, 'type' => 'image/png', 'attachment' => 0 );
        };
        return array(
            'guest_name' => array( 'name' => 'guest_name', 'value' => 'Ada', 'label' => 'Guest', 'type' => 'var' ),
            'guest_name_2' => array( 'name' => 'guest_name_2', 'value' => $this->visitor_row_value(), 'label' => 'Guest', 'type' => 'var' ),
            'guest_document' => array( 'name' => 'guest_document', 'label' => 'Document', 'type' => 'files', 'files' => array( $file( 'one.png', 'https://files.example.test/one.png' ) ) ),
            'guest_document_2' => array( 'name' => 'guest_document_2', 'label' => 'Document', 'type' => 'files', 'files' => array( $file( 'two.png', 'https://files.example.test/two.png' ) ) ),
            // `html` (TinyMCE) keeps every typed character, a `var` value loses `<%x%>` to strip_tags() in SUPER_Common::decode()
            'note' => array( 'name' => 'note', 'value' => $this->visitor_note(), 'label' => 'Note', 'type' => 'html' ),
        );
    }

    public function test_trigger_send_email_resolves_rows_and_keeps_visitor_text_literal() {
        if( !class_exists( 'SUPER_Triggers' ) ) {
            require_once dirname( __DIR__ ) . '/src/includes/class-triggers.php';
        }
        $form_id = $this->create_form( 'publish', $this->repeater_elements() );
        $data = $this->trigger_data();
        $data['hidden_form_id'] = array( 'name' => 'hidden_form_id', 'value' => (string) $form_id, 'type' => 'form_id' );
        $settings = array_merge( SUPER_Common::get_form_settings( $form_id ), array( 'id' => $form_id ) );
        $options = array(
            'schedule' => array( 'enabled' => 'false' ),
            'exclude' => array( 'enabled' => 'false' ),
            'loop' => '<tr><th>{loop_label}</th><td>{loop_value}</td></tr>',
            'loop_open' => '<table>',
            'loop_close' => '</table>',
            'body' => self::TEMPLATE . '{loop_fields}',
            'rtl' => 'false',
            'to' => 'sec-fe-trigger@example.test',
            'from_email' => 'sec-fe-from@example.test',
            'from_name' => 'Sec FE',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Trigger',
            'header_additional' => '',
            'charset' => 'UTF-8',
            'content_type' => 'html',
            'reply_to' => array( 'enabled' => 'false' ),
            'attachments' => '',
        );
        $messages = array();
        $capture = static function( $short_circuit, $atts ) use ( &$messages ) {
            $messages[] = $atts['message'];
            return true;
        };
        add_filter( 'pre_wp_mail', $capture, 10, 2 );
        try {
            SUPER_Triggers::send_email( array(
                'i18n' => '',
                'eventName' => 'sf.after.submission',
                'triggerName' => 'Sec FE trigger',
                'action' => array( 'action' => 'send_email', 'data' => $options ),
                'form_id' => $form_id,
                'form_data' => array( 'data' => $data, 'settings' => $settings ),
            ) );
        } finally {
            remove_filter( 'pre_wp_mail', $capture, 10 );
        }
        $this->assertCount( 1, $messages );
        $this->assertTemplateOutput( $messages[0], 'Ada', 'trigger send_email' );
        $decoded = html_entity_decode( $messages[0], ENT_QUOTES );
        $this->assertStringContainsString( 'U=https://files.example.test/one.png,V=https://files.example.test/one.png,N=one.png;', $decoded );
        $this->assertStringContainsString( 'U=https://files.example.test/two.png,V=https://files.example.test/two.png,N=two.png;', $decoded );
        // The {loop_fields} rows carry the visitor note as text too.
        $this->assertSame( 2, substr_count( $decoded, $this->visitor_note() ), $decoded );
    }
}
