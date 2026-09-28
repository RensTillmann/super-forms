<?php
require_once __DIR__ . '/test-security-upload-00-base.php';
class Test_Security_Sessionless_Public_Capability extends Super_Forms_Upload_Security_Test_Case {
    public static function modes_and_types() {
        return array(array('csrf_check', 'false', 'print'), array('allow_storing_cookies', '0', 'print'), array('csrf_check', 'false', 'populate'), array('allow_storing_cookies', '0', 'populate'));
    }

    /** @dataProvider modes_and_types */
    public function test_sessionless_capabilities_preserve_scope_without_cookies( $setting, $value, $type ) {
        $settings = array($setting=>$value);
        update_option('super_settings', $settings);
        SUPER_Forms()->global_settings = $settings;
        unset($_COOKIE['_sfs_id']);
        $scope = $type==='print' ? array('form_id'=>41, 'file_id'=>82) : array('form_id'=>41, 'field_name'=>'lookup', 'method'=>'equals', 'skip'=>'private', 'result_scope'=>'contact_entry');
        $issue = array('SUPER_Common', 'issue_public_' . $type . '_capability');
        $consume = array('SUPER_Common', 'consume_public_' . $type . '_capability');
        $token = call_user_func($issue, $scope);
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $token);
        $this->assertArrayNotHasKey('_sfs_id', $_COOKIE);
        $this->assertSame(array('version'=>1) + $scope, call_user_func($consume, $token, $scope));
        $this->assertFalse(call_user_func($consume, $token, $scope), 'Token must be single-use.');
        foreach( array_keys($scope) as $key ) {
            $token = call_user_func($issue, $scope);
            $wrong = $scope;
            $wrong[$key] = is_int($wrong[$key]) ? $wrong[$key]+1 : $wrong[$key] . '-wrong';
            $this->assertFalse(call_user_func($consume, $token, $wrong), 'Must bind ' . $key);
        }
        $token = call_user_func($issue, $scope);
        $user = self::factory()->user->create();
        wp_set_current_user($user);
        $this->assertFalse(call_user_func($consume, $token, $scope), 'A changed login identity must not use the token.');
        wp_set_current_user(0);
        $this->assertSame(array('version'=>1) + $scope, call_user_func($consume, $token, $scope));
        $this->assertArrayNotHasKey('_sfs_id', $_COOKIE);
    }

    public static function storage_backends() { return array(array(false), array(true)); }

    /** @dataProvider storage_backends */
    public function test_expiry_and_stale_cached_replays_fail_closed( $external_cache ) {
        $previous_cache = wp_using_ext_object_cache();
        $settings = array('allow_storing_cookies'=>'0');
        update_option('super_settings', $settings);
        SUPER_Forms()->global_settings = $settings;
        unset($_COOKIE['_sfs_id']);
        wp_using_ext_object_cache($external_cache);
        $scope = array('form_id'=>41, 'file_id'=>82);
        try {
            $token = SUPER_Common::issue_public_print_capability($scope);
            $this->assertIsString($token);
            $key = 'sf_public_' . hash('sha256', 'print_custom_html_' . hash('sha256', $token));
            $stored = get_transient($key);
            $this->assertIsArray($stored);
            $this->assertSame(array('version'=>1) + $scope, SUPER_Common::consume_public_print_capability($token, $scope));
            // Simulate a concurrent caller still seeing the pre-consumption cached record.
            if( $external_cache ) wp_cache_set($key, $stored, 'transient');
            else {
                wp_cache_delete('notoptions', 'options');
                wp_cache_set('_transient_' . $key, $stored, 'options');
            }
            $this->assertSame($stored, get_transient($key));
            $this->assertFalse(SUPER_Common::consume_public_print_capability($token, $scope));
            delete_transient($key);
            $token = SUPER_Common::issue_public_print_capability($scope);
            $key = 'sf_public_' . hash('sha256', 'print_custom_html_' . hash('sha256', $token));
            $stored = get_transient($key);
            $stored['expires'] = time() - 1;
            set_transient($key, $stored, 600);
            $this->assertFalse(SUPER_Common::consume_public_print_capability($token, $scope));
            $this->assertFalse(get_transient($key));
            $this->assertArrayNotHasKey('_sfs_id', $_COOKIE);
        } finally {
            wp_using_ext_object_cache($previous_cache);
        }
    }
}
