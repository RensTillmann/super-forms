<?php
/**
 * Security regressions for the read-only form-builder AJAX actions
 * (preview, language switch, undo/redo, element settings and element builder HTML).
 *
 * @package Super_Forms\Tests
 */

class Test_Security_Builder_Ajax extends WP_UnitTestCase {

	private $baseline_form_ids = array();
	private $filters = array();
	private $original_current_user = 0;
	private $original_files = array();
	private $original_get = array();
	private $original_post = array();
	private $original_request = array();
	private $scope;
	private $user_ids = array();

	public function set_up() {
		parent::set_up();

		// The plugin loads its AJAX handlers only on AJAX requests; these tests dispatch
		// wp_ajax_* actions directly. The test framework restores the hooks after every
		// test, so register the handlers again for each test.
		if ( ! class_exists( 'SUPER_Ajax' ) ) {
			require_once SUPER_PLUGIN_DIR . '/includes/class-ajax.php';
		} elseif ( ! has_action( 'wp_ajax_super_load_preview' ) ) {
			SUPER_Ajax::init();
		}
		// Admin page requests load the element setting field types (src/super-forms.php includes()).
		if ( ! class_exists( 'SUPER_Field_Types' ) ) {
			require_once SUPER_PLUGIN_DIR . '/includes/class-field-types.php';
		}

		$this->scope                 = 'sfbuilder' . str_replace( '-', '', wp_generate_uuid4() );
		$this->original_current_user = get_current_user_id();
		$this->original_post         = $_POST;
		$this->original_get          = $_GET;
		$this->original_request      = $_REQUEST;
		$this->original_files        = $_FILES;
		$this->baseline_form_ids     = $this->all_form_ids();

		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		$_FILES   = array();
		wp_set_current_user( 0 );
	}

	public function tear_down() {
		$cleanup_errors = array();

		foreach ( array_reverse( $this->filters ) as $filter ) {
			remove_filter( $filter['tag'], $filter['callback'], $filter['priority'] );
		}
		$this->filters = array();

		wp_set_current_user( 0 );
		$form_ids = array_values( array_diff( $this->all_form_ids(), $this->baseline_form_ids ) );
		rsort( $form_ids, SORT_NUMERIC );
		foreach ( $form_ids as $form_id ) {
			wp_delete_post( $form_id, true );
			clean_post_cache( $form_id );
			if ( get_post( $form_id ) ) {
				$cleanup_errors[] = 'form ' . $form_id;
			}
		}

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		foreach ( array_reverse( $this->user_ids ) as $user_id ) {
			if ( get_userdata( $user_id ) ) {
				wp_delete_user( $user_id );
			}
			if ( get_userdata( $user_id ) ) {
				$cleanup_errors[] = 'user ' . $user_id;
			}
		}

		$_POST    = $this->original_post;
		$_GET     = $this->original_get;
		$_REQUEST = $this->original_request;
		$_FILES   = $this->original_files;
		wp_set_current_user( $this->original_current_user );

		parent::tear_down();

		if ( ! empty( $cleanup_errors ) ) {
			$this->fail( 'Failed to remove owned builder-ajax fixtures: ' . implode( ', ', $cleanup_errors ) );
		}
	}

	private function all_form_ids() {
		global $wpdb;

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s ORDER BY ID ASC",
				'super_form'
			)
		);
		return array_map( 'intval', $ids );
	}

	private function add_tracked_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		add_filter( $tag, $callback, $priority, $accepted_args );
		$this->filters[] = array(
			'tag'      => $tag,
			'callback' => $callback,
			'priority' => $priority,
		);
	}

	private function set_actor( $actor ) {
		if ( 'anonymous' === $actor ) {
			wp_set_current_user( 0 );
			return 0;
		}

		$user_id          = self::factory()->user->create( array( 'role' => $actor ) );
		$this->user_ids[] = $user_id;
		wp_set_current_user( $user_id );
		return $user_id;
	}

	private function apply_nonce( $mode ) {
		unset( $_POST['nonce'] );
		if ( 'valid' === $mode ) {
			$_POST['nonce'] = wp_create_nonce( 'super_admin_ajax' );
		} elseif ( 'wrong' === $mode ) {
			$_POST['nonce'] = wp_create_nonce( 'different-builder-ajax-action' );
		}
		$_REQUEST = $_POST;
	}

	private function field_name() {
		return $this->scope . '_field';
	}

	private function text_element() {
		return array(
			'tag'   => 'text',
			'group' => 'form_elements',
			'data'  => array(
				'name'  => $this->field_name(),
				'email' => $this->field_name(),
			),
		);
	}

	private function create_form( $status = 'publish', $parent = 0 ) {
		$form_id = wp_insert_post(
			array(
				'post_parent' => $parent,
				'post_title'  => 'Builder ajax form ' . $this->scope,
				'post_status' => $status,
				'post_type'   => 'super_form',
			),
			true
		);
		$this->assertNotWPError( $form_id );
		update_post_meta( $form_id, '_super_elements', array( $this->text_element() ) );
		update_post_meta( $form_id, '_super_form_settings', array( 'header_from' => $this->scope . '@example.test' ) );
		update_post_meta( $form_id, '_super_version', 'fixture-' . $this->scope );
		return (int) $form_id;
	}

	/**
	 * Request fields the builder sends for each action (without the nonce).
	 */
	private function builder_request( $action, $form_id ) {
		$request = array(
			'action'  => 'super_' . $action,
			'form_id' => $form_id,
		);
		if ( 'switch_language' === $action ) {
			$request['i18n'] = '';
		} elseif ( 'undo_redo' === $action ) {
			$request['elements'] = wp_slash( wp_json_encode( array( $this->text_element() ) ) );
		} elseif ( 'load_element_settings' === $action ) {
			// Same keys as the "edit element" request in create-form.js.
			$request['tag']         = 'text';
			$request['group']       = 'form_elements';
			$request['data']        = array( 'name' => $this->field_name() );
			$request['translating'] = 'false';
			$request['i18n']        = '';
		} elseif ( 'get_element_builder_html' === $action ) {
			// Same keys as the "update element" request in create-form.js.
			$request['tag']         = 'text';
			$request['group']       = 'form_elements';
			$request['builder']     = '0';
			$request['data']        = array( 'name' => $this->field_name() );
			$request['translating'] = 'false';
			$request['i18n']        = '';
		}
		return $request;
	}

	/**
	 * Output fragment that only an answered (not rejected) request contains.
	 */
	private function success_marker( $action, $form_id ) {
		switch ( $action ) {
			case 'load_preview':
				return 'id="super-form-' . $form_id . '"';
			case 'switch_language':
				return 'super-form-settings-tabs';
			case 'load_element_settings':
				return 'super-update-element';
			case 'get_element_builder_html':
				// builder=0 returns the element HTML only, which renders the field by its name.
				return 'name="' . $this->field_name() . '"';
			default:
				return 'data-shortcode-tag="text"';
		}
	}

	/**
	 * Run a builder AJAX handler out of process.
	 *
	 * The handlers end with a raw die(), which would end the whole PHPUnit process,
	 * so the request runs in a forked child whose output is captured to a file.
	 * Status 0 means the handler terminated (die/wp_die) without returning.
	 */
	private function run_dying( $callback ) {
		if ( ! function_exists( 'pcntl_fork' ) || ! function_exists( 'pcntl_waitpid' ) || ! function_exists( 'pcntl_exec' ) ) {
			$this->fail( 'The raw-die builder AJAX regression requires pcntl fork, wait, and exec support.' );
		}
		$this->add_tracked_filter( 'wp_doing_ajax', '__return_true' );
		$capture = tempnam( sys_get_temp_dir(), 'sf-builder-ajax-' );
		$this->assertNotFalse( $capture );
		$pid = pcntl_fork();
		$this->assertNotSame( -1, $pid );
		if ( 0 === $pid ) {
			$returned = false;
			ob_start(
				static function ( $buffer ) use ( $capture ) {
					file_put_contents( $capture, $buffer, FILE_APPEND | LOCK_EX );
					return '';
				}
			);
			// Exec at shutdown avoids destructing the inherited mysqli connection,
			// which would roll back the parent WP_UnitTestCase transaction.
			register_shutdown_function(
				static function () use ( &$returned ) {
					$last_error = error_get_last();
					$fatal      = $last_error && in_array(
						$last_error['type'],
						array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ),
						true
					);
					$status = ( ! $returned && ! $fatal ) ? 0 : 97;
					while ( ob_get_level() > 0 ) {
						@ob_end_flush();
					}
					pcntl_exec( PHP_BINARY, array( '-r', 'exit(' . $status . ');' ) );
				}
			);
			try {
				call_user_func( $callback );
				$returned = true;
			} catch ( Throwable $exception ) {
				echo get_class( $exception ) . ': ' . $exception->getMessage();
				$returned = true;
			}
			exit( 97 );
		}

		$status = 0;
		pcntl_waitpid( $pid, $status );
		global $wpdb;
		if ( isset( $wpdb ) && method_exists( $wpdb, 'check_connection' ) ) {
			$wpdb->check_connection( false );
		}
		wp_cache_flush();
		$output = file_get_contents( $capture );
		unlink( $capture );
		$this->assertTrue( pcntl_wifexited( $status ), (string) $output );
		return array(
			'status' => pcntl_wexitstatus( $status ),
			'output' => ( false === $output ) ? '' : $output,
		);
	}

	private function run_dying_ajax( $action ) {
		return $this->run_dying(
			static function () use ( $action ) {
				do_action( 'wp_ajax_super_' . $action );
			}
		);
	}

	private static function guarded_actions() {
		return array( 'load_preview', 'switch_language', 'undo_redo', 'load_element_settings', 'get_element_builder_html' );
	}

	public static function rejected_request_provider() {
		$cases  = array();
		$actors = array(
			'subscriber with valid nonce'   => array( 'subscriber', 'valid' ),
			'editor with valid nonce'       => array( 'editor', 'valid' ),
			'admin without nonce'           => array( 'administrator', 'missing' ),
			'admin with wrong nonce'        => array( 'administrator', 'wrong' ),
		);
		foreach ( self::guarded_actions() as $action ) {
			foreach ( $actors as $label => $actor ) {
				$cases[ $action . ' ' . $label ] = array( $action, $actor[0], $actor[1] );
			}
		}
		return $cases;
	}

	/**
	 * @dataProvider rejected_request_provider
	 */
	public function test_builder_ajax_rejects_without_administrator_and_admin_nonce( $action, $actor, $nonce_mode ) {
		$form_id = $this->create_form();
		$this->set_actor( $actor );
		$_POST = $this->builder_request( $action, $form_id );
		$this->apply_nonce( $nonce_mode );

		$result = $this->run_dying_ajax( $action );

		$this->assertSame( 0, $result['status'], $result['output'] );
		$this->assertStringNotContainsString( $this->success_marker( $action, $form_id ), $result['output'], 'Rejected builder request returned builder markup.' );
		$this->assertStringNotContainsString( $this->field_name(), $result['output'], 'Rejected builder request leaked form data.' );
		$this->assertSame( '-1', trim( $result['output'] ) );
	}

	public static function form_reading_action_provider() {
		return array(
			'preview'         => array( 'load_preview' ),
			'switch language' => array( 'switch_language' ),
		);
	}

	/**
	 * @dataProvider form_reading_action_provider
	 */
	public function test_builder_ajax_rejects_backup_form_for_administrator( $action ) {
		$form_id   = $this->create_form();
		$backup_id = $this->create_form( 'backup', $form_id );
		$this->set_actor( 'administrator' );
		$_POST = $this->builder_request( $action, $backup_id );
		$this->apply_nonce( 'valid' );

		$result = $this->run_dying_ajax( $action );

		$this->assertSame( 0, $result['status'], $result['output'] );
		$this->assertStringNotContainsString( $this->success_marker( $action, $backup_id ), $result['output'], 'Backup form was rendered.' );
		$this->assertSame( '-1', trim( $result['output'] ) );
	}

	public static function authorized_request_provider() {
		$cases = array();
		foreach ( self::guarded_actions() as $action ) {
			$cases[ $action ] = array( $action );
		}
		return $cases;
	}

	/**
	 * @dataProvider authorized_request_provider
	 */
	public function test_administrator_with_admin_nonce_gets_builder_response( $action ) {
		$form_id = $this->create_form();
		$this->set_actor( 'administrator' );
		$_POST = $this->builder_request( $action, $form_id );
		$this->apply_nonce( 'valid' );

		$result = $this->run_dying_ajax( $action );

		$this->assertSame( 0, $result['status'], $result['output'] );
		$this->assertStringContainsString( $this->success_marker( $action, $form_id ), $result['output'] );
	}

	public function test_internal_get_element_builder_html_call_returns_html_without_nonce() {
		$form_id = $this->create_form();
		$this->set_actor( 'administrator' );
		$_POST    = array( 'form_id' => $form_id );
		$_REQUEST = $_POST;

		$html = SUPER_Ajax::get_element_builder_html( 'text', 'form_elements', null, array( 'name' => $this->field_name() ), 0 );

		$this->assertIsString( $html );
		$this->assertStringContainsString( 'data-shortcode-tag="text"', $html );
	}

	public function test_internal_load_element_settings_call_outputs_settings_without_nonce() {
		$form_id = $this->create_form();
		$this->set_actor( 'administrator' );
		$_POST    = array(
			'form_id'     => $form_id,
			'translating' => 'false',
		);
		$_REQUEST = $_POST;
		$name     = $this->field_name();

		$result = $this->run_dying(
			static function () use ( $name ) {
				SUPER_Ajax::load_element_settings( 'text', 'form_elements', array( 'name' => $name ) );
			}
		);

		$this->assertSame( 0, $result['status'], $result['output'] );
		$this->assertStringContainsString( 'super-update-element', $result['output'] );
	}

	public function test_create_form_script_localizes_admin_nonce_and_rejection_message() {
		$this->set_actor( 'administrator' );
		$scripts = SUPER_Forms::get_scripts();
		$this->assertArrayHasKey( 'super-create-form', $scripts );
		$localize = $scripts['super-create-form']['localize'];
		$this->assertArrayHasKey( 'admin_nonce', $localize );
		$this->assertNotFalse( wp_verify_nonce( $localize['admin_nonce'], 'super_admin_ajax' ) );
		$this->assertArrayHasKey( 'request_rejected', $localize );
		$this->assertStringContainsString( '%s', $localize['request_rejected'] );
	}
}
