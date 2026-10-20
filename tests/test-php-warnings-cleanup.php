<?php
/**
 * Submission/settings code paths must not raise PHP warnings or deprecations.
 */

class SUPER_PHP_Warnings_Cleanup_Test extends WP_UnitTestCase {

    /** @var array<int,string> */
    private $raised = array();

    private function collect_errors(callable $fn) {
        $this->raised = array();
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            $this->raised[] = $errstr . ' (' . basename((string) $errfile) . ':' . $errline . ')';
            return true;
        });
        try {
            return $fn();
        } finally {
            restore_error_handler();
        }
    }

    public function test_conditionally_wc_checkout_without_woocommerce_settings_raises_nothing() {
        $result = $this->collect_errors(function () {
            return SUPER_Common::conditionally_wc_checkout(array(), array('save_contact_entry' => 'no'));
        });
        $this->assertSame(array(), $this->raised);
        $this->assertFalse($result);
    }

    public function test_conditionally_wc_checkout_without_checkout_key_raises_nothing() {
        $result = $this->collect_errors(function () {
            return SUPER_Common::conditionally_wc_checkout(array(), array('_woocommerce' => array()));
        });
        $this->assertSame(array(), $this->raised);
        $this->assertFalse($result);
    }

    public function test_conditionally_wc_checkout_still_checks_out_when_enabled() {
        $settings = array('_woocommerce' => array('checkout' => 'true', 'checkout_conditionally' => array('enabled' => 'false')));
        $result = $this->collect_errors(function () use ($settings) {
            return SUPER_Common::conditionally_wc_checkout(array(), $settings);
        });
        $this->assertSame(array(), $this->raised);
        $this->assertTrue($result);
    }

    public function test_files_setting_without_stored_value_raises_no_deprecation() {
        if (!class_exists('SUPER_UI')) require_once SUPER_PLUGIN_DIR . '/includes/class-ui.php';
        $html = $this->collect_errors(function () {
            ob_start();
            SUPER_UI::print_field(array(), array('name' => 'attachments', 'type' => 'files'), array());
            return ob_get_clean();
        });
        $this->assertSame(array(), $this->raised);
        $this->assertStringContainsString('class="file-preview"', $html);
    }
}
