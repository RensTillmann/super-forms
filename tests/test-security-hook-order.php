<?php
/** Rejected submissions must not trigger integrations with external effects. */
class Test_Security_Hook_Order extends Super_Forms_Upload_Security_Test_Case {
    public static function duplicate_title_scope_cases() {
        return array(
            'same form' => array('form', false, false, true),
            'global other form' => array('global', false, false, true),
            'selected other form' => array('ids', false, false, true),
            'same form ignores trash' => array('form', true, false, false),
            'same form includes trash' => array('form', true, true, true),
            'global ignores trash' => array('global', true, false, false),
            'selected ignores trash' => array('ids', true, false, false),
            'selected includes trash' => array('ids', true, true, true),
        );
    }

    /** @dataProvider duplicate_title_scope_cases */
    public function test_duplicate_title_rejection_precedes_integration_hook($scope, $trashed, $include_trash, $reject) {
        $this->configure_csrf( 'false' );
        $other_form_id = $this->create_form('publish');
        $unrelated_form_id = $this->create_form('publish');
        $form_id = $this->create_form( 'publish', array(), array(
            'save_contact_entry' => 'yes',
            'enable_custom_entry_title' => 'true',
            'contact_entry_title' => 'Existing title',
            'contact_entry_unique_title' => 'true',
            'contact_entry_unique_title_compare' => $scope,
            'contact_entry_unique_title_form_ids' => $other_form_id . ', 0, ' . $unrelated_form_id,
            'contact_entry_unique_title_trashed' => $include_trash ? 'true' : 'false',
            'send' => 'no', 'confirm' => 'no',
            'contact_entry_unique_title_msg' => 'Duplicate entry',
        ) );
        self::factory()->post->create( array(
            'post_type' => 'super_contact_entry',
            'post_status' => $trashed ? 'trash' : 'super_unread',
            'post_parent' => $scope === 'form' ? $form_id : $other_form_id,
            'post_title' => 'Existing title',
        ) );
        $this->add_upload_filter( 'super_before_sending_email_hook', static function( $atts ) {
            echo 'SIDE_EFFECT_MARKER';
            return $atts;
        } );
        $this->set_request( $form_id );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertTrue( $result['exited'] );
        if ($reject) {
            $this->assertStringContainsString('Duplicate entry', $result['output']);
            $this->assertStringNotContainsString('SIDE_EFFECT_MARKER', $result['output']);
        } else {
            $this->assertStringNotContainsString('Duplicate entry', $result['output']);
            $this->assertStringContainsString('SIDE_EFFECT_MARKER', $result['output']);
        }
    }
    public function test_conditional_no_save_does_not_reject_a_duplicate_title() {
        global $wpdb;
        $this->configure_csrf('false');
        $form_id = $this->create_form('publish', array(), array(
            'save_contact_entry' => 'yes', 'send' => 'no', 'confirm' => 'no',
            'conditionally_save_entry' => 'true', 'conditionally_save_entry_check' => 'no,==,yes',
            'enable_custom_entry_title' => 'true', 'contact_entry_title' => 'Existing title',
            'contact_entry_unique_title' => 'true', 'contact_entry_unique_title_compare' => 'form',
            'contact_entry_unique_title_msg' => 'Duplicate entry',
        ));
        self::factory()->post->create(array('post_type' => 'super_contact_entry',
            'post_status' => 'super_unread', 'post_parent' => $form_id, 'post_title' => 'Existing title'));
        $marker = '_conditional_entry_hook_' . $form_id;
        $this->add_upload_filter('super_before_sending_email_hook', static function() use ($marker) {
            update_option($marker, 'reached', false);
        });
        $this->set_request($form_id);
        $result = $this->run_dying_handler(array('SUPER_Ajax', 'submit_form'));
        $this->assertSame(0, $result['status'], $result['output']);
        $response = json_decode($result['output'], true);
        $this->assertFalse($response['error'], $result['output']);
        $this->assertSame('reached', $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $marker)));
        $this->assertSame(1, (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_parent = %d", 'super_contact_entry', $form_id)));
        delete_option($marker);
    }

    public function test_integration_account_identity_survives_submission_record_updates() {
        $this->configure_csrf( 'false' );
        $account_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
        $form_id = $this->create_form( 'publish', array(), array(
            'save_contact_entry' => 'yes',
            'send' => 'no',
            'confirm' => 'no',
        ) );
        $this->add_upload_filter( 'super_before_sending_email_hook', static function( $atts ) use ( $account_id ) {
            $key = '_sfsi_' . $atts['sfs_uid'];
            $record = get_option( $key );
            $record['account_user_id'] = $account_id;
            update_option( $key, $record );
        } );
        $this->set_request( $form_id );
        $result = $this->run_dying_handler( array( 'SUPER_Ajax', 'submit_form' ) );
        $this->assertTrue( $result['exited'] );
        $response = json_decode( $result['output'], true );
        $this->assertTrue( is_array( $response ), $result['output'] );
        $this->assertFalse( $response['error'], $result['output'] );
        $entries = get_posts( array(
            'post_type' => 'super_contact_entry',
            'post_status' => 'any',
            'post_parent' => $form_id,
            'numberposts' => -1,
        ) );
        $this->assertCount( 1, $entries );
        $this->assertSame( $account_id, (int) $entries[0]->post_author );
    }

}
