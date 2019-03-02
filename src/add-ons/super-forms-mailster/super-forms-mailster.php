<?php
/**
 * Super Forms - Mailster
 *
 * @package   Super Forms - Mailster
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Mailster
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Register subscribers for Mailster with Super Forms
 * Version:     1.0.2
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Mailster')) :


    /**
     * Main SUPER_Mailster Class
     *
     * @class SUPER_Mailster
     * @version	1.0.0
     */
    final class SUPER_Mailster {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.0.2';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'mailster';
        public $add_on_name = 'Mailster';


        /**
         * @var SUPER_Mailster The single instance of the class
         *
         *	@since		1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Mailster Instance
         *
         * Ensures only one instance of SUPER_Mailster is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Mailster()
         * @return SUPER_Mailster - Main instance
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
         * SUPER_Mailster Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_mailster_loaded');
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
         *	@since		1.0.0
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
                add_action( 'super_before_sending_email_hook', array( $this, 'add_subscriber' ) );

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
                SUPER_Forms::add_on_deactivate(SUPER_Mailster()->add_on_slug);
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
                if( (isset($settings['mailster_enabled'])) && ($settings['mailster_enabled']=='true') ) {
                    return SUPER_Forms::add_on_activation_message($activation_msg, $this->add_on_slug, $this->add_on_name);
                }
            }
            return $activation_msg;
        }


        /**
         * Save Mailster subscriber
         *
         *  @since      1.0.0
        */
        public static function add_subscriber( $atts ) {

            if (function_exists('mailster_subscribe')) {
                $settings = $atts['settings'];
                if( (isset($settings['mailster_enabled'])) && ($settings['mailster_enabled']=='true') ) {
                    $data = $atts['post']['data'];
                    $save_subscriber = 'yes';

                    // @since 1.0.2 - check if we do not want to save subscriber conditionally
                    if( !empty($settings['mailster_conditionally_save']) ) {
                        $settings = $atts['settings'];
                        $save_subscriber = 'no';
                        if( !empty($settings['mailster_conditionally_save_check']) ) {
                            $values = explode(',', $settings['mailster_conditionally_save_check']);
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
                                    $save_subscriber = 'yes';
                                }
                                // Check if values do not match eachother
                                if( ($values[1]=='!=') && ($values[0]!=$values[2]) ) {
                                    // we do not want to save the contact entry
                                    $save_subscriber = 'yes';
                                }

                            }
                        }
                    }

                    // Only save when enabled and in case conditional saving matched
                    if( $save_subscriber=='yes' ) {
                        $email = SUPER_Common::email_tags( $settings['mailster_email'], $data, $settings );
                        $userdata = array();
                        $fields = explode( "\n", $settings['mailster_fields'] );
                        foreach( $fields as $k ) {
                            $field = explode( "|", $k );
                            // first check if a field with the name exists
                            if( isset( $data[$field[1]]['value'] ) ) {
                                $userdata[$field[0]] = $data[$field[1]]['value'];
                            }else{
                                // if no field exists, just save it as a string
                                $string = SUPER_Common::email_tags( $field[1], $data, $settings );
                                // check if string is serialized array
                                $unserialize = @unserialize($string);
                                if ($unserialize !== false) {
                                    $userdata[$field[0]] = $unserialize;
                                }else{
                                    $userdata[$field[0]] = $string;
                                }
                            }
                        }
                        $lists = SUPER_Common::email_tags( $settings['mailster_lists'], $data, $settings );
                        $lists = explode(",", $lists);
                        $result = mailster_subscribe( $email, $userdata, $lists);
                        if( !$result ) {
                            if( isset($result->errors) ) {
                                foreach( $result->errors as $k => $v ) {
                                    SUPER_Common::output_error( $error=true, $v[0] );
                                }
                            }
                        }
                    }
                }
            }
        }


        /**
         * Hook into settings and add Mailster settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
            $array['mailster'] = array(        
                'hidden' => 'settings',
                'name' => __( 'Mailster Settings', 'super-forms' ),
                'label' => __( 'Mailster Settings', 'super-forms' ),
                'fields' => array(
                    'mailster_enabled' => array(
                        'desc' => __( 'This will save a subscriber for Mailster', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'mailster_enabled', $settings['settings'], '' ),
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Add Mailster subscriber', 'super-forms' ),
                        )
                    ),

                    // @since 1.0.2  - conditionally save mailster subscriber based on user input
                    'mailster_conditionally_save' => array(
                        'hidden_setting' => true,
                        'default' => SUPER_Settings::get_value( 0, 'mailster_conditionally_save', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Conditionally save subscriber based on user data', 'super-forms' ),
                        ),
                        'parent' => 'mailster_enabled',
                        'filter_value' => 'true',
                    ),
                    'mailster_conditionally_save_check' => array(
                        'hidden_setting' => true,
                        'type' => 'conditional_check',
                        'name' => __( 'Only save subscriber when following condition is met', 'super-forms' ),
                        'label' => __( 'Your are allowed to enter field {tags} to do the check', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'mailster_conditionally_save_check', $settings['settings'], '' ),
                        'placeholder' => "{fieldname},value",
                        'filter'=>true,
                        'parent' => 'mailster_conditionally_save',
                        'filter_value' => 'true',
                        'allow_empty'=>true,
                    ),

                    'mailster_email' => array(
                        'name' => __( 'Subscriber email address', 'super-forms' ), 
                        'desc' => __( 'This will save the entered email by the user as the subsriber email address', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'mailster_email', $settings['settings'], '{email}' ),
                        'filter'=>true,
                        'parent' => 'mailster_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'mailster_fields' => array(
                        'name' => __( 'Save Mailster user data', 'super-forms' ), 
                        'label' => __( 'Seperate Mailster field and field_name by pipes "|" (put each on a new line).<br />Example: mailster_field_name|super_forms_field_name<br />With this method you can save custom Mailster user data', 'super-forms' ),
                        'desc' => __( 'Enter the  fields that need to be saved for a subscriber', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'mailster_fields', $settings['settings'], "lastname|last_name\nfirstname|first_name" ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'mailster_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'mailster_lists' => array(
                        'name' => __( 'Subscriber list ID(\'s) seperated by comma\'s', 'super-forms' ), 
                        'label' => __( 'You are allowed to use a {tag} if you want to allow the user to choose a list from your form', 'super-forms' ),
                        'desc' => __( 'Enter the list ID\'s or enter a {tag}', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'mailster_lists', $settings['settings'], '{lists}' ),
                        'filter'=>true,
                        'parent' => 'mailster_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                )
            );
            return $array;
        }
    }
        
endif;


/**
 * Returns the main instance of SUPER_Mailster to prevent the need to use globals.
 *
 * @return SUPER_Mailster
 */
function SUPER_Mailster() {
    return SUPER_Mailster::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_Mailster'] = SUPER_Mailster();