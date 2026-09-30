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
			require_once dirname( __DIR__ ) . '/includes/class-ajax.php';
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

	public function resolve_cases() {
		$default = 'https://api.super-forms.com/v1';
		return array(
			'same host'              => array( 'https://api.super-forms.com/v1', $default, 'https://api.super-forms.com/v1' ),
			'trailing slash trimmed' => array( 'https://api.super-forms.com/v1/', $default, 'https://api.super-forms.com/v1' ),
			'our subdomain'          => array( 'https://api.dev.super-forms.com/v1', $default, 'https://api.dev.super-forms.com/v1' ),
			'http'                   => array( 'http://api.super-forms.com/v1', $default, $default ),
			'foreign host'           => array( 'https://evil.example/v1', $default, $default ),
			'look-alike suffix'      => array( 'https://api.super-forms.com.evil.example/v1', $default, $default ),
			'look-alike prefix'      => array( 'https://evilsuper-forms.com/v1', $default, $default ),
			'userinfo'               => array( 'https://api.super-forms.com@evil.example/v1', $default, $default ),
			'query'                  => array( 'https://api.super-forms.com/v1?x=1', $default, $default ),
			'fragment'               => array( 'https://api.super-forms.com/v1#x', $default, $default ),
			'not a string'           => array( array( 'https://api.super-forms.com/v1' ), $default, $default ),
		);
	}

	/**
	 * @dataProvider resolve_cases
	 */
	public function test_resolve_endpoint( $requested, $default, $expected ) {
		$this->assertSame( $expected, SUPER_Ajax::api_resolve_endpoint( $requested, $default ) );
	}
}
