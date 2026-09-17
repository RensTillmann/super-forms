<?php
/**
 * Security regressions for anonymous post-submission entry access.
 *
 * @package Super_Forms_Tests
 */

class Test_Security_Entry_Access_Common extends SUPER_Common {

	public static $cookie_calls = array();
	public static $cookie_result = true;
	public static $delete_result = null;

	protected static function set_entry_access_cookie( $name, $value, $options ) {
		self::$cookie_calls[] = array(
			'name' => $name,
			'value' => $value,
			'options' => $options,
		);
		return self::$cookie_result;
	}

	protected static function delete_entry_access_transient( $transient_key ) {
		if( self::$delete_result!==null ) return self::$delete_result;
		return parent::delete_entry_access_transient($transient_key);
	}
}

class Test_Security_Entry_Access extends WP_UnitTestCase {

	/**
	 * The WordPress test bootstrap never defines DOING_AJAX, so super-forms.php:197-232
	 * (is_request('ajax')) skips ajax_includes() and includes/class-ajax.php is never
	 * loaded. Load it explicitly so SUPER_Ajax exists for the reflection lookups and
	 * the forked handler calls below.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		if( !class_exists( 'SUPER_Ajax' ) ) {
			require_once dirname( __DIR__ ) . '/includes/class-ajax.php';
		}
	}

	private $browser_session_id;
	private $browser_session_ids = array();
	private $entry_a;
	private $entry_b;
	private $form_id;
	private $other_form_id;
	private $tokens = array();
	private $original_get;
	private $owned_attachment_ids = array();

	public function set_up() {
		parent::set_up();
		// WP_UnitTestCase restores $wp_filter per test, dropping the wp_ajax_super_*
		// registrations made when includes/class-ajax.php was loaded; init() is idempotent.
		if( !has_action( 'wp_ajax_nopriv_super_submit_form' ) ) {
			SUPER_Ajax::init();
		}
		$this->original_get = $_GET;
		$_GET = array();
		wp_set_current_user( 0 );
		$_COOKIE = array();
		Test_Security_Entry_Access_Common::$cookie_calls = array();
		Test_Security_Entry_Access_Common::$cookie_result = true;
		Test_Security_Entry_Access_Common::$delete_result = null;
		$this->browser_session_id = $this->use_browser_session('default');

		$this->form_id = self::factory()->post->create(
			array(
				'post_type' => 'super_form',
				'post_status' => 'publish',
			)
		);
		$this->other_form_id = self::factory()->post->create(
			array(
				'post_type' => 'super_form',
				'post_status' => 'publish',
			)
		);
		$this->entry_a = $this->create_entry( $this->form_id, 'Entry A' );
		$this->entry_b = $this->create_entry( $this->form_id, 'Entry B' );
		$element = array(
			'tag' => 'text',
			'group' => 'form_elements',
			'data' => array( 'name' => 'entry_secret' ),
			'inner' => array(),
		);
		update_post_meta( $this->form_id, '_super_elements', array( $element ) );
		update_post_meta( $this->other_form_id, '_super_elements', array( $element ) );
		update_post_meta(
			$this->entry_a,
			'_super_contact_entry_data',
			array( 'entry_secret' => array( 'value' => 'entry-a-' . wp_generate_uuid4() ) )
		);
		update_post_meta(
			$this->entry_b,
			'_super_contact_entry_data',
			array( 'entry_secret' => array( 'value' => 'entry-b-' . wp_generate_uuid4() ) )
		);
	}

	public function tear_down() {
		foreach( array_unique($this->tokens) as $token ) {
			delete_transient( '_super_form_entry_access_' . hash('sha256', $token) );
		}
		foreach( array_unique($this->browser_session_ids) as $browser_session_id ) {
			delete_option('_sfsdata_' . $browser_session_id);
		}
		foreach( array_reverse( array_unique( $this->owned_attachment_ids ) ) as $attachment_id ) {
			if( get_post( $attachment_id ) ) {
				wp_delete_attachment( $attachment_id, true );
			}
		}
		delete_transient( 'super_form_authenticated_entry_id_' . $this->entry_a );
		delete_transient( 'super_form_authenticated_entry_id_' . $this->entry_b );
		$_COOKIE = array();
		$_GET = $this->original_get;
		Test_Security_Entry_Access_Common::$cookie_calls = array();
		Test_Security_Entry_Access_Common::$cookie_result = true;
		Test_Security_Entry_Access_Common::$delete_result = null;
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	private function create_entry( $form_id, $title ) {
		return self::factory()->post->create(
			array(
				'post_type' => 'super_contact_entry',
				'post_status' => 'super_unread',
				'post_parent' => $form_id,
				'post_title' => $title,
			)
		);
	}

	private function use_browser_session( $label ) {
		$browser_session_id = substr(hash('sha256', $label . ':' . wp_generate_uuid4()), 0, 42);
		$now = time();
		update_option(
			'_sfsdata_' . $browser_session_id,
			array(
				'expires' => $now + 3600,
				'exp_var' => $now + 1800,
				// A live browser session always carries at least one unrelated client
				// data record. Without one, consuming a single-use capability drops the
				// record count below three and SUPER_Common::cleanupOldClientData()
				// (includes/class-common.php:686-688) deletes the whole session row. The
				// CLI SAPI can never publish a replacement cookie (headers_sent() is
				// permanently true), so the session would be unrecoverable mid-test.
				'session_marker' => array(
					'expires' => $now + 3600,
					'exp_var' => $now + 1800,
					'value' => 'session-marker-' . $label,
				),
			),
			false
		);
		$this->browser_session_ids[] = $browser_session_id;
		$_COOKIE['_sfs_id'] = $browser_session_id;
		return $browser_session_id;
	}
	private function assertRenderedInputValue( $html, $name, $value ) {
		$this->assertRenderedInputPattern( $html, $name, preg_quote( (string) $value, '/' ) );
	}

	private function renderedInputValue( $html, $name, $value_pattern ) {
		$pattern = '/<input\b[^>]*(?:name="' . preg_quote( (string) $name, '/' ) . '"[^>]*value="' . $value_pattern . '"|value="' . $value_pattern . '"[^>]*name="' . preg_quote( (string) $name, '/' ) . '")[^>]*>/';
		$this->assertSame( 1, preg_match( $pattern, $html, $matches ), $html );
		foreach( array_slice( $matches, 1 ) as $match ) {
			if( $match!=='' ) {
				return $match;
			}
		}
		return '';
	}

	private function assertRenderedInputPattern( $html, $name, $value_pattern ) {
		$pattern = '/<input\b[^>]*(?:name="' . preg_quote( (string) $name, '/' ) . '"[^>]*value="' . $value_pattern . '"|value="' . $value_pattern . '"[^>]*name="' . preg_quote( (string) $name, '/' ) . '")[^>]*>/';
		$this->assertSame( 1, preg_match( $pattern, $html ), $html );
	}


	private function use_logged_in_user( $user_id ) {
		$expiration = time() + 3600;
		$token = WP_Session_Tokens::get_instance($user_id)->create($expiration);
		$_COOKIE[LOGGED_IN_COOKIE] = wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $token);
		wp_set_current_user($user_id);
		return $token;
	}
	private function create_attachment( $basename, $contents, $mime='text/html' ) {
		$upload_dir = wp_upload_dir();
		$this->assertEmpty( $upload_dir['error'] );
		$file = trailingslashit( $upload_dir['basedir'] ) . wp_unique_filename(
			$upload_dir['basedir'],
			sanitize_file_name( $basename )
		);
		$this->assertNotFalse( file_put_contents( $file, $contents ) );
		$attachment_id = wp_insert_attachment(
			array(
				'post_author'    => 0,
				'post_mime_type' => $mime,
				'post_status'    => 'inherit',
				'post_title'     => $basename,
			),
			$file
		);
		$this->assertIsInt( $attachment_id );
		$this->assertGreaterThan( 0, $attachment_id );
		$this->owned_attachment_ids[] = $attachment_id;
		return $attachment_id;
	}
	private function run_print_request( $post ) {
		$this->require_process_forking( 'The public print capability regression requires pcntl fork, wait, and exec support.' );
		$output_file = tempnam( sys_get_temp_dir(), 'sf-print-output-' );
		$this->assertNotFalse( $output_file );
		$pid = pcntl_fork();
		$this->assertNotSame( -1, $pid );
		if( $pid===0 ) {
			// The fork inherits this process' non-persistent object cache, which still
			// holds the pre-request option values; drop it so the handler reads the
			// current rows (single-use capability consumption must be visible).
			wp_cache_flush();
			$returned = false;
			ob_start(
				static function ( $buffer ) use ( $output_file ) {
					file_put_contents( $output_file, $buffer, FILE_APPEND | LOCK_EX );
					return '';
				}
			);
			register_shutdown_function(
				static function () use ( &$returned ) {
					$last_error = error_get_last();
					$fatal = $last_error && in_array(
						$last_error['type'],
						array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ),
						true
					);
					$status = ( ! $returned && ! $fatal ) ? 0 : 97;
					while( ob_get_level() > 0 ) {
						@ob_end_flush();
					}
					pcntl_exec( PHP_BINARY, array( '-r', 'exit(' . $status . ');' ) );
				}
			);
			$_POST = $post;
			$_REQUEST = $post;
			try {
				SUPER_Ajax::print_custom_html();
				$returned = true;
			} catch ( Throwable $e ) {
				echo get_class( $e ) . ': ' . $e->getMessage();
				$returned = true;
			}
			exit(97);
		}
		$status = 0;
		pcntl_waitpid( $pid, $status );
		global $wpdb;
		if( isset($wpdb) && method_exists($wpdb, 'check_connection') ) {
			$wpdb->check_connection(false);
		}
		wp_cache_flush();
		$output = file_get_contents( $output_file );
		unlink( $output_file );
		return array(
			'output' => $output===false ? '' : $output,
			'status' => pcntl_wifexited($status) ? pcntl_wexitstatus($status) : null,
		);
	}
	private function decode_output_message_response( $response ) {
		$payload = json_decode( $response, true );
		$this->assertIsArray( $payload, $response );
		return $payload;
	}
	private function strict_security_pcntl_required() {
		$flag = getenv( 'SUPER_FORMS_STRICT_SECURITY_TESTS' );
		return is_string($flag) && $flag!=='' && $flag!=='0' && strtolower($flag)!=='false';
	}
	private function require_process_forking( $message ) {
		if( function_exists('pcntl_fork') && function_exists('pcntl_waitpid') && function_exists('pcntl_exec') ) {
			return;
		}
		if( $this->strict_security_pcntl_required() ) {
			$this->fail( $message );
		}
		$this->markTestSkipped( $message );
	}
	private function run_populate_request( $post ) {
		$this->require_process_forking( 'The public populate regression requires pcntl fork, wait, and exec support.' );
		$output_file = tempnam( sys_get_temp_dir(), 'sf-populate-output-' );
		$warnings_file = tempnam( sys_get_temp_dir(), 'sf-populate-warnings-' );
		$this->assertNotFalse( $output_file );
		$this->assertNotFalse( $warnings_file );
		$pid = pcntl_fork();
		$this->assertNotSame( -1, $pid );
		if( $pid===0 ) {
			wp_cache_flush();
			$returned = false;
			ob_start( static function( $buffer ) use ( $output_file ) {
				file_put_contents( $output_file, $buffer, FILE_APPEND | LOCK_EX );
				return '';
			} );
			$previous_error_handler = null;
			$previous_error_handler = set_error_handler( static function( $severity, $message, $file, $line ) use ( $warnings_file, &$previous_error_handler ) {
				if( in_array( $severity, array( E_WARNING, E_NOTICE, E_USER_WARNING, E_USER_NOTICE ), true ) ) {
					file_put_contents( $warnings_file, $message . "\n", FILE_APPEND | LOCK_EX );
					return true;
				}
				if( is_callable( $previous_error_handler ) ) {
					call_user_func( $previous_error_handler, $severity, $message, $file, $line );
					return true;
				}
				return false;
			} );
			register_shutdown_function( static function() use ( &$returned ) {
				$last_error = error_get_last();
				$fatal = $last_error && in_array(
					$last_error['type'],
					array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ),
					true
				);
				$status = ( ! $returned && ! $fatal ) ? 0 : 97;
				while( ob_get_level() > 0 ) {
					@ob_end_flush();
				}
				pcntl_exec( PHP_BINARY, array( '-r', 'exit(' . $status . ');' ) );
			} );
			$_POST = $post;
			$_REQUEST = $post;
			try {
				SUPER_Ajax::populate_form_data();
				$returned = true;
			} catch( Throwable $e ) {
				echo get_class($e) . ': ' . $e->getMessage();
				$returned = true;
			}
			restore_error_handler();
			exit(97);
		}
		$status = 0;
		pcntl_waitpid( $pid, $status );
		global $wpdb;
		if( isset($wpdb) && method_exists($wpdb, 'check_connection') ) {
			$wpdb->check_connection(false);
		}
		wp_cache_flush();
		$output = file_get_contents( $output_file );
		$warnings = file_get_contents( $warnings_file );
		unlink( $output_file );
		unlink( $warnings_file );
		return array(
			'output' => $output===false ? '' : $output,
			'warnings' => $warnings===false ? '' : $warnings,
			'status' => pcntl_wifexited($status) ? pcntl_wexitstatus($status) : null,
		);
	}
	private function run_nonce_request( $post ) {
		$this->require_process_forking( 'The capability bootstrap regression requires pcntl fork, wait, and exec support.' );
		$output_file = tempnam( sys_get_temp_dir(), 'sf-nonce-output-' );
		$this->assertNotFalse( $output_file );
		$pid = pcntl_fork();
		$this->assertNotSame( -1, $pid );
		if( $pid===0 ) {
			wp_cache_flush();
			$returned = false;
			ob_start(
				static function ( $buffer ) use ( $output_file ) {
					file_put_contents( $output_file, $buffer, FILE_APPEND | LOCK_EX );
					return '';
				}
			);
			register_shutdown_function(
				static function () use ( &$returned ) {
					$last_error = error_get_last();
					$fatal = $last_error && in_array(
						$last_error['type'],
						array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ),
						true
					);
					$status = ( ! $returned && ! $fatal ) ? 0 : 97;
					while( ob_get_level() > 0 ) {
						@ob_end_flush();
					}
					pcntl_exec( PHP_BINARY, array( '-r', 'exit(' . $status . ');' ) );
				}
			);
			$_POST = $post;
			$_REQUEST = $post;
			try {
				SUPER_Ajax::create_nonce();
				$returned = true;
			} catch ( Throwable $e ) {
				echo get_class( $e ) . ': ' . $e->getMessage();
				$returned = true;
			}
			exit(97);
		}
		$status = 0;
		pcntl_waitpid( $pid, $status );
		global $wpdb;
		if( isset($wpdb) && method_exists($wpdb, 'check_connection') ) {
			$wpdb->check_connection(false);
		}
		wp_cache_flush();
		$output = file_get_contents( $output_file );
		unlink( $output_file );
		return array(
			'output' => $output===false ? '' : $output,
			'status' => pcntl_wifexited($status) ? pcntl_wexitstatus($status) : null,
		);
	}
	private function decode_public_populate_response( $response ) {
		$payload = json_decode( $response, true );
		$this->assertIsArray( $payload, $response );
		$capability = '';
		if( isset( $payload['_super_populate_capability'] ) && is_string( $payload['_super_populate_capability'] ) ) {
			$capability = $payload['_super_populate_capability'];
			unset( $payload['_super_populate_capability'] );
		}
		$capability_rejected = !empty( $payload['_super_capability_rejected'] );
		unset( $payload['_super_capability_rejected'] );
		return array(
			'data' => $payload,
			'capability' => $capability,
			'capability_rejected' => $capability_rejected,
		);
	}

	private function issue( $entry_id ) {
		Test_Security_Entry_Access_Common::$cookie_calls = array();
		$this->assertTrue( Test_Security_Entry_Access_Common::issue_entry_access_credential(get_post($entry_id)) );
		$this->assertNotEmpty( Test_Security_Entry_Access_Common::$cookie_calls );
		$call = Test_Security_Entry_Access_Common::$cookie_calls[0];
		$this->tokens[] = $call['value'];
		return $call;
	}

	private function disclose_entry( $form_id, $entry_id ) {
		$_GET = array( 'contact_entry_id' => (string) $entry_id );
		return SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
	}

	public function test_guessed_id_and_legacy_transient_do_not_authorize() {
		set_transient( 'super_form_authenticated_entry_id_' . $this->entry_a, $this->entry_a, 30 );

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertSame(
			$this->entry_a,
			get_transient('super_form_authenticated_entry_id_' . $this->entry_a)
		);

		$cookie_name = 'super_form_entry_access_' . $this->entry_a;
		$_COOKIE[$cookie_name] = str_repeat('a', 64);
		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertArrayNotHasKey( $cookie_name, $_COOKIE );
	}

	public function test_malformed_cookie_fails_and_is_removed() {
		$cookie_name = 'super_form_entry_access_' . $this->entry_a;
		$_COOKIE[$cookie_name] = 'not-a-valid-token';

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertArrayNotHasKey( $cookie_name, $_COOKIE );
	}

	public function test_public_entry_disclosure_requires_one_credential_for_one_exact_entry_and_form() {
		$entry_a_data = SUPER_Data_Access::get_entry_data( $this->entry_a );
		$entry_b_data = SUPER_Data_Access::get_entry_data( $this->entry_b );
		$entry_a_secret = $entry_a_data['entry_secret']['value'];
		$entry_b_secret = $entry_b_data['entry_secret']['value'];

		$call = $this->issue( $this->entry_a );
		$_COOKIE['super_form_entry_access_' . $this->entry_b] = $call['value'];
		$sibling_output = $this->disclose_entry( $this->form_id, $this->entry_b );
		$this->assertStringNotContainsString( $entry_b_secret, $sibling_output );
		$this->assertStringNotContainsString( $entry_a_secret, $sibling_output );

		$call = $this->issue( $this->entry_a );
		$_COOKIE[$call['name']] = $call['value'];
		$wrong_form_output = $this->disclose_entry( $this->other_form_id, $this->entry_a );
		$this->assertStringNotContainsString( $entry_a_secret, $wrong_form_output );

		$call = $this->issue( $this->entry_a );
		$_COOKIE[$call['name']] = $call['value'];
		$authorized_output = $this->disclose_entry( $this->form_id, $this->entry_a );
		$this->assertStringContainsString( $entry_a_secret, $authorized_output );
		$this->assertStringNotContainsString( $entry_b_secret, $authorized_output );

		$replay_output = $this->disclose_entry( $this->form_id, $this->entry_a );
		$this->assertStringNotContainsString( $entry_a_secret, $replay_output );
	}
	public function test_explicit_non_listings_update_refuses_a_cross_user_target_without_falling_back_to_the_latest_owned_entry() {
		$owner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$other_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array(
				'update_contact_entry' => 'true',
				'retrieve_last_entry_data' => 'true',
			)
		);
		$target_entry_id = self::factory()->post->create(
			array(
				'post_type' => 'super_contact_entry',
				'post_status' => 'super_unread',
				'post_parent' => $this->form_id,
				'post_author' => $other_id,
			)
		);
		$owned_entry_id = self::factory()->post->create(
			array(
				'post_type' => 'super_contact_entry',
				'post_status' => 'super_unread',
				'post_parent' => $this->form_id,
				'post_author' => $owner_id,
			)
		);
		SUPER_Data_Access::update_entry_data(
			$target_entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'other-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);
		SUPER_Data_Access::update_entry_data(
			$owned_entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'owner-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);

		wp_set_current_user( $owner_id );
		$_GET = array( 'contact_entry_id' => (string) $target_entry_id );
		$output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $this->form_id ) );

		$this->assertStringNotContainsString( 'other-secret-', $output );
		$this->assertStringNotContainsString( 'owner-secret-', $output );
		$this->assertFalse( SUPER_Common::getClientData( 'update_contact_entry_' . $this->form_id . '_' . $target_entry_id ) );
	}
	public function test_explicit_contact_entry_id_prefills_authorized_read_only_entry_without_issuing_an_update_grant() {
		$owner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array( 'update_contact_entry' => '' )
		);
		$entry_id = self::factory()->post->create(
			array(
				'post_type' => 'super_contact_entry',
				'post_status' => 'super_unread',
				'post_parent' => $this->form_id,
				'post_author' => $owner_id,
			)
		);
		SUPER_Data_Access::update_entry_data(
			$entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'readonly-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);

		wp_set_current_user( $owner_id );
		$_GET = array( 'contact_entry_id' => (string) $entry_id );
		$output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $this->form_id ) );

		$this->assertStringContainsString( 'readonly-secret-', $output );
		$this->assertStringNotContainsString( 'name="hidden_contact_entry_id"', $output );
		$this->assertFalse( SUPER_Common::getClientData( 'update_contact_entry_' . $this->form_id . '_' . $entry_id ) );
	}
	public function test_shortcode_override_enables_update_grants_for_retrieved_last_entry_data() {
		$owner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array(
				'update_contact_entry' => '',
				'retrieve_last_entry_data' => 'true',
			)
		);
		$entry_id = self::factory()->post->create(
			array(
				'post_type' => 'super_contact_entry',
				'post_status' => 'super_unread',
				'post_parent' => $this->form_id,
				'post_author' => $owner_id,
			)
		);
		SUPER_Data_Access::update_entry_data(
			$entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'override-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);

		wp_set_current_user( $owner_id );
		$output = SUPER_Shortcodes::super_form_func( array(
			'id' => (string) $this->form_id,
			'_setting_update_contact_entry' => 'true',
		) );

		$this->assertStringContainsString( 'override-secret-', $output );
		$this->assertRenderedInputValue( $output, 'hidden_contact_entry_id', $entry_id );
		$grant_name = 'update_contact_entry_' . $this->form_id . '_' . $entry_id;
		$this->assertSame( SUPER_Common::current_entry_update_grant_value(), SUPER_Common::getClientData( $grant_name ) );
	}
	public function test_non_listings_existing_entry_submit_requires_the_exact_render_grant_even_for_owner_and_administrator() {
		$owner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array( 'update_contact_entry' => 'true' )
		);
		$entry_id = self::factory()->post->create(
			array(
				'post_type' => 'super_contact_entry',
				'post_status' => 'super_unread',
				'post_parent' => $this->form_id,
				'post_author' => $owner_id,
			)
		);
		$method = new ReflectionMethod( 'SUPER_Ajax', 'submission_entry_update_is_authorized' );
		$method->setAccessible( true );
		$this->use_logged_in_user($owner_id);
		$this->assertFalse( $method->invoke( null, $entry_id, '', $this->form_id, array( 'update_contact_entry' => 'true' ) ) );
		$this->use_logged_in_user($admin_id);
		$this->assertFalse( $method->invoke( null, $entry_id, '', $this->form_id, array( 'update_contact_entry' => 'true' ) ) );
		SUPER_Common::setClientData( array(
			'name' => 'update_contact_entry_' . $this->form_id . '_' . $entry_id,
			'value' => SUPER_Common::current_entry_update_grant_value(),
			'force' => true,
		) );
		$this->assertTrue( $method->invoke( null, $entry_id, '', $this->form_id, array( 'update_contact_entry' => 'true' ) ) );
	}
	public function test_retrieve_last_entry_cross_form_update_uses_the_exact_render_grant_for_the_retrieved_entry() {
		$owner_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		update_post_meta(
			$this->form_id,
			'_super_form_settings',
			array(
				'update_contact_entry' => 'true',
				'retrieve_last_entry_data' => 'true',
				'retrieve_last_entry_form' => (string) $this->other_form_id,
			)
		);
		$foreign_entry_id = self::factory()->post->create(
			array(
				'post_type' => 'super_contact_entry',
				'post_status' => 'super_unread',
				'post_parent' => $this->other_form_id,
				'post_author' => $owner_id,
			)
		);
		SUPER_Data_Access::update_entry_data(
			$foreign_entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'foreign-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);
		$this->use_logged_in_user( $owner_id );
		$output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $this->form_id ) );
		$this->assertStringContainsString( 'foreign-secret-', $output );
		$this->assertRenderedInputValue( $output, 'hidden_contact_entry_id', $foreign_entry_id );
		$grant_name = 'update_contact_entry_' . $this->form_id . '_' . $foreign_entry_id;
		$this->assertSame( SUPER_Common::current_entry_update_grant_value(), SUPER_Common::getClientData( $grant_name ) );
		$method = new ReflectionMethod( 'SUPER_Ajax', 'submission_entry_update_is_authorized' );
		$method->setAccessible( true );
		$this->assertTrue(
			$method->invoke(
				null,
				$foreign_entry_id,
				'',
				$this->form_id,
				array(
					'update_contact_entry' => 'true',
					'retrieve_last_entry_form' => (string) $this->other_form_id,
				)
			)
		);
		$this->assertFalse(
			$method->invoke(
				null,
				$foreign_entry_id,
				'',
				$this->form_id,
				array( 'update_contact_entry' => 'true' )
			)
		);
	}
	public function test_session_refresh_preserves_existing_payload_and_day_long_update_grants() {
		$grant_name = 'update_contact_entry_' . $this->form_id . '_' . $this->entry_a;
		SUPER_Common::setClientData( array( 'name' => 'preserved', 'value' => 'marker', 'force' => true ) );
		SUPER_Common::setClientData( array(
			'name' => $grant_name,
			'value' => SUPER_Common::current_entry_update_grant_value(),
			'force' => true,
		) );

		$session_id = $_COOKIE['_sfs_id'];
		$stored = get_option( '_sfsdata_' . $session_id );
		$this->assertSame( 'marker', $stored['preserved']['value'] );
		$this->assertGreaterThanOrEqual( time() + DAY_IN_SECONDS - 5, $stored[ $grant_name ]['expires'] );

		$stored['exp_var'] = time() - 1;
		update_option( '_sfsdata_' . $session_id, $stored, false );

		$this->assertSame( $session_id, SUPER_Common::startClientSession( array( 'force' => true ) ) );
		$refreshed = get_option( '_sfsdata_' . $session_id );
		$this->assertSame( 'marker', $refreshed['preserved']['value'] );
		$this->assertSame( $stored['preserved'], $refreshed['preserved'] );
		$this->assertSame( SUPER_Common::current_entry_update_grant_value(), $refreshed[ $grant_name ]['value'] );
		$this->assertGreaterThan( time(), $refreshed['exp_var'] );
	}
	public function test_missing_server_record_is_never_adopted_and_leaves_no_session_row_behind() {
		global $wpdb;
		$presented = substr( hash( 'sha256', 'missing-session:' . wp_generate_uuid4() ), 0, 42 );
		delete_option( '_sfsdata_' . $presented );
		$_COOKIE['_sfs_id'] = $presented;
		$before = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
				$wpdb->esc_like( '_sfsdata_' ) . '%'
			)
		);
		$issued = SUPER_Common::startClientSession( array( 'force' => true ) );
		// A presented cookie without a server record is never adopted:
		// includes/class-common.php:588-599 mints a fresh token instead of trusting the
		// client value. Publishing that token needs a Set-Cookie header, which the CLI
		// SAPI can never send (headers_sent() is permanently true, so publish_session()
		// at includes/class-common.php:560-577 refuses), and the hardened path then
		// fails closed and rolls the freshly written record back.
		$this->assertNotSame( $presented, $issued );
		$this->assertFalse( $issued );
		$this->assertFalse( get_option( '_sfsdata_' . $presented, false ) );
		$this->assertArrayNotHasKey( '_sfs_id', $_COOKIE );
		$after = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
				$wpdb->esc_like( '_sfsdata_' ) . '%'
			)
		);
		$this->assertSame( $before, $after );
	}
	public function test_rendered_print_button_requires_the_current_session_and_single_use_capability() {
		$template_id = $this->create_attachment( 'print-template.html', '<div>Printable {entry_secret}</div>' );
		$other_template_id = $this->create_attachment( 'other-template.html', '<div>Wrong file</div>' );
		$form_id = self::factory()->post->create(
			array(
				'post_type' => 'super_form',
				'post_status' => 'publish',
			)
		);
		update_post_meta(
			$form_id,
			'_super_elements',
			array(
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array( 'name' => 'entry_secret' ),
					'inner' => array(),
				),
				array(
					'tag' => 'button',
					'group' => 'form_elements',
					'data' => array(
						'action' => 'print',
						'name' => 'Print',
						'print_custom' => 'true',
						'print_file' => $template_id,
					),
					'inner' => array(),
				),
			)
		);
		$output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
		$file_matches = array( '', $this->renderedInputValue( $output, 'print_file', '(\d+)' ) );
		$this->assertSame( $template_id, absint( $file_matches[1] ) );
		$capability_matches = array( '', $this->renderedInputValue( $output, 'print_capability', '([a-f0-9]{64})' ) );
		$authorized_session = $_COOKIE['_sfs_id'];
		$secret = 'print-secret-' . wp_generate_uuid4();
		$post = array(
			'action' => 'super_print_custom_html',
			'file_id' => (string) $template_id,
			'capability' => $capability_matches[1],
			'data' => array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => $secret,
					'type' => 'text',
				),
				'hidden_form_id' => array(
					'name' => 'hidden_form_id',
					'value' => (string) $form_id,
					'type' => 'form_id',
				),
			),
		);
		$template_url = wp_get_attachment_url( $template_id );
		$this->assertIsString( $template_url );
		$fetch = static function( $preempt, $args, $url ) use ( $template_url ) {
			if( $url!==$template_url ) {
				return false;
			}
			return array(
				'headers' => array(),
				'body' => '<div>Printable {entry_secret}</div>',
				'response' => array(
					'code' => 200,
					'message' => 'OK',
				),
				'cookies' => array(),
				'filename' => null,
			);
		};
		add_filter( 'pre_http_request', $fetch, 10, 3 );
		try {
			$missing = $post;
			unset( $missing['capability'] );
			$missing_result = $this->run_print_request( $missing );
			$this->assertSame( 0, $missing_result['status'], $missing_result['output'] );
			$missing_payload = $this->decode_output_message_response( $missing_result['output'] );
			$this->assertTrue( $missing_payload['error'] );
			$this->assertStringContainsString( 'invalid form data', strtolower( wp_strip_all_tags( $missing_payload['msg'] ) ) );
			$_COOKIE['_sfs_id'] = $authorized_session;
			$success = $this->run_print_request( $post );
			$this->assertSame( 0, $success['status'], $success['output'] );
			$this->assertStringContainsString( 'Printable ' . $secret, $success['output'] );
			$replay = $this->run_print_request( $post );
			$this->assertSame( 0, $replay['status'], $replay['output'] );
			$replay_payload = $this->decode_output_message_response( $replay['output'] );
			$this->assertTrue( $replay_payload['error'] );
			$_COOKIE['_sfs_id'] = $authorized_session;
			$fresh_output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
			$fresh_matches = array( '', $this->renderedInputValue( $fresh_output, 'print_capability', '([a-f0-9]{64})' ) );
			$wrong_file = $post;
			$wrong_file['capability'] = $fresh_matches[1];
			$wrong_file['file_id'] = (string) $other_template_id;
			$wrong_file_result = $this->run_print_request( $wrong_file );
			$this->assertSame( 0, $wrong_file_result['status'], $wrong_file_result['output'] );
			$wrong_file_payload = $this->decode_output_message_response( $wrong_file_result['output'] );
			$this->assertTrue( $wrong_file_payload['error'] );
			$_COOKIE['_sfs_id'] = $authorized_session;
			$wrong_session_output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
			$wrong_session_matches = array( '', $this->renderedInputValue( $wrong_session_output, 'print_capability', '([a-f0-9]{64})' ) );
			$this->use_browser_session('print-other');
			$wrong_session = $post;
			$wrong_session['capability'] = $wrong_session_matches[1];
			$wrong_session_result = $this->run_print_request( $wrong_session );
			$this->assertSame( 0, $wrong_session_result['status'], $wrong_session_result['output'] );
			$wrong_session_payload = $this->decode_output_message_response( $wrong_session_result['output'] );
			$this->assertTrue( $wrong_session_payload['error'] );
		} finally {
			remove_filter( 'pre_http_request', $fetch, 10 );
		}
	}
	public function test_rendered_public_search_uses_the_current_form_and_real_populate_capability() {
		$lookup_form_id = self::factory()->post->create(
			array(
				'post_type' => 'super_form',
				'post_status' => 'publish',
			)
		);
		$outside_form_id = self::factory()->post->create(
			array(
				'post_type' => 'super_form',
				'post_status' => 'publish',
			)
		);
		update_post_meta(
			$lookup_form_id,
			'_super_elements',
			array(
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array(
						'name' => 'lookup_title',
						'enable_search' => 'true',
						'search_method' => 'equals',
					),
					'inner' => array(),
				),
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array( 'name' => 'entry_secret' ),
					'inner' => array(),
				),
			)
		);
		update_post_meta(
			$outside_form_id,
			'_super_elements',
			array(
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array( 'name' => 'entry_secret' ),
					'inner' => array(),
				),
			)
		);
		$lookup_entry_id = $this->create_entry( $lookup_form_id, 'Shared lookup title' );
		$outside_entry_id = $this->create_entry( $outside_form_id, 'Shared lookup title' );
		SUPER_Data_Access::update_entry_data(
			$lookup_entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'lookup-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);
		SUPER_Data_Access::update_entry_data(
			$outside_entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'outside-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);
		$_GET = array( 'lookup_title' => 'Shared lookup title' );
		$output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $lookup_form_id ) );
		$this->assertStringNotContainsString( 'Warning', $output );
		$this->assertStringContainsString( 'lookup-secret-', $output );
		$this->assertStringNotContainsString( 'outside-secret-', $output );
		$this->assertSame(
			1,
			preg_match( '/data-search-capability="([a-f0-9]{64})"/', $output, $matches )
		);
		$missing = $this->run_populate_request(
			array(
				'form_id' => (string) $lookup_form_id,
				'field_name' => 'lookup_title',
				'method' => 'equals',
				'skip' => '',
				'value' => 'Shared lookup title',
			)
		);
		$this->assertSame( 0, $missing['status'], $missing['output'] );
		$this->assertSame( '', $missing['warnings'] );
		$missing_payload = $this->decode_public_populate_response( $missing['output'] );
		$this->assertTrue( $missing_payload['capability_rejected'] );
		$this->assertSame( array(), $missing_payload['data'] );
		$result = $this->run_populate_request(
			array(
				'capability' => $matches[1],
				'form_id' => (string) $lookup_form_id,
				'field_name' => 'lookup_title',
				'method' => 'equals',
				'skip' => '',
				'value' => 'Shared lookup title',
			)
		);
		$this->assertSame( 0, $result['status'], $result['output'] );
		$this->assertSame( '', $result['warnings'] );
		$resolved = $this->decode_public_populate_response( $result['output'] );
		$payload = $resolved['data'];
		$this->assertIsArray( $payload );
		$this->assertStringContainsString( 'lookup-secret-', $payload['entry_secret']['value'] );
		$this->assertStringNotContainsString( 'outside-secret-', $payload['entry_secret']['value'] );
		$this->assertSame( $lookup_entry_id, absint( $payload['hidden_contact_entry_id']['value'] ) );
		$this->assertFalse( SUPER_Common::getClientData( 'update_contact_entry_' . $lookup_form_id . '_' . $lookup_entry_id ) );
		$this->assertSame( 'Shared lookup title', $payload['hidden_contact_entry_title']['value'] );
		$next_capability = $resolved['capability'];
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $next_capability );
		$this->assertNotSame( $matches[1], $next_capability );
		$replay = $this->run_populate_request(
			array(
				'capability' => $matches[1],
				'form_id' => (string) $lookup_form_id,
				'field_name' => 'lookup_title',
				'method' => 'equals',
				'skip' => '',
				'value' => 'Shared lookup title',
			)
		);
		$this->assertSame( 0, $replay['status'], $replay['output'] );
		$replay_payload = $this->decode_public_populate_response( $replay['output'] );
		$this->assertTrue( $replay_payload['capability_rejected'] );
		$this->assertSame( array(), $replay_payload['data'] );
		$fresh_output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $lookup_form_id ) );
		$this->assertSame(
			1,
			preg_match( '/data-search-capability="([a-f0-9]{64})"/', $fresh_output, $fresh_matches )
		);
		$this->use_browser_session('other');
		$wrong_session = $this->run_populate_request(
			array(
				'capability' => $fresh_matches[1],
				'form_id' => (string) $lookup_form_id,
				'field_name' => 'lookup_title',
				'method' => 'equals',
				'skip' => '',
				'value' => 'Shared lookup title',
			)
		);
		$this->assertSame( 0, $wrong_session['status'], $wrong_session['output'] );
		$wrong_session_payload = $this->decode_public_populate_response( $wrong_session['output'] );
		$this->assertTrue( $wrong_session_payload['capability_rejected'] );
		$this->assertSame( array(), $wrong_session_payload['data'] );
	}
	public function test_nonce_refresh_endpoint_requires_the_render_nonce_to_mint_public_search_capability() {
		update_post_meta(
			$this->form_id,
			'_super_elements',
			array(
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array(
						'name' => 'lookup_title',
						'enable_search' => 'true',
						'search_method' => 'equals',
					),
					'inner' => array(),
				),
			)
		);
		$missing = $this->run_nonce_request(
			array(
				'form_id' => (string) $this->form_id,
				'field_name' => 'lookup_title',
				'method' => 'equals',
				'skip' => '',
			)
		);
		$this->assertSame( 0, $missing['status'], $missing['output'] );
		$missing_payload = json_decode( $missing['output'], true );
		$this->assertIsArray( $missing_payload );
		$this->assertArrayNotHasKey( 'capability', $missing_payload );
		$wrong = $this->run_nonce_request(
			array(
				'form_id' => (string) $this->form_id,
				'field_name' => 'lookup_title',
				'method' => 'equals',
				'skip' => '',
				'nonce' => wp_create_nonce( 'different-create-nonce-action' ),
			)
		);
		$this->assertSame( 0, $wrong['status'], $wrong['output'] );
		$wrong_payload = json_decode( $wrong['output'], true );
		$this->assertIsArray( $wrong_payload );
		$this->assertArrayNotHasKey( 'capability', $wrong_payload );
	}

	public function test_nonce_refresh_endpoint_bootstraps_public_search_capabilities_when_render_could_not_mint_them() {
		update_post_meta(
			$this->form_id,
			'_super_elements',
			array(
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array(
						'name' => 'lookup_title',
						'enable_search' => 'true',
						'search_method' => 'equals',
					),
					'inner' => array(),
				),
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array( 'name' => 'entry_secret' ),
					'inner' => array(),
				),
			)
		);
		$lookup_entry_id = $this->create_entry( $this->form_id, 'Bootstrap search title' );
		SUPER_Data_Access::update_entry_data(
			$lookup_entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'bootstrap-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);
		global $wpdb;
		$session_options_before = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
				$wpdb->esc_like( '_sfsdata_' ) . '%'
			)
		);
		// The render could not mint a capability (no `data-search-capability` was read
		// here); the refresh endpoint must mint both the CSRF nonce and the populate
		// capability into the session the browser presents. A browser presenting *no*
		// session at all cannot be exercised under the CLI SAPI: minting one requires a
		// Set-Cookie header and headers_sent() is permanently true, so
		// includes/class-common.php:610-617 fails closed by design.
		$session_id = $_COOKIE['_sfs_id'];
		$bootstrap = $this->run_nonce_request(
			array(
				'form_id' => (string) $this->form_id,
				'field_name' => 'lookup_title',
				'method' => 'equals',
				'skip' => '',
				'nonce' => wp_create_nonce( 'super_create_nonce_' . $this->form_id ),
			)
		);
		$this->assertSame( 0, $bootstrap['status'], $bootstrap['output'] );
		$payload = json_decode( $bootstrap['output'], true );
		$this->assertIsArray( $payload );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{96}$/D', $payload['sf_nonce'] );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $payload['capability'] );
		$session_options_after = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name",
				$wpdb->esc_like( '_sfsdata_' ) . '%'
			)
		);
		// No second session row: the minted artifacts are bound to the presented session.
		$this->assertSame( $session_options_before, $session_options_after );
		$stored_session = get_option( '_sfsdata_' . $session_id );
		$this->assertIsArray( $stored_session );
		$this->assertSame( $payload['sf_nonce'], $stored_session['sf_nonce']['value'] );
		$this->assertArrayHasKey(
			'populate_form_data_' . hash( 'sha256', $payload['capability'] ),
			$stored_session
		);
		$result = $this->run_populate_request(
			array(
				'capability' => $payload['capability'],
				'form_id' => (string) $this->form_id,
				'field_name' => 'lookup_title',
				'method' => 'equals',
				'skip' => '',
				'value' => 'Bootstrap search title',
			)
		);
		$this->assertSame( 0, $result['status'], $result['output'] );
		$resolved = $this->decode_public_populate_response( $result['output'] );
		$payload = $resolved['data'];
		$this->assertIsArray( $payload );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $resolved['capability'] );
		$this->assertStringContainsString( 'bootstrap-secret-', $payload['entry_secret']['value'] );
	}
	public function test_rendered_wc_order_population_stays_within_the_current_form_scope_and_capability_contract() {
		$lookup_form_id = self::factory()->post->create(
			array(
				'post_type' => 'super_form',
				'post_status' => 'publish',
			)
		);
		$outside_form_id = self::factory()->post->create(
			array(
				'post_type' => 'super_form',
				'post_status' => 'publish',
			)
		);
		update_post_meta(
			$lookup_form_id,
			'_super_elements',
			array(
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array(
						'name' => 'order_lookup',
						'wc_order_search' => 'true',
						'wc_order_search_method' => 'equals',
						'wc_order_search_filterby' => '_billing_email',
						'wc_order_search_populate' => 'true',
					),
					'inner' => array(),
				),
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array( 'name' => 'entry_secret' ),
					'inner' => array(),
				),
			)
		);
		update_post_meta(
			$outside_form_id,
			'_super_elements',
			array(
				array(
					'tag' => 'text',
					'group' => 'form_elements',
					'data' => array( 'name' => 'entry_secret' ),
					'inner' => array(),
				),
			)
		);
		update_post_meta( $lookup_form_id, '_super_form_settings', array( 'update_contact_entry' => 'true' ) );
		$order_id = self::factory()->post->create(
			array(
				'post_type' => 'shop_order',
				'post_status' => 'publish',
			)
		);
		// A real WooCommerce order always carries billing postmeta, and the scoped
		// lookup query (includes/class-shortcodes.php:3224-3232) INNER JOINs the order's
		// postmeta, so a bare `shop_order` post would match nothing.
		update_post_meta( $order_id, '_billing_email', 'order-customer@example.com' );
		$lookup_entry_id = $this->create_entry( $lookup_form_id, 'Lookup order linked entry' );
		$outside_entry_id = $this->create_entry( $outside_form_id, 'Outside order linked entry' );
		SUPER_Data_Access::update_entry_data(
			$lookup_entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'lookup-order-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);
		SUPER_Data_Access::update_entry_data(
			$outside_entry_id,
			array(
				'entry_secret' => array(
					'name' => 'entry_secret',
					'value' => 'outside-order-secret-' . wp_generate_uuid4(),
					'type' => 'text',
				),
			)
		);
		update_post_meta( $lookup_entry_id, '_super_contact_entry_wc_order_id', $order_id );
		update_post_meta( $outside_entry_id, '_super_contact_entry_wc_order_id', $order_id );
		$_GET = array( 'order_lookup' => (string) $order_id );
		$output = SUPER_Shortcodes::super_form_func( array( 'id' => (string) $lookup_form_id ) );
		$this->assertStringNotContainsString( 'Warning', $output );
		$this->assertStringContainsString( 'lookup-order-secret-', $output );
		$this->assertStringNotContainsString( 'outside-order-secret-', $output );
		$this->assertSame( 1, preg_match( '/data-wcosc="([a-f0-9]{64})"/', $output, $matches ) );
		$missing = $this->run_populate_request(
			array(
				'form_id' => (string) $lookup_form_id,
				'field_name' => 'order_lookup',
				'method' => 'wc_order_id',
				'skip' => '',
				'order_id' => (string) $order_id,
			)
		);
		$this->assertSame( 0, $missing['status'], $missing['output'] );
		$this->assertSame( '', $missing['warnings'] );
		$missing_payload = $this->decode_public_populate_response( $missing['output'] );
		$this->assertTrue( $missing_payload['capability_rejected'] );
		$this->assertSame( array(), $missing_payload['data'] );
		$result = $this->run_populate_request(
			array(
				'capability' => $matches[1],
				'form_id' => (string) $lookup_form_id,
				'field_name' => 'order_lookup',
				'method' => 'wc_order_id',
				'skip' => '',
				'order_id' => (string) $order_id,
			)
		);
		$this->assertSame( 0, $result['status'], $result['output'] );
		$this->assertSame( '', $result['warnings'] );
		$resolved = $this->decode_public_populate_response( $result['output'] );
		$payload = $resolved['data'];
		$this->assertIsArray( $payload );
		// A WooCommerce-linked entry can never be updated through a submission
		// (includes/class-ajax.php:5299-5300), so the WC populate branch deliberately
		// keeps the entry id out of the response and mints no update grant: it passes
		// `$preserve_entry_id` as false (includes/class-ajax.php:1299-1304) and
		// public_populate_response_data refuses the grant for WC-linked entries
		// (includes/class-ajax.php:1182-1192).
		$this->assertArrayNotHasKey( 'hidden_contact_entry_id', $payload );
		$this->assertStringContainsString( 'lookup-order-secret-', $payload['entry_secret']['value'] );
		$this->assertStringNotContainsString( 'outside-order-secret-', $payload['entry_secret']['value'] );
		$this->assertFalse(
			SUPER_Common::getClientData( 'update_contact_entry_' . $lookup_form_id . '_' . $lookup_entry_id )
		);
		$next_capability = $resolved['capability'];
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $next_capability );
		$wrong_method = $this->run_populate_request(
			array(
				'capability' => $next_capability,
				'form_id' => (string) $lookup_form_id,
				'field_name' => 'order_lookup',
				'method' => 'equals',
				'skip' => '',
				'order_id' => (string) $order_id,
			)
		);
		$this->assertSame( 0, $wrong_method['status'], $wrong_method['output'] );
		$wrong_method_payload = $this->decode_public_populate_response( $wrong_method['output'] );
		$this->assertTrue( $wrong_method_payload['capability_rejected'] );
		$this->assertSame( array(), $wrong_method_payload['data'] );
	}
	public function test_no_refresh_client_reads_do_not_revive_an_expired_nonce() {
		SUPER_Common::setClientData(
			array(
				'name' => 'sf_nonce',
				'value' => 'nonce-' . wp_generate_uuid4(),
				'expires' => 30,
				'exp_var' => 30,
				'force' => true,
			)
		);
		$session_id = $_COOKIE['_sfs_id'];
		$stored = get_option( '_sfsdata_' . $session_id );
		$stored['sf_nonce']['expires'] = time() - 1;
		$stored['sf_nonce']['exp_var'] = time() - 1;
		update_option( '_sfsdata_' . $session_id, $stored, false );
		$this->assertFalse( SUPER_Common::getClientData( 'sf_nonce', false ) );
		$after = get_option( '_sfsdata_' . $session_id, array() );
		$this->assertArrayNotHasKey( 'sf_nonce', $after );
	}





	public function test_unrecognized_storage_payload_fails_closed() {
		$call = $this->issue( $this->entry_a );
		$transient_key = '_super_form_entry_access_' . hash('sha256', $call['value']);
		$payload = get_transient($transient_key);
		$payload['storage'] = 'custom_table';
		set_transient($transient_key, $payload, 30);
		$_COOKIE[$call['name']] = $call['value'];

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertFalse( get_transient($transient_key) );
	}

	public function test_browser_session_mismatch_fails_closed() {
		$call = $this->issue( $this->entry_a );
		$this->use_browser_session('other');
		$_COOKIE[$call['name']] = $call['value'];

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertFalse( get_transient('_super_form_entry_access_' . hash('sha256', $call['value'])) );
	}

	public function test_user_and_wordpress_session_mismatch_fail_closed_without_raw_session_storage() {
		$user_a = self::factory()->user->create(array('role'=>'subscriber'));
		$user_b = self::factory()->user->create(array('role'=>'subscriber'));
		$session_a = $this->use_logged_in_user($user_a);
		$call = $this->issue( $this->entry_a );
		$transient_key = '_super_form_entry_access_' . hash('sha256', $call['value']);
		$payload = get_transient($transient_key);

		$this->assertSame( $user_a, $payload['actor_id'] );
		$this->assertSame( hash('sha256', 'wordpress:' . $session_a), $payload['user_session_hash'] );
		$this->assertStringNotContainsString( $session_a, serialize($payload) );

		$this->use_logged_in_user($user_b);
		$_COOKIE[$call['name']] = $call['value'];
		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertFalse( get_transient($transient_key) );
	}

	public function test_failed_single_use_delete_cannot_disclose_entry() {
		$call = $this->issue( $this->entry_a );
		$transient_key = '_super_form_entry_access_' . hash('sha256', $call['value']);
		$_COOKIE[$call['name']] = $call['value'];
		Test_Security_Entry_Access_Common::$delete_result = false;

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertNotFalse( get_transient($transient_key) );

		Test_Security_Entry_Access_Common::$delete_result = null;
		$_COOKIE[$call['name']] = $call['value'];
		$this->assertSame(
			$this->entry_a,
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)['entry_id']
		);
	}
	public function test_nonce_generation_exposes_the_browser_session_to_immediate_entry_access_issuance() {
		$session_id = $_COOKIE['_sfs_id'];
		$nonce = SUPER_Common::generate_nonce();
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{96}$/D', $nonce );
		$this->assertSame( $session_id, $_COOKIE['_sfs_id'] );
		$this->assertSame( $nonce, get_option( '_sfsdata_' . $session_id )['sf_nonce']['value'] );
		$call = $this->issue( $this->entry_a );
		$this->assertSame(
			hash( 'sha256', 'browser:' . $session_id ),
			get_transient( '_super_form_entry_access_' . hash( 'sha256', $call['value'] ) )['browser_session_hash']
		);

		// Without a presented session the CLI SAPI cannot publish one (headers_sent() is
		// permanently true, includes/class-common.php:610-617), so nonce generation must
		// fail closed instead of persisting a session-less bearer nonce.
		unset( $_COOKIE['_sfs_id'] );
		SUPER_Common::generate_nonce();
		$this->assertArrayNotHasKey( '_sfs_id', $_COOKIE );
		$this->assertFalse( SUPER_Common::getClientData( 'sf_nonce', false ) );
	}

	public function test_issue_mirrors_the_cookie_into_the_current_request_for_immediate_consumption() {
		$call = $this->issue( $this->entry_a );
		$this->assertArrayHasKey( $call['name'], $_COOKIE );
		$this->assertSame( $call['value'], $_COOKIE[$call['name']] );
		$this->assertSame(
			array(
				'entry_id' => $this->entry_a,
				'form_id' => $this->form_id,
				'storage' => 'post_type',
			),
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
	}

	public function test_valid_browser_cookie_authorizes_exact_entry_once() {
		$before = time();
		$call = $this->issue( $this->entry_a );
		$token = $call['value'];
		$transient_key = '_super_form_entry_access_' . hash('sha256', $token);
		$payload = get_transient($transient_key);

		$this->assertSame( 'super_form_entry_access_' . $this->entry_a, $call['name'] );
		$this->assertGreaterThanOrEqual( $before + 30, $call['options']['expires'] );
		$this->assertLessThanOrEqual( time() + 30, $call['options']['expires'] );
		$this->assertSame( defined('COOKIEPATH') ? COOKIEPATH : '/', $call['options']['path'] );
		$this->assertSame( is_ssl(), $call['options']['secure'] );
		$this->assertTrue( $call['options']['httponly'] );
		$this->assertSame( 'Lax', $call['options']['samesite'] );
		$this->assertStringNotContainsString( $token, $call['name'] . serialize($call['options']) );
		$this->assertStringNotContainsString( $token, serialize($payload) );
		$this->assertStringNotContainsString( $this->browser_session_id, serialize($payload) );
		$this->assertSame(
			array(
				'version' => 1,
				'entry_id' => $this->entry_a,
				'form_id' => $this->form_id,
				'storage' => 'post_type',
				'actor_id' => 0,
				'browser_session_hash' => hash('sha256', 'browser:' . $this->browser_session_id),
				'user_session_hash' => '',
			),
			$payload
		);

		$_COOKIE[$call['name']] = $token;
		$this->assertSame(
			array(
				'entry_id' => $this->entry_a,
				'form_id' => $this->form_id,
				'storage' => 'post_type',
			),
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertFalse( get_transient($transient_key) );
		$this->assertArrayNotHasKey( $call['name'], $_COOKIE );

		$_COOKIE[$call['name']] = $token;
		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
	}

	public function test_cookie_names_are_per_entry() {
		$call_a = $this->issue( $this->entry_a );
		$call_b = $this->issue( $this->entry_b );

		$this->assertNotSame( $call_a['name'], $call_b['name'] );
		$this->assertNotSame( $call_a['value'], $call_b['value'] );
	}

	public function test_cookie_failure_removes_partial_transient_state() {
		Test_Security_Entry_Access_Common::$cookie_calls = array();
		Test_Security_Entry_Access_Common::$cookie_result = false;

		$this->assertFalse(
			Test_Security_Entry_Access_Common::issue_entry_access_credential(get_post($this->entry_a))
		);
		$this->assertCount( 2, Test_Security_Entry_Access_Common::$cookie_calls );
		$token = Test_Security_Entry_Access_Common::$cookie_calls[0]['value'];
		$this->tokens[] = $token;
		$this->assertFalse( get_transient('_super_form_entry_access_' . hash('sha256', $token)) );
		$this->assertSame( '', Test_Security_Entry_Access_Common::$cookie_calls[1]['value'] );
	}
}
