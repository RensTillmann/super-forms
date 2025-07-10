<?php
/**
 * Super Forms - XML Attachment
 * Allows you to attach an XML file with the form data to the admin email as an attachment
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! class_exists( 'SUPER_XML_Attachment' ) ) :
	final class SUPER_XML_Attachment {
		protected static $_instance = null;
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}
		public function __construct() {
			$this->init_hooks();
		}
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
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
		private function init_hooks() {
			if ( $this->is_request( 'admin' ) ) {
				add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
			}
			if ( $this->is_request( 'ajax' ) ) {
				add_filter( 'super_before_sending_email_attachments_filter', array( $this, 'add_xml_attachment' ), 10, 2 );
			}
		}
		public static function add_xml_attachment( $attachments, $atts ) {
			error_log( 'add_xml_attachment() called' );
			// error_log('Raw $atts: ' . print_r($atts, true));
			$data    = isset( $atts['data'] ) ? $atts['data'] : array();
			$form_id = isset( $atts['form_id'] ) ? $atts['form_id'] : null;
			// Use options from trigger if present
			$xml_settings = null;
			if ( isset( $atts['options']['xml_attachment'] ) && is_array( $atts['options']['xml_attachment'] ) ) {
				$xml_settings = $atts['options']['xml_attachment'];
				error_log( 'Using $atts[options][xml_attachment] for settings' );
			} elseif ( isset( $atts['xml_attachment'] ) && is_array( $atts['xml_attachment'] ) ) {
				$xml_settings = $atts['xml_attachment'];
				error_log( 'Using $atts[xml_attachment] for settings' );
			} else {
				// fallback to legacy
				$xml_settings = isset( $atts['settings'] ) ? $atts['settings'] : $atts;
				error_log( 'Using legacy structure for XML settings' );
			}
			error_log( 'XML settings: ' . print_r( $xml_settings, true ) );
			// Check if enabled
			$enabled = isset( $xml_settings['enabled'] ) ? $xml_settings['enabled'] : ( isset( $xml_settings['xml_attachment_enable'] ) ? $xml_settings['xml_attachment_enable'] : '' );
			if ( $enabled === 'true' ) {
				$xml_attachment_name = isset( $xml_settings['name'] ) ? $xml_settings['name'] : ( isset( $xml_settings['xml_attachment_name'] ) ? $xml_settings['xml_attachment_name'] : 'super-xml-attachment' );
				$xml_attachment_name = SUPER_Common::email_tags( $xml_attachment_name, $data, $xml_settings );
				$xml_content         = isset( $xml_settings['content'] ) ? $xml_settings['content'] : ( isset( $xml_settings['xml_content'] ) ? $xml_settings['xml_content'] : '' );
				$xml_content         = SUPER_Common::email_tags( $xml_content, $data, $xml_settings );
				try {
					error_log( 'form_id: ' . $form_id );
					$d        = wp_upload_dir();
					$basename = sanitize_title_with_dashes( $xml_attachment_name ) . '.xml';
					$filename = trailingslashit( $d['path'] ) . $basename;
					file_put_contents( $filename, $xml_content );
					$attachment    = array(
						'post_mime_type' => 'application/xml',
						'post_title'     => preg_replace( '/\.[^.]+$/', '', $basename ),
						'post_content'   => '',
						'post_status'    => 'inherit',
					);
					$attachment_id = wp_insert_attachment( $attachment, $filename, 0 );
					add_post_meta( $attachment_id, 'super-forms-form-upload-file', true );
					$attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
					wp_update_attachment_metadata( $attachment_id, $attach_data );
					$attachments['xml-form-data.xml'] = wp_get_attachment_url( $attachment_id );
					error_log( 'XML attachment created: ' . $filename . ' (ID: ' . $attachment_id . ')' );
				} catch ( Exception $e ) {
					error_log( 'form_id: ' . $form_id );
					SUPER_Common::output_message(
						array(
							'msg'     => $e->getMessage(),
							'form_id' => absint( $form_id ),
						)
					);
				}
			}
			return $attachments;
		}
		public static function add_settings( $array, $x ) {
			$array['xml_attachment'] = array(
				'hidden' => 'settings',
				'name'   => esc_html__( 'XML Attachments', 'super-forms' ),
				'label'  => esc_html__( 'XML Attachments Settings', 'super-forms' ),
				'html'   => array(
					sprintf( esc_html__( '%1$s%2$sNote: %3$sXML attachment can be enabled via the [Triggers] TAB when sending an E-mail%4$s', 'super-forms' ), '<div class="sfui-notice sfui-desc">', '<strong>', '</strong>', '</div>' ),
				),
			);
			return $array;
		}
	}
endif;
if ( ! function_exists( 'SUPER_XML_Attachment' ) ) {
	function SUPER_XML_Attachment() {
		return SUPER_XML_Attachment::instance();
	}
	// Global for backwards compatibility.
	$GLOBALS['SUPER_XML_Attachment'] = SUPER_XML_Attachment();
}
