<?php
/** Public Mailchimp merge tags may contain legacy serialized scalars or arrays, never objects. */
class Test_Security_Mailchimp_Merge extends WP_UnitTestCase {
    public static function set_up_before_class() {
        parent::set_up_before_class();
        if( !class_exists('SUPER_Mailchimp') ) {
            require_once SUPER_PLUGIN_DIR . '/add-ons/super-forms-mailchimp/super-forms-mailchimp.php';
        }
    }

    private function decode( $value ) {
        $method = new ReflectionMethod( 'SUPER_Mailchimp', 'safe_mailchimp_merge_value' );
        $method->setAccessible( true );
        return $method->invoke( null, $value );
    }

    public function test_legacy_scalar_and_array_values_remain_usable() {
        $this->assertSame( 'plain', $this->decode('plain') );
        $this->assertSame( 42, $this->decode('i:42;') );
        $this->assertFalse( $this->decode(serialize(false)) );
        $this->assertTrue( $this->decode(serialize(true)) );
        $this->assertSame( 'b:invalid;', $this->decode('b:invalid;') );
        $this->assertSame( array('name'=>'Ada'), $this->decode(serialize(array('name'=>'Ada'))) );
    }

    public function test_serialized_objects_and_nested_objects_remain_inert_strings() {
        $object = serialize((object)array('name'=>'Ada'));
        $nested = serialize(array('name'=>(object)array('value'=>'Ada')));
        $this->assertSame( $object, $this->decode($object) );
        $this->assertSame( $nested, $this->decode($nested) );
    }
}
