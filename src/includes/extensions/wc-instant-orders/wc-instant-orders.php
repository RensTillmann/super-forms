<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!class_exists('SUPER_WC_Instant_Orders')) :

    /**
     * Main SUPER_WC_Instant_Orders Class
     *
     * @class SUPER_WC_Instant_Orders
     * @version 1.0.0
     */
    final class SUPER_WC_Instant_Orders {
    
        /**
         * @var string
         *
         *  @since      1.0.0
        */
        public $add_on_slug = 'woocommerce';
        public $add_on_name = 'WooCommerce';


        /**
         * @var SUPER_WC_Instant_Orders The single instance of the class
         *
         *  @since      1.0.0
        */
        protected static $_instance = null;

        
        /**
         * Main SUPER_WC_Instant_Orders Instance
         *
         * Ensures only one instance of SUPER_WC_Instant_Orders is loaded or can be loaded.
         *
         * @static
         * @see SUPER_WC_Instant_Orders()
         * @return SUPER_WC_Instant_Orders - Main instance
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
         * SUPER_WC_Instant_Orders Constructor.
         *
         *  @since      1.0.0
        */
        public function __construct(){
            $this->includes();
            $this->init_hooks();
            do_action('super_woocommerce_loaded');
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
            add_action( 'super_before_redirect_action', array( $this, 'redirect_to_woocommerce_order' ) );      
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_create_form_tabs', array( $this, 'add_tab' ), 5, 1 );
                add_action( 'super_create_form_woocommerce_tab', array( $this, 'add_tab_content' ) );
                add_action( 'after_contact_entry_metabox_hook', array( $this, 'add_transaction_link' ), 0 );
            }
        }
        public static function add_tab($tabs){
            $tabs['woocommerce'] = 'WooCommerce';
            return $tabs;
        }
        // tmp public static function get_value($array, $keyPath, $iv){
        // tmp     // tmp if($iv!==null){
        // tmp     // tmp     echo 'd.';
        // tmp     // tmp     $name = explode('.', $keyPath);
        // tmp     // tmp     $name = end($name);
        // tmp     // tmp     echo '--'.$keyPath;
        // tmp     // tmp     echo '--'.$name;
        // tmp     // tmp     if(isset($iv[$name])){
        // tmp     // tmp         echo 'xxx';
        // tmp     // tmp         return $iv[$name];
        // tmp     // tmp     }
        // tmp     // tmp }
        // tmp     // tmp echo 'e.';
        // tmp     // tmp $value = $array;
        // tmp     // tmp echo SUPER_Common::safe_json_encode($value);
        // tmp     // tmp $keys = explode('.', $keyPath);
        // tmp     // tmp echo SUPER_Common::safe_json_encode($keys);
        // tmp     // tmp foreach ($keys as $key) {
        // tmp     // tmp     if (isset($value[$key])) {
        // tmp     // tmp         $value = $value[$key];
        // tmp     // tmp     } else {
        // tmp     // tmp         return null;
        // tmp     // tmp     }
        // tmp     // tmp }
        // tmp     // tmp return $value;
        // tmp }
        public static function add_tab_content($atts){
            $slug = SUPER_WC_Instant_Orders()->add_on_slug;
            // $s = self::get_default_woocommerce_settings($atts);
            $form_id = $atts['form_id'];
            $version = $atts['version'];
            $settings = $atts['settings'];
            $s = $atts['settings'];

            $statuses = SUPER_Settings::get_entry_statuses();
            if(!isset($statuses['delete'])) $statuses['delete'] = 'Delete';
            $entryStatusesCode = '';
            foreach($statuses as $k => $v) {
                if($k==='') continue;
                if($entryStatusesCode!=='') $entryStatusesCode .= ', ';
                $entryStatusesCode .= '<code>'.$k.'</code>';
            }

            $postStatusesCode = '';
            $statuses = array(
                'publish' => esc_html__( 'Publish (default)', 'super-forms' ),
                'future' => esc_html__( 'Future', 'super-forms' ),
                'draft' => esc_html__( 'Draft', 'super-forms' ),
                'pending' => esc_html__( 'Pending', 'super-forms' ),
                'private' => esc_html__( 'Private', 'super-forms' ),
                'trash' => esc_html__( 'Trash', 'super-forms' ),
                'auto-draft' => esc_html__( 'Auto-Draft', 'super-forms' ),
                'delete' => esc_html__( 'Delete', 'super-forms' )
            );
            foreach($statuses as $k => $v) {
                if($k==='') continue;
                if($postStatusesCode!=='') $postStatusesCode .= ', ';
                $postStatusesCode .= '<code>'.$k.'</code>';
            }

            global $wp_roles;
            $all_roles = $wp_roles->roles;
            $editable_roles = apply_filters( 'editable_roles', $all_roles );
            $rolesCode = '';
            foreach($editable_roles as $k => $v){
                if($rolesCode!=='') $rolesCode .= ', ';
                $rolesCode .= '<code>'.$k.'</code>';
            }

            // Enable WooCommerce Checkout & Instant Order
            $nodes = array(
                array(
                    'name' => 'checkout',
                    'type' => 'checkbox',
                    'default' => 'false',
                    'title' => esc_html__( 'Enable WooCommerce Checkout', 'super-forms' ),
                    'nodes' => array(
                        array(
                            'sub' => true, // sfui-sub-settings
                            'filter' => 'checkout;true',
                            'nodes' => array(
                                array(
                                    //'width_auto' => false, // 'sfui-width-auto'
                                    'wrap' => false,
                                    'group' => true, // sfui-setting-group
                                    'group_name' => 'checkout_conditionally',
                                    'inline' => true, // sfui-inline
                                    //'vertical' => true, // sfui-vertical
                                    'filter' => 'checkout;true',
                                    'nodes' => array(
                                        array(
                                            'name' => 'enabled',
                                            'type' => 'checkbox',
                                            'default' => 'false',
                                            'title' => esc_html__( 'Only checkout when below condition is met', 'super-forms' ),
                                            'nodes' => array(
                                                array(
                                                    'sub' => true, // sfui-sub-settings
                                                    //'group' => true, // sfui-setting-group
                                                    'inline' => true, // sfui-inline
                                                    //'vertical' => true, // sfui-vertical
                                                    'filter' => 'checkout_conditionally.enabled;true',
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'f1',
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'placeholder' => 'e.g. {tag}',
                                                        ),
                                                        array(
                                                            'name' => 'logic',
                                                            'type' => 'select', // dropdown
                                                            'options' => array(
                                                                '==' => '== Equal',
                                                                '!=' => '!= Not equal',
                                                                '??' => '?? Contains',
                                                                '!!' => '!! Not contains',
                                                                '>'  => '&gt; Greater than',
                                                                '<'  => '&lt;  Less than',
                                                                '>=' => '&gt;= Greater than or equal to',
                                                                '<=' => '&lt;= Less than or equal'
                                                            ),
                                                            'default' => '',
                                                        ),
                                                        array(
                                                            'name' => 'f2',
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'placeholder' => 'e.g. true'
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Define products', 'super-forms' ) . '<span style="margin-left:10px;color:red;">(required)</span>',
                                    'nodes' => array(
                                        array(
                                            'name' => 'products',
                                            'type' => 'repeater',
                                            'title' => esc_html__( 'Products to add to the cart/checkout', 'super-forms' ),
                                            'nodes' => array( // repeater item
                                                array(
                                                    'inline' => true,
                                                    'padding' => false,
                                                    'nodes' => array(
                                                        array(
                                                            'vertical' => true, // sfui-vertical
                                                            'name' => 'id',
                                                            'title' => 'Product ID',
                                                            'label' => 'Enter the WooCommerce product ID',
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'placeholder' => 'e.g. {product_id}'
                                                        ),
                                                        array(
                                                            'vertical' => true, // sfui-vertical
                                                            'name' => 'qty',
                                                            'title' => 'Cart quantity',
                                                            'label' => 'How many items to add to the cart',
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'placeholder' => 'e.g. {item_quantity}'
                                                        ),
                                                        array(
                                                            'vertical' => true, // sfui-vertical
                                                            'name' => 'price',
                                                            'title' => 'Dynamic price',
                                                            'label' => 'Leave blank if you do not have the Name Your Price plugin installed',
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'placeholder' => 'e.g. {dynamic_price}'
                                                        ),
                                                        array(
                                                            'vertical' => true, // sfui-vertical
                                                            'name' => 'variation',
                                                            'title' => 'Variation ID (optional)',
                                                            'label' => 'If a product has variations, you can enter the variation ID here',
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'placeholder' => 'e.g. {variation_id}'
                                                        )
                                                    )
                                                ),


                                                array(
                                                    'name' => 'meta',
                                                    'type' => 'checkbox',
                                                    'default' => 'false',
                                                    'title' => esc_html__( 'Product meta data (optional)', 'super-forms' ),
                                                    'nodes' => array(
                                                        array(
                                                            'sub' => true, // sfui-sub-settings
                                                            'filter' => 'meta;true',
                                                            'nodes' => array(
                                                                array(
                                                                    'name' => 'items',
                                                                    'type' => 'repeater',
                                                                    'nodes' => array( // repeater item
                                                                        array(
                                                                            'inline' => true,
                                                                            'padding' => false,
                                                                            'nodes' => array(
                                                                                array(
                                                                                    'vertical' => true, // sfui-vertical
                                                                                    'name' => 'label',
                                                                                    'title' => 'Label',
                                                                                    'label' => 'Define the meta label, for instance `Color`',
                                                                                    'type' => 'text',
                                                                                    'default' => '',
                                                                                    'placeholder' => 'e.g. Color'
                                                                                ),
                                                                                array(
                                                                                    'vertical' => true, // sfui-vertical
                                                                                    'name' => 'value',
                                                                                    'title' => 'Value',
                                                                                    'label' => 'Define the meta value, for instance `red`',
                                                                                    'type' => 'text',
                                                                                    'default' => '',
                                                                                    'placeholder' => 'e.g. red'
                                                                                ),
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Checkout fee(s)', 'super-forms' ),
                                    'nodes' => array(
                                        array(
                                            //'width_auto' => false, // 'sfui-width-auto'
                                            'wrap' => false,
                                            'group' => true, // sfui-setting-group
                                            'group_name' => 'fees',
                                            //'inline' => true, // sfui-inline
                                            'vertical' => true, // sfui-vertical
                                            'filter' => 'checkout;true',
                                            'nodes' => array(
                                                array(
                                                    'name' => 'enabled',
                                                    'type' => 'checkbox',
                                                    'default' => 'false',
                                                    'title' => esc_html__( 'Add checkout fee(s)', 'super-forms' ),
                                                ),
                                                array(
                                                    'sub' => true, // sfui-sub-settings
                                                    'filter' => 'fees.enabled;true',
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'items',
                                                            'type' => 'repeater',
                                                            'nodes' => array( // repeater item
                                                                array(
                                                                    'inline' => true,
                                                                    'padding' => false,
                                                                    'nodes' => array(
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'name',
                                                                            'title' => 'Fee name',
                                                                            'label' => 'Enter the name/label of the fee',
                                                                            'type' => 'text',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. Administration fee'
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'amount',
                                                                            'title' => 'Amount',
                                                                            'label' => 'Enter the fee amount (must be a float value)',
                                                                            'type' => 'text',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. 5.95'
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'taxable',
                                                                            'title' => 'Taxable',
                                                                            'label' => 'Accepted values: <code>true</code> or <code>false</code>',
                                                                            'type' => 'text',
                                                                            'default' => 'false',
                                                                            'placeholder' => 'e.g. false'
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'tax_class',
                                                                            'title' => 'Tax class',
                                                                            'label' => 'e.g. <code>none</code>, <code>standard</code>, <code>reduced-rate</code>, <code>zero-rate</code>',
                                                                            'type' => 'text',
                                                                            'default' => 'none',
                                                                            'placeholder' => 'e.g. none'
                                                                        )
                                                                    )
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Populate checkout fields with form data', 'super-forms' ),
                                    'nodes' => array(
                                        array(
                                            //'width_auto' => false, // 'sfui-width-auto'
                                            'wrap' => false,
                                            'group' => true, // sfui-setting-group
                                            'group_name' => 'populate',
                                            //'inline' => true, // sfui-inline
                                            'vertical' => true, // sfui-vertical
                                            'filter' => 'checkout;true',
                                            'nodes' => array(
                                                array(
                                                    'name' => 'enabled',
                                                    'type' => 'checkbox',
                                                    'default' => 'false',
                                                    'title' => esc_html__( 'Populate checkout fields with form data', 'super-forms' ),
                                                ),
                                                array(
                                                    'sub' => true, // sfui-sub-settings
                                                    'filter' => 'populate.enabled;true',
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'items',
                                                            'type' => 'repeater',
                                                            'nodes' => array( // repeater item
                                                                array(
                                                                    'inline' => true,
                                                                    'padding' => false,
                                                                    'nodes' => array(
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'name',
                                                                            'title' => 'Checkout field name',
                                                                            'subline' => 'Enter the field name of this checkout field. Available field names: <code>billing_country</code>, <code>shipping_country</code>, <code>billing_first_name</code>, <code>billing_last_name</code>, <code>billing_company</code>, <code>billing_country</code>, <code>billing_address_1</code>, <code>billing_address_2</code>, <code>billing_postcode</code>, <code>billing_city</code>, <code>billing_state</code>, <code>billing_phone</code>, <code>billing_email</code>, <code>order_comment</code>',
                                                                            'type' => 'text',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. billing_first_name'
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'value',
                                                                            'title' => 'Value',
                                                                            'subline' => 'The value to set the field to',
                                                                            'type' => 'text',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. {first_name}'
                                                                        )
                                                                    )
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Custom checkout fields', 'super-forms' ),
                                    'nodes' => array(
                                        array(
                                            //'width_auto' => false, // 'sfui-width-auto'
                                            'wrap' => false,
                                            'group' => true, // sfui-setting-group
                                            'group_name' => 'fields',
                                            //'inline' => true, // sfui-inline
                                            'vertical' => true, // sfui-vertical
                                            'filter' => 'checkout;true',
                                            'nodes' => array(
                                                array(
                                                    'name' => 'enabled',
                                                    'type' => 'checkbox',
                                                    'default' => 'false',
                                                    'title' => esc_html__( 'Add custom checkout field(s)', 'super-forms' ),
                                                ),
                                                array(
                                                    'sub' => true, // sfui-sub-settings
                                                    'filter' => 'fields.enabled;true',
                                                    'nodes' => array(
                                                        array(
                                                            'name' => 'items',
                                                            'type' => 'repeater',
                                                            'nodes' => array( // repeater item
                                                                array(
                                                                    'inline' => true,
                                                                    'padding' => false,
                                                                    'nodes' => array(
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'type',
                                                                            'title' => 'Type',
                                                                            'type' => 'select',
                                                                            'options' => array(
                                                                                'text' => 'Text',
                                                                                'textarea' => 'Textarea',
                                                                                'password' => 'Password',
                                                                                'select' => 'Select (dropdown)'
                                                                            ),
                                                                            'default' => 'text'
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'type' => 'text',
                                                                            'name' => 'name',
                                                                            'title' => 'Name',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. '
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'label',
                                                                            'title' => 'Label',
                                                                            'type' => 'text',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. '
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'placeholder',
                                                                            'title' => 'Placeholder',
                                                                            'type' => 'text',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. '
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'value',
                                                                            'title' => 'Value',
                                                                            'type' => 'text',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. {tag}'
                                                                        ),
                                                                    )
                                                                ),
                                                                array(
                                                                    'inline' => true,
                                                                    'padding' => false,
                                                                    'nodes' => array(
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'required',
                                                                            'title' => 'Required',
                                                                            'label' => 'Accepted values: <code>true</code> or <code>false</code>',
                                                                            'type' => 'text',
                                                                            'default' => 'true',
                                                                            'placeholder' => 'e.g. true'
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'section',
                                                                            'title' => 'Section',
                                                                            'label' => 'Choose where to put the field',
                                                                            'type' => 'select',
                                                                            'options' => array(
                                                                                'billing' => 'Billing',
                                                                                'shipping' => 'Shipping',
                                                                                'account' => 'Account',
                                                                                'order' => 'Order'
                                                                            ),
                                                                            'default' => 'billing'
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'clear',
                                                                            'title' => 'Clear',
                                                                            'label' => 'Puts the field on a single row. Accepted values: <code>true</code> or <code>false</code>',
                                                                            'type' => 'text',
                                                                            'default' => 'true',
                                                                            'placeholder' => 'e.g. true'
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'class',
                                                                            'title' => 'Class',
                                                                            'label' => 'Apply a custom class name for the input',
                                                                            'type' => 'text',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. my-custom-input-classname'
                                                                        ),
                                                                        array(
                                                                            'vertical' => true, // sfui-vertical
                                                                            'name' => 'label_class',
                                                                            'title' => 'Label class',
                                                                            'label' => 'Apply a custom class name for the label',
                                                                            'type' => 'text',
                                                                            'type' => 'text',
                                                                            'default' => '',
                                                                            'placeholder' => 'e.g. my-custom-label-classname'
                                                                        ),
                                                                    )
                                                                ),
                                                                array(
                                                                    'inline' => true,
                                                                    'padding' => false,
                                                                    'nodes' => array(
                                                                        array(
                                                                            'name' => 'skip',
                                                                            'type' => 'checkbox',
                                                                            'default' => 'false',
                                                                            'title' => esc_html__( 'Only add if field is not conditionally hidden', 'super-forms' )
                                                                        ),
                                                                    )
                                                                ),
                                                                // Dropdown items
                                                                array(
                                                                    'wrap' => false,
                                                                    'group' => true, // sfui-setting-group
                                                                    'group_name' => '',
                                                                    'inline' => true, // sfui-inline
                                                                    //'vertical' => true, // sfui-vertical
                                                                    //'filter' => 'type;select',
                                                                    'filter' => 'fields.type;select',
                                                                    'nodes' => array(
                                                                        array(
                                                                            'name' => 'options',
                                                                            'type' => 'repeater',
                                                                            'title' => esc_html__( 'Dropdown items', 'super-forms' ),
                                                                            'nodes' => array( // repeater item
                                                                                array(
                                                                                    'inline' => true,
                                                                                    'padding' => false,
                                                                                    'nodes' => array(
                                                                                        array(
                                                                                            'vertical' => true, // sfui-vertical
                                                                                            'name' => 'label',
                                                                                            'title' => 'Item label',
                                                                                            'type' => 'text',
                                                                                            'default' => '',
                                                                                            'placeholder' => 'e.g. Red'
                                                                                        ),
                                                                                        array(
                                                                                            'vertical' => true, // sfui-vertical
                                                                                            'name' => 'value',
                                                                                            'title' => 'Item value',
                                                                                            'type' => 'text',
                                                                                            'default' => '',
                                                                                            'placeholder' => 'e.g. red'
                                                                                        )
                                                                                    )
                                                                                )
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            )
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                                // Update entry status when WooCommerce status changes
                                array(
                                    'name' => 'entry_status',
                                    'type' => 'repeater',
                                    'toggle' => true,
                                    'title' => esc_html__( 'Update entry status when WooCommerce Order status changes', 'super-forms' ),
                                    'nodes' => array( // repeater item
                                        array(
                                            'inline' => true,
                                            'padding' => false,
                                            'nodes' => array(
                                                array(
                                                    'vertical' => true, // sfui-vertical
                                                    'name' => 'order',
                                                    'title' => 'Order status',
                                                    'subline' => 'Accepted values: <code>pending</code>, <code>processing</code>, <code>on-hold</code>, <code>completed</code>, <code>cancelled</code>, <code>refunded</code>, <code>failed</code>',
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'placeholder' => 'e.g. completed'
                                                ),
                                                array(
                                                    'vertical' => true, // sfui-vertical
                                                    'name' => 'entry',
                                                    'title' => 'Entry status',
                                                    'subline' => esc_html__( 'Leave blank or delete to keep the current entry status unchanged. Accepted values are:', 'super-forms' ). ' ' . $entryStatusesCode . '. ' . sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' ),
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'placeholder' => 'e.g. completed'
                                                ),
                                            )
                                        )
                                    )
                                ),
                                // Update post status when WooCommerce status changes
                                array(
                                    'name' => 'post_status',
                                    'type' => 'repeater',
                                    'toggle' => true,
                                    'title' => esc_html__( 'Update post status when WooCommerce Order status changes', 'super-forms' ),
                                    'nodes' => array( // repeater item
                                        array(
                                            'inline' => true,
                                            'padding' => false,
                                            'nodes' => array(
                                                array(
                                                    'vertical' => true, // sfui-vertical
                                                    'name' => 'order',
                                                    'title' => 'Order status',
                                                    'subline' => 'Accepted values: <code>pending</code>, <code>processing</code>, <code>on-hold</code>, <code>completed</code>, <code>cancelled</code>, <code>refunded</code>, <code>failed</code>',
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'placeholder' => 'e.g. completed'
                                                ),
                                                array(
                                                    'vertical' => true, // sfui-vertical
                                                    'name' => 'post',
                                                    'title' => 'Post status',
                                                    'subline' => esc_html__( 'Leave blank or delete to keep the current post status unchanged. Accepted values are:', 'super-forms' ). ' ' . $postStatusesCode . '.',
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'placeholder' => 'e.g. publish'
                                                ),
                                            )
                                        )
                                    )
                                ),
                                // Update login status when WooCommerce status changes
                                array(
                                    'name' => 'login_status',
                                    'type' => 'repeater',
                                    'toggle' => true,
                                    'title' => esc_html__( 'Update user login status when WooCommerce Order status changes', 'super-forms' ),
                                    'nodes' => array( // repeater item
                                        array(
                                            'inline' => true,
                                            'padding' => false,
                                            'nodes' => array(
                                                array(
                                                    'vertical' => true, // sfui-vertical
                                                    'name' => 'order',
                                                    'title' => 'Order status',
                                                    'subline' => 'Accepted values: <code>pending</code>, <code>processing</code>, <code>on-hold</code>, <code>completed</code>, <code>cancelled</code>, <code>refunded</code>, <code>failed</code>',
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'placeholder' => 'e.g. completed'
                                                ),
                                                array(
                                                    'vertical' => true, // sfui-vertical
                                                    'name' => 'login_status',
                                                    'title' => 'User login status',
                                                    'subline' => esc_html__( 'Leave blank or delete to keep the current user status unchanged. Accepted values are:', 'super-forms' ). ' <code>active</code>, <code>pending</code>, <code>payment_required</code>, <code>blocked</code>.',
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'placeholder' => 'e.g. active'
                                                ),
                                            )
                                        )
                                    )
                                ),

                                // Update user role when WooCommerce status changes
                                array(
                                    'name' => 'user_role',
                                    'type' => 'repeater',
                                    'toggle' => true,
                                    'title' => esc_html__( 'Update user role when WooCommerce Order status changes', 'super-forms' ),
                                    'nodes' => array( // repeater item
                                        array(
                                            'inline' => true,
                                            'padding' => false,
                                            'nodes' => array(
                                                array(
                                                    'vertical' => true, // sfui-vertical
                                                    'name' => 'order',
                                                    'title' => 'Order status',
                                                    'subline' => 'Accepted values: <code>pending</code>, <code>processing</code>, <code>on-hold</code>, <code>completed</code>, <code>cancelled</code>, <code>refunded</code>, <code>failed</code>',
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'placeholder' => 'e.g. completed'
                                                ),
                                                array(
                                                    'vertical' => true, // sfui-vertical
                                                    'name' => 'user_role',
                                                    'title' => 'User role',
                                                    'subline' => esc_html__( 'Leave blank or delete to keep the current user role unchanged. Accepted values are:', 'super-forms' ). ' ' . $rolesCode . '.',
                                                    'type' => 'text',
                                                    'default' => '',
                                                    'placeholder' => 'e.g. subscriber'
                                                ),
                                            )
                                        )
                                    )
                                ),

                                array(
                                    'toggle' => true,
                                    'title' => esc_html__( 'Send email after payment completed', 'super-forms' ),
                                    'nodes' => array(
                                        array(
                                            'notice' => 'info', // hint/info
                                            'content' => 'To send an email after a WooCommerce order is completed, you can create a new action under the Triggers tab.',
                                            'filter' => 'checkout;true'
                                            //'width_auto' => false, // 'sfui-width-auto'
                                            //'wrap' => false,
                                            //'group' => true, // sfui-setting-group
                                            //'group_name' => 'emails',
                                            //'inline' => true, // sfui-inline
                                            //'vertical' => true, // sfui-vertical
                                            // tmp 'nodes' => array(
                                            // tmp     array(
                                            // tmp         'name' => 'status',
                                            // tmp         'type' => 'text',
                                            // tmp         'default' => '',
                                            // tmp         'title' => esc_html__( 'When order status changes to', 'super-forms' ),
                                            // tmp     ),
                                            // tmp     array(
                                            // tmp         'sub' => true, // sfui-sub-settings
                                            // tmp         'filter' => 'status;completed',
                                            // tmp         'nodes' => array(
                                            // tmp             array(
                                            // tmp                 'name' => 'to',
                                            // tmp                 'type' => 'text',
                                            // tmp                 'default' => '',
                                            // tmp                 'title' => esc_html__( 'To:', 'super-forms' ),
                                            // tmp             ),
                                            // tmp         )
                                            // tmp     )
                                            // tmp )
                                        )
                                    )
                                ),


                                array(
                                    'vertical' => true, // sfui-vertical
                                    'name' => 'redirect',
                                    'title' => 'Redirect to:',
                                    'subline' => 'Redirect to Checkout, Cart or use form redirect. Accepted values: <code>checkout</code>, <code>cart</code> or <code>none</code>',
                                    'type' => 'text',
                                    'default' => 'checkout'
                                ),
                                array(
                                    'inline' => true, // sfui-inline
                                    'name' => 'empty_cart',
                                    'type' => 'checkbox',
                                    'default' => 'false',
                                    'title' => esc_html__( 'Empty cart before adding products', 'super-forms' ),
                                ),
                                array(
                                    'inline' => true, // sfui-inline
                                    'name' => 'remove_fees',
                                    'type' => 'checkbox',
                                    'default' => 'false',
                                    'title' => esc_html__( 'Remove/clear fees before redirecting to checkout/cart', 'super-forms' ),
                                ),
                                array(
                                    'inline' => true, // sfui-inline
                                    'name' => 'remove_coupons',
                                    'type' => 'checkbox',
                                    'default' => 'false',
                                    'title' => esc_html__( 'Remove/clear coupons before redirecting to checkout/cart', 'super-forms' ),
                                ),
                                array(
                                    'vertical' => true, // sfui-vertical
                                    'name' => 'coupon',
                                    'type' => 'text',
                                    'placeholder' => 'e.g. {coupon_code}',
                                    'default' => '',
                                    'title' => esc_html__( 'Apply a coupon code', 'super-forms' )
                                ),

                            )
                        )
                    )
                ),
                array(
                    'name' => 'instant',
                    'type' => 'checkbox',
                    'default' => 'false',
                    'title' => esc_html__( 'Enable WooCommerce Instant Order', 'super-forms' ),
                    'nodes' => array(
                        array(
                            'sub' => true, // sfui-sub-settings
                            'filter' => 'instant;true',
                            'nodes' => array(
                                array(
                                    'wrap' => false,
                                    'group' => true, // sfui-setting-group
                                    'group_name' => 'instant_conditionally',
                                    'inline' => true, // sfui-inline
                                    'filter' => 'instant;true',
                                    'nodes' => array(
                                        array(
                                            'name' => 'enabled',
                                            'type' => 'checkbox',
                                            'default' => 'false',
                                            'title' => esc_html__( 'Only create the order when below condition is met', 'super-forms' ),
                                            'nodes' => array(
                                                array(
                                                    'inline' => true, // sfui-inline
                                                    'filter' => 'instant_conditionally.enabled;true',
                                                    'nodes' => array(
                                                        array(
                                                            'wrap' => false,
                                                            'name' => 'f1',
                                                            'inline' => true,
                                                            'padding' => false,
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'placeholder' => 'e.g. {tag}',
                                                        ),
                                                        array(
                                                            'wrap' => false,
                                                            'name' => 'logic',
                                                            'padding' => false,
                                                            'type' => 'select', // dropdown
                                                            'options' => array(
                                                                '==' => '== Equal',
                                                                '!=' => '!= Not equal',
                                                                '??' => '?? Contains',
                                                                '!!' => '!! Not contains',
                                                                '>'  => '&gt; Greater than',
                                                                '<'  => '&lt;  Less than',
                                                                '>=' => '&gt;= Greater than or equal to',
                                                                '<=' => '&lt;= Less than or equal'
                                                            ),
                                                            'default' => '',
                                                        ),
                                                        array(
                                                            'wrap' => false,
                                                            'name' => 'f2',
                                                            'inline' => true,
                                                            'padding' => false,
                                                            'type' => 'text',
                                                            'default' => '',
                                                            'placeholder' => 'e.g. true'
                                                        )
                                                    )
                                                )
                                            )
                                        )
                                    )
                                ),
                            )
                        )
                    )
                ),
            );
            $prefix = array();
            if(!isset($s['_woocommerce'])) $s['_woocommerce'] = array();
            SUPER_UI::loop_over_tab_setting_nodes($s['_woocommerce'], $nodes, $prefix);
        }
        // Get default listing settings
        public static function get_default_woocommerce_settings($atts=array(), $s=array()) {
            //// Get form settings
            // tmp if(empty($s['checkout'])) $s['checkout'] = 'false';
            // tmp if(empty($s['instant'])) $s['instant'] = 'false';
            // tmp if(empty($s['checkout_conditionally'])) $s['checkout_conditionally'] = array(
            // tmp     'enabled' => 'false', 
            // tmp     'f1' => '', 
            // tmp     'f2' => '', 
            // tmp     'logic' => ''
            // tmp );
            // tmp if(empty($s['instant_conditionally'])) $s['instant_conditionally'] = array(
            // tmp     'enabled' => 'false', 
            // tmp     'f1' => '', 
            // tmp     'f2' => '', 
            // tmp     'logic' => ''
            // tmp );
            // tmp $s = apply_filters( 'super_woocommerce_default_settings_filter', $s );
            // tmp if(isset($settings) && isset($settings['_wc'])){
            // tmp     $s = array_merge($s, $settings['_wc']);
            // tmp }
            return $s;
        }


        /**
         * Create Stripe Payment Intent
         *
         *  @since      1.0.0
         */
        public static function redirect_to_woocommerce_order($x){
            extract( shortcode_atts( array(
                'sfsi'=>array(),
                'form_id'=>0,
                'sfs_uid'=>'',
                'post'=>array(), 
                'data'=>array(), 
                'settings'=>array(), 
                'entry_id'=>0, 
                'attachments'=>array()
            ), $x));
            if(empty($settings['_wc'])) return true;
            $s = $settings['_wc'];
            // Skip if Stripe checkout is not enabled
            if($s['enabled']!=='true') return true;
            // If conditional check is enabled
            $checkout = true;
            $c = $s['conditions'];
            if($c['enabled']==='true' && $c['logic']!==''){
                $logic = $c['logic'];
                $f1 = SUPER_Common::email_tags($c['f1'], $data, $settings);
                $f2 = SUPER_Common::email_tags($c['f2'], $data, $settings);
                $checkout = self::conditional_compare_check($f1, $logic, $f2);
            }
            if($checkout===false) return true;
            $mode = SUPER_Common::email_tags( $s['mode'], $data, $settings );
            $customer_email = (isset($s['customer_email']) ? SUPER_Common::email_tags( $s['customer_email'], $data, $settings ) : '');
            $customer = '';
            if($s['use_logged_in_email']==='true'){
                // Check if user is logged in, or a newly user was registerd
                $user_id = get_current_user_id();
                error_log('user_id: '.$user_id);
                error_log('Entry ID wc order redirect: '.$sfsi['entry_id']);
                error_log('User ID wc order redirect: '.$sfsi['user_id']);
                if(!empty($sfsi['user_id'])){
                    $user_id = $sfsi['user_id'];
                }
                $email = '';
                if(!empty($user_id)){
                    $email = SUPER_Common::get_user_email($user_id);
                }
                error_log('user_email: '.$email);
                error_log('user_id after: '.$user_id);
                error_log('user_email after: '.$email);
                $sfsi['user_id'] = $user_id;
            }
            $description = (isset($s['subscription_data']['description']) ? SUPER_Common::email_tags( $s['subscription_data']['description'], $data, $settings ) : '');
            $trial_period_days = (isset($s['subscription_data']['trial_period_days']) ? SUPER_Common::email_tags( $s['subscription_data']['trial_period_days'], $data, $settings ) : '');
            $payment_methods = (isset($s['payment_method_types']) ? SUPER_Common::email_tags( $s['payment_method_types'], $data, $settings ) : '');
            $payment_methods = explode(',', str_replace(' ', '', $payment_methods));
            $metadata = array(
                'sf_id' => $form_id,
                'sf_entry' => $entry_id,
                'sf_user' => (isset($sfsi['user_id']) ? $sfsi['user_id'] : 0),
                'sf_post' => (isset($sfsi['created_post']) ? $sfsi['created_post'] : 0),
                'sfsi_id' => $sfs_uid
            );
            error_log('custom metadata for woocommerce order: ' . SUPER_Common::safe_json_encode($metadata));
            $sfsi = get_option( '_sfsi_' . $sfs_uid, array() );
            error_log('sfsi: '.SUPER_Common::safe_json_encode($sfsi));
            $sfsi['entry_id'] = $entry_id;
            error_log('14');update_option('_sfsi_' . $sfs_uid, $sfsi );

            $line_items = array();
            foreach($s['line_items'] as $k => $v){
                $i=0;
                $ov = $v;
                $p = SUPER_Common::get_tag_parts($v['quantity'], $i);
                $op = $p;
                $v['quantity'] = SUPER_Common::email_tags( $v['quantity'], $data, $settings );
                $p = SUPER_Common::get_tag_parts($v['price'], $i);
                $v['price'] = SUPER_Common::email_tags( $v['price'], $data, $settings );
                if($v['type'] === 'price'){
                    if(trim($v['price'])===''){
                        SUPER_Common::output_message( array( 
                            'msg' => esc_html__( 'Please provide the price/plan ID for your line item', 'super-forms' )
                        ));
                    }
                }
                if($v['type'] === 'price_data'){
                    // Set correct unit amount
                    // Prices require an `unit_amount` or `unit_amount_decimal` parameter to be set.
                    $p = SUPER_Common::get_tag_parts($v['price_data']['unit_amount_decimal'], $i);
                    //'unit_amount_decimal' => '10.95', // amount representing how much to charge
                    $v['price_data']['unit_amount_decimal'] = SUPER_Common::email_tags( $p['new'], $data, $settings );
                    $v['price_data']['unit_amount_decimal'] = floatval($v['price_data']['unit_amount_decimal']) * 100;
                    $v['price_data']['tax_behavior'] = $v['price_data']['tax_behavior'];
                    if($v['price_data']['type'] === 'product_data'){
                        // Unset empty product values
                        if(trim($v['price_data']['product_data']['name'])===''){
                            $v['price_data']['product_data']['name'] = '{product_name}';
                        }
                        $p = SUPER_Common::get_tag_parts($v['price_data']['product_data']['name'], $i);
                        $v['price_data']['product_data']['name'] = SUPER_Common::email_tags( $p['new'], $data, $settings );

                        $p = SUPER_Common::get_tag_parts($v['price_data']['product_data']['description'], $i);
                        $v['price_data']['product_data']['description'] = SUPER_Common::email_tags( $p['new'], $data, $settings );

                        $p = SUPER_Common::get_tag_parts($v['price_data']['product_data']['tax_code'], $i);
                        $v['price_data']['product_data']['tax_code'] = SUPER_Common::email_tags( $p['new'], $data, $settings );
                    }
                }
                if($v['custom_tax_rate']==='true'){
                    $v['tax_rates'] = explode(',', str_replace(' ', '', trim($v['tax_rates'])));
                }
                $line_items[] = $v;

                $i=2;
                while( isset( $data[$op['name'] . '_' . ($i)]) ) {
                    $p = SUPER_Common::get_tag_parts($ov['quantity'], $i);
                    $v['quantity'] = SUPER_Common::email_tags( $p['new'], $data, $settings );
                    $p = SUPER_Common::get_tag_parts($ov['price'], $i);
                    $v['price'] = SUPER_Common::email_tags( $p['new'], $data, $settings );
                    if($ov['type'] === 'price'){
                        if(trim($ov['price'])===''){
                            SUPER_Common::output_message( array( 
                                'msg' => esc_html__( 'Please provide the price/plan ID for your line item', 'super-forms' )
                            ));
                        }
                    }
                    if($ov['type'] === 'price_data'){
                        // Set correct unit amount
                        // Prices require an `unit_amount` or `unit_amount_decimal` parameter to be set.
                        $p = SUPER_Common::get_tag_parts($ov['price_data']['unit_amount_decimal'], $i);
                        $v['price_data']['unit_amount_decimal'] = SUPER_Common::email_tags( $p['new'], $data, $settings );
                        $v['price_data']['unit_amount_decimal'] = floatval($v['price_data']['unit_amount_decimal']) * 100;
                        $v['price_data']['tax_behavior'] = $ov['price_data']['tax_behavior'];
                        if($ov['price_data']['type'] === 'product_data'){
                            // Unset empty product values
                            if(trim($ov['price_data']['product_data']['name'])===''){
                                $v['price_data']['product_data']['name'] = '{product_name}';
                            }
                            $p = SUPER_Common::get_tag_parts($ov['price_data']['product_data']['name'], $i);
                            $v['price_data']['product_data']['name'] = SUPER_Common::email_tags( $p['new'], $data, $settings );

                            $p = SUPER_Common::get_tag_parts($ov['price_data']['product_data']['description'], $i);
                            $v['price_data']['product_data']['description'] = SUPER_Common::email_tags( $p['new'], $data, $settings );

                            $p = SUPER_Common::get_tag_parts($ov['price_data']['product_data']['tax_code'], $i);
                            $v['price_data']['product_data']['tax_code'] = SUPER_Common::email_tags( $p['new'], $data, $settings );
                        }
                    }
                    if($ov['custom_tax_rate']==='true'){
                        $v['tax_rates'] = explode(',', str_replace(' ', '', trim($ov['tax_rates'])));
                    }
                    $line_items[] = $v;
                    $i++;
                }
            }
            foreach($line_items as $k => $v){
                if($v['type'] === 'price'){
                    unset($line_items[$k]['price_data']);
                }
                if($v['type'] === 'price_data'){
                    unset($line_items[$k]['price']);
                    if($v['price_data']['recurring']['interval']==='none'){
                        unset($line_items[$k]['price_data']['recurring']);
                    }
                    if($v['price_data']['type'] === 'product'){
                        unset($line_items[$k]['price_data']['product_data']);
                    }
                    if($v['price_data']['type'] === 'product_data'){
                        unset($line_items[$k]['price_data']['product']);
                        if(trim($v['price_data']['product_data']['description'])===''){
                            unset($line_items[$k]['price_data']['product_data']['description']);
                        }
                        if(trim($v['price_data']['product_data']['tax_code'])===''){
                            unset($line_items[$k]['price_data']['product_data']['tax_code']);
                        }
                    }
                }
                if($v['custom_tax_rate']==='true'){
                    $v['tax_rates'] = explode(',', str_replace(' ', '', trim($v['tax_rates'])));
                }else{
                    unset($line_items[$k]['tax_rates']);
                }
                unset($line_items[$k]['type']);
                unset($line_items[$k]['price_data']['type']);
                unset($line_items[$k]['custom_tax_rate']);
            }
            // Redirect to WC order
            SUPER_Common::output_message( array(
                'error'=>false, 
                'msg' => '', 
                'redirect' => $checkout_session->url,
                'form_id' => absint($sfsi['form_id'])
            ));
            die();
        }


        /**
         * Add the WC order link to the entry info/data page
         *
         * @since       1.0.0
         */
        public static function add_transaction_link($entry_id) {
            $order_id = get_post_meta( $entry_id, '_super_wc_order_txn_id', true );
            if(!empty($order_id)){
                ?>
                <div class="misc-pub-section">
                    <span><?php echo esc_html__('WooCommerce Order', 'super-forms' ).':'; ?> <strong><?php echo '<a target="_blank" href="' . esc_url('?id' . $order_id) . '">' . substr($order_id, 0, 15) . ' ...</a>'; ?></strong></span>
                </div>
                <?php 
            }
        }
    }
endif;


/**
 * Returns the main instance of SUPER_WC_Instant_Orders to prevent the need to use globals.
 *
 * @return SUPER_WC_Instant_Orders
 */
if(!function_exists('SUPER_WC_Instant_Orders')){
    function SUPER_WC_Instant_Orders() {
        return SUPER_WC_Instant_Orders::instance();
    }
    // Global for backwards compatibility.
    $GLOBALS['SUPER_WC_Instant_Orders'] = SUPER_WC_Instant_Orders();
}


// tmp // @TODO
// tmp // Option to add fees / discounts
// tmp if ( ! defined( 'ABSPATH' ) ) {
// tmp     exit; // Exit if accessed directly
// tmp }
// tmp 
// tmp if(!class_exists('SUPER_WC_Instant_Orders')) :
// tmp 
// tmp 
// tmp     /**
// tmp      * Main SUPER_WC_Instant_Orders Class
// tmp      *
// tmp      * @class SUPER_WC_Instant_Orders
// tmp      */
// tmp     final class SUPER_WC_Instant_Orders {
// tmp     
// tmp         
// tmp         /**
// tmp          * @var string
// tmp          *
// tmp         */
// tmp         public $add_on_slug = 'wc-instant-orders';
// tmp         public $add_on_name = 'WooCommerce Instant Orders';
// tmp 
// tmp 
// tmp         /**
// tmp          * @var SUPER_WC_Instant_Orders The single instance of the class
// tmp          *
// tmp         */
// tmp         protected static $_instance = null;
// tmp 
// tmp         
// tmp         /**
// tmp          * Main SUPER_WC_Instant_Orders Instance
// tmp          *
// tmp          * Ensures only one instance of SUPER_WC_Instant_Orders is loaded or can be loaded.
// tmp          *
// tmp          * @static
// tmp          * @see SUPER_WC_Instant_Orders()
// tmp          * @return SUPER_WC_Instant_Orders - Main instance
// tmp          *
// tmp         */
// tmp         public static function instance() {
// tmp             if(is_null( self::$_instance)){
// tmp                 self::$_instance = new self();
// tmp             }
// tmp             return self::$_instance;
// tmp         }
// tmp 
// tmp         
// tmp         /**
// tmp          * SUPER_WC_Instant_Orders Constructor.
// tmp          *
// tmp         */
// tmp         public function __construct(){
// tmp             $this->init_hooks();
// tmp             do_action('SUPER_WC_Instant_Orders_loaded');
// tmp         }
// tmp 
// tmp         
// tmp         /**
// tmp          * Define constant if not already set
// tmp          *
// tmp          * @param  string $name
// tmp          * @param  string|bool $value
// tmp          *
// tmp         */
// tmp         private function define($name, $value){
// tmp             if(!defined($name)){
// tmp                 define($name, $value);
// tmp             }
// tmp         }
// tmp 
// tmp         
// tmp         /**
// tmp          * What type of request is this?
// tmp          *
// tmp          * string $type ajax, frontend or admin
// tmp          * @return bool
// tmp          *
// tmp         */
// tmp         private function is_request($type){
// tmp             switch ($type){
// tmp                 case 'admin' :
// tmp                     return is_admin();
// tmp                 case 'ajax' :
// tmp                     return defined( 'DOING_AJAX' );
// tmp                 case 'cron' :
// tmp                     return defined( 'DOING_CRON' );
// tmp                 case 'frontend' :
// tmp                     return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
// tmp             }
// tmp         }
// tmp 
// tmp         
// tmp         /**
// tmp          * Hook into actions and filters
// tmp          *
// tmp         */
// tmp         private function init_hooks() {
// tmp             
// tmp             add_action( 'super_before_redirect_action', array( $this, 'redirect_to_wc_order' ) );      
// tmp 
// tmp             // Filters since 1.0.0
// tmp             //add_filter( 'super_redirect_url_filter', array( $this, 'redirect_to_order' ), 10, 2 );
// tmp             
// tmp             if ( $this->is_request( 'admin' ) ) {
// tmp                 
// tmp                 add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );
// tmp                 
// tmp             }
// tmp             
// tmp             if ( $this->is_request( 'ajax' ) ) {
// tmp                 //add_action( 'super_before_email_success_msg_action', array( $this, 'before_email_success_msg' ) );
// tmp             }
// tmp         }
// tmp 
// tmp 
// tmp         /**
// tmp          * Redirect to newly created order
// tmp          * 
// tmp         */
// tmp         // tmp public function redirect_to_order( $url, $attr ) {
// tmp         // tmp     // Only check for URL in the session if setting was enabled
// tmp         // tmp     // Check if option to redirect to created order is enabled in form settings
// tmp         // tmp     if( (isset($attr['settings']['wc_instant_orders_redirect'])) && ($attr['settings']['wc_instant_orders_redirect']==='order') ) {
// tmp         // tmp         // If setting was enabled, let's check if we can find the Order ID in the stored session
// tmp         // tmp         $order_id = SUPER_Common::getClientData( 'wc_instant_orders_created_order' );
// tmp         // tmp         $url = get_edit_post_link( $order_id, '' );
// tmp         // tmp         // Make sure to reset the session to clear it from the database, and so that we won't have a redirect conflict with other possible forms
// tmp         // tmp         SUPER_Common::setClientData( array( 'name'=> 'wc_instant_orders_created_order', 'value'=>false  ) );
// tmp         // tmp     }
// tmp         // tmp     return $url;
// tmp         // tmp }
// tmp 
// tmp 
// tmp         /**
// tmp          * Loop through {tags} if dynamic column is used
// tmp          *
// tmp         */
// tmp         public static function new_wc_checkout_products( $products_tags, $i, $looped, $product, $product_id, $quantity, $name, $variation_id, $subtotal, $total, $tax_class, $variation ){
// tmp             if(!in_array($i, $looped)){
// tmp                 $new_line = '';
// tmp 
// tmp                 $index = 0;
// tmp                 if(isset($product_id)){
// tmp                     // Get the product ID tag
// tmp                     if( $product[$index][0]=='{' ) { 
// tmp                         $new_line .= '{' . $product_id . '_' . $i . '}'; 
// tmp                     }else{ 
// tmp                         if(!empty($product[$index])) {
// tmp                             $new_line .= '|' . $product[$index];
// tmp                         }else{
// tmp                             $new_line .= '|0';
// tmp                         }
// tmp                     }
// tmp                 }
// tmp                 $index++;
// tmp                 if(isset($quantity)){
// tmp                     // Get the product quantity tag
// tmp                     if( $product[$index][0]=='{' ) { 
// tmp                         $new_line .= '|{' . $quantity . '_' . $i . '}'; 
// tmp                     }else{ 
// tmp                         if(!empty($product[$index])) {
// tmp                             $new_line .= '|' . $product[$index];
// tmp                         }else{
// tmp                             $new_line .= '|0';
// tmp                         }
// tmp                     }
// tmp                 }
// tmp                 $index++;
// tmp                 if(isset($name)){
// tmp                     // Get the product name tag
// tmp                     if( $product[$index][0]=='{' ) { 
// tmp                         $new_line .= '|{' . $name . '_' . $i . '}'; 
// tmp                     }else{ 
// tmp                         if(!empty($product[$index])) {
// tmp                             $new_line .= '|' . $product[$index];
// tmp                         }else{
// tmp                             $new_line .= '|';
// tmp                         }
// tmp                     }
// tmp                 }
// tmp                 $index++;
// tmp                 if(isset($variation_id)){
// tmp                     // Get the product variation ID tag
// tmp                     if( $product[$index][0]=='{' ) { 
// tmp                         $new_line .= '|{' . $variation_id . '_' . $i . '}'; 
// tmp                     }else{ 
// tmp                         if(!empty($product[$index])) {
// tmp                             $new_line .= '|' . $product[$index];
// tmp                         }else{
// tmp                             $new_line .= '|0';
// tmp                         }
// tmp                     }
// tmp                 }
// tmp                 $index++;
// tmp                 if(isset($subtotal)){
// tmp                     // Get the product price tag
// tmp                     if( $product[$index][0]=='{' ) { 
// tmp                         $new_line .= '|{' . $subtotal . '_' . $i . '}'; 
// tmp                     }else{ 
// tmp                         if(!empty($product[$index])) {
// tmp                             $new_line .= '|' . $product[$index];
// tmp                         }else{
// tmp                             $new_line .= '|0';
// tmp                         }
// tmp                     }
// tmp                 }
// tmp                 $index++;
// tmp                 if(isset($total)){
// tmp                     // Get the product total tag
// tmp                     if( $product[$index][0]=='{' ) { 
// tmp                         $new_line .= '|{' . $total . '_' . $i . '}'; 
// tmp                     }else{
// tmp                         if(!empty($product[$index])) {
// tmp                             $new_line .= '|' . $product[$index];
// tmp                         }else{
// tmp                             $new_line .= '|0';
// tmp                         }
// tmp                     }
// tmp                 }
// tmp                 $index++;
// tmp                 if(isset($tax_class)){
// tmp                     // Get the product tax_class tag
// tmp                     if( $product[$index][0]=='{' ) { 
// tmp                         $new_line .= '|{' . $tax_class . '_' . $i . '}'; 
// tmp                     }else{ 
// tmp                         if(!empty($product[$index])) {
// tmp                             $new_line .= '|' . $product[$index];
// tmp                         }else{
// tmp                             $new_line .= '|';
// tmp                         }
// tmp                     }
// tmp                 }
// tmp                 $index++;
// tmp                 if(isset($variation)){
// tmp                     // Get the product tax_class tag
// tmp                     if( $product[$index][0]=='{' ) { 
// tmp                         $new_line .= '|{' . $variation . '_' . $i . '}'; 
// tmp                     }else{ 
// tmp                         if(!empty($product[$index])) {
// tmp                             $new_line .= '|' . $product[$index];
// tmp                         }else{
// tmp                             $new_line .= '|';
// tmp                         }
// tmp                     }
// tmp                 }
// tmp 
// tmp                 $products_tags[] = $new_line;
// tmp                 $looped[$i] = $i;
// tmp                 $i++;
// tmp                 return array(
// tmp                     'i'=>$i, 
// tmp                     'looped'=>$looped, 
// tmp                     'products_tags'=>$products_tags 
// tmp                 );
// tmp             }else{
// tmp                 return false;
// tmp             }
// tmp         }
// tmp 
// tmp 
// tmp         /**
// tmp          * Hook into before sending email and check if we need to create or update an order
// tmp          *
// tmp         */
// tmp         public static function redirect_to_wc_order( $atts ) {
// tmp             extract( shortcode_atts( array(
// tmp                 'sfsi'=>array(),
// tmp                 'form_id'=>0,
// tmp                 'sfs_uid'=>'',
// tmp                 'post'=>array(), 
// tmp                 'data'=>array(), 
// tmp                 'settings'=>array(), 
// tmp                 'entry_id'=>0, 
// tmp                 'attachments'=>array()
// tmp             ), $x));
// tmp             if($settings['save_contact_entry']=='yes'){
// tmp                 $data = get_post_meta( $entry_id, '_super_contact_entry_data', true );
// tmp             }else{
// tmp                 $data = $post['data'];
// tmp             }
// tmp 
// tmp             // tmp if( !isset( $settings['wc_instant_orders_action'] ) ) return true;
// tmp             // tmp if( $settings['wc_instant_orders_action']=='none' ) return true;
// tmp 
// tmp             // tmp // Create WooCommerce Order
// tmp             // tmp if( $settings['wc_instant_orders_action']=='create_order' ) {
// tmp 
// tmp             // tmp     // Check if we are updating an existing order
// tmp             // tmp     $update = false;
// tmp             // tmp     if(!empty($settings['wc_instant_orders_id'])){
// tmp             // tmp         $order_id = SUPER_Common::email_tags( $settings['wc_instant_orders_id'], $data, $settings );
// tmp             // tmp         if(absint($order_id)!=0){
// tmp             // tmp             $update = true;
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Gather all product information, and replace any tags with values
// tmp             // tmp     // After that combine both products and their custom meta data (if found any) together in one array
// tmp             // tmp     // Then loop through the products array and add it to the order along with possible meta data
// tmp             // tmp     //$products = explode("\n",$settings['wc_instant_orders_products']);
// tmp             // tmp     //$products_meta = explode("\n",$settings['wc_instant_orders_products_meta']);
// tmp             // tmp     $products = explode( "\n", $settings['wc_instant_orders_products'] );  
// tmp             // tmp     $products_tags = $products;
// tmp             // tmp     foreach( $products as $k => $v ) {
// tmp             // tmp         $product =  explode( "|", $v );
// tmp             // tmp         // {product_id}|{quantity}|{name}|{variation_id}|{subtotal}|{total}|{tax_class}|{variation}
// tmp             // tmp         if( isset( $product[0] ) ) $product_id = trim($product[0], '{}');
// tmp             // tmp         if( isset( $product[1] ) ) $quantity = trim($product[1], '{}');
// tmp             // tmp         if( isset( $product[2] ) ) $name = trim($product[2], '{}');
// tmp             // tmp         if( isset( $product[3] ) ) $variation_id = trim($product[3], '{}');
// tmp             // tmp         if( isset( $product[4] ) ) $subtotal = trim($product[4], '{}');
// tmp             // tmp         if( isset( $product[5] ) ) $total = trim($product[5], '{}');
// tmp             // tmp         if( isset( $product[6] ) ) $tax_class = trim($product[6], '{}');
// tmp             // tmp         if( isset( $product[7] ) ) $variation = trim($product[7], '{}');
// tmp             // tmp         $looped = array();
// tmp             // tmp         $i=2;
// tmp             // tmp         while( isset( $data[$product_id . '_' . ($i)]) ) {
// tmp             // tmp             $array = self::new_wc_checkout_products( $products_tags, $i, $looped, $product, $product_id, $quantity, $name, $variation_id, $subtotal, $total, $tax_class, $variation );
// tmp             // tmp             if($array==false) break;
// tmp             // tmp             $i = $array['i'];
// tmp             // tmp             $looped = $array['looped'];
// tmp             // tmp             $products_tags = $array['products_tags'];
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     $products_meta = explode( "\n", $settings['wc_instant_orders_products_meta'] );  
// tmp             // tmp     $values = array();
// tmp             // tmp     $meta = array();
// tmp             // tmp     $regex = "/{(.+?)}/";
// tmp             // tmp     foreach( $products_meta as $wck => $v ) {
// tmp             // tmp         $product =  explode( "|", $v );
// tmp 
// tmp             // tmp         // Skip if not enough values where found, we must have ID|Label|Value (a total of 3 values)
// tmp             // tmp         if( count($product) < 3 ) {
// tmp             // tmp             continue;
// tmp             // tmp         }
// tmp 
// tmp             // tmp         $found = false; // In case we found this tag in the submitted data
// tmp 
// tmp             // tmp         // Check if Product ID was set via a {tag} e.g: {tshirt_id}
// tmp             // tmp         if( isset( $product[0] ) ) {
// tmp             // tmp             $values[0]['value'] = $product[0];
// tmp             // tmp             $match = preg_match_all($regex, $product[0], $matches, PREG_SET_ORDER, 0);
// tmp             // tmp             if( $match ) {
// tmp             // tmp                 $values[0]['value'] = trim($values[0]['value'], '{}');
// tmp             // tmp                 $values[0]['match'] = true;
// tmp             // tmp                 foreach( $matches as $k => $v ) {
// tmp             // tmp                     $key = str_replace(';label', '', $v[0]);
// tmp             // tmp                     if( isset($data[$key]) ) {
// tmp             // tmp                         $found = true;
// tmp             // tmp                     }
// tmp             // tmp                 }
// tmp             // tmp             }
// tmp             // tmp         }
// tmp 
// tmp             // tmp         // Check if meta Label was set via a {tag} e.g: {tshirt_meta_label}
// tmp             // tmp         if( isset( $product[1] ) ) {
// tmp             // tmp             $values[1]['value'] = $product[1];
// tmp             // tmp             $match = preg_match_all($regex, $product[1], $matches, PREG_SET_ORDER, 0);
// tmp             // tmp             if( $match ) {
// tmp             // tmp                 $values[1]['value'] = trim($values[1]['value'], '{}');
// tmp             // tmp                 $values[1]['match'] = true;
// tmp             // tmp                 foreach( $matches as $k => $v ) {
// tmp             // tmp                     $key = str_replace(';label', '', $v[1]);
// tmp             // tmp                     if( isset($data[$key]) ) {
// tmp             // tmp                         $found = true;
// tmp             // tmp                     }
// tmp             // tmp                 }
// tmp             // tmp             }
// tmp             // tmp         } 
// tmp             // tmp       
// tmp             // tmp         // Check if meta Value was set via a {tag} e.g: {tshirt_color}
// tmp             // tmp         if( isset( $product[2] ) ) {
// tmp             // tmp             $values[2]['value'] = $product[2];
// tmp             // tmp             $match = preg_match_all($regex, $product[2], $matches, PREG_SET_ORDER, 0);
// tmp             // tmp             if( $match ) {
// tmp             // tmp                 $values[2]['value'] = trim($values[2]['value'], '{}');
// tmp             // tmp                 $values[2]['match'] = true;
// tmp             // tmp                 foreach( $matches as $k => $v ) {
// tmp             // tmp                     $key = str_replace(';label', '', $v[2]);
// tmp             // tmp                     if( isset($data[$key]) ) {
// tmp             // tmp                         $found = true;
// tmp             // tmp                     }else{
// tmp             // tmp                         $product[2] = '';
// tmp             // tmp                     }
// tmp             // tmp                 }
// tmp             // tmp             }
// tmp             // tmp         }
// tmp 
// tmp             // tmp         // Let's first add the current meta lin to the new array
// tmp             // tmp         $meta[] = $product;
// tmp 
// tmp             // tmp         // We found a {tag} and it existed in the form data
// tmp             // tmp         if( $found ) {
// tmp 
// tmp             // tmp             $i=2;
// tmp 
// tmp             // tmp             // Check if any of the matches exists in a dynamic column and are inside the submitted data
// tmp             // tmp             $stop_loop = false;
// tmp             // tmp             while( !$stop_loop ) {
// tmp             // tmp                 if( ( (isset($data[$values[0]['value'] . '_' . ($i)])) && ($values[0]['match']) ) || 
// tmp             // tmp                     ( (isset($data[$values[1]['value'] . '_' . ($i)])) && ($values[1]['match']) ) || 
// tmp             // tmp                     ( (isset($data[$values[2]['value'] . '_' . ($i)])) && ($values[2]['match']) ) ) {
// tmp 
// tmp             // tmp                     // Check if ID is {tag}
// tmp             // tmp                     $new_line = array();
// tmp             // tmp                     if($values[0]['match']){
// tmp             // tmp                         $new_line[] = '{' . $values[0]['value'] . '_' . $i . '}'; 
// tmp             // tmp                     }else{
// tmp             // tmp                         $new_line[] = $values[0]['value']; 
// tmp             // tmp                     }
// tmp 
// tmp             // tmp                     // Check if Label is {tag}
// tmp             // tmp                     if($values[1]['match']){
// tmp             // tmp                         // The label must be unique compared to other labels so we have to add (2) behind it
// tmp             // tmp                         $new_line[] = '{' . $values[1]['value'] . '_' . $i . '}' . ' ('.$i.')';
// tmp             // tmp                     }else{
// tmp             // tmp                         // The label must be unique compared to other labels so we have to add (2) behind it
// tmp             // tmp                         $new_line[] = $values[1]['value'] . ' ('.$i.')';
// tmp             // tmp                     }
// tmp 
// tmp             // tmp                     // Check if Value is {tag}
// tmp             // tmp                     if($values[2]['match']){
// tmp             // tmp                         $new_line[] = '{' . $values[2]['value'] . '_' . $i . '}'; 
// tmp             // tmp                     }else{
// tmp             // tmp                         $new_line[] = $values[2]['value']; 
// tmp             // tmp                     }
// tmp             // tmp                     $meta[] = $new_line;
// tmp             // tmp                     $i++;
// tmp             // tmp                 }else{
// tmp             // tmp                     $stop_loop = true;
// tmp             // tmp                 }
// tmp             // tmp             }
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     $final_products_meta = array();
// tmp             // tmp     foreach( $meta as $mk => $mv ) {
// tmp             // tmp         $product_id = 0;
// tmp             // tmp         $meta_key = '';
// tmp             // tmp         $meta_value = '';
// tmp             // tmp         if( isset( $mv[0] ) ) $product_id = SUPER_Common::email_tags( $mv[0], $data, $settings );
// tmp             // tmp         if( isset( $mv[1] ) ) $meta_key = SUPER_Common::email_tags( $mv[1], $data, $settings );
// tmp             // tmp         if( isset( $mv[2] ) ) $meta_value = SUPER_Common::email_tags( $mv[2], $data, $settings );
// tmp             // tmp         if(!empty($meta_value)) $final_products_meta[$product_id][$meta_key] = $meta_value;
// tmp             // tmp     }
// tmp 
// tmp             // tmp     $products = array();
// tmp             // tmp     foreach( $products_tags as $k => $v ) {
// tmp             // tmp         $product =  explode( "|", $v );
// tmp             // tmp         $product_id = 0;
// tmp             // tmp         $qty = 0;
// tmp             // tmp         $name = '';
// tmp             // tmp         $variation_id = '';
// tmp             // tmp         $subtotal = '';
// tmp             // tmp         $total = '';
// tmp             // tmp         $tax_class = '';
// tmp             // tmp         $variation = '';
// tmp             // tmp         if( isset( $product[0] ) ) $product_id = SUPER_Common::email_tags( $product[0], $data, $settings );     // '118'
// tmp             // tmp         if( isset( $product[1] ) ) $qty = SUPER_Common::email_tags( $product[1], $data, $settings );            // '1'
// tmp             // tmp         if( isset( $product[2] ) ) $name = SUPER_Common::email_tags( $product[2], $data, $settings );           // 'T-shirt custom name'
// tmp             // tmp         if( isset( $product[3] ) ) $variation_id = SUPER_Common::email_tags( $product[3], $data, $settings );   // '0'
// tmp             // tmp         if( isset( $product[4] ) ) $subtotal = SUPER_Common::email_tags( $product[4], $data, $settings );       // '10'
// tmp             // tmp         if( isset( $product[5] ) ) $total = SUPER_Common::email_tags( $product[5], $data, $settings );          // '10'
// tmp             // tmp         if( isset( $product[6] ) ) $tax_class = SUPER_Common::email_tags( $product[6], $data, $settings );      // '0'
// tmp             // tmp         $variations_array = array();
// tmp             // tmp         if( isset( $product[7] ) ) {
// tmp             // tmp             $variation = SUPER_Common::email_tags( $product[7], $data, $settings );                             // color;red#size;XL
// tmp             // tmp             $variations = explode("#", $variation);
// tmp             // tmp             foreach($variations as $k => $v){
// tmp             // tmp                 $values = explode(";", $v);
// tmp             // tmp                 $key = (isset($values[0]) ? $values[0] : '');
// tmp             // tmp                 $value = (isset($values[1]) ? $values[1] : '');
// tmp             // tmp                 if($key!==''){
// tmp             // tmp                     $variations_array[$key] = $values[1];
// tmp             // tmp                 }
// tmp             // tmp             }
// tmp             // tmp         } 
// tmp 
// tmp             // tmp         $qty = absint($qty);
// tmp             // tmp         if( $qty>0 ) {
// tmp             // tmp             $product_id = absint($product_id);
// tmp             // tmp             $meta = array();
// tmp             // tmp             if( isset($final_products_meta[$product_id]) ) {
// tmp             // tmp                 $meta = $final_products_meta[$product_id];
// tmp             // tmp             }
// tmp             // tmp             $product = wc_get_product( $product_id );
// tmp             // tmp             // If product exists, proceed, otherwise create an Arbitrary product instead
// tmp             // tmp             if($product){
// tmp             // tmp                 error_log('product exists, use it');
// tmp             // tmp                 // Existing product
// tmp             // tmp                 $price = $product->get_price();
// tmp             // tmp                 $product_array = array( 
// tmp             // tmp                     'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
// tmp             // tmp                     'quantity'     => $qty,
// tmp             // tmp                     'name'         => $product->get_name(),
// tmp             // tmp                     'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
// tmp             // tmp                     'subtotal'     => wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ),
// tmp             // tmp                     'total'        => wc_get_price_excluding_tax( $product, array( 'qty' => $qty ) ),
// tmp             // tmp                     'tax_class'    => $product->get_tax_class(),
// tmp             // tmp                     'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
// tmp             // tmp                     'meta_data'    => $meta
// tmp             // tmp                 );
// tmp             // tmp                 if($subtotal!==''){
// tmp             // tmp                     $subtotal = SUPER_Common::tofloat($subtotal);
// tmp             // tmp                     $product_array['subtotal'] = $subtotal;
// tmp             // tmp                     if( empty($total) ) $total = $subtotal;
// tmp             // tmp                 }
// tmp             // tmp                 if(!empty($total)){
// tmp             // tmp                     $total = SUPER_Common::tofloat($total);
// tmp             // tmp                     $product_array['total'] = $total;
// tmp             // tmp                 }
// tmp             // tmp                 if( count($variations_array) > 0 ) {
// tmp             // tmp                     $product_array['variation'] = $variations_array;
// tmp             // tmp                 }
// tmp             // tmp                 $products[] = $product_array;
// tmp             // tmp             }else{
// tmp             // tmp                 error_log('create arbitrary product');
// tmp             // tmp                 // Arbitrary product
// tmp             // tmp                 if( empty($total) ) $total = $subtotal;
// tmp             // tmp                 $products[] = array( 
// tmp             // tmp                     'name'         => $name,
// tmp             // tmp                     'quantity'     => $qty,
// tmp             // tmp                     'subtotal'     => $subtotal,
// tmp             // tmp                     'total'        => $total,
// tmp             // tmp                     'tax_class'    => $tax_class,
// tmp             // tmp                     'meta_data'    => $meta
// tmp             // tmp                 );
// tmp             // tmp             }
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Check if we can create a valid order, and if there are products to be added for this order
// tmp             // tmp     // If not return error message to the user
// tmp             // tmp     // foreach( $products as $args ) {
// tmp             // tmp     //     if( (absint($args['product_id'])===0) || (absint($args['quantity'])===0) ) {
// tmp             // tmp     //         // Return the error message to the user
// tmp             // tmp     //         SUPER_Common::output_message( array(
// tmp             // tmp     //             'msg' => esc_html__( 'The order couldn\'t be created because it is missing products!', 'super-forms' ),
// tmp             // tmp     //         ));
// tmp             // tmp     //     }
// tmp             // tmp     // }
// tmp 
// tmp             // tmp     // If verything is OK we will create the order
// tmp             // tmp     // global $woocommerce;
// tmp             // tmp     $args = array();
// tmp             // tmp     $args['status'] = SUPER_Common::email_tags( $settings['wc_instant_orders_status'], $data, $settings );
// tmp             // tmp     if($settings['wc_instant_orders_customer_id']!='') {
// tmp             // tmp         $args['customer_id'] = absint(SUPER_Common::email_tags( $settings['wc_instant_orders_customer_id'], $data, $settings ));
// tmp             // tmp     }else{
// tmp             // tmp         $user_id = get_current_user_id();
// tmp             // tmp         if( $user_id!=0 ) {
// tmp             // tmp             $args['customer_id'] = absint($user_id);
// tmp             // tmp         }
// tmp             // tmp     }
// tmp             // tmp     // Customer note can't be empty, because WP would throw an error `Column &#039;post_excerpt&#039; cannot be null`
// tmp             // tmp     if( empty( $settings['wc_instant_orders_customer_notes'] ) ) {
// tmp             // tmp         $args['customer_note'] = '';
// tmp             // tmp     }else{
// tmp             // tmp         $args['customer_note'] = SUPER_Common::email_tags( $settings['wc_instant_orders_customer_notes'], $data, $settings );
// tmp             // tmp     }
// tmp             // tmp     $args['created_via'] = 'Super Forms';
// tmp             // tmp     
// tmp             // tmp     if($update){
// tmp             // tmp         // Before updating the order we must remove all of it's items
// tmp             // tmp         $order = new WC_Order( $order_id );
// tmp             // tmp         $items = $order->get_items();
// tmp             // tmp         foreach ( $items as $item_id => $product ) {
// tmp             // tmp             wc_delete_order_item( $item_id );
// tmp             // tmp         }
// tmp             // tmp         $args['order_id'] = $order_id;
// tmp             // tmp         $order = wc_update_order($args);
// tmp             // tmp         if(is_wp_error($order)){
// tmp             // tmp             // Return the error message to the user
// tmp             // tmp             SUPER_Common::output_message( array(
// tmp             // tmp                 'msg' => esc_html__('Error: Unable to create order. Please try again.', 'woocommerce'),
// tmp             // tmp             ));
// tmp             // tmp         }else{
// tmp             // tmp             $order->remove_order_items();
// tmp             // tmp             // Delete old contact entry (we no longer need this because a brand new one will be created and used)
// tmp             // tmp             global $wpdb;
// tmp             // tmp             $contact_entry_id = $wpdb->get_var("
// tmp             // tmp                 SELECT post_id 
// tmp             // tmp                 FROM $wpdb->postmeta 
// tmp             // tmp                 WHERE meta_key = '_super_contact_entry_wc_order_id' 
// tmp             // tmp                 AND meta_value = '" . absint($order_id) . "'"
// tmp             // tmp             );
// tmp             // tmp             wp_delete_post( $contact_entry_id, true ); 
// tmp             // tmp             do_action('woocommerce_resume_order', $order_id);
// tmp             // tmp         }
// tmp             // tmp     }else{
// tmp             // tmp         error_log('create order');
// tmp             // tmp         $order = wc_create_order($args);
// tmp             // tmp         if(is_wp_error($order)){
// tmp             // tmp             error_log('error creating order');
// tmp             // tmp             // Return the error message to the user
// tmp             // tmp             SUPER_Common::output_message( array(
// tmp             // tmp                 'msg' => esc_html__('Error: Unable to create order. Please try again.', 'woocommerce'),
// tmp             // tmp             ));
// tmp             // tmp         }else{
// tmp             // tmp             do_action('woocommerce_new_order', $order->id);
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Payment Method
// tmp             // tmp     // Possible values are for example:
// tmp             // tmp     // bacs (Direct bank transfer)
// tmp             // tmp     // paypal (PayPal)
// tmp             // tmp     // check (Check payments)
// tmp             // tmp     // cos (Cash on delivery)
// tmp             // tmp     if(!empty($settings['wc_instant_orders_payment_gateway'])){
// tmp             // tmp         $payment_method = isset( $settings['wc_instant_orders_payment_gateway'] ) ? wc_clean( SUPER_Common::email_tags( $settings['wc_instant_orders_payment_gateway'], $data, $settings ) ) : false;
// tmp             // tmp         if($payment_method===false){
// tmp             // tmp             // Let user decide the payment method
// tmp             // tmp         }else{
// tmp             // tmp             $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
// tmp             // tmp             if( (isset($available_gateways)) && (isset($available_gateways[$payment_method])) ) {
// tmp             // tmp                 $order->set_payment_method($available_gateways[$payment_method]);
// tmp             // tmp             }else{
// tmp             // tmp                 // Delete the order
// tmp             // tmp                 wp_delete_post($order->id, true);
// tmp             // tmp                 // Return the error message to the user
// tmp             // tmp                 SUPER_Common::output_message( array(
// tmp             // tmp                     'msg' => esc_html__( 'Invalid payment method.', 'woocommerce' ),
// tmp             // tmp                 ));
// tmp             // tmp             }
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Save order ID to contact entry meta data, so we can link from contact entry page to the order
// tmp             // tmp     update_post_meta( $atts['entry_id'], '_super_contact_entry_wc_order_id', $order->get_id() );
// tmp 
// tmp             // tmp     // Save custom order meta
// tmp             // tmp     $meta_data = array();
// tmp             // tmp     $custom_meta = explode( "\n", $settings['wc_instant_orders_meta'] );
// tmp             // tmp     foreach( $custom_meta as $k ) {
// tmp             // tmp         if(empty($k)) continue;
// tmp             // tmp         $field = explode( "|", $k );
// tmp             // tmp         if( isset( $data[$field[1]]['value'] ) ) {
// tmp             // tmp             $meta_data[$field[0]] = $data[$field[1]]['value'];
// tmp             // tmp         }else{
// tmp             // tmp             if( (!empty($data[$field[1]])) && ( ($data[$field[1]]['type']=='files') && (isset($data[$field[1]]['files'])) ) ) {
// tmp             // tmp                 if( count($data[$field[1]]['files']>1) ) {
// tmp             // tmp                     foreach( $data[$field[1]]['files'] as $fk => $fv ) {
// tmp             // tmp                         if($meta_data[$field[0]]==''){
// tmp             // tmp                             $meta_data[$field[0]] = (!empty($fv['attachment']) ? $fv['attachment'] : (!empty($fv['path']) ? $fv['path'] : 0));
// tmp             // tmp                         }else{
// tmp             // tmp                             $meta_data[$field[0]] .= ',' . (!empty($fv['attachment']) ? $fv['attachment'] : (!empty($fv['path']) ? $fv['path'] : 0));
// tmp             // tmp                         }
// tmp             // tmp                     }
// tmp             // tmp                 }elseif( count($data[$field[1]]['files'])==1) {
// tmp             // tmp                     $cur = $data[$field[1]]['files'][0];
// tmp             // tmp                     if(!empty($cur['attachment'])){
// tmp             // tmp                         $fValue = absint($cur['attachment']);
// tmp             // tmp                     }else{
// tmp             // tmp                         $fValue = (!empty($cur['path']) ? $cur['path'] : 0);
// tmp             // tmp                     }
// tmp             // tmp                     $meta_data[$field[0]] = $fValue;
// tmp             // tmp                 }else{
// tmp             // tmp                     $meta_data[$field[0]] = '';
// tmp             // tmp                 }
// tmp             // tmp                 continue;
// tmp             // tmp             }else{
// tmp             // tmp                 $string = SUPER_Common::email_tags( $field[1], $data, $settings );
// tmp             // tmp                 $unserialize = unserialize($string);
// tmp             // tmp                 if ($unserialize !== false) {
// tmp             // tmp                     $meta_data[$field[0]] = $unserialize;
// tmp             // tmp                 }else{
// tmp             // tmp                     $meta_data[$field[0]] = $string;
// tmp             // tmp                 }
// tmp             // tmp             }
// tmp             // tmp         }
// tmp             // tmp     }
// tmp             // tmp     foreach( $meta_data as $k => $v ) {
// tmp             // tmp         if (function_exists('get_field_object')) {
// tmp             // tmp             global $wpdb;
// tmp             // tmp             $length = strlen($k);
// tmp             // tmp             if( class_exists('acf_pro') ) {
// tmp             // tmp                 $sql = "SELECT post_name FROM {$wpdb->posts} WHERE post_excerpt = '$k' AND post_type = 'acf-field'";
// tmp             // tmp             }else{
// tmp             // tmp                 $sql = "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE 'field_%' AND meta_value LIKE '%\"name\";s:$length:\"$k\";%';";
// tmp             // tmp             }
// tmp             // tmp             $acf_field = $wpdb->get_var($sql);
// tmp             // tmp             if(!$acf_field){
// tmp             // tmp                 $sql = "SELECT post_name FROM {$wpdb->posts} WHERE post_excerpt = '$k' AND post_type = 'acf-field'";
// tmp             // tmp                 $acf_field = $wpdb->get_var($sql);
// tmp             // tmp             }
// tmp             // tmp             $acf_field = get_field_object($acf_field);
// tmp             // tmp             if( ($acf_field['type']=='checkbox') || ($acf_field['type']=='select') || ($acf_field['type']=='radio') || ($acf_field['type']=='gallery') ) {
// tmp             // tmp                 $value = explode( ",", $v );
// tmp             // tmp                 update_field( $acf_field['key'], $value, $order->get_id() );
// tmp             // tmp                 continue;
// tmp             // tmp             }elseif( $acf_field['type']=='google_map' ) {
// tmp             // tmp                 if( isset($data[$k]['geometry']) ) {
// tmp             // tmp                     $data[$k]['geometry']['location']['address'] = $data[$k]['value'];
// tmp             // tmp                     $value = $data[$k]['geometry']['location'];
// tmp             // tmp                 }else{
// tmp             // tmp                     $value = array(
// tmp             // tmp                         'address' => $data[$k]['value'],
// tmp             // tmp                         'lat' => '',
// tmp             // tmp                         'lng' => '',
// tmp             // tmp                     );
// tmp             // tmp                 }
// tmp             // tmp                 update_field( $acf_field['key'], $value, $order->get_id() );
// tmp             // tmp                 continue;
// tmp             // tmp             }
// tmp             // tmp             if($acf_field['type']=='repeater'){
// tmp             // tmp                 $repeater_values = array();
// tmp             // tmp                 foreach($acf_field['sub_fields'] as $sk => $sv){
// tmp             // tmp                     if( isset($data[$sv['name']]) ) {
// tmp             // tmp                         $repeater_values[0][$sv['name']] = SUPER_WC_Instant_Orders()->return_field_value( $data, $sv['name'], $sv['type'], $settings );
// tmp             // tmp                         $field_counter = 2;
// tmp             // tmp                         while( isset($data[$sv['name'] . '_' . $field_counter]) ) {
// tmp             // tmp                             $repeater_values[$field_counter-1][$sv['name']] = sSUPER_WC_Instant_Orders()->return_field_value( $data, $sv['name'] . '_' . $field_counter, $sv['type'], $settings );
// tmp             // tmp                             $field_counter++;
// tmp             // tmp                         }
// tmp             // tmp                     }
// tmp             // tmp                 }
// tmp             // tmp                 update_field( $acf_field['key'], $repeater_values, $order->get_id() );
// tmp             // tmp                 continue;
// tmp             // tmp             }
// tmp             // tmp             update_field( $acf_field['key'], $v, $order->get_id() );
// tmp             // tmp             continue;
// tmp             // tmp         }
// tmp             // tmp         update_post_meta( $order->get_id(), $k, $v );
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Loop through possible order notes and save theme to the order
// tmp             // tmp     $notes = explode("\n", $settings['wc_instant_orders_order_notes']);
// tmp             // tmp     foreach($notes as $k => $v){
// tmp             // tmp         if(!empty($v)){
// tmp             // tmp             $row = explode("|", $v);
// tmp             // tmp             if(!isset($row[1])) $row[1] = 'false';
// tmp             // tmp             $is_customer_note = 1;
// tmp             // tmp             if($row[1]=='false'){
// tmp             // tmp                 $is_customer_note = 0;
// tmp             // tmp             }
// tmp             // tmp             if($row[0]!=''){
// tmp             // tmp                 $order->add_order_note( $row[0], $is_customer_note, false );
// tmp             // tmp             }
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Save billing address
// tmp             // tmp     $address = array();
// tmp             // tmp     $billing = explode("\n", $settings['wc_instant_orders_billing']);
// tmp             // tmp     foreach($billing as $k => $v){
// tmp             // tmp         $row = explode("|", $v);
// tmp             // tmp         if(!isset($row[1])) $row[1] = '';
// tmp             // tmp         $value = SUPER_Common::email_tags( $row[1], $data, $settings );
// tmp             // tmp         // Set to empty if {tag} wasn't repleaced, but only if it contained a {tag}
// tmp             // tmp         if( (strpos($row[1], '{')!==false) && (strpos($row[1], '}')!==false) ) {
// tmp             // tmp             if($value===$row[1]) $value = '';
// tmp             // tmp         }
// tmp             // tmp         $address[$row[0]] = $value;
// tmp             // tmp     }
// tmp             // tmp     try {
// tmp             // tmp         $object = $order->set_address( $address, 'billing' );
// tmp             // tmp     } catch ( WC_Data_Exception $e ) {
// tmp             // tmp         // Delete the order
// tmp             // tmp         wp_delete_post($order->id, true);
// tmp             // tmp         // Return the error message to the user
// tmp             // tmp         SUPER_Common::output_message( array(
// tmp             // tmp             'msg' => $e->getMessage(),
// tmp             // tmp         ));
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Save shipping address
// tmp             // tmp     $address = array();
// tmp             // tmp     $shipping = explode("\n", $settings['wc_instant_orders_shipping']);
// tmp             // tmp     foreach($shipping as $k => $v){
// tmp             // tmp         $row = explode("|", $v);
// tmp             // tmp         if(!isset($row[1])) $row[1] = '';
// tmp             // tmp         $value = SUPER_Common::email_tags( $row[1], $data, $settings );
// tmp             // tmp         // Set to empty if {tag} wasn't repleaced, but only if it contained a {tag}
// tmp             // tmp         if( (strpos($row[1], '{')!==false) && (strpos($row[1], '}')!==false) ) {
// tmp             // tmp             if($value===$row[1]) $value = '';
// tmp             // tmp         }
// tmp             // tmp         $address[$row[0]] = $value;
// tmp             // tmp     }
// tmp             // tmp     try {
// tmp             // tmp         $order->set_address( $address, 'shipping' );
// tmp             // tmp     } catch ( WC_Data_Exception $e ) {
// tmp             // tmp         // Delete the order
// tmp             // tmp         wp_delete_post($order->id, true);
// tmp             // tmp         // Return the error message to the user
// tmp             // tmp         SUPER_Common::output_message( array(
// tmp             // tmp             'msg' => $e->getMessage(),
// tmp             // tmp         ));
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Add products to the order
// tmp             // tmp     foreach( $products as $args ) {
// tmp             // tmp         $product = wc_get_product( $args['product_id'] );
// tmp             // tmp         // Qty will be overridden by $arg
// tmp             // tmp         $item_id = $order->add_product( $product, 1, $args ); // pid 8 & qty 1
// tmp             // tmp         foreach($args['meta_data'] as $mk => $mv){
// tmp             // tmp             // Add products meta data
// tmp             // tmp             wc_add_order_item_meta( $item_id, $mk, $mv);
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Add the coupon code if any
// tmp             // tmp     $coupon = SUPER_Common::email_tags( $settings['wc_instant_orders_coupon'], $data, $settings );
// tmp             // tmp     if(!empty($coupon)){
// tmp             // tmp         $order->apply_coupon( wc_clean( $coupon ) );
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Add shipping costs
// tmp             // tmp     // * @param string  $id          Shipping rate ID.
// tmp             // tmp     // * @param string  $label       Shipping rate label.
// tmp             // tmp     // * @param integer $cost        Cost.
// tmp             // tmp     // * @param array   $taxes       Taxes applied to shipping rate.
// tmp             // tmp     // * @param string  $method_id   Shipping method ID.
// tmp             // tmp     // * @param int     $instance_id Shipping instance ID.
// tmp             // tmp     // shipping_rate_id|shipping_rate_label|cost|method_id|instance_id
// tmp             // tmp     $wc_instant_orders_shipping_costs = explode("\n", $settings['wc_instant_orders_shipping_costs']);
// tmp             // tmp     foreach($wc_instant_orders_shipping_costs as $k => $v){
// tmp             // tmp         $row = explode("|", $v);
// tmp             // tmp         if((!isset($row[1])) || (!isset($row[2]))) continue;
// tmp             // tmp         $shipping_rate_id = SUPER_Common::email_tags( $row[0], $data, $settings );
// tmp             // tmp         $shipping_rate_label = SUPER_Common::email_tags( $row[1], $data, $settings );
// tmp             // tmp         $cost = SUPER_Common::email_tags( $row[2], $data, $settings );
// tmp             // tmp         $method_id = SUPER_Common::email_tags( $row[3], $data, $settings );
// tmp             // tmp         $instance_id = SUPER_Common::email_tags( $row[4], $data, $settings );
// tmp             // tmp         $shipping_taxes = WC_Tax::calc_shipping_tax($cost, WC_Tax::get_shipping_tax_rates());
// tmp             // tmp         $rate = new WC_Shipping_Rate($shipping_rate_id, $shipping_rate_label, $cost, $shipping_taxes, $method_id, $instance_id);
// tmp             // tmp         $item = new WC_Order_Item_Shipping();
// tmp             // tmp         $item->set_props(
// tmp             // tmp             array(
// tmp             // tmp                 'method_title' => $rate->label, 
// tmp             // tmp                 'method_id' => $rate->id, 
// tmp             // tmp                 'instance_id' => $rate->instance_id, 
// tmp             // tmp                 'total' => wc_format_decimal($rate->cost), 
// tmp             // tmp                 'taxes' => $rate->taxes, 
// tmp             // tmp                 'meta_data' => $rate->get_meta_data()
// tmp             // tmp             )
// tmp             // tmp         );
// tmp             // tmp         $order->add_item($item);
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Add order fee(s)
// tmp             // tmp     $orders_fees = explode( "\n", $settings['wc_instant_orders_fees'] );  
// tmp             // tmp     $values = array();
// tmp             // tmp     $fees = array();
// tmp             // tmp     $regex = "/{(.+?)}/";
// tmp             // tmp     foreach( $orders_fees as $wck => $v ) {
// tmp             // tmp         $fee =  explode( "|", $v );
// tmp             // tmp         $count = count($fee)-1;
// tmp             // tmp         // Skip if not enough values where found, we must have fee_name|fee_amount (a total of 2 values)
// tmp             // tmp         if( count($fee) < 2 ) {
// tmp             // tmp             continue;
// tmp             // tmp         }
// tmp             // tmp         $found = false; // In case we found this tag in the submitted data
// tmp             // tmp         $match_found = false;
// tmp             // tmp         // Check if option was set via a {tag} e.g: {fee_name}
// tmp             // tmp         $i = 0;
// tmp             // tmp         while( $i < $count ) {
// tmp             // tmp             if( isset( $fee[$i] ) ) {
// tmp             // tmp                 $values[$i]['value'] = $fee[$i];
// tmp             // tmp                 $match = preg_match_all($regex, $fee[$i], $matches, PREG_SET_ORDER, 0);
// tmp             // tmp                 if( $match ) {
// tmp             // tmp                     $match_found = true;
// tmp             // tmp                     $values[$i]['value'] = trim($values[$i]['value'], '{}');
// tmp             // tmp                     $values[$i]['match'] = true;
// tmp             // tmp                     foreach( $matches as $k => $v ) {
// tmp             // tmp                         $key = str_replace(';label', '', $v[$i]);
// tmp             // tmp                         if( isset($data[$key]) ) {
// tmp             // tmp                             $found = true;
// tmp             // tmp                         }
// tmp             // tmp                     }
// tmp             // tmp                 }
// tmp             // tmp             }
// tmp             // tmp             $i++;
// tmp             // tmp         }
// tmp 
// tmp             // tmp         if($match_found && $found){
// tmp             // tmp             // Let's first add the current meta line to the new array
// tmp             // tmp             $fees[] = $fee;
// tmp             // tmp         }else{
// tmp             // tmp             if($match_found && !$found){
// tmp             // tmp                 continue;
// tmp             // tmp             }
// tmp             // tmp         }
// tmp             // tmp         // We found a {tag} and it existed in the form data
// tmp             // tmp         if( $found ) {
// tmp 
// tmp             // tmp             $i=2;
// tmp             // tmp             // Check if any of the matches exists in a dynamic column and are inside the submitted data
// tmp             // tmp             $stop_loop = false;
// tmp             // tmp             while( !$stop_loop ) {
// tmp             // tmp                 if( ( (isset($data[$values[0]['value'] . '_' . ($i)])) && ($values[0]['match']) ) || 
// tmp             // tmp                     ( (isset($data[$values[1]['value'] . '_' . ($i)])) && ($values[1]['match']) ) || 
// tmp             // tmp                     ( (isset($data[$values[2]['value'] . '_' . ($i)])) && ($values[2]['match']) ) || 
// tmp             // tmp                     ( (isset($data[$values[3]['value'] . '_' . ($i)])) && ($values[3]['match']) ) ) {
// tmp             // tmp                     // Check if fee name is {tag}
// tmp             // tmp                     $new_line = array();
// tmp 
// tmp             // tmp                     $ii = 0;
// tmp             // tmp                     while( $ii < $count ) {
// tmp             // tmp                         if($values[$ii]['match']){
// tmp             // tmp                             $new_line[] = '{' . $values[$ii]['value'] . '_' . $i . '}'; 
// tmp             // tmp                         }else{
// tmp             // tmp                             $new_line[] = $values[$ii]['value']; 
// tmp             // tmp                         }
// tmp             // tmp                         $ii++;
// tmp             // tmp                     }
// tmp             // tmp                     $fees[] = $new_line;
// tmp             // tmp                     $i++;
// tmp             // tmp                 }else{
// tmp             // tmp                     $stop_loop = true;
// tmp             // tmp                 }
// tmp             // tmp             }
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     foreach( $fees as $fk => $fv ) {
// tmp             // tmp         if(!isset($fv[1])) continue;
// tmp             // tmp         $fee_name = 'Fee';
// tmp             // tmp         $fee_amount = 0;
// tmp             // tmp         $fee_tax_class = '';
// tmp             // tmp         $fee_tax_status = '';
// tmp             // tmp         if( isset( $fv[0] ) ) $fee_name = SUPER_Common::email_tags( $fv[0], $data, $settings );
// tmp             // tmp         if( isset( $fv[1] ) ) $fee_amount = wc_format_decimal(SUPER_Common::email_tags( $fv[1], $data, $settings ));
// tmp             // tmp         if( isset( $fv[2] ) ) $fee_tax_class = SUPER_Common::email_tags( $fv[2], $data, $settings );
// tmp             // tmp         if( isset( $fv[3] ) ) $fee_tax_status = SUPER_Common::email_tags( $fv[3], $data, $settings );
// tmp             // tmp         if(!empty($fee_name) && !empty($fee_amount)){
// tmp             // tmp             $fee = new WC_Order_Item_Fee();
// tmp             // tmp             $fee->set_props( array(
// tmp             // tmp                 'name' => $fee_name,
// tmp             // tmp                 'total' => $fee_amount, // @param string $amount (Fee amount) (do not enter negative amounts).
// tmp             // tmp                 'tax_class' => $fee_tax_class, // Valid tax_classes are inside WC_Tax::get_tax_class_slugs()
// tmp             // tmp                 'tax_status' => $fee_tax_status, // @param string $value (Set tax_status) `taxable` OR `none`
// tmp             // tmp                 'order_id'  => $order->get_id()
// tmp             // tmp                 //'amount' => $amount, // @param string $value (Set fee amount) deprecated?
// tmp             // tmp                 //'total_tax' => $fee->tax, // @param string $amount (Set total tax)
// tmp             // tmp                 //'taxes'     => array(
// tmp             // tmp                  //   'total' => $fee->tax_data, // @param array $raw_tax_data (Set taxes) This is an array of tax ID keys with total amount values.
// tmp             // tmp                 //),
// tmp             // tmp             ) );
// tmp             // tmp             $fee->save();
// tmp             // tmp             $order->add_item($fee);
// tmp             // tmp         }
// tmp             // tmp     }
// tmp 
// tmp             // tmp     // Make sure to calculate order totals
// tmp             // tmp     $order->calculate_totals();
// tmp 
// tmp             // tmp     // Redirect the user accordingly
// tmp             // tmp     if(!isset($settings['wc_instant_orders_redirect'])) $settings['wc_instant_orders_redirect'] = 'gateway';
// tmp             // tmp     $redirect_to = $settings['wc_instant_orders_redirect'];
// tmp             // tmp     error_log($redirect_to);
// tmp             // tmp     if($redirect_to!=='none'){
// tmp             // tmp         // If redirecting to payment gateway
// tmp             // tmp         if($redirect_to=='gateway'){
// tmp             // tmp             // Redirect to Payment gateway if order needs payment
// tmp             // tmp             // Update payment method
// tmp             // tmp             if ( $order->needs_payment() ) {
// tmp             // tmp                 error_log('needs payment');
// tmp             // tmp                 // Let the payment method validate fields
// tmp             // tmp                 $payment_method = isset( $settings['wc_instant_orders_payment_gateway'] ) ? wc_clean( SUPER_Common::email_tags( $settings['wc_instant_orders_payment_gateway'], $data, $settings ) ) : false;
// tmp             // tmp                 if(empty(trim($payment_method))){
// tmp             // tmp                     // Let user decide the payment method
// tmp             // tmp                     error_log('let user decide the payment method');
// tmp             // tmp                     SUPER_Common::output_message( array(
// tmp             // tmp                         'error' => false,
// tmp             // tmp                         'msg' => '',
// tmp             // tmp                         'redirect' => $order->get_checkout_payment_url()
// tmp             // tmp                     ));
// tmp             // tmp                 }else{
// tmp             // tmp                     error_log('payment method: ' . $payment_method);
// tmp             // tmp                     error_log('redirect to the gateway');
// tmp             // tmp                     $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
// tmp             // tmp                     if( (isset($available_gateways)) && (isset($available_gateways[$payment_method])) ) {
// tmp             // tmp                         $available_gateways[$payment_method]->validate_fields();
// tmp             // tmp                         // If validation was successful, continue
// tmp             // tmp                         if(wc_notice_count('error')===0){
// tmp             // tmp                             $result = $available_gateways[$payment_method]->process_payment($order->id);
// tmp             // tmp                             // Redirect to success/confirmation/payment page
// tmp             // tmp                             if($result['result']==='success'){
// tmp             // tmp                                 error_log('redirect now...');
// tmp             // tmp                                 error_log($result['redirect']);
// tmp             // tmp                                 SUPER_Common::output_message( array(
// tmp             // tmp                                     'error' => false,
// tmp             // tmp                                     'msg' => '',
// tmp             // tmp                                     'redirect' => $result['redirect']
// tmp             // tmp                                 ));
// tmp             // tmp                                 exit;
// tmp             // tmp                             }
// tmp             // tmp                         }
// tmp             // tmp                     }
// tmp             // tmp                 }
// tmp             // tmp             }
// tmp             // tmp         }
// tmp             // tmp         // If redirecting to "Pay for order page"
// tmp             // tmp         if($redirect_to=='pay_for_order'){
// tmp             // tmp             SUPER_Common::output_message( array(
// tmp             // tmp                 'error' => false,
// tmp             // tmp                 'msg' => '',
// tmp             // tmp                 'redirect' => $order->get_checkout_payment_url()
// tmp             // tmp             ));
// tmp             // tmp             exit;
// tmp             // tmp         }
// tmp             // tmp         // If redirecting to "Order received page"
// tmp             // tmp         if($redirect_to=='order_received_page'){
// tmp             // tmp             // Set to payment completed if order does not need a payment
// tmp             // tmp             if(!$order->needs_payment()){
// tmp             // tmp                 $order->payment_complete();
// tmp             // tmp             }
// tmp             // tmp             SUPER_Common::output_message( array(
// tmp             // tmp                 'error' => false,
// tmp             // tmp                 'msg' => '',
// tmp             // tmp                 'redirect' => $order->get_checkout_order_received_url()
// tmp             // tmp             ));
// tmp             // tmp             exit;
// tmp             // tmp         }
// tmp             // tmp     }
// tmp             // tmp }
// tmp 
// tmp             // tmp // Create WooCommerce Subscription
// tmp             // tmp if( $settings['wc_instant_orders_action']=='create_subscription' ) {
// tmp 
// tmp             // tmp     // tmp // Step 1: Create a new subscription product
// tmp             // tmp     // tmp $product = new WC_Product();
// tmp             // tmp     // tmp // Set the product name
// tmp             // tmp     // tmp $product->set_name('Arbitrary Subscription');
// tmp             // tmp     // tmp // Set the regular price
// tmp             // tmp     // tmp $product->set_regular_price(20);
// tmp             // tmp     // tmp // Enable subscription for the product
// tmp             // tmp     // tmp $product->add_meta_data('_subscription', 'yes');
// tmp             // tmp     // tmp // Set the subscription period to monthly
// tmp             // tmp     // tmp $product->add_meta_data('_subscription_period', 'month');
// tmp             // tmp     // tmp // Set the subscription length to never expire
// tmp             // tmp     // tmp $product->add_meta_data('_subscription_length', '');
// tmp             // tmp     // tmp // Set the signup fee
// tmp             // tmp     // tmp $product->add_meta_data('_subscription_sign_up_fee', 2);
// tmp             // tmp     // tmp // Set the free trial period
// tmp             // tmp     // tmp $product->add_meta_data('_subscription_trial_length', 3);
// tmp             // tmp     // tmp $product->add_meta_data('_subscription_trial_period', 'day');
// tmp             // tmp     // tmp // Set the sale price
// tmp             // tmp     // tmp $product->set_sale_price(12);
// tmp             // tmp     // tmp // Set the subscription price
// tmp             // tmp     // tmp $product->add_meta_data('_subscription_price', 20);
// tmp             // tmp     // tmp // Save the product
// tmp             // tmp     // tmp $product_id = $product->save();
// tmp             // tmp     // tmp // Step 2: Create a new order
// tmp             // tmp     // tmp $order = wc_create_order();
// tmp             // tmp     // tmp // Step 3: Add the subscription product to the order
// tmp             // tmp     // tmp $order->add_product($product, 1);
// tmp             // tmp     // tmp // Step 4: Calculate totals
// tmp             // tmp     // tmp $order->calculate_totals();
// tmp             // tmp     // tmp // Step 5: Save the order
// tmp             // tmp     // tmp $order->save();
// tmp             // tmp     // tmp // Step 6: Create a new subscription object
// tmp 
// tmp             // tmp     // tmp $subscription = new WC_Subscription();
// tmp             // tmp     // tmp // Set the parent order ID for the subscription
// tmp             // tmp     // tmp $subscription->set_parent_id($order->get_id());
// tmp             // tmp     // tmp // Set the status of the subscription (e.g., 'pending', 'active', etc.)
// tmp             // tmp     // tmp $subscription->set_status('pending');
// tmp             // tmp     // tmp // Set the subscription start date (optional)
// tmp             // tmp     // tmp $subscription->set_date_created($order->get_date_created());
// tmp             // tmp     // tmp // Set the product ID for the subscription
// tmp             // tmp     // tmp $subscription->set_product_id($product_id);
// tmp             // tmp     // tmp // Set the customer ID for the subscription (optional)
// tmp             // tmp     // tmp $subscription->set_customer_id($order->get_customer_id());
// tmp             // tmp     // tmp // Set any additional subscription details if needed
// tmp             // tmp     // tmp // Save the subscription
// tmp             // tmp     // tmp $subscription->save();
// tmp             // tmp     // tmp // Step 7: Redirect the user to the payment gateway page
// tmp             // tmp     // tmp $gateway = WC_Payment_Gateways::instance()->get_available_payment_gateways();
// tmp             // tmp     // tmp $payment_gateway = current($gateway);
// tmp             // tmp     // tmp $redirect_url = $payment_gateway->get_return_url($order);
// tmp 
// tmp             // tmp     // tmp SUPER_Common::output_message( array(
// tmp             // tmp     // tmp     'error' => false,
// tmp             // tmp     // tmp     'msg' => '',
// tmp             // tmp     // tmp     'redirect' => $redirect_url 
// tmp             // tmp     // tmp     //$order->get_checkout_payment_url()
// tmp             // tmp     // tmp ));
// tmp             // tmp     // tmp exit;
// tmp             // tmp     // tmp wp_redirect($redirect_url);
// tmp             // tmp     // tmp exit;
// tmp 
// tmp 
// tmp             // tmp     // Step 1: Create a new subscription product
// tmp             // tmp     //$product = new WC_Product_Simple();
// tmp             // tmp     $product = new WC_Product_Subscription();
// tmp             // tmp     // Set the product name
// tmp             // tmp     $product->set_name('Arbitrary Subscription');
// tmp             // tmp     $product->add_meta_data('_subscription_payment_sync_date', 0);
// tmp             // tmp     // Set the subscription price
// tmp             // tmp     $product->add_meta_data('_regular_price', 20);
// tmp             // tmp     $product->add_meta_data('_subscription_price', 20);
// tmp             // tmp     // Set sale price
// tmp             // tmp     $product->add_meta_data('_price', 15);
// tmp             // tmp     $product->add_meta_data('_sale_price', 15);
// tmp             // tmp     $product->add_meta_data('_sale_price_dates_from', '');
// tmp             // tmp     $product->add_meta_data('_sale_price_dates_to', '');
// tmp             // tmp     // Set trial period
// tmp             // tmp     $product->add_meta_data('_subscription_trial_length', 3);
// tmp             // tmp     $product->add_meta_data('_subscription_trial_period', 'day');
// tmp             // tmp     // Setup fee
// tmp             // tmp     $product->add_meta_data('_subscription_sign_up_fee', 2);
// tmp             // tmp     // Subscription interval
// tmp             // tmp     $product->add_meta_data('_subscription_period', 'month');
// tmp             // tmp     $product->add_meta_data('_subscription_period_interval', 1);
// tmp             // tmp     $product->add_meta_data('_subscription_length', 0);
// tmp             // tmp     $product->add_meta_data('_subscription_limit', 'no');
// tmp             // tmp     $product->add_meta_data('_subscription_one_time_shipping', 'no');
// tmp             // tmp     // Save product
// tmp             // tmp     $product_id = $product->save();
// tmp 
// tmp             // tmp     // Step 1: Check if user is logged in, otherwise create new user
// tmp             // tmp     $email = trim('test@test.com');
// tmp             // tmp     $address = array(
// tmp             // tmp         'first_name' => 'Jeremy',
// tmp             // tmp         'last_name'  => 'Test',
// tmp             // tmp         'company'    => '',
// tmp             // tmp         'email'      => $email,
// tmp             // tmp         'phone'      => '777-777-777-777',
// tmp             // tmp         'address_1'  => '31 Main Street',
// tmp             // tmp         'address_2'  => '', 
// tmp             // tmp         'city'       => 'Auckland',
// tmp             // tmp         'state'      => 'AKL',
// tmp             // tmp         'postcode'   => '12345',
// tmp             // tmp         'country'    => 'AU'
// tmp             // tmp     );
// tmp             // tmp     $default_password = wp_generate_password();
// tmp             // tmp     // If user is not logged in or doesn't exist, 
// tmp             // tmp     // create a new user with a random password and with the filled out email address
// tmp             // tmp     if(!$user = get_user_by('login', $email)) {
// tmp             // tmp         $user = wp_create_user( $email, $default_password, $email );
// tmp             // tmp     }
// tmp             // tmp     // Step 2: Create a new order
// tmp             // tmp     $order = wc_create_order(array('customer_id' => $user->id));
// tmp             // tmp     // Step 3: Add the subscription product to the order
// tmp             // tmp     $order->add_product($product, 1);
// tmp             // tmp     // Step 4: Calculate totals
// tmp             // tmp     $order->calculate_totals();
// tmp             // tmp     // Step 5: Save the order
// tmp             // tmp     $order->save();
// tmp 
// tmp             // tmp     // Step 6: Create the subscription
// tmp             // tmp     $start_date = current_time('Y-m-d H:i:s');
// tmp             // tmp     $billing_period = WC_Subscriptions_Product::get_period( $product );
// tmp             // tmp     error_log('period: '.$period);
// tmp             // tmp     $billing_interval = WC_Subscriptions_Product::get_interval( $product );
// tmp             // tmp     error_log('interval: '.$interval);
// tmp             // tmp     error_log('order ID: '.$order->get_id());
// tmp 
// tmp             // tmp     error_log('customer_id: ' . $order->get_user_id());
// tmp 
// tmp 
// tmp 
// tmp             // tmp     $sub = wcs_create_subscription( array(
// tmp             // tmp         //'start_date'       => get_date_from_gmt( $args['start_date'] ),
// tmp             // tmp         'order_id'         => wcs_get_objects_property( $order, 'id' ),
// tmp             // tmp         'customer_id'      => $order->get_user_id(),
// tmp             // tmp         'billing_period'   => $billing_period,
// tmp             // tmp         'billing_interval' => $billing_interval,
// tmp             // tmp         'customer_note'    => wcs_get_objects_property( $order, 'customer_note' ),
// tmp             // tmp     ));
// tmp             // tmp     if ( is_wp_error( $subscription ) ) {
// tmp             // tmp         throw new Exception( __( 'Error: Unable to create subscription. Please try again.', 'woocommerce-subscriptions' ) );
// tmp             // tmp     }
// tmp 
// tmp             // tmp     $sub = wcs_create_subscription(
// tmp             // tmp         array(
// tmp             // tmp             'order_id' => $order->get_id(), 
// tmp             // tmp             'billing_period' => $period, 
// tmp             // tmp             'billing_interval' => $interval, 
// tmp             // tmp             // 'start_date' => $start_date by default use start date of current date
// tmp             // tmp         )
// tmp             // tmp     );
// tmp             // tmp     if(is_wp_error($sub)){
// tmp             // tmp         //$error_message = $sub->get_error_message();
// tmp             // tmp         SUPER_Common::output_message( array(
// tmp             // tmp             'msg' => __( 'Error: Unable to create subscription. Please try again.', 'woocommerce-subscriptions' ),
// tmp             // tmp         ));
// tmp             // tmp     }
// tmp             // tmp     //$args = array(
// tmp             // tmp     //    'attribute_billing-period' => 'Yearly',
// tmp             // tmp     //    'attribute_subscription-type' => 'Both'
// tmp             // tmp     //);
// tmp             // tmp     $quantity = 1;
// tmp             // tmp     //$sub->add_product( $product, $quantity, $args);
// tmp             // tmp     $item_id = $sub->add_product(
// tmp             // tmp         $product,
// tmp             // tmp         $quantity,
// tmp             // tmp         array(
// tmp             // tmp             'variation' => (method_exists($product, 'get_variation_attributes')) ? $product->get_variation_attributes() : array(),
// tmp             // tmp             'totals'    => array(
// tmp             // tmp                 'subtotal'     => $product->get_price(),
// tmp             // tmp                 'subtotal_tax' => 0,
// tmp             // tmp                 'total'        => $product->get_price(),
// tmp             // tmp                 'tax'          => 0,
// tmp             // tmp                 'tax_data'     => array(
// tmp             // tmp                     'subtotal' => array(),
// tmp             // tmp                     'total'    => array(),
// tmp             // tmp                 ),
// tmp             // tmp             ),
// tmp             // tmp         )
// tmp             // tmp     );
// tmp             // tmp     if(!$item_id){
// tmp             // tmp         SUPER_Common::output_message( array(
// tmp             // tmp             'msg' => __( 'Error: Unable to add product to created subscription. Please try again.', 'woocommerce-subscriptions' ),
// tmp             // tmp         ));
// tmp             // tmp     }
// tmp 
// tmp             // tmp     //$sub->set_address( $address, 'billing' );
// tmp             // tmp     //$sub->set_address( $address, 'shipping' );
// tmp             // tmp     //$sub->add_shipping((object)array (
// tmp             // tmp     //    'id' => $selected_shipping_method->id,
// tmp             // tmp     //    'label'    => $selected_shipping_method->title,
// tmp             // tmp     //    'cost'     => SUPER_Common::tofloat($class_cost),
// tmp             // tmp     //    'taxes'    => array(),
// tmp             // tmp     //    'calc_tax'  => 'per_order'
// tmp             // tmp     //));
// tmp             // tmp     //$sub->calculate_totals();
// tmp 
// tmp             // tmp     $sub->calculate_totals();
// tmp 
// tmp             // tmp     SUPER_Common::output_message( array(
// tmp             // tmp         'error' => false,
// tmp             // tmp         'msg' => '',
// tmp             // tmp         'redirect' => $sub->get_view_order_url() //$order->get_checkout_payment_url()
// tmp             // tmp     ));
// tmp             // tmp     exit;
// tmp 
// tmp             // tmp     //wp_safe_redirect( $sub->get_view_order_url() );
// tmp             // tmp     // tmp WC_Subscriptions_Manager::activate_subscriptions_for_order($order);
// tmp 
// tmp             // tmp     // Step 7: Redirect the user to the payment gateway page
// tmp             // tmp     //$gateway = WC_Payment_Gateways::instance()->get_available_payment_gateways();
// tmp             // tmp     //$payment_gateway = current($gateway);
// tmp             // tmp     //$redirect_url = $payment_gateway->get_return_url($order);
// tmp             // tmp     //wp_redirect($redirect_url);
// tmp             // tmp     SUPER_Common::output_message( array(
// tmp             // tmp         'error' => false,
// tmp             // tmp         'msg' => '',
// tmp             // tmp         'redirect' => $order->get_checkout_payment_url()
// tmp             // tmp     ));
// tmp             // tmp     exit;
// tmp 
// tmp             // tmp     //// Set the product type to subscription
// tmp             // tmp     ////$product->set_type('subscription');
// tmp             // tmp     ////$product->set_regular_price(20);
// tmp             // tmp     //// Enable subscription for the product
// tmp             // tmp     //$product->add_meta_data('_subscription', 'yes');
// tmp             // tmp     //// Set the subscription period to monthly
// tmp             // tmp     //$product->add_meta_data('_subscription_period', 'month');
// tmp             // tmp     //// Set the subscription length to never expire
// tmp             // tmp     //$product->add_meta_data('_subscription_length', '');
// tmp             // tmp     //// Set the signup fee
// tmp             // tmp     //$product->add_meta_data('_subscription_sign_up_fee', 2);
// tmp             // tmp     //// Set the free trial period
// tmp             // tmp     //$product->add_meta_data('_subscription_trial_length', 3);
// tmp             // tmp     //$product->add_meta_data('_subscription_trial_period', 'day');
// tmp             // tmp     //// Set the sale price
// tmp             // tmp     //$product->set_sale_price(12);
// tmp             // tmp     //// Save the product
// tmp             // tmp     //$product_id = $product->save();
// tmp 
// tmp             // tmp     // tmp global $woocommerce;
// tmp             // tmp     // tmp $email = 'test@test.com';
// tmp             // tmp     // tmp $start_date = '2015-01-01 00:00:00';
// tmp             // tmp     // tmp $address = array(
// tmp             // tmp     // tmp     'first_name' => 'Jeremy',
// tmp             // tmp     // tmp     'last_name'  => 'Test',
// tmp             // tmp     // tmp     'company'    => '',
// tmp             // tmp     // tmp     'email'      => $email,
// tmp             // tmp     // tmp     'phone'      => '777-777-777-777',
// tmp             // tmp     // tmp     'address_1'  => '31 Main Street',
// tmp             // tmp     // tmp     'address_2'  => '', 
// tmp             // tmp     // tmp     'city'       => 'Auckland',
// tmp             // tmp     // tmp     'state'      => 'AKL',
// tmp             // tmp     // tmp     'postcode'   => '12345',
// tmp             // tmp     // tmp     'country'    => 'AU'
// tmp             // tmp     // tmp );
// tmp             // tmp     // tmp $default_password = wp_generate_password();
// tmp             // tmp     
// tmp             // tmp     // tmp // If user is not logged in or doesn't exist, create a new user with a random password and with the filled out email address
// tmp             // tmp     // tmp if (!$user = get_user_by('login', $email)) {
// tmp             // tmp     // tmp     $user = wp_create_user( $email, $default_password, $email );
// tmp             // tmp     // tmp }
// tmp 
// tmp             // tmp     // tmp // I've used one product with multiple variations
// tmp             // tmp     // tmp $parent_product = wc_get_product(22998);
// tmp             // tmp     // tmp $args = array(
// tmp             // tmp     // tmp     'attribute_billing-period' => 'Yearly',
// tmp             // tmp     // tmp     'attribute_subscription-type' => 'Both'
// tmp             // tmp     // tmp );
// tmp             // tmp     // tmp $product_variation = $parent_product->get_matching_variation($args);
// tmp             // tmp     // tmp $product = wc_get_product($product_variation);  
// tmp             // tmp     
// tmp             // tmp     // tmp // Each variation also has its own shipping class
// tmp             // tmp     // tmp $shipping_class = get_term_by('slug', $product->get_shipping_class(), 'product_shipping_class');
// tmp             // tmp     // tmp WC()->shipping->load_shipping_methods();
// tmp             // tmp     // tmp $shipping_methods = WC()->shipping->get_shipping_methods();
// tmp             // tmp     
// tmp             // tmp     // tmp // I have some logic for selecting which shipping method to use; your use case will likely be different, so figure out the method you need and store it in $selected_shipping_method
// tmp             // tmp     // tmp $selected_shipping_method = $shipping_methods['free_shipping'];
// tmp             // tmp     // tmp $class_cost = $selected_shipping_method->get_option('class_cost_' . $shipping_class->term_id);
// tmp             // tmp     // tmp $quantity = 1;
// tmp             // tmp     
// tmp             // tmp     // tmp // As far as I can see, you need to create the order first, then the sub
// tmp             // tmp     // tmp $order = wc_create_order(array('customer_id' => $user->id));
// tmp             // tmp     // tmp $order->add_product( $product, $quantity, $args);
// tmp             // tmp     // tmp $order->set_address( $address, 'billing' );
// tmp             // tmp     // tmp $order->set_address( $address, 'shipping' );
// tmp             // tmp     // tmp $order->add_shipping((object)array (
// tmp             // tmp     // tmp     'id' => $selected_shipping_method->id,
// tmp             // tmp     // tmp     'label'    => $selected_shipping_method->title,
// tmp             // tmp     // tmp     'cost'     => SUPER_Common::tofloat($class_cost),
// tmp             // tmp     // tmp     'taxes'    => array(),
// tmp             // tmp     // tmp     'calc_tax'  => 'per_order'
// tmp             // tmp     // tmp ));
// tmp             // tmp     // tmp $order->calculate_totals();
// tmp             // tmp     // tmp $order->update_status("completed", 'Imported order', TRUE);
// tmp 
// tmp             // tmp     // tmp // Order created, now create sub attached to it -- optional if you're not creating a subscription, obvs
// tmp             // tmp     // tmp // Each variation has a different subscription period
// tmp             // tmp     // tmp $period = WC_Subscriptions_Product::get_period( $product );
// tmp             // tmp     // tmp $interval = WC_Subscriptions_Product::get_interval( $product );
// tmp             // tmp     // tmp $sub = wcs_create_subscription(array('order_id' => $order->get_id(), 'billing_period' => $period, 'billing_interval' => $interval, 'start_date' => $start_date));
// tmp             // tmp     // tmp $sub->add_product( $product, $quantity, $args);
// tmp             // tmp     // tmp $sub->set_address( $address, 'billing' );
// tmp             // tmp     // tmp $sub->set_address( $address, 'shipping' );
// tmp             // tmp     // tmp $sub->add_shipping((object)array (
// tmp             // tmp     // tmp     'id' => $selected_shipping_method->id,
// tmp             // tmp     // tmp     'label'    => $selected_shipping_method->title,
// tmp             // tmp     // tmp     'cost'     => SUPER_Common::tofloat($class_cost),
// tmp             // tmp     // tmp     'taxes'    => array(),
// tmp             // tmp     // tmp     'calc_tax'  => 'per_order'
// tmp             // tmp     // tmp ));
// tmp             // tmp     // tmp $sub->calculate_totals();
// tmp             // tmp     // tmp WC_Subscriptions_Manager::activate_subscriptions_for_order($order);
// tmp             // tmp     // tmp print "<a href='/wp-admin/post.php?post=" . $sub->id . "&action=edit'>Sub created! Click here to edit</a>";
// tmp             // tmp }
// tmp 
// tmp             // tmp // Store the created order ID into a session, to either alter the redirect URL or for developers to use in their custom code
// tmp             // tmp // The redirect URL will only be altered if the option to do so was enabled in the form settings.
// tmp             // tmp SUPER_Common::setClientData( array( 'name'=> 'wc_instant_orders_created_order', 'value'=>$order->get_id( ) ) );
// tmp             // tmp do_action( 'super_wc_instant_orders_after_insert_order_action', array( 'order_id'=>$order->get_id(), 'data'=>$data, 'atts'=>$atts ) );
// tmp         }
// tmp 
// tmp         
// tmp        /**
// tmp         * Return field value for saving into post meta
// tmp         *
// tmp         *  @since      1.1.3
// tmp        */
// tmp        public static function return_field_value( $data, $name, $type, $settings ) {
// tmp            $value = '';
// tmp            $type = $type;           
// tmp            if( ($data[$name]['type']=='files') && (isset($data[$name]['files'])) ) {
// tmp                if( count($data[$name]['files'])>1 ) {
// tmp                    foreach( $data[$name]['files'] as $fk => $fv ) {
// tmp                        if($value==''){
// tmp                            $value = (!empty($fv['attachment']) ? $fv['attachment'] : (!empty($fv['path']) ? $fv['path'] : 0));
// tmp                        }else{
// tmp                            $value .= ',' . (!empty($fv['attachment']) ? $fv['attachment'] : (!empty($fv['path']) ? $fv['path'] : 0));
// tmp                        }
// tmp                    }
// tmp                }elseif( count($data[$name]['files'])==1) {
// tmp                    $cur = $data[$name]['files'][0];
// tmp                    if(!empty($cur['attachment'])){
// tmp                        $value = absint($cur['attachment']);
// tmp                    }else{
// tmp                        $value = (!empty($cur['path']) ? $cur['path'] : 0);
// tmp                    }
// tmp                }else{
// tmp                    $value = '';
// tmp                }
// tmp            }elseif( ($type=='checkbox') || ($type=='select') || ($type=='radio') || ($type=='gallery') ) {
// tmp                $value = explode( ",", $data[$name]['value'] );
// tmp            }elseif( $type=='google_map' ) {
// tmp                if( isset($data[$name]['geometry']) ) {
// tmp                    $data[$name]['geometry']['location']['address'] = $data[$name]['value'];
// tmp                    $value = $data[$name]['geometry']['location'];
// tmp                }else{
// tmp                    $value = array(
// tmp                        'address' => $data[$name]['value'],
// tmp                        'lat' => '',
// tmp                        'lng' => '',
// tmp                    );
// tmp                }
// tmp            }else{
// tmp                $value = $data[$name]['value'];
// tmp            }
// tmp            return $value;
// tmp        }
// tmp         /**
// tmp          * Hook into settings and add WooCommerce Instant Orders settings
// tmp          *
// tmp         */
// tmp         public static function add_settings( $array, $x ) {
// tmp             $default = $x['default'];
// tmp             $settings = $x['settings'];
// tmp             
// tmp             // If woocommerce is not loaded, just return the array
// tmp             if(!function_exists('WC')) {
// tmp                 $array['wc_instant_orders'] = array(        
// tmp                     'hidden' => 'settings',
// tmp                     'name' => esc_html__( 'WooCommerce Instant Orders', 'super-forms' ),
// tmp                     'label' => esc_html__( 'WooCommerce Instant Orders Settings', 'super-forms' ),
// tmp                     'html' => array(
// tmp                         sprintf( esc_html__( '%sPlease install and activate the %sWooCommerce plugin%s in order to see and configure these settings.%s', 'super-forms' ), '<div class="sfui-notice sfui-desc">', '<a href="https://wordpress.org/plugins/woocommerce/">', '</a>', '</div>' ),
// tmp                     )
// tmp                 );
// tmp                 return $array;
// tmp             }
// tmp             
// tmp             $default_address = sprintf( esc_html__( 'first_name|{first_name}%1$slast_name|{last_name}%1$scompany|{company}%1$semail|{email}%1$sphone|{phone}%1$saddress_1|{address_1}%1$saddress_2|{address_2}%1$scity|{city}%1$sstate|{state}%1$spostcode|{postcode}%1$scountry|{country}', 'super-forms' ), "\n" );
// tmp             $array['wc_instant_orders'] = array(        
// tmp                 'hidden' => 'settings',
// tmp                 'name' => esc_html__( 'WooCommerce Instant Orders', 'super-forms' ),
// tmp                 'label' => esc_html__( 'WooCommerce Instant Orders Settings', 'super-forms' ),
// tmp                 'fields' => array(
// tmp                     'wc_instant_orders_action' => array(
// tmp                         'name' => esc_html__( 'Actions', 'super-forms' ),
// tmp                         'default' =>  'none',
// tmp                         'filter' => true,
// tmp                         'type' => 'select',
// tmp                         'values' => array(
// tmp                             'none' => esc_html__( 'None (do nothing)', 'super-forms' ),
// tmp                             'create_order' => esc_html__( 'Create/Update WooCommerce Order', 'super-forms' ),
// tmp                             'create_subscription' => esc_html__( 'Create/Update WooCommerce Subscription', 'super-forms' ),
// tmp                         ),
// tmp                     ),
// tmp                     'wc_instant_orders_redirect' => array(
// tmp                         'name' => esc_html__( 'Redirect to:', 'super-forms' ),
// tmp                         'label' => esc_html__( 'Choose between redirecting to the payment gateway, the created order itself or to the "Order received" page', 'super-forms' ),
// tmp                         'default' =>  'gateway',
// tmp                         'type' => 'select',
// tmp                         'values' => array(
// tmp                             'gateway' => esc_html__( 'Payment gateway (default)', 'super-forms' ),
// tmp                             'pay_for_order' => esc_html__( 'Pay for order page (redirects to front-end payment page)', 'super-forms' ),
// tmp                             'order' => esc_html__( 'Created order (redirects to order in back-end)', 'super-forms' ),
// tmp                             'order_received_page' => esc_html__( 'Order received page (redirects to front-end summary page)', 'super-forms' ),
// tmp                             'none' => esc_html__( 'Disabled (do not redirect)', 'super-forms' )
// tmp                         ),
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription'
// tmp                     ),
// tmp                     'wc_instant_orders_id' => array(
// tmp                         'name' => esc_html__( 'Enter order ID in case you want to update an existing order', 'super-forms' ),
// tmp                         'label' => esc_html__( 'Leave blank to create a new order instead (use {tags} if needed)', 'super-forms' ),
// tmp                         'type' => 'text',
// tmp                         'default' =>  '',
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_products' => array(
// tmp                         'name' => esc_html__( 'Enter the product(s) ID that needs to be added to the order', 'super-forms' ),
// tmp                         'label' => sprintf( esc_html__( 'Put each one a new line. If the product ID is set to 0 or doesn\'t exist, it will be added as an Arbitrary product instead.%s{product_id}|{quantity}|{name}|{variation_id}|{subtotal}|{total}|{tax_class}|{variation}%sExample: 0|1|T-shirt|0|10|10|0|color;red#size;XL
// tmp                             ', 'super-forms' ), '<br />', '<br />' ),
// tmp                         'placeholder' => '{product_id}|{quantity}|{name}|{variation_id}|{subtotal}|{total}|{tax_class}|{variation}',
// tmp                         'type' => 'textarea',
// tmp                         'default' =>  "0|1|T-shirt|0|10|10|0|color;red#size;XL",
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_products_meta' => array(
// tmp                         'name' => esc_html__( 'Enter the product(s) custom meta data (optional)', 'super-forms' ),
// tmp                         'label' => sprintf( esc_html__( 'If field is inside dynamic column, system will automatically add all the meta data. Put each product ID with it\'s meta data on a new line separated by pipes "|".%1$s%2$sExample with tags:%3$s {id}|Color|{color}%1$s%2$sExample without tags:%3$s 82921|Color|Red%1$s%2$sAllowed values:%3$s integer|string|string.', 'super-forms' ), '<br />', '<strong>', '</strong>' ),
// tmp                         'desc' => esc_html__( 'Put each on a new line, {tags} can be used to retrieve data', 'super-forms' ),
// tmp                         'type' => 'textarea',
// tmp                         'default' =>  "{product_id}|Color|{color}",
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_shipping_costs' => array(
// tmp                         'name' => esc_html__( 'Shipping cost(s)', 'super-forms' ),
// tmp                         'label' => sprintf( esc_html__( '%2$sPut each shipping cost on a new line. Example format:%3$s%1$sshipping_rate_id|shipping_rate_label|cost|method_id|instance_id%1$s%2$sExample without tags:%3$s flat_rate_shipping|Ship by airplane|275|flat_rate%1$s%2$sExample with tags:%3$s {shipping_method_id}|{shipping_method_label}|{cost}|{shipping_method}%1$s%2$sValid shipping method ID\'s are:%3$s', 'super-forms' ), '<br />', '<strong>', '</strong>' ) . '<br />' . implode('<br />',array_values(array_keys(WC()->shipping->get_shipping_methods()))),
// tmp                         'placeholder' => 'flat_rate_shipping|Ship by airplane|275|flat_rate',
// tmp                         'type' => 'textarea',
// tmp                         'default' =>  "",
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true
// tmp                     ),
// tmp                     'wc_instant_orders_fees' => array(
// tmp                         'name' => esc_html__( 'Order fee(s)', 'super-forms' ),
// tmp                         'label' => esc_html__( 'If field is inside dynamic column, system will automatically add all the fees. <strong>Put each fee on a new line. Example format:</strong><br />name|amount|tax_class|tax_status<br /><strong>Example without tags:</strong> Extra processing fee|45|zero-rate|taxable<br /><strong>Example with tags:</strong> {fee_name}|{amount}|zero-rate|taxable<br /><strong>Valid tax classes are:</strong>', 'super-forms' ).'<br />'.implode('<br />',(WC_Tax::get_tax_class_slugs())),
// tmp                         'placeholder' => 'Handling fee|45',
// tmp                         'type' => 'textarea',
// tmp                         'default' =>  "",
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_billing' => array(
// tmp                         'name' => esc_html__( 'Define billing address', 'super-forms' ),
// tmp                         'label' => esc_html__( 'Put each item on a new line and use {tags} to retrieve values dynamically', 'super-forms' ),
// tmp                         'type' => 'textarea',
// tmp                         'placeholder' => $default_address,
// tmp                         'default' =>  $default_address,
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_shipping' => array(
// tmp                         'name' => esc_html__( 'Define shipping address', 'super-forms' ),
// tmp                         'label' => esc_html__( 'Put each item on a new line and use {tags} to retrieve values dynamically', 'super-forms' ),
// tmp                         'type' => 'textarea',
// tmp                         'placeholder' => $default_address,
// tmp                         'default' =>  $default_address,
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_coupon' => array(
// tmp                         'name' => esc_html__( 'Coupon code ', 'super-forms' ),
// tmp                         'label' => esc_html__( '(use {tags} if needed)', 'super-forms' ),
// tmp                         'type' => 'text',
// tmp                         'default' =>  '',
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_customer_note' => array(
// tmp                         'name' => esc_html__( 'Customer note', 'super-forms' ),
// tmp                         'label' => esc_html__( '(use {tags} if needed, leave blank for no none)', 'super-forms' ),
// tmp                         'type' => 'textarea',
// tmp                         'default' =>  '',
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_status' => array(
// tmp                         'name' => esc_html__( 'Order status ', 'super-forms' ),
// tmp                         'label' => sprintf( esc_html__( 'Use {tags} if needed.%s%sValid statuses are:%s', 'super-forms' ), '<br />', '<strong>', '</strong>' ) . '<br />' . implode(', ',array_keys(wc_get_order_statuses())),
// tmp                         'type' => 'text',
// tmp                         'default' =>  '',
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_order_notes' => array(
// tmp                         'name' => esc_html__( 'Add order notes', 'super-forms' ),
// tmp                         'label' => esc_html__( '(use {tags} if needed, leave blank for no order notes, put each order note on a new line and specify if the note is a customer note)', 'super-forms' ),
// tmp                         'type' => 'textarea',
// tmp                         'placeholder' => sprintf( esc_html__( 'This is a customer note|true%sAnd this is not a customer note|false', 'super-forms' ), "\n" ),
// tmp                         'default' =>  '',
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_payment_gateway' => array(
// tmp                         'name' => esc_html__( 'Set a fixed payment gateway (optional)', 'super-forms' ),
// tmp                         'label' => sprintf( esc_html__( 'Leave blank to let user decide what payment gateway to use. Use {tags} if needed.%s%sValid payment gateways are:%s', 'super-forms' ), '<br />', '<strong>', '</strong>' ) . '<br />' . implode(', ',array_keys(WC()->payment_gateways->get_available_payment_gateways())),
// tmp                         'type' => 'text',
// tmp                         'default' =>  '',
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_customer_id' => array(
// tmp                         'name' => esc_html__( 'Customer ID', 'super-forms' ),
// tmp                         'label' => esc_html__( '(use {tags} if needed, defaults to logged in user)', 'super-forms' ),
// tmp                         'type' => 'text',
// tmp                         'default' =>  '',
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     ),
// tmp                     'wc_instant_orders_meta' => array(
// tmp                         'name' => esc_html__( 'Save custom order meta data', 'super-forms' ),
// tmp                         'label' => esc_html__( 'Example: _first_name|{first_name}', 'super-forms' ),
// tmp                         'desc' => esc_html__( 'Based on your form fields you can save custom meta data for your order', 'super-forms' ),
// tmp                         'placeholder' => "meta_key|{field1}\nmeta_key2|{field2}\nmeta_key3|{field3}",
// tmp                         'type' => 'textarea',
// tmp                         'default' =>  '',
// tmp                         'filter' => true,
// tmp                         'parent' => 'wc_instant_orders_action',
// tmp                         'filter_value' => 'create_order,create_subscription',
// tmp                         'allow_empty' => true,
// tmp                     )
// tmp                 )
// tmp             );
// tmp             return $array;
// tmp         }
// tmp     }
// tmp endif;
// tmp 
// tmp /**
// tmp  * Returns the main instance of SUPER_WC_Instant_Orders to prevent the need to use globals.
// tmp  *
// tmp  * @return SUPER_WC_Instant_Orders
// tmp  */
// tmp if(!function_exists('SUPER_WC_Instant_Orders')){
// tmp     function SUPER_WC_Instant_Orders() {
// tmp         return SUPER_WC_Instant_Orders::instance();
// tmp     }
// tmp     // Global for backwards compatibility.
// tmp     $GLOBALS['SUPER_WC_Instant_Orders'] = SUPER_WC_Instant_Orders();
// tmp }