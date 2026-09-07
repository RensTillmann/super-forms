<?php
/**
 * Security regressions for form-builder save and single-form import authorization.
 *
 * @package Super_Forms\Tests
 */

class Super_Forms_Form_Authoring_Wp_Die_Exception extends RuntimeException {
}

class Super_Forms_Form_Authoring_Side_Effect_Exception extends RuntimeException {
}

class Super_Forms_Form_Authoring_Persisted_Exception extends RuntimeException {
}

class Test_Security_Form_Authoring extends WP_UnitTestCase {

	private $attachment_ids = array();
	private $attachment_lookup_count = 0;
	private $baseline_form_ids = array();
	private $filters = array();
	private $global_secrets_existed = false;
	private $global_secrets_original;
	private $global_secrets_sentinel;
	private $original_current_user = 0;
	private $original_files = array();
	private $original_get = array();
	private $original_post = array();
	private $original_request = array();
	private $remote_fetch_count = 0;
	private $scope;
	private $user_ids = array();

	public function set_up() {
		parent::set_up();

		$this->scope                 = 'sf-authoring-' . str_replace( '-', '', wp_generate_uuid4() );
		$this->original_current_user = get_current_user_id();
		$this->original_post         = $_POST;
		$this->original_get          = $_GET;
		$this->original_request      = $_REQUEST;
		$this->original_files        = $_FILES;
		$this->baseline_form_ids     = $this->all_form_ids();

		$this->global_secrets_sentinel = new stdClass();
		$this->global_secrets_original = get_option( 'super_global_secrets', $this->global_secrets_sentinel );
		$this->global_secrets_existed  = ( $this->global_secrets_original !== $this->global_secrets_sentinel );
		update_option( 'super_global_secrets', $this->baseline_global_secrets() );

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
		foreach ( array_reverse( $this->attachment_ids ) as $attachment_id ) {
			clean_post_cache( $attachment_id );
			if ( get_post( $attachment_id ) ) {
				wp_delete_attachment( $attachment_id, true );
				clean_post_cache( $attachment_id );
			}
			if ( get_post( $attachment_id ) ) {
				$cleanup_errors[] = 'attachment ' . $attachment_id;
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

		if ( $this->global_secrets_existed ) {
			update_option( 'super_global_secrets', $this->global_secrets_original );
		} else {
			delete_option( 'super_global_secrets' );
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
			$this->fail( 'Failed to remove owned form-authoring fixtures: ' . implode( ', ', $cleanup_errors ) );
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

	private function baseline_global_secrets() {
		return array(
			array(
				'name'  => 'baseline-global-secret',
				'value' => 'unchanged-' . $this->scope,
			),
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
			$_POST['nonce'] = wp_create_nonce( 'super_save_form' );
		} elseif ( 'wrong' === $mode ) {
			$_POST['nonce'] = wp_create_nonce( 'different-form-authoring-action' );
		}
		$_REQUEST = $_POST;
	}

	private function fixture_values( $prefix ) {
		return array(
			'elements' => array(
				array(
					'tag'   => 'text',
					'group' => 'form_elements',
					'data'  => array(
						'name'  => $prefix . '_field',
						'label' => $prefix . ' field',
					),
				),
			),
			'settings' => array(
				'form_size'   => 'medium',
				'header_from' => $prefix . '@example.test',
			),
			'translations' => array(
				'nl' => array(
					'name' => $prefix . ' Nederlands',
				),
			),
			'local_secrets' => array(
				array(
					'name'  => $prefix . '_local_secret',
					'value' => $prefix . '_local_value',
				),
			),
			'global_secrets' => array(
				array(
					'name'  => $prefix . '_global_secret',
					'value' => $prefix . '_global_value',
				),
			),
		);
	}

	private function create_existing_form() {
		$form_id = wp_insert_post(
			array(
				'post_title'   => 'Original protected form ' . $this->scope,
				'post_content' => 'original-content-' . $this->scope,
				'post_excerpt' => 'original-excerpt-' . $this->scope,
				'post_status'  => 'publish',
				'post_type'    => 'super_form',
			),
			true
		);
		$this->assertNotWPError( $form_id );

		$values = $this->fixture_values( 'original' );
		update_post_meta( $form_id, '_super_version', 'fixture-version-' . $this->scope );
		update_post_meta( $form_id, '_super_elements', $values['elements'] );
		update_post_meta( $form_id, '_super_form_settings', $values['settings'] );
		update_post_meta( $form_id, '_super_translations', $values['translations'] );
		update_post_meta( $form_id, '_super_local_secrets', $values['local_secrets'] );
		add_post_meta( $form_id, '_security_byte_sentinel', "first\0value-" . $this->scope );
		add_post_meta( $form_id, '_security_byte_sentinel', 'second-value-' . $this->scope );
		return $form_id;
	}

	private function create_import_attachment() {
		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => 'https://example.com/' . $this->scope . '.txt',
				'post_mime_type' => 'text/plain',
				'post_status'    => 'inherit',
				'post_title'     => 'Owned form import ' . $this->scope,
			),
			'',
			0,
			true
		);
		$this->assertNotWPError( $attachment_id );
		update_post_meta( $attachment_id, '_wp_attached_file', 'security-tests/' . $this->scope . '.txt' );
		$this->attachment_ids[] = $attachment_id;
		return $attachment_id;
	}

	private function snapshot_post_and_meta( $post_id ) {
		$post = get_post( $post_id, ARRAY_A );
		$meta = get_post_meta( $post_id );
		ksort( $post );
		ksort( $meta );
		return serialize(
			array(
				'post' => $post,
				'meta' => $meta,
			)
		);
	}

	private function builder_request( $form_id, $title, $values ) {
		return array(
			'action'              => 'super_save_form',
			'form_id'             => $form_id,
			'title'               => $title,
			'elements'            => 'true',
			'settings'            => 'true',
			'translations'        => 'true',
			'formElements'        => wp_slash( wp_json_encode( $values['elements'] ) ),
			'formSettings'        => wp_slash( wp_json_encode( $values['settings'] ) ),
			'translationSettings' => wp_slash( wp_json_encode( $values['translations'] ) ),
			'localSecrets'        => $values['local_secrets'],
			'globalSecrets'       => $values['global_secrets'],
		);
	}

	private function direct_import_save_request( $form_id, $title, $values ) {
		return array(
			'action'              => 'super_import_single_form',
			'form_id'             => $form_id,
			'title'               => $title,
			'elements'            => 'true',
			'settings'            => 'true',
			'translations'        => 'true',
			'formElements'        => $values['elements'],
			'formSettings'        => $values['settings'],
			'translationSettings' => $values['translations'],
			'localSecrets'        => $values['local_secrets'],
			'globalSecrets'       => $values['global_secrets'],
		);
	}

	private function import_request( $form_id, $attachment_id ) {
		return array(
			'action'       => 'super_import_single_form',
			'form_id'      => $form_id,
			'file_id'      => $attachment_id,
			'elements'     => 'true',
			'settings'     => 'true',
			'translations' => 'true',
			'secrets'      => 'true',
		);
	}

	private function install_import_transport( $attachment_id, $body, $fail_on_fetch ) {
		$expected_url = 'https://example.com/' . $this->scope . '-import.txt';
		$url_filter   = function ( $url, $candidate_id ) use ( $attachment_id, $expected_url ) {
			if ( (int) $candidate_id === (int) $attachment_id ) {
				++$this->attachment_lookup_count;
				return $expected_url;
			}
			return $url;
		};
		$this->add_tracked_filter( 'wp_get_attachment_url', $url_filter, 10, 2 );

		$remote_filter = function ( $preempt, $args, $url ) use ( $body, $expected_url, $fail_on_fetch ) {
			if ( $expected_url !== $url ) {
				return $preempt;
			}
			++$this->remote_fetch_count;
			if ( $fail_on_fetch ) {
				throw new Super_Forms_Form_Authoring_Side_Effect_Exception( 'Rejected import reached its attachment fetch.' );
			}
			return array(
				'headers'  => array(),
				'body'     => $body,
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};
		$this->add_tracked_filter( 'pre_http_request', $remote_filter, 10, 3 );
	}

	private function install_global_secret_mutation_trap( $successful_request ) {
		$updated = function ( $option ) use ( $successful_request ) {
			if ( 'super_global_secrets' !== $option ) {
				return;
			}
			if ( $successful_request ) {
				throw new Super_Forms_Form_Authoring_Persisted_Exception( 'Builder save persisted through its final option write.' );
			}
			throw new Super_Forms_Form_Authoring_Side_Effect_Exception( 'Rejected builder save changed global secrets.' );
		};
		$added = function ( $option ) use ( $successful_request ) {
			if ( 'super_global_secrets' !== $option ) {
				return;
			}
			if ( $successful_request ) {
				throw new Super_Forms_Form_Authoring_Persisted_Exception( 'Builder save persisted through its final option write.' );
			}
			throw new Super_Forms_Form_Authoring_Side_Effect_Exception( 'Rejected builder save added global secrets.' );
		};
		$this->add_tracked_filter( 'updated_option', $updated, 10, 3 );
		$this->add_tracked_filter( 'added_option', $added, 10, 2 );
	}

	private function install_import_persistence_stop() {
		$callback = function ( $meta_id, $post_id, $meta_key ) {
			if ( '_super_local_secrets' !== $meta_key || 'super_form' !== get_post_type( $post_id ) ) {
				return;
			}
			throw new Super_Forms_Form_Authoring_Persisted_Exception( 'Authorized import persisted its selected secrets.' );
		};
		$this->add_tracked_filter( 'added_post_meta', $callback, 10, 4 );
	}

	private function invoke_with_wp_die_capture( $callback ) {
		$die_filter = static function () {
			return static function ( $message = '' ) {
				throw new Super_Forms_Form_Authoring_Wp_Die_Exception( (string) $message );
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
			call_user_func( $callback );
		} catch ( Super_Forms_Form_Authoring_Wp_Die_Exception $exception ) {
			$termination = 'wp_die';
		} catch ( Super_Forms_Form_Authoring_Side_Effect_Exception $exception ) {
			$termination = 'side_effect';
		} catch ( Super_Forms_Form_Authoring_Persisted_Exception $exception ) {
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

	private function find_root_form_by_title( $title, $candidate_ids ) {
		$matches = array();
		foreach ( $candidate_ids as $form_id ) {
			$post = get_post( $form_id );
			if ( $post && 0 === (int) $post->post_parent && $title === $post->post_title ) {
				$matches[] = $form_id;
			}
		}
		$this->assertCount( 1, $matches, 'Expected exactly one newly persisted root form.' );
		return $matches[0];
	}

	public static function rejected_ajax_request_provider() {
		return array(
			'save anonymous with valid nonce'  => array( 'super_save_form', 'anonymous', 'valid' ),
			'save subscriber with valid nonce' => array( 'super_save_form', 'subscriber', 'valid' ),
			'save editor with valid nonce'     => array( 'super_save_form', 'editor', 'valid' ),
			'save admin without nonce'         => array( 'super_save_form', 'administrator', 'missing' ),
			'save admin with wrong nonce'      => array( 'super_save_form', 'administrator', 'wrong' ),
			'import anonymous with valid nonce'  => array( 'super_import_single_form', 'anonymous', 'valid' ),
			'import subscriber with valid nonce' => array( 'super_import_single_form', 'subscriber', 'valid' ),
			'import editor with valid nonce'     => array( 'super_import_single_form', 'editor', 'valid' ),
			'import admin without nonce'         => array( 'super_import_single_form', 'administrator', 'missing' ),
			'import admin with wrong nonce'      => array( 'super_import_single_form', 'administrator', 'wrong' ),
		);
	}

	/**
	 * @dataProvider rejected_ajax_request_provider
	 */
	public function test_ajax_authoring_rejects_without_administrator_and_exact_nonce_before_any_effect( $action, $actor, $nonce_mode ) {
		$this->set_actor( $actor );
		$form_id          = $this->create_existing_form();
		$attachment_id    = $this->create_import_attachment();
		$values           = $this->fixture_values( 'unauthorized' );
		$form_snapshot    = $this->snapshot_post_and_meta( $form_id );
		$attachment_state = $this->snapshot_post_and_meta( $attachment_id );
		$form_ids         = $this->all_form_ids();
		$global_secrets   = get_option( 'super_global_secrets' );

		if ( 'super_save_form' === $action ) {
			$_POST = $this->builder_request( $form_id, 'Unauthorized builder mutation', $values );
			$this->install_global_secret_mutation_trap( false );
		} else {
			$_POST = $this->import_request( $form_id, $attachment_id );
			$this->install_import_transport(
				$attachment_id,
				serialize(
					array(
						'title'        => 'Unauthorized imported mutation',
						'elements'     => $values['elements'],
						'settings'     => $values['settings'],
						'translations' => $values['translations'],
						'secrets'      => $values['local_secrets'],
					)
				),
				true
			);
			$this->install_global_secret_mutation_trap( false );
		}
		$this->apply_nonce( $nonce_mode );

		$result = $this->invoke_with_wp_die_capture(
			static function () use ( $action ) {
				do_action( 'wp_ajax_' . $action );
			}
		);

		$this->assertSame( 'wp_die', $result['termination'], 'Unauthorized form authoring did not terminate through wp_die.' );
		$this->assertSame( 0, $this->attachment_lookup_count, 'Rejected import looked up its attachment URL.' );
		$this->assertSame( 0, $this->remote_fetch_count, 'Rejected import attempted to fetch its attachment.' );
		$this->assertSame( $form_ids, $this->all_form_ids(), 'Rejected authoring created a form or backup.' );
		$this->assertSame( $form_snapshot, $this->snapshot_post_and_meta( $form_id ), 'Rejected authoring changed existing post or meta bytes.' );
		$this->assertSame( $attachment_state, $this->snapshot_post_and_meta( $attachment_id ), 'Rejected authoring changed its attachment.' );
		$this->assertSame( $global_secrets, get_option( 'super_global_secrets' ), 'Rejected authoring changed global secrets.' );
	}

	public static function rejected_direct_import_save_provider() {
		return array(
			'anonymous with valid nonce'  => array( 'anonymous', 'valid' ),
			'subscriber with valid nonce' => array( 'subscriber', 'valid' ),
			'editor with valid nonce'     => array( 'editor', 'valid' ),
			'admin without nonce'         => array( 'administrator', 'missing' ),
			'admin with wrong nonce'      => array( 'administrator', 'wrong' ),
		);
	}

	/**
	 * @dataProvider rejected_direct_import_save_provider
	 */
	public function test_direct_import_flavored_save_cannot_bypass_authorization( $actor, $nonce_mode ) {
		$this->set_actor( $actor );
		$form_id        = $this->create_existing_form();
		$values         = $this->fixture_values( 'direct-bypass' );
		$form_snapshot  = $this->snapshot_post_and_meta( $form_id );
		$form_ids       = $this->all_form_ids();
		$global_secrets = get_option( 'super_global_secrets' );

		$_POST = $this->direct_import_save_request( $form_id, 'Direct import bypass', $values );
		$this->apply_nonce( $nonce_mode );
		$result = $this->invoke_with_wp_die_capture(
			static function () {
				SUPER_Ajax::save_form();
			}
		);

		$this->assertSame( 'wp_die', $result['termination'], 'Direct import-flavored save bypassed its authorization guard.' );
		$this->assertSame( $form_ids, $this->all_form_ids(), 'Rejected direct save created a backup or form.' );
		$this->assertSame( $form_snapshot, $this->snapshot_post_and_meta( $form_id ), 'Rejected direct save changed post or meta bytes.' );
		$this->assertSame( $global_secrets, get_option( 'super_global_secrets' ), 'Rejected direct save changed global secrets.' );
	}

	public function test_administrator_with_exact_nonce_can_create_form_through_builder_ajax() {
		$this->set_actor( 'administrator' );
		$values         = $this->fixture_values( 'builder-create' );
		$title          = 'Authorized builder create ' . $this->scope;
		$before_ids     = $this->all_form_ids();
		$_POST          = $this->builder_request( 0, $title, $values );
		$this->apply_nonce( 'valid' );
		$this->install_global_secret_mutation_trap( true );

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				do_action( 'wp_ajax_super_save_form' );
			}
		);
		$this->assertTrue( in_array( $result['termination'], array( 'persisted', 'wp_die' ), true ), 'Authorized builder create did not complete.' );

		$new_ids = array_values( array_diff( $this->all_form_ids(), $before_ids ) );
		$form_id = $this->find_root_form_by_title( $title, $new_ids );
		$this->assertSame( 'publish', get_post_status( $form_id ) );
		$this->assertSame( $values['elements'], get_post_meta( $form_id, '_super_elements', true ) );
		$this->assertSame( $values['settings'], get_post_meta( $form_id, '_super_form_settings', true ) );
		$this->assertSame( $values['translations'], get_post_meta( $form_id, '_super_translations', true ) );
		$this->assertSame( $values['local_secrets'], get_post_meta( $form_id, '_super_local_secrets', true ) );
		$this->assertSame( $values['global_secrets'], get_option( 'super_global_secrets' ) );
	}

	public function test_administrator_with_exact_nonce_can_update_form_through_builder_ajax() {
		$this->set_actor( 'administrator' );
		$form_id        = $this->create_existing_form();
		$values         = $this->fixture_values( 'builder-update' );
		$title          = 'Authorized builder update ' . $this->scope;
		$before_ids     = $this->all_form_ids();
		$_POST          = $this->builder_request( $form_id, $title, $values );
		$this->apply_nonce( 'valid' );
		$this->install_global_secret_mutation_trap( true );

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				do_action( 'wp_ajax_super_save_form' );
			}
		);
		$this->assertTrue( in_array( $result['termination'], array( 'persisted', 'wp_die' ), true ), 'Authorized builder update did not complete.' );

		$post = get_post( $form_id );
		$this->assertSame( $title, $post->post_title );
		$this->assertSame( 'original-content-' . $this->scope, $post->post_content );
		$this->assertSame( $values['elements'], get_post_meta( $form_id, '_super_elements', true ) );
		$this->assertSame( $values['settings'], get_post_meta( $form_id, '_super_form_settings', true ) );
		$this->assertSame( $values['translations'], get_post_meta( $form_id, '_super_translations', true ) );
		$this->assertSame( $values['local_secrets'], get_post_meta( $form_id, '_super_local_secrets', true ) );
		$this->assertSame( $values['global_secrets'], get_option( 'super_global_secrets' ) );

		$new_ids = array_values( array_diff( $this->all_form_ids(), $before_ids ) );
		$this->assertCount( 1, $new_ids, 'Authorized update must retain its one-backup behavior.' );
		$backup = get_post( $new_ids[0] );
		$this->assertSame( $form_id, (int) $backup->post_parent );
		$this->assertSame( 'backup', $backup->post_status );
		$this->assertSame( $values['elements'], get_post_meta( $backup->ID, '_super_elements', true ) );
		$this->assertSame( $values['settings'], get_post_meta( $backup->ID, '_super_form_settings', true ) );
		$this->assertSame( $values['translations'], get_post_meta( $backup->ID, '_super_translations', true ) );
		$this->assertSame( $values['local_secrets'], get_post_meta( $backup->ID, '_super_local_secrets', true ) );
	}

	public function test_administrator_with_exact_nonce_can_import_all_selected_form_data() {
		$this->set_actor( 'administrator' );
		$attachment_id = $this->create_import_attachment();
		$values        = $this->fixture_values( 'authorized-import' );
		$title         = 'Authorized selected import ' . $this->scope;
		$body          = serialize(
			array(
				'title'        => $title,
				'elements'     => $values['elements'],
				'settings'     => $values['settings'],
				'translations' => $values['translations'],
				'secrets'      => $values['local_secrets'],
			)
		);
		$attachment_state = $this->snapshot_post_and_meta( $attachment_id );
		$before_ids       = $this->all_form_ids();
		$global_secrets   = get_option( 'super_global_secrets' );

		$this->install_import_transport( $attachment_id, $body, false );
		$this->install_import_persistence_stop();
		$_POST          = $this->import_request( 0, $attachment_id );
		$this->apply_nonce( 'valid' );

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				do_action( 'wp_ajax_super_import_single_form' );
			}
		);
		$this->assertTrue( in_array( $result['termination'], array( 'persisted', 'wp_die' ), true ), 'Authorized selected import did not complete.' );
		$this->assertSame( 1, $this->attachment_lookup_count, 'Authorized import did not resolve its selected attachment exactly once.' );
		$this->assertSame( 1, $this->remote_fetch_count, 'Authorized import did not fetch its selected attachment exactly once.' );

		$new_ids = array_values( array_diff( $this->all_form_ids(), $before_ids ) );
		$form_id = $this->find_root_form_by_title( $title, $new_ids );
		$this->assertSame( $values['elements'], get_post_meta( $form_id, '_super_elements', true ) );
		$this->assertSame( $values['settings'], get_post_meta( $form_id, '_super_form_settings', true ) );
		$this->assertSame( $values['translations'], get_post_meta( $form_id, '_super_translations', true ) );
		$this->assertSame( $values['local_secrets'], get_post_meta( $form_id, '_super_local_secrets', true ) );
		$this->assertSame( $global_secrets, get_option( 'super_global_secrets' ), 'Single-form import must not replace global secrets.' );
		$this->assertSame( $attachment_state, $this->snapshot_post_and_meta( $attachment_id ), 'Successful import unexpectedly changed its source attachment.' );
	}

	public function test_administrator_can_save_form_with_an_empty_custom_regex_configuration() {
		$this->set_actor( 'administrator' );
		$values = $this->fixture_values( 'builder-empty-custom-regex' );
		$values['elements'][0]['data']['validation'] = 'custom';
		$values['elements'][0]['data']['custom_regex'] = '';
		$title = 'Authorized builder empty custom regex ' . $this->scope;
		$before_ids = $this->all_form_ids();
		$_POST = $this->builder_request( 0, $title, $values );
		$this->apply_nonce( 'valid' );
		$this->install_global_secret_mutation_trap( true );

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				do_action( 'wp_ajax_super_save_form' );
			}
		);
		$this->assertTrue( in_array( $result['termination'], array( 'persisted', 'wp_die' ), true ), 'Authorized builder save with an empty custom regex did not complete.' );

		$new_ids = array_values( array_diff( $this->all_form_ids(), $before_ids ) );
		$form_id = $this->find_root_form_by_title( $title, $new_ids );
		$elements = get_post_meta( $form_id, '_super_elements', true );
		$this->assertIsArray( $elements );
		$this->assertSame( 'custom', $elements[0]['data']['validation'] );
		$this->assertSame( '', $elements[0]['data']['custom_regex'] );
	}
	public function test_administrator_can_save_form_with_an_empty_form_elements_payload() {
		$this->set_actor( 'administrator' );
		$values = $this->fixture_values( 'builder-empty-elements' );
		$title = 'Authorized builder empty elements ' . $this->scope;
		$before_ids = $this->all_form_ids();
		$_POST = $this->builder_request( 0, $title, $values );
		$_POST['formElements'] = '';
		$this->apply_nonce( 'valid' );
		$this->install_global_secret_mutation_trap( true );

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				do_action( 'wp_ajax_super_save_form' );
			}
		);
		$this->assertTrue( in_array( $result['termination'], array( 'persisted', 'wp_die' ), true ), 'Authorized builder save with empty elements did not complete.' );

		$new_ids = array_values( array_diff( $this->all_form_ids(), $before_ids ) );
		$form_id = $this->find_root_form_by_title( $title, $new_ids );
		$this->assertSame( array(), get_post_meta( $form_id, '_super_elements', true ) );
	}

	public function test_administrator_cannot_save_a_js_incompatible_custom_regex() {
		$this->set_actor( 'administrator' );
		$values = $this->fixture_values( 'builder-js-incompatible-regex' );
		$values['elements'][0]['data']['validation'] = 'custom';
		$values['elements'][0]['data']['custom_regex'] = '\A';
		$title = 'Rejected builder js incompatible regex ' . $this->scope;
		$before_ids = $this->all_form_ids();
		$_POST = $this->builder_request( 0, $title, $values );
		$this->apply_nonce( 'valid' );
		$this->install_global_secret_mutation_trap( true );

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				do_action( 'wp_ajax_super_save_form' );
			}
		);
		$this->assertSame( 'wp_die', $result['termination'] );
		$this->assertStringContainsString( 'JavaScript Unicode regular expressions', $result['output'] );
		$this->assertSame( $before_ids, $this->all_form_ids() );
	}
}
