<?php
/**
 * Super Forms - PayPal Checkout
 *
 * @package   Super Forms - PayPal Checkout
 * @author    WebRehab
 * @link      http://super-forms.com
 * @copyright 2022 by WebRehab
 * @license   GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - PayPal Checkout
 * Description: Checkout with PayPal after form submission. Charge users for registering or posting content.
 * Version:     1.5.1
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

if ( ! class_exists( 'SUPER_PayPal' ) ) :


	/**
	 * Main SUPER_PayPal Class
	 *
	 * @class SUPER_PayPal
	 */
	final class SUPER_PayPal {


		/**
		 * @var string
		 *
		 *  @since      1.0.0
		 */
		public $version = '1.5.1';


		/**
		 * @var string
		 *
		 *  @since      1.0.0
		 */
		public $add_on_slug = 'paypal';
		public $add_on_name = 'PayPal Checkout';


		/**
		 * @var array
		 *
		 *  @since      1.0.0
		 */
		public static $currency_codes          = array(
			'AUD' => array(
				'symbol' => '$',
				'name'   => 'Australian Dollar',
			),
			'BRL' => array(
				'symbol' => 'R$',
				'name'   => 'Brazilian Real',
			),
			'CAD' => array(
				'symbol' => '$',
				'name'   => 'Canadian Dollar',
			),
			'CZK' => array(
				'symbol' => '&#75;&#269;',
				'name'   => 'Czech Koruna',
			),
			'DKK' => array(
				'symbol' => '&#107;&#114;',
				'name'   => 'Danish Krone',
			),
			'EUR' => array(
				'symbol' => '&#128;',
				'name'   => 'Euro',
			),
			'HKD' => array(
				'symbol' => '&#20803;',
				'name'   => 'Hong Kong Dollar',
			),
			'HUF' => array(
				'symbol'  => '&#70;&#116;',
				'name'    => 'Hungarian Forint',
				'decimal' => true,
			),
			'ILS' => array(
				'symbol' => '&#8362;',
				'name'   => 'Israeli New Sheqel',
			),
			'JPY' => array(
				'symbol'  => '&#165;',
				'name'    => 'Japanese Yen',
				'decimal' => true,
			),
			'MYR' => array(
				'symbol' => '&#82;&#77;',
				'name'   => 'Malaysian Ringgit',
			),
			'MXN' => array(
				'symbol' => '&#36;',
				'name'   => 'Mexican Peso',
			),
			'NOK' => array(
				'symbol' => '&#107;&#114;',
				'name'   => 'Norwegian Krone',
			),
			'NZD' => array(
				'symbol' => '&#36;',
				'name'   => 'New Zealand Dollar',
			),
			'PHP' => array(
				'symbol' => '&#80;&#104;&#11;',
				'name'   => 'Philippine Peso',
			),
			'PLN' => array(
				'symbol' => '&#122;&#322;',
				'name'   => 'Polish Zloty',
			),
			'GBP' => array(
				'symbol' => '&#163;',
				'name'   => 'Pound Sterling',
			),
			'RUB' => array(
				'symbol' => '&#1088;&#1091;',
				'name'   => 'Russian Ruble',
			),
			'SGD' => array(
				'symbol' => '&#36;',
				'name'   => 'Singapore Dollar',
			),
			'SEK' => array(
				'symbol' => '&#107;&#114;',
				'name'   => 'Swedish Krona',
			),
			'CHF' => array(
				'symbol' => '&#67;&#72;&#70;',
				'name'   => 'Swiss Franc',
			),
			'TWD' => array(
				'symbol'  => '&#36;',
				'name'    => 'Taiwan New Dollar',
				'decimal' => true,
			),
			'THB' => array(
				'symbol' => '&#3647;',
				'name'   => 'Thai Baht',
			),
			'USD' => array(
				'symbol' => '$',
				'name'   => 'U.S. Dollar',
			),
		);
		public static $paypal_payment_statuses = array();


		/**
		 * @var SUPER_PayPal The single instance of the class
		 *
		 *  @since      1.0.0
		 */
		protected static $_instance = null;


		/**
		 * Main SUPER_PayPal Instance
		 *
		 * Ensures only one instance of SUPER_PayPal is loaded or can be loaded.
		 *
		 * @static
		 * @see SUPER_PayPal()
		 * @return SUPER_PayPal - Main instance
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
		 * SUPER_PayPal Constructor.
		 *
		 *  @since      1.0.0
		 */
		public function __construct() {
			$this->init_hooks();
			do_action( 'super_paypal_loaded' );
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

			add_action( 'init', array( $this, 'set_payment_statuses' ), 0 );

			add_filter( 'super_after_contact_entry_data_filter', array( $this, 'add_entry_order_link' ), 10, 2 );
			add_action( 'init', array( $this, 'register_post_types' ), 5 );
			add_action( 'parse_request', array( $this, 'paypal_ipn' ) );

			if ( $this->is_request( 'admin' ) ) {

				add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
				add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 1 );
				add_filter( 'manage_super_paypal_txn_posts_columns', array( $this, 'super_paypal_txn_columns' ), 999999 );
				add_filter( 'manage_super_paypal_sub_posts_columns', array( $this, 'super_paypal_sub_columns' ), 999999 );
				add_filter( 'super_enqueue_styles', array( $this, 'backend_styles' ) );

				add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
				add_action( 'init', array( $this, 'custom_paypal_txn_status' ) );
				add_action( 'admin_footer-post.php', array( $this, 'append_paypal_txn_status_list' ) );
				add_action( 'manage_super_paypal_txn_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );
				add_action( 'manage_super_paypal_sub_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );

				add_action( 'current_screen', array( $this, 'after_screen' ), 0 );
				add_action( 'current_screen', array( $this, 'reset_paypal_counter' ) );
				add_action( 'restrict_manage_posts', array( $this, 'filter_form_dropdown' ) );

			}
			if ( $this->is_request( 'ajax' ) ) {
				add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ), 20 );
			}

			// Actions since 1.0.0
			// add_action( 'super_front_end_posting_after_insert_post_action', array( $this, 'save_post_id' ) );
			// add_action( 'super_after_wp_insert_user_action', array( $this, 'save_user_id' ) );
		}


		public function set_payment_statuses() {
			self::$paypal_payment_statuses = array(
				'Canceled_Reversal' => array(
					'label' => esc_html__( 'Canceled Reversal', 'super-forms' ),
					'desc'  => esc_html__( 'A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.', 'super-forms' ),
				),
				'Completed'         => array(
					'label' => esc_html__( 'Completed', 'super-forms' ),
					'desc'  => esc_html__( 'The payment has been completed, and the funds have been added successfully to your account balance.', 'super-forms' ),
				),
				'Created'           => array(
					'label' => esc_html__( 'Created', 'super-forms' ),
					'desc'  => esc_html__( 'A German ELV payment is made using Express Checkout.', 'super-forms' ),
				),
				'Denied'            => array(
					'label' => esc_html__( 'Denied', 'super-forms' ),
					'desc'  => esc_html__( 'The payment was denied. This happens only if the payment was previously pending because of one of the reasons listed for the pending_reason variable or the Fraud_Management_Filters_x variable.', 'super-forms' ),
				),
				'Expired'           => array(
					'label' => esc_html__( 'Expired', 'super-forms' ),
					'desc'  => esc_html__( 'This authorization has expired and cannot be captured.', 'super-forms' ),
				),
				'Failed'            => array(
					'label' => esc_html__( 'Failed', 'super-forms' ),
					'desc'  => esc_html__( 'The payment has failed. This happens only if the payment was made from your customer\'s bank account.', 'super-forms' ),
				),
				'Pending'           => array(
					'label' => esc_html__( 'Pending', 'super-forms' ),
					'desc'  => esc_html__( 'The payment is pending.', 'super-forms' ),
				),
				'Refunded'          => array(
					'label' => esc_html__( 'Refunded', 'super-forms' ),
					'desc'  => esc_html__( 'You refunded the payment.', 'super-forms' ),
					// See 'pending_reason' for more information.
				),
				'Reversed'          => array(
					'label' => esc_html__( 'Reversed', 'super-forms' ),
					'desc'  => esc_html__( 'A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.', 'super-forms' ), // See pending_reason for more information.
					// See 'ReasonCode' for more information.
				),
				'Processed'         => array(
					'label' => esc_html__( 'Processed', 'super-forms' ),
					'desc'  => esc_html__( 'A payment has been accepted.', 'super-forms' ),
				),
				'Voided'            => array(
					'label' => esc_html__( 'Voided', 'super-forms' ),
					'desc'  => esc_html__( 'This authorization has been voided.', 'super-forms' ),
				),
			);
		}


		/**
		 * Adjust filter/search for transactions and subscriptions
		 *
		 * @param  string $current_screen
		 *
		 * @since       1.0.0
		 */
		public function after_screen( $current_screen ) {
			if ( $current_screen->id == 'edit-super_paypal_txn' ) {
				add_filter( 'posts_where', array( $this, 'custom_posts_where' ), 0, 2 );
				add_filter( 'posts_join', array( $this, 'custom_posts_join' ), 0, 2 );
				add_filter( 'posts_groupby', array( $this, 'custom_posts_groupby' ), 0, 2 );
				add_filter( 'get_edit_post_link', array( $this, 'edit_post_link' ), 99, 3 );
			}
		}
		public function edit_post_link( $link, $post_id, $context ) {
			if ( get_post_type() === 'super_paypal_txn' ) {
				return 'admin.php?page=super_paypal_txn&id=' . get_the_ID();
			}
			return $link;
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
		 * Add form filter dropdown
		 *
		 *  @since      1.0.0
		 */
		public static function filter_form_dropdown( $post_type ) {
			if ( $post_type == 'super_paypal_txn' ) {
				echo '<select name="super_form_filter">';
				$args  = array(
					'post_type'      => 'super_form',
					'posts_per_page' => -1,
				);
				$forms = get_posts( $args );
				if ( count( $forms ) == 0 ) {
					echo '<option value="0">' . esc_html__( 'No forms found', 'super-forms' ) . '</option>';
				} else {
					$super_form_filter = ( isset( $_GET['super_form_filter'] ) ? $_GET['super_form_filter'] : 0 );
					echo '<option value="0">' . esc_html__( 'All forms', 'super-forms' ) . '</option>';
					foreach ( $forms as $value ) {
						echo '<option value="' . esc_attr( $value->ID ) . '" ' . ( $value->ID == $super_form_filter ? 'selected="selected"' : '' ) . '>' . $value->post_title . '</option>';
					}
				}
				echo '</select>';
			}
		}

		/**
		 * Hook into the where query to filter custom meta data
		 *
		 *  @since      1.0.0
		 */
		public static function custom_posts_where( $where, $object ) {
			global $wpdb;
			$table      = $wpdb->prefix . 'posts';
			$table_meta = $wpdb->prefix . 'postmeta';
			$where      = '';
			if ( ( isset( $_GET['s'] ) ) && ( $_GET['s'] != '' ) ) {
				$s          = sanitize_text_field( $_GET['s'] );
				$where     .= 'AND (';
					$where .= "($table.post_title LIKE '%$s%') OR";
					$where .= "($table_meta.meta_key = '_super_txn_data' AND $table_meta.meta_value LIKE '%$s%')"; // @since 3.4.0 - custom entry status
				$where     .= ')';
			}
			if ( ( isset( $_GET['super_form_filter'] ) ) && ( absint( $_GET['super_form_filter'] ) != 0 ) ) {
				$super_form_filter = absint( $_GET['super_form_filter'] );
				$where            .= 'AND (';
					$where        .= "($table.post_parent = $super_form_filter)";
				$where            .= ')';
			}
			if ( ( isset( $_GET['post_status'] ) ) && ( $_GET['post_status'] != '' ) && ( $_GET['post_status'] != 'all' ) ) {
				$post_status = sanitize_text_field( $_GET['post_status'] );
				$where      .= 'AND (';
					$where  .= "($table.post_status = '$post_status')";
				$where      .= ')';
			} else {
				$where     .= 'AND (';
					$where .= "($table.post_status != 'trash')";
				$where     .= ')';
			}
			$where     .= 'AND (';
				$where .= "($table.post_type = 'super_paypal_txn')";
			$where     .= ')';
			return $where;
		}

		/**
		 * Hook into the join query to filter custom meta data
		 *
		 *  @since      1.0.0
		 */
		public static function custom_posts_join( $join, $object ) {
			if ( ( isset( $_GET['s'] ) ) && ( $_GET['s'] != '' ) ) {
				global $wpdb;
				$prefix      = $wpdb->prefix;
				$table_posts = $wpdb->prefix . 'posts';
				$table_meta  = $wpdb->prefix . 'postmeta';
				$join        = "INNER JOIN $table_meta ON $table_meta.post_id = $table_posts.ID";
			}
			return $join;
		}

		/**
		 * Hook into the groupby query to filter custom meta data
		 *
		 *  @since      1.0.0
		 */
		public static function custom_posts_groupby( $groupby, $object ) {
			if ( ( isset( $_GET['s'] ) ) && ( $_GET['s'] != '' ) ) {
				global $wpdb;
				$table   = $wpdb->prefix . 'posts';
				$groupby = "$table.ID";
			}
			return $groupby;
		}


		/**
		 * Save Post ID into session after inserting post with Front-end Posting feature
		 * This way we can add it to the paypal custom data and use it later to update the user status after payment is completed
		 *
		 *  @since      1.0.0
		 */
		// public function save_post_id($data) {
		// SUPER_Common::setClientData( array( 'name'=> 'super_forms_created_post_id', 'value'=>absint($data['post_id'] ) ) );
		// }

		/**
		 * Save User ID into session after creating user Register & Login feature
		 * This way we can add it to the paypal custom data and use it later to update the user status after payment is completed
		 *
		 *  @since      1.0.0
		 */
		// public function save_user_id($data) {
		// SUPER_Common::setClientData( array( 'name'=> 'paypal_user_id', 'value'=>absint($data['user_id'] ) ) );
		// }


		/**
		 * Display activation message for automatic updates
		 *
		 *  @since      1.0.0
		 */
		public function reset_paypal_counter( $current_screen ) {
			if ( $current_screen->post_type == 'super_paypal_txn' ) {
				update_option( 'super_paypal_txn_count', 0 );
			}
			if ( $current_screen->post_type == 'super_paypal_sub' ) {
				update_option( 'super_paypal_sub_count', 0 );
			}
		}


		/**
		 * Enqueue styles
		 *
		 *  @since      1.0.0
		 */
		public function backend_styles( $array ) {
			$assets_path               = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . 'assets/';
			$backend_path              = $assets_path . 'css/backend/';
			$array['super-paypal-txn'] = array(
				'src'     => $backend_path . 'paypal-txn.css',
				'deps'    => '',
				'version' => $this->version,
				'media'   => 'all',
				'screen'  => array(
					'edit-super_paypal_txn',
					'admin_page_super_paypal_txn',
					'edit-super_paypal_sub',
					'admin_page_super_paypal_sub',
				),
				'method'  => 'enqueue',
			);
			if ( isset( $array['font-awesome-v5.9'] ) ) {
				$array['font-awesome-v5.9']['screen'][] = 'edit-super_paypal_txn';
				$array['font-awesome-v5.9']['screen'][] = 'admin_page_super_paypal_txn';
				$array['font-awesome-v5.9']['screen'][] = 'edit-super_paypal_sub';
				$array['font-awesome-v5.9']['screen'][] = 'admin_page_super_paypal_sub';
			}
			return $array;
		}


		/**
		 * Change row actions
		 *
		 *  @since      1.0.0
		 */
		public static function remove_row_actions( $actions ) {
			if ( ( get_post_type() === 'super_paypal_txn' ) || ( get_post_type() === 'super_paypal_sub' ) ) {
				if ( isset( $actions['trash'] ) ) {
					$trash = $actions['trash'];
					unset( $actions['trash'] );
				}
				unset( $actions['inline hide-if-no-js'] );
				unset( $actions['view'] );
				unset( $actions['edit'] );
				$actions['view'] = '<a href="' . esc_url( 'admin.php?page=super_paypal_txn&id=' . get_the_ID() ) . '">' . esc_html__( 'View', 'super-forms' ) . '</a>';
				if ( get_post_type() === 'super_paypal_sub' ) {
					$actions['view'] = '<a href="' . esc_url( 'admin.php?page=super_paypal_sub&id=' . get_the_ID() ) . '">' . esc_html__( 'View', 'super-forms' ) . '</a>';
				}
				if ( isset( $trash ) ) {
					$actions['trash'] = $trash;
				}
			}
			return $actions;
		}


		/**
		 * Custom transaction columns
		 *
		 *  @since      1.0.0
		 */
		public static function super_paypal_txn_columns( $columns ) {

			$global_settings                         = SUPER_Common::get_global_settings();
			$GLOBALS['backend_contact_entry_status'] = SUPER_Settings::get_entry_statuses( $global_settings );

			foreach ( $columns as $k => $v ) {
				if ( ( $k != 'title' ) && ( $k != 'cb' ) ) {
					unset( $columns[ $k ] );
				}
			}
			$columns['title']             = 'Transaction ID'; // post_title
			$columns['pp_status']         = 'Payment status'; // payment_status
			$columns['pp_payer_email']    = 'E-mail'; // payer_email
			$columns['pp_invoice']        = 'Invoice'; // invoice
			$columns['pp_item']           = 'Quantity — Item'; // item_name + quantity
			$columns['pp_hidden_form_id'] = 'Based on Form'; // hidden_form_id
			$columns['date']              = 'Date'; // payment_date
			return $columns;
		}


		/**
		 * Custom subscriptions columns
		 *
		 *  @since      1.0.0
		 */
		public static function super_paypal_sub_columns( $columns ) {

			$global_settings                         = SUPER_Common::get_global_settings();
			$GLOBALS['backend_contact_entry_status'] = SUPER_Settings::get_entry_statuses( $global_settings );

			foreach ( $columns as $k => $v ) {
				if ( ( $k != 'title' ) && ( $k != 'cb' ) ) {
					unset( $columns[ $k ] );
				}
			}
			$columns['title']              = 'Subscription ID'; // post_title
			$columns['pp_status']          = 'Status'; // payment_status
			$columns['pp_payer_email']     = 'Name / E-mail'; // first_name + last_name / payer_email
			$columns['pp_invoice']         = 'Invoice'; // invoice
			$columns['pp_item']            = 'Recurring Payment'; // item_name + quantity
			$columns['pp_initial_payment'] = 'Trial Period'; // a1,t1,p1 / a2,t2,p2
			$columns['pp_trial_period']    = 'Trial Period 2'; // a1,t1,p1 / a2,t2,p2
			$columns['pp_hidden_form_id']  = 'Based on Form'; // hidden_form_id
			$columns['date']               = 'Date'; // payment_date
			return $columns;
		}

		public static function get_amount_per_cycle( $txn_data ) {
			if ( isset( $txn_data['amount_per_cycle'] ) ) {
				return $txn_data['amount_per_cycle'];
			}
			if ( isset( $txn_data['mc_amount3'] ) ) {
				return $txn_data['mc_amount3'];
			}
		}
		public static function get_currency_code( $txn_data ) {
			if ( isset( $txn_data['currency_code'] ) ) {
				return $txn_data['currency_code'];
			}
			if ( isset( $txn_data['mc_currency'] ) ) {
				return $txn_data['mc_currency'];
			}
		}
		public static function get_product_item_name( $txn_data ) {
			if ( isset( $txn_data['item_name'] ) ) {
				return $txn_data['item_name'];
			}
			if ( isset( $txn_data['product_name'] ) ) {
				return $txn_data['product_name'];
			}
		}
		public static function get_payment_cycle( $txn_data, $period = 3 ) {
			$payment_cycle = '';
			if ( isset( $txn_data['payment_cycle'] ) ) {
				$payment_cycle = $txn_data['payment_cycle'];
			}
			if ( isset( $txn_data[ 'period' . $period ] ) ) {
				$payment_cycle = $txn_data[ 'period' . $period ];
				$payment_cycle = explode( ' ', $payment_cycle );

				if ( $period > 2 ) {
					if ( $payment_cycle[0] > 1 ) {
						switch ( $payment_cycle[1] ) {
							case 'D':
								$payment_cycle = 'Every ' . $payment_cycle[0] . ' days';
								break;

							case 'W':
								$payment_cycle = 'Every ' . $payment_cycle[0] . ' weeks';
								break;

							case 'M':
								$payment_cycle = 'Every ' . $payment_cycle[0] . ' months';
								break;

							case 'Y':
								$payment_cycle = 'Every ' . $payment_cycle[0] . ' years';
								break;
						}
					} else {
						switch ( $payment_cycle[1] ) {
							case 'D':
								$payment_cycle = 'Daily';
								break;

							case 'W':
								$payment_cycle = 'Weekly';
								break;

							case 'M':
								$payment_cycle = 'Monthly';
								break;

							case 'Y':
								$payment_cycle = 'Yearly';
								break;
						}
					}
				} elseif ( $payment_cycle[0] > 1 ) {
					switch ( $payment_cycle[1] ) {
						case 'D':
							$payment_cycle = $payment_cycle[0] . ' days';
							break;

						case 'W':
							$payment_cycle = $payment_cycle[0] . ' weeks';
							break;

						case 'M':
							$payment_cycle = $payment_cycle[0] . ' months';
							break;

						case 'Y':
							$payment_cycle = $payment_cycle[0] . ' years';
							break;
					}
				} else {
					switch ( $payment_cycle[1] ) {
						case 'D':
							$payment_cycle = '1 day';
							break;

						case 'W':
							$payment_cycle = '1 week';
							break;

						case 'M':
							$payment_cycle = '1 month';
							break;

						case 'Y':
							$payment_cycle = '1 year';
							break;
					}
				}
			}
			return $payment_cycle;
		}
		public static function super_custom_columns( $column, $post_id ) {
			$txn_data = get_post_meta( $post_id, '_super_txn_data', true );
			$sfsi_id  = $txn_data['custom'];
			// $custom = explode( '|', $txn_data['custom'] );
			// $custom = array($sfsi_id);
			// $custom = array(
			// 0 absint($atts['post']['form_id']),
			// 1 $settings['paypal_payment_type'],
			// 2 $atts['entry_id'],
			// 3 get_current_user_id(),
			// 4 absint($post_id), // Used only if Front-end Posting is enabled to update the post status after successfull payment.
			// 5 absint($user_id) // Used only if Register & Login is enabled to update the user status after successfull payment.
			// );

			$form_id = wp_get_post_parent_id( $post_id );

			// Get currency code e.g: EUR
			$currency_code = self::get_currency_code( $txn_data );
			$symbol        = self::$currency_codes[ $currency_code ]['symbol'];

			// Get product/item name
			$product_name = self::get_product_item_name( $txn_data );

			// Get amount per cycle
			$amount_per_cycle = self::get_amount_per_cycle( $txn_data );

			switch ( $column ) {
				case 'pp_status':
					if ( ( $txn_data['txn_type'] == 'subscr_signup' ) || ( $txn_data['txn_type'] == 'subscr_modify' ) || ( $txn_data['txn_type'] == 'subscr_cancel' ) || ( $txn_data['txn_type'] == 'recurring_payment_suspended' ) ) {
						$entry_status      = 'Active';
						$entry_status_desc = '';
						if ( isset( $txn_data['profile_status'] ) ) {
							$entry_status      = $txn_data['profile_status'];
							$entry_status_desc = $entry_status;
						}
						if ( $txn_data['txn_type'] == 'recurring_payment_suspended' ) {
							$entry_status_desc = esc_html__( 'This profile has been suspended, and no further amounts will be collected.', 'super-forms' );
						}
						if ( $txn_data['txn_type'] == 'subscr_cancel' ) {
							$entry_status      = esc_html__( 'Canceled', 'super-forms' );
							$entry_status_desc = esc_html__( 'This recurring payment plan has been canceled and cannot be reactivated. No more recurring payments will be made.', 'super-forms' );
						}
						echo '<span title="' . esc_attr( $entry_status_desc ) . '" class="super-txn-status super-txn-status-' . strtolower( $entry_status ) . '">' . esc_html( $entry_status ) . '</span>';
					} else {
						$entry_status = $txn_data['payment_status'];
						$value        = self::$paypal_payment_statuses[ $entry_status ];
						$statuses     = $GLOBALS['backend_contact_entry_status'];
						if ( ( isset( $statuses[ $entry_status ] ) ) && ( $entry_status != '' ) ) {
							echo '<span title="' . esc_attr( $value['desc'] ) . '" class="super-txn-status super-txn-status-' . strtolower( $entry_status ) . '" style="color:' . $statuses[ $entry_status ]['color'] . ';background-color:' . $statuses[ $entry_status ]['bg_color'] . '">' . $value['label'] . '</span>';
						} else {
							echo '<span title="' . esc_attr( $value['desc'] ) . '" class="super-txn-status super-txn-status-' . strtolower( $entry_status ) . '">' . esc_html( $value['label'] ) . '</span>';
						}
					}
					break;
				case 'pp_payer_email':
					$tooltip = '';
					if ( $txn_data['payer_status'] == 'verified' ) {
						$tooltip = '<i title="' . esc_attr__( 'Customer has a verified PayPal account', 'super-forms' ) . '" class="super-paypal-txn-verified" aria-hidden="true">✅</i>';
					}
					if ( $txn_data['payer_status'] == 'unverified' ) {
						$tooltip = '<i title="' . esc_attr__( 'Customer has an unverified PayPal account', 'super-forms' ) . '" class="super-paypal-txn-unverified" aria-hidden="true">❌</i>';
					}
					echo '<span class="pp-name-email">';
					echo $tooltip;
					echo '<strong>' . $txn_data['first_name'] . ' ' . $txn_data['last_name'] . '</strong><br />';
					echo $txn_data['payer_email'];
					echo '</span>';
					break;
				case 'pp_invoice':
					echo ( isset( $txn_data['invoice'] ) ? $txn_data['invoice'] : '' );
					break;
				case 'pp_item':
					if ( $txn_data['txn_type'] == 'cart' ) {
						$i = 1;
						while ( isset( $txn_data[ 'item_name' . $i ] ) ) {
							echo $txn_data[ 'quantity' . $i ] . 'x — <strong>' . $txn_data[ 'item_name' . $i ] . '</strong><br />';
							++$i;
						}
					} elseif ( ( $txn_data['txn_type'] == 'subscr_payment' ) || ( $txn_data['txn_type'] == 'subscr_signup' ) || ( $txn_data['txn_type'] == 'subscr_modify' ) || ( $txn_data['txn_type'] == 'subscr_cancel' ) || ( $txn_data['txn_type'] == 'recurring_payment_suspended' ) ) {
						if ( $txn_data['txn_type'] == 'subscr_payment' ) {
							echo '1x — <strong>' . $txn_data['item_name'] . '</strong><br />';
							echo '(' . $symbol . number_format_i18n( $txn_data['mc_gross'], 2 ) . ' ' . $currency_code . ')';
						} else {
							echo '<strong>' . $product_name . '</strong><br />';
							// Get payment cycle
							$payment_cycle = self::get_payment_cycle( $txn_data, 3 );
							echo '(' . $payment_cycle . ': ' . $symbol . number_format_i18n( $amount_per_cycle, 2 ) . ' ' . $currency_code . ')';
						}
					} else {
						echo $txn_data['quantity'] . 'x — <strong>' . $txn_data['item_name'] . '</strong><br />';
						echo '(' . $symbol . number_format_i18n( $txn_data['mc_gross'], 2 ) . ' ' . $currency_code . ')';
					}
					break;
				case 'pp_initial_payment':
					if ( isset( $txn_data['mc_amount1'] ) ) {
						// Get payment cycle
						$payment_cycle = self::get_payment_cycle( $txn_data, 1 );
						echo '(' . $payment_cycle . ': ' . $symbol . number_format_i18n( $txn_data['mc_amount1'], 2 ) . ' ' . $currency_code . ')';
					}
					break;
				case 'pp_trial_period':
					if ( isset( $txn_data['mc_amount2'] ) ) {
						// Get payment cycle
						$payment_cycle = self::get_payment_cycle( $txn_data, 2 );
						echo '(' . $payment_cycle . ': ' . $symbol . number_format_i18n( $txn_data['mc_amount2'], 2 ) . ' ' . $currency_code . ')';
					}
					break;
				case 'pp_hidden_form_id':
					if ( $form_id == 0 ) {
						echo esc_html__( 'Unknown', 'super-forms' );
					} else {
						$form = get_post( $form_id );
						if ( isset( $form->post_title ) ) {
							echo '<a href="' . ( 'admin.php?page=super_create_form&id=' . absint( $form->ID ) ) . '">' . esc_html( $form->post_title ) . '</a>';
						} else {
							echo esc_html__( 'Unknown', 'super-forms' );
						}
					}
					break;
			}
		}


		/**
		 * Register post statuses (payment statuses) for paypal transactions
		 *
		 *  @since      1.0.0
		 */
		public static function custom_paypal_txn_status() {
			foreach ( self::$paypal_payment_statuses as $k => $v ) {
				register_post_status(
					$k,
					array(
						'label'                     => $v['label'],
						'public'                    => true,
						'exclude_from_search'       => false,
						'show_in_admin_all_list'    => true,
						'show_in_admin_status_list' => true,
						'label_count'               => _n_noop( $v['label'] . ' <span class="count">(%s)</span>', $v['label'] . ' <span class="count">(%s)</span>' ),
					)
				);
			}
		}
		public static function append_paypal_txn_status_list() {
			global $post;
			$complete = '';
			$label    = '';
			if ( $post->post_type == 'super_paypal_txn' ) {
				foreach ( self::$paypal_payment_statuses as $k => $v ) {
					if ( $post->post_status == $k ) {
						$complete = ' selected="selected"';
						$label    = '<span id="post-status-display"> ' . esc_html( $v['label'] ) . '</span>';
					}
					echo '<script>
					jQuery(document).ready(function($){
					$("select#post_status").append("<option value="archive" ' . $complete . '>' . esc_html__( 'Archive', 'super-forms' ) . '</option>");
					$(".misc-pub-section label").append("' . $label . '");
					});
					</script>';
				}
			}
		}


		/**
		 *  Register post types
		 *
		 *  @since    1.0.0
		 */
		public static function register_post_types() {
			if ( ! post_type_exists( 'super_paypal_txn' ) ) {
				register_post_type(
					'super_paypal_txn',
					apply_filters(
						'super_register_post_type_super_paypal_txn',
						array(
							'label'               => 'PayPal Transactions',
							'description'         => '',
							'public'              => true,
							'show_ui'             => true,
							'show_in_menu'        => false,
							'capability_type'     => 'post',
							'map_meta_cap'        => true,
							'hierarchical'        => false,
							'rewrite'             => array(
								'slug'       => 'super_paypal_txn',
								'with_front' => true,
							),
							'exclude_from_search' => true, // make sure to exclude from default search
							'query_var'           => true,
							'supports'            => array(),
							'capabilities'        => array(
								'create_posts' => false, // Removes support for the "Add New" function
							),
							'labels'              => array(
								'name'               => 'PayPal Transactions',
								'singular_name'      => 'PayPal Transaction',
								'menu_name'          => 'PayPal Transactions',
								'add_new'            => 'Add Transaction',
								'add_new_item'       => 'Add New Transaction',
								'edit'               => 'Edit',
								'edit_item'          => 'Edit Transaction',
								'new_item'           => 'New Transaction',
								'view'               => 'View Transaction',
								'view_item'          => 'View Transaction',
								'search_items'       => 'Search Transactions',
								'not_found'          => 'No Transactions Found',
								'not_found_in_trash' => 'No Transactions Found in Trash',
								'parent'             => 'Parent Transaction',
							),
						)
					)
				);
			}
			if ( ! post_type_exists( 'super_paypal_sub' ) ) {
				register_post_type(
					'super_paypal_sub',
					apply_filters(
						'super_register_post_type_super_paypal_sub',
						array(
							'label'               => 'PayPal Subscriptions',
							'description'         => '',
							'public'              => true,
							'show_ui'             => true,
							'show_in_menu'        => false,
							'capability_type'     => 'post',
							'map_meta_cap'        => true,
							'hierarchical'        => false,
							'rewrite'             => array(
								'slug'       => 'super_paypal_sub',
								'with_front' => true,
							),
							'exclude_from_search' => true, // make sure to exclude from default search
							'query_var'           => true,
							'supports'            => array(),
							'capabilities'        => array(
								'create_posts' => false, // Removes support for the "Add New" function
							),
							'labels'              => array(
								'name'               => 'PayPal Subscriptions',
								'singular_name'      => 'PayPal Subscription',
								'menu_name'          => 'PayPal Subscriptions',
								'add_new'            => 'Add Subscription',
								'add_new_item'       => 'Add New Subscription',
								'edit'               => 'Edit',
								'edit_item'          => 'Edit Subscription',
								'new_item'           => 'New Subscription',
								'view'               => 'View Subscription',
								'view_item'          => 'View Subscription',
								'search_items'       => 'Search Subscriptions',
								'not_found'          => 'No Subscriptions Found',
								'not_found_in_trash' => 'No Subscriptions Found in Trash',
								'parent'             => 'Parent Subscription',
							),
						)
					)
				);
			}
		}


		/**
		 *  Add menu items
		 *
		 *  @since    1.0.0
		 */
		public static function register_menu() {
			global $menu, $submenu;
			$styles = 'background-image:url(' . plugin_dir_url( __FILE__ ) . 'assets/images/paypal.png);width:22px;height:22px;display:inline-block;background-position:-3px -3px;background-repeat:no-repeat;margin:0px 0px -9px 0px;';

			// Transactions menu
			$count = get_option( 'super_paypal_txn_count', 0 );
			if ( $count > 0 ) {
				$count = ' <span class="update-plugins"><span class="plugin-count">' . $count . '</span></span>';
			} else {
				$count = '';
			}
			add_submenu_page(
				'super_forms',
				esc_html__( 'PayPal Transactions', 'super-forms' ),
				'<span class="super-pp-icon" style="' . $styles . '"></span>' . esc_html__( 'Transactions', 'super-forms' ) . $count,
				'manage_options',
				'edit.php?post_type=super_paypal_txn'
			);
			add_submenu_page(
				'null_super_forms', // in later versions of PHP/WordPress you can't set this to `null`
				esc_html__( 'View PayPal transaction', 'super-forms' ),
				esc_html__( 'View PayPal transaction', 'super-forms' ),
				'manage_options',
				'super_paypal_txn',
				'SUPER_PayPal::paypal_transaction'
			);

			// Subscriptions menu
			$count = get_option( 'super_paypal_sub_count', 0 );
			if ( $count > 0 ) {
				$count = ' <span class="update-plugins"><span class="plugin-count">' . $count . '</span></span>';
			} else {
				$count = '';
			}
			add_submenu_page(
				'super_forms',
				esc_html__( 'PayPal Subscriptions', 'super-forms' ),
				'<span class="super-pp-icon" style="' . $styles . '"></span>' . esc_html__( 'Subscriptions', 'super-forms' ) . $count,
				'manage_options',
				'edit.php?post_type=super_paypal_sub'
			);
			add_submenu_page(
				'null_super_forms', // in later versions of PHP/WordPress you can't set this to `null`
				esc_html__( 'View PayPal subscription', 'super-forms' ),
				esc_html__( 'View PayPal subscription', 'super-forms' ),
				'manage_options',
				'super_paypal_sub',
				'SUPER_PayPal::paypal_subscription'
			);
		}


		/**
		 * Handles the output for the view paypal transaction page in admin
		 */
		public static function paypal_transaction() {
			$id = $_GET['id'];
			if ( ( false === get_post_status( $id ) ) && ( get_post_type( $id ) != 'super_paypal_txn' ) ) {
				// The post does not exist
				echo 'This transaction does not exist.';
			} else {
				// The post exists
				$date     = get_the_date( false, $id );
				$time     = get_the_time( false, $id );
				$txn_data = get_post_meta( $id, '_super_txn_data', true );
				$custom   = explode( '|', $txn_data['custom'] );
				if ( count( $custom ) > 1 ) {
					$entry_id     = ( ! empty( $custom[2] ) ? $custom[2] : 0 );
					$created_post = ( ! empty( $custom[4] ) ? $custom[4] : 0 );
					// $custom = array(
					// 0 absint($atts['post']['form_id']),
					// 1 $settings['paypal_payment_type'],
					// 2 $atts['entry_id'],
					// 3 get_current_user_id(),
					// 4 absint($post_id), // Used only if Front-end Posting is enabled to update the post status after successfull payment.
					// 5 absint($user_id) // Used only if Register & Login is enabled to update the user status after successfull payment.
					// );
				} else {
					// $sfsi_id = $txn_data['custom'];
					// Since v6.4.015 and above, because
					$entry_id     = get_post_meta( $id, '_super_contact_entry_id', true );
					$created_post = get_post_meta( $id, '_super_connected_post_id', true );
				}

				// Get parent (form)
				$form_id = wp_get_post_parent_id( $id );
				// Get the author ID
				$author_id = get_post_field( 'post_author', $id );
				?>
				<script>
					jQuery('.toplevel_page_super_forms').removeClass('wp-not-current-submenu').addClass('wp-menu-open wp-has-current-submenu');
					jQuery('.toplevel_page_super_forms').find('a[href$="super_paypal_txn"]').parents('li:eq(0)').addClass('current');
				</script>
				<div class="wrap">
					<div id="poststuff">
						<div id="post-body" class="metabox-holder columns-2">
							<div id="postbox-container-1" class="postbox-container">
								<div id="side-sortables" class="meta-box-sortables ui-sortable">
									<div id="submitdiv" class="postbox ">
										<div class="handlediv" title="">
											<br>
										</div>
										<h3 class="hndle ui-sortable-handle">
											<span><?php echo esc_html__( 'Transaction Details', 'super-forms' ); ?>:</span>
										</h3>
										<div class="inside">
											<div class="submitbox" id="submitpost">
												<div id="minor-publishing">
													<div class="misc-pub-section">
														<?php
														$currency_code = self::get_currency_code( $txn_data );
														$mc_gross      = number_format_i18n( $txn_data['mc_gross'], 2 ) . ' ' . $currency_code;
														?>
														<span><?php echo esc_html__( 'Gross amount', 'super-forms' ) . ':'; ?> <strong><?php echo $mc_gross; ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Transaction ID', 'super-forms' ) . ':'; ?> <strong><?php echo get_the_title( $id ); ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Payment status', 'super-forms' ) . ':'; ?> <strong><?php echo $txn_data['payment_status']; ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Payer E-mail', 'super-forms' ) . ':'; ?> <strong><?php echo $txn_data['payer_email']; ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Payment type', 'super-forms' ) . ':'; ?> <strong><?php echo $txn_data['payment_type']; ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Submitted', 'super-forms' ) . ':'; ?> <strong><?php echo $date . ' @ ' . $time; ?></strong></span>
													</div>

													<?php
													// Check if the user exists
													if ( $author_id && get_userdata( $author_id ) ) {
														// Get the author's user login
														$user_login = get_the_author_meta( 'user_login', $author_id );
														$edit_link  = get_edit_user_link( $author_id );
														?>
														<div class="misc-pub-section">
															<?php echo '<span>' . esc_html__( 'Author', 'super-forms' ) . ':'; ?> <?php echo '<a href="' . esc_url( $edit_link ) . '"><strong>' . $user_login . '</strong></a></span>'; ?>
														</div>
														<?php
													}
													// Query to retrieve the contact entry post associated with this PayPal order
													if ( ! empty( $entry_id ) ) {
														echo '<div class="misc-pub-section">';
															echo '<span>' . esc_html__( 'Contact Entry', 'super-forms' ) . ': <a href="' . esc_url( 'admin.php?page=super_contact_entry&id=' . $entry_id ) . '"><strong>' . get_the_title( $entry_id ) . '</strong></a></span>';
														echo '</div>';
													}
													// Get subscription
													$sub_id = 0;
													if ( isset( $txn_data['subscr_id'] ) ) {
														$sub_id = sanitize_text_field( $txn_data['subscr_id'] );
													}
													if ( isset( $txn_data['recurring_payment_id'] ) ) {
														$sub_id = sanitize_text_field( $txn_data['recurring_payment_id'] );
													}
													global $wpdb;
													$post_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta AS meta INNER JOIN $wpdb->posts AS post ON post.id = meta.post_id WHERE post.post_type = 'super_paypal_sub' AND meta_key = '_super_sub_id' AND meta_value = '$sub_id'" );
													if ( absint( $post_id ) != 0 ) {
														echo '<div class="misc-pub-section">';
															echo '<span>' . esc_html__( 'Based on subscription', 'super-forms' ) . ': <a href="' . esc_url( 'admin.php?page=super_paypal_sub&id=' . $post_id ) . '"><strong>' . $sub_id . '</strong></a></span>';
														echo '</div>';
													}
													// Created post (front-end posting)
													if ( ! empty( $created_post ) ) {
														?>
														<div class="misc-pub-section">
															<?php echo '<span>' . esc_html__( 'Created Post', 'super-forms' ) . ':'; ?> <?php echo '<a href="' . esc_url( get_edit_post_link( $created_post ) ) . '"><strong>' . get_the_title( $created_post ) . '</strong></a></span>'; ?>
														</div>
														<?php
													}
													// Check if the post exists
													if ( $form_id && get_post( $form_id ) ) {
														?>
														<div class="misc-pub-section">
															<?php echo '<span>' . esc_html__( 'Based on Form', 'super-forms' ) . ':'; ?> <?php echo '<a href="' . esc_url( 'admin.php?page=super_create_form&id=' . $form_id ) . '"><strong>' . get_the_title( $form_id ) . '</strong></a></span>'; ?>
														</div>
														<?php
													}
													?>

													<div class="clear"></div>
												</div>

												<div id="major-publishing-actions">
													<div id="delete-action">
														<a class="submitdelete super-delete-contact-entry" data-contact-entry="<?php echo absint( $id ); ?>" href="#"><?php echo esc_html__( 'Move to Trash', 'super-forms' ); ?></a>
													</div>
													<div id="publishing-action">
														<span class="spinner"></span>
														<input name="print" type="submit" class="super-print-contact-entry button button-large" value="<?php echo esc_html__( 'Print', 'super-forms' ); ?>">
													</div>
													<div class="clear"></div>
												</div>
											</div>

										</div>
									</div>
								</div>
							</div>
							
							<div id="postbox-container-2" class="postbox-container">
								<?php

								// Get currency code e.g: EUR
								$currency_code = self::get_currency_code( $txn_data );
								$mc_gross      = number_format_i18n( $txn_data['mc_gross'], 2 ) . ' ' . $currency_code;

								if ( $txn_data['txn_type'] != 'subscr_payment' ) {
									?>
									<div id="normal-sortables" class="meta-box-sortables ui-sortable">
										<div id="super-contact-entry-data" class="postbox ">
											<div class="handlediv" title="">
												<br>
											</div>
											<h3 class="hndle ui-sortable-handle">
												<span><?php echo esc_html__( 'Order details', 'super-forms' ); ?>:</span>
											</h3>
											<div class="inside">
												<?php
												echo '<table style="width:100%">';
													echo '<tr><th align="left">' . esc_html__( 'Item name', 'super-forms' ) . '</th><th align="right">' . esc_html__( 'Quantity', 'super-forms' ) . '</th><th align="right">' . esc_html__( 'Price', 'super-forms' ) . '</th><th align="right">' . esc_html__( 'Subtotal', 'super-forms' ) . '</th></tr>';
												if ( isset( $txn_data['item_name'] ) ) {
													echo '<tr>';
													echo '<td align="left">' . $txn_data['item_name'] . '</td>';
													echo '<td align="right">1</td>';
													echo '<td align="right">' . number_format_i18n( $txn_data['mc_gross'], 2 ) . ' ' . $currency_code . '</td>';
													echo '<td align="right">' . number_format_i18n( $txn_data['mc_gross'], 2 ) . ' ' . $currency_code . '</td>';
													echo '</tr>';
												} else {
													$i = 1;
													while ( isset( $txn_data[ 'item_name' . $i ] ) ) {
														echo '<tr>';
														echo '<td align="left">' . $txn_data[ 'item_name' . $i ] . '</td>';
														echo '<td align="right">' . $txn_data[ 'quantity' . $i ] . '</td>';
														echo '<td align="right">' . number_format_i18n( ( $txn_data[ 'mc_gross_' . $i ] / $txn_data[ 'quantity' . $i ] ), 2 ) . ' ' . $currency_code . '</td>';
														echo '<td align="right">' . number_format_i18n( $txn_data[ 'mc_gross_' . $i ], 2 ) . ' ' . $currency_code . '</td>';
														echo '</tr>';
														++$i;
													}
												}
													echo '<tr><th colspan="3" align="right">' . esc_html__( 'Purchase total', 'super-forms' ) . '</th><td align="right">' . $mc_gross . '</td></tr>';
												echo '</table>';
												?>
											</div>
										</div>
									</div>
									<?php
								}
								?>

								<div id="normal-sortables" class="meta-box-sortables ui-sortable">
									<div id="super-contact-entry-data" class="postbox ">
										<div class="handlediv" title="">
											<br>
										</div>
										<h3 class="hndle ui-sortable-handle">
											<span><?php echo esc_html__( 'Payment details', 'super-forms' ); ?>:</span>
										</h3>
										<div class="inside">
											<?php
											if ( ! empty( $txn_data['address_country_code'] ) && $txn_data['address_country_code'] == 'US' ) {
												$located = 'inside';
											} else {
												$located = 'outside';
											}
											if ( ! empty( $txn_data['payer_status'] ) && $txn_data['payer_status'] == 'verified' ) {
												$verified = '';
												$color    = 'green';
											} else {
												$verified = 'NOT ';
												$color    = 'red';
											}
											$verified_text  = $txn_data['first_name'] . ' ' . $txn_data['last_name'] . '<br />';
											$verified_text .= sprintf( esc_html__( 'The sender of this payment has %1$sverified their account and is located %2$s the US.', 'super-forms' ), '<strong style="color:' . $color . ';">' . $verified, $located ) . '</strong><br />';
											$verified_text .= $txn_data['payer_email'];
											if ( $txn_data['txn_type'] == 'subscr_payment' ) {
												echo '<table>';
												echo '<tr><th align="left">' . esc_html__( 'Gross amount', 'super-forms' ) . '</th><td align="right">' . $mc_gross . '</td></tr>';
												if ( empty( $txn_data['mc_fee'] ) ) {
													$txn_data['mc_fee'] = 0;
												}
												echo '<tr><th align="left">' . esc_html__( 'PayPal fee', 'super-forms' ) . '</th><td align="right">' . number_format_i18n( $txn_data['mc_fee'], 2 ) . ' ' . $currency_code . '</td></tr>';
												echo '<tr><th align="left">' . esc_html__( 'Net amount', 'super-forms' ) . '</th><td align="right">' . number_format_i18n( ( $txn_data['mc_gross'] - $txn_data['mc_fee'] ), 2 ) . ' ' . $currency_code . '</td></tr>';
												echo '</table>';
												echo '<table>';
												echo '<tr><th align="left">' . esc_html__( 'Recurring Payment ID', 'super-forms' ) . '</th><td align="left">' . $txn_data['subscr_id'] . '</td></tr>';
												echo '<tr><th align="left">' . esc_html__( 'Reason', 'super-forms' ) . '</th><td align="left">' . esc_html__( 'Recurring', 'super-forms' ) . '</td></tr>';
												echo '<tr>';
													echo '<th align="left" valign="top">' . esc_html__( 'Paid by', 'super-forms' ) . '</th>';
													echo '<td>';
														echo $verified_text;
													echo '</td>';
												echo '</tr>';
												echo '<tr><th align="left">' . esc_html__( 'Memo', 'super-forms' ) . '</th><td align="left">' . $txn_data['item_name'] . '</td></tr>';
												echo '</table>';
											} else {
												echo '<table>';
												echo '<tr><th align="right">' . esc_html__( 'Purchase total', 'super-forms' ) . '</th><td align="right">' . $mc_gross . '</td></tr>';
												echo '<tr><th align="right">' . esc_html__( 'Sales tax', 'super-forms' ) . '</th><td align="right">' . ( isset( $txn_data['tax'] ) ? number_format_i18n( $txn_data['tax'], 2 ) : number_format_i18n( 0, 2 ) ) . ' ' . $currency_code . '</td></tr>';
												echo '<tr><th align="right">' . esc_html__( 'Shipping amount', 'super-forms' ) . '</th><td align="right">' . ( isset( $txn_data['mc_shipping'] ) ? number_format_i18n( $txn_data['mc_shipping'], 2 ) : number_format_i18n( 0, 2 ) ) . ' ' . $currency_code . '</td></tr>';
												echo '<tr><th align="right">' . esc_html__( 'Handling amount', 'super-forms' ) . '</th><td align="right">' . ( isset( $txn_data['mc_handling'] ) ? number_format_i18n( $txn_data['mc_handling'], 2 ) : number_format_i18n( 0, 2 ) ) . ' ' . $currency_code . '</td></tr>';
												echo '<tr><th align="right">' . esc_html__( 'Insurance', 'super-forms' ) . '</th><td align="right">' . ( isset( $txn_data['insurance_amount'] ) ? number_format_i18n( $txn_data['insurance_amount'], 2 ) : number_format_i18n( 0, 2 ) ) . ' ' . $currency_code . '</td></tr>';
												echo '<tr><th align="right">' . esc_html__( 'Gross amount', 'super-forms' ) . '</th><td align="right">' . $mc_gross . '</td></tr>';
												echo '<tr><th align="right">' . esc_html__( 'PayPal fee', 'super-forms' ) . '</th><td align="right">' . number_format_i18n( $txn_data['mc_fee'], 2 ) . ' ' . $currency_code . '</td></tr>';
												echo '<tr><th align="right">' . esc_html__( 'Net amount', 'super-forms' ) . '</th><td align="right">' . number_format_i18n( ( $txn_data['mc_gross'] - $txn_data['mc_fee'] ), 2 ) . ' ' . $currency_code . '</td></tr>';
												if ( ( isset( $txn_data['invoice'] ) ) && ( $txn_data['invoice'] != '' ) ) {
													echo '<tr><th>' . esc_html__( 'Invoice ID', 'super-forms' ) . '</th><td>' . $txn_data['invoice'] . '</td></tr>';
												}
												echo '</table>';
												echo '<table>';
												echo '<tr>';
													echo '<th valign="top">' . esc_html__( 'Paid by', 'super-forms' ) . '</th>';
													echo '<td>';
														echo $verified_text;
													echo '</td>';
												echo '</tr>';
												echo '</table>';
											}
											?>
										</div>
									</div>
								</div>

								<div id="normal-sortables" class="meta-box-sortables ui-sortable">
									<div id="super-contact-entry-data" class="postbox ">
										<div class="handlediv" title="">
											<br>
										</div>
										<h3 class="hndle ui-sortable-handle">
											<span><?php echo esc_html__( 'Address', 'super-forms' ); ?>:</span>
										</h3>
										<div class="inside">
											<?php
											echo '<table>';
											if ( ! empty( $txn_data['address_name'] ) ) {
												echo '<tr><th align="left">' . esc_html__( 'Name', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_name'] . '</td></tr>';
											}
											if ( ! empty( $txn_data['address_street'] ) ) {
												echo '<tr><th align="left">' . esc_html__( 'Street', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_street'] . '</td></tr>';
											}
											if ( ! empty( $txn_data['address_zip'] ) ) {
												echo '<tr><th align="left">' . esc_html__( 'Zipcode', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_zip'] . '</td></tr>';
											}
											if ( ! empty( $txn_data['address_city'] ) ) {
												echo '<tr><th align="left">' . esc_html__( 'City', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_city'] . '</td></tr>';
											}
											if ( ! empty( $txn_data['address_state'] ) ) {
												echo '<tr><th align="left">' . esc_html__( 'State', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_state'] . '</td></tr>';
											}
											if ( ! empty( $txn_data['address_country'] ) ) {
												echo '<tr><th align="left">' . esc_html__( 'Country', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_country'] . ' (' . $txn_data['address_country_code'] . ')</td></tr>';
											}
											if ( ! empty( $txn_data['address_status'] ) ) {
												echo '<tr><th align="left">' . esc_html__( 'Address status', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_status'] . '</td></tr>';
											}
											echo '</table>';
											?>
										</div>
									</div>
								</div>

								<div id="normal-sortables" class="meta-box-sortables ui-sortable">
									<div id="super-contact-entry-data" class="postbox ">
										<div class="handlediv" title="">
											<br>
										</div>
										<h3 class="hndle ui-sortable-handle">
											<span><?php echo esc_html__( 'Raw Transaction Data', 'super-forms' ); ?>:</span>
										</h3>
										<div class="inside">
											<?php
											echo '<table>';
											foreach ( $txn_data as $k => $v ) {
												echo '<tr><th align="right">' . $k . '</th><td>' . $v . '</td></tr>';
											}
												echo apply_filters(
													'super_after_paypal_txn_data_filter',
													'',
													array(
														'paypal_txn_id' => $_GET['id'],
														'txn_data' => $txn_data,
													)
												);
											echo '</table>';
											?>
										</div>
									</div>
								</div>

								<div id="advanced-sortables" class="meta-box-sortables ui-sortable"></div>



							</div>
						</div>
						<!-- /post-body -->
						<br class="clear">
					</div>
				<?php
			}
		}


		/**
		 * Handles the output for the view paypal subscription page in admin
		 */
		public static function paypal_subscription() {
			$id = $_GET['id'];
			if ( ( false === get_post_status( $id ) ) && ( get_post_type( $id ) != 'super_paypal_sub' ) ) {
				// The post does not exist
				echo 'This subscription does not exist.';
			} else {
				$date     = get_the_date( false, $id );
				$time     = get_the_time( false, $id );
				$txn_data = get_post_meta( $id, '_super_txn_data', true );
				// $sfsi_id = $txn_data['custom'];
				// $custom = explode( '|', $txn_data['custom'] );
				// $custom = array($sfsi_id);
				// $custom = array(
				// 0 absint($atts['post']['form_id']),
				// 1 $settings['paypal_payment_type'],
				// 2 $atts['entry_id'],
				// 3 get_current_user_id(),
				// 4 absint($post_id), // Used only if Front-end Posting is enabled to update the post status after successfull payment.
				// 5 absint($user_id) // Used only if Register & Login is enabled to update the user status after successfull payment.
				// );

				// Get the parent ID of the post
				$parent_id = wp_get_post_parent_id( $id );
				?>
				<script>
					jQuery('.toplevel_page_super_forms').removeClass('wp-not-current-submenu').addClass('wp-menu-open wp-has-current-submenu');
					jQuery('.toplevel_page_super_forms').find('a[href$="super_paypal_sub"]').parents('li:eq(0)').addClass('current');
				</script>
				<div class="wrap">
					<div id="poststuff">
						<div id="post-body" class="metabox-holder columns-2">
							<div id="postbox-container-1" class="postbox-container">
								<div id="side-sortables" class="meta-box-sortables ui-sortable">
									<div id="submitdiv" class="postbox ">
										<div class="handlediv" title="">
											<br>
										</div>
										<h3 class="hndle ui-sortable-handle">
											<span><?php echo esc_html__( 'Transaction Details', 'super-forms' ); ?>:</span>
										</h3>
										<div class="inside">
											<div class="submitbox" id="submitpost">
												<div id="minor-publishing">
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Transaction ID', 'super-forms' ) . ':'; ?> <strong><?php echo get_the_title( $id ); ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Status', 'super-forms' ) . ':'; ?> <strong><?php echo ( isset( $txn_data['profile_status'] ) ? $txn_data['profile_status'] : esc_html__( 'Active', 'super-forms' ) ); ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Payer E-mail', 'super-forms' ) . ':'; ?> <strong><?php echo $txn_data['payer_email']; ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Payment type', 'super-forms' ) . ':'; ?> <strong><?php echo esc_html__( 'Subscription', 'super-forms' ); ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Submitted', 'super-forms' ) . ':'; ?> <strong><?php echo $date . ' @ ' . $time; ?></strong></span>
													</div>
													<div class="misc-pub-section">
														<span><?php echo esc_html__( 'Based on Form', 'super-forms' ) . ':'; ?> <strong><?php echo '<a href="' . esc_url( 'admin.php?page=super_create_form&id=' . $parent_id ) . '">' . get_the_title( $parent_id ) . '</a>'; ?></strong></span>
													</div>

													<div class="clear"></div>
												</div>

												<div id="major-publishing-actions">
													<div id="delete-action">
														<a class="submitdelete super-delete-contact-entry" data-contact-entry="<?php echo absint( $id ); ?>" href="#"><?php echo esc_html__( 'Move to Trash', 'super-forms' ); ?></a>
													</div>
													<div id="publishing-action">
														<span class="spinner"></span>
														<input name="print" type="submit" class="super-print-contact-entry button button-large" value="<?php echo esc_html__( 'Print', 'super-forms' ); ?>">
													</div>
													<div class="clear"></div>
												</div>
											</div>

										</div>
									</div>
								</div>
							</div>
							
							<div id="postbox-container-2" class="postbox-container">
								<div id="normal-sortables" class="meta-box-sortables ui-sortable">
									<div id="super-contact-entry-data" class="postbox ">
										<div class="handlediv" title="">
											<br>
										</div>
										<h3 class="hndle ui-sortable-handle">
											<span><?php echo esc_html__( 'Raw Transaction Data', 'super-forms' ); ?>:</span>
										</h3>
										<div class="inside">
											<?php
											echo '<table>';
											foreach ( $txn_data as $k => $v ) {
												echo '<tr><th align="right">' . $k . '</th><td>' . $v . '</td></tr>';
											}
												echo apply_filters(
													'super_after_paypal_txn_data_filter',
													'',
													array(
														'paypal_txn_id' => $_GET['id'],
														'txn_data' => $txn_data,
													)
												);
											echo '</table>';
											?>
										</div>
									</div>
								</div>
								<div id="advanced-sortables" class="meta-box-sortables ui-sortable"></div>
							</div>
						</div>
						<!-- /post-body -->
						<br class="clear">
					</div>
				<?php
			}
		}



		/**
		 * PayPal IPN
		 *
		 * @since       1.0.0
		 */
		public function paypal_ipn() {

			if ( ( isset( $_GET['page'] ) ) && ( $_GET['page'] == 'super_paypal_ipn' ) ) {
				error_log( 'Super Forms: handling incoming Paypal IPN' );

				// Only continue for transactions that contain 'payment_status'
				if ( empty( $_POST['payment_status'] ) && $_POST['txn_type'] !== 'subscr_signup' ) {
					error_log( 'Super Forms: Paypal IPN did not contain `payment_status` and is not of type `subscr_signup`, do nothing' );
					error_log( $_POST['txn_type'] );
					die();
				}

				// txn_type options are:
				// subscr_signup
				// subscr_cancel
				// subscr_modify
				// subscr_payment
				// subscr_failed
				// subscr_eot

				// When the subscription has expired due to cancelation or expiration (term has ended) we don't have to do anything other then notifying paypal that we received the IPN message.
				// The subscription has expired, either because the subscriber cancelled it or it has a fixed term (implying a fixed number of payments) and it has now expired with no further payments being due.
				if ( ( isset( $_POST['txn_type'] ) ) && ( $_POST['txn_type'] == 'subscr_eot' ) ) {
					error_log( 'Super Forms: Paypal IPN subscription expired due to cancelation or expiration (term has ended), notify Paypal by returning 200 status code' );
					do_action( 'super_after_paypal_ipn_subscription_expired', array( 'post' => $_POST ) );
					SUPER_Common::triggerEvent( 'paypal.ipn.subscription.expired', array( 'sfsi_id' => $sfsi_id ) );
					// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
					http_response_code( 200 );
					exit;
				}

				// When the subscription payment has failed, not much we can do about this, and we don't have to do anything except let paypal know we received the IPN message
				if ( ( isset( $_POST['txn_type'] ) ) && ( $_POST['txn_type'] == 'subscr_failed' ) ) {
					error_log( 'Super Forms: Paypal IPN subscription payment failed, notify Paypal by returning 200 status code' );
					do_action( 'super_after_paypal_ipn_subscription_payment_failed', array( 'post' => $_POST ) );
					SUPER_Common::triggerEvent( 'paypal.ipn.subscription.payment.failed', array( 'sfsi_id' => $sfsi_id ) );
					// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
					http_response_code( 200 );
					exit;
				}

				// IPN message telling that the subscription is either being modified, suspended or canceled
				if ( ( isset( $_POST['txn_type'] ) ) && ( ( $_POST['txn_type'] == 'subscr_modify' ) || ( $_POST['txn_type'] == 'recurring_payment_suspended' ) || ( $_POST['txn_type'] == 'subscr_cancel' ) ) ) {
					error_log( 'Super Forms: Paypal IPN subscription is being modified, suspended or canceled' );

					// Get subscription ID
					if ( isset( $_POST['subscr_id'] ) ) {
						$sub_id = sanitize_text_field( $_POST['subscr_id'] );
					}
					if ( isset( $_POST['recurring_payment_id'] ) ) {
						$sub_id = sanitize_text_field( $_POST['recurring_payment_id'] );
					}

					// Get ID based on ipn tracking ID
					global $wpdb;
					$post_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta AS meta INNER JOIN $wpdb->posts AS post ON post.id = meta.post_id WHERE post.post_type = 'super_paypal_sub' AND meta_key = '_super_sub_id' AND meta_value = '$sub_id'" );

					// Update data accordingly
					if ( isset( $_POST['subscr_id'] ) ) {
						update_post_meta( $post_id, '_super_sub_id', $_POST['subscr_id'] );
					}
					if ( isset( $_POST['recurring_payment_id'] ) ) {
						update_post_meta( $post_id, '_super_sub_id', $_POST['recurring_payment_id'] );
					}

					// If subscription is suspended
					if ( $_POST['txn_type'] == 'recurring_payment_suspended' ) {
						$post_txn_data                   = get_post_meta( $post_id, '_super_txn_data', true );
						$post_txn_data['txn_type']       = 'recurring_payment_suspended';
						$post_txn_data['profile_status'] = 'Suspended';
						update_post_meta( $post_id, '_super_txn_data', $post_txn_data );
					}

					// If subscription is canceled
					if ( $_POST['txn_type'] == 'subscr_cancel' ) {
						$post_txn_data                   = get_post_meta( $post_id, '_super_txn_data', true );
						$post_txn_data['txn_type']       = 'subscr_cancel';
						$post_txn_data['profile_status'] = 'Canceled';
						update_post_meta( $post_id, '_super_txn_data', $post_txn_data );
					}

					// If subscription is modified
					if ( $_POST['txn_type'] == 'subscr_modify' ) {
						update_post_meta( $post_id, '_super_txn_data', $_POST );
					}

					do_action(
						'super_after_paypal_ipn_subscription_changed',
						array(
							'post'     => $_POST,
							'post_id'  => $post_id,
							'txn_type' => $_POST['txn_type'],
						)
					);
					SUPER_Common::triggerEvent( 'paypal.ipn.subscription.changed', array( 'sfsi_id' => $sfsi_id ) );

					// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
					http_response_code( 200 );
					exit;
				}

				// If payment status is Refunded
				if ( ( isset( $_POST['payment_status'] ) ) && ( $_POST['payment_status'] == 'Refunded' ) ) {
					error_log( 'Super Forms: Paypal IPN subscription payment status changed to refunded' );

					// Get ID based on ipn tracking ID
					global $wpdb;
					$parent_txn_id                   = sanitize_text_field( $_POST['parent_txn_id'] );
					$post_id                         = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_type = 'super_paypal_txn' AND post_title = '$parent_txn_id'" );
					$post_txn_data                   = get_post_meta( $post_id, '_super_txn_data', true );
					$post_txn_data['payment_status'] = 'Refunded';
					update_post_meta( $post_id, '_super_txn_data', $post_txn_data );

					do_action(
						'super_after_paypal_ipn_payment_refunded',
						array(
							'post'    => $_POST,
							'post_id' => $post_id,
						)
					);
					SUPER_Common::triggerEvent( 'paypal.ipn.payment.refunded', array( 'sfsi_id' => $sfsi_id ) );

					// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
					http_response_code( 200 );
					exit;
				}

				// Retrieve submission info
				$sfsi_id = sanitize_text_field( $_POST['custom'] );
				$sfsi    = get_option( '_sfsi_' . $sfsi_id, array() );
				extract( $sfsi );
				// "i18n": "",
				// "sfsi_id": "e21a7dfad2e730960f5b9b3059c494ff.1735403513",
				// "data": "....",
				// "form_id": 73046,
				// "entry_id": "",
				// "list_id": "",
				error_log( 'PayPal sfsi: ' . json_encode( $sfsi ) );
				error_log( 'form_id: ' . $form_id );
				// $custom = apply_filters( 'super_paypal_custom_data_filter', $_POST['custom'] );
				// $custom = array(
				// 0 absint($atts['post']['form_id']),
				// 1 $settings['paypal_payment_type'],
				// 2 $atts['entry_id'],
				// 3 get_current_user_id(),
				// 4 absint($post_id), // Used only if Front-end Posting is enabled to update the post status after successfull payment.
				// 5 absint($user_id) // Used only if Register & Login is enabled to update the user status after successfull payment.
				// );
				if ( ! $form_id ) {
					return;
				}
				if ( absint( $form_id ) == 0 ) {
					return;
				}
				error_log( 'before: ' . json_encode( $settings ) );
				if ( method_exists( 'SUPER_Common', 'get_form_settings' ) ) {
					$settings = SUPER_Common::get_form_settings( $form_id );
					error_log( 'after 1: ' . json_encode( $settings ) );
				} else {
					$settings = get_post_meta( absint( $form_id ), '_super_form_settings', true );
					error_log( 'after 2: ' . json_encode( $settings ) );
				}
				if ( ! is_array( $settings ) ) {
					return;
				}
				// Check the receiver email to see if it matches your list of paypal email addresses
				$merchant_emails = explode( ',', $settings['paypal_merchant_email'] );
				$email_found     = false;
				foreach ( $merchant_emails as $email ) {
					if ( ( strtolower( $_POST['receiver_email'] ) ) == ( strtolower( trim( $email ) ) ) ) {
						$email_found = true;
						break;
					}
				}
				if ( $email_found == false ) {
					return;
				}
				// Set endpoint URL to post the verification data to
				if ( ! isset( $settings['paypal_mode'] ) ) {
					$settings['paypal_mode'] = '';
				}
				$url = 'https://www.' . ( $settings['paypal_mode'] == 'sandbox' ? 'sandbox.' : '' ) . 'paypal.com/cgi-bin/webscr';
				// Build the body of the verification post request, adding the _notify-validate command.
				$raw_post_data  = file_get_contents( 'php://input' );
				$raw_post_array = explode( '&', $raw_post_data );
				$myPost         = array();
				foreach ( $raw_post_array as $keyval ) {
					$keyval = explode( '=', $keyval );
					if ( count( $keyval ) == 2 ) {
						// Since we do not want the plus in the datetime string to be encoded to a space, we manually encode it.
						if ( $keyval[0] === 'payment_date' ) {
							if ( substr_count( $keyval[1], '+' ) === 1 ) {
								$keyval[1] = str_replace( '+', '%2B', $keyval[1] );
							}
						}
						$myPost[ $keyval[0] ] = urldecode( $keyval[1] );
					}
				}
				$req = 'cmd=_notify-validate';
				foreach ( $myPost as $key => $value ) {
					$value = urlencode( $value );
					$req  .= "&$key=$value";
				}
				// Post the data back to PayPal.
				$http      = new WP_Http();
				$response  = $http->post(
					$url,
					array(
						'sslverify' => false,
						'ssl'       => true,
						'body'      => $req,
						'timeout'   => 20,
					)
				);
				$http_code = $response['response']['code'];
				if ( $http_code != 200 ) {
					throw new Exception( "PayPal responded with HTTP code $http_code" );
				}

				// Check if PayPal verifies the IPN data, and if so, return true.
				if ( ( ! is_wp_error( $response ) ) && ( $response['body'] == 'VERIFIED' ) ) {
					$post_type = 'super_paypal_txn';
					if ( $_POST['txn_type'] == 'subscr_signup' ) {
						$post_status = 'publish';
						$post_type   = 'super_paypal_sub';
						$post_title  = $_POST['subscr_id'];
					} else {
						$post_status = $_POST['payment_status'];
						$post_title  = $_POST['txn_id'];
					}
					$post            = array(
						'post_status' => sanitize_text_field( $post_status ),
						'post_type'   => $post_type,
						'post_title'  => sanitize_text_field( $post_title ),
						'post_parent' => absint( $form_id ),
						'post_author' => absint( $user_id ),
					);
					$paypal_order_id = wp_insert_post( $post );
					if ( isset( $_POST['subscr_id'] ) ) {
						add_post_meta( $paypal_order_id, '_super_sub_id', $_POST['subscr_id'] );
					}
					if ( isset( $_POST['recurring_payment_id'] ) ) {
						add_post_meta( $paypal_order_id, '_super_sub_id', $_POST['recurring_payment_id'] );
					}
					add_post_meta( $paypal_order_id, '_super_txn_data', $_POST );
					if ( $_POST['txn_type'] == 'subscr_signup' ) {
						$count = get_option( 'super_paypal_sub_count', 0 );
						update_option( 'super_paypal_sub_count', ( $count + 1 ) );
					} else {
						$count = get_option( 'super_paypal_txn_count', 0 );
						update_option( 'super_paypal_txn_count', ( $count + 1 ) );
					}

					// Grab sfsi data
					error_log( '$sfsi_id: ' . $sfsi_id );
					// Save paypal order ID to contact entry
					if ( ! empty( $entry_id ) ) {
						update_post_meta( $entry_id, '_super_contact_entry_paypal_order_id', $paypal_order_id );
						update_post_meta( $paypal_order_id, '_super_contact_entry_id', $entry_id );
					}
					// Save paypal order ID to created post (if front-end posting was enabled)
					if ( ! empty( $created_post ) ) {
						update_post_meta( $created_post, '_super_connected_paypal_order_id', $paypal_order_id );
						update_post_meta( $paypal_order_id, '_super_connected_post_id', $created_post );
					}

					// moved to triggers // Update contact entry status after succesfull payment
					// moved to triggers if( !empty($settings['paypal_completed_entry_status']) ) {
					// moved to triggers    update_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['paypal_completed_entry_status'] );
					// moved to triggers }

					// moved to triggers // Update post status after succesfull payment (only used for Front-end Posting)
					// moved to triggers $paypal_order_id = absint($custom[4]);
					// moved to triggers if( ($paypal_order_id!=0) && (!empty($settings['paypal_completed_post_status'])) ) {
					// moved to triggers    wp_update_post(
					// moved to triggers        array(
					// moved to triggers            'ID' => $paypal_order_id,
					// moved to triggers            'post_status' => $settings['paypal_completed_post_status']
					// moved to triggers        )
					// moved to triggers    );
					// moved to triggers }

					// moved to triggers // Update user status after succesfull payment (only used for Register & Login)
					// moved to triggers $user_id = 0;
					// moved to triggers if( !empty($settings['register_login_action']) ) {
					// moved to triggers    if( $settings['register_login_action']=='register' ) {
					// moved to triggers        $user_id = absint($custom[5]);
					// moved to triggers        if( $user_id!=0 ) {
					// moved to triggers            // Update login status
					// moved to triggers            if( !empty($settings['paypal_completed_signup_status']) ) {
					// moved to triggers                update_user_meta( $user_id, 'super_user_login_status', $settings['paypal_completed_signup_status'] );
					// moved to triggers            }
					// moved to triggers            // Update user role
					// moved to triggers            $user_role = '';
					// moved to triggers            if( !empty($settings['paypal_completed_user_role']) ) {
					// moved to triggers                $user_role = $settings['paypal_completed_user_role'];
					// moved to triggers            }
					// moved to triggers            if( !empty($user_role) ) {
					// moved to triggers                $userdata = array(
					// moved to triggers                    'ID' => $user_id,
					// moved to triggers                    'role' => $user_role
					// moved to triggers                );
					// moved to triggers                $result = wp_update_user( $userdata );
					// moved to triggers                if( is_wp_error( $result ) ) {
					// moved to triggers                    throw new Exception($result->get_error_message());
					// moved to triggers                }
					// moved to triggers            }
					// moved to triggers        }
					// moved to triggers    }
					// moved to triggers }

					// tmp // Send E-mail when payment was completed/successful
					// tmp // Can only work if entry was created
					// tmp if( !empty($contact_entry_id) && !empty($settings['paypal_completed_email']) ) {
					// tmp  $data = get_post_meta($contact_entry_id, '_super_contact_entry_data', true);
					// tmp  $global_settings = SUPER_Common::get_global_settings();
					// tmp  if( $settings!=false ) {
					// tmp      // @since 4.0.0 - when adding new field make sure we merge settings from global settings with current form settings
					// tmp      foreach( $settings as $k => $v ) {
					// tmp          if( isset( $global_settings[$k] ) ) {
					// tmp              if( $global_settings[$k] == $v ) {
					// tmp                  unset( $settings[$k] );
					// tmp              }
					// tmp          }
					// tmp      }
					// tmp  }else{
					// tmp      $settings = array();
					// tmp  }
					// tmp  $settings = array_merge($global_settings, $settings);

					// tmp  if(!isset($settings['paypal_completed_exclude_empty'])) $settings['paypal_completed_exclude_empty'] = '';

					// tmp  $confirm_loop = '';
					// tmp  $confirm_attachments = array();
					// tmp  $confirm_string_attachments = array();
					// tmp  if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
					// tmp      foreach( $data as $k => $v ) {
					// tmp          // Skip dynamic data
					// tmp          if($k=='_super_dynamic_data') continue;
					// tmp          $confirm_row = $settings['paypal_completed_email_loop'];
					// tmp          if( !isset( $v['exclude'] ) ) {
					// tmp              $v['exclude'] = 0;
					// tmp          }
					// tmp          if( $v['exclude']==2 ) {
					// tmp              continue;
					// tmp          }

					// tmp          /**
					// tmp           *  Filter to control the email loop when something special needs to happen
					// tmp           *  e.g. Signature element needs to display image instead of the base64 code that the value contains
					// tmp           *
					// tmp           *  @param  string  $row
					// tmp           *  @param  array   $data
					// tmp           *
					// tmp           *  @since      1.0.9
					// tmp          */
					// tmp          $confirm_result = apply_filters( 'super_before_email_loop_data_filter', $confirm_row, array( 'type'=>'confirm', 'v'=>$v, 'confirm_string_attachments'=>$confirm_string_attachments ) );
					// tmp          $continue = false;
					// tmp          if( isset( $confirm_result['status'] ) ) {
					// tmp              if( $confirm_result['status']=='continue' ) {
					// tmp                  if( isset( $confirm_result['confirm_string_attachments'] ) ) {
					// tmp                      $confirm_string_attachments = $confirm_result['confirm_string_attachments'];
					// tmp                  }
					// tmp                  $confirm_loop .= $confirm_result['row'];
					// tmp                  $continue = true;
					// tmp              }
					// tmp          }
					// tmp          if($continue) continue;

					// tmp          if( isset($v['type']) && $v['type']=='files' ) {
					// tmp              $files_value = '';
					// tmp              if( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) {
					// tmp                  $v['value'] = '';
					// tmp                  if( !empty( $v['label'] ) ) {
					// tmp                      // Replace %d with empty string if exists
					// tmp                      $v['label'] = str_replace('%d', '', $v['label']);
					// tmp                      $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
					// tmp                      $confirm_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $confirm_row );
					// tmp                  }else{
					// tmp                      $row = str_replace( '{loop_label}', '', $row );
					// tmp                      $confirm_row = str_replace( '{loop_label}', '', $confirm_row );
					// tmp                  }
					// tmp                  $files_value .= esc_html__( 'User did not upload any files', 'super-forms' );
					// tmp              }else{
					// tmp                  $v['value'] = '-';
					// tmp                  foreach( $v['files'] as $key => $value ) {
					// tmp                      if( $key==0 ) {
					// tmp                          if( !empty( $v['label'] ) ) {
					// tmp                              $v['label'] = str_replace('%d', '', $v['label']);
					// tmp                              $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
					// tmp                              $confirm_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $confirm_row );
					// tmp                          }else{
					// tmp                              $row = str_replace( '{loop_label}', '', $row );
					// tmp                              $confirm_row = str_replace( '{loop_label}', '', $confirm_row );
					// tmp                          }
					// tmp                      }
					// tmp                      // In case the file was deleted we do not want to add a hyperlink that links to the file
					// tmp                      if( !empty($settings['file_upload_submission_delete']) ) {
					// tmp                          $files_value .= $value['value'] . '<br /><br />';
					// tmp                      }else{
					// tmp                          $files_value .= '<a href="' . esc_url($value['url']) . '" target="_blank">' . $value['value'] . '</a><br /><br />';
					// tmp                      }
					// tmp                      // Exclude file from email completely
					// tmp                      if( $v['exclude']!=2 ) {
					// tmp                          if( $v['exclude']!=1 ) {
					// tmp                              $confirm_attachments[$value['value']] = $value['url'];
					// tmp                          }
					// tmp                      }
					// tmp                  }
					// tmp              }
					// tmp              $row = str_replace( '{loop_value}', $files_value, $row );
					// tmp              $confirm_row = str_replace( '{loop_value}', $files_value, $confirm_row );
					// tmp          }else{
					// tmp              if( isset($v['type']) && (($v['type']=='form_id') || ($v['type']=='entry_id')) ) {
					// tmp                  $row = '';
					// tmp                  $confirm_row = '';
					// tmp              }else{

					// tmp                  if( !empty( $v['label'] ) ) {
					// tmp                      $v['label'] = str_replace('%d', '', $v['label']);
					// tmp                      $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
					// tmp                      $confirm_row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $confirm_row );
					// tmp                  }else{
					// tmp                      $row = str_replace( '{loop_label}', '', $row );
					// tmp                      $confirm_row = str_replace( '{loop_label}', '', $confirm_row );
					// tmp                  }
					// tmp                  // @since 1.2.7
					// tmp                  if( isset( $v['admin_value'] ) ) {
					// tmp                      // @since 3.9.0 - replace comma's with HTML
					// tmp                      if( !empty($v['replace_commas']) ) $v['admin_value'] = str_replace( ',', $v['replace_commas'], $v['admin_value'] );
					// tmp
					// tmp                      $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['admin_value'] ), $row );
					// tmp                      $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['admin_value'] ), $confirm_row );
					// tmp                  }
					// tmp                  if( isset( $v['paypal_completed_value'] ) ) {
					// tmp                      // @since 3.9.0 - replace comma's with HTML
					// tmp                      if( !empty($v['replace_commas']) ) $v['paypal_completed_value'] = str_replace( ',', $v['replace_commas'], $v['paypal_completed_value'] );
					// tmp
					// tmp                      $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['paypal_completed_value'] ), $confirm_row );
					// tmp                  }
					// tmp                  if( isset( $v['value'] ) ) {
					// tmp                      // @since 3.9.0 - replace comma's with HTML
					// tmp                      if( !empty($v['replace_commas']) ) $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
					// tmp
					// tmp                      $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['value'] ), $row );
					// tmp                      $confirm_row = str_replace( '{loop_value}', SUPER_Common::decode_textarea_v5( $v, $v['value'] ), $confirm_row );
					// tmp                  }
					// tmp              }
					// tmp          }
					// tmp          // @since 4.5.0 - check if value is empty, and if we need to exclude it from the email
					// tmp          if( $v['exclude']!=1 ) {
					// tmp              if( $settings['paypal_completed_exclude_empty']=='true' && (empty($v['value']) || $v['value']=='0') ) {
					// tmp              }else{
					// tmp                  $confirm_loop .= $confirm_row;
					// tmp              }
					// tmp          }
					// tmp      }
					// tmp  }

					// tmp  // @since 2.8.0 - additional header support for confirmation emails
					// tmp  if( !isset($settings['paypal_completed_header_additional']) ) $settings['paypal_completed_header_additional'] = '';
					// tmp  $settings['header_additional'] = $settings['paypal_completed_header_additional'];
					// tmp
					// tmp  if(!empty($settings['paypal_completed_body_open'])) $settings['paypal_completed_body_open'] = $settings['paypal_completed_body_open'] . '<br /><br />';
					// tmp  if(!empty($settings['paypal_completed_body'])) $settings['paypal_completed_body'] = $settings['paypal_completed_body'] . '<br /><br />';
					// tmp  $email_body = $settings['paypal_completed_body_open'] . $settings['paypal_completed_body'] . $settings['paypal_completed_body_close'];
					// tmp  $email_body = str_replace( '{loop_fields}', $confirm_loop, $email_body );

					// tmp  // Set a new password when a user registered and when `{register_generated_password}` tag is found and if we are sending an email to the user
					// tmp  if( $user_id!=0 && !empty($settings['paypal_completed_email']) && $settings['paypal_completed_email']==='true' ) {
					// tmp      // Please note that if this tag is being used, while a password field existed in the form named "user_pass" the user defined password will be reset to a new one.
					// tmp      // It's better to not use a "user_pass" field when using a registration form in combination with Paypal payment and the option to send a "completed email" via Paypal
					// tmp      // Only if passwords tags are found in the email body
					// tmp      if(strpos($email_body, '{field_user_pass}')!==false || strpos($email_body, '{user_pass}')!==false || strpos($email_body, '{register_generated_password}')!==false){
					// tmp          // Prevent sending default WP email
					// tmp          add_filter( 'send_password_change_email', '__return_false' );
					// tmp          $password = wp_generate_password();
					// tmp          $user_id = wp_update_user( array( 'ID' => $user_id, 'user_pass' => $password ) );
					// tmp          $email_body = str_replace( '{field_user_pass}', $password, $email_body );
					// tmp          $email_body = str_replace( '{user_pass}', $password, $email_body );
					// tmp          $email_body = str_replace( '{register_generated_password}', $password, $email_body );
					// tmp      }
					// tmp  }

					// tmp  $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );

					// tmp  // @since 3.1.0 - optionally automatically add line breaks
					// tmp  if(!isset($settings['paypal_completed_body_nl2br'])) $settings['paypal_completed_body_nl2br'] = 'true';
					// tmp  if($settings['paypal_completed_body_nl2br']=='true') $email_body = nl2br( $email_body );

					// tmp  // @since 4.9.5 - RTL email setting
					// tmp  if(!isset($settings['paypal_completed_rtl'])) $settings['paypal_completed_rtl'] = '';
					// tmp  if($settings['paypal_completed_rtl']=='true') $email_body = '<div dir="rtl" style="text-align:right;">' . $email_body . '</div>';
					// tmp
					// tmp  $email_body = do_shortcode($email_body);
					// tmp  $email_body = apply_filters( 'super_before_sending_confirm_body_filter', $email_body, array( 'settings'=>$settings, 'confirm_loop'=>$confirm_loop, 'data'=>$data ) );
					// tmp  if( !isset( $settings['paypal_completed_from_type'] ) ) $settings['paypal_completed_from_type'] = 'default';
					// tmp  if( $settings['paypal_completed_from_type']=='default' ) {
					// tmp      $settings['paypal_completed_from_name'] = get_option( 'blogname' );
					// tmp      $settings['paypal_completed_from'] = get_option( 'admin_email' );
					// tmp  }
					// tmp  if( !isset( $settings['paypal_completed_from_name'] ) ) $settings['paypal_completed_from_name'] = get_option( 'blogname' );
					// tmp  if( !isset( $settings['paypal_completed_from'] ) ) $settings['paypal_completed_from'] = get_option( 'admin_email' );
					// tmp  $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['paypal_completed_to'], $data, $settings ) );
					// tmp  $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['paypal_completed_from'], $data, $settings ) );
					// tmp  $from_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['paypal_completed_from_name'], $data, $settings ) );
					// tmp  $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['paypal_completed_subject'], $data, $settings ) );

					// tmp  // @since 2.8.0 - cc and bcc support for confirmation emails
					// tmp  $cc = '';
					// tmp  if( !empty($settings['paypal_completed_header_cc']) ) {
					// tmp      $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['paypal_completed_header_cc'], $data, $settings ) );
					// tmp  }
					// tmp  $bcc = '';
					// tmp  if( !empty($settings['paypal_completed_header_bcc']) ) {
					// tmp      $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['paypal_completed_header_bcc'], $data, $settings ) );
					// tmp  }

					// tmp  // @since 2.8.0 - custom reply to headers
					// tmp  if( !isset($settings['paypal_completed_header_reply_enabled']) ) $settings['paypal_completed_header_reply_enabled'] = false;
					// tmp  $reply = '';
					// tmp  $reply_name = '';
					// tmp  if( $settings['paypal_completed_header_reply_enabled']==false ) {
					// tmp      $custom_reply = false;
					// tmp  }else{
					// tmp      $custom_reply = true;
					// tmp      if( !isset($settings['paypal_completed_header_reply']) ) $settings['paypal_completed_header_reply'] = '';
					// tmp      if( !isset($settings['paypal_completed_header_reply_name']) ) $settings['paypal_completed_header_reply_name'] = '';
					// tmp      $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['paypal_completed_header_reply'], $data, $settings ) );
					// tmp      $reply_name = SUPER_Common::decode( SUPER_Common::email_tags( $settings['paypal_completed_header_reply_name'], $data, $settings ) );
					// tmp  }

					// tmp  // @since 3.3.2 - default confirm email attachments
					// tmp  if( !empty($settings['paypal_completed_attachments']) ) {
					// tmp      $email_attachments = explode( ',', $settings['paypal_completed_attachments'] );
					// tmp      foreach($email_attachments as $k => $v){
					// tmp          $file = get_attached_file($v);
					// tmp          if( $file ) {
					// tmp              $url = wp_get_attachment_url($v);
					// tmp              $filename = basename ( $file );
					// tmp              $confirm_attachments[$filename] = $url;
					// tmp          }
					// tmp      }
					// tmp  }

					// tmp  // @since 2.0
					// tmp  $confirm_attachments = apply_filters( 'super_before_sending_email_confirm_attachments_filter', $confirm_attachments, array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$email_body )  );

					// tmp  // Send the email
					// tmp  $mail = SUPER_Common::email( array( 'to'=>$to, 'from'=>$from, 'from_name'=>$from_name, 'custom_reply'=>$custom_reply, 'reply'=>$reply, 'reply_name'=>$reply_name, 'cc'=>$cc, 'bcc'=>$bcc, 'subject'=>$subject, 'body'=>$email_body, 'settings'=>$settings, 'attachments'=>$attachments, 'string_attachments'=>$confirm_string_attachments ));

					// tmp  // Return error message
					// tmp  if( !empty( $mail->ErrorInfo ) ) {
					// tmp      $msg = esc_html__( 'Message could not be sent. Error: ' . $mail->ErrorInfo, 'super-forms' );
					// tmp      SUPER_Common::output_message( array(
					// tmp          'msg'=>$msg,
					// tmp          'form_id'=>absint($form_id)
					// tmp      ));
					// tmp  }
					// tmp }

					do_action(
						'super_after_paypal_ipn_payment_verified',
						array(
							'post_id' => $paypal_order_id,
							'post'    => $_POST,
						)
					);
					SUPER_Common::triggerEvent( 'paypal.ipn.payment.verified', array( 'sfsi_id' => $sfsi_id ) );

				}
				// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
				http_response_code( 200 );
				exit;
			}
		}


		/**
		 * Add the PayPal order link to the entry info/data page
		 *
		 * @since       1.0.0
		 */
		public static function add_entry_order_link( $result, $data ) {
			$order_id = get_post_meta( $data['entry_id'], '_super_contact_entry_paypal_order_id', true );
			if ( ! empty( $order_id ) ) {
				$order_id = absint( $order_id );
				if ( $order_id != 0 ) {
					$url = admin_url() . 'admin.php?page=super_paypal_txn&id=' . $order_id;
					if ( get_post_type( $order_id ) === 'super_paypal_sub' ) {
						$url = admin_url() . 'admin.php?page=super_paypal_sub&id=' . $order_id;
					}
					$result .= '<tr><th align="right">' . esc_html__( 'PayPal Order', 'super-forms' ) . ':</th><td><span class="super-contact-entry-data-value">';
					$result .= '<a href="' . esc_url( $url ) . '">' . get_the_title( $order_id ) . '</a>';
					$result .= '</span></td></tr>';
				}
			}
			return $result;
		}


		/**
		 * Hook into before sending email and check if we need to create or update a post or taxonomy
		 *
		 *  @since      1.0.0
		 */
		public static function before_email_success_msg( $atts ) {
			error_log( 'before_email_success_msg(paypal)' );
			// $atts values:
			// 'i18n'=>$i18n,
			// 'sfsi_id'=>$sfsi_id,
			// 'post'=>$_POST,
			// 'data'=>$data,
			// 'settings'=>$settings,
			// 'entry_id'=>$contact_entry_id,
			// 'attachments'=>$attachments,
			// 'form_id'=>$form_id

			$sfsi_id  = $atts['sfsi_id'];
			$settings = $atts['settings'];
			if ( isset( $atts['data'] ) ) {
				$data = $atts['data'];
			} elseif ( $settings['save_contact_entry'] == 'yes' ) {
					$data = get_post_meta( $atts['entry_id'], '_super_contact_entry_data', true );
			} else {
				$data = $atts['post']['data'];
			}

			// @since 1.3.0 - check if we do not want to checkout to PayPal conditionally
			if ( ! empty( $settings['conditionally_paypal_checkout'] ) ) {
				$settings['paypal_checkout'] = '';
				if ( ! empty( $settings['conditionally_paypal_checkout_check'] ) ) {
					$values = explode( ',', $settings['conditionally_paypal_checkout_check'] );
					// let's replace tags with values
					foreach ( $values as $k => $v ) {
						$values[ $k ] = SUPER_Common::email_tags( $v, $data, $settings );
					}
					if ( ! isset( $values[0] ) ) {
						$values[0] = '';
					}
					if ( ! isset( $values[1] ) ) {
						$values[1] = '=='; // is either == or !=   (== by default)
					}
					if ( ! isset( $values[2] ) ) {
						$values[2] = '';
					}
					// if at least 1 of the 2 is not empty then apply the check otherwise skip it completely
					if ( ( $values[0] != '' ) || ( $values[2] != '' ) ) {
						// Check if values match eachother
						if ( ( $values[1] == '==' ) && ( $values[0] == $values[2] ) ) {
							$settings['paypal_checkout'] = 'true';
						}
						if ( ( $values[1] == '!=' ) && ( $values[0] != $values[2] ) ) {
							$settings['paypal_checkout'] = 'true';
						}
					}
				}
			}

			if ( ( isset( $settings['paypal_checkout'] ) ) && ( $settings['paypal_checkout'] == 'true' ) ) {
				if ( ! isset( $settings['paypal_mode'] ) ) {
					$settings['paypal_mode'] = '';
				}
				if ( ! isset( $settings['paypal_payment_type'] ) ) {
					$settings['paypal_payment_type'] = 'product';
				}
				if ( ! isset( $settings['paypal_merchant_email'] ) ) {
					$settings['paypal_merchant_email'] = '';
				}
				if ( ! isset( $settings['paypal_cancel_url'] ) ) {
					$settings['paypal_cancel_url'] = get_home_url();
				}
				if ( ! isset( $settings['paypal_custom_return_url'] ) ) {
					$settings['paypal_custom_return_url'] = '';
				}
				if ( ! isset( $settings['paypal_return_url'] ) ) {
					$settings['paypal_return_url'] = get_home_url();
				}
				if ( ! isset( $settings['paypal_currency_code'] ) ) {
					$settings['paypal_currency_code'] = 'USD';
				}
				if ( ! isset( $settings['paypal_item_amount'] ) ) {
					$settings['paypal_item_amount'] = '5.00';
				}
				if ( is_numeric( $settings['paypal_item_amount'] ) ) {
					$settings['paypal_item_amount'] = number_format( (float) $settings['paypal_item_amount'], 2 );
					if ( ( isset( self::$currency_codes[ $settings['paypal_currency_code'] ]['decimal'] ) ) && ( self::$currency_codes[ $settings['paypal_currency_code'] ]['decimal'] == true ) ) {
						$settings['paypal_item_amount'] = (float) $settings['paypal_item_amount'];
						$settings['paypal_item_amount'] = floor( $settings['paypal_item_amount'] );
					}
				}
				if ( $settings['save_contact_entry'] != 'yes' ) {
					$atts['entry_id'] = 0;
				}

				// Get Post ID and save it in custom parameter for paypal so we can update the post status after successfull payment complete
				// tmp $post_id = SUPER_Common::getClientData( 'super_forms_created_post_id' );
				// tmp if( $post_id==false ) {
				// tmp  $post_id = 0;
				// tmp }

				// Get User ID and save it in custom parameter for paypal so we can update the user status after successfull payment complete
				// $user_id = SUPER_Common::getClientData( 'super_forms_registered_user_id' );
				// if( $user_id==false ) {
				// $user_id = 0;
				// }

				// $custom = array($sfsi_id);
				// $custom = array(
				// 0 absint($atts['post']['form_id']),
				// 1 $settings['paypal_payment_type'],
				// 2 $atts['entry_id'],
				// 3 get_current_user_id(),
				// 4 absint($post_id), // Used only if Front-end Posting is enabled to update the post status after successfull payment.
				// 5 absint($user_id) // Used only if Register & Login is enabled to update the user status after successfull payment.
				// );
				$home_url = get_home_url() . '/';
				if ( strstr( $home_url, '?' ) ) {
					$return_url = $home_url . '&page=super_paypal_response'; // . absint($atts['entry_id']) . '|' . $form_id . '|' . $payment_type;
					$notify_url = $home_url . '&page=super_paypal_ipn';
				} else {
					$return_url = $home_url . '?page=super_paypal_response'; // . absint($atts['entry_id']) . '|' . $form_id . '|' . $payment_type;
					$notify_url = $home_url . '?page=super_paypal_ipn';
				}
				if ( $settings['paypal_custom_return_url'] == 'true' ) {
					$return_url = $settings['paypal_return_url'];
				}

				$cmd = '_xclick';
				switch ( $settings['paypal_payment_type'] ) {

					// _xclick - The button that the person clicked was a Buy Now button.
					case 'product':
						$cmd = '_xclick';
						break;

					// _donations - The button that the person clicked was a Donate button.
					case 'donation':
						$cmd = '_donations';
						break;

					// _xclick-subscriptions - The button that the person clicked was a Subscribe button.
					case 'subscription':
						$cmd = '_xclick-subscriptions';
						break;

					// _cart - For shopping cart purchases. The following variables specify the kind of shopping cart button that the person clicked:
					case 'cart':
						$cmd = '_cart';
						break;
				}
				// $action = 'http://f4d.nl/dev/?page=super_paypal_ipn'; // For local testing
				$action  = 'https://www.' . ( $settings['paypal_mode'] == 'sandbox' ? 'sandbox.' : '' ) . 'paypal.com/cgi-bin/webscr';
				$message = '';

				$message .= '<form target="_self" id="super_paypal_' . $atts['post']['form_id'] . '" action="' . esc_attr( $action ) . '" method="post">';

				// If continue shopping is enabled (e.g: custom URL redirect is enabled for the form)
				if ( ! empty( $settings['form_redirect_option'] ) ) {
					$redirect = null;
					if ( $settings['form_redirect_option'] == 'page' ) {
						$redirect = get_permalink( $settings['form_redirect_page'] );
					}
					if ( $settings['form_redirect_option'] == 'custom' ) {
						$redirect = SUPER_Common::email_tags( $settings['form_redirect'], $data, $settings );
					}
					if ( $redirect != null ) {
						$message .= '<input type="hidden" name="shopping_url" value="' . esc_url( $redirect ) . '">';
					}
				}

				$message .= '<input type="hidden" name="cmd" value="' . esc_attr( $cmd ) . '">';

				// Sets the character set and character encoding for the billing information/log-in page on the PayPal website. In addition, this variable sets the same values for information that you send to PayPal in your HTML button code. Default is based on the language encoding settings in your account profile.
				if ( ! empty( $settings['paypal_charset'] ) ) {
					$message .= '<input type="hidden" name="charset" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_charset'], $data, $settings ) ) . '">';
				} else {
					$message .= '<input type="hidden" name="charset" value="UTF-8">';
				}

				// The URL of the 150x50-pixel image displayed as your logo in the upper left corner of the PayPal checkout pages.
				// Default is your business name, if you have a PayPal Business account or your email address, if you have PayPal Premier or Personal account.
				if ( ! empty( $settings['paypal_image_url'] ) ) {
					$message .= '<input type="hidden" name="image_url" value="">';
				}

				// Your PayPal ID or an email address associated with your PayPal account. E-mail addresses must be confirmed.
				$message .= '<input type="hidden" name="business" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_merchant_email'], $data, $settings ) ) . '">';

				// The URL to which PayPal posts information about the payment, in the form of Instant Payment Notification messages.
				$message .= '<input type="hidden" name="notify_url" value="' . esc_url( SUPER_Common::email_tags( $notify_url, $data, $settings ) ) . '">';

				// Do not prompt buyers for a shipping address.
				// 0. Prompt for an address, but do not require one.
				// 1. Do not prompt for an address.
				// 2. Prompt for an address and require one.
				if ( ! empty( $settings['paypal_no_shipping'] ) ) {
					$message .= '<input type="hidden" name="no_shipping" value="' . esc_attr( $settings['paypal_no_shipping'] ) . '">';
				}

				// The URL to which PayPal redirects buyers' browser after they complete their payments. For example, specify a URL on your site that displays a hank you for your payment page.
				$message .= '<input type="hidden" name="return" value="' . esc_url( SUPER_Common::email_tags( $return_url, $data, $settings ) ) . '">';

				// The buyer's browser is redirected to the return URL by using the POST method, and all payment variables are included.
				$message .= '<input type="hidden" name="rm" value="2">';

				// A URL to which PayPal redirects the buyers' browsers if they cancel checkout before completing their payments. For example, specify a URL on your website that displays the Payment Canceled page.
				$message .= '<input type="hidden" name="cancel_return" value="' . esc_url( SUPER_Common::email_tags( $settings['paypal_cancel_url'], $data, $settings ) ) . '">';

				// The currency of the payment. Default is USD.
				$message .= '<input type="hidden" name="currency_code" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_currency_code'], $data, $settings ) ) . '" />';

				// Pass-through variable for your own tracking purposes, which buyers do not see.
				$message .= '<input type="hidden" name="custom" value="' . esc_attr( $sfsi_id ) . '">';

				// Pass-through variable you can use to identify your invoice number for this purchase.
				if ( ! empty( $settings['paypal_invoice'] ) ) {
					$message .= '<input type="hidden" name="invoice" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_invoice'], $data, $settings ) ) . '">';
				}

				if ( ! empty( $settings['paypal_night_phone_a'] ) ) {
					$message .= '<input type="hidden" name="night_phone_a" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_night_phone_a'], $data, $settings ) ) . '">';
				}
				if ( ! empty( $settings['paypal_night_phone_b'] ) ) {
					$message .= '<input type="hidden" name="night_phone_b" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_night_phone_b'], $data, $settings ) ) . '">';
				}
				if ( ! empty( $settings['paypal_night_phone_c'] ) ) {
					$message .= '<input type="hidden" name="night_phone_c" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_night_phone_c'], $data, $settings ) ) . '">';
				}

				// Parse custom address to paypal
				if ( ( ! empty( $settings['paypal_custom_address'] ) ) && ( $settings['paypal_custom_address'] == 'true' ) ) {
					// Let user not edit the address
					if ( ( ! empty( $settings['paypal_address_override'] ) ) && ( $settings['paypal_address_override'] == 'true' ) ) {
						$message .= '<input type="hidden" name="address_override" value="1">';
					}
					$message .= '<input type="hidden" name="first_name" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_first_name'], $data, $settings ) ) . '">';
					$message .= '<input type="hidden" name="last_name" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_last_name'], $data, $settings ) ) . '">';
					$message .= '<input type="hidden" name="email" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_email'], $data, $settings ) ) . '">';
					$message .= '<input type="hidden" name="address1" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_address1'], $data, $settings ) ) . '">';
					$message .= '<input type="hidden" name="address2" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_address2'], $data, $settings ) ) . '">';
					$message .= '<input type="hidden" name="city" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_city'], $data, $settings ) ) . '">';
					$message .= '<input type="hidden" name="state" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_state'], $data, $settings ) ) . '">';
					$message .= '<input type="hidden" name="zip" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_zip'], $data, $settings ) ) . '">';
					$message .= '<input type="hidden" name="country" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_country'], $data, $settings ) ) . '">';
				}

				if ( $cmd == '_cart' ) {
					// tax_cart
					if ( ! empty( $settings['paypal_tax_cart'] ) ) {
						$message .= '<input type="hidden" name="tax_cart" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_tax_cart'], $data, $settings ) ) . '">';
					}
					// weight_cart
					if ( ! empty( $settings['paypal_weight_cart'] ) ) {
						$message .= '<input type="hidden" name="weight_cart" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_weight_cart'], $data, $settings ) ) . '">';
					}
					// discount_amount_cart
					if ( ! empty( $settings['paypal_discount_amount_cart'] ) ) {
						$message .= '<input type="hidden" name="discount_amount_cart" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_discount_amount_cart'], $data, $settings ) ) . '">';
					}
					// discount_rate_cart
					if ( ! empty( $settings['paypal_discount_rate_cart'] ) ) {
						$message .= '<input type="hidden" name="discount_rate_cart" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_discount_rate_cart'], $data, $settings ) ) . '">';
					}
					// handling_cart
					if ( ! empty( $settings['paypal_handling_cart'] ) ) {
						$message .= '<input type="hidden" name="handling_cart" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_handling_cart'], $data, $settings ) ) . '">';
					}
				}

				// Handling charges. This variable is not quantity-specific. The same handling cost applies, regardless of the number of items on the order.
				if ( ! empty( $settings['paypal_handling'] ) ) {
					$message .= '<input type="hidden" name="handling" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_handling'], $data, $settings ) ) . '">';
				}

				// The unit of measure if weight_cart is specified. Valid value is lbs or kgs.
				if ( ! empty( $settings['paypal_weight_unit'] ) ) {
					$message .= '<input type="hidden" name="weight_unit" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_weight_unit'], $data, $settings ) ) . '">';
				}

				if ( ! empty( $settings['paypal_lc'] ) ) {
					$message .= '<input type="hidden" name="lc" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_lc'], $data, $settings ) ) . '">';
				}

				if ( ( $cmd == '_xclick' ) || ( $cmd == '_donations' ) ) {
					$paypal_item_amount = SUPER_Common::email_tags( $settings['paypal_item_amount'], $data, $settings );
					$paypal_item_amount = self::tofloat( $paypal_item_amount );
					$message           .= '<input type="hidden" name="amount" value="' . $paypal_item_amount . '">';
					if ( ! empty( $settings['paypal_item_name'] ) ) {
						$message .= '<input type="hidden" name="item_name" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_item_name'], $data, $settings ) ) . '">';
					}
					if ( $cmd == '_xclick' ) {
						if ( ! empty( $settings['paypal_item_number'] ) ) {
							$message .= '<input type="hidden" name="item_number" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_item_number'], $data, $settings ) ) . '">';
						}
						if ( ! empty( $settings['paypal_item_quantity'] ) ) {
							$message .= '<input type="hidden" name="quantity" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_item_quantity'], $data, $settings ) ) . '">';
						}
						if ( ! empty( $settings['paypal_item_shipping'] ) ) {
							$message .= '<input type="hidden" name="shipping" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_item_shipping'], $data, $settings ) ) . '">';
						}
						if ( ! empty( $settings['paypal_item_shipping2'] ) ) {
							$message .= '<input type="hidden" name="shipping2" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_item_shipping2'], $data, $settings ) ) . '">';
						}
						if ( ! empty( $settings['paypal_undefined_quantity'] ) ) {
							$message .= '<input type="hidden" name="undefined_quantity" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_undefined_quantity'], $data, $settings ) ) . '">';
						}
						if ( ! empty( $settings['paypal_item_weight'] ) ) {
							$message .= '<input type="hidden" name="weight" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_item_weight'], $data, $settings ) ) . '">';
						}
						if ( ! empty( $settings['paypal_item_discount_amount'] ) ) {
							$paypal_item_discount_amount = SUPER_Common::email_tags( $settings['paypal_item_discount_amount'], $data, $settings );
							$paypal_item_discount_amount = self::tofloat( $paypal_item_discount_amount );
							$message                    .= '<input type="hidden" name="discount_amount" value="' . esc_attr( $paypal_item_discount_amount ) . '">';
							$message                    .= '<input type="hidden" name="discount_amount2" value="' . esc_attr( $paypal_item_discount_amount ) . '">';
						}
						if ( ! empty( $settings['paypal_item_discount_rate'] ) ) {
							$paypal_item_discount_rate = SUPER_Common::email_tags( $settings['paypal_item_discount_rate'], $data, $settings );
							$paypal_item_discount_rate = self::tofloat( $paypal_item_discount_rate );
							$message                  .= '<input type="hidden" name="discount_rate" value="' . esc_attr( $paypal_item_discount_rate ) . '">';
							$message                  .= '<input type="hidden" name="discount_rate2" value="' . esc_attr( $paypal_item_discount_rate ) . '">';
						}
						if ( ! empty( $settings['paypal_item_discount_num'] ) ) {
							$message .= '<input type="hidden" name="discount_num" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_item_discount_num'], $data, $settings ) ) . '">';
						}
					}
				}

				// Cart checkout
				if ( $cmd == '_cart' ) {
					$message .= '<input type="hidden" name="upload" value="1">';

					// Add all items to the cart
					$items        = explode( "\n", $settings['paypal_cart_items'] );
					$absolute_key = 0;
					foreach ( $items as $k => $v ) {
						// Items are defined as:
						// {amount}|{quantity}|{item_name}|{tax}|{shipping}|{shipping2}|{discount_amount}|{discount_rate}
						$options = explode( '|', $v );
						// Amount can not be 0, and quantity can not be 0
						if ( empty( $options[0] ) ) {
							continue;
						}
						if ( empty( $options[1] ) ) {
							continue;
						}
						$amount   = SUPER_Common::email_tags( $options[0], $data, $settings );
						$quantity = SUPER_Common::email_tags( $options[1], $data, $settings );
						if ( empty( $amount ) || empty( $quantity ) ) {
							continue;
						}
						if ( ( $amount == 0 ) || ( $quantity == 0 ) ) {
							continue;
						}
						// Reset key to correct key, because paypal doesn't like it when we skip amount_1 and go straight to amount_2
						$k = $absolute_key;

						$amount   = self::tofloat( $amount );
						$message .= '<input type="hidden" name="amount_' . ( $k + 1 ) . '" value="' . esc_attr( $amount ) . '">';
						$message .= '<input type="hidden" name="quantity_' . ( $k + 1 ) . '" value="' . esc_attr( $quantity ) . '">';

						$ii = 2;
						if ( ! empty( $options[ $ii ] ) ) {
							$message .= '<input type="hidden" name="item_name_' . ( $k + 1 ) . '" value="' . esc_attr( SUPER_Common::email_tags( $options[ $ii ], $data, $settings ) ) . '">';
						}

						++$ii;
						if ( ! empty( $options[ $ii ] ) ) {
							$tax      = SUPER_Common::email_tags( $options[ $ii ], $data, $settings );
							$tax      = self::tofloat( $tax );
							$message .= '<input type="hidden" name="tax_' . ( $k + 1 ) . '" value="' . esc_attr( $tax ) . '">';
						}
						++$ii;
						if ( ! empty( $options[ $ii ] ) ) {
							$shipping = SUPER_Common::email_tags( $options[ $ii ], $data, $settings );
							$shipping = self::tofloat( $shipping );
							$message .= '<input type="hidden" name="shipping_' . ( $k + 1 ) . '" value="' . esc_attr( $shipping ) . '">';
						}
						++$ii;
						if ( ! empty( $options[ $ii ] ) ) {
							$shipping = SUPER_Common::email_tags( $options[ $ii ], $data, $settings );
							$shipping = self::tofloat( $shipping );
							$message .= '<input type="hidden" name="shipping2_' . ( $k + 1 ) . '" value="' . esc_attr( $shipping ) . '">';
						}
						++$ii;
						if ( ! empty( $options[ $ii ] ) ) {
							$discount_amount = SUPER_Common::email_tags( $options[ $ii ], $data, $settings );
							$discount_amount = self::tofloat( $discount_amount );
							$message        .= '<input type="hidden" name="discount_amount_' . ( $k + 1 ) . '" value="' . esc_attr( $discount_amount ) . '">';
						}

						++$ii;
						if ( ! empty( $options[ $ii ] ) ) {
							$message .= '<input type="hidden" name="discount_rate_' . ( $k + 1 ) . '" value="' . esc_attr( SUPER_Common::email_tags( $options[ $ii ], $data, $settings ) ) . '">';
						}

						// Let's check if at least on of the options contains a {tag}
						foreach ( $options as $op => $ov ) {
							if ( preg_match( '/{(.+?)}/', $ov ) ) {
								$origin_name = str_replace( '{', '', $ov );
								$origin_name = str_replace( '}', '', $origin_name );
								// Loop through dynamic added fields
								$i = 2;
								while ( true ) {
									if ( ! isset( $data[ $origin_name . '_' . $i ] ) ) {
										break;
									}
									$field_names = array(
										'amount',
										'quantity',
										'item_name',
										'tax',
										'shipping',
										'shipping2',
										'discount_amount',
										'discount_rate',
									);
									$ii          = 0;
									foreach ( $field_names as $v ) {
										if ( ! empty( $options[ $ii ] ) ) {
											if ( preg_match( '/{(.+?)}/', $options[ $ii ] ) ) {
												$name  = str_replace( '{', '', $options[ $ii ] );
												$name  = str_replace( '}', '', $name );
												$name  = str_replace( $name, $name . '_' . ( $i ), $options[ $ii ] );
												$value = SUPER_Common::email_tags( $name, $data, $settings );
											} else {
												// @since 1.0.3 - in case static value is used
												$value = $options[ $ii ];
											}
											$message .= '<input type="hidden" name="' . $v . '_' . $i . '" value="' . $value . '">';
										}
										++$ii;
									}
									++$i;
								}
								break;
							}
						}
						++$absolute_key;
					}
				}

				// Subscriptions checkout
				if ( $cmd == '_xclick-subscriptions' ) {
					if ( ! empty( $settings['paypal_item_name'] ) ) {
						// e.g: Alice\'s Weekly Digest
						$message .= '<input type="hidden" name="item_name" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_item_name'], $data, $settings ) ) . '">';
					}
					if ( ! empty( $settings['paypal_item_number'] ) ) {
						// e.g: DIG Weekly
						$message .= '<input type="hidden" name="item_number" value="' . esc_attr( SUPER_Common::email_tags( $settings['paypal_item_number'], $data, $settings ) ) . '">';
					}

					// Add allo periods
					$periods = explode( "\n", $settings['paypal_subscription_periods'] );
					$periods = array_reverse( $periods );
					$counter = 3;
					foreach ( $periods as $k => $v ) {
						$options     = explode( '|', $v );
						$amount      = SUPER_Common::email_tags( $options[0], $data, $settings );
						$amount      = self::tofloat( $amount );
						$period      = SUPER_Common::email_tags( $options[1], $data, $settings );
						$time_format = SUPER_Common::email_tags( $options[2], $data, $settings );
						// a3 - the price of the subscription e.g: 5.00
						$message .= '<input type="hidden" name="a' . $counter . '" value="' . esc_attr( $amount ) . '">';
						// p3 - the period of the subscription e.g: 7 (for 7 days if t1 has value of D)
						$message .= '<input type="hidden" name="p' . $counter . '" value="' . esc_attr( $period ) . '">';
						// t3 - the time format for the period e.g: D=days, W=weeks, M=months, Y=years
						$message .= '<input type="hidden" name="t' . $counter . '" value="' . esc_attr( $time_format ) . '">';
						--$counter;
						// Check if we only have 1 trial period:
						if ( count( $periods ) == 2 ) {
							--$counter;
						}
					}

					// Set recurring payments until canceled.
					$message .= '<input type="hidden" name="src" value="1">';
				}

				$message .= '</form>';
				$message .= '<script data-cfasync="false" type="text/javascript" language="javascript">';
				$message .= 'document.getElementById("super_paypal_' . $atts['post']['form_id'] . '").submit();';
				$message .= '</script>';
				if ( $settings['form_show_thanks_msg'] == 'true' ) {
					if ( $settings['form_thanks_title'] != '' ) {
						$settings['form_thanks_title'] = '<h1>' . $settings['form_thanks_title'] . '</h1>';
					}
					$msg = do_shortcode( $settings['form_thanks_title'] . nl2br( $settings['form_thanks_description'] ) );
				}
				SUPER_Common::output_message(
					array(
						'error'    => false,
						'msg'      => $msg . $message,
						'redirect' => false,
						'fields'   => array(),
						'display'  => true,
						'loading'  => true,
					)
				);
			}
		}


		/**
		 * Hook into settings and add PayPal settings
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
			$currencies = array();
			foreach ( self::$currency_codes as $k => $v ) {
				$currencies[ $k ] = $k . ' - ' . $v['name'] . ' (' . $v['symbol'] . ')';
			}
			$array['paypal_checkout'] = array(
				'hidden' => 'settings',
				'name'   => esc_html__( 'PayPal Checkout', 'super-forms' ),
				'label'  => esc_html__( 'PayPal Checkout', 'super-forms' ),
				'fields' => array(
					'paypal_checkout'                     => array(
						'default' => '',
						'type'    => 'checkbox',
						'filter'  => true,
						'values'  => array(
							'true' => esc_html__( 'Enable PayPal Checkout', 'super-forms' ),
						),
					),
					'paypal_mode'                         => array(
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'sandbox' => esc_html__( 'Enable PayPal Sandbox mode (for testing purposes only)', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'paypal_checkout',
						'filter_value' => 'true',
					),
					// @since 1.3.0 - Conditionally PayPal Checkout
					'conditionally_paypal_checkout'       => array(
						'hidden_setting' => true,
						'default'        => '',
						'type'           => 'checkbox',
						'filter'         => true,
						'values'         => array(
							'true' => esc_html__( 'Conditionally checkout to PayPal', 'super-forms' ),
						),
						'parent'         => 'paypal_checkout',
						'filter_value'   => 'true',
					),
					'conditionally_paypal_checkout_check' => array(
						'hidden_setting' => true,
						'type'           => 'conditional_check',
						'name'           => esc_html__( 'Only checkout to PayPal when following condition is met', 'super-forms' ),
						'label'          => esc_html__( 'You are allowed to enter field {tags} to do the check', 'super-forms' ),
						'default'        => '',
						'placeholder'    => '{fieldname},value',
						'filter'         => true,
						'parent'         => 'conditionally_paypal_checkout',
						'filter_value'   => 'true',
					),
					'paypal_merchant_email'               => array(
						'name'         => esc_html__( 'PayPal merchant email (to receive payments)', 'super-forms' ),
						'desc'         => esc_html__( 'Your PayPal ID or an email address associated with your PayPal account. E-mail addresses must be confirmed.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_checkout',
						'filter_value' => 'true',
					),
					'paypal_currency_code'                => array(
						'name'         => esc_html__( 'PayPal currency code', 'super-forms' ),
						'default'      => 'USD',
						'type'         => 'select',
						'values'       => $currencies,
						'filter'       => true,
						'parent'       => 'paypal_checkout',
						'filter_value' => 'true',
					),

					// Select wether or not to prompt buyers for a shipping address
					'paypal_no_shipping'                  => array(
						'name'         => esc_html__( 'Select whether or not to prompt buyers for a shipping address.', 'super-forms' ),
						'default'      => '0',
						'type'         => 'select',
						'values'       => array(
							'0' => 'Prompt for an address, but do not require one.',
							'1' => 'Do not prompt for an address.',
							'2' => 'Prompt for an address and require one.',
						),
						'filter'       => true,
						'parent'       => 'paypal_checkout',
						'filter_value' => 'true',
					),

					'paypal_payment_type'                 => array(
						'name'         => esc_html__( 'PayPal payment method', 'super-forms' ),
						'default'      => 'product',
						'type'         => 'select',
						'values'       => array(
							'product'      => esc_html__( 'Single product or service checkout', 'super-forms' ),
							'donation'     => esc_html__( 'Donation checkout', 'super-forms' ),
							'subscription' => esc_html__( 'Subscription checkout', 'super-forms' ),
							'cart'         => esc_html__( 'Cart checkout (for multiple product checkout)', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'paypal_checkout',
						'filter_value' => 'true',
					),

					// PRODUCT & DONATION CHECKOUT SETTINGS

						// Item description
						// Description of item. If you omit this variable, buyers enter their own name during checkout.
						'paypal_item_name'                => array(
							'name'         => esc_html__( 'Item description (leave blank to let users enter a name)', 'super-forms' ),
							'desc'         => esc_html__( 'Description of item. If you omit this variable, buyers enter their own name during checkout.', 'super-forms' ),
							'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
							'default'      => 'Flower (roses)',
							'type'         => 'text',
							'filter'       => true,
							'parent'       => 'paypal_payment_type',
							'filter_value' => 'product,donation,subscription',
							'allow_empty'  => true,
						),

					// Item price
					// The price or amount of the product, service, or contribution, not including shipping, handling, or tax. If you omit this variable from Buy Now or Donate buttons, buyers enter their own amount at the time of payment.
					'paypal_item_amount'                  => array(
						'name'         => esc_html__( 'Item price (leave blank to let user enter their own price)', 'super-forms' ),
						'desc'         => esc_html__( 'The price or amount of the product, service, or contribution, not including shipping, handling, or tax. If you omit this variable from Buy Now or Donate buttons, buyers enter their own amount at the time of payment.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}. (only decimal format is allowed e.g: 16.95)', 'super-forms' ),
						'default'      => '5.00',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'product,donation',
						'allow_empty'  => true,
					),

					// SINGLE PRODUCT CHECKOUT SETTINGS

						// Quantity (Number of items)
						// Note: The value for quantity must be a positive integer. Null, zero, or negative numbers are not allowed.
						'paypal_item_quantity'            => array(
							'name'         => esc_html__( 'Quantity (Number of items)', 'super-forms' ),
							'desc'         => esc_html__( 'Note: The value for quantity must be a positive integer. Null, zero, or negative numbers are not allowed.', 'super-forms' ),
							'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
							'default'      => '1',
							'type'         => 'text',
							'filter'       => true,
							'parent'       => 'paypal_payment_type',
							'filter_value' => 'product',
							'allow_empty'  => true,
						),

					// Weight of item
					// If profile-based shipping rates are configured with a basis of weight, the sum of weight values is used to calculate the shipping charges for the payment. A valid value is a decimal number with two significant digits to the right of the decimal point.
					'paypal_item_weight'                  => array(
						'name'         => esc_html__( 'Weight of item (leave blank for none)', 'super-forms' ),
						'desc'         => esc_html__( 'If profile-based shipping rates are configured with a basis of weight, the sum of weight values is used to calculate the shipping charges for the payment. A valid value is a decimal number with two significant digits to the right of the decimal point.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'product',
					),

					// Item number (to track product or service)
					// Pass-through variable for you to track product or service purchased or the contribution made. The value you specify is passed back to you upon payment completion.
					'paypal_item_number'                  => array(
						'name'         => esc_html__( 'Item number (to track product or service)', 'super-forms' ),
						'desc'         => esc_html__( 'Pass-through variable for you to track product or service purchased or the contribution made. The value you specify is passed back to you upon payment completion.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'product',
					),

					// Discount amount (leave blank for no discount)
					// Discount amount associated with an item, which must be less than the selling price of the item.
					'paypal_item_discount_amount'         => array(
						'name'         => esc_html__( 'Discount amount (leave blank for no discount)', 'super-forms' ),
						'desc'         => esc_html__( 'Discount amount associated with an item, which must be less than the selling price of the item.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'product',
					),

					// Discount rate (leave blank for no discount)
					// Discount rate, as a percentage, associated with an item. Set to a value less than 100
					'paypal_item_discount_rate'           => array(
						'name'         => esc_html__( 'Discount rate (leave blank for no discount)', 'super-forms' ),
						'desc'         => esc_html__( 'Discount rate, as a percentage, associated with an item. Set to a value less than 100', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'product',
					),

					// Discount number
					// Number of additional quantities of the item to which the discount applies.
					'paypal_item_discount_num'            => array(
						'name'         => esc_html__( 'Discount number', 'super-forms' ),
						'desc'         => esc_html__( 'Number of additional quantities of the item to which the discount applies.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'product',
					),

					// Shipping cost. The cost of shipping this item. (applies to first item added to cart)
					'paypal_item_shipping'                => array(
						'name'         => esc_html__( 'Shipping cost (applies to first item added to cart)', 'super-forms' ),
						'desc'         => esc_html__( 'The cost of shipping this item.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'product',
					),
					// Shipping cost. The cost of shipping this item. (applies to each additional item added to cart)
					// The cost of shipping each additional unit of this item.
					// If you omit this variable and profile-based shipping rates are configured, buyers are charged an amount according to the shipping methods they choose.
					'paypal_item_shipping2'               => array(
						'name'         => esc_html__( 'Shipping cost (applies to each additional item added to cart)', 'super-forms' ),
						'desc'         => esc_html__( 'The cost of shipping each additional unit of this item.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'product',
					),

					// SUBSCRIPTION CHECKOUT SETTINGS

						// Subscription settings
						'paypal_subscription_periods'     => array(
							'name'         => esc_html__( 'Subscription periods', 'super-forms' ),
							'desc'         => esc_html__( 'Here you can setup the subscription price, time and periods', 'super-forms' ),
							'label'        => sprintf( esc_html__( 'You are allowed to use {tags}.%1$sPut each period on a new line, separate values by pipes, for example:%1$s%2$s7 day trial for free:%3$s 0|7|D%1$s%2$sAfter trial 3 weeks for 5 dollar:%3$s 5|3|W%1$s%2$sAfter that $49.99 for each year:%3$s 49.99|1|Y%1$s%2$sTime format options:%3$s D=days, W=weeks, M=months, Y=years', 'super-forms' ), '<br />', '<strong>', '</strong>' ),
							'default'      => '',
							'type'         => 'textarea',
							'placeholder'  => "0|7|D\n5|3|W\n49.99|1|Y",
							'filter'       => true,
							'parent'       => 'paypal_payment_type',
							'filter_value' => 'subscription',
						),

					// CART CHECKOUT SETTINGS

						// Cart items
						// Items to be added to cart
						// Here you can enter the items that need to be added to the cart after form submission
						'paypal_cart_items'               => array(
							'name'         => esc_html__( 'Items to be added to cart', 'super-forms' ),
							'desc'         => esc_html__( 'Here you can enter the items that need to be added to the cart after form submission', 'super-forms' ),
							'label'        => sprintf(
								esc_html__(
									'You are allowed to use {tags}.%1$s Put each item on a new line, separate values by pipes%1$sLeave options blank that you do not wish to use, for example:%1$s%1$s%2$sTo add 5 times a 3.49 dollar product write it like below:%3$s%1$s3.49|5|Flowers%1$s%1$s%2$sBelow you can see a full example with {tags}:%3$s%1$s{price}|{quantity}|{item_name}|{tax}|{shipping}|{shipping2}|{discount_amount}|{discount_rate}%1$s%1$sFor more information about each option read the %4$sPayPal\'s Variable Reference%5$s',
									'super-forms'
								),
								'<br />',
								'<strong>',
								'</strong>',
								'<a target="_blank" href="https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/#individual-items-variables">',
								'</a>'
							),
							'default'      => '',
							'type'         => 'textarea',
							'placeholder'  => "3.49|5|Flowers\n7.25|3|Towels",
							'filter'       => true,
							'parent'       => 'paypal_payment_type',
							'filter_value' => 'cart',
						),

					// Cart-wide tax, overriding any individual item tax_x value
					'paypal_tax_cart'                     => array(
						'name'         => esc_html__( 'Cart tax', 'super-forms' ),
						'desc'         => esc_html__( 'Cart-wide tax, overriding any individual item tax value', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'cart',
					),

					// If profile-based shipping rates are configured with a basis of weight, PayPal uses this value to calculate the shipping charges for the payment. This value overrides the weight values of individual items.
					'paypal_weight_cart'                  => array(
						'name'         => esc_html__( 'Cart weight', 'super-forms' ),
						'desc'         => esc_html__( 'This value overrides the weight values of individual items. If profile-based shipping rates are configured with a basis of weight, PayPal uses this value to calculate the shipping charges for the payment.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'cart',
					),

					// Single discount amount charged cart-wide.
					// It must be less than the selling price of all items combined in the cart. This variable overrides any individual item discount_amount_x values, if present.
					'paypal_discount_amount_cart'         => array(
						'name'         => esc_html__( 'Cart discount amount', 'super-forms' ),
						'desc'         => esc_html__( 'Single discount amount charged cart-wide. It must be less than the selling price of all items combined in the cart.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'cart',
					),

					// Single Discount rate, as a percentage, to be charged cart-wide.
					// Set to a value less than 100. The variable overrides any individual item discount_rate
					'paypal_discount_rate_cart'           => array(
						'name'         => esc_html__( 'Cart discount rate', 'super-forms' ),
						'desc'         => esc_html__( 'Single Discount rate, as a percentage, to be charged cart-wide. Set to a value less than 100. The variable overrides any individual item discount rate', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'cart',
					),

					// Single handling fee charged cart-wide. If handling_cart is used in multiple Add to Cart buttons, the handling_cart value of the first item is used.
					'paypal_handling_cart'                => array(
						'name'         => esc_html__( 'Cart handling fee', 'super-forms' ),
						'desc'         => esc_html__( 'Single handling fee charged cart-wide.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_payment_type',
						'filter_value' => 'cart',
					),

					// GENERAL CHECKOUT SETTINGS

						// Custom return URL
						'paypal_custom_return_url'        => array(
							'default'      => '',
							'type'         => 'checkbox',
							'values'       => array(
								'true' => esc_html__( 'Enable custom return URL', 'super-forms' ),
							),
							'filter'       => true,
							'parent'       => 'paypal_checkout',
							'filter_value' => 'true',
						),

					// PayPal return URL
					// The URL to which PayPal redirects buyers' browser after they complete their payments.
					// For example, specify a URL on your site that displays a hank you for your payment page.
					'paypal_return_url'                   => array(
						'name'         => esc_html__( 'PayPal return URL (when user successfully returns from paypal)', 'super-forms' ),
						'desc'         => esc_html__( 'The URL to which PayPal posts information about the payment, in the form of Instant Payment Notification messages.', 'super-forms' ),
						'label'        => esc_html__( 'User will be redirected to this URL after making a payment', 'super-forms' ),
						'default'      => get_home_url() . '/my-custom-thank-you-page',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_return_url',
						'filter_value' => 'true',
						'allow_empty'  => true,
					),

					// Cancel URL when order was canceled by the user
					// A URL to which PayPal redirects the buyers' browsers if they cancel checkout before completing their payments.
					// For example, specify a URL on your website that displays the Payment Canceled page.
					'paypal_cancel_url'                   => array(
						'name'         => esc_html__( 'PayPal cancel URL (when payment is canceled by user)', 'super-forms' ),
						'label'        => esc_html__( 'User that cancels payment will be redirected to this URL', 'super-forms' ),
						'default'      => get_home_url() . '/my-custom-canceled-page',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_checkout',
						'filter_value' => 'true',
						'allow_empty'  => true,
					),

					// ADVANCED PAYPAL SETTINGS
					'paypal_advanced_settings'            => array(
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'true' => esc_html__( 'Show Advanced PayPal Settings', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'paypal_checkout',
						'filter_value' => 'true',
					),

					'paypal_lc'                           => array(
						'name'         => esc_html__( 'Language for the billing information/log-in page', 'super-forms' ),
						'desc'         => esc_html__( 'Sets the language for the billing information/log-in page only. Default is US.', 'super-forms' ),
						'label'        => sprintf( esc_html__( 'You are allowed to use {tags}.%1$sFor valid values, see %2$sCountries and Regions Supported by PayPal%3$s.', 'super-forms' ), '<br />', '<a href="https://developer.paypal.com/docs/classic/api/country_codes/">', '</a>' ),
						'default'      => 'US',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),
					'paypal_charset'                      => array(
						'name'         => esc_html__( 'Character set and character encoding for the billing information/log-in page', 'super-forms' ),
						'desc'         => esc_html__( 'Sets the character set and character encoding for the billing information/log-in page on the PayPal website. In addition, this variable sets the same values for information that you send to PayPal in your HTML button code. Default is based on the language encoding settings in your account profile.', 'super-forms' ),
						'label'        => sprintf( esc_html__( 'You are allowed to use {tags}.%1$sFor valid values, see %2$sSetting the Character Set — charset%3$s.', 'super-forms' ), '<br />', '<a href="https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/formbasics/#setting-the-character-set--charset">', '</a>' ),
						'default'      => 'UTF-8',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),
					'paypal_handling'                     => array(
						'name'         => esc_html__( 'Handling charges', 'super-forms' ),
						'desc'         => esc_html__( 'This variable is not quantity-specific. The same handling cost applies, regardless of the number of items on the order.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),
					'paypal_undefined_quantity'           => array(
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'true' => esc_html__( 'Allow buyers to specify the quantity', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),
					'paypal_weight_unit'                  => array(
						'name'         => esc_html__( 'Select weight unit', 'super-forms' ),
						'desc'         => esc_html__( 'The unit of measure if weight is specified.', 'super-forms' ),
						'default'      => 'lbs',
						'type'         => 'select',
						'values'       => array(
							'lbs' => 'lbs (default)',
							'kgs' => 'kgs',
						),
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),
					'paypal_invoice'                      => array(
						'name'         => esc_html__( 'Invoice number', 'super-forms' ),
						'desc'         => esc_html__( 'Use to identify your invoice number for this purchase.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),
					'paypal_night_phone_a'                => array(
						'name'         => esc_html__( 'The area code for U.S. phone numbers, or the country code for phone numbers outside the U.S.', 'super-forms' ),
						'desc'         => esc_html__( 'PayPal fills in the buyer\'s home phone number automatically.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),
					'paypal_night_phone_b'                => array(
						'name'         => esc_html__( 'The three-digit prefix for U.S. phone numbers, or the entire phone number for phone numbers outside the U.S., excluding country code.', 'super-forms' ),
						'desc'         => esc_html__( 'PayPal fills in the buyer\'s home phone number automatically.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),
					'paypal_night_phone_c'                => array(
						'name'         => esc_html__( 'The four-digit phone number for U.S. phone numbers.', 'super-forms' ),
						'desc'         => esc_html__( 'PayPal fills in the buyer\'s home phone number automatically.', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),

					// ADDRESS PAYPAL SETTINGS
					'paypal_custom_address'               => array(
						'desc'         => esc_html__( 'Parse the entered address information to paypal. This will not override the PayPal member\'s default address unless you enable the \'Override\' option below.', 'super-forms' ),
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'true' => esc_html__( 'Parse address to paypal based on form input data.', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'paypal_checkout',
						'filter_value' => 'true',
					),

					'paypal_address_override'             => array(
						'desc'         => esc_html__( 'The address specified with automatic fill-in variables overrides the PayPal member\'s stored address. Buyers see the addresses that you pass in, but they cannot edit them. PayPal does not show addresses if they are invalid or omitted.', 'super-forms' ),
						'default'      => '',
						'type'         => 'checkbox',
						'values'       => array(
							'true' => esc_html__( 'Override the PayPal member\'s stored address', 'super-forms' ),
						),
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),
					'paypal_first_name'                   => array(
						'name'         => esc_html__( 'First name', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),
					'paypal_last_name'                    => array(
						'name'         => esc_html__( 'Last name', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),
					'paypal_email'                        => array(
						'name'         => esc_html__( 'E-mail address', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),
					'paypal_address1'                     => array(
						'name'         => esc_html__( 'Street (1 of 2 fields)', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),
					'paypal_address2'                     => array(
						'name'         => esc_html__( 'Street (2 of 2 fields)', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),
					'paypal_city'                         => array(
						'name'         => esc_html__( 'City', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),
					'paypal_state'                        => array(
						'name'         => esc_html__( 'U.S. state', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),
					'paypal_zip'                          => array(
						'name'         => esc_html__( 'Postal code', 'super-forms' ),
						'label'        => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),
					'paypal_country'                      => array(
						'name'         => esc_html__( 'Shipping and billing country', 'super-forms' ),
						'desc'         => esc_html__( 'Sets shipping and billing country.', 'super-forms' ),
						'label'        => sprintf( esc_html__( 'You are allowed to use {tags}.%1$sFor valid values, see %2$sCountry and Region Codes%3$s.', 'super-forms' ), '<br />', '<a target="_blank" href="https://developer.paypal.com/docs/classic/api/country_codes/">', '</a>' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_custom_address',
						'filter_value' => 'true',
					),

					// moved to triggers 'paypal_completed_entry_status' => array(
					// moved to triggers    'name' => esc_html__( 'Entry status after payment completed', 'super-forms' ),
					// moved to triggers    'label' => sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' ),
					// moved to triggers    'default' =>  'completed',
					// moved to triggers    'type' => 'select',
					// moved to triggers    'values' => $statuses,
					// moved to triggers    'filter' => true,
					// moved to triggers    'parent' => 'paypal_checkout',
					// moved to triggers    'filter_value' => 'true',
					// moved to triggers ),
					'paypal_notify_url'                   => array(
						'name'         => esc_html__( 'PayPal notify URL (only for developers!)', 'super-forms' ),
						'label'        => esc_html__( 'Used for IPN (Instant payment notifications) when payment is confirmed by paypal', 'super-forms' ),
						'default'      => '',
						'type'         => 'text',
						'filter'       => true,
						'parent'       => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),

					// moved to triggers // option to send email after payment completed
					// moved to triggers 'paypal_completed_email' => array(
					// moved to triggers     'name' => esc_html__( 'Send email after payment completed', 'super-forms' ),
					// moved to triggers     'label' => esc_html__( 'Note: this will only work if you save a contact entry', 'super-forms' ),
					// moved to triggers     'default' =>  '',
					// moved to triggers     'type' => 'checkbox',
					// moved to triggers     'values' => array(
					// moved to triggers         'true' => esc_html__( 'Send email after payment completed', 'super-forms' ),
					// moved to triggers     ),
					// moved to triggers     'filter' => true,
					// moved to triggers     'parent' => 'paypal_checkout',
					// moved to triggers     'filter_value' => 'true',
					// moved to triggers ),

				),
			);

			// moved to triggers // option to send email after payment completed
			// moved to triggers $fields = $array['confirmation_email_settings']['fields'];
			// moved to triggers $new_fields = array();
			// moved to triggers foreach($fields as $k => $v){
			// moved to triggers     if($k=='confirm'){
			// moved to triggers         unset($fields[$k]);
			// moved to triggers         continue;
			// moved to triggers     }
			// moved to triggers     if( !empty($v['parent']) ) {
			// moved to triggers         if($v['parent']=='confirm'){
			// moved to triggers             $v['parent'] = 'paypal_completed_email';
			// moved to triggers             $v['filter_value'] = 'true';
			// moved to triggers         }else{
			// moved to triggers             $v['parent'] = str_replace('confirm', 'paypal_completed', $v['parent']);
			// moved to triggers         }
			// moved to triggers     }
			// moved to triggers     unset($fields[$k]);
			// moved to triggers     $k = str_replace('confirm', 'paypal_completed', $k);
			// moved to triggers     //$v['default'] = SUPER_Settings::get_value( $default, $k, $settings, $v['default'] );
			// moved to triggers     $new_fields[$k] = $v;
			// moved to triggers }
			// moved to triggers $array['paypal_checkout']['fields'] = array_merge($array['paypal_checkout']['fields'], $new_fields);
			// moved to triggers $array['paypal_checkout']['fields']['paypal_completed_attachments'] = array(
			// moved to triggers     'name' => esc_html__( 'Attachments for paypal completed emails:', 'super-forms' ),
			// moved to triggers     'label' => esc_html__( 'Upload a file to send as attachment', 'super-forms' ),
			// moved to triggers     'default' =>  '',
			// moved to triggers     'type' => 'file',
			// moved to triggers     'multiple' => 'true',
			// moved to triggers     'filter' => true,
			// moved to triggers     'parent' => 'paypal_completed_email',
			// moved to triggers     'filter_value' => 'true',
			// moved to triggers );

			// moved to triggers if (class_exists('SUPER_Frontend_Posting')) {
			// moved to triggers    $array['paypal_checkout']['fields']['paypal_completed_post_status'] = array(
			// moved to triggers        'name' => esc_html__( 'Post status after payment complete', 'super-forms' ),
			// moved to triggers        'label' => esc_html__( 'Only used for Front-end posting', 'super-forms' ),
			// moved to triggers        'default' =>  'publish',
			// moved to triggers        'type' => 'select',
			// moved to triggers        'values' => array(
			// moved to triggers            'publish' => esc_html__( 'Publish (default)', 'super-forms' ),
			// moved to triggers            'future' => esc_html__( 'Future', 'super-forms' ),
			// moved to triggers            'draft' => esc_html__( 'Draft', 'super-forms' ),
			// moved to triggers            'pending' => esc_html__( 'Pending', 'super-forms' ),
			// moved to triggers            'private' => esc_html__( 'Private', 'super-forms' ),
			// moved to triggers            'trash' => esc_html__( 'Trash', 'super-forms' ),
			// moved to triggers            'auto-draft' => esc_html__( 'Auto-Draft', 'super-forms' ),
			// moved to triggers        ),
			// moved to triggers        'filter' => true,
			// moved to triggers        'parent' => 'paypal_checkout',
			// moved to triggers        'filter_value' => 'true',
			// moved to triggers    );
			// moved to triggers }
			// moved to triggers if (class_exists('SUPER_Register_Login')) {
			// moved to triggers    global $wp_roles;
			// moved to triggers    $all_roles = $wp_roles->roles;
			// moved to triggers    $editable_roles = apply_filters( 'editable_roles', $all_roles );
			// moved to triggers    $roles = array();
			// moved to triggers    foreach( $editable_roles as $k => $v ) {
			// moved to triggers        $roles[$k] = $v['name'];
			// moved to triggers    }
			// moved to triggers    $array['paypal_checkout']['fields']['paypal_completed_signup_status'] = array(
			// moved to triggers        'name' => esc_html__( 'Registered user login status after payment complete', 'super-forms' ),
			// moved to triggers        'label' => esc_html__( 'Only used for Register & Login feature', 'super-forms' ),
			// moved to triggers        'default' =>  'active',
			// moved to triggers        'type' => 'select',
			// moved to triggers        'values' => array(
			// moved to triggers            'active' => esc_html__( 'Active (default)', 'super-forms' ),
			// moved to triggers            'pending' => esc_html__( 'Pending', 'super-forms' ),
			// moved to triggers            'blocked' => esc_html__( 'Blocked', 'super-forms' ),
			// moved to triggers        ),
			// moved to triggers        'filter' => true,
			// moved to triggers        'parent' => 'paypal_checkout',
			// moved to triggers        'filter_value' => 'true',
			// moved to triggers    );
			// moved to triggers    $array['paypal_checkout']['fields']['paypal_completed_user_role'] = array(
			// moved to triggers        'name' => esc_html__( 'Change user role after payment complete', 'super-forms' ),
			// moved to triggers        'label' => esc_html__( 'Only used for Register & Login feature', 'super-forms' ),
			// moved to triggers        'default' =>  '',
			// moved to triggers        'type' => 'select',
			// moved to triggers        'values' => array_merge($roles, array('' => esc_html__( 'Do not change role', 'super-forms' ))),
			// moved to triggers        'filter' => true,
			// moved to triggers        'parent' => 'paypal_checkout',
			// moved to triggers        'filter_value' => 'true',
			// moved to triggers    );
			// moved to triggers }
			return $array;
		}
	}
endif;


/**
 * Returns the main instance of SUPER_PayPal to prevent the need to use globals.
 *
 * @return SUPER_PayPal
 */
if ( ! function_exists( 'SUPER_PayPal' ) ) {
	function SUPER_PayPal() {
		return SUPER_PayPal::instance();
	}
	// Global for backwards compatibility.
	$GLOBALS['SUPER_PayPal'] = SUPER_PayPal();
}
