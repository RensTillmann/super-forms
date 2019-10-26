<?php
/**
 * Super Forms - Stripe
 *
 * @package   Super Forms - Stripe
 * @author    feeling4design
 * @link      http://codecanyon.net/user/feeling4design
 * @copyright 2016 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Stripe
 * Plugin URI:  http://codecanyon.net/user/feeling4design
 * Description: Charge your customers with Stripe
 * Version:     1.0.0
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


if(!class_exists('SUPER_Stripe')) :


    /**
     * Main SUPER_Stripe Class
     *
     * @class SUPER_Stripe
     * @version 1.0.0
     */
    final class SUPER_Stripe {
    
        
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $version = '1.0.0';


        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'stripe';
        public $add_on_name = 'Stripe';

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
        public static $stripe_payment_statuses = array(
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
         * @var SUPER_Stripe The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Stripe Instance
         *
         * Ensures only one instance of SUPER_Stripe is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Stripe()
         * @return SUPER_Stripe - Main instance
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
         * SUPER_Stripe Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->includes();
            $this->init_hooks();
            do_action('super_stripe_loaded');
        }

        
        /**
         * Include required core files used in admin and on the frontend.
         *
         *  @since      1.0.0
        */
        public function includes(){


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
            add_action( 'init', array( $this, 'register_post_types' ), 5 );
            add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_stripe_element' ), 10, 2 );
            add_action( 'wp_head', array( $this, 'stripe_ipn'));
            if ( $this->is_request( 'admin' ) ) {

                add_filter( 'manage_super_stripe_txn_posts_columns', array( $this, 'super_stripe_txn_columns' ), 999999 );
                add_filter( 'manage_super_stripe_sub_posts_columns', array( $this, 'super_stripe_sub_columns' ), 999999 );
                add_action( 'manage_super_stripe_txn_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );
                add_action( 'manage_super_stripe_sub_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );


                add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );
                add_action( 'init', array( $this, 'update_plugin' ) );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );

                add_action( 'current_screen', array( $this, 'after_screen' ), 0 );
                add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 1 );



            }
            if ( $this->is_request( 'ajax' ) ) {
            }

            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            //add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 110, 2 );
        
            //add_action( 'super_before_sending_email_hook', array( $this, 'stripe_request' ) );
            //add_action( 'super_before_email_success_msg_action', array( $this, 'create_source' ) );
            //add_action( 'super_before_sending_email_hook', array( $this, 'create_source' ) );
            add_filter( 'super_redirect_url_filter', array( $this, 'stripe_redirect' ), 10, 2 );

            //$settings = apply_filters( 'super_before_sending_email_settings_filter', $settings );
            //$redirect = apply_filters( 'super_redirect_url_filter', $redirect, array( 'data'=>$data, 'settings'=>$settings ) );
            add_action( 'super_stripe_charge_succeeded', array( $this, 'payment_completed_email'), 10 );

            add_action( 'super_front_end_posting_after_insert_post_action', array( $this, 'save_post_id' ) );
            add_action( 'super_after_wp_insert_user_action', array( $this, 'save_user_id' ) );

        }


        /**
         * Change row actions
         *
         *  @since      1.0.0
         */
        public static function remove_row_actions( $actions ) {
            if( (get_post_type()==='super_stripe_txn') || (get_post_type()==='super_stripe_sub') ) {
                if( isset( $actions['trash'] ) ) {
                    $trash = $actions['trash'];
                    unset( $actions['trash'] );
                }
                unset( $actions['inline hide-if-no-js'] );
                unset( $actions['view'] );
                unset( $actions['edit'] );
                //$actions['view'] = '<a href="admin.php?page=super_stripe_txn&id=' . get_the_ID() . '">' . esc_html__( 'View', 'super-forms' ) . '</a>';
                if(get_post_type()==='super_stripe_sub'){
                    //$actions['view'] = '<a href="admin.php?page=super_stripe_sub&id=' . get_the_ID() . '">' . esc_html__( 'View', 'super-forms' ) . '</a>';
                }
                // if( isset( $trash ) ) {
                //     $actions['trash'] = $trash;
                // }
            }
            return $actions;
        }


        /**
         * Adjust filter/search for transactions and subscriptions
         *
         * @param  string $current_screen
         * 
         * @since       1.0.0
        */
        public function after_screen( $current_screen ) {
            if( $current_screen->id=='edit-super_stripe_txn' ) {
                add_filter( 'get_edit_post_link', array( $this, 'edit_post_link' ), 99, 3 );
            } 
        }
        public function edit_post_link( $link, $post_id, $context ) {
            if( get_post_type()==='super_stripe_txn' ) {
                $data = get_post_meta( get_the_ID(), '_super_txn_payload_data', true );
                return 'https://dashboard.stripe.com/payments/' . $data['data']['object']['id'];
                //return $data['data']['object']['receipt_url'];
                // https://dashboard.stripe.com/payments/py_1FXrxHFKn7uROhgCUM6edSmB

            }
            return $link;
        }


        /**
         * Save Post ID into session after inserting post with Front-end Posting Add-on
         * This way we can add it to the Stripe metadata and use it later to update the user status after payment is completed
         * array( 'post_id'=>$post_id, 'data'=>$data, 'atts'=>$atts )
         *
         *  @since      1.0.0
         */
        public function save_post_id($data) {
            SUPER_Forms()->session->set( '_super_stripe_post_id', absint($data['post_id']) );
        }


        /**
         * Save User ID into session after creating user Front-end Register & Login add-on
         * This way we can add it to the Stripe metadata and use it later to update the user status after payment is completed
         * array( 'user_id'=>$user_id, 'atts'=>$atts )
         *
         *  @since      1.0.0
         */
        public function save_user_id($data) {
            SUPER_Forms()->session->set( '_super_stripe_user_id', absint($data['user_id']) );
        }


        /**
         *  Register post types
         *
         *  @since    1.0.0
         */
        public static function register_post_types() {
            if (!post_type_exists('super_stripe_txn')) {
                register_post_type('super_stripe_txn', apply_filters('super_register_post_type_super_stripe_txn', array(
                    'label' => 'Stripe Transactions',
                    'description' => '',
                    'capability_type' => 'post',
                    'exclude_from_search' => true, // make sure to exclude from default search
                    'public' => false,
                    'query_var' => false,
                    'has_archive' => false,
                    'publicaly_queryable' => false,
                    'show_ui' => true,
                    'show_in_menu' => false,
                    'map_meta_cap' => true,
                    'hierarchical' => false,
                    'supports' => array(),
                    'capabilities' => array(
                        'create_posts' => false, // Removes support for the "Add New" function
                    ),
                    'rewrite' => array(
                        'slug' => 'super_stripe_txn',
                        'with_front' => true
                    ),
                    'labels' => array(
                        'name' => 'Stripe Transactions',
                        'singular_name' => 'Stripe Transaction',
                        'menu_name' => 'Stripe Transactions',
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
            if (!post_type_exists('super_stripe_sub')) {
                register_post_type('super_stripe_sub', apply_filters('super_register_post_type_super_stripe_sub', array(
                    'label' => 'Stripe Subscriptions',
                    'description' => '',
                    'capability_type' => 'post',
                    'exclude_from_search' => true, // make sure to exclude from default search
                    'public' => false,
                    'query_var' => false,
                    'has_archive' => false,
                    'publicaly_queryable' => false,
                    'show_ui' => true,
                    'show_in_menu' => false,
                    'map_meta_cap' => true,
                    'hierarchical' => false,
                    'supports' => array(),
                    'capabilities' => array(
                        'create_posts' => false, // Removes support for the "Add New" function
                    ),
                    'rewrite' => array(
                        'slug' => 'super_stripe_sub',
                        'with_front' => true
                    ),
                    'labels' => array(
                        'name' => 'Stripe Subscriptions',
                        'singular_name' => 'Stripe Subscription',
                        'menu_name' => 'Stripe Subscriptions',
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
        /**
         *  Add menu items
         *
         *  @since    1.0.0
         */
        public static function register_menu() {
            global $menu, $submenu;
            $styles = 'background-image:url(' . plugin_dir_url( __FILE__ ) . 'assets/images/stripe.png);width:22px;height:22px;display:inline-block;background-position:-3px -3px;background-repeat:no-repeat;margin:0px 0px -9px 0px;';
            
            // Transactions menu
            $count = get_option( 'super_stripe_txn_count', 0 );
            if( $count>0 ) {
                $count = ' <span class="update-plugins"><span class="plugin-count">' . $count . '</span></span>';
            }else{
                $count = '';
            }
            add_submenu_page(
                'super_forms', 
                esc_html__( 'Stripe Transactions', 'super-forms' ),
                '<span class="super-stripe-icon" style="' . $styles . '"></span>' . esc_html__( 'Transactions', 'super-forms' ) . $count,
                'manage_options', 
                'edit.php?post_type=super_stripe_txn'
            );
            add_submenu_page(
                null, 
                esc_html__( 'View Stripe transaction', 'super-forms' ), 
                esc_html__( 'View Stripe transaction', 'super-forms' ), 
                'manage_options', 
                'super_stripe_txn', 
                'SUPER_Stripe::stripe_transaction'
            );

            // Subscriptions menu
            $count = get_option( 'super_stripe_sub_count', 0 );
            if( $count>0 ) {
                $count = ' <span class="update-plugins"><span class="plugin-count">' . $count . '</span></span>';
            }else{
                $count = '';
            }
            add_submenu_page(
                'super_forms', 
                esc_html__( 'Stripe Subscriptions', 'super-forms' ),
                '<span class="super-stripe-icon" style="' . $styles . '"></span>' . esc_html__( 'Subscriptions', 'super-forms' ) . $count,
                'manage_options', 
                'edit.php?post_type=super_stripe_sub'
            );
            add_submenu_page(
                null, 
                esc_html__( 'View Stripe subscription', 'super-forms' ), 
                esc_html__( 'View Stripe subscription', 'super-forms' ), 
                'manage_options', 
                'super_stripe_sub', 
                'SUPER_Stripe::stripe_subscription'
            );

        }


        /**
         * Handles the output for the view Stripe transaction page in admin
         */
        function loop_txn_data($data, $i=1){
            if($i>7) $i = 7;
            foreach( $data as $k => $v ) {
                if( is_array($v) ) {
                    echo '<h'.$i.'>' . $k . '</h'.$i.'>';
                    $i++;
                    self::loop_txn_data($v, $i);
                }else{
                    echo '<strong>'. $k .':</strong> ' . $v . '<br />';
                }
            }
        }
        public static function stripe_transaction() {
            $id = $_GET['id'];
            if ( (FALSE === get_post_status($id)) && (get_post_type($id)!='super_stripe_txn') ) {
                // The post does not exist
                echo 'This transaction does not exist.';
            } else {
                // The post exists
                $date = get_the_date(false,$id);
                $time = get_the_time(false,$id);
                $txn_data = get_post_meta( $id, '_super_txn_data', true );
                $txn_event_data = get_post_meta( $id, '_super_txn_event_data', true );
                $data = get_post_meta( $id, '_super_txn_payload_data', true );
                echo '<a href="' . $data['data']['object']['receipt_url'] . '">View Receipt</a>';
                var_dump($data);
                //echo '<h1>Event data</h1>';
                //self::loop_txn_data($txn_event_data);
                //echo '<h1>Payload data</h1>';
                //self::loop_txn_data($txn_payload_data);
                exit;

                $custom = explode( '|', $txn_data['custom'] );
                ?>
                <script>
                    jQuery('.toplevel_page_super_forms').removeClass('wp-not-current-submenu').addClass('wp-menu-open wp-has-current-submenu');
                    jQuery('.toplevel_page_super_forms').find('a[href$="super_stripe_txn"]').parents('li:eq(0)').addClass('current');
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
                                            <span><?php echo esc_html__('Transaction Details', 'super-forms' ); ?>:</span>
                                        </h3>
                                        <div class="inside">
                                            <div class="submitbox" id="submitpost">
                                                <div id="minor-publishing">
                                                    <div class="misc-pub-section">
                                                        <?php 
                                                        //$currency_code = self::get_currency_code($txn_data);
                                                        $mc_gross = number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code;
                                                        ?>
                                                        <span><?php echo esc_html__( 'Gross amount', 'super-forms' ) . ':'; ?> <strong><?php echo $mc_gross; ?></strong></span>
                                                    </div>
                                                    <div class="misc-pub-section">
                                                        <span><?php echo esc_html__( 'Transaction ID', 'super-forms' ) . ':'; ?> <strong><?php echo get_the_title($id); ?></strong></span>
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
                                                        <span><?php echo esc_html__('Submitted', 'super-forms' ) . ':'; ?> <strong><?php echo $date.' @ '.$time; ?></strong></span>
                                                    </div>

                                                    <?php
                                                    if( (isset($custom[3])) && ($custom[3]!=0) ) {
                                                        $user_info = get_userdata($custom[3]);
                                                        echo '<div class="misc-pub-section">';
                                                            echo '<span>' . esc_html__( 'Submitted by user', 'super-forms' ) . ': <a href="' . get_edit_user_link($user_info->ID) . '"><strong>' . $user_info->display_name . '</strong></a></span>';
                                                        echo '</div>';
                                                    }
                                                    if( (isset($custom[2])) && ($custom[2]!=0) ) {
                                                        echo '<div class="misc-pub-section">';
                                                            echo '<span>' . esc_html__( 'Contact Entry', 'super-forms' ) . ': <a href="admin.php?page=super_contact_entry&id=' . $custom[0] . '"><strong>' . get_the_title($custom[2]) . '</strong></a></span>';
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
                                                    $post_id = $wpdb->get_var("SELECT post_id FROM $wpdb->postmeta AS meta INNER JOIN $wpdb->posts AS post ON post.id = meta.post_id WHERE post.post_type = 'super_stripe_sub' AND meta_key = '_super_sub_id' AND meta_value = '$sub_id'");
                                                    if(absint($post_id)!=0){
                                                        echo '<div class="misc-pub-section">';
                                                            echo '<span>' . esc_html__( 'Based on subscription', 'super-forms' ) . ': <a href="admin.php?page=super_stripe_sub&id=' . $post_id . '"><strong>' . $sub_id . '</strong></a></span>';
                                                        echo '</div>';                                                  
                                                    }

                                                    // Check if there was a post created 
                                                    if( (isset($custom[4])) && ($custom[4]!=0) ) {
                                                        $post_id = absint($custom[4]);
                                                        $edit_link = get_edit_post_link($post_id);
                                                        ?>
                                                        <div class="misc-pub-section">
                                                            <?php echo '<span>' . esc_html__('Created Post', 'super-forms' ) . ':'; ?> <?php echo '<a href="' . $edit_link . '"><strong>' . get_the_title( $post_id ) . '</strong></a></span>'; ?>
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
                                                                <?php echo '<span>' . esc_html__('Created User', 'super-forms' ) . ':'; ?> <?php echo '<a href="' . $edit_link . '"><strong>' . $user_info->user_login . '</strong></a></span>'; ?>
                                                            </div>
                                                            <?php
                                                        }
                                                    }
                                                    ?>

                                                    <div class="misc-pub-section">
                                                        <?php echo '<span>' . esc_html__('Based on Form', 'super-forms' ) . ':'; ?> <?php echo '<a href="admin.php?page=super_create_form&id=' . $custom[0] . '"><strong>' . get_the_title( $custom[0] ) . '</strong></a></span>'; ?>
                                                    </div>

                                                    <div class="clear"></div>
                                                </div>

                                                <div id="major-publishing-actions">
                                                    <div id="delete-action">
                                                        <a class="submitdelete super-delete-contact-entry" data-contact-entry="<?php echo absint($id); ?>" href="#"><?php echo esc_html__('Move to Trash', 'super-forms' ); ?></a>
                                                    </div>
                                                    <div id="publishing-action">
                                                        <span class="spinner"></span>
                                                        <input name="print" type="submit" class="super-print-contact-entry button button-large" value="<?php echo esc_html__('Print', 'super-forms' ); ?>">
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
                                //$currency_code = self::get_currency_code($txn_data);
                                $mc_gross = number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code;

                                if( $txn_data['txn_type']!='subscr_payment' ) {
                                    ?>
                                    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                                        <div id="super-contact-entry-data" class="postbox ">
                                            <div class="handlediv" title="">
                                                <br>
                                            </div>
                                            <h3 class="hndle ui-sortable-handle">
                                                <span><?php echo esc_html__('Order details', 'super-forms' ); ?>:</span>
                                            </h3>
                                            <div class="inside">
                                                <?php
                                                echo '<table style="width:100%">';
                                                    echo '<tr><th align="left">' . esc_html__( 'Item name', 'super-forms' ) . '</th><th align="right">' . esc_html__( 'Quantity', 'super-forms' ) . '</th><th align="right">' . esc_html__( 'Price', 'super-forms' ) . '</th><th align="right">' . esc_html__( 'Subtotal', 'super-forms' ) . '</th></tr>';
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
                                            <span><?php echo esc_html__('Payment details', 'super-forms' ); ?>:</span>
                                        </h3>
                                        <div class="inside">
                                            <?php
                                            if( !empty($txn_data['address_country_code']) && $txn_data['address_country_code']=='US' ) {
                                                $located = 'inside';
                                            }else{
                                                $located = 'outside';
                                            }
                                            if( !empty($txn_data['payer_status']) && $txn_data['payer_status']=='verified' ) {
                                                $verified = '';
                                                $color = 'green';
                                            }else{
                                                $verified = 'NOT ';
                                                $color = 'red';
                                            }
                                            $verified_text = $txn_data['first_name'] . ' ' . $txn_data['last_name'] . '<br />';
                                            $verified_text .= sprintf( esc_html__( 'The sender of this payment has %1$sverified their account and is located %2$s the US.', 'super-forms' ), '<strong style="color:' . $color . ';">' . $verified, $located ) . '</strong><br />';
                                            $verified_text .= $txn_data['payer_email'];
                                            if( $txn_data['txn_type']=='subscr_payment' ) {
                                                echo '<table>';
                                                echo '<tr><th align="left">' . esc_html__( 'Gross amount', 'super-forms' ) . '</th><td align="right">' . $mc_gross . '</td></tr>';
                                                if(empty($txn_data['mc_fee'])) $txn_data['mc_fee'] = 0;
                                                echo '<tr><th align="left">' . esc_html__( 'Stripe fee', 'super-forms' ) . '</th><td align="right">' . number_format_i18n($txn_data['mc_fee'], 2) . ' ' . $currency_code . '</td></tr>';
                                                echo '<tr><th align="left">' . esc_html__( 'Net amount', 'super-forms' ) . '</th><td align="right">' . number_format_i18n(($txn_data['mc_gross']-$txn_data['mc_fee']), 2) . ' ' . $currency_code . '</td></tr>';
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
                                            }else{
                                                echo '<table>';
                                                echo '<tr><th align="right">' . esc_html__( 'Purchase total', 'super-forms' ) . '</th><td align="right">' . $mc_gross . '</td></tr>';
                                                echo '<tr><th align="right">' . esc_html__( 'Sales tax', 'super-forms' ) . '</th><td align="right">' . (isset($txn_data['tax']) ? number_format_i18n($txn_data['tax'], 2) : number_format_i18n(0, 2)) . ' ' . $currency_code . '</td></tr>';
                                                echo '<tr><th align="right">' . esc_html__( 'Shipping amount', 'super-forms' ) . '</th><td align="right">' . (isset($txn_data['mc_shipping']) ? number_format_i18n($txn_data['mc_shipping'], 2) : number_format_i18n(0, 2)) . ' ' . $currency_code . '</td></tr>';
                                                echo '<tr><th align="right">' . esc_html__( 'Handling amount', 'super-forms' ) . '</th><td align="right">' . (isset($txn_data['mc_handling']) ? number_format_i18n($txn_data['mc_handling'], 2) : number_format_i18n(0, 2)) . ' ' . $currency_code . '</td></tr>';
                                                echo '<tr><th align="right">' . esc_html__( 'Insurance', 'super-forms' ) . '</th><td align="right">' . (isset($txn_data['insurance_amount']) ? number_format_i18n($txn_data['insurance_amount'], 2) : number_format_i18n(0, 2)) . ' ' . $currency_code . '</td></tr>'; 
                                                echo '<tr><th align="right">' . esc_html__( 'Gross amount', 'super-forms' ) . '</th><td align="right">' . $mc_gross . '</td></tr>';
                                                echo '<tr><th align="right">' . esc_html__( 'Stripe fee', 'super-forms' ) . '</th><td align="right">' . number_format_i18n($txn_data['mc_fee'], 2) . ' ' . $currency_code . '</td></tr>';
                                                echo '<tr><th align="right">' . esc_html__( 'Net amount', 'super-forms' ) . '</th><td align="right">' . number_format_i18n(($txn_data['mc_gross']-$txn_data['mc_fee']), 2) . ' ' . $currency_code . '</td></tr>';
                                                if( (isset($txn_data['invoice'])) && ($txn_data['invoice']!='') ) {
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
                                            <span><?php echo esc_html__('Address', 'super-forms' ); ?>:</span>
                                        </h3>
                                        <div class="inside">
                                            <?php
                                            echo '<table>';
                                            if(!empty($txn_data['address_name'])) echo '<tr><th align="left">' . esc_html__( 'Name', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_name'] . '</td></tr>';
                                            if(!empty($txn_data['address_street'])) echo '<tr><th align="left">' . esc_html__( 'Street', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_street'] . '</td></tr>';
                                            if(!empty($txn_data['address_zip'])) echo '<tr><th align="left">' . esc_html__( 'Zipcode', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_zip'] . '</td></tr>';
                                            if(!empty($txn_data['address_city'])) echo '<tr><th align="left">' . esc_html__( 'City', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_city'] . '</td></tr>';
                                            if(!empty($txn_data['address_state'])) echo '<tr><th align="left">' . esc_html__( 'State', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_state'] . '</td></tr>';
                                            if(!empty($txn_data['address_country'])) echo '<tr><th align="left">' . esc_html__( 'Country', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_country'] . ' (' . $txn_data['address_country_code'] . ')</td></tr>';
                                            if(!empty($txn_data['address_status'])) echo '<tr><th align="left">' . esc_html__( 'Address status', 'super-forms' ) . '</th><td align="left">' . $txn_data['address_status'] . '</td></tr>';
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
                                            <span><?php echo esc_html__('Raw Transaction Data', 'super-forms' ); ?>:</span>
                                        </h3>
                                        <div class="inside">
                                            <?php
                                            echo '<table>';
                                                foreach( $txn_data as $k => $v ) {
                                                    echo '<tr><th align="right">' . $k . '</th><td>' . $v . '</td></tr>';
                                                }
                                                echo apply_filters( 'super_after_stripe_txn_data_filter', '', array( 'stripe_txn_id'=>$_GET['id'], 'txn_data'=>$txn_data ) );
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
         * Custom transaction columns
         *
         *  @since      1.0.0
         */
        public static function super_stripe_txn_columns($columns){
            
            $global_settings = get_option( 'super_settings' );
            $GLOBALS['backend_contact_entry_status'] = SUPER_Settings::get_entry_statuses($global_settings);

            foreach($columns as $k => $v) {
                if (($k != 'title') && ($k != 'cb')) {
                    unset($columns[$k]);
                }
            }
            $columns['title'] = 'Source ID';
            $columns['stripe_amount'] = 'Amount';
            $columns['stripe_description'] = 'Description';
            $columns['stripe_customer'] = 'Customer';
            $columns['stripe_method'] = 'Payment method';
            $columns['stripe_status'] = 'Status';
            $columns['stripe_form_id'] = 'Based on Form';
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
        public static function super_stripe_sub_columns($columns){
            
            $global_settings = get_option( 'super_settings' );
            $GLOBALS['backend_contact_entry_status'] = SUPER_Settings::get_entry_statuses($global_settings);

            foreach($columns as $k => $v) {
                if (($k != 'title') && ($k != 'cb')) {
                    unset($columns[$k]);
                }
            }
            $columns['title'] = 'Subscription ID'; // post_title
            $columns['stripe_status'] = 'Status'; // payment_status
            $columns['stripe_payer_email'] = 'Name / E-mail'; // first_name + last_name / payer_email
            $columns['stripe_invoice'] = 'Invoice'; // invoice
            $columns['stripe_item'] = 'Recurring Payment'; // item_name + quantity
            $columns['stripe_initial_payment'] = 'Trial Period'; // a1,t1,p1 / a2,t2,p2
            $columns['stripe_trial_period'] = 'Trial Period 2'; // a1,t1,p1 / a2,t2,p2
            $columns['stripe_hidden_form_id'] = 'Based on Form'; // hidden_form_id
            $columns['date'] = 'Date'; // payment_date
            return $columns;

            //address_status
            //payer_status
        }


        public static function super_custom_columns($column, $post_id) {

            $txn_data = get_post_meta( $post_id, '_super_txn_event_data', true );
            $obj = $txn_data['data']['object'];
            $currency_code = strtoupper($obj['currency']);
            $symbol = self::$currency_codes[$currency_code]['symbol'];
            //var_dump($txn_data);
            //$txn_data = get_post_meta( $post_id, '_super_txn_payload_data', true );
            //var_dump($txn_data);
            switch ($column) {
                case 'stripe_amount':
                    echo $symbol . number_format_i18n($obj['amount']/100, 2) . ' ' . $currency_code;
                    //echo '(' . $symbol . number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code . ')';
                    break;
                case 'stripe_description':
                    echo $obj['description'];
                    break;
                case 'stripe_customer':
                    echo $obj['billing_details']['email'];
                    break;
                case 'stripe_method':
                    echo $obj['payment_method_details']['type'];
                    break;
                case 'stripe_status':
                    echo $obj['status'];
                    break;
                case 'stripe_form_id':
                    $form_id = wp_get_post_parent_id($post_id);
                    if ($form_id == 0) {
                        echo esc_html__( 'Unknown', 'super-forms');
                    } else {
                        $form = get_post($form_id);
                        if (isset($form->post_title)) {
                            echo '<a href="admin.php?page=super_create_form&id=' . $form->ID . '">' . $form->post_title . '</a>';
                        }
                        else {
                            echo esc_html__( 'Unknown', 'super-forms');
                        }
                    }
                    break;
            }

            // $txn_data = get_post_meta( $post_id, '_super_txn_data', true );
            // $custom = explode( '|', $txn_data['custom'] );

            // // Get currency code e.g: EUR
            // $currency_code = self::get_currency_code($txn_data);
            // $symbol = self::$currency_codes[$currency_code]['symbol'];

            // // Get product/item name
            // $product_name = self::get_product_item_name($txn_data);

            // // Get amount per cycle
            // $amount_per_cycle = self::get_amount_per_cycle($txn_data);

            // switch ($column) {
            //     case 'stripe_status':
            //         if( ($txn_data['txn_type']=='subscr_signup') || ($txn_data['txn_type']=='subscr_modify') || ($txn_data['txn_type']=='subscr_cancel') || ($txn_data['txn_type']=='recurring_payment_suspended') ) {
            //             $entry_status = 'Active';
            //             $entry_status_desc = '';
            //             if( isset($txn_data['profile_status']) ) {
            //                 $entry_status = $txn_data['profile_status'];
            //                 $entry_status_desc = $entry_status;
            //             }
            //             if( $txn_data['txn_type']=='recurring_payment_suspended' ) {
            //                 $entry_status_desc = 'This profile has been suspended, and no further amounts will be collected.';                      
            //             }
            //             if( $txn_data['txn_type']=='subscr_cancel' ) {
            //                 $entry_status = 'Canceled';
            //                 $entry_status_desc = 'This recurring payment plan has been canceled and cannot be reactivated. No more recurring payments will be made.';
            //             }
            //             echo '<span title="' . esc_attr($entry_status_desc) . '" class="super-txn-status super-txn-status-' . strtolower($entry_status) . '">' . $entry_status . '</span>';
            //         }else{
            //             $entry_status = $txn_data['payment_status'];
            //             $value = self::$stripe_payment_statuses[$entry_status];
            //             $statuses = $GLOBALS['backend_contact_entry_status'];
            //             if( (isset($statuses[$entry_status])) && ($entry_status!='') ) {
            //                 echo '<span title="' . esc_attr($value['desc']) . '" class="super-txn-status super-txn-status-' . strtolower($entry_status) . '" style="color:' . $statuses[$entry_status]['color'] . ';background-color:' . $statuses[$entry_status]['bg_color'] . '">' . $value['label'] . '</span>';
            //             }else{
            //                 echo '<span title="' . esc_attr($value['desc']) . '" class="super-txn-status super-txn-status-' . strtolower($entry_status) . '">' . $value['label'] . '</span>';
            //             }
            //         }               
            //         break;
            //     case 'stripe_payer_email':
            //         $tooltip = '';
            //         if($txn_data['payer_status']=='verified'){
            //             $tooltip = '<i title="Customer has a verified Stripe account" class="fas fa-check-circle super-stripe-txn-verified" aria-hidden="true"></i>';
            //         }
            //         if($txn_data['payer_status']=='unverified'){
            //             $tooltip = '<i title="Customer has an unverified Stripe account" class="fas fa-exclamation-circle super-stripe-txn-unverified" aria-hidden="true"></i>';
            //         }
            //         echo '<span class="pp-name-email">';
            //         echo $tooltip;
            //         echo '<strong>' . $txn_data['first_name'] . ' ' . $txn_data['last_name'] . '</strong><br />';
            //         echo $txn_data['payer_email'];
            //         echo '</span>';
            //         break;
            //     case 'stripe_invoice':
            //         echo (isset($txn_data['invoice']) ? $txn_data['invoice'] : '');
            //         break;
            //     case 'stripe_item':
            //         if($txn_data['txn_type']=='cart'){
            //             $i=1;
            //             while( isset($txn_data['item_name'.$i]) ) {
            //                 echo $txn_data['quantity'.$i] . 'x — <strong>' . $txn_data['item_name'.$i] . '</strong><br />';
            //                 $i++;
            //             }
            //         }else{
            //             if( ($txn_data['txn_type']=='subscr_payment') || ($txn_data['txn_type']=='subscr_signup') || ($txn_data['txn_type']=='subscr_modify') || ($txn_data['txn_type']=='subscr_cancel') || ($txn_data['txn_type']=='recurring_payment_suspended') ) {
            //                 if($txn_data['txn_type']=='subscr_payment'){
            //                     echo '1x — <strong>' . $txn_data['item_name'] . '</strong><br />';
            //                     echo '(' . $symbol . number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code . ')';
            //                 }else{
            //                     echo '<strong>' . $product_name . '</strong><br />';
            //                     // Get payment cycle
            //                     $payment_cycle = self::get_payment_cycle($txn_data, 3);
            //                     echo '(' . $payment_cycle . ': ' . $symbol . number_format_i18n($amount_per_cycle, 2) . ' ' . $currency_code . ')';
            //                 }
            //             }else{
            //                 echo $txn_data['quantity'] . 'x — <strong>' . $txn_data['item_name'] . '</strong><br />';
            //                 echo '(' . $symbol . number_format_i18n($txn_data['mc_gross'], 2) . ' ' . $currency_code . ')';
            //             }
            //         }
            //         break;
            //     case 'stripe_initial_payment':
            //         if( isset($txn_data['mc_amount1']) ) {
            //             // Get payment cycle
            //             $payment_cycle = self::get_payment_cycle($txn_data, 1);
            //             echo '(' . $payment_cycle . ': ' . $symbol . number_format_i18n($txn_data['mc_amount1'], 2) . ' ' . $currency_code . ')';
            //         }
            //         break;
            //     case 'stripe_trial_period':
            //         if( isset($txn_data['mc_amount2']) ) {
            //             // Get payment cycle
            //             $payment_cycle = self::get_payment_cycle($txn_data, 2);
            //             echo '(' . $payment_cycle . ': ' . $symbol . number_format_i18n($txn_data['mc_amount2'], 2) . ' ' . $currency_code . ')';
            //         }
            //         break;
            //     case 'stripe_hidden_form_id':
            //         $form_id = absint($custom[0]);
            //         if ($form_id == 0) {
            //             echo esc_html__( 'Unknown', 'super-forms');
            //         } else {
            //             $form = get_post($form_id);
            //             if (isset($form->post_title)) {
            //                 echo '<a href="admin.php?page=super_create_form&id=' . $form->ID . '">' . $form->post_title . '</a>';
            //             }
            //             else {
            //                 echo esc_html__( 'Unknown', 'super-forms');
            //             }
            //         }
            //         break;
            // }
        }


        /**
         * Redirect to Stripe Checkout page
         *
         * @since       1.0.0
         */
        public function stripe_redirect($redirect, $atts) {
            $settings = $atts['settings'];
            $data = $atts['data'];

            // A set of key-value pairs that you can attach to a source object. 
            // It can be useful for storing additional information about the source in a structured format.
            $metadata = array();
            $metadata['_super_form_id'] = absint($data['hidden_form_id']['value']);
            $metadata['_super_author_id'] = absint(get_current_user_id());
            // Get Post ID and save it in custom parameter for stripe so we can update the post status after successfull payment complete
            $post_id = SUPER_Forms()->session->get( '_super_stripe_post_id' );
            if( !empty($post_id) ) {
                $metadata['_super_stripe_post_id'] = absint($post_id);
            }
            // Get User ID and save it in custom parameter for stripe so we can update the user status after successfull payment complete
            $user_id = SUPER_Forms()->session->get( '_super_stripe_user_id' );
            if( !empty($user_id) ) {
                $metadata['_super_stripe_user_id'] = absint($user_id);
            }

            if(!empty($settings['save_contact_entry']) && $settings['save_contact_entry']=='yes'){
                $metadata['_super_contact_entry_id'] = absint($data['contact_entry_id']['value']);
            }
            // Allow devs to filter metadata if needed
            $metadata = apply_filters( 'super_stripe_source_metadata', $metadata, array('settings'=>$settings, 'data'=>$data ) );

            // Check if Stripe checkout is enabled
            if($settings['stripe_checkout']=='true'){
                // If enabled determine what checkout method was choosen by the end user
                if( (!empty($data['stripe_ideal'])) && (!empty($data['stripe_ideal']['value'])) ) {
                    $bank = sanitize_text_field($data['stripe_ideal']['value']);
                    $amount = SUPER_Common::email_tags( $settings['stripe_amount'], $data, $settings );
                    $stripe_statement_descriptor = sanitize_text_field(SUPER_Common::email_tags( $settings['stripe_statement_descriptor'], $data, $settings ));
                    if(empty($stripe_statement_descriptor)) $stripe_statement_descriptor = null;
                    $stripe_email = SUPER_Common::email_tags( $settings['stripe_email'], $data, $settings );
                    $stripe_name = SUPER_Common::email_tags( $settings['stripe_name'], $data, $settings );
                    $stripe_phone = SUPER_Common::email_tags( $settings['stripe_phone'], $data, $settings );
                    $stripe_city = SUPER_Common::email_tags( $settings['stripe_city'], $data, $settings );
                    $stripe_country = SUPER_Common::email_tags( $settings['stripe_country'], $data, $settings );
                    $stripe_line1 = SUPER_Common::email_tags( $settings['stripe_line1'], $data, $settings );
                    $stripe_line2 = SUPER_Common::email_tags( $settings['stripe_line2'], $data, $settings );
                    $stripe_postal_code = SUPER_Common::email_tags( $settings['stripe_postal_code'], $data, $settings );
                    $stripe_state = SUPER_Common::email_tags( $settings['stripe_state'], $data, $settings );

                    // The URL the customer should be redirected to after the authorization process.
                    $stripe_return_url = esc_url(SUPER_Common::email_tags( $settings['stripe_return_url'], $data, $settings ));
                    if( empty($stripe_return_url) ) {
                        // Fallback to blog home URL
                        $stripe_return_url = get_home_url();
                    }

                    // Create Source for iDeal payment
                    $url = 'https://api.stripe.com/v1/sources';
                    $response = wp_remote_post( 
                        $url, 
                        array(
                            'timeout' => 45,
                            'headers'=>array(
                                'Authorization' => 'Bearer sk_test_CczNHRNSYyr4TenhiCp7Oz05'
                            ),                      
                            'body' => array(
                                // The type of the source. The type is a payment method, one of ach_credit_transfer, ach_debit, alipay, bancontact, card, card_present, eps, giropay, ideal, multibanco, klarna, p24, sepa_debit, sofort, three_d_secure, or wechat. An additional hash is included on the source with a name matching this value. It contains additional information specific to the payment method used.
                                'type' => 'ideal', 

                                'currency' => 'eur', // iDeal only supports EUR currency
                                'amount' => SUPER_Common::tofloat($amount)*100, // The amount to charge times hundred (because amount is in cents)


                                // iDEAL requires a statement descriptor before the customer is redirected to authenticate the payment. By default, your Stripe account’s statement descriptor is used (you can review this in the Dashboard). 
                                'statement_descriptor' => $stripe_statement_descriptor,
                                'ideal' => array(
                                    'bank' => $bank, // abn_amro, asn_bank, bunq, handelsbanken, ing, knab, moneyou, rabobank, regiobank, sns_bank, triodos_bank, van_lanschot
                                    //'statement_descriptor' => $stripe_statement_descriptor // NOT USED? Unclear from Stripe API documentation
                                ),
                                // Information about the owner of the payment instrument that may be used or required by particular source types.
                                // (optional)
                                'owner' => array(
                                    'email' => $stripe_email,
                                    'name' => $stripe_name,
                                    'phone' => $stripe_phone,
                                    // address
                                    'address' => array(
                                        'city' => $stripe_city,
                                        'country' => $stripe_country,
                                        'line1' => $stripe_line1,
                                        'line2' => $stripe_line2,
                                        'postal_code' => $stripe_postal_code,
                                        'state' => $stripe_state
                                    )
                                ),
                                'redirect' => array(
                                    'return_url' => $stripe_return_url // Required for iDeal Source
                                ),
                                'metadata' => $metadata
                            )
                        )
                    );
                    if ( is_wp_error( $response ) ) {
                        $error_message = $response->get_error_message();
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $error_message
                        );
                    } else {
                        $obj = json_decode($response['body']);
                        if( !empty($obj->error) ) {
                            SUPER_Common::output_error(
                                $error = true,
                                $msg = $obj->error->message
                            );
                        }
                        return $obj->redirect->url;
                    }
                }else{
                    // Check if the API key is correctly configured
                    $global_settings = SUPER_Common::get_global_settings();
                    if( (!empty($global_settings['stripe_mode'])) && (empty($global_settings['stripe_sandbox_key'])) ) {
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = sprintf( esc_html__( 'Stripe Sandbox API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#stripe-checkout">', '</a>' )
                        );
                    }
                    if( (empty($global_settings['stripe_mode'])) && (empty($global_settings['stripe_live_key'])) ) {
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = sprintf( esc_html__( 'Stripe Live API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#stripe-checkout">', '</a>' )
                        );
                    }
                    // Check if iDeal element exists
                    if( !isset($data['stripe_ideal']) ) {
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = sprintf( esc_html__( 'No element found named %sstripe_ideal%s. Please make sure you added the Stripe iDeal element and named it %sstripe_ideal%s.', 'super-forms' ), '<strong>', '</strong>', '<strong>', '</strong>' )
                        );
                    }else{
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = esc_html__( 'Please choose a bank.', 'super-forms' )
                        );             
                    }
                }
            }
            return $redirect;
        }


        /**
         * Create Stripe Source
         *
         * @since       1.0.0
         */
        public function create_source($atts) {
            $settings = $atts['settings'];
            $data = $atts['post']['data'];
            // Check if Stripe checkout is enabled
            if($settings['stripe_checkout']=='true'){
                // If enabled determine what checkout method was choosen by the end user
                if( (!empty($data['stripe_ideal'])) && (!empty($data['stripe_ideal']['value'])) ) {
                    var_dump('Bank:');
                    var_dump($data['stripe_ideal']['value']);
                    // Create Source for iDeal payment
                    $url = 'https://api.stripe.com/v1/sources';
                    $response = wp_remote_post( 
                        $url, 
                        array(
                            'timeout' => 45,
                            'headers'=>array(
                                'Authorization' => 'Bearer sk_test_CczNHRNSYyr4TenhiCp7Oz05'
                            ),                      
                            'body' => array(
                                'type' => 'ideal',
                                'currency' => 'eur', // iDeal only supports EUR currency
                                'amount' => 15*100, // The amount to charge times hundred (because amount is in cents)
                                'owner' => array(
                                    'name' => 'Rens Tillmann',
                                    'email' => 'jenny.rosen@example.com'
                                ),
                                'redirect' => array(
                                    'return_url' => 'https://f4d.nl/dev' // Required for iDeal Source
                                )
                            )
                        )
                    );
                    if ( is_wp_error( $response ) ) {
                        $error_message = $response->get_error_message();
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $error_message
                        );
                    } else {
                        $obj = json_decode($response['body']);
                        var_dump($obj);
                        //$obj->redirect->url
                    }

                    // require_once( 'stripe-php/init.php' );
                    // $token = sanitize_text_field($data['_stripe_token']['value']);
                    // var_dump($token);
                    // \Stripe\Stripe::setApiKey("sk_test_CczNHRNSYyr4TenhiCp7Oz05");
                    // $response = \Stripe\Customer::create([
                    //   'description' => "Customer for feeling4design@gmail.com",
                    //   'email' => 'feeling4design@gmail.com',
                    //   'source' => $token // obtained with Stripe.js
                    // ]);
                    // var_dump($response);

                }else{
                    if(!isset($data['stripe_ideal'])){
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = sprintf( esc_html__( 'No element found named %sstripe_ideal%s. Please make sure your Stripe iDeal element is named %sstripe_ideal%s.', 'super-forms' ), '<strong>', '</strong>', '<strong>', '</strong>' )
                        ); 
                    }else{
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = esc_html__( 'Please choose a bank.', 'super-forms' )
                        );             
                    }
                }
            }
            exit;

            // $data = $atts['post']['data'];
            // $settings = $atts['settings'];
            // if(!empty($data['_stripe_token'])){
            //     require_once( 'stripe-php/init.php' );
            //     $token = sanitize_text_field($data['_stripe_token']['value']);
            //     var_dump($token);
            //     \Stripe\Stripe::setApiKey("sk_test_CczNHRNSYyr4TenhiCp7Oz05");
            //     $response = \Stripe\Customer::create([
            //       'description' => "Customer for feeling4design@gmail.com",
            //       'email' => 'feeling4design@gmail.com',
            //       'source' => $token // obtained with Stripe.js
            //     ]);
            //     var_dump($response);
            // }

        }


        /**
         * Stripe IPN (better know as WebHooks handler)
         *
         * @since       1.0.0
         */
        public function stripe_ipn() {

            if( !empty($_GET['client_secret']) ) {
                $status = $GLOBALS['stripe_obj']->status;

                // canceled == payment was canceled
                // pending == payment method can take up to a few days to be processed
                // chargeable == waiting for bank to process payment
                // failed == canceled by user or due to other reason
                // consumed == completed

                ?>
                <div class="verifying-payment">
                    <div class="wrapper">
                        <svg width="84px" height="84px" viewBox="0 0 84 84" version="1.1" xmlns="http://www.w3.org/2000/svg" xlink="http://www.w3.org/1999/xlink">
                            <circle class="border" cx="42" cy="42" r="40" stroke-linecap="round" stroke-width="4" stroke="#000" fill="none"></circle>
                        </svg>
                        <div class="caption verifying">
                            <div class="title">
                                <svg width="34px" height="34px" viewBox="0 0 84 84" version="1.1" xmlns="http://www.w3.org/2000/svg" xlink="http://www.w3.org/1999/xlink"></svg>
                                <span>Verifying payment...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                die();
            }

            if ((isset($_GET['page'])) && ($_GET['page'] == 'super_stripe_ipn')) {
                require_once( 'stripe-php/init.php' );
                // Set your secret key: remember to change this to your live secret key in production
                // See your keys here: https://dashboard.stripe.com/account/apikeys
                \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');
                // You can find your endpoint's secret in your webhook settings
                $endpoint_secret = 'whsec_ghatJ98Av3MmvhHiWHZ9DJfaJ8qEGj6n';
                $payload = file_get_contents('php://input');
                //error_log( "Stripe IPN Payload: " . json_encode($payload), 0 );

                $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
                $event = null;
                try {
                    $event = \Stripe\Webhook::constructEvent(
                        $payload, $sig_header, $endpoint_secret
                    );
                } catch(\UnexpectedValueException $e) {
                    // Invalid payload
                    http_response_code(400);
                    exit();
                } catch(\Stripe\Error\SignatureVerification $e) {
                    // Invalid signature
                    http_response_code(400);
                    exit();
                }


                // WebHook responses:
                // source.chargeable - A Source object becomes chargeable after a customer has authenticated and verified a payment.   
                // @Todo: Create a Charge.

                // source.failed - A Source object failed to become chargeable as your customer declined to authenticate the payment.  
                // @Todo: Cancel the order and optionally re-engage the customer in your payment flow.

                // source.canceled - A Source object expired and cannot be used to create a charge.  
                // @Todo: Cancel the order and optionally re-engage the customer in your payment flow.

                // charge.pending - The Charge is pending (asynchronous payments only). 
                // @Todo: Nothing to do.

                // charge.succeeded - The Charge succeeded and the payment is complete.
                // @Todo: Finalize the order and send a confirmation to the customer over email.
                
                // charge.failed - The Charge has failed and the payment could not be completed.
                // @Todo: Cancel the order and optionally re-engage the customer in your payment flow.

                // Handle the event
                do_action( 'super_stripe_' . str_replace('.', '_', $event->type), array( 'event'=>$event ) );

                $event = $event->__toArray(true);
                $payload = json_decode($payload, true);
                $metadata = $payload['data']['object']['metadata'];

                if($event['type']==='source.chargeable'){
                    // A Source object becomes chargeable after a customer has authenticated and verified a payment.   
                    // @Todo: Create a Charge. Create Transaction
                    // @Message: Your order was received and is awaiting payment confirmation.

                    // Create transaction
                    $post = array(
                        'post_status' => 'publish', // 'awaiting_payment_confirmation',
                        'post_type' => 'super_stripe_txn',
                        'post_title' => $event['data']['object']['id'],
                        'post_parent' => absint($metadata['_super_form_id']),
                        'post_author' => absint($metadata['_super_author_id'])
                    );
                    $post_id = wp_insert_post($post);
                    // Right after creating a post, add the post_id to the meta data
                    $metadata['_super_txn_id'] = $post_id;
                    $count = get_option( 'super_stripe_txn_count', 0 );
                    update_option( 'super_stripe_txn_count', ($count+1) );
                    // Connect transaction to contact entry if an Entry was created
                    if(!empty($metadata['_super_contact_entry_id'])){
                        $contact_entry_id = absint($metadata['_super_contact_entry_id']);
                        update_post_meta( $contact_entry_id, '_super_contact_entry_stripe_txn_id', $post_id );
                        // Update contact entry status after succesfull payment
                        if( !empty($settings['stripe_completed_entry_status']) ) {
                            update_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['stripe_completed_entry_status'] );
                        }
                    }
                    // Update post status after succesfull payment (only used for Front-end Posting add-on)
                    if( (!empty($settings['stripe_completed_post_status'])) && (!empty($metadata['_super_stripe_post_id'])) ) {
                        wp_update_post( 
                            array(
                                'ID' => absint($metadata['_super_stripe_post_id']),
                                'post_status' => $settings['stripe_completed_post_status']
                            )
                        );
                    }
                    // Update user status after succesfull payment (only used for Front-end Register & Login add-on)
                    if( (!empty($settings['register_login_action'])) && ($settings['register_login_action']=='register') && (!empty($metadata['_super_stripe_user_id'])) ) {
                        $user_id = absint($metadata['_super_stripe_user_id']);
                        if( ($user_id!=0) && (!empty($settings['stripe_completed_signup_status'])) ) {
                            update_user_meta( $user_id, 'super_user_login_status', $settings['stripe_completed_signup_status'] );
                        }
                    }
                    // Save all transaction data
                    add_post_meta( $post_id, '_super_txn_event_data', $event );
                    add_post_meta( $post_id, '_super_txn_payload_data', $payload );

                    // A Source object becomes chargeable after a customer has authenticated and verified a payment.   
                    // @Todo: Create a Charge.
                    // @Message: Your order was received and is awaiting payment confirmation.
                    $charge = \Stripe\Charge::create([

                        // amount
                        // REQUIRED
                        // A positive integer representing how much to charge in the smallest currency unit (e.g., 100 cents to charge $1.00 or 100 to charge ¥100, a zero-decimal currency). The minimum amount is $0.50 US or equivalent in charge currency. The amount value supports up to eight digits (e.g., a value of 99999999 for a USD charge of $999,999.99).
                        'amount' => $event['data']['object']['amount'], // e.g: 1099,

                        // currency
                        // REQUIRED
                        // Three-letter ISO currency code, in lowercase. Must be a supported currency.
                        'currency' => $event['data']['object']['currency'], // e.g: 'eur',

                        // application_fee_amount
                        // optional
                        // A fee in cents that will be applied to the charge and transferred to the application owner’s Stripe account. The request must be made with an OAuth key or the Stripe-Account header in order to take an application fee. For more information, see the application fees documentation.
                        //'application_fee_amount' => 1*100,

                        // capture
                        // optional
                        // Whether to immediately capture the charge. Defaults to true. When false, the charge issues an authorization (or pre-authorization), and will need to be captured later. Uncaptured charges expire in seven days. For more information, see the authorizing charges and settling later documentation.

                        // customer
                        // optional
                        // The ID of an existing customer that will be charged in this request.

                        // description
                        // optional
                        // An arbitrary string which you can attach to a Charge object. It is displayed when in the web interface alongside the charge. Note that if you use Stripe to send automatic email receipts to your customers, your receipt emails will include the description of the charge(s) that they are describing. This can be unset by updating the value to null and then saving.
                        'description' => '1 year license for Super Forms',

                        // metadata
                        // optional associative array
                        // Set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
                        // 'metadata' => array(
                        //     'custom1' => 'Custom 1',
                        //     'custom2' => 'Custom 2',
                        //     'custom3' => 'Custom 3'
                        // ),
                        'metadata' => $metadata,

                        // on_behalf_of
                        // optional
                        // The Stripe account ID for which these funds are intended. Automatically set if you use the destination parameter. For details, see Creating Separate Charges and Transfers.

                        // receipt_email
                        // optional
                        // The email address to which this charge’s receipt will be sent. The receipt will not be sent until the charge is paid, and no receipts will be sent for test mode charges. If this charge is for a Customer, the email address specified here will override the customer’s email address. If receipt_email is specified for a charge in live mode, a receipt will be sent regardless of your email settings.
                        'receipt_email' => 'feeling4design@gmail.com',

                        // shipping
                        // optional associative array
                        // Shipping information for the charge. Helps prevent fraud on charges for physical goods.
                        'shipping' => array(
                            'address' => array(
                                'line1' => 'Korenweg 25',
                                'city' => 'Silvolde',
                                'country' => 'the Netherlands',
                                'line2' => '',
                                'postal_code' => '7064BW',
                                'state' => 'Gelderland'
                            ),
                            'name' => 'Rens Tillmann',
                            'carrier' => 'USPS',
                            'phone' => '0634441193',
                            'tracking_number' => 'XXX-XXX-XXXXXX'
                        ),

                        // Hide child arguments
                        // shipping.address
                        // REQUIRED
                        // Shipping address.

                        // Hide child arguments
                        // shipping.address.line1
                        // REQUIRED
                        // shipping.address.city
                        // optional
                        // shipping.address.country
                        // optional
                        // shipping.address.line2
                        // optional
                        // shipping.address.postal_code
                        // optional
                        // shipping.address.state
                        // optional
                        // shipping.name
                        // REQUIRED
                        // Recipient name. This can be unset by updating the value to null and then saving.

                        // shipping.carrier
                        // optional
                        // The delivery service that shipped a physical product, such as Fedex, UPS, USPS, etc. This can be unset by updating the value to null and then saving.

                        // shipping.phone
                        // optional
                        // Recipient phone (including extension). This can be unset by updating the value to null and then saving.

                        // shipping.tracking_number
                        // optional
                        // The tracking number for a physical product, obtained from the delivery service. If multiple tracking numbers were generated for this purchase, please separate them with commas. This can be unset by updating the value to null and then saving.

                        // source
                        // optional
                        // A payment source to be charged. This can be the ID of a card (i.e., credit or debit card), a bank account, a source, a token, or a connected account. For certain sources—namely, cards, bank accounts, and attached sources—you must also pass the ID of the associated customer.
                        'source' => $event['data']['object']['id'], // e.g: 'src_18eYalAHEMiOZZp1l9ZTjSU0',

                        // statement_descriptor
                        // optional
                        // An arbitrary string to be used as the dynamic portion of the full descriptor displayed on your customer’s credit card statement. This value will be prefixed by your account’s statement descriptor. As an example, if your account’s statement descriptor is RUNCLUB and the item you’re charging for is a race ticket, you may want to specify a statement_descriptor of 5K RACE, so that the resulting full descriptor would be RUNCLUB* 5K RACE. The full descriptor may be up to 22 characters. This value must contain at least one letter, may not include <>"' characters, and will appear on your customer’s statement in capital letters. Non-ASCII characters are automatically stripped. While most banks display this information consistently, some may display it incorrectly or not at all.
                        
                        // Charges on single-use sources of type `ideal` do not support the `statement_descriptor` attribute. 
                        // Use the `source[ideal][statement_descriptor]` attribute instead
                        //'statement_descriptor' => '1 year license' 

                        // transfer_data
                        // optional associative array
                        // An optional dictionary including the account to automatically transfer to as part of a destination charge. See the Connect documentation for details.

                        // Hide child arguments
                        // transfer_data.destination
                        // REQUIRED
                        // ID of an existing, connected Stripe account.

                        // transfer_data.amount
                        // optional
                        // The amount transferred to the destination account, if specified. By default, the entire charge amount is transferred to the destination account.

                        // transfer_group
                        // optional
                        // A string that identifies this transaction as part of a group. For details, see Grouping transactions.

                    ]);
                    // Check for errors, if any errors where found log them
                    if( !empty($charge->error) ) {
                        // Delete the post
                        wp_delete_post( $post_id, true );
                        error_log( "Stripe Charge Error: " . $charge->error->message, 0 );
                    }
                }else{
                    // Update order data
                    if(isset($metadata['_super_txn_id'])){
                        $post_id = absint($metadata['_super_txn_id']);
                        if($post_id!==0){
                            // Save all transaction data
                            update_post_meta( $post_id, '_super_txn_event_data', $event );
                            update_post_meta( $post_id, '_super_txn_payload_data', $payload );
                        }
                    }
                }

                // switch ($event['type']) {
                //     case 'source.chargeable':
                //         break;
                //         // Save Stirpe Transaction t
                //         //     $contact_entry_id = absint($custom[2]);

                //         //     // Save stripe order ID to contact entry
                //         //     update_post_meta( $contact_entry_id, '_super_contact_entry_stripe_order_id', $post_id );

                //         //$event->data->object->metadata

                //         // if( $_POST['txn_type']=='subscr_signup' ) {
                //         //     $post_status = 'publish';
                //         //     $post_type = 'super_stripe_sub';
                //         //     $post_title = $_POST['subscr_id'];
                //         // }else{
                //         //     $post_status = $_POST['payment_status'];
                //         //     $post_title = $_POST['txn_id'];
                //         // }
                //         // $post = array(
                //         //     'post_status' => sanitize_text_field($post_status),
                //         //     'post_type' => $post_type,
                //         //     'post_title' => sanitize_text_field($post_title),
                //         //     'post_parent' => absint($custom[0]),
                //         //     'post_author' => absint($custom[3])
                //         // );
                //         // $post_id = wp_insert_post($post);
                //         // if(isset($_POST['subscr_id'])){
                //         //     add_post_meta($post_id, '_super_sub_id', $_POST['subscr_id']);
                //         // }
                //         // if(isset($_POST['recurring_payment_id'])){
                //         //     add_post_meta($post_id, '_super_sub_id', $_POST['recurring_payment_id']);
                //         // }
                //         // add_post_meta( $post_id, '_super_txn_data', $_POST );
                //         // if( $_POST['txn_type']=='subscr_signup' ) {
                //         //     $count = get_option( 'super_stripe_sub_count', 0 );
                //         //     update_option( 'super_stripe_sub_count', ($count+1) );
                //         // }else{
                //         //     $count = get_option( 'super_stripe_txn_count', 0 );
                //         //     update_option( 'super_stripe_txn_count', ($count+1) );
                //         // }
                //         // if( (isset($custom[2])) && ($custom[2]!=0) ) {
                //         //     $contact_entry_id = absint($custom[2]);

                //         //     // Save stripe order ID to contact entry
                //         //     update_post_meta( $contact_entry_id, '_super_contact_entry_stripe_order_id', $post_id );

                //         //     // Update contact entry status after succesfull payment
                //         //     if( !empty($settings['stripe_completed_entry_status']) ) {
                //         //         update_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['stripe_completed_entry_status'] );
                //         //     }
                //         // }
                //         // // Update post status after succesfull payment (only used for Front-end Posting add-on)
                //         // $post_id = absint($custom[4]);
                //         // if( ($post_id!=0) && (!empty($settings['stripe_completed_post_status'])) ) {
                //         //     wp_update_post( 
                //         //         array(
                //         //             'ID' => $post_id,
                //         //             'post_status' => $settings['stripe_completed_post_status']
                //         //         )
                //         //     );
                //         // }
                //         // // Update user status after succesfull payment (only used for Front-end Register & Login add-on)
                //         // if( !empty($settings['register_login_action']) ) {
                //         //     if( $settings['register_login_action']=='register' ) {
                //         //         $user_id = absint($custom[5]);
                //         //         if( ($user_id!=0) && (!empty($settings['stripe_completed_signup_status'])) ) {
                //         //             update_user_meta( $user_id, 'super_user_login_status', $settings['stripe_completed_signup_status'] );
                //         //         }
                //         //     }
                //         // }
                //         // do_action( 'super_after_stripe_ipn_payment_verified', array( 'post_id'=>$post_id, 'post'=>$_POST ) );
                //     case 'source.failed':
                //         // A Source object failed to become chargeable as your customer declined to authenticate the payment.
                //         // @Todo: Cancel the order and optionally re-engage the customer in your payment flow.
                //         // @Message: Your payment failed and your order couldn’t be processed.
                //         break;
                //     case 'source.canceled':
                //         // A Source object expired and cannot be used to create a charge.
                //         // @Todo: Cancel the order and optionally re-engage the customer in your payment flow.
                //         // @Message: Your payment failed and your order couldn’t be processed.
                //         break;
                //     case 'source.pending':
                //         // @Message: Your order was received and is awaiting payment confirmation.
                //     // Occurs whenever a previously uncaptured charge is captured.
                //     case 'charge.captured':
                //         break;
                //     // Occurs whenever an uncaptured charge expires.
                //     case 'charge.expired':
                //         break;
                //     // Occurs whenever a failed charge attempt occurs.
                //     case 'charge.failed':
                //         // The Charge has failed and the payment could not be completed.
                //         // @Todo: Cancel the order and optionally re-engage the customer in your payment flow.
                //         // @Message: Your payment failed and your order couldn’t be processed.
                //         break;
                //     // Occurs whenever a pending charge is created.
                //     case 'charge.pending':
                //         // The Charge is pending (asynchronous payments only)
                //         // @Todo: Nothing to do.
                //         // @Message: Your order was received and is awaiting payment confirmation.
                //         break;
                //     // Occurs whenever a charge is refunded, including partial refunds.
                //     case 'charge.refunded':
                //         break;
                //     // Occurs whenever a new charge is created and is successful.
                //     case 'charge.succeeded':
                //         // The Charge succeeded and the payment is complete.
                //         // @Todo: Finalize the order and send a confirmation to the customer over email.
                //         // @Message: Your payment is confirmed and your order is complete.
                //         break;
                //     // Occurs whenever a charge description or metadata is updated.
                //     case 'charge.updated':
                //         break;
                //     // Occurs when a dispute is closed and the dispute status changes to lost, warning_closed, or won.
                //     case 'charge.dispute.closed':
                //         break;
                //     // Occurs whenever a customer disputes a charge with their bank.
                //     case 'charge.dispute.created':
                //         break;
                //     // Occurs when funds are reinstated to your account after a dispute is closed. This includes partially refunded payments.
                //     case 'charge.dispute.funds_reinstated':
                //         break;
                //     // Occurs when funds are removed from your account due to a dispute.
                //     case 'charge.dispute.funds_withdrawn':
                //         break;
                //     // Occurs when the dispute is updated (usually with evidence).
                //     case 'charge.dispute.updated':
                //         break;
                //     // Occurs whenever a refund is updated, on selected payment methods.
                //     case 'charge.refund.updated':
                //         break;
                //     default:
                //         // Unexpected event type
                //         error_log( "Unexpected event type: " . $event['type'] );
                //         http_response_code(400);
                //         exit();
                // }
                // error_log( "Stripe Payment1 " . json_encode($event->data->object->id), 0 );
                // error_log( "Stripe Payment2 " . json_encode((array) $event->data->object), 0 );
                // error_log( "Stripe Payment3 " . json_encode((array) $event->data), 0 );

                // // For debugging purposes only:
                // SUPER_Common::email( 
                //     $to = 'feeling4design@gmail.com', 
                //     $from = 'no-reply@f4d.nl', 
                //     $from_name = 'f4d.nl', 
                //     $custom_reply = false, 
                //     $reply = '', 
                //     $reply_name = '', 
                //     $cc = '',
                //     $bcc = '',
                //     $subject = 'Stripe Payment ['.$event->type.']',
                //     $body = 'Stripe Payment '. $event->type,
                //     $settings = array(), 
                //     $attachments = array(), 
                //     $string_attachments = array() 
                // );

                http_response_code(200);
                die();
            }
        }


        /**
         * Redirect to Stripe Checkout page
         *
         * @since       1.0.0
         */
        public function payment_completed_email($atts) {
            $event = $atts['event'];
            SUPER_Common::email( 
                $to = 'feeling4design@gmail.com', 
                $from = 'no-reply@f4d.nl', 
                $from_name = 'f4d.nl', 
                $custom_reply = false, 
                $reply = '', 
                $reply_name = '', 
                $cc = '',
                $bcc = '',
                $subject = 'Stripe Payment Completed',
                $body = 'Stripe Payment Completed',
                $settings = array(), 
                $attachments = array(), 
                $string_attachments = array() 
            );
        }


        /**
         * Hook into elements and add Stripe element
         *
         *  @since      1.0.0
        */
        public static function stripe_request( $atts ) {
            // \Stripe\Stripe::setApiKey("pk_test_1i3UyFAuxbe3Po62oX1FV47U");

            // $endpoint = \Stripe\WebhookEndpoint::create([
            //   "url" => "https://example.com/my/webhook/endpoint",
            //   "enabled_events" => ["charge.failed", "charge.succeeded"]
            // ]);

            $data = $atts['post']['data'];
            $settings = $atts['settings'];
            if(!empty($data['_stripe_token'])){
                require_once( 'stripe-php/init.php' );
                $token = sanitize_text_field($data['_stripe_token']['value']);
                var_dump($token);
                \Stripe\Stripe::setApiKey("sk_test_CczNHRNSYyr4TenhiCp7Oz05");
                $response = \Stripe\Customer::create([
                  'description' => "Customer for feeling4design@gmail.com",
                  'email' => 'feeling4design@gmail.com',
                  'source' => $token // obtained with Stripe.js
                ]);
                var_dump($response);
            }


            // Set your secret key: remember to change this to your live secret key in production
            // See your keys here: https://dashboard.stripe.com/account/apikeys
            // \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');
            // $charge = \Stripe\Charge::create([
            //     'amount' => 999,
            //     'currency' => 'usd',
            //     'source' => 'tok_visa',
            //     'receipt_email' => 'jenny.rosen@example.com',
            // ]);


            // // Create subsciption
            // // Set your secret key: remember to change this to your live secret key in production
            // // See your keys here: https://dashboard.stripe.com/account/apikeys
            // \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');

            // $subscription = \Stripe\Subscription::create([
            //     'customer' => 'cus_4fdAW5ftNQow1a',
            //     'items' => [['plan' => 'plan_CBb6IXqvTLXp3f']],
            //     'coupon' => 'free-period',
            // ]);


            // Create a subscription [requires to create a customer beforehand]

            // customer [required]                                          - The identifier of the customer to subscribe.
            // items [required]                                             - List of subscription items, each with an attached plan.
            // items.plan [required]                                        - Plan ID for this item, as a string.
            // items.billing_thresholds [optional]                          - Define thresholds at which an invoice will be sent, and the subscription advanced to a new billing period
            // items.billing_thresholds.usage_gte [required]                - Usage threshold that triggers the subscription to advance to a new billing period
            // items.metadata [optional]                                    - Set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
            // items.quantity [optional]                                    - Quantity for this item.
            // items.tax_rates [optional]                                   - The tax rates which apply to this `subscription_item`. When set, the `default_tax_rates` on the subscription do not apply to this `subscription_item`.

            // coupon [optional]                                            - The code of the coupon to apply to this subscription. A coupon applied to a subscription will only affect invoices created for that particular subscription. This can be unset by updating the value to null and then saving.

            // // ADVANCED SETTINGS:

            // metadata [optional]                                          - A set of key-value pairs that you can attach to a `Subscription` object. It can be useful for storing additional information about the subscription in a structured format.
            // prorate [optional]                                           - Boolean (defaults to `true`) telling us whether to credit for unused time when the billing cycle changes (e.g. when switching plans, resetting `billing_cycle_anchor=now`, or starting a trial), or if an item’s `quantity` changes. If `false`, the anchor period will be free (similar to a trial) and no proration adjustments will be created.

            // trial_end [optional]                                         - Unix timestamp representing the end of the trial period the customer will get before being charged for the first time. This will always overwrite any trials that might apply via a subscribed plan. If set, trial_end will override the default trial period of the plan the customer is being subscribed to. The special value `now` can be provided to end the customer’s trial immediately. Can be at most two years from `billing_cycle_anchor`

            // trial_from_plan [optional]                                   - Indicates if a plan’s `trial_period_days` should be applied to the subscription. Setting `trial_end` per subscription is preferred, and this defaults to `false`. Setting this flag to `true` together with `trial_end` is not allowed.
            // @IMPORTANT
            // trial_period_days [optional]                                 - Integer representing the number of trial period days before the customer is charged for the first time. This will always overwrite any trials that might apply via a subscribed plan.
            // @IMPORTANT

            // application_fee_percent [optional]                           - A non-negative decimal between 0 and 100, with at most two decimal places. This represents the percentage of the subscription invoice subtotal that will be transferred to the application owner’s Stripe account. 
            // billing [optional]                                           - Either `charge_automatically`, or `send_invoice`. When charging automatically, Stripe will attempt to pay this subscription at the end of the cycle using the default source attached to the customer. When sending an invoice, Stripe will email your customer an invoice with payment instructions.
            // billing_cycle_anchor [optional]                              - A future timestamp to anchor the subscription’s billing cycle. This is used to determine the date of the first full invoice, and, for plans with month or year intervals, the day of the month for subsequent invoices.
            // billing_thresholds [optional]                                - Define thresholds at which an invoice will be sent, and the subscription advanced to a new billing period. Pass an empty string to remove previously-defined thresholds.
            // billing_thresholds.amount_gte [optional]                     - Monetary threshold that triggers the subscription to advance to a new billing period
            // billing_thresholds.reset_billing_cycle_anchor [optional]     - Indicates if the `billing_cycle_anchor` should be reset when a threshold is reached. If true, `billing_cycle_anchor` will be updated to the date/time the threshold was last reached; otherwise, the value will remain unchanged.
            // cancel_at_period_end [optional]                              - Boolean indicating whether this subscription should cancel at the end of the current period.
            // days_until_due [optional]                                    - Number of days a customer has to pay invoices generated by this subscription. Valid only for subscriptions where `billing` is set to `send_invoice`.
            // default_payment_method [optional]                            - ID of the default payment method for the subscription. It must belong to the customer associated with the subscription. If not set, invoices will use the default payment method in the customer’s invoice settings.
            // default_source [optional]                                    - ID of the default payment source for the subscription. It must belong to the customer associated with the subscription and be in a chargeable state. If not set, defaults to the customer’s default source.
            // default_tax_rates [optional]                                 - The tax rates that will apply to any subscription item that does not have tax_rates set. Invoices created will have their default_tax_rates populated from the subscription.

            //var_dump($charge);
            //exit;

        }


        /**
         * Hook into elements and add Stripe element
         *
         *  @since      1.0.0
        */
        public static function add_stripe_element( $array, $attributes ) {

            // Include the predefined arrays
            require( SUPER_PLUGIN_DIR . '/includes/shortcodes/predefined-arrays.php' );
            $array['form_elements']['shortcodes']['stripe'] = array(
                'callback' => 'SUPER_Stripe::stripe_cc',
                'name' => 'Credit card',
                'icon' => 'stripe;fab',
                'atts' => array(
                    'general' => array(
                        'name' => __( 'General', 'super-forms' ),
                        'fields' => array(
                            // 'name' => SUPER_Shortcodes::name( $attributes, '' ),
                        )
                    )
                )
            );

            $banks = array(
                'abn_amro' => 'ABN Amro',
                'asn_bank' => 'ASN Bank',
                'bunq' => 'bunq B.V.‎',
                'handelsbanken' => 'Handelsbanken',
                'ing' => 'ING Bank',
                'knab' => 'Knab',
                'moneyou' => 'Moneyou',
                'rabobank' => 'Rabobank',
                'regiobank' => 'RegioBank',
                'sns_bank' => 'SNS Bank',
                'triodos_bank' => 'Triodos Bank',
                'van_lanschot' => 'Van Lanschot'
            );
            $dropdown_items = array();
            foreach($banks as $k => $v){
                $dropdown_items[] = array(
                    'checked' => false,
                    'label' => $v,
                    'value' => $k
                );
            }
            $array['form_elements']['shortcodes']['stripe_ideal'] = array(
                'name' => esc_html__( 'iDeal', 'super-forms' ),
                'icon' => 'stripe;fab',
                'predefined' => array(
                    array(
                        'tag' => 'dropdown',
                        'group' => 'form_elements',
                        'data' => array(
                            'name' => esc_html__( 'stripe_ideal', 'super-forms' ),
                            'email' => esc_html__( 'Stripe iDeal:', 'super-forms' ),
                            'placeholder' => esc_html__( '- selecteer uw bank -', 'super-forms' ),
                            'icon' => 'caret-square-down;far',
                            'dropdown_items' => $dropdown_items,
                            'validation' => 'empty',
                            'error' => esc_html__( 'Selecteer uw bank!', 'super-forms' )
                        )
                    )
                ),
                'atts' => array(),
            );


            // $banks = array(
            //     'ABN Amro',
            //     'ASN Bank',
            //     'bunq B.V.‎',
            //     'Handelsbanken',
            //     'ING Bank',
            //     'Knab',
            //     'Moneyou',
            //     'Rabobank',
            //     'RegioBank',
            //     'SNS Bank',
            //     'Triodos Bank',
            //     'Van Lanschot'
            // );
            // $dropdown_items = array();
            // foreach($banks as $v){
            //     $dropdown_items[] = array(
            //         'checked' => false,
            //         'label' => $v,
            //         'value' => $v
            //     );
            // }
            // $array['form_elements']['shortcodes']['stripe_ideal'] = array(
            //     'name' => 'iDeal',
            //     'icon' => 'stripe;fab',
            //     'predefined' => array(
            //         array(
            //             'tag' => 'dropdown',
            //             'group' => 'form_elements',
            //             'data' => array(
            //                 'dropdown_items' => $dropdown_items,
            //                 'name' => esc_html__( 'ideal', 'super-forms' ),
            //                 'email' => esc_html__( 'iDeal:', 'super-forms' ),
            //                 'placeholder' => esc_html__( '- selecteer uw bank -', 'super-forms' ),
            //                 'validation' => 'empty',
            //                 'icon' => 'caret-square-down;far',
            //             )
            //         )
            //     ),
            //     'atts' => array(),
            // );

            return $array;
        }


        /**
         * Handle the Stripe iDeal element output
         *
         *  @since      1.0.0
        */
        public static function stripe_ideal( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {
            // Fallback check for older super form versions
            if (method_exists('SUPER_Common','generate_array_default_element_settings')) {
                $defaults = SUPER_Common::generate_array_default_element_settings($shortcodes, 'form_elements', $tag);
            }else{
                $defaults = array(
                    // 'name' => 'subtotal'
                );
            }
            $atts = wp_parse_args( $atts, $defaults );
            // @since Super Forms 4.7.0 - translation
            if (method_exists('SUPER_Shortcodes','merge_i18n')) {
                $atts = SUPER_Shortcodes::merge_i18n($atts, $i18n); 
            }
            // wp_enqueue_style( 'super-calculator', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/calculator.css', array(), SUPER_Calculator()->version );
            // wp_enqueue_script( 'super-stripe', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/calculator.js', array( 'jquery', 'super-common' ), SUPER_Calculator()->version );

            // Enqueu required scripts
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_Stripe()->version, false );  
            $handle = 'super-stripe-ideal';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'scripts-ideal.js', array( 'jquery', 'super-common' ), SUPER_Stripe()->version, false );  
            $global_settings = SUPER_Common::get_global_settings();
            wp_localize_script(
                $handle,
                $name,
                array( 
                    'stripe_pk' => $global_settings['stripe_pk'],
                    'styles' => array(
                        
                    )
                )
            );
            wp_enqueue_script( $handle );

            $result = SUPER_Shortcodes::opening_tag( $tag, $atts );
            $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );

            $result .= '<input hidden class="super-shortcode-field super-hidden" data-validation="empty" type="text" name="super_stripe_ideal" style="display:none;"';
            $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
            $result .= ' />';
            $result .= '<div class="super-stripe-ideal-element"></div>';
            $result .= '<div class="super-ideal-errors" role="alert"></div>';

            
            $result .= '</div>';
            $result .= SUPER_Shortcodes::loop_conditions( $atts );
            $result .= '</div>';
            return $result;        
        }


        /**
         * Handle the Stripe Credit Card element output
         *
         *  @since      1.0.0
        */
        public static function stripe_cc( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {
            // Fallback check for older super form versions
            if (method_exists('SUPER_Common','generate_array_default_element_settings')) {
                $defaults = SUPER_Common::generate_array_default_element_settings($shortcodes, 'form_elements', $tag);
            }else{
                $defaults = array(
                    // 'name' => 'subtotal'
                );
            }
            $atts = wp_parse_args( $atts, $defaults );
            // @since Super Forms 4.7.0 - translation
            if (method_exists('SUPER_Shortcodes','merge_i18n')) {
                $atts = SUPER_Shortcodes::merge_i18n($atts, $i18n); 
            }
            // wp_enqueue_style( 'super-calculator', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/calculator.css', array(), SUPER_Calculator()->version );
            // wp_enqueue_script( 'super-stripe', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/calculator.js', array( 'jquery', 'super-common' ), SUPER_Calculator()->version );

            // Enqueu required scripts
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_Stripe()->version, false );  
            $handle = 'super-stripe-cc';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'scripts-cc.js', array( 'jquery', 'super-common' ), SUPER_Stripe()->version, false );  
            $global_settings = SUPER_Common::get_global_settings();
            if(empty($global_settings['stripe_pk'])){
                $global_settings['stripe_pk'] = 'pk_test_1i3UyFAuxbe3Po62oX1FV47U';
            }
            wp_localize_script(
                $handle,
                $name,
                array( 
                    'stripe_pk' => $global_settings['stripe_pk']
                )
            );
            wp_enqueue_script( $handle );

            $result = SUPER_Shortcodes::opening_tag( $tag, $atts );
            $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );

            $result .= "<style></style>";
            $result .= '<div class="form-row">';
            $result .= '<div class="super-stripe-cc-element">';
            $result .= '</div>';
            $result .= '<!-- Used to display form errors. -->';
            $result .= '<div class="super-card-errors" role="alert"></div>';
            $result .= '</div>';

            $result .= SUPER_Shortcodes::common_attributes( $atts, $tag );
            $result .= '</div>';
            $result .= SUPER_Shortcodes::loop_conditions( $atts );
            $result .= '</div>';
            return $result;        
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
         * Enqueue scripts
         *
         *  @since      1.0.0
        */
        public function enqueue_scripts() {
            if( !empty($_GET['client_secret']) ) {
                $client_secret = sanitize_text_field($_GET['client_secret']);
                $livemode = sanitize_text_field($_GET['livemode']);
                $source = sanitize_text_field($_GET['source']);
                // Get Source status
                // https://f4d.nl/dev/?client_secret=src_client_secret_FAjQj85HSzhwvo4EzUTgC4dm&livemode=false&source=src_1EgNxUFKn7uROhgClC0MmsoJ
                $url = 'https://api.stripe.com/v1/sources/' . $source;
                $response = wp_remote_post( 
                    $url, 
                    array(
                        'timeout' => 45,
                        'headers'=>array(
                            'Authorization' => 'Bearer sk_test_CczNHRNSYyr4TenhiCp7Oz05'
                        ),                      
                        'body' => array()
                    )
                );
                if ( is_wp_error( $response ) ) {
                    $error_message = $response->get_error_message();
                    $GLOBALS['stripe_error_message'] = $error_message;
                } else {
                    $obj = json_decode($response['body']);
                    $GLOBALS['stripe_obj'] = $obj;
                }

                // Enqueue styles
                wp_enqueue_style( 'stripe-confirmation', plugin_dir_url( __FILE__ ) . 'confirmation.css', array(), SUPER_Stripe()->version );
                // Enqueue scripts
                wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_Stripe()->version, false ); 
                $handle = 'super-stripe-confirmation';
                $name = str_replace( '-', '_', $handle ) . '_i18n';
                wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'confirmation.js', array(), SUPER_Stripe()->version, false ); 
                $global_settings = SUPER_Common::get_global_settings();
                if(empty($global_settings['stripe_pk'])){
                    $global_settings['stripe_pk'] = 'pk_test_1i3UyFAuxbe3Po62oX1FV47U';
                }
                wp_localize_script(
                    $handle,
                    $name,
                    array( 
                        'stripe_pk' => $global_settings['stripe_pk'],
                        'status' => (!empty($GLOBALS['stripe_obj']) ? $GLOBALS['stripe_obj']->status : ''),
                        'client_secret' => $client_secret,
                        'livemode' => $livemode,
                        'source' => $source,

                        'chargeable' => esc_html__( 'Completing your order...', 'super_forms' ),
                        'consumed' => sprintf( 
                            esc_html__( '%sThank you for your order!%s%sWe\'ll send your receipt as soon as your payment is confirmed.%s', 'super_forms' ), 
                            '<div class="title">', 
                            '</div>', 
                            '<div class="description">', 
                            '</div>' 
                        ),
                        'pending' => sprintf( 
                            esc_html__( '%sPending payment!%s%sYour payment might be processed within a couple of days depending on your payment method.%s', 'super_forms' ), 
                            '<div class="title">', 
                            '</div>', 
                            '<div class="description">', 
                            '</div>' 
                        ),
                        'canceled' => sprintf( 
                            esc_html__( '%sPayment canceled!%s', 'super_forms' ), 
                            '<div class="title">', 
                            '</div>'
                        ),
                        'failed' => sprintf( 
                            esc_html__( '%sPayment failed!%s%sWe couldn\'t process your order.%s', 'super_forms' ), 
                            '<div class="title">', 
                            '</div>', 
                            '<div class="description">', 
                            '</div>' 
                        )


                    )
                );
                wp_enqueue_script( $handle );

            }

            // // https://js.stripe.com/v3/
         //    wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_Stripe()->version, false );  
            
         //    $handle = 'super-stripe';
         //    $name = str_replace( '-', '_', $handle ) . '_i18n';
         //    wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'scripts.js', array( 'jquery', 'super-common' ), SUPER_Stripe()->version, false );  
            
         //    $global_settings = SUPER_Common::get_global_settings();
         //    if(empty($global_settings['stripe_pk'])){
         //     $global_settings['stripe_pk'] = 'pk_test_1i3UyFAuxbe3Po62oX1FV47U';
         //    }
         //    wp_localize_script(
         //        $handle,
         //        $name,
         //        array( 
         //            'stripe_pk' => $global_settings['stripe_pk']
         //        )
         //    );
         //    wp_enqueue_script( $handle );

        }


        /**
         * Hook into JS filter and add the Stripe Token
         *
         *  @since      1.0.0
        */
        public static function add_dynamic_function( $functions ) {
            $functions['before_submit_hook'][] = array(
                'name' => 'stripe_cc_create_payment_method'
            );
            $functions['before_email_send_hook'][] = array(
                'name' => 'stripe_ideal_create_source'
            );
            $functions['after_email_send_hook'][] = array(
                'name' => 'stripe_ideal_redirect'
            );
            return $functions;
        }


        /**
         * Display activation message for automatic updates
         *
         *  @since      1.0.0
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
         * Hook into settings and add Stripe settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
           
            // Stripe Settings
            $array['stripe_checkout'] = array(        
                'name' => esc_html__( 'Stripe Checkout', 'super-forms' ),
                'label' => esc_html__( 'Stripe Checkout', 'super-forms' ),
                'html' => array( '<style>.super-settings .stripe-settings-html-notice {display:none;}</style>', '<p class="stripe-settings-html-notice">' . sprintf( esc_html__( 'Need to send more E-mails? You can increase the amount here:%s%s%sSuper Forms > Settings > Stripe Settings%s%s', 'super-forms' ), '<br />', '<a target="_blank" href="' . admin_url() . 'admin.php?page=super_settings#stripe-settings">', '<strong>', '</strong>', '</a>' ) . '</p>' ),
                'fields' => array(
                    'stripe_mode' => array(
                        'hidden' => true,
                        'default' => SUPER_Settings::get_value(0, 'stripe_mode', $settings['settings'], 'sandbox', true ),
                        'type' => 'checkbox',
                        'values' => array(
                            'sandbox' => esc_html__( 'Enable Stripe Sandbox/Test mode (for testing purposes only)', 'super-forms' ),
                        ),

                    ),
                    'stripe_live_public_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live Publishable key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'default' => SUPER_Settings::get_value(0, 'stripe_live_public_key', $settings['settings'], '' ),
                    ),
                    'stripe_live_secret_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live Secret key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'default' => SUPER_Settings::get_value(0, 'stripe_live_secret_key', $settings['settings'], '' ),
                    ),

                    'stripe_sandbox_public_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox Publishable key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'default' => SUPER_Settings::get_value(0, 'stripe_sandbox_public_key', $settings['settings'], '' ),
                    ),
                    'stripe_sandbox_secret_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox Secret key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'default' => SUPER_Settings::get_value(0, 'stripe_sandbox_secret_key', $settings['settings'], '' ),
                    ),

                    'stripe_email_amount' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Select how many individual E-mails you require', 'super-forms' ),
                        'desc' => esc_html__( 'If you need to send 3 individual E-mails enter: 3', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'stripe_email_amount', $settings['settings'], '2' )
                    ),

                    'stripe_checkout' => array(
                        'hidden_setting' => true,
                        'default' => SUPER_Settings::get_value(0, 'stripe_checkout', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter' => true,
                        'values' => array(
                            'true' => esc_html__( 'Enable Stripe Checkout', 'super-forms' ),
                        ),
                    ),
                    'stripe_amount' => array(
                        'name' => esc_html__( 'Amount to charge', 'super-forms' ),
                        'label' => esc_html__( 'You are allowed to use {tags}', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_amount', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_currency' => array(
                        'name' => esc_html__( 'Currency', 'super-forms' ),
                        'label' => esc_html__( 'Three-letter ISO code for the currency e.g: USD, AUD, EUR', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_currency', $settings['settings'], 'USD' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_statement_descriptor' => array(
                        'name' => esc_html__( 'Statement descriptor', 'super-forms' ),
                        'label' => esc_html__( 'For non-card charges, you can use this value as the complete description that appears on your customers statements. Must contain at least one letter, maximum 22 characters.', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_statement_descriptor', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_return_url' => array(
                        'name' => esc_html__( 'Return URL', 'super-forms' ),
                        'label' => esc_html__( 'The URL the customer should be redirected to after the authorization process.', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_return_url', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),

                    // Owner
                    'stripe_email' => array(
                        'name' => esc_html__( 'Owner\'s email address', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_email', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_name' => array(
                        'name' => esc_html__( 'Owner\'s full name', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_name', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_city' => array(
                        'name' => esc_html__( 'Owner\'s City', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_city', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_country' => array(
                        'name' => esc_html__( 'Owner\'s Country', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_country', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_line1' => array(
                        'name' => esc_html__( 'Owner\'s Address line1', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_line1', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_line2' => array(
                        'name' => esc_html__( 'Owner\'s Address line 2', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_line2', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_postal_code' => array(
                        'name' => esc_html__( 'Owner\'s Postal code', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_postal_code', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_state' => array(
                        'name' => esc_html__( 'Owner\'s State', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_state', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_phone' => array(
                        'name' => esc_html__( 'Owner\'s phone number', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value(0, 'stripe_phone', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                )
            );
             
            if(empty($settings['settings']['stripe_email_amount'])) $settings['settings']['stripe_email_amount'] = 3;
            $limit = absint($settings['settings']['stripe_email_amount']);
            if($limit==0) $limit = 2;

            $x = 1;
            while($x <= $limit) {
                $stripe_checkout = array(
                    'stripe_email_'.$x => array(
                        'hidden_setting' => true,
                        'desc' => sprintf( esc_html__( 'Send payment completed E-mail #%s', 'super-forms' ), $x ), 
                        'default' => SUPER_Settings::get_value( 0, 'stripe_email_'.$x, $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => sprintf( esc_html__( 'Send payment completed E-mail #%s', 'super-forms' ), $x ),
                        ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true'
                    )
                );
                $array['stripe_checkout']['fields'] = array_merge($array['stripe_checkout']['fields'], $stripe_checkout);

                $fields = $array['confirmation_email_settings']['fields'];
                $new_fields = array();
                foreach($fields as $k => $v){
                    if($k=='confirm'){
                        unset($fields[$k]);
                        continue;
                    }
                    if( !empty($v['parent']) ) {
                        if($v['parent']=='confirm'){
                            $v['parent'] = 'stripe_email_'.$x;
                            $v['filter_value'] = 'true';
                        }else{
                            $v['parent'] = str_replace('confirm_', 'stripe_email_'.$x.'_', $v['parent']);
                        }
                    }
                    unset($fields[$k]);
                    $k = str_replace('confirm_', 'stripe_email_'.$x.'_', $k);
                    if( !empty($v['default']) ) {
                        $v['default'] = SUPER_Settings::get_value( 0, $k, $settings['settings'], $v['default'] );
                    }
                    $v['hidden_setting'] = true;
                    $new_fields[$k] = $v;
                }
                $new_fields['stripe_email_'.$x.'_attachments'] = array(
                    'hidden_setting' => true,
                    'name' => sprintf( esc_html__( 'Attachments E-mail #%s', 'super-forms' ), $x ),
                    'desc' => esc_html__( 'Upload a file to send as attachment', 'super-forms' ),
                    'default'=> SUPER_Settings::get_value( 0, 'stripe_email_'.$x.'_attachments', $settings['settings'], '' ),
                    'type' => 'file',
                    'multiple' => 'true',
                    'filter'=>true,
                    'parent'=>'stripe_email_'.$x,
                    'filter_value'=>'true'
                );
                $array['stripe_checkout']['fields'] = array_merge($array['stripe_checkout']['fields'], $new_fields);
                $x++;
            }
            return $array;
        }

    }
        
endif;


/**
 * Returns the main instance of SUPER_Stripe to prevent the need to use globals.
 *
 * @return SUPER_Stripe
 */
if(!function_exists('SUPER_Stripe')){
    function SUPER_Stripe() {
        return SUPER_Stripe::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_Stripe'] = SUPER_Stripe();
}
