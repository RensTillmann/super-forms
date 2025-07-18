<?php
/**
 * Super Forms - Signature
 *
 * @package   Super Forms - Signature
 * @author    WebRehab
 * @link      http://super-forms.com
 * @copyright 2022 by WebRehab
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Signature
 * Description: Adds an extra element that allows users to sign their signature before submitting the form
 * Version:     1.8.5
 * Plugin URI:  http://super-forms.com
 * Author URI:  http://super-forms.com
 * Author:      WebRehab
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

if ( ! class_exists( 'SUPER_Signature' ) ) :


	/**
	 * Main SUPER_Signature Class
	 *
	 * @class SUPER_Signature
	 * @version 1.0.0
	 */
	final class SUPER_Signature {


		/**
		 * @var string
		 *
		 *  @since      1.0.0
		 */
		public $version = '1.8.5';


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
		 * Main SUPER_Signature Instance
		 *
		 * Ensures only one instance of SUPER_Signature is loaded or can be loaded.
		 *
		 * @static
		 * @see SUPER_Signature()
		 * @return SUPER_Signature - Main instance
		 *
		 *  @since      1.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}


		/**
		 * SUPER_Signature Constructor.
		 *
		 *  @since      1.0.0
		 */
		public function __construct() {
			$this->init_hooks();
			do_action( 'super_signature_loaded' );
		}


		/**
		 * Define constant if not already set
		 *
		 * @param  string      $name
		 * @param  string|bool $value
		 *
		 *  @since      1.0.0
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}


		/**
		 * What type of request is this?
		 *
		 * string $type ajax, frontend or admin
		 *
		 * @return bool
		 *
		 *  @since      1.0.0
		 */
		private function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin();
				case 'ajax':
					return defined( 'DOING_AJAX' );
				case 'cron':
					return defined( 'DOING_CRON' );
				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}
		}


		/**
		 * Hook into actions and filters
		 *
		 *  @since      1.0.0
		 */
		private function init_hooks() {

			// Actions since 1.0.0
			add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_signature_element' ), 10, 2 );

			// This filter should not just be called for ajax request because of possible scheduled Triggers (Send E-mail action)
			add_filter( 'super_before_email_loop_data_filter', array( $this, 'add_signature_to_email_loop' ), 10, 2 );

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
			}

			if ( $this->is_request( 'ajax' ) ) {
				add_action( 'super_before_email_loop_data', array( $this, 'continue_after_signature' ) );
			}
		}


		/**
		 * Add dynamic JavaScript functions
		 *
		 *  @since      1.0.0
		 */
		public static function add_dynamic_function( $functions ) {

			// @since 6.4.0 responsive signature (reszie canvas upon turning mobile, also required for signatures that are inside a multi-part)
			$functions['after_responsive_form_hook'][] = array(
				'name' => 'init_signature_resize',
			);
			// @since 1.2.2
			$functions['after_duplicating_column_hook'][]          = array(
				'name' => 'init_signature_after_duplicating_column',
			);
			$functions['after_appending_duplicated_column_hook'][] = array(
				'name' => 'init_remove_initialized_class',
			);
			$functions['after_form_cleared_hook'][]                = array(
				'name' => 'init_clear_signatures',
			);
			$functions['after_field_change_blur_hook'][]           = array(
				'name' => 'refresh_signature',
			);
			return $functions;
		}


		/**
		 * Enqueue scripts before ajax call is made
		 *
		 *  @since      1.0.2
		 */
		public static function load_scripts( $atts ) {
			if ( $atts['ajax'] ) {
				wp_enqueue_style( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/signature.css', array(), SUPER_VERSION );
				wp_enqueue_script( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/signature.js', array( 'super-common' ), SUPER_VERSION );
			}
		}


		/**
		 * Hook into stylesheets of the form and add styles for the signature element
		 *
		 *  @since      1.0.0
		 */
		public static function add_element_styles( $styles, $attributes ) {
			$s       = '.super-form-' . $attributes['id'] . ' ';
			$v       = $attributes['settings'];
			$styles .= $s . '.super-signature-canvas {';
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
			$assets_path              = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . '/assets/';
			$frontend_path            = $assets_path . 'css/frontend/';
			$array['super-signature'] = array(
				'src'     => $frontend_path . 'signature.css',
				'deps'    => '',
				'version' => SUPER_VERSION,
				'media'   => 'all',
				'screen'  => array(
					'super-forms_page_super_create_form',
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
			$assets_path              = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . '/assets/';
			$frontend_path            = $assets_path . 'js/frontend/';
			$array['super-signature'] = array(
				'src'     => $frontend_path . 'signature.js',
				'deps'    => array( 'super-common' ),
				'version' => SUPER_VERSION,
				'footer'  => false,
				'screen'  => array(
					'super-forms_page_super_create_form',
				),
				'method'  => 'enqueue',
			);
			return $array;
		}


		/**
		 * Handle the Signature element output
		 *
		 *  @since      1.0.0
		 */
		public static function signature( $x ) {
			extract(
				shortcode_atts(
					array(
						'tag'          => '',
						'atts'         => array(),
						'inner'        => array(),
						'shortcodes'   => null,
						'settings'     => array(),
						'i18n'         => null,
						'entry_data'   => null,
						'formProgress' => false,
					),
					$x
				)
			);

			// Fallback check for older super form versions
			if ( method_exists( 'SUPER_Common', 'generate_array_default_element_settings' ) ) {
				$defaults = SUPER_Common::generate_array_default_element_settings( $shortcodes, 'form_elements', $tag );
			} else {
				$defaults = array(
					'name'         => 'subtotal',
					'thickness'    => 2,
					'color'        => '#000000',
					'disallowEdit' => 'true',
					'bg_size'      => 150,
					'width'        => 0,
					'height'       => 100,
					'icon'         => 'pencil',
				);
			}
			$atts = wp_parse_args( $atts, $defaults );

			// @since Super Forms 4.7.0 - translation
			if ( method_exists( 'SUPER_Shortcodes', 'merge_i18n' ) ) {
				$atts = SUPER_Shortcodes::merge_i18n( $atts, $i18n );
			}

			if ( empty( $atts['bg_size'] ) ) {
				$atts['bg_size'] = 150;
			}
			if ( empty( $atts['width'] ) ) {
				$atts['width'] = 0;
			}
			if ( empty( $atts['height'] ) ) {
				$atts['height'] = 100;
			}
			if ( empty( $atts['thickness'] ) ) {
				$atts['thickness'] = 2;
			}
			if ( empty( $atts['color'] ) ) {
				$atts['color'] = '#000000';
			}

			$result = '';
			if ( SUPER_Signature()->is_request( 'ajax' ) ) {
				$url     = plugin_dir_url( __FILE__ ) . 'assets/css/frontend/signature.css';
				$css     = wp_remote_fopen( $url );
				$result .= '<style>' . $css . '</style>';
			} else {
				wp_enqueue_style( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/signature.css', array(), SUPER_VERSION );
			}
			wp_enqueue_script( 'super-signature', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/signature.js', array( 'super-common' ), SUPER_VERSION );

			$result .= SUPER_Shortcodes::opening_tag(
				array(
					'tag'      => $tag,
					'atts'     => $atts,
					'settings' => $settings,
				)
			);
			$result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );

			if ( ! isset( $atts['background_img'] ) ) {
				$atts['background_img'] = 0;
			}
			$attachment_id = absint( $atts['background_img'] );
			if ( $attachment_id === 0 ) {
				$url = plugin_dir_url( __FILE__ ) . 'assets/images/sign-here.png';
			} else {
				$url = wp_get_attachment_url( $attachment_id );
			}
			$styles  = '';
			$styles .= 'height:' . $atts['height'] . 'px;';
			$styles .= 'background-image:url(\'' . esc_url( $url ) . '\');';
			$styles .= 'background-size:' . $atts['bg_size'] . 'px;';
			$result .= '<div class="super-signature-canvas" style="' . $styles . '"><canvas></canvas></div>';
			$result .= '<span class="super-signature-clear"></span>';
			$result .= '<textarea style="display:none;" class="super-shortcode-field"';
			$result .= ' name="' . esc_attr( $atts['name'] ) . '"';
			$result .= ' data-thickness="' . esc_attr( $atts['thickness'] ) . '"';
			$result .= ' data-color="' . esc_attr( $atts['color'] ) . '"';
			$result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
			// Get the value for from entry data
			$entry_data_value = SUPER_Shortcodes::get_entry_data_value( $tag, '', $atts['name'], $entry_data );
			if ( ! isset( $atts['value'] ) ) {
				$atts['value'] = '';
			}
			if ( ( isset( $entry_data_value ) ) && ( $entry_data_value !== '' ) ) {
				$atts['value'] = $entry_data_value;
			}
			if ( $formProgress === false && $atts['disallowEdit'] === 'true' && $atts['value'] !== '' ) {
				$result .= ' data-disallowEdit="' . esc_attr( $atts['disallowEdit'] ) . '"';
			}
			$result .= ' />' . $atts['value'] . '</textarea>';
			$result .= '</div>';
			$result .= SUPER_Shortcodes::loop_conditions( $atts, $tag, $settings );
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
			require SUPER_PLUGIN_DIR . '/includes/shortcodes/predefined-arrays.php';

			$array['form_elements']['shortcodes']['signature_predefined'] = array(
				'name'       => esc_html__( 'Signature', 'super-forms' ),
				'icon'       => 'signature',
				'predefined' => array(
					array(
						'tag'   => 'signature',
						'group' => 'form_elements',
						'data'  => array(
							'name'          => esc_html__( 'signature', 'super-forms' ),
							'email'         => esc_html__( 'Signature:', 'super-forms' ),
							'validation'    => 'empty',
							'height'        => 150,
							'wrapper_width' => 450,
							'icon'          => 'signature',
						),
					),
				),
			);
			$array['form_elements']['shortcodes']['signature']            = array(
				'hidden'   => true,
				'callback' => 'SUPER_Signature::signature',
				'name'     => esc_html__( 'Signature', 'super-forms' ),
				'icon'     => 'signature',
				'atts'     => array(
					'general'           => array(
						'name'   => esc_html__( 'General', 'super-forms' ),
						'fields' => array(
							'name'           => SUPER_Shortcodes::name( $attributes, '' ),
							'email'          => SUPER_Shortcodes::email( $attributes, '' ),
							'label'          => $label,
							'description'    => $description,
							'thickness'      => SUPER_Shortcodes::width( $attributes = null, $default = 1, $min = 1, $max = 20, $steps = 1, $name = esc_html__( 'Line Thickness', 'super-forms' ), $desc = esc_html__( 'The thickness of the signature when drawing', 'super-forms' ) ),
							'color'          => array(
								'name'    => esc_html__( 'Line color', 'super-forms' ),
								'default' => ( ! isset( $attributes['color'] ) ? '' : $attributes['color'] ),
								'type'    => 'color',
							),
							'disallowEdit'   => array(
								'default'     => ( ! isset( $attributes['disallowEdit'] ) ? 'true' : $attributes['disallowEdit'] ),
								'type'        => 'checkbox',
								'values'      => array(
									'true' => esc_html__( 'Disallow users to change existing signature when form was populated with data', 'super-forms' ),
								),
								'allow_empty' => true, // For backward compatibility with older forms
							),
							'background_img' => array(
								'name'    => esc_html__( 'Custom sign here image', 'super-forms' ),
								'desc'    => esc_html__( 'Background image to show the user they can draw a signature', 'super-forms' ),
								'default' => '',
								'type'    => 'image',
							),
							'bg_size'        => SUPER_Shortcodes::width( $attributes = null, $default = '', $min = 0, $max = 1000, $steps = 10, $name = esc_html__( 'Image background size', 'super-forms' ), $desc = esc_html__( 'You can adjust the size of your background image here', 'super-forms' ) ),
							'tooltip'        => $tooltip,
							'validation'     => array(
								'name'    => esc_html__( 'Validation', 'super-forms' ),
								'desc'    => esc_html__( 'How does this field need to be validated?', 'super-forms' ),
								'default' => ( ! isset( $attributes['validation'] ) ? 'none' : $attributes['validation'] ),
								'type'    => 'select',
								'values'  => array(
									'none'  => esc_html__( 'None', 'super-forms' ),
									'empty' => esc_html__( 'Required Field (not empty)', 'super-forms' ),
								),
							),
							'exclude'        => $exclude,
							'exclude_entry'  => $exclude_entry,
							'error'          => $error,
							'emptyError'     => ( isset( $emptyError ) ? $emptyError : '' ),
						),
					),
					'advanced'          => array(
						'name'   => esc_html__( 'Advanced', 'super-forms' ),
						'fields' => array(
							'grouped'        => $grouped,
							'width'          => SUPER_Shortcodes::width( $attributes = null, $default = '', $min = 0, $max = 600, $steps = 10, $name = null, $desc = null ),
							'height'         => SUPER_Shortcodes::width( $attributes = null, $default = '', $min = 0, $max = 600, $steps = 10, $name = esc_html__( 'Field height in pixels', 'super-forms' ), $desc = esc_html__( 'Set to 0 to use default CSS height', 'super-forms' ) ),
							'wrapper_width'  => $wrapper_width,
							'error_position' => $error_position,
						),
					),
					'icon'              => array(
						'name'   => esc_html__( 'Icon', 'super-forms' ),
						'fields' => array(
							'icon_position' => $icon_position,
							'icon_align'    => $icon_align,
							'icon'          => SUPER_Shortcodes::icon( $attributes, '' ),
						),
					),
					'conditional_logic' => $conditional_logic_array,
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
			$type            = isset( $data['type'] ) ? $data['type'] : '';
			$v               = $data['v'];
			$data['status']  = '';
			$data['exclude'] = '';
			$data['row']     = '';
			if ( ! isset( $v['value'] ) ) {
				return $data;
			}
			if ( ( strpos( $v['value'], 'data:image/png;base64,' ) !== false ) || ( strpos( $v['value'], 'data:image/jpeg;base64,' ) !== false ) ) {
				// Determine the actual image type
				if ( strpos( $v['value'], 'data:image/jpeg;base64,' ) !== false ) {
					$signature_filename = $v['name'] . '.jpg';
					$signature_type     = 'image/jpeg';
				} else {
					$signature_filename = $v['name'] . '.png';
					$signature_type     = 'image/png';
				}
				$signature_contact_image_data = $v['value'];
				$signature_data               = substr( $signature_contact_image_data, strpos( $signature_contact_image_data, ',' ) );
				$signature_encoding           = 'base64';
				$string_attachment            = array(
					'data'     => $signature_data,
					'filename' => $signature_filename,
					'encoding' => $signature_encoding,
					'type'     => $signature_type,
				);
				// Check if we should exclude the file from emails
				// 0 = Do not exclude from e-mails
				// 1 = Exclude from confirmation email
				// 2 = Exclude from all email
				// 3 = Exclude from admin email
				if ( $type === 'confirm' ) {
					if ( $v['exclude'] == 1 || $v['exclude'] == 2 ) {
						// Excluded
					} else {
						$data['confirm_string_attachments'][] = $string_attachment;
					}
				}
				if ( $type === 'admin' ) {
					if ( $v['exclude'] == 3 || $v['exclude'] == 2 ) {
						// Excluded
					} else {
						$data['string_attachments'][] = $string_attachment;
					}
				}
				if ( $type === 'listing' ) {
					$data['string_attachments'][] = $string_attachment;
				}
				if ( isset( $v['label'] ) ) {
					$row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
				}
				// OLD: if( isset( $v['value'] ) ) $row = str_replace( '{loop_value}', $signature_filename . '<br /><img src="cid:'.sanitize_title_with_dashes($signature_filename).'" />', $row );
				// New way, inline signature via file instead of base64 string:
				// Signature is temporarily stored in /uploads/tmp/xxxxx/signature-field-name.png
				// After the E-mail is send it is deleted from the server automatically
				if ( isset( $v['value'] ) ) {
					$row = str_replace( '{loop_value}', '<img src="cid:' . sanitize_title_with_dashes( $signature_filename ) . '" />', $row );
				}
				$data['status']  = 'continue';
				$data['exclude'] = $v['exclude'];
				$data['row']     = $row;
			}
			return $data;
		}
	}

endif;


/**
 * Returns the main instance of SUPER_Signature to prevent the need to use globals.
 *
 * @return SUPER_Signature
 */
if ( ! function_exists( 'SUPER_Signature' ) ) {
	function SUPER_Signature() {
		return SUPER_Signature::instance();
	}
	// Global for backwards compatibility.
	$GLOBALS['SUPER_Signature'] = SUPER_Signature();
}
