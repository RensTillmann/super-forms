<?php
/**
 * Super Forms - Calculator
 *
 * @package   Super Forms - Calculator
 * @author    feeling4design
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Calculator
 * Description: Adds an extra element that allows you to do calculations on any of your fields
 * Version:     2.3.4
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

if( !class_exists('SUPER_Calculator') ) :


    /**
     * Main SUPER_Calculator Class
     *
     * @class SUPER_Calculator
     * @version	1.0.0
     */
    final class SUPER_Calculator {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '2.3.4';


        /**
         * @var string
         *
         *  @since      1.3.0
        */
        public $add_on_slug = 'calculator';
        public $add_on_name = 'Calculator';
        

        /**
         * @var SUPER_Calculator The single instance of the class
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
         * Main SUPER_Calculator Instance
         *
         * Ensures only one instance of SUPER_Calculator is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Calculator()
         * @return SUPER_Calculator - Main instance
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
         * SUPER_Calculator Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_calculator_loaded');
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
            
            // Filters since 1.0.0
            add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_calculator_element' ), 10, 2 );
            
            // Filters since 1.0.8
            add_filter( 'super_common_attributes_filter', array( $this, 'add_element_attribute' ), 10, 2 );
            

            if ( $this->is_request( 'frontend' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_form_styles_filter', array( $this, 'add_element_styles' ), 10, 2 );
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

                // Filters since 1.0.8
                add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_date_field_settings' ), 10, 2 );
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
         * Hook into common attributes and add return_age attribute for date element
         *
         *  @since      1.0.8
        */
        public static function add_element_attribute( $result, $element ) {
            if( $element['tag']=='date' ) {
                $atts = $element['atts'];
                if( !isset( $atts['return_age'] ) ) $atts['return_age'] = '';
                $result .= ' data-return_age="' . $atts['return_age'] . '"';
            }
            return $result;
        }


        /**
         * Hook into settings and add Text field settings
         *
         *  @since      1.0.8
        */
        public static function add_date_field_settings( $array, $attributes ) {
            
            // Now add the age settings field
            $fields_array = $array['form_elements']['shortcodes']['date']['atts']['general']['fields'];
            $res = array_slice($fields_array, 0, 8, true);
            $setting['return_age'] = array(
                'name' => esc_html__( 'Return age as value instead of the date', 'super-forms'),
                'label' => esc_html__( 'When enabled the age will be returned based on the selected date. This can be useful when a user enters their birthdate or when you need to know the age of an object.', 'super-forms' ), 
                'default'=> ( !isset( $attributes['return_age'] ) ? '' : $attributes['return_age'] ),
                'type' => 'checkbox', 
                'filter'=>true,
                'values' => array(
                    'true' => esc_html__( 'Yes', 'super-forms' ),
                )
            );
            $res = $res + $setting + array_slice($fields_array, 1, count($fields_array) - 1, true);

            $array['form_elements']['shortcodes']['date']['atts']['general']['fields'] = $res;
            return $array;
        }


        /**
         * Enqueue scripts before ajax call is made
         *
         *  @since      1.0.0
        */
        public static function load_scripts($atts) {
            if($atts['ajax']) {
                wp_enqueue_style( 'super-calculator', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/calculator.css', array(), SUPER_Calculator()->version );
                wp_enqueue_script( 'mathjs', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/mathjs.min.js', array( 'jquery', 'super-common' ), SUPER_Calculator()->version );
                wp_enqueue_script( 'super-calculator', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/calculator.js', array( 'mathjs' ), SUPER_Calculator()->version );
            }
        }


        /**
         * Hook into stylesheets of the form and add styles for the calculator element
         *
         *  @since      1.0.0
        */
        public static function add_dynamic_function( $functions ) {
            
            $functions['after_initializing_forms_hook'][] = array(
                'name' => 'init_calculator'
            );
            $functions['before_validating_form_hook'][] = array(
                'name' => 'init_calculator'
            );
            $functions['after_field_change_blur_hook'][] = array(
                'name' => 'init_calculator'
            );

            // @since 1.1.2
            $functions['after_form_data_collected_hook'][] = array(
                'name' => 'init_calculator_update_data_value'
            );

            // @since 1.8.5 - make sure we execute this function AFTER all other fields have been renamed
            // otherwise fields would be skipped if the are placed below the calculator element
            $functions['after_duplicating_column_hook'][] = array(
                'name' => 'init_calculator_update_math'
            );

            // @since 1.1.4
            $functions['after_init_calculator_hook'][] = array(
                'name' => 'conditional_logic'
            );

            // @since 1.5.0
            $functions['after_duplicating_column_hook'][] = array(
                'name' => 'init_calculator_after_duplicating_column'
            );

            return $functions;
        }


        /**
         * Hook into stylesheets of the form and add styles for the calculator element
         *
         *  @since      1.0.0
        */
        public static function add_element_styles( $styles, $attributes ) {
            $s = '.super-form-'.$attributes['id'].' ';
            $v = $attributes['settings'];
            $styles .= $s.'.super-calculator-canvas {';
    		$styles .= 'border: solid 1px ' . $v['theme_field_colors_border'] . ';';
    		$styles .= 'background-color: ' . $v['theme_field_colors_top'] . ';';
    		$styles .= '}';
            return $styles;
		}


        /**
         * Hook into stylesheets and add calculator stylesheet
         *
         *  @since      1.0.0
        */
        public static function add_stylesheet( $array ) {
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . '/assets/';
            $frontend_path   = $assets_path . 'css/frontend/';
            $array['super-calculator'] = array(
                'src'     => $frontend_path . 'calculator.css',
                'deps'    => '',
                'version' => SUPER_Calculator()->version,
                'media'   => 'all',
                'screen'  => array( 
                    'super-forms_page_super_create_form'
                ),
                'method'  => 'enqueue',
            );
            return $array;
        }


        /**
         * Hook into scripts and add calculator javascripts
         *
         *  @since      1.0.0
        */
        public static function add_scripts( $array ) {
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . '/assets/';
            $frontend_path  = $assets_path . 'js/frontend/';
            $array['mathjs'] = array(
                'src'     => $frontend_path . 'mathjs.min.js',
                'deps'    => array( 'jquery', 'super-common' ),
                'version' => SUPER_Calculator()->version,
                'footer'  => false,
                'screen'  => array( 
                    'super-forms_page_super_create_form'
                ),
                'method' => 'enqueue'
            );
            $array['super-calculator'] = array(
                'src'     => $frontend_path . 'calculator.js',
                'deps'    => array( 'mathjs' ),
                'version' => SUPER_Calculator()->version,
                'footer'  => false,
                'screen'  => array( 
                    'super-forms_page_super_create_form'
                ),
                'method' => 'enqueue'
            );
           
            return $array;
        }


        /**
         * Handle the Calculator element output
         *
         *  @since      1.0.0
        */
        public static function calculator($x) {
            extract( shortcode_atts( array( 'tag'=>'', 'atts'=>array(), 'inner'=>array(), 'shortcodes'=>null, 'settings'=>array(), 'i18n'=>null), $x ) );
            
            // Fallback check for older super form versions
            if (method_exists('SUPER_Common','generate_array_default_element_settings')) {
                $defaults = SUPER_Common::generate_array_default_element_settings($shortcodes, 'form_elements', $tag);
            }else{
                $defaults = array(
                    'name' => 'subtotal',
                    'math' => '0',
                    'amount_label' => '',
                    'format' => '',
                    'currency' => '$',
                    'email' => 'Subtotal:',
                    'label' => '',
                    'description' => '',
                    'tooltip' => '',
                    'validation' => 'none',
                    'error' => '',
                    'decimals' => '2',
                    'decimal_separator' => '.',
                    'thousand_separator' => ',',
                    'date_calculations' => '',
                    'date_math' => 'years',
                    'grouped' => 0,
                    'align' => 'left',
                    'amount_width' => 0,
                    'wrapper_width' => 0,
                    'margin' => '',
                    'exclude' => 0,
                    'error_position' => '',
                    'icon_position' => 'outside',
                    'icon_align' => 'left',
                    'icon' => 'calculator',

                    // @since 1.8.6
                    'convert_timestamp' => '',
                    'date_format' => 'dd-mm-yy',
                    'custom_format' => 'dd-mm-yy',
                );
            }
            $atts = wp_parse_args( $atts, $defaults );

            // @since Super Forms 4.7.0 - translation
            if (method_exists('SUPER_Shortcodes','merge_i18n')) {
                $atts = SUPER_Shortcodes::merge_i18n($atts, $i18n); 
            }

            // @since 1.8.6
            if( !empty($atts['convert_timestamp']) ) {
                $format = $atts['date_format'];
                if( $format=='custom' ) $format = $atts['custom_format'];

                $jsformat = $format;
                $jsformat = str_replace('DD', 'dddd', $jsformat);
                if (preg_match("/MM/i", $jsformat)) {
                    $jsformat = str_replace('MM', 'MMMM', $jsformat);
                }else{
                    if (preg_match("/M/i", $jsformat)) {
                        $jsformat = str_replace('M', 'MMM', $jsformat);
                    }
                }
                $jsformat = str_replace('mm', 'MM', $jsformat);
                if (preg_match("/yy/i", $jsformat)) {
                    $jsformat = str_replace('yy', 'yyyy', $jsformat);
                }else{
                    if (preg_match("/y/i", $jsformat)) {
                        $jsformat = str_replace('y', 'yy', $jsformat);
                    }
                }
            }

            wp_enqueue_style( 'super-calculator', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/calculator.css', array(), SUPER_Calculator()->version );
            wp_enqueue_script( 'mathjs', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/mathjs.min.js', array( 'jquery', 'super-common' ), SUPER_Calculator()->version );
            wp_enqueue_script( 'super-calculator', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/calculator.js', array( 'mathjs' ), SUPER_Calculator()->version );
            $class = ''; 
            if( $atts['margin']!='' ) {
                $class = 'super-remove-margin'; 
            }
            $result = SUPER_Shortcodes::opening_tag( $tag, $atts, $class );
	        $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
            if( !isset( $atts['decimals'] ) ) $atts['decimals'] = 2;
            if( !isset( $atts['thousand_separator'] ) ) $atts['thousand_separator'] = ',';
            if( !isset( $atts['decimal_separator'] ) ) $atts['decimal_separator'] = '.';
            if( !isset( $atts['math'] ) ) $atts['math'] = '';
            // Option to directly retrieve predefined tags inside math, e.g: to retrieve current year, or month, or price of current WooCommerce product 
            // We should skip predefined tags that start with `option_`
            if($atts['math']!='') $atts['math'] = SUPER_Common::email_tags( $atts['math'], null, $settings, $user=null, $skip=true, $skipSecrets=true, $skipOptions=true );

            if( !isset( $atts['amount_label'] ) ) $atts['amount_label'] = '';

            // @since 1.2.0 - return years, months or days for date calculations
            if( !isset( $atts['date_calculations'] ) ) $atts['date_calculations'] = '';
            if($atts['date_calculations']=='true'){
                if( !isset( $atts['date_math'] ) ) $atts['date_math'] = 'years';
            }else{
                $atts['date_math'] = '';
            }

            // @since 2.3.0 - speed improvement, only do calculations for applied fields
            preg_match_all('/{\K[^}]*(?=})/m', $atts['math'], $matches);
            $fields = implode('}{', $matches[0]);

            $dataFields = '';
            if(!empty($fields)) $dataFields = ' data-fields="{' . $fields . '}"';
            $result .= '<div class="super-calculator-wrapper"' . ($dataFields ? $dataFields : '') . ($atts['date_math']!='' ? ' data-date-math="' . $atts['date_math'] . '"' : '') . ' data-decimals="' . $atts['decimals'] . '" data-thousand-separator="' . $atts['thousand_separator'] . '" data-decimal-separator="' . $atts['decimal_separator'] . '" data-super-math="' . $atts['math'] . '"';
            if(!empty($jsformat)) $result .= ' data-jsformat="' . $jsformat . '"';
            $result .= '>';

            $result .= '<span class="super-calculator-label">' . $atts['amount_label'] . '</span>';

            $style = '';
            if( !isset( $atts['amount_width'] ) ) $atts['amount_width'] = 0;
            if( $atts['amount_width']!=0 ) {
                $style = 'width:' . $atts['amount_width'] . 'px;';
            }
            if( !empty( $style ) ) {
                $style = ' style="' . $style . '"';
            }
            $result .= '<span' . $style . ' class="super-calculator-currency-wrapper">';
            $result .= '<span class="super-calculator-currency">' . $atts['currency'] . '</span>';
            $result .= '<span class="super-calculator-amount">' . number_format( 0, $atts['decimals'], $atts['decimal_separator'], '' ) . '</span>';
            
            // @since v1.0.2
            if( !isset( $atts['format'] ) ) $atts['format'] = '';
            $result .= '<span class="super-calculator-format">' . $atts['format'] . '</span>';
            
            $result .= '</span>';
            $result .= '</div>';
	        $result .= '<input type="hidden" class="super-shortcode-field"';
	        $result .= ' data-value="' . $atts['currency'] . number_format( 0, $atts['decimals'], $atts['decimal_separator'], '' ) . $atts['format'] . '" value="' . number_format( 0, $atts['decimals'], $atts['decimal_separator'], '' ) . '" name="' . $atts['name'] . '"';

	        $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
	        $result .= ' />';
	        $result .= '</div>';
	        $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag );
	        $result .= '</div>';
	        return $result;
        }


        /**
         * Hook into elements and add Calculator element
         * This element specifies the Calculator List by it's given ID and retrieves it's Groups
         *
         *  @since      1.0.0
        */
        public static function add_calculator_element( $array, $attributes ) {

            // Include the predefined arrays
            require( SUPER_PLUGIN_DIR . '/includes/shortcodes/predefined-arrays.php' );

            $array['form_elements']['shortcodes']['calculator_predefined'] = array(
                'name' => esc_html__( 'Calculator', 'super-forms' ),
                'icon' => 'calculator',
                'predefined' => array(
                    array(
                        'tag' => 'calculator',
                        'group' => 'form_elements',
                        'data' => array(
                            'name' => esc_html__( 'subtotal', 'super-forms' ),
                            'email' => esc_html__( 'Subtotal:', 'super-forms' ),
                            'currency' => '$',
                            'thousand_separator' => ',',
                            'icon' => 'calculator',
                        )
                    )
                )
            );

	        $array['form_elements']['shortcodes']['calculator'] = array(
	            'hidden' => true,
                'callback' => 'SUPER_Calculator::calculator',
	            'name' => esc_html__( 'Calculator', 'super-forms' ),
	            'icon' => 'calculator',
	            'atts' => array(
	                'general' => array(
	                    'name' => esc_html__( 'General', 'super-forms' ),
	                    'fields' => array(
                            'name' => SUPER_Shortcodes::name( $attributes, '' ),
                            'email' => SUPER_Shortcodes::email( $attributes, '' ),
                            'math' => array(
                                'name'=>esc_html__( 'Calculation', 'super-forms' ), 
                                'desc'=>esc_html__( 'You can use tags to retrieve field values e.g: ({field1}+{field2})*7.5', 'super-forms' ),
                                'default'=> ( !isset( $attributes['math'] ) ? '' : $attributes['math'] ),
                                'placeholder'=>'({field1}+{field2})*7.5',
                                'required'=>true
                            ),
                            'amount_label' => array(
                                'name'=>esc_html__( 'Amount Label', 'super-forms' ), 
                                'desc'=>esc_html__( 'Set a label for the amount e.g: Subtotal or Total', 'super-forms' ),
                                'default'=> ( !isset( $attributes['amount_label'] ) ? '' : $attributes['amount_label'] ),
                                'placeholder'=>'',
                                'i18n'=>true
                            ),
                            'format' => array(
                                'default'=> ( !isset( $attributes['format'] ) ? '' : $attributes['format'] ),
                                'name' => esc_html__( 'Amount format (example: %)', 'super-forms' ), 
                                'desc' => esc_html__( 'Set a format e.g: %, EUR, USD etc.', 'super-forms' ),
                                'i18n'=>true
                            ),
                            'currency' => array(
                                'name'=>esc_html__( 'Currency', 'super-forms' ), 
                                'desc'=>esc_html__( 'Set the currency of or leave empty for no currency e.g: $ or €', 'super-forms' ),
                                'default'=> ( !isset( $attributes['currency'] ) ? '' : $attributes['currency'] ),
                                'placeholder'=>'$',
                                'i18n'=>true
                            ),                            
	                        'label' => $label,
	                        'description'=>$description,
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
	                        'emptyError' => (isset($emptyError) ? $emptyError : ''),
	                    ),
	                ),
	                'advanced' => array(
	                    'name' => esc_html__( 'Advanced', 'super-forms' ),
	                    'fields' => array(
                            'decimals' => array(
                                'name'=>esc_html__( 'Length of decimal', 'super-forms' ), 
                                'desc'=>esc_html__( 'Choose a length for your decimals (default = 2)', 'super-forms' ), 
                                'default'=> (!isset($attributes['decimals']) ? '2' : $attributes['decimals']),
                                'type'=>'slider', 
                                'min'=>0,
                                'max'=>50,
                                'steps'=>1
                            ),
                            'decimal_separator' => array(
                                'name'=>esc_html__( 'Decimal separator', 'super-forms' ), 
                                'desc'=>esc_html__( 'Choose your decimal separator (comma or dot)', 'super-forms' ), 
                                'default'=> (!isset($attributes['decimal_separator']) ? '.' : $attributes['decimal_separator']),
                                'type'=>'select', 
                                'values'=>array(
                                    '.' => esc_html__( '. (dot)', 'super-forms' ),
                                    ',' => esc_html__( ', (comma)', 'super-forms' ), 
                                    ' ' => esc_html__( '(space)', 'super-forms' ), 
                                ),
                                'i18n'=>true
                            ),
                            'thousand_separator' => array(
                                'name'=>esc_html__( 'Thousand separator', 'super-forms' ), 
                                'desc'=>esc_html__( 'Choose your thousand separator (empty, comma or dot)', 'super-forms' ), 
                                'default'=> (!isset($attributes['thousand_separator']) ? '' : $attributes['thousand_separator']),
                                'type'=>'select', 
                                'values'=>array(
                                    '' => esc_html__( 'None (empty)', 'super-forms' ),
                                    '.' => esc_html__( '. (dot)', 'super-forms' ),
                                    ',' => esc_html__( ', (comma)', 'super-forms' ), 
                                    ' ' => esc_html__( '(space)', 'super-forms' ), 
                                ),
                                'i18n'=>true
                            ),

                            // @since 1.2.0 - return years, months or days for math
                            'date_calculations' => array(
                                'desc' => esc_html__( 'This allows you return the age, months or days based on the birthdate', 'super-forms' ), 
                                'default'=> ( !isset( $attributes['date_calculations'] ) ? '' : $attributes['date_calculations'] ),
                                'type' => 'checkbox', 
                                'filter'=>true,
                                'values' => array(
                                    'true' => esc_html__( 'Enable birthdate calculations', 'super-forms' ),
                                )
                            ),
                            'date_math' => array(
                                'name'=>esc_html__( 'Select which value to return for calculations', 'super-forms' ), 
                                'default'=> (!isset($attributes['date_math']) ? 'years' : $attributes['date_math']),
                                'type'=>'select',
                                'values'=>array(
                                    'years' => esc_html__( 'Return years (age)', 'super-forms' ),
                                    'months' => esc_html__( 'Return months', 'super-forms' ),
                                    'days' => esc_html__( 'Return days', 'super-forms' ),
                                ),
                                'filter'=>true,
                                'parent'=>'date_calculations',
                                'filter_value'=>'true',
                            ),

                            // @since 1.8.6 - return date format based on timestamps
                            'convert_timestamp' => array(
                                'default'=> ( !isset( $attributes['convert_timestamp'] ) ? '' : $attributes['convert_timestamp'] ),
                                'type' => 'checkbox', 
                                'filter'=>true,
                                'values' => array(
                                    'true' => esc_html__( 'Convert timestamp to specific date format', 'super-forms' ),
                                ),
                            ),
                            'date_format' => array(
                                'name'=>esc_html__( 'Date Format', 'super-forms' ), 
                                'desc'=>esc_html__( 'Change the date format', 'super-forms' ), 
                                'default'=> ( !isset( $attributes['date_format']) ? 'dd-mm-yy' : $attributes['date_format']),
                                'type'=>'select', 
                                'values'=>array(
                                    'custom' => esc_html__( 'Custom date format', 'super-forms' ),
                                    'dd-mm-yy' => esc_html__( 'European - dd-mm-yy', 'super-forms' ),
                                    'mm/dd/yy' => esc_html__( 'Default - mm/dd/yy', 'super-forms' ),
                                    'yy-mm-dd' => esc_html__( 'ISO 8601 - yy-mm-dd', 'super-forms' ),
                                    'd M, y' => esc_html__( 'Short - d M, y', 'super-forms' ),
                                    'd MM, y' => esc_html__( 'Medium - d MM, y', 'super-forms' ),
                                    'DD, d MM, yy' => esc_html__( 'Full - DD, d MM, yy', 'super-forms' ),
                                ),
                                'filter'=>true,
                                'parent'=>'convert_timestamp',
                                'filter_value'=>'true',
                                'i18n'=>true
                            ),
                            'custom_format' => array(
                                'name'=>'Enter a custom Date Format',
                                'default'=> ( !isset( $attributes['custom_format']) ? 'dd-mm-yy' : $attributes['custom_format']),
                                'filter'=>true,
                                'parent'=>'date_format',
                                'filter_value'=>'custom',
                                'i18n'=>true
                            ),

	                        'grouped' => $grouped,
                            'align' => array(
                                'name'=> esc_html__('Alignment', 'super-forms' ),
                                'default'=> ( !isset( $attributes['align']) ? 'left' : $attributes['align']),
                                'type'=>'select', 
                                'values'=>array(
                                    'left' => 'Align Left', 
                                    'center' => 'Align Center', 
                                    'right' => 'Align Right', 
                                ),
                            ),
                            'amount_width' => array(
                                'type' => 'slider', 
                                'default'=> (!isset($attributes['amount_width']) ? 0 : $attributes['amount_width']),
                                'min' => 0, 
                                'max' => 600, 
                                'steps' => 10, 
                                'name' => esc_html__( 'Amount wrapper width in pixels', 'super-forms' ), 
                                'desc' => esc_html__( 'Set to 0 to use default CSS width.', 'super-forms' )
                            ),
                            'wrapper_width' => $wrapper_width,
                            'margin' => array(
                                'name'=>esc_html__( 'Remove margin', 'super-forms' ),
                                'default'=> (!isset($attributes['margin']) ? '' : $attributes['margin']),
                                'type'=>'select',
                                'values'=>array(
                                    ''=>'No',
                                    'no_margin'=>'Yes',
                                )
                            ),
	                        'exclude' => $exclude,
                            'exclude_entry' => $exclude_entry, // @since 1.8.3 - option to skip saving value in Contact Entries
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


    }
        
endif;


/**
 * Returns the main instance of SUPER_Calculator to prevent the need to use globals.
 *
 * @return SUPER_Calculator
 */
if( !function_exists('SUPER_Calculator') ){
    function SUPER_Calculator() {
        return SUPER_Calculator::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Calculator'] = SUPER_Calculator();
}
