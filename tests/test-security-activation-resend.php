<?php
require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Security_Activation_Resend extends Super_Forms_Upload_Security_Test_Case {
    protected function bootstrap_shared_anonymous_session() {
        if( get_current_user_id()!==0 ) {
            return;
        }
        if( isset( $_COOKIE['_sfs_id'] ) && is_string( $_COOKIE['_sfs_id'] ) && $_COOKIE['_sfs_id']!=='' ) {
            return;
        }
        // PHPUnit runs under the CLI SAPI, where headers_sent() is already true once the
        // runner emits progress output, so startClientSession() can never publish a fresh
        // Set-Cookie header: includes/class-common.php:610-612 returns false for a brand new
        // session and the $publish_session closure (includes/class-common.php:559-577) refuses
        // as well. Seed the exact cookie/option pair a real first response persists, then
        // require the hardened adoption path to accept it unchanged without re-issuing.
        $session_id = bin2hex( random_bytes( 32 ) );
        $now = time();
        // The third record matters: SUPER_Common::setClientData() DELETES a session row
        // that drops below three keys (includes/class-common.php:649-657), and the CLI SAPI
        // can never publish a replacement cookie. Any `value => false` client-data write
        // would otherwise destroy the whole session - including the process-wide shutdown
        // handler SUPER_Register_Login arms once per process
        // (add-ons/super-forms-register-login/super-forms-register-login.php:1148-1153),
        // which every forked child inherits and runs on exit. A live browser session always
        // carries at least one unrelated record; the other seeding helpers do the same
        // (tests/test-security-listings.php:43-51, tests/test-security-proof-row1-retained.php:53-56).
        update_option( '_sfsdata_' . $session_id, array(
            'expires' => $now + HOUR_IN_SECONDS,
            'exp_var' => $now + ( 20 * MINUTE_IN_SECONDS ),
            'session_marker' => array(
                'expires' => $now + HOUR_IN_SECONDS,
                'exp_var' => $now + ( 20 * MINUTE_IN_SECONDS ),
                'value' => 'seeded-session-marker',
            ),
        ), 'no' );
        $_COOKIE['_sfs_id'] = $session_id;
        $adopted = SUPER_Common::startClientSession( array( 'force' => true ) );
        $this->assertTrue( is_string( $adopted ) && $adopted!=='' );
        $this->assertSame( $session_id, $adopted );
        $this->assertSame( $session_id, $_COOKIE['_sfs_id'] );
    }



    private function invoke( $name, ...$args ) {
        $method = new ReflectionMethod('SUPER_Register_Login', $name);
        $method->setAccessible(true);
        return $method->invoke(null, ...$args);
    }

    private function pending_account() {
        if( !class_exists('SUPER_Register_Login') ) {
            require_once SUPER_PLUGIN_DIR . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
        $id = self::factory()->user->create(array('role'=>'subscriber'));
        update_user_meta($id, 'super_account_status', 0);
        update_user_meta($id, 'super_user_login_status', 'pending');
        update_user_meta($id, 'super_account_activation', 'verification-code');
        $settings = array(
            'register_login_action'=>'register', 'register_login_activation'=>'verify',
            'register_activation_subject'=>'Activate', 'register_activation_email'=>'Code: {register_activation_code}',
            'register_login_url'=>'https://example.test/activate',
            'header_from'=>'noreply@example.test', 'header_from_name'=>'Forms',
            'header_reply_enabled'=>'', 'header_reply'=>'', 'header_reply_name'=>'',
        );
        $form = $this->create_form('publish', array(), $settings);
        return array(get_userdata($id), $form, SUPER_Common::get_form_settings($form));
    }

    public function test_registration_retry_obeys_both_existing_resend_limits() {
        foreach( array('minute', 'daily') as $limit ) {
            list($user, $form, $settings) = $this->pending_account();
            $this->bootstrap_shared_anonymous_session();
            $this->assertTrue($this->invoke('issue_pending_registration_recovery', $user->ID, $form, $user->user_login, $user->user_email));
            $key = $limit==='minute'
                ? $this->invoke('resend_activation_rate_limit_key', $user->ID, $user->user_email)
                : $this->invoke('resend_activation_daily_limit_key', $user->ID);
            set_transient($key, 5, HOUR_IN_SECONDS);
            $capture = tempnam(sys_get_temp_dir(), 'sf-resend-limit-');
            $filter = static function($message) use ($capture) {
                file_put_contents($capture, 'mail', FILE_APPEND);
                throw new RuntimeException('Unexpected verification email reached delivery');
            };
            add_filter('super_before_sending_verification_email_body_filter', $filter);
            try {
                $result = $this->run_dying_handler(function() use ($user, $form, $settings) {
                    $this->invoke('maybe_resume_pending_registration', $user, $form, $settings, array());
                });
                $this->assertSame('', file_get_contents($capture), $limit . ' limit must cover registration retries');
                $this->assertSame(0, $result['status'], $result['output']);
                $body = json_decode($result['output'], true);
                $this->assertIsArray($body, $result['output']);
                $this->assertFalse($body['error']);
                $this->assertSame('', file_get_contents($capture), $limit . ' limit must cover registration retries');
                $this->assertSame(5, (int)get_transient($key));
            } finally {
                remove_filter('super_before_sending_verification_email_body_filter', $filter);
                unlink($capture);
                delete_transient($key);
            }
        }
    }

    public function test_resend_does_not_send_while_another_connection_holds_account_lock() {
        global $wpdb;
        list($user, $form, $settings) = $this->pending_account();
        $other = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
        $name = 'sf-registration-' . sha1($wpdb->prefix . ':' . $user->ID);
        $this->assertSame('1', (string)$other->get_var($other->prepare('SELECT GET_LOCK(%s, 0)', $name)));
        $sent = 0;
        $filter = static function($message) use (&$sent) { ++$sent; throw new RuntimeException('Unexpected verification email reached delivery'); };
        add_filter('super_before_sending_verification_email_body_filter', $filter);
        try {
            try {
                $this->invoke('maybe_resend_pending_activation_email', $user, $form, $settings);
            } catch( RuntimeException $e ) {
                $this->assertSame('Unexpected verification email reached delivery', $e->getMessage());
            }
            $this->assertSame(0, $sent, 'Contending resend must leave delivery to the lock owner.');
            $this->assertFalse(get_transient($this->invoke('resend_activation_rate_limit_key', $user->ID, $user->user_email)));
        } finally {
            remove_filter('super_before_sending_verification_email_body_filter', $filter);
            $other->get_var($other->prepare('SELECT RELEASE_LOCK(%s)', $name));
            $other->close();
        }
    }

    public function test_successful_resends_hold_lock_and_share_minute_and_daily_limits() {
        global $wpdb;
        list($user, $form, $settings) = $this->pending_account();
        $this->bootstrap_shared_anonymous_session();
        $rate = $this->invoke('resend_activation_rate_limit_key', $user->ID, $user->user_email);
        $daily = $this->invoke('resend_activation_daily_limit_key', $user->ID);
        $name = 'sf-registration-' . sha1($wpdb->prefix . ':' . $user->ID);
        $sent = 0;
        $filter = function($result, $atts) use (&$sent, $wpdb, $name, $user, $form, $settings) {
            ++$sent;
            $this->assertSame((string)$wpdb->get_var('SELECT CONNECTION_ID()'), (string)$wpdb->get_var($wpdb->prepare('SELECT IS_USED_LOCK(%s)', $name)));
            $this->assertStringContainsString('verification-code', $atts['message']);
            // A hook may re-enter the sender on the same DB connection; no second delivery.
            $this->assertTrue($this->invoke('maybe_resend_pending_activation_email', $user, $form, $settings));
            return true;
        };
        add_filter('pre_wp_mail', $filter, 10, 2);
        try {
            for( $i=0; $i<6; ++$i ) {
                $this->assertTrue($this->invoke('maybe_resend_pending_activation_email', $user, $form, $settings));
                $this->assertTrue($this->invoke('maybe_resend_pending_activation_email', $user, $form, $settings));
                $this->assertSame(min($i+1, 5), $sent);
                delete_transient($rate);
            }
            $this->assertSame(5, (int)get_transient($daily));
            $this->assertSame('1', (string)$wpdb->get_var($wpdb->prepare('SELECT IS_FREE_LOCK(%s)', $name)));
        } finally {
            remove_filter('pre_wp_mail', $filter, 10);
            delete_transient($rate);
            delete_transient($daily);
        }
    }

    public function test_failed_delivery_releases_lock_and_keeps_attempt_limit() {
        global $wpdb;
        list($user, $form, $settings) = $this->pending_account();
        $name = 'sf-registration-' . sha1($wpdb->prefix . ':' . $user->ID);
        $filter = static function() { throw new RuntimeException('Simulated transport failure'); };
        add_filter('super_before_sending_verification_email_body_filter', $filter);
        try {
            try {
                $this->invoke('maybe_resend_pending_activation_email', $user, $form, $settings);
                $this->fail('Expected transport exception');
            } catch( RuntimeException $e ) {
                $this->assertSame('Simulated transport failure', $e->getMessage());
            }
            $this->assertSame('1', (string)$wpdb->get_var($wpdb->prepare('SELECT IS_FREE_LOCK(%s)', $name)));
            $this->assertNotFalse(get_transient($this->invoke('resend_activation_rate_limit_key', $user->ID, $user->user_email)));
            $this->assertSame(1, (int)get_transient($this->invoke('resend_activation_daily_limit_key', $user->ID)));
        } finally {
            remove_filter('super_before_sending_verification_email_body_filter', $filter);
        }
    }
}
