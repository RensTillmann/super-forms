<?php
/**
 * Super Forms - WooCommerce Checkout
 *
 * @package   Super Forms - WooCommerce Checkout
 * @author    feeling4design
 * @link      http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * @copyright 2019 by feeling4design
 *
 * @wordpress-plugin
 * Plugin Name: Super Forms - WooCommerce Checkout
 * Plugin URI:  http://codecanyon.net/item/super-forms-drag-drop-form-builder/13979866
 * Description: Checkout with WooCommerce after form submission. Charge users for registering or posting content.
 * Version:     1.6.0
 * Author:      feeling4design
 * Author URI:  http://codecanyon.net/user/feeling4design
 * Text Domain: super-forms
 * Domain Path: /i18n/languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_WooCommerce')) :


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
        public $version = '1.6.0';


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
            if(is_null( self::$_instance)){
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        
        /**
         * SUPER_WooCommerce Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->init_hooks();
            do_action('super_woocommerce_loaded');
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
            
            // Filters since 1.0.0
            add_filter( 'super_after_contact_entry_data_filter', array( $this, 'add_entry_order_link' ), 10, 2 );

            // Filters since 1.2.0
            add_filter( 'super_countries_list_filter', array( $this, 'return_wc_countries' ), 10, 2 );

            // Actions since 1.0.0
            add_action( 'super_front_end_posting_after_insert_post_action', array( $this, 'save_wc_order_post_session_data' ) );
            add_action( 'super_after_wp_insert_user_action', array( $this, 'save_wc_order_signup_session_data' ) );
            add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ), 10, 1 );
            add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 1, 3 );
            add_action( 'super_after_saving_contact_entry_action', array( $this, 'set_contact_entry_order_id_session' ), 10, 3 );
            add_action( 'woocommerce_new_order_item', array( $this, 'add_order_item_meta' ), 10, 3);
            add_filter( 'woocommerce_get_item_data', array( $this, 'display_product_meta_data_frontend' ), 10, 2 );

            if ( $this->is_request( 'frontend' ) ) {
                add_filter( 'woocommerce_checkout_get_value', array( $this, 'populate_checkout_field_values' ), 10, 2 );
                add_filter( 'woocommerce_checkout_fields' , array( $this, 'custom_override_checkout_fields' ) );
                add_action( 'woocommerce_cart_calculate_fees', array( $this, 'additional_shipping_costs' ), 5 );
            }
            
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
                add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'checkout_field_display_admin_order_meta' ), 10, 1 );
                add_action( 'all_admin_notices', array( $this, 'display_activation_msg' ) );
                add_action( 'init', array( $this, 'update_plugin' ) );
            }
            
            if ( $this->is_request( 'ajax' ) ) {
                add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ) );
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
         * Display activation message for automatic updates
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
         * This function takes the last comma or dot (if any) to make a clean float, ignoring thousand separator, currency or any other letter :
         */
        public static function tofloat($num) {
            $dotPos = strrpos($num, '.');
            $commaPos = strrpos($num, ',');
            $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : 
                ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
           
            if (!$sep) {
                return floatval(preg_replace("/[^0-9]/", "", $num));
            } 

            return floatval(
                preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
                preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
            );
        }


        /**
         * Display custom product meta data on Cart and Checkout pages
         *
         *  @since      1.3.4
        */
        public function display_product_meta_data_frontend( $item_data, $cart_item ) {
            if( isset($cart_item['super_data']) ) {
                foreach($cart_item['super_data'] as $k => $v){
                    $item_data[] = array( 
                        'name' =>  $k,
                        'value' => $v
                    );
                }
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
            if($woocommerce->cart != null){
                foreach ( $woocommerce->cart->get_cart() as $k => $v ) {
                    if(!isset($values->legacy_cart_item_key)) continue;
                    if( $k==$values->legacy_cart_item_key ) {
                        foreach( $v['super_data'] as $k => $v ) {
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
            $custom_fields = SUPER_Forms()->session->get( '_super_wc_custom_fields' );
            if( is_array($custom_fields) ) {
                foreach( $custom_fields as $k => $v ) {
                    $fields[$v['section']][$v['name']] = array(
                        'label' => $v['label'],
                        'placeholder' => $v['placeholder'],
                        'type' => $v['type'],
                        'required' => ($v['required']=='true' ? true : false),
                        'clear' => ($v['clear']=='true' ? true : false),
                        'class' => array( $v['class'] ),
                        'label_class' => array( $v['label_class'] )
                    );
                    if( $v['type']=='select' ) {
                        $array = array();
                        $options =  explode( ";", $v['options'] );
                        foreach( $options as $ok => $ov ) {
                            $values = explode( ",", $ov );
                            $value = $values[0];
                            $label = $values[1];
                            $array[$value] = $label;
                        }
                        $fields[$v['section']][$v['name']]['options'] = $array;
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
        function checkout_field_display_admin_order_meta( $order ){
            $custom_fields = get_post_meta( $order->get_id(), '_super_wc_custom_fields', true );
            if( is_array($custom_fields) ) {
                foreach( $custom_fields as $k => $v ) {
                    echo '<p><strong>' . $v['label'] . ':</strong> ' . get_post_meta( $order->get_id(), $v['name'], true ) . '</p>';
                }
            }
        }


        /**
         * Return WC countries list for billing_country and shipping_country only
         *
         *  @since      1.2.0
        */
        public function return_wc_countries($countries, $data) {
            if( (class_exists('WC_Countries')) && (isset($data['settings']['woocommerce_checkout'])) && ($data['settings']['woocommerce_checkout']=='true') && ( ($data['name']=='billing_country') || ($data['name']=='shipping_country') ) ) {
                $countries_obj = new WC_Countries();
                $countries = $countries_obj->__get('countries');
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
            // Billing & Shipping
            $data = $woocommerce->session->get('_super_form_data', array() );
            if( (isset($data[$input])) && (isset($data[$input]['value'])) ) return $data[$input]['value'];
            // If form contained no field name that is used on checkout page see if there is a custom mapped one from the settings
            $fields = $woocommerce->session->get('_super_form_woocommerce_populate_checkout_fields', array() );
            if( isset($fields[$input]) ) return SUPER_Common::email_tags( $fields[$input], $data );
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
                $order_id = absint($order_id);
                if( $order_id!=0 ) {
                    $result .= '<tr><th align="right">' . esc_html__( 'WooCommerce Order', 'super-forms' ) . ':</th><td><span class="super-contact-entry-data-value">';
                    $result .= '<a href="' . get_admin_url() . 'post.php?post=' . $order_id . '&action=edit">' . get_the_title( $order_id ) . '</a>';
                    $result .= '</span></td></tr>';
                }
            }
            return $result;
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
                if( $post_type=='super_contact_entry' ) {
                    $woocommerce->session->set( '_super_entry_id', array( 'entry_id'=>$data['entry_id'] ) );
                }else{
                    $woocommerce->session->set( '_super_entry_id', array() );
                }
            }
        }


        /**
         * Add additional shipping costs
         * 
         * @since       1.0.0
        */
        function additional_shipping_costs( ) {
            global $woocommerce;
            $_super_wc_fee = SUPER_Forms()->session->get( '_super_wc_fee' );
            if( $_super_wc_fee!=false ) {
                foreach( $_super_wc_fee as $k => $v ) {
                    if( $v['amount']>0 ) {
                        $woocommerce->cart->add_fee( $v['name'], $v['amount'], $v['taxable'], $v['tax_class'] );
                    }else{
                        $woocommerce->cart->add_fee( $v['name'], $v['amount'], $v['taxable'], $v['tax_class'] );
                    }
                }
            }
        }


        /**
         * If Front-end posting add-on is activated and being used retrieve the inserted Post ID and save it to the WC Order
         *
         *  @since      1.0.0
        */
        function save_wc_order_post_session_data( $data ) {
            global $woocommerce;
            if($woocommerce){
                // Check if Front-end Posting add-on is activated
                if ( class_exists( 'SUPER_Frontend_Posting' ) ) {
                    $post_id = absint($data['post_id']);
                    $settings = $data['atts']['settings'];
                    if( (isset($settings['frontend_posting_action']) ) && ($settings['frontend_posting_action']=='create_post') ) {
                        $woocommerce->session->set( '_super_wc_post', array( 'post_id'=>$post_id, 'status'=>$settings['woocommerce_post_status'] ) );
                    }else{
                        $woocommerce->session->set( '_super_wc_post', array() );
                    }
                }else{
                    $woocommerce->session->set( '_super_wc_post', array() );
                }
            }
        }


        /**
         * If Register & Login add-on is activated and being used retrieve the created User ID and save it to the WC Order
         *
         *  @since      1.0.0
        */
        function save_wc_order_signup_session_data( $data ) {
            global $woocommerce;
            if($woocommerce){
                // Check if Register & Login add-on is activated
                if ( class_exists( 'SUPER_Register_Login' ) ) {
                    $user_id = absint($data['user_id']);
                    $settings = $data['atts']['settings'];
                    if( !empty($settings['register_login_action']) && $settings['register_login_action']=='register' && $user_id!=0 ) {
                        $user_role = '';
                        if( !empty($settings['woocommerce_completed_user_role']) ) {
                            $user_role = $settings['woocommerce_completed_user_role'];
                        }
                        $woocommerce->session->set( '_super_wc_signup', array( 
                            'user_id' => $user_id,
                            'status' => $settings['woocommerce_signup_status'],
                            'role' => $user_role
                        ));
                    }else{
                        $woocommerce->session->set( '_super_wc_signup', array() );
                    }
                }else{
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
            $custom_fields = SUPER_Forms()->session->get( '_super_wc_custom_fields' );
            if( !empty($custom_fields) ) {
                update_post_meta( $order_id, '_super_wc_custom_fields', $custom_fields );
                if( is_array($custom_fields) ) {
                    foreach( $custom_fields as $k => $v ) {
                        if ( !empty($_POST[$v['name']]) ) {
                            update_post_meta( $order_id, $v['name'], sanitize_text_field( $_POST[$v['name']] ) );
                        }
                    }
                }
            }
            
            // @since 1.2.2 - save entry data to the order
            $data = SUPER_Forms()->session->get( '_super_wc_entry_data' );
            update_post_meta( $order_id, '_super_wc_entry_data', $data );

            global $woocommerce;
            if($woocommerce){
                $_super_wc_post = $woocommerce->session->get( '_super_wc_post', array() );
                update_post_meta( $order_id, '_super_wc_post', $_super_wc_post );

                $_super_wc_signup = $woocommerce->session->get( '_super_wc_signup', array() );
                update_post_meta( $order_id, '_super_wc_signup', $_super_wc_signup );

                $_super_entry_id = $woocommerce->session->get( '_super_entry_id', array() );
                if( isset($_super_entry_id['entry_id']) ) {
                    update_post_meta( $_super_entry_id['entry_id'], '_super_contact_entry_wc_order_id', $order_id );
                }
            }
        }


        /**
         * After order status changed
         * 
         * @since       1.3.8
        */
        public function order_status_changed( $order_id, $old_status, $new_status ) {
            if( $new_status=='completed' ) {
                $_super_wc_post = get_post_meta( $order_id, '_super_wc_post', true );
                if ( !empty( $_super_wc_post ) ) { // @since 1.0.2 - check if not empty
                    $my_post = array(
                        'ID' => $_super_wc_post['post_id'],
                        'post_status' => $_super_wc_post['status'],
                    );
                    wp_update_post( $my_post );


                }

                // Update user login status and role
                $_super_wc_signup = get_post_meta( $order_id, '_super_wc_signup', true );
                if ( !empty( $_super_wc_signup ) ) { // @since 1.0.2 - check if not empty
                    // Update login status
                    if(!empty($_super_wc_signup['user_id'])) {
                        update_user_meta( $_super_wc_signup['user_id'], 'super_user_login_status', $_super_wc_signup['status'] );
                        // Update user role
                        if( !empty($_super_wc_signup['role']) ) {
                            $userdata = array(
                                'ID' => $_super_wc_signup['user_id'],
                                'role' => $_super_wc_signup['role']
                            );
                            $result = wp_update_user( $userdata );
                            if( is_wp_error( $result ) ) {
                                throw new Exception($return->get_error_message());
                            }
                        }
                    }
                }

                // @since 1.3.8 - Check if sending email is enabled
                $data = get_post_meta( $order_id, '_super_wc_entry_data', true);
                $form_id = absint($data['hidden_form_id']['value']);
                if (method_exists('SUPER_Common','get_form_settings')) {
                    $form_settings = SUPER_Common::get_form_settings($form_id);
                }else{
                    $settings = get_post_meta(absint($form_id), '_super_form_settings', true);
                }

                if( !empty($form_settings['woocommerce_completed_email']) ) {
                    
                    $global_settings = get_option( 'super_settings' );
                    if( $form_settings!=false ) {
                        // @since 4.0.0 - when adding new field make sure we merge settings from global settings with current form settings
                        foreach( $form_settings as $k => $v ) {
                            if( isset( $global_settings[$k] ) ) {
                                if( $global_settings[$k] == $v ) {
                                    unset( $form_settings[$k] );
                                }
                            }
                        }
                    }else{
                        $form_settings = array();
                    }
                    $settings = array_merge($global_settings, $form_settings);

                    if( !empty( $settings['woocommerce_completed_header_additional'] ) ) {
                        $header_additional = '';
                        if( !empty( $settings['woocommerce_completed_header_additional'] ) ) {
                            $headers = explode( "\n", $settings['woocommerce_completed_header_additional'] );   
                            foreach( $headers as $k => $v ) {
                                // @since 1.2.6.92
                                $v = SUPER_Common::email_tags( $v, $data, $settings );
                                $header_additional .= $v . "\r\n";
                            }
                        }
                        $settings['woocommerce_completed_header_additional'] = $header_additional;
                    }

                    $email_loop = '';
                    $attachments = array();
                    $confirm_attachments = array();
                    $string_attachments = array();
                    if( ( isset( $data ) ) && ( count( $data )>0 ) ) {
                        foreach( $data as $k => $v ) {
                            $row = $settings['woocommerce_completed_email_loop'];

                            if( !isset( $v['exclude'] ) ) {
                                $v['exclude'] = 0;
                            }
                            if( $v['exclude']==2 ) {
                                continue;
                            }
                            $result = apply_filters( 'super_before_email_loop_data_filter', $row, array( 'v'=>$v, 'string_attachments'=>$string_attachments ) );
                            if( isset( $result['status'] ) ) {
                                if( $result['status']=='continue' ) {
                                    if( isset( $result['string_attachments'] ) ) {
                                        $string_attachments = $result['string_attachments'];
                                    }
                                    $email_loop .= $result['row'];
                                    continue;
                                }
                            }
                            if( $v['type']=='files' ) {
                                $files_value = '';
                                if( ( !isset( $v['files'] ) ) || ( count( $v['files'] )==0 ) ) {
                                    if( !empty( $v['label'] ) ) {
                                        $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                    }else{
                                        $row = str_replace( '{loop_label}', '', $row );
                                    }
                                    $files_value .= esc_html__( 'User did not upload any files', 'super-forms' );
                                }else{
                                    foreach( $v['files'] as $key => $value ) {
                                        if( $key==0 ) {
                                            if( !empty( $v['label'] ) ) {
                                                $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                            }else{
                                                $row = str_replace( '{loop_label}', '', $row );
                                            }
                                        }
                                        $files_value .= '<a href="' . $value['url'] . '" target="_blank">' . $value['value'] . '</a><br /><br />';
                                        if( $v['exclude']!=2 ) {
                                            if( $v['exclude']==1 ) {
                                                $attachments[$value['value']] = $value['url'];
                                            }else{
                                                $attachments[$value['value']] = $value['url'];
                                            }
                                        }
                                    }
                                }
                                $row = str_replace( '{loop_value}', $files_value, $row );
                            }else{
                                if( ($v['type']=='form_id') || ($v['type']=='entry_id') ) {
                                    $row = '';
                                }else{
                                    if( !empty( $v['label'] ) ) {
                                        $row = str_replace( '{loop_label}', SUPER_Common::decode( $v['label'] ), $row );
                                    }else{
                                        $row = str_replace( '{loop_label}', '', $row );
                                    }
                                    // @since 1.2.7
                                    if( isset( $v['admin_value'] ) ) {
                                        // @since 3.9.0 - replace comma's with HTML
                                        if( !empty($v['replace_commas']) ) $v['admin_value'] = str_replace( ',', $v['replace_commas'], $v['admin_value'] );
                                        
                                        $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['admin_value'] ), $row );
                                    }
                                    if( isset( $v['confirm_value'] ) ) {
                                        // @since 3.9.0 - replace comma's with HTML
                                        if( !empty($v['replace_commas']) ) $v['confirm_value'] = str_replace( ',', $v['replace_commas'], $v['confirm_value'] );
                                    }
                                    if( isset( $v['value'] ) ) {
                                        // @since 3.9.0 - replace comma's with HTML
                                        if( !empty($v['replace_commas']) ) $v['value'] = str_replace( ',', $v['replace_commas'], $v['value'] );
                                        
                                        $row = str_replace( '{loop_value}', SUPER_Common::decode_textarea( $v['value'] ), $row );
                                    }
                                }
                            }
                            $email_loop .= $row;
                        }
                    }
                    
                    if(!empty($settings['woocommerce_completed_body_open'])) $settings['woocommerce_completed_body_open'] = $settings['woocommerce_completed_body_open'] . '<br /><br />';
                    if(!empty($settings['woocommerce_completed_body'])) $settings['woocommerce_completed_body'] = $settings['woocommerce_completed_body'] . '<br /><br />';
                    $email_body = $settings['woocommerce_completed_body_open'] . $settings['woocommerce_completed_body'] . $settings['woocommerce_completed_body_close'];
                    $email_body = str_replace( '{loop_fields}', $email_loop, $email_body );
                    $email_body = SUPER_Common::email_tags( $email_body, $data, $settings );
                
                    // @since 3.1.0 - optionally automatically add line breaks
                    if(!isset($settings['woocommerce_completed_body_nl2br'])) $settings['woocommerce_completed_body_nl2br'] = 'true';
                    if($settings['woocommerce_completed_body_nl2br']=='true') $email_body = nl2br( $email_body );
                    
                    $email_body = apply_filters( 'super_before_sending_email_body_filter', $email_body, array( 'settings'=>$settings, 'email_loop'=>$email_loop, 'data'=>$data ) );
                    if( !isset( $settings['woocommerce_completed_from_type'] ) ) $settings['woocommerce_completed_from_type'] = 'default';
                    if( $settings['woocommerce_completed_from_type']=='default' ) {
                        $settings['woocommerce_completed_from_name'] = get_option( 'blogname' );
                        $settings['woocommerce_completed_from'] = get_option( 'admin_email' );
                    }
                    if( !isset( $settings['woocommerce_completed_from_name'] ) ) $settings['woocommerce_completed_from_name'] = get_option( 'blogname' );
                    if( !isset( $settings['woocommerce_completed_from'] ) ) $settings['woocommerce_completed_from'] = get_option( 'admin_email' );

                    $to = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_to'], $data, $settings ) );
                    $from = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_from'], $data, $settings ) );
                    $from_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_from_name'], $data, $settings ) );
                    
                    $cc = '';
                    if( !empty($settings['woocommerce_completed_header_cc']) ) {
                        $cc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_header_cc'], $data, $settings ) );
                    }
                    $bcc = '';
                    if( !empty($settings['woocommerce_completed_header_bcc']) ) {
                        $bcc = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_header_bcc'], $data, $settings ) );
                    }
                    
                    $subject = SUPER_Common::decode( SUPER_Common::email_tags( $settings['woocommerce_completed_subject'], $data, $settings ) );

                    // @since 2.8.0 - custom reply to headers
                    if( !isset($settings['woocommerce_completed_header_reply_enabled']) ) $settings['woocommerce_completed_header_reply_enabled'] = false;
                    $reply = '';
                    $reply_name = '';
                    if( $settings['woocommerce_completed_header_reply_enabled']==false ) {
                        $custom_reply = false;
                    }else{
                        $custom_reply = true;
                        if( !isset($settings['woocommerce_completed_header_reply']) ) $settings['woocommerce_completed_header_reply'] = '';
                        if( !isset($settings['woocommerce_completed_header_reply_name']) ) $settings['woocommerce_completed_header_reply_name'] = '';
                        $reply = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_header_reply'], $data, $settings ) );
                        $reply_name = SUPER_Common::decode_email_header( SUPER_Common::email_tags( $settings['woocommerce_completed_header_reply_name'], $data, $settings ) );
                    }

                    // @since 3.3.2 - default admin email attachments
                    if( !empty($settings['woocommerce_completed_attachments']) ) {
                        $email_attachments = explode( ',', $settings['woocommerce_completed_attachments'] );
                        foreach($email_attachments as $k => $v){
                            $file = get_attached_file($v);
                            if( $file ) {
                                $url = wp_get_attachment_url($v);
                                $filename = basename ( $file );
                                $attachments[$filename] = $url;
                            }
                        }
                    }

                    // @since 2.0
                    $attachments = apply_filters( 'super_before_sending_email_attachments_filter', $attachments, array( 'settings'=>$settings, 'data'=>$data, 'email_body'=>$email_body ) );
                    
                    // Send the email
                    $mail = SUPER_Common::email( $to, $from, $from_name, $custom_reply, $reply, $reply_name, $cc, $bcc, $subject, $email_body, $settings, $attachments, $string_attachments );

                }
            }
        }


        /**
         * Loop through {tags} if dynamic column is used
         *
         *  @since      1.3.4
        */
        public static function new_wc_checkout_products( $products_tags, $i, $looped, $product, $id, $quantity, $variation, $price ){
            if(!in_array($i, $looped)){
                $new_line = '';
            
                // Get the product ID tag
                if( $product[0][0]=='{' ) { 
                    $new_line .= '{' . $id . '_' . $i . '}'; 
                }else{ 
                    $new_line .= $product[0]; 
                }

                // Get the product quantity tag
                if( $product[1][0]=='{' ) { 
                    $new_line .= '|{' . $quantity . '_' . $i . '}'; 
                }else{ 
                    if(!empty($product[1])) $new_line .= '|' . $product[1]; 
                }

                // Get the product variation ID tag
                if( $product[2][0]=='{' ) { 
                    $new_line .= '|{' . $variation . '_' . $i . '}'; 
                }else{ 
                    if(!empty($product[2])) $new_line .= '|' . $product[2]; 
                }

                // Get the product price tag
                if( $product[3][0]=='{' ) { 
                    $new_line .= '|{' . $price . '_' . $i . '}'; 
                }else{ 
                    if(!empty($product[3])) $new_line .= '|' . $product[3]; 
                }

                $products_tags[] = $new_line;
                $looped[$i] = $i;
                $i++;
                return array(
                    'i'=>$i, 
                    'looped'=>$looped, 
                    'products_tags'=>$products_tags 
                );
            }else{
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
            if( isset( $atts['data'] ) ) {
                $data = $atts['data'];
            }else{
                if( $settings['save_contact_entry']=='yes' ) {
                    $data = get_post_meta( $atts['entry_id'], '_super_contact_entry_data', true );
                }else{
                    $data = $atts['post']['data'];
                }
            }

            // @since 1.2.2 - first reset order entry data
            SUPER_Forms()->session->set( '_super_wc_entry_data', false );

            if( (isset($settings['woocommerce_checkout'])) && ($settings['woocommerce_checkout']=='true') ) {

                // @since 1.2.2 - save the entry data to the order
                SUPER_Forms()->session->set( '_super_wc_entry_data', $data );

                // No products defined to add to cart!
                if( (!isset($settings['woocommerce_checkout_products'])) || (empty($settings['woocommerce_checkout_products'])) ) {
                    $msg = sprintf( esc_html__( 'You haven\'t defined what products should be added to the cart. Please %sedit%s your form settings and try again', 'super-forms' ), '<a href="' . get_admin_url() . 'admin.php?page=super_create_form&id=' . absint( $atts['post']['form_id'] ) . '">', '</a>' );
                    SUPER_Common::output_message(
                        $error = true,
                        $msg = $msg,
                        $redirect = null
                    );
                }

                $checkout_products = explode( "\n", $settings['woocommerce_checkout_products'] );  
                $products_tags = $checkout_products;
                foreach( $checkout_products as $k => $v ) {
                    $product =  explode( "|", $v );
                    if( isset( $product[0] ) ) $id = trim($product[0], '{}');
                    if( isset( $product[1] ) ) $quantity = trim($product[1], '{}');
                    if( isset( $product[2] ) ) $variation = trim($product[2], '{}');
                    if( isset( $product[3] ) ) $price = trim($product[3], '{}');
                    $looped = array();
                    $i=2;
                    while( isset( $data[$id . '_' . ($i)]) ) {
                        $array = self::new_wc_checkout_products( $products_tags, $i, $looped, $product, $id, $quantity, $variation, $price );
                        if($array==false) break;
                        $i = $array['i'];
                        $looped = $array['looped'];
                        $products_tags = $array['products_tags'];
                    }
                }

                $woocommerce_checkout_products_meta = explode( "\n", $settings['woocommerce_checkout_products_meta'] );  
                $values = array();
                $meta = array();
                $regex = "/{(.*?)}/";
                foreach( $woocommerce_checkout_products_meta as $wck => $v ) {
                    $product =  explode( "|", $v );

                    // Skip if not enough values where found, we must have ID|Label|Value (a total of 3 values)
                    if( count($product) < 3 ) {
                        continue;
                    }

                    $found = false; // In case we found this tag in the submitted data

                    // Check if Product ID was set via a {tag} e.g: {tshirt_id}
                    if( isset( $product[0] ) ) {
                        $values[0]['value'] = $product[0];
                        $match = preg_match_all($regex, $product[0], $matches, PREG_SET_ORDER, 0);
                        if( $match ) {
                            $values[0]['value'] = trim($values[0]['value'], '{}');
                            $values[0]['match'] = true;
                            foreach( $matches as $k => $v ) {
                                $key = str_replace(';label', '', $v[1]); // @since 1.3.7
                                if( isset($data[$key]) ) {
                                    $found = true;
                                }
                            }
                        }
                    }

                    // Check if meta Label was set via a {tag} e.g: {tshirt_meta_label}
                    if( isset( $product[1] ) ) {
                        $values[1]['value'] = $product[1];
                        $match = preg_match_all($regex, $product[1], $matches, PREG_SET_ORDER, 0);
                        if( $match ) {
                            $values[1]['value'] = trim($values[1]['value'], '{}');
                            $values[1]['match'] = true;
                            foreach( $matches as $k => $v ) {
                                $key = str_replace(';label', '', $v[1]); // @since 1.3.7
                                if( isset($data[$key]) ) {
                                    $found = true;
                                }
                            }
                        }
                    } 
                  
                    // Check if meta Value was set via a {tag} e.g: {tshirt_color}
                    if( isset( $product[2] ) ) {
                        $values[2]['value'] = $product[2];
                        $match = preg_match_all($regex, $product[2], $matches, PREG_SET_ORDER, 0);
                        if( $match ) {
                            $values[2]['value'] = trim($values[2]['value'], '{}');
                            $values[2]['match'] = true;
                            foreach( $matches as $k => $v ) {
                                $key = str_replace(';label', '', $v[1]); // @since 1.3.7
                                if( isset($data[$key]) ) {
                                    $found = true;
                                }else{
                                    $product[2] = '';
                                }
                            }
                        }
                    }

                    // Let's first add the current meta lin to the new array
                    $meta[] = $product;

                    // We found a {tag} and it existed in the form data
                    if( $found ) {

                        $i=2;

                        // Check if any of the matches exists in a dynamic column and are inside the submitted data
                        $stop_loop = false;
                        while( !$stop_loop ) {
                            if( ( (isset($data[$values[0]['value'] . '_' . ($i)])) && ($values[0]['match']) ) || 
                                ( (isset($data[$values[1]['value'] . '_' . ($i)])) && ($values[1]['match']) ) || 
                                ( (isset($data[$values[2]['value'] . '_' . ($i)])) && ($values[2]['match']) ) ) {

                                // Check if ID is {tag}
                                $new_line = array();
                                if($values[0]['match']){
                                    $new_line[] = '{' . $values[0]['value'] . '_' . $i . '}'; 
                                }else{
                                    $new_line[] = $values[0]['value']; 
                                }

                                // Check if Label is {tag}
                                if($values[1]['match']){
                                    // The label must be unique compared to other labels so we have to add (2) behind it
                                    $new_line[] = '{' . $values[1]['value'] . '_' . $i . '}' . ' ('.$i.')';
                                }else{
                                    // The label must be unique compared to other labels so we have to add (2) behind it
                                    $new_line[] = $values[1]['value'] . ' ('.$i.')';
                                }

                                // Check if Value is {tag}
                                if($values[2]['match']){
                                    $new_line[] = '{' . $values[2]['value'] . '_' . $i . '}'; 
                                }else{
                                    $new_line[] = $values[2]['value']; 
                                }
                                $meta[] = $new_line;
                                $i++;
                            }else{
                                $stop_loop = true;
                            }
                        }
                    }
                }

                $products_meta = array();
                foreach( $meta as $mk => $mv ) {
                    $product_id = 0;
                    $meta_key = '';
                    $meta_value = '';
                    if( isset( $mv[0] ) ) $product_id = SUPER_Common::email_tags( $mv[0], $data, $settings );
                    if( isset( $mv[1] ) ) $meta_key = SUPER_Common::email_tags( $mv[1], $data, $settings );
                    if( isset( $mv[2] ) ) $meta_value = SUPER_Common::email_tags( $mv[2], $data, $settings );
                    if(!empty($meta_value)) $products_meta[$product_id][$meta_key] = $meta_value;
                }

                $products = array();
                foreach( $products_tags as $k => $v ) {
                    $product =  explode( "|", $v );
                    $product_id = 0;
                    $product_quantity = 0;
                    $product_variation_id = '';
                    $product_price = '';
                    if( isset( $product[0] ) ) $product_id = SUPER_Common::email_tags( $product[0], $data, $settings );
                    if( isset( $product[1] ) ) $product_quantity = SUPER_Common::email_tags( $product[1], $data, $settings );
                    if( isset( $product[2] ) ) $product_variation_id = SUPER_Common::email_tags( $product[2], $data, $settings );
                    if( isset( $product[3] ) ) $product_price = SUPER_Common::email_tags( $product[3], $data, $settings );

                    $product_price = self::tofloat($product_price);
                    $product_quantity = absint($product_quantity);
                    if( $product_quantity>0 ) {
                        // Check if multiple ideas found (seperate by comma)
                        $multi_products = explode(',', $product_id);
                        foreach( $multi_products as $product_id ) {
                            $product_id = absint($product_id);
                            $meta = array();
                            if( isset($products_meta[$product_id]) ) {
                                $meta = $products_meta[$product_id];
                            }
                            $products[] = array(
                                'id' => $product_id,
                                'quantity' => $product_quantity,
                                'variation_id' => absint($product_variation_id),
                                'price' => $product_price,
                                'super_data' => $meta
                            );
                        }

                    }
                }

                global $woocommerce;

                // Empty the cart
                if( (isset($settings['woocommerce_checkout_empty_cart'])) && ($settings['woocommerce_checkout_empty_cart']=='true') ) {
                    $woocommerce->cart->empty_cart();
                }

                // Remove any coupons.
                if( (isset($settings['woocommerce_checkout_remove_coupons'])) && ($settings['woocommerce_checkout_remove_coupons']=='true') ) {
                    $woocommerce->cart->remove_coupons();
                }

                // Add discount
                if( (isset($settings['woocommerce_checkout_coupon'])) && ($settings['woocommerce_checkout_coupon']!='') ) {
                    $woocommerce->cart->add_discount($settings['woocommerce_checkout_coupon']);
                }

                // Delete any fees
                if( (isset($settings['woocommerce_checkout_remove_fees'])) && ($settings['woocommerce_checkout_remove_fees']=='true') ) {
                    $woocommerce->session->set( 'fees', array() );
                    SUPER_Forms()->session->set( '_super_wc_fee', false );
                }

                // Add fee
                if( (isset($settings['woocommerce_checkout_fees'])) && ($settings['woocommerce_checkout_fees']!='') ) {
                    $fees = array();
                    $woocommerce_checkout_fees = explode( "\n", $settings['woocommerce_checkout_fees'] );  
                    foreach( $woocommerce_checkout_fees as $k => $v ) {
                        $fee =  explode( "|", $v );
                        $name = '';
                        $amount = 0;
                        $taxable = false;
                        $tax_class = '';
                        if( isset( $fee[0] ) ) $name = SUPER_Common::email_tags( $fee[0], $data, $settings );
                        if( isset( $fee[1] ) ) $amount = SUPER_Common::email_tags( $fee[1], $data, $settings );
                        if( isset( $fee[2] ) ) $taxable = SUPER_Common::email_tags( $fee[2], $data, $settings );
                        if( isset( $fee[3] ) ) $tax_class = SUPER_Common::email_tags( $fee[3], $data, $settings );
                        $amount = self::tofloat($amount);
                        if( $amount>0 ) {
                            $fees[] = array(
                                'name' => $name,            // ( string ) required – Unique name for the fee. Multiple fees of the same name cannot be added.
                                'amount' => $amount,        // ( float ) required – Fee amount.
                                'taxable' => $taxable,      // ( bool ) optional – (default: false) Is the fee taxable?
                                'tax_class' => $tax_class,  // ( string ) optional – (default: '') The tax class for the fee if taxable. A blank string is standard tax class.
                            );
                        }
                    }
                    SUPER_Forms()->session->set( '_super_wc_fee', $fees );
                }

                // @since 1.2.2 - Add custom checkout fields
                if( (isset($settings['woocommerce_checkout_fields'])) && ($settings['woocommerce_checkout_fields']!='') ) {
                    $fields = array();
                    $woocommerce_checkout_fields = explode( "\n", $settings['woocommerce_checkout_fields'] );  
                    foreach( $woocommerce_checkout_fields as $k => $v ) {
                        $field =  explode( "|", $v );
                        if( !isset( $field[0] ) ) {
                            continue; 
                        }
                        $name = '';
                        $value = '';
                        $label = '';
                        $placeholder = '';
                        $type = 'text';
                        $section = 'billing';
                        $required = 'true';
                        $clear = 'true';
                        $class = 'super-checkout-custom';
                        $label_class = 'super-checkout-custom-label';
                        $options = 'red,Red;blue,Blue;green,Green';
                        if( isset( $field[0] ) ) $name = SUPER_Common::email_tags( $field[0], $data, $settings );
                        if( isset( $field[1] ) ) $value = SUPER_Common::email_tags( $field[1], $data, $settings );
                        if( isset( $field[2] ) ) $label = SUPER_Common::email_tags( $field[2], $data, $settings );
                        if( isset( $field[3] ) ) $placeholder = SUPER_Common::email_tags( $field[3], $data, $settings );
                        if( isset( $field[4] ) ) $type = SUPER_Common::email_tags( $field[4], $data, $settings );
                        if( isset( $field[5] ) ) $section = SUPER_Common::email_tags( $field[5], $data, $settings );
                        if( isset( $field[6] ) ) $required = SUPER_Common::email_tags( $field[6], $data, $settings );
                        if( isset( $field[7] ) ) $clear = SUPER_Common::email_tags( $field[7], $data, $settings );
                        if( isset( $field[8] ) ) $class = SUPER_Common::email_tags( $field[8], $data, $settings );
                        if( isset( $field[9] ) ) $label_class = SUPER_Common::email_tags( $field[9], $data, $settings );
                        if( isset( $field[10] ) ) $options = SUPER_Common::email_tags( $field[10], $data, $settings );


                        // Only add the field if the field name was visible in the form itself
                        if( (isset($settings['woocommerce_checkout_fields_skip_empty'])) && ($settings['woocommerce_checkout_fields_skip_empty']=='true') ) {
                            if( !isset($data[$name]) ) {
                                continue;
                            }
                        }

                        $fields[] = array(
                            'name' => $name,
                            'value' => $value,
                            'label' => $label,
                            'placeholder' => $placeholder,
                            'type' => $type,
                            'section' => $section,
                            'required' => $required,
                            'clear' => $clear,
                            'class' => $class,
                            'label_class' => $label_class,
                            'options' => $options
                        );
                    }
                    SUPER_Forms()->session->set( '_super_wc_custom_fields', $fields );
                }


                global $wpdb;
          
                // Now add the product(s) to the cart
                foreach( $products as $k => $v ) {

                    if( class_exists('WC_Name_Your_Price_Helpers') ) {
                        $posted_nyp_field = 'nyp' . apply_filters( 'nyp_field_prefix', '', $v['id'] );
                        $_REQUEST[$posted_nyp_field] = wc_format_decimal($v['price']);
                    }

                    $new_attributes = array();
                    if( $v['variation_id']!=0 ) {
                        $product = wc_get_product( $v['id'] );
                        if( $product->product_type=='variable' ) {
                            $attributes = $product->get_variation_attributes();
                            foreach( $attributes as $ak => $av ) {
                                $new_attributes[$ak] = get_post_meta( $v['variation_id'], 'attribute_' . $ak, true );
                            }
                        }
                    }

                    $super_data = array();
                    if( isset($v['super_data']) ) {
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
                if( isset($settings['woocommerce_redirect']) ) {

                    if( (isset($settings['woocommerce_populate_checkout_fields'])) && ($settings['woocommerce_populate_checkout_fields']!='') ) {
                        // First reset
                        $woocommerce->session->set( '_super_form_woocommerce_populate_checkout_fields', array() );
                        $fields = array();
                        $woocommerce_populate_checkout_fields = explode( "\n", $settings['woocommerce_populate_checkout_fields'] );  
                        foreach( $woocommerce_populate_checkout_fields as $k => $v ) {
                            $field =  explode( "|", $v );
                            if( !isset( $field[0] ) ) continue; 
                            $value = '';
                            if( isset( $field[1] ) ) $value = SUPER_Common::email_tags( $field[1], $data, $settings );
                            $fields[$field[0]] = $value;
                        }
                        $woocommerce->session->set( '_super_form_woocommerce_populate_checkout_fields', $fields );
                    }

                    $woocommerce->session->set( '_super_form_data', $data ); // @since 1.2.0 - save data to session for billing fields
                    $redirect = null;
                    if( $settings['woocommerce_redirect']=='checkout' ) {
                        $redirect = wc_get_checkout_url();
                    }
                    if( $settings['woocommerce_redirect']=='cart' ) {
                        $redirect = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
                    }
                    if( $redirect!=null ) {
                        SUPER_Common::output_message(
                            $error = false,
                            $msg = '',
                            $redirect = $redirect
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
        public static function add_settings( $array, $settings ) {
            $array['woocommerce_checkout'] = array(        
                'hidden' => 'settings',
                'name' => esc_html__( 'WooCommerce Checkout', 'super-forms' ),
                'label' => esc_html__( 'WooCommerce Checkout', 'super-forms' ),
                'fields' => array(
                    'woocommerce_checkout' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'filter'=>true,
                        'values' => array(
                            'true' => esc_html__( 'Enable WooCommerce Checkout', 'super-forms' ),
                        ),
                    ),               
                    'woocommerce_checkout_empty_cart' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_empty_cart', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Empty cart before adding products', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    'woocommerce_checkout_remove_coupons' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_remove_coupons', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Remove/clear coupons before redirecting to cart', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ), 
                    'woocommerce_checkout_remove_fees' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_remove_fees', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Remove/clear fees before redirecting to cart', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    'woocommerce_checkout_products' => array(
                        'name' => esc_html__( 'Enter the product(s) ID that needs to be added to the cart', 'super-forms' ) . '<br /><i>' . sprintf( esc_html__( 'If field is inside dynamic column, system will automatically add all the products. Put each product ID with it\'s quantity on a new line separated by pipes "|".%1$s%2$sExample with tags:%3$s {id}|{quantity}%1$s%2$sExample without tags:%3$s 82921|3%1$s%2$sExample with variations:%3$s {id}|{quantity}|{variation_id}%1$s%2$sExample with dynamic pricing:%3$s {id}|{quantity}|none|{price}%1$s%2$sAllowed values:%3$s integer|integer|integer|float%1$s(dynamic pricing requires %4$sWooCommerce Name Your Price add-on%5$s).', 'super-forms' ), '<br />', '<strong>', '</strong>', '<a target="_blank" href="https://woocommerce.com/products/name-your-price/">', '</a>' ) . '</i>',
                        'desc' => esc_html__( 'Put each on a new line, {tags} can be used to retrieve data', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_products', $settings['settings'], "{id}|{quantity}|none|{price}" ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    // @since 1.3.4 - custom product meta data
                    'woocommerce_checkout_products_meta' => array(
                        'name' => sprintf( esc_html__( 'Enter the product(s) custom meta data (optional)', 'super-forms' ) . '<br /><i>' . esc_html__( 'If field is inside dynamic column, system will automatically add all the meta data. Put each product ID with it\'s meta data on a new line separated by pipes "|".%1$s%2$sExample with tags:%3$s {id}|Color|{color}%1$s%2$sExample without tags:%3$s 82921|Color|Red%1$s%2$sAllowed values:%3$s integer|string|string.', 'super-forms' ), '<br />', '<strong>', '</strong>' ) . '</i>',
                        'desc' => esc_html__( 'Put each on a new line, {tags} can be used to retrieve data', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_products_meta', $settings['settings'], "{id}|Color|{color}" ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),

                    'woocommerce_checkout_coupon' => array(
                        'name' => esc_html__( 'Apply the following coupon code (leave blank for none):', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_coupon', $settings['settings'], '' ),
                        'type' => 'text',
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    'woocommerce_checkout_fees' => array(
                        'name' => esc_html__( 'Add checkout fee(s)', 'super-forms' ) . '<br /><i>' . sprintf( esc_html__( 'Put each fee on a new line with values seperated by pipes "|".%1$s%2$sExample with tags:%3$s {fee_name}|{amount}|{taxable}|{tax_class}%1$s%2$sExample without tags:%3$s Administration fee|5|false|\'\'%1$s%2$sAllowed values:%3$s string|float|bool|string', 'super-forms' ), '<br />', '<strong>', '</strong>' ) . '</i>',
                        'desc' => esc_html__( 'Leave blank for no fees', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_fees', $settings['settings'], "{fee_name}|{amount}|{taxable}|{tax_class}" ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                        'allow_empty' => true,
                    ),
                    'woocommerce_populate_checkout_fields' => array(
                        'name' => esc_html__( 'Populate checkout fields with form data', 'super-forms' ) . '<br /><i>' . esc_html__( 'Put each on a new line', 'super-forms' ) . '</i><br />',
                        'label' => sprintf( esc_html__( 'Example:%1$sbilling_first_name|{first_name}%1$sbilling_last_name|{last_name}%1$sbilling_postcode|{zipcode}%1$sbilling_city|{city}%1$s%1$sPossible field to populate on checkout page are:%1$s', 'super-forms' ), '<br />' ) . ' billing_country, shipping_country, billing_first_name, billing_last_name, billing_company, billing_country, billing_address_1, billing_address_2, billing_postcode, billing_city, billing_state, billing_phone, billing_email, order_comments',
                        'desc' => esc_html__( 'Leave blank to not populate any checkout fields', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_populate_checkout_fields', $settings['settings'], "" ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true'
                    ),
                    // @since 1.2.2 - add custom checkout fields to checkout page
                    'woocommerce_checkout_fields' => array(
                        'name' => esc_html__( 'Add custom checkout field(s)', 'super-forms' ) . '<br /><i>' . esc_html__( 'Put each field on a new line with field options seperated by pipes "|".', 'super-forms' ) . '</i><br />',
                        'label' => sprintf( esc_html__( 'Example:%1$sbilling_custom|{billing_custom}|Billing custom|This is a custom field|text|billing|true|true|super-billing-custom|super-billing-custom-label|red,Red;blue,Blue;green,Green%1$s%2$sAvailable field options:%3$s%1$s%2$sname%3$s - the field name%1$s%2$svalue%3$s - the field value ({tags} can be used here)%1$s%2$slabel%3$s – label for the input field%1$s%2$splaceholder%3$s – placeholder for the input%1$s%2$stype%3$s – type of field (text, textarea, password, select)%1$s%2$ssection%3$s - billing, shipping, account, order%1$s%2$srequired%3$s – true or false, whether or not the field is require%1$s%2$sclear%3$s – true or false, applies a clear fix to the field/label%1$s%2$sclass%3$s – class for the input%1$s%2$slabel_class%3$s – class for the label element%1$s%2$soptions%3$s – for select boxes, array of options (key => value pairs)', 'super-forms' ), '<br />', '<strong>', '</strong>' ),
                        'desc' => esc_html__( 'Leave blank for no custom fields', 'super-forms' ),
                        'type' => 'textarea',
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_fields', $settings['settings'], "" ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true'
                    ),
                    'woocommerce_checkout_fields_skip_empty' => array(
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_checkout_fields_skip_empty', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Only add custom field if field exists in form and not conditionally hidden', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),

                    'woocommerce_redirect' => array(
                        'name' => esc_html__( 'Redirect to Checkout page or Shopping Cart?', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_redirect', $settings['settings'], 'checkout' ),
                        'type' => 'select',
                        'values' => array(
                            'checkout' => esc_html__( 'Checkout page (default)', 'super-forms' ),
                            'cart' => esc_html__( 'Shopping Cart', 'super-forms' ),
                            'none' => esc_html__( 'None (use the form redirect)', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),

                    // @since 1.3.8 - option to send email after payment completed
                    'woocommerce_completed_email' => array(
                        'name' => esc_html__( 'Send email after order completed', 'super-forms' ),
                        'label' => esc_html__( 'Note: this will only work if you save a contact entry', 'super-forms' ),
                        'default' => SUPER_Settings::get_value( 0, 'woocommerce_completed_email', $settings['settings'], '' ),
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Send email after order completed', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'woocommerce_checkout',
                        'filter_value' => 'true',
                    ),
                    
                )
            );

            // @since 1.3.8 - option to send email after payment completed
            $fields = $array['confirmation_email_settings']['fields'];
            $new_fields = array();
            foreach($fields as $k => $v){
                if($k=='confirm'){
                    unset($fields[$k]);
                    continue;
                }
                if( !empty($v['parent']) ) {
                    if($v['parent']=='confirm'){
                        $v['parent'] = 'woocommerce_completed_email';
                        $v['filter_value'] = 'true';
                    }else{
                        $v['parent'] = str_replace('confirm', 'woocommerce_completed', $v['parent']);
                    }
                }
                unset($fields[$k]);
                $k = str_replace('confirm', 'woocommerce_completed', $k);
                $v['default'] = SUPER_Settings::get_value( 0, $k, $settings['settings'], $v['default'] );
                $new_fields[$k] = $v;
            }
            $array['woocommerce_checkout']['fields'] = array_merge($array['woocommerce_checkout']['fields'], $new_fields);
            $array['woocommerce_checkout']['fields']['woocommerce_completed_attachments'] = array(
                'name' => esc_html__( 'Attachments for woocommerce completed emails:', 'super-forms' ),
                'label' => esc_html__( 'Upload a file to send as attachment', 'super-forms' ),
                'default' => SUPER_Settings::get_value( 0, 'woocommerce_completed_attachments', $settings['settings'], '' ),
                'type' => 'file',
                'multiple' => 'true',
                'filter' => true,
                'parent' => 'woocommerce_completed_email',
                'filter_value' => 'true',
            );

            if ( class_exists( 'SUPER_Frontend_Posting' ) ) {
                $array['woocommerce_checkout']['fields']['woocommerce_post_status'] = array(
                    'name' => esc_html__( 'Post status after payment complete', 'super-forms' ),
                    'label' => esc_html__( 'Only used for Front-end posting', 'super-forms' ),
                    'default' => SUPER_Settings::get_value( 0, 'woocommerce_post_status', $settings['settings'], 'publish' ),
                    'type' => 'select',
                    'values' => array(
                        'publish' => esc_html__( 'Publish (default)', 'super-forms' ),
                        'future' => esc_html__( 'Future', 'super-forms' ),
                        'draft' => esc_html__( 'Draft', 'super-forms' ),
                        'pending' => esc_html__( 'Pending', 'super-forms' ),
                        'private' => esc_html__( 'Private', 'super-forms' ),
                        'trash' => esc_html__( 'Trash', 'super-forms' ),
                        'auto-draft' => esc_html__( 'Auto-Draft', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'woocommerce_checkout',
                    'filter_value' => 'true',
                );
            }

            if ( class_exists( 'SUPER_Register_Login' ) ) {
                global $wp_roles;
                $all_roles = $wp_roles->roles;
                $editable_roles = apply_filters( 'editable_roles', $all_roles );
                $roles = array();
                foreach( $editable_roles as $k => $v ) {
                    $roles[$k] = $v['name'];
                }
                $array['woocommerce_checkout']['fields']['woocommerce_signup_status'] = array(
                    'name' => esc_html__( 'Registered user login status after payment complete', 'super-forms' ),
                    'label' => esc_html__( 'Only used for Register & Login add-on', 'super-forms' ),
                    'default' => SUPER_Settings::get_value( 0, 'woocommerce_signup_status', $settings['settings'], 'active' ),
                    'type' => 'select',
                    'values' => array(
                        'active' => esc_html__( 'Active (default)', 'super-forms' ),
                        'pending' => esc_html__( 'Pending', 'super-forms' ),
                        'blocked' => esc_html__( 'Blocked', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'woocommerce_checkout',
                    'filter_value' => 'true',
                );
				$array['woocommerce_checkout']['fields']['woocommerce_completed_user_role'] = array(
					'name' => esc_html__( 'Change user role after payment complete', 'super-forms' ),
					'label' => esc_html__( 'Only used for Register & Login add-on', 'super-forms' ),
					'default' => SUPER_Settings::get_value(0, 'woocommerce_completed_user_role', $settings['settings'], 'active' ),
					'type' => 'select',
					'values' => array_merge($roles, array('' => esc_html__( 'Do not change role', 'super-forms' ))),
					'filter' => true,
					'parent' => 'woocommerce_checkout',
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
if(!function_exists('SUPER_WooCommerce')){
    function SUPER_WooCommerce() {
        return SUPER_WooCommerce::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_WooCommerce'] = SUPER_WooCommerce();
}