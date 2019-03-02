<?php
/**
 * Super Forms - Front-end Posting
 *
 * @package   Super Forms - Front-end Posting
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2015 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - Front-end Posting
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Let visitors create posts from your front-end website
 * Version:     1.2.4
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if(!class_exists('SUPER_Frontend_Posting')) :


    /**
     * Main SUPER_Frontend_Posting Class
     *
     * @class SUPER_Frontend_Posting
     */
    final class SUPER_Frontend_Posting {
    
        
        /**
         * @var string
         *
         *	@since		1.0.0
        */
        public $version = '1.2.4';

        
        /**
         * @var string
         *
         *  @since      1.1.0
        */
        public $add_on_slug = 'frontend_posting';
        public $add_on_name = 'Front-end Posting';


        /**
         * @var SUPER_Frontend_Posting The single instance of the class
         *
         *	@since		1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_Frontend_Posting Instance
         *
         * Ensures only one instance of SUPER_Frontend_Posting is loaded or can be loaded.
         *
         * @static
         * @see SUPER_Frontend_Posting()
         * @return SUPER_Frontend_Posting - Main instance
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
         * SUPER_Frontend_Posting Constructor.
         *
         *	@since		1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('SUPER_Frontend_Posting_loaded');
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

            // Filters since 1.2.2
            add_filter( 'super_redirect_url_filter', array( $this, 'redirect_to_post' ), 10, 2 );
            

            if ( $this->is_request( 'frontend' ) ) {
                
                // Filters since 1.0.0

                // Actions since 1.0.0

            }
            
            if ( $this->is_request( 'admin' ) ) {
                
                // Filters since 1.0.0
                add_filter( 'super_settings_after_smtp_server_filter', array( $this, 'add_settings' ), 10, 2 );
                add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_text_field_settings' ), 10, 2 );

                // Filters since 1.1.0
                add_filter( 'super_settings_end_filter', array( $this, 'activation' ), 100, 2 );
                
                // Actions since 1.1.0
                add_action( 'init', array( $this, 'update_plugin' ) );

                // Actions since 1.1.4
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );   

            }
            
            if ( $this->is_request( 'ajax' ) ) {

                // Filters since 1.0.0

                // Actions since 1.0.0
                add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ) );

            }
            
        }


        /**
         * Display activation message for automatic updates
         *
         *  @since      1.1.4
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
                SUPER_Forms::add_on_deactivate(SUPER_Frontend_Posting()->add_on_slug);
            }
        }


        /**
         * Check license and show activation message
         * 
         * @since       1.1.0
        */
        public function activation_message( $activation_msg, $data ) {
            if (method_exists('SUPER_Forms','add_on_activation_message')) {
                $form_id = absint($data['id']);
                $settings = $data['settings'];
                if( (isset($settings['frontend_posting_action'])) && ($settings['frontend_posting_action']!='none') ) {
                    return SUPER_Forms::add_on_activation_message($activation_msg, $this->add_on_slug, $this->add_on_name);
                }
            }
            return $activation_msg;
        }


        /**
         * Redirect to newly created Post
         * 
         * @since       1.2.2
        */
        public function redirect_to_post( $url, $attr ) {

            // Only check for URL in the session if setting was enabled
            // Check if option to redirect to created post is enabled in form settings
            if( !empty($attr['settings']['frontend_posting_redirect'] ) ) {
           
                // If setting was enabled, let's check if we can find the Post ID in the stored session
                $post_id = SUPER_Forms()->session->get( 'super_forms_frontend_posting_created_post' );
                $url = get_permalink( $post_id );
                
                // Make sure to reset the session to clear it from the database, and so that we won't have a redirect conflict with other possible forms
                SUPER_Forms()->session->set( 'super_forms_frontend_posting_created_post', false );
            }
            return $url;
        }


        /**
         * Hook into settings and add Text field settings
         *
         *  @since      1.0.0
        */
        public static function add_text_field_settings( $array, $attributes ) {
            
            // Make sure that older Super Forms versions also have the 
            // filter attribute set to true for the name setting field for text field element:
            $array['form_elements']['shortcodes']['text']['atts']['general']['fields']['name']['filter'] = true;

            // Now add the taxonomy settings field
            $fields_array = $array['form_elements']['shortcodes']['text']['atts']['general']['fields'];
            $res = array_slice($fields_array, 0, 1, true);
            $taxonomy['tag_taxonomy'] = array(
                'name' => __( 'The tag taxonomy name (e.g: post_tag or product_tag)', 'super-forms' ),
                'desc' => __( 'Required to connect the post to tags (if found)', 'super-forms' ),
                'default'=> ( !isset( $attributes['tag_taxonomy'] ) ? '' : $attributes['tag_taxonomy'] ),
                'filter' => true,
                'parent' => 'name',
                'filter_value' => 'tags_input',
                'required' => true
            );
            $taxonomy['cat_taxonomy'] = array(
                'name' => __( 'The cat taxonomy name (e.g: category or product_cat)', 'super-forms' ),
                'desc' => __( 'Required to connect the post to categories (if found)', 'super-forms' ),
                'default'=> ( !isset( $attributes['cat_taxonomy'] ) ? '' : $attributes['cat_taxonomy'] ),
                'filter' => true,
                'parent' => 'name',
                'filter_value' => 'tax_input',
                'required' => true
            );
            $res = $res + $taxonomy + array_slice($fields_array, 1, count($fields_array) - 1, true);



            $array['form_elements']['shortcodes']['text']['atts']['general']['fields'] = $res;
            return $array;

        }


        /**
         * Hook into before sending email and check if we need to create or update a post or taxonomy
         *
         *  @since      1.0.0
        */
        public static function before_email_success_msg( $atts ) {

            $settings = $atts['settings'];
            if( isset( $atts['data'] ) ) {
                $data = $atts['data'];
            }else{
                if( $settings['save_contact_entry']=='yes' ) {
                    $data = get_post_meta( $atts['entry_id'], '_super_contact_entry_data', true );
                }else{
                    $data = $atts['post']['data'];
                }
            }

            if( !isset( $settings['frontend_posting_action'] ) ) return true;
            if( $settings['frontend_posting_action']=='none' ) return true;

            // Create a new post
            if( $settings['frontend_posting_action']=='create_post' ) {
                
                // Lets check if post type exists
                if( $settings['frontend_posting_post_type']=='' ) $settings['frontend_posting_post_type'] = 'page';

                // Check if we are creating a new WooCommerce Order
                if( ($settings['frontend_posting_post_type']=='order') || ($settings['frontend_posting_post_type']=='order_subscription') ) {

                    // If creating a simple order
                    if($settings['frontend_posting_post_type']=='order'){
                        global $woocommerce;
                        $address_billing = array(
                            'first_name' => 'Rens',
                            'last_name'  => 'Tillmann',
                            'company'    => 'feeling4design',
                            'email'      => 'feeling4design@gmail.com',
                            'phone'      => '777-777-777-777',
                            'address_1'  => 'Korenweg 25',
                            'address_2'  => '', 
                            'city'       => 'Silvolde',
                            'state'      => 'Gelderland',
                            'postcode'   => '7064BW',
                            'country'    => 'NL'
                        );
                        $address_shipping = $address_billing;

                        $order = wc_create_order();

                        // Define the products and their quantity to be added to this order
                        // Product_ID => Quantity
                        $products = array( 
                            '34293' => 1,
                            '34499' => 2
                        );

                        // Add products to the order
                        foreach($products as $pid => $quantity){
                            $product_to_add = get_product( $pid );
                            $price = $product_to_add->get_price();
                            $price_params = array( 'totals' => array( 'subtotal' => $price, 'total' => $price ) );
                            $order->add_product( get_product( $pid ), $quantity, $price_params ); // pid 8 & qty 1
                        }
                        $order->calculate_totals();

                        // Set billing and shipping addresses
                        $order->set_address( $address_billing, 'billing' );
                        $order->set_address( $address_shipping, 'shipping' );

                        // Add the coupon
                        $coupon_code = 'coupon4';
                        $result = $order->apply_coupon( wc_clean( $coupon_code ) );
                        
                        $order->update_status("Completed", 'Imported order', TRUE); 
                        $order->save();
                        exit;       

                        // coupon1 = Percentage discount
                        // coupon2 = Fixed cart discount
                        // coupon3 = Fixed product discount
                        // coupon4 = Percentage discount + Free shipping

                        // Define discount based on the coupon code
                        /*$coupon_code = 'coupon2';
                        $coupon = new WC_Coupon($coupon_code);
                        $total_discount = 0;
                        $discount = array(
                            'code' => $coupon_code,
                            'type' => $coupon->get_discount_type(),
                            'amount' => $coupon->get_amount() // pennies
                        );

                        // Check if the coupon code exists, if so apply the coupon code to the order
                        if( $discount['type']=='fixed_cart' && $coupon->get_amount()==0 ) {
                            // nothing to do here, skip adding coupon
                        }else{
                            //var_dump($coupon->get_discount_type());
                            //var_dump($coupon->get_amount());
                            //var_dump($coupon);
                            // Check if coupon type is "Percentage discount"
                            if($discount['type']=='percent'){
                                // Loop through all products that need to be added
                                foreach($products as $pid => $quantity){
                                    $product_to_add = get_product( $pid );
                                    $sale_price = $product_to_add->get_price();
                                    // Here we calculate the final price with the discount
                                    $total_discount = $total_discount + round((($sale_price/100) * $discount['amount']), 2);
                                    $final_price = round($sale_price - (($sale_price/100) * $discount['amount']), 2);
                                    // Create the price params that will be passed with add_product(), if you have taxes you will need to calculate them here too
                                    $price_params = array( 'totals' => array( 'subtotal' => $sale_price, 'total' => $final_price ) );
                                    $order->add_product( get_product( $pid ), $quantity, $price_params ); // pid 8 & qty 1
                                }
                                // Set billing and shipping addresses
                                $order->set_address( $address_billing, 'billing' );
                                $order->set_address( $address_shipping, 'shipping' );
                                // Add the coupon
                                $order->add_coupon( $discount['code'], $total_discount ); // not pennies (use dollars amount)
                                $order->set_total( ($discount['amount']/100) , 'order_discount'); // not pennies (use dollars amount)
                            }

                            // Check if coupon type is "Fixed cart discount"
                            if($discount['type']=='fixed_cart'){
                            }

                            // Check if coupon type is "Fixed product discount"
                            if($discount['type']=='fixed_product'){
                            }
                        }
                        */

                        /*
                        // Now you can get type in your condition
                        if ( $coupon->get_discount_type() == 'cash_back_percentage' ){
                            // Get the coupon object amount
                            $coupons_amount1 = $coupon->get_amount();
                        }

                        // Or use this other conditional method for coupon type
                        if( $coupon->is_type( 'cash_back_fixed' ) ){
                            // Get the coupon object amount
                            $coupons_amount2 = $coupon->get_amount();
                        }
                        */

                        //var_dump($coupon['discount_type']);
                        // Discount types: percent, fixed_cart, fixed_product

                        
                        // Optionally set payment method
                        //$order->set_payment_method($this);
                        
                        // Optionally set shipping
                        //$rate = new WC_Shipping_Rate(  $response_body['shippingMethodCode'] , $ship_method_title, ($response_body['shippingCost']/100), array(), $response_body['shippingMethodCode'] );
                        //$order->add_shipping( $rate );

                        //$order->calculate_totals();
                        //$order->update_status("Completed", 'Imported order', TRUE); 
                        //$return_url = $this->get_return_url( $order );
                        //$order->add_coupon('10discount');
                        //$order->add_coupon('10discount', '10', '2'); // accepted param $couponcode, $couponamount, $coupon_tax
                        

                        /*
                        // Create the coupon
                        global $woocommerce;
                        $coupon = new WC_Coupon($coupon_code);
                        // Get the coupon discount amount (My coupon is a fixed value off)
                        $discount_total = $coupon->get_amount();
                        // Loop through products and apply the coupon discount
                        foreach($order->get_items() as $order_item){
                            $product_id = $order_item->get_product_id();

                            if($this->coupon_applies_to_product($coupon, $product_id)){
                                $total = $order_item->get_total();
                                $order_item->set_subtotal($total);
                                $order_item->set_total($total - $discount_total);
                                $order_item->save();
                            }
                        }
                        */
                        //$order->save();
                        //$order->apply_coupon( wc_clean( $coupon_code ) );
                        //exit;


                    }

                    // If creating an order subscription
                    if($settings['frontend_posting_post_type']=='order_subscription'){
                        global $woocommerce;
                        $email = 'test@test.com';
                        $start_date = '2015-01-01 00:00:00';
                        $address = array(
                            'first_name' => 'Jeremy',
                            'last_name'  => 'Test',
                            'company'    => '',
                            'email'      => $email,
                            'phone'      => '777-777-777-777',
                            'address_1'  => '31 Main Street',
                            'address_2'  => '', 
                            'city'       => 'Auckland',
                            'state'      => 'AKL',
                            'postcode'   => '12345',
                            'country'    => 'AU'
                        );
                        $default_password = wp_generate_password();
                        
                        // If user is not logged in or doesn't exist, create a new user with a random password and with the filled out email address
                        if (!$user = get_user_by('login', $email)) {
                            $user = wp_create_user( $email, $default_password, $email );
                        }

                        // I've used one product with multiple variations
                        $parent_product = wc_get_product(22998);
                        $args = array(
                            'attribute_billing-period' => 'Yearly',
                            'attribute_subscription-type' => 'Both'
                        );
                        $product_variation = $parent_product->get_matching_variation($args);
                        $product = wc_get_product($product_variation);  
                        
                        // Each variation also has its own shipping class
                        $shipping_class = get_term_by('slug', $product->get_shipping_class(), 'product_shipping_class');
                        WC()->shipping->load_shipping_methods();
                        $shipping_methods = WC()->shipping->get_shipping_methods();
                        
                        // I have some logic for selecting which shipping method to use; your use case will likely be different, so figure out the method you need and store it in $selected_shipping_method
                        $selected_shipping_method = $shipping_methods['free_shipping'];
                        $class_cost = $selected_shipping_method->get_option('class_cost_' . $shipping_class->term_id);
                        $quantity = 1;
                        
                        // As far as I can see, you need to create the order first, then the sub
                        $order = wc_create_order(array('customer_id' => $user->id));
                        $order->add_product( $product, $quantity, $args);
                        $order->set_address( $address, 'billing' );
                        $order->set_address( $address, 'shipping' );
                        $order->add_shipping((object)array (
                            'id' => $selected_shipping_method->id,
                            'label'    => $selected_shipping_method->title,
                            'cost'     => (float)$class_cost,
                            'taxes'    => array(),
                            'calc_tax'  => 'per_order'
                        ));
                        $order->calculate_totals();
                        $order->update_status("completed", 'Imported order', TRUE);

                        // Order created, now create sub attached to it -- optional if you're not creating a subscription, obvs
                        // Each variation has a different subscription period
                        $period = WC_Subscriptions_Product::get_period( $product );
                        $interval = WC_Subscriptions_Product::get_interval( $product );
                        $sub = wcs_create_subscription(array('order_id' => $order->id, 'billing_period' => $period, 'billing_interval' => $interval, 'start_date' => $start_date));
                        $sub->add_product( $product, $quantity, $args);
                        $sub->set_address( $address, 'billing' );
                        $sub->set_address( $address, 'shipping' );
                        $sub->add_shipping((object)array (
                            'id' => $selected_shipping_method->id,
                            'label'    => $selected_shipping_method->title,
                            'cost'     => (float)$class_cost,
                            'taxes'    => array(),
                            'calc_tax'  => 'per_order'
                        ));
                        $sub->calculate_totals();
                        WC_Subscriptions_Manager::activate_subscriptions_for_order($order);
                        print "<a href='/wp-admin/post.php?post=" . $sub->id . "&action=edit'>Sub created! Click here to edit</a>";
                    }

                }else{
                    // post_title and post_content are required so let's check if these are both set
                    if( (!isset( $data['post_title'])) || (!isset($data['post_content'])) ) {
                        $msg = __( 'We couldn\'t find the <strong>post_title</strong> and <strong>post_content</strong> fields which are required in order to create a new post. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again', 'super-forms' );
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }

                    // Lets check if post type exists
                    if( $settings['frontend_posting_post_type']=='' ) $settings['frontend_posting_post_type'] = 'page';
                    if ( !post_type_exists( $settings['frontend_posting_post_type'] ) ) {
                        $msg = sprintf( __( 'The post type <strong>%s</strong> doesn\'t seem to exist. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super-forms' ), $settings['frontend_posting_post_type'] );
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }

                    $postarr = array();
                    
                    // Default values from the form settings
                    $postarr['post_type'] = sanitize_text_field( $settings['frontend_posting_post_type'] );
                    $postarr['post_status'] = sanitize_text_field( $settings['frontend_posting_status'] );
                    $postarr['post_parent'] = absint( $settings['frontend_posting_post_parent'] );
                    $postarr['comment_status'] = sanitize_text_field( $settings['frontend_posting_comment_status'] );
                    $postarr['ping_status'] = sanitize_text_field( $settings['frontend_posting_ping_status'] );
                    $postarr['menu_order'] = absint( $settings['frontend_posting_menu_order'] );
                    $postarr['post_password'] = $settings['frontend_posting_post_password'];
                    if($settings['frontend_posting_author']!='') {
                        $postarr['post_author'] = absint( $settings['frontend_posting_author'] );
                    }else{
                        $user_id = get_current_user_id();
                        if( $user_id!=0 ) {
                            $postarr['post_author'] = $user_id;
                        }
                    }
                    $post_format = sanitize_text_field( $settings['frontend_posting_post_format'] );
                    $tax_input = sanitize_text_field( $settings['frontend_posting_tax_input'] );
                    $tags_input = sanitize_text_field( $settings['frontend_posting_tags_input'] );
                    $tag_taxonomy = sanitize_text_field( $settings['frontend_posting_post_tag_taxonomy'] );
                    $cat_taxonomy = sanitize_text_field( $settings['frontend_posting_post_cat_taxonomy'] );

                    // Override default values for form field values
                    $postarr['post_title'] = $data['post_title']['value'];
                    if( isset( $data['post_content'] ) ) $postarr['post_content'] = $data['post_content']['value'];
                    if( isset( $data['post_excerpt'] ) ) $postarr['post_excerpt'] = $data['post_excerpt']['value'];
                    if( isset( $data['post_type'] ) ) $postarr['post_type'] = sanitize_text_field( $data['post_type']['value'] );
                    if( isset( $data['post_format'] ) ) $post_format = sanitize_text_field( $data['post_format']['value'] );
                    if( isset( $data['tax_input'] ) ) $tax_input = sanitize_text_field( $data['tax_input']['value'] );
                    if( isset( $data['tags_input'] ) ) $tags_input = sanitize_text_field( $data['tags_input']['value'] );
                    if( isset( $data['tag_taxonomy'] ) ) $tag_taxonomy = sanitize_text_field( $data['tag_taxonomy']['value'] );
                    if( isset( $data['cat_taxonomy'] ) ) $cat_taxonomy = sanitize_text_field( $data['cat_taxonomy']['value'] );
                    if( isset( $data['post_status'] ) ) $postarr['post_status'] = sanitize_text_field( $data['post_status']['value'] );
                    if( isset( $data['post_parent'] ) ) $postarr['post_parent'] = absint( $data['post_parent']['value'] );
                    if( isset( $data['comment_status'] ) ) $postarr['comment_status'] = sanitize_text_field( $data['comment_status']['value'] );
                    if( isset( $data['ping_status'] ) ) $postarr['ping_status'] = sanitize_text_field( $data['ping_status']['value'] );
                    if( isset( $data['post_password'] ) ) $postarr['post_password'] = $data['post_password']['value'];
                    if( isset( $data['menu_order'] ) ) $postarr['menu_order'] = $data['menu_order']['value'];
                    if( isset( $data['post_author'] ) ) $postarr['post_author'] = absint( $data['post_author']['value'] );
                    if( (isset( $data['post_date'] )) && (isset( $data['post_time'] )) ) {
                        $postarr['post_time'] = date( 'H:i:s', strtotime($data['post_time']['value'] ) ); // Must be formatted as '18:57:33';
                        $postarr['post_date'] = date( 'Y-m-d', strtotime($data['post_date']['value'] ) ); // Must be formatted as '2010-02-23';
                        $postarr['post_date'] = $postarr['post_date'] . ' ' . $postarr['post_time']; // Must be formatted as '2010-02-23 18:57:33';
                    }else{
                        if( isset( $data['post_date'] ) ) {
                            $postarr['post_date'] = date( 'Y-m-d H:i:s', strtotime($data['post_date']['value'] ) ); // Must be formatted as '2010-02-23 18:57:33';
                        }
                    }
                    if( ($postarr['comment_status']=='open') || ($postarr['comment_status']=='1') || ($postarr['comment_status']=='yes') || ($postarr['comment_status']=='true') ) {
                        $postarr['comment_status'] = 'open';
                    }elseif( ($postarr['comment_status']=='closed') || ($postarr['comment_status']=='0') || ($postarr['comment_status']=='no') || ($postarr['comment_status']=='false') ) {
                        $postarr['comment_status'] = 'closed';
                    }else{
                        unset($postarr['comment_status']);
                    }

                    // Lets check if tax_input field exists
                    // If so, let's check if the post_taxonomy exists, because this is required in order to connect the categories accordingly to the post.
                    if( $tax_input!='' ) {
                        if( $cat_taxonomy=='' ) {
                            $msg = __( 'You have a field called <strong>tax_input</strong> but you haven\'t set a valid taxonomy name. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super-forms' );
                            SUPER_Common::output_error(
                                $error = true,
                                $msg = $msg,
                                $redirect = null
                            );
                        }else{
                            if ( !taxonomy_exists( $cat_taxonomy ) ) {
                                $msg = sprintf( __( 'The taxonomy <strong>%s</strong> doesn\'t seem to exist. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super-forms' ), $settings['frontend_posting_post_cat_taxonomy'] );
                                SUPER_Common::output_error(
                                    $error = true,
                                    $msg = $msg,
                                    $redirect = null
                                );
                            }
                        }
                    }

                    // Lets check if tags_input field exists
                    // If so, let's check if the tag_taxonomy exists, because this is required in order to connect the categories accordingly to the post.
                    if( $tags_input!='' ) {
                        if( $tag_taxonomy=='' ) {
                            $msg = __( 'You have a field called <strong>tags_input</strong> but you haven\'t set a valid taxonomy name. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super-forms' );
                            SUPER_Common::output_error(
                                $error = true,
                                $msg = $msg,
                                $redirect = null
                            );
                        }else{
                            if ( !taxonomy_exists( $tag_taxonomy ) ) {
                                $msg = sprintf( __( 'The taxonomy <strong>%s</strong> doesn\'t seem to exist. Please <a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">edit</a> your form and try again ', 'super-forms' ), $tag_taxonomy );
                                SUPER_Common::output_error(
                                    $error = true,
                                    $msg = $msg,
                                    $redirect = null
                                );
                            }
                        }
                    }

                    // @since 1.0.1
                    $postarr = apply_filters( 'super_front_end_posting_before_insert_post_filter', $postarr );

                    // Get the post ID or return the error(s)
                    $result = wp_insert_post( $postarr, true );
                    if( isset( $result->errors ) ) {
                        $msg = '';
                        foreach( $result->errors as $v ) {
                            $msg .= '- ' . $v[0] . '<br />';
                        }
                        SUPER_Common::output_error(
                            $error = true,
                            $msg = $msg,
                            $redirect = null
                        );
                    }else{

                        $post_id = $result;

                        // Check if we need to make this post sticky
                        if( isset( $data['stick_post'] ) ) {
                            $sticky = sanitize_text_field( $data['stick_post']['value'] );
                            if( ($sticky=='1') || ($sticky=='true') || ($sticky=='yes') ) {
                                stick_post($post_id);
                            }
                        }

                        // BuddyPress functions
                        // Make Topic sticky
                        if( function_exists( 'bbp_stick_topic' ) ) {
                            if( isset( $data['_bbp_topic_type'] ) ) {
                                $stickies = array( $post_id );
                                $stickies = array_values( $stickies );
                                if( $data['_bbp_topic_type']['value']=='super' ) {
                                    update_option( '_bbp_super_sticky_topics', $stickies );
                                }
                                if( $data['_bbp_topic_type']['value']=='stick' ) {
                                    update_post_meta( $postarr['post_parent'], '_bbp_sticky_topics', $stickies );
                                }
                            }
                        }
                        // Set parent for topics only
                        if( function_exists( 'bbp_get_topic_post_type' ) ) {
                            if( $postarr['post_type']==bbp_get_topic_post_type() ) {
                                update_post_meta( $post_id, '_bbp_author_ip', bbp_current_author_ip() );
                                if( isset( $postarr['post_parent'] ) ) {
                                    if( $postarr['post_parent']!=0 ) {
                                        update_post_meta( $post_id, '_bbp_forum_id', $postarr['post_parent'] );
                                    }
                                }
                            }
                        }
                        // Subscribe to the Topic
                        if( function_exists( 'bbp_add_user_subscription' ) ) {
                            if( isset( $data['bbp_subscribe'] ) ) {
                                $bbp_subscribe = filter_var( $data['bbp_subscribe']['value'], FILTER_VALIDATE_BOOLEAN );
                                if( $bbp_subscribe===true) {
                                    $result = bbp_add_user_subscription( $postarr['post_author'], $post_id );
                                }
                            }
                        }

                        // Collect categories from the field tax_input
                        if( $tax_input!='' ) {
                            $tax_input_array = array();
                            
                            // @since 1.1.4 - replace {tags}
                            $tax_input = SUPER_Common::email_tags( $tax_input, $data, $settings );
                            $cat_taxonomy = SUPER_Common::email_tags( $cat_taxonomy, $data, $settings );

                            $categories = explode( ",", $tax_input );
                            foreach( $categories as $slug ) {
                                $slug = trim($slug);
                                if( !empty( $slug ) ) {
                                    $tax_input_array[] = $slug;
                                }
                            }
                            wp_set_object_terms( $post_id, $tax_input_array, $cat_taxonomy );
                        }

                        // Collect tags from the field tags_input
                        if( $tags_input!='' ) {
                            $tags_input_array = array();

                            // @since 1.1.4 - replace {tags}
                            $tags_input = SUPER_Common::email_tags( $tags_input, $data, $settings );
                            $tag_taxonomy = SUPER_Common::email_tags( $tag_taxonomy, $data, $settings );

                            $tags = explode( ",", $tags_input );
                            foreach( $tags as $slug ) {
                                $slug = trim($slug);
                                if( !empty( $slug ) ) {
                                    $tags_input_array[] = $slug;
                                }
                            }
                            wp_set_object_terms($post_id, $tags_input_array, $tag_taxonomy );
                        }

                        // Check if we are saving a WooCommerce product
                        if( $postarr['post_type']=='product' ) {

                            // Set the product type (default = simple)
                            $product_type = sanitize_text_field( $settings['frontend_posting_product_type'] );
                            if( isset( $data['product_type'] ) ) $product_type = sanitize_text_field( $data['product_type']['value'] );
                            if( $product_type=='' ) $product_type = 'simple';
                            wp_set_object_terms( $post_id, $product_type, 'product_type' );

                            // Set the shipping class (default = none)
                            $shipping_class = 0;
                            if( isset( $data['product_shipping_class'] ) ) $shipping_class = absint( $data['product_shipping_class']['value'] );
                            if( $shipping_class!=0 ) {
                                wp_set_object_terms( $post_id, absint($shipping_class), 'product_shipping_class' );
                            }

                            // Save all the product meta data
                            $fields = array(
                                'product_downloadable' => '_downloadable',
                                'product_virtual' => '_virtual',
                                'product_visibility' => '_visibility',
                                'product_featured' => '_featured',
                                'product_stock_status' => '_stock_status',
                                'product_manage_stock' => '_manage_stock',
                                'product_stock' => '_stock',
                                'product_backorders' => '_backorders',
                                'product_sold_individually' => '_sold_individually',
                                'product_regular_price' => '_regular_price',
                                'product_sale_price' => '_sale_price',
                                'product_purchase_note' => '_purchase_note',
                                'product_weight' => '_weight',
                                'product_length' => '_length',
                                'product_width' => '_width',
                                'product_height' => '_height',
                                'product_sku' => '_sku',
                                'product_attributes' => '_product_attributes',
                                'product_sale_price_dates_from' => '_sale_price_dates_from',
                                'product_sale_price_dates_to' => '_sale_price_dates_to',
                                'product_price' => '_price',
                                
                                'product_downloadable_files' => '_downloadable_files',
                                'product_download_limit' => '_download_limit',
                                'product_download_expiry' => '_download_expiry',
                                'product_download_type' => '_download_type',

                                'product_url' => '_product_url',
                                'product_button_text' => '_button_text',
                                
                                'product_upsell_ids' => 'upsell_ids',
                                'product_crosssell_ids' => 'crosssell_ids',
                                
                                // Do we really need this? I don't think so, if a client requests this we will add it
                                // For now we will just comment it
                                //'product_total_sales' => 'total_sales',

                                // file paths will be stored in an array keyed off md5(file path)
                                //$downdloadArray =array('name'=>"Test", 'file' => $uploadDIR['baseurl']."/video/".$video);
                                //$file_path =md5($uploadDIR['baseurl']."/video/".$video);
                                //$_file_paths[  $file_path  ] = $downdloadArray;
                                // grant permission to any newly added files on any existing orders for this product
                                // do_action( 'woocommerce_process_product_file_download_paths', $post_id, 0, $downdloadArray );
                                //update_post_meta( $post_id, '_downloadable_files', $_file_paths);
                                //update_post_meta( $post_id, '_download_limit', '');
                                //update_post_meta( $post_id, '_download_expiry', '');
                                //update_post_meta( $post_id, '_download_type', '');

                            );

                            $product = wc_get_product( absint( $post_id ) );

                            foreach( $fields as $k => $v ) {

                                if( $product ) {

                                    $setting_key = 'frontend_posting_' . $k;

                                    // @since 1.2.0 - set featured, stock status, visibility, virtual, downloadable, sold method, backorders, stock, manage stock
                                    if( ($k=='product_featured') ||
                                        ($k=='product_stock_status') || 
                                        ($k=='product_visibility') || 
                                        ($k=='product_virtual') ||
                                        ($k=='product_downloadable') ||
                                        ($k=='product_sold_individually') ||
                                        ($k=='product_backorders') ||
                                        ($k=='product_stock') ||
                                        ($k=='product_manage_stock') ||
                                        ($k=='product_sale_price_dates_from') ||
                                        ($k=='product_sale_price_dates_to') ) {
                                            $field_value = '';
                                            if( isset( $settings[$setting_key] ) ) {
                                                $field_value = wc_clean( $settings[$setting_key] );
                                            }
                                            if( !empty($data[$k]) ) {
                                                $field_value = wc_clean( $data[$k]['value'] );
                                            }
                                            switch ( $k ) {
                                                case 'product_featured' :
                                                    if( $field_value=='yes' ) {
                                                        $product->set_featured( true );
                                                    }
                                                    break;
                                                case 'product_stock_status' :
                                                    if( $product_type!='external' ) {
                                                        $product->set_stock_status( $field_value );
                                                    }
                                                    break; 
                                                case 'product_visibility' :
                                                    $product->set_catalog_visibility( $field_value );
                                                    break; 
                                                case 'product_virtual' :
                                                    $product->set_virtual( $field_value );
                                                    break; 
                                                case 'product_downloadable' :
                                                    $product->set_downloadable( $field_value );
                                                    break; 
                                                case 'product_sold_individually' :
                                                    $product->set_sold_individually( $field_value );
                                                    break; 
                                                case 'product_backorders' :
                                                    if( $product_type!='external' ) {
                                                        $product->set_backorders( $field_value );
                                                    }
                                                    break; 
                                                case 'product_stock' :
                                                    $product->set_stock_quantity( $field_value );
                                                    break; 
                                                case 'product_manage_stock' :
                                                    $product->set_manage_stock( $field_value );
                                                    break; 
                                                case 'product_sale_price_dates_from' :
                                                    $product->set_date_on_sale_from( strtotime($field_value) );
                                                    break; 
                                                case 'product_sale_price_dates_to' :
                                                    $product->set_date_on_sale_to( strtotime($field_value) );
                                                    break; 
                                            }
                                            $product->save();
                                            continue;
                                    }

                                }
                             
                                if( $k=='product_downloadable_files' ) {
                                    if( isset( $data['downloadable_files'] ) ) {
                                        $files = array();
                                        $_file_paths = array();
                                        foreach( $data['downloadable_files']['files'] as $v ) {
                                            $name = get_the_title( $v['attachment'] );
                                            $url = $v['url'];
                                            $array = array( 'name'=>$name, 'file' => $url );
                                            $url = md5( $url );
                                            $_file_paths[$url] = $array;
                                        }
                                        update_post_meta( $post_id, '_downloadable_files', $_file_paths);
                                    }
                                    continue;
                                }


                                if( $k=='product_attributes' ) {
                                        
                                    // Lets make sure we loop through all the product attributes in case a column was set to use Add more + feature
                                    $_product_attributes = array();
                                    foreach( $data as $dk => $dv ) {
                                        if( ( ($dk=='product_attributes') || (strpos($dk, 'product_attributes_') !== false) ) && (strpos($dk, 'product_attributes_name') === false) ) {
                                            $counter = str_replace('product_attributes_', '', $dv['name']);
                                            $counter = absint($counter);
                                            $value = '';
                                            $visible = '1';
                                            $variation = '0';
                                            $taxonomy = '0';
                                            if( $counter==0 ) {
                                                $name = 'Variation 1';
                                                if( isset( $data['product_attributes_name'] ) ) {
                                                    $name = sanitize_text_field( $data['product_attributes_name']['value'] );
                                                }
                                                if( isset( $data['product_attributes'] ) ) {
                                                    $value = sanitize_text_field( $data['product_attributes']['value'] );
                                                }
                                                if( isset( $data['product_attributes_is_visible'] ) ) {
                                                    $visible = sanitize_text_field( $data['product_attributes_is_visible']['value'] );
                                                    if( ($visible=='1') || ($visible=='true') || ($visible=='yes') ) {
                                                        $visible = '1';
                                                    }
                                                }
                                                if( isset( $data['product_attributes_is_variation'] ) ) {
                                                    $variation = sanitize_text_field( $data['product_attributes_is_variation']['value'] );
                                                    if( ($variation=='1') || ($variation=='true') || ($variation=='yes') ) {
                                                        $variation = '1';
                                                    }
                                                }
                                                if( isset( $data['product_attributes_is_taxonomy'] ) ) {
                                                    $taxonomy = sanitize_text_field( $data['product_attributes_is_taxonomy']['value'] );
                                                    if( ($taxonomy=='1') || ($taxonomy=='true') || ($taxonomy=='yes') ) {
                                                        $taxonomy = '1';
                                                    }
                                                }
                                            }else{
                                                $name = 'Variation ' . $counter;
                                                if( isset( $data['product_attributes_name_' . $counter] ) ) {
                                                    $name = sanitize_text_field( $data['product_attributes_name_' . $counter]['value'] );
                                                }
                                                if( isset( $data['product_attributes_' . $counter] ) ) {
                                                    $value = sanitize_text_field( $data['product_attributes_' . $counter]['value'] );
                                                }                                            
                                                if( isset( $data['product_attributes_is_visible_' . $counter] ) ) {
                                                    $visible = sanitize_text_field( $data['product_attributes_is_visible_' . $counter]['value'] );
                                                    if( ($visible=='1') || ($visible=='true') || ($visible=='yes') ) {
                                                        $visible = '1';
                                                    }
                                                }
                                                if( isset( $data['product_attributes_is_variation_' . $counter] ) ) {
                                                    $variation = sanitize_text_field( $data['product_attributes_is_variation_' . $counter]['value'] );
                                                    if( ($variation=='1') || ($variation=='true') || ($variation=='yes') ) {
                                                        $variation = '1';
                                                    }
                                                }                                            
                                                if( isset( $data['product_attributes_is_taxonomy_' . $counter] ) ) {
                                                    $taxonomy = sanitize_text_field( $data['product_attributes_is_taxonomy_' . $counter]['value'] );
                                                    if( ($taxonomy=='1') || ($taxonomy=='true') || ($taxonomy=='yes') ) {
                                                        $taxonomy = '1';
                                                    }
                                                }
                                            }
                                            $term_taxonomy_ids = wp_set_object_terms( $post_id, $value, $name, true );
                                            $_product_attributes[$name]['name'] = $name;
                                            $_product_attributes[$name]['value'] = $value;
                                            $_product_attributes[$name]['is_visible'] = $visible;
                                            $_product_attributes[$name]['is_variation'] = $variation;
                                            $_product_attributes[$name]['is_taxonomy'] = $taxonomy;
                                        } 
                                    }
                                    update_post_meta( $post_id, '_product_attributes', $_product_attributes);
                                    continue;
                                }

                                $field_value = '';
                                if( isset( $settings['frontend_posting_'.$k] ) ) $field_value = sanitize_text_field( $settings['frontend_posting_'.$k] );
                                if( isset( $data[$k] ) ) $field_value = sanitize_text_field( $data[$k]['value'] );
                                update_post_meta( $post_id, $v, $field_value );
                            }

                            // Save custom product attributes
                            $attributes = explode( "\n", $settings['frontend_posting_product_attributes'] );
                            foreach( $attributes as $v ) {
                                if(empty($v)) continue;
                                $values = explode( "|", $v );
                                if( (isset($values[0])) && (isset($values[1])) ) {
                                    if(!isset($values[2])) $values[2] = '1';
                                    if(!isset($values[3])) $values[3] = '1';
                                    if(!isset($values[4])) $values[4] = '1';
                                    $values[1] = SUPER_Common::email_tags( $values[1], $data, $settings );
                                    $values[1] = explode(",", $values[1]);
                                    $attribute = $values[0];
                                    $attribute_value = $values[1];
                                    $is_visible = $values[2];
                                    $is_variation = $values[3];
                                    $is_taxonomy = $values[4];
                                    $term_taxonomy_ids = wp_set_object_terms( $post_id, $attribute_value, 'pa_'.$attribute, true );
                                    $_product_attributes['pa_'.$attribute]['name'] = 'pa_'.$attribute;
                                    $_product_attributes['pa_'.$attribute]['value'] = $attribute_value;
                                    $_product_attributes['pa_'.$attribute]['is_visible'] = $is_visible;
                                    $_product_attributes['pa_'.$attribute]['is_variation'] = $is_variation;
                                    $_product_attributes['pa_'.$attribute]['is_taxonomy'] = $is_taxonomy;
                                    update_post_meta( $post_id, '_product_attributes', $_product_attributes);
                                }
                            }

                            // If we are saving a WooCommerce product check if we need to add images to the gallery
                            if( isset( $data['image_gallery'] ) ) {
                                $files = array();
                                foreach( $data['image_gallery']['files'] as $v ) {
                                    $files[] = $v['attachment'];
                                }
                                $files = implode( ',', $files );

                                // @since 1.2.0 - use native function for saving gallery images
                                $product->set_gallery_image_ids( $files );
                            }

                            // Sales and prices
                            if ( in_array( $product_type, array( 'variable', 'grouped' ) ) ) {
                                // Variable and grouped products have no prices
                                $product->set_regular_price('');
                                $product->set_sale_price('');
                                $product->set_date_on_sale_from('');
                                $product->set_date_on_sale_to('');
                                $product->set_price('');
                            }else{
                                // Regular Price
                                if ( isset( $data['product_regular_price'] ) ) {
                                    $product->set_regular_price($data['product_regular_price']['value']);
                                }
                                $regular_price = $product->get_regular_price( 'edit' );

                                // Sale Price
                                if ( isset( $data['product_sale_price'] ) ) {
                                    $product->set_sale_price($data['product_sale_price']['value']);
                                } else {
                                    $sale_price = $product->get_sale_price( 'edit' );
                                }
                                $date_from = $product->get_date_on_sale_from( 'edit' );
                                $date_to = $product->get_date_on_sale_to( 'edit' );

                                
                                if ( $date_to && ! $date_from ) {
                                    $date_from = strtotime( 'NOW', current_time( 'timestamp' ) );
                                    $product->set_date_on_sale_from($date_from);
                                }

                                // Update price if on sale
                                if ( '' !== $sale_price && '' == $date_to && '' == $date_from ) {
                                    $product->set_price($sale_price);
                                } else {
                                    $product->set_price($regular_price);
                                }
                                if ( '' !== $sale_price && $date_from && $date_from <= strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
                                    $product->set_price($sale_price);
                                }
                                if ( $date_to && $date_to < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
                                    $product->set_price($regular_price);
                                    $product->set_date_on_sale_from('');
                                    $product->set_date_on_sale_to('');
                                }
                            }
                            $product->save();
                        }

                        // Save custom post meta
                        $meta_data = array();
                        $custom_meta = explode( "\n", $settings['frontend_posting_meta'] );
                        foreach( $custom_meta as $k ) {
                            if(empty($k)) continue;
                            $field = explode( "|", $k );
                            // @since 1.0.3 - first check if a field with the name exists
                            if( isset( $data[$field[0]]['value'] ) ) {
                                $meta_data[$field[1]] = $data[$field[0]]['value'];
                            }else{
                                
                                // @since 1.1.2 - check if type is files
                                if( (!empty($data[$field[0]])) && ( ($data[$field[0]]['type']=='files') && (isset($data[$field[0]]['files'])) ) ) {
                                    if( count($data[$field[0]]['files']>1) ) {
                                        foreach( $data[$field[0]]['files'] as $fk => $fv ) {
                                            if($meta_data[$field[1]]==''){
                                                $meta_data[$field[1]] = $fv['attachment'];
                                            }else{
                                                $meta_data[$field[1]] .= ',' . $fv['attachment'];
                                            }
                                        }
                                    }elseif( count($data[$field[0]]['files'])==1) {
                                        $meta_data[$field[1]] = absint($data[$field[0]]['files'][0]['attachment']);
                                    }else{
                                        $meta_data[$field[1]] = '';
                                    }
                                    continue;
                                }else{
                                    // @since 1.0.3 - if no field exists, just save it as a string
                                    $string = SUPER_Common::email_tags( $field[0], $data, $settings );
                                    
                                    // @since 1.0.3 - check if string is serialized array
                                    $unserialize = @unserialize($string);
                                    if ($unserialize !== false) {
                                        $meta_data[$field[1]] = $unserialize;
                                    }else{
                                        $meta_data[$field[1]] = $string;
                                    }
                                }
                            }
                        }

                        foreach( $meta_data as $k => $v ) {
                            // @since 1.1.1 - Check for ACF field and check if checkbox, if checkbox save values as Associative Array
                            if (function_exists('get_field_object')) {
                                global $wpdb;
                                $length = strlen($k);

                                // @since 1.1.2 - Because there are major differences between ACF Pro and the regular ACF plugin we have to do different queries
                                if( class_exists('acf_pro') ) {
                                    $sql = "SELECT post_name FROM {$wpdb->posts} WHERE post_excerpt = '$k' AND post_type = 'acf-field'";
                                }else{
                                    $sql = "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'field_%' AND meta_value LIKE '%\"name\";s:$length:\"$k\";%';";
                                }
                                $acf_field = $wpdb->get_var($sql);

                                // @since 1.2.4 - It might be possible we are using the ACF Gallery Add-on here for the none pro version, in this case we must do a different query
                                if(!$acf_field){
                                    $sql = "SELECT post_name FROM {$wpdb->posts} WHERE post_excerpt = '$k' AND post_type = 'acf-field'";
                                    $acf_field = $wpdb->get_var($sql);
                                }

                                $acf_field = get_field_object($acf_field);

                                // @since 1.1.3 - save a checkbox or select value
                                if( ($acf_field['type']=='checkbox') || ($acf_field['type']=='select') || ($acf_field['type']=='radio') || ($acf_field['type']=='gallery') ) {
                                    $value = explode( ",", $v );
                                    update_field( $acf_field['key'], $value, $post_id );
                                    continue;
                                }elseif( $acf_field['type']=='google_map' ) {
                                    if( isset($data[$k]['geometry']) ) {
                                        $data[$k]['geometry']['location']['address'] = $data[$k]['value'];
                                        $value = $data[$k]['geometry']['location'];
                                    }else{
                                        $value = array(
                                            'address' => $data[$k]['value'],
                                            'lat' => '',
                                            'lng' => '',
                                        );
                                    }
                                    update_field( $acf_field['key'], $value, $post_id );
                                    continue;
                                }

                                // @since 1.1.3 - save a repeater field value
                                if($acf_field['type']=='repeater'){
                                    $repeater_values = array();
                                    foreach($acf_field['sub_fields'] as $sk => $sv){
                                        if( isset($data[$sv['name']]) ) {
                                            $repeater_values[0][$sv['name']] = $this->return_field_value( $data, $sv['name'], $sv['type'], $settings );
                                            $field_counter = 2;
                                            while( isset($data[$sv['name'] . '_' . $field_counter]) ) {
                                                $repeater_values[$field_counter-1][$sv['name']] = $this->return_field_value( $data, $sv['name'] . '_' . $field_counter, $sv['type'], $settings );
                                                $field_counter++;
                                            }
                                        }
                                    }
                                    update_field( $acf_field['key'], $repeater_values, $post_id );
                                    continue;
                                }

                                // save a basic text value
                                update_field( $acf_field['key'], $v, $post_id );
                                continue;

                            }
                            add_post_meta( $post_id, $k, $v );
                        }

                        // Set post format for the post if theme supports it and if it was set by the form settings or by one of the form fields
                        if ( current_theme_supports( 'post-formats' ) ) {
                            $post_formats = get_theme_support( 'post-formats' );
                            if ( is_array( $post_formats[0] ) ) {
                                if ( in_array( $post_format, $post_formats[0] ) ) {
                                    set_post_format( $post_id , $post_format);
                                }
                            }
                        }

                        // Set the featured image if a file upload field with the name featured_image was found
                        if( !empty( $data['featured_image'] ) ) {
                            if( !empty( $data['featured_image']['files'] ) ) {
                                set_post_thumbnail( $post_id, $data['featured_image']['files'][0]['attachment'] );
                            }
                        }
                    }

                    // Store the created post ID into a session, to either alter the redirect URL or for developers to use in their custom code
                    // The redirect URL will only be altered if the option to do so was enabled in the form settings.
                    SUPER_Forms()->session->set( 'super_forms_frontend_posting_created_post', $post_id );

                    // @since 1.0.1
                    do_action( 'super_front_end_posting_after_insert_post_action', array( 'post_id'=>$post_id, 'data'=>$data, 'atts'=>$atts ) );


                }
            }
        }


        /**
         * Return field value for saving into post meta
         *
         *  @since      1.1.3
        */
        public static function return_field_value( $data, $name, $type, $settings ) {
            $value = '';
            $type = $type;           
            if( ($data[$name]['type']=='files') && (isset($data[$name]['files'])) ) {
                if( count($data[$name]['files']>1) ) {
                    foreach( $data[$name]['files'] as $fk => $fv ) {
                        if($value==''){
                            $value = $fv['attachment'];
                        }else{
                            $value .= ',' . $fv['attachment'];
                        }
                    }
                }elseif( count($data[$name]['files'])==1) {
                    $value = absint($data[$name]['files'][0]['attachment']);
                }else{
                    $value = '';
                }
            }elseif( ($type=='checkbox') || ($type=='select') || ($type=='radio') || ($type=='gallery') ) {
                $value = explode( ",", $data[$name]['value'] );
            }elseif( $type=='google_map' ) {
                if( isset($data[$name]['geometry']) ) {
                    $data[$name]['geometry']['location']['address'] = $data[$name]['value'];
                    $value = $data[$name]['geometry']['location'];
                }else{
                    $value = array(
                        'address' => $data[$name]['value'],
                        'lat' => '',
                        'lng' => '',
                    );
                }
            }else{
                $value = $data[$name]['value'];
            }
            return $value;
        }


        /**
         * Hook into settings and add Front-end Posting settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $settings ) {
            $array['frontend_posting'] = array(        
                'name' => __( 'Front-end Posting', 'super-forms' ),
                'label' => __( 'Front-end Posting Settings', 'super-forms' ),
                'fields' => array(
                    'frontend_posting_action' => array(
                        'name' => __( 'Actions', 'super-forms' ),
                        'desc' => __( 'Select what this form should do (register or login)?', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_action', $settings['settings'], 'none' ),
                        'filter' => true,
                        'type' => 'select',
                        'values' => array(
                            'none' => __( 'None (do nothing)', 'super-forms' ),
                            'create_post' => __( 'Create new Post', 'super-forms' ), //(post, page, product etc.)
                        ),
                    ),

                    // @since 1.2.2 - option to redirect to the newly created post
                    'frontend_posting_redirect' => array(
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_redirect', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => __( 'Redirect to the created Post after form submission', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),

                    'frontend_posting_post_type' => array(
                        'name' => __( 'Post type', 'super-forms' ),
                        'desc' => __( 'Enter the name of the post type (e.g: post, page, product)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_post_type', $settings['settings'], 'page' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_status' => array(
                        'name' => __( 'Status', 'super-forms' ),
                        'desc' => __( 'Select what the status should be (publish, future, draft, pending, private, trash, auto-draft)?', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_status', $settings['settings'], 'publish' ),
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
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_post_parent' => array(
                        'name' => __( 'Parent ID (leave blank for none)', 'super-forms' ),
                        'desc' => __( 'Enter a parent ID if you want the post to have a parent', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_post_parent', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_comment_status' => array(
                        'name' => __( 'Allow comments', 'super-forms' ),
                        'desc' => __( 'Whether the post can accept comments', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_comment_status', $settings['settings'], '' ),
                        'type' => 'select',
                        'values' => array(
                            '' => __( 'Default (use the default_comment_status option)', 'super-forms' ),
                            'open' => __( 'Open (allow comments)', 'super-forms' ),
                            'closed' => __( 'Closed (disallow comments)', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_ping_status' => array(
                        'name' => __( 'Allow pings', 'super-forms' ),
                        'desc' => __( 'Whether the post can accept pings', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_ping_status', $settings['settings'], '' ),
                        'type' => 'select',
                        'values' => array(
                            '' => __( 'Default (use the default_ping_status option)', 'super-forms' ),
                            'open' => __( 'Open (allow pings)', 'super-forms' ),
                            'closed' => __( 'Closed (disallow pings)', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_post_password' => array(
                        'name' => __( 'Password protect (leave blank for none)', 'super-forms' ),
                        'desc' => __( 'The password to access the post', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_post_password', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_menu_order' => array(
                        'name' => __( 'Menu order (blank = 0)', 'super-forms' ),
                        'desc' => __( 'The order the post should be displayed in', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_menu_order', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_meta' => array(
                        'name' => __( 'Save custom post meta', 'super-forms' ),
                        'desc' => __( 'Based on your form fields you can save custom meta for your post', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_meta', $settings['settings'], "field_name|meta_key\nfield_name2|meta_key2\nfield_name3|meta_key3" ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                        'allow_empty' => true,
                    ),
                    'frontend_posting_author' => array(
                        'name' => __( 'Author ID (default = current user ID if logged in)', 'super-forms' ),
                        'desc' => __( 'The ID of the user where the post will belong to', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_author', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_post_cat_taxonomy' => array(
                        'name' => __( 'The cat taxonomy name (e.g: category or product_cat)', 'super-forms' ),
                        'desc' => __( 'Required to connect the post to categories (if found)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_post_cat_taxonomy', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_tax_input' => array(
                        'name' => __( 'The post categories slug(s) (e.g: books, cars)', 'super-forms' ),
                        'desc' => __( 'Category slug separated by comma', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_tax_input', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_tags_input' => array(
                        'name' => __( 'The post tags', 'super-forms' ),
                        'desc' => __( 'Post tags separated by comma', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_tags_input', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_post_tag_taxonomy' => array(
                        'name' => __( 'The tag taxonomy name (e.g: post_tag or product_tag)', 'super-forms' ),
                        'desc' => __( 'Required to connect the post to categories (if found)', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_post_tag_taxonomy', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_post_format' => array(
                        'name' => __( 'The post format (e.g: quote, gallery, audio etc.)', 'super-forms' ),
                        'desc' => __( 'Leave blank for no post format', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_post_format', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_guid' => array(
                        'name' => __( 'GUID', 'super-forms' ),
                        'desc' => __( 'Global Unique ID for referencing the post', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_guid', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_action',
                        'filter_value' => 'create_post',
                    ),
                    'frontend_posting_product_type' => array(
                        'name' => __( 'Product Type (e.g: simple, grouped, external, variable)', 'super-forms' ),
                        'desc' => __( 'Leave blank to use the default product type: simple', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_type', $settings['settings'], '' ),
                        'filter' => true,
                        'parent' => 'frontend_posting_post_type',
                        'filter_value' => 'product',
                    ),
                    'frontend_posting_product_attributes' => array(
                        'name' => __( 'Save product attributes', 'super-forms' ),
                        'label' => __( "Enter the attributes that needs to be saved for this product<br />Put each attribute category on a new line separated by pipes \"|\".<br /><strong>Define your values like so:</strong> Attribute Slug|Attribute value|Visible|Variation|Taxonomys<br /><strong>Example with tags:</strong> colors|{color}|1|1|1<br /><strong>Example without tags:</strong> colors|red,green,yellow|1|1|1<br /><strong>Allowed values:</strong> string|string|integer|integer|integer", 'super-forms' ),
                        'desc' => __( 'Based on your form fields you can save product attributes', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_attributes', $settings['settings'], "color|{color}|1|1|1" ),
                        'filter' => true,
                        'parent' => 'frontend_posting_post_type',
                        'filter_value' => 'product',
                        'allow_empty' => true,
                    ),
                    'frontend_posting_product_featured' => array(
                        'name' => __( 'Featured product', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_featured', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super-forms' ),
                            'yes' => __( 'Yes', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_post_type',
                        'filter_value' => 'product',
                    ),
                    'frontend_posting_product_stock_status' => array(
                        'name' => __( 'In stock?', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_stock_status', $settings['settings'], 'instock' ),
                        'type' => 'select',
                        'values' => array(
                            'instock' => __( 'In stock (default)', 'super-forms' ),
                            'outofstock' => __( 'Out of stock', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_post_type',
                        'filter_value' => 'product',
                    ),
                    'frontend_posting_product_manage_stock' => array(
                        'name' => __( 'Manage stock?', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_manage_stock', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super-forms' ),
                            'yes' => __( 'Yes', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_post_type',
                        'filter_value' => 'product',
                    ),
                    'frontend_posting_product_stock' => array(
                        'name' => __( 'Stock Qty', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_stock', $settings['settings'], '' ),
                        'type' => 'slider',
                        'min' => 0,
                        'max' => 100,
                        'steps' => 1,
                        'filter' => true,
                        'parent' => 'frontend_posting_product_manage_stock',
                        'filter_value' => 'yes',
                    ),
                    'frontend_posting_product_backorders' => array(
                        'name' => __( 'Allow Backorders?', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_backorders', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'Do not allow (default)', 'super-forms' ),
                            'notify' => __( 'Allow, but notify customer', 'super-forms' ),
                            'yes' => __( 'Allow', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_product_manage_stock',
                        'filter_value' => 'yes',
                    ),
                    'frontend_posting_product_sold_individually' => array(
                        'name' => __( 'Sold individually?', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_sold_individually', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super-forms' ),
                            'yes' => __( 'Yes', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_post_type',
                        'filter_value' => 'product',
                    ),
                    'frontend_posting_product_downloadable' => array(
                        'name' => __( 'Downloadable product', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_downloadable', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super-forms' ),
                            'yes' => __( 'Yes', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_post_type',
                        'filter_value' => 'product',
                    ),
                    'frontend_posting_product_virtual' => array(
                        'name' => __( 'Virtual product', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_virtual', $settings['settings'], 'no' ),
                        'type' => 'select',
                        'values' => array(
                            'no' => __( 'No (default)', 'super-forms' ),
                            'yes' => __( 'Yes', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_post_type',
                        'filter_value' => 'product',
                    ),
                    'frontend_posting_product_visibility' => array(
                        'name' => __( 'Product visibility', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'frontend_posting_product_visibility', $settings['settings'], 'visible' ),
                        'type' => 'select',
                        'values' => array(
                            'visible' => __( 'Catalog & search (default)', 'super-forms' ),
                            'catalog' => __( 'Catalog', 'super-forms' ),
                            'search' => __( 'Search', 'super-forms' ),
                            'hidden' => __( 'Hidden', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'frontend_posting_post_type',
                        'filter_value' => 'product',
                    ),

                )
            );
            return $array;
        }



    }
        
endif;


/**
 * Returns the main instance of SUPER_Frontend_Posting to prevent the need to use globals.
 *
 * @return SUPER_Frontend_Posting
 */
function SUPER_Frontend_Posting() {
    return SUPER_Frontend_Posting::instance();
}


// Global for backwards compatibility.
$GLOBALS['SUPER_Frontend_Posting'] = SUPER_Frontend_Posting();