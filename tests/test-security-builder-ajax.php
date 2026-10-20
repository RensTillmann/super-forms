<?php
/**
 * Security regressions for the form builder's read-only AJAX actions (preview, language
 * switch, undo/redo, element settings and element HTML): they render form markup and form
 * settings, so they require an administrator and the localized super_admin_ajax nonce.
 *
 * @package Super_Forms\Tests
 */

require_once __DIR__ . '/test-security-upload-00-base.php';

class Super_Forms_Builder_Ajax_Rejected_Exception extends RuntimeException {
}

class Test_Security_Builder_Ajax extends Super_Forms_Upload_Security_Test_Case {

	const REJECTED = 'Super_Forms_Builder_Ajax_Rejected_Exception: -1';

	private $marker;

	public static function set_up_before_class() {
		parent::set_up_before_class();
		// SUPER_Field_Types is only loaded on admin requests (super-forms.php:227); the
		// element settings and form settings HTML of these builder actions need it.
		if ( ! class_exists( 'SUPER_Field_Types' ) ) {
			require_once dirname( __DIR__ ) . '/includes/class-field-types.php';
		}
	}

	public function set_up() {
		parent::set_up();
		$this->marker = 'builder_ajax_marker_' . str_replace( '-', '', wp_generate_uuid4() );
	}

	private function elements() {
		return array(
			array(
				'tag'   => 'text',
				'group' => 'form_elements',
				'data'  => array(
					'name'  => $this->marker,
					'email' => 'Marker:',
				),
				'inner' => array(),
			),
		);
	}

	private function set_actor( $role ) {
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		wp_set_current_user( $user_id );
		return $user_id;
	}

	private function request_for( $action, $form_id ) {
		$request = array(
			'action'  => 'super_' . $action,
			'form_id' => $form_id,
		);
		if ( 'switch_language' === $action ) {
			$request['i18n'] = '';
		} elseif ( 'undo_redo' === $action ) {
			$request['elements'] = wp_json_encode( $this->elements() );
		} elseif ( 'load_element_settings' === $action || 'get_element_builder_html' === $action ) {
			$request['tag']         = 'text';
			$request['group']       = 'form_elements';
			$request['data']        = $this->text_element_settings_data();
			$request['translating'] = 'false';
			$request['i18n']        = '';
		}
		return $request;
	}

	/**
	 * The element data the builder posts when a text field is edited: the element's setting
	 * values. Settings without a 'default' in their definition (such as the "type" select)
	 * only render when the element data supplies their value
	 * (SUPER_Ajax::loop_over_element_setting_fields() copies it into 'default').
	 */
	private function text_element_settings_data() {
		$shortcodes = SUPER_Shortcodes::shortcodes( false, false, false );
		$data       = array();
		foreach ( $shortcodes['form_elements']['shortcodes']['text']['atts'] as $tab ) {
			$groups = isset( $tab['fields'] ) ? array( $tab ) : array_filter( $tab, 'is_array' );
			foreach ( $groups as $group ) {
				if ( empty( $group['fields'] ) ) {
					continue;
				}
				foreach ( $group['fields'] as $key => $field ) {
					if ( isset( $field['default'] ) ) {
						$data[ $key ] = $field['default'];
					} elseif ( isset( $field['type'] ) && 'select' === $field['type'] && ! empty( $field['values'] ) ) {
						$data[ $key ] = (string) key( $field['values'] );
					}
				}
			}
		}
		$data['name'] = $this->marker;
		return $data;
	}

	private function apply_nonce( $request, $mode ) {
		unset( $request['nonce'] );
		if ( 'valid' === $mode ) {
			$request['nonce'] = wp_create_nonce( 'super_admin_ajax' );
		} elseif ( 'wrong' === $mode ) {
			$request['nonce'] = wp_create_nonce( 'super_save_form' );
		}
		$_POST    = $request;
		$_REQUEST = $request;
	}

	/**
	 * Run the wp_ajax_super_* action out of process, because every handler ends in a raw die().
	 * A wp_die() rejection (check_ajax_referer() or a capability check) is reported as
	 * self::REJECTED with exit status 97; a handler that rendered and died exits with 0.
	 */
	private function run_builder_ajax( $action ) {
		return $this->run_dying_handler(
			static function () use ( $action ) {
				$die_handler = static function () {
					return static function ( $message = '' ) {
						throw new Super_Forms_Builder_Ajax_Rejected_Exception( is_scalar( $message ) ? (string) $message : 'non-scalar' );
					};
				};
				add_filter( 'wp_doing_ajax', '__return_true' );
				add_filter( 'wp_die_ajax_handler', $die_handler );
				add_filter( 'wp_die_handler', $die_handler );
				do_action( 'wp_ajax_super_' . $action );
			},
			false
		);
	}

	private function success_marker( $action ) {
		return 'load_element_settings' === $action ? 'super-update-element' : $this->marker;
	}

	public static function builder_actions() {
		return array(
			'load_preview'             => array( 'load_preview' ),
			'switch_language'          => array( 'switch_language' ),
			'undo_redo'                => array( 'undo_redo' ),
			'load_element_settings'    => array( 'load_element_settings' ),
			'get_element_builder_html' => array( 'get_element_builder_html' ),
		);
	}

	public static function rejected_request_provider() {
		$actors = array(
			'subscriber with valid nonce'    => array( 'subscriber', 'valid' ),
			'editor with valid nonce'        => array( 'editor', 'valid' ),
			'administrator without nonce'    => array( 'administrator', 'missing' ),
			'administrator with wrong nonce' => array( 'administrator', 'wrong' ),
		);
		$cases = array();
		foreach ( array_keys( self::builder_actions() ) as $action ) {
			foreach ( $actors as $label => $actor ) {
				$cases[ $action . ': ' . $label ] = array( $action, $actor[0], $actor[1] );
			}
		}
		return $cases;
	}

	/**
	 * @dataProvider rejected_request_provider
	 */
	public function test_builder_ajax_rejects_without_administrator_and_admin_nonce( $action, $role, $nonce_mode ) {
		$form_id = $this->create_form( 'draft', $this->elements(), array() );
		$this->set_actor( $role );
		$this->apply_nonce( $this->request_for( $action, $form_id ), $nonce_mode );

		$result = $this->run_builder_ajax( $action );

		$this->assertTrue( $result['exited'] );
		$this->assertSame( 97, $result['status'], $result['output'] );
		$this->assertSame( self::REJECTED, trim( $result['output'] ) );
		$this->assertStringNotContainsString( $this->marker, $result['output'] );
		$this->assertStringNotContainsString( 'super-update-element', $result['output'] );
		$this->assertStringNotContainsString( '"settings"', $result['output'] );
	}

	/**
	 * @dataProvider builder_actions
	 */
	public function test_administrator_with_admin_nonce_can_use_builder_ajax( $action ) {
		$form_id = $this->create_form( 'publish', $this->elements(), array() );
		$this->set_actor( 'administrator' );
		$this->apply_nonce( $this->request_for( $action, $form_id ), 'valid' );

		$result = $this->run_builder_ajax( $action );

		$this->assertTrue( $result['exited'] );
		$this->assertSame( 0, $result['status'], $result['output'] );
		$this->assertStringNotContainsString( self::REJECTED, $result['output'] );
		$this->assertStringContainsString( $this->success_marker( $action ), $result['output'] );
		if ( 'switch_language' === $action ) {
			$payload = json_decode( $result['output'], true );
			$this->assertIsArray( $payload, $result['output'] );
			$this->assertArrayHasKey( 'settings', $payload );
			$this->assertStringContainsString( $this->marker, $payload['elements'] );
		}
	}

	public function test_preview_rejects_a_form_backup_for_an_administrator() {
		$form_id   = $this->create_form( 'publish', array(), array() );
		$backup_id = self::factory()->post->create(
			array(
				'post_type'   => 'super_form',
				'post_status' => 'backup',
				'post_parent' => $form_id,
			)
		);
		update_post_meta( $backup_id, '_super_elements', $this->elements() );
		update_post_meta( $backup_id, '_super_form_settings', array() );
		$this->set_actor( 'administrator' );
		$this->apply_nonce( $this->request_for( 'load_preview', $backup_id ), 'valid' );

		$result = $this->run_builder_ajax( 'load_preview' );

		$this->assertTrue( $result['exited'] );
		$this->assertSame( 97, $result['status'], $result['output'] );
		$this->assertSame( self::REJECTED, trim( $result['output'] ) );
		$this->assertStringNotContainsString( $this->marker, $result['output'] );
	}

	public function test_internal_get_element_builder_html_call_still_returns_html() {
		$this->set_actor( 'subscriber' );
		$html = SUPER_Ajax::get_element_builder_html( 'text', 'form_elements', array(), array( 'name' => $this->marker ), 0 );
		$this->assertIsString( $html );
		$this->assertStringContainsString( $this->marker, $html );
	}

	public function test_internal_load_element_settings_call_still_renders_settings() {
		$this->set_actor( 'subscriber' );
		$_POST    = array(
			'form_id'     => 0,
			'translating' => 'false',
			'i18n'        => '',
		);
		$_REQUEST = $_POST;
		$data     = $this->text_element_settings_data();

		$result = $this->run_dying_handler(
			static function () use ( $data ) {
				SUPER_Ajax::load_element_settings( 'text', 'form_elements', $data );
			},
			false
		);

		$this->assertTrue( $result['exited'] );
		$this->assertSame( 0, $result['status'], $result['output'] );
		$this->assertStringContainsString( 'super-update-element', $result['output'] );
	}


}
