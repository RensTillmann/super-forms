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
 * Version:     1.0.3
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
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
        public $version = '1.0.3';


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
            
            register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
            
            // Filters since 1.0.0
            add_filter( 'super_after_activation_message_filter', array( $this, 'activation_message' ), 10, 2 );

            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );
                add_filter( 'super_settings_end_filter', array( $this, 'activation' ), 100, 2 );

                // Actions since 1.0.0
                add_action( 'init', array( $this, 'update_plugin' ) );

            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Actions since 1.0.0
                add_action( 'super_before_email_success_msg_action', array( $this, 'zapier_static_web_hook' ), 5 , 1 );

            }
            
        }

        
        /**
         * Automatically update plugin from the repository
         *
         *  @since      1.0.0
        */
        function update_plugin() {
            if( defined('SUPER_PLUGIN_DIR') ) {
                require_once ( SUPER_PLUGIN_DIR . '/includes/admin/update-super-forms.php' );
                $plugin_remote_path = 'http://f4d.nl/super-forms/';
                $plugin_slug = plugin_basename( __FILE__ );
                new SUPER_WP_AutoUpdate( $this->version, $plugin_remote_path, $plugin_slug, '', '', $this->add_on_slug );
            }
        }


        /**
         * Add the activation under the "Activate" TAB
         * 
         * @since       1.0.0
        */
        public function activation($array, $data) {
            if (method_exists('SUPER_Forms','add_on_activation')) {
                return SUPER_Forms::add_on_activation($array, $this->add_on_slug, $this->add_on_name);
            }else{
                return $array;
            }
        }


        /**  
         *  Deactivate
         *
         *  Upon plugin deactivation delete activation
         *
         *  @since      1.0.0
         */
        public static function deactivate(){
            if (method_exists('SUPER_Forms','add_on_deactivate')) {
                SUPER_Forms::add_on_deactivate(SUPER_Zapier()->add_on_slug);
            }
        }


        /**
         * Check license and show activation message
         * 
         * @since       1.0.0
        */
        public function activation_message( $activation_msg, $data ) {
            if (method_exists('SUPER_Forms','add_on_activation_message')) {
                $settings = $data['settings'];
                if( (isset($settings['zapier_enable'])) && ($settings['zapier_enable']=='true') ) {
                    return SUPER_Forms::add_on_activation_message($activation_msg, $this->add_on_slug, $this->add_on_name);
                }
            }
            return $activation_msg;
        }


        /**
         * Hook into form submit action and post the form data to the Zapier webhook
         *
         *  @since      1.0.0
        */
        public static function zapier_static_web_hook( $data ) {
            if(isset($data['attachments'])){
                $attachments = $data['attachments'];
            }
            $post = $data['post'];
            $settings = $data['settings'];
            $entry_id = $data['entry_id'];
            $data = $post['data'];
            // @since 1.0.3 - transfer uploaded files
            if(isset($attachments)){
                $data['_super_attachments'] = $attachments;
            }
            if( !empty($settings['zapier_enable']) ) {
                $url = $settings['zapier_webhook'];  
                $body = json_encode(array('data'=>$data, 'settings'=>$settings));
                $result = wp_remote_post(
                    $url,
                    array(
                        'headers'=>array(
                            'Content-Type'=>'application/json; charset=utf-8'
                        ),
                        'body'=>$body
                    )
                );
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
                'name' => __( 'Zapier Settings', 'super-forms' ),
                'label' => __( 'Zapier Settings', 'super-forms' ),
                'fields' => array(
                    'zapier_enable' => array(
                        'desc' => __( 'Allows you to connect this form with Zapier', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'zapier_enable', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Enable Zapier connection', 'super-forms' ),
                        ),
                        'filter' => true
                    ),
                    'zapier_webhook' => array(
                        'name'=> __( 'Zapier webhook URL', 'super-forms' ),
                        'desc' => __( 'You can find your webhook URL when viewing your Zap on zapier.com', 'super-forms' ), 
                        'label'=> __( 'Click <a target="_blank" href="https://zapier.com/developer/invite/57527/bbb10ee808fe8a835a33e29f5249fd2d/">here</a> to get your webhook for Super Forms on Zapier', 'super-forms' ),
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
function SUPER_Zapier() {
    return SUPER_Zapier::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_Zapier'] = SUPER_Zapier();