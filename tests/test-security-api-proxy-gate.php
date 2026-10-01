<?php
/**
 * Security regression: the Licenses screen API proxy (SUPER_Ajax::api_do_request() and
 * SUPER_Ajax::api_auth()) is only usable by administrators and can not be pointed at
 * another server through $_POST['api_endpoint'].
 *
 * The wp_ajax_super_api_* handlers are registered for every logged-in user
 * (includes/class-ajax.php, SUPER_Ajax::init()), so before this change a subscriber
 * could make the site POST (with the license auth cookie in the body) to any URL.
 *
 * @package Super_Forms\Tests
 */

class Super_Forms_Api_Proxy_Wp_Die_Exception extends RuntimeException {
}

class Super_Forms_Api_Proxy_Done_Exception extends RuntimeException {
}

class Test_Security_Api_Proxy_Gate extends WP_UnitTestCase {

	private $original_post = array();
	private $requests = array();

	public static function set_up_before_class() {
		parent::set_up_before_class();
		if ( ! class_exists( 'SUPER_Ajax' ) ) {
			require_once dirname( __DIR__ ) . '/src/includes/class-ajax.php';
		}
	}

	public function set_up() {
		parent::set_up();
		$this->original_post = $_POST;
		$this->requests      = array();
		add_filter( 'pre_http_request', array( $this, 'capture_request' ), 10, 3 );
		add_filter( 'wp_die_handler', array( $this, 'sf_die_handler' ) );
		add_filter( 'wp_die_ajax_handler', array( $this, 'sf_die_handler' ) );
	}

	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'capture_request' ), 10 );
		remove_filter( 'wp_die_handler', array( $this, 'sf_die_handler' ) );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'sf_die_handler' ) );
		$_POST = $this->original_post;
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	public function capture_request( $preempt, $args, $url ) {
		$this->requests[] = $url;
		return array(
			'headers'  => array(),
			'body'     => '{"status":200,"body":"ok"}',
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	public function sf_die_handler() {
		return array( $this, 'throw_wp_die' );
	}

	public function throw_wp_die( $message, $title = '', $args = array() ) {
		throw new Super_Forms_Api_Proxy_Wp_Die_Exception( is_string( $message ) ? $message : 'wp_die' );
	}

	/**
	 * Run api_do_request() up to its die() by returning early through $method='return'.
	 */
	private function do_request( $route ) {
		return SUPER_Ajax::api_do_request( $route, array( 'body' => array( 'x' => 'y' ) ), 'return' );
	}

	public function test_subscriber_is_refused_before_any_http_request() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST = array( 'api_endpoint' => 'https://evil.example/v1' );
		try {
			$this->do_request( 'addons/list' );
			$this->fail( 'A subscriber reached the API proxy.' );
		} catch ( Super_Forms_Api_Proxy_Wp_Die_Exception $e ) {
			$this->assertSame( array(), $this->requests, 'No HTTP request may be made for a refused user.' );
		}
	}

	public function test_subscriber_can_not_set_the_auth_cookie() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$_POST = array( 'auth' => 'token' );
		$this->expectException( Super_Forms_Api_Proxy_Wp_Die_Exception::class );
		SUPER_Ajax::api_auth();
	}

	public function test_administrator_request_goes_to_the_configured_api() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_POST = array();
		$this->do_request( 'addons/list' );
		$this->assertSame( array( SUPER_API_ENDPOINT . '/addons/list' ), $this->requests );
	}

	public function test_administrator_can_not_redirect_the_request_to_another_host() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_POST = array( 'api_endpoint' => 'https://evil.example/v1' );
		$this->do_request( 'addons/list' );
		$this->assertSame( array( SUPER_API_ENDPOINT . '/addons/list' ), $this->requests );
	}

	/**
	 * $_POST['api_endpoint'] is ignored entirely: the handlers can not require a nonce (the API
	 * rendered Licenses page does not send one), so a forged request must not be able to move the
	 * auth cookie to any other host, sub domain or path, not even one of our own.
	 */
	public function posted_endpoint_cases() {
		return array(
			'the configured endpoint' => array( SUPER_API_ENDPOINT ),
			'trailing slash'          => array( SUPER_API_ENDPOINT . '/' ),
			'our dev sub domain'      => array( 'https://api.dev.super-forms.com/v1' ),
			'our root domain'         => array( 'https://super-forms.com/v1' ),
			'same host other path'    => array( 'https://api.super-forms.com/other' ),
			'http'                    => array( 'http://api.super-forms.com/v1' ),
			'foreign host'            => array( 'https://evil.example/v1' ),
			'look-alike suffix'       => array( 'https://api.super-forms.com.evil.example/v1' ),
			'userinfo'                => array( 'https://api.super-forms.com@evil.example/v1' ),
			'query'                   => array( 'https://api.super-forms.com/v1?x=1' ),
			'not a string'            => array( array( 'https://evil.example/v1' ) ),
		);
	}

	/**
	 * @dataProvider posted_endpoint_cases
	 */
	public function test_posted_api_endpoint_is_ignored( $posted ) {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_POST = array( 'api_endpoint' => $posted );
		$this->do_request( 'addons/list' );
		$this->assertSame( array( SUPER_API_ENDPOINT . '/addons/list' ), $this->requests );
	}

	public function test_configured_endpoint_is_the_https_api_host() {
		$this->assertSame( 'https', wp_parse_url( SUPER_API_ENDPOINT, PHP_URL_SCHEME ) );
		$this->assertSame( 'api.super-forms.com', wp_parse_url( SUPER_API_ENDPOINT, PHP_URL_HOST ) );
	}

	public function auth_token_cases() {
		return array(
			'raw base64'         => array( 'AbC09+/xyz', 'AbC09+/xyz' ),
			'padded base64'      => array( 'QUJD==', 'QUJD==' ),
			'slashed by WP'      => array( 'QUJD\\/x', 'QUJD/x' ),
			'empty'              => array( '', false ),
			'not a string'       => array( array( 'QUJD' ), false ),
			'too long'           => array( str_repeat( 'A', 2049 ), false ),
			'max length'         => array( str_repeat( 'A', 2048 ), str_repeat( 'A', 2048 ) ),
			'cookie separator'   => array( 'QUJD; path=/', false ),
			'html'               => array( '<script>', false ),
			'newline'            => array( "QUJD\n", false ),
			'padding in middle'  => array( 'QU=JD', false ),
			'url-safe alphabet'  => array( 'QU-J_D', false ),
		);
	}

	/**
	 * @dataProvider auth_token_cases
	 */
	public function test_auth_token_validation( $posted, $expected ) {
		$this->assertSame( $expected, SUPER_Ajax::api_sanitize_auth_token( $posted ) );
	}
}
