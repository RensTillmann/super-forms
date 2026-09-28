<?php
/** A submitted activation field requires the server to have rendered that field. */
class Test_Security_Activation_Render extends Super_Forms_Upload_Security_Test_Case {
    public static function set_up_before_class() {
        parent::set_up_before_class();
        if( !class_exists( 'SUPER_Register_Login' ) ) {
            require_once SUPER_PLUGIN_DIR . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
    }

    private function activation_form() {
        return $this->create_form( 'publish', array(
            array( 'tag' => 'verification_code', 'group' => 'form_elements', 'data' => array(
                'name' => 'activation_code',
            ) ),
        ) );
    }

    public function test_unrendered_activation_field_is_not_admitted_during_admin_ajax() {
        $form_id = $this->activation_form();
        unset( $_GET['code'] );
        $_POST['form_id'] = (string) $form_id;
        $result = $this->run_dying_handler( static function() {
            if( !defined('DOING_AJAX') ) define('DOING_AJAX', true);
            if( !defined('WP_ADMIN') ) define('WP_ADMIN', true);
            $contracts = SUPER_Register_Login::instance()->submission_carrier_contracts(
                array(), array( 'tag' => 'activation_code' )
            );
            echo wp_json_encode($contracts);
        } );
        $this->assertSame( 97, $result['status'], $result['output'] );
        $this->assertSame( array(), json_decode($result['output'], true) );
    }

    public function test_rendered_activation_field_grants_real_stored_tag_during_ajax() {
        $form_id = $this->activation_form();
        $this->seed_browser_session();
        $_POST['form_id'] = (string) $form_id;
        $_GET['code'] = 'rendered-code';
        $result = $this->run_dying_handler( static function() use ( $form_id ) {
            ob_start();
            $html = SUPER_Shortcodes::super_form_func( array( 'id' => $form_id ) );
            ob_end_clean();
            unset( $_GET['code'] );
            if( !defined('DOING_AJAX') ) define('DOING_AJAX', true);
            if( !defined('WP_ADMIN') ) define('WP_ADMIN', true);
            $contracts = SUPER_Register_Login::instance()->submission_carrier_contracts(
                array(), array( 'tag' => 'verification_code' )
            );
            echo wp_json_encode( array(
                'rendered' => is_string($html) && strpos($html, 'name="activation_code"')!==false,
                'contract' => isset($contracts['activation_code']),
            ) );
        } );
        unset( $_GET['code'] );
        $this->assertSame( 97, $result['status'], $result['output'] );
        $this->assertSame(
            array( 'rendered' => true, 'contract' => true ),
            json_decode($result['output'], true)
        );
    }
    public function test_rendered_activation_value_survives_real_ajax_submission_checks() {
        $this->add_upload_filter(
            'super_submission_carrier_contracts_filter',
            array( SUPER_Register_Login::instance(), 'submission_carrier_contracts' ),
            10, 2
        );
        $form_id = $this->activation_form();
        $this->seed_browser_session();
        $this->set_request( $form_id, array(
            'activation_code' => array(
                'name' => 'activation_code', 'type' => 'var',
                'value' => 'rendered-code',
            ),
        ) );
        $_GET['code'] = 'rendered-code';
        $result = $this->run_dying_handler( static function() use ( $form_id ) {
            ob_start();
            $html = SUPER_Shortcodes::super_form_func( array( 'id' => $form_id ) );
            ob_end_clean();
            unset( $_GET['code'] );
            if( !defined('DOING_AJAX') ) define('DOING_AJAX', true);
            if( !defined('WP_ADMIN') ) define('WP_ADMIN', true);
            $atts = SUPER_Ajax::submit_form_checks( false );
            echo wp_json_encode( array(
                'rendered' => is_string($html) && strpos($html, 'name="activation_code"')!==false,
                'value' => $atts['data']['activation_code']['value'],
            ) );
        } );
        unset( $_GET['code'] );
        $this->assertSame( 97, $result['status'], $result['output'] );
        $this->assertSame(
            array( 'rendered' => true, 'value' => 'rendered-code' ),
            json_decode($result['output'], true)
        );
    }

}
