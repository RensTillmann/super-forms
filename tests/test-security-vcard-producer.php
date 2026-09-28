<?php
require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Security_Vcard_Producer extends Super_Forms_Upload_Security_Test_Case {
    public static function storage_cases() {
        return array(array('', ''), array('true', ''), array('', 'true'), array('true', 'true'));
    }

    /** @dataProvider storage_cases */
    public function test_submission_generates_owned_vcard_and_obeys_cleanup_settings($dated, $delete) {
        if (!class_exists('SUPER_VCF_Attachment')) {
            require_once SUPER_PLUGIN_DIR . '/includes/extensions/vcf-card/vcf-card.php';
        }
        $this->add_upload_filter('super_after_processing_files_data_filter', array('SUPER_VCF_Attachment', 'create_vcard'), 10, 2);
        // Remove the instance registration so this fixture invokes the producer exactly once.
        remove_filter('super_after_processing_files_data_filter', array(SUPER_VCF_Attachment::instance(), 'create_vcard'), 10);
        list($parent, $root) = $this->create_temporary_root(true);
        $settings = array(
            'csrf_check'=>'false', 'send'=>'no', 'confirm'=>'no', 'save_contact_entry'=>'yes',
            'form_thanks_title'=>'', 'form_thanks_description'=>'', 'form_show_thanks_msg'=>'', 'form_redirect_option'=>'',
            'file_upload_dir'=>'../' . basename($parent) . '/' . basename($root),
            'file_upload_use_year_month_folders'=>$dated, 'file_upload_entry_delete'=>'true',
            'vcard_enable'=>'entry', 'vcard_name'=>'{first_name}-contact',
            'vcard_content'=>"VERSION:3.0\nFN:{first_name}", 'vcard_delete'=>$delete,
        );
        update_option('super_settings', $settings);
        SUPER_Forms()->global_settings = $settings;
        $form = $this->create_form('publish', array(array('tag'=>'text', 'data'=>array('name'=>'first_name'))), $settings);
        $this->set_request($form, array('first_name'=>array('name'=>'first_name', 'value'=>'Ada', 'type'=>'var')), array(), array('action'=>'super_submit_form'));
        $_POST['data'] = wp_slash($_POST['data']);
        $_REQUEST = $_POST;
        $result = $this->run_dying_handler(array('SUPER_Ajax', 'submit_form'));
        $this->assertSame(0, $result['status'], $result['output']);
        $response = json_decode($result['output'], true);
        $this->assertIsArray($response, $result['output']);
        $this->assertFalse($response['error'], $result['output']);
        $entries = get_posts(array('post_type'=>'super_contact_entry', 'post_parent'=>$form, 'post_status'=>'any', 'numberposts'=>-1));
        $this->assertCount(1, $entries);
        $stored = SUPER_Data_Access::get_entry_data($entries[0]->ID);
        $this->assertCount(1, $stored['_vcard']['files']);
        $file = $stored['_vcard']['files'][0];
        $this->assertSame('owned', $file['_super_file_authority']);
        $this->assertSame('text/vcard', $file['type']);
        $this->assertSame('Ada-contact.vcf', $file['value']);
        $this->assertStringStartsWith(wp_normalize_path($root) . '/', wp_normalize_path($file['path']));
        if ($delete === 'true') {
            $this->assertFileDoesNotExist($file['path'], 'Configured post-submission cleanup must delete the generated file.');
            return;
        }
        $this->assertFileExists($file['path']);
        $this->assertSame("BEGIN:VCARD\nVERSION:3.0\nFN:Ada\nEND:VCARD", file_get_contents($file['path']));
        $resolver = new ReflectionMethod('SUPER_Forms', 'resolve_sfgtfi_file');
        $resolver->setAccessible(true);
        $url_path = wp_parse_url($file['url'], PHP_URL_PATH);
        $marker = strpos($url_path, '/sfgtfi/');
        $this->assertNotFalse($marker);
        $route = rawurldecode(substr($url_path, $marker + strlen('/sfgtfi/')));
        $resolved = $resolver->invoke(null, $route, $settings);
        $this->assertIsArray($resolved, 'The actual producer URL must resolve through the download guard.');
        $this->assertSame(wp_normalize_path(realpath($file['path'])), $resolved['file']);
        $forged = $stored;
        $forged['_vcard']['files'][0]['_super_file_proof'] = str_repeat('0', 64);
        SUPER_Data_Access::update_entry_data($entries[0]->ID, $forged);
        SUPER_Forms::delete_entry_attachments($entries[0]->ID);
        $this->assertFileExists($file['path'], 'A forged ownership proof must not authorize deletion.');
        SUPER_Data_Access::update_entry_data($entries[0]->ID, $stored);
        SUPER_Forms::delete_entry_attachments($entries[0]->ID);
        $this->assertFileDoesNotExist($file['path'], 'An authentic generated vCard must be deleted with its entry.');
    }
}
