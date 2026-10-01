<?php
/**
 * Front-end Posting custom meta: the "save it as a string" branch runs unserialize() on the output of
 * SUPER_Common::email_tags(), which can contain submitted values. A visitor-supplied serialized object
 * must never be instantiated (PHP object injection), and must not be stored as an object either
 * (WordPress unserializes meta without class restrictions on read). Serialized arrays keep working.
 *
 * The decision block is taken from the add-on source and executed as is, so the test follows the code.
 *
 * @package Super_Forms_Tests
 */

if( !class_exists( 'Super_Forms_Fep_Unserialize_Probe' ) ) {
    class Super_Forms_Fep_Unserialize_Probe {
        public static $woken = 0;
        public $x;
        public function __wakeup() { self::$woken++; }
        public function __destruct() { self::$woken++; }
    }
}

class Test_Security_Fep_Unserialize extends WP_UnitTestCase {

    private function meta_value_for( $string ) {
        $source = file_get_contents( dirname( __DIR__ ) . '/add-ons/super-forms-front-end-posting/super-forms-front-end-posting.php' );
        $start = strpos( $source, '$unserialize = @unserialize( $string' );
        $this->assertNotFalse( $start, 'the hardened unserialize() call is missing' );
        $if = strpos( $source, 'if ($unserialize !== false && !$has_object) {', $start );
        $this->assertNotFalse( $if, 'the object check is missing' );
        $end = strpos( $source, '}else{', $if );
        $block = substr( $source, $start, $end - $start ) . '}else{ $meta_data[$field[1]][\'value\'] = $string; }';
        $meta_data = array();
        $field = array( 1 => 'meta_key' );
        eval( $block );
        return $meta_data['meta_key']['value'];
    }

    public function test_serialized_objects_are_never_instantiated_and_stored_as_text() {
        Super_Forms_Fep_Unserialize_Probe::$woken = 0;
        $probe = 'O:33:"Super_Forms_Fep_Unserialize_Probe":1:{s:1:"x";s:1:"y";}';
        $this->assertSame( $probe, $this->meta_value_for( $probe ) );
        $std = 'O:8:"stdClass":1:{s:1:"a";s:1:"b";}';
        $this->assertSame( $std, $this->meta_value_for( $std ) );
        $nested = 'a:1:{i:0;O:33:"Super_Forms_Fep_Unserialize_Probe":0:{}}';
        $this->assertSame( $nested, $this->meta_value_for( $nested ) );
        $this->assertSame( 0, Super_Forms_Fep_Unserialize_Probe::$woken );

        // The PHP call itself gives __PHP_Incomplete_Class, never the object.
        $result = @unserialize( $probe, array( 'allowed_classes' => false ) );
        $this->assertInstanceOf( '__PHP_Incomplete_Class', $result );
        $this->assertNotInstanceOf( 'Super_Forms_Fep_Unserialize_Probe', $result );
        unset( $result );
        $this->assertSame( 0, Super_Forms_Fep_Unserialize_Probe::$woken );
    }

    public function test_serialized_arrays_and_plain_text_keep_working() {
        $this->assertSame( array( 'red', 'blue' ), $this->meta_value_for( serialize( array( 'red', 'blue' ) ) ) );
        $this->assertSame( array( 'a' => array( 'b' => 1 ) ), $this->meta_value_for( serialize( array( 'a' => array( 'b' => 1 ) ) ) ) );
        $this->assertSame( 'plain {text}', $this->meta_value_for( 'plain {text}' ) );
        $this->assertSame( '', $this->meta_value_for( '' ) );
    }
}
