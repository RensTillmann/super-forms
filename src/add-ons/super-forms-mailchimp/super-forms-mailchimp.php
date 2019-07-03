<?php
/**
 * Super Forms - Mailchimp
 *
 * @package   Super Forms - Mailchimp
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2019 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Mailchimp
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Subscribes and unsubscribes users from a specific Mailchimp list
 * Version:     1.4.22
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Mailchimp')) :


    /**
     * Main SUPER_Mailchimp Class
     *
     * @class SUPER_Mailchimp
     * @version	1.0.0
     */
    final class SUPER_Mailchimp {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.4.22';

        
        /**
         * @var string
         *
         *  @since      1.1.0
        */
        public $add_on_slug = 'mailchimp';
        public $add_on_name = 'Mailchimp';


        /**
         * @var SUPER_Mailchimp The single instance of the class
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
         * Main SUPER_Mailchimp Instance
         *
         * Ensures only one instance of SUPER_Mailchimp is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Mailchimp()
         * @return SUPER_Mailchimp - Main instance
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
         * SUPER_Mailchimp Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_mailchimp_loaded');
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
            
            add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_mailchimp_element' ), 10, 2 );
            
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_mailchimp_settings' ), 10, 2 );
                add_filter( 'super_enqueue_styles', array( $this, 'add_stylesheet' ), 10, 1 );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );   
                add_action( 'init', array( $this, 'update_plugin' ) );
            }
            
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_sending_email_hook', array( $this, 'update_mailchimp_subscribers' ) );
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
         * Hook into elements and add Mailchimp element
         * This element specifies the Mailchimp List by it's given ID and retrieves it's Groups
         *
         *  @since      1.0.0
        */
        public static function add_stylesheet( $array ) {
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . '/assets/';
            $backend_path   = $assets_path . 'css/backend/';
            $array['super-mailchimp'] = array(
                'src'     => $backend_path . 'mailchimp.css',
                'deps'    => '',
                'version' => SUPER_Mailchimp()->version,
                'media'   => 'all',
                'screen'  => array( 
                    'super-forms_page_super_create_form'
                ),
                'method'  => 'enqueue',
            );
            return $array;
        }


        /**
         * Handle the Mailchimp element output
         *
         *  @since      1.0.0
        */
        public static function mailchimp( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
          
            // Fallback check for older super form versions
            if (method_exists('SUPER_Common','generate_array_default_element_settings')) {
                $defaults = SUPER_Common::generate_array_default_element_settings($shortcodes, 'form_elements', $tag);
                $atts = wp_parse_args( $atts, $defaults );
            }

            if( !isset( $atts['vip'] ) ) $atts['vip'] = '';
            if( !isset( $atts['display_interests'] ) ) $atts['display_interests'] = 'no';

            if( $atts['display_interests']=='no' ) {
                $atts['label'] = '';
                $atts['description'] = '';
                $atts['icon'] = '';
            }

            $tag = 'checkbox';
            $classes = ' display-' . $atts['display'];
            $result = SUPER_Shortcodes::opening_tag( $tag, $atts, $classes );

            $show_hidden_field = true;

            // Retrieve groups based on the given List ID:
            $global_settings = get_option( 'super_settings' );

            // Check if the API key has been set
            if( ( !isset( $global_settings['mailchimp_key'] ) ) || ( $global_settings['mailchimp_key']=='' ) ) {
                $show_hidden_field = false;
                $result .= '<strong style="color:red;">' . esc_html__( 'Please setup your API key in', 'super-forms' ) . ' <a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#mailchimp">Super Forms > ' . esc_html__( 'Settings', 'super-forms' ) . ' > Mailchimp</a></strong>';
            }else{
                if( ( !isset( $atts['list_id'] ) ) || ( $atts['list_id']=='' ) ) {
                    $show_hidden_field = false;
                    $result .= '<strong style="color:red;">' . esc_html__( 'Please edit the element and enter your List ID.', 'super-forms' ) . '</strong>';
                }else{
                    $list_id = sanitize_text_field( $atts['list_id'] );
                    $api_key = $global_settings['mailchimp_key'];
                    $datacenter = explode('-', $api_key);
                    if( !isset( $datacenter[1] ) ) {
                		$result .= '<strong style="color:red;">' . esc_html__( 'Your API key seems to be invalid', 'super-forms' ) . '</strong>';
                    }else{
                    	if( $atts['display_interests']=='yes' ) {
	                        $datacenter = $datacenter[1];
	                        $endpoint = 'https://' . $datacenter . '.api.mailchimp.com/3.0/';
	                        $request = 'lists/' . $list_id . '/interest-categories/';
	                        $url = $endpoint . $request;
	                        $ch = curl_init();
	                        curl_setopt( $ch, CURLOPT_URL, $url );
	                        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'content-type: application/json' ) );
	                        curl_setopt( $ch, CURLOPT_USERPWD, 'anystring:' . $api_key ); 
	                        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	                        curl_setopt( $ch, CURLOPT_ENCODING, '' );
	                        $output = curl_exec( $ch );
	                        $output = json_decode( $output );
	                        if( ( isset( $output->status ) ) && ( $output->status==401 ) ) {
								$result .= '<strong style="color:red;">' . esc_html($output->detail) . '</strong>';
	                        }else{
		                        if( !isset( $output->categories ) ) {
		                            $result .= '<strong style="color:red;">' . esc_html__( 'The List ID seems to be invalid, please make sure you entered to correct List ID.', 'super-forms' ) . '</strong>';
		                        }else{
		                            $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $global_settings );
		                            foreach( $output->categories as $k => $v ) {
		                                $request = $request . $v->id . '/interests/';
		                                $url = $endpoint.$request;
		                                $ch = curl_init();
		                                curl_setopt( $ch, CURLOPT_URL, $url );
		                                curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'content-type: application/json' ) );
		                                curl_setopt( $ch, CURLOPT_USERPWD, 'anystring:' . $api_key ); 
		                                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		                                curl_setopt( $ch, CURLOPT_ENCODING, '' );
		                                $output = curl_exec( $ch );
		                                $output = json_decode( $output );
		                                foreach( $output->interests as $ik => $iv ) {
		                                    $result .= '<label><input type="checkbox" value="' . esc_attr( $iv->id ) . '" />' . esc_html($iv->name) . '</label>';
		                                }
		                            }
		                            $result .= '<input class="super-shortcode-field" type="hidden"';
		                            $result .= ' name="mailchimp_interests" value=""';
		                            $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
		                            $result .= ' />';
		                            $result .= '</div>';
		                        }
	                    	}
	                    }
	                }
                }
            }
            $result .= SUPER_Shortcodes::loop_conditions( $atts );
            $result .= '</div>';

            // Add the hidden fields
            if( $atts['send_confirmation']=='yes' ) {
                $atts['label'] = '';
                $atts['description'] = '';
                $atts['icon'] = '';
                $classes = ' hidden';
                $result .= SUPER_Shortcodes::opening_tag( 'hidden', $atts, $classes );
                $result .= '<input class="super-shortcode-field" type="hidden" value="1" name="mailchimp_send_confirmation" data-exclude="2" />';
                $result .= '</div>';
            }
            if( $show_hidden_field==true ) {
                $atts['label'] = '';
                $atts['description'] = '';
                $atts['icon'] = '';
                $classes = ' hidden';
                $result .= SUPER_Shortcodes::opening_tag( 'hidden', $atts, $classes );
                $result .= '<input class="super-shortcode-field" type="hidden" value="' . $list_id . '" name="mailchimp_list_id" data-exclude="2"';
                if( !empty($atts['vip'] ) ) $result .= ' data-vip="' . $atts['vip'] . '"';
                $result .= ' />';
                $result .= '</div>';

                // @since 1.2.0 - add the merge fields       
                if( (isset($atts['custom_fields'])) && ($atts['custom_fields']!='') ) $result .= '<textarea class="super-shortcode-field super-hidden" name="mailchimp_custom_fields_' . $list_id . '" data-exclude="2">' . $atts['custom_fields'] . '</textarea>';
            }

            return $result;
        }


        /**
         * Hook into elements and add Mailchimp element
         * This element specifies the Mailchimp List by it's given ID and retrieves it's Groups
         *
         *  @since      1.0.0
        */
        public static function add_mailchimp_element( $array, $attributes ) {

            // Include the predefined arrays
            require(SUPER_PLUGIN_DIR.'/includes/shortcodes/predefined-arrays.php' );

            $array['form_elements']['shortcodes']['mailchimp'] = array(
                'callback' => 'SUPER_Mailchimp::mailchimp',
                'name' => esc_html__( 'Mailchimp', 'super-forms' ),
                'icon' => 'mailchimp;fab',
                'atts' => array(
                    'general' => array(
                        'name' => esc_html__( 'General', 'super-forms' ),
                        'fields' => array(
                            'list_id' => array(
                                'name' => esc_html__( 'Mailchimp List ID', 'super-forms' ), 
                                'label' => esc_html__( 'Your List ID for example: 9e67587f52', 'super-forms' ),
                                'default'=> (!isset($attributes['list_id']) ? '' : $attributes['list_id']),
                                'required'=>true, 
                            ),
                            'custom_fields' => array(
                                'name' => esc_html__( 'Custom fields to save (*|MERGE|* tags)', 'super-forms' ),
                                'label' => sprintf( esc_html__( 'Seperate Mailchimp field and field_name by pipes "|" (put each on a new line).%1$sExample: PHONE|phonenumber%1$sWith this method you can save custom Mailchimp user data', 'super-forms' ), '<br />' ),
                                'desc' => esc_html__( 'Allows you to save your custom fields within Mailchimp', 'super-forms' ),
                                'type' => 'textarea',
                                'default'=> (!isset($attributes['custom_fields']) ? '' : $attributes['custom_fields']),
                            ),
                            'display_interests' => array(
                                'name' => esc_html__( 'Display interests', 'super-forms' ),
                                'label' => esc_html__( 'Allow users to select one or more interests (retrieved by given List ID)', 'super-forms' ),
                                'type' => 'select',
                                'default'=> (!isset($attributes['interests']) ? 'no' : $attributes['interests']),
                                'values' => array(
                                    'no' => esc_html__( 'No', 'super-forms' ), 
                                    'yes' => esc_html__( 'Yes', 'super-forms' ), 
                                ),
                            ),
                            'vip' => array(
                                'name' => esc_html__( 'VIP status for subscriber', 'super-forms' ), 
                                'label' => esc_html__( 'Must be either "true" or "false". You can use {tags} if needed. (or leave blank for none-VIP)', 'super-forms' ),
                                'default'=> (!isset($attributes['vip']) ? '' : $attributes['vip']),
                            ),
                            'send_confirmation' => array(
                                'name' => esc_html__( 'Send the Mailchimp confirmation email', 'super-forms' ),
                                'label' => esc_html__( 'Users will receive a confirmation email before they are subscribed', 'super-forms' ),
                                'type' => 'select',
                                'default'=> (!isset($attributes['send_confirmation']) ? 'no' : $attributes['send_confirmation']),
                                'values' => array(
                                    'no' => esc_html__( 'No', 'super-forms' ), 
                                    'yes' => esc_html__( 'Yes', 'super-forms' ), 
                                ),
                            ),                            
                            'email' => SUPER_Shortcodes::email($attributes, $default='Interests'),
                            'label' => $label,
                            'description'=> $description,
                            'tooltip' => $tooltip,
                            'validation' => $validation_empty,
                            'error' => $error,  
                        )
                    ),
                    'advanced' => array(
                        'name' => esc_html__( 'Advanced', 'super-forms' ),
                        'fields' => array(
                            'maxlength' => $maxlength,
                            'minlength' => $minlength,
                            'display' => array(
                                'name' => esc_html__( 'Vertical / Horizontal display', 'super-forms' ), 
                                'type' => 'select',
                                'default'=> (!isset($attributes['display']) ? 'vertical' : $attributes['display']),
                                'values' => array(
                                    'vertical' => esc_html__( 'Vertical display ( | )', 'super-forms' ), 
                                    'horizontal' => esc_html__( 'Horizontal display ( -- )', 'super-forms' ), 
                                ),
                            ),
                            'grouped' => $grouped,                    
                            'width' => $width,
                            'exclude' => $exclude, 
                            'error_position' => $error_position_left_only,
                            
                        ),
                    ),
                    'icon' => array(
                        'name' => esc_html__( 'Icon', 'super-forms' ),
                        'fields' => array(
                            'icon_position' => $icon_position,
                            'icon_align' => $icon_align,
                            'icon' => SUPER_Shortcodes::icon($attributes,'check-square-o'),
                        ),
                    ),
                    'conditional_logic' => $conditional_logic_array
                ),
            );
            return $array;
        }


        /**
         * Hook into settings and add Mailchimp settings
         *
         *  @since      1.0.0
        */
        public static function add_mailchimp_settings( $array, $settings ) {
            $array['mailchimp'] = array(        
                'hidden' => true,
                'name' => esc_html__( 'Mailchimp', 'super-forms' ),
                'label' => esc_html__( 'Mailchimp Settings', 'super-forms' ),
                'fields' => array(
                    'mailchimp_key' => array(
                        'name' => esc_html__( 'API key', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'mailchimp_key', $settings['settings'], '' ),
                    )
                )
            );
            return $array;
        }


        /**
         * Hook into before sending email and check for subscribe or unsubscribe action
         * After that do a curl request to mailchimp to update the list by the given List ID
         *
         *  @since      1.0.0
        */
        public static function update_mailchimp_subscribers( $atts ) {
            
            $data = $atts['post']['data'];
            if( isset( $data['mailchimp_list_id'] ) ) {

                // First check if 'email' field exists, because this is required to make the request
                if( (empty($data['email'])) || (empty($data['email']['value'])) ) {
                    SUPER_Common::output_error(
                        $error = true,
                        $msg = sprintf( 
                            esc_html__( '%1$sError:%2$s Couldn\'t subscribe the user to Mailchimp because no %1$sE-mail Address%2$s field was found in your form. Make sure to add this field and that it\'s named %1$semail%2$s', 'super_forms' ), 
                            '<strong>', 
                            '</strong>'
                        )
                    );
                }

                // Retreive the list ID
                $list_id = sanitize_text_field( $data['mailchimp_list_id']['value'] );

                // Setup CURL
                $global_settings = get_option( 'super_settings' );
                $api_key = $global_settings['mailchimp_key'];
                $datacenter = explode('-', $api_key);
                $datacenter = $datacenter[1];
                $endpoint = 'https://' . $datacenter . '.api.mailchimp.com/3.0/';
                $request = 'lists/' . $list_id . '/interest-categories/';

                $email = sanitize_email( $data['email']['value'] );
                $email = strtolower($email);
                $email_md5 = md5($email);
                $request = 'lists/' . $list_id . '/members/';
                $url = $endpoint.$request;
                $patch_url = $url . $email_md5;

                // Setup default user data
                $user_data = array();

                // Set user info
                $user_data['email_address'] = $email;
                if( isset( $data['first_name'] ) ) {
                    $user_data['merge_fields']['FNAME'] = $data['first_name']['value'];
                }
                if( isset( $data['last_name'] ) ) {
                    $user_data['merge_fields']['LNAME'] = $data['last_name']['value'];
                }

                // Retreive the VIP status if any
                $user_data['vip'] = 'false';
                if(!empty($data['mailchimp_list_id']['vip'])){
                    $user_data['vip'] = $data['mailchimp_list_id']['vip'];
                }
                // Convert to Boolean
                $user_data['vip'] = $user_data['vip'] === 'true'? true: false;

                // @since 1.2.0 - option to save custom fields
                if( ( isset( $data['mailchimp_custom_fields_' . $list_id] ) ) && ($data['mailchimp_custom_fields_' . $list_id]!='') ) {
                    $merge_fields = array();
                    $fields = explode( "\n", $data['mailchimp_custom_fields_' . $list_id]['value'] );
                    foreach( $fields as $k ) {
                        $field = explode( "|", $k );
                        // first check if a field with the name exists
                        if( isset( $data[$field[1]]['value'] ) ) {
                            $merge_fields[$field[0]] = $data[$field[1]]['value'];
                        }else{
                            // if no field exists, just save it as a string
                            $string = SUPER_Common::email_tags( $field[1], $data, $global_settings );
                            // check if string is serialized array
                            $unserialize = unserialize($string);
                            if ($unserialize !== false) {
                                $merge_fields[$field[0]] = $unserialize;
                            }else{
                                $merge_fields[$field[0]] = $string;
                            }
                        }
                    }
                    foreach( $merge_fields as $k => $v ) {
                        $user_data['merge_fields'][$k] = $v;
                    }
                }

                if( $data['mailchimp_send_confirmation']['value']==1 ) {
                    $user_data['status'] = 'pending';
                }else{
                    $user_data['status'] = 'subscribed';
                }

                // Find out if we have some selected interests
                if( isset( $data['mailchimp_interests'] ) ) {
                    $interests = explode( ',', $data['mailchimp_interests']['value'] );
                    foreach($interests as $k => $v ){
                        $user_data['interests'][$v] = true;
                    }
                }
                $data_string = json_encode($user_data); 
                $response = wp_remote_post( 
                    $url, 
                    array(
                        'headers' => array(
                            'Content-Type' => 'application/json; charset=utf-8',
                            'Authorization' => 'Bearer ' . $api_key
                        ),
                        'body' => $data_string,
                        'method' => 'POST',
                        'data_format' => 'body'
                    )
                );
                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    SUPER_Common::output_error(
                        $error = true,
                        $msg = $error_message
                    );
                }

                $obj = json_decode($response['body'], true);
                $status = $obj['status'];
                $link_to_error_code = 'https://developer.mailchimp.com/documentation/mailchimp/guides/error-glossary/#';

                // Check if response code is not 200, then we display a error message to the user
                if( $status!=200 && $status!=400 && $status!=='subscribed' ) {
                    SUPER_Common::output_error(
                        $error = true,
                        $msg = '<strong>' . $obj['title'] . ':</strong> ' . $obj['detail'] . ' (<a href="' . $link_to_error_code . '#' . $status . '" target="_blank">' . esc_html__( 'View error details', 'super-forms' ) . '</a>)'
                    );
                }

                // User already exists for this list, lets update the user with a PUT request
                if( $status==400 ) {

                    // Only delete interests if this for is actually giving the user the option to select interests
                    if( isset( $data['mailchimp_interests'] ) ) {
                        
                        // First get all interests of this member
                        $response = wp_remote_post( 
                            $patch_url, 
                            array(
                                'headers' => array(
                                    'Content-Type' => 'application/json; charset=utf-8',
                                    'Authorization' => 'Bearer ' . $api_key
                                ),
                                'body' => $data_string,
                                'method' => 'GET',
                                'data_format' => 'body'
                            )
                        );
                        if ( is_wp_error( $response ) ) {
                            $error_message = $response->get_error_message();
                            SUPER_Common::output_error(
                                $error = true,
                                $msg = $error_message
                            );
                        }  
                        $obj = json_decode($response['body'], true);
                        // Merge new interests with existing ones, set old ones to false if need be
                        foreach( $obj['interests'] as $k => $v ) {
                            if(!in_array($user_data['interests'])){
                                $obj['interests'][$k] = false;
                            }
                        }
                        $user_data['interests'] = array_merge( $obj['interests'], $user_data['interests'] );
                        $data_string = json_encode($user_data); 
                    }

                    // Now update the user with it's new interests
                    $response = wp_remote_post( 
                        $patch_url, 
                        array(
                            'headers' => array(
                                'Content-Type' => 'application/json; charset=utf-8',
                                'Authorization' => 'Bearer ' . $api_key
                            ),
                            'body' => $data_string,
                            'method' => 'PATCH',
                            'data_format' => 'body'
                        )
                    );
                    if ( is_wp_error( $response ) ) {
                        $error_message = $response->get_error_message();
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $error_message
                        );
                    }
                    $obj = json_decode($response['body'], true);
                }else{
                    if( $status!=='subscribed' ) {
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = '<strong>' . $obj['title'] . ':</strong> ' . $obj['detail'] . ' (<a href="' . $link_to_error_code . '#' . $status . '" target="_blank">' . esc_html__( 'View error details', 'super-forms' ) . '</a>)'
                        );
                    }
                }
            }
        }
    }
        
endif;


/**
 * Returns the main instance of SUPER_Mailchimp to prevent the need to use globals.
 *
 * @return SUPER_Mailchimp
 */
if(!function_exists('SUPER_Mailchimp')){
    function SUPER_Mailchimp() {
        return SUPER_Mailchimp::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['super_mailchimp'] = SUPER_Mailchimp();
}
