<?php
/**
 * Super Forms - MailPoet
 *
 * @package   Super Forms - MailPoet
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - MailPoet
 * Description: Register subscribers for MailPoet with Super Forms
 * Version:     1.0.0
 * Plugin URI:  http://f4d.nl/super-forms
 * Author URI:  http://f4d.nl/super-forms
 * Author:      feeling4design
 * Text Domain: super-forms
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 4.9
 * Requires PHP:      5.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists('SUPER_MailPoet') ) :


    /**
     * Main SUPER_MailPoet Class
     *
     * @class SUPER_MailPoet
     * @version	1.0.0
     */
    final class SUPER_MailPoet {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.0.0';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'mailpoet';
        public $add_on_name = 'MailPoet';


        /**
         * @var SUPER_MailPoet The single instance of the class
         *
         *	@since		1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_MailPoet Instance
         *
         * Ensures only one instance of SUPER_MailPoet is loaded or can be loaded.
         *
         * @static
         * @see SUPER_MailPoet()
         * @return SUPER_MailPoet - Main instance
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
         * SUPER_MailPoet Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_mailpoet_loaded');
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
            
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
            }
            
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_sending_email_hook', array( $this, 'add_subscriber' ) );
            }
            
        }


        /**
         * Save MailPoet subscriber
         *
         *  @since      1.0.0
        */
        public static function add_subscriber( $x ) {
            extract( shortcode_atts( array( 'data'=>array(), 'post'=>array(), 'settings'=>array()), $x ) );
            if(!class_exists(\MailPoet\API\API::class)) return;
            if((isset($settings['mailpoet_enabled'])) && ($settings['mailpoet_enabled']!=='true')) return;

            $data = wp_unslash($post['data']);
            $data = json_decode($data, true);
            $save_subscriber = 'yes';
            // @since 1.0.2 - check if we do not want to save subscriber conditionally
            if( !empty($settings['mailpoet_conditionally_save']) ) {
                $save_subscriber = 'no';
                if( !empty($settings['mailpoet_conditionally_save_check']) ) {
                    $values = explode(',', $settings['mailpoet_conditionally_save_check']);
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
                $email = SUPER_Common::email_tags( $settings['mailpoet_email'], $data, $settings );
                $fname = SUPER_Common::email_tags( $settings['mailpoet_fname'], $data, $settings );
                $lname = SUPER_Common::email_tags( $settings['mailpoet_lname'], $data, $settings );
                $sub = array(
                    'email' => $email,
                    'first_name' => $fname,
                    'last_name' => $lname
                );

                // map custom fields with the actual field ID
                $fields = SUPER_Common::email_tags( $settings['mailpoet_fields'], $data, $settings );
                $fields = explode( "\n", $fields );
                foreach( $fields as $k ) {
                    $field = explode( "|", $k );
                    $string = $field[1];
                    // check if string is serialized array
                    $unserialize = @unserialize($string);
                    if ($unserialize !== false) {
                        $sub[$field[0]] = $unserialize;
                    }else{
                        $sub[$field[0]] = $string;
                    }
                }
                $mailpoet_api = \MailPoet\API\API::MP('v1');
                // See if the user exists first.
                $subscriber = false;
                try {
                    $subscriber = $mailpoet_api->getSubscriber( $sub['email'] );
                } catch ( \Throwable $e ) {
                    if($e->getCode()!==4){
                        SUPER_Common::output_message( $error=true, $e->getMessage() );
                    }
                }
                // If the subscriber doesn't exist, add them.
                if(!$subscriber){
                    try {
                        $subscriber = $mailpoet_api->addSubscriber( $sub );
                    } catch (\Throwable $e) {
                        SUPER_Common::output_message( $error=true, $e->getMessage() );
                    }
                }
                // Try add the user to lists.
                if($subscriber){
                    $user_id = $subscriber['id'];
                    $lists = array();
                    if(empty($settings['mailpoet_lists'])){
                        return;
                    }
                    $lists_id = SUPER_Common::email_tags( $settings['mailpoet_lists'], $data, $settings );
                    $lists_id = explode(",", $lists_id);
                    if(count($lists_id)===0){
                        return;
                    }
                    foreach( $lists_id as $key => $list_id ) {
                        $lists[] = intval( $list_id );
                    }
                    // add users to the lists.
                    try {
                        $subscribe = $mailpoet_api->subscribeToLists( $user_id, $lists );
                    } catch ( \Throwable $e ) {
                        SUPER_Common::output_message( $error=true, $e->getMessage() );
                    }
                }
            }
        }
        public static function add_settings( $array, $x ) {
            $default = $x['default'];
            $settings = $x['settings'];

            global $wpdb;
            $table_name = $wpdb->prefix.'mailpoet_custom_fields';
            $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));
            $fields = array();
            if($wpdb->get_var($query)===$table_name) {
                $fields = $wpdb->get_results("SELECT * FROM $table_name WHERE 1=1");
            }
            $fieldsList = '';
            foreach($fields as $k => $v){
                $fieldsList .= '<strong>cf_' . $v->id . '</strong> - (' . $v->name . ')<br />';
            }
            if(!empty($fieldsList)){
                $fieldsList = esc_html__( 'List of available fields:' , 'super-forms' ) . '<br />' . $fieldsList;
            }else{
                $fieldsList = esc_html__( 'No fields found! Please make some custom fields via [MailPoet > Forms]' , 'super-forms' );
            }
            $array['mailpoet'] = array(        
                'hidden' => 'settings',
                'name' => esc_html__( 'MailPoet Settings', 'super-forms' ),
                'label' => esc_html__( 'MailPoet Settings', 'super-forms' ),
                'fields' => array(
                    'mailpoet_enabled' => array(
                        'desc' => esc_html__( 'This will save a subscriber for MailPoet', 'super-forms' ), 
                        'default' =>  '',
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Add MailPoet subscriber', 'super-forms' ),
                        )
                    ),
                    'mailpoet_conditionally_save' => array(
                        'hidden_setting' => true,
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Conditionally save subscriber based on user data', 'super-forms' ),
                        ),
                        'parent' => 'mailpoet_enabled',
                        'filter_value' => 'true',
                    ),
                    'mailpoet_conditionally_save_check' => array(
                        'hidden_setting' => true,
                        'type' => 'conditional_check',
                        'name' => esc_html__( 'Only save subscriber when following condition is met', 'super-forms' ),
                        'label' => esc_html__( 'Your are allowed to enter field {tags} to do the check', 'super-forms' ),
                        'default' =>  '',
                        'placeholder' => "{fieldname},value",
                        'filter'=>true,
                        'parent' => 'mailpoet_conditionally_save',
                        'filter_value' => 'true',
                        'allow_empty'=>true,
                    ),
                    'mailpoet_email' => array(
                        'name' => esc_html__( 'Subscriber email address', 'super-forms' ), 
                        'desc' => esc_html__( 'This will save the entered email by the user as the subsriber email address', 'super-forms' ), 
                        'default' =>  '{email}',
                        'filter'=>true,
                        'parent' => 'mailpoet_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'mailpoet_fname' => array(
                        'name' => esc_html__( 'First name (optional)', 'super-forms' ), 
                        'default' =>  '{first_name}',
                        'filter'=>true,
                        'parent' => 'mailpoet_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'mailpoet_lname' => array(
                        'name' => esc_html__( 'Last name (optional)', 'super-forms' ), 
                        'default' =>  '{last_name}',
                        'filter'=>true,
                        'parent' => 'mailpoet_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'mailpoet_fields' => array(
                        'name' => esc_html__( 'Map custom fields', 'super-forms' ), 
                        'label' => sprintf( esc_html__( 'Put each on a new line. Example format:%scf_1|{form_field_name}%scf_2|{form_field_name2}%s%s', 'super-forms' ), '<pre>', '<br />', '</pre>', $fieldsList ),
                        'default' =>  '',
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'mailpoet_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'mailpoet_lists' => array(
                        'name' => esc_html__( 'Subscriber list ID(\'s) seperated by comma\'s', 'super-forms' ), 
                        'label' => esc_html__( 'You are allowed to use {tags} if you want to allow the user to choose a list from dropdown or radio/checkbox in your form', 'super-forms' ),
                        'default' =>  '{lists}',
                        'filter'=>true,
                        'parent' => 'mailpoet_enabled',
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
 * Returns the main instance of SUPER_MailPoet to prevent the need to use globals.
 *
 * @return SUPER_MailPoet
 */
if( !function_exists('SUPER_MailPoet') ){
    function SUPER_MailPoet() {
        return SUPER_MailPoet::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_MailPoet'] = SUPER_MailPoet();
}