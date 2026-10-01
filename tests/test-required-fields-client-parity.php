<?php
/**
 * Test server-side required-field enforcement parity with the front-end.
 *
 * SUPER_Ajax::collect_required_fields() enrolled every stored element whose
 * `may_be_empty` is absent or 'false', including elements the front-end never
 * validates (Hidden fields, fields inside an invisible column). Those are still
 * submitted, so an empty one made the server answer "Please fill in all required
 * fields." for a form the browser had accepted.
 *
 * @package Super_Forms\Tests
 */

require_once 'class-test-helpers.php';

class Test_Required_Fields_Client_Parity extends SUPER_Test_Helpers {

	/**
	 * Call the private SUPER_Ajax::collect_required_fields().
	 *
	 * @param array $elements Stored `_super_elements`.
	 * @return array
	 */
	private function collect( $elements ) {
		if ( ! class_exists( 'SUPER_Ajax' ) ) {
			// The plugin only includes class-ajax.php for AJAX requests.
			require_once dirname( __DIR__ ) . '/src/includes/class-ajax.php';
		}
		$method = new ReflectionMethod( 'SUPER_Ajax', 'collect_required_fields' );
		$method->setAccessible( true );
		return $method->invoke( null, $elements );
	}

	/**
	 * Builder-default text field (the builder strips may_be_empty='false' on save).
	 */
	private function text( $name, $extra = array() ) {
		return array(
			'tag'   => 'text',
			'group' => 'form_elements',
			'data'  => array_merge( array( 'name' => $name, 'email' => $name . ':' ), $extra ),
		);
	}

	public function test_hidden_field_is_not_required() {
		$required = $this->collect(
			array(
				$this->text( 'name' ),
				array(
					'tag'   => 'hidden',
					'group' => 'form_elements',
					'data'  => array( 'name' => 'hidden', 'email' => 'Hidden:' ),
				),
			)
		);
		$this->assertArrayHasKey( 'name', $required );
		$this->assertArrayNotHasKey( 'hidden', $required );
	}

	public function test_hidden_field_nested_in_column_is_not_required() {
		$required = $this->collect(
			array(
				array(
					'tag'   => 'column',
					'group' => 'layout_elements',
					'inner' => array(
						array(
							'tag'   => 'hidden',
							'group' => 'form_elements',
							'data'  => array( 'name' => 'tracking' ),
						),
					),
					'data'  => array( 'size' => '1/1' ),
				),
			)
		);
		$this->assertSame( array(), $required );
	}

	public function test_fields_in_invisible_column_are_not_required() {
		$required = $this->collect(
			array(
				$this->text( 'name' ),
				array(
					'tag'   => 'column',
					'group' => 'layout_elements',
					'inner' => array( $this->text( 'calc_holder' ) ),
					'data'  => array( 'size' => '1/1', 'invisible' => 'true' ),
				),
			)
		);
		$this->assertArrayHasKey( 'name', $required );
		$this->assertArrayNotHasKey( 'calc_holder', $required );
	}

	public function test_fields_in_visible_column_are_still_required() {
		$required = $this->collect(
			array(
				array(
					'tag'   => 'column',
					'group' => 'layout_elements',
					'inner' => array( $this->text( 'inside' ) ),
					'data'  => array( 'size' => '1/1', 'invisible' => '' ),
				),
			)
		);
		$this->assertArrayHasKey( 'inside', $required );
	}

	public function test_may_be_empty_behaviour_is_unchanged_for_validated_tags() {
		$required = $this->collect(
			array(
				$this->text( 'absent' ),
				$this->text( 'explicit_false', array( 'may_be_empty' => 'false' ) ),
				$this->text( 'optional', array( 'may_be_empty' => 'true' ) ),
				$this->text( 'conditional', array( 'may_be_empty' => 'conditions' ) ),
			)
		);
		$this->assertArrayHasKey( 'absent', $required );
		$this->assertArrayHasKey( 'explicit_false', $required );
		$this->assertArrayNotHasKey( 'optional', $required );
		$this->assertArrayNotHasKey( 'conditional', $required );
	}
}
