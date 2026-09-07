<?php
/**
 * Security regressions for the public upload download resolver and dispatchers.
 *
 * @package Super_Forms\Tests
 */


class Test_Security_Download_Route extends WP_UnitTestCase {

	private $attachment_ids = array();
	private $configured_relative;
	private $configured_root;
	private $default_fixture;
	private $default_root;
	private $owned_directories = array();
	private $owned_files = array();
	private $scope;
	private $unrelated_file;
	private $unrelated_bytes;
	private $legacy_prefix;
	public function set_up() {
		parent::set_up();

		$this->scope = 'sf-route-' . str_replace( '-', '', wp_generate_uuid4() );
		$legacy_base = substr( (string) floor( microtime( true ) * 1000 ), 0, 10 );
		$legacy_slot = abs( crc32( $this->scope ) ) % 1000;
		$legacy_attempts = 0;
		do {
			$this->legacy_prefix = $legacy_base . str_pad( (string) $legacy_slot, 3, '0', STR_PAD_LEFT );
			$occupied = file_exists( ABSPATH . '/' . $this->legacy_prefix ) || is_link( ABSPATH . '/' . $this->legacy_prefix );
			$legacy_slot = ( $legacy_slot + 1 ) % 1000;
			++$legacy_attempts;
		} while ( $occupied && $legacy_attempts < 1000 );
		$this->assertFalse( $occupied, 'Could not allocate an isolated legacy timestamp fixture.' );
		$this->create_owned_directory( wp_normalize_path( ABSPATH . '/' . $this->legacy_prefix ) );

		$default_root = wp_normalize_path(
			trailingslashit( ABSPATH ) . trim( wp_normalize_path( SUPER_FORMS_UPLOAD_DIR ), '/' )
		);
		$this->default_fixture = $default_root . '/' . $this->legacy_prefix;
		$this->create_owned_directory( $this->default_fixture );
		$this->default_root = wp_normalize_path( realpath( $default_root ) );

		$normalized_abspath = trailingslashit( wp_normalize_path( ABSPATH ) );
		$normalized_content = untrailingslashit( wp_normalize_path( WP_CONTENT_DIR ) );
		$this->assertSame( 0, strpos( $normalized_content, $normalized_abspath ) );
		$content_relative          = ltrim( substr( $normalized_content, strlen( $normalized_abspath ) ), '/' );
		$this->configured_relative = $content_relative . '/uploads/' . $this->scope . '-configured';
		$this->configured_root     = wp_normalize_path( $normalized_abspath . $this->configured_relative );
		$this->create_owned_directory( $this->configured_root );
		$this->configured_root = wp_normalize_path( realpath( $this->configured_root ) );

		$this->unrelated_bytes = "\xFF\xD8\xFF\xD9unrelated-" . $this->scope;
		$this->unrelated_file  = $this->default_fixture . '/unrelated.jpg';
		$this->create_owned_file( $this->unrelated_file, $this->unrelated_bytes );
	}

	public function tear_down() {
		$cleanup_errors = array();

		wp_set_current_user( 0 );
		foreach ( array_reverse( $this->attachment_ids ) as $attachment_id ) {
			clean_post_cache( $attachment_id );
			if ( get_post( $attachment_id ) && false === wp_delete_attachment( $attachment_id, true ) ) {
				$cleanup_errors[] = 'attachment ' . $attachment_id;
			}
		}
		foreach ( array_reverse( array_unique( $this->owned_files ) ) as $file ) {
			if ( ( is_file( $file ) || is_link( $file ) ) && ! @unlink( $file ) ) {
				$cleanup_errors[] = $file;
			}
		}
		foreach ( array_reverse( array_unique( $this->owned_directories ) ) as $directory ) {
			if ( is_dir( $directory ) && ! @rmdir( $directory ) ) {
				$cleanup_errors[] = $directory;
			}
		}

		parent::tear_down();

		if ( ! empty( $cleanup_errors ) ) {
			$this->fail( 'Failed to remove owned route fixtures: ' . implode( ', ', $cleanup_errors ) );
		}
	}

	private function create_owned_directory( $directory ) {
		$directory = untrailingslashit( wp_normalize_path( $directory ) );
		if ( ! is_dir( $directory ) ) {
			$this->assertTrue( wp_mkdir_p( $directory ), 'Could not create owned directory ' . $directory );
		}
		if ( ! in_array( $directory, $this->owned_directories, true ) ) {
			$this->owned_directories[] = $directory;
		}
		return $directory;
	}

	private function create_owned_file( $file, $bytes ) {
		$file = wp_normalize_path( $file );
		if ( ! in_array( $file, $this->owned_files, true ) ) {
			$this->owned_files[] = $file;
		}
		$this->assertNotFalse( file_put_contents( $file, $bytes ), 'Could not create owned file ' . $file );
		return $file;
	}

	private function create_default_nested_directory() {
		return $this->default_fixture;
	}

	private function create_outside_sibling_root( $suffix ) {
		$root = dirname( $this->default_root ) . '/' . basename( $this->default_root ) . '-' . $this->scope . '-' . $suffix;
		return $this->create_owned_directory( $root );
	}

	private function invoke_resolver( $route, $settings = array() ) {
		$method = new ReflectionMethod( 'SUPER_Forms', 'resolve_sfgtfi_file' );
		$method->setAccessible( true );
		return $method->invoke( null, $route, $settings );
	}
	private function track_owned_external_file( $file, $external_root ) {
		$file = wp_normalize_path( $file );
		if ( ! in_array( $file, $this->owned_files, true ) ) {
			$this->owned_files[] = $file;
		}
		$root = untrailingslashit( wp_normalize_path( realpath( $external_root ) ) );
		$directories = array();
		for ( $directory = untrailingslashit( wp_normalize_path( dirname( $file ) ) ); $directory !== $root; $directory = dirname( $directory ) ) {
			$this->assertSame( 0, strpos( $directory, trailingslashit( $root ) ) );
			$directories[] = $directory;
		}
		foreach ( array_reverse( $directories ) as $directory ) {
			$this->create_owned_directory( $directory );
		}
	}

	private function materialize_external_pdf( $external_setting, $external_root, $bytes ) {
		$missing = '__super_forms_route_settings_missing__';
		$previous_option = get_option( 'super_settings', $missing );
		$forms = SUPER_Forms();
		$had_global_settings = isset( $forms->global_settings );
		$previous_global_settings = $had_global_settings ? $forms->global_settings : null;
		$had_upload_dir = array_key_exists( 'super_upload_dir', $GLOBALS );
		$previous_upload_dir = $had_upload_dir ? $GLOBALS['super_upload_dir'] : null;

		try {
			$global_settings = array(
				'file_upload_dir' => $external_setting,
				'file_upload_use_year_month_folders' => '',
			);
			update_option( 'super_settings', $global_settings, false );
			$forms->global_settings = $global_settings;

			$data = array(
				'_generated_pdf_file' => array(
					'type' => 'files',
					'files' => array(
						'generated' => array(
							'value' => 'external-route.pdf',
							'label' => 'External route PDF',
							'datauristring' => 'data:application/pdf;base64,' . base64_encode( $bytes ),
						),
					),
				),
			);
			$method = new ReflectionMethod( 'SUPER_Ajax', 'materialize_generated_pdf' );
			$method->setAccessible( true );
			$materialized = $method->invoke(
				null,
				$data,
				1,
				array( '_pdf' => array( 'generate' => 'true' ) )
			);
			$owned_file = is_array( $materialized )
				&& isset( $materialized['owned_files'][0]['file'] )
				&& is_string( $materialized['owned_files'][0]['file'] )
				? wp_normalize_path( $materialized['owned_files'][0]['file'] )
				: false;
			if ( $owned_file && is_file( $owned_file ) ) {
				$this->track_owned_external_file( $owned_file, $external_root );
			}

			$this->assertNotWPError( $materialized );
			$this->assertIsArray( $materialized );
			$this->assertArrayHasKey( 'data', $materialized );
			$this->assertArrayHasKey( '_generated_pdf_file', $materialized['data'] );
			$this->assertArrayHasKey( 'files', $materialized['data']['_generated_pdf_file'] );
			$this->assertArrayHasKey( 'generated', $materialized['data']['_generated_pdf_file']['files'] );
			$record = $materialized['data']['_generated_pdf_file']['files']['generated'];
			$this->assertIsArray( $record );
			$this->assertSame( $owned_file, wp_normalize_path( $record['path'] ) );
			$this->assertSame( $bytes, file_get_contents( $owned_file ) );
			return $record;
		} finally {
			if ( $previous_option === $missing ) {
				delete_option( 'super_settings' );
			} else {
				update_option( 'super_settings', $previous_option, false );
			}
			if ( $had_global_settings ) {
				$forms->global_settings = $previous_global_settings;
			} else {
				unset( $forms->global_settings );
			}
			if ( $had_upload_dir ) {
				$GLOBALS['super_upload_dir'] = $previous_upload_dir;
			} else {
				unset( $GLOBALS['super_upload_dir'] );
			}
		}
	}


	private function assert_fixture_integrity( $protected_files, $absent_files = array(), $links = array() ) {
		foreach ( $protected_files as $file => $expected_bytes ) {
			$this->assertTrue( is_file( $file ), 'Denied route removed protected fixture ' . $file );
			$this->assertSame( $expected_bytes, file_get_contents( $file ), 'Denied route changed protected fixture ' . $file );
		}
		foreach ( $absent_files as $file ) {
			$this->assertFalse( file_exists( $file ) || is_link( $file ), 'Denied route created its nonexistent candidate ' . $file );
		}
		foreach ( $links as $link ) {
			$this->assertTrue( is_link( $link ), 'Denied route removed or replaced an escape symlink ' . $link );
		}
	}

	private function assert_resolution_denied( $case ) {
		$result = $this->invoke_resolver( $case['route'], $case['settings'] );
		$this->assert_fixture_integrity( $case['protected_files'], $case['absent_files'], $case['links'] );
		$this->assertFalse( $result, 'Unsafe route unexpectedly resolved: ' . $case['label'] );
	}

	private function relative_to_abspath( $path ) {
		$path    = wp_normalize_path( $path );
		$abspath = trailingslashit( wp_normalize_path( ABSPATH ) );
		$this->assertSame( 0, strpos( $path, $abspath ) );
		return ltrim( substr( $path, strlen( $abspath ) ), '/' );
	}

	private function build_denial_case( $name ) {
		$settings        = array();
		$protected_files = array( $this->unrelated_file => $this->unrelated_bytes );
		$absent_files    = array();
		$links           = array();

		switch ( $name ) {
			case 'raw-parent':
			case 'encoded-parent':
			case 'legacy-parent':
				$legacy_target = $this->create_outside_sibling_root( $name ) . '/secret.jpg';
				$target_bytes  = "\xFF\xD8\xFF\xD9outside-" . $name;
				$protected_files[ $legacy_target ] = $target_bytes;
				$this->create_owned_file( $legacy_target, $target_bytes );
				if ( 'raw-parent' === $name ) {
					$route = '../' . basename( dirname( $legacy_target ) ) . '/secret.jpg';
				} elseif ( 'encoded-parent' === $name ) {
					$route = '%2e%2e%2f' . basename( dirname( $legacy_target ) ) . '%2fsecret.jpg';
				} else {
					$route = $this->legacy_prefix . '/__/' . $this->relative_to_abspath( $legacy_target );
				}
				break;

			case 'absolute-posix':
			case 'absolute-windows':
				$outside_root = $this->create_outside_sibling_root( $name );
				$target       = $outside_root . '/absolute.jpg';
				$target_bytes = "\xFF\xD8\xFF\xD9absolute-" . $name;
				$this->create_owned_file( $target, $target_bytes );
				$protected_files[ $target ] = $target_bytes;
				$route = 'absolute-posix' === $name ? $target : 'C:/route-security/' . $this->scope . '/absolute.jpg';
				break;

			case 'nul-byte':
				$nested       = $this->create_default_nested_directory();
				$target       = $nested . '/nul.jpg';
				$target_bytes = "\xFF\xD8\xFF\xD9nul";
				$this->create_owned_file( $target, $target_bytes );
				$protected_files[ $target ] = $target_bytes;
				$route = $this->legacy_prefix . '/nul.jpg' . "\0" . '.php';
				break;

			case 'sibling-prefix':
				$outside_root = $this->create_outside_sibling_root( $name );
				$target       = $outside_root . '/prefix.jpg';
				$target_bytes = "\xFF\xD8\xFF\xD9prefix";
				$this->create_owned_file( $target, $target_bytes );
				$protected_files[ $target ] = $target_bytes;
				$route = $this->relative_to_abspath( $target );
				break;

			case 'symlink-escape':
				if ( ! function_exists( 'symlink' ) ) {
					$this->markTestSkipped( 'The symlink extension is unavailable.' );
				}
				$outside_root = $this->create_outside_sibling_root( $name );
				$target       = $outside_root . '/symlink-target.jpg';
				$target_bytes = "\xFF\xD8\xFF\xD9symlink-target";
				$this->create_owned_file( $target, $target_bytes );
				$protected_files[ $target ] = $target_bytes;
				$link = $this->default_fixture . '/escape.jpg';
				if ( ! @symlink( $target, $link ) ) {
					$this->markTestSkipped( 'The test filesystem does not permit symlinks.' );
				}
				$this->owned_files[] = $link;
				$links[]             = $link;
				$route               = $this->legacy_prefix . '/escape.jpg';
				break;

			case 'directory':
				$directory = $this->create_owned_directory( $this->default_fixture . '/directory-candidate' );
				$marker    = $directory . '/marker.txt';
				$this->create_owned_file( $marker, 'directory-marker' );
				$protected_files[ $marker ] = 'directory-marker';
				$route = $this->legacy_prefix . '/directory-candidate';
				break;

			case 'nonexistent':
				$missing        = $this->default_fixture . '/missing.jpg';
				$absent_files[] = $missing;
				$route          = $this->legacy_prefix . '/missing.jpg';
				break;

			case 'case-suffix':
			case 'compound-suffix':
				$nested = $this->create_default_nested_directory();
				$name   = 'case-suffix' === $name ? 'payload.PhP' : 'payload.php.jpg';
				$target = $nested . '/' . $name;
				$bytes  = 'disallowed-suffix-fixture-' . $name;
				$this->create_owned_file( $target, $bytes );
				$protected_files[ $target ] = $bytes;
				$route = $this->legacy_prefix . '/' . $name;
				break;

			case 'non-generated-parent':
				$plain_root = $this->create_owned_directory( $this->default_root . '/' . $this->scope . '-plain' );
				$target     = $plain_root . '/ordinary.jpg';
				$bytes      = "\xFF\xD8\xFF\xD9non-generated-parent";
				$this->create_owned_file( $target, $bytes );
				$protected_files[ $target ] = $bytes;
				$route = basename( $plain_root ) . '/ordinary.jpg';
				break;

			case 'unknown-suffix':
			case 'no-suffix':
				$name   = 'unknown-suffix' === $name ? 'secret.env' : 'README';
				$target = $this->default_fixture . '/' . $name;
				$bytes  = 'non-downloadable-fixture-' . $name;
				$this->create_owned_file( $target, $bytes );
				$protected_files[ $target ] = $bytes;
				$route = $this->legacy_prefix . '/' . $name;
				break;

			case 'nested-after-slot':
				$nested = $this->create_owned_directory( $this->default_fixture . '/nested' );
				$target = $nested . '/ordinary.jpg';
				$bytes  = "\xFF\xD8\xFF\xD9nested-after-slot";
				$this->create_owned_file( $target, $bytes );
				$protected_files[ $target ] = $bytes;
				$route = $this->legacy_prefix . '/nested/ordinary.jpg';
				break;

			default:
				$this->fail( 'Unknown denial case ' . $name );
				$route = '';
		}

		return array(
			'label'           => $name,
			'route'           => $route,
			'settings'        => $settings,
			'protected_files' => $protected_files,
			'absent_files'    => $absent_files,
			'links'           => $links,
		);
	}

	public static function denied_candidate_provider() {
		return array(
			'raw ../'                     => array( 'raw-parent' ),
			'encoded ../'                 => array( 'encoded-parent' ),
			'legacy __/'                  => array( 'legacy-parent' ),
			'POSIX absolute path'         => array( 'absolute-posix' ),
			'Windows absolute path'       => array( 'absolute-windows' ),
			'NUL byte'                    => array( 'nul-byte' ),
			'sibling-prefix root'         => array( 'sibling-prefix' ),
			'symlink escape'              => array( 'symlink-escape' ),
			'directory candidate'         => array( 'directory' ),
			'nonexistent candidate'       => array( 'nonexistent' ),
			'mixed-case dangerous suffix' => array( 'case-suffix' ),
			'compound dangerous suffix'   => array( 'compound-suffix' ),
			'non-generated parent'         => array( 'non-generated-parent' ),
			'unknown suffix'               => array( 'unknown-suffix' ),
			'no suffix'                    => array( 'no-suffix' ),
			'nested path after slot'       => array( 'nested-after-slot' ),
		);
	}

	/**
	 * @dataProvider denied_candidate_provider
	 */
	public function test_resolver_denies_unsafe_candidates_without_mutation( $name ) {
		$this->assert_resolution_denied( $this->build_denial_case( $name ) );
	}

	public function test_resolver_returns_exact_canonical_default_root_result_for_nested_allowlisted_file() {
		$nested = $this->create_default_nested_directory();
		$file   = $nested . '/default-image.JPG';
		$bytes  = "\xFF\xD8\xFF\xD9default-image";
		$this->create_owned_file( $file, $bytes );

		$root_relative = $this->legacy_prefix . '/default-image.JPG';
		$root_prefixed = trim( wp_normalize_path( SUPER_FORMS_UPLOAD_DIR ), '/' ) . '/' . $root_relative;
		$expected      = array(
			'file' => wp_normalize_path( realpath( $file ) ),
			'root' => $this->default_root,
		);

		$this->assertSame( $expected, $this->invoke_resolver( $root_relative ) );
		$this->assertSame( $expected, $this->invoke_resolver( $root_prefixed ) );
		$this->assertSame( $bytes, file_get_contents( $file ) );
		$this->assertSame( $this->unrelated_bytes, file_get_contents( $this->unrelated_file ) );
	}

	public function test_resolver_returns_exact_canonical_configured_root_result_for_nested_allowlisted_file() {
		$nested = $this->create_owned_directory( $this->configured_root . '/' . $this->legacy_prefix );
		$file   = $nested . '/configured-report.pdf';
		$bytes  = "%PDF-1.4\nconfigured-report\n%%EOF";
		$this->create_owned_file( $file, $bytes );

		$settings      = array( 'file_upload_dir' => $this->configured_relative );
		$root_relative = $this->legacy_prefix . '/configured-report.pdf';
		$root_prefixed = $this->configured_relative . '/' . $root_relative;
		$expected      = array(
			'file' => wp_normalize_path( realpath( $file ) ),
			'root' => $this->configured_root,
		);

		$this->assertSame( $expected, $this->invoke_resolver( $root_relative, $settings ) );
		$this->assertSame( $expected, $this->invoke_resolver( $root_prefixed, $settings ) );
		$this->assertSame( $bytes, file_get_contents( $file ) );
		$this->assertSame( $this->unrelated_bytes, file_get_contents( $this->unrelated_file ) );
	}

	public function test_email_keeps_content_and_configured_private_attachments_but_drops_parent_route_escapes() {
		global $wp_version;
		$content_directory = $this->create_owned_directory(
			untrailingslashit( wp_normalize_path( WP_CONTENT_DIR ) ) . '/uploads/' . $this->scope . '-mail-content'
		);
		$content_file  = $content_directory . '/content-attachment.pdf';
		$content_bytes = "%PDF-1.4\ncontent attachment\n%%EOF";
		$this->create_owned_file( $content_file, $content_bytes );
		$content_relative = ltrim(
			substr(
				$content_file,
				strlen( untrailingslashit( wp_normalize_path( WP_CONTENT_DIR ) ) )
			),
			'/'
		);
		$content_url = content_url( $content_relative );

		$private_base = wp_normalize_path( dirname( dirname( untrailingslashit( ABSPATH ) ) ) . '/' . basename( sys_get_temp_dir() ) );
		$private_relative = '../../' . basename( sys_get_temp_dir() ) . '/sf-route-private-' . $this->scope;
		$private_root     = $this->create_owned_directory( $private_base . '/sf-route-private-' . $this->scope );
		$private_directory = $this->create_owned_directory( $private_root . '/' . $this->legacy_prefix );
		$private_file      = $private_directory . '/private-attachment.pdf';
		$private_bytes     = "%PDF-1.4\nprivate attachment\n%%EOF";
		$this->create_owned_file( $private_file, $private_bytes );
		$private_attachment = '/' . $private_relative . '/' . $this->legacy_prefix . '/private-attachment.pdf';

		$escape_root  = $this->create_owned_directory(
			wp_normalize_path( dirname( untrailingslashit( ABSPATH ) ) . '/sf-route-email-escape-' . $this->scope )
		);
		$escape_file  = $escape_root . '/outside.pdf';
		$escape_bytes = "%PDF-1.4\noutside attachment\n%%EOF";
		$this->create_owned_file( $escape_file, $escape_bytes );
		$escape_route = '/' . $private_relative . '/../' . basename( $escape_root ) . '/outside.pdf';

		$captured = array();
		$legacy_capture = static function( $args ) use ( &$captured ) {
			$captured[] = $args;
			$args['to'] = array();
			return $args;
		};
		$short_circuit_capture = static function( $short_circuit, $args ) use ( &$captured ) {
			$captured[] = $args;
			return true;
		};
		$forms = SUPER_Forms();
		$had_global_settings = isset( $forms->global_settings );
		$original_global_settings = $had_global_settings ? $forms->global_settings : null;
		$forms->global_settings = array( 'smtp_enabled' => 'disabled' );
		$use_pre_wp_mail = version_compare( $wp_version, '5.7', '>=' );
		if ( $use_pre_wp_mail ) {
			add_filter( 'pre_wp_mail', $short_circuit_capture, 10, 2 );
		} else {
			add_filter( 'wp_mail', $legacy_capture );
		}
		try {
			$result = SUPER_Common::email(
				'recipient@example.test',
				'sender@example.test',
				'Sender',
				false,
				'',
				'',
				'',
				'',
				'Attachment containment',
				'Body',
				array( 'file_upload_dir' => $private_relative ),
				array(
					'content' => $content_url,
					'private' => $private_attachment,
					'escape'  => $escape_route,
				)
			);
		} finally {
			if ( $use_pre_wp_mail ) {
				remove_filter( 'pre_wp_mail', $short_circuit_capture, 10 );
			} else {
				remove_filter( 'wp_mail', $legacy_capture );
			}
			if( $had_global_settings ) {
				$forms->global_settings = $original_global_settings;
			}else{
				unset( $forms->global_settings );
			}
		}

		$this->assertIsArray( $result );
		$this->assertCount( 1, $captured );
		$this->assertSame(
			array(
				wp_normalize_path( realpath( $content_file ) ),
				wp_normalize_path( realpath( $private_file ) ),
			),
			$captured[0]['attachments']
		);
		$this->assertSame( $content_bytes, file_get_contents( $content_file ) );
		$this->assertSame( $private_bytes, file_get_contents( $private_file ) );
		$this->assertSame( $escape_bytes, file_get_contents( $escape_file ) );
	}

	private function strict_security_pcntl_required() {
		$flag = getenv( 'SUPER_FORMS_STRICT_SECURITY_TESTS' );
		return is_string($flag) && $flag!=='' && $flag!=='0' && strtolower($flag)!=='false';
	}

	private function require_process_forking() {
		if ( function_exists( 'pcntl_fork' ) && function_exists( 'pcntl_waitpid' ) && function_exists( 'pcntl_exec' ) ) {
			return;
		}
		if ( $this->strict_security_pcntl_required() ) {
			$this->fail( 'The dispatcher regression requires pcntl fork, wait, and exec support.' );
		}
		$this->markTestSkipped( 'The dispatcher regression requires pcntl fork, wait, and exec support.' );
	}

	private function run_dispatcher_in_child( $query_vars ) {
		$this->require_process_forking();
		$output_file = tempnam( sys_get_temp_dir(), 'sf-route-output-' );
		$this->assertNotFalse( $output_file );
		$this->create_owned_file( $output_file, '' );

		$pid = pcntl_fork();
		if ( -1 === $pid ) {
			$this->fail( 'Could not fork the dispatcher test process.' );
		}
		if ( 0 === $pid ) {
			$returned = false;
			ob_start(
				static function ( $buffer ) use ( $output_file ) {
					file_put_contents( $output_file, $buffer, FILE_APPEND | LOCK_EX );
					return '';
				}
			);
			// Preserve the parent's transactional mysqli connection across this fork.
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
			$previous_error_handler = null;
			$previous_error_handler = set_error_handler(
				static function ( $severity, $message, $file, $line ) use ( &$previous_error_handler ) {
					if ( E_WARNING === $severity
						&& PHP_SAPI === 'cli'
						&& strpos( $message, 'Cannot modify header information - headers already sent by' ) === 0 ) {
						return true;
					}
					if ( is_callable( $previous_error_handler ) ) {
						call_user_func( $previous_error_handler, $severity, $message, $file, $line );
						return true;
					}
					return false;
				}
			);
			$_SERVER['SERVER_SOFTWARE'] = 'PHPUnit';
			unset( $_SERVER['HTTP_IF_MODIFIED_SINCE'], $_SERVER['HTTP_IF_NONE_MATCH'] );
			$_GET = $query_vars;
			$_REQUEST = $query_vars;
			$wp = new stdClass();
			$wp->query_vars = $query_vars;
			try {
				SUPER_Forms()->parse_request( $wp );
				$returned = true;
			} catch ( Throwable $e ) {
				echo get_class( $e ) . ': ' . $e->getMessage();
				$returned = true;
			}
			restore_error_handler();
			exit( 97 );
		}

		$status = null;
		$this->assertSame( $pid, pcntl_waitpid( $pid, $status ), 'Could not wait for dispatcher child.' );
		$this->assertTrue( pcntl_wifexited( $status ), 'Dispatcher child terminated by signal.' );
		$this->assertSame( 0, pcntl_wexitstatus( $status ), 'Dispatcher returned instead of taking its terminal response path.' );

		global $wpdb;
		if ( isset( $wpdb ) && method_exists( $wpdb, 'check_connection' ) ) {
			$this->assertTrue( $wpdb->check_connection( false ), 'WordPress database connection did not recover after dispatcher child.' );
		}

		return file_get_contents( $output_file );
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

	public static function real_dispatcher_denial_provider() {
		return array(
			'raw traversal'      => array( 'raw-parent' ),
			'encoded traversal'  => array( 'encoded-parent' ),
			'legacy traversal'   => array( 'legacy-parent' ),
			'absolute path'      => array( 'absolute-posix' ),
			'prefix collision'   => array( 'sibling-prefix' ),
			'symlink escape'     => array( 'symlink-escape' ),
			'case suffix'        => array( 'case-suffix' ),
			'compound suffix'    => array( 'compound-suffix' ),
		);
	}

	/**
	 * @dataProvider real_dispatcher_denial_provider
	 */
	public function test_real_public_dispatcher_denies_unsafe_route_without_output_or_mutation( $name ) {
		$case   = $this->build_denial_case( $name );
		$filter = $this->add_public_route_settings_filter( false );
		try {
			$output = $this->run_dispatcher_in_child(
				array( 'sfgtfi' => $case['route'] )
			);
		} finally {
			remove_filter( 'super_form_settings_filter', $filter );
		}

		$this->assert_fixture_integrity( $case['protected_files'], $case['absent_files'], $case['links'] );
		$this->assertSame( '', $output, 'Denied public route emitted file bytes.' );
	}

	public static function denied_actor_provider() {
		return array(
			'anonymous'  => array( 'anonymous' ),
			'subscriber' => array( 'subscriber' ),
		);
	}

	private function set_actor( $actor ) {
		if ( 'anonymous' === $actor ) {
			wp_set_current_user( 0 );
			return;
		}
		$user_id = self::factory()->user->create( array( 'role' => $actor ) );
		wp_set_current_user( $user_id );
	}

	/**
	 * @dataProvider denied_actor_provider
	 */
	public function test_public_dispatcher_preserves_login_and_role_denial_without_mutation( $actor ) {
		$nested = $this->create_default_nested_directory();
		$file   = $nested . '/role-protected.jpg';
		$bytes  = "\xFF\xD8\xFF\xD9role-protected";
		$this->create_owned_file( $file, $bytes );
		$this->set_actor( $actor );

		$filter = $this->add_public_route_settings_filter( true );
		try {
			$output = $this->run_dispatcher_in_child(
				array( 'sfgtfi' => $this->legacy_prefix . '/role-protected.jpg' )
			);
		} finally {
			remove_filter( 'super_form_settings_filter', $filter );
		}

		$this->assertSame( '', $output );
		$this->assertSame( $bytes, file_get_contents( $file ) );
		$this->assertSame( $this->unrelated_bytes, file_get_contents( $this->unrelated_file ) );
	}

	public function test_public_dispatcher_allows_configured_role_to_read_only_the_resolved_file() {
		$nested = $this->create_default_nested_directory();
		$file   = $nested . '/authorized.jpg';
		$bytes  = "\xFF\xD8\xFF\xD9authorized-public-download";
		$this->create_owned_file( $file, $bytes );
		$this->set_actor( 'administrator' );

		$filter = $this->add_public_route_settings_filter( true );
		try {
			$output = $this->run_dispatcher_in_child(
				array( 'sfgtfi' => $this->legacy_prefix . '/authorized.jpg' )
			);
		} finally {
			remove_filter( 'super_form_settings_filter', $filter );
		}

		$this->assertSame( $bytes, $output );
		$this->assertSame( $bytes, file_get_contents( $file ), 'Public upload download unexpectedly deleted its source.' );
		$this->assertSame( $this->unrelated_bytes, file_get_contents( $this->unrelated_file ) );
	}

	public function test_public_dispatcher_downloads_only_exact_encoded_route_for_external_custom_root() {
		$external_base = wp_normalize_path( dirname( dirname( untrailingslashit( ABSPATH ) ) ) . '/' . basename( sys_get_temp_dir() ) );
		$external_root = $this->create_owned_directory( $external_base . '/sf-route-external-' . $this->scope );
		$external_setting = '../../' . basename( sys_get_temp_dir() ) . '/sf-route-external-' . $this->scope;
		$bytes = "%PDF-1.4\nexternal custom root\n%%EOF";
		$record = $this->materialize_external_pdf( $external_setting, $external_root, $bytes );
		$file = wp_normalize_path( $record['path'] );
		$route_path = (string) wp_parse_url( $record['url'], PHP_URL_PATH );
		$route_marker = '/sfgtfi/';
		$route_offset = strpos( $route_path, $route_marker );
		$this->assertNotFalse( $route_offset );
		$emitted_route = rawurldecode( substr( $route_path, $route_offset + strlen( $route_marker ) ) );
		$relative_file = ltrim( substr( $file, strlen( trailingslashit( $external_root ) ) ), '/' );
		$exact_route = ltrim( str_replace( '../', '__/', trailingslashit( $external_setting ) ) . $relative_file, '/' );
		$settings = array( 'file_upload_dir' => $external_setting );
		$tampered_route = str_replace(
			'/' . basename( $external_root ) . '/',
			'/' . basename( $external_root ) . '/../' . basename( $external_root ) . '/',
			$exact_route
		);

		$this->assertSame( $exact_route, $emitted_route );
		$this->assertFalse( $this->invoke_resolver( $external_setting . '/' . $relative_file, $settings ) );
		$this->assertFalse( $this->invoke_resolver( $relative_file, $settings ) );
		$this->assertFalse( $this->invoke_resolver( $tampered_route, $settings ) );
		$expected_resolution = array(
			'file' => wp_normalize_path( realpath( $file ) ),
			'root' => wp_normalize_path( realpath( $external_root ) ),
		);
		$this->assertSame( $expected_resolution, $this->invoke_resolver( $emitted_route, $settings ) );
		$this->assertSame(
			array_merge( $expected_resolution, array( 'ext' => 'pdf', 'mime' => 'application/pdf' ) ),
			SUPER_Forms::resolve_owned_upload_file( $record['url'], $settings )
		);
		$replacement_root = $this->create_owned_directory(
			wp_normalize_path( dirname( untrailingslashit( ABSPATH ) ) . '/sf-route-external-replacement-' . $this->scope )
		);
		$replacement_settings = array( 'file_upload_dir' => '../' . basename( $replacement_root ) );
		$this->assertSame(
			array_merge( $expected_resolution, array( 'ext' => 'pdf', 'mime' => 'application/pdf' ) ),
			SUPER_Forms::resolve_owned_upload_file( $record['url'], $replacement_settings )
		);


		$this->set_actor( 'administrator' );
		$filter = $this->add_public_route_settings_filter( true, $external_setting );
		try {
			$output = $this->run_dispatcher_in_child(
				array( 'sfgtfi' => $emitted_route )
			);
		} finally {
			remove_filter( 'super_form_settings_filter', $filter );
		}

		$this->assertSame( $bytes, $output );
		$this->assertSame( $bytes, file_get_contents( $file ) );

		$protected_directory = $this->create_owned_directory(
			untrailingslashit( wp_normalize_path( WP_CONTENT_DIR ) ) . '/' . $this->legacy_prefix
		);
		$protected_file = $protected_directory . '/protected-root.jpg';
		$protected_bytes = "\xFF\xD8\xFF\xD9protected-root";
		$this->create_owned_file( $protected_file, $protected_bytes );
		$protected_setting = $this->relative_to_abspath( WP_CONTENT_DIR );
		$protected_filter = $this->add_public_route_settings_filter( true, $protected_setting );
		try {
			$protected_output = $this->run_dispatcher_in_child(
				array( 'sfgtfi' => $protected_setting . '/' . $this->legacy_prefix . '/protected-root.jpg' )
			);
		} finally {
			remove_filter( 'super_form_settings_filter', $protected_filter );
		}

		$this->assertSame( '', $protected_output );
		$this->assertSame( $protected_bytes, file_get_contents( $protected_file ) );
		$this->assertSame( $this->unrelated_bytes, file_get_contents( $this->unrelated_file ) );
	}
	public function test_public_owned_upload_url_signs_inline_custom_root_routes_and_dispatcher_resolves_only_the_signed_url() {
		$external_base = wp_normalize_path( dirname( dirname( untrailingslashit( ABSPATH ) ) ) . '/' . basename( sys_get_temp_dir() ) );
		$external_root = $this->create_owned_directory( $external_base . '/sf-route-inline-' . $this->scope );
		$external_setting = '../../' . basename( sys_get_temp_dir() ) . '/sf-route-inline-' . $this->scope;
		$bytes = "%PDF-1.4\nexternal inline custom root\n%%EOF";
		$record = $this->materialize_external_pdf( $external_setting, $external_root, $bytes );
		$raw_url = $record['url'];
		$signed_url = SUPER_Forms::public_owned_upload_url(
			$record,
			array( 'file_upload_dir' => $external_setting ),
			'inline'
		);
		$this->assertNotSame( $raw_url, $signed_url );
		$query = array();
		parse_str( (string) wp_parse_url( $signed_url, PHP_URL_QUERY ), $query );
		$this->assertArrayHasKey( 'sfgtfi_disp', $query );
		$this->assertArrayHasKey( 'sfgtfi_sig', $query );
		$this->assertSame( 'inline', $query['sfgtfi_disp'] );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $query['sfgtfi_sig'] );
		$route_path = (string) wp_parse_url( $signed_url, PHP_URL_PATH );
		$route_marker = '/sfgtfi/';
		$route_offset = strpos( $route_path, $route_marker );
		$this->assertNotFalse( $route_offset );
		$route = rawurldecode( substr( $route_path, $route_offset + strlen( $route_marker ) ) );
		$this->set_actor( 'administrator' );
		$filter = $this->add_public_route_settings_filter( true );
		try {
			$this->assertSame( '', $this->run_dispatcher_in_child( array( 'sfgtfi' => $route ) ) );
			$output = $this->run_dispatcher_in_child(
				array(
					'sfgtfi' => $route,
					'sfgtfi_disp' => $query['sfgtfi_disp'],
					'sfgtfi_sig' => $query['sfgtfi_sig'],
				)
			);
		} finally {
			remove_filter( 'super_form_settings_filter', $filter );
		}
		$this->assertSame( $bytes, $output );
	}

	private function create_owned_attachment( $file ) {
		$attachment_id = wp_insert_attachment(
			array(
				'post_author'    => get_current_user_id(),
				'post_mime_type' => 'text/plain',
				'post_status'    => 'inherit',
				'post_title'     => 'Owned route export ' . $this->scope,
			),
			$file
		);
		$this->assertNotWPError( $attachment_id );
		$this->assertSame( wp_normalize_path( $file ), wp_normalize_path( get_attached_file( $attachment_id ) ) );
		$this->attachment_ids[] = $attachment_id;
		return $attachment_id;
	}

	private function export_cleanup_schedule_succeeded( $schedule_result, $attachment_id ) {
		$method = new ReflectionMethod( 'SUPER_Forms', 'export_cleanup_schedule_succeeded' );
		$method->setAccessible( true );
		return $method->invoke( null, $schedule_result, $attachment_id );
	}

	private function create_export_grant( $attachment_id ) {
		$url = SUPER_Forms::create_export_download_url( $attachment_id );
		$this->assertIsString( $url );
		$this->assertNotSame( '', $url );
		$query = array();
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
		$this->assertSame( $attachment_id, absint( $query['sfdlfi'] ) );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $query['sfdlfi_token'] );
		$this->assertSame( '1', (string) get_post_meta( $attachment_id, '_super_forms_export_file', true ) );
		$this->assertSame( (string) get_current_user_id(), (string) get_post_meta( $attachment_id, '_super_forms_export_owner', true ) );
		$this->assertSame(
			hash( 'sha256', $query['sfdlfi_token'] ),
			get_post_meta( $attachment_id, '_super_forms_export_token_hash', true )
		);
		$expires = (int) get_post_meta( $attachment_id, '_super_forms_export_expires', true );
		$this->assertGreaterThan( time(), $expires );
		$this->assertLessThanOrEqual( time() + 5 * MINUTE_IN_SECONDS, $expires );
		$this->assertNotFalse( wp_next_scheduled( 'super_cleanup_export_attachment', array( $attachment_id ) ) );
		return $query['sfdlfi_token'];
	}

	private function drain_export_download( $download ) {
		$this->assertIsArray( $download );
		$this->assertArrayHasKey( 'handle', $download );
		$this->assertTrue( is_resource( $download['handle'] ) );
		$content = stream_get_contents( $download['handle'] );
		fclose( $download['handle'] );
		$this->assertIsString( $content );
		return $content;
	}

	public function test_legacy_void_export_schedule_keeps_token_metadata_when_the_event_exists() {
		$this->set_actor( 'administrator' );
		$file = $this->default_fixture . '/legacy-void-export.txt';
		$this->create_owned_file( $file, 'legacy-void-export-' . $this->scope );
		$attachment_id = $this->create_owned_attachment( $file );
		$token = $this->create_export_grant( $attachment_id );

		$this->assertTrue(
			$this->export_cleanup_schedule_succeeded( null, $attachment_id ),
			'WordPress 4.9 returns void after scheduling this event.'
		);
		$this->assertSame(
			hash( 'sha256', $token ),
			get_post_meta( $attachment_id, '_super_forms_export_token_hash', true )
		);
	}

	public function test_export_schedule_failure_removes_token_metadata() {
		global $wp_version;
		if ( version_compare( $wp_version, '5.1', '<' ) ) {
			$this->markTestSkipped( 'pre_schedule_event is unavailable before WordPress 5.1.' );
		}
		$this->set_actor( 'administrator' );
		$file = $this->default_fixture . '/failed-export-schedule.txt';
		$this->create_owned_file( $file, 'failed-export-schedule-' . $this->scope );
		$attachment_id = $this->create_owned_attachment( $file );
		$block_schedule = static function( $pre, $event ) {
			if ( 'super_cleanup_export_attachment' === $event->hook ) {
				return false;
			}
			return $pre;
		};
		add_filter( 'pre_schedule_event', $block_schedule, 10, 2 );
		try {
			$this->assertFalse( SUPER_Forms::create_export_download_url( $attachment_id ) );
		} finally {
			remove_filter( 'pre_schedule_event', $block_schedule, 10 );
		}

		$this->assertSame( '', get_post_meta( $attachment_id, '_super_forms_export_token_hash', true ) );
		$this->assertSame( '', get_post_meta( $attachment_id, '_super_forms_export_owner', true ) );
		$this->assertSame( '', get_post_meta( $attachment_id, '_super_forms_export_expires', true ) );
		$this->assertSame( '', get_post_meta( $attachment_id, '_super_forms_export_file', true ) );
		$this->assertFalse( wp_next_scheduled( 'super_cleanup_export_attachment', array( $attachment_id ) ) );
	}

	/**
	 * @dataProvider denied_actor_provider
	 */
	public function test_export_grant_is_bound_to_its_authorized_owner( $actor ) {
		$this->set_actor( 'administrator' );
		$file  = $this->default_fixture . '/denied-export-' . $actor . '.txt';
		$bytes = 'denied-export-' . $actor;
		$this->create_owned_file( $file, $bytes );
		$attachment_id = $this->create_owned_attachment( $file );
		$token         = $this->create_export_grant( $attachment_id );

		$this->set_actor( $actor );
		$result = SUPER_Forms::consume_export_download( $attachment_id, $token );

		$this->assertWPError( $result );
		$this->assertSame( 'export_download_forbidden', $result->get_error_code() );
		$this->assertSame( $bytes, file_get_contents( $file ) );
		$this->assertSame( hash( 'sha256', $token ), get_post_meta( $attachment_id, '_super_forms_export_token_hash', true ) );
		$this->assertSame( $this->unrelated_bytes, file_get_contents( $this->unrelated_file ) );
		clean_post_cache( $attachment_id );
		$this->assertNotNull( get_post( $attachment_id ) );
	}

	public function test_export_grant_uses_the_manage_options_boundary_without_requiring_delete_post_capability() {
		$role = 'sf_exporter_' . substr( md5( $this->scope ), 0, 12 );
		remove_role( $role );
		$this->assertInstanceOf(
			WP_Role::class,
			add_role(
				$role,
				$role,
				array(
					'read' => true,
					'export' => true,
					'manage_options' => true,
				)
			)
		);
		try {
			$user_id = self::factory()->user->create( array( 'role' => $role ) );
			wp_set_current_user( $user_id );
			$file  = $this->default_fixture . '/custom-exporter.txt';
			$bytes = 'custom-exporter-' . $this->scope;
			$this->create_owned_file( $file, $bytes );
			$attachment_id = $this->create_owned_attachment( $file );
			$token = $this->create_export_grant( $attachment_id );
		$download = SUPER_Forms::consume_export_download( $attachment_id, $token );
		$this->assertSame( $bytes, $this->drain_export_download( $download ) );
		$this->assertSame( strlen( $bytes ), $download['size'] );
		} finally {
			remove_role( $role );
		}
	}

	public function test_export_grant_returns_exact_bytes_once_and_claims_its_token_for_the_streaming_caller() {
		$this->set_actor( 'administrator' );
		$file  = $this->default_fixture . '/authorized-export.txt';
		$bytes = 'authorized-export-' . $this->scope;
		$this->create_owned_file( $file, $bytes );
		$attachment_id = $this->create_owned_attachment( $file );
		$token         = $this->create_export_grant( $attachment_id );

		$download = SUPER_Forms::consume_export_download( $attachment_id, $token );
		$this->assertIsArray( $download );
		$this->assertSame( basename( $file ), $download['filename'] );
		$this->assertSame( 'text/plain', $download['mime'] );
		$this->assertSame( strlen( $bytes ), $download['size'] );
		$this->assertSame( $bytes, $this->drain_export_download( $download ) );

		// The single-use token is claimed atomically at consume time, so a replay
		// fails closed. End-to-end attachment deletion is the streaming caller's
		// (parse_request) guaranteed cleanup responsibility, proven in row 10.
		$this->assertSame( '', get_post_meta( $attachment_id, '_super_forms_export_token_hash', true ) );
		$this->assertFalse( wp_next_scheduled( 'super_cleanup_export_attachment', array( $attachment_id ) ) );
		$this->assertSame( $this->unrelated_bytes, file_get_contents( $this->unrelated_file ) );

		$replay = SUPER_Forms::consume_export_download( $attachment_id, $token );
		$this->assertWPError( $replay );
	}

	public function test_export_grant_accepts_a_symlinked_upload_alias_under_the_canonical_upload_root() {
		if ( ! function_exists( 'symlink' ) ) {
			$this->markTestSkipped( 'The symlink extension is unavailable.' );
		}
		$this->set_actor( 'administrator' );
		$real_directory = $this->create_owned_directory( $this->default_fixture . '/symlinked-real' );
		$alias_directory = $this->default_fixture . '/symlinked-alias';
		if ( ! @symlink( $real_directory, $alias_directory ) ) {
			$this->markTestSkipped( 'The test filesystem does not permit symlinks.' );
		}
		$this->owned_files[] = $alias_directory;
		$file = $this->create_owned_file( $real_directory . '/symlinked-export.txt', 'symlinked-export-' . $this->scope );
		$attachment_id = $this->create_owned_attachment( $alias_directory . '/symlinked-export.txt' );
		$token = $this->create_export_grant( $attachment_id );

		$download = SUPER_Forms::consume_export_download( $attachment_id, $token );
		$this->assertSame( 'symlinked-export-' . $this->scope, $this->drain_export_download( $download ) );
		$this->assertSame( basename( $file ), $download['filename'] );
		$this->assertSame( '', get_post_meta( $attachment_id, '_super_forms_export_token_hash', true ) );
	}

	public function test_export_cleanup_deletes_only_an_expired_unclaimed_authoritative_attachment() {
		$this->set_actor( 'administrator' );
		$cases = array();
		foreach ( array( 'expired', 'future', 'claimed', 'wrong-owner' ) as $name ) {
			$file = $this->default_fixture . '/cleanup-' . $name . '.txt';
			$this->create_owned_file( $file, 'cleanup-' . $name );
			$attachment_id = $this->create_owned_attachment( $file );
			$token         = $this->create_export_grant( $attachment_id );
			$cases[ $name ] = compact( 'file', 'attachment_id', 'token' );
		}
		update_post_meta( $cases['expired']['attachment_id'], '_super_forms_export_expires', time() - 1 );
		update_post_meta( $cases['claimed']['attachment_id'], '_super_forms_export_expires', time() - 1 );
		delete_post_meta(
			$cases['claimed']['attachment_id'],
			'_super_forms_export_token_hash',
			hash( 'sha256', $cases['claimed']['token'] )
		);
		update_post_meta( $cases['wrong-owner']['attachment_id'], '_super_forms_export_expires', time() - 1 );
		update_post_meta( $cases['wrong-owner']['attachment_id'], '_super_forms_export_owner', get_current_user_id() + 1 );

		foreach ( $cases as $case ) {
			SUPER_Forms::cleanup_export_attachment( $case['attachment_id'] );
			clean_post_cache( $case['attachment_id'] );
		}

		$this->assertNull( get_post( $cases['expired']['attachment_id'] ) );
		$this->assertFileDoesNotExist( $cases['expired']['file'] );
		foreach ( array( 'future', 'claimed', 'wrong-owner' ) as $name ) {
			$this->assertNotNull( get_post( $cases[ $name ]['attachment_id'] ), $name . ' export attachment was deleted.' );
			$this->assertFileExists( $cases[ $name ]['file'], $name . ' export file was deleted.' );
		}
		$this->assertSame( $this->unrelated_bytes, file_get_contents( $this->unrelated_file ) );
	}

	private function create_entry_cleanup_attachment( $entry_id, $marked, $stored_form_id = false, $file = false ) {
		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'text/plain',
				'post_parent'    => $entry_id,
				'post_status'    => 'inherit',
				'post_title'     => 'Entry cleanup ' . $this->scope,
			),
			$file,
			$entry_id,
			true
		);
		$this->assertNotWPError( $attachment_id );
		$attachment_id = absint( $attachment_id );
		$this->assertGreaterThan( 0, $attachment_id );
		if ( is_string( $file ) && '' !== $file ) {
			$this->assertSame( wp_normalize_path( $file ), wp_normalize_path( get_attached_file( $attachment_id ) ) );
		}
		if ( $marked ) {
			$this->assertNotFalse( add_post_meta( $attachment_id, 'super-forms-form-upload-file', true ) );
		}
		if ( false !== $stored_form_id ) {
			$this->assertNotFalse( add_post_meta( $attachment_id, '_super_forms_upload_form_id', $stored_form_id ) );
		}
		return $attachment_id;
	}

	public function test_entry_cleanup_requires_authoritative_attachment_identity_and_preserves_custom_files() {
		$form_id  = self::factory()->post->create(
			array(
				'post_type'   => 'super_form',
				'post_status' => 'publish',
			)
		);
		$entry_id = self::factory()->post->create(
			array(
				'post_parent' => $form_id,
				'post_type'   => 'super_contact_entry',
				'post_status' => 'publish',
			)
		);
		$this->assertNotEmpty( $form_id );
		$this->assertNotEmpty( $entry_id );

		$exact_marked = $this->create_entry_cleanup_attachment( $entry_id, true, $form_id );
		$legacy_marked = $this->create_entry_cleanup_attachment( $entry_id, true );
		$legacy_child_file = $this->create_owned_file( $this->default_fixture . '/legacy-child.txt', 'legacy child' );
		$legacy_child = $this->create_entry_cleanup_attachment( $entry_id, false, false, $legacy_child_file );
		$unmarked = $this->create_entry_cleanup_attachment( $entry_id, false, $form_id );
		$mismatched = $this->create_entry_cleanup_attachment( $entry_id, true, $form_id + 1 );
		$attachment_ids = array( $exact_marked, $legacy_marked, $legacy_child, $unmarked, $mismatched );

		$custom_file = $this->create_owned_file( $this->default_fixture . '/forged-custom-delete.txt', 'preserve custom' );
		update_post_meta(
			$entry_id,
			'_super_contact_entry_data',
			array(
				'attachments' => array(
					'type'  => 'files',
					'files' => array(
						array( 'attachment' => $exact_marked ),
						array( 'attachment' => $legacy_marked ),
						array( 'attachment' => $legacy_child ),
						array( 'attachment' => $unmarked ),
						array( 'attachment' => $mismatched ),
					),
				),
				'upload' => array(
					'type'  => 'files',
					'files' => array(
						array(
							'_super_file_authority' => 'owned',
							'path'                  => $custom_file,
						),
					),
				),
			)
		);
		$stored_entry_data = SUPER_Data_Access::get_entry_data( $entry_id );
		$this->assertSame( $exact_marked, $stored_entry_data['attachments']['files'][0]['attachment'] );
		$this->assertSame( 'owned', $stored_entry_data['upload']['files'][0]['_super_file_authority'] );
		$this->assertSame( $custom_file, $stored_entry_data['upload']['files'][0]['path'] );

		$forms               = SUPER_Forms();
		$had_global_settings = isset( $forms->global_settings );
		$original_settings   = $had_global_settings ? $forms->global_settings : null;
		$forms->global_settings = is_array( $original_settings ) ? $original_settings : array();
		$forms->global_settings['file_upload_entry_delete'] = 'true';
		try {
			SUPER_Forms::delete_entry_attachments( $entry_id );
			foreach ( $attachment_ids as $attachment_id ) {
				clean_post_cache( $attachment_id );
			}
			$this->assertNull( get_post( $exact_marked ), 'Exact marked attachment was not deleted.' );
			$this->assertNull( get_post( $legacy_marked ), 'Legacy marked attachment with an exact parent was not deleted.' );
			$this->assertNull( get_post( $legacy_child ), 'Exact legacy child attachment inside uploads root was not deleted.' );
			$this->assertNotNull( get_post( $unmarked ), 'Proofless attachment without an authoritative file was deleted.' );
			$this->assertNotNull( get_post( $mismatched ), 'Attachment with mismatched form authority was deleted.' );
			$this->assertFileDoesNotExist( $legacy_child_file );
			$this->assertFileExists( $custom_file, 'A forged custom file record was deleted.' );
		} finally {
			if ( $had_global_settings ) {
				$forms->global_settings = $original_settings;
			} else {
				unset( $forms->global_settings );
			}
			foreach ( $attachment_ids as $attachment_id ) {
				if ( get_post( $attachment_id ) ) {
					wp_delete_attachment( $attachment_id, true );
				}
			}
			wp_delete_post( $entry_id, true );
			wp_delete_post( $form_id, true );
		}
	}

	public function test_export_attachments_are_unconditionally_hidden_from_media_picker() {
		set_current_screen( 'upload' );
		try {
			$args = SUPER_Forms()->hide_uploads_from_media_grid_and_overlay_view( array() );
		} finally {
			set_current_screen( 'front' );
		}
		$this->assertIsArray( $args );
		$found = false;
		foreach ( $args['meta_query'] as $condition ) {
			if ( isset( $condition['key'], $condition['compare'] )
				&& '_super_forms_export_file' === $condition['key']
				&& 'NOT EXISTS' === $condition['compare'] ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Generated exports were offered through the media picker.' );
	}

	private function get_download_cache_headers( $protected ) {
		$method = new ReflectionMethod( 'SUPER_Forms', 'download_cache_headers' );
		$method->setAccessible( true );
		return $method->invoke( null, $protected );
	}

	public function test_protected_download_cache_policy_is_private_while_public_policy_remains_long_lived() {
		$protected = $this->get_download_cache_headers( true );
		$this->assertNotFalse( strpos( $protected['Cache-Control'], 'private' ) );
		$this->assertNotFalse( strpos( $protected['Cache-Control'], 'no-store' ) );
		$this->assertLessThanOrEqual( time(), strtotime( $protected['Expires'] ) );
		$this->assertFalse( $protected['Last-Modified'] );
		$this->assertFalse( $protected['ETag'] );
		$this->assertSame( 'Cookie', $protected['Vary'] );

		$public = $this->get_download_cache_headers( false );
		$this->assertSame( 'public', $public['Cache-Control'] );
		$this->assertGreaterThan( time() + 29 * DAY_IN_SECONDS, strtotime( $public['Expires'] ) );
		$this->assertArrayNotHasKey( 'Last-Modified', $public );
		$this->assertArrayNotHasKey( 'ETag', $public );
		$this->assertArrayNotHasKey( 'Vary', $public );
	}
}
