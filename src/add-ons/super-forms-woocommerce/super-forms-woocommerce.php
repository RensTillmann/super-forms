<?php
/**
 * Super Forms - WooCommerce Checkout
 *
 * @package   Super Forms - WooCommerce Checkout
 * @author    WebRehab
 * @link      http://super-forms.com
 * @copyright 2022 by WebRehab
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - WooCommerce Checkout
 * Description: Checkout with WooCommerce after form submission. Charge users for registering or posting content.
 * Version:     1.9.5
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

if ( ! class_exists( 'SUPER_WooCommerce' ) ) :


	/**
	 * Main SUPER_WooCommerce Class
	 *
	 * @class SUPER_WooCommerce
	 */
	final class SUPER_WooCommerce {


		/**
		 * @var string
		 *
		 *  @since      1.0.0
		 */
		public $version = '1.9.5';


		/**
		 * @var string
		 *
		 *  @since      1.1.0
		 */
		public $add_on_slug = 'woocommerce';
		public $add_on_name = 'WooCommerce Checkout';


		/**
		 * @var SUPER_WooCommerce The single instance of the class
		 *
		 *  @since      1.0.0
		 */
		protected static $_instance = null;


		/**
		 * Main SUPER_WooCommerce Instance
		 *
		 * Ensures only one instance of SUPER_WooCommerce is loaded or can be loaded.
		 *
		 * @static
		 * @see SUPER_WooCommerce()
		 * @return SUPER_WooCommerce - Main instance
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
		 * SUPER_WooCommerce Constructor.
		 *
		 *  @since      1.0.0
		 */
		public function __construct() {
			$this->init_hooks();
			do_action( 'super_woocommerce_loaded' );
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

			// Filters since 1.0.0
			add_filter( 'super_after_contact_entry_data_filter', array( $this, 'add_entry_order_link' ), 10, 2 );
			add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'add_contact_entry_link_to_order' ) );

			// Filters since 1.2.0
			add_filter( 'super_countries_list_filter', array( $this, 'return_wc_countries' ), 10, 2 );

			// Actions since 1.0.0
			add_action( 'super_front_end_posting_after_insert_post_action', array( $this, 'save_wc_order_post_session_data' ) );
			add_action( 'super_after_wp_insert_user_action', array( $this, 'save_wc_order_signup_session_data' ) );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ), 10, 1 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 1, 3 );
			add_action( 'super_after_saving_contact_entry_action', array( $this, 'set_contact_entry_order_id_session' ), 10, 3 );
			add_action( 'woocommerce_new_order_item', array( $this, 'add_order_item_meta' ), 10, 3 );
			add_filter( 'woocommerce_get_item_data', array( $this, 'display_product_meta_data_frontend' ), 10, 2 );

			if ( $this->is_request( 'frontend' ) ) {
				add_filter( 'woocommerce_checkout_get_value', array( $this, 'populate_checkout_field_values' ), 10, 2 );
				add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_override_checkout_fields' ) );
				add_action( 'woocommerce_cart_calculate_fees', array( $this, 'additional_shipping_costs' ), 5 );
			}

			if ( $this->is_request( 'admin' ) ) {
				add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
				add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'checkout_field_display_admin_order_meta' ), 10, 1 );
			}

			if ( $this->is_request( 'ajax' ) ) {
				add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ), 20 );
			}

			if ( $this->is_request( 'frontend' ) ) {
				// Remove product link from cart page
				add_filter( 'woocommerce_cart_item_permalink', array( $this, 'remove_product_link' ), 1000, 3 );
				// Remove "Edit" link from cart page
				add_filter( 'wc_nyp_isset_disable_edit_it_cart', array( $this, 'remove_edit_link' ), 1000, 3 );
				// Return 404 on single product page
				add_action( 'template_redirect', array( $this, 'return_404_single_product_page' ), 1 );
				// Exclude products from a particular category on the shop page
				add_action( 'pre_get_posts', array( $this, 'exclude_products_from_shop' ) );
				// Exclude products from WooCommerce shortcodes
				add_filter( 'woocommerce_shortcode_products_query', array( $this, 'exclude_products_from_wc_shortcodes' ), 10, 3 );
				// Remove add to cart form
				add_action( 'wp', array( $this, 'remove_single_product_gallery_title_rating_price_excerpt' ), 10 );
				// Replace add to cart with form
				add_action( 'woocommerce_single_product_summary', array( $this, 'replace_add_to_cart_btn_section_with_form' ), 20 );
				// Replace loop add to cart URL
				add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'replace_loop_add_to_cart_url' ), 1000, 2 );
				// Replace loop add to cart text
				add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'replace_loop_add_to_cart_text' ), 1000, 2 );
				add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'replace_loop_add_to_cart_text' ), 1000, 2 );
				// Remove ajax class from add to cart button
				add_filter( 'woocommerce_loop_add_to_cart_args', array( $this, 'remove_ajax_class_from_loop_add_to_cart_btn' ), 1000, 2 );
			}
		}
		// Remove product link from cart page
		public function remove_product_link( $url, $cart_item, $cart_item_key ) {
			$productId = $cart_item['product_id'];
			$result    = $this->hideProductsByIdOrSlug();
			if ( ! $result ) {
				return $url;
			}
			if ( ( count( $result['ids'] ) !== 0 && in_array( $productId, $result['ids'] ) ) || ( count( $result['slugs'] ) !== 0 && has_term( $result['slugs'], 'product_cat', $productId ) ) ) {
				return '';
			}
			return $url;
		}
		// Remove "Edit" link from cart page
		public function remove_edit_link( $remove, $cart_item, $cart_item_key ) {
			$productId = $cart_item['product_id'];
			// Based on hiding product from shop
			$result = $this->hideProductsByIdOrSlug();
			if ( ! $result ) {
				return $remove;
			}
			if ( $result ) {
				if ( ( count( $result['ids'] ) !== 0 && in_array( $productId, $result['ids'] ) ) || ( count( $result['slugs'] ) !== 0 && has_term( $result['slugs'], 'product_cat', $productId ) ) ) {
					return true;
				}
			}
			// Based on hiding default "Add to cart" section
			$result = $this->productMatchByIdOrSlug( $productId );
			if ( $result['match'] ) {
				return true;
			}
			return $remove;
		}

		// Return 404 on single product page
		public function return_404_single_product_page() {
			global $post;
			if ( ! $post ) {
				return true;
			}
			$productId = $post->ID;
			$result    = $this->hideProductsByIdOrSlug();
			if ( ! $result ) {
				return true;
			}
			if ( ( count( $result['ids'] ) !== 0 && in_array( $productId, $result['ids'] ) ) || ( count( $result['slugs'] ) !== 0 && has_term( $result['slugs'], 'product_cat', $productId ) ) ) {
				include get_query_template( '404' );
				exit;
			}
		}
		// Exclude products from a particular category on the shop page
		public function exclude_products_from_shop( $q ) {
			$result = $this->hideProductsByIdOrSlug();
			if ( ! $result ) {
				return true;
			}
			if ( count( $result['ids'] ) !== 0 ) {
				$q->set( 'post__not_in', $result['ids'] ); // Replace 70 and 53 with your products IDs. Separate each ID with a comma.
			}
			if ( count( $result['slugs'] ) !== 0 ) {
				$tax_query   = (array) $q->get( 'tax_query' );
				$tax_query[] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $result['slugs'],
					'operator' => 'NOT IN',
				);
				$q->set( 'tax_query', $tax_query );
			}
		}
		// Exclude products from WooCommerce shortcodes
		public function exclude_products_from_wc_shortcodes( $query_args, $attr, $type ) {
			$result = $this->hideProductsByIdOrSlug();
			if ( ! $result ) {
				return $query_args;
			}
			if ( count( $result['ids'] ) !== 0 ) {
				$query_args['post__not_in'] = $result['ids'];
			}
			if ( count( $result['slugs'] ) !== 0 ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $result['slugs'],
					'operator' => 'NOT IN',
				);
			}
			return $query_args;
		}
		// Remove add to cart form
		public function remove_single_product_gallery_title_rating_price_excerpt() {
			global $post;
			if ( ! $post ) {
				return true;
			}
			$productId = $post->ID;
			$result    = $this->productMatchByIdOrSlug( $productId );
			if ( $result['match'] ) {
				// Optionally remove gallery, title, rating, price, excerpt?
				if ( $result['remove_gallery'] === 'true' ) {
					remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
					remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
					?>
					<style>
					.single-product div.product .summary,
					.single-product div.product .single-product-summary {
						width: 100%!important;
					}
					.single-product div.product .single-product-main-image {
						display: none;
					}

					</style>
					<?php
				}
				if ( $result['remove_title'] === 'true' ) {
					remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
				}
				if ( $result['remove_rating'] === 'true' ) {
					remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
				}
				if ( $result['remove_price'] === 'true' ) {
					remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
				}
				if ( $result['remove_excerpt'] === 'true' ) {
					remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
				}
			}
		}
		// Replace add to cart with form
		public function replace_add_to_cart_btn_section_with_form() {
			global $product;
			if ( ! $product ) {
				return true;
			}
			$productId = $product->get_id();
			$result    = $this->productMatchByIdOrSlug( $productId );
			if ( $result['match'] ) {
				echo do_shortcode( '[super_form id="' . $result['form_id'] . '"]' );
				// Remove single product page add to cart form
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
				remove_action( 'woocommerce_simple_add_to_cart', 'woocommerce_simple_add_to_cart', 30 );
				remove_action( 'woocommerce_grouped_add_to_cart', 'woocommerce_grouped_add_to_cart', 30 );
				remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
				remove_action( 'woocommerce_external_add_to_cart', 'woocommerce_external_add_to_cart', 30 );
				remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
				remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
			}
		}
		// Replace loop add to cart URL
		public function replace_loop_add_to_cart_url( $url, $product ) {
			$productId = $product->get_id();
			$result    = $this->productMatchByIdOrSlug( $productId );
			if ( $result['match'] ) {
				return $product->get_permalink();
			}
			return $url;
		}
		// Replace loop add to cart text
		public function replace_loop_add_to_cart_text( $text, $product ) {
			$productId = $product->get_id();
			$result    = $this->productMatchByIdOrSlug( $productId );
			if ( $result['match'] ) {
				return __( 'View product', 'super-forms' );
			}
			return $text;
		}
		// Remove ajax class from add to cart button
		public function remove_ajax_class_from_loop_add_to_cart_btn( $args, $product ) {
			$productId = $product->get_id();
			$result    = $this->productMatchByIdOrSlug( $productId );
			if ( $result['match'] ) {
				$args['class'] = str_replace( 'ajax_add_to_cart', '', $args['class'] );
			}
			return $args;
		}
		public function productMatchByIdOrSlug( $productId ) {
			$settings = $this->globalWooCommerceSettings();
			$key      = array_search( $productId, array_column( $settings['ids'], 'product_id' ) );
			$match    = false;
			$formId   = 0;
			if ( $key !== false ) {
				$formId = $settings['ids'][ $key ]['form_id'];
				$match  = true;
			} else {
				// Loop over possible slugs
				foreach ( $settings['slugs'] as $k => $v ) {
					if ( has_term( $v['slug'], 'product_cat', $productId ) ) {
						$formId = $v['form_id'];
						$match  = true;
						break;
					}
				}
			}
			return array(
				'match'          => $match,
				'form_id'        => $formId,
				'remove_gallery' => $settings['remove_gallery'],
				'remove_title'   => $settings['remove_title'],
				'remove_rating'  => $settings['remove_rating'],
				'remove_price'   => $settings['remove_price'],
				'remove_excerpt' => $settings['remove_excerpt'],
			);
		}
		public function globalWooCommerceSettings() {
			$globalSettings   = SUPER_Common::get_global_settings();
			$formProductSlugs = '';
			if ( empty( $globalSettings['wc_remove_single_product_page_gallery'] ) ) {
				$globalSettings['wc_remove_single_product_page_gallery'] = 'false';
			}
			if ( empty( $globalSettings['wc_remove_single_product_page_title'] ) ) {
				$globalSettings['wc_remove_single_product_page_title'] = 'false';
			}
			if ( empty( $globalSettings['wc_remove_single_product_page_rating'] ) ) {
				$globalSettings['wc_remove_single_product_page_rating'] = 'false';
			}
			if ( empty( $globalSettings['wc_remove_single_product_page_price'] ) ) {
				$globalSettings['wc_remove_single_product_page_price'] = 'false';
			}
			if ( empty( $globalSettings['wc_remove_single_product_page_excerpt'] ) ) {
				$globalSettings['wc_remove_single_product_page_excerpt'] = 'false';
			}
			if ( ! empty( $globalSettings['wc_replace_add_to_cart_btn'] ) ) {
				$formProductSlugs = $globalSettings['wc_replace_add_to_cart_btn'];
				$formProductSlugs = str_replace( ' ', '', $formProductSlugs );
				$formProductSlugs = explode( "\n", $formProductSlugs );
				// Cleanup
				foreach ( $formProductSlugs as $k => $v ) {
					if ( empty( $v ) ) {
						unset( $formProductSlugs[ $k ] );
					}
				}
				// Remove none ID values:
				$ids = array();
				foreach ( $formProductSlugs as $k => $v ) {
					$v = explode( '|', $v );
					if ( ! empty( $v[1] ) && absint( $v[1] ) !== 0 ) {
						$ids[] = array(
							'form_id'    => $v[0],
							'product_id' => $v[1],
						);
					}
				}
				// Remove none Slug values:
				$slugs = array();
				foreach ( $formProductSlugs as $k => $v ) {
					$v = explode( '|', $v );
					if ( ! empty( $v[1] ) && absint( $v[1] ) === 0 ) {
						$slugs[] = array(
							'form_id' => $v[0],
							'slug'    => $v[1],
						);
					}
				}
				return array(
					'ids'            => $ids,
					'slugs'          => $slugs,
					'remove_gallery' => $globalSettings['wc_remove_single_product_page_gallery'],
					'remove_title'   => $globalSettings['wc_remove_single_product_page_title'],
					'remove_rating'  => $globalSettings['wc_remove_single_product_page_rating'],
					'remove_price'   => $globalSettings['wc_remove_single_product_page_price'],
					'remove_excerpt' => $globalSettings['wc_remove_single_product_page_excerpt'],
				);
			} else {
				return array(
					'ids'            => array(),
					'slugs'          => array(),
					'remove_gallery' => $globalSettings['wc_remove_single_product_page_gallery'],
					'remove_title'   => $globalSettings['wc_remove_single_product_page_title'],
					'remove_rating'  => $globalSettings['wc_remove_single_product_page_rating'],
					'remove_price'   => $globalSettings['wc_remove_single_product_page_price'],
					'remove_excerpt' => $globalSettings['wc_remove_single_product_page_excerpt'],
				);
			}
		}
		public function hideProductsByIdOrSlug() {
			if ( ! class_exists( 'SUPER_Common' ) ) {
				return false;
			}
			$globalSettings = SUPER_Common::get_global_settings();
			$idSlugs        = '';
			if ( ! empty( $globalSettings['wc_hide_products_by_id_or_slug'] ) ) {
				$idSlugs = $globalSettings['wc_hide_products_by_id_or_slug'];
				$idSlugs = str_replace( ' ', '', $idSlugs );
				$idSlugs = explode( "\n", $idSlugs );
				// Cleanup
				foreach ( $idSlugs as $k => $v ) {
					if ( empty( $v ) ) {
						unset( $idSlugs[ $k ] );
					}
				}
				// Remove none ID values:
				$ids = array();
				foreach ( $idSlugs as $k => $v ) {
					if ( absint( $v ) === 0 ) {
						continue;
					}
					$ids[] = $v;
				}
				// Remove none Slug values:
				$slugs = array();
				foreach ( $idSlugs as $k => $v ) {
					if ( absint( $v ) !== 0 ) {
						continue;
					}
					$slugs[] = $v;
				}
				return array(
					'ids'   => $ids,
					'slugs' => $slugs,
				);
			}
			return false;
		}


		/**
		 * This function takes the last comma or dot (if any) to make a clean float, ignoring thousand separator, currency or any other letter :
		 */
		public static function tofloat( $num ) {
			$dotPos   = strrpos( $num, '.' );
			$commaPos = strrpos( $num, ',' );
			$sep      = ( ( $dotPos > $commaPos ) && $dotPos ) ? $dotPos :
				( ( ( $commaPos > $dotPos ) && $commaPos ) ? $commaPos : false );

			if ( ! $sep ) {
				return floatval( preg_replace( '/[^0-9]/', '', $num ) );
			}

			return floatval(
				preg_replace( '/[^0-9]/', '', substr( $num, 0, $sep ) ) . '.' .
				preg_replace( '/[^0-9]/', '', substr( $num, $sep + 1, strlen( $num ) ) )
			);
		}


		/**
		 * Display custom product meta data on Cart and Checkout pages
		 *
		 *  @since      1.3.4
		 */
		public function display_product_meta_data_frontend( $item_data, $cart_item ) {
			if ( ! isset( $cart_item['super_data'] ) ) {
				return $item_data;
			}
			if ( ! is_array( $cart_item['super_data'] ) ) {
				return $item_data;
			}
			foreach ( $cart_item['super_data'] as $k => $v ) {
				$item_data[] = array(
					'name'  => $v['label'],
					'value' => $v['value'],
				);
			}
			return $item_data;
		}


		/**
		 * Add custom product meta data
		 *
		 *  @since      1.3.4
		 */
		public function add_order_item_meta( $item_id, $values, $cart_item_key ) {
			global $woocommerce;
			if ( $woocommerce->cart != null ) {
				foreach ( $woocommerce->cart->get_cart() as $k => $v ) {
					if ( ! isset( $values->legacy_cart_item_key ) ) {
						continue;
					}
					if ( $k == $values->legacy_cart_item_key ) {
						if ( ! isset( $v['super_data'] ) ) {
							continue;
						}
						if ( ! is_array( $v['super_data'] ) ) {
							continue;
						}
						foreach ( $v['super_data'] as $k => $v ) {
							wc_add_order_item_meta( $item_id, $k, $v );
						}
					}
				}
			}
		}


		/**
		 * Add additional checkout fields
		 *
		 * @since       1.2.2
		 */
		function custom_override_checkout_fields( $fields ) {
			$custom_fields = SUPER_Common::getClientData( 'wc_custom_fields' );
			if ( is_array( $custom_fields ) ) {
				foreach ( $custom_fields as $k => $v ) {
					$fields[ $v['section'] ][ $v['name'] ] = array(
						'label'       => $v['label'],
						'placeholder' => $v['placeholder'],
						'type'        => $v['type'],
						'required'    => ( $v['required'] == 'true' ? true : false ),
						'clear'       => ( $v['clear'] == 'true' ? true : false ),
						'class'       => array( $v['class'] ),
						'label_class' => array( $v['label_class'] ),
					);
					if ( $v['type'] == 'select' ) {
						$array   = array();
						$options = explode( ';', $v['options'] );
						foreach ( $options as $ok => $ov ) {
							$values          = explode( ',', $ov );
							$value           = $values[0];
							$label           = $values[1];
							$array[ $value ] = $label;
						}
						$fields[ $v['section'] ][ $v['name'] ]['options'] = $array;
					}
				}
			}
			return $fields;
		}


		/**
		 * Add additional shipping costs
		 *
		 * @since       1.2.2
		 */
		function checkout_field_display_admin_order_meta( $order ) {
			$custom_fields = get_post_meta( $order->get_id(), '_super_wc_custom_fields', true );
			if ( is_array( $custom_fields ) ) {
				foreach ( $custom_fields as $k => $v ) {
					echo '<p><strong>' . $v['label'] . ':</strong> ' . get_post_meta( $order->get_id(), $v['name'], true ) . '</p>';
				}
			}
		}


		/**
		 * Return WC countries list for billing_country and shipping_country only
		 *
		 *  @since      1.2.0
		 */
		public function return_wc_countries( $countries, $data ) {
			$s   = $data['settings'];
			$wcs = null;
			if ( isset( $s['_woocommerce'] ) ) {
				$wcs = $s['_woocommerce'];
			}
			if ( ( class_exists( 'WC_Countries' ) ) && ( isset( $wcs ) && isset( $wcs['checkout'] ) ) && ( $wcs['checkout'] == 'true' ) && ( ( $data['name'] == 'billing_country' ) || ( $data['name'] == 'shipping_country' ) ) ) {
				$countries_obj = new WC_Countries();
				$countries     = $countries_obj->__get( 'countries' );
				return $countries;
			}
			return $countries;
		}

		/**
		 * Auto popuplate field with form data value
		 *
		 * @since       1.2.0
		 */
		public static function populate_checkout_field_values( $value, $input ) {
			global $woocommerce;
			$data          = $woocommerce->session->get( '_super_form_data', array() );
			$custom_fields = SUPER_Common::getClientData( 'wc_custom_fields' );
			if ( is_array( $custom_fields ) ) {
				foreach ( $custom_fields as $k => $v ) {
					if ( $v['name'] === $input ) {
						return SUPER_Common::email_tags( $v['value'], $data );
					}
				}
			}
			// Billing & Shipping
			if ( ( isset( $data[ $input ] ) ) && ( isset( $data[ $input ]['value'] ) ) ) {
				return $data[ $input ]['value'];
			}
			// If form contained no field name that is used on checkout page see if there is a custom mapped one from the settings
			$fields = $woocommerce->session->get( '_super_form_woocommerce_populate_checkout_fields', array() );
			if ( isset( $fields[ $input ] ) ) {
				return SUPER_Common::email_tags( $fields[ $input ], $data );
			}
			return $value;
		}


		/**
		 * Add the WC Order link to the entry info/data page
		 *
		 * @since       1.0.0
		 */
		public static function add_entry_order_link( $result, $data ) {
			$order_id = get_post_meta( $data['entry_id'], '_super_contact_entry_wc_order_id', true );
			if ( ! empty( $order_id ) ) {
				$order_id = absint( $order_id );
				if ( $order_id != 0 ) {
					$result .= '<tr><th align="right">' . esc_html__( 'WooCommerce Order', 'super-forms' ) . ':</th><td><span class="super-contact-entry-data-value">';
					$result .= '<a href="' . esc_url( get_admin_url() . 'post.php?post=' . $order_id . '&action=edit' ) . '">#' . $order_id . ' - ' . get_the_title( $order_id ) . '</a>';
					$result .= '</span></td></tr>';
				}
			}
			return $result;
		}


		/**
		 * Add the Contact Entry link to the order
		 *
		 * @since       1.9.4
		 */
		public static function add_contact_entry_link_to_order( $order ) {
			$entry_id = get_post_meta( $order->get_id(), '_super_entry_id', true );
			if ( ! empty( $entry_id ) ) {
				$entry_id = absint( $entry_id );
				if ( $entry_id != 0 ) {
					echo '<p class="form-field form-field-wide wc-super-contact-entry">';
					echo '<span>';
					echo esc_html__( 'Contact Entry', 'super-forms' ) . ': ';
					echo '<a target="_blank" href="' . esc_url( get_admin_url() . '?page=super_contact_entry&id=' . $entry_id ) . '">' . get_the_title( $entry_id ) . '</a>';
					echo '</span>';
					echo '</p>';
				}
			}
		}


		/**
		 * Save contact entry ID to session
		 *
		 * @since       1.0.0
		 */
		function set_contact_entry_order_id_session( $data ) {
			if ( class_exists( 'WooCommerce' ) ) {
				$post_type = get_post_type( $data['entry_id'] );

				// Check if post_type is super_contact_entry
				global $woocommerce;
				if ( $post_type == 'super_contact_entry' ) {
					$woocommerce->session->set( '_super_entry_id', array( 'entry_id' => $data['entry_id'] ) );
				} else {
					$woocommerce->session->set( '_super_entry_id', array() );
				}
			}
		}


		/**
		 * Add additional shipping costs
		 *
		 * @since       1.0.0
		 */
		function additional_shipping_costs() {
			global $woocommerce;
			$fee = SUPER_Common::getClientData( 'wc_fee' );
			if ( $fee != false ) {
				if ( is_array( $fee ) ) {
					foreach ( $fee as $k => $v ) {
						if ( $v['amount'] > 0 ) {
							$woocommerce->cart->add_fee( $v['name'], $v['amount'], $v['taxable'], $v['tax_class'] );
						} else {
							$woocommerce->cart->add_fee( $v['name'], $v['amount'], $v['taxable'], $v['tax_class'] );
						}
					}
				}
			}
		}


		/**
		 * If Front-end posting is being used retrieve the inserted Post ID and save it to the WC Order
		 *
		 *  @since      1.0.0
		 */
		function save_wc_order_post_session_data( $data ) {
			global $woocommerce;
			if ( $woocommerce ) {
				// Check if Front-end Posting is activated
				if ( class_exists( 'SUPER_Frontend_Posting' ) ) {
					$post_id  = absint( $data['post_id'] );
					$settings = $data['atts']['settings'];
					if ( ( isset( $settings['frontend_posting_action'] ) ) && ( $settings['frontend_posting_action'] == 'create_post' ) ) {
						$woocommerce->session->set(
							'_super_wc_post',
							array(
								'post_id' => $post_id,
								'status'  => $settings['woocommerce_post_status'],
							)
						);
					} else {
						$woocommerce->session->set( '_super_wc_post', array() );
					}
				} else {
					$woocommerce->session->set( '_super_wc_post', array() );
				}
			}
		}


		/**
		 * If Register & Login is being used retrieve the created User ID and save it to the WC Order
		 *
		 *  @since      1.0.0
		 */
		function save_wc_order_signup_session_data( $data ) {
			global $woocommerce;
			if ( $woocommerce ) {
				// Check if Register & Login is used
				if ( class_exists( 'SUPER_Register_Login' ) ) {
					$user_id  = absint( $data['user_id'] );
					$settings = $data['atts']['settings'];
					if ( ! empty( $settings['register_login_action'] ) && $settings['register_login_action'] == 'register' && $user_id != 0 ) {
						$user_role = '';
						if ( ! empty( $settings['woocommerce_completed_user_role'] ) ) {
							$user_role = $settings['woocommerce_completed_user_role'];
						}
						$woocommerce->session->set(
							'_super_wc_signup',
							array(
								'user_id' => $user_id,
								'status'  => $settings['_woocommerce']['login_status'],
								'role'    => $user_role,
							)
						);
					} else {
						$woocommerce->session->set( '_super_wc_signup', array() );
					}
				} else {
					$woocommerce->session->set( '_super_wc_signup', array() );
				}
			}
		}


		/**
		 * Set the post ID and status to the order post_meta so we can update it after payment completed
		 *
		 * @since       1.0.0
		 */
		public static function update_order_meta( $order_id ) {
			// @since 1.2.2 - save the custom fields to the order, so we can retrieve it in back-end for later use
			$custom_fields = SUPER_Common::getClientData( 'wc_custom_fields' );
			if ( ! empty( $custom_fields ) ) {
				update_post_meta( $order_id, '_super_wc_custom_fields', $custom_fields );
				if ( is_array( $custom_fields ) ) {
					foreach ( $custom_fields as $k => $v ) {
						if ( ! empty( $_POST[ $v['name'] ] ) ) {
							update_post_meta( $order_id, $v['name'], sanitize_text_field( $_POST[ $v['name'] ] ) );
						}
					}
				}
			}

			// @since 1.2.2 - save entry data to the order
			$data = SUPER_Common::getClientData( 'wc_entry_data' );
			update_post_meta( $order_id, '_super_wc_entry_data', $data );

			global $woocommerce;
			if ( $woocommerce ) {
				$_super_wc_post = $woocommerce->session->get( '_super_wc_post', array() );
				update_post_meta( $order_id, '_super_wc_post', $_super_wc_post );

				$_super_wc_signup = $woocommerce->session->get( '_super_wc_signup', array() );
				update_post_meta( $order_id, '_super_wc_signup', $_super_wc_signup );

				$_super_entry_id = $woocommerce->session->get( '_super_entry_id', array() );
				if ( isset( $_super_entry_id['entry_id'] ) ) {
					update_post_meta( $order_id, '_super_entry_id', $_super_entry_id['entry_id'] );
					update_post_meta( $_super_entry_id['entry_id'], '_super_contact_entry_wc_order_id', $order_id );
					$woocommerce->session->set( '_super_entry_id', array() );
				}
			}
		}


		/**
		 * After order status changed
		 *
		 * @since       1.3.8
		 */
		public function order_status_changed( $order_id, $old_status, $new_status ) {
			if ( $new_status == 'completed' ) {
				$contact_entry_id = get_post_meta( $order_id, '_super_entry_id', true );
				$_super_wc_post   = get_post_meta( $order_id, '_super_wc_post', true );
				if ( ! empty( $_super_wc_post ) ) { // @since 1.0.2 - check if not empty
					$my_post = array(
						'ID'          => $_super_wc_post['post_id'],
						'post_status' => $_super_wc_post['status'],
					);
					wp_update_post( $my_post );
				}

				// Update user login status and role
				$_super_wc_signup = get_post_meta( $order_id, '_super_wc_signup', true );
				if ( ! empty( $_super_wc_signup ) ) { // @since 1.0.2 - check if not empty
					// Update login status
					if ( ! empty( $_super_wc_signup['user_id'] ) ) {
						$matched_login_status = array();
						foreach ( $settings['_woocommerce']['login_status'] as $k => $v ) {
							$matched_login_status[ $v['order'] ] = $v['login_status'];
						}
						update_user_meta( $_super_wc_signup['user_id'], 'super_user_login_status', $matched_login_status['completed'] );
						// Update user role
						if ( ! empty( $_super_wc_signup['role'] ) ) {
							$userdata = array(
								'ID'   => $_super_wc_signup['user_id'],
								'role' => $_super_wc_signup['role'],
							);
							$result   = wp_update_user( $userdata );
							if ( is_wp_error( $result ) ) {
								throw new Exception( $result->get_error_message() );
							}
						}
					}
				}

				// @since 1.3.8 - Check if sending email is enabled
				$data = get_post_meta( $order_id, '_super_wc_entry_data', true );
				if ( empty( $data ) ) {
					return false;
				}
				$form_id = absint( $data['hidden_form_id']['value'] );
				if ( method_exists( 'SUPER_Common', 'get_form_settings' ) ) {
					$form_settings = SUPER_Common::get_form_settings( $form_id );
				} else {
					$form_settings = get_post_meta( absint( $form_id ), '_super_form_settings', true );
				}
				// Update contact entry status after succesfull payment
				foreach ( $form_settings['_woocommerce']['entry_status'] as $k => $v ) {
					if ( trim( $v['order'] ) === $new_status ) {
						$new_entry_status = SUPER_Common::email_tags( $v['entry'], $data, $settings );
						update_post_meta( $contact_entry_id, '_super_contact_entry_status', $new_entry_status );
						break;
					}
				}

				// var_dump('@TODO WC Completed E-mail trigger');
				// tmp if( !empty($form_settings['woocommerce_completed_email']) ) {
				// tmp     $global_settings = SUPER_Common::get_global_settings();
				// tmp     if( $form_settings!=false ) {
				// tmp         // @since 4.0.0 - when adding new field make sure we merge settings from global settings with current form settings
				// tmp         foreach( $form_settings as $k => $v ) {
				// tmp             if( isset( $global_settings[$k] ) ) {
				// tmp                 if( $global_settings[$k] == $v ) {
				// tmp                     unset( $form_settings[$k] );
				// tmp                 }
				// tmp             }
				// tmp         }
				// tmp     }else{
				// tmp         $form_settings = array();
				// tmp     }
				// tmp     $settings = array_merge($global_settings, $form_settings);

				// tmp     if( !empty( $settings['woocommerce_completed_header_additional'] ) ) {
				// tmp         $header_additional = '';
				// tmp         if( !empty( $settings['woocommerce_completed_header_additional'] ) ) {
				// tmp             $headers = explode( "\n", $settings['woocommerce_completed_header_additional'] );
				// tmp             foreach( $headers as $k => $v ) {
				// tmp                 // @since 1.2.6.92
				// tmp                 $v = SUPER_Common::email_tags( $v, $data, $settings );
				// tmp                 $header_additional .= $v . "\r\n";
				// tmp             }
				// tmp         }
				// tmp         $settings['woocommerce_completed_header_additional'] = $header_additional;
				// tmp     }
				// tmp
				// tmp     $email_loop = '';
				// tmp     $attachments = array();
				// tmp     $confirm_string_attachments = array();
				// tmp     if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
				// tmp         foreach( $data as $k => $v ) {
				// tmp             // Skip dynamic data
				// tmp             if($k=='_super_dynamic_data') continue;
				// tmp
				// tmp             $row = $settings['woocommerce_completed_email_loop'];
				// tmp             // Exclude from emails
				// tmp             // 0 = Do not exclude from e-mails
				// tmp             // 2 = Exclude from all email
				// tmp             // 3 = Exclude from admin email
				// tmp             if( !isset( $v['exclude'] ) ) {
				// tmp                 $v['exclude'] = 0;
				// tmp             }
				// tmp             if( $v['exclude']==2 ) {
				// tmp                 // Exclude from all emails
				// tmp                 continue;
				// tmp             }

				// tmp             /**
				// tmp              *  Filter to control the email loop when something special needs to happen
				// tmp              *  e.g. Signature element needs to display image instead of the base64 code that the value contains
				// tmp              *
				// tmp              *  @param  string  $row
				// tmp              *  @param  array   $data
				// tmp              *
				// tmp              *  @since      1.0.9
				// tmp             */
				// tmp             $result = apply_filters( 'super_before_email_loop_data_filter', $row, array( 'type'=>'confirm', 'v'=>$v, 'confirm_string_attachments'=>$confirm_string_attachments ) );
				// tmp             $continue = false;
				// tmp             if( isset( $result['status'] ) ) {
				// tmp                 if( $result['status']=='continue' ) {
				// tmp                     if( isset( $result['confirm_string_attachments'] ) ) {
				// tmp                         $confirm_string_attachments = $result['confirm_string_attachments'];
				// tmp                     }
				// tmp                     $email_loop .= $result['row'];
				// tmp                     $continue = true;
				// tmp                 }
				// tmp             }
				// tmp             if($continue) continue;

				// tmp             if( isset($v['type']) && $v['type']=='files' ) {
				// tmp                 $files_value = '';
				// tmp                 if( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) {
				// tmp                     $v['value'] = '';
				// tmp                     if( !empty( $v['label'] ) ) {
				// tmp                         // Replace %d with empty string if exists
				// tmp                         $v['label'] = str_replace('%d', '', $v['label']);
				// tmp                         $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
				// tmp                     }else{
				// tmp                         $row = str_replace( '{loop_label}', '', $row );
				// tmp                     }
				// tmp                     $files_value .= esc_html__( 'User did not upload any files', 'super-forms' );
				// tmp                 }else{
				// tmp                     $v['value'] = '-';
				// tmp                     foreach( $v['files'] as $key => $value ) {
				// tmp                         // Check if user explicitely wants to remove files from {loop_fields} in emails
				// tmp                         if(!empty($settings['file_upload_remove_from_email_loop'])) {
				// tmp                             // Remove this row completely
				// tmp                             $row = '';
				// tmp                         }else{
				// tmp                             if( $key==0 ) {
				// tmp                                 if( !empty( $v['label'] ) ) {
				// tmp                                     $v['label'] = str_replace('%d', '', $v['label']);
				// tmp                                     $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
				// tmp                                 }else{
				// tmp                                     $row = str_replace( '{loop_label}', '', $row );
				// tmp                                 }
				// tmp                             }
				// tmp                             // In case the file was deleted we do not want to add a hyperlink that links to the file
				// tmp                             // In case the user explicitely choose to remove the hyperlink
				// tmp                             if( !empty($settings['file_upload_submission_delete']) ||
				// tmp                                 !empty($settings['file_upload_remove_hyperlink_in_emails']) ) {
				// tmp                                 $files_value .= $value['value'] . '<br /><br />';
				// tmp                             }else{
				// tmp                                 $files_value .= '<a href="' . esc_url($value['url']) . '" target="_blank">' . esc_html($value['value']) . '</a><br /><br />';
				// tmp                             }
				// tmp                         }
				// tmp                         // Check if we should exclude the file from emails
				// tmp                         // 0 = Do not exclude from e-mails
				// tmp                         // 2 = Exclude from all email
				// tmp                         // 3 = Exclude from admin email
				// tmp                         if( $v['exclude']!=2 ) {
				// tmp                             // Get either URL or Secure file path
				// tmp                             if(!empty($value['attachment'])){
				// tmp                                 $fileValue = $value['url'];
				// tmp                             }else{
				// tmp                                 // See if this was a secure file upload
				// tmp                                 if(!empty($value['path'])) $fileValue = wp_normalize_path(trailingslashit($value['path']) . $value['value']);
				// tmp                             }
				// tmp                             // 1 = Exclude from confirmation email
				// tmp                             if( $v['exclude']==1 ) {
				// tmp                                 $attachments[$value['value']] = $fileValue;
				// tmp                             }else{
				// tmp                                 // 3 = Exclude from admin email
				// tmp                                 if( $v['exclude']==3 ) {
				// tmp                                 }else{
				// tmp                                     // Do not exclude
				// tmp                                     $attachments[$value['value']] = $fileValue;
				// tmp                                 }
				// tmp                             }
				// tmp                         }
				// tmp                     }
				// tmp                 }
				// tmp                 $row = str_replace( '{loop_value}', $files_value, $row );
				// tmp             }else{
				// tmp                 if( isset($v['type']) && (($v['type']=='form_id') || ($v['type']=='entry_id')) ) {
				// tmp                     $row = '';
				// tmp                 }else{

				// tmp                     if( !empty( $v['label'] ) ) {
				// tmp                         $v['label'] = str_replace('%d', '', $v['label']);
				// tmp                         $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
				// tmp                     }else{
				// tmp                         $row = str_replace( '{loop_label}', '', $row );
				// tmp                     }
				// tmp                     // @since 1.2.7
				// tmp                     if( isset( $v['admin_value'] ) ) {
				// tmp                         // @since 3.9.0 - replace comma's with HTML
				// tmp                         if( !empty($v['replace_commas']) ) $v['admin_value'] = str_replace( ',', $v['replace_commas'], $v['admin_value'] );
				// tmp
				// tmp                         $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['admin_value'] ), $row );
				// tmp                     }
				// tmp                     if( isset( $v['value'] ) ) {
				// tmp                         // @since 3.9.0 - replace comma's with HTML
				// tmp                         if( !empty($v['replace_commas']) ) $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
				// tmp
				// tmp                         $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['value'] ), $row );
				// tmp                     }

				// tmp                 }
				// tmp             }

				// tmp             // @since 4.5.0 - check if value is empty, and if we need to exclude it from the email
				// tmp             // 0 = Do not exclude from e-mails
				// tmp             // 2 = Exclude from all email
				// tmp             // 3 = Exclude from admin email
				// tmp             if( $v['exclude']==3 || ($settings['woocommerce_completed_exclude_empty']=='true' && (empty($v['value']) || $v['value']=='0') )) {
				// tmp                 // Exclude from admin email loop
				// tmp             }else{
				// tmp                 $email_loop .= $row;
				// tmp             }
				// tmp         }
				// tmp     }
				// tmp
				// tmp     if(!empty($settings['woocommerce_completed_body_open'])) $settings['woocommerce_completed_body_open'] = $settings['woocommerce_completed_body_open'] . '<br /><br />';
				// tmp     if(!empty($settings['woocommerce_completed_body'])) $settings['woocommerce_completed_body'] = $settings['woocommerce_completed_body'] . '<br /><br />';
				// tmp     $email_body = $settings['woocommerce_completed_body_open'] . $settings['woocommerce_completed_body'] . $settings['woocommerce_completed_body_close'];
				// tmp     $email_body = str_replace( '{loop_fields}', $email_loop, $email_body );
				// tmp     $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );

				// tmp     // @since 3.1.0 - optionally automatically add line breaks
				// tmp     if(!isset($settings['woocommerce_completed_body_nl2br'])) $settings['woocommerce_completed_body_nl2br'] = 'true';
				// tmp     if($settings['woocommerce_completed_body_nl2br']=='true') $email_body = nl2br( $email_body );
				// tmp
				// tmp     // @since 4.9.5 - RTL email setting
				// tmp     if(!isset($settings['woocommerce_completed_rtl'])) $settings['woocommerce_completed_rtl'] = '';
				// tmp     if($settings['woocommerce_completed_rtl']=='true') $email_body =  '<div dir="rtl" style="text-align:right;">' . $email_body . '</div>';

				// tmp     $email_body = do_shortcode($email_body);
				// tmp     $email_body = apply_filters( 'super_before_sending_email_body_filter', $email_body, array( 'settings'=>$settings, 'email_loop'=>$email_loop, 'data'=>$data ) );

				// tmp     if( !isset( $settings['woocommerce_completed_from_type'] ) ) $settings['woocommerce_completed_from_type'] = 'default';
				// tmp     if( $settings['woocommerce_completed_from_type']=='default' ) {
				// tmp         $settings['woocommerce_completed_from_name'] = get_option( 'blogname' );
				// tmp         $settings['woocommerce_completed_from'] = get_option( 'admin_email' );
				// tmp     }
				// tmp     if( !isset( $settings['woocommerce_completed_from_name'] ) ) $settings['woocommerce_completed_from_name'] = get_option( 'blogname' );
				// tmp     if( !isset( $settings['woocommerce_completed_from'] ) ) $settings['woocommerce_completed_from'] = get_option( 'admin_email' );

				// tmp     $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_to'], $data, $settings ) );
				// tmp     $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_from'], $data, $settings ) );
				// tmp     $from_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_from_name'], $data, $settings ) );
				// tmp
				// tmp     $cc = '';
				// tmp     if( !empty($settings['woocommerce_completed_header_cc']) ) {
				// tmp         $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_header_cc'], $data, $settings ) );
				// tmp     }
				// tmp     $bcc = '';
				// tmp     if( !empty($settings['woocommerce_completed_header_bcc']) ) {
				// tmp         $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_header_bcc'], $data, $settings ) );
				// tmp     }
				// tmp
				// tmp     $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['woocommerce_completed_subject'], $data, $settings ) );

				// tmp     // @since 2.8.0 - custom reply to headers
				// tmp     if( !isset($settings['woocommerce_completed_header_reply_enabled']) ) $settings['woocommerce_completed_header_reply_enabled'] = false;
				// tmp     $reply = '';
				// tmp     $reply_name = '';
				// tmp     if( $settings['woocommerce_completed_header_reply_enabled']==false ) {
				// tmp         $custom_reply = false;
				// tmp     }else{
				// tmp         $custom_reply = true;
				// tmp         if( !isset($settings['woocommerce_completed_header_reply']) ) $settings['woocommerce_completed_header_reply'] = '';
				// tmp         if( !isset($settings['woocommerce_completed_header_reply_name']) ) $settings['woocommerce_completed_header_reply_name'] = '';
				// tmp         $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_header_reply'], $data, $settings ) );
				// tmp         $reply_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_header_reply_name'], $data, $settings ) );
				// tmp     }

				// tmp     // @since 3.3.2 - default admin email attachments
				// tmp     if( !empty($settings['woocommerce_completed_attachments']) ) {
				// tmp         $email_attachments = explode( ',', $settings['woocommerce_completed_attachments'] );
				// tmp         foreach($email_attachments as $k => $v){
				// tmp             $file = get_attached_file($v);
				// tmp             if( $file ) {
				// tmp                 $url = wp_get_attachment_url($v);
				// tmp                 $filename = basename ( $file );
				// tmp                 $attachments[$filename] = $url;
				// tmp             }
				// tmp         }
				// tmp     }

				// tmp     // @since 2.0
				// tmp     $attachments = apply_filters( 'super_before_sending_email_attachments_filter', $attachments, array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$email_body ) );

				// tmp     // Send the email
				// tmp     $mail = SUPER_Common::email( array( 'to'=>$to, 'from'=>$from, 'from_name'=>$from_name, 'custom_reply'=>$custom_reply, 'reply'=>$reply, 'reply_name'=>$reply_name, 'cc'=>$cc, 'bcc'=>$bcc, 'subject'=>$subject, 'body'=>$email_body, 'settings'=>$settings, 'attachments'=>$attachments, 'string_attachments'=>$confirm_string_attachments ));

				// tmp     // Return error message
				// tmp     if( !empty( $mail->ErrorInfo ) ) {
				// tmp         $msg = esc_html__( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
				// tmp         SUPER_Common::output_message( array(
				// tmp             'msg'=>$msg,
				// tmp             'form_id'=> absint($form_id)
				// tmp         ));
				// tmp     }
				// tmp }
			}
		}


		/**
		 * Loop through {tags} if dynamic column is used
		 *
		 *  @since      1.3.4
		 */
		public static function new_wc_checkout_products( $products_tags, $i, $looped, $product, $id, $quantity, $variation, $price ) {
			if ( ! in_array( $i, $looped ) ) {
				$new_line = '';

				// Get the product ID tag
				if ( $product[0][0] == '{' ) {
					$new_line .= '{' . $id . '_' . $i . '}';
				} else {
					$new_line .= $product[0];
				}

				// Get the product quantity tag
				if ( $product[1][0] == '{' ) {
					$new_line .= '|{' . $quantity . '_' . $i . '}';
				} elseif ( ! empty( $product[1] ) ) {
						$new_line .= '|' . $product[1];
				}

				// Get the product variation ID tag
				if ( $product[2][0] == '{' ) {
					$new_line .= '|{' . $variation . '_' . $i . '}';
				} elseif ( ! empty( $product[2] ) ) {
						$new_line .= '|' . $product[2];
				}

				// Get the product price tag
				if ( $product[3][0] == '{' ) {
					$new_line .= '|{' . $price . '_' . $i . '}';
				} elseif ( ! empty( $product[3] ) ) {
						$new_line .= '|' . $product[3];
				}

				$products_tags[] = $new_line;
				$looped[ $i ]    = $i;
				++$i;
				return array(
					'i'             => $i,
					'looped'        => $looped,
					'products_tags' => $products_tags,
				);
			} else {
				return false;
			}
		}


		/**
		 * Hook into before sending email and check if we need to create or update a post or taxonomy
		 *
		 *  @since      1.0.0
		 */
		public static function before_email_success_msg( $atts ) {
			$settings = $atts['settings'];
			$wcs      = null;
			if ( isset( $settings['_woocommerce'] ) ) {
				$wcs = $settings['_woocommerce'];
			}
			$form_id = absint( $atts['form_id'] );
			// If WC checkout is enabled but WC is not installed and activated
			if ( ( isset( $settings['_woocommerce'] ) ) && ( $settings['_woocommerce']['checkout'] == 'true' ) ) {
				global $woocommerce;
				if ( ! $woocommerce ) {
					$msg = esc_html__( 'WooCommerce Checkout is enabled for this form, but WooCommerce itself is not activated!', 'super-forms' );
					SUPER_Common::output_message(
						array(
							'msg'     => $msg,
							'form_id' => absint( $form_id ),
						)
					);
				}
			}

			if ( isset( $atts['data'] ) ) {
				$data = $atts['data'];
			} elseif ( $settings['save_contact_entry'] == 'yes' ) {
					$data = get_post_meta( $atts['entry_id'], '_super_contact_entry_data', true );
			} else {
				$data = $atts['post']['data'];
			}

			// @since 1.9.3 - check if we do not want to checkout to WooCommerce conditionally
			$wcs = SUPER_Common::get_form_woocommerce_settings( $form_id );
			if ( ! empty( $wcs ) && $wcs['checkout'] == 'true' ) {
				$wcs['checkout'] = SUPER_Common::conditionally_wc_checkout( $data, $wcs, $settings );
			}

			// @since 1.2.2 - first reset order entry data
			SUPER_Common::setClientData(
				array(
					'name'  => 'wc_entry_data',
					'value' => false,
				)
			);

			if ( isset( $wcs ) && $wcs['checkout'] == 'true' ) {
				// @since 1.2.2 - save the entry data to the order
				SUPER_Common::setClientData(
					array(
						'name'  => 'wc_entry_data',
						'value' => $data,
					)
				);
				foreach ( $wcs['products'] as $k => $v ) {
					$id         = trim( $v['id'], '{}' );
					$id         = explode( ';', $id );
					$qty        = trim( $v['qty'], '{}' );
					$qty        = explode( ';', $qty );
					$price      = trim( $v['price'], '{}' );
					$price      = explode( ';', $price );
					$variation  = trim( $v['variation'], '{}' );
					$variation  = explode( ';', $variation );
					$meta       = $v['meta'];
					$meta_items = $v['items'];
					$i          = 2;
					while ( isset( $data[ $id[0] . '_' . $i ] ) || isset( $data[ $qty[0] . '_' . $i ] ) || isset( $data[ $price[0] . '_' . $i ] ) || isset( $data[ $variation[0] . '_' . $i ] ) ) {
						$new = $wcs['products'][ $k ];
						if ( '' !== $new['id'] && $new['id'][0] == '{' ) {
							$new['id'] = '{' . $id[0] . '_' . $i . ( isset( $id[1] ) ? ';' . $id[1] : '' ) . '}';
						}
						if ( '' !== $new['qty'] && $new['qty'][0] == '{' ) {
							$new['qty'] = '{' . $qty[0] . '_' . $i . ( isset( $qty[1] ) ? ';' . $qty[1] : '' ) . '}';
						}
						if ( '' !== $new['variation'] && $new['variation'][0] == '{' ) {
							$new['variation'] = '{' . $variation[0] . '_' . $i . ( isset( $variation[1] ) ? ';' . $variation[1] : '' ) . '}';
						}
						if ( '' !== $new['price'] && $new['price'][0] == '{' ) {
							$new['price'] = '{' . $price[0] . '_' . $i . ( isset( $price[1] ) ? ';' . $price[1] : '' ) . '}';
						}
						if ( $new['meta'] === 'true' ) {
							$new_meta = $new['items'];
							foreach ( $new_meta as $mk => $mv ) {
								$label = trim( $mv['label'], '{}' );
								$label = explode( ';', $label );
								$value = trim( $mv['value'], '{}' );
								$value = explode( ';', $value );
								if ( '' !== $mv['label'] && $mv['label'][0] == '{' ) {
									$new_meta[ $mk ]['label'] = '{' . $label[0] . '_' . $i . ( isset( $label[1] ) ? ';' . $label[1] : '' ) . '}';
								}
								if ( '' !== $mv['value'] && $mv['value'][0] == '{' ) {
									$new_meta[ $mk ]['value'] = '{' . $value[0] . '_' . $i . ( isset( $value[1] ) ? ';' . $value[1] : '' ) . '}';
								}
							}
							$new['items'] = $new_meta;
						}
						$wcs['products'][] = $new;
						++$i;
					}
				}
				$products = array();
				foreach ( $wcs['products'] as $k => $v ) {
					$qty = absint( SUPER_Common::email_tags( $v['qty'], $data, $settings ) );
					if ( $qty > 0 ) {
						$id        = SUPER_Common::email_tags( $v['id'], $data, $settings );
						$variation = SUPER_Common::email_tags( $v['variation'], $data, $settings );
						$price     = SUPER_Common::email_tags( $v['price'], $data, $settings );
						$price     = self::tofloat( $price );
						foreach ( $v['items'] as $ik => $iv ) {
							// Skip items that don't exist in the form data
							$value_before = $v['items'][ $ik ]['value'];
							$value_after  = SUPER_Common::email_tags( $v['items'][ $ik ]['value'], $data, $settings );
							if ( trim( $value_before ) === trim( $value_after ) ) {
								// Didn't change, might be the case that this {tag} doesn't exist in the form data
								if ( preg_match( '/{(.+?)}/', $value_after ) ) {
									unset( $v['items'][ $ik ] );
									continue;
								}
							} else {
								$v['items'][ $ik ]['value'] = $value_after;
							}
							// Delete any empty product meta items
							if ( empty( $v['items'][ $ik ]['value'] ) ) {
								unset( $v['items'][ $ik ] );
								continue;
							}
							// Replace possible {tags} for the label
							$v['items'][ $ik ]['label'] = SUPER_Common::email_tags( $v['items'][ $ik ]['label'], $data, $settings );
						}
						// Check if multiple ID's found (separate by comma)
						$multi_products = explode( ',', $id );
						foreach ( $multi_products as $product_id ) {
							$products[] = array(
								'id'           => $product_id,
								'quantity'     => $qty,
								'variation_id' => $variation,
								'price'        => $price,
								'super_data'   => $v['items'],
							);
						}
					}
				}

				// Empty the cart
				if ( ( isset( $wcs['empty_cart'] ) ) && ( $wcs['empty_cart'] == 'true' ) ) {
					$woocommerce->cart->empty_cart();
				}
				// Remove any coupons.
				if ( ( isset( $wcs['remove_coupons'] ) ) && ( $wcs['remove_coupons'] == 'true' ) ) {
					$woocommerce->cart->remove_coupons();
				}
				// Add discount
				if ( ( isset( $wcs['coupon'] ) ) && ( $wcs['coupon'] != '' ) ) {
					$woocommerce->cart->add_discount( $wcs['coupon'] );
				}
				// Delete any fees
				if ( ( isset( $wcs['remove_fees'] ) ) && ( $wcs['remove_fees'] == 'true' ) ) {
					$woocommerce->session->set( 'fees', array() );
					SUPER_Common::setClientData(
						array(
							'name'  => 'wc_fee',
							'value' => false,
						)
					);
				}
				// Add fee
				if ( ! empty( $wcs['fees'] ) ) {
					if ( $wcs['fees']['enabled'] === 'true' ) {
						$fees = array();
						foreach ( $wcs['fees']['items'] as $k => $v ) {
							$name      = '';
							$amount    = 0;
							$taxable   = false;
							$tax_class = '';
							if ( isset( $fee[0] ) ) {
								$name = SUPER_Common::email_tags( $v['name'], $data, $settings );
							}
							if ( isset( $fee[1] ) ) {
								$amount = SUPER_Common::email_tags( $v['amount'], $data, $settings );
							}
							if ( isset( $fee[2] ) ) {
								$taxable = SUPER_Common::email_tags( $v['taxable'], $data, $settings );
							}
							if ( isset( $fee[3] ) ) {
								$tax_class = SUPER_Common::email_tags( $v['tax_class'], $data, $settings );
							}
							$amount = self::tofloat( $amount );
							if ( $amount > 0 ) {
								$fees[] = array(
									'name'      => $name,            // (string) required – Unique name for the fee. Multiple fees of the same name cannot be added.
									'amount'    => $amount,        // (float) required – Fee amount.
									'taxable'   => $taxable,      // (bool) optional – (default: false) Is the fee taxable?
									'tax_class' => $tax_class,  // (string) optional – (default: '') The tax class for the fee if taxable. A blank string is standard tax class.
								);
							}
						}
						SUPER_Common::setClientData(
							array(
								'name'  => 'wc_fee',
								'value' => $fees,
							)
						);
					}
				}
				if ( ! empty( $wcs['fields'] ) ) {
					if ( $wcs['fields']['enabled'] === 'true' ) {
						$fields = array();
						foreach ( $wcs['fields']['items'] as $k => $v ) {
							// Only add the field if the field name was visible in the form itself
							if ( empty( $v['name'] ) ) {
								continue;
							}
							$name = SUPER_Common::email_tags( $v['name'], $data, $settings );
							if ( empty( $name ) ) {
								continue;
							}
							if ( isset( $v['skip'] ) && $v['skip'] == 'true' && ! isset( $data[ $name ] ) ) {
								continue;
							}
							// Add the field
							if ( isset( $v['options'] ) && is_array( $v['options'] ) ) {
								foreach ( $v['options'] as $ok => $ov ) {
									$v['options'][ $ok ]['label'] = SUPER_Common::email_tags( $ov['label'], $data, $settings );
									$v['options'][ $ok ]['value'] = SUPER_Common::email_tags( $ov['value'], $data, $settings );
								}
							}
							$fields[] = array(
								'name'        => $name,
								'value'       => SUPER_Common::email_tags( $v['value'], $data, $settings ),
								'label'       => SUPER_Common::email_tags( $v['label'], $data, $settings ),
								'placeholder' => SUPER_Common::email_tags( $v['placeholder'], $data, $settings ),
								'type'        => SUPER_Common::email_tags( $v['type'], $data, $settings ),
								'section'     => SUPER_Common::email_tags( $v['section'], $data, $settings ),
								'required'    => SUPER_Common::email_tags( $v['required'], $data, $settings ),
								'clear'       => SUPER_Common::email_tags( $v['clear'], $data, $settings ),
								'class'       => SUPER_Common::email_tags( $v['class'], $data, $settings ),
								'label_class' => SUPER_Common::email_tags( $v['label_class'], $data, $settings ),
								'options'     => $v['options'],
							);
						}
						SUPER_Common::setClientData(
							array(
								'name'  => 'wc_custom_fields',
								'value' => $fields,
							)
						);
					}
				}

				$products = apply_filters(
					'super_before_adding_wc_products_to_cart_filter',
					$products,
					array(
						'settings' => $settings,
						'data'     => $data,
					)
				);

				// Now add the product(s) to the cart
				foreach ( $products as $k => $v ) {
					if ( class_exists( 'WC_Name_Your_Price_Helpers' ) ) {
						$posted_nyp_field = 'nyp' . apply_filters( 'nyp_field_prefix', '', $v['id'] );
						// Make sure we set the correct price format for Name Your Price based on the defined WooCommerce thousand/decimal separator and decimal separators
						$price                         = (float) $v['price']; // Convert to float to avoid issues on PHP 8.
						$negative                      = $price < 0;
						$price                         = $v['price'];
						$price                         = number_format( $price, wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator() );
						$_REQUEST[ $posted_nyp_field ] = ( $negative ? '-' : '' ) . $price;
					}

					$new_attributes = array();
					if ( $v['variation_id'] != 0 ) {
						$product = wc_get_product( $v['id'] );
						if ( $product->product_type == 'variable' ) {
							$attributes = $product->get_variation_attributes();
							foreach ( $attributes as $ak => $av ) {
								$new_attributes[ $ak ] = get_post_meta( $v['variation_id'], 'attribute_' . $ak, true );
							}
						}
					}

					$super_data = array();
					if ( isset( $v['super_data'] ) ) {
						$super_data = array( 'super_data' => $v['super_data'] );
					}
					$cart_item_key = $woocommerce->cart->add_to_cart(
						$v['id'],               // ( int ) optional – contains the id of the product to add to the cart
						$v['quantity'],         // ( int ) optional default: 1 – contains the quantity of the item to add
						$v['variation_id'],     // ( int ) optional –
						$new_attributes,        // ( array ) optional – attribute values
						$super_data             // ( array ) optional – extra cart item data we want to pass into the item
					);
				}

				// Redirect to cart / checkout page
				if ( isset( $wcs['redirect'] ) ) {

					if ( ( isset( $wcs['woocommerce_populate_checkout_fields'] ) ) && ( $wcs['woocommerce_populate_checkout_fields'] != '' ) ) {
						// First reset
						$woocommerce->session->set( '_super_form_woocommerce_populate_checkout_fields', array() );
						$fields                               = array();
						$woocommerce_populate_checkout_fields = explode( "\n", $wcs['woocommerce_populate_checkout_fields'] );
						foreach ( $woocommerce_populate_checkout_fields as $k => $v ) {
							$field = explode( '|', $v );
							if ( ! isset( $field[0] ) ) {
								continue;
							}
							$value = '';
							if ( isset( $field[1] ) ) {
								$value = SUPER_Common::email_tags( $field[1], $data, $settings );
							}
							$fields[ $field[0] ] = $value;
						}
						$woocommerce->session->set( '_super_form_woocommerce_populate_checkout_fields', $fields );
					}

					$woocommerce->session->set( '_super_form_data', $data ); // @since 1.2.0 - save data to session for billing fields
					$redirect = null;
					if ( $wcs['redirect'] == 'checkout' ) {
						$redirect = wc_get_checkout_url();
					}
					if ( $wcs['redirect'] == 'cart' ) {
						$redirect = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
					}
					if ( $redirect != null ) {
						SUPER_Common::output_message(
							array(
								'error'    => false,
								'msg'      => '',
								'redirect' => $redirect,
								'form_id'  => absint( $form_id ),
							)
						);
					}
				}
			}
		}


		/**
		 * Hook into settings and add WooCommerce settings
		 *
		 *  @since      1.0.0
		 */
		public static function add_settings( $array, $x ) {
			$default      = $x['default'];
			$settings     = $x['settings'];
			$statuses     = SUPER_Settings::get_entry_statuses();
			$new_statuses = array();
			foreach ( $statuses as $k => $v ) {
				$new_statuses[ $k ] = $v['name'];
			}
			$statuses = $new_statuses;
			unset( $new_statuses );
			$array['woocommerce_checkout_global'] = array(
				'hidden' => true,
				'name'   => esc_html__( 'WooCommerce Checkout', 'super-forms' ),
				'label'  => esc_html__( 'WooCommerce Checkout', 'super-forms' ),
				'fields' => array(
					// Hide products from shop by product ID or Category Slug
					// This will also disallow products from being added into the cart via normal WooCommerce behaviour
					// This allows the product to only be added via a Super Forms submission
					'wc_hide_products_by_id_or_slug'       => array(
						'name'    => esc_html__( 'Hide products from the shop', 'super-forms' ),
						'label'   => sprintf( esc_html__( 'When configured, the product(s) will not be listed on the Archive pages (shop, category, tag and search pages). This way the product can only be ordered by submitting the form.%1$s%1$sPut each product ID and or Category slug on a new line. For example:%1$s%1$s3245%1$s3246%1$s3247%1$sbooks%1$scars%1$scomputers', 'super-forms' ), '<br />' ),
						'default' => '',
						'type'    => 'textarea',
					),
					// Replace the default "Add to cart" area on the single product page with a form.
					// This will also replace the "Add to cart" button on Archive pages with a "View Product" button so the user is navigated to the single product page
					// and able to fill out the form.
					// This setting will obviously become obsolete for products you are hiding in above settings
					'wc_replace_add_to_cart_btn'           => array(
						'name'        => esc_html__( 'Replace the default "Add to cart" area', 'super-forms' ),
						'label'       => sprintf( esc_html__( 'When configured, the single product page "Add to cart" section will be replaced with the defined form. It will also replace the "Add to cart" button with a "View Product" button on all Archive pages (shop, category, tag and search pages).%1$s%1$sThis setting will obviously become obsolete for products you are hiding from your shop in the above setting.%1$s%1$sDefine each on a new line formatted like so:%1$s%1$sFORM_ID|PRODUCT_ID%1$sFORM_ID|CATEGORY_SLUG', 'super-forms' ), '<br />' ),
						'default'     => '',
						'type'        => 'textarea',
						'placeholder' => esc_html__( "FORM_ID|PRODUCT_ID\nFORM_ID|CATEGORY_SLUG", 'super-forms' ),
					),
					// Optionally delete, gallery, title, price, rating, excerpt from product page and only display the form
					'wc_remove_single_product_page_gallery' => array(
						'name'    => esc_html__( 'Remove gallery from single product page', 'super-forms' ),
						'label'   => sprintf( esc_html__( 'When enabled and above setting replaced the "Add to cart" section with a form, the gallery of the product will also be removed', 'super-forms' ) ),
						'default' => '',
						'type'    => 'checkbox',
						'values'  => array(
							'true' => esc_html__( 'Remove gallery (images) from single product page', 'super-forms' ),
						),
					),
					'wc_remove_single_product_page_title'  => array(
						'name'    => esc_html__( 'Remove title from single product page', 'super-forms' ),
						'label'   => sprintf( esc_html__( 'When enabled and above setting replaced the "Add to cart" section with a form, the title of the product will also be removed', 'super-forms' ) ),
						'default' => '',
						'type'    => 'checkbox',
						'values'  => array(
							'true' => esc_html__( 'Remove title from single product page', 'super-forms' ),
						),
					),
					'wc_remove_single_product_page_rating' => array(
						'name'    => esc_html__( 'Remove rating from single product page', 'super-forms' ),
						'label'   => sprintf( esc_html__( 'When enabled and above setting replaced the "Add to cart" section with a form, the rating of the product will also be removed', 'super-forms' ) ),
						'default' => '',
						'type'    => 'checkbox',
						'values'  => array(
							'true' => esc_html__( 'Remove rating from single product page', 'super-forms' ),
						),
					),
					'wc_remove_single_product_page_price'  => array(
						'name'    => esc_html__( 'Remove price from single product page', 'super-forms' ),
						'label'   => sprintf( esc_html__( 'When enabled and above setting replaced the "Add to cart" section with a form, the price of the product will also be removed', 'super-forms' ) ),
						'default' => '',
						'type'    => 'checkbox',
						'values'  => array(
							'true' => esc_html__( 'Remove price from single product page', 'super-forms' ),
						),
					),
					'wc_remove_single_product_page_excerpt' => array(
						'name'    => esc_html__( 'Remove short description from single product page', 'super-forms' ),
						'label'   => sprintf( esc_html__( 'When enabled and above setting replaced the "Add to cart" section with a form, the short description of the product will also be removed', 'super-forms' ) ),
						'default' => '',
						'type'    => 'checkbox',
						'values'  => array(
							'true' => esc_html__( 'Remove short description from single product page', 'super-forms' ),
						),
					),
				),
			);
			$array['woocommerce_checkout'] = array(
				'hidden' => 'settings',
				'name'   => esc_html__( 'WooCommerce Checkout', 'super-forms' ),
				'label'  => esc_html__( 'WooCommerce Checkout', 'super-forms' ),
				'docs'   => array(
					array(
						'title' => 'Fixed price checkout',
						'url'   => '/features/integrations/woocommerce-checkout/fixed-price-checkout',
					),
					array(
						'title' => 'Dynamic price checkout',
						'url'   => '/features/integrations/woocommerce-checkout/dynamic-price-checkout',
					),
					array(
						'title' => 'Variable product checkout (variations)',
						'url'   => '/features/integrations/woocommerce-checkout/variable-product-checkout-variations',
					),
					array(
						'title' => 'Replacing the "Add to cart" on a product page with a form',
						'url'   => '/features/integrations/woocommerce-checkout/replacing-the-add-to-cart-on-a-product-page-with-a-form',
					),
					array(
						'title' => 'Hiding product from shop and order via custom form',
						'url'   => '/features/integrations/woocommerce-checkout/hiding-product-from-shop-and-order-via-custom-form',
					),
				),
				'html'   => array(
					sprintf( esc_html__( '%1$s%2$sNote: %3$sThese settings have been moved to the [WooCommerce] TAB%4$s', 'super-forms' ), '<div class="sfui-notice sfui-desc">', '<strong>', '</strong>', '</div>' ),
				),
			);
			return $array;
			$array['woocommerce_checkout'] = array(
				'hidden' => 'settings',
				'name'   => esc_html__( 'WooCommerce Checkout', 'super-forms' ),
				'label'  => esc_html__( 'WooCommerce Checkout', 'super-forms' ),
				'docs'   => array(
					array(
						'title' => 'Fixed price checkout',
						'url'   => '/features/integrations/woocommerce-checkout/fixed-price-checkout',
					),
					array(
						'title' => 'Dynamic price checkout',
						'url'   => '/features/integrations/woocommerce-checkout/dynamic-price-checkout',
					),
					array(
						'title' => 'Variable product checkout (variations)',
						'url'   => '/features/integrations/woocommerce-checkout/variable-product-checkout-variations',
					),
					array(
						'title' => 'Replacing the "Add to cart" on a product page with a form',
						'url'   => '/features/integrations/woocommerce-checkout/replacing-the-add-to-cart-on-a-product-page-with-a-form',
					),
					array(
						'title' => 'Hiding product from shop and order via custom form',
						'url'   => '/features/integrations/woocommerce-checkout/hiding-product-from-shop-and-order-via-custom-form',
					),
				),
				// tmp 'html' => array(
				// tmp     sprintf( esc_html__( '%s%sNote:%s if you only want users to be able to order products via this form you can configure the products in question to be excluded from your regular shop. Alternatively you can also define to replace the product "Add to cart" area with your form. You can define those settings here: %sSuper Forms > Settings > WooCommerce Checkout%s%s', 'super-forms' ), '<div class="sfui-notice sfui-desc">', '<strong>', '</strong>', '<a target="_blank" href="' . esc_url(admin_url()) . 'admin.php?page=super_settings#woocommerce-checkout-global">', '</a>', '</div>' ),
				// tmp     sprintf( esc_html__( '%s%sDocumentation:%s %sSingle product checkout with a fixed quantity and price%s%s', 'super-forms' ), '<div class="sfui-notice sfui-desc">', '<strong>', '</strong>', '<a target="_blank" href="https://webrehab.zendesk.com/hc/en-gb/articles/360018449398"">', '</a>', '</div>' )
				// tmp ),
				'fields' => array(
					'woocommerce_checkout'                 => array(
						'default' => '',
						'type'    => 'checkbox',
						'filter'  => true,
						'values'  => array(
							'true' => esc_html__( 'Enable WooCommerce Checkout', 'super-forms' ),
						),
					),
					// @since 1.9.3 - Conditionally checkout to WooCommerce
					'conditionally_wc_checkout'            => array(
						'hidden_setting' => true,
						'default'        => '',
						'type'           => 'checkbox',
						'filter'         => true,
						'values'         => array(
							'true' => esc_html__( 'Conditionally checkout to WooCommerce', 'super-forms' ),
						),
						'parent'         => 'woocommerce_checkout',
						'filter_value'   => 'true',
					),
					'conditionally_wc_checkout_check'      => array(
						'hidden_setting' => true,
						'type'           => 'conditional_check',
						'name'           => esc_html__( 'Only checkout to WooCommerce when following condition is met', 'super-forms' ),
						'label'          => esc_html__( 'You are allowed to enter field {tags} to do the check', 'super-forms' ),
						'default'        => '',
						'placeholder'    => '{fieldname},value',
						'filter'         => true,
						'parent'         => 'conditionally_wc_checkout',
						'filter_value'   => 'true',
					),

					'woocommerce_checkout_empty_cart'      => array(
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'true' => esc_html__( 'Empty cart before adding products', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),
					'woocommerce_checkout_remove_coupons'  => array(
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'true' => esc_html__( 'Remove/clear coupons before redirecting to cart', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),
					'woocommerce_checkout_remove_fees'     => array(
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'true' => esc_html__( 'Remove/clear fees before redirecting to cart', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),
					'woocommerce_checkout_products'        => array(
						'name'         => esc_html__( 'Enter the product(s) ID that needs to be added to the cart', 'super-forms' ) . '<br />',
						'label'        => sprintf( esc_html__( 'Put each on a new line, {tags} can be used to retrieve data.%3$sIf field is inside dynamic column, system will automatically add all the products. Put each product ID with it\'s quantity on a new line separated by pipes "|".%3$sExample with tags:%3$s%1$s%4$s%2$s%3$sExample without tags:%3$s%1$s%5$s%2$s%3$sExample with variations:%3$s%1$s%6$s%2$s%3$sExample with dynamic pricing:%3$s%1$s%7$s%2$s%3$sAllowed values:%3$s%1$s%8$s%2$s%3$s(dynamic pricing requires %9$sWooCommerce Name Your Price%10$s add-on).', 'super-forms' ), '<strong style="color:red;">', '</strong>', '<br />', '{product_id}|{cart_qty}', '82921|3', '{product_id}|{cart_qty}|{variation_id}', '{product_id}|{cart_qty}|none|{price}', 'integer|integer|integer|float', '<a target="_blank" href="https://woocommerce.com/products/name-your-price/">', '</a>' ),
						'type'         => 'textarea',
						'default'      => '{id}|{quantity}|none|{price}',
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
						'allow_empty'  => true,
					),

					// @since 1.3.4 - custom product meta data
					'woocommerce_checkout_products_meta'   => array(
						'name'         => esc_html__( 'Enter the product(s) custom meta data (optional)', 'super-forms' ) . '<br />',
						'label'        => esc_html__( 'Put each on a new line, {tags} can be used to retrieve data', 'super-forms' ) . '<br /> ' . sprintf( esc_html__( 'If field is inside dynamic column, system will automatically add all the meta data. Put each product ID with it\'s meta data on a new line separated by pipes "|".%1$s%2$sExample with tags:%3$s {id}|Color|{color}%1$s%2$sExample without tags:%3$s 82921|Color|Red%1$s%2$sAllowed values:%3$s integer|string|string.', 'super-forms' ), '<br />', '<strong>', '</strong>' ),
						'type'         => 'textarea',
						'default'      => '{id}|Color|{color}',
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
						'allow_empty'  => true,
					),

					'woocommerce_checkout_coupon'          => array(
						'name'         => esc_html__( 'Apply the following coupon code (leave blank for none):', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),
					'woocommerce_checkout_fees'            => array(
						'name'         => esc_html__( 'Add checkout fee(s)', 'super-forms' ) . '<br />',
						'label'        => esc_html__( 'Leave blank for no fees', 'super-forms' ) . '<br />' . sprintf( esc_html__( 'Put each fee on a new line with values separated by pipes "|".%1$s%2$sExample with tags:%3$s {fee_name}|{amount}|{taxable}|{tax_class}%1$s%2$sExample without tags:%3$s Administration fee|5|false|\'\'%1$s%2$sAllowed values:%3$s string|float|bool|string', 'super-forms' ), '<br />', '<strong>', '</strong>' ),
						'type'         => 'textarea',
						'default'      => '{fee_name}|{amount}|{taxable}|{tax_class}',
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
						'allow_empty'  => true,
					),
					'woocommerce_populate_checkout_fields' => array(
						'name'         => esc_html__( 'Populate checkout fields with form data', 'super-forms' ) . '<br />',
						'label'        => esc_html__( 'Leave blank to not populate any checkout fields', 'super-forms' ) . '<br />' . esc_html__( 'Put each on a new line', 'super-forms' ) . '<br />' . sprintf( esc_html__( 'Example:%1$sbilling_first_name|{first_name}%1$sbilling_last_name|{last_name}%1$sbilling_postcode|{zipcode}%1$sbilling_city|{city}%1$s%1$sPossible field to populate on checkout page are:%1$s', 'super-forms' ), '<br />' ) . ' billing_country, shipping_country, billing_first_name, billing_last_name, billing_company, billing_country, billing_address_1, billing_address_2, billing_postcode, billing_city, billing_state, billing_phone, billing_email, order_comments',
						'type'         => 'textarea',
						'default'      => '',
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),
					// @since 1.2.2 - add custom checkout fields to checkout page
					'woocommerce_checkout_fields'          => array(
						'name'         => esc_html__( 'Add custom checkout field(s)', 'super-forms' ) . '<br />',
						'label'        => sprintf( esc_html__( 'Leave blank for no custom fields. Put each field on a new line with field options separated by pipes "|".%3$sFormat:%3$s%1$s%4$s%2$s%3$sExample with tags:%3$s%1$s%4$s%2$s%3$sOptions:%3$s%6$s - the field name%3$s%7$s - the field value ({tags} can be used here)%3$s%8$s - label for the input field%3$s%9$s - placeholder for the input%3$s%10$s - type of field (text, textarea, password, select)%3$s%11$s - billing, shipping, account, order%3$s%12$s - true or false, whether or not the field is required%3$s%13$s - true or false, applies a clear fix to the field/label%3$s%14$s - class for the input%3$s%15$s - class for the label element%3$s%16$s - for select boxes, array of options (key => value pairs)', 'super-forms' ), '<strong style="color:red;">', '</strong>', '<br />', 'name|value|label|placeholder|type|section|required|clear|class|label_class|options', 'billing_custom|{billing_custom}|Billing custom|This is a custom field|select|billing|true|true|super-billing-custom|super-billing-custom-label|red,Red;blue,Blue;green,Green', 'name', 'value', 'label', 'placeholder', 'type', 'section', 'required', 'clear', 'class', 'label_class', 'options' ),
						'type'         => 'textarea',
						'default'      => '',
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),
					'woocommerce_checkout_fields_skip_empty' => array(
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'true' => esc_html__( 'Only add custom field if field exists in form and not conditionally hidden', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),

					'woocommerce_redirect'                 => array(
						'name'         => esc_html__( 'Redirect to Checkout page or Shopping Cart?', 'super-forms' ),
						'default'      => 'checkout',
						'type'         => 'select',
						'values'       => array(
							'checkout' => esc_html__( 'Checkout page (default)', 'super-forms' ),
							'cart'     => esc_html__( 'Shopping Cart', 'super-forms' ),
							'none'     => esc_html__( 'None (use the form redirect)', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),

					'woocommerce_completed_entry_status'   => array(
						'name'         => esc_html__( 'Entry status after payment completed', 'super-forms' ),
						'label'        => sprintf( esc_html__( 'You can add custom statuses via %1$sSuper Forms > Settings > Backend Settings%2$s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url( admin_url() . 'admin.php?page=super_settings#backend-settings' ) . '">', '</a>' ),
						'default'      => 'completed',
						'type'         => 'select',
						'values'       => $statuses,
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),

					// @since 1.3.8 - option to send email after payment completed
					'woocommerce_completed_email'          => array(
						'name'         => esc_html__( 'Send email after order completed', 'super-forms' ),
						'label'        => esc_html__( 'Note: this will only work if you save a contact entry', 'super-forms' ),
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'true' => esc_html__( 'Send email after order completed', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'woocommerce_checkout',
						'filter_value' => 'true',
					),

				),
			);

			// @since 1.3.8 - option to send email after payment completed
			$fields     = $array['confirmation_email_settings']['fields'];
			$new_fields = array();
			foreach ( $fields as $k => $v ) {
				if ( $k == 'confirm' ) {
					unset( $fields[ $k ] );
					continue;
				}
				if ( ! empty( $v['parent'] ) ) {
					if ( $v['parent'] == 'confirm' ) {
						$v['parent']       = 'woocommerce_completed_email';
						$v['filter_value'] = 'true';
					} else {
						$v['parent'] = str_replace( 'confirm', 'woocommerce_completed', $v['parent'] );
					}
				}
				unset( $fields[ $k ] );
				$k = str_replace( 'confirm', 'woocommerce_completed', $k );
				// $v['default'] = SUPER_Settings::get_value( $default, $k, $settings, $v['default'] );
				$new_fields[ $k ] = $v;
			}
			$array['woocommerce_checkout']['fields']                                      = array_merge( $array['woocommerce_checkout']['fields'], $new_fields );
			$array['woocommerce_checkout']['fields']['woocommerce_completed_attachments'] = array(
				'name'         => esc_html__( 'Attachments for woocommerce completed emails:', 'super-forms' ),
				'label'        => esc_html__( 'Upload a file to send as attachment', 'super-forms' ),
				'default'      => '',
				'type'         => 'file',
				'multiple'     => 'true',
				'filter'       => true,
				'parent'       => 'woocommerce_completed_email',
				'filter_value' => 'true',
			);

			if ( class_exists( 'SUPER_Frontend_Posting' ) ) {
				$array['woocommerce_checkout']['fields']['woocommerce_post_status'] = array(
					'name'         => esc_html__( 'Post status after payment complete', 'super-forms' ),
					'label'        => esc_html__( 'Only used for Front-end posting', 'super-forms' ),
					'default'      => 'publish',
					'type'         => 'select',
					'values'       => array(
						'publish'    => esc_html__( 'Publish (default)', 'super-forms' ),
						'future'     => esc_html__( 'Future', 'super-forms' ),
						'draft'      => esc_html__( 'Draft', 'super-forms' ),
						'pending'    => esc_html__( 'Pending', 'super-forms' ),
						'private'    => esc_html__( 'Private', 'super-forms' ),
						'trash'      => esc_html__( 'Trash', 'super-forms' ),
						'auto-draft' => esc_html__( 'Auto-Draft', 'super-forms' ),
					),
					'filter'       => true,
					'parent'       => 'woocommerce_checkout',
					'filter_value' => 'true',
				);
			}

			if ( class_exists( 'SUPER_Register_Login' ) ) {
				global $wp_roles;
				$all_roles      = $wp_roles->roles;
				$editable_roles = apply_filters( 'editable_roles', $all_roles );
				$roles          = array();
				foreach ( $editable_roles as $k => $v ) {
					$roles[ $k ] = $v['name'];
				}
				$array['woocommerce_checkout']['fields']['woocommerce_signup_status']       = array(
					'name'         => esc_html__( 'Registered user login status after payment complete', 'super-forms' ),
					'label'        => esc_html__( 'Only used for Register & Login feature', 'super-forms' ),
					'default'      => 'active',
					'type'         => 'select',
					'values'       => array(
						'active'  => esc_html__( 'Active (default)', 'super-forms' ),
						'pending' => esc_html__( 'Pending', 'super-forms' ),
						'blocked' => esc_html__( 'Blocked', 'super-forms' ),
					),
					'filter'       => true,
					'parent'       => 'woocommerce_checkout',
					'filter_value' => 'true',
				);
				$array['woocommerce_checkout']['fields']['woocommerce_completed_user_role'] = array(
					'name'         => esc_html__( 'Change user role after payment complete', 'super-forms' ),
					'label'        => esc_html__( 'Only used for Register & Login feature', 'super-forms' ),
					'default'      => '',
					'type'         => 'select',
					'values'       => array_merge( $roles, array( '' => esc_html__( 'Do not change role', 'super-forms' ) ) ),
					'filter'       => true,
					'parent'       => 'woocommerce_checkout',
					'filter_value' => 'true',
				);

			}

			return $array;
		}
	}

endif;


/**
 * Returns the main instance of SUPER_WooCommerce to prevent the need to use globals.
 *
 * @return SUPER_WooCommerce
 */
if ( ! function_exists( 'SUPER_WooCommerce' ) ) {
	function SUPER_WooCommerce() {
		return SUPER_WooCommerce::instance();
	}
	// Global for backwards compatibility.
	$GLOBALS['SUPER_WooCommerce'] = SUPER_WooCommerce();
}
