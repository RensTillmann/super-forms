<?php
/**
 * Mailchimp / Mailster / MailPoet field mapping: a mapping value that is not a plain field name is run through
 * SUPER_Common::email_tags() (MailPoet: the whole mapping) and the result through unserialize(), so a
 * visitor-supplied serialized object reached unserialize(). SUPER_Common::unserialize_without_objects() never
 * instantiates objects and treats a result holding one as "not serialized" (the plain string is used),
 * while serialized arrays and plain text behave exactly as before.
 *
 * @package Super_Forms_Tests
 */

if( !class_exists( 'Super_Forms_Newsletter_Unserialize_Probe' ) ) {
    class Super_Forms_Newsletter_Unserialize_Probe {
        public static $woken = 0;
        public $x;
        public function __wakeup() { self::$woken++; }
        public function __destruct() { self::$woken++; }
    }
}

class Test_Security_Newsletter_Unserialize extends WP_UnitTestCase {

    private function addon_sources() {
        $root = dirname( __DIR__ ) . '/add-ons/';
        return array(
            'mailchimp' => file_get_contents( $root . 'super-forms-mailchimp/super-forms-mailchimp.php' ),
            'mailster' => file_get_contents( $root . 'super-forms-mailster/super-forms-mailster.php' ),
            'mailpoet' => file_get_contents( $root . 'super-forms-mailpoet/super-forms-mailpoet.php' ),
        );
    }

    /**
     * The add-on statement: `$unserialize = <call>($string); if ($unserialize !== false) value else $string`.
     */
    private function mapped_value( $string ) {
        $unserialize = SUPER_Common::unserialize_without_objects( $string );
        return ( $unserialize !== false ? $unserialize : $string );
    }

    public function test_addons_use_the_hardened_unserialize() {
        foreach( $this->addon_sources() as $addon => $source ) {
            if ( $addon === 'mailchimp' && strpos( $source, 'self::safe_mailchimp_merge_value( $string )' ) !== false ) {
                // 6.3.318 (#208) already hardened Mailchimp with its own allowed_classes/shape check, which the merge keeps.
                $this->assertStringContainsString( "'allowed_classes' => false", $source, $addon );
            } else {
                $this->assertStringContainsString( '$unserialize = SUPER_Common::unserialize_without_objects($string);', $source, $addon );
            }
            $this->assertSame( 0, preg_match( '/(?<![\w>:])unserialize\s*\(\s*\$string\s*\)/', $source ), $addon . ' still calls unserialize( $string ) directly' );
        }
    }

    public function test_serialized_objects_are_never_instantiated() {
        Super_Forms_Newsletter_Unserialize_Probe::$woken = 0;
        $probe = 'O:40:"Super_Forms_Newsletter_Unserialize_Probe":1:{s:1:"x";s:1:"y";}';
        $this->assertSame( strlen( 'Super_Forms_Newsletter_Unserialize_Probe' ), 40 );
        foreach( array( $probe, 'O:8:"stdClass":1:{s:1:"a";s:1:"b";}', 'a:1:{i:0;' . $probe . '}', 'a:1:{s:1:"k";a:1:{i:0;O:8:"stdClass":0:{}}}' ) as $string ) {
            $this->assertFalse( SUPER_Common::unserialize_without_objects( $string ), $string );
            $this->assertSame( $string, $this->mapped_value( $string ), $string );
        }
        $this->assertSame( 0, Super_Forms_Newsletter_Unserialize_Probe::$woken );
    }

    public function test_serialized_arrays_scalars_and_plain_text_behave_as_before() {
        $this->assertSame( array( 'red', 'blue' ), $this->mapped_value( serialize( array( 'red', 'blue' ) ) ) );
        $this->assertSame( array( 'a' => array( 'b' => 1 ) ), $this->mapped_value( serialize( array( 'a' => array( 'b' => 1 ) ) ) ) );
        $this->assertSame( 5, $this->mapped_value( 'i:5;' ) );
        $this->assertSame( 'b:0;', $this->mapped_value( 'b:0;' ) );
        $this->assertSame( 'plain {text}', $this->mapped_value( 'plain {text}' ) );
        $this->assertSame( '', $this->mapped_value( '' ) );
        $this->assertFalse( SUPER_Common::unserialize_without_objects( array( 'not', 'a', 'string' ) ) );
    }

    public function test_visitor_value_through_email_tags_is_not_instantiated() {
        Super_Forms_Newsletter_Unserialize_Probe::$woken = 0;
        $payload = 'O:40:"Super_Forms_Newsletter_Unserialize_Probe":0:{}';
        $data = array( 'company' => array( 'name' => 'company', 'value' => $payload, 'type' => 'var' ) );
        // Mapping `MERGE1|{company}`: `{company}` is not a field name, so the add-on resolves it with email_tags().
        $string = SUPER_Common::email_tags( '{company}', $data, array() );
        $this->assertSame( $payload, $this->mapped_value( $string ) );
        $this->assertSame( 0, Super_Forms_Newsletter_Unserialize_Probe::$woken );
    }
}
