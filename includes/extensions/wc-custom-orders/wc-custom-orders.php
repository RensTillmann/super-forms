<?php
// @TODO
// Option to add fees / discounts
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// if(!class_exists('SUPER_WC_Custom_Orders')) :
// 
// 
//     /**
//      * Main SUPER_WC_Custom_Orders Class
//      *
//      * @class SUPER_WC_Custom_Orders
//      */
//     final class SUPER_WC_Custom_Orders {
//     
//         
//         /**
//          * @var string
//          *
//         */
//         public $add_on_slug = 'wc-custom-orders';
//         public $add_on_name = 'WooCommerce Custom Orders';
// 
// 
//         /**
//          * @var SUPER_WC_Custom_Orders The single instance of the class
//          *
//         */
//         protected static $_instance = null;
// 
//         
//         /**
//          * Main SUPER_WC_Custom_Orders Instance
//          *
//          * Ensures only one instance of SUPER_WC_Custom_Orders is loaded or can be loaded.
//          *
//          * @static
//          * @see SUPER_WC_Custom_Orders()
//          * @return SUPER_WC_Custom_Orders - Main instance
//          *
//         */
//         public static function instance() {
//             if(is_null( self::$_instance)){
//                 self::$_instance = new self();
//             }
//             return self::$_instance;
//         }
// 
//         
//         /**
//          * SUPER_WC_Custom_Orders Constructor.
//          *
//         */
//         public function __construct(){
//             $this->init_hooks();
//             do_action('SUPER_WC_Custom_Orders_loaded');
//         }
// 
//         
//         /**
//          * Define constant if not already set
//          *
//          * @param  string $name
//          * @param  string|bool $value
//          *
//         */
//         private function define($name, $value){
//             if(!defined($name)){
//                 define($name, $value);
//             }
//         }
// 
//         
//         /**
//          * What type of request is this?
//          *
//          * string $type ajax, frontend or admin
//          * @return bool
//          *
//         */
//         private function is_request($type){
//             switch ($type){
//                 case 'admin' :
//                     return is_admin();
//                 case 'ajax' :
//                     return defined( 'DOING_AJAX' );
//                 case 'cron' :
//                     return defined( 'DOING_CRON' );
//                 case 'frontend' :
//                     return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
//             }
//         }
// 
//         
//         /**
//          * Hook into actions and filters
//          *
//         */
//         private function init_hooks() {
//             
//             add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
//             
//             // Filters since 1.0.0
//             add_filter( 'super_redirect_url_filter', array( $this, 'redirect_to_order' ), 10, 2 );
//             
//             if ( $this->is_request( 'admin' ) ) {
//                 
//                 add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
//                 
//             }
//             
//             if ( $this->is_request( 'ajax' ) ) {
//                 add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ) );
//             }
//         }
// 
// 
//         /**
//          * Load Localisation files.
//          * Note: the first-loaded translation file overrides any following ones if the same translation is present.
//          */
//         public function load_plugin_textdomain() {
//             $locale = apply_filters( 'plugin_locale', get_locale(), 'super-forms' );
// 
//             load_textdomain( 'super-forms', WP_LANG_DIR . '/super-forms-' . $this->add_on_slug . '/super-forms-' . $this->add_on_slug . '-' . $locale . '.mo' );
//             load_plugin_textdomain( 'super-forms', false, plugin_basename( dirname( __FILE__ ) ) . '/i18n/languages' );
//         }
// 
// 
//         /**
//          * Redirect to newly created order
//          * 
//         */
//         public function redirect_to_order( $url, $attr ) {
//             // Only check for URL in the session if setting was enabled
//             // Check if option to redirect to created order is enabled in form settings
//             if( (isset($attr['settings']['wc_custom_orders_redirect'])) && ($attr['settings']['wc_custom_orders_redirect']==='order') ) {
//                 // If setting was enabled, let's check if we can find the Order ID in the stored session
//                 $order_id = SUPER_Common::getClientData( 'wc_custom_orders_created_order' );
//                 $url = get_edit_post_link( $order_id, '' );
//                 // Make sure to reset the session to clear it from the database, and so that we won't have a redirect conflict with other possible forms
//                 SUPER_Common::setClientData( array( 'name'=> 'wc_custom_orders_created_order', 'value'=>false  ) );
//             }
//             return $url;
//         }
// 
// 
//         /**
//          * Loop through {tags} if dynamic column is used
//          *
//         */
//         public static function new_wc_checkout_products( $products_tags, $i, $looped, $product, $product_id, $quantity, $name, $variation_id, $subtotal, $total, $tax_class, $variation ){
//             if(!in_array($i, $looped)){
//                 $new_line = '';
// 
//                 $index = 0;
//                 if(isset($product_id)){
//                     // Get the product ID tag
//                     if( $product[$index][0]=='{' ) { 
//                         $new_line .= '{' . $product_id . '_' . $i . '}'; 
//                     }else{ 
//                         if(!empty($product[$index])) {
//                             $new_line .= '|' . $product[$index];
//                         }else{
//                             $new_line .= '|0';
//                         }
//                     }
//                 }
//                 $index++;
//                 if(isset($quantity)){
//                     // Get the product quantity tag
//                     if( $product[$index][0]=='{' ) { 
//                         $new_line .= '|{' . $quantity . '_' . $i . '}'; 
//                     }else{ 
//                         if(!empty($product[$index])) {
//                             $new_line .= '|' . $product[$index];
//                         }else{
//                             $new_line .= '|0';
//                         }
//                     }
//                 }
//                 $index++;
//                 if(isset($name)){
//                     // Get the product name tag
//                     if( $product[$index][0]=='{' ) { 
//                         $new_line .= '|{' . $name . '_' . $i . '}'; 
//                     }else{ 
//                         if(!empty($product[$index])) {
//                             $new_line .= '|' . $product[$index];
//                         }else{
//                             $new_line .= '|';
//                         }
//                     }
//                 }
//                 $index++;
//                 if(isset($variation_id)){
//                     // Get the product variation ID tag
//                     if( $product[$index][0]=='{' ) { 
//                         $new_line .= '|{' . $variation_id . '_' . $i . '}'; 
//                     }else{ 
//                         if(!empty($product[$index])) {
//                             $new_line .= '|' . $product[$index];
//                         }else{
//                             $new_line .= '|0';
//                         }
//                     }
//                 }
//                 $index++;
//                 if(isset($subtotal)){
//                     // Get the product price tag
//                     if( $product[$index][0]=='{' ) { 
//                         $new_line .= '|{' . $subtotal . '_' . $i . '}'; 
//                     }else{ 
//                         if(!empty($product[$index])) {
//                             $new_line .= '|' . $product[$index];
//                         }else{
//                             $new_line .= '|0';
//                         }
//                     }
//                 }
//                 $index++;
//                 if(isset($total)){
//                     // Get the product total tag
//                     if( $product[$index][0]=='{' ) { 
//                         $new_line .= '|{' . $total . '_' . $i . '}'; 
//                     }else{
//                         if(!empty($product[$index])) {
//                             $new_line .= '|' . $product[$index];
//                         }else{
//                             $new_line .= '|0';
//                         }
//                     }
//                 }
//                 $index++;
//                 if(isset($tax_class)){
//                     // Get the product tax_class tag
//                     if( $product[$index][0]=='{' ) { 
//                         $new_line .= '|{' . $tax_class . '_' . $i . '}'; 
//                     }else{ 
//                         if(!empty($product[$index])) {
//                             $new_line .= '|' . $product[$index];
//                         }else{
//                             $new_line .= '|';
//                         }
//                     }
//                 }
//                 $index++;
//                 if(isset($variation)){
//                     // Get the product tax_class tag
//                     if( $product[$index][0]=='{' ) { 
//                         $new_line .= '|{' . $variation . '_' . $i . '}'; 
//                     }else{ 
//                         if(!empty($product[$index])) {
//                             $new_line .= '|' . $product[$index];
//                         }else{
//                             $new_line .= '|';
//                         }
//                     }
//                 }
// 
//                 $products_tags[] = $new_line;
//                 $looped[$i] = $i;
//                 $i++;
//                 return array(
//                     'i'=>$i, 
//                     'looped'=>$looped, 
//                     'products_tags'=>$products_tags 
//                 );
//             }else{
//                 return false;
//             }
//         }
// 
// 
//         /**
//          * Hook into before sending email and check if we need to create or update an order
//          *
//         */
//         public static function before_email_success_msg( $atts ) {
//             $settings = $atts['settings'];
//             if( isset( $atts['data'] ) ) {
//                 $data = $atts['data'];
//             }else{
//                 if( $settings['save_contact_entry']=='yes' ) {
//                     $data = get_post_meta( $atts['entry_id'], '_super_contact_entry_data', true );
//                 }else{
//                     $data = $atts['post']['data'];
//                 }
//             }
// 
//             if( !isset( $settings['wc_custom_orders_action'] ) ) return true;
//             if( $settings['wc_custom_orders_action']=='none' ) return true;
// 
//             // Create WooCommerce Order
//             if( $settings['wc_custom_orders_action']=='create_order' ) {
// 
//                 // Check if we are updating an existing order
//                 $update = false;
//                 if(!empty($settings['wc_custom_orders_id'])){
//                     $order_id = SUPER_Common::email_tags( $settings['wc_custom_orders_id'], $data, $settings );
//                     if(absint($order_id)!=0){
//                         $update = true;
//                     }
//                 }
// 
//                 // Gather all product information, and replace any tags with values
//                 // After that combine both products and their custom meta data (if found any) together in one array
//                 // Then loop through the products array and add it to the order along with possible meta data
//                 //$products = explode("\n",$settings['wc_custom_orders_products']);
//                 //$products_meta = explode("\n",$settings['wc_custom_orders_products_meta']);
//                 $products = explode( "\n", $settings['wc_custom_orders_products'] );  
//                 $products_tags = $products;
//                 foreach( $products as $k => $v ) {
//                     $product =  explode( "|", $v );
//                     // {product_id}|{quantity}|{name}|{variation_id}|{subtotal}|{total}|{tax_class}|{variation}
//                     if( isset( $product[0] ) ) $product_id = trim($product[0], '{}');
//                     if( isset( $product[1] ) ) $quantity = trim($product[1], '{}');
//                     if( isset( $product[2] ) ) $name = trim($product[2], '{}');
//                     if( isset( $product[3] ) ) $variation_id = trim($product[3], '{}');
//                     if( isset( $product[4] ) ) $subtotal = trim($product[4], '{}');
//                     if( isset( $product[5] ) ) $total = trim($product[5], '{}');
//                     if( isset( $product[6] ) ) $tax_class = trim($product[6], '{}');
//                     if( isset( $product[7] ) ) $variation = trim($product[7], '{}');
//                     $looped = array();
//                     $i=2;
//                     while( isset( $data[$product_id . '_' . ($i)]) ) {
//                         $array = self::new_wc_checkout_products( $products_tags, $i, $looped, $product, $product_id, $quantity, $name, $variation_id, $subtotal, $total, $tax_class, $variation );
//                         if($array==false) break;
//                         $i = $array['i'];
//                         $looped = $array['looped'];
//                         $products_tags = $array['products_tags'];
//                     }
//                 }
// 
//                 $products_meta = explode( "\n", $settings['wc_custom_orders_products_meta'] );  
//                 $values = array();
//                 $meta = array();
//                 $regex = "/{(.*?)}/";
//                 foreach( $products_meta as $wck => $v ) {
//                     $product =  explode( "|", $v );
// 
//                     // Skip if not enough values where found, we must have ID|Label|Value (a total of 3 values)
//                     if( count($product) < 3 ) {
//                         continue;
//                     }
// 
//                     $found = false; // In case we found this tag in the submitted data
// 
//                     // Check if Product ID was set via a {tag} e.g: {tshirt_id}
//                     if( isset( $product[0] ) ) {
//                         $values[0]['value'] = $product[0];
//                         $match = preg_match_all($regex, $product[0], $matches, PREG_SET_ORDER, 0);
//                         if( $match ) {
//                             $values[0]['value'] = trim($values[0]['value'], '{}');
//                             $values[0]['match'] = true;
//                             foreach( $matches as $k => $v ) {
//                                 $key = str_replace(';label', '', $v[0]);
//                                 if( isset($data[$key]) ) {
//                                     $found = true;
//                                 }
//                             }
//                         }
//                     }
// 
//                     // Check if meta Label was set via a {tag} e.g: {tshirt_meta_label}
//                     if( isset( $product[1] ) ) {
//                         $values[1]['value'] = $product[1];
//                         $match = preg_match_all($regex, $product[1], $matches, PREG_SET_ORDER, 0);
//                         if( $match ) {
//                             $values[1]['value'] = trim($values[1]['value'], '{}');
//                             $values[1]['match'] = true;
//                             foreach( $matches as $k => $v ) {
//                                 $key = str_replace(';label', '', $v[1]);
//                                 if( isset($data[$key]) ) {
//                                     $found = true;
//                                 }
//                             }
//                         }
//                     } 
//                   
//                     // Check if meta Value was set via a {tag} e.g: {tshirt_color}
//                     if( isset( $product[2] ) ) {
//                         $values[2]['value'] = $product[2];
//                         $match = preg_match_all($regex, $product[2], $matches, PREG_SET_ORDER, 0);
//                         if( $match ) {
//                             $values[2]['value'] = trim($values[2]['value'], '{}');
//                             $values[2]['match'] = true;
//                             foreach( $matches as $k => $v ) {
//                                 $key = str_replace(';label', '', $v[2]);
//                                 if( isset($data[$key]) ) {
//                                     $found = true;
//                                 }else{
//                                     $product[2] = '';
//                                 }
//                             }
//                         }
//                     }
// 
//                     // Let's first add the current meta lin to the new array
//                     $meta[] = $product;
// 
//                     // We found a {tag} and it existed in the form data
//                     if( $found ) {
// 
//                         $i=2;
// 
//                         // Check if any of the matches exists in a dynamic column and are inside the submitted data
//                         $stop_loop = false;
//                         while( !$stop_loop ) {
//                             if( ( (isset($data[$values[0]['value'] . '_' . ($i)])) && ($values[0]['match']) ) || 
//                                 ( (isset($data[$values[1]['value'] . '_' . ($i)])) && ($values[1]['match']) ) || 
//                                 ( (isset($data[$values[2]['value'] . '_' . ($i)])) && ($values[2]['match']) ) ) {
// 
//                                 // Check if ID is {tag}
//                                 $new_line = array();
//                                 if($values[0]['match']){
//                                     $new_line[] = '{' . $values[0]['value'] . '_' . $i . '}'; 
//                                 }else{
//                                     $new_line[] = $values[0]['value']; 
//                                 }
// 
//                                 // Check if Label is {tag}
//                                 if($values[1]['match']){
//                                     // The label must be unique compared to other labels so we have to add (2) behind it
//                                     $new_line[] = '{' . $values[1]['value'] . '_' . $i . '}' . ' ('.$i.')';
//                                 }else{
//                                     // The label must be unique compared to other labels so we have to add (2) behind it
//                                     $new_line[] = $values[1]['value'] . ' ('.$i.')';
//                                 }
// 
//                                 // Check if Value is {tag}
//                                 if($values[2]['match']){
//                                     $new_line[] = '{' . $values[2]['value'] . '_' . $i . '}'; 
//                                 }else{
//                                     $new_line[] = $values[2]['value']; 
//                                 }
//                                 $meta[] = $new_line;
//                                 $i++;
//                             }else{
//                                 $stop_loop = true;
//                             }
//                         }
//                     }
//                 }
// 
//                 $final_products_meta = array();
//                 foreach( $meta as $mk => $mv ) {
//                     $product_id = 0;
//                     $meta_key = '';
//                     $meta_value = '';
//                     if( isset( $mv[0] ) ) $product_id = SUPER_Common::email_tags( $mv[0], $data, $settings );
//                     if( isset( $mv[1] ) ) $meta_key = SUPER_Common::email_tags( $mv[1], $data, $settings );
//                     if( isset( $mv[2] ) ) $meta_value = SUPER_Common::email_tags( $mv[2], $data, $settings );
//                     if(!empty($meta_value)) $final_products_meta[$product_id][$meta_key] = $meta_value;
//                 }
// 
//                 $products = array();
//                 foreach( $products_tags as $k => $v ) {
//                     $product =  explode( "|", $v );
//                     $product_id = 0;
//                     $qty = 0;
//                     $name = '';
//                     $variation_id = '';
//                     $subtotal = '';
//                     $total = '';
//                     $tax_class = '';
//                     $variation = '';
//                     if( isset( $product[0] ) ) $product_id = SUPER_Common::email_tags( $product[0], $data, $settings );     // '118'
//                     if( isset( $product[1] ) ) $qty = SUPER_Common::email_tags( $product[1], $data, $settings );            // '1'
//                     if( isset( $product[2] ) ) $name = SUPER_Common::email_tags( $product[2], $data, $settings );           // 'T-shirt custom name'
//                     if( isset( $product[3] ) ) $variation_id = SUPER_Common::email_tags( $product[3], $data, $settings );   // '0'
//                     if( isset( $product[4] ) ) $subtotal = SUPER_Common::email_tags( $product[4], $data, $settings );       // '10'
//                     if( isset( $product[5] ) ) $total = SUPER_Common::email_tags( $product[5], $data, $settings );          // '10'
//                     if( isset( $product[6] ) ) $tax_class = SUPER_Common::email_tags( $product[6], $data, $settings );      // '0'
//                     $variations_array = array();
//                     if( isset( $product[7] ) ) {
//                         $variation = SUPER_Common::email_tags( $product[7], $data, $settings );                             // color;red#size;XL
//                         $variations = explode("#", $variation);
//                         foreach($variations as $k => $v){
//                             $values = explode(";", $v);
//                             $key = (isset($values[0]) ? $values[0] : '');
//                             $value = (isset($values[1]) ? $values[1] : '');
//                             if($key!==''){
//                                 $variations_array[$key] = $values[1];
//                             }
//                         }
//                     } 
// 
//                     $qty = absint($qty);
//                     if( $qty>0 ) {
//                         $product_id = absint($product_id);
//                         $meta = array();
//                         if( isset($final_products_meta[$product_id]) ) {
//                             $meta = $final_products_meta[$product_id];
//                         }
//                         $product = wc_get_product( $product_id );
//                         // If product exists, proceed, otherwise create an Arbitrary product instead
//                         if($product){
//                             // Existing product
//                             $price = $product->get_price();
//                             $product_array = array( 
//                                 'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
//                                 'quantity'     => $qty,
//                                 'name'         => $product->get_name(),
//                                 'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
//                                 'subtotal'     => wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ),
//                                 'total'        => wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ),
//                                 'tax_class'    => $product->get_tax_class(),
//                                 'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
//                                 'meta_data'    => $meta
//                             );
//                             if($subtotal!==''){
//                                 $subtotal = SUPER_Common::tofloat($subtotal);
//                                 $product_array['subtotal'] = $subtotal;
//                                 if( empty($total) ) $total = $subtotal;
//                             }
//                             if(!empty($total)){
//                                 $total = SUPER_Common::tofloat($total);
//                                 $product_array['total'] = $total;
//                             }
//                             if( count($variations_array) > 0 ) {
//                                 $product_array['variation'] = $variations_array;
//                             }
//                             $products[] = $product_array;
//                         }else{
//                             // Arbitrary product
//                             if( empty($total) ) $total = $subtotal;
//                             $products[] = array( 
//                                 'name'         => $name,
//                                 'quantity'     => $qty,
//                                 'subtotal'     => $subtotal,
//                                 'total'        => $total,
//                                 'tax_class'    => $tax_class,
//                                 'meta_data'    => $meta
//                             );
//                         }
//                     }
//                 }
// 
//                 // Check if we can create a valid order, and if there are products to be added for this order
//                 // If not return error message to the user
//                 // foreach( $products as $args ) {
//                 //     if( (absint($args['product_id'])===0) || (absint($args['quantity'])===0) ) {
//                 //         // Return the error message to the user
//                 //         SUPER_Common::output_message(
//                 //             $error = true,
//                 //             $msg = esc_html__( 'The order couldn\'t be created because it is missing products!', 'super-forms' ),
//                 //             $redirect = null
//                 //         );
//                 //     }
//                 // }
// 
//                 // If verything is OK we will create the order
//                 // global $woocommerce;
//                 $args = array();
//                 $args['status'] = SUPER_Common::email_tags( $settings['wc_custom_orders_status'], $data, $settings );
//                 if($settings['wc_custom_orders_customer_id']!='') {
//                     $args['customer_id'] = absint(SUPER_Common::email_tags( $settings['wc_custom_orders_customer_id'], $data, $settings ));
//                 }else{
//                     $user_id = get_current_user_id();
//                     if( $user_id!=0 ) {
//                         $args['customer_id'] = absint($user_id);
//                     }
//                 }
//                 // Customer note can't be empty, because WP would throw an error `Column &#039;post_excerpt&#039; cannot be null`
//                 if( empty( $settings['wc_custom_orders_customer_notes'] ) ) {
//                     $args['customer_note'] = '';
//                 }else{
//                     $args['customer_note'] = SUPER_Common::email_tags( $settings['wc_custom_orders_customer_notes'], $data, $settings );
//                 }
//                 $args['created_via'] = 'Super Forms';
//                 
//                 if($update){
//                     // Before updating the order we must remove all of it's items
//                     $order = new WC_Order( $order_id );
//                     $items = $order->get_items();
//                     foreach ( $items as $item_id => $product ) {
//                         wc_delete_order_item( $item_id );
//                     }
//                     $args['order_id'] = $order_id;
//                     $order = wc_update_order($args);
//                     if(is_wp_error($order)){
//                         // Return the error message to the user
//                         SUPER_Common::output_message(
//                             $error = true,
//                             $msg = esc_html__('Error: Unable to create order. Please try again.', 'woocommerce'),
//                             $redirect = null
//                         );
//                     }else{
//                         $order->remove_order_items();
//                         // Delete old contact entry (we no longer need this because a brand new one will be created and used)
//                         global $wpdb;
//                         $contact_entry_id = $wpdb->get_var("
//                             SELECT post_id 
//                             FROM $wpdb->postmeta 
//                             WHERE meta_key = '_super_contact_entry_wc_order_id' 
//                             AND meta_value = '" . absint($order_id) . "'"
//                         );
//                         wp_delete_post( $contact_entry_id, true ); 
//                         do_action('woocommerce_resume_order', $order_id);
//                     }
//                 }else{
//                     $order = wc_create_order($args);
//                     if(is_wp_error($order)){
//                         // Return the error message to the user
//                         SUPER_Common::output_message(
//                             $error = true,
//                             $msg = esc_html__('Error: Unable to create order. Please try again.', 'woocommerce'),
//                             $redirect = null
//                         );
//                     }else{
//                         do_action('woocommerce_new_order', $order->id);
//                     }
//                 }
// 
//                 // Payment Method
//                 // Possible values are for example:
//                 // bacs (Direct bank transfer)
//                 // paypal (PayPal)
//                 // check (Check payments)
//                 // cos (Cash on delivery)
//                 if(!empty($settings['wc_custom_orders_payment_gateway'])){
//                     $payment_method = isset( $settings['wc_custom_orders_payment_gateway'] ) ? wc_clean( SUPER_Common::email_tags( $settings['wc_custom_orders_payment_gateway'], $data, $settings ) ) : false;
//                     $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
//                     if(isset($available_gateways[$payment_method])){
//                         $order->set_payment_method($available_gateways[$payment_method]);
//                     }else{
//                         // Delete the order
//                         wp_delete_post($order->id, true);
//                         // Return the error message to the user
//                         SUPER_Common::output_message(
//                             $error = true,
//                             $msg = esc_html__( 'Invalid payment method.', 'woocommerce' ),
//                             $redirect = null
//                         );
//                     }
//                 }
// 
//                 // Save order ID to contact entry meta data, so we can link from contact entry page to the order
//                 update_post_meta( $atts['entry_id'], '_super_contact_entry_wc_order_id', $order->get_id() );
// 
//                     // Save custom order meta
//                     $meta_data = array();
//                     $custom_meta = explode( "\n", $settings['wc_custom_orders_meta'] );
//                     foreach( $custom_meta as $k ) {
//                         if(empty($k)) continue;
//                         $field = explode( "|", $k );
//                         if( isset( $data[$field[1]]['value'] ) ) {
//                             $meta_data[$field[0]] = $data[$field[1]]['value'];
//                         }else{
//                             if( (!empty($data[$field[1]])) && ( ($data[$field[1]]['type']=='files') && (isset($data[$field[1]]['files'])) ) ) {
//                                 if( count($data[$field[1]]['files']>1) ) {
//                                     foreach( $data[$field[1]]['files'] as $fk => $fv ) {
//                                         if($meta_data[$field[0]]==''){
//                                             $meta_data[$field[0]] = (!empty($fv['attachment']) ? $fv['attachment'] : (!empty($fv['path']) ? $fv['path'] : 0));
//                                         }else{
//                                             $meta_data[$field[0]] .= ',' . (!empty($fv['attachment']) ? $fv['attachment'] : (!empty($fv['path']) ? $fv['path'] : 0));
//                                         }
//                                     }
//                                 }elseif( count($data[$field[1]]['files'])==1) {
//                                     $cur = $data[$field[1]]['files'][0];
//                                     if(!empty($cur['attachment'])){
//                                         $fValue = absint($cur['attachment']);
//                                     }else{
//                                         $fValue = (!empty($cur['path']) ? $cur['path'] : 0);
//                                     }
//                                     $meta_data[$field[0]] = $fValue;
//                                 }else{
//                                     $meta_data[$field[0]] = '';
//                                 }
//                                 continue;
//                             }else{
//                                 $string = SUPER_Common::email_tags( $field[1], $data, $settings );
//                                 $unserialize = unserialize($string);
//                                 if ($unserialize !== false) {
//                                     $meta_data[$field[0]] = $unserialize;
//                                 }else{
//                                     $meta_data[$field[0]] = $string;
//                                 }
//                             }
//                         }
//                     }
//                 foreach( $meta_data as $k => $v ) {
//                     if (function_exists('get_field_object')) {
//                         global $wpdb;
//                         $length = strlen($k);
//                         if( class_exists('acf_pro') ) {
//                             $sql = "SELECT post_name FROM {$wpdb->posts} WHERE post_excerpt = '$k' AND post_type = 'acf-field'";
//                         }else{
//                             $sql = "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'field_%' AND meta_value LIKE '%\"name\";s:$length:\"$k\";%';";
//                         }
//                         $acf_field = $wpdb->get_var($sql);
//                         if(!$acf_field){
//                             $sql = "SELECT post_name FROM {$wpdb->posts} WHERE post_excerpt = '$k' AND post_type = 'acf-field'";
//                             $acf_field = $wpdb->get_var($sql);
//                         }
//                         $acf_field = get_field_object($acf_field);
//                         if( ($acf_field['type']=='checkbox') || ($acf_field['type']=='select') || ($acf_field['type']=='radio') || ($acf_field['type']=='gallery') ) {
//                             $value = explode( ",", $v );
//                             update_field( $acf_field['key'], $value, $order->get_id() );
//                             continue;
//                         }elseif( $acf_field['type']=='google_map' ) {
//                             if( isset($data[$k]['geometry']) ) {
//                                 $data[$k]['geometry']['location']['address'] = $data[$k]['value'];
//                                 $value = $data[$k]['geometry']['location'];
//                             }else{
//                                 $value = array(
//                                     'address' => $data[$k]['value'],
//                                     'lat' => '',
//                                     'lng' => '',
//                                 );
//                             }
//                             update_field( $acf_field['key'], $value, $order->get_id() );
//                             continue;
//                         }
//                         if($acf_field['type']=='repeater'){
//                             $repeater_values = array();
//                             foreach($acf_field['sub_fields'] as $sk => $sv){
//                                 if( isset($data[$sv['name']]) ) {
//                                     $repeater_values[0][$sv['name']] = SUPER_WC_Custom_Orders()->return_field_value( $data, $sv['name'], $sv['type'], $settings );
//                                     $field_counter = 2;
//                                     while( isset($data[$sv['name'] . '_' . $field_counter]) ) {
//                                         $repeater_values[$field_counter-1][$sv['name']] = sSUPER_WC_Custom_Orders()->return_field_value( $data, $sv['name'] . '_' . $field_counter, $sv['type'], $settings );
//                                         $field_counter++;
//                                     }
//                                 }
//                             }
//                             update_field( $acf_field['key'], $repeater_values, $order->get_id() );
//                             continue;
//                         }
//                         update_field( $acf_field['key'], $v, $order->get_id() );
//                         continue;
//                     }
//                     update_post_meta( $order->get_id(), $k, $v );
//                 }
// 
//                 // Loop through possible order notes and save theme to the order
//                 $notes = explode("\n", $settings['wc_custom_orders_order_notes']);
//                 foreach($notes as $k => $v){
//                     if(!empty($v)){
//                         $row = explode("|", $v);
//                         if(!isset($row[1])) $row[1] = 'false';
//                         $is_customer_note = 1;
//                         if($row[1]=='false'){
//                             $is_customer_note = 0;
//                         }
//                         if($row[0]!=''){
//                             $order->add_order_note( $row[0], $is_customer_note, false );
//                         }
//                     }
//                 }
// 
//                 // Save billing address
//                 $address = array();
//                 $billing = explode("\n", $settings['wc_custom_orders_billing']);
//                 foreach($billing as $k => $v){
//                     $row = explode("|", $v);
//                     if(!isset($row[1])) $row[1] = '';
//                     $value = SUPER_Common::email_tags( $row[1], $data, $settings );
//                     // Set to empty if {tag} wasn't repleaced, but only if it contained a {tag}
//                     if( (strpos($row[1], '{')!==false) && (strpos($row[1], '}')!==false) ) {
//                         if($value===$row[1]) $value = '';
//                     }
//                     $address[$row[0]] = $value;
//                 }
//                 try {
//                     $object = $order->set_address( $address, 'billing' );
//                 } catch ( WC_Data_Exception $e ) {
//                     // Delete the order
//                     wp_delete_post($order->id, true);
//                     // Return the error message to the user
//                     SUPER_Common::output_message(
//                         $error = true,
//                         $msg = $e->getMessage(),
//                         $redirect = null
//                     );
//                 }
// 
//                 // Save shipping address
//                 $address = array();
//                 $shipping = explode("\n", $settings['wc_custom_orders_shipping']);
//                 foreach($shipping as $k => $v){
//                     $row = explode("|", $v);
//                     if(!isset($row[1])) $row[1] = '';
//                     $value = SUPER_Common::email_tags( $row[1], $data, $settings );
//                     // Set to empty if {tag} wasn't repleaced, but only if it contained a {tag}
//                     if( (strpos($row[1], '{')!==false) && (strpos($row[1], '}')!==false) ) {
//                         if($value===$row[1]) $value = '';
//                     }
//                     $address[$row[0]] = $value;
//                 }
//                 try {
//                     $order->set_address( $address, 'shipping' );
//                 } catch ( WC_Data_Exception $e ) {
//                     // Delete the order
//                     wp_delete_post($order->id, true);
//                     // Return the error message to the user
//                     SUPER_Common::output_message(
//                         $error = true,
//                         $msg = $e->getMessage(),
//                         $redirect = null
//                     );
//                 }
// 
//                 // Add products to the order
//                 foreach( $products as $args ) {
//                     $product = wc_get_product( $args['product_id'] );
//                     // Qty will be overridden by $arg
//                     $item_id = $order->add_product( $product, 1, $args ); // pid 8 & qty 1
//                     foreach($args['meta_data'] as $mk => $mv){
//                         // Add products meta data
//                         wc_add_order_item_meta( $item_id, $mk, $mv);
//                     }
//                 }
// 
//                 // Add the coupon code if any
//                 $coupon = SUPER_Common::email_tags( $settings['wc_custom_orders_coupon'], $data, $settings );
//                 if(!empty($coupon)){
//                     $order->apply_coupon( wc_clean( $coupon ) );
//                 }
// 
//                 // Add shipping costs
//                 // * @param string  $id          Shipping rate ID.
//                 // * @param string  $label       Shipping rate label.
//                 // * @param integer $cost        Cost.
//                 // * @param array   $taxes       Taxes applied to shipping rate.
//                 // * @param string  $method_id   Shipping method ID.
//                 // * @param int     $instance_id Shipping instance ID.
//                 // shipping_rate_id|shipping_rate_label|cost|method_id|instance_id
//                 $wc_custom_orders_shipping_costs = explode("\n", $settings['wc_custom_orders_shipping_costs']);
//                 foreach($wc_custom_orders_shipping_costs as $k => $v){
//                     $row = explode("|", $v);
//                     if((!isset($row[1])) || (!isset($row[2]))) continue;
//                     $shipping_rate_id = SUPER_Common::email_tags( $row[0], $data, $settings );
//                     $shipping_rate_label = SUPER_Common::email_tags( $row[1], $data, $settings );
//                     $cost = SUPER_Common::email_tags( $row[2], $data, $settings );
//                     $method_id = SUPER_Common::email_tags( $row[3], $data, $settings );
//                     $instance_id = SUPER_Common::email_tags( $row[4], $data, $settings );
//                     $shipping_taxes = WC_Tax::calc_shipping_tax($cost, WC_Tax::get_shipping_tax_rates());
//                     $rate = new WC_Shipping_Rate($shipping_rate_id, $shipping_rate_label, $cost, $shipping_taxes, $method_id, $instance_id);
//                     $item = new WC_Order_Item_Shipping();
//                     $item->set_props(
//                         array(
//                             'method_title' => $rate->label, 
//                             'method_id' => $rate->id, 
//                             'instance_id' => $rate->instance_id, 
//                             'total' => wc_format_decimal($rate->cost), 
//                             'taxes' => $rate->taxes, 
//                             'meta_data' => $rate->get_meta_data()
//                         )
//                     );
//                     $order->add_item($item);
//                 }
// 
//                 // Add order fee(s)
//                 $orders_fees = explode( "\n", $settings['wc_custom_orders_fees'] );  
//                 $values = array();
//                 $fees = array();
//                 $regex = "/{(.*?)}/";
//                 foreach( $orders_fees as $wck => $v ) {
//                     $fee =  explode( "|", $v );
//                     $count = count($fee)-1;
//                     // Skip if not enough values where found, we must have fee_name|fee_amount (a total of 2 values)
//                     if( count($fee) < 2 ) {
//                         continue;
//                     }
//                     $found = false; // In case we found this tag in the submitted data
//                     $match_found = false;
//                     // Check if option was set via a {tag} e.g: {fee_name}
//                     $i = 0;
//                     while( $i < $count ) {
//                         if( isset( $fee[$i] ) ) {
//                             $values[$i]['value'] = $fee[$i];
//                             $match = preg_match_all($regex, $fee[$i], $matches, PREG_SET_ORDER, 0);
//                             if( $match ) {
//                                 $match_found = true;
//                                 $values[$i]['value'] = trim($values[$i]['value'], '{}');
//                                 $values[$i]['match'] = true;
//                                 foreach( $matches as $k => $v ) {
//                                     $key = str_replace(';label', '', $v[$i]);
//                                     if( isset($data[$key]) ) {
//                                         $found = true;
//                                     }
//                                 }
//                             }
//                         }
//                         $i++;
//                     }
// 
//                     if($match_found && $found){
//                         // Let's first add the current meta line to the new array
//                         $fees[] = $fee;
//                     }else{
//                         if($match_found && !$found){
//                             continue;
//                         }
//                     }
//                     // We found a {tag} and it existed in the form data
//                     if( $found ) {
// 
//                         $i=2;
//                         // Check if any of the matches exists in a dynamic column and are inside the submitted data
//                         $stop_loop = false;
//                         while( !$stop_loop ) {
//                             if( ( (isset($data[$values[0]['value'] . '_' . ($i)])) && ($values[0]['match']) ) || 
//                                 ( (isset($data[$values[1]['value'] . '_' . ($i)])) && ($values[1]['match']) ) || 
//                                 ( (isset($data[$values[2]['value'] . '_' . ($i)])) && ($values[2]['match']) ) || 
//                                 ( (isset($data[$values[3]['value'] . '_' . ($i)])) && ($values[3]['match']) ) ) {
//                                 // Check if fee name is {tag}
//                                 $new_line = array();
// 
//                                 $ii = 0;
//                                 while( $ii < $count ) {
//                                     if($values[$ii]['match']){
//                                         $new_line[] = '{' . $values[$ii]['value'] . '_' . $i . '}'; 
//                                     }else{
//                                         $new_line[] = $values[$ii]['value']; 
//                                     }
//                                     $ii++;
//                                 }
//                                 $fees[] = $new_line;
//                                 $i++;
//                             }else{
//                                 $stop_loop = true;
//                             }
//                         }
//                     }
//                 }
// 
//                 foreach( $fees as $fk => $fv ) {
//                     if(!isset($fv[1])) continue;
//                     $fee_name = 'Fee';
//                     $fee_amount = 0;
//                     $fee_tax_class = '';
//                     $fee_tax_status = '';
//                     if( isset( $fv[0] ) ) $fee_name = SUPER_Common::email_tags( $fv[0], $data, $settings );
//                     if( isset( $fv[1] ) ) $fee_amount = wc_format_decimal(SUPER_Common::email_tags( $fv[1], $data, $settings ));
//                     if( isset( $fv[2] ) ) $fee_tax_class = SUPER_Common::email_tags( $fv[2], $data, $settings );
//                     if( isset( $fv[3] ) ) $fee_tax_status = SUPER_Common::email_tags( $fv[3], $data, $settings );
//                     if(!empty($fee_name) && !empty($fee_amount)){
//                         $fee = new WC_Order_Item_Fee();
//                         $fee->set_props( array(
//                             'name' => $fee_name,
//                             'total' => $fee_amount, // @param string $amount (Fee amount) (do not enter negative amounts).
//                             'tax_class' => $fee_tax_class, // Valid tax_classes are inside WC_Tax::get_tax_class_slugs()
//                             'tax_status' => $fee_tax_status, // @param string $value (Set tax_status) `taxable` OR `none`
//                             'order_id'  => $order->get_id()
//                             //'amount' => $amount, // @param string $value (Set fee amount) deprecated?
//                             //'total_tax' => $fee->tax, // @param string $amount (Set total tax)
//                             //'taxes'     => array(
//                              //   'total' => $fee->tax_data, // @param array $raw_tax_data (Set taxes) This is an array of tax ID keys with total amount values.
//                             //),
//                         ) );
//                         $fee->save();
//                         $order->add_item($fee);
//                     }
//                 }
// 
//                 // Make sure to calculate order totals
//                 $order->calculate_totals();
// 
//                 // Redirect the user accordingly
//                 if(!isset($settings['wc_custom_orders_redirect'])) $settings['wc_custom_orders_redirect'] = 'gateway';
//                 $redirect_to = $settings['wc_custom_orders_redirect'];
//                 if($redirect_to!=='none'){
//                     // If redirecting to payment gateway
//                     if($redirect_to=='gateway'){
//                         // Redirect to Payment gateway if order needs payment
//                         // Update payment method
//                         if ( $order->needs_payment() ) {
//                             // Let the payment method validate fields
//                             if( (isset($available_gateways)) && ($available_gateways[$payment_method]) ) {
//                                 $available_gateways[$payment_method]->validate_fields();
//                                 // If validation was successful, continue
//                                 if(wc_notice_count('error')===0){
//                                     $result = $available_gateways[$payment_method]->process_payment($order->id);
//                                     // Redirect to success/confirmation/payment page
//                                     if($result['result']==='success'){
//                                         SUPER_Common::output_message(
//                                             $error = false,
//                                             $msg = '',
//                                             $redirect = $result['redirect']
//                                         );
//                                         exit;
//                                     }
//                                 }
//                             }
//                         }
//                     }
//                     // If redirecting to "Pay for order page"
//                     if($redirect_to=='pay_for_order'){
//                         SUPER_Common::output_message(
//                             $error = false,
//                             $msg = '',
//                             $redirect = $order->get_checkout_payment_url()
//                         );
//                         exit;
//                     }
//                     // If redirecting to "Order received page"
//                     if($redirect_to=='order_received_page'){
//                         // Set to payment completed if order does not need a payment
//                         if(!$order->needs_payment()){
//                             $order->payment_complete();
//                         }
//                         SUPER_Common::output_message(
//                             $error = false,
//                             $msg = '',
//                             $redirect = $order->get_checkout_order_received_url()
//                         );
//                         exit;
//                     }
//                 }
//             }
// 
//             // Create WooCommerce Subscription
//             if( $settings['wc_custom_orders_action']=='create_subscription' ) {
//                 global $woocommerce;
//                 $email = 'test@test.com';
//                 $start_date = '2015-01-01 00:00:00';
//                 $address = array(
//                     'first_name' => 'Jeremy',
//                     'last_name'  => 'Test',
//                     'company'    => '',
//                     'email'      => $email,
//                     'phone'      => '777-777-777-777',
//                     'address_1'  => '31 Main Street',
//                     'address_2'  => '', 
//                     'city'       => 'Auckland',
//                     'state'      => 'AKL',
//                     'postcode'   => '12345',
//                     'country'    => 'AU'
//                 );
//                 $default_password = wp_generate_password();
//                 
//                 // If user is not logged in or doesn't exist, create a new user with a random password and with the filled out email address
//                 if (!$user = get_user_by('login', $email)) {
//                     $user = wp_create_user( $email, $default_password, $email );
//                 }
// 
//                 // I've used one product with multiple variations
//                 $parent_product = wc_get_product(22998);
//                 $args = array(
//                     'attribute_billing-period' => 'Yearly',
//                     'attribute_subscription-type' => 'Both'
//                 );
//                 $product_variation = $parent_product->get_matching_variation($args);
//                 $product = wc_get_product($product_variation);  
//                 
//                 // Each variation also has its own shipping class
//                 $shipping_class = get_term_by('slug', $product->get_shipping_class(), 'product_shipping_class');
//                 WC()->shipping->load_shipping_methods();
//                 $shipping_methods = WC()->shipping->get_shipping_methods();
//                 
//                 // I have some logic for selecting which shipping method to use; your use case will likely be different, so figure out the method you need and store it in $selected_shipping_method
//                 $selected_shipping_method = $shipping_methods['free_shipping'];
//                 $class_cost = $selected_shipping_method->get_option('class_cost_' . $shipping_class->term_id);
//                 $quantity = 1;
//                 
//                 // As far as I can see, you need to create the order first, then the sub
//                 $order = wc_create_order(array('customer_id' => $user->id));
//                 $order->add_product( $product, $quantity, $args);
//                 $order->set_address( $address, 'billing' );
//                 $order->set_address( $address, 'shipping' );
//                 $order->add_shipping((object)array (
//                     'id' => $selected_shipping_method->id,
//                     'label'    => $selected_shipping_method->title,
//                     'cost'     => SUPER_Common::tofloat($class_cost),
//                     'taxes'    => array(),
//                     'calc_tax'  => 'per_order'
//                 ));
//                 $order->calculate_totals();
//                 $order->update_status("completed", 'Imported order', TRUE);
// 
//                 // Order created, now create sub attached to it -- optional if you're not creating a subscription, obvs
//                 // Each variation has a different subscription period
//                 $period = WC_Subscriptions_Product::get_period( $product );
//                 $interval = WC_Subscriptions_Product::get_interval( $product );
//                 $sub = wcs_create_subscription(array('order_id' => $order->get_id(), 'billing_period' => $period, 'billing_interval' => $interval, 'start_date' => $start_date));
//                 $sub->add_product( $product, $quantity, $args);
//                 $sub->set_address( $address, 'billing' );
//                 $sub->set_address( $address, 'shipping' );
//                 $sub->add_shipping((object)array (
//                     'id' => $selected_shipping_method->id,
//                     'label'    => $selected_shipping_method->title,
//                     'cost'     => SUPER_Common::tofloat($class_cost),
//                     'taxes'    => array(),
//                     'calc_tax'  => 'per_order'
//                 ));
//                 $sub->calculate_totals();
//                 WC_Subscriptions_Manager::activate_subscriptions_for_order($order);
//                 print "<a href='/wp-admin/post.php?post=" . $sub->id . "&action=edit'>Sub created! Click here to edit</a>";
//             }
// 
//             // Store the created order ID into a session, to either alter the redirect URL or for developers to use in their custom code
//             // The redirect URL will only be altered if the option to do so was enabled in the form settings.
//             SUPER_Common::setClientData( array( 'name'=> 'wc_custom_orders_created_order', 'value'=>$order->get_id( ) ) );
//             do_action( 'super_wc_custom_orders_after_insert_order_action', array( 'order_id'=>$order->get_id(), 'data'=>$data, 'atts'=>$atts ) );
//         }
// 
//         
//         /**
//          * Hook into settings and add WooCommerce Custom Orders settings
//          *
//         */
//         public static function add_settings( $array, $x ) {
//             $default = $x['default'];
//             $settings = $x['settings'];
//             
//             // If woocommerce is not loaded, just return the array
//             if(!function_exists('WC')) return $array;
//             
//             $default_address = sprintf( esc_html__( 'first_name|{first_name}%1$slast_name|{last_name}%1$scompany|{company}%1$semail|{email}%1$sphone|{phone}%1$saddress_1|{address_1}%1$saddress_2|{address_2}%1$scity|{city}%1$sstate|{state}%1$spostcode|{postcode}%1$scountry|{country}', 'super-forms' ), "\n" );
//             $array['wc_custom_orders'] = array(        
//                 'hidden' => 'settings',
//                 'name' => esc_html__( 'WooCommerce Custom Orders', 'super-forms' ),
//                 'label' => esc_html__( 'WooCommerce Custom Orders Settings', 'super-forms' ),
//                 'fields' => array(
//                     'wc_custom_orders_action' => array(
//                         'name' => esc_html__( 'Actions', 'super-forms' ),
//                         'default' =>  'none',
//                         'filter' => true,
//                         'type' => 'select',
//                         'values' => array(
//                             'none' => esc_html__( 'None (do nothing)', 'super-forms' ),
//                             'create_order' => esc_html__( 'Create/Update WooCommerce Order', 'super-forms' ),
//                             'create_subscription' => esc_html__( 'Create/Update WooCommerce Subscription', 'super-forms' ),
//                         ),
//                     ),
//                     'wc_custom_orders_redirect' => array(
//                         'name' => esc_html__( 'Redirect to:', 'super-forms' ),
//                         'label' => esc_html__( 'Choose between redirecting to the payment gateway, the created order itself or to the "Order received" page', 'super-forms' ),
//                         'default' =>  'gateway',
//                         'type' => 'select',
//                         'values' => array(
//                             'gateway' => esc_html__( 'Payment gateway (default)', 'super-forms' ),
//                             'pay_for_order' => esc_html__( 'Pay for order page (redirects to front-end payment page)', 'super-forms' ),
//                             'order' => esc_html__( 'Created order (redirects to order in back-end)', 'super-forms' ),
//                             'order_received_page' => esc_html__( 'Order received page (redirects to front-end summary page)', 'super-forms' ),
//                             'none' => esc_html__( 'Disabled (do not redirect)', 'super-forms' )
//                         ),
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription'
//                     ),
//                     'wc_custom_orders_id' => array(
//                         'name' => esc_html__( 'Enter order ID in case you want to update an existing order', 'super-forms' ),
//                         'label' => esc_html__( 'Leave blank to create a new order instead (use {tags} if needed)', 'super-forms' ),
//                         'type' => 'text',
//                         'default' =>  '',
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_products' => array(
//                         'name' => esc_html__( 'Enter the product(s) ID that needs to be added to the order', 'super-forms' ),
//                         'label' => sprintf( esc_html__( 'Put each one a new line. If the product ID is set to 0 or doesn\'t exist, it will be added as an Arbitrary product instead.%s{product_id}|{quantity}|{name}|{variation_id}|{subtotal}|{total}|{tax_class}|{variation}%sExample: 0|1|T-shirt|0|10|10|0|color;red#size;XL
//                             ', 'super-forms' ), '<br />', '<br />' ),
//                         'placeholder' => '{product_id}|{quantity}|{name}|{variation_id}|{subtotal}|{total}|{tax_class}|{variation}',
//                         'type' => 'textarea',
//                         'default' =>  "0|1|T-shirt|0|10|10|0|color;red#size;XL",
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_products_meta' => array(
//                         'name' => esc_html__( 'Enter the product(s) custom meta data (optional)', 'super-forms' ),
//                         'label' => sprintf( esc_html__( 'If field is inside dynamic column, system will automatically add all the meta data. Put each product ID with it\'s meta data on a new line separated by pipes "|".%1$s%2$sExample with tags:%3$s {id}|Color|{color}%1$s%2$sExample without tags:%3$s 82921|Color|Red%1$s%2$sAllowed values:%3$s integer|string|string.', 'super-forms' ), '<br />', '<strong>', '</strong>' ),
//                         'desc' => esc_html__( 'Put each on a new line, {tags} can be used to retrieve data', 'super-forms' ),
//                         'type' => 'textarea',
//                         'default' =>  "{product_id}|Color|{color}",
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_shipping_costs' => array(
//                         'name' => esc_html__( 'Shipping cost(s)', 'super-forms' ),
//                         'label' => sprintf( esc_html__( '%2$sPut each shipping cost on a new line. Example format:%3$s%1$sshipping_rate_id|shipping_rate_label|cost|method_id|instance_id%1$s%2$sExample without tags:%3$s flat_rate_shipping|Ship by airplane|275|flat_rate%1$s%2$sExample with tags:%3$s {shipping_method_id}|{shipping_method_label}|{cost}|{shipping_method}%1$s%2$sValid shipping method ID\'s are:%3$s', 'super-forms' ), '<br />', '<strong>', '</strong>' ) . '<br />' . implode('<br />',array_values(array_keys(WC()->shipping->get_shipping_methods()))),
//                         'placeholder' => 'flat_rate_shipping|Ship by airplane|275|flat_rate',
//                         'type' => 'textarea',
//                         'default' =>  "",
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true
//                     ),
//                     'wc_custom_orders_fees' => array(
//                         'name' => esc_html__( 'Order fee(s)', 'super-forms' ),
//                         'label' => esc_html__( 'If field is inside dynamic column, system will automatically add all the fees. <strong>Put each fee on a new line. Example format:</strong><br />name|amount|tax_class|tax_status<br /><strong>Example without tags:</strong> Extra processing fee|45|zero-rate|taxable<br /><strong>Example with tags:</strong> {fee_name}|{amount}|zero-rate|taxable<br /><strong>Valid tax classes are:</strong>', 'super-forms' ).'<br />'.implode('<br />',(WC_Tax::get_tax_class_slugs())),
//                         'placeholder' => 'Handling fee|45',
//                         'type' => 'textarea',
//                         'default' =>  "",
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_billing' => array(
//                         'name' => esc_html__( 'Define billing address', 'super-forms' ),
//                         'label' => esc_html__( 'Put each item on a new line and use {tags} to retrieve values dynamically', 'super-forms' ),
//                         'type' => 'textarea',
//                         'placeholder' => $default_address,
//                         'default' =>  $default_address,
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_shipping' => array(
//                         'name' => esc_html__( 'Define shipping address', 'super-forms' ),
//                         'label' => esc_html__( 'Put each item on a new line and use {tags} to retrieve values dynamically', 'super-forms' ),
//                         'type' => 'textarea',
//                         'placeholder' => $default_address,
//                         'default' =>  $default_address,
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_coupon' => array(
//                         'name' => esc_html__( 'Coupon code ', 'super-forms' ),
//                         'label' => esc_html__( '(use {tags} if needed)', 'super-forms' ),
//                         'type' => 'text',
//                         'default' =>  '',
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_customer_note' => array(
//                         'name' => esc_html__( 'Customer note', 'super-forms' ),
//                         'label' => esc_html__( '(use {tags} if needed, leave blank for no none)', 'super-forms' ),
//                         'type' => 'textarea',
//                         'default' =>  '',
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_status' => array(
//                         'name' => esc_html__( 'Order status ', 'super-forms' ),
//                         'label' => sprintf( esc_html__( 'Use {tags} if needed.%s%sValid statuses are:%s', 'super-forms' ), '<br />', '<strong>', '</strong>' ) . '<br />' . implode(', ',array_keys(wc_get_order_statuses())),
//                         'type' => 'text',
//                         'default' =>  '',
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_order_notes' => array(
//                         'name' => esc_html__( 'Add order notes', 'super-forms' ),
//                         'label' => esc_html__( '(use {tags} if needed, leave blank for no order notes, put each order note on a new line and specify if the note is a customer note)', 'super-forms' ),
//                         'type' => 'textarea',
//                         'placeholder' => sprintf( esc_html__( 'This is a customer note|true%sAnd this is not a customer note|false', 'super-forms' ), "\n" ),
//                         'default' =>  '',
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_payment_gateway' => array(
//                         'name' => esc_html__( 'Set a fixed payment gateway (optional)', 'super-forms' ),
//                         'label' => sprintf( esc_html__( 'Leave blank to let user decide what payment gateway to use. Use {tags} if needed.%s%sValid payment gateways are:%s', 'super-forms' ), '<br />', '<strong>', '</strong>' ) . '<br />' . implode(', ',array_keys(WC()->payment_gateways->get_available_payment_gateways())),
//                         'type' => 'text',
//                         'default' =>  '',
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_customer_id' => array(
//                         'name' => esc_html__( 'Customer ID', 'super-forms' ),
//                         'label' => esc_html__( '(use {tags} if needed, defaults to logged in user)', 'super-forms' ),
//                         'type' => 'text',
//                         'default' =>  '',
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     ),
//                     'wc_custom_orders_meta' => array(
//                         'name' => esc_html__( 'Save custom order meta data', 'super-forms' ),
//                         'label' => esc_html__( 'Example: _first_name|{first_name}', 'super-forms' ),
//                         'desc' => esc_html__( 'Based on your form fields you can save custom meta data for your order', 'super-forms' ),
//                         'placeholder' => "meta_key|{field1}\nmeta_key2|{field2}\nmeta_key3|{field3}",
//                         'type' => 'textarea',
//                         'default' =>  '',
//                         'filter' => true,
//                         'parent' => 'wc_custom_orders_action',
//                         'filter_value' => 'create_order,create_subscription',
//                         'allow_empty' => true,
//                     )
//                 )
//             );
//             return $array;
//         }
//     }
// endif;
// 
// /**
//  * Returns the main instance of SUPER_WC_Custom_Orders to prevent the need to use globals.
//  *
//  * @return SUPER_WC_Custom_Orders
//  */
// if(!function_exists('SUPER_WC_Custom_Orders')){
//     function SUPER_WC_Custom_Orders() {
//         return SUPER_WC_Custom_Orders::instance();
//     }
//     // Global for backwards compatibility.
//     $GLOBALS['SUPER_WC_Custom_Orders'] = SUPER_WC_Custom_Orders();
// }