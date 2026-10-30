<?php
/**
 * PHP 8 throws a TypeError for arithmetic on a non-numeric string and for a non-numeric string passed to a
 * float parameter of an internal function. Stored settings that PHP 7 tolerated with a warning must not end a
 * form submission on WordPress' "critical error" page (HTTP 500).
 */
class Test_PHP8_Numeric_Settings_Regressions extends Super_Forms_Upload_Security_Test_Case {

    private function smtp_settings( $timeout ) {
        $settings = array(
            'smtp_enabled' => 'enabled',
            'smtp_host' => '127.0.0.1',
            'smtp_auth' => 'disabled',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_secure' => '',
            'smtp_port' => '9', // Nothing listens on the discard port, so the connection is refused at once.
            'smtp_timeout' => $timeout,
            'smtp_keep_alive' => 'disabled',
            'smtp_debug' => '0',
            'smtp_debug_output_mode' => 'error_log',
        );
        if( $timeout===null ) unset( $settings['smtp_timeout'] );
        return $settings;
    }

    private function send_smtp_mail( $timeout ) {
        SUPER_Forms()->global_settings = $this->smtp_settings( $timeout );
        return SUPER_Common::email( 'to@example.com', 'from@example.com', 'Sender', false, '', '', '', '', 'Subject', 'Body', array() );
    }

    public function invalid_smtp_timeouts() {
        return array(
            'empty' => array( '' ),
            'text' => array( 'thirty' ),
            'zero' => array( '0' ),
            'missing' => array( null ),
        );
    }

    /**
     * @dataProvider invalid_smtp_timeouts
     */
    public function test_smtp_mail_with_an_invalid_timeout_uses_the_default_and_fails_softly( $timeout ) {
        $mail = $this->send_smtp_mail( $timeout );
        $this->assertFalse( $mail['result'] );
        $this->assertNotSame( '', $mail['error'] );
        $this->assertSame( 30, $mail['mail']->Timeout );
    }

    public function test_smtp_mail_keeps_a_numeric_timeout() {
        $mail = $this->send_smtp_mail( '12' );
        $this->assertFalse( $mail['result'] );
        $this->assertSame( 12, $mail['mail']->Timeout );
    }

    private function reminder_day( $days ) {
        return gmdate( 'd-m-Y', time() + $days * DAY_IN_SECONDS );
    }

    private function reminder_data( $day ) {
        return array(
            'hidden_form_id' => array( 'name' => 'hidden_form_id', 'value' => '0', 'type' => 'form_id' ),
            'reservation' => array(
                'name' => 'reservation', 'value' => $day, 'type' => 'var',
                'timestamp' => (string) ( strtotime( $day . ' 00:00 UTC' ) * 1000 ),
            ),
        );
    }

    private function insert_reminder( $settings, $day ) {
        $settings = array_merge( array(
            'email_reminder_1_base_date' => '{reservation}',
            'email_reminder_1_date_offset' => '0',
            'email_reminder_1_time_method' => 'fixed',
            'email_reminder_1_time_fixed' => '10:00',
            'email_reminder_1_time_offset' => '0',
        ), $settings );
        SUPER_Email_Reminders::insert_reminder( 1, $settings, $this->reminder_data( $day ) );
        // The add-on stores reminders with the unregistered `queued` status, which WP_Query drops.
        global $wpdb;
        $reminder_id = (int) $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'super_email_reminder' AND post_status = 'queued' ORDER BY ID DESC LIMIT 1" );
        $this->assertGreaterThan( 0, $reminder_id );
        return (int) get_post_meta( $reminder_id, '_super_reminder_timestamp', true );
    }

    public function test_reminder_with_a_text_day_offset_is_queued_on_the_base_date_as_on_php7() {
        $day = $this->reminder_day( 10 );
        $timestamp = $this->insert_reminder( array( 'email_reminder_1_date_offset' => 'one day before' ), $day );
        $this->assertSame( strtotime( $day . ' 10:00' ), $timestamp );
    }

    public function test_reminder_with_a_leading_number_day_offset_keeps_that_number() {
        $day = $this->reminder_day( 10 );
        $timestamp = $this->insert_reminder( array(
            'email_reminder_1_base_date' => '{reservation;timestamp}',
            'email_reminder_1_date_offset' => '-1 day',
        ), $day );
        $this->assertSame( strtotime( $day . ' 10:00' ) - DAY_IN_SECONDS, $timestamp );
    }

    public function test_reminder_with_a_text_hour_offset_is_queued_at_the_submission_time() {
        $day = $this->reminder_day( 10 );
        $timestamp = $this->insert_reminder( array(
            'email_reminder_1_time_method' => 'offset',
            'email_reminder_1_time_offset' => 'two hours',
        ), $day );
        $this->assertSame( gmdate( 'Y-m-d', strtotime( $day ) ), gmdate( 'Y-m-d', $timestamp ) );
    }

    public function test_reminder_with_an_empty_timestamp_base_date_is_rejected_with_a_message() {
        $day = $this->reminder_day( 10 );
        $this->assert_handler_rejected_with( function() use ( $day ) {
            SUPER_Email_Reminders::insert_reminder( 1, array(
                'email_reminder_1_base_date' => '{missing;timestamp}',
                'email_reminder_1_date_offset' => '0',
                'email_reminder_1_time_method' => 'fixed',
                'email_reminder_1_time_fixed' => '10:00',
                'email_reminder_1_time_offset' => '0',
            ), $this->reminder_data( $day ) );
        }, 'can not be used as a reminder date because it is in the past' );
    }

    /**
     * A host that disables PHP's mail() makes PHPMailer throw an Error on PHP 8 that wp_mail() does not catch.
     * An Error thrown from phpmailer_init escapes wp_mail() the same way.
     */
    public function test_wp_mail_error_is_reported_as_a_failed_email_instead_of_a_fatal() {
        SUPER_Forms()->global_settings = array( 'smtp_enabled' => 'disabled' );
        $throw = function() {
            throw new Error( 'Call to undefined function PHPMailer\PHPMailer\mail()' );
        };
        add_action( 'phpmailer_init', $throw );
        try {
            $mail = SUPER_Common::email( 'to@example.com', 'from@example.com', 'Sender', false, '', '', '', '', 'Subject', 'Body', array() );
        } finally {
            remove_action( 'phpmailer_init', $throw );
        }
        $this->assertFalse( $mail['result'] );
        $this->assertStringContainsString( 'Call to undefined function', $mail['error'] );
    }
}
