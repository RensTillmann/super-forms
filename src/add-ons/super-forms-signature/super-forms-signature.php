<?php
/**
 * Super Forms Signature
 *
 * @package   Super Forms Signature
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms Signature
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Adds an extra element that allows users to sign their signature before submitting the form
 * Version:     1.3.0
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
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
        public $version = '1.3.0';


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
            
            // @since 1.1.0
            register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

            // Filters since 1.1.0
            add_filter( 'super_after_activation_message_filter', array( $this, 'activation_message' ), 10, 2 );


            // Actions since 1.0.0
            add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_signature_element' ), 10, 2 );

            if ( $this->is_request( 'frontend' ) ) {
                
                // Filters since 1.0.0
            	add_filter( 'super_form_styles_filter', array( $this, 'add_element_styles' ), 10, 2 );

                // Filters since 1.2.2
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 110, 2 );


                /**
                 * Check if this site uses Ajax calls to generate content dynamically
                 * If this is the case make sure the styles and scripts for the element(s) are loaded
                 *
                 *  @since      1.0.2
                */
                $settings = get_option( 'super_settings' );
                if( isset( $settings['enable_ajax'] ) ) {
                    if( $settings['enable_ajax']=='1' ) {
                        add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_scripts_before_ajax' ) );
                    }
                }
                
            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_enqueue_styles', array( $this, 'add_stylesheet' ), 10, 1 );
                add_filter( 'super_enqueue_scripts', array( $this, 'add_scripts' ), 10, 1 );
                add_filter( 'super_form_styles_filter', array( $this, 'add_element_styles' ), 10, 2 );

                // Actions since 1.0.0
                add_action( 'super_before_load_form_dropdown_hook', array( $this, 'add_ready_to_use_forms' ) );
                add_action( 'super_after_load_form_dropdown_hook', array( $this, 'add_ready_to_use_forms_json' ) );

                // Filters since 1.1.0
                add_filter( 'super_settings_end_filter', array( $this, 'activation' ), 100, 2 );

                // Actions since 1.1.0
                add_action( 'init', array( $this, 'update_plugin' ) );

                // Filters since 1.2.2
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 110, 2 );

                // Actions since 1.2.2
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) ); 

            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Filters since 1.0.0
                add_filter( 'super_before_email_loop_data_filter', array( $this, 'add_signature_to_email_loop' ), 10, 2 );

                // Actions since 1.0.0
                add_action( 'super_before_email_loop_data', array( $this, 'continue_after_signature' ) );

            }
            
        }

       
       /**
         * Display activation message for automatic updates
         *
         *  @since      1.2.2
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

            return $functions;
        }

                
        /**
         * Automatically update plugin from the repository
         *
         *  @since      1.1.0
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
         * @since       1.1.0
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
         *  @since      1.1.0
         */
        public static function deactivate(){
            if (method_exists('SUPER_Forms','add_on_deactivate')) {
                SUPER_Forms::add_on_deactivate(SUPER_Signature()->add_on_slug);
            }
        }


        /**
         * Check license and show activation message
         * 
         * @since       1.1.0
        */
        public function activation_message( $activation_msg, $data ) {
            if (method_exists('SUPER_Forms','add_on_activation_message')) {
                return SUPER_Forms::add_on_activation_message($activation_msg, $this->add_on_slug, $this->add_on_name);
            }
            return $activation_msg;
        }


        /**
         * Enqueue scripts before ajax call is made
         *
         *  @since      1.0.2
        */
        public static function load_frontend_scripts_before_ajax() {
            wp_enqueue_style( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/signature.min.css', array(), SUPER_Signature()->version );
            wp_enqueue_script( 'super-jquery-touch-punch', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/jquery.ui.touch-punch.min.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-mouse' ), SUPER_Signature()->version );
            wp_enqueue_script( 'super-jquery-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/jquery.signature.js', array( 'jquery', 'jquery-ui-mouse' ), SUPER_Signature()->version );
            wp_enqueue_script( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/signature.min.js', array( 'super-common', 'super-jquery-signature' ), SUPER_Signature()->version );
        }


        /**
         * Hook into the load form dropdown and add some ready to use forms
         *
         *  @since      1.0.0
        */
        public static function add_ready_to_use_forms() {
            $html = '<option value="signature-email">Signature - Subscribe email address only</option>';
            $html .= '<option value="signature-name">Signature - Subscribe with first and last name</option>';
            $html .= '<option value="signature-interests">Signature - Subscribe with interests</option>';
            echo $html;
        }


        /**
         * Hook into the after load form dropdown and add the json of the ready to use forms
         *
         *  @since      1.0.0
        */
        public static function add_ready_to_use_forms_json() {
            $html  = '<textarea hidden name="signature-email">';
            $html .= '[{"tag":"text","group":"form_elements","inner":"","data":{"name":"email","email":"Email","label":"","description":"","placeholder":"Your Email Address","tooltip":"","validation":"email","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"envelope","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"name","logic":"contains","value":""}]}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"first_name","email":"First name:","label":"","description":"","placeholder":"Your First Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"email","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"last_name","email":"Last name:","label":"","description":"","placeholder":"Your Last Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"email","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"signature","group":"form_elements","inner":"","data":{"list_id":"53e03de9e1","display_interests":"yes","send_confirmation":"yes","email":"","label":"Interests","description":"Select one or more interests","tooltip":"","validation":"empty","error":"","maxlength":"0","minlength":"0","display":"horizontal","grouped":"0","width":"0","exclude":"2","error_position":"","icon_position":"inside","icon_align":"left","icon":"star","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"email","logic":"contains","value":""}]}}]';
            $html .= '</textarea>';

            $html .= '<textarea hidden name="signature-name">';
            $html .= '[{"tag":"text","group":"form_elements","inner":"","data":{"name":"email","email":"Email","label":"","description":"","placeholder":"Your Email Address","tooltip":"","validation":"email","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"envelope","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"name","logic":"contains","value":""}]}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"first_name","email":"First name:","label":"","description":"","placeholder":"Your First Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"email","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"last_name","email":"Last name:","label":"","description":"","placeholder":"Your Last Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"email","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"signature","group":"form_elements","inner":"","data":{"list_id":"53e03de9e1","display_interests":"yes","send_confirmation":"yes","email":"","label":"Interests","description":"Select one or more interests","tooltip":"","validation":"empty","error":"","maxlength":"0","minlength":"0","display":"horizontal","grouped":"0","width":"0","exclude":"2","error_position":"","icon_position":"inside","icon_align":"left","icon":"star","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"email","logic":"contains","value":""}]}}]';
            $html .= '</textarea>';

            $html .= '<textarea hidden name="signature-interests">';
            $html .= '[{"tag":"text","group":"form_elements","inner":"","data":{"name":"email","email":"Email","label":"","description":"","placeholder":"Your Email Address","tooltip":"","validation":"email","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"envelope","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"name","logic":"contains","value":""}]}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"first_name","email":"First name:","label":"","description":"","placeholder":"Your First Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"email","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"column","group":"layout_elements","inner":[{"tag":"text","group":"form_elements","inner":"","data":{"name":"last_name","email":"Last name:","label":"","description":"","placeholder":"Your Last Name","tooltip":"","validation":"empty","error":"","grouped":"0","maxlength":"0","minlength":"0","width":"0","exclude":"0","error_position":"","icon_position":"outside","icon_align":"left","icon":"user","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"email","logic":"contains","value":""}]}}],"data":{"size":"1/2","margin":"","conditional_action":"disabled"}},{"tag":"signature","group":"form_elements","inner":"","data":{"list_id":"53e03de9e1","display_interests":"yes","send_confirmation":"yes","email":"","label":"Interests","description":"Select one or more interests","tooltip":"","validation":"empty","error":"","maxlength":"0","minlength":"0","display":"horizontal","grouped":"0","width":"0","exclude":"2","error_position":"","icon_position":"inside","icon_align":"left","icon":"star","conditional_action":"disabled","conditional_trigger":"all","conditional_items":[{"field":"email","logic":"contains","value":""}]}}]';
            $html .= '</textarea>';
            echo $html;
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
            $suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.min' : '.min';
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . '/assets/';
            $frontend_path   = $assets_path . 'css/frontend/';
            $array['super-signature'] = array(
                'src'     => $frontend_path . 'signature' . $suffix . '.css',
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

			$suffix         = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.min' : '.min';
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
                'src'     => $frontend_path . 'signature' . $suffix . '.js',
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
        public static function signature( $tag, $atts, $inner, $shortcodes=null, $settings=null ) {
         
            // Fallback check for older super form versions
            if (method_exists('SUPER_Common','generate_array_default_element_settings')) {
                $defaults = SUPER_Common::generate_array_default_element_settings($shortcodes, 'form_elements', $tag);
            }else{
                $defaults = array(
                    'name' => 'subtotal',
                    'thickness' => 1,
                    'bg_size' => 150,
                    'width' => 0,
                    'height' => 100,
                    'icon' => 'pencil',
                );
            }
            $atts = wp_parse_args( $atts, $defaults );
            if(empty($atts['bg_size'])) $atts['bg_size'] = 150;
            if(empty($atts['width'])) $atts['width'] = 0;
            if(empty($atts['height'])) $atts['height'] = 100;
            if(empty($atts['thickness'])) $atts['thickness'] = 1;

            $result = '';
            if( SUPER_Signature()->is_request('ajax') ){
                $url = plugin_dir_url( __FILE__ ) . 'assets/css/frontend/signature.min.css';
                $css = wp_remote_fopen($url);
                $result .= '<style>' . $css . '</style>';
            }else{
                wp_enqueue_style( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/signature.min.css', array(), SUPER_Signature()->version );
            }
            wp_enqueue_script( 'super-jquery-touch-punch', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/jquery.ui.touch-punch.min.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-mouse' ), SUPER_Signature()->version );
            wp_enqueue_script( 'super-jquery-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/jquery.signature.js', array( 'jquery', 'jquery-ui-mouse' ), SUPER_Signature()->version );
			wp_enqueue_script( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/signature.min.js', array( 'super-jquery-signature' ), SUPER_Signature()->version );

            $result .= SUPER_Shortcodes::opening_tag( $tag, $atts );
	        $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
	        if( ( !isset( $atts['value'] ) ) || ( $atts['value']=='' ) ) {
	            $atts['value'] = '';
	        }else{
	            $atts['value'] = SUPER_Common::email_tags( $atts['value'] );
	        }
	        $styles = '';
	        $image = wp_prepare_attachment_for_js( $atts['background_img'] );
            if( $image==null ) $image['url'] = plugin_dir_url( __FILE__ ) . 'assets/images/sign-here.png';
	        $styles .= 'height:' . $atts['height'] . 'px;';
	        $styles .= 'background-image:url(\'' . $image['url'] . '\');';
	        $styles .= 'background-size:' . $atts['bg_size'] . 'px;';
	        $result .= '<div class="super-signature-canvas" style="' . $styles . '"></div>';
	        $result .= '<span class="super-signature-clear"></span>';
	        $result .= '<textarea style="display:none;" class="super-shortcode-field"';
	        $result .= ' name="' . $atts['name'] . '"';
	        $result .= ' data-thickness="' . $atts['thickness'] . '"';
	        $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
	        $result .= ' />' . $atts['value'] . '</textarea>';
	        $result .= '</div>';
	        $result .= SUPER_Shortcodes::loop_conditions( $atts );
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
                'name' => __( 'Signature', 'super-forms' ),
                'icon' => 'pencil-square-o',
                'predefined' => array(
                    array(
                        'tag' => 'signature',
                        'group' => 'form_elements',
                        'data' => array(
                            'name' => __( 'signature', 'super-forms' ),
                            'email' => __( 'Signature:', 'super-forms' ),
                            'icon' => 'pencil',
                        )
                    )
                )
            );
	        $array['form_elements']['shortcodes']['signature'] = array(
	            'hidden' => true,
                'callback' => 'SUPER_Signature::signature',
	            'name' => __( 'Signature', 'super-forms' ),
	            'icon' => 'pencil-square-o',
	            'atts' => array(
	                'general' => array(
	                    'name' => __( 'General', 'super-forms' ),
	                    'fields' => array(
	                        'name' => SUPER_Shortcodes::name( $attributes, '' ),
	                        'email' => SUPER_Shortcodes::email( $attributes, '' ),
	                        'label' => $label,
	                        'description'=>$description,
	                        'thickness' => SUPER_Shortcodes::width( $attributes=null, $default='', $min=1, $max=20, $steps=1, $name=__( 'Line Thickness', 'super-forms' ), $desc=__( 'The thickness of the signature when drawing', 'super-forms' ) ),
	                        'background_img' => array(
				                'name' => __( 'Custom sign here image', 'super-forms' ),
				                'desc' => __( 'Background image to show the user they can draw a signature', 'super-forms' ),
				                'default' => SUPER_Settings::get_value( 1, 'background_img', null, '' ),
				                'type' => 'image',
				            ),
	                        'bg_size' => SUPER_Shortcodes::width( $attributes=null, $default='', $min=0, $max=1000, $steps=10, $name=__( 'Image background size', 'super-forms' ), $desc=__( 'You can adjust the size of your background image here', 'super-forms' ) ),
				            'tooltip' => $tooltip,
                            'validation' => array(
                                'name'=>__( 'Special Validation', 'super-forms' ), 
                                'desc'=>__( 'How does this field need to be validated?', 'super-forms' ), 
                                'default'=> (!isset($attributes['validation']) ? 'none' : $attributes['validation']),
                                'type'=>'select', 
                                'values'=>array(
                                    'none' => __( 'No validation needed', 'super-forms' ),
                                    'empty' => __( 'Not empty', 'super-forms' ), 
                                )
                            ),
	                        'error' => $error,
	                    ),
	                ),
	                'advanced' => array(
	                    'name' => __( 'Advanced', 'super-forms' ),
	                    'fields' => array(
	                        'grouped' => $grouped,
	                        'width' => SUPER_Shortcodes::width( $attributes=null, $default='', $min=0, $max=600, $steps=10, $name=null, $desc=null ),
	                        'height' => SUPER_Shortcodes::width( $attributes=null, $default='', $min=0, $max=600, $steps=10, $name=__( 'Field height in pixels', 'super-forms' ), $desc=__( 'Set to 0 to use default CSS height', 'super-forms' ) ),
	                        'exclude' => $exclude,
	                        'error_position' => $error_position,
	                    ),
	                ),
	                'icon' => array(
	                    'name' => __( 'Icon', 'super-forms' ),
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
                if( isset( $v['value'] ) ) $row = str_replace( '{loop_value}', $signature_filename . '<br /><img src="' . $v['value'] . '" />', $row );
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
function SUPER_Signature() {
    return SUPER_Signature::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_Signature'] = SUPER_Signature();