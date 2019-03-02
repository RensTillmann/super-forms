<?php
/**
 * Super Forms - Password Protect
 *
 * @package   Super Forms - Password Protect
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Password Protect
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Password protect your forms or lock out specific user roles from submitting the form
 * Version:     1.0.6
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Password_Protect')) :


    /**
     * Main SUPER_Password_Protect Class
     *
     * @class SUPER_Password_Protect
     * @version	1.0.0
     */
    final class SUPER_Password_Protect {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.0.6';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'password_protect';
        public $add_on_name = 'Password Protect';


        /**
         * @var SUPER_Password_Protect The single instance of the class
         *
         *	@since		1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Password_Protect Instance
         *
         * Ensures only one instance of SUPER_Password_Protect is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Password_Protect()
         * @return SUPER_Password_Protect - Main instance
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
         * SUPER_Password_Protect Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_password_protect_loaded');
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

            if ( $this->is_request( 'frontend' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_form_before_do_shortcode_filter', array( $this, 'hide_form' ), 10, 2 );

                // Filters since 1.0.2
                add_filter( 'super_form_before_first_form_element_filter', array( $this, 'locked_msg' ), 10, 2 );

            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );
				add_filter( 'super_settings_end_filter', array( $this, 'activation' ), 100, 2 );

                // Actions since 1.0.0
                add_action( 'init', array( $this, 'update_plugin' ) );

                // Actions since 1.0.3
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );
                
            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Actions since 1.0.0
                add_action( 'super_before_sending_email_hook', array( $this, 'before_sending_email' ) );

            }
            
        }


        /**
         * Display activation message for automatic updates
         *
         *  @since      1.0.3
        */
        public function display_activation_msg() {
            if( !class_exists('SUPER_Forms') ) {
                echo '<div class="notice notice-error">'; // notice-success
                    echo '<p>';
                    echo sprintf( 
                        __( '%sPlease note:%s You must install and activate %4$s%1$sSuper Forms%2$s%5$s in order to be able to use %1$s%s%2$s!', 'super_forms' ), 
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
                SUPER_Forms::add_on_deactivate(SUPER_Password_Protect()->add_on_slug);
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
                if( ((isset($settings['password_protect'])) && ($settings['password_protect']=='true') ) ||
                	((isset($settings['password_protect_roles'])) && ($settings['password_protect_roles']=='true') ) ||
                	((isset($settings['password_protect_login'])) && ($settings['password_protect_login']=='true') ) ) {
                    return SUPER_Forms::add_on_activation_message($activation_msg, $this->add_on_slug, $this->add_on_name);
                }
            }
            return $activation_msg;
        }


        /**
         * Hook into before sending email and do password protect check
         *
         *  @since      1.0.0
        */
        public static function before_sending_email( $atts ) {
            $atts = array(
                'id' => $atts['post']['form_id'],
                'data' => $atts['post']['data'],
                'settings' => $atts['settings']
            );
            SUPER_Password_Protect()->locked_msg( '', $atts );
        }


        /**
         * Hide form if needed
         *
         *  @since      1.0.2
        */
        public static function hide_form( $result, $atts ) {
            
            // Check if we need to hide the form
            if( !isset( $atts['settings']['password_protect_roles'] ) ) {
                $atts['settings']['password_protect_roles'] = '';
            }
            if( $atts['settings']['password_protect_roles']=='true' ) {
	            if( !isset( $atts['settings']['password_protect_hide'] ) ) {
	                $atts['settings']['password_protect_hide'] = '';
	            }
	            if( $atts['settings']['password_protect_hide']=='true' ) {
                    // Check if the users doesn't have the propper user role
                    global $current_user;
                    if( (!isset( $atts['settings']['password_protect_user_roles'] )) || ($atts['settings']['password_protect_user_roles']=='') ) {
                        $atts['settings']['password_protect_user_roles'] = array();
                    }
                    $allowed_roles = $atts['settings']['password_protect_user_roles'];
                    $allowed = false;
                    foreach( $current_user->roles as $v ) {
                        if( in_array( $v, $allowed_roles ) ) {
                            $allowed = true;
                        }
                    }
                    if( $allowed==false ) {
                        return '';
                    }
	            }
            }

            if( !isset( $atts['settings']['password_protect_login'] ) ) {
                $atts['settings']['password_protect_login'] = '';
            }
            if( $atts['settings']['password_protect_login']=='true' ) {
            	if ( !is_user_logged_in() ) {
		            if( !isset( $atts['settings']['password_protect_login_hide'] ) ) {
		                $atts['settings']['password_protect_login_hide'] = '';
		            }
		            if( $atts['settings']['password_protect_login_hide']=='true' ) {

                        // @since 1.0.4 - ability to even display error message when complete form is hidden
                        if( $atts['settings']['password_protect_show_login_after_submit']!='true' ) {
                            if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                                SUPER_Common::output_error(
                                    $error = true,
                                    $msg = $atts['settings']['password_protect_login_msg'],
                                    $redirect = null
                                );               
                            }
                            $msg  = '<div id="super-form-' . $atts['id'] . '" class="super-form super-form-' . $atts['id'] . '">';
                                $msg .= '<div class="super-msg super-error">';
                                $msg .= $atts['settings']['password_protect_login_msg'];
                                $msg .= '<span class="close"></span>';
                                $msg .= '</div>';
                            $msg .= '</div>';
                            if( SUPER_Forms()->form_custom_css!='' ) {
                                $msg .= '<style type="text/css">' . SUPER_Forms()->form_custom_css . '</style>';
                            }
                            return $msg;
                        }else{
                            return '';
                        }
		            }
	            }
	        }

            // @since 1.0.3 - hide form from logged in users
            if ( is_user_logged_in() ) {
                if( !isset( $atts['settings']['password_protect_not_login_hide'] ) ) {
                    $atts['settings']['password_protect_not_login_hide'] = '';
                }
                if( $atts['settings']['password_protect_not_login_hide']=='true' ) {
                    return '';
                }
            }

            return $result;
        }


        /**
         * Display message to locked out users
         *
         *  @since      1.0.2
        */
        public static function locked_msg( $result, $atts ) {

            if( !isset( $atts['settings']['password_protect'] ) ) $atts['settings']['password_protect'] = '';
            if( !isset( $atts['settings']['password_protect_login'] ) ) $atts['settings']['password_protect_login'] = '';
            if( !isset( $atts['settings']['password_protect_roles'] ) ) $atts['settings']['password_protect_roles'] = '';

            // Check if password protect is enabled
            if( $atts['settings']['password_protect']=='true' ) {

                if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                    
                    if ( !SUPER_Password_Protect()->is_request( 'admin' ) ) {
	                    // Before we proceed, lets check if we have a password field
	                    if( !isset( $atts['data']['password'] ) ) {
	                        $msg = __( 'We couldn\'t find the <strong>password</strong> field which is required in order to password protect the form. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['id'] ) . '">edit</a> your form and try again', 'super-forms' );
	                        SUPER_Common::output_error(
	                            $error = true,
	                            $msg = $msg,
	                            $redirect = null
	                        );
	                    }
	                }

                    // Now lets check if the passwords are incorrect
					if( (isset($_REQUEST['action'])) && ($_REQUEST['action']=='super_send_email') ) {
						if( $atts['data']['password']['value']!=$atts['settings']['password_protect_password'] ) {
	                        if( !isset( $atts['settings']['password_protect_incorrect_msg'] ) ) {
	                            $atts['settings']['password_protect_incorrect_msg'] = __( 'Incorrect password, please try again!', 'super-forms' );
	                        }
	                        SUPER_Common::output_error(
	                            $error = true,
	                            $msg = $atts['settings']['password_protect_incorrect_msg'],
	                            $redirect = null
	                        );               
	                    }
	                }
                  
                }

                $elements = get_post_meta( absint($atts['id']), '_super_elements', true );
                if(!is_array($elements)){
                    $elements = json_decode( $elements, true );
                }
                $elements_json = json_encode($elements);
                $field_found = strpos($elements_json, '"name":"password"');
                if ($field_found === false) {
                    $msg  = '<div class="super-msg super-error">';
                    $msg .= __( 'You have enabled password protection for this form, but we couldn\'t find a password field with the name: <strong>password</strong>. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['id'] ) . '">edit</a> your form and try again.', 'super-forms' );
                    $msg .= '<span class="close"></span>';
                    $msg .= '</div>';
                    return $result.$msg;
                }
            }

            // Return message for non logged in users
            if( $atts['settings']['password_protect_login']=='true' ) {
                if ( !is_user_logged_in() ) {
                    if( !isset( $atts['settings']['password_protect_show_login_msg'] ) ) {
                        $atts['settings']['password_protect_show_login_msg'] = '';
                    }
                    if( $atts['settings']['password_protect_show_login_msg']=='true' ) {
                        if( !isset( $atts['settings']['password_protect_login_msg'] ) ) {
                            $atts['settings']['password_protect_login_msg'] = __( 'You do not have permission to submit this form!', 'super-forms' );
                        }
                        
                        // @since 1.0.1 - show only after form submit
                        if( !isset( $atts['settings']['password_protect_show_login_after_submit'] ) ) $atts['settings']['password_protect_show_login_after_submit'] = '';
                        if( $atts['settings']['password_protect_show_login_after_submit']=='true' ) {
                            if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                                if( (isset($_REQUEST['action'])) && ($_REQUEST['action']=='super_send_email') ) {
                                    SUPER_Common::output_error(
                                        $error = true,
                                        $msg = $atts['settings']['password_protect_login_msg'],
                                        $redirect = null
                                    );
                                }             
                            }
                        }else{
                            if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                                SUPER_Common::output_error(
                                    $error = true,
                                    $msg = $atts['settings']['password_protect_login_msg'],
                                    $redirect = null
                                );               
                            }
                            $msg  = '<div class="super-msg super-error">';
                            $msg .= $atts['settings']['password_protect_login_msg'];
                            $msg .= '<span class="close"></span>';
                            $msg .= '</div>';
                            return $result.$msg;
                        }
                    }

                    // @since 1.0.2 - If user didn't choose any setting option at least show error to the user
                    if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                        if( (isset($_REQUEST['action'])) && ($_REQUEST['action']=='super_send_email') ) {
                            SUPER_Common::output_error(
                                $error = true,
                                $msg = $atts['settings']['password_protect_login_msg'],
                                $redirect = null
                            );
                        }             
                    }

                }
            }

            // Return message for locked out users
            if( $atts['settings']['password_protect_roles']=='true' ) {
                if( !isset( $atts['settings']['password_protect_show_msg'] ) ) {
                    $atts['settings']['password_protect_show_msg'] = '';
                }
                if( !isset( $atts['settings']['password_protect_msg'] ) ) {
                    $atts['settings']['password_protect_msg'] = __( 'You are currently not logged in. In order to submit the form make sure you are logged in!', 'super-forms' );
                }
                // Check if the users doesn't have the propper user role
                global $current_user;
                if( (!isset( $atts['settings']['password_protect_user_roles'] )) || ($atts['settings']['password_protect_user_roles']=='') ) {
                    $atts['settings']['password_protect_user_roles'] = array();
                }
                $allowed_roles = $atts['settings']['password_protect_user_roles'];
                $allowed = false;
                foreach( $current_user->roles as $v ) {
                    if( in_array( $v, $allowed_roles ) ) {
                        $allowed = true;
                    }
                }
                if( $allowed==false ) {
                    if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $atts['settings']['password_protect_msg'],
                            $redirect = null
                        );               
                    }
                    if( $atts['settings']['password_protect_show_msg']=='true' ) {
                        $msg  = '<div class="super-msg super-error">';
                        $msg .= $atts['settings']['password_protect_msg'];
                        $msg .= '<span class="close"></span>';
                        $msg .= '</div>';
                        return $result.$msg;
                    }
                }
            }
            return $result;

        }


        /**
         * Hook into settings and add Password Protect settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
            global $wp_roles;
            $all_roles = $wp_roles->roles;
            $editable_roles = apply_filters( 'editable_roles', $all_roles );
            $roles = array(
                '' => __( 'All user roles', 'super-forms' )
            );
            foreach( $editable_roles as $k => $v ) {
                $roles[$k] = $v['name'];
            }
            $reg_roles = $roles;
            unset($reg_roles['']);
            $array['password_protect'] = array(        
                'hidden' => 'settings',
                'name' => __( 'Password Protect', 'super-forms' ),
                'label' => __( 'Password Protect Settings', 'super-forms' ),
                'fields' => array(
                    'password_protect' => array(
                        'desc' => __( 'Use a password to protect the form', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect', $settings['settings'], '' ),
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Enable password protection', 'super-forms' ),
                        )
                    ),
                    'password_protect_password' => array(
                        'name' => __( 'Password', 'super-forms' ),
                        'desc' => __( 'Enter a password to protect the form', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_password', $settings['settings'], wp_generate_password( 24 ) ),
                        'filter' => true,
                        'parent' => 'password_protect',
                        'filter_value' => 'true',
                    ),
                    'password_protect_incorrect_msg' => array(
                        'name' => __( 'Incorrect password message', 'super-forms' ), 
                        'desc' => __( 'The message to display when an incorrect password was entered', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_incorrect_msg', $settings['settings'], __( 'Incorrect password, please try again!', 'super-forms' ) ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'password_protect',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    'password_protect_roles' => array(
                        'desc' => __( 'Allows only specific user roles to submit the form', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_roles', $settings['settings'], '' ),
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Allow only specific user roles', 'super-forms' ),
                        )
                    ),
                    'password_protect_user_roles' => array(
                        'name' => __( 'Use CTRL or SHIFT to select multiple roles', 'super-forms' ),
                        'desc' => __( 'Select all user roles who are allowed to submit the form', 'super-forms' ),
                        'type' => 'select',
                        'multiple' => true,
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_user_roles', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'password_protect_roles',
                        'filter_value' => 'true',
                        'values' => $reg_roles,
                    ),
                    'password_protect_hide' => array(
                        'desc' => __( 'Hide the form from locked out users', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_hide', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Hide form from locked out users', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_roles',
                        'filter_value' => 'true',
                    ),
                    'password_protect_show_msg' => array(
                        'desc' => __( 'Display a message to the locked out user', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_show_msg', $settings['settings'], 'true' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Display a message to the locked out user', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_roles',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'password_protect_msg' => array(
                        'name' => __( 'Message for locked out users', 'super-forms' ), 
                        'desc' => __( 'The message to display to locked out users', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_msg', $settings['settings'], __( 'You do not have permission to submit this form!', 'super-forms' ) ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'password_protect_show_msg',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    'password_protect_login' => array(
                        'desc' => __( 'Allow only logged in users to submit the form', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_login', $settings['settings'], '' ),
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Allow only logged in users', 'super-forms' ),
                        )
                    ),
                    'password_protect_login_hide' => array(
                        'desc' => __( 'Hide the form from not logged in users', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_login_hide', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Hide form from not logged in users', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_login',
                        'filter_value' => 'true',
                    ),
                    'password_protect_show_login_msg' => array(
                        'desc' => __( 'Display a message to the logged out user', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_show_login_msg', $settings['settings'], 'true' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => __( 'Display a message to the logged out user', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_login',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'password_protect_login_msg' => array(
                        'name' => __( 'Message for not logged in users', 'super-forms' ), 
                        'desc' => __( 'The message to display to none logged in users', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_login_msg', $settings['settings'], __( 'You are currently not logged in. In order to submit the form make sure you are logged in!', 'super-forms' ) ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'password_protect_show_login_msg',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    // @since 1.0.1 - option to only display the error message after form submit (instead of both on form init and submit)
                    'password_protect_show_login_after_submit' => array(
                        'desc' => __( 'Only display the message after the user tried to submit the form', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_show_login_after_submit', $settings['settings'], 'true' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Only display after user tried to submit the form', 'super-forms' ),
                        ),
                        'filter'=>true,
                        'parent' => 'password_protect_show_login_msg',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    // @since 1.0.3 - option to hide form for logged in users
                    'password_protect_not_login_hide' => array(
                        'desc' => __( 'Hide the form from logged in users', 'super-forms' ), 
                        'default' => SUPER_Settings::get_value( 0, 'password_protect_not_login_hide', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Hide form from logged in users', 'super-forms' ),
                        ),
                    ),
                )
            );
            return $array;
        }



    }
        
endif;


/**
 * Returns the main instance of SUPER_Password_Protect to prevent the need to use globals.
 *
 * @return SUPER_Password_Protect
 */
function SUPER_Password_Protect() {
    return SUPER_Password_Protect::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_Password_Protect'] = SUPER_Password_Protect();