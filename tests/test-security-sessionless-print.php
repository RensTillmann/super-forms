<?php
require_once __DIR__ . '/test-security-upload-00-base.php';
class Test_Security_Sessionless_Print extends Super_Forms_Upload_Security_Test_Case {
    public static function modes() { return array(array('csrf_check', 'false'), array('allow_storing_cookies', '0')); }

    /** @dataProvider modes */
    public function test_rendered_custom_print_works_without_a_session_cookie( $setting, $value ) {
        $settings = array($setting=>$value);
        update_option('super_settings', $settings);
        SUPER_Forms()->global_settings = $settings;
        unset($_COOKIE['_sfs_id']);
        $dirs = wp_upload_dir();
        $this->assertTrue(wp_mkdir_p($dirs['path']));
        $path = trailingslashit($dirs['path']) . 'print-' . wp_generate_uuid4() . '.html';
        $template = '<div>Printable {entry_secret}</div>';
        file_put_contents($path, $template);
        $attachment = wp_insert_attachment(array('post_mime_type'=>'text/html', 'post_status'=>'inherit'), $path);
        $this->attachment_ids[] = $attachment;
        $elements = array(
            array('tag'=>'text', 'group'=>'form_elements', 'data'=>array('name'=>'entry_secret'), 'inner'=>array()),
            array('tag'=>'button', 'group'=>'form_elements', 'data'=>array('action'=>'print', 'name'=>'Print', 'print_custom'=>'true', 'print_file'=>$attachment), 'inner'=>array()),
        );
        $form = $this->create_form('publish', $elements, $settings);
        $html = SUPER_Shortcodes::super_form_func(array('id'=>(string)$form));
        preg_match_all('/<input\b[^>]*>/', $html, $inputs);
        $token = '';
        foreach( $inputs[0] as $input ) {
            if( strpos($input, 'name="print_capability"')!==false && preg_match('/value="([a-f0-9]{64})"/', $input, $matches) ) $token = $matches[1];
        }
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $token);
        $url = wp_get_attachment_url($attachment);
        $fetch = static function($preempt, $args, $requested) use ($url, $template) {
            if( $requested!==$url ) return new WP_Error('unexpected_test_request');
            return array('headers'=>array(), 'body'=>$template, 'response'=>array('code'=>200, 'message'=>'OK'), 'cookies'=>array(), 'filename'=>null);
        };
        add_filter('pre_http_request', $fetch, 10, 3);
        try {
            $_POST = array('action'=>'super_print_custom_html', 'file_id'=>(string)$attachment, 'capability'=>$token, 'nonce'=>wp_create_nonce('super_create_nonce_' . $form), 'data'=>array(
                'entry_secret'=>array('name'=>'entry_secret', 'value'=>'safe print value', 'type'=>'text'),
                'hidden_form_id'=>array('name'=>'hidden_form_id', 'value'=>(string)$form, 'type'=>'form_id'),
            ));
            $_REQUEST = $_POST;
            $printed = $this->run_dying_handler(array('SUPER_Ajax', 'print_custom_html'), false);
            $this->assertSame(0, $printed['status'], $printed['output']);
            $this->assertStringContainsString('Printable safe print value', $printed['output']);
            $replay = $this->run_dying_handler(array('SUPER_Ajax', 'print_custom_html'), false);
            $this->assertSame(0, $replay['status'], $replay['output']);
            $denied = json_decode($replay['output'], true);
            $this->assertIsArray($denied, $replay['output']);
            $this->assertTrue($denied['error']);
            $this->assertArrayNotHasKey('_sfs_id', $_COOKIE);
        } finally {
            remove_filter('pre_http_request', $fetch, 10);
        }
    }
}
