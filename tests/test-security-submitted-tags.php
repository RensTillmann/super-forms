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
}
