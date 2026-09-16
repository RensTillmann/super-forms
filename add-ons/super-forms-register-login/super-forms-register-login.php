<?php
/**
 * Super Forms - Register & Login
 *
 * @package   Super Forms - Register & Login
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Register & Login
 * Description: Makes it possible to let users register and login from the front-end
 * Version:     2.0.0
 * Plugin URI:  http://f4d.nl/super-forms
 * Author URI:  http://f4d.nl/super-forms
 * Author:      feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.7
 * Requires PHP:      8.2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists('SUPER_Register_Login') ) :


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
        public $version = '2.0.0';


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
         * Request-local authority for the deferred custom user-meta mutation.
         *
         * @var array|null
         */
        private static $deferred_user_action = null;

        /**
         * Whether request-end bridge cleanup has been registered.
         *
         * @var bool
         */
        private static $bridge_cleanup_registered = false;
        
        
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
            add_filter( 'super_submission_carrier_contracts_filter', array( $this, 'submission_carrier_contracts' ), 10, 2 );

            // Filters since 1.0.3
            add_filter( 'wp_authenticate_user', array( $this, 'check_user_login_status' ), 10, 2 );

            // Actions since 1.0.0
            add_action( 'wp_ajax_super_resend_activation', array( $this, 'resend_activation' ) );
            add_action( 'wp_ajax_nopriv_super_resend_activation', array( $this, 'resend_activation' ) );


            // Filters since 1.2.0
            add_filter( 'super_form_settings_filter', array( $this, 'set_get_values' ), 10, 2 );
            add_filter( 'super_countries_list_filter', array( $this, 'return_wc_countries' ), 10, 2 );

            if ( $this->is_request( 'frontend' ) ) {
            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
                add_filter( 'super_email_tags_filter', array( $this, 'add_email_tags' ), 10, 1 );

                add_action( 'show_user_profile', array( $this, 'add_customer_meta_fields' ) );
                add_action( 'edit_user_profile', array( $this, 'add_customer_meta_fields' ) );
                add_action( 'personal_options_update', array( $this, 'save_customer_meta_fields' ) );
                add_action( 'edit_user_profile_update', array( $this, 'save_customer_meta_fields' ) );
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
                    if( !isset($_GET[$k]) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- existence probe only, inside the read-only settings filter for the logged-in user's own `update` form prefill (super-forms-register-login.php:251); no request value is consumed, the assigned value comes from $current_user->data (super-forms-register-login.php:255)
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
            // Check if the login status of the user is pending or blocked
            $user_login_status = get_user_meta( $user->ID, 'super_user_login_status', true );
            if( ($user_login_status=='pending') || ($user_login_status=='blocked') ) {
                remove_action('authenticate', 'wp_authenticate_username_password', 20);
                $user = new WP_Error( 'account_not_active', sprintf( esc_html__( '%sERROR%s: You are not allowed to login.', 'super-forms' ), '<strong>', '</strong>' ) );
            }else{
                // Check if user has not activated their account yet
                $status = get_user_meta( $user->ID, 'super_account_status', true ); // 0 = inactive, 1 = active
                if( (!isset($_POST['action'])) || (isset($_POST['action']) && $_POST['action']!=='super_submit_form')){ // phpcs:ignore WordPress.Security.NonceVerification.Missing -- route detection only inside the core `authenticate` filter (SUPER_Register_Login::check_user_login_status(), super-forms-register-login.php:286), where WordPress core owns the login credential/nonce flow; the branch only decides whether to return a WP_Error (super-forms-register-login.php:296-299) and changes no state
                    if( $status!=1 && $status!=='' ) {
                        remove_action('authenticate', 'wp_authenticate_username_password', 20);
                        $user = new WP_Error( 'account_not_active', esc_html__( 'You haven\'t verified your email address yet. Please check your email!' ) );
                    }
                }
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
            // Don't show this option to the current user
            if(get_current_user_id()===$user->ID) return;
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
         * Only privileged actors may manage another user's login status.
         */
        private static function can_manage_user_login_status( $user_id ) {
            $user_id = absint( $user_id );
            $actor_id = get_current_user_id();
            return ($user_id!==0)
                && ($actor_id!==0)
                && ($actor_id!==$user_id)
                && current_user_can( 'edit_users' )
                && current_user_can( 'edit_user', $user_id );
        }


        /**
         * Save Address Fields on edit user pages.
         *
         * @param int $user_id User ID of the user being saved
         * @since      1.0.3
         */
        public function save_customer_meta_fields( $user_id ) {
            $user_id = absint( $user_id );
            if( !self::can_manage_user_login_status($user_id)
                || !isset($_POST['super_user_login_status']) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- guarded by SUPER_Register_Login::can_manage_user_login_status() (super-forms-register-login.php:375-383, edit_users + edit_user + no self-edit); the request nonce is verified by WordPress core (check_admin_referer('update-user_'.$user_id) in wp-admin/user-edit.php) before it fires personal_options_update / edit_user_profile_update (hooked at super-forms-register-login.php:203-204)
                || !is_string($_POST['super_user_login_status']) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- same capability guard SUPER_Register_Login::can_manage_user_login_status() (super-forms-register-login.php:375-383) plus core's update-user_{id} nonce check before the hook (super-forms-register-login.php:203-204)
                return;
            }
            $new_status = sanitize_text_field( wp_unslash( $_POST['super_user_login_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- same capability guard SUPER_Register_Login::can_manage_user_login_status() (super-forms-register-login.php:375-383) plus core's update-user_{id} nonce check before personal_options_update / edit_user_profile_update (super-forms-register-login.php:203-204); value is allowlisted at super-forms-register-login.php:400
            if( !in_array($new_status, array('active', 'pending', 'blocked'), true) ) {
                return;
            }

            // Get form data and settings
            $form_data = get_user_meta( $user_id, 'super_user_approve_data', true );
            if( ($form_data!='') && ($new_status==='active') ) {
                $settings = $form_data['settings'];
                $data = $form_data['data'];
                $user_status = get_user_meta( $user_id, 'super_user_login_status', true );
                if( $user_status!=='active' ) {
                    if( (!empty($settings['register_approve_subject'])) && (!empty($settings['register_approve_email'])) ) {
                        $user = get_user_by( 'ID', $user_id );
                        if( $user ) {
                            $password = '';
                            $mail = self::send_approve_email(array('password'=>$password, 'code'=>$code, 'user'=>$user, 'settings'=>$settings, 'data'=>$data));
                            // After email is send, delete the email and subject (remove the password from database for security reasons)
                            if( empty( $mail->ErrorInfo ) ) {
                                if( !self::can_manage_user_login_status($user_id) ) {
                                    return;
                                }
                                delete_user_meta( $user_id, 'super_user_approve_data' );
                            }
                        }
                    }
                }
            }
            if( !self::can_manage_user_login_status($user_id) ) {
                return;
            }
            update_user_meta( $user_id, 'super_user_login_status', $new_status );
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
         * Mirror the activation-code element render contract when rebuilding
         * submission carriers from the stored form tree.
         */
        private static function activation_code_render_value() {
            if( ( SUPER_Forms::is_request( 'frontend' ) ) && ( isset( $_GET['code'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- emailed activation URL: ?code= is itself the bearer credential and cannot carry a nonce; it is only mirrored into the render contract here and re-validated on submit against the session-bound render proof SUPER_Register_Login::activation_code_render_proof_presented() (super-forms-register-login.php:554-557)
                return sanitize_text_field( wp_unslash( $_GET['code'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- same emailed activation URL credential; no state is changed here and the value is re-validated on submit via SUPER_Register_Login::activation_code_render_proof_presented() (super-forms-register-login.php:554-557)
            }
            if ( SUPER_Forms::is_request( 'admin' ) ) {
                $code = '';
                // If switching between language
                if(isset($_POST['i18n']) && isset($_GET['code']) && isset($_POST['action']) && $_POST['action']==='super_language_switcher'){ // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended -- route detection only: SUPER_Ajax::language_switcher() is CSRF-verified by SUPER_Common::verifyCSRF() (includes/class-common.php:785) at includes/class-ajax.php:421 and aborts with output_message() before any state change
                    $code = sanitize_text_field( wp_unslash( $_GET['code'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- same CSRF-verified super_language_switcher route (SUPER_Common::verifyCSRF(), includes/class-common.php:785, enforced at includes/class-ajax.php:421); the value only mirrors the rendered activation-code field
                }
                return $code;
            }
            return false;
        }
        /**
         * Renderer-issued proof that the conditional activation-code field was
         * actually presented in a legitimately rendered form. Mirrors the public
         * populate/print capability pattern: a session-bound grant is written when
         * the field renders and verified on submit, so the carrier is never
         * admitted from an unproven client payload.
         */
        private static function activation_code_render_proof_name( $form_id ) {
            return 'activation_code_presented_' . absint($form_id);
        }
        private static function issue_activation_code_render_proof( $form_id ) {
            $form_id = absint($form_id);
            if( $form_id===0 || SUPER_Common::uses_legacy_sessionless_mode() ) {
                // Fail closed in legacy sessionless mode: without a persisted
                // browser session the proof would become an unbound bearer token.
                return false;
            }
            $grant = SUPER_Common::current_entry_update_grant_value( true );
            if( !is_array($grant) ) {
                return false;
            }
            SUPER_Common::setClientData( array(
                'name' => self::activation_code_render_proof_name( $form_id ),
                'value' => $grant,
                'force' => true,
            ) );
            return self::activation_code_render_proof_presented( $form_id );
        }
        private static function activation_code_render_proof_presented( $form_id ) {
            $form_id = absint($form_id);
            if( $form_id===0 ) {
                return false;
            }
            $grant = SUPER_Common::getClientData(
                self::activation_code_render_proof_name( $form_id ),
                false
            );
            return SUPER_Common::entry_update_grant_matches_current( $grant );
        }
        /**
         * Handle the Activation Code element output
         *
         *  @since      1.0.0
        */
        public static function activation_code($x) {
            extract($x); // $tag, $atts, $inner, $shortcodes=null, $settings=null
            $code = self::activation_code_render_value();
            if( $code!==false ) {
                $atts['name'] = 'activation_code';
                $proof_form_id = class_exists('SUPER_Shortcodes') ? absint( SUPER_Shortcodes::$current_form_id ) : 0;
                if( $proof_form_id!==0 ) {
                    self::issue_activation_code_render_proof( $proof_form_id );
                }
                $result = SUPER_Shortcodes::opening_tag( $tag, $atts );
                $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
                $result .= '<input class="super-shortcode-field" type="text"';
                $result .= ' name="' . esc_attr($atts['name']) . '" value="' . esc_attr($code) . '"';
                $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
                $result .= ' />';
                $result .= '</div>';
                $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag );
                $result .= '</div>';
                return $result;
            }

        }
        public function submission_carrier_contracts( $contracts, $atts ) {
            if( !is_array($contracts) ) {
                $contracts = array();
            }
            if( !is_array($atts) || empty($atts['tag']) || $atts['tag']!=='activation_code' ) {
                return $contracts;
            }
            // Admit the conditional activation-code carrier only when the field was
            // actually presented: either the activation URL rendered it (`?code=`)
            // or the renderer issued a session-bound presentation proof. Never
            // derive contract keys from the client payload; the shared submission
            // route matcher resolves any repeater ordinal (e.g. `activation_code_2`)
            // from the single stored base key.
            $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- runs only inside the CSRF-verified submit route: SUPER_Common::verifyCSRF() (includes/class-common.php:785) gates SUPER_Ajax::submit_form() (includes/class-ajax.php:8399) before it applies super_submission_carrier_contracts_filter (includes/class-ajax.php:3297,3644); the value is coerced with absint()
            if( self::activation_code_render_value()===false
                && !self::activation_code_render_proof_presented( $form_id ) ) {
                return $contracts;
            }
            $meta = array( 'type' => 'var' );
            $repeater_depth = isset($atts['repeater_depth']) ? absint($atts['repeater_depth']) : 0;
            if( $repeater_depth>0 ) {
                $meta['nested_repeater_suffix_depth'] = max( 0, $repeater_depth-1 );
                $meta['repeatable'] = true;
            }
            $contracts['activation_code'] = $meta;
            return $contracts;
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
        public static function add_settings( $array, $x ) {
            $default = $x['default'];
            $settings = $x['settings'];
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
                        'default' =>  'none',
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
                    'register_custom_email_header' => array(
                        'name'=> esc_html__( 'E-mail headers', 'super-forms' ),
                        'label' => sprintf( esc_html__( 'Inherit headers from your Admin or Confirmation email settings.%1$s%2$sNote:%3$s you must define custom headers in case you are not sending Admin or Confirmation emails.', 'super-forms' ), '<br />', '<strong>', '</strong>' ),
                        'default' =>  'admin',
                        'type'=>'select',
                        'values'=>array(
                            'custom' => esc_html__(  'Use custom headers', 'super-forms' ),
                            'admin' => esc_html__(  'Use headers defined for Admin emails (default)', 'super-forms' ),
                            'confirmation' => esc_html__(  'Use headers defined for Confirmation emails', 'super-forms' )
                        ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register,login,reset_password',
                    ),
                    'register_header_from_type' => array(
                        'name'=> esc_html__( 'Send email from:', 'super-forms' ),
                        'desc' => esc_html__( 'Enter a custom email address or use the blog settings', 'super-forms' ),
                        'default' =>  '{option_admin_email}',
                        'type'=>'select',
                        'values'=>array(
                            'default' => esc_html__(  'Default blog email and name', 'super-forms' ),
                            'custom' => esc_html__(  'Custom from', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_custom_email_header',
                        'filter_value' => 'custom'
                    ),
                    'register_header_from' => array(
                        'name' => esc_html__( 'From email:', 'super-forms' ),
                        'desc' => esc_html__( 'Example: info@companyname.com', 'super-forms' ),
                        'default' =>  '{option_admin_email}',
                        'placeholder' => esc_html__( 'Company Email Address', 'super-forms' ),
                        'filter'=>true,
                        'parent'=>'register_header_from_type',
                        'filter_value'=>'custom',
                    ),
                    'register_header_from_name' => array(
                        'name' => esc_html__( 'From name:', 'super-forms' ),
                        'desc' => esc_html__( 'Example: Company Name', 'super-forms' ),
                        'default' =>  '{option_blogname}',
                        'placeholder' => esc_html__( 'Your Company Name', 'super-forms' ),
                        'filter'=>true,
                        'parent'=>'register_header_from_type',
                        'filter_value'=>'custom',
                    ),
                    'register_header_reply_enabled' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( '(optional) Set a custom reply to header', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_custom_email_header',
                        'filter_value' => 'custom'
                    ),
                    'register_header_reply' => array(
                        'name' => esc_html__( 'Reply to email:', 'super-forms' ),
                        'desc' => esc_html__( 'Example: no-reply@companyname.com', 'super-forms' ),
                        'default' =>  '{option_admin_email}',
                        'placeholder' => esc_html__( 'Company Email Address', 'super-forms' ),
                        'filter'=>true,
                        'parent'=>'register_header_reply_enabled',
                        'filter_value'=>'true',
                    ),
                    'register_header_reply_name' => array(
                        'name' => esc_html__( 'Reply to name:', 'super-forms' ),
                        'desc' => esc_html__( 'Example: Company Name', 'super-forms' ),
                        'default' =>  '{option_blogname}',
                        'placeholder' => esc_html__( 'Your Company Name', 'super-forms' ),
                        'filter'=>true,
                        'parent'=>'register_header_reply_enabled',
                        'filter_value'=>'true',
                    ),

                    // @since 1.4.0 - option to register new user if user doesn't exists while updating user
                    'register_login_register_not_logged_in' => array(
                        'default' =>  '',
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
                        'default' =>  '',
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
                        'default' =>  '',
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
                        'label' => esc_html__( 'Which user roles are allowed to login?', 'super-forms' ),
                        'type' => 'select',
                        'multiple' => true,
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'login',
                        'values' => $roles,
                    ),
                    'register_user_role' => array(
                        'name' => esc_html__( 'User role', 'super-forms' ),
                        'label' => esc_html__( 'What user role should this user get?', 'super-forms' ),
                        'type' => 'select',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register,update',
                        'values' => array_merge($roles, array('_super_keep_existing_role' => esc_html__( 'Keep existing role (only use this when updating existing user)', 'super-forms' ))),
                    ),
                    'register_login_activation' => array(
                        'name' => esc_html__( 'Send email confirmation/verification email', 'super-forms' ),
                        'label' => esc_html__( 'Optionally let users verify their account or let them instantly login without verification', 'super-forms' ),
                        'type' => 'select',
                        'default' =>  'verify',
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register',
                        'values' => array(
                            'verify' => esc_html__( 'Send verification email (default)', 'super-forms' ),
                            'verify_login' => esc_html__( 'Send verification email and automatically login', 'super-forms' ),
                            'auto' => esc_html__( 'No verification required and login automatically', 'super-forms' ),
                            'activate' => esc_html__( 'No verification required and do not automatically login either', 'super-forms' ),
                            'none' => esc_html__( 'Do nothing (don\'t login nor send verification email)', 'super-forms' ),
                        ),
                    ),
                    'register_login_url' => array(
                        'name' => esc_html__( 'Login page URL', 'super-forms' ),
                        'label' => esc_html__( 'URL of your login page where you placed the login form, here users can verify their email address', 'super-forms' ),
                        'default' =>  get_site_url() . '/login/',
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register,login,reset_password,update',
                        'allow_empty' => true,
                    ),
                    'register_welcome_back_msg' => array(
                        'name' => esc_html__( 'Welcome back message', 'super-forms' ),
                        'label' => esc_html__( 'Display a welcome message after user has logged in (leave blank for no message)', 'super-forms' ),
                        'default' =>  esc_html__( 'Welcome back {user_login}!', 'super-forms' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'login',
                        'allow_empty' => true,
                    ),
                    'register_incorrect_code_msg' => array(
                        'name' => esc_html__( 'Incorrect activation code message', 'super-forms' ),
                        'label' => esc_html__( 'Display a message when the activation code is incorrect', 'super-forms' ),
                        'default' =>  esc_html__( 'The combination username, password and activation code is incorrect!', 'super-forms' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'login',
                        'allow_empty' => true,
                    ),
                    'register_account_activated_msg' => array(
                        'name' => esc_html__( 'Account verified message', 'super-forms' ),
                        'label' => esc_html__( 'Display a message when account has been verified', 'super-forms' ),
                        'default' =>  esc_html__( 'Hello {user_login}, your account has been verified!', 'super-forms' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'login',
                        'allow_empty' => true,
                    ),
                    'register_activation_subject' => array(
                        'name' => esc_html__( 'Verification E-mail Subject', 'super-forms' ),
                        'label' => esc_html__( 'Example: Verify your account', 'super-forms' ),
                        'default' =>  esc_html__( 'Verify your account', 'super-forms' ),
                        'filter' => true,
                        'parent' => 'register_login_activation',
                        'filter_value' => 'verify,verify_login',
                        'allow_empty' => true,
                    ),
                    'register_activation_email' => array(
                        'name' => esc_html__( 'Activation E-mail Body', 'super-forms' ),
                        'label' => esc_html__( 'The email message. You can use {activation_code} and {register_login_url}', 'super-forms' ),
                        'type' => 'textarea',
                        'default' =>  sprintf( esc_html__( 'Dear {user_login},%1$s%1$sThank you for registering! Before you can login you will need to verify your account.%1$sBelow you will find your activation code. You need this code to verify your account:%1$s%1$sActivation Code: %2$s{register_activation_code}%3$s%1$s%1$sClick %4$shere%5$s to verify your account with the provided code.%1$s%1$s%1$sBest regards,%1$s%1$s{option_blogname}', 'super-forms' ), '<br />', '<strong>', '</strong>', '<a href="{register_login_url}?code={register_activation_code}">', '</a>' ),
                        'filter' => true,
                        'parent' => 'register_login_activation',
                        'filter_value' => 'verify,verify_login',
                        'allow_empty' => true,
                    ),
                    'register_login_show_toolbar' => array(
                        'default' =>  'true',
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
                        'default' =>  '',
                        'placeholder' => "field_name|meta_key\nbilling_first_name|billing_first_name\nbilling_last_name|billing_last_name\nbilling_company|billing_company\nbilling_address_1|billing_address_1\nbilling_address_2|billing_address_2\nbilling_city|billing_city\nbilling_postcode|billing_postcode\nbilling_country|billing_country\nbilling_state|billing_state\nbilling_phone|billing_phone\nbilling_email|billing_email\nshipping_first_name|shipping_first_name\nshipping_last_name|shipping_last_name\nshipping_company|shipping_company\nshipping_address_1|shipping_address_1\nshipping_address_2|shipping_address_2\nshipping_city|shipping_city\nshipping_postcode|shipping_postcode\nshipping_country|shipping_country\nshipping_state|shipping_state",
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'register',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_enabled' => array(
                        'desc' => esc_html__( 'This will create a new site within your wordpress site network', 'super-forms' ),
                        'default' =>  '',
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
                        'default' =>  '{user_email}',
                        'filter' => true,
                        'parent' => 'register_login_multisite_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_path' => array(
                        'name' => esc_html__( 'Path to the blog', 'super-forms' ),
                        'label' => esc_html__( 'Default: None', 'super-forms' ),
                        'default' =>  '{user_email}',
                        'filter' => true,
                        'parent' => 'register_login_multisite_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_title' => array(
                        'name' => esc_html__( 'Title for blog', 'super-forms' ),
                        'label' => esc_html__( 'Default: None', 'super-forms' ),
                        'default' =>  '{user_email}',
                        'filter' => true,
                        'parent' => 'register_login_multisite_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_id' => array(
                        'name' => esc_html__( 'Site ID, if running multiple networks', 'super-forms' ),
                        'label' => esc_html__( 'Default: 1', 'super-forms' ),
                        'default' =>  '1',
                        'filter' => true,
                        'parent' => 'register_login_multisite_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_login_multisite_email' => array(
                        'default' =>  'true',
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
                        'label' => esc_html__( 'Display a message after user has reset their password (leave blank for no message)', 'super-forms' ),
                        'default' =>  esc_html__( 'Your password has been reset. We have just send you a new password to your email address.', 'super-forms' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'reset_password',
                        'allow_empty' => true,
                    ),
                    'register_reset_password_not_exists_msg' => array(
                        'name' => esc_html__( 'Not found message', 'super-forms' ),
                        'label' => esc_html__( 'Display a message when no user was found (leave blank for no message)', 'super-forms' ),
                        'default' =>  esc_html__( 'We couldn\'t find a user with the given email address!', 'super-forms' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'reset_password',
                        'allow_empty' => true,
                    ),
                    'register_reset_password_subject' => array(
                        'name' => esc_html__( 'Lost Password E-mail Subject', 'super-forms' ),
                        'label' => esc_html__( 'Example: Your new password. You can use {user_login}', 'super-forms' ),
                        'default' =>  esc_html__( 'Your new password', 'super-forms' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'reset_password',
                        'allow_empty' => true,
                    ),
                    'register_reset_password_email' => array(
                        'name' => esc_html__( 'Lost Password E-mail Body', 'super-forms' ),
                        'label' => esc_html__( 'The email message. You can use {user_login}, {register_generated_password} and {register_login_url}', 'super-forms' ),
                        'type' => 'textarea',
                        'default' =>  sprintf( 
                            esc_html__( 
                                'Dear {user_login},%1$s%1$sYou just requested to reset your password.%1$sUsername: %2$s{user_login}%3$s%1$sPassword: %2$s{register_generated_password}%3$s%1$s%1$sClick %4$shere%5$s to login with your new password.%1$s%1$s%1$sBest regards,%1$s%1$s{option_blogname}', 'super-forms' ), "\n", '<strong>', '</strong>', '<a href="{register_login_url}">', '</a>' ),
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'reset_password',
                        'allow_empty' => true,
                    ),

                    // @since 1.2.0 - not logged in user for when we are updating user data
                    'register_login_not_logged_in_msg' => array(
                        'name' => esc_html__( 'Not logged in message (leave blank for no message)', 'super-forms' ),
                        'label' => esc_html__( 'Display a message when no user is logged in', 'super-forms' ),
                        'type' => 'textarea',
                        'default' =>  sprintf( esc_html__( 'You must be logged in to submit this form. Click %shere%s to login!', 'super-forms' ), '<a href="{register_login_url}">', '</a>' ),
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
                        'default' =>  "billing_first_name|billing_first_name\nbilling_last_name|billing_last_name\nbilling_company|billing_company\nbilling_address_1|billing_address_1\nbilling_address_2|billing_address_2\nbilling_city|billing_city\nbilling_postcode|billing_postcode\nbilling_country|billing_country\nbilling_state|billing_state\nbilling_phone|billing_phone\nbilling_email|billing_email\nshipping_first_name|shipping_first_name\nshipping_last_name|shipping_last_name\nshipping_company|shipping_company\nshipping_address_1|shipping_address_1\nshipping_address_2|shipping_address_2\nshipping_city|shipping_city\nshipping_postcode|shipping_postcode\nshipping_country|shipping_country\nshipping_state|shipping_state",
                        'filter' => true,
                        'parent' => 'register_login_action',
                        'filter_value' => 'update',
                        'allow_empty' => true,
                    ),
                    'register_user_signup_status' => array(
                        'name' => esc_html__( 'User login status after registration', 'super-forms' ),
                        'label' => esc_html__( 'Only set this to "Pending" if you wish to manually verify registrations of users.', 'super-forms' ),
                        'type' => 'select',
                        'default' =>  'active',
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
                        'default' =>  '',
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Send approve email when account is activated by admin', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_user_signup_status',
                        'filter_value' => 'pending,blocked'
                    ),
                    'register_approve_subject' => array(
                        'name' => esc_html__( 'Approved E-mail Subject', 'super-forms' ),
                        'label' => esc_html__( 'Example: Your account has been approved', 'super-forms' ),
                        'default' =>  esc_html__( 'Account has been approved', 'super-forms' ),
                        'filter' => true,
                        'parent' => 'register_send_approve_email',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_approve_email' => array(
                        'name' => esc_html__( 'Approved E-mail Body', 'super-forms' ),
                        'label' => esc_html__( 'The email message.', 'super-forms' ),
                        'type' => 'textarea',
                        'default' =>  sprintf( esc_html__( 'Dear {user_login},%1$s%1$sYour account has been approved and can now be used!%1$s%1$sUsername: %2$s{user_login}%3$s%1$sPassword: %2$s{user_pass}%3$s%1$s%1$sClick %4$shere%5$s to login into your account.%1$s%1$s%1$sBest regards,%1$s%1$s{option_blogname}', 'super-forms' ), '<br />', '<strong>', '</strong>', '<a href="{register_login_url}">', '</a>' ),
                        'filter' => true,
                        'parent' => 'register_send_approve_email',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'register_approve_generate_pass' => array(
                        'desc' => esc_html__( 'This will generate a new password as soon as the user account has been approved', 'super-forms' ),
                        'label' => esc_html__( 'You can retrieve the generated password with {register_generated_password} in the email', 'super-forms' ),
                        'default' =>  '',
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Generate new password on the fly when sending approve email', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'register_send_approve_email',
                        'filter_value' => 'true'
                    ),
                )
            );
            return $array;
        }


        /**
         * Clear both request-local mutation authority and the legacy contact-author value.
         */
        private static function clear_user_meta_bridge() {
            self::$deferred_user_action = null;
            SUPER_Common::setClientData( array( 'name'=> 'update_user_meta', 'value'=>false ) );
        }


        /**
         * Return a stable identity for the submitted request.
         */
        private static function get_request_fingerprint( $post ) {
            if( !is_array($post) ) {
                return false;
            }
            return hash( 'sha256', serialize($post) );
        }


        /**
         * Build request-local authority for one exact account action.
         */
        private static function build_user_action_context( $post, $actor_id, $target_id, $action, $meta_mapping ) {
            $fingerprint = self::get_request_fingerprint( $post );
            $actor_id = absint( $actor_id );
            $target_id = absint( $target_id );
            if( ($fingerprint===false) || ($target_id===0) || (!in_array($action, array('register', 'update'), true)) || (!is_array($meta_mapping))
                || (($action==='update') && ($actor_id===0)) ) {
                return false;
            }
            foreach( $meta_mapping as $mapping ) {
                if( !is_array($mapping) || !isset($mapping['source'], $mapping['meta_key'])
                    || !is_string($mapping['source']) || !is_string($mapping['meta_key'])
                    || ($mapping['source']==='') || ($mapping['meta_key']==='')
                    || self::is_protected_user_meta_key($mapping['meta_key']) ) {
                    return false;
                }
            }
            return array(
                'request' => $fingerprint,
                'actor' => $actor_id,
                'target' => $target_id,
                'action' => $action,
                'meta_mapping' => $meta_mapping,
            );
        }


        /**
         * Publish one deferred action and expose only its target to the legacy
         * contact-entry author bridge.
         */
        private static function set_deferred_user_action( $context ) {
            if( !is_array($context) || !isset($context['request'], $context['actor'], $context['target'], $context['action'], $context['meta_mapping'])
                || !is_string($context['request']) || !preg_match('/^[a-f0-9]{64}$/D', $context['request'])
                || (absint($context['target'])===0) || !in_array($context['action'], array('register', 'update'), true)
                || (($context['action']==='update') && (absint($context['actor'])===0)) || !is_array($context['meta_mapping']) ) {
                self::clear_user_meta_bridge();
                return false;
            }
            foreach( $context['meta_mapping'] as $mapping ) {
                if( !is_array($mapping) || !isset($mapping['source'], $mapping['meta_key'])
                    || !is_string($mapping['source']) || !is_string($mapping['meta_key'])
                    || ($mapping['source']==='') || ($mapping['meta_key']==='')
                    || self::is_protected_user_meta_key($mapping['meta_key']) ) {
                    self::clear_user_meta_bridge();
                    return false;
                }
            }
            self::$deferred_user_action = $context;
            if( self::$bridge_cleanup_registered===false ) {
                self::$bridge_cleanup_registered = true;
                register_shutdown_function( function() {
                    self::clear_user_meta_bridge();
                } );
            }
            SUPER_Common::setClientData( array( 'name'=> 'update_user_meta', 'value'=>$context['target'] ) );
            return true;
        }


        /**
         * Consume deferred authority once, clearing the persisted compatibility
         * value before any validation or mutation.
         */
        private static function consume_deferred_user_action( $post ) {
            $context = self::$deferred_user_action;
            self::clear_user_meta_bridge();
            if( !is_array($context) || !self::user_action_context_is_authorized($context, $post) ) {
                return false;
            }
            return $context;
        }


        /**
         * Check whether the current actor may update the requested user.
         *
         * Self-updates are allowed. Updating another user requires WordPress'
         * target-aware edit_user capability check.
         */
        private static function can_update_user( $actor_id, $target_id ) {
            $actor_id = absint( $actor_id );
            $target_id = absint( $target_id );
            if( ($actor_id===0) || ($target_id===0) || (get_userdata( $target_id )===false) ) {
                return false;
            }
            if( $actor_id===$target_id ) {
                return true;
            }
            return current_user_can( 'edit_user', $target_id );
        }
        /**
         * Bind pending verification retries to the original form/account/session issuer.
         */
        private static function pending_registration_recovery_name( $form_id, $user_login, $user_email ) {
            return 'pending_registration_' . hash(
                'sha256',
                absint($form_id) . "\n" . strtolower(trim((string) $user_login)) . "\n" . strtolower(trim((string) $user_email))
            );
        }

        private static function issue_pending_registration_recovery( $user_id, $form_id, $user_login, $user_email ) {
            $user_id = absint($user_id);
            $form_id = absint($form_id);
            $user_login = sanitize_user($user_login);
            $user_email = sanitize_email($user_email);
            // Fail closed in legacy sessionless mode: without a persisted browser
            // session the recovery token would degrade into an unbound bearer token.
            if( SUPER_Common::uses_legacy_sessionless_mode() ) {
                return false;
            }
            $grant = SUPER_Common::current_entry_update_grant_value(true);
            $token = class_exists('SUPER_Forms') ? SUPER_Forms::generate_secure_hex(32) : false;
            if( $user_id===0 || $form_id===0 || $user_login==='' || $user_email==='' || !is_array($grant)
                || !is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token)!==1 ) {
                return false;
            }
            $payload = array(
                'version' => 1,
                'user_id' => $user_id,
                'form_id' => $form_id,
                'user_login' => $user_login,
                'user_email' => $user_email,
                'token_hash' => hash('sha256', $token),
                'expires' => time() + DAY_IN_SECONDS,
                'grant' => $grant,
            );
            update_user_meta( $user_id, 'super_pending_registration_recovery', $payload );
            SUPER_Common::setClientData( array(
                'name' => self::pending_registration_recovery_name( $form_id, $user_login, $user_email ),
                'value' => $token,
                'force' => true,
                'expires' => DAY_IN_SECONDS,
                'exp_var' => 12 * HOUR_IN_SECONDS,
            ) );
            return get_user_meta( $user_id, 'super_pending_registration_recovery', true ) === $payload;
        }

        private static function pending_registration_recovery_token( $form_id, $user_login, $user_email ) {
            $token = SUPER_Common::getClientData(
                self::pending_registration_recovery_name( $form_id, $user_login, $user_email ),
                false
            );
            return ( is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token)===1 ) ? $token : '';
        }
        private static function pending_registration_recovery_matches_account( $payload, $user_id, $form_id, $user_login, $user_email ) {
            return is_array($payload)
                && isset($payload['version'], $payload['user_id'], $payload['form_id'], $payload['user_login'], $payload['user_email'], $payload['token_hash'], $payload['expires'], $payload['grant'])
                && $payload['version']===1
                && absint($payload['user_id'])===absint($user_id)
                && absint($payload['form_id'])===absint($form_id)
                && is_string($payload['user_login'])
                && is_string($payload['user_email'])
                && is_string($payload['token_hash'])
                && preg_match('/^[a-f0-9]{64}$/D', $payload['token_hash'])===1
                && absint($payload['expires'])>=time()
                && $payload['user_login']===sanitize_user($user_login)
                && $payload['user_email']===sanitize_email($user_email)
                && is_array($payload['grant'])
                && isset($payload['grant']['version'], $payload['grant']['actor_id'], $payload['grant']['browser_session_hash'], $payload['grant']['user_session_hash'])
                && $payload['grant']['version']===1
                && is_string($payload['grant']['browser_session_hash'])
                && ( $payload['grant']['browser_session_hash']==='' || preg_match('/^[a-f0-9]{64}$/D', $payload['grant']['browser_session_hash'])===1 )
                && is_string($payload['grant']['user_session_hash'])
                && ( $payload['grant']['user_session_hash']==='' || preg_match('/^[a-f0-9]{64}$/D', $payload['grant']['user_session_hash'])===1 )
                && ( $payload['grant']['browser_session_hash']!=='' || $payload['grant']['user_session_hash']!=='' );
        }
        private static function pending_registration_recovery_matches_current( $payload, $user_id, $form_id, $user_login, $user_email ) {
            if( !self::pending_registration_recovery_matches_account( $payload, $user_id, $form_id, $user_login, $user_email )
                || !SUPER_Common::entry_update_grant_matches_current($payload['grant']) ) {
                return false;
            }
            $token = self::pending_registration_recovery_token( $form_id, $user_login, $user_email );
            return $token!==''
                && hash_equals($payload['token_hash'], hash('sha256', $token));
        }

        private static function maybe_resume_pending_registration( $user, $form_id, $settings, $data ) {
            if( !($user instanceof WP_User) ) {
                return false;
            }
            $form_id = absint($form_id);
            $user_login = isset($data['user_login']['value']) ? sanitize_user($data['user_login']['value']) : $user->user_login;
            $user_email = isset($data['user_email']['value']) ? sanitize_email($data['user_email']['value']) : $user->user_email;
            $payload = get_user_meta( $user->ID, 'super_pending_registration_recovery', true );
            if( !self::pending_registration_recovery_matches_current( $payload, $user->ID, $form_id, $user_login, $user_email ) ) {
                return false;
            }
            $code = get_user_meta( $user->ID, 'super_account_activation', true );
            if( !is_string($code) || $code==='' ) {
                return false;
            }
            $mail = self::send_verification_email(array('password'=>'', 'code'=>$code, 'user'=>$user, 'settings'=>$settings, 'data'=>$data));
            if( !empty( $mail->ErrorInfo ) ) {
                SUPER_Common::output_message(
                    $error = true,
                    $msg = $mail->ErrorInfo,
                    $redirect = null
                );
            }
            self::issue_pending_registration_recovery( $user->ID, $form_id, $user_login, $user_email );
            SUPER_Common::output_message(
                $error = false,
                $msg = esc_html__( 'We have send you a new verification code, check your email to verify your account!', 'super-forms' ),
                $redirect = null
            );
        }
        private static function resend_activation_rate_limit_key( $user_id, $user_email ) {
            return 'super_resend_activation_' . hash(
                'sha256',
                absint($user_id) . "\n" . strtolower(trim((string) $user_email))
            );
        }
        private static function resend_activation_daily_limit_key( $user_id ) {
            return 'super_resend_activation_daily_' . hash(
                'sha256',
                absint($user_id) . "\n" . gmdate('Ymd')
            );
        }
        private static function resend_activation_daily_limit_ttl() {
            $expires = gmmktime(
                0,
                0,
                0,
                (int) gmdate('n'),
                (int) gmdate('j') + 1,
                (int) gmdate('Y')
            ) - time();
            return ( $expires>0 ) ? $expires : DAY_IN_SECONDS;
        }
        private static function resend_activation_request_nonce() {
            return wp_create_nonce('super_resend_activation');
        }
        private static function resend_activation_form_settings( $form_id ) {
            $form_id = absint($form_id);
            if( $form_id===0 ) {
                return false;
            }
            $form = get_post($form_id);
            if( !($form instanceof WP_Post)
                || $form->post_type!=='super_form'
                || $form->post_status!=='publish' ) {
                return false;
            }
            if (method_exists('SUPER_Common','get_form_settings')) {
                $settings = SUPER_Common::get_form_settings($form_id);
            }else{
                $settings = get_post_meta($form_id, '_super_form_settings', true);
            }
            if( !is_array($settings)
                || empty($settings['register_login_action'])
                || $settings['register_login_action']!=='register' ) {
                return false;
            }
            $activation = isset($settings['register_login_activation']) && is_string($settings['register_login_activation'])
                ? $settings['register_login_activation']
                : 'verify';
            if( !in_array($activation, array('verify', 'verify_login'), true) ) {
                return false;
            }
            $settings['register_login_activation'] = $activation;
            return $settings;
        }

        private static function resend_activation_requested_user( $user_login, $user_email, $form_id ) {
            $user_login = sanitize_user($user_login);
            $user_email = sanitize_email($user_email);
            $form_id = absint($form_id);
            if( $user_login===''
                || $user_email===''
                || $form_id===0 ) {
                return false;
            }
            $login_user = get_user_by( 'login', $user_login );
            $email_user = get_user_by( 'email', $user_email );
            if( !($login_user instanceof WP_User)
                || !($email_user instanceof WP_User)
                || absint($login_user->ID)!==absint($email_user->ID)
                || !hash_equals( sanitize_user($login_user->user_login), $user_login )
                || !hash_equals( sanitize_email($login_user->user_email), $user_email ) ) {
                return false;
            }
            $payload = get_user_meta( $login_user->ID, 'super_pending_registration_recovery', true );
            if( !self::pending_registration_recovery_matches_current( $payload, $login_user->ID, $form_id, $user_login, $user_email ) ) {
                return false;
            }
            return $login_user;
        }

        private static function maybe_resend_pending_activation_email( $user, $form_id, $settings ) {
            if( !($user instanceof WP_User) ) {
                return false;
            }
            $form_id = absint($form_id);
            $user_email = sanitize_email($user->user_email);
            $account_status = get_user_meta( $user->ID, 'super_account_status', true );
            $code = get_user_meta( $user->ID, 'super_account_activation', true );
            if( $form_id===0
                || !is_array($settings)
                || $user_email===''
                || $account_status===1
                || $account_status==='1'
                || !is_string($code)
                || $code==='' ) {
                return false;
            }
            $rate_limit_key = self::resend_activation_rate_limit_key( $user->ID, $user_email );
            if( get_transient($rate_limit_key)!==false ) {
                return true;
            }
            $daily_limit_key = self::resend_activation_daily_limit_key( $user->ID );
            $daily_count = absint( get_transient($daily_limit_key) );
            if( $daily_count>=5 ) {
                return true;
            }
            $mail = self::send_verification_email(array(
                'password' => '',
                'code' => $code,
                'user' => $user,
                'settings' => $settings,
                'data' => array(
                    'user_login' => array( 'value' => $user->user_login ),
                    'user_email' => array( 'value' => $user_email ),
                ),
            ));
            if( !empty( $mail->ErrorInfo ) ) {
                return false;
            }
            set_transient( $rate_limit_key, time(), MINUTE_IN_SECONDS );
            set_transient( $daily_limit_key, $daily_count + 1, self::resend_activation_daily_limit_ttl() );
            return true;
        }

        private static function finish_resend_activation_request() {
            SUPER_Common::output_message(
                $error = false,
                $msg = esc_html__( 'If the account can receive verification messages, we have sent a new verification code.', 'super-forms' ),
                $redirect = null
            );
        }

        /**
         * Revalidate an exact request, actor, target, and action.
         */
        private static function user_action_context_is_authorized( $context, $post ) {
            if( !is_array($context) || !isset($context['request'], $context['actor'], $context['target'], $context['action']) || !is_string($context['request']) ) {
                return false;
            }
            $fingerprint = self::get_request_fingerprint( $post );
            if( ($fingerprint===false) || (!hash_equals($context['request'], $fingerprint)) ) {
                return false;
            }
            $current_actor = get_current_user_id();
            if( $context['action']==='update' ) {
                return ($current_actor===absint($context['actor'])) && self::can_update_user($context['actor'], $context['target']);
            }
            if( $context['action']==='register' ) {
                return (get_userdata(absint($context['target']))!==false)
                    && (($current_actor===absint($context['actor'])) || ($current_actor===absint($context['target'])));
            }
            return false;
        }

        /**
         * Stop before an account mutation when the exact action context is no
         * longer authorized.
         */
        private static function require_user_action_context( $context, $post ) {
            if( !self::user_action_context_is_authorized($context, $post) ) {
                SUPER_Common::output_message(
                    $error = true,
                    $msg = esc_html__( 'You are not allowed to update this user.', 'super-forms' ),
                    $redirect = null
                );
            }
        }

        /**
         * Reauthorize immediately before one user-meta mutation.
         */
        private static function update_user_meta_for_action( $context, $post, $meta_key, $value ) {
            self::require_user_action_context( $context, $post );
            return update_user_meta( absint($context['target']), $meta_key, $value );
        }


        /**
         * Reject WordPress and add-on metadata that controls account authority
         * or credentials.
         */
        private static function is_protected_user_meta_key( $meta_key ) {
            if( !is_string($meta_key) || ($meta_key==='') ) {
                return true;
            }
            $meta_key = strtolower( $meta_key );
            $protected = array(
                'capabilities',
                'user_level',
                'session_tokens',
                '_application_passwords',
                'application_passwords',
                'super_account_status',
                'super_account_activation',
                'super_user_login_status',
                'super_user_approve_data',
                'super_last_login',
            );
            if( in_array($meta_key, $protected, true) ) {
                return true;
            }

            global $wpdb;
            $prefixes = array();
            if( isset($wpdb) && is_object($wpdb) ) {
                if( isset($wpdb->prefix) && is_string($wpdb->prefix) ) {
                    $prefixes[] = strtolower( $wpdb->prefix );
                }
                if( isset($wpdb->base_prefix) && is_string($wpdb->base_prefix) ) {
                    $prefixes[] = strtolower( $wpdb->base_prefix );
                }
            }
            foreach( array_unique($prefixes) as $prefix ) {
                if( ($meta_key===$prefix . 'capabilities') || ($meta_key===$prefix . 'user_level') ) {
                    return true;
                }
            }
            if( isset($wpdb->base_prefix) && is_string($wpdb->base_prefix) && ($wpdb->base_prefix!=='') ) {
                $base_prefix = preg_quote( strtolower($wpdb->base_prefix), '/' );
                if( preg_match('/^' . $base_prefix . '[0-9]+_(?:capabilities|user_level)$/D', $meta_key) ) {
                    return true;
                }
            }
            return false;
        }


        /**
         * Parse and validate the complete administrator-authored custom-meta map.
         *
         * Empty lines are ignored. Every other line must contain exactly one
         * source-to-meta separator and a safe, non-authority meta key.
         */
        private static function validate_custom_meta_mapping( $mapping ) {
            if( !is_string($mapping) ) {
                return false;
            }
            $validated = array();
            $lines = preg_split( '/\r\n|\r|\n/', $mapping );
            foreach( $lines as $line ) {
                if( trim($line)==='' ) {
                    continue;
                }
                $parts = explode( '|', $line );
                if( count($parts)!==2 ) {
                    return false;
                }
                $source = trim( $parts[0] );
                $meta_key = trim( $parts[1] );
                if( ($source==='') || ($meta_key==='') || (!preg_match('/^[^\x00-\x1F\x7F|]+$/D', $source))
                    || (!preg_match('/^[A-Za-z0-9_.:-]+$/D', $meta_key))
                    || self::is_protected_user_meta_key($meta_key) ) {
                    return false;
                }
                $validated[] = array(
                    'source' => $source,
                    'meta_key' => $meta_key,
                );
            }
            return $validated;
        }

        /**
         * Resolve one administrator-authored mapping from finalized server-side form data.
         *
         * File values are accepted only when the core submission pipeline has
         * rebuilt them from an owned upload receipt or retained entry record.
         */
        private static function resolve_custom_meta_value( $source, $data, $settings, $form_id=0 ) {
            if( isset($data[$source]) && is_array($data[$source])
                && isset($data[$source]['type']) && $data[$source]['type']==='files' ) {
                if( !isset($data[$source]['files']) || !is_array($data[$source]['files']) ) {
                    return new WP_Error( 'super_forms_invalid_custom_meta_file' );
                }
                $file_values = array();
                foreach( $data[$source]['files'] as $file ) {
                    if( !is_array($file) || isset($file['upload_token']) || isset($file['retention_token'])
                        || !isset($file['_super_file_authority'])
                        || !in_array($file['_super_file_authority'], array('owned', 'retained'), true)
                        || !isset($file['value'], $file['name'], $file['type'], $file['url'])
                        || !is_string($file['value']) || !is_string($file['name'])
                        || !is_string($file['type']) || !is_string($file['url'])
                        || $file['name']!==$source ) {
                        return new WP_Error( 'super_forms_invalid_custom_meta_file' );
                    }
                    $attachment_id = isset($file['attachment']) ? absint($file['attachment']) : 0;
                    if( $attachment_id!==0 ) {
                        if( isset($file['path']) || isset($file['subdir']) ) {
                            return new WP_Error( 'super_forms_invalid_custom_meta_file' );
                        }
                        $filename = get_attached_file($attachment_id);
                        $real = is_string($filename) && $filename!=='' && !is_link($filename)
                            ? realpath($filename)
                            : false;
                        if( $real===false || !is_file($real) || get_post_type($attachment_id)!=='attachment'
                            || basename($real)!==$file['value'] ) {
                            return new WP_Error( 'super_forms_invalid_custom_meta_file' );
                        }
                        $file_values[] = $attachment_id;
                        continue;
                    }
                    $stored_proof = isset($file['_super_file_proof']) && is_string($file['_super_file_proof'])
                        ? $file['_super_file_proof']
                        : '';
                    if( isset($file['attachment'])
                        || !isset($file['path'], $file['subdir'])
                        || !is_string($file['path']) || !is_string($file['subdir'])
                        || $file['path']==='' || $file['subdir']==='' || is_link($file['path'])
                        || strpos($file['path'], "\0")!==false
                        || strpos($file['subdir'], "\0")!==false || strpos($file['subdir'], '\\')!==false
                        || preg_match('/^[a-f0-9]{64}$/D', $stored_proof)!==1
                        || !class_exists('SUPER_Ajax') ) {
                        return new WP_Error( 'super_forms_invalid_custom_meta_file' );
                    }
                    // Resolve the retained file from its stored path+subdir the same way the
                    // core submission pipeline does, independent of the form's current
                    // upload-root setting, so a later upload-root change cannot break a valid
                    // account update.
                    $candidates = SUPER_Forms::resolve_stored_owned_upload_candidates(
                        wp_normalize_path( wp_unslash($file['path']) ),
                        $file['subdir']
                    );
                    if( count($candidates)!==1 || !is_array($candidates[0]) ) {
                        return new WP_Error( 'super_forms_invalid_custom_meta_file' );
                    }
                    $candidate = $candidates[0];
                    if( empty($candidate['file']) || !is_string($candidate['file'])
                        || empty($candidate['root']) || !is_string($candidate['root'])
                        || basename($candidate['file'])!==$file['value'] ) {
                        return new WP_Error( 'super_forms_invalid_custom_meta_file' );
                    }
                    $size = filesize($candidate['file']);
                    if( !is_int($size) || $size<0 ) {
                        return new WP_Error( 'super_forms_invalid_custom_meta_file' );
                    }
                    // Rebuild the server-owned record for the resolved file and require the
                    // stored authority proof to match. The proof binds the form, field, path,
                    // subdir, mime, url and size, so no client-supplied value carries authority.
                    $owned = SUPER_Ajax::build_owned_upload(
                        absint($form_id),
                        $file['name'],
                        $candidate['file'],
                        $file['type'],
                        $file['url'],
                        0,
                        $candidate['root'],
                        $size,
                        $file['subdir']
                    );
                    $proof = is_array($owned) ? SUPER_Ajax::owned_custom_upload_proof($owned) : false;
                    if( $owned===false || !is_string($proof) || !hash_equals($stored_proof, $proof) ) {
                        return new WP_Error( 'super_forms_invalid_custom_meta_file' );
                    }
                    $file_values[] = $candidate['file'];
                }
                if( count($file_values)===1 ) {
                    return reset($file_values);
                }
                return implode(',', $file_values);
            }
            if( isset($data[$source]) && is_array($data[$source]) && array_key_exists('value', $data[$source]) ) {
                return $data[$source]['value'];
            }
            $string = SUPER_Common::email_tags( $source, $data, $settings );
            if( !is_string($string) ) {
                return $string;
            }
            $unserialized = (defined('PHP_VERSION_ID') && PHP_VERSION_ID>=70000)
                ? @unserialize( $string, array('allowed_classes'=>false) )
                : @unserialize( $string );
            return is_array($unserialized) ? $unserialized : $string;
        }


        /**
         * Registration roles must never grant WordPress or obvious third-party
         * administrative authority to unauthenticated visitors.
         */
        private static function role_grants_unsafe_public_registration_capability( $capability ) {
            if( !is_string($capability) || $capability==='' ) {
                return false;
            }
            $capability = strtolower($capability);
            $unsafe = array(
                'activate_plugins'=>true,
                'add_users'=>true,
                'create_sites'=>true,
                'create_users'=>true,
                'customize'=>true,
                'delete_plugins'=>true,
                'delete_site'=>true,
                'delete_sites'=>true,
                'delete_themes'=>true,
                'delete_users'=>true,
                'edit_files'=>true,
                'edit_plugins'=>true,
                'edit_theme_options'=>true,
                'edit_themes'=>true,
                'edit_users'=>true,
                'install_languages'=>true,
                'install_plugins'=>true,
                'install_themes'=>true,
                'list_users'=>true,
                'manage_network'=>true,
                'manage_network_options'=>true,
                'manage_network_plugins'=>true,
                'manage_network_themes'=>true,
                'manage_network_users'=>true,
                'manage_options'=>true,
                'manage_sites'=>true,
                'promote_users'=>true,
                'remove_users'=>true,
                'resume_plugins'=>true,
                'resume_themes'=>true,
                'setup_network'=>true,
                'switch_themes'=>true,
                'unfiltered_html'=>true,
                'unfiltered_upload'=>true,
                'update_core'=>true,
                'update_plugins'=>true,
                'update_themes'=>true,
                'upgrade_network'=>true,
                'upload_plugins'=>true,
                'upload_themes'=>true,
            );
            if( isset($unsafe[$capability]) ) {
                return true;
            }
            if( preg_match('/^manage_(?:.+_)?(?:admin|administrator|network|plugin|plugins|theme|themes|core|site|sites|setting|settings|option|options|role|roles|capability|capabilities|account|accounts|user|users|platform)$/', $capability)===1 ) {
                return true;
            }
            if( preg_match('/(^|_)(?:admin|administrator)(_|$)/', $capability)===1 ) {
                return true;
            }
            return preg_match('/^(?:create|delete|edit|install|promote|remove|switch|update)_(?:users?|plugins?|themes?|core|sites?|network|roles?|capabilities?|options?|settings?|accounts?)$/', $capability)===1;
        }

        private static function get_safe_registration_role_slug( $role ) {
            if( !is_string($role) || $role==='' || $role==='_super_keep_existing_role' ) {
                return false;
            }
            // The built-in editor role grants unfiltered_html on supported installs.
            if( $role==='editor' ) {
                return false;
            }
            $role_object = get_role( $role );
            if( !($role_object instanceof WP_Role) ) {
                return false;
            }
            foreach( $role_object->capabilities as $capability => $granted ) {
                if( !empty($granted) && self::role_grants_unsafe_public_registration_capability($capability) ) {
                    return false;
                }
            }
            return $role;
        }

        private static function get_registration_role_field_name( $role ) {
            if( !is_string($role) ) {
                return false;
            }
            $role = trim($role);
            if( preg_match('/^\{([A-Za-z0-9_-]+)\}$/D', $role, $matches)!==1 ) {
                return false;
            }
            return $matches[1];
        }

        private static function collect_registration_role_fields( $elements, $field_name, &$matches=array() ) {
            if( !is_array($elements) ) {
                return;
            }
            foreach( $elements as $element ) {
                if( !is_array($element) ) {
                    continue;
                }
                if( !empty($element['inner']) ) {
                    self::collect_registration_role_fields( $element['inner'], $field_name, $matches );
                }
                $data = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
                if( isset($data['name']) && is_string($data['name']) && $data['name']===$field_name ) {
                    $matches[] = $element;
                }
            }
        }

        private static function resolve_saved_registration_role_choice( $form_id, $field_name, $data, $settings ) {
            if( !class_exists('SUPER_Shortcodes') ) {
                require_once( SUPER_PLUGIN_DIR . '/includes/class-shortcodes.php' );
            }
            $missing = array(
                'status' => 'missing',
                'role' => null,
            );
            $fallback = array(
                'status' => 'fallback',
                'role' => null,
            );
            $invalid_field = array(
                'status' => 'invalid_field',
                'role' => null,
            );
            $not_selector = array(
                'status' => 'not_selector',
                'role' => null,
            );
            $elements = SUPER_Common::get_form_elements( $form_id );
            $matches = array();
            self::collect_registration_role_fields( $elements, $field_name, $matches );
            if( count($matches)===0 ) {
                return $missing;
            }
            if( count($matches)!==1 ) {
                return $invalid_field;
            }
            $element = $matches[0];
            $tag = isset($element['tag']) ? $element['tag'] : '';
            $atts = (isset($element['data']) && is_array($element['data'])) ? $element['data'] : array();
            if( ($tag!=='dropdown' && $tag!=='radio')
                || ($tag==='dropdown' && (!isset($atts['dropdown_items']) || !is_array($atts['dropdown_items'])))
                || ($tag==='radio' && (!isset($atts['radio_items']) || !is_array($atts['radio_items']))) ) {
                // Single matched field exists but is not shaped like a role
                // selector (e.g. an ordinary text field named "role", or a
                // selector missing its items array). This is not a tamper
                // signal, so report it distinctly from invalid_field so the
                // caller can decide per-context (legacy probe falls through to
                // the safe configured/default role; explicit configured field
                // still fails closed as author misconfiguration).
                return $not_selector;
            }
            $render_settings = SUPER_Common::get_form_settings( $form_id );
            if( !is_array($render_settings) ) {
                $render_settings = array();
            }
            $items = SUPER_Shortcodes::get_items(array(
                'items' => array(),
                'tag' => $tag,
                'atts' => $atts,
                'prefix' => '',
                'settings' => $render_settings,
                'entry_data' => array(),
            ));
            if( !is_array($items) || !isset($items['items_values']) || !is_array($items['items_values']) ) {
                return $invalid_field;
            }
            $choices = array();
            foreach( $items['items_values'] as $item ) {
                if( !is_scalar($item) ) {
                    return $invalid_field;
                }
                $raw = (string) $item;
                $slug = trim( explode(';', $raw )[0] );
                if( $raw==='' || $slug==='' || isset($choices[$slug]) ) {
                    return $invalid_field;
                }
                $choices[$slug] = $raw;
            }
            if( !isset($data[$field_name]) ) {
                return $missing;
            }
            if( !is_array($data[$field_name]) || !array_key_exists('value', $data[$field_name]) || !is_scalar($data[$field_name]['value']) ) {
                return $fallback;
            }
            $selected = (string) $data[$field_name]['value'];
            if( $selected==='' ) {
                return $missing;
            }
            if( strpos($selected, ',')!==false || strpos($selected, ';')!==false ) {
                return $fallback;
            }
            $selected_slug = trim( explode(';', $selected )[0] );
            if( $selected_slug==='' || !isset($choices[$selected_slug]) ) {
                return $fallback;
            }
            if( !(get_role($selected_slug) instanceof WP_Role) ) {
                return $fallback;
            }
            return array(
                'status' => 'selected',
                'role' => $selected_slug,
            );
        }

        private static function get_safe_public_registration_role( $settings, $data=array(), $form_id=0 ) {
            $configured_role = (isset($settings['register_user_role']) && is_string($settings['register_user_role']))
                ? trim($settings['register_user_role'])
                : '';
            $default_role = (string) get_option( 'default_role' );
            $configured_field_name = self::get_registration_role_field_name( $configured_role );
            if( $configured_field_name!==false ) {
                $resolved = self::resolve_saved_registration_role_choice( absint($form_id), $configured_field_name, $data, $settings );
                if( !is_array($resolved) || empty($resolved['status']) ) {
                    return false;
                }
                // An explicitly configured role field that is not a selector is
                // author misconfiguration: fail closed exactly like a tamper
                // signal rather than silently falling back.
                if( $resolved['status']==='invalid_field' || $resolved['status']==='not_selector' ) {
                    return false;
                }
                if( $resolved['status']==='selected' ) {
                    $safe_selected_role = self::get_safe_registration_role_slug( $resolved['role'] );
                    if( $safe_selected_role!==false ) {
                        return $safe_selected_role;
                    }
                }
                return self::get_safe_registration_role_slug( $default_role );
            }
            $legacy_role = self::resolve_saved_registration_role_choice( absint($form_id), 'role', $data, $settings );
            if( !is_array($legacy_role) || empty($legacy_role['status']) ) {
                return false;
            }
            if( $legacy_role['status']==='invalid_field' ) {
                return false;
            }
            // A field literally named "role" that is not a selector (e.g. an
            // ordinary text field) is not a configured role source: ignore it
            // and fall through to the safe configured/default role. Only real
            // tamper/ambiguity (invalid_field) refuses registration.
            if( $legacy_role['status']==='selected' ) {
                $safe_legacy_role = self::get_safe_registration_role_slug( $legacy_role['role'] );
                if( $safe_legacy_role!==false ) {
                    return $safe_legacy_role;
                }
            }
            if( $configured_role==='' ) {
                $configured_role = $default_role;
            }
            return self::get_safe_registration_role_slug( $configured_role );
        }


        /**
         * Save validated custom user metadata after uploaded files have been
         * resolved into their final server-owned values.
         *
         *  @since      1.3.0
        */
        public static function before_email_success_msg( $atts ) {
            $post = (isset($atts['post']) && is_array($atts['post'])) ? $atts['post'] : array();
            $settings = (isset($atts['settings']) && is_array($atts['settings'])) ? $atts['settings'] : array();
            $had_context = is_array(self::$deferred_user_action);
            $context = self::consume_deferred_user_action( $post );
            if( $context===false ) {
                if( $had_context ) {
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = esc_html__( 'Unable to authorize the account action for this request.', 'super-forms' ),
                        $redirect = null
                    );
                }
                return;
            }
            $data = (isset($atts['data']) && is_array($atts['data'])) ? $atts['data'] : array();
            $user_id = absint( $context['target'] );
            $form_id = absint( isset($post['form_id']) ? $post['form_id'] : 0 );
            $meta_data = array();

            if( $context['action']==='update' ) {
                foreach( $context['meta_mapping'] as $mapping ) {
                    $value = self::resolve_custom_meta_value(
                        $mapping['source'],
                        $data,
                        $settings,
                        $form_id
                    );
                    if( is_wp_error($value) ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = esc_html__( 'Invalid file upload.', 'super-forms' ),
                            $redirect = null
                        );
                    }
                    $meta_data[$mapping['meta_key']] = $value;
                }
                if( !isset($context['userdata']) || !is_array($context['userdata'])
                    || !isset($context['userdata']['ID'])
                    || absint($context['userdata']['ID'])!==$user_id ) {
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = esc_html__( 'Unable to authorize the user update for this request.', 'super-forms' ),
                        $redirect = null
                    );
                }
                self::require_user_action_context($context, $post);
                $result = wp_update_user($context['userdata']);
                if( is_wp_error($result) ) {
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $result->get_error_message(),
                        $redirect = null
                    );
                }
                foreach( $meta_data as $meta_key => $value ) {
                    self::update_user_meta_for_action( $context, $post, $meta_key, $value );
                }
                return;
            }

            foreach( $context['meta_mapping'] as $mapping ) {
                $value = self::resolve_custom_meta_value($mapping['source'], $data, $settings, $form_id);
                if( is_wp_error($value) ) {
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = esc_html__( 'Invalid file upload.', 'super-forms' ),
                        $redirect = null
                    );
                }
                $meta_data[$mapping['meta_key']] = array(
                    'source' => $mapping['source'],
                    'value' => $value,
                );
            }

            foreach( $meta_data as $meta_key => $meta ) {
                if( function_exists('get_field_object') ) {
                    global $wpdb;
                    $length = strlen( $meta_key );
                    if( class_exists('acf_pro') ) {
                        $acf_field = $wpdb->get_var( $wpdb->prepare(
                            "SELECT post_name FROM {$wpdb->posts} WHERE post_excerpt = %s AND post_type = 'acf-field'",
                            $meta_key
                        ) );
                    }else{
                        $acf_field = $wpdb->get_var( $wpdb->prepare(
                            "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_value LIKE %s",
                            $wpdb->esc_like('field_') . '%',
                            '%' . $wpdb->esc_like('"name";s:' . $length . ':"' . $meta_key . '";') . '%'
                        ) );
                    }
                    if( $acf_field ) {
                        $acf_field = get_field_object( $acf_field );
                        if( ($acf_field['type']==='checkbox') || ($acf_field['type']==='select') || ($acf_field['type']==='radio') || ($acf_field['type']==='gallery') ) {
                            $value = is_array($meta['value']) ? $meta['value'] : explode( ',', (string)$meta['value'] );
                            if( !self::user_action_context_is_authorized($context, $post) ) {
                                SUPER_Common::output_message( $error = true, esc_html__( 'You are not allowed to update this user.', 'super-forms' ) );
                            }
                            update_field( $acf_field['key'], $value, 'user_'.$user_id );
                            continue;
                        }
                        if( $acf_field['type']==='google_map' ) {
                            $source = $meta['source'];
                            if( isset($data[$source]['geometry']['location']) ) {
                                $data[$source]['geometry']['location']['address'] = isset($data[$source]['value']) ? $data[$source]['value'] : '';
                                $value = $data[$source]['geometry']['location'];
                            }else{
                                $value = array(
                                    'address' => isset($data[$source]['value']) ? $data[$source]['value'] : '',
                                    'lat' => '',
                                    'lng' => '',
                                );
                            }
                            if( !self::user_action_context_is_authorized($context, $post) ) {
                                SUPER_Common::output_message( $error = true, esc_html__( 'You are not allowed to update this user.', 'super-forms' ) );
                            }
                            update_field( $acf_field['key'], $value, 'user_'.$user_id );
                            continue;
                        }
                        if( $acf_field['type']==='repeater' ) {
                            $repeater_values = array();
                            foreach( $acf_field['sub_fields'] as $sub_field ) {
                                if( isset($data[$sub_field['name']]) ) {
                                    $repeater_values[0][$sub_field['name']] = SUPER_Register_Login()->return_field_value( $data, $sub_field['name'], $sub_field['type'], $settings );
                                    $field_counter = 2;
                                    while( isset($data[$sub_field['name'] . '_' . $field_counter]) ) {
                                        $repeater_values[$field_counter-1][$sub_field['name']] = SUPER_Register_Login()->return_field_value( $data, $sub_field['name'] . '_' . $field_counter, $sub_field['type'], $settings );
                                        $field_counter++;
                                    }
                                }
                            }
                            if( !self::user_action_context_is_authorized($context, $post) ) {
                                SUPER_Common::output_message( $error = true, esc_html__( 'You are not allowed to update this user.', 'super-forms' ) );
                            }
                            update_field( $acf_field['key'], $repeater_values, 'user_'.$user_id );
                            continue;
                        }
                        if( !self::user_action_context_is_authorized($context, $post) ) {
                            SUPER_Common::output_message( $error = true, esc_html__( 'You are not allowed to update this user.', 'super-forms' ) );
                        }
                        update_field( $acf_field['key'], $meta['value'], 'user_'.$user_id );
                        continue;
                    }
                }
                self::update_user_meta_for_action( $context, $post, $meta_key, $meta['value'] );
            }
        }


        /**
         * Hook into before sending email and check if we need to register or login a user
         *
         *  @since      1.0.0
        */
        public static function before_sending_email( $x ) {
            self::clear_user_meta_bridge();
            extract( shortcode_atts( array( 'data'=>array(), 'post'=>array(), 'settings'=>array()), $x ) );
            if( isset($post['action']) && ($post['action']==='super_upload_files') ) return true;
            if( !isset( $settings['register_login_action'] ) ) return true;
            if( $settings['register_login_action']==='none' ) return true;

            $request_actor_id = get_current_user_id();

            // @since 1.2.0 - update existing user data
            if( $settings['register_login_action']==='update' ) {
                $actor_id = $request_actor_id;
                $target_id = $actor_id;
                if( ($actor_id!==0) && (!empty($settings['register_login_user_id_update'])) && ($settings['register_login_user_id_update']==='true') ) {
                    if( isset($data['user_id']['value']) && (absint($data['user_id']['value'])!==0) ) {
                        $target_id = absint( $data['user_id']['value'] );
                    }
                }

                if( $actor_id===0 ) {
                    // @since 1.4.0 - do not throw error message when we allow none logged in users to register
                    if( (!empty($settings['register_login_register_not_logged_in'])) && ($settings['register_login_register_not_logged_in']==='true') ) {
                        $settings['register_login_action'] = 'register';
                    }else{
                        $msg = $settings['register_login_not_logged_in_msg'];
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }
                }elseif( !self::can_update_user($actor_id, $target_id) ){
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = esc_html__( 'You are not allowed to update this user.', 'super-forms' ),
                        $redirect = null
                    );
                }else{
                    $meta_mapping = self::validate_custom_meta_mapping(
                        isset($settings['register_login_update_user_meta']) ? $settings['register_login_update_user_meta'] : ''
                    );
                    if( $meta_mapping===false ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = esc_html__( 'The custom user meta mapping is invalid or contains a protected key.', 'super-forms' ),
                            $redirect = null
                        );
                    }

                    $other_userdata = array(
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
                    foreach( $other_userdata as $key ) {
                        if( isset($data[$key]['value']) ) {
                            $value = $data[$key]['value'];
                            if( $key==='user_login' ) $value = sanitize_user( $value );
                            if( $key==='user_email' ) $value = sanitize_email( $value );
                            $userdata[$key] = $value;
                        }
                    }
                    if(!empty($settings['register_login_show_toolbar'])) {
                        $userdata['show_admin_bar_front'] = $settings['register_login_show_toolbar'];
                    }

                    $requested_role = false;
                    if( isset($settings['register_user_role']) && is_string($settings['register_user_role']) ) {
                        $role = SUPER_Common::email_tags( $settings['register_user_role'], $data, $settings );
                        $editable_roles = function_exists('get_editable_roles') ? get_editable_roles() : array();
                        if( ($role!=='')
                            && ($role!=='_super_keep_existing_role')
                            && isset($editable_roles[$role])
                            && (get_role($role) instanceof WP_Role)
                            // WordPress deliberately does not let a user alter their own role.
                            && $actor_id!==$target_id
                            && current_user_can('promote_user', $target_id) ) {
                            $requested_role = $role;
                        }
                    }
                    if( $requested_role!==false ) {
                        $userdata['role'] = $requested_role;
                    }

                    if( (get_current_user_id()!==$actor_id) || !self::can_update_user($actor_id, $target_id) ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = esc_html__( 'You are not allowed to update this user.', 'super-forms' ),
                            $redirect = null
                        );
                    }
                    $userdata['ID'] = $target_id;
                    $context = self::build_user_action_context( $post, $actor_id, $target_id, 'update', $meta_mapping );
                    if( $context===false ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = esc_html__( 'Unable to authorize the user update for this request.', 'super-forms' ),
                            $redirect = null
                        );
                    }
                    // The account is still untouched here.  Bind the exact intended
                    // mutation to the request-local context and execute it only from
                    // the final success hook after the submission's remaining effects.
                    $context['userdata'] = $userdata;
                    if( !self::set_deferred_user_action($context) ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = esc_html__( 'Unable to authorize the user update for this request.', 'super-forms' ),
                            $redirect = null
                        );
                    }
                }
            }

            if( $settings['register_login_action']==='register' ) {
                $meta_mapping = self::validate_custom_meta_mapping(
                    isset($settings['register_login_user_meta']) ? $settings['register_login_user_meta'] : ''
                                        );
                if( $meta_mapping===false ) {
                        SUPER_Common::output_message(
                            $error = true,
                        $msg = esc_html__( 'The custom user meta mapping is invalid or contains a protected key.', 'super-forms' ),
                            $redirect = null
                        );
                    }

                $registration_role = self::get_safe_public_registration_role(
                    $settings,
                    $data,
                    isset($post['form_id']) ? absint($post['form_id']) : 0
                );
                if( $registration_role===false ) {
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = esc_html__( 'Registration is unavailable because the configured user role is not allowed.', 'super-forms' ),
                        $redirect = null
                    );
                }

                // @since 1.2.6 - skip registration if user_login or user_email couldn't be found or where conditionally hidden
                if(!isset($settings['register_login_action_skip_register'])) $settings['register_login_action_skip_register'] = '';
                if( ($settings['register_login_action_skip_register']==='true') && ( (!isset($data['user_login'])) || (!isset($data['user_email'])) ) ) {
                    // do nothing
                }else{


                    // Before we proceed, lets check if we have at least a user_login and user_email field
                    if( ( !isset( $data['user_login'] ) ) || ( !isset( $data['user_email'] ) ) ) {
                        $msg = sprintf( esc_html__( 'We couldn\'t find the %1$s and %2$s fields which are required in order to register a new user. Please %3$sedit%4$s your form and try again', 'super-forms' ), '<strong>user_login</strong>', '<strong>user_email</strong>', '<a href="' . esc_url(get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $post['form_id'] )) . '">', '</a>' );
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
                    $username_user = ($username_exists!==false) ? get_user_by( 'login', $user_login ) : false;
                    if( $username_exists!==false && !($username_user instanceof WP_User) ) {
                        $username_exists = true;
                    }

                    $email_exists = email_exists($user_email);
                    $email_user = ($email_exists!==false) ? get_user_by( 'email', $user_email ) : false;
                    if( $email_exists!==false && !($email_user instanceof WP_User) ) {
                        $email_exists = true;
                    }

                    if( ($username_user instanceof WP_User)
                        && ($email_user instanceof WP_User)
                        && absint($username_user->ID)===absint($email_user->ID) ) {
                        self::maybe_resume_pending_registration(
                            $username_user,
                            isset($post['form_id']) ? absint($post['form_id']) : 0,
                            $settings,
                            $data
                        );
                        $username_exists = true;
                        $email_exists = true;
                    }else{
                        if( $username_user instanceof WP_User ) {
                            $username_exists = true;
                        }
                        if( $email_user instanceof WP_User ) {
                            $email_exists = true;
                        }
                    }

                    if( ( $username_exists!=false ) || ( $email_exists!=false ) ) {
                        $msg = esc_html__( 'Username or E-mail address already exists, please try again', 'super-forms' );
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
                    $password = '';
                    if( !isset( $data['user_pass'] ) ) {
                        $send_password = true;
                        $password = wp_generate_password( 24, false );
                    }else{
                        $password = $data['user_pass']['value'];
                    }

                    // Lets gather all data that we need to insert for this user
                    $userdata = array();
                    $userdata['user_login'] = $user_login;
                    $userdata['user_email'] = $user_email;
                    $userdata['user_pass'] = $password;
                    $userdata['role'] = $registration_role;
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

                        SUPER_Common::setClientData( array( 'name'=> 'msg', 'value'=>array( 'data'=>$data, 'settings'=>$settings, 'msg'=>$msg, 'type'=>'error'  ) ) );
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }

                    // @since v1.0.3 - currently used by the WooCommerce Checkout feature
                    do_action( 'super_after_wp_insert_user_action', array( 'user_id'=>$user_id, 'atts'=>$x ) );
       
                    $registration_context = self::build_user_action_context(
                        $post,
                        $request_actor_id,
                        $user_id,
                        'register',
                        $meta_mapping
                    );
                    if( $registration_context===false ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = esc_html__( 'Unable to authorize registration for this request.', 'super-forms' ),
                            $redirect = null
                        );
                    }

                    // @since 1.0.3
                    if( !isset($settings['register_user_signup_status']) ) $settings['register_user_signup_status'] = 'active';
                    self::update_user_meta_for_action( $registration_context, $post, 'super_user_login_status', $settings['register_user_signup_status'] );

                    if( (isset($settings['register_send_approve_email'])) && ($settings['register_send_approve_email']==='true') ) {
                        self::update_user_meta_for_action( $registration_context, $post, 'super_user_approve_data', array('settings'=>$settings, 'data'=>$data) );
                    }

                    // Check if we need to send an activation email to this user
                    if( ($settings['register_login_activation']=='verify') || ($settings['register_login_activation']=='verify_login') ) {
                        $code = wp_generate_password( 8, false );
                        
                        // @since 1.2.4 - allows users to use a custom activation code, for instance generated with the unique random number with a hidden field
                        if(isset($data['register_activation_code'])){
                            $code = $data['register_activation_code']['value'];
                        }
                        
                        self::update_user_meta_for_action( $registration_context, $post, 'super_account_status', 0 ); // 0 = inactive, 1 = active
                        self::update_user_meta_for_action( $registration_context, $post, 'super_account_activation', $code );
                        self::issue_pending_registration_recovery(
                            $user_id,
                            isset($post['form_id']) ? absint($post['form_id']) : 0,
                            $user_login,
                            $user_email
                        );
                        $user = get_user_by( 'id', $user_id );
                        $mail = self::send_verification_email(array('password'=>$password, 'code'=>$code, 'user'=>$user, 'settings'=>$settings, 'data'=>$data));
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
                    if( $settings['register_login_activation']=='verify_login' ) {
                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie( $user_id );
                        self::update_user_meta_for_action( $registration_context, $post, 'super_last_login', time() );
                    }

                    // Check if we let users automatically login after registering (instant login)
                    if( $settings['register_login_activation']=='login' ) $settings['register_login_activation'] = 'auto';
                    if( $settings['register_login_activation']=='auto' ) {
                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie( $user_id );
                        self::update_user_meta_for_action( $registration_context, $post, 'super_last_login', time() );
                        self::update_user_meta_for_action( $registration_context, $post, 'super_account_status', 1 );
                        self::update_user_meta_for_action( $registration_context, $post, 'super_user_login_status', 'active' );
                    }

                    // Check if automatically activate users
                    if( $settings['register_login_activation']=='activate' ) {
                        self::update_user_meta_for_action( $registration_context, $post, 'super_account_status', 1 );
                    }
                    // When set to 'none' we update account status to 1 so that user is able to login, although they are not automatically logged in
                    // When the login status of a new registered user is not set to "Active" then the user won't be able to login until an Admin has approved their account
                    if( $settings['register_login_activation']=='none' ) {
                        self::update_user_meta_for_action( $registration_context, $post, 'super_account_status', 1 );
                    }

                    // @since 1.1.0 - create multi-site
                    if( !isset($settings['register_login_multisite_enabled']) ) $settings['register_login_multisite_enabled'] = '';
                    if( $settings['register_login_multisite_enabled']=='true' ) {
                        $user = get_user_by( 'id', $user_id );
                        $domain = SUPER_Common::email_tags( $settings['register_login_multisite_domain'], $data, $settings, $user );
                        $path = SUPER_Common::email_tags( $settings['register_login_multisite_path'], $data, $settings, $user );
                        $title = SUPER_Common::email_tags( $settings['register_login_multisite_title'], $data, $settings, $user );
                        $site_id = SUPER_Common::email_tags( $settings['register_login_multisite_id'], $data, $settings, $user );
                        $site_meta = apply_filters( 'super_register_login_create_blog_site_meta', array(), $user_id, array(), $x, $settings );
                        $blog_id = wpmu_create_blog($domain, $path, $title, $user_id, $site_meta, $site_id);
                        if( is_wp_error( $blog_id ) ) {
                            $msg = $blog_id->get_error_message();
                            SUPER_Common::setClientData( array( 'name'=> 'msg', 'value'=>array( 'data'=>$data, 'settings'=>$settings, 'msg'=>$msg, 'type'=>'error'  ) ) );
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

                    // Keep only one request-local deferred action. The client
                    // value below is compatibility data for contact authorship,
                    // never authority for account or meta mutation.
                    self::require_user_action_context( $registration_context, $post );
                    if( !self::set_deferred_user_action($registration_context) ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = esc_html__( 'Unable to authorize registration for this request.', 'super-forms' ),
                            $redirect = null
                        );
                    }
                }
            }

            if( $settings['register_login_action']=='login' ) {

                // Before we proceed, lets check if we have at least a user_login or user_email and user_pass field
                if( ( !isset( $data['user_login'] ) ) || ( !isset( $data['user_pass'] ) ) ) {
                    $msg = sprintf( esc_html__( 'We couldn\'t find the %1$s or %2$s fields which are required in order to login a new user. Please %3$sedit%4$s your form and try again', 'super-forms' ), '<strong>user_login</strong>', '<strong>user_pass</strong>', '<a href="' . esc_url(get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $post['form_id'] )) . '">', '</a>' );
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
                        if(!isset($settings['login_user_role'])) $settings['login_user_role'] = array();
                        $loginUserRoles = array_filter($settings['login_user_role']);
                        if(count($loginUserRoles)===0){
                            $allowed = true;
                        }else{
                            foreach( $user->roles as $role ) {
                                if(in_array( $role, $loginUserRoles )){
                                    $allowed = true;
                                }
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
                            $msg = sprintf(
                                /* translators: 1: opening HTML link tag for resending the verification email, 2: closing HTML link tag. */
                                esc_html__( 'You haven\'t verified your account yet. Please check your email or click %1$shere%2$s to resend your verification email.', 'super-forms' ),
                                '<a href="#" class="resend-code" data-form="' . absint( $post['form_id'] ) . '" data-user="' . esc_attr($user->user_login) . '" data-email="' . esc_attr($user->user_email) . '" data-nonce="' . esc_attr( self::resend_activation_request_nonce() ) . '">',
                                '</a>'
                            );
                            // Only store message in session, if overlay popup is not enabled
                            if(!empty($settings['form_processing_overlay']) && $settings['form_processing_overlay']==='true'){
                                // Overlay enabled
                            }else{
                                SUPER_Common::setClientData( array( 'name'=> 'msg', 'value'=>array( 'data'=>$data, 'settings'=>$settings, 'msg'=>$msg, 'type'=>'error'  ) ) );
                            }
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
                                    delete_user_meta( $user_id, 'super_pending_registration_recovery' );
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
                        SUPER_Common::setClientData( array( 'name'=> 'msg', 'value'=>array( 'data'=>$data, 'settings'=>$settings, 'msg'=>$msg, 'type'=>'success'  ) ) );
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
                    $msg = sprintf( esc_html__( 'We couldn\'t find the %1$s field which is required in order to reset passwords. Please %2$sedit%3$s your form and try again', 'super-forms' ), '<strong>user_email</strong>', '<a href="' . esc_url(get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $post['form_id'] )) . '">', '</a>' );
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
                    // Also try to find by username
                    $user = get_user_by( 'login', $user_email );
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
                }

                // Disable the default lost password emails
                add_filter( 'send_password_change_email', '__return_false' );

                // Generate a new password for this user
                $password = wp_generate_password( 24, false );
                // Update the new password for this user
                $user_id = wp_update_user( array( 'ID' => $user->ID, 'user_pass' => $password ) );

                $mail = self::send_reset_password_email(array('password'=>$password, 'code'=>'', 'user'=>$user, 'settings'=>$settings, 'data'=>$data));

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
        public static function get_email_headers($x){
            extract( shortcode_atts( array( 'settings'=>array(), 'data'=>array(), 'user'=>null), $x ) );
            if(empty($settings['register_custom_email_header'])) $settings['register_custom_email_header'] = 'admin';
            if($settings['register_custom_email_header']==='admin'){
                // Use admin headers
                $header_from = $settings['header_from'];
                $header_from_name = $settings['header_from_name'];
                $header_reply_enabled = $settings['header_reply_enabled'];
                $header_reply = $settings['header_reply'];
                $header_reply_name = $settings['header_reply_name'];
            }
            if($settings['register_custom_email_header']==='confirmation'){
                // Use confirmation email headers
                $header_from = $settings['confirm_from'];
                $header_from_name = $settings['confirm_from_name'];
                $header_reply_enabled = $settings['confirm_header_reply_enabled'];
                $header_reply = $settings['confirm_header_reply'];
                $header_reply_name = $settings['confirm_header_reply_name'];
            }
            if($settings['register_custom_email_header']==='custom'){
                // Use custom headers
                $header_from = $settings['register_header_from'];
                $header_from_name = $settings['register_header_from_name'];
                $header_reply_enabled = $settings['register_header_reply_enabled'];
                $header_reply = $settings['register_header_reply'];
                $header_reply_name = $settings['register_header_reply_name'];
            }

            // @since 1.6.1 - set native from headers
            if(!empty($header_from)){
                $header_from = SUPER_Common::email_tags( $header_from, $data, $settings, $user );
            }else{
                $urlparts = parse_url(home_url());
                $header_from = 'no-reply@' . $urlparts['host']; // returns domain name
            }
            if(!empty($header_from_name)){
                $header_from_name = SUPER_Common::email_tags( $header_from_name, $data, $settings, $user );
            }else{
                $header_from_name = get_bloginfo('name');
            }
            // @since 1.3.0 - custom reply to headers
            if( $header_reply_enabled=='false' ) {
                $custom_reply = false;
            }else{
                $custom_reply = true;
                $header_reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $header_reply, $data, $settings ) );
                $header_reply_name = SUPER_Common::email_tags( $header_reply_name, $data, $settings );
            }
            return array(
                'header_from' => $header_from,
                'header_from_name' => $header_from_name,
                'custom_reply' => $custom_reply,
                'header_reply' => $header_reply,
                'header_reply_name' => $header_reply_name
            );
        }
        public static function send_verification_email($x){
            extract( shortcode_atts( array( 'password'=>'', 'code'=>'', 'user'=>null, 'settings'=>array(), 'data'=>array(), 'message'=>''), $x ) );
            $to = $user->user_email;
            $username = $user->user_login;
            // Replace email tags with correct data
            $subject = SUPER_Common::email_tags( $settings['register_activation_subject'], $data, $settings );
            $message = $settings['register_activation_email'];
            $message = str_replace( '{field_user_login}', $username, $message );
            $message = str_replace( '{user_login}', $username, $message );
            $message = str_replace( '{register_login_url}', $settings['register_login_url'], $message );
            $message = str_replace( '{register_activation_code}', $code, $message );
            $message = SUPER_Common::email_tags( $message, $data, $settings );
            if(!empty($password)){
                $message = str_replace( '{register_generated_password}', $password, $message );
            }
            $message = SUPER_Common::email_tags( $message, $data, $settings, $user );
            $message = nl2br( $message );
            // By default use Admin email settings
            $h = self::get_email_headers(array('settings'=>$settings, 'data'=>$data, 'user'=>$user));
            // Send the email
            $message = apply_filters( 'super_before_sending_email_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
            $message = apply_filters( 'super_before_sending_verification_email_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
            $attachments = apply_filters( 'super_register_login_before_verify_attachments_filter', array(), array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$message ) );
            // Deprecated, but used as fallback for custome code by other devs
            $attachments = apply_filters( 'super_register_login_before_resend_activation_attachments_filter', array(), array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$message ) );
            $mail = SUPER_Common::email( $to, $h['header_from'], $h['header_from_name'], $h['custom_reply'], $h['header_reply'], $h['header_reply_name'], '', '', $subject, $message, $settings, $attachments );
            return $mail;
        }
        public static function send_approve_email($x){
            extract( shortcode_atts( array( 'password'=>'', 'code'=>'', 'user'=>null, 'settings'=>array(), 'data'=>array(), 'message'=>''), $x ) );
            $username = $user->user_login;
            $to = $user->user_email;
            // Replace email tags with correct data
            $subject = SUPER_Common::email_tags( $settings['register_approve_subject'], $data, $settings );
            $message = $settings['register_approve_email'];
            $message = str_replace( '{field_user_login}', $username, $message );
            $message = str_replace( '{user_login}', $username, $message );
            $message = str_replace( '{register_login_url}', $settings['register_login_url'], $message );
            // Generate a password upon approval
            if( (isset($settings['register_approve_generate_pass'])) && ($settings['register_approve_generate_pass']=='true') ) {
                add_filter( 'send_password_change_email', '__return_false' );
                $password = wp_generate_password( 24, false );
                $user_id = wp_update_user( array( 'ID' => $user->ID, 'user_pass' => $password ) );
                $message = str_replace( '{field_user_pass}', $password, $message );
                $message = str_replace( '{user_pass}', $password, $message );
                $message = str_replace( '{register_generated_password}', $password, $message );
            }
            $message = SUPER_Common::email_tags( $message, $data, $settings );
            $message = nl2br( $message );
            // By default use Admin email settings
            $h = self::get_email_headers(array('settings'=>$settings, 'data'=>$data, 'user'=>$user));
            // Send the email
            $message = apply_filters( 'super_before_sending_email_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
            $message = apply_filters( 'super_before_sending_approve_email_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
            $attachments = apply_filters( 'super_register_login_before_approve_attachments_filter', array(), array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$message ) );
            $mail = SUPER_Common::email( $to, $h['header_from'], $h['header_from_name'], $h['custom_reply'], $h['header_reply'], $h['header_reply_name'], '', '', $subject, $message, $settings, $attachments );
            return $mail;
        }
        public static function send_reset_password_email($x){
            extract( shortcode_atts( array( 'password'=>'', 'code'=>'', 'user'=>null, 'settings'=>array(), 'data'=>array(), 'message'=>''), $x ) );
            $username = $user->user_login;
            $to = $user->user_email;
            // Replace email tags with correct data
            $subject = SUPER_Common::email_tags( $settings['register_reset_password_subject'], $data, $settings, $user );
            $message = $settings['register_reset_password_email'];
            $message = str_replace( '{field_user_login}', $username, $message );
            $message = str_replace( '{user_login}', $username, $message );
            $message = str_replace( '{register_login_url}', $settings['register_login_url'], $message );
            $message = str_replace( '{field_user_pass}', $password, $message );
            $message = str_replace( '{user_pass}', $password, $message );
            $message = str_replace( '{register_generated_password}', $password, $message );
            $message = SUPER_Common::email_tags( $message, $data, $settings );
            $message = nl2br( $message );
            // By default use Admin email settings
            $h = self::get_email_headers(array('settings'=>$settings, 'data'=>$data, 'user'=>$user));
            // Send the email
            $message = apply_filters( 'super_before_sending_email_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
            $message = apply_filters( 'super_before_sending_reset_password_body_filter', $message, array( 'settings'=>$settings, 'email_loop'=>'', 'data'=>$data ) );
            $attachments = apply_filters( 'super_register_login_before_sending_reset_password_attachments_filter', array(), array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$message ) );
            $mail = SUPER_Common::email( $to, $h['header_from'], $h['header_from_name'], $h['custom_reply'], $h['header_reply'], $h['header_reply_name'], '', '', $subject, $message, $settings, $attachments );
            return $mail;
        }


        /** 
         *  Resend activation code
         *
         *  @since      1.0.0
        */
        public static function resend_activation() {
            check_ajax_referer( 'super_resend_activation', 'nonce' );
            $data = isset($_POST['data']) && is_array($_POST['data'])
                ? map_deep( wp_unslash( $_POST['data'] ), 'sanitize_text_field' )
                : array();
            $username = isset($data['username']) ? sanitize_user( $data['username'] ) : '';
            $user_email = isset($data['email']) ? sanitize_email( $data['email'] ) : '';
            $form_id = isset($data['form']) ? absint( $data['form'] ) : 0;
            $settings = self::resend_activation_form_settings($form_id);
            if( $settings===false ) {
                self::finish_resend_activation_request();
            }
            $user = self::resend_activation_requested_user( $username, $user_email, $form_id );
            if( $user instanceof WP_User
                && self::maybe_resend_pending_activation_email( $user, $form_id, $settings ) ) {
                self::finish_resend_activation_request();
            }
            self::finish_resend_activation_request();
        }
    }
        
endif;


/**
 * Returns the main instance of SUPER_Register_Login to prevent the need to use globals.
 *
 * @return SUPER_Register_Login
 */
if( !function_exists('SUPER_Register_Login') ){
    function SUPER_Register_Login() {
        return SUPER_Register_Login::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['super_register_login'] = SUPER_Register_Login();
}
