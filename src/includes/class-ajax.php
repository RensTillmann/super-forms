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
            'mark_unread'                   => false,
            'mark_read'                     => false,
            'delete_contact_entry'          => false,
            'save_settings'                 => false,
            'get_element_builder_html'      => false,
            'load_element_settings'         => false,
            'save_form'                     => false,
            'delete_form'                   => false,
            'load_preview'                  => false,
            'switch_language'               => false, // @since 4.7.0

            'send_email'                    => true,
            'language_switcher'             => true,  // @since 4.7.0

            'load_default_settings'         => false,
            'import_global_settings'        => false,
            'export_entries'                => false, // @since 1.1.9
            'prepare_contact_entry_import'  => false, // @since 1.2.6
            'import_contact_entries'        => false, // @since 1.2.6

            'demos_install_item'            => false, // @since 1.2.8

            'get_entry_export_columns'      => false, // @since 1.7
            'export_selected_entries'       => false, // @since 1.7
            'update_contact_entry'          => false, // @since 1.7

            'export_forms'                  => false, // @since 1.9
            'start_forms_import'            => false, // @since 1.9

            'populate_form_data'            => true,  // @since 2.2.0
            'search_wc_orders'              => true,

            'calculate_distance'            => true,  // @since 3.1.0
            'restore_backup'                => false, // @since 3.1.0
            'delete_backups'                => false, // @since 3.1.0

            'save_form_progress'            => true,  // @since 3.2.0

            'bulk_edit_entries'             => false, // @since 3.4.0
            'reset_submission_counter'      => false, // @since 3.4.0

            'undo_redo'                     => false, // @since 3.8.0

            'reset_user_submission_counter' => false, // @since 3.8.0

            'print_custom_html'             => true, // @since 3.9.0
            
            'export_single_form'            => false, // @since 4.0.0
            'import_single_form'            => false, // @since 4.0.0
            'reset_form_settings'           => false, // @since 4.0.0
            'tutorial_do_not_show_again'    => false, // @since 4.0.0

            'update_unique_code'            => true, // @since 4.9.46

            'api_cancel_subscription'       => false,
            'api_start_trial'               => false,
            'api_checkout'                  => false,
            'api_register_user'             => false,
            'api_login_user'                => false,
            'api_send_reset_password_email' => false,
            'api_reset_password'            => false,
            'api_logout_user'               => false,
            'api_verify_code'               => false,
            'api_auth'                      => false,
            'api_submit_feedback'           => false,

        );
        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }
    }

    public static function api_get_auth(){
        if (isset($_COOKIE['super_forms'])) {
            return ($_COOKIE['super_forms']);
        }
		return array("wp_admin" => "false");
    }
    public static function api_auth(){
        $auth = $_POST['auth'];
        $result = setcookie(
            'super_forms[wp_admin]', // name
            $auth, // value
            time()+60*120, // expires after 15 minutes
            '',  // path
            '', // domain
            false, // secure (many WP dashboard might not have valid certificate, or are not forced to https protocol)
            true // httponly
        );
        echo ($result===true ? 'true' : 'false');
        die();
    }
    public static function api_submit_feedback() {
        $custom_args = array(
            'body' => (array(
                'addon_title' => $_POST['addon_title'],
                'feedback' => $_POST['feedback'],
                'email' => $_POST['email']
            ))
        );
        self::api_do_request('feedback/submit', $custom_args);
    }
    public static function api_verify_code() {
        $custom_args = array(
            'body' => (array(
                'code' => $_POST['code']
            ))
        );
        self::api_do_request('verify/code', $custom_args);
    }
    public static function api_register_user() {
        $custom_args = array(
            'body' => (array(
                'email' => $_POST['email'],
                'password' => $_POST['password']
            ))
        );
        self::api_do_request('register', $custom_args);
    }
    public static function api_send_reset_password_email() {
        $custom_args = array(
            'body' => (array(
                'email' => $_POST['email'],
                'data' => $_POST['data']
            ))
        );
        self::api_do_request('send_reset_password_email', $custom_args);
    }
    public static function api_reset_password() {
        $custom_args = array(
            'body' => (array(
                'code' => $_POST['code'],
                'password' => $_POST['password']
            ))
        );
        self::api_do_request('reset_password', $custom_args);
    }
    public static function api_login_user() {
        $custom_args = array(
            'body' => (array(
                'email' => $_POST['email'],
                'password' => $_POST['password']
            ))
        );
        self::api_do_request('login', $custom_args);
    }
    public static function api_logout_user() {
        self::api_do_request('logout', array());
    }
    public static function api_cancel_subscription() {
        $custom_args = array(
            'body' => (array(
                'slug' => $_POST['slug'],
                'data' => $_POST['data']
            ))
        );
        self::api_do_request('addons/cancel', $custom_args);
    }
    public static function api_start_trial() {
        $custom_args = array(
            'body' => (array(
                'slug' => $_POST['slug'],
                'data' => $_POST['data']
            ))
        );
        self::api_do_request('addons/start_trial', $custom_args);
    }
    public static function api_checkout() {
        $custom_args = array(
            'body' => (array(
                'plans' => $_POST['plans'],
                'payment_method_id' => $_POST['payment_method_id'],
                'invoice_id' => $_POST['invoice_id'],
                'data' => $_POST['data']
            ))
        );
        self::api_do_request('addons/checkout', $custom_args);
    }

    public static function api_do_request($route, $custom_args, $method='echo'){
        $args = self::api_default_post_args($custom_args);
        if($route==='logout'){
            setcookie('super_forms[wp_admin]', '', time()-3600);
        }
        $api_endpoint = (isset($_POST['api_endpoint']) ? $_POST['api_endpoint'] : SUPER_API_ENDPOINT);
        $r = wp_remote_post($api_endpoint . '/' . $route, $args);
        $response = self::api_handle_response($r, $args);
        if($method=='return') return $response;
        if($method=='echo') echo $response;
        die();
    }

    public static function api_default_post_args($custom_args){
        $default_args = array(
            'method' => 'POST',
            'timeout' => 45,
            'data_format' => 'body',
            'headers' => array('Content-Type' => 'application/json; charset=utf-8')
        );
        $custom_args['body']['auth'] = self::api_get_auth();
        $custom_args['body'] = json_encode($custom_args['body']);
        return array_merge($default_args, $custom_args);
    }

    public static function api_handle_response($r, $args){
        $body = '';
        if ( is_wp_error( $r ) ) {
            $err = $r->get_error_message();
            $body .= '<div class="error notice" style="margin-top:50px;">';
                $body .= '<p>'.esc_html__('Unable to load content, please refresh the page, or try again later.', 'super-forms').'</p>';
                $body .= '<textarea style="display:none;opacity:0;">' . $err . '</textarea>';
            $body .= '</div>';
        }else{
            // Just an API error/notice/success message or HTML payload
            $body .= $r['body'];
        }
        return $body;
    }

    // @since 4.9.46
    // Update unique code when browser "Back" button was pressed
    // Otherwise some browsers might retain the previously generated code
    // Which causes duplicated (none unique) codes
    public static function update_unique_code() {
        $codesettings = wp_unslash($_POST['codesettings']);
        $codesettings = json_decode($codesettings, true);
        echo SUPER_Common::generate_random_code($codesettings);
        die();
    }

    /** 
     *  Switch language from Front-end, reloads all form elements for choosen langauge
     *
     *  @since      4.7.0
    */
    public static function language_switcher() {
        $atts = array(
            'id' => absint($_POST['form_id']),
            'i18n' => sanitize_text_field($_POST['i18n'])
        );
        // @since 4.7.0 - translation RTL
        // check if the translation has enable RTL mode
        $rtl = false;
        $translations = SUPER_Common::get_form_translations($atts['id']);
        if(is_array($translations)){
            if( !empty($translations[$atts['i18n']]) && !empty($translations[$atts['i18n']]['rtl']) ){
                if($translations[$atts['i18n']]['rtl']=='true'){
                    $rtl = true;
                }
            }
        }
        // This will grab only the elements of the form. We can then return it and add it inside the <form> tag
        $data = array(
            'html' => SUPER_Shortcodes::super_form_func( $atts, true ),
            'rtl' => $rtl
        );
        echo json_encode($data);
        die();
    }


    /** 
     *  Switch from builder to language mode in Back-end
     *  This will reload all form elements and also reload form settings
     *
     *  @since      4.7.0
    */
    public static function switch_language() {
        $form_id = absint($_POST['form_id']);
        // Retrieve all settings with the correct default values
        $settings = SUPER_Common::get_form_settings($form_id);

        // @since 4.7.0 - translation
        if(!empty($_POST['i18n'])){
            $i18n = $_POST['i18n'];
            if( (!empty($settings['i18n'])) && (!empty($settings['i18n'][$i18n])) ){
                $settings = array_replace_recursive($settings, $settings['i18n'][$i18n]);
            }
        }

        $form_settings = SUPER_Settings::fields( $settings, 0 );
        $settings_html = '';

        $settings_html .= '<div class="super-form-settings-tabs">';
            $settings_html .= '<select>';
            $i = 0;
            foreach( $form_settings as $key => $value ) { 
                if( ( (!isset($value['hidden'])) || ($value['hidden']==false) || ($value['hidden']==='settings') ) && (!empty($value['name'])) ) {
                    $settings_html .= '<option value="' . $i . '" ' . ( $i==0 ? 'selected="selected"' : '') . '>' . $value['name'] . '</option>';
                    $i++;
                }
            }
            $settings_html .= '</select>';
        $settings_html .= '</div>';
        $counter = 0;

        foreach( $form_settings as $key => $value ) { 
            if( ( (!isset($value['hidden'])) || ($value['hidden']==false) || ($value['hidden']==='settings') ) && (!empty($value['name'])) ) {
                $settings_html .= '<div class="tab-content '.($counter==0 ? 'super-active' : '') . '">';
                if( isset( $value['html'] ) ) {
                    foreach( $value['html'] as $v ) {
                        $settings_html .= $v;
                    }
                }
                if( isset( $value['fields'] ) ) {
                    foreach( $value['fields'] as $k => $v ) {
                        if(empty($_POST['i18n'])){
                            if( ( !isset( $v['hidden'] ) ) || ( $v['hidden']==false ) )  {
                                $filter = '';
                                $parent = '';
                                $filtervalue = '';
                                if( ( isset( $v['filter'] ) ) && ( $v['filter']==true ) ) {
                                    $filter = ' super-filter';
                                    if( isset( $v['parent'] ) ) $parent = ' data-parent="' . esc_attr($v['parent']) . '"';
                                    if( isset( $v['filter_value'] ) ) $filtervalue = ' data-filtervalue="' . esc_attr($v['filter_value']) . '"';
                                }
                                $settings_html .= '<div class="super-field' . $filter . '"' . $parent . '' . $filtervalue;
                                $settings_html .= '>';
                                    if( isset( $v['name'] ) ) $settings_html .= '<div class="field-name">' . esc_html($v['name']) . '</div>';
                                    if( isset( $v['desc'] ) ) $settings_html .= '<i class="info super-tooltip" title="' . esc_attr($v['desc']) . '"></i>';
                                    if( isset( $v['label'] ) ) $settings_html .= '<div class="field-label">' . nl2br($v['label']) . '</div>';
                                    $settings_html .= '<div class="field-input">';
                                        if( !isset( $v['type'] ) ) $v['type'] = 'text';
                                        $settings_html .= call_user_func( array( 'SUPER_Field_Types', $v['type'] ), $k, $v );
                                    $settings_html .= '</div>';
                                $settings_html .= '</div>';
                            }
                        }else{
                            if(empty($v['i18n'])) continue;
                            // Make sure to skip this file if it's source location is invalid
                            if( ( isset( $v['filter'] ) ) && ( $v['filter']==true ) && (isset($v['parent'])) ) {
                                if (strpos($value['fields'][$v['parent']]['default'], $v['filter_value']) === false) {
                                    continue;
                                }
                            }
                            if( ( !isset( $v['hidden'] ) ) || ( $v['hidden']==false ) )  {
                                $settings_html .= '<div class="super-field">';
                                    if( isset( $v['name'] ) ) $settings_html .= '<div class="field-name">' . esc_html($v['name']) . '</div>';
                                    if( isset( $v['desc'] ) ) $settings_html .= '<i class="info super-tooltip" title="' . esc_attr($v['desc']) . '"></i>';
                                    if( isset( $v['label'] ) ) $settings_html .= '<div class="field-label">' . nl2br($v['label']) . '</div>';
                                    $settings_html .= '<div class="field-input">';
                                        if( !isset( $v['type'] ) ) $v['type'] = 'text';
                                        $settings_html .= call_user_func( array( 'SUPER_Field_Types', $v['type'] ), $k, $v );
                                    $settings_html .= '</div>';
                                $settings_html .= '</div>';
                            }
                        }
                    }
                }
                $settings_html .= '</div>';
            }
            $counter++;
        }

        // Retrieve all form elements
        $elements = get_post_meta( $form_id, '_super_elements', true );
        $shortcodes = SUPER_Shortcodes::shortcodes();
        $elements_html = SUPER_Common::generate_backend_elements($form_id, $shortcodes, $elements);

        // Return elements and settings
        $data = array(
            'elements' => $elements_html,
            'settings' => $settings_html
        );
        echo json_encode($data);
        die();
    }


    /** 
     *  Do not show intro tutorial
     *
     *  @since      4.0.0
    */
    public static function tutorial_do_not_show_again() {
        $status = sanitize_text_field($_POST['status']);
        if($status==='false'){
            $status = 'true';
        }else{
            $status = 'false';
        }
        update_option( 'super_skip_tutorial', $status );
        die();
    }

    /** 
     *  Replace {tags} for custom HTML print buttons
     *
     *  @since      3.9.0
    */
    public static function print_custom_html() {
        $file_id = absint($_POST['file_id']);
        $file = wp_get_attachment_url($file_id);
        if($file){
            $html = wp_remote_fopen($file);
            $data = array();
            if( isset( $_POST['data'] ) ) {
                $data = $_POST['data'];
            }
            $settings = SUPER_Common::get_form_settings($data['hidden_form_id']['value']);
            $html = SUPER_Common::email_tags( $html, $data, $settings );
            $html = SUPER_Forms()->email_if_statements( $html, $data );
            echo $html;
        }else{
            echo '404 file with ID #'.$file_id.' not found!';
        }
        die();
    }
    

    /** 
     *  Load form elements after Redo/Undo buttons is clicked
     *
     *  @since      3.8.0
    */
    public static function undo_redo() {
        $form_id = absint($_POST['form_id']);
        $elements = $_POST['elements'];
        $shortcodes = SUPER_Shortcodes::shortcodes();
        $form_html = SUPER_Common::generate_backend_elements($form_id, $shortcodes, $elements);
        echo $form_html;
        die();
    }

    /** 
     *  Reset submission counter (locker)
     *
     *  @since      3.4.0
    */
    public static function reset_submission_counter() {
        $form_id = absint($_POST['form_id']);
        $counter = absint($_POST['counter']);
        if( $counter==0 ) {
            delete_post_meta( $form_id, '_super_submission_count' );
        }else{
            update_post_meta( $form_id, '_super_submission_count', $counter );
        }
        die();
    }


    /** 
     *  Reset users submission counter (locker)
     *
     *  @since      3.8.0
    */
    public static function reset_user_submission_counter() {
        $form_id = absint($_POST['form_id']);
        delete_post_meta( $form_id, '_super_user_submission_counter' );
        die();
    }

    
    /** 
     *  Bulk edit contact entry status
     *
     *  @since      3.4.0
    */
    public static function bulk_edit_entries() {
        if( (isset($_POST['entry_status'])) && ($_POST['entry_status'] != -1) ) {
            $post_ids = (!empty($_POST['post_ids'])) ? $_POST['post_ids'] : array();
            if( !empty($post_ids) && is_array($post_ids) ) {
                $entry_status = $_POST['entry_status'];
                foreach( $post_ids as $post_id ) {
                    if($entry_status==''){
                        delete_post_meta( $post_id, '_super_contact_entry_status' );
                    }else{
                        update_post_meta( $post_id, '_super_contact_entry_status', $entry_status );
                    }
                }
            }
        }
        die();
    }

    /** 
     *  Save form progress in session after field change
     *
     *  @since      3.1.0
    */
    public static function save_form_progress() {
        if(!empty($_POST['form_id'])){
            $form_id = absint($_POST['form_id']);
            $data = false; // Clear date by default
            if(!empty($_POST['data'])){
                $data = $_POST['data'];
            }
            SUPER_Forms()->session->set( 'super_form_progress_' . $form_id, $data );
        }
        die();
    }


    /** 
     *  Calculate distance between to places / zipcodes
     *
     *  @since      3.1.0
    */
    public static function calculate_distance() {
        global $wpdb;
   
        $units = sanitize_text_field($_POST['units']);
        $q = '';
        if($units=='imperial') $q = '&units=imperial';
   
        $origin = sanitize_text_field($_POST['origin']);
        $destination = sanitize_text_field($_POST['destination']);
        $url = 'https://maps.googleapis.com/maps/api/directions/json?gl=uk' . $q . '&origin=' . $origin . '&destination=' . $destination;

        $global_settings = SUPER_Common::get_global_settings();
        if( !empty($global_settings['form_google_places_api']) ) $url .= '&key=' . $global_settings['form_google_places_api'];
        
        $response = wp_remote_get( $url, array('timeout'=>60) );
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
        }
        $json = json_decode($response['body'], true);
        if($json['status']!='OK'){
            if($json['status']=='NOT_FOUND'){
                $error_message = esc_html__( 'Address could not be found, please verify that the address was entered correctly.', 'super-forms' );
            }else{
                $error_message = $json['error_message'];
            }
        }
        if(!empty($error_message)){
            SUPER_Common::output_message(
                $error = true,
                $msg = $error_message
            );
        }else{
            echo $response['body'];
        }
        die();
    }



    /** 
     *  Delete all backups
     *
     *  @since      3.1.0
    */
    public static function delete_backups() {
        global $wpdb;
        $form_id = absint($_POST['form_id']);

        // Only delete selected backup
        if( isset($_POST['backup_id']) ) {
            wp_delete_post( absint($_POST['backup_id']), true );
            die();
        }

        // Delete form backups
        $args = array( 
            'post_parent' => $form_id,
            'post_type' => 'super_form',
            'post_status' => 'backup',
            'posts_per_page' => -1 //Make sure all matching backups will be retrieved
        );
        $backups = get_posts( $args );
        if(is_array($backups) && count($backups) > 0) {
            foreach( $backups as $v ) {
                wp_delete_post( $v->ID, true );
            }
        }
        die();            
    }


    /** 
     *  Restore selected backup
     *
     *  @since      3.1.0
    */
    public static function restore_backup() {
        global $wpdb;
        $form_id = absint($_POST['form_id']);
        
        // Only refresh backup list
        if( !isset($_POST['backup_id']) ) {
            $args = array(
                'post_parent' => $form_id,
                'post_type' => 'super_form',
                'post_status' => 'backup',
                'posts_per_page' => -1 //Make sure all matching backups will be retrieved
            );
            $backups = get_posts( $args );
            if( count($backups)==0 ) {
                echo '<i>' . esc_html__( 'No backups found...', 'super-forms' ) . '</i>';
            }else{
                $today = date_i18n('d-m-Y');
                $yesterday = date_i18n('d-m-Y', strtotime($today . ' -1 day'));
                echo '<ul>';
                foreach( $backups as $k => $v ) {
                    echo '<li data-id="' . $v->ID . '">';
                    echo '<i></i>';
                    $date = date_i18n('d-m-Y', strtotime($v->post_date));
                    if( $today==$date ) {
                        $to_time = strtotime(date_i18n('Y-m-d H:i:s'));
                        $from_time = strtotime($v->post_date);
                        $minutes = round(abs($to_time - $from_time) / 60, 0);
                        echo 'Today @ ' . date_i18n('H:i:s', strtotime($v->post_date)) . ' <strong>(' . $minutes . ($minutes==1 ? ' minute' : ' minutes') . ' ago)</strong>';
                    }elseif( $yesterday==$date ) {
                        echo 'Yesterday @ ' . date_i18n('H:i:s', strtotime($v->post_date));
                    }else{
                        echo date_i18n('d M Y @ H:i:s', strtotime($v->post_date));
                    }
                    echo '<span>'.esc_html__('Restore backup', 'super-forms').'</span></li>';
                }
                echo '</ul>';
            }
            die();
        }
        $form_id = absint($_POST['form_id']);
        $backup_id = absint($_POST['backup_id']);

        $elements = get_post_meta( $backup_id, '_super_elements', true );
        if(!is_array($elements)){
            $elements = json_decode( $elements, true );
        }
        update_post_meta( $form_id, '_super_elements', $elements );
     
        $settings = SUPER_Common::get_form_settings($backup_id);
        update_post_meta( $form_id, '_super_form_settings', $settings );
      
        $version = get_post_meta( $backup_id, '_super_version', true );
        update_post_meta( $form_id, '_super_version', $version );

        // @since 4.7.0 - translations
        $translations = SUPER_Common::get_form_translations($backup_id);
        update_post_meta( $form_id, '_super_translations', $translations );

        die();
    }


    /** 
     *  Search WC orders
     *
     *  @since      4.6.0
    */
    public static function search_wc_orders() {
        $value = sanitize_text_field($_POST['value']);
        $method = sanitize_text_field($_POST['method']);
        $filterby = sanitize_text_field($_POST['filterby']);
        if(empty($filterby)){
            $filterby = 'ID;_billing_email;_billing_address_1;_billing_postcode;_billing_first_name;_billing_last_name;_billing_company'; 
        }
        $default_return_label = '[Order #{ID} - {_billing_email}, {_billing_first_name} {_billing_last_name}]';
        if(!empty($_POST['return_label'])) $default_return_label = sanitize_text_field($_POST['return_label']);
        $return_value = 'ID;_billing_email;_billing_first_name;_billing_last_name';
        if(!empty($_POST['return_value'])) $return_value = sanitize_text_field($_POST['return_value']);
        $populate = sanitize_text_field($_POST['populate']);
        $skip = sanitize_text_field($_POST['skip']);
        $query = "(post_type = 'shop_order') AND (";
        if($method=='equals') {
            $query .= "(wc_order.ID LIKE '$value')";
        }
        if($method=='contains') {
            $query .= "(wc_order.ID LIKE '%$value%')";
        }
        global $wpdb;
        $filterby = explode(";", $filterby);
        foreach($filterby as $k => $v){
            if(!empty($v)){
                if($method=='equals') {
                    $query .= " OR (meta.meta_key = '".$v."' AND meta.meta_value LIKE '$value')";
                }
                if($method=='contains') {
                    $query .= " OR (meta.meta_key = '".$v."' AND meta.meta_value LIKE '%$value%')";
                }
            }
        }
        $query .= ")";
        if(!empty($_POST['status'])){
            $status = sanitize_text_field($_POST['status']);
            $status = explode(';', $status);
            foreach($status as $k => $v){
                $status[$k] = trim($v);
            }
            $status = "'" . implode("','", $status) . "'";
            $query .= "AND wc_order.post_status IN ($status)";
        }
        $query = "SELECT wc_order.*
        FROM $wpdb->posts AS wc_order
        INNER JOIN $wpdb->postmeta AS meta ON meta.post_id = wc_order.ID
        WHERE $query
        GROUP BY wc_order.ID
        LIMIT 50";
        $orders = $wpdb->get_results($query);
        $regex = '/\{(.*?)\}/';
        $orders_array = array();
        foreach($orders as $k => $v){
            $v = (array) $v;
            // Replace all {tags} and build the user label
            $order_label = $default_return_label;
            preg_match_all($regex, $order_label, $matches, PREG_SET_ORDER, 0);
            foreach($matches as $mk => $mv){
                if( isset($mv[1]) && isset($v[$mv[1]]) ) {
                    $order_label = str_replace( '{' . $mv[1] . '}', $v[$mv[1]], $order_label );
                }else{
                    // Maybe we need to search in user meta data
                    $meta_value = get_post_meta( $v['ID'], $mv[1], true );
                    $order_label = str_replace( '{' . $mv[1] . '}', $meta_value, $order_label );
                }
            }
            // Replace all meta_keys and build the user value
            $mk = explode(";", $return_value);
            $order_value = array();
            foreach($mk as $mv){
                if( isset($v[$mv]) ) {
                    $order_value[] = $v[$mv];
                }else{
                    // Maybe we need to search in user meta data
                    $meta_value = get_post_meta( $v['ID'], $mv, true );
                    $order_value[] = $meta_value;
                }   
            }
            $orders_array[] = array(
                'label' => $order_label,
                'value' => implode(';', $order_value)
            );
        }
        foreach($orders_array as $k => $v){
            echo '<li class="super-item" style="display:block;" data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '">' . $v['label'] . '</li>';
        }
        die();
    }

    /** 
     *  Populate form with contact entry data
     *
     *  @since      2.2.0
    */
    public static function populate_form_data() {
        global $wpdb;
        // @since 4.6.0 - check if we are looking up entry data based on a WC order
        if(isset($_POST['order_id'])){
            $order_id = absint($_POST['order_id']);
            $skip = sanitize_text_field($_POST['skip']);
            $data = SUPER_Common::get_entry_data_by_wc_order_id($order_id, $skip);
            echo json_encode($data);
        }else{
            $value = sanitize_text_field($_POST['value']);
            $method = sanitize_text_field($_POST['method']);
            $table = $wpdb->prefix . 'posts';
            $table_meta = $wpdb->prefix . 'postmeta';
            if($method=='equals') $query = "post_title = BINARY '$value'";
            if($method=='contains') $query = "post_title LIKE BINARY '%$value%'";
            $entry = $wpdb->get_results("SELECT ID FROM $table WHERE $query AND post_status IN ('publish','super_unread','super_read') AND post_type = 'super_contact_entry' LIMIT 1");
            $data = array();
            if( isset($entry[0])) {
                $data = get_post_meta( $entry[0]->ID, '_super_contact_entry_data', true );
                unset($data['hidden_form_id']);
                // @since 3.2.0 - skip specific fields from being populated
                $skip = sanitize_text_field($_POST['skip']);
                $skip_fields = explode( "|", $skip );
                foreach($skip_fields as $field_name){
                    if( isset($data[$field_name]) ) {
                        unset($data[$field_name]);
                    }
                }
                if( isset($entry[0])) {
                    $data['hidden_contact_entry_id'] = array(
                        'name' => 'hidden_contact_entry_id',
                        'value' => $entry[0]->ID,
                        'type' => 'entry_id'
                    );
                }
            }
            echo json_encode($data);
        }
        die();
    }


    /** 
     *  Update contact entry data
     *
     *  @since      1.7
    */
    public static function update_contact_entry() {
        $id = absint( $_POST['id'] );
        $new_data = $_POST['data'];

        // @since 3.3.0 - update Contact Entry title
        $entry_title = $new_data['super_contact_entry_post_title'];
        unset($new_data['super_contact_entry_post_title']);
        $entry = array(
            'ID' => $id,
            'post_title' => $entry_title
        );
        wp_update_post( $entry );

        // @since 3.4.0 - update contact entry status
        $entry_status = $_POST['entry_status'];
        update_post_meta( $id, '_super_contact_entry_status', $entry_status);

        $data = get_post_meta( $id, '_super_contact_entry_data', true );
        // If doesn't exist, we don't have to do anything, must be of type Array
        if( ($data!=='') && (is_array($data)) ) { 
            foreach( $data as $k => $v ) {
                // Assign new value only if it exists
                if(isset($new_data[$k]))  $data[$k]['value'] = $new_data[$k];
            }
            update_post_meta( $id, '_super_contact_entry_data', $data);
        }
        SUPER_Common::output_message(
            $error = false,
            $msg = esc_html__( 'Contact entry updated.', 'super-forms' )
        );
        die();
    }


    /** 
     *  Export selected entries to CSV
     *
     *  @since      1.7
    */
    public static function export_selected_entries() {
        $columns = $_POST['columns'];
        $query = $_POST['query'];
        $rows = array();
        foreach( $columns as $k => $v ) {
            $rows[0][$k] = $v;
        }

        global $wpdb;
        $delimiter = ',';
        $enclosure = '"';
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $entries = $wpdb->get_results("
        SELECT ID, post_title, post_date, post_author, post_status, meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry' AND entry.ID IN ($query)");

        foreach( $entries as $k => $v ) {
            $data = unserialize( $v->data );
            $data['entry_id']['value'] = $v->ID;
            $data['entry_title']['value'] = $v->post_title;
            $data['entry_date']['value'] = $v->post_date;
            $data['entry_author']['value'] = $v->post_author;
            $data['entry_status']['value'] = $v->post_status;
            $data['entry_ip']['value'] = get_post_meta( $v->ID, '_super_contact_entry_ip', true );

            // @since 3.4.0 - custom entry status
            $data['entry_custom_status']['value'] = get_post_meta( $v->ID, '_super_contact_entry_status', true );

            $entries[$k] = $data;
        }
        // Filter to alter for instance the "entry_date" format from 19:00 to 06:00 Pm
        $entries = apply_filters( 'super_export_selected_entries_filter', $entries );

        foreach( $entries as $k => $v ) {
            foreach( $columns as $ck => $cv ) {
                if( isset( $v[$ck] ) ) {
                    if( (isset($v[$ck]['type'])) && ($v[$ck]['type'] == 'files') ) {
                        $files = '';
                        if( ( isset( $v[$ck]['files'] ) ) && ( count( $v[$ck]['files'] )!=0 ) ) {
                            foreach( $v[$ck]['files'] as $fk => $fv ) {
                                if( $fk==0 ) {
                                    $files .= $fv['url'];
                                }else{
                                    $files .= "\n" . $fv['url'];
                                }
                            }
                        }
                        $rows[$k+1][] = $files;
                    }else{
                        if( !isset($v[$ck]['value']) ) {
                            $rows[$k+1][] = '';
                        }else{
                            $rows[$k+1][] = $v[$ck]['value'];
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
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // @since 3.1.0 - write file header for correct encoding
        foreach ( $rows as $fields ) {
            fputcsv( $fp, $fields, $delimiter, $enclosure );
        }
        fclose( $fp );
        echo SUPER_PLUGIN_FILE . $file_location;
        die();
    }


    /** 
     *  Return entry export columns
     *
     *  @since      1.7
    */
    public static function get_entry_export_columns() {
        global $wpdb;

        $global_settings = SUPER_Common::get_global_settings();
        $fields = explode( "\n", $global_settings['backend_contact_entry_list_fields'] );
        
        $column_settings = array();
        foreach( $fields as $k ) {
            $field = explode( "|", $k );
            $column_settings[$field[0]] = $field[1];
        }

        $entries = $_POST['entries'];
        $query = '';
        foreach( $entries as $k => $v ) {
            if( $k==0 ) {
                $query .= $v;
            }else{
                $query .= ',' . $v;
            }
        }
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $results = $wpdb->get_results("
        SELECT meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry' AND entry.ID IN ($query)");
        $columns = array();
        $columns[] = 'entry_id';
        $columns[] = 'entry_title';
        $columns[] = 'entry_date';
        $columns[] = 'entry_author';
        $columns[] = 'entry_status';
        foreach( $results as $k => $v ) {
            $data = unserialize( $v->data );
            foreach( $data as $dk => $dv ) {
                if ( !in_array( $dk, $columns ) ) {
                    $columns[] = $dk;
                }
            }
        }
        $columns[] = 'entry_ip';
        echo '<span class="button super-export-selected-columns-toggle" style="margin-top:10px;">'.esc_html__('Toggle all fields', 'super-forms').'</span>';
        echo '<ul class="super-export-entry-columns">';
        foreach( $columns as $k => $v ) {
            echo '<li class="super-entry-column" data-name="' . esc_attr($v) . '">';
            echo '<input type="checkbox"' . ((isset($column_settings[$v])) ? ' checked="checked"' : '') . ' />';
            echo '<span class="name">' . $v . '</span>';
            echo '<input type="text" value="' . ((isset($column_settings[$v])) ? $column_settings[$v] : $v) . '" />';
            echo '<span class="sort"></span>';
            echo '</li>';
        }
        echo '</ul>';
        echo '<input type="hidden" name="query" value="' . $query . '" />';
        echo '<span class="button button-primary button-large super-export-selected-columns">'.esc_html__('Export', 'super-forms').'</span>';
        die();
    }


    /** 
     *  Install demos item
     *
     *  @since      1.2.8
    */
    public static function demos_install_item() {
        $title = $_POST['title'];
        if( !empty($_POST['import']) ) {
            $import = maybe_unserialize(stripslashes($_POST['import']));
            $settings = $import['settings'];
            $elements = $import['elements'];
        }else{
            $settings = json_decode( stripslashes( $_POST['settings'] ), true );
            $elements = json_decode( stripslashes( $_POST['elements'] ), true );
        }
        $form = array(
            'post_title' => $title,
            'post_status' => 'publish',
            'post_type'  => 'super_form'
        );
        $id = wp_insert_post( $form );
        add_post_meta( $id, '_super_form_settings', $settings );
        add_post_meta( $id, '_super_elements', $elements );
        echo $id;
        die();
    }


    /** 
     *  Mark as read/unread
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
        foreach( $_POST['data'] as $k => $v ) {
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
                    SUPER_Common::output_message(
                        $error='smtp_error',
                        $ev
                    );
                    die();
                }
                SUPER_Common::output_message(
                    $error='smtp_error',
                    esc_html__( 'Invalid SMTP settings!', 'super-forms' )
                );
                die();
            }
        }
        update_option( 'super_settings', $array );
        die();
    }


    /** 
     *  Load the default settings (Settings page)
     *
     *  @since      1.0.0
    */
    public static function load_default_settings() {
        $default_settings = SUPER_Settings::get_defaults();
        update_option('super_settings', $default_settings);
        die();
    }


    /** 
     *  Import Contact Entries (from CSV file)
     *
     *  @since      1.2.6
    */
    public static function import_contact_entries() {
        $file_id = absint( $_POST['file_id'] );
        $column_connections = $_POST['column_connections'];
        $skip_first = $_POST['skip_first'];
        $delimiter = ',';
        if( isset( $_POST['import_delimiter'] ) ) {
            $delimiter = $_POST['import_delimiter'];
        }
        $enclosure = '"';
        if( isset( $_POST['import_enclosure'] ) ) {
            $enclosure = stripslashes($_POST['import_enclosure']);
        }
        $file = get_attached_file($file_id);
        $columns = array();
        $entries = array();
        if( $file ) {
            $row = 0;
            if (($handle = fopen($file, "r")) !== FALSE) {
                // Progress file pointer and get first 3 characters to compare to the BOM string.
                $bom = "\xef\xbb\xbf"; // BOM as a string for comparison.
                if (fgets($handle, 4) !== $bom) rewind($handle); // BOM not found - rewind pointer to start of file.
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
                                'type' => $column_type
                            );
                            continue;
                        }
                        if( $column_type=='var' ) {
                            $entries[$row]['data'][$column_name] = array(
                                'name' => $column_name,
                                'label' => $column_label,
                                'value' => $v,
                                'type' => $column_type
                            );
                            continue;
                        }
                        if( $column_type=='text' ) {
                            $entries[$row]['data'][$column_name] = array(
                                'name' => $column_name,
                                'label' => $column_label,
                                'value' => $v,
                                'type' => $column_type
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

        $global_settings = SUPER_Common::get_global_settings();
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
                    $contact_entry_title = esc_html__( 'Contact entry', 'super-forms' );
                }
                if( $global_settings['contact_entry_add_id']=='true' ) {
                    $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
                }
                $contact_entry = array(
                    'ID' => $contact_entry_id,
                    'post_title' => $contact_entry_title,
                );
                wp_update_post( $contact_entry );
                $imported++;
            }
        }

        echo '<div class="message success">';
        echo sprintf( esc_html__( '%d of %d contact entries imported!', 'super-forms' ), $imported, count($entries) );
        echo '</div>';
        die();

    }


    /** 
     *  Prepare Contact Entries Import (from CSV file)
     *
     *  @since      1.2.6
    */
    public static function prepare_contact_entry_import() {
        $file_id = absint( $_POST['file_id'] );
        $delimiter = ',';
        if( isset( $_POST['import_delimiter'] ) ) {
            $delimiter = $_POST['import_delimiter'];
        }
        $enclosure = '"';
        if( isset( $_POST['import_enclosure'] ) ) {
            $enclosure = stripslashes($_POST['import_enclosure']);
        }
        $file = get_attached_file($file_id);
        $columns = array();
        if( $file ) {
            $row = 1;
            if (($handle = fopen($file, "r")) !== FALSE) {
                // Progress file pointer and get first 3 characters to compare to the BOM string.
                $bom = "\xef\xbb\xbf"; // BOM as a string for comparison.
                if (fgets($handle, 4) !== $bom) rewind($handle); // BOM not found - rewind pointer to start of file.
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
     *  Export single Form
     *
     *  @since      4.0.0
    */
    public static function export_single_form() {
        $form_id = absint( $_POST['form_id'] );
        if( $form_id==0 ) {
            $title = esc_html__( 'Form Name', 'super-forms' );
        }else{
            $title = get_the_title( $form_id );
        }
        $formSettings = $_POST['formSettings'];
        $formElements = wp_unslash($_POST['formElements']);
        $formElements = json_decode($formElements, true);
        $translationSettings = get_post_meta( $form_id, '_super_translations', true );
        $export = array(
            'title' => $title,
            'settings' => $formSettings,
            'elements' => $formElements,
            'translations' => $translationSettings
        );
        $export = '<html>'.maybe_serialize($export);
        $filename = $title.'-super-forms-export.html';
        $filename = sanitize_file_name($filename);
        $file_location = '/uploads/php/files/'.$filename;
        $source = urldecode( SUPER_PLUGIN_DIR . $file_location );
        file_put_contents($source, $export);
        echo SUPER_PLUGIN_FILE . $file_location;
        die();
    }


    /** 
     *  Import single Form
     *
     *  @since      4.0.0
    */
    public static function import_single_form() {
        $form_id = absint( $_POST['form_id'] );
        $file_id = absint( $_POST['file_id'] );

        // What do we need to import?
        $import_elements = $_POST['elements']; // Form elements
        $import_settings = $_POST['settings']; // Form settings
        $import_translations = $_POST['translations']; // Translation settings

        $file = wp_get_attachment_url($file_id);
        if( $file ) {
            $contents = wp_remote_fopen($file);
            // Remove <html> tag at the beginning if exists
            $html_tag = substr($contents, 0, 6);
            if($html_tag==='<html>'){
                $contents = substr($contents, 6);
            }
            
            // Check if content is json (backward compatibility import from older SF versions)
            json_decode($contents);
            if( json_last_error() == JSON_ERROR_NONE ) {
                $contents = json_decode($contents, true)[0];
            }

            $contents = maybe_unserialize( $contents );
            $title = (isset($contents['title']) ? $contents['title'] : $contents['post_title']);

            // Only set elements from import file if user choose to do so
            $formElements = array();
            if($import_elements=='true') {
                if(isset($contents['elements'])) $formElements = $contents['elements'];
            }
            // Only set settings from import file if user choose to do so
            $formSettings = array();
            if($import_settings=='true') {
                if(isset($contents['settings']))  $formSettings = $contents['settings'];
            } 
            // Only set translation settings from import file if user choose to do so
            $translationSettings = array();
            if($import_translations=='true') {
                if(isset($contents['translations'])) $translationSettings = $contents['translations'];
            } 

            if( $form_id==0 ) {
                // Create a new form
                $form_id = self::save_form( $form_id, $formElements, $translationSettings, $formSettings, $title );
            }else{
                // Only import/update elements if user wanted to
                if($import_elements=='true') {
                    update_post_meta( $form_id, '_super_elements', $formElements );
                }
                // Only import/update settings if user wanted to
                if($import_settings=='true') {
                    update_post_meta( $form_id, '_super_form_settings', $formSettings );
                }
                // Only import/update translation settings if user wanted to
                if($import_translations=='true') {
                    update_post_meta( $form_id, '_super_translations', $translationSettings );
                }
            }
            echo $form_id;
        }else{
            SUPER_Common::output_message(
                $error = true,
                $msg = sprintf( esc_html__( 'Import file #%d could not be located', 'super-forms' ), $file_id )
            );
        }
        die();
    }


    /** 
     *  Reset form settings
     *
     *  @since      4.0.0
    */
    public static function reset_form_settings() {
        $form_id = absint( $_POST['form_id'] );
        $global_settings = SUPER_Common::get_global_settings();
        update_post_meta( $form_id, '_super_form_settings', $global_settings );
        echo $form_id;
        die();
    }


    /** 
     *  Export Forms
     *
     *  @since      1.9
    */
    public static function export_forms() {
        $file_location = '/uploads/php/files/super-forms-export.html';
        $source = urldecode( SUPER_PLUGIN_DIR . $file_location );
        ini_set('max_execution_time', 0);
        global $wpdb;
        $offset = absint($_POST['offset']);
        $limit = absint($_POST['limit']);
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        if($_POST['found']==''){
            // Return total forms
            $found = absint($wpdb->get_var("SELECT COUNT(form.ID) FROM $table AS form WHERE form.post_status IN ('publish') AND form.post_type = 'super_form'"));
        }else{
            $found = absint($_POST['found']);
        }
        $forms = $wpdb->get_results("
        SELECT 
        form.ID,
        form.post_author,
        form.post_date,
        form.post_date_gmt,
        form.post_title,
        form.post_status
        FROM $table AS form WHERE form.post_status IN ('publish') AND form.post_type = 'super_form' LIMIT $limit OFFSET $offset", ARRAY_A);
        
        $fp = fopen($source, 'w');
        fwrite($fp, "<html>");
        foreach( $forms as $k => $v ) {
            $form_id = $v['ID'];
            $settings = SUPER_Common::get_form_settings($form_id);
            $elements = get_post_meta( $form_id, '_super_elements', true );
            $forms[$k]['settings'] = $settings;
            if(is_array($elements)){
                $forms[$k]['elements'] = $elements;
            }else{
                $forms[$k]['elements'] = json_decode($elements, true);
            }
            $translations = get_post_meta( $form_id, '_super_translations', true );
            $forms[$k]['translations'] = $translations;
        }
        $content = json_encode($forms);
        fwrite($fp, $content);
        fclose($fp);
        echo json_encode(array('file_url'=>SUPER_PLUGIN_FILE . $file_location, 'offset'=>$offset+$limit, 'found'=>$found));
        die();
    }


    /** 
     *  Prepare Forms Import (from TXT file)
     *
     *  @since      1.9
    */
    public static function start_forms_import() {
        $file_id = absint( $_POST['file_id'] );
        $source = get_attached_file($file_id);
        $contents = file_get_contents($source);

        // Remove <html> tag at the beginning if exists
        $html_tag = substr($contents, 0, 6);
        if($html_tag==='<html>'){
            $contents = substr($contents, 6);
        }
        $forms = json_decode($contents, true);
        foreach($forms as $k => $v){
            $form = array(
                'post_author' => $v['post_author'],
                'post_date' => $v['post_date'],
                'post_date_gmt' => $v['post_date_gmt'],
                'post_title' => $v['post_title'],
                'post_status' => $v['post_status'],
                'post_type'  => 'super_form'
            );
            $form_id = wp_insert_post( $form );
            add_post_meta( $form_id, '_super_form_settings', $v['settings'] );
        
            $elements = $v['elements'];
            if( !is_array($elements) ) {
                $elements = json_decode( $elements, true );
            }
            add_post_meta( $form_id, '_super_elements', $elements );

            // @since 4.7.0 - translations
            if(isset($v['translations'])){
                add_post_meta( $form_id, '_super_translations', $v['translations'] );
            }
        }
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
        if( isset( $_POST['type'] ) ) {
            $type = $_POST['type'];
        }
        $from = '';
        $till = '';
        $range_query = '';
        if( isset( $_POST['from'] ) ) {
            $from = $_POST['from'];
        }
        if( isset( $_POST['till'] ) ) {
            $till = $_POST['till'];
        }
        if( ($from!='') && ($till!='') ) {
            $from = date_i18n( 'Y-m-d', strtotime( $from ) );
            $till = date_i18n( 'Y-m-d', strtotime( $till ) );
            $range_query = " AND ((entry.post_date LIKE '$from%' OR entry.post_date LIKE '$till%') OR (entry.post_date BETWEEN '$from' AND '$till'))";
        }

        $delimiter = ',';
        if( isset( $_POST['delimiter'] ) ) {
            $delimiter = $_POST['delimiter'];
        }
        $enclosure = '"';
        if( isset( $_POST['enclosure'] ) ) {
            $enclosure = stripslashes($_POST['enclosure']);
        }
        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $entries = $wpdb->get_results("
        SELECT ID, post_title, post_date, post_author, post_status, meta.meta_value AS data
        FROM $table AS entry
        INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID  AND meta.meta_key = '_super_contact_entry_data'
        WHERE entry.post_status IN ('publish','super_unread','super_read') AND entry.post_type = 'super_contact_entry'$range_query");

        $rows = array();
        $columns = array();
        $rows[0][] = 'entry_id';
        $rows[0][] = 'entry_title';
        $rows[0][] = 'entry_date';
        $rows[0][] = 'entry_author';
        $rows[0][] = 'entry_status';
        $columns[] = 'entry_id';
        $columns[] = 'entry_title';
        $columns[] = 'entry_date';
        $columns[] = 'entry_author';
        $columns[] = 'entry_status';
        foreach( $entries as $k => $v ) {
            $data = unserialize( $v->data );
            foreach( $data as $dk => $dv ) {
                if ( !in_array( $dk, $columns ) ) {
                    $columns[] = $dk;
                    $rows[0][] = $dk;
                }
            }
            $data['entry_id']['value'] = $v->ID;
            $data['entry_title']['value'] = $v->post_title;
            $data['entry_date']['value'] = $v->post_date;
            $data['entry_author']['value'] = $v->post_author;
            $data['entry_status']['value'] = $v->post_status;
            $data['entry_ip']['value'] = get_post_meta( $v->ID, '_super_contact_entry_ip', true );

            // @since 3.4.0 - custom entry status
            $data['entry_custom_status']['value'] = get_post_meta( $v->ID, '_super_contact_entry_status', true );

            $entries[$k] = $data;
        }
        $rows[0][] = 'entry_ip';
        $columns[] = 'entry_ip';

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
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // @since 3.1.0 - write file header for correct encoding
        foreach ( $rows as $fields ) {
            fputcsv( $fp, $fields, $delimiter, $enclosure );
        }
        fclose( $fp );
        echo SUPER_PLUGIN_FILE . $file_location;
        die();
    }


    /** 
     *  Import Global Settings (from settings page)
     *
     *  @since      1.0.6
    */
    public static function import_global_settings() {
        if( ( isset ( $_POST['method'] ) ) && ( $_POST['method']=='load-default' ) ) {
            $settings = SUPER_Settings::get_defaults();
        }else{
            $settings = $_POST['settings'];
            $settings = json_decode( stripslashes( $settings ), true );
            if( json_last_error() != 0 ) {
                var_dump( 'JSON error: ' . json_last_error() );
            }
        }
        update_option( 'super_settings', $settings );
        die();
    }


    /** 
     *  Loads the form preview on backedn (create form page)
     *
     *  @since      1.0.0
    */
    public static function load_preview() {
        $form_id = absint( $_POST['form_id'] );
        echo SUPER_Shortcodes::super_form_func( array( 'id'=>$form_id ) );
        die();
    }


    /** 
     *  Clear deleted translations
     *
     *  @since      4.7.0
    */
    public static function clear_i18n( $elements=array(), $translations=array() ) {
        if(!empty($elements)){
            foreach($elements as $k => $v){
                // Check if has inner elements
                if(!empty($v['inner'])){
                    $elements[$k]['inner'] = self::clear_i18n( $v['inner'], $translations );
                }else{
                    // Just remove deleted translations
                    if( !empty($v['data']['i18n']) && is_array($v['data']['i18n']) ) {
                        foreach( $v['data']['i18n'] as $ik => $iv ) {
                            if(!isset($translations[$ik])){
                                // Delete translation
                                unset($elements[$k]['data']['i18n'][$ik]);
                            }
                        } 
                    }
                }
            }
        }
        return $elements;
    }


    /** 
     *  Saves the form with all it's settings
     *
     *  @since      1.0.0
    */
    public static function save_form( $id=null, $formElements=null, $translationSettings=null, $formSettings=null, $title=null ) {
        if(empty($id)){
            if(isset($_POST['form_id'])) $id = $_POST['form_id'];
            if(empty($formElements)){
                $_POST['formElements'] = wp_unslash($_POST['formElements']);
                $formElements = json_decode($_POST['formElements'], true);
            }
            if(empty($formSettings)){
                $_POST['formSettings'] = wp_unslash($_POST['formSettings']);
                $formSettings = json_decode($_POST['formSettings'], true);
            }
            if(empty($translationSettings)){
                $_POST['translationSettings'] = wp_unslash($_POST['translationSettings']);
                $translationSettings = json_decode($_POST['translationSettings'], true);
            }
        }
        $id = absint( $id );

        $formElements = wp_slash($formElements); // This is required to keep "Custom regex" working e.g: \\d will become \\\\d
        $formSettings = wp_slash($formSettings); // This is required to keep Custom CSS {content: '\x123';} working
        // @since 4.7.0 - translations
        // We must delete/clear any translations that no longer exist
        $formElements = self::clear_i18n($formElements, $translationSettings);

        // @since 3.9.0 - don't save settings that are the same as global settings
        // Get global settings
        $global_settings = SUPER_Common::get_global_settings();
        // Loop trhough all form settings, and look for duplicates based on global settings
        if(!empty($formSettings)){
            foreach( $formSettings as $k => $v ) {
                // Check if the setting exists on global level
                if( isset( $global_settings[$k] ) ) {
                    // Only unset key if value is exactly the same as global setting value
                    if( $global_settings[$k] == $v ) {
                        unset( $formSettings[$k] );
                    }
                }
            }
        }
        // @since 4.7.0 - translation language switcher
        if(isset($_POST['i18n_switch'])) $formSettings['i18n_switch'] = sanitize_text_field($_POST['i18n_switch']);

        if( $title==null) {
            $title = esc_html__( 'Form Name', 'super-forms' );
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

            // @since 4.7.0 - translation
            add_post_meta( $id, '_super_form_settings', $formSettings );
            add_post_meta( $id, '_super_elements', $formElements );

            // @since 3.1.0 - save current plugin version / form version
            add_post_meta( $id, '_super_version', SUPER_VERSION );

            // @since 4.7.0 - translations
            add_post_meta( $id, '_super_translations', $translationSettings );

        }else{
            $form = array(
                'ID' => $id,
                'post_title' => $title
            );
            wp_update_post( $form );

            if(!empty($_POST['i18n'])){
                // Merge with existing form settings
                $settings = SUPER_Common::get_form_settings($id);
                // Add language to the form settings
                $settings['i18n'][$_POST['i18n']] = $formSettings;
                $formSettings = $settings;
            }else{
                $settings = SUPER_Common::get_form_settings($id);
                if(!empty($settings['i18n'])){
                    $formSettings['i18n'] = $settings['i18n'];
                }
            }
            update_post_meta( $id, '_super_form_settings', $formSettings );
            update_post_meta( $id, '_super_elements', $formElements );

            // @since 3.1.0 - save current plugin version / form version
            update_post_meta( $id, '_super_version', SUPER_VERSION );

            // @since 4.7.0 - translations
            update_post_meta( $id, '_super_translations', $translationSettings );

            // @since 3.1.0 - save history (store a total of 50 backups into db)
            $form = array(
                'post_parent' => $id,
                'post_title' => $title,
                'post_status' => 'backup',
                'post_type'  => 'super_form'
            );
            $backup_id = wp_insert_post( $form ); 
            add_post_meta( $backup_id, '_super_form_settings', $formSettings );
            add_post_meta( $backup_id, '_super_elements', $formElements );
            add_post_meta( $backup_id, '_super_version', SUPER_VERSION );
            // @since 4.7.0 - translations
            add_post_meta( $backup_id, '_super_translations', $translationSettings );
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
        $form_id = absint( $_POST['form_id'] );

        // @since 3.1.0 - also delete backups
        $args = array( 
            'post_parent' => $form_id,
            'post_type' => 'super_form',
            'post_status' => 'backup',
            'posts_per_page' => -1 //Make sure all matching backups will be retrieved
        );
        $backups = get_posts( $args );
        if(is_array($backups) && count($backups) > 0) {
            // Delete all the Children of the Parent Page
            foreach( $backups as $v ) {
                wp_delete_post( $v->ID, true );
            }
        }

        // Delete the form
        wp_delete_post( $form_id, true );

        die();
    }

    // Helper function, to loop over all element settings
    public static function loop_over_element_setting_fields($fields, $data, $shortcodes, $group, $tag, $k){
        $result = '';
        foreach( $fields  as $fk => $fv ) {
            $default = SUPER_Common::get_default_element_setting_value($shortcodes, $group, $tag, $k, $fk);
            $filter = '';
            $parent = '';
            $filtervalue = '';
            if( ( isset( $fv['filter'] ) ) && ( $fv['filter']==true ) ) {
                $filter = ' super-filter';
                if( isset( $fv['parent'] ) ) $parent = ' data-parent="' . $fv['parent'] . '"';
                if( isset( $fv['filter_value'] ) ) $filtervalue = ' data-filtervalue="' . $fv['filter_value'] . '"';
            }
            $hidden = '';
            if( isset( $fv['hidden'] ) && ( $fv['hidden']==true ) ) {
                $hidden = ' super-hidden';
            }
            $result .= '<div class="super-field' . $filter . $hidden . '"' . $parent . '' . $filtervalue . '>';
                if( isset( $fv['name'] ) ) $result .= '<div class="field-name">' . $fv['name'] . '</div>';
                if( isset( $fv['desc'] ) ) $result .= '<i class="info super-tooltip" title="' . $fv['desc'] . '"></i>';
                if( isset( $fv['label'] ) ) $result .= '<div class="field-label">' . nl2br($fv['label']) . '</div>';
                $result .= '<div class="field-input"';
                if( !empty($fv['allow_empty']) ) {
                    $result .= ' data-allow-empty="true"';
                }
                if( ($default!=='') && (!is_array($default)) ) {
                    $result .= ' data-default="' . $default . '"';
                }
                if( !empty($fv['_styles']) ) {
                    $result .= ' data-styles="' .esc_attr(json_encode($fv['_styles'], true)). '"';
                }
                $result .= '>';
                    if( !isset( $fv['type'] ) ) $fv['type'] = 'text';
                    if( method_exists( 'SUPER_Field_Types', $fv['type'] ) ) {
                        if( isset($data[$fk]) ) {
                            $fv['default'] = $data[$fk];
                        }
                        $result .= call_user_func( array( 'SUPER_Field_Types', $fv['type'] ), $fk, $fv, $data );
                    }
                $result .= '</div>';
            $result .= '</div>';
        }
        return $result;
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

        $settings = SUPER_Common::get_form_settings($_POST['form_id']);
        $shortcodes = SUPER_Shortcodes::shortcodes( false, false, false );
        $array = SUPER_Shortcodes::shortcodes( false, $data, false );
        $tabs = $array[$group]['shortcodes'][$tag]['atts'];
        $result = '';
        
        $translating = $_POST['translating'];
        if($translating=='false'){
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
                $result .= '<div class="tab-content' . ( $i==0 ? ' super-active' : '' ) . '">';
                    if($k==='icon' && $settings['theme_hide_icons']==='yes'){
                        $result .= '<strong style="color:red;">' . esc_html__( 'Please note', 'super-forms' ) . ':</strong> ' . esc_html__('Your icons will not be displayed because you currently have enabled the option to hide field icons under "Form Settings > Theme & Colors > Hide field icons"', 'super-forms' );
                    }
                    if($k==='distance_calculator' && empty($settings['form_google_places_api'])){
                        $result .= '<strong style="color:red;">' . esc_html__( 'Please note', 'super-forms' ) . ':</strong> ' . sprintf( esc_html__( 'In order to use this feature you must provide your Google API key in %sSuper Forms > Settings > Form Settings%s', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#form-settings') . '">', '</a>' );
                    }
                    if( isset( $v['fields'] ) ) {
                        $result .= self::loop_over_element_setting_fields($v['fields'], $data, $shortcodes, $group, $tag, $k);
                    }else{
                        // Display subtabs
                        unset($v['name']);
                        $result .= '<div class="super-subtabs">';
                        $i = 0;
                        foreach( $v as $stk => $stv ) {
                            $result .= '<div class="super-subtab' . ($i==0 ? ' super-active' : '') . '">' . $stv['name'] . '</div>';
                            $i++;
                        }
                        $result .= '</div>';
                        $result .= '<div class="super-subtabscontent">';
                        $i = 0;
                        foreach( $v as $stk => $stv ) {
                            $result .= '<div class="super-subtabcontent' . ($i==0 ? ' super-active' : '') . '">';
                                // Loop over all fields belonging to this Sub TAB
                                $result .= self::loop_over_element_setting_fields($stv['fields'], $data, $shortcodes, $group, $tag, $k);
                            $result .= '</div>';
                            $i++;
                        }
                        $result .= '</div>';
                    }
                $result .= '</div>';
                $i = 1;
            }
        }else{
            $result .= '<div class="tab-content super-active">';
                foreach( $tabs as $k => $v ){                
                    if( isset( $v['fields'] ) ) {
                        foreach( $v['fields'] as $fk => $fv ) {
                            if(empty($fv['i18n'])) continue;

                            // Make sure to skip this file if it's source location is invalid
                            if( ( isset( $fv['filter'] ) ) && ( $fv['filter']==true ) && (isset($fv['parent'])) ) {
                                if (strpos($v['fields'][$fv['parent']]['default'], $fv['filter_value']) === false) {
                                    continue;
                                }
                            }
                            $default = SUPER_Common::get_default_element_setting_value($shortcodes, $group, $tag, $k, $fk);
                            $hidden = '';
                            if( isset( $fv['hidden'] ) && ( $fv['hidden']==true ) ) {
                                $hidden = ' hidden';
                            }
                            $result .= '<div class="super-field' . $hidden . '">';
                                if( isset( $fv['name'] ) ) $result .= '<div class="field-name">' . $fv['name'] . '</div>';
                                if( isset( $fv['desc'] ) ) $result .= '<i class="info super-tooltip" title="' . $fv['desc'] . '"></i>';
                                if( isset( $fv['label'] ) ) $result .= '<div class="field-label">' . nl2br($fv['label']) . '</div>';
                                $result .= '<div class="field-input"';
                                if( !empty($fv['allow_empty']) ) {
                                    $result .= ' data-allow-empty="true"';
                                }
                                if( ($default!=='') && (!is_array($default)) ) {
                                    $result .= ' data-default="' . $default . '"';
                                }
                                $result .= '>';
                                    if( !isset( $fv['type'] ) ) $fv['type'] = 'text';
                                    if( method_exists( 'SUPER_Field_Types', $fv['type'] ) ) {
                                        if(isset($data['i18n']) && isset($data['i18n'][$_POST['i18n']])){
                                            if( isset($data['i18n'][$_POST['i18n']][$fk]) ) {
                                                $fv['default'] = $data['i18n'][$_POST['i18n']][$fk];
                                            }else{
                                                if( isset($data[$fk]) ) {
                                                    $fv['default'] = $data[$fk];
                                                }
                                            }
                                        }else{
                                            if( isset($data[$fk]) ) {
                                                $fv['default'] = $data[$fk];
                                            }
                                        }
                                        $result .= call_user_func( array( 'SUPER_Field_Types', $fv['type'] ), $fk, $fv, $data );
                                    }
                                $result .= '</div>';
                            $result .= '</div>';
                        }
                    }
                }
            $result .= '</div>';
        }
        $result .= '<span class="super-button super-update-element">' . esc_html__( 'Update Element', 'super-forms' ) . '</span>';
        $result .= '<span class="super-button super-cancel-update">' . esc_html__( 'Close', 'super-forms' ) . '</span>';
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
        $i18n = (isset($_POST['i18n']) ? $_POST['i18n'] : '');
        $form_id = 0;
        if( isset( $_POST['form_id'] ) ) {
            $form_id = absint( $_POST['form_id'] );
        }
        $settings = SUPER_Common::get_form_settings($form_id);

        include_once( SUPER_PLUGIN_DIR . '/includes/class-shortcodes.php' );
        $shortcodes = SUPER_Shortcodes::shortcodes();

        $predefined = '';
        if( isset( $_POST['predefined'] ) ) {
            $predefined = $_POST['predefined'];
        }
        if( $predefined!='' ) {
            $result = '';
            foreach( $predefined as $k => $v ) {
                // Output builder HTML (element and with action buttons)
                if( empty($v['data']) ) $v['data'] = null;
                if( empty($v['inner']) ) $v['inner'] = null;
                $result .= SUPER_Shortcodes::output_builder_html( $v['tag'], $v['group'], $v['data'], $v['inner'], $shortcodes, $settings, true );
            }
        }else{
            if($tag==null){
                $tag = $_POST['tag'];
            }
            if($group==null){
                $group = $_POST['group'];
            }
            $builder = 1;
            if(isset($_POST['builder'])){
                $builder = $_POST['builder'];
            }
            if(empty($inner)) {
                $inner = array();
                if(isset($_POST['inner'])){
                    $inner = $_POST['inner'];
                }
            }
            if(empty($data)) {
                $data = array();
                if(isset($_POST['data'])){
                    $data = $_POST['data'];
                }
            }
            if(is_array($data)) {
                $data = array_map('stripslashes_deep', $data);
            }

            // If updating TAB element, we only want to update the TABs, not the content
            $builder = explode(';', $builder);
            $from = $builder[0];
            if($from=='tabs' || $from=='accordion' || $from=='list'){
                // Make sure the correct layout is send (required in case we are translating the element, otherwise it would default to TAB layout
                if( $_POST['translating']=='true' ) {
                    $builder[1] = $from;
                    $data['layout'] = $from;
                }
                $result = SUPER_Shortcodes::output_builder_html( $tag, $group, $data, $inner, $shortcodes, $settings, false, $builder );
            }else{
                if($from==0){
                    // Output element HTML only
                    $result = SUPER_Shortcodes::output_element_html( $tag, $group, $data, $inner, $shortcodes, $settings, $i18n, false );
                }else{
                    // Output builder HTML (element and with action buttons)
                    $result = SUPER_Shortcodes::output_builder_html( $tag, $group, $data, $inner, $shortcodes, $settings );
                }
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

        // Check if form_id exists, this is always required
        // If it doesn't exist it is most likely due the server not being able to process all the data
        // In that case "max_input_vars" should be increased
        if(empty($_POST['form_id'])) {
            // First try to increase it manually
            // If it fails, tell the user about it, so they can contact the webmaster
            $max_input_vars = ini_get('max_input_vars');
            $double_max_input_vars = round(ini_get('max_input_vars')*2, 0);
            if(ini_set('max_input_vars', $double_max_input_vars)==false){
                // Failed, notify user
                SUPER_Common::output_message( 
                    $error = true, 
                    sprintf( esc_html__( 'Error: the server could not submit this form because it reached it\'s "max_input_vars" limit of %s' . ini_get('max_input_vars') . '%s. Please contact your webmaster and increase this limit inside your php.ini file!', 'super-forms' ), '<strong>', '</strong>' )
                );
            }else{
                // Success, notify user to try again
                SUPER_Common::output_message( 
                    $error = true, 
                    sprintf( esc_html__( 'Error: the server could not submit this form because it reached it\'s "max_input_vars" limit of %s' . $max_input_vars . '%s. We manually increased this limit to %s' . $double_max_input_vars . '%s. Please refresh this page and try again!', 'super-forms' ), '<strong>', '</strong>' )
                );
            }
        }

        $data = array();
        if( isset( $_POST['data'] ) ) {
            $data = $_POST['data'];
        }

        // @since 3.2.0 
        // - If honeypot captcha field is not empty just cancel the request completely
        // - Also make sure to unset the field for saving, because we do not need this field to be saved
        if( !empty($data['super_hp']) ) {
            exit;
        }
        unset($data['super_hp']);
        unset($_POST['data']['super_hp']);

        // @since 1.7.6
        $data = apply_filters( 'super_before_sending_email_data_filter', $data, array( 'post'=>$_POST, 'settings'=>$settings ) );        

        // Return extra data via ajax response
        $response_data = array();

        // Get form settings
        $form_id = absint( $_POST['form_id'] );
        $response_data['form_id'] = $form_id;
        if( $settings==null ) {
            $settings = SUPER_Common::get_form_settings($form_id);
            // @since 4.4.0 - Let's unset some settings we don't need
            unset($settings['theme_custom_js']);
            unset($settings['theme_custom_css']);
            unset($settings['form_custom_css']);
        }

        // Temporarily deprecated till found a solution with caching plugins
        // // @since 4.6.0 - Check if ajax request is valid based on nonce field
        // if ( !wp_verify_nonce( $_POST['super_ajax_nonce'], 'super_submit_' . $form_id ) ) {
        //     SUPER_Common::output_message( 
        //         $error = true, 
        //         esc_html__( 'Failed to verify nonce! You either do not have permission to submit this form or caching is enabled. If caching is enabled make sure to exclude this page from being cached.', 'super-forms' )
        //     );
        // }
        // check_ajax_referer( 'super_submit_' . $form_id, 'super_ajax_nonce' );

        // @since 4.6.0 - verify reCAPTCHA token
        if(!empty($_POST['version'])){
            $version = sanitize_text_field( $_POST['version'] );
            $secret = $settings['form_recaptcha_secret'];
            if($version==='v3'){
                $secret = $settings['form_recaptcha_v3_secret'];
            }
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $args = array(
                'secret' => $secret, 
                'response' => $_POST['token']
            );
            // @since 1.2.2   use wp_remote_post instead of file_get_contents because of the 15 sec. open connection on some hosts
            $response = wp_remote_post( 
                $url, 
                array(
                    'timeout' => 45,
                    'body' => $args
                )
            );
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                SUPER_Common::output_message(
                    $error = true,
                    $msg = esc_html__( 'Something went wrong:', 'super-forms' ) . ' ' . $error_message
                );
            } else {
                $result = json_decode( $response['body'], true );
                if( $result['success']!==true ) {
                    SUPER_Common::output_message( $error=true, esc_html__( 'Google reCAPTCHA verification failed!', 'super-forms' ) );
                }
            }
        }

        // @since 4.7.0 - translation
        if(!empty($_POST['i18n'])){
            $i18n = sanitize_text_field($_POST['i18n']);
            if( (!empty($settings['i18n'])) && (!empty($settings['i18n'][$i18n])) ){
                $settings = array_replace_recursive($settings, $settings['i18n'][$i18n]);
                unset($settings['i18n']);
            }
        }
        
        do_action( 'super_before_sending_email_hook', array( 'post'=>$_POST, 'settings'=>$settings ) );       

        // @since 3.4.0 - Lock form after specific amount of submissions (based on total contact entries created)
        if( !empty($settings['form_locker']) ) {
            if( !isset($settings['form_locker_limit']) ) $settings['form_locker_limit'] = 0;
            $limit = $settings['form_locker_limit'];
            $count = get_post_meta( $form_id, '_super_submission_count', true );
            $display_msg = false;
            if( $count>=$limit ) {
                $msg = '';
                if($settings['form_locker_msg_title']!='') {
                    $msg .= '<h1>' . $settings['form_locker_msg_title'] . '</h1>';
                }
                $msg .= nl2br($settings['form_locker_msg_desc']);
                SUPER_Common::output_message( $error=true, $msg );
            }
        }

        // @since 3.8.0 - Lock form after specific amount of submissions for logged in user (based on total contact entries created by user)
        if( !empty($settings['user_form_locker']) ) {
            // Let's check if the user is logged in
            $current_user_id = get_current_user_id();
            if( $current_user_id!=0 ) {
                
                $user_limits = get_post_meta( $form_id, '_super_user_submission_counter', true );
                $count = 0;
                if(!empty($user_limits[$current_user_id])) {
                    $count = absint($user_limits[$current_user_id])+1;
                }
                $limit = 0;
                if( !empty($settings['user_form_locker_limit']) ){
                    $limit = absint($settings['user_form_locker_limit']);
                } 

                $display_msg = false;
                if( $count>$limit ) {
                    $msg = '';
                    if($settings['user_form_locker_msg_title']!='') {
                        $msg .= '<h1>' . $settings['user_form_locker_msg_title'] . '</h1>';
                    }
                    $msg .= nl2br($settings['user_form_locker_msg_desc']);
                    SUPER_Common::output_message( $error=true, $msg );
                }
            }
        }


        if( !empty( $settings['header_additional'] ) ) {
            $header_additional = '';
            if( !empty( $settings['header_additional'] ) ) {
                $headers = explode( "\n", $settings['header_additional'] );   
                foreach( $headers as $k => $v ) {
                    
                    // @since 1.2.6.92
                    $v = SUPER_Common::email_tags( $v, $data, $settings );
                    
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
                if( !isset($v['type']) ) continue;
                if( $v['type']=='files' ) {
                    if( ( isset( $v['files'] ) ) && ( count( $v['files'] )!=0 ) ) {
                        foreach( $v['files'] as $key => $value ) {
                            // If there is a generated PDF let it act as a regular file upload
                            // Try to generate PDF file
                            if(isset($value['datauristring'])){
                                try {
                                    $imgData = str_replace( ' ', '+', $value['datauristring']);
                                    $imgData =  substr( $imgData, strpos( $imgData, "," )+1 );
                                    $imgData = base64_decode( $imgData );
                                    // Path where the image is going to be saved
                                    $folder = SUPER_PLUGIN_DIR . '/uploads/php/files';
                                    $folderResult = SUPER_Common::generate_random_folder($folder);
                                    $folderPath = $folderResult['folderPath'];
                                    $folderName = $folderResult['folderName'];
                                    $value['value'] = SUPER_Common::email_tags( $value['value'], $data, $settings );
                                    $value['emailLabel'] = SUPER_Common::email_tags( $value['emailLabel'], $data, $settings );
                                    $fileName = $value['value'];
                                    $fileLocation = trailingslashit($folderPath) . $fileName;
                                    // Write $imgData into the image file
                                    $file = fopen( $fileLocation, 'w' );
                                    fwrite( $file, $imgData );
                                    fclose( $file );
                                    $value['url'] = $fileLocation;
                                    $data[$k]['files'][$key] = $value;
                                } catch (Exception $e) {
                                    // Print error message
                                    SUPER_Common::output_message(
                                        $error = true,
                                        $e->getMessage()
                                    );
                                }
                            }

                            // Before we proceed check if the file already exists, if so, do nothing
                            // Exclude files that are being uploaded for the first time
                            // They will be in the "uploads/php" directory
                            $file = $value['url'];
                            if(!strpos($file, 'uploads/php/files')) {
                                $file_headers = @get_headers($file);
                                if($file_headers && $file_headers[0] != '404') {
                                    continue;
                                }
                            }

                            // If the file does not exists let's process it
                            $file = basename( $value['url'] );
                            $folder = basename( dirname( $value['url'] ) );
                            
                            // Skip if one of the values are empty
                            if( ($file=='') || ($folder=='') ) continue;

                            // Get source file
                            $sourcePath = SUPER_PLUGIN_DIR . '/uploads/php/files/' . $folder . '/' . $file;
                            $sourcePath = urldecode( $sourcePath );
                            // Determine location to store the file
                            $wp_upload_dir = wp_upload_dir();

                            // Default to super forms directory
                            $uploadPath = SUPER_FORMS_UPLOAD_DIR;
                            if(!empty($settings['file_upload_dir'])){
                                // User defined directory
                                $uploadPath = ABSPATH . $settings['file_upload_dir'];
                            }
                            $fileLocation = '';
                            if( !isset($settings['file_upload_use_year_month_folders']) || !empty($settings['file_upload_use_year_month_folders']) ) {
                                $fileLocation .= trailingslashit(wp_normalize_path($wp_upload_dir["subdir"]));
                                $folder = trailingslashit(wp_normalize_path($uploadPath . $wp_upload_dir["subdir"]));
                            }else{
                                $folder = trailingslashit(wp_normalize_path($uploadPath));
                            }

                            // Create directory if not yet exists and generate random directory
                            $folderResult = SUPER_Common::generate_random_folder($folder);

                            $folderPath = $folderResult['folderPath'];
                            $folderName = $folderResult['folderName'];
                            $fileLocation = trailingslashit(wp_normalize_path($fileLocation)) . $folderName;
                            $fileName = basename( $sourcePath );
                            $fileLocation = trailingslashit($fileLocation) . $fileName;

                            // By default upload files to Media Library
                            $uploadToMediaLibrary = true;
                            // Check if the file is being uploaded to the wordpress content directory
                            // If not we won't add it to the media library
                            $newfile = $folderPath . '/' . $fileName;
                            if(strpos($newfile, WP_CONTENT_DIR)===false){
                                $uploadToMediaLibrary = false;
                            }
                            $dir = str_replace( $fileName, '', $sourcePath );
                            if ( !copy( $sourcePath, $newfile ) ) {
                                $error = error_get_last();
                                SUPER_Common::delete_dir( dirname($sourcePath) );
                                SUPER_Common::delete_dir( $folderPath );
                                SUPER_Common::output_message(true, '<strong>' . esc_html__( 'Upload failed1', 'super-forms' ) . ':</strong> ' . $error['message']);
                            }else{
                                if( !empty( $dir ) ) $delete_dirs[] = $dir;
                                $filetype = wp_check_filetype( basename( $newfile ), null );
                                $attachment = array(
                                    'post_mime_type' => $filetype['type'],
                                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $newfile ) ),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );
                                // Only insert attachment if we are in root directory
                                if($uploadToMediaLibrary){
                                    $attachment_id = wp_insert_attachment( $attachment, $newfile );
                                    add_post_meta($attachment_id, 'super-forms-form-upload-file', true);
                                    require_once( ABSPATH . 'wp-admin/includes/image.php' );
                                    $attach_data = wp_generate_attachment_metadata( $attachment_id, $newfile );
                                    wp_update_attachment_metadata( $attachment_id,  $attach_data );
                                    $data[$k]['files'][$key]['attachment'] = $attachment_id;
                                    $data[$k]['files'][$key]['url'] = wp_get_attachment_url( $attachment_id );
                                }else{
                                    // If secure upload, update URL:
                                    $fileUrl = trailingslashit(get_home_url()) . 'sfgtfi' . $fileLocation;
                                    $data[$k]['files'][$key]['url'] = $fileUrl;
                                    $data[$k]['files'][$key]['path'] = $folderPath;
                                }
                            }
                        }
                    }
                }else{
                    // @since 1.2.9 - Save [label] or both [value and label], make sure we set the correct value if we do not want to save only the value of the element
                    if( isset( $v['entry_value'] ) ) {
                        $data[$k]['value'] = $v['entry_value'];
                    }
                }                   
            }
            foreach( $delete_dirs as $dir ) {
                SUPER_Common::delete_dir( $dir );
            }
        }
        
        // @since 4.9.5
        $data = apply_filters( 'super_after_processing_files_data_filter', $data, array( 'post'=>$_POST, 'settings'=>$settings ) );        

        // @since 2.8.0 - save generated code(s) into options table instaed of postmeta table per contact entry
        foreach( $data as $k => $v ) {
            if( (isset($v['code'])) && ($v['code']=='true') ) {
                
                // @since 2.8.0 - invoice numbers
                if( !empty($v['invoice_padding']) ) {
                    if ( ctype_digit( (string)$v['invoice_padding'] ) ) {
                        $number = get_option('_super_form_invoice_number', 0) + 1;
                        $number = update_option('_super_form_invoice_number', $number);
                        $v['value'] = sprintf('%0' . $v['invoice_padding'] . 'd', $number );
                    }
                }
                add_option( '_super_contact_entry_code-'.$v['value'], $v['value'], '', 'no' );
            }
        }


        // @since 4.0.0 - check if we do not want to save contact entry conditionally
        if( !empty($settings['conditionally_save_entry']) ) {
            $settings['save_contact_entry'] = 'no';
            if( !empty($settings['conditionally_save_entry_check']) ) {
                $values = explode(',', $settings['conditionally_save_entry_check']);
                // let's replace tags with values
                foreach( $values as $k => $v ) {
                    $values[$k] = SUPER_Common::email_tags( $v, $data, $settings );
                }
                if(!isset($values[0])) $values[0] = '';
                if(!isset($values[1])) $values[1] = '=='; // is either == or !=   (== by default)
                if(!isset($values[2])) $values[2] = '';

                // if at least 1 of the 2 is not empty then apply the check otherwise skip it completely
                if( ($values[0]!='') || ($values[2]!='') ) {
                    // Check if values match eachother
                    if( ($values[1]=='==') && ($values[0]==$values[2]) ) {
                        // we do not want to save the contact entry
                        $settings['save_contact_entry'] = 'yes';
                    }
                    // Check if values do not match eachother
                    if( ($values[1]=='!=') && ($values[0]!=$values[2]) ) {
                        // we do not want to save the contact entry
                        $settings['save_contact_entry'] = 'yes';
                    }

                }
            }
        }
            
        // @since 4.7.7 - prevent new Contact Entry from being created
        $entry_id = absint( $_POST['entry_id'] );
        if( ($entry_id!=0) && (!empty($settings['contact_entry_prevent_creation'])) ) {
            $settings['save_contact_entry'] = 'no';
        }

        $contact_entry_id = null;
        if( $settings['save_contact_entry']=='yes' ) {
            $post = array(
                'post_status' => 'super_unread',
                'post_type' => 'super_contact_entry' ,
                'post_parent' => $data['hidden_form_id']['value'] // @since 1.7 - save the form ID as the parent
            );

            // @since 3.8.0 - save the post author based on session if set (currently used by Register & Login Add-on)
            $post_author = SUPER_Forms()->session->get( 'super_update_user_meta' );
            if( $post_author!=false ) {
                $post['post_author'] = absint($post_author);
            }

            $contact_entry_id = wp_insert_post($post); 
            $response_data['contact_entry_id'] = $contact_entry_id;

            // @since 3.4.0 - save custom contact entry status
            $entry_status = sanitize_text_field( $_POST['entry_status'] );
            if($entry_status!=''){
                $settings['contact_entry_custom_status'] = $entry_status;
            }
            if( (isset($settings['contact_entry_custom_status'])) && ($settings['contact_entry_custom_status']!='') ) {
                add_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['contact_entry_custom_status'] );
            }

            // @since 1.4 - add the contact entry ID to the data array so we can use it to retrieve it with {tags}
            $data['contact_entry_id']['name'] = 'contact_entry_id';
            $data['contact_entry_id']['value'] = $contact_entry_id;
            $data['contact_entry_id']['label'] = '';
            $data['contact_entry_id']['type'] = 'form_id';

            // Update attachment post_parent to contact entry ID
            foreach( $data as $k => $v ) {
                if( (isset($v['type'])) && ($v['type']=='files') ) {
                    if( ( isset( $v['files'] ) ) && ( count( $v['files'] )!=0 ) ) {
                        foreach($v['files'] as $file){
                            $attachment = array(
                                'ID' => (!empty($file['attachment']) ? absint($file['attachment']) : 0),
                                'post_parent' => $contact_entry_id
                            );
                            wp_update_post( $attachment );
                        }
                    }
                }
            }

        }

        // @since 3.3.0 - exclude fields from saving as contact entry
        if(!isset($settings['contact_entry_exclude_empty'])) $settings['contact_entry_exclude_empty'] = '';
        $final_entry_data = array();
        if( ($settings['save_contact_entry']=='yes') || ($entry_id!=0) ) {
            foreach( $data as $k => $v ) {
                if( (isset($v['exclude_entry'])) && ($v['exclude_entry']=='true') ) {
                    continue;
                }else{
                    if(isset($v['type']) && ($v['type']=='form_id' || $v['type']=='entry_id')){
                        // Neve exclude these 2 types
                        $final_entry_data[$k] = $v;
                    }else{
                        // @since 4.5.0 - check if value is empty, and if we need to exclude it from being saved in the contact entry
                        if(isset($v['type']) && $v['type']=='files'){
                            if( $settings['contact_entry_exclude_empty']=='true' && ( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) ) {
                            }else{
                                $final_entry_data[$k] = $v;
                            }
                        }else{
                            if( $settings['contact_entry_exclude_empty']=='true' && empty($v['value']) ) {
                                // Except for _super_dynamic_data
                                if($k=='_super_dynamic_data') {
                                    $final_entry_data[$k] = $v;
                                }
                            }else{
                                $final_entry_data[$k] = $v;
                            }
                        }
                    }
                }
            }
        }

        // @since 2.2.0 - update contact entry data by ID
        if($entry_id!=0){
            $result = update_post_meta( $entry_id, '_super_contact_entry_data', $final_entry_data);

            // @since 3.4.0 - update contact entry status
            $entry_status_update = sanitize_text_field( $_POST['entry_status_update'] );
            if($entry_status_update!=''){
                $settings['contact_entry_custom_status_update'] = $entry_status_update;
            }
            if( (isset($settings['contact_entry_custom_status_update'])) && ($settings['contact_entry_custom_status_update']!='') ) {
                add_post_meta( $entry_id, '_super_contact_entry_status', $settings['contact_entry_custom_status_update'] );
            }
        }

        if( $settings['save_contact_entry']=='yes' ){
            add_post_meta( $contact_entry_id, '_super_contact_entry_data', $final_entry_data);
            add_post_meta( $contact_entry_id, '_super_contact_entry_ip', SUPER_Common::real_ip() );

            // @since 1.2.6     - custom contact entry titles
            $contact_entry_title = esc_html__( 'Contact entry', 'super-forms' );
            if( !isset( $settings['enable_custom_entry_title'] ) ) $settings['enable_custom_entry_title'] = '';
            if( $settings['enable_custom_entry_title']=='true' ) {
                if( !isset( $settings['contact_entry_title'] ) ) $settings['contact_entry_title'] = $contact_entry_title;
                if( !isset( $settings['contact_entry_add_id'] ) ) $settings['contact_entry_add_id'] = '';
                $contact_entry_title = SUPER_Common::email_tags( $settings['contact_entry_title'], $data, $settings );
                if($settings['contact_entry_add_id']=='true'){
                    if($contact_entry_title==''){
                        $contact_entry_title = $contact_entry_id;
                    }else{
                        $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
                    }
                }
            }else{
                $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
            }

            $contact_entry = array(
                'ID' => $contact_entry_id,
                'post_title' => $contact_entry_title,
            );
            wp_update_post( $contact_entry );

            /** 
             *  Hook after inserting contact entry
             *
             *  @param  post    $_POST
             *  @param  array   $settings
             *  @param  int     $contact_entry_id    @since v1.2.2
             *
             *  @since      1.2.9
            */
            do_action( 'super_after_saving_contact_entry_action', array( 'post'=>$_POST, 'data'=>$data, 'settings'=>$settings, 'entry_id'=>$contact_entry_id ) );

        }

        $settings = apply_filters( 'super_before_sending_email_settings_filter', $settings );
     
        if(!isset($settings['email_exclude_empty'])) $settings['email_exclude_empty'] = '';
        if(!isset($settings['confirm_exclude_empty'])) $settings['confirm_exclude_empty'] = '';

        $email_loop = '';
        $confirm_loop = '';
        $attachments = array();
        $confirm_attachments = array();
        $string_attachments = array();
        if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
            foreach( $data as $k => $v ) {
                // Skip dynamic data
                if($k=='_super_dynamic_data') continue;
                
                $row = $settings['email_loop'];
                $confirm_row = $row;
                if( isset($settings['confirm_email_loop']) ) {
                    $confirm_row = $settings['confirm_email_loop'];
                }
                // Exclude from emails
                // 0 = Do not exclude from e-mails
                // 1 = Exclude from confirmation email
                // 2 = Exclude from all email
                // 3 = Exclude from admin email
                if( !isset( $v['exclude'] ) ) {
                    $v['exclude'] = 0;
                }
                if( $v['exclude']==2 ) {
                    // Exclude from all emails
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
                $confirm_result = apply_filters( 'super_before_email_loop_data_filter', $confirm_row, array( 'v'=>$v, 'string_attachments'=>$string_attachments ) );
                $continue = false;
                if( isset( $result['status'] ) ) {
                    if( $result['status']=='continue' ) {
                        if( isset( $result['string_attachments'] ) ) {
                            $string_attachments = $result['string_attachments'];
                        }
                        $email_loop .= $result['row'];
                        $continue = true;
                    }
                }
                if( isset( $confirm_result['status'] ) ) {
                    if( $confirm_result['status']=='continue' ) {
                        if( isset( $confirm_result['string_attachments'] ) ) {
                            $string_attachments = $confirm_result['string_attachments'];
                        }
                        if( ( isset( $confirm_result['exclude'] ) ) && ( $confirm_result['exclude']==1 ) ) {
                        }else{
                            $confirm_loop .= $confirm_result['row'];
                        }
                        $continue = true;
                    }
                }
                if($continue) continue;

                if( isset($v['type']) && $v['type']=='files' ) {
                    $files_value = '';
                    if( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) {
                        $v['value'] = '';
                        if( !empty( $v['label'] ) ) {
                            // Replace %d with empty string if exists
                            $v['label'] = str_replace('%d', '', $v['label']);
                            $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                            $confirm_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $confirm_row );
                        }else{
                            $row = str_replace( '{loop_label}', '', $row );
                            $confirm_row = str_replace( '{loop_label}', '', $confirm_row );
                        }
                        $files_value .= esc_html__( 'User did not upload any files', 'super-forms' );
                    }else{
                        $v['value'] = '-';
                        foreach( $v['files'] as $key => $value ) {
                            // Check if user explicitely wants to remove files from {loop_fields} in emails
                            if(!empty($settings['file_upload_remove_from_email_loop'])) {
                                // Remove this row completely
                                $row = ''; 
                                $confirm_row = '';
                            }else{
                                if( $key==0 ) {
                                    if( !empty( $v['label'] ) ) {
                                        $v['label'] = str_replace('%d', '', $v['label']);
                                        $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                        $confirm_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $confirm_row );
                                    }else{
                                        $row = str_replace( '{loop_label}', '', $row );
                                        $confirm_row = str_replace( '{loop_label}', '', $confirm_row );
                                    }
                                }
                                // In case the file was deleted we do not want to add a hyperlink that links to the file
                                // In case the user explicitely choose to remove the hyperlink
                                if( !empty($settings['file_upload_submission_delete']) || 
                                    !empty($settings['file_upload_remove_hyperlink_in_emails']) ) {
                                    $files_value .= $value['value'] . '<br /><br />';
                                }else{
                                    $files_value .= '<a href="' . esc_url($value['url']) . '" target="_blank">' . esc_html($value['value']) . '</a><br /><br />';
                                }
                            }
                            // Check if we should exclude the file from emails
                            // 0 = Do not exclude from e-mails
                            // 1 = Exclude from confirmation email
                            // 2 = Exclude from all email
                            // 3 = Exclude from admin email
                            if( $v['exclude']!=2 ) {
                                // Get either URL or Secure file path
                                if(!empty($value['attachment'])){
                                    $fileValue = $value['url'];
                                }else{
                                    // See if this was a secure file upload
                                    if(!empty($value['path'])) $fileValue = wp_normalize_path(trailingslashit($value['path']) . $value['value']);
                                }
                                // 1 = Exclude from confirmation email
                                if( $v['exclude']==1 ) {
                                    $attachments[$value['value']] = $fileValue;
                                }else{
                                    // 3 = Exclude from admin email
                                    if( $v['exclude']==3 ) {
                                        $confirm_attachments[$value['value']] = $fileValue;
                                    }else{
                                        // Do not exclude
                                        $attachments[$value['value']] = $fileValue;
                                        $confirm_attachments[$value['value']] = $fileValue;
                                    }
                                }
                            }
                        }
                    }
                    $row = str_replace( '{loop_value}', $files_value, $row );
                    $confirm_row = str_replace( '{loop_value}', $files_value, $confirm_row );
                }else{
                    if( isset($v['type']) && (($v['type']=='form_id') || ($v['type']=='entry_id')) ) {
                        $row = '';
                        $confirm_row = '';
                    }else{

                        if( !empty( $v['label'] ) ) {
                            $v['label'] = str_replace('%d', '', $v['label']);
                            $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                            $confirm_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $confirm_row );
                        }else{
                            $row = str_replace( '{loop_label}', '', $row );
                            $confirm_row = str_replace( '{loop_label}', '', $confirm_row );
                        }
                        // @since 1.2.7
                        if( isset( $v['admin_value'] ) ) {
                            // @since 3.9.0 - replace comma's with HTML
                            if( !empty($v['replace_commas']) ) $v['admin_value'] = str_replace( ',', $v['replace_commas'], $v['admin_value'] );
                            
                            $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['admin_value'] ), $row );
                            $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['admin_value'] ), $confirm_row );
                        }
                        if( isset( $v['confirm_value'] ) ) {
                            // @since 3.9.0 - replace comma's with HTML
                            if( !empty($v['replace_commas']) ) $v['confirm_value'] = str_replace( ',', $v['replace_commas'], $v['confirm_value'] );
                            
                            $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['confirm_value'] ), $confirm_row );
                        }
                        if( isset( $v['value'] ) ) {
                            // @since 3.9.0 - replace comma's with HTML
                            if( !empty($v['replace_commas']) ) $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
                            
                            $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['value'] ), $row );
                            $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['value'] ), $confirm_row );
                        }

                    }
                }

                // @since 4.5.0 - check if value is empty, and if we need to exclude it from the email
                // 0 = Do not exclude from e-mails
                // 1 = Exclude from confirmation email
                // 2 = Exclude from all email
                // 3 = Exclude from admin email
                if( $v['exclude']==3 || ($settings['email_exclude_empty']=='true' && empty($v['value']) )) {
                    // Exclude from admin email loop
                }else{
                    $email_loop .= $row;
                }
                if( $v['exclude']==1 || ($settings['confirm_exclude_empty']=='true' && empty($v['value']) )) {
                    // Exclude from confirmation email loop
                }else{
                    $confirm_loop .= $confirm_row;
                }
            }
        }

        // @since 4.9.5 - override setting with global email settings
        // If we made it to here, retrieve global settings and check if any settings have "Force" enabled
        // meaning we should ignore any settings from the form itself and use the global setting instead
        $global_settings = SUPER_Common::get_global_settings();
        $overrideSettings = array(
            // Set global 'To' header, can override 'header_to' and 'confirm_to' settings
            'global_email_to_admin' => 'header_to',
            'global_email_to_confirm' => 'confirm_to',
            // Set global 'From' header, can override 'header_from' and 'confirm_from' settings
            'global_email_from' => array('header_from', 'confirm_from'),
            // Set global 'From name' header, can override 'header_from_name' and 'confirm_from_name' settings
            'global_email_from_name' => array('header_from_name', 'confirm_from_name'),
            // Set global 'Reply to' header, can override 'header_reply' and 'confirm_reply' settings
            'global_email_reply' => array('header_reply', 'confirm_reply'),
            // Set global 'Reply name' header, can override 'header_reply_name' and 'confirm_reply_name' settings
            'global_email_reply_name' => array('header_reply_name', 'confirm_reply_name'),
        );
        foreach($overrideSettings as $k => $v){
            if(!empty($global_settings[$k . '_force'])){
                if(is_array($v)){
                    foreach($v as $vv){
                        $settings[$vv] = $global_settings[$k];
                    }
                }else{
                    $settings[$v] = $global_settings[$k];
                }
            }
        }

        if( $settings['send']=='yes' ) {
            if(!empty($settings['email_body_open'])) $settings['email_body_open'] = $settings['email_body_open'] . '<br /><br />';
            if(!empty($settings['email_body'])) $settings['email_body'] = $settings['email_body'] . '<br /><br />';
            $email_body = $settings['email_body_open'] . $settings['email_body'] . $settings['email_body_close'];
            $email_body = str_replace( '{loop_fields}', $email_loop, $email_body );
            $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );
            
            // @since 3.1.0 - optionally automatically add line breaks
            if(!isset($settings['email_body_nl2br'])) $settings['email_body_nl2br'] = 'true';
            if($settings['email_body_nl2br']=='true') $email_body = nl2br( $email_body );
            
            // @since 4.9.5 - RTL email setting
            if(!isset($settings['email_rtl'])) $settings['email_rtl'] = '';
            if($settings['email_rtl']=='true') $email_body =  '<div dir="rtl" style="text-align:right;">' . $email_body . '</div>';

            $email_body = do_shortcode($email_body);
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
            $from_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['header_from_name'], $data, $settings ) );
            
            $cc = '';
            if( !empty($settings['header_cc']) ) {
                $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_cc'], $data, $settings ) );
            }
            $bcc = '';
            if( !empty($settings['header_bcc']) ) {
                $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_bcc'], $data, $settings ) );
            }
            
            $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['header_subject'], $data, $settings ) );

            // @since 2.8.0 - custom reply to headers
            if( !isset($settings['header_reply_enabled']) ) $settings['header_reply_enabled'] = false;
            $reply = '';
            $reply_name = '';
            if( $settings['header_reply_enabled']==false ) {
                $custom_reply = false;
            }else{
                $custom_reply = true;
                if( !isset($settings['header_reply']) ) $settings['header_reply'] = '';
                if( !isset($settings['header_reply_name']) ) $settings['header_reply_name'] = '';
                $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply'], $data, $settings ) );
                $reply_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['header_reply_name'], $data, $settings ) );
            }

            // @since 3.3.2 - default admin email attachments
            if( !empty($settings['admin_attachments']) ) {
                $email_attachments = explode( ',', $settings['admin_attachments'] );
                foreach($email_attachments as $k => $v){
                    $file = get_attached_file($v);
                    if( $file ) {
                        $url = wp_get_attachment_url($v);
                        $filename = basename ( $file );
                        $attachments[$filename] = $url;
                    }
                }
            }

            // @since 2.0
            $attachments = apply_filters( 'super_before_sending_email_attachments_filter', $attachments, array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$email_body ) );

            // Send the email
            $mail = SUPER_Common::email( $to, $from, $from_name, $custom_reply, $reply, $reply_name, $cc, $bcc, $subject, $email_body, $settings, $attachments, $string_attachments );

            // Return error message
            if( !empty( $mail->ErrorInfo ) ) {
                $msg = esc_html__( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
                SUPER_Common::output_message( $error=true, $msg );
            }
        }
        if( $settings['confirm']=='yes' ) {
            
            // @since 2.8.0 - additional header support for confirmation emails
            if( !isset($settings['confirm_header_additional']) ) $settings['confirm_header_additional'] = '';
            $settings['header_additional'] = $settings['confirm_header_additional'];
            
            if(!empty($settings['confirm_body_open'])) $settings['confirm_body_open'] = $settings['confirm_body_open'] . '<br /><br />';
            if(!empty($settings['confirm_body'])) $settings['confirm_body'] = $settings['confirm_body'] . '<br /><br />';
            $email_body = $settings['confirm_body_open'] . $settings['confirm_body'] . $settings['confirm_body_close'];
            $email_body = str_replace( '{loop_fields}', $confirm_loop, $email_body );
            $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );

            // @since 3.1.0 - optionally automatically add line breaks
            if(!isset($settings['confirm_body_nl2br'])) $settings['confirm_body_nl2br'] = 'true';
            if($settings['confirm_body_nl2br']=='true') $email_body = nl2br( $email_body );

            // @since 4.9.5 - RTL email setting
            if(!isset($settings['confirm_rtl'])) $settings['confirm_rtl'] = '';
            if($settings['confirm_rtl']=='true') $email_body = '<div dir="rtl" style="text-align:right;">' . $email_body . '</div>';
            
            $email_body = do_shortcode($email_body);
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
            $from_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['confirm_from_name'], $data, $settings ) );          
            $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['confirm_subject'], $data, $settings ) );

            // @since 2.8.0 - cc and bcc support for confirmation emails
            $cc = '';
            if( !empty($settings['confirm_header_cc']) ) {
                $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_header_cc'], $data, $settings ) );
            }
            $bcc = '';
            if( !empty($settings['confirm_header_bcc']) ) {
                $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_header_bcc'], $data, $settings ) );
            }

            // @since 2.8.0 - custom reply to headers
            if( !isset($settings['confirm_header_reply_enabled']) ) $settings['confirm_header_reply_enabled'] = false;
            $reply = '';
            $reply_name = '';
            if( $settings['confirm_header_reply_enabled']==false ) {
                $custom_reply = false;
            }else{
                $custom_reply = true;
                if( !isset($settings['confirm_header_reply']) ) $settings['confirm_header_reply'] = '';
                if( !isset($settings['confirm_header_reply_name']) ) $settings['confirm_header_reply_name'] = '';
                $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['confirm_header_reply'], $data, $settings ) );
                $reply_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['confirm_header_reply_name'], $data, $settings ) );
            }

            // @since 3.3.2 - default confirm email attachments
            if( !empty($settings['confirm_attachments']) ) {
                $email_attachments = explode( ',', $settings['confirm_attachments'] );
                foreach($email_attachments as $k => $v){
                    $file = get_attached_file($v);
                    if( $file ) {
                        $url = wp_get_attachment_url($v);
                        $filename = basename ( $file );
                        $confirm_attachments[$filename] = $url;
                    }
                }
            }

            // @since 2.0
            $confirm_attachments = apply_filters( 'super_before_sending_email_confirm_attachments_filter', $confirm_attachments, array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$email_body )  );

            // Send the email
            $mail = SUPER_Common::email( $to, $from, $from_name, $custom_reply, $reply, $reply_name, $cc, $bcc, $subject, $email_body, $settings, $confirm_attachments, $string_attachments );

            // Return error message
            if( !empty( $mail->ErrorInfo ) ) {
                $msg = esc_html__( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
                SUPER_Common::output_message( $error=true, $msg );
            }
        }
        if( $form_id!=0 ) {

            // Clear form progression
            SUPER_Forms()->session->set( 'super_form_progress_' . $form_id, false );

            // @since 3.4.0 - Form Locker - Lock form after specific amount of submissions (based on total contact entries created)
            if( ( isset( $settings['form_locker'] ) ) && ( $settings['form_locker']=='true' ) ) {
                $count = get_post_meta( $form_id, '_super_submission_count', true );
                update_post_meta( $form_id, '_super_submission_count', absint($count)+1 );
                update_post_meta( $form_id, '_super_last_submission_date', date_i18n('Y-m-d H:i:s') );
            }

            // @since 3.8.0 - Lock form after specific amount of submissions for logged in user (based on total contact entries created by user)
            if( ( isset( $settings['user_form_locker'] ) ) && ( $settings['user_form_locker']=='true' ) ) {
                // Let's check if the user is logged in
                $current_user_id = get_current_user_id();
                if( $current_user_id!=0 ) {
                    $user_limits = get_post_meta( $form_id, '_super_user_submission_counter', true );
                    if( !is_array($user_limits) ) {
                        $user_limits = array();
                    }
                    if( empty($user_limits[$current_user_id]) ) {
                        $user_limits[$current_user_id] = 1;
                    }else{
                        $user_limits[$current_user_id] = absint($user_limits[$current_user_id])+1;
                    }
                    update_post_meta( $form_id, '_super_user_submission_counter', $user_limits );
                    update_post_meta( $form_id, '_super_last_submission_date', date_i18n('Y-m-d H:i:s') );
                }
            }


            // @since 3.6.0 - custom POST parameters method
            if( empty($settings['form_post_custom']) ) $settings['form_post_custom'] = '';
            if( $settings['form_post_custom']=='true' ) {
                $parameter = array();
                if( empty($settings['form_post_parameters']) ) $settings['form_post_parameters'] = '';
                if(trim($settings['form_post_parameters'])==''){
                    // When left empty we will send all form data
                    foreach($data as $k => $v){
                        if( $v['type']=='files' ) {
                            $files = array();
                            if( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) {
                                $v['value'] = '';
                            }else{
                                $v['value'] = '-';
                                foreach( $v['files'] as $key => $value ) {
                                    $files[] = $value['url'];
                                }
                            }
                            $parameters[$k] = $files;
                        }else{
                            $parameters[$v['name']] = $v['value'];
                        }
                    }
                }else{
                    // If not empty only send specific fields
                    $form_post_parameters = explode( "\n", $settings['form_post_parameters'] );  
                    $new_form_post_parameters = $form_post_parameters;
                    foreach( $form_post_parameters as $k => $v ) {
                        $parameter =  explode( "|", $v );
                        if( isset( $parameter[0] ) ) $parameter_key = trim($parameter[0], '{}');
                        if( isset( $parameter[1] ) ) $parameter_tag = trim($parameter[1], '{}');

                        $looped = array();
                        $i=2;
                        while( isset( $data[$parameter_key . '_' . ($i)]) ) {
                            if(!in_array($i, $looped)){
                                $new_line = '';
                                if( $parameter[0][0]=='{' ) { $new_line .= '{' . $parameter_key . '_' . $i . '}'; }else{ $new_line .= $parameter[0]; }
                                if( $parameter[1][0]=='{' ) { $new_line .= '|{' . $parameter_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $parameter[1]; }
                                $new_form_post_parameters[] = $new_line;
                                $looped[$i] = $i;
                                $i++;
                            }else{
                                break;
                            }
                        }

                        $i=2;
                        while( isset( $data[$parameter_tag . '_' . ($i)]) ) {
                            if(!in_array($i, $looped)){
                                $new_line = '';
                                if( $parameter[0][0]=='{' ) { $new_line .= '{' . $parameter_key . '_' . $i . '}'; }else{ $new_line .= $parameter[0]; }
                                if( $parameter[1][0]=='{' ) { $new_line .= '|{' . $parameter_tag . '_' . $i . '}'; }else{ $new_line .= '|' . $parameter[1]; }
                                $new_form_post_parameters[] = $new_line;
                                $looped[$i] = $i;
                                $i++;
                            }else{
                                break;
                            }
                        }
                    }
                    foreach( $new_form_post_parameters as $k => $v ) {
                        if(empty($v)) continue;
                        $parameter =  explode( "|", $v );
                        $key = '';
                        $value = '';
                        $product_variation_id = '';
                        $product_price = '';
                        if( isset( $parameter[0] ) ) $key = SUPER_Common::email_tags( $parameter[0], $data, $settings );
                        if( isset( $parameter[1] ) ) $value = SUPER_Common::email_tags( $parameter[1], $data, $settings );
                        $parameters[$key] = $value;
                    }
                }

                // Include dynamic data
                if( !empty($settings['form_post_incl_dynamic_data']) && isset($data['_super_dynamic_data']) ) {
                    $parameters['_super_dynamic_data'] = $data['_super_dynamic_data'];
                }

                if( empty($settings['form_post_json']) ) $settings['form_post_json'] = '';
                if( empty($settings['form_post_timeout']) ) $settings['form_post_timeout'] = '5';
                if( empty($settings['form_post_http_version']) ) $settings['form_post_http_version'] = '1.0';
                if( empty($settings['form_post_debug']) ) $settings['form_post_debug'] = '';
                
                $headers = array();
                if($settings['form_post_json']=='true'){
                    $headers = array('Content-Type' => 'application/json; charset=utf-8');
                    $parameters = json_encode($parameters);
                }
                $response = wp_remote_post(
                    $settings['form_post_url'], 
                    array(
                        'method' => 'POST',
                        'timeout' => $settings['form_post_timeout'],
                        'httpversion' => $settings['form_post_http_version'],
                        'headers' => $headers,
                        'body' => $parameters
                    )
                );
                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $error_message,
                        $redirect = false
                    );
                }

                do_action( 'super_after_wp_remote_post_action', $response );

                if( $settings['form_post_debug']=='true' ) {
                    // Check if Array, if so convert to json
                    if(is_array($parameters)){
                        $parameters_output = json_encode($parameters);
                    }else{
                        $parameters_output = $parameters;
                    }
                    SUPER_Common::output_message(
                        $error = false,
                        $msg = '<strong>POST data:</strong><br /><textarea style="min-height:150px;width:100%;font-size:12px;">' . $parameters_output . '</textarea><br /><br /><strong>Response:</strong><br /><textarea style="min-height:150px;width:100%;font-size:12px;">' . $response['body'] . '</textarea>',
                        $redirect = false
                    );
                }
            }


            /** 
             *  Hook before outputing the success message or redirect after a succesfull submitted form
             *
             *  @param  post    $_POST
             *  @param  array   $settings
             *  @param  int     $contact_entry_id    @since v1.2.2
             *
             *  @since      1.0.2
            */
            
            // @since 4.6.0 - also parse all attachments (useful for external file storage through for instance Zapier)
            $attachments = array(
                'attachments' => $attachments,
                'confirm_attachments' => $confirm_attachments,
                'string_attachments' => $string_attachments
            );
            do_action( 'super_before_email_success_msg_action', array( 'post'=>$_POST, 'data'=>$data, 'settings'=>$settings, 'entry_id'=>$contact_entry_id, 'attachments'=>$attachments ) );

            // If the option to delete files after form submission is enabled remove all uploaded files from the server
            if( !empty($settings['file_upload_submission_delete']) ) {
                // Loop through all data with field typ 'files' and look for any uploaded attachments
                foreach( $data as $k => $v ) {
                    if( $v['type']=='files' ) {
                        if( ( isset( $v['files'] ) ) && ( count( $v['files'] )!=0 ) ) {
                            foreach($v['files'] as $file){
                                if(!empty($file['attachment'])){
                                    wp_delete_attachment( absint($file['attachment']), true );
                                }else{
                                    if(!empty($file['path'])){
                                        SUPER_Common::delete_dir( $file['path'] );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Return message or redirect and save message to session
            $redirect = null;
            $save_msg = false;
            if( (isset($settings['form_show_thanks_msg'])) && ($settings['form_show_thanks_msg']=='true') ) $save_msg = true;
            $settings['form_thanks_title'] = '<h1>' . $settings['form_thanks_title'] . '</h1>';

            $msg = do_shortcode( $settings['form_thanks_title'] . $settings['form_thanks_description'] );
            $msg = SUPER_Common::email_tags( $msg, $data, $settings );
            
            // @since 4.1.0 - option to do if statements in success message
            $msg = SUPER_Forms()->email_if_statements( $msg, $data );

            $session_data = array( 'msg'=>$msg, 'type'=>'success', 'data'=>$data, 'settings'=>$settings, 'entry_id'=>$contact_entry_id );
            if( !empty( $settings['form_redirect_option'] ) ) {
                if( $settings['form_redirect_option']=='page' ) {
                    $redirect = get_permalink( $settings['form_redirect_page'] );
                }
                if( $settings['form_redirect_option']=='custom' ) {
                    $redirect = SUPER_Common::email_tags( $settings['form_redirect'], $data, $settings );
                }
                if( $save_msg==true ) {
                    SUPER_Forms()->session->set( 'super_msg', $session_data );
                }
            }
            if( (!empty($settings['form_post_option'])) && ($save_msg==true) ) {
                SUPER_Forms()->session->set( 'super_msg', $session_data );
            }
            if($save_msg==false) $msg = '';

            /** 
             *  Filter to control the redirect URL
             *  e.g. Currenlty used for Front-end Posting add-on to redirect to the created post
             *
             *  @param  array  $data
             *  @param  array  $settings
             *
             *  @since      4.3.0
            */
            $redirect = apply_filters( 'super_redirect_url_filter', $redirect, array( 'data'=>$data, 'settings'=>$settings ) );
            
            SUPER_Common::output_message(
                $error=false, 
                $msg = $msg,
                $redirect = $redirect,
                $fields=array(),
                $display=true,
                $loading=false,
                $json=true,
                $response_data=$response_data
            );
            die();
        }
    }
}
endif;
SUPER_Ajax::init();
