<?php

class Test_Super_Forms_Submission_State extends Super_Forms_Upload_Security_Test_Case {
    private function submit_review8_probe( $elements, $data, $allowed, $language='', $translations=array() ) {
        global $wpdb;
        $form_id = $this->create_form('publish', $elements, array(
            'save_contact_entry' => 'yes', 'send' => 'no', 'confirm' => 'no',
            'form_thanks_title' => '', 'form_thanks_description' => '',
            'form_show_thanks_msg' => '', 'form_redirect_option' => '',
        ));
        update_post_meta($form_id, '_super_translations', $translations);
        $marker = '_review8_hook_' . $form_id;
        $hook = static function( $value ) use ( $marker ) { update_option($marker, 'reached', false); return $value; };
        add_filter('super_before_sending_email_data_filter', $hook);
        try {
            $result = $this->with_super_settings(array('csrf_check' => 'false'), function() use ($form_id, $data, $language) {
                $this->set_request($form_id, $data, array(), array('action' => 'super_submit_form', 'i18n' => $language));
                // A real WordPress request is slashed before the handler unslashes JSON.
                $_POST['data'] = wp_slash($_POST['data']);
                $_REQUEST = $_POST;
                return $this->run_dying_handler(static function() { SUPER_Ajax::submit_form(); });
            });
        } finally { remove_filter('super_before_sending_email_data_filter', $hook); }
        $this->assertSame(0, $result['status'], $result['output']);
        $decoded = json_decode($result['output'], true);
        $this->assertIsArray($decoded, $result['output']);
        $this->assertSame(!$allowed, $decoded['error'], $result['output'] . wp_json_encode($data));
        if(!$allowed) $this->assertSame('Invalid form data.', $decoded['msg']);
        $this->assertCount($allowed ? 1 : 0, $this->entry_ids_for($form_id));
        $observed = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $marker));
        $this->assertSame($allowed ? 'reached' : null, $observed);
        delete_option($marker);
        return $form_id;
    }

    public function test_translated_dates_use_saved_language_formats_before_submission_effects() {
        $cases = array(
            array('', '27-09-2026', true, '', ''),
            array('en', '09/27/2026', true, '', ''),
            array('de', '3 März 2026', true, '', ''),
            array('en', '02/31/2026', false, '', ''),
            array('en', '27-09-2026', false, '', ''),
            array('unknown', '09/27/2026', false, '', ''),
            array('unknown', '27-09-2026', true, '', ''),
            array(array('en'), '09/27/2026', false, '', ''),
            array('en', '27.09.2026', false, 'dd.mm.yy', 'de'),
        );
        foreach($cases as $case) {
            list($language, $value, $allowed, $client_format, $client_locale) = $case;
            $date = array('tag' => 'date', 'data' => array(
                'name' => 'appointment', 'format' => 'custom', 'custom_format' => 'dd-mm-yy',
                'localization' => '', 'maxPicks' => '1', 'validation' => 'none',
                'i18n' => array(
                    'en' => array('custom_format' => 'mm/dd/yy'),
                    'de' => array('custom_format' => 'd MM yy', 'localization' => 'de'),
                ),
            ));
            // Nest the date to exercise the same traversal used for layout columns.
            $elements = array(array('tag' => 'column', 'data' => array(), 'inner' => array($date)));
            $data = array('appointment' => array('name' => 'appointment', 'type' => 'var', 'value' => $value));
            if($client_format!=='') $data['appointment']['format'] = $client_format;
            if($client_locale!=='') $data['appointment']['localization'] = $client_locale;
            $form_id = $this->submit_review8_probe($elements, $data, $allowed, $language);
            if($allowed) {
                $entries = $this->entry_ids_for($form_id);
                $stored = SUPER_Data_Access::get_entry_data($entries[0]);
                $this->assertSame($value, $stored['appointment']['value']);
                $expected = $language==='de' ? gmmktime(0, 0, 0, 3, 3, 2026) : gmmktime(0, 0, 0, 9, 27, 2026);
                $this->assertSame((string)($expected * 1000), $stored['appointment']['timestamp']);
            }
        }
    }


    private function entry_ids_for( $form_id ) {
        global $wpdb;
        return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d",
            'super_contact_entry',
            absint( $form_id )
        ) ) );
    }
    private function with_super_settings( $settings, $callback ) {
        $missing = '__super_settings_missing__';
        $previous = get_option( 'super_settings', $missing );
        $forms = SUPER_Forms();
        $had_global_settings = isset( $forms->global_settings );
        $previous_global_settings = $had_global_settings ? $forms->global_settings : null;
        update_option( 'super_settings', $settings, false );
        $forms->global_settings = $settings;
        try {
            return call_user_func( $callback );
        } finally {
            if( $previous===$missing ) {
                delete_option( 'super_settings' );
            }else{
                update_option( 'super_settings', $previous, false );
            }
            if( $had_global_settings ) {
                $forms->global_settings = $previous_global_settings;
            }else{
                unset( $forms->global_settings );
            }
        }
    }

    public function test_multi_date_submission_validates_every_pick_and_saved_limit_before_effects() {
        $cases = array(
            array('dd-mm-yy', '', '03-09-2026, 04-09-2026', true),
            array('dd-mm-yy', '', '03-09-2026', true),
            array('dd-mm-yy', '', '', true),
            array('dd-mm-yy', '', 'arbitrary text', false),
            array('dd-mm-yy', '', '03-09-2026, 31-02-2026', false),
            array('dd-mm-yy', '', '03-09-2026, 04-09-2026, 05-09-2026', false),
            array('dd-mm-yy', '', '03-09-2026, 03-09-2026', false),
            array('dd-mm-yy', '', '03-09-2026, ', false),
            array('dd-mm-yy', '', '03-09-2026; 04-09-2026', false),
            array('D, d M yy', '', 'Thu, 3 Sep 2026, Fri, 4 Sep 2026', true),
            array('dd-mm-yy', 'de', '03.03.2026, 04.03.2026', true),
            array('d MM yy', 'de', '3 März 2026, 4 März 2026', true),
        );
        foreach($cases as $case) {
            list($format, $locale, $value, $allowed) = $case;
            $elements = array(array('tag' => 'date', 'data' => array(
                'name' => 'appointment', 'format' => 'custom', 'custom_format' => $format,
                'localization' => $locale, 'maxPicks' => '2', 'validation' => 'none',
            )));
            $data = array('appointment' => array('name' => 'appointment', 'type' => 'var', 'value' => $value, 'timestamp' => 'forged'));
            $form_id = $this->submit_review8_probe($elements, $data, $allowed);
            if($allowed && strpos($value, ', ')!==false) {
                $entries = $this->entry_ids_for($form_id);
                $stored = SUPER_Data_Access::get_entry_data($entries[0]);
                $this->assertSame($value, $stored['appointment']['value']);
                $this->assertArrayNotHasKey('timestamp', $stored['appointment']);
            }
        }
    }

    public function test_nested_repeated_codes_are_reserved_and_mirrored_before_storage() {
        global $wpdb;
        foreach(array(1, 2) as $depth) {
            $prefix = 'NEST' . $depth . '-';
            $invoice_key = 'nestedreview10-' . $depth;
            $counter_key = '_sf_invoice_number_' . $invoice_key;
            update_option($counter_key, '1', false);
            update_option('_sf_unique_code-' . $prefix . '0001', $prefix . '0001', false);
            $code = array('tag' => 'hidden', 'data' => array(
                'name' => 'invoice_code', 'enable_random_code' => 'true', 'code_length' => '0',
                'code_prefix' => $prefix, 'code_invoice' => 'true', 'code_invoice_key' => $invoice_key, 'code_invoice_padding' => '4',
            ));
            $nested = $code;
            for($n=0; $n<$depth; $n++) $nested = array('tag' => 'column', 'data' => array('duplicate' => 'enabled'), 'inner' => array($nested));
            $elements = array(array('tag' => 'column', 'data' => array('duplicate' => 'enabled'), 'inner' => array(
                array('tag' => 'text', 'data' => array('name' => 'guest_name', 'validation' => 'none')), $nested,
            )));
            $data = array('_super_dynamic_data' => array('guest_name' => array()));
            $routes = array();
            for($row=0; $row<2; $row++) {
                $suffix = $row===0 ? '' : '_2';
                $guest = 'guest_name' . $suffix;
                $carriers = array($guest => array('name' => $guest, 'type' => 'var', 'value' => 'Guest ' . $row));
                for($inner=0; $inner<2; $inner++) {
                    $name = 'invoice_code' . str_repeat('[0]', $depth-1) . '[' . $inner . ']' . $suffix;
                    $carriers[$name] = array('name' => $name, 'type' => 'var', 'value' => $prefix . '0001');
                    $routes[$name] = $row;
                }
                $data['_super_dynamic_data']['guest_name'][] = $carriers;
                $data = array_merge($data, $carriers);
            }
            $hook_key = '_nested_code_hook_' . $depth;
            $hook = static function($value) use ($hook_key) { update_option($hook_key, $value, false); return $value; };
            add_filter('super_before_sending_email_data_filter', $hook);
            try { $form_id = $this->submit_review8_probe($elements, $data, true); }
            finally { remove_filter('super_before_sending_email_data_filter', $hook); }
            $entries = $this->entry_ids_for($form_id);
            $stored = SUPER_Data_Access::get_entry_data($entries[0]);
            $seen_by_hook = maybe_unserialize($wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $hook_key)));
            $number = 2;
            foreach($routes as $name => $row) {
                $expected = $prefix . sprintf('%04d', $number++);
                $this->assertSame($expected, $stored[$name]['value']);
                $this->assertSame($expected, $stored['_super_dynamic_data']['guest_name'][$row][$name]['value']);
                $this->assertSame($expected, $seen_by_hook[$name]['value']);
                $this->assertSame($expected, $seen_by_hook['_super_dynamic_data']['guest_name'][$row][$name]['value']);
                $this->assertSame($expected, $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", '_sf_unique_code-' . $expected)));
            }
            $this->assertSame('5', $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $counter_key)));
            delete_option($hook_key);
            // Malformed nested routes must fail before another invoice is reserved.
            $old_name = array_key_first($routes);
            $bad_name = str_replace('[0]', '[bad]', $old_name);
            $bad = $data;
            $bad[$bad_name] = $bad[$old_name];
            $bad[$bad_name]['name'] = $bad_name;
            unset($bad[$old_name]);
            $bad['_super_dynamic_data']['guest_name'][0][$bad_name] = $bad[$bad_name];
            unset($bad['_super_dynamic_data']['guest_name'][0][$old_name]);
            $this->submit_review8_probe($elements, $bad, false);
            $this->assertSame('5', $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $counter_key)));
        }
    }

    public function test_unconditional_generated_codes_cannot_be_omitted_but_dynamic_omissions_remain_valid() {
        global $wpdb;
        $code = array('tag' => 'hidden', 'data' => array(
            'name' => 'invoice_code', 'enable_random_code' => 'true', 'code_length' => '0',
            'code_prefix' => 'REVIEW8-', 'code_invoice' => 'true', 'code_invoice_key' => 'review8', 'code_invoice_padding' => '4',
        ));
        $carrier = array('tag' => 'text', 'data' => array('name' => 'carrier', 'validation' => 'none'));
        $data = array('carrier' => array('name' => 'carrier', 'type' => 'var', 'value' => 'kept'));
        $cases = array(
            array($code, false),
            array(array('tag' => 'multipart', 'inner' => array($code)), false),
            array(array('tag' => 'column', 'data' => array('conditional_action' => 'show'), 'inner' => array($code)), true),
            array(array('tag' => 'column', 'data' => array('hide_on_mobile' => 'true'), 'inner' => array($code)), true),
            array(array('tag' => 'column', 'data' => array('duplicate' => 'enabled'), 'inner' => array($code)), true),
        );
        foreach(array('conditional_action' => 'show', 'hide_on_mobile' => 'true') as $setting => $value) {
            $optional = array('tag' => 'column', 'data' => array($setting => $value), 'inner' => array($code));
            foreach(array(array($code, $optional), array($optional, $code)) as $declarations) {
                $cases[] = array(array('tag' => 'column', 'inner' => $declarations), false);
            }
        }
        foreach($cases as $case) {
            $before = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '_sf_invoice_number_review8'");
            $this->submit_review8_probe(array($carrier, $case[0]), $data, $case[1]);
            $this->assertSame($before, $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '_sf_invoice_number_review8'"));
        }
        $data['invoice_code'] = array('name' => 'invoice_code', 'type' => 'var', 'value' => '');
        $form_id = $this->submit_review8_probe(array($carrier, $code), $data, true);
        $entries = $this->entry_ids_for($form_id);
        $stored = SUPER_Data_Access::get_entry_data($entries[0]);
        $this->assertSame('REVIEW8-0001', $stored['invoice_code']['value']);
        $this->assertSame('1', $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = '_sf_invoice_number_review8'"));
    }

    public function test_cookie_disabled_upload_response_does_not_create_a_session_nonce() {
        global $wpdb;
        $settings = array('allow_storing_cookies' => '0', 'csrf_check' => 'false', 'email_reminder_amount' => 0);
        update_option('super_settings', $settings, false);
        SUPER_Forms()->global_settings = $settings;
        unset($_COOKIE['_sfs_id']);
        $form_id = $this->create_form('publish');
        $before = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_sfsdata_%' ORDER BY option_name");
        $files = array('documents' => array('type' => 'files', 'files' => array(array('upload_token' => str_repeat('a', 64)))));
        // Test the production response boundary directly; CLI cannot create a native HTTP upload.
        $response = $this->invoke_ajax_private('upload_success_response', array($files, $form_id));
        $this->assertSame($files, $response['files']);
        $this->assertSame('', $response['sf_nonce']);
        $this->assertSame($before, $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '_sfsdata_%' ORDER BY option_name"));
    }

    public function test_cookie_disabled_submissions_have_separate_expiring_state() {
        $settings = array('allow_storing_cookies' => '0', 'csrf_check' => 'false', 'email_reminder_amount' => 0);
        update_option('super_settings', $settings, false);
        SUPER_Forms()->global_settings = $settings;
        unset($_COOKIE['_sfs_id']);
        $form_id = self::factory()->post->create(array('post_type' => 'super_form', 'post_status' => 'publish'));
        $first = $second = null;
        try {
            $before = time();
            $first = $this->invoke_ajax_private('submission_trigger_context', array(
                array('marker' => array('value' => 'first')), $form_id, 0, '', $settings, array(), '', false
            ));
            $second = $this->invoke_ajax_private('submission_trigger_context', array(
                array('marker' => array('value' => 'second')), $form_id, 0, '', $settings, array(), '', true
            ));
            $this->assertIsString($first['sfs_uid']);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.[0-9]+$/D', $first['sfs_uid']);
            $this->assertNotSame($first['sfs_uid'], $second['sfs_uid']);
            $this->assertArrayNotHasKey('_sfs_id', $_COOKIE);
            $this->assertFalse(get_option('_sfsi_', false));
            $stored_first = get_option('_sfsi_' . $first['sfs_uid']);
            $stored_second = get_option('_sfsi_' . $second['sfs_uid']);
            $this->assertSame('first', $stored_first['data']['marker']['value']);
            $this->assertSame('second', $stored_second['data']['marker']['value']);
            $expiry = (int) substr(strrchr($first['sfs_uid'], '.'), 1);
            $this->assertGreaterThanOrEqual($before + 30 * MINUTE_IN_SECONDS, $expiry);
            $this->assertLessThanOrEqual(time() + 30 * MINUTE_IN_SECONDS, $expiry);

            $expired_key = '_sfsi_' . SUPER_Forms::generate_secure_hex(16) . '.' . (time() - 1);
            update_option($expired_key, $stored_first, false);
            SUPER_Common::deleteOldClientData();
            global $wpdb;
            $this->assertNull($wpdb->get_var($wpdb->prepare(
                "SELECT option_id FROM $wpdb->options WHERE option_name = %s", $expired_key
            )));
            $this->assertSame('second', get_option('_sfsi_' . $second['sfs_uid'])['data']['marker']['value']);
        } finally {
            foreach (array($first, $second) as $context) {
                if (is_array($context)) delete_option('_sfsi_' . $context['sfs_uid']);
            }
        }
    }
}
