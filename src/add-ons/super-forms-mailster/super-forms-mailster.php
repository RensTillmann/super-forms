<?php
/**
 * Super Forms - Mailster
 *
 * @package   Super Forms - Mailster
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Mailster
 * Description: Register subscribers for Mailster with Super Forms
 * Version:     1.3.2
 * Plugin URI:  http://f4d.nl/super-forms
 * Author URI:  http://f4d.nl/super-forms
 * Author:      feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 4.9
 * Requires PHP:      5.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists('SUPER_Mailster') ) :


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
        public $version = '1.3.2';


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
            
            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
            
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
            }
            
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_sending_email_hook', array( $this, 'add_subscriber' ) );
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
         * Save Mailster subscriber
         *
         *  @since      1.0.0
        */
        public static function add_subscriber( $x ) {

            if (function_exists('mailster_subscribe')) {
                extract( shortcode_atts( array( 'data'=>array(), 'post'=>array(), 'settings'=>array()), $x ) );
                if( (isset($settings['mailster_enabled'])) && ($settings['mailster_enabled']=='true') ) {
                    $data = wp_unslash($post['data']);
                    $data = json_decode($data, true);
                    $save_subscriber = 'yes';
                    // @since 1.0.2 - check if we do not want to save subscriber conditionally
                    if( !empty($settings['mailster_conditionally_save']) ) {
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
                                $unserialize = unserialize($string);
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
                                    SUPER_Common::output_message( $error=true, $v[0] );
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
        public static function add_settings( $array, $x ) {
            $default = $x['default'];
            $settings = $x['settings'];
            $array['mailster'] = array(        
                'hidden' => 'settings',
                'name' => esc_html__( 'Mailster Settings', 'super-forms' ),
                'label' => esc_html__( 'Mailster Settings', 'super-forms' ),
                'fields' => array(
                    'mailster_enabled' => array(
                        'desc' => esc_html__( 'This will save a subscriber for Mailster', 'super-forms' ), 
                        'default' =>  '',
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Add Mailster subscriber', 'super-forms' ),
                        )
                    ),

                    // @since 1.0.2  - conditionally save mailster subscriber based on user input
                    'mailster_conditionally_save' => array(
                        'hidden_setting' => true,
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Conditionally save subscriber based on user data', 'super-forms' ),
                        ),
                        'parent' => 'mailster_enabled',
                        'filter_value' => 'true',
                    ),
                    'mailster_conditionally_save_check' => array(
                        'hidden_setting' => true,
                        'type' => 'conditional_check',
                        'name' => esc_html__( 'Only save subscriber when following condition is met', 'super-forms' ),
                        'label' => esc_html__( 'Your are allowed to enter field {tags} to do the check', 'super-forms' ),
                        'default' =>  '',
                        'placeholder' => "{fieldname},value",
                        'filter'=>true,
                        'parent' => 'mailster_conditionally_save',
                        'filter_value' => 'true',
                        'allow_empty'=>true,
                    ),

                    'mailster_email' => array(
                        'name' => esc_html__( 'Subscriber email address', 'super-forms' ), 
                        'desc' => esc_html__( 'This will save the entered email by the user as the subsriber email address', 'super-forms' ), 
                        'default' =>  '{email}',
                        'filter'=>true,
                        'parent' => 'mailster_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'mailster_fields' => array(
                        'name' => esc_html__( 'Save Mailster user data', 'super-forms' ), 
                        'label' => sprintf( esc_html__( 'Seperate Mailster field and field_name by pipes "|" (put each on a new line).%sExample: mailster_field_name|super_forms_field_name%sWith this method you can save custom Mailster user data', 'super-forms' ), '<br />', '<br />' ),
                        'desc' => esc_html__( 'Enter the  fields that need to be saved for a subscriber', 'super-forms' ), 
                        'default' =>  "lastname|last_name\nfirstname|first_name",
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'mailster_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'mailster_lists' => array(
                        'name' => esc_html__( 'Subscriber list ID(\'s) seperated by comma\'s', 'super-forms' ), 
                        'label' => esc_html__( 'You are allowed to use a {tag} if you want to allow the user to choose a list from your form', 'super-forms' ),
                        'desc' => esc_html__( 'Enter the list ID\'s or enter a {tag}', 'super-forms' ), 
                        'default' =>  '{lists}',
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
if( !function_exists('SUPER_Mailster') ){
    function SUPER_Mailster() {
        return SUPER_Mailster::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Mailster'] = SUPER_Mailster();
}