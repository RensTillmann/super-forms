<?php
/** Exercise the public callback without permitting external Mailchimp requests. */
class Test_Security_Mailchimp_Inputs extends WP_UnitTestCase {
    public function set_up() {
        parent::set_up();
        if ( !class_exists('SUPER_Mailchimp') ) {
            require_once SUPER_PLUGIN_DIR . '/add-ons/super-forms-mailchimp/super-forms-mailchimp.php';
        }
    }

    public function test_malformed_callback_inputs_return_without_http_or_notices() {
        $requests = 0;
        $capture = static function() use (&$requests) {
            ++$requests;
            return new WP_Error('unexpected_http', 'No HTTP expected');
        };
        add_filter('pre_http_request', $capture);
        try {
            $valid = array('settings'=>array(), 'post'=>array('form_id'=>'41', 'data'=>'{}'));
            $cases = array(null, false, 'invalid', array(), array('post'=>'invalid', 'settings'=>array()));
            foreach (array(null, array(), (object)array(), false, 42) as $data) {
                $case = $valid;
                $case['post']['data'] = $data;
                $cases[] = $case;
            }
            foreach (array('{broken', 'null', 'false', '42', '"text"') as $data) {
                $case = $valid;
                $case['post']['data'] = $data;
                $cases[] = $case;
            }
            foreach (array(null, 'invalid', (object)array()) as $settings) {
                $case = $valid;
                $case['settings'] = $settings;
                $cases[] = $case;
            }
            foreach (array(array(41), (object)array(), '41bad', -41, 0) as $id) {
                $case = $valid;
                $case['post']['form_id'] = $id;
                $cases[] = $case;
            }
            foreach ($cases as $case) {
                $this->assertFalse(SUPER_Mailchimp::update_mailchimp_subscribers($case));
            }
            $this->assertSame(0, $requests);
        } finally {
            remove_filter('pre_http_request', $capture);
        }
    }

    public function test_valid_callback_uses_saved_variant_and_ignores_extra_local_variable_names() {
        $element = array('name'=>'newsletter', 'list_id'=>'saved-list', 'display_interests'=>'no',
            'send_confirmation'=>'no', 'subscriber_status'=>'subscribed', 'subscriber_tags'=>'',
            'vip'=>'false', 'custom_fields'=>'');
        $form = self::factory()->post->create(array('post_type'=>'super_form', 'post_status'=>'publish'));
        update_post_meta($form, '_super_elements', wp_slash(wp_json_encode(array(
            array('tag'=>'mailchimp', 'group'=>'form_elements', 'data'=>$element),
        ))));
        $settings = array('mailchimp_key'=>'synthetic-us1');
        update_option('super_settings', $settings);
        $old_settings = SUPER_Forms()->global_settings;
        SUPER_Forms()->global_settings = $settings;
        $variant = new ReflectionMethod('SUPER_Mailchimp', 'mailchimp_variant_id');
        $variant->setAccessible(true);
        $name = 'mailchimp_variant_' . $variant->invoke(null, $element);
        $data = array($name=>array('value'=>'1'), 'email'=>array('value'=>'Person@example.test'));
        $requests = array();
        $capture = static function($pre, $args, $url) use (&$requests) {
            $requests[] = array('url'=>$url, 'args'=>$args);
            return array('headers'=>array(), 'response'=>array('code'=>200, 'message'=>'OK'),
                'body'=>wp_json_encode(array('status'=>'subscribed', 'interests'=>array())));
        };
        add_filter('pre_http_request', $capture, 10, 3);
        try {
            SUPER_Mailchimp::update_mailchimp_subscribers(array(
                'settings'=>$settings, 'post'=>array('form_id'=>(string)$form, 'data'=>wp_slash(wp_json_encode($data))),
                'atts'=>array(), 'data'=>array(), 'resolved'=>array('list_id'=>'forged-list'),
                'api_key'=>'forged-us99', 'list_id'=>'forged-list',
            ));
            $this->assertCount(2, $requests);
            $this->assertSame('GET', $requests[0]['args']['method']);
            $this->assertSame('PATCH', $requests[1]['args']['method']);
            $this->assertStringContainsString('/lists/saved-list/members/', $requests[1]['url']);
            $body = json_decode($requests[1]['args']['body'], true);
            $this->assertSame('person@example.test', $body['email_address']);
            $this->assertSame('subscribed', $body['status']);
            $this->assertFalse($body['vip']);
        } finally {
            remove_filter('pre_http_request', $capture, 10);
            SUPER_Forms()->global_settings = $old_settings;
        }
    }
}
