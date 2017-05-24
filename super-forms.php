<?php
/**
 * Super Forms
 *
 * @package   Super Forms
 * @author    feeling4design
 * @link      http://codecanyon.net/user/feeling4design
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Drag & Drop Form Builder
 * Plugin URI:  http://codecanyon.net/user/feeling4design
 * Description: Build forms anywhere on your website with ease.
 * Version:     3.0.3
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
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
         *	@since		1.0.0
        */
        public $version = '3.0.3';


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
        public $calendar_i18n;


        /**
         * @var string
         *
         *  @since      1.3
        */
        public $form_custom_css;

       
        /**
         * @var SUPER_Forms The single instance of the class
         *
         *	@since		1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Contains an array of registered script handles
         *
         * @var array
         *
         *	@since		1.0.0
        */
        private static $scripts = array();
        
        
        /**
         * Contains an array of localized script handles
         *
         * @var array
         *
         *	@since		1.0.0
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
         *	@since		1.0.0
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
         *	@since		1.0.0
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
         *	@since		1.0.0
        */
        private function define_constants(){
            
            // define plugin info
            $this->define( 'SUPER_PLUGIN_NAME', 'Super Forms' );
            $this->define( 'SUPER_PLUGIN_FILE', plugin_dir_url( __FILE__ ) );
            $this->define( 'SUPER_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
            $this->define( 'SUPER_PLUGIN_DIR', dirname( __FILE__ ) );
            $this->define( 'SUPER_VERSION', $this->version );
            $this->define( 'SUPER_WC_ACTIVE', in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) );
        
        }

        
        /**
         * Define constant if not already set
         *
         * @param  string $name
         * @param  string|bool $value
         *
         *	@since		1.0.0
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
         *	@since		1.0.0
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
         *	@since		1.0.0
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
         * Hook into actions and filters
         *
         *	@since		1.0.0
        */
        private function init_hooks() {
            
            
            register_activation_hook( __FILE__, array( 'SUPER_Install', 'install' ) );
            
            // @since 1.9
            register_deactivation_hook( __FILE__, array( 'SUPER_Install', 'deactivate' ) );


            add_action( 'init', array( $this, 'init' ), 0 );
            
            // Filters since 1.0.0

            // Actions since 1.0.0
            add_action( 'init', array( $this, 'register_shortcodes' ) );

            if ( ( $this->is_request( 'frontend' ) ) || ( $this->is_request( 'ajax' ) ) ) {
                /**
                 * Session for displaying messages
                 *
                 * @since       1.0.6
                 *
                */
                if( version_compare(phpversion(), '5.4.0') >= 0 ) {
                    if( function_exists('session_status') ) {
                        if( session_status()==PHP_SESSION_NONE ) session_start();
                    }else{
                        if( !session_id() ) session_start();
                    }
                }else{
                    if ( !session_id() ) session_start();
                }

            }

            // Filters since 1.2.3
            if ( ( $this->is_request( 'frontend' ) ) || ( $this->is_request( 'admin' ) ) ) {
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 100, 2 );
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_html_tags_dynamic_function' ), 120, 2 );
            }

            if ( $this->is_request( 'frontend' ) ) {

                // Filters since 1.0.0
                //add_filter( 'the_content', 'do_shortcode', 100 ); // disabled because of some weird conflicts?
                add_filter( 'widget_text', 'do_shortcode', 100 );

                // Filters since 1.0.6
                add_action( 'loop_start', array( $this, 'print_message_before_content' ) );

                // Actions since 1.0.6
                if( isset( $_SESSION['super_msg'] ) ) {
                    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_message_scripts' ) );
                }

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

            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0

                // Actions since 1.0.0
                add_action( 'admin_menu', 'SUPER_Menu::register_menu' );
                add_action( 'current_screen', array( $this, 'after_screen' ), 0 );
                add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                add_action( 'admin_print_scripts', array( $this, 'localize_printed_scripts' ), 5 );
                add_action( 'admin_print_footer_scripts', array( $this, 'localize_printed_scripts' ), 5 );
                add_action( 'admin_action_duplicate_super_form', array( $this, 'duplicate_form_action' ) );
                add_action( 'init', array( $this, 'custom_contact_entry_status' ) );
                add_action( 'admin_footer-post.php', array( $this, 'append_contact_entry_status_list' ) );
                
                // Actions since 1.2.6
                add_action( 'init', array( $this, 'update_super_forms' ) );

                // Actions since 1.7
                add_action( 'restrict_manage_posts', array( $this, 'contact_entry_filter_form_dropdown' ) );
  


            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Filters since 1.0.0

                // Actions since 1.0.0

            }

            // Actions since 1.2.7
            add_action( 'phpmailer_init', array( $this, 'add_string_attachments' ) );


            
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
                    echo '<option value="0">' . __( 'No forms found', 'super-forms' ) . '</option>';
                }else{
                    $super_form_filter = (isset($_GET['super_form_filter']) ? $_GET['super_form_filter'] : 0);
                    echo '<option value="0">' . __( 'All forms', 'super-forms' ) . '</option>';
                    foreach( $forms as $value ) {
                        echo '<option value="' . $value->ID . '" ' . ($value->ID==$super_form_filter ? 'selected="selected"' : '') . '>' . $value->post_title . '</option>';
                    }
                }
                echo '</select>';
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
            echo 'Export to CSV';
            echo '</span>';
            echo '<a style="display:none;" href="#TB_inline?width=600&height=550&inlineId=super-export-entries-content" title="Select & Sort the data that needs to be exported " class="thickbox super-export-entries-thickbox"></a>';
            echo '</div>';
            echo '<div id="super-export-entries-content" style="display:none;"></div>';
        }       


        /**
         * Automatically update Super Forms from the repository
         *
         *  @since      1.2.6
        */
        public static function add_form_styles() {
            if( isset(SUPER_Forms()->form_custom_css) ) {
                $css = SUPER_Forms()->form_custom_css;
                if( $css!='' ) echo '<style type="text/css">' . $css . '</style>';
            }
        }


        /**
         * Automatically update Super Forms from the repository
         *
         *  @since      1.2.6
        */
        function add_string_attachments( $phpmailer ) {
            if( isset( $_SESSION['super_string_attachments'] ) ) {
                foreach( $_SESSION['super_string_attachments'] as $v ) {
                    $phpmailer->AddStringAttachment( $v['data'], $v['filename'], $v['encoding'], $v['type'] );
                }
                unset($_SESSION['super_string_attachments']);
            }
        }


        /**
         * Automatically update Super Forms from the repository
         *
         *  @since      1.2.6
        */
        function update_super_forms() {
            require_once ( 'includes/admin/update-super-forms.php' );
            $plugin_remote_path = 'http://f4d.nl/super-forms/';
            $plugin_slug = plugin_basename( __FILE__ );
            new SUPER_WP_AutoUpdate( $this->version, $plugin_remote_path, $plugin_slug );
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
                $where .= "AND (";
                    $where .= "($table.post_title LIKE '%$s%') OR";
                    $where .= "($table_meta.meta_key = '_super_contact_entry_data' AND $table_meta.meta_value LIKE '%$s%') OR";
                    $where .= "($table_meta.meta_key = '_super_contact_entry_ip' AND $table_meta.meta_value LIKE '%$s%')";
                $where .= ")";
            }
            if( (isset($_GET['super_form_filter'])) && (absint($_GET['super_form_filter'])!=0) ) {
                $super_form_filter = absint($_GET['super_form_filter']);
                $where .= "AND (";
                    $where .= "($table.post_parent = $super_form_filter)";
                $where .= ")";
            }
            if( (isset($_GET['post_status'])) && ($_GET['post_status']!='') && ($_GET['post_status']!='all') ) {
                $post_status = sanitize_text_field($_GET['post_status']);
                $where .= "AND (";
                    $where .= "($table.post_status = '$post_status')";
                $where .= ")";
            }else{
                // @since 2.8.6 - fix issue with showing "All" contact entries also showing deleted items
                $where .= "AND (";
                    $where .= "($table.post_status != 'trash')";
                $where .= ")";     
            }
            $where .= "AND (";
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
         * Hook into the dynamic javascript functions of Super Forms
         *
         *  @since      1.0.0
        */
        public static function add_dynamic_function( $functions ) {
            $functions['before_validating_form_hook'][] = array(
                'name' => 'conditional_logic'
            );
            $functions['after_initializing_forms_hook'][] = array(
                'name' => 'conditional_logic'
            );
            $functions['after_dropdown_change_hook'][] = array(
                'name' => 'conditional_logic'
            );
            $functions['after_field_change_blur_hook'][] = array(
                'name' => 'conditional_logic'
            );
            $functions['after_radio_change_hook'][] = array(
                'name' => 'conditional_logic'
            );
            $functions['after_checkbox_change_hook'][] = array(
                'name' => 'conditional_logic'
            );
            return $functions;
        }


        /**
         * Replace HTML element {tags} with field values
         *
         *  @since      1.2.7
        */
        public static function add_html_tags_dynamic_function( $functions ) {
            $functions['after_initializing_forms_hook'][] = array(
                'name' => 'init_replace_html_tags'
            );
            $functions['before_validating_form_hook'][] = array(
                'name' => 'init_replace_html_tags'
            );
            $functions['after_dropdown_change_hook'][] = array(
                'name' => 'init_replace_html_tags'
            );
            $functions['after_field_change_blur_hook'][] = array(
                'name' => 'init_replace_html_tags'
            );
            $functions['after_radio_change_hook'][] = array(
                'name' => 'init_replace_html_tags'
            );
            $functions['after_checkbox_change_hook'][] = array(
                'name' => 'init_replace_html_tags'
            );
            return $functions;
        }
            

        /**
         * Enqueue [super-form] shortcode styles
         *
         *  @since      1.1.9.5
        */
        public static function enqueue_element_styles() {
            wp_enqueue_style( 'super-font-awesome', SUPER_PLUGIN_FILE . 'assets/css/fonts/font-awesome.min.css', array(), SUPER_VERSION );
            wp_enqueue_style( 'super-elements', SUPER_PLUGIN_FILE . 'assets/css/frontend/elements.min.css', array(), SUPER_VERSION );
        }


        /**
         * Enqueue [super-form] shortcode scripts
         *
         *  @since      1.1.9.5
        */
        public static function enqueue_element_scripts( $settings=array(), $ajax=false ) {

            $handle = 'super-common';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/common.min.js', array( 'jquery' ), SUPER_VERSION, false );  

            // @since 3.1.0 - add WPML langauge parameter to ajax URL's required for for instance when redirecting to WooCommerce checkout/cart page
            $ajax_url = SUPER_Forms()->ajax_url();
            $my_current_lang = apply_filters( 'wpml_current_language', NULL ); 
            if ( $my_current_lang ) $ajax_url = add_query_arg( 'lang', $my_current_lang, $ajax_url );

            wp_localize_script(
                $handle,
                $name,
                array( 
                    'ajaxurl'=>$ajax_url,
                    'preload'=>$settings['form_preload'],
                    'duration'=>$settings['form_duration'],
                    'dynamic_functions' => SUPER_Common::get_dynamic_functions(),
                    'loading'=>SUPER_Forms()->common_i18n['loading'],
                    'directions'=>SUPER_Forms()->common_i18n['directions'],
                    'errors'=>SUPER_Forms()->common_i18n['errors']
                )
            );
            wp_enqueue_script( $handle );
            
            $handle = 'super-elements';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/frontend/elements.min.js', array( 'super-common' ), SUPER_VERSION, false );  
            wp_localize_script( $handle, $name, SUPER_Forms()->calendar_i18n );
            wp_enqueue_script( $handle );

            $handle = 'super-frontend-common';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/frontend/common.min.js', array( 'super-common' ), SUPER_VERSION, false );  
            wp_localize_script( $handle, $name, array( 'includes_url'=>includes_url(), 'plugin_url'=>SUPER_PLUGIN_FILE ) );
            wp_enqueue_script( $handle );

            // Add js files that are needed in case when theme makes an Ajax call to load content dynamically
            if( $ajax==true ) {
                // We need to add these, just in case the form has an file upload element
                wp_enqueue_script( 'jquery-ui-datepicker', false, array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_script( 'jquery-timepicker', SUPER_PLUGIN_FILE . 'assets/js/frontend/timepicker.min.js', array( 'jquery' ), SUPER_VERSION, false );
                wp_enqueue_script( 'super-date-format', SUPER_PLUGIN_FILE . 'assets/js/frontend/date-format.min.js', array( 'jquery' ), SUPER_VERSION, false );
        
                wp_enqueue_style( 'super-simpleslider', SUPER_PLUGIN_FILE . 'assets/css/backend/simpleslider.min.css', array(), SUPER_VERSION, false ); 
                wp_enqueue_script( 'super-simpleslider', SUPER_PLUGIN_FILE . 'assets/js/backend/simpleslider.min.js', array( 'jquery' ), SUPER_VERSION, false );

                $dir = SUPER_PLUGIN_FILE . 'assets/js/frontend/jquery-file-upload/';
                wp_enqueue_script( 'super-upload-iframe-transport', $dir . 'jquery.iframe-transport.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
                wp_enqueue_script( 'super-upload-fileupload', $dir . 'jquery.fileupload.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
                wp_enqueue_script( 'super-upload-fileupload-process', $dir . 'jquery.fileupload-process.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
                wp_enqueue_script( 'super-upload-fileupload-validate', $dir . 'jquery.fileupload-validate.js', array( 'jquery', 'jquery-ui-widget' ), SUPER_VERSION, false );
                
                // Needed for Text Editor
                wp_enqueue_media();

            }

            // @since 1.2.8 -   super_after_enqueue_element_scripts_action
            do_action( 'super_after_enqueue_element_scripts_action', array( 'settings'=>$settings, 'ajax'=>$ajax ) );

        }


        /**
         * Enqueue scripts before ajax call is made
         *
         *  @since      1.1.9.5
        */
        public static function load_frontend_scripts_before_ajax() {

            $settings = get_option( 'super_settings' );
            if( isset( $settings['enable_ajax'] ) ) {
                if( $settings['enable_ajax']=='1' ) {            
                    require_once( SUPER_PLUGIN_DIR . '/includes/class-settings.php' );
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
                    $settings = array_merge( $array, $settings );
                    self::enqueue_element_styles();
                    self::enqueue_element_scripts( $settings, true );
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
         *	@since		1.0.0
        */
        public function ajax_includes() {
            
            include_once('includes/class-ajax.php'); // Ajax functions for admin and the front-end
        
        }

        
        /**
         * Include required frontend files.
         *
         *	@since		1.0.0
        */
        public function frontend_includes() {
                        
        }

        
        /**
         * Init Super Forms when WordPress Initialises.
         *
         *	@since		1.0.0
        */
        public function init() {

            // Before init action
            do_action('before_super_init');
    
            $this->load_plugin_textdomain();

            $this->common_i18n = array(
                'loading' => __( 'Loading...', 'super-forms' ),
                'directions' => array(
                    'next' => __( 'Next', 'super-forms' ),
                    'prev' => __( 'Prev', 'super-forms' ),
                ),
                'errors' => array(
                    'fields' => array(
                        'required' => __( 'Field is required!', 'super-forms' )
                    ),
                    'file_upload' => array(
                        'incorrect_file_extension' => __( 'Sorry, file extension is not allowed!', 'super-forms' )
                    )
                )
            );

            $this->calendar_i18n = array(
                'monthNames' => array(
                    __( 'January', 'super-forms' ),
                    __( 'February', 'super-forms' ),
                    __( 'March', 'super-forms' ),
                    __( 'April', 'super-forms' ),
                    __( 'May', 'super-forms' ),
                    __( 'June', 'super-forms' ),
                    __( 'July', 'super-forms' ),
                    __( 'August', 'super-forms' ),
                    __( 'September', 'super-forms' ),
                    __( 'October', 'super-forms' ),
                    __( 'November', 'super-forms' ),
                    __( 'December', 'super-forms' )
                ),
                'monthNamesShort' => array(
                    __( 'Jan', 'super-forms' ),
                    __( 'Feb', 'super-forms' ),
                    __( 'Mar', 'super-forms' ),
                    __( 'Apr', 'super-forms' ),
                    __( 'May', 'super-forms' ),
                    __( 'Jun', 'super-forms' ),
                    __( 'Jul', 'super-forms' ),
                    __( 'Aug', 'super-forms' ),
                    __( 'Sep', 'super-forms' ),
                    __( 'Oct', 'super-forms' ),
                    __( 'Nov', 'super-forms' ),
                    __( 'Dec', 'super-forms' )
                ),
                'dayNames' => array(
                    __( 'Sunday', 'super-forms' ),
                    __( 'Monday', 'super-forms' ),
                    __( 'Tuesday', 'super-forms' ),
                    __( 'Wednesday', 'super-forms' ),
                    __( 'Thursday', 'super-forms' ),
                    __( 'Friday', 'super-forms' ),
                    __( 'Saturday', 'super-forms' )
                ),
                'dayNamesShort' => array(
                    __( 'Sun', 'super-forms' ),
                    __( 'Mon', 'super-forms' ),
                    __( 'Tue', 'super-forms' ),
                    __( 'Wed', 'super-forms' ),
                    __( 'Thu', 'super-forms' ),
                    __( 'Fri', 'super-forms' ),
                    __( 'Sat', 'super-forms' )
                ),
                'dayNamesMin' => array(
                    __( 'Su', 'super-forms' ),
                    __( 'Mo', 'super-forms' ),
                    __( 'Tu', 'super-forms' ),
                    __( 'We', 'super-forms' ),
                    __( 'Th', 'super-forms' ),
                    __( 'Fr', 'super-forms' ),
                    __( 'Sa', 'super-forms' )
                ),
                'weekHeader' => __( 'Wk', 'super-forms' ),
            );

            // Init action
            do_action('super_init');
            
        }
        
        
        /**
         * Call Classes and Execute Functions based on current screen ID 
         *
         * @param  string $current_screen
         * 
         * @since		1.0.0
        */
        public function after_screen( $current_screen ) {

            // @since 1.7 - add the export button only on the super_contact_entry page
            if( $current_screen->id=='edit-super_contact_entry' ) {
                add_action( 'manage_posts_extra_tablenav', array( $this, 'contact_entry_export_button' ) );
                add_filter( 'posts_where', array( $this, 'custom_posts_where' ), 0, 2 );
                add_filter( 'posts_join', array( $this, 'custom_posts_join' ), 0, 2 );
                add_filter( 'posts_groupby', array( $this, 'custom_posts_groupby' ), 0, 2 );
            }

            if( $current_screen->id=='edit-super_form' ) {
                include_once( 'includes/admin/form-list-page.php' );
            }
            if( $current_screen->id=='edit-super_contact_entry' ) {
                include_once( 'includes/admin/contact-entry-list-page.php' );
            }

            // @since 1.2.8 -   check if plugin is updated
            if( $current_screen->id=='update' ) {
                if( (isset($_REQUEST['action'])) && (isset($_REQUEST['plugin'])) ) {
                    if( ($_REQUEST['action']=='upgrade-plugin') && ($_REQUEST['plugin']=='super-forms/super-forms.php') ){
                        $downloaded = wp_remote_fopen('http://f4d.nl/super-forms/download/super-forms/');
                    }
                }
            }
            
        }
    

        /**
         * Enqueue styles used for displaying messages
         * 
         * @since       1.0.6
        */
        public function enqueue_message_scripts() {
            $settings = get_option('super_settings');
            //$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_style( 'super-font-awesome', SUPER_PLUGIN_FILE . 'assets/css/fonts/font-awesome.min.css', array(), SUPER_VERSION );
            wp_enqueue_style( 'super-elements', SUPER_PLUGIN_FILE . 'assets/css/frontend/elements.min.css', array(), SUPER_VERSION );
            
            $handle = 'super-common';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/common.min.js', array( 'jquery' ), SUPER_VERSION, false );

            // @since 3.1.0 - add WPML langauge parameter to ajax URL's required for for instance when redirecting to WooCommerce checkout/cart page
            $ajax_url = SUPER_Forms()->ajax_url();
            $my_current_lang = apply_filters( 'wpml_current_language', NULL ); 
            if ( $my_current_lang ) $ajax_url = add_query_arg( 'lang', $my_current_lang, $ajax_url );

            wp_localize_script(
                $handle,
                $name,
                array( 
                    'ajaxurl'=>$ajax_url,
                    'preload'=>$settings['form_preload'],
                    'duration'=>$settings['form_duration'],
                    'dynamic_functions' => SUPER_Common::get_dynamic_functions(),
                    'loading'=>$this->common_i18n['loading'],
                    'directions'=>$this->common_i18n['directions'],
                    'errors'=>$this->common_i18n['errors']
                )
            );
            wp_enqueue_script( $handle );

            $handle = 'super-elements';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/frontend/elements.min.js', array( 'super-common' ), SUPER_VERSION, false );  
            wp_localize_script(
                $handle,
                $name,
                $this->calendar_i18n
            );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'super-frontend-common', SUPER_PLUGIN_FILE . 'assets/js/frontend/common.min.js', array( 'super-common' ), SUPER_VERSION, false );  
        }


        /**
         * Enqueue scripts for each admin page
         * 
         * @since		1.0.0
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
         * @since		1.0.0
        */
        public static function get_styles() {

            //$suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', SUPER_PLUGIN_FILE ) . 'assets/';
            $backend_path   = $assets_path . 'css/backend/';
            $frontend_path  = $assets_path . 'css/frontend/';
            
            return apply_filters( 
                'super_enqueue_styles', 
                array(
                    'super-common' => array(
                        'src'     => $backend_path . 'common.min.css',
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
                        'src'     => $backend_path . 'create-form.min.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-create-form-responsive' => array(
                        'src'     => $backend_path . 'create-form-responsive.min.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'super-forms_page_super_create_form' ),
                        'method'  => 'enqueue',
                    ),
                    'super-contact-entry' => array(
                        'src'     => $backend_path . 'contact-entry.min.css',
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
                        'src'     => $backend_path . 'settings.min.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'super-forms_page_super_settings' ),
                        'method'  => 'enqueue',
                    ),
                    'super-documentation' => array(
                        'src'     => $backend_path . 'documentation.min.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'super-forms_page_super_documentation' ),
                        'method'  => 'enqueue',
                    ),
                    'super-marketplace' => array(
                        'src'     => $backend_path . 'marketplace.min.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'super-forms_page_super_marketplace' ),
                        'method'  => 'enqueue',
                    ),
                    'super-simpleslider' => array(
                        'src'     => $backend_path . 'simpleslider.min.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-tooltip' => array(
                        'src'     => $backend_path . 'tooltips.min.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),                  
                    'super-font-awesome' => array(
                        'src'     => $backend_path . 'font-awesome.min.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings',
                            'super-forms_page_super_documentation',
                            'edit-super_contact_entry',
                            'admin_page_super_contact_entry',
                            'super-forms_page_super_marketplace'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-elements' => array(
                        'src'     => $frontend_path . 'elements.min.css',
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
         * @since		1.0.0
        */
        public static function get_scripts() {
            
            //$suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', SUPER_PLUGIN_FILE ) . 'assets/';
            $backend_path   = $assets_path . 'js/backend/';
            $frontend_path  = $assets_path . 'js/frontend/';
            $settings       = get_option('super_settings');

            return apply_filters( 
                'super_enqueue_scripts', 
                array(
                    'jquery-ui-datepicker' => array(
                        'src'     => $frontend_path . 'timepicker.min.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue', // Register because we need to localize it
                    ),
                    'super-timepicker' => array(
                        'src'     => $frontend_path . 'timepicker.min.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue', // Register because we need to localize it
                    ),
                    'super-date-format' => array(
                        'src'     => $frontend_path . 'date-format.min.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue', // Register because we need to localize it
                    ),
                    'super-skype' => array(
                        'src'     => 'https://secure.skypeassets.com/i/scom/js/skype-uri.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue', // Register because we need to localize it
                    ),
                    'super-common' => array(
                        'src'     => $assets_path . 'js/common.min.js',
                        'deps'    => array( 'jquery', 'farbtastic', 'wp-color-picker' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize'=> array(
                            'preload' => ( !isset( $settings['form_preload'] ) ? '1' : $settings['form_preload'] ),
                            'duration' => ( !isset( $settings['form_duration'] ) ? 500 : $settings['form_duration'] ),
                            'dynamic_functions' => SUPER_Common::get_dynamic_functions(),
                            'loading' => SUPER_Forms()->common_i18n['loading'],
                            'directions' => SUPER_Forms()->common_i18n['directions'],
                            'errors' => SUPER_Forms()->common_i18n['errors']
                        )
                    ),
                    'super-backend-common' => array(
                        'src'     => $backend_path . 'common.min.js',
                        'deps'    => array( 'super-common' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-create-form' => array(
                        'src'     => $backend_path . 'create-form.min.js',
                        'deps'    => array( 'super-backend-common', 'jquery-ui-sortable' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize'=> array(
                            'not_editing_an_element' => sprintf( __( 'You are currently not editing an element.%sEdit any alement by clicking the %s icon.', 'super-forms' ), '<br />', '<i class="fa fa-pencil"></i>' )
                        ),
                    ),
                    'super-contact-entry' => array(
                        'src'     => $backend_path . 'contact-entry.min.js',
                        'deps'    => array( 'jquery', 'jquery-ui-sortable' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'edit-super_contact_entry',
                            'admin_page_super_contact_entry'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-jquery-pep' => array(
                        'src'     => $backend_path . 'jquery-pep.min.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 'super-forms_page_super_create_form' ),
                        'method'  => 'enqueue',
                    ),
                    'super-settings' => array(
                        'src'     => $backend_path . 'settings.min.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 'super-forms_page_super_settings' ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize' => array(
                            'import_working' => __( 'Importing...', 'super-forms' ),
                            'import_completed' => __( 'Import completed', 'super-forms' ),
                            'import_error' => __( 'Import failed: something went wrong while importing.', 'super-forms' ),
                            'export_entries_working' => __( 'Downloading file...', 'super-forms' ),
                            'export_entries_error' => __( 'Something went wrong while downloading export.', 'super-forms' ),
                            'deactivate_confirm' => __( 'This will deactivate your plugin for this domain. Click OK if you are sure to continue!', 'super-forms' ),
                            'deactivate_working' => __( 'Deactivating plugin...', 'super-forms' ),
                            'deactivate_error' => __( 'Something went wrong while deactivating the plugin.', 'super-forms' ),
                            'restore_default_confirm' => __( 'This will delete all your current settings. Click OK if you are sure to continue!', 'super-forms' ),
                            'restore_default_working' => __( 'Restoring settings...', 'super-forms' ),
                            'restore_default_error' => __( 'Something went wrong while restoring default settings.', 'super-forms' ),
                            'save_loading' => __( 'Loading...', 'super-forms' ),
                            'save_settings' => __( 'Save Settings', 'super-forms' ),
                            'save_success' => __( 'All settings have been saved.', 'super-forms' ),
                            'save_error' => __( 'Something went wrong while saving your settings.', 'super-forms' ),
                        ),
                    ),
                    'super-markjs' => array(
                        'src' => $backend_path . 'jquery.mark.min.js',
                        'deps' => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer' => false,
                        'screen' => array( 'super-forms_page_super_documentation' ),
                        'method' => 'enqueue',
                    ),
                    'super-marketplace' => array(
                        'src'     => $backend_path . 'marketplace.min.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 'super-forms_page_super_marketplace' ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize' => array(
                            'reason' => __( 'Reason', 'super-forms' ),
                            'reason_empty' => __( 'Please enter a reason!', 'super-forms' ),
                            'connection_lost' => __( 'Connection lost, please try again', 'super-forms' ),
                        ),
                    ),
                    'super-simpleslider' => array(
                        'src'     => $backend_path . 'simpleslider.min.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-tooltip' => array(
                        'src'     => $backend_path . 'tooltips.min.js',
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
                        'src'     => $frontend_path . 'masked-input.min.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-masked-currency' => array(
                        'src'     => $frontend_path . 'masked-currency.min.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-elements' => array(
                        'src'     => $frontend_path . 'elements.min.js',
                        'deps'    => array( 'super-backend-common' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'register',
                        'localize' => SUPER_Forms()->calendar_i18n,
                    ),      
                )
            );
        }
        
        
        /**
         * Localize a script once.
         *
         * @access private
         * @param  string $handle
         *
         * @since		1.0.0
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
         * @since		1.0.0
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
            if( isset( $_SESSION['super_msg'] ) ) {
                do_action( 'super_before_printing_message', $query );
                if( $_SESSION['super_msg']['msg']!='' ) {
                    $custom_content = '';
                    $custom_content .= '<div class="super-msg super-'.$_SESSION['super_msg']['type'].'">';
                    $custom_content .= $_SESSION['super_msg']['msg'];
                    $custom_content .= '<span class="close"></span>';
                    $custom_content .= '</div>';

                    // @since 2.6.0 - also load the correct styles for success message even if we are on a page that hasn't loaded these styles
                    $id = absint($_SESSION['super_msg']['data']['hidden_form_id']['value']);
                    echo '<div class="super-form-' . $id . '">' . $custom_content . '</div>';
                    $style_content  = '';
                    $settings = $_SESSION['super_msg']['settings'];
                    if( ( isset( $settings['theme_style'] ) ) && ( $settings['theme_style']!='' ) ) {
                        $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/' . str_replace( 'super-', '', $settings['theme_style'] ) . '.php' );
                    }
                    $style_content .= require( SUPER_PLUGIN_DIR . '/assets/css/frontend/themes/style-default.php' );
                    SUPER_Forms()->form_custom_css .= apply_filters( 'super_form_styles_filter', $style_content, array( 'id'=>$id, 'settings'=>$settings ) );
                    $settings_default = get_option( 'super_settings' );
                    if( !isset( $settings_default['theme_custom_css'] ) ) $settings_default['theme_custom_css'] = '';
                    $settings_default['theme_custom_css'] = stripslashes($settings_default['theme_custom_css']);
                    SUPER_Forms()->form_custom_css .= $settings_default['theme_custom_css'];
                    if( !isset( $settings['form_custom_css'] ) ) $settings['form_custom_css'] = '';
                    $settings['form_custom_css'] = stripslashes($settings['form_custom_css']);
                    SUPER_Forms()->form_custom_css .= $settings['form_custom_css'];
                    if( SUPER_Forms()->form_custom_css!='' ) {
                        echo '<style type="text/css">' . SUPER_Forms()->form_custom_css . '</style>';
                    }
                    unset( $_SESSION['super_msg'] );
                }
            }
        }


        /**
         * Duplicates a form
         *
         * @since       1.0.0
        */
        public function duplicate_form_action() {

            if ( empty( $_REQUEST['post'] ) ) {
                wp_die( __( 'No form to duplicate has been supplied!', 'super-forms' ) );
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
                wp_die( __( 'Form creation failed, could not find original form:', 'super-forms' ) . ' ' . $id );
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
                $suffix = ' ' . __( '(Copy)', 'super-forms' );
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
            $this->duplicate_post_meta( $post->ID, $new_post_id );
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
        private function duplicate_post_meta( $id, $new_id ) {
            global $wpdb;
            $post_meta_infos = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=%d AND meta_key;", absint( $id ) ) );
            if ( count( $post_meta_infos ) != 0 ) {
                $sql_query_sel = array();
                $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
                foreach ( $post_meta_infos as $meta_info ) {
                    $meta_key = $meta_info->meta_key;
                    $meta_value = addslashes( $meta_info->meta_value );
                    $sql_query_sel[]= "SELECT $new_id, '$meta_key', '$meta_value'";
                }
                $sql_query.= implode( " UNION ALL ", $sql_query_sel );
                $wpdb->query($sql_query);
            }
            $form_settings = get_post_meta( $id, '_super_form_settings', true );
            $raw_shortcode = get_post_meta( $id, '_super_elements', true );
            add_post_meta( $new_id, '_super_form_settings', $form_settings );
            add_post_meta( $new_id, '_super_elements', wp_slash($raw_shortcode) );
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
                    'label' => __( 'Unread', 'super-forms' ),
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
                    'label' => __('Read', 'super-forms' ),
                    'public' => true,
                    'exclude_from_search' => false,
                    'show_in_admin_all_list' => true,
                    'show_in_admin_status_list' => true,
                    'label_count' => _n_noop( 'Read <span class="count">(%s)</span>', 'Read <span class="count">(%s)</span>' ),
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
                       $label = '<span id="post-status-display"> Unread</span>';
                  }
                  echo '<script>
                  jQuery(document).ready(function($){
                       $("select#post_status").append("<option value="archive" ' . $complete . '>Archive</option>");
                       $(".misc-pub-section label").append("'. $label . '");
                  });
                  </script>';
                  if( $post->post_status == 'super_read' ) {
                       $complete = ' selected="selected"';
                       $label = '<span id="post-status-display"> Read</span>';
                  }
                  echo '<script>
                  jQuery(document).ready(function($){
                       $("select#post_status").append("<option value="archive" ' . $complete . '>Archive</option>");
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
         *
         * Note: the first-loaded translation file overrides any following ones if the same translation is present.
         *
         * Locales found in:
         *      - WP_LANG_DIR/super-forms/super-LOCALE.mo
         *      - WP_LANG_DIR/plugins/super-LOCALE.mo
         */
        public function load_plugin_textdomain() {
            $locale = apply_filters( 'plugin_locale', get_locale(), 'super-forms' );

            load_textdomain( 'super-forms', WP_LANG_DIR . '/super-forms/super-forms-' . $locale . '.mo' );
            load_plugin_textdomain( 'super-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
        }
        

        /** 
         *	Get Ajax URL
         *
         *	@since		1.0.0
        */
        public function ajax_url() {
            return admin_url( 'admin-ajax.php', 'relative' );
        }
        


        /**
         * Add the Add-on activation under the "Activate" TAB
         * 
         * @since       2.0.0
        */
        public static function add_on_activation($array, $add_on, $add_on_name) {
            $settings = get_option( 'super_settings' );
            if(!isset($settings['license_' . $add_on])) $settings['license_' . $add_on] = '';
            $sac = get_option( 'sac_' . $add_on, 0 );
            if( $sac==1 ) {
                $sact = '<strong style="color:green;">' . __( 'Add-on is activated!', 'super-forms' ) . '</strong>';
                $dact = '<br /><br />---';
                $dact .= '<br /><br /><strong style="color:green;">' . __( 'If you want to transfer this add-on to another domain,', 'super-forms' ) . '<br />';
                $dact .= __( 'you can deactivate it on this domain by clicking the following button:', 'super-forms' ) . '</strong>';
                $dact .= '<br /><br /><span class="button super-button deactivate-add-on">' . __( 'Deactivate on current domain', 'super-forms' ) . '</span>';
            }else{
                $dact = '';
                $sact = '<strong style="color:red;">' . __( 'Add-on is not yet activated!', 'super-forms' ) . '</strong>';
                $sact .= '<br /><br />---';
                $sact .= '<br /><br /><span class="button super-button activate-add-on">' . __( 'Activate', 'super-forms' ) . '</span>';
                $sact .= '';
            }
            $new_activation_html = '';
            $new_activation_html .= '<div class="super-field">';
            $new_activation_html .= '<div class="super-field-info"></div>';
            $new_activation_html .= '<div class="input"><strong>Super Forms - ' . $add_on_name . '</strong><br /><input type="text" name="license_' . $add_on . '" class="element-field" value="' . $settings['license_' . $add_on] . '" /></div>';
            $new_activation_html .= '<input type="hidden" name="add_on" value="' . $add_on . '" />';
            $new_activation_html .= '<div class="input add-on-activation-msg">' . $sact . $dact . '</div>';
            $new_activation_html .= '</div>';
            $array['activation']['html'][] = $new_activation_html;
            return $array;
        }


        /**  
         *  Deactivate
         *
         *  Upon plugin deactivation delete activation
         *
         *  @since      2.0.0
         */
        public static function add_on_deactivate($add_on){
            $settings = get_option( 'super_settings' );
            if(isset($settings['license_' . $add_on])){
                $license = $settings['license_' . $add_on];
                $domain = $_SERVER['SERVER_NAME'];
                $url = 'http://f4d.nl/super-forms/?api=license-deactivate-add-on&add-on=' . $add_on . '&key=' . $license . '&domain=' . $domain;
                wp_remote_get( $url, array('timeout'=>60) );
            }
            delete_option( 'sac_' . $add_on );
        }


        /**
         * Check license and show activation message
         * 
         * @since       2.0.0
        */
        public static function add_on_activation_message( $activation_msg, $add_on, $add_on_name ) {
            $sac = get_option( 'sac_' . $add_on, 0 );
            if( $sac!=1 ) {
                $activation_msg .= '<div class="super-msg super-error"><h1>Please note:</h1>';
                $activation_msg .= __( 'You haven\'t activated Super Forms - ' . $add_on_name . ' yet', 'super-forms' ) . '<br />';
                $activation_msg .= __( 'Please click <a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#activate">here</a> and enter you Purchase Code under the Activation TAB.', 'super-forms' );
                $activation_msg .= '<span class="close"></span>';
                $activation_msg .= '</div>';
            }
            return $activation_msg;
        }



        
        /** 
         *	Sample function title
         *
         *	Sample function description
         *  @param  string $name
         *  @param  string $value
         *
         *	@since		1.0.0
        */
        public function sample_function() {
           
        }
        
        
    }
endif;


/**
 * Returns the main instance of SUPER_Forms to prevent the need to use globals.
 *
 * @return SUPER_Forms
 */
function SUPER_Forms() {
    return SUPER_Forms::instance();
}


// Global for backwards compatibility.
$GLOBALS['super'] = SUPER_Forms();
