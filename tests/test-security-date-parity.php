<?php
require_once __DIR__ . '/test-security-upload-00-base.php';
class Test_Security_Date_Parity extends Super_Forms_Upload_Security_Test_Case {
    private function submit_review8_probe( $elements, $data, $allowed, $language='' ) {
        global $wpdb;
        $form_id = $this->create_form('publish', $elements, array(
            'save_contact_entry' => 'yes', 'send' => 'no', 'confirm' => 'no',
            'form_thanks_title' => '', 'form_thanks_description' => '',
            'form_show_thanks_msg' => '', 'form_redirect_option' => '',
        ));
        $marker = '_review8_hook_' . $form_id;
        $hook = static function( $value ) use ( $marker ) { update_option($marker, 'reached', false); return $value; };
        add_filter('super_before_sending_email_data_filter', $hook);
        try {
            $this->configure_csrf('false');
            $this->set_request($form_id, $data, array(), array('action'=>'super_submit_form', 'i18n'=>$language));
            $_POST['data'] = wp_slash($_POST['data']);
            $_REQUEST = $_POST;
            $result = $this->run_dying_handler(array('SUPER_Ajax', 'submit_form'));
        } finally { remove_filter('super_before_sending_email_data_filter', $hook); }
        $this->assertSame(0, $result['status'], $result['output']);
        $decoded = json_decode($result['output'], true);
        $this->assertIsArray($decoded, $result['output']);
        $this->assertSame(!$allowed, $decoded['error'], $result['output'] . wp_json_encode($data));
        $this->assertCount($allowed ? 1 : 0, $this->entry_ids_for($form_id));
        $observed = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $marker));
        $this->assertSame($allowed ? 'reached' : null, $observed);
        delete_option($marker);
        return $form_id;
    }

    private function entry_ids_for( $form_id ) {
        global $wpdb;
        return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d",
            'super_contact_entry',
            absint( $form_id )
        ) ) );
    }

    public function test_yearless_custom_dates_submit_with_a_server_owned_current_year() {
        $year = (int)gmdate('Y');
        foreach(array(
            array('dd-mm', '', '03-09', true, 9, 3),
            array('d MM', 'de', '3 März', true, 3, 3),
            array('dd-mm', '', '31-02', false, 2, 31),
            array('dd-mm', '', '29-02', checkdate(2, 29, $year), 2, 29),
            array('dd-mm', '', '03-09-2026', false, 9, 3),
            array('oo', '', '001', true, 1, 1),
            array('mm', '', '09', false, 9, 0),
        ) as $case) {
            list($format, $locale, $value, $allowed, $month, $day) = $case;
            $elements = array(array('tag' => 'date', 'data' => array(
                'name' => 'appointment', 'format' => 'custom', 'custom_format' => $format,
                'localization' => $locale, 'maxPicks' => '1', 'validation' => 'none',
            )));
            $data = array('appointment' => array('name' => 'appointment', 'type' => 'var', 'value' => $value, 'timestamp' => '1'));
            $form_id = $this->submit_review8_probe($elements, $data, $allowed);
            if($allowed) {
                $entries = $this->entry_ids_for($form_id);
                $stored = SUPER_Data_Access::get_entry_data($entries[0]);
                $this->assertSame($value, $stored['appointment']['value']);
                $this->assertSame((string)(gmmktime(0, 0, 0, $month, $day, $year) * 1000), $stored['appointment']['timestamp']);
            }
        }
    }

    public function test_saved_date_minimum_picks_are_enforced_before_submission_effects() {
        foreach(array(
            array('0', '', true),
            array('1', '', false),
            array('1', '03-09-2026', true),
            array('2', '', false),
            array('2', '03-09-2026', false),
            array('2', '03-09-2026, 04-09-2026', true),
            array('2', '03-09-2026, 03-09-2026', false),
            array('2', '03-09-2026, 31-02-2026', false),
        ) as $case) {
            list($minimum, $value, $allowed) = $case;
            $elements = array(array('tag' => 'date', 'data' => array(
                'name' => 'appointment', 'format' => 'custom', 'custom_format' => 'dd-mm-yy',
                'maxPicks' => '2', 'minPicks' => $minimum, 'validation' => 'none',
            )));
            $data = array('appointment' => array('name' => 'appointment', 'type' => 'var', 'value' => $value));
            $this->submit_review8_probe($elements, $data, $allowed);
        }
    }

}
