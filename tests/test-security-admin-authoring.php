<?php
/**
 * Security regressions for administrator-only settings and form-authoring AJAX actions.
 *
 * @package Super_Forms\Tests
 */

class Super_Forms_Admin_Authoring_Wp_Die_Exception extends RuntimeException {
}

class Super_Forms_Admin_Authoring_Side_Effect_Exception extends RuntimeException {
}

class Super_Forms_Admin_Authoring_Persisted_Exception extends RuntimeException {
}

class Test_Security_Admin_Authoring extends WP_UnitTestCase {

	private $baseline_form_ids = array();
	private $filters = array();
	private $option_existed = false;
	private $option_original;
	private $option_sentinel;
	private $original_current_user = 0;
	private $original_files = array();
	private $original_get = array();
	private $original_post = array();
	private $original_request = array();
	private $post_ids = array();
	private $scope;
	private $user_ids = array();
	private $original_scripts = array();
	private $original_wp_localize_scripts = array();

	private function strict_security_pcntl_required() {
		$flag = getenv( 'SUPER_FORMS_STRICT_SECURITY_TESTS' );
		return is_string($flag) && $flag!=='' && $flag!=='0' && strtolower($flag)!=='false';
	}

	private function require_process_forking( $message ) {
		if ( function_exists( 'pcntl_fork' ) && function_exists( 'pcntl_waitpid' ) && function_exists( 'pcntl_exec' ) ) {
			return;
		}
		if ( $this->strict_security_pcntl_required() ) {
			$this->fail( $message );
		}
		$this->markTestSkipped( $message );
	}

	/**
	 * Load the AJAX handlers under test.
	 *
	 * The WordPress test bootstrap never defines DOING_AJAX, so
	 * super-forms.php:230 (is_request('ajax')) skips ajax_includes() and none of
	 * the wp_ajax_super_* actions exist in the test process. Including
	 * includes/class-ajax.php runs SUPER_Ajax::init() (class-ajax.php:9140),
	 * which registers the handlers this class exercises.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		if ( ! class_exists( 'SUPER_Ajax' ) ) {
			require_once dirname( __DIR__ ) . '/includes/class-ajax.php';
		}
	}

	public function set_up() {
		parent::set_up();
		if ( ! has_action( 'wp_ajax_super_save_settings' ) ) {
			SUPER_Ajax::init();
		}
		if( !class_exists( 'SUPER_Pages' ) ) {
			require_once dirname( __DIR__ ) . '/includes/class-pages.php';
		}

		$this->scope                 = 'sf-admin-authoring-' . str_replace( '-', '', wp_generate_uuid4() );
		$this->original_current_user = get_current_user_id();
		$this->original_post         = $_POST;
		$this->original_get          = $_GET;
		$this->original_request      = $_REQUEST;
		$this->original_files        = $_FILES;
		$this->baseline_form_ids     = $this->all_form_ids();

		$this->option_sentinel = new stdClass();
		$this->option_original = get_option( 'super_settings', $this->option_sentinel );
		$this->option_existed  = ( $this->option_original !== $this->option_sentinel );
		update_option( 'super_settings', $this->baseline_settings() );
		$scripts_property = new ReflectionProperty( 'SUPER_Forms', 'scripts' );
		$scripts_property->setAccessible( true );
		$this->original_scripts = $scripts_property->getValue();
		$localized_property = new ReflectionProperty( 'SUPER_Forms', 'wp_localize_scripts' );
		$localized_property->setAccessible( true );
		$this->original_wp_localize_scripts = $localized_property->getValue();

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
		$scripts_property = new ReflectionProperty( 'SUPER_Forms', 'scripts' );
		$scripts_property->setAccessible( true );
		$scripts_property->setValue( null, $this->original_scripts );
		$localized_property = new ReflectionProperty( 'SUPER_Forms', 'wp_localize_scripts' );
		$localized_property->setAccessible( true );
		$localized_property->setValue( null, $this->original_wp_localize_scripts );

		wp_set_current_user( 0 );
		foreach ( array_reverse( $this->post_ids ) as $post_id ) {
			if ( get_post( $post_id ) ) {
				wp_delete_post( $post_id, true );
			}
			clean_post_cache( $post_id );
			if ( get_post( $post_id ) ) {
				$cleanup_errors[] = 'post ' . $post_id;
			}
		}

		$form_ids = array_values( array_diff( $this->all_form_ids(), $this->baseline_form_ids ) );
		rsort( $form_ids, SORT_NUMERIC );
		foreach ( $form_ids as $form_id ) {
			wp_delete_post( $form_id, true );
			clean_post_cache( $form_id );
			if ( get_post( $form_id ) ) {
				$cleanup_errors[] = 'form ' . $form_id;
			}
		}

		if ( $this->option_existed ) {
			update_option( 'super_settings', $this->option_original );
		} else {
			delete_option( 'super_settings' );
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
			$this->fail( 'Failed to remove owned admin-authoring fixtures: ' . implode( ', ', $cleanup_errors ) );
		}
	}

	private function baseline_settings() {
		return array(
			'smtp_enabled'   => 'disabled',
			'security_marker' => 'baseline-' . $this->scope,
		);
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
			$_POST['nonce'] = wp_create_nonce( 'different-admin-authoring-action' );
		}
		$_REQUEST = $_POST;
	}

	private function localized_admin_nonce( $handle ) {
		$scripts = SUPER_Forms::get_scripts();
		$this->assertArrayHasKey( $handle, $scripts );
		$this->assertArrayHasKey( 'localize', $scripts[$handle] );
		$this->assertArrayHasKey( 'admin_nonce', $scripts[$handle]['localize'] );
		$nonce = $scripts[$handle]['localize']['admin_nonce'];
		$this->assertNotFalse( wp_verify_nonce( $nonce, 'super_admin_ajax' ) );
		return $nonce;
	}

	private function create_form( $label ) {
		$form_id = wp_insert_post(
			array(
				'post_title'  => $label . ' ' . $this->scope,
				'post_status' => 'publish',
				'post_type'   => 'super_form',
			),
			true
		);
		$this->assertNotWPError( $form_id );
		update_post_meta( $form_id, '_super_elements', array( array( 'name' => $label . '-original' ) ) );
		update_post_meta( $form_id, '_super_form_settings', array( 'marker' => $label . '-original' ) );
		update_post_meta( $form_id, '_super_version', 'fixture-' . $this->scope );
		update_post_meta( $form_id, '_super_translations', array( 'en' => $label . '-original' ) );
		return (int) $form_id;
	}

	private function create_backup( $form_id, $label ) {
		$backup_id = wp_insert_post(
			array(
				'post_parent' => $form_id,
				'post_title'  => $label . ' ' . $this->scope,
				'post_status' => 'backup',
				'post_type'   => 'super_form',
			),
			true
		);
		$this->assertNotWPError( $backup_id );
		update_post_meta( $backup_id, '_super_elements', array( array( 'name' => $label . '-backup' ) ) );
		update_post_meta( $backup_id, '_super_form_settings', array( 'marker' => $label . '-backup' ) );
		update_post_meta( $backup_id, '_super_version', 'backup-' . $this->scope );
		update_post_meta( $backup_id, '_super_translations', array( 'en' => $label . '-backup' ) );
		return (int) $backup_id;
	}

	private function create_non_form_post( $form_id ) {
		$post_id = wp_insert_post(
			array(
				'post_parent' => $form_id,
				'post_title'  => 'Forged ordinary post ' . $this->scope,
				'post_status' => 'publish',
				'post_type'   => 'post',
			),
			true
		);
		$this->assertNotWPError( $post_id );
		$this->post_ids[] = (int) $post_id;
		return (int) $post_id;
	}

	private function create_contact_entry( $label ) {
		$entry_id = wp_insert_post(
			array(
				'post_title'  => $label . ' ' . $this->scope,
				'post_status' => 'super_unread',
				'post_type'   => 'super_contact_entry',
			),
			true
		);
		$this->assertNotWPError( $entry_id );
		$this->post_ids[] = (int) $entry_id;
		update_post_meta( $entry_id, '_super_contact_entry_status', 'original' );
		update_post_meta(
			$entry_id,
			'_super_contact_entry_data',
			array( 'field' => array( 'name' => 'field', 'value' => 'original' ) )
		);
		return (int) $entry_id;
	}

	private function create_wrong_status_backup( $form_id ) {
		$post_id = wp_insert_post(
			array(
				'post_parent' => $form_id,
				'post_title'  => 'Forged published child ' . $this->scope,
				'post_status' => 'publish',
				'post_type'   => 'super_form',
			),
			true
		);
		$this->assertNotWPError( $post_id );
		return (int) $post_id;
	}

	private function snapshot_post( $post_id ) {
		$post = get_post( $post_id, ARRAY_A );
		$meta = get_post_meta( $post_id );
		if ( is_array( $post ) ) {
			ksort( $post );
		}
		ksort( $meta );
		return array(
			'post' => $post,
			'meta' => $meta,
		);
	}

	private function snapshot_posts( $post_ids ) {
		$post_ids = array_map( 'intval', $post_ids );
		sort( $post_ids, SORT_NUMERIC );
		$snapshot = array();
		foreach ( $post_ids as $post_id ) {
			$snapshot[$post_id] = $this->snapshot_post( $post_id );
		}
		return serialize( $snapshot );
	}

	private function invoke_ajax( $action ) {
		$die_filter = static function () {
			return static function ( $message = '' ) {
				throw new Super_Forms_Admin_Authoring_Wp_Die_Exception( (string) $message );
			};
		};
		$ajax_filter = static function () {
			return true;
		};
		add_filter( 'wp_doing_ajax', $ajax_filter );
		add_filter( 'wp_die_handler', $die_filter );
		add_filter( 'wp_die_ajax_handler', $die_filter );

		$termination = 'returned';
		$output      = '';
		ob_start();
		try {
			do_action( 'wp_ajax_super_' . $action );
		} catch ( Super_Forms_Admin_Authoring_Wp_Die_Exception $exception ) {
			$termination = 'wp_die';
		} catch ( Super_Forms_Admin_Authoring_Side_Effect_Exception $exception ) {
			$termination = 'side_effect';
		} catch ( Super_Forms_Admin_Authoring_Persisted_Exception $exception ) {
			$termination = 'persisted';
		} finally {
			$output = ob_get_clean();
			remove_filter( 'wp_doing_ajax', $ajax_filter );
			remove_filter( 'wp_die_handler', $die_filter );
			remove_filter( 'wp_die_ajax_handler', $die_filter );
		}

		return array(
			'termination' => $termination,
			'output'      => $output,
		);
	}

	private function assert_raw_ajax_terminates( $action ) {
		$this->require_process_forking( 'The raw-die contact-entry regression requires pcntl fork, wait, and exec support.' );
		$capture = tempnam( sys_get_temp_dir(), 'sf-lts-contact-entry-' );
		$this->assertNotFalse( $capture );
		$pid = pcntl_fork();
		$this->assertNotSame( -1, $pid );
		if ( $pid === 0 ) {
			$returned = false;
			ob_start(
				static function ( $buffer ) use ( $capture ) {
					file_put_contents( $capture, $buffer, FILE_APPEND | LOCK_EX );
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
					while ( ob_get_level() > 0 ) {
						@ob_end_flush();
					}
					pcntl_exec( PHP_BINARY, array( '-r', 'exit(' . $status . ');' ) );
				}
			);
			try {
				do_action( 'wp_ajax_super_' . $action );
				$returned = true;
			} catch ( Exception $exception ) {
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
		$this->assertTrue( pcntl_wifexited( $status ), $output );
		$this->assertSame( 0, pcntl_wexitstatus( $status ), $output );
	}

	private function prepare_rejected_scenario( $scenario ) {
		if ( 'settings' === $scenario ) {
			$trap = static function ( $option ) {
				if ( 'super_settings' === $option ) {
					throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Rejected settings request mutated its option.' );
				}
			};
			$this->add_tracked_filter( 'updated_option', $trap, 10, 3 );
			return array(
				'action'   => 'save_settings',
				'request'  => array(
					'data' => array(
						array( 'name' => 'smtp_enabled', 'value' => 'disabled' ),
						array( 'name' => 'security_marker', 'value' => 'changed-' . $this->scope ),
					),
				),
				'snapshot' => function () {
					return serialize( get_option( 'super_settings' ) );
				},
			);
		}

		if ( 'demo' === $scenario ) {
			$title = 'Rejected demo ' . $this->scope;
			$trap = static function ( $post_id, $post ) use ( $title ) {
				if ( $post instanceof WP_Post && 'super_form' === $post->post_type && $title === $post->post_title ) {
					throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Rejected demo request created a form.' );
				}
			};
			$this->add_tracked_filter( 'wp_insert_post', $trap, 10, 3 );
			return array(
				'action'   => 'demos_install_item',
				'request'  => array(
					'title'    => $title,
					'settings' => wp_json_encode( array( 'marker' => 'rejected-demo' ) ),
					'elements' => wp_json_encode( array( array( 'name' => 'rejected-demo' ) ) ),
					'import'   => '',
				),
				'snapshot' => function () {
					return serialize( $this->all_form_ids() );
				},
			);
		}

		if ( 'reset_form' === $scenario || 'delete_form' === $scenario ) {
			$form_id = $this->create_form( $scenario );
			if ( 'reset_form' === $scenario ) {
				$trap = static function ( $meta_id, $post_id, $meta_key ) use ( $form_id ) {
					if ( (int) $post_id === $form_id && '_super_form_settings' === $meta_key ) {
						throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Rejected reset mutated form settings.' );
					}
				};
				$this->add_tracked_filter( 'updated_post_meta', $trap, 10, 4 );
			} else {
				$trap = static function ( $post_id ) use ( $form_id ) {
					if ( (int) $post_id === $form_id ) {
						throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Rejected delete removed its form.' );
					}
				};
				$this->add_tracked_filter( 'deleted_post', $trap, 10, 1 );
			}
			return array(
				'action'   => 'reset_form' === $scenario ? 'reset_form_settings' : 'delete_form',
				'request'  => array( 'form_id' => $form_id ),
				'snapshot' => function () use ( $form_id ) {
					return $this->snapshot_posts( array( $form_id ) );
				},
			);
		}

		$target_id = $this->create_form( $scenario . '-target' );
		$owner_id  = $this->create_form( $scenario . '-owner' );
		$backup_id = $this->create_backup( $owner_id, $scenario );
		if ( 'restore_unrelated_backup' === $scenario ) {
			$trap = static function ( $meta_id, $post_id ) use ( $target_id ) {
				if ( (int) $post_id === $target_id ) {
					throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Rejected restore mutated its target form.' );
				}
			};
			$this->add_tracked_filter( 'updated_post_meta', $trap, 10, 4 );
		} else {
			$trap = static function ( $post_id ) use ( $backup_id ) {
				if ( (int) $post_id === $backup_id ) {
					throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Rejected backup delete removed its candidate.' );
				}
			};
			$this->add_tracked_filter( 'deleted_post', $trap, 10, 1 );
		}
		return array(
			'action'   => 'restore_unrelated_backup' === $scenario ? 'restore_backup' : 'delete_backups',
			'request'  => array(
				'form_id'   => $target_id,
				'backup_id' => $backup_id,
			),
			'snapshot' => function () use ( $target_id, $owner_id, $backup_id ) {
				return $this->snapshot_posts( array( $target_id, $owner_id, $backup_id ) );
			},
		);
	}

	public static function rejected_request_provider() {
		$cases = array();
		$scenarios = array(
			'settings',
			'demo',
			'reset_form',
			'delete_form',
			'restore_unrelated_backup',
			'delete_unrelated_backup',
		);
		$actors = array(
			'anonymous with valid nonce'       => array( 'anonymous', 'valid' ),
			'subscriber with valid nonce'      => array( 'subscriber', 'valid' ),
			'administrator without nonce'      => array( 'administrator', 'missing' ),
			'administrator with wrong nonce'   => array( 'administrator', 'wrong' ),
		);
		foreach ( $scenarios as $scenario ) {
			foreach ( $actors as $label => $actor ) {
				$cases[$scenario . ': ' . $label] = array( $scenario, $actor[0], $actor[1] );
			}
		}
		return $cases;
	}

	/**
	 * @dataProvider rejected_request_provider
	 */
	public function test_rejected_requests_cannot_mutate_admin_authoring_state( $scenario, $actor, $nonce_mode ) {
		$this->set_actor( $actor );
		$case  = $this->prepare_rejected_scenario( $scenario );
		$_POST = array_merge( array( 'action' => 'super_' . $case['action'] ), $case['request'] );
		$this->apply_nonce( $nonce_mode );

		$before = call_user_func( $case['snapshot'] );
		$result = $this->invoke_ajax( $case['action'] );

		$this->assertSame( $before, call_user_func( $case['snapshot'] ) );
		$this->assertSame( 'wp_die', $result['termination'] );
	}

	public function test_contact_entry_mutations_require_admin_nonce_and_exact_entry_identity() {
		$entry_id      = $this->create_contact_entry( 'Protected entry' );
		$ordinary_id   = $this->create_non_form_post( 0 );
		$entry_before  = $this->snapshot_post( $entry_id );
		$ordinary_before = $this->snapshot_post( $ordinary_id );

		$this->set_actor( 'subscriber' );
		$requests = array(
			'delete_contact_entry' => array( 'contact_entry' => $entry_id ),
			'update_contact_entry' => array(
				'id'           => $entry_id,
				'entry_status' => 'attacker',
				'data'         => array(
					'super_contact_entry_post_title' => 'Attacker title',
					'field' => 'attacker',
				),
			),
			'mark_read' => array( 'contact_entry' => $entry_id ),
			'mark_unread' => array( 'contact_entry' => $entry_id ),
			'bulk_edit_entries' => array(
				'post_ids'    => array( $entry_id ),
				'entry_status' => 'attacker',
			),
		);
		foreach ( $requests as $action => $request ) {
			$_POST = array_merge(
				array(
					'action' => 'super_' . $action,
					'nonce'  => wp_create_nonce( 'super_admin_ajax' ),
				),
				$request
			);
			$_REQUEST = $_POST;
			$result = $this->invoke_ajax( $action );
			$this->assertSame( 'wp_die', $result['termination'], $action );
			$this->assertSame( $entry_before, $this->snapshot_post( $entry_id ), $action );
		}

		$this->set_actor( 'administrator' );
		foreach ( array( 'delete_contact_entry', 'update_contact_entry', 'mark_read', 'mark_unread', 'bulk_edit_entries' ) as $action ) {
			$_POST = array(
				'action'        => 'super_' . $action,
				'nonce'         => wp_create_nonce( 'super_admin_ajax' ),
				'contact_entry' => $ordinary_id,
				'id'            => $ordinary_id,
				'post_ids'     => array( $ordinary_id ),
				'entry_status'  => 'forged',
				'data'          => array( 'super_contact_entry_post_title' => 'Forged type' ),
			);
			$_REQUEST = $_POST;
			$result = $this->invoke_ajax( $action );
			$this->assertSame( 'wp_die', $result['termination'], $action );
			$this->assertSame( $ordinary_before, $this->snapshot_post( $ordinary_id ), $action );
		}
	}

	public function test_admin_contact_entry_mutations_preserve_authorized_workflow() {
		$this->set_actor( 'administrator' );

		$update_id = $this->create_contact_entry( 'Authorized update' );
		$_POST = array(
			'action'        => 'super_update_contact_entry',
			'nonce'         => wp_create_nonce( 'super_admin_ajax' ),
			'id'            => $update_id,
			'entry_status'  => 'reviewed',
			'data'          => array(
				'super_contact_entry_post_title' => 'Updated ' . $this->scope,
				'field' => 'updated',
			),
		);
		$_REQUEST = $_POST;
		$this->assert_raw_ajax_terminates( 'update_contact_entry' );
		$this->assertSame( 'Updated ' . $this->scope, get_the_title( $update_id ) );
		$this->assertSame( 'reviewed', get_post_meta( $update_id, '_super_contact_entry_status', true ) );
		$this->assertSame( 'updated', SUPER_Data_Access::get_entry_data( $update_id )['field']['value'] );

		foreach ( array( 'mark_read' => 'super_read', 'mark_unread' => 'super_unread' ) as $action => $status ) {
			$_POST = array(
				'action'        => 'super_' . $action,
				'nonce'         => wp_create_nonce( 'super_admin_ajax' ),
				'contact_entry' => $update_id,
			);
			$_REQUEST = $_POST;
			$this->assert_raw_ajax_terminates( $action );
			$this->assertSame( $status, get_post_status( $update_id ) );
		}

		$bulk_id = $this->create_contact_entry( 'Authorized bulk edit' );
		$_POST = array(
			'action'       => 'super_bulk_edit_entries',
			'nonce'        => wp_create_nonce( 'super_admin_ajax' ),
			'post_ids'     => array( $bulk_id ),
			'entry_status' => 'bulk-reviewed',
		);
		$_REQUEST = $_POST;
		$this->assert_raw_ajax_terminates( 'bulk_edit_entries' );
		$this->assertSame( 'bulk-reviewed', get_post_meta( $bulk_id, '_super_contact_entry_status', true ) );

		$delete_id = $this->create_contact_entry( 'Authorized delete' );
		$_POST = array(
			'action'        => 'super_delete_contact_entry',
			'nonce'         => wp_create_nonce( 'super_admin_ajax' ),
			'contact_entry' => $delete_id,
		);
		$_REQUEST = $_POST;
		$this->assert_raw_ajax_terminates( 'delete_contact_entry' );
		$this->assertSame( 'trash', get_post_status( $delete_id ) );
	}

	public function test_all_backend_script_objects_localize_the_same_admin_nonce_contract() {
		$this->set_actor( 'administrator' );
		$create_nonce   = $this->localized_admin_nonce( 'super-create-form' );
		$settings_nonce = $this->localized_admin_nonce( 'super-settings' );
		$demos_nonce    = $this->localized_admin_nonce( 'super-demos' );
		$contact_nonce  = $this->localized_admin_nonce( 'super-contact-entry' );

		$this->assertSame( $create_nonce, $settings_nonce );
		$this->assertSame( $create_nonce, $demos_nonce );
		$this->assertSame( $create_nonce, $contact_nonce );
	}

	public function test_contact_entry_script_emits_its_localized_admin_nonce_on_contact_entry_screens() {
		$this->set_actor( 'administrator' );
		wp_dequeue_script( 'super-contact-entry' );
		wp_deregister_script( 'super-contact-entry' );
		$scripts_property = new ReflectionProperty( 'SUPER_Forms', 'scripts' );
		$scripts_property->setAccessible( true );
		$scripts_property->setValue( null, array() );
		$localized_property = new ReflectionProperty( 'SUPER_Forms', 'wp_localize_scripts' );
		$localized_property->setAccessible( true );
		$localized_property->setValue( null, array() );
		set_current_screen( 'edit-super_contact_entry' );
		try {
			SUPER_Forms()->enqueue_scripts();
			SUPER_Forms::localize_printed_scripts();
		} finally {
			set_current_screen( 'front' );
		}
		$this->assertTrue( wp_script_is( 'super-contact-entry', 'registered' ) );
		$this->assertTrue( wp_script_is( 'super-contact-entry', 'enqueued' ) );
		$data = (string) wp_scripts()->get_data( 'super-contact-entry', 'data' );
		$this->assertStringContainsString( 'super_contact_entry_i18n', $data );
		$this->assertStringContainsString( 'admin_nonce', $data );
	}

	public function test_admin_with_localized_settings_nonce_can_save_global_settings() {
		$this->set_actor( 'administrator' );
		$expected = array(
			'smtp_enabled'   => 'disabled',
			'security_marker' => 'authorized-' . $this->scope,
		);
		$_POST = array(
			'action' => 'super_save_settings',
			'nonce'  => $this->localized_admin_nonce( 'super-settings' ),
			'data'   => array(
				array( 'name' => 'smtp_enabled', 'value' => $expected['smtp_enabled'] ),
				array( 'name' => 'security_marker', 'value' => $expected['security_marker'] ),
			),
		);
		$_REQUEST = $_POST;

		$persisted = static function ( $option ) {
			if ( 'super_settings' === $option ) {
				throw new Super_Forms_Admin_Authoring_Persisted_Exception( 'Settings persisted.' );
			}
		};
		$this->add_tracked_filter( 'updated_option', $persisted, 10, 3 );

		$result = $this->invoke_ajax( 'save_settings' );
		$this->assertSame( 'persisted', $result['termination'] );
		$this->assertSame( $expected, get_option( 'super_settings' ) );
	}

	public function test_admin_with_localized_demo_nonce_can_create_demo_form() {
		$this->set_actor( 'administrator' );
		$title    = 'Authorized demo ' . $this->scope;
		$settings = array( 'marker' => 'authorized-demo-' . $this->scope );
		$_POST = array(
			'action'   => 'super_demos_install_item',
			'nonce'    => $this->localized_admin_nonce( 'super-demos' ),
			'title'    => $title,
			'settings' => wp_json_encode( $settings ),
			'elements' => wp_json_encode( array( array( 'name' => 'authorized-demo' ) ) ),
			'import'   => '',
		);
		$_REQUEST = $_POST;

		$persisted_form_id = 0;
		$persisted = function ( $meta_id, $post_id, $meta_key ) use ( &$persisted_form_id ) {
			if ( '_super_form_settings' === $meta_key && 'super_form' === get_post_type( $post_id ) ) {
				$persisted_form_id = (int) $post_id;
				throw new Super_Forms_Admin_Authoring_Persisted_Exception( 'Demo form persisted.' );
			}
		};
		$this->add_tracked_filter( 'added_post_meta', $persisted, 10, 4 );

		$result = $this->invoke_ajax( 'demos_install_item' );
		$this->assertSame( 'persisted', $result['termination'] );
		$this->assertNotSame( 0, $persisted_form_id );
		$this->assertSame( $title, get_the_title( $persisted_form_id ) );
		$this->assertSame( $settings, get_post_meta( $persisted_form_id, '_super_form_settings', true ) );
	}

	public function test_admin_with_localized_create_form_nonce_can_reset_form_settings() {
		$this->set_actor( 'administrator' );
		$form_id  = $this->create_form( 'Authorized reset' );
		$original = get_post_meta( $form_id, '_super_form_settings', true );
		$_POST = array(
			'action'  => 'super_reset_form_settings',
			'nonce'   => $this->localized_admin_nonce( 'super-create-form' ),
			'form_id' => $form_id,
		);
		$_REQUEST = $_POST;

		$persisted_value = null;
		$persisted = function ( $meta_id, $post_id, $meta_key, $value ) use ( $form_id, &$persisted_value ) {
			if ( (int) $post_id === $form_id && '_super_form_settings' === $meta_key ) {
				$persisted_value = $value;
				throw new Super_Forms_Admin_Authoring_Persisted_Exception( 'Form settings reset.' );
			}
		};
		$this->add_tracked_filter( 'updated_post_meta', $persisted, 10, 4 );

		$result = $this->invoke_ajax( 'reset_form_settings' );
		$this->assertSame( 'persisted', $result['termination'] );
		$this->assertNotSame( $original, $persisted_value );
		$this->assertSame( $persisted_value, get_post_meta( $form_id, '_super_form_settings', true ) );
	}

	public static function forged_form_target_provider() {
		return array(
			'reset rejects ordinary post' => array( 'reset_form_settings', 'wrong_type' ),
			'delete rejects ordinary post' => array( 'delete_form', 'wrong_type' ),
			'reset rejects backup' => array( 'reset_form_settings', 'backup' ),
			'delete rejects backup' => array( 'delete_form', 'backup' ),
			'reset rejects child form' => array( 'reset_form_settings', 'child' ),
			'delete rejects child form' => array( 'delete_form', 'child' ),
		);
	}

	/**
	 * @dataProvider forged_form_target_provider
	 */
	public function test_admin_nonce_rejects_non_form_and_non_root_form_targets( $action, $forgery ) {
		$this->set_actor( 'administrator' );
		$owner_id = $this->create_form( 'Forged form target owner' );
		if ( 'wrong_type' === $forgery ) {
			$target_id = $this->create_non_form_post( 0 );
		} elseif ( 'backup' === $forgery ) {
			$target_id = $this->create_backup( $owner_id, 'Forged form target backup' );
		} else {
			$target_id = $this->create_wrong_status_backup( $owner_id );
		}
		$_POST = array(
			'action'  => 'super_' . $action,
			'nonce'   => $this->localized_admin_nonce( 'super-create-form' ),
			'form_id' => $target_id,
		);
		$_REQUEST = $_POST;
		$before = $this->snapshot_posts( array( $owner_id, $target_id ) );

		if ( 'reset_form_settings' === $action ) {
			$trap = static function ( $meta_id, $post_id ) use ( $target_id ) {
				if ( (int) $post_id === $target_id ) {
					throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Forged form target was mutated.' );
				}
			};
			$this->add_tracked_filter( 'updated_post_meta', $trap, 10, 4 );
		} else {
			$trap = static function ( $post_id ) use ( $target_id ) {
				if ( (int) $post_id === $target_id ) {
					throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Forged form target was deleted.' );
				}
			};
			$this->add_tracked_filter( 'deleted_post', $trap, 10, 1 );
		}

		$result = $this->invoke_ajax( $action );
		$this->assertSame( $before, $this->snapshot_posts( array( $owner_id, $target_id ) ) );
		$this->assertSame( 'wp_die', $result['termination'] );
	}

	public static function forged_backup_provider() {
		return array(
			'restore rejects wrong parent' => array( 'restore_backup', 'wrong_parent' ),
			'delete rejects wrong parent'  => array( 'delete_backups', 'wrong_parent' ),
			'restore rejects wrong type'   => array( 'restore_backup', 'wrong_type' ),
			'delete rejects wrong type'    => array( 'delete_backups', 'wrong_type' ),
			'restore rejects wrong status' => array( 'restore_backup', 'wrong_status' ),
			'delete rejects wrong status'  => array( 'delete_backups', 'wrong_status' ),
		);
	}

	/**
	 * @dataProvider forged_backup_provider
	 */
	public function test_admin_nonce_rejects_forged_backup_type_status_and_parent( $action, $forgery ) {
		$this->set_actor( 'administrator' );
		$target_id = $this->create_form( 'Forged backup target' );
		$owner_id  = $this->create_form( 'Forged backup owner' );
		if ( 'wrong_parent' === $forgery ) {
			$backup_id = $this->create_backup( $owner_id, 'Wrong parent backup' );
		} elseif ( 'wrong_type' === $forgery ) {
			$backup_id = $this->create_non_form_post( $target_id );
		} else {
			$backup_id = $this->create_wrong_status_backup( $target_id );
		}

		$_POST = array(
			'action'    => 'super_' . $action,
			'nonce'     => $this->localized_admin_nonce( 'super-create-form' ),
			'form_id'   => $target_id,
			'backup_id' => $backup_id,
		);
		$_REQUEST = $_POST;
		$before = $this->snapshot_posts( array( $target_id, $owner_id, $backup_id ) );
		if ( 'restore_backup' === $action ) {
			$trap = static function ( $meta_id, $post_id ) use ( $target_id ) {
				if ( (int) $post_id === $target_id ) {
					throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Forged backup mutated its target form.' );
				}
			};
			$this->add_tracked_filter( 'updated_post_meta', $trap, 10, 4 );
		} else {
			$trap = static function ( $post_id ) use ( $backup_id ) {
				if ( (int) $post_id === $backup_id ) {
					throw new Super_Forms_Admin_Authoring_Side_Effect_Exception( 'Forged backup was deleted.' );
				}
			};
			$this->add_tracked_filter( 'deleted_post', $trap, 10, 1 );
		}

		$result = $this->invoke_ajax( $action );
		$this->assertSame( $before, $this->snapshot_posts( array( $target_id, $owner_id, $backup_id ) ) );
		$this->assertSame( 'wp_die', $result['termination'] );
	}

	public function test_listing_retrieval_scope_uses_exact_authoritative_form_ids() {
		$list = array(
			'retrieve' => 'specific_forms',
			'form_ids' => '123, 456',
		);
		$this->assertTrue( SUPER_Listings::entry_is_in_retrieval_scope( $list, 123, 999 ) );
		$this->assertTrue( SUPER_Listings::entry_is_in_retrieval_scope( $list, 456, 999 ) );
		$this->assertFalse( SUPER_Listings::entry_is_in_retrieval_scope( $list, 23, 999 ) );
		$this->assertFalse( SUPER_Listings::entry_is_in_retrieval_scope( $list, 4567, 999 ) );

		$list['retrieve'] = 'this_form';
		$this->assertTrue( SUPER_Listings::entry_is_in_retrieval_scope( $list, 999, 999 ) );
		$this->assertFalse( SUPER_Listings::entry_is_in_retrieval_scope( $list, 123, 999 ) );

		$list['retrieve'] = 'all_forms';
		$this->assertTrue( SUPER_Listings::entry_is_in_retrieval_scope( $list, 123, 999 ) );
		$this->assertFalse( SUPER_Listings::entry_is_in_retrieval_scope( $list, 0, 999 ) );
	}

	public function test_submission_entry_updates_require_exact_listing_identity_and_permission() {
		$this->set_actor( 'administrator' );
		$host_form_id  = $this->create_form( 'Listing host' );
		$other_form_id = $this->create_form( 'Other form' );
		$entry_id      = $this->create_contact_entry( 'Listing entry' );
		wp_update_post( array( 'ID' => $entry_id, 'post_parent' => $host_form_id ) );
		$list = array(
			'edit_any' => array(
				'enabled'    => 'true',
				'user_roles' => 'administrator',
				'user_ids'   => '',
			),
		);
		$settings = array( '_listings' => array( 'lists' => array( $list ) ) );
		$method = new ReflectionMethod( 'SUPER_Ajax', 'submission_entry_update_is_authorized' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( null, $entry_id, 0, $host_form_id, $settings ) );
		$this->assertFalse( $method->invoke( null, $entry_id, 0, $other_form_id, $settings ) );
		$excluded_list = $list;
		$excluded_list['retrieve'] = 'specific_forms';
		$excluded_list['form_ids'] = (string) $other_form_id;
		$excluded_settings = array( '_listings' => array( 'lists' => array( $excluded_list ) ) );
		$this->assertFalse(
			$method->invoke( null, $entry_id, 0, $host_form_id, $excluded_settings ),
			'A same-parent entry excluded by the list retrieval scope must not be writable.'
		);
		$this->assertFalse( $method->invoke( null, $entry_id, '', $host_form_id, $settings ) );
		$this->assertTrue( $method->invoke( null, 0, '', $host_form_id, $settings ) );
	}

	public function test_contact_entry_admin_screen_uses_the_public_owned_upload_url_for_non_attachment_files() {
		$this->set_actor( 'administrator' );
		$form_id = $this->create_form( 'Owned upload form' );
		$entry_id = $this->create_contact_entry( 'Owned upload entry' );
		wp_update_post( array( 'ID' => $entry_id, 'post_parent' => $form_id ) );

		$parent = trailingslashit( sys_get_temp_dir() ) . 'sf-admin-owned-' . str_replace( '-', '', wp_generate_uuid4() );
		$root = trailingslashit( $parent ) . 'owned';
		$slot = str_pad( (string) wp_rand( 1, 9999999999999 ), 13, '0', STR_PAD_LEFT );
		$directory = trailingslashit( $root ) . $slot;
		$filename = trailingslashit( $directory ) . 'admin-link.pdf';
		$this->assertTrue( wp_mkdir_p( $directory ) );
		$this->assertNotFalse( file_put_contents( $filename, "%PDF-1.4\nadmin screen link\n" ) );

		$file_upload_dir_setting = '../' . basename( $parent ) . '/' . basename( $root );
		update_post_meta( $form_id, '_super_form_settings', array( 'file_upload_dir' => $file_upload_dir_setting ) );
		$subdir = '/' . $file_upload_dir_setting . '/' . $slot . '/admin-link.pdf';
		$raw_url = trailingslashit( get_option( 'siteurl' ) ) . 'sfgtfi/' . ltrim( str_replace( '../', '__/', $subdir ), '/' );

		$build_owned_upload = new ReflectionMethod( 'SUPER_Ajax', 'build_owned_upload' );
		$build_owned_upload->setAccessible( true );
		$owned_upload_file_record = new ReflectionMethod( 'SUPER_Ajax', 'owned_upload_file_record' );
		$owned_upload_file_record->setAccessible( true );
		$owned = $build_owned_upload->invoke(
			null,
			$form_id,
			'documents',
			$filename,
			'application/pdf',
			$raw_url,
			0,
			$root,
			filesize( $filename ),
			$subdir
		);
		$this->assertIsArray( $owned );
		$stored = $owned_upload_file_record->invoke( null, $owned, 'documents' );
		$this->assertIsArray( $stored );

		SUPER_Data_Access::update_entry_data( $entry_id, array(
			'documents' => array(
				'name' => 'documents',
				'label' => 'Document',
				'type' => 'files',
				'files' => array(
					array_merge( $stored, array( 'label' => 'Document' ) ),
				),
			),
			'hidden_form_id' => array(
				'name' => 'hidden_form_id',
				'value' => (string) $form_id,
				'type' => 'form_id',
			),
		) );

		$expected_url = SUPER_Forms::public_owned_upload_url( $stored, SUPER_Common::get_form_settings( $form_id ) );
		$this->assertNotSame( '', $expected_url );
		$this->assertNotSame( $raw_url, $expected_url );

		$_GET = array( 'id' => (string) $entry_id );
		$output = '';
		$buffer_level = ob_get_level();
		ob_start();
		try {
			SUPER_Pages::contact_entry();
			$output = ob_get_clean();
		} finally {
			while( ob_get_level() > $buffer_level ) {
				$output = ob_get_clean();
			}
			@unlink( $filename );
			@rmdir( $directory );
			@rmdir( $root );
			@rmdir( $parent );
		}

		$this->assertStringContainsString( 'href="' . esc_url( $expected_url ) . '"', $output );
		$this->assertStringNotContainsString( 'href="' . esc_url( $raw_url ) . '"', $output );
	}
	public function test_admin_with_localized_nonce_can_restore_a_related_backup() {
		$this->set_actor( 'administrator' );
		$form_id   = $this->create_form( 'Authorized restore target' );
		$backup_id = $this->create_backup( $form_id, 'Authorized restore backup' );
		$expected  = get_post_meta( $backup_id, '_super_elements', true );
		$_POST = array(
			'action'    => 'super_restore_backup',
			'nonce'     => $this->localized_admin_nonce( 'super-create-form' ),
			'form_id'   => $form_id,
			'backup_id' => $backup_id,
		);
		$_REQUEST = $_POST;

		$persisted = function ( $meta_id, $post_id, $meta_key ) use ( $form_id ) {
			if ( (int) $post_id === $form_id && '_super_elements' === $meta_key ) {
				throw new Super_Forms_Admin_Authoring_Persisted_Exception( 'Related backup restored.' );
			}
		};
		$this->add_tracked_filter( 'updated_post_meta', $persisted, 10, 4 );

		$result = $this->invoke_ajax( 'restore_backup' );
		$this->assertSame( 'persisted', $result['termination'] );
		$this->assertSame( $expected, get_post_meta( $form_id, '_super_elements', true ) );
	}
}
