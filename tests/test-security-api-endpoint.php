<?php
/**
 * Security regression: the stable plugin must talk to the production API by default.
 *
 * SUPER_API_ENDPOINT is built in SUPER_Forms::define_constants() from the production
 * base URL, or from the SUPER_API_URL constant (wp-config.php) when that constant is a
 * valid https:// URL. Constants cannot be redefined inside one PHP process, so the
 * resolution lives in SUPER_Forms::resolve_api_url() and the override rules are
 * exercised through that method with plain values.
 *
 * @package Super_Forms\Tests
 */

class Test_Security_Api_Endpoint extends WP_UnitTestCase {

	const PRODUCTION_URL = 'https://api.super-forms.com/';

	/**
	 * (i) Without an override the endpoint constant must point at production.
	 */
	public function test_endpoint_constant_points_at_production_by_default() {
		if ( defined( 'SUPER_API_URL' ) ) {
			$this->markTestSkipped( 'SUPER_API_URL is defined in this test configuration; the default cannot be observed.' );
		}
		$this->assertTrue( defined( 'SUPER_API_ENDPOINT' ) );
		$this->assertStringStartsWith( self::PRODUCTION_URL, SUPER_API_ENDPOINT );
		$this->assertSame( self::PRODUCTION_URL . SUPER_API_VERSION, SUPER_API_ENDPOINT );
		$this->assertSame( self::PRODUCTION_URL, SUPER_Forms::instance()->apiUrl );
		$this->assertSame( false, strpos( SUPER_API_ENDPOINT, 'api.dev.' ) );
	}

	/**
	 * Whatever the configuration, the endpoint is https and derived from the resolved base URL.
	 */
	public function test_endpoint_constant_is_https_and_matches_resolved_base_url() {
		$this->assertStringStartsWith( 'https://', SUPER_API_ENDPOINT );
		$this->assertSame( SUPER_Forms::instance()->apiUrl . SUPER_API_VERSION, SUPER_API_ENDPOINT );
	}

	public function data_override_values() {
		return array(
			'undefined (null)'         => array( null, self::PRODUCTION_URL ),
			'empty string'             => array( '', self::PRODUCTION_URL ),
			'whitespace only'          => array( "  \n", self::PRODUCTION_URL ),
			'https host with slash'    => array( 'https://api.dev.super-forms.com/', 'https://api.dev.super-forms.com/' ),
			'https host without slash' => array( 'https://api.dev.super-forms.com', 'https://api.dev.super-forms.com/' ),
			'https host double slash'  => array( 'https://api.dev.super-forms.com//', 'https://api.dev.super-forms.com/' ),
			'https host padded'        => array( "  https://api.dev.super-forms.com/ \n", 'https://api.dev.super-forms.com/' ),
			'https host with port'     => array( 'https://localhost:8443', 'https://localhost:8443/' ),
			'https host with path'     => array( 'https://staging.example.test/super-forms', 'https://staging.example.test/super-forms/' ),
			'http is ignored'          => array( 'http://api.dev.super-forms.com/', self::PRODUCTION_URL ),
			'uppercase scheme ignored' => array( 'HTTPS://api.dev.super-forms.com/', self::PRODUCTION_URL ),
			'scheme-relative ignored'  => array( '//api.dev.super-forms.com/', self::PRODUCTION_URL ),
			'bare host ignored'        => array( 'api.dev.super-forms.com/', self::PRODUCTION_URL ),
			'ftp ignored'              => array( 'ftp://api.dev.super-forms.com/', self::PRODUCTION_URL ),
			'javascript ignored'       => array( 'javascript:alert(1)', self::PRODUCTION_URL ),
			'https without host'       => array( 'https://', self::PRODUCTION_URL ),
			'https empty host'         => array( 'https:///v1/', self::PRODUCTION_URL ),
			'credentials ignored'      => array( 'https://user:secret@api.dev.super-forms.com/', self::PRODUCTION_URL ),
			'query ignored'            => array( 'https://api.dev.super-forms.com/?x=1', self::PRODUCTION_URL ),
			'fragment ignored'         => array( 'https://api.dev.super-forms.com/#v1', self::PRODUCTION_URL ),
			'boolean true ignored'     => array( true, self::PRODUCTION_URL ),
			'integer ignored'          => array( 1, self::PRODUCTION_URL ),
			'array ignored'            => array( array( 'https://api.dev.super-forms.com/' ), self::PRODUCTION_URL ),
		);
	}

	/**
	 * (ii) The override is honoured only for https:// URLs and is normalised to a trailing slash.
	 *
	 * @dataProvider data_override_values
	 */
	public function test_override_is_honoured_only_for_https_urls( $override, $expected ) {
		$this->assertSame( $expected, SUPER_Forms::resolve_api_url( self::PRODUCTION_URL, $override ) );
	}

	public function test_resolved_url_composes_a_versioned_endpoint() {
		$base = SUPER_Forms::resolve_api_url( self::PRODUCTION_URL, 'https://api.dev.super-forms.com' );
		$this->assertSame( 'https://api.dev.super-forms.com/v1', $base . SUPER_API_VERSION );

		$base = SUPER_Forms::resolve_api_url( self::PRODUCTION_URL, 'http://api.dev.super-forms.com' );
		$this->assertSame( self::PRODUCTION_URL . SUPER_API_VERSION, $base . SUPER_API_VERSION );
	}

	public function test_default_is_returned_untouched_when_override_is_rejected() {
		$this->assertSame( 'https://example.test/', SUPER_Forms::resolve_api_url( 'https://example.test/', 'nonsense' ) );
		$this->assertSame( 'https://example.test/', SUPER_Forms::resolve_api_url( 'https://example.test/' ) );
	}
}
