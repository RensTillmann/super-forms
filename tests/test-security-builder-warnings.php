<?php
/**
 * Builder code paths must not raise PHP warnings/notices.
 */
class Super_Forms_Builder_Warnings_Stop extends Exception {}

class Test_Security_Builder_Warnings extends WP_UnitTestCase {
	private $issues = array();

	/**
	 * The test bootstrap is neither an admin nor an AJAX request, so the builder
	 * classes are not loaded by super-forms.php.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		if ( ! class_exists( 'SUPER_Ajax' ) ) {
			require_once dirname( __DIR__ ) . '/includes/class-ajax.php';
		}
		if ( ! class_exists( 'SUPER_Field_Types' ) ) {
			require_once dirname( __DIR__ ) . '/includes/class-field-types.php';
		}
	}

	private function capture_issues() {
		$this->issues = array();
		$issues = &$this->issues;
		set_error_handler(
			static function ( $errno, $errstr, $errfile, $errline ) use ( &$issues ) {
				if ( strpos( $errfile, '/plugins/super-forms/' ) !== false ) {
					$issues[] = $errstr . ' in ' . basename( $errfile ) . ':' . $errline;
				}
				return true;
			}
		);
	}

	public function test_switch_language_to_translation_raises_no_warnings() {
		$form_id = self::factory()->post->create( array( 'post_type' => 'super_form', 'post_status' => 'publish' ) );
		// switch_language() ends with a raw die(); stop it once the settings are rendered, when it loads the elements.
		$stop = static function ( $value, $object_id, $meta_key ) {
			if ( $meta_key === '_super_elements' ) {
				throw new Super_Forms_Builder_Warnings_Stop();
			}
			return $value;
		};
		add_filter( 'get_post_metadata', $stop, 10, 3 );
		$original_post = $_POST;
		$original_request = $_REQUEST;
		$original_user = get_current_user_id();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_POST = array( 'form_id' => (string) $form_id, 'i18n' => 'nl_NL', 'nonce' => wp_create_nonce( 'super_admin_ajax' ) );
		$_REQUEST = $_POST;
		$stopped = false;
		$this->capture_issues();
		ob_start();
		try {
			SUPER_Ajax::switch_language();
		} catch ( Super_Forms_Builder_Warnings_Stop $e ) {
			$stopped = true;
		} finally {
			ob_end_clean();
			restore_error_handler();
			remove_filter( 'get_post_metadata', $stop, 10 );
			$_POST = $original_post;
			$_REQUEST = $original_request;
			wp_set_current_user( $original_user );
		}
		$this->assertTrue( $stopped, 'switch_language() did not reach the form elements.' );
		$this->assertSame( array(), $this->issues );
	}

	public function test_select_setting_without_default_raises_no_warnings() {
		$this->capture_issues();
		try {
			$html = SUPER_Field_Types::select( 'some_setting', array( 'values' => array( 'a' => 'A', 'b' => 'B' ), 'v' => 'b' ) );
		} finally {
			restore_error_handler();
		}
		$this->assertSame( array(), $this->issues );
		$this->assertStringContainsString( '<option value="b" selected="selected">B</option>', $html );
		$this->assertStringContainsString( 'super-reset-default-value" title="Reset to default value" data-value=""', $html );
	}

	public function test_reset_setting_icons_keeps_given_default() {
		$this->capture_issues();
		try {
			$single = SUPER_Common::reset_setting_icons( array( 'default' => 'x', 'v' => 'y', 'g' => 'z' ) );
			$all    = SUPER_Common::reset_setting_icons( array( 'default' => '_reset_', 'v' => '_reset_', 'g' => '_reset_' ) );
		} finally {
			restore_error_handler();
		}
		$this->assertSame( array(), $this->issues );
		$this->assertStringContainsString( 'title="Reset to default value" data-value="x"', $single );
		$this->assertStringContainsString( 'title="Reset all to default value" data-value="_reset_"', $all );
	}

	public function test_minute_schedule_before_theme_setup_does_not_translate() {
		global $wp_actions;
		$translated = 0;
		$count      = static function ( $text ) use ( &$translated ) {
			$translated++;
			return $text;
		};
		add_filter( 'gettext_super-forms', $count );
		$saved = isset( $wp_actions['after_setup_theme'] ) ? $wp_actions['after_setup_theme'] : null;
		unset( $wp_actions['after_setup_theme'] );
		try {
			$early = SUPER_Forms::minute_schedule( array() );
		} finally {
			if ( $saved !== null ) {
				$wp_actions['after_setup_theme'] = $saved;
			}
		}
		$this->assertSame( 0, $translated, 'The super-forms text domain was used before after_setup_theme.' );
		$this->assertSame( array( 'interval' => 60, 'display' => 'Every minute' ), $early['every_minute'] );

		$late = SUPER_Forms::minute_schedule( array() );
		remove_filter( 'gettext_super-forms', $count );
		$this->assertSame( 1, $translated );
		$this->assertSame( array( 'interval' => 60, 'display' => 'Every minute' ), $late['every_minute'] );
	}
}
