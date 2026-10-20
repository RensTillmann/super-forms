<?php
/**
 * collect_required_fields() must mirror the browser: fields inside an invisible column are
 * never validated client-side (common.js has_hidden_parent() includeInvisibleColumns defaults
 * to true; validate_form skips them), so the server must not enroll them as required.
 *
 * @package Super_Forms_Tests
 */

class Test_Super_Forms_Required_Fields_Invisible_Column_Security extends WP_UnitTestCase {
    public static function set_up_before_class() {
        parent::set_up_before_class();
        if( !class_exists( 'SUPER_Ajax' ) ) {
            require_once dirname( __DIR__ ) . '/includes/class-ajax.php';
        }
    }

    private function collect( $elements ) {
        $method = new ReflectionMethod( 'SUPER_Ajax', 'collect_required_fields' );
        $method->setAccessible( true );
        return $method->invoke( null, $elements );
    }

    private function field( $data = array(), $tag = 'text' ) {
        return array( 'tag' => $tag, 'data' => array_merge( array( 'name' => 'amount', 'validation' => 'numeric' ), $data ) );
    }

    private function column( $inner, $invisible ) {
        return array( 'tag' => 'column', 'data' => array( 'size' => '1/1', 'invisible' => $invisible ), 'inner' => $inner );
    }

    public function test_validated_field_in_invisible_column_is_not_required() {
        $this->assertArrayNotHasKey( 'amount', $this->collect( array( $this->column( array( $this->field() ), 'true' ) ) ) );
        $this->assertArrayNotHasKey( 'amount', $this->collect( array( $this->column( array( $this->field( array( 'validation' => 'email' ) ) ), 'true' ) ) ) );
    }

    public function test_validated_field_in_visible_column_is_required() {
        $required = $this->collect( array( $this->column( array( $this->field() ), '' ) ) );
        $this->assertArrayHasKey( 'amount', $required );
        $this->assertTrue( $required['amount']['always_present'] );
    }

    public function test_visible_column_nested_in_invisible_column_is_not_required() {
        $elements = array( $this->column( array( $this->column( array( $this->field() ), '' ) ), 'true' ) );
        $this->assertArrayNotHasKey( 'amount', $this->collect( $elements ) );
    }

    public function test_hidden_field_is_not_required() {
        $this->assertArrayNotHasKey( 'amount', $this->collect( array( $this->field( array( 'validation' => 'none' ), 'hidden' ) ) ) );
        $this->assertArrayNotHasKey( 'amount', $this->collect( array( $this->column( array( $this->field( array(), 'hidden' ) ), '' ) ) ) );
    }

    public function test_may_be_empty_settings_are_unchanged() {
        foreach( array( 'true', 'conditions' ) as $may_be_empty ) {
            $this->assertArrayNotHasKey( 'amount', $this->collect( array( $this->column( array( $this->field( array( 'may_be_empty' => $may_be_empty ) ) ), '' ) ) ), $may_be_empty );
        }
        $required = $this->collect( array( $this->column( array( $this->field( array( 'may_be_empty' => 'false' ) ) ), '' ) ) );
        $this->assertTrue( $required['amount']['always_present'] );
    }
}
