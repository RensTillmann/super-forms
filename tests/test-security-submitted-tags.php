<?php
/**
 * Submitted-tags regression (sec-g).
 *
 * SUPER_Common::email_tags() substitutes submitted field values into the form
 * author's template first and then runs the {option_*}, {@secret},
 * {user_meta_*}, {post_meta_*}, {form_setting_*} and system-tag branches over
 * the combined string. A `{tag}` a visitor typed into a field therefore used
 * to resolve as if the author had written it (proven with a PayPal item name
 * `{item_name}` that returned the admin e-mail).
 *
 * Owner decision (2026-10-01): tags a visitor types are plain text and are
 * never resolved, so they cannot come back resolved through e-mails, the
 * success message, PayPal parameters, stored entries, last-entry / edit-view
 * prefills or {loop_fields}. Tags the form author configured keep resolving.
 * The same holds for a `[shortcode]` a visitor typed: it never runs in an
 * e-mail, a prefill or the Listings view, author shortcodes keep running.
 *
 * @package Super_Forms_Tests
 */

require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Super_Forms_Submitted_Tags_Security extends Super_Forms_Upload_Security_Test_Case {

    const ADMIN_EMAIL = 'sec-g-admin@example.test';
    const SMTP_PASSWORD = 'SEC-G-SMTP-PASSWORD';
    const USER_META = 'SEC-G-USER-META';
    const POST_META = 'SEC-G-POST-META';
    const FORM_SETTING = 'SEC-G-FORM-SETTING';
    const GLOBAL_SECRET = 'SEC-G-GLOBAL-SECRET';
    const SALES_EMAIL = 'sec-g-sales@example.test';
    const SC_MARKER = 'SEC-G-SHORTCODE-RAN';

    private $original_post_global;
    private $form_id;
    private $settings;

    public function set_up() {
        parent::set_up();
        $this->original_post_global = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;

        update_option( 'admin_email', self::ADMIN_EMAIL );
        update_option( 'blogname', 'Sec G Blog' );
        update_option( 'super_settings', array( 'smtp_password' => self::SMTP_PASSWORD ), false );
        update_option( 'super_global_secrets', array(
            array( 'name' => 'global_secret', 'value' => self::GLOBAL_SECRET ),
        ) );

        $user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        update_user_meta( $user_id, 'sec_g_meta', self::USER_META );
        wp_set_current_user( $user_id );

        $page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Sec G Page' ) );
        update_post_meta( $page_id, 'sec_g_meta', self::POST_META );
        $GLOBALS['post'] = get_post( $page_id );

        $this->form_id = $this->create_form( 'publish', $this->elements() );
        update_post_meta( $this->form_id, '_super_local_secrets', array(
            array( 'name' => 'sales_email', 'value' => self::SALES_EMAIL ),
        ) );
        $this->settings = array(
            'id' => $this->form_id,
            'secret_setting' => self::FORM_SETTING,
            'paypal_item_name' => '{item_name}',
            'header_subject' => 'New message from {first_name}',
            'email_body' => '<p>{message}</p><p>{option_blogname}</p>',
            'email_loop' => '<tr><th>{loop_label}</th><td>{loop_value}</td></tr>',
            'confirm_email_loop' => '<tr><th>{loop_label}</th><td>{loop_value}</td></tr>',
            'email_exclude_empty' => '',
            'confirm_exclude_empty' => '',
        );
    }

    public function tear_down() {
        $GLOBALS['post'] = $this->original_post_global;
        $_GET = array();
        parent::tear_down();
    }

    private function elements() {
        return array(
            array( 'group' => 'form_elements', 'tag' => 'text', 'data' => array( 'name' => 'first_name', 'email' => 'First name' ) ),
            array( 'group' => 'form_elements', 'tag' => 'textarea', 'data' => array( 'name' => 'message', 'email' => 'Message' ) ),
            array( 'group' => 'form_elements', 'tag' => 'hidden', 'data' => array( 'name' => 'item_name', 'value' => 'Order from {post_title}', 'email' => 'Item' ) ),
            array( 'group' => 'form_elements', 'tag' => 'hidden', 'data' => array( 'name' => 'route', 'value' => '{@global_secret}', 'email' => 'Route' ) ),
            array( 'group' => 'form_elements', 'tag' => 'dropdown', 'data' => array(
                'name' => 'department',
                'email' => 'Department',
                'dropdown_items' => array(
                    array( 'checked' => false, 'label' => 'Sales', 'value' => '{@sales_email}' ),
                    array( 'checked' => false, 'label' => 'Other', 'value' => 'other' ),
                ),
            ) ),
        );
    }

    private function data( $values ) {
        $data = array(
            'hidden_form_id' => array( 'name' => 'hidden_form_id', 'value' => (string) $this->form_id, 'type' => 'form_id' ),
        );
        foreach( $values as $name => $value ) {
            $data[$name] = array( 'name' => $name, 'value' => $value, 'label' => ucfirst( $name ), 'type' => 'var' );
        }
        return $data;
    }

    private function visitor_tags() {
        return array(
            '{option_admin_email}',
            '{option_super_settings;smtp_password}',
            '{user_meta_sec_g_meta}',
            '{post_meta_sec_g_meta}',
            '{form_setting_secret_setting}',
            '{@global_secret}',
            '{@sales_email}',
            '{{option_admin_email}}',
        );
    }

    private function assertNoSecret( $output ) {
        foreach( array( self::ADMIN_EMAIL, self::SMTP_PASSWORD, self::USER_META, self::POST_META, self::FORM_SETTING, self::GLOBAL_SECRET, self::SALES_EMAIL ) as $secret ) {
            $this->assertStringNotContainsString( $secret, $output );
        }
    }

    public function test_visitor_typed_lookup_tags_stay_literal_in_email_body_subject_paypal_item_name_and_success_message() {
        foreach( $this->visitor_tags() as $typed ) {
            $data = $this->data( array( 'first_name' => $typed, 'message' => $typed, 'item_name' => $typed ) );

            $body = SUPER_Common::email_tags( $this->settings['email_body'], $data, $this->settings );
            $this->assertSame( '<p>' . $typed . '</p><p>Sec G Blog</p>', $body, $typed );

            $subject = SUPER_Common::decode( SUPER_Common::email_tags( $this->settings['header_subject'], $data, $this->settings ) );
            $this->assertSame( 'New message from ' . $typed, $subject, $typed );

            // Same call the PayPal add-on makes for the item_name parameter.
            $item_name = SUPER_Common::email_tags( $this->settings['paypal_item_name'], $data, $this->settings );
            $this->assertSame( $typed, $item_name, $typed );

            // Same pipeline as the success message in SUPER_Ajax::submit_form().
            $msg = SUPER_Common::email_tags( '<h1>Thanks</h1>{first_name}', $data, $this->settings );
            $msg = SUPER_Forms()->email_if_statements( $msg, array( 'data' => $data, 'settings' => $this->settings ) );
            $this->assertSame( '<h1>Thanks</h1>' . $typed, $msg, $typed );

            $this->assertNoSecret( $body . $subject . $item_name . $msg );
        }
    }

    public function test_author_tags_and_field_references_keep_resolving() {
        $data = $this->data( array( 'first_name' => 'Jane', 'department' => '{@sales_email}', 'route' => '{@global_secret}' ) );
        $data['department']['option_label'] = 'Sales';
        $data['first_name']['timestamp'] = '1700000000';

        $this->assertSame( self::ADMIN_EMAIL, SUPER_Common::email_tags( '{option_admin_email}', $data, $this->settings ) );
        $this->assertSame( self::SMTP_PASSWORD, SUPER_Common::email_tags( '{option_super_settings;smtp_password}', $data, $this->settings ) );
        $this->assertSame( self::FORM_SETTING, SUPER_Common::email_tags( '{form_setting_secret_setting}', $data, $this->settings ) );
        $this->assertSame( self::GLOBAL_SECRET, SUPER_Common::email_tags( '{@global_secret}', $data, $this->settings ) );
        $this->assertSame(
            'Hello Jane, write to ' . self::ADMIN_EMAIL . ' at Sec G Blog',
            SUPER_Common::email_tags( 'Hello {first_name}, write to {option_admin_email} at {option_blogname}', $data, $this->settings )
        );
        $this->assertSame( 'Jane', SUPER_Common::email_tags( '{field_first_name}', $data, $this->settings ) );
        $this->assertSame( 'First_name', SUPER_Common::email_tags( '{field_label_first_name}', $data, $this->settings ) );
        $this->assertSame( 'Sales', SUPER_Common::email_tags( '{department;label}', $data, $this->settings ) );
        $this->assertSame( '1700000000', SUPER_Common::email_tags( '{first_name;timestamp}', $data, $this->settings ) );

        // Secrets in choice items (docs: features/advanced/secrets.md) are author-configured.
        $this->assertSame( self::SALES_EMAIL, SUPER_Common::email_tags( '{department}', $data, $this->settings ) );
        // A hidden field whose author default is a secret resolves on submission.
        $this->assertSame( self::GLOBAL_SECRET, SUPER_Common::email_tags( '{route}', $data, $this->settings ) );
    }

    public function test_only_the_tags_the_author_configured_on_that_same_field_resolve() {
        // The dropdown's author secret typed into a text field stays literal.
        $data = $this->data( array( 'first_name' => '{@sales_email}' ) );
        $this->assertSame( '{@sales_email}', SUPER_Common::email_tags( '{first_name}', $data, $this->settings ) );

        // A tampered hidden field keeps the author's tag but not the added one.
        $data = $this->data( array( 'route' => '{@global_secret} {option_admin_email}' ) );
        $this->assertSame( self::GLOBAL_SECRET . ' {option_admin_email}', SUPER_Common::email_tags( '{route}', $data, $this->settings ) );
    }

    public function test_visitor_value_naming_another_field_does_not_pull_that_field() {
        $data = $this->data( array( 'first_name' => '{message}', 'message' => 'PRIVATE-MESSAGE' ) );
        $this->assertSame( 'X {message} X', SUPER_Common::email_tags( 'X {first_name} X', $data, $this->settings ) );

        $data = $this->data( array( 'message' => 'PRIVATE-MESSAGE', 'first_name' => '{field_message}' ) );
        $this->assertSame( '{field_message}', SUPER_Common::email_tags( '{first_name}', $data, $this->settings ) );
    }

    public function test_form_setting_recursion_does_not_resolve_visitor_text() {
        $settings = $this->settings;
        $settings['subject_template'] = 'Hi {first_name}';
        $data = $this->data( array( 'first_name' => '{option_admin_email}' ) );
        $this->assertSame( 'Hi {option_admin_email}', SUPER_Common::email_tags( '{form_setting_subject_template}', $data, $settings ) );
    }

    public function test_email_loop_rows_keep_visitor_text_literal() {
        $data = $this->data( array( 'first_name' => '{option_admin_email}', 'department' => '{@sales_email}' ) );
        $loops = SUPER_Common::retrieve_email_loop_html( array(
            'data' => $data,
            'settings' => $this->settings,
            'exclude' => array(),
            'listing_loop' => '<div>{loop_label}: {loop_value}</div>',
        ) );
        foreach( array( 'email_loop', 'confirm_loop' ) as $loop ) {
            $body = SUPER_Common::email_tags( str_replace( '{loop_fields}', $loops[$loop], '<table>{loop_fields}</table>' ), $data, $this->settings );
            // Rendered by the mail client as the literal `{option_admin_email}`.
            $this->assertStringContainsString( '<td>&#123;option_admin_email}</td>', $body );
            $this->assertStringNotContainsString( self::ADMIN_EMAIL, $body );
            // The author's choice-item secret still routes through {loop_fields}.
            $this->assertStringContainsString( '<td>' . self::SALES_EMAIL . '</td>', $body );
        }
        // Listings "view entry" HTML.
        $this->assertStringContainsString( '<div>First_name: &#123;option_admin_email}</div>', $loops['listing_loop'] );
    }

    public function test_visitor_typed_foreach_statement_cannot_resolve_lookup_tags() {
        $data = $this->data( array( 'first_name' => 'foreach(first_name): {option_admin_email} <%option_admin_email%> <%@global_secret%> endforeach;' ) );
        $body = SUPER_Common::email_tags( '<p>{first_name}</p>', $data, $this->settings );
        $body = SUPER_Forms()->email_if_statements( $body, array( 'data' => $data, 'settings' => $this->settings ) );
        $this->assertNoSecret( $body );

        // The documented author foreach loop keeps working.
        $data = $this->data( array( 'first_name' => 'Ann', 'first_name_2' => 'Bob' ) );
        $body = SUPER_Common::email_tags( 'foreach(first_name):<%counter%>. <%first_name%> ({option_blogname})<br />endforeach;', $data, $this->settings );
        $body = SUPER_Forms()->email_if_statements( $body, array( 'data' => $data, 'settings' => $this->settings ) );
        $this->assertSame( '1. Ann (Sec G Blog)<br />2. Bob (Sec G Blog)<br />', $body );
    }

    public function test_stored_entry_value_is_exactly_what_the_visitor_typed() {
        // The same expression SUPER_Ajax::submit_form() uses to build the entry data.
        foreach( array_merge( $this->visitor_tags(), array( 'plain text', '{first_name}' ) ) as $typed ) {
            $data = $this->data( array( 'first_name' => $typed ) );
            $stored = SUPER_Common::email_tags( SUPER_Common::neutralize_submitted_value( $typed, 'first_name', $data, $this->settings ), $data, $this->settings );
            $this->assertSame( $typed, $stored );
        }
        // An author default secret on a hidden field is resolved as before.
        $data = $this->data( array( 'route' => '{@global_secret}' ) );
        $this->assertSame( self::GLOBAL_SECRET, SUPER_Common::email_tags( SUPER_Common::neutralize_submitted_value( '{@global_secret}', 'route', $data, $this->settings ), $data, $this->settings ) );
    }

    public function test_full_submission_stores_and_echoes_visitor_tags_verbatim() {
        // A plain anonymous visitor submission.
        wp_set_current_user( 0 );
        $GLOBALS['post'] = null;
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array(
            array( 'group' => 'form_elements', 'tag' => 'text', 'data' => array( 'name' => 'note' ) ),
        ), array(
            'save_contact_entry' => 'yes',
            'send' => 'no',
            'confirm' => 'no',
            'form_thanks_title' => '',
            'form_thanks_description' => 'Thanks {note}',
            'form_show_thanks_msg' => 'true',
            'form_redirect_option' => '',
        ) );
        $typed = '{option_admin_email}';
        $this->set_submit_request( $form_id, array(
            'note' => array( 'name' => 'note', 'value' => $typed, 'type' => 'var' ),
        ) );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );
        $this->assertStringContainsString( 'Thanks ' . $typed, $decoded['msg'] );
        $this->assertStringNotContainsString( self::ADMIN_EMAIL, $result['output'] );

        $entry_id = (int) $decoded['response_data']['contact_entry_id'];
        $this->assertGreaterThan( 0, $entry_id );
        $this->assertSame( $typed, SUPER_Data_Access::get_entry_data( $entry_id )['note']['value'] );
    }

    public function test_prefill_from_url_parameter_or_entry_data_stays_literal() {
        $settings = $this->settings;
        $atts = array( 'name' => 'first_name', 'value' => '' );

        // ?first_name={option_super_settings;smtp_password}
        $_GET = array( 'first_name' => '{option_super_settings;smtp_password}' );
        $this->assertSame( '{option_super_settings;smtp_password}', SUPER_Shortcodes::get_default_value( 'text', $atts, $settings, null ) );
        $_GET = array();

        // Last-entry autopopulate / Listings edit view render the stored entry value.
        $entry_data = array( 'first_name' => array( 'name' => 'first_name', 'value' => '{option_admin_email}' ) );
        $this->assertSame( '{option_admin_email}', SUPER_Shortcodes::get_default_value( 'text', $atts, $settings, $entry_data ) );

        // An author default still resolves at render.
        $this->assertSame( 'Sec G Blog', SUPER_Shortcodes::get_default_value( 'text', array( 'name' => 'first_name', 'value' => '{option_blogname}' ), $settings, null ) );
    }

    /*
     * Owner decision (2026-10-01): a `[shortcode]` a visitor typed renders literally wherever
     * the result still runs through do_shortcode() (e-mails, prefills, Listings view), while the
     * shortcodes the form author wrote keep running.
     */

    private function register_test_shortcode() {
        add_shortcode( 'sf_test_sc', static function() {
            return self::SC_MARKER;
        } );
    }

    private function visitor_shortcodes() {
        return array( '[sf_test_sc]', '[sf_test_sc a="1"]', '[[sf_test_sc]]', 'x"] [sf_test_sc]', '{option_admin_email}[sf_test_sc]' );
    }

    /**
     * The sequence SUPER_Ajax::submit_form() (and the PayPal, WooCommerce and E-mail Reminders
     * add-ons) use for an e-mail body.
     */
    private function email_body( $template, $data, $settings, $nl2br=true ) {
        $loops = SUPER_Common::retrieve_email_loop_html( array( 'data' => $data, 'settings' => $settings, 'exclude' => array() ) );
        $body = str_replace( '{loop_fields}', $loops['email_loop'], $template );
        $literalValues = array();
        $body = SUPER_Common::email_tags( $body, $data, $settings, null, true, false, false, $literalValues );
        if( $nl2br ) $body = nl2br( $body );
        $body = do_shortcode( $body );
        return SUPER_Common::restore_literal_tag_values( $body, $literalValues );
    }

    public function test_visitor_typed_shortcode_stays_literal_in_email_body_subject_paypal_item_name_and_loop_fields() {
        $this->register_test_shortcode();
        try {
            foreach( $this->visitor_shortcodes() as $typed ) {
                $data = $this->data( array( 'first_name' => $typed, 'message' => $typed, 'item_name' => $typed ) );

                // HTML e-mail (nl2br) and plain text e-mail both carry the typed characters.
                $this->assertSame( '<p>' . $typed . '</p>', $this->email_body( '<p>{message}</p>', $data, $this->settings ), $typed );
                $this->assertSame( 'Message: ' . $typed, $this->email_body( 'Message: {message}', $data, $this->settings, false ), $typed );

                // Visitor text inside an author shortcode attribute adds no shortcode of its own.
                $body = $this->email_body( '<p>[sf_test_sc a="{first_name}"]</p>', $data, $this->settings );
                $this->assertSame( 1, substr_count( $body, self::SC_MARKER ), $typed );

                // {loop_fields}: entities in the HTML rows, the shortcode never runs.
                $body = $this->email_body( '<table>{loop_fields}</table>', $data, $this->settings );
                $this->assertStringNotContainsString( self::SC_MARKER, $body, $typed );
                $this->assertStringContainsString( 'sf_test_sc', $body, $typed );

                $subject = SUPER_Common::decode( SUPER_Common::email_tags( $this->settings['header_subject'], $data, $this->settings ) );
                $this->assertSame( 'New message from ' . $typed, $subject, $typed );
                $this->assertSame( $typed, SUPER_Common::email_tags( $this->settings['paypal_item_name'], $data, $this->settings ), $typed );
                // The stored entry value stays verbatim.
                $this->assertSame( $typed, SUPER_Common::email_tags( SUPER_Common::neutralize_submitted_value( $typed, 'first_name', $data, $this->settings ), $data, $this->settings ), $typed );
                $this->assertNoSecret( $body . $subject );
            }
        } finally {
            remove_shortcode( 'sf_test_sc' );
        }
    }

    public function test_author_shortcodes_keep_running_in_emails() {
        $this->register_test_shortcode();
        try {
            $data = $this->data( array( 'first_name' => 'Jane' ) );
            $this->assertSame( '<p>' . self::SC_MARKER . ' Jane</p>', $this->email_body( '<p>[sf_test_sc] {first_name}</p>', $data, $this->settings ) );
            // An author shortcode configured as a choice item value still runs ...
            $elements = $this->elements();
            $elements[] = array( 'group' => 'form_elements', 'tag' => 'dropdown', 'data' => array(
                'name' => 'promo',
                'email' => 'Promo',
                'dropdown_items' => array( array( 'checked' => false, 'label' => 'Promo', 'value' => '[sf_test_sc]' ) ),
            ) );
            update_post_meta( $this->form_id, '_super_elements', $elements );
            $data = $this->data( array( 'promo' => '[sf_test_sc]', 'first_name' => '[sf_test_sc]' ) );
            $this->assertSame( '<p>' . self::SC_MARKER . '</p>', $this->email_body( '<p>{promo}</p>', $data, $this->settings ) );
            // ... but typed into another field it is text.
            $this->assertSame( '<p>[sf_test_sc]</p>', $this->email_body( '<p>{first_name}</p>', $data, $this->settings ) );
        } finally {
            remove_shortcode( 'sf_test_sc' );
        }
    }

    public function test_full_submission_email_and_success_message_keep_visitor_shortcode_literal() {
        wp_set_current_user( 0 );
        $GLOBALS['post'] = null;
        $this->configure_csrf( 'false' );
        $this->register_test_shortcode();
        $form_id = $this->create_form( 'publish', array(
            array( 'group' => 'form_elements', 'tag' => 'text', 'data' => array( 'name' => 'note' ) ),
        ), array(
            'save_contact_entry' => 'no',
            'send' => 'yes',
            'confirm' => 'no',
            'header_to' => 'sec-g-recipient@example.test',
            'header_from_type' => 'default',
            'header_subject' => 'Subject {note}',
            'email_body_open' => '',
            'email_body' => '<p>{note}</p><p>[sf_test_sc]</p>',
            'email_body_close' => '',
            'email_body_nl2br' => 'true',
            'form_thanks_title' => '',
            'form_thanks_description' => '[sf_test_sc] Thanks {note}',
            'form_show_thanks_msg' => 'true',
            'form_redirect_option' => '',
        ) );
        $typed = '[sf_test_sc]';
        $this->set_submit_request( $form_id, array(
            'note' => array( 'name' => 'note', 'value' => $typed, 'type' => 'var' ),
        ) );
        // The handler runs in a forked child, so the captured mail goes through a file.
        $mail_log = tempnam( sys_get_temp_dir(), 'sf-sec-g-mail-' );
        $capture = static function( $short_circuit, $atts ) use ( $mail_log ) {
            file_put_contents( $mail_log, wp_json_encode( array( 'subject' => $atts['subject'], 'message' => $atts['message'] ) ) . "\n", FILE_APPEND | LOCK_EX );
            return true;
        };
        add_filter( 'pre_wp_mail', $capture, 10, 2 );
        try {
            $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        } finally {
            remove_filter( 'pre_wp_mail', $capture, 10 );
            remove_shortcode( 'sf_test_sc' );
        }
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertIsArray( $decoded, $result['output'] );
        $this->assertFalse( $decoded['error'], $result['output'] );
        // Success message: the author's shortcode ran once, the typed one is text.
        $this->assertSame( 1, substr_count( $decoded['msg'], self::SC_MARKER ), $decoded['msg'] );
        $this->assertStringContainsString( 'Thanks ' . $typed, $decoded['msg'] );

        $lines = array_filter( explode( "\n", (string) file_get_contents( $mail_log ) ) );
        unlink( $mail_log );
        $this->assertCount( 1, $lines, 'admin e-mail was not captured' );
        $mail = json_decode( reset( $lines ), true );
        $this->assertSame( 'Subject ' . $typed, $mail['subject'] );
        $this->assertStringContainsString( '<p>' . $typed . '</p>', $mail['message'] );
        $this->assertSame( 1, substr_count( $mail['message'], self::SC_MARKER ), $mail['message'] );
    }

    public function test_listings_view_rows_keep_visitor_shortcode_literal() {
        $this->register_test_shortcode();
        try {
            foreach( $this->visitor_shortcodes() as $typed ) {
                $data = $this->data( array( 'first_name' => $typed ) );
                $loops = SUPER_Common::retrieve_email_loop_html( array(
                    'data' => $data,
                    'settings' => $this->settings,
                    'exclude' => array(),
                    'listing_loop' => '<div>{loop_label}: {loop_value}</div>',
                ) );
                // Same as includes/extensions/listings/form-blank-page-template.php: template + rows, then do_shortcode().
                $view = do_shortcode( '<h2>[sf_test_sc]</h2>' . $loops['listing_loop'] );
                $this->assertSame( 1, substr_count( $view, self::SC_MARKER ), $view );
                $this->assertStringContainsString( 'First_name: ' . $typed, html_entity_decode( $view, ENT_QUOTES ), $typed );
            }
        } finally {
            remove_shortcode( 'sf_test_sc' );
        }
    }

    public function test_prefill_from_url_parameter_or_entry_data_does_not_run_visitor_shortcode() {
        $this->register_test_shortcode();
        try {
            $atts = array( 'name' => 'first_name', 'value' => '' );
            foreach( $this->visitor_shortcodes() as $typed ) {
                // The rendered form HTML is passed through do_shortcode() at the end of super_form_func().
                $_GET = array( 'first_name' => $typed );
                $page = do_shortcode( SUPER_Shortcodes::get_default_value( 'text', $atts, $this->settings, null ) );
                $this->assertStringNotContainsString( self::SC_MARKER, $page, $typed );
                $_GET = array();

                $entry_data = array( 'first_name' => array( 'name' => 'first_name', 'value' => $typed ) );
                $page = do_shortcode( SUPER_Shortcodes::get_default_value( 'text', $atts, $this->settings, $entry_data ) );
                $this->assertStringNotContainsString( self::SC_MARKER, $page, $typed );
            }
            // An author default shortcode still runs.
            $this->assertSame( self::SC_MARKER, SUPER_Shortcodes::get_default_value( 'text', array( 'name' => 'first_name', 'value' => '[sf_test_sc]' ), $this->settings, null ) );
        } finally {
            remove_shortcode( 'sf_test_sc' );
        }
    }

    public function test_author_shortcode_in_html_element_runs_and_url_prefill_adds_none() {
        $this->register_test_shortcode();
        try {
            $form_id = $this->create_form( 'publish', array(
                array( 'group' => 'html_elements', 'tag' => 'html', 'data' => array( 'html' => '<p>[sf_test_sc]</p>' ), 'inner' => array() ),
                array( 'group' => 'form_elements', 'tag' => 'text', 'data' => array( 'name' => 'first_name', 'email' => 'First name' ), 'inner' => array() ),
            ) );
            $_GET = array();
            $plain = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
            $author_runs = substr_count( $plain, self::SC_MARKER );
            $this->assertGreaterThan( 0, $author_runs, $plain );

            $_GET = array( 'first_name' => '[sf_test_sc]' );
            $prefilled = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
            $_GET = array();
            $this->assertSame( $author_runs, substr_count( $prefilled, self::SC_MARKER ), $prefilled );
        } finally {
            $_GET = array();
            remove_shortcode( 'sf_test_sc' );
        }
    }

    /*
     * PR #213 review: SUPER_Forms::email_if_statements() runs over the e-mail body after the submitted
     * values were substituted, so a visitor-typed `foreach(x):<%x%>endforeach;` (or if/isset block)
     * was evaluated as author syntax: `<%routing%>` became a live `{routing}` that resolved the author
     * tag of that hidden field (a local secret) into the visitor's own confirmation e-mail.
     */

    private function add_routing_and_notes_fields() {
        $elements = $this->elements();
        $elements[] = array( 'group' => 'form_elements', 'tag' => 'hidden', 'data' => array( 'name' => 'routing', 'value' => '{@sales_email}', 'email' => 'Routing' ) );
        $elements[] = array( 'group' => 'form_elements', 'tag' => 'textarea', 'data' => array( 'name' => 'notes', 'email' => 'Notes' ) );
        update_post_meta( $this->form_id, '_super_elements', $elements );
    }

    /**
     * The sequence SUPER_Ajax::submit_form() uses for the confirmation e-mail body, including the
     * `super_before_sending_confirm_body_filter` that runs email_if_statements().
     */
    private function confirm_body( $template, $data, $settings ) {
        $loops = SUPER_Common::retrieve_email_loop_html( array( 'data' => $data, 'settings' => $settings, 'exclude' => array() ) );
        $body = str_replace( '{loop_fields}', $loops['confirm_loop'], $template );
        $literalValues = array();
        $body = SUPER_Common::email_tags( $body, $data, $settings, null, true, false, false, $literalValues );
        $body = nl2br( $body );
        $body = do_shortcode( $body );
        $body = SUPER_Common::restore_literal_tag_values( $body, $literalValues, false, true );
        $body = apply_filters( 'super_before_sending_confirm_body_filter', $body, array( 'settings' => $settings, 'confirm_loop' => $loops['confirm_loop'], 'data' => $data ) );
        return SUPER_Common::restore_submitted_control_syntax( $body );
    }

    public function test_visitor_typed_foreach_in_html_field_never_reveals_author_secret_in_confirmation() {
        $this->add_routing_and_notes_fields();
        foreach( array( 'foreach(x):<%x%>endforeach;', 'foreach(routing):<%routing%>endforeach;', 'foreach( routing ):<%routing%> <%routing;label%>endforeach;' ) as $typed ) {
            $data = $this->data( array( 'first_name' => 'Jane', 'routing' => '{@sales_email}', 'notes' => $typed ) );
            $data['notes']['type'] = 'html';
            $data['routing']['exclude'] = 1; // kept out of the confirmation e-mail by the author
            $body = $this->confirm_body( '<p>Thanks {first_name}</p><table>{loop_fields}</table>', $data, $this->settings );
            $this->assertStringNotContainsString( self::SALES_EMAIL, $body, $typed );
            $this->assertStringContainsString( '<td>' . $typed . '</td>', html_entity_decode( $body, ENT_QUOTES ), $typed );
            $this->assertStringNotContainsString( "\x1A", $body, $typed );
        }
    }

    public function test_visitor_typed_if_and_isset_statements_stay_literal() {
        $this->add_routing_and_notes_fields();
        foreach( array( 'isset(routing):ROUTED endif;', '!isset(nope):MISSING endif;', 'if(1==1):SHOWN elseif:HIDDEN endif;', 'if(a==b):' ) as $typed ) {
            $data = $this->data( array( 'first_name' => $typed, 'routing' => '{@sales_email}' ) );
            $this->assertSame( '<p>' . $typed . '</p><p>after</p>', $this->confirm_body( '<p>{first_name}</p><p>after</p>', $data, $this->settings ), $typed );
        }
        // A visitor `if(` can not take over the author's own if/endif.
        $data = $this->data( array( 'first_name' => 'if(x==y):', 'routing' => 'r' ) );
        $this->assertSame( '<p>if(x==y):</p>YES ', $this->confirm_body( '<p>{first_name}</p>if({routing}==r):YES elseif:NO endif;', $data, $this->settings ) );
        // Every other email_tags() caller (subject, stored entry, redirect) gets the characters back right away.
        $data = $this->data( array( 'first_name' => 'if(a):b endif; isset(x):y endif;' ) );
        $this->assertSame( 'S: if(a):b endif; isset(x):y endif;', SUPER_Common::email_tags( 'S: {first_name}', $data, $this->settings ) );
    }

    public function test_author_foreach_if_and_isset_keep_working() {
        $this->add_routing_and_notes_fields();
        $data = $this->data( array( 'first_name' => 'Jane', 'routing' => '{@sales_email}' ) );
        $data['first_name_2'] = array( 'name' => 'first_name_2', 'value' => 'Bob', 'label' => 'First_name', 'type' => 'var' );
        $this->assertSame( '1. Jane (Sec G Blog)<br />2. Bob (Sec G Blog)<br />', $this->confirm_body( 'foreach(first_name):<%counter%>. <%first_name%> ({option_blogname})<br />endforeach;', $data, $this->settings ) );
        // The author's own foreach over the hidden field resolves its author tag, as documented.
        $this->assertSame( 'R:' . self::SALES_EMAIL . ';', $this->confirm_body( 'foreach(routing):R:<%routing%>;endforeach;', $data, $this->settings ) );
        $this->assertSame( 'HELLO JANE ', $this->confirm_body( 'if({first_name}==Jane):HELLO JANE elseif:OTHER endif;', $data, $this->settings ) );
        $this->assertSame( 'OTHER ', $this->confirm_body( 'if({first_name}==Bob):HELLO BOB elseif:OTHER endif;', $data, $this->settings ) );
        $this->assertSame( 'HAS ROUTING ', $this->confirm_body( 'isset(routing):HAS ROUTING endif;', $data, $this->settings ) );
        $this->assertSame( 'NO NOPE ', $this->confirm_body( '!isset(nope):NO NOPE endif;', $data, $this->settings ) );
        // Visitor syntax inside a value used in an author foreach row stays literal.
        $data['first_name_2']['value'] = 'isset(routing):EVAL endif;';
        $this->assertSame( '1. Jane|2. isset(routing):EVAL endif;|', $this->confirm_body( 'foreach(first_name):<%counter%>. <%first_name%>|endforeach;', $data, $this->settings ) );
    }

    public function test_full_submission_email_and_success_message_keep_visitor_if_and_foreach_literal() {
        wp_set_current_user( 0 );
        $GLOBALS['post'] = null;
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array(
            array( 'group' => 'form_elements', 'tag' => 'text', 'data' => array( 'name' => 'note', 'email' => 'Note' ) ),
            array( 'group' => 'form_elements', 'tag' => 'textarea', 'data' => array( 'name' => 'notes', 'email' => 'Notes' ) ),
            array( 'group' => 'form_elements', 'tag' => 'hidden', 'data' => array( 'name' => 'routing', 'value' => '{@sales_email}', 'email' => 'Routing' ) ),
        ), array(
            'save_contact_entry' => 'no',
            'send' => 'yes',
            'confirm' => 'no',
            'header_to' => 'sec-g-recipient@example.test',
            'header_from_type' => 'default',
            'header_subject' => 'Subject',
            'email_body_open' => '',
            'email_body' => '<p>{note}</p><table>{loop_fields}</table>',
            'email_body_close' => '',
            'email_body_nl2br' => 'false',
            'email_loop' => '<tr><th>{loop_label}</th><td>{loop_value}</td></tr>',
            'email_exclude_empty' => '',
            'form_thanks_title' => '',
            'form_thanks_description' => 'Thanks {note}',
            'form_show_thanks_msg' => 'true',
            'form_redirect_option' => '',
        ) );
        update_post_meta( $form_id, '_super_local_secrets', array( array( 'name' => 'sales_email', 'value' => self::SALES_EMAIL ) ) );
        $typed = 'isset(note):EVALUATED endif;';
        $loop_typed = 'foreach(routing):<%routing%>endforeach;';
        $this->set_submit_request( $form_id, array(
            'note' => array( 'name' => 'note', 'value' => $typed, 'type' => 'var' ),
            'notes' => array( 'name' => 'notes', 'value' => $loop_typed, 'type' => 'html' ),
            'routing' => array( 'name' => 'routing', 'value' => '{@sales_email}', 'type' => 'var' ),
        ) );
        $mail_log = tempnam( sys_get_temp_dir(), 'sf-sec-g-mail-' );
        $capture = static function( $short_circuit, $atts ) use ( $mail_log ) {
            file_put_contents( $mail_log, wp_json_encode( array( 'message' => $atts['message'] ) ) . "\n", FILE_APPEND | LOCK_EX );
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
        $this->assertStringContainsString( 'Thanks ' . $typed, $decoded['msg'] );

        $lines = array_filter( explode( "\n", (string) file_get_contents( $mail_log ) ) );
        unlink( $mail_log );
        $this->assertCount( 1, $lines, 'admin e-mail was not captured' );
        $mail = json_decode( reset( $lines ), true );
        $this->assertStringContainsString( '<p>' . $typed . '</p>', $mail['message'] );
        $this->assertStringContainsString( $loop_typed, html_entity_decode( $mail['message'], ENT_QUOTES ) );
        // At most the routing row itself carries the author secret, the typed foreach adds no copy of it.
        $this->assertLessThanOrEqual( 1, substr_count( $mail['message'], self::SALES_EMAIL ), $mail['message'] );
    }

    /*
     * The Register & Login activation e-mail runs email_tags() twice over the same message (submitted data,
     * then the new user). The second pass must not resolve a tag the visitor typed in the first.
     */

    public function test_register_login_activation_email_keeps_visitor_tags_literal() {
        if( !class_exists( 'SUPER_Register_Login' ) ) {
            require_once dirname( __DIR__ ) . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
        wp_set_current_user( 0 );
        $new_user_id = self::factory()->user->create( array( 'role' => 'subscriber', 'user_login' => 'secgnewbie', 'user_email' => 'secg-newbie@example.test', 'user_url' => 'https://newbie.example' ) );
        $new_user = get_userdata( $new_user_id );
        $settings = array_merge( $this->settings, array(
            'register_activation_subject' => 'Activate',
            'register_activation_email' => 'Hi {first_name}, code {register_activation_code}, login {register_login_url} as {user_login}, pw {register_generated_password}, site {option_blogname}, url {user_url}, dept {department}',
            'register_login_url' => 'https://example.test/login/',
            'register_custom_email_header' => 'admin',
            'header_from' => 'no-reply@example.test',
            'header_from_name' => 'Sec G',
            'header_reply_enabled' => 'false',
            'header_reply' => '',
            'header_reply_name' => '',
        ) );
        $captured = array();
        $capture = static function( $short_circuit, $atts ) use ( &$captured ) {
            $captured[] = $atts['message'];
            return true;
        };
        add_filter( 'pre_wp_mail', $capture, 10, 2 );
        try {
            foreach( array( '{option_admin_email}', '{@sales_email}', '{option_super_settings;smtp_password}', '{user_url}', '{{option_admin_email}}', 'isset(first_name):X endif;' ) as $typed ) {
                $captured = array();
                $data = $this->data( array( 'first_name' => $typed, 'department' => '{@sales_email}' ) );
                SUPER_Register_Login::send_verification_email( array( 'password' => 'PW-456', 'code' => 'CODE123', 'user' => $new_user, 'settings' => $settings, 'data' => $data ) );
                $this->assertCount( 1, $captured, $typed );
                // The author's tags (activation code, login URL, user login, option, new user's URL, choice-item secret) resolve,
                // the visitor's first name is shown exactly as typed.
                $this->assertStringContainsString(
                    'Hi ' . $typed . ', code CODE123, login https://example.test/login/ as secgnewbie, pw PW-456, site Sec G Blog, url https://newbie.example, dept ' . self::SALES_EMAIL,
                    $captured[0],
                    $typed
                );
                $this->assertStringNotContainsString( self::ADMIN_EMAIL, $captured[0], $typed );
                $this->assertStringNotContainsString( self::SMTP_PASSWORD, $captured[0], $typed );
                $this->assertStringNotContainsString( "\x1A", $captured[0], $typed );
            }
        } finally {
            remove_filter( 'pre_wp_mail', $capture, 10 );
        }
    }

    /*
     * rc3 B2: the activation e-mail inserts the password for {register_generated_password}. When the form has a
     * `user_pass` field that is the password the visitor typed (SUPER_Register_Login::register_user() passes
     * $data['user_pass']['value']); it must arrive byte for byte and never be resolved or evaluated.
     */

    public function test_register_login_activation_email_inserts_visitor_password_literally() {
        if( !class_exists( 'SUPER_Register_Login' ) ) {
            require_once dirname( __DIR__ ) . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
        wp_set_current_user( 0 );
        $elements = $this->elements();
        $elements[] = array( 'group' => 'form_elements', 'tag' => 'password', 'data' => array( 'name' => 'user_pass', 'email' => 'Password' ) );
        update_post_meta( $this->form_id, '_super_elements', $elements );
        $new_user_id = self::factory()->user->create( array( 'role' => 'subscriber', 'user_login' => 'secgpwuser', 'user_email' => 'secg-pw@example.test', 'user_url' => 'https://pw.example' ) );
        $new_user = get_userdata( $new_user_id );
        $settings = array_merge( $this->settings, array(
            'register_activation_subject' => 'Activate',
            'register_activation_email' => 'Your password: [{register_generated_password}] for {first_name}',
            'register_login_url' => 'https://example.test/login/',
            'register_custom_email_header' => 'admin',
            'header_from' => 'no-reply@example.test',
            'header_from_name' => 'Sec G',
            'header_reply_enabled' => 'false',
            'header_reply' => '',
            'header_reply_name' => '',
        ) );
        $captured = array();
        $capture = static function( $short_circuit, $atts ) use ( &$captured ) {
            $captured[] = $atts['message'];
            return true;
        };
        add_filter( 'pre_wp_mail', $capture, 10, 2 );
        try {
            foreach( array( '{@sales_email}|{option_admin_email}', 'foreach(first_name):<%first_name%>endforeach;', 'p[x]%{user_url}%>if(1==1):y endif;{@global_secret}' ) as $typed ) {
                $captured = array();
                $data = $this->data( array( 'first_name' => 'Jane', 'user_pass' => $typed ) );
                // Same call SUPER_Register_Login::register_user() makes after creating the user.
                SUPER_Register_Login::send_verification_email( array( 'password' => $typed, 'code' => 'CODE123', 'user' => $new_user, 'settings' => $settings, 'data' => $data ) );
                $this->assertCount( 1, $captured, $typed );
                $this->assertStringContainsString( 'Your password: [' . $typed . '] for Jane', $captured[0], $typed );
                $this->assertStringNotContainsString( self::ADMIN_EMAIL, $captured[0], $typed );
                $this->assertStringNotContainsString( self::SALES_EMAIL, $captured[0], $typed );
                $this->assertStringNotContainsString( self::GLOBAL_SECRET, $captured[0], $typed );
                $this->assertStringNotContainsString( 'https://pw.example', $captured[0], $typed );
                $this->assertStringNotContainsString( "\x1A", $captured[0], $typed );
            }
        } finally {
            remove_filter( 'pre_wp_mail', $capture, 10 );
        }
    }

    /*
     * PR #213 review (#209 territory): {user_meta_*} and {author_meta_*} return meta data the user
     * controls (any subscriber can set their profile description), so the contents are inserted
     * literally: no nested tag resolution, no shortcode execution, brackets escaped on the
     * default-value render path.
     */

    public function test_user_and_author_meta_contents_stay_literal() {
        $this->register_test_shortcode();
        try {
            $user_id = get_current_user_id();
            $author_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
            foreach( array( '[sf_test_sc]', '{option_admin_email}', '{option_super_settings;smtp_password} [sf_test_sc a="1"]', 'x"] [sf_test_sc] {user_meta_sec_g_meta}' ) as $typed ) {
                update_user_meta( $user_id, 'description', $typed );
                update_user_meta( $author_id, 'description', $typed );
                $atts = array( 'name' => 'first_name', 'value' => '{user_meta_description}' );
                // The rendered form HTML is passed through do_shortcode() once more at the end of super_form_func().
                $page = do_shortcode( SUPER_Shortcodes::get_default_value( 'text', $atts, $this->settings, null ) );
                $this->assertStringNotContainsString( self::SC_MARKER, $page, $typed );
                $this->assertSame( $typed, html_entity_decode( $page, ENT_QUOTES ), $typed );
                $this->assertNoSecret( $page );
                // E-mail body sequence.
                $body = $this->email_body( '{user_meta_description}', $this->data( array() ), $this->settings, false );
                $this->assertSame( $typed, $body, $typed );
                // The author of a profile page is chosen by the request.
                $_GET = array( 'author' => (string) $author_id );
                $page = do_shortcode( SUPER_Shortcodes::get_default_value( 'text', array( 'name' => 'first_name', 'value' => '{author_meta_description}' ), $this->settings, null ) );
                $_GET = array();
                $this->assertStringNotContainsString( self::SC_MARKER, $page, $typed );
                $this->assertSame( $typed, html_entity_decode( $page, ENT_QUOTES ), $typed );
            }
            // Normal meta values still appear.
            update_user_meta( $user_id, 'description', 'I like forms & tea' );
            $this->assertSame( 'I like forms & tea', html_entity_decode( SUPER_Shortcodes::get_default_value( 'text', array( 'name' => 'first_name', 'value' => '{user_meta_description}' ), $this->settings, null ), ENT_QUOTES ) );
            $this->assertSame( self::USER_META, SUPER_Common::email_tags( '{user_meta_sec_g_meta}' ) );
            $this->assertSame( self::USER_META, $this->email_body( '{user_meta_sec_g_meta}', $this->data( array() ), $this->settings, false ) );
        } finally {
            $_GET = array();
            remove_shortcode( 'sf_test_sc' );
        }
    }
}
