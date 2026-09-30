<?php
/**
 * Security regression: the stable plugin must talk to the production API by default,
 * and a request can not be pointed at another server or fired by a non-administrator.
 *
 * SUPER_API_ENDPOINT is built in SUPER_Forms::define_constants() from the production
 * base URL, or from the SUPER_API_URL constant (wp-config.php) when that constant is a
 * valid https:// URL. Constants cannot be redefined inside one PHP process, so the
 * resolution lives in SUPER_Forms::resolve_api_url() and the override rules are
 * exercised through that method with plain values.
 *
 * SUPER_Ajax::api_do_request() used to POST to whatever $_POST['api_endpoint'] held and
 * the wp_ajax_super_api_* handlers were open to every logged-in user. They now require
 * manage_options (SUPER_Ajax::api_verify_access()) and only honour a caller supplied
 * endpoint on the configured API host (SUPER_Ajax::api_resolve_endpoint()).
 *
 * @package Super_Forms\Tests
 */

class Super_Forms_Api_Endpoint_Wp_Die_Exception extends RuntimeException {
}

class Super_Forms_Api_Endpoint_Side_Effect_Exception extends RuntimeException {
}

class Test_Security_Api_Endpoint extends WP_UnitTestCase {

	const PRODUCTION_URL = 'https://api.super-forms.com/';

	private $filters = array();
	private $original_current_user = 0;
	private $original_post = array();
	private $original_request = array();
	private $remote_requests = array();
	private $user_ids = array();

	/**
	 * Load the AJAX handlers under test.
	 *
	 * The WordPress test bootstrap never defines DOING_AJAX, so super-forms.php
	 * (is_request('ajax')) skips ajax_includes() and none of the wp_ajax_super_*
	 * actions exist in the test process. Including src/includes/class-ajax.php runs
	 * SUPER_Ajax::init() (bottom of that file), which registers the handlers.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		if ( ! class_exists( 'SUPER_Ajax' ) ) {
			require_once dirname( __DIR__ ) . '/src/includes/class-ajax.php';
		}
	}

	public function set_up() {
		parent::set_up();
		if ( ! has_action( 'wp_ajax_super_api_logout_user' ) ) {
			SUPER_Ajax::init();
		}
		$this->original_current_user = get_current_user_id();
		$this->original_post         = $_POST;
		$this->original_request      = $_REQUEST;
		$this->remote_requests       = array();
		$_POST                       = array();
		$_REQUEST                    = array();
		wp_set_current_user( 0 );
	}

	public function tear_down() {
		foreach ( array_reverse( $this->filters ) as $filter ) {
			remove_filter( $filter['tag'], $filter['callback'], $filter['priority'] );
		}
		$this->filters = array();

		wp_set_current_user( 0 );
		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}
		foreach ( array_reverse( $this->user_ids ) as $user_id ) {
			if ( get_userdata( $user_id ) ) {
				wp_delete_user( $user_id );
			}
		}
		$this->user_ids = array();

		$_POST    = $this->original_post;
		$_REQUEST = $this->original_request;
		wp_set_current_user( $this->original_current_user );
		parent::tear_down();
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

	/**
	 * Record every outgoing HTTP request instead of sending it. A rejected request must
	 * never reach this filter, so $fail_on_request turns a reach into an exception.
	 */
	private function install_remote_transport( $fail_on_request ) {
		$remote_filter = function ( $preempt, $args, $url ) use ( $fail_on_request ) {
			if ( $fail_on_request ) {
				throw new Super_Forms_Api_Endpoint_Side_Effect_Exception( 'Rejected API proxy request reached the HTTP transport: ' . $url );
			}
			$this->remote_requests[] = array(
				'url'  => $url,
				'args' => $args,
			);
			return array(
				'headers'  => array(),
				'body'     => 'stub-api-body:' . $url,
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

	private function invoke_with_wp_die_capture( $callback ) {
		$die_filter = static function () {
			return static function ( $message = '' ) {
				throw new Super_Forms_Api_Endpoint_Wp_Die_Exception( (string) $message );
			};
		};
		$ajax_filter = static function () {
			return true;
		};
		add_filter( 'wp_doing_ajax', $ajax_filter );
		add_filter( 'wp_die_handler', $die_filter );
		add_filter( 'wp_die_ajax_handler', $die_filter );

		$termination = 'returned';
		$message     = '';
		$output      = '';
		ob_start();
		try {
			call_user_func( $callback );
		} catch ( Super_Forms_Api_Endpoint_Wp_Die_Exception $exception ) {
			$termination = 'wp_die';
			$message     = $exception->getMessage();
		} catch ( Super_Forms_Api_Endpoint_Side_Effect_Exception $exception ) {
			$termination = 'side_effect';
			$message     = $exception->getMessage();
		} finally {
			$output = ob_get_clean();
			remove_filter( 'wp_doing_ajax', $ajax_filter );
			remove_filter( 'wp_die_handler', $die_filter );
			remove_filter( 'wp_die_ajax_handler', $die_filter );
		}

		return array(
			'termination' => $termination,
			'message'     => $message,
			'output'      => $output,
		);
	}

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

	/**
	 * Values a caller can put in $_POST['api_endpoint'] against the production endpoint.
	 */
	public function data_requested_endpoints() {
		$prod = 'https://api.super-forms.com/v1';
		return array(
			'null (not posted)'                => array( null, $prod ),
			'empty string'                     => array( '', $prod ),
			'array'                            => array( array( 'https://attacker.test/v1' ), $prod ),
			'same endpoint'                    => array( 'https://api.super-forms.com/v1', 'https://api.super-forms.com/v1' ),
			'same endpoint trailing slash'     => array( 'https://api.super-forms.com/v1/', 'https://api.super-forms.com/v1' ),
			'same host uppercase'              => array( 'https://API.SUPER-FORMS.COM/v1', 'https://API.SUPER-FORMS.COM/v1' ),
			'same host other port'             => array( 'https://api.super-forms.com:8443/v1', 'https://api.super-forms.com:8443/v1' ),
			'other super-forms.com subdomain'  => array( 'https://api.dev.super-forms.com/v1', 'https://api.dev.super-forms.com/v1' ),
			'apex super-forms.com ignored'     => array( 'https://super-forms.com/v1', $prod ),
			'dot-only super-forms.com ignored' => array( 'https://.super-forms.com/v1', $prod ),
			'foreign host ignored'             => array( 'https://attacker.test/v1', $prod ),
			'foreign host with our path'       => array( 'https://attacker.test/api.super-forms.com/v1', $prod ),
			'suffix lookalike ignored'         => array( 'https://api.super-forms.com.attacker.test/v1', $prod ),
			'prefix lookalike ignored'         => array( 'https://xsuper-forms.com/v1', $prod ),
			'userinfo lookalike ignored'       => array( 'https://api.super-forms.com@attacker.test/v1', $prod ),
			'backslash userinfo ignored'       => array( 'https://api.super-forms.com\\@attacker.test/v1', $prod ),
			'credentials ignored'              => array( 'https://user:secret@api.super-forms.com/v1', $prod ),
			'password only ignored'            => array( 'https://:secret@api.super-forms.com/v1', $prod ),
			'query ignored'                    => array( 'https://api.super-forms.com/v1?x=1', $prod ),
			'fragment ignored'                 => array( 'https://api.super-forms.com/v1#x', $prod ),
			'http ignored'                     => array( 'http://api.super-forms.com/v1', $prod ),
			'uppercase scheme ignored'         => array( 'HTTPS://api.super-forms.com/v1', $prod ),
			'scheme-relative ignored'          => array( '//api.super-forms.com/v1', $prod ),
			'bare host ignored'                => array( 'api.super-forms.com/v1', $prod ),
			'padded ignored'                   => array( ' https://api.super-forms.com/v1', $prod ),
			'javascript ignored'               => array( 'javascript:alert(1)', $prod ),
			'https without host'               => array( 'https://', $prod ),
		);
	}

	/**
	 * @dataProvider data_requested_endpoints
	 */
	public function test_requested_endpoint_is_honoured_only_on_the_configured_api_host( $requested, $expected ) {
		$this->assertSame( $expected, SUPER_Ajax::api_resolve_endpoint( $requested, 'https://api.super-forms.com/v1' ) );
	}

	/**
	 * A site that runs against another host via SUPER_API_URL keeps working: the
	 * configured host is always allowed, foreign hosts still are not.
	 */
	public function test_requested_endpoint_follows_a_non_production_configured_host() {
		$default = 'https://localhost:8443/v1';
		$this->assertSame( 'https://localhost:8443/v1', SUPER_Ajax::api_resolve_endpoint( 'https://localhost:8443/v1/', $default ) );
		$this->assertSame( 'https://api.super-forms.com/v1', SUPER_Ajax::api_resolve_endpoint( 'https://api.super-forms.com/v1', $default ) );
		$this->assertSame( $default, SUPER_Ajax::api_resolve_endpoint( 'https://attacker.test/v1', $default ) );
		$this->assertSame( $default, SUPER_Ajax::api_resolve_endpoint( 'http://localhost:8443/v1', $default ) );
	}

	public function test_api_handlers_are_not_registered_for_anonymous_visitors() {
		$actions = array( 'auth', 'logout_user', 'login_user', 'register_user', 'send_reset_password_email', 'reset_password', 'verify_code', 'transfer_license', 'cancel_subscription', 'start_trial', 'checkout', 'submit_feedback' );
		foreach ( $actions as $action ) {
			$this->assertNotFalse( has_action( 'wp_ajax_super_api_' . $action ), 'wp_ajax_super_api_' . $action . ' is not registered.' );
			$this->assertFalse( has_action( 'wp_ajax_nopriv_super_api_' . $action ), 'wp_ajax_nopriv_super_api_' . $action . ' must not be registered.' );
		}
	}

	public static function rejected_actor_provider() {
		return array(
			'anonymous'  => array( 'anonymous' ),
			'subscriber' => array( 'subscriber' ),
			'author'     => array( 'author' ),
			'editor'     => array( 'editor' ),
		);
	}

	/**
	 * @dataProvider rejected_actor_provider
	 */
	public function test_api_proxy_rejects_non_administrators_before_any_request( $actor ) {
		$this->set_actor( $actor );
		$this->install_remote_transport( true );
		$_POST    = array(
			'action'       => 'super_api_logout_user',
			'api_endpoint' => 'https://attacker.test/v1',
		);
		$_REQUEST = $_POST;

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				do_action( 'wp_ajax_super_api_logout_user' );
			}
		);
		$this->assertSame( 'wp_die', $result['termination'], 'Non-administrator API proxy request did not terminate through wp_die: ' . $result['message'] );
		$this->assertSame( '', $result['output'], 'Rejected API proxy request produced output.' );
		$this->assertSame( array(), $this->remote_requests );
	}

	/**
	 * @dataProvider rejected_actor_provider
	 */
	public function test_api_auth_cookie_rejects_non_administrators( $actor ) {
		$this->set_actor( $actor );
		$_POST    = array(
			'action' => 'super_api_auth',
			'auth'   => 'forged-token',
		);
		$_REQUEST = $_POST;

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				do_action( 'wp_ajax_super_api_auth' );
			}
		);
		$this->assertSame( 'wp_die', $result['termination'], 'Non-administrator super_api_auth did not terminate through wp_die: ' . $result['message'] );
		$this->assertSame( '', $result['output'] );
	}

	/**
	 * The direct (non-hook) entry point used by SUPER_Pages::addons() is guarded as well.
	 */
	public function test_direct_api_do_request_rejects_non_administrators() {
		$this->set_actor( 'subscriber' );
		$this->install_remote_transport( true );

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				SUPER_Ajax::api_do_request( 'addons/list', array( 'body' => array() ), 'return' );
			}
		);
		$this->assertSame( 'wp_die', $result['termination'], 'Direct api_do_request() by a subscriber did not terminate through wp_die: ' . $result['message'] );
		$this->assertSame( array(), $this->remote_requests );
	}

	public static function administrator_endpoint_provider() {
		return array(
			'no api_endpoint posted'      => array( null ),
			'foreign host posted'         => array( 'https://attacker.test/v1' ),
			'http scheme posted'          => array( 'http://api.super-forms.com/v1' ),
			'userinfo lookalike posted'   => array( 'https://api.super-forms.com@attacker.test/v1' ),
			'configured endpoint posted'  => array( 'SUPER_API_ENDPOINT' ),
			'configured endpoint + slash' => array( 'SUPER_API_ENDPOINT/' ),
		);
	}

	/**
	 * An administrator's request is sent, but always to the configured endpoint unless the
	 * posted one is on the API host itself. Uses the 'return' mode so the process survives.
	 *
	 * @dataProvider administrator_endpoint_provider
	 */
	public function test_administrator_request_goes_to_the_configured_endpoint( $posted ) {
		$this->set_actor( 'administrator' );
		$this->install_remote_transport( false );
		$_POST = array( 'action' => 'super_api_logout_user' );
		if ( null !== $posted ) {
			$_POST['api_endpoint'] = str_replace( 'SUPER_API_ENDPOINT', SUPER_API_ENDPOINT, $posted );
		}
		$_REQUEST = $_POST;

		$result = $this->invoke_with_wp_die_capture(
			static function () {
				return SUPER_Ajax::api_do_request( 'addons/list', array( 'body' => array( 'email' => 'admin@example.test' ) ), 'return' );
			}
		);
		$this->assertSame( 'returned', $result['termination'], $result['message'] );
		$this->assertCount( 1, $this->remote_requests );
		$this->assertSame( SUPER_API_ENDPOINT . '/addons/list', $this->remote_requests[0]['url'] );
		$this->assertSame( 'POST', $this->remote_requests[0]['args']['method'] );
		$this->assertStringStartsWith( 'https://', $this->remote_requests[0]['url'] );
		$this->assertSame( false, strpos( $this->remote_requests[0]['url'], 'attacker.test' ) );
	}

	public function test_administrator_request_returns_the_api_body_from_the_configured_endpoint() {
		$this->set_actor( 'administrator' );
		$this->install_remote_transport( false );
		$_POST    = array(
			'action'       => 'super_api_logout_user',
			'api_endpoint' => 'https://attacker.test/v1',
		);
		$_REQUEST = $_POST;

		$body = SUPER_Ajax::api_do_request( 'addons/list', array( 'body' => array() ), 'return' );
		$this->assertSame( 'stub-api-body:' . SUPER_API_ENDPOINT . '/addons/list', $body );
	}
}
