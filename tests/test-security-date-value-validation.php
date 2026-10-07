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

    private function date_element_without_format( $data_overrides ) {
        return array( 'tag' => 'date', 'group' => 'form_elements', 'data' => array_merge(
            array( 'name' => 'appointment' ),
            $data_overrides
        ) );
    }

    /**
     * Alec private 6.3.321 hotfix: a missing/empty saved format (or an empty
     * custom_format) falls back to dd-mm-yy instead of rejecting every submit.
     * Day-less explicit formats (e.g. mm-yy) are NOT covered here; see T4.
     */
    public function test_omitted_or_empty_saved_format_falls_back_to_dmy() {
        $this->configure_csrf( 'false' );
        foreach( array(
            array(),
            array( 'format' => '' ),
            array( 'format' => 'custom', 'custom_format' => '' ),
        ) as $data_overrides ) {
            $form_id = $this->create_form( 'publish', array( $this->date_element_without_format( $data_overrides ) ) );
            $this->set_request( $form_id, array( 'appointment' => array(
                'name' => 'appointment', 'type' => 'var', 'value' => '27-09-2026',
            ) ) );
            $atts = SUPER_Ajax::submit_form_checks( false );
            $this->assertSame( '27-09-2026', $atts['data']['appointment']['value'] );
            $this->assertSame( (string) (gmmktime(0, 0, 0, 9, 27, 2026) * 1000), $atts['data']['appointment']['timestamp'] );
        }
    }
}
