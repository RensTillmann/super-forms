<?php
require_once __DIR__ . '/test-security-upload-00-base.php';
class Test_Security_Search_Render extends Super_Forms_Upload_Security_Test_Case {
    public static function search_values() {
        return array(array('a'), array('valid lookup title'), array(str_repeat('z', 201)), array('valid lookup title', 'csrf_check', 'false'), array('valid lookup title', 'allow_storing_cookies', '0'));
    }

    /** @dataProvider search_values */
    public function test_prefilled_search_does_not_read_entry_data_during_render( $value, $setting=null, $setting_value=null ) {
        $original_get = $_GET;
        if( $setting!==null ) {
            $settings = array($setting=>$setting_value);
            update_option('super_settings', $settings);
            SUPER_Forms()->global_settings = $settings;
            unset($_COOKIE['_sfs_id']);
        } elseif( method_exists($this, 'bootstrap_shared_anonymous_session') ) $this->bootstrap_shared_anonymous_session();
        $elements = array(
            array('tag'=>'text', 'group'=>'form_elements', 'data'=>array('name'=>'lookup_title', 'enable_search'=>'true', 'search_method'=>'equals'), 'inner'=>array()),
            array('tag'=>'text', 'group'=>'form_elements', 'data'=>array('name'=>'entry_secret'), 'inner'=>array()),
        );
        $form = $this->create_form('publish', $elements);
        $entry = self::factory()->post->create(array('post_type'=>'super_contact_entry', 'post_status'=>'super_read', 'post_parent'=>$form, 'post_title'=>$value));
        $secret = 'render-secret-' . wp_generate_uuid4();
        SUPER_Data_Access::update_entry_data($entry, array('entry_secret'=>array('name'=>'entry_secret', 'value'=>$secret, 'type'=>'text')));
        $lookups = array();
        $filter = static function($query) use (&$lookups) {
            if( strpos($query, 'post_title = BINARY')!==false || strpos($query, 'post_title LIKE BINARY')!==false ) $lookups[] = $query;
            return $query;
        };
        add_filter('query', $filter);
        try {
            $_GET = array('lookup_title'=>$value);
            $html = SUPER_Shortcodes::super_form_func(array('id'=>(string)$form));
            $this->assertStringContainsString('data-search="true"', $html);
            $this->assertStringNotContainsString($secret, $html, 'Initial HTML must not disclose an entry from a request-derived search value.');
            $this->assertArrayNotHasKey('entry_secret', $_GET);
            $this->assertArrayNotHasKey('hidden_contact_entry_id', $_GET);
            $this->assertSame(array(), $lookups, 'Search must run through the guarded populate endpoint.');
            $this->assertSame(1, preg_match('/data-search-capability="([a-f0-9]{64})"/', $html, $matches));
            $_POST = array('form_id'=>(string)$form, 'field_name'=>'lookup_title', 'method'=>'equals', 'skip'=>'', 'value'=>$value, 'capability'=>'', 'nonce'=>wp_create_nonce('super_create_nonce_' . $form));
            $_REQUEST = $_POST;
            $denied = $this->run_dying_handler(array('SUPER_Ajax', 'populate_form_data'), false);
            $this->assertSame(0, $denied['status'], $denied['output']);
            $denied_payload = json_decode($denied['output'], true);
            $this->assertIsArray($denied_payload, $denied['output']);
            $this->assertTrue($denied_payload['_super_capability_rejected']);
            $this->assertArrayNotHasKey('entry_secret', $denied_payload);
            $_POST['capability'] = $matches[1];
            $_REQUEST = $_POST;
            $allowed = $this->run_dying_handler(array('SUPER_Ajax', 'populate_form_data'), false);
            $this->assertSame(0, $allowed['status'], $allowed['output']);
            $payload = json_decode($allowed['output'], true);
            $this->assertIsArray($payload, $allowed['output']);
            if( strlen($value)>=3 && strlen($value)<=200 ) {
                $this->assertSame($secret, $payload['entry_secret']['value']);
            } else {
                $this->assertArrayNotHasKey('entry_secret', $payload);
            }

            if( $setting!==null ) $this->assertArrayNotHasKey('_sfs_id', $_COOKIE);
        } finally {
            remove_filter('query', $filter);
            $_GET = $original_get;
        }
    }
}
