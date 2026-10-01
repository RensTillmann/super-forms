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
                'value' => '2026-09-27T14:05',
            ),
        ) );
        $atts = SUPER_Ajax::submit_form_checks( false );
        $this->assertSame( '2026-09-27T14:05', $atts['data']['appointment']['value'] );
        $this->assertSame( '2026-09-27T14:05', $seen['appointment']['value'] );
        $this->assertArrayNotHasKey( 'timestamp', $atts['data']['appointment'] );
    }

    public function test_datetime_local_timestamp_carrier_is_not_in_lts_browser_contract() {
        $elements = array( array( 'tag' => 'text', 'data' => array(
            'name' => 'appointment', 'type' => 'datetime-local',
        ) ) );
        $data = array( 'appointment' => array(
            'name' => 'appointment', 'type' => 'var',
            'value' => '2026-09-27T14:05', 'timestamp' => '1',
        ) );
        $this->assertFalse( $this->invoke_ajax_private(
            'submission_data_matches_contract',
            array( $data, $elements, 41, '', '' )
        ) );
    }
}
