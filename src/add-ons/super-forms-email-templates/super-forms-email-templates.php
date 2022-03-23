<?php
/**
 * Super Forms - E-mail Templates
 *
 * @package   Super Forms - E-mail Templates
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - E-mail Templates
 * Description: Adds an extra email template to choose from
 * Version:     1.2.2
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

if( !class_exists('SUPER_Email_Templates') ) :


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
        public $version = '1.2.2';

        
        /**
         * @var string
         *
         *  @since      1.0.1
        */
        public $add_on_slug = 'email-templates';
        public $add_on_name = 'E-mail Templates';

        
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
            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_settings_after_email_template_filter', array( $this, 'add_settings' ), 10, 2 );
            }
            // Following filters must be called outside "admin" scope, because some features will trigger outside of it
            // For instance with the WooCommerce Checkout feature whenever the Order status changed to "completed" it should fire the below filters
            add_filter( 'super_before_sending_email_body_filter', array( $this, 'create_new_body' ), 50, 2 );
            add_filter( 'super_before_sending_confirm_body_filter', array( $this, 'create_new_confirm_body' ), 50, 2 );
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
         * Hook into settings and add Register & Login settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $x ) {
			$array['email_template']['fields']['email_template']['values']['email_template_1'] = esc_html__( 'E-mail Template 1', 'super-forms' );
			$new_fields = array(
	        	'email_template_1_logo' => array(
	                'name' => esc_html__( 'E-mail logo', 'super-forms' ),
	                'desc' => esc_html__( 'Upload a logo to use for this email template', 'super-forms' ),
	                'default' => '',
	                'type' => 'image',
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1', 
	            ),
	            'email_template_1_title' => array(
	                'name' => esc_html__( 'E-mail title', 'super-forms' ),
	                'desc' => esc_html__( 'A title to display below your logo', 'super-forms' ),
	                'default' => esc_html__( 'Your title', 'super-forms' ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
                    'i18n'=>true
            	),
            	'email_template_1_confirm_title' => array(
	                'name' => esc_html__( 'E-mail title (confirm)', 'super-forms' ),
	                'desc' => esc_html__( 'A title to display below your logo (used for confirmation emails)', 'super-forms' ),
	                'default' => esc_html__( 'Your title', 'super-forms' ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
                    'i18n'=>true
	            ),
	            'email_template_1_subtitle' => array(
	                'name' => esc_html__( 'E-mail subtitle', 'super-forms' ),
	                'desc' => esc_html__( 'A subtitle to display before the email body (content)', 'super-forms' ),
	                'default' => esc_html__( 'Your subtitle', 'super-forms' ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
                    'i18n'=>true
	            ),
	            'email_template_1_confirm_subtitle' => array(
	                'name' => esc_html__( 'E-mail subtitle (confirm)', 'super-forms' ),
	                'desc' => esc_html__( 'A subtitle to display before the email body (used for confirmation emails)', 'super-forms' ),
	                'default' => esc_html__( 'Your subtitle', 'super-forms' ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1', 
                    'allow_empty' => true,
                    'i18n'=>true
	            ),
	            'email_template_1_copyright' => array(
	                'name' => esc_html__( 'E-mail copyright', 'super-forms' ),
	                'desc' => esc_html__( 'Enter anything you like for the copyright section', 'super-forms' ),
	                'default' => esc_html__( '&copy; Someone, somewhere 2016', 'super-forms' ),
	                'placeholder' => esc_html__( '&copy; Someone, somewhere 2015', 'super-forms' ),
	                'type' => 'textarea',
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
                    'i18n'=>true
	            ),
	            'email_template_1_socials' => array(
	                'name' => esc_html__( 'E-mail social icons', 'super-forms' ),
                    'desc' => esc_html__( 'Put each social icon on a new line', 'super-forms' ),
	                'label' => sprintf( esc_html__( 'Put each on a new line, seperate values by pipes%s%sExample:%s http://facebook.com/company|http://domain.com/fb-icon.png|Facebook', 'super-forms' ), '<br />', '<strong>', '</strong>' ),
	                'default' => 'url_facebook_page|url_social_icon|Facebook',
	                'placeholder' =>  'url_facebook_page|url_social_icon|Facebook',
	                'type' => 'textarea',
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
                    'allow_empty' => true,
	            ),
	            'email_template_1_header_colors' => array(
	                'name' => esc_html__( 'Header colors', 'super-forms' ),
	                'type' => 'multicolor', 
	                'colors' => array(
	                    'email_template_1_header_bg_color' => array(
	                        'label' => 'Header background color',
	                		'default' => '#5ba1d3'
	                    ),
	                    'email_template_1_header_title_color' => array(
	                        'label' => 'Header title color',
	                		'default' => '#ffffff'
	                    ),
	                ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
	            ),
	            'email_template_1_body_colors' => array(
	                'name' => esc_html__( 'Body colors', 'super-forms' ),
	                'type' => 'multicolor', 
	                'colors' => array(
	                    'email_template_1_body_bg_color' => array(
	                        'label' => 'Body background color',
	                		'default' => '#ffffff'
	                    ),
	                    'email_template_1_body_subtitle_color' => array(
	                        'label' => 'Body subtitle color',
	                		'default' => '#474747'
	                    ),
	                    'email_template_1_body_font_color' => array(
	                        'label' => 'Body font color',
	                		'default' => '#9e9e9e'
	                    ),            
	                ),
	                'filter' => true,
	                'parent' => 'email_template',
	                'filter_value' => 'email_template_1',
	            ),    
	            'email_template_1_footer_colors' => array(
	                'name' => esc_html__( 'Footer colors', 'super-forms' ),
	                'type' => 'multicolor', 
	                'colors' => array(
	                    'email_template_1_footer_bg_color' => array(
	                        'label' => 'Footer background color',
	                		'default' => '#ee4c50'
	                    ),
	                    'email_template_1_footer_font_color' => array(
	                        'label' => 'Footer font color',
	                		'default' => '#ffffff'
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
                $email_body .= '<img src="' . esc_url($logo) . '" alt="' . esc_attr($header_title) . '" style="padding: 0px 0 30px 0;display: block;" />';
                $email_body .= '<p style="margin:0;">' . esc_html($header_title) . '</p>';
            }else{
                $email_body .= '<p style="margin:0;">' . esc_html($header_title) . '</p>';
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
		                    $email_body .= '<a href="' . esc_url($exploded[0]) . '" target="_blank" style="color:#ffffff;">';
		                        $email_body .= '<img src="' . esc_url($exploded[1]) . '" alt="' . esc_attr($exploded[2]) . '" style="padding-left:5px;display: block;" border="0" />';
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
if( !function_exists('SUPER_Email_Templates') ){
    function SUPER_Email_Templates() {
        return SUPER_Email_Templates::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Email_Templates'] = SUPER_Email_Templates();
}
