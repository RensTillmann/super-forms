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
    private static $pending_owned_upload_cleanup = array();
    private static $owned_upload_cleanup_registered = false;

    
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

            'create_nonce'                  => true,
            'upload_files'                  => true,
            'submit_form'                   => true,
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
            'api_transfer_license'          => false,
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

            'listings_view_entry' => true,
            'listings_edit_entry' => true,
            'load_form_inside_modal' => true,
            'listings_delete_entry' => false,

        );
        add_action( 'super_cleanup_upload_receipt', array( __CLASS__, 'cleanup_expired_upload_receipt' ) );
        add_action( 'wp_loaded', array( __CLASS__, 'cleanup_expired_upload_receipts_fallback' ) );
        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_super_' . $ajax_event, array( __CLASS__, $ajax_event ) );
            }
        }
    }
    private static function export_attachment_url( $attachment_id ) {
        if ( ! $attachment_id ) {
            return '';
        }
        $url = SUPER_Forms::create_export_download_url( $attachment_id );
        if ( ! is_string( $url ) || '' === $url ) {
            if ( $attachment_id ) {
                wp_delete_attachment( $attachment_id, true );
            }
            throw new Exception( esc_html__( 'Unable to create a secure export download.', 'super-forms' ) );
        }
        return $url;
    }

    public static function create_nonce(){
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $capability = self::requested_public_populate_capability();
        if( $capability===null ) {
            echo self::request_uses_sessionless_submission_mode($form_id) ? '' : SUPER_Common::generate_nonce();
            die();
        }
        $response = array( 'sf_nonce' => '' );
        if( is_string($capability) && $capability!=='' ) {
            $response['sf_nonce'] = self::request_uses_sessionless_submission_mode($form_id) ? '' : SUPER_Common::generate_nonce();
            $response['capability'] = $capability;
        }
        echo wp_json_encode($response);
        die();
    }
    public static function listings_view_entry(){
        require_once( SUPER_PLUGIN_DIR . '/includes/class-common.php' );
        require_once( SUPER_PLUGIN_DIR . '/includes/extensions/listings/form-blank-page-template.php' );
        die();
    }
    public static function listings_edit_entry(){
        require_once( SUPER_PLUGIN_DIR . '/includes/class-common.php' );
        require_once( SUPER_PLUGIN_DIR . '/includes/extensions/listings/form-blank-page-template.php' );
        die();
    }
    public static function load_form_inside_modal(){
        require_once( SUPER_PLUGIN_DIR . '/includes/class-common.php' );
        if( !isset($_POST['entry_id'], $_POST['form_id'], $_POST['list_id'], $_POST['nonce']) ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form data.', 'super-forms' ) );
        }
        $_POST['action'] = 'super_listings_edit_entry';
        self::listings_edit_entry();
    }
    public static function listings_delete_entry(){
        $current_user_id = get_current_user_id();
        if( $current_user_id===0 ) {
            echo esc_html__( 'To delete this entry you must be logged in.', 'super-forms' );
            die();
        }

        $entry_id = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0;
        $form_id = isset($_POST['form_id']) ? SUPER_Listings::parse_form_id($_POST['form_id']) : false;
        $list_id = isset($_POST['list_id']) ? absint($_POST['list_id']) : -1;
        if( !$form_id || get_post_type($form_id)!=='super_form' ) {
            echo esc_html__( 'Permission denied, because this form does not exist', 'super-forms' );
            die();
        }
        $settings = SUPER_Common::get_form_settings($form_id);
        $lists = is_array($settings) && isset($settings['_listings']['lists'])
            && is_array($settings['_listings']['lists'])
            ? $settings['_listings']['lists']
            : array();
        if( !isset($lists[$list_id]) ) {
            echo esc_html__( 'Permission denied, because this list does not exist', 'super-forms' );
            die();
        }
        $list = SUPER_Listings::get_default_listings_settings($lists[$list_id]);
        if( !check_ajax_referer(
            'super_listings_delete_entry_' . $form_id . '_' . $list_id,
            'nonce',
            false
        ) ) {
            echo esc_html__( 'You do not have permission to delete this entry.', 'super-forms' );
            die();
        }

        $entry = $entry_id ? get_post($entry_id) : false;
        if( !($entry instanceof WP_Post)
            || $entry->post_type!=='super_contact_entry' ) {
            echo esc_html__( 'No entry found with ID:', 'super-forms' ) . ' ' . $entry_id;
            die();
        }
        if( !SUPER_Listings::entry_is_in_retrieval_scope($list, $entry->post_parent, $form_id) ) {
            echo esc_html__( 'You do not have permission to delete this entry.', 'super-forms' );
            die();
        }
        $allow = SUPER_Listings::get_action_permissions(array('list'=>$list, 'entry'=>$entry));
        $allow_delete_any = !empty($allow['allowDeleteAny']);
        $allow_delete_own = !empty($allow['allowDeleteOwn'])
            && absint($entry->post_author)===$current_user_id;
        if( !$allow_delete_any && !$allow_delete_own ) {
            echo esc_html__( 'You do not have permission to delete this entry.', 'super-forms' );
            die();
        }

        $permanent = $allow_delete_any
            ? (!empty($list['delete_any']['permanent']) && $list['delete_any']['permanent']==='true')
            : (!empty($list['delete_own']['permanent']) && $list['delete_own']['permanent']==='true');
        $deleted = $permanent ? wp_delete_post($entry_id, true) : wp_trash_post($entry_id);
        if( !$deleted ) {
            echo esc_html__( 'Unable to delete this entry.', 'super-forms' );
            die();
        }
        echo '1';
        die();
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
    public static function api_transfer_license() {
        $custom_args = array(
            'body' => (array(
                'domain' => (isset($_POST['domain']) ? $_POST['domain'] : ''),
                'slug' => $_POST['slug'],
                'data' => $_POST['data']
            ))
        );
        self::api_do_request('license/transfer', $custom_args);
    }
    public static function api_cancel_subscription() {
        $custom_args = array(
            'body' => (array(
                'domain' => (isset($_POST['domain']) ? $_POST['domain'] : ''),
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
                'l' => (isset($_POST['l']) ? $_POST['l'] : ''),
                'data' => $_POST['data']
            ))
        );
        self::api_do_request('addons/start_trial', $custom_args);
    }
    public static function api_checkout() {
        $custom_args = array('body' => $_POST);
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
                $body .= '<p>Error returned by server:</p><textarea style="width:100%;height:50px;">' . $err . '</textarea>';
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
        $submittingForm = ($_POST['submittingForm']==='true' ? true : false);
        $codesettings = wp_unslash($_POST['codesettings']);
        $codesettings = json_decode($codesettings, true);
        echo SUPER_Common::generate_random_code($codesettings, $submittingForm);
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
            'i18n' => (isset($_POST['i18n']) ? sanitize_text_field($_POST['i18n']) : ''),
            'parameters' => (isset($_POST['parameters']) ? $_POST['parameters'] : array()),
        );
        // Check if languages are used
        $verified = true;
        $settings = SUPER_Common::get_form_settings($atts['id']);
        if(empty($settings['i18n_switch'])) $settings['i18n_switch'] = 'false';
        if($settings['i18n_switch']!=='true'){
            // No need to do this, return message
            $verified = false;
        }
        $translations = SUPER_Common::get_form_translations($atts['id']);
        if( (!is_array($translations)) || ((is_array($translations)) && (count($translations)<2)) ) {
            // No need to do this, return message
            $verified = false;
        }
        $csrfValidation = SUPER_Common::verifyCSRF();
        if(!$csrfValidation){
            // Only check when not disabled by the user.
            // Some users want to use/load their forms via an iframe from a different domain name
            // In this case sessions won't work  because of browsers "SameSite by default cookies"
            $global_settings = SUPER_Common::get_global_settings();
            if(!empty($global_settings['csrf_check']) && $global_settings['csrf_check']==='false'){
                // Check was disabled by the user, skip it
            }else{
                // Return error
                $verified = false;
            }
        }
        if($verified===false){
            SUPER_Common::output_message( 
                $error = true, 
                esc_html__( 'Unable to switch language, session expired!', 'super-forms' )
            );
        }
        // @since 4.7.0 - translation RTL
        // check if the translation has enable RTL mode
        $rtl = false;
        if(!empty($translations[$atts['i18n']]) && !empty($translations[$atts['i18n']]['rtl'])){
            if($translations[$atts['i18n']]['rtl']=='true'){
                $rtl = true;
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

        $fields = SUPER_Settings::fields( $settings );
        $settings_html = '';

        $settings_html .= '<div class="super-form-settings-tabs">';
            $settings_html .= '<select>';
            $i = 0;
            foreach( $fields as $key => $value ) { 
                if( ( (!isset($value['hidden'])) || ($value['hidden']==false) || ($value['hidden']==='settings') ) && (!empty($value['name'])) ) {
                    $settings_html .= '<option value="' . $i . '" ' . ( $i==0 ? 'selected="selected"' : '') . '>' . $value['name'] . '</option>';
                    $i++;
                }
            }
            $settings_html .= '</select>';
            $settings_html .= SUPER_Common::reset_setting_icons(array(
                'default' => '_reset_',
                'g' => '_reset_',
                'v' => '_reset_'
            ));
        $settings_html .= '</div>';
        $counter = 0;

        foreach( $fields as $key => $value ) { 
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
                                    if( isset( $v['name'] ) ) {
                                        $settings_html .= '<div class="super-field-name">' . ($v['name']);
                                        if( isset( $v['desc'] ) ) {
                                            $settings_html .= '<i class="info super-tooltip" title="' . esc_attr($v['desc']) . '"></i>';
                                        }
                                    }
                                    if( isset( $v['label'] ) ) {
                                        $settings_html .= '<div class="super-field-label">' . nl2br($v['label']);
                                        if( !isset( $v['name'] ) && isset( $v['desc'] ) ) {
                                            $settings_html .= '<i class="info super-tooltip" title="' . esc_attr($v['desc']) . '"></i>';
                                        }
                                    }
                                    if( isset( $v['label'] ) ) $settings_html .= '</div>';
                                    if( isset( $v['name'] ) ) $settings_html .= '</div>';
                                    $settings_html .= '<div class="super-field-input">';
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
                                $settings_html .= '<div class="super-field super-field-type-'.$v['type'].'">';
                                    if( isset( $v['name'] ) ) {
                                        $settings_html .= '<div class="super-field-name">' . ($v['name']);
                                        if( isset( $v['desc'] ) ) {
                                            $settings_html .= '<i class="info super-tooltip" title="' . esc_attr($v['desc']) . '"></i>';
                                        }
                                    }
                                    if( isset( $v['label'] ) ) {
                                        $settings_html .= '<div class="super-field-label">' . nl2br($v['label']);
                                        if( !isset( $v['name'] ) && isset( $v['desc'] ) ) {
                                            $settings_html .= '<i class="info super-tooltip" title="' . esc_attr($v['desc']) . '"></i>';
                                        }
                                    }
                                    if( isset( $v['label'] ) ) $settings_html .= '</div>';
                                    if( isset( $v['name'] ) ) $settings_html .= '</div>';
                                    $settings_html .= '<div class="super-field-input">';
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
        self::authorize_admin_ajax_request();
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
        $capability = isset($_POST['capability']) && is_scalar($_POST['capability'])
            ? (string) wp_unslash($_POST['capability'])
            : '';
        $file_id = isset($_POST['file_id']) ? absint($_POST['file_id']) : 0;
        $data = ( isset( $_POST['data'] ) && is_array($_POST['data']) ) ? $_POST['data'] : array();
        $form_id = isset($data['hidden_form_id']['value']) ? absint($data['hidden_form_id']['value']) : 0;
        $form = get_post( $form_id );
        if( $file_id===0
            || $form_id===0
            || !$form
            || $form->post_type!=='super_form'
            || SUPER_Common::consume_public_print_capability(
                $capability,
                array(
                    'form_id' => $form_id,
                    'file_id' => $file_id,
                )
            )===false ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form data.', 'super-forms' ) );
        }
        $file = wp_get_attachment_url($file_id);
        $form_elements = SUPER_Common::get_form_elements($form_id);
        if( !is_string($file) || $file==='' || !is_array($form_elements) ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form data.', 'super-forms' ) );
        }
        $data = self::rebuild_selection_entry_values($data, $form_elements, $form_id);
        if( !is_array($data) ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form data.', 'super-forms' ) );
        }
        $html = wp_remote_fopen($file);
        if( !is_string($html) ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form data.', 'super-forms' ) );
        }
        if( !headers_sent() ) {
            $next_capability = SUPER_Common::issue_public_print_capability( array(
                'form_id' => $form_id,
                'file_id' => $file_id,
            ) );
            if( is_string($next_capability) && $next_capability!=='' ) {
                header( 'X-Super-Print-Capability: ' . $next_capability );
            }
        }
        $settings = SUPER_Common::get_form_settings($form_id);
        $html = SUPER_Common::email_tags( $html, $data, $settings );
        $html = SUPER_Forms()->email_if_statements( $html, $data );
        echo $html;
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
        self::authorize_admin_ajax_request();
        $form_id = absint($_POST['form_id']);
        self::require_admin_form( $form_id );
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
        self::authorize_admin_ajax_request();
        $form_id = absint($_POST['form_id']);
        self::require_admin_form( $form_id );
        delete_post_meta( $form_id, '_super_user_submission_counter' );
        die();
    }

    
    /** 
     *  Bulk edit contact entry status
     *
     *  @since      3.4.0
    */
    public static function bulk_edit_entries() {
        self::authorize_admin_ajax_request();
        if( (isset($_POST['entry_status'])) && ($_POST['entry_status'] != -1) ) {
            $post_ids = (!empty($_POST['post_ids'])) ? $_POST['post_ids'] : array();
            if( !empty($post_ids) && is_array($post_ids) ) {
                $validated_ids = array();
                foreach( $post_ids as $post_id ) {
                    $post_id = absint($post_id);
                    self::require_admin_contact_entry($post_id);
                    $validated_ids[] = $post_id;
                }
                $entry_status = sanitize_text_field(wp_unslash($_POST['entry_status']));
                foreach( $validated_ids as $post_id ) {
                    if($entry_status===''){
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
            SUPER_Common::setClientData( array( 'name' => 'progress_' . $form_id, 'value' => $data ) );
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
        $url = 'https://maps.googleapis.com/maps/api/directions/json?';
        $origin = sanitize_text_field($_POST['origin']);
        $destination = sanitize_text_field($_POST['destination']);
        $url .= 'origin=' . $origin . '&destination=' . $destination;
        $global_settings = SUPER_Common::get_global_settings();
        if( !empty($global_settings['form_google_places_api']) ) $url .= '&key=' . $global_settings['form_google_places_api'];
        if( !empty($global_settings['google_maps_api_language']) ) $url .= '&language=' . $global_settings['google_maps_api_language'];
        if( !empty($global_settings['google_maps_api_region']) ) $url .= '&region=' . $global_settings['google_maps_api_region'];
        if($units=='imperial') $url .= '&units=imperial';
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
        self::authorize_admin_ajax_request();
        $form_id = absint($_POST['form_id']);
        self::require_admin_form( $form_id );

        // Only delete selected backup
        if( isset($_POST['backup_id']) ) {
            $backup_id = absint($_POST['backup_id']);
            self::require_admin_form_backup( $backup_id, $form_id );
            wp_delete_post( $backup_id, true );
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
        self::authorize_admin_ajax_request();
        $form_id = absint($_POST['form_id']);
        self::require_admin_form( $form_id );

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
        $backup_id = absint($_POST['backup_id']);
        self::require_admin_form_backup( $backup_id, $form_id );

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
            echo '<li class="super-item" data-value="' . esc_attr( $v['value'] ) . '" data-search-value="' . esc_attr( $v['label'] ) . '">' . esc_html( $v['label'] ) . '</li>';
        }
        die();
    }

    private static function collect_public_entry_search_fields( $elements, $field_name, &$matches=array() ) {
        if( !is_array($elements) || !is_string($field_name) || $field_name==='' ) {
            return;
        }
        foreach( $elements as $element ) {
            if( !is_array($element) ) {
                continue;
            }
            if( !empty($element['inner']) ) {
                self::collect_public_entry_search_fields( $element['inner'], $field_name, $matches );
            }
            $data = ( isset($element['data']) && is_array($element['data']) ) ? $element['data'] : array();
            if( ( isset($element['tag']) ? $element['tag'] : '' )!=='text'
                || empty($data['enable_search'])
                || $data['enable_search']!=='true'
                || !isset($data['name'])
                || !is_string($data['name'])
                || $data['name']!==$field_name ) {
                continue;
            }
            $matches[] = $element;
        }
    }

    private static function collect_public_wc_order_search_fields( $elements, $field_name, &$matches=array() ) {
        if( !is_array($elements) || !is_string($field_name) || $field_name==='' ) {
            return;
        }
        foreach( $elements as $element ) {
            if( !is_array($element) ) {
                continue;
            }
            if( !empty($element['inner']) ) {
                self::collect_public_wc_order_search_fields( $element['inner'], $field_name, $matches );
            }
            $data = ( isset($element['data']) && is_array($element['data']) ) ? $element['data'] : array();
            if( ( isset($element['tag']) ? $element['tag'] : '' )!=='text'
                || empty($data['wc_order_search'])
                || $data['wc_order_search']!=='true'
                || empty($data['wc_order_search_populate'])
                || $data['wc_order_search_populate']!=='true'
                || !isset($data['name'])
                || !is_string($data['name'])
                || $data['name']!==$field_name ) {
                continue;
            }
            $matches[] = $element;
        }
    }

    private static function public_entry_search_contract( $form_id, $field_name ) {
        $form_id = absint($form_id);
        if( $form_id===0 || !is_string($field_name) || $field_name==='' ) {
            return false;
        }
        $matches = array();
        self::collect_public_entry_search_fields( SUPER_Common::get_form_elements($form_id), $field_name, $matches );
        if( count($matches)!==1 ) {
            return false;
        }
        $data = ( isset($matches[0]['data']) && is_array($matches[0]['data']) ) ? $matches[0]['data'] : array();
        $method = ( isset($data['search_method']) && is_string($data['search_method']) ) ? $data['search_method'] : '';
        if( !in_array($method, array('equals', 'contains'), true) ) {
            return false;
        }
        $skip = ( isset($data['search_skip']) && is_string($data['search_skip']) ) ? sanitize_text_field($data['search_skip']) : '';
        return array(
            'form_id' => $form_id,
            'field_name' => $field_name,
            'method' => $method,
            'skip' => $skip,
            'result_scope' => 'contact_entry',
        );
    }

    private static function public_wc_order_search_contract( $form_id, $field_name ) {
        $form_id = absint($form_id);
        if( $form_id===0 || !is_string($field_name) || $field_name==='' ) {
            return false;
        }
        $matches = array();
        self::collect_public_wc_order_search_fields( SUPER_Common::get_form_elements($form_id), $field_name, $matches );
        if( count($matches)!==1 ) {
            return false;
        }
        $data = ( isset($matches[0]['data']) && is_array($matches[0]['data']) ) ? $matches[0]['data'] : array();
        $skip = ( isset($data['wc_order_search_skip']) && is_string($data['wc_order_search_skip']) ) ? sanitize_text_field($data['wc_order_search_skip']) : '';
        return array(
            'form_id' => $form_id,
            'field_name' => $field_name,
            'method' => 'wc_order_id',
            'skip' => $skip,
            'result_scope' => 'wc_order_entry',
        );
    }

    private static function public_populate_contract_matches( $expected, $actual ) {
        return is_array($expected)
            && is_array($actual)
            && isset($expected['form_id'], $expected['field_name'], $expected['method'], $expected['skip'], $expected['result_scope'])
            && isset($actual['form_id'], $actual['field_name'], $actual['method'], $actual['skip'], $actual['result_scope'])
            && absint($expected['form_id'])===absint($actual['form_id'])
            && $expected['field_name']===$actual['field_name']
            && $expected['method']===$actual['method']
            && $expected['skip']===$actual['skip']
            && $expected['result_scope']===$actual['result_scope'];
    }

    private static function requested_public_populate_capability() {
        if( !isset($_POST['form_id'], $_POST['field_name'], $_POST['method']) ) {
            return null;
        }
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $field_name = isset($_POST['field_name']) && is_scalar($_POST['field_name'])
            ? (string) wp_unslash($_POST['field_name'])
            : '';
        $method = isset($_POST['method']) && is_scalar($_POST['method'])
            ? sanitize_text_field(wp_unslash($_POST['method']))
            : '';
        $skip = isset($_POST['skip']) && is_scalar($_POST['skip'])
            ? sanitize_text_field(wp_unslash($_POST['skip']))
            : '';
        if( $form_id===0 || $field_name==='' || $method==='' ) {
            return false;
        }
        if( !check_ajax_referer( 'super_create_nonce_' . $form_id, 'nonce', false ) ) {
            return false;
        }
        $contract = ( $method==='wc_order_id' )
            ? self::public_wc_order_search_contract( $form_id, $field_name )
            : self::public_entry_search_contract( $form_id, $field_name );
        if( $contract===false
            || $contract['method']!==$method
            || $contract['skip']!==$skip ) {
            return false;
        }
        return SUPER_Common::issue_public_populate_capability($contract);
    }

    private static function attach_public_populate_capability( $response, $contract ) {
        if( !is_array($response) ) {
            $response = array();
        }
        $token = SUPER_Common::issue_public_populate_capability($contract);
        if( is_string($token) && $token!=='' ) {
            $response['_super_populate_capability'] = $token;
            if( !headers_sent() ) {
                header( 'X-Super-Populate-Capability: ' . $token );
            }
        }
        return $response;
    }
    private static function public_populate_capability_rejected_response() {
        return array( '_super_capability_rejected' => true );
    }
    private static function issue_public_populate_update_grant( $form_id, $entry_id ) {
        $form_id = absint($form_id);
        $entry_id = absint($entry_id);
        if( $form_id===0 || $entry_id===0 ) {
            return false;
        }
        $grant = SUPER_Common::current_entry_update_grant_value(true);
        if( !is_array($grant) ) {
            return false;
        }
        $name = 'update_contact_entry_' . $form_id . '_' . $entry_id;
        SUPER_Common::setClientData( array(
            'name' => $name,
            'value' => $grant,
            'force' => true
        ) );
        return SUPER_Common::entry_update_grant_matches_current(
            SUPER_Common::getClientData( $name, false )
        );
    }

    private static function public_populate_response_data( $data, $form_id, $preserve_entry_id=false ) {
        if( !is_array($data) ) {
            return array();
        }
        $settings = SUPER_Common::get_form_settings($form_id);
        $entry_id = isset($data['hidden_contact_entry_id']['value'])
            ? absint($data['hidden_contact_entry_id']['value'])
            : 0;
        if( $entry_id!==0
            && !SUPER_Common::entry_has_wc_order( $entry_id )
            && is_array($settings)
            && !empty($settings['update_contact_entry'])
            && $settings['update_contact_entry']==='true'
            && self::issue_public_populate_update_grant( $form_id, $entry_id ) ) {
            return $data;
        }
        if( !$preserve_entry_id ) {
            unset($data['hidden_contact_entry_id']);
        }
        return $data;
    }

    private static function public_entry_search_data( $form_id, $field_name, $value, $method, $skip ) {
        global $wpdb;
        $contract = self::public_entry_search_contract( $form_id, $field_name );
        if( $contract===false
            || !is_string($value)
            || !is_string($method)
            || !is_string($skip)
            || $contract['method']!==$method
            || $contract['skip']!==sanitize_text_field($skip) ) {
            return array();
        }
        $table = $wpdb->posts;
        if( $method==='equals' ) {
            $query = $wpdb->prepare(
                "SELECT ID FROM {$table}
                WHERE post_parent = %d
                AND post_title = BINARY %s
                AND post_status IN ('publish','super_unread','super_read')
                AND post_type = 'super_contact_entry'
                LIMIT 1",
                $contract['form_id'],
                $value
            );
        }else{
            $query = $wpdb->prepare(
                "SELECT ID FROM {$table}
                WHERE post_parent = %d
                AND post_title LIKE BINARY %s
                AND post_status IN ('publish','super_unread','super_read')
                AND post_type = 'super_contact_entry'
                LIMIT 1",
                $contract['form_id'],
                '%' . $wpdb->esc_like($value) . '%'
            );
        }
        $entry_id = absint($wpdb->get_var($query));
        if( $entry_id===0 ) {
            return array();
        }
        $data = SUPER_Data_Access::get_entry_data( $entry_id );
        if( !is_array($data) ) {
            return array();
        }
        unset($data['hidden_form_id']);
        $entry_status = get_post_meta( $entry_id, '_super_contact_entry_status', true );
        if( empty($entry_status) ) {
            $entry_status = get_post_status($entry_id);
        }
        $data['hidden_contact_entry_status'] = array(
            'name' => 'hidden_contact_entry_status',
            'value' => $entry_status,
            'type' => 'var'
        );
        $data['hidden_contact_entry_id'] = array(
            'name' => 'hidden_contact_entry_id',
            'value' => $entry_id,
            'type' => 'entry_id'
        );
        $data['hidden_contact_entry_title'] = array(
            'name' => 'hidden_contact_entry_title',
            'value' => get_the_title($entry_id),
            'type' => 'var'
        );
        foreach( explode( '|', sanitize_text_field($skip) ) as $skip_field_name ) {
            if( isset($data[$skip_field_name]) ) {
                unset($data[$skip_field_name]);
            }
        }
        return $data;
    }
    /** 
     *  Populate form with contact entry data
     *
     *  @since      2.2.0
    */
    public static function populate_form_data() {
        $capability = isset($_POST['capability']) && is_scalar($_POST['capability'])
            ? (string) wp_unslash($_POST['capability'])
            : '';
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $field_name = isset($_POST['field_name']) && is_scalar($_POST['field_name'])
            ? (string) wp_unslash($_POST['field_name'])
            : '';
        $method = isset($_POST['method']) && is_scalar($_POST['method'])
            ? sanitize_text_field(wp_unslash($_POST['method']))
            : '';
        $skip = isset($_POST['skip']) && is_scalar($_POST['skip'])
            ? sanitize_text_field(wp_unslash($_POST['skip']))
            : '';
        if(isset($_POST['order_id'])){
            $order_id = absint($_POST['order_id']);
            $presented = SUPER_Common::consume_public_populate_capability( $capability, array(
                'form_id' => $form_id,
                'field_name' => $field_name,
                'method' => $method,
                'skip' => $skip,
                'result_scope' => 'wc_order_entry',
            ) );
            $contract = ( $presented===false ) ? false : self::public_wc_order_search_contract( $presented['form_id'], $presented['field_name'] );
            if( $presented===false || $contract===false || !self::public_populate_contract_matches( $presented, $contract ) ) {
                echo wp_json_encode( self::public_populate_capability_rejected_response() );
                die();
            }
            echo wp_json_encode(
                self::attach_public_populate_capability(
                    self::public_populate_response_data(
                        SUPER_Common::get_entry_data_by_wc_order_id( $order_id, $contract['skip'], $contract['form_id'] ),
                        $contract['form_id']
                    ),
                    $contract
                )
            );
        }else{
            $value = isset($_POST['value']) && is_scalar($_POST['value'])
                ? sanitize_text_field(wp_unslash($_POST['value']))
                : '';
            $presented = SUPER_Common::consume_public_populate_capability( $capability, array(
                'form_id' => $form_id,
                'field_name' => $field_name,
                'method' => $method,
                'skip' => $skip,
                'result_scope' => 'contact_entry',
            ) );
            $contract = ( $presented===false ) ? false : self::public_entry_search_contract( $presented['form_id'], $presented['field_name'] );
            if( $presented===false || $contract===false || !self::public_populate_contract_matches( $presented, $contract ) ) {
                echo wp_json_encode( self::public_populate_capability_rejected_response() );
                die();
            }
            echo wp_json_encode(
                self::attach_public_populate_capability(
                    self::public_populate_response_data(
                        self::public_entry_search_data( $contract['form_id'], $contract['field_name'], $value, $contract['method'], $contract['skip'] ),
                        $contract['form_id'],
                        true
                    ),
                    $contract
                )
            );
        }
        die();
    }


    /** 
     *  Update contact entry data
     *
     *  @since      1.7
    */
    public static function update_contact_entry() {
        self::authorize_admin_ajax_request();
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        self::require_admin_contact_entry($id);
        $new_data = (isset($_POST['data']) && is_array($_POST['data'])) ? wp_unslash($_POST['data']) : array();
        foreach( $new_data as $field_name => $value ) {
            if( !is_string($field_name) || $field_name==='' || !is_scalar($value) ) {
                SUPER_Common::output_message(
                    $error = true,
                    $msg = esc_html__( 'Invalid contact entry data.', 'super-forms' )
                );
            }
        }

        // @since 3.3.0 - update Contact Entry title
        $entry_title = isset($new_data['super_contact_entry_post_title'])
            ? sanitize_text_field($new_data['super_contact_entry_post_title'])
            : '';
        unset($new_data['super_contact_entry_post_title']);
        $entry = array(
            'ID' => $id,
            'post_title' => $entry_title
        );
        wp_update_post( $entry );

        // @since 3.4.0 - update contact entry status
        $entry_status = isset($_POST['entry_status'])
            ? sanitize_text_field(wp_unslash($_POST['entry_status']))
            : '';
        update_post_meta( $id, '_super_contact_entry_status', $entry_status);

        $data = SUPER_Data_Access::get_entry_data( $id );
        // If doesn't exist, we don't have to do anything, must be of type Array
        if( ($data!=='') && (is_array($data)) ) {
            foreach( $data as $k => $v ) {
                if( array_key_exists($k, $new_data) ) {
                    $data[$k]['value'] = self::sanitize_contact_entry_editor_value( $v, (string) $new_data[$k] );
                }
            }
            SUPER_Data_Access::update_entry_data( $id, $data );
            if( SUPER_Data_Access::get_entry_data( $id )!==$data ) {
                SUPER_Common::output_message(
                    $error = true,
                    $msg = esc_html__( 'Unable to save contact entry.', 'super-forms' )
                );
            }
        }
        SUPER_Common::output_message(
            $error = false,
            $msg = esc_html__( 'Contact entry updated.', 'super-forms' )
        );
        die();
    }
    private static function sanitize_contact_entry_editor_value( $field, $value ) {
        $type = (is_array($field) && isset($field['type']) && is_string($field['type'])) ? $field['type'] : '';
        if( $type==='text' ) {
            return sanitize_textarea_field($value);
        }
        return sanitize_text_field($value);
    }


    /** 
     *  Export selected entries to CSV
     *
     *  @since      1.7
    */
    public static function export_selected_entries() {
        self::authorize_admin_ajax_request();
        global $wpdb;

        $columns = isset($_POST['columns']) && is_array($_POST['columns']) ? $_POST['columns'] : array();
        if( empty($columns) ) {
            self::reject_admin_ajax_request();
        }
        foreach( $columns as $field_name => $column_name ) {
            if( !is_scalar($column_name) || (string) $field_name==='' ) {
                self::reject_admin_ajax_request();
            }
            $columns[$field_name] = sanitize_text_field(wp_unslash((string) $column_name));
        }

        $entry_ids = self::strict_positive_id_array(isset($_POST['entries']) ? $_POST['entries'] : null);
        $form_ids = self::admin_contact_entry_form_scope();
        self::require_admin_contact_entries($entry_ids, $form_ids);
        $order_by_filter = self::contact_entry_order_clause();
        $delimiter = self::contact_entry_csv_character('delimiter', ',');
        $enclosure = self::contact_entry_csv_character('enclosure', '"');

        $rows = array();
        foreach( $columns as $k => $v ) {
            $rows[0][$k] = $v;
        }

        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $entry_placeholders = implode(', ', array_fill(0, count($entry_ids), '%d'));
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_date, post_author, post_status, meta.meta_value AS data
                FROM $table AS entry
                INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID AND meta.meta_key = '_super_contact_entry_data'
                WHERE entry.post_status IN ('publish','super_unread','super_read')
                AND entry.post_type = 'super_contact_entry'
                AND entry.ID IN ($entry_placeholders)
                ORDER BY $order_by_filter",
                $entry_ids
            )
        );

        foreach( $entries as $k => $v ) {
            $data = maybe_unserialize($v->data);
            if( !is_array($data) ) {
                $data = array();
            }
            $data['entry_id']['value'] = $v->ID;
            $data['entry_title']['value'] = $v->post_title;
            $data['entry_date']['value'] = $v->post_date;
            $data['entry_author']['value'] = $v->post_author;
            $data['entry_status']['value'] = $v->post_status;
            $data['entry_ip']['value'] = get_post_meta($v->ID, '_super_contact_entry_ip', true);

            // @since 3.4.0 - custom entry status
            $data['entry_custom_status']['value'] = get_post_meta($v->ID, '_super_contact_entry_status', true);

            $entries[$k] = $data;
        }
        // Filter to alter for instance the "entry_date" format from 19:00 to 06:00 Pm
        $entries = apply_filters('super_export_selected_entries_filter', $entries);

        foreach( $entries as $k => $v ) {
            foreach( $columns as $ck => $cv ) {
                if( isset($v[$ck]) ) {
                    if( isset($v[$ck]['type']) && $v[$ck]['type']==='files' ) {
                        $files = '';
                        if( isset($v[$ck]['files']) && is_array($v[$ck]['files']) ) {
                            foreach( $v[$ck]['files'] as $fk => $fv ) {
                                if( !isset($fv['url']) ) {
                                    continue;
                                }
                                if( $fk!==0 && $files!=='' ) {
                                    $files .= PHP_EOL;
                                }
                                $files .= $fv['url'];
                            }
                        }
                        $rows[$k+1][] = $files;
                    }else{
                        $rows[$k+1][] = isset($v[$ck]['value']) ? $v[$ck]['value'] : '';
                    }
                }else{
                    $rows[$k+1][] = '';
                }
            }
        }

        try {
            $d = wp_upload_dir();
            if( !empty($d['error']) || empty($d['path']) ) {
                throw new Exception(esc_html__('Unable to create the export file.', 'super-forms'));
            }
            $basename = 'super-contact-entries-' . self::export_filename_entropy() . '.csv';
            $filename = trailingslashit($d['path']) . $basename;
            $fp = fopen($filename, 'x');
            if( $fp===false ) {
                throw new Exception(esc_html__('Unable to create the export file.', 'super-forms'));
            }
            $bom = apply_filters('super_csv_bom_header_filter', chr(0xEF).chr(0xBB).chr(0xBF));
            if( fwrite($fp, $bom)===false ) {
                fclose($fp);
                unlink($filename);
                throw new Exception(esc_html__('Unable to write the export file.', 'super-forms'));
            }
            foreach( $rows as $fields ) {
                if( SUPER_Common::write_csv_row($fp, $fields, $delimiter, $enclosure)===false ) {
                    fclose($fp);
                    unlink($filename);
                    throw new Exception(esc_html__('Unable to write the export file.', 'super-forms'));
                }
            }
            fclose($fp);
            $attachment = array(
                'post_mime_type' => 'text/csv',
                'post_title' => preg_replace('/\.[^.]+$/', '', $basename),
                'post_content' => '',
                'post_status' => 'private',
                'post_author' => get_current_user_id()
            );
            $attachment_id = wp_insert_attachment($attachment, $filename, 0);
            if( is_wp_error($attachment_id) || !$attachment_id ) {
                unlink($filename);
                throw new Exception(esc_html__('Unable to register the export file.', 'super-forms'));
            }
            $attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
            wp_update_attachment_metadata($attachment_id, $attach_data);
            echo self::export_attachment_url($attachment_id);
            die();
        } catch (Exception $e) {
            SUPER_Common::output_message(
                $error = true,
                $e->getMessage()
            );
        }
    }


    /** 
     *  Return entry export columns
     *
     *  @since      1.7
    */
    public static function get_entry_export_columns() {
        self::authorize_admin_ajax_request();
        global $wpdb;

        $entry_ids = self::strict_positive_id_array(isset($_POST['entries']) ? $_POST['entries'] : null);
        $form_ids = self::admin_contact_entry_form_scope();
        self::require_admin_contact_entries($entry_ids, $form_ids);

        $global_settings = SUPER_Common::get_global_settings();
        $fields = isset($global_settings['backend_contact_entry_list_fields'])
            ? explode("\n", $global_settings['backend_contact_entry_list_fields'])
            : array();

        $column_settings = array();
        foreach( $fields as $field_setting ) {
            $field = explode('|', $field_setting, 2);
            if( count($field)===2 && $field[0]!=='' ) {
                $column_settings[$field[0]] = $field[1];
            }
        }

        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $entry_placeholders = implode(', ', array_fill(0, count($entry_ids), '%d'));
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta.meta_value AS data
                FROM $table AS entry
                INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID AND meta.meta_key = '_super_contact_entry_data'
                WHERE entry.post_status IN ('publish','super_unread','super_read')
                AND entry.post_type = 'super_contact_entry'
                AND entry.ID IN ($entry_placeholders)",
                $entry_ids
            )
        );
        $columns = array(
            'entry_id',
            'entry_title',
            'entry_date',
            'entry_author',
            'entry_status'
        );
        foreach( $results as $result ) {
            $data = maybe_unserialize($result->data);
            if( !is_array($data) ) {
                continue;
            }
            foreach( $data as $field_name => $field_data ) {
                if( !in_array($field_name, $columns, true) ) {
                    $columns[] = $field_name;
                }
            }
        }
        $columns[] = 'entry_ip';

        echo '<div class="super-contact-entries-export-modal">';
        echo '<span class="button super-export-selected-columns-toggle" style="margin-top:10px;">'.esc_html__('Toggle all fields', 'super-forms').'</span>';
        echo '<span class="button button-primary button-large super-export-selected-columns" style="margin: 10px 30px 0px 0px;">'.esc_html__('Export', 'super-forms').'</span>';
        echo '<ul class="super-export-entry-columns">';
        foreach( $columns as $column ) {
            echo '<li class="super-entry-column" data-name="' . esc_attr($column) . '">';
            echo '<input type="checkbox"' . (isset($column_settings[$column]) ? ' checked="checked"' : '') . ' />';
            echo '<span class="name">' . esc_html($column) . '</span>';
            echo '<input type="text" value="' . esc_attr(isset($column_settings[$column]) ? $column_settings[$column] : $column) . '" />';
            echo '<span class="sort"></span>';
            echo '</li>';
        }
        echo '</ul>';
        foreach( $entry_ids as $entry_id ) {
            echo '<input type="hidden" name="entries[]" value="' . esc_attr($entry_id) . '" />';
        }
        echo '<label>Delimiter: <input type="text" name="delimiter" value="," /></label>';
        echo '<label>Enclosure: <input type="text" name="enclosure" value="' . esc_attr('"') . '" /></label>';
        echo '<label>Sort: <select name="order_by"><option value="ASC">ASC - Oldest first (default)</option><option value="DESC">DESC - Newest first</option></select></label>';
        echo '<span class="button button-primary button-large super-export-selected-columns" style="margin: 0px 30px 0px 0px;">'.esc_html__('Export', 'super-forms').'</span>';
        echo '</div>';
        die();
    }


    /** 
     *  Install demos item
     *
     *  @since      1.2.8
    */
    public static function demos_install_item() {
        self::authorize_admin_ajax_request();
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
        self::authorize_admin_ajax_request();
        $entry_id = isset($_POST['contact_entry']) ? absint($_POST['contact_entry']) : 0;
        self::require_admin_contact_entry($entry_id);
        $my_post = array(
            'ID' => $entry_id,
            'post_status' => 'super_unread',
        );
        wp_update_post( $my_post );
        die();
    }
    public static function mark_read() {
        self::authorize_admin_ajax_request();
        $entry_id = isset($_POST['contact_entry']) ? absint($_POST['contact_entry']) : 0;
        self::require_admin_contact_entry($entry_id);
        $my_post = array(
            'ID' => $entry_id,
            'post_status' => 'super_read',
        );
        wp_update_post( $my_post );
        die();
    }
    public static function delete_contact_entry() {
        self::authorize_admin_ajax_request();
        $entry_id = isset($_POST['contact_entry']) ? absint($_POST['contact_entry']) : 0;
        self::require_admin_contact_entry($entry_id);
        wp_trash_post( $entry_id );
        die();
    }

    
    /** 
     *  Save the default settings
     *
     *  @since      1.0.0
    */
    public static function save_settings() {
        self::authorize_admin_ajax_request();
        $array = array();
        foreach( $_POST['data'] as $k => $v ) {
            $array[$v['name']] = $v['value'];
        }
        if($array['smtp_enabled']=='enabled'){
            // @since 4.9.551 - WordPress changed the location of PHPMailer apperantly...
            global $wp_version;
            if ( version_compare( $wp_version, '5.5', '<' ) ) {
                require_once(ABSPATH . WPINC . "/class-phpmailer.php");
                require_once(ABSPATH . WPINC . "/class-smtp.php");
                require_once(ABSPATH . WPINC . "/class-pop3.php");
				$phpmailer = new PHPMailer();
            }else{
				require_once(ABSPATH . WPINC . "/PHPMailer/PHPMailer.php");
          		require_once(ABSPATH . WPINC . "/PHPMailer/SMTP.php");
          		require_once(ABSPATH . WPINC . "/class-pop3.php");
				$phpmailer = new \PHPMailer\PHPMailer\PHPMailer();
            }

            $phpmailer->isSMTP();
            $phpmailer->Host = $array['smtp_host'];
            $phpmailer->Port = $array['smtp_port'];
            $phpmailer->Username = $array['smtp_username'];
            $phpmailer->Password = $array['smtp_password'];
            if( $array['smtp_auth']=='enabled' ) $phpmailer->SMTPAuth = true;
            if( $array['smtp_secure']!='' ) $phpmailer->SMTPSecure = $array['smtp_secure']; 
            try {
                if($phpmailer->smtpConnect()){
                    $phpmailer->smtpClose();
                }else{
                    SUPER_Common::output_message(
                        $error='smtp_error',
                        esc_html__( 'Invalid SMTP settings!', 'super-forms' )
                    );
                    die();
                }
            } catch (Exception $e) {
                SUPER_Common::output_message(
                    $error='smtp_error',
                    $e->getMessage()
                );
                die();
            }
        }
        update_option( 'super_settings', $array );
        SUPER_Common::output_message(
            $error = false,
            $msg = ''
        );
        die();
    }


    /** 
     *  Load the default settings (Settings page)
     *
     *  @since      1.0.0
    */
    public static function load_default_settings() {
        self::authorize_admin_ajax_request();
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
        self::authorize_admin_ajax_request();

        $file_id = self::strict_positive_id_array(
            array(isset($_POST['file_id']) ? $_POST['file_id'] : null)
        );
        $file_id = $file_id[0];
        $file = self::require_admin_contact_entry_import_file($file_id, true);
        $column_connections = self::contact_entry_import_columns(
            isset($_POST['column_connections']) ? $_POST['column_connections'] : null
        );
        $skip_first = self::contact_entry_import_skip_first(
            isset($_POST['skip_first']) ? $_POST['skip_first'] : false
        );
        $delimiter = self::contact_entry_csv_character('import_delimiter', ',');
        $enclosure = self::contact_entry_csv_character('import_enclosure', '"');

        $entries = array();
        $row = 0;
        $handle = fopen($file, 'r');
        if( $handle===false ) {
            self::reject_admin_ajax_request();
        }
        $bom = "\xef\xbb\xbf";
        if( fgets($handle, 4)!==$bom ) {
            rewind($handle);
        }
        while( ($data = fgetcsv($handle, 0, $delimiter, $enclosure))!==false ) {
            if( $skip_first && $row===0 ) {
                $row++;
                continue;
            }
            $row++;
            if( count($data)>count($column_connections) ) {
                fclose($handle);
                self::reject_admin_ajax_request();
            }
            $entries[$row] = array('data' => array());
            foreach( $data as $column_index => $value ) {
                if( !isset($column_connections[$column_index]) ) {
                    fclose($handle);
                    self::reject_admin_ajax_request();
                }
                $connection = $column_connections[$column_index];
                $column_type = $connection['column'];
                $column_name = $connection['name'];
                $column_label = $connection['label'];
                if( $column_type==='form_id' ) {
                    $form_id = self::strict_positive_id_array(array($value));
                    $form_id = $form_id[0];
                    self::require_admin_form($form_id);
                    $entries[$row]['post_parent'] = $form_id;
                    $entries[$row]['data']['hidden_form_id'] = array(
                        'name' => 'hidden_form_id',
                        'value' => $form_id,
                        'type' => 'form_id'
                    );
                }elseif( $column_type==='file' ) {
                    $entries[$row]['data'][$column_name] = array(
                        'name' => $column_name,
                        'label' => $column_label,
                        'type' => 'files',
                        'files' => array()
                    );
                    foreach( explode(',', $value) as $file_index => $file_value ) {
                        $entries[$row]['data'][$column_name]['files'][$file_index] = array(
                            'name' => $column_name,
                            'label' => $column_label,
                            'value' => trim($file_value)
                        );
                    }
                }elseif( in_array($column_type, array('post_author', 'post_title', 'post_date', 'ip_address'), true) ) {
                    $entries[$row][$column_type] = sanitize_text_field($value);
                }else{
                    $entries[$row]['data'][$column_name] = array(
                        'name' => $column_name,
                        'label' => $column_label,
                        'value' => $value,
                        'type' => $column_type
                    );
                }
            }
        }
        fclose($handle);

        $global_settings = SUPER_Common::get_global_settings();
        $imported = 0;
        foreach( $entries as $entry ) {
            $post = array(
                'post_status' => 'super_unread',
                'post_type' => 'super_contact_entry',
                'post_author' => isset($entry['post_author']) ? absint($entry['post_author']) : 0
            );
            if( !empty($entry['post_date']) ) {
                $post['post_date'] = $entry['post_date'];
            }
            if( isset($entry['post_parent']) ) {
                $post['post_parent'] = $entry['post_parent'];
            }
            $contact_entry_id = wp_insert_post($post, true);
            if( is_wp_error($contact_entry_id) || !$contact_entry_id ) {
                continue;
            }
            SUPER_Data_Access::update_entry_data( $contact_entry_id, $entry['data'] );
            if( SUPER_Data_Access::get_entry_data( $contact_entry_id )!==$entry['data'] ) {
                wp_delete_post( $contact_entry_id, true );
                continue;
            }
            add_post_meta(
                $contact_entry_id,
                '_super_contact_entry_ip',
                isset($entry['ip_address']) ? $entry['ip_address'] : ''
            );
            $contact_entry_title = isset($entry['post_title']) && $entry['post_title']!==''
                ? $entry['post_title']
                : esc_html__('Contact entry', 'super-forms');
            if( isset($global_settings['contact_entry_add_id']) && $global_settings['contact_entry_add_id']==='true' ) {
                $contact_entry_title .= ' ' . $contact_entry_id;
            }
            wp_update_post(
                array(
                    'ID' => $contact_entry_id,
                    'post_title' => $contact_entry_title
                )
            );
            $imported++;
        }

        delete_post_meta(
            $file_id,
            '_super_forms_contact_entry_import_file',
            'super-forms-contact-entry-import-v1'
        );
        echo '<div class="message super-success">';
        echo sprintf(esc_html__('%d of %d contact entries imported!', 'super-forms'), $imported, count($entries));
        echo '</div>';
        die();
    }


    /** 
     *  Prepare Contact Entries Import (from CSV file)
     *
     *  @since      1.2.6
    */
    public static function prepare_contact_entry_import() {
        self::authorize_admin_ajax_request();

        $file_id = self::strict_positive_id_array(
            array(isset($_POST['file_id']) ? $_POST['file_id'] : null)
        );
        $file_id = $file_id[0];
        $file = self::require_admin_contact_entry_import_file($file_id, false);
        $delimiter = self::contact_entry_csv_character('import_delimiter', ',');
        $enclosure = self::contact_entry_csv_character('import_enclosure', '"');

        $columns = array();
        $handle = fopen($file, 'r');
        if( $handle===false ) {
            self::reject_admin_ajax_request();
        }
        $bom = "\xef\xbb\xbf";
        if( fgets($handle, 4)!==$bom ) {
            rewind($handle);
        }
        $data = fgetcsv($handle, 0, $delimiter, $enclosure);
        fclose($handle);
        if( $data===false || !is_array($data) || empty($data) ) {
            self::reject_admin_ajax_request();
        }
        foreach( $data as $value ) {
            $columns[] = array(
                'header' => $value,
                'name' => self::contact_entry_import_default_field_name($value),
            );
        }

        update_post_meta(
            $file_id,
            '_super_forms_contact_entry_import_file',
            'super-forms-contact-entry-import-v1'
        );
        echo wp_json_encode($columns);
        die();
    }


    /** 
     *  Export single Form
     *
     *  @since      4.0.0
    */
    public static function export_single_form() {
        self::authorize_admin_ajax_request();
        $form_id = absint( $_POST['form_id'] );
        if( $form_id!==0 ) {
            self::require_admin_form( $form_id );
        }
        if( $form_id==0 ) {
            $title = esc_html__( 'Form Name', 'super-forms' );
        }else{
            $title = get_the_title( $form_id );
        }
        $formSettings = $_POST['formSettings'];
        $formElements = wp_unslash($_POST['formElements']);
        $formElements = json_decode($formElements, true);
        $translationSettings = get_post_meta( $form_id, '_super_translations', true );
        $secretsSettings = get_post_meta( $form_id, '_super_local_secrets', true );
        $export = array(
            'title' => $title,
            'settings' => $formSettings,
            'elements' => $formElements,
            'translations' => $translationSettings,
            'secrets' => $secretsSettings
        );
        $export = maybe_serialize($export);
        try {
            $basename = sanitize_file_name(
                $title . '-super-forms-export-' . self::export_filename_entropy() . '.txt'
            );
            $d = wp_upload_dir();
            if( !empty($d['error']) || empty($d['path']) ) {
                throw new Exception(esc_html__('Unable to create the export file.', 'super-forms'));
            }
            $filename = trailingslashit($d['path']) . $basename;
            $fp = fopen($filename, 'x');
            if( $fp===false ) {
                throw new Exception(esc_html__('Unable to create the export file.', 'super-forms'));
            }
            $written = fwrite($fp, $export);
            fclose($fp);
            if( $written===false || $written!==strlen($export) ) {
                unlink($filename);
                throw new Exception(esc_html__('Unable to write the export file.', 'super-forms'));
            }
            $attachment = array(
                'post_mime_type' => 'text/plain',
                'post_title' => preg_replace('/\.[^.]+$/', '', $basename),
                'post_content' => '',
                'post_status' => 'private',
                'post_author' => get_current_user_id()
            );
            $attachment_id = wp_insert_attachment($attachment, $filename, 0);
            if( is_wp_error($attachment_id) || !$attachment_id ) {
                unlink($filename);
                throw new Exception(esc_html__('Unable to register the export file.', 'super-forms'));
            }
            $attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
            wp_update_attachment_metadata($attachment_id, $attach_data);
            echo self::export_attachment_url($attachment_id);
            die();
        } catch (Exception $e) {
            SUPER_Common::output_message(
                $error = true,
                $e->getMessage()
            );
        }
    }


    /**
     * Authorize administrator-only AJAX requests before reading request data.
     */
    private static function authorize_admin_ajax_request() {
        if( !current_user_can('manage_options') ) {
            wp_die('-1', '', array('response'=>403));
        }
        check_ajax_referer('super_admin_ajax', 'nonce');
    }

    private static function reject_admin_ajax_request() {
        wp_die('-1', '', array('response'=>400));
    }

    private static function strict_positive_id_array( $value, $allow_empty=false ) {
        if( !is_array($value) || (!$allow_empty && empty($value)) ) {
            self::reject_admin_ajax_request();
        }
        $ids = array();
        foreach( $value as $raw_id ) {
            if( is_int($raw_id) ) {
                $id = $raw_id;
            }elseif( is_string($raw_id) ) {
                $raw_id = wp_unslash($raw_id);
                if( !preg_match('/^[1-9][0-9]*$/D', $raw_id) ) {
                    self::reject_admin_ajax_request();
                }
                $id = (int) $raw_id;
                if( (string) $id!==$raw_id ) {
                    self::reject_admin_ajax_request();
                }
            }else{
                self::reject_admin_ajax_request();
            }
            if( $id<=0 || isset($ids[$id]) ) {
                self::reject_admin_ajax_request();
            }
            $ids[$id] = $id;
        }
        return array_values($ids);
    }

    private static function strict_form_id_list( $value ) {
        if( is_array($value) ) {
            return self::strict_positive_id_array($value, true);
        }
        if( !is_scalar($value) ) {
            self::reject_admin_ajax_request();
        }
        $value = trim(wp_unslash((string) $value));
        if( $value==='' ) {
            return array();
        }
        $ids = array();
        foreach( explode(',', $value) as $id ) {
            $id = trim($id);
            // Tolerate empty tokens produced by leading, trailing, or repeated
            // commas — the base parser skipped these silently. Non-empty tokens
            // still fail closed through strict_positive_id_array() below.
            if( $id==='' ) {
                continue;
            }
            $ids[] = $id;
        }
        if( empty($ids) ) {
            return array();
        }
        return self::strict_positive_id_array($ids);
    }

    private static function admin_contact_entry_form_scope() {
        if( isset($_POST['form_id']) && isset($_POST['form_ids']) ) {
            self::reject_admin_ajax_request();
        }
        if( isset($_POST['form_id']) ) {
            $form_ids = self::strict_positive_id_array(array($_POST['form_id']));
        }elseif( isset($_POST['form_ids']) ) {
            $form_ids = self::strict_form_id_list($_POST['form_ids']);
        }else{
            return array();
        }
        foreach( $form_ids as $form_id ) {
            self::require_admin_form($form_id);
        }
        return $form_ids;
    }

    private static function require_admin_contact_entries( $entry_ids, $form_ids=array() ) {
        $allowed_statuses = array('publish', 'super_unread', 'super_read');
        foreach( $entry_ids as $entry_id ) {
            $entry = get_post($entry_id);
            if( !$entry
                || $entry->post_type!=='super_contact_entry'
                || !in_array($entry->post_status, $allowed_statuses, true)
                || (!empty($form_ids) && !in_array((int) $entry->post_parent, $form_ids, true)) ) {
                self::reject_admin_ajax_request();
            }
        }
    }

    private static function contact_entry_order_clause() {
        $sort_columns = array(
            'entry_date' => 'entry.post_date',
            'entry_id' => 'entry.ID',
            'entry_title' => 'entry.post_title',
            'entry_author' => 'entry.post_author',
            'entry_status' => 'entry.post_status'
        );
        $sort_by = isset($_POST['sort_by']) ? $_POST['sort_by'] : 'entry_date';
        $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : 'ASC';
        if( !is_string($sort_by) || !is_string($order_by) ) {
            self::reject_admin_ajax_request();
        }
        $sort_by = wp_unslash($sort_by);
        $order_by = wp_unslash($order_by);
        if( !isset($sort_columns[$sort_by]) || !in_array($order_by, array('ASC', 'DESC'), true) ) {
            self::reject_admin_ajax_request();
        }
        return $sort_columns[$sort_by] . ' ' . $order_by;
    }

    private static function contact_entry_csv_character( $name, $default ) {
        if( !isset($_POST[$name]) || $_POST[$name]==='' ) {
            return $default;
        }
        if( !is_string($_POST[$name]) ) {
            self::reject_admin_ajax_request();
        }
        $value = wp_unslash($_POST[$name]);
        if( strlen($value)!==1 ) {
            self::reject_admin_ajax_request();
        }
        return $value;
    }

    private static function contact_entry_export_date( $name ) {
        if( !isset($_POST[$name]) || $_POST[$name]==='' ) {
            return '';
        }
        if( !is_string($_POST[$name]) ) {
            self::reject_admin_ajax_request();
        }
        $value = sanitize_text_field(wp_unslash($_POST[$name]));
        $timestamp = strtotime($value);
        if( $timestamp===false ) {
            self::reject_admin_ajax_request();
        }
        return date_i18n('Y-m-d', $timestamp);
    }

    private static function export_filename_entropy() {
        $entropy = SUPER_Forms::generate_secure_hex(16);
        if( !is_string($entropy) || preg_match('/^[a-f0-9]{32}$/D', $entropy)!==1 ) {
            throw new Exception(esc_html__('Unable to create a secure export filename.', 'super-forms'));
        }
        return $entropy;
    }

    private static function require_admin_contact_entry_import_file( $file_id, $require_marker ) {
        $attachment = get_post($file_id);
        if( !$attachment
            || $attachment->post_type!=='attachment'
            || !in_array($attachment->post_status, array('inherit', 'private'), true)
            || get_post_mime_type($file_id)!=='text/csv'
            || !current_user_can('edit_post', $file_id)
            || !current_user_can('delete_post', $file_id) ) {
            self::reject_admin_ajax_request();
        }
        if( $require_marker
            && get_post_meta($file_id, '_super_forms_contact_entry_import_file', true)!=='super-forms-contact-entry-import-v1' ) {
            self::reject_admin_ajax_request();
        }
        $file = get_attached_file($file_id);
        $uploads = wp_upload_dir();
        $upload_root = !empty($uploads['basedir']) ? realpath($uploads['basedir']) : false;
        $real_file = is_string($file) && $file!=='' ? realpath($file) : false;
        if( $upload_root===false
            || $real_file===false
            || !is_file($real_file)
            || is_link($file)
            || strtolower(pathinfo($real_file, PATHINFO_EXTENSION))!=='csv' ) {
            self::reject_admin_ajax_request();
        }
        $upload_root = trailingslashit(wp_normalize_path($upload_root));
        $real_file = wp_normalize_path($real_file);
        if( strpos($real_file, $upload_root)!==0 ) {
            self::reject_admin_ajax_request();
        }
        return $real_file;
    }

    private static function contact_entry_import_default_field_name( $value ) {
        if( !is_string($value) ) {
            return '';
        }
        $value = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value);
        return trim((string) $value, '_-');
    }

    private static function contact_entry_import_field_name( $value ) {
        if( !is_string($value) ) {
            return '';
        }
        $value = wp_unslash($value);
        if( $value==='' || preg_match('/^[A-Za-z0-9_-]+$/D', $value)!==1 ) {
            return '';
        }
        return $value;
    }
    private static function contact_entry_import_column_writes_entry_data( $column ) {
        return !in_array($column, array('post_author', 'post_title', 'post_date', 'ip_address'), true);
    }


    private static function contact_entry_import_columns( $value ) {
        if( !is_array($value) || empty($value) ) {
            self::reject_admin_ajax_request();
        }
        $allowed_types = array('var', 'text', 'post_author', 'post_title', 'post_date', 'ip_address', 'form_id', 'file');
        $connections = array();
        $entry_data_names = array();
        foreach( $value as $connection ) {
            if( !is_array($connection)
                || !isset($connection['column'])
                || !is_string($connection['column'])
                || !in_array($connection['column'], $allowed_types, true) ) {
                self::reject_admin_ajax_request();
            }
            $column = $connection['column'];
            $name = $column==='form_id'
                ? 'hidden_form_id'
                : self::contact_entry_import_field_name(
                    isset($connection['name']) ? $connection['name'] : ''
                );
            if( $name==='' ) {
                self::reject_admin_ajax_request();
            }
            if( self::contact_entry_import_column_writes_entry_data($column) ) {
                if( isset($entry_data_names[$name]) ) {
                    self::reject_admin_ajax_request();
                }
                $entry_data_names[$name] = true;
            }
            $label = isset($connection['label']) && is_string($connection['label'])
                ? sanitize_text_field(wp_unslash($connection['label']))
                : '';
            $connections[] = array(
                'column' => $column,
                'name' => $name,
                'label' => $label
            );
        }
        return $connections;
    }

    private static function contact_entry_import_skip_first( $value ) {
        if( $value===true || $value==='true' || $value==='1' ) {
            return true;
        }
        if( $value===false || $value==='false' || $value==='0' || $value==='' ) {
            return false;
        }
        self::reject_admin_ajax_request();
    }

    /**
     * Require a root form rather than an arbitrary post or backup.
     */
    private static function require_admin_form( $form_id ) {
        $form = get_post( $form_id );
        if( !$form || $form->post_type!=='super_form' || $form->post_status==='backup' || (int) $form->post_parent!==0 ) {
            wp_die('-1', '', array('response'=>400));
        }
    }

    /**
     * Require an actual contact entry rather than an arbitrary post.
     */
    private static function require_admin_contact_entry( $entry_id ) {
        $entry = get_post( $entry_id );
        if( !$entry || $entry->post_type!=='super_contact_entry' ) {
            wp_die('-1', '', array('response'=>400));
        }
    }

    /**
     * Require an actual backup belonging to the supplied form.
     */
    private static function require_admin_form_backup( $backup_id, $form_id ) {
        $backup = get_post( $backup_id );
        if( !$backup || $backup->post_type!=='super_form' || $backup->post_status!=='backup' || (int) $backup->post_parent!==(int) $form_id ) {
            wp_die('-1', '', array('response'=>400));
        }
    }


    /**
     * Authorize every form-authoring request before reading request data or mutating state.
     */
    private static function authorize_form_authoring_request() {
        if( !current_user_can('manage_options') ) {
            wp_die('-1', '', array('response'=>403));
        }
        check_ajax_referer('super_save_form', 'nonce');
    }

    /** 
     *  Import single Form
     *
     *  @since      4.0.0
    */
    public static function import_single_form() {
        self::authorize_form_authoring_request();
        $action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
        if( $action!=='super_import_single_form' ) {
            wp_die('-1', '', array('response'=>400));
        }
        $form_id = absint( $_POST['form_id'] );
        if( $form_id!==0 ) {
            self::require_admin_form( $form_id );
        }
        $file_id = absint( $_POST['file_id'] );
        // What do we need to import?
        $import_elements = $_POST['elements']; // Form elements
        $import_settings = $_POST['settings']; // Form settings
        $import_translations = $_POST['translations']; // Translation settings
        $import_secrets = $_POST['secrets']; // Translation settings
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
            $_POST['title'] = (isset($contents['title']) ? $contents['title'] : $contents['post_title']);
            $formElements = array();
            $formSettings = array();
            $translationSettings = array();
            $secretsSettings = array();
            if($import_elements=='true' && isset($contents['elements'])) $_POST['formElements'] = $contents['elements'];
            if($import_settings=='true' && isset($contents['settings'])) $_POST['formSettings'] = $contents['settings'];
            if($import_translations=='true' && isset($contents['translations'])) $_POST['translationSettings']= $contents['translations'];
            if($import_secrets=='true' && isset($contents['secrets'])) $_POST['localSecrets'] = $contents['secrets'];
            $form_id = self::save_form();
            echo $form_id;
        }else{
            SUPER_Common::output_message(
                $error = true,
                $msg = sprintf( esc_html__( 'Import file #%d could not be located', 'super-forms' ), $file_id )
            );
        }
        wp_die();
    }


    /** 
     *  Reset form settings
     *
     *  @since      4.0.0
    */
    public static function reset_form_settings() {
        self::authorize_admin_ajax_request();
        $form_id = absint( $_POST['form_id'] );
        self::require_admin_form( $form_id );
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
        self::authorize_admin_ajax_request();
        try {
            ini_set('max_execution_time', 0);
            global $wpdb;
            $offset = absint($_POST['offset']);
            $limit = absint($_POST['limit']);
            $table = $wpdb->prefix . 'posts';
            $table_meta = $wpdb->prefix . 'postmeta';
            if($_POST['found']===''){
                // Return total forms
                $found = absint($wpdb->get_var("
                SELECT COUNT(form.ID) 
                FROM $table AS form 
                WHERE form.post_status IN ('publish') AND form.post_type = 'super_form'"));
            }else{
                $found = absint($_POST['found']);
            }
            $forms = $wpdb->get_results("
            SELECT form.ID, form.post_author, form.post_date, form.post_date_gmt, form.post_title, form.post_status
            FROM $table AS form WHERE form.post_status IN ('publish') AND form.post_type = 'super_form' 
            LIMIT $limit OFFSET $offset", ARRAY_A);
            $d = wp_upload_dir();
            if( !empty($d['error']) || empty($d['path']) ) {
                throw new Exception(esc_html__('Unable to create the export file.', 'super-forms'));
            }
            $basename = 'super-forms-export-' . self::export_filename_entropy() . '.txt';
            $filename = trailingslashit($d['path']) . $basename;
            $fp = fopen($filename, 'x');
            if( $fp===false ) {
                throw new Exception(esc_html__('Unable to create the export file.', 'super-forms'));
            }
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
                $secretsSettings = get_post_meta( $form_id, '_super_local_secrets', true );
                $forms[$k]['secrets'] = $secretsSettings;
            }
            $content = json_encode($forms);
            if( $content===false ) {
                fclose($fp);
                unlink($filename);
                throw new Exception(esc_html__('Unable to write the export file.', 'super-forms'));
            }
            $written = fwrite($fp, $content);
            fclose($fp);
            if( $written===false || $written!==strlen($content) ) {
                unlink($filename);
                throw new Exception(esc_html__('Unable to write the export file.', 'super-forms'));
            }
            $attachment_id = 0;
            $file_url = '';
            if($offset+$limit>$found){
                $attachment = array(
                    'post_mime_type' => 'text/plain',
                    'post_title' => preg_replace('/\.[^.]+$/', '', $basename),
                    'post_content' => '',
                    'post_status' => 'private',
                    'post_author' => get_current_user_id()
                );
                $attachment_id = wp_insert_attachment($attachment, $filename, 0);
                if( is_wp_error($attachment_id) || !$attachment_id ) {
                    unlink($filename);
                    throw new Exception(esc_html__('Unable to register the export file.', 'super-forms'));
                }
                $attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
                wp_update_attachment_metadata($attachment_id, $attach_data);
                $file_url = self::export_attachment_url($attachment_id);
            }else{
                unlink($filename);
            }
            echo json_encode(
                array(
                    'file_url' => $file_url,
                    'offset' => $offset+$limit,
                    'found' => $found
                )
            );
            die();
        } catch (Exception $e) {
            SUPER_Common::output_message(
                $error = true,
                $e->getMessage()
            );
        }
    }


    /** 
     *  Prepare Forms Import (from TXT file)
     *
     *  @since      1.9
    */
    public static function start_forms_import() {
        self::authorize_admin_ajax_request();
        $file_id = absint( $_POST['file_id'] );
        $url = wp_get_attachment_url( $file_id );
        $request = wp_safe_remote_get($url);
        $contents = wp_remote_retrieve_body( $request );

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
            // @since 4.7.0 - translations
            if(isset($v['secrets'])){
                add_post_meta( $form_id, '_super_local_secrets', $v['secrets'] );
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
        self::authorize_admin_ajax_request();
        global $wpdb;

        $type = isset($_POST['type']) ? $_POST['type'] : 'csv';
        if( !is_string($type) || wp_unslash($type)!=='csv' ) {
            self::reject_admin_ajax_request();
        }
        $form_ids = isset($_POST['form_ids']) ? self::strict_form_id_list($_POST['form_ids']) : array();
        foreach( $form_ids as $form_id ) {
            self::require_admin_form($form_id);
        }
        $order_by_filter = self::contact_entry_order_clause();
        $from = self::contact_entry_export_date('from');
        $till = self::contact_entry_export_date('till');
        $delimiter = self::contact_entry_csv_character('delimiter', ',');
        $enclosure = self::contact_entry_csv_character('enclosure', '"');

        $table = $wpdb->prefix . 'posts';
        $table_meta = $wpdb->prefix . 'postmeta';
        $sql = "SELECT ID, post_title, post_date, post_author, post_status, meta.meta_value AS data
            FROM $table AS entry
            INNER JOIN $table_meta AS meta ON meta.post_id = entry.ID AND meta.meta_key = '_super_contact_entry_data'
            WHERE entry.post_status IN ('publish','super_unread','super_read')
            AND entry.post_type = 'super_contact_entry'";
        $prepare_values = array();
        if( !empty($form_ids) ) {
            $form_placeholders = implode(', ', array_fill(0, count($form_ids), '%d'));
            $sql .= " AND entry.post_parent IN ($form_placeholders)";
            $prepare_values = array_merge($prepare_values, $form_ids);
        }
        if( $from!=='' && $till!=='' ) {
            $sql .= ' AND ((entry.post_date LIKE %s OR entry.post_date LIKE %s) OR (entry.post_date BETWEEN %s AND %s))';
            $prepare_values[] = $from . '%';
            $prepare_values[] = $till . '%';
            $prepare_values[] = $from;
            $prepare_values[] = $till;
        }
        $sql .= " ORDER BY $order_by_filter";
        if( !empty($prepare_values) ) {
            $sql = $wpdb->prepare($sql, $prepare_values);
        }
        $entries = $wpdb->get_results($sql);

        $rows = array();
        $columns = array('entry_id', 'entry_title', 'entry_date', 'entry_author', 'entry_status');
        $rows[0] = $columns;
        foreach( $entries as $k => $v ) {
            $data = maybe_unserialize($v->data);
            if( !is_array($data) ) {
                $data = array();
            }
            foreach( $data as $field_name => $field_data ) {
                if( !in_array($field_name, $columns, true) ) {
                    $columns[] = $field_name;
                    $rows[0][] = $field_name;
                }
            }
            $data['entry_id']['value'] = $v->ID;
            $data['entry_title']['value'] = $v->post_title;
            $data['entry_date']['value'] = $v->post_date;
            $data['entry_author']['value'] = $v->post_author;
            $data['entry_status']['value'] = $v->post_status;
            $data['entry_ip']['value'] = get_post_meta($v->ID, '_super_contact_entry_ip', true);
            $data['entry_custom_status']['value'] = get_post_meta($v->ID, '_super_contact_entry_status', true);
            $entries[$k] = $data;
        }
        $rows[0][] = 'entry_ip';
        $columns[] = 'entry_ip';

        foreach( $entries as $k => $v ) {
            foreach( $columns as $column ) {
                if( isset($v[$column]) ) {
                    if( isset($v[$column]['type']) && $v[$column]['type']==='files' ) {
                        $files = '';
                        if( isset($v[$column]['files']) && is_array($v[$column]['files']) ) {
                            foreach( $v[$column]['files'] as $file ) {
                                if( !isset($file['url']) ) {
                                    continue;
                                }
                                if( $files!=='' ) {
                                    $files .= PHP_EOL;
                                }
                                $files .= $file['url'];
                            }
                        }
                        $rows[$k+1][] = $files;
                    }else{
                        $rows[$k+1][] = isset($v[$column]['value']) ? $v[$column]['value'] : '';
                    }
                }else{
                    $rows[$k+1][] = '';
                }
            }
        }

        try {
            $d = wp_upload_dir();
            if( !empty($d['error']) || empty($d['path']) ) {
                throw new Exception(esc_html__('Unable to create the export file.', 'super-forms'));
            }
            $basename = 'super-contact-entries-' . self::export_filename_entropy() . '.csv';
            $filename = trailingslashit($d['path']) . $basename;
            $fp = fopen($filename, 'x');
            if( $fp===false ) {
                throw new Exception(esc_html__('Unable to create the export file.', 'super-forms'));
            }
            $bom = apply_filters('super_csv_bom_header_filter', chr(0xEF).chr(0xBB).chr(0xBF));
            if( fwrite($fp, $bom)===false ) {
                fclose($fp);
                unlink($filename);
                throw new Exception(esc_html__('Unable to write the export file.', 'super-forms'));
            }
            foreach( $rows as $fields ) {
                if( SUPER_Common::write_csv_row($fp, $fields, $delimiter, $enclosure)===false ) {
                    fclose($fp);
                    unlink($filename);
                    throw new Exception(esc_html__('Unable to write the export file.', 'super-forms'));
                }
            }
            fclose($fp);
            $attachment = array(
                'post_mime_type' => 'text/csv',
                'post_title' => preg_replace('/\.[^.]+$/', '', $basename),
                'post_content' => '',
                'post_status' => 'private',
                'post_author' => get_current_user_id()
            );
            $attachment_id = wp_insert_attachment($attachment, $filename, 0);
            if( is_wp_error($attachment_id) || !$attachment_id ) {
                unlink($filename);
                throw new Exception(esc_html__('Unable to register the export file.', 'super-forms'));
            }
            $attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
            wp_update_attachment_metadata($attachment_id, $attach_data);
            echo self::export_attachment_url($attachment_id);
            die();
        } catch (Exception $e) {
            SUPER_Common::output_message(
                $error = true,
                $e->getMessage()
            );
        }
    }


    /** 
     *  Import Global Settings (from settings page)
     *
     *  @since      1.0.6
    */
    public static function import_global_settings() {
        self::authorize_admin_ajax_request();
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
     *  Loads the form preview on backend (create form page)
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
    public static function save_form() {
        self::authorize_form_authoring_request();
        $action = isset($_POST['action']) ? sanitize_key(wp_unslash($_POST['action'])) : '';
        if( $action!=='super_save_form' && $action!=='super_import_single_form' ) {
            wp_die('-1', '', array('response'=>400));
        }
        $form_id = (!empty($_POST['form_id']) ? absint($_POST['form_id']) : 0);
        if( $form_id!==0 ) {
            self::require_admin_form( $form_id );
        }
        $title = (!empty($_POST['title']) ? $_POST['title'] : esc_html__( 'Form Name', 'super-forms' ));
        
        // Check if one of the keys doesn't exist, this is the case when the server was unable to process this request
        // because the form is to large to be saved by this specific server
        if((!isset($_POST['formElements']) && ($_POST['elements']==='true')) || 
           (!isset($_POST['formSettings']) && ($_POST['settings']==='true')) || 
           (!isset($_POST['translationSettings']) && ($_POST['translations']==='true')) ){
            // Failed, notify user
            SUPER_Common::output_message( $error = true, esc_html__( 'Error: server could not save the form because the request is to large. Please contact your webmaster and increase your server limits.', 'super-forms' ));
        }

        $_super_elements = wp_unslash($_POST['formElements']);
        $_super_form_settings = wp_unslash($_POST['formSettings']);
        $_super_translations = wp_unslash($_POST['translationSettings']);
        if($action==='super_save_form'){
            if( is_string($_super_elements) ) {
                if( trim($_super_elements)==='' ) {
                    $_super_elements = array();
                }else{
                    $_super_elements = json_decode($_super_elements, true);
                    if( !is_array($_super_elements) ) {
                        SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form data.', 'super-forms' ) );
                    }
                }
            }elseif( !is_array($_super_elements) ) {
                SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form data.', 'super-forms' ) );
            }
            $_super_form_settings = json_decode($_super_form_settings, true);
            $_super_translations = json_decode($_super_translations, true);
        }
        $_super_local_secrets = (!empty($_POST['localSecrets']) ? $_POST['localSecrets'] : '');
        $super_global_secrets = (!empty($_POST['globalSecrets']) ? $_POST['globalSecrets'] : '');
        $elements_for_regex_validation = $_super_elements;
        $stored_elements_for_regex_validation = ( $form_id!==0 ) ? SUPER_Common::get_form_elements($form_id) : array();
        if( $action==='super_import_single_form' && !is_array($elements_for_regex_validation) ) {
            $decoded_elements = json_decode( stripslashes((string) $elements_for_regex_validation), true );
            if( $decoded_elements===null ) {
                $decoded_elements = json_decode( (string) $elements_for_regex_validation, true );
            }
            $elements_for_regex_validation = is_array($decoded_elements) ? $decoded_elements : array();
        }
        if( !self::form_custom_regexes_are_compatible($elements_for_regex_validation)
            && !self::legacy_form_custom_regexes_are_unchanged($stored_elements_for_regex_validation, $elements_for_regex_validation) ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'Invalid custom regex configuration. The pattern must be compatible with JavaScript Unicode regular expressions.', 'super-forms' ) );
        }
        $_super_elements = wp_slash($_super_elements); // This is required to keep "Custom regex" working e.g: \\d will become \\\\d
        $_super_form_settings = wp_slash($_super_form_settings); // This is required to keep Custom CSS {content: '\x123';} working
        // We must delete/clear any translations that no longer exist
        $_super_elements = self::clear_i18n($_super_elements, $_super_translations);
        // @since 3.9.0 - don't save settings that are the same as global settings
        // Get global settings
        $global_settings = SUPER_Common::get_global_settings();
        // @since 4.7.0 - translation language switcher
        if(isset($_POST['i18n_switch'])) $_super_form_settings['i18n_switch'] = sanitize_text_field($_POST['i18n_switch']);
        if( empty( $form_id ) ) {
            $form = array(
                'post_title' => $title,
                'post_status' => 'publish',
                'post_type'  => 'super_form'
            );
            $form_id = wp_insert_post( $form ); 
            self::save_form_meta(
                array(
                    'action'=>$action,
                    'form_id'=>$form_id,
                    'settings'=>$_super_form_settings,
                    'elements'=>$_super_elements, 
                    'translations'=>$_super_translations, 
                    'local_secrets'=>$_super_local_secrets, 
                    'global_secrets'=>$super_global_secrets,
                    'new'=>true,
                    'backup'=>false
                )
            );
        }else{
            $form = array(
                'ID' => $form_id,
                'post_title' => $title
            );
            wp_update_post( $form );
            if(!empty($_POST['i18n'])){
                // Merge with existing form settings
                $settings = SUPER_Common::get_form_settings($form_id);
                // Add language to the form settings
                $settings['i18n'][$_POST['i18n']] = $_super_form_settings;
                $_super_form_settings = $settings;
            }else{
                $settings = SUPER_Common::get_form_settings($form_id);
                if(!empty($settings['i18n'])){
                    $_super_form_settings['i18n'] = $settings['i18n'];
                }
            }
            self::save_form_meta(
                array(
                    'action'=>$action,
                    'form_id'=>$form_id,
                    'settings'=>$_super_form_settings,
                    'elements'=>$_super_elements, 
                    'translations'=>$_super_translations, 
                    'local_secrets'=>$_super_local_secrets, 
                    'global_secrets'=>$super_global_secrets,
                    'new'=>false,
                    'backup'=>false
                )
            );
        }
        if($action==='super_save_form'){
            // Only update global secrets if we are not importing a form
            update_option( 'super_global_secrets', $super_global_secrets );
            echo $form_id;
            wp_die();
        }
        // Importing single form, we must return the form ID
        if($action==='super_import_single_form'){
            return $form_id;
        }
    }
    public static function save_form_meta($atts) {
        extract($atts);
        if($new===true){
            add_post_meta( $form_id, '_super_version', SUPER_VERSION );
            add_post_meta( $form_id, '_super_form_settings', $settings );
            add_post_meta( $form_id, '_super_elements', $elements );
            add_post_meta( $form_id, '_super_translations', $translations );
            add_post_meta( $form_id, '_super_local_secrets', $local_secrets );
        }else{
            update_post_meta( $form_id, '_super_version', SUPER_VERSION );
            if($action==='super_save_form'){
                update_post_meta( $form_id, '_super_form_settings', $settings );
                update_post_meta( $form_id, '_super_elements', $elements );
                update_post_meta( $form_id, '_super_translations', $translations );
                update_post_meta( $form_id, '_super_local_secrets', $local_secrets );
            }
            if($action==='super_import_single_form'){
                if(!empty($settings)) update_post_meta( $form_id, '_super_form_settings', $settings );
                if(!empty($elements)) update_post_meta( $form_id, '_super_elements', $elements );
                if(!empty($translations)) update_post_meta( $form_id, '_super_translations', $translations );
                if(!empty($local_secrets)) update_post_meta( $form_id, '_super_local_secrets', $local_secrets );
            }
            // @since 3.1.0 - save history (store a total of 50 backups into db)
            if($backup===false){
                $title = get_the_title($form_id);
                $form = array(
                    'post_parent' => $form_id,
                    'post_title' => $title,
                    'post_status' => 'backup',
                    'post_type'  => 'super_form'
                );
                $backup_id = wp_insert_post( $form ); 
                self::save_form_meta(
                    array(
                        'action'=>$action,
                        'form_id'=>$backup_id,
                        'settings'=>$settings,
                        'elements'=>$elements, 
                        'translations'=>$translations, 
                        'local_secrets'=>$local_secrets, 
                        'global_secrets'=>$global_secrets,
                        'new'=>false,
                        'backup'=>true
                    )
                );
            }
        }
    }

    /** 
     *  Deletes the form with all it's settings
     *
     *  @since      1.0.0
    */
    public static function delete_form() {
        self::authorize_admin_ajax_request();
        $form_id = absint( $_POST['form_id'] );
        self::require_admin_form( $form_id );

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
            $fv['v'] = $default; // if doesn't exists, fallback to default value
            if(isset($data[$fk])){
                $fv['v'] = $data[$fk];
            }
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
            $result .= '<div class="super-field' . (isset($fv['type']) ? ' super-field-type-'.$fv['type'] : '') . $filter . $hidden . '"' . $parent . '' . $filtervalue . '>';
                if( isset( $fv['name'] ) ) {
                    $result .= '<div class="super-field-name">' . ($fv['name']);
                    if( isset( $fv['desc'] ) ) {
                        $result .= '<i class="info super-tooltip" title="' . esc_attr($fv['desc']) . '"></i>';
                    }
                }
                if( isset( $fv['label'] ) ) {
                    $result .= '<div class="super-field-label">' . nl2br($fv['label']);
                    if( !isset( $fv['name'] ) && isset( $fv['desc'] ) ) {
                        $result .= '<i class="info super-tooltip" title="' . esc_attr($fv['desc']) . '"></i>';
                    }
                }
                if( isset( $fv['label'] ) ) $result .= '</div>';
                if( isset( $fv['name'] ) ) $result .= '</div>';

                $result .= '<div class="super-field-input"';
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
        if($tag==null) $tag = $_POST['tag'];
        if($group==null) $group = $_POST['group'];
        if($data==null) $data = $_POST['data'];

        $settings = SUPER_Common::get_form_settings($_POST['form_id']);
        $shortcodes = SUPER_Shortcodes::shortcodes( false, false, false );
        $array = SUPER_Shortcodes::shortcodes( false, $data, false );
        $tabs = $array[$group]['shortcodes'][$tag]['atts'];
        $result = '';
        if($tag==='html'){
            if(!isset($data['exclude']))        $data['exclude'] = '2';
            if(!isset($data['exclude_entry']))  $data['exclude_entry'] = 'true';
        }
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
                            if(!isset($data[$fk]) || empty($fv['i18n'])) continue;

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
                                if( isset( $fv['name'] ) ) {
                                    $result .= '<div class="super-field-name">' . ($fv['name']);
                                    if( isset( $fv['desc'] ) ) {
                                        $result .= '<i class="info super-tooltip" title="' . esc_attr($fv['desc']) . '"></i>';
                                    }
                                }
                                if( isset( $fv['label'] ) ) {
                                    $result .= '<div class="super-field-label">' . nl2br($fv['label']);
                                    if( !isset( $fv['name'] ) && isset( $fv['desc'] ) ) {
                                        $result .= '<i class="info super-tooltip" title="' . esc_attr($fv['desc']) . '"></i>';
                                    }
                                }
                                if( isset( $fv['label'] ) ) $result .= '</div>';
                                if( isset( $fv['name'] ) ) $result .= '</div>';

                                $result .= '<div class="super-field-input"';
                                if( !empty($fv['allow_empty']) ) {
                                    $result .= ' data-allow-empty="true"';
                                }
                                if( ($default!=='') && (!is_array($default)) ) {
                                    $result .= ' data-default="' . $default . '"';
                                }
                                $result .= '>';
                                    if( !isset( $fv['type'] ) ) $fv['type'] = 'text';
                                    if( method_exists( 'SUPER_Field_Types', $fv['type'] ) ) {
                                        $fv['v'] = $default; // if doesn't exists, fallback to default value
                                        if(isset($data['i18n']) && isset($data['i18n'][$_POST['i18n']])){
                                            if( isset($data['i18n'][$_POST['i18n']][$fk]) ) {
                                                $fv['v'] = $data['i18n'][$_POST['i18n']][$fk];
                                            }else{
                                                if( isset($data[$fk]) ) {
                                                    $fv['v'] = $data[$fk];
                                                }
                                            }
                                        }else{
                                            if( isset($data[$fk]) ) {
                                                $fv['v'] = $data[$fk];
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
                $result .= SUPER_Shortcodes::output_builder_html( array( 'tag'=>$v['tag'], 'group'=>$v['group'], 'data'=>$v['data'], 'inner'=>$v['inner'], 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'predefined'=>true));
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
                $result = SUPER_Shortcodes::output_builder_html( array('tag'=>$tag, 'group'=>$group, 'data'=>$data, 'inner'=>$inner, 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'builder'=>$builder) );
            }else{
                if($from==0){
                    // Output element HTML only
                    $result = SUPER_Shortcodes::output_element_html( array('grid'=>null, 'tag'=>$tag, 'group'=>$group, 'data'=>$data, 'inner'=>$inner, 'shortcodes'=>$shortcodes, 'settings'=>$settings, 'i18n'=>$i18n, 'builder'=>false) );
                }else{
                    // Output builder HTML (element and with action buttons)
                    $result = SUPER_Shortcodes::output_builder_html( array('tag'=>$tag, 'group'=>$group, 'data'=>$data, 'inner'=>$inner, 'shortcodes'=>$shortcodes, 'settings'=>$settings) );
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
     * Return the field name used by common.js as this repeater's
     * `_super_dynamic_data` group key: the first rendered payload carrier.
     * Built-in structural tags and nameless custom leaves with no declared
     * carrier are skipped because the client never uses them for the key.
     */
    private static function first_repeater_group_name( $elements ) {
        if( !is_array( $elements ) ) return '';
        static $payload_tags = array(
            'quantity'=>true, 'toggle'=>true, 'color'=>true, 'slider'=>true,
            'currency'=>true, 'text'=>true, 'textarea'=>true, 'dropdown'=>true, 'checkbox'=>true,
            'radio'=>true, 'file'=>true, 'date'=>true, 'time'=>true, 'rating'=>true,
            'countries'=>true, 'password'=>true, 'hidden'=>true, 'html'=>true,
            'tinymce'=>true, 'calculator'=>true, 'signature'=>true,
        );
        static $non_payload_tags = array(
            'button'=>true, 'heading'=>true, 'divider'=>true,
            'spacer'=>true, 'image'=>true, 'recaptcha'=>true,
        );
        foreach( $elements as $element ) {
            if( !empty( $element['inner'] ) ) {
                $name = self::first_repeater_group_name( $element['inner'] );
                if( $name === null || $name !== '' ) return $name;
                continue;
            }
            $edata = ( isset( $element['data'] ) && is_array( $element['data'] ) ) ? $element['data'] : array();
            $tag = isset( $element['tag'] ) ? $element['tag'] : '';
            if( isset( $edata['name'] ) && is_string( $edata['name'] ) && $edata['name'] !== '' ) {
                if( isset( $payload_tags[ $tag ] ) ) {
                    if( $tag === 'html' || $tag === 'tinymce' ) {
                        $html = ( isset( $edata['html'] ) && is_string( $edata['html'] ) ) ? $edata['html'] : '';
                        $translations = ( isset( $edata['i18n'] ) && is_array( $edata['i18n'] ) ) ? $edata['i18n'] : array();
                        if( $html === '' ) {
                            foreach( $translations as $translation ) {
                                if( is_array( $translation )
                                    && isset( $translation['html'] )
                                    && is_string( $translation['html'] )
                                    && $translation['html'] !== '' ) {
                                    return null;
                                }
                            }
                            continue;
                        }
                        foreach( $translations as $translation ) {
                            if( is_array( $translation )
                                && array_key_exists( 'html', $translation )
                                && ( !is_string( $translation['html'] ) || $translation['html'] === '' ) ) {
                                return null;
                            }
                        }
                    }
                    return $edata['name'];
                }
            }else{
                if( isset( $payload_tags[ $tag ] ) ) {
                    continue;
                }
                continue;
            }
            if( isset( $non_payload_tags[ $tag ] ) ) continue;
            return null;
        }
        return '';
    }

    /**
     * @since 6.3.315 - Recursively collect required fields from stored form elements and
     * classify each for safe server-side enforcement (CVE-2026-14894 follow-up).
     *
     * Only element tags that expose the `may_be_empty` setting and have an explicit
     * client-side validation rule are enrolled. This mirrors the front-end exactly:
     * `SUPER_Shortcodes::common_attributes()` renders `data-validation` only for a
     * non-empty rule other than `none`, and common.js validates only those fields.
     * Data-carrier / structural elements (hidden, toggle_field, rating, recaptcha,
     * button, html, option-item children, file) are never enrolled. Because a
     * genuinely-required validated input stores `may_be_empty` absent (the `false`
     * default is stripped on save), absence is treated as required. Fields without
     * a validation rule and fields with may_be_empty `true` or `conditions` are skipped.
     *
     * Each enrolled field carries enforcement metadata:
     *   - always_present: the field sits under NO conditional / mobile-hidden / repeater
     *     ancestor and is not itself conditional/mobile-hidden, so the front-end
     *     ALWAYS sends it; a payload missing it was tampered with (presence enforcement).
     *   - repeater_enforceable: the field is inside a repeater whose ENTIRE enclosing chain has
     *     deterministic visibility, so positional row data can be validated safely.
     *   - repeater_group: the stored-tree payload key common.js assigns to that repeater.
     * On a duplicate field name, always_present is AND-combined and row enforcement is disabled
     * because the payload cannot identify which same-named occurrence supplied the value.
     *
     * @param array $elements  Stored `_super_elements` (or an `inner` subtree).
     * @param array $ctx       Recursion context; null seeds the top-level default.
     */
    private static function collect_required_fields( $elements, $ctx = null ) {
        if( $ctx === null ) $ctx = array( 'ancestor_locked' => false, 'in_repeater' => false, 'repeater_safe' => true, 'repeater_group' => '' );
        $required = array();
        if( !is_array( $elements ) ) return $required;
        // The 14 stored input tags routed through SUPER_Shortcodes::common_attributes (data-validation + may_be_empty).
        $validated_tags = array(
            'text'=>true, 'textarea'=>true, 'dropdown'=>true, 'checkbox'=>true, 'radio'=>true,
            'quantity'=>true, 'toggle'=>true, 'color'=>true, 'slider'=>true, 'currency'=>true,
            'date'=>true, 'time'=>true, 'countries'=>true, 'password'=>true,
        );
        foreach( $elements as $element ) {
            $edata = ( isset( $element['data'] ) && is_array( $element['data'] ) ) ? $element['data'] : array();
            $tag = isset( $element['tag'] ) ? $element['tag'] : '';
            // An element (or ancestor) that can hide/repeat its subtree "locks" the fields below
            // it: the front-end may legitimately omit them, so they can never be presence-enforced.
            // Conditional logic is not evaluable server-side; repeaters vary per
            // submission; mobile-hidden columns drop on small viewports. A multipart step
            // is always part of a final browser submit, so it does NOT lock its fields.
            $ca = isset( $edata['conditional_action'] ) ? $edata['conditional_action'] : '';
            $conditional = ( $ca !== '' && $ca !== 'disabled' );
            $repeater = ( isset( $edata['duplicate'] ) && $edata['duplicate'] === 'enabled' );
            $mobile_hide = ( ( isset( $edata['hide_on_mobile'] ) && $edata['hide_on_mobile'] === 'true' )
                || ( isset( $edata['hide_on_mobile_window'] ) && $edata['hide_on_mobile_window'] === 'true' ) );
            $child_locked = ( $ctx['ancestor_locked'] || $conditional || $repeater || $mobile_hide );
            if( !empty( $element['inner'] ) ) {
                if( $repeater ) {
                    // A repeater is row-enforceable only when its payload group is known and
                    // NOTHING in its subtree has conditional or mobile-dependent visibility.
                    $repeater_group = self::first_repeater_group_name( $element['inner'] );
                    $this_safe = ( is_string( $repeater_group ) && $repeater_group !== '' && !self::subtree_has_dynamic_visibility( $element['inner'] ) );
                    $child_ctx = array(
                        'ancestor_locked' => $child_locked,
                        'in_repeater' => true,
                        'repeater_safe' => ( $ctx['repeater_safe'] && $this_safe ),
                        'repeater_group' => $repeater_group,
                    );
                } else {
                    $child_ctx = array(
                        'ancestor_locked' => $child_locked,
                        'in_repeater' => $ctx['in_repeater'],
                        'repeater_safe' => $ctx['repeater_safe'],
                        'repeater_group' => $ctx['repeater_group'],
                    );
                }
                foreach( self::collect_required_fields( $element['inner'], $child_ctx ) as $sub_name => $sub_meta ) {
                    $required = self::merge_required_meta( $required, $sub_name, $sub_meta );
                }
            } elseif( !empty( $edata['name'] ) ) {
                if( empty( $validated_tags[ $tag ] ) ) continue; // not a front-end-validated input tag
                $validation = isset( $edata['validation'] ) ? $edata['validation'] : '';
                if( !is_string( $validation ) || $validation === '' || $validation === 'none' ) continue; // no data-validation attribute on the rendered field
                $may_be_empty = isset( $edata['may_be_empty'] ) ? $edata['may_be_empty'] : 'false';
                if( $may_be_empty === 'false' ) {
                    $required = self::merge_required_meta( $required, $edata['name'], array(
                        'always_present' => !$child_locked,
                        'repeater_enforceable' => ( $ctx['in_repeater'] && $ctx['repeater_safe'] && $ctx['repeater_group'] !== '' ),
                        'repeater_group' => $ctx['repeater_group'],
                    ) );
                }
            }
        }
        return $required;
    }

    /**
     * Reconstruct the client carrier namespace solely from the stored element tree.
     * A non-file carrier can never nominate a field, type, or validation rule.
     *
     * $authoritative marks the rendered field-collector as the source of truth for a
     * rendered payload tag. When a later non-authoritative declaration (an extension
     * carrier) conflicts with an authoritative rendered entry, the rendered entry is
     * kept instead of poisoning the name, so a real customer field stays submittable.
     * Poisoning is reserved for genuinely irreconcilable duplicates (two rendered
     * fields, or conflicting declarations for a name that was never rendered).
     */
    private static function register_submission_contract_entry( &$contract, $name, $meta, $authoritative = false ) {
        if( !is_array($contract) || !is_string($name) || $name==='' || !is_array($meta) ) {
            return false;
        }
        if( !isset($meta['type']) || !is_string($meta['type']) || $meta['type']==='' ) {
            return false;
        }
        $normalized = array(
            'type' => $meta['type'],
            'validation' => ( isset($meta['validation']) && is_string($meta['validation']) ) ? $meta['validation'] : '',
            'custom_regex' => ( isset($meta['custom_regex']) && is_string($meta['custom_regex']) ) ? $meta['custom_regex'] : '',
            'minlength' => isset($meta['minlength']) ? $meta['minlength'] : '',
            'maxlength' => isset($meta['maxlength']) ? $meta['maxlength'] : '',
            'selection_limit' => !empty($meta['selection_limit']),
            'selection_joiner' => ( isset($meta['selection_joiner']) && is_string($meta['selection_joiner']) ) ? $meta['selection_joiner'] : ', ',
            'length_mode' => ( isset($meta['length_mode']) && is_string($meta['length_mode']) ) ? $meta['length_mode'] : 'text',
            'keyword_split_method' => ( isset($meta['keyword_split_method']) && is_string($meta['keyword_split_method']) ) ? $meta['keyword_split_method'] : '',
            'nested_repeater_suffix_depth' => isset($meta['nested_repeater_suffix_depth']) ? max(0, absint($meta['nested_repeater_suffix_depth'])) : 0,
            'repeatable' => !empty($meta['repeatable']),
            'allow_saved_choice_fallback' => !empty($meta['allow_saved_choice_fallback']),
            'enforce_choice_values' => !empty($meta['enforce_choice_values']),
            'allow_context_free_selected_values' => !empty($meta['allow_context_free_selected_values']),
            'choice_values' => array(),
            'allows_geometry' => !empty($meta['allows_geometry']),
            'allows_timestamp' => !empty($meta['allows_timestamp']),
            'allows_signature_lines' => !empty($meta['allows_signature_lines']),
            'allows_hidden_code' => !empty($meta['allows_hidden_code']),
            'allows_selected_values' => !empty($meta['allows_selected_values']),
        );
        if( array_key_exists('choice_values', $meta) ) {
            if( $meta['choice_values']===false ) {
                $normalized['choice_values'] = false;
            }elseif( !is_array($meta['choice_values']) ) {
                return false;
            }else{
                $choice_values = array();
                foreach( $meta['choice_values'] as $choice_value ) {
                    // PHP silently coerces canonical-integer string array keys
                    // (e.g. "1", "1000") to integer keys, so choice values that
                    // originate from array_keys() can arrive here as integers.
                    // Normalize them back to strings before the is_string gate so
                    // genuine numeric choice values register the field instead of
                    // aborting the whole contract entry, while non-scalar/malformed
                    // choices stay rejected.
                    if( is_int($choice_value) ) {
                        $choice_value = (string) $choice_value;
                    }
                    if( !is_string($choice_value) || $choice_value==='' || isset($choice_values[$choice_value]) ) {
                        return false;
                    }
                    $choice_values[$choice_value] = $choice_value;
                }
                $normalized['choice_values'] = array_values($choice_values);
            }
        }
        if( array_key_exists('exact_value', $meta) ) {
            if( !is_scalar($meta['exact_value']) && $meta['exact_value']!==null ) {
                return false;
            }
            $normalized['exact_value'] = $meta['exact_value'];
        }
        $existing = isset($contract[$name]) ? $contract[$name] : null;
        if( $existing===false ) {
            // Already poisoned by an earlier conflict. Only a rendered field may reclaim
            // the name; a non-authoritative carrier for a never-rendered name stays rejected.
            if( $authoritative ) {
                $normalized['authoritative'] = true;
                $contract[$name] = $normalized;
                return true;
            }
            return false;
        }
        if( is_array($existing) ) {
            $existing_authoritative = !empty($existing['authoritative']);
            $compare_existing = $existing;
            unset($compare_existing['authoritative']);
            if( $compare_existing!==$normalized ) {
                if( $existing_authoritative && !$authoritative ) {
                    // Keep the authoritative rendered entry; ignore the conflicting
                    // extension redeclaration instead of poisoning a real field.
                    return true;
                }
                if( !$existing_authoritative && $authoritative ) {
                    // A rendered field supersedes a prior non-authoritative declaration.
                    $normalized['authoritative'] = true;
                    $contract[$name] = $normalized;
                    return true;
                }
                // Genuinely irreconcilable duplicate (two rendered fields, or two
                // conflicting declarations for a never-rendered name): poison the carrier.
                $contract[$name] = false;
                return false;
            }
        }
        $normalized['authoritative'] = ( $authoritative || ( is_array($existing) && !empty($existing['authoritative']) ) );
        $contract[$name] = $normalized;
        return true;
    }

    /**
     * Reconstruct the client carrier namespace solely from the stored element tree.
     * A non-file carrier can never nominate a field, type, or validation rule.
     */
    private static function collect_submission_field_contract( $elements, &$contract=array(), $repeater_depth=0, $form_id=0 ) {
        if( !is_array($elements) ) return;
        $payload_tags = array_fill_keys(array(
            'quantity', 'toggle', 'color', 'slider', 'currency', 'text', 'textarea',
            'dropdown', 'checkbox', 'radio', 'file', 'date', 'time', 'rating',
            'countries', 'password', 'hidden', 'html', 'tinymce', 'calculator',
            'signature'
        ), true);
        foreach( $elements as $element ) {
            if( !is_array($element) ) continue;
            $data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
            $tag = isset($element['tag']) ? $element['tag'] : '';
            $child_repeater_depth = $repeater_depth;
            if( $tag==='column' && isset($data['duplicate']) && $data['duplicate']==='enabled' ) {
                $child_repeater_depth++;
            }
            if( !empty($element['inner']) ) self::collect_submission_field_contract($element['inner'], $contract, $child_repeater_depth, $form_id);
            if( !empty($payload_tags[$tag]) && isset($data['name']) && is_string($data['name']) && $data['name']!=='' ) {
                $type = $tag==='file' ? 'files' : 'var';
                $length_mode = 'text';
                if( $tag==='textarea' ) $type = 'text';
                if( $tag==='html' || $tag==='tinymce' ) $type = 'html';
                if( $tag==='text' && isset($data['enable_address_auto_complete'])
                    && $data['enable_address_auto_complete']==='true' ) $type = 'google_address';
                if( in_array($tag, array('dropdown', 'checkbox', 'radio', 'countries'), true) ) {
                    $length_mode = 'selection';
                }elseif( $tag==='date' ) {
                    $length_mode = 'skip';
                }elseif( $tag==='text' && !empty($data['enable_keywords']) ) {
                    $length_mode = 'keywords';
                }
                self::register_submission_contract_entry($contract, $data['name'], array(
                    'type'=>$type,
                    'validation'=>isset($data['validation']) && is_string($data['validation']) ? $data['validation'] : '',
                    'custom_regex'=>isset($data['custom_regex']) && is_string($data['custom_regex']) ? $data['custom_regex'] : '',
                    'minlength'=>isset($data['minlength']) ? $data['minlength'] : '',
                    'maxlength'=>isset($data['maxlength']) ? $data['maxlength'] : '',
                    'selection_limit'=>( $tag==='dropdown' || $tag==='checkbox' || $tag==='countries' ),
                    'selection_joiner'=>( $tag==='checkbox' ? ',' : ', ' ),
                    'length_mode'=>$length_mode,
                    'keyword_split_method'=>( isset($data['keyword_split_method']) && is_string($data['keyword_split_method']) ? $data['keyword_split_method'] : '' ),
                    'nested_repeater_suffix_depth'=>max( 0, $repeater_depth-1 ),
                    'repeatable'=>( $repeater_depth>0 ),
                    'enforce_choice_values'=>( $tag==='dropdown' || $tag==='checkbox' || $tag==='radio' || $tag==='countries' ),
                    'allow_context_free_selected_values'=>( in_array($tag, array('dropdown', 'checkbox', 'radio'), true) && isset($data['retrieve_method']) && is_string($data['retrieve_method']) && trim($data['retrieve_method'])==='product_attribute' ),
                    'choice_values'=>self::submission_choice_values($tag, $data, $form_id),
                    'allows_geometry'=>( $type==='google_address' ),
                    'allows_timestamp'=>( $tag==='date' ),
                    'allows_signature_lines'=>( $tag==='signature' ),
                    'allows_hidden_code'=>( $tag==='hidden' ),
                ), true);
            }
        }
    }

    private static function register_login_role_contract_fields( $form_id ) {
        $fields = array();
        $form_id = absint($form_id);
        if( $form_id===0 ) {
            return $fields;
        }
        $settings = SUPER_Common::get_form_settings($form_id);
        if( !is_array($settings)
            || empty($settings['register_login_action'])
            || $settings['register_login_action']!=='register' ) {
            return $fields;
        }
        $fields['role'] = true;
        if( isset($settings['register_user_role'])
            && is_string($settings['register_user_role'])
            && preg_match('/^\{([A-Za-z0-9_-]+)\}$/D', trim($settings['register_user_role']), $matches)===1 ) {
            $fields[$matches[1]] = true;
        }
        return $fields;
    }

    private static function submission_field_allows_saved_choice_fallback( $field_name, $form_id ) {
        if( !is_string($field_name) || $field_name==='' ) {
            return false;
        }
        $lookup_name = self::submission_lookup_field_name($field_name);
        if( $lookup_name==='' ) {
            return false;
        }
        $fields = self::register_login_role_contract_fields($form_id);
        return isset($fields[$lookup_name]);
    }

    private static function apply_register_login_role_contracts( $contract, $form_id ) {
        if( !is_array($contract) ) {
            return $contract;
        }
        foreach( self::register_login_role_contract_fields($form_id) as $field_name => $enabled ) {
            if( !$enabled || !isset($contract[$field_name]) || !is_array($contract[$field_name]) ) {
                continue;
            }
            $contract[$field_name]['allow_saved_choice_fallback'] = true;
        }
        return $contract;
    }

    private static function submission_decode_html_attribute( $value ) {
        if( !is_string($value) ) {
            return '';
        }
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }

    private static function submission_rendered_choice_label( $item_html, $fallback='' ) {
        if( is_string($item_html)
            && preg_match('/\sdata-search-value=(["\'])(.*?)\1/', $item_html, $matches)===1 ) {
            return self::submission_decode_html_attribute($matches[2]);
        }
        if( !is_string($item_html) || trim($item_html)==='' ) {
            return is_scalar($fallback) ? (string) $fallback : '';
        }
        if( class_exists('DOMDocument') ) {
            $previous = libxml_use_internal_errors(true);
            $document = new DOMDocument();
            $loaded = $document->loadHTML('<?xml encoding="utf-8" ?><div>' . $item_html . '</div>');
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            if( $loaded ) {
                $wrapper = $document->getElementsByTagName('div')->item(0);
                if( $wrapper ) {
                    $text = trim(preg_replace('/\s+/u', ' ', $wrapper->textContent));
                    if( $text!=='' ) {
                        return $text;
                    }
                }
            }
        }
        return trim(wp_strip_all_tags($item_html));
    }

    private static function submission_choice_schema_add_item( &$choices, $value, $label ) {
        if( !is_array($choices) || !is_scalar($value) || $value===null ) {
            return false;
        }
        $parts = explode(';', (string) $value, 2);
        $value = $parts[0];
        if( $value==='' ) {
            return true;
        }
        $label = is_scalar($label) ? (string) $label : '';
        if( isset($choices[$value]) ) {
            return true;
        }
        $choices[$value] = $label;
        return true;
    }

    private static function submission_countries_choice_schema( $data, $form_id=0 ) {
        $countries = array();
        if ( file_exists( SUPER_PLUGIN_DIR . '/countries.txt' ) ) {
            $countries = wp_remote_fopen( SUPER_PLUGIN_FILE . 'countries.txt' );
            $countries = explode( "\n", $countries );
        }
        $field_name = ( isset($data['name']) && is_string($data['name']) ) ? $data['name'] : '';
        $countries = apply_filters(
            'super_countries_list_filter',
            $countries,
            array(
                'name' => $field_name,
                'settings' => SUPER_Common::get_form_settings($form_id),
            )
        );
        if( !is_array($countries) ) {
            return false;
        }
        $choices = array();
        foreach( $countries as $key => $country ) {
            if( !is_scalar($country) ) {
                return false;
            }
            $country = trim((string) $country);
            if( $country==='' ) {
                continue;
            }
            $value = is_string($key) ? (string) $key : $country;
            if( !self::submission_choice_schema_add_item($choices, $value, $country) ) {
                return false;
            }
        }
        return array(
            'values' => array_keys($choices),
            'labels' => $choices,
        );
    }

    private static function submission_rendered_choice_schema( $tag, $data, $form_id=0, $selected_values=array() ) {
        if( !in_array($tag, array('dropdown', 'checkbox', 'radio', 'countries'), true) || !is_array($data) ) {
            return array(
                'values' => array(),
                'labels' => array(),
            );
        }
        if( $tag==='countries' ) {
            return self::submission_countries_choice_schema( $data, $form_id );
        }
        $items_key = $tag . '_items';
        $retrieve_method = isset($data['retrieve_method']) && is_string($data['retrieve_method'])
            ? trim($data['retrieve_method'])
            : 'custom';
        if( $retrieve_method==='' ) {
            $retrieve_method = 'custom';
        }
        if( $retrieve_method==='custom' && isset($data[$items_key]) ) {
            if( !is_array($data[$items_key]) ) {
                return false;
            }
            $choices = array();
            foreach( $data[$items_key] as $item ) {
                if( !is_array($item) || !isset($item['value']) || !is_scalar($item['value']) ) {
                    return false;
                }
                $label = isset($item['label']) && is_scalar($item['label']) ? $item['label'] : '';
                if( !self::submission_choice_schema_add_item($choices, $item['value'], $label) ) {
                    return false;
                }
            }
            return array(
                'values' => array_map('strval', array_keys($choices)),
                'labels' => $choices,
            );
        }
        if( !class_exists('SUPER_Shortcodes') ) {
            require_once( SUPER_PLUGIN_DIR . '/includes/class-shortcodes.php' );
        }
        if( !class_exists('SUPER_Shortcodes') ) {
            return false;
        }
        $shortcodes = SUPER_Shortcodes::shortcodes();
        $defaults = SUPER_Common::generate_array_default_element_settings($shortcodes, 'form_elements', $tag);
        $atts = wp_parse_args($data, $defaults);
        if( !isset($atts['value']) || !is_scalar($atts['value']) ) {
            $atts['value'] = '';
        }
        if( !empty($selected_values) ) {
            $atts['value'] = implode( $tag==='checkbox' ? ',' : ', ', array_map('strval', $selected_values) );
        }
        if( !isset($atts['class']) || !is_string($atts['class']) ) {
            $atts['class'] = '';
        }
        $rendered = SUPER_Shortcodes::get_items(array(
            'items' => array(),
            'tag' => $tag,
            'atts' => $atts,
            'prefix' => '',
            'settings' => SUPER_Common::get_form_settings($form_id),
            'entry_data' => array(),
        ));
        if( !is_array($rendered)
            || !isset($rendered['items_values'], $rendered['items'])
            || !is_array($rendered['items_values'])
            || !is_array($rendered['items']) ) {
            return false;
        }
        $choices = array();
        foreach( array_values($rendered['items_values']) as $index => $choice_value ) {
            if( !is_scalar($choice_value) ) {
                return false;
            }
            $choice_label = self::submission_rendered_choice_label(
                isset($rendered['items'][$index]) ? $rendered['items'][$index] : '',
                $choice_value
            );
            if( !self::submission_choice_schema_add_item($choices, $choice_value, $choice_label) ) {
                return false;
            }
        }
        return array(
            'values' => array_map('strval', array_keys($choices)),
            'labels' => $choices,
        );
    }

    private static function submission_choice_values( $tag, $data, $form_id=0 ) {
        $schema = self::submission_rendered_choice_schema($tag, $data, $form_id);
        if( $schema===false || !isset($schema['values']) || !is_array($schema['values']) ) {
            return false;
        }
        return $schema['values'];
    }

    private static function submission_parse_selected_choices( $value, $meta ) {
        if( !is_array($meta) || empty($meta['selection_limit']) ) {
            return false;
        }
        if( array_key_exists('choice_values', $meta) && $meta['choice_values']===false ) {
            return false;
        }
        if( !array_key_exists('choice_values', $meta) || !is_array($meta['choice_values']) ) {
            return false;
        }
        $value = (string) $value;
        if( $value==='' ) {
            return array();
        }
        if( empty($meta['choice_values']) ) {
            return false;
        }
        $choices = array_values($meta['choice_values']);
        usort($choices, function( $left, $right ) {
            return strlen($right) - strlen($left);
        });
        $cache = array();
        $walk = function( $offset ) use ( $value, $choices, &$cache, &$walk ) {
            if( array_key_exists($offset, $cache) ) {
                return $cache[$offset];
            }
            if( $offset===strlen($value) ) {
                $cache[$offset] = array(
                    'matched' => true,
                    'ambiguous' => false,
                    'selected' => array(),
                );
                return $cache[$offset];
            }
            $matched = null;
            foreach( $choices as $choice ) {
                $length = strlen($choice);
                if( $length===0 || substr($value, $offset, $length)!==$choice ) {
                    continue;
                }
                $next = $offset + $length;
                $candidates = array();
                if( $next===strlen($value) ) {
                    $candidates[] = array($choice);
                }else{
                    foreach( array(', ', ',') as $separator ) {
                        $separator_length = strlen($separator);
                        if( substr($value, $next, $separator_length)!==$separator ) {
                            continue;
                        }
                        $tail = $walk($next + $separator_length);
                        if( !is_array($tail) || empty($tail['matched']) || !empty($tail['ambiguous']) ) {
                            if( !empty($tail['ambiguous']) ) {
                                $cache[$offset] = array(
                                    'matched' => false,
                                    'ambiguous' => true,
                                    'selected' => array(),
                                );
                                return $cache[$offset];
                            }
                            continue;
                        }
                        $candidate = $tail['selected'];
                        array_unshift($candidate, $choice);
                        $candidates[] = $candidate;
                    }
                }
                foreach( $candidates as $candidate ) {
                    if( $matched===null ) {
                        $matched = $candidate;
                        continue;
                    }
                    if( $matched!==$candidate ) {
                        $cache[$offset] = array(
                            'matched' => false,
                            'ambiguous' => true,
                            'selected' => array(),
                        );
                        return $cache[$offset];
                    }
                }
            }
            if( $matched===null ) {
                $cache[$offset] = array(
                    'matched' => false,
                    'ambiguous' => false,
                    'selected' => array(),
                );
                return $cache[$offset];
            }
            $cache[$offset] = array(
                'matched' => true,
                'ambiguous' => false,
                'selected' => $matched,
            );
            return $cache[$offset];
        };
        $result = $walk(0);
        if( !is_array($result) || empty($result['matched']) || !empty($result['ambiguous']) ) {
            return false;
        }
        $selected = $result['selected'];
        if( count($selected)!==count(array_unique($selected)) ) {
            return false;
        }
        return $selected;
    }

    private static function submission_choice_value_joiner( $meta ) {
        if( !is_array($meta) || !isset($meta['selection_joiner']) || !is_string($meta['selection_joiner']) ) {
            return ', ';
        }
        return $meta['selection_joiner'];
    }

    private static function submission_keyword_values( $value, $meta ) {
        $value = is_scalar($value) || $value===null ? (string) $value : '';
        if( $value==='' ) {
            return array();
        }
        $split_method = ( is_array($meta) && !empty($meta['keyword_split_method']) && is_string($meta['keyword_split_method']) )
            ? $meta['keyword_split_method']
            : 'both';
        if( $split_method==='comma' ) {
            $parts = explode(',', $value);
        }elseif( $split_method==='space' ) {
            $parts = preg_split('/\s+/u', $value);
        }else{
            $parts = preg_split('/(?:\s+|,\s*)/u', $value);
        }
        if( !is_array($parts) ) {
            return array();
        }
        $keywords = array();
        foreach( $parts as $part ) {
            if( !is_scalar($part) ) {
                continue;
            }
            $part = trim((string) $part);
            if( $part!=='' ) {
                $keywords[] = $part;
            }
        }
        return $keywords;
    }

    private static function submission_selected_values_payload( $carrier ) {
        if( !is_array($carrier) ) {
            return false;
        }
        if( !array_key_exists('selected_values', $carrier) ) {
            return null;
        }
        if( !self::submission_array_is_indexed($carrier['selected_values']) ) {
            return false;
        }
        $selected = array();
        foreach( $carrier['selected_values'] as $selected_value ) {
            if( !is_scalar($selected_value) && $selected_value!==null ) {
                return false;
            }
            $selected_value = (string) $selected_value;
            if( $selected_value==='' || isset($selected[$selected_value]) ) {
                return false;
            }
            $selected[$selected_value] = $selected_value;
        }
        return array_values($selected);
    }

    private static function submission_product_attribute_context_is_missing() {
        if( !class_exists('WooCommerce') ) {
            return false;
        }
        global $post, $product;
        return !isset($post)
            || !isset($product)
            || !is_object($product)
            || !method_exists($product, 'get_attribute');
    }

    private static function submission_context_free_selected_values( $value, $selected, $meta ) {
        if( !is_array($meta) ) {
            return false;
        }
        if( $selected===null ) {
            return ( $value==='' ) ? array() : false;
        }
        if( !empty($meta['selection_limit']) ) {
            if( empty($selected) ) {
                return ( $value==='' ) ? array() : false;
            }
            return ( $value===implode(self::submission_choice_value_joiner($meta), $selected) )
                ? $selected
                : false;
        }
        if( empty($selected) ) {
            return ( $value==='' ) ? array() : false;
        }
        return ( count($selected)===1 && $value===$selected[0] )
            ? $selected
            : false;
    }

    private static function submission_selected_values_from_carrier( $carrier, $meta ) {
        if( !is_array($carrier) || !is_array($meta) ) {
            return false;
        }
        $value = array_key_exists('value', $carrier) && (is_scalar($carrier['value']) || $carrier['value']===null)
            ? (string) $carrier['value']
            : '';
        $selected = self::submission_selected_values_payload($carrier);
        if( $selected===false ) {
            return false;
        }
        $allow_saved_choice_fallback = !empty($meta['allow_saved_choice_fallback']);
        $allow_context_free_selected_values = !empty($meta['allow_context_free_selected_values'])
            && self::submission_product_attribute_context_is_missing();
        if( $allow_context_free_selected_values
            && ( !array_key_exists('choice_values', $meta)
                || $meta['choice_values']===false
                || ( is_array($meta['choice_values']) && empty($meta['choice_values']) ) ) ) {
            return self::submission_context_free_selected_values($value, $selected, $meta);
        }
        if( array_key_exists('choice_values', $meta) && $meta['choice_values']===false ) {
            return ( $value==='' && ($selected===null || empty($selected)) ) ? array() : false;
        }
        if( !empty($meta['selection_limit']) ) {
            if( $selected===null ) {
                $parsed = self::submission_parse_selected_choices($value, $meta);
                if( $parsed!==false ) {
                    return $parsed;
                }
                if( $allow_saved_choice_fallback && $value!=='' ) {
                    return array($value);
                }
                return false;
            }
            if( empty($selected) ) {
                if( $value==='' ) {
                    return array();
                }
                if( $allow_saved_choice_fallback ) {
                    return array($value);
                }
                return false;
            }
            if( !array_key_exists('choice_values', $meta) || !is_array($meta['choice_values']) ) {
                return false;
            }
            foreach( $selected as $selected_value ) {
                if( !in_array($selected_value, $meta['choice_values'], true) ) {
                    return false;
                }
            }
            if( $value!==implode(self::submission_choice_value_joiner($meta), $selected) ) {
                return false;
            }
            return $selected;
        }
        if( array_key_exists('choice_values', $meta) && is_array($meta['choice_values']) ) {
            if( $selected===null ) {
                if( $value==='' ) {
                    return array();
                }
                if( in_array($value, $meta['choice_values'], true) ) {
                    return array($value);
                }
                return $allow_saved_choice_fallback ? array($value) : false;
            }
            if( empty($selected) ) {
                if( $value==='' ) {
                    return array();
                }
                return $allow_saved_choice_fallback ? array($value) : false;
            }
            if( count($selected)!==1 || $value!==$selected[0] ) {
                return false;
            }
            if( !in_array($selected[0], $meta['choice_values'], true) ) {
                return $allow_saved_choice_fallback ? array($value) : false;
            }
            return $selected;
        }
        return ( $selected===null ) ? null : false;
    }

    private static function submission_unicode_length( $value ) {
        if( function_exists('mb_strlen') ) {
            return mb_strlen($value, 'UTF-8');
        }
        if( function_exists('iconv_strlen') ) {
            $length = @iconv_strlen($value, 'UTF-8');
            if( $length!==false ) {
                return $length;
            }
        }
        if( preg_match_all('/./us', $value, $characters)!==false ) {
            return count($characters[0]);
        }
        return strlen($value);
    }

    private static function submission_value_length( $value, $meta, $carrier=null ) {
        if( !is_array($meta) ) {
            return -1;
        }
        $length_mode = isset($meta['length_mode']) && is_string($meta['length_mode'])
            ? $meta['length_mode']
            : 'text';
        if( $length_mode==='skip' ) {
            return 0;
        }
        if( $length_mode==='selection'
            || !empty($meta['selection_limit'])
            || !empty($meta['enforce_choice_values']) ) {
            $selection_carrier = is_array($carrier) ? $carrier : array( 'value' => $value );
            $selected = self::submission_selected_values_from_carrier($selection_carrier, $meta);
            return is_array($selected) ? count($selected) : -1;
        }
        if( $length_mode==='keywords' ) {
            return count(self::submission_keyword_values($value, $meta));
        }
        return self::submission_unicode_length($value);
    }

    private static function javascript_u_mode_regex_is_compatible( $regex ) {
        $length = strlen($regex);
        for( $i=0; $i<$length; $i++ ) {
            if( $regex[$i]!=='\\' ) continue;
            $i++;
            if( $i>=$length ) return false;
            $escape = $regex[$i];
            if( preg_match('/[1-9]/', $escape)===1 || strpos('dDsSwWbBfnrtv', $escape)!==false ) continue;
            if( $escape==='0' ) {
                if( isset($regex[$i + 1]) && preg_match('/\d/', $regex[$i + 1])===1 ) return false;
                continue;
            }
            if( $escape==='c' ) {
                if( !isset($regex[$i + 1]) || preg_match('/[A-Za-z]/', $regex[$i + 1])!==1 ) return false;
                $i++;
                continue;
            }
            if( $escape==='x' ) {
                if( preg_match('/\A[0-9A-Fa-f]{2}\z/', substr($regex, $i + 1, 2))!==1 ) return false;
                $i += 2;
                continue;
            }
            if( $escape==='u' ) {
                if( preg_match('/\A\{[0-9A-Fa-f]{1,6}\}/', substr($regex, $i + 1), $matches)===1 ) {
                    $i += strlen($matches[0]);
                    continue;
                }
                if( preg_match('/\A[0-9A-Fa-f]{4}\z/', substr($regex, $i + 1, 4))!==1 ) return false;
                $i += 4;
                continue;
            }
            if( $escape==='p' || $escape==='P' ) {
                if( preg_match('/\A\{[^{}]+\}/', substr($regex, $i + 1), $matches)!==1 ) return false;
                $i += strlen($matches[0]);
                continue;
            }
            if( $escape==='k' ) {
                if( preg_match('/\A<[A-Za-z_][A-Za-z0-9_]*>/', substr($regex, $i + 1), $matches)!==1 ) return false;
                $i += strlen($matches[0]);
                continue;
            }
            if( preg_match('/[A-Za-z]/', $escape)===1 ) return false;
        }
        return true;
    }

    private static function submission_custom_regex_has_supported_structure( $regex ) {
        if( !is_string($regex) || strpos($regex, "\0")!==false ) {
            return false;
        }
        if( $regex==='' ) {
            return true;
        }
        return preg_match('/\(\?(?![:=!<])/', $regex)!==1
            && preg_match('/\(\*[A-Z]/', $regex)!==1;
    }
    private static function submission_custom_regex_is_supported( $regex ) {
        return self::submission_custom_regex_has_supported_structure($regex)
            && ( $regex==='' || self::javascript_u_mode_regex_is_compatible($regex) );
    }

    private static function legacy_plain_submission_custom_regex_source( $regex ) {
        if( !self::submission_custom_regex_has_supported_structure($regex) ) {
            return false;
        }
        $translated = '';
        $length = strlen($regex);
        for( $i=0; $i<$length; $i++ ) {
            $char = $regex[$i];
            if( $char!=='\\' ) {
                $translated .= $char;
                continue;
            }
            $i++;
            if( $i>=$length ) {
                return false;
            }
            $escape = $regex[$i];
            if( preg_match('/[1-9]/', $escape)===1 || strpos('dDsSwWbBfnrtv', $escape)!==false ) {
                $translated .= '\\' . $escape;
                continue;
            }
            if( $escape==='0' ) {
                if( isset($regex[$i + 1]) && preg_match('/\d/', $regex[$i + 1])===1 ) {
                    return false;
                }
                $translated .= '\\0';
                continue;
            }
            if( $escape==='c' ) {
                if( isset($regex[$i + 1]) && preg_match('/[A-Za-z]/', $regex[$i + 1])===1 ) {
                    $translated .= '\\c' . $regex[$i + 1];
                    $i++;
                }else{
                    $translated .= '\\\\c';
                }
                continue;
            }
            if( $escape==='x' ) {
                $hex = substr($regex, $i + 1, 2);
                if( preg_match('/\A[0-9A-Fa-f]{2}\z/', $hex)===1 ) {
                    $translated .= '\\x' . $hex;
                    $i += 2;
                }else{
                    $translated .= 'x';
                }
                continue;
            }
            if( $escape==='u' ) {
                if( preg_match('/\A\{[0-9A-Fa-f]{1,6}\}/', substr($regex, $i + 1), $matches)===1 ) {
                    $translated .= 'u' . $matches[0];
                    $i += strlen($matches[0]);
                    continue;
                }
                $hex = substr($regex, $i + 1, 4);
                if( preg_match('/\A[0-9A-Fa-f]{4}\z/', $hex)===1 ) {
                    $translated .= '\\x{' . $hex . '}';
                    $i += 4;
                }else{
                    $translated .= 'u';
                }
                continue;
            }
            if( $escape==='p' || $escape==='P' ) {
                if( preg_match('/\A\{[^{}]+\}/', substr($regex, $i + 1), $matches)===1 ) {
                    $translated .= $escape . $matches[0];
                    $i += strlen($matches[0]);
                }else{
                    $translated .= $escape;
                }
                continue;
            }
            if( $escape==='k' ) {
                if( preg_match('/\A<[A-Za-z_][A-Za-z0-9_]*>/', substr($regex, $i + 1), $matches)===1 ) {
                    $translated .= '\\k' . $matches[0];
                    $i += strlen($matches[0]);
                }else{
                    $translated .= 'k';
                }
                continue;
            }
            if( preg_match('/[A-Za-z]/', $escape)===1 ) {
                $translated .= $escape;
                continue;
            }
            $translated .= '\\' . $escape;
        }
        return $translated;
    }

    private static function escaped_submission_custom_regex( $regex ) {
        $escaped = '';
        $backslashes = 0;
        $length = strlen($regex);
        for( $i=0; $i<$length; $i++ ) {
            $char = $regex[$i];
            if( $char==='\\' ) {
                $escaped .= $char;
                $backslashes++;
                continue;
            }
            if( $char==='/' && ($backslashes % 2)===0 ) {
                $escaped .= '\\/';
            }else{
                $escaped .= $char;
            }
            $backslashes = 0;
        }
        return $escaped;
    }

    private static function compiled_submission_custom_regex_pattern( $regex, $unicode=true ) {
        $pattern = '/' . self::escaped_submission_custom_regex($regex) . '/' . ( $unicode ? 'u' : '' );
        return ( @preg_match($pattern, '')===false ) ? false : $pattern;
    }

    private static function javascript_u_mode_submission_custom_regex_pattern( $regex ) {
        if( !self::submission_custom_regex_is_supported($regex) ) {
            return false;
        }
        if( $regex==='' ) {
            return '';
        }
        return self::compiled_submission_custom_regex_pattern($regex, true);
    }

    private static function submission_custom_regex_pattern( $regex ) {
        if( !self::submission_custom_regex_has_supported_structure($regex) ) {
            return false;
        }
        if( $regex==='' ) {
            return '';
        }
        if( self::javascript_u_mode_regex_is_compatible($regex) ) {
            $pattern = self::compiled_submission_custom_regex_pattern($regex, true);
            if( $pattern!==false ) {
                return $pattern;
            }
            return self::compiled_submission_custom_regex_pattern($regex, false);
        }
        $legacy_regex = self::legacy_plain_submission_custom_regex_source($regex);
        if( $legacy_regex===false ) {
            return false;
        }
        return self::compiled_submission_custom_regex_pattern($legacy_regex, false);
    }

    private static function form_custom_regexes_are_compatible( $elements ) {
        if( !is_array($elements) ) {
            return false;
        }
        foreach( $elements as $element ) {
            if( !is_array($element) ) {
                return false;
            }
            $data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
            if( isset($data['validation']) && $data['validation']==='custom' ) {
                $custom_regex = isset($data['custom_regex']) && is_string($data['custom_regex'])
                    ? $data['custom_regex']
                    : '';
                if( self::javascript_u_mode_submission_custom_regex_pattern($custom_regex)===false ) {
                    return false;
                }
            }
            if( !empty($element['inner']) && !self::form_custom_regexes_are_compatible($element['inner']) ) {
                return false;
            }
        }
        return true;
    }
    private static function collect_incompatible_form_custom_regexes( $elements, &$patterns=array(), $identity_prefix='' ) {
        if( !is_array($elements) ) {
            return false;
        }
        foreach( $elements as $index => $element ) {
            if( !is_array($element) ) {
                return false;
            }
            $data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
            if( isset($data['validation']) && $data['validation']==='custom' ) {
                $custom_regex = isset($data['custom_regex']) && is_string($data['custom_regex'])
                    ? $data['custom_regex']
                    : '';
                if( self::javascript_u_mode_submission_custom_regex_pattern($custom_regex)===false ) {
                    $tag = isset($element['tag']) && is_string($element['tag']) ? $element['tag'] : '';
                    $field_name = isset($data['name']) && is_string($data['name']) ? $data['name'] : '';
                    $field_identity = $field_name!=='' ? $field_name : ( $identity_prefix . '[' . $index . ']' );
                    if( $tag!=='' ) {
                        $field_identity = $tag . ':' . $field_identity;
                    }
                    $serialized = wp_json_encode( array( $field_identity, $custom_regex ) );
                    if( !is_string($serialized) || $serialized==='' ) {
                        return false;
                    }
                    $patterns[] = $serialized;
                }
            }
            if( !empty($element['inner'])
                && !self::collect_incompatible_form_custom_regexes($element['inner'], $patterns, $identity_prefix . '[' . $index . ']') ) {
                return false;
            }
        }
        return true;
    }
    private static function legacy_form_custom_regexes_are_unchanged( $stored_elements, $submitted_elements ) {
        if( !is_array($stored_elements) || !is_array($submitted_elements) ) {
            return false;
        }
        $stored_patterns = array();
        $submitted_patterns = array();
        if( !self::collect_incompatible_form_custom_regexes($stored_elements, $stored_patterns)
            || !self::collect_incompatible_form_custom_regexes($submitted_elements, $submitted_patterns) ) {
            return false;
        }
        sort( $stored_patterns );
        sort( $submitted_patterns );
        return $stored_patterns===$submitted_patterns;
    }

    private static function submission_value_matches_validation( $value, $meta, $carrier=null ) {
        if( !is_array($meta) || !is_scalar($value) && $value!==null ) return false;
        $value = (string)$value;
        if( array_key_exists('exact_value', $meta) ) {
            $expected = $meta['exact_value'];
            if( !is_scalar($expected) && $expected!==null ) return false;
            return $value===(string)$expected;
        }
        if( !empty($meta['selection_limit']) || !empty($meta['enforce_choice_values']) ) {
            $selection_carrier = is_array($carrier) ? $carrier : array( 'value' => $value );
            $selected_values = self::submission_selected_values_from_carrier($selection_carrier, $meta);
            if( $value==='' && is_array($selected_values) && empty($selected_values) ) {
                return true;
            }
            if( $selected_values===false && empty($meta['allow_saved_choice_fallback']) ) {
                return false;
            }
            if( !empty($meta['selection_limit']) && $selected_values===false ) {
                return false;
            }
        }elseif( $value==='' ) {
            return true; // requiredness is a separate stored-field rule.
        }
        $validation = isset($meta['validation']) ? $meta['validation'] : '';
        if( $validation==='numeric' && preg_match('/^\d+$/D', $value)!==1 ) return false;
        if( $validation==='float' && preg_match('/^[+-]?\d+(?:\.\d+)?$/D', $value)!==1 ) return false;
        if( $validation==='email' && !is_email($value) ) return false;
        if( $validation==='phone'
            && preg_match('/^((\+)?[1-9]{1,2})?([-\s.])?((\(\d{1,4}\))|\d{1,4})(([-\s.])?[0-9]{1,12}){1,2}$/D', $value)!==1 ) return false;
        if( $validation==='website'
            && preg_match('/^(?:https?:\/\/)?(?:www\.)?[a-zA-Z0-9]+(?:[-.][a-zA-Z0-9]+)*\.[a-zA-Z]{2,63}(?::[0-9]{1,5})?(?:\/.*)?$/D', $value)!==1 ) return false;
        if( $validation==='custom' ) {
            $regex = self::submission_custom_regex_pattern(
                isset($meta['custom_regex']) ? $meta['custom_regex'] : ''
            );
            if( $regex===false ) return false;
            if( $regex!=='' && @preg_match($regex, $value)!==1 ) return false;
        }
        if( isset($meta['length_mode']) && $meta['length_mode']==='skip' ) {
            return true;
        }
        foreach(array('minlength'=>'min', 'maxlength'=>'max') as $key=>$bound) {
            if( isset($meta[$key]) && $meta[$key]!=='' && is_numeric($meta[$key]) ) {
                $length = self::submission_value_length( $value, $meta, $carrier );
                if( $length<0 || ($bound==='min' && $length<(int)$meta[$key]) || ($bound==='max' && $length>(int)$meta[$key]) ) return false;
            }
        }
        return true;
    }
    /**
     * Accept only the exact geometry object emitted by assets/js/common.js.
     * A manually typed address legitimately has an empty location object.
     */
    private static function submission_google_address_geometry_is_valid( $carrier ) {
        if( !is_array($carrier) || !isset($carrier['geometry']) || !is_array($carrier['geometry'])
            || array_diff(array_keys($carrier['geometry']), array('location'))
            || !isset($carrier['geometry']['location']) || !is_array($carrier['geometry']['location'])
            || array_diff(array_keys($carrier['geometry']['location']), array('lat', 'lng')) ) return false;
        $location = $carrier['geometry']['location'];
        $has_lat = array_key_exists('lat', $location);
        $has_lng = array_key_exists('lng', $location);
        if( $has_lat!==$has_lng ) return false;
        if( !$has_lat ) return true;
        if( !is_numeric($location['lat']) || !is_numeric($location['lng']) ) return false;
        $lat = (float)$location['lat'];
        $lng = (float)$location['lng'];
        return is_finite($lat) && is_finite($lng)
            && $lat>=-90 && $lat<=90 && $lng>=-180 && $lng<=180;
    }

    private static function collect_dynamic_submission_contract( $elements, &$groups=array(), $form_id=0 ) {
        if( !is_array($elements) ) return;
        foreach( $elements as $element ) {
            if( !is_array($element) ) continue;
            $data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
            $inner = (isset($element['inner']) && is_array($element['inner'])) ? $element['inner'] : array();
            if( isset($element['tag']) && $element['tag']==='column'
                && isset($data['duplicate']) && $data['duplicate']==='enabled' && !empty($inner) ) {
                $group_name = self::first_repeater_group_name( $inner );
                $row_contract = array();
                self::collect_submission_field_contract($inner, $row_contract, 1, $form_id);
                if( !is_string($group_name) || $group_name==='' || empty($row_contract) ) {
                    continue;
                }
                if( isset($groups[$group_name]) ) {
                    $groups[$group_name] = false;
                }else{
                    $groups[$group_name] = $row_contract;
                }
            }
            if( !empty($inner) ) self::collect_dynamic_submission_contract($inner, $groups, $form_id);
        }
    }

    private static function submission_array_is_indexed( $value ) {
        if( !is_array($value) ) return false;
        $index = 0;
        foreach( array_keys($value) as $key ) {
            if( $key!==$index++ ) return false;
        }
        return true;
    }

    private static function submission_file_record_keys_are_allowed( $file ) {
        return is_array($file) && empty(array_diff(
            array_keys($file),
            array(
                'name', 'value', 'url', 'label', 'exclude', 'exclude_entry',
                'excludeconditional', 'upload_token', 'retention_token', 'type',
                'size', 'attachment', 'path', 'subdir', '_super_file_authority',
                '_super_file_proof'
            )
        ));
    }

    private static function submission_carrier_keys_are_allowed( $carrier, $meta ) {
        if( !is_array($carrier) || !is_array($meta) ) {
            return false;
        }
        if( isset($carrier['type']) && $carrier['type']==='files' ) {
            return empty(array_diff(
                array_keys($carrier),
                array('name', 'label', 'type', 'exclude', 'exclude_entry', 'files', 'field_name')
            ));
        }
        $allowed = array(
            'name', 'value', 'label', 'exclude', 'replace_commas',
            'exclude_entry', 'excludeconditional', 'type'
        );
        if( !empty($meta['selection_limit']) || !empty($meta['enforce_choice_values']) || !empty($meta['allows_selected_values']) ) {
            $allowed[] = 'selected_values';
        }
        if( !empty($meta['allows_geometry']) ) {
            $allowed[] = 'geometry';
        }
        if( !empty($meta['allows_timestamp']) ) {
            $allowed[] = 'timestamp';
        }
        if( !empty($meta['allows_signature_lines']) ) {
            $allowed[] = 'signatureLines';
        }
        if( !empty($meta['allows_hidden_code']) ) {
            $allowed[] = 'code';
            $allowed[] = 'invoice_padding';
        }
        return empty(array_diff(array_keys($carrier), $allowed));
    }

    private static function submission_dynamic_route_name( $name, $carrier ) {
        if( !is_string($name) || $name==='' || !is_array($carrier) ) {
            return false;
        }
        if( isset($carrier['name']) ) {
            if( !is_string($carrier['name']) || $carrier['name']==='' || $carrier['name']!==$name ) {
                return false;
            }
        }
        return $name;
    }


    private static function submission_dynamic_contract_meta( $name, $contract ) {
        if( !is_string($name) || !is_array($contract) ) {
            return false;
        }
        $identity = self::submission_route_contract_identity($name, $contract);
        return $identity===false ? false : $identity['meta'];
    }

    private static function submission_route_contract_identity( $route_name, $contract, $expected_stored_field_name=null ) {
        if( !is_string($route_name) || $route_name==='' || !is_array($contract) ) {
            return false;
        }
        $match = static function( $candidate_route, $stored_field_name, $meta ) {
            if( !is_string($candidate_route) || $candidate_route===''
                || !is_string($stored_field_name) || $stored_field_name===''
                || !is_array($meta) ) {
                return false;
            }
            $depth = isset($meta['nested_repeater_suffix_depth'])
                ? max( 0, absint($meta['nested_repeater_suffix_depth']) )
                : 0;
            $pattern = '/\A' . preg_quote($stored_field_name, '/');
            if( $depth>0 ) {
                $pattern .= '(?:\[\d+\]){' . $depth . '}';
            }
            if( !empty($meta['repeatable']) ) {
                $pattern .= '(?:_([1-9]\d*))?';
            }
            $pattern .= '\z/';
            if( preg_match($pattern, $candidate_route, $matches)!==1 ) {
                return false;
            }
            return array(
                'stored_field_name' => $stored_field_name,
                'route_name' => $candidate_route,
                'meta' => $meta,
                'row_ordinal' => !empty($matches[1]) ? absint($matches[1]) : 1,
            );
        };
        if( $expected_stored_field_name!==null ) {
            if( !is_string($expected_stored_field_name)
                || !isset($contract[$expected_stored_field_name])
                || !is_array($contract[$expected_stored_field_name]) ) {
                return false;
            }
            return $match($route_name, $expected_stored_field_name, $contract[$expected_stored_field_name]);
        }
        if( isset($contract[$route_name]) && is_array($contract[$route_name]) ) {
            $exact = $match($route_name, $route_name, $contract[$route_name]);
            if( $exact!==false ) {
                return $exact;
            }
        }
        $matches = array();
        foreach( $contract as $stored_field_name => $meta ) {
            if( !is_string($stored_field_name) || !is_array($meta) || $stored_field_name===$route_name ) {
                continue;
            }
            $candidate = $match($route_name, $stored_field_name, $meta);
            if( $candidate!==false ) {
                $matches[$stored_field_name] = $candidate;
            }
        }
        if( count($matches)!==1 ) {
            return false;
        }
        return reset($matches);
    }

    private static function submission_dynamic_row_identity( $identity ) {
        if( !is_array($identity) || !isset($identity['row_ordinal']) ) {
            return false;
        }
        $row_ordinal = absint($identity['row_ordinal']);
        return $row_ordinal>0 ? 'alias:' . $row_ordinal : false;
    }

    private static function submission_name_targets_repeatable_field( $name, $contract ) {
        if( !is_string($name) || !is_array($contract) ) {
            return false;
        }
        if( isset($contract[$name]) && is_array($contract[$name]) && !empty($contract[$name]['repeatable']) ) {
            return true;
        }
        $base_name = self::submission_lookup_field_name($name);
        return $base_name!==$name
            && isset($contract[$base_name])
            && is_array($contract[$base_name])
            && !empty($contract[$base_name]['repeatable']);
    }

    private static function submission_carrier_matches_contract( $name, $carrier, $meta ) {
        if( !is_string($name) || !is_array($carrier) || !is_array($meta)
            || !isset($carrier['type']) || !is_string($carrier['type'])
            || !isset($meta['type']) || $carrier['type']!==$meta['type']
            || (isset($carrier['name']) && (!is_string($carrier['name']) || $carrier['name']!==$name))
            || !self::submission_carrier_keys_are_allowed($carrier, $meta) ) return false;
        if( $carrier['type']==='files' ) {
            if( !isset($carrier['files']) || !is_array($carrier['files']) ) return false;
            foreach( $carrier['files'] as $file ) {
                if( !self::submission_file_record_keys_are_allowed($file) ) return false;
            }
            return true;
        }
        if( array_key_exists('selected_values', $carrier) ) {
            $selected_values = self::submission_selected_values_payload($carrier);
            if( $selected_values===false ) return false;
        }
        if( $carrier['type']==='google_address'
            && !self::submission_google_address_geometry_is_valid($carrier) ) return false;
        return array_key_exists('value', $carrier)
            && (is_scalar($carrier['value']) || $carrier['value']===null)
            && self::submission_value_matches_validation($carrier['value'], $meta, $carrier);
    }

    private static function submission_carriers_equivalent( $left, $right ) {
        if( !is_array($left) || !is_array($right) ) {
            return false;
        }
        unset($left['name'], $right['name']);
        return $left===$right;
    }

    private static function dynamic_submission_data_matches_contract( $dynamic_data, $groups, &$routes=array() ) {
        if( !is_array($dynamic_data) || !is_array($groups) ) return false;
        $routes = array();
        foreach( $dynamic_data as $group_name=>$rows ) {
            if( !is_string($group_name) || !isset($groups[$group_name]) || $groups[$group_name]===false
                || empty($rows) || !self::submission_array_is_indexed($rows) ) return false;
            foreach( $rows as $row ) {
                $row_identity = null;
                if( !is_array($row) || empty($row) || self::submission_array_is_indexed($row) ) return false;
                foreach( $row as $name=>$carrier ) {
                    if( !is_string($name) || !is_array($carrier) ) return false;
                    $route_name = self::submission_dynamic_route_name($name, $carrier);
                    // Dynamic-group carriers resolve through the shared route-contract identity resolver.
                    $route_identity = ( $route_name!==false )
                        ? self::submission_route_contract_identity( $route_name, $groups[$group_name] )
                        : false;
                    if( $route_identity===false ) {
                        return false;
                    }
                    $meta = $route_identity['meta'];
                    $carrier_for_contract = $carrier;
                    unset($carrier_for_contract['name']);
                    if( !self::submission_carrier_matches_contract($name, $carrier_for_contract, $meta) ) {
                        return false;
                    }
                    $current_row_identity = self::submission_dynamic_row_identity($route_identity);
                    if( $current_row_identity===false ) {
                        return false;
                    }
                    if( $row_identity===null ) {
                        $row_identity = $current_row_identity;
                    }elseif( $row_identity!==$current_row_identity ) {
                        return false;
                    }
                    if( isset($routes[$route_name]) ) {
                        return false;
                    }
                    $routes[$route_name] = array(
                        'carrier' => $carrier,
                        'meta' => $meta,
                    );
                }
            }
        }
        return true;
    }

    private static function submission_identity_carrier_matches( $carrier, $name, $type, $expected_id ) {
        if( !is_array($carrier) || count($carrier)!==3
            || array_diff(array_keys($carrier), array('name', 'value', 'type'))
            || !isset($carrier['name'], $carrier['value'], $carrier['type'])
            || !is_string($carrier['name']) || !is_string($carrier['value']) || !is_string($carrier['type'])
            || $carrier['name']!==$name || $carrier['type']!==$type ) return false;
        if( $expected_id==='' ) return in_array($carrier['value'], array('', '0'), true);
        return preg_match('/^[1-9]\d*$/D', $carrier['value'])===1
            && $carrier['value']===(string)absint($expected_id);
    }

    private static function submission_data_matches_contract( $data, $elements, $form_id, $entry_id, $list_id ) {
        if( !is_array($data) ) return false;
        $contract = array();
        $dynamic_groups = array();
        $dynamic_routes = array();
        self::collect_submission_field_contract($elements, $contract, 0, $form_id);
        $contract = self::apply_register_login_role_contracts($contract, $form_id);
        self::collect_dynamic_submission_contract($elements, $dynamic_groups, $form_id);
        if( !isset($data['hidden_form_id'], $data['hidden_contact_entry_id'])
            || !self::submission_identity_carrier_matches($data['hidden_form_id'], 'hidden_form_id', 'form_id', $form_id)
            || !self::submission_identity_carrier_matches($data['hidden_contact_entry_id'], 'hidden_contact_entry_id', 'entry_id', $entry_id) ) return false;
        if( isset($data['_super_dynamic_data']) ) {
            if( !self::dynamic_submission_data_matches_contract($data['_super_dynamic_data'], $dynamic_groups, $dynamic_routes) ) {
                return false;
            }
        }else{
            foreach( $data as $name => $carrier ) {
                if( in_array($name, array('hidden_form_id', 'hidden_contact_entry_id', 'hidden_list_id', '_generated_pdf_file'), true) ) {
                    continue;
                }
                if( self::submission_name_targets_repeatable_field($name, $contract) ) {
                    return false;
                }
            }
        }
        foreach( $data as $name=>$carrier ) {
            if( $name==='_super_dynamic_data' ) {
                continue;
            }
            if( $name==='hidden_list_id' ) {
                if( $list_id==='' || !is_array($carrier) || !isset($carrier['type'])
                    || $carrier['type']!=='var' || !array_key_exists('value', $carrier)
                    || !is_scalar($carrier['value']) || (string)$carrier['value']!==(string)$list_id ) return false;
                continue;
            }
            if( $name==='hidden_form_id' || $name==='hidden_contact_entry_id' ) continue;
            if( $name==='_generated_pdf_file' ) continue; // materialized later under stored PDF settings.
            if( !is_string($name) ) return false;
            $dynamic_route = isset($dynamic_routes[$name]) ? $dynamic_routes[$name] : false;
            if( isset($contract[$name]) && $contract[$name]!==false ) {
                if( !empty($contract[$name]['repeatable']) && $dynamic_route===false ) {
                    return false;
                }
                if( !self::submission_carrier_matches_contract($name, $carrier, $contract[$name]) ) {
                    return false;
                }
                if( $dynamic_route!==false
                    && !self::submission_carriers_equivalent($carrier, $dynamic_route['carrier']) ) {
                    return false;
                }
                continue;
            }
            if( $dynamic_route!==false ) {
                $alias_carrier = $carrier;
                unset($alias_carrier['name']);
                if( !self::submission_carrier_matches_contract($name, $alias_carrier, $dynamic_route['meta'])
                    || !self::submission_carriers_equivalent($carrier, $dynamic_route['carrier']) ) {
                    return false;
                }
                continue;
            }
            return false;
        }
        foreach( $dynamic_routes as $route_name => $unused ) {
            if( !array_key_exists($route_name, $data) ) {
                return false;
            }
        }
        return true;
    }

    /**
     * Treat scalar and flat scalar-array values like the browser does while rejecting
     * nested or object payloads as empty. This prevents malformed direct requests from
     * turning required-field validation into a PHP error.
     */
    private static function required_field_value_present( $value ) {
        if( is_array( $value ) ) {
            $parts = array();
            foreach( $value as $part ) {
                if( !is_scalar( $part ) && $part !== null ) return false;
                $parts[] = ( $part === null ) ? '' : (string) $part;
            }
            $value = implode( '', $parts );
        } elseif( !is_scalar( $value ) && $value !== null ) {
            return false;
        }
        return trim( wp_strip_all_tags( (string) $value ) ) !== '';
    }

    /**
     * Validate every required field in every row of the repeater group assigned
     * from the stored form tree. Payload-provided group names never establish
     * membership, so moving a valid value into a forged group cannot bypass checks.
     */
    private static function validate_repeater_required_values( $dynamic_data, $required_fields, $form_elements, $form_id=0 ) {
        $groups = array();
        foreach( $required_fields as $field_name => $meta ) {
            if( empty( $meta['repeater_enforceable'] ) ) continue;
            $group_name = isset( $meta['repeater_group'] ) ? $meta['repeater_group'] : '';
            if( !is_string( $group_name ) || $group_name === '' ) return false;
            if( !isset( $groups[ $group_name ] ) ) $groups[ $group_name ] = array();
            $groups[ $group_name ][ $field_name ] = true;
        }
        if( empty( $groups ) ) return true;
        if( !is_array( $dynamic_data ) || !is_array( $form_elements ) ) return false;
        $dynamic_groups = array();
        self::collect_dynamic_submission_contract( $form_elements, $dynamic_groups, $form_id );
        foreach( $groups as $group_name => $group_required_fields ) {
            if( !isset( $dynamic_data[ $group_name ] )
                || !is_array( $dynamic_data[ $group_name ] )
                || empty( $dynamic_data[ $group_name ] )
                || !isset( $dynamic_groups[ $group_name ] )
                || !is_array( $dynamic_groups[ $group_name ] ) ) {
                return false;
            }
            $group_contract = $dynamic_groups[ $group_name ];
            foreach( $dynamic_data[ $group_name ] as $row ) {
                if( !is_array( $row ) ) return false;
                foreach( $group_required_fields as $field_name => $_required ) {
                    $matched = null;
                    foreach( $row as $row_field_name => $row_field_data ) {
                        $route_name = self::submission_dynamic_route_name($row_field_name, $row_field_data);
                        // Dynamic-group carriers resolve through the shared route-contract identity resolver.
                        $identity = ( $route_name!==false )
                            ? self::submission_route_contract_identity( $route_name, $group_contract, $field_name )
                            : false;
                        if( $identity===false ) {
                            continue;
                        }
                        if( !is_array($row_field_data) || !array_key_exists('value', $row_field_data) ) {
                            return false;
                        }
                        $matched = self::required_field_value_present( $row_field_data['value'] );
                        break;
                    }
                    if( $matched!==true ) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * @since 6.3.315 - Merge one classified required field into the accumulator.
     * Duplicate names remain eligible for top-level enforcement only when every
     * occurrence is unconditional. Row enforcement is disabled because a payload
     * cannot identify which same-named repeater occurrence a value belongs to.
     */
    private static function merge_required_meta( $required, $name, $meta ) {
        if( isset( $required[ $name ] ) ) {
            $meta['always_present'] = ( $required[ $name ]['always_present'] && $meta['always_present'] );
            $meta['repeater_enforceable'] = false;
        }
        $required[ $name ] = $meta;
        return $required;
    }

    /**
     * @since 6.3.315 - True when ANY element in the subtree can disappear
     * conditionally or on mobile. Such repeaters are exempt from row-level
     * enforcement because common.js can change positional key normalization.

     */
    private static function subtree_has_dynamic_visibility( $elements ) {
        if( !is_array( $elements ) ) return false;
        foreach( $elements as $element ) {
            $edata = ( isset( $element['data'] ) && is_array( $element['data'] ) ) ? $element['data'] : array();
            $ca = isset( $edata['conditional_action'] ) ? $edata['conditional_action'] : '';
            $mobile_hide = ( ( isset( $edata['hide_on_mobile'] ) && $edata['hide_on_mobile'] === 'true' )
                || ( isset( $edata['hide_on_mobile_window'] ) && $edata['hide_on_mobile_window'] === 'true' ) );
            if( ( $ca !== '' && $ca !== 'disabled' ) || $mobile_hide ) return true;
            if( !empty( $element['inner'] ) && self::subtree_has_dynamic_visibility( $element['inner'] ) ) return true;
        }
        return false;
    }
    private static function form_recaptcha_versions( $elements, &$versions=array() ) {
        if( !is_array($elements) ) return;
        foreach( $elements as $element ) {
            if( !is_array($element) ) continue;
            if( isset($element['tag']) && $element['tag']==='recaptcha' ) {
                $data = isset($element['data']) && is_array($element['data']) ? $element['data'] : array();
                $versions[(!empty($data['version']) && $data['version']==='v3') ? 'v3' : 'v2'] = true;
            }
            if( !empty($element['inner']) ) self::form_recaptcha_versions($element['inner'], $versions);
        }
    }


    /**
     * Collect exact stored file elements. Duplicate names are deliberately ambiguous.
     */
    private static function collect_file_elements( $elements, $field_name, &$matches ) {
        if( !is_array($elements) ) return;
        foreach( $elements as $element ) {
            if( !is_array($element) ) continue;
            $edata = ( isset($element['data']) && is_array($element['data']) ) ? $element['data'] : array();
            if( isset($element['tag']) && $element['tag']==='file'
                && isset($edata['name']) && is_string($edata['name'])
                && $edata['name']===$field_name ) {
                $matches[] = $edata;
            }
            if( !empty($element['inner']) ) self::collect_file_elements($element['inner'], $field_name, $matches);
        }
    }

    private static function get_file_element( $elements, $field_name ) {
        if( !is_string($field_name) || $field_name==='' ) return false;
        $matches = array();
        self::collect_file_elements($elements, $field_name, $matches);
        return count($matches)===1 ? $matches[0] : false;
    }

    /**
     * Expand WordPress MIME keys such as "jpg|jpeg|jpe" into literal extension keys.
     */
    private static function expand_mime_types( $mime_types ) {
        $expanded = array();
        $ambiguous = array();
        if( !is_array($mime_types) ) return $expanded;
        foreach( $mime_types as $extensions => $mime ) {
            if( !is_string($extensions) || !is_string($mime) || $mime==='' ) continue;
            foreach( explode('|', strtolower($extensions)) as $extension ) {
                $extension = trim($extension);
                if( $extension==='' || sanitize_key($extension)!==$extension
                    || !preg_match('/^[a-z0-9]+$/D', $extension)
                    || isset($ambiguous[$extension]) ) continue;
                if( isset($expanded[$extension]) && $expanded[$extension]!==$mime ) {
                    unset($expanded[$extension]);
                    $ambiguous[$extension] = true;
                    continue;
                }
                $expanded[$extension] = $mime;
            }
        }
        return $expanded;
    }
    /**
     * Return only literal extensions shipped by WordPress core.
     */
    private static function core_upload_extensions() {
        static $extensions = null;
        if( $extensions!==null ) return $extensions;
        $extensions = array();
        if( !function_exists('wp_get_ext_types') ) return $extensions;
        foreach( wp_get_ext_types() as $type_extensions ) {
            if( !is_array($type_extensions) ) continue;
            foreach( $type_extensions as $extension ) {
                $extension = strtolower((string) $extension);
                if( preg_match('/^[a-z0-9]+$/D', $extension) ) {
                    $extensions[$extension] = true;
                }
            }
        }
        return $extensions;
    }


    private static function dangerous_upload_extensions() {
        return array_fill_keys(array(
            'php', 'php2', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8',
            'phtml', 'pht', 'phtm', 'phps', 'phar', 'inc', 'cgi', 'pl', 'py',
            'rb', 'sh', 'bash', 'zsh', 'ksh', 'asp', 'aspx', 'asa', 'cer',
            'jsp', 'jspx', 'jsw', 'jsv', 'jspf', 'exe', 'com', 'bat', 'cmd',
            'msi', 'dll', 'so', 'htaccess', 'userini', 'config', 'ini', 'shtml', 'shtm',
            'html', 'htm', 'xhtml', 'js', 'mjs', 'jar', 'swf', 'svg', 'svgz',
            'xml', 'xsl', 'xslt', 'css',
        ), true);
    }

    /**
     * Resolve a field policy only from literal extensions present in WordPress core.
     * The legacy filter may narrow that set, but may not add or remap a type.
     */
    private static function allowed_file_mime_types( $file_element ) {
        $extension_setting = 'jpg|jpeg|png|gif|pdf';
        if( isset($file_element['extensions']) && is_string($file_element['extensions'])
            && trim($file_element['extensions'])!=='' ) {
            $extension_setting = $file_element['extensions'];
        }
        $core_mimes = wp_get_mime_types();
        $filtered_mimes = apply_filters('super_file_upload_mime_types_validation', $core_mimes);
        $core = self::expand_mime_types($core_mimes);
        $filtered = self::expand_mime_types($filtered_mimes);
        $core_extensions = self::core_upload_extensions();
        $dangerous = self::dangerous_upload_extensions();
        $allowed = array();
        foreach( explode('|', strtolower($extension_setting)) as $extension ) {
            $extension = trim($extension);
            if( $extension==='' || sanitize_key($extension)!==$extension
                || !preg_match('/^[a-z0-9]+$/D', $extension)
                || isset($dangerous[$extension]) ) continue;
            if( !isset($core_extensions[$extension]) || !isset($core[$extension]) || !isset($filtered[$extension]) ) continue;
            if( $filtered[$extension]!==$core[$extension] ) continue;
            if( preg_match('/(?:php|x-httpd|x-cgi|x-perl|x-python|x-ruby|javascript|ecmascript|text\\/html)/i', $core[$extension]) ) continue;
            $allowed[$extension] = $core[$extension];
        }
        return $allowed;
    }

    private static function upload_path_is_descendant( $target, $root ) {
        $target = untrailingslashit(wp_normalize_path($target));
        $root = untrailingslashit(wp_normalize_path($root));
        if( DIRECTORY_SEPARATOR==='\\' ) {
            $target = strtolower($target);
            $root = strtolower($root);
        }
        return $target!==$root && strpos($target, trailingslashit($root))===0;
    }

    /**
     * Build the only record shape that can later authorize attachment/file effects.
     */
    public static function build_owned_upload( $form_id, $field_name, $filename, $mime, $url, $attachment_id, $allowed_root, $size, $legacy_subdir='' ) {
        $root = realpath($allowed_root);
        $file = realpath($filename);
        if( $root===false || $file===false || is_link($filename) || !is_file($file) ) return false;
        $size = is_int($size) ? $size : filesize($file);
        if( !is_int($size) || $size<0 ) return false;
        $root = untrailingslashit(wp_normalize_path($root));
        $file = untrailingslashit(wp_normalize_path($file));
        if( !self::upload_path_is_descendant($file, $root) ) return false;
        $attachment_id = absint($attachment_id);
        if( $attachment_id!==0 ) {
            $attached_file = get_attached_file($attachment_id);
            $attached_real = $attached_file ? realpath($attached_file) : false;
            if( get_post_type($attachment_id)!=='attachment' || $attached_real===false
                || wp_normalize_path($attached_real)!==$file ) return false;
        }
        return array(
            'version' => 1,
            'form_id' => absint($form_id),
            'field' => $field_name,
            'storage' => $attachment_id ? 'attachment' : 'custom',
            'file' => $file,
            'attachment' => $attachment_id,
            'custom_path' => $attachment_id ? '' : $file,
            'allowed_root' => $root,
            'mime' => $mime,
            'url' => $url,
            'size' => $size,
            'basename' => basename($file),
            'legacy_subdir' => $legacy_subdir,
        );
    }

    private static function owned_upload_is_current( $owned, $expected_parent=null ) {
        if( !is_array($owned)
            || !isset($owned['version']) || $owned['version']!==1
            || empty($owned['form_id']) || !isset($owned['field']) || !is_string($owned['field'])
            || !isset($owned['storage']) || !isset($owned['file']) || !is_string($owned['file'])
            || !isset($owned['allowed_root']) || !is_string($owned['allowed_root'])
            || !isset($owned['mime']) || !is_string($owned['mime'])
            || !isset($owned['size']) || !is_int($owned['size'])
            || !isset($owned['basename']) || !is_string($owned['basename']) ) return false;
        $root = realpath($owned['allowed_root']);
        $file = realpath($owned['file']);
        $current_size = ( $file!==false && !is_link($owned['file']) && is_file($file) ) ? filesize($file) : false;
        if( $root===false || $file===false || $current_size===false || !is_int($current_size) || is_link($owned['file']) || !is_file($file) ) return false;
        $root = untrailingslashit(wp_normalize_path($root));
        $file = untrailingslashit(wp_normalize_path($file));
        if( $root!==untrailingslashit(wp_normalize_path($owned['allowed_root']))
            || $file!==untrailingslashit(wp_normalize_path($owned['file']))
            || $current_size!==$owned['size']
            || !self::upload_path_is_descendant($file, $root)
            || basename($file)!==$owned['basename'] ) return false;
        if( $owned['storage']==='attachment' ) {
            $attachment_id = isset($owned['attachment']) ? absint($owned['attachment']) : 0;
            $attached_file = $attachment_id ? get_attached_file($attachment_id) : false;
            $attached_real = $attached_file ? realpath($attached_file) : false;
            $stored_form_id = $attachment_id
                ? get_post_meta($attachment_id, '_super_forms_upload_form_id', true)
                : '';
            $stored_field = $attachment_id
                ? get_post_meta($attachment_id, '_super_forms_upload_field', true)
                : '';
            $metadata_owned = absint($stored_form_id)===absint($owned['form_id'])
                && (string) $stored_field===$owned['field'];
            $legacy_entry_id = isset($owned['legacy_entry_id'])
                ? absint($owned['legacy_entry_id'])
                : 0;
            $legacy_owned = $legacy_entry_id
                && isset($owned['legacy_source_field'], $owned['legacy_source_key'])
                && is_string($owned['legacy_source_field'])
                && $owned['legacy_source_field']!==''
                && ($stored_form_id==='' || absint($stored_form_id)===absint($owned['form_id']))
                && ($stored_field==='' || (string) $stored_field===$owned['field'])
                && wp_get_post_parent_id($attachment_id)===$legacy_entry_id;
            if( !$attachment_id || get_post_type($attachment_id)!=='attachment'
                || !get_post_meta($attachment_id, 'super-forms-form-upload-file', true)
                || (!$metadata_owned && !$legacy_owned)
                || $attached_real===false || wp_normalize_path($attached_real)!==$file
                || get_post_mime_type($attachment_id)!==$owned['mime'] ) return false;
            if( $expected_parent!==null && wp_get_post_parent_id($attachment_id)!==absint($expected_parent) ) return false;
            return true;
        }
        return $owned['storage']==='custom'
            && isset($owned['custom_path'])
            && wp_normalize_path($owned['custom_path'])===$file;
    }

    /**
     * Apply the same public-request CSRF policy to upload and submission.
     */
    private static function csrf_policy_allows_request( $verified, $global_settings ) {
        if( $verified ) return true;
        return is_array($global_settings)
            && (
                ( !empty($global_settings['csrf_check']) && $global_settings['csrf_check']==='false' )
                || ( isset($global_settings['allow_storing_cookies']) && $global_settings['allow_storing_cookies']==='0' )
            );
    }

    /**
     * Anonymous requests may address only an exact published form. Editors may preview
     * a non-published form only when they can edit that exact post.
     */
    private static function upload_form_id_is_valid( $form_id ) {
        $form_id = absint($form_id);
        if( !$form_id || get_post_type($form_id)!=='super_form' ) return false;
        if( get_post_status($form_id)==='publish' ) return true;
        return is_user_logged_in() && current_user_can('edit_post', $form_id);
    }

    private static function request_uses_sessionless_submission_mode( $form_id=0 ) {
        $form_id = absint($form_id);
        if( $form_id!==0 && !self::upload_form_id_is_valid($form_id) ) {
            return false;
        }
        return self::csrf_policy_allows_request(false, SUPER_Common::get_global_settings());
    }

    private static function upload_receipt_uses_bearer_binding( $form_id ) {
        return get_current_user_id()===0 && self::request_uses_sessionless_submission_mode($form_id);
    }

    private static function submission_listing_host_form_id( $form_id ) {
        if( !isset($_POST['listing_form_id']) ) {
            return absint($form_id);
        }
        if( !class_exists('SUPER_Listings') ) {
            return false;
        }
        $listing_form_id = SUPER_Listings::parse_form_id($_POST['listing_form_id']);
        if( $listing_form_id===false || get_post_type($listing_form_id)!=='super_form' ) {
            return false;
        }
        return absint($listing_form_id);
    }

    /**
     * Authorize an existing-entry submission only through the exact render-grant
     * or Listings policy that produced it.
     */
    private static function submission_entry_update_is_authorized( $entry_id, $list_id, $form_id, $settings, $listing_form_id=0, $require_listing_grant=false ) {
        $entry_id = absint($entry_id);
        $form_id = absint($form_id);
        if( $entry_id===0 ) return $list_id==='';
        $entry = get_post($entry_id);
        if( !($entry instanceof WP_Post) || $entry->post_type!=='super_contact_entry' ) return false;
        $entry_form_id = absint($entry->post_parent);
        $wc_order_id = get_post_meta($entry_id, '_super_contact_entry_wc_order_id', true);
        if( !empty($wc_order_id) ) return false;
        if( $list_id==='' ) {
            if( !is_array($settings)
                || empty($settings['update_contact_entry'])
                || $settings['update_contact_entry']!=='true' ) {
                return false;
            }
            if( $entry_form_id!==$form_id
                && !in_array( $entry_form_id, SUPER_Common::configured_retrieve_last_entry_form_ids( $form_id, $settings ), true ) ) {
                return false;
            }
            return SUPER_Common::entry_update_grant_matches_current(
                SUPER_Common::getClientData(
                    'update_contact_entry_' . absint($form_id) . '_' . $entry_id
                )
            );
        }
        if( $entry_form_id!==$form_id
            || !is_array($settings) || !class_exists('SUPER_Listings')
            || empty($settings['_listings']) || !is_array($settings['_listings'])
            || empty($settings['_listings']['lists']) || !is_array($settings['_listings']['lists']) ) return false;
        $list_id = absint($list_id);
        if( !isset($settings['_listings']['lists'][$list_id]) ) return false;
        $listing_form_id = $listing_form_id ? absint($listing_form_id) : $form_id;
        if( !$listing_form_id || get_post_type($listing_form_id)!=='super_form' ) return false;
        if( $require_listing_grant && !SUPER_Common::entry_update_grant_matches_current(
            SUPER_Common::getClientData(
                'update_contact_entry_' . absint($form_id) . '_' . absint($listing_form_id) . '_' . $list_id . '_' . $entry_id
            )
        ) ) return false;
        $list = SUPER_Listings::get_default_listings_settings($settings['_listings']['lists'][$list_id]);
        if( !SUPER_Listings::entry_is_in_retrieval_scope($list, $entry->post_parent, $listing_form_id) ) return false;
        $allow = SUPER_Listings::get_action_permissions(array('list'=>$list, 'entry'=>$entry));
        if( !is_array($allow) ) return false;
        if( !empty($allow['allowEditAny']) ) return true;
        if( empty($allow['allowEditOwn']) ) return false;
        $current_user_id = get_current_user_id();
        return $current_user_id>0 && $current_user_id===absint($entry->post_author);
    }

    /**
     * A configured field limit is a positive finite numeric value in megabytes.
     */
    private static function get_upload_field_size_limit( $file_element ) {
        if( !is_array($file_element) ) return false;
        $megabytes = 5;
        if( array_key_exists('filesize', $file_element) ) {
            if( !is_int($file_element['filesize'])
                && !is_float($file_element['filesize'])
                && !is_string($file_element['filesize']) ) return false;
            $configured = is_string($file_element['filesize'])
                ? trim($file_element['filesize'])
                : $file_element['filesize'];
            if( $configured==='' || !is_numeric($configured) ) return false;
            $megabytes = (float) $configured;
        }
        if( !is_finite((float) $megabytes) || $megabytes<=0 ) return false;
        $bytes = $megabytes * 1000000;
        if( !is_finite((float) $bytes) ) return false;
        return array(
            'megabytes' => $megabytes,
            'bytes' => $bytes,
        );
    }
    private static function get_upload_field_count_limit( $file_element, $setting_name ) {
        if( !is_array($file_element) ) return false;
        if( !array_key_exists($setting_name, $file_element) || $file_element[$setting_name]==='' ) {
            return 0;
        }
        if( !is_int($file_element[$setting_name])
            && !is_float($file_element[$setting_name])
            && !is_string($file_element[$setting_name]) ) {
            return false;
        }
        $configured = is_string($file_element[$setting_name])
            ? trim($file_element[$setting_name])
            : $file_element[$setting_name];
        if( $configured==='' || !is_numeric($configured) ) {
            return false;
        }
        $limit = (int) $configured;
        if( (string) $limit!==(string) (0 + $configured) || $limit<0 ) {
            return false;
        }
        return $limit;
    }
    private static function get_upload_field_policy( $file_element ) {
        $size_limit = self::get_upload_field_size_limit($file_element);
        $min_files = self::get_upload_field_count_limit($file_element, 'minlength');
        $max_files = self::get_upload_field_count_limit($file_element, 'maxlength');
        if( $size_limit===false || $min_files===false || $max_files===false ) {
            return false;
        }
        if( $max_files!==0 && $min_files>$max_files ) {
            return false;
        }
        $aggregate_bytes = false;
        if( $max_files>0 ) {
            $aggregate = (float) $size_limit['bytes'] * (float) $max_files;
            if( !is_finite($aggregate) || $aggregate<=0 ) {
                return false;
            }
            $aggregate_bytes = (int) floor($aggregate);
        }
        return array(
            'size_limit' => $size_limit,
            'min_files' => $min_files,
            'max_files' => $max_files,
            'aggregate_bytes' => $aggregate_bytes,
        );
    }
    private static function generated_pdf_settings( $settings ) {
        $pdf_settings = ( isset($settings['_pdf']) && is_array($settings['_pdf']) ) ? $settings['_pdf'] : array();
        if( class_exists('SUPER_PDF_Generator') ) {
            return SUPER_PDF_Generator::get_default_pdf_settings($pdf_settings);
        }
        if( empty($pdf_settings['generate']) ) $pdf_settings['generate'] = 'false';
        if( empty($pdf_settings['debug']) ) $pdf_settings['debug'] = 'false';
        if( empty($pdf_settings['filename']) ) $pdf_settings['filename'] = esc_html__( 'form', 'super-forms' ) . '.pdf';
        if( empty($pdf_settings['emailLabel']) ) $pdf_settings['emailLabel'] = esc_html__( 'PDF file', 'super-forms' ) . ':';
        if( empty($pdf_settings['adminEmail']) ) $pdf_settings['adminEmail'] = 'true';
        if( empty($pdf_settings['confirmationEmail']) ) $pdf_settings['confirmationEmail'] = 'true';
        if( empty($pdf_settings['excludeEntry']) ) $pdf_settings['excludeEntry'] = 'false';
        return $pdf_settings;
    }

    private static function generated_pdf_size_limit( $settings ) {
        $pdf_settings = self::generated_pdf_settings($settings);
        $configured = array();
        if( isset($pdf_settings['filesize']) ) {
            $configured['filesize'] = $pdf_settings['filesize'];
        }
        $field_limit = self::get_upload_field_size_limit($configured);
        if( $field_limit===false ) {
            return false;
        }
        $limit = 0.0;
        foreach( array( (float) $field_limit['bytes'], (float) wp_max_upload_size(), 10 * 1000000.0 ) as $candidate ) {
            if( is_finite($candidate) && $candidate>0 && ($limit===0.0 || $candidate<$limit) ) {
                $limit = $candidate;
            }
        }
        if( $limit<=0.0 ) {
            return false;
        }
        return array(
            'bytes' => (int) floor($limit),
            'settings' => $pdf_settings,
        );
    }

    private static function base64_decoded_size_upper_bound( $encoded ) {
        if( !is_string($encoded) ) return false;
        $encoded = trim($encoded);
        $length = strlen($encoded);
        if( $length===0 || ($length % 4)!==0 ) return false;
        if( preg_match('/[^A-Za-z0-9+\/=]/', $encoded)===1 ) return false;
        $padding = 0;
        if( substr($encoded, -2)==='==' ) {
            $padding = 2;
        }elseif( substr($encoded, -1)==='=' ) {
            $padding = 1;
        }
        return (int) (($length / 4) * 3) - $padding;
    }

    private static function current_upload_actor_context( $bootstrap_anonymous=false ) {
        $actor_id = get_current_user_id();
        if( $actor_id>0 ) {
            return array(
                'actor_id' => $actor_id,
                'actor_hash' => hash_hmac('sha256', 'wordpress-user:' . $actor_id, wp_salt('auth')),
            );
        }
        if( $bootstrap_anonymous ) {
            $browser_session_id = SUPER_Common::startClientSession( array( 'force' => true ) );
        }elseif( isset($_COOKIE['_sfs_id']) && is_string($_COOKIE['_sfs_id']) ) {
            $browser_session_id = wp_unslash($_COOKIE['_sfs_id']);
        }else{
            return false;
        }
        if( !is_string($browser_session_id)
            || preg_match('/\A[A-Za-z0-9]{32,128}\z/', $browser_session_id)!==1 ) {
            return false;
        }
        $client_data = get_option('_sfsdata_' . $browser_session_id, false);
        if( !is_array($client_data)
            || !isset($client_data['expires'])
            || absint($client_data['expires'])<time() ) {
            return false;
        }
        return array(
            'actor_id' => 0,
            'actor_hash' => hash_hmac('sha256', 'browser:' . $browser_session_id, wp_salt('auth')),
        );
    }

    private static function upload_receipt_actor_matches_current( $receipt, $bootstrap_anonymous=false ) {
        if( !is_array($receipt) ) {
            return false;
        }
        if( isset($receipt['binding']) ) {
            if( !is_string($receipt['binding'])
                || !in_array($receipt['binding'], array('actor', 'bearer'), true) ) {
                return false;
            }
            if( $receipt['binding']==='bearer' ) {
                return true;
            }
        }
        if( !isset($receipt['actor_id'], $receipt['actor_hash'])
            || !is_int($receipt['actor_id'])
            || !is_string($receipt['actor_hash'])
            || preg_match('/^[a-f0-9]{64}$/D', $receipt['actor_hash'])!==1 ) {
            return false;
        }
        $current = self::current_upload_actor_context($bootstrap_anonymous);
        return is_array($current)
            && $current['actor_id']===$receipt['actor_id']
            && is_string($current['actor_hash'])
            && hash_equals($receipt['actor_hash'], $current['actor_hash']);
    }

    private static function collect_submission_file_routes( $elements, &$routes=array(), $repeater_depth=0 ) {
        if( !is_array($elements) ) return;
        foreach( $elements as $element ) {
            if( !is_array($element) ) continue;
            $data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
            $tag = isset($element['tag']) ? $element['tag'] : '';
            $child_repeater_depth = $repeater_depth;
            if( $tag==='column' && isset($data['duplicate']) && $data['duplicate']==='enabled' ) {
                $child_repeater_depth++;
            }
            if( !empty($element['inner']) ) {
                self::collect_submission_file_routes($element['inner'], $routes, $child_repeater_depth);
            }
            if( $tag!=='file' || !isset($data['name']) || !is_string($data['name']) || $data['name']==='' ) {
                continue;
            }
            $meta = array(
                'stored_field_name' => $data['name'],
                'nested_repeater_suffix_depth' => max( 0, $repeater_depth-1 ),
                'repeatable' => ( $repeater_depth>0 ),
            );
            if( isset($routes[$data['name']]) ) {
                $routes[$data['name']] = false;
            } else {
                $routes[$data['name']] = $meta;
            }
        }
    }

    private static function submission_file_stored_field_name( $route_name, $field_data, $file_routes ) {
        $expected_stored_field_name = null;
        if( is_array($field_data) && array_key_exists('field_name', $field_data) ) {
            if( !is_string($field_data['field_name']) || $field_data['field_name']==='' ) {
                return false;
            }
            $expected_stored_field_name = $field_data['field_name'];
        }
        // File carriers resolve through the shared route-contract identity resolver.
        $identity = self::submission_route_contract_identity($route_name, $file_routes, $expected_stored_field_name);
        if( $identity===false || !is_array($field_data) ) {
            return false;
        }
        return $identity['stored_field_name'];
    }

    private static function upload_request_field_identities( $routes, $file_routes ) {
        if( !is_array($routes) || !is_array($file_routes) ) {
            return false;
        }
        $submitted = ( isset($_POST['file_field_map']) && is_array($_POST['file_field_map']) )
            ? wp_unslash($_POST['file_field_map'])
            : array();
        if( !empty(array_diff_key($submitted, $routes)) ) {
            return false;
        }
        $identities = array();
        foreach( $routes as $route_name => $unused ) {
            $expected_stored_field_name = null;
            if( isset($submitted[$route_name]) ) {
                if( !is_string($submitted[$route_name]) || $submitted[$route_name]==='' ) {
                    return false;
                }
                $expected_stored_field_name = $submitted[$route_name];
            }
            // File carriers resolve through the shared route-contract identity resolver.
            $identity = self::submission_route_contract_identity($route_name, $file_routes, $expected_stored_field_name);
            if( $identity===false ) {
                return false;
            }
            $identities[$route_name] = $identity;
        }
        return $identities;
    }

    private static function upload_array_keys_match( $left, $right ) {
        if( !is_array($left) || !is_array($right) || count($left)!==count($right) ) return false;
        return empty(array_diff_key($left, $right)) && empty(array_diff_key($right, $left));
    }

    /**
     * Validate the complete parallel multipart shape before the first file effect.
     */
    private static function upload_files_are_parallel( $files ) {
        if( !is_array($files) ) return false;
        $parts = array('name', 'type', 'tmp_name', 'error', 'size');
        $has_full_path = array_key_exists('full_path', $files);
        if( !empty(array_diff(array_keys($files), array_merge($parts, array('full_path')))) ) return false;
        foreach( $parts as $part ) {
            if( !isset($files[$part]) || !is_array($files[$part]) ) return false;
        }
        if( $has_full_path && ( !is_array($files['full_path']) || !self::upload_array_keys_match($files['name'], $files['full_path']) ) ) return false;
        if( empty($files['name']) ) return false;
        foreach( $parts as $part ) {
            if( !self::upload_array_keys_match($files['name'], $files[$part]) ) return false;
        }
        foreach( $files['name'] as $field_name => $names ) {
            if( !is_string($field_name) || $field_name==='' || !is_array($names) || empty($names) ) return false;
            if( $has_full_path && ( !isset($files['full_path'][$field_name])
                || !self::upload_array_keys_match($names, $files['full_path'][$field_name]) ) ) return false;
            foreach( $parts as $part ) {
                if( !isset($files[$part][$field_name])
                    || !self::upload_array_keys_match($names, $files[$part][$field_name]) ) return false;
            }
            foreach( $names as $key => $name ) {
                if( !is_string($name) || $name==='' ) return false;
                if( !is_string($files['type'][$field_name][$key])
                    || !is_string($files['tmp_name'][$field_name][$key])
                    || !is_numeric($files['error'][$field_name][$key])
                    || !is_numeric($files['size'][$field_name][$key])
                    || !is_finite((float) $files['error'][$field_name][$key])
                    || !is_finite((float) $files['size'][$field_name][$key])
                    || (float) (int) $files['error'][$field_name][$key] !== (float) $files['error'][$field_name][$key]
                    || ( $has_full_path && !is_string($files['full_path'][$field_name][$key]) ) ) return false;
            }
        }
        return true;
    }

    private static function upload_receipt_hash_option_names( $token_hash ) {
        if( !is_string($token_hash) || !preg_match('/^[a-f0-9]{64}$/D', $token_hash) ) return false;
        return array(
            'token_hash' => $token_hash,
            'receipt_option' => '_super_upload_receipt_' . $token_hash,
            'claim_option' => '_super_upload_receipt_claim_' . $token_hash,
        );
    }

    private static function upload_receipt_option_names( $token ) {
        if( !is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token) ) return false;
        return self::upload_receipt_hash_option_names(hash('sha256', $token));
    }

    /**
     * Generate a 256-bit receipt identifier via the shared Super Forms entropy helper.
     */
    private static function generate_upload_token() {
        if( !class_exists('SUPER_Forms') ) {
            return false;
        }
        $token = SUPER_Forms::generate_secure_hex(32);
        return ( is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token)===1 )
            ? $token
            : false;
    }

    /**
     * Upload receipts must survive the supported draft/edit window so an active
     * long-running form cannot silently lose already-uploaded files.
     */
    private static function upload_receipt_ttl() {
        return DAY_IN_SECONDS;
    }
    /**
     * Issue an independent bearer capability. The raw token is returned once and is never stored.
     */
    private static function issue_upload_receipt( $owned ) {
        if( !self::owned_upload_is_current($owned, 0) ) return false;
        $bearer = self::upload_receipt_uses_bearer_binding( $owned['form_id'] );
        $actor = $bearer ? false : self::current_upload_actor_context(true);
        if( !$bearer && $actor===false ) return false;
        for( $attempt=0; $attempt<3; $attempt++ ) {
            $token = self::generate_upload_token();
            if( $token===false ) return false;
            $names = self::upload_receipt_option_names($token);
            if( $names===false ) return false;
            $receipt = array(
                'version' => 1,
                'token_hash' => $names['token_hash'],
                'expires' => time() + self::upload_receipt_ttl(),
                'form_id' => absint($owned['form_id']),
                'field' => $owned['field'],
                'binding' => $bearer ? 'bearer' : 'actor',
                'owned' => $owned,
            );
            if( !$bearer ) {
                $receipt['actor_id'] = $actor['actor_id'];
                $receipt['actor_hash'] = $actor['actor_hash'];
            }
            if( add_option($names['receipt_option'], $receipt, '', 'no') ) {
                wp_schedule_single_event(
                    $receipt['expires'] + 1,
                    'super_cleanup_upload_receipt',
                    array($names['token_hash'])
                );
                return $token;
            }
        }
        return false;
    }

    private static function upload_receipt_matches_hash( $receipt, $token_hash ) {
        return is_array($receipt)
            && isset($receipt['version']) && $receipt['version']===1
            && isset($receipt['token_hash']) && is_string($receipt['token_hash'])
            && hash_equals($token_hash, $receipt['token_hash'])
            && isset($receipt['expires']) && is_int($receipt['expires'])
            && isset($receipt['owned']) && is_array($receipt['owned']);
    }

    private static function unschedule_upload_receipt_cleanup( $token_hash, $expires ) {
        if( !is_string($token_hash) || !preg_match('/^[a-f0-9]{64}$/D', $token_hash)
            || !is_int($expires) ) return false;
        return wp_unschedule_event(
            $expires + 1,
            'super_cleanup_upload_receipt',
            array($token_hash)
        );
    }

    /**
     * Delete only an upload-receipt option and confirm the durable row is absent.
     *
     * delete_option() may evict its object-cache entry even when the database DELETE
     * fails, so get_option() is not a durable deletion readback.
     */
    private static function delete_upload_receipt_option( $option_name ) {
        if( !is_string($option_name)
            || !preg_match('/^_super_upload_receipt_(?:claim_)?[a-f0-9]{64}$/D', $option_name) ) return false;
        global $wpdb;
        if( !isset($wpdb->options) || !is_string($wpdb->options) ) return false;
        delete_option($option_name);
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name = %s",
                $option_name
            )
        );
        return $count!==null && (string) $count==='0';
    }


    private static function discard_upload_receipt( $token ) {
        $names = self::upload_receipt_option_names($token);
        if( $names===false ) return false;
        $receipt = get_option($names['receipt_option'], false);
        if( !self::upload_receipt_matches_hash($receipt, $names['token_hash']) ) return false;
        if( !self::delete_upload_receipt_option($names['receipt_option']) ) return false;
        self::delete_upload_receipt_option($names['claim_option']);
        self::unschedule_upload_receipt_cleanup($names['token_hash'], $receipt['expires']);
        return true;
    }



    public static function cleanup_expired_upload_receipt( $token_hash ) {
        $names = self::upload_receipt_hash_option_names($token_hash);
        if( $names===false ) return false;
        $receipt = get_option($names['receipt_option'], false);
        if( !self::upload_receipt_matches_hash($receipt, $token_hash)
            || $receipt['expires']>time() ) return false;

        $claim_id = self::generate_upload_token();
        if( $claim_id===false ) return false;
        $cleanup_claim = array(
            'version' => 1,
            'claim_id' => $claim_id,
            'token_hash' => $token_hash,
            'expires' => $receipt['expires'],
            'purpose' => 'cleanup',
            'claimed_at' => time(),
        );
        $descriptor = array(
            'token_hash' => $token_hash,
            'receipt_option' => $names['receipt_option'],
            'claim_option' => $names['claim_option'],
            'expires' => $receipt['expires'],
            'owned' => $receipt['owned'],
        );
        if( get_option($names['claim_option'], false)!==false ) {
            if( !self::reclaim_stale_upload_receipt_claim($descriptor, $receipt, 'cleanup')
                || get_option($names['claim_option'], false)!==false ) {
                return false;
            }
        }
        if( !add_option($names['claim_option'], $cleanup_claim, '', 'no') ) {
            return false;
        }
        $current = get_option($names['receipt_option'], false);
        if( $current!==$receipt
            || get_option($names['claim_option'], false)!==$cleanup_claim
            || !self::upload_receipt_matches_hash($current, $token_hash)
            || $current['expires']>time() ) {
            if( get_option($names['claim_option'], false)===$cleanup_claim ) {
                self::delete_upload_receipt_option($names['claim_option']);
            }
            return false;
        }
        if( self::owned_upload_is_current($current['owned'], 0)
            && !self::cleanup_owned_uploads(array($current['owned'])) ) {
            if( get_option($names['claim_option'], false)===$cleanup_claim ) {
                self::delete_upload_receipt_option($names['claim_option']);
            }
            return false;
        }
        if( get_option($names['claim_option'], false)!==$cleanup_claim
            || get_option($names['receipt_option'], false)!==$current
            || !self::delete_upload_receipt_option($names['receipt_option']) ) {
            if( get_option($names['claim_option'], false)===$cleanup_claim ) {
                self::delete_upload_receipt_option($names['claim_option']);
            }
            return false;
        }
        self::unschedule_upload_receipt_cleanup($token_hash, $current['expires']);
        if( get_option($names['claim_option'], false)===$cleanup_claim ) {
            self::delete_upload_receipt_option($names['claim_option']);
        }
        return true;
    }

    public static function cleanup_expired_upload_receipts_fallback() {
        if( get_transient('_super_upload_receipt_cleanup_sweep') ) return 0;
        set_transient('_super_upload_receipt_cleanup_sweep', 1, MINUTE_IN_SECONDS);
        global $wpdb;
        if( !isset($wpdb->options) || !is_string($wpdb->options) ) return 0;

        $prefix = '_super_upload_receipt_';
        $cursor = max(0, (int) get_option('_super_upload_receipt_cleanup_cursor', 0));
        $like = $wpdb->esc_like($prefix) . '%';
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_id, option_name FROM {$wpdb->options}
                WHERE option_id > %d AND option_name LIKE %s AND CHAR_LENGTH(option_name) = %d
                ORDER BY option_id ASC LIMIT 20",
                $cursor,
                $like,
                strlen($prefix) + 64
            ),
            ARRAY_A
        );
        if( !is_array($rows) || empty($rows) ) {
            if( $cursor!==0 ) update_option('_super_upload_receipt_cleanup_cursor', 0, false);
            return 0;
        }

        $scanned = 0;
        foreach( $rows as $row ) {
            if( !isset($row['option_id'], $row['option_name']) ) continue;
            $cursor = max($cursor, (int) $row['option_id']);
            $token_hash = substr((string) $row['option_name'], strlen($prefix));
            if( preg_match('/^[a-f0-9]{64}$/D', $token_hash) ) {
                self::cleanup_expired_upload_receipt($token_hash);
            }
            $scanned++;
        }
        update_option('_super_upload_receipt_cleanup_cursor', $cursor, false);
        return $scanned;
    }

    /**
     * Inspect a capability without claiming or consuming it.
     */
    private static function inspect_upload_receipt( $token, $form_id, $field_name ) {
        if( !is_string($field_name) || $field_name==='' ) return false;
        $names = self::upload_receipt_option_names($token);
        if( $names===false ) return false;
        $receipt = get_option($names['receipt_option'], false);
        if( !is_array($receipt)
            || !isset($receipt['version']) || $receipt['version']!==1
            || !isset($receipt['token_hash']) || !is_string($receipt['token_hash'])
            || !hash_equals($names['token_hash'], $receipt['token_hash'])
            || !isset($receipt['expires']) || !is_int($receipt['expires']) || $receipt['expires']<=time()
            || empty($receipt['form_id']) || absint($receipt['form_id'])!==absint($form_id)
            || !isset($receipt['field']) || !is_string($receipt['field']) || $receipt['field']!==$field_name
            || !self::upload_receipt_actor_matches_current($receipt)
            || !isset($receipt['owned']) || !is_array($receipt['owned'])
            || empty($receipt['owned']['form_id']) || absint($receipt['owned']['form_id'])!==absint($form_id)
            || !isset($receipt['owned']['field']) || $receipt['owned']['field']!==$field_name
            || !self::owned_upload_is_current($receipt['owned'], 0) ) return false;
        return array(
            'token_hash' => $names['token_hash'],
            'receipt_option' => $names['receipt_option'],
            'claim_option' => $names['claim_option'],
            'expires' => (int) $receipt['expires'],
            'owned' => $receipt['owned'],
        );
    }

    /**
     * Release only claim records created by this request.
     */
    private static function rollback_upload_receipt_claims( $claims ) {
        if( !is_array($claims) ) return false;
        $success = true;
        foreach( array_reverse($claims) as $claim ) {
            if( !is_array($claim) || empty($claim['claim_option']) || !is_string($claim['claim_option']) || !isset($claim['claim_value']) ) {
                $success = false;
                continue;
            }
            $current = get_option($claim['claim_option'], false);
            if( $current===false ) continue;
            if( $current!==$claim['claim_value'] ) {
                $success = false;
                continue;
            }
            $receipt = ( !empty($claim['receipt_option']) && is_string($claim['receipt_option']) )
                ? get_option($claim['receipt_option'], false)
                : false;
            if( $receipt!==false && !self::upload_receipt_actor_matches_current($receipt) ) {
                $success = false;
                continue;
            }
            if( !self::delete_upload_receipt_option($claim['claim_option']) ) $success = false;
        }
        return $success;
    }

    /**
     * Reclaim only a request-local claim that is provably abandoned.  The receipt remains
     * authoritative: its exact descriptor is read again both before and after removal,
     * so a normal concurrent request can never be displaced.
     */
    private static function reclaim_stale_upload_receipt_claim( $descriptor, $receipt, $purpose='submission' ) {
        if( !is_array($descriptor) || !is_array($receipt)
            || empty($descriptor['claim_option']) || !is_string($descriptor['claim_option'])
            || empty($descriptor['token_hash']) || !is_string($descriptor['token_hash'])
            || !isset($descriptor['expires']) || !is_int($descriptor['expires'])
            || !is_string($purpose) || !in_array($purpose, array('submission', 'cleanup'), true) ) return false;
        $claim = get_option($descriptor['claim_option'], false);
        $now = time();
        if( !is_array($claim)
            || !isset($claim['version'], $claim['purpose'], $claim['token_hash'], $claim['expires'], $claim['claimed_at'])
            || $claim['version']!==1
            || !is_string($claim['purpose'])
            || !in_array($claim['purpose'], array('submission', 'cleanup'), true)
            || !is_string($claim['token_hash'])
            || !hash_equals($descriptor['token_hash'], $claim['token_hash'])
            || !is_int($claim['expires']) || $claim['expires']!==$descriptor['expires']
            || !is_int($claim['claimed_at'])
            || $claim['claimed_at'] > $now - 2 * MINUTE_IN_SECONDS
            || !self::upload_receipt_matches_hash($receipt, $descriptor['token_hash'])
            || !isset($receipt['expires'], $receipt['owned'])
            || $receipt['expires']!==$descriptor['expires']
            || $receipt['owned']!==$descriptor['owned'] ) return false;
        if( $purpose==='submission' ) {
            if( $claim['purpose']!=='submission' || $receipt['expires']<=$now ) {
                return false;
            }
        }else{
            if( $receipt['expires']>$now ) {
                return false;
            }
        }
        if( get_option($descriptor['claim_option'], false)!==$claim
            || get_option($descriptor['receipt_option'], false)!==$receipt ) return false;
        return self::delete_upload_receipt_option($descriptor['claim_option']);
    }

    /**
     * Acquire every distinct token claim before any token is consumed.
     */
    private static function claim_upload_receipts( $inspected ) {
        if( !is_array($inspected) ) return false;
        $claims = array();
        $seen = array();
        foreach( $inspected as $descriptor ) {
            if( !is_array($descriptor)
                || empty($descriptor['token_hash']) || !is_string($descriptor['token_hash'])
                || !preg_match('/^[a-f0-9]{64}$/D', $descriptor['token_hash'])
                || isset($seen[$descriptor['token_hash']])
                || empty($descriptor['receipt_option']) || !is_string($descriptor['receipt_option'])
                || empty($descriptor['claim_option']) || !is_string($descriptor['claim_option'])
                || empty($descriptor['expires']) || !is_int($descriptor['expires'])
                || empty($descriptor['owned']) || !is_array($descriptor['owned']) ) {
                self::rollback_upload_receipt_claims($claims);
                return false;
            }
            $seen[$descriptor['token_hash']] = true;
            $current = get_option($descriptor['receipt_option'], false);
            if( !is_array($current)
                || !isset($current['version']) || $current['version']!==1
                || !isset($current['token_hash']) || !is_string($current['token_hash'])
                || !hash_equals($descriptor['token_hash'], $current['token_hash'])
                || !isset($current['expires']) || !is_int($current['expires']) || $current['expires']!==$descriptor['expires']
                || !self::upload_receipt_actor_matches_current($current)
                || !isset($current['owned']) || $current['owned']!==$descriptor['owned']
                || $current['expires']<=time()
                || !self::owned_upload_is_current($current['owned'], 0) ) {
                self::rollback_upload_receipt_claims($claims);
                return false;
            }
            $claim_id = self::generate_upload_token();
            if( $claim_id===false ) {
                self::rollback_upload_receipt_claims($claims);
                return false;
            }
            $descriptor['claim_value'] = array(
                'version' => 1,
                'claim_id' => $claim_id,
                'token_hash' => $descriptor['token_hash'],
                'expires' => $descriptor['expires'],
                'purpose' => 'submission',
                'claimed_at' => time(),
            );
            if( !add_option($descriptor['claim_option'], $descriptor['claim_value'], '', 'no')
                && !( self::reclaim_stale_upload_receipt_claim($descriptor, $current)
                    && add_option($descriptor['claim_option'], $descriptor['claim_value'], '', 'no') ) ) {
                self::rollback_upload_receipt_claims($claims);
                return false;
            }
            $current = get_option($descriptor['receipt_option'], false);
            if( !is_array($current)
                || !isset($current['version']) || $current['version']!==1
                || !isset($current['token_hash']) || !is_string($current['token_hash'])
                || !hash_equals($descriptor['token_hash'], $current['token_hash'])
                || !isset($current['expires']) || !is_int($current['expires']) || $current['expires']!==$descriptor['expires']
                || !self::upload_receipt_actor_matches_current($current)
                || !isset($current['owned']) || $current['owned']!==$descriptor['owned']
                || $current['expires']<=time()
                || !self::owned_upload_is_current($current['owned'], 0) ) {
                $claims[] = $descriptor;
                self::rollback_upload_receipt_claims($claims);
                return false;
            }
            $claims[] = $descriptor;
        }
        return $claims;
    }

    /**
     * Consume a fully acquired claim set and return its ordered owned records.
     */
    private static function consume_upload_receipt_claims( $claims ) {
        if( !is_array($claims) ) return false;
        foreach( $claims as $claim ) {
            if( !is_array($claim)
                || empty($claim['token_hash']) || !is_string($claim['token_hash'])
                || empty($claim['receipt_option']) || !is_string($claim['receipt_option'])
                || empty($claim['claim_option']) || !is_string($claim['claim_option'])
                || !isset($claim['claim_value'])
                || get_option($claim['claim_option'], false)!==$claim['claim_value'] ) {
                self::rollback_upload_receipt_claims($claims);
                return false;
            }
            $current = get_option($claim['receipt_option'], false);
            if( !is_array($current)
                || !isset($current['version']) || $current['version']!==1
                || !isset($current['token_hash']) || !is_string($current['token_hash'])
                || !hash_equals($claim['token_hash'], $current['token_hash'])
                || !isset($current['expires']) || !is_int($current['expires']) || $current['expires']!==$claim['expires']
                || !self::upload_receipt_actor_matches_current($current)
                || !isset($current['owned']) || $current['owned']!==$claim['owned']
                || $current['expires']<=time()
                || !self::owned_upload_is_current($current['owned'], 0) ) {
                self::rollback_upload_receipt_claims($claims);
                return false;
            }
        }
        $owned_files = array();
        foreach( $claims as $claim ) {
            $owned_files[] = $claim['owned'];
        }
        foreach( $claims as $claim ) {
            if( !self::delete_upload_receipt_option($claim['receipt_option']) ) {
                // A partial consume must never leave a bearer receipt or its file reusable.
                foreach( $claims as $cleanup_claim ) {
                    if( self::delete_upload_receipt_option($cleanup_claim['receipt_option']) ) {
                        self::unschedule_upload_receipt_cleanup(
                            $cleanup_claim['token_hash'],
                            $cleanup_claim['expires']
                        );
                    }
                }
                self::rollback_upload_receipt_claims($claims);
                self::cleanup_owned_uploads($owned_files);
                return false;
            }
            self::unschedule_upload_receipt_cleanup($claim['token_hash'], $claim['expires']);
        }
        self::rollback_upload_receipt_claims($claims);
        return $owned_files;
    }

    private static function owned_upload_public_record( $owned, $route_name=null ) {
        if( !is_string($route_name) || $route_name==='' ) {
            $route_name = $owned['field'];
        }
        return array(
            'value' => $owned['basename'],
            'name' => $route_name,
            'type' => $owned['mime'],
            'url' => $owned['url'],
            'size' => $owned['size'],
        );
    }

    public static function owned_custom_upload_proof( $owned ) {
        if( !is_array($owned)
            || !isset($owned['storage'])
            || $owned['storage']!=='custom' ) return false;
        $fields = array(
            'version', 'form_id', 'field', 'file', 'custom_path',
            'allowed_root', 'mime', 'url', 'size', 'basename', 'legacy_subdir',
        );
        $payload = array();
        foreach( $fields as $field ) {
            if( !array_key_exists($field, $owned) || !is_scalar($owned[$field]) ) return false;
            $payload[$field] = (string) $owned[$field];
        }
        $encoded = wp_json_encode($payload, JSON_UNESCAPED_SLASHES);
        if( !is_string($encoded) ) return false;
        return hash_hmac('sha256', $encoded, wp_salt('auth'));
    }

    private static function owned_upload_file_record( $owned, $route_name=null ) {
        $file = self::owned_upload_public_record($owned, $route_name);
        $file['_super_file_authority'] = 'owned';
        if( $owned['storage']==='attachment' ) {
            $file['attachment'] = $owned['attachment'];
        } else {
            $file['path'] = $owned['custom_path'];
            $file['subdir'] = $owned['legacy_subdir'];
            $file['_super_file_proof'] = self::owned_custom_upload_proof($owned);
        }
        return $file;
    }

    private static function verified_existing_upload_mime( $filename, $file_element ) {
        if( !is_string($filename) || $filename==='' || is_link($filename) ) return false;
        $filename = realpath($filename);
        if( $filename===false || !is_file($filename) ) return false;
        $basename = basename($filename);
        $parts = explode('.', strtolower($basename));
        if( count($parts)<2 ) return false;
        array_shift($parts);
        $dangerous = self::dangerous_upload_extensions();
        foreach( $parts as $part ) {
            if( $part==='' || isset($dangerous[sanitize_key($part)]) ) return false;
        }
        $extension = end($parts);
        $allowed = self::allowed_file_mime_types($file_element);
        if( !isset($allowed[$extension]) ) return false;
        $verified = wp_check_filetype_and_ext($filename, $basename, $allowed);
        if( empty($verified['ext']) || empty($verified['type'])
            || $verified['ext']!==$extension || $verified['type']!==$allowed[$extension] ) return false;
        return $verified['type'];
    }

    private static function retained_entry_file_selector( $file ) {
        if( !is_array($file) || !isset($file['value'], $file['url'])
            || !is_string($file['value']) || !is_string($file['url']) ) return false;
        $value = wp_unslash($file['value']);
        $url = wp_unslash($file['url']);
        if( $value==='' || $url==='' || strpos($value, "\0")!==false || strpos($url, "\0")!==false ) return false;
        return hash('sha256', $value . "\0" . $url);
    }

    /**
     * Rebuild one stored entry file without accepting client path, attachment, or MIME authority.
     */
    private static function rebuild_retained_entry_file( $stored, $entry_id, $form_id, $field_name, $file_element, $settings, $source_key=null, $stored_field_name=null, &$owned=null ) {
        $owned = false;
        if( $stored_field_name===null ) {
            $stored_field_name = $field_name;
        }
        if( !is_array($stored) ) return false;
        $attachment_id = isset($stored['attachment']) ? absint($stored['attachment']) : 0;
        if( !$attachment_id ) {
            $stored_value = ( isset($stored['value']) && is_string($stored['value']) ) ? $stored['value'] : '';
            $stored_name = ( isset($stored['name']) && is_string($stored['name']) ) ? $stored['name'] : $field_name;
            $stored_type = ( isset($stored['type']) && is_string($stored['type']) ) ? $stored['type'] : '';
            $stored_url = ( isset($stored['url']) && is_string($stored['url']) ) ? $stored['url'] : '';
            $stored_path = ( isset($stored['path']) && is_string($stored['path']) ) ? wp_normalize_path($stored['path']) : '';
            $stored_subdir = ( isset($stored['subdir']) && is_string($stored['subdir']) ) ? $stored['subdir'] : '';
            $stored_authority = ( isset($stored['_super_file_authority']) && is_string($stored['_super_file_authority']) ) ? $stored['_super_file_authority'] : '';
            $stored_size = ( isset($stored['size']) && is_numeric($stored['size']) && (int) $stored['size'] >= 0 ) ? (int) $stored['size'] : null;
            $stored_proof = isset($stored['_super_file_proof']) && is_string($stored['_super_file_proof'])
                ? $stored['_super_file_proof']
                : '';
            if( $stored_value===''
                || $stored_url===''
                || $stored_subdir===''
                || isset($stored['attachment'])
                || isset($stored['upload_token'])
                || isset($stored['retention_token'])
                || $stored_name!==$field_name
                || ($stored_authority!=='' && !in_array($stored_authority, array('owned', 'retained'), true))
                || ($stored_proof!=='' && preg_match('/^[a-f0-9]{64}$/D', $stored_proof)!==1)
                || strpos($stored_subdir, "\0")!==false
                || strpos($stored_subdir, '\\')!==false
                || ($stored_path!=='' && ( basename($stored_path)!==$stored_value || is_link($stored_path) )) ) {
                return false;
            }
            $allowed = self::allowed_file_mime_types($file_element);
            $proof_matches = array();
            $legacy_matches = array();
            $candidates = array();
            if( $stored_path!=='' ) {
                $candidates = SUPER_Forms::resolve_stored_owned_upload_candidates($stored_path, $stored_subdir);
            }
            if( empty($candidates) ) {
                $candidates = SUPER_Forms::resolve_stored_owned_upload_candidates_from_subdir($stored_subdir, $stored_value);
            }
            foreach( $candidates as $candidate ) {
                if( !is_array($candidate)
                    || empty($candidate['file']) || !is_string($candidate['file'])
                    || empty($candidate['root']) || !is_string($candidate['root'])
                    || empty($candidate['route_prefix']) || !is_string($candidate['route_prefix'])
                    || empty($candidate['relative_path']) || !is_string($candidate['relative_path']) ) {
                    continue;
                }
                $basename = basename($candidate['file']);
                if( $basename!==$stored_value ) {
                    continue;
                }
                $verified = wp_check_filetype_and_ext($candidate['file'], $basename, $allowed);
                if( empty($verified['ext']) || empty($verified['type'])
                    || !isset($allowed[$verified['ext']])
                    || $allowed[$verified['ext']]!==$verified['type']
                    || ($stored_type!=='' && $stored_type!==$verified['type']) ) {
                    continue;
                }
                $size = filesize($candidate['file']);
                if( !is_int($size) || $size<0 || ($stored_size!==null && $size!==$stored_size) ) {
                    continue;
                }
                $canonical_subdir = '/' . ltrim(
                    trailingslashit(str_replace('__/', '../', $candidate['route_prefix'])) . $candidate['relative_path'],
                    '/'
                );
                if( !in_array($stored_subdir, array($canonical_subdir, ltrim($canonical_subdir, '/')), true) ) {
                    continue;
                }
                $canonical_url = trailingslashit( get_option('siteurl') ) . 'sfgtfi/' . ltrim(
                    trailingslashit($candidate['route_prefix']) . $candidate['relative_path'],
                    '/'
                );
                if( $stored_url!==$canonical_url ) {
                    continue;
                }
                if( $stored_path!=='' && wp_normalize_path($candidate['file'])!==$stored_path ) {
                    continue;
                }
                $candidate_owned = self::build_owned_upload(
                    $form_id,
                    $stored_field_name,
                    $candidate['file'],
                    $verified['type'],
                    $canonical_url,
                    0,
                    $candidate['root'],
                    $size,
                    $canonical_subdir
                );
                if( $candidate_owned===false || !self::owned_upload_is_current($candidate_owned) ) {
                    continue;
                }
                $record = self::owned_upload_file_record($candidate_owned, $field_name);
                if( !is_array($record)
                    || !isset($record['_super_file_proof'])
                    || !is_string($record['_super_file_proof'])
                    || $record['name']!==$field_name
                    || $record['value']!==$stored_value
                    || $record['url']!==$stored_url
                    || ($stored_type!=='' && $record['type']!==$stored_type)
                    || ($stored_size!==null && (int)$record['size']!==$stored_size)
                    || ($stored_path!=='' && wp_normalize_path($record['path'])!==$stored_path) ) {
                    continue;
                }
                if( $stored_proof!=='' && hash_equals($stored_proof, $record['_super_file_proof']) ) {
                    $candidate_owned['legacy_entry_id'] = absint($entry_id);
                    $candidate_owned['legacy_source_field'] = $field_name;
                    $candidate_owned['legacy_source_key'] = $source_key;
                    $candidate_owned['cleanup_parent'] = absint($entry_id);
                    $candidate_owned['cleanup_authority'] = true;
                    $proof_matches[] = array(
                        'owned' => $candidate_owned,
                        'record' => $record,
                    );
                    continue;
                }
                $record['_super_file_authority'] = 'retained';
                $legacy_matches[] = array(
                    'legacy_source_key' => $source_key,
                    'cleanup_authority' => false,
                    'record' => $record,
                );
            }
            if( count($proof_matches)===1 ) {
                $owned = $proof_matches[0]['owned'];
                $record = $proof_matches[0]['record'];
                $record['_super_file_authority'] = 'retained';
                return $record;
            }
            if( count($proof_matches)>1 || count($legacy_matches)!==1 ) {
                $owned = false;
                return false;
            }
            $owned = array(
                'legacy_source_key' => $legacy_matches[0]['legacy_source_key'],
                'cleanup_authority' => false,
            );
            return $legacy_matches[0]['record'];
        }
        if( $attachment_id ) {
            $filename = get_attached_file($attachment_id);
            if( !is_string($filename) || $filename==='' || is_link($filename) ) return false;
            $filename = realpath($filename);
            if( $filename===false || get_post_type($attachment_id)!=='attachment'
                || wp_get_post_parent_id($attachment_id)!==absint($entry_id)
                || !get_post_meta($attachment_id, 'super-forms-form-upload-file', true) ) return false;
            $stored_form_id = get_post_meta($attachment_id, '_super_forms_upload_form_id', true);
            $stored_field = get_post_meta($attachment_id, '_super_forms_upload_field', true);
            if( $stored_form_id!=='' && absint($stored_form_id)!==absint($form_id) ) return false;
            if( $stored_field!=='' && (string) $stored_field!==$stored_field_name ) return false;
            $mime = self::verified_existing_upload_mime($filename, $file_element);
            $url = wp_get_attachment_url($attachment_id);
            $resolved = SUPER_Forms::resolve_owned_upload_file($filename, $settings);
            $size = filesize($filename);
            if( $mime===false || !is_int($size) || $size<0 || get_post_mime_type($attachment_id)!==$mime
                || !is_string($url) || $url==='' || $resolved===false
                || empty($resolved['root']) || !is_string($resolved['root']) ) return false;
            $owned = self::build_owned_upload(
                $form_id,
                $stored_field_name,
                $filename,
                $mime,
                $url,
                $attachment_id,
                $resolved['root'],
                $size
            );
            if( $owned===false ) return false;
            $owned['legacy_entry_id'] = absint($entry_id);
            $owned['legacy_source_field'] = $field_name;
            $owned['legacy_source_key'] = $source_key;
            $owned['cleanup_parent'] = absint($entry_id);
            $record = self::owned_upload_file_record($owned, $field_name);
            $record['_super_file_authority'] = 'retained';
            return $record;
        }
    }

    private static function resolve_retained_entry_file( $client_file, $entry_id, $field_name, $stored_field_name=null, &$matched_owned=null ) {
        $matched_owned = false;
        if( $stored_field_name===null ) {
            $stored_field_name = $field_name;
        }
        $entry_id = absint($entry_id);
        $entry = $entry_id ? get_post($entry_id) : false;
        if( !($entry instanceof WP_Post) || $entry->post_type!=='super_contact_entry'
            || !in_array($entry->post_status, array('publish', 'super_unread', 'super_read'), true) ) return false;
        // The Listing host authorizes access, but the entry's parent owns its file
        // policy and configured root.  Never reconstruct a retained file under the host.
        $entry_form_id = absint($entry->post_parent);
        if( !$entry_form_id || get_post_type($entry_form_id)!=='super_form' ) return false;
        $entry_elements = SUPER_Common::get_form_elements($entry_form_id);
        $entry_file_element = self::get_file_element($entry_elements, $stored_field_name);
        $entry_settings = SUPER_Common::get_form_settings($entry_form_id);
        if( $entry_file_element===false || !is_array($entry_settings) ) return false;
        $entry_data = SUPER_Data_Access::get_entry_data($entry_id);
        if( !is_array($entry_data) || !isset($entry_data[$field_name])
            || !is_array($entry_data[$field_name])
            || !isset($entry_data[$field_name]['type']) || $entry_data[$field_name]['type']!=='files'
            || !isset($entry_data[$field_name]['files']) || !is_array($entry_data[$field_name]['files']) ) return false;
        $client_selector = self::retained_entry_file_selector($client_file);
        if( $client_selector===false ) return false;

        $matched = false;
        foreach( $entry_data[$field_name]['files'] as $source_key => $stored ) {
            $record_owned = false;
            $record = self::rebuild_retained_entry_file(
                $stored,
                $entry_id,
                $entry_form_id,
                $field_name,
                $entry_file_element,
                $entry_settings,
                $source_key,
                $stored_field_name,
                $record_owned
            );
            if( $record===false ) continue;
            $selectors = array(
                self::retained_entry_file_selector($record),
                self::retained_entry_file_selector($stored),
            );
            if( !in_array($client_selector, $selectors, true) ) continue;
            if( $matched!==false ) return false;
            $matched = $record;
            $matched_owned = $record_owned;
        }
        return $matched;
    }


    /**
     * Inspect every client carrier and rebuild normal file data from immutable
     * upload receipts or exact server-stored contact-entry records.
     * No receipt is claimed or consumed here.
     */
    private static function resolve_submission_files( $data, $form_id, $form_elements, $entry_id ) {
        $inspected = array();
        $retained_seen = array();
        $retained_owned_files = array();
        $file_routes = array();
        self::collect_submission_file_routes($form_elements, $file_routes);
        foreach( $data as $field_name => $field_data ) {
            if( !is_array($field_data) || !isset($field_data['type']) || $field_data['type']!=='files' ) continue;
            if( !isset($field_data['files']) || !is_array($field_data['files']) ) {
                return new WP_Error('invalid_upload', esc_html__( 'Invalid file upload.', 'super-forms' ));
            }
            if( $field_name==='_generated_pdf_file' ) {
                if( count($field_data['files'])!==1 || !is_array(reset($field_data['files'])) ) {
                    return new WP_Error('invalid_upload', esc_html__( 'Invalid file upload.', 'super-forms' ));
                }
                $pdf = reset($field_data['files']);
                if( empty($pdf['datauristring']) || !is_string($pdf['datauristring']) ) {
                    return new WP_Error('invalid_upload', esc_html__( 'Invalid file upload.', 'super-forms' ));
                }
                $field_data['files'] = array(array(
                    'label' => isset($pdf['label']) && is_scalar($pdf['label']) ? (string) $pdf['label'] : '',
                    'name' => isset($pdf['name']) && is_scalar($pdf['name']) ? (string) $pdf['name'] : '',
                    'value' => isset($pdf['value']) && is_scalar($pdf['value']) ? (string) $pdf['value'] : '',
                    'datauristring' => $pdf['datauristring'],
                ));
                $data[$field_name] = $field_data;
                continue;
            }
            $stored_field_name = self::submission_file_stored_field_name($field_name, $field_data, $file_routes);
            $file_element = $stored_field_name!==false ? self::get_file_element($form_elements, $stored_field_name) : false;
            if( $file_element===false ) {
                return new WP_Error('invalid_upload_field', esc_html__( 'Invalid file upload field.', 'super-forms' ));
            }
            $files = array();
            $resolved_files = array();
            $reserved_source_keys = array();
            foreach( $field_data['files'] as $file ) {
                if( !is_array($file) ) {
                    return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                }
                if( array_key_exists('upload_token', $file) ) {
                    if( isset($file['retention_token'])
                        || empty($file['upload_token']) || !is_string($file['upload_token']) ) {
                        return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                    }
                    $descriptor = self::inspect_upload_receipt($file['upload_token'], $form_id, $stored_field_name);
                    if( $descriptor===false
                        || ( isset($descriptor['owned']['route_name'])
                            && ( !is_string($descriptor['owned']['route_name'])
                                || $descriptor['owned']['route_name']!==$field_name ) ) ) {
                        return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                    }
                    $inspected[] = $descriptor;
                    $resolved_files[] = array(
                        'record' => self::owned_upload_file_record($descriptor['owned'], $field_name),
                        'source_key' => null,
                    );
                    continue;
                }
                if( !isset($file['retention_token']) || $file['retention_token']!=='entry' ) {
                    return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                }
                $retained_owned = false;
                $retained = self::resolve_retained_entry_file(
                    $file,
                    $entry_id,
                    $field_name,
                    $stored_field_name,
                    $retained_owned
                );
                if( $retained===false ) {
                    return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                }
                $identity = !empty($retained['attachment'])
                    ? 'attachment:' . absint($retained['attachment'])
                    : 'custom:' . hash('sha256', (string) $retained['subdir']);
                if( isset($retained_seen[$identity]) ) {
                    return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                }
                $retained_seen[$identity] = true;
                if( $retained_owned===false || !is_array($retained_owned) ) {
                    return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                }
                $source_key = isset($retained_owned['legacy_source_key'])
                    ? $retained_owned['legacy_source_key']
                    : null;
                if( (!is_int($source_key) && !is_string($source_key))
                    || array_key_exists($source_key, $reserved_source_keys) ) {
                    return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                }
                $reserved_source_keys[$source_key] = true;
                if( !empty($retained_owned['cleanup_authority']) ) {
                    $retained_owned_files[] = $retained_owned;
                }
                $resolved_files[] = array(
                    'record' => $retained,
                    'source_key' => $source_key,
                );
            }
            $next_key = 0;
            foreach( $resolved_files as $resolved_file ) {
                $source_key = $resolved_file['source_key'];
                if( $source_key===null ) {
                    while( array_key_exists($next_key, $reserved_source_keys)
                        || array_key_exists($next_key, $files) ) {
                        $next_key++;
                    }
                    $source_key = $next_key++;
                }
                if( array_key_exists($source_key, $files) ) {
                    return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                }
                $files[$source_key] = $resolved_file['record'];
            }
            $field_data['files'] = $files;
            if( $stored_field_name!==$field_name || array_key_exists('field_name', $field_data) ) {
                $field_data['field_name'] = $stored_field_name;
            }else{
                unset($field_data['field_name']);
            }
            $data[$field_name] = $field_data;
        }
        if( isset($data['_super_dynamic_data']) ) {
            if( !is_array($data['_super_dynamic_data']) ) {
                return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
            }
            foreach( $data['_super_dynamic_data'] as $group_name => $rows ) {
                if( !is_array($rows) ) {
                    return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                }
                foreach( $rows as $row_index => $row ) {
                    if( !is_array($row) ) {
                        return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                    }
                    foreach( $row as $row_field_name => $row_field_data ) {
                        if( !is_array($row_field_data) || !isset($row_field_data['type']) || $row_field_data['type']!=='files' ) {
                            continue;
                        }
                        $route_name = self::submission_dynamic_route_name($row_field_name, $row_field_data);
                        if( $route_name===false
                            || !isset($data[$route_name]) || !is_array($data[$route_name])
                            || !isset($data[$route_name]['type']) || $data[$route_name]['type']!=='files' ) {
                            return new WP_Error('invalid_upload_receipt', esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
                        }
                        $data['_super_dynamic_data'][$group_name][$row_index][$row_field_name] = $data[$route_name];
                    }
                }
            }
        }
        return array(
            'data'=>$data,
            'inspected'=>$inspected,
            'retained_owned_files'=>$retained_owned_files,
        );
    }
    private static function collect_presence_enforced_file_routes( $elements, $ctx = null ) {
        if( $ctx===null ) {
            $ctx = array( 'ancestor_locked' => false, 'in_repeater' => false );
        }
        $required = array();
        if( !is_array($elements) ) {
            return $required;
        }
        foreach( $elements as $element ) {
            if( !is_array($element) ) {
                continue;
            }
            $edata = ( isset( $element['data'] ) && is_array( $element['data'] ) ) ? $element['data'] : array();
            $tag = isset( $element['tag'] ) ? $element['tag'] : '';
            $conditional = ( isset( $edata['conditional_action'] ) && $edata['conditional_action']!=='' && $edata['conditional_action']!=='disabled' );
            $repeater = ( isset( $edata['duplicate'] ) && $edata['duplicate']==='enabled' );
            $mobile_hide = ( ( isset( $edata['hide_on_mobile'] ) && $edata['hide_on_mobile']==='true' )
                || ( isset( $edata['hide_on_mobile_window'] ) && $edata['hide_on_mobile_window']==='true' ) );
            $child_locked = ( $ctx['ancestor_locked'] || $conditional || $repeater || $mobile_hide );
            if( !empty($element['inner']) ) {
                foreach( self::collect_presence_enforced_file_routes( $element['inner'], array(
                    'ancestor_locked' => $child_locked,
                    'in_repeater' => ( $ctx['in_repeater'] || $repeater ),
                ) ) as $route_name => $policy ) {
                    $required[$route_name] = $policy;
                }
            }
            if( $tag!=='file' || empty($edata['name']) || !is_string($edata['name']) ) {
                continue;
            }
            $policy = self::get_upload_field_policy($edata);
            if( $policy===false || $policy['min_files']<=0 || $child_locked || $ctx['in_repeater'] ) {
                continue;
            }
            $required[$edata['name']] = $policy;
        }
        return $required;
    }

    private static function submission_files_match_stored_policy( $data, $form_elements ) {
        if( !is_array($data) || !is_array($form_elements) ) {
            return false;
        }
        $file_routes = array();
        self::collect_submission_file_routes($form_elements, $file_routes);
        foreach( self::collect_presence_enforced_file_routes($form_elements) as $field_name => $policy ) {
            if( !isset($data[$field_name]) ) {
                return false;
            }
        }
        foreach( $data as $field_name => $field_data ) {
            if( !is_string($field_name)
                || !is_array($field_data)
                || !isset($field_data['type'])
                || $field_data['type']!=='files'
                || $field_name==='_generated_pdf_file' ) {
                continue;
            }
            if( !isset($field_data['files']) || !is_array($field_data['files']) ) {
                return false;
            }
            $stored_field_name = self::submission_file_stored_field_name($field_name, $field_data, $file_routes);
            $file_element = $stored_field_name!==false ? self::get_file_element($form_elements, $stored_field_name) : false;
            $policy = $file_element!==false ? self::get_upload_field_policy($file_element) : false;
            if( $policy===false ) {
                return false;
            }
            $count = count($field_data['files']);
            if( ($policy['min_files']>0 && $count<$policy['min_files'])
                || ($policy['max_files']>0 && $count>$policy['max_files']) ) {
                return false;
            }
            $aggregate_size = 0;
            foreach( $field_data['files'] as $file ) {
                if( !is_array($file) || !isset($file['size']) || !is_numeric($file['size']) ) {
                    return false;
                }
                $size = (int) $file['size'];
                if( $size<0 || $size>$policy['size_limit']['bytes'] ) {
                    return false;
                }
                $aggregate_size += $size;
                if( $policy['aggregate_bytes']!==false && $aggregate_size>$policy['aggregate_bytes'] ) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function submission_request_post_data_json( $data ) {
        $json = wp_json_encode($data);
        return is_string($json) ? wp_slash($json) : '';
    }

    private static function submission_data_without_files( $data ) {
        if( !is_array($data) ) return array();
        foreach( $data as $field_name => $field_data ) {
            if( is_array($field_data) && isset($field_data['type']) && $field_data['type']==='files' ) {
                unset($data[$field_name]);
                continue;
            }
            if( is_array($field_data) ) {
                $data[$field_name] = self::submission_data_without_files($field_data);
            }
        }
        return $data;
    }
    private static function collect_named_submission_elements( $elements, $name, &$matches=array() ) {
        if( !is_array($elements) || !is_string($name) || $name==='' ) {
            return;
        }
        foreach( $elements as $element ) {
            if( !is_array($element) ) {
                continue;
            }
            if( !empty($element['inner']) ) {
                self::collect_named_submission_elements($element['inner'], $name, $matches);
            }
            $data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
            if( isset($data['name']) && is_string($data['name']) && $data['name']===$name ) {
                $matches[] = $element;
            }
        }
    }

    private static function selection_field_choice_labels( $element, $form_id=0, $selected_values=array() ) {
        if( !is_array($element) ) {
            return false;
        }
        $tag = isset($element['tag']) ? $element['tag'] : '';
        if( !in_array($tag, array('dropdown', 'checkbox', 'radio', 'countries'), true) ) {
            return false;
        }
        $data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
        $schema = self::submission_rendered_choice_schema($tag, $data, $form_id, $selected_values);
        if( $schema===false || !isset($schema['labels']) || !is_array($schema['labels']) ) {
            return false;
        }
        return $schema['labels'];
    }
    private static function stored_submission_element_label( $element ) {
        if( !is_array($element) ) {
            return '';
        }
        $data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
        if( isset($data['email']) && is_scalar($data['email']) ) {
            return (string) $data['email'];
        }
        if( isset($data['label']) && is_scalar($data['label']) ) {
            return (string) $data['label'];
        }
        return '';
    }

    private static function stored_submission_elements_for_route( $field_name, $form_elements ) {
        if( !is_string($field_name) || $field_name==='' || !is_array($form_elements) ) {
            return array();
        }
        $matches = array();
        self::collect_named_submission_elements($form_elements, $field_name, $matches);
        if( count($matches)===0 ) {
            $lookup_name = self::submission_lookup_field_name($field_name);
            if( $lookup_name==='' ) {
                return array();
            }
            self::collect_named_submission_elements($form_elements, $lookup_name, $matches);
        }
        return $matches;
    }

    private static function submission_lookup_field_name( $field_name ) {
        if( !is_string($field_name) || $field_name==='' ) {
            return '';
        }
        $field_name = preg_replace('/_([1-9]\d*)$/', '', $field_name);
        if( preg_match('/\A([A-Za-z0-9_-]+)(?:\[\d+\])+\z/', $field_name, $matches)===1 ) {
            return $matches[1];
        }
        return $field_name;
    }

    private static function submission_route_repeat_ordinal( $field_name ) {
        if( !is_string($field_name) || $field_name==='' ) {
            return 0;
        }
        if( preg_match_all('/\[(\d+)\]/', $field_name, $matches)!==false ) {
            $ordinals = isset($matches[1]) ? $matches[1] : array();
            if( !empty($ordinals) ) {
                return absint(end($ordinals)) + 1;
            }
        }
        $lookup_name = self::submission_lookup_field_name($field_name);
        if( $lookup_name==='' ) {
            return 0;
        }
        if( preg_match('/\A' . preg_quote($lookup_name, '/') . '(?:\[\d+\])*(?:_([1-9]\d*))?\z/', $field_name, $matches)!==1 ) {
            return 0;
        }
        return !empty($matches[1]) ? absint($matches[1]) : 1;
    }

    private static function server_owned_submission_label( $field_name, $form_elements ) {
        $elements = self::stored_submission_elements_for_route($field_name, $form_elements);
        if( empty($elements) ) {
            return null;
        }
        $label = null;
        foreach( $elements as $element ) {
            $candidate = self::stored_submission_element_label($element);
            if( $candidate!=='' && strpos($candidate, '%d')!==false ) {
                $ordinal = self::submission_route_repeat_ordinal($field_name);
                if( $ordinal<1 ) {
                    $ordinal = 1;
                }
                $candidate = str_replace('%d', (string) $ordinal, $candidate);
            }
            if( $label===null ) {
                $label = $candidate;
                continue;
            }
            if( $label!==$candidate ) {
                return null;
            }
        }
        return $label;
    }

    private static function server_owned_selection_values_for_element( $element, $field_name, $field_data, $form_id=0 ) {
        if( !is_array($element) || !is_array($field_data) ) {
            return false;
        }
        $tag = isset($element['tag']) ? $element['tag'] : '';
        if( !in_array($tag, array('dropdown', 'checkbox', 'radio', 'countries'), true) ) {
            return null;
        }
        if( !isset($field_data['value']) || !is_scalar($field_data['value']) ) {
            return false;
        }
        $element_data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
        $choices = self::selection_field_choice_labels($element, $form_id);
        $meta = array(
            'selection_limit' => ( $tag!=='radio' ),
            'selection_joiner' => ( $tag==='checkbox' ? ',' : ', ' ),
            'choice_values' => is_array($choices) ? array_map('strval', array_keys($choices)) : false,
            'allow_context_free_selected_values' => (
                in_array($tag, array('dropdown', 'checkbox', 'radio'), true)
                && isset($element_data['retrieve_method'])
                && is_string($element_data['retrieve_method'])
                && trim($element_data['retrieve_method'])==='product_attribute'
            ),
        );
        if( self::submission_field_allows_saved_choice_fallback($field_name, $form_id) ) {
            $meta['allow_saved_choice_fallback'] = true;
        }
        $selected_values = self::submission_selected_values_from_carrier($field_data, $meta);
        if( $selected_values===false ) {
            return false;
        }
        if( $choices===false ) {
            $choices = array();
        }
        $fallback_value = (string) $field_data['value'];
        $allow_context_free_selected_values = !empty($meta['allow_context_free_selected_values'])
            && self::submission_product_attribute_context_is_missing();
        $labels = array();
        $labels_with_values = array();
        foreach( $selected_values as $selected_value ) {
            if( !isset($choices[$selected_value]) ) {
                if( $allow_context_free_selected_values ) {
                    $labels[] = $selected_value;
                    $labels_with_values[] = $selected_value . ' (' . $selected_value . ')';
                    continue;
                }
                if( !empty($meta['allow_saved_choice_fallback'])
                    && count($selected_values)===1
                    && $selected_value===$fallback_value ) {
                    return array( 'value' => $fallback_value );
                }
                return false;
            }
            $labels[] = $choices[$selected_value];
            $labels_with_values[] = $choices[$selected_value] . ' (' . $selected_value . ')';
        }
        $variants = array(
            'value' => implode(self::submission_choice_value_joiner($meta), $selected_values),
            'option_label' => implode(', ', $labels),
        );
        $admin_mode = isset($element_data['admin_email_value']) && is_string($element_data['admin_email_value'])
            ? $element_data['admin_email_value']
            : 'value';
        if( $admin_mode==='label' ) {
            $variants['admin_value'] = implode(', ', $labels);
        }elseif( $admin_mode==='both' ) {
            $variants['admin_value'] = implode(', ', $labels_with_values);
        }
        $confirm_mode = isset($element_data['confirm_email_value']) && is_string($element_data['confirm_email_value'])
            ? $element_data['confirm_email_value']
            : 'value';
        if( $confirm_mode==='label' ) {
            $variants['confirm_value'] = implode(', ', $labels);
        }elseif( $confirm_mode==='both' ) {
            $variants['confirm_value'] = implode(', ', $labels_with_values);
        }
        $entry_mode = isset($element_data['contact_entry_value']) && is_string($element_data['contact_entry_value'])
            ? $element_data['contact_entry_value']
            : 'value';
        if( $entry_mode==='label' ) {
            $variants['entry_value'] = implode(', ', $labels);
        }elseif( $entry_mode==='both' ) {
            $variants['entry_value'] = implode(', ', $labels_with_values);
        }
        return $variants;
    }

    private static function server_owned_selection_values( $field_name, $field_data, $form_elements, $form_id=0 ) {
        if( !is_string($field_name) || $field_name==='' || !is_array($field_data) || !is_array($form_elements) ) {
            return null;
        }
        $elements = self::stored_submission_elements_for_route($field_name, $form_elements);
        if( empty($elements) ) {
            return null;
        }
        $variants = null;
        $selection_matches = 0;
        foreach( $elements as $element ) {
            $candidate = self::server_owned_selection_values_for_element($element, $field_name, $field_data, $form_id);
            if( $candidate===null ) {
                continue;
            }
            if( $candidate===false ) {
                return false;
            }
            $selection_matches++;
            if( $variants===null ) {
                $variants = $candidate;
                continue;
            }
            if( $variants!==$candidate ) {
                return null;
            }
        }
        return $selection_matches>0 ? $variants : null;
    }
    private static function datepicker_localization_array( $source, $name, $expected_count ) {
        if( !is_string($source) || $source==='' || !is_string($name) || $name==='' ) {
            return false;
        }
        if( preg_match('/' . preg_quote($name, '/') . '\s*:\s*\[(.*?)\]/us', $source, $matches)!==1 ) {
            return false;
        }
        if( preg_match_all('/"((?:\\\\.|[^"\\\\])*)"/u', $matches[1], $values)===false ) {
            return false;
        }
        $items = array();
        foreach( $values[1] as $value ) {
            $items[] = stripcslashes($value);
        }
        return count($items)===$expected_count ? $items : false;
    }
    private static function datepicker_localization_value( $source, $name ) {
        if( !is_string($source) || $source==='' || !is_string($name) || $name==='' ) {
            return false;
        }
        if( preg_match('/' . preg_quote($name, '/') . '\s*:\s*"((?:\\\\.|[^"\\\\])*)"/u', $source, $matches)!==1 ) {
            return false;
        }
        return stripcslashes($matches[1]);
    }
    private static function datepicker_localization_symbols( $localization ) {
        static $cache = array();
        $defaults = array(
            'dayNames' => array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ),
            'dayNamesShort' => array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ),
            'monthNames' => array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ),
            'monthNamesShort' => array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ),
            'dateFormat' => '',
        );
        $key = is_string($localization) ? $localization : '';
        if( isset($cache[$key]) ) {
            return $cache[$key];
        }
        $symbols = $defaults;
        if( $key==='' || preg_match('/^[A-Za-z0-9_-]+$/D', $key)!==1 ) {
            $cache[$key] = $symbols;
            return $symbols;
        }
        $path = untrailingslashit(SUPER_PLUGIN_DIR) . '/assets/js/frontend/datepicker/i18n/datepicker-' . $key . '.js';
        $source = is_readable($path) ? @file_get_contents($path) : false;
        if( is_string($source) && $source!=='' ) {
            foreach( array(
                'dayNames' => 7,
                'dayNamesShort' => 7,
                'monthNames' => 12,
                'monthNamesShort' => 12,
            ) as $name => $expected_count ) {
                $parsed = self::datepicker_localization_array( $source, $name, $expected_count );
                if( is_array($parsed) ) {
                    $symbols[$name] = $parsed;
                }
            }
            $date_format = self::datepicker_localization_value( $source, 'dateFormat' );
            if( is_string($date_format) && $date_format!=='' ) {
                $symbols['dateFormat'] = $date_format;
            }
        }
        $cache[$key] = $symbols;
        return $symbols;
    }
    private static function datepicker_name_candidates( $localized, $fallback, $with_number=false ) {
        $candidates = array();
        foreach( array( $localized, $fallback ) as $values ) {
            if( !is_array($values) ) {
                continue;
            }
            foreach( array_values($values) as $index => $label ) {
                if( !is_string($label) || $label==='' || isset($candidates[$label]) ) {
                    continue;
                }
                $candidate = array( 'label' => $label );
                if( $with_number ) {
                    $candidate['number'] = $index + 1;
                }
                $candidates[$label] = $candidate;
            }
        }
        $candidates = array_values($candidates);
        usort( $candidates, static function( $a, $b ) {
            return strlen($b['label']) - strlen($a['label']);
        } );
        return $candidates;
    }
    private static function match_datepicker_name_token( $value, $offset, $candidates ) {
        if( !is_string($value) || !is_array($candidates) ) {
            return false;
        }
        foreach( $candidates as $candidate ) {
            if( !is_array($candidate) || !isset($candidate['label']) || !is_string($candidate['label']) ) {
                continue;
            }
            $label = $candidate['label'];
            if( substr($value, $offset, strlen($label))!==$label ) {
                continue;
            }
            $match = array( 'offset' => $offset + strlen($label) );
            if( isset($candidate['number']) ) {
                $match['number'] = $candidate['number'];
            }
            return $match;
        }
        return false;
    }
    private static function normalize_short_datepicker_year( $year ) {
        $year = absint($year);
        if( $year>99 ) {
            return $year;
        }
        $current_year = (int) gmdate('Y');
        $current_century = $current_year - ($current_year % 100);
        $cutoff = ($current_year % 100) + 10;
        return $current_century + $year + ( $year<=$cutoff ? 0 : -100 );
    }
    private static function submission_date_timestamp_from_value_and_format( $value, $format, $localization='' ) {
        if( !is_string($value) || $value==='' || !is_string($format) || $format==='' ) {
            return false;
        }
        $symbols = self::datepicker_localization_symbols($localization);
        $long_days = self::datepicker_name_candidates(
            isset($symbols['dayNames']) ? $symbols['dayNames'] : array(),
            array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' )
        );
        $short_days = self::datepicker_name_candidates(
            isset($symbols['dayNamesShort']) ? $symbols['dayNamesShort'] : array(),
            array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' )
        );
        $long_months = self::datepicker_name_candidates(
            isset($symbols['monthNames']) ? $symbols['monthNames'] : array(),
            array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ),
            true
        );
        $short_months = self::datepicker_name_candidates(
            isset($symbols['monthNamesShort']) ? $symbols['monthNamesShort'] : array(),
            array( 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ),
            true
        );
        $read_digits = static function( $source, $offset, $min, $max, $allow_sign=false ) {
            $length = strlen($source);
            if( $offset >= $length ) {
                return false;
            }
            $cursor = $offset;
            if( $allow_sign && isset($source[$cursor]) && $source[$cursor]==='-' ) {
                $cursor++;
            }
            $digits_start = $cursor;
            while( $cursor < $length && ctype_digit($source[$cursor]) && ($cursor - $digits_start) < $max ) {
                $cursor++;
            }
            $digits = $cursor - $digits_start;
            if( $digits < $min ) {
                return false;
            }
            return array(
                'text' => substr($source, $offset, $cursor - $offset),
                'offset' => $cursor,
            );
        };
        $assign = static function( &$parts, $key, $parsed ) {
            if( isset($parts[$key]) && $parts[$key]!==null && $parts[$key]!==$parsed ) {
                return false;
            }
            $parts[$key] = $parsed;
            return true;
        };
        $parts = array(
            'year' => null,
            'month' => null,
            'day' => null,
            'day_of_year' => null,
            'unix_ms' => null,
            'windows_ticks' => null,
        );
        $value_offset = 0;
        $format_length = strlen($format);
        for( $i = 0; $i < $format_length; ) {
            $token = substr($format, $i, 2);
            if( $format[$i]==="'" ) {
                $i++;
                $literal = '';
                while( $i < $format_length ) {
                    if( $format[$i]==="'" ) {
                        if( isset($format[$i + 1]) && $format[$i + 1]==="'" ) {
                            $literal .= "'";
                            $i += 2;
                            continue;
                        }
                        $i++;
                        break;
                    }
                    $literal .= $format[$i];
                    $i++;
                }
                if( substr($value, $value_offset, strlen($literal))!==$literal ) {
                    return false;
                }
                $value_offset += strlen($literal);
                continue;
            }
            if( $token==='DD' ) {
                $matched = self::match_datepicker_name_token( $value, $value_offset, $long_days );
                if( $matched===false ) return false;
                $value_offset = $matched['offset'];
                $i += 2;
                continue;
            }
            if( $token==='MM' ) {
                $matched = self::match_datepicker_name_token( $value, $value_offset, $long_months );
                if( $matched===false || !isset($matched['number']) || !$assign( $parts, 'month', absint($matched['number']) ) ) return false;
                $value_offset = $matched['offset'];
                $i += 2;
                continue;
            }
            if( $token==='dd' ) {
                $matched = $read_digits( $value, $value_offset, 2, 2 );
                if( $matched===false || !$assign( $parts, 'day', absint($matched['text']) ) ) return false;
                $value_offset = $matched['offset'];
                $i += 2;
                continue;
            }
            if( $token==='mm' ) {
                $matched = $read_digits( $value, $value_offset, 2, 2 );
                if( $matched===false || !$assign( $parts, 'month', absint($matched['text']) ) ) return false;
                $value_offset = $matched['offset'];
                $i += 2;
                continue;
            }
            if( $token==='oo' ) {
                $matched = $read_digits( $value, $value_offset, 3, 3 );
                if( $matched===false || !$assign( $parts, 'day_of_year', absint($matched['text']) ) ) return false;
                $value_offset = $matched['offset'];
                $i += 2;
                continue;
            }
            if( $token==='yy' ) {
                $matched = $read_digits( $value, $value_offset, 4, 4 );
                if( $matched===false || !$assign( $parts, 'year', absint($matched['text']) ) ) return false;
                $value_offset = $matched['offset'];
                $i += 2;
                continue;
            }
            if( $format[$i]==='D' ) {
                $matched = self::match_datepicker_name_token( $value, $value_offset, $short_days );
                if( $matched===false ) return false;
                $value_offset = $matched['offset'];
                $i++;
                continue;
            }
            if( $format[$i]==='M' ) {
                $matched = self::match_datepicker_name_token( $value, $value_offset, $short_months );
                if( $matched===false || !isset($matched['number']) || !$assign( $parts, 'month', absint($matched['number']) ) ) return false;
                $value_offset = $matched['offset'];
                $i++;
                continue;
            }
            if( $format[$i]==='d' ) {
                $matched = $read_digits( $value, $value_offset, 1, 2 );
                if( $matched===false || !$assign( $parts, 'day', absint($matched['text']) ) ) return false;
                $value_offset = $matched['offset'];
                $i++;
                continue;
            }
            if( $format[$i]==='m' ) {
                $matched = $read_digits( $value, $value_offset, 1, 2 );
                if( $matched===false || !$assign( $parts, 'month', absint($matched['text']) ) ) return false;
                $value_offset = $matched['offset'];
                $i++;
                continue;
            }
            if( $format[$i]==='o' ) {
                $matched = $read_digits( $value, $value_offset, 1, 3 );
                if( $matched===false || !$assign( $parts, 'day_of_year', absint($matched['text']) ) ) return false;
                $value_offset = $matched['offset'];
                $i++;
                continue;
            }
            if( $format[$i]==='y' ) {
                $matched = $read_digits( $value, $value_offset, 2, 2 );
                if( $matched===false || !$assign( $parts, 'year', self::normalize_short_datepicker_year($matched['text']) ) ) return false;
                $value_offset = $matched['offset'];
                $i++;
                continue;
            }
            if( $format[$i]==='@' ) {
                $matched = $read_digits( $value, $value_offset, 1, 17, true );
                if( $matched===false || !$assign( $parts, 'unix_ms', $matched['text'] ) ) return false;
                $value_offset = $matched['offset'];
                $i++;
                continue;
            }
            if( $format[$i]==='!' ) {
                $matched = $read_digits( $value, $value_offset, 1, 20, true );
                if( $matched===false || !$assign( $parts, 'windows_ticks', $matched['text'] ) ) return false;
                $value_offset = $matched['offset'];
                $i++;
                continue;
            }
            if( substr($value, $value_offset, 1)!==$format[$i] ) {
                return false;
            }
            $value_offset++;
            $i++;
        }
        if( $value_offset!==strlen($value) ) {
            return false;
        }
        if( $parts['unix_ms']!==null ) {
            if( $parts['windows_ticks']!==null || $parts['year']!==null || $parts['month']!==null || $parts['day']!==null || $parts['day_of_year']!==null ) {
                return false;
            }
            $milliseconds = (int) $parts['unix_ms'];
            if( $milliseconds < -62135596800000 || $milliseconds > 253402300799000 ) {
                return false;
            }
            return (string) $milliseconds;
        }
        if( $parts['windows_ticks']!==null ) {
            if( $parts['year']!==null || $parts['month']!==null || $parts['day']!==null || $parts['day_of_year']!==null || PHP_INT_SIZE < 8 ) {
                return false;
            }
            $ticks = (int) $parts['windows_ticks'];
            $milliseconds = (int) floor( ($ticks - 621355968000000000) / 10000 );
            if( $milliseconds < -62135596800000 || $milliseconds > 253402300799000 ) {
                return false;
            }
            return (string) $milliseconds;
        }
        $year = isset($parts['year']) ? absint($parts['year']) : 0;
        if( $year < 1 || $year > 9999 ) {
            return false;
        }
        if( $parts['day_of_year']!==null ) {
            if( $parts['month']!==null || $parts['day']!==null ) {
                return false;
            }
            $day_of_year = absint($parts['day_of_year']);
            $max_day_of_year = ((($year % 4)===0 && ($year % 100)!==0) || (($year % 400)===0)) ? 366 : 365;
            if( $day_of_year < 1 || $day_of_year > $max_day_of_year ) {
                return false;
            }
            $seconds = gmmktime( 0, 0, 0, 1, $day_of_year, $year );
            return ( $seconds===false ) ? false : (string) (((int) $seconds) * 1000);
        }
        $month = isset($parts['month']) ? absint($parts['month']) : 0;
        $day = isset($parts['day']) ? absint($parts['day']) : 0;
        if( !checkdate($month, $day, $year) ) {
            return false;
        }
        $seconds = gmmktime( 0, 0, 0, $month, $day, $year );
        return ( $seconds===false ) ? false : (string) (((int) $seconds) * 1000);
    }
    private static function server_owned_date_timestamp_for_element( $element, $field_data ) {
        if( !is_array($element) || !is_array($field_data) ) {
            return false;
        }
        $tag = isset($element['tag']) && is_string($element['tag']) ? $element['tag'] : '';
        if( $tag!=='date' ) {
            return null;
        }
        if( !isset($field_data['value']) || (!is_scalar($field_data['value']) && $field_data['value']!==null) ) {
            return false;
        }
        $value = (string) $field_data['value'];
        if( $value==='' ) {
            return '';
        }
        $element_data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
        $format = isset($element_data['format']) && is_string($element_data['format']) ? $element_data['format'] : '';
        if( $format==='custom' ) {
            $format = isset($element_data['custom_format']) && is_string($element_data['custom_format'])
                ? $element_data['custom_format']
                : '';
        }
        $localization = isset($element_data['localization']) && is_string($element_data['localization'])
            ? $element_data['localization']
            : '';
        $max_picks = isset($element_data['maxPicks']) ? absint($element_data['maxPicks']) : 0;
        $timestamp = self::submission_date_timestamp_from_value_and_format( $value, $format, $localization );
        if( $timestamp!==false ) {
            return $timestamp;
        }
        if( $max_picks>1 ) {
            return '';
        }
        if( $localization==='' ) {
            return false;
        }
        $symbols = self::datepicker_localization_symbols($localization);
        $localized_format = isset($symbols['dateFormat']) && is_string($symbols['dateFormat']) ? $symbols['dateFormat'] : '';
        if( $localized_format==='' || $localized_format===$format ) {
            return false;
        }
        return self::submission_date_timestamp_from_value_and_format( $value, $localized_format, $localization );
    }
    private static function server_owned_date_timestamp( $field_name, $field_data, $form_elements ) {
        if( !is_string($field_name) || $field_name==='' || !is_array($field_data) || !is_array($form_elements) ) {
            return null;
        }
        $elements = self::stored_submission_elements_for_route($field_name, $form_elements);
        if( empty($elements) ) {
            return null;
        }
        $timestamp = null;
        $date_matches = 0;
        foreach( $elements as $element ) {
            $candidate = self::server_owned_date_timestamp_for_element($element, $field_data);
            if( $candidate===null ) {
                continue;
            }
            $date_matches++;
            if( !is_string($candidate) ) {
                return false;
            }
            if( $timestamp===null ) {
                $timestamp = $candidate;
                continue;
            }
            if( $timestamp!==$candidate ) {
                return false;
            }
        }
        return $date_matches>0 ? $timestamp : null;
    }
    private static function rebuild_selection_entry_values( $data, $form_elements, $form_id=0 ) {
        if( !is_array($data) || !is_array($form_elements) ) {
            return false;
        }
        foreach( $data as $field_name => $field_data ) {
            if( !is_array($field_data) ) {
                continue;
            }
            if( !is_string($field_name) ) {
                $data[$field_name] = self::rebuild_selection_entry_values($field_data, $form_elements, $form_id);
                if( $data[$field_name]===false ) {
                    return false;
                }
                continue;
            }
            if( isset($field_data['type']) && is_string($field_data['type']) ) {
                $matching_elements = self::stored_submission_elements_for_route($field_name, $form_elements);
                $server_label = self::server_owned_submission_label($field_name, $form_elements);
                if( $server_label===false ) {
                    return false;
                }
                $variants = self::server_owned_selection_values($field_name, $field_data, $form_elements, $form_id);
                if( $variants===false ) {
                    return false;
                }
                $server_timestamp = array_key_exists('timestamp', $field_data)
                    ? self::server_owned_date_timestamp($field_name, $field_data, $form_elements)
                    : null;
                unset(
                    $field_data['label'],
                    $field_data['option_label'],
                    $field_data['admin_value'],
                    $field_data['confirm_value'],
                    $field_data['entry_value'],
                    $field_data['selected_values'],
                    $field_data['timestamp']
                );
                if( $server_label!==null && !( count($matching_elements)>1 && $variants===null ) ) {
                    $field_data['label'] = $server_label;
                }
                if( is_array($variants) ) {
                    foreach( $variants as $variant_key => $variant_value ) {
                        $field_data[$variant_key] = $variant_value;
                    }
                }
                if( is_string($server_timestamp) && $server_timestamp!=='' ) {
                    $field_data['timestamp'] = $server_timestamp;
                }
                $data[$field_name] = $field_data;
                continue;
            }
            $data[$field_name] = self::rebuild_selection_entry_values($field_data, $form_elements, $form_id);
            if( $data[$field_name]===false ) {
                return false;
            }
        }
        return $data;
    }


    private static function retained_owned_upload_is_current( $owned ) {
        if( !is_array($owned)
            || !isset(
                $owned['legacy_entry_id'],
                $owned['legacy_source_field'],
                $owned['legacy_source_key'],
                $owned['form_id'],
                $owned['field'],
                $owned['storage'],
                $owned['basename'],
                $owned['mime'],
                $owned['url']
            )
            || absint($owned['legacy_entry_id'])===0
            || absint($owned['form_id'])===0
            || !is_string($owned['legacy_source_field'])
            || $owned['legacy_source_field']===''
            || (!is_int($owned['legacy_source_key']) && !is_string($owned['legacy_source_key'])) ) {
            return false;
        }
        $entry_id = absint($owned['legacy_entry_id']);
        $field = (string) $owned['legacy_source_field'];
        $entry = get_post($entry_id);
        if( !($entry instanceof WP_Post)
            || $entry->post_type!=='super_contact_entry'
            || absint($entry->post_parent)!==absint($owned['form_id'])
            || !in_array($entry->post_status, array('publish', 'super_unread', 'super_read'), true) ) {
            return false;
        }
        $entry_data = SUPER_Data_Access::get_entry_data($entry_id);
        if( !is_array($entry_data)
            || !isset($entry_data[$field])
            || !is_array($entry_data[$field])
            || !isset($entry_data[$field]['type'])
            || $entry_data[$field]['type']!=='files'
            || !isset($entry_data[$field]['files'])
            || !is_array($entry_data[$field]['files'])
            || !array_key_exists($owned['legacy_source_key'], $entry_data[$field]['files']) ) {
            return false;
        }
        $stored = $entry_data[$field]['files'][$owned['legacy_source_key']];
        if( !is_array($stored)
            || !isset($stored['value'], $stored['name'], $stored['type'], $stored['url'])
            || !is_string($stored['value'])
            || !is_string($stored['name'])
            || !is_string($stored['type'])
            || !is_string($stored['url'])
            || $stored['value']!==(string) $owned['basename']
            || $stored['name']!==$field
            || $stored['type']!==(string) $owned['mime']
            || $stored['url']!==(string) $owned['url'] ) {
            return false;
        }
        if( $owned['storage']==='attachment' ) {
            return isset($owned['attachment'], $stored['attachment'])
                && absint($stored['attachment'])===absint($owned['attachment'])
                && self::owned_upload_is_current($owned, $entry_id);
        }
        if( $owned['storage']!=='custom'
            || !isset(
                $owned['custom_path'],
                $owned['legacy_subdir'],
                $stored['path'],
                $stored['subdir'],
                $stored['_super_file_proof']
            )
            || !is_string($stored['path'])
            || !is_string($stored['subdir'])
            || !is_string($stored['_super_file_proof'])
            || $stored['path']!==(string) $owned['custom_path']
            || $stored['subdir']!==(string) $owned['legacy_subdir'] ) {
            return false;
        }
        $proof = self::owned_custom_upload_proof($owned);
        return is_string($proof)
            && hash_equals($stored['_super_file_proof'], $proof)
            && self::owned_upload_is_current($owned);
    }

    private static function delete_finalized_owned_uploads( $owned_files, $parent_id, $form_id, $field=null ) {
        $success = true;
        foreach( (array) $owned_files as $owned ) {
            if( $field!==null && (!isset($owned['field']) || $owned['field']!==$field) ) continue;
            $storage = isset($owned['storage']) ? $owned['storage'] : '';
            $retained = isset($owned['legacy_entry_id']);
            if( !in_array($storage, array('attachment', 'custom'), true)
                || ($retained
                    ? !self::retained_owned_upload_is_current($owned)
                    : ( absint(isset($owned['form_id']) ? $owned['form_id'] : 0)!==absint($form_id)
                        || !self::owned_upload_is_current(
                            $owned,
                            $storage==='attachment' ? absint($parent_id) : null
                        ) ) ) ) {
                $success = false;
                continue;
            }
            if( $storage==='attachment' ) {
                $deleted = wp_delete_attachment($owned['attachment'], true)!==false
                    && get_post($owned['attachment'])===null;
            } else {
                $deleted = SUPER_Common::delete_file($owned['custom_path'], $owned['allowed_root'])
                    && !is_file($owned['custom_path']);
            }
            if( !$deleted ) $success = false;
        }
        return $success;
    }

    private static function cleanup_owned_uploads( $owned_files ) {
        if( !is_array($owned_files) ) return false;
        $success = true;
        foreach( array_reverse($owned_files) as $owned ) {
            if( isset($owned['legacy_entry_id']) ) continue;
            $expected_parent = ($owned['storage']==='attachment' && isset($owned['cleanup_parent']))
                ? absint($owned['cleanup_parent'])
                : 0;
            if( !self::owned_upload_is_current($owned, $expected_parent) ) {
                $success = false;
                continue;
            }
            if( $owned['storage']==='attachment' ) {
                if( wp_delete_attachment($owned['attachment'], true)===false
                    || get_post($owned['attachment'])!==null ) {
                    $success = false;
                }
            } elseif( !SUPER_Common::delete_file($owned['custom_path'], $owned['allowed_root'])
                || is_file($owned['custom_path']) ) {
                $success = false;
            }
        }
        return $success;
    }

    private static function cleanup_pending_owned_uploads() {
        $owned_files = self::$pending_owned_upload_cleanup;
        self::$pending_owned_upload_cleanup = array();
        // Delete first and release the receipt only for a proven deletion. Discarding
        // receipts up front strips the retry authority for a file that survived.
        foreach( array_reverse($owned_files) as $owned ) {
            if( !self::cleanup_owned_uploads(array($owned)) ) continue;
            if( !empty($owned['cleanup_receipt_token']) && is_string($owned['cleanup_receipt_token']) ) {
                self::discard_upload_receipt($owned['cleanup_receipt_token']);
            }
        }
    }

    private static function arm_owned_upload_cleanup( $owned_files ) {
        if( !is_array($owned_files) || !empty(self::$pending_owned_upload_cleanup) ) return false;
        foreach( $owned_files as $owned ) {
            if( !self::owned_upload_is_current($owned, 0) ) return false;
        }
        self::$pending_owned_upload_cleanup = $owned_files;
        if( !empty($owned_files) && !self::$owned_upload_cleanup_registered ) {
            self::$owned_upload_cleanup_registered = true;
            register_shutdown_function(static function() {
                self::cleanup_pending_owned_uploads();
            });
        }
        return true;
    }

    private static function append_pending_owned_upload_cleanup( $owned, $upload_token ) {
        if( !self::owned_upload_is_current($owned, 0)
            || !is_string($upload_token) || !preg_match('/^[a-f0-9]{64}$/D', $upload_token) ) return false;
        $owned['cleanup_receipt_token'] = $upload_token;
        self::$pending_owned_upload_cleanup[] = $owned;
        if( !self::$owned_upload_cleanup_registered ) {
            self::$owned_upload_cleanup_registered = true;
            register_shutdown_function(static function() {
                self::cleanup_pending_owned_uploads();
            });
        }
        return true;
    }

    private static function update_pending_owned_upload_parent( $attachment_id, $parent_id ) {
        $attachment_id = absint($attachment_id);
        $parent_id = absint($parent_id);
        foreach( self::$pending_owned_upload_cleanup as $index => $owned ) {
            if( !is_array($owned) || $owned['storage']!=='attachment'
                || absint($owned['attachment'])!==$attachment_id ) continue;
            self::$pending_owned_upload_cleanup[$index]['cleanup_parent'] = $parent_id;
            return self::owned_upload_is_current(
                self::$pending_owned_upload_cleanup[$index],
                $parent_id
            );
        }
        return false;
    }

    private static function disarm_owned_upload_cleanup() {
        self::$pending_owned_upload_cleanup = array();
    }

    /**
     * Decode and materialize the generated PDF before any file-aware extension hook.
     * Raw data never survives in the returned submission data.
     */
    private static function materialize_generated_pdf( $data, $form_id, $settings ) {
        $field_name = '_generated_pdf_file';
        if( !isset($data[$field_name]) ) {
            return array('data'=>$data, 'owned_files'=>array());
        }
        if( empty($settings['_pdf']['generate']) || $settings['_pdf']['generate']!=='true' ) {
            unset($data[$field_name]);
            return array('data'=>$data, 'owned_files'=>array());
        }
        $field_data = $data[$field_name];
        if( !is_array($field_data)
            || !isset($field_data['type']) || $field_data['type']!=='files'
            || !isset($field_data['files']) || !is_array($field_data['files'])
            || count($field_data['files'])!==1 ) {
            return new WP_Error('invalid_generated_pdf', esc_html__( 'Invalid file upload.', 'super-forms' ));
        }
        $key = null;
        $value = null;
        foreach( $field_data['files'] as $file_key => $file_value ) {
            $key = $file_key;
            $value = $file_value;
            break;
        }
        if( !is_array($value) || empty($value['datauristring']) || !is_string($value['datauristring']) ) {
            return new WP_Error('invalid_generated_pdf', esc_html__( 'Invalid file upload.', 'super-forms' ));
        }
        $pdf_policy = self::generated_pdf_size_limit($settings);
        if( $pdf_policy===false ) {
            return new WP_Error('invalid_generated_pdf', esc_html__( 'Invalid file upload configuration.', 'super-forms' ));
        }
        $data_uri = str_replace(' ', '+', $value['datauristring']);
        unset($value['datauristring']);
        unset($data[$field_name]['files'][$key]['datauristring']);
        $prefix = 'data:application/pdf;base64,';
        if( strpos($data_uri, $prefix)===0 ) {
            $encoded_pdf = substr($data_uri, strlen($prefix));
        }else{
            // The bundled jsPDF build emits the PDF media type with an optional
            // ";filename=<name>" parameter, e.g.
            //   data:application/pdf;filename=example.pdf;base64,<data>
            // Tolerate that single MIME parameter and strip it off, keeping the
            // strict base64 payload validation and size limits below unchanged.
            $filename_prefix = 'data:application/pdf;filename=';
            $base64_marker = ';base64,';
            $marker_pos = ( strpos($data_uri, $filename_prefix)===0 )
                ? strpos($data_uri, $base64_marker, strlen($filename_prefix))
                : false;
            if( $marker_pos===false ) {
                return new WP_Error('invalid_generated_pdf', esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
            }
            $encoded_pdf = substr($data_uri, $marker_pos + strlen($base64_marker));
        }
        $max_decoded = self::base64_decoded_size_upper_bound($encoded_pdf);
        if( $max_decoded===false || $max_decoded>$pdf_policy['bytes'] ) {
            return new WP_Error('invalid_generated_pdf', esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
        }
        $pdf_data = base64_decode($encoded_pdf, true);
        unset($data_uri);
        unset($encoded_pdf);
        if( $pdf_data===false || substr($pdf_data, 0, 5)!=='%PDF-' ) {
            return new WP_Error('invalid_generated_pdf', esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
        }
        $pdf_length = strlen($pdf_data);
        if( !is_int($pdf_length) || $pdf_length<=0 || $pdf_length>$pdf_policy['bytes'] ) {
            return new WP_Error('invalid_generated_pdf', esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
        }
        if( function_exists('finfo_open') && defined('FILEINFO_MIME_TYPE') ) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if( $finfo!==false ) {
                $detected_type = @finfo_buffer($finfo, $pdf_data);
                finfo_close($finfo);
                if( $detected_type!==false && $detected_type!=='application/pdf' && $detected_type!=='application/x-pdf' ) {
                    return new WP_Error('invalid_generated_pdf', esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
                }
            }
        }

        $filename = false;
        $base_dir = false;
        $attachment_id = 0;
        try {
            unset($GLOBALS['super_upload_dir']);
            add_filter('upload_dir', array('SUPER_Forms', 'filter_upload_dir'));
            if( empty($GLOBALS['super_upload_dir']) ) {
                $GLOBALS['super_upload_dir'] = wp_upload_dir();
            }
            $upload_dir = $GLOBALS['super_upload_dir'];
            remove_filter('upload_dir', array('SUPER_Forms', 'filter_upload_dir'));
            unset($GLOBALS['super_upload_dir']);
            if( !is_array($upload_dir) || empty($upload_dir['path']) ) {
                throw new Exception(esc_html__( 'Invalid upload directory.', 'super-forms' ));
            }

            $pdf_settings = $pdf_policy['settings'];
            $tag_data = $data;
            unset($tag_data[$field_name]);
            $value['value'] = SUPER_Common::email_tags(
                isset($pdf_settings['filename']) && is_scalar($pdf_settings['filename']) ? (string) $pdf_settings['filename'] : '',
                $tag_data,
                $settings
            );
            $value['label'] = SUPER_Common::email_tags(
                isset($pdf_settings['emailLabel']) && is_scalar($pdf_settings['emailLabel']) ? (string) $pdf_settings['emailLabel'] : '',
                $tag_data,
                $settings
            );
            $basename = sanitize_file_name(wp_basename((string) $value['value']));
            $stem = preg_replace('/\.[^.]*$/', '', $basename);
            $stem = str_replace('.', '_', (string) $stem);
            $stem = trim($stem, '.-_');
            if( $stem==='' ) {
                $stem = 'super-forms-' . strtotime(date_i18n('Y-m-d H:i:s'));
            }

            $base_dir = realpath($upload_dir['path']);
            if( $base_dir===false ) {
                throw new Exception(esc_html__( 'Invalid upload directory.', 'super-forms' ));
            }
            $basename = wp_unique_filename($base_dir, $stem . '.pdf');
            $filename = trailingslashit($base_dir) . $basename;
            $parent_real = realpath(dirname($filename));
            if( $parent_real===false
                || wp_normalize_path($parent_real)!==wp_normalize_path($base_dir)
                || !self::upload_path_is_descendant($filename, $base_dir) ) {
                throw new Exception(esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
            }

            $handle = fopen($filename, 'xb');
            if( $handle===false ) {
                throw new Exception(esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
            }
            $written = fwrite($handle, $pdf_data);
            fclose($handle);
            unset($pdf_data);
            if( $written===false || $written!==$pdf_length ) {
                throw new Exception(esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
            }
            $verified_pdf = wp_check_filetype_and_ext(
                $filename,
                $basename,
                array('pdf'=>'application/pdf')
            );
            if( empty($verified_pdf['ext']) || $verified_pdf['ext']!=='pdf'
                || empty($verified_pdf['type']) || $verified_pdf['type']!=='application/pdf' ) {
                throw new Exception(esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
            }

            $legacy_subdir = '';
            $is_secure_dir = substr($upload_dir['subdir'], 0, 3);
            $wp_content_dir = str_replace(ABSPATH, '', WP_CONTENT_DIR);
            if( strpos($upload_dir['subdir'], $wp_content_dir)===false ) $is_secure_dir = '/..';
            if( $is_secure_dir==='/..' || $is_secure_dir==='../' ) {
                $legacy_subdir = trailingslashit($upload_dir['subdir']) . $basename;
                $file_url = trailingslashit($upload_dir['baseurl']) . 'sfgtfi' . $legacy_subdir;
                $file_url = str_replace('../', '__/', $file_url);
            } else {
                $attachment = array(
                    'post_mime_type' => 'application/pdf',
                    'post_title' => preg_replace('/\.[^.]+$/', '', $basename),
                    'post_content' => '',
                    'post_status' => 'inherit',
                );
                $attachment_id = wp_insert_attachment($attachment, $filename, 0);
                if( is_wp_error($attachment_id) || !$attachment_id ) {
                    $attachment_id = 0;
                    throw new Exception(esc_html__( 'The file upload failed.', 'super-forms' ));
                }
                add_post_meta($attachment_id, 'super-forms-form-upload-file', true);
                add_post_meta($attachment_id, '_super_forms_upload_form_id', absint($form_id));
                add_post_meta($attachment_id, '_super_forms_upload_field', $field_name);
                $attach_data = wp_generate_attachment_metadata($attachment_id, $filename);
                wp_update_attachment_metadata($attachment_id, $attach_data);
                $file_url = wp_get_attachment_url($attachment_id);
            }
            if( !is_string($file_url) || $file_url==='' ) {
                throw new Exception(esc_html__( 'The file upload failed.', 'super-forms' ));
            }
            $owned = self::build_owned_upload(
                $form_id,
                $field_name,
                $filename,
                'application/pdf',
                $file_url,
                $attachment_id,
                $base_dir,
                $pdf_length,
                $legacy_subdir
            );
            if( $owned===false ) {
                throw new Exception(esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
            }
            $server_file = self::owned_upload_file_record($owned);
            $server_file['label'] = $value['label'];
            $server_file['name'] = $owned['basename'];
            if( $owned['storage']==='custom' ) $server_file['path'] = $owned['custom_path'];
            $data[$field_name]['files'][$key] = $server_file;
            if( !empty($settings['_pdf']['excludeEntry']) && $settings['_pdf']['excludeEntry']==='true' ) {
                $data[$field_name]['exclude_entry'] = 'true';
            }
            return array('data'=>$data, 'owned_files'=>array($owned));
        } catch( Exception $e ) {
            remove_filter('upload_dir', array('SUPER_Forms', 'filter_upload_dir'));
            unset($GLOBALS['super_upload_dir']);
            if( $attachment_id ) {
                wp_delete_attachment($attachment_id, true);
            } elseif( $filename && $base_dir && is_file($filename) ) {
                SUPER_Common::delete_file($filename, $base_dir);
            }
            return new WP_Error('invalid_generated_pdf', $e->getMessage());
        }
    }

    public static function submit_form_checks( $settings=null, $skipChecks=false ) {
        // Check if form_id exists, this is always required
        if(empty($_POST['form_id'])) {
            $max_input_vars = ini_get('max_input_vars');
            $double_max_input_vars = round(ini_get('max_input_vars')*2, 0);
            if(ini_set('max_input_vars', $double_max_input_vars)==false){
                SUPER_Common::output_message(
                    $error = true,
                    sprintf( esc_html__( 'Error: the server could not submit this form because it reached it\'s "max_input_vars" limit of %s' . ini_get('max_input_vars') . '%s. Please contact your webmaster and increase this limit inside your php.ini file!', 'super-forms' ), '<strong>', '</strong>' )
                );
            }else{
                SUPER_Common::output_message(
                    $error = true,
                    sprintf( esc_html__( 'Error: the server could not submit this form because it reached it\'s "max_input_vars" limit of %s' . $max_input_vars . '%s. We manually increased this limit to %s' . $double_max_input_vars . '%s. Please refresh this page and try again!', 'super-forms' ), '<strong>', '</strong>' )
                );
            }
        }
        $form_id = absint($_POST['form_id']);
        if( !$form_id || !self::upload_form_id_is_valid($form_id) ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form.', 'super-forms' ) );
        }
        $response_data = array('form_id'=>$form_id);
        if( $skipChecks===true ) {
            return array(
                'data'=>array(),
                'form_id'=>$form_id,
                'entry_id'=>'',
                'list_id'=>'',
                'settings'=>$settings,
                'response_data'=>$response_data,
                'owned_files'=>array(),
                'retained_owned_files'=>array(),
            );
        }

        $data = array();
        if( !empty( $_POST['data'] ) ) {
            $data = json_decode(wp_unslash($_POST['data']), true);
            if( !is_array($data) ) {
                SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form data.', 'super-forms' ) );
            }
        }
        if( !empty($data['super_hp']) ) exit;
        unset($data['super_hp']);

        $stored_settings = SUPER_Common::get_form_settings($form_id);
        if( !is_array($stored_settings) ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form.', 'super-forms' ) );
        }
        if( $settings==null ) {
            $settings = $stored_settings;
            unset($settings['theme_custom_js']);
            unset($settings['theme_custom_css']);
            unset($settings['form_custom_css']);
        }
        $entry_id = isset($_POST['entry_id']) ? absint($_POST['entry_id']) : 0;
        $list_id = isset($_POST['list_id']) ? absint($_POST['list_id']) : '';
        $listing_settings = $stored_settings;
        $listing_form_id = $form_id;
        if( $list_id!=='' ) {
            $listing_form_id = self::submission_listing_host_form_id($form_id);
            if( $listing_form_id===false ) {
                SUPER_Common::output_message( $error = true, esc_html__( 'You do not have permission to edit this entry.', 'super-forms' ) );
            }
            $listing_settings = SUPER_Common::get_form_settings($listing_form_id);
            if( !is_array($listing_settings) ) {
                SUPER_Common::output_message( $error = true, esc_html__( 'You do not have permission to edit this entry.', 'super-forms' ) );
            }
        }elseif( $entry_id!=='' && $entry_id!==0
            && ( !is_array($stored_settings)
                || empty($stored_settings['update_contact_entry'])
                || $stored_settings['update_contact_entry']!=='true' ) ) {
            $entry_id = 0;
            $_POST['entry_id'] = '0';
            $_REQUEST['entry_id'] = '0';
            if( isset($data['hidden_contact_entry_id']) && is_array($data['hidden_contact_entry_id']) ) {
                $data['hidden_contact_entry_id']['value'] = '0';
            }
        }
        if( !self::submission_entry_update_is_authorized($entry_id, $list_id, $form_id, $listing_settings, $listing_form_id, $list_id!=='') ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'You do not have permission to edit this entry.', 'super-forms' ) );
        }
        $form_elements = SUPER_Common::get_form_elements($form_id);
        if( !self::submission_data_matches_contract($data, $form_elements, $form_id, $entry_id ? $entry_id : '', $list_id) ) {
            SUPER_Common::output_message( $error = true, esc_html__( 'Invalid form data.', 'super-forms' ) );
        }
        $resolved = self::resolve_submission_files($data, $form_id, $form_elements, $entry_id);
        if( is_wp_error($resolved) ) {
            SUPER_Common::output_message($error = true, $resolved->get_error_message());
        }
        $data = $resolved['data'];
        $inspected_receipts = $resolved['inspected'];
        $retained_owned_files = $resolved['retained_owned_files'];
        if( !self::submission_files_match_stored_policy( $data, $form_elements ) ) {
            SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
        }

        // Settings extensions still run before deterministic validation, but file carriers and
        // raw PDF bytes are absent from both their data and post payloads.
        $settings_filter_data = self::submission_data_without_files($data);
        $had_post_data = array_key_exists('data', $_POST);
        $original_post_data = $had_post_data ? $_POST['data'] : null;
        $_POST['data'] = self::submission_request_post_data_json($settings_filter_data);
        try {
            $settings = apply_filters(
                'super_before_submit_form_settings_filter',
                $settings,
                array('data'=>$settings_filter_data, 'post'=>$_POST, 'entry_id'=>$entry_id, 'list_id'=>$list_id)
            );
        } catch( Exception $e ) {
            if( $had_post_data ) {
                $_POST['data'] = $original_post_data;
            } else {
                unset($_POST['data']);
            }
            throw $e;
        }
        if( $had_post_data ) {
            $_POST['data'] = $original_post_data;
        } else {
            unset($_POST['data']);
        }

        // @since 6.3.315 - Server-side required-field validation.
        if( is_array($form_elements) && !empty($form_elements) ) {
            $required_fields = self::collect_required_fields( $form_elements );
            if( !empty( $required_fields ) ) {
                foreach( $data as $field_name => $field_data ) {
                    if( !is_array( $field_data ) || !isset( $field_data['value'] ) ) continue;
                    if( isset( $field_data['type'] ) && $field_data['type']==='files' ) continue;
                    if( isset($required_fields[$field_name])
                        && !self::required_field_value_present($field_data['value']) ) {
                            SUPER_Common::output_message( $error = true, esc_html__( 'Please fill in all required fields.', 'super-forms' ) );
                        }
                    }
                foreach( $required_fields as $field_name => $meta ) {
                    if( empty( $meta['always_present'] ) ) continue;
                    $present = isset( $data[ $field_name ]['value'] )
                        && self::required_field_value_present( $data[ $field_name ]['value'] );
                    if( !$present ) {
                        SUPER_Common::output_message( $error = true, esc_html__( 'Please fill in all required fields.', 'super-forms' ) );
                    }
                }
                $dynamic_data = isset( $data['_super_dynamic_data'] ) ? $data['_super_dynamic_data'] : null;
                if( !self::validate_repeater_required_values( $dynamic_data, $required_fields, $form_elements, $form_id ) ) {
                    SUPER_Common::output_message( $error = true, esc_html__( 'Please fill in all required fields.', 'super-forms' ) );
                }
            }
        }

        // The stored reCAPTCHA element selects the allowed version and stored form
        // settings select its secret; request fields supply only a response token.
        $captcha_versions = array();
        self::form_recaptcha_versions($form_elements, $captcha_versions);
        if( !empty($captcha_versions) ) {
            if( !isset($_POST['version'], $_POST['token'])
                || !is_string($_POST['version']) || !is_string($_POST['token'])
                || $_POST['token']==='' || !isset($captcha_versions[$_POST['version']]) ) {
                SUPER_Common::output_message( $error=true, esc_html__( 'reCAPTCHA verification is required.', 'super-forms' ) );
            }
            $version = $_POST['version'];
            $secret_key = $version==='v3' ? 'form_recaptcha_v3_secret' : 'form_recaptcha_secret';
            $secret = ( is_array($settings) && isset($settings[$secret_key]) ) ? $settings[$secret_key] : '';
            if( !is_string($secret) || $secret==='' ) {
                SUPER_Common::output_message( $error=true, esc_html__( 'reCAPTCHA verification is required.', 'super-forms' ) );
            }
            $response = wp_remote_post(
                'https://www.google.com/recaptcha/api/siteverify',
                array(
                    'timeout' => 45,
                    'body' => array(
                        'secret' => $secret,
                        'response' => $_POST['token'],
                    ),
                )
            );
            if ( is_wp_error( $response ) ) {
                SUPER_Common::output_message(
                    $error = true,
                    esc_html__( 'Something went wrong:', 'super-forms' ) . ' ' . $response->get_error_message()
                );
            }
            $result = json_decode( $response['body'], true );
            if( !is_array($result) || empty($result['success']) ) {
                SUPER_Common::output_message( $error=true, esc_html__( 'Google reCAPTCHA verification failed!', 'super-forms' ) );
            }
        }

        // @since 4.7.0 - translation
        if(!empty($_POST['i18n'])){
            $i18n = sanitize_text_field($_POST['i18n']);
            if( !empty($settings['i18n']) && !empty($settings['i18n'][$i18n]) ){
                $settings = array_replace_recursive($settings, $settings['i18n'][$i18n]);
                unset($settings['i18n']);
            }
        }

        // Deterministic form-locker rejection happens before any receipt claim.
        if( !empty($settings['form_locker']) ) {
            if( !isset($settings['form_locker_limit']) ) $settings['form_locker_limit'] = 0;
            $count = get_post_meta( $form_id, '_super_submission_count', true );
            if( $count>=$settings['form_locker_limit'] ) {
                $msg = '';
                if($settings['form_locker_msg_title']!='') {
                    $msg .= '<h1>' . $settings['form_locker_msg_title'] . '</h1>';
                }
                $msg .= nl2br($settings['form_locker_msg_desc']);
                SUPER_Common::output_message( $error=true, $msg );
            }
        }
        if( !empty($settings['user_form_locker']) ) {
            $current_user_id = get_current_user_id();
            if( $current_user_id!=0 ) {
                $user_limits = get_post_meta( $form_id, '_super_user_submission_counter', true );
                $count = !empty($user_limits[$current_user_id])
                    ? absint($user_limits[$current_user_id]) + 1
                    : 0;
                $limit = !empty($settings['user_form_locker_limit'])
                    ? absint($settings['user_form_locker_limit'])
                    : 0;
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


        $claims = self::claim_upload_receipts($inspected_receipts);
        if( $claims===false ) {
            SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
        }
        $materialized = self::materialize_generated_pdf($data, $form_id, $settings);
        if( is_wp_error($materialized) ) {
            self::rollback_upload_receipt_claims($claims);
            SUPER_Common::output_message($error = true, $materialized->get_error_message());
        }
        $data = $materialized['data'];
        $consumed_files = self::consume_upload_receipt_claims($claims);
        if( $consumed_files===false ) {
            self::cleanup_owned_uploads($materialized['owned_files']);
            SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload receipt.', 'super-forms' ));
        }
        $owned_files = array_merge($consumed_files, $materialized['owned_files']);
        if( !self::arm_owned_upload_cleanup($owned_files) ) {
            self::cleanup_owned_uploads($owned_files);
            SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload ownership.', 'super-forms' ));
        }

        // From this point onward every file carrier is a server-built owned record.
        $data = self::rebuild_selection_entry_values($data, $form_elements, $form_id);
        if( $data===false ) {
            self::cleanup_owned_uploads($owned_files);
            SUPER_Common::output_message($error = true, esc_html__( 'Invalid form data.', 'super-forms' ));
        }
        $_POST['data'] = self::submission_request_post_data_json($data);
        $data = apply_filters(
            'super_before_sending_email_data_filter',
            $data,
            array('data'=>$data, 'post'=>$_POST, 'settings'=>$settings)
        );
        if( !is_array($data) ) {
            SUPER_Common::output_message($error = true, esc_html__( 'Invalid form data.', 'super-forms' ));
        }
        do_action('super_before_sending_email_hook', array('data'=>$data, 'post'=>$_POST, 'settings'=>$settings));

        return array(
            'data'=>$data,
            'form_id'=>$form_id,
            'entry_id'=>$entry_id,
            'list_id'=>$list_id,
            'settings'=>$settings,
            'response_data'=>$response_data,
            'owned_files'=>$owned_files,
            'retained_owned_files'=>$retained_owned_files,
        );
    }
    public static function upload_files() {
        $csrf_validation = SUPER_Common::verifyCSRF();
        $csrf_settings = $csrf_validation ? array() : SUPER_Common::get_global_settings();
        if( !self::csrf_policy_allows_request($csrf_validation, $csrf_settings) ) {
            SUPER_Common::output_message(
                $error = true,
                esc_html__( 'Unable to upload file, session expired!', 'super-forms' )
            );
        }
        if( array_key_exists('super_hp', $_POST) ) {
            if( !is_scalar($_POST['super_hp']) && $_POST['super_hp']!==null ) {
                exit;
            }
            if( trim((string) wp_unslash($_POST['super_hp']))!=='' ) {
                exit;
            }
        }
        $atts = self::submit_form_checks( null, true );
        $data = $atts['data'];
        $form_id = $atts['form_id'];
        $form_elements = SUPER_Common::get_form_elements($form_id);
        $file_routes = array();
        self::collect_submission_file_routes($form_elements, $file_routes);


        $files = isset($_FILES['files']) ? $_FILES['files'] : array();
        if( !self::upload_files_are_parallel($files) ) {
            SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload request.', 'super-forms' ));
        }
        $field_identities = self::upload_request_field_identities($files['name'], $file_routes);
        if( $field_identities===false ) {
            SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload field.', 'super-forms' ));
        }

        // Validate the complete request and exact stored field policies before the first move.
        $dangerous = self::dangerous_upload_extensions();
        $plans = array();
        foreach( $files['name'] as $field_name => $file_names ) {
            $identity = isset($field_identities[$field_name]) ? $field_identities[$field_name] : false;
            $stored_field_name = ( is_array($identity) && isset($identity['stored_field_name']) )
                ? $identity['stored_field_name']
                : false;
            $file_element = $stored_field_name!==false ? self::get_file_element($form_elements, $stored_field_name) : false;
            if( $file_element===false ) {
                SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload field.', 'super-forms' ));
            }
            $allowed_mimes = self::allowed_file_mime_types($file_element);
            if( empty($allowed_mimes) ) {
                SUPER_Common::output_message($error = true, esc_html__( 'This file type is not permitted.', 'super-forms' ));
            }
            $policy = self::get_upload_field_policy($file_element);
            if( $policy===false ) {
                SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload configuration.', 'super-forms' ));
            }
            $plans[$field_name] = array(
                'field_name' => $stored_field_name,
                'allowed_mimes' => $allowed_mimes,
                'size_limit' => $policy['size_limit'],
                'policy' => $policy,
                'files' => array(),
            );
            $file_count = count($file_names);
            if( $policy['max_files']>0 && $file_count>$policy['max_files'] ) {
                SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
            }
            $aggregate_size = 0;
            foreach( $file_names as $key => $unused_name ) {
                $tmp_name = $files['tmp_name'][$field_name][$key];
                $measured_size = ( is_string($tmp_name) && $tmp_name!=='' && is_file($tmp_name) ) ? filesize($tmp_name) : false;
                $file = array(
                    'name' => $files['name'][$field_name][$key],
                    'type' => $files['type'][$field_name][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $files['error'][$field_name][$key],
                    'size' => $files['size'][$field_name][$key],
                );
                if( (int) $file['error']!==UPLOAD_ERR_OK ) {
                    SUPER_Common::output_message($error = true, esc_html__( 'The file upload failed.', 'super-forms' ));
                }
                if( !is_int($measured_size) || $measured_size<0 ) {
                    SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload.', 'super-forms' ));
                }
                if( $measured_size>$policy['size_limit']['bytes'] ) {
                    SUPER_Common::output_message(
                        $error = true,
                        sprintf(
                            esc_html__( 'The file size exceeded the filesize limitation of %s MB.', 'super-forms' ),
                            $policy['size_limit']['megabytes']
                        )
                    );
                }
                $aggregate_size += $measured_size;
                if( $policy['aggregate_bytes']!==false && $aggregate_size>$policy['aggregate_bytes'] ) {
                    SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
                }

                $original_name = wp_basename($file['name']);
                $original_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                if( $original_extension==='' || sanitize_key($original_extension)!==$original_extension
                    || !preg_match('/^[a-z0-9]+$/D', $original_extension)
                    || isset($dangerous[$original_extension])
                    || !isset($allowed_mimes[$original_extension]) ) {
                    SUPER_Common::output_message($error = true, esc_html__( 'This file type is not permitted.', 'super-forms' ));
                }
                $name_parts = explode('.', strtolower($original_name));
                array_shift($name_parts);
                foreach( $name_parts as $name_part ) {
                    if( isset($dangerous[sanitize_key($name_part)]) ) {
                        SUPER_Common::output_message($error = true, esc_html__( 'This file type is not permitted.', 'super-forms' ));
                    }
                }
                $original_type = wp_check_filetype($original_name, $allowed_mimes);
                if( empty($original_type['ext']) || empty($original_type['type'])
                    || $original_type['ext']!==$original_extension
                    || $original_type['type']!==$allowed_mimes[$original_extension] ) {
                    SUPER_Common::output_message($error = true, esc_html__( 'This file type is not permitted.', 'super-forms' ));
                }
                $plans[$field_name]['files'][$key] = array(
                    'file' => $file,
                    'original_extension' => $original_extension,
                );
            }
        }

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        foreach( $plans as $field_name => $plan ) {
            $data[$field_name] = array('type'=>'files', 'files'=>array());
            if( $plan['field_name']!==$field_name ) {
                $data[$field_name]['field_name'] = $plan['field_name'];
            }
            foreach( $plan['files'] as $key => $file_plan ) {
                unset($GLOBALS['super_upload_dir']);
                $upload_dir = array();
                $upload_root = false;
                $uploaded_file = false;
                add_filter('upload_dir', array('SUPER_Forms', 'filter_upload_dir'));
                try {
                    if( empty($GLOBALS['super_upload_dir']) ) {
                        $GLOBALS['super_upload_dir'] = wp_upload_dir();
                    }
                    $upload_dir = $GLOBALS['super_upload_dir'];
                    if( is_array($upload_dir) && !empty($upload_dir['path']) ) {
                        $upload_root = realpath($upload_dir['path']);
                    }
                    if( $upload_root!==false && is_dir($upload_root) ) {
                        $uploaded_file = wp_handle_upload(
                            $file_plan['file'],
                            array(
                                'test_form' => false,
                                'mimes' => $plan['allowed_mimes'],
                            )
                        );
                    }
                } catch( Exception $e ) {
                    remove_filter('upload_dir', array('SUPER_Forms', 'filter_upload_dir'));
                    unset($GLOBALS['super_upload_dir']);
                    throw $e;
                }
                remove_filter('upload_dir', array('SUPER_Forms', 'filter_upload_dir'));
                unset($GLOBALS['super_upload_dir']);
                if( $upload_root===false || !is_dir($upload_root) ) {
                    SUPER_Common::output_message($error = true, esc_html__( 'Invalid upload directory.', 'super-forms' ));
                }
                if( !is_array($uploaded_file) || !empty($uploaded_file['error']) ) {
                    if( is_array($uploaded_file) && !empty($uploaded_file['file']) && is_string($uploaded_file['file']) ) {
                        SUPER_Common::delete_file($uploaded_file['file'], $upload_root);
                    }
                    $upload_error = is_array($uploaded_file) && !empty($uploaded_file['error'])
                        ? $uploaded_file['error']
                        : esc_html__( 'The file upload failed.', 'super-forms' );
                    SUPER_Common::output_message($error = true, $upload_error);
                }
                if( empty($uploaded_file['file']) || !is_string($uploaded_file['file'])
                    || empty($uploaded_file['type']) || !is_string($uploaded_file['type']) ) {
                    if( !empty($uploaded_file['file']) && is_string($uploaded_file['file']) ) {
                        SUPER_Common::delete_file($uploaded_file['file'], $upload_root);
                    }
                    SUPER_Common::output_message($error = true, esc_html__( 'The file upload failed.', 'super-forms' ));
                }

                $filename = realpath($uploaded_file['file']);
                if( $filename===false || is_link($uploaded_file['file'])
                    || !self::upload_path_is_descendant($filename, $upload_root) ) {
                    SUPER_Common::delete_file($uploaded_file['file'], $upload_root);
                    SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
                }
                $final_size = filesize($filename);
                if( !is_int($final_size) || $final_size<0 || $final_size>$plan['size_limit']['bytes'] ) {
                    SUPER_Common::delete_file($filename, $upload_root);
                    SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
                }
                $final_name = basename($filename);
                $final_extension = strtolower(pathinfo($final_name, PATHINFO_EXTENSION));
                $final_parts = explode('.', strtolower($final_name));
                array_shift($final_parts);
                foreach( $final_parts as $name_part ) {
                    if( isset($dangerous[sanitize_key($name_part)]) ) {
                        SUPER_Common::delete_file($filename, $upload_root);
                        SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
                    }
                }
                $verified_type = wp_check_filetype_and_ext($filename, $final_name, $plan['allowed_mimes']);
                if( $final_extension!==$file_plan['original_extension']
                    || !isset($plan['allowed_mimes'][$final_extension])
                    || empty($verified_type['ext']) || empty($verified_type['type'])
                    || $verified_type['ext']!==$final_extension
                    || $verified_type['type']!==$plan['allowed_mimes'][$final_extension]
                    || $uploaded_file['type']!==$verified_type['type'] ) {
                    SUPER_Common::delete_file($filename, $upload_root);
                    SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
                    }

                $attachment_id = 0;
                $legacy_subdir = '';
                $is_secure_dir = substr($upload_dir['subdir'], 0, 3);
                $wp_content_dir = str_replace(ABSPATH, '', WP_CONTENT_DIR);
                if( strpos($upload_dir['subdir'], $wp_content_dir)===false ) $is_secure_dir = '/..';
                if( $is_secure_dir==='/..' || $is_secure_dir==='../' ) {
                    $legacy_subdir = trailingslashit($upload_dir['subdir']) . $final_name;
                    $file_url = trailingslashit($upload_dir['baseurl']) . 'sfgtfi' . $legacy_subdir;
                    $file_url = str_replace('../', '__/', $file_url);
                } else {
                    $attachment = array(
                        'post_mime_type' => $verified_type['type'],
                        'post_title' => preg_replace('/\.[^.]+$/', '', $final_name),
                        'post_content' => '',
                        'post_status' => 'inherit',
                    );
                    $attachment_id = wp_insert_attachment($attachment, $filename, 0);
                    if( is_wp_error($attachment_id) || !$attachment_id ) {
                        SUPER_Common::delete_file($filename, $upload_root);
                        SUPER_Common::output_message($error = true, esc_html__( 'The file upload failed.', 'super-forms' ));
                }
                        add_post_meta($attachment_id, 'super-forms-form-upload-file', true);
                    add_post_meta($attachment_id, '_super_forms_upload_form_id', $form_id);
                    add_post_meta($attachment_id, '_super_forms_upload_field', $plan['field_name']);
                        $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
                        wp_update_attachment_metadata( $attachment_id,  $attach_data );
                    $file_url = wp_get_attachment_url($attachment_id);
                    }
                if( !is_string($file_url) || $file_url==='' ) {
                    if( $attachment_id ) {
                        wp_delete_attachment($attachment_id, true);
                    } else {
                        SUPER_Common::delete_file($filename, $upload_root);
                    }
                    SUPER_Common::output_message($error = true, esc_html__( 'The file upload failed.', 'super-forms' ));
                }
                $owned = self::build_owned_upload(
                    $form_id,
                    $plan['field_name'],
                    $filename,
                    $verified_type['type'],
                    $file_url,
                    $attachment_id,
                    $upload_root,
                    $final_size,
                    $legacy_subdir
                );
                if( $owned===false ) {
                    if( $attachment_id ) {
                        wp_delete_attachment($attachment_id, true);
                    } else {
                        SUPER_Common::delete_file($filename, $upload_root);
                    }
                    SUPER_Common::output_message($error = true, esc_html__( 'Invalid file upload rejected.', 'super-forms' ));
                }
                $receipt_owned = $owned;
                if( is_array($receipt_owned) ) {
                    $receipt_owned['route_name'] = $field_name;
                }
                $upload_token = $owned ? self::issue_upload_receipt($receipt_owned) : false;
                if( $upload_token===false ) {
                    if( $attachment_id ) {
                        wp_delete_attachment($attachment_id, true);
                    } else {
                        SUPER_Common::delete_file($filename, $upload_root);
                    }
                    SUPER_Common::output_message($error = true, esc_html__( 'Unable to authorize the uploaded file.', 'super-forms' ));
                }
                if( !self::append_pending_owned_upload_cleanup($owned, $upload_token) ) {
                    self::discard_upload_receipt($upload_token);
                    self::cleanup_owned_uploads(array($owned));
                    SUPER_Common::output_message($error = true, esc_html__( 'Unable to authorize the uploaded file.', 'super-forms' ));
                }
                $response_file = self::owned_upload_public_record($owned, $field_name);
                $response_file['upload_token'] = $upload_token;
                $data[$field_name]['files'][$key] = $response_file;
                }
            }
        self::disarm_owned_upload_cleanup();
        echo wp_json_encode($data);
        die();
    }
    public static function submit_form( $settings=null ) {
        $csrf_validation = SUPER_Common::verifyCSRF();
        $csrf_settings = $csrf_validation ? array() : SUPER_Common::get_global_settings();
        if( !self::csrf_policy_allows_request($csrf_validation, $csrf_settings) ) {
                SUPER_Common::output_message(
                    $error = true,
                    esc_html__( 'Unable to submit form, session expired!', 'super-forms' )
                );
        }
        $atts = self::submit_form_checks($settings);
        $data = $atts['data'];
        $form_id = $atts['form_id'];
        $entry_id = $atts['entry_id'];
        $list_id = $atts['list_id'];
        $settings = $atts['settings'];
        $response_data = $atts['response_data'];
        $owned_files = $atts['owned_files'];
        $retained_owned_files = $atts['retained_owned_files'];
        if( isset($data) && count($data)>0 ) {
            foreach( $data as $key => $field_data ) {
                if( !is_array($field_data) || !isset($field_data['type']) || $field_data['type']==='files' ) continue;
                // @since 1.2.9 - Save [label] or both [value and label].
                if( isset($field_data['entry_value']) ) {
                    $data[$key]['value'] = $field_data['entry_value'];
                                }
                            }
                        }
        
        // @since 4.9.5
        $data = apply_filters( 'super_after_processing_files_data_filter', $data, array( 'post'=>$_POST, 'settings'=>$settings ) );        

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
            
        if( ($entry_id!=0) && (!empty($settings['contact_entry_prevent_creation'])) ) {
            $settings['save_contact_entry'] = 'no';
        }

        $contact_entry_id = null;
        if( $settings['save_contact_entry']=='yes' ) {
            // First save the entry simply because we need the ID
            $post = array(
                'post_status' => 'super_unread',
                'post_type' => 'super_contact_entry' ,
                'post_parent' => $form_id // @since 1.7 - save the form ID as the parent
            );
            // @since 3.8.0 - save the post author based on session if set (currently used by Register & Login)
            $post_author = SUPER_Common::getClientData( 'update_user_meta' );
            if( $post_author!=false ) {
                $post['post_author'] = absint($post_author);
            }
            $contact_entry_id = wp_insert_post($post);
            if( is_wp_error($contact_entry_id) || !$contact_entry_id ) {
                SUPER_Common::output_message( $error = true, esc_html__( 'Unable to save contact entry.', 'super-forms' ) );
            }


            // Check if we prevent saving duplicate entry titles
            // Return error message to user
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
                        $contact_entry_title = $contact_entry_title . $contact_entry_id;
                    }
                }
            }else{
                $contact_entry_title = $contact_entry_title . ' ' . $contact_entry_id;
            }
            // Update title
            $post = array(
                'ID' => $contact_entry_id,
                'post_title' => $contact_entry_title,
            );
            wp_update_post($post);

            // @since 4.9.600 - check if entry title already exists
            if(!empty($settings['contact_entry_unique_title']) && $settings['contact_entry_unique_title']==='true'){
                if(empty($settings['contact_entry_unique_title_compare'])) $settings['contact_entry_unique_title_compare'] = 'form';
                global $wpdb;
                $total = 0;
                if(empty($settings['contact_entry_unique_title_trashed'])) $settings['contact_entry_unique_title_trashed'] = '';
                // By default we do not compare against trashed entries
                $trash_compare = "post_status != 'trash' AND ";
                if($settings['contact_entry_unique_title_trashed']==='true'){
                    // If user also wishes to compare against trashed entries
                    $trash_compare = '';
                }
                if($settings['contact_entry_unique_title_compare']==='form'){
                    $query = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE $trash_compare post_type = 'super_contact_entry' AND post_parent = '%d' AND post_title = '%s'", $form_id, $contact_entry_title);
                    $total = $wpdb->get_var($query);
                }elseif($settings['contact_entry_unique_title_compare']==='global'){
                    $query = $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE $trash_compare post_type = 'super_contact_entry' AND post_title = '%s'", $contact_entry_title);
                    $total = $wpdb->get_var($query);
                }elseif($settings['contact_entry_unique_title_compare']==='ids'){
                    if(empty($settings['contact_entry_unique_title_form_ids'])) $settings['contact_entry_unique_title_form_ids'] = '';
                    $ids = $settings['contact_entry_unique_title_form_ids'];
                    $ids = sanitize_text_field($ids);
                    $ids = explode(',', $ids);
                    $form_ids = array();
                    foreach($ids as $k => $v){
                        $v = trim($v);
                        if(empty($v)) continue;
                        $form_ids[$k] = absint($v);
                    }
                    unset($ids);
                    $form_ids_placeholder = implode( ', ', array_fill( 0, count( $form_ids ), '%d' ) );
                    $prepare_values  = array_merge( $form_ids, array( $contact_entry_title ) );
                    $query = $wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->posts WHERE $trash_compare post_type = 'super_contact_entry' AND post_parent IN ($form_ids_placeholder) AND post_title = '%s'", $prepare_values);
                    $total = $wpdb->get_var($query);
                }
                if($total>1){ // If 2 entries found, it means the current created entry has the same title as an already existing entry
                    wp_delete_post( $contact_entry_id, true );
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = esc_html(SUPER_Common::email_tags( $settings['contact_entry_unique_title_msg'], $data, $settings ))
                    );
                }
            }

            $response_data['contact_entry_id'] = $contact_entry_id;

            // @since 3.4.0 - save custom contact entry status
            if(!empty($_POST['entry_status'])){
                $entry_status = sanitize_text_field( $_POST['entry_status'] );
                if($entry_status!=''){
                    $settings['contact_entry_custom_status'] = $entry_status;
                }
            }
            if(!empty($settings['contact_entry_custom_status'])){
                update_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['contact_entry_custom_status'] );
            }

            // @since 1.4 - add the contact entry ID to the data array so we can use it to retrieve it with {tags}
            $data['contact_entry_id']['name'] = 'contact_entry_id';
            $data['contact_entry_id']['value'] = $contact_entry_id;
            $data['contact_entry_id']['label'] = '';
            $data['contact_entry_id']['type'] = 'form_id';

            // Reparent only receipt-resolved/server-created attachments that are still the
            // exact plugin-owned objects recorded earlier in this request.
            foreach( $owned_files as $owned_file ) {
                if( $owned_file['storage']!=='attachment' ) continue;
                if( !self::owned_upload_is_current($owned_file, 0) ) {
                    SUPER_Common::output_message( $error = true, esc_html__( 'Invalid file upload ownership.', 'super-forms' ) );
                }
                $updated = wp_update_post(array(
                    'ID' => $owned_file['attachment'],
                    'post_parent' => $contact_entry_id,
                ), true);
                if( is_wp_error($updated) || !$updated
                    || !self::update_pending_owned_upload_parent($owned_file['attachment'], $contact_entry_id) ) {
                    SUPER_Common::output_message( $error = true, esc_html__( 'Unable to attach uploaded file.', 'super-forms' ) );
                }
            }

        }
        if( $entry_id!=0 && $settings['save_contact_entry']!=='yes' ) {
            foreach( $owned_files as $owned_file ) {
                if( $owned_file['storage']!=='attachment' ) continue;
                if( !self::owned_upload_is_current($owned_file, 0) ) {
                    SUPER_Common::output_message( $error = true, esc_html__( 'Invalid file upload ownership.', 'super-forms' ) );
                }
                $updated = wp_update_post(array(
                    'ID' => $owned_file['attachment'],
                    'post_parent' => $entry_id,
                ), true);
                if( is_wp_error($updated) || !$updated
                    || !self::update_pending_owned_upload_parent($owned_file['attachment'], $entry_id) ) {
                    SUPER_Common::output_message( $error = true, esc_html__( 'Unable to attach uploaded file.', 'super-forms' ) );
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
                                if(!empty($v['value'])) $v['value'] = SUPER_Common::email_tags( $v['value'], $data, $settings );
                                $final_entry_data[$k] = $v;
                            }
                        }
                    }
                }
            }
        }
        if(isset($final_entry_data['hidden_list_id'])) {
            unset($final_entry_data['hidden_list_id']);
        }

        // @since 2.2.0 - update contact entry data by ID
        if($entry_id!=0){
            SUPER_Data_Access::update_entry_data( $entry_id, $final_entry_data);
            if( SUPER_Data_Access::get_entry_data($entry_id)!==$final_entry_data ) {
                SUPER_Common::output_message( $error = true, esc_html__( 'Unable to save contact entry.', 'super-forms' ) );
            }

            // @since 3.4.0 - update contact entry status
            $entry_status_update = (isset($_POST['entry_status_update']) ? sanitize_text_field( $_POST['entry_status_update'] ) : '');
            if($entry_status_update!=''){
                $settings['contact_entry_custom_status_update'] = $entry_status_update;
            }
            if( (isset($settings['contact_entry_custom_status_update'])) && ($settings['contact_entry_custom_status_update']!='') ) {
                update_post_meta( $entry_id, '_super_contact_entry_status', $settings['contact_entry_custom_status_update'] );
            }
        }

        if( $settings['save_contact_entry']=='yes' ){
            SUPER_Data_Access::update_entry_data( $contact_entry_id, $final_entry_data);
            if( SUPER_Data_Access::get_entry_data($contact_entry_id)!==$final_entry_data ) {
                SUPER_Common::output_message( $error = true, esc_html__( 'Unable to save contact entry.', 'super-forms' ) );
            }
            update_post_meta( $contact_entry_id, '_super_contact_entry_ip', SUPER_Common::real_ip() );

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
        if( $entry_id!=0 || $settings['save_contact_entry']==='yes' ) {
            $persisted_entry_id = $settings['save_contact_entry']==='yes' ? absint($contact_entry_id) : absint($entry_id);
            foreach( $owned_files as $owned_file ) {
                if( $owned_file['storage']==='attachment'
                    && !self::owned_upload_is_current($owned_file, $persisted_entry_id) ) {
                    SUPER_Common::output_message( $error = true, esc_html__( 'Invalid file upload ownership.', 'super-forms' ) );
                }
            }
            self::disarm_owned_upload_cleanup();
        }

        $settings = apply_filters( 'super_before_sending_email_settings_filter', $settings );
     
        if(!isset($settings['email_exclude_empty'])) $settings['email_exclude_empty'] = '';
        if(!isset($settings['confirm_exclude_empty'])) $settings['confirm_exclude_empty'] = '';

        $loops = SUPER_Common::retrieve_email_loop_html(array('data'=>$data, 'settings'=>$settings, 'exclude'=>array()));
        $email_loop = $loops['email_loop'];
        $confirm_loop = $loops['confirm_loop'];
        $attachments = $loops['attachments'];
        $confirm_attachments = $loops['confirm_attachments'];
        $string_attachments = $loops['string_attachments'];
        // Confirmation email keeps its own string attachments so exclude settings
        // (e.g. a signature excluded from the confirmation) are honored per recipient.
        $confirm_string_attachments = $loops['confirm_string_attachments'];

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
            if($k==='global_email_to_admin') {
                if(!empty($global_settings[$k . '_admin_force'])) $settings['header_to'] = $global_settings[$k];
            }
            if($k==='global_email_to_confirm') {
                if(!empty($global_settings[$k . '_confirm_force'])) $settings['confirm_to'] = $global_settings[$k];
            }
            if($k==='global_email_from'){
                if(!empty($global_settings[$k . '_admin_force'])) $settings['header_from'] = $global_settings[$k];
                if(!empty($global_settings[$k . '_confirm_force'])) $settings['confirm_from'] = $global_settings[$k];
            }
            if($k==='global_email_from_name'){
                if(!empty($global_settings[$k . '_admin_force'])) $settings['header_from_name'] = $global_settings[$k];
                if(!empty($global_settings[$k . '_confirm_force'])) $settings['confirm_from_name'] = $global_settings[$k];
            }
            if($k==='global_email_reply'){
                if(!empty($global_settings[$k . '_admin_force'])) $settings['header_reply'] = $global_settings[$k];
                if(!empty($global_settings[$k . '_confirm_force'])) $settings['confirm_reply'] = $global_settings[$k];
            }
            if($k==='global_email_reply_name'){
                if(!empty($global_settings[$k . '_admin_force'])) $settings['header_reply_name'] = $global_settings[$k];
                if(!empty($global_settings[$k . '_confirm_force'])) $settings['confirm_reply_name'] = $global_settings[$k];
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
            $mail = SUPER_Common::email( $to, $from, $from_name, $custom_reply, $reply, $reply_name, $cc, $bcc, $subject, $email_body, $settings, $confirm_attachments, $confirm_string_attachments );

            // Return error message
            if( !empty( $mail->ErrorInfo ) ) {
                $msg = esc_html__( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
                SUPER_Common::output_message( $error=true, $msg );
            }
        }
        if( $form_id!=0 ) {

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
            if( empty($settings['form_post_option']) ) $settings['form_post_option'] = '';
            if( empty($settings['form_post_custom']) ) $settings['form_post_custom'] = '';
            if( $settings['form_post_option']=='true' && $settings['form_post_custom']=='true' ) {
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
                    self::disarm_owned_upload_cleanup();
                    SUPER_Common::output_message(
                        $error = false,
                        $msg = '<strong>POST data:</strong><br /><textarea style="min-height:150px;width:100%;font-size:12px;">' . $parameters_output . '</textarea><br /><br /><strong>Response:</strong><br /><textarea style="min-height:150px;width:100%;font-size:12px;">' . $response['body'] . '</textarea>',
                        $redirect = false
                    );
                }
            }

            // Legacy sessionless mode never persists a browser session, so no
            // client-data write below may publish _sfs_id/_sfsdata_* artifacts.
            $sessionless_submission = self::request_uses_sessionless_submission_mode( $form_id );

            // Clear form progression
            if( !$sessionless_submission ) {
                SUPER_Common::setClientData( array( 'name' => 'progress_' . $form_id, 'value' => false ) );
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
                'attachments' => (isset($attachments) ? $attachments : array()),
                'confirm_attachments' => (isset($confirm_attachments) ? $confirm_attachments : array()),
                'string_attachments' => (isset($string_attachments) ? $string_attachments : array())
            );
            $attachments = apply_filters( 'super_attachments_filter', $attachments, array( 'post'=>$_POST, 'data'=>$data, 'settings'=>$settings, 'entry_id'=>$contact_entry_id, 'attachments'=>$attachments ) );
            do_action( 'super_before_email_success_msg_action', array( 'post'=>$_POST, 'data'=>$data, 'settings'=>$settings, 'entry_id'=>$contact_entry_id, 'attachments'=>$attachments ) );

            // Delete only the exact server-owned objects resolved for this request. Client
            // attachment/path/subdir values and post-filter file-shaped data have no authority.
            if( !empty($settings['file_upload_submission_delete']) ) {
                $expected_parent = $contact_entry_id
                    ? absint($contact_entry_id)
                    : ($entry_id ? absint($entry_id) : 0);
                if( !self::delete_finalized_owned_uploads(
                    array_merge($owned_files, $retained_owned_files),
                    $expected_parent,
                    $form_id
                ) ) {
                    SUPER_Common::output_message( $error = true, esc_html__( 'Unable to delete uploaded file.', 'super-forms' ) );
                }
                self::disarm_owned_upload_cleanup();
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
                if( $save_msg==true && !$sessionless_submission ) {
                    SUPER_Common::setClientData( array( 'name'=> 'msg', 'value'=>$session_data  ) );
                }
            }
            if( (!empty($settings['form_post_option'])) && ($save_msg==true) ) {
                // Only store the message into a session if the form is submitted as a normal POST request.
                // This will not be the case when `custom parameter string for POST method` is enabled.
                // In this case we do not want to store the thank you message into a session because if we
                // navigate to a different page it would show the thank you message again to the user while
                // it was already displayed to the user
                if( $settings['form_post_custom']!=='true' && !$sessionless_submission ) {
                    SUPER_Common::setClientData( array( 'name'=> 'msg', 'value'=>$session_data  ) );
                }
            }
            if($save_msg==false) $msg = '';

            /** 
             *  Filter to control the redirect URL
             *  e.g. Currenlty used for Front-end Posting to redirect to the created post
             *
             *  @param  array  $data
             *  @param  array  $settings
             *
             *  @since      4.3.0
            */
            $redirect = apply_filters( 'super_redirect_url_filter', $redirect, array( 'data'=>$data, 'settings'=>$settings ) );
            self::disarm_owned_upload_cleanup();
            
            $response_data['sf_nonce'] = self::request_uses_sessionless_submission_mode($form_id) ? '' : SUPER_Common::generate_nonce();
            if( $contact_entry_id ) {
                $entry_access_entry = get_post(absint($contact_entry_id));
                if( ($entry_access_entry instanceof WP_Post)
                    && $entry_access_entry->ID===absint($contact_entry_id)
                    && $entry_access_entry->post_type==='super_contact_entry'
                    && absint($entry_access_entry->post_parent)===absint($form_id) ) {
                    SUPER_Common::issue_entry_access_credential($entry_access_entry);
                }
            }
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
