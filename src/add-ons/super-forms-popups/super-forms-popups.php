<?php
/**
 * Super Forms - Popups
 *
 * @package   Super Forms - Popups
 * @author    feeling4design 
 * @link      http://f4d.nl/super-forms
 * @copyright 2022 by feeling4design
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Popups
 * Description: Create fully customizable popups for Super Forms
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

if( !class_exists('SUPER_Popup') ) :


    /**
     * Main SUPER_Popup Class
     *
     * @class SUPER_Popup
     * @version 1.0.0
     */
    final class SUPER_Popup {
    
        
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $version = '1.7.3';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'popups';
        public $add_on_name = 'Popups';

        
        /**
         * @var SUPER_Popup The single instance of the class
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
         * Main SUPER_Popup Instance
         *
         * Ensures only one instance of SUPER_Popup is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Popup()
         * @return SUPER_Popup - Main instance
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
         * SUPER_Popup Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('SUPER_Popup_loaded');
        }
        
        
        /**
         * Define constant if not already set
         *
         * @param  string $name
         * @param  string|bool $value
         *
         *  @since  1.0.0
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
            
            add_shortcode( 'super-popup', array( $this, 'popup_shortcode_func' ) );
            add_action( 'wp_ajax_super_set_popup_expire_cookie', array( $this, 'set_popup_expire_cookie' ) ); 
            add_action( 'wp_ajax_nopriv_super_set_popup_expire_cookie', array( $this, 'set_popup_expire_cookie' ) ); 

            if ( $this->is_request( 'frontend' ) ) {
                
                add_filter( 'super_form_settings_filter', array( $this, 'remove_preloader' ), 50, 2 ); 
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 100, 2 );
                add_filter( 'super_form_styles_filter', array( $this, 'add_popup_styles' ), 10, 2 );

                add_action( 'super_after_enqueue_element_scripts_action', array( $this, 'load_scripts' ) );
                add_action( 'super_form_before_do_shortcode_filter', array( $this, 'add_form_inside_popup_wrapper'  ), 50, 2 );

            }
            if ( $this->is_request( 'admin' ) ) {
               
                add_filter( 'super_settings_after_export_import_filter', array( $this, 'register_popup_settigs' ), 50, 2 ); 
                add_filter( 'super_form_styles_filter', array( $this, 'add_popup_styles' ), 10, 2 );
                add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 100, 2 );

                add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
                add_action( 'admin_print_scripts', array( $this, 'localize_printed_scripts' ), 5 );
                add_action( 'admin_print_footer_scripts', array( $this, 'localize_printed_scripts' ), 5 );
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
         * Enqueue scripts for each admin page
         * 
         * @since       1.0.0
        */
        public function enqueue_scripts() {
            
            if ( function_exists( 'get_current_screen' ) ) {
                $current_screen = get_current_screen();
            }else{
                $current_screen = new stdClass();
                $current_screen->id = '';
            }

            // Enqueue Javascripts
            if( $enqueue_scripts = self::get_scripts() ) {
                foreach( $enqueue_scripts as $handle => $args ) {
                    if ( ( in_array( $current_screen->id, $args['screen'] ) ) || ( $args['screen'][0]=='all' ) ) {
                        if($args['method']=='register'){
                            self::$scripts[] = $handle;
                            wp_register_script( $handle, $args['src'], $args['deps'], $args['version'], $args['footer'] );
                        }else{
                            wp_enqueue_script( $handle, $args['src'], $args['deps'], $args['version'], $args['footer'] );
                        }
                    }
                }
            }
            
            // Enqueue Styles
            if( $enqueue_styles = self::get_styles() ) {
                foreach( $enqueue_styles as $handle => $args ) {
                    if ( ( in_array( $current_screen->id, $args['screen'] ) ) || ( $args['screen'][0]=='all' ) ) {
                        if($args['method']=='register'){
                            wp_register_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
                        }else{
                            wp_enqueue_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'] );
                        }
                    }
                }
            }
            
        }



        /**
         * Get styles for the backend
         *
         * @access private
         * @return array
         * [$handle, $src, $deps, $ver, $media]
         *
         * @since       1.0.0
        */
        public static function get_styles() {
            return apply_filters( 
                'super_enqueue_styles', 
                array(
                    'super-popup' => array(
                        'src'     => plugin_dir_url( __FILE__ ) . 'assets/css/popup.css',
                        'deps'    => array(),
                        'version' => SUPER_Popup()->version,
                        'media'   => 'all',
                        'screen'  => array(
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    )
                )
            );
        }
        
        
        /**
         * Get scripts for the backend
         *
         * @access private
         * @return array
         * [$handle, $src, $deps, $ver, $in_footer]
         *
         * @since       1.0.0
        */
        public static function get_scripts() {

            return apply_filters( 
                'super_enqueue_scripts', 
                array(
                    'css-plugin' => array(
                        'src'     => plugin_dir_url( __FILE__ ) . 'assets/js/css-plugin.js',
                        'deps'    => array( 'jquery', 'super-common' ),
                        'version' => SUPER_Popup()->version,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'ease-pack' => array(
                        'src'     => plugin_dir_url( __FILE__ ) . 'assets/js/ease-pack.js',
                        'deps'    => array( 'css-plugin' ),
                        'version' => SUPER_Popup()->version,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'tween-lite' => array(
                        'src'     => plugin_dir_url( __FILE__ ) . 'assets/js/tween-lite.js',
                        'deps'    => array( 'ease-pack' ),
                        'version' => SUPER_Popup()->version,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'enqueue',
                    ),
                    'super-popup' => array(
                        'src'     => plugin_dir_url( __FILE__ ) . 'assets/js/popup.js',
                        'deps'    => array(),
                        'version' => SUPER_Popup()->version,
                        'footer'  => false,
                        'screen'  => array( 
                            'super-forms_page_super_create_form',
                        ),
                        'method'  => 'register', // Register because we need to localize it
                        'localize'=> array(
                            'ajaxurl' => admin_url( 'admin-ajax.php' )
                        ),
                    ),
                )
            );
        }
        
        
        /**
         * Localize a script once.
         *
         * @access private
         * @param  string $handle
         *
         * @since       1.0.0
        */
        private static function localize_script( $handle ) {
            if ( ! in_array( $handle, self::$wp_localize_scripts ) && wp_script_is( $handle, 'registered' ) && ( $data = self::get_script_data( $handle ) ) ) {
                $name = str_replace( '-', '_', $handle ) . '_i18n';
                self::$wp_localize_scripts[] = $handle;
                wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
                wp_enqueue_script( $handle );
            }        
        }
        
        
        /**
         * Localize scripts only when enqueued
         *
         * @access private
         * @param  string $handle
         *
         * @since       1.0.0
        */
        public static function localize_printed_scripts() {
            foreach ( self::$scripts as $handle ) {
                self::localize_script( $handle );
            }
        }
        
        
        /**
         * Return data for script handles.
         * @access private
         * @param  string $handle
         * @return array|bool
        */
        private static function get_script_data( $handle ) {
            
            $scripts = self::get_scripts();
            if( isset( $scripts[$handle]['localize'] ) ) {
                return $scripts[$handle]['localize'];
            }
            
            return false;
        
        }


        /**
         * Popup shortcode URL
         *
         *  @since      1.0.0
        */
        public static function popup_shortcode_func( $atts, $content = "" ) {
            return '<a href="#super-popup-'.$atts['id'].'">'.$content.'</a>';
        }


        /**
         * Hook into the javascript functions
         *
         *  @since      1.0.0
        */
        public static function add_dynamic_function( $functions ) {
            
            $functions['after_responsive_form_hook'][] = array(
                'name' => 'init_responsive_popup'
            );
            $functions['before_submit_button_click_hook'][] = array(
                'name' => 'init_check_submit_button_close_popup'
            );
            $functions['after_email_send_hook'][] = array(
                'name' => 'init_set_expiration_cookie_on_submit_popup'
            );
            $functions['after_preview_loaded_hook'][] = array(
                'name' => 'init_show_preview_popup'
            );
            $functions['before_scrolling_to_error_hook'][] = array(
                'name' => 'init_before_scrolling_to_error_popup'
            );
            $functions['before_scrolling_to_message_hook'][] = array(
                'name' => 'init_before_scrolling_to_message_popup'
            );
            return $functions;
        }


        /**
         * Remove preloader
         *
         *  @since      1.0.0
        */
        public static function remove_preloader( $settings, $data ) {
            if( (isset($settings['popup_enabled'])) && ($settings['popup_enabled']=='true') ) {
                $settings['form_preload'] = 0;
            }
            return $settings;
        }


        /**
         * Set the popup expire cookie
         *
         *  @since      1.0.0
        */
        public static function set_popup_expire_cookie() {
            $form_id = absint($_POST['form_id']);
            if(!isset($_COOKIE['super_popup_expire_' . $form_id])) {
                $expire = floatval($_POST['expire']);
                if( $expire>0 ) {
                    setcookie( 'super_popup_expire_' . $form_id, $form_id, time() + ($expire * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
                }
            }
            die();
        }


        /**
         * Hook into stylesheets of the form and add styles for the popup
         *
         *  @since      1.0.0
        */
        public static function add_popup_styles( $styles, $data ) {
            $v = $data['settings'];   
            if( (isset($v['popup_enabled'])) && ($v['popup_enabled']=='true') ) {
                $v = SUPER_Popup()->form_configuration_data( $v );
                $v = json_decode($v, true);
                
                if(empty($v['overlay_color'])) $v['overlay_color'] = '#000000';
                if(empty($v['overlay_opacity'])) $v['overlay_opacity'] = '0.5';
                if(empty($v['width'])) $v['width'] = '700';
                if(empty($v['background_color'])) $v['background_color'] = '#ffffff';
                if(empty($v['background_image'])) $v['background_image'] = '';
                if(empty($v['enable_shadows'])) $v['enable_shadows'] = '';
                if(empty($v['background_image_size'])) $v['background_image_size'] = 'cover';

                // Body overflow background color / opacity
                $styles .= '.super-popup-wrapper-'.$data['id'].' {';
                    $styles .= 'background-color: ' . SUPER_Common::hex2rgb( $v['overlay_color'], $v['overlay_opacity'] ) . ';';
                $styles .= '}';

                $s = '.super-popup-'.$data['id'].' ';
                $styles .= $s.'{';
                    // Popup width
                    $styles .= 'width: ' . $v['width'] . 'px;';                    
                    if( $v['background_color']!='' ) {
                        $styles .= 'background-color: ' . $v['background_color'] . ';';
                    }
                    // Popup background image
                    if( $v['background_image']!='' ) {
                        $styles .= 'background-image: url(' . $v['background_image'] . ');';
                        if( $v['background_image_repeat']!='' ) {
                            $styles .= 'background-repeat: ' . $v['background_image_repeat'] . ';';
                        }
                        if( $v['background_image_size']!='' ) {
                            $styles .= 'background-size: ' . $v['background_image_size'] . ';';
                        }
                    }else{
                        $styles .= 'background-image: none;';
                    }
                    // Popup border / radius
                    if( !empty($v['enable_borders']) ) {
                        if( $v['border_size']>0 ) {
                            $styles .= 'border-width: ' . $v['border_size'] . 'px;';
                            $styles .= 'border-color: ' . $v['border_color'] . ';';
                            $styles .= 'border-style: solid;';
                        }
                        $border_radius = $v['border_radius_top_left'].'px ';
                        $border_radius .= $v['border_radius_top_right'].'px '; 
                        $border_radius .= $v['border_radius_bottom_right'].'px ';
                        $border_radius .= $v['border_radius_bottom_left'].'px '; 
                        $styles .= 'border-radius: ' . $border_radius . ';';
                        $styles .= '-moz-border-radius: ' . $border_radius . ';';
                        $styles .= '-webkit-border-radius: ' . $border_radius . ';';
                    }
                    // Popup shadow
                    if( $v['enable_shadows']=='true') {
                        $box_shadow = $v['shadow_horizontal_length'].'px ';
                        $box_shadow .= $v['shadow_vertical_length'].'px '; 
                        $box_shadow .= $v['blur_radius'].'px ';
                        $box_shadow .= $v['spread_radius'].'px '; 
                        $box_shadow .= SUPER_Common::hex2rgb( $v['shadow_color'], $v['shadow_opacity'] );
                        $styles .= '-webkit-box-shadow: ' . $box_shadow . ';';
                        $styles .= '-moz-box-shadow: ' . $box_shadow . ';';
                        $styles .= 'box-shadow: ' . $box_shadow . ';';
                    }
                $styles .= '}';

                if( (isset($v['close_btn'])) && ($v['close_btn']=='true') ) {
                    $styles .= $s.'.super-popup-close > .super-popup-close-icon {';
                        $styles .= 'color: ' . $v['close_btn_icon_color'] . ';';
                        $styles .= 'background-color: ' . $v['close_btn_bg_color'] . ';';
                        $styles .= 'font-size: ' . $v['close_btn_icon_size'] . 'px;';
                        $styles .= 'border-width: ' . $v['close_btn_border'] . 'px;';
                        if(!empty($v['close_btn_border_color'])) $styles .= 'border-color: ' . $v['close_btn_border_color'] . ';';
                        $styles .= 'border-style: solid;';
                        if( !empty($v['close_btn_padding']) ) {
                            $styles .= 'padding: ' . $v['close_btn_padding'] . ';';
                        }
                        if( ($v['close_btn_radius']!='') && ($v['close_btn_radius']>0) ) {
                            $styles .= '-webkit-border-radius: ' . $v['close_btn_radius'] . 'px;';
                            $styles .= '-moz-border-radius: ' . $v['close_btn_radius'] . 'px;';
                            $styles .= 'border-radius: ' . $v['close_btn_radius'] . 'px;';
                        }
                    $styles .= '}';

                    $styles .= $s.'.super-popup-close {';
                        $styles .= 'top: ' . $v['close_btn_top'] . 'px;';
                        $styles .= 'right: ' . $v['close_btn_right'] . 'px;';
                    $styles .= '}';

                    $styles .= $s.'.super-popup-close-label {';
                        if( !empty($v['close_btn_label_padding']) ) {
                            $styles .= 'padding: ' . $v['close_btn_label_padding'] . ';';
                        }
                        $styles .= 'color: ' . $v['close_btn_label_color'] . ';';
                        $styles .= 'background-color: ' . $v['close_btn_label_bg_color'] . ';';
                    $styles .= '}';
                }
            }
            return $styles;
        }


        /**
         *  Add the form inside the popup wrapper
         *
         *  @since      1.0.0
        */
        function add_form_inside_popup_wrapper( $result, $data ) {
            $form_id = absint($data['id']);

            $settings = $data["settings"];
            if( (isset($settings['popup_enabled'])) && ($settings['popup_enabled']=='true') ) {

                if(empty($settings['popup_logged_in'])) $settings['popup_logged_in'] = 'true';
                if(empty($settings['popup_not_logged_in'])) $settings['popup_not_logged_in'] = 'true';

                // Check if expiration is enabled and if cookie exists
                if( (!empty($settings['popup_expire_trigger'])) && ($settings['popup_expire']>0) ) {
                    if( isset($_COOKIE['super_popup_expire_' . $form_id]) ) {
                        return '';
                    }
                }

                // Generate popup HTML only if popup is enabled
                if( ( ($settings['popup_logged_in']=='true') && (is_user_logged_in()) ) || ( ($settings['popup_not_logged_in']=='true') && (!is_user_logged_in()) ) ) {
                    $form_html = $result;
                    $result = '';
                    $result .= '<div class="super-popup-wrapper-' . $form_id . ' super-popup-wrapper" style="opacity:0;z-index:-2147483648;"' . (!empty($settings['popup_clear_form']) ? ' data-clear-form="true"' : '') . '>';
                        $result .= '<div class="super-popup-' . $form_id . ' super-popup super-popup-sticky-'.($settings['popup_sticky']).'" style="opacity:0;">';
                
                            // Popup close button
                            if(empty($settings['popup_close_btn'])) $settings['popup_close_btn'] = 'true';
                            if( $settings['popup_close_btn']=='true' ) {
                                $result .= '<span class="super-popup-close">';
                                if( !empty($settings['popup_close_btn_label']) ) $result .= '<span class="super-popup-close-label">' . $settings['popup_close_btn_label'] . '</span>';
                                $result .= '<span class="super-popup-close-icon"></span>';
                                $result .= '</span>';    
                            }
                            
                            // Custom popup paddings
                            $styles = '';
                            if( !isset( $settings['popup_enable_padding'] ) ) $settings['popup_enable_padding'] = '';
                            if( $settings['popup_enable_padding']=='true' ) {
                                if( !isset( $settings['popup_padding'] ) ) $settings['popup_padding'] = '';
                                if( $settings['popup_padding']!='' ) {
                                    $styles = ' style="padding:' . $settings['popup_padding'] . ';"';
                                }
                            }

                            // Popup content
                            $result .= '<div class="super-popup-content"' . $styles . '>';
                                $result .= $form_html;
                            $result .= '</div>';
                            
                            $result .= '<textarea name="super-popup-settings">' . $this->form_configuration_data( $settings ) . '</textarea>';
                        $result .= '</div>';
                    $result .= '</div>';
                }else{
                    return '';
                }
            }
            return $result;
            
        }
       

        /**
         * Enqueue scripts
         *
         *  @since      1.0.0
        */
        public static function load_scripts( $data ) {
            if( (isset($data['settings']['popup_enabled'])) && ($data['settings']['popup_enabled']=='true') ) {
                wp_enqueue_style( 'super-popup', plugin_dir_url( __FILE__ ) . 'assets/css/popup.css', array(), SUPER_Popup()->version );
                wp_enqueue_script( 'css-plugin', plugin_dir_url( __FILE__ ) . 'assets/js/css-plugin.js', array( 'jquery', 'super-common' ), SUPER_Popup()->version );
                wp_enqueue_script( 'ease-pack', plugin_dir_url( __FILE__ ) . 'assets/js/ease-pack.js', array( 'css-plugin' ), SUPER_Popup()->version );
                wp_enqueue_script( 'tween-lite', plugin_dir_url( __FILE__ ) . 'assets/js/tween-lite.js', array( 'ease-pack' ), SUPER_Popup()->version );
                wp_enqueue_script( 'jquery-gsap', plugin_dir_url( __FILE__ ) . 'assets/js/jquery.gsap.js', array( 'tween-lite' ), SUPER_Popup()->version );
                wp_enqueue_script( 'super-popup', plugin_dir_url( __FILE__ ) . 'assets/js/popup.js', array( 'jquery-gsap' ), SUPER_Popup()->version );
                wp_localize_script( 'super-popup', 'super_popup_i18n', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
            }
        }


        /**
         *  Settings Data Array
         * 
         *  @since  1.0.0
         *  @return string configuration settings for javascript
        */
        public function form_configuration_data( $settings ) {
            $popup_settings = array_intersect_key($settings, array_flip(preg_grep('/^popup_/', array_keys($settings))));
            foreach ( $popup_settings as $k => $v ) {
                $old_k = $k;
                $k = str_replace( 'popup_', '', $k );
                if( $k=='background_image' ) {
                    $v = ((trim($v) != '' && intval($v) > 0) ? wp_get_attachment_url(absint($v)) : '');
                }
                unset($popup_settings[$old_k]);
                $popup_settings[$k] = $v;
            }
            return json_encode($popup_settings);
        }


        /**
         * Hook into popup settings 
         *
         *  @since      1.0.0
        */
        function register_popup_settigs( $array, $settings ) {

            $array['popup_settings'] = array(        
                'hidden' => 'settings',
                'name' => esc_html__( 'Popup Settings', 'super-forms' ),
                'label' => esc_html__( 'Popup Settings', 'super-forms' ),
                'html' => array(
                    sprintf( esc_html__( '%s%sNote:%s if you want to open the popup via a button you will need to put the form shortcode %s[super_form id="XXX"]%s on the page and add a button that user can click on to open the popup.%sFor example, you can use a button like so:%s%s%sOpen the Popup%s%s%sor with the use of a shortcode:%s%s[super-popup id=XXX]Open Form[/super-popup]%s%sJust replace XXX with the form ID. Via `Appearance > Menu` you can also add a `Custom Link` with URL set to %s to open the Popup from your site menu. In that case make sure you load the form on all pages. You can do this by putting the form shortcode in a footer widget.%s' ), '<div class="sfui-notice sfui-desc">', '<strong>', '</strong>', '<code>', '</code>', '<br />', '<br />', '<code>', esc_html('<a href="#super-popup-XXX">'), esc_html('</a>'), '</code>', '<br />', '<br />', '<code>', '</code>', '<br />', '<code>#super-popup-XXX</code>', '</div>' ),
                ),
                'fields' => array(
                    'popup_enabled' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Enable Popup', 'super-forms' ),
                        ),
                    ),
                    'popup_logged_in' => array(
                        'default' =>  'true',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Show popup to logged in users', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'popup_not_logged_in' => array(
                        'default' =>  'true',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Show popup to none logged in users', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'popup_page_load' => array(
                        'default' =>  'true',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display popup on page load', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'popup_exit_intent' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display popup on exit intent', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_leave' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display popup on page leave/close/exit', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_leave_msg' => array(
                        'name' => esc_html__( 'Allert message text (browser requires this)', 'super-forms' ),
                        'default' =>  esc_html__( "Wait stay with us! Please take the time to fill out our form!?", "super-forms" ),
                        'filter'=>true,
                        'parent' => 'popup_leave',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'popup_enable_scrolling' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display popup after xx% scrolled', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_scrolled' => array(
                        'default' =>  '0',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>100,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_scrolling',
                        'filter_value' => 'true',
                    ),
                    'popup_enable_seconds' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display popup after X seconds', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_seconds' => array(
                        'default' =>  '0',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>100,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_seconds',
                        'filter_value' => 'true',
                    ),
                    'popup_enable_inactivity' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display popup after X seconds of inactivity', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_inactivity' => array(
                        'default' =>  '0',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>100,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_inactivity',
                        'filter_value' => 'true',
                    ),
                    'popup_enable_schedule' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display popup between date range (schedule)', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_from' => array(
                        'name' => esc_html__( 'From date', 'super-forms' ),
                        'desc' => esc_html__( 'From date (yyyy-mm-dd): Display the popup within specific date range', 'super-forms' ),
                        'default' =>  date('Y-m-d'),
                        'parent' => 'popup_enable_schedule',
                        'filter_value' => 'true',
                        'filter'=>true,
                    ), 
                    'popup_till' => array(
                        'name' => esc_html__( 'Till date', 'super-forms' ),
                        'desc' => esc_html__( 'Till date (yyyy-mm-dd): Display the popup within specific date range', 'super-forms' ),
                        'default' =>  date('Y-m-d'),
                        'parent' => 'popup_enable_schedule',
                        'filter_value' => 'true',
                        'filter'=>true,
                    ),
                    'popup_disable_closing' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Disable popup closing', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn' => array(
                        'default' =>  'true',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Display close (X) button', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'popup_clear_form' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Clear form after closing popup', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'popup_close_btn_icon_color' => array(
                        'name' => esc_html__( 'Close button icon color', 'super-forms' ),
                        'type'=>'color',  
                        'default' =>  '#fff',
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn_bg_color' => array(
                        'name' => esc_html__( 'Close button background color (leave blank for none)', 'super-forms' ),
                        'type'=>'color',  
                        'default' =>  '#00bc65',
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn_label' => array(
                        'name' => esc_html__( 'Close button label text e.g: Close', 'super-forms' ),
                        'type'=>'text',  
                        'default' =>  '',
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn_label_color' => array(
                        'name' => esc_html__( 'Close button label color', 'super-forms' ),
                        'type'=>'color',  
                        'default' =>  '#00bc65',
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn_label_bg_color' => array(
                        'name' => esc_html__( 'Close button label bg color', 'super-forms' ),
                        'type'=>'color',  
                        'default' =>  '#00bc65',
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn_label_padding' => array(
                        'name' => esc_html__( 'Close button label paddings e.g: 0px 0px 0px 0px', 'super-forms' ),
                        'label' => esc_html__( '(leave blank for default paddings)', 'super-forms' ),
                        'default' =>  '',
                        'type'=>'text',
                        'filter'=>true,
                        'parent'=>'popup_close_btn',
                        'filter_value'=>'true'
                    ),
                    'popup_close_btn_icon_size' => array(
                        'name' => esc_html__( 'Close button icon size in pixels (px)', 'super-forms' ),
                        'default' =>  '14',
                        'type'=>'slider',
                        'min'=>10,
                        'max'=>50,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn_border' => array(
                        'name' => esc_html__( 'Close button border size in pixels (px)', 'super-forms' ),
                        'default' =>  '0',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>10,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn_border_color' => array(
                        'name' => esc_html__( 'Close button border color', 'super-forms' ),
                        'type'=>'color',  
                        'default' =>  '',
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',  
                    ),
                    'popup_close_btn_top' => array(
                        'name' => esc_html__( 'Close button position top in pixels (px)', 'super-forms' ),
                        'default' =>  '0',
                        'type'=>'slider',
                        'min'=>-100,
                        'max'=>100,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn_right' => array(
                        'name' => esc_html__( 'Close button position right in pixels (px)', 'super-forms' ),
                        'default' =>  '0',
                        'type'=>'slider',
                        'min'=>-100,
                        'max'=>100,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_close_btn_padding' => array(
                        'name' => esc_html__( 'Close button paddings e.g: 0px 0px 0px 0px', 'super-forms' ),
                        'label' => esc_html__( '(leave blank for default paddings)', 'super-forms' ),
                        'default' =>  '',
                        'type'=>'text',
                        'filter'=>true,
                        'parent'=>'popup_close_btn',
                        'filter_value'=>'true'
                    ),
                    'popup_close_btn_radius' => array(
                        'name' => esc_html__( 'Close button border radius in pixels (px)', 'super-forms' ),
                        'default' =>  '0',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>100,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_close_btn',
                        'filter_value' => 'true',
                    ),
                    'popup_enable_padding' => array(
                        'default' =>  '',
                        'type' => 'checkbox', 
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Enable custom popup padding', 'super-forms' ),
                        ),
                        'filter'=>true,
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_padding' => array(
                        'name' => esc_html__( 'Popup paddings e.g: 0px 0px 0px 0px', 'super-forms' ),
                        'label' => esc_html__( '(leave blank for default paddings)', 'super-forms' ),
                        'default' =>  '',
                        'type'=>'text',
                        'filter'=>true,
                        'parent'=>'popup_enable_padding',
                        'filter_value'=>'true'
                    ),
                    'popup_expire_trigger' => array(
                        'name' => esc_html__( 'Enable expiration cookie (show popup only once)', 'super-forms' ),
                        'type'=>'select',
                        'default' =>  '',
                        'values'=>array( 
                            '' => esc_html__( 'Disabled', 'super-forms' ),
                            'view' => esc_html__( 'When popup has been viewed', 'super-forms' ),
                            'close' => esc_html__( 'When popup has been closed', 'super-forms' ),
                            'submit' => esc_html__( 'When form has been submitted', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                        'filter'=>true,
                    ),
                    'popup_expire' => array(
                        'name' => esc_html__( 'Expiration time in days', 'super-forms' ),
                        'default' =>  '1',
                        'type'=>'slider',
                        'min'=>1,
                        'max'=>365,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_expire_trigger',
                        'filter_value' => 'view,close,submit',
                    ),

                    'popup_width' => array(
                        'name' => esc_html__( 'Popup width in pixels (px)', 'super-forms' ),
                        'default' =>  '700',
                        'type'=>'slider',
                        'min'=>360,
                        'max'=>1000,
                        'steps'=>10,
                        'filter'=>true,
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_background_color' => array(
                        'name' => esc_html__( 'Popup background color', 'super-forms' ),
                        'type'=>'color',  
                        'default' =>  '#ffffff',
                        'filter'=>true,
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_overlay_color' => array(
                        'name' => esc_html__( 'Body overlay color', 'super-forms' ),
                        'type'=>'color',  
                        'default' =>  '#000000',
                        'filter'=>true,
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ), 
                    'popup_overlay_opacity' => array(
                        'name' => esc_html__( 'Body overlay opacity', 'super-forms' ),
                        'default' =>  '0.5',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>1,
                        'steps'=>0.1,
                        'filter'=>true,
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_background_image' => array(
                        'name' => esc_html__( 'Background Image', 'super-forms' ),
                        'type'=>'image',
                        'filter'=>true,
                        'default' =>  '0',
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_background_image_repeat' => array(
                        'name' => esc_html__( 'Background Image Repeat', 'super-forms' ),
                        'filter'=>true,
                        'type'=>'select',
                        'filter'=>true,
                        'default' =>  'no-repeat',
                        'values'=>array( 
                            'no-repeat' => esc_html__( 'No (no-repeat)', 'super-forms' ),
                            'repeat' => esc_html__( 'Repeat (repeat)', 'super-forms' ),
                            'repeat-x' => esc_html__( 'Repeat X (repeat-x)', 'super-forms' ),
                            'repeat-y' => esc_html__( 'Repeat Y (repeat-y)', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_background_image_size' => array(
                        'name' => esc_html__( 'Background Image Size', 'super-forms' ),
                        'filter'=>true,
                        'type'=>'select',
                        'default' =>  'cover',
                        'values'=>array( 
                            'inherit' => esc_html__( 'Default (inherit)', 'super-forms' ),
                            'contain' => esc_html__( 'Contain / Fit (contain)', 'super-forms' ),
                            'cover' => esc_html__( 'Cover / Fit (cover)', 'super-forms' ) 
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_slide' => array(
                        'name' => esc_html__( 'Popup slide in', 'super-forms' ),
                        'desc' => esc_html__( 'Slide in: From Top, Right, Bottom or Left', 'super-forms' ),
                        'filter'=>true,
                        'type'=>'select',
                        'default' =>  'none',
                        'values'=>array(
                            'none' => esc_html__( 'None (default)', 'super-forms' ),
                            'from_top' => esc_html__( 'From Top', 'super-forms' ),
                            'from_right' => esc_html__( 'From Right', 'super-forms' ),
                            'from_bottom' => esc_html__( 'From Bottom', 'super-forms' ),
                            'from_left' => esc_html__( 'From Left', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ), 
                    'popup_slide_duration' => array(
                        'name' => esc_html__( 'Popup Slide In duration in milliseconds', 'super-forms' ),
                        'desc' => esc_html__( 'Slide In duration in milliseconds (0 is no fade effect)', 'super-forms' ),
                        'default' =>  '300',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>5000,
                        'steps'=>100,
                        'filter'=>true,
                        'parent' => 'popup_slide',
                        'filter_value' => 'from_top,from_right,from_bottom,from_left',
                    ),
                    'popup_fade_duration' => array(
                        'name' => esc_html__( 'Popup FadeIn duration in milliseconds', 'super-forms' ),
                        'desc' => esc_html__( 'FadeIn duration in milliseconds (0 is no fade effect)', 'super-forms' ),
                        'default' =>  '300',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>5000,
                        'steps'=>100,
                        'filter'=>true,
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_fade_out_duration' => array(
                        'name' => esc_html__( 'Popup FadeOut duration in milliseconds', 'super-forms' ),
                        'desc' => esc_html__( 'FadeOut duration in milliseconds (0 is no fade effect)', 'super-forms' ),
                        'default' =>  '300',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>5000,
                        'steps'=>100,
                        'filter'=>true,
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_sticky' => array(
                        'name' => esc_html__( 'Make popup sticky', 'super-forms' ),
                        'desc' => esc_html__( 'Stick to top, right, bottom or left', 'super-forms' ),
                        'filter'=>true,
                        'type'=>'select',
                        'default' =>  'default',
                        'values'=>array(
                            'default' => esc_html__( 'default', 'super-forms' ),
                            'top' => esc_html__( 'Top', 'super-forms' ),
                            'right' => esc_html__( 'Right', 'super-forms' ),
                            'bottom' => esc_html__( 'Bottom', 'super-forms' ),
                            'left' => esc_html__( 'Left', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_enable_borders' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Enable Popup Border', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_border_size' => array(
                        'name' => esc_html__( 'Border size in pixels (px)', 'super-forms' ),
                        'default' =>  '0',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>10,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_borders',
                        'filter_value' => 'true',
                    ),
                    'popup_border_color' => array(
                        'name' => esc_html__( 'Border color', 'super-forms' ),
                        'type'=>'color',  
                        'default' =>  '#00bc65',
                        'filter'=>true,
                        'parent' => 'popup_enable_borders',
                        'filter_value' => 'true',  
                    ),
                    'popup_border_radius_top_left' => array(
                        'name' => esc_html__( 'Border Radius Top Left', 'super-forms' ), 
                        'default' =>  '10',
                        'type'=>'slider',
                        'filter'=>true,
                        'min'=>0,
                        'max'=>200,
                        'steps'=>1,
                        'parent' => 'popup_enable_borders',
                        'filter_value' => 'true',
                    ),
                    'popup_border_radius_top_right' => array(
                        'name' => esc_html__( 'Border Radius Top Right', 'super-forms' ), 
                        'default' =>  '10',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>200,
                        'filter'=>true,
                        'steps'=>1,
                        'parent' => 'popup_enable_borders',
                        'filter_value' => 'true',
                    ),
                    'popup_border_radius_bottom_left' => array(
                        'name' => esc_html__( 'Border Radius Bottom Left', 'super-forms' ), 
                        'default' =>  '10',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>200,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_borders',
                        'filter_value' => 'true',
                    ),
                    'popup_border_radius_bottom_right' => array(
                        'name' => esc_html__( 'Border Radius Bottom Right', 'super-forms' ), 
                        'default' =>  '10',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>200,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_borders',
                        'filter_value' => 'true',
                    ),

                    'popup_enable_shadows' => array(
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Enable Popup Shadows', 'super-forms' ),
                        ),
                        'parent' => 'popup_enabled',
                        'filter_value' => 'true',
                    ),
                    'popup_shadow_horizontal_length' => array(
                        'name' => esc_html__( 'Shadow Horizontal Length', 'super-forms' ),
                        'default' =>  '5',
                        'type'=>'slider',
                        'min'=>-200,
                        'max'=>200,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_shadows',
                        'filter_value' => 'true',
                    ),
                    'popup_shadow_vertical_length' => array(
                        'name' => esc_html__( 'Shadow Vertical Length', 'super-forms' ),
                        'default' =>  '5',
                        'type'=>'slider',
                        'min'=>-200,
                        'max'=>200,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_shadows',
                        'filter_value' => 'true',
                    ),
                    'popup_blur_radius' => array(
                        'name' => esc_html__( 'Shadow Blur Radius', 'super-forms' ),
                        'default' =>  '15',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>300,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_shadows',
                        'filter_value' => 'true',
                    ),
                    'popup_spread_radius' => array(
                        'name' => esc_html__( 'Shadow Spread Radius', 'super-forms' ),
                        'default' =>  '3',
                        'type'=>'slider',
                        'min'=>-200,
                        'max'=>200,
                        'steps'=>1,
                        'filter'=>true,
                        'parent' => 'popup_enable_shadows',
                        'filter_value' => 'true',
                    ),
                    'popup_shadow_color' => array(
                        'name' => esc_html__( 'Shadow Color', 'super-forms' ),
                        'type'=>'color',  
                        'default' =>  '#000000',
                        'filter'=>true,
                        'parent' => 'popup_enable_shadows',
                        'filter_value' => 'true',
                    ),
                    'popup_shadow_opacity' => array(
                        'name' => esc_html__( 'Shadow Opacity', 'super-forms' ),
                        'default' =>  '0.7',
                        'type'=>'slider',
                        'min'=>0,
                        'max'=>1,
                        'steps'=>0.05,
                        'filter'=>true,
                        'parent' => 'popup_enable_shadows',
                        'filter_value' => 'true',
                    ),
                 ), 
            ); 
            return $array;
        }


}
endif;

/**
 * Returns the main instance of SUPER_Popup to prevent the need to use globals.
 *
 * @return SUPER_Popup
 */
if( !function_exists('SUPER_Popup') ){
    function SUPER_Popup() {
        return SUPER_Popup::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Popup'] = SUPER_Popup();
}
