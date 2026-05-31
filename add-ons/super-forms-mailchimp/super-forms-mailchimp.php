<?php
/**
 * Super Forms - Mailchimp
 *
 * @package   Super Forms - Mailchimp
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Mailchimp
 * Description: Subscribes and unsubscribes users from a specific Mailchimp list
 * Version:     1.7.3
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

if( !class_exists('SUPER_Mailchimp') ) :


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
        public $version = '1.7.3';

        
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
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_mailchimp_settings' ), 10, 2 );
                add_filter( 'super_enqueue_styles', array( $this, 'add_stylesheet' ), 10, 1 );
            }
            
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_sending_email_hook', array( $this, 'update_mailchimp_subscribers' ) );
            }

            // @since 1.5.4
            add_filter( 'super_before_sending_email_data_filter', array( $this, 'remove_mailchimp_data' ), 10, 2 );

            // Load scripts before Ajax request
            add_action( 'super_after_enqueue_element_scripts_action', array( $this, 'load_scripts' ) );

        }


        /**
         * Enqueue scripts before ajax call is made
         *
         *  @since      1.0.0
        */
        public static function load_scripts($atts) {
            if($atts['ajax']) {
                wp_enqueue_style( 'super-mailchimp', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/mailchimp.css', array(), SUPER_Mailchimp()->version );
            }
        }
        

        // @since 1.5.4 - Make sure to remove the mailchimp data such as list ID and interests ID's
        public function remove_mailchimp_data($data, $atts){
            unset($data['mailchimp_interests']);
            unset($data['mailchimp_send_confirmation']);
            unset($data['mailchimp_subscriber_status']);
            unset($data['mailchimp_list_id']);
            foreach($data as $k => $v){
                if(substr($k, 0, 24)==='mailchimp_custom_fields_') {
                    unset($data[$k]);
                }
            }
            return $data;
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
         * Hook into elements and add Mailchimp element
         * This element specifies the Mailchimp List by it's given ID and retrieves it's Groups
         *
         *  @since      1.0.0
        */
        public static function add_stylesheet( $array ) {
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . '/assets/';
            $array['super-mailchimp'] = array(
                'src'     => $assets_path . 'css/backend/mailchimp.css',
                'deps'    => '',
                'version' => SUPER_Mailchimp()->version,
                'media'   => 'all',
                'screen'  => array( 
                    'super-forms_page_super_create_form'
                ),
                'method'  => 'enqueue',
            );
            $array['super-mailchimp'] = array(
                'src'     => $assets_path . 'css/frontend/mailchimp.css',
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
         * Handle Mailchimp API errors
        */
        public static function handle_api_response($response){
            // Requests to /tags/ return an empty payload (204 - No Content)
            if(!empty($response['body'])){
                // Check for WP errors
                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $error_message
                    );
                }
                // Check if we have any errors:
                $obj = json_decode($response['body'], true);
                if( $obj['status'] == 400 ) {
                    $detail = $obj['detail'];
                    $errors = $obj['errors'];
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = '<strong>' . esc_html($detail) . ':</strong> ' . esc_html(json_encode($errors)));
                }else{
                    // Otherwise display any other error response
                    if( $obj['status']!=200 && $obj['status']!=400 && $obj['status']!=='subscribed' && $obj['status']!=='unsubscribed' && $obj['status']!=='pending' ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = '<strong>' . esc_html__( 'Error', 'super-forms' ) . ':</strong> ' . json_encode($obj)
                        );
                    }
                }
            }
        }


        /**
         * Handle the Mailchimp element output
         *
         *  @since      1.0.0
        */
        public static function mailchimp($x) {
            extract( shortcode_atts( array( 'tag'=>'', 'atts'=>array(), 'inner'=>array(), 'shortcodes'=>null), $x ) );

            wp_enqueue_style( 'super-mailchimp', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/mailchimp.css', array(), SUPER_Mailchimp()->version );

            // Fallback check for older super form versions
            if (method_exists('SUPER_Common','generate_array_default_element_settings')) {
                $defaults = SUPER_Common::generate_array_default_element_settings($shortcodes, 'form_elements', $tag);
                $atts = wp_parse_args( $atts, $defaults );
            }

            if( !isset( $atts['display_interests'] ) ) $atts['display_interests'] = 'no';
            if( !isset( $atts['subscriber_tags'] ) ) $atts['subscriber_tags'] = '';
            if( !isset( $atts['vip'] ) ) $atts['vip'] = '';

            if( $atts['display_interests']=='no' ) {
                $atts['label'] = '';
                $atts['description'] = '';
                $atts['icon'] = '';
            }

            $conditions = SUPER_Shortcodes::loop_conditions( $atts, $tag );
            $result = '<div class="super-grid super-shortcode">';
            $result .= '<div class="super-shortcode super_one_full super-column column-number-1 first-column"' . ( $conditions!='' ? ' data-conditional-action="show" data-conditional-trigger="all"' : '' ) . '>';
            $tag = 'checkbox';
            $classes = ' super-mailchimp';
            $classes .= ' display-' . $atts['display'];
            $result .= SUPER_Shortcodes::opening_tag( $tag, $atts, $classes );

            $show_hidden_field = true;

            // Retrieve groups based on the given List ID:
            $global_settings = get_option( 'super_settings' );

            // Check if the API key has been set
            if( ( !isset( $global_settings['mailchimp_key'] ) ) || ( $global_settings['mailchimp_key']=='' ) ) {
                $show_hidden_field = false;
                $result .= '<strong style="color:red;">' . esc_html__( 'Please setup your API key in', 'super-forms' ) . ' <a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#mailchimp').'">Super Forms > ' . esc_html__( 'Settings', 'super-forms' ) . ' > Mailchimp</a></strong>';
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
		                                $url = $request . $v->id . '/interests/?count=1000'; // make sure to set limit to 1000 (maximum) otherwise would default to only 10 (by default)
		                                $url = $endpoint.$url;
		                                $ch = curl_init();
		                                curl_setopt( $ch, CURLOPT_URL, $url );
		                                curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'content-type: application/json' ) );
		                                curl_setopt( $ch, CURLOPT_USERPWD, 'anystring:' . $api_key ); 
		                                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		                                curl_setopt( $ch, CURLOPT_ENCODING, '' );
		                                $output = curl_exec( $ch );
                                        $output = json_decode( $output );
                                        $result .= '<span class="super-group-title">' . esc_html($v->title) . '</span>';
                                        $result .= '<div class="super-items-list">';
		                                foreach( $output->interests as $ik => $iv ) {
                                            $result .= '<label class="super-item">';
                                            $result .= '<span class="super-before"><span class="super-after"></span></span>';
                                            $result .= '<input type="checkbox" value="' . esc_attr( $iv->id ) . '" />';
                                            $result .= '<div>' . esc_html($iv->name) . '</div>';
                                            $result .= '</label>';
		                                }
                                        $result .= '</div>';
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


            $result .= $conditions;
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
            }else{
                $atts['label'] = '';
                $atts['description'] = '';
                $atts['icon'] = '';
                $classes = ' hidden';
                $result .= SUPER_Shortcodes::opening_tag( 'hidden', $atts, $classes );
                if( empty($atts['subscriber_status'] ) ) $atts['subscriber_status'] = 'subscribed';
                $result .= '<input class="super-shortcode-field" type="hidden" value="'.esc_attr($atts['subscriber_status']).'" name="mailchimp_subscriber_status" data-exclude="2" />';
                $result .= '</div>';
            }
            if( $show_hidden_field==true ) {
                $atts['label'] = '';
                $atts['description'] = '';
                $atts['icon'] = '';
                $classes = ' hidden';
                $result .= SUPER_Shortcodes::opening_tag( 'hidden', $atts, $classes );
                $result .= '<input class="super-shortcode-field" type="hidden" value="' . $list_id . '" name="mailchimp_list_id" data-exclude="2"';
                if( !empty($atts['subscriber_tags'] ) ) $result .= ' data-subscriber-tags="' . $atts['subscriber_tags'] . '"';
                if( !empty($atts['vip'] ) ) $result .= ' data-vip="' . $atts['vip'] . '"';
                $result .= ' />';
                $result .= '</div>';

                // @since 1.2.0 - add the merge fields
                if( (isset($atts['custom_fields'])) && ($atts['custom_fields']!='') ) $result .= '<textarea class="super-shortcode-field super-hidden" name="mailchimp_custom_fields_' . $list_id . '" data-exclude="2">' . $atts['custom_fields'] . '</textarea>';
            }

            $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag );
            $result .= '</div>';
            $result .= '</div>';

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
                                'name' => esc_html__( 'Mailchimp Audiance ID', 'super-forms' ), 
                                'label' => esc_html__( 'Your Audiance ID for example: f25b7204f4', 'super-forms' ),
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
                                'name' => esc_html__( 'Display interests/groups', 'super-forms' ),
                                'label' => esc_html__( 'Allow users to select one or more interests/groups (retrieved by given List ID)', 'super-forms' ),
                                'type' => 'select',
                                'default'=> (!isset($attributes['display_interests']) ? 'no' : $attributes['display_interests']),
                                'values' => array(
                                    'no' => esc_html__( 'No', 'super-forms' ), 
                                    'yes' => esc_html__( 'Yes', 'super-forms' ), 
                                )
                            ),
                            'subscriber_tags' => array(
                                'name' => esc_html__( 'Subscribe with the following tags', 'super-forms' ),
                                'label' => esc_html__( 'Enter the tag names seperated by comma e.g: developers,sales,press', 'super-forms' ),
                                'default'=> (!isset($attributes['subscriber_tags']) ? '' : $attributes['subscriber_tags'])
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
                                'filter' => true,
                            ),                            
                            'subscriber_status' => array(
                                'name' => esc_html__( 'Subscriber status after submitting the form', 'super-forms' ),
                                'label' => esc_html__( 'Normally you would want to subscribe a user, but it\'s also possible to unsubscribe a user if they are already subscribed if they are already subscribed.', 'super-forms' ),
                                'type' => 'select',
                                'default'=> (!isset($attributes['subscriber_status']) ? 'subscribed' : $attributes['subscriber_status']),
                                'values' => array(
                                    'subscribed' => esc_html__( 'Subscribed (default)', 'super-forms' ), 
                                    'unsubscribed' => esc_html__( 'Unsubscribed', 'super-forms' )
                                ),
                                'filter' => true,
                                'parent' => 'send_confirmation',
                                'filter_value' => 'no'
                            ),                            
                            'email' => SUPER_Shortcodes::email($attributes, $default='Interests'),
                            'label' => $label,
                            'description'=> $description,
                            'tooltip' => $tooltip,
                            'validation' => $validation_empty,
                            'error' => $error,  
	                        'emptyError' => (isset($emptyError) ? $emptyError : ''),
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
        public static function add_mailchimp_settings( $array, $x ) {
            $default = $x['default'];
            $settings = $x['settings'];
            $array['mailchimp'] = array(        
                'hidden' => true,
                'name' => esc_html__( 'Mailchimp', 'super-forms' ),
                'label' => esc_html__( 'Mailchimp Settings', 'super-forms' ),
                'fields' => array(
                    'mailchimp_key' => array(
                        'name' => esc_html__( 'API key', 'super-forms' ),
                        'default' =>  '',
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
            extract($atts); // data, post, settings
            if(!isset($atts['post']['data'])) return false;
            $data = wp_unslash($atts['post']['data']);
            $data = json_decode($data, true);
            if( isset( $data['mailchimp_list_id'] ) ) {
                // First check if 'email' field exists, because this is required to make the request
                if( (empty($data['email'])) || (empty($data['email']['value'])) ) {
                    SUPER_Common::output_message(
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
                $get_url = $url . $email_md5;

                // First find out if this member already exists
                $response = wp_remote_post( 
                    $patch_url, 
                    array(
                        'headers' => array(
                            'Content-Type' => 'application/json; charset=utf-8',
                            'Authorization' => 'Bearer ' . $api_key
                        ),
                        'body' => null,
                        'method' => 'GET',
                        'data_format' => 'body'
                    )
                );
                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $error_message
                    );
                }  

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
                        $user_data['merge_fields'][$k] = wp_unslash($v);
                    }
                }

                if( (!empty($data['mailchimp_send_confirmation']['value'])) && ($data['mailchimp_send_confirmation']['value']==1 )) {
                    $user_data['status'] = 'pending'; // When user needs to confirm their E-mail address, we want to set status to pending
                }else{
                    // Use the status defined on the field
                    if(empty($data['mailchimp_subscriber_status']['value'])) {
                        $data['mailchimp_subscriber_status'] = 'subscribed';
                    }
                    // Can be `subscribed` or `unsubscribed`
                    $user_data['status'] = $data['mailchimp_subscriber_status']['value'];
                }

                // Find out if we have some selected interests
                if( isset( $data['mailchimp_interests'] ) ) {
                    $interests = explode( ',', $data['mailchimp_interests']['value'] );
                    foreach($interests as $k => $v ){
                        $user_data['interests'][$v] = true;
                    }
                }

                $data_string = json_encode($user_data);

                $obj = json_decode( $response['body'], true );
                if( $obj['status']=='pending' || $obj['status']=='subscribed' || $obj['status']=='unsubscribed' ) {
                    // The user exists, let's PATCH instead of POST
                    // Only delete interests if this for is actually giving the user the option to select interests
                    if( isset( $data['mailchimp_interests'] ) ) {
                        // Merge new interests with existing ones, set old ones to false if need be
                        foreach( $obj['interests'] as $k => $v ) {
                            if(!isset($user_data['interest'][$k])){
                                $obj['interests'][$k] = false;
                                if(isset($user_data['interests'][$k])) $obj['interests'][$k] = true;
                            }
                        }
                        $user_data['interests'] = $obj['interests'];
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
                    // Handle response
                    self::handle_api_response($response);
                }else{
                    // The user does not exist, let's create a new one
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
                    // Handle response
                    self::handle_api_response($response);
                }
                
                // Set subscription tags if any
                if(!empty($data['mailchimp_list_id']['subscriber_tags'])){
                    $tags = $data['mailchimp_list_id']['subscriber_tags'];
                    $tags = explode(',', $tags);
                    foreach($tags as $k => $v){
                        $tags[$k] = array(
                            'name' => trim($v),
                            'status' => 'active'
                        );
                    }
                    $tags = array('tags' => $tags);
                    $tags = json_encode($tags);
                    $response = wp_remote_post( 
                        $patch_url . '/tags', 
                        array(
                            'headers' => array(
                                'Content-Type' => 'application/json; charset=utf-8',
                                'Authorization' => 'Bearer ' . $api_key
                            ),
                            'body' => $tags,
                            'method' => 'POST',
                            'data_format' => 'body'
                        )
                    );
                    // Handle response
                    self::handle_api_response($response);
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
if( !function_exists('SUPER_Mailchimp') ){
    function SUPER_Mailchimp() {
        return SUPER_Mailchimp::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['super_mailchimp'] = SUPER_Mailchimp();
}
