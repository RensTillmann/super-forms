<?php
/**
 * Regressions for the cached licence-transient call SUPER_Common::get_transient()
 * (includes/class-common.php) and SUPER_Common::flush_api_transients().
 *
 * The API is mocked through the pre_http_request filter; no test reaches the
 * network. The disable-cache scenario defines SUPER_API_TRANSIENT_DISABLE_CACHE
 * for the rest of the process, so it is the last method of this class and the
 * cache scenarios skip themselves when the constant is already set.
 *
 * @package Super_Forms\Tests
 */

class Test_Security_Api_Transient_Cache extends WP_UnitTestCase {

	const ALERT      = '<script>alert("Connection error! Please refresh the page to try again, or contact support.");</script>';
	const ERROR_TEXT = 'SENTINEL-ERROR-TEXT-must-never-reach-the-page';

	private static $slugs = array( 'before_do_shortcode', 'before_do_shortcode_admin', 'super-forms_page_super_create_form' );

	private $filters   = array();
	private $requests  = array();
	private $responder = null;

	public function set_up() {
		parent::set_up();
		$this->requests  = array();
		$this->responder = null;
		$this->reset_cache_state();
		$this->add_tracked_filter( 'pre_http_request', array( $this, 'mock_api' ), 10, 3 );
	}

	public function tear_down() {
		foreach ( array_reverse( $this->filters ) as $filter ) {
			remove_filter( $filter['tag'], $filter['callback'], $filter['priority'] );
		}
		$this->filters = array();
		$this->reset_cache_state();
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

	private function reset_cache_state() {
		SUPER_Common::flush_api_transients();
		foreach ( self::$slugs as $slug ) {
			delete_option( '_super_api_transient_last_' . $this->key( $slug ) );
		}
	}

	/**
	 * The cache key uses the home URL as stored in the database, never the (filterable,
	 * WP_HOME / Host header dependent) get_home_url().
	 */
	private function key( $slug ) {
		return md5( $slug . '|' . $this->stored_home() );
	}

	/**
	 * The home URL as stored in the database: the cache key and the home_url the
	 * request sends both use it.
	 */
	private function stored_home() {
		global $wpdb;
		return $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = 'home'" );
	}

	private function fresh_body( $slug ) {
		$fresh = get_transient( '_super_api_transient_' . $this->key( $slug ) );
		$this->assertIsArray( $fresh, 'The fresh transient must exist.' );
		$this->assertSame( SUPER_VERSION, $fresh['version'], 'The fresh transient must record the plugin version.' );
		return $fresh['body'];
	}

	private function cache_rows() {
		global $wpdb;
		return $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%\\_super\\_api\\_transient%' ORDER BY option_name" );
	}

	private function endpoint() {
		return SUPER_API_ENDPOINT . '/settings/transient';
	}

	private function fixture_body( $prefix ) {
		// Quotes, slashes, angle brackets, a newline and non-ASCII: the bytes the API really serves.
		return '<script id="sftmp">var u = "https://example.test/wp-admin/";' . "\n" . '/* ' . $prefix . ' ünïcödé ✓ */ if(a<b && c>d){ x = "\\u0041"; }</script>';
	}

	/**
	 * Encode like the Go API (encoding/json escapes <, > and &).
	 */
	private function api_json( $status, $body ) {
		return json_encode( array( 'status' => $status, 'body' => $body ), JSON_HEX_TAG | JSON_HEX_AMP );
	}

	private function http_response( $code, $body ) {
		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => $code,
				'message' => ( 200 === $code ) ? 'OK' : 'Error',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	public function mock_api( $preempt, $args, $url ) {
		if ( $this->endpoint() !== $url ) {
			return $preempt;
		}
		$this->requests[] = array(
			'url'  => $url,
			'args' => $args,
		);
		if ( null === $this->responder ) {
			$this->fail( 'The licence transient call reached the API without a mocked response.' );
		}
		return call_user_func( $this->responder, $args, $url );
	}

	private function respond_with_body( $body ) {
		$json            = $this->api_json( 200, $body );
		$this->responder = function () use ( $json ) {
			return $this->http_response( 200, $json );
		};
	}

	private function respond_with_failure( $kind ) {
		$this->responder = function () use ( $kind ) {
			switch ( $kind ) {
				case 'wp_error':
					return new WP_Error( 'http_request_failed', 'cURL error 28: ' . self::ERROR_TEXT );
				case 'http_500':
					return $this->http_response( 500, '{"status":500,"message":"' . self::ERROR_TEXT . '"}' );
				case 'non_json':
					return $this->http_response( 200, '<html>' . self::ERROR_TEXT . '</html>' );
				case 'status_not_200':
					return $this->http_response( 200, $this->api_json( 403, self::ERROR_TEXT ) );
				case 'empty':
					return $this->http_response( 200, '' );
			}
			$this->fail( 'Unknown failure kind ' . $kind );
		};
	}

	private function fallback_for( $slug ) {
		return ( 'super-forms_page_super_create_form' === $slug ) ? self::ALERT : '';
	}

	private function call( $slug ) {
		return SUPER_Common::get_transient( array( 'slug' => $slug ) );
	}

	private function assert_no_error_text( $output, $message ) {
		$this->assertIsString( $output, $message );
		$this->assertStringNotContainsString( self::ERROR_TEXT, $output, $message );
		$this->assertStringNotContainsString( 'cURL error', $output, $message );
	}

	private function require_cache_enabled() {
		if ( defined( 'SUPER_API_TRANSIENT_DISABLE_CACHE' ) && SUPER_API_TRANSIENT_DISABLE_CACHE ) {
			$this->markTestSkipped( 'SUPER_API_TRANSIENT_DISABLE_CACHE is defined in this process.' );
		}
	}

	public static function slug_provider() {
		return array(
			'front-end slug'       => array( 'before_do_shortcode' ),
			'front-end admin slug' => array( 'before_do_shortcode_admin' ),
			'builder slug'         => array( 'super-forms_page_super_create_form' ),
		);
	}

	public static function failure_provider() {
		return array(
			'WP_Error'           => array( 'wp_error' ),
			'HTTP 500'           => array( 'http_500' ),
			'non-JSON body'      => array( 'non_json' ),
			'JSON status != 200' => array( 'status_not_200' ),
			'empty response'     => array( 'empty' ),
		);
	}

	/**
	 * (i) success body served byte-identical and cached (fresh transient + last-known-good option)
	 *
	 * @dataProvider slug_provider
	 */
	public function test_success_body_is_served_byte_identical_and_cached( $slug ) {
		$this->require_cache_enabled();
		$body = $this->fixture_body( $slug );
		$this->respond_with_body( $body );

		$before = time();
		$this->assertSame( $body, $this->call( $slug ), 'The served body must be byte-identical to what the API sent.' );
		$this->assertCount( 1, $this->requests );

		$key = $this->key( $slug );
		$this->assertSame( $body, $this->fresh_body( $slug ), 'The fresh transient must hold the exact body.' );
		$last = get_option( '_super_api_transient_last_' . $key );
		$this->assertIsArray( $last );
		$this->assertSame( $body, $last['body'], 'The last-known-good option must hold the exact body.' );
		$this->assertSame( SUPER_VERSION, $last['version'], 'The last-known-good option must record the plugin version.' );
		$this->assertGreaterThanOrEqual( $before, (int) $last['time'] );
		$this->assertLessThanOrEqual( time(), (int) $last['time'] );
		$this->assertFalse( get_transient( '_super_api_transient_cb' ), 'A success must not trip the breaker.' );
		$this->assertFalse( get_option( '_super_api_transient_lock_' . $key ), 'The refresh lock must be released after a success.' );
		// Not autoloaded on every WordPress version: absent from the alloptions set, and the raw
		// column reads 'no' before 6.6 and 'off' since (wp_determine_option_autoload_value()).
		$this->assertArrayNotHasKey( '_super_api_transient_last_' . $key, wp_load_alloptions(), 'The last-known-good option must not be autoloaded.' );
		$this->assertContains( $this->autoload_of( '_super_api_transient_last_' . $key ), array( 'no', 'off' ), 'The last-known-good option must not be autoloaded.' );
	}

	private function autoload_of( $option ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", $option ) );
	}

	/**
	 * (i) an empty body (licensed site, front-end slug) is a valid answer and is cached as such
	 */
	public function test_empty_success_body_is_cached_and_served_without_a_second_request() {
		$this->require_cache_enabled();
		$this->respond_with_body( '' );
		$this->assertSame( '', $this->call( 'before_do_shortcode' ) );
		$this->assertSame( '', $this->call( 'before_do_shortcode' ) );
		$this->assertCount( 1, $this->requests, 'An empty body is a valid cached answer, not a miss.' );
		$this->assertFalse( get_transient( '_super_api_transient_cb' ) );
	}

	/**
	 * (ii) second call makes no HTTP request
	 */
	public function test_second_call_is_served_from_the_fresh_transient_without_a_request() {
		$this->require_cache_enabled();
		$body = $this->fixture_body( 'cached' );
		$this->respond_with_body( $body );
		$this->assertSame( $body, $this->call( 'before_do_shortcode_admin' ) );
		$this->respond_with_body( 'changed-on-the-api' );
		$this->assertSame( $body, $this->call( 'before_do_shortcode_admin' ) );
		$this->assertCount( 1, $this->requests, 'The second call must be served from the transient.' );
	}

	/**
	 * (iii) failure after a cached good answer -> stale body served, output contains no error text
	 *
	 * @dataProvider failure_provider
	 */
	public function test_failure_after_a_cached_good_answer_serves_the_stale_body_without_error_text( $kind ) {
		$this->require_cache_enabled();
		foreach ( self::$slugs as $slug ) {
			$good = $this->fixture_body( 'good-' . $slug );
			$this->respond_with_body( $good );
			$this->assertSame( $good, $this->call( $slug ) );
			$count = count( $this->requests );

			// Expire the fresh copy and the breaker so the next call really refreshes.
			delete_transient( '_super_api_transient_' . $this->key( $slug ) );
			delete_transient( '_super_api_transient_cb' );
			$this->respond_with_failure( $kind );

			$output = $this->call( $slug );
			$this->assertCount( $count + 1, $this->requests, 'The refresh must have been attempted once.' );
			$this->assertSame( $good, $output, 'The last-known-good body must be served on ' . $kind . ' for ' . $slug );
			$this->assert_no_error_text( $output, $kind . ' leaked into the page for ' . $slug );
			$this->assertNotFalse( get_transient( '_super_api_transient_cb' ), 'A failure must trip the breaker.' );
			$this->assertFalse( get_option( '_super_api_transient_lock_' . $this->key( $slug ) ), 'The refresh lock must be released after a failure.' );
			delete_transient( '_super_api_transient_cb' );
		}
	}

	/**
	 * (iv) failure with nothing cached -> '' for the front slugs, the alert only for the builder slug
	 *
	 * @dataProvider failure_provider
	 */
	public function test_failure_with_nothing_cached_returns_empty_for_front_slugs_and_the_alert_for_the_builder( $kind ) {
		$this->require_cache_enabled();
		$this->respond_with_failure( $kind );
		foreach ( self::$slugs as $slug ) {
			delete_transient( '_super_api_transient_cb' );
			$count  = count( $this->requests );
			$output = $this->call( $slug );
			$this->assertCount( $count + 1, $this->requests, 'The request must have been attempted for ' . $slug );
			$this->assertSame( $this->fallback_for( $slug ), $output, $kind . ' must yield the bare fallback for ' . $slug );
			$this->assert_no_error_text( $output, $kind . ' leaked into the page for ' . $slug );
			$this->assertFalse( get_transient( '_super_api_transient_' . $this->key( $slug ) ), 'A failure must not be cached as fresh.' );
			$this->assertFalse( get_option( '_super_api_transient_last_' . $this->key( $slug ) ), 'A failure must not become last-known-good.' );
		}
	}

	/**
	 * (v) the breaker prevents a second request within 5 minutes (for every slug)
	 */
	public function test_breaker_prevents_a_second_request_within_five_minutes() {
		$this->require_cache_enabled();
		$this->respond_with_failure( 'wp_error' );
		$this->assertSame( '', $this->call( 'before_do_shortcode' ) );
		$this->assertCount( 1, $this->requests );
		$this->assertNotFalse( get_transient( '_super_api_transient_cb' ) );

		$this->respond_with_body( 'would-be-served-if-requested' );
		$this->assertSame( '', $this->call( 'before_do_shortcode' ) );
		$this->assertSame( '', $this->call( 'before_do_shortcode_admin' ) );
		$this->assertSame( self::ALERT, $this->call( 'super-forms_page_super_create_form' ) );
		$this->assertCount( 1, $this->requests, 'No request may be made while the breaker transient exists.' );

		if ( ! wp_using_ext_object_cache() ) {
			$timeout = (int) get_option( '_transient_timeout__super_api_transient_cb' );
			$this->assertGreaterThan( time() + 4 * MINUTE_IN_SECONDS, $timeout );
			$this->assertLessThanOrEqual( time() + 5 * MINUTE_IN_SECONDS, $timeout );
		}

		delete_transient( '_super_api_transient_cb' );
		$this->assertSame( 'would-be-served-if-requested', $this->call( 'before_do_shortcode' ) );
		$this->assertCount( 2, $this->requests, 'Once the breaker is gone the request is made again.' );
	}

	/**
	 * (vi) the request args carry timeout 3 and the unchanged endpoint, headers and body fields
	 *
	 * @dataProvider slug_provider
	 */
	public function test_request_carries_timeout_three_and_the_unchanged_body_fields( $slug ) {
		$this->respond_with_body( 'ok' );
		delete_transient( '_super_api_transient_cb' );
		$this->assertSame( 'ok', $this->call( $slug ) );
		$this->assertCount( 1, $this->requests );

		$request = $this->requests[0];
		$this->assertSame( $this->endpoint(), $request['url'] );
		$args = $request['args'];
		$this->assertSame( 'POST', $args['method'] );
		$this->assertSame( 3, $args['timeout'], 'The licence call must give up after 3 seconds.' );
		$this->assertSame( 'body', $args['data_format'] );
		$this->assertSame( array( 'Content-Type' => 'application/json; charset=utf-8' ), $args['headers'] );
		$this->assertSame(
			array(
				'slug'      => $slug,
				'home_url'  => $this->stored_home(),
				'admin_url' => admin_url(),
				'version'   => SUPER_VERSION,
			),
			json_decode( $args['body'], true ),
			'The request body must keep exactly the fields and order the API golden tests expect.'
		);
	}

	/**
	 * (vii) flush_api_transients() forces a refresh but keeps the last-known-good copy
	 */
	public function test_flush_api_transients_forces_a_refresh_and_keeps_last_known_good() {
		$this->require_cache_enabled();
		$first = $this->fixture_body( 'first' );
		$this->respond_with_body( $first );
		$this->assertSame( $first, $this->call( 'before_do_shortcode' ) );
		$this->assertSame( $first, $this->call( 'super-forms_page_super_create_form' ) );
		$this->assertCount( 2, $this->requests );

		$second = $this->fixture_body( 'second' );
		$this->respond_with_body( $second );
		$this->assertSame( $first, $this->call( 'before_do_shortcode' ), 'Still cached before the flush.' );
		$this->assertCount( 2, $this->requests );

		// A lock and a tripped breaker are cleared by the flush as well.
		add_option( '_super_api_transient_lock_' . $this->key( 'before_do_shortcode' ), time(), '', 'no' );
		set_transient( '_super_api_transient_cb', time(), 5 * MINUTE_IN_SECONDS );

		SUPER_Common::flush_api_transients();

		foreach ( self::$slugs as $slug ) {
			$this->assertFalse( get_transient( '_super_api_transient_' . $this->key( $slug ) ), 'The flush must drop the fresh transient of ' . $slug );
			$this->assertFalse( get_option( '_super_api_transient_lock_' . $this->key( $slug ) ), 'The flush must drop the lock of ' . $slug );
		}
		$this->assertFalse( get_transient( '_super_api_transient_cb' ), 'The flush must drop the breaker.' );
		$last = get_option( '_super_api_transient_last_' . $this->key( 'before_do_shortcode' ) );
		$this->assertSame( $first, $last['body'], 'The flush must keep the last-known-good copy.' );

		$this->assertSame( $second, $this->call( 'before_do_shortcode' ) );
		$this->assertSame( $second, $this->call( 'super-forms_page_super_create_form' ) );
		$this->assertCount( 4, $this->requests, 'Each slug must be fetched again after the flush.' );
	}

	/**
	 * (3) a last-known-good copy older than the stale maximum is not served
	 */
	public function test_last_known_good_older_than_seven_days_falls_back_to_empty_or_alert() {
		$this->require_cache_enabled();
		$this->respond_with_failure( 'wp_error' );
		foreach ( self::$slugs as $slug ) {
			$option = '_super_api_transient_last_' . $this->key( $slug );
			delete_transient( '_super_api_transient_cb' );

			update_option( $option, array( 'body' => 'young-' . $slug, 'time' => time() - 7 * DAY_IN_SECONDS + MINUTE_IN_SECONDS, 'version' => SUPER_VERSION ), 'no' );
			$this->assertSame( 'young-' . $slug, $this->call( $slug ), 'A copy younger than 7 days must be served.' );

			delete_transient( '_super_api_transient_cb' );
			update_option( $option, array( 'body' => 'old-' . $slug, 'time' => time() - 7 * DAY_IN_SECONDS - MINUTE_IN_SECONDS, 'version' => SUPER_VERSION ), 'no' );
			$output = $this->call( $slug );
			$this->assertSame( $this->fallback_for( $slug ), $output, 'A copy older than 7 days must not be served for ' . $slug );
			$this->assert_no_error_text( $output, 'error text leaked for ' . $slug );
		}
	}

	/**
	 * (5) single-flight: a held lock serves last-known-good without a request; a stale lock is taken over
	 */
	public function test_lock_held_by_another_request_serves_last_known_good_and_a_stale_lock_is_taken_over() {
		$this->require_cache_enabled();
		$slug = 'before_do_shortcode_admin';
		$lock = '_super_api_transient_lock_' . $this->key( $slug );
		$this->respond_with_body( 'fresh-from-api' );

		// Another request holds a young lock: nothing cached -> '' and no request, lock untouched.
		$this->assertTrue( add_option( $lock, time(), '', 'no' ) );
		$this->assertSame( '', $this->call( $slug ) );
		$this->assertCount( 0, $this->requests );
		$this->assertNotFalse( get_option( $lock ), 'A request that does not own the lock must not release it.' );

		// Same with a last-known-good copy: it is served.
		update_option( '_super_api_transient_last_' . $this->key( $slug ), array( 'body' => 'known-good', 'time' => time(), 'version' => SUPER_VERSION ), 'no' );
		$this->assertSame( 'known-good', $this->call( $slug ) );
		$this->assertCount( 0, $this->requests );

		// The builder slug with nothing cached gets its alert while another request refreshes.
		$builder_lock = '_super_api_transient_lock_' . $this->key( 'super-forms_page_super_create_form' );
		$this->assertTrue( add_option( $builder_lock, time(), '', 'no' ) );
		$this->assertSame( self::ALERT, $this->call( 'super-forms_page_super_create_form' ) );
		$this->assertCount( 0, $this->requests );

		// A lock older than 30 seconds is stale: it is taken over, the request is made, the lock released.
		update_option( $lock, time() - 31 );
		$this->assertSame( 'fresh-from-api', $this->call( $slug ) );
		$this->assertCount( 1, $this->requests );
		$this->assertFalse( get_option( $lock ), 'The taken-over lock must be released.' );
	}

	private function lock_row( $lock ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $lock ) );
	}

	/**
	 * (5b) The lock is atomic even when the options cache says the lock does not exist.
	 *
	 * This is the race add_option() lost: a second request whose get_option() pre-check
	 * misses (another request inserted the row a moment ago, or a stale notoptions cache)
	 * ran INSERT ... ON DUPLICATE KEY UPDATE, which reports success when time() differs,
	 * so both requests believed they held the lock.
	 */
	public function test_lock_is_not_granted_twice_when_the_options_cache_misses_the_row() {
		global $wpdb;
		$this->require_cache_enabled();
		$slug = 'before_do_shortcode';
		$lock = '_super_api_transient_lock_' . $this->key( $slug );
		$this->respond_with_body( 'fresh-from-api' );

		$held = ( time() - 5 ) . '.123456';
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $lock, $held ) );
		wp_cache_delete( $lock, 'options' );
		$notoptions          = (array) wp_cache_get( 'notoptions', 'options' );
		$notoptions[ $lock ] = true;
		wp_cache_set( 'notoptions', $notoptions, 'options' );
		$this->assertFalse( get_option( $lock ), 'Precondition: the options cache misses the lock row.' );

		$this->assertSame( '', $this->call( $slug ) );
		$this->assertCount( 0, $this->requests, 'A second request must not refresh while another holds the lock.' );
		$this->assertSame( $held, $this->lock_row( $lock ), 'The holder\'s lock row must not be overwritten.' );
	}

	/**
	 * (5c) A request only releases its own lock: if another request took the lock over
	 * while this one was waiting for the API, that lock stays.
	 */
	public function test_request_releases_only_its_own_lock() {
		global $wpdb;
		$this->require_cache_enabled();
		$slug  = 'before_do_shortcode';
		$lock  = '_super_api_transient_lock_' . $this->key( $slug );
		$other = time() . '.999999';
		$json  = $this->api_json( 200, 'fresh-from-api' );

		$this->responder = function () use ( $json, $lock, $other, $wpdb ) {
			$this->assertNotNull( $this->lock_row( $lock ), 'The lock is held during the API request.' );
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s", $other, $lock ) );
			return $this->http_response( 200, $json );
		};

		$this->assertSame( 'fresh-from-api', $this->call( $slug ) );
		$this->assertCount( 1, $this->requests );
		$this->assertSame( $other, $this->lock_row( $lock ), 'Another request\'s lock must not be released.' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", $lock ) );
	}

	public function filter_home_to_request_host( $url ) {
		return preg_replace( '#^https?://[^/]+#', 'https://' . $this->request_host, $url );
	}

	public function filter_home_option_to_request_host() {
		return 'https://' . $this->request_host;
	}

	private $request_host = '';

	/**
	 * (9) A home URL that follows the Host header (WP_HOME built from $_SERVER['HTTP_HOST'])
	 * does not create new rows per host: the key uses the stored home option, so the cache
	 * stays bounded to the fixed licence slugs. The request body sends the same stored home.
	 */
	public function test_home_url_that_varies_per_request_does_not_create_new_cache_rows() {
		$this->require_cache_enabled();
		if ( wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'Transient rows are not stored in the options table with an external object cache.' );
		}
		$this->add_tracked_filter( 'home_url', array( $this, 'filter_home_to_request_host' ), 99 );
		$this->add_tracked_filter( 'option_home', array( $this, 'filter_home_option_to_request_host' ), 99 );
		$this->respond_with_body( 'one-answer' );

		$this->request_host = 'host-a.example';
		$this->assertSame( 'one-answer', $this->call( 'before_do_shortcode' ) );
		$this->assertCount( 1, $this->requests );
		$body = json_decode( $this->requests[0]['args']['body'], true );
		$this->assertSame( $this->stored_home(), $body['home_url'], 'The request sends the stored home, the value the cache key uses.' );
		$rows = $this->cache_rows();

		foreach ( array( 'host-b.example', 'host-c.example', 'evil.example:8080' ) as $host ) {
			$this->request_host = $host;
			$this->assertSame( 'one-answer', $this->call( 'before_do_shortcode' ), 'Served from the same cache for ' . $host );
		}
		$this->assertCount( 1, $this->requests, 'Another Host header must not miss the cache.' );
		$this->assertSame( $rows, $this->cache_rows(), 'Another Host header must not add option rows.' );
		$this->assertSame( 'one-answer', $this->fresh_body( 'before_do_shortcode' ) );
	}

	/**
	 * (9a) The identity the API is asked about is the one the answer is cached under.
	 * With a home_url / option_home filter that returns another (staging-pattern or
	 * unlicensed) domain on every request, each request still sends the stored home, so
	 * the API never decides on the Host's domain and no other domain's answer is cached
	 * for the site (fresh transient and last-known-good).
	 */
	public function test_home_url_filter_never_changes_the_requested_identity_or_the_cached_answer() {
		$this->require_cache_enabled();
		$this->add_tracked_filter( 'home_url', array( $this, 'filter_home_to_request_host' ), 99 );
		$this->add_tracked_filter( 'option_home', array( $this, 'filter_home_option_to_request_host' ), 99 );
		$stored          = $this->stored_home();
		$this->responder = function ( $args ) use ( $stored ) {
			// Mimic the API: the answer depends on the home_url it is asked about.
			$sent = json_decode( $args['body'], true );
			$body = ( $sent['home_url'] === $stored ) ? 'answer-for-stored-home' : 'answer-for-' . $sent['home_url'];
			return $this->http_response( 200, $this->api_json( 200, $body ) );
		};

		foreach ( array( 'staging.example.com', 'dev.example.test', 'unlicensed.example', 'evil.example:8080' ) as $i => $host ) {
			$this->request_host = $host;
			$this->assertSame( 'https://' . $host, get_home_url(), 'The filter is active for ' . $host );
			SUPER_Common::flush_api_transients();
			foreach ( self::slug_provider() as $row ) {
				$this->assertSame( 'answer-for-stored-home', $this->call( $row[0] ), 'Answer for the stored home with Host ' . $host );
				$this->assertSame( 'answer-for-stored-home', $this->fresh_body( $row[0] ) );
				$last = get_option( '_super_api_transient_last_' . $this->key( $row[0] ) );
				$this->assertSame( 'answer-for-stored-home', $last['body'] );
			}
		}
		$this->assertCount( 12, $this->requests, 'One request per slug and host after each flush.' );
		foreach ( $this->requests as $request ) {
			$sent = json_decode( $request['args']['body'], true );
			$this->assertSame( $stored, $sent['home_url'], 'The request must send the stored home, never the filtered one.' );
			$this->assertSame( array( 'slug', 'home_url', 'admin_url', 'version' ), array_keys( $sent ) );
		}
	}

	/**
	 * (9b) Only the three licence slugs are cached; any other slug is fetched every time
	 * and leaves no rows behind, so the number of cache rows has a fixed upper bound.
	 */
	public function test_other_slugs_are_never_stored() {
		$this->require_cache_enabled();
		if ( wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'Transient rows are not stored in the options table with an external object cache.' );
		}
		$this->respond_with_body( 'other' );
		$before = $this->cache_rows();
		$this->assertSame( 'other', $this->call( 'pdf' ) );
		$this->assertSame( 'other', $this->call( 'pdf' ) );
		$this->assertSame( 'other', $this->call( 'random-' . wp_generate_password( 8, false ) ) );
		$this->assertCount( 3, $this->requests, 'Other slugs are not cached.' );
		$this->assertSame( $before, $this->cache_rows(), 'Other slugs must not create option rows.' );

		$this->respond_with_failure( 'wp_error' );
		$output = $this->call( 'pdf' );
		$this->assertSame( self::ALERT, $output, 'Unchanged fallback for other slugs.' );
		$this->assert_no_error_text( $output, 'error text leaked for an other slug' );
		$this->assertFalse( get_transient( '_super_api_transient_cb' ), 'Other slugs do not use the breaker.' );

		foreach ( self::$slugs as $slug ) {
			$this->respond_with_body( 'licence-' . $slug );
			$this->call( $slug );
		}
		$this->assertLessThanOrEqual( 2 * 3 + 3, count( $this->cache_rows() ), 'At most a fresh transient (+ timeout) and a last-known-good row per licence slug.' );
	}

	/**
	 * (10) A copy cached by another plugin version is not served after an upgrade:
	 * the fresh transient is refreshed, and an old last-known-good is not a fallback.
	 */
	public function test_copies_cached_by_another_version_are_not_served() {
		$this->require_cache_enabled();
		$slug = 'before_do_shortcode_admin';
		$key  = $this->key( $slug );
		set_transient( '_super_api_transient_' . $key, array( 'body' => 'from-old-version', 'version' => '0.0.1' ), 900 );
		update_option( '_super_api_transient_last_' . $key, array( 'body' => 'old-known-good', 'time' => time(), 'version' => '0.0.1' ), 'no' );

		$this->respond_with_failure( 'wp_error' );
		$this->assertSame( '', $this->call( $slug ), 'Neither copy of the old version may be served.' );
		$this->assertCount( 1, $this->requests, 'The old fresh copy is a miss.' );

		delete_transient( '_super_api_transient_cb' );
		$this->respond_with_body( 'from-this-version' );
		$this->assertSame( 'from-this-version', $this->call( $slug ) );
		$this->assertSame( 'from-this-version', $this->fresh_body( $slug ) );
		$last = get_option( '_super_api_transient_last_' . $key );
		$this->assertSame( array( 'from-this-version', SUPER_VERSION ), array( $last['body'], $last['version'] ), 'The refresh overwrites the old rows.' );

		// A bare string (the format before the version was recorded) is a miss as well.
		set_transient( '_super_api_transient_' . $key, 'bare-string', 900 );
		$this->respond_with_body( 'refetched' );
		$this->assertSame( 'refetched', $this->call( $slug ) );
	}

	/**
	 * (2/7) SUPER_API_TRANSIENT_TTL is honoured when defined; the default is 15 minutes
	 */
	public function test_fresh_transient_ttl_is_fifteen_minutes_by_default_or_the_defined_constant() {
		$this->require_cache_enabled();
		if ( wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'Transient timeouts are not observable with an external object cache.' );
		}
		$this->respond_with_body( 'ttl' );
		$this->assertSame( 'ttl', $this->call( 'before_do_shortcode' ) );
		$expected = defined( 'SUPER_API_TRANSIENT_TTL' ) ? (int) SUPER_API_TRANSIENT_TTL : 900;
		$timeout  = (int) get_option( '_transient_timeout__super_api_transient_' . $this->key( 'before_do_shortcode' ) );
		$this->assertGreaterThan( time() + $expected - 5, $timeout );
		$this->assertLessThanOrEqual( time() + $expected, $timeout );
	}

	/**
	 * (viii) SUPER_API_TRANSIENT_DISABLE_CACHE: always fetch, 3 second timeout, never any error text.
	 *
	 * Defines the constant for the remainder of the process; keep this the last test of the class.
	 */
	public function test_disable_cache_constant_always_fetches_and_never_prints_error_text() {
		if ( ! defined( 'SUPER_API_TRANSIENT_DISABLE_CACHE' ) ) {
			define( 'SUPER_API_TRANSIENT_DISABLE_CACHE', true );
		}
		if ( ! SUPER_API_TRANSIENT_DISABLE_CACHE ) {
			$this->markTestSkipped( 'SUPER_API_TRANSIENT_DISABLE_CACHE is defined false in this process.' );
		}

		$this->respond_with_body( 'live' );
		$this->assertSame( 'live', $this->call( 'before_do_shortcode' ) );
		$this->assertSame( 'live', $this->call( 'before_do_shortcode' ) );
		$this->assertCount( 2, $this->requests, 'Without the cache every call fetches.' );
		$this->assertSame( 3, $this->requests[1]['args']['timeout'] );
		$this->assertFalse( get_transient( '_super_api_transient_' . $this->key( 'before_do_shortcode' ) ), 'Nothing may be cached while the cache is disabled.' );
		$this->assertFalse( get_option( '_super_api_transient_last_' . $this->key( 'before_do_shortcode' ) ) );

		foreach ( array( 'wp_error', 'http_500', 'non_json', 'status_not_200', 'empty' ) as $kind ) {
			$this->respond_with_failure( $kind );
			foreach ( self::$slugs as $slug ) {
				$count  = count( $this->requests );
				$output = $this->call( $slug );
				$this->assertCount( $count + 1, $this->requests );
				$this->assertSame( $this->fallback_for( $slug ), $output, $kind . ' must yield the bare fallback for ' . $slug );
				$this->assert_no_error_text( $output, $kind . ' leaked into the page for ' . $slug );
			}
		}
		$this->assertFalse( get_transient( '_super_api_transient_cb' ), 'The breaker is not used while the cache is disabled.' );
	}
}
