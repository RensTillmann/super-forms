<?php
/**
 * The one-time 6.4 form rewrite (SUPER_Common::get_form_settings(), runs while `_super_version` < 6.4) on
 * data saved by a 2023 beta (6.3.6xx/6.3.7xx).
 *
 * A 2023 beta stores each form trigger as one `_super_trigger-<name>` row with an `active` flag and the e-mail
 * details directly on the action. Before the fix the rewrite added every such row a second time, unchanged,
 * so the form had two copies without `enabled` and 6.4 never ran them: the trigger e-mail stopped.
 * The rewrite also stored every global setting in each form.
 * Forms that already went through that rewrite (6.4.009 to 6.4.011) are repaired once per site by
 * SUPER_Common::repair_2023_beta_duplicate_triggers().
 *
 * @package Super_Forms_Tests
 */

class Test_Super_Forms_Form_Rewrite_2023_Beta_Triggers extends WP_UnitTestCase {

    const OFFICE = 'office@example.test';

    private $form_ids = array();

    public function tear_down() {
        foreach( $this->form_ids as $id ) {
            wp_delete_post( $id, true );
        }
        parent::tear_down();
    }

    /**
     * A trigger exactly as the 6.3.725 builder saves it; $action changes fields of its one send_email action.
     */
    private function beta_trigger( $name, $active, array $action = array() ) {
        return array(
            'active' => $active,
            'event' => 'sf.after.submission',
            'name' => $name,
            'desc' => '',
            'listen_to' => '',
            'listen_to_ids' => '',
            'order' => '1',
            'actions' => array(
                array_merge( array(
                    'action' => 'send_email',
                    'conditionally' => 'false',
                    'f1' => '',
                    'logic' => '',
                    'f2' => '',
                    'to' => self::OFFICE,
                    'from' => 'forms@example.test',
                    'reply_to' => '',
                    'subject' => 'Trigger: request from {first_name}',
                    'body' => "<p>Trigger mail for {first_name}</p>\n<p>{loop_fields}</p>",
                    'cc' => '',
                    'bcc' => '',
                    'content_type' => 'html',
                    'charset' => 'UTF-8',
                    'headers' => '',
                ), $action ),
            ),
        );
    }

    private function create_beta_form( array $triggers ) {
        $id = self::factory()->post->create( array( 'post_type' => 'super_form', 'post_status' => 'publish', 'post_title' => 'Beta form' ) );
        $this->form_ids[] = $id;
        add_post_meta( $id, '_super_version', '6.3.725' );
        add_post_meta( $id, '_super_form_settings', array( 'form_title' => 'Beta form', 'send' => 'no', 'confirm' => 'no' ) );
        add_post_meta( $id, '_super_elements', array() );
        foreach( $triggers as $t ) {
            add_post_meta( $id, '_super_trigger-' . sanitize_title_with_dashes( $t['name'] ), wp_slash( $t ) );
        }
        return $id;
    }

    private function trigger_rows( $form_id ) {
        global $wpdb;
        $rows = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = %d AND meta_key LIKE %s ORDER BY meta_id", $form_id, $wpdb->esc_like( '_super_trigger-' ) . '%' ) );
        return array_map( 'maybe_unserialize', $rows );
    }

    private function submit_trigger_mails( $form_id ) {
        $mails = array();
        $capture = static function( $short_circuit, $atts ) use ( &$mails ) {
            $mails[] = $atts;
            return true;
        };
        add_filter( 'pre_wp_mail', $capture, 10, 2 );
        try {
            SUPER_Common::triggerEvent( 'sf.after.submission', array(
                'form_id' => $form_id,
                'data' => array( 'first_name' => array( 'name' => 'first_name', 'value' => 'value-one', 'label' => 'First name', 'type' => 'var' ) ),
                'settings' => SUPER_Common::get_form_settings( $form_id ),
                'entry_id' => 0,
                'attachments' => array(),
                'post' => array(),
                'i18n' => '',
            ) );
        } finally {
            remove_filter( 'pre_wp_mail', $capture, 10 );
        }
        return $mails;
    }

    public function test_beta_trigger_is_converted_in_place_and_stays_on() {
        $form_id = $this->create_beta_form( array( $this->beta_trigger( 'Notify office', 'true' ) ) );

        SUPER_Common::get_form_settings( $form_id );

        $rows = $this->trigger_rows( $form_id );
        $this->assertCount( 1, $rows, 'the rewrite must replace the trigger row, not add a second one' );
        $this->assertSame( 'true', $rows[0]['enabled'] );
        $this->assertArrayNotHasKey( 'active', $rows[0] );
        $this->assertSame( self::OFFICE, $rows[0]['actions'][0]['data']['to'] );
        $this->assertSame( 'false', $rows[0]['actions'][0]['conditions']['enabled'] );
        $this->assertSame( SUPER_VERSION, get_post_meta( $form_id, '_super_version', true ) );
    }

    public function test_switched_off_beta_trigger_stays_off() {
        $form_id = $this->create_beta_form( array( $this->beta_trigger( 'Paused', 'false' ) ) );

        SUPER_Common::get_form_settings( $form_id );

        $rows = $this->trigger_rows( $form_id );
        $this->assertCount( 1, $rows );
        $this->assertSame( 'false', $rows[0]['enabled'] );
        $this->assertCount( 0, $this->submit_trigger_mails( $form_id ) );
    }

    public function test_converted_trigger_sends_one_mail_per_submission() {
        $form_id = $this->create_beta_form( array( $this->beta_trigger( 'Notify office', 'true' ) ) );
        SUPER_Common::get_form_settings( $form_id );
        SUPER_Common::get_form_settings( $form_id );

        $mails = $this->submit_trigger_mails( $form_id );

        $this->assertCount( 1, $mails );
        $to = is_array( $mails[0]['to'] ) ? implode( ',', $mails[0]['to'] ) : $mails[0]['to'];
        $this->assertSame( self::OFFICE, $to );
        $this->assertSame( 'Trigger: request from value-one', $mails[0]['subject'] );
    }

    public function test_conditional_action_without_operator_still_sends() {
        // The 2023 beta ran a conditional action whose operator was left empty, whatever f1 and f2 held
        $form_id = $this->create_beta_form( array( $this->beta_trigger( 'Notify office', 'true', array( 'conditionally' => 'true', 'logic' => '', 'f1' => '{first_name}', 'f2' => 'value-two' ) ) ) );
        SUPER_Common::get_form_settings( $form_id );

        $this->assertCount( 1, $this->submit_trigger_mails( $form_id ) );
    }

    public function test_backslashes_in_trigger_text_survive_the_rewrite() {
        $body = '<p>Files are in C:\\forms\\{first_name}</p>';
        $form_id = $this->create_beta_form( array( $this->beta_trigger( 'Notify office', 'true', array( 'body' => $body ) ) ) );

        SUPER_Common::get_form_settings( $form_id );

        $rows = $this->trigger_rows( $form_id );
        $this->assertSame( $body, $rows[0]['actions'][0]['data']['body'] );
    }

    public function test_rewrite_does_not_copy_global_settings_into_the_form() {
        $form_id = $this->create_beta_form( array() );
        $global = SUPER_Common::get_global_settings();

        SUPER_Common::get_form_settings( $form_id );

        $stored = get_post_meta( $form_id, '_super_form_settings', true );
        $this->assertSame( 'Beta form', $stored['form_title'] );
        $copied = array();
        foreach( $stored as $key => $value ) {
            if( in_array( $key, array( 'form_title', 'send', 'confirm' ), true ) ) continue;
            if( array_key_exists( $key, $global ) && $global[$key] === $value ) $copied[] = $key;
        }
        $this->assertSame( array(), $copied, 'global settings the rewrite did not change must stay global' );
    }

    /**
     * A form as the 6.4.009 to 6.4.011 rewrite left it: the beta row plus an unchanged copy, version 6.4.011.
     */
    private function create_duplicated_form( array $trigger, array $copy ) {
        $form_id = $this->create_beta_form( array( $trigger ) );
        add_post_meta( $form_id, '_super_trigger-' . sanitize_title_with_dashes( $trigger['name'] ), wp_slash( $copy ) );
        update_post_meta( $form_id, '_super_version', '6.4.011' );
        return $form_id;
    }

    public function test_repair_collapses_duplicated_beta_trigger_into_one_enabled_trigger() {
        $trigger = $this->beta_trigger( 'Notify office', 'true', array( 'body' => '<p>Files are in C:\\forms</p>' ) );
        // The copy 6.4.011 added lost its backslashes on the way in
        $form_id = $this->create_duplicated_form( $trigger, wp_unslash( $trigger ) );

        SUPER_Common::repair_2023_beta_duplicate_triggers();

        $rows = $this->trigger_rows( $form_id );
        $this->assertCount( 1, $rows );
        $this->assertSame( 'true', $rows[0]['enabled'] );
        $this->assertArrayNotHasKey( 'active', $rows[0] );
        $this->assertSame( '<p>Files are in C:\\forms</p>', $rows[0]['actions'][0]['data']['body'] );
        $this->assertCount( 1, $this->submit_trigger_mails( $form_id ) );
    }

    public function test_repair_keeps_a_switched_off_beta_trigger_off() {
        $trigger = $this->beta_trigger( 'Paused', 'false' );
        $form_id = $this->create_duplicated_form( $trigger, $trigger );

        SUPER_Common::repair_2023_beta_duplicate_triggers();

        $rows = $this->trigger_rows( $form_id );
        $this->assertCount( 1, $rows );
        $this->assertSame( 'false', $rows[0]['enabled'] );
    }

    public function test_repair_leaves_rows_without_the_duplication_pattern_alone() {
        $trigger = $this->beta_trigger( 'Notify office', 'true' );
        // The admin already switched one copy on in the 6.4 builder
        $edited = $trigger;
        $edited['enabled'] = 'true';
        $edited_form = $this->create_duplicated_form( $trigger, $edited );
        // Two different triggers that happen to share a row key
        $other = $this->beta_trigger( 'Notify office', 'true', array( 'to' => 'someone@example.test' ) );
        $different_form = $this->create_duplicated_form( $trigger, $other );
        // One beta row, not duplicated
        $single_form = $this->create_beta_form( array( $trigger ) );
        $before = array( $this->trigger_rows( $edited_form ), $this->trigger_rows( $different_form ), $this->trigger_rows( $single_form ) );

        SUPER_Common::repair_2023_beta_duplicate_triggers();

        $this->assertSame( $before, array( $this->trigger_rows( $edited_form ), $this->trigger_rows( $different_form ), $this->trigger_rows( $single_form ) ) );
    }
}
