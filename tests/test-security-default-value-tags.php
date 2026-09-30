<?php
/**
 * Security regressions for {tag} and shortcode resolution in rendered default field values.
 *
 * SUPER_Shortcodes::get_default_value() (includes/class-shortcodes.php:163) builds the value
 * attribute of every element from, in order of precedence: the request ($_GET/$_POST[name]),
 * entry data (a previous submission or saved form progress) and the author-configured default.
 * Only the author default may resolve {tags} (SUPER_Common::email_tags()) and shortcodes
 * (do_shortcode()); request values and entry data are user input and must render literally,
 * and the author default must never resolve {option_*} (class-common.php:2798, meant for
 * e-mails) or {@secret} (class-common.php:2859).
 *
 * @package Super_Forms\Tests
 */

class Test_Security_Default_Value_Tags extends WP_UnitTestCase {

	private $custom_option;
	private $field;
	private $form_ids = array();
	private $global_secrets_existed = false;
	private $global_secrets_original;
	private $global_settings_existed = false;
	private $global_settings_original;
	private $option_sentinel;
	private $original_current_user = 0;
	private $original_get = array();
	private $original_post = array();
	private $original_post_global;
	private $original_request = array();
	private $original_request_uri = null;
	private $post_ids = array();
	private $scope;
	private $secret_sentinel;
	private $shortcode_calls = 0;
	private $shortcode_tag;
	private $smtp_sentinel;
	private $super_settings_existed = false;
	private $super_settings_original;
	private $user_ids = array();

	public function set_up() {
		parent::set_up();

		$this->scope           = 'sfdv' . str_replace( '-', '', wp_generate_uuid4() );
		$this->field           = $this->scope . '_field';
		$this->custom_option   = $this->scope . '_option';
		$this->shortcode_tag   = $this->scope . '_probe';
		$this->smtp_sentinel   = 'smtp-secret-' . $this->scope;
		$this->option_sentinel = 'option-secret-' . $this->scope;
		$this->secret_sentinel = 'global-secret-' . $this->scope;
		$this->shortcode_calls = 0;

		$this->original_current_user = get_current_user_id();
		$this->original_get          = $_GET;
		$this->original_post         = $_POST;
		$this->original_request      = $_REQUEST;
		$this->original_post_global  = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
		$this->original_request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;

		$missing = new stdClass();
		$this->super_settings_original = get_option( 'super_settings', $missing );
		$this->super_settings_existed  = ( $this->super_settings_original !== $missing );
		$this->global_secrets_original = get_option( 'super_global_secrets', $missing );
		$this->global_secrets_existed  = ( $this->global_secrets_original !== $missing );
		$forms                         = SUPER_Forms();
		$this->global_settings_existed = isset( $forms->global_settings );
		$this->global_settings_original = $this->global_settings_existed ? $forms->global_settings : null;

		// SUPER_Common::get_form_settings() (class-common.php:1464) merges the global settings
		// into every form's $settings, so the SMTP password is reachable as an option value
		// ({option_super_settings;smtp_password}) and as a form setting ({form_setting_smtp_password}).
		$settings = array(
			'smtp_password'         => $this->smtp_sentinel,
			'email_reminder_amount' => 0,
		);
		update_option( 'super_settings', $settings, false );
		$forms->global_settings = $settings;
		update_option( $this->custom_option, $this->option_sentinel, false );
		update_option(
			'super_global_secrets',
			array(
				array(
					'name'  => $this->scope . '_secret',
					'value' => $this->secret_sentinel,
				),
			)
		);
		add_shortcode( $this->shortcode_tag, array( $this, 'probe_shortcode' ) );

		// The contact-entry post statuses are registered on admin requests only
		// (super-forms.php:333,343); the entry-data regression stores one.
		if ( ! get_post_status_object( 'super_unread' ) ) {
			SUPER_Forms::custom_contact_entry_status();
		}
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			$_SERVER['REQUEST_URI'] = '/';
		}

		$_GET     = array();
		$_POST    = array();
		$_REQUEST = array();
		unset( $GLOBALS['post'] );
		wp_set_current_user( 0 );
	}

	public function tear_down() {
		remove_shortcode( $this->shortcode_tag );
		wp_set_current_user( 0 );
		unset( $GLOBALS['post'] );

		foreach ( array_reverse( array_merge( $this->post_ids, $this->form_ids ) ) as $post_id ) {
			wp_delete_post( $post_id, true );
			clean_post_cache( $post_id );
		}
		$this->post_ids = array();
		$this->form_ids = array();

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		foreach ( array_reverse( $this->user_ids ) as $user_id ) {
			if ( get_userdata( $user_id ) ) {
				wp_delete_user( $user_id );
			}
		}
		$this->user_ids = array();

		delete_option( $this->custom_option );
		if ( $this->super_settings_existed ) {
			update_option( 'super_settings', $this->super_settings_original, false );
		} else {
			delete_option( 'super_settings' );
		}
		if ( $this->global_secrets_existed ) {
			update_option( 'super_global_secrets', $this->global_secrets_original );
		} else {
			delete_option( 'super_global_secrets' );
		}
		$forms = SUPER_Forms();
		if ( $this->global_settings_existed ) {
			$forms->global_settings = $this->global_settings_original;
		} else {
			unset( $forms->global_settings );
		}

		$_GET     = $this->original_get;
		$_POST    = $this->original_post;
		$_REQUEST = $this->original_request;
		if ( null !== $this->original_post_global ) {
			$GLOBALS['post'] = $this->original_post_global;
		}
		if ( null === $this->original_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->original_request_uri;
		}
		wp_set_current_user( $this->original_current_user );

		parent::tear_down();
	}

	public function probe_shortcode() {
		++$this->shortcode_calls;
		return 'shortcode-ran-' . $this->scope;
	}

	private function shortcode_output() {
		return 'shortcode-ran-' . $this->scope;
	}

	private function create_text_form( $default_value, $settings = array() ) {
		$form_id = self::factory()->post->create(
			array(
				'post_type'   => 'super_form',
				'post_status' => 'publish',
			)
		);
		$this->form_ids[] = $form_id;
		update_post_meta(
			$form_id,
			'_super_elements',
			array(
				array(
					// Every stored element carries its builder group; the renderer reads it
					// unguarded (includes/class-shortcodes.php:6355).
					'tag'   => 'text',
					'group' => 'form_elements',
					'data'  => array(
						'name'  => $this->field,
						'email' => 'Probe field:',
						'value' => $default_value,
					),
					'inner' => array(),
				),
			)
		);
		update_post_meta( $form_id, '_super_form_settings', $settings );
		return $form_id;
	}

	private function render( $form_id ) {
		return SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
	}

	/**
	 * The value attribute the text element rendered for the probe field
	 * (includes/class-shortcodes.php:3086-3090 emits name before value), decoded;
	 * null when the input carries no value attribute at all.
	 */
	private function rendered_value( $html ) {
		$this->assertStringContainsString( 'name="' . $this->field . '"', $html );
		$pattern = '/<input[^>]*\sname="' . preg_quote( $this->field, '/' ) . '"([^>]*)>/';
		$this->assertSame( 1, preg_match( $pattern, $html, $input ) );
		if ( ! preg_match( '/\svalue="([^"]*)"/', $input[1], $value ) ) {
			return null;
		}
		return html_entity_decode( $value[1], ENT_QUOTES, 'UTF-8' );
	}

	private function default_value( $author_default, $form_id, $entry_data = null, $tag = 'text', $extra = array() ) {
		$atts = array_merge(
			array(
				'name'  => $this->field,
				'value' => $author_default,
			),
			$extra
		);
		return SUPER_Shortcodes::get_default_value( $tag, $atts, SUPER_Common::get_form_settings( $form_id ), $entry_data );
	}

	private function create_actor( $role = 'subscriber' ) {
		$user_id          = self::factory()->user->create( array( 'role' => $role ) );
		$this->user_ids[] = $user_id;
		return $user_id;
	}

	private function create_page( $title ) {
		$post_id          = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $title,
			)
		);
		$this->post_ids[] = $post_id;
		return $post_id;
	}

	private function assert_no_secret( $value ) {
		$this->assertIsString( $value );
		$this->assertStringNotContainsString( $this->smtp_sentinel, $value );
		$this->assertStringNotContainsString( $this->option_sentinel, $value );
		$this->assertStringNotContainsString( $this->secret_sentinel, $value );
	}

	public function test_author_default_never_resolves_options() {
		$smtp_tag   = '{option_super_settings;smtp_password}';
		$custom_tag = '{option_' . $this->custom_option . '}';
		$form_id    = $this->create_text_form( $smtp_tag );

		$this->assertSame( $this->smtp_sentinel, get_option( 'super_settings' )['smtp_password'] );
		$this->assertSame( $this->option_sentinel, get_option( $this->custom_option ) );

		$direct = $this->default_value( $smtp_tag, $form_id );
		$this->assert_no_secret( $direct );
		$this->assertSame( $smtp_tag, $direct );

		$direct = $this->default_value( $custom_tag, $form_id );
		$this->assert_no_secret( $direct );
		$this->assertSame( $custom_tag, $direct );

		$html = $this->render( $form_id );
		$this->assert_no_secret( $html );
		$this->assertSame( $smtp_tag, $this->rendered_value( $html ) );
	}

	public function test_request_supplied_tags_render_literally() {
		$form_id = $this->create_text_form( 'author-default-' . $this->scope );
		$user_id = $this->create_actor();
		update_user_meta( $user_id, $this->scope . '_meta', 'user-meta-secret-' . $this->scope );
		wp_set_current_user( $user_id );

		$probes = array(
			'{option_super_settings;smtp_password}',
			'{option_' . $this->custom_option . '}',
			'{form_setting_smtp_password}',
			'{user_meta_' . $this->scope . '_meta}',
			'{user_login}',
		);
		foreach ( $probes as $probe ) {
			$_GET     = array( $this->field => $probe );
			$_POST    = array();
			$_REQUEST = $_GET;
			$direct   = $this->default_value( 'author-default-' . $this->scope, $form_id );
			$this->assert_no_secret( $direct );
			$this->assertStringNotContainsString( 'user-meta-secret-', $direct );
			$this->assertSame( $probe, $direct, 'GET value must be literal: ' . $probe );

			$_GET     = array();
			$_POST    = array( $this->field => $probe );
			$_REQUEST = $_POST;
			$direct   = $this->default_value( 'author-default-' . $this->scope, $form_id );
			$this->assertSame( $probe, $direct, 'POST value must be literal: ' . $probe );
		}

		$_GET     = array( $this->field => '{option_super_settings;smtp_password}' );
		$_POST    = array();
		$_REQUEST = $_GET;
		$html     = $this->render( $form_id );
		$this->assert_no_secret( $html );
		$this->assertStringNotContainsString( 'user-meta-secret-', $html );
		$this->assertSame( '{option_super_settings;smtp_password}', $this->rendered_value( $html ) );
	}

	public function test_request_supplied_shortcode_is_not_executed() {
		$probe   = '[' . $this->shortcode_tag . ']';
		$form_id = $this->create_text_form( 'author-default-' . $this->scope );

		$_GET     = array( $this->field => $probe );
		$_REQUEST = $_GET;
		$direct   = $this->default_value( 'author-default-' . $this->scope, $form_id );
		$this->assertSame( $probe, $direct );
		$this->assertSame( 0, $this->shortcode_calls );

		$html = $this->render( $form_id );
		$this->assertStringNotContainsString( $this->shortcode_output(), $html );
		$this->assertSame( $probe, $this->rendered_value( $html ) );
		$this->assertSame( 0, $this->shortcode_calls );
	}

	public function test_author_default_still_resolves_documented_tags_and_shortcodes() {
		$page_id = $this->create_page( 'Probe page ' . $this->scope );
		update_post_meta( $page_id, $this->scope . '_meta', 'post-meta-' . $this->scope );
		$user_id = $this->create_actor();
		$login   = get_userdata( $user_id )->user_login;
		wp_set_current_user( $user_id );
		$GLOBALS['post'] = get_post( $page_id );

		$form_id = $this->create_text_form( '{post_title}' );

		$this->assertSame( 'Probe page ' . $this->scope, $this->default_value( '{post_title}', $form_id ) );
		$this->assertSame( $login, $this->default_value( '{user_login}', $form_id ) );
		$this->assertSame( 'post-meta-' . $this->scope, $this->default_value( '{post_meta_' . $this->scope . '_meta}', $form_id ) );
		$this->assertSame( $this->shortcode_output(), $this->default_value( '[' . $this->shortcode_tag . ']', $form_id ) );
		$this->assertSame( 1, $this->shortcode_calls );

		$html = $this->render( $form_id );
		$this->assertSame( 'Probe page ' . $this->scope, $this->rendered_value( $html ) );

		// An empty entry value does not replace the author default (class-shortcodes.php:173).
		$entry_data = array( $this->field => array( 'value' => '' ) );
		$this->assertSame( $login, $this->default_value( '{user_login}', $form_id, $entry_data ) );
	}

	public function test_secret_tags_stay_unresolved() {
		$probe   = '{@' . $this->scope . '_secret}';
		$form_id = $this->create_text_form( $probe );

		$direct = $this->default_value( $probe, $form_id );
		$this->assert_no_secret( $direct );
		$this->assertSame( $probe, $direct );

		$html = $this->render( $form_id );
		$this->assert_no_secret( $html );
		$this->assertSame( $probe, $this->rendered_value( $html ) );

		$_GET     = array( $this->field => $probe );
		$_REQUEST = $_GET;
		$this->assertSame( $probe, $this->default_value( 'author-default-' . $this->scope, $form_id ) );
	}

	public function test_entry_data_values_render_literally() {
		$smtp_tag = '{option_super_settings;smtp_password}';
		$probe    = '[' . $this->shortcode_tag . ']';
		$form_id  = $this->create_text_form( '{post_title}' );

		$entry_data = array( $this->field => array( 'value' => $smtp_tag ) );
		$direct     = $this->default_value( '{post_title}', $form_id, $entry_data );
		$this->assert_no_secret( $direct );
		$this->assertSame( $smtp_tag, $direct );

		$entry_data = array( $this->field => array( 'value' => $probe ) );
		$this->assertSame( $probe, $this->default_value( '{post_title}', $form_id, $entry_data ) );
		$this->assertSame( 0, $this->shortcode_calls );

		// Full render: the entry owner reads back their own entry through ?contact_entry_id=
		// (class-shortcodes.php:5941-5974), the same entry-data path saved form progress uses.
		$owner_id = $this->create_actor();
		$entry_id = self::factory()->post->create(
			array(
				'post_type'   => 'super_contact_entry',
				'post_status' => 'super_unread',
				'post_parent' => $form_id,
				'post_author' => $owner_id,
			)
		);
		$this->post_ids[] = $entry_id;
		SUPER_Data_Access::update_entry_data(
			$entry_id,
			array(
				$this->field => array(
					'name'  => $this->field,
					'value' => $smtp_tag,
					'type'  => 'text',
				),
			)
		);
		wp_set_current_user( $owner_id );
		$_GET     = array( 'contact_entry_id' => (string) $entry_id );
		$_REQUEST = $_GET;
		$html     = $this->render( $form_id );
		$this->assert_no_secret( $html );
		$this->assertSame( $smtp_tag, $this->rendered_value( $html ) );
	}

	public function test_dropdown_absolute_default_and_default_argument_are_unchanged() {
		$form_id = $this->create_text_form( '' );

		$this->assertSame( 'abs-' . $this->scope, $this->default_value( '', $form_id, null, 'dropdown', array( 'absolute_default' => 'abs-' . $this->scope ) ) );

		$atts = array( 'name' => $this->field );
		$this->assertSame( '0', SUPER_Shortcodes::get_default_value( 'text', $atts, SUPER_Common::get_form_settings( $form_id ), null, '0' ) );
	}
}
