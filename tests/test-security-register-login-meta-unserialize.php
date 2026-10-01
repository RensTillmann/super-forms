<?php
/**
 * Register & Login custom user meta: SUPER_Register_Login::resolve_custom_meta_value() runs a mapping source
 * that is not a plain field name through SUPER_Common::email_tags() and the result through unserialize().
 * It already used allowed_classes=false but only checked is_array(), so an array holding a nested object
 * (__PHP_Incomplete_Class) was returned and saved to user meta; WordPress serializes it again and
 * unserializes meta without class restrictions on read, which would instantiate the original class.
 * The result must never be an array with an object in it, and must never come back from user meta as one.
 *
 * @package Super_Forms_Tests
 */

if( !class_exists( 'Super_Forms_RL_Meta_Unserialize_Probe' ) ) {
    class Super_Forms_RL_Meta_Unserialize_Probe {
        public static $woken = 0;
        public $x;
        public function __wakeup() { self::$woken++; }
        public function __destruct() { self::$woken++; }
    }
}

class Test_Security_Register_Login_Meta_Unserialize extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();
        if( !class_exists( 'SUPER_Register_Login' ) ) {
            require_once dirname( __DIR__ ) . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
    }

    private function resolve( $source, $data ) {
        $method = new ReflectionMethod( 'SUPER_Register_Login', 'resolve_custom_meta_value' );
        $method->setAccessible( true );
        return $method->invoke( null, $source, $data, array(), 0 );
    }

    private static function contains_object( $value ) {
        if( is_object( $value ) || $value instanceof __PHP_Incomplete_Class ) return true;
        if( is_array( $value ) ) {
            foreach( $value as $item ) {
                if( self::contains_object( $item ) ) return true;
            }
        }
        return false;
    }

    public function test_array_with_a_nested_object_is_not_instantiated_or_stored_as_an_object() {
        $this->assertSame( 37, strlen( 'Super_Forms_RL_Meta_Unserialize_Probe' ) );
        $user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        Super_Forms_RL_Meta_Unserialize_Probe::$woken = 0;
        $payloads = array(
            'a:1:{i:0;O:37:"Super_Forms_RL_Meta_Unserialize_Probe":1:{s:1:"x";s:1:"y";}}',
            'a:1:{s:1:"k";a:1:{i:0;O:37:"Super_Forms_RL_Meta_Unserialize_Probe":0:{}}}',
            'O:37:"Super_Forms_RL_Meta_Unserialize_Probe":0:{}',
            'a:1:{i:0;O:8:"stdClass":0:{}}',
        );
        foreach( $payloads as $payload ) {
            // Mapping source `{company}` is not a field name, so it goes through email_tags(): the visitor's value.
            $data = array( 'company' => array( 'name' => 'company', 'value' => $payload, 'type' => 'var' ) );
            $value = $this->resolve( '{company}', $data );
            $this->assertSame( $payload, $value, $payload );
            $this->assertFalse( self::contains_object( $value ), $payload );
            // Saved and read back the way register_user() / update_user_meta() do.
            update_user_meta( $user_id, 'sec_g_rl_meta', $value );
            wp_cache_flush();
            $stored = get_user_meta( $user_id, 'sec_g_rl_meta', true );
            $this->assertSame( $payload, $stored, $payload );
            $this->assertFalse( self::contains_object( $stored ), $payload );
        }
        $this->assertSame( 0, Super_Forms_RL_Meta_Unserialize_Probe::$woken );
    }

    public function test_arrays_and_plain_strings_behave_as_before() {
        $data = array( 'colors' => array( 'name' => 'colors', 'value' => serialize( array( 'red', 'blue' ) ), 'type' => 'var' ) );
        $this->assertSame( array( 'red', 'blue' ), $this->resolve( '{colors}', $data ) );
        $data = array( 'note' => array( 'name' => 'note', 'value' => 'plain {text}', 'type' => 'var' ) );
        $this->assertSame( 'plain {text}', $this->resolve( '{note}', $data ) );
        // A serialized scalar is kept as the string, as before (only arrays were ever returned).
        $data = array( 'num' => array( 'name' => 'num', 'value' => 'i:5;', 'type' => 'var' ) );
        $this->assertSame( 'i:5;', $this->resolve( '{num}', $data ) );
        // A field name maps straight to the value.
        $data = array( 'first_name' => array( 'name' => 'first_name', 'value' => 'Jane', 'type' => 'var' ) );
        $this->assertSame( 'Jane', $this->resolve( 'first_name', $data ) );
    }
}
