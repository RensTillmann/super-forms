<?php
/**
 * The one-time 6.4 form rewrite (SUPER_Common::get_form_settings(), runs while `_super_version` < 6.4) on
 * triggers saved before 6.4, in both beta shapes:
 * - 2023 betas (6.3.6xx/6.3.7xx): one `_super_trigger-<name>` row with an `active` flag and the e-mail
 *   details directly on the action;
 * - 6.3.742 builds (2024): `enabled`, the e-mail details under `actions[].email`, schedules under `schedule.dates`.
 * Before the fix the rewrite added every such row a second time, unchanged: 2023 triggers then ran in neither
 * copy, 6.3.742 triggers ran in both without their e-mail details. The rewrite also stored every global
 * setting in each form.
 * Forms that already went through that rewrite (6.4.009 to 6.4.011) are repaired once per site by
 * SUPER_Common::repair_pre_64_duplicate_triggers().
 *
 * @package Super_Forms_Tests
 */

class Test_Super_Forms_Form_Rewrite_Pre_64_Triggers extends WP_UnitTestCase {

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

    private function submit_trigger_mails( $form_id, $first_name = 'value-one' ) {
        $mails = array();
        $capture = static function( $short_circuit, $atts ) use ( &$mails ) {
            $mails[] = $atts;
            return true;
        };
        add_filter( 'pre_wp_mail', $capture, 10, 2 );
        try {
            SUPER_Common::triggerEvent( 'sf.after.submission', array(
                'form_id' => $form_id,
                'data' => array( 'first_name' => array( 'name' => 'first_name', 'value' => $first_name, 'label' => 'First name', 'type' => 'var' ) ),
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

        SUPER_Common::repair_pre_64_duplicate_triggers();

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

        SUPER_Common::repair_pre_64_duplicate_triggers();

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

        SUPER_Common::repair_pre_64_duplicate_triggers();

        $this->assertSame( $before, array( $this->trigger_rows( $edited_form ), $this->trigger_rows( $different_form ), $this->trigger_rows( $single_form ) ) );
    }

    /**
     * A trigger exactly as the 6.3.742 builder saves it; $email changes fields of its `email` group.
     */
    private function trigger_742( $name, $enabled, array $email = array() ) {
        return array(
            'enabled' => $enabled,
            'event' => 'sf.after.submission',
            'name' => $name,
            'listen_to' => '',
            'ids' => '',
            'order' => '1',
            'actions' => array(
                array(
                    'action' => 'send_email',
                    'order' => '1',
                    'conditions' => array( 'enabled' => 'false', 'f1' => '', 'logic' => '==', 'f2' => '' ),
                    'email' => array_merge( array(
                        'to' => self::OFFICE,
                        'from_email' => 'forms@example.test',
                        'from_name' => 'Forms',
                        'reply_to' => array( 'enabled' => 'false', 'email' => '', 'name' => '' ),
                        'subject' => 'Trigger 742: request from {first_name}',
                        'body' => "<p>742 trigger mail for {first_name}</p>\n<p>{loop_fields}</p>",
                        'attachments' => '',
                        'loop_open' => '<table>',
                        'loop' => '<tr><th>{loop_label}</th><td>{loop_value}</td></tr>',
                        'loop_close' => '</table>',
                        'exclude_empty' => 'false',
                        'rtl' => 'false',
                        'cc' => '',
                        'bcc' => '',
                        'header_additional' => '',
                        'content_type' => 'html',
                        'charset' => 'UTF-8',
                        'schedule' => array( 'enabled' => 'false', 'dates' => array() ),
                    ), $email ),
                ),
            ),
        );
    }

    public function test_742_trigger_is_converted_in_place_and_keeps_its_email() {
        $form_id = $this->create_beta_form( array( $this->trigger_742( 'Notify office', 'true' ) ) );
        update_post_meta( $form_id, '_super_version', '6.3.742' );

        SUPER_Common::get_form_settings( $form_id );

        $rows = $this->trigger_rows( $form_id );
        $this->assertCount( 1, $rows );
        $this->assertSame( 'true', $rows[0]['enabled'] );
        $this->assertArrayNotHasKey( 'email', $rows[0]['actions'][0] );
        $this->assertSame( "<p>742 trigger mail for {first_name}</p>\n<p>{loop_fields}</p>", $rows[0]['actions'][0]['data']['body'] );
        $this->assertSame( 'Forms', $rows[0]['actions'][0]['data']['from_name'] );
        $mails = $this->submit_trigger_mails( $form_id );
        $this->assertCount( 1, $mails );
        $to = is_array( $mails[0]['to'] ) ? implode( ',', $mails[0]['to'] ) : $mails[0]['to'];
        $this->assertSame( self::OFFICE, $to );
        $this->assertSame( 'Trigger 742: request from value-one', $mails[0]['subject'] );
    }

    public function test_742_reminder_trigger_keeps_its_schedule_and_is_not_added_twice() {
        // 6.3.742 itself turned the flat reminder settings into this trigger on every settings load
        $reminder = $this->trigger_742( 'E-mail reminder #1', 'true', array(
            'to' => 'reminder@example.test',
            'subject' => 'Reminder',
            'schedule' => array( 'enabled' => 'true', 'dates' => array( array( 'date' => '', 'days' => '1', 'method' => 'time', 'time' => '09:00', 'offset' => '0' ) ) ),
        ) );
        $form_id = $this->create_beta_form( array( $reminder ) );
        update_post_meta( $form_id, '_super_version', '6.3.742' );
        $settings = get_post_meta( $form_id, '_super_form_settings', true );
        $settings['email_reminder_1'] = 'true';
        $settings['email_reminder_1_to'] = 'reminder@example.test';
        update_post_meta( $form_id, '_super_form_settings', $settings );

        SUPER_Common::get_form_settings( $form_id );

        $rows = $this->trigger_rows( $form_id );
        $this->assertCount( 1, $rows );
        $schedule = $rows[0]['actions'][0]['data']['schedule'];
        $this->assertSame( 'true', $schedule['enabled'] );
        $this->assertArrayNotHasKey( 'dates', $schedule );
        $this->assertSame( '09:00', $schedule['schedules'][0]['time'] );

        $this->submit_trigger_mails( $form_id );
        $queued = get_posts( array( 'post_type' => 'sf_scheduled_action', 'post_status' => 'queued', 'post_parent' => $form_id, 'numberposts' => -1 ) );
        $this->assertCount( 1, $queued );
        $action = maybe_unserialize( $queued[0]->post_content );
        $this->assertSame( 'reminder@example.test', $action['data']['to'] );
    }

    public function test_repair_collapses_duplicated_742_trigger_into_one_working_trigger() {
        $trigger = $this->trigger_742( 'Notify office', 'true' );
        $form_id = $this->create_duplicated_form( $trigger, $trigger );

        SUPER_Common::repair_pre_64_duplicate_triggers();

        $rows = $this->trigger_rows( $form_id );
        $this->assertCount( 1, $rows );
        $this->assertSame( self::OFFICE, $rows[0]['actions'][0]['data']['to'] );
        $this->assertCount( 1, $this->submit_trigger_mails( $form_id ) );
    }

    /**
     * A trigger condition compares the submitted value of a {tag}, not the tag text.
     */
    public function test_condition_tag_resolves_to_the_submitted_value() {
        $eq = $this->create_beta_form( array( $this->beta_trigger( 'Equal', 'true', array( 'conditionally' => 'true', 'f1' => '{first_name}', 'logic' => '==', 'f2' => 'first_choice' ) ) ) );
        $ne = $this->create_beta_form( array( $this->beta_trigger( 'Not equal', 'true', array( 'conditionally' => 'true', 'f1' => '{first_name}', 'logic' => '!=', 'f2' => 'first_choice' ) ) ) );
        SUPER_Common::get_form_settings( $eq );
        SUPER_Common::get_form_settings( $ne );

        $this->assertCount( 1, $this->submit_trigger_mails( $eq, 'first_choice' ), '== with the matching value sends' );
        $this->assertCount( 0, $this->submit_trigger_mails( $eq, 'second_choice' ), '== with another value does not send' );
        $this->assertCount( 0, $this->submit_trigger_mails( $ne, 'first_choice' ), '!= with the matching value does not send' );
        $this->assertCount( 1, $this->submit_trigger_mails( $ne, 'second_choice' ), '!= with another value sends' );
    }
}
