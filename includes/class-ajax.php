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
        $data = array(
            'secret' => $settings['form_recaptcha_secret'], 
            'response' => $_REQUEST['response']
        );
        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query( $data ),
            ),
        );
        $context  = stream_context_create( $options );
        $result = file_get_contents( $url, false, $context );
        $result = json_decode( $result, true );
        if( $result['success'] ) {
            echo 1; //Success!
        }else{
            echo 0; //Error!
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
        update_option( 'super_settings', $array );
        $domain = sanitize_text_field($_SERVER['SERVER_NAME']);
        $url = 'http://f4d.nl/super-forms/?api=license-check&key=' . $array['license'] . '&domain=' . $domain;
        $curl_handle=curl_init();
        curl_setopt( $curl_handle, CURLOPT_URL, $url);
        curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl_handle, CURLOPT_USERAGENT, 'Super Forms' );
        $result = curl_exec( $curl_handle );
        curl_close( $curl_handle );
        if( $result==false ) {
            $result = 'offline';
        }
        if($result=='activated'){
            update_option( 'super_la', 1 );
            $error=false;
            $msg = __( 'Plugin is activated!', 'super-forms' );
        }else{
            $error=true;
            if($result=='activate'){
                update_option( 'super_la', 1 );
                $error=false;
                $msg = __( 'Product successfully activated!', 'super-forms' );
            }
            if($result=='used'){
                update_option( 'super_la', 0 );
                $msg = __( 'Purchase code already used on an other domain, could not activate the plugin!<br />Please <a target="_blank" href="http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866">purchase another license</a> in order to activate the plugin..', 'super-forms' );
            }
            if($result=='invalid'){
                update_option( 'super_la', 0 );
                $msg = __( 'Invalid purchase code, please check and try again!', 'super-forms' );
            }                
            if($result=='error'){
                update_option( 'super_la', 0 );
                $msg = __( 'Either the Purchase Code was empty or something else went wrong', 'super-forms' );
            }
            if($result=='offline'){
                update_option( 'super_la', 1 );
                $msg = __( 'Could\'t connect database to check Purchase Code. Plugin activated manually.', 'super-forms' );
            }            
        }
        SUPER_Common::output_error(
            $error,
            $msg
        );
        die();
        
    }


    /** 
     *  Deactivate plugin
     *
     *  @since      1.1.5
    */
    public static function deactivate() {
        $array = array();
        foreach( $_REQUEST['data'] as $k => $v ) {
            $array[$v['name']] = $v['value'];
        }
        $license = $array['license'];
        $domain = sanitize_text_field( $_SERVER['SERVER_NAME'] );
        
        $url = 'http://f4d.nl/super-forms/?api=license-deactivate&key=' . $license . '&domain=' . $domain;
        $curl_handle=curl_init();
        curl_setopt( $curl_handle, CURLOPT_URL, $url);
        curl_setopt( $curl_handle, CURLOPT_CONNECTTIMEOUT, 2 );
        curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl_handle, CURLOPT_USERAGENT, 'Super Forms' );
        $result = curl_exec( $curl_handle );
        curl_close( $curl_handle );
        if( $result==false ) {
            $result = 'offline';
        }
        if($result=='deactivate'){
            update_option( 'super_la', 0 );
            $error=false;
            $msg = __( 'Plugin has been deactivated!', 'super-forms' );
        }else{
            $error=true;
            if($result=='invalid'){
                update_option( 'super_la', 0 );
                $msg = __( 'Invalid purchase code, please check and try again!', 'super-forms' );
            }                
            if($result=='error'){
                update_option( 'super_la', 0 );
                $msg = __( 'Either the Purchase Code was empty or something else went wrong', 'super-forms' );
            }
            if($result=='offline'){
                update_option( 'super_la', 1 );
                $msg = __( 'Could\'t connect database to check Purchase Code. Plugin activated manually.', 'super-forms' );
            }            
        }
        SUPER_Common::output_error(
            $error,
            $msg
        );
        die();
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
                            if( isset( $fv['desc'] ) ) $result .= '<i class="info popup" title="" data-placement="bottom" data-original-title="' . $fv['desc'] . '"></i>';
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

        include_once(SUPER_PLUGIN_DIR.'/includes/class-shortcodes.php' );
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
        
        if( ( $settings['save_contact_entry']=='yes' ) || ( $settings['send']=='yes' ) || ( $settings['confirm']=='yes' ) ) {
            if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
                $delete_dirs = array();
                foreach( $data as $k => $v ) {
                    if( $v['type']=='files' ) {
                        if( ( isset( $v['files'] ) ) && ( count( $v['files'] )!=0 ) ) {
                            foreach( $v['files'] as $key => $value ) {
                                $domain_url_without_http = str_replace( 'http://', '', SUPER_PLUGIN_FILE );
                                $domain_url_without_http = str_replace( 'https://', '', $domain_url_without_http );
                                $image_url_without_http = str_replace( 'http://', '', $value['url'] );
                                $image_url_without_http = str_replace( 'https://', '', $image_url_without_http );
                                $image_url_without_http = str_replace( $domain_url_without_http, '', $image_url_without_http );
                                $source = urldecode( SUPER_PLUGIN_DIR . '/' . $image_url_without_http );
                                $wp_upload_dir = wp_upload_dir();
                                $folder = $wp_upload_dir['basedir'] . $wp_upload_dir["subdir"];
                                $unique_folder = SUPER_Common::generate_random_folder($folder);
                                $newfile = $unique_folder . '/' . basename( $source );
                                if ( !copy( $source, $newfile ) ) {
                                    SUPER_Common::output_error(
                                        $error = true,
                                        $msg = __( 'Failed to copy', 'super-forms' ) . $file,
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
        }

        if( $settings['save_contact_entry']=='yes' ) {
            $post = array(
                'post_status' => 'super_unread',
                'post_type'  => 'super_contact_entry' ,
            ); 
            $contact_entry_id = wp_insert_post($post); 
            add_post_meta( $contact_entry_id, '_super_contact_entry_data', $data);
            add_post_meta( $contact_entry_id, '_super_contact_entry_ip', SUPER_Common::real_ip() );
            $contact_entry = array(
                'ID' => $contact_entry_id,
                'post_title'  => __( 'Contact entry', 'super-forms' ) . ' ' . $contact_entry_id,
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
             *  @param  post   $_POST
             *  @param  array  $settings
             *
             *  @since      1.0.2
            */
            do_action( 'super_before_email_success_msg_action', array( 'post'=>$_POST, 'settings'=>$settings ) );

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