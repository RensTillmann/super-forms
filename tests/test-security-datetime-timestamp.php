<?php
/** Native date/time submission timestamps must be reconstructed from the saved field and value. */
class Test_Security_Datetime_Timestamp extends Super_Forms_Upload_Security_Test_Case {
    public function test_datetime_local_timestamp_reaches_submission_filters() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array(
            array( 'tag' => 'text', 'group' => 'form_elements', 'data' => array(
                'name' => 'appointment', 'type' => 'datetime-local',
            ) ),
        ) );
        $seen = null;
        $this->add_upload_filter( 'super_before_sending_email_data_filter', static function( $data ) use ( &$seen ) {
            $seen = $data;
            return $data;
        }, 10, 2 );
        $this->set_request( $form_id, array(
            'appointment' => array(
                'name' => 'appointment', 'type' => 'var',
                'value' => '2026-09-27T14:05', 'timestamp' => '1',
            ),
        ) );
        $atts = SUPER_Ajax::submit_form_checks( false );
        $expected = (string) ( gmmktime( 14, 5, 0, 9, 27, 2026 ) * 1000 );
        $this->assertSame( $expected, $atts['data']['appointment']['timestamp'] );
        $this->assertSame( $expected, $seen['appointment']['timestamp'] );
    }

    public function test_datetime_local_rejects_invalid_calendar_time_and_unrelated_types() {
        $element = array( 'tag' => 'text', 'data' => array( 'name' => 'appointment', 'type' => 'datetime-local' ) );
        $this->assertFalse( $this->invoke_ajax_private(
            'server_owned_date_timestamp_for_element',
            array( $element, array( 'value' => '2026-02-30T14:05', 'timestamp' => '1' ) )
        ) );
        $this->assertFalse( $this->invoke_ajax_private(
            'server_owned_date_timestamp_for_element',
            array( $element, array( 'value' => '2026-09-27T24:05', 'timestamp' => '1' ) )
        ) );
        $this->assertSame(
            (string) ( gmmktime( 14, 5, 30, 9, 27, 2026 ) * 1000 + 500 ),
            $this->invoke_ajax_private(
                'server_owned_date_timestamp_for_element',
                array( $element, array( 'value' => '2026-09-27T14:05:30.5', 'timestamp' => '1' ) )
            )
        );
        $this->assertNull( $this->invoke_ajax_private(
            'server_owned_date_timestamp_for_element',
            array( array( 'tag' => 'text', 'data' => array( 'name' => 'plain', 'type' => 'text' ) ),
                array( 'value' => '2026-09-27T14:05', 'timestamp' => '1' ) )
        ) );
    }
    public function test_datetime_local_does_not_apply_datepicker_minimum_picks() {
        $element = array( 'tag' => 'text', 'data' => array(
            'name' => 'appointment', 'type' => 'datetime-local', 'minPicks' => '2',
        ) );
        $this->assertSame( '', $this->invoke_ajax_private(
            'server_owned_date_timestamp_for_element', array( $element, array( 'value' => '' ) )
        ) );
        $this->assertSame(
            (string) ( gmmktime( 14, 5, 0, 9, 27, 2026 ) * 1000 ),
            $this->invoke_ajax_private( 'server_owned_date_timestamp_for_element', array(
                $element, array( 'value' => '2026-09-27T14:05', 'timestamp' => '1' ),
            ) )
        );
    }

}
