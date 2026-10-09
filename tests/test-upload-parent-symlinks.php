<?php

require_once __DIR__ . '/test-security-upload-00-base.php';

class Test_Super_Forms_Upload_Parent_Symlinks_Security extends Super_Forms_Upload_Security_Test_Case {
    private function image_under_symlinked_uploads_parent( $width=64, $height=48, $orientation=1 ) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        list( $parent, $root ) = $this->create_temporary_root( true );
        $physical_parent = $root . '/physical';
        $this->assertTrue( wp_mkdir_p( $physical_parent . '/uploads/slot' ) );
        $parent_alias = $root . '/uploads-parent';
        $this->assertTrue( symlink( $physical_parent, $parent_alias ) );
        $upload_root = $parent_alias . '/uploads';
        $this->add_upload_filter( 'upload_dir', static function( $dirs ) use ( $upload_root ) {
            $dirs['basedir'] = $upload_root;
            $dirs['path'] = $upload_root . '/slot';
            $dirs['baseurl'] = site_url( '/symlink-fixture-uploads' );
            $dirs['url'] = $dirs['baseurl'] . '/slot';
            $dirs['subdir'] = '/slot';
            $dirs['error'] = false;
            return $dirs;
        } );
        $filename = $upload_root . '/slot/camera.jpg';
        $image = imagecreatetruecolor( $width, $height );
        $this->assertNotFalse( $image );
        try {
            $this->assertTrue( imagejpeg( $image, $filename, 85 ) );
        } finally {
            imagedestroy( $image );
        }
        if( $orientation!==1 ) {
            $this->assertSame( 6, $orientation );
            $exif = "Exif\0\0" . hex2bin( '49492a0008000000010012010300010000000600000000000000' );
            $jpeg = file_get_contents( $filename );
            $this->assertNotFalse( file_put_contents( $filename, substr( $jpeg, 0, 2 )
                . "\xff\xe1" . pack( 'n', strlen( $exif ) + 2 ) . $exif . substr( $jpeg, 2 ) ) );
            $this->assertSame( 6, (int) wp_read_image_metadata( $filename )['orientation'] );
        }
        $form_id = $this->create_form( 'publish', array( $this->file_element( 'documents' ) ) );
        $attachment_id = wp_insert_attachment( array(
            'post_mime_type' => 'image/jpeg', 'post_title' => 'Aliased uploads parent', 'post_status' => 'inherit',
        ), $filename, 0 );
        $this->assertTrue( is_int( $attachment_id ) && $attachment_id>0 );
        $this->attachment_ids[] = $attachment_id;
        add_post_meta( $attachment_id, 'super-forms-form-upload-file', true );
        add_post_meta( $attachment_id, '_super_forms_upload_form_id', $form_id );
        add_post_meta( $attachment_id, '_super_forms_upload_field', 'documents' );
        $metadata = wp_generate_attachment_metadata( $attachment_id, $filename );
        $this->assertIsArray( $metadata );
        wp_update_attachment_metadata( $attachment_id, $metadata );
        clearstatcache();
        return array( 'form_id' => $form_id, 'attachment' => $attachment_id, 'file' => $filename,
            'root' => $upload_root, 'canonical_file' => wp_normalize_path( realpath( $filename ) ),
            'canonical_root' => wp_normalize_path( realpath( $upload_root ) ), 'metadata' => $metadata );
    }

    private function build_alias_owned( $created, $filename=null ) {
        return SUPER_Ajax::build_owned_upload(
            $created['form_id'], 'documents', $filename===null ? $created['file'] : $filename,
            'image/jpeg', wp_get_attachment_url( $created['attachment'] ), $created['attachment'],
            $created['root'], ''
        );
    }

    public function test_small_image_under_symlinked_uploads_parent_builds_and_revalidates_owned_identity() {
        $created = $this->image_under_symlinked_uploads_parent();
        $this->assertNotSame( get_attached_file( $created['attachment'] ), $created['canonical_file'] );
        $owned = $this->build_alias_owned( $created );
        $this->assertIsArray( $owned );
        $this->assertSame( $created['canonical_file'], $owned['file'] );
        $this->assertSame( $created['canonical_root'], $owned['allowed_root'] );
        $this->assertTrue( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        $token = $this->issue_receipt( $owned );
        $this->assertIsArray( $this->invoke_ajax_private( 'inspect_upload_receipt', array(
            $token, $created['form_id'], 'documents',
        ) ) );
    }

    public function test_processed_original_under_symlinked_uploads_parent_builds_revalidates_and_maps_exact_original() {
        foreach( array( array( 3000, 2000, 1 ), array( 2000, 1500, 6 ) ) as $case ) {
            $created = $this->image_under_symlinked_uploads_parent( $case[0], $case[1], $case[2] );
            $wordpress_original = wp_get_original_image_path( $created['attachment'] );
            $this->assertNotSame( $wordpress_original, $created['canonical_file'] );
            $this->assertSame( $created['canonical_file'], wp_normalize_path( realpath( $wordpress_original ) ) );
            $this->assertNotSame( $created['canonical_file'], wp_normalize_path( realpath( get_attached_file( $created['attachment'] ) ) ) );
            $owned = $this->build_alias_owned( $created );
            $this->assertIsArray( $owned );
            $this->assertSame( $created['canonical_file'], $owned['file'] );
            $this->assertTrue( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
            $token = $this->issue_receipt( $owned );
            $this->assertIsArray( $this->invoke_ajax_private( 'inspect_upload_receipt', array(
                $token, $created['form_id'], 'documents',
            ) ) );
            $record = SUPER_Ajax::owned_upload_file_record( $owned );
            $mapping = new ReflectionMethod( 'SUPER_Register_Login', 'resolve_custom_meta_value' );
            $mapping->setAccessible( true );
            $this->assertSame( $created['attachment'], $mapping->invokeArgs( null, array(
                'documents', array( 'documents' => array( 'type' => 'files', 'files' => array( $record ) ) ), array(), $created['form_id'], array( $owned ),
            ) ) );
            $mismatch = $record;
            $mismatch['value'] = 'not-the-original.jpg';
            $this->assertInstanceOf( 'WP_Error', $mapping->invokeArgs( null, array(
                'documents', array( 'documents' => array( 'type' => 'files', 'files' => array( $mismatch ) ) ), array(), $created['form_id'], array( $owned ),
            ) ) );
        }
    }

    public function test_internal_directory_alias_below_an_aliased_uploads_root_cannot_acquire_authority() {
        foreach( array( array( 64, 48 ), array( 3000, 2000 ) ) as $case ) {
            $created = $this->image_under_symlinked_uploads_parent( $case[0], $case[1] );
            $owned = $this->build_alias_owned( $created );
            $this->assertIsArray( $owned );
            $saved_attached = get_post_meta( $created['attachment'], '_wp_attached_file', true );
            $attached = get_attached_file( $created['attachment'] );
            $internal_alias = $created['root'] . '/internal-directory';
            $this->assertTrue( symlink( $created['root'] . '/slot', $internal_alias ) );
            try {
                update_post_meta( $created['attachment'], '_wp_attached_file', $internal_alias . '/' . basename( $attached ) );
                $this->assertFalse( $this->build_alias_owned( $created ) );
                $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
                $this->assertFalse( SUPER_Ajax::attachment_upload_value_is_valid( $created['attachment'], basename( $created['file'] ) ) );
                update_post_meta( $created['attachment'], '_wp_attached_file', $saved_attached );
                if( !empty( $created['metadata']['original_image'] ) ) {
                    $filter = $this->add_upload_filter( 'wp_get_original_image_path', static function() use ( $internal_alias, $created ) {
                        return $internal_alias . '/' . basename( $created['file'] );
                    } );
                    try {
                        $this->assertFalse( $this->build_alias_owned( $created ) );
                        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
                        $this->assertFalse( SUPER_Ajax::attachment_upload_value_is_valid( $created['attachment'], basename( $created['file'] ) ) );
                    } finally {
                        $this->remove_upload_filter( 'wp_get_original_image_path', $filter );
                    }
                }
            } finally {
                update_post_meta( $created['attachment'], '_wp_attached_file', $saved_attached );
                unlink( $internal_alias );
            }
            $this->assertTrue( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        }
    }

    public function test_symlinked_attached_and_original_leaves_remain_rejected_under_aliased_uploads_parent() {
        $created = $this->image_under_symlinked_uploads_parent( 3000, 2000 );
        $owned = $this->build_alias_owned( $created );
        $this->assertIsArray( $owned );
        $saved_attached = get_post_meta( $created['attachment'], '_wp_attached_file', true );
        $attached = get_attached_file( $created['attachment'] );
        $attached_link = dirname( $attached ) . '/linked-attached.jpg';
        $this->assertTrue( symlink( $attached, $attached_link ) );
        try {
            update_post_meta( $created['attachment'], '_wp_attached_file', $attached_link );
            $this->assertFalse( $this->build_alias_owned( $created ) );
            $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        } finally {
            update_post_meta( $created['attachment'], '_wp_attached_file', $saved_attached );
            unlink( $attached_link );
        }
        $original_backup = dirname( $created['file'] ) . '/original-backup.jpg';
        $this->assertTrue( rename( $created['file'], $original_backup ) );
        $this->assertTrue( symlink( $original_backup, $created['file'] ) );
        try {
            clearstatcache();
            $this->assertFalse( $this->build_alias_owned( $created ) );
            // Even a direct canonical target cannot turn the metadata's linked
            // original leaf into authority inside the shared validator.
            $this->assertFalse( $this->build_alias_owned( $created, wp_normalize_path( realpath( $original_backup ) ) ) );
            $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        } finally {
            unlink( $created['file'] );
            $this->assertTrue( rename( $original_backup, $created['file'] ) );
            clearstatcache();
        }
        $metadata = $created['metadata'];
        $metadata['original_image'] = '../slot/' . basename( $created['file'] );
        wp_update_attachment_metadata( $created['attachment'], $metadata );
        $this->assertFalse( $this->build_alias_owned( $created ) );
        $this->assertFalse( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
        wp_update_attachment_metadata( $created['attachment'], $created['metadata'] );
        $this->assertTrue( $this->invoke_ajax_private( 'owned_upload_is_current', array( $owned, 0 ) ) );
    }
}
