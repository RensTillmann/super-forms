<?php
require_once __DIR__ . '/test-security-upload-00-base.php';
class Test_Security_Production_Upload_Root extends Super_Forms_Upload_Security_Test_Case {
    private function valid_png_bytes() {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            true
        );
    }

    public static function field_names() {
        return array(array('documents'), array('0'), array('123'), array('001'));
    }

    /** @dataProvider field_names */
    public function test_custom_file_created_in_production_upload_directory_is_deleted_with_entry( $field_name ) {
        foreach( array('', 'true') as $dated ) {
            list($parent, $root) = $this->create_temporary_root(true);
            $settings = array(
                'file_upload_dir'=>'../' . basename($parent) . '/' . basename($root),
                'file_upload_entry_delete'=>'true', 'file_upload_use_year_month_folders'=>$dated,
            );
            update_option('super_settings', $settings);
            SUPER_Forms()->global_settings = $settings;
            $form = $this->create_form('publish', array($this->file_element($field_name)), $settings);
            $dirs = SUPER_Forms::filter_upload_dir(wp_upload_dir());
            $this->assertFalse($dirs['error']);
            $this->assertNotSame(wp_normalize_path($root), wp_normalize_path($dirs['path']));
            $path = trailingslashit($dirs['path']) . 'owned.png';
            file_put_contents($path, $this->valid_png_bytes());
            $subdir = trailingslashit($dirs['subdir']) . 'owned.png';
            $url = str_replace('../', '__/', trailingslashit($dirs['baseurl']) . 'sfgtfi' . $subdir);
            $owned = SUPER_Ajax::build_owned_upload($form, $field_name, $path, 'image/png', $url, 0, $dirs['path'], filesize($path), $subdir);
            $this->assertIsArray($owned);
            $stored = $this->invoke_ajax_private('owned_upload_file_record', array($owned));
            $entry = self::factory()->post->create(array('post_type'=>'super_contact_entry', 'post_status'=>'super_read', 'post_parent'=>$form));
            $data = array($field_name=>array('name'=>$field_name, 'type'=>'files', 'files'=>array($stored)));
            update_post_meta($entry, '_super_contact_entry_data', $data);
            $forged = $data;
            $forged[$field_name]['files'][0]['_super_file_proof'] = str_repeat('0', 64);
            update_post_meta($entry, '_super_contact_entry_data', $forged);
            SUPER_Forms::delete_entry_attachments($entry);
            $this->assertFileExists($path, 'Forged ownership must not delete the file.');
            update_post_meta($entry, '_super_contact_entry_data', $data);
            SUPER_Forms::delete_entry_attachments($entry);
            $this->assertFileDoesNotExist($path, 'Production-created custom upload must be deleted with its entry.');
        }
    }

    /** @dataProvider field_names */
    public function test_attachment_deletion_preserves_exact_numeric_field_identity( $field_name ) {
        $settings = array('file_upload_entry_delete'=>'true');
        update_option('super_settings', $settings);
        SUPER_Forms()->global_settings = $settings;
        $form = $this->create_form('publish', array($this->file_element($field_name)), $settings);
        $uploaded = wp_upload_bits('sf-owned-' . wp_generate_uuid4() . '.png', null, $this->valid_png_bytes());
        $this->assertEmpty($uploaded['error']);
        $attachment = wp_insert_attachment(array('post_mime_type'=>'image/png', 'post_status'=>'inherit'), $uploaded['file']);
        $this->attachment_ids[] = $attachment;
        add_post_meta($attachment, 'super-forms-form-upload-file', true);
        add_post_meta($attachment, '_super_forms_upload_form_id', $form);
        add_post_meta($attachment, '_super_forms_upload_field', $field_name);
        $owned = SUPER_Ajax::build_owned_upload($form, $field_name, $uploaded['file'], 'image/png', $uploaded['url'], $attachment, dirname($uploaded['file']), filesize($uploaded['file']));
        $upload = array('owned'=>$owned, 'attachment'=>$attachment, 'file'=>$uploaded['file']);
        $entry = self::factory()->post->create(array('post_type'=>'super_contact_entry', 'post_status'=>'super_read', 'post_parent'=>$form));
        wp_update_post(array('ID'=>$upload['attachment'], 'post_parent'=>$entry));
        $stored = $this->invoke_ajax_private('owned_upload_file_record', array($upload['owned']));
        update_post_meta($entry, '_super_contact_entry_data', array($field_name=>array('name'=>$field_name, 'type'=>'files', 'files'=>array($stored))));
        update_post_meta($upload['attachment'], '_super_forms_upload_field', 'other-field');
        SUPER_Forms::delete_entry_attachments($entry);
        $this->assertNotNull(get_post($upload['attachment']), 'Wrong field metadata must not authorize deletion.');
        update_post_meta($upload['attachment'], '_super_forms_upload_field', $field_name);
        SUPER_Forms::delete_entry_attachments($entry);
        $this->assertNull(get_post($upload['attachment']), 'Matching numeric field must authorize its own attachment.');
        $this->assertFileDoesNotExist($upload['file']);
    }
}
