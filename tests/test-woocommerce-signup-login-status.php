<?php
/** Completed orders use their saved signup status rather than unavailable form settings. */
class Test_Super_Forms_WooCommerce_Signup_Login_Status extends WP_UnitTestCase {
    public function set_up() {
        parent::set_up();
        if( !class_exists('SUPER_WooCommerce') ) {
            require_once dirname(__DIR__) . '/src/add-ons/super-forms-woocommerce/super-forms-woocommerce.php';
        }
    }

    private function fixture($status, $role='') {
        $user_id = self::factory()->user->create(array('role' => 'subscriber'));
        update_user_meta($user_id, 'super_user_login_status', 'pending');
        $order_id = self::factory()->post->create(array('post_type' => 'shop_order'));
        update_post_meta($order_id, '_super_wc_signup', array(
            'user_id' => $user_id,
            'status' => $status,
            'role' => $role,
        ));
        return array($order_id, $user_id);
    }

    private function change_status($order_id, $new_status='completed') {
        // Treat notices and warnings on the actual handler path as test failures.
        set_error_handler(static function($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE);
        try {
            SUPER_WooCommerce()->order_status_changed($order_id, 'pending', $new_status);
        } finally {
            restore_error_handler();
        }
    }

    public function test_completed_order_uses_its_saved_mapping_and_preserves_role_update() {
        list($order_id, $user_id) = $this->fixture(array(
            array('order' => 'pending', 'login_status' => 'pending'),
            array('order' => 'completed', 'login_status' => 'active'),
            array('order' => 'failed', 'login_status' => 'blocked'),
        ), 'editor');
        // No entry data or form lookup is needed to honour the signup snapshot.
        $this->change_status($order_id);
        $this->assertSame('active', get_user_meta($user_id, 'super_user_login_status', true));
        $this->assertSame(array('editor'), get_userdata($user_id)->roles);
    }

    public function test_legacy_scalar_statuses_remain_readable() {
        foreach(array('active', 'pending', 'blocked') as $status) {
            list($order_id, $user_id) = $this->fixture($status);
            $this->change_status($order_id);
            $this->assertSame($status, get_user_meta($user_id, 'super_user_login_status', true));
        }
    }

    public function test_missing_or_unusable_status_never_clears_existing_login_status() {
        foreach(array(
            '', null, false, 17,
            array(),
            array(array('order' => 'failed', 'login_status' => 'blocked')),
            array('bad-row', array('order' => 'completed')),
            array(array('order' => 'completed', 'login_status' => array('active'))),
        ) as $status) {
            list($order_id, $user_id) = $this->fixture($status);
            $this->change_status($order_id);
            $this->assertSame('pending', get_user_meta($user_id, 'super_user_login_status', true));
        }
        list($order_id, $user_id) = $this->fixture('active');
        update_post_meta($order_id, '_super_wc_signup', array('user_id' => $user_id));
        $this->change_status($order_id);
        $this->assertSame('pending', get_user_meta($user_id, 'super_user_login_status', true));
    }

    public function test_non_completed_order_does_not_apply_completed_status_or_role() {
        list($order_id, $user_id) = $this->fixture('active', 'editor');
        $this->change_status($order_id, 'processing');
        $this->assertSame('pending', get_user_meta($user_id, 'super_user_login_status', true));
        $this->assertSame(array('subscriber'), get_userdata($user_id)->roles);
    }

    public function test_order_without_signup_data_does_not_change_user() {
        list($order_id, $user_id) = $this->fixture('active');
        delete_post_meta($order_id, '_super_wc_signup');
        $this->change_status($order_id);
        $this->assertSame('pending', get_user_meta($user_id, 'super_user_login_status', true));
    }

    public function test_completed_entry_status_tags_receive_loaded_form_settings() {
        list($order_id, $user_id) = $this->fixture(array(
            array('order' => 'completed', 'login_status' => 'blocked'),
        ));
        $form_id = self::factory()->post->create(array('post_type' => 'super_form'));
        $entry_id = self::factory()->post->create(array('post_type' => 'super_contact_entry'));
        update_post_meta($form_id, '_super_version', '6.4.010');
        update_post_meta($form_id, '_super_form_settings', array('_woocommerce' => array(
            'entry_status' => array(array('order' => 'completed', 'entry' => '{completion_status}')),
        )));
        update_post_meta($order_id, '_super_entry_id', $entry_id);
        update_post_meta($order_id, '_super_wc_entry_data', array(
            'hidden_form_id' => array('name' => 'hidden_form_id', 'value' => (string) $form_id, 'type' => 'var'),
            'completion_status' => array('name' => 'completion_status', 'value' => 'paid', 'type' => 'var'),
        ));
        $this->change_status($order_id);
        $this->assertSame('blocked', get_user_meta($user_id, 'super_user_login_status', true));
        $this->assertSame('paid', get_post_meta($entry_id, '_super_contact_entry_status', true));
    }
}
