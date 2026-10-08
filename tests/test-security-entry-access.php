<?php
/**
 * Security regressions for anonymous post-submission entry access.
 *
 * @package Super_Forms_Tests
 */

class Test_Security_Entry_Access_Common extends SUPER_Common {

	public static $cookie_calls = array();
	public static $cookie_result = true;
	public static $delete_result = null;

	protected static function set_entry_access_cookie( $name, $value, $options ) {
		self::$cookie_calls[] = array(
			'name' => $name,
			'value' => $value,
			'options' => $options,
		);
		return self::$cookie_result;
	}

	protected static function delete_entry_access_transient( $transient_key ) {
		if( self::$delete_result!==null ) return self::$delete_result;
		return parent::delete_entry_access_transient($transient_key);
	}
}

class Test_Security_Entry_Access extends WP_UnitTestCase {

	private $browser_session_id;
	private $browser_session_ids = array();
	private $entry_a;
	private $entry_b;
	private $form_id;
	private $other_form_id;
	private $tokens = array();
	private $original_get;

	public function set_up() {
		parent::set_up();
		$this->original_get = $_GET;
		$_GET = array();
		wp_set_current_user( 0 );
		$_COOKIE = array();
		Test_Security_Entry_Access_Common::$cookie_calls = array();
		Test_Security_Entry_Access_Common::$cookie_result = true;
		Test_Security_Entry_Access_Common::$delete_result = null;
		$this->browser_session_id = $this->use_browser_session('default');

		$this->form_id = self::factory()->post->create(
			array(
				'post_type' => 'super_form',
				'post_status' => 'publish',
			)
		);
		$this->other_form_id = self::factory()->post->create(
			array(
				'post_type' => 'super_form',
				'post_status' => 'publish',
			)
		);
		$this->entry_a = $this->create_entry( $this->form_id, 'Entry A' );
		$this->entry_b = $this->create_entry( $this->form_id, 'Entry B' );
		$element = array(
			'tag' => 'text',
			'group' => 'form_elements',
			'data' => array( 'name' => 'entry_secret' ),
			'inner' => array(),
		);
		update_post_meta( $this->form_id, '_super_elements', array( $element ) );
		update_post_meta( $this->other_form_id, '_super_elements', array( $element ) );
		update_post_meta(
			$this->entry_a,
			'_super_contact_entry_data',
			array( 'entry_secret' => array( 'value' => 'entry-a-' . wp_generate_uuid4() ) )
		);
		update_post_meta(
			$this->entry_b,
			'_super_contact_entry_data',
			array( 'entry_secret' => array( 'value' => 'entry-b-' . wp_generate_uuid4() ) )
		);
	}

	public function tear_down() {
		foreach( array_unique($this->tokens) as $token ) {
			delete_transient( '_super_form_entry_access_' . hash('sha256', $token) );
		}
		foreach( array_unique($this->browser_session_ids) as $browser_session_id ) {
			delete_option('_sfsdata_' . $browser_session_id);
		}
		delete_transient( 'super_form_authenticated_entry_id_' . $this->entry_a );
		delete_transient( 'super_form_authenticated_entry_id_' . $this->entry_b );
		$_COOKIE = array();
		$_GET = $this->original_get;
		Test_Security_Entry_Access_Common::$cookie_calls = array();
		Test_Security_Entry_Access_Common::$cookie_result = true;
		Test_Security_Entry_Access_Common::$delete_result = null;
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	private function create_entry( $form_id, $title ) {
		return self::factory()->post->create(
			array(
				'post_type' => 'super_contact_entry',
				'post_status' => 'super_unread',
				'post_parent' => $form_id,
				'post_title' => $title,
			)
		);
	}

	private function use_browser_session( $label ) {
		$browser_session_id = substr(hash('sha256', $label . ':' . wp_generate_uuid4()), 0, 42);
		update_option(
			'_sfsdata_' . $browser_session_id,
			array(
				'expires' => time() + 3600,
				'exp_var' => time() + 1800,
			),
			false
		);
		$this->browser_session_ids[] = $browser_session_id;
		$_COOKIE['_sfs_id'] = $browser_session_id;
		return $browser_session_id;
	}

	private function use_logged_in_user( $user_id ) {
		$expiration = time() + 3600;
		$token = WP_Session_Tokens::get_instance($user_id)->create($expiration);
		$_COOKIE[LOGGED_IN_COOKIE] = wp_generate_auth_cookie($user_id, $expiration, 'logged_in', $token);
		wp_set_current_user($user_id);
		return $token;
	}

	private function issue( $entry_id ) {
		Test_Security_Entry_Access_Common::$cookie_calls = array();
		$this->assertTrue( Test_Security_Entry_Access_Common::issue_entry_access_credential(get_post($entry_id)) );
		$this->assertNotEmpty( Test_Security_Entry_Access_Common::$cookie_calls );
		$call = Test_Security_Entry_Access_Common::$cookie_calls[0];
		$this->tokens[] = $call['value'];
		return $call;
	}

	private function disclose_entry( $form_id, $entry_id ) {
		$_GET = array( 'contact_entry_id' => (string) $entry_id );
		return SUPER_Shortcodes::super_form_func( array( 'id' => (string) $form_id ) );
	}

	public function test_guessed_id_and_legacy_transient_do_not_authorize() {
		set_transient( 'super_form_authenticated_entry_id_' . $this->entry_a, $this->entry_a, 30 );

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertSame(
			$this->entry_a,
			get_transient('super_form_authenticated_entry_id_' . $this->entry_a)
		);

		$cookie_name = 'super_form_entry_access_' . $this->entry_a;
		$_COOKIE[$cookie_name] = str_repeat('a', 64);
		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertArrayNotHasKey( $cookie_name, $_COOKIE );
	}

	public function test_malformed_cookie_fails_and_is_removed() {
		$cookie_name = 'super_form_entry_access_' . $this->entry_a;
		$_COOKIE[$cookie_name] = 'not-a-valid-token';

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertArrayNotHasKey( $cookie_name, $_COOKIE );
	}

	public function test_public_entry_disclosure_requires_one_credential_for_one_exact_entry_and_form() {
		$entry_a_data = get_post_meta( $this->entry_a, '_super_contact_entry_data', true );
		$entry_b_data = get_post_meta( $this->entry_b, '_super_contact_entry_data', true );
		$entry_a_secret = $entry_a_data['entry_secret']['value'];
		$entry_b_secret = $entry_b_data['entry_secret']['value'];

		$call = $this->issue( $this->entry_a );
		$_COOKIE['super_form_entry_access_' . $this->entry_b] = $call['value'];
		$sibling_output = $this->disclose_entry( $this->form_id, $this->entry_b );
		$this->assertStringNotContainsString( $entry_b_secret, $sibling_output );
		$this->assertStringNotContainsString( $entry_a_secret, $sibling_output );

		$call = $this->issue( $this->entry_a );
		$_COOKIE[$call['name']] = $call['value'];
		$wrong_form_output = $this->disclose_entry( $this->other_form_id, $this->entry_a );
		$this->assertStringNotContainsString( $entry_a_secret, $wrong_form_output );

		$call = $this->issue( $this->entry_a );
		$_COOKIE[$call['name']] = $call['value'];
		$authorized_output = $this->disclose_entry( $this->form_id, $this->entry_a );
		$this->assertStringContainsString( $entry_a_secret, $authorized_output );
		$this->assertStringNotContainsString( $entry_b_secret, $authorized_output );

		$replay_output = $this->disclose_entry( $this->form_id, $this->entry_a );
		$this->assertStringNotContainsString( $entry_a_secret, $replay_output );
	}

	public function test_unrecognized_storage_payload_fails_closed() {
		$call = $this->issue( $this->entry_a );
		$transient_key = '_super_form_entry_access_' . hash('sha256', $call['value']);
		$payload = get_transient($transient_key);
		$payload['storage'] = 'custom_table';
		set_transient($transient_key, $payload, 30);
		$_COOKIE[$call['name']] = $call['value'];

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertFalse( get_transient($transient_key) );
	}

	public function test_browser_session_mismatch_fails_closed() {
		$call = $this->issue( $this->entry_a );
		$this->use_browser_session('other');
		$_COOKIE[$call['name']] = $call['value'];

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertFalse( get_transient('_super_form_entry_access_' . hash('sha256', $call['value'])) );
	}

	public function test_user_and_wordpress_session_mismatch_fail_closed_without_raw_session_storage() {
		$user_a = self::factory()->user->create(array('role'=>'subscriber'));
		$user_b = self::factory()->user->create(array('role'=>'subscriber'));
		$session_a = $this->use_logged_in_user($user_a);
		$call = $this->issue( $this->entry_a );
		$transient_key = '_super_form_entry_access_' . hash('sha256', $call['value']);
		$payload = get_transient($transient_key);

		$this->assertSame( $user_a, $payload['actor_id'] );
		$this->assertSame( hash('sha256', 'wordpress:' . $session_a), $payload['user_session_hash'] );
		$this->assertStringNotContainsString( $session_a, serialize($payload) );

		$this->use_logged_in_user($user_b);
		$_COOKIE[$call['name']] = $call['value'];
		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertFalse( get_transient($transient_key) );
	}

	public function test_failed_single_use_delete_cannot_disclose_entry() {
		$call = $this->issue( $this->entry_a );
		$transient_key = '_super_form_entry_access_' . hash('sha256', $call['value']);
		$_COOKIE[$call['name']] = $call['value'];
		Test_Security_Entry_Access_Common::$delete_result = false;

		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertNotFalse( get_transient($transient_key) );

		Test_Security_Entry_Access_Common::$delete_result = null;
		$_COOKIE[$call['name']] = $call['value'];
		$this->assertSame(
			$this->entry_a,
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)['entry_id']
		);
	}

	public function test_a_recent_nonce_of_the_same_session_stays_valid_after_a_newer_one_is_issued() {
		// Two tabs (or a tab's upload and another tab's nonce fetch) interleave: N1, then N2, then N1 is submitted.
		$session_id = $_COOKIE['_sfs_id'];
		$first = SUPER_Common::generate_nonce();
		$second = SUPER_Common::generate_nonce();
		$this->assertTrue( SUPER_Common::sf_nonce_is_valid( $second ) );
		$this->assertTrue( SUPER_Common::sf_nonce_is_valid( $first ), 'N1 is still valid after N2 was issued' );
		$this->assertSame( $second, get_option( '_sfsdata_' . $session_id )['sf_nonce']['value'], 'the current nonce record is unchanged' );
		// Unknown, empty and other-session values stay rejected.
		$this->assertFalse( SUPER_Common::sf_nonce_is_valid( str_repeat( 'a', 96 ) ) );
		$this->assertFalse( SUPER_Common::sf_nonce_is_valid( '' ) );
		$_COOKIE['_sfs_id'] = 'foreign' . $session_id;
		$this->assertFalse( SUPER_Common::sf_nonce_is_valid( $second ), 'a nonce is bound to its own session' );
		$this->assertFalse( SUPER_Common::sf_nonce_is_valid( $first ) );
		$_COOKIE['_sfs_id'] = $session_id;
		// Each recent nonce keeps its own 15-minute expiry.
		$stored = get_option( '_sfsdata_' . $session_id );
		$stored['sf_nonces']['value'][$first] = time() - 1;
		update_option( '_sfsdata_' . $session_id, $stored, false );
		$this->assertFalse( SUPER_Common::sf_nonce_is_valid( $first ), 'an expired recent nonce is rejected' );
		$this->assertTrue( SUPER_Common::sf_nonce_is_valid( $second ) );
		// Only a small window is kept: five newer nonces evict N2.
		for( $i = 0; $i < 5; $i++ ) $latest = SUPER_Common::generate_nonce();
		$this->assertFalse( SUPER_Common::sf_nonce_is_valid( $second ), 'outside the window of recent nonces' );
		$this->assertTrue( SUPER_Common::sf_nonce_is_valid( $latest ) );
	}

	public function test_refreshing_nonce_preserves_a_nonce_only_browser_session() {
		$session_id = $_COOKIE['_sfs_id'];
		update_option( '_sfsdata_' . $session_id, array(
			'expires' => time() + HOUR_IN_SECONDS,
			'exp_var' => time() + 20 * MINUTE_IN_SECONDS,
		), false );
		$first = SUPER_Common::generate_nonce();
		$second = SUPER_Common::generate_nonce();
		$this->assertNotSame( $first, $second );
		$this->assertSame( $session_id, $_COOKIE['_sfs_id'] );
		$this->assertSame( $second, get_option( '_sfsdata_' . $session_id )['sf_nonce']['value'] );
	}

	public function test_valid_browser_cookie_authorizes_exact_entry_once() {
		$before = time();
		$call = $this->issue( $this->entry_a );
		$token = $call['value'];
		$transient_key = '_super_form_entry_access_' . hash('sha256', $token);
		$payload = get_transient($transient_key);

		$this->assertSame( 'super_form_entry_access_' . $this->entry_a, $call['name'] );
		$this->assertGreaterThanOrEqual( $before + 30, $call['options']['expires'] );
		$this->assertLessThanOrEqual( time() + 30, $call['options']['expires'] );
		$this->assertSame( defined('COOKIEPATH') ? COOKIEPATH : '/', $call['options']['path'] );
		$this->assertSame( is_ssl(), $call['options']['secure'] );
		$this->assertTrue( $call['options']['httponly'] );
		$this->assertSame( 'Lax', $call['options']['samesite'] );
		$this->assertStringNotContainsString( $token, $call['name'] . serialize($call['options']) );
		$this->assertStringNotContainsString( $token, serialize($payload) );
		$this->assertStringNotContainsString( $this->browser_session_id, serialize($payload) );
		$this->assertSame(
			array(
				'version' => 1,
				'entry_id' => $this->entry_a,
				'form_id' => $this->form_id,
				'storage' => 'post_type',
				'actor_id' => 0,
				'browser_session_hash' => hash('sha256', 'browser:' . $this->browser_session_id),
				'user_session_hash' => '',
			),
			$payload
		);

		$_COOKIE[$call['name']] = $token;
		$this->assertSame(
			array(
				'entry_id' => $this->entry_a,
				'form_id' => $this->form_id,
				'storage' => 'post_type',
			),
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
		$this->assertFalse( get_transient($transient_key) );
		$this->assertArrayNotHasKey( $call['name'], $_COOKIE );

		$_COOKIE[$call['name']] = $token;
		$this->assertFalse(
			Test_Security_Entry_Access_Common::consume_entry_access_credential($this->entry_a, $this->form_id)
		);
	}

	public function test_cookie_names_are_per_entry() {
		$call_a = $this->issue( $this->entry_a );
		$call_b = $this->issue( $this->entry_b );

		$this->assertNotSame( $call_a['name'], $call_b['name'] );
		$this->assertNotSame( $call_a['value'], $call_b['value'] );
	}

	public function test_cookie_failure_removes_partial_transient_state() {
		Test_Security_Entry_Access_Common::$cookie_calls = array();
		Test_Security_Entry_Access_Common::$cookie_result = false;

		$this->assertFalse(
			Test_Security_Entry_Access_Common::issue_entry_access_credential(get_post($this->entry_a))
		);
		$this->assertCount( 2, Test_Security_Entry_Access_Common::$cookie_calls );
		$token = Test_Security_Entry_Access_Common::$cookie_calls[0]['value'];
		$this->tokens[] = $token;
		$this->assertFalse( get_transient('_super_form_entry_access_' . hash('sha256', $token)) );
		$this->assertSame( '', Test_Security_Entry_Access_Common::$cookie_calls[1]['value'] );
	}
}
