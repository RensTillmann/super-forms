<?php
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
        public $add_on_slug = 'stripe';
        public $add_on_name = 'Stripe';

        public static $currency_codes = array(
            // https://www.thefinancials.com/Default.aspx?SubSectionID=curformat
            // https://www2.1010data.com/documentationcenter/prime/1010dataUsersGuide/DataTypesAndFormats/currencyUnitCodes.html
            'USD'=>array('symbol'=>'$'),
            'AED'=>array('symbol'=>'د.إ'),
            'AFN*'=>array('symbol'=>'؋'),
            'ALL'=>array('symbol'=>'Lek'),
            'AMD'=>array('symbol'=>'֏'),
            'ANG'=>array('symbol'=>'ƒ','format'=>'0.0,00'),
            'AOA*'=>array('symbol'=>'Kz'),
            'ARS*'=>array('symbol'=>'$','format'=>'0.0,00'),
            'AUD'=>array('symbol'=>'$'),
            'AWG'=>array('symbol'=>'ƒ'),
            'AZN'=>array('symbol'=>'₼'),
            'BAM'=>array('symbol'=>'KM'),
            'BBD'=>array('symbol'=>'$'),
            'BDT'=>array('symbol'=>'৳'),
            'BGN'=>array('symbol'=>'Лв.'),
            'BIF'=>array('symbol'=>'FBu','format'=>'0,0'),
            'BMD'=>array('symbol'=>'$'),
            'BND'=>array('symbol'=>'$'),
            'BOB*'=>array('symbol'=>'Bs.'),
            'BRL*'=>array('symbol'=>'R$','format'=>'0.0,00'),
            'BSD'=>array('symbol'=>'$'),
            'BWP'=>array('symbol'=>'P'),
            'BZD'=>array('symbol'=>'BZ$'),
            'CAD'=>array('symbol'=>'$'),
            'CDF'=>array('symbol'=>'FC'),
            'CHF'=>array('symbol'=>'CHf'),
            'CLP'=>array('symbol'=>'$','format'=>'0,0'),
            'CNY'=>array('symbol'=>'¥'),
            'COP*'=>array('symbol'=>'$','format'=>'0.0,00'),
            'CRC*'=>array('symbol'=>'₡','format'=>'0.0,00'),
            'CVE*'=>array('symbol'=>'Esc'),
            'CZK*'=>array('symbol'=>'Kč','format'=>'0.0,00'),
            'DJF'=>array('symbol'=>'Fdj'),
            'DKK'=>array('symbol'=>'kr','format'=>'0.0,00'),
            'DOP'=>array('symbol'=>'RD$'),
            'DZD'=>array('symbol'=>'دج'),
            'EGP'=>array('symbol'=>'ج.م'),
            'ETB'=>array('symbol'=>'ብር'),
            'EUR'=>array('symbol'=>'€','format'=>'0.0,00'),
            'FJD'=>array('symbol'=>'$'),
            'FKP*'=>array('symbol'=>'£'),
            'GBP'=>array('symbol'=>'£'),
            'GEL'=>array('symbol'=>'ლ'),
            'GIP'=>array('symbol'=>'£'),
            'GMD'=>array('symbol'=>'D'),
            'GNF'=>array('symbol'=>'FG','format'=>'0,0'),
            'GTQ*'=>array('symbol'=>'Q'),
            'GYD'=>array('symbol'=>'$'),
            'HKD'=>array('symbol'=>'$'),
            'HNL*'=>array('symbol'=>'L'),
            'HRK'=>array('symbol'=>'kn','format'=>'0.0,00'),
            'HTG'=>array('symbol'=>'G'),
            'HUF*'=>array('symbol'=>'Ft','format'=>'0.000'),
            'IDR'=>array('symbol'=>'Rp','format'=>'0.0,00'),
            'ILS'=>array('symbol'=>'₪'),
            'INR*'=>array('symbol'=>'₹'),
            'ISK'=>array('symbol'=>'kr','format'=>'0'),
            'JMD'=>array('symbol'=>'J$'),
            'JPY'=>array('symbol'=>'¥'),
            'KES'=>array('symbol'=>'Ksh'),
            'KGS'=>array('symbol'=>'Лв'),
            'KHR'=>array('symbol'=>'៛'),
            'KMF'=>array('symbol'=>'CF'),
            'KRW'=>array('symbol'=>'₩'),
            'KYD'=>array('symbol'=>'$'),
            'KZT'=>array('symbol'=>'₸'),
            'LAK*'=>array('symbol'=>'₭'),
            'LBP'=>array('symbol'=>'ل.ل.‎'),
            'LKR'=>array('symbol'=>'රු'),
            'LRD'=>array('symbol'=>'$'),
            'LSL'=>array('symbol'=>'L'),
            'MAD'=>array('symbol'=>'د.م.'),
            'MDL'=>array('symbol'=>'L'),
            'MGA'=>array('symbol'=>'Ar'),
            'MKD'=>array('symbol'=>'Ден'),
            'MMK'=>array('symbol'=>'K'),
            'MNT'=>array('symbol'=>'₮'),
            'MOP'=>array('symbol'=>'MOP$'),
            'MRO'=>array('symbol'=>'UM'),
            'MUR*'=>array('symbol'=>'₨'),
            'MVR'=>array('symbol'=>'Rf'),
            'MWK'=>array('symbol'=>'MK'),
            'MXN'=>array('symbol'=>'$'),
            'MYR'=>array('symbol'=>'RM'),
            'MZN'=>array('symbol'=>'MT'),
            'NAD'=>array('symbol'=>'$'),
            'NGN'=>array('symbol'=>'₦'),
            'NIO*'=>array('symbol'=>'C$'),
            'NOK'=>array('symbol'=>'kr'),
            'NPR'=>array('symbol'=>'रू'),
            'NZD'=>array('symbol'=>'NZ$'),
            'PAB*'=>array('symbol'=>'B/.'),
            'PEN*'=>array('symbol'=>'S/'),
            'PGK'=>array('symbol'=>'K'),
            'PHP'=>array('symbol'=>'₱'),
            'PKR'=>array('symbol'=>'₨'),
            'PLN'=>array('symbol'=>'zł'),
            'PYG'=>array('symbol'=>'₲'),
            'QAR'=>array('symbol'=>'ر.ق'),
            'RON'=>array('symbol'=>'lei'),
            'RSD'=>array('symbol'=>'din'),
            'RUB'=>array('symbol'=>'₽'),
            'RWF'=>array('symbol'=>'R₣'),
            'SAR'=>array('symbol'=>'﷼‎'),
            'SBD'=>array('symbol'=>'$'),
            'SCR'=>array('symbol'=>'SR'),
            'SEK'=>array('symbol'=>'kr'),
            'SGD'=>array('symbol'=>'$'),
            'SHP*'=>array('symbol'=>'£'),
            'SLL'=>array('symbol'=>'Le'),
            'SOS'=>array('symbol'=>'S'),
            'SRD*'=>array('symbol'=>'$'),
            'STD*'=>array('symbol'=>'Db'),
            'SZL'=>array('symbol'=>'E'),
            'THB'=>array('symbol'=>'฿'),
            'TJS'=>array('symbol'=>'ЅM'),
            'TOP'=>array('symbol'=>'PT'),
            'TRY'=>array('symbol'=>'₺'),
            'TTD'=>array('symbol'=>'TT$'),
            'TWD'=>array('symbol'=>'NT$'),
            'TZS'=>array('symbol'=>'TSh'),
            'UAH'=>array('symbol'=>'₴'),
            'UGX'=>array('symbol'=>'USh'),
            'UYU*'=>array('symbol'=>'$U'),
            'UZS'=>array('symbol'=>'so’m'),
            'VND'=>array('symbol'=>'₫','format'=>'0'),
            'VUV'=>array('symbol'=>'VT'),
            'WST'=>array('symbol'=>'SAT'),
            'XAF'=>array('symbol'=>'FCFA'),
            'XCD'=>array('symbol'=>'$'),
            'XOF'=>array('symbol'=>'CFA'),
            'XPF'=>array('symbol'=>'₣'),
            'YER'=>array('symbol'=>'﷼'),
            'ZAR'=>array('symbol'=>'R'),
            'ZMW'=>array('symbol'=>'ZK','format'=>'0.00')


            // Default = #,###.## = ;0,0.00

            // Secondary = #.###,## = ;0.0,00
            // ARS - Argentine Peso
            // BRL - Brazilian Real
            // COP - Colombian Peso
            // CRC - Costa Rican Colon
            // HRK - Croatian Kuna
            // CYP - Cyprus Pound <<<<<
            // CZK - Czech Koruna
            // DKK - Danish Krone
            // IDR - Indonesia, Rupiah
            // MZM - Mozambique Metical <<<<<<
            // ANG - Netherlands Antillian Guilder
            // NOK - Norwegian Krone
            // UYU - Peso Uruguayo
            // RON - Romania, New Leu
            // ROL - Romania, Old Leu <<<<<<
            // RUB - Russian Ruble
            // SIT - Slovenia, Tolar <<<<<<<
            // VES - Venezuela Bolivares Fuertes <<<<<<

            // Uncommon = #,###.### ;0,0.000
            // BHD - Bahraini Dinar <<<<<<<
            // JOD - Jordanian Dinar <<<<<<<
            // KWD - Kuwaiti Dinar <<<<<<<
            // OMR - Rial Omani

            // Zero decimals
            // BYN - Belarussian Ruble
            // BYR - Belarussian Ruble <<<<<
            // BIF - Burundi Franc <<<<
            // XPF - CFP Franc <<<<
            // CLP - Chilean Peso
            // KMF - Comoro Franc <<<
            // DJF - Djibouti Franc <<<
            // HUF - Hungary, Forint
            // ISK - Iceland Krona
            // JPY - Japan, Yen
            // MGA - Malagasy Ariary <<<
            // MZN - Mozambique Metical
            // PYG - Paraguay, Guarani
            // RWF - Rwanda Franc <<<
            // KRW - South Korea, Won
            // VUV - Vanuatu, Vatu <<<

            // 3 decimals
            // IQD - Iraqi Dinar
            // LYD - Libyan Dinar
            // TND - Tunisian Dinar
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

            add_action( 'super_before_redirect_action', array( $this, 'redirect_to_stripe_checkout' ) );      

            add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
            add_action( 'init', array( $this, 'register_post_types' ), 5 );
            add_filter( 'super_shortcodes_after_form_elements_filter', array( $this, 'add_stripe_element' ), 10, 2 );
            add_action( 'wp_head', array( $this, 'stripe_ipn'));
            if ( $this->is_request( 'admin' ) ) {
                add_filter( 'super_create_form_tabs', array( $this, 'add_tab' ), 10, 1 );
                add_action( 'super_create_form_stripe_tab', array( $this, 'add_tab_content' ) );

                add_filter( 'manage_super_stripe_txn_posts_columns', array( $this, 'super_stripe_txn_columns' ), 999999 );
                add_action( 'manage_super_stripe_txn_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );
                add_action( 'manage_super_stripe_sub_posts_custom_column', array( $this, 'super_custom_columns' ), 10, 2 );

                add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
                add_filter( 'super_settings_after_custom_js_filter', array( $this, 'add_settings' ), 10, 2 );

                add_action( 'current_screen', array( $this, 'after_screen' ), 0 );
                add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 1 );
                
                add_action( 'after_contact_entry_metabox_hook', array( $this, 'add_transaction_link' ), 0 );

                add_filter( 'views_edit-super_stripe_txn', array( $this, 'delete_list_views_filter' ), 10, 1 );
            }

            if ( $this->is_request( 'ajax' ) ) {
				add_action( 'super_before_email_success_msg_action', array( $this, 'stripe_conditional_checkout' ) );
            }

            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            //add_filter( 'super_redirect_url_filter', array( $this, 'stripe_redirect' ), 10, 2 );
            //add_action( 'super_front_end_posting_after_insert_post_action', array( $this, 'save_post_id' ) );
            //add_action( 'super_after_wp_insert_user_action', array( $this, 'save_user_id' ) );
            
            // add_action( 'super_stripe_webhook_payment_intent_succeeded', array( $this, 'payment_intent_succeeded' ), 10 );
            //add_action( 'super_stripe_webhook_payment_intent_created', array( $this, 'payment_intent_created' ), 10 );
            //add_action( 'super_stripe_webhook_payment_intent_payment_failed', array( $this, 'payment_intent_payment_failed' ), 10 );
            
            // Load more Transactions, Products, Customers
            add_action( 'wp_ajax_super_stripe_api_handler', array( $this, 'super_stripe_api_handler' ) );

            // Prepare payment
            add_action( 'wp_ajax_super_stripe_prepare_payment', array( $this, 'stripe_prepare_payment' ) );
            add_action( 'wp_ajax_nopriv_super_stripe_prepare_payment', array( $this, 'stripe_prepare_payment' ) );

            // Create customer
            add_action( 'wp_ajax_super_stripe_create_subscription', array( $this, 'stripe_create_subscription' ) );
            add_action( 'wp_ajax_nopriv_super_stripe_create_subscription', array( $this, 'stripe_create_subscription' ) );
            

            // Filters since 1.2.3
            add_filter( 'super_common_js_dynamic_functions_filter', array( $this, 'add_dynamic_function' ), 100, 2 );

            add_filter( 'super_form_styles_filter', array( $this, 'add_stripe_styles' ), 100, 2 );
            add_filter( 'super_enqueue_scripts', array( $this, 'super_enqueue_scripts' ), 10, 1 );
            add_filter( 'super_enqueue_styles', array( $this, 'super_enqueue_styles' ), 10, 1 );

            // Occurs whenever a pending charge is created.
            // The Charge is pending (asynchronous payments only). 
            // Nothing to do.
            // add_action( 'super_stripe_webhook_charge_pending', array( $this, 'charge_pending' ), 10, 1 );
            
            // Occurs whenever a new charge is created and is successful.
            // The Charge succeeded and the payment is complete.
            // Finalize the order and send a confirmation to the customer over email.
            add_action( 'super_stripe_webhook_charge_succeeded', array( $this, 'charge_succeeded' ), 10, 1 );
            
            
            // ...Occurs when a new PaymentIntent is created.
            // self::handlePaymentIntentCreated($paymentIntent);
            add_action( 'super_stripe_webhook_payment_intent_created', array( $this, 'payment_intent_created' ), 10, 1 );
            
            // Occurs whenever a new customer is created.
            // "id": "cus_GTvv2oy4MRV5Vh",
            // "discount": null,
            // "email": null,
            add_action( 'super_stripe_webhook_customer_created', array( $this, 'customer_created' ), 10, 1 );

            // Occurs whenever a new payment method is attached to a customer.
            add_action( 'super_stripe_webhook_payment_method_attached', array( $this, 'payment_method_attached' ), 10, 1 );
         
            // Occurs when a PaymentIntent has failed the attempt to create a source or a payment.
            // When payment is unsuccessful, you can find more details by inspecting the 
            // PaymentIntent’s `last_payment_error` property. You can notify the customer 
            // that their payment didn’t complete and encourage them to try again with a 
            // different payment method. Reuse the same PaymentIntent to continue tracking 
            // the customer’s purchase.
            add_action( 'super_stripe_webhook_payment_intent_payment_failed', array( $this, 'payment_intent_payment_failed' ), 10, 1 );
            
        }
        public static function add_tab($tabs){
            $tabs['stripe'] = esc_html__( 'Stripe', 'super-forms' );
            return $tabs;
        }
        public static function add_tab_content($atts){
            $slug = SUPER_Stripe()->add_on_slug;
            $s = self::get_default_stripe_settings();
            if(isset($atts['settings']) && isset($atts['settings']['_'.$slug])){
                $s = array_merge( $s, $atts['settings']['_'.$slug] );
            }

            // Stripe general information
            echo '<div class="sfui-notice sfui-desc">';
                echo '<strong>'.esc_html__('Note', 'super-forms').':</strong> ' . sprintf( esc_html__( 'Make sure to enter your Stripe API credentials via %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url()) . 'admin.php?page=super_settings#stripe-checkout">', '</a>' );
            echo '</div>';
            echo '<div class="sfui-notice sfui-desc">';
                echo '<strong>'.esc_html__('Tip', 'super-forms').':</strong> ' . sprintf( esc_html__( 'You can use field {tags} to configure the below settings based on user input.', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url()) . 'admin.php?page=super_settings#stripe-checkout">', '</a>' );
            echo '</div>';
            
            // Enable Stripe checkout
            echo '<div class="sfui-setting">';
                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                    echo '<input type="checkbox" name="enabled" value="true"' . ($s['enabled']==='true' ? ' checked="checked"' : '') . ' />';
                    echo '<span class="sfui-title">' . esc_html__( 'Enable Stripe checkout for this form', 'super-forms' ) . '</span>';
                echo '</label>';
                echo '<div class="sfui-sub-settings" data-f="enabled;true">';
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'The mode of the Checkout Session', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Possible values', 'super-forms' ) . ': <code>payment</code> <code>subscription</code> <code>setup</code></span>';
                            echo '<input type="text" name="mode" placeholder="e.g: payment" value="' . sanitize_text_field($s['mode']) . '" />';
                        echo '</label>';
                    echo '</div>';
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Submit type', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Describes the type of transaction being performed by Checkout in order to customize relevant text on the page, such as the submit button. Possible values', 'super-forms' ) . ': <code>auto</code> <code>pay</code> <code>book</code> <code>donate</code></span>';
                            echo '<input type="text" name="submit_type" placeholder="e.g: donate" value="' . sanitize_text_field($s['submit_type']) . '" />';
                        echo '</label>';
                    echo '</div>';
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Payment methods', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'A list of the types of payment methods (e.g., card) this Checkout Session can accept. Separate each by comma.', 'super-forms' ) . ' ' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>card</code> <code>acss_debit</code> <code>afterpay_clearpay</code> <code>alipay</code> <code>au_becs_debit</code> <code>bacs_debit</code> <code>bancontact</code> <code>boleto</code> <code>eps</code> <code>fpx</code> <code>giropay</code> <code>grabpay</code> <code>ideal</code> <code>klarna</code> <code>konbini</code> <code>oxxo</code> <code>p24</code> <code>paynow</code> <code>sepa_debit</code> <code>sofort</code> <code>us_bank_account</code> <code>wechat_pay</code></span>';
                            echo '<input type="text" name="payment_method_types" placeholder="e.g: card,ideal" value="' . sanitize_text_field($s['payment_method_types']) . '" />';
                        echo '</label>';
                    echo '</div>';
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Customer E-mail', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'If provided, this value will be used when the Customer object is created. If not provided, customers will be asked to enter their email address. Use this parameter to prefill customer data if you already have an email on file.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="customer_email" placeholder="e.g: {email}" value="' . sanitize_text_field($s['customer_email']) . '" />';
                        echo '</label>';
                    echo '</div>';

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Enable automatic Tax', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Accepted value are', 'super-forms' ) . ': <code>true</code> or <code>false</code></span>';
                            echo '<input type="text" name="automatic_tax.enabled" placeholder="e.g: true" value="' . sanitize_text_field($s['automatic_tax']['enabled']) . '" />';
                        echo '</label>';
                    echo '</div>';
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Enable Tax ID collection (allows users to purchase as a business)', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Accepted value are', 'super-forms' ) . ': <code>true</code> or <code>false</code></span>';
                            echo '<input type="text" name="tax_id_collection.enabled" placeholder="e.g: true" value="' . sanitize_text_field($s['tax_id_collection']['enabled']) . '" />';
                        echo '</label>';
                    echo '</div>';

                    //if(empty($s['metadata'])) $s['metadata'] = '';
                    //if(empty($s['payment_method_types'])) $s['payment_method_types'] = 'card';
                    //if(empty($s['automatic_tax'])) $s['automatic_tax'] = array(
                    //    'enabled' => 'true',
                    //);

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Define checkout line items', 'super-forms' ) . '</span>';
                        echo '</label>';
                        echo '<div class="sfui-repeater" data-k="line_items">';
                        // Repeater Item
                        foreach( $s['line_items'] as $k => $v ) {
                            echo '<div class="sfui-repeater-item">';
                                echo '<div class="sfui-setting sfui-vertical" style="flex:1;">';
                                    echo '<label>';
                                        echo '<span class="sfui-title">' . esc_html__( 'Quantity:', 'super-forms' ) . '</span>';
                                        echo '<input type="text" name="quantity" placeholder="' . esc_html__( 'e.g: 1', 'super-forms' ) . '" value="' . sanitize_text_field($v['quantity']) . '" />';
                                    echo '</label>';
                                echo '</div>';

                                echo '<div class="sfui-inline sfui-vertical">';
                                    echo '<form class="sfui-setting">';
                                        // Price
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="type" value="price"' . ($v['type']==='price' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Based on existing Stripe Price or Plan ID (recommended)', 'super-forms' ) . '</span>';
                                            echo '<div class="sfui-sub-settings sfui-inline" data-f="type;price">';
                                                echo '<div class="sfui-setting sfui-inline">';
                                                    // Price or Plan ID
                                                    echo '<div class="sfui-setting sfui-vertical" style="flex:1;">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . esc_html__( 'Enter product price/plan ID.', 'super-forms' ) . ' ' . sprintf( esc_html__( 'You can create a new product and price via the Stripe %sDashboard%s.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/products">', '</a>' ) . '</span>';
                                                            echo '<input type="text" name="price" placeholder="' . esc_html__( 'e.g: price_XXXX', 'super-forms' ) . '" value="' . sanitize_text_field($v['price']) . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                echo '</div>';
                                            echo '</div>';
                                        echo '</label>';
                                        // Price data
                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                            echo '<input type="radio" name="type" value="price_data"' . ($v['type']==='price_data' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Create new price object', 'super-forms' ) . '</span>';
                                            echo '<div class="sfui-sub-settings sfui-vertical" data-f="type;price_data">';
                                                echo '<div class="sfui-inline sfui-vertical">';
                                                    echo '<div class="sfui-setting">';
                                                        // Existing Product
                                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                                            echo '<input type="radio" name="price_data.type" value="product"' . ($v['price_data']['type']==='product' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Use an existing product ID which the price will belong to', 'super-forms' ) . '</span>';
                                                            echo '<div class="sfui-sub-settings sfui-inline" data-f="price_data.type;product">';
                                                                // Product ID
                                                                echo '<div class="sfui-setting sfui-vertical" style="flex:1;">';
                                                                    echo '<label>';
                                                                        echo '<span class="sfui-label">' . esc_html__( 'Enter an already existing Stripe product ID that this price will belong to', 'super-forms' ) . '</span>';
                                                                        echo '<input type="text" name="price_data.product" placeholder="e.g: prod_XXXXXXXXXXXXXX" value="' . sanitize_text_field($v['price_data']['product']) . '" />';
                                                                    echo '</label>';
                                                                echo '</div>';
                                                            echo '</div>';
                                                        echo '</label>';
                                                        // New product data
                                                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                                            echo '<input type="radio" name="price_data.type" value="product_data"' . ($v['price_data']['type']==='product_data' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Create new Stripe product on the fly', 'super-forms' ) . '</span>';
                                                            echo '<div class="sfui-sub-settings sfui-inline" data-f="price_data.type;product_data">';
                                                                echo '<div class="sfui-setting">';
                                                                    // Product data
                                                                    echo '<div class="sfui-vertical">';
                                                                        echo '<label>';
                                                                            echo '<span class="sfui-label">' . esc_html__( 'Product name', 'super-forms' ) . '</span>';
                                                                            echo '<input type="text" name="price_data.product_data.name" placeholder="e.g: Notebook" value="' . sanitize_text_field($v['price_data']['product_data']['name']) . '" />';
                                                                        echo '</label>';
                                                                    echo '</div>';
                                                                    echo '<div class="sfui-vertical">';
                                                                        echo '<label>';
                                                                            echo '<span class="sfui-label">' . esc_html__( 'Description', 'super-forms' ) . '</span>';
                                                                            echo '<input type="text" name="price_data.product_data.description" placeholder="Intel i7, 8GB RAM" value="' . sanitize_text_field($v['price_data']['product_data']['description']) . '" />';
                                                                        echo '</label>';
                                                                    echo '</div>';
                                                                    echo '<div class="sfui-vertical">';
                                                                        echo '<label>';
                                                                            echo '<span class="sfui-label">' . sprintf( esc_html__( 'Tax category code ID. %sFind a tax category%s. Your default tax category is used if you don’t provide one when creating a transaction with Stripe Tax enabled. You can update this in your %stax settings%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/tax/tax-categories">', '</a>', '<a target="_blank" href="https://dashboard.stripe.com/settings/tax">', '</a>' ) . '</span>';
                                                                            echo '<input type="text" name="price_data.product_data.tax_code" placeholder="e.g: txcd_99999999 (Tangible Goods)" value="' . sanitize_text_field($v['price_data']['product_data']['tax_code']) . '" />';
                                                                        echo '</label>';
                                                                    echo '</div>';
                                                                echo '</div>';
                                                            echo '</div>';
                                                        echo '</label>';
                                                    echo '</div>';
                                                echo '</div>';

                                                echo '<div class="sfui-setting sfui-vertical">';
                                                    echo '<label>';
                                                        echo '<span class="sfui-title">' . esc_html__( 'Unit amount', 'super-forms' ) . '</span>';
                                                        echo '<span class="sfui-label">' . esc_html__( 'Define a price as float value (only dot is accepted as decimal seperator)', 'super-forms' ) . '</span>';
                                                        echo '<input type="text" name="price_data.unit_amount_decimal" placeholder="e.g: 65.95" value="' . sanitize_text_field($v['price_data']['unit_amount_decimal']) . '" />';
                                                    echo '</label>';
                                                echo '</div>';
                                                echo '<div class="sfui-setting sfui-vertical">';
                                                    echo '<label>';
                                                        echo '<span class="sfui-title">' . esc_html__( 'Currency', 'super-forms' ) . '</span>';
                                                        echo '<span class="sfui-label">' . sprintf( esc_html__( 'Three-letter ISO currency code. Must be a %ssupported currency%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ) . '</span>';
                                                        echo '<input type="text" name="price_data.currency" placeholder="e.g: USD or EUR" value="' . sanitize_text_field($v['price_data']['currency']) . '" />';
                                                        echo '<div class="sfui-notice sfui-desc">';
                                                            echo '<strong>' . esc_html__('Note', 'super-forms') . ':</strong> ' . esc_html__( 'Some payment methods require a specific currency to be set for your line items. For instance, when using `ideal` the currency must be set to EUR or Stripe will return an error message.', 'super-forms' );
                                                        echo '</div>';
                                                    echo '</label>';
                                                echo '</div>';
                                                echo '<div class="sfui-setting sfui-vertical">';
                                                    echo '<label>';
                                                        echo '<span class="sfui-title">' . esc_html__( 'Tax behavior', 'super-forms' ) . '</span>';
                                                        echo '<span class="sfui-label">' . esc_html__( 'Specifies whether the price is considered inclusive of taxes or exclusive of taxes. One of inclusive, exclusive, or unspecified. Once specified as either inclusive or exclusive, it cannot be changed.', 'super-forms' ) . '</span>';
                                                        echo '<span class="sfui-label">' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>inclusive</code> <code>exclusive</code> <code>unspecified</code></span>';
                                                        echo '<input type="text" name="price_data.tax_behavior" placeholder="e.g: exclusive" value="' . sanitize_text_field($v['price_data']['tax_behavior']) . '" />';
                                                    echo '</label>';
                                                echo '</div>';
                                                echo '<div class="sfui-setting sfui-vertical">';
                                                    echo '<label>';
                                                        echo '<span class="sfui-title">' . esc_html__( 'Specify billing frequency (defaults to `none` for one-time payments)', 'super-forms' ) . '</span>';
                                                        echo '<span class="sfui-label">' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>none</code> <code>day</code> <code>week</code> <code>month</code> <code>year</code></span>';
                                                        echo '<input type="text" name="price_data.recurring.interval" placeholder="e.g: month" value="' . sanitize_text_field($v['price_data']['recurring']['interval']) . '" />';
                                                    echo '</label>';
                                                echo '</div>';
                                                echo '<div class="sfui-setting sfui-vertical">';
                                                    echo '<label>';
                                                        echo '<span class="sfui-title">' . esc_html__( 'The number of intervals between subscription billings.', 'super-forms' ) . '</span>';
                                                        echo '<span class="sfui-label">' . esc_html__( 'Maximum of one year interval allowed (1 year, 12 months, or 52 weeks).', 'super-forms' ) . '</span>';
                                                        echo '<input type="text" name="price_data.recurring.interval_count" placeholder="Enter the number of intervals" value="' . sanitize_text_field($v['price_data']['recurring']['interval_count']) . '" />';
                                                    echo '</label>';
                                                echo '</div>';
                                            echo '</div>';
                                        echo '</label>';
                                    echo '</form>';
                                echo '</div>';

                                echo '<div class="sfui-setting">';
                                    echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                        echo '<input type="checkbox" name="custom_tax_rate" value="true"' . ($v['custom_tax_rate']==='true' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Apply custom Tax Rates', 'super-forms' ) . ' (' . esc_html__( 'optional', 'super-forms' ) . ')</span>';
                                    echo '</label>';
                                    echo '<div class="sfui-sub-settings sfui-vertical" data-f="custom_tax_rate;true">';
                                        echo '<label>';
                                            echo '<span class="sfui-title">' . esc_html__( 'Tax rates:', 'super-forms' ) . '</span>';
                                            echo '<span class="sfui-label">' . sprintf( esc_html__( 'Seperate each rate with a comma. You can manage and create Tax Rates in via the Stripe %sDashboard%s.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/test/tax-rates">', '</a>' ) . '</span>';
                                            echo '<input type="text" name="tax_rates" placeholder="' . esc_html__( 'e.g: txr_1LmUhbFKn7uROhgCwnWwpN9p', 'super-forms' ) . '" value="' . sanitize_text_field($v['tax_rates']) . '" />';
                                        echo '</label>';
                                    echo '</div>';
                                echo '</div>';

                                echo '<div style="margin-left:10px;" class="sfui-btn sfui-green sfui-round sfui-tooltip" title="' . esc_attr__( 'Add item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Add item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'addRepeaterItem\')"><i class="fas fa-plus"></i></div>';
                                echo '<div style="margin-left:0px;" class="sfui-btn sfui-red sfui-round sfui-tooltip" title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" data-title="' . esc_attr__( 'Delete item', 'super-forms' ) .'" onclick="SUPER.ui.btn(event, this, \'deleteRepeaterItem\')"><i class="fas fa-trash"></i></div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    echo '</div>';

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Apply discount', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . sprintf( esc_html__( 'You can create coupons easily via the %coupon management%s page of the Stripe dashboard.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/coupons">', '</a>' ) . '</span>';
                        echo '</label>';
                        echo '<div class="sfui-inline sfui-vertical">';
                            echo '<form class="sfui-setting">';
                                // Discount Options
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="discounts.type" value="none"' . ($s['discounts']['type']==='none' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Do not apply any discount', 'super-forms' ) . '</span>';
                                echo '</label>';
                                // Coupon ID
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="discounts.type" value="existing_coupon"' . ($s['discounts']['type']==='existing_coupon' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Based on existing Stripe Coupon ID', 'super-forms' ) . '</span>';
                                    echo '<div class="sfui-sub-settings sfui-inline" data-f="discounts.type;existing_coupon">';
                                        echo '<div class="sfui-setting sfui-inline">';
                                            echo '<div class="sfui-setting sfui-vertical" style="flex:1;">';
                                                echo '<label>';
                                                    echo '<span class="sfui-label">' . esc_html__( 'Enter Coupon ID:', 'super-forms' ) . '</span>';
                                                    echo '<input type="text" name="discounts.coupon" placeholder="' . esc_html__( 'e.g: SbwGtc0x', 'super-forms' ) . '" value="' . sanitize_text_field($s['discounts']['coupon']) . '" />';
                                                echo '</label>';
                                            echo '</div>';
                                        echo '</div>';
                                    echo '</div>';
                                echo '</label>';
                                // Promotion ID
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="discounts.type" value="existing_promotion_code"' . ($s['discounts']['type']==='existing_promotion_code' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Based on existing Stripe Promotion ID', 'super-forms' ) . '</span>';
                                    echo '<div class="sfui-sub-settings sfui-inline" data-f="discounts.type;existing_promotion_code">';
                                        echo '<div class="sfui-setting sfui-inline">';
                                            echo '<div class="sfui-setting sfui-vertical" style="flex:1;">';
                                                echo '<label>';
                                                    echo '<span class="sfui-label">' . esc_html__( 'Enter Promotion ID:', 'super-forms' ) . '</span>';
                                                    echo '<input type="text" name="discounts.promotion_code" placeholder="' . esc_html__( 'e.g: SbwGtc0x', 'super-forms' ) . '" value="' . sanitize_text_field($s['discounts']['promotion_code']) . '" />';
                                                echo '</label>';
                                            echo '</div>';
                                        echo '</div>';
                                    echo '</div>';
                                echo '</label>';
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="discounts.type" value="new"' . ($s['discounts']['type']==='new' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Create new coupon on the fly', 'super-forms' ) . '</span>';
                                    echo '<div class="sfui-sub-settings sfui-inline" data-f="discounts.type;new">';
                                        echo '<div class="sfui-inline sfui-vertical">';
                                            echo '<div class="sfui-setting">';
                                                echo '<label>';
                                                    echo '<span class="sfui-label">' . esc_html__( 'Name of the coupon displayed to customers on, for instance invoices, or receipts. By default the id is shown if name is not set.', 'super-forms' ) . '</span>';
                                                    echo '<input type="text" name="discounts.new.name" placeholder="FALLDISCOUNT" value="' . sanitize_text_field($s['discounts']['new']['name']) . '" />';
                                                echo '</label>';
                                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                                    echo '<input type="radio" name="discounts.new.type" value="percent_off"' . ($s['discounts']['new']['type']==='percent_off' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Percent Off', 'super-forms' ) . '</span>';
                                                    echo '<div class="sfui-sub-settings sfui-vertical" data-f="discounts.new.type;percent_off">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . esc_html__( 'Value larger tahn 0, and smaller or equal to 100 that represents the discount the coupon will apply.', 'super-forms' ) . '</span>';
                                                            echo '<input type="text" name="discounts.new.percent_off" placeholder="e.g: 10" value="' . (isset($s['discounts']['new']['percent_off']) ? sanitize_text_field($s['discounts']['new']['percent_off']) : '') . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                echo '</label>';
                                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                                    echo '<input type="radio" name="discounts.new.type" value="amount_off"' . ($s['discounts']['new']['type']==='amount_off' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Amount Off', 'super-forms' ) . '</span>';
                                                    echo '<div class="sfui-sub-settings sfui-vertical" data-f="discounts.new.type;amount_off">';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . esc_html__( 'A positive integer representing the amount to subtract from an invoice total', 'super-forms' ) . '</span>';
                                                            echo '<input type="text" name="discounts.new.amount_off" placeholder="e.g: 50.00" value="' . (isset($s['discounts']['new']['amount_off']) ? sanitize_text_field($s['discounts']['new']['amount_off']) : '') . '" />';
                                                        echo '</label>';
                                                        echo '<label>';
                                                            echo '<span class="sfui-label">' . sprintf( esc_html__( 'Three-letter ISO currency code. Must be a %ssupported currency%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ) . '</span>';
                                                            echo '<input type="text" name="discounts.new.currency" placeholder="e.g: USD" value="' . sanitize_text_field($s['shipping_options']['shipping_rate_data']['fixed_amount']['currency']) . '" />';
                                                        echo '</label>';
                                                    echo '</div>';
                                                echo '</label>';
                                            echo '</div>';
                                        echo '</div>';
                                    echo '</div>';
                                echo '</label>';
                            echo '</form>';
                        echo '</div>';

                    echo '</div>';


                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Customer ID', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'ID of an existing Customer, if one exists. In payment mode, the customer\'s most recent card payment method will be used to prefill the email, name, card details, and billing address on the Checkout page. In subscription mode, the customer\'s default payment method will be used if it\'s a card, and otherwise the most recent card will be used. A valid billing address, billing name and billing email are required on the payment method for Checkout to prefill the customer\'s card details.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="customer" placeholder="e.g: cus_XXXXXX" value="' . sanitize_text_field($s['customer']) . '" />';
                        echo '</label>';
                    echo '</div>';
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<span class="sfui-title">' . esc_html__( 'The IETF language tag of the locale Checkout is displayed in. If blank or auto, the browser’s locale is used.', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Possible values', 'super-forms' ) . ': <code>auto</code> <code>bg</code> <code>cs</code> <code>da</code> <code>de</code> <code>el</code> <code>en</code> <code>en-GB</code> <code>es</code> <code>es-419</code> <code>et</code> <code>fi</code> <code>fil</code> <code>fr</code> <code>fr-CA</code> <code>hr</code> <code>hu</code> <code>id</code> <code>it</code> <code>ja</code> <code>ko</code> <code>lt</code> <code>lv</code> <code>ms</code> <code>mt</code> <code>nb</code> <code>nl</code> <code>pl</code> <code>pt</code> <code>pt-BR</code> <code>ro</code> <code>ru</code> <code>sk</code> <code>sl</code> <code>sv</code> <code>th</code> <code>tr</code> <code>vi</code> <code>zh</code> <code>zh-HK</code> <code>zh-TW</code></span>';
                            echo '<input type="text" name="locale" placeholder="e.g: en" value="' . sanitize_text_field($s['locale']) . '" />';
                        echo '</label>';
                    echo '</div>';

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<span class="sfui-title">' . esc_html__( 'The subscription’s description, meant to be displayable to the customer.', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Use this field to optionally store an explanation of the subscription for rendering in Stripe hosted surfaces.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="subscription_data.description" placeholder="e.g: Website updates and maintenance" value="' . sanitize_text_field($s['subscription_data']['description']) . '" />';
                        echo '</label>';
                    echo '</div>';
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                            echo '<span class="sfui-title">' . esc_html__( 'Trial period in days (only works when mode is set to `subscription`)', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Integer representing the number of trial period days before the customer is charged for the first time. Has to be at least 1.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="subscription_data.trial_period_days" placeholder="e.g: 15 (leave blank for no trial period)" value="' . sanitize_text_field($s['subscription_data']['trial_period_days']) . '" />';
                        echo '</label>';
                    echo '</div>';

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Cancel URL', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'The URL the customer will be directed to if they decide to cancel payment and return to your website.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="cancel_url" placeholder="Leave blank to redirect back to the page with the form" value="' . sanitize_text_field($s['cancel_url']) . '" />';
                        echo '</label>';
                    echo '</div>';
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Success URL', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'The URL to which Stripe should send customers when payment or setup is complete. If you\'d like to use information from the successful Checkout Session on your page, read the guide on customizing your success page.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="success_url" placeholder="Leave blank to redirect back to the page with the form" value="' . sanitize_text_field($s['success_url']) . '" />';
                        echo '</label>';
                    echo '</div>';


                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<div class="sfui-notice sfui-desc">';
                            echo '<strong>' . esc_html__('Note', 'super-forms') . ':</strong> ' . sprintf( esc_html__( 'When using any of the following payment methods %s the below E-mail will be send to the user when their payment failed. This way they are able to retry their payment without filling out the form again.', 'super-forms' ), '<code>bacs_debit</code> <code>boleto</code> <code>acss_debit</code> <code>oxxo</code> <code>sepa_debit</code> <code>sofort</code> <code>us_bank_account</code>');
                        echo '</div>';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Retry payment E-mail Subject', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="retryPaymentEmail.subject" placeholder="Payment failed" value="' . sanitize_text_field($s['retryPaymentEmail']['subject']) . '" />';
                        echo '</label>';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Retry payment E-mail Body', 'super-forms' ) . '</span>';
                            echo '<textarea name="retryPaymentEmail.body">' . $s['retryPaymentEmail']['body'] . '</textarea>';
                        echo '</label>';
                        echo '<div class="sfui-inline">';
                            echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                echo '<input type="checkbox" name="retryPaymentEmail.lineBreaks" value="true"' . ($s['retryPaymentEmail']['lineBreaks']==='true' ? ' checked="checked"' : '') . ' />';
                                echo '<span class="sfui-label">' . esc_html__( 'Automatically add line breaks (enabled by default)', 'super-forms' ) . '</span>';
                            echo '</label>';
                        echo '</div>';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Retry payment link expiry in hours', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Enter the amount in hours before the retry payment link expires. Must be a number between 24 and 720.', 'super-forms' ) . '</span>';
                            echo '<input type="number" min="24" max="720" name="retryPaymentEmail.expiry" placeholder="e.g: 24" value="' . sanitize_text_field($s['retryPaymentEmail']['expiry']) . '" />';
                        echo '</label>';
                    echo '</div>';

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Client reference ID', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'A unique string to reference the Checkout Session. This can be a customer ID, a cart ID, or similar, and can be used to reconcile the session with your internal systems.', 'super-forms' ) . '</span>';
                            echo '<input type="text" name="client_reference_id" placeholder="" value="' . sanitize_text_field($s['client_reference_id']) . '" />';
                        echo '</label>';
                    echo '</div>';
                    
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Collect customer phone number', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>false</code> <code>true</code></span>';
                            echo '<input type="text" name="phone_number_collection.enabled" value="true"' . ($s['phone_number_collection']['enabled']==='true' ? ' checked="checked"' : '') . ' />';
                        echo '</label>';
                        echo '<div class="sfui-sub-settings sfui-inline" data-f="phone_number_collection.enabled;true">';
                            echo '<div class="sfui-notice sfui-desc">';
                                echo '<strong>' . esc_html__('Note', 'super-forms') . ':</strong> ' . sprintf( esc_html__( 'We recommend that you review your privacy policy and check with your legal contacts before using this feature. Learn more about %scollecting phone numbers with Checkout%s.', 'super-forms' ), '<a href="https://stripe.com/docs/payments/checkout/phone-numbers">', '</a>');
                            echo '</div>';
                        echo '</div>';
                    echo '</div>';

                    // [optional] Specify whether Checkout should collect the customer’s billing address.
                    // `auto` Checkout will only collect the billing address when necessary.
                    // `required` Checkout will always collect the customer’s billing address.
                    //'billing_address_collection' => 'auto', 
                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'Collect customer billing address', 'super-forms' ) . '</span>';
                            echo '<span class="sfui-label">' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>auto</code> <code>required</code></span>';
                            echo '<input type="text" name="billing_address_collection" placeholder="e.g: required" value="' . sanitize_text_field($s['billing_address_collection']) . '" />';
                        echo '</label>';
                    echo '</div>';

                    echo '<div class="sfui-setting sfui-vertical">';
                        echo '<label>';
                            echo '<span class="sfui-title">' . esc_html__( 'The shipping rate options to apply to this Session.', 'super-forms' ) . '</span>';
                        echo '</label>';
                        echo '<div class="sfui-inline sfui-vertical">';
                            echo '<form class="sfui-setting">';
                                // Shipping Options
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="shipping_options.type" value="id"' . ($s['shipping_options']['type']==='id' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Based on existing Stripe Shipping Rate ID (recommended)', 'super-forms' ) . '</span>';
                                    echo '<div class="sfui-sub-settings sfui-inline" data-f="shipping_options.type;id">';
                                        echo '<div class="sfui-setting sfui-inline">';
                                            // Shipping Rate ID
                                            echo '<div class="sfui-setting sfui-vertical" style="flex:1;">';
                                                echo '<label>';
                                                    echo '<span class="sfui-label">' . esc_html__( 'Enter shipping rate ID:', 'super-forms' ) . '</span>';
                                                    echo '<input type="text" name="shipping_options.shipping_rate" placeholder="' . esc_html__( 'e.g: shr_XXXXXXXXXXXXXXXXXXXXXXXX', 'super-forms' ) . '" value="' . sanitize_text_field($s['shipping_options']['shipping_rate']) . '" />';
                                                echo '</label>';
                                            echo '</div>';
                                        echo '</div>';
                                    echo '</div>';
                                echo '</label>';
                                // Shipping Rate Data
                                echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                                    echo '<input type="radio" name="shipping_options.type" value="data"' . ($s['shipping_options']['type']==='data' ? ' checked="checked"' : '') . ' /><span class="sfui-title">' . esc_html__( 'Create new shipping rate on the fly', 'super-forms' ) . '</span>';
                                    echo '<div class="sfui-sub-settings sfui-vertical" data-f="shipping_options.type;data">';
                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<span class="sfui-title">' . esc_html__( 'Display name', 'super-forms' ) . '</span>';
                                                echo '<span class="sfui-label">' . esc_html__( 'The name of the shipping rate, meant to be displayable to the customer. This will appear on CheckoutSessions.', 'super-forms' ) . '</span>';
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.display_name" placeholder="e.g: World wide shipping" value="' . sanitize_text_field($s['shipping_options']['shipping_rate_data']['display_name']) . '" />';
                                            echo '</label>';
                                        echo '</div>';
                                        echo '<div class="sfui-setting sfui-vertical">';

                                            echo '<label>';
                                                echo '<span class="sfui-title">' . esc_html__( 'The estimated range for how long shipping will take, meant to be displayable to the customer.', 'super-forms' ) . '</span>';
                                            echo '</label>';

                                            echo '<div class="sfui-setting sfui-vertical">';
                                                echo '<span class="sfui-title">' . esc_html__( 'The upper bound of the estimated range. If empty, represents no upper bound i.e., infinite.', 'super-forms' ) . '</span>';
                                                echo '<label>';
                                                    echo '<span class="sfui-label">' . esc_html__( 'Unit.', 'super-forms' ) . ' ' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>hour</code> <code>day</code> <code>business_day</code> <code>week</code> <code>month</code></span>'; 
                                                    echo '<input type="text" name="shipping_options.shipping_rate_data.delivery_estimate.maximum.unit" placeholder="e.g: business_day" value="' . sanitize_text_field($s['shipping_options']['shipping_rate_data']['delivery_estimate']['maximum']['unit']) . '" />';
                                                echo '</label>';
                                                echo '<label>';
                                                    echo '<span class="sfui-label">' . esc_html__( 'Value (must be greater than 0)', 'super-forms' ) . '</span>';
                                                    echo '<input type="number" name="shipping_options.shipping_rate_data.delivery_estimate.maximum.value" placeholder="e.g: 5" value="' . absint($s['shipping_options']['shipping_rate_data']['delivery_estimate']['maximum']['value']) . '" />';
                                                echo '</label>';
                                            echo '</div>';
                                            echo '<div class="sfui-setting sfui-vertical">';
                                                echo '<span class="sfui-title">' . esc_html__( 'The lower bound of the estimated range. If empty, represents no lower bound.', 'super-forms' ) . '</span>';
                                                echo '<label>';
                                                    echo '<span class="sfui-label">' . esc_html__( 'Unit.', 'super-forms' ) . ' ' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>hour</code> <code>day</code> <code>business_day</code> <code>week</code> <code>month</code></span>'; 
                                                    echo '<input type="text" name="shipping_options.shipping_rate_data.delivery_estimate.minimum.unit" placeholder="e.g: business_day" value="' . sanitize_text_field($s['shipping_options']['shipping_rate_data']['delivery_estimate']['minimum']['unit']) . '" />';
                                                echo '</label>';
                                                echo '<label>';
                                                    echo '<span class="sfui-label">' . esc_html__( 'Value (must be greater than 0)', 'super-forms' ) . '</span>';
                                                    echo '<input type="number" name="shipping_options.shipping_rate_data.delivery_estimate.minimum.value" placeholder="e.g: 5" value="' . absint($s['shipping_options']['shipping_rate_data']['delivery_estimate']['minimum']['value']) . '" />';
                                                echo '</label>';
                                            echo '</div>';
                                        echo '</div>';

                                        echo '<div class="sfui-setting sfui-vertical">';
                                            echo '<label>';
                                                echo '<span class="sfui-title">' . esc_html__( 'Amount to charge for shipping', 'super-forms' ) . '</span>';
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.fixed_amount.amount" placeholder="e.g: 6.99" value="' . sanitize_text_field($s['shipping_options']['shipping_rate_data']['fixed_amount']['amount']) . '" />';
                                            echo '</label>';
                                            echo '<label>';
                                                echo '<span class="sfui-title">' . esc_html__( 'Shipping currency', 'super-forms' ) . '</span>';
                                                echo '<span class="sfui-label">' . sprintf( esc_html__( 'Three-letter ISO currency code. Must be a %ssupported currency%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ) . '</span>';
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.fixed_amount.currency" placeholder="e.g: USD" value="' . sanitize_text_field($s['shipping_options']['shipping_rate_data']['fixed_amount']['currency']) . '" />';
                                            echo '</label>';
                                            echo '<label>';
                                                echo '<span class="sfui-title">' . esc_html__( 'Shipping tax behavior', 'super-forms' ) . '</span>';
                                                echo '<span class="sfui-label">' . esc_html__( 'Specifies whether the rate is considered inclusive of taxes or exclusive of taxes.', 'super-forms' ) . ' ' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>inclusive</code> <code>exclusive</code> <code>unspecified</code></span>'; 
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.tax_behavior" placeholder="e.g: inclusive" value="' . sanitize_text_field($s['shipping_options']['shipping_rate_data']['tax_behavior']) . '" />';
                                            echo '</label>';
                                            echo '<label>';
                                                echo '<span class="sfui-title">' . esc_html__( 'Shipping tax code', 'super-forms' ) . '</span>';
                                                echo '<span class="sfui-label">' . esc_html__( 'A tax code ID. The Shipping tax code is', 'super-forms' ) . ': <code>txcd_92010001</code></span>';
                                                echo '<input type="text" name="shipping_options.shipping_rate_data.tax_code" placeholder="e.g: inclusive" value="' . sanitize_text_field($s['shipping_options']['shipping_rate_data']['tax_code']) . '" />';
                                            echo '</label>';
                                        echo '</div>';

                                    echo '</div>';
                                echo '</label>';
                            echo '</form>';
                        echo '</div>';

                    echo '</div>';




                    // [optional] The shipping rate options to apply to this Session.
                    //'shipping_options' => [
                    //    'shipping_rate' => '', // The ID of the Shipping Rate to use for this shipping option.
                    //    'shipping_rate_data' => [ // Parameters to be passed to Shipping Rate creation for this shipping option
                    //        'display_name' => '', // The name of the shipping rate, meant to be displayable to the customer. This will appear on CheckoutSessions.
                    //        'type' => 'fixed_amount', // The type of calculation to use on the shipping rate. Can only be fixed_amount for now.
                    //        'delivery_estimate' => [ // The estimated range for how long shipping will take, meant to be displayable to the customer. This will appear on CheckoutSessions.
                    //            'maximum' => [ // The upper bound of the estimated range. If empty, represents no upper bound i.e., infinite.
                    //                'unit' => 'business_day', // hour, day, business_day, week, month
                    //                'value' => 5,
                    //            ],
                    //            'minimum' => [ // The lower bound of the estimated range. If empty, represents no lower bound.
                    //                'unit' => 'business_day', // hour, day, business_day, week, month
                    //                'value' => 2,
                    //            ]
                    //        ],
                    //        'fixed_amount' => [
                    //            'amount' => 3000, // A non-negative integer in cents representing how much to charge.
                    //            'currency' => 'usd' // Three-letter ISO currency code. Must be a supported currency.
                    //        ]
                    //        'metadata' => [], // Set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format. Individual keys can be unset by posting an empty value to them. All keys can be unset by posting an empty value to metadata.
                    //        'tax_behavior' => 'exclusive' // Specifies whether the rate is considered inclusive of taxes or exclusive of taxes. One of inclusive, exclusive, or unspecified.
                    //        'tax_code' => 'txcd_92010001', // A tax code ID. The Shipping tax code is txcd_92010001.
                    //    ]
                    //],



                    
                    // not used echo '<div class="sfui-setting sfui-vertical">';
                    // not used     echo '<div class="sfui-notice sfui-desc">';
                    // not used         echo '<strong>' . esc_html__('Note', 'super-forms') . ':</strong> ' . sprintf( esc_html__( '%sStripe Connect%s is required for the below settings to work', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/connect">', '</a>' );
                    // not used     echo '</div>';
                    // not used     echo '<div class="sfui-setting sfui-vertical">';
                    // not used         echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                    // not used             echo '<span class="sfui-title">' . esc_html__( 'Application fee percent (enter a non-negative decimal between 0 and 100, with at most two decimal places)', 'super-forms' ) . '</span>';
                    // not used             echo '<span class="sfui-label">' . esc_html__( 'This represents the percentage of the subscription invoice subtotal that will be transferred to the application owner’s Stripe account. To use an application fee percent, the request must be made on behalf of another account, using the Stripe-Account header or an OAuth key. For more information, see the application fees documentation.', 'super-forms' ) . '</span>';
                    // not used             echo '<input type="text" name="subscription_data.application_fee_percent" placeholder="e.g: 30" value="' . sanitize_text_field($s['subscription_data']['application_fee_percent']) . '" />';
                    // not used         echo '</label>';
                    // not used     echo '</div>';
                    // not used     echo '<div class="sfui-setting sfui-vertical">';
                    // not used         echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                    // not used             echo '<span class="sfui-title">' . esc_html__( 'ID of an existing, connected Stripe account.', 'super-forms' ) . '</span>';
                    // not used             echo '<input type="text" name="subscription_data.transfer_data.destination" placeholder="e.g: acct_1D1FNjFKn7uROhgC" value="' . sanitize_text_field($s['subscription_data']['transfer_data']['destination']) . '" />';
                    // not used         echo '</label>';
                    // not used     echo '</div>';
                    // not used     echo '<div class="sfui-setting sfui-vertical">';
                    // not used         echo '<label onclick="SUPER.ui.updateSettings(event, this)">';
                    // not used             echo '<span class="sfui-title">' . esc_html__( 'Amount percent (enter a non-negative decimal between 0 and 100, with at most two decimal places)', 'super-forms' ) . '</span>';
                    // not used             echo '<span class="sfui-label">' . esc_html__( 'This represents the percentage of the subscription invoice subtotal that will be transferred to the destination account. By default, the entire amount is transferred to the destination.', 'super-forms' ) . '</span>';
                    // not used             echo '<input type="text" name="subscription_data.transfer_data.amount_percent" placeholder="e.g: 100" value="' . sanitize_text_field($s['subscription_data']['transfer_data']['amount_percent']) . '" />';
                    // not used         echo '</label>';
                    // not used     echo '</div>';
                    // not used echo '</div>';


                echo '</div>';
            echo '</div>';


        }
        // Get default listing settings
        public static function get_default_stripe_settings($s=array()) {
            if(empty($s['enabled'])) $s['enabled'] = 'false';
            if(empty($s['mode'])) $s['mode'] = 'payment'; // The mode of the Checkout Session. Required when using prices or setup mode. Pass subscription if the Checkout Session includes at least one recurring item.
            if(empty($s['submit_type'])) $s['submit_type'] = 'auto'; // Describes the type of transaction being performed by Checkout in order to customize relevant text on the page, such as the submit button. submit_type can only be specified on Checkout Sessions in payment mode, but not Checkout Sessions in subscription or setup mode.
            if(empty($s['cancel_url'])) $s['cancel_url'] = ''; // The URL the customer will be directed to if they decide to cancel payment and return to your website.
            if(empty($s['success_url'])) $s['success_url'] = ''; // The URL to which Stripe should send customers when payment or setup is complete. If you’d like to use information from the successful Checkout Session on your page, read the guide on customizing your success page.
            if(empty($s['customer'])) $s['customer'] = ''; // ID of an existing Customer, if one exists. In payment mode, the customer’s most recent card payment method will be used to prefill the email, name, card details, and billing address on the Checkout page. In subscription mode, the customer’s default payment method will be used if it’s a card, and otherwise the most recent card will be used. A valid billing address, billing name and billing email are required on the payment method for Checkout to prefill the customer’s card details.
            if(empty($s['customer_email'])) $s['customer_email'] = ''; // If provided, this value will be used when the Customer object is created. If not provided, customers will be asked to enter their email address. Use this parameter to prefill customer data if you already have an email on file. To access information about the customer once a session is complete, use the customer field.
            if(empty($s['client_reference_id'])) $s['client_reference_id'] = ''; // A unique string to reference the Checkout Session. This can be a customer ID, a cart ID, or similar, and can be used to reconcile the session with your internal systems.
            if(empty($s['metadata'])) $s['metadata'] = '';
            if(empty($s['payment_method_types'])) $s['payment_method_types'] = 'card';
            if(empty($s['automatic_tax'])) $s['automatic_tax'] = array(
                'enabled' => 'true',
            );
            if(empty($s['tax_id_collection'])) $s['tax_id_collection'] = array(
                'enabled' => 'true',
            );
            if(empty($s['discounts'])) $s['discounts'] = array(
                'type' => 'none',
                'coupon' => '',
                'promotion_code' => '',
                'new' => array(
                    'type' => 'percent_off',
                    'amount_off' => '',
                    'percent_off' => '',
                    'currency' => '',
                    'name' => ''
                )
            );
            if(empty($s['subscription_data'])) $s['subscription_data'] = array(
                'description' => '', // The subscription’s description, meant to be displayable to the customer. Use this field to optionally store an explanation of the subscription for rendering in Stripe hosted surfaces.
                'trial_period_days' => '', // Integer representing the number of trial period days before the customer is charged for the first time. Has to be at least 1.
                // not used 'default_tax_rates' => '', // The tax rates that will apply to any subscription item that does not have tax_rates set. Invoices created will have their default_tax_rates populated from the subscription.
                // not used // Stripe Connect is required https://stripe.com/docs/connect
                // not used 'application_fee_percent' => '', // A non-negative decimal between 0 and 100, with at most two decimal places. This represents the percentage of the subscription invoice subtotal that will be transferred to the application owner’s Stripe account. To use an application fee percent, the request must be made on behalf of another account, using the Stripe-Account header or an OAuth key. For more information, see the application fees documentation.
                // not used 'transfer_data' => array( // If specified, the funds from the subscription’s invoices will be transferred to the destination and the ID of the resulting transfers will be found on the resulting charges.
                // not used     'destination' => '', // ID of an existing, connected Stripe account.
                // not used     'amount_percent' => ''  // A non-negative decimal between 0 and 100, with at most two decimal places. This represents the percentage of the subscription invoice subtotal that will be transferred to the destination account. By default, the entire amount is transferred to the destination.
                // not used ),
                // not used // 'metadata' => array()
            );

            if(empty($s['locale'])) $s['locale'] = '';

            if(empty($s['retryPaymentEmail'])) $s['retryPaymentEmail'] = array(
                'expiry' => 48, // Defaults to 48 hours (2 days)
                'subject' => esc_html__( 'Payment failed', 'super-forms' ),
                'body' => sprintf( esc_html__( 'Payment failed please try again by clicking the below URL.%sThe below link will be valid for %s hours before your order is removed.%s%s', 'super-forms' ), "\n", '{stripe_retry_payment_expiry}', "\n\n", '<a href="{stripe_retry_payment_url}">{stripe_retry_payment_url}</a>' ),
                'lineBreaks' => 'true'
            );

            if(empty($s['phone_number_collection'])) $s['phone_number_collection'] = array(
                'enabled' => 'false'
            );
            if(empty($s['billing_address_collection'])) $s['billing_address_collection'] = 'required';

            if(empty($s['shipping_options'])) $s['shipping_options'] = array(
                'type' => 'id',
                'shipping_rate' => '',
                'shipping_rate_data' => array(
                    'display_name' => '', // The name of the shipping rate, meant to be displayable to the customer. This will appear on CheckoutSessions.
                    // always set to `fixed_amount` no need to have this setting 'type' => 'fixed_amount', // The type of calculation to use on the shipping rate. Can only be fixed_amount for now.
                    'delivery_estimate' => array( // The estimated range for how long shipping will take, meant to be displayable to the customer. This will appear on CheckoutSessions.
                        'maximum' => array( // The upper bound of the estimated range. If empty, represents no upper bound i.e., infinite.
                            'unit' => 'week', // hour, day, business_day, week, month
                            'value' => 2
                        ),
                        'minimum' => array( // The lower bound of the estimated range. If empty, represents no lower bound.
                            'unit' => 'business_day', // hour, day, business_day, week, month
                            'value' => 2
                        )
                    ),
                    'fixed_amount' => array(
                        'amount' => '', // A non-negative integer in cents representing how much to charge.
                        'currency' => '' // Three-letter ISO currency code. Must be a supported currency.
                    ),
                    // not used 'metadata' => [], // Set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format. Individual keys can be unset by posting an empty value to them. All keys can be unset by posting an empty value to metadata.
                    'tax_behavior' => '', // Specifies whether the rate is considered inclusive of taxes or exclusive of taxes. One of inclusive, exclusive, or unspecified.
                    'tax_code' => 'txcd_92010001', // A tax code ID. The Shipping tax code is txcd_92010001.
                ),
            );

            if(empty($s['line_items'])) $s['line_items'] = array(
                array(
                    'type' => 'price', // (not a Stripe key) Type, either `price` or `price_data`
                    'price' => '', // The ID of the Price or Plan object. One of price or price_data is required.
                    'quantity' => '1', // The ID of the Price or Plan object. One of price or price_data is required.
                    'price_data' => array( // Data used to generate a new Price object inline. One of price or price_data is required.
                        'currency' => 'usd', // Three-letter ISO currency code. Must be a supported currency.
                        'type' => 'product', //product_data', // (not a Stripe key) Type either `product` or `product_data`
                        'product' => '', // The ID of the product that this price will belong to. One of product or product_data is required.
                        'product_data' => array( // Data used to generate a new product object inline. One of product or product_data is required.
                            'name' => '', // The product’s name, meant to be displayable to the customer.
                            'description' => '', // The product’s description, meant to be displayable to the customer. Use this field to optionally store a long form explanation of the product being sold for your own rendering purposes.
                            'tax_code' => '', // A tax code ID.
                            //'images' => '', // (not used by Super Forms at this moment) A list of up to 8 URLs of images for this product, meant to be displayable to the customer.
                            //'metadata' => '' // (not used by Super Forms at this moment)
                        ),
                        'unit_amount_decimal' => '10.95', // amount representing how much to charge
                        //'unit_amount' => '', // integer in cents representing how much to charge (not used by Super Forms at this moment)
                        //'metadata' => '' // (not used by Super Forms at this moment)
                        'recurring' => array( // The recurring components of a price such as `interval` and `interval_count`.
                            'interval' => 'none', // Specifies billing frequency. Either `day`, `week`, `month` or `year`.
                            'interval_count' => 1 // The number of intervals between subscription billings. For example, interval=month and interval_count=3 bills every 3 months. Maximum of one year interval allowed (1 year, 12 months, or 52 weeks).
                        ),
                        'tax_behavior' => 'unspecified' // Specifies whether the price is considered inclusive of taxes or exclusive of taxes. One of inclusive, exclusive, or unspecified. Once specified as either inclusive or exclusive, it cannot be changed.
                    ),
                    'adjustable_quantity' => array(
                        'enabled' => false, // Set to true if the quantity can be adjusted to any non-negative integer. By default customers will be able to remove the line item by setting the quantity to 0.
                        'maximum' => 99, // The maximum quantity the customer can purchase for the Checkout Session. By default this value is 99. You can specify a value up to 999.
                        'minimum' => 0 // The minimum quantity the customer must purchase for the Checkout Session. By default this value is 0.
                    ),
                    //'dynamic_tax_rates' => '' // (not used by Super Forms at this moment)
                    'quantity' => 1, // The quantity of the line item being purchased. Quantity should not be defined when recurring.usage_type=metered.
                    // 'tax_rates' => array( // The tax rates which apply to this line item.
                    //     '{{TAX_RATE_ID}}',
                    // )
                    'custom_tax_rate' => '',
                    'tax_rates' => '', // comma seperated list
                    //'display_name' => '',
                    //'description' => '',
                    //'inclusive' => 'false',
                    //'percentage' => '',
                    //'country' => '',
                    //'state' => '',
                    //'jurisdiction' => '',
                    //'tax_type' => '',
                )
            );
            //if(empty($s['title_column'])) $s['title_column'] = array(
            //    'enabled' => 'true',
            //    'name' => esc_html__( 'Title', 'super-forms' ),
            //    'filter' => array(
            //        'enabled' => 'true',
            //        'type' => 'text',
            //        'placeholder' => esc_html__( 'search...', 'super-forms' ),
            //    ),
            //    'sort' => 'true',
            //    'link' => array(
            //        'type' => 'none',
            //        'url' => ''
            //    ),
            //    'order' => 10,
            //    'width' => 150
            //);
            //if(empty($s['custom_columns']) ) $s['custom_columns'] = array(
            //    'enabled' => 'true',
            //    'columns' => array(
            //        array(
            //            'name' => esc_html__( 'First Name', 'super-forms' ),
            //            'field_name' => 'first_name',
            //            'filter' => array(
            //                'enabled' => 'true',
            //                'type' => 'text', // text, dropdown
            //                'items' => '',
            //                'placeholder' => esc_html__( 'search...', 'super-forms' )
            //            ),
            //            'sort' => 'true',
            //            'link' => array(
            //                'type' => 'none',
            //                'url' => ''
            //            ),
            //            'width' => 150,
            //            'order' => 10
            //        ),
            //        array(
            //            'name' => esc_html__( 'Last Name', 'super-forms' ),
            //            'field_name' => 'last_name',
            //            'filter' => array(
            //                'enabled' => 'true',
            //                'type' => 'text', // text, dropdown
            //                'items' => '',
            //                'placeholder' => esc_html__( 'search...', 'super-forms' )
            //            ),
            //            'sort' => 'true',
            //            'link' => array(
            //                'type' => 'none',
            //                'url' => ''
            //            ),
            //            'width' => 150,
            //            'order' => 10
            //        ),
            //        array(
            //            'name' => esc_html__( 'E-mail', 'super-forms' ),
            //            'field_name' => 'email',
            //            'filter' => array(
            //                'enabled' => 'true',
            //                'type' => 'text', // text, dropdown
            //                'items' => '',
            //                'placeholder' => esc_html__( 'search...', 'super-forms' )
            //            ),
            //            'sort' => 'true',
            //            'link' => array(
            //                'type' => 'none',
            //                'url' => ''
            //            ),
            //            'width' => 150,
            //            'order' => 10
            //        ),
            //    ),
            //);
            $s = apply_filters( 'super_stripe_default_settings_filter', $s );
            return $s;
        }


        /**
         * Create Stripe Payment Intent
         *
         *  @since      1.0.0
         */
        public static function redirect_to_stripe_checkout($x){
            extract( shortcode_atts( array( 
                'uniqueSubmissionId'=>'',
                'post'=>array(), 
                'data'=>array(), 
                'settings'=>array(), 
                'entry_id'=>0, 
                'attachments'=>array()
            ), $x));

            $domain = home_url(); // e.g: 'http://domain.com';
            $home_url = trailingslashit($domain);
            $s = $settings['_stripe'];
            // Skip if Stripe checkout is not enabled
            if($s['enabled']!=='true') return true;
            self::setAppInfo();

            //[
            //    'url' => $home_url . 'sfswh', // super forms stripe webhook // 'https://example.com/my/webhook/endpoint',
            //    'description' => 'Required for Super Forms Stripe Checkout Sessions',
            //    'enabled_events' => [
            //        'checkout.session.completed',
            //        'checkout.session.async_payment_succeeded',
            //        'checkout.session.async_payment_failed'
            //    ],
            //]);

            //if(!$webhook){
            //    $webhookUrl = 
            //    $webhook = \Stripe\WebhookEndpoint::create([
            //        'url' => $home_url . 'sfswh', // super forms stripe webhook // 'https://example.com/my/webhook/endpoint',
            //        'description' => 'Required for Super Forms Stripe Checkout Sessions',
            //        'enabled_events' => [
            //            'checkout.session.completed',
            //            'checkout.session.async_payment_succeeded',
            //            'checkout.session.async_payment_failed'
            //        ],
            //    ]);
            //}
            error_log($home_url);
            $cancel_url = $home_url . 'sfssid/cancel/{CHECKOUT_SESSION_ID}';
            $success_url = $home_url . 'sfssid/success/{CHECKOUT_SESSION_ID}';
            error_log($cancel_url);
            // $cancel_url  = add_query_arg( array( 'sfssid' => '{CHECKOUT_SESSION_ID}' ), $cancel_url );  // sfssid stands for Super Forms Stripe Session ID
            // $success_url = add_query_arg( array( 'sfssid' => '{CHECKOUT_SESSION_ID}' ), $success_url ); // sfssid stands for Super Forms Stripe Session ID

            //$cancel_url = $domain . '/cancel_stripe';
            //$success_url = $domain . '/success_stripe?session_id={CHECKOUT_SESSION_ID}';
            $mode = SUPER_Common::email_tags( $s['mode'], $data, $settings );
            $customer_email = SUPER_Common::email_tags( $s['customer_email'], $data, $settings );
            $customer = SUPER_Common::email_tags( $s['customer'], $data, $settings );
            $description = SUPER_Common::email_tags( $s['subscription_data']['description'], $data, $settings );
            $trial_period_days = SUPER_Common::email_tags( $s['subscription_data']['trial_period_days'], $data, $settings );
            $payment_methods = SUPER_Common::email_tags( $s['payment_method_types'], $data, $settings );
            $payment_methods = explode(',', str_replace(' ', '', $payment_methods));
            $metadata = array('sfsi_id' => $uniqueSubmissionId);
            $home_cancel_url = SUPER_Common::email_tags( $s['cancel_url'], $data, $settings );
            $home_success_url = SUPER_Common::email_tags( $s['success_url'], $data, $settings );
            if($home_cancel_url==='') $home_cancel_url = $_SERVER['HTTP_REFERER'];
            if($home_success_url==='') $home_success_url = $_SERVER['HTTP_REFERER'];

            $submissionInfo = get_option( 'sfsi_' . $uniqueSubmissionId, array() );
            $submissionInfo['stripe_home_cancel_url'] = $home_cancel_url;
            $submissionInfo['stripe_home_success_url'] = $home_cancel_url;
            update_option( 'sfsi_' . $uniqueSubmissionId, $submissionInfo );
            // Shipping options
            $shipping_options = array();
            if($s['shipping_options']['type']==='id'){
                $shipping_options['shipping_rate'] = $s['shipping_options']['shipping_rate'];
            }
            if($s['shipping_options']['type']==='data'){
                $shipping_options['shipping_rate_data'] = $s['shipping_options']['shipping_rate_data'];
                $shipping_options['shipping_rate_data']['type'] = 'fixed_amount';
                if(trim($shipping_options['shipping_rate_data']['tax_behavior'])===''){
                    $shipping_options['shipping_rate_data']['tax_behavior'] = 'exclusive';
                }
                if(trim($shipping_options['shipping_rate_data']['tax_code'])==='') unset($shipping_options['shipping_rate_data']['tax_code']);
                if(trim($shipping_options['shipping_rate_data']['fixed_amount']['amount'])==='' || trim($shipping_options['shipping_rate_data']['fixed_amount']['currency'])===''){
                    $shipping_options = array();
                }else{
                    $shipping_options['shipping_rate_data']['delivery_estimate']['minimum']['value'] = intval($shipping_options['shipping_rate_data']['delivery_estimate']['minimum']['value']);
                    $shipping_options['shipping_rate_data']['delivery_estimate']['maximum']['value'] = intval($shipping_options['shipping_rate_data']['delivery_estimate']['maximum']['value']);
                    $shipping_options['shipping_rate_data']['fixed_amount']['amount'] = floatval($shipping_options['shipping_rate_data']['fixed_amount']['amount']) * 100;
                }
            }

            // A set of key-value pairs that you can attach to a source object. 
            // It can be useful for storing additional information about the source in a structured format.
            // already defined... no longer needed $metadata['form_id'] = absint($data['hidden_form_id']['value']);
            //$metadata['user_id'] = get_current_user_id();
            //$metadata['entry_id'] = $entry_id;
            //error_log('entry_id: ' . $entry_id);
            foreach($metadata as $k => $v){
                if(is_array($v)) $metadata[$k] = json_encode($v);
            }
            error_log('metadata: ' . json_encode($metadata));

            //// Get Contact Entry ID and save it so we can update the entry status after successfull payment
            //if(!empty($settings['save_contact_entry']) && $settings['save_contact_entry']=='yes'){
            //    $response = $_POST['response'];
            //    $contact_entry_id = 0;
            //    if(!empty($response['response_data'])){
            //        if(!empty($response['response_data']['contact_entry_id'])){
            //            $contact_entry_id = absint($response['response_data']['contact_entry_id']);
            //        }
            //    }
            //    $metadata['contact_entry_id'] = $contact_entry_id;
            //}


            //array(5) {
            //    ["string_attachments"]=>
            //    string(6) "a:0:{}"
            //    ["super_forms_email_reminders"]=>
            //    string(18) "a:1:{i:0;i:59574;}"
            //    ["tags_values"]=>
            //    string(6600) "a:64:{s:11:"field_*****";a:2:{i:0;s:37:"Any field value submitted by the user";i:1;s:0:"";}s:17:"field_label_*****";a:2:{i:0;s:37:"Any field value submitted by the user";i:1;s:0:"";}s:18:"form_setting_*****";a:2:{i:0;s:35:"Any setting value used for the form";i:1;s:0:"";}s:12:"option_*****";a:2:{i:0;s:34:"Any option value from the database";i:1;s:0:"";}s:18:"option_admin_email";a:2:{i:0;s:36:"E-mail address of blog administrator";i:1;s:24:"feeling4design@gmail.com";}s:15:"option_blogname";a:2:{i:0;s:36:"Weblog title; set in General Options";i:1;s:3:"DEV";}s:22:"option_blogdescription";a:2:{i:0;s:45:"Tagline for your blog; set in General Options";i:1;s:27:"Just another WordPress site";}s:19:"option_blog_charset";a:2:{i:0;s:12:"Blog Charset";i:1;s:5:"UTF-8";}s:18:"option_date_format";a:2:{i:0;s:11:"Date Format";i:1;s:6:"F j, Y";}s:23:"option_default_category";a:2:{i:0;s:45:"Default post category; set in Writing Options";i:1;s:1:"1";}s:11:"option_home";a:2:{i:0;s:56:"The blog&#039;s home web address; set in General Options";i:1;s:18:"https://f4d.nl/dev";}s:14:"option_siteurl";a:2:{i:0;s:45:"WordPress web address; set in General Options";i:1;s:18:"https://f4d.nl/dev";}s:15:"option_template";a:2:{i:0;s:50:"The current theme&#039;s name; set in Presentation";i:1;s:14:"twentynineteen";}s:20:"option_start_of_week";a:2:{i:0;s:17:"Start of the week";i:1;s:1:"1";}s:18:"option_upload_path";a:2:{i:0;s:53:"Default upload location; set in Miscellaneous Options";i:1;s:0:"";}s:21:"option_posts_per_page";a:2:{i:0;s:14:"Posts per page";i:1;s:2:"10";}s:20:"option_posts_per_rss";a:2:{i:0;s:18:"Posts per RSS feed";i:1;s:2:"10";}s:7:"real_ip";a:2:{i:0;s:41:"Retrieves the submitter&#039;s IP address";i:1;s:13:"77.163.86.143";}s:10:"loop_label";a:1:{i:0;s:58:"Retrieves the field label for the field loop {loop_fields}";}s:10:"loop_value";a:1:{i:0;s:58:"Retrieves the field value for the field loop {loop_fields}";}s:11:"loop_fields";a:1:{i:0;s:41:"Retrieves the loop anywhere in your email";}s:10:"post_title";a:2:{i:0;s:40:"Retrieves the current page or post title";i:1;s:0:"";}s:7:"post_id";a:2:{i:0;s:37:"Retrieves the current page or post ID";i:1;s:0:"";}s:9:"author_id";a:2:{i:0;s:31:"Retrieves the current author ID";i:1;s:0:"";}s:12:"author_email";a:2:{i:0;s:34:"Retrieves the current author email";i:1;s:0:"";}s:14:"post_author_id";a:2:{i:0;s:44:"Retrieves the current page or post author ID";i:1;s:0:"";}s:17:"post_author_email";a:2:{i:0;s:47:"Retrieves the current page or post author email";i:1;s:0:"";}s:14:"post_permalink";a:2:{i:0;s:30:"Retrieves the current page URL";i:1;s:0:"";}s:10:"user_login";a:2:{i:0;s:53:"Retrieves the current logged in user login (username)";i:1;s:14:"feeling4design";}s:10:"user_email";a:2:{i:0;s:42:"Retrieves the current logged in user email";i:1;s:24:"feeling4design@gmail.com";}s:14:"user_firstname";a:2:{i:0;s:47:"Retrieves the current logged in user first name";i:1;s:4:"Rens";}s:13:"user_lastname";a:2:{i:0;s:46:"Retrieves the current logged in user last name";i:1;s:8:"Tillmann";}s:12:"user_display";a:2:{i:0;s:49:"Retrieves the current logged in user display name";i:1;s:13:"Tillmann Rens";}s:7:"user_id";a:2:{i:0;s:39:"Retrieves the current logged in user ID";i:1;i:1;}s:10:"user_roles";a:2:{i:0;s:42:"Retrieves the current logged in user roles";i:1;s:13:"administrator";}s:20:"server_http_referrer";a:2:{i:0;s:97:"Retrieves the location where user came from (if exists any) before loading the page with the form";i:1;s:102:"https://f4d.nl/dev/stripe-5/?sfssid=cs_test_a1jGYZojs0GuOXJjFTE33JqdB65srSkKOkyG313du5SGUqlXER8JafD9Gd";}s:28:"server_http_referrer_session";a:2:{i:0;s:112:"Retrieves the location where user came from from a session (if exists any) before loading the page with the form";i:1;s:28:"https://checkout.stripe.com/";}s:20:"server_timestamp_gmt";a:2:{i:0;s:40:"Retrieves the server timestamp (UTC/GMT)";i:1;i:1651771650;}s:14:"server_day_gmt";a:2:{i:0;s:48:"Retrieves the current day of the month (UTC/GMT)";i:1;s:2:"05";}s:16:"server_month_gmt";a:2:{i:0;s:49:"Retrieves the current month of the year (UTC/GMT)";i:1;s:2:"05";}s:15:"server_year_gmt";a:2:{i:0;s:44:"Retrieves the current year of time (UTC/GMT)";i:1;s:4:"2022";}s:15:"server_hour_gmt";a:2:{i:0;s:47:"Retrieves the current hour of the day (UTC/GMT)";i:1;s:2:"17";}s:17:"server_minute_gmt";a:2:{i:0;s:50:"Retrieves the current minute of the hour (UTC/GMT)";i:1;s:2:"27";}s:18:"server_seconds_gmt";a:2:{i:0;s:52:"Retrieves the current second of the minute (UTC/GMT)";i:1;s:2:"30";}s:16:"server_timestamp";a:2:{i:0;s:43:"Retrieves the server timestamp (Local time)";i:1;i:1651771650;}s:10:"server_day";a:2:{i:0;s:51:"Retrieves the current day of the month (Local time)";i:1;s:2:"05";}s:12:"server_month";a:2:{i:0;s:52:"Retrieves the current month of the year (Local time)";i:1;s:2:"05";}s:11:"server_year";a:2:{i:0;s:47:"Retrieves the current year of time (Local time)";i:1;s:4:"2022";}s:11:"server_hour";a:2:{i:0;s:50:"Retrieves the current hour of the day (Local time)";i:1;s:2:"17";}s:13:"server_minute";a:2:{i:0;s:53:"Retrieves the current minute of the hour (Local time)";i:1;s:2:"27";}s:14:"server_seconds";a:2:{i:0;s:55:"Retrieves the current second of the minute (Local time)";i:1;s:2:"30";}s:16:"submission_count";a:2:{i:0;s:61:"Retrieves the total submission count (if form locker is used)";i:1;s:0:"";}s:17:"last_entry_status";a:2:{i:0;s:41:"Retrieves the latest Contact Entry status";i:1;s:0:"";}s:13:"last_entry_id";a:2:{i:0;s:37:"Retrieves the latest Contact Entry ID";i:1;i:0;}s:22:"user_last_entry_status";a:2:{i:0;s:88:"Retrieves the latest Contact Entry status of the logged in user based on current form ID";i:1;s:0:"";}s:18:"user_last_entry_id";a:2:{i:0;s:84:"Retrieves the latest Contact Entry ID of the logged in user based on current form ID";i:1;i:0;}s:31:"user_last_entry_status_any_form";a:2:{i:0;s:76:"Retrieves the latest Contact Entry status of the logged in user for any form";i:1;s:0:"";}s:27:"user_last_entry_id_any_form";a:2:{i:0;s:72:"Retrieves the latest Contact Entry ID of the logged in user for any form";i:1;i:59573;}s:25:"_generated_pdf_file_label";a:2:{i:0;s:19:"Generated PDF Label";i:1;s:0:"";}s:24:"_generated_pdf_file_name";a:2:{i:0;s:18:"Generated PDF name";i:1;s:0:"";}s:23:"_generated_pdf_file_url";a:2:{i:0;s:17:"Generated PDF URL";i:1;s:0:"";}s:18:"register_login_url";a:2:{i:0;s:28:"Retrieves the login page URL";i:1;s:0:"";}s:24:"register_activation_code";a:2:{i:0;s:29:"Retrieves the activation code";i:1;s:0:"";}s:27:"register_generated_password";a:2:{i:0;s:32:"Retrieves the generated password";i:1;s:0:"";}}"
            //    ["sf_nonce"]=>
            //    string(96) "b006ec3d4976055339d407c802a63a7032a7ff77f74d870bebbc55b7f65d0fd1e829b74df35b2bbffa32d3fa9908a535"
            //    ["server_http_referrer"]=>
            //    string(28) "https://checkout.stripe.com/"
            //}


            // // Delete any E-mail reminders based on this form ID as it's parent
            // $email_reminders = SUPER_Common::getClientData( 'super_forms_email_reminders' );
            // if( $email_reminders!=false ) {
            //     if (is_array($email_reminders) && count($email_reminders) > 0) {
            //         // Delete all the Children of the Parent Page
            //         foreach($email_reminders as $reminder){
            //             wp_delete_post($reminder, true);  // force delete, we no longer want it in our system
            //         }
            //     }
            // }
            // SUPER_Common::setClientData( array( 'name'=> 'super_forms_email_reminders', 'value'=>false  ) );


            // Todo when returning from cancel
            // $metadata = (isset($paymentIntent['metadata']) ? $paymentIntent['metadata'] : '');
            // if( !is_array($metadata) ) {
            //     $metadata = json_decode($metadata, true);
            // }
            // // Delete contact entry status after failed payment
            // $contact_entry_id = (isset($metadata['contact_entry_id']) ? absint($metadata['contact_entry_id']) : 0 );
            // if( !empty($contact_entry_id) ) {
            //     wp_delete_post($contact_entry_id, true); // force delete, we no longer want it in our system
            // }
            // // Delete post after failed payment (only used for Front-end Posting feature)
            // $frontend_post_id = (isset($metadata['frontend_post_id']) ? absint($metadata['frontend_post_id']) : 0 );
            // if( !empty($frontend_post_id) ) {
            //     wp_delete_post($frontend_post_id, true); // force delete, we no longer want it in our system
            // }
            // // Delete user after failed payment (only used for Register & Login feature)
            // $frontend_user_id = (isset($metadata['frontend_user_id']) ? absint($metadata['frontend_user_id']) : 0 );
            // if( !empty($frontend_user_id) ) {
            //     require_once( ABSPATH . 'wp-admin/includes/user.php' );
            //     wp_delete_user($frontend_user_id);
            // }
            // // Delete any E-mail reminders based on this form ID as it's parent
            // $email_reminders = SUPER_Common::getClientData( 'email_reminders' );
            // if( $email_reminders!=false ) {
            //     if (is_array($email_reminders) && count($email_reminders) > 0) {
            //         // Delete all the Children of the Parent Page
            //         foreach($email_reminders as $reminder){
            //             wp_delete_post($reminder, true);  // force delete, we no longer want it in our system
            //         }
            //     }
            // }
            // SUPER_Common::setClientData( array( 'name'=> 'email_reminders', 'value'=>false  ) );

            error_log(json_encode($s['line_items']));
            foreach($s['line_items'] as $k => $v){
                if($v['type'] === 'price'){
                    if(trim($v['price'])===''){
                        SUPER_Common::output_message( array( 
                            'msg' => esc_html__( 'Please provide the price/plan ID for your line item', 'super-forms' )
                        ));
                    }
                    unset($v['price_data']);
                }
                if($v['type'] === 'price_data'){
                    unset($v['price']);
                    if($v['price_data']['recurring']['interval']==='none'){
                        unset($v['price_data']['recurring']);
                    }
                    // Set correct unit amount
                    $v['price_data']['unit_amount_decimal'] = floatval($v['price_data']['unit_amount_decimal']) * 100;
                    $v['price_data']['tax_behavior'] = $v['price_data']['tax_behavior'];
                    if($v['price_data']['type'] === 'product'){
                        unset($v['price_data']['product_data']);
                    }
                    if($v['price_data']['type'] === 'product_data'){
                        unset($v['price_data']['product']);
                        // Unset empty product values
                        if(trim($v['price_data']['product_data']['name'])===''){
                            $v['price_data']['product_data']['name'] = '{product_name}';
                        }
                        if(trim($v['price_data']['product_data']['description'])===''){
                            unset($v['price_data']['product_data']['description']);
                        }
                        if(trim($v['price_data']['product_data']['tax_code'])===''){
                            unset($v['price_data']['product_data']['tax_code']);
                        }
                    }
                }
                if($v['custom_tax_rate']==='true'){
                    $v['tax_rates'] = explode(',', str_replace(' ', '', trim($v['tax_rates'])));
                }else{
                    unset($v['tax_rates']);
                }
                unset($v['type']);
                unset($v['price_data']['type']);
                unset($v['custom_tax_rate']);
                $s['line_items'][$k] = $v;
            }

            //$currency = SUPER_Common::email_tags( $s['stripe_currency'], $data, $settings );
            //error_log('$payment_method: ' . json_encode($payment_methods));
            //error_log('$currency: ' . $currency);

            //// Set meta data
            //// A set of key-value pairs that you can attach to a source object. 
            //// It can be useful for storing additional information about the source in a structured format.
            //$metadata = array();
            //$metadata['form_id'] = $form_id;
            //$metadata['user_id'] = get_current_user_id();
            //// Get Post ID and save it in custom parameter for stripe so we can update the post status after successfull payment complete
            //$post_id = SUPER_Common::getClientData( '_super_stripe_frontend_post_id' );
            //if( !empty($post_id) ) {
            //    $metadata['frontend_post_id'] = absint($post_id);
            //}
            //// Get User ID and save it in custom parameter for stripe so we can update the user status after successfull payment complete
            //$user_id = SUPER_Common::getClientData( 'stripe_frontend_user_id' );
            //if( !empty($user_id) ) {
            //    $metadata['frontend_user_id'] = absint($user_id);
            //}
            //// Get Contact Entry ID and save it so we can update the entry status after successfull payment


            // Before we continue, let's check if the Webhook is configured properly
            $global_settings = SUPER_Common::get_global_settings();
            if(!isset($global_settings['stripe_mode'])) $global_settings['stripe_mode'] = 'live';
            // Get webhook ID and secret
            $webhookId = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_id']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_id'] : '');
            $webhookSecret = (isset($global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_secret']) ? $global_settings['stripe_' . $global_settings['stripe_mode'] . '_webhook_secret'] : '');
            error_log('webhookId: ' . $webhookId);
            error_log('webhookSecret: ' . $webhookSecret);
            if(empty($webhookId)){
                $msg = sprintf(
                    esc_html( 'Please enter your webhook ID under:%s%sSettings > Stripe Checkout%s.%sIt should start with `we_`.%sYou can find your Webhook ID via %swebhook settings%s.', 'super-forms' ), 
                    '<br />',
                    '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>', 
                    '<br /><br />', '<br /><br />',
                    '<a target="_blank" href="https://dashboard.stripe.com/webhooks">', '</a>'
                );
                $e = new Exception($msg);
                error_log("exceptionHandler19()");
                self::exceptionHandler($e, $metadata);
                die();
            }
            if(empty($webhookSecret)){
                $msg = sprintf(
                    esc_html( 'Please enter your webhook secret under:%s%sSettings > Stripe Checkout%s.%sIt should start with `whsec_`.%sYou can find your Stripe endpoint\'s secret via %swebhook settings%s.', 'super-forms' ), 
                    '<br />',
                    '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>', 
                    '<br /><br />', '<br /><br />',
                    '<a target="_blank" href="https://dashboard.stripe.com/webhooks">', '</a>'
                );
                $e = new Exception($msg);
                error_log("exceptionHandler19()");
                self::exceptionHandler($e, $metadata);
                die();
            }
            // Check if this webhook has a correct endpoint URL and Events defined
            $webhook = \Stripe\WebhookEndpoint::retrieve($webhookId, []);
            error_log('webhook: ' . json_encode($webhook));
            $eventMissing = false;
            if( !in_array('checkout.session.async_payment_failed', $webhook['enabled_events']) ) {
                $eventMissing = true;
            }
            if( !in_array('checkout.session.async_payment_succeeded', $webhook['enabled_events']) ) {
                $eventMissing = true;
            }
            if( !in_array('checkout.session.completed', $webhook['enabled_events']) ) {
                $eventMissing = true;
            }
            // ? string(24) "checkout.session.expired"
            if($eventMissing){
                $msg = sprintf(
                    esc_html( 'Your webhook is missing a required event, please make sure the following events are enabled:%sYou can enable these events via %swebhook settings%s.', 'super-forms' ), 
                    '<br /><br /><code>checkout.session.async_payment_failed</code><br /><code>checkout.session.async_payment_succeeded</code><br /><code>checkout.session.completed</code><br /><br />',
                    '<a target="_blank" href="https://dashboard.stripe.com/webhooks">', '</a>'
                );
                $e = new Exception($msg);
                error_log("exceptionHandler18()");
                self::exceptionHandler($e, $metadata);
                die();
            }
            if(trailingslashit($webhook['url'])!==trailingslashit($home_url.'sfstripe/webhook')){
                $msg = sprintf(
                    esc_html( 'Please update your Webhook endpoint so that it points to the following URL:%sYou can change this via %swebhook settings%s.', 'super-forms' ), 
                    '<br /><br /><code> ' . $home_url . 'sfstripe/webhook</code><br /><br />', // super forms stripe webhook e.g: https://domain.com/sfstripe/webhook will be converted into https://domain.com/index.php?sfstripewebhook=true 
                    '<a target="_blank" href="https://dashboard.stripe.com/webhooks/'. $webhook['id'].'">', '</a>'
                );
                $e = new Exception($msg);
                error_log("exceptionHandler17()");
                self::exceptionHandler($e, $metadata);
                die();
            }

            // Try to start a Checkout Session
            try {
                // Use Stripe's library to make requests...
                error_log('shipping_options');
                error_log(json_encode($shipping_options));
                $stripeData = array(
                    // [required conditionally] The mode of the Checkout Session. Required when using prices or setup mode. Pass subscription if the Checkout Session includes at least one recurring item.
                    'mode' => $mode, //'payment', // `payment`, `subscription` or `setup`
                    // [required] The URL the customer will be directed to if they decide to cancel payment and return to your website.
                    'cancel_url' => $cancel_url,
                    // [required] The URL to which Stripe should send customers when payment or setup is complete. If you’d like to use information from the successful Checkout Session on your page, read the guide on
                    'success_url' => $success_url,
                    // Example success page could be:
                    // ```php
                    // $session = \Stripe\Checkout\Session::retrieve($request->get('session_id'));
                    // $customer = \Stripe\Customer::retrieve($session->customer);
                    // return $response->write("<html><body><h1>Thanks for your order, $customer->name!</h1></body></html>");
                    // ```

                    // [optional] ID of an existing Customer, if one exists. In payment mode, the customer’s most recent card payment method will be used to prefill the email, name, card details, and billing address on the Checkout page. 
                    //            In subscription mode, the customer’s default payment method will be used if it’s a card, and otherwise the most recent card will be used. 
                    //            A valid billing address, billing name and billing email are required on the payment method for Checkout to prefill the customer’s card details.
                    //            If the Customer already has a valid email set, the email will be prefilled and not editable in Checkout. If the Customer does not have a valid email, Checkout will set the email entered during the session on the Customer.
                    //            If blank for Checkout Sessions in payment or subscription mode, Checkout will create a new Customer object based on information provided during the payment flow.
                    //            You can set payment_intent_data.setup_future_usage to have Checkout automatically attach the payment method to the Customer you pass in for future reuse.
                    // 'customer' => 'cus_XXXXX'
                    'customer' => $customer, // cus_LC8tK7PWO9Lwbw

                    // A list of the types of payment methods (e.g., card) this Checkout Session can accept.
                    // Read more about the supported payment methods and their requirements in our payment method details guide.
                    // If multiple payment methods are passed, Checkout will dynamically reorder them to prioritize the most relevant payment methods based on the customer’s location and other characteristics.
                    // `card` `acss_debit` `afterpay_clearpay` `alipay` `au_becs_debit` `bacs_debit` `bancontact` `boleto` `eps` `fpx` `giropay` `grabpay` `ideal` `klarna` `konbini` `oxxo` `p24` `paynow` `sepa_debit` `sofort` `us_bank_account` `wechat_pay`
                    'payment_method_types' => $payment_methods, // ['card', 'ideal'],

                    // A list of items the customer is purchasing. Use this parameter to pass one-time or recurring Prices.
                    // For payment mode, there is a maximum of 100 line items, however it is recommended to consolidate line items if there are more than a few dozen.
                    // For subscription mode, there is a maximum of 20 line items with recurring Prices and 20 line items with one-time Prices. Line items with one-time Prices in will be on the initial invoice only.
                    // line items:
                    // If you have a fixed defined price or plan simply use:
                    // e.g: pr_XXXXXX
                    // e.g: plan_XXXXX
                    // If you want to create a new price on the fly define it like so:
                    // e.g: product_name|quantity|product_name|unit_amount|tax|recurring|interval                     

                    'line_items' => $s['line_items'],
                    //'line_items' => [[
                    //    # Provide the exact Price ID (e.g. pr_1234) of the product you want to sell
                    //    // 'price' => 'price_1Kf8eNFKn7uROhgCcYhH5G2L', //{{PRICE_ID}}',
                    //    // `price_data` or `price`
                    //    'price_data' => [
                    //        'currency' => strtolower($currency), // Three-letter ISO currency code. must be EUR when purchasing with iDeal
                    //        // `product` or `product_data`
                    //        'product_data' => [
                    //            'name' => 'Product name here',
                    //            'description' => 'Product description here',
                    //            //'images' => [], // list of image up to 8
                    //            //'metadata' => [], // product meta data
                    //            //'tax_code' => ''
                    //        ],
                    //        'unit_amount' => 10000, // A non-negative integer in cents representing how much to charge. One of unit_amount or unit_amount_decimal is required.
                    //        //'metadata' => [], // product meta data
                    //        // only possible when payment mode is set to `subscription` >> 'recurring' => [
                    //        // only possible when payment mode is set to `subscription` >>     'interval' => 'month', // day, week, month, year
                    //        // only possible when payment mode is set to `subscription` >>     'interval_count' => 1, // The number of intervals between subscription billings. For example, interval=month and interval_count=3 bills every 3 months. Maximum of one year interval allowed (1 year, 12 months, or 52 weeks).
                    //        // only possible when payment mode is set to `subscription` >> ],
                    //        'tax_behavior' => 'exclusive' // Specifies whether the price is considered inclusive of taxes or exclusive of taxes. One of inclusive, exclusive, or unspecified. Once specified as either inclusive or exclusive, it cannot be changed.
                    //    ],
                    //    'quantity' => 1,
                    //]],


                    // [optional] Set of key-value pairs that you can attach to an object. 
                    //            This can be useful for storing additional information about the object in a structured format. 
                    //            Individual keys can be unset by posting an empty value to them. 
                    //            All keys can be unset by posting an empty value to metadata.
                    'metadata' => $metadata,

                    //'payment_method_options' => [
                    //    'acss_debit' => [ // contains details about the ACSS Debit payment method options.
                    //        'currency' => 'usd'/'cad', // Three-letter ISO currency code. Must be a supported currency. This is only accepted for Checkout Sessions in setup mode.
                    //        'mandate_options' => [
                    //            'custom_mandate_url' => '', // A URL for custom mandate text to render during confirmation step. The URL will be rendered with additional GET parameters payment_intent and payment_intent_client_secret when confirming a Payment Intent, or setup_intent and setup_intent_client_secret when confirming a Setup Intent.
                    //            // List of Stripe products where this mandate can be selected automatically. Only usable in setup mode.
                    //            'default_for' => 'invoice', 
                    //                           // 'subscription', 
                    //            'interval_description' => '', // Description of the mandate interval. Only required if ‘payment_schedule’ parameter is ‘interval’ or ‘combined’.
                    //            // Payment schedule for the mandate.
                    //            'payment_schedule' => 'interval', // Payments are initiated at a regular pre-defined interval
                    //                               // 'sporadic' // Payments are initiated sporadically
                    //                               // 'combined' // Payments can be initiated at a pre-defined interval or sporadically

                    //            // Transaction type of the mandate.
                    //            'transaction_type' => 'personal', // Transactions are made for personal reasons
                    //                                //'business' // Transactions are made for business reason

                    //            // Verification method for the intent
                    //            'verification_method' => 'automatic', // Instant verification with fallback to microdeposits.
                    //                                  // 'instant' // Instant verification.
                    //                                  // 'microdeposits' // Verification using microdeposits.
                    //        ]
                    //    ],
                    //    'alipay' => [
                    //        // No parameters.
                    //    ],

                    //    'boleto' => [
                    //        'expires_after_days' => 1 // The number of calendar days before a Boleto voucher expires. For example, if you create a Boleto voucher on Monday and you set expires_after_days to 2, the Boleto invoice will expire on Wednesday at 23:59 America/Sao_Paulo time.
                    //    ],
                    //    'konbini' => [
                    //        'expires_after_days' => 1 // The number of calendar days (between 1 and 60) after which Konbini payment instructions will expire. For example, if a PaymentIntent is confirmed with Konbini and expires_after_days set to 2 on Monday JST, the instructions will expire on Wednesday 23:59:59 JST. Defaults to 3 days.
                    //    ],
                    //    'us_bank_account' => [
                    //        'verification_method' => 'automatic', // Instant verification with fallback to microdeposits.
                    //                              // 'instant' // Instant verification only.
                    //    ],
                    //    'wechat_pay' => [
                    //        'app_id' => '', // The app ID registered with WeChat Pay. Only required when client is ios or android.
                    //        'client' => 'web', // The end customer will pay from web browser
                    //                 // 'ios' The end customer will pay from an iOS app
                    //                 // 'android' The end customer will pay from an Android app
                    //    ]
                    //],

                    // [optional] Controls phone number collection settings for the session.
                    // We recommend that you review your privacy policy and check with your legal contacts before using this feature. Learn more about collecting phone numbers with Checkout.
                    //'phone_number_collection' => [
                    //    'enabled' => false, // Set to true to enable phone number collection.
                    //],

                    // [optional] A subset of parameters to be passed to SetupIntent creation for Checkout Sessions in setup mode.
                    //'setup_intent_data' => [
                    //    'description', // An arbitrary string attached to the object. Often useful for displaying to users.
                    //    'metadata', // Set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format. Individual keys can be unset by posting an empty value to them. All keys can be unset by posting an empty value to metadata.
                    //    'on_behalf_of', // The Stripe account for which the setup is intended.
                    //]

                    'shipping_options' => array($shipping_options),
                    // [optional] The shipping rate options to apply to this Session.
                    //'shipping_options' => [
                    //    'shipping_rate' => '', // The ID of the Shipping Rate to use for this shipping option.
                    //    'shipping_rate_data' => [ // Parameters to be passed to Shipping Rate creation for this shipping option
                    //        'display_name' => '', // The name of the shipping rate, meant to be displayable to the customer. This will appear on CheckoutSessions.
                    //        'type' => 'fixed_amount', // The type of calculation to use on the shipping rate. Can only be fixed_amount for now.
                    //        'delivery_estimate' => [ // The estimated range for how long shipping will take, meant to be displayable to the customer. This will appear on CheckoutSessions.
                    //            'maximum' => [ // The upper bound of the estimated range. If empty, represents no upper bound i.e., infinite.
                    //                'unit' => 'business_day', // hour, day, business_day, week, month
                    //                'value' => 5,
                    //            ],
                    //            'minimum' => [ // The lower bound of the estimated range. If empty, represents no lower bound.
                    //                'unit' => 'business_day', // hour, day, business_day, week, month
                    //                'value' => 2,
                    //            ]
                    //        ],
                    //        'fixed_amount' => [
                    //            'amount' => 3000, // A non-negative integer in cents representing how much to charge.
                    //            'currency' => 'usd' // Three-letter ISO currency code. Must be a supported currency.
                    //        ]
                    //        'metadata' => [], // Set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format. Individual keys can be unset by posting an empty value to them. All keys can be unset by posting an empty value to metadata.
                    //        'tax_behavior' => 'exclusive' // Specifies whether the rate is considered inclusive of taxes or exclusive of taxes. One of inclusive, exclusive, or unspecified.
                    //        'tax_code' => 'txcd_92010001', // A tax code ID. The Shipping tax code is txcd_92010001.
                    //    ]
                    //],

                    // A subset of parameters to be passed to subscription creation for Checkout Sessions in subscription mode.
                    //'subscription_data' => [

                    //    'application_fee_percent' => 0, // A non-negative decimal between 0 and 100, with at most two decimal places. This represents the percentage of the subscription invoice subtotal that will be transferred to the application owner’s Stripe account. To use an application fee percent, the request must be made on behalf of another account, using the Stripe-Account header or an OAuth key. For more information, see the application fees documentation.
                    //    'default_tax_rates' => // The tax rates that will apply to any subscription item that does not have tax_rates set. Invoices created will have their default_tax_rates populated from the subscription.
                    //    'items' => [
                    //        'plan', // Plan ID for this item.
                    //        'quantity', // The quantity of the subscription item being purchased. Quantity should not be defined when recurring.usage_type=metered.
                    //        'tax_rates', // The tax rates which apply to this item. When set, the default_tax_rates on subscription_data do not apply to this item.
                    //    ]
                    //    'metadata' => [], // Set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format. Individual keys can be unset by posting an empty value to them. All keys can be unset by posting an empty value to metadata.
                    //    'transfer_data' => [ // If specified, the funds from the subscription’s invoices will be transferred to the destination and the ID of the resulting transfers will be found on the resulting charges.
                    //        'destination' => // ID of an existing, connected Stripe account.
                    //        'amount_percent' => // A non-negative decimal between 0 and 100, with at most two decimal places. This represents the percentage of the subscription invoice subtotal that will be transferred to the destination account. By default, the entire amount is transferred to the destination.
                    //    ]
                    //    'trial_end' => // Unix timestamp representing the end of the trial period the customer will get before being charged for the first time. Has to be at least 48 hours in the future.
                    //    'trial_period_days' =>  // Integer representing the number of trial period days before the customer is charged for the first time. Has to be at least 1.
                    //],

                    //'tax_id_collection' => [ // Controls tax ID collection settings for the session.
                    //    'enabled' => 'true', // Set to true to enable Tax ID collection.
                    //],

                    // [optional] Specify whether Checkout should collect the customer’s billing address.
                    // `auto` Checkout will only collect the billing address when necessary.
                    // `required` Checkout will always collect the customer’s billing address.
                    'billing_address_collection' => $s['billing_address_collection'],
                    
                    // [optional] When set, provides configuration for Checkout to collect a shipping address from a customer.
                    //'shipping_address_collection' => [
                    //    'allowed_countries' => [] // An array of two-letter ISO country codes representing which countries Checkout should provide as options for shipping locations. 
                    //                              // Unsupported country codes: AS, CX, CC, CU, HM, IR, KP, MH, FM, NF, MP, PW, SD, SY, UM, VI.
                    //]

                    // [optional] Controls what fields on Customer can be updated by the Checkout Session. Can only be provided when customer is provided.
                    //'customer_update' => [ 
                    //    'address' => 'auto'/'never',  // Describes whether Checkout saves the billing address onto customer.address. To always collect a full billing address, use billing_address_collection. Defaults to never.
                    //    'name' => 'auto'/'never',     // Describes whether Checkout saves the name onto customer.name. Defaults to never.
                    //    'shipping' => 'auto'/'never', // Describes whether Checkout saves shipping information onto customer.shipping. To collect shipping information, use shipping_address_collection. Defaults to never.
                    //],

                    // [optional] The coupon or promotion code to apply to this Session. Currently, only up to one may be specified.
                    //'discounts' => [
                    //    'coupon' => '', // The ID of the coupon to apply to this Session.
                    //    'promotion_code' => '' // The ID of a promotion code to apply to this Session.
                    //]

                    // [optional] The Epoch time in seconds at which the Checkout Session will expire. It can be anywhere from 1 to 24 hours after Checkout Session creation. By default, this value is 24 hours from creation.
                    //'expires_at' => ''

                    // [optional] A subset of parameters to be passed to PaymentIntent creation for Checkout Sessions in payment mode.
                    // 'payment_intent_data' => [
                    //      'application_fee_amount' => 0, // The amount of the application fee (if any) that will be requested to be applied to the payment and transferred to the application owner’s Stripe account. The amount of the application fee collected will be capped at the total payment amount. For more information, see the PaymentIntents use case for connected accounts.
                    //      'capture_method' => 'automatic'/'manual', // The amount of the application fee (if any) that will be requested to be applied to the payment and transferred to the application owner’s Stripe account. The amount of the application fee collected will be capped at the total payment amount. For more information, see the PaymentIntents use case for connected accounts.
                    //      'description' => '', // An arbitrary string attached to the object. Often useful for displaying to users.
                    //      'metadata' => [], // Set of key-value pairs that you can attach to an object. This can be useful for storing additional information about the object in a structured format. Individual keys can be unset by posting an empty value to them. All keys can be unset by posting an empty value to metadata.
                    //      'on_behalf_of' => '', // The Stripe account ID for which these funds are intended. For details, see the PaymentIntents use case for connected accounts.
                    //      'receipt_email' => '', // Email address that the receipt for the resulting payment will be sent to. If receipt_email is specified for a payment in live mode, a receipt will be sent regardless of your email settings.
                    //      'setup_future_usage' => '' // Indicates that you intend to make future payments with the payment method collected by this Checkout Session.
                    //                                 // When setting this to on_session, Checkout will show a notice to the customer that their payment details will be saved.
                    //                                 // When setting this to off_session, Checkout will show a notice to the customer that their payment details will be saved and used for future payments.
                    //                                 // If a Customer has been provided or Checkout creates a new Customer, Checkout will attach the payment method to the Customer.
                    //                                 // If Checkout does not create a Customer, the payment method is not attached to a Customer. To reuse the payment method, you can retrieve it from the Checkout Session’s PaymentIntent.
                    //                                 // When processing card payments, Checkout also uses setup_future_usage to dynamically optimize your payment flow and comply with regional legislation and network rules, such as SCA.
                    //                                 // Use `on_session` if you intend to only reuse the payment method when your customer is present in your checkout flow.
                    //                                 // Use `off_session` if your customer may or may not be present in your checkout flow.
                    //      'shipping' => [ // Shipping information for this payment.
                    //          'address' => [line1, city, country, line2, postal_code, state], // Shipping address.
                    //          'name' => '', // Recipient name.
                    //          'carrier' => '', // The delivery service that shipped a physical product, such as Fedex, UPS, USPS, etc.
                    //          'phone' => '', // Recipient phone (including extension).
                    //          'tracking_number' => '', // The tracking number for a physical product, obtained from the delivery service. If multiple tracking numbers were generated for this purchase, please separate them with commas.
                    //      ],
                    //      'statement_descriptor' => '', // Extra information about the payment. This will appear on your customer’s statement when this payment succeeds in creating a charge.
                    //      'statement_descriptor_suffix' => '', // Provides information about the charge that customers see on their statements. Concatenated with the prefix (shortened descriptor) or statement descriptor that’s set on the account to form the complete statement descriptor. Maximum 22 characters for the concatenated descriptor.
                    //      'transfer_data' => '', // The parameters used to automatically create a Transfer when the payment succeeds. For more information, see the PaymentIntents use case for connected accounts.
                    //      'transfer_group' => '', // A string that identifies the resulting payment as part of a group. See the PaymentIntents use case for connected accounts for details.
                    // ],


                    // [optional] The IETF language tag of the locale Checkout is displayed in. If blank or auto, the browser’s locale is used.
                    // 'locale' => 'auto'/'en'
                    'locale' => $s['locale'],

                    'automatic_tax' => $s['automatic_tax'],
                    'tax_id_collection' => $s['tax_id_collection'],

                    'subscription_data' => array(
                        'description' => $description,
                        'trial_period_days' => $trial_period_days
                    ),

                    // [optional] If provided, this value will be used when the Customer object is created. 
                    //            If not provided, customers will be asked to enter their email address. 
                    //            Use this parameter to prefill customer data if you already have an email on file. 
                    //            To access information about the customer once a session is complete, use the customer field.
                    'customer_email' => $customer_email, //'info@customer.com',

                    // [optional] Configure whether a Checkout Session creates a Customer during Session confirmation.
                    // When a Customer is not created, you can still retrieve email, address, and other customer data entered in Checkout with customer_details.
                    // Sessions that don’t create Customers instead create Guest Customers in the Dashboard. Promotion codes limited to first time customers will return invalid for these Sessions.
                    // Can only be set in payment and setup mode.
                    'customer_creation' => 'always', // 'if_required', // The Checkout Session will only create a Customer if it is required for Session confirmation. Currently, only subscription mode Sessions require a Customer.
                                                     // 'always' // The Checkout Session will always create a Customer when a Session confirmation is attempted.

                    // [optional] A unique string to reference the Checkout Session. This can be a customer ID, a cart ID, or similar, and can be used to reconcile the session with your internal systems.
                    'client_reference_id' => $s['client_reference_id'],
                );

                function array_remove_empty($haystack){
                    foreach ($haystack as $key => $value) {
                        if(is_array($value)) $haystack[$key] = array_remove_empty($haystack[$key]);
                        if(empty($haystack[$key])) unset($haystack[$key]);
                    }
                    return $haystack;
                }

                if($mode==='subscription'){
                    unset($stripeData['customer_creation']);
                }
                if($mode==='payment'){
                    // [optional] Describes the type of transaction being performed by Checkout in order to customize relevant text on the page, 
                    // such as the submit button. `submit_type` can only be specified on Checkout Sessions in `payment` mode, but not Checkout Sessions in subscription or setup mode.
                    //'submit_type' => 'auto', //  payment mode must be `payment` in order for this to work, possible values are `auto` `pay` `book` `donate`
                    $stripeData['submit_type'] = $s['submit_type'];
                }

                // Tax ID collection requires updating business name on the customer. 
                // To enable tax ID collection for an existing customer, please set `customer_update[name]` to `auto`.
                //'customer_update' => [ 
                //    'address' => 'auto'/'never',  // Describes whether Checkout saves the billing address onto customer.address. To always collect a full billing address, use billing_address_collection. Defaults to never.
                //    'name' => 'auto'/'never',     // Describes whether Checkout saves the name onto customer.name. Defaults to never.
                //    'shipping' => 'auto'/'never', // Describes whether Checkout saves shipping information onto customer.shipping. To collect shipping information, use shipping_address_collection. Defaults to never.
                //],
                if($customer!=='' && $s['tax_id_collection']['enabled']==='true'){
                    if(empty($stripeData['customer_update'])) $stripeData['customer_update'] = array();
                    $stripeData['customer_update']['name'] = 'auto';
                    $stripeData['customer_update']['address'] = 'auto';
                }
                
                $stripeData = array_remove_empty($stripeData);
                error_log(json_encode($stripeData));
                error_log('uniqueSubmissionId: ' . $uniqueSubmissionId);
                $submissionInfo = get_option( 'sfsi_' . $uniqueSubmissionId, array() );
                $submissionInfo['stripeData'] = $stripeData;
                update_option( 'sfsi_' . $uniqueSubmissionId, $submissionInfo );
                $checkout_session = \Stripe\Checkout\Session::create($stripeData);
            } catch( Exception $e ){
                if ($e instanceof \Stripe\Error\Card ||
                    $e instanceof \Stripe\Exception\CardException ||
                    $e instanceof \Stripe\Exception\RateLimitException ||
                    $e instanceof \Stripe\Exception\InvalidRequestException ||
                    $e instanceof \Stripe\Exception\AuthenticationException ||
                    $e instanceof \Stripe\Exception\ApiConnectionException ||
                    $e instanceof \Stripe\Exception\ApiErrorException) {
                    // Specific Stripe exception
                    error_log("specific exceptionHandler16()");
                    self::exceptionHandler($e, $metadata);
                } else {
                    // Normal exception
                    error_log("normal exceptionHandler16()");
                    self::exceptionHandler($e, $metadata);
                }
            }

            error_log('uniqueSubmissionId: ' . $uniqueSubmissionId);
            $submissionInfo = get_option( 'sfsi_' . $uniqueSubmissionId, array() );
            $submissionInfo['stripeData'] = $stripeData; // Perhaps used when we can "recover" a checkout session?
            update_option( 'sfsi_' . $uniqueSubmissionId, $submissionInfo );

            //header("HTTP/1.1 303 See Other");
            //header("Location: " . $checkout_session->url);
            SUPER_Common::output_message( array( 
                'error'=>false, 
                'msg' => '', 
                'redirect' => $checkout_session->url,
                'form_id' => absint($submissionInfo['form_id'])
            ));
            die();
        }

        // Whe payment intent failed
        public static function payment_intent_payment_failed($paymentIntent){
            $metadata = (isset($paymentIntent['metadata']) ? $paymentIntent['metadata'] : '');
            if( !is_array($metadata) ) {
                $metadata = json_decode($metadata, true);
            }
            // Delete contact entry status after failed payment
            $contact_entry_id = (isset($metadata['contact_entry_id']) ? absint($metadata['contact_entry_id']) : 0 );
            if( !empty($contact_entry_id) ) {
                error_log('delete entry #'.$contact_entry_id);
                wp_delete_post($contact_entry_id, true); // force delete, we no longer want it in our system
            }
            // Delete post after failed payment (only used for Front-end Posting feature)
            $frontend_post_id = (isset($metadata['_super_stripe_frontend_post_id']) ? absint($metadata['_super_stripe_frontend_post_id']) : 0 );
            if( !empty($frontend_post_id) ) {
                error_log('delete post #'.$frontend_post_id);
                wp_delete_post($frontend_post_id, true); // force delete, we no longer want it in our system
            }
            // Delete user after failed payment (only used for Register & Login feature)
            $frontend_user_id = (isset($metadata['_super_stripe_frontend_user_id']) ? absint($metadata['_super_stripe_frontend_user_id']) : 0 );
            if( !empty($frontend_user_id) ) {
                require_once( ABSPATH . 'wp-admin/includes/user.php' );
                error_log('delete user #'.$frontend_user_id);
                wp_delete_user($frontend_user_id);
            }
            // Delete any E-mail reminders based on this form ID as it's parent
            $email_reminders = (isset($metadata['super_forms_email_reminders']) ? $metadata['super_forms_email_reminders'] : array() );
            if (is_array($email_reminders) && count($email_reminders) > 0) {
                // Delete all the Children of the Parent Page
                foreach($email_reminders as $reminder){
                    error_log('delete reminder #'.$reminder);
                    wp_delete_post($reminder, true);  // force delete, we no longer want it in our system
                }
            }
        }

        // When charge succeeded
        public function charge_succeeded($paymentIntent){
            error_log("charge_succeeded()");
            // Get the metadata from the paymentIntent
            if(isset($paymentIntent['object']) && isset($paymentIntent['payment_intent']) && $paymentIntent['object']=='charge'){
                try {
                    $paymentIntent = \Stripe\PaymentIntent::retrieve(
                        $paymentIntent['payment_intent']
                    );
                    $paymentIntent = $paymentIntent->toArray();
                } catch( Exception $e ){
                    if ($e instanceof \Stripe\Error\Card ||
                        $e instanceof \Stripe\Exception\CardException ||
                        $e instanceof \Stripe\Exception\RateLimitException ||
                        $e instanceof \Stripe\Exception\InvalidRequestException ||
                        $e instanceof \Stripe\Exception\AuthenticationException ||
                        $e instanceof \Stripe\Exception\ApiConnectionException ||
                        $e instanceof \Stripe\Exception\ApiErrorException) {
                        // Specific Stripe exception
                        error_log("specific exceptionHandler9()");
                        self::exceptionHandler($e);
                    } else {
                        // Normal exception
                        error_log("normal exceptionHandler9()");
                        self::exceptionHandler($e);
                    }
                }
            }

            // Get metadata
            $metadata = (isset($paymentIntent['metadata']) ? $paymentIntent['metadata'] : '');
            if( !is_array($metadata) ) {
                $metadata = json_decode($metadata, true);
            }
            $form_id = (isset($metadata['form_id']) ? absint($metadata['form_id']) : 0 );
            
            if($form_id!==0){
                $settings = SUPER_Common::get_form_settings($form_id);
                // Update "New" transaction counter with 1
                $count = get_option( 'super_stripe_txn_count', 0 );
                update_option( 'super_stripe_txn_count', ($count+1) );
                // Update contact entry status after succesfull payment
                $contact_entry_id = (isset($metadata['contact_entry_id']) ? absint($metadata['contact_entry_id']) : 0 );
                if( !empty($contact_entry_id) ) {
                    if( !empty($settings['stripe_completed_entry_status']) ) {
                        update_post_meta( $contact_entry_id, '_super_contact_entry_status', $settings['stripe_completed_entry_status'] );
                    }
                }
                // Update post status after succesfull payment (only used for Front-end Posting feature)
                $frontend_post_id = (isset($metadata['frontend_post_id']) ? absint($metadata['frontend_post_id']) : 0 );
                if( !empty($frontend_post_id) ) {
                    if( (!empty($settings['stripe_completed_post_status'])) && (!empty($frontend_post_id)) ) {
                        wp_update_post( 
                            array(
                                'ID' => $frontend_post_id,
                                'post_status' => $settings['stripe_completed_post_status']
                            )
                        );
                    }
                }
                // Update user status after succesfull payment (only used for Register & Login feature)
                $frontend_user_id = (isset($metadata['frontend_user_id']) ? absint($metadata['frontend_user_id']) : 0 );
                if( !empty($frontend_user_id) ) {
                    if( (!empty($settings['register_login_action'])) && ($settings['register_login_action']=='register') && (!empty($frontend_user_id)) ) {
                        if( ($frontend_user_id!=0) && (!empty($settings['stripe_completed_signup_status'])) ) {
                            // Update login status
                            update_user_meta( $frontend_user_id, 'super_user_login_status', $settings['stripe_completed_signup_status'] );
                            // Update user role
                            $user_role = '';
                            if( !empty($settings['stripe_completed_user_role']) ) {
                                $user_role = $settings['stripe_completed_user_role'];
                            }
                            if( !empty($user_role) ) {
                                $userdata = array(
                                    'ID' => $frontend_user_id,
                                    'role' => $user_role
                                );
                                $result = wp_update_user( $userdata );
                                if( is_wp_error( $result ) ) {
                                    throw new Exception($return->get_error_message());
                                }
                            }
                        }
                    }
                }
            }
        }
        public function payment_intent_created($paymentIntent){
            error_log( 'payment_intent_created()', 0);
        }
        public function customer_created($paymentIntent){
            error_log( 'customer_created()', 0);
        }
        public function payment_method_attached($paymentIntent){
            error_log( 'payment_method_attached()', 0);
        }

        public static function super_enqueue_styles($styles){
            $styles['super-stripe-dashboard'] = array(
                'src'     => plugin_dir_url( __FILE__ ) . 'stripe-dashboard.css',
                'deps'    => array(),
                'version' => SUPER_VERSION,
                'media'   => 'all',
                'screen'  => array(
                    'super-forms_page_super_stripe_dashboard'
                ),
                'method'  => 'enqueue',
            );
            return $styles;
        }
        public static function super_enqueue_scripts($scripts){
            $global_settings = SUPER_Common::get_global_settings();
            $scripts['super-stripe-dashboard'] = array(
                'src'     => plugin_dir_url( __FILE__ ) . 'stripe-dashboard.js',
                'deps'    => array(),
                'version' => SUPER_VERSION,
                'footer'  => true,
                'screen'  => array( 
                    'super-forms_page_super_stripe_dashboard'
                ),
                'method'  => 'register', // Register because we need to localize it
                'localize'=> array(
                    'sandbox' => ( !empty($global_settings['stripe_mode']) ? 'true' : 'false' ),
                    'dashboardUrl' => 'https://dashboard.stripe.com' . ( !empty($global_settings['stripe_mode']) ? '/test' : '' ),
                    'viewOnlineInvoice' => esc_html__( 'View online invoice', 'super-forms' ),
                    'refundReasons' => array(
                        'duplicate' => esc_html__( 'Add more details about this refund', 'super-forms' ),
                        'fraudulent' => esc_html__( 'Why is this payment fraudulent?', 'super-forms' ),
                        'requested_by_customer' => esc_html__( 'Add more details about this refund', 'super-forms' ),
                        'other' => esc_html__( 'Add a reason for this refund', 'super-forms' ),
                        'other_note' => esc_html__( 'A note is required when a provided reason isn’t selected', 'super-forms' )
                    ),
                    'declineCodes' => array(
                        'authentication_required' => array(
                            'desc' => esc_html__( 'The card was declined as the transaction requires authentication.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again and authenticate their card when prompted during the transaction.', 'super-forms' )
                        ),
                        'approve_with_id' => array(
                            'desc' => esc_html__( 'The payment cannot be authorized.', 'super-forms' ),
                            'steps' => esc_html__( 'The payment should be attempted again. If it still cannot be processed, the customer needs to contact their card issuer.', 'super-forms' )
                        ),
                        'call_issuer' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
                        ),
                        'card_not_supported' => array(
                            'desc' => esc_html__( 'The card does not support this type of purchase.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer to make sure their card can be used to make this type of purchase.', 'super-forms' )
                        ),
                        'card_velocity_exceeded' => array(
                            'desc' => esc_html__( 'The customer has exceeded the balance or credit limit available on their card.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
                        ),
                        'currency_not_supported' => array(
                            'desc' => esc_html__( 'The card does not support the specified currency.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to check with the issuer whether the card can be used for the type of currency specified.', 'super-forms' )
                        ),
                        'do_not_honor' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
                        ),
                        'do_not_try_again' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
                        ),
                        'duplicate_transaction' => array(
                            'desc' => esc_html__( 'A transaction with identical amount and credit card information was submitted very recently.', 'super-forms' ),
                            'steps' => esc_html__( 'Check to see if a recent payment already exists.', 'super-forms' )
                        ),
                        'expired_card' => array(
                            'desc' => esc_html__( 'The card has expired.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should use another card.', 'super-forms' )
                        ),
                        'fraudulent' => array(
                            'desc' => esc_html__( 'The payment has been declined as Stripe suspects it is fraudulent.', 'super-forms' ),
                            'steps' => esc_html__( 'Do not report more detailed information to your customer.  Instead, present as you would the ', 'super-forms' )
                        ),
                        'generic_decline' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
                        ),
                        'incorrect_number' => array(
                            'desc' => esc_html__( 'The card number is incorrect.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again using the correct card number.', 'super-forms' )
                        ),
                        'incorrect_cvc' => array(
                            'desc' => esc_html__( 'The CVC number is incorrect.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again using the correct CVC.', 'super-forms' )
                        ),
                        'incorrect_pin' => array(
                            'desc' => esc_html__( 'The PIN entered is incorrect. This decline code only applies to payments made with a card reader. ', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again using the correct PIN.', 'super-forms' )
                        ),
                        'incorrect_zip' => array(
                            'desc' => esc_html__( 'The ZIP/postal code is incorrect.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again using the correct billing ZIP/postal code.', 'super-forms' )
                        ),
                        'insufficient_funds' => array(
                            'desc' => esc_html__( 'The card has insufficient funds to complete the purchase.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should use an alternative payment method.', 'super-forms' )
                        ),
                        'invalid_account' => array(
                            'desc' => esc_html__( 'The card or account the card is connected to, is invalid.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer to check that the card is working correctly.', 'super-forms' )
                        ),
                        'invalid_amount' => array(
                            'desc' => esc_html__( 'The payment amount is invalid or exceeds the amount that is allowed.', 'super-forms' ),
                             'steps' => esc_html__( 'If the amount appears to be correct, the customer needs to check with their card issuer that they can make purchases of that amount.', 'super-forms' )
                        ),
                        'invalid_cvc' => array(
                            'desc' => esc_html__( 'The CVC number is incorrect.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again using the correct CVC.', 'super-forms' )
                        ),
                        'invalid_expiry_year' => array(
                            'desc' => esc_html__( 'The expiration year invalid.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again using the correct expiration date.', 'super-forms' )
                        ),
                        'invalid_number' => array(
                            'desc' => esc_html__( 'The card number is incorrect.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again using the correct card number.', 'super-forms' )
                        ),
                        'invalid_pin' => array(
                            'desc' => esc_html__( 'The PIN entered is incorrect. This decline code only applies to payments made with a card reader.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again using the correct PIN.', 'super-forms' )
                        ),
                        'issuer_not_available' => array(
                            'desc' => esc_html__( 'The card issuer could not be reached so the payment could not be authorized.', 'super-forms' ),
                            'steps' => esc_html__( 'The payment should be attempted again. If it still cannot be processed, the customer needs to contact their card issuer.', 'super-forms' )
                        ),
                        'lost_card' => array(
                            'desc' => esc_html__( 'The payment has been declined because the card is reported lost.', 'super-forms' ),
                            'steps' => esc_html__( 'The specific reason for the decline should not be reported to the customer. Instead, it needs to be presented as a generic decline.', 'super-forms' )
                        ),
                        'merchant_blacklist' => array(
                            'desc' => esc_html__( 'The payment has been declined because it matches a value on the Stripe user’s block list.', 'super-forms' ),
                            'steps' => esc_html__( 'Do not report more detailed information to your customer. Instead, present as you would the ', 'super-forms' )
                        ),
                        'new_account_information_available' => array(
                            'desc' => esc_html__( 'The card or account the card is connected to, is invalid.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
                        ),
                        'no_action_taken' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
                        ),
                        'not_permitted' => array(
                            'desc' => esc_html__( 'The payment is not permitted.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
                        ),
                        'offline_pin_required' => array(
                            'desc' => esc_html__( 'The card has been declined as it requires a PIN.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should try again by inserting their card and entering a PIN.', 'super-forms' )
                        ),
                        'online_or_offline_pin_required' => array(
                            'desc' => esc_html__( 'The card has been declined as it requires a PIN.', 'super-forms' ),
                            'steps' => esc_html__( 'If the card reader supports Online PIN, the customer should be prompted for a PIN without a new transaction being created. If the card reader does not support Online PIN, the customer should try again by inserting their card and entering a PIN.', 'super-forms' )
                        ),
                        'pickup_card' => array(
                            'desc' => esc_html__( 'The card cannot be used to make this payment (it is possible it has been reported lost or stolen).', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
                        ),
                        'pin_try_exceeded' => array(
                            'desc' => esc_html__( 'The allowable number of PIN tries has been exceeded.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer must use another card or method of payment.', 'super-forms' )
                        ),
                        'processing_error' => array(
                            'desc' => esc_html__( 'An error occurred while processing the card.', 'super-forms' ),
                            'steps' => esc_html__( 'The payment should be attempted again. If it still cannot be processed, try again later.', 'super-forms' )
                        ),
                        'reenter_transaction' => array(
                            'desc' => esc_html__( 'The payment could not be processed by the issuer for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The payment should be attempted again. If it still cannot be processed, the customer needs to contact their card issuer.', 'super-forms' )
                        ),
                        'restricted_card' => array(
                            'desc' => esc_html__( 'The card cannot be used to make this payment (it is possible it has been reported lost or stolen).', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
                        ),
                        'revocation_of_all_authorizations' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
                        ),
                        'revocation_of_authorization' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
                        ),
                        'security_violation' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
                        ),
                        'service_not_allowed' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
                        ),
                        'stolen_card' => array(
                            'desc' => esc_html__( 'The payment has been declined because the card is reported stolen.', 'super-forms' ),
                            'steps' => esc_html__( 'The specific reason for the decline should not be reported to the customer. Instead, it needs to be presented as a generic decline.', 'super-forms' )
                        ),
                        'stop_payment_order' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should contact their card issuer for more information.', 'super-forms' )
                        ),
                        'testmode_decline' => array(
                            'desc' => esc_html__( 'A Stripe test card number was used.', 'super-forms' ),
                            'steps' => esc_html__( 'A genuine card must be used to make a payment.', 'super-forms' )
                        ),
                        'transaction_not_allowed' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'The customer needs to contact their card issuer for more information.', 'super-forms' )
                        ),
                        'try_again_later' => array(
                            'desc' => esc_html__( 'The card has been declined for an unknown reason.', 'super-forms' ),
                            'steps' => esc_html__( 'Ask the customer to attempt the payment again. If subsequent payments are declined, the customer should contact their card issuer for more information.', 'super-forms' )
                        ),
                        'withdrawal_count_limit_exceeded' => array(
                            'desc' => esc_html__( 'The customer has exceeded the balance or credit limit available on their card. ', 'super-forms' ),
                            'steps' => esc_html__( 'The customer should use an alternative payment method.', 'super-forms' )
                        )
                    )       
                )
            );
            $scripts['currencyFormatter'] = array(
                'src'     => plugin_dir_url( __FILE__ ) . 'currencyFormatter.min.js',
                'deps'    => array('super-stripe-dashboard'),
                'version' => SUPER_VERSION,
                'footer'  => true,
                'screen'  => array( 
                    'super-forms_page_super_stripe_dashboard'
                ),
                'method'  => 'enqueue'
            );
            if(isset($scripts['masked-currency'])){
                $scripts['masked-currency']['screen'][] = 'super-forms_page_super_stripe_dashboard';
            }

            return $scripts;
        }


        public static function setAppInfo(){
            require_once 'stripe-php/init.php';
            \Stripe\Stripe::setAppInfo(
                'Super Forms - Stripe Add-on',
                SUPER_VERSION,
                'https://f4d.nl/super-forms'
            );
            \Stripe\Stripe::setApiKey('sk_test_CczNHRNSYyr4TenhiCp7Oz05');
            \Stripe\Stripe::setApiVersion('2019-12-03');
        }
        
        public static function delete_list_views_filter($views){
            if(!isset($views['trash'])) return array();
            return array('trash' => $views['trash']);
        }

        public static function getTransactionId($d){
            // @important: determine the Transaction ID based on the 'object'
            $txn_id = $d['id'];
            if( $d['object']=='payment_intent' ) {
                $txn_id = $d['id']; // e.g: pi_1FwyfIFKn7uROhgCKO8iiuFF
            }
            if( $d['object']=='charge' ) {
                if( !empty($d['payment_intent']) ) {
                    $txn_id = $d['payment_intent']; // e.g: pi_1FwyfIFKn7uROhgCKO8iiuFF
                }
            }
            if( $d['object']=='dispute' ) {
                if( !empty($d['charge']) ) {
                    $txn_id = $d['charge']; // e.g: ch_1FxHBzFKn7uROhgCFnIWl62A
                }
                if( !empty($d['payment_intent']) ) {
                    $txn_id = $d['payment_intent']; // e.g: pi_1FwyfIFKn7uROhgCKO8iiuFF
                }
            }
            return $txn_id;
        }

        /**
         * Add the Stripe transaction link to the entry info/data page
         *
         * @since       1.0.0
         */
        public static function add_transaction_link($entry_id) {
            $post_id = get_post_meta( $entry_id, '_super_stripe_txn_id', true );
            if(!empty($post_id)){
                $data = get_post_meta( $post_id, '_super_txn_data', true );
                $txn_id = self::getTransactionId($data);
                ?>
                <div class="misc-pub-section">
                    <span><?php echo esc_html__('Stripe Transaction', 'super-forms' ).':'; ?> <strong><?php echo '<a target="_blank" href="' . esc_url('https://dashboard.stripe.com/payments/' . $txn_id) . '">' . substr($txn_id, 0, 15) . ' ...</a>'; ?></strong></span>
                </div>
                <?php
            }
        }

        /**
         * Add Stripe styles
         *
         *  @since      1.0.0
         */
        public static function add_stripe_styles($style_content, $atts) {
            //$atts['id'] // form id
            //$atts['settings'] // form settings
            $styles = "
            .super-stripe-ideal-element {
                height: 33px;
                width: 300px;
            }
            .super-field-size-large .super-stripe-ideal-element {
                height: 43px;
            }
            .super-field-size-huge .super-stripe-ideal-element {
                height: 53px;
            }
            .super-stripe-cc-element,
            .super-stripe-iban-element {
                padding-top: 8px;
                padding-left: 15px;
                padding-right: 0px;
                padding-bottom: 8px;
                height: 33px;
                width: 300px;
            }
            .super-field-size-large .super-stripe-cc-element,
            .super-field-size-large .super-stripe-iban-element {
                padding-top: 13px;
                padding-left: 15px;
                padding-right: 0px;
                padding-bottom: 13px;
                height: 43px;
            }
            .super-field-size-huge .super-stripe-cc-element,
            .super-field-size-huge .super-stripe-iban-element {
                padding-top: 17px;
                padding-left: 15px;
                padding-right: 0px;
                padding-bottom: 17px;
                height: 53px;
            }
            .super-style-one .super-stripe-base:before {
                content: '';
                position: absolute;
                left: 0;
                width: 0%;
                bottom: 1px;
                margin-top: 2px;
                bottom: -8px;
                border-bottom: 4px solid #cdcdcd;
                z-index: 2;
                -webkit-transition: width .4s ease-out;
                -moz-transition: width .4s ease-out;
                -o-transition: width .4s ease-out;
                transition: width .4s ease-out;
            }
            .super-style-one .super-stripe-focus:before {
                width: 100%;
            }";
            return $style_content.$styles;
        }


        /**
         * Create Stripe Customer (required for subscriptions)
         *
         *  @since      1.0.0
         */
        public static function stripe_create_subscription() {
            self::setAppInfo();
            $data = $_POST['data'];
            $payment_method = $_POST['payment_method'];
            $metadata = ( isset($_POST['metadata']) ? $_POST['metadata'] : array() );
            $form_id = absint($data['hidden_form_id']['value']);
            $settings = SUPER_Common::get_form_settings($form_id);
            
            // Check if plan ID is empty (is required)
            if(empty($settings['stripe_plan_id'])){
                SUPER_Common::output_message( array(
                    'msg' => esc_html__( 'Subscription plan ID cannot be empty!', 'super-forms' ),
                    'form_id' => absint($form_id)
                ));
            }

            // Check if the user is logged in
            // If so, we will want to save the stripe customer ID for this wordpress user
            $user_id = get_current_user_id();
            if(isset($metadata['frontend_user_id'])){
                if(absint($metadata['frontend_user_id'])!==0){
                    $user_id = $metadata['frontend_user_id'];
                }
            }
            $super_stripe_cus = get_user_meta( $user_id, 'super_stripe_cus', true );

            try {
                $create_new_customer = true;
                // Check if user is logged in, if so check if this is an existing customer
                // If customer exists, we want to update the `default_payment_method` and `invoice_settings.default_payment_method`
                if( !empty($super_stripe_cus) ) {
                    $customer = \Stripe\Customer::retrieve($super_stripe_cus);
                }
                if( !empty($customer) ) {
                    // Check if customer was deleted
                    if(!empty($customer['deleted']) && $customer['deleted']==true){
                        // Customer was deleted, we should create a new
                    }else{
                        // The customer exists, let's update the payment method for this customer
                        $paymentMethod = \Stripe\PaymentMethod::retrieve($payment_method); // e.g: pm_1FYeznClCIKljWvssSbEXRww
                        $paymentMethod->attach(['customer' => $customer->id]);
                        // Once the payment method has been attached to your customer, 
                        // update the customers default payment method
                        \Stripe\Customer::update($customer->id,[
                            'invoice_settings' => [
                                'default_payment_method' => $paymentMethod->id,
                            ],
                        ]);
                        // Make sure we do not create a new customer
                        $create_new_customer = false; 
                    }
                }
                if($create_new_customer){
                    // Customer doesn't exists, create a new customer
                    $customer = \Stripe\Customer::create([
                        'payment_method' => $payment_method,
                        'email' => 'jenny.rosen@example.com',
                        'invoice_settings' => [
                            'default_payment_method' => $payment_method // Creating subscriptions automatically charges customers because the default payment method is set.
                        ],
                    ]);
                    $paymentMethod->attach(['customer' => $customer->id]);
                    // Save the stripe customer ID for this wordpress user
                    update_user_meta( $user_id, 'super_stripe_cus', $customer->id);
                }
            } catch( Exception $e ){
                if ($e instanceof \Stripe\Error\Card ||
                    $e instanceof \Stripe\Exception\CardException ||
                    $e instanceof \Stripe\Exception\RateLimitException ||
                    $e instanceof \Stripe\Exception\InvalidRequestException ||
                    $e instanceof \Stripe\Exception\AuthenticationException ||
                    $e instanceof \Stripe\Exception\ApiConnectionException ||
                    $e instanceof \Stripe\Exception\ApiErrorException) {
                    // Specific Stripe exception
                    if($e->getCode()===0){
                        // If no such stripe customer exists we do not output the error instead we create a new stripe customer
                        // Customer doesn't exists, create a new customer
                        $customer = \Stripe\Customer::create([
                            'payment_method' => $payment_method,
                            'email' => 'jenny.rosen@example2.com',
                            'invoice_settings' => [
                                'default_payment_method' => $payment_method // Creating subscriptions automatically charges customers because the default payment method is set.
                            ],
                        ]);
                        $paymentMethod->attach(['customer' => $customer->id]);
                        // Save the stripe customer ID for this wordpress user
                        update_user_meta( $user_id, 'super_stripe_cus', $customer->id);
                    }else{
                        error_log("exceptionHandler10()");
                        self::exceptionHandler($e, $metadata);
                    }
                } else {
                    // Normal exception
                    error_log("normal exceptionHandler10()");
                    self::exceptionHandler($e, $metadata);
                }
            }

            try {
                // Attempt to create the subscriptions
                $subscription = \Stripe\Subscription::create([
                    'customer' => $customer->id,
                    'items' => [
                        [
                            'plan' => $settings['stripe_plan_id'],
                        ],
                    ],
                    // 'trial_period_days' => 0, // Integer representing the number of trial period days before the customer is charged for the first time. This will always overwrite any trials that might apply via a subscribed plan.
                    'payment_behavior' => 'allow_incomplete',
                    'expand' => ['latest_invoice.payment_intent'],
                    'metadata' => $metadata
                ]);
            } catch( Exception $e ){
                if ($e instanceof \Stripe\Error\Card ||
                    $e instanceof \Stripe\Exception\CardException ||
                    $e instanceof \Stripe\Exception\RateLimitException ||
                    $e instanceof \Stripe\Exception\InvalidRequestException ||
                    $e instanceof \Stripe\Exception\AuthenticationException ||
                    $e instanceof \Stripe\Exception\ApiConnectionException ||
                    $e instanceof \Stripe\Exception\ApiErrorException) {
                    // Specific Stripe exception
                    error_log("specific exceptionHandler11()");
                    self::exceptionHandler($e, $metadata);
                } else {
                    // Normal exception
                    error_log("normal exceptionHandler11()");
                    self::exceptionHandler($e, $metadata);
                }
            }

            // Update PaymentIntent with metadata
            \Stripe\PaymentIntent::update( $subscription->latest_invoice->payment_intent->id, array( 'metadata' => $metadata ) );

            // Depending on the outcome do things:
            $paymentintent_status = (isset($subscription->latest_invoice->payment_intent) ? $subscription->latest_invoice->payment_intent->status : '');

            // Outcome 3: Payment fails
            if (($subscription->status == 'incomplete') && ($subscription->latest_invoice->status == 'open') && ($paymentintent_status == 'requires_payment_method')) {
                // The charge attempt for the subscription failed, please try with a new payment method
                self::payment_intent_payment_failed( array( 'metadata' => $metadata ) );
                SUPER_Common::output_message( array(
                    'msg' => esc_html__( 'The charge attempt for the subscription failed, please try with a new payment method', 'super-forms' ),
                    'form_id' => absint($form_id)
                ));
            }

            echo json_encode( array( 
                'client_secret' => $subscription->latest_invoice->payment_intent->client_secret,
                'subscription_status' => $subscription->status,
                'invoice_status' => $subscription->latest_invoice->status,
                'paymentintent_status' => (isset($subscription->latest_invoice->payment_intent) ? $subscription->latest_invoice->payment_intent->status : ''),
                'metadata' => $metadata
            ) );

            // // Outcome 1: Payment succeeds
            // if (($subscription->status == 'active') && ($subscription->latest_invoice->status == 'paid') && ($paymentintent_status == 'succeeded')) {
            //     //console.log('Payment succeeds');
            //     // The payment has succeeded. Display a success message.
            //     //console.log('The payment has succeeded, show success message1.');
            //     //$form.data('is-doing-things', ''); // Finish form submission
            // }
            // // Outcome 2: Trial starts
            // if (($subscription->status == 'trialing') && ($subscription->latest_invoice->status == 'paid')) {
            //     //console.log('Trial starts');
            //     //$form.data('is-doing-things', ''); // Finish form submission
            // }

            // // Outcome 4: Requires action
            // if (($subscription->status == 'incomplete') && ($subscription->latest_invoice->status == 'open') && ($paymentintent_status == 'requires_action')) {
            //     Notify customer that further action is required
            //     stripe.confirmCardPayment(result.client_secret).then(function (result) {
            //         if (result.error) {
            //             // Display error.msg in your UI.
            //             SUPER.stripe_proceed(result, $form, $oldHtml);
            //         } else {
            //             // The payment has succeeded. Display a success message.
            //             console.log('The payment has succeeded, show success message2.');
            //             $form.data('is-doing-things', ''); // Finish form submission
            //         }
            //     });
            // }



            // // Outcome 1: Payment succeeds
            // if( ($subscription->status=='active') && ($subscription->latest_invoice->status=='paid') && ($subscription->latest_invoice->payment_intent->status=='succeeded') ) {
            // }
            // // Outcome 2: Trial starts
            // if( ($subscription->status=='trialing') && ($subscription->latest_invoice->status=='paid') ) {
            // }
            // // Outcome 3: Payment fails
            // if( ($subscription->status=='incomplete') && ($subscription->latest_invoice->status=='open') && ($subscription->latest_invoice->payment_intent->status=='requires_payment_method') ) {
            // }
            // // Outcome 4: Requires action
            // if( ($subscription->status=='incomplete') && ($subscription->latest_invoice->status=='open') && ($subscription->latest_invoice->payment_intent->status=='requires_action') ) {
            // }

            die();
        }

        // Create PaymentIntent
        public static function createPaymentIntent($payment_method, $data, $settings, $amount, $currency, $description, $metadata){
            error_log("createPaymentIntent()");
            error_log("payment_method: " . $payment_method);
            error_log("amount: " . $amount);
            error_log("currency: " . $currency);
            error_log("description: " . $description);
            try {
                $data = array(
                    'amount' => $amount, // The amount to charge times hundred (because amount is in cents)
                    'currency' => ($payment_method==='ideal' || $payment_method==='sepa_debit' ? 'eur' : $currency), // iDeal only accepts "EUR" as a currency
                    'description' => $description,
                    'payment_method_types' => [$payment_method], // e.g: ['card','ideal','sepa_debit'], 
                    // Shipping information for this PaymentIntent.
                    'shipping' => array(
                        'address' => array(
                            'line1' => SUPER_Common::email_tags( $settings['stripe_line1'], $data, $settings ),
                            'line2' => SUPER_Common::email_tags( $settings['stripe_line2'], $data, $settings ),
                            'city' => SUPER_Common::email_tags( $settings['stripe_city'], $data, $settings ),
                            'country' => SUPER_Common::email_tags( $settings['stripe_country'], $data, $settings ),
                            'postal_code' => SUPER_Common::email_tags( $settings['stripe_postal_code'], $data, $settings ),
                            'state' => SUPER_Common::email_tags( $settings['stripe_state'], $data, $settings )
                        ),
                        'name' => SUPER_Common::email_tags( $settings['stripe_name'], $data, $settings ),
                        'phone' => SUPER_Common::email_tags( $settings['stripe_phone'], $data, $settings ),
                        'carrier' => SUPER_Common::email_tags( $settings['stripe_carrier'], $data, $settings ),
                        'tracking_number' => SUPER_Common::email_tags( $settings['stripe_tracking_number'], $data, $settings )
                    ),
                    'metadata' => $metadata
                );
                // Only add receipt email if E-mail address was set
                if(!empty($settings['stripe_email'])){
                    $data['stripe_email'] = SUPER_Common::email_tags( $settings['stripe_email'], $data, $settings ); // E-mail address that the receipt for the resulting payment will be sent to.
                }
                if( $payment_method=='sepa_debit' ) {
                    $data['setup_future_usage'] = 'off_session'; // SEPA Direct Debit only accepts an off_session value for this parameter.
                }
                $intent = \Stripe\PaymentIntent::create($data);

                //error_log('$payment_method 2: '. $payment_method, 0);
                //$data = array(
                //    'amount' => $amount, // The amount to charge times hundred (because amount is in cents)
                //    'currency' => ($payment_method==='ideal' || $payment_method==='sepa_debit' ? 'eur' : $currency), // iDeal only accepts "EUR" as a currency
                //    'description' => $description,
                //    'payment_method_types' => [$payment_method], // e.g: ['card','ideal','sepa_debit'], 
                //    'receipt_email' => SUPER_Common::email_tags( $settings['stripe_email'], $data, $settings ), // E-mail address that the receipt for the resulting payment will be sent to.
                //    // Shipping information for this PaymentIntent.
                //    'shipping' => array(
                //        'address' => array(
                //            'line1' => SUPER_Common::email_tags( $settings['stripe_line1'], $data, $settings ),
                //            'line2' => SUPER_Common::email_tags( $settings['stripe_line2'], $data, $settings ),
                //            'city' => SUPER_Common::email_tags( $settings['stripe_city'], $data, $settings ),
                //            'country' => SUPER_Common::email_tags( $settings['stripe_country'], $data, $settings ),
                //            'postal_code' => SUPER_Common::email_tags( $settings['stripe_postal_code'], $data, $settings ),
                //            'state' => SUPER_Common::email_tags( $settings['stripe_state'], $data, $settings )
                //        ),
                //        'name' => SUPER_Common::email_tags( $settings['stripe_name'], $data, $settings ),
                //        'phone' => SUPER_Common::email_tags( $settings['stripe_phone'], $data, $settings ),
                //        'carrier' => SUPER_Common::email_tags( $settings['stripe_carrier'], $data, $settings ),
                //        'tracking_number' => SUPER_Common::email_tags( $settings['stripe_tracking_number'], $data, $settings )
                //    ),
                //    'metadata' => $metadata
                //);
                //if( $payment_method=='sepa_debit' ) {
                //    $data['setup_future_usage'] = 'off_session'; // SEPA Direct Debit only accepts an off_session value for this parameter.
                //}
                //$intent = \Stripe\PaymentIntent::create($data);
                //error_log("intent:" . json_encode($intent));
            } catch( Exception $e ){
                if ($e instanceof \Stripe\Error\Card ||
                    $e instanceof \Stripe\Exception\CardException ||
                    $e instanceof \Stripe\Exception\RateLimitException ||
                    $e instanceof \Stripe\Exception\InvalidRequestException ||
                    $e instanceof \Stripe\Exception\AuthenticationException ||
                    $e instanceof \Stripe\Exception\ApiConnectionException ||
                    $e instanceof \Stripe\Exception\ApiErrorException) {
                    // Specific Stripe exception
                    error_log("specific exceptionHandler15()");
                    self::exceptionHandler($e, $metadata);
                } else {
                    // Normal exception
                    error_log("normal exceptionHandler15()");
                    self::exceptionHandler($e, $metadata);
                }
            }
            return $intent;
        }



        /**
         * Change row actions
         *
         *  @since      1.0.0
         */
        public static function remove_row_actions( $actions ) {
            if( (get_post_type()==='super_stripe_txn') || (get_post_type()==='super_stripe_sub') ) {
                if( isset( $actions['trash'] ) ) {
                    unset( $actions['trash'] );
                }
                unset( $actions['inline hide-if-no-js'] );
                unset( $actions['view'] );
                unset( $actions['edit'] );
            }
            return $actions;
        }


        public static function stripe_element_scripts() {
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_VERSION, false );
            $handle = 'super-stripe';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'stripe-elements.js', array( 'stripe-v3', 'jquery', 'super-common' ), SUPER_VERSION, false );  
            $global_settings = SUPER_Common::get_global_settings();
            if(empty($global_settings['stripe_pk'])){
                $global_settings['stripe_pk'] = 'pk_test_1i3UyFAuxbe3Po62oX1FV47U';
            }
            $idealPadding = '9px 15px 9px 15px';
            if( (isset($settings['theme_field_size'])) && ($settings['theme_field_size']=='large') ) {
                $idealPadding = '13px 15px 13px 15px';
            }
            if( (isset($settings['theme_field_size'])) && ($settings['theme_field_size']=='huge') ) {
                $idealPadding = '18px 15px 18px 15px';
            }
            wp_localize_script(
                $handle,
                $name,
                array( 
                    'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
                    'stripe_pk' => $global_settings['stripe_pk'],
                    'choose_payment_method' => esc_html__( 'Please choose a payment method!', 'super-forms' ),
                    'ideal_subscription_error' => esc_html__( 'Subscriptions can not be paid through iDeal, please choose a different payment method!', 'super-forms' ),
                    'styles' => array(
                        'fontFamily' => ( isset( $settings['font_global_family'] ) ? stripslashes($settings['font_global_family']) : '"Helvetica", "Arial", sans-serif' ),
                        'fontSize' => ( isset( $settings['font_global_size'] ) ? $settings['font_global_size'] : 12 ),
                        'color' => ( isset( $settings['theme_field_colors_font'] ) ? $settings['theme_field_colors_font'] : '#444444' ),
                        'colorFocus' => ( isset( $settings['theme_field_colors_font_focus'] ) ? $settings['theme_field_colors_font_focus'] : '#444444' ),
                        'placeholder' => ( isset( $settings['theme_field_colors_placeholder'] ) ? $settings['theme_field_colors_placeholder'] : '#444444' ),
                        'placeholderFocus' => ( isset( $settings['theme_field_colors_placeholder_focus'] ) ? $settings['theme_field_colors_placeholder_focus'] : '#444444' ),
                        'iconColor' => ( isset( $settings['theme_icon_color'] ) ? $settings['theme_icon_color'] : '#B3DBDD' ),
                        'iconColorFocus' => ( isset( $settings['theme_icon_color_focus'] ) ? $settings['theme_icon_color_focus'] : '#4EB1B6' ),
                        'idealPadding' => $idealPadding
                    )
                )
            );
            wp_enqueue_script( $handle );
        }


        /**
         * Update transaction counter and enqueue Stripe element scripts
         *
         * @param  string $current_screen
         * 
         * @since       1.0.0
        */
        public function after_screen( $current_screen ) {
            if( $current_screen->id=='super-forms_page_super_stripe_dashboard' ) {
                update_option( 'super_stripe_txn_count', 0 );
            } 
            if( $current_screen->id=='super-forms_page_super_create_form' ) {
                self::stripe_element_scripts();
            }
        }


        /**
         * Save Post ID into session after inserting post with Front-end Posting
         * This way we can add it to the Stripe metadata and use it later to update the post status after payment is completed
         * array( 'post_id'=>$post_id, 'data'=>$data, 'atts'=>$atts )
         *
         *  @since      1.0.0
         */
        //public function save_post_id($atts) {
        //    SUPER_Common::setClientData( array( 'name'=> '_super_stripe_frontend_post_id', 'value'=>absint($atts['post_id'] ) ) );
        //}


        /**
         * Save User ID into session after creating user Register & Login
         * This way we can add it to the Stripe metadata and use it later to update the user status after payment is completed
         * array( 'user_id'=>$user_id, 'atts'=>$atts )
         *
         *  @since      1.0.0
         */
        //public function save_user_id($atts) {
        //    SUPER_Common::setClientData( array( 'name'=> '_super_stripe_frontend_user_id', 'value'=>absint($atts['user_id'] ) ) );
        //}


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
            // Dashboard Page
            add_submenu_page(
                'super_forms', 
                esc_html__( 'Stripe', 'super-forms' ), 
                '<span class="super-stripe-icon" style="' . $styles . '"></span>' . esc_html__( 'Stripe', 'super-forms' ) . $count,
                'manage_options', 
                'super_stripe_dashboard', 
                'SUPER_Stripe::stripe_dashboard'
            );
        }

        public static function stripe_dashboard(){
            $global_settings = SUPER_Common::get_global_settings();
            // Check if the API key is correctly configured
            $configured = true;
            if( (!empty($global_settings['stripe_mode'])) && ((empty($global_settings['stripe_sandbox_public_key'])) || (empty($global_settings['stripe_sandbox_secret_key']))) ) {
                $configured = false;
                echo '<div class="super-stripe-notice">';
                echo sprintf( esc_html__( 'Stripe Sandbox API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>' );
                echo '</div>';
            }
            if( (empty($global_settings['stripe_mode'])) && ((empty($global_settings['stripe_live_public_key'])) || (empty($global_settings['stripe_live_secret_key']))) ) {
                $configured = false;
                echo '<div class="super-stripe-notice">';
                echo sprintf( esc_html__( 'Stripe Live API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>' );
                echo '</div>';
            }

            $dashboardUrl = 'https://dashboard.stripe.com/';
            if( !empty($global_settings['stripe_mode']) ) {
                $dashboardUrl .= 'test/';
            }

            if($configured){
                self::setAppInfo();
                require_once('dashboard.php');
            }
        }
        public static function getInvoice($id) {
            return \Stripe\Invoice::retrieve($id);
        }
        public static function exceptionHandler($e, $metadata=array()){
            error_log("e: " . json_encode($e));
            error_log("err: " . json_encode($e->getError()));
            error_log("metadata: " . json_encode($metadata));
            $form_id = SUPER_Common::cleanupFormSubmissionInfo($metadata['sfsi_id'], '');
            if(isset($metadata['form_id'])) $form_id = $metadata['form_id'];
            SUPER_Common::output_message( array(
                'msg' => $e->getMessage(),
                'form_id' => absint($form_id)
            ));
            die();

            // deprecated self::payment_intent_payment_failed( array( 'metadata' => $metadata ) );
            //echo json_encode( array( 'error' => array( 'message' => $e->getMessage() ) ) ); 
            //{"error":{"message":"No such price: 'price_1KJINrFKn7uROhgCgsntnfbq'; a similar object exists in live mode, but a test mode key was used to make this request."}}
            // Print error message
            // catch(\Stripe\Error\Card $e) {
            //     // Since it's a decline, \Stripe\Error\Card will be caught
            //     $message = $e->getJsonBody()['error']['message'];
            //     echo json_encode( array( 'error' => array( 'message' => $message ) ) );
            //     die();
            // } catch(\Stripe\Exception\CardException $e) {
            //     // Since it's a decline, \Stripe\Exception\CardException will be caught
            //     echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
            //     die();
            // } catch (\Stripe\Exception\RateLimitException $e) {
            //     // Too many requests made to the API too quickly
            //     echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
            //     die();
            // } catch (\Stripe\Exception\InvalidRequestException $e) {
            //     // Invalid parameters were supplied to Stripe's API
            //     echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
            //     die();
            // } catch (\Stripe\Exception\AuthenticationException $e) {
            //     // Authentication with Stripe's API failed
            //     // (maybe you changed API keys recently)
            //     echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
            //     die();
            // } catch (\Stripe\Exception\ApiConnectionException $e) {
            //     // Network communication with Stripe failed
            //     echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) );
            //     die();
            // } catch (\Stripe\Exception\ApiErrorException $e) {
            //     // Display a very generic error to the user, and maybe send
            //     // yourself an email
            //     echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured with the Stripe API', 'super-forms' ) ) ) );
            //     die();
            // } catch (Exception $e) {
            //     // Something else happened, completely unrelated to Stripe
            //     echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured', 'super-forms' ) ) ) );
            //     die();
            // }

            // if( isset($e) && function_exists($e->getError()) && isset($e->getError()->message) )
            //     echo json_encode( array( 'error' => array( 'message' => $e->getError()->message ) ) ); die();
            // echo json_encode( array( 'error' => array( 'message' => esc_html__( 'An error occured', 'super-forms' ) ) ) ); die();
        }
        public static function createRefund($payment_intent, $reason, $amount) {
            error_log("createRefund()");
            try {
                $response = \Stripe\Refund::create([
                    'payment_intent' => $payment_intent,
                    'reason' => $reason,
                    'amount' => $amount
                ]);
            } catch( Exception $e ){
                if ($e instanceof \Stripe\Error\Card ||
                    $e instanceof \Stripe\Exception\CardException ||
                    $e instanceof \Stripe\Exception\RateLimitException ||
                    $e instanceof \Stripe\Exception\InvalidRequestException ||
                    $e instanceof \Stripe\Exception\AuthenticationException ||
                    $e instanceof \Stripe\Exception\ApiConnectionException ||
                    $e instanceof \Stripe\Exception\ApiErrorException) {
                    // Specific Stripe exception
                    error_log("specific exceptionHandler1()");
                    self::exceptionHandler($e);
                } else {
                    // Normal exception
                    error_log("normal exceptionHandler1()");
                    self::exceptionHandler($e);
                }
            }
            echo json_encode($response->toArray(), JSON_PRETTY_PRINT);
            die();
        }
        public static function getPaymentIntents( $formatted=true, $limit=20, $starting_after=null, $created=null, $customer=null, $ending_before=null) {
            error_log("getPaymentIntents()");
            if($limit==1){
                try {
                    $paymentIntents->data[] = \Stripe\PaymentIntent::retrieve(
                        $starting_after // in this case it holds the payment intent ID
                    );
                } catch( Exception $e ){
                    if ($e instanceof \Stripe\Error\Card ||
                        $e instanceof \Stripe\Exception\CardException ||
                        $e instanceof \Stripe\Exception\RateLimitException ||
                        $e instanceof \Stripe\Exception\InvalidRequestException ||
                        $e instanceof \Stripe\Exception\AuthenticationException ||
                        $e instanceof \Stripe\Exception\ApiConnectionException ||
                        $e instanceof \Stripe\Exception\ApiErrorException) {
                        // Specific Stripe exception
                        error_log("specific exceptionHandler2()");
                        self::exceptionHandler($e);
                    } else {
                        // Normal exception
                        error_log("normal exceptionHandler2()");
                        self::exceptionHandler($e);
                    }
                }
            }else{
                try {
                    $paymentIntents = \Stripe\PaymentIntent::all([
                        'limit' => $limit,
                        'starting_after' => $starting_after,
                        'created' => $created,
                        'customer' => $customer,
                        'ending_before' => $ending_before
                    ]);
                } catch( Exception $e ){
                    if ($e instanceof \Stripe\Error\Card ||
                        $e instanceof \Stripe\Exception\CardException ||
                        $e instanceof \Stripe\Exception\RateLimitException ||
                        $e instanceof \Stripe\Exception\InvalidRequestException ||
                        $e instanceof \Stripe\Exception\AuthenticationException ||
                        $e instanceof \Stripe\Exception\ApiConnectionException ||
                        $e instanceof \Stripe\Exception\ApiErrorException) {
                        // Specific Stripe exception
                        error_log("specific exceptionHandler3()");
                        self::exceptionHandler($e);
                    } else {
                        // Normal exception
                        error_log("normal exceptionHandler3()");
                        self::exceptionHandler($e);
                    }
                }
            }

            // Because Stripe doesn't provide us with the customer details
            // we don't have any other choice than looping over every single
            // paymentintent and retrieve the customer based on the 
            // 'customer' key which holds the customer ID
            // foreach($paymentIntents->data as $k => $v){
            //     if($v['customer']){
            //         $customer = \Stripe\Customer::retrieve($v['customer']);
            //         $v['customer'] = $customer;
            //     }
            // }

            if( $formatted ) {
                //echo $symbol . number_format_i18n($d['amount']/100, 2) . ' ' . $currency_code;
                foreach($paymentIntents->data as $k => $v){
                    // Format amounts
                    $currency_code = strtoupper($v['currency']);
                    $symbol = (isset(self::$currency_codes[$currency_code]) ? self::$currency_codes[$currency_code]['symbol'] : $currency_code);
                    if(!empty($paymentIntents->data[$k]->amount)){
                        $paymentIntents->data[$k]->amountFormatted = $symbol . number_format_i18n($v->amount/100, 2) . ' ' . $currency_code;
                    }
                    if(!empty($paymentIntents->data[$k]->amount_refunded)) {
                        $paymentIntents->data[$k]->amount_refundedFormatted = $symbol . number_format_i18n($v->amount_refunded/100, 2) . ' ' . $currency_code;
                    }
                    // Format the timestamp to wordpress date format
                    if(!empty($paymentIntents->data[$k]->created)) {
                        $paymentIntents->data[$k]->createdFormatted = date_i18n( 'j M Y, H:i', $paymentIntents->data[$k]->created );
                    }
                    if(isset($paymentIntents->data[$k]->metadata)){
                        $userID = 0;
                        if(isset($paymentIntents->data[$k]->metadata['frontend_post_id'])){
                            $postID = absint($paymentIntents->data[$k]->metadata['frontend_post_id']);
                            $paymentIntents->data[$k]->post_permalink = get_edit_post_link( $postID );
                        }
                        if(isset($paymentIntents->data[$k]->metadata['user_id'])){
                            $userID = absint($paymentIntents->data[$k]->metadata['user_id']);
                        }
                        if(isset($paymentIntents->data[$k]->metadata['frontend_user_id'])){
                            $userID = absint($paymentIntents->data[$k]->metadata['frontend_user_id']);
                        }
                        if($userID!==0){
                            // Lookup WP user data
                            $user_info = get_userdata($userID);
                            if($user_info){
                                $user_info->data->edit_link = get_edit_user_link($user_info->ID);
                                $paymentIntents->data[$k]->wp_user_info = $user_info->data;
                            }
                        }
                    }
                    $paymentIntents->data[$k]->raw = json_encode($paymentIntents->data[$k]->toArray(), JSON_PRETTY_PRINT);
                }
                return $paymentIntents->data;
            }else{
                return $paymentIntents->data;
            }
        }


        public static function getCustomers( $formatted=true, $limit=20, $starting_after=null, $created=null, $customer=null, $ending_before=null) {
            error_log("getCustomers()");
            if($limit==1){
                try {
                    $customers->data[] = \Stripe\Customer::retrieve(
                        $starting_after // in this case it holds the payment intent ID
                    );
                } catch( Exception $e ){
                    if ($e instanceof \Stripe\Error\Card ||
                        $e instanceof \Stripe\Exception\CardException ||
                        $e instanceof \Stripe\Exception\RateLimitException ||
                        $e instanceof \Stripe\Exception\InvalidRequestException ||
                        $e instanceof \Stripe\Exception\AuthenticationException ||
                        $e instanceof \Stripe\Exception\ApiConnectionException ||
                        $e instanceof \Stripe\Exception\ApiErrorException) {
                        // Specific Stripe exception
                        error_log("specific exceptionHandler4()");
                        self::exceptionHandler($e);
                    } else {
                        // Normal exception
                        error_log("normal exceptionHandler4()");
                        self::exceptionHandler($e);
                    }
                }
            }else{
                try {
                    $customers = \Stripe\Customer::all([
                        'limit' => $limit,
                        'starting_after' => $starting_after,
                        'created' => $created,
                        'ending_before' => $ending_before
                    ]);
                } catch( Exception $e ){
                    if ($e instanceof \Stripe\Error\Card ||
                        $e instanceof \Stripe\Exception\CardException ||
                        $e instanceof \Stripe\Exception\RateLimitException ||
                        $e instanceof \Stripe\Exception\InvalidRequestException ||
                        $e instanceof \Stripe\Exception\AuthenticationException ||
                        $e instanceof \Stripe\Exception\ApiConnectionException ||
                        $e instanceof \Stripe\Exception\ApiErrorException) {
                        // Specific Stripe exception
                        error_log("specific exceptionHandler5()");
                        self::exceptionHandler($e);
                    } else {
                        // Normal exception
                        error_log("normal exceptionHandler5()");
                        self::exceptionHandler($e);
                    }
                }
            }
            foreach($customers->data as $k => $v){
                $customers->data[$k]->createdFormatted = date_i18n( 'j M Y, H:i', $customers->data[$k]->created );
                $customers->data[$k]->raw = json_encode($customers->data[$k]->toArray(), JSON_PRETTY_PRINT);
                // Search for wordpress user that is connected to this Stripe customer based on customer ID
                $args = array(
                    'meta_query' => array(
                        array(
                            'key' => 'super_stripe_cus',
                            'value' => $customers->data[$k]->id,
                            'compare' => '='
                        )
                    )
                );
                $users = get_users($args); // Find all WP users with this meta_key == 'super_stripe_cus' AND 'meta_value' == $customers->data[$k]->id
                $userID = 0;
                if ($users) {
                    foreach ($users as $user) {
                        $userID = $user->ID;
                    }
                }
                if($userID!==0){
                    // Lookup WP user data
                    $user_info = get_userdata($userID);
                    if($user_info){
                        $user_info->data->edit_link = get_edit_user_link($user_info->ID);
                        $user_info->data->customer_id = $customers->data[$k]->id;
                        $customers->data[$k]->wp_user_info = $user_info->data;
                    }
                }
            }
            return $customers->data;
        }


        public static function getSubscriptions( $formatted=true, $limit=20, $starting_after=null, $created=null, $customer=null, $ending_before=null) {
            error_log("getSubscriptions()");
            if($limit==1){
                try {
                    $subscriptions->data[] = \Stripe\Customer::retrieve(
                        $starting_after // in this case it holds the payment intent ID
                    );
                } catch( Exception $e ){
                    if ($e instanceof \Stripe\Error\Card ||
                        $e instanceof \Stripe\Exception\CardException ||
                        $e instanceof \Stripe\Exception\RateLimitException ||
                        $e instanceof \Stripe\Exception\InvalidRequestException ||
                        $e instanceof \Stripe\Exception\AuthenticationException ||
                        $e instanceof \Stripe\Exception\ApiConnectionException ||
                        $e instanceof \Stripe\Exception\ApiErrorException) {
                        // Specific Stripe exception
                        error_log("specific exceptionHandler6()");
                        self::exceptionHandler($e);
                    } else {
                        // Normal exception
                        error_log("normal exceptionHandler6()");
                        self::exceptionHandler($e);
                    }
                }
            }else{
                try {
                    $subscriptions = \Stripe\Subscription::all([
                        'limit' => $limit,
                        'starting_after' => $starting_after,
                        'status' => 'all'
                    ]);
                } catch( Exception $e ){
                    if ($e instanceof \Stripe\Error\Card ||
                        $e instanceof \Stripe\Exception\CardException ||
                        $e instanceof \Stripe\Exception\RateLimitException ||
                        $e instanceof \Stripe\Exception\InvalidRequestException ||
                        $e instanceof \Stripe\Exception\AuthenticationException ||
                        $e instanceof \Stripe\Exception\ApiConnectionException ||
                        $e instanceof \Stripe\Exception\ApiErrorException) {
                        // Specific Stripe exception
                        error_log("specific exceptionHandler7()");
                        self::exceptionHandler($e);
                    } else {
                        // Normal exception
                        error_log("normal exceptionHandler7()");
                        self::exceptionHandler($e);
                    }
                }
            }
            foreach($subscriptions->data as $k => $v){
                // Retrieve subscription pricing (items)
                try {
                    foreach($v->items->data as $kk => $vv){
                        error_log('Product ID: ' . $vv->plan->product, 0);
                        $product = \Stripe\Product::retrieve($vv->plan->product);
                        $v->items->data[$kk]->productName = $product->name;
                    }
                } catch( Exception $e ){
                    if ($e instanceof \Stripe\Error\Card ||
                        $e instanceof \Stripe\Exception\CardException ||
                        $e instanceof \Stripe\Exception\RateLimitException ||
                        $e instanceof \Stripe\Exception\InvalidRequestException ||
                        $e instanceof \Stripe\Exception\AuthenticationException ||
                        $e instanceof \Stripe\Exception\ApiConnectionException ||
                        $e instanceof \Stripe\Exception\ApiErrorException) {
                        // Specific Stripe exception
                        error_log("specific exceptionHandler8()");
                        self::exceptionHandler($e);
                    } else {
                        // Normal exception
                        error_log("normal exceptionHandler8()");
                        self::exceptionHandler($e);
                    }
                }
                //$subscriptions->data[$k]->productName = $product->name;
                $subscriptions->data[$k]->createdFormatted = date_i18n( 'j M Y, H:i', $subscriptions->data[$k]->created );
                $subscriptions->data[$k]->raw = json_encode($subscriptions->data[$k]->toArray(), JSON_PRETTY_PRINT);
                // Search for wordpress user that is connected to this Stripe customer based on customer ID
                $args = array(
                    'meta_query' => array(
                        array(
                            'key' => 'super_stripe_cus',
                            'value' => $subscriptions->data[$k]->customer,
                            'compare' => '='
                        )
                    )
                );
                $users = get_users($args); // Find all WP users with this meta_key == 'super_stripe_cus' AND 'meta_value' == $subscriptions->data[$k]->customer
                $userID = 0;
                if ($users) {
                    foreach ($users as $user) {
                        $userID = $user->ID;
                    }
                }
                if($userID!==0){
                    // Lookup WP user data
                    $user_info = get_userdata($userID);
                    if($user_info){
                        $user_info->data->edit_link = get_edit_user_link($user_info->ID);
                        $user_info->data->customer_id = $subscriptions->data[$k]->customer;
                        $subscriptions->data[$k]->wp_user_info = $user_info->data;
                    }
                }
            }
            return $subscriptions->data;
        }

        
        // public static function getProducts($formatted=true, $limit=20, $starting_after=null, $active=null, $created=null, $ending_before=null, $ids=null, $shippable=null, $type=null, $url=null) {
        //     $products = \Stripe\Product::all([
        //         // optional
        //         // A limit on the number of objects to be returned. Limit can range between 1 and 100, and the default is 10.
        //         'limit' => $limit,
        //         // optional
        //         // A cursor for use in pagination. starting_after is an object ID that defines your place in the list. For instance, if you make a list request and receive 100 objects, ending with obj_foo, your subsequent call can include starting_after=obj_foo in order to fetch the next page of the list.
        //         'starting_after' => $starting_after,
        //         // optional
        //         // Only return products that are active or inactive (e.g., pass false to list all inactive products).
        //         'active' => $active,
        //         // optional associative array
        //         // A filter on the list based on the object created field. The value can be a string with an integer Unix timestamp, or it can be a dictionary with the following options:
        //         'created' => $created,
        //         // optional
        //         // A cursor for use in pagination. ending_before is an object ID that defines your place in the list. For instance, if you make a list request and receive 100 objects, starting with obj_bar, your subsequent call can include ending_before=obj_bar in order to fetch the previous page of the list.
        //         'ending_before' => $ending_before,
        //         // optional
        //         // Only return products with the given IDs.
        //         'ids' => $ids,
        //         // optional
        //         // Only return products that can be shipped (i.e., physical, not digital products).
        //         'shippable' => $shippable,
        //         // optional
        //         // Only return products of this type.
        //         'type' => $type,
        //         // optional
        //         // Only return products with the given url.
        //         'url' => $url
        //     ]);
        //     return $products->data;
        // }



        /**
         * Load More 
         *
         *  @since      1.0.0
         */
        public static function super_stripe_api_handler() {  
            if( !empty($_POST['data']) ) {
                $data = $_POST['data'];
                if( !empty($data['type']) ) {
                    self::setAppInfo();
                    $type = sanitize_text_field($data['type']);
                    if( $type=='searchUsers' ) {
                        // Search WordPress users
                        $value = sanitize_text_field($data['value']);
                        // We use this to give a "best matches/suggestions" user connection for this customer
                        $customer_email = sanitize_email($data['customer_email']);
                        if(empty($customer_email)){
                            $suggestions = array();
                        }else{
                            // Try to search for suggestions
                            $query = new WP_User_Query(
                                array(
                                    'fields' => array(
                                        'ID',
                                        'user_login',
                                        'display_name',
                                        'user_email'
                                    ),
                                    'number' => 1, // Acts as the limit
                                    'search' => "*{$customer_email}*",
                                    'search_columns' => array(
                                        'user_email'
                                    ),
                                )
                            );
                            $suggestions = $query->get_results();
                        }
                        $exclude = array();
                        if(isset($suggestions[0])){
                            $exclude = array($suggestions[0]->ID);
                        }
                        // search usertable
                        $query = new WP_User_Query(
                            array(
                                'fields' => array(
                                    'ID',
                                    'user_login',
                                    'display_name',
                                    'user_email'
                                ),
                                'number' => 20, // Acts as the limit
                                'exclude' => $exclude, // Exclude suggested user
                                'search' => "*{$value}*",
                                'search_columns' => array(
                                    'ID',
                                    'user_login',
                                    'display_name',
                                    'user_email',
                                    'user_nicename'
                                ),
                            )
                        );
                        $users = $query->get_results();
                        // search usermeta
                        $query = new WP_User_Query(
                            array(
                                'fields' => array(
                                    'ID',
                                    'user_login',
                                    'display_name',
                                    'user_email'
                                ),
                                'number' => 20, // Acts as the limit
                                'exclude' => $exclude, // Exclude suggested user
                                'meta_query' => array(
                                    'relation' => 'OR',
                                    array(
                                        'key' => 'first_name',
                                        'value' => $value,
                                        'compare' => 'LIKE'
                                    ),
                                    array(
                                        'key' => 'last_name',
                                        'value' => $value,
                                        'compare' => 'LIKE'
                                    )
                                )
                            )
                        );
                        $users2 = $query->get_results();
                        // Merge all users
                        $users = array_merge( $users, $users2 );
                        $payload = array(
                            'suggestions' => $suggestions,
                            'users' => array_unique($users, SORT_REGULAR)
                        );
                    }
                    if( $type=='connectUser' ) {
                        $unconnect = filter_var($data['unconnect'], FILTER_VALIDATE_BOOLEAN);
                        $customer_id = sanitize_text_field($data['customer_id']);
                        $user_id = sanitize_text_field($data['user_id']);
                        if($unconnect==true){
                            delete_user_meta( $user_id, 'super_stripe_cus');
                            $payload = array();
                        }else{
                            update_user_meta( $user_id, 'super_stripe_cus', $customer_id);
                            $edit_link = '#';
                            if($user_id!==0){
                                $user_info = get_userdata($user_id);
                                if($user_info){
                                    $user_info->data->edit_link = get_edit_user_link($user_info->ID);
                                    $user_info->data->customer_id = $customer_id;
                                    $wp_user_info = $user_info->data;
                                }
                            }
                            $payload = array(
                                'wp_user_info' => $wp_user_info
                            );
                        }
                    }
                    if( $type=='invoice.online' || 
                        $type=='invoice.pdf' || 
                        $type=='paymentIntents' || 
                        $type=='refreshPaymentIntent' ||
                        $type=='customers' ||
                        $type=='subscriptions'
                        //$type=='products' || 
                        ) {

                            $payload = array();
                            $id = '';
                            $starting_after = null;
                            if(empty($data['formatted'])) $formatted = true;
                            if(!empty($data['limit'])) $limit = absint($data['limit']);
                            if(!empty($data['id'])) $id = sanitize_text_field($data['id']);
                            if(!empty($data['starting_after'])) $starting_after = sanitize_text_field($data['starting_after']);
                            if( (!empty($id)) && (($type=='invoice.pdf') || ($type=='invoice.online')) ) {
                                $payload = self::getInvoice($id);
                            }
                            if( $type=='paymentIntents' || $type=='refreshPaymentIntent' ) {
                                if( (!empty($id)) && (($type=='refreshPaymentIntent')) ) {
                                    $starting_after = $id;
                                }
                                $payload = self::getPaymentIntents($formatted, $limit, $starting_after);
                            }
                            if( $type=='customers' ) {
                                $payload = self::getCustomers($formatted, $limit, $starting_after);
                            }
                            if( $type=='subscriptions' ) {
                                $payload = self::getSubscriptions($formatted, $limit, $starting_after);
                            }
                            // if( $type=='products' ) {
                            //     $payload = self::getProducts($formatted, $limit, $starting_after);
                            // }

                    }
                    if( $type=='refund.create' ) {
                        // ID of the PaymentIntent to refund.
                        $payment_intent = sanitize_text_field($data['payment_intent']);
                        // String indicating the reason for the refund. If set, possible values are duplicate, fraudulent, and requested_by_customer.
                        // If you believe the charge to be fraudulent, specifying fraudulent as the reason will add the associated card and email to your block lists, and will also help us improve our fraud detection algorithms.
                        $reason = sanitize_text_field($data['reason']);
                        // A positive integer in cents representing how much of this charge to refund.
                        // Can refund only up to the remaining, unrefunded amount of the charge.
                        $amount = sanitize_text_field($data['amount']);
                        $payload = self::createRefund($payment_intent, $reason, $amount);
                    }
                    $payload = json_encode($payload);
                    echo $payload;
                }
            }
            die();
        }    


        /**
         * Handles the output for the view Stripe transaction page in admin
         */
        function loop_txn_data($data, $size=1){
            if($size>7) $size = 7;
            foreach( $data as $k => $v ) {
                if( is_array($v) ) {
                    echo '<h'.$size.'>' . $k . '</h'.$size.'>';
                    $size++;
                    self::loop_txn_data($v, $size);
                }else{
                    echo '<strong>'. $k .':</strong> ' . $v . '<br />';
                }
            }
        }


        /**
         * Custom transaction columns
         *
         *  @since      1.0.0
         */
        public static function super_stripe_txn_columns($columns){
            foreach($columns as $k => $v) {
                if($k=='cb') continue; // Do not remove the checkbox
                unset($columns[$k]);
            }
            $columns['stripe_txn_id'] = esc_html__( 'Transaction', 'super-forms' );
            $columns['stripe_receipt'] = esc_html__( 'Receipt/Invoice', 'super-forms' );
            $columns['stripe_amount'] = esc_html__( 'Amount', 'super-forms' );
            $columns['stripe_status'] = esc_html__( 'Payment Status', 'super-forms' );
            $columns['stripe_description'] = esc_html__( 'Description', 'super-forms' );
            $columns['stripe_connections'] = esc_html__( 'Connections', 'super-forms' );
            $columns['date'] = esc_html__( 'Date', 'super-forms' );
            return $columns;

            // Transaction ID / Subscritpion ID
            // Payment Status
            // Total Amount
            // Payment Type
            // Payment Date

        }


        public static function super_custom_columns($column, $post_id) {
            $d = get_post_meta( $post_id, '_super_txn_data', true );
            $txn_id = self::getTransactionId($d);
            $currency_code = strtoupper($d['currency']);
            $symbol = (isset(self::$currency_codes[$currency_code]) ? self::$currency_codes[$currency_code] : $currency_code);
            switch ($column) {
                case 'stripe_txn_id':
                    echo '<a target="_blank" href="' . esc_url('https://dashboard.stripe.com/payments/' . $txn_id) . '">' . $txn_id . '</a>';
                    break;
                case 'stripe_receipt':
                    $receiptUrl = ( (!empty($d['charges']) && (!empty($d['charges']['data'])) ) ? $d['charges']['data'][0]['receipt_url'] : '');
                    if( !empty($d['receipt_url']) ) {
                        $receiptUrl = $d['receipt_url'];
                    }
                    if( !empty($receiptUrl) ) {
                        echo '<a target="_blank" href="' . esc_url($receiptUrl) . '">';
                        echo esc_html__( 'View Receipt', 'super-forms' );
                        echo '</a>';
                    }
                    break;
                case 'stripe_amount':
                    echo $symbol . number_format_i18n($d['amount']/100, 2) . ' ' . $currency_code;
                    break;
                case 'stripe_status':
                    $label = '';
                    $labelColor = '#4f566b;';
                    $title = '';
                    $class = '';
                    $pathFill = '#697386';
                    $path = 'M8 6.585l4.593-4.592a1 1 0 0 1 1.415 1.416L9.417 8l4.591 4.591a1 1 0 0 1-1.415 1.416L8 9.415l-4.592 4.592a1 1 0 0 1-1.416-1.416L6.584 8l-4.59-4.591a1 1 0 1 1 1.415-1.416z';
                    $bgColor = '#e3e8ee';

                    if( $d['status']=='warning_needs_response' ) {
                        $label = esc_html__( 'Needs response', 'super-forms' );
                        $labelColor = '#983705;';
                        $class = ' super-stripe-needs-response';
                        $path = 'M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm0-2.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM8 2a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0V3a1 1 0 0 0-1-1z';
                        $pathFill = '#bb5504';
                        $bgColor = '#f8e5b9';
                    }
                    if( $d['status']=='canceled' ) {
                        $label = esc_html__( 'Canceled', 'super-forms' );
                        $title = ' title="' . esc_attr__( 'Cancellation reason', 'super-forms' ) . ': ' . esc_attr($d['cancellation_reason']) . '"';
                        $class = ' super-stripe-canceled';
                    }
                    if( $d['status']=='failed' ) {
                        $label = esc_html__( 'Failed', 'super-forms' );
                        $class = ' super-stripe-failed';
                        if( (!empty($d['last_payment_error'])) && (isset($declineCodes[$d['last_payment_error']['decline_code']])) ) {
                            $title = ' title="' . esc_attr($declineCodes[$d['last_payment_error']['decline_code']]['desc']) . '"';
                        }else{
                            if(isset($d['outcome']['seller_message'])){
                                $title = ' title="' . esc_attr($d['outcome']['seller_message']) . '"';
                            }else{
                                if( (isset($d['charges'])) && (isset($d['charges']['data'])) ) {
                                    $title = ' title="' . esc_attr($d['charges']['data'][0]['outcome']['seller_message']) . '"';
                                }
                            }
                        }
                    }
                    if( $d['status']=='requires_payment_method' || $d['status']=='requires_capture' ) {
                        if( (!empty($d['last_payment_error'])) && (isset($declineCodes[$d['last_payment_error']['decline_code']])) ) {
                            $class = ' super-stripe-failed';
                            $label = esc_html__( 'Failed', 'super-forms' );
                            $title = ' title="' . esc_attr($declineCodes[$d['last_payment_error']['decline_code']]['desc']) . '"';
                        }else{
                            if( $d['status']=='requires_payment_method' ) {
                                $class = ' super-stripe-incomplete';
                                $label = esc_html__( 'Incomplete', 'super-forms' );
                                $title = ' title="' . esc_attr__( 'The customer has not entered their payment method.', 'super-forms' ) . '"';
                            }else{
                                $class = ' super-stripe-uncaptured';
                                $label = esc_html__( 'Uncaptured', 'super-forms' );
                                $title = ' title="' . esc_attr__( 'Payment authorized, but not yet captured.', 'super-forms' ) . '"';
                            }
                            $path = 'M8 16A8 8 0 1 1 8 0a8 8 0 0 1 0 16zm1-8.577V4a1 1 0 1 0-2 0v4a1 1 0 0 0 .517.876l2.581 1.49a1 1 0 0 0 1-1.732z';
                        }
                    }
                    if( $d['status']=='succeeded' ) {
                        if( !empty($d['refunded']) ) {
                            $label = esc_html__( 'Refunded', 'super-forms' );
                            $title = '';
                            $class = ' super-stripe-refunded';
                            $pathFill = '#697386';
                            $path = 'M10.5 5a5 5 0 0 1 0 10 1 1 0 0 1 0-2 3 3 0 0 0 0-6l-6.586-.007L6.45 9.528a1 1 0 0 1-1.414 1.414L.793 6.7a.997.997 0 0 1 0-1.414l4.243-4.243A1 1 0 0 1 6.45 2.457L3.914 4.993z';
                        }else{
                            if( !empty($d['amount_refunded']) ) {
                                $label = esc_html__( 'Partial refund', 'super-forms' );
                                $labelColor = '#3d4eac;';
                                $title = ' title="' . ($symbol . number_format_i18n($d['amount_refunded']/100, 2) . ' ' . $currency_code) . ' ' . esc_attr__('was refunded', 'super-forms') . '"';
                                $class = ' super-stripe-partial-refund';
                                $pathFill = '#5469d4';
                                $path = 'M9 8a1 1 0 0 0-1-1H5.5a1 1 0 1 0 0 2H7v4a1 1 0 0 0 2 0zM4 0h8a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H4a4 4 0 0 1-4-4V4a4 4 0 0 1 4-4zm4 5.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z';
                            }else{
                                $label = esc_html__( 'Succeeded', 'super-forms' );
                                $labelColor = '#3d4eac;';
                                $title = '';
                                $class = ' super-stripe-succeeded';
                                $pathFill = '#5469d4';
                                $bgColor = '#d6ecff';
                                $path = 'M5.297 13.213L.293 8.255c-.39-.394-.39-1.033 0-1.426s1.024-.394 1.414 0l4.294 4.224 8.288-8.258c.39-.393 1.024-.393 1.414 0s.39 1.033 0 1.426L6.7 13.208a.994.994 0 0 1-1.402.005z';
                            }
                        }
                    }
                    echo '<span' . $title . ' class="super-stripe-status' . $class . '" style="color:' . $labelColor . ';font-size:12px;padding:2px 8px 2px 8px;background-color:'.$bgColor.';border-radius:20px;font-weight:500;">';
                        echo $label;
                        echo '<svg height="16" viewBox="0 0 16 16" width="16" xmlns="http://www.w3.org/2000/svg" style="height:12px;width:12px;padding-left:3px;margin-bottom:-1px;">';
                            echo '<path style="fill:' . $pathFill . ';" d="' . $path . '" fill-rule="evenodd"></path>';
                        echo '</svg>';
                    echo '</span>';
                    break;
                case 'stripe_description':
                    echo (isset($d['description']) ? esc_html($d['description']) : '');
                    break;
                case 'stripe_connections':
                    $metadata = ( isset($d['metadata']) ? $d['metadata'] : '' );
                    $metadata = json_decode($metadata, true);
                    if( isset($metadata['user_id']) ) {
                        $user_id = absint($metadata['user_id']);
                        $user = get_user_by( 'ID', $user_id );
                        $name = '';
                        if( !empty($user->first_name) ) {
                            $name = $user->first_name;
                        }
                        if( !empty($user->last_name) ) {
                            if( empty($name) ) {
                                $name = $user->last_name;
                            }else{
                                $name .= ' ' . $user->last_name;
                            }
                        }
                        if( !empty($name) ) {
                            $name = ' (' . $name . ')';
                        }
                        echo esc_html__( 'User:', 'super-forms' ) . ' <a target="_blank" href="' . esc_url(get_edit_user_link( $user_id )) . '">' . $user->user_login . $name . '</a>';
                        echo '<br />';
                    }
                    if( isset($dmetadata['_super_contact_entry_id']) ) {
                        $entry_id = absint($dmetadata['_super_contact_entry_id']);
                        echo esc_html__( 'Contact Entry:', 'super-forms' ) . ' <a target="_blank" href="' . esc_url(get_edit_post_link( $entry_id )) . '">' . get_the_title($entry_id) . '</a>';
                        echo '<br />';
                    }
                    $form_id = wp_get_post_parent_id($post_id);
                    if ($form_id != 0) {
                        echo esc_html__( 'Form:', 'super-forms' ) . ' <a target="_blank" href="' . esc_url('admin.php?page=super_create_form&id=' . $form_id) . '">' . get_the_title($form_id) . '</a>';
                        echo '<br />';
                    }


                    // $contact_entry_id = (isset($metadata['contact_entry_id']) ? absint($metadata['contact_entry_id']) : 0 );
                    // $frontend_post_id = (isset($metadata['frontend_post_id']) ? absint($metadata['frontend_post_id']) : 0 );
                    // $frontend_user_id = (isset($metadata['frontend_user_id']) ? absint($metadata['frontend_user_id']) : 0 );

                    break;
            }
        }


        // no longer used...? /**
        // no longer used...?  * Redirect to Stripe Checkout page
        // no longer used...?  *
        // no longer used...?  * @since       1.0.0
        // no longer used...?  */
        // no longer used...? public function stripe_redirect($redirect, $atts) {
        // no longer used...?     $settings = $atts['settings'];
        // no longer used...?     $data = $atts['data'];

        // no longer used...?     // A set of key-value pairs that you can attach to a source object. 
        // no longer used...?     // It can be useful for storing additional information about the source in a structured format.
        // no longer used...?     $metadata = array();
        // no longer used...?     $metadata['form_id'] = absint($data['hidden_form_id']['value']);
        // no longer used...?     $metadata['user_id'] = get_current_user_id();

        // no longer used...?     // Get Post ID and save it in custom parameter for stripe so we can update the post status after successfull payment complete
        // no longer used...?     $post_id = SUPER_Common::getClientData( '_super_stripe_frontend_post_id' );
        // no longer used...?     if( !empty($post_id) ) {
        // no longer used...?         $metadata['frontend_post_id'] = absint($post_id);
        // no longer used...?     }
        // no longer used...?     // Get User ID and save it in custom parameter for stripe so we can update the user status after successfull payment complete
        // no longer used...?     $user_id = SUPER_Common::getClientData( 'stripe_frontend_user_id' );
        // no longer used...?     if( !empty($user_id) ) {
        // no longer used...?         $metadata['frontend_user_id'] = absint($user_id);
        // no longer used...?     }
        // no longer used...?     // Get Contact Entry ID and save it so we can update the entry status after successfull payment
        // no longer used...?     if(!empty($settings['save_contact_entry']) && $settings['save_contact_entry']=='yes'){
        // no longer used...?         $metadata['contact_entry_id'] = absint($data['contact_entry_id']['value']);
        // no longer used...?     }

        // no longer used...?     // Allow devs to filter metadata if needed
        // no longer used...?     $metadata = apply_filters( 'super_stripe_source_metadata', $metadata, array('settings'=>$settings, 'data'=>$data ) );

        // no longer used...?     // Check if Stripe checkout is enabled
        // no longer used...?     if($settings['stripe_checkout']=='true'){
        // no longer used...?         exit;
        // no longer used...?         // If subscription checkout
        // no longer used...?         if($settings['stripe_method']=='subscription'){

        // no longer used...?         }

        // no longer used...?         // If single payment checkout
        // no longer used...?         if($settings['stripe_method']=='single'){
        // no longer used...?             // If enabled determine what checkout method was choosen by the end user
        // no longer used...?             if( (!empty($data['stripe_ideal'])) && (!empty($data['stripe_ideal']['value'])) ) {
        // no longer used...?                 $bank = sanitize_text_field($data['stripe_ideal']['value']);
        // no longer used...?                 $amount = SUPER_Common::email_tags( $settings['stripe_amount'], $data, $settings );
        // no longer used...?                 $stripe_statement_descriptor = sanitize_text_field(SUPER_Common::email_tags( $settings['stripe_statement_descriptor'], $data, $settings ));
        // no longer used...?                 if(empty($stripe_statement_descriptor)) $stripe_statement_descriptor = null;
        // no longer used...?                 $stripe_email = SUPER_Common::email_tags( $settings['stripe_email'], $data, $settings );
        // no longer used...?                 $stripe_name = SUPER_Common::email_tags( $settings['stripe_name'], $data, $settings );
        // no longer used...?                 $stripe_phone = SUPER_Common::email_tags( $settings['stripe_phone'], $data, $settings );
        // no longer used...?                 $stripe_city = SUPER_Common::email_tags( $settings['stripe_city'], $data, $settings );
        // no longer used...?                 $stripe_country = SUPER_Common::email_tags( $settings['stripe_country'], $data, $settings );
        // no longer used...?                 $stripe_line1 = SUPER_Common::email_tags( $settings['stripe_line1'], $data, $settings );
        // no longer used...?                 $stripe_line2 = SUPER_Common::email_tags( $settings['stripe_line2'], $data, $settings );
        // no longer used...?                 $stripe_postal_code = SUPER_Common::email_tags( $settings['stripe_postal_code'], $data, $settings );
        // no longer used...?                 $stripe_state = SUPER_Common::email_tags( $settings['stripe_state'], $data, $settings );

        // no longer used...?                 // The URL the customer should be redirected to after the authorization process.
        // no longer used...?                 if(empty($settings['stripe_return_url'])) $settings['stripe_return_url'] = get_home_url(); // default to home page
        // no longer used...?                 $stripe_return_url = esc_url(SUPER_Common::email_tags( $settings['stripe_return_url'], $data, $settings ));

        // no longer used...?                 // Create Source for iDeal payment
        // no longer used...?                 $url = 'https://api.stripe.com/v1/sources';
        // no longer used...?                 $response = wp_remote_post( 
        // no longer used...?                     $url, 
        // no longer used...?                     array(
        // no longer used...?                         'timeout' => 45,
        // no longer used...?                         'headers'=>array(
        // no longer used...?                             'Authorization' => 'Bearer sk_test_CczNHRNSYyr4TenhiCp7Oz05'
        // no longer used...?                         ),                      
        // no longer used...?                         'body' => array(
        // no longer used...?                             // The type of the source. The type is a payment method, one of ach_credit_transfer, ach_debit, alipay, bancontact, card, card_present, eps, giropay, ideal, multibanco, klarna, p24, sepa_debit, sofort, three_d_secure, or wechat. An additional hash is included on the source with a name matching this value. It contains additional information specific to the payment method used.
        // no longer used...?                             'type' => 'ideal', 
        // no longer used...?                             'currency' => 'eur', // iDeal only supports EUR currency
        // no longer used...?                             'amount' => SUPER_Common::tofloat($amount)*100, // The amount to charge times hundred (because amount is in cents)
        // no longer used...?                             // iDEAL requires a statement descriptor before the customer is redirected to authenticate the payment. By default, your Stripe account’s statement descriptor is used (you can review this in the Dashboard). 
        // no longer used...?                             'statement_descriptor' => $stripe_statement_descriptor,
        // no longer used...?                             'ideal' => array(
        // no longer used...?                                 'bank' => $bank, // abn_amro, asn_bank, bunq, handelsbanken, ing, knab, moneyou, rabobank, regiobank, sns_bank, triodos_bank, van_lanschot
        // no longer used...?                                 'statement_descriptor' => $stripe_statement_descriptor // NOT USED? Unclear from Stripe API documentation
        // no longer used...?                             ),
        // no longer used...?                             // Information about the owner of the payment instrument that may be used or required by particular source types.
        // no longer used...?                             // (optional)
        // no longer used...?                             'owner' => array(
        // no longer used...?                                 'email' => $stripe_email,
        // no longer used...?                                 'name' => $stripe_name,
        // no longer used...?                                 'phone' => $stripe_phone,
        // no longer used...?                                 // address
        // no longer used...?                                 'address' => array(
        // no longer used...?                                     'city' => $stripe_city,
        // no longer used...?                                     'country' => $stripe_country,
        // no longer used...?                                     'line1' => $stripe_line1,
        // no longer used...?                                     'line2' => $stripe_line2,
        // no longer used...?                                     'postal_code' => $stripe_postal_code,
        // no longer used...?                                     'state' => $stripe_state
        // no longer used...?                                 )
        // no longer used...?                             ),
        // no longer used...?                             'redirect' => array(
        // no longer used...?                                 'return_url' => $stripe_return_url // Required for iDeal Source
        // no longer used...?                             ),
        // no longer used...?                             'metadata' => $metadata
        // no longer used...?                         )
        // no longer used...?                     )
        // no longer used...?                 );
        // no longer used...?                 if ( is_wp_error( $response ) ) {
        // no longer used...?                     $error_message = $response->get_error_message();
        // no longer used...?                     SUPER_Common::output_message( array(
        // no longer used...?                         'msg' => $error_message,
        // no longer used...?                         'form_id' => @TODO
        // no longer used...?                     ));
        // no longer used...?                 } else {
        // no longer used...?                     $obj = json_decode($response['body']);
        // no longer used...?                     if( !empty($obj->error) ) {
        // no longer used...?                         SUPER_Common::output_message( array(
        // no longer used...?                             'msg' => $obj->error->message,
        // no longer used...?                             'form_id' => @TODO
        // no longer used...?                         ));
        // no longer used...?                     }
        // no longer used...?                     return $obj->redirect->url;
        // no longer used...?                 }
        // no longer used...?             }else{
        // no longer used...?                 // Check if the API key is correctly configured
        // no longer used...?                 $global_settings = SUPER_Common::get_global_settings();
        // no longer used...?                 if( (!empty($global_settings['stripe_mode'])) && (empty($global_settings['stripe_sandbox_key'])) ) {
        // no longer used...?                     SUPER_Common::output_message( array(
        // no longer used...?                         'error' => true,
        // no longer used...?                         'msg' => sprintf( esc_html__( 'Stripe Sandbox API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>' )
        // no longer used...?                     ));
        // no longer used...?                 }
        // no longer used...?                 if( (empty($global_settings['stripe_mode'])) && (empty($global_settings['stripe_live_key'])) ) {
        // no longer used...?                     SUPER_Common::output_message( array(
        // no longer used...?                         'error' => true,
        // no longer used...?                         'msg' => sprintf( esc_html__( 'Stripe Live API key not configured, please enter your API key under %sSuper Forms > Settings > Stripe Checkout%s', 'super-forms' ), '<a target="_blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#stripe-checkout') . '">', '</a>' )
        // no longer used...?                     ));
        // no longer used...?                 }
        // no longer used...?                 // Check if iDeal element exists
        // no longer used...?                 if( !isset($data['stripe_ideal']) ) {
        // no longer used...?                     SUPER_Common::output_message( array(
        // no longer used...?                         'error' => true,
        // no longer used...?                         'msg' => sprintf( esc_html__( 'No element found named %sstripe_ideal%s. Please make sure you added the Stripe iDeal element and named it %sstripe_ideal%s.', 'super-forms' ), '<strong>', '</strong>', '<strong>', '</strong>' )
        // no longer used...?                     ));
        // no longer used...?                 }else{
        // no longer used...?                     SUPER_Common::output_message( array(
        // no longer used...?                         'error' => true,
        // no longer used...?                         'msg' => esc_html__( 'Please choose a bank.', 'super-forms' )
        // no longer used...?                     ));
        // no longer used...?                 }
        // no longer used...?             }
        // no longer used...?         }
        // no longer used...?     }
        // no longer used...?     return $redirect;
        // no longer used...? }


        /**
         * Stripe IPN (better know as WebHooks handler)
         *
         * @since       1.0.0
         */
        public function stripe_ipn() {

            // payment_intent=pi_1FfCwmFKn7uROhgCVZENWCcG
            // payment_intent_client_secret=pi_1FfCwmFKn7uROhgCVZENWCcG_secret_U2Idi8YnxtUBPyPCYjs2wUeTO
            // source_type=ideal

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

            if ((isset($_GET['ipn'])) && ($_GET['ipn'] == 'super_stripe')) {
                self::setAppInfo();

                $payload = file_get_contents('php://input');
                $event = null;
                try {
                    $event = json_decode($payload, true);
                } catch(\UnexpectedValueException $e) {
                    // Invalid payload
                    http_response_code(400);
                    exit();
                }

                // WebHook responses:

                // charge.pending - The Charge is pending (asynchronous payments only). 
                // @Todo: Nothing to do.

                // charge.succeeded - The Charge succeeded and the payment is complete.
                // @Todo: Finalize the order and send a confirmation to the customer over email.
                
                // charge.failed - The Charge has failed and the payment could not be completed.
                // @Todo: Cancel the order and optionally re-engage the customer in your payment flow.

                // $payload = json_decode($payload, true);
                // $obj = $payload['data']['object'];
                // do_action( 'super_stripe_webhook_' . str_replace('.', '_', $event->type), $obj );

                // Handle the event
                $paymentIntent = $event['data']['object'];

                // $handleWebhook = array(
                //     'charge.failed',            // Occurs whenever a failed charge attempt occurs.
                //                                 // The Charge has failed and the payment could not be completed.
                //                                 // Cancel the order and optionally re-engage the customer in your payment flow.
                //     'charge.refunded',          // Occurs whenever a charge is refunded, including partial refunds.
                //     'charge.updated',           // Occurs whenever a charge description or metadata is updated.
                //     'charge.refund.updated',    // Occurs whenever a refund is updated, on selected payment methods.
                //     'charge.dispute.created',   // Occurs whenever a customer disputes a charge with their bank.

                //     'payment_intent.canceled',                  // Occurs when a PaymentIntent is canceled.
                //     'payment_intent.payment_failed',            // Occurs when a PaymentIntent has failed the attempt to create a source or a payment.
                //                                                 // When payment is unsuccessful, you can find more details by inspecting the 
                //                                                 // PaymentIntent’s `last_payment_error` property. You can notify the customer 
                //                                                 // that their payment didn’t complete and encourage them to try again with a 
                //                                                 // different payment method. Reuse the same PaymentIntent to continue tracking 
                //                                                 // the customer’s purchase.

                    
                //     'payment_intent.succeeded',                 // Occurs when a PaymentIntent has been successfully fulfilled.
                //     'payment_intent.amount_capturable_updated'  // Occurs when a PaymentIntent has funds to be captured
                // );
                // if( in_array($event['type'], $handleWebhook) ) {
                //     self::handleWebhook($paymentIntent);
                //     http_response_code(200);
                //     die();
                // }

                // Action hook to do specific things based on a given event
                do_action( 'super_stripe_webhook_' . str_replace('.', '_', $event['type']), $paymentIntent );
                
                // Return 200 code
                http_response_code(200);
                die();

                // ... handle other event types
                // Othe Events:
                // balance.available
                // charge.captured
                // charge.dispute.created
                // charge.failed
                // charge.refunded
                // charge.succeeded
                // checkout.session.completed
                // customer.created
                // customer.deleted
                // customer.source.created
                // customer.source.updated
                // customer.subscription.created
                // customer.subscription.deleted
                // customer.subscription.updated
                // customer.updated
                // invoice.created
                // invoice.finalized
                // invoice.payment_failed
                // invoice.payment_succeeded
                // invoice.updated
                // payment_intent.amount_capturable_updated
                // payment_intent.canceled
                // payment_intent.created
                // payment_intent.payment_failed
                // payment_intent.succeeded
                // payment_method.attached
                // setup_intent.canceled
                // setup_intent.created
                // setup_intent.setup_failed
                // setup_intent.succeeded

                // switch ($event['type']) {

                //     // ... handle other event types
                //     // Othe Events:
                //     // balance.available
                //     // charge.captured
                //     // charge.dispute.created
                //     // charge.failed
                //     // charge.refunded
                //     // charge.succeeded
                //     // checkout.session.completed
                //     // customer.created
                //     // customer.deleted
                //     // customer.source.created
                //     // customer.source.updated
                //     // customer.subscription.created
                //     // customer.subscription.deleted
                //     // customer.subscription.updated
                //     // customer.updated
                //     // invoice.created
                //     // invoice.finalized
                //     // invoice.payment_failed
                //     // invoice.payment_succeeded
                //     // invoice.updated
                //     // payment_intent.amount_capturable_updated
                //     // payment_intent.canceled
                //     // payment_intent.created
                //     // payment_intent.payment_failed
                //     // payment_intent.succeeded
                //     // payment_method.attached
                //     // setup_intent.canceled
                //     // setup_intent.created
                //     // setup_intent.setup_failed
                //     // setup_intent.succeeded

                //     default:
                //         // Unexpected event type
                //         http_response_code(400);
                //         exit();
                // }

                http_response_code(200);
                die();
            }
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
                'callback' => 'SUPER_Stripe::stripe_element',
                'name' => 'Stripe',
                'icon' => 'stripe;fab',
                'atts' => array(
                    'general' => array(
                        'name' => esc_html__( 'General', 'super-forms' ),
                        'fields' => array(
                            'payment_method' => array(
                                'name' => esc_html__( 'Choose payment gateway', 'super-forms' ), 
                                'label' => esc_html__( 'Please note that the iDeal gateway can not be used in combination with subscriptions!', 'super-forms' ),
                                'type' => 'select',
                                'values' => array(
                                    'card' => esc_html__( 'Credit Card', 'super-forms' ),
                                    'sepa_debit' => esc_html__( 'SEPA Direct Debit', 'super-forms' ),
                                    'ideal' => esc_html__( 'iDeal', 'super-forms' ) 
                                ),
                                'default' => ( !isset( $attributes['payment_method'] ) ? '' : $attributes['payment_method'] )
                            ),
                            'label' => $label,
                            'description'=>$description,
                            'tooltip' => $tooltip
                        ),
                    ),
                    'icon' => array(
                        'name' => esc_html__( 'Icon', 'super-forms' ),
                        'fields' => array(
                            'icon_position' => $icon_position,
                            'icon_align' => $icon_align,
                            'icon' => SUPER_Shortcodes::icon($attributes,''),
                        ),
                    ),
                    'conditional_logic' => $conditional_logic_array
                )
            );
            return $array;
        }


        /**
         * Handle the Stripe iDeal element output
         *
         *  @since      1.0.0
        */
        public static function stripe_ideal_v1( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {
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
            // Enqueu required scripts
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_VERSION, false );  
            $handle = 'super-stripe-ideal';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'scripts-ideal.js', array( 'jquery', 'super-common' ), SUPER_VERSION, false );  
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
            $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag );
            $result .= '</div>';
            return $result;        
        }


        /**
         * Handle the Stripe element output
         * Depending on the users choice, this can print a Card, iDeal or IBAN (for SEPA payments) element
         *
         *  @since      1.0.0
        */
        public static function stripe_element($x) {
            extract($x); // $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null
            self::stripe_element_scripts();
            $result = SUPER_Shortcodes::opening_tag( 'text', $atts );
            $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
            if( empty($atts['payment_method']) ) {
                $result .= esc_html__( 'Please edit this Stripe element and choose a payment gateway!', 'super-forms' );
            }else{
                if( $atts['payment_method']=='card' ) {
                    $result .= '<div class="super-stripe-cc-element"></div>';
                }
                if( $atts['payment_method']=='sepa_debit' ) {
                    $result .= '<div class="super-stripe-iban-element"></div>';
                }
                if( $atts['payment_method']=='ideal' ) {
                    $result .= '<div class="super-stripe-ideal-element"></div>';
                }
                $result .= '<div class="super-stripe-errors" role="alert"></div>';
            }
            $result .= SUPER_Shortcodes::common_attributes( $atts, 'text' );
            $result .= '</div>';
            $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag );
            $result .= '</div>';
            return $result;
        }

        
        /**
         * Handle the Stripe iDeal element output
         *
         *  @since      1.0.0
        */
        public static function stripe_ideal( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {

            // Enqueu required scripts
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_VERSION, false ); 
            $handle = 'super-stripe';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'stripe-elements.js', array( 'stripe-v3', 'jquery', 'super-common' ), SUPER_VERSION, false );  
            $global_settings = SUPER_Common::get_global_settings();
            if(empty($global_settings['stripe_pk'])){
                $global_settings['stripe_pk'] = 'pk_test_1i3UyFAuxbe3Po62oX1FV47U';
            }

            $idealPadding = '9px 15px 9px 15px';
            if( (isset($settings['theme_field_size'])) && ($settings['theme_field_size']=='large') ) {
                $idealPadding = '13px 15px 13px 15px';
            }
            if( (isset($settings['theme_field_size'])) && ($settings['theme_field_size']=='huge') ) {
                $idealPadding = '18px 15px 18px 15px';
            }

            wp_localize_script(
                $handle,
                $name,
                array( 
                    'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
                    'stripe_pk' => $global_settings['stripe_pk'],
                    'styles' => array(
                        'fontFamily' => ( isset( $settings['font_global_family'] ) ? stripslashes($settings['font_global_family']) : '"Helvetica", "Arial", sans-serif' ),
                        'fontSize' => ( isset( $settings['font_global_size'] ) ? $settings['font_global_size'] : 12 ),
                        'color' => ( isset( $settings['theme_field_colors_font'] ) ? $settings['theme_field_colors_font'] : '#444444' ),
                        'colorFocus' => ( isset( $settings['theme_field_colors_font_focus'] ) ? $settings['theme_field_colors_font_focus'] : '#444444' ),
                        'placeholder' => ( isset( $settings['theme_field_colors_placeholder'] ) ? $settings['theme_field_colors_placeholder'] : '#444444' ),
                        'placeholderFocus' => ( isset( $settings['theme_field_colors_placeholder_focus'] ) ? $settings['theme_field_colors_placeholder_focus'] : '#444444' ),
                        'iconColor' => ( isset( $settings['theme_icon_color'] ) ? $settings['theme_icon_color'] : '#B3DBDD' ),
                        'iconColorFocus' => ( isset( $settings['theme_icon_color_focus'] ) ? $settings['theme_icon_color_focus'] : '#4EB1B6' ),
                        'idealPadding' => $idealPadding
                    )
                )
            );
            wp_enqueue_script( $handle );

            $result = SUPER_Shortcodes::opening_tag( 'text', $atts );
            $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
            $result .= '<div class="super-stripe-ideal-element">';
            $result .= '</div>';
            $result .= '<!-- Used to display form errors. -->';
            $result .= '<div class="super-stripe-errors" role="alert"></div>';
            $result .= SUPER_Shortcodes::common_attributes( $atts, 'text' );
            $result .= '</div>';
            $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag );
            $result .= '</div>';
            return $result;
        }


        /**
         * Handle the Stripe Credit Card element output
         *
         *  @since      1.0.0
        */
        public static function stripe_cc( $tag, $atts, $inner, $shortcodes=null, $settings=null, $i18n=null ) {
            // Enqueu required scripts
            wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_VERSION, false ); 
            $handle = 'super-stripe';
            $name = str_replace( '-', '_', $handle ) . '_i18n';
            wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'stripe-elements.js', array( 'stripe-v3', 'jquery', 'super-common' ), SUPER_VERSION, false );  
            $global_settings = SUPER_Common::get_global_settings();
            if(empty($global_settings['stripe_pk'])){
                $global_settings['stripe_pk'] = 'pk_test_1i3UyFAuxbe3Po62oX1FV47U';
            }

            $idealPadding = '9px 15px 9px 15px';
            if( (isset($settings['theme_field_size'])) && ($settings['theme_field_size']=='large') ) {
                $idealPadding = '13px 15px 13px 15px';
            }
            if( (isset($settings['theme_field_size'])) && ($settings['theme_field_size']=='huge') ) {
                $idealPadding = '18px 15px 18px 15px';
            }

            wp_localize_script(
                $handle,
                $name,
                array( 
                    'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
                    'stripe_pk' => $global_settings['stripe_pk'],
                    'styles' => array(
                        'fontFamily' => ( isset( $settings['font_global_family'] ) ? stripslashes($settings['font_global_family']) : '"Helvetica", "Arial", sans-serif' ),
                        'fontSize' => ( isset( $settings['font_global_size'] ) ? $settings['font_global_size'] : 12 ),
                        'color' => ( isset( $settings['theme_field_colors_font'] ) ? $settings['theme_field_colors_font'] : '#444444' ),
                        'colorFocus' => ( isset( $settings['theme_field_colors_font_focus'] ) ? $settings['theme_field_colors_font_focus'] : '#444444' ),
                        'placeholder' => ( isset( $settings['theme_field_colors_placeholder'] ) ? $settings['theme_field_colors_placeholder'] : '#444444' ),
                        'placeholderFocus' => ( isset( $settings['theme_field_colors_placeholder_focus'] ) ? $settings['theme_field_colors_placeholder_focus'] : '#444444' ),
                        'iconColor' => ( isset( $settings['theme_icon_color'] ) ? $settings['theme_icon_color'] : '#B3DBDD' ),
                        'iconColorFocus' => ( isset( $settings['theme_icon_color_focus'] ) ? $settings['theme_icon_color_focus'] : '#4EB1B6' ),
                        'idealPadding' => $idealPadding
                    )
                )
            );
            wp_enqueue_script( $handle );

            $result = SUPER_Shortcodes::opening_tag( 'text', $atts );
            $result .= SUPER_Shortcodes::opening_wrapper( $atts, $inner, $shortcodes, $settings );
            $result .= '<div class="super-stripe-cc-element">';
            $result .= '</div>';
            $result .= '<!-- Used to display form errors. -->';
            $result .= '<div class="super-stripe-errors" role="alert"></div>';
            $result .= SUPER_Shortcodes::common_attributes( $atts, 'text' );
            $result .= '</div>';
            $result .= SUPER_Shortcodes::loop_conditions( $atts, $tag );
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
                wp_enqueue_style( 'super-stripe-confirmation', plugin_dir_url( __FILE__ ) . 'stripe-confirmation.css', array(), SUPER_VERSION );
                // Enqueue scripts
                wp_enqueue_script( 'stripe-v3', '//js.stripe.com/v3/', array(), SUPER_VERSION, false ); 
                $handle = 'super-stripe-confirmation';
                $name = str_replace( '-', '_', $handle ) . '_i18n';
                wp_register_script( $handle, plugin_dir_url( __FILE__ ) . 'confirmation.js', array(), SUPER_VERSION, false ); 
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
                            esc_html__( '%sThank you for your order!%s%sWe’ll send your receipt as soon as your payment is confirmed.%s', 'super_forms' ), 
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
                            esc_html__( '%sPayment failed!%s%sWe couldn’t process your order.%s', 'super_forms' ), 
                            '<div class="title">', 
                            '</div>', 
                            '<div class="description">', 
                            '</div>' 
                        )


                    )
                );
                wp_enqueue_script( $handle );
            }
        }


        /**
         * Hook into JS filter and add the Stripe Token
         *
         *  @since      1.0.0
        */
        public static function add_dynamic_function( $functions ) {
            $functions['before_submit_hook'][] = array(
                'name' => 'stripe_validate'
            );
            $functions['after_email_send_hook'][] = array(
                'name' => 'stripe_cc_create_payment_method'
            );
            $functions['after_email_send_hook'][] = array(
                'name' => 'stripe_iban_create_payment_method'
            );
            $functions['after_email_send_hook'][] = array(
                'name' => 'stripe_ideal_create_payment_method'
            );

            $functions['after_init_common_fields'][] = array(
                'name' => 'init_stripe_elements'
            );
            return $functions;
        }


        /**
         * Hook into settings and add Stripe settings
         *
         *  @since      1.0.0
        */
        public static function add_settings( $array, $x ) {
           
            $statuses = SUPER_Settings::get_entry_statuses();
            $new_statuses = array();
            foreach($statuses as $k => $v) {
                $new_statuses[$k] = $v['name'];
            }
            $statuses = $new_statuses;
            unset($new_statuses);

            $domain = home_url(); // e.g: 'http://domain.com';
            $home_url = trailingslashit($domain);
            $webhookUrl = $home_url . 'sfstripe/webhook'; // super forms stripe webhook e.g: https://domain.com/sfstripe/webhook will be converted into https://domain.com/index.php?sfstripewebhook=true 

            // Stripe Settings
            $array['stripe_checkout'] = array(        
                'name' => esc_html__( 'Stripe Checkout', 'super-forms' ),
                'label' => esc_html__( 'Stripe Checkout', 'super-forms' ),
                'html' => array( '<style>.super-settings .stripe-settings-html-notice {display:none;}</style>', '<p class="stripe-settings-html-notice">' . sprintf( esc_html__( 'Before filling out these settings we %shighly recommend%s you to read the %sdocumentation%s.', 'super-forms' ), '<strong>', '</strong>', '<a target="_blank" href="https://renstillmann.github.io/super-forms/#/stripe-add-on">', '</a>' ) . '</p>' ),
                'fields' => array(
                    // Sandbox keys
                    'stripe_mode' => array(
                        'hidden' => true,
                        'default' =>  'sandbox',
                        'type' => 'checkbox',
                        'values' => array(
                            'sandbox' => esc_html__( 'Enable Stripe Sandbox/Test mode (for testing purposes only)', 'super-forms' ),
                        ),
                        'filter' => true
                    ),
                    'stripe_sandbox_public_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox publishable key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'placeholder' => 'pk_test_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => 'sandbox'
                    ),
                    'stripe_sandbox_secret_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox secret key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'placeholder' => 'sk_test_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => 'sandbox'
                    ),
                    'stripe_sandbox_webhook_id' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox webhook ID', 'super-forms' ),
                        'label' => sprintf( esc_html__( '%sCreate a webhook ID%s%sEndpoint URL must be: %s', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/webhooks">', '</a>', '<br /><br />', '<code>' . $webhookUrl . '</code>' ),
                        'desc' => sprintf( 
                            esc_html__( 'Make sure the following events are enabled for this webhook:%s%s%s%s', 'super-forms' ), 
                            '<br />',
                            '<code>checkout.session.async_payment_failed</code><br />',
                            '<code>checkout.session.async_payment_succeeded</code><br />',
                            '<code>checkout.session.completed</code><br />'
                        ),
                        'placeholder' => 'we_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => 'sandbox'
                    ),
                    'stripe_sandbox_webhook_secret' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Sandbox webhook signing secret key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/webhooks">' . esc_html__( 'Get your webhook signing secret key', 'super-forms' ) . '</a>',
                        'placeholder' => 'whsec_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_mode',
                        'filter_value' => 'sandbox'
                    ),

                    // Live keys
                    'stripe_live_public_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live publishable key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'placeholder' => 'pk_live_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                    ),
                    'stripe_live_secret_key' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live secret key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/apikeys">' . esc_html__( 'Get your API key', 'super-forms' ) . '</a>',
                        'placeholder' => 'sk_live_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                    ),
                    'stripe_live_webhook_id' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live webhook ID', 'super-forms' ),
                        'label' => sprintf( esc_html__( '%sCreate a webhook ID%s%sEndpoint URL must be: %s', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/webhooks">', '</a>', '<br /><br />', '<code>' . $webhookUrl . '</code>' ),
                        'desc' => sprintf( 
                            esc_html__( 'Make sure the following events are enabled for this webhook:%s%s%s%s', 'super-forms' ), 
                            '<br />',
                            '<code>checkout.session.async_payment_failed</code><br />',
                            '<code>checkout.session.async_payment_succeeded</code><br />',
                            '<code>checkout.session.completed</code><br />'
                        ),
                        'placeholder' => 'we_XXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                    ),
                    'stripe_live_webhook_secret' => array(
                        'hidden' => true,
                        'name' => esc_html__( 'Live webhook signing secret key', 'super-forms' ),
                        'desc' => '<a target="_blank" href="https://dashboard.stripe.com/webhooks">' . esc_html__( 'Get your webhook signing secret key', 'super-forms' ) . '</a>',
                        'placeholder' => 'whsec_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
                        'default' =>  '',
                    ),

                    
                    'stripe_checkout' => array(
                        'hidden_setting' => true,
                        'default' =>  '',
                        'type' => 'checkbox',
                        'filter' => true,
                        'values' => array(
                            'true' => esc_html__( 'Enable Stripe Checkout', 'super-forms' ),
                        ),
                    ),
                    'stripe_receipt_email' => array(
                        'name' => esc_html__( 'Owner’s email address', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),

					// @since 1.3.0 - Conditionally Stripe Checkout
					'conditionally_stripe_checkout' => array(
						'hidden_setting' => true,
						'default' =>  '',
						'type' => 'checkbox',
						'filter'=>true,
						'values' => array(
							'true' => esc_html__( 'Conditionally checkout to Stripe', 'super-forms' ),
						),
						'parent' => 'stripe_checkout',
						'filter_value' => 'true'
					),
					'conditionally_stripe_checkout_check' => array(
						'hidden_setting' => true,
						'type' => 'conditional_check',
						'name' => esc_html__( 'Only checkout to Stripe when following condition is met', 'super-forms' ),
						'label' => esc_html__( 'Your are allowed to enter field {tags} to do the check', 'super-forms' ),
						'default' =>  '',
						'placeholder' => "{fieldname},value",
						'filter'=>true,
						'parent' => 'conditionally_stripe_checkout',
						'filter_value' => 'true'
					),
                    'stripe_method' => array(
                        'name' => esc_html__( 'Stripe checkout method', 'super-forms' ),
                        'default' =>  'single',
                        'type' => 'select',
                        'values' => array(
                            'single' => esc_html__( 'Single product or service checkout', 'super-forms' ),
                            'subscription' => esc_html__( 'Subscription checkout', 'super-forms' )
                        ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),

                    'stripe_payment_methods' => array(
                        'name' => esc_html__( 'Payment method', 'super-forms' ),
                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ) . '. ' . esc_html__( 'Accepted values are', 'super-forms' ) . ': <code>card</code> <code>acss_debit</code> <code>afterpay_clearpay</code> <code>alipay</code> <code>au_becs_debit</code> <code>bacs_debit</code> <code>bancontact</code> <code>boleto</code> <code>eps</code> <code>fpx</code> <code>giropay</code> <code>grabpay</code> <code>ideal</code> <code>klarna</code> <code>konbini</code> <code>oxxo</code> <code>p24</code> <code>paynow</code> <code>sepa_debit</code> <code>sofort</code> <code>us_bank_account</code> <code>wechat_pay</code>',
                        'default' =>  'card',
                        'filter' => true,
                        'parent' => 'stripe_method',
                        'filter_value' => 'single,subscription',
                    ),
                    'stripe_currency' => array(
                        'name' => esc_html__( 'Currency', 'super-forms' ),
                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ) . '. ' . esc_html__( 'Three-letter ISO currency code e.g USD, CAD, EUR', 'super-forms' ),
                        'default' =>  'card',
                        'filter' => true,
                        'parent' => 'stripe_method',
                        'filter_value' => 'single,subscription',
                    ),

                    // Subscription checkout settings
                    'stripe_plan_id' => array(
                        'name' => esc_html__( 'Subscription Plan ID (should look similar to: plan_G0FvDp6vZvdwRZ)', 'super-forms' ),
                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ) . '. ' . sprintf( esc_html__( 'You can find your plan ID’s under %sBilling > Products > Pricing plans%s.', 'super-forms' ), '<a target="_blank" href="https://dashboard.stripe.com/subscriptions/products/">', '</a>' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_method',
                        'filter_value' => 'subscription',
                    ),
                    'stripe_billing_email' => array(
                        'name' => esc_html__( 'Billing E-mail address (required)', 'super-forms' ),
                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_method',
                        'filter_value' => 'subscription',
                    ),


                    // Single checkout settings
                    'stripe_amount' => array(
                        'name' => esc_html__( 'Amount to charge', 'super-forms' ),
                        'label' => esc_html__( 'You are allowed to use {tags}.', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_method',
                        'filter_value' => 'single',
                    ),
                    'stripe_description' => array(
                        'name' => esc_html__( 'Description', 'super-forms' ),
                        'label' => esc_html__( 'An arbitrary string which you can attach to a Charge object. It is displayed when in the web interface alongside the charge. Note that if you use Stripe to send automatic email receipts to your customers, your receipt emails will include the description of the charge(s) that they are describing.', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_currency' => array(
                        'name' => esc_html__( 'Currency', 'super-forms' ),
                        'label' => sprintf( esc_html__( 'Three-letter ISO code for the currency e.g: USD, AUD, EUR. List of %ssupported currencies%s.', 'super-forms' ), '<a target="_blank" href="https://stripe.com/docs/currencies">', '</a>' ),
                        'default' =>  'usd',
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_return_url' => array(
                        'name' => esc_html__( 'Thank you page (return URL)', 'super-forms' ),
                        'label' => esc_html__( 'Return the customer to this page after a sucessfull payment. Leave blank to redirect to home page.', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                   
                    'stripe_completed_entry_status' => array(
                        'name' => esc_html__( 'Entry status after payment completed', 'super-forms' ),
                        'label' => sprintf( esc_html__( 'You can add custom statuses via %sSuper Forms > Settings > Backend Settings%s if needed', 'super-forms' ), '<a target="blank" href="' . esc_url(admin_url() . 'admin.php?page=super_settings#backend-settings') . '">', '</a>' ),
                        'default' =>  'completed',
                        'type' => 'select',
                        'values' => $statuses,
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),

                    // Advanced settings
                    'stripe_checkout_advanced' => array(
                        'hidden_setting' => true,
                        'default' =>  '',
                        'type' => 'checkbox',
                        'values' => array(
                            'true' => esc_html__( 'Show advanced settings', 'super-forms' ),
                        ),
                        'filter' => true,
                        'parent' => 'stripe_checkout',
                        'filter_value' => 'true',
                    ),
                    'stripe_statement_descriptor' => array(
                        'name' => esc_html__( 'Statement descriptor', 'super-forms' ),
                        'label' => esc_html__( 'You can use this value as the complete description that appears on your customers statements. Must contain at least one letter, maximum 22 characters. An arbitrary string to be displayed on your customer’s statement. As an example, if your website is "RunClub" and the item you’re charging for is a race ticket, you may want to specify "RunClub 5K race ticket".', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),

                    // Owner
                    'stripe_email' => array(
                        'name' => esc_html__( 'Owner’s email address', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_name' => array(
                        'name' => esc_html__( 'Owner’s full name', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_city' => array(
                        'name' => esc_html__( 'Owner’s City', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_country' => array(
                        'name' => esc_html__( 'Owner’s Country', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_line1' => array(
                        'name' => esc_html__( 'Owner’s Address line1', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_line2' => array(
                        'name' => esc_html__( 'Owner’s Address line 2', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_postal_code' => array(
                        'name' => esc_html__( 'Owner’s Postal code', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_state' => array(
                        'name' => esc_html__( 'Owner’s State', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    'stripe_phone' => array(
                        'name' => esc_html__( 'Owner’s phone number', 'super-forms' ),
                        'label' => esc_html__( '(optional)', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),

                    // Carrier
                    'stripe_carrier' => array(
                        'name' => esc_html__( 'Carrier (optional)', 'super-forms' ),
                        'label' => esc_html__( 'The delivery service that shipped a physical product, such as Fedex, UPS, USPS, etc.', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),
                    // Tracking number
                    'stripe_tracking_number' => array(
                        'name' => esc_html__( 'Tracking number (optional)', 'super-forms' ),
                        'label' => esc_html__( 'The tracking number for a physical product, obtained from the delivery service. If multiple tracking numbers were generated for this purchase, please separate them with commas.', 'super-forms' ),
                        'default' =>  '',
                        'filter' => true,
                        'parent' => 'stripe_checkout_advanced',
                        'filter_value' => 'true',
                    ),

                )
            );
            if (class_exists('SUPER_Frontend_Posting')) {
                $array['stripe_checkout']['fields']['stripe_completed_post_status'] = array(
                    'name' => esc_html__( 'Post status after payment complete', 'super-forms' ),
                    'label' => esc_html__( 'Only used for Front-end posting', 'super-forms' ),
                    'default' =>  'publish',
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
                    'parent' => 'stripe_checkout',
                    'filter_value' => 'true',
                );
            }
            if (class_exists('SUPER_Register_Login')) {
                global $wp_roles;
                $all_roles = $wp_roles->roles;
                $editable_roles = apply_filters( 'editable_roles', $all_roles );
                $roles = array();
                foreach( $editable_roles as $k => $v ) {
                    $roles[$k] = $v['name'];
                }
                $array['stripe_checkout']['fields']['stripe_completed_signup_status'] = array(
                    'name' => esc_html__( 'Registered user login status after payment complete', 'super-forms' ),
                    'label' => esc_html__( 'Only used for Register & Login feature', 'super-forms' ),
                    'default' =>  'active',
                    'type' => 'select',
                    'values' => array(
                        'active' => esc_html__( 'Active (default)', 'super-forms' ),
                        'pending' => esc_html__( 'Pending', 'super-forms' ),
                        'blocked' => esc_html__( 'Blocked', 'super-forms' ),
                    ),
                    'filter' => true,
                    'parent' => 'stripe_checkout',
                    'filter_value' => 'true',
                );
				$array['stripe_checkout']['fields']['stripe_completed_user_role'] = array(
					'name' => esc_html__( 'Change user role after payment complete', 'super-forms' ),
					'label' => esc_html__( 'Only used for Register & Login feature', 'super-forms' ),
					'default' =>  '',
					'type' => 'select',
					'values' => array_merge($roles, array('' => esc_html__( 'Do not change role', 'super-forms' ))),
					'filter' => true,
					'parent' => 'stripe_checkout',
					'filter_value' => 'true',
				);
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
