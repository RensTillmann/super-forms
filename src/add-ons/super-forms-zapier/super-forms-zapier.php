<?php
/**
 * Super Forms - Zapier
 *
 * @package   Super Forms - Zapier
 * @author    feeling4design
 * @link      http://codecanyon.net/user/feeling4design
 * @copyright 2016 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Zapier
 * Plugin URI:  http://codecanyon.net/user/feeling4design
 * Description: Allows you to connect Super Forms with Zapier (zapier.com)
 * Version:     1.3.1
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Zapier')) :


    /**
     * Main SUPER_Zapier Class
     *
     * @class SUPER_Zapier
     * @version 1.0.0
     */
    final class SUPER_Zapier {
    
        
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $version = '1.3.1';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'zapier';
        public $add_on_name = 'Zapier';

        
        /**
         * @var SUPER_Zapier The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Zapier Instance
         *
         * Ensures only one instance of SUPER_Zapier is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Zapier()
         * @return SUPER_Zapier - Main instance
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
         * SUPER_Zapier Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_zapier_loaded');
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
        private function is_request($type){
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
         * Hook into actions and filters
         *
         *  @since      1.0.0
        */
        private function init_hooks() {
            
            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
            
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );
                add_action( 'init', array( $this, 'update_plugin' ) );
            }
            
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_email_success_msg_action', array( $this, 'zapier_static_web_hook' ), 5 , 1 );
            }
            
        }


        /**
         * Load Localisation files.
         * Note: the first-loaded translation file overrides any following ones if the same translation is present.
         */
        public function load_plugin_textdomain() {
            $locale = apply_filters( 'plugin_locale', get_locale(), 'super-forms' );

            load_textdomain( 'super-forms', WP_LANG_DIR . '/super-forms-' . $this->add_on_slug . '/super-forms-' . $this->add_on_slug . '-' . $locale . '.mo' );
            load_plugin_textdomain( 'super-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
        }
        
        
        /**
         * Display activation message for automatic updates
        */
        public function display_activation_msg() {
            if( !class_exists('SUPER_Forms') ) {
                echo '<div class="notice notice-error">'; // notice-success
                    echo '<p>';
                    echo sprintf( 
                        esc_html__( '%sPlease note:%s You must install and activate %4$s%1$sSuper Forms%2$s%5$s in order to be able to use %1$s%s%2$s!', 'super_forms' ), 
                        '<strong>', 
                        '</strong>', 
                        'Super Forms - ' . $this->add_on_name, 
                        '<a target="_blank" href="https://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866">', 
                        '</a>' 
                    );
                    echo '</p>';
                echo '</div>';
            }
        }


        /**
         * Automatically update plugin from the repository
        */
        public function update_plugin() {
            if( defined('SUPER_PLUGIN_DIR') ) {
                if(include( SUPER_PLUGIN_DIR . '/includes/admin/plugin-update-checker/plugin-update-checker.php')){
                    $MyUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
                        'http://f4d.nl/@super-forms-updates/?action=get_metadata&slug=super-forms-' . $this->add_on_slug,  //Metadata URL
                        __FILE__, //Full path to the main plugin file.
                        'super-forms-' . $this->add_on_slug //Plugin slug. Usually it's the same as the name of the directory.
                    );
                }
            }
        }


        /**
         * Hook into form submit action and post the form data to the Zapier webhook
         *
         *  @since      1.0.0
        */
        public static function zapier_static_web_hook( $atts ) {
            extract($atts); // post, data, settings, entry_id, attachments
            $data = wp_unslash($data);

            // Create array for all files with numbered indexes , so that the index on zapier is always the same
            // because the filenames are dynamic and we can't rely on that
            $files = array();
            foreach($attachments as $k => $v){
                $i = 1;
                foreach($v as $ak => $av){
                    $files[$i] = array(
                        'url' => $av,
                        'name' => $ak
                    );
                    $i++;
                }
            }

            // @since 1.0.3 - transfer uploaded files
            $data['_super_attachments'] = $attachments;
            
            if( !empty($settings['zapier_enable']) ) {
                $url = $settings['zapier_webhook'];  
                if(isset($settings['zapier_exclude_settings']) && $settings['zapier_exclude_settings']=='true'){
                    $body = json_encode(
                        array(
                            'files'=>$files, 
                            'data'=>$data
                        )
                    );
                }else{
                    $body = json_encode(
                        array(
                            'files'=>$files, 
                            'data'=>$data, 
                            'settings'=>$settings
                        )
                    );
                }
                $response = wp_remote_post(
                    $url,
                    array(
                        'headers'=>array(
                            'Content-Type'=>'application/json; charset=utf-8'
                        ),
                        'body'=>$body
                    )
                );
                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = 'Zapier: ' . $error_message
                    );
                }
            }
        }


        /**
         * Hook into settings and add Zapier settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {

            $array['zapier'] = array(        
                'hidden' => 'settings',
                'name' => esc_html__( 'Zapier Settings', 'super-forms' ),
                'label' => esc_html__( 'Zapier Settings', 'super-forms' ),
                'fields' => array(
                    'zapier_enable' => array(
                        'desc' => esc_html__( 'Allows you to connect this form with Zapier', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'zapier_enable', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Enable Zapier connection', 'super-forms' ),
                        ),
                        'filter' => true
                    ),
                    'zapier_exclude_settings' => array(
                        'desc' => esc_html__( 'This will prevent all the settings from being send (normally you do not need these for your Zap)', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'zapier_exclude_settings', $settings['settings'], 'true' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Do not send form settings to Zapier (enabled by default)', 'super-forms' ),
                        ),
                        'filter'=>true,
                        'parent'=>'zapier_enable',
                        'filter_value'=>'true'
                    ),
                    'zapier_webhook' => array(
                        'name'=> esc_html__( 'Zapier webhook URL', 'super-forms' ),
                        'desc' => esc_html__( 'You can find your webhook URL when viewing your Zap on zapier.com', 'super-forms' ), 
                        'label'=> sprintf( esc_html__( 'Click %shere%s to get your webhook for Super Forms on Zapier', 'super-forms' ), '<a target="_blank" href="https://zapier.com/developer/invite/57527/bbb10ee808fe8a835a33e29f5249fd2d/">', '</a>' ),
                        'default'=> SUPER_Settings::get_value( 0, 'zapier_webhook', $settings['settings'], '' ),
                        'filter'=>true,
                        'parent'=>'zapier_enable',
                        'filter_value'=>'true'
                    ),
                )
            );
            return $array;
        }



    }
        
endif;


/**
 * Returns the main instance of SUPER_Zapier to prevent the need to use globals.
 *
 * @return SUPER_Zapier
 */
if(!function_exists('SUPER_Zapier')){
    function SUPER_Zapier() {
        return SUPER_Zapier::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Zapier'] = SUPER_Zapier();
}
