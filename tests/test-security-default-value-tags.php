<?php
/**
 * Security regressions for {tag} and shortcode resolution in rendered default field values.
 *
 * SUPER_Shortcodes::get_default_value() (includes/class-shortcodes.php:163) builds the value
 * attribute of every element from, in order of precedence: the request ($_GET/$_POST[name]),
 * entry data (a previous submission or saved form progress, also the per-row values of a dynamic
 * column in `_super_dynamic_data`) and the author-configured default.
 * Only the author default may resolve {tags} (SUPER_Common::email_tags()) and shortcodes
 * (do_shortcode()); request values and entry data are user input and must render literally.
 * Tags written by the author keep the 6.3.317 behaviour: {option_*} (class-common.php:2826) and
 * {form_setting_*} resolve, only {@secret} (class-common.php:2886) stays unresolved until submission.
 * The contents of the tags a visitor controls ({server_http_referrer}, {user_firstname} etc.,
 * SUPER_Common::literal_tag_names() at class-common.php:2927) are inserted literally, also when
 * the author uses them.
 *
 * Two more sinks emit the same strings for every visitor and are covered by the full renders:
 * the whole form is passed through do_shortcode() once more at the end of super_form_func()
 * (class-shortcodes.php:6593), and common_attributes() prints the author default a second time
 * as data-absolute-default="" (class-shortcodes.php:1783, fed by output_element_html() at 5140).
 *
 * @package Super_Forms\Tests
 */

class Test_Security_Default_Value_Tags extends WP_UnitTestCase {

	private $blogname_original;
	private $custom_option;
	private $field;
	private $form_ids = array();
	private $global_secrets_existed = false;
	private $global_secrets_original;
	private $global_settings_existed = false;
	private $global_settings_original;
	private $option_sentinel;
	private $original_cookie_session = null;
	private $original_current_user = 0;
	private $original_get = array();
	private $original_post = array();
	private $original_post_global;
	private $original_referer = null;
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
		// A secret-looking option name: must never be resolved into the page.
		$this->custom_option   = $this->scope . '_api_key';
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
		$this->original_referer      = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : null;
		$this->original_cookie_session = isset( $_COOKIE['_sfs_id'] ) ? $_COOKIE['_sfs_id'] : null;

		$missing = new stdClass();
		$this->super_settings_original = get_option( 'super_settings', $missing );
		$this->super_settings_existed  = ( $this->super_settings_original !== $missing );
		$this->global_secrets_original = get_option( 'super_global_secrets', $missing );
		$this->global_secrets_existed  = ( $this->global_secrets_original !== $missing );
		$this->blogname_original       = get_option( 'blogname' );
		$forms                         = SUPER_Forms();
		$this->global_settings_existed = isset( $forms->global_settings );
		$this->global_settings_original = $this->global_settings_existed ? $forms->global_settings : null;

		// SUPER_Common::get_form_settings() (class-common.php:1471) merges the global settings
		// into every form's $settings, so the SMTP password is reachable as an option value
		// ({option_super_settings;smtp_password}) and as a form setting ({form_setting_smtp_password}).
		$settings = array(
			'smtp_password'         => $this->smtp_sentinel,
			'email_reminder_amount' => 0,
		);
		update_option( 'super_settings', $settings, false );
		$forms->global_settings = $settings;
		update_option( $this->custom_option, $this->option_sentinel, false );
		update_option( 'blogname', 'Probe blog ' . $this->scope );
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
		unset( $_SERVER['HTTP_REFERER'], $_COOKIE['_sfs_id'] );
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
		update_option( 'blogname', $this->blogname_original );
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
		if ( null === $this->original_referer ) {
			unset( $_SERVER['HTTP_REFERER'] );
		} else {
			$_SERVER['HTTP_REFERER'] = $this->original_referer;
		}
		if ( isset( $_COOKIE['_sfs_id'] ) ) {
			delete_option( '_sfsdata_' . $_COOKIE['_sfs_id'] );
		}
		if ( null === $this->original_cookie_session ) {
			unset( $_COOKIE['_sfs_id'] );
		} else {
			$_COOKIE['_sfs_id'] = $this->original_cookie_session;
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

	private function create_form( $tag, $default_value, $settings = array() ) {
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
					// unguarded (includes/class-shortcodes.php:6486).
					'tag'   => $tag,
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

	public function test_column_and_field_custom_classes_are_attribute_escaped() {
		// A class with markup characters (e.g. a sanitizer placeholder) must not break out of the
		// class attribute and push the fields outside the <form>.
		$evil = 'note <customer-identifier> x"><b id="sf-injected">y</b><i class="';
		$form_id = self::factory()->post->create( array( 'post_type' => 'super_form', 'post_status' => 'publish' ) );
		$this->form_ids[] = $form_id;
		update_post_meta( $form_id, '_super_elements', array(
			array(
				'tag' => 'column', 'group' => 'layout_elements',
				'data' => array( 'size' => '1/1', 'class' => $evil ),
				'inner' => array(
					array( 'tag' => 'text', 'group' => 'form_elements', 'data' => array( 'name' => 'inside_column', 'email' => 'Inside:', 'class' => $evil ), 'inner' => array() ),
				),
			),
		) );
		update_post_meta( $form_id, '_super_form_settings', array() );
		$html = $this->render( $form_id );
		$this->assertStringContainsString( 'name="inside_column"', $html );
		$this->assertStringNotContainsString( '<b id="sf-injected"', $html );
		$this->assertStringNotContainsString( '<customer-identifier>', $html );
	}

	public function test_pdf_font_download_failure_is_not_cached_and_no_font_link_is_printed() {
		// t_1b97fa56: a failed font download must not leave an (empty) cached file that breaks PDFs
		// forever, and the browser must not be pointed at fonts that do not exist.
		$language = 'zzgatemissing';
		$dir = SUPER_PLUGIN_DIR . '/includes/extensions/pdf-generator/fonts/';
		if( !wp_mkdir_p( $dir ) || !is_writable( $dir ) ) {
			$this->markTestSkipped( 'Plugin font directory is read-only here (PHPUnit runner payload); covered by the scratch font probe.' );
		}
		foreach( array( 'json', 'woff', 'woff2' ) as $ext ) @unlink( $dir . $language . '.' . $ext );
		file_put_contents( $dir . $language . '.json', '' ); // left behind by an earlier failed download
		$form_id = self::factory()->post->create( array( 'post_type' => 'super_form', 'post_status' => 'publish' ) );
		$this->form_ids[] = $form_id;
		update_post_meta( $form_id, '_super_elements', array(
			array( 'tag' => 'text', 'group' => 'form_elements', 'data' => array( 'name' => 'pdf_probe', 'email' => 'Probe:' ), 'inner' => array() ),
		) );
		update_post_meta( $form_id, '_super_form_settings', array(
			'_pdf' => array( 'generate' => 'true', 'debug' => 'false', 'textRendering' => 'true', 'language' => $language ),
		) );
		$html = $this->render( $form_id );
		ob_start();
		// As on a real page load: wp_enqueue_scripts runs first, and WordPress core's
		// wp_enqueue_block_template_skip_link() unhooks the deprecated the_block_template_skip_link from wp_footer.
		do_action( 'wp_enqueue_scripts' );
		do_action( 'wp_footer' );
		$footer = ob_get_clean();
		$this->assertStringContainsString( 'name="pdf_probe"', $html );
		$this->assertStringNotContainsString( 'fonts/' . $language, $footer );
		foreach( array( 'json', 'woff', 'woff2' ) as $ext ) {
			$file = $dir . $language . '.' . $ext;
			$this->assertFalse( file_exists( $file ) && filesize( $file )===0, 'empty cached ' . $ext );
			@unlink( $file );
		}
	}

	private function create_text_form( $default_value, $settings = array() ) {
		return $this->create_form( 'text', $default_value, $settings );
	}

	private function create_textarea_form( $default_value, $settings = array() ) {
		return $this->create_form( 'textarea', $default_value, $settings );
	}

	private function render( $form_id ) {
		return SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
	}

	/**
	 * The complete opening tag of the probe element (input or textarea).
	 */
	private function probe_element( $html ) {
		$this->assertStringContainsString( 'name="' . $this->field . '"', $html );
		$pattern = '/<(?:input|textarea)\b[^>]*\sname="' . preg_quote( $this->field, '/' ) . '"[^>]*>/';
		$this->assertSame( 1, preg_match( $pattern, $html, $element ) );
		return $element[0];
	}

	/**
	 * The value attribute the text element rendered for the probe field
	 * (includes/class-shortcodes.php:3208-3212 emits name before value), decoded;
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

	/**
	 * The text between the textarea tags of the probe field (written without escaping at
	 * includes/class-shortcodes.php:3648-3649), decoded the way the browser decodes it.
	 */
	private function rendered_textarea_value( $html ) {
		$this->assertStringContainsString( 'name="' . $this->field . '"', $html );
		$pattern = '/<textarea\b[^>]*\sname="' . preg_quote( $this->field, '/' ) . '"[^>]*>(.*?)<\/textarea>/s';
		$this->assertSame( 1, preg_match( $pattern, $html, $textarea ) );
		return html_entity_decode( $textarea[1], ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * The data-absolute-default attribute of the probe element (class-shortcodes.php:1783),
	 * decoded; null when absent.
	 */
	private function rendered_absolute_default( $html ) {
		$element = $this->probe_element( $html );
		if ( ! preg_match( '/\sdata-absolute-default="([^"]*)"/', $element, $value ) ) {
			return null;
		}
		return html_entity_decode( $value[1], ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * A literal (request/entry) value leaves get_default_value() with its square brackets
	 * entity-escaped so the whole-form do_shortcode() can not match it; the browser (and
	 * do_shortcode() itself, through unescape_invalid_shortcodes()) turns them back into the
	 * text the visitor typed.
	 */
	private function assert_bracket_literal( $expected, $got, $message = '' ) {
		$this->assertIsString( $got, $message );
		$this->assertStringNotContainsString( '[', $got, $message );
		$this->assertStringNotContainsString( ']', $got, $message );
		$this->assertSame( $expected, html_entity_decode( $got, ENT_QUOTES, 'UTF-8' ), $message );
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

	/**
	 * A contact entry owned by $owner_id holding one value for the probe field; the owner
	 * reads it back through ?contact_entry_id= (class-shortcodes.php:6070-6160), the same
	 * entry-data path saved form progress uses.
	 */
	private function create_owned_entry( $form_id, $owner_id, $value, $type = 'text' ) {
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
					'value' => $value,
					'type'  => $type,
				),
			)
		);
		return $entry_id;
	}

	private function assert_no_secret( $value ) {
		$this->assertIsString( $value );
		$this->assertStringNotContainsString( $this->smtp_sentinel, $value );
		$this->assertStringNotContainsString( $this->option_sentinel, $value );
		$this->assertStringNotContainsString( $this->secret_sentinel, $value );
	}

	public function test_author_default_tags_resolve_as_in_6_3_317() {
		// Tags written by the form author behave exactly as before this fix: the generic and the
		// built-in {option_*} tags and {form_setting_*} resolve at render (the author's choice;
		// sensitive values belong in the Secrets tab), {@secrets} stay unresolved until submission.
		$plain_option = $this->scope . '_currency';
		update_option( $plain_option, 'EUR-' . $this->scope, false );
		$plain_tag = '{option_' . $plain_option . '}';
		$form_id   = $this->create_text_form( $plain_tag );
		$this->assertSame( 'EUR-' . $this->scope, $this->default_value( $plain_tag, $form_id ) );
		$html = $this->render( $form_id );
		$this->assertSame( 'EUR-' . $this->scope, $this->rendered_value( $html ) );
		$this->assertSame( 'EUR-' . $this->scope, $this->rendered_absolute_default( $html ) );
		delete_option( $plain_option );

		$this->assertSame( get_option( 'admin_email' ), $this->default_value( '{option_admin_email}', $form_id ) );
		$this->assertSame( $this->option_sentinel, $this->default_value( '{option_' . $this->custom_option . '}', $form_id ) );
		$this->assertSame( $this->smtp_sentinel, $this->default_value( '{form_setting_smtp_password}', $form_id ) );

		$secret_tag = '{@' . $this->scope . '_secret}';
		$this->assertSame( $secret_tag, $this->default_value( $secret_tag, $form_id ) );
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
		$this->assert_bracket_literal( $probe, $direct );
		$this->assertSame( 0, $this->shortcode_calls );

		$_GET     = array();
		$_POST    = array( $this->field => $probe );
		$_REQUEST = $_POST;
		$this->assert_bracket_literal( $probe, $this->default_value( 'author-default-' . $this->scope, $form_id ) );
		$this->assertSame( 0, $this->shortcode_calls );

		// The whole form is passed through do_shortcode() at the end of super_form_func()
		// (class-shortcodes.php:6593): the callback must not run for the value attribute and
		// the visitor must still see what was typed.
		$_GET     = array( $this->field => $probe );
		$_POST    = array();
		$_REQUEST = $_GET;
		$html     = $this->render( $form_id );
		$this->assertStringNotContainsString( $this->shortcode_output(), $html );
		$this->assertSame( $probe, $this->rendered_value( $html ) );
		$this->assertSame( 0, $this->shortcode_calls, 'the whole-form do_shortcode() must not invoke the callback for a value attribute' );

		// A textarea writes the value between its tags without escaping (class-shortcodes.php:3648-3649),
		// where the whole-form do_shortcode() would expand a bare [shortcode] as text.
		$form_id = $this->create_textarea_form( 'author-default-' . $this->scope );
		$html    = $this->render( $form_id );
		$this->assertStringNotContainsString( $this->shortcode_output(), $html );
		$this->assertSame( $probe, $this->rendered_textarea_value( $html ) );
		$this->assertSame( 0, $this->shortcode_calls, 'the whole-form do_shortcode() must not expand a textarea value' );

		// Plain brackets that are not a shortcode render exactly as typed.
		$_GET     = array( $this->field => 'a [b] c' );
		$_REQUEST = $_GET;
		$html     = $this->render( $form_id );
		$this->assertSame( 'a [b] c', $this->rendered_textarea_value( $html ) );
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
		// The predefined {option_blogname} tag (class-common.php:2286) is not the generic {option_*} branch.
		$this->assertSame( 'Probe blog ' . $this->scope, $this->default_value( '{option_blogname}', $form_id ) );
		$this->assertSame( $this->shortcode_output(), $this->default_value( '[' . $this->shortcode_tag . ']', $form_id ) );
		$this->assertSame( 1, $this->shortcode_calls );

		$html = $this->render( $form_id );
		$this->assertSame( 'Probe page ' . $this->scope, $this->rendered_value( $html ) );
		$this->assertSame( 'Probe page ' . $this->scope, $this->rendered_absolute_default( $html ) );

		// An author default that is a shortcode still runs in a textarea as well. super_form_func()
		// evaluates element defaults twice per render (also on unpatched lts/6.3.x), so only
		// assert that it ran and that its output is what the visitor gets.
		$this->shortcode_calls = 0;
		$form_id               = $this->create_textarea_form( '[' . $this->shortcode_tag . ']' );
		$html                  = $this->render( $form_id );
		$this->assertSame( $this->shortcode_output(), $this->rendered_textarea_value( $html ) );
		$this->assertGreaterThanOrEqual( 1, $this->shortcode_calls );

		// An empty entry value does not replace the author default (class-shortcodes.php:182).
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
		$this->assertSame( $probe, $this->rendered_absolute_default( $html ) );

		$_GET     = array( $this->field => $probe );
		$_REQUEST = $_GET;
		$this->assertSame( $probe, $this->default_value( 'author-default-' . $this->scope, $form_id ) );
	}

	public function test_absolute_default_attribute_follows_the_default_value_rules() {
		// data-absolute-default="" is the author default emitted a second time on the same element
		// (output_element_html() 5131-5140 -> SUPER_Common::get_absolute_default_value() -> common_attributes() 1783).
		$page_id = $this->create_page( 'Probe page ' . $this->scope );
		$GLOBALS['post'] = get_post( $page_id );

		$form_id = $this->create_text_form( '{@' . $this->scope . '_secret}' );
		$html    = $this->render( $form_id );
		$this->assert_no_secret( $html );
		$this->assertSame( '{@' . $this->scope . '_secret}', $this->rendered_absolute_default( $html ) );

		// Documented tags keep resolving there (the attribute resets a duplicated dynamic column).
		$form_id = $this->create_text_form( '{post_title}' );
		$html    = $this->render( $form_id );
		$this->assertSame( 'Probe page ' . $this->scope, $this->rendered_absolute_default( $html ) );

		// A request value never reaches the attribute: it stays the author default.
		$_GET     = array( $this->field => '{option_' . $this->custom_option . '}' );
		$_REQUEST = $_GET;
		$html     = $this->render( $form_id );
		$this->assert_no_secret( $html );
		$this->assertSame( '{option_' . $this->custom_option . '}', $this->rendered_value( $html ) );
		$this->assertSame( 'Probe page ' . $this->scope, $this->rendered_absolute_default( $html ) );
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
		$this->assert_bracket_literal( $probe, $this->default_value( '{post_title}', $form_id, $entry_data ) );
		$this->assert_bracket_literal( $probe, $this->default_value( '', $form_id, $entry_data, 'textarea' ) );
		$this->assertSame( 0, $this->shortcode_calls );

		// Full render: the entry owner reads back their own entry through ?contact_entry_id=
		// (class-shortcodes.php:6070-6160), the same entry-data path saved form progress uses.
		$owner_id = $this->create_actor();
		$entry_id = $this->create_owned_entry( $form_id, $owner_id, $smtp_tag );
		wp_set_current_user( $owner_id );
		$_GET     = array( 'contact_entry_id' => (string) $entry_id );
		$_REQUEST = $_GET;
		$html     = $this->render( $form_id );
		$this->assert_no_secret( $html );
		$this->assertSame( $smtp_tag, $this->rendered_value( $html ) );

		// A stored [shortcode] in a textarea survives the whole-form do_shortcode() (6593) literally.
		$form_id  = $this->create_textarea_form( 'author-default-' . $this->scope );
		$entry_id = $this->create_owned_entry( $form_id, $owner_id, $probe, 'textarea' );
		$_GET     = array( 'contact_entry_id' => (string) $entry_id );
		$_REQUEST = $_GET;
		$html     = $this->render( $form_id );
		$this->assertStringNotContainsString( $this->shortcode_output(), $html );
		$this->assertSame( $probe, $this->rendered_textarea_value( $html ) );
		$this->assertSame( 0, $this->shortcode_calls );
	}

	public function test_dropdown_absolute_default_and_default_argument_are_unchanged() {
		$form_id = $this->create_text_form( '' );

		$this->assertSame( 'abs-' . $this->scope, $this->default_value( '', $form_id, null, 'dropdown', array( 'absolute_default' => 'abs-' . $this->scope ) ) );

		$atts = array( 'name' => $this->field );
		$this->assertSame( '0', SUPER_Shortcodes::get_default_value( 'text', $atts, SUPER_Common::get_form_settings( $form_id ), null, '0' ) );

		// Plain autopopulation (docs/autopopulate-fields.md) is untouched.
		$_GET     = array( $this->field => 'John' );
		$_REQUEST = $_GET;
		$this->assertSame( 'John', $this->default_value( 'author-default-' . $this->scope, $form_id ) );
	}

	/**
	 * A form with one dynamic column ("Add more", duplicate=enabled) around the probe field.
	 */
	private function create_dynamic_column_form( $author_default, $settings = array() ) {
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
					'tag'   => 'column',
					'group' => 'layout_elements',
					'data'  => array(
						'size'      => '1/1',
						'duplicate' => 'enabled',
					),
					'inner' => array(
						array(
							'tag'   => 'text',
							'group' => 'form_elements',
							'data'  => array(
								'name'  => $this->field,
								'email' => 'Probe field:',
								'value' => $author_default,
							),
							'inner' => array(),
						),
					),
				),
			)
		);
		update_post_meta( $form_id, '_super_form_settings', array_merge( array( 'save_form_progress' => 'true' ), $settings ) );
		return $form_id;
	}

	/**
	 * Store $rows as this browser's saved form progress, carried only in `_super_dynamic_data`
	 * (no flat field keys). This is what the logged-out super_save_form_progress action allows:
	 * it stores the raw $_POST['data'] (class-ajax.php:755-762), super_form_func() reads it back
	 * as entry data (class-shortcodes.php:6173-6178) and the dynamic column copies each row value
	 * into the element's `value` (class-common.php:999 via class-shortcodes.php:2663).
	 */
	private function store_dynamic_column_progress( $form_id, $rows ) {
		$key     = md5( 'session-' . $this->scope );
		$expires = time() + 600;
		update_option(
			'_sfsdata_' . $key,
			array(
				'expires'            => $expires,
				'exp_var'            => $expires,
				'progress_' . $form_id => array(
					'expires' => $expires,
					'exp_var' => $expires,
					'value'   => array( '_super_dynamic_data' => array( $this->field => $rows ) ),
				),
			),
			false
		);
		$_COOKIE['_sfs_id'] = $key;
	}

	/**
	 * The opening tag of the input named exactly $name.
	 */
	private function named_input( $html, $name ) {
		$pattern = '/<input\b[^>]*\sname="' . preg_quote( $name, '/' ) . '"[^>]*>/';
		$this->assertSame( 1, preg_match( $pattern, $html, $input ), 'input ' . $name . ' rendered once' );
		return $input[0];
	}

	/**
	 * A decoded attribute of an element; null when absent.
	 */
	private function attribute( $element, $attribute ) {
		if ( ! preg_match( '/\s' . preg_quote( $attribute, '/' ) . '="([^"]*)"/', $element, $value ) ) {
			return null;
		}
		return html_entity_decode( $value[1], ENT_QUOTES, 'UTF-8' );
	}

	public function test_dynamic_column_progress_values_render_literally() {
		$smtp_tag = '{option_super_settings;smtp_password}';
		$probe    = '[' . $this->shortcode_tag . ']';
		$form_id  = $this->create_dynamic_column_form( '{option_blogname}' );
		$this->store_dynamic_column_progress(
			$form_id,
			array(
				array( $this->field => array( 'value' => $smtp_tag ) ),
				array( $this->field => array( 'value' => $probe ) ),
				array( $this->field => array( 'value' => '{option_' . $this->custom_option . '}' ) ),
			)
		);

		$html = $this->render( $form_id );
		$this->assert_no_secret( $html );
		$this->assertStringNotContainsString( $this->shortcode_output(), $html );
		$this->assertSame( 0, $this->shortcode_calls );

		// 6.4 adaptation: the 6.4 line does not render saved dynamic column rows on the server (no
		// `_super_dynamic_data` handling in SUPER_Shortcodes::column(); the browser adds rows, and
		// SUPER.populate_form_with_entry_data() fills values through the DOM), so only the author's first
		// row is in the HTML. The saved row values never reach get_default_value() there; what is
		// asserted is that none of them resolved or ran a shortcode, and that the rendered row keeps the
		// author default (data-absolute-default, used to reset an added row) with its {option_*} resolved.
		$row1 = $this->named_input( $html, $this->field );
		$this->assertSame( 0, preg_match( '/\sname="' . preg_quote( $this->field . '_2', '/' ) . '"/', $html ) );
		$this->assertStringNotContainsString( $this->option_sentinel, $html );
		$this->assertSame( 'Probe blog ' . $this->scope, $this->attribute( $row1, 'data-absolute-default' ) );

		// The marker replace_tags_dynamic_columns() sets makes get_default_value() literal by itself.
		$extra = array( '_super_literal_value' => true );
		$this->assertSame( $smtp_tag, $this->default_value( $smtp_tag, $form_id, null, 'text', $extra ) );
		$this->assert_bracket_literal( $probe, $this->default_value( $probe, $form_id, null, 'text', $extra ) );
		$this->assertSame( 0, $this->shortcode_calls );
	}

	public function test_dynamic_column_author_default_still_resolves() {
		$form_id = $this->create_dynamic_column_form( '{option_blogname}' );

		// No saved progress: the author default resolves in the value and the absolute default.
		$html = $this->render( $form_id );
		$row  = $this->named_input( $html, $this->field );
		$this->assertSame( 'Probe blog ' . $this->scope, $this->attribute( $row, 'value' ) );
		$this->assertSame( 'Probe blog ' . $this->scope, $this->attribute( $row, 'data-absolute-default' ) );

		// A row without a value for the field keeps the author default.
		$this->store_dynamic_column_progress(
			$form_id,
			array(
				array( $this->field => array( 'value' => '' ) ),
				array( $this->field => array( 'value' => 'typed-' . $this->scope ) ),
			)
		);
		$html = $this->render( $form_id );
		// 6.4 adaptation: saved dynamic column rows are added in the browser (see
		// test_dynamic_column_progress_values_render_literally()), only the author's first row is rendered here.
		$row1 = $this->named_input( $html, $this->field );
		$this->assertSame( 'Probe blog ' . $this->scope, $this->attribute( $row1, 'value' ) );
		$this->assertSame( 'Probe blog ' . $this->scope, $this->attribute( $row1, 'data-absolute-default' ) );

		// With an empty author default the absolute default stays empty, it never becomes the row value.
		$form_id = $this->create_dynamic_column_form( '' );
		$this->store_dynamic_column_progress(
			$form_id,
			array( array( $this->field => array( 'value' => '{option_admin_email}' ) ) )
		);
		$html = $this->render( $form_id );
		$row  = $this->named_input( $html, $this->field );
		// 6.4 adaptation: the saved row is not rendered on the server, the rendered row keeps the (empty) author default
		$this->assertContains( $this->attribute( $row, 'value' ), array( null, '' ) );
		$this->assertStringNotContainsString( get_option( 'admin_email' ), $html );
		$this->assertSame( '', $this->attribute( $row, 'data-absolute-default' ) );
	}

	public function test_referer_contents_render_literally() {
		// {server_http_referrer} (class-common.php:2414) is the Referer header the visitor sends.
		$form_id  = $this->create_text_form( '{server_http_referrer}' );
		$settings = SUPER_Common::get_form_settings( $form_id );
		$probes   = array(
			'{option_admin_email}',
			'{option_super_settings;smtp_password}',
			'{option_' . $this->custom_option . '}',
			'{form_setting_smtp_password}',
			'{@' . $this->scope . '_secret}',
		);
		foreach ( $probes as $probe ) {
			$_SERVER['HTTP_REFERER'] = $probe;
			$this->assertSame( $probe, $this->default_value( '{server_http_referrer}', $form_id ), 'Referer must be literal: ' . $probe );
			$html = $this->render( $form_id );
			$this->assert_no_secret( $html );
			$this->assertSame( $probe, $this->rendered_value( $html ) );
			$this->assertSame( $probe, $this->rendered_absolute_default( $html ) );
			// E-mails and other email_tags() callers no longer resolve tags inside it either.
			$this->assertSame( 'From: ' . $probe, SUPER_Common::email_tags( 'From: {server_http_referrer}', null, $settings ) );
		}

		$probe                   = '[' . $this->shortcode_tag . ']';
		$_SERVER['HTTP_REFERER'] = $probe;
		$this->assert_bracket_literal( $probe, $this->default_value( '{server_http_referrer}', $form_id ) );
		$html = $this->render( $form_id );
		$this->assertStringNotContainsString( $this->shortcode_output(), $html );
		$this->assertSame( $probe, $this->rendered_value( $html ) );
		$this->assertSame( $probe, $this->rendered_absolute_default( $html ) );
		$this->assertSame( 0, $this->shortcode_calls );

		// A normal Referer is still inserted, also next to other tags.
		$_SERVER['HTTP_REFERER'] = 'https://example.org/page/?a=1&b=2';
		$this->assertSame( 'https://example.org/page/?a=1&b=2', $this->default_value( '{server_http_referrer}', $form_id ) );
		$this->assertSame( 'Probe blog ' . $this->scope . ' https://example.org/page/?a=1&b=2', $this->default_value( '{option_blogname} {server_http_referrer}', $form_id ) );
		$this->assertSame( 'https://example.org/page/?a=1&b=2', $this->rendered_value( $this->render( $form_id ) ) );
	}

	public function test_user_name_contents_render_literally() {
		// Any registered user sets their own first, last and display name.
		$probe   = '[' . $this->shortcode_tag . ']';
		$user_id = $this->create_actor();
		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => '{option_' . $this->custom_option . '}',
				'last_name'    => $probe,
				'display_name' => '{form_setting_smtp_password}',
			)
		);
		wp_set_current_user( $user_id );
		$user = wp_get_current_user();
		$this->assertSame( '{option_' . $this->custom_option . '}', $user->user_firstname );
		$this->assertSame( $probe, $user->user_lastname );
		$this->assertSame( '{form_setting_smtp_password}', $user->display_name );

		$form_id = $this->create_text_form( '{user_firstname} {user_lastname}' );
		$this->assertSame( '{option_' . $this->custom_option . '}', $this->default_value( '{user_firstname}', $form_id ) );
		$this->assertSame( '{form_setting_smtp_password}', $this->default_value( '{user_display}', $form_id ) );
		$this->assert_bracket_literal( $probe, $this->default_value( '{user_lastname}', $form_id ) );
		$this->assertSame( 0, $this->shortcode_calls );

		$html = $this->render( $form_id );
		$this->assert_no_secret( $html );
		$this->assertStringNotContainsString( $this->shortcode_output(), $html );
		$this->assertSame( '{option_' . $this->custom_option . '} ' . $probe, $this->rendered_value( $html ) );
		$this->assertSame( 0, $this->shortcode_calls );

		// Normal names are still inserted.
		$user_id = $this->create_actor();
		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => 'Jane',
				'last_name'    => 'Doe',
				'display_name' => 'Jane D.',
			)
		);
		wp_set_current_user( $user_id );
		$this->assertSame( 'Jane Doe / Jane D.', $this->default_value( '{user_firstname} {user_lastname} / {user_display}', $form_id ) );
		$this->assertSame( 'Jane Doe', $this->rendered_value( $this->render( $form_id ) ) );
	}
}
