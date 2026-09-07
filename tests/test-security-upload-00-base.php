<?php

abstract class Super_Forms_Upload_Security_Test_Case extends WP_UnitTestCase {
    protected $receipt_tokens = array();
    protected $attachment_ids = array();
    protected $temporary_parents = array();
    protected $added_filters = array();
    protected $owned_cleanup = array();

    private $original_post;
    private $original_request;
    private $original_files;
    private $original_cookie_exists;
    private $original_cookie_value;
    private $original_settings_exists;
    private $original_settings;
    private $original_global_settings_exists;
    private $original_global_settings;

    public function set_up() {
        parent::set_up();

        $this->original_post = $_POST;
        $this->original_request = $_REQUEST;
        $this->original_files = $_FILES;
        $this->original_cookie_exists = array_key_exists( '_sfs_id', $_COOKIE );
        $this->original_cookie_value = $this->original_cookie_exists ? $_COOKIE['_sfs_id'] : null;

        $missing = '__super_forms_upload_security_missing__';
        $settings = get_option( 'super_settings', $missing );
        $this->original_settings_exists = ( $settings !== $missing );
        $this->original_settings = $settings;

        $forms = SUPER_Forms();
        $this->original_global_settings_exists = isset( $forms->global_settings );
        $this->original_global_settings = $this->original_global_settings_exists ? $forms->global_settings : null;

        $_POST = array();
        $_REQUEST = array();
        $_FILES = array();
        unset( $_COOKIE['_sfs_id'] );
        wp_set_current_user( 0 );
        unset( $GLOBALS['super_upload_dir'] );
        remove_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ) );
    }

    public function tear_down() {
        if( class_exists('SUPER_Ajax') ) {
            $this->invoke_ajax_private( 'disarm_owned_upload_cleanup' );
        }
        wp_set_current_user( 0 );
        remove_filter( 'upload_dir', array( 'SUPER_Forms', 'filter_upload_dir' ) );
        unset( $GLOBALS['super_upload_dir'] );

        foreach( array_reverse( $this->added_filters ) as $filter ) {
            remove_filter( $filter[0], $filter[1], $filter[2] );
        }

        foreach( array_unique( $this->receipt_tokens ) as $token ) {
            $hash = hash( 'sha256', $token );
            $receipt = get_option( '_super_upload_receipt_' . $hash, false );
            if( is_array( $receipt ) && isset( $receipt['expires'] ) && is_int( $receipt['expires'] ) ) {
                wp_unschedule_event( $receipt['expires'] + 1, 'super_cleanup_upload_receipt', array( $hash ) );
            }
            delete_option( '_super_upload_receipt_' . $hash );
            delete_option( '_super_upload_receipt_claim_' . $hash );
        }

        foreach( $this->owned_cleanup as $owned ) {
            if( !is_array( $owned ) || empty( $owned['storage'] ) ) continue;
            if( $owned['storage'] === 'attachment' && !empty( $owned['attachment'] ) ) {
                $this->attachment_ids[] = absint( $owned['attachment'] );
            } elseif( $owned['storage'] === 'custom' && !empty( $owned['custom_path'] ) && !empty( $owned['allowed_root'] ) ) {
                SUPER_Common::delete_file( $owned['custom_path'], $owned['allowed_root'] );
            }
        }

        foreach( array_unique( $this->attachment_ids ) as $attachment_id ) {
            if( get_post_type( $attachment_id ) === 'attachment' ) {
                wp_delete_attachment( $attachment_id, true );
            }
        }

        foreach( array_reverse( array_unique( $this->temporary_parents ) ) as $parent ) {
            $this->remove_test_tree( $parent );
        }

        if( $this->original_settings_exists ) {
            update_option( 'super_settings', $this->original_settings, false );
        } else {
            delete_option( 'super_settings' );
        }
        $forms = SUPER_Forms();
        if( $this->original_global_settings_exists ) {
            $forms->global_settings = $this->original_global_settings;
        } else {
            unset( $forms->global_settings );
        }
        if( isset( $_COOKIE['_sfs_id'] ) && is_string( $_COOKIE['_sfs_id'] ) ) {
            $current_session_id = $_COOKIE['_sfs_id'];
            if( !$this->original_cookie_exists || $current_session_id !== $this->original_cookie_value ) {
                delete_option( '_sfsdata_' . $current_session_id );
            }
        }

        if( $this->original_cookie_exists ) {
            $_COOKIE['_sfs_id'] = $this->original_cookie_value;
        } else {
            unset( $_COOKIE['_sfs_id'] );
        }
        $_POST = $this->original_post;
        $_REQUEST = $this->original_request;
        $_FILES = $this->original_files;

        parent::tear_down();
    }

    protected function invoke_ajax_private( $method_name, $arguments=array() ) {
        $method = new ReflectionMethod( 'SUPER_Ajax', $method_name );
        $method->setAccessible( true );
        return $method->invokeArgs( null, $arguments );
    }

    protected function add_upload_filter( $tag, $callback, $priority=10, $accepted_args=1 ) {
        add_filter( $tag, $callback, $priority, $accepted_args );
        $this->added_filters[] = array( $tag, $callback, $priority );
        return $callback;
    }

    protected function remove_upload_filter( $tag, $callback, $priority=10 ) {
        remove_filter( $tag, $callback, $priority );
        foreach( $this->added_filters as $key => $filter ) {
            if( $filter[0] === $tag && $filter[1] === $callback && $filter[2] === $priority ) {
                unset( $this->added_filters[$key] );
            }
        }
    }
    protected function strict_security_pcntl_required() {
        $flag = getenv( 'SUPER_FORMS_STRICT_SECURITY_TESTS' );
        return is_string($flag) && $flag!=='' && $flag!=='0' && strtolower($flag)!=='false';
    }

    protected function require_process_forking( $message ) {
        if( function_exists( 'pcntl_fork' ) && function_exists( 'pcntl_waitpid' ) && function_exists( 'pcntl_exec' ) ) {
            return;
        }
        if( $this->strict_security_pcntl_required() ) {
            $this->fail( $message );
        }
        $this->markTestSkipped( $message );
    }
    protected function bootstrap_shared_anonymous_session() {
        if( get_current_user_id()!==0 ) {
            return;
        }
        if( isset( $_COOKIE['_sfs_id'] ) && is_string( $_COOKIE['_sfs_id'] ) && $_COOKIE['_sfs_id']!=='' ) {
            return;
        }
        $session_id = SUPER_Common::startClientSession( array( 'force' => true ) );
        $this->assertTrue( is_string( $session_id ) && $session_id!=='' );
        $this->assertSame( $session_id, $_COOKIE['_sfs_id'] );
    }


    protected function configure_csrf( $value ) {
        $settings = array(
            'csrf_check' => $value,
            'email_reminder_amount' => 0,
        );
        update_option( 'super_settings', $settings, false );
        SUPER_Forms()->global_settings = $settings;
    }

    protected function create_form( $status='publish', $elements=array(), $settings=array(), $author=0 ) {
        $form_id = self::factory()->post->create( array(
            'post_type' => 'super_form',
            'post_status' => $status,
            'post_author' => $author,
        ) );
        update_post_meta( $form_id, '_super_elements', $elements );
        update_post_meta( $form_id, '_super_form_settings', $settings );
        return $form_id;
    }

    protected function file_element( $name='documents', $overrides=array() ) {
        return array(
            'tag' => 'file',
            'data' => array_merge( array(
                'name' => $name,
                'extensions' => 'jpg|jpeg|png|pdf',
                'filesize' => '5',
            ), $overrides ),
        );
    }

    protected function set_request( $form_id, $data=array(), $files=array(), $extra_post=array() ) {
        if( !isset($data['hidden_form_id']) ) {
            $data['hidden_form_id'] = array(
                'name' => 'hidden_form_id',
                'value' => (string) $form_id,
                'type' => 'form_id',
            );
        }
        if( !isset($data['hidden_contact_entry_id']) ) {
            $data['hidden_contact_entry_id'] = array(
                'name' => 'hidden_contact_entry_id',
                'value' => isset($extra_post['entry_id']) && absint($extra_post['entry_id'])
                    ? (string)absint($extra_post['entry_id'])
                    : '',
                'type' => 'entry_id',
            );
        }
        $_POST = array_merge( array(
            'form_id' => (string) $form_id,
            'data' => wp_slash( wp_json_encode( $data ) ),
        ), $extra_post );
        $_REQUEST = $_POST;
        $_FILES = $files;
    }
    protected function set_submit_request( $form_id, $data=array(), $extra_post=array() ) {
        $this->set_request(
            $form_id,
            $data,
            array(),
            array_merge(
                array(
                    'action' => 'super_submit_form',
                    'i18n' => '',
                ),
                $extra_post
            )
        );
    }
    protected function assertRenderedInputValue( $html, $name, $value ) {
        $pattern = '/<input\b[^>]*(?:name="' . preg_quote( (string) $name, '/' ) . '"[^>]*value="' . preg_quote( (string) $value, '/' ) . '"|value="' . preg_quote( (string) $value, '/' ) . '"[^>]*name="' . preg_quote( (string) $name, '/' ) . '")[^>]*>/';
        $this->assertSame( 1, preg_match( $pattern, $html ), $html );
    }


    protected function run_dying_handler( $callback, $bootstrap_session=true ) {
        $this->require_process_forking( 'The raw-die endpoint regression requires pcntl fork, wait, and exec support.' );
        if( $bootstrap_session ) {
            $this->bootstrap_shared_anonymous_session();
        }


        $capture = tempnam( sys_get_temp_dir(), 'sf-upload-die-' );
        $this->assertNotFalse( $capture );
        $pid = pcntl_fork();
        $this->assertNotSame( -1, $pid );

        if( $pid === 0 ) {
            $returned = false;
            ob_start( static function( $buffer ) use ( $capture ) {
                file_put_contents( $capture, $buffer, FILE_APPEND | LOCK_EX );
                return '';
            } );
            // Exec at shutdown avoids destructing the inherited mysqli connection, which
            // would roll back the parent WP_UnitTestCase transaction after a bare die().
            register_shutdown_function( static function() use ( &$returned ) {
                $last_error = error_get_last();
                $fatal = $last_error && in_array(
                    $last_error['type'],
                    array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ),
                    true
                );
                $status = ( ! $returned && ! $fatal ) ? 0 : 97;
                while( ob_get_level() > 0 ) {
                    @ob_end_flush();
                }
                pcntl_exec( PHP_BINARY, array( '-r', 'exit(' . $status . ');' ) );
            } );
            try {
                call_user_func( $callback );
                $returned = true;
            } catch( Throwable $e ) {
                echo get_class($e) . ': ' . $e->getMessage();
                $returned = true;
            }
            exit( 97 );
        }

        $status = 0;
        pcntl_waitpid( $pid, $status );
        global $wpdb;
        if( isset( $wpdb ) && method_exists( $wpdb, 'check_connection' ) ) {
            $wpdb->check_connection( false );
        }
        wp_cache_flush();

        $output = file_get_contents( $capture );
        unlink( $capture );
        return array(
            'output' => ( $output === false ) ? '' : $output,
            'exited' => pcntl_wifexited( $status ),
            'status' => pcntl_wifexited( $status ) ? pcntl_wexitstatus( $status ) : null,
        );
    }

    protected function assert_handler_rejected_with( $callback, $message ) {
        $result = $this->run_dying_handler( $callback );
        $this->assertTrue( $result['exited'] );
        $this->assertSame( 0, $result['status'], $result['output'] );
        $decoded = json_decode( $result['output'], true );
        $this->assertTrue( is_array( $decoded ), $result['output'] );
        $this->assertTrue( $decoded['error'] );
        $this->assertStringContainsString( $message, wp_strip_all_tags( $decoded['msg'] ) );
        return $decoded;
    }

    protected function create_temporary_root( $outside_abspath=false ) {
        $base = $outside_abspath
            ? dirname( untrailingslashit( wp_normalize_path( ABSPATH ) ) )
            : sys_get_temp_dir();
        $parent = trailingslashit( $base ) . 'sf-upload-security-' . str_replace( '-', '', wp_generate_uuid4() );
        $root = trailingslashit( $parent ) . 'owned';
        $this->assertTrue( wp_mkdir_p( $root ) );
        $parent_real = realpath( $parent );
        $root_real = realpath( $root );
        $this->assertNotFalse( $parent_real );
        $this->assertNotFalse( $root_real );
        $this->temporary_parents[] = $parent_real;
        return array( $parent_real, $root_real );
    }

    protected function create_owned_upload( $form_id, $field='documents', $attachment=false, $contents='owned upload' ) {
        list( $parent, $root ) = $this->create_temporary_root();
        $filename = trailingslashit( $root ) . 'file-' . str_replace( '-', '', wp_generate_uuid4() ) . '.jpg';
        $this->assertNotFalse( file_put_contents( $filename, $contents ) );
        $attachment_id = 0;
        if( $attachment ) {
            $attachment_id = wp_insert_attachment( array(
                'post_mime_type' => 'image/jpeg',
                'post_title' => 'Owned upload',
                'post_content' => '',
                'post_status' => 'inherit',
            ), $filename, 0 );
            $this->assertTrue( is_int( $attachment_id ) && $attachment_id > 0 );
            $this->attachment_ids[] = $attachment_id;
            add_post_meta( $attachment_id, 'super-forms-form-upload-file', true );
            add_post_meta( $attachment_id, '_super_forms_upload_form_id', $form_id );
            add_post_meta( $attachment_id, '_super_forms_upload_field', $field );
        }

        $owned = $this->invoke_ajax_private( 'build_owned_upload', array(
            $form_id,
            $field,
            $filename,
            'image/jpeg',
            'https://example.test/' . basename( $filename ),
            $attachment_id,
            $root,
            filesize( $filename ),
            '',
        ) );
        $this->assertTrue( is_array( $owned ) );
        return array(
            'owned' => $owned,
            'parent' => $parent,
            'root' => $root,
            'file' => $filename,
            'attachment' => $attachment_id,
        );
    }

    protected function issue_receipt( $owned ) {
        $token = $this->invoke_ajax_private( 'issue_upload_receipt', array( $owned ) );
        $this->assertTrue( is_string( $token ) );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/D', $token );
        $this->receipt_tokens[] = $token;
        return $token;
    }

    protected function track_owned_cleanup( $owned ) {
        $this->owned_cleanup[] = $owned;
    }

    private function remove_test_tree( $path ) {
        if( is_link( $path ) || is_file( $path ) ) {
            unlink( $path );
            return;
        }
        if( !is_dir( $path ) ) return;
        $entries = scandir( $path );
        if( $entries === false ) return;
        foreach( $entries as $entry ) {
            if( $entry === '.' || $entry === '..' ) continue;
            $this->remove_test_tree( trailingslashit( $path ) . $entry );
        }
        rmdir( $path );
    }
}
