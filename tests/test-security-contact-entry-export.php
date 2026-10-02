<?php
/**
 * Contact-entry export and import security regressions.
 *
 * @package Super_Forms\Tests
 */

class Super_Forms_Contact_Entry_Export_Wp_Die_Exception extends RuntimeException {
}

class Test_Security_Contact_Entry_Export extends WP_UnitTestCase {

	private $files = array();
	private $original_current_user = 0;
	private $original_option;
	private $original_option_existed = false;
	private $original_post = array();
	private $original_request = array();
	private $post_ids = array();
	private $scope;
	private $user_ids = array();

	public function set_up() {
		parent::set_up();

		// The plugin loads its AJAX handlers only on AJAX requests; these tests dispatch
		// wp_ajax_* actions directly. The test framework restores the hooks after every
		// test, so register the handlers again for each test.
		if ( ! class_exists( 'SUPER_Ajax' ) ) {
			require_once SUPER_PLUGIN_DIR . '/includes/class-ajax.php';
		} elseif ( ! has_action( 'wp_ajax_super_save_settings' ) ) {
			SUPER_Ajax::init();
		}

		$this->scope = 'sf-contact-export-' . str_replace( '-', '', wp_generate_uuid4() );
		$this->original_current_user = get_current_user_id();
		$this->original_post = $_POST;
		$this->original_request = $_REQUEST;
		$sentinel = new stdClass();
		$this->original_option = get_option( 'super_settings', $sentinel );
		$this->original_option_existed = ( $this->original_option !== $sentinel );
		update_option(
			'super_settings',
			array(
				'backend_contact_entry_list_fields' => "field|Field\nhidden_form_id|Form",
				'contact_entry_add_id' => 'false',
			)
		);
		$_POST = array();
		$_REQUEST = array();
		wp_set_current_user( 0 );
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		foreach ( array_reverse( $this->post_ids ) as $post_id ) {
			if ( get_post( $post_id ) ) {
				wp_delete_post( $post_id, true );
			}
		}
		foreach ( array_reverse( $this->files ) as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
		if ( $this->original_option_existed ) {
			update_option( 'super_settings', $this->original_option );
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
		}
		$_POST = $this->original_post;
		$_REQUEST = $this->original_request;
		wp_set_current_user( $this->original_current_user );
		parent::tear_down();
	}

	private function set_actor( $role ) {
		if ( 'anonymous' === $role ) {
			wp_set_current_user( 0 );
			return 0;
		}
		$user_id = self::factory()->user->create( array( 'role' => $role ) );
		$this->user_ids[] = $user_id;
		wp_set_current_user( $user_id );
		return $user_id;
	}

	private function create_form( $label ) {
		$form_id = wp_insert_post(
			array(
				'post_title' => $label . ' ' . $this->scope,
				'post_status' => 'publish',
				'post_type' => 'super_form',
			),
			true
		);
		$this->assertNotWPError( $form_id );
		$this->post_ids[] = (int) $form_id;
		return (int) $form_id;
	}

	private function create_entry( $form_id, $marker ) {
		$entry_id = wp_insert_post(
			array(
				'post_parent' => $form_id,
				'post_title' => 'Entry ' . $marker,
				'post_status' => 'super_unread',
				'post_type' => 'super_contact_entry',
			),
			true
		);
		$this->assertNotWPError( $entry_id );
		$this->post_ids[] = (int) $entry_id;
		update_post_meta(
			$entry_id,
			'_super_contact_entry_data',
			array(
				'hidden_form_id' => array(
					'name' => 'hidden_form_id',
					'value' => $form_id,
					'type' => 'form_id',
				),
				'field' => array(
					'name' => 'field',
					'value' => $marker,
					'type' => 'var',
				),
			)
		);
		return (int) $entry_id;
	}

	private function create_ordinary_post() {
		$post_id = wp_insert_post(
			array(
				'post_title' => 'Ordinary ' . $this->scope,
				'post_status' => 'publish',
				'post_type' => 'post',
			),
			true
		);
		$this->assertNotWPError( $post_id );
		$this->post_ids[] = (int) $post_id;
		return (int) $post_id;
	}

	private function create_csv_attachment( $contents, $mime='text/csv' ) {
		$uploads = wp_upload_dir();
		$this->assertEmpty( $uploads['error'] );
		$this->assertTrue( wp_mkdir_p( $uploads['path'] ) );
		$basename = wp_unique_filename( $uploads['path'], 'contact-import-' . $this->scope . '.csv' );
		$file = trailingslashit( $uploads['path'] ) . $basename;
		$this->assertNotFalse( file_put_contents( $file, $contents ) );
		$this->files[] = $file;
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $mime,
				'post_title' => $basename,
				'post_status' => 'inherit',
				'post_author' => get_current_user_id(),
			),
			$file,
			0
		);
		$this->assertIsInt( $attachment_id );
		$this->assertGreaterThan( 0, $attachment_id );
		$this->post_ids[] = $attachment_id;
		return $attachment_id;
	}

	private function create_outside_csv_attachment( $contents ) {
		$file = tempnam( sys_get_temp_dir(), 'sf-contact-import-' );
		$this->assertNotFalse( $file );
		$csv_file = $file . '.csv';
		$this->assertTrue( rename( $file, $csv_file ) );
		$this->assertNotFalse( file_put_contents( $csv_file, $contents ) );
		$this->files[] = $csv_file;
		$attachment_id = wp_insert_post(
			array(
				'post_mime_type' => 'text/csv',
				'post_title' => basename( $csv_file ),
				'post_status' => 'inherit',
				'post_type' => 'attachment',
				'post_author' => get_current_user_id(),
			),
			true
		);
		$this->assertNotWPError( $attachment_id );
		update_post_meta( $attachment_id, '_wp_attached_file', $csv_file );
		$this->post_ids[] = (int) $attachment_id;
		return (int) $attachment_id;
	}

	private function request( $action, $data, $nonce_mode='valid' ) {
		$_POST = array_merge( array( 'action' => 'super_' . $action ), $data );
		if ( 'valid' === $nonce_mode ) {
			$_POST['nonce'] = wp_create_nonce( 'super_admin_ajax' );
		} elseif ( 'wrong' === $nonce_mode ) {
			$_POST['nonce'] = wp_create_nonce( 'wrong-contact-entry-export-action' );
		}
		$_REQUEST = $_POST;
	}

	private function invoke_rejected_ajax( $action ) {
		$die_filter = static function () {
			return static function ( $message='' ) {
				throw new Super_Forms_Contact_Entry_Export_Wp_Die_Exception( (string) $message );
			};
		};
		$ajax_filter = static function () {
			return true;
		};
		add_filter( 'wp_doing_ajax', $ajax_filter );
		add_filter( 'wp_die_handler', $die_filter );
		add_filter( 'wp_die_ajax_handler', $die_filter );
		$termination = 'returned';
		ob_start();
		try {
			do_action( 'wp_ajax_super_' . $action );
		} catch ( Super_Forms_Contact_Entry_Export_Wp_Die_Exception $exception ) {
			$termination = 'wp_die';
		} finally {
			ob_end_clean();
			remove_filter( 'wp_doing_ajax', $ajax_filter );
			remove_filter( 'wp_die_handler', $die_filter );
			remove_filter( 'wp_die_ajax_handler', $die_filter );
		}
		$this->assertSame( 'wp_die', $termination, $action );
	}

	private function invoke_raw_ajax( $action ) {
		if ( ! function_exists( 'pcntl_fork' ) || ! function_exists( 'pcntl_waitpid' ) || ! function_exists( 'pcntl_exec' ) ) {
			$this->markTestSkipped( 'The raw contact-entry AJAX regression requires pcntl fork, wait, and exec support.' );
		}
		$capture = tempnam( sys_get_temp_dir(), 'sf-contact-export-response-' );
		$this->assertNotFalse( $capture );
		$pid = pcntl_fork();
		$this->assertNotSame( -1, $pid );
		if ( $pid===0 ) {
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
					$status = ( !$returned && !$fatal ) ? 0 : 97;
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
		return $output;
	}

	private function count_contact_entries() {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = %s",
				'super_contact_entry'
			)
		);
	}

	private function valid_requests( $entry_id, $file_id ) {
		return array(
			'get_entry_export_columns' => array(
				'entries' => array( (string) $entry_id ),
			),
			'export_selected_entries' => array(
				'entries' => array( (string) $entry_id ),
				'columns' => array( 'field' => 'Field' ),
				'sort_by' => 'entry_date',
				'order_by' => 'ASC',
				'delimiter' => ',',
				'enclosure' => '"',
			),
			'export_entries' => array(
				'type' => 'csv',
				'sort_by' => 'entry_date',
				'order_by' => 'ASC',
				'from' => '',
				'till' => '',
				'form_ids' => '',
				'delimiter' => ',',
				'enclosure' => '"',
			),
			'prepare_contact_entry_import' => array(
				'file_id' => (string) $file_id,
				'import_delimiter' => ',',
				'import_enclosure' => '"',
			),
			'import_contact_entries' => array(
				'file_id' => (string) $file_id,
				'column_connections' => array(
					array( 'column' => 'var', 'name' => 'field', 'label' => 'Field' ),
				),
				'skip_first' => 'false',
				'import_delimiter' => ',',
				'import_enclosure' => '"',
			),
		);
	}

	public function test_all_five_handlers_reject_low_privilege_and_invalid_nonce_before_effects() {
		$this->set_actor( 'administrator' );
		$form_id = $this->create_form( 'Protected form' );
		$entry_id = $this->create_entry( $form_id, 'protected-pii-' . $this->scope );
		$file_id = $this->create_csv_attachment( "Field\nsecret\n" );
		$requests = $this->valid_requests( $entry_id, $file_id );
		$before_count = $this->count_contact_entries();

		foreach ( $requests as $action => $data ) {
			$this->set_actor( 'subscriber' );
			$this->request( $action, $data, 'valid' );
			$this->invoke_rejected_ajax( $action );
			$this->assertSame( $before_count, $this->count_contact_entries(), $action );

			$this->set_actor( 'administrator' );
			$this->request( $action, $data, 'missing' );
			$this->invoke_rejected_ajax( $action );
			$this->assertSame( $before_count, $this->count_contact_entries(), $action );

			$this->request( $action, $data, 'wrong' );
			$this->invoke_rejected_ajax( $action );
			$this->assertSame( $before_count, $this->count_contact_entries(), $action );
		}
		$this->assertSame( '', get_post_meta( $file_id, '_super_forms_contact_entry_import_file', true ) );
	}

	public function test_selected_entry_ids_reject_sql_mixed_types_and_cross_form_scope() {
		$this->set_actor( 'administrator' );
		$form_a = $this->create_form( 'Form A' );
		$form_b = $this->create_form( 'Form B' );
		$entry_a = $this->create_entry( $form_a, 'form-a-' . $this->scope );
		$entry_b = $this->create_entry( $form_b, 'form-b-' . $this->scope );
		$ordinary_id = $this->create_ordinary_post();
		$actions = array( 'get_entry_export_columns', 'export_selected_entries' );

		foreach ( $actions as $action ) {
			$base = 'export_selected_entries'===$action
				? array(
					'columns' => array( 'field' => 'Field' ),
					'sort_by' => 'entry_date',
					'order_by' => 'ASC',
					'delimiter' => ',',
					'enclosure' => '"',
				)
				: array();

			$this->request( $action, array_merge( $base, array( 'entries' => array( $entry_a . ') OR 1=1 --' ) ) ) );
			$this->invoke_rejected_ajax( $action );

			$this->request( $action, array_merge( $base, array( 'entries' => array( (string) $entry_a, (string) $ordinary_id ) ) ) );
			$this->invoke_rejected_ajax( $action );

			$this->request(
				$action,
				array_merge(
					$base,
					array(
						'entries' => array( (string) $entry_a, (string) $entry_b ),
						'form_ids' => array( (string) $form_a ),
					)
				)
			);
			$this->invoke_rejected_ajax( $action );
		}
	}

	public function test_export_filters_reject_client_sql_and_invalid_sort_direction() {
		$this->set_actor( 'administrator' );
		$form_id = $this->create_form( 'Sort form' );
		$entry_id = $this->create_entry( $form_id, 'sort-' . $this->scope );
		$selected = array(
			'entries' => array( (string) $entry_id ),
			'columns' => array( 'field' => 'Field' ),
			'order_by' => 'ASC',
			'delimiter' => ',',
			'enclosure' => '"',
		);

		$this->request( 'export_selected_entries', array_merge( $selected, array( 'sort_by' => 'entry.post_date, (SELECT 1)' ) ) );
		$this->invoke_rejected_ajax( 'export_selected_entries' );
		$this->request( 'export_selected_entries', array_merge( $selected, array( 'sort_by' => 'entry_date', 'order_by' => 'DESC, ID' ) ) );
		$this->invoke_rejected_ajax( 'export_selected_entries' );

		$bulk = array(
			'type' => 'csv',
			'sort_by' => 'entry_date',
			'order_by' => 'ASC',
			'delimiter' => ',',
			'enclosure' => '"',
			'from' => '',
			'till' => '',
		);
		$this->request( 'export_entries', array_merge( $bulk, array( 'form_ids' => $form_id . ') OR 1=1 --' ) ) );
		$this->invoke_rejected_ajax( 'export_entries' );
		$this->request( 'export_entries', array_merge( $bulk, array( 'form_ids' => (string) $form_id, 'sort_by' => 'post_date DESC' ) ) );
		$this->invoke_rejected_ajax( 'export_entries' );
		$this->request( 'export_entries', array_merge( $bulk, array( 'form_ids' => (string) $form_id, 'order_by' => 'desc' ) ) );
		$this->invoke_rejected_ajax( 'export_entries' );
	}

	public function test_import_rejects_arbitrary_or_unprepared_attachment_without_creating_entries() {
		$this->set_actor( 'administrator' );
		$ordinary_id = $this->create_ordinary_post();
		$wrong_mime_id = $this->create_csv_attachment( "Field\nsecret\n", 'text/plain' );
		$outside_id = $this->create_outside_csv_attachment( "Field\nsecret\n" );
		$unprepared_id = $this->create_csv_attachment( "Field\nsecret\n" );
		$before_count = $this->count_contact_entries();

		foreach ( array( $ordinary_id, $wrong_mime_id, $outside_id ) as $file_id ) {
			$this->request(
				'prepare_contact_entry_import',
				array(
					'file_id' => (string) $file_id,
					'import_delimiter' => ',',
					'import_enclosure' => '"',
				)
			);
			$this->invoke_rejected_ajax( 'prepare_contact_entry_import' );
		}

		$this->request(
			'import_contact_entries',
			array(
				'file_id' => (string) $unprepared_id,
				'column_connections' => array(
					array( 'column' => 'var', 'name' => 'field', 'label' => 'Field' ),
				),
				'skip_first' => 'false',
				'import_delimiter' => ',',
				'import_enclosure' => '"',
			)
		);
		$this->invoke_rejected_ajax( 'import_contact_entries' );
		$this->assertSame( $before_count, $this->count_contact_entries() );
	}

	private function assert_private_random_export_and_consume( $url, $expected, $unexpected='' ) {
		$url = trim( $url );
		$this->assertNotSame( '', $url );
		$this->assertStringNotContainsString( '/wp-content/uploads/', $url );
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		$this->assertIsString( $query );
		parse_str( $query, $query_args );
		$this->assertArrayHasKey( 'sfdlfi', $query_args );
		$this->assertArrayHasKey( 'sfdlfi_token', $query_args );
		$attachment_id = (int) $query_args['sfdlfi'];
		$this->assertGreaterThan( 0, $attachment_id );
		$this->post_ids[] = $attachment_id;
		$attachment = get_post( $attachment_id );
		$this->assertInstanceOf( WP_Post::class, $attachment );
		$this->assertSame( 'attachment', $attachment->post_type );
		$this->assertSame( 'private', $attachment->post_status );
		$this->assertSame( get_current_user_id(), (int) $attachment->post_author );
		$file = get_attached_file( $attachment_id );
		$this->assertMatchesRegularExpression( '/^super-contact-entries-[a-f0-9]{32}\.csv$/D', basename( $file ) );
		$download = SUPER_Forms::consume_export_download( $attachment_id, $query_args['sfdlfi_token'] );
		$this->assertNotWPError( $download );
		$contents = $this->read_export_download_bytes( $download );
		$this->assertStringContainsString( $expected, $contents );
		if ( $unexpected!=='' ) {
			$this->assertStringNotContainsString( $unexpected, $contents );
		}
		return basename( $file );
	}

	private function read_export_download_bytes( $download ) {
		$this->assertIsArray( $download );
		$this->assertArrayHasKey( 'handle', $download );
		$this->assertArrayHasKey( 'size', $download );
		$handle = $download['handle'];
		$this->assertIsResource( $handle );
		$contents = stream_get_contents( $handle );
		fclose( $handle );
		$this->assertSame( (int) $download['size'], strlen( $contents ) );
		return $contents;
	}

	public function test_valid_admin_selected_and_scoped_exports_keep_token_url_shape_and_private_random_files() {
		$this->set_actor( 'administrator' );
		$form_a = $this->create_form( 'Export form A' );
		$form_b = $this->create_form( 'Export form B' );
		$marker_a = 'selected-a-' . $this->scope;
		$marker_b = 'excluded-b-' . $this->scope;
		$entry_a = $this->create_entry( $form_a, $marker_a );
		$this->create_entry( $form_b, $marker_b );

		$this->request( 'get_entry_export_columns', array( 'entries' => array( (string) $entry_a ) ) );
		$columns_response = $this->invoke_raw_ajax( 'get_entry_export_columns' );
		$this->assertStringContainsString( 'super-contact-entries-export-modal', $columns_response );
		$this->assertStringContainsString( 'name="entries[]"', $columns_response );
		$this->assertStringNotContainsString( 'name="query"', $columns_response );

		$this->request(
			'export_selected_entries',
			array(
				'entries' => array( (string) $entry_a ),
				'columns' => array( 'field' => 'Field' ),
				'sort_by' => 'entry_date',
				'order_by' => 'ASC',
				'delimiter' => ',',
				'enclosure' => '"',
			)
		);
		$selected_name = $this->assert_private_random_export_and_consume(
			$this->invoke_raw_ajax( 'export_selected_entries' ),
			$marker_a,
			$marker_b
		);

		$this->request(
			'export_entries',
			array(
				'type' => 'csv',
				'form_ids' => (string) $form_a,
				'sort_by' => 'entry_date',
				'order_by' => 'DESC',
				'from' => '',
				'till' => '',
				'delimiter' => ',',
				'enclosure' => '"',
			)
		);
		$bulk_name = $this->assert_private_random_export_and_consume(
			$this->invoke_raw_ajax( 'export_entries' ),
			$marker_a,
			$marker_b
		);
		$this->assertNotSame( $selected_name, $bulk_name );
	}

	public function test_valid_admin_prepare_and_import_preserve_response_shapes_and_consume_marker() {
		$this->set_actor( 'administrator' );
		$form_id = $this->create_form( 'Import target' );
		$imported_title = 'Imported ' . $this->scope;
		$imported_value = 'imported-pii-' . $this->scope;
		$file_id = $this->create_csv_attachment(
			"Form ID,Title,Field\n{$form_id},{$imported_title},{$imported_value}\n"
		);

		$this->request(
			'prepare_contact_entry_import',
			array(
				'file_id' => (string) $file_id,
				'import_delimiter' => ',',
				'import_enclosure' => '"',
			)
		);
		$prepare_response = $this->invoke_raw_ajax( 'prepare_contact_entry_import' );
		// Each column carries its header and the server-sanitized default field name; the
		// settings screen (assets/js/backend/settings.js) reads both.
		$this->assertSame(
			array(
				array( 'header' => 'Form ID', 'name' => 'Form_ID' ),
				array( 'header' => 'Title', 'name' => 'Title' ),
				array( 'header' => 'Field', 'name' => 'Field' ),
			),
			json_decode( $prepare_response, true )
		);
		$this->assertSame(
			'super-forms-contact-entry-import-v1',
			get_post_meta( $file_id, '_super_forms_contact_entry_import_file', true )
		);

		$before_count = $this->count_contact_entries();
		$this->request(
			'import_contact_entries',
			array(
				'file_id' => (string) $file_id,
				'column_connections' => array(
					array( 'column' => 'form_id', 'name' => 'hidden_form_id', 'label' => 'Form ID' ),
					array( 'column' => 'post_title', 'name' => 'post_title', 'label' => 'Title' ),
					array( 'column' => 'var', 'name' => 'field', 'label' => 'Field' ),
				),
				'skip_first' => 'true',
				'import_delimiter' => ',',
				'import_enclosure' => '"',
			)
		);
		$import_response = $this->invoke_raw_ajax( 'import_contact_entries' );
		$this->assertStringContainsString( '1 of 1 contact entries imported!', $import_response );
		$this->assertSame( $before_count + 1, $this->count_contact_entries() );
		$this->assertSame( '', get_post_meta( $file_id, '_super_forms_contact_entry_import_file', true ) );

		// Query the table directly: the plugin registers its custom entry statuses
		// (super_unread, super_read) only in request contexts, and WP_Query drops
		// statuses that are not registered.
		global $wpdb;
		$imported_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'super_contact_entry' AND post_parent = %d AND post_title = %s",
				$form_id,
				$imported_title
			)
		);
		$this->assertCount( 1, $imported_ids );
		$imported_entry = get_post( (int) $imported_ids[0] );
		$this->assertInstanceOf( WP_Post::class, $imported_entry );
		$this->post_ids[] = $imported_entry->ID;
		$data = get_post_meta( $imported_entry->ID, '_super_contact_entry_data', true );
		$this->assertSame( $form_id, $data['hidden_form_id']['value'] );
		$this->assertSame( $imported_value, $data['field']['value'] );
	}

	public function test_shared_csv_writer_emits_neutralized_formula_bytes_and_preserves_safe_scalars() {
		$handle = fopen( 'php://temp', 'w+' );
		$this->assertIsResource( $handle );
		$written = SUPER_Common::write_csv_row(
			$handle,
			array(
				'=direct',
				"\t+tab",
				"\r-formula",
				' @space',
				"\x01@control",
				"\x7F=delete",
				'＝lookalike',
				'safe = later',
				"'=already-safe",
				42,
				-7,
				3.5,
			),
			',',
			'"'
		);
		$this->assertNotFalse( $written );
		rewind( $handle );
		$actual = stream_get_contents( $handle );
		fclose( $handle );
		$expected = "'=direct,\"'\t+tab\",\"'\r-formula\",\"' @space\",'\x01@control,'\x7F=delete,＝lookalike,\"safe = later\",'=already-safe,42,-7,3.5\n";
		$this->assertSame( $expected, $actual );
	}

	public function test_selected_export_neutralizes_exact_header_data_and_file_url_bytes() {
		$this->set_actor( 'administrator' );
		$form_id = $this->create_form( 'Formula export form' );
		$entry_id = $this->create_entry( $form_id, 'placeholder' );
		update_post_meta(
			$entry_id,
			'_super_contact_entry_data',
			array(
				'formula' => array(
					'name' => 'formula',
					'value' => "\t=SUM(1,1)",
					'type' => 'var',
				),
				'upload' => array(
					'name' => 'upload',
					'type' => 'files',
					'files' => array(
						array( 'url' => "\r@remote" ),
						array( 'url' => 'safe-url' ),
					),
				),
			)
		);
		$this->request(
			'export_selected_entries',
			array(
				'entries' => array( (string) $entry_id ),
				'columns' => array(
					'formula' => '=Header',
					'upload' => '+Files',
				),
				'sort_by' => 'entry_date',
				'order_by' => 'ASC',
				'delimiter' => ',',
				'enclosure' => '"',
			)
		);
		$url = trim( $this->invoke_raw_ajax( 'export_selected_entries' ) );
		$query = wp_parse_url( $url, PHP_URL_QUERY );
		$this->assertIsString( $query );
		parse_str( $query, $query_args );
		$this->assertArrayHasKey( 'sfdlfi', $query_args );
		$this->assertArrayHasKey( 'sfdlfi_token', $query_args );
		$attachment_id = (int) $query_args['sfdlfi'];
		$this->post_ids[] = $attachment_id;
		$download = SUPER_Forms::consume_export_download( $attachment_id, $query_args['sfdlfi_token'] );
		$this->assertNotWPError( $download );
		$expected = "\xEF\xBB\xBF'=Header,'+Files\n\"'\t=SUM(1,1)\",\"'\r@remote\nsafe-url\"\n";
		$this->assertSame( $expected, $this->read_export_download_bytes( $download ) );
	}
}
