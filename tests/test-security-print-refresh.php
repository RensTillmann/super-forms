<?php
require_once __DIR__ . '/test-security-upload-00-base.php';
class Test_Security_Print_Refresh extends Super_Forms_Upload_Security_Test_Case {
    public function test_refresh_requires_nonce_and_exact_saved_print_attachment() {
        $settings = array('allow_storing_cookies'=>'0');
        update_option('super_settings', $settings);
        SUPER_Forms()->global_settings = $settings;
        unset($_COOKIE['_sfs_id']);
        $file = self::factory()->post->create(array('post_type'=>'attachment', 'post_status'=>'inherit', 'post_mime_type'=>'text/html'));
        $other = self::factory()->post->create(array('post_type'=>'attachment', 'post_status'=>'inherit', 'post_mime_type'=>'text/html'));
        $button = array('tag'=>'button', 'data'=>array('action'=>'print', 'print_custom'=>'true', 'print_file'=>(string)$file));
        $elements = array(array('tag'=>'column', 'inner'=>array($button)));
        $form = $this->create_form('publish', $elements, $settings);
        $nonce = wp_create_nonce('super_create_nonce_' . $form);
        foreach( array(array($file, 'invalid', false), array($other, $nonce, false), array(array($file), $nonce, false), array($file, $nonce, true)) as $case ) {
            $_POST = array('action'=>'super_create_nonce', 'form_id'=>(string)$form, 'print_file_id'=>$case[0], 'nonce'=>$case[1]);
            $_REQUEST = $_POST;
            $result = $this->run_dying_handler(array('SUPER_Ajax', 'create_nonce'), false);
            $this->assertSame(0, $result['status'], $result['output']);
            $payload = json_decode($result['output'], true);
            $this->assertIsArray($payload, $result['output']);
            $this->assertArrayHasKey('print_capability', $payload);
            if( $case[2] ) {
                $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $payload['print_capability']);
                $this->assertSame(array('version'=>1, 'form_id'=>$form, 'file_id'=>$file), SUPER_Common::consume_public_print_capability($payload['print_capability'], array('form_id'=>$form, 'file_id'=>$file)));
            } else $this->assertSame('', $payload['print_capability']);
        }
        wp_update_post(array('ID'=>$form, 'post_status'=>'draft'));
        $result = $this->run_dying_handler(array('SUPER_Ajax', 'create_nonce'), false);
        $payload = json_decode($result['output'], true);
        $this->assertSame('', $payload['print_capability'], 'Anonymous refresh must not expose draft configuration.');
        $this->assertArrayNotHasKey('_sfs_id', $_COOKIE);
    }
}
