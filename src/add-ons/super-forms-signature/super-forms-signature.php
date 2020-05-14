<?php
/**
 * Super Forms - Signature
 *
 * @package   Super Forms - Signature
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2019 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Signature
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Adds an extra element that allows users to sign their signature before submitting the form
 * Version:     1.6.0
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Signature')) :


    /**
     * Main SUPER_Signature Class
     *
     * @class SUPER_Signature
     * @version	1.0.0
     */
    final class SUPER_Signature {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.6.0';


        /**
         * @var string
         *
         *  @since      1.1.0
        */
        public $add_on_slug = 'signature';
        public $add_on_name = 'Signature';

        
        /**
         * @var SUPER_Signature The single instance of the class
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
         * Main SUPER_Signature Instance
         *
         * Ensures only one instance of SUPER_Signature is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Signature()
         * @return SUPER_Signature - Main instance
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
         * SUPER_Signature Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_signature_loaded');
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
            
            // Actions since 1.0.0
            add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_signature_element' ), 10, 2 );

            if ( $this->is_request( 'frontend' ) ) {
                
                // Filters since 1.0.0
            	add_filter( 'super_form_styles_filter', array( $this, 'add_element_styles' ), 10, 2 );

                // Filters since 1.2.2
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 110, 2 );

                // Load scripts before Ajax request
                add_action( 'super_after_enqueue_element_scripts_action', array( $this, 'load_scripts' ) );

            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_enqueue_styles', array( $this, 'add_stylesheet' ), 10, 1 );
                add_filter( 'super_enqueue_scripts', array( $this, 'add_scripts' ), 10, 1 );
                add_filter( 'super_form_styles_filter', array( $this, 'add_element_styles' ), 10, 2 );
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 110, 2 );

                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) ); 
                add_action( 'init', array( $this, 'update_plugin' ) );

            }
            
            if ( $this->is_request( 'ajax' ) ) {
                add_filter( 'super_before_email_loop_data_filter', array( $this, 'add_signature_to_email_loop' ), 10, 2 );
                add_action( 'super_before_email_loop_data', array( $this, 'continue_after_signature' ) );
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
         * Add dynamic JavaScript functions
         *
         *  @since      1.0.0
        */
        public static function add_dynamic_function( $functions ) {
            
            // @since 1.2.2
            $functions['after_duplicating_column_hook'][] = array(
                'name' => 'init_signature_after_duplicating_column'
            );
            $functions['after_appending_duplicated_column_hook'][] = array(
                'name' => 'init_remove_initialized_class'
            );
            $functions['after_form_cleared_hook'][] = array(
                'name' => 'init_clear_signatures'
            );
            $functions['after_field_change_blur_hook'][] = array(
                'name' => 'refresh_signature'
            );
            $functions['after_responsive_form_hook'][] = array(
                'name' => 'refresh_signatures'
            );
            return $functions;
        }


        /**
         * Enqueue scripts before ajax call is made
         *
         *  @since      1.0.2
        */
        public static function load_scripts($atts) {
            if($atts['ajax']) {
                wp_enqueue_style( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/signature.css', array(), SUPER_Signature()->version );
                wp_enqueue_script( 'jquery-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/jquery.signature.js', array( 'jquery', 'jquery-touch-punch', 'jquery-ui-mouse' ), SUPER_Signature()->version );
                wp_enqueue_script( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/signature.js', array( 'super-common', 'jquery-signature' ), SUPER_Signature()->version );
            }
        }


        /**
         * Hook into stylesheets of the form and add styles for the signature element
         *
         *  @since      1.0.0
        */
        public static function add_element_styles( $styles, $attributes ) {
            $s = '.super-form-'.$attributes['id'].' ';
            $v = $attributes['settings'];
            $styles .= $s.'.super-signature-canvas {';
    		$styles .= 'border: solid 1px ' . $v['theme_field_colors_border'] . ';';
    		$styles .= 'background-color: ' . $v['theme_field_colors_top'] . ';';
    		$styles .= '}';
            return $styles;
		}


        /**
         * Hook into stylesheets and add signature stylesheet
         *
         *  @since      1.0.0
        */
        public static function add_stylesheet( $array ) {
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . '/assets/';
            $frontend_path   = $assets_path . 'css/frontend/';
            $array['super-signature'] = array(
                'src'     => $frontend_path . 'signature.css',
                'deps'    => '',
                'version' => SUPER_Signature()->version,
                'media'   => 'all',
                'screen'  => array( 
                    'super-forms_page_super_create_form'
                ),
                'method'  => 'enqueue',
            );
            return $array;
        }


        /**
         * Hook into scripts and add signature javascripts
         *
         *  @since      1.0.0
        */
        public static function add_scripts( $array ) {
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . '/assets/';
            $frontend_path  = $assets_path . 'js/frontend/';
            $array['super-jquery-signature'] = array(
                'src'     => $frontend_path . 'jquery.signature.js',
                'deps'    => array( 'jquery', 'jquery-ui-mouse' ),
                'version' => SUPER_Signature()->version,
                'footer'  => false,
                'screen'  => array( 
                    'super-forms_page_super_create_form'
                ),
                'method' => 'enqueue'
            );
            $array['super-signature'] = array(
                'src'     => $frontend_path . 'signature.js',
                'deps'    => array( 'super-jquery-signature' ),
                'version' => SUPER_Signature()->version,
                'footer'  => false,
                'screen'  => array( 
                    'super-forms_page_super_create_form'
                ),
                'method' => 'enqueue'
            );
            return $array;
        }


        /**
         * Handle the Signature element output
         *
         *  @since      1.0.0
        */
        public static function signature( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {
         
            // Fallback check for older super form versions
            if (method_exists('SUPER_Common','generate_array_default_element_settings')) {
                $defaults = SUPER_Common::generate_array_default_element_settings($shortcodes, 'form_elements', $tag);
            }else{
                $defaults = array(
                    'name' => 'subtotal',
                    'thickness' => 2,
                    'bg_size' => 150,
                    'width' => 0,
                    'height' => 100,
                    'icon' => 'pencil',
                );
            }
            $atts = wp_parse_args( $atts, $defaults );

            // @since Super Forms 4.7.0 - translation
            if (method_exists('SUPER_Shortcodes','merge_i18n')) {
                $atts = SUPER_Shortcodes::merge_i18n($atts, $i18n); 
            }

            if(empty($atts['bg_size'])) $atts['bg_size'] = 150;
            if(empty($atts['width'])) $atts['width'] = 0;
            if(empty($atts['height'])) $atts['height'] = 100;
            if(empty($atts['thickness'])) $atts['thickness'] = 2;

            $result = '';
            if( SUPER_Signature()->is_request('ajax') ){
                $url = plugin_dir_url( __FILE__ ) . 'assets/css/frontend/signature.css';
                $css = wp_remote_fopen($url);
                $result .= '<style>' . $css . '</style>';
            }else{
                wp_enqueue_style( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/signature.css', array(), SUPER_Signature()->version );
            }
            wp_enqueue_script( 'jquery-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/jquery.signature.js', array( 'jquery', 'jquery-touch-punch', 'jquery-ui-mouse' ), SUPER_Signature()->version );
			wp_enqueue_script( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/signature.js', array( 'jquery-signature' ), SUPER_Signature()->version );

            $result .= SUPER_Shortcodes::opening_tag( $tag, $atts );
	        $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
	        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) {
	            $atts['value'] = '';
	        }else{
	            $atts['value'] = SUPER_Common::email_tags( $atts['value'] );
	        }
            $styles = '';
            
            if( !isset( $atts['background_img'] ) ) $atts['background_img'] = 0;
            $attachment_id = absint($atts['background_img']);
            if( $attachment_id===0 ) {
                $url = plugin_dir_url( __FILE__ ) . 'assets/images/sign-here.png';
            }else{
                $url = wp_get_attachment_url( $attachment_id );
            }
	        $styles .= 'height:' . $atts['height'] . 'px;';
	        $styles .= 'background-image:url(\'' . esc_url($url) . '\');';
	        $styles .= 'background-size:' . $atts['bg_size'] . 'px;';
	        $result .= '<div class="super-signature-canvas" style="' . $styles . '"></div>';
	        $result .= '<span class="super-signature-clear"></span>';
	        $result .= '<textarea style="display:none;" class="super-shortcode-field"';
	        $result .= ' name="' . $atts['name'] . '"';
	        $result .= ' data-thickness="' . $atts['thickness'] . '"';
	        $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
	        $result .= ' />' . $atts['value'] . '</textarea>';
	        $result .= '</div>';
	        $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag );
	        $result .= '</div>';
	        return $result;
        }


        /**
         * Hook into elements and add Signature element
         * This element specifies the Signature List by it's given ID and retrieves it's Groups
         *
         *  @since      1.0.0
        */
        public static function add_signature_element( $array, $attributes ) {

            // Include the predefined arrays
            require( SUPER_PLUGIN_DIR . '/includes/shortcodes/predefined-arrays.php' );

            $array['form_elements']['shortcodes']['signature_predefined'] = array(
                'name' => esc_html__( 'Signature', 'super-forms' ),
                'icon' => 'signature',
                'predefined' => array(
                    array(
                        'tag' => 'signature',
                        'group' => 'form_elements',
                        'data' => array(
                            'name' => esc_html__( 'signature', 'super-forms' ),
                            'email' => esc_html__( 'Signature:', 'super-forms' ),
                            'icon' => 'signature',
                        )
                    )
                )
            );
	        $array['form_elements']['shortcodes']['signature'] = array(
	            'hidden' => true,
                'callback' => 'SUPER_Signature::signature',
	            'name' => esc_html__( 'Signature', 'super-forms' ),
	            'icon' => 'signature',
	            'atts' => array(
	                'general' => array(
	                    'name' => esc_html__( 'General', 'super-forms' ),
	                    'fields' => array(
	                        'name' => SUPER_Shortcodes::name( $attributes, '' ),
	                        'email' => SUPER_Shortcodes::email( $attributes, '' ),
	                        'label' => $label,
	                        'description'=>$description,
	                        'thickness' => SUPER_Shortcodes::width( $attributes=null, $default='', $min=1, $max=20, $steps=1, $name=esc_html__( 'Line Thickness', 'super-forms' ), $desc=esc_html__( 'The thickness of the signature when drawing', 'super-forms' ) ),
	                        'background_img' => array(
				                'name' => esc_html__( 'Custom sign here image', 'super-forms' ),
				                'desc' => esc_html__( 'Background image to show the user they can draw a signature', 'super-forms' ),
				                'default' => SUPER_Settings::get_value( 1, 'background_img', null, '' ),
				                'type' => 'image',
				            ),
	                        'bg_size' => SUPER_Shortcodes::width( $attributes=null, $default='', $min=0, $max=1000, $steps=10, $name=esc_html__( 'Image background size', 'super-forms' ), $desc=esc_html__( 'You can adjust the size of your background image here', 'super-forms' ) ),
				            'tooltip' => $tooltip,
                            'validation' => array(
                                'name'=>esc_html__( 'Validation', 'super-forms' ), 
                                'desc'=>esc_html__( 'How does this field need to be validated?', 'super-forms' ), 
                                'default'=> (!isset($attributes['validation']) ? 'none' : $attributes['validation']),
                                'type'=>'select', 
                                'values'=>array(
                                    'none' => esc_html__( 'None', 'super-forms' ),
                                    'empty' => esc_html__( 'Required Field (not empty)', 'super-forms' ), 
                                )
                            ),
	                        'error' => $error,
	                    ),
	                ),
	                'advanced' => array(
	                    'name' => esc_html__( 'Advanced', 'super-forms' ),
	                    'fields' => array(
	                        'grouped' => $grouped,
	                        'width' => SUPER_Shortcodes::width( $attributes=null, $default='', $min=0, $max=600, $steps=10, $name=null, $desc=null ),
	                        'height' => SUPER_Shortcodes::width( $attributes=null, $default='', $min=0, $max=600, $steps=10, $name=esc_html__( 'Field height in pixels', 'super-forms' ), $desc=esc_html__( 'Set to 0 to use default CSS height', 'super-forms' ) ),
	                        'exclude' => $exclude,
	                        'error_position' => $error_position,
	                    ),
	                ),
	                'icon' => array(
	                    'name' => esc_html__( 'Icon', 'super-forms' ),
	                    'fields' => array(
	                        'icon_position' => $icon_position,
	                        'icon_align' => $icon_align,
	                        'icon' => SUPER_Shortcodes::icon( $attributes, '' ),
	                    ),
	                ),
	                'conditional_logic' => $conditional_logic_array
	            ),
	        );
            return $array;
        }


        /**
         * Filter: super_before_email_loop_data_filter
         *
         *  @since      1.0.0
        */
        public static function add_signature_to_email_loop( $row, $data ) {
            $v = $data['v'];
            $result['status'] = '';
            $result['exclude'] = '';
            $result['row'] = '';
            if(!isset($v['value'])) return $result;
            if (strpos($v['value'], 'data:image/png;base64,') !== false) {
                $signature_contact_image_data = $v['value'];
                $signature_data = substr($signature_contact_image_data, strpos($signature_contact_image_data, ","));
                $signature_filename = $v['name'] . ".png";
                $signature_encoding = "base64";
                $signature_type = "image/png";
                $data['string_attachments'][] = array(
                    'data' => $signature_data,
                    'filename' => $signature_filename,
                    'encoding' => $signature_encoding,
                    'type' => $signature_type

                );
                if( isset( $v['label'] ) ) $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                // @IMPORTANT, escape the Data URL but make sure add it as an acceptable protocol 
                // otherwise the signature will not be displayed
                if( isset( $v['value'] ) ) $row = str_replace( '{loop_value}', $signature_filename . '<br /><img src="' . esc_url( $v['value'], array( 'data' ) ) . '" />', $row );
                $result['status'] = 'continue';
                $result['exclude'] = $v['exclude'];
                $result['row'] = $row;
                $result['string_attachments'] = $data['string_attachments'];
            }
            return $result;
        }
    }
        
endif;


/**
 * Returns the main instance of SUPER_Signature to prevent the need to use globals.
 *
 * @return SUPER_Signature
 */
if(!function_exists('SUPER_Signature')){
    function SUPER_Signature() {
        return SUPER_Signature::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Signature'] = SUPER_Signature();
}
