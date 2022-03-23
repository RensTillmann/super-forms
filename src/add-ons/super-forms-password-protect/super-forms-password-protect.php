<?php
/**
 * Super Forms - Password Protect
 *
 * @package   Super Forms - Password Protect
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Password Protect
 * Description: Password protect your forms or lock out specific user roles from submitting the form
 * Version:     1.4.0
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

if( !class_exists('SUPER_Password_Protect') ) :


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
        public $version = '1.4.0';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'password-protect';
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
            
            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
            
            if ( $this->is_request( 'frontend' ) ) {
                add_filter( 'super_form_before_do_shortcode_filter', array( $this, 'hide_form' ), 10, 2 );
                add_filter( 'super_form_before_first_form_element_filter', array( $this, 'locked_msg' ), 10, 2 );
            }
            
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
            }
            
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_sending_email_hook', array( $this, 'before_sending_email' ) );
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
         * Hook into before sending email and do password protect check
         *
         *  @since      1.0.0
        */
        public static function before_sending_email( $x ) {
            extract( shortcode_atts( array( 'data'=>array(), 'post'=>array(), 'settings'=>array()), $x ) );
            if(!isset($data)) return false;
            if(!isset($data['hidden_form_id'])) return false;
            $form_id = $data['hidden_form_id']['value'];
            $x['id'] = $form_id;
            SUPER_Password_Protect()->locked_msg( '', $x );
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
                                SUPER_Common::output_message(
                                    $error = true,
                                    $msg = $atts['settings']['password_protect_login_msg'],
                                    $redirect = null
                                );               
                            }
                            $msg  = '<div id="super-form-' . $atts['id'] . '" class="super-form super-form-' . $atts['id'] . '">';
                                $msg .= '<div class="super-msg super-error">';
                                $msg .= $atts['settings']['password_protect_login_msg'];
                                $msg .= '<span class="super-close"></span>';
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
        public static function locked_msg( $result, $x ) {
            extract( shortcode_atts( array( 'id'=>'', 'data'=>array(), 'post'=>array(), 'settings'=>array()), $x ) );
            $form_id = $id;

            if( !isset( $settings['password_protect'] ) ) $settings['password_protect'] = '';
            if( !isset( $settings['password_protect_login'] ) ) $settings['password_protect_login'] = '';
            if( !isset( $settings['password_protect_roles'] ) ) $settings['password_protect_roles'] = '';

            // Check if password protect is enabled
            if( $settings['password_protect']=='true' ) {

                if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                    
                    if ( !SUPER_Password_Protect()->is_request( 'admin' ) ) {
	                    // Before we proceed, lets check if we have a password field
	                    if( !isset( $data['password'] ) ) {
	                        $msg = sprintf( esc_html__( 'We couldn\'t find the %1$s field which is required in order to password protect the form. Please %2$sedit%3$s your form and try again', 'super-forms' ), '<strong>password</strong>', '<a href="' . esc_url(get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $form_id )) . '">', '</a>' );
	                        SUPER_Common::output_message(
	                            $error = true,
	                            $msg = $msg,
	                            $redirect = null
	                        );
	                    }
	                }

                    // Now lets check if the passwords are incorrect
					if( (isset($_REQUEST['action'])) && ($_REQUEST['action']=='super_submit_form') ) {
						if( $data['password']['value']!=$settings['password_protect_password'] ) {
	                        if( !isset( $settings['password_protect_incorrect_msg'] ) ) {
	                            $settings['password_protect_incorrect_msg'] = esc_html__( 'Incorrect password, please try again!', 'super-forms' );
	                        }
	                        SUPER_Common::output_message(
	                            $error = true,
	                            $msg = $settings['password_protect_incorrect_msg'],
	                            $redirect = null
	                        );               
	                    }
	                }
                }

                $elements = get_post_meta( absint($form_id), '_super_elements', true );
                if(!is_array($elements)){
                    $elements = json_decode( $elements, true );
                }
                $elements_json = json_encode($elements);
                $field_found = strpos($elements_json, '"name":"password"');
                if ($field_found === false) {
                    $msg  = '<div class="super-msg super-error">';
                    $msg .= sprintf( esc_html__( 'You have enabled password protection for this form, but we couldn\'t find a password field with the name: %1$s. Please %2$sedit%3$s your form and try again.', 'super-forms' ), '<strong>password</strong>', '<a href="' . esc_url(get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $form_id )) . '">', '</a>' );
                    $msg .= '<span class="super-close"></span>';
                    $msg .= '</div>';
                    return $result.$msg;
                }
            }

            // Return message for non logged in users
            if( $settings['password_protect_login']=='true' ) {
                if ( !is_user_logged_in() ) {
                    if( !isset( $settings['password_protect_show_login_msg'] ) ) {
                        $settings['password_protect_show_login_msg'] = '';
                    }
                    if( $settings['password_protect_show_login_msg']=='true' ) {
                        if( !isset( $settings['password_protect_login_msg'] ) ) {
                            $settings['password_protect_login_msg'] = esc_html__( 'You do not have permission to submit this form!', 'super-forms' );
                        }
                        
                        // @since 1.0.1 - show only after form submit
                        if( !isset( $settings['password_protect_show_login_after_submit'] ) ) $settings['password_protect_show_login_after_submit'] = '';
                        if( $settings['password_protect_show_login_after_submit']=='true' ) {
                            if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                                if( (isset($_REQUEST['action'])) && ($_REQUEST['action']=='super_submit_form') ) {
                                    SUPER_Common::output_message(
                                        $error = true,
                                        $msg = $settings['password_protect_login_msg'],
                                        $redirect = null
                                    );
                                }             
                            }
                        }else{
                            if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                                SUPER_Common::output_message(
                                    $error = true,
                                    $msg = $settings['password_protect_login_msg'],
                                    $redirect = null
                                );
                            }
                            $msg  = '<div class="super-msg super-error">';
                            $msg .= $settings['password_protect_login_msg'];
                            $msg .= '<span class="super-close"></span>';
                            $msg .= '</div>';
                            return $result.$msg;
                        }
                    }

                    // @since 1.0.2 - If user didn't choose any setting option at least show error to the user
                    if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                        if( (isset($_REQUEST['action'])) && ($_REQUEST['action']=='super_submit_form') ) {
                            SUPER_Common::output_message(
                                $error = true,
                                $msg = $settings['password_protect_login_msg'],
                                $redirect = null
                            );
                        }             
                    }

                }
            }

            // Return message for locked out users
            if( $settings['password_protect_roles']=='true' ) {
                if( !isset( $settings['password_protect_show_msg'] ) ) {
                    $settings['password_protect_show_msg'] = '';
                }
                if( !isset( $settings['password_protect_msg'] ) ) {
                    $settings['password_protect_msg'] = esc_html__( 'You are currently not logged in. In order to submit the form make sure you are logged in!', 'super-forms' );
                }
                // Check if the users doesn't have the propper user role
                global $current_user;
                if( (!isset( $settings['password_protect_user_roles'] )) || ($settings['password_protect_user_roles']=='') ) {
                    $settings['password_protect_user_roles'] = array();
                }
                $allowed_roles = $settings['password_protect_user_roles'];
                $allowed = false;
                foreach( $current_user->roles as $v ) {
                    if( in_array( $v, $allowed_roles ) ) {
                        $allowed = true;
                    }
                }
                if( $allowed==false ) {
                    if ( SUPER_Password_Protect()->is_request( 'ajax' ) ) {
                        SUPER_Common::output_message(
                            $error = true,
                            $msg = $settings['password_protect_msg'],
                            $redirect = null
                        );               
                    }
                    if( $settings['password_protect_show_msg']=='true' ) {
                        $msg  = '<div class="super-msg super-error">';
                        $msg .= $settings['password_protect_msg'];
                        $msg .= '<span class="super-close"></span>';
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
            $array['password_protect'] = array(        
                'hidden' => 'settings',
                'name' => esc_html__( 'Password Protect', 'super-forms' ),
                'label' => esc_html__( 'Password Protect Settings', 'super-forms' ),
                'fields' => array(
                    'password_protect' => array(
                        'desc' => esc_html__( 'Use a password to protect the form', 'super-forms' ), 
                        'default' =>  '',
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Enable password protection', 'super-forms' ),
                        )
                    ),
                    'password_protect_password' => array(
                        'name' => esc_html__( 'Password', 'super-forms' ),
                        'desc' => esc_html__( 'Enter a password to protect the form', 'super-forms' ),
                        'default' =>  wp_generate_password( 24 ),
                        'filter' => true,
                        'parent' => 'password_protect',
                        'filter_value' => 'true',
                    ),
                    'password_protect_incorrect_msg' => array(
                        'name' => esc_html__( 'Incorrect password message', 'super-forms' ), 
                        'desc' => esc_html__( 'The message to display when an incorrect password was entered', 'super-forms' ), 
                        'default' =>  esc_html__( 'Incorrect password, please try again!', 'super-forms' ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'password_protect',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    'password_protect_roles' => array(
                        'desc' => esc_html__( 'Allows only specific user roles to submit the form', 'super-forms' ), 
                        'default' =>  '',
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Allow only specific user roles', 'super-forms' ),
                        )
                    ),
                    'password_protect_user_roles' => array(
                        'name' => esc_html__( 'Use CTRL or SHIFT to select multiple roles', 'super-forms' ),
                        'desc' => esc_html__( 'Select all user roles who are allowed to submit the form', 'super-forms' ),
                        'type' => 'select',
                        'multiple' => true,
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'password_protect_roles',
                        'filter_value' => 'true',
                        'values' => $roles,
                    ),
                    'password_protect_hide' => array(
                        'desc' => esc_html__( 'Hide the form from locked out users', 'super-forms' ), 
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Hide form from locked out users', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_roles',
                        'filter_value' => 'true',
                    ),
                    'password_protect_show_msg' => array(
                        'desc' => esc_html__( 'Display a message to the locked out user', 'super-forms' ), 
                        'default' =>  'true',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display a message to the locked out user', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_roles',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'password_protect_msg' => array(
                        'name' => esc_html__( 'Message for locked out users', 'super-forms' ), 
                        'desc' => esc_html__( 'The message to display to locked out users', 'super-forms' ), 
                        'default' =>  esc_html__( 'You do not have permission to submit this form!', 'super-forms' ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'password_protect_show_msg',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    'password_protect_login' => array(
                        'desc' => esc_html__( 'Allow only logged in users to submit the form', 'super-forms' ), 
                        'default' =>  '',
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Allow only logged in users', 'super-forms' ),
                        )
                    ),
                    'password_protect_login_hide' => array(
                        'desc' => esc_html__( 'Hide the form from not logged in users', 'super-forms' ), 
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Hide form from not logged in users', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_login',
                        'filter_value' => 'true',
                    ),
                    'password_protect_show_login_msg' => array(
                        'desc' => esc_html__( 'Display a message to the logged out user', 'super-forms' ), 
                        'default' =>  'true',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display a message to the logged out user', 'super-forms' ),
                        ),
                        'parent' => 'password_protect_login',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'password_protect_login_msg' => array(
                        'name' => esc_html__( 'Message for not logged in users', 'super-forms' ), 
                        'desc' => esc_html__( 'The message to display to none logged in users', 'super-forms' ), 
                        'default' =>  esc_html__( 'You are currently not logged in. In order to submit the form make sure you are logged in!', 'super-forms' ),
                        'type' => 'textarea',
                        'filter'=>true,
                        'parent' => 'password_protect_show_login_msg',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    // @since 1.0.1 - option to only display the error message after form submit (instead of both on form init and submit)
                    'password_protect_show_login_after_submit' => array(
                        'desc' => esc_html__( 'Only display the message after the user tried to submit the form', 'super-forms' ), 
                        'default' =>  'true',
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Only display after user tried to submit the form', 'super-forms' ),
                        ),
                        'filter'=>true,
                        'parent' => 'password_protect_show_login_msg',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    // @since 1.0.3 - option to hide form for logged in users
                    'password_protect_not_login_hide' => array(
                        'desc' => esc_html__( 'Hide the form from logged in users', 'super-forms' ), 
                        'default' =>  '',
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Hide form from logged in users', 'super-forms' ),
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
if( !function_exists('SUPER_Password_Protect') ){
    function SUPER_Password_Protect() {
        return SUPER_Password_Protect::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Password_Protect'] = SUPER_Password_Protect();
}
