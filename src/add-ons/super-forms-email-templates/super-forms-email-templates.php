<?php
/**
 * Super Forms - Email Templates
 *
 * @package   Super Forms - Email Templates
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Email Templates
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Adds an extra email template to choose from
 * Version:     1.0.5
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if( !class_exists( 'SUPER_Email_Templates' ) ) :


    /**
     * Main SUPER_Email_Templates Class
     *
     * @class SUPER_Email_Templates
     * @version	1.0.0
     */
    final class SUPER_Email_Templates {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.0.5';

        
        /**
         * @var string
         *
         *  @since      1.0.1
        */
        public $add_on_slug = 'email_templates';
        public $add_on_name = 'Email Templates';

        
        /**
         * @var SUPER_Email_Templates The single instance of the class
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
         * Main SUPER_Email_Templates Instance
         *
         * Ensures only one instance of SUPER_Email_Templates is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Email_Templates()
         * @return SUPER_Email_Templates - Main instance
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
         * SUPER_Email_Templates Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_email_templates_loaded');
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

            // Filters since 1.0.1
            add_filter( 'super_after_activation_message_filter', array( $this, 'activation_message' ), 10, 2 ); 

            if ( $this->is_request( 'frontend' ) ) {
                
            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_settings_after_email_template_filter', array( $this, 'add_settings' ), 10, 2 );
                add_filter( 'super_before_sending_email_body_filter', array( $this, 'create_new_body' ), 50, 2 );
                add_filter( 'super_before_sending_confirm_body_filter', array( $this, 'create_new_confirm_body' ), 50, 2 );

                // Filters since 1.0.2
                add_filter( 'super_settings_end_filter', array( $this, 'activation' ), 100, 2 );
                add_action( 'init', array( $this, 'update_plugin' ) );

                // Actions since 1.0.3
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) ); 

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
         *  @since      1.0.1
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
         * @since       1.0.1
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
         *  @since      1.0.1
         */
        public static function deactivate(){
            if (method_exists('SUPER_Forms','add_on_deactivate')) {
                SUPER_Forms::add_on_deactivate(SUPER_Email_Templates()->add_on_slug);
            }
        }


        /**
         * Check license and show activation message
         * 
         * @since       1.0.1
        */
        public function activation_message( $activation_msg, $data ) {
            if (method_exists('SUPER_Forms','add_on_activation_message')) {
                $form_id = absint($data['id']);
                $settings = $data['settings'];
                if( (isset($settings['email_template'])) && ($settings['email_template']!='default_email_template') ) {
                    return SUPER_Forms::add_on_activation_message($activation_msg, $this->add_on_slug, $this->add_on_name);
                }
            }
            return $activation_msg;
        }


        /**
         * Hook into settings and add Register & Login settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
			$array['email_template']['fields']['email_template']['values']['email_template_1'] = __( 'Email Template 1', 'super-forms' );
			$new_fields = array(
	        	'email_template_1_logo' => array(
	                'name' => __( 'Email logo', 'super-forms' ),
	                'desc' => __( 'Upload a logo to use for this email template', 'super-forms' ),
	                'default' => SUPER_Settings::get_value( 0, 'email_template_1_logo', $settings['settings'], '' ),
	                'type' => 'image',
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1', 
	            ),
	            'email_template_1_title' => array(
	                'name' => __( 'Email title', 'super-forms' ),
	                'desc' => __( 'A title to display below your logo', 'super-forms' ),
	                'default' => SUPER_Settings::get_value( 0, 'email_template_1_title', $settings['settings'], __( 'Your title', 'super-forms' ) ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
            	),
            	'email_template_1_confirm_title' => array(
	                'name' => __( 'Email title (confirm)', 'super-forms' ),
	                'desc' => __( 'A title to display below your logo (used for confirmation emails)', 'super-forms' ),
	                'default' => SUPER_Settings::get_value( 0, 'email_template_1_confirm_title', $settings['settings'], __( 'Your title', 'super-forms' ) ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
	            ),
	            'email_template_1_subtitle' => array(
	                'name' => __( 'Email subtitle', 'super-forms' ),
	                'desc' => __( 'A subtitle to display before the email body (content)', 'super-forms' ),
	                'default' => SUPER_Settings::get_value( 0, 'email_template_1_subtitle', $settings['settings'], __( 'Your subtitle', 'super-forms' ) ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
	            ),
	            'email_template_1_confirm_subtitle' => array(
	                'name' => __( 'Email subtitle (confirm)', 'super-forms' ),
	                'desc' => __( 'A subtitle to display before the email body (used for confirmation emails)', 'super-forms' ),
	                'default' => SUPER_Settings::get_value( 0, 'email_template_1_confirm_subtitle', $settings['settings'], __( 'Your subtitle', 'super-forms' ) ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1', 
                    'allow_empty' => true,
	            ),
	            'email_template_1_copyright' => array(
	                'name' => __( 'Email copyright', 'super-forms' ),
	                'desc' => __( 'Enter anything you like for the copyright section', 'super-forms' ),
	                'default' => SUPER_Settings::get_value( 0, 'email_template_1_copyright', $settings['settings'], __( '&copy; Someone, somewhere 2016', 'super-forms' ) ),
	                'placeholder' => __( '&copy; Someone, somewhere 2015', 'super-forms' ),
	                'type' => 'textarea',
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
	            ),
	            'email_template_1_socials' => array(
	                'name' => __( 'Email social icons', 'super-forms' ),
                    'desc' => __( 'Put each social icon on a new line', 'super-forms' ),
	                'label' => __( 'Put each on a new line, seperate values by pipes<br /><strong>Example:</strong> http://facebook.com/company|http://domain.com/fb-icon.png|Facebook', 'super-forms' ),
	                'default' => SUPER_Settings::get_value( 0, 'email_template_1_socials', $settings['settings'], 'url_facebook_page|url_social_icon|Facebook' ),
	                'placeholder' =>  'url_facebook_page|url_social_icon|Facebook',
	                'type' => 'textarea',
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
	            ),
	            'email_template_1_header_colors' => array(
	                'name' => __( 'Header colors', 'super-forms' ),
	                'type' => 'multicolor', 
	                'colors' => array(
	                    'email_template_1_header_bg_color' => array(
	                        'label' => 'Header background color',
	                		'default' => SUPER_Settings::get_value( 0, 'email_template_1_header_bg_color', $settings['settings'], '#5ba1d3' ),
	                    ),
	                    'email_template_1_header_title_color' => array(
	                        'label' => 'Header title color',
	                		'default' => SUPER_Settings::get_value( 0, 'email_template_1_header_title_color', $settings['settings'], '#ffffff' ),
	                    ),
	                ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
	            ),
	            'email_template_1_body_colors' => array(
	                'name' => __( 'Body colors', 'super-forms' ),
	                'type' => 'multicolor', 
	                'colors' => array(
	                    'email_template_1_body_bg_color' => array(
	                        'label' => 'Body background color',
	                		'default' => SUPER_Settings::get_value( 0, 'email_template_1_body_bg_color', $settings['settings'], '#ffffff' ),
	                    ),
	                    'email_template_1_body_subtitle_color' => array(
	                        'label' => 'Body subtitle color',
	                		'default' => SUPER_Settings::get_value( 0, 'email_template_1_body_subtitle_color', $settings['settings'], '#474747' ),
	                    ),
	                    'email_template_1_body_font_color' => array(
	                        'label' => 'Body font color',
	                		'default' => SUPER_Settings::get_value( 0, 'email_template_1_body_font_color', $settings['settings'], '#9e9e9e' ),
	                    ),            
	                ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
	            ),    
	            'email_template_1_footer_colors' => array(
	                'name' => __( 'Footer colors', 'super-forms' ),
	                'type' => 'multicolor', 
	                'colors' => array(
	                    'email_template_1_footer_bg_color' => array(
	                        'label' => 'Footer background color',
	                		'default' => SUPER_Settings::get_value( 0, 'email_template_1_footer_bg_color', $settings['settings'], '#ee4c50' ),
	                    ),
	                    'email_template_1_footer_font_color' => array(
	                        'label' => 'Footer font color',
	                		'default' => SUPER_Settings::get_value( 0, 'email_template_1_footer_font_color', $settings['settings'], '#ffffff' ),
	                    ),
	                ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
	            )
			);
	        $new_array = array_merge( $array['email_template']['fields'], $new_fields );
			$array['email_template']['fields'] = $new_array;
			return $array;
        }


        /**
         * Hook into email body html before sending email
         */
        public function create_new_body( $email_body, $attr ) {
            return self::body_html( $email_body, $attr, 'admin' );
        }

        /**
         * Hook into confirm body html before sending email
         */
        public function create_new_confirm_body( $email_body, $attr ) {
            return self::body_html( $email_body, $attr, 'confirm' );
        }


        /**
         * Create the new email with the email body
         *
         * @param  string $email_body
         * @param  array $attr
         * @param  string $type
         *
         *  @since      1.0.0
        */
        public function body_html( $email_body, $attr, $type='admin' ) {
            
            if( $attr['settings']['email_template']!='email_template_1' ) {
                return $email_body;   
            }
            $settings_prefix = 'email_template_1_';

            // @since 1.0.1 - RTL support
            $rtl = '';
            if( (isset( $attr['settings']['theme_rtl'] )) && ($attr['settings']['theme_rtl']=='true') ) {
                $rtl = 'true';
            }

            $header_bg_color = $attr['settings'][$settings_prefix.'header_bg_color'];
            $header_title_color = $attr['settings'][$settings_prefix.'header_title_color'];
            $header_logo = $attr['settings'][$settings_prefix.'logo'];
            $body_bg_color = $attr['settings'][$settings_prefix.'body_bg_color'];
            $body_subtitle_color = $attr['settings'][$settings_prefix.'body_subtitle_color'];
            $body_font_color = $attr['settings'][$settings_prefix.'body_font_color'];
            $footer_bg_color = $attr['settings'][$settings_prefix.'footer_bg_color'];
            $footer_font_color = $attr['settings'][$settings_prefix.'footer_font_color'];
            $footer_socials = $attr['settings'][$settings_prefix.'socials'];
            $footer_copyright = $attr['settings'][$settings_prefix.'copyright'];
            if( $footer_copyright!='') {
            	$footer_copyright = SUPER_Common::email_tags( $footer_copyright, $attr['data'] );
            }

            if( $type=='confirm' ) {
                $settings_prefix = 'email_template_1_confirm_';
            }
            $header_title = $attr['settings'][$settings_prefix.'title'];
            if( $header_title!='') {
            	$header_title = SUPER_Common::email_tags( $header_title, $attr['data'] );
            }
            $body_subtitle = $attr['settings'][$settings_prefix.'subtitle'];
            if( $body_subtitle!='') {
            	$body_subtitle = SUPER_Common::email_tags( $body_subtitle, $attr['data'] );
            }

            $old_email_body = $email_body;
            $email_body  = '<body style="margin: 0; padding: 0;">';
            $email_body .= '<table border="0" cellpadding="0" cellspacing="0" width="100%">';
            $email_body .= '<tr>';
            $email_body .= '<td style="padding: 10px 0 30px 0;">';
            $email_body .= '<table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border: 1px solid #cccccc; border-collapse: collapse;">';
            $email_body .= '<tr>';
            $email_body .= '<td align="center" bgcolor="'.$header_bg_color.'" style="padding: 40px 0 30px 0; color: '.$header_title_color.'; font-size: 28px; font-weight: bold; font-family: Arial, sans-serif;">';
            $logo = wp_get_attachment_image_src($header_logo, 'full' );
            $logo = !empty( $logo[0] ) ? $logo[0] : '';
            if( !empty( $logo ) ) {
                $email_body .= '<img src="' . $logo . '" alt="' . $header_title . '" style="padding: 0px 0 30px 0;display: block;" />';
                $email_body .= $header_title;
            }else{
                $email_body .= $header_title;
            }
            $email_body .= '</td>';
            $email_body .= '</tr>';
            $email_body .= '<tr>';
            $email_body .= '<td bgcolor="'.$body_bg_color.'" style="padding: 40px 30px 40px 30px;">';
            $email_body .= '<table border="0" cellpadding="0" cellspacing="0" width="100%"' . ($rtl=='true' ? ' style="text-align:right;"' : '') . '>';
            if( $body_subtitle!='') {
	            $email_body .= '<tr>';
	            $email_body .= '<td style="color: '.$body_subtitle_color.'; font-family: Arial, sans-serif; font-size: 24px;">';
	            $email_body .= '<b>'.$body_subtitle.'</b>';
	            $email_body .= '</td>';
	            $email_body .= '</tr>';
	        }
            $email_body .= '<tr>';
            $email_body .= '<td style="padding: 20px 0 0 0; color: '.$body_font_color.'; font-family: Arial, sans-serif; font-size: 16px; line-height: 20px;">';
            $email_body .= $old_email_body;
            $email_body .= '</td>';
            $email_body .= '</tr>';
            $email_body .= '</table>';
            $email_body .= '</td>';
            $email_body .= '</tr>';
            $email_body .= '<tr>';
            $email_body .= '<td bgcolor="'.$footer_bg_color.'" style="padding: 30px 30px 30px 30px;">';
            $email_body .= '<table border="0" cellpadding="0" cellspacing="0" width="100%">';
            $email_body .= '<tr>';
            $column_width = 75;
            if( $footer_socials!='' ) {
            	$column_width = 100;
            }
            $email_body .= '<td style="color:'.$footer_font_color.'; font-family: Arial, sans-serif; font-size: 14px;" width="'.$column_width.'%">';
            $email_body .= nl2br($footer_copyright);
            $email_body .= '</td>';
            if( $footer_socials!='' ) {
            	$email_body .= '<td align="right" width="25%">';
            	$email_body .= '<table border="0" cellpadding="0" cellspacing="0">';
            	$email_body .= '<tr>';
            	$socials = explode( "\n", $footer_socials );
				foreach( $socials as $v ) {
	                $exploded = explode('|', $v);
                    if(!isset($exploded[1])) $exploded[1] = '';
                    if(!isset($exploded[2])) $exploded[2] = '';
	                if( ( $exploded[0]!='' ) && ( $exploded[1]!='' ) ) {
		                $email_body .= '<td style="font-family: Arial, sans-serif; font-size: 12px; font-weight: bold;">';
		                    $email_body .= '<a href="' . $exploded[0] . '" target="_blank" style="color:#ffffff;">';
		                        $email_body .= '<img src="' . $exploded[1] . '" alt="' . $exploded[2] . '" style="padding-left:5px;display: block;" border="0" />';
		                    $email_body .= '</a>';
		                $email_body .= '</td>';
	            	}
				}
	            $email_body .= '</tr>';
	            $email_body .= '</table>';
        	}
            $email_body .= '</td>';
            $email_body .= '</tr>';
            $email_body .= '</table>';
            $email_body .= '</td>';
            $email_body .= '</tr>';
            $email_body .= '</table>';
            $email_body .= '</td>';
            $email_body .= '</tr>';
            $email_body .= '</table>';
            return $email_body;
        }
    }
        
endif;


/**
 * Returns the main instance of SUPER_Email_Templates to prevent the need to use globals.
 *
 * @return SUPER_Email_Templates
 */
function SUPER_Email_Templates() {
    return SUPER_Email_Templates::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_Email_Templates'] = SUPER_Email_Templates();