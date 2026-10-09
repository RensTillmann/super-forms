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


    private function default_date_element( $settings=array() ) {
        return array( 'tag' => 'date', 'group' => 'form_elements', 'data' => array_merge(
            array( 'name' => 'appointment' ), $settings
        ) );
    }

    private function assert_default_date_accepted( $settings, $value='13-06-2027', $timestamp='1812844800000', $language='' ) {
        $this->configure_csrf( 'false' );
        $element = $this->default_date_element( $settings );
        $form_id = $this->create_form( 'publish', array( $element ) );
        foreach( array( false, true ) as $with_timestamp ) {
            $field = array( 'name' => 'appointment', 'type' => 'var', 'value' => $value );
            if( $with_timestamp ) $field['timestamp'] = '1';
            // Assert reconstruction first so an unfixed base fails rather than dying inside the public consumer.
            $elements = $this->invoke_ajax_private( 'translated_submission_elements', array( array( $element ), $language ) );
            $rebuilt = $this->invoke_ajax_private( 'rebuild_selection_entry_values', array(
                array( 'appointment' => $field ), $elements
            ) );
            $this->assertIsArray( $rebuilt );
            $this->assertSame( $value, $rebuilt['appointment']['value'] );
            $this->assertSame( $timestamp, $rebuilt['appointment']['timestamp'] );
            $this->set_request( $form_id, array( 'appointment' => $field ), array(), array( 'i18n' => $language ) );
            $atts = SUPER_Ajax::submit_form_checks( false );
            $this->assertSame( $value, $atts['data']['appointment']['value'] );
            $this->assertSame( $timestamp, $atts['data']['appointment']['timestamp'] );
        }
    }

    private function assert_default_date_rejected( $settings, $values, $language='' ) {
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array( $this->default_date_element( $settings ) ) );
        foreach( $values as $value ) {
            foreach( array( false, true ) as $with_timestamp ) {
                $field = array( 'name' => 'appointment', 'type' => 'var', 'value' => $value );
                if( $with_timestamp ) $field['timestamp'] = '1';
                $this->set_request( $form_id, array( 'appointment' => $field ), array(), array( 'i18n' => $language ) );
                $this->assert_handler_rejected_with( array( 'SUPER_Ajax', 'submit_form_checks' ), 'Invalid form data.' );
            }
        }
    }

    public function test_date_defaults_missing_format_reconstructs_the_default_timestamp() {
        $this->assert_default_date_accepted( array() );
    }

    public function test_date_defaults_missing_custom_format_reconstructs_the_default_timestamp() {
        $this->assert_default_date_accepted( array( 'format' => 'custom' ) );
    }

    public function test_date_defaults_empty_patterns_reconstruct_the_default_timestamp() {
        // Server compatibility only: an empty saved custom pattern does not repair the picker UI.
        $this->assert_default_date_accepted( array( 'format' => '' ) );
        $this->assert_default_date_accepted( array( 'format' => 'custom', 'custom_format' => '' ) );
    }

    public function test_date_defaults_rebuilding_replaces_untrusted_derived_timestamps() {
        $elements = array( $this->default_date_element() );
        foreach( array( null, '1', array( 'forged' ) ) as $timestamp ) {
            $field = array( 'name' => 'appointment', 'type' => 'var', 'value' => '13-06-2027' );
            if( $timestamp!==null ) $field['timestamp'] = $timestamp;
            $rebuilt = $this->invoke_ajax_private( 'rebuild_selection_entry_values', array(
                array( 'appointment' => $field ), $elements
            ) );
            $this->assertIsArray( $rebuilt );
            $this->assertSame( '13-06-2027', $rebuilt['appointment']['value'] );
            $this->assertSame( '1812844800000', $rebuilt['appointment']['timestamp'] );
            $field['value'] = '31-02-2027';
            $this->assertFalse( $this->invoke_ajax_private( 'rebuild_selection_entry_values', array(
                array( 'appointment' => $field ), $elements
            ) ) );
        }
    }

    public function test_date_defaults_preserve_strict_calendars_and_explicit_format_precedence() {
        foreach( array(
            array(),
            array( 'format' => 'custom' ),
            array( 'format' => '' ),
            array( 'format' => 'custom', 'custom_format' => '' ),
        ) as $settings ) {
            $this->assert_default_date_rejected( $settings, array( '31-02-2027', 'not-a-date', '13-06-2027 junk' ) );
        }
        foreach( array(
            array( 'format' => 'yy-mm-dd', 'custom_format' => 'dd-mm-yy' ),
            array( 'format' => 'custom', 'custom_format' => 'yy-mm-dd' ),
        ) as $settings ) {
            $this->assert_default_date_accepted( $settings, '2027-06-13' );
            $this->assert_default_date_rejected( $settings, array( '13-06-2027', '2027-02-31' ) );
        }
        foreach( array(
            array( 'format' => 'unsupported' ),
            array( 'format' => 'custom', 'custom_format' => 'unsupported' ),
        ) as $settings ) {
            $this->assert_default_date_rejected( $settings, array( '13-06-2027' ) );
        }
    }

    public function test_date_defaults_preserve_optional_empty_values_and_minimum_picks() {
        $this->configure_csrf( 'false' );
        foreach( array(
            array(),
            array( 'format' => 'custom' ),
            array( 'format' => '' ),
            array( 'format' => 'custom', 'custom_format' => '' ),
        ) as $settings ) {
            $form_id = $this->create_form( 'publish', array( $this->default_date_element( $settings ) ) );
            foreach( array( false, true ) as $with_timestamp ) {
                $field = array( 'name' => 'appointment', 'type' => 'var', 'value' => '' );
                if( $with_timestamp ) $field['timestamp'] = '1';
                $this->set_request( $form_id, array( 'appointment' => $field ) );
                $atts = SUPER_Ajax::submit_form_checks( false );
                $this->assertSame( '', $atts['data']['appointment']['value'] );
                $this->assertArrayNotHasKey( 'timestamp', $atts['data']['appointment'] );
            }
            $settings['minPicks'] = '1';
            $this->assert_default_date_rejected( $settings, array( '' ) );
        }
    }

    public function test_date_defaults_preserve_saved_localization_and_translated_custom_formats() {
        $this->assert_default_date_accepted( array( 'localization' => 'de' ), '10.06.2027', '1812585600000' );
        $this->assert_default_date_rejected( array( 'localization' => 'de' ), array( '31.02.2027' ) );
        $settings = array(
            'format' => 'custom',
            'i18n' => array( 'en' => array( 'custom_format' => 'yy-mm-dd' ) ),
        );
        $this->assert_default_date_accepted( $settings, '2027-06-13', '1812844800000', 'en' );
        $this->assert_default_date_rejected( $settings, array( '13-06-2027', '2027-02-31' ), 'en' );
        $this->assert_default_date_accepted( $settings, '13-06-2027', '1812844800000', 'unknown' );
    }

    public function test_date_defaults_preserve_multiple_pick_validation_and_limits() {
        $this->configure_csrf( 'false' );
        $settings = array( 'minPicks' => '2', 'maxPicks' => '2' );
        $form_id = $this->create_form( 'publish', array( $this->default_date_element( $settings ) ) );
        $value = '13-06-2027, 14-06-2027';
        $rebuilt = $this->invoke_ajax_private( 'rebuild_selection_entry_values', array(
            array( 'appointment' => array(
                'name' => 'appointment', 'type' => 'var', 'value' => $value, 'timestamp' => '1',
            ) ), array( $this->default_date_element( $settings ) )
        ) );
        $this->assertIsArray( $rebuilt );
        $this->assertSame( $value, $rebuilt['appointment']['value'] );
        $this->assertArrayNotHasKey( 'timestamp', $rebuilt['appointment'] );
        $this->set_request( $form_id, array( 'appointment' => array(
            'name' => 'appointment', 'type' => 'var', 'value' => $value, 'timestamp' => '1',
        ) ) );
        $atts = SUPER_Ajax::submit_form_checks( false );
        $this->assertSame( $value, $atts['data']['appointment']['value'] );
        $this->assertArrayNotHasKey( 'timestamp', $atts['data']['appointment'] );
        $this->assert_default_date_rejected( $settings, array(
            '13-06-2027',
            '13-06-2027, 13-06-2027',
            '13-06-2027, 31-02-2027',
            '13-06-2027; 14-06-2027',
            '13-06-2027, ',
            '13-06-2027, 14-06-2027, 15-06-2027',
        ) );
    }

    public function test_date_defaults_omitted_settings_submit_successfully_and_store_one_entry() {
        global $wpdb;
        $this->configure_csrf( 'false' );
        $form_id = $this->create_form( 'publish', array( $this->default_date_element() ), array(
            'save_contact_entry' => 'yes', 'send' => 'no', 'confirm' => 'no',
            'form_thanks_title' => '', 'form_thanks_description' => '',
            'form_show_thanks_msg' => '', 'form_redirect_option' => '',
        ) );
        $marker = '_date_defaults_hook_' . $form_id;
        $hook = static function( $data ) use ( $marker ) {
            update_option( $marker, 'reached', false );
            return $data;
        };
        add_filter( 'super_before_sending_email_data_filter', $hook );
        try {
            $this->set_request( $form_id, array( 'appointment' => array(
                'name' => 'appointment', 'type' => 'var', 'value' => '13-06-2027',
            ) ), array(), array( 'action' => 'super_submit_form', 'i18n' => '' ) );
            $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
            $this->assertTrue( $result['exited'] );
            $this->assertSame( 0, $result['status'], $result['output'] );
            $decoded = json_decode( $result['output'], true );
            $this->assertIsArray( $decoded, $result['output'] );
            $this->assertFalse( $decoded['error'], $result['output'] );
            // Count all stored statuses, not just the WP_Query status whitelist.
            $entries = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d",
                'super_contact_entry', $form_id
            ) );
            $this->assertCount( 1, $entries );
            $stored = SUPER_Data_Access::get_entry_data( (int) $entries[0] );
            $this->assertSame( '13-06-2027', $stored['appointment']['value'] );
            $this->assertSame( '1812844800000', $stored['appointment']['timestamp'] );
            $this->assertSame( 'reached', $wpdb->get_var( $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $marker
            ) ) );
        } finally {
            remove_filter( 'super_before_sending_email_data_filter', $hook );
            delete_option( $marker );
        }
    }
}
