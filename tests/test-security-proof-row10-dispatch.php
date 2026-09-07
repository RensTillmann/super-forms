<?php
/**
 * Matrix proof package — Row 10 (sfgtfi/sfdlfi real parse_request dispatch),
 * Row 15 (resolve_owned_upload_file public URL-component denial), and Row 13
 * dispatcher half (wp_delete_post -> before_delete_post -> delete_entry_attachments).
 *
 * @package Super_Forms\Tests
 */

require_once __DIR__ . '/test-security-upload-00-base.php';

/**
 * Local wp_die() substitute for the forked dispatcher child. It performs the
 * exact real side effect wp_die() would (status_header()) but never renders an
 * HTML error page, so a denied sfdlfi request can be asserted as zero body
 * bytes instead of an incidental error-page byte count.
 */
class Super_Forms_Proof_Row10_Wp_Die_Exception extends Exception {}

class Test_Super_Forms_Proof_Row10_Download_Dispatch extends WP_UnitTestCase {

	private $default_root;
	private $admin_id;
	private $original_get;
	private $original_server_method;
	private $owned_directories = array();
	private $owned_attachment_ids = array();

	public function set_up() {
		parent::set_up();
		$this->default_root = wp_normalize_path(
			trailingslashit( ABSPATH ) . trim( wp_normalize_path( SUPER_FORMS_UPLOAD_DIR ), '/' )
		);
		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );
		$this->original_get = $_GET;
		$this->original_server_method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : null;
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		$_GET = $this->original_get;
		if ( null === $this->original_server_method ) {
			unset( $_SERVER['REQUEST_METHOD'] );
		} else {
			$_SERVER['REQUEST_METHOD'] = $this->original_server_method;
		}
		foreach ( array_unique( $this->owned_attachment_ids ) as $attachment_id ) {
			if ( 'attachment' === get_post_type( $attachment_id ) ) {
				wp_delete_attachment( $attachment_id, true );
			}
		}
		foreach ( array_reverse( array_unique( $this->owned_directories ) ) as $directory ) {
			$this->remove_test_tree( $directory );
		}
		parent::tear_down();
	}

	private function remove_test_tree( $path ) {
		if ( is_link( $path ) || is_file( $path ) ) {
			@unlink( $path );
			return;
		}
		if ( ! is_dir( $path ) ) {
			return;
		}
		$entries = scandir( $path );
		if ( false === $entries ) {
			return;
		}
		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$this->remove_test_tree( trailingslashit( $path ) . $entry );
		}
		@rmdir( $path );
	}

	private function strict_security_pcntl_required() {
		$flag = getenv( 'SUPER_FORMS_STRICT_SECURITY_TESTS' );
		return is_string( $flag ) && '' !== $flag && '0' !== $flag && 'false' !== strtolower( $flag );
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

	private function create_owned_directory( $directory ) {
		$directory = untrailingslashit( wp_normalize_path( $directory ) );
		$this->assertTrue( wp_mkdir_p( $directory ), 'Could not create fixture directory ' . $directory );
		$this->owned_directories[] = $directory;
		return $directory;
	}

	private function generated_slot() {
		return (string) ( 1000000000000 + random_int( 0, 999999999 ) );
	}

	private function add_public_route_settings_filter( $require_authentication, $upload_dir = SUPER_FORMS_UPLOAD_DIR ) {
		$filter = static function ( $settings ) use ( $require_authentication, $upload_dir ) {
			$settings['file_upload_dir']        = $upload_dir;
			$settings['file_upload_auth']       = $require_authentication ? '1' : '';
			$settings['file_upload_auth_roles'] = $require_authentication ? 'administrator' : '';
			return $settings;
		};
		add_filter( 'super_form_settings_filter', $filter );
		return $filter;
	}

	/**
	 * Reflection supplement only. Row 10's boundary proof is exact body bytes,
	 * captured status_header() state, and persisted export-record state.
	 */
	private function download_cache_headers( $protected ) {
		$method = new ReflectionMethod( 'SUPER_Forms', 'download_cache_headers' );
		$method->setAccessible( true );
		return $method->invoke( null, $protected );
	}
	/**
	 * Reflection supplement only. Raw dispatcher headers are not observable in
	 * this CLI harness, so the nosniff assertion is scoped to the reflected
	 * parse_request() branch source.
	 */

	private function assert_parse_request_branch_sets_nosniff( $start_marker, $end_marker ) {
		$method = new ReflectionMethod( 'SUPER_Forms', 'parse_request' );
		$source = file( $method->getFileName() );
		$this->assertIsArray( $source, 'Could not read the reflected parse_request() source.' );
		$block = implode(
			'',
			array_slice(
				$source,
				$method->getStartLine() - 1,
				$method->getEndLine() - $method->getStartLine() + 1
			)
		);
		$start = strpos( $block, $start_marker );
		$this->assertNotFalse( $start, 'Could not locate the reflected branch start marker.' );
		$end = strpos( $block, $end_marker, $start );
		$this->assertNotFalse( $end, 'Could not locate the reflected branch end marker.' );
		$branch = substr( $block, $start, $end - $start );
		$this->assertStringContainsString( "header( 'X-Content-Type-Options: nosniff' )", $branch );
	}

	private function run_dispatcher_in_child( $query_vars, $method = 'GET' ) {
		$this->require_process_forking( 'The real public dispatcher regression requires pcntl fork, wait, and exec support.' );
		$output_file = tempnam( sys_get_temp_dir(), 'sf-row10-output-' );
		$status_file = tempnam( sys_get_temp_dir(), 'sf-row10-status-' );
		$this->assertNotFalse( $output_file );
		$this->assertNotFalse( $status_file );

		$pid = pcntl_fork();
		$this->assertNotSame( -1, $pid );

		if ( 0 === $pid ) {
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
					while ( ob_get_level() > 0 ) {
						@ob_end_flush();
					}
					pcntl_exec( PHP_BINARY, array( '-r', 'exit(' . $status . ');' ) );
				}
			);
			add_filter(
				'status_header',
				static function ( $status_header, $code ) use ( $status_file ) {
					file_put_contents( $status_file, (string) $code, LOCK_EX );
					return $status_header;
				},
				10,
				2
			);
			$_GET                       = array();
			$_SERVER['REQUEST_METHOD']  = $method;
			$_SERVER['SERVER_SOFTWARE'] = 'PHPUnit';
			unset( $_SERVER['HTTP_IF_MODIFIED_SINCE'], $_SERVER['HTTP_IF_NONE_MATCH'] );
			$wp             = new stdClass();
			$wp->query_vars = $query_vars;
			try {
				SUPER_Forms()->parse_request( $wp );
				$returned = true;
			} catch ( Super_Forms_Proof_Row10_Wp_Die_Exception $e ) {
			} catch ( Throwable $e ) {
				echo get_class( $e ) . ': ' . $e->getMessage();
				$returned = true;
			}
			exit( 97 );
		}

		$status = null;
		$this->assertSame( $pid, pcntl_waitpid( $pid, $status ), 'Could not wait for the dispatcher child.' );
		$this->assertTrue( pcntl_wifexited( $status ), 'Dispatcher child terminated by signal.' );
		$this->assertSame( 0, pcntl_wexitstatus( $status ), 'Dispatcher returned instead of taking its terminal response path.' );

		global $wpdb;
		if ( isset( $wpdb ) && method_exists( $wpdb, 'check_connection' ) ) {
			$this->assertTrue( $wpdb->check_connection( false ), 'Database connection did not recover after the dispatcher child.' );
		}

		$output      = file_get_contents( $output_file );
		$status_code = file_get_contents( $status_file );
		unlink( $output_file );
		unlink( $status_file );
		return array(
			'output'      => false === $output ? '' : $output,
			'status_code' => ( false === $status_code || '' === $status_code ) ? null : (int) $status_code,
		);
	}

	public function test_public_sfgtfi_dispatcher_serves_exact_inline_image_bytes_with_safe_basename_and_no_cache_policy() {
		$slot     = $this->generated_slot();
		$dir      = $this->create_owned_directory( trailingslashit( $this->default_root ) . $slot );
		$basename = "sea' side.jpg";
		$file     = trailingslashit( $dir ) . $basename;
		$bytes    = "\xFF\xD8\xFF\xD9row10-inline-" . wp_generate_uuid4();
		$this->assertNotFalse( file_put_contents( $file, $bytes ) );

		$filter = $this->add_public_route_settings_filter( true );
		try {
			$result = $this->run_dispatcher_in_child( array( 'sfgtfi' => $slot . '/' . $basename ) );
		} finally {
			remove_filter( 'super_form_settings_filter', $filter );
		}

		$this->assertSame( $bytes, $result['output'] );
		$this->assertSame( $bytes, file_get_contents( $file ), 'Download unexpectedly mutated its source file.' );
		$mime = wp_check_filetype( basename( $file ) );
		$this->assertSame( 'image/jpeg', $mime['type'] );
		$this->assertSame( 0, strpos( $mime['type'], 'image/' ), 'An image/* mime selects the inline disposition branch.' );
		$this->assertSame( 'sea-side.jpg', sanitize_file_name( basename( $file ) ) );

		$protected = $this->download_cache_headers( true );
		$this->assertStringContainsString( 'no-cache', $protected['Cache-Control'] );
		$this->assertStringContainsString( 'no-store', $protected['Cache-Control'] );
		$this->assertStringContainsString( 'private', $protected['Cache-Control'] );
		$this->assertLessThanOrEqual( time(), strtotime( $protected['Expires'] ) );
		$this->assertFalse( $protected['Last-Modified'] );
		$this->assertFalse( $protected['ETag'] );
		$this->assertSame( 'Cookie', $protected['Vary'] );

		$this->assert_parse_request_branch_sets_nosniff(
			"array_key_exists( 'sfgtfi', \$wp->query_vars )",
			'fpassthru( $handle )'
		);
	}

	public function test_public_sfgtfi_dispatcher_serves_exact_attachment_bytes_with_safe_basename() {
		$slot     = $this->generated_slot();
		$dir      = $this->create_owned_directory( trailingslashit( $this->default_root ) . $slot );
		$basename = 'report v2.pdf';
		$file     = trailingslashit( $dir ) . $basename;
		$bytes    = "%PDF-1.4\nrow10-attachment-" . wp_generate_uuid4() . "\n%%EOF";
		$this->assertNotFalse( file_put_contents( $file, $bytes ) );

		$filter = $this->add_public_route_settings_filter( true );
		try {
			$result = $this->run_dispatcher_in_child( array( 'sfgtfi' => $slot . '/' . $basename ) );
		} finally {
			remove_filter( 'super_form_settings_filter', $filter );
		}

		$this->assertSame( $bytes, $result['output'] );
		$this->assertSame( $bytes, file_get_contents( $file ) );
		$mime = wp_check_filetype( basename( $file ) );
		$this->assertSame( 'application/pdf', $mime['type'] );
		$this->assertNotSame( 0, strpos( $mime['type'], 'image/' ), 'A non-image/* mime selects the attachment disposition branch.' );
		$this->assertSame( 'report-v2.pdf', sanitize_file_name( basename( $file ) ) );

		$protected = $this->download_cache_headers( true );
		$this->assertStringContainsString( 'no-cache', $protected['Cache-Control'] );
		$this->assertStringContainsString( 'no-store', $protected['Cache-Control'] );
		$this->assertStringContainsString( 'private', $protected['Cache-Control'] );

		$this->assert_parse_request_branch_sets_nosniff(
			"array_key_exists( 'sfgtfi', \$wp->query_vars )",
			'fpassthru( $handle )'
		);
	}

	private function create_export_attachment( $basename, $bytes ) {
		$upload_dir = wp_upload_dir();
		$this->assertEmpty( $upload_dir['error'] );
		$dir  = $this->create_owned_directory( trailingslashit( $upload_dir['basedir'] ) . 'sf-row10-export-' . str_replace( '-', '', wp_generate_uuid4() ) );
		$file = trailingslashit( $dir ) . $basename;
		$this->assertNotFalse( file_put_contents( $file, $bytes ) );
		$attachment_id = wp_insert_attachment(
			array(
				'post_author'    => $this->admin_id,
				'post_mime_type' => 'text/plain',
				'post_status'    => 'inherit',
				'post_title'     => 'Row 10 export fixture',
			),
			$file
		);
		$this->assertIsInt( $attachment_id );
		$this->assertGreaterThan( 0, $attachment_id );
		$this->assertSame( wp_normalize_path( $file ), wp_normalize_path( get_attached_file( $attachment_id ) ) );
		$this->owned_attachment_ids[] = $attachment_id;
		return array( 'attachment_id' => $attachment_id, 'file' => $file, 'dir' => $dir );
	}

	public function test_public_sfdlfi_dispatcher_post_denies_with_zero_bytes_and_a_captured_404_while_get_yields_the_exact_grant_once() {
		$basename = "final report'.txt";
		$bytes    = 'row10-export-' . wp_generate_uuid4();
		$fixture  = $this->create_export_attachment( $basename, $bytes );

		$url = SUPER_Forms::create_export_download_url( $fixture['attachment_id'] );
		$this->assertIsString( $url );
		$query = array();
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$this->assertSame( $fixture['attachment_id'], absint( $query['sfdlfi'] ) );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $query['sfdlfi_token'] );

		$die_filter = static function () {
			return static function ( $message = '', $title = '', $args = array() ) {
				$response = ( is_array( $args ) && isset( $args['response'] ) ) ? (int) $args['response'] : 0;
				if ( $response > 0 ) {
					status_header( $response );
				}
				throw new Super_Forms_Proof_Row10_Wp_Die_Exception();
			};
		};
		add_filter( 'wp_die_handler', $die_filter );
		try {
			$post_result = $this->run_dispatcher_in_child(
				array( 'sfdlfi' => $query['sfdlfi'], 'sfdlfi_token' => $query['sfdlfi_token'] ),
				'POST'
			);
		} finally {
			remove_filter( 'wp_die_handler', $die_filter );
		}

		$this->assertSame( '', $post_result['output'], 'A non-GET sfdlfi request emitted file bytes.' );
		$this->assertSame( 404, $post_result['status_code'], 'A non-GET sfdlfi request did not record a 404 status.' );
		clean_post_cache( $fixture['attachment_id'] );
		$this->assertNotNull( get_post( $fixture['attachment_id'] ), 'A denied POST consumed the export grant.' );
		$this->assertFileExists( $fixture['file'], 'A denied POST deleted the export file.' );
		$this->assertSame(
			hash( 'sha256', $query['sfdlfi_token'] ),
			get_post_meta( $fixture['attachment_id'], '_super_forms_export_token_hash', true ),
			'A denied POST consumed the single-use export token.'
		);

		$expected_mime = get_post_mime_type( $fixture['attachment_id'] );
		$this->assertSame( 'text/plain', $expected_mime );
		$get_result = $this->run_dispatcher_in_child(
			array( 'sfdlfi' => $query['sfdlfi'], 'sfdlfi_token' => $query['sfdlfi_token'] ),
			'GET'
		);
		$this->assertSame( $bytes, $get_result['output'] );
		$this->assertSame( 'final-report.txt', sanitize_file_name( basename( $fixture['file'] ) ) );

		$protected = $this->download_cache_headers( true );
		$this->assertStringContainsString( 'no-cache', $protected['Cache-Control'] );
		$this->assertStringContainsString( 'no-store', $protected['Cache-Control'] );
		$this->assertStringContainsString( 'private', $protected['Cache-Control'] );

		$this->assert_parse_request_branch_sets_nosniff(
			"array_key_exists( 'sfdlfi', \$wp->query_vars )",
			"array_key_exists( 'sfgtfi', \$wp->query_vars )"
		);

		clean_post_cache( $fixture['attachment_id'] );
		$this->assertNull( get_post( $fixture['attachment_id'] ), 'A successful GET did not consume the single-use export attachment.' );
		$this->assertFileDoesNotExist( $fixture['file'], 'A successful GET did not delete the exported source file.' );
	}

	public function test_public_sfdlfi_dispatcher_streams_a_large_export_in_bounded_chunks_and_cleans_up() {
		$basename = 'large-export.txt';
		// Larger than the dispatcher's bounded read chunk so streaming must loop.
		$bytes = str_repeat( 'row10-large-' . wp_generate_uuid4() . "\n", 4096 );
		$this->assertGreaterThan( 8192, strlen( $bytes ) );
		$fixture = $this->create_export_attachment( $basename, $bytes );

		$url = SUPER_Forms::create_export_download_url( $fixture['attachment_id'] );
		$this->assertIsString( $url );
		$query = array();
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		$result = $this->run_dispatcher_in_child(
			array( 'sfdlfi' => $query['sfdlfi'], 'sfdlfi_token' => $query['sfdlfi_token'] ),
			'GET'
		);
		$this->assertSame( $bytes, $result['output'], 'A large export was not streamed with identical bytes.' );

		clean_post_cache( $fixture['attachment_id'] );
		$this->assertNull( get_post( $fixture['attachment_id'] ), 'A streamed large export did not clean up its attachment record.' );
		$this->assertFileDoesNotExist( $fixture['file'], 'A streamed large export did not delete its source file.' );
		$this->assertSame(
			'',
			get_post_meta( $fixture['attachment_id'], '_super_forms_export_token_hash', true ),
			'A streamed large export did not consume its single-use token.'
		);
	}
}

class Test_Super_Forms_Proof_Row15_Resolver_Url_Components extends WP_UnitTestCase {

	private $default_root;
	private $fixture_dir;
	private $fixture_file;
	private $fixture_bytes;
	private $valid_url;

	public function set_up() {
		parent::set_up();
		$this->default_root = wp_normalize_path(
			trailingslashit( ABSPATH ) . trim( wp_normalize_path( SUPER_FORMS_UPLOAD_DIR ), '/' )
		);
		$slot               = (string) ( 1000000000000 + random_int( 0, 999999999 ) );
		$this->fixture_dir  = untrailingslashit( wp_normalize_path( trailingslashit( $this->default_root ) . $slot ) );
		$this->assertTrue( wp_mkdir_p( $this->fixture_dir ) );
		$this->fixture_bytes = 'row15-proof-' . wp_generate_uuid4();
		$this->fixture_file  = trailingslashit( $this->fixture_dir ) . 'proof-fixture.txt';
		$this->assertNotFalse( file_put_contents( $this->fixture_file, $this->fixture_bytes ) );
		$route           = $slot . '/proof-fixture.txt';
		$this->valid_url = trailingslashit( site_url( '/' ) ) . 'sfgtfi/' . $route;

		// Sanity control: the unmodified baseline URL must resolve, otherwise a
		// denial provoked by mutating one component would be meaningless.
		$baseline = SUPER_Forms::resolve_owned_upload_file( $this->valid_url, array() );
		$this->assertIsArray( $baseline, 'The baseline sfgtfi URL did not resolve before any component was mutated.' );
		$this->assertSame( wp_normalize_path( realpath( $this->fixture_file ) ), $baseline['file'] );
	}

	public function tear_down() {
		if ( is_file( $this->fixture_file ) ) {
			unlink( $this->fixture_file );
		}
		if ( is_dir( $this->fixture_dir ) ) {
			rmdir( $this->fixture_dir );
		}
		parent::tear_down();
	}

	private function build_variant_url( array $overrides ) {
		$parts = wp_parse_url( $this->valid_url );
		foreach ( $overrides as $key => $value ) {
			if ( null === $value ) {
				unset( $parts[ $key ] );
			} else {
				$parts[ $key ] = $value;
			}
		}
		$url = $parts['scheme'] . '://';
		if ( isset( $parts['user'] ) ) {
			$url .= $parts['user'];
			if ( isset( $parts['pass'] ) ) {
				$url .= ':' . $parts['pass'];
			}
			$url .= '@';
		}
		$url .= $parts['host'];
		if ( isset( $parts['port'] ) ) {
			$url .= ':' . $parts['port'];
		}
		$url .= isset( $parts['path'] ) ? $parts['path'] : '';
		if ( isset( $parts['query'] ) ) {
			$url .= '?' . $parts['query'];
		}
		if ( isset( $parts['fragment'] ) ) {
			$url .= '#' . $parts['fragment'];
		}
		return $url;
	}

	private function build_component_variant( $component ) {
		$site = wp_parse_url( site_url( '/' ) );
		switch ( $component ) {
			case 'scheme':
				$flipped = ( 'https' === strtolower( $site['scheme'] ) ) ? 'http' : 'https';
				return $this->build_variant_url( array( 'scheme' => $flipped ) );
			case 'host':
				return $this->build_variant_url( array( 'host' => 'evil-' . $site['host'] ) );
			case 'port':
				$current = isset( $site['port'] ) ? (int) $site['port'] : 0;
				return $this->build_variant_url( array( 'port' => $current + 4444 ) );
			case 'userinfo':
				return $this->build_variant_url( array( 'user' => 'attacker' ) );
			case 'user-pass':
				return $this->build_variant_url( array( 'user' => 'attacker', 'pass' => 'secret' ) );
			case 'query':
				return $this->build_variant_url( array( 'query' => 'x=1' ) );
			case 'fragment':
				return $this->build_variant_url( array( 'fragment' => 'frag' ) );
			case 'percent-path':
				$path = wp_parse_url( $this->valid_url, PHP_URL_PATH );
				return $this->build_variant_url( array( 'path' => $path . '%41' ) );
			default:
				$this->fail( 'Unknown URL component ' . $component );
		}
	}

	public static function denied_url_component_provider() {
		return array(
			'scheme flips from the site scheme'             => array( 'scheme' ),
			'host does not match the site host'              => array( 'host' ),
			'port does not match the site port'               => array( 'port' ),
			'userinfo without a password is present'          => array( 'userinfo' ),
			'userinfo with a user and a password is present'  => array( 'user-pass' ),
			'a query string is present'                       => array( 'query' ),
			'a fragment is present'                           => array( 'fragment' ),
			'the path contains a percent-encoded byte'        => array( 'percent-path' ),
		);
	}

	/**
	 * @dataProvider denied_url_component_provider
	 */
	public function test_public_resolver_denies_a_url_with_exactly_one_mutated_component( $component ) {
		$variant = $this->build_component_variant( $component );
		$this->assertNotSame( $this->valid_url, $variant, $component );
		$this->assertFalse( SUPER_Forms::resolve_owned_upload_file( $variant, array() ), $component );
		$this->assertTrue( is_file( $this->fixture_file ), $component . ': fixture file was removed' );
		$this->assertSame( $this->fixture_bytes, file_get_contents( $this->fixture_file ), $component . ': fixture file was mutated' );
	}
}

class Test_Super_Forms_Proof_Row13_Delete_Dispatch extends Super_Forms_Upload_Security_Test_Case {

	private function build_owned_root_file( $bytes, $slot, $basename ) {
		list( $parent, $root ) = $this->create_temporary_root( true );
		$slot_dir = trailingslashit( $root ) . $slot;
		$this->assertTrue( wp_mkdir_p( $slot_dir ) );
		$file = trailingslashit( $slot_dir ) . $basename;
		$this->assertNotFalse( file_put_contents( $file, $bytes ) );
		return array(
			'parent'  => $parent,
			'root'    => $root,
			'file'    => $file,
			'setting' => '../' . basename( $parent ) . '/' . basename( $root ),
			'subdir'  => '../' . basename( $parent ) . '/' . basename( $root ) . '/' . $slot . '/' . $basename,
		);
	}

	/**
	 * Build a fully sealed custom-storage entry-data file record the exact
	 * way production code does (SUPER_Ajax::build_owned_upload +
	 * owned_upload_file_record), so its _super_file_proof genuinely matches.
	 */
	private function build_sealed_custom_record( $form_id, $field_name, $bytes, $basename, $slot = null, $url_domain = null ) {
		$slot       = null !== $slot ? $slot : (string) ( 1000000000000 + random_int( 0, 999999999 ) );
		$owned_root = $this->build_owned_root_file( $bytes, $slot, $basename );
		$settings   = array( 'file_upload_dir' => $owned_root['setting'] );
		$descriptor = SUPER_Forms::resolve_owned_upload_file( $owned_root['file'], $settings );
		$this->assertIsArray( $descriptor );
		$route_suffix = str_replace( '../', '__/', $owned_root['subdir'] );
		$domain       = null !== $url_domain ? $url_domain : trailingslashit( get_option( 'siteurl' ) );
		$url          = trailingslashit( $domain ) . 'sfgtfi/' . ltrim( $route_suffix, '/' );
		$owned        = $this->invoke_ajax_private(
			'build_owned_upload',
			array(
				$form_id,
				$field_name,
				$descriptor['file'],
				$descriptor['mime'],
				$url,
				0,
				$descriptor['root'],
				filesize( $descriptor['file'] ),
				$owned_root['subdir'],
			)
		);
		$this->assertIsArray( $owned );
		$record = $this->invoke_ajax_private( 'owned_upload_file_record', array( $owned ) );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $record['_super_file_proof'] );
		return array(
			'record' => $record,
			'file'   => $owned_root['file'],
			'slot'   => $slot,
			'owned_root' => $owned_root,
		);
	}

	private function with_entry_delete_enabled( $entry_id ) {
		$forms        = SUPER_Forms();
		$had_settings = isset( $forms->global_settings );
		$original     = $had_settings ? $forms->global_settings : null;
		$forms->global_settings = is_array( $original ) ? $original : array();
		$forms->global_settings['file_upload_entry_delete'] = 'true';
		try {
			$this->assertNotFalse(
				has_action( 'before_delete_post', array( $forms, 'delete_entry_attachments' ) ),
				'delete_entry_attachments is not wired to before_delete_post.'
			);
			return wp_delete_post( $entry_id, true );
		} finally {
			if ( $had_settings ) {
				$forms->global_settings = $original;
			} else {
				unset( $forms->global_settings );
			}
		}
	}

	private function create_entry( $form_id ) {
		return self::factory()->post->create(
			array(
				'post_type'   => 'super_contact_entry',
				'post_status' => 'super_read',
				'post_parent' => $form_id,
			)
		);
	}

	private function create_legacy_child_attachment( $entry_id, $basename, $bytes ) {
		$upload_dir = wp_upload_dir();
		$this->assertEmpty( $upload_dir['error'] );
		$dir = trailingslashit( $upload_dir['basedir'] ) . 'sf-proof-row13-' . str_replace( '-', '', wp_generate_uuid4() );
		$this->assertTrue( wp_mkdir_p( $dir ) );
		$this->temporary_parents[] = realpath( $dir );
		$file = trailingslashit( $dir ) . $basename;
		$this->assertNotFalse( file_put_contents( $file, $bytes ) );
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'text/plain',
				'post_parent'    => $entry_id,
				'post_status'    => 'inherit',
				'post_title'     => 'Row 13 legacy child',
			),
			$file,
			$entry_id,
			true
		);
		$this->assertIsInt( $attachment_id );
		$this->assertGreaterThan( 0, $attachment_id );
		$this->attachment_ids[] = $attachment_id;
		return array( 'attachment_id' => $attachment_id, 'file' => $file );
	}

	public function test_dispatcher_deletes_exact_sealed_custom_file_and_exact_legacy_child_attachment_via_before_delete_post_hook() {
		$form_id  = $this->create_form( 'publish' );
		$entry_id = $this->create_entry( $form_id );

		$sealed = $this->build_sealed_custom_record( $form_id, 'documents', 'sealed-custom-bytes', 'sealed.txt' );
		$legacy = $this->create_legacy_child_attachment( $entry_id, 'legacy-child.txt', 'legacy-child-bytes' );

		update_post_meta(
			$entry_id,
			'_super_contact_entry_data',
			array(
				'documents'   => array( 'type' => 'files', 'files' => array( $sealed['record'] ) ),
				'attachments' => array( 'type' => 'files', 'files' => array( array( 'attachment' => $legacy['attachment_id'] ) ) ),
			)
		);

		$deleted = $this->with_entry_delete_enabled( $entry_id );
		$this->assertInstanceOf( 'WP_Post', $deleted );

		clean_post_cache( $legacy['attachment_id'] );
		$this->assertNull( get_post( $legacy['attachment_id'] ), 'Dispatcher did not delete the exact legacy child attachment.' );
		$this->assertFileDoesNotExist( $legacy['file'], 'Dispatcher did not delete the legacy child attachment file.' );
		$this->assertFileDoesNotExist( $sealed['file'], 'Dispatcher did not delete the exact sealed custom file.' );
	}

	public function test_dispatcher_preserves_a_custom_file_whose_stored_proof_fails_validation() {
		$form_id  = $this->create_form( 'publish' );
		$entry_id = $this->create_entry( $form_id );

		$sealed                        = $this->build_sealed_custom_record( $form_id, 'documents', 'proofless-bytes', 'proofless.txt' );
		$record                        = $sealed['record'];
		$record['_super_file_proof']   = 'not-a-valid-proof';
		update_post_meta(
			$entry_id,
			'_super_contact_entry_data',
			array( 'documents' => array( 'type' => 'files', 'files' => array( $record ) ) )
		);

		$deleted = $this->with_entry_delete_enabled( $entry_id );
		$this->assertInstanceOf( 'WP_Post', $deleted );

		$this->assertFileExists( $sealed['file'], 'Dispatcher deleted a custom file whose stored proof failed validation.' );
		$this->assertSame( 'proofless-bytes', file_get_contents( $sealed['file'] ) );
	}

	public function test_dispatcher_preserves_a_dual_root_custom_file_when_stored_path_and_subdir_disagree_on_the_real_file() {
		$form_id  = $this->create_form( 'publish' );
		$entry_id = $this->create_entry( $form_id );

		$slot = (string) ( 1000000000000 + random_int( 0, 999999999 ) );
		// Root A: the record's literal `path` field and its genuine proof.
		$a = $this->build_sealed_custom_record( $form_id, 'documents', 'root-a-bytes', 'dual-root.txt', $slot );
		// Root B: an independent, otherwise legitimate owned root that happens to
		// hold a DIFFERENT real file at the exact same slot and basename.
		$b = $this->build_owned_root_file( 'root-b-bytes', $slot, 'dual-root.txt' );

		$decoy_record             = $a['record'];
		$decoy_record['subdir']   = $b['subdir'];
		update_post_meta(
			$entry_id,
			'_super_contact_entry_data',
			array( 'documents' => array( 'type' => 'files', 'files' => array( $decoy_record ) ) )
		);

		$deleted = $this->with_entry_delete_enabled( $entry_id );
		$this->assertInstanceOf( 'WP_Post', $deleted );

		$this->assertFileExists( $a['file'], 'Dispatcher deleted the path-owning file of a dual-root record.' );
		$this->assertSame( 'root-a-bytes', file_get_contents( $a['file'] ) );
		$this->assertFileExists( $b['file'], 'Dispatcher deleted the subdir-owning decoy file of a dual-root record.' );
		$this->assertSame( 'root-b-bytes', file_get_contents( $b['file'] ) );
	}

	public function test_dispatcher_preserves_an_attachment_referenced_under_two_ambiguous_field_names() {
		$form_id  = $this->create_form( 'publish' );
		$entry_id = $this->create_entry( $form_id );

		$upload_dir = wp_upload_dir();
		$this->assertEmpty( $upload_dir['error'] );
		$dir = trailingslashit( $upload_dir['basedir'] ) . 'sf-proof-row13-ambiguous-' . str_replace( '-', '', wp_generate_uuid4() );
		$this->assertTrue( wp_mkdir_p( $dir ) );
		$this->temporary_parents[] = realpath( $dir );
		$file = trailingslashit( $dir ) . 'ambiguous.txt';
		$this->assertNotFalse( file_put_contents( $file, 'ambiguous-bytes' ) );
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'text/plain',
				'post_parent'    => $entry_id,
				'post_status'    => 'inherit',
				'post_title'     => 'Row 13 ambiguous attachment',
			),
			$file,
			$entry_id,
			true
		);
		$this->assertIsInt( $attachment_id );
		$this->assertGreaterThan( 0, $attachment_id );
		$this->attachment_ids[] = $attachment_id;
		add_post_meta( $attachment_id, 'super-forms-form-upload-file', true );
		add_post_meta( $attachment_id, '_super_forms_upload_form_id', $form_id );
		add_post_meta( $attachment_id, '_super_forms_upload_field', 'field_a' );

		update_post_meta(
			$entry_id,
			'_super_contact_entry_data',
			array(
				'field_a' => array( 'type' => 'files', 'files' => array( array( 'attachment' => $attachment_id ) ) ),
				'field_b' => array( 'type' => 'files', 'files' => array( array( 'attachment' => $attachment_id ) ) ),
			)
		);

		$deleted = $this->with_entry_delete_enabled( $entry_id );
		$this->assertInstanceOf( 'WP_Post', $deleted );

		clean_post_cache( $attachment_id );
		$this->assertNotNull( get_post( $attachment_id ), 'Dispatcher deleted an attachment referenced under two ambiguous field names.' );
		$this->assertFileExists( $file );
	}

	public function test_dispatcher_deletes_a_sealed_custom_file_across_a_changed_siteurl_via_sfgtfi_route_suffix_parity() {
		$form_id  = $this->create_form( 'publish' );
		$entry_id = $this->create_entry( $form_id );

		$sealed = $this->build_sealed_custom_record(
			$form_id,
			'documents',
			'migrated-bytes',
			'migrated.txt',
			null,
			'https://old-migrated-domain.example'
		);
		$this->assertStringStartsWith( 'https://old-migrated-domain.example/sfgtfi/', $sealed['record']['url'] );
		$this->assertNotSame( trailingslashit( get_option( 'siteurl' ) ), 'https://old-migrated-domain.example/' );

		update_post_meta(
			$entry_id,
			'_super_contact_entry_data',
			array( 'documents' => array( 'type' => 'files', 'files' => array( $sealed['record'] ) ) )
		);

		$deleted = $this->with_entry_delete_enabled( $entry_id );
		$this->assertInstanceOf( 'WP_Post', $deleted );

		$this->assertFileDoesNotExist(
			$sealed['file'],
			'Dispatcher did not tolerate a changed siteurl with a matching /sfgtfi/ route suffix.'
		);
	}
}
