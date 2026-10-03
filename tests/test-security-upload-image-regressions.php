<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

/** Metadata/receipt tests are not evidence of a PHP-received HTTP upload. */
class Test_Super_Forms_Upload_Image_Regressions extends Super_Forms_Upload_Security_Test_Case {
    private function parallel_files( $field, $items ) {
        $files = array(
            'name' => array( $field => array() ),
            'type' => array( $field => array() ),
            'tmp_name' => array( $field => array() ),
            'error' => array( $field => array() ),
            'size' => array( $field => array() ),
        );
        foreach( $items as $key => $item ) {
            $files['name'][$field][$key] = $item['name'];
            $files['type'][$field][$key] = $item['type'];
            $files['tmp_name'][$field][$key] = $item['tmp_name'];
            $files['error'][$field][$key] = UPLOAD_ERR_OK;
            $files['size'][$field][$key] = $item['size'];
        }
        return $files;
    }

    private function image_attachment( $form_id, $width=4032, $height=3024, $extension='jpg', $orientation=1 ) {
        $this->assertTrue( function_exists('imagecreatetruecolor'), 'These regressions require GD image fixtures.' );
        require_once ABSPATH . 'wp-admin/includes/image.php';
        list( $parent, $root ) = $this->create_temporary_root(true);
        $file = trailingslashit($root) . 'camera-' . wp_generate_uuid4() . '.' . $extension;
        $image = imagecreatetruecolor($width, $height);
        $this->assertNotFalse($image);
        if( strtolower($extension)==='png' ) {
            $this->assertTrue(imagepng($image, $file));
            $mime = 'image/png';
        } else {
            $this->assertTrue(imagejpeg($image, $file, 85));
            $mime = 'image/jpeg';
        }
        imagedestroy($image);
        if( $orientation!==1 ) {
            // Real JPEG APP1/TIFF Orientation tag; WordPress reads and rotates it.
            $tiff = "II\x2a\x00\x08\x00\x00\x00" . pack('v', 1)
                . pack('vvVv', 0x0112, 3, 1, $orientation) . "\x00\x00" . pack('V', 0);
            $exif = "Exif\x00\x00" . $tiff;
            $jpeg = file_get_contents($file);
            $this->assertNotFalse(file_put_contents($file, substr($jpeg, 0, 2)
                . "\xff\xe1" . pack('n', strlen($exif)+2) . $exif . substr($jpeg, 2)));
        }
        $attachment = wp_insert_attachment(array(
            'post_mime_type' => $mime,
            'post_title' => 'Camera image',
            'post_status' => 'inherit',
        ), $file, 0);
        $this->assertTrue(is_int($attachment) && $attachment>0);
        $this->attachment_ids[] = $attachment;
        add_post_meta($attachment, 'super-forms-form-upload-file', true);
        add_post_meta($attachment, '_super_forms_upload_form_id', $form_id);
        add_post_meta($attachment, '_super_forms_upload_field', 'documents');
        $metadata = wp_generate_attachment_metadata($attachment, $file);
        wp_update_attachment_metadata($attachment, $metadata);
        return array(
            'file' => wp_normalize_path(realpath($file)), 'root' => $root,
            'attachment' => $attachment, 'mime' => $mime, 'metadata' => $metadata,
            'form' => $form_id, 'parent' => $parent,
        );
    }

    private function build_image_owned( $created ) {
        return SUPER_Ajax::build_owned_upload(
            $created['form'], 'documents', $created['file'], $created['mime'],
            wp_get_attachment_url($created['attachment']), $created['attachment'],
            $created['root'], false
        );
    }

    public static function generated_image_cases() {
        return array(
            'landscape JPEG' => array(4032, 3024, 'jpg', 1, 'scaled'),
            'portrait JPEG' => array(3024, 4032, 'jpeg', 1, 'scaled'),
            'large PNG' => array(3000, 2000, 'png', 1, 'scaled'),
            'EXIF rotation below threshold' => array(2000, 1500, 'jpg', 6, 'rotated'),
            'unscaled control' => array(2200, 1650, 'jpg', 1, ''),
        );
    }

    /** @dataProvider generated_image_cases */
    public function test_generated_image_survives_ownership_receipt_and_submission( $width, $height, $extension, $orientation, $suffix ) {
        $this->configure_csrf('false');
        $form_id = $this->create_form('publish', array($this->file_element('documents')));
        $created = $this->image_attachment($form_id, $width, $height, $extension, $orientation);
        $attached = wp_normalize_path(realpath(get_attached_file($created['attachment'])));
        if( $suffix!=='' ) {
            $this->assertNotSame($created['file'], $attached);
            $this->assertStringContainsString('-' . $suffix . '.', $attached);
            $this->assertSame(basename($created['file']), $created['metadata']['original_image']);
            $dimensions = getimagesize($attached);
            if( $suffix==='scaled' ) {
                $this->assertSame(2560, max($dimensions[0], $dimensions[1]));
            } else {
                $this->assertSame(array($height, $width), array($dimensions[0], $dimensions[1]));
            }
        } else {
            $this->assertSame($created['file'], $attached);
        }
        $owned = $this->build_image_owned($created);
        $this->assertIsArray($owned);
        $this->assertTrue($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
        $token = $this->issue_receipt($owned);
        $this->assertIsArray($this->invoke_ajax_private('inspect_upload_receipt', array($token, $form_id, 'documents')));
        $this->set_request($form_id, array('documents' => array(
            'type' => 'files', 'files' => array(array(
                'upload_token' => $token, 'value' => 'forged.php',
                'url' => 'https://attacker.invalid/forged.php', 'path' => '/etc/passwd',
            )),
        )));
        $atts = SUPER_Ajax::submit_form_checks(array(), false);
        $this->assertCount(1, $atts['owned_files']);
        $this->assertSame($created['file'], $atts['owned_files'][0]['file']);
        $this->assertSame(wp_get_attachment_url($created['attachment']), $atts['data']['documents']['files'][0]['url']);
        $this->assertFalse($this->invoke_ajax_private('inspect_upload_receipt', array($token, $form_id, 'documents')));
        $this->assertFileExists($created['file']);
        $this->assertFileExists($attached);
        $entry = self::factory()->post->create(array(
            'post_type'=>'super_contact_entry', 'post_status'=>'super_unread', 'post_parent'=>$form_id,
        ));
        wp_update_post(array('ID'=>$created['attachment'], 'post_parent'=>$entry));
        $this->assertTrue($this->invoke_ajax_private('owned_upload_is_current', array($owned, $entry)));
        $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
        $settings = array(
            'file_upload_dir'=>'../' . basename($created['parent']) . '/' . basename($created['root']),
        );
        update_post_meta($form_id, '_super_form_settings', $settings);
        update_post_meta($entry, '_super_contact_entry_data', $atts['data']);
        $stored = $atts['data']['documents']['files'][0];
        $element = $this->file_element('documents');
        $retained = false;
        $record = $this->invoke_ajax_private('rebuild_retained_entry_file', array(
            $stored, $entry, $form_id, 'documents', $element['data'], $settings, 0, 'documents', &$retained,
        ));
        $this->assertIsArray($record);
        $this->assertSame(basename($created['file']), $record['value']);
        $this->assertSame(filesize($created['file']), $record['size']);
        $this->assertTrue($this->invoke_ajax_private('retained_owned_upload_is_current', array($retained)));
        foreach( array('../' . $stored['value'], $created['file'], 'other/' . $stored['value'], 'other.jpg') as $value ) {
            $forged = $stored;
            $forged['value'] = $value;
            if( $value==='other.jpg' ) {
                update_post_meta($entry, '_super_contact_entry_data', array(
                    'documents'=>array('type'=>'files', 'files'=>array($forged)),
                ));
                $legacy_owned = false;
                $legacy = $this->invoke_ajax_private('rebuild_retained_entry_file', array(
                    $forged, $entry, $form_id, 'documents', $element['data'], $settings, 0, 'documents', &$legacy_owned,
                ));
                $this->assertIsArray($legacy);
                $this->assertSame(basename($attached), $legacy['value']);
                $this->assertSame(filesize($attached), $legacy['size']);
                $this->assertFalse($legacy_owned['cleanup_authority']);
                $this->assertFalse($this->invoke_ajax_private('retained_owned_upload_is_current', array($legacy_owned)));
                $this->assertFalse($this->invoke_ajax_private('delete_finalized_owned_uploads', array(array($legacy_owned), $entry, $form_id)));
                $this->assertFileExists($attached);
                $this->assertFileExists($created['file']);
                update_post_meta($entry, '_super_contact_entry_data', $atts['data']);
            } else {
                $this->assertFalse($this->invoke_ajax_private('rebuild_retained_entry_file', array(
                    $forged, $entry, $form_id, 'documents', $element['data'], $settings, 0, 'documents',
                )));
            }
            if( $suffix!=='' ) {
                $metadata = $created['metadata'];
                $metadata['original_image'] = $value;
                wp_update_attachment_metadata($created['attachment'], $metadata);
                $this->assertFalse($this->invoke_ajax_private('retained_owned_upload_is_current', array($retained)));
                wp_update_attachment_metadata($created['attachment'], $created['metadata']);
            }
        }
    }

    public function test_generated_original_metadata_cannot_grant_path_or_cleanup_authority() {
        $form_id = $this->create_form('publish', array($this->file_element('documents')));
        $created = $this->image_attachment($form_id);
        $owned = $this->build_image_owned($created);
        $this->assertIsArray($owned);
        $token = $this->issue_receipt($owned);
        $metadata = $created['metadata'];
        $other = trailingslashit($created['root']) . 'other.jpg';
        $this->assertTrue(copy($created['file'], $other));
        $directory = trailingslashit($created['root']) . 'other-directory';
        $this->assertTrue(wp_mkdir_p($directory));
        $outside = trailingslashit($directory) . basename($created['file']);
        $this->assertTrue(copy($created['file'], $outside));
        $link = trailingslashit($created['root']) . 'original-link.jpg';
        $this->assertTrue(symlink($created['file'], $link));
        foreach( array(
            '../' . basename($created['file']), $created['file'],
            'other-directory/' . basename($created['file']), '..\\' . basename($created['file']),
            'C:\\camera.jpg', 'original-link.jpg', 'missing.jpg', 'other.jpg', '.', '..',
            array('camera.jpg'), "camera\0.jpg",
        ) as $forged ) {
            $changed = $metadata;
            $changed['original_image'] = $forged;
            wp_update_attachment_metadata($created['attachment'], $changed);
            $this->assertFalse($this->build_image_owned($created));
            $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
            $this->assertFalse($this->invoke_ajax_private('inspect_upload_receipt', array($token, $form_id, 'documents')));
            $this->assertFalse($this->invoke_ajax_private('cleanup_owned_uploads', array(array($owned))));
            $this->assertFileExists($created['file']);
            $this->assertFileExists($other);
            $this->assertFileExists($outside);
        }
        wp_update_attachment_metadata($created['attachment'], $metadata);
        unlink($link);
        $this->assertTrue($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
    }

    public function test_generated_original_and_attached_path_swaps_revoke_receipts() {
        $form_id = $this->create_form('publish');
        $created = $this->image_attachment($form_id);
        $owned = $this->build_image_owned($created);
        $this->assertIsArray($owned);
        $token = $this->issue_receipt($owned);
        $attached_meta = get_post_meta($created['attachment'], '_wp_attached_file', true);
        $attached = get_attached_file($created['attachment']);
        $other = trailingslashit($created['root']) . 'swapped-scaled.jpg';
        $this->assertTrue(copy($attached, $other));
        update_post_meta($created['attachment'], '_wp_attached_file', $other);
        $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
        $this->assertFalse($this->invoke_ajax_private('inspect_upload_receipt', array($token, $form_id, 'documents')));
        update_post_meta($created['attachment'], '_wp_attached_file', trailingslashit(dirname($attached)) . './' . basename($attached));
        $this->assertFalse($this->build_image_owned($created));
        update_post_meta($created['attachment'], '_wp_attached_file', $attached_meta);
        $backup = $created['file'] . '.backup';
        $this->assertTrue(rename($created['file'], $backup));
        clearstatcache();
        $this->assertFalse($this->build_image_owned($created));
        $this->assertFalse($this->invoke_ajax_private('inspect_upload_receipt', array($token, $form_id, 'documents')));
        $this->assertTrue(symlink($backup, $created['file']));
        clearstatcache();
        $this->assertFalse($this->build_image_owned($created));
        $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
        unlink($created['file']);
        $this->assertTrue(rename($backup, $created['file']));
        clearstatcache();
        $this->assertTrue($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
        $this->assertIsArray($this->invoke_ajax_private('inspect_upload_receipt', array($token, $form_id, 'documents')));
        // Canonical path and size are bound; same-size regular-file content is not hashed.
    }

    public function test_generated_image_keeps_marker_form_field_mime_root_size_and_parent_defenses() {
        $form_id = $this->create_form('publish');
        $created = $this->image_attachment($form_id);
        $owned = $this->build_image_owned($created);
        $this->assertIsArray($owned);
        foreach( array(
            array('super-forms-form-upload-file', ''),
            array('_super_forms_upload_form_id', $form_id+1),
            array('_super_forms_upload_field', 'other'),
        ) as $change ) {
            $previous = get_post_meta($created['attachment'], $change[0], true);
            update_post_meta($created['attachment'], $change[0], $change[1]);
            $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
            update_post_meta($created['attachment'], $change[0], $previous);
        }
        wp_update_post(array('ID'=>$created['attachment'], 'post_mime_type'=>'text/plain'));
        $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
        wp_update_post(array('ID'=>$created['attachment'], 'post_mime_type'=>$created['mime']));
        $changed = $owned;
        $changed['size']++;
        $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($changed, 0)));
        list($parent, $other_root) = $this->create_temporary_root();
        $changed = $owned;
        $changed['allowed_root'] = $other_root;
        $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($changed, 0)));
        $this->assertTrue($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
    }

    public function test_uppercase_image_extensions_reach_file_handling_without_relaxing_policy() {
        $this->configure_csrf('false');
        $form_id = $this->create_form('publish', array($this->file_element('documents')));
        list($parent, $root) = $this->create_temporary_root();
        $tmp = trailingslashit($root) . 'incoming';
        file_put_contents($tmp, 'preflight only, not an HTTP upload');
        $marker = trailingslashit($parent) . 'prefilter';
        $this->add_upload_filter('wp_handle_upload_prefilter', static function($file) use ($marker) {
            file_put_contents($marker, 'reached');
            return $file;
        });
        foreach( array('camera.JPG', 'camera.JpG', 'camera.JPEG', 'camera.JpEg', 'camera.PNG', 'camera.PnG') as $name ) {
            $this->set_request($form_id, array(), array('files'=>$this->parallel_files('documents', array(array(
                'name'=>$name, 'tmp_name'=>$tmp, 'type'=>'image/jpeg', 'size'=>filesize($tmp),
            )))));
            $result = $this->run_dying_handler(array('SUPER_Ajax', 'upload_files'));
            $this->assertSame(0, $result['status'], $result['output']);
            $this->assertFileExists($marker, $name . ' must pass extension preflight. HTTP proves upload success.');
            unlink($marker);
        }
        foreach( array('shell.PHP', 'shell.PHP.JPG', 'shell.phtml.PNG', 'shell.phar.JPEG', 'camera.SVG') as $name ) {
            $this->set_request($form_id, array(), array('files'=>$this->parallel_files('documents', array(array(
                'name'=>$name, 'tmp_name'=>$tmp, 'type'=>'image/jpeg', 'size'=>filesize($tmp),
            )))));
            $this->assert_handler_rejected_with(array('SUPER_Ajax', 'upload_files'), 'not permitted');
            $this->assertFileDoesNotExist($marker);
            $this->assertFileExists($tmp);
        }
        $this->add_upload_filter('super_file_upload_mime_types_validation', static function($mimes) {
            $mimes['php'] = 'image/jpeg';
            $mimes['jpg|jpeg'] = 'text/plain';
            return $mimes;
        });
        $allowed = $this->invoke_ajax_private('allowed_file_mime_types', array(array('extensions'=>'jpg|jpeg|png|php')));
        $this->assertArrayNotHasKey('php', $allowed);
        $this->assertArrayNotHasKey('jpg', $allowed);
        $this->assertArrayNotHasKey('jpeg', $allowed);
        $this->assertArrayHasKey('png', $allowed);
    }

    public static function retained_extension_cases() {
        return array(
            array('JPG'), array('JpG'), array('JPEG'), array('JpEg'),
            array('PNG'), array('PnG'),
        );
    }

    /** @dataProvider retained_extension_cases */
    public function test_uppercase_image_retained_entry_rebuild_preserves_authority( $extension ) {
        $element = $this->file_element('documents');
        $form_id = $this->create_form('publish', array($element));
        $created = $this->image_attachment($form_id, 320, 240, $extension);
        list($configured_parent, $configured_root) = $this->create_temporary_root(true);
        $file = trailingslashit($configured_root) . basename($created['file']);
        $this->assertTrue(rename($created['file'], $file));
        $this->assertTrue(update_attached_file($created['attachment'], $file));
        $settings = array(
            'file_upload_dir' => '../' . basename($configured_parent) . '/' . basename($configured_root),
        );
        update_post_meta($form_id, '_super_form_settings', $settings);
        $entry_id = self::factory()->post->create(array(
            'post_type'=>'super_contact_entry', 'post_status'=>'super_unread', 'post_parent'=>$form_id,
        ));
        wp_update_post(array('ID'=>$created['attachment'], 'post_parent'=>$entry_id));
        $stored = array(
            'value'=>basename($file), 'name'=>'documents', 'type'=>$created['mime'],
            'url'=>wp_get_attachment_url($created['attachment']), 'attachment'=>$created['attachment'],
        );
        update_post_meta($entry_id, '_super_contact_entry_data', array(
            'documents'=>array('type'=>'files', 'files'=>array(7=>$stored)),
        ));
        $owned = false;
        $record = $this->invoke_ajax_private('rebuild_retained_entry_file', array(
            $stored, $entry_id, $form_id, 'documents', $element['data'], $settings, 7, 'documents', &$owned,
        ));
        $this->assertIsArray($record);
        $this->assertSame('retained', $record['_super_file_authority']);
        $this->assertSame(basename($file), $record['value']);
        $this->assertIsArray($owned);
        $this->assertTrue($this->invoke_ajax_private('retained_owned_upload_is_current', array($owned)));
        // Established retained/direct records do not need the newly recorded
        // derived-path association: the exact attached path still equals file.
        unset($owned['attached_file']);
        $this->assertTrue($this->invoke_ajax_private('retained_owned_upload_is_current', array($owned)));
        wp_update_post(array('ID'=>$created['attachment'], 'post_mime_type'=>'text/plain'));
        $this->assertFalse($this->invoke_ajax_private('retained_owned_upload_is_current', array($owned)));
    }

    public static function legacy_client_name_cases() {
        return array(
            'sanitized' => array('IMG 0001.JPG', 'IMG-0001.jpg', false),
            'uniquified' => array('camera.jpg', 'camera-1.jpg', false),
            'uppercase client' => array('CAMERA.JPG', 'CAMERA.jpg', false),
            'removed original' => array('old camera.JPG', '', true),
            'missing literal original' => array('', '', true),
        );
    }

    /** @dataProvider legacy_client_name_cases */
    public function test_legacy_client_name_retention_resolves_without_cleanup_authority( $client_name, $attached_name, $remove_original ) {
        $element = $this->file_element('documents');
        $form_id = $this->create_form('publish', array($element));
        $created = $this->image_attachment($form_id, $remove_original ? 3000 : 320, $remove_original ? 2000 : 240);
        $attached = get_attached_file($created['attachment']);
        if( $remove_original ) {
            $this->assertNotSame($created['file'], wp_normalize_path($attached));
            if( $client_name==='' ) $client_name = basename($created['file']);
            $this->assertTrue(unlink($created['file']));
            clearstatcache();
        } else {
            $renamed = trailingslashit($created['root']) . $attached_name;
            $this->assertTrue(rename($attached, $renamed));
            $this->assertTrue(update_attached_file($created['attachment'], $renamed));
            $attached = $renamed;
        }
        $entry = self::factory()->post->create(array(
            'post_type'=>'super_contact_entry', 'post_status'=>'super_unread', 'post_parent'=>$form_id,
        ));
        wp_update_post(array('ID'=>$created['attachment'], 'post_parent'=>$entry));
        $settings = array('file_upload_dir'=>'../' . basename($created['parent']) . '/' . basename($created['root']));
        update_post_meta($form_id, '_super_form_settings', $settings);
        $stored = array(
            'value'=>$client_name, 'name'=>'documents', 'type'=>$created['mime'],
            'url'=>wp_get_attachment_url($created['attachment']), 'attachment'=>$created['attachment'],
        );
        update_post_meta($entry, '_super_contact_entry_data', array(
            'documents'=>array('type'=>'files', 'files'=>array(7=>$stored)),
        ));
        $owned = false;
        $record = $this->invoke_ajax_private('resolve_retained_entry_file', array(
            array('value'=>$client_name, 'url'=>$stored['url'], 'retention_token'=>'entry'),
            $entry, 'documents', 'documents', &$owned,
        ));
        $this->assertIsArray($record);
        $this->assertSame(basename($attached), $record['value']);
        $this->assertSame(filesize($attached), $record['size']);
        $this->assertSame(wp_normalize_path(realpath($attached)), $owned['file']);
        $this->assertFalse($owned['cleanup_authority']);
        $this->assertFalse($this->invoke_ajax_private('retained_owned_upload_is_current', array($owned)));
        $this->assertFalse($this->invoke_ajax_private('delete_finalized_owned_uploads', array(array($owned), $entry, $form_id)));
        $this->assertFileExists($attached);
        if( $remove_original ) $this->assertFileDoesNotExist($created['file']);
    }

    public static function custom_meta_image_cases() {
        return array(
            'scaled' => array(3000, 2000, 1),
            'rotated' => array(2000, 1500, 6),
            'unprocessed' => array(320, 240, 1),
        );
    }

    private function image_meta_action( $form_id, $data ) {
        $settings = array(
            'register_login_action'=>'update', 'register_login_user_id_update'=>'true',
            'register_login_register_not_logged_in'=>'', 'register_login_not_logged_in_msg'=>'Please log in.',
            'register_login_show_toolbar'=>'', 'register_user_role'=>'_super_keep_existing_role',
            'register_login_update_user_meta'=>'documents|sf_image_meta', 'register_login_user_meta'=>'',
            'register_login_action_skip_register'=>'', 'register_login_activation'=>'none',
            'register_user_signup_status'=>'active', 'register_send_approve_email'=>'',
            'register_login_multisite_enabled'=>'',
        );
        $post = array('action'=>'super_submit_form', 'form_id'=>(string)$form_id, 'data'=>wp_json_encode($data));
        $_POST = $_REQUEST = $post;
        $atts = array(
            'settings'=>$settings, 'data'=>$data, 'post'=>$post,
            'entry_id'=>0, 'attachments'=>array(),
        );
        SUPER_Register_Login::before_sending_email($atts);
        return $atts;
    }

    /** @dataProvider custom_meta_image_cases */
    public function test_register_login_maps_original_image_through_account_hooks( $width, $height, $orientation ) {
        if( !class_exists('SUPER_Register_Login') ) {
            require_once SUPER_PLUGIN_DIR . '/add-ons/super-forms-register-login/super-forms-register-login.php';
        }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        $form_id = $this->create_form('publish', array($this->file_element('documents')));
        $created = $this->image_attachment($form_id, $width, $height, 'jpg', $orientation);
        $owned = $this->build_image_owned($created);
        $this->assertIsArray($owned);
        $record = $this->invoke_ajax_private('owned_upload_file_record', array($owned));
        $resolver = new ReflectionMethod('SUPER_Register_Login', 'resolve_custom_meta_value');
        $resolver->setAccessible(true);
        $data = array('documents'=>array('type'=>'files', 'files'=>array($record)));
        $this->assertSame($created['attachment'], $resolver->invoke(null, 'documents', $data, array(), $form_id));
        $user_id = self::factory()->user->create(array('role'=>'subscriber'));
        wp_set_current_user($user_id);
        $client_key = 'sfimage' . str_replace('-', '', wp_generate_uuid4());
        $_COOKIE['_sfs_id'] = $client_key;
        update_option('_sfsdata_' . $client_key, array(
            'expires'=>time()+HOUR_IN_SECONDS, 'exp_var'=>time()+20*MINUTE_IN_SECONDS, 'image_meta_fixture'=>true,
        ), false);
        $data['user_id'] = array('type'=>'text', 'value'=>(string)$user_id);
        try {
            $atts = $this->image_meta_action($form_id, $data);
            SUPER_Register_Login::before_email_success_msg($atts);
            $this->assertSame((string)$created['attachment'], get_user_meta($user_id, 'sf_image_meta', true));
            foreach( array('../' . $record['value'], 'other.jpg', get_attached_file($created['attachment'])) as $value ) {
                $forged_data = $data;
                $forged_data['documents']['files'][0]['value'] = $value;
                $this->assertWPError($resolver->invoke(null, 'documents', $forged_data, array(), $form_id));
                $forged_atts = $this->image_meta_action($form_id, $forged_data);
                $result = $this->run_dying_handler(static function() use ($forged_atts) {
                    SUPER_Register_Login::before_email_success_msg($forged_atts);
                }, false);
                $this->assertSame(0, $result['status'], $result['output']);
                $this->assertStringContainsString('Invalid file upload', wp_strip_all_tags($result['output']));
                $this->assertSame((string)$created['attachment'], get_user_meta($user_id, 'sf_image_meta', true));
            }
            if( $width>2560 || $orientation!==1 ) {
                foreach( array('../' . $record['value'], 'other.jpg', $created['file'], array($record['value'])) as $value ) {
                    $metadata = $created['metadata'];
                    $metadata['original_image'] = $value;
                    wp_update_attachment_metadata($created['attachment'], $metadata);
                    $this->assertWPError($resolver->invoke(null, 'documents', $data, array(), $form_id));
                    $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
                }
                wp_update_attachment_metadata($created['attachment'], $created['metadata']);
            }
        } finally {
            $clear = new ReflectionMethod('SUPER_Register_Login', 'clear_user_meta_bridge');
            $clear->setAccessible(true);
            $clear->invoke(null);
            wp_set_current_user(0);
            wp_delete_user($user_id);
            delete_option('_sfsdata_' . $client_key);
        }
    }

    private function assert_symlinked_upload_parent_image( $width, $height, $orientation=1 ) {
        $form_id = $this->create_form('publish', array($this->file_element('documents')));
        $created = $this->image_attachment($form_id, $width, $height, 'jpg', $orientation);
        $attached = wp_normalize_path(realpath(get_attached_file($created['attachment'])));
        $uploads = wp_upload_dir();
        $this->assertEmpty($uploads['error']);
        $this->assertTrue(wp_mkdir_p($uploads['basedir']));
        $linked_parent = trailingslashit($uploads['basedir']) . 'sf-linked-uploads-' . wp_generate_uuid4();
        $this->assertTrue(symlink($created['root'], $linked_parent));
        $upload_filter = $this->add_upload_filter('upload_dir', static function($directory) use ($linked_parent, $uploads) {
            $directory['basedir'] = $directory['path'] = $linked_parent;
            $directory['subdir'] = '';
            $directory['baseurl'] = $directory['url'] = trailingslashit($uploads['baseurl']) . basename($linked_parent);
            return $directory;
        });
        try {
            $linked_attached = trailingslashit($linked_parent) . basename($attached);
            $this->assertTrue(update_attached_file($created['attachment'], $linked_attached));
            $this->assertSame(wp_normalize_path($linked_attached), wp_normalize_path(get_attached_file($created['attachment'])));
            $this->assertTrue(is_link(dirname($linked_attached)));
            $this->assertFalse(is_link($linked_attached));
            $linked_original = trailingslashit($linked_parent) . basename($created['file']);
            $owned = SUPER_Ajax::build_owned_upload(
                $form_id, 'documents', $linked_original, $created['mime'],
                wp_get_attachment_url($created['attachment']), $created['attachment'], $linked_parent, false
            );
            $this->assertIsArray($owned);
            $this->assertSame($created['file'], $owned['file']);
            $this->assertSame(wp_normalize_path(realpath($created['root'])), $owned['allowed_root']);
            $this->assertSame(basename($created['file']), $owned['basename']);
            $this->assertSame(filesize($created['file']), $owned['size']);
            $this->assertTrue($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
            // Direct attachments cover the old LTS behavior; processed originals
            // separately exercise WordPress's lexical original-path normalization.
            if( $attached!==$created['file'] ) {
                $this->assertSame($attached, $owned['attached_file']);
                $this->assertSame(wp_normalize_path($linked_original), wp_normalize_path(wp_get_original_image_path($created['attachment'])));
                $this->assertTrue(SUPER_Ajax::attachment_upload_value_is_valid($created['attachment'], basename($created['file'])));
                $this->assertFalse(SUPER_Ajax::attachment_upload_value_is_valid($created['attachment'], 'other/' . basename($created['file'])));
            }
            $outside = SUPER_Ajax::build_owned_upload(
                $form_id, 'documents', $linked_original, $created['mime'],
                wp_get_attachment_url($created['attachment']), $created['attachment'], $uploads['basedir'], false
            );
            $this->assertFalse($outside, 'A directory alias must not widen the authorized canonical root.');
        } finally {
            remove_filter('upload_dir', $upload_filter);
            update_attached_file($created['attachment'], $attached);
            unlink($linked_parent);
        }
    }

    public function test_symlinked_upload_parent_accepts_unprocessed_image() {
        $this->assert_symlinked_upload_parent_image(320, 240);
    }

    public static function symlinked_parent_processed_cases() {
        return array(
            'scaled original' => array(3000, 2000, 1),
            'rotated original' => array(2000, 1500, 6),
        );
    }

    /** @dataProvider symlinked_parent_processed_cases */
    public function test_symlinked_upload_parent_preserves_processed_original_identity( $width, $height, $orientation ) {
        $this->assert_symlinked_upload_parent_image($width, $height, $orientation);
    }

    public function test_symlinked_upload_leaf_remains_rejected() {
        $form_id = $this->create_form('publish', array($this->file_element('documents')));
        $created = $this->image_attachment($form_id, 320, 240);
        $owned = $this->build_image_owned($created);
        $this->assertIsArray($owned);
        $linked_leaf = trailingslashit($created['root']) . 'linked-leaf.jpg';
        $this->assertTrue(symlink($created['file'], $linked_leaf));
        try {
            $linked_input = $created;
            $linked_input['file'] = $linked_leaf;
            $this->assertFalse($this->build_image_owned($linked_input));
            $linked_owned = $owned;
            $linked_owned['file'] = $linked_leaf;
            $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($linked_owned, 0)));
            $this->assertTrue(update_attached_file($created['attachment'], $linked_leaf));
            $this->assertFalse($this->build_image_owned($created));
            $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
            $this->assertFileExists($created['file']);
        } finally {
            update_attached_file($created['attachment'], $created['file']);
            unlink($linked_leaf);
        }
    }

    public function test_symlinked_upload_root_rejects_internal_directory_links() {
        $form_id = $this->create_form('publish', array($this->file_element('documents')));
        $created = $this->image_attachment($form_id, 320, 240);
        $uploads = wp_upload_dir();
        $this->assertEmpty($uploads['error']);
        $this->assertTrue(wp_mkdir_p($uploads['basedir']));
        $linked_parent = trailingslashit($uploads['basedir']) . 'sf-linked-uploads-' . wp_generate_uuid4();
        $internal_link = trailingslashit($created['root']) . 'linked-directory';
        $this->assertTrue(symlink($created['root'], $linked_parent));
        $this->assertTrue(symlink($created['root'], $internal_link));
        $upload_filter = $this->add_upload_filter('upload_dir', static function($directory) use ($linked_parent) {
            $directory['basedir'] = $directory['path'] = $linked_parent;
            $directory['subdir'] = '';
            return $directory;
        });
        try {
            $linked_file = trailingslashit($linked_parent) . basename($created['file']);
            $this->assertTrue(update_attached_file($created['attachment'], $linked_file));
            $owned = SUPER_Ajax::build_owned_upload(
                $form_id, 'documents', $linked_file, $created['mime'],
                wp_get_attachment_url($created['attachment']), $created['attachment'], $linked_parent, false
            );
            $this->assertIsArray($owned);
            $this->assertTrue($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
            $internal_file = trailingslashit($linked_parent) . 'linked-directory/' . basename($created['file']);
            $this->assertSame($created['file'], wp_normalize_path(realpath($internal_file)));
            $this->assertFalse(is_link($internal_file));
            $this->assertTrue(update_attached_file($created['attachment'], $internal_file));
            $this->assertFalse($this->build_image_owned($created));
            $this->assertFalse($this->invoke_ajax_private('owned_upload_is_current', array($owned, 0)));
            $this->assertFalse(SUPER_Ajax::attachment_upload_value_is_valid($created['attachment'], basename($created['file'])));
            $this->assertFileExists($created['file']);
        } finally {
            remove_filter('upload_dir', $upload_filter);
            update_attached_file($created['attachment'], $created['file']);
            unlink($internal_link);
            unlink($linked_parent);
        }
    }
}
