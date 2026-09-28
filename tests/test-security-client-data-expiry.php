<?php

class Test_Super_Forms_Client_Data_Expiry extends Super_Forms_Upload_Security_Test_Case {
    private function expiry_record() {
        return array('expires' => time() + 120, 'exp_var' => time() - 1, 'value' => 'submission');
    }

    private function install_expiry_filters() {
        $this->add_upload_filter('super_client_data_unique_submission_id_expires_filter', function () { return 900; });
        $this->add_upload_filter('super_client_data_unique_submission_id_exp_var_filter', function () { return 800; });
        $this->add_upload_filter('super_client_data_unique_submission_id_123_expires_filter', function () { return 300; });
        $this->add_upload_filter('super_client_data_unique_submission_id_123_exp_var_filter', function () { return 200; });
    }

    public function test_creation_retains_generic_submission_expiry_filters() {
        $this->install_expiry_filters();
        $before = time();
        SUPER_Common::setClientData(array('name' => 'unique_submission_id_123', 'value' => 'submission'));
        $data = get_option('_sfsdata_' . $_COOKIE['_sfs_id']);
        $this->assertGreaterThanOrEqual($before + 900, $data['unique_submission_id_123']['expires']);
        $this->assertLessThanOrEqual(time() + 900, $data['unique_submission_id_123']['expires']);
        $this->assertGreaterThanOrEqual($before + 800, $data['unique_submission_id_123']['exp_var']);
    }

    public function test_read_refresh_preserves_per_form_expiry_filters() {
        $this->assert_refresh_preserves_per_form_filters(false);
    }

    public function test_cleanup_refresh_preserves_per_form_expiry_filters() {
        $this->assert_refresh_preserves_per_form_filters(true);
    }

    private function assert_refresh_preserves_per_form_filters($cleanup) {
        $this->install_expiry_filters();
        $key = $_COOKIE['_sfs_id'];
        $data = get_option('_sfsdata_' . $key);
        $data['unique_submission_id_123'] = $this->expiry_record();
        update_option('_sfsdata_' . $key, $data, false);
        $before = time();
        if ($cleanup) {
            SUPER_Common::cleanupOldClientData($key, $data);
        } else {
            $this->assertSame('submission', SUPER_Common::getClientData('unique_submission_id_123'));
        }
        $stored = get_option('_sfsdata_' . $key);
        $record = $stored['unique_submission_id_123'];
        $this->assertGreaterThanOrEqual($before + 300, $record['expires']);
        $this->assertLessThanOrEqual(time() + 300, $record['expires']);
        $this->assertGreaterThanOrEqual($before + 200, $record['exp_var']);
        $this->assertLessThanOrEqual(time() + 200, $record['exp_var']);
    }
}
