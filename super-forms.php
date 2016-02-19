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
 * Plugin Name: Super Forms
 * Plugin URI:  http://codecanyon.net/user/feeling4design
 * Description: Build forms anywhere on your website with ease.
 * Version:     1.0.8.4
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
     * @version	1.0.0
     */
    final class SUPER_Forms {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.0.8.4';

        
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
            $this->define( 'SUPER_PLUGIN_DIR', __DIR__ );
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
                if ( !session_id() ) {
                    session_start();
                }
            }

            if ( $this->is_request( 'frontend' ) ) {

                // Filters since 1.0.0
                add_filter( 'the_content', 'do_shortcode', 10 );
                add_filter( 'widget_text', 'do_shortcode', 10 );

                // Filters since 1.0.6
                add_action( 'loop_start', array( $this, 'print_message_before_content' ) );

                // Actions since 1.0.6
                if( isset( $_SESSION['super_msg'] ) ) {
                    add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_message_scripts' ) );
                }
                
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

                
            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Filters since 1.0.0

                // Actions since 1.0.0

            }
            
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
    
            // Set up localisation
            $this->load_plugin_textdomain();

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

            if( $current_screen->id=='edit-super_form' ) {
                include_once( 'includes/admin/form-list-page.php' );
            }
            if( $current_screen->id=='edit-super_contact_entry' ) {
                include_once( 'includes/admin/contact-entry-list-page.php' );
            }
            
        }
    

        /**
         * Enqueue styles used for displaying messages
         * 
         * @since       1.0.6
        */
        public function enqueue_message_scripts() {
            $settings = get_option('super_settings');
            $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_style( 'super-font-awesome', SUPER_PLUGIN_FILE . 'assets/css/fonts/font-awesome' . $suffix . '.css', array(), SUPER_VERSION );
            wp_enqueue_style( 'super-elements', SUPER_PLUGIN_FILE . 'assets/css/frontend/elements' . $suffix . '.css', array(), SUPER_VERSION );
            $handle = 'super-common';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, SUPER_PLUGIN_FILE . 'assets/js/common' . $suffix . '.js', array( 'jquery' ), SUPER_VERSION, false );  
            wp_localize_script( $handle, $name, array( 'ajaxurl'=>SUPER_Forms()->ajax_url(), 'preload'=>$settings['form_preload'], 'duration'=>$settings['form_duration'] ) );
            wp_enqueue_script( $handle );
            wp_enqueue_script( 'super-elements', SUPER_PLUGIN_FILE . 'assets/js/frontend/elements' . $suffix . '.js', array( 'super-common' ), SUPER_VERSION, false );  
            wp_enqueue_script( 'super-frontend-common', SUPER_PLUGIN_FILE . 'assets/js/frontend/common' . $suffix . '.js', array( 'super-common' ), SUPER_VERSION, false );  
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

            if( $current_screen->id=='super-forms_page_super_create_form' ) {
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

            $suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', SUPER_PLUGIN_FILE ) . 'assets/';
            $backend_path   = $assets_path . 'css/backend/';
            $frontend_path  = $assets_path . 'css/frontend/';
            
            return apply_filters( 
                'super_enqueue_styles', 
                array(
                    'super-common' => array(
                        'src'     => $backend_path . 'common' . $suffix . '.css',
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
                        'src'     => $backend_path . 'create-form' . $suffix . '.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-create-form-responsive' => array(
                        'src'     => $backend_path . 'create-form-responsive' . $suffix . '.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'super-forms_page_super_create_form' ),
                        'method'  => 'enqueue',
                    ),
                    'super-contact-entry' => array(
                        'src'     => $backend_path . 'contact-entry' . $suffix . '.css',
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
                        'src'     => $backend_path . 'settings' . $suffix . '.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'super-forms_page_super_settings' ),
                        'method'  => 'enqueue',
                    ),
                    'super-simpleslider' => array(
                        'src'     => $backend_path . 'simpleslider' . $suffix . '.css',
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
                        'src'     => $backend_path . 'tooltips' . $suffix . '.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),                  
                    'font-awesome' => array(
                        'src'     => $backend_path . 'font-awesome' . $suffix . '.css',
                        'deps'    => '',
                        'version' => SUPER_VERSION,
                        'media'   => 'all',
                        'screen'  => array( 'all' ),
                        'method'  => 'enqueue',
                    ),
                    'super-elements' => array(
                        'src'     => $frontend_path . 'elements' . $suffix . '.css',
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
            
            $suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', SUPER_PLUGIN_FILE ) . 'assets/';
            $backend_path   = $assets_path . 'js/backend/';
            $frontend_path  = $assets_path . 'js/frontend/';
            $settings       = get_option('super_settings');
            
            return apply_filters( 
                'super_enqueue_scripts', 
                array(
                    'jquery-ui-datepicker' => array(
                        'src'     => $frontend_path . 'timepicker' . $suffix . '.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue', // Register because we need to localize it
                    ),
                    'super-timepicker' => array(
                        'src'     => $frontend_path . 'timepicker' . $suffix . '.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue', // Register because we need to localize it
                    ),
                    'super-skype' => array(
                        'src'     => 'http://www.skypeassets.com/i/scom/js/skype-uri.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue', // Register because we need to localize it
                    ),
                    'super-common' => array(
                        'src'     => $assets_path . 'js/common' . $suffix . '.js',
                        'deps'    => array( 'jquery', 'farbtastic', 'wp-color-picker' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize'=> array(
                            'duration' => ( !isset( $settings['form_duration'] ) ? 500 : $settings['form_duration'] ),
                            'preload' => ( !isset( $settings['form_preload'] ) ? '1' : $settings['form_preload'] ),
                        ),
                    ),
                    'super-backend-common' => array(
                        'src'     => $backend_path . 'common' . $suffix . '.js',
                        'deps'    => array( 'super-common' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue'
                    ),
                    'super-create-form' => array(
                        'src'     => $backend_path . 'create-form' . $suffix . '.js',
                        'deps'    => array( 'super-backend-common', 'jquery-ui-sortable' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'super-forms_page_super_create_form'
                        ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize'=> array(
                            'not_editing_an_element' => sprintf( __( 'You are currently not editing an element.%sEdit any alement by clicking the %s icon.', 'super' ), '<br />', '<i class="fa fa-pencil"></i>' )
                        )                        
                    ),
                    'super-contact-entry' => array(
                        'src'     => $backend_path . 'contact-entry' . $suffix . '.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'edit-super_contact_entry',
                            'admin_page_super_contact_entry'
                        ),
                        'method'  => 'enqueue'
                    ),
                    'super-jquery-pep' => array(
                        'src'     => $backend_path . 'jquery-pep' . $suffix . '.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 'super-forms_page_super_create_form' ),
                        'method'  => 'enqueue',
                    ),
                    'super-settings' => array(
                        'src'     => $backend_path . 'settings' . $suffix . '.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 'super-forms_page_super_settings' ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize' => array(
                            'restore_default_confirm' => __( 'This will delete all your current settings. Click OK if you are sure to continue!', 'super' ),
                            'restore_default_working' => __( 'Restoring settings...', 'super' ),
                            'restore_default_error' => __( 'Something went wrong while restoring default settings.', 'super' ),
                            'save_loading' => __( 'Loading...', 'super' ),
                            'save_settings' => __( 'Save Settings', 'super' ),
                            'save_success' => __( 'All settings have been saved.', 'super' ),
                            'save_error' => __( 'Something went wrong while saving your settings.', 'super' ),
                        )
                    ),
                    'super-simpleslider' => array(
                        'src'     => $backend_path . 'simpleslider' . $suffix . '.js',
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
                        'src'     => $backend_path . 'tooltips' . $suffix . '.js',
                        'deps'    => array( 'jquery' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                            'super-forms_page_super_settings'
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-elements' => array(
                        'src'     => $frontend_path . 'elements' . $suffix . '.js',
                        'deps'    => array( 'super-backend-common' ),
                        'version' => SUPER_VERSION,
                        'footer'  => false,
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'register',
                        'localize' => array(
                            'monthNames' => array(
                                __( 'January', 'super' ),
                                __( 'February', 'super' ),
                                __( 'March', 'super' ),
                                __( 'April', 'super' ),
                                __( 'May', 'super' ),
                                __( 'June', 'super' ),
                                __( 'July', 'super' ),
                                __( 'August', 'super' ),
                                __( 'September', 'super' ),
                                __( 'October', 'super' ),
                                __( 'November', 'super' ),
                                __( 'December', 'super' )
                            ),
                            'monthNamesShort' => array(
                                __( 'Jan', 'super' ),
                                __( 'Feb', 'super' ),
                                __( 'Mar', 'super' ),
                                __( 'Apr', 'super' ),
                                __( 'May', 'super' ),
                                __( 'Jun', 'super' ),
                                __( 'Jul', 'super' ),
                                __( 'Aug', 'super' ),
                                __( 'Sep', 'super' ),
                                __( 'Oct', 'super' ),
                                __( 'Nov', 'super' ),
                                __( 'Dec', 'super' )
                            ),
                            'dayNames' => array(
                                __( 'Sunday', 'super' ),
                                __( 'Monday', 'super' ),
                                __( 'Tuesday', 'super' ),
                                __( 'Wednesday', 'super' ),
                                __( 'Thursday', 'super' ),
                                __( 'Friday', 'super' ),
                                __( 'Saturday', 'super' )
                            ),
                            'dayNamesShort' => array(
                                __( 'Sun', 'super' ),
                                __( 'Mon', 'super' ),
                                __( 'Tue', 'super' ),
                                __( 'Wed', 'super' ),
                                __( 'Thu', 'super' ),
                                __( 'Fri', 'super' ),
                                __( 'Sat', 'super' )
                            ),
                            'dayNamesMin' => array(
                                __( 'Su', 'super' ),
                                __( 'Mo', 'super' ),
                                __( 'Tu', 'super' ),
                                __( 'We', 'super' ),
                                __( 'Th', 'super' ),
                                __( 'Fr', 'super' ),
                                __( 'Sa', 'super' )
                            ),
                            'weekHeader' => __( 'Wk', 'super' ),
                        ),
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
                    $custom_content .= '<div class="super-msg '.$_SESSION['super_msg']['type'].'">';
                    $custom_content .= $_SESSION['super_msg']['msg'];
                    $custom_content .= '<span class="close"></span>';
                    $custom_content .= '</div>';
                    unset( $_SESSION['super_msg'] );
                    echo $custom_content;
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
                wp_die( __( 'No form to duplicate has been supplied!', 'super' ) );
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
                wp_die( __( 'Form creation failed, could not find original form:', 'super' ) . ' ' . $id );
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
                $suffix = ' ' . __( '(Copy)', 'super' );
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
            add_post_meta( $new_id, '_super_elements', $raw_shortcode );
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
                    'label' => __( 'Unread', 'super' ),
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
                    'label' => __('Read', 'super'),
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
         * Admin Locales are found in:
         * 		- WP_LANG_DIR/super/super-forms-admin-LOCALE.mo
         * 		- WP_LANG_DIR/plugins/super-forms-admin-LOCALE.mo
         *
         * Frontend/global Locales found in:
         * 		- WP_LANG_DIR/super/super-forms-LOCALE.mo
         * 	 	- super/i18n/languages/super-forms-LOCALE.mo (which if not found falls back to:)
         * 	 	- WP_LANG_DIR/plugins/super-forms-LOCALE.mo
         */
        public function load_plugin_textdomain() {
            $locale = apply_filters( 'plugin_locale', get_locale(), 'super' );
            if( $this->is_request( 'admin' ) ) {
                load_textdomain( 'super', WP_LANG_DIR . '/super/super-forms-admin-' . $locale . '.mo' );
                load_textdomain( 'super', WP_LANG_DIR . '/plugins/super-forms-admin-' . $locale . '.mo' );
            }
            load_textdomain( 'super', WP_LANG_DIR . '/super/super-forms-' . $locale . '.mo' );
            load_plugin_textdomain( 'super', false, plugin_basename( dirname( __FILE__ ) ) . "/i18n/languages" );
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
