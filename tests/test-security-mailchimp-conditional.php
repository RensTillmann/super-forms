<?php
require_once __DIR__ . '/test-security-upload-00-base.php';
class Test_Security_Mailchimp_Conditional extends Super_Forms_Upload_Security_Test_Case {
    public function test_distinct_variant_carriers_remain_inside_their_conditional_columns() {
        if( !class_exists('SUPER_Mailchimp') ) require_once SUPER_PLUGIN_DIR . '/add-ons/super-forms-mailchimp/super-forms-mailchimp.php';
        $settings = array('mailchimp_key'=>'synthetic-test-us1');
        update_option('super_settings', $settings);
        SUPER_Forms()->global_settings = $settings;
        $elements = array();
        foreach( array('first', 'second') as $choice ) {
            $elements[] = array('tag'=>'mailchimp', 'group'=>'form_elements', 'data'=>array(
                'name'=>'mailchimp_' . $choice, 'list_id'=>'list_' . $choice,
                'display_interests'=>'no', 'send_confirmation'=>'no', 'display'=>'vertical',
                'conditional_action'=>'show', 'conditional_trigger'=>'all',
                'conditional_items'=>array(array('field'=>'choice', 'logic'=>'equal', 'value'=>$choice)),
            ));
        }
        $form = $this->create_form('publish', $elements, $settings);
        $html = SUPER_Shortcodes::super_form_func(array('id'=>(string)$form));
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        $carriers = $xpath->query('//input[starts-with(@name,"mailchimp_variant_")]');
        $this->assertSame(2, $carriers->length, $html);
        $names = array();
        foreach( $carriers as $carrier ) {
            $names[] = $carrier->getAttribute('name');
            $columns = $xpath->query('ancestor::div[@data-conditional-action="show" and contains(concat(" ",normalize-space(@class)," ")," super-column ")]', $carrier);
            $this->assertGreaterThan(0, $columns->length, 'Variant carrier must share the conditional column with its configuration.');
        }
        $this->assertNotSame($names[0], $names[1]);
        $resolve = new ReflectionMethod('SUPER_Mailchimp', 'resolve_mailchimp_variant_settings');
        $resolve->setAccessible(true);
        foreach( $names as $i=>$name ) {
            $resolved = $resolve->invoke(null, $form, array($name=>array('value'=>'1')), $settings);
            $this->assertIsArray($resolved);
            $this->assertSame($i===0 ? 'list_first' : 'list_second', $resolved['list_id']);
        }
        $this->assertFalse($resolve->invoke(null, $form, array($names[0]=>array('value'=>'1'), $names[1]=>array('value'=>'1')), $settings));
    }
}
