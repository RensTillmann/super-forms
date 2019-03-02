<?php
/**
 * Super Forms - PayPal Checkout
 *
 * @package   Super Forms - PayPal Checkout
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - PayPal Checkout
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Checkout with PayPal after form submission. Charge users for registering or posting content.
 * Version:     1.0.4
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('SUPER_PayPal')):
	

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
		public $version = '1.0.4';

		
		/**
		 * @var string
		 *
		 *  @since      1.0.0
		 */
		public $add_on_slug = 'paypal_checkout';
		public $add_on_name = 'PayPal Checkout';


		/**
		 * @var array
		 *
		 *  @since      1.0.0
		 */
		public static $currency_codes = array(
			'AUD' => array( 'symbol' => '$', 'name' => 'Australian Dollar' ),
			'BRL' => array( 'symbol' => 'R$', 'name' => 'Brazilian Real' ),
			'CAD' => array( 'symbol' => '$', 'name' => 'Canadian Dollar' ),
			'CZK' => array( 'symbol' => '&#75;&#269;', 'name' => 'Czech Koruna' ),
			'DKK' => array( 'symbol' => '&#107;&#114;', 'name' => 'Danish Krone' ),
			'EUR' => array( 'symbol' => '&#128;', 'name' => 'Euro' ),
			'HKD' => array( 'symbol' => '&#20803;', 'name' => 'Hong Kong Dollar' ),
			'HUF' => array( 'symbol' => '&#70;&#116;', 'name' => 'Hungarian Forint', 'decimal' => true ),
			'ILS' => array( 'symbol' => '&#8362;', 'name' => 'Israeli New Sheqel' ),
			'JPY' => array( 'symbol' => '&#165;', 'name' => 'Japanese Yen', 'decimal' => true ),
			'MYR' => array( 'symbol' => '&#82;&#77;', 'name' => 'Malaysian Ringgit' ),
			'MXN' => array( 'symbol' => '&#36;', 'name' => 'Mexican Peso' ),
			'NOK' => array( 'symbol' => '&#107;&#114;', 'name' => 'Norwegian Krone' ),
			'NZD' => array( 'symbol' => '&#36;', 'name' => 'New Zealand Dollar' ),
			'PHP' => array( 'symbol' => '&#80;&#104;&#11;', 'name' => 'Philippine Peso' ),
			'PLN' => array( 'symbol' => '&#122;&#322;', 'name' => 'Polish Zloty' ),
			'GBP' => array( 'symbol' => '&#163;', 'name' => 'Pound Sterling' ),
			'RUB' => array( 'symbol' => '&#1088;&#1091;', 'name' => 'Russian Ruble' ),
			'SGD' => array( 'symbol' => '&#36;', 'name' => 'Singapore Dollar' ),
			'SEK' => array( 'symbol' => '&#107;&#114;', 'name' => 'Swedish Krona' ),
			'CHF' => array( 'symbol' => '&#67;&#72;&#70;', 'name' => 'Swiss Franc' ),
			'TWD' => array( 'symbol' => '&#36;', 'name' => 'Taiwan New Dollar', 'decimal' => true ),
			'THB' => array( 'symbol' => '&#3647;', 'name' => 'Thai Baht' ),
			'USD' => array( 'symbol' => '$', 'name' => 'U.S. Dollar' )
		);
		public static $paypal_payment_statuses = array(
			'Canceled_Reversal' => array(
				 'label' => 'Canceled Reversal',
				 'desc' => 'A reversal has been canceled. For example, you won a dispute with the customer, and the funds for the transaction that was reversed have been returned to you.'
			),
			'Completed' => array(
 				'label' => 'Completed',
 				'desc' => 'The payment has been completed, and the funds have been added successfully to your account balance.'
			),
			'Created' => array(
 				'label' => 'Created',
 				'desc' => 'A German ELV payment is made using Express Checkout.'
			),
			'Denied' => array(
 				'label' => 'Denied',
 				'desc' => 'The payment was denied. This happens only if the payment was previously pending because of one of the reasons listed for the pending_reason variable or the Fraud_Management_Filters_x variable.'
			),
			'Expired' => array(
 				'label' => 'Expired',
 				'desc' => 'This authorization has expired and cannot be captured.'
			),
			'Failed' => array(
 				'label' => 'Failed',
 				'desc' => 'The payment has failed. This happens only if the payment was made from your customer\'s bank account.'
			),
			'Pending' => array(
 				'label' => 'Pending',
 				'desc' => 'The payment is pending.',
			),
			'Refunded' => array(
 				'label' => 'Refunded',
 				'desc' => 'You refunded the payment.',
				// See 'pending_reason' for more information.
			),
			'Reversed' => array(
 				'label' => 'Reversed',
 				'desc' => 'A payment was reversed due to a chargeback or other type of reversal. The funds have been removed from your account balance and returned to the buyer. The reason for the reversal is specified in the ReasonCode element.', // See pending_reason for more information.
				// See 'ReasonCode' for more information.
			),
			'Processed' => array(
 				'label' => 'Processed',
 				'desc' => 'A payment has been accepted.',
			),
			'Voided' => array(
 				'label' => 'Voided',
 				'desc' => 'This authorization has been voided.',
			)
		);


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
		public static function instance(){
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}


		/**
		 * SUPER_PayPal Constructor.
		 *
		 *  @since      1.0.0
		 */
		public function __construct(){
			$this->init_hooks();
			do_action('super_paypal_loaded');
		}


		/**
		 * Define constant if not already set
		 *
		 * @param  string $name
		 * @param  string|bool $value
		 *
		 *  @since      1.0.0
		 */
		private function define($name, $value){
			if (!defined($name)) {
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
			switch ($type) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined('DOING_AJAX');
			case 'cron':
				return defined('DOING_CRON');
			case 'frontend':
				return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
			}
		}
		

		/**
		 * Hook into actions and filters
		 *
		 *  @since      1.0.0
		 */
		private function init_hooks(){
			
			// Filters since 1.0.0
			register_deactivation_hook( __FILE__, array( $this, 'deactivate'));
			add_filter( 'super_after_activation_message_filter', array( $this, 'activation_message' ), 10, 2 );
			add_filter( 'super_after_contact_entry_data_filter', array( $this, 'add_entry_order_link' ), 10, 2 );
			

			// Actions since 1.0.0
			add_action( 'init', array( $this, 'register_post_types' ), 5 );
			add_action( 'parse_request', array( $this, 'paypal_ipn'));

			if ($this->is_request('admin')) {

				// Filters since 1.0.0
				add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );
				add_filter( 'super_settings_end_filter', array( $this, 'activation' ), 100, 2 );
				add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 1 );
				add_filter( 'manage_super_paypal_txn_posts_columns', array( $this, 'super_paypal_txn_columns' ), 999999 );
				add_filter( 'manage_super_paypal_sub_posts_columns', array( $this, 'super_paypal_sub_columns' ), 999999 );
				add_filter( 'super_enqueue_styles', array( $this, 'backend_styles' ) );

				// Actions since 1.0.0
				add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
				add_action( 'init', array( $this, 'update_plugin'));
				add_action( 'init', array( $this, 'custom_paypal_txn_status' ) );
				add_action( 'admin_footer-post.php', array( $this, 'append_paypal_txn_status_list' ) );
				add_action( 'manage_super_paypal_txn_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );
				add_action( 'manage_super_paypal_sub_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );

				add_action( 'current_screen', array( $this, 'after_screen' ), 0 );
				add_action( 'current_screen', array( $this, 'reset_paypal_counter' ) );
				add_action( 'restrict_manage_posts', array( $this, 'filter_form_dropdown' ) );

			}
			if ($this->is_request('ajax')) {
				// Actions since 1.0.0
				add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ) );
			}


			// Actions since 1.0.0
			add_action( 'super_front_end_posting_after_insert_post_action', array( $this, 'save_post_id' ) );
			add_action( 'super_after_wp_insert_user_action', array( $this, 'save_user_id' ) );

		}

        


        /**
         * Adjust filter/search for transactions and subscriptions
         *
         * @param  string $current_screen
         * 
         * @since		1.0.0
        */
        public function after_screen( $current_screen ) {
            if( $current_screen->id=='edit-super_paypal_txn' ) {
                add_filter( 'posts_where', array( $this, 'custom_posts_where' ), 0, 2 );
                add_filter( 'posts_join', array( $this, 'custom_posts_join' ), 0, 2 );
                add_filter( 'posts_groupby', array( $this, 'custom_posts_groupby' ), 0, 2 );
            }
        }

        /**
         * Add form filter dropdown
         *
         *  @since      1.0.0
        */
		public static function filter_form_dropdown($post_type) {
            if( $post_type=='super_paypal_txn') {
                echo '<select name="super_form_filter">';
                $args = array(
                    'post_type' => 'super_form',
                    'posts_per_page' => -1
                );
                $forms = get_posts( $args );
                if(count($forms)==0){
                    echo '<option value="0">' . __( 'No forms found', 'super-forms' ) . '</option>';
                }else{
                    $super_form_filter = (isset($_GET['super_form_filter']) ? $_GET['super_form_filter'] : 0);
                    echo '<option value="0">' . __( 'All forms', 'super-forms' ) . '</option>';
                    foreach( $forms as $value ) {
                        echo '<option value="' . $value->ID . '" ' . ($value->ID==$super_form_filter ? 'selected="selected"' : '') . '>' . $value->post_title . '</option>';
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
            $table = $wpdb->prefix . 'posts';
            $table_meta = $wpdb->prefix . 'postmeta';
            $where = "";
            if( (isset($_GET['s'])) && ($_GET['s']!='') ) {
                $s = sanitize_text_field($_GET['s']);
                $where .= "AND (";
                    $where .= "($table.post_title LIKE '%$s%') OR";
                    $where .= "($table_meta.meta_key = '_super_txn_data' AND $table_meta.meta_value LIKE '%$s%')"; // @since 3.4.0 - custom entry status
                $where .= ")";
            }
            if( (isset($_GET['super_form_filter'])) && (absint($_GET['super_form_filter'])!=0) ) {
                $super_form_filter = absint($_GET['super_form_filter']);
                $where .= "AND (";
                    $where .= "($table.post_parent = $super_form_filter)";
                $where .= ")";
            }
            if( (isset($_GET['post_status'])) && ($_GET['post_status']!='') && ($_GET['post_status']!='all') ) {
                $post_status = sanitize_text_field($_GET['post_status']);
                $where .= "AND (";
                    $where .= "($table.post_status = '$post_status')";
                $where .= ")";
            }else{
                $where .= "AND (";
                    $where .= "($table.post_status != 'trash')";
                $where .= ")";     
            }
            $where .= "AND (";
                $where .= "($table.post_type = 'super_paypal_txn')";
            $where .= ")";
            return $where;
        }

        /**
         * Hook into the join query to filter custom meta data
         *
         *  @since      1.0.0
        */
        public static function custom_posts_join( $join, $object ) {
            if( (isset($_GET['s'])) && ($_GET['s']!='') ) {
                global $wpdb;
                $prefix = $wpdb->prefix;
                $table_posts = $wpdb->prefix . 'posts';
                $table_meta = $wpdb->prefix . 'postmeta';
                $join = "INNER JOIN $table_meta ON $table_meta.post_id = $table_posts.ID";
            }
            return $join;
        }

        /**
         * Hook into the groupby query to filter custom meta data
         *
         *  @since      1.0.0
        */
        public static function custom_posts_groupby( $groupby, $object ) {
            if( (isset($_GET['s'])) && ($_GET['s']!='') ) {
                global $wpdb;
                $table = $wpdb->prefix . 'posts';
                $groupby = "$table.ID";
            }
            return $groupby;
        }


		/**
		 * Save Post ID into session after inserting post with Front-end Posting Add-on
		 * This way we can add it to the paypal custom data and use it later to update the user status after payment is completed
		 * array( 'post_id'=>$post_id, 'data'=>$data, 'atts'=>$atts )
		 *
		 *  @since      1.0.0
		 */
		public function save_post_id($data) {
			SUPER_Forms()->session->set( '_super_paypal_post_id', absint($data['post_id']) );
		}

		/**
		 * Save User ID into session after creating user Front-end Register & Login add-on
		 * This way we can add it to the paypal custom data and use it later to update the user status after payment is completed
		 * array( 'user_id'=>$user_id, 'atts'=>$atts )
		 *
		 *  @since      1.0.0
		 */
		public function save_user_id($data) {
			SUPER_Forms()->session->set( '_super_paypal_user_id', absint($data['user_id']) );
		}


		/**
		 * Display activation message for automatic updates
		 *
		 *  @since      1.0.0
		 */
		public function reset_paypal_counter($current_screen){
			if($current_screen->post_type == 'super_paypal_txn'){
				update_option( 'super_paypal_txn_count', 0 );
			}
			if($current_screen->post_type == 'super_paypal_sub'){
				update_option( 'super_paypal_sub_count', 0 );
			}
		}


		/**
		 * Display activation message for automatic updates
		 *
		 *  @since      1.0.0
		 */
		public function display_activation_msg(){
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
		 *  @since      1.0.0
		 */
		function update_plugin(){
			if (defined('SUPER_PLUGIN_DIR')) {
				require_once (SUPER_PLUGIN_DIR . '/includes/admin/update-super-forms.php');
				$plugin_remote_path = 'http://f4d.nl/super-forms/';
				$plugin_slug = plugin_basename(__FILE__);
				new SUPER_WP_AutoUpdate($this->version, $plugin_remote_path, $plugin_slug, '', '', $this->add_on_slug);
			}
		}


		/**
		 * Add the activation under the "Activate" TAB
		 *
		 * @since       1.0.0
		 */
		public function activation($array, $data){
			if (method_exists('SUPER_Forms', 'add_on_activation')) {
				return SUPER_Forms::add_on_activation($array, $this->add_on_slug, $this->add_on_name);
			}
			else {
				return $array;
			}
		}


		/**
		 *  Deactivate
		 *
		 *  Upon plugin deactivation delete activation
		 *
		 *  @since      1.0.0
		 */
		public static function deactivate() {
			if (method_exists('SUPER_Forms', 'add_on_deactivate')) {
				SUPER_Forms::add_on_deactivate(SUPER_PayPal()->add_on_slug);
			}
		}


		/**
		 * Check license and show activation message
		 *
		 * @since       1.0.0
		 */
		public function activation_message($activation_msg, $data) {
			if (method_exists('SUPER_Forms', 'add_on_activation_message')) {
				$form_id = absint($data['id']);
				$settings = $data['settings'];
				if ((isset($settings['paypal_checkout'])) && ($settings['paypal_checkout'] == 'true')) {
					return SUPER_Forms::add_on_activation_message($activation_msg, $this->add_on_slug, $this->add_on_name);
				}
			}
			return $activation_msg;
		}


		/**
		 * Enqueue styles
		 *
		 *  @since      1.0.0
		 */
		public function backend_styles($array){
            $assets_path    = str_replace( array( 'http:', 'https:' ), '', plugin_dir_url( __FILE__ ) ) . 'assets/';
            $backend_path   = $assets_path . 'css/backend/';
            $array['super-paypal-txn'] = array(
                'src'     => $backend_path . 'paypal-txn.css',
                'deps'    => '',
                'version' => $this->version,
                'media'   => 'all',
                'screen'  => array( 
                    'edit-super_paypal_txn',
                    'admin_page_super_paypal_txn',
                    'edit-super_paypal_sub',
                    'admin_page_super_paypal_sub'
                ),
                'method'  => 'enqueue',
            );
            if(isset($array['super-font-awesome'])){
            	$array['super-font-awesome']['screen'][] = 'edit-super_paypal_txn';
            	$array['super-font-awesome']['screen'][] = 'admin_page_super_paypal_txn';
            	$array['super-font-awesome']['screen'][] = 'edit-super_paypal_sub';
            	$array['super-font-awesome']['screen'][] = 'admin_page_super_paypal_sub';
            }
			return $array;
		}


		/**
		 * Change row actions
		 *
		 *  @since      1.0.0
		 */
		public static function remove_row_actions( $actions ) {
		    if( (get_post_type()==='super_paypal_txn') || (get_post_type()==='super_paypal_sub') ) {
		        if( isset( $actions['trash'] ) ) {
		            $trash = $actions['trash'];
		            unset( $actions['trash'] );
		        }
		        unset( $actions['inline hide-if-no-js'] );
		        unset( $actions['view'] );
		        unset( $actions['edit'] );
		        $actions['view'] = '<a href="admin.php?page=super_paypal_txn&id=' . get_the_ID() . '">View</a>';
		        if(get_post_type()==='super_paypal_sub'){
		        	$actions['view'] = '<a href="admin.php?page=super_paypal_sub&id=' . get_the_ID() . '">View</a>';
		        }
		        if( isset( $trash ) ) {
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
		public static function super_paypal_txn_columns($columns){
    		
    		$settings = get_option( 'super_settings' );
    		$GLOBALS['backend_contact_entry_status'] = SUPER_Settings::get_entry_statuses($settings);

    		foreach($columns as $k => $v) {
				if (($k != 'title') && ($k != 'cb')) {
					unset($columns[$k]);
				}
			}
			$columns['title'] = 'Transaction ID'; // post_title
			$columns['pp_status'] = 'Payment status'; // payment_status
			$columns['pp_payer_email'] = 'E-mail'; // payer_email
			$columns['pp_invoice'] = 'Invoice'; // invoice
			$columns['pp_item'] = 'Quantity — Item'; // item_name + quantity
			$columns['pp_hidden_form_id'] = 'Based on Form'; // hidden_form_id
			$columns['date'] = 'Date'; // payment_date
			return $columns;

			//address_status
			//payer_status

		}


		/**
		 * Custom subscriptions columns
		 *
		 *  @since      1.0.0
		 */
		public static function super_paypal_sub_columns($columns){
    		
    		$settings = get_option( 'super_settings' );
    		$GLOBALS['backend_contact_entry_status'] = SUPER_Settings::get_entry_statuses($settings);

    		foreach($columns as $k => $v) {
				if (($k != 'title') && ($k != 'cb')) {
					unset($columns[$k]);
				}
			}
			$columns['title'] = 'Subscription ID'; // post_title
			$columns['pp_status'] = 'Status'; // payment_status
			$columns['pp_payer_email'] = 'Name / E-mail'; // first_name + last_name / payer_email
			$columns['pp_invoice'] = 'Invoice'; // invoice
			$columns['pp_item'] = 'Recurring Payment'; // item_name + quantity
			$columns['pp_initial_payment'] = 'Trial Period'; // a1,t1,p1 / a2,t2,p2
			$columns['pp_trial_period'] = 'Trial Period 2'; // a1,t1,p1 / a2,t2,p2
			$columns['pp_hidden_form_id'] = 'Based on Form'; // hidden_form_id
			$columns['date'] = 'Date'; // payment_date
			return $columns;

			//address_status
			//payer_status

		}

		public static function get_amount_per_cycle($txn_data){
			if( isset($txn_data['amount_per_cycle']) ) return $txn_data['amount_per_cycle'];
			if( isset($txn_data['mc_amount3']) ) return $txn_data['mc_amount3'];
		}
		public static function get_currency_code($txn_data){
			if( isset($txn_data['currency_code']) ) return $txn_data['currency_code'];
			if( isset($txn_data['mc_currency']) ) return $txn_data['mc_currency'];
		}
		public static function get_product_item_name($txn_data){
			if( isset($txn_data['item_name']) ) return $txn_data['item_name'];
			if( isset($txn_data['product_name']) ) return $txn_data['product_name'];
		}
		public static function get_payment_cycle($txn_data, $period=3){
			$payment_cycle = '';
			if( isset($txn_data['payment_cycle']) ) {
				$payment_cycle = $txn_data['payment_cycle'];
			}
			if( isset($txn_data['period'.$period]) ) {
				$payment_cycle = $txn_data['period'.$period];
				$payment_cycle = explode(" ", $payment_cycle);

				if( $period>2 ) {
					if( $payment_cycle[0]>1 ) {
						switch( $payment_cycle[1] ) {
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
					}else{
						switch( $payment_cycle[1] ) {
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
				}else{
					if( $payment_cycle[0]>1 ) {
						switch( $payment_cycle[1] ) {
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
					}else{
						switch( $payment_cycle[1] ) {
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
			}
			return $payment_cycle;
		}
		public static function super_custom_columns($column, $post_id) {
			$txn_data = get_post_meta( $post_id, '_super_txn_data', true );
			$custom = explode( '|', $txn_data['custom'] );

			// Get currency code e.g: EUR
			$currency_code = self::get_currency_code($txn_data);
			$symbol = self::$currency_codes[$currency_code]['symbol'];

			// Get product/item name
			$product_name = self::get_product_item_name($txn_data);

			// Get amount per cycle
			$amount_per_cycle = self::get_amount_per_cycle($txn_data);

			switch ($column) {
			    case 'pp_status':
			    	if( ($txn_data['txn_type']=='subscr_signup') || ($txn_data['txn_type']=='subscr_modify') || ($txn_data['txn_type']=='subscr_cancel') || ($txn_data['txn_type']=='recurring_payment_suspended') ) {
				        $entry_status = 'Active';
				        $entry_status_desc = '';
				        if( isset($txn_data['profile_status']) ) {
				        	$entry_status = $txn_data['profile_status'];
				        	$entry_status_desc = $entry_status;
				        }
				        if( $txn_data['txn_type']=='recurring_payment_suspended' ) {
				        	$entry_status_desc = 'This profile has been suspended, and no further amounts will be collected.';				        
				        }
				        if( $txn_data['txn_type']=='subscr_cancel' ) {
				        	$entry_status = 'Canceled';
				        	$entry_status_desc = 'This recurring payment plan has been canceled and cannot be reactivated. No more recurring payments will be made.';
				        }
						echo '<span title="' . esc_attr($entry_status_desc) . '" class="super-txn-status super-txn-status-' . strtolower($entry_status) . '">' . $entry_status . '</span>';
			    	}else{
				        $entry_status = $txn_data['payment_status'];
				        $value = self::$paypal_payment_statuses[$entry_status];
				        $statuses = $GLOBALS['backend_contact_entry_status'];
				        if( (isset($statuses[$entry_status])) && ($entry_status!='') ) {
				            echo '<span title="' . esc_attr($value['desc']) . '" class="super-txn-status super-txn-status-' . strtolower($entry_status) . '" style="color:' . $statuses[$entry_status]['color'] . ';background-color:' . $statuses[$entry_status]['bg_color'] . '">' . $value['label'] . '</span>';
				        }else{
							echo '<span title="' . esc_attr($value['desc']) . '" class="super-txn-status super-txn-status-' . strtolower($entry_status) . '">' . $value['label'] . '</span>';
				        }
					}			    
					break;
			    case 'pp_payer_email':
			    	$tooltip = '';
			    	if($txn_data['payer_status']=='verified'){
			    		$tooltip = '<i title="Customer has a verified PayPal account" class="fa fa-check-circle super-paypal-txn-verified" aria-hidden="true"></i>';
			    	}
			    	if($txn_data['payer_status']=='unverified'){
			    		$tooltip = '<i title="Customer has an unverified PayPal account" class="fa fa-exclamation-circle super-paypal-txn-unverified" aria-hidden="true"></i>';
			    	}
			    	echo '<span class="pp-name-email">';
			    	echo $tooltip;
			    	echo '<strong>' . $txn_data['first_name'] . ' ' . $txn_data['last_name'] . '</strong><br />';
			        echo $txn_data['payer_email'];
			        echo '</span>';
			        break;
			    case 'pp_invoice':
			        echo (isset($txn_data['invoice']) ? $txn_data['invoice'] : '');
			        break;
			    case 'pp_item':
			    	if($txn_data['txn_type']=='cart'){
			        	$i=1;
			        	while( isset($txn_data['item_name'.$i]) ) {
			        		echo $txn_data['quantity'.$i] . 'x — <strong>' . $txn_data['item_name'.$i] . '</strong><br />';
			        		$i++;
			        	}
			    	}else{
			    		if( ($txn_data['txn_type']=='subscr_payment') || ($txn_data['txn_type']=='subscr_signup') || ($txn_data['txn_type']=='subscr_modify') || ($txn_data['txn_type']=='subscr_cancel') || ($txn_data['txn_type']=='recurring_payment_suspended') ) {
			    			if($txn_data['txn_type']=='subscr_payment'){
			    				echo '1x — <strong>' . $txn_data['item_name'] . '</strong><br />';
			    				echo '(' . $symbol . number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code . ')';
							}else{
			    				echo '<strong>' . $product_name . '</strong><br />';
								// Get payment cycle
								$payment_cycle = self::get_payment_cycle($txn_data, 3);
			    				echo '(' . $payment_cycle . ': ' . $symbol . number_format_i18n($amount_per_cycle, 2) . ' ' . $currency_code . ')';
			    			}
			    		}else{
				        	echo $txn_data['quantity'] . 'x — <strong>' . $txn_data['item_name'] . '</strong><br />';
				        	echo '(' . $symbol . number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code . ')';
			    		}
			    	}
			        break;
			    case 'pp_initial_payment':
					if( isset($txn_data['mc_amount1']) ) {
						// Get payment cycle
						$payment_cycle = self::get_payment_cycle($txn_data, 1);
						echo '(' . $payment_cycle . ': ' . $symbol . number_format_i18n($txn_data['mc_amount1'], 2) . ' ' . $currency_code . ')';
					}
			        break;
			    case 'pp_trial_period':
					if( isset($txn_data['mc_amount2']) ) {
						// Get payment cycle
						$payment_cycle = self::get_payment_cycle($txn_data, 2);
						echo '(' . $payment_cycle . ': ' . $symbol . number_format_i18n($txn_data['mc_amount2'], 2) . ' ' . $currency_code . ')';
					}
			        break;
			    case 'pp_hidden_form_id':
			    	$form_id = absint($custom[0]);
					if ($form_id == 0) {
						echo __( 'Unknown', 'super-forms');
					} else {
						$form = get_post($form_id);
						if (isset($form->post_title)) {
							echo '<a href="admin.php?page=super_create_form&id=' . $form->ID . '">' . $form->post_title . '</a>';
						}
						else {
							echo __( 'Unknown', 'super-forms');
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
			foreach(self::$paypal_payment_statuses as $k => $v) {
				register_post_status($k, array(
					'label' => $v['label'],
					'public' => true,
					'exclude_from_search' => false,
					'show_in_admin_all_list' => true,
					'show_in_admin_status_list' => true,
					'label_count' => _n_noop($v['label'] . ' <span class="count">(%s)</span>', $v['label'] . ' <span class="count">(%s)</span>' ),
				));
			}
		}
		public static function append_paypal_txn_status_list() {
			global $post;
			$complete = '';
			$label = '';
			if ($post->post_type == 'super_paypal_txn') {
				foreach(self::$paypal_payment_statuses as $k => $v) {
					if ($post->post_status == $k) {
						$complete = ' selected="selected"';
						$label = '<span id="post-status-display"> ' . $v['label'] . '</span>';
					}
					echo '<script>
					jQuery(document).ready(function($){
					$("select#post_status").append("<option value="archive" ' . $complete . '>Archive</option>");
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
			if (!post_type_exists('super_paypal_txn')) {
				register_post_type('super_paypal_txn', apply_filters('super_register_post_type_super_paypal_txn', array(
					'label' => 'PayPal Transactions',
					'description' => '',
					'public' => true,
					'show_ui' => true,
					'show_in_menu' => false,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => array(
						'slug' => 'super_paypal_txn',
						'with_front' => true
					),
					'exclude_from_search' => true, // make sure to exclude from default search
					'query_var' => true,
					'supports' => array(),
					'capabilities' => array(
						'create_posts' => false, // Removes support for the "Add New" function
					),
					'labels' => array(
						'name' => 'PayPal Transactions',
						'singular_name' => 'PayPal Transaction',
						'menu_name' => 'PayPal Transactions',
						'add_new' => 'Add Transaction',
						'add_new_item' => 'Add New Transaction',
						'edit' => 'Edit',
						'edit_item' => 'Edit Transaction',
						'new_item' => 'New Transaction',
						'view' => 'View Transaction',
						'view_item' => 'View Transaction',
						'search_items' => 'Search Transactions',
						'not_found' => 'No Transactions Found',
						'not_found_in_trash' => 'No Transactions Found in Trash',
						'parent' => 'Parent Transaction',
					)
				)));
			}
			if (!post_type_exists('super_paypal_sub')) {
				register_post_type('super_paypal_sub', apply_filters('super_register_post_type_super_paypal_sub', array(
					'label' => 'PayPal Subscriptions',
					'description' => '',
					'public' => true,
					'show_ui' => true,
					'show_in_menu' => false,
					'capability_type' => 'post',
					'map_meta_cap' => true,
					'hierarchical' => false,
					'rewrite' => array(
						'slug' => 'super_paypal_sub',
						'with_front' => true
					),
					'exclude_from_search' => true, // make sure to exclude from default search
					'query_var' => true,
					'supports' => array(),
					'capabilities' => array(
						'create_posts' => false, // Removes support for the "Add New" function
					),
					'labels' => array(
						'name' => 'PayPal Subscriptions',
						'singular_name' => 'PayPal Subscription',
						'menu_name' => 'PayPal Subscriptions',
						'add_new' => 'Add Subscription',
						'add_new_item' => 'Add New Subscription',
						'edit' => 'Edit',
						'edit_item' => 'Edit Subscription',
						'new_item' => 'New Subscription',
						'view' => 'View Subscription',
						'view_item' => 'View Subscription',
						'search_items' => 'Search Subscriptions',
						'not_found' => 'No Subscriptions Found',
						'not_found_in_trash' => 'No Subscriptions Found in Trash',
						'parent' => 'Parent Subscription',
					)
				)));
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
			$count = get_option( 'super_paypal_txn_count', 5 );
			if( $count>0 ) {
				$count = ' <span class="update-plugins"><span class="plugin-count">' . $count . '</span></span>';
			}else{
				$count = '';
			}
			add_submenu_page(
				'super_forms', 
				__( 'PayPal Transactions', 'super-forms' ),
				'<span class="super-pp-icon" style="' . $styles . '"></span>' . __( 'Transactions', 'super-forms' ) . $count,
				'manage_options', 
				'edit.php?post_type=super_paypal_txn'
			);
			add_submenu_page(
				null, 
				__( 'View PayPal transaction', 'super-forms' ), 
				__( 'View PayPal transaction', 'super-forms' ), 
				'manage_options', 
				'super_paypal_txn', 
				'SUPER_PayPal::paypal_transaction'
			);

			// Subscriptions menu
			$count = get_option( 'super_paypal_sub_count', 2 );
			if( $count>0 ) {
				$count = ' <span class="update-plugins"><span class="plugin-count">' . $count . '</span></span>';
			}else{
				$count = '';
			}
			add_submenu_page(
				'super_forms', 
				__( 'PayPal Subscriptions', 'super-forms' ),
				'<span class="super-pp-icon" style="' . $styles . '"></span>' . __( 'Subscriptions', 'super-forms' ) . $count,
				'manage_options', 
				'edit.php?post_type=super_paypal_sub'
			);
			add_submenu_page(
				null, 
				__( 'View PayPal subscription', 'super-forms' ), 
				__( 'View PayPal subscription', 'super-forms' ), 
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
			if ( (FALSE === get_post_status($id)) && (get_post_type($id)!='super_paypal_txn') ) {
			  	// The post does not exist
				echo 'This transaction does not exist.';
			} else {
			  	// The post exists
		        $date = get_the_date(false,$id);
		        $time = get_the_time(false,$id);
				$txn_data = get_post_meta( $id, '_super_txn_data', true );
				$custom = explode( '|', $txn_data['custom'] );
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
		                                    <span><?php echo __('Transaction Details', 'super-forms' ); ?>:</span>
		                                </h3>
		                                <div class="inside">
		                                    <div class="submitbox" id="submitpost">
		                                        <div id="minor-publishing">
		                                            <div class="misc-pub-section">
														<?php 
														$currency_code = self::get_currency_code($txn_data);
														$mc_gross = number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code;
														?>
		                                                <span><?php echo __( 'Gross amount', 'super-forms' ) . ':'; ?> <strong><?php echo $mc_gross; ?></strong></span>
		                                            </div>
		                                            <div class="misc-pub-section">
		                                                <span><?php echo __( 'Transaction ID', 'super-forms' ) . ':'; ?> <strong><?php echo get_the_title($id); ?></strong></span>
		                                            </div>
													<div class="misc-pub-section">
		                                                <span><?php echo __( 'Payment status', 'super-forms' ) . ':'; ?> <strong><?php echo $txn_data['payment_status']; ?></strong></span>
		                                            </div>
													<div class="misc-pub-section">
		                                                <span><?php echo __( 'Payer E-mail', 'super-forms' ) . ':'; ?> <strong><?php echo $txn_data['payer_email']; ?></strong></span>
		                                            </div>
		                                            <div class="misc-pub-section">
		                                                <span><?php echo __( 'Payment type', 'super-forms' ) . ':'; ?> <strong><?php echo $txn_data['payment_type']; ?></strong></span>
		                                            </div>
													<div class="misc-pub-section">
		                                                <span><?php echo __('Submitted', 'super-forms' ) . ':'; ?> <strong><?php echo $date.' @ '.$time; ?></strong></span>
		                                            </div>

													<?php
													if( (isset($custom[3])) && ($custom[3]!=0) ) {
														$user_info = get_userdata($custom[3]);
														echo '<div class="misc-pub-section">';
		                                                	echo '<span>' . __( 'Submitted by user', 'super-forms' ) . ': <a href="' . get_edit_user_link($user_info->ID) . '"><strong>' . $user_info->display_name . '</strong></a></span>';
		                                            	echo '</div>';
		                                           	}
													if( (isset($custom[2])) && ($custom[2]!=0) ) {
														echo '<div class="misc-pub-section">';
		                                                	echo '<span>' . __( 'Contact Entry', 'super-forms' ) . ': <a href="admin.php?page=super_contact_entry&id=' . $custom[0] . '"><strong>' . get_the_title($custom[2]) . '</strong></a></span>';
		                                            	echo '</div>';
		                                           	}
		                                           	
													// Get subscription
													$sub_id = 0;
													if( isset($txn_data['subscr_id']) ) {
														$sub_id = sanitize_text_field( $txn_data['subscr_id'] );
													}
													if( isset($txn_data['recurring_payment_id']) ) {
														$sub_id = sanitize_text_field( $txn_data['recurring_payment_id'] );
													}
													global $wpdb;
													$post_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta AS meta INNER JOIN $wpdb->posts AS post ON post.id = meta.post_id WHERE post.post_type = 'super_paypal_sub' AND meta_key = '_super_sub_id' AND meta_value = '$sub_id'");
													if(absint($post_id)!=0){
														echo '<div class="misc-pub-section">';
	                                                		echo '<span>' . __( 'Based on subscription', 'super-forms' ) . ': <a href="admin.php?page=super_paypal_sub&id=' . $post_id . '"><strong>' . $sub_id . '</strong></a></span>';
	                                            		echo '</div>';													
		                                           	}

		                                           	// Check if there was a post created 
		                                           	if( (isset($custom[4])) && ($custom[4]!=0) ) {
														$post_id = absint($custom[4]);
														$edit_link = get_edit_post_link($post_id);
														?>
														<div class="misc-pub-section">
		                                                	<?php echo '<span>' . __('Created Post', 'super-forms' ) . ':'; ?> <?php echo '<a href="' . $edit_link . '"><strong>' . get_the_title( $post_id ) . '</strong></a></span>'; ?>
		                                            	</div>
		                                           		<?php
		                                           	}

		                                           	// Check if there was a user created 
		                                           	if( !empty($custom[5]) ) {
														$user_id = absint($custom[5]);
														$edit_link = get_edit_user_link($user_id);
														$user_info = get_userdata( $user_id );
		                                           		if( $user_info ) { // @since 1.0.1 - check if user exists
															?>
															<div class="misc-pub-section">
			                                                	<?php echo '<span>' . __('Created User', 'super-forms' ) . ':'; ?> <?php echo '<a href="' . $edit_link . '"><strong>' . $user_info->user_login . '</strong></a></span>'; ?>
			                                            	</div>
			                                           		<?php
		                                           		}
		                                           	}
		                                           	?>

													<div class="misc-pub-section">
		                                                <?php echo '<span>' . __('Based on Form', 'super-forms' ) . ':'; ?> <?php echo '<a href="admin.php?page=super_create_form&id=' . $custom[0] . '"><strong>' . get_the_title( $custom[0] ) . '</strong></a></span>'; ?>
		                                            </div>

		                                            <div class="clear"></div>
		                                        </div>

		                                        <div id="major-publishing-actions">
		                                            <div id="delete-action">
		                                                <a class="submitdelete super-delete-contact-entry" data-contact-entry="<?php echo absint($id); ?>" href="#"><?php echo __('Move to Trash', 'super-forms' ); ?></a>
		                                            </div>
		                                            <div id="publishing-action">
		                                                <span class="spinner"></span>
		                                                <input name="print" type="submit" class="super-print-contact-entry button button-large" value="<?php echo __('Print', 'super-forms' ); ?>">
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
								$currency_code = self::get_currency_code($txn_data);
								$mc_gross = number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code;

		                        if( $txn_data['txn_type']!='subscr_payment' ) {
		                        	?>
			                        <div id="normal-sortables" class="meta-box-sortables ui-sortable">
			                            <div id="super-contact-entry-data" class="postbox ">
			                                <div class="handlediv" title="">
			                                    <br>
			                                </div>
			                                <h3 class="hndle ui-sortable-handle">
			                                    <span><?php echo __('Order details', 'super-forms' ); ?>:</span>
			                                </h3>
			                                <div class="inside">
			                                    <?php
			                                    echo '<table style="width:100%">';
			                                    	echo '<tr><th align="left">Item name</th><th align="right">Quantity</th><th align="right">Price</th><th align="right">Subtotal</th></tr>';
		                                            if(isset($txn_data['item_name'])){
		                                                echo '<tr>';
		                                                echo '<td align="left">' . $txn_data['item_name'] . '</td>';
		                                                echo '<td align="right">1</td>';
		                                                echo '<td align="right">' . number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code . '</td>';
		                                                echo '<td align="right">' . number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code . '</td>';
		                                                echo '</tr>';
		                                            }else{
		                                            	$i = 1;
			                                            while( isset($txn_data['item_name' . $i])) {
			                                                echo '<tr>';
			                                                echo '<td align="left">' . $txn_data['item_name' . $i] . '</td>';
			                                                echo '<td align="right">' . $txn_data['quantity' . $i] . '</td>';
			                                                echo '<td align="right">' . number_format_i18n(($txn_data['mc_gross_' . $i]/$txn_data['quantity' . $i]), 2) . ' ' . $currency_code . '</td>';
			                                                echo '<td align="right">' . number_format_i18n($txn_data['mc_gross_' . $i], 2) . ' ' . $currency_code . '</td>';
			                                                echo '</tr>';
			                                                $i++;
			                                            }
			                                        }
			                                    	echo '<tr><th colspan="3" align="right">Purchase total</th><td align="right">' . $mc_gross . '</td></tr>';
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
		                                    <span><?php echo __('Payment details', 'super-forms' ); ?>:</span>
		                                </h3>
		                                <div class="inside">
		                                    <?php
											if( $txn_data['address_country_code']=='US' ) {
												$located = 'inside';
											}else{
												$located = 'outside';
											}
											if( $txn_data['payer_status']=='verified' ) {
												$verified = '';
												$color = 'green';
											}else{
												$verified = 'NOT ';
												$color = 'red';
											}
											$verified_text = $txn_data['first_name'] . ' ' . $txn_data['last_name'] . '<br />';
											$verified_text .= 'The sender of this payment has <strong style="color:' . $color . ';">' . $verified . 'verified their account and is located ' . $located . ' the US.</strong><br />';
											$verified_text .= $txn_data['payer_email'];
											if( $txn_data['txn_type']=='subscr_payment' ) {
			                                    echo '<table>';
			                                    echo '<tr><th align="left">Gross amount</th><td align="right">' . $mc_gross . '</td></tr>';
			                                    echo '<tr><th align="left">PayPal fee</th><td align="right">' . number_format_i18n($txn_data['mc_fee'], 2) . ' ' . $currency_code . '</td></tr>';
			                                    echo '<tr><th align="left">Net amount</th><td align="right">' . number_format_i18n(($txn_data['mc_gross']-$txn_data['mc_fee']), 2) . ' ' . $currency_code . '</td></tr>';
			                                    echo '</table>';
			                                    echo '<table>';
			                                    echo '<tr><th align="left">Recurring Payment ID</th><td align="left">' . $txn_data['subscr_id'] . '</td></tr>';
			                                    echo '<tr><th align="left">Reason</th><td align="left">Recurring</td></tr>';
												echo '<tr>';
													echo '<th align="left" valign="top">Paid by</th>';
													echo '<td>';
														echo $verified_text;
													echo '</td>';
												echo '</tr>';
			                                    echo '<tr><th align="left">Memo</th><td align="left">' . $txn_data['item_name'] . '</td></tr>';
			                                    echo '</table>';
		                                    }else{
			                                    echo '<table>';
			                                    echo '<tr><th align="right">Purchase total</th><td align="right">' . $mc_gross . '</td></tr>';
			                                    echo '<tr><th align="right">Sales tax</th><td align="right">' . (isset($txn_data['tax']) ? number_format_i18n($txn_data['tax'], 2) : number_format_i18n(0, 2)) . ' ' . $currency_code . '</td></tr>';
												echo '<tr><th align="right">Shipping amount</th><td align="right">' . (isset($txn_data['mc_shipping']) ? number_format_i18n($txn_data['mc_shipping'], 2) : number_format_i18n(0, 2)) . ' ' . $currency_code . '</td></tr>';
			                                    echo '<tr><th align="right">Handling amount</th><td align="right">' . (isset($txn_data['mc_handling']) ? number_format_i18n($txn_data['mc_handling'], 2) : number_format_i18n(0, 2)) . ' ' . $currency_code . '</td></tr>';
			                                    echo '<tr><th align="right">Insurance</th><td align="right">' . (isset($txn_data['insurance_amount']) ? number_format_i18n($txn_data['insurance_amount'], 2) : number_format_i18n(0, 2)) . ' ' . $currency_code . '</td></tr>'; 
			                                    echo '<tr><th align="right">Gross amount</th><td align="right">' . $mc_gross . '</td></tr>';
			                                    echo '<tr><th align="right">PayPal fee</th><td align="right">' . number_format_i18n($txn_data['mc_fee'], 2) . ' ' . $currency_code . '</td></tr>';
			                                    echo '<tr><th align="right">Net amount</th><td align="right">' . number_format_i18n(($txn_data['mc_gross']-$txn_data['mc_fee']), 2) . ' ' . $currency_code . '</td></tr>';
			                                    if( (isset($txn_data['invoice'])) && ($txn_data['invoice']!='') ) {
													echo '<tr><th>Invoice ID</th><td>' . $txn_data['invoice'] . '</td></tr>';
			                                    }
												echo '</table>';
			                                    echo '<table>';
												echo '<tr>';
													echo '<th valign="top">Paid by</th>';
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
		                                    <span><?php echo __('Address', 'super-forms' ); ?>:</span>
		                                </h3>
		                                <div class="inside">
		                                    <?php
		                                    echo '<table>';
		                                    echo '<tr><th align="left">Name</th><td align="left">' . $txn_data['address_name'] . '</td></tr>';
		                                    echo '<tr><th align="left">Street</th><td align="left">' . $txn_data['address_street'] . '</td></tr>';
		                                    echo '<tr><th align="left">Zipcode</th><td align="left">' . $txn_data['address_zip'] . '</td></tr>';
		                                    echo '<tr><th align="left">City</th><td align="left">' . $txn_data['address_city'] . '</td></tr>';
		                                    echo '<tr><th align="left">State</th><td align="left">' . $txn_data['address_state'] . '</td></tr>';
		                                    echo '<tr><th align="left">Country</th><td align="left">' . $txn_data['address_country'] . ' (' . $txn_data['address_country_code'] . ')</td></tr>';
		                                    echo '<tr><th align="left">Address status</th><td align="left">' . $txn_data['address_status'] . '</td></tr>';
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
		                                    <span><?php echo __('Raw Transaction Data', 'super-forms' ); ?>:</span>
		                                </h3>
		                                <div class="inside">
		                                    <?php
		                                    echo '<table>';
	                                            foreach( $txn_data as $k => $v ) {
	                                                echo '<tr><th align="right">' . $k . '</th><td>' . $v . '</td></tr>';
	                                            }
		                                        echo apply_filters( 'super_after_paypal_txn_data_filter', '', array( 'paypal_txn_id'=>$_GET['id'], 'txn_data'=>$txn_data ) );
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
			if ( (FALSE === get_post_status($id)) && (get_post_type($id)!='super_paypal_sub') ) {
			  	// The post does not exist
				echo 'This subscription does not exist.';
			} else {
		        $date = get_the_date(false,$id);
		        $time = get_the_time(false,$id);
				$txn_data = get_post_meta( $id, '_super_txn_data', true );
				$custom = explode( '|', $txn_data['custom'] );
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
		                                    <span><?php echo __('Transaction Details', 'super-forms' ); ?>:</span>
		                                </h3>
		                                <div class="inside">
		                                    <div class="submitbox" id="submitpost">
		                                        <div id="minor-publishing">
		                                            <div class="misc-pub-section">
		                                                <span><?php echo __( 'Transaction ID', 'super-forms' ) . ':'; ?> <strong><?php echo get_the_title($id); ?></strong></span>
		                                            </div>
													<div class="misc-pub-section">
		                                                <span><?php echo __( 'Status', 'super-forms' ) . ':'; ?> <strong><?php echo (isset($txn_data['profile_status']) ? $txn_data['profile_status'] : __( 'Active', 'super-forms' )); ?></strong></span>
		                                            </div>
													<div class="misc-pub-section">
		                                                <span><?php echo __( 'Payer E-mail', 'super-forms' ) . ':'; ?> <strong><?php echo $txn_data['payer_email']; ?></strong></span>
		                                            </div>
		                                            <div class="misc-pub-section">
		                                                <span><?php echo __( 'Payment type', 'super-forms' ) . ':'; ?> <strong><?php echo __( 'Subscription', 'super-forms' ); ?></strong></span>
		                                            </div>
													<div class="misc-pub-section">
		                                                <span><?php echo __('Submitted', 'super-forms' ) . ':'; ?> <strong><?php echo $date.' @ '.$time; ?></strong></span>
		                                            </div>
													<div class="misc-pub-section">
		                                                <span><?php echo __('Based on Form', 'super-forms' ) . ':'; ?> <strong><?php echo '<a href="admin.php?page=super_create_form&id=' . $custom[0] . '">' . get_the_title( $custom[0] ) . '</a>'; ?></strong></span>
		                                            </div>

		                                            <div class="clear"></div>
		                                        </div>

		                                        <div id="major-publishing-actions">
		                                            <div id="delete-action">
		                                                <a class="submitdelete super-delete-contact-entry" data-contact-entry="<?php echo absint($id); ?>" href="#"><?php echo __('Move to Trash', 'super-forms' ); ?></a>
		                                            </div>
		                                            <div id="publishing-action">
		                                                <span class="spinner"></span>
		                                                <input name="print" type="submit" class="super-print-contact-entry button button-large" value="<?php echo __('Print', 'super-forms' ); ?>">
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
		                                    <span><?php echo __('Raw Transaction Data', 'super-forms' ); ?>:</span>
		                                </h3>
		                                <div class="inside">
		                                    <?php
		                                    echo '<table>';
	                                            foreach( $txn_data as $k => $v ) {
	                                                echo '<tr><th align="right">' . $k . '</th><td>' . $v . '</td></tr>';
	                                            }
		                                        echo apply_filters( 'super_after_paypal_txn_data_filter', '', array( 'paypal_txn_id'=>$_GET['id'], 'txn_data'=>$txn_data ) );
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

			if ((isset($_GET['page'])) && ($_GET['page'] == 'super_paypal_ipn')) {
	
				// txn_type options:
				//subscr_signup
				//subscr_cancel
				//subscr_modify
				//subscr_payment
				//subscr_failed
				//subscr_eot

				// When the subscription has expired due to cancelation or expiration (term has ended) we don't have to do anything other then notifying paypal that we received the IPN message.
				// The subscription has expired, either because the subscriber cancelled it or it has a fixed term (implying a fixed number of payments) and it has now expired with no further payments being due.
				if( (isset($_POST['txn_type'])) && ($_POST['txn_type']=='subscr_eot') ) {
					// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
					header("HTTP/1.1 200 OK");
					die();
				}

				// When the subscription payment has failed, not much we can do about this, and we don't have to do anything except let paypal know we received the IPN message
				if( (isset($_POST['txn_type'])) && ($_POST['txn_type']=='subscr_failed') ) {
					// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
					header("HTTP/1.1 200 OK");
					die();
				}

				// IPN message telling that the subscription is either being modified, suspended or canceled
				if( (isset($_POST['txn_type'])) && (($_POST['txn_type']=='subscr_modify') || ($_POST['txn_type']=='recurring_payment_suspended') || ($_POST['txn_type']=='subscr_cancel')) ) {

					// Get subscription ID
					if( isset($_POST['subscr_id']) ) {
						$sub_id = sanitize_text_field( $_POST['subscr_id'] );
					}
					if( isset($_POST['recurring_payment_id']) ) {
						$sub_id = sanitize_text_field( $_POST['recurring_payment_id'] );
					}

					// Get ID based on ipn tracking ID
					global $wpdb;
					$post_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta AS meta INNER JOIN $wpdb->posts AS post ON post.id = meta.post_id WHERE post.post_type = 'super_paypal_sub' AND meta_key = '_super_sub_id' AND meta_value = '$sub_id'");
					
					// Update data accordingly
					if( isset($_POST['subscr_id']) ) {
						update_post_meta( $post_id, '_super_sub_id', $_POST['subscr_id'] );
					}
					if( isset($_POST['recurring_payment_id']) ) {
						update_post_meta( $post_id, '_super_sub_id', $_POST['recurring_payment_id'] );
					}

					// If subscription is suspended
					if($_POST['txn_type']=='recurring_payment_suspended'){
						$post_txn_data = get_post_meta( $post_id, '_super_txn_data', true );
						$post_txn_data['txn_type'] = 'recurring_payment_suspended';
						$post_txn_data['profile_status'] = 'Suspended';
						update_post_meta( $post_id, '_super_txn_data', $post_txn_data );
					}
					
					// If subscription is canceled
					if( $_POST['txn_type']=='subscr_cancel' ) {
						$post_txn_data = get_post_meta( $post_id, '_super_txn_data', true );
						$post_txn_data['txn_type'] = 'subscr_cancel';
						$post_txn_data['profile_status'] = 'Canceled';
						update_post_meta( $post_id, '_super_txn_data', $post_txn_data );
					}

					// If subscription is modified
					if($_POST['txn_type']=='subscr_modify'){
						update_post_meta( $post_id, '_super_txn_data', $_POST );
					}

					do_action( 'super_after_paypal_ipn_subscription_changed', array( 'post_id'=>$post_id, 'txn_type'=>$_POST['txn_type'] ) );

					// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
					header("HTTP/1.1 200 OK");
					die();
				}

				// If payment status is Refunded
				if( (isset($_POST['payment_status'])) && ($_POST['payment_status']=='Refunded') ) {

					// Get ID based on ipn tracking ID
					global $wpdb;
					$parent_txn_id = sanitize_text_field($_POST['parent_txn_id']);
					$post_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_type = 'super_paypal_txn' AND post_title = '$parent_txn_id'");
					$post_txn_data = get_post_meta( $post_id, '_super_txn_data', true );
					$post_txn_data['payment_status'] = 'Refunded';
					update_post_meta( $post_id, '_super_txn_data', $post_txn_data );

					do_action( 'super_after_paypal_ipn_payment_refunded', array( 'post_id'=>$post_id ) );

					// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
					header("HTTP/1.1 200 OK");
					die();
				}

				// First retrieve the form settings
				$custom = apply_filters( 'super_paypal_custom_data_filter', $_POST['custom'] );
				$custom = explode('|', $custom);
				$form_id = $custom[0];
				if (!$form_id) return;
				if (absint($form_id) == 0) return;
				$settings = get_post_meta(absint($form_id), '_super_form_settings', true);
				if (!is_array($settings)) return;
				// Check the receiver email to see if it matches your list of paypal email addresses
				$merchant_emails = explode(',', $settings['paypal_merchant_email']);
				$email_found = false;
				foreach($merchant_emails as $email) {
					if ((strtolower($_POST["receiver_email"])) == (strtolower(trim($email)))) {
						$email_found = true;
						break;
					}
				}
				if ($email_found == false) return;
				// Set endpoint URL to post the verification data to
				if (!isset($settings['paypal_mode'])) $settings['paypal_mode'] = '';
				$url = 'https://www.' . ($settings['paypal_mode'] == 'sandbox' ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr';
				// Build the body of the verification post request, adding the _notify-validate command.
				$raw_post_data = file_get_contents('php://input');
				$raw_post_array = explode('&', $raw_post_data);
				$myPost = array();
				foreach($raw_post_array as $keyval) {
					$keyval = explode('=', $keyval);
					if (count($keyval) == 2) {
						// Since we do not want the plus in the datetime string to be encoded to a space, we manually encode it.
						if ($keyval[0] === 'payment_date') {
							if (substr_count($keyval[1], '+') === 1) {
								$keyval[1] = str_replace('+', '%2B', $keyval[1]);
							}
						}
						$myPost[$keyval[0]] = urldecode($keyval[1]);
					}
				}
				$req = 'cmd=_notify-validate';
				$get_magic_quotes_exists = false;
				if (function_exists('get_magic_quotes_gpc')) {
					$get_magic_quotes_exists = true;
				}
				foreach($myPost as $key => $value) {
					if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
						$value = urlencode(stripslashes($value));
					}
					else {
						$value = urlencode($value);
					}
					$req.= "&$key=$value";
				}
				// Post the data back to PayPal.
				$http = new WP_Http();
				$response = $http->post($url, array(
					'sslverify' => false,
					'ssl' => true,
					'body' => $req,
					'timeout' => 20
				));
				$http_code = $response['response']['code'];
				if ($http_code != 200) {
					update_option('super_ipn_error_log_' . $form_id . '_' . time(), "PayPal responded with http code $http_code");
					throw new Exception("PayPal responded with http code $http_code");
				}

				// Log IPN data
				update_option('super_ipn_log_' . $form_id . '_' . time(), ($_POST));

				// Check if PayPal verifies the IPN data, and if so, return true.
				if ((!is_wp_error($response)) && ($response['body'] == 'VERIFIED')) {
					$post_type = 'super_paypal_txn';
					if( $_POST['txn_type']=='subscr_signup' ) {
						$post_status = 'publish';
						$post_type = 'super_paypal_sub';
						$post_title = $_POST['subscr_id'];
					}else{
						$post_status = $_POST['payment_status'];
						$post_title = $_POST['txn_id'];
					}
					$post = array(
						'post_status' => sanitize_text_field($post_status),
						'post_type' => $post_type,
						'post_title' => sanitize_text_field($post_title),
						'post_parent' => absint($custom[0]),
						'post_author' => absint($custom[3])
					);
					$post_id = wp_insert_post($post);
					if(isset($_POST['subscr_id'])){
						add_post_meta($post_id, '_super_sub_id', $_POST['subscr_id']);
					}
					if(isset($_POST['recurring_payment_id'])){
						add_post_meta($post_id, '_super_sub_id', $_POST['recurring_payment_id']);
					}
					add_post_meta( $post_id, '_super_txn_data', $_POST );
					if( $_POST['txn_type']=='subscr_signup' ) {
						$count = get_option( 'super_paypal_sub_count', 0 );
						update_option( 'super_paypal_sub_count', ($count+1) );
					}else{
						$count = get_option( 'super_paypal_txn_count', 0 );
						update_option( 'super_paypal_txn_count', ($count+1) );
					}
					if( (isset($custom[2])) && ($custom[2]!=0) ) {
						$contact_entry_id = absint($custom[2]);

						// Save paypal order ID to contact entry
						update_post_meta( $contact_entry_id, '_super_contact_entry_paypal_order_id', $post_id );

						// Update contact entry status after succesfull payment
						if( !empty($settings['paypal_completed_entry_status']) ) {
							update_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['paypal_completed_entry_status'] );
						}
					}
					// Update post status after succesfull payment (only used for Front-end Posting add-on)
					$post_id = absint($custom[4]);
					if( ($post_id!=0) && (!empty($settings['paypal_completed_post_status'])) ) {
						wp_update_post( 
							array(
								'ID' => $post_id,
								'post_status' => $settings['paypal_completed_post_status']
							)
						);
					}
					// Update user status after succesfull payment (only used for Front-end Register & Login add-on)
					if( !empty($settings['register_login_action']) ) {
						if( $settings['register_login_action']=='register' ) {
							$user_id = absint($custom[5]);
							if( ($user_id!=0) && (!empty($settings['paypal_completed_signup_status'])) ) {
								update_user_meta( $user_id, 'super_user_login_status', $settings['paypal_completed_signup_status'] );
							}
						}
					}

					do_action( 'super_after_paypal_ipn_payment_verified', array( 'post_id'=>$post_id, 'post'=>$_POST ) );

				}
			}
			// Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
			header("HTTP/1.1 200 OK");
		}


		/**
		 * Add the PayPal order link to the entry info/data page
		 *
		 * @since       1.0.0
		 */
		public static function add_entry_order_link($result, $data) {
			$order_id = get_post_meta($data['entry_id'], '_super_contact_entry_paypal_order_id', true);
			if( !empty($order_id) ) {
				$order_id = absint($order_id);
				if ($order_id != 0) {
			        $url = admin_url() . 'admin.php?page=super_paypal_txn&id=' . $order_id;
			        if( get_post_type($order_id)==='super_paypal_sub' ) {
			        	$url = admin_url() . 'admin.php?page=super_paypal_sub&id=' . $order_id;
			        }
					$result.= '<tr><th align="right">' . __( 'PayPal Order', 'super-forms') . ':</th><td><span class="super-contact-entry-data-value">';
					$result.= '<a href="' . $url . '">' . get_the_title($order_id) . '</a>';
					$result.= '</span></td></tr>';
				}
			}
			return $result;
		}


		/**
		 * Hook into before sending email and check if we need to create or update a post or taxonomy
		 *
		 *  @since      1.0.0
		 */
		public static function before_email_success_msg($atts) {

			$settings = $atts['settings'];
			if (isset($atts['data'])) {
				$data = $atts['data'];
			}
			else {
				if ($settings['save_contact_entry'] == 'yes') {
					$data = get_post_meta($atts['entry_id'], '_super_contact_entry_data', true);
				}
				else {
					$data = $atts['post']['data'];
				}
			}
			if ((isset($settings['paypal_checkout'])) && ($settings['paypal_checkout'] == 'true')) {
				if (!isset($settings['paypal_mode'])) $settings['paypal_mode'] = '';
				if (!isset($settings['paypal_payment_type'])) $settings['paypal_payment_type'] = 'product';
				if (!isset($settings['paypal_merchant_email'])) $settings['paypal_merchant_email'] = '';
				if (!isset($settings['paypal_cancel_url'])) $settings['paypal_cancel_url'] = get_home_url();
				if (!isset($settings['paypal_custom_return_url'])) $settings['paypal_custom_return_url'] = '';
				if (!isset($settings['paypal_return_url'])) $settings['paypal_return_url'] = get_home_url();
				if (!isset($settings['paypal_currency_code'])) $settings['paypal_currency_code'] = 'USD';
				if (!isset($settings['paypal_item_amount'])) $settings['paypal_item_amount'] = '5.00';
				if (is_numeric($settings['paypal_item_amount'])) {
					$settings['paypal_item_amount'] = number_format((float)$settings['paypal_item_amount'], 2);
					if ((isset(self::$currency_codes[$settings['paypal_currency_code']]['decimal'])) && (self::$currency_codes[$settings['paypal_currency_code']]['decimal'] == true)) {
						$settings['paypal_item_amount'] = (float)$settings['paypal_item_amount'];
						$settings['paypal_item_amount'] = floor($settings['paypal_item_amount']);
					}
				}
				if ($settings['save_contact_entry'] != 'yes') {
					$atts['entry_id'] = 0;
				}

				// Get Post ID and save it in custom parameter for paypal so we can update the post status after successfull payment complete
				$post_id = SUPER_Forms()->session->get( '_super_paypal_post_id' );
				if( $post_id==false ) {
					$post_id = 0;
            	}

				// Get User ID and save it in custom parameter for paypal so we can update the user status after successfull payment complete
				$user_id = SUPER_Forms()->session->get( '_super_paypal_user_id' );
				if( $user_id==false ) {
					$user_id = 0;
            	}

				$custom = array(
					absint($atts['post']['form_id']),
					$settings['paypal_payment_type'],
					$atts['entry_id'],
					get_current_user_id(),
					absint($post_id), // Used only if Front-end Posting add-on is installed and enabled to update the post status after successfull payment.
					absint($user_id) // Used only if Front-end Register & Login add-on is installed and enabled to update the user status after successfull payment.
				);
				$home_url = get_home_url() . "/";
				if (strstr($home_url, '?')) {
					$return_url = $home_url . '&page=super_paypal_response'; // . absint($atts['entry_id']) . '|' . $form_id . '|' . $payment_type;
					$notify_url = $home_url . '&page=super_paypal_ipn';
				}
				else {
					$return_url = $home_url . '?page=super_paypal_response'; // . absint($atts['entry_id']) . '|' . $form_id . '|' . $payment_type;
					$notify_url = $home_url . '?page=super_paypal_ipn';
				}
				if ($settings['paypal_custom_return_url'] == 'true') {
					$return_url = $settings['paypal_return_url'];
				}
				
				$cmd = '_xclick';
				switch ($settings['paypal_payment_type']) {
				
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
				$action = 'https://www.' . ($settings['paypal_mode'] == 'sandbox' ? 'sandbox.' : '') . 'paypal.com/cgi-bin/webscr';
				$message = '';

				$message .= '<form target="_self" id="super_paypal_' . $atts['post']['form_id'] . '" action="' . $action . '" method="post">';

				// If continue shopping is enabled (e.g: custom URL redirect is enabled for the form)
	            if( !empty( $settings['form_redirect_option'] ) ) {
	                $redirect = null;
	                if( $settings['form_redirect_option']=='page' ) {
	                    $redirect = get_permalink( $settings['form_redirect_page'] );
	                }
	                if( $settings['form_redirect_option']=='custom' ) {
	                    $redirect = SUPER_Common::email_tags( $settings['form_redirect'], $data, $settings );
	                }
	                if($redirect!=null){
	                	$message .= '<input type="hidden" name="shopping_url" value="' . esc_url($redirect) . '">';
	                }
	            }


	            $message .= '<input type="hidden" name="cmd" value="' . $cmd . '">';

	            // Sets the character set and character encoding for the billing information/log-in page on the PayPal website. In addition, this variable sets the same values for information that you send to PayPal in your HTML button code. Default is based on the language encoding settings in your account profile.
				$message .= '<input type="hidden" name="charset" value="UTF-8">';

	            // The URL of the 150x50-pixel image displayed as your logo in the upper left corner of the PayPal checkout pages.
				// Default is your business name, if you have a PayPal Business account or your email address, if you have PayPal Premier or Personal account.
				if( !empty($settings['paypal_image_url']) ) {
					$message .= '<input type="hidden" name="image_url" value="">';
				}

				// Your PayPal ID or an email address associated with your PayPal account. Email addresses must be confirmed.
				$message .= '<input type="hidden" name="business" value="' . esc_attr(SUPER_Common::email_tags($settings['paypal_merchant_email'], $data, $settings)) . '">';
				
				// The URL to which PayPal posts information about the payment, in the form of Instant Payment Notification messages.
				$message .= '<input type="hidden" name="notify_url" value="' . esc_url(SUPER_Common::email_tags($notify_url, $data, $settings)) . '">';

	            // Do not prompt buyers for a shipping address.
				// 0. Prompt for an address, but do not require one.
				// 1. Do not prompt for an address.
				// 2. Prompt for an address and require one.
				if( !empty($settings['paypal_no_shipping']) ) {
					$message .= '<input type="hidden" name="no_shipping" value="' . $settings['paypal_no_shipping'] . '">';
				}

				// The URL to which PayPal redirects buyers' browser after they complete their payments. For example, specify a URL on your site that displays a hank you for your payment page.
				$message .= '<input type="hidden" name="return" value="' . esc_url(SUPER_Common::email_tags($return_url, $data, $settings)) . '">';

				// The buyer's browser is redirected to the return URL by using the POST method, and all payment variables are included.
	            $message .= '<input type="hidden" name="rm" value="2">';

				// A URL to which PayPal redirects the buyers' browsers if they cancel checkout before completing their payments. For example, specify a URL on your website that displays the Payment Canceled page.
				$message .= '<input type="hidden" name="cancel_return" value="' . esc_url(SUPER_Common::email_tags($settings['paypal_cancel_url'], $data, $settings)) . '">';
				
				// The currency of the payment. Default is USD.
				$message .= '<input type="hidden" name="currency_code" value="' . SUPER_Common::email_tags($settings['paypal_currency_code'], $data, $settings) . '" />';
				
				// Pass-through variable for your own tracking purposes, which buyers do not see.
				$message .= '<input type="hidden" name="custom" value="' . esc_attr(implode("|", $custom)) . '">';
				
				// Pass-through variable you can use to identify your invoice number for this purchase.
				if( !empty($settings['paypal_invoice']) ) {
					$message .= '<input type="hidden" name="invoice" value="' . SUPER_Common::email_tags($settings['paypal_invoice'], $data, $settings) . '">';
				}

				// Parse custom address to paypal
				if( (!empty($settings['paypal_custom_address'])) && ($settings['paypal_custom_address']=='true') ) {
					// Let user not edit the address
					if( (!empty($settings['paypal_address_override'])) && ($settings['paypal_address_override']=='true') ) {
						$message .= '<input type="hidden" name="address_override" value="1">';
					}
					$message .= '<input type="hidden" name="first_name" value="' . SUPER_Common::email_tags($settings['paypal_first_name'], $data, $settings) . '">';
					$message .= '<input type="hidden" name="last_name" value="' . SUPER_Common::email_tags($settings['paypal_last_name'], $data, $settings) . '">';
					$message .= '<input type="hidden" name="email" value="' . SUPER_Common::email_tags($settings['paypal_email'], $data, $settings) . '">';
					$message .= '<input type="hidden" name="address1" value="' . SUPER_Common::email_tags($settings['paypal_address1'], $data, $settings) . '">';
					$message .= '<input type="hidden" name="address2" value="' . SUPER_Common::email_tags($settings['paypal_address2'], $data, $settings) . '">';
					$message .= '<input type="hidden" name="city" value="' . SUPER_Common::email_tags($settings['paypal_city'], $data, $settings) . '">';
					$message .= '<input type="hidden" name="state" value="' . SUPER_Common::email_tags($settings['paypal_state'], $data, $settings) . '">';
					$message .= '<input type="hidden" name="zip" value="' . SUPER_Common::email_tags($settings['paypal_zip'], $data, $settings) . '">';
					$message .= '<input type="hidden" name="country" value="' . SUPER_Common::email_tags($settings['paypal_country'], $data, $settings) . '">';
				}


				if ($cmd == '_cart') {
					// tax_cart
					if( !empty($settings['paypal_tax_cart']) ) {
						$message .= '<input type="hidden" name="tax_cart" value="' . SUPER_Common::email_tags($settings['paypal_tax_cart'], $data, $settings) . '">';
					}
					// weight_cart
					if( !empty($settings['paypal_weight_cart']) ) {
						$message .= '<input type="hidden" name="weight_cart" value="' . SUPER_Common::email_tags($settings['paypal_weight_cart'], $data, $settings) . '">';
					}
					// discount_amount_cart
					if( !empty($settings['paypal_discount_amount_cart']) ) {
						$message .= '<input type="hidden" name="discount_amount_cart" value="' . SUPER_Common::email_tags($settings['paypal_discount_amount_cart'], $data, $settings) . '">';
					}
					// discount_rate_cart
					if( !empty($settings['paypal_discount_rate_cart']) ) {
						$message .= '<input type="hidden" name="discount_rate_cart" value="' . SUPER_Common::email_tags($settings['paypal_discount_rate_cart'], $data, $settings) . '">';
					}
					// handling_cart
					if( !empty($settings['paypal_handling_cart']) ) {
						$message .= '<input type="hidden" name="handling_cart" value="' . SUPER_Common::email_tags($settings['paypal_handling_cart'], $data, $settings) . '">';
					}
				}

				// Handling charges. This variable is not quantity-specific. The same handling cost applies, regardless of the number of items on the order.
				if( !empty($settings['paypal_handling']) ) {
					$message .= '<input type="hidden" name="handling" value="' . SUPER_Common::email_tags($settings['paypal_handling'], $data, $settings) . '">';
				}

				// The unit of measure if weight_cart is specified. Valid value is lbs or kgs.
				if( !empty($settings['paypal_weight_unit']) ) {
					$message .= '<input type="hidden" name="weight_unit" value="' . SUPER_Common::email_tags($settings['paypal_weight_unit'], $data, $settings) . '">';
				}

				if (($cmd == '_xclick') || ($cmd == '_donations')) {
					$message .= '<input type="hidden" name="amount" value="' . SUPER_Common::email_tags($settings['paypal_item_amount'], $data, $settings) . '">';
					if( !empty($settings['paypal_item_name']) ) {
						$message .= '<input type="hidden" name="item_name" value="' . esc_attr(SUPER_Common::email_tags($settings['paypal_item_name'], $data, $settings)) . '">';
					}
					if( $cmd == '_xclick' ) {
						if( !empty($settings['paypal_item_number']) ) {
							$message .= '<input type="hidden" name="item_number" value="' . esc_attr(SUPER_Common::email_tags($settings['paypal_item_number'], $data, $settings)) . '">';
						}
						if( !empty($settings['paypal_item_quantity']) ) {
							$message .= '<input type="hidden" name="quantity" value="' . SUPER_Common::email_tags($settings['paypal_item_quantity'], $data, $settings) . '">';
						}
						if( !empty($settings['paypal_item_shipping']) ) {
							$message .= '<input type="hidden" name="shipping" value="' . SUPER_Common::email_tags($settings['paypal_item_shipping'], $data, $settings) . '">';
						}
						if( !empty($settings['paypal_item_shipping2']) ) {
							$message .= '<input type="hidden" name="shipping2" value="' . SUPER_Common::email_tags($settings['paypal_item_shipping2'], $data, $settings) . '">';
						}
						if( !empty($settings['paypal_undefined_quantity']) ) {
							$message .= '<input type="hidden" name="undefined_quantity" value="' . SUPER_Common::email_tags($settings['paypal_undefined_quantity'], $data, $settings) . '">';
						}
						if( !empty($settings['paypal_item_weight']) ) {
							$message .= '<input type="hidden" name="weight" value="' . SUPER_Common::email_tags($settings['paypal_item_weight'], $data, $settings) . '">';
						}
						if( !empty($settings['paypal_item_discount_amount']) ) {
							$message .= '<input type="hidden" name="discount_amount" value="' . SUPER_Common::email_tags($settings['paypal_item_discount_amount'], $data, $settings) . '">';
							$message .= '<input type="hidden" name="discount_amount2" value="' . SUPER_Common::email_tags($settings['paypal_item_discount_amount'], $data, $settings) . '">';
						}
						if( !empty($settings['paypal_item_discount_rate']) ) {
							$message .= '<input type="hidden" name="discount_rate" value="' . SUPER_Common::email_tags($settings['paypal_item_discount_rate'], $data, $settings) . '">';
							$message .= '<input type="hidden" name="discount_rate2" value="' . SUPER_Common::email_tags($settings['paypal_item_discount_rate'], $data, $settings) . '">';
						}
						if( !empty($settings['paypal_item_discount_num']) ) {
							$message .= '<input type="hidden" name="discount_num" value="' . SUPER_Common::email_tags($settings['paypal_item_discount_num'], $data, $settings) . '">';
						}
					}
				}

				// Cart checkout
				if ($cmd == '_cart') {
					$message .= '<input type="hidden" name="upload" value="1">';

					// Add all items to the cart
					$items = explode("\n", $settings['paypal_cart_items']);
					foreach( $items as $k => $v ) {
						$options = explode("|", $v);
						
						// {amount}|{quantity}|{item_name}|{tax}|{shipping}|{shipping2}|{discount_amount}|{discount_rate}
						$ii = 0;
						if(!empty($options[$ii])) $message .= '<input type="hidden" name="amount_' . ($k+1) . '" value="' . SUPER_Common::email_tags($options[$ii], $data, $settings) . '">';
						
						$ii++;
						if(!empty($options[$ii])) $message .= '<input type="hidden" name="quantity_' . ($k+1) . '" value="' . SUPER_Common::email_tags($options[$ii], $data, $settings) . '">';
						
						$ii++;
						if(!empty($options[$ii])) $message .= '<input type="hidden" name="item_name_' . ($k+1) . '" value="' . SUPER_Common::email_tags($options[$ii], $data, $settings) . '">';
						
						$ii++;
						if(!empty($options[$ii])) $message .= '<input type="hidden" name="tax_' . ($k+1) . '" value="' . SUPER_Common::email_tags($options[$ii], $data, $settings) . '">';
						
						$ii++;
						if(!empty($options[$ii])) $message .= '<input type="hidden" name="shipping_' . ($k+1) . '" value="' . SUPER_Common::email_tags($options[$ii], $data, $settings) . '">';
						
						$ii++;
						if(!empty($options[$ii])) $message .= '<input type="hidden" name="shipping2_' . ($k+1) . '" value="' . SUPER_Common::email_tags($options[$ii], $data, $settings) . '">';
						
						$ii++;
						if(!empty($options[$ii])) $message .= '<input type="hidden" name="discount_amount_' . ($k+1) . '" value="' . SUPER_Common::email_tags($options[$ii], $data, $settings) . '">';
						
						$ii++;
						if(!empty($options[$ii])) $message .= '<input type="hidden" name="discount_rate_' . ($k+1) . '" value="' . SUPER_Common::email_tags($options[$ii], $data, $settings) . '">';
					
						// Let's check if at least on of the options contains a {tag}
						foreach( $options as $op => $ov ) {
							if( preg_match("/{(.*?)}/", $ov) ) {
								$origin_name = str_replace("{", "", $ov);
								$origin_name = str_replace("}", "", $origin_name);
								// Loop through dynamic added fields
								$i = 2;
								while (true) {
								    if (!isset($data[$origin_name . '_' . $i])) break;
									$field_names = array(
										'amount',
										'quantity',
										'item_name',
										'tax',
										'shipping',
										'shipping2',
										'discount_amount',
										'discount_rate'
									);
									$ii = 0;
									foreach( $field_names as $v ) {
										if( !empty( $options[$ii] ) ) {
											if( preg_match("/{(.*?)}/", $options[$ii]) ) {
												$name = str_replace("{", "", $options[$ii]); 
												$name = str_replace("}", "", $name);
												$name = str_replace($name, $name . '_' . ($i), $options[$ii]);
												$value = SUPER_Common::email_tags($name, $data, $settings);
											}else{
												// @since 1.0.3 - in case static value is used
												$value = $options[$ii];
											}
											$message .= '<input type="hidden" name="' . $v . '_' . $i . '" value="' . $value . '">';
										}
										$ii++;
									}
								    $i++;
								}
								break;
							}
						}
					}
				}

				// Subscriptions checkout
				if ($cmd == '_xclick-subscriptions') {
					if( !empty($settings['paypal_item_name']) ) {
						// e.g: Alice\'s Weekly Digest
						$message .= '<input type="hidden" name="item_name" value="' . esc_attr(SUPER_Common::email_tags($settings['paypal_item_name'], $data, $settings)) . '">';
					}
					if( !empty($settings['paypal_item_number']) ) {
						// e.g: DIG Weekly
						$message .= '<input type="hidden" name="item_number" value="' . esc_attr(SUPER_Common::email_tags($settings['paypal_item_number'], $data, $settings)) . '">';
					}

					// Add allo periods
					$periods = explode("\n", $settings['paypal_subscription_periods']);
					$periods = array_reverse($periods);
					$counter = 3;
					foreach( $periods as $k => $v ) {
						$options = explode("|", $v);
						
						// a3 - the price of the subscription e.g: 5.00
						$message .= '<input type="hidden" name="a' . $counter . '" value="' . SUPER_Common::email_tags($options[0], $data, $settings) . '">';
						
						// p3 - the period of the subscription e.g: 7 (for 7 days if t1 has value of D)
						$message .= '<input type="hidden" name="p' . $counter . '" value="' . SUPER_Common::email_tags($options[1], $data, $settings) . '">';
						
						// t3 - the time format for the period e.g: D=days, W=weeks, M=months, Y=years
						$message .= '<input type="hidden" name="t' . $counter . '" value="' . SUPER_Common::email_tags($options[2], $data, $settings) . '">';

						$counter--;

						// Check if we only have 1 trial period:
						if(count($periods)==2){
							$counter--;
						}
					}

					// Set recurring payments until canceled.
					$message .= '<input type="hidden" name="src" value="1">';
				}
			
				$message .= '</form>';
				$message .= '<script data-cfasync="false" type="text/javascript" language="javascript">';
				$message .= 'document.getElementById("super_paypal_' . $atts['post']['form_id'] . '").submit();';
				$message .= '</script>';
				if ($settings['form_show_thanks_msg'] == 'true') {
					if ($settings['form_thanks_title'] != '') {
						$settings['form_thanks_title'] = '<h1>' . $settings['form_thanks_title'] . '</h1>';
					}
					$msg = do_shortcode($settings['form_thanks_title'] . nl2br($settings['form_thanks_description']));
				}
				SUPER_Common::output_error($error = false, $msg = $msg . $message, $redirect = false, $fields = array(), $display = true, $loading = true);
			}
		}


		/**
		 * Hook into settings and add PayPal settings
		 *
		 *  @since      1.0.0
		 */
		public static function add_settings($array, $settings) {
			$statuses = SUPER_Settings::get_entry_statuses();
			$new_statuses = array();
			foreach($statuses as $k => $v) {
				$new_statuses[$k] = $v['name'];
			}
			$statuses = $new_statuses;
			unset($new_statuses);
			$currencies = array();
			foreach(self::$currency_codes as $k => $v) {
				$currencies[$k] = $k . ' - ' . $v['name'] . ' (' . $v['symbol'] . ')';
			}
			$array['paypal_checkout'] = array(
				'hidden' => 'settings',
				'name' => __( 'PayPal Checkout', 'super-forms' ),
				'label' => __( 'PayPal Checkout', 'super-forms' ),
				'fields' => array(
					'paypal_checkout' => array(
						'default' => SUPER_Settings::get_value(0, 'paypal_checkout', $settings['settings'], '' ),
						'type' => 'checkbox',
						'filter' => true,
						'values' => array(
							'true' => __( 'Enable PayPal Checkout', 'super-forms' ),
						),
					),
					'paypal_mode' => array(
						'default' => SUPER_Settings::get_value(0, 'paypal_mode', $settings['settings'], '' ),
						'type' => 'checkbox',
						'values' => array(
							'sandbox' => __( 'Enable PayPal Sandbox mode (for testing purposes only)', 'super-forms' ),
						),
						'filter' => true,
						'parent' => 'paypal_checkout',
						'filter_value' => 'true',
					),
					'paypal_merchant_email' => array(
						'name' => __( 'PayPal merchant email (to receive payments)', 'super-forms' ),
						'desc' => __( 'Your PayPal ID or an email address associated with your PayPal account. Email addresses must be confirmed.', 'super-forms' ),
						'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
						'default' => SUPER_Settings::get_value(0, 'paypal_merchant_email', $settings['settings'], '' ),
						'type' => 'text',
						'filter' => true,
						'parent' => 'paypal_checkout',
						'filter_value' => 'true',
					),
					'paypal_currency_code' => array(
						'name' => __( 'PayPal currency code', 'super-forms' ),
						'default' => SUPER_Settings::get_value(0, 'paypal_currency_code', $settings['settings'], 'USD' ),
						'type' => 'select',
						'values' => $currencies,
						'filter' => true,
						'parent' => 'paypal_checkout',
						'filter_value' => 'true',
					),

					// Select wether or not to prompt buyers for a shipping address
					'paypal_no_shipping' => array(
						'name' => __( 'Select whether or not to prompt buyers for a shipping address.', 'super-forms' ),
						'default' => SUPER_Settings::get_value(0, 'paypal_no_shipping', $settings['settings'], '0' ),
						'type' => 'select',
						'values' => array(
							'0' => 'Prompt for an address, but do not require one.',
							'1' => 'Do not prompt for an address.',
							'2' => 'Prompt for an address and require one.',
						),
						'filter' => true,
						'parent' => 'paypal_checkout',
						'filter_value' => 'true',
					),

					'paypal_payment_type' => array(
						'name' => __( 'PayPal payment method', 'super-forms' ),
						'default' => SUPER_Settings::get_value(0, 'paypal_payment_type', $settings['settings'], 'product' ),
						'type' => 'select',
						'values' => array(
							'product' => __( 'Single product or service checkout', 'super-forms' ),
							'donation' => __( 'Donation checkout', 'super-forms' ),
							'subscription' => __( 'Subscription checkout', 'super-forms' ),
							'cart' => __( 'Cart checkout (for multiple product checkout)', 'super-forms' ),
						),
						'filter' => true,
						'parent' => 'paypal_checkout',
						'filter_value' => 'true',
					),


					// PRODUCT & DONATION CHECKOUT SETTINGS

						// Item description
						// Description of item. If you omit this variable, buyers enter their own name during checkout.
						'paypal_item_name' => array(
							'name' => __( 'Item description (leave blank to let users enter a name)', 'super-forms' ),
							'desc' => __( 'Description of item. If you omit this variable, buyers enter their own name during checkout.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_name', $settings['settings'], 'Flower (roses)' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product,donation,subscription',
							'allow_empty' => true,
						),

						// Item price
						// The price or amount of the product, service, or contribution, not including shipping, handling, or tax. If you omit this variable from Buy Now or Donate buttons, buyers enter their own amount at the time of payment.
						'paypal_item_amount' => array(
							'name' => __( 'Item price (leave blank to let user enter their own price)', 'super-forms' ),
							'desc' => __( 'The price or amount of the product, service, or contribution, not including shipping, handling, or tax. If you omit this variable from Buy Now or Donate buttons, buyers enter their own amount at the time of payment.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed (only decimal format is allowed e.g: 16.95)', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_amount', $settings['settings'], '5.00' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product,donation',
							'allow_empty' => true,
						),


					// SINGLE PRODUCT CHECKOUT SETTINGS

						// Quantity (Number of items)
						// Note: The value for quantity must be a positive integer. Null, zero, or negative numbers are not allowed.
						'paypal_item_quantity' => array(
							'name' => __( 'Quantity (Number of items)', 'super-forms' ),
							'desc' => __( 'Note: The value for quantity must be a positive integer. Null, zero, or negative numbers are not allowed.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_quantity', $settings['settings'], '1' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product',
							'allow_empty' => true,
						),

						// Weight of item
						// If profile-based shipping rates are configured with a basis of weight, the sum of weight values is used to calculate the shipping charges for the payment. A valid value is a decimal number with two significant digits to the right of the decimal point.
						'paypal_item_weight' => array(
							'name' => __( 'Weight of item (leave blank for none)', 'super-forms' ),
							'desc' => __( 'If profile-based shipping rates are configured with a basis of weight, the sum of weight values is used to calculate the shipping charges for the payment. A valid value is a decimal number with two significant digits to the right of the decimal point.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_weight', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product',
						),

						// Item number (to track product or service)
						// Pass-through variable for you to track product or service purchased or the contribution made. The value you specify is passed back to you upon payment completion.
						'paypal_item_number' => array(
							'name' => __( 'Item number (to track product or service)', 'super-forms' ),
							'desc' => __( 'Pass-through variable for you to track product or service purchased or the contribution made. The value you specify is passed back to you upon payment completion.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_number', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product',
						),

						// Discount amount (leave blank for no discount)
						// Discount amount associated with an item, which must be less than the selling price of the item.
						'paypal_item_discount_amount' => array(
							'name' => __( 'Discount amount (leave blank for no discount)', 'super-forms' ),
							'desc' => __( 'Discount amount associated with an item, which must be less than the selling price of the item.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_discount_amount', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product',
						),

						// Discount rate (leave blank for no discount)
						// Discount rate, as a percentage, associated with an item. Set to a value less than 100
						'paypal_item_discount_rate' => array(
							'name' => __( 'Discount rate (leave blank for no discount)', 'super-forms' ),
							'desc' => __( 'Discount rate, as a percentage, associated with an item. Set to a value less than 100', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_discount_rate', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product',
						),

						// Discount number
						// Number of additional quantities of the item to which the discount applies.
						'paypal_item_discount_num' => array(
							'name' => __( 'Discount number', 'super-forms' ),
							'desc' => __( 'Number of additional quantities of the item to which the discount applies.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_discount_num', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product',
						),

						// Shipping cost. The cost of shipping this item. (applies to first item added to cart)
						'paypal_item_shipping' => array(
							'name' => __( 'Shipping cost (applies to first item added to cart)', 'super-forms' ),
							'desc' => __( 'The cost of shipping this item.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_shipping', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product',
						),
						// Shipping cost. The cost of shipping this item. (applies to each additional item added to cart)
						// The cost of shipping each additional unit of this item.
						// If you omit this variable and profile-based shipping rates are configured, buyers are charged an amount according to the shipping methods they choose.
						'paypal_item_shipping2' => array(
							'name' => __( 'Shipping cost (applies to each additional item added to cart)', 'super-forms' ),
							'desc' => __( 'The cost of shipping each additional unit of this item.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_item_shipping2', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'product',
						),


					// SUBSCRIPTION CHECKOUT SETTINGS

						// Subscription settings
						'paypal_subscription_periods' => array(
							'name' => __( 'Subscription periods', 'super-forms' ),
							'desc' => __( 'Here you can setup the subscription price, time and periods', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags}<br />Put each period on a new line, seperate values by pipes, for example:<br /><strong>7 day trial for free:</strong> 0|7|D<br /><strong>After trial 3 weeks for 5 dollar:</strong> 5|3|W<br /><strong>After that $49.99 for each year:</strong> 49.99|1|Y<br /><strong>Time format options:</strong> D=days, W=weeks, M=months, Y=years', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_subscription_periods', $settings['settings'], '' ),
							'type' => 'textarea',
							'placeholder' => "0|7|D\n5|3|W\n49.99|1|Y",
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'subscription',
						),


					// CART CHECKOUT SETTINGS

						// Cart items
						// Items to be added to cart
						// Here you can enter the items that need to be added to the cart after form submission
						'paypal_cart_items' => array(
							'name' => __( 'Items to be added to cart', 'super-forms' ),
							'desc' => __( 'Here you can enter the items that need to be added to the cart after form submission', 'super-forms' ),
							'label' => sprintf( 
									__( 'You are allowed to use {tags}%1$s
										Put each item on a new line, seperate values by pipes%1$s
										Leave options blank that you do not wish to use, for example:%1$s%1$s

										%2$sTo add 5 times a 3.49 dollar product write it like below:%3$s%1$s3.49|5|Flowers%1$s%1$s

										%2$sBelow you can see a full example with {tags}:%3$s%1$s
										{price}|{quantity}|{item_name}|{tax}|{shipping}|{shipping2}|{discount_amount}|{discount_rate}%1$s%1$s

										For more information about each option read the %4$sPayPal\'s Variable Reference%5$s', 'super-forms'
									),
									'<br />',
			                    	'<strong>',
			                    	'</strong>',
			                    	'<a target="_blank" href="https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/#individual-items-variables">', 
			                    	'</a>' 
			                ),
							'default' => SUPER_Settings::get_value(0, 'paypal_cart_items', $settings['settings'], '' ),
							'type' => 'textarea',
							'placeholder' => "3.49|5|Flowers\n7.25|3|Towels",
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'cart',
						),

						// Cart-wide tax, overriding any individual item tax_x value
						'paypal_tax_cart' => array(
							'name' => __( 'Cart tax', 'super-forms' ),
							'desc' => __( 'Cart-wide tax, overriding any individual item tax value', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_tax_cart', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'cart',
						),

						// If profile-based shipping rates are configured with a basis of weight, PayPal uses this value to calculate the shipping charges for the payment. This value overrides the weight values of individual items.
						'paypal_weight_cart' => array(
							'name' => __( 'Cart weight', 'super-forms' ),
							'desc' => __( 'This value overrides the weight values of individual items. If profile-based shipping rates are configured with a basis of weight, PayPal uses this value to calculate the shipping charges for the payment.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_weight_cart', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'cart',
						),

						// Single discount amount charged cart-wide.
						// It must be less than the selling price of all items combined in the cart. This variable overrides any individual item discount_amount_x values, if present.
						'paypal_discount_amount_cart' => array(
							'name' => __( 'Cart discount amount', 'super-forms' ),
							'desc' => __( 'Single discount amount charged cart-wide. It must be less than the selling price of all items combined in the cart.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_discount_amount_cart', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'cart',
						),

						// Single Discount rate, as a percentage, to be charged cart-wide.
						// Set to a value less than 100. The variable overrides any individual item discount_rate
						'paypal_discount_rate_cart' => array(
							'name' => __( 'Cart discount rate', 'super-forms' ),
							'desc' => __( 'Single Discount rate, as a percentage, to be charged cart-wide. Set to a value less than 100. The variable overrides any individual item discount rate', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_discount_rate_cart', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'cart',
						),

						// Single handling fee charged cart-wide. If handling_cart is used in multiple Add to Cart buttons, the handling_cart value of the first item is used.
						'paypal_handling_cart' => array(
							'name' => __( 'Cart handling fee', 'super-forms' ),
							'desc' => __( 'Single handling fee charged cart-wide.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_handling_cart', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_payment_type',
							'filter_value' => 'cart',
						),

						

					// GENERAL CHECKOUT SETTINGS

						// Custom return URL
						'paypal_custom_return_url' => array(
							'default' => SUPER_Settings::get_value(0, 'paypal_custom_return_url', $settings['settings'], '' ),
							'type' => 'checkbox',
							'values' => array(
								'true' => __( 'Enable custom return URL', 'super-forms' ),
							),
							'filter' => true,
							'parent' => 'paypal_checkout',
							'filter_value' => 'true',
						),

						// PayPal return URL
						// The URL to which PayPal redirects buyers' browser after they complete their payments.
						// For example, specify a URL on your site that displays a hank you for your payment page.
						'paypal_return_url' => array(
							'name' => __( 'PayPal return URL (when user successfully returns from paypal)', 'super-forms' ),
							'desc' => __( 'The URL to which PayPal posts information about the payment, in the form of Instant Payment Notification messages.', 'super-forms' ),
							'label' => __( 'User will be redirected to this URL after making a payment', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_return_url', $settings['settings'], get_home_url() . '/my-custom-thank-you-page' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_return_url',
							'filter_value' => 'true',
							'allow_empty' => true,
						),

						// Cancel URL when order was canceled by the user
						// A URL to which PayPal redirects the buyers' browsers if they cancel checkout before completing their payments.
						// For example, specify a URL on your website that displays the Payment Canceled page.
						'paypal_cancel_url' => array(
							'name' => __( 'PayPal cancel URL (when payment is canceled by user)', 'super-forms' ),
							'label' => __( 'User that cancels payment will be redirected to this URL', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_cancel_url', $settings['settings'], get_home_url() . '/my-custom-canceled-page' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_checkout',
							'filter_value' => 'true',
							'allow_empty' => true,
						),




					// ADVANCED PAYPAL SETTINGS
					'paypal_advanced_settings' => array(
						'default' => SUPER_Settings::get_value(0, 'paypal_advanced_settings', $settings['settings'], '' ),
						'type' => 'checkbox',
						'values' => array(
							'true' => __( 'Show Advanced PayPal Settings', 'super-forms' ),
						),
						'filter' => true,
						'parent' => 'paypal_checkout',
						'filter_value' => 'true',
					),

						'paypal_lc' => array(
							'name' => __( 'Language for the billing information/log-in page', 'super-forms' ),
							'desc' => __( 'Sets the language for the billing information/log-in page only. Default is US.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed<br />For valid values, see <a href="https://developer.paypal.com/docs/classic/api/country_codes/">Countries and Regions Supported by PayPal</a>.', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_lc', $settings['settings'], 'US' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_advanced_settings',
							'filter_value' => 'true',
						),
						'paypal_charset' => array(
							'name' => __( 'Character set and character encoding for the billing information/log-in page', 'super-forms' ),
							'desc' => __( 'Sets the character set and character encoding for the billing information/log-in page on the PayPal website. In addition, this variable sets the same values for information that you send to PayPal in your HTML button code. Default is based on the language encoding settings in your account profile.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed<br />For valid values, see <a href="https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/formbasics/#setting-the-character-set--charset">Setting the Character Set — charset</a>.', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_charset', $settings['settings'], 'UTF-8' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_advanced_settings',
							'filter_value' => 'true',
						),
						'paypal_handling' => array(
							'name' => __( 'Handling charges', 'super-forms' ),
							'desc' => __( 'This variable is not quantity-specific. The same handling cost applies, regardless of the number of items on the order.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_handling', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_advanced_settings',
							'filter_value' => 'true',
						),
						'paypal_undefined_quantity' => array(
							'default' => SUPER_Settings::get_value(0, 'paypal_undefined_quantity', $settings['settings'], '' ),
							'type' => 'checkbox',
							'values' => array(
								'true' => __( 'Allow buyers to specify the quantity', 'super-forms' ),
							),
							'filter' => true,
							'parent' => 'paypal_advanced_settings',
							'filter_value' => 'true',
						),
						'paypal_weight_unit' => array(
							'name' => __( 'Select weight unit', 'super-forms' ),
							'desc' => __( 'The unit of measure if weight is specified.', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_weight_unit', $settings['settings'], 'lbs' ),
							'type' => 'select',
							'values' => array(
								'lbs' => 'lbs (default)',
								'kgs' => 'kgs',
							),
							'filter' => true,
							'parent' => 'paypal_advanced_settings',
							'filter_value' => 'true',
						),
						'paypal_invoice' => array(
							'name' => __( 'Invoice number', 'super-forms' ),
							'desc' => __( 'Use to identify your invoice number for this purchase.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_invoice', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_advanced_settings',
							'filter_value' => 'true',
						),
						'paypal_night_phone_a' => array(
							'name' => __( 'The area code for U.S. phone numbers, or the country code for phone numbers outside the U.S.', 'super-forms' ),
							'desc' => __( 'PayPal fills in the buyer\'s home phone number automatically.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_night_phone_a', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_advanced_settings',
							'filter_value' => 'true',
						),
						'night_phone_b' => array(
							'name' => __( 'The three-digit prefix for U.S. phone numbers, or the entire phone number for phone numbers outside the U.S., excluding country code.', 'super-forms' ),
							'desc' => __( 'PayPal fills in the buyer\'s home phone number automatically.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'night_phone_b', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_advanced_settings',
							'filter_value' => 'true',
						),
						'night_phone_c' => array(
							'name' => __( 'The four-digit phone number for U.S. phone numbers.', 'super-forms' ),
							'desc' => __( 'PayPal fills in the buyer\'s home phone number automatically.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'night_phone_c', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_advanced_settings',
							'filter_value' => 'true',
						),


						
					// ADDRESS PAYPAL SETTINGS
					'paypal_custom_address' => array(
						'desc' => __( 'Parse the entered address information to paypal. This will not override the PayPal member\'s default address unless you enable the \'Override\' option below.', 'super-forms' ),
						'default' => SUPER_Settings::get_value(0, 'paypal_custom_address', $settings['settings'], '' ),
						'type' => 'checkbox',
						'values' => array(
							'true' => __( 'Parse address to paypal based on form input data.', 'super-forms' ),
						),
						'filter' => true,
						'parent' => 'paypal_checkout',
						'filter_value' => 'true',
					),

						'paypal_address_override' => array(
							'desc' => __( 'The address specified with automatic fill-in variables overrides the PayPal member\'s stored address. Buyers see the addresses that you pass in, but they cannot edit them. PayPal does not show addresses if they are invalid or omitted.', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_address_override', $settings['settings'], '' ),
							'type' => 'checkbox',
							'values' => array(
								'true' => __( 'Override the PayPal member\'s stored address', 'super-forms' ),
							),
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),
						'paypal_first_name' => array(
							'name' => __( 'First name', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_first_name', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),
						'paypal_last_name' => array(
							'name' => __( 'Last name', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_last_name', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),
						'paypal_email' => array(
							'name' => __( 'Email address', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_email', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),
						'paypal_address1' => array(
							'name' => __( 'Street (1 of 2 fields)', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_address1', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),
						'paypal_address2' => array(
							'name' => __( 'Street (2 of 2 fields)', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_address2', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),
						'paypal_city' => array(
							'name' => __( 'City', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_city', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),
						'paypal_state' => array(
							'name' => __( 'U.S. state', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_state', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),
						'paypal_zip' => array(
							'name' => __( 'Postal code', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_zip', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),
						'paypal_country' => array(
							'name' => __( 'Shipping and billing country', 'super-forms' ),
							'desc' => __( 'Sets shipping and billing country.', 'super-forms' ),
							'label' => __( 'You are allowed to use {tags} if needed<br />For valid values, see <a target="_blank" href="https://developer.paypal.com/docs/classic/api/country_codes/">Country and Region Codes</a>.', 'super-forms' ),
							'default' => SUPER_Settings::get_value(0, 'paypal_country', $settings['settings'], '' ),
							'type' => 'text',
							'filter' => true,
							'parent' => 'paypal_custom_address',
							'filter_value' => 'true',
						),



					'paypal_completed_entry_status' => array(
						'name' => __( 'Entry status after payment completed', 'super-forms' ),
						'label' => sprintf(__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . admin_url() . 'admin.php?page=super_settings#backend">', '</a>' ),
						'default' => SUPER_Settings::get_value(0, 'paypal_completed_entry_status', $settings['settings'], 'completed' ),
						'type' => 'select',
						'values' => $statuses,
						'filter' => true,
						'parent' => 'paypal_checkout',
						'filter_value' => 'true',
					),
					'paypal_notify_url' => array(
						'name' => __( 'PayPal notify URL (only for developers!)', 'super-forms' ),
						'label' => __( 'Used for IPN (Instant payment notifications) when payment is confirmed by paypal', 'super-forms' ),
						'default' => SUPER_Settings::get_value(0, 'paypal_notify_url', $settings['settings'], '' ),
						'type' => 'text',
						'filter' => true,
						'parent' => 'paypal_advanced_settings',
						'filter_value' => 'true',
					),
				)
			);
			if (class_exists('SUPER_Frontend_Posting')) {
				$array['paypal_checkout']['fields']['paypal_completed_post_status'] = array(
					'name' => __( 'Post status after payment complete', 'super-forms' ),
					'desc' => __( 'Only used for Front-end posting (publish, future, draft, pending, private, trash, auto-draft)', 'super-forms' ),
					'default' => SUPER_Settings::get_value(0, 'paypal_completed_post_status', $settings['settings'], 'publish' ),
					'type' => 'select',
					'values' => array(
						'publish' => __( 'Publish (default)', 'super-forms' ),
						'future' => __( 'Future', 'super-forms' ),
						'draft' => __( 'Draft', 'super-forms' ),
						'pending' => __( 'Pending', 'super-forms' ),
						'private' => __( 'Private', 'super-forms' ),
						'trash' => __( 'Trash', 'super-forms' ),
						'auto-draft' => __( 'Auto-Draft', 'super-forms' ),
					),
					'filter' => true,
					'parent' => 'paypal_checkout',
					'filter_value' => 'true',
				);
			}
			if (class_exists('SUPER_Register_Login')) {
				$array['paypal_checkout']['fields']['paypal_completed_signup_status'] = array(
					'name' => __( 'Registered user login status after payment complete', 'super-forms' ),
					'desc' => __( 'Only used for Register & Login add-on (active, pending, blocked)', 'super-forms' ),
					'default' => SUPER_Settings::get_value(0, 'paypal_completed_signup_status', $settings['settings'], 'active' ),
					'type' => 'select',
					'values' => array(
						'active' => __( 'Active (default)', 'super-forms' ),
						'pending' => __( 'Pending', 'super-forms' ),
						'blocked' => __( 'Blocked', 'super-forms' ),
					),
					'filter' => true,
					'parent' => 'paypal_checkout',
					'filter_value' => 'true',
				);
			}
			return $array;
		}
	}
endif;


/**
 * Returns the main instance of SUPER_PayPal to prevent the need to use globals.
 *
 * @return SUPER_PayPal
 */
function SUPER_PayPal() {
	return SUPER_PayPal::instance();
}

// Global for backwards compatibility.
$GLOBALS['SUPER_PayPal'] = SUPER_PayPal();
