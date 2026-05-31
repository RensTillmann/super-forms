<?php
/**
 * Super Forms
 *
 * @package   Super Forms
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Super Forms - Drag & Drop Form Builder
 * Description:       The most advanced, flexible and easy to use form builder for WordPress!
 * Version:           6.3.312
 * Plugin URI:        http://f4d.nl/super-forms
 * Author URI:        http://f4d.nl/super-forms
 * Author:            feeling4design
 * Text Domain:       super-forms
 * Domain Path:       /i18n/languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 4.9
 * Requires PHP:      5.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Forms')) :


    /**
     * Main SUPER_Forms Class
     *
     * @class SUPER_Forms
     */
    final class SUPER_Forms {
    
        
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $version = '6.3.312';
        public $slug = 'super-forms';
        public $apiUrl = 'https://api.super-forms.com/';
        public $apiVersion = 'v1';
        
        /**
         * @var array
         *
         *  @since      1.1.8
        */
        public $common_i18n;


        /**
         * @var array
         *
         *  @since      1.1.6
        */
        public $elements_i18n;


        /**
         * @var array
         *
         *  @since      4.2
        */
        public $global_settings;
        public $default_settings;


        /**
         * @var string
         *
         *  @since      1.3
        */
        public $form_custom_css;


        /**
         * @var string
         *
         *  @since      4.2.0
        */
        public $theme_custom_js;

       
        /**
         * @var SUPER_Forms The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Contains an array of registered script handles
         *
         * @var array
         *
         *  @since      1.0.0
        */
        private static $scripts = array();
        
        
        /**
         * Contains an array of localized script handles
         *
         * @var array
         *
         *  @since      1.0.0
        */
        private static $wp_localize_scripts = array();
        
        
        /**
         * Main SUPER_Forms Instance
         *
         * Ensures only one instance of SUPER_Forms is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Forms()
         * @return SUPER_Forms - Main instance
         *
         *  @since      1.0.0
        */
        public static function instance() {
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        
        /**
         * SUPER_Forms Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
            do_action('super_loaded');
        }

        
        /**
         * Define SUPER_Forms Constants
         *
         *  @since      1.0.0
        */
        private function define_constants(){
            
            // define plugin info
            $this->define( 'SUPER_PLUGIN_NAME', 'Super Forms' );
            $this->define( 'SUPER_PLUGIN_FILE', plugin_dir_url( __FILE__ ) ); // http://domain.com/wp-content/plugins/super-forms/
            $this->define( 'SUPER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // super-forms/super-forms.php
            $this->define( 'SUPER_PLUGIN_DIR', dirname( __FILE__ ) ); // /home/domains/domain.com/public_html/wp-content/plugins/super-forms
            $this->define( 'SUPER_VERSION', $this->version );
            $this->define( 'SUPER_API_ENDPOINT', $this->apiUrl . $this->apiVersion );
            $this->define( 'SUPER_API_VERSION', $this->apiVersion );
            $this->define( 'SUPER_WC_ACTIVE', in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) );
            $this->define( 'SUPER_FORMS_UPLOAD_DIR', apply_filters( 'super_forms_upload_dir_filter', str_replace(ABSPATH, '', WP_CONTENT_DIR) . '/uploads/superforms' ) );
            
        }

        
        /**
         * Define constant if not already set
         *
         * @param  string $name
         * @param  string|bool $value
         *
         *  @since      1.0.0
        */
        private function define($name, $value){
            if(!defined($name)){
                define($name, $value);
            }
        }

        
        /**
         * What type of request is this?
         *
         * string $type ajax, frontend or admin
         * @return bool
         *
         *  @since      1.0.0
        */
        public static function is_request($type){
            switch ($type){
                case 'admin' :
                    return is_admin();
                case 'ajax' :
                    return defined( 'DOING_AJAX' );
                case 'cron' :
                    return defined( 'DOING_CRON' );
                case 'frontend' :
                    return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
            }
        }

        
        /**
         * Include required core files used in admin and on the frontend.
         *
         *  @since      1.0.0
        */
        public function includes(){
            
            include_once( 'includes/class-common.php' );
             
            if ( $this->is_request( 'admin' ) ) {
                include_once( 'includes/class-install.php' );
                include_once( 'includes/class-menu.php' );
                include_once( 'includes/class-pages.php' );
                include_once( 'includes/class-settings.php' );
                include_once( 'includes/class-shortcodes.php' );
                include_once( 'includes/class-field-types.php' );
            }

            if ( $this->is_request( 'ajax' ) ) {
                $this->ajax_includes();
            }

            if ( $this->is_request( 'frontend' ) ) {
                include_once( 'includes/class-shortcodes.php' );
            }
            
            // Registers post types
            include_once('includes/class-post-types.php');

        }


        /**
         * Include add-ons
         *
         *  @since      1.0.0
        */        
        public static function include_add_ons(){
            // Include Add-ons
            $directory = SUPER_PLUGIN_DIR . '/add-ons';
            $folders = array_diff(scandir($directory), array('..', '.'));
            foreach($folders as $k => $v){
                include_once('add-ons/'.$v.'/'.$v.'.php');
            }
        }


        /**
         * Hook into actions and filters
         *
         *  @since      1.0.0
        */
        private function init_hooks() {

            // Add minute schedule for cron system
            add_filter( 'cron_schedules', array( $this, 'minute_schedule' ) );

            add_action( 'wp', array( $this, 'super_client_data_register_garbage_collection' ) );
            add_action( 'super_client_data_garbage_collection', array( $this, 'super_client_data_cleanup' ) );
            
            add_action( 'plugins_loaded', array( $this, 'include_add_ons' ), 0 );

            include_once( 'elementor/elementor-super-forms-extension.php' );
            add_action( 'plugins_loaded', array( $this, 'include_extensions'), 0);

            register_activation_hook( __FILE__, array( 'SUPER_Install', 'install' ) );
            
            // @since 1.9
            register_deactivation_hook( __FILE__, array( 'SUPER_Install', 'deactivate' ) );

            // Actions since 1.0.0
            add_action( 'init', array( $this, 'init' ), 0 );
            add_action( 'init', array( $this, 'register_shortcodes' ) );
			add_action( 'parse_request', array( $this, 'sfapi'));
            

            // Filters since 4.8.0
            add_filter( 'post_types_to_delete_with_user', array( $this, 'post_types_to_delete_with_user'), 10, 2 );
            
            if ( $this->is_request( 'frontend' ) ) {

                add_action( 'wp_head', array( $this, 'ga_tracking_code' ), 1 );
    
                // Filters since 1.0.0
                add_filter( 'widget_text', 'do_shortcode', 100 );

                // Actions since 1.0.6
                add_action( 'loop_start', array( $this, 'print_message_before_content' ) );
                add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_message_scripts' ) );

                /**
                 * Check if this site uses Ajax calls to generate content dynamically
                 * If this is the case make sure the styles and scripts for the element(s) are loaded
                 *
                 *  @since      1.1.9.5
                */
                add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_scripts_before_ajax' ) );
                
                /**
                 * Make sure the custom styles are loaded at the very end
                 * This way we don't have to use !important tags (which is always a good thing for extra flexibility)
                 *
                 *  @since      1.3
                */
                add_action( 'wp_footer', array( $this, 'add_form_styles' ), 500 );

                // @since 4.2.0 - add custom JS script
                add_action( 'wp_footer', array( $this, 'add_form_scripts' ), 500 );

            }
            
            if ( $this->is_request( 'admin' ) ) {

                // Actions since 1.0.0
                add_action( 'admin_menu', 'SUPER_Menu::register_menu' );
                add_action( 'current_screen', array( $this, 'after_screen' ), 0 );
                add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                add_action( 'admin_print_scripts', array( $this, 'localize_printed_scripts' ), 5 );
                add_action( 'admin_print_footer_scripts', array( $this, 'localize_printed_scripts' ), 5 );
                add_action( 'admin_action_duplicate_super_form', array( $this, 'duplicate_form_action' ) );
                add_action( 'admin_action_duplicate_super_contact_entry', array( $this, 'duplicate_contact_entry_action' ) );
                add_action( 'init', array( $this, 'custom_contact_entry_status' ) );
                add_action( 'admin_footer-post.php', array( $this, 'append_contact_entry_status_list' ) );
                
                // Actions since 1.2.6
                add_action( 'init', array( $this, 'update_plugin' ) );

                // Actions since 1.7
                add_action( 'restrict_manage_posts', array( $this, 'contact_entry_filter_form_dropdown' ) );
                add_action( 'restrict_manage_posts', array( $this, 'contact_entry_filter_date_range' ) );
  
                // Actions since 3.1.0
                add_action( 'before_delete_post', array( $this, 'delete_form_backups' ) );

                // Actions since 3.4.0
                add_action( 'all_admin_notices', array( $this, 'show_whats_new' ) );

                // Actions since 4.0.0
                add_action( 'all_admin_notices', array( $this, 'show_admin_notices' ) );

            }
            
            if ( $this->is_request( 'ajax' ) ) {
            }

            // Filters since 3.6.0 - filter to apply if statements on emails
            add_filter( 'super_before_sending_email_body_filter', array( $this, 'email_if_statements' ), 10, 2 );
            add_filter( 'super_before_sending_confirm_body_filter', array( $this, 'email_if_statements' ), 10, 2 );

            // Actions since 1.2.7
            add_action( 'phpmailer_init', array( $this, 'add_string_attachments' ) );

            // Actions since 3.3.0
            add_action( 'vc_before_init', array( $this, 'super_forms_addon' ) );


            // @since 4.7.0 - trigger onchange for tinyMCE editor, this is used for the Calculator element to count words
            add_filter('tiny_mce_before_init', array( $this, 'onchange_tinymce' ) );
            add_filter('super_form_before_do_shortcode_filter', array( $this, 'before_do_shortcode' ), 100, 2 );

            
            // @since 4.7.2 - option to delete attachments after deleting contact entry
            add_action( 'before_delete_post', array( $this, 'delete_entry_attachments' ) );

            add_action( 'upgrader_process_complete', array( $this, 'api_post_update' ), 10, 2);
            register_activation_hook( __FILE__, array( $this, 'api_post_activation' ) );
            register_deactivation_hook( __FILE__, array( $this, 'api_post_deactivation' ) );

            // Hide file uploads from Media Library
            add_action( 'pre_get_posts', array( $this, 'hide_uploads_from_media_library_list_view' ) );
            add_filter( 'ajax_query_attachments_args', array( $this, 'hide_uploads_from_media_grid_and_overlay_view' ) );

            // Rewrite rule to retrieve secure file uploads
            add_action( 'init', array( $this, 'rewrite_rules' ) );
            add_action( 'query_vars', array( $this, 'query_vars' ) );
            add_filter( 'parse_request', array( $this, 'parse_request' ) );

            // Allow text/plain MIME type for export/import
            add_filter( 'wp_check_filetype_and_ext', function($types, $file, $filename, $mimes) {
                if(false !== strpos( $filename, '.txt' ) ) {
                    $types['ext'] = 'txt';
                    $types['type'] = 'text/plain';
                }
                return $types;
            }, 10, 4 );
            add_filter( 'upload_mimes', function($mimes){
                $mimes['txt'] = 'text/plain';
                return $mimes;
            });

            // Delete deprecated folders
            $deprecatedFolders = array(
                'includes/sessions', // no longer used since new session system
                'uploads', 'u' // no longer needed since new file upload system
            );
            foreach($deprecatedFolders as $folder){
                $path = trailingslashit(SUPER_PLUGIN_DIR) . $folder;
                if(is_dir($path)){
                    SUPER_Common::delete_dir( $path );
                }
            }

            // Delete deprecated files
            $deprecatedFiles = array(
                'includes/class-super-session.php' // no longer used since new session system
            );
            foreach($deprecatedFiles as $file){
                $path = trailingslashit(SUPER_PLUGIN_DIR) . $file;
                SUPER_Common::delete_file( $path );
            }
        }

        /**
         * Add minute schedule for cron system
         *
         *  @since      1.0.0
        */
        public static function minute_schedule( $schedules ) {
            $schedules['every_minute'] = array(
                'interval' => 60,
                'display' => esc_html__( 'Every minute', 'super-forms' )
            );
            return $schedules;
        }

        public static function super_client_data_cleanup() {
            if(defined('WP_SETUP_CONFIG')) return;
            if(!defined('WP_INSTALLING')) SUPER_Common::deleteOldClientData();
            do_action( 'super_client_data_cleanup' );
        }

        public static function super_client_data_register_garbage_collection() {
            if(!wp_next_scheduled('super_client_data_garbage_collection')){
                wp_schedule_event(time(), 'every_minute', 'super_client_data_garbage_collection');
            }
        }

        public static function allow_txt_mime_type($mimes, $user){
            $mimes['txt'] = 'text/plain';
            return $mimes;
        }

        public static function add_custom_wc_my_account_menu_items( $menu ){
            $global_settings = SUPER_Common::get_global_settings();
            if(empty($global_settings['wc_my_account_menu_items'])) $global_settings['wc_my_account_menu_items'] = '';
            $wc_my_account_menu_items = explode("\n", $global_settings['wc_my_account_menu_items']);
            foreach( $wc_my_account_menu_items as $v ) {
                $v = explode('|', $v);
                // form-submissions|Form Submissions|[super_listings list="1" id="54751"]|3
                if(!isset($v[0]) || !isset($v[1]) || !isset($v[2]) ) continue;
                // by default add menu item to the end of the current menu array
                if(empty($v[3])) $v[3] = 0; 
                // by default we do not set a custom URL to redirect to
                if(empty($v[4])) $v[4] = '';
                $menu_slug = $v[0]; 
                add_rewrite_endpoint( $menu_slug, EP_PAGES );
                //add_rewrite_endpoint( 'form-submissions', EP_PAGES );
                $menu_title = $v[1];
                $menu_content = $v[2];
                $menu_position = absint($v[3]);
                $menu_url = $v[4]; 
                if(empty(trim($menu_url))){
                    add_action( 'woocommerce_account_' . $menu_slug . '_endpoint', function() use ($menu_content) {
                        echo do_shortcode($menu_content);
                    });
                }
                $new = array( $menu_slug => $menu_title );
                // Add new menu item between the other ones
                $menu = array_slice( $menu, 0, $menu_position, true ) + $new + array_slice( $menu, $menu_position, NULL, true );
            }
            return $menu;
        }
        public static function add_custom_wc_my_account_menu_item_endpoint( $url, $endpoint, $value, $permalink ){
            $global_settings = SUPER_Common::get_global_settings();
            if(empty($global_settings['wc_my_account_menu_items'])) $global_settings['wc_my_account_menu_items'] = '';
            $wc_my_account_menu_items = explode("\n", $global_settings['wc_my_account_menu_items']);
            foreach( $wc_my_account_menu_items as $v ) {
                $v = explode('|', $v);
                // form-submissions|Form Submissions|[super_listings list="1" id="54751"]|3|https://domain.com/custom-page-url
                if(!isset($v[4])) continue;
                $menu_slug = $v[0]; 
                $menu_url = $v[4]; 
                if(!empty(trim($menu_url))){
                    if($endpoint === $menu_slug){
                        return $menu_url;
                    }
                }
            }
            return $url;
        }
        public static function include_extensions(){
            // Include extensions
            $directory = SUPER_PLUGIN_DIR . '/includes/extensions';
            $folders = array_diff(scandir($directory), array('..', '.'));
            foreach($folders as $k => $v){
                include_once('includes/extensions/'.$v.'/'.$v.'.php');
            }
        }
        public function rewrite_rules(){
            add_rewrite_rule(
                'sfdlfi\/(.*)', // sfdlfi stands for "super forms download file"
                'index.php?sfdlfi=$matches[1]', 
                'top' 
            );
            add_rewrite_rule(
                'sfgtfi\/(.*)', // sfgtfi stands for "super forms get file"
                'index.php?sfgtfi=$matches[1]', 
                'top' 
            );
            if(!get_option('_sf_permalinks_flushed')){
                flush_rewrite_rules(false);
                update_option('_sf_permalinks_flushed', 1);
            }
        }
        public function query_vars( $query_vars ){
            $query_vars[] = 'sfdlfi';
            $query_vars[] = 'sfgtfi';
            return $query_vars;
        }
        public function caching_headers($file, $timestamp) {
            // Example: Tue, 12 May 2020 22:17:04 GMT
            $last_modified = gmdate('D, d M Y H:i:s T', $timestamp); 
            // If the content has not changed, do not resend a full response
            // In other words: be more efficient and save bandwidth :)
            $etag = md5($timestamp . $file);
            header('ETag: "' . $etag . '"');
            // Determine if a resource received or stored is the same
            // Even though ETag is more accurate, add a 
            // "Last-Modified" header as a fallback method.
            header('Last-Modified: ' . $last_modified);
            // May be stored by any cache, 
            // even if the response is normally non-cacheable.
            header('Cache-Control: public');
            // The Expires header contains the date/time after which the response is considered stale.
            // "stale" means "not fresh"
            header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 86400*30 ) . ' GMT' ); // +30 days
            // Only send back the requested resource (with a 200 status)
            // if it has been last modified after the given date.
            // If the request has not been modified since, send back a 304 without any body
            // Unlike "If-Unmodified-Since", "If-Modified-Since" can only be used with a GET or HEAD.
            if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) || isset($_SERVER['HTTP_IF_NONE_MATCH']) ) {
                $clientEtag = str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH']));
                if( ($_SERVER['HTTP_IF_MODIFIED_SINCE']==$last_modified) || ($clientEtag==$etag) ) {
                    status_header(304);
                    header("Vary: Accept-Encoding,User-Agent");
                    exit();
                }
            }
        }

        public static function filter_upload_dir($dirs){
            if(!empty($GLOBALS['super_upload_dir'])){
                return $GLOBALS['super_upload_dir'];
            }
            $global_settings = SUPER_Common::get_global_settings();
            $defaults = SUPER_Settings::get_defaults($global_settings);
            $global_settings = array_merge( $defaults, $global_settings );
            $upload_folder = $global_settings['file_upload_dir'];
            if(!isset($settings['file_upload_use_year_month_folders']) || !empty($settings['file_upload_use_year_month_folders'])) {
                $upload_folder = $global_settings['file_upload_dir'] . $dirs['subdir'];
            }
            $upload_dir = ABSPATH . $upload_folder;
            $folderResult = SUPER_Common::generate_random_folder($upload_dir);
            $upload_folder = $upload_folder . '/' . $folderResult['folderName'];
            $siteurl = get_option('siteurl');
            $dirs['path'] = $folderResult['folderPath'];
            $dirs['url'] = trailingslashit($siteurl) . $upload_folder;
            $dirs['subdir'] = $upload_folder;
            if(substr($upload_folder, 0, 1) !== '/') {
                $dirs['subdir'] = '/'.$upload_folder;
            }
            $dirs['basedir'] = ABSPATH;
            $dirs['baseurl'] = $siteurl;
            return $dirs;
        }
        public static function filter_mime_types($mime_types){
            // Add MS Word .doc files mime type to WordPress.
            $mime_types['doc'] = 'application/msword'; // Adding .doc extension
            // Add AI files mime type to WordPress.
            //$mime_types['ai'] = 'application/pdf'; // Adding .ai extension
            return $mime_types;
        }


        public function parse_request( &$wp ) {
            if ( array_key_exists( 'sfdlfi', $wp->query_vars ) ) {
                if ( ! current_user_can( 'export' ) ) {
                    wp_die( __( 'Sorry, you are not allowed to export the content of this site.' ) );
                }
                $fileLocation = $wp->query_vars['sfdlfi'];
                $url = wp_get_attachment_url( $fileLocation );
                if(empty($url)){
                    header("HTTP/1.1 404 Not Found");
                    exit;
                }
                $request = wp_safe_remote_get($url);
                if ( is_wp_error( $request ) ) {
                    header("HTTP/1.1 404 Not Found");
                    exit;
                }
                $content = wp_remote_retrieve_body( $request );

                // Delete the export data
                wp_delete_attachment( $fileLocation, true );
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename=' . basename($url) );
                header('Content-Type: text/txt; charset=' . get_option( 'blog_charset' ), true );
                echo $content;
                exit;
            }
            if ( array_key_exists( 'sfgtfi', $wp->query_vars ) ) {
                // Get settings
                $settings = SUPER_Common::get_form_settings(0);
                // Check if user must be logged in to download the file
                $auth = true;
                if(!empty($settings['file_upload_auth'])){
                    if ( !is_user_logged_in() ) {
                        $auth = false;
                    }else{
                        // Also check for roles (if defined)
                        if(!empty($settings['file_upload_auth_roles'])){
                            global $current_user;
                            $allowed_roles = explode(',', $settings['file_upload_auth_roles']);
                            $allowed_roles = array_map('trim', $allowed_roles);
                            $allowed_roles = array_filter($allowed_roles);
                            // Only check if there are any roles, otherwise allow all logged in users
                            if(count($allowed_roles)>0){
                                $allowed = false;
                                foreach( $current_user->roles as $v ) {
                                    if( in_array( $v, $allowed_roles ) ) {
                                        $allowed = true;
                                        break;
                                    }
                                }
                                // Not allowed
                                if($allowed===false) $auth = false; 
                            }
                        }
                    }
                }
                if($auth===false){
                    auth_redirect();
                }
                $fileLocation = $wp->query_vars['sfgtfi'];
                // Check if this file was uploaded via the new file upload system (v5.0.0+)
                // This is true if the subdir is 13 digits long
                $new = false;
                $re = '/[0-9]{13}\/.*\..*/m';
                preg_match_all($re, $fileLocation, $matches, PREG_SET_ORDER, 0);
                if($matches) $new = true;
                $re = '/[0-9]{4}\/[0-9]{2}\/[0-9]{13}\/.*\..*/m';
                preg_match_all($re, $fileLocation, $matches, PREG_SET_ORDER, 0);
                if($matches) $new = true;
                if($new){
                    // Was uploaded with the new file upload system...
                    $file = ABSPATH . str_replace('__/', '../', $wp->query_vars['sfgtfi']);
                    $file = urldecode(urlencode($file));
                    if(!is_file($file)) {
                        status_header(404);
                        exit;
                    }
                }else{
                    // Get settings
                    $settings = SUPER_Common::get_form_settings(0);
                    // Default to super forms directory
                    $uploadPath = SUPER_FORMS_UPLOAD_DIR;
                    if(!empty($settings['file_upload_dir'])){
                        // User defined directory
                        $uploadPath = ABSPATH . $settings['file_upload_dir'];
                    }
                    $file =  wp_normalize_path(trailingslashit($uploadPath) . $fileLocation);
                    $file = urldecode( $file );
                    if (!$uploadPath || !is_file($file)) {
                        status_header(404);
                        exit;
                    }
                }
                $mime = wp_check_filetype($file);
                if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) ) {
                    $mime[ 'type' ] = mime_content_type( $file );
                }
                if($mime['type']) {
                    $mimetype = $mime['type'];
                }else{
                    $mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
                }
                header( 'Content-Type: ' . $mimetype ); // always send this
                if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) ) {
                    header( 'Content-Length: ' . filesize( $file ) );
                }
                self::caching_headers($file, filemtime($file));
                // If we made it this far, just serve the file
                readfile( $file );
                exit();
            }
            return;
        }
        // Hide file uploads in list view (Media Library)
        public function hide_uploads_from_media_library_list_view($query) {
            if ( ! is_admin() )  return;
            if ( ! $query->is_main_query() ) return;
            if ( !function_exists( 'get_current_screen' ) ) return;
            $screen = get_current_screen();
            if ( !$screen || $screen->id !== 'upload' ||  $screen->post_type !== 'attachment' ) {
                return;
            }
            $global_settings = SUPER_Common::get_global_settings();
            $defaults = SUPER_Settings::get_defaults($global_settings);
            $global_settings = array_merge( $defaults, $global_settings );
            if(!empty($global_settings['file_upload_hide_from_media_library'])){
                $query->set( 'meta_query', array(
                    array(
                        'key' => '_wp_attached_file',
                        'value' => 'superforms', // This is a fallback method for old super forms versions < 4.9.435
                        'compare' => 'NOT LIKE'
                    ),
                    array(
                        'key' => 'super-forms-form-upload-file',
                        'compare' => 'NOT EXISTS' // Exclude super forms files
                    )
                ) );
            }

        }
        // Hide file uploads in grid view (Media Library) and from overlay view (popup)
        public function hide_uploads_from_media_grid_and_overlay_view( $args ) {
            if ( ! is_admin() )  return;
            $global_settings = SUPER_Common::get_global_settings();
            $defaults = SUPER_Settings::get_defaults($global_settings);
            $global_settings = array_merge( $defaults, $global_settings );
            if(!empty($global_settings['file_upload_hide_from_media_library'])){
                $args['meta_query'] = array(
                    array(
                        'key' => '_wp_attached_file',
                        'value' => 'superforms', // This is a fallback method for old super forms versions < 4.9.435
                        'compare' => 'NOT LIKE'
                    ),
                    array(
                        'key' => 'super-forms-form-upload-file',
                        'compare' => 'NOT EXISTS' // Exclude super forms files
                    )
                );
            }
            return $args;
        }
        public function api_post_activation() {
            self::api_post('activation');
        }
        public function api_post_deactivation() {
            self::api_post('deactivation');
        }
        public function api_post_update( $upgrader_object, $options ) {
            $current_plugin_path_name = plugin_basename( __FILE__ );
            if ($options['action'] == 'update' && $options['type'] == 'plugin' ){
                if( (isset($options['plugins'])) && (is_array($options['plugins'])) ) {
                    foreach($options['plugins'] as $each_plugin){
                        if ($each_plugin==$current_plugin_path_name){
                            // Delete deprecated files
                            $deprecatedFiles = array(
                                'includes/ajax-handler.php'
                            );
                            foreach($deprecatedFiles as $file){
                                $path = trailingslashit(SUPER_PLUGIN_DIR) . $file;
                                if(is_file($path)) unlink($path);
                            }
                            // Send API request that the plugin has been updated
                            self::api_post('update');
                        }
                    }
                }
            }
        }
        public function api_post($type) {
            $userEmail = SUPER_Common::get_user_email();
            $response = wp_remote_post(
                SUPER_API_ENDPOINT . '/plugin/'.$type, // activation, deactivation
                array(
                    'method' => 'POST',
                    'timeout' => 45,
                    'data_format' => 'body',
                    'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
                    'body' => json_encode(array(
                        'home_url' => get_home_url(),
                        'site_url' => site_url(),
                        'email' => $userEmail,
                        'addons_activated' => array('super-forms'=>SUPER_VERSION),
                        'version' => $this->version,
                        'type' => $type
                    ))
                )
            );
        }

        public function onchange_tinymce( $init ) {
            ob_start();
            echo 'function(editor){
                var word_count_timeout = null;
                editor.on("keyup blur", function(e){
                    // Check if this Editor belongs to super forms
                    if( editor.container.closest(".super-forms") ) {
                        var $this = this, $time = 250, $text, $words, $removeNoneChars, $chars, $allChars;
                        if(e.type!="keyup") $time = 0;
                        if (word_count_timeout !== null) {
                            clearTimeout(word_count_timeout);
                        }
                        word_count_timeout = setTimeout(function () {
                            $text = editor.getContent();
                            $words = $text.match(/\S+/g);
                            $words = $words ? $words.length : 0;
                            $removeNoneChars = $text.replace(/\s/g, ""); // use the \s quantifier to remove all white space, is equivalent to [\r\n\t\f\v ]
                            $chars = $removeNoneChars.length; // count only characters after removing any whitespace, tabs, linebreaks etc.
                            $allChars = $text.length; // count all characters
                            console.log($words);
                            console.log($chars);
                            console.log($allChars);
                            jQuery($this.targetElm).attr("data-word-count", $words);
                            jQuery($this.targetElm).attr("data-chars-count", $chars);
                            jQuery($this.targetElm).attr("data-allchars-count", $allChars);
                            SUPER.after_field_change_blur_hook({el: $this.targetElm});
                        }, $time);
                        return false;
                    }
                });
            }';
            $init['setup'] = ob_get_contents();
            ob_end_clean();
            return $init;
        }

        public function before_do_shortcode( $result, $atts ) {
            return $result . SUPER_Common::get_transient(array('slug'=>'before_do_shortcode'.(current_user_can('manage_options') ? '_admin' : '')));
        }

        // When deleting a user with the option "Delete all content" we must also include contact entries and forms created by this user. 
        public static function post_types_to_delete_with_user($post_types, $user_id) {
            $post_types[] = 'super_form';
            $post_types[] = 'super_contact_entry';
            return $post_types;
        }

        // If enabled, delete all attachments related to this contact entry
        public static function delete_entry_attachments( $post_id ) {
            // First check if this is a contact entry
            if( get_post_type($post_id)=='super_contact_entry' ) {
                $global_settings = SUPER_Common::get_global_settings();
                if(!empty($global_settings['file_upload_entry_delete'])){
                    $attachments = get_attached_media( '', $post_id );
                    foreach( $attachments as $attachment ) {
                        // Force delete this attachment
                        wp_delete_attachment( $attachment->ID, true );
                    }
                    // Must also delete private uploaded files (if any)
                    $contact_entry_data = get_post_meta( $post_id, '_super_contact_entry_data', true );
                    if( is_array($contact_entry_data) ) {
                        foreach( $contact_entry_data as $k => $v ) {
                            if( isset($v['type']) && ($v['type']=='files') ) {
                                if( isset( $v['files'] ) ) {
                                    // Delete possible generated PDF file
                                    foreach( $v['files'] as $fk => $fv ) {
                                        if($k==='_generated_pdf_file'){
                                            if(!empty($fv['url'])){
                                                // Try to delete it
                                                SUPER_Common::delete_dir( $fv['url'] );
                                            }
                                        }else{
                                            if(!empty($fv['path'])){
                                                // Try to delete it
                                                SUPER_Common::delete_dir( $fv['path'] );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Add google analytics tracking code
         *
         *  @since      3.6.0
        */
        public static function ga_tracking_code() {
            $global_settings = SUPER_Common::get_global_settings();
            if( (!empty($global_settings['form_enable_ga_tracking'])) && (!empty($global_settings['form_ga_code'])) ) {
                echo '<!-- Super Forms - Google Tracking Code -->';
                echo '<script>' . stripslashes( $global_settings['form_ga_code'] ) . '</script>';
                echo '<!-- End Super Forms - Google Tracking Code -->';
            }
        }

        /**
         * Apply email if statements
         *
         *  @since      3.6.0
        */
        public static function email_if_statements($email_body, $data) {
            
            // The following function parses E-mail If Statements and E-mail foreach loops
            // Refer the documentation for more info about this:
            // https://renstillmann.github.io/super-forms/#/email-if-statements
            // https://renstillmann.github.io/super-forms/#/email-foreach-loops

            // Regex to do foreach loop for dynamic column fields
            $regex = '/foreach\s?\(\s?[\'|"|\s|]?(.*?)[\'|"|\s|]?\)\s?:([\s\S]*?)(?:endforeach\s?;)/';
            $match = preg_match_all($regex, $email_body, $matches, PREG_SET_ORDER, 0);
            $fileLoopRows = array();
            foreach($matches as $k => $v){
                $original = $v[0];
                $field_name = $v[1];
                $splitName = explode(';', $field_name);
                $field_name = $splitName[0];
                $value_n = (isset($splitName[1]) ? $splitName[1] : '');
                $original_field_name = $field_name;
                $return = '';
                if( isset( $v[2] ) ) $return = $v[2];
                if($return==='') continue;
                $i = 1;
                $rows = '';
                while( isset( $data['data'][$field_name] ) ){
                    $row_regex = '/<%(.*?)%>/';
                    $row = $return;
                    $row_match = preg_match_all($row_regex, $row, $row_matches, PREG_SET_ORDER, 0);
                    foreach($row_matches as $rk => $rv){
                        if($value_n==='loop'){
                            // Loop over all current files
                            $files = $data['data'][$field_name]['files'];
                            if(!isset($fileLoopRows[$field_name])) $fileLoopRows[$field_name] = array();
                            foreach($files as $x => $fv){
                                if(!isset($fileLoopRows[$field_name][$x])) $fileLoopRows[$field_name][$x] = $row;
                                if($rv[1]==='counter'){
                                    $fileLoopRows[$field_name][$x] = str_replace( $rv[0], $x+1, $fileLoopRows[$field_name][$x]);
                                    continue;
                                }
                                $fileLoopRows[$field_name][$x] = str_replace( $rv[0], '{'.$field_name.';'.$rv[1].'['.($x).']}', $fileLoopRows[$field_name][$x]);
                            }
                        }else{
                            if($rv[1]==='counter'){
                                $row = str_replace( $rv[0], $i, $row);
                                continue;
                            }
                            if($i<2){
                                $row = str_replace( $rv[0], '{'.$rv[1].'}', $row);
                                continue;
                            }
                            $splitName = explode(';', $rv[1]);
                            $newName = $splitName[0].'_'.$i;
                            if(count($splitName)>1){
                                $newName .= ';'.$splitName[1];
                            }
                            $row = str_replace( $rv[0], '{'.$newName.'}', $row);
                        }
                    }
                    if($value_n==='loop'){
                        foreach($fileLoopRows[$field_name] as $value){
                            $rows .= $value;
                        }
                    }else{
                        $rows .= $row;
                    }
                    $i++;
                    $field_name = $original_field_name.'_'.$i;
                }
                $rows = SUPER_Common::email_tags( $rows, $data['data'], $data['settings'] );
                $email_body = str_replace( $original, $rows, $email_body);
            }

            // Regex to check if field was submitted (with isset and !isset)
            $regex = '/!isset\s?\(\s?[\'|"|\s|]?(.*?)[\'|"|\s|]?\)\s?:([\s\S]*?)(?:endif\s?;|(?:elseif\s?:([\s\S]*?))endif\s?;)/';
            $match = preg_match_all($regex, $email_body, $matches, PREG_SET_ORDER, 0);
            foreach($matches as $k => $v){
                $original = $v[0];
                $field_name = $v[1];
                $true = '';
                $false = '';
                if( isset( $v[2] ) ) $true = $v[2];
                if( isset( $v[3] ) ) $false = $v[3];
                if(!isset($data['data'][$field_name])){
                    $statement = $true;
                }else{
                    $statement = $false;
                }
                $email_body = str_replace( $original, $statement, $email_body);
            }

            // Regex to check if field was submitted (with isset and !isset)
            $regex = '/isset\s?\(\s?[\'|"|\s|]?(.*?)[\'|"|\s|]?\)\s?:([\s\S]*?)(?:endif\s?;|(?:elseif\s?:([\s\S]*?))endif\s?;)/';
            $match = preg_match_all($regex, $email_body, $matches, PREG_SET_ORDER, 0);
            foreach($matches as $k => $v){
                $original = $v[0];
                $field_name = $v[1];
                $true = '';
                $false = '';
                if( isset( $v[2] ) ) $true = $v[2];
                if( isset( $v[3] ) ) $false = $v[3];
                if(isset($data['data'][$field_name])){
                    $statement = $true;
                }else{
                    $statement = $false;
                }
                $email_body = str_replace( $original, $statement, $email_body);
            }

            $email_body = SUPER_Common::filter_if_statements($email_body);
            return $email_body;
        }


        /**
         * Add super forms shortcode to visual composer elements
         *
         *  @since      3.3.0
        */
        public static function super_forms_addon($form_id) {

            // Get all Forms created with Super Forms (post type: super_form)
            $args = array(
                'post_type' => 'super_form', //We want to retrieve all the Forms
                'posts_per_page' => -1 //Make sure all matching forms will be retrieved
            );
            $forms = get_posts( $args );
            $forms_array = array();
            foreach( $forms as $k => $v ) {
                $forms_array['#' . $v->ID . ' - ' . $v->post_title] = $v->ID;
            }
            vc_map( array(
                'name' => esc_html__( 'Super Form', 'super-forms' ),
                'icon' => SUPER_PLUGIN_FILE . '/assets/images/vc_icon.png',
                'base' => 'super_form',
                'category' => esc_html__( 'Content', 'super-forms' ),
                'params' => array(
                    array(
                        'type' => 'dropdown',
                        'holder' => 'div',
                        'class' => '',
                        'heading' => esc_html__( 'Select your form', 'super-forms' ),
                        'param_name' => 'id',
                        'value' => $forms_array,
                        'description' => esc_html__( 'Choose the form you want to use.', 'super-forms' )
                    )
                )
            ) );
        }


        /**
         * Add form filter dropdown
         *
         *  @since      3.1.0
        */
        public static function delete_form_backups($form_id) {

            // We check if the global post type isn't ours and just return
            global $post_type;
            if ( $post_type != 'super_form' ) return;
     
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
        }
        

        /**
         * Add form filter dropdown
         *
         *  @since      1.7
        */
        public static function contact_entry_filter_form_dropdown($post_type) {
            if( $post_type=='super_contact_entry') {
                echo '<select name="super_form_filter">';
                $args = array(
                    'post_type' => 'super_form',
                    'posts_per_page' => -1
                );
                $forms = get_posts( $args );
                if(count($forms)==0){
                    echo '<option value="0">' . esc_html__( 'No forms found', 'super-forms' ) . '</option>';
                }else{
                    $super_form_filter = (isset($_GET['super_form_filter']) ? $_GET['super_form_filter'] : 0);
                    echo '<option value="0">' . esc_html__( 'All forms', 'super-forms' ) . '</option>';
                    foreach( $forms as $value ) {
                        echo '<option value="' . $value->ID . '" ' . ($value->ID==$super_form_filter ? 'selected="selected"' : '') . '>' . $value->post_title . '</option>';
                    }
                }
                echo '</select>';
            }
        }


        /**
         * Add date range filter
         *
         *  @since      4.4.5
        */
        public static function contact_entry_filter_date_range($post_type) {
            if( $post_type=='super_contact_entry') {
                $from = ( isset( $_GET['sffrom'] ) && $_GET['sffrom'] ) ? $_GET['sffrom'] : '';
                $to = ( isset( $_GET['sfto'] ) && $_GET['sfto'] ) ? $_GET['sfto'] : '';
                echo '<input autocomplete="false" type="text" name="sffrom" placeholder="Date From" value="' . esc_attr($from) . '" />';
                echo '<input autocomplete="false" type="text" name="sfto" placeholder="Date To" value="' . esc_attr($to) . '" />';
            }
        }


        /**
         * Add contact entry export button
         *
         *  @since      1.7
        */
        public static function contact_entry_export_button($post_type) {
            add_thickbox();
            echo '<div class="alignleft actions">';
            echo '<span style="margin-bottom:1px;margin-top:1px;" class="button super-export-entries">';
            echo esc_html__( 'Export to CSV', 'super-forms' );
            echo '</span>';
            echo '<a style="display:none;" href="#TB_inline?width=600&height=550&inlineId=super-export-entries-content" title="' . esc_attr__( 'Select & Sort the data that needs to be exported', 'super-forms' ) . '" class="thickbox super-export-entries-thickbox"></a>';
            echo '</div>';
            echo '<div id="super-export-entries-content" style="display:none;"></div>';
        }       


        /**
         * Add form custom CSS
         *
         *  @since      1.2.6
        */
        public static function add_form_styles() {
            if( isset(SUPER_Forms()->form_custom_css) ) {
                $css = SUPER_Forms()->form_custom_css;
                $global_css = '';
                if( isset(SUPER_Forms()->global_settings) ) {
                    if( isset(SUPER_Forms()->global_settings['theme_custom_css']) ) {
                        $global_css = stripslashes(SUPER_Forms()->global_settings['theme_custom_css']);
                    }
                }
                if( $css!='' ) echo '<style type="text/css">' . $global_css . $css . '</style>';
            }
        }


        /**
         * Add custom JS
         *
         *  @since      4.2.0
        */
        public static function add_form_scripts() {
            if( isset(SUPER_Forms()->theme_custom_js) ) {
                $js = SUPER_Forms()->theme_custom_js;
                if( $js!='' ) {
                    ?>
                    <script type="text/javascript">
                    //<![CDATA[
                        <?php echo stripslashes($js); ?>
                    //]]>
                    </script>
                    <?php
                }
            }
        }


        /**
         * Add string attachments
         *
         *  @since      1.2.6
        */
        function add_string_attachments( $phpmailer ) {
            $attachments = SUPER_Common::getClientData( 'string_attachments' );
            if( $attachments!=false ) {
                foreach( $attachments as $v ) {
                    $phpmailer->AddStringAttachment( base64_decode($v['data']), $v['filename'], $v['encoding'], $v['type'] );
                }
                SUPER_Common::setClientData( array( 'name'=> 'string_attachments', 'value'=>false  ) );
            }
        }


        /**
         * Show PHP version error if PHP below v5.4 is installed
         *
         *  @since      4.0.0
        */
        public function show_admin_notices() {
            if( version_compare(phpversion(), '5.4.0', '<') ) {
                echo '<div class="notice notice-error">'; // notice-success, notice-error
                echo '<p>';
                echo sprintf( esc_html__( '%sPlease note:%s Super Forms requires at least v5.4.0 or higher to be installed to work properly, your current PHP version is %s', 'super_forms' ), '<strong>', '</strong>', phpversion() );
                echo '</p>';
                echo '</div>';
            }
            // Add-ons should be deactivated when running Super Forms v6.0.0 or higher, these add-ons are now included into the main plugin
            if( version_compare(SUPER_VERSION, '6.0.0', '>=') ) {
                // check for plugin using plugin name
                $plugins = array(
                    'calculator' => 'Calculator',
                    'csv-attachment' => 'CSV Attachment',
                    'email-reminders' => 'E-mail Reminders',
                    'email-templates' => 'E-mail Templates',
                    'front-end-posting' => 'Front-end Posting',
                    'mailchimp' => 'Mailchimp',
                    'mailster' => 'Mailster',
                    'password-protect' => 'Password Protect',
                    'paypal' => 'Paypal',
                    'popups' => 'Popups',
                    'register-login' => 'Register & Login',
                    'signature' => 'Signature',
                    'woocommerce' => 'WooCommerce Checkout',
                    'zapier' => 'Zapier'
                );
                $addOnsActivated = array();
                $activePluginFound = false;
                foreach($plugins as $slug => $title){
                    if ( is_plugin_active( 'super-forms-'.$slug.'/super-forms-'.$slug.'.php' ) ) {
                        $activePluginFound = true;
                        $addOnsActivated[$slug] = $title . ' Add-on <strong style="color:red;">(activated, please deactivate and remove this plugin)</strong>';
                        continue;
                    } 
                    $addOnsActivated[$slug] = $title . ' Add-on <strong style="color:green;">(now included for free)</strong>';
                }
                if($activePluginFound===true){
                    echo '<div class="notice notice-error">'; // notice-success, notice-error
                    echo '<p>' .  sprintf( esc_html__( '%sPlease note:%s Since Super Forms v%s the below plugins are now included into the plugin by default. Please deactivate and remove the following Add-ons to avoid possible conflicts. Also make sure to %sactivate your copy%s of Super Forms.', 'super_forms' ), '<strong>', '</strong>', SUPER_VERSION, '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_addons') . '" class="button button-primary button-large">', '</a>');
                    echo '<ul>';
                    foreach($addOnsActivated as $msg){
                        echo '<li>- '.$msg. '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
            }
        }


        /**
         * Show what's new message
         *
         *  @since      3.4.0
        */
        public function show_whats_new() {
            $global_settings = SUPER_Common::get_global_settings();
            if(!isset($global_settings['backend_disable_whats_new_notice'])){
                $version = get_option( 'super_current_version', '1.0.0' );
                if( version_compare($version, $this->version, '<') ) {
                    update_option( 'super_current_version', $this->version );
                    echo '<div class="notice notice-success">'; // notice-success, notice-error
                        echo '<div class="super-demos-notice">';
                        echo '<div style="display:flex;padding: 20px 50px 20px 0px;">';
                            echo '<img style="height:100px;width:154px;" src="' . esc_url(SUPER_PLUGIN_FILE . '/assets/images/logo.jpg') . '" />';
                            echo '<div>';
                                echo '<p>';
                                    echo sprintf( esc_html__( 'Successfully updated Super Forms to v%s - %sCheck what\'s new!%s', 'super_forms' ), $this->version , '<a target="_blank" href="https://renstillmann.github.io/super-forms/#/changelog">', '</a>');
                                    echo sprintf( esc_html__( '%sDisable this notification%s', 'super-forms' ), '<a style="padding-left:15px;" target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>');
                                echo '</p>';
                                echo '<h1>' . esc_html__( 'What\'s new?', 'super-forms' ) . '</h1>';
                                echo '<hr />';
                                echo '<h2>' . sprintf( esc_html__( 'Listings Add-on %1$sBETA%2$s', 'super-forms' ), '<span style="color:red;">', '</span>' ) . '</h2>';
                                echo '<p><a target="_blank" href="https://webrehab.zendesk.com/hc/en-gb/sections/4405742210961-Listings-Add-on" class="button button-secondary button-large">' . esc_html__( 'Documentation', 'super-forms' ) . '</a> <a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_addons') . '" class="button button-primary button-large">' . esc_html__( 'Start 15 day trial', 'super-forms' ) . '</a></p>';
                                echo '<hr />';
                                echo '<h2>' . sprintf( esc_html__( 'PDF Generator Add-on %1$sBETA%2$s', 'super-forms' ), '<span style="color:red;">', '</span>' ) . '</h2>';
                                echo '<p><a target="_blank" href="https://webrehab.zendesk.com/hc/en-gb/sections/4404338396177-PDF-Generator" class="button button-secondary button-large">' . esc_html__( 'Documentation', 'super-forms' ) . '</a> <a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_addons') . '" class="button button-primary button-large">' . esc_html__( 'Start 15 day trial', 'super-forms' ) . '</a></p>';
                                echo '<hr />';
                                echo '<h2>' . sprintf( esc_html__( 'Secure File Uploads', 'super-forms' ), '<span style="color:red;">', '</span>' ) . '</h2>';
                                echo '<p>' . sprintf( esc_html__( 'By default any files uploaded via your forms will no longer be visible in the %1$sMedia Library%2$s. To change this behaviour you can visit the File Upload Settings.', 'super-forms'), '<a target="_blank" href="' . esc_url(get_admin_url() . 'upload.php') . '">', '</a>') . '</p>';
                                echo '<p><a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#file-upload-settings') . '" class="button button-primary button-large">' . esc_html__( 'Change File Upload Settings', 'super-forms' ) . '</a></p>';
                                echo '</div>';
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';
                }
            }
        }


        /**
         * Automatically update Super Forms from the repository
         *
         *  @since      1.2.6
        */
        public function update_plugin() {
            // @since 3.8.0 - check if settings do not exist, make sure we save default settings
            if( !get_option( 'super_settings' ) ) {
                SUPER_Install::install();
            }

            $slug = $this->slug;
            require_once ( 'includes/admin/plugin-update-checker/plugin-update-checker.php' );
            $MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
                'http://f4d.nl/@super-forms-updates/?action=get_metadata&slug=' . $slug,  //Metadata URL
                __FILE__, //Full path to the main plugin file.
                $slug //Plugin slug. Usually it's the same as the name of the directory.
            );
        }


        /**
         * Hook into the where query to filter custom meta data
         *
         *  @since      1.7
        */
        public static function custom_posts_where( $where, $object ) {
            global $wpdb;
            $table = $wpdb->prefix . 'posts';
            $table_meta = $wpdb->prefix . 'postmeta';
            $where = "";
            if( (isset($_GET['s'])) && ($_GET['s']!='') ) {
                $s = sanitize_text_field($_GET['s']);
                $where .= " AND (";
                    $where .= "($table.post_title LIKE '%$s%') OR ($table.post_excerpt LIKE '%$s%') OR ($table.post_content LIKE '%$s%') OR";
                    $where .= "($table_meta.meta_key = '_super_contact_entry_data' AND $table_meta.meta_value LIKE '%$s%') OR";
                    $where .= "($table_meta.meta_key = '_super_contact_entry_ip' AND $table_meta.meta_value LIKE '%$s%') OR";
                    $where .= "($table_meta.meta_key = '_super_contact_entry_status' AND $table_meta.meta_value LIKE '%$s%')"; // @since 3.4.0 - custom entry status
                $where .= ")";
            }
            if( ( (isset($_GET['sffrom'])) && ($_GET['sffrom']!='') ) && ( (isset($_GET['sfto'])) && ($_GET['sfto']!='') ) ) {
                $sffrom = date('Y-m-d', strtotime($_GET['sffrom']));
                $sfto = date('Y-m-d', strtotime($_GET['sfto']));
                $where .= " AND ( (date($table.post_date) BETWEEN '$sffrom' AND '$sfto') )";
            }
            if( (isset($_GET['super_form_filter'])) && (absint($_GET['super_form_filter'])!=0) ) {
                $super_form_filter = absint($_GET['super_form_filter']);
                $where .= " AND (";
                    $where .= "($table.post_parent = $super_form_filter)";
                $where .= ")";
            }
            if( (isset($_GET['post_status'])) && ($_GET['post_status']!='') && ($_GET['post_status']!='all') ) {
                $post_status = sanitize_text_field($_GET['post_status']);
                $where .= " AND (";
                    $where .= "($table.post_status = '$post_status')";
                $where .= ")";
            }else{
                // @since 2.8.6 - fix issue with showing "All" contact entries also showing deleted items
                $where .= " AND (";
                    $where .= "($table.post_status != 'trash')";
                $where .= ")";     
            }
            $where .= " AND (";
                $where .= "($table.post_type = 'super_contact_entry')";
            $where .= ")";
            return $where;
        }


        /**
         * Hook into the join query to filter custom meta data
         *
         *  @since      1.7
        */
        public static function custom_posts_join( $join, $object ) {
            if( (isset($_GET['s'])) && ($_GET['s']!='') ) {
                global $wpdb;
                $prefix = $wpdb->prefix;
                $table_posts = $wpdb->prefix . 'posts';
                $table_meta = $wpdb->prefix . 'postmeta';
                $join = "INNER JOIN $table_meta ON $table_meta.post_id = $table_posts.ID";
            }
            return $join;
        }


        /**
         * Hook into the groupby query to filter custom meta data
         *
         *  @since      1.7
        */
        public static function custom_posts_groupby( $groupby, $object ) {
            if( (isset($_GET['s'])) && ($_GET['s']!='') ) {
                global $wpdb;
                $table = $wpdb->prefix . 'posts';
                $groupby = "$table.ID";
            }
            return $groupby;
        }


        /**
         * Enqueue [super-form] shortcode styles
         *
         *  @since      1.1.9.5
        */
        public static function enqueue_fontawesome_styles() {
            wp_enqueue_style( 'font-awesome-v5.9', SUPER_PLUGIN_FILE . 'assets/css/fonts/css/all.min.css', array(), SUPER_VERSION );
        }


        /**
         * Enqueue [super-form] shortcode styles
         *
         *  @since      1.1.9.5
        */
        public static function enqueue_element_styles() {
            self::enqueue_fontawesome_styles();
            wp_enqueue_style( 'super-elements', SUPER_PLUGIN_FILE . 'assets/css/frontend/elements.css', array(), SUPER_VERSION );
        }


        /**
         * Enqueue [super-form] shortcode scripts
         *
         *  @since      1.1.9.5
        */
        public static function enqueue_element_scripts( $settings=array(), $ajax=false, $form_id=0 ) {

            $handle = 'super-common';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/common.js', array( 'jquery' ), SUPER_VERSION, false );  

            // @since 3.1.0 - add WPML langauge parameter to ajax URL's required for for instance when redirecting to WooCommerce checkout/cart page
            $ajax_url = SUPER_Forms()->ajax_url();
            $my_current_lang = apply_filters( 'wpml_current_language', NULL ); 
            if ( $my_current_lang ) $ajax_url = add_query_arg( 'lang', $my_current_lang, $ajax_url );

            $i18n = array(
                'ajaxurl'=>$ajax_url,
                'preload'=>$settings['form_preload'],
                'duration'=>$settings['form_duration'],
                'dynamic_functions' => SUPER_Common::get_dynamic_functions(),
                'loadingOverlay'=>SUPER_Forms()->common_i18n['loadingOverlay'],
                'loading'=>SUPER_Forms()->common_i18n['loading'],
                'tab_index_exclusion' => SUPER_Forms()->common_i18n['tab_index_exclusion'],
                'elementor'=>SUPER_Forms()->common_i18n['elementor'],
                'directions'=>SUPER_Forms()->common_i18n['directions'],
                'errors'=>SUPER_Forms()->common_i18n['errors'],
                // @since 3.6.0 - google tracking
                'ga_tracking' => ( !isset( $settings['form_ga_tracking'] ) ? "" : $settings['form_ga_tracking'] ),
                'super_int_phone_utils' => SUPER_PLUGIN_FILE . 'assets/js/frontend/int-phone-utils.js'
            );
            wp_localize_script($handle, $name, $i18n);
            wp_enqueue_script( $handle );
            
            $handle = 'super-elements';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/frontend/elements.js', array( 'super-common' ), SUPER_VERSION, false );  
            wp_localize_script( $handle, $name, SUPER_Forms()->elements_i18n );
            wp_enqueue_script( $handle );

            $handle = 'super-frontend-common';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/frontend/common.js', array( 'super-common' ), SUPER_VERSION, false );  
            wp_localize_script( $handle, $name, array( 'includes_url'=>includes_url(), 'plugin_url'=>SUPER_PLUGIN_FILE ) );
            wp_enqueue_script( $handle );

            // Add JS files that are needed in case when theme makes an Ajax call to load content dynamically
            // This is also used on the Elementor editor pages
            if( $ajax==true ) {
                wp_enqueue_media(); // Needed for Text Editor
                wp_enqueue_script( 'super-masked-currency', SUPER_PLUGIN_FILE . 'assets/js/frontend/masked-currency.js', array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_script( 'spectrum', SUPER_PLUGIN_FILE . 'assets/js/frontend/spectrum.js', array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_style( 'spectrum', SUPER_PLUGIN_FILE.'assets/css/frontend/spectrum.css', array(), SUPER_VERSION, false );    
                wp_enqueue_style( 'tooltips', SUPER_PLUGIN_FILE.'assets/css/backend/tooltips.css', array(), SUPER_VERSION, false );
                wp_enqueue_script( 'tooltips', SUPER_PLUGIN_FILE.'assets/js/backend/tooltips.js', array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_script( 'iban-check', SUPER_PLUGIN_FILE . 'assets/js/frontend/iban-check.js', array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_script( 'super-masked-input', SUPER_PLUGIN_FILE . 'assets/js/frontend/masked-input.js', array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_style( 'simpleslider', SUPER_PLUGIN_FILE.'assets/css/backend/simpleslider.css', array(), SUPER_VERSION );
                wp_enqueue_script( 'simpleslider', SUPER_PLUGIN_FILE.'assets/js/backend/simpleslider.js', array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_style( 'super-carousel', SUPER_PLUGIN_FILE.'assets/css/frontend/carousel.css', array(), SUPER_VERSION );    
                wp_enqueue_script( 'super-carousel', SUPER_PLUGIN_FILE . 'assets/js/frontend/carousel.js', array( 'super-common' ), SUPER_VERSION );
                wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_script( 'date-format', SUPER_PLUGIN_FILE . 'assets/js/frontend/date-format.js', array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_script( 'jquery-timepicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/timepicker.js', array( 'jquery' ), SUPER_VERSION, false );
                if( !empty($settings['form_recaptcha_v3']) && !empty($settings['form_recaptcha_v3']) ) {
                    wp_enqueue_script('recaptcha', '//www.google.com/recaptcha/api.js?onload=SUPERreCaptcha&render=' . $settings['form_recaptcha_v3']);
                }else{
                    if( !empty($settings['form_recaptcha']) && !empty($settings['form_recaptcha_secret']) ) {
                        wp_enqueue_script('recaptcha', '//www.google.com/recaptcha/api.js?onload=SUPERreCaptcha&render=explicit');
                    }
                }
                // @since 3.1.0 - google maps API places library
                if( !empty($settings['form_google_places_api']) ) {
                    $url = '//maps.googleapis.com/maps/api/js?';
                    if( !empty( $settings['google_maps_api_region'] ) ){
                        $url .= 'region='.$settings['google_maps_api_region'].'&';
                    }
                    if( !empty( $settings['google_maps_api_language'] ) ){
                        $url .= 'language='.$settings['google_maps_api_language'].'&';
                    }
                    $url .= 'key=' . $settings['form_google_places_api'] . '&libraries=drawing,geometry,places,visualization&callback=SUPER.google_maps_init';
                    wp_enqueue_script( 'google-maps-api', $url, array( 'super-common' ), SUPER_VERSION, false );
                }

                $dir = SUPER_PLUGIN_FILE . 'assets/js/frontend/jquery-file-upload/';
                wp_enqueue_script( 'jquery-iframe-transport', $dir . 'jquery.iframe-transport.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
                wp_enqueue_script( 'jquery-fileupload', $dir . 'jquery.fileupload.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
                wp_enqueue_script( 'jquery-fileupload-process', $dir . 'jquery.fileupload-process.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
                wp_enqueue_script( 'jquery-fileupload-validate', $dir . 'jquery.fileupload-validate.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
                
            }

            // @since 1.2.8 -   super_after_enqueue_element_scripts_action
            do_action( 'super_after_enqueue_element_scripts_action', array( 'settings'=>$settings, 'ajax'=>$ajax ) );

        }


        /**
         * Enqueue scripts before ajax call is made
         *
         *  @since      1.1.9
        */
        public static function load_frontend_scripts_before_ajax() {
            $global_settings = SUPER_Common::get_global_settings();
            if( isset( $global_settings['enable_ajax'] ) ) {
                if( $global_settings['enable_ajax']=='1' ) {            
                    require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
                    $default_settings = SUPER_Settings::get_defaults();
                    $global_settings = array_merge( $default_settings, $global_settings );
                    self::enqueue_element_styles();
                    self::enqueue_element_scripts( $global_settings, true );
                }
            }
        }


        /**
         * Include required ajax files.
         *
         *  @since      1.0.0
        */
        public function enqueue_scripts_before_ajax_calls() {
            
            include_once('includes/class-ajax.php'); // Ajax functions for admin and the front-end
        
        }

        
        /**
         * Include required ajax files.
         *
         *  @since      1.0.0
        */
        public function ajax_includes() {
            
            include_once( 'includes/class-ajax.php' ); // Ajax functions for admin and the front-end
        
        }

        
        /**
         * Include required frontend files.
         *
         *  @since      1.0.0
        */
        public function frontend_includes() {
                        
        }

        public function sfapi(){
            if(isset($_GET['sfapi'])){
                if($_GET['sfapi']=='v1'){
                    $p = file_get_contents('php://input');
                    try {
                        $p = json_decode($p, true);
                        if( empty($_GET['m']) ) {
                            throw new Exception("Invalid payload");
                        }
                        if($_GET['m']=='t'){
                            echo 'pong';
                            http_response_code(200);
                            exit;
                        }
                    } catch (Exception $e) {
                        echo $e->getMessage();
                        http_response_code(400);
                        exit;
                    }
                }
                exit;
            }
        }

        /**
         * Init Super Forms when WordPress Initialises.
         *
         *  @since      1.0.0
        */
        public function init() {
            if(!headers_sent()){
                // Start session for this client
                SUPER_Common::startClientSession(array('update_option' => false));
            }

            // Can't rely solely on cronjobs, because some servers have it disabled
            // Default interval is 1 out of 50, and it can delete up to 200 expired sessions at a time by default
            // If needed you can tweak these values with the use of the filter hooks
            $interval = absint(apply_filters( 'super_delete_old_client_data_manually_interval_filter', 50 ));
            if(rand(1, $interval)===1) {
                $limit = apply_filters( 'super_delete_client_data_manually_limit_filter', 10 );
                SUPER_Common::deleteOldClientData($limit);
            }

            // Before init action
            do_action('before_super_init');
 
            $this->load_plugin_textdomain();

            $failed_to_process_data = esc_html__( 'Failed to process data, please try again', 'super-forms' );

            // @since 3.2.0 - filter hook for javascrip translation string and other manipulation
            $this->common_i18n = apply_filters( 'super_common_i18n_filter', 
                array(  

                    // @since 3.2.0 - dynamic tab index class exclusion
                    'tab_index_exclusion' => '.super-prev-multipart,.super-next-multipart,.super-calculator,.super-spacer,.super-divider,.super-recaptcha,.super-heading,.super-image,.hidden,.super-hidden,.super-html,.super-pdf_page_break',
                    
                    // Loading overlay text
                    'loadingOverlay' => array(
                        'processing' => esc_html__( 'Processing form data...', 'super-forms' ),
                        'uploading_files' => esc_html__( 'Uploading files...', 'super-forms' ),
                        'generating_pdf' => esc_html__( 'Generating PDF file...', 'super-forms' ),
                        'completed' => esc_html__( 'Completed!', 'super-forms' ),
                        'close' => esc_html__( 'Close', 'super-forms' ),
                        'redirecting' => esc_html__( 'Redirecting...', 'super-forms' ),
                    ),

                    'loading' => esc_html__( 'Loading...', 'super-forms' ),
                    'elementor' => array(
                        'notice' => esc_html__( 'Notice', 'super-forms' ),
                        'msg' => esc_html__( 'when using Elementor, you must use the native Super Forms Widget or Shortcode Widget to display your forms', 'super-forms' ),
                    ),
                    'directions' => array(
                        'next' => esc_html__( 'Next', 'super-forms' ),
                        'prev' => esc_html__( 'Prev', 'super-forms' ),
                    ),
                    'errors' => array(
                        'failed_to_process_data' => $failed_to_process_data,
                        'file_upload' => array(
                            'upload_limit_reached' => esc_html__( 'Upload limit reached!', 'super-forms' ),
                            'upload_size_limit_reached' => esc_html__( 'Upload size limit reached!', 'super-forms' ),
                            'incorrect_file_extension' => esc_html__( 'Sorry, file extension is not allowed!', 'super-forms' ),
                            'filesize_too_big' => esc_html__( 'Filesize is too big', 'super-forms' ),
                        ),
                        'distance_calculator' => array(
                            'zero_results' => esc_html__( 'Sorry, no distance could be calculated based on entered data. Please enter a valid address or zipcode.', 'super-forms' ),
                            'error' => esc_html__( 'Something went wrong while calculating the distance.', 'super-forms' )
                        )
                    )
                )
            );

            // @since 3.2.0 - filter hook for javascrip translation string and other manipulation
            $this->elements_i18n = apply_filters( 'super_elements_i18n_filter', 
                array(

                    'ajaxurl' => SUPER_Forms()->ajax_url(),

                    'failed_to_process_data' => $failed_to_process_data,

                    // @since 3.2.0 - dynamic tab index class exclusion
                    'tab_index_exclusion' => $this->common_i18n['tab_index_exclusion'],

                    'monthNames' => array(
                        esc_html__( 'January', 'super-forms' ),
                        esc_html__( 'February', 'super-forms' ),
                        esc_html__( 'March', 'super-forms' ),
                        esc_html__( 'April', 'super-forms' ),
                        esc_html__( 'May', 'super-forms' ),
                        esc_html__( 'June', 'super-forms' ),
                        esc_html__( 'July', 'super-forms' ),
                        esc_html__( 'August', 'super-forms' ),
                        esc_html__( 'September', 'super-forms' ),
                        esc_html__( 'October', 'super-forms' ),
                        esc_html__( 'November', 'super-forms' ),
                        esc_html__( 'December', 'super-forms' )
                    ),
                    'monthNamesShort' => array(
                        esc_html__( 'Jan', 'super-forms' ),
                        esc_html__( 'Feb', 'super-forms' ),
                        esc_html__( 'Mar', 'super-forms' ),
                        esc_html__( 'Apr', 'super-forms' ),
                        esc_html__( 'May', 'super-forms' ),
                        esc_html__( 'Jun', 'super-forms' ),
                        esc_html__( 'Jul', 'super-forms' ),
                        esc_html__( 'Aug', 'super-forms' ),
                        esc_html__( 'Sep', 'super-forms' ),
                        esc_html__( 'Oct', 'super-forms' ),
                        esc_html__( 'Nov', 'super-forms' ),
                        esc_html__( 'Dec', 'super-forms' )
                    ),
                    'dayNames' => array(
                        esc_html__( 'Sunday', 'super-forms' ),
                        esc_html__( 'Monday', 'super-forms' ),
                        esc_html__( 'Tuesday', 'super-forms' ),
                        esc_html__( 'Wednesday', 'super-forms' ),
                        esc_html__( 'Thursday', 'super-forms' ),
                        esc_html__( 'Friday', 'super-forms' ),
                        esc_html__( 'Saturday', 'super-forms' )
                    ),
                    'dayNamesShort' => array(
                        esc_html__( 'Sun', 'super-forms' ),
                        esc_html__( 'Mon', 'super-forms' ),
                        esc_html__( 'Tue', 'super-forms' ),
                        esc_html__( 'Wed', 'super-forms' ),
                        esc_html__( 'Thu', 'super-forms' ),
                        esc_html__( 'Fri', 'super-forms' ),
                        esc_html__( 'Sat', 'super-forms' )
                    ),
                    'dayNamesMin' => array(
                        esc_html__( 'Su', 'super-forms' ),
                        esc_html__( 'Mo', 'super-forms' ),
                        esc_html__( 'Tu', 'super-forms' ),
                        esc_html__( 'We', 'super-forms' ),
                        esc_html__( 'Th', 'super-forms' ),
                        esc_html__( 'Fr', 'super-forms' ),
                        esc_html__( 'Sa', 'super-forms' )
                    ),
                    'weekHeader' => esc_html__( 'Wk', 'super-forms' ),
                )
            );

            // Init action
            do_action('super_init');
            
            // Add WooCommerce menu items?
            add_filter( 'woocommerce_account_menu_items', array( $this, 'add_custom_wc_my_account_menu_items' ), 10, 1 );
            add_filter( 'woocommerce_get_endpoint_url', array( $this, 'add_custom_wc_my_account_menu_item_endpoint' ), 10, 4 );
            $global_settings = SUPER_Common::get_global_settings();
            if(empty($global_settings['wc_my_account_menu_items'])) $global_settings['wc_my_account_menu_items'] = '';
            $wc_my_account_menu_items = explode("\n", $global_settings['wc_my_account_menu_items']);
            foreach( $wc_my_account_menu_items as $v ) {
                $v = explode('|', $v);
                // form-submissions|Form Submissions|[super_listings list="1" id="54751"]|3
                if(!isset($v[0]) || !isset($v[1]) || !isset($v[2]) ) continue;
                $menu_slug = $v[0]; 
                add_rewrite_endpoint( $menu_slug, EP_PAGES );
            }
        }
        
        
        /**
         * Call Classes and Execute Functions based on current screen ID 
         *
         * @param  string $current_screen
         * 
         * @since       1.0.0
        */
        public function after_screen( $current_screen ) {

            if($current_screen->id==='super-forms_page_super_create_form'){
                add_action( 'super_create_form_builder_tab', array( 'SUPER_Pages', 'builder_tab' ), 10, 1 );
                add_action( 'super_create_form_code_tab', array( 'SUPER_Pages', 'code_tab' ), 10, 1 );
                add_action( 'super_create_form_secrets_tab', array( 'SUPER_Pages', 'secrets_tab' ), 10, 1 );
                add_action( 'super_create_form_translations_tab', array( 'SUPER_Pages', 'translations_tab' ), 10, 1 );
                add_action( 'super_create_form_triggers_tab', array( 'SUPER_Pages', 'triggers_tab' ), 10, 1 );
                add_action( 'admin_footer', function(){ echo SUPER_Common::get_transient(array('slug'=>'super-forms_page_super_create_form'));}, 15);
            }

            // @since 1.7 - add the export button only on the super_contact_entry page
            if( $current_screen->id=='edit-super_contact_entry' ) {
                add_action( 'manage_posts_extra_tablenav', array( $this, 'contact_entry_export_button' ) );
                add_filter( 'posts_where', array( $this, 'custom_posts_where' ), 0, 2 );
                add_filter( 'posts_join', array( $this, 'custom_posts_join' ), 0, 2 );
                add_filter( 'posts_groupby', array( $this, 'custom_posts_groupby' ), 0, 2 );
            }

            if( $current_screen->id=='edit-super_form' ) {
                add_filter('post_row_actions', array( $this, 'super_remove_row_actions' ), 10, 1);
                add_filter('get_edit_post_link', array( $this, 'edit_post_link' ), 99, 3);
                add_action('admin_head', array( $this, 'add_post_link' ));

            }
            if( $current_screen->id=='edit-super_contact_entry' ) {
                add_filter( 'manage_super_contact_entry_posts_columns', array( $this, 'super_contact_entry_columns' ), 999999 );
                add_filter( 'manage_super_form_posts_columns', array( $this, 'super_form_columns' ), 999999 );
                add_action( 'manage_super_contact_entry_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );
                add_filter( 'post_row_actions', array( $this, 'super_remove_row_actions' ), 10, 1 );
                add_filter( 'get_edit_post_link', array( $this, 'edit_post_link' ), 99, 3 );
                add_action( 'bulk_edit_custom_box', array( $this, 'display_custom_quickedit_super_contact_entry' ), 10, 2 );
            }
        }
    
        public function super_contact_entry_columns( $columns ) {
            foreach( $columns as $k => $v ) {
                if( ( $k != 'title' ) && ( $k != 'cb' ) ) {
                    unset( $columns[$k] );
                }
            }
            $global_settings = SUPER_Common::get_global_settings();
            $GLOBALS['backend_contact_entry_status'] = SUPER_Settings::get_entry_statuses($global_settings);

            $fields = explode( "\n", $global_settings['backend_contact_entry_list_fields'] );

            // @since 3.4.0 - add the contact entry status to the column list for entries
            if( !isset($global_settings['backend_contact_entry_list_status']) ) $global_settings['backend_contact_entry_list_status'] = 'true';
            if( $global_settings['backend_contact_entry_list_status']=='true' ) {
                $columns = array_merge( $columns, array( 'entry_status' => esc_html__( 'Status', 'super-forms' ) ) );
            }

            // @since 1.2.9
            if( !isset($global_settings['backend_contact_entry_list_form']) ) $global_settings['backend_contact_entry_list_form'] = 'true';
            if( $global_settings['backend_contact_entry_list_form']=='true' ) {
                $columns = array_merge( $columns, array( 'hidden_form_id' => esc_html__( 'Based on Form', 'super-forms' ) ) );
            }

            // @since 3.1.0
            if( (isset($global_settings['backend_contact_entry_list_ip'])) && ($global_settings['backend_contact_entry_list_ip']=='true') ) {
                $columns = array_merge( $columns, array( 'contact_entry_ip' => esc_html__( 'IP-address', 'super-forms' ) ) );
            }

            foreach( $fields as $k ) {
                $field = explode( "|", $k );
                if( $field[0]=='hidden_form_id' ) {
                    $columns['hidden_form_id'] = $field[1];
                }elseif( $field[0]=='entry_status' ){
                    $columns['entry_status'] = $field[1];
                }else{
                    $columns = array_merge( $columns, array( $field[0] => $field[1] ) );
                }
            }

            $columns = array_merge( $columns, array( 'date' => esc_html__( 'Date', 'super-forms' ) ) );
            return $columns;
        }
        public function super_form_columns( $columns ) {
            foreach( $columns as $k => $v ) {
                if( ( $k != 'title' ) && ( $k != 'cb' ) && ( $k != 'date' ) ) {
                    unset( $columns[$k] );
                }
            }
            return $columns;
        }
        public function super_custom_columns( $column, $post_id ) {
            $contact_entry_data = get_post_meta( $post_id, '_super_contact_entry_data' );
            if( $column=='hidden_form_id' ) {
                if( isset( $contact_entry_data[0][$column] ) ) {
                    $form_id = $contact_entry_data[0][$column]['value'];
                    $form_id = absint($form_id);
                    if($form_id==0){
                        echo esc_html__( 'Unknown', 'super-forms' );
                    }else{
                        $form = get_post($form_id);
                        if( isset( $form->post_title ) ) {
                            echo '<a href="' . esc_url('admin.php?page=super_create_form&id=' . absint($form->ID)) . '">' . esc_html($form->post_title) . '</a>';
                        }else{
                            echo esc_html__( 'Unknown', 'super-forms' );
                        }
                    }
                }
            }elseif( $column=='entry_status' ) {
                $entry_status = get_post_meta($post_id, '_super_contact_entry_status', true);
                $statuses = $GLOBALS['backend_contact_entry_status'];
                if( (isset($statuses[$entry_status])) && ($entry_status!='') ) {
                    echo '<span class="super-entry-status super-entry-status-' . $entry_status . '" style="color:' . $statuses[$entry_status]['color'] . ';background-color:' . $statuses[$entry_status]['bg_color'] . '">' . $statuses[$entry_status]['name'] . '</span>';
                }else{
                    $post_status = get_post_status($post_id);
                    if($post_status=='super_read'){
                        echo '<span class="super-entry-status super-entry-status-' . $post_status . '" style="background-color:#d6d6d6;">' . esc_html__( 'Read', 'super-forms' ) . '</span>';
                    }else{
                        echo '<span class="super-entry-status super-entry-status-' . $post_status . '">' . esc_html__( 'Unread', 'super-forms' ) . '</span>';
                    }
                }
            }elseif( $column=='contact_entry_ip' ) {
                $entry_ip = get_post_meta($post_id, '_super_contact_entry_ip', true);
                echo $entry_ip . ' [<a href="' . esc_url('http://whois.domaintools.com/' . $entry_ip) . '" target="_blank">Whois</a>]';
            }else{
                if( isset( $contact_entry_data[0][$column] ) ) {
                    echo esc_html($contact_entry_data[0][$column]['value']);
                }
            }
        }
        public function super_remove_row_actions( $actions ) {
            if(get_post_type() === 'super_form'){
                if(isset($actions['trash'])){
                    $trash = $actions['trash'];
                    unset($actions['trash']);
                }
                unset($actions['inline hide-if-no-js']);
                unset($actions['view']);
                unset($actions['edit']);
                $actions['shortcode'] = '<input type="text" readonly="readonly" class="super-get-form-shortcodes" value=\'[super_form id="'.get_the_ID().'"]\' />';
                $actions['duplicate'] = '<a href="' . esc_url(wp_nonce_url( admin_url( 'edit.php?post_type=super_form&action=duplicate_super_form&amp;post=' . get_the_ID() ), 'super-duplicate-form_' . get_the_ID() )) . '" title="' . esc_attr__( 'Make a duplicate from this form', 'super-forms' ) . '" rel="permalink">' .  esc_html__( 'Duplicate', 'super-forms' ) . '</a>';
                $actions['view'] = '<a href="' . esc_url('admin.php?page=super_create_form&id='.get_the_ID()) . '">'.esc_html__('Edit','wp').'</a>';
                if(isset($trash)) $actions['trash'] = $trash;
            }
            if( get_post_type()==='super_contact_entry' ) {
                if( isset( $actions['trash'] ) ) {
                    $trash = $actions['trash'];
                    unset( $actions['trash'] );
                }
                unset( $actions['inline hide-if-no-js'] );
                unset( $actions['view'] );
                unset( $actions['edit'] );
                $actions['view'] = '<a href="' . esc_url('admin.php?page=super_contact_entry&id=' . get_the_ID()) . '">'.esc_html__('View', 'super-forms').'</a>';

                

                $actions['mark'] = '<a class="super-mark-read" data-contact-entry="' . get_the_ID() . '" title="' . esc_attr__( 'Mark this entry as read', 'super-forms' ) . '" href="#">' . esc_html__( 'Mark read', 'super-forms' ) . '</a><a class="super-mark-unread" data-contact-entry="' . get_the_ID() . '" title="' . esc_attr__( 'Mark this entry as unread', 'super-forms' ) . '" href="#">' . esc_html__( 'Mark unread', 'super-forms' ) . '</a>';
                $actions['duplicate'] = '<a href="' . esc_url(wp_nonce_url( admin_url( 'edit.php?post_type=super_contact_entry&action=duplicate_super_contact_entry&amp;post=' . get_the_ID() ), 'super-duplicate-contact-entry_' . get_the_ID() )) . '" title="' . esc_attr__( 'Make a duplicate of this entry', 'super-forms' ) . '" rel="permalink">' .  esc_html__( 'Duplicate', 'super-forms' ) . '</a>';
                if( isset( $trash ) ) {
                    $actions['trash'] = $trash;
                }
            }
            return $actions;
        }
        public function edit_post_link( $link, $post_id, $context ) {
            if( get_post_type()==='super_form' ) {
                return 'admin.php?page=super_create_form&id=' . get_the_ID();
            }
            if( get_post_type() === 'super_contact_entry' ) {
                return 'admin.php?page=super_contact_entry&id=' . get_the_ID();
            }
            return $link;
        }
        // @since 3.4.0 - add bulk edit option to change entry status
        public function display_custom_quickedit_super_contact_entry( $column_name, $post_type ) {
            if( ($post_type=='super_contact_entry') && ($column_name=='entry_status') ) {
                static $printNonce = TRUE;
                if ( $printNonce ) {
                    $printNonce = FALSE;
                    wp_nonce_field( plugin_basename( __FILE__ ), 'book_edit_nonce' );
                }
                ?>
                <fieldset class="inline-edit-col-right">
                    <div class="inline-edit-col">
                        <div class="inline-edit-group wp-clearfix">
                            <label class="inline-edit-status alignleft">
                            <span class="title"><?php echo esc_html__('Entry status', 'super-forms'); ?></span>
                                <select name="entry_status">
                                <option value="-1">— <?php echo esc_html__('No changes', 'super-forms'); ?> —</option>
                                    <?php
                                    $statuses = $GLOBALS['backend_contact_entry_status'];
                                    foreach($statuses as $k => $v){
                                        echo '<option value="' . $k . '">' . $v['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </label>
                        </div>
                    </div>
                </fieldset>
                <?php
            }
        }
        public function add_post_link() {
            global $post_new_file,$post_type_object;
            if(!isset($post_type_object) || 'super_form' != $post_type_object->name){
                return false;
            }
            $post_new_file = 'admin.php?page=super_create_form';
        }



        /**
         * Enqueue styles used for displaying messages
         * 
         * @since       1.0.6
        */
        public function enqueue_message_scripts() {
            $super_msg = SUPER_Common::getClientData( 'msg' );
            if( $super_msg!=false ) {
                $global_settings = SUPER_Common::get_global_settings();

                self::enqueue_fontawesome_styles();
                wp_enqueue_style( 'super-elements', SUPER_PLUGIN_FILE . 'assets/css/frontend/elements.css', array(), SUPER_VERSION );
                
                $handle = 'super-common';
                $name = str_replace( '-', '_', $handle ) . '_i18n';
                wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/common.js', array( 'jquery' ), SUPER_VERSION, false );

                // @since 3.1.0 - add WPML langauge parameter to ajax URL's required for for instance when redirecting to WooCommerce checkout/cart page
                $ajax_url = SUPER_Forms()->ajax_url();
                $my_current_lang = apply_filters( 'wpml_current_language', NULL ); 
                if ( $my_current_lang ) $ajax_url = add_query_arg( 'lang', $my_current_lang, $ajax_url );

                wp_localize_script(
                    $handle,
                    $name,
                    array(
                        'ajaxurl'=>$ajax_url,
                        'preload'=>$global_settings['form_preload'],
                        'duration'=>$global_settings['form_duration'],
                        'dynamic_functions' => SUPER_Common::get_dynamic_functions(),
                        'loadingOverlay'=>$this->common_i18n['loadingOverlay'],
                        'loading'=>$this->common_i18n['loading'],
                        'tab_index_exclusion'=>$this->common_i18n['tab_index_exclusion'],
                        'elementor'=>$this->common_i18n['elementor'],
                        'directions'=>$this->common_i18n['directions'],
                        'errors'=>$this->common_i18n['errors'],
                        // @since 3.6.0 - google tracking
                        'ga_tracking' => ( !isset( $global_settings['form_ga_tracking'] ) ? "" : $global_settings['form_ga_tracking'] ),
                        'super_int_phone_utils' => SUPER_PLUGIN_FILE . 'assets/js/frontend/int-phone-utils.js',
                    )
                );
                wp_enqueue_script( $handle );

                $handle = 'super-elements';
                $name = str_replace( '-', '_', $handle ) . '_i18n';
                wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/frontend/elements.js', array( 'super-common' ), SUPER_VERSION, false );  
                wp_localize_script(
                    $handle,
                    $name,
                    $this->elements_i18n
                );
                wp_enqueue_script( $handle );
                wp_enqueue_script( 'super-frontend-common', SUPER_PLUGIN_FILE . 'assets/js/frontend/common.js', array( 'super-common' ), SUPER_VERSION, false );
            }
        }


        /**
         * Enqueue scripts for each admin page
         * 
         * @since       1.0.0
        */
        public function enqueue_scripts() {
            if ( function_exists( 'get_current_screen' ) ) {
                $current_screen = get_current_screen();
            }else{
                $current_screen = new stdClass();
                $current_screen->id = '';
            }
            if( ( $current_screen->id=='super-forms_page_super_create_form' ) || ( $current_screen->id=='super-forms_page_super_settings' ) ) {
                wp_enqueue_media();
            }
            // Enqueue Javascripts
            if( $enqueue_scripts = self::get_scripts() ) {
                foreach( $enqueue_scripts as $handle => $args ) {
                    if ( ( in_array( $current_screen->id, $args['screen'] ) ) || ( $args['screen'][0]=='all' ) ) {
                        if($args['method']=='register'){
                            self::$scripts[] = $handle;
                            wp_register_script( $handle, $args['src'], $args['deps'], $args['version'], $args['footer'] );
                        }else{
                            wp_enqueue_script( $handle, $args['src'], $args['deps'], $args['version'], $args['footer'] );
                        }
                    }
                }
            }
            // Enqueue Styles
            if( $enqueue_styles = self::get_styles() ) {
                foreach( $enqueue_styles as $handle => $args ) {
                    if ( ( in_array( $current_screen->id, $args['screen'] ) ) || ( $args['screen'][0]=='all' ) ) {
                        if($args['method']=='register'){
                            wp_register_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
                        }else{
                            wp_enqueue_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
                        }
                    }
                }
            }
        }
        
        
        /**
         * Get styles for the backend
         *
         * @access private
         * @return array
         * [$handle, $src, $deps, $ver, $media]
         *
         * @since       1.0.0
        */
        public static function get_styles() {
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', SUPER_PLUGIN_FILE ) . 'assets/';
            $backend_path   = $assets_path . 'css/backend/';
            $frontend_path  = $assets_path . 'css/frontend/';
            $fonts_path  = $assets_path . 'css/fonts/css/';
            return apply_filters( 
                'super_enqueue_styles', 
                array(
                    'super-common' => array(
                        'src'     => $backend_path . 'common.css',
                        'deps'    => array( 'farbtastic', 'wp-color-picker' ),
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-create-form' => array(
                        'src'     => $backend_path . 'create-form.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-create-form-responsive' => array(
                        'src'     => $backend_path . 'create-form-responsive.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'super-forms_page_super_create_form' ),
                        'method'  => 'enqueue',
                    ),
                    'super-flags' => array(
                        'src'     => $frontend_path . 'flags.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-contact-entry' => array(
                        'src'     => $backend_path . 'contact-entry.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 
                            'edit-super_contact_entry',
                            'admin_page_super_contact_entry'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-settings' => array(
                        'src'     => $backend_path . 'settings.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'super-forms_page_super_settings' ),
                        'method'  => 'enqueue',
                    ),
                    'super-demos' => array(
                        'src'     => $backend_path . 'demos.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'super-forms_page_super_demos' ),
                        'method'  => 'enqueue',
                    ),
                    // @since 5.0.100 - international phone numbers
                    'super-int-phone' => array(
                        'src'     => $frontend_path . 'int-phone.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    // @since 4.8.0 - CarouselJS for "Display Layout > Slider" for Radio/Checkbox elements
                    'super-carousel' => array(
                        'src'     => $frontend_path . 'carousel.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'spectrum' => array(
                        'src'     => $frontend_path . 'spectrum.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'simpleslider' => array(
                        'src'     => $backend_path . 'simpleslider.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'tooltip' => array(
                        'src'     => $backend_path . 'tooltips.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),                  
                    'font-awesome-v5.9' => array(
                        'src'     => $fonts_path . 'all.min.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings',
                            'edit-super_contact_entry',
                            'admin_page_super_contact_entry',
                            'super-forms_page_super_demos'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-elements' => array(
                        'src'     => $frontend_path . 'elements.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),

                    // @since 4.0.0 - hints/introduction
                    'super-hints' => array(
                        'src'     => $backend_path . 'hints.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                )
            );
        }
        
        
        /**
         * Get scripts for the backend
         *
         * @access private
         * @return array
         * [$handle, $src, $deps, $ver, $in_footer]
         *
         * @since       1.0.0
        */
        public static function get_scripts() {
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', SUPER_PLUGIN_FILE ) . 'assets/';
            $lib_path    = str_replace( array( 'http:', 'https:' ), '', SUPER_PLUGIN_FILE ) . 'lib/';
            $backend_path   = $assets_path . 'js/backend/';
            $frontend_path  = $assets_path . 'js/frontend/';
            $global_settings = SUPER_Common::get_global_settings();

            return apply_filters( 
                'super_enqueue_scripts', 
                array(   
                    'jquery-ui-datepicker' => array(
                        'src'     => $frontend_path . 'timepicker.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'timepicker' => array(
                        'src'     => $frontend_path . 'timepicker.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'date-format' => array(
                        'src'     => $frontend_path . 'date-format.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-common' => array(
                        'src'     => $assets_path . 'js/common.js',
                        'deps'    => array( 'jquery', 'farbtastic', 'wp-color-picker' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'edit-super_contact_entry',
                            'admin_page_super_contact_entry',
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize'=> array(
                            'preload' => ( !isset( $global_settings['form_preload'] ) ? '1' : $global_settings['form_preload'] ),
                            'duration' => ( !isset( $global_settings['form_duration'] ) ? 500 : $global_settings['form_duration'] ),
                            'dynamic_functions' => SUPER_Common::get_dynamic_functions(),
                            'loadingOverlay'=>SUPER_Forms()->common_i18n['loadingOverlay'],
                            'loading' => SUPER_Forms()->common_i18n['loading'],
                            'tab_index_exclusion' => SUPER_Forms()->common_i18n['tab_index_exclusion'],
                            'elementor' => SUPER_Forms()->common_i18n['elementor'],
                            'directions' => SUPER_Forms()->common_i18n['directions'],
                            'errors' => SUPER_Forms()->common_i18n['errors'],
                            // @since 3.6.0 - google tracking
                            'ga_tracking' => ( !isset( $global_settings['form_ga_tracking'] ) ? "" : $global_settings['form_ga_tracking'] )
                        )
                    ),
                    'super-backend-common' => array(
                        'src'     => $backend_path . 'common.js',
                        'deps'    => array( 'super-common' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),

                    // @since 5.0.300 - tinymce
                    'super-tinymce' => array(
                        'src'     => $lib_path . 'tinymce/tinymce.min.js',
                        'deps'    => array(),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    // @since 4.0.0 - hints/introduction
                    'hints' => array(
                        'src'     => $backend_path . 'hints.js',
                        'deps'    => array( 'super-backend-common' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-create-form' => array(
                        'src'     => $backend_path . 'create-form.js',
                        'deps'    => array( 'super-backend-common', 'jquery-ui-sortable', 'hints', 'super-tinymce' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize'=> array(
                            'not_editing_an_element' => sprintf( esc_html__( 'You are currently not editing an element.%sEdit any alement by clicking the %s icon.', 'super-forms' ), '<br />', '<i class="fa fa-pencil"></i>' ),
                            'no_backups_found' => esc_html__( 'No backups found...', 'super-forms' ),
                            'confirm_reset' => esc_html__( 'Are you sure you want to reset all the form settings according to your current global settings?', 'super-forms' ),
                            'confirm_deletion' => esc_html__( 'Please confirm deletion!', 'super-forms' ),
                            'confirm_import' => esc_html__( "Please confirm import!\nThis will override your current progress!", 'super-forms' ),
                            'export_form_error' => esc_html__( 'Something went wrong while exporting form data.', 'super-forms' ),
                            'import_form_error' => esc_html__( 'Something went wrong while importing form data.', 'super-forms' ),
                            'import_form_select_option' => esc_html__( 'Please select what you want to import!', 'super-forms' ),
                            'import_form_choose_file' => esc_html__( 'Please choose an import file first!', 'super-forms' ),
                            'confirm_clear_form' => esc_html__( 'Please confirm to clear form!', 'super-forms' ),
                            'confirm_reset_submission_counter' => esc_html__( 'Please confirm to reset submission counter!', 'super-forms' ),
                            'confirm_load_form' => esc_html__( 'This will delete your current progress. Before you proceed, please confirm that you want to delete all elements and insert this example form!', 'super-forms' ),
                            'alert_select_form' => esc_html__( 'You did not select a form!', 'super-forms' ),
                            'alert_save' => esc_html__( 'Before you can preview it, you need to save your form!', 'super-forms' ),
                            'alert_save_not_allowed_code_tab' => esc_html__( 'You are not allowed to save the form while the "Code" tab is opened!', 'super-forms' ),
                            'alert_duplicate_field_names' => esc_html__( 'You have duplicate field names. Please make sure each field has a unique name!', 'super-forms' ),
                            'alert_duplicate_secret_names' => esc_html__( 'You have duplicate secret names. Please make sure each secret has a unique name!', 'super-forms' ),
                            'alert_multipart_error' => esc_html__( 'It\'s not possible to insert a Multipart inside a Multipart', 'super-forms' ),
                            'alert_empty_field_name' => esc_html__( 'Unique field name may not be empty!', 'super-forms' ),
                            'deleting' => esc_html__( 'Deleting...', 'super-forms' ),
                            'edit_json_notice_n1' => sprintf( esc_html__( '%sForm elements:%s', 'super-forms' ), '<strong>', '</strong>' ),
                            'edit_json_notice_n2' => sprintf( esc_html__( '%sForm settings:%s', 'super-forms' ), '<strong>', '</strong>' ),
                            'edit_json_notice_n3' => sprintf( esc_html__( '%sTranslation settings:%s (this only includes the translation settings, not the actual strings, this is stored in the "Form elements" code)', 'super-forms' ), '<strong>', '</strong>' ),
                            'save_loading' => esc_html__( 'Loading...', 'super-forms' ),
                            'invalid_json' => esc_html__( 'Invalid JSON, please correct the error(s) and try again!', 'super-forms' ),
                            'try_jsonlint' => sprintf( esc_html__( 'Use %shttps://jsonlint.com/%s in case you are unable to find the error.', 'super-forms' ), '<a target="_blank" href="https://jsonlint.com/">', '</a>' )
                        ),
                    ),
                    'super-contact-entry' => array(
                        'src'     => $backend_path . 'contact-entry.js',
                        'deps'    => array( 'super-common', 'jquery-ui-datepicker', 'jquery-ui-sortable' ), 
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'edit-super_contact_entry',
                            'admin_page_super_contact_entry'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'jquery-pep' => array(
                        'src'     => $backend_path . 'jquery-pep.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 'super-forms_page_super_create_form' ),
                        'method'  => 'enqueue',
                    ),
                    'super-settings' => array(
                        'src'     => $backend_path . 'settings.js',
                        'deps'    => array( 'jquery-ui-datepicker', 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 'super-forms_page_super_settings' ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize' => array(
                            'import_working' => esc_html__( 'Importing...', 'super-forms' ),
                            'import_completed' => esc_html__( 'Import completed', 'super-forms' ),
                            'import_error' => esc_html__( 'Import failed: something went wrong while importing.', 'super-forms' ),
                            'export_entries_working' => esc_html__( 'Downloading file...', 'super-forms' ),
                            'export_entries_error' => esc_html__( 'Something went wrong while downloading export.', 'super-forms' ),
                            'deactivate_confirm' => esc_html__( 'This will deactivate your plugin for this domain. Click OK if you are sure to continue!', 'super-forms' ),
                            'deactivate_working' => esc_html__( 'Deactivating plugin...', 'super-forms' ),
                            'deactivate_error' => esc_html__( 'Something went wrong while deactivating the plugin.', 'super-forms' ),
                            'restore_default_confirm' => esc_html__( 'This will delete all your current settings. Click OK if you are sure to continue!', 'super-forms' ),
                            'restore_default_working' => esc_html__( 'Restoring settings...', 'super-forms' ),
                            'restore_default_error' => esc_html__( 'Something went wrong while restoring default settings.', 'super-forms' ),
                            'save_loading' => esc_html__( 'Loading...', 'super-forms' ),
                            'save_settings' => esc_html__( 'Save Settings', 'super-forms' ),
                            'save_success' => esc_html__( 'All settings have been saved.', 'super-forms' ),
                            'save_error' => esc_html__( 'Something went wrong while saving your settings.', 'super-forms' ),
                        ),
                    ),
                    'super-demos' => array(
                        'src'     => $backend_path . 'demos.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 'super-forms_page_super_demos' ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize' => array(
                            'reason' => esc_html__( 'Reason', 'super-forms' ),
                            'reason_empty' => esc_html__( 'Please enter a reason!', 'super-forms' ),
                            'connection_lost' => esc_html__( 'Connection lost, please try again', 'super-forms' ),
                        ),
                    ),
                    // @since 5.0.100 - international phone numbers
                    'super-int-phone' => array(
                        'src'     => $frontend_path . 'int-phone.js',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    // @since 4.8.0 - CarouselJS for "Display Layout > Slider" for Radio/Checkbox elements
                    'super-carousel' => array(
                        'src'     => $frontend_path . 'carousel.js',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'spectrum' => array(
                        'src'     => $frontend_path . 'spectrum.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'upload-iframe-transport' => array(
                        'src'     => $frontend_path . 'jquery-file-upload/jquery.iframe-transport.js',
                        'deps'    => array( 'jquery', 'jquery-ui-widget' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'jquery-fileupload' => array(
                        'src'     => $frontend_path . 'jquery-file-upload/jquery.fileupload.js',
                        'deps'    => array( 'jquery', 'jquery-ui-widget' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'jquery-fileupload-process' => array(
                        'src'     => $frontend_path . 'jquery-file-upload/jquery.fileupload-process.js',
                        'deps'    => array( 'jquery', 'jquery-ui-widget' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'jquery-fileupload-validate' => array(
                        'src'     => $frontend_path . 'jquery-file-upload/jquery.fileupload-validate.js',
                        'deps'    => array( 'jquery', 'jquery-ui-widget' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'simpleslider' => array(
                        'src'     => $backend_path . 'simpleslider.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'tooltip' => array(
                        'src'     => $backend_path . 'tooltips.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-masked-input' => array(
                        'src'     => $frontend_path . 'masked-input.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'masked-currency' => array(
                        'src'     => $frontend_path . 'masked-currency.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-elements' => array(
                        'src'     => $frontend_path . 'elements.js',
                        'deps'    => array( 'super-backend-common', 'spectrum' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'register',
                        'localize' => SUPER_Forms()->elements_i18n,
                    ), 
                    'super-elements' => array(
                        'src'     => $frontend_path . 'elements.js',
                        'deps'    => array( 'super-backend-common', 'spectrum' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'register',
                        'localize' => SUPER_Forms()->elements_i18n,
                    ), 
                    'super-stripe-js-v3' => array(
                        'src'     => 'https://js.stripe.com/v3/',
                        'deps'    => array(),
                        'version' => SUPER_VERSION,
                        'footer'  => true,
                        'screen'  => array( 'super-forms_page_super_addons' ),
                        'method'  => 'enqueue'
                    )
                )
            );
        }
        
        
        /**
         * Localize a script once.
         *
         * @access private
         * @param  string $handle
         *
         * @since       1.0.0
        */
        private static function localize_script( $handle ) {
            if ( ! in_array( $handle, self::$wp_localize_scripts ) && wp_script_is( $handle, 'registered' ) && ( $data = self::get_script_data( $handle ) ) ) {
                $name = str_replace( '-', '_', $handle ) . '_i18n';
                self::$wp_localize_scripts[] = $handle;
                wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
                wp_enqueue_script( $handle );
            }        
        }
        
        
        /**
         * Localize scripts only when enqueued
         *
         * @access private
         * @param  string $handle
         *
         * @since       1.0.0
        */
        public static function localize_printed_scripts() {
            foreach ( self::$scripts as $handle ) {
                self::localize_script( $handle );
            }
        }
        
        
        /**
         * Return data for script handles.
         * @access private
         * @param  string $handle
         * @return array|bool
        */
        private static function get_script_data( $handle ) {
            $scripts = self::get_scripts();
            if( isset( $scripts[$handle]['localize'] ) ) {
                return $scripts[$handle]['localize'];
            }
            return false;
        }


        /**
         * Display message before the content
         *
         * @param  string $content
         *
         * @since       1.0.6
        */
        public function print_message_before_content( $query ) {
            $super_msg = SUPER_Common::getClientData( 'msg' );
            if( $super_msg!=false ) {
                do_action( 'super_before_printing_message', $query );
                if( $super_msg['msg']!='' ) {
                    $custom_content = '';
                    $custom_content .= '<div class="super-msg super-'.$super_msg['type'].' super-visible">';
                    $custom_content .= $super_msg['msg'];
                    $custom_content .= '<span class="super-close"></span>';
                    $custom_content .= '</div>';
                    // @since 2.6.0 - also load the correct styles for success message even if we are on a page that hasn't loaded these styles
                    $form_id = absint($super_msg['data']['hidden_form_id']['value']);
                    $class = ' super-default-squared';
                    if(!empty($settings['theme_style'])) {
                        $class = ' ' . $settings['theme_style'];
                    }
                    echo '<div class="super-form-' . $form_id . $class . '">' . $custom_content . '</div>';
                    $settings = $super_msg['settings'];
                    echo SUPER_Common::load_google_fonts($settings);

                    // Try to load the selected theme style
                    // Always load the default styles
                    $style_content = require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
                    $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/fonts.php' );
                    $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/colors.php' );

                    SUPER_Forms()->form_custom_css .= apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$form_id, 'settings'=>$settings ) );
                    
                    $global_settings = SUPER_Common::get_global_settings();
                    if( !isset( $global_settings['theme_custom_css'] ) ) $global_settings['theme_custom_css'] = '';
                    $global_settings['theme_custom_css'] = stripslashes($global_settings['theme_custom_css']);
                    SUPER_Forms()->form_custom_css .= $global_settings['theme_custom_css'];
                    
                    if( !isset( $settings['form_custom_css'] ) ) $settings['form_custom_css'] = '';
                    $settings['form_custom_css'] = stripslashes($settings['form_custom_css']);
                    SUPER_Forms()->form_custom_css .= $settings['form_custom_css'];
                    
                    if( SUPER_Forms()->form_custom_css!='' ) {
                        echo '<style type="text/css">' . SUPER_Forms()->form_custom_css . '</style>';
                    }

                    SUPER_Common::setClientData( array( 'name'=> 'msg', 'value'=>false  ) );
                }
            }
        }


        /**
         * Duplicates a Contact Entry
         *
         * @since       3.3.0
        */
        public function duplicate_contact_entry_action() {
            if ( empty( $_REQUEST['post'] ) ) {
                wp_die( esc_html__( 'No Contact Entry to duplicate has been supplied!', 'super-forms' ) );
            }

            // Get the original page
            $id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

            check_admin_referer( 'super-duplicate-contact-entry_' . $id );

            $post = $this->get_contact_entry_to_duplicate( $id );

            // Copy the page and insert it
            if ( ! empty( $post ) ) {
                $new_id = $this->duplicate_contact_entry( $post );
                do_action( 'super_duplicate_contact_entry', $new_id, $post );
                wp_redirect( admin_url( 'admin.php?page=super_contact_entry&id=' . $new_id ) );
                exit;
            } else {
                wp_die( esc_html__( 'Contact Entry creation failed, could not find original Contact Entry:', 'super-forms' ) . ' ' . $id );
            }
        }
        public function duplicate_contact_entry( $post, $parent = 0, $post_status = '' ) {
            global $wpdb;
            $new_post_author = wp_get_current_user();
            $new_post_date = current_time( 'mysql' );
            $new_post_date_gmt = get_gmt_from_date( $new_post_date );
            if ( $parent > 0 ) {
                $post_parent = $parent;
                $post_status = $post_status ? $post_status : 'publish';
                $suffix = '';
            } else {
                $post_parent = $post->post_parent;
                $post_status = $post_status ? $post_status : 'publish';
                $suffix = ' ' . esc_html__( '(Copy)', 'super-forms' );
            }
            $wpdb->insert(
                $wpdb->posts,
                array(
                    'post_author'               => $new_post_author->ID,
                    'post_date'                 => $new_post_date,
                    'post_date_gmt'             => $new_post_date_gmt,
                    'post_content'              => $post->post_content,
                    'post_content_filtered'     => $post->post_content_filtered,
                    'post_title'                => $post->post_title . $suffix,
                    'post_excerpt'              => $post->post_excerpt,
                    'post_status'               => $post_status,
                    'post_type'                 => $post->post_type,
                    'comment_status'            => $post->comment_status,
                    'ping_status'               => $post->ping_status,
                    'post_password'             => $post->post_password,
                    'to_ping'                   => $post->to_ping,
                    'pinged'                    => $post->pinged,
                    'post_modified'             => $new_post_date,
                    'post_modified_gmt'         => $new_post_date_gmt,
                    'post_parent'               => $post_parent,
                    'menu_order'                => $post->menu_order,
                    'post_mime_type'            => $post->post_mime_type
                )
            );
            $new_post_id = $wpdb->insert_id;
            $this->duplicate_entry_post_meta( $post->ID, $new_post_id );
            return $new_post_id;
        }
        private function get_contact_entry_to_duplicate( $id ) {
            global $wpdb;
            $id = absint( $id );
            if ( ! $id ) {
                return false;
            }
            $post = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE ID=$id" );
            if ( isset( $post->post_type ) && $post->post_type == "revision" ) {
                $id   = $post->post_parent;
                $post = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE ID=$id" );
            }
            return $post[0];
        }
        private function duplicate_entry_post_meta( $id, $new_id ) {
            global $wpdb;
            $post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=%d AND meta_key;", absint( $id ) ) );
            if ( count( $post_meta_infos ) != 0 ) {
                $sql_query_sel = array();
                $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                foreach ( $post_meta_infos as $meta_info ) {
                    $meta_key = $meta_info->meta_key;
                    $meta_value = addslashes( $meta_info->meta_value );
                    $sql_query_sel[]= $wpdb->prepare( "SELECT %d, '%s', '%s'", $new_id, $meta_key, $meta_value );
                }
                $sql_query.= implode( " UNION ALL ", $sql_query_sel );
                $wpdb->query($sql_query);
            }
            $entry_data = get_post_meta( $id, '_super_contact_entry_data', true );
            add_post_meta( $new_id, '_super_contact_entry_data', $entry_data );
            
            $entry_ip = get_post_meta( $id, '_super_contact_entry_ip', true );
            add_post_meta( $new_id, '_super_contact_entry_ip', $entry_ip );

            // @since 3.4.0 - custom entry status
            $entry_status = get_post_meta( $id, '_super_contact_entry_status', true );
            add_post_meta( $new_id, '_super_contact_entry_status', $entry_status );
        }
        

        /**
         * Duplicates a form
         *
         * @since       1.0.0
        */
        public function duplicate_form_action() {

            if ( empty( $_REQUEST['post'] ) ) {
                wp_die( esc_html__( 'No form to duplicate has been supplied!', 'super-forms' ) );
            }

            // Get the original page
            $id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

            check_admin_referer( 'super-duplicate-form_' . $id );

            $post = $this->get_form_to_duplicate( $id );

            // Copy the page and insert it
            if ( ! empty( $post ) ) {
                $new_id = $this->duplicate_form( $post );
                do_action( 'super_duplicate_form', $new_id, $post );
                wp_redirect( admin_url( 'admin.php?page=super_create_form&id=' . $new_id ) );
                exit;
            } else {
                wp_die( esc_html__( 'Form creation failed, could not find original form:', 'super-forms' ) . ' ' . $id );
            }
        }
        public function duplicate_form( $post, $parent = 0, $post_status = '' ) {
            global $wpdb;
            $new_post_author = wp_get_current_user();
            $new_post_date = current_time( 'mysql' );
            $new_post_date_gmt = get_gmt_from_date( $new_post_date );
            if ( $parent > 0 ) {
                $post_parent = $parent;
                $post_status = $post_status ? $post_status : 'publish';
                $suffix = '';
            } else {
                $post_parent = $post->post_parent;
                $post_status = $post_status ? $post_status : 'publish';
                $suffix = ' ' . esc_html__( '(Copy)', 'super-forms' );
            }
            $wpdb->insert(
                $wpdb->posts,
                array(
                    'post_author'               => $new_post_author->ID,
                    'post_date'                 => $new_post_date,
                    'post_date_gmt'             => $new_post_date_gmt,
                    'post_content'              => $post->post_content,
                    'post_content_filtered'     => $post->post_content_filtered,
                    'post_title'                => $post->post_title . $suffix,
                    'post_excerpt'              => $post->post_excerpt,
                    'post_status'               => $post_status,
                    'post_type'                 => $post->post_type,
                    'comment_status'            => $post->comment_status,
                    'ping_status'               => $post->ping_status,
                    'post_password'             => $post->post_password,
                    'to_ping'                   => $post->to_ping,
                    'pinged'                    => $post->pinged,
                    'post_modified'             => $new_post_date,
                    'post_modified_gmt'         => $new_post_date_gmt,
                    'post_parent'               => $post_parent,
                    'menu_order'                => $post->menu_order,
                    'post_mime_type'            => $post->post_mime_type
                )
            );
            $new_post_id = $wpdb->insert_id;
            $this->duplicate_form_post_meta( $post->ID, $new_post_id );
            return $new_post_id;
        }
        private function get_form_to_duplicate( $id ) {
            global $wpdb;
            $id = absint( $id );
            if ( ! $id ) {
                return false;
            }
            $post = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE ID=$id" );
            if ( isset( $post->post_type ) && $post->post_type == "revision" ) {
                $id   = $post->post_parent;
                $post = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE ID=$id" );
            }
            return $post[0];
        }
        private function duplicate_form_post_meta( $id, $new_id ) {
            global $wpdb;
            $post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=%d AND meta_key;", absint( $id ) ) );
            if ( count( $post_meta_infos ) != 0 ) {
                $sql_query_sel = array();
                $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                foreach ( $post_meta_infos as $meta_info ) {
                    $meta_key = $meta_info->meta_key;
                    $meta_value = addslashes( $meta_info->meta_value );
                    $sql_query_sel[]= $wpdb->prepare( "SELECT %d, '%s', '%s'", $new_id, $meta_key, $meta_value );
                }
                $sql_query.= implode( " UNION ALL ", $sql_query_sel );
                $wpdb->query($sql_query);
            }

            $form_settings = SUPER_Common::get_form_settings($id);
            add_post_meta( $new_id, '_super_form_settings', $form_settings );

            $elements = get_post_meta( $id, '_super_elements', true );
            if( !is_array($elements) ) {
                $elements = json_decode( $elements, true );
            }
            add_post_meta( $new_id, '_super_elements', $elements );

            // @since 4.7.0 - translations
            $translations = SUPER_Common::get_form_translations($id);
            add_post_meta( $new_id, '_super_translations', $translations );
        }


        /**
         * Register post status for contact entries
         *
         *  @since      1.0.0
        */
        public static function custom_contact_entry_status() {
            register_post_status(
                'super_unread',
                array(
                    'label' => esc_html__( 'Unread', 'super-forms' ),
                    'public' => true,
                    'exclude_from_search' => false,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop( 'Unread <span class="count">(%s)</span>', 'Unread <span class="count">(%s)</span>' ),
                )
            );
            register_post_status(
                'super_read', 
                array(
                    'label' => esc_html__('Read', 'super-forms' ),
                    'public' => true,
                    'exclude_from_search' => false,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop( 'Read <span class="count">(%s)</span>', 'Read <span class="count">(%s)</span>' ),
                )
            );
            register_post_status(
                'backup', 
                array(
                    'label' => esc_html__('Backups', 'super-forms' ),
                    'public' => false,
                    'exclude_from_search' => true,
                    'show_in_admin_all_list' => false,
                    'show_in_admin_status_list' => false,
                    'label_count' => _n_noop( 'Backups <span class="count">(%s)</span>', 'Backups <span class="count">(%s)</span>' ),
                )
            );
        }
        public static function append_contact_entry_status_list() {
             global $post;
             $complete = '';
             $label = '';
             if( $post->post_type=='super_contact_entry' ) {
                  if( $post->post_status == 'super_unread' ) {
                       $complete = ' selected="selected"';
                       $label = '<span id="post-status-display"> ' . esc_html__( 'Unread', 'super-forms' ) . '</span>';
                  }
                  echo '<script>
                  jQuery(document).ready(function($){
                       $("select#post_status").append("<option value="archive" ' . $complete . '>' . esc_html__( 'Archive', 'super-forms' ) . '</option>");
                       $(".misc-pub-section label").append("'. $label . '");
                  });
                  </script>';
                  if( $post->post_status == 'super_read' ) {
                       $complete = ' selected="selected"';
                       $label = '<span id="post-status-display"> ' . esc_html__( 'Read', 'super-forms' ) . '</span>';
                  }
                  echo '<script>
                  jQuery(document).ready(function($){
                       $("select#post_status").append("<option value="archive" ' . $complete . '>' . esc_html__( 'Archive', 'super-forms' ) . '</option>");
                       $(".misc-pub-section label").append("' . $label . '");
                  });
                  </script>';
             }
        }


        /**
         * Return data for script handles.
         *
         *  @since      1.0.0
        */        
        public static function register_shortcodes(){
            add_shortcode( 'super_form', array( 'SUPER_Shortcodes', 'super_form_func' ) );
        }

        
        /**
         * Load Localisation files.
         * Note: the first-loaded translation file overrides any following ones if the same translation is present.
         */
        public function load_plugin_textdomain() {
            $locale = apply_filters( 'plugin_locale', get_locale(), 'super-forms' );
            load_textdomain( 'super-forms', WP_LANG_DIR . '/super-forms/super-forms-' . $locale . '.mo' );
            load_plugin_textdomain( 'super-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
        }
        

        /** 
         *  Get Ajax URL
         *
         *  @since      1.0.0
        */
        public function ajax_url() {
            return admin_url( 'admin-ajax.php', 'relative' );
        }
        
    }
endif;


/**
 * Returns the main instance of SUPER_Forms to prevent the need to use globals.
 *
 * @return SUPER_Forms
 */
if(!function_exists('SUPER_Forms')){
    function SUPER_Forms() {
        return SUPER_Forms::instance();
    }

    // Global for backwards compatibility.
    $GLOBALS['super'] = SUPER_Forms();
}
