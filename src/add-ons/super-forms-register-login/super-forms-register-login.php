<?php
/**
 * Super Forms - Register & Login
 *
 * @package   Super Forms - Register & Login
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2019 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Register & Login
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Makes it possible to let users register and login from the front-end
 * Version:     1.8.0
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Register_Login')) :


    /**
     * Main SUPER_Register_Login Class
     *
     * @class SUPER_Register_Login
     * @version 1.0.0
     */
    final class SUPER_Register_Login {
    
        
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $version = '1.8.0';


        /**
         * @var string
         *
         *  @since      1.1.0
        */
        public $add_on_slug = 'register-login';
        public $add_on_name = 'Register & Login';


        /**
         * @var SUPER_Register_Login The single instance of the class
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
         * Main SUPER_Register_Login Instance
         *
         * Ensures only one instance of SUPER_Register_Login is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Register_Login()
         * @return SUPER_Register_Login - Main instance
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
         * SUPER_Register_Login Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_register_login_loaded');
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
            
            // Filters since 1.0.0
            add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_activation_code_element' ), 10, 2 );

            // Filters since 1.0.3
            add_filter( 'wp_authenticate_user', array( $this, 'check_user_login_status' ), 10, 2 );

            // Actions since 1.0.0
            add_action( 'wp_ajax_super_resend_activation', array( $this, 'resend_activation' ) );
            add_action( 'wp_ajax_nopriv_super_resend_activation', array( $this, 'resend_activation' ) );


            // Filters since 1.2.0
            add_filter( 'super_form_settings_filter', array( $this, 'set_get_values' ), 10, 2 );
            add_filter( 'super_countries_list_filter', array( $this, 'return_wc_countries' ), 10, 2 );

            if ( $this->is_request( 'frontend' ) ) {
                add_action( 'super_before_printing_message', array( $this, 'resend_activation_code_script' ) );
            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
                add_filter( 'super_email_tags_filter', array( $this, 'add_email_tags' ), 10, 1 );

                add_action( 'show_user_profile', array( $this, 'add_customer_meta_fields' ) );
                add_action( 'edit_user_profile', array( $this, 'add_customer_meta_fields' ) );
                add_action( 'personal_options_update', array( $this, 'save_customer_meta_fields' ) );
                add_action( 'edit_user_profile_update', array( $this, 'save_customer_meta_fields' ) );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );               
                add_action( 'init', array( $this, 'update_plugin' ) );

            }
            
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_sending_email_hook', array( $this, 'before_sending_email' ) );
                add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ) );
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
         * Return WC countries list for billing_country and shipping_country only
         *
         *  @since      1.2.0
        */
        public function return_wc_countries($countries, $data) {
            if(!isset($data['settings']['register_login_action'])) $data['settings']['register_login_action'] = '';
            if( (class_exists('WC_Countries')) && (($data['settings']['register_login_action']=='register') || ($data['settings']['register_login_action']=='update')) && (($data['name']=='billing_country') || ($data['name']=='shipping_country')) ) {
                $countries_obj = new WC_Countries();
                $countries = $countries_obj->__get('countries');
                return $countries;
            }
            return $countries;
        }


        /**
         * Set $_GET values for updating user forms
         *
         *  @since      1.2.0
        */
        public function set_get_values($settings, $data) {
            
            // Before proceeding, check if a user is logged in
            if( (isset($settings['register_login_action'])) && ($settings['register_login_action']=='update') && (is_user_logged_in()) ) {
                global $current_user;
                
                // Get all user data
                $user_data = (array) $current_user->data;
                
                // Set $_GET values for user data
                foreach( $user_data as $k => $v ) {
                    if( !isset($_GET[$k]) ) {
                        $_GET[$k] = $v;
                    }
                }

                // Get all user meta data
                $meta = get_user_meta( $user_data['ID'] );

                // Filter out empty meta data
                $meta = array_filter( array_map( function( $a ) {
                    return $a[0];
                }, $meta ) );

                // Set $_GET values for meta data
                foreach( $meta as $k => $v ) {
                    $_GET[$k] = $v;
                }
            }
            return $settings;
        }


        /**
         * Add extra auth login check based on user login status
         *
         * @since      1.0.3
         */
        public function check_user_login_status( $user, $password ) {
            
            // Now check if the login status of the user is pending or blocked
            $user_login_status = get_user_meta( $user->ID, 'super_user_login_status', true );
            if( ($user_login_status=='pending') || ($user_login_status=='blocked') ) {
                remove_action('authenticate', 'wp_authenticate_username_password', 20);
                $user = new WP_Error( 'account_not_active', sprintf( esc_html__( '%sERROR%s: You are not allowed to login.', 'super-forms' ), '<strong>', '</strong>' ) );
            }
            return $user;
        }


        /**
         * Get Status Field for the edit user pages.
         *
         * @since      1.0.3
         */
        public function get_customer_meta_fields() {
            $fields = array(
                'super_user_login_status' => array(
                    'title' => esc_html__( 'Super Forms - User Status', 'super-forms' ),
                    'fields' => array(
                        'super_user_login_status' => array(
                            'label' => esc_html__( 'User Status', 'super-forms' ),
                            'description' => esc_html__( 'When set to pending/blocked user won\'t be able to login', 'super-forms' ),
                            'type' => 'select',
                            'options' => array(
                                'active' => esc_html__( 'Active', 'super-forms' ),
                                'pending' => esc_html__( 'Pending', 'super-forms' ),
                                'blocked' => esc_html__( 'Blocked', 'super-forms' ),
                            )
                        ),
                    )
                ),
            );
            return $fields;
        }


        /**
         * Show Status Field on edit user pages.
         *
         * @param WP_User $user
         * @since      1.0.3
         */
        public function add_customer_meta_fields( $user ) {
            $show_fields = $this->get_customer_meta_fields();
            foreach( $show_fields as $fieldset ) {
                echo '<h3>' . $fieldset['title'] . '</h3>';
                echo '<table class="form-table">';
                foreach( $fieldset['fields'] as $key => $field ) {
                    echo '<tr>';
                        echo '<th>';
                            echo '<label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label>';
                        echo '</th>';
                        echo '<td>';
                            if ( ! empty( $field['type'] ) && 'select' == $field['type'] ) {
                                echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" class="' . ( ! empty( $field['class'] ) ? $field['class'] : '' ) . '" style="width: 25em;">';
                                $selected = esc_attr( get_user_meta( $user->ID, $key, true ) );
                                foreach( $field['options'] as $option_key => $option_value ) {
                                    echo '<option value="' . esc_attr( $option_key ) . '" ' . selected( $selected, $option_key, true ) . '>' . esc_attr( $option_value ) . '</option>';
                                }
                                echo '</select>';
                            }else{
                                echo '<input type="text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( get_user_meta( $user->ID, $key, true ) ) . '" class="' . ( ! empty( $field['class'] ) ? $field['class'] : 'regular-text' ) . '" />';
                            }
                            echo '<br/>';
                            echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
                        echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }


        /**
         * Save Address Fields on edit user pages.
         *
         * @param int $user_id User ID of the user being saved
         * @since      1.0.3
         */
        public function save_customer_meta_fields( $user_id ) {

            // Get form data and settings
            $form_data = get_user_meta( $user_id, 'super_user_approve_data', true );
            if( ($form_data!='') && (isset($_POST['super_user_login_status'])) ) {
                $settings = $form_data['settings'];
                $data = $form_data['data'];
                $user_status = get_user_meta( $user_id, 'super_user_login_status', true );
                if( ($user_status!='active') && ($_POST['super_user_login_status']=='active') ) {
                    if( (!empty($settings['register_approve_subject'])) && (!empty($settings['register_approve_email'])) ) {
                        $user = get_user_by( 'ID', $user_id );
                        if( $user ) {
                            $username = $user->user_login;
                            $user_email = $user->user_email;
                            $name = $user->display_name;

                            // Replace email tags with correct data
                            $subject = SUPER_Common::email_tags( $settings['register_approve_subject'], $data, $settings );
                            $message = $settings['register_approve_email'];
                            $message = str_replace( '{field_user_login}', $username, $message );
                            $message = str_replace( '{user_login}', $username, $message );
                            $message = str_replace( '{register_login_url}', $settings['register_login_url'], $message );

                            // Generate a password upon approval
                            if( (isset($settings['register_approve_generate_pass'])) && ($settings['register_approve_generate_pass']=='true') ) {
                                add_filter( 'send_password_change_email', '__return_false' );
                                $password = wp_generate_password();
                                $user_id = wp_update_user( array( 'ID' => $user->ID, 'user_pass' => $password ) );
                                $message = str_replace( '{field_user_pass}', $password, $message );
                                $message = str_replace( '{user_pass}', $password, $message );
                                $message = str_replace( '{register_generated_password}', $password, $message );
                            }

                            $message = SUPER_Common::email_tags( $message, $data, $settings );
                            $message = nl2br( $message );
                            $from = SUPER_Common::email_tags( $settings['header_from'], $data, $settings );
                            $from_name = SUPER_Common::email_tags( $settings['header_from_name'], $data, $settings );
                            $attachments = apply_filters( 'super_register_login_before_resend_activation_attachments_filter', array(), array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$message ) );

                            if( !isset($settings['header_reply_enabled']) ) $settings['header_reply_enabled'] = false;
                            $reply = '';
                            $reply_name = '';
                            if( $settings['header_reply_enabled']==false ) {
                                $custom_reply = false;
                            }else{
                                $custom_reply = true;
                                if( !isset($settings['header_reply']) ) $settings['header_reply'] = '';
                                if( !isset($settings['header_reply_name']) ) $settings['header_reply_name'] = '';
                                $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply'], $data, $settings ) );
                                $reply_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply_name'], $data, $settings ) );
                            }

                            // Send the email
                            $message = apply_filters( 'super_before_sending_email_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
                            $mail = SUPER_Common::email( $user_email, $from, $from_name, $custom_reply, $reply, $reply_name, '', '', $subject, $message, $settings, $attachments );
                         
                            // After email is send, delete the email and subject (remove the password from database for security reasons)
                            if( empty( $mail->ErrorInfo ) ) {
                                delete_user_meta( $user_id, 'super_user_approve_data' );          
                            }

                            exit;

                        }
                    }
                }
            }

            $save_fields = $this->get_customer_meta_fields();
            foreach ( $save_fields as $fieldset ) {
                foreach ( $fieldset['fields'] as $key => $field ) {
                    if ( isset( $_POST[ $key ] ) ) {
                        if (function_exists('wc_clean')) {
                            update_user_meta( $user_id, $key, wc_clean( $_POST[ $key ] ) );
                        }else{
                            update_user_meta( $user_id, $key, sanitize_text_field( $_POST[ $key ] ) );
                        }
                    }
                }
            }
        }

        
        /**
         * Hook into outputting the message and make sure to add the resend activation javascript
         *
         *  @since      1.0.0
        */
        public static function resend_activation_code_script( $data ) {
            $global_settings = get_option( 'super_settings' );
            $handle = 'super-register-common';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'assets/js/frontend/common.js', array( 'jquery' ), SUPER_Register_Login()->version, false );  
            wp_localize_script( $handle, $name, array( 'ajaxurl'=>SUPER_Forms()->ajax_url(), 'duration'=>absint( $global_settings['form_duration'] ) ) );
            wp_enqueue_script( $handle );
        }


        /**
         * Hook into the default email tags and add extra tags that can be used in our Activation email
         *
         *  @since      1.0.0
        */
        public static function add_email_tags( $tags ) {
            $tags['register_login_url'] = array(
                esc_html__( 'Retrieves the login page URL', 'super-forms' ),
                ''
            );
            $tags['register_activation_code'] = array(
                esc_html__( 'Retrieves the activation code', 'super-forms' ),
                ''
            );
            $tags['register_generated_password'] = array(
                esc_html__( 'Retrieves the generated password', 'super-forms' ),
                ''
            );
            return $tags;
        }


        /**
         * Handle the Activation Code element output
         *
         *  @since      1.0.0
        */
        public static function activation_code( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
            
            $return = false;
            if( ( SUPER_Forms::is_request( 'frontend' ) ) && ( isset( $_GET['code'] ) ) ) {
                $code = sanitize_text_field( $_GET['code'] );
                $return = true;
            }
            if ( SUPER_Forms::is_request( 'admin' ) ) {
                $code = '';
                $return = true;
            }
            if( $return==true ) {
                $atts['name'] = 'activation_code';
                $result = SUPER_Shortcodes::opening_tag( $tag, $atts );
                $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
                $result .= '<input class="super-shortcode-field" type="text"';
                $result .= ' name="' . $atts['name'] . '" value="' . $code . '"';
                $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
                $result .= ' />';
                $result .= '</div>';
                $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag );
                $result .= '</div>';
                return $result;
            }

        }


        /**
         * Hook into elements and add Activation Code element
         * This element will show the activation code input field when it has been set in the URL parameter
         *
         *  @since      1.0.0
        */
        public static function add_activation_code_element( $array, $attributes ) {

            // Include the predefined arrays
            require(SUPER_PLUGIN_DIR.'/includes/shortcodes/predefined-arrays.php' );

            $array['form_elements']['shortcodes']['activation_code_predefined'] = array(
                'name' => esc_html__( 'Activation Code', 'super-forms' ),
                'icon' => 'code',
                'predefined' => array(
                    array(
                        'tag' => 'activation_code',
                        'group' => 'form_elements',
                        'data' => array(
                            'placeholder' => '[-CODE-]',
                            'icon' => 'code',
                        )
                    )
                )
            );
            $array['form_elements']['shortcodes']['activation_code'] = array(
                'hidden' => true,
                'callback' => 'SUPER_Register_Login::activation_code',
                'name' => esc_html__( 'Activation Code', 'super-forms' ),
                'icon' => 'code',
                'atts' => array(
                    'general' => array(
                        'name' => esc_html__( 'General', 'super-forms' ),
                        'fields' => array(
                            'label' => $label,
                            'description'=> $description,
                            'placeholder' => SUPER_Shortcodes::placeholder( $attributes, '' ),
                            'placeholderFilled' => ( function_exists( 'SUPER_Shortcodes::placeholderFilled' ) ? SUPER_Shortcodes::placeholderFilled( $attributes, '' ) : SUPER_Shortcodes::placeholder( $attributes, '' ) ),
                            'tooltip' => $tooltip,
                        )
                    ),
                    'advanced' => array(
                        'name' => esc_html__( 'Advanced', 'super-forms' ),
                        'fields' => array(
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
                            'icon' => SUPER_Shortcodes::icon( $attributes, 'code' ),
                        ),
                    ),
                    'conditional_logic' => $conditional_logic_array
                ),
            );
            return $array;
        }


        /**
         * Hook into settings and add Register & Login settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
            global $wp_roles;
            $all_roles = $wp_roles->roles;
            $editable_roles = apply_filters( 'editable_roles', $all_roles );
            $roles = array();
            foreach( $editable_roles as $k => $v ) {
                $roles[$k] = $v['name'];
            }
            $array['register_login'] = array(        
                'name' => esc_html__( 'Register & Login', 'super-forms' ),
                'label' => esc_html__( 'Register & Login Settings', 'super-forms' ),
                'fields' => array(
                    'register_login_action' => array(
                        'name' => esc_html__( 'Actions', 'super-forms' ),
                        'label' => esc_html__( 'Select what the form should do (register, login, update or reset a password)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_login_action', $settings['settings'], 'none' ),
                        'filter' => true,
                        'type' => 'select',
                        'values' => array(
                            'none' => esc_html__( 'None (do nothing)', 'super-forms' ),
                            'register' => esc_html__( 'Register a new user', 'super-forms' ),
                            'login' => esc_html__( 'Login (user will be logged in)', 'super-forms' ),
                            'reset_password' => esc_html__( 'Reset password (lost password)', 'super-forms' ),
                            'update' => esc_html__( 'Update current logged in user', 'super-forms' ),
                        ),
                    ),

                    // @since 1.4.0 - option to register new user if user doesn't exists while updating user
                    'register_login_register_not_logged_in' => array(
                        'default' => SUPER_Settings::get_value( 0, 'register_login_register_not_logged_in', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Register new user if user is not logged in', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'update'
                    ),

                    // @since 1.5.0 - option to update user based on user_id field (if exists or if it's set via GET or POST)
                    'register_login_user_id_update' => array(
                        'name' => esc_html__( 'Update based on user ID (user_id)', 'super-forms' ),
                        'label' => esc_html__( 'A hidden field named "user_id" must be present in your form in order for this to work', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_login_user_id_update', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Update user based on user_id field or GET or POST', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'update'
                    ),

                    // @since 1.2.6 - skip registration if user_login or user_email are not found
                    'register_login_action_skip_register' => array(
                        'label' => esc_html__( 'This option is only usefull whenever you conditionally hide the user_login or user_email field', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_login_action_skip_register', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Skip registration if user_login or user_email are not found', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register'
                    ),

                    'login_user_role' => array(
                        'name' => esc_html__( 'Allowed user role(s)', 'super-forms' ),
                        'desc' => esc_html__( 'Which user roles are allowed to login?', 'super-forms' ),
                        'type' => 'select',
                        'multiple' => true,
                        'default' => SUPER_Settings::get_value( 0, 'login_user_role', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'login',
                        'values' => $roles,
                    ),
                    'register_user_role' => array(
                        'name' => esc_html__( 'User role', 'super-forms' ),
                        'desc' => esc_html__( 'What user role should this user get?', 'super-forms' ),
                        'type' => 'select',
                        'default' => SUPER_Settings::get_value( 0, 'register_user_role', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register,update',
                        'values' => array_merge($roles, array('_super_keep_existing_role' => esc_html__( 'Keep existing role (only use this when updating existing user)', 'super-forms' ))),
                    ),
                    'register_user_signup_status' => array(
                        'name' => esc_html__( 'User login status after registration', 'super-forms' ),
                        'desc' => esc_html__( 'The login status the user should get after completed registration.', 'super-forms' ),
                        'type' => 'select',
                        'default' => SUPER_Settings::get_value( 0, 'register_user_signup_status', $settings['settings'], 'active' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register',
                        'values' => array(
                            'active' => esc_html__( 'Active (default)', 'super-forms' ),
                            'pending' => esc_html__( 'Pending', 'super-forms' ),
                            'blocked' => esc_html__( 'Blocked', 'super-forms' ),
                        ),
                    ),

                    // @since 1.2.7 - Send activation email when account is activated by admin
                    'register_send_approve_email' => array(
                        'desc' => esc_html__( 'When admin approves registration this email will be send to the user', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_send_approve_email', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Send approve email when account is activated by admin', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_user_signup_status',
                        'filter_value' => 'pending,blocked'
                    ),
                    'register_approve_subject' => array(
                        'name' => esc_html__( 'Approved Email Subject', 'super-forms' ),
                        'desc' => esc_html__( 'Example: Your account has been approved', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_approve_subject', $settings['settings'], esc_html__( 'Account has been approved', 'super-forms' ) ),
                        'filter' => true,
                        'parent' => 'register_send_approve_email',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_approve_email' => array(
                        'name' => esc_html__( 'Approved Email Body', 'super-forms' ),
                        'desc' => esc_html__( 'The email message.', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'register_approve_email', $settings['settings'], sprintf( __( 'Dear {user_login},%1$s%1$sYour account has been approved and can now be used!%1$s%1$sUsername: %2$s{user_login}%3$s%1$sPassword: %2$s{user_pass}%3$s%1$s%1$sClick %4$shere%5$s to login into your account.%1$s%1$s%1$sBest regards,%1$s%1$s{option_blogname}', 'super-forms' ), '<br />', '<strong>', '</strong>', '<a href="{register_login_url}">', '</a>' ) ),
                        'filter' => true,
                        'parent' => 'register_send_approve_email',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_approve_generate_pass' => array(
                        'desc' => esc_html__( 'This will generate a new password as soon as the user account has been approved', 'super-forms' ),
                        'label' => esc_html__( 'You can retrieve the generated password with {register_generated_password} in the email', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_approve_generate_pass', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Generate new password on the fly when sending approve email', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_send_approve_email',
                        'filter_value' => 'true'
                    ),
                    'register_login_activation' => array(
                        'name' => esc_html__( 'Send activation email', 'super-forms' ),
                        'desc' => esc_html__( 'Optionally let users activate their account or let them instantly login without verification', 'super-forms' ),
                        'type' => 'select',
                        'default' => SUPER_Settings::get_value( 0, 'register_login_activation', $settings['settings'], 'verify' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register',
                        'values' => array(
                            'verify' => esc_html__( 'Send activation email without logging in the user', ' super' ),
                            'verify_login' => esc_html__( 'Send activation email and login the user automatically', 'super-forms' ),
                            'auto' => esc_html__( 'Auto activate and login (login status will also be updated to: active)', 'super-forms' ),
                            'login' => esc_html__( 'Login automatically without activating', 'super-forms' ),
                            'activate' => esc_html__( 'Auto activate but don\'t login automatically', 'super-forms' ),
                            'none' => esc_html__( 'Do nothing (don\'t send email and don\'t activate)', 'super-forms' ),
                        ),
                    ),
                    'register_login_url' => array(
                        'name' => esc_html__( 'Login page URL', 'super-forms' ),
                        'desc' => esc_html__( 'URL of your login page where you placed the login form, here users can activate their account', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_login_url', $settings['settings'], get_site_url() . '/login/' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register,login,reset_password,update',
                        'allow_empty' => true,
                    ),
                    'register_welcome_back_msg' => array(
                        'name' => esc_html__( 'Welcome back message', 'super-forms' ),
                        'desc' => esc_html__( 'Display a welcome message after user has logged in (leave blank for no message)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_welcome_back_msg', $settings['settings'], esc_html__( 'Welcome back {user_login}!', 'super-forms' ) ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'login',
                        'allow_empty' => true,
                    ),
                    'register_incorrect_code_msg' => array(
                        'name' => esc_html__( 'Incorrect activation code message', 'super-forms' ),
                        'desc' => esc_html__( 'Display a message when the activation code is incorrect', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_incorrect_code_msg', $settings['settings'], esc_html__( 'The combination username, password and activation code is incorrect!', 'super-forms' ) ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'login',
                        'allow_empty' => true,
                    ),
                    'register_account_activated_msg' => array(
                        'name' => esc_html__( 'Account activated message', 'super-forms' ),
                        'desc' => esc_html__( 'Display a message when account has been activated', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_account_activated_msg', $settings['settings'], esc_html__( 'Hello {user_login}, your account has been activated!', 'super-forms' ) ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'login',
                        'allow_empty' => true,
                    ),
                    'register_activation_subject' => array(
                        'name' => esc_html__( 'Activation Email Subject', 'super-forms' ),
                        'desc' => esc_html__( 'Example: Activate your account', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_activation_subject', $settings['settings'], esc_html__( 'Activate your account', 'super-forms' ) ),
                        'filter' => true,
                        'parent' => 'register_login_activation',
                        'filter_value' => 'verify,verify_login',
                        'allow_empty' => true,
                    ),
                    'register_activation_email' => array(
                        'name' => esc_html__( 'Activation Email Body', 'super-forms' ),
                        'desc' => esc_html__( 'The email message. You can use {activation_code} and {register_login_url}', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'register_activation_email', $settings['settings'], sprintf( __( 'Dear {user_login},%1$s%1$sThank you for registering! Before you can login you will need to activate your account.%1$sBelow you will find your activation code. You need this code to activate your account:%1$s%1$sActivation Code: %2$s{register_activation_code}%3$s%1$s%1$sClick %4$shere%5$s to activate your account with the provided code.%1$s%1$s%1$sBest regards,%1$s%1$s{option_blogname}', 'super-forms' ), '<br />', '<strong>', '</strong>', '<a href="{register_login_url}?code={register_activation_code}">', '</a>' ) ),
                        'filter' => true,
                        'parent' => 'register_login_activation',
                        'filter_value' => 'verify,verify_login',
                        'allow_empty' => true,
                    ),
                    'register_login_show_toolbar' => array(
                        'default' => SUPER_Settings::get_value( 0, 'register_login_show_toolbar', $settings['settings'], 'true' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Show Toolbar when viewing site (enabled by default)', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register,update',
                        'allow_empty' => true,
                    ),
                    'register_login_user_meta' => array(
                        'name' => esc_html__( 'Save custom user meta', 'super-forms' ),
                        'label' => esc_html__( 'Useful for external plugins such as WooCommerce. Example: \'field_name|meta_key\' (each on a new line)', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'register_login_user_meta', $settings['settings'], '' ),
                        'placeholder' => "field_name|meta_key\nbilling_first_name|billing_first_name\nbilling_last_name|billing_last_name\nbilling_company|billing_company\nbilling_address_1|billing_address_1\nbilling_address_2|billing_address_2\nbilling_city|billing_city\nbilling_postcode|billing_postcode\nbilling_country|billing_country\nbilling_state|billing_state\nbilling_phone|billing_phone\nbilling_email|billing_email\nshipping_first_name|shipping_first_name\nshipping_last_name|shipping_last_name\nshipping_company|shipping_company\nshipping_address_1|shipping_address_1\nshipping_address_2|shipping_address_2\nshipping_city|shipping_city\nshipping_postcode|shipping_postcode\nshipping_country|shipping_country\nshipping_state|shipping_state",
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_enabled' => array(
                        'desc' => esc_html__( 'This will create a new site within your wordpress site network', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_login_multisite_enabled', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Create new Multi-site after registration', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register'
                    ),
                    'register_login_multisite_domain' => array(
                        'name' => esc_html__( 'Domain name for blog', 'super-forms' ),
                        'label' => esc_html__( 'Default: None', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_login_multisite_domain', $settings['settings'], '{user_email}' ),
                        'filter' => true,
                        'parent' => 'register_login_multisite_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_path' => array(
                        'name' => esc_html__( 'Path to the blog', 'super-forms' ),
                        'label' => esc_html__( 'Default: None', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_login_multisite_path', $settings['settings'], '{user_email}' ),
                        'filter' => true,
                        'parent' => 'register_login_multisite_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_title' => array(
                        'name' => esc_html__( 'Title for blog', 'super-forms' ),
                        'label' => esc_html__( 'Default: None', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_login_multisite_title', $settings['settings'], '{user_email}' ),
                        'filter' => true,
                        'parent' => 'register_login_multisite_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_id' => array(
                        'name' => esc_html__( 'Site ID, if running multiple networks', 'super-forms' ),
                        'label' => esc_html__( 'Default: 1', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_login_multisite_id', $settings['settings'], '1' ),
                        'filter' => true,
                        'parent' => 'register_login_multisite_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_email' => array(
                        'default' => SUPER_Settings::get_value( 0, 'register_login_multisite_email', $settings['settings'], 'true' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Send site credentials to the user email', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_login_multisite_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_reset_password_success_msg' => array(
                        'name' => esc_html__( 'Success message', 'super-forms' ),
                        'desc' => esc_html__( 'Display a message after user has reset their password (leave blank for no message)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_reset_password_success_msg', $settings['settings'], esc_html__( 'Your password has been reset. We have just send you a new password to your email address.', 'super-forms' ) ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'reset_password',
                        'allow_empty' => true,
                    ),
                    'register_reset_password_not_exists_msg' => array(
                        'name' => esc_html__( 'Not found message', 'super-forms' ),
                        'desc' => esc_html__( 'Display a message when no user was found (leave blank for no message)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_reset_password_not_exists_msg', $settings['settings'], esc_html__( 'We couldn\'t find a user with the given email address!', 'super-forms' ) ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'reset_password',
                        'allow_empty' => true,
                    ),
                    'register_reset_password_subject' => array(
                        'name' => esc_html__( 'Lost Password Email Subject', 'super-forms' ),
                        'desc' => esc_html__( 'Example: Your new password. You can use {user_login}', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'register_reset_password_subject', $settings['settings'], esc_html__( 'Your new password', 'super-forms' ) ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'reset_password',
                        'allow_empty' => true,
                    ),
                    'register_reset_password_email' => array(
                        'name' => esc_html__( 'Lost Password Email Body', 'super-forms' ),
                        'desc' => esc_html__( 'The email message. You can use {user_login}, {register_generated_password} and {register_login_url}', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'register_reset_password_email', $settings['settings'], sprintf( __( 'Dear {user_login},%1$s%1$sYou just requested to reset your password.%1$sUsername: %2$s{user_login}%3$s%1$sPassword: %2$s{register_generated_password}%3$s%1$s%1$sClick %4$shere%5$s to login with your new password.%1$s%1$s%1$sBest regards,%1$s%1$s{option_blogname}', 'super-forms' ), "\n", '<strong>', '</strong>', '<a href="{register_login_url}">', '</a>' ) ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'reset_password',
                        'allow_empty' => true,
                    ),

                    // @since 1.2.0 - not logged in user for when we are updating user data
                    'register_login_not_logged_in_msg' => array(
                        'name' => esc_html__( 'Not logged in message (leave blank for no message)', 'super-forms' ),
                        'desc' => esc_html__( 'Display a message when no user is logged in', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'register_login_not_logged_in_msg', $settings['settings'], sprintf( __( 'You must be logged in to submit this form. Click %shere%s to login!', 'super-forms' ), '<a href="{register_login_url}">', '</a>' ) ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'update',
                        'allow_empty' => true,
                    ),
                    'register_login_update_user_meta' => array(
                        'name' => esc_html__( 'Update custom user meta', 'super-forms' ),
                        'label' => esc_html__( 'E.g: field_name|meta_key (each on a new line)', 'super-forms' ),
                        'desc' => esc_html__( 'Useful for external plugins such as WooCommerce.', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'register_login_update_user_meta', $settings['settings'], "billing_first_name|billing_first_name\nbilling_last_name|billing_last_name\nbilling_company|billing_company\nbilling_address_1|billing_address_1\nbilling_address_2|billing_address_2\nbilling_city|billing_city\nbilling_postcode|billing_postcode\nbilling_country|billing_country\nbilling_state|billing_state\nbilling_phone|billing_phone\nbilling_email|billing_email\nshipping_first_name|shipping_first_name\nshipping_last_name|shipping_last_name\nshipping_company|shipping_company\nshipping_address_1|shipping_address_1\nshipping_address_2|shipping_address_2\nshipping_city|shipping_city\nshipping_postcode|shipping_postcode\nshipping_country|shipping_country\nshipping_state|shipping_state" ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'update',
                        'allow_empty' => true,
                    ),
                )
            );
            return $array;
        }


        /**
         * Make sure to update user meta data after possible uploaded file(s) have been saved into media library
         * otherwise we are unable to return/save the file ID correctly
         *
         *  @since      1.3.0
        */
        public static function before_email_success_msg( $atts ) {
            $settings = $atts['settings'];
            $data = $atts['data'];

            // @since 1.2.0 - update existing user data
            if( $settings['register_login_action']=='update' ) {
                $user_id = SUPER_Forms()->session->get( 'super_update_user_meta' );
                $user_id = absint($user_id);
                if( $user_id!=0 ) {
                    SUPER_Forms()->session->set( 'super_update_user_meta', false );

                    // Loop through all default user data that WordPress provides us with out of the box
                    $other_userdata = array(
                        // 'role',      // We do not want this to be changed from the form itself because it poses a security risk!
                                        // if you really want to change this, do it via a hook after the user was updated ;)
                        'user_login',
                        'user_email',
                        'user_pass',
                        'user_registered',
                        'show_admin_bar_front',
                        'user_nicename',
                        'user_url',
                        'display_name',
                        'nickname',
                        'first_name',
                        'last_name',
                        'description',
                        'rich_editing',
                        'jabber',
                        'aim',
                        'yim'
                    );
                    $userdata = array();
                    foreach( $other_userdata as $k ) {
                        if( isset( $data[$k]['value'] ) ) {
                            $value = $data[$k]['value'];
                            if( $k=='user_login' ) $value = sanitize_user($value);
                            if( $k=='user_email' ) $value = sanitize_email($value);
                            $userdata[$k] = $value;
                        }
                    }

                    // Option to optionally change/update the user role or to keep the existing role
                    if(!empty($settings['register_user_role'])){
                        // Only change role if we want to
                        if( $settings['register_user_role']!=='_super_keep_existing_role' ) {
                            // Change the user role to something different
                            $userdata['role'] = $settings['register_user_role'];
                        }
                    }

                    // @since 1.6.1 - option to enable or disable toolbar
                    if(!empty($settings['register_login_show_toolbar'])) {
                        $userdata['show_admin_bar_front'] = $settings['register_login_show_toolbar'];
                    }

                    $userdata['ID'] = $user_id;
                    $result = wp_update_user( $userdata );
                    if( is_wp_error( $result ) ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = $return->get_error_message(),
                            $redirect = null
                        );
                    }

                    // Save custom user meta
                    $meta_data = array();
                    $custom_user_meta = explode( "\n", $settings['register_login_update_user_meta'] );
                    foreach( $custom_user_meta as $k ) {
                        $field = explode( "|", $k );
                        if( isset( $data[$field[0]]['value'] ) ) {
                            $meta_data[$field[1]] = $data[$field[0]]['value'];
                        }
                    }

                    foreach( $meta_data as $k => $v ) {
                        update_user_meta( $user_id, $k, $v ); 
                    }
                }else{
                    // @since 1.4.0 - register new user if user doesn't exists while updating user
                    if( (!empty($settings['register_login_register_not_logged_in'])) && ($settings['register_login_register_not_logged_in']=='true') ) {
                        $settings['register_login_action'] = 'register';
                    }
                }
            }

            if( $settings['register_login_action']=='register' ) {
                $user_id = SUPER_Forms()->session->get( 'super_update_user_meta' );
                $user_id = absint($user_id);
                if( $user_id!=0 ) {
                    SUPER_Forms()->session->set( 'super_update_user_meta', false );

                    // Save custom user meta
                    $meta_data = array();
                    $custom_meta = explode( "\n", $settings['register_login_user_meta'] );
                    foreach( $custom_meta as $k ) {
                        $field = explode( "|", $k );
                        if(!isset($field[1])) continue;
                        // @since 1.0.3 - first check if a field with the name exists
                        if( isset( $data[$field[0]]['value'] ) ) {
                            $meta_data[$field[1]] = $data[$field[0]]['value'];
                        }else{
                            
                            // @since 1.1.2 - check if type is files
                            if( (!empty($data[$field[0]])) && ( ($data[$field[0]]['type']=='files') && (isset($data[$field[0]]['files'])) ) ) {
                                if( count($data[$field[0]]['files']>1) ) {
                                    foreach( $data[$field[0]]['files'] as $fk => $fv ) {
                                        if($meta_data[$field[1]]==''){
                                            $meta_data[$field[1]] = (!empty($fv['attachment']) ? $fv['attachment'] : (!empty($fv['path']) ? $fv['path'] : 0));
                                        }else{
                                            $meta_data[$field[1]] .= ',' . (!empty($fv['attachment']) ? $fv['attachment'] : (!empty($fv['path']) ? $fv['path'] : 0));
                                        }
                                    }
                                }elseif( count($data[$field[0]]['files'])==1) {
                                    $cur = $data[$field[0]]['files'][0];
                                    if(!empty($cur['attachment'])){
                                        $fValue = absint($cur['attachment']);
                                    }else{
                                        $fValue = (!empty($cur['path']) ? $cur['path'] : 0);
                                    }
                                    $meta_data[$field[1]] = $fValue;
                                }else{
                                    $meta_data[$field[1]] = '';
                                }
                                continue;
                            }else{
                                // @since 1.0.3 - if no field exists, just save it as a string
                                $string = SUPER_Common::email_tags( $field[0], $data, $settings );
                                
                                // @since 1.0.3 - check if string is serialized array
                                $unserialize = unserialize($string);
                                if ($unserialize !== false) {
                                    $meta_data[$field[1]] = $unserialize;
                                }else{
                                    $meta_data[$field[1]] = $string;
                                }
                            }
                        }
                    }

                    foreach( $meta_data as $k => $v ) {
                        // @since 1.1.1 - Check for ACF field and check if checkbox, if checkbox save values as Associative Array
                        if (function_exists('get_field_object')) {
                            global $wpdb;
                            $length = strlen($k);

                            // @since 1.1.2 - Because there are major differences between ACF Pro and the regular ACF plugin we have to do different queries
                            if( class_exists('acf_pro') ) {
                                $sql = "SELECT post_name FROM {$wpdb->posts} WHERE post_excerpt = '$k' AND post_type = 'acf-field'";
                            }else{
                                $sql = "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'field_%' AND meta_value LIKE '%\"name\";s:$length:\"$k\";%';";
                            }
                            $acf_field = $wpdb->get_var($sql);
                            if( $acf_field ) {
                                $acf_field = get_field_object($acf_field);

                                // @since 1.1.3 - save a checkbox or select value
                                if( ($acf_field['type']=='checkbox') || ($acf_field['type']=='select') || ($acf_field['type']=='radio') || ($acf_field['type']=='gallery') ) {
                                    $value = explode( ",", $v );
                                    update_field( $acf_field['key'], $value, 'user_'.$user_id );
                                    continue;
                                }elseif( $acf_field['type']=='google_map' ) {
                                    if( isset($data[$k]['geometry']) ) {
                                        $data[$k]['geometry']['location']['address'] = $data[$k]['value'];
                                        $value = $data[$k]['geometry']['location'];
                                    }else{
                                        $value = array(
                                            'address' => $data[$k]['value'],
                                            'lat' => '',
                                            'lng' => '',
                                        );
                                    }
                                    update_field( $acf_field['key'], $value, 'user_'.$user_id );
                                    continue;
                                }

                                // @since 1.1.3 - save a repeater field value
                                if($acf_field['type']=='repeater'){
                                    $repeater_values = array();
                                    foreach($acf_field['sub_fields'] as $sk => $sv){
                                        if( isset($data[$sv['name']]) ) {
                                            $repeater_values[0][$sv['name']] = SUPER_Register_Login()->return_field_value( $data, $sv['name'], $sv['type'], $settings );
                                            $field_counter = 2;
                                            while( isset($data[$sv['name'] . '_' . $field_counter]) ) {
                                                $repeater_values[$field_counter-1][$sv['name']] = SUPER_Register_Login()->return_field_value( $data, $sv['name'] . '_' . $field_counter, $sv['type'], $settings );
                                                $field_counter++;
                                            }
                                        }
                                    }
                                    update_field( $acf_field['key'], $repeater_values, 'user_'.$user_id );
                                    continue;
                                }

                                // save a basic text value
                                update_field( $acf_field['key'], $v, 'user_'.$user_id );
                                continue;
                            }

                        }
                        update_user_meta( $user_id, $k, $v ); 
                    }
                }
            }
        }


        /**
         * Hook into before sending email and check if we need to register or login a user
         *
         *  @since      1.0.0
        */
        public static function before_sending_email( $atts ) {
            $data = $atts['post']['data'];
            $settings = $atts['settings'];
            
            if( !isset( $settings['register_login_action'] ) ) return true;
            if( $settings['register_login_action']=='none' ) return true;

            // @since 1.2.0 - update existing user data
            if( $settings['register_login_action']=='update' ) {

                // @since 1.5.0 - option to update user based on user_id field (if exists or if it's set via GET or POST)
                $user_id = get_current_user_id();
                if( (!empty($settings['register_login_user_id_update'])) && ($settings['register_login_user_id_update']=='true') ) {
                    if( (isset($data['user_id']['value'])) && (absint($data['user_id']['value'])!=0) ) {
                        $user_id = absint($data['user_id']['value']);
                    }
                }
                if( $user_id==0 ) {
                    // @since 1.4.0 - do not throw error message when we allow none logged in users to register
                    if( (!empty($settings['register_login_register_not_logged_in'])) && ($settings['register_login_register_not_logged_in']=='true') ) {
                        $settings['register_login_action'] = 'register';
                    }else{
                        $msg = $settings['register_login_not_logged_in_msg'];
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }
                }else{
                    // @since 1.3.0 - save user meta after possible file(s) have been processed and saved into media library
                    SUPER_Forms()->session->set( 'super_update_user_meta', $user_id );
                }

            }

            if( $settings['register_login_action']=='register' ) {

                // @since 1.2.6 - skip registration if user_login or user_email couldn't be found or where conditionally hidden
                if(!isset($settings['register_login_action_skip_register'])) $settings['register_login_action_skip_register'] = '';
                if( ($settings['register_login_action_skip_register']=='true') && ( (!isset($data['user_login'])) || (!isset($data['user_email'])) ) ) {
                    // do nothing
                }else{

                    // Before we proceed, lets check if we have at least a user_login and user_email field
                    if( ( !isset( $data['user_login'] ) ) || ( !isset( $data['user_email'] ) ) ) {
                        $msg = sprintf( esc_html__( 'We couldn\'t find the %1$s and %2$s fields which are required in order to register a new user. Please %3$sedit%4$s your form and try again', 'super-forms' ), '<strong>user_login</strong>', '<strong>user_email</strong>', '<a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">', '</a>' );
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }

                    // Now lets check if a user already exists with the same user_login or user_email
                    $user_login = sanitize_user( $data['user_login']['value'] );
                    $user_email = sanitize_email( $data['user_email']['value'] );
                    
                    $username_exists = username_exists($user_login);
                    if( $username_exists!=false ) {
                        $user = get_user_by( 'login', $user_login );
                        $user_login_status = get_user_meta( $user->ID, 'super_user_login_status', true );
                        if( ($user_login_status=='active') || ($user_login_status=='') ) {
                            $username_exists = true;
                        }else{
                            wp_delete_user( $user->ID );
                            $username_exists = false;
                        }
                    }

                    $email_exists = email_exists($user_email);        
                    if( $email_exists!=false ) {
                        $user = get_user_by( 'email', $user_email );
                        $user_login_status = get_user_meta( $user->ID, 'super_user_login_status', true );
                        if( ($user_login_status=='active') || ($user_login_status=='') ) {
                            $email_exists = true;
                        }else{
                            wp_delete_user( $user->ID );
                            $email_exists = false;
                        }
                    }

                    if( ( $username_exists!=false ) || ( $email_exists!=false ) ) {
                        $msg = esc_html__( 'Username or Email address already exists, please try again', 'super-forms' );
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = $msg,
                            $redirect = null,
                            $fields = array(
                                'user_login' => 'input',
                                'user_pass' => 'input'
                            )
                        );
                    }

                    // If user_pass field doesn't exist, we can generate one and send it by email to the registered user
                    $send_password = false;
                    if( !isset( $data['user_pass'] ) ) {
                        $send_password = true;
                        $password = wp_generate_password();
                    }else{
                        $password = $data['user_pass']['value'];
                    }

                    // Lets gather all data that we need to insert for this user
                    $userdata = array();
                    $userdata['user_login'] = $user_login;
                    $userdata['user_email'] = $user_email;
                    $userdata['user_pass'] = $password;
                    $userdata['role'] = $settings['register_user_role'];
                    $userdata['user_registered'] = date('Y-m-d H:i:s');
                    $userdata['show_admin_bar_front'] = 'false';

                    // Also loop through some of the other default user data that WordPress provides us with out of the box
                    $other_userdata = array(
                        'user_nicename',
                        'user_url',
                        'display_name',
                        'nickname',
                        'first_name',
                        'last_name',
                        'description',
                        'rich_editing',
                        'role', // This is in case we have a custom dropdown with the name "role" which allows users to select their own account type/role
                        'jabber',
                        'aim',
                        'yim'
                    );
                    foreach( $other_userdata as $k ) {
                        if( isset( $data[$k]['value'] ) ) {
                            $userdata[$k] = $data[$k]['value'];
                        }
                    }

                    // @since 1.6.1 - option to enable or disable toolbar
                    if(!empty($settings['register_login_show_toolbar'])) {
                        $userdata['show_admin_bar_front'] = $settings['register_login_show_toolbar'];
                    }

                    // Insert the user and return the user ID
                    $user_id = wp_insert_user( $userdata );
                    if( is_wp_error( $user_id ) ) {
                        $msg = $user_id->get_error_message();

                        SUPER_Forms()->session->set( 'super_msg', array( 'data'=>$data, 'settings'=>$settings, 'msg'=>$msg, 'type'=>'error' ) );
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }

                    // @since v1.0.3 - currently used by the WooCommerce Checkout Add-on
                    do_action( 'super_after_wp_insert_user_action', array( 'user_id'=>$user_id, 'atts'=>$atts ) );
       
                    // @since 1.3.0 - save user meta after possible file(s) have been processed and saved into media library
                    SUPER_Forms()->session->set( 'super_update_user_meta', $user_id );

                    // @since 1.0.3
                    if( !isset($settings['register_user_signup_status']) ) $settings['register_user_signup_status'] = 'active';
                    update_user_meta( $user_id, 'super_user_login_status', $settings['register_user_signup_status'] );

                    if( (isset($settings['register_send_approve_email'])) && ($settings['register_send_approve_email']=='true') ) {
                        update_user_meta( $user_id, 'super_user_approve_data', array('settings'=>$settings, 'data'=>$data) );
                    }

                    // Check if we need to send an activation email to this user
                    if( ($settings['register_login_activation']=='verify') || ($settings['register_login_activation']=='verify_login') ) {
                        $code = wp_generate_password( 8, false );
                        
                        // @since 1.2.4 - allows users to use a custom activation code, for instance generated with the unique random number with a hidden field
                        if(isset($data['register_activation_code'])){
                            $code = $data['register_activation_code']['value'];
                        }
                        
                        update_user_meta( $user_id, 'super_account_status', 0 ); // 0 = inactive, 1 = active
                        update_user_meta( $user_id, 'super_account_activation', $code ); 
                        $user = get_user_by( 'id', $user_id );

                        // Replace email tags with correct data
                        $subject = SUPER_Common::email_tags( $settings['register_activation_subject'], $data, $settings, $user );
                        $message = $settings['register_activation_email'];
                        $message = str_replace( '{register_login_url}', $settings['register_login_url'], $message );
                        $message = str_replace( '{register_activation_code}', $code, $message );
                        $message = str_replace( '{register_generated_password}', $password, $message );
                        $message = SUPER_Common::email_tags( $message, $data, $settings, $user );
                        $message = nl2br( $message );

                        // @since 1.6.1 - set native from headers
                        if(!empty($settings['header_from'])){
                            $from = SUPER_Common::email_tags( $settings['header_from'], $data, $settings, $user );
                        }else{
                            $urlparts = parse_url(home_url());
                            $from = 'no-replyy@' . $urlparts['host']; // returns domain name
                        }
                        if(!empty($settings['header_from_name'])){
                            $from_name = SUPER_Common::email_tags( $settings['header_from_name'], $data, $settings, $user );
                        }else{
                            $from_name = get_bloginfo('name');
                        }

                        $attachments = apply_filters( 'super_register_login_before_verify_attachments_filter', array(), array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$message ) );

                        // @since 1.3.0 - custom reply to headers
                        if( !isset($settings['header_reply_enabled']) ) $settings['header_reply_enabled'] = false;
                        $reply = '';
                        $reply_name = '';
                        if( $settings['header_reply_enabled']==false ) {
                            $custom_reply = false;
                        }else{
                            $custom_reply = true;
                            if( !isset($settings['header_reply']) ) $settings['header_reply'] = '';
                            if( !isset($settings['header_reply_name']) ) $settings['header_reply_name'] = '';
                            $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply'], $data, $settings ) );
                            $reply_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply_name'], $data, $settings ) );
                        }

                        // Send the email
                        $message = apply_filters( 'super_before_sending_email_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
                        $mail = SUPER_Common::email( $user_email, $from, $from_name, $custom_reply, $reply, $reply_name, '', '', $subject, $message, $settings, $attachments );

                        // Return message
                        if( !empty( $mail->ErrorInfo ) ) {
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = $mail->ErrorInfo,
                                $redirect = null
                            );
                        }
                    }
                    
                    // @since 1.0.4
                    // Login the user without activating it's account
                    if( ($settings['register_login_activation']=='login') || ($settings['register_login_activation']=='verify_login') ) {
                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie( $user_id );
                        update_user_meta( $user_id, 'super_last_login', time() );
                    }

                    // Check if we let users automatically login after registering (instant login)
                    if( $settings['register_login_activation']=='auto' ) {
                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie( $user_id );
                        update_user_meta( $user_id, 'super_last_login', time() );
                        update_user_meta( $user_id, 'super_account_status', 1 );
                        update_user_meta( $user_id, 'super_user_login_status', 'active' );
                    }

                    // Check if automatically activate users
                    if( $settings['register_login_activation']=='activate' ) {
                        update_user_meta( $user_id, 'super_account_status', 1 );
                    }


                    // @since 1.1.0 - create multi-site
                    if( !isset($settings['register_login_multisite_enabled']) ) $settings['register_login_multisite_enabled'] = '';
                    if( $settings['register_login_multisite_enabled']=='true' ) {
                        $user = get_user_by( 'id', $user_id );
                        $domain = SUPER_Common::email_tags( $settings['register_login_multisite_domain'], $data, $settings, $user );
                        $path = SUPER_Common::email_tags( $settings['register_login_multisite_path'], $data, $settings, $user );
                        $title = SUPER_Common::email_tags( $settings['register_login_multisite_title'], $data, $settings, $user );
                        $site_id = SUPER_Common::email_tags( $settings['register_login_multisite_id'], $data, $settings, $user );
                        $site_meta = apply_filters( 'super_register_login_create_blog_site_meta', array(), $user_id, $meta_data, $atts, $settings );
                        $blog_id = wpmu_create_blog($domain, $path, $title, $user_id, $site_meta, $site_id);
                        if( is_wp_error( $blog_id ) ) {
                            $msg = $blog_id->get_error_message();
                            SUPER_Forms()->session->set( 'super_msg', array( 'data'=>$data, 'settings'=>$settings, 'msg'=>$msg, 'type'=>'error' ) );
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = $msg,
                                $redirect = null
                            );
                        }
                        global $current_site;
                        if( (!is_super_admin($user_id)) && (get_user_option('primary_blog', $user_id)==$current_site->blog_id) ) {
                            update_user_option( $user_id, 'primary_blog', $blog_id, true );
                        }
                        if( $settings['register_login_multisite_email']=='true' ) {
                            wpmu_welcome_notification( $blog_id, $user_id, $password, $title, array('public'=>1) );
                        }
                        do_action( 'super_register_login_after_create_blog', $blog_id );
                    }
                }
            }

            if( $settings['register_login_action']=='login' ) {

                // Before we proceed, lets check if we have at least a user_login or user_email and user_pass field
                if( ( !isset( $data['user_login'] ) ) || ( !isset( $data['user_pass'] ) ) ) {
                    $msg = sprintf( esc_html__( 'We couldn\'t find the %1$s or %2$s fields which are required in order to login a new user. Please %3$sedit%4$s your form and try again', 'super-forms' ), '<strong>user_login</strong>', '<strong>user_pass</strong>', '<a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">', '</a>' );
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }
                $username = sanitize_user( $data['user_login']['value'] );
                $password = $data['user_pass']['value'];
                $creds = array();
                $creds['user_login'] = $username;
                $creds['user_password'] = $password;
                $creds['remember'] = true;
                $user = wp_signon( $creds, false );
                if( !is_wp_error( $user ) ) {
                    $user_id = $user->ID;
                    $user = get_user_by( 'id', $user_id );
                    if( $user ) {

                        // First check if the user role is allowed to login
                        $allowed = false;

                        if( ( !isset( $settings['login_user_role'] ) ) || ( $settings['login_user_role']=='' ) ) {
                            $allowed = true;
                        }else{
                            $allowed = in_array( $user->roles[0], $settings['login_user_role'] );
                            if( in_array( '', $settings['login_user_role'] ) ) {
                                $allowed = true;
                            }
                        }                        

                        if( $allowed != true ) {
                            wp_logout();
                            $msg = esc_html__( 'You are not allowed to login!', 'super-forms' );
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = $msg,
                                $redirect = null
                            );
                        }

                        // Check if user has not activated their account yet
                        $activated = '';
                        $status = get_user_meta( $user_id, 'super_account_status', true ); // 0 = inactive, 1 = active
                        // Maybe this user was already registered before Super Forms was used, if so skip the test
                        if( ( !isset( $data['activation_code'] ) ) && ( $status==0 ) && ( $status!='' ) ) {
                            wp_logout();
                            $msg = sprintf( esc_html__( 'You haven\'t activated your account yet. Please check your email or click %shere%s to resend your activation email.', 'super-forms' ), '<a href="#" class="resend-code" data-form="' . absint( $atts['post']['form_id'] ) . '" data-user="' . $user->user_login . '">', '</a>' );
                            SUPER_Forms()->session->set( 'super_msg', array( 'data'=>$data, 'settings'=>$settings, 'msg'=>$msg, 'type'=>'error' ) );
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = $msg,
                                $redirect = $settings['register_login_url'] . '?code=[%20CODE%20]&user=' . $username
                            );
                        }

                        // Validate the activation code
                        if( isset( $data['activation_code'] ) ) {    
                            if( $status==0 ) {
                                $code = sanitize_text_field( $data['activation_code']['value'] );
                                $activation = get_user_meta( $user_id, 'super_account_activation', true );
                                if( $code==$activation ) {
                                    update_user_meta( $user_id, 'super_account_status', 1 ); // 0 = inactive, 1 = active
                                    delete_user_meta( $user_id, 'super_account_activation' );
                                    $activated = 'true';
                                }else{
                                    $activated = 'false';
                                }
                            }
                            if( $status==1 ) {
                                $activated = 'true';
                            }
                        }
                        $msg = '';
                        if( ( isset( $settings['register_welcome_back_msg'] ) ) && ( $settings['register_welcome_back_msg']!='' ) ) {
                            $msg = SUPER_Common::email_tags( $settings['register_welcome_back_msg'], $data, $settings, $user );
                        }
                        $error = false;

                        $redirect = get_site_url();
                        if( !empty( $settings['form_redirect_option'] ) ) {
                            if( $settings['form_redirect_option']=='page' ) {
                                $redirect = get_permalink( $settings['form_redirect_page'] );
                            }
                            if( $settings['form_redirect_option']=='custom' ) {
                                $redirect = SUPER_Common::email_tags( $settings['form_redirect'], $data, $settings, $user );
                            }
                        }
                        if( $activated=='false' ) {
                            wp_logout();
                            $msg = SUPER_Common::email_tags( $settings['register_incorrect_code_msg'], $data, $settings, $user );
                            $error = true;
                            $redirect = null;
                            SUPER_Common::output_message(
                                $error = $error,
                                $msg = $msg,
                                $redirect = $redirect
                            );
                        }else{
                            wp_set_current_user($user_id);
                            wp_set_auth_cookie($user_id);
                            if( $activated=='true' ) {
                                $msg = SUPER_Common::email_tags( $settings['register_account_activated_msg'], $data, $settings, $user );
                            }
                        }
                        SUPER_Forms()->session->set( 'super_msg', array( 'data'=>$data, 'settings'=>$settings, 'msg'=>$msg, 'type'=>'success' ) );
                        SUPER_Common::output_message(
                            $error = $error,
                            $msg = $msg,
                            $redirect = $redirect
                        );
                    }
                }else{
                    wp_logout();
                    if( count( $user->errors ) > 0 ) {
                        $errors = $user->errors;
                        $errors = array_values( $errors );
                        $errors = array_shift( $errors );
                        $msg = $errors[0];
                    }else{
                        $msg = sprintf( esc_html__( '%sError:%s Something went wrong while logging in, please try again', 'super-forms' ), '<strong>', '</strong>' );
                    }
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }
            }

            if( $settings['register_login_action']=='reset_password' ) {
   
                // Before we proceed, lets check if we have at least a user_email field
                if( !isset( $data['user_email'] ) ) {
                    $msg = sprintf( esc_html__( 'We couldn\'t find the %1$s field which is required in order to reset passwords. Please %2$sedit%3$s your form and try again', 'super-forms' ), '<strong>user_email</strong>', '<a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">', '</a>' );
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }

                // Sanitize the user email address
                $user_email = sanitize_email( $data['user_email']['value'] );
                
                // Try to find a user with this email address
                $user = get_user_by( 'email', $user_email );
                $msg = '';
                if( !$user ) {
                    if( ( isset( $settings['register_reset_password_not_exists_msg'] ) ) && ( $settings['register_reset_password_not_exists_msg']!='' ) ) {
                        $msg = SUPER_Common::email_tags( $settings['register_reset_password_not_exists_msg'], $data, $settings, $user );
                    }
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }

                // Disable the default lost password emails
                add_filter( 'send_password_change_email', '__return_false' );

                // Generate a new password for this user
                $password = wp_generate_password( 8, false );
                
                // Update the new password for this user
                $user_id = wp_update_user( array( 'ID' => $user->ID, 'user_pass' => $password ) );

                // Replace the email subject tags with the correct data
                $subject = SUPER_Common::email_tags( $settings['register_reset_password_subject'], $data, $settings, $user );

                // Replace the email body tags with the correct data
                $message = $settings['register_reset_password_email'];
                $message = str_replace( '{register_login_url}', $settings['register_login_url'], $message );
                $message = str_replace( '{register_generated_password}', $password, $message );
                $message = SUPER_Common::email_tags( $message, $data, $settings, $user );
                $message = nl2br( $message );
                $from = SUPER_Common::email_tags( $settings['header_from'], $data, $settings, $user );
                $from_name = SUPER_Common::email_tags( $settings['header_from_name'], $data, $settings, $user );
                $attachments = apply_filters( 'super_register_login_before_sending_reset_password_attachments_filter', array(), array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$message ) );

                // @since 1.3.0 - custom reply to headers
                if( !isset($settings['header_reply_enabled']) ) $settings['header_reply_enabled'] = false;
                $reply = '';
                $reply_name = '';
                if( $settings['header_reply_enabled']==false ) {
                    $custom_reply = false;
                }else{
                    $custom_reply = true;
                    if( !isset($settings['header_reply']) ) $settings['header_reply'] = '';
                    if( !isset($settings['header_reply_name']) ) $settings['header_reply_name'] = '';
                    $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply'], $data, $settings ) );
                    $reply_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply_name'], $data, $settings ) );
                }

                // Send the email
                $message = apply_filters( 'super_before_sending_email_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
                $mail = SUPER_Common::email( $user_email, $from, $from_name, $custom_reply, $reply, $reply_name, '', '', $subject, $message, $settings, $attachments );

                // Return message
                if( !empty( $mail->ErrorInfo ) ) {
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $mail->ErrorInfo,
                        $redirect = null
                    );
                }else{
                    $msg = '';
                    if( ( isset( $settings['register_reset_password_success_msg'] ) ) && ( $settings['register_reset_password_success_msg']!='' ) ) {
                        $msg = SUPER_Common::email_tags( $settings['register_reset_password_success_msg'], $data, $settings );
                    }
                    SUPER_Common::output_message(
                        $error = false,
                        $msg = $msg,
                        $redirect = null
                    );                    
                }
            }
        }


        /** 
         *  Resend activation code
         *
         *  @since      1.0.0
        */
        public static function resend_activation() {
            
            $data = $_REQUEST['data'];
            $username = sanitize_user( $data['username'] );
            $form_id = absint( $data['form'] );
            $user = get_user_by( 'login', $username );
            if( $user ) {
                $to = $user->user_email;
                $name = $user->display_name;
                $code = wp_generate_password( 8, false );
                $password = wp_generate_password();
                $user_id = wp_update_user( array( 'ID' => $user->ID, 'user_pass' => $password ) );
                update_user_meta( $user->ID, 'super_account_activation', $code );
                
                // Get the form settings, so we can setup the correct email message and subject
                if (method_exists('SUPER_Common','get_form_settings')) {
                    $settings = SUPER_Common::get_form_settings($form_id);
                }else{
                    $settings = get_post_meta(absint($form_id), '_super_form_settings', true);
                }

                // Replace email tags with correct data
                $subject = SUPER_Common::email_tags( $settings['register_activation_subject'], $data, $settings );
                $message = $settings['register_activation_email'];
                $message = str_replace( '{field_user_login}', $username, $message );
                $message = str_replace( '{user_login}', $username, $message );
                $message = str_replace( '{register_login_url}', $settings['register_login_url'], $message );
                $message = str_replace( '{register_activation_code}', $code, $message );
                $message = str_replace( '{register_generated_password}', $password, $message );
                $message = SUPER_Common::email_tags( $message, $data, $settings );
                $message = nl2br( $message );
                $from = SUPER_Common::email_tags( $settings['header_from'], $data, $settings );
                $from_name = SUPER_Common::email_tags( $settings['header_from_name'], $data, $settings );
                $attachments = apply_filters( 'super_register_login_before_resend_activation_attachments_filter', array(), array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$message ) );

                // @since 1.3.0 - custom reply to headers
                if( !isset($settings['header_reply_enabled']) ) $settings['header_reply_enabled'] = false;
                $reply = '';
                $reply_name = '';
                if( $settings['header_reply_enabled']==false ) {
                    $custom_reply = false;
                }else{
                    $custom_reply = true;
                    if( !isset($settings['header_reply']) ) $settings['header_reply'] = '';
                    if( !isset($settings['header_reply_name']) ) $settings['header_reply_name'] = '';
                    $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply'], $data, $settings ) );
                    $reply_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['header_reply_name'], $data, $settings ) );
                }

                // Send the email
                $message = apply_filters( 'super_before_sending_email_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
                $mail = SUPER_Common::email( $user_email, $from, $from_name, $custom_reply, $reply, $reply_name, '', '', $subject, $message, $settings, $attachments );

                // Return message
                if( !empty( $mail->ErrorInfo ) ) {
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $mail->ErrorInfo,
                        $redirect = null
                    );
                }else{
                    $msg = esc_html__( 'We have send you a new activation code, check your email to activate your account!', 'super-forms' );
                    SUPER_Common::output_message(
                        $error = false,
                        $msg = $msg,
                        $redirect = null
                    );                    
                }
            }
            die();
        }


        /** 
         *  Send email with activation code
         *
         *  Generates the random activation code and adds it to the users table during user registration.
         *
         *  @since      1.0.0
        */
        public static function new_user_notification( $user_id, $notify='', $data=null, $settings=null, $password=null, $method=null, $send_password=false ) {

            global $wpdb, $wp_hasher;
            
            do_action( 'super_before_new_user_notifcation_hook' );

            // Generate a code and save it for the user
            $code = wp_generate_password( 8 );
            $wpdb->update(
                $wpdb->users,
                array(
                    'super_activation_code' => $code,
                    'super_user_status' => '0'
                ),
                array(
                    'ID' => $user_id
                )
            );
            $user = get_userdata( $user_id );
            $user_login = $user->user_login; 
            $user_email = $user->user_email;

            do_action( 'super_after_new_user_notifcation_hook' );                        
        
        }

    }
        
endif;


/**
 * Returns the main instance of SUPER_Register_Login to prevent the need to use globals.
 *
 * @return SUPER_Register_Login
 */
if(!function_exists('SUPER_Register_Login')){
    function SUPER_Register_Login() {
        return SUPER_Register_Login::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['super_register_login'] = SUPER_Register_Login();
}