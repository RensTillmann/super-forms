<?php
/**
 * Security regressions for the plugin updater transport: metadata must be fetched
 * over https and update packages must only ever be downloaded over https from our
 * own hosts (super-forms.php update_plugin(), is_trusted_update_package_url() and
 * filter_update_info()).
 *
 * @package Super_Forms\Tests
 */

class Test_Security_Updater_Transport extends WP_UnitTestCase {

	const RESULT_FILTER = 'puc_request_info_result-super-forms';

	private $error_log_file;
	private $original_error_log;

	/**
	 * update_plugin() is hooked to init only for admin requests (super-forms.php:333,347),
	 * so the test process has to call it itself. Each call builds a fresh PUC checker;
	 * WP_UnitTestCase restores $wp_filter in tear_down(), which drops the checker's
	 * hooks (and PUC's 'puc_is_slug_in_use-super-forms' guard) again after every test.
	 *
	 * The PUC 4.6 loader normally runs inside update_plugin(); the Puc_v4p6_Plugin_Info
	 * fixtures below need it before that, so load it here (every PUC file is
	 * class_exists()-guarded, and update_plugin()'s require_once resolves to the same path).
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		if ( ! class_exists( 'Puc_v4p6_Plugin_UpdateChecker' ) ) {
			require_once dirname( __DIR__ ) . '/src/includes/admin/plugin-update-checker/plugin-update-checker.php';
		}
	}

	public function set_up() {
		parent::set_up();
		$this->original_error_log = ini_get( 'error_log' );
		$this->error_log_file     = tempnam( sys_get_temp_dir(), 'sf-updater-log-' );
		ini_set( 'error_log', $this->error_log_file );
	}

	public function tear_down() {
		ini_set( 'error_log', $this->original_error_log );
		if ( $this->error_log_file && file_exists( $this->error_log_file ) ) {
			unlink( $this->error_log_file );
		}
		parent::tear_down();
	}

	private function logged() {
		return file_exists( $this->error_log_file ) ? (string) file_get_contents( $this->error_log_file ) : '';
	}

	private function plugin_info( $download_url, $translations = array() ) {
		$info               = new Puc_v4p6_Plugin_Info();
		$info->name         = 'Super Forms';
		$info->slug         = 'super-forms';
		$info->version      = '99.0.0';
		$info->download_url = $download_url;
		$info->translations = $translations;
		return $info;
	}

	private function translation( $package ) {
		return (object) array(
			'language' => 'nl_NL',
			'version'  => '99.0.0',
			'updated'  => '2026-09-30 00:00:00',
			'package'  => $package,
		);
	}

	/**
	 * Run $checker->requestUpdate() against a mocked metadata response so the real
	 * PUC 4.6 request path (wp_remote_get -> Puc_v4p6_Plugin_Info::fromJson -> the
	 * result filter -> filterUpdateResult) is exercised. $metadata is encoded with
	 * wp_json_encode(), so an associative array becomes a JSON object like the
	 * server would send it.
	 */
	private function request_update_with_metadata( $checker, array $metadata ) {
		$body = wp_json_encode( $metadata );
		$mock = static function( $pre, $args, $url ) use ( $body ) {
			if ( 0 !== strpos( $url, 'https://f4d.nl/@super-forms-updates/' ) ) {
				return $pre;
			}
			return array(
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => $body,
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'cookies'  => array(),
				'filename' => null,
			);
		};
		add_filter( 'pre_http_request', $mock, 10, 3 );
		try {
			return $checker->requestUpdate();
		} finally {
			remove_filter( 'pre_http_request', $mock, 10 );
		}
	}

	/* -------------------------------------------------------------------
	 * (i) metadata URL
	 * -------------------------------------------------------------------
	 */

	public function test_update_checker_fetches_metadata_over_https_only() {
		$checker = SUPER_Forms()->update_plugin();

		$this->assertInstanceOf( 'Puc_v4p6_Plugin_UpdateChecker', $checker );
		$this->assertSame( 'super-forms', $checker->slug );
		$this->assertStringStartsWith( 'https://f4d.nl/@super-forms-updates/', $checker->metadataUrl );
		$this->assertStringContainsString( 'action=get_metadata&slug=super-forms', $checker->metadataUrl );
		$this->assertSame( 'https', wp_parse_url( $checker->metadataUrl, PHP_URL_SCHEME ) );
		$this->assertSame( 'f4d.nl', wp_parse_url( $checker->metadataUrl, PHP_URL_HOST ) );
	}

	public function test_update_plugin_registers_the_result_filter() {
		$this->assertFalse( has_filter( self::RESULT_FILTER, array( 'SUPER_Forms', 'filter_update_info' ) ) );
		SUPER_Forms()->update_plugin();
		$this->assertSame( 10, has_filter( self::RESULT_FILTER, array( 'SUPER_Forms', 'filter_update_info' ) ) );
	}

	/* -------------------------------------------------------------------
	 * (ii) package URL validation with plain values
	 * -------------------------------------------------------------------
	 */

	public function accepted_package_urls() {
		return array(
			'f4d.nl download endpoint' => array( 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms' ),
			'f4d.nl root'              => array( 'https://f4d.nl/' ),
			'f4d.nl upper case host'   => array( 'HTTPS://F4D.NL/@super-forms-updates/?action=download&slug=super-forms' ),
			'api.super-forms.com'      => array( 'https://api.super-forms.com/v1/plugin/download/super-forms.zip' ),
			'super-forms.com apex'     => array( 'https://super-forms.com/downloads/super-forms.zip' ),
			'nested subdomain'         => array( 'https://cdn.eu.super-forms.com/super-forms.zip' ),
			'GitHub Pages origin'      => array( 'https://renstillmann.github.io/super-forms/super-forms.zip' ),
		);
	}

	public function test_trusted_hosts_filter_can_add_and_remove_hosts() {
		$add = static function( $hosts ) {
			$hosts[] = 'updates.example.org';
			return $hosts;
		};
		add_filter( 'super_forms_trusted_update_hosts', $add );
		$this->assertTrue( SUPER_Forms::is_trusted_update_package_url( 'https://updates.example.org/super-forms.zip' ) );
		$this->assertFalse( SUPER_Forms::is_trusted_update_package_url( 'http://updates.example.org/super-forms.zip' ), 'https stays mandatory for added hosts' );
		remove_filter( 'super_forms_trusted_update_hosts', $add );

		$only_f4d = static function() {
			return array( 'f4d.nl' );
		};
		add_filter( 'super_forms_trusted_update_hosts', $only_f4d );
		$this->assertFalse( SUPER_Forms::is_trusted_update_package_url( 'https://api.super-forms.com/super-forms.zip' ) );
		$this->assertTrue( SUPER_Forms::is_trusted_update_package_url( 'https://f4d.nl/super-forms.zip' ) );
		remove_filter( 'super_forms_trusted_update_hosts', $only_f4d );

		$broken = static function() {
			return 'f4d.nl';
		};
		add_filter( 'super_forms_trusted_update_hosts', $broken );
		$this->assertFalse( SUPER_Forms::is_trusted_update_package_url( 'https://f4d.nl/super-forms.zip' ), 'a non-array filter result trusts nothing' );
		remove_filter( 'super_forms_trusted_update_hosts', $broken );
	}

	/**
	 * @dataProvider accepted_package_urls
	 */
	public function test_validation_accepts_https_urls_on_our_hosts( $url ) {
		$this->assertTrue( SUPER_Forms::is_trusted_update_package_url( $url ) );
	}

	public function rejected_package_urls() {
		return array(
			'plain http f4d.nl'                  => array( 'http://f4d.nl/@super-forms-updates/?action=download&slug=super-forms' ),
			'plain http super-forms.com'         => array( 'http://api.super-forms.com/super-forms.zip' ),
			'foreign host'                       => array( 'https://evil.example/super-forms.zip' ),
			'userinfo trick'                     => array( 'https://f4d.nl@evil.example/' ),
			'userinfo with password'             => array( 'https://f4d.nl:x@evil.example/super-forms.zip' ),
			'userinfo on trusted host'           => array( 'https://user:pass@f4d.nl/super-forms.zip' ),
			'look-alike suffix'                  => array( 'https://f4d.nl.evil.example/super-forms.zip' ),
			'look-alike prefix'                  => array( 'https://evilsuper-forms.com/super-forms.zip' ),
			'trusted host in path only'          => array( 'https://evil.example/f4d.nl/super-forms.zip' ),
			'trusted host in query only'         => array( 'https://evil.example/?u=https://f4d.nl/' ),
			'ftp scheme'                         => array( 'ftp://f4d.nl/super-forms.zip' ),
			'scheme-relative'                    => array( '//f4d.nl/super-forms.zip' ),
			'relative path'                      => array( '/@super-forms-updates/?action=download' ),
			'empty string'                       => array( '' ),
			'null'                               => array( null ),
			'array'                              => array( array( 'https://f4d.nl/' ) ),
			'javascript scheme'                  => array( 'javascript:alert(1)' ),
			'backslash before at'                => array( 'https://f4d.nl\\@evil.example/' ),
			'unicode host'                       => array( 'https://f4d.nl' . "\xe2\x80\x8b" . '/super-forms.zip' ),
		);
	}

	/**
	 * @dataProvider rejected_package_urls
	 */
	public function test_validation_rejects_http_foreign_and_userinfo_urls( $url ) {
		$this->assertFalse( SUPER_Forms::is_trusted_update_package_url( $url ) );
	}

	/* -------------------------------------------------------------------
	 * (iii) the filter callback
	 * -------------------------------------------------------------------
	 */

	public function test_filter_drops_update_for_foreign_host_and_logs_once() {
		$info = $this->plugin_info( 'https://evil.example/super-forms.zip' );

		$this->assertNull( SUPER_Forms::filter_update_info( $info, array( 'response' => array( 'code' => 200 ) ) ) );

		$log = $this->logged();
		$this->assertStringContainsString( 'Super Forms: update ignored', $log );
		$this->assertStringContainsString( 'evil.example', $log );
		$this->assertSame( 1, substr_count( $log, 'Super Forms: update ignored' ) );
	}

	public function test_filter_drops_update_for_plain_http_download_url() {
		$info = $this->plugin_info( 'http://f4d.nl/@super-forms-updates/?action=download&slug=super-forms' );
		$this->assertNull( SUPER_Forms::filter_update_info( $info ) );
		$this->assertStringContainsString( 'Super Forms: update ignored', $this->logged() );
	}

	public function test_filter_drops_update_when_download_url_is_missing() {
		$info = $this->plugin_info( null );
		$this->assertNull( SUPER_Forms::filter_update_info( $info ) );
	}

	public function test_filter_drops_update_when_download_url_is_not_a_string() {
		$this->assertNull( SUPER_Forms::filter_update_info( $this->plugin_info( array( 'https://f4d.nl/' ) ) ) );
		$this->assertNull( SUPER_Forms::filter_update_info( $this->plugin_info( (object) array( 'a' => 1 ) ) ) );
		$this->assertSame( 2, substr_count( $this->logged(), 'Super Forms: update ignored' ) );
		$this->assertStringNotContainsString( 'Array to string', $this->logged() );
	}

	public function test_filter_drops_update_for_foreign_translation_package() {
		$translation = $this->translation( 'https://evil.example/super-forms-nl_NL.zip' );
		$info = $this->plugin_info( 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms', array( $translation ) );
		$this->assertNull( SUPER_Forms::filter_update_info( $info ) );
		$this->assertStringContainsString( 'evil.example', $this->logged() );
	}

	/**
	 * A JSON *object* {"translations":{"x":{...}}} reaches the filter as stdClass, not as an
	 * array (Puc_v4p6_Metadata::createFromJson() copies json_decode()'s output as is), and
	 * PUC iterates a stdClass just as happily as an array. The package check must not be
	 * skipped for that encoding.
	 */
	public function test_filter_drops_update_for_foreign_translation_package_in_object_form() {
		$translations = (object) array( 'x' => $this->translation( 'https://evil.example/nl.zip' ) );
		$info = $this->plugin_info( 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms', $translations );
		$this->assertNull( SUPER_Forms::filter_update_info( $info ) );
		$this->assertStringContainsString( 'Super Forms: update ignored', $this->logged() );
		$this->assertStringContainsString( 'evil.example', $this->logged() );
	}

	public function test_filter_keeps_update_with_trusted_translation_package_in_object_form() {
		$translations = (object) array( 'nl_NL' => $this->translation( 'https://f4d.nl/@super-forms-updates/?action=download_translation&slug=super-forms&language=nl_NL' ) );
		$info = $this->plugin_info( 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms', $translations );
		$this->assertSame( $info, SUPER_Forms::filter_update_info( $info ) );
		$this->assertSame( '', $this->logged() );
	}

	public function malformed_translation_lists() {
		return array(
			'string'                  => array( 'https://evil.example/nl.zip' ),
			'integer'                 => array( 42 ),
			'boolean'                 => array( true ),
			'null entry'              => array( array( null ) ),
			'string entry'            => array( array( 'https://evil.example/nl.zip' ) ),
			'entry without package'   => array( array( (object) array( 'language' => 'nl_NL', 'version' => '99.0.0', 'updated' => '2026-09-30 00:00:00' ) ) ),
			'entry with null package' => array( array( (object) array( 'language' => 'nl_NL', 'package' => null ) ) ),
		);
	}

	/**
	 * @dataProvider malformed_translation_lists
	 */
	public function test_filter_drops_update_when_translations_is_malformed( $translations ) {
		$info = $this->plugin_info( 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms', $translations );
		$this->assertNull( SUPER_Forms::filter_update_info( $info ) );
		$this->assertSame( 1, substr_count( $this->logged(), 'Super Forms: update ignored' ) );
	}

	public function test_filter_keeps_update_from_trusted_https_host_untouched() {
		$translation = $this->translation( 'https://f4d.nl/@super-forms-updates/?action=download_translation&slug=super-forms&language=nl_NL' );
		$info = $this->plugin_info( 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms', array( $translation ) );

		$filtered = SUPER_Forms::filter_update_info( $info );

		$this->assertSame( $info, $filtered );
		$this->assertSame( 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms', $filtered->download_url );
		$this->assertCount( 1, $filtered->translations );
		$this->assertSame( '', $this->logged() );
	}

	public function test_rejected_update_shows_an_admin_notice_until_a_trusted_update_arrives() {
		delete_option( '_super_update_rejected' );
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}
		wp_set_current_user( $admin );

		$this->assertNull( SUPER_Forms::filter_update_info( $this->plugin_info( 'https://evil.example/super-forms.zip' ) ) );
		$rejected = get_option( '_super_update_rejected' );
		$this->assertIsArray( $rejected );
		$this->assertStringContainsString( 'evil.example', $rejected['reason'] );

		ob_start();
		SUPER_Forms()->show_admin_notices();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'notice-error', $html );
		$this->assertStringContainsString( 'evil.example', $html );

		// Users who can not update plugins do not see it.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		ob_start();
		SUPER_Forms()->show_admin_notices();
		$this->assertStringNotContainsString( 'evil.example', ob_get_clean() );

		// A trusted update clears it.
		SUPER_Forms::filter_update_info( $this->plugin_info( 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms' ) );
		$this->assertFalse( get_option( '_super_update_rejected' ) );
		wp_set_current_user( 0 );
	}

	public function test_filter_passes_null_through_when_the_request_failed() {
		$this->assertNull( SUPER_Forms::filter_update_info( null, new WP_Error( 'http_request_failed', 'timeout' ) ) );
		$this->assertSame( '', $this->logged() );
	}

	public function test_log_line_neutralises_control_characters_in_the_url() {
		$info = $this->plugin_info( "https://evil.example/a\r\nFAKE LOG LINE\n" );
		$this->assertNull( SUPER_Forms::filter_update_info( $info ) );
		$log = $this->logged();
		$this->assertStringNotContainsString( "\nFAKE LOG LINE", $log );
		$this->assertStringContainsString( 'evil.example/a??FAKE LOG LINE?', $log );
	}

	public function test_filter_is_applied_through_the_puc_hook_after_update_plugin() {
		SUPER_Forms()->update_plugin();

		$foreign = $this->plugin_info( 'https://evil.example/super-forms.zip' );
		$this->assertNull( apply_filters( self::RESULT_FILTER, $foreign, null ) );

		$trusted = $this->plugin_info( 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms' );
		$this->assertSame( $trusted, apply_filters( self::RESULT_FILTER, $trusted, null ) );
	}

	public function test_checker_reports_no_update_when_metadata_points_at_foreign_host() {
		$checker = SUPER_Forms()->update_plugin();

		$update = $this->request_update_with_metadata( $checker, array(
			'name'         => 'Super Forms',
			'slug'         => 'super-forms',
			'version'      => '99.0.0',
			'download_url' => 'https://evil.example/super-forms.zip',
		) );

		$this->assertNull( $update );
		$this->assertStringContainsString( 'evil.example', $this->logged() );
	}

	public function test_checker_reports_no_update_when_object_shaped_translations_point_at_foreign_host() {
		$checker = SUPER_Forms()->update_plugin();

		// An associative array encodes as a JSON object, which json_decode() turns into stdClass.
		$update = $this->request_update_with_metadata( $checker, array(
			'name'         => 'Super Forms',
			'slug'         => 'super-forms',
			'version'      => '99.0.0',
			'download_url' => 'https://f4d.nl/@super-forms-updates/?action=download&slug=super-forms',
			'translations' => array( 'x' => (array) $this->translation( 'https://evil.example/nl.zip' ) ),
		) );

		$this->assertNull( $update );
		$this->assertStringContainsString( 'evil.example', $this->logged() );
		// Nothing is left for injectTranslationUpdates() to hand to the language pack upgrader.
		$this->assertSame( array(), $checker->getTranslationUpdates() );
		$transient = $checker->injectTranslationUpdates( (object) array( 'translations' => array() ) );
		$this->assertSame( array(), $transient->translations );
	}
}
