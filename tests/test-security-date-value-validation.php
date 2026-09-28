<?php
/** Saved date values are validated even when clients omit derived timestamp metadata. */
class Test_Security_Date_Value_Validation extends Super_Forms_Upload_Security_Test_Case {
    private function date_element() {
        return array( 'tag' => 'date', 'group' => 'form_elements', 'data' => array(
            'name' => 'appointment', 'format' => 'yy-mm-dd',
        ) );
    }

    public function test_invalid_saved_date_is_rejected_with_or_without_timestamp() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array( $this->date_element() ) );
        foreach( array( '2026-02-30', 'not-a-date' ) as $value ) {
            foreach( array( false, true ) as $with_timestamp ) {
                $field = array( 'name' => 'appointment', 'type' => 'var', 'value' => $value );
                if( $with_timestamp ) $field['timestamp'] = '1';
                $this->set_request( $form_id, array( 'appointment' => $field ) );
                $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'submit_form_checks' ), 'Invalid form data.' );
            }
        }
    }

    public function test_valid_date_without_timestamp_and_empty_optional_date_are_accepted() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array( $this->date_element() ) );
        foreach( array( '2026-09-27', '' ) as $value ) {
            $this->set_request( $form_id, array( 'appointment' => array(
                'name' => 'appointment', 'type' => 'var', 'value' => $value,
            ) ) );
            $atts = SUPER_Ajax::submit_form_checks( false );
            $this->assertSame( $value, $atts['data']['appointment']['value'] );
            if( $value!=='' ) {
                $this->assertSame( (string) (gmmktime(0, 0, 0, 9, 27, 2026) * 1000), $atts['data']['appointment']['timestamp'] );
            } else {
                $this->assertArrayNotHasKey( 'timestamp', $atts['data']['appointment'] );
            }
        }
    }

    public function test_invalid_native_datetime_is_rejected_without_timestamp() {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array( array(
            'tag' => 'text', 'group' => 'form_elements', 'data' => array(
                'name' => 'appointment', 'type' => 'datetime-local',
            ),
        ) ) );
        foreach( array( '2026-02-30T14:05', '2026-09-27T24:05', 'not-a-date' ) as $value ) {
            $this->set_request( $form_id, array( 'appointment' => array(
                'name' => 'appointment', 'type' => 'var', 'value' => $value,
            ) ) );
            $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'submit_form_checks' ), 'Invalid form data.' );
        }
    }
}
